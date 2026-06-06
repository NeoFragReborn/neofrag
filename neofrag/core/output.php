<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Output extends Core
{
	const ZONES   = 2;
	const ROWS    = 4;
	const COLS    = 8;
	const WIDGETS = 16;

	public $data;

	protected $_module;
	protected $_theme;
	protected $_title;
	protected $_error;
	protected $_route;
	protected $_admin_theme = 'admin';

	public function __construct($config = [])
	{
		if (isset($config['title']) && is_a($config['title'], 'closure'))
		{
			$this->_title = $config['title'];
		}
		else
		{
			$this->_title = function(){
				if (($this->url->segments[0] != 'index' || $this->url->subdomain) && $this->module() && ($title = $this->data->get('module', 'title')))
				{
					return $title.' | '.$this->config->nf_name;
				}

				return $this->config->nf_description.' | '.$this->config->nf_name;
			};
		}

		if (isset($config['route']) && is_a($config['route'], 'closure'))
		{
			$this->_route = $config['route'];
		}

		if (isset($config['admin_theme']))
		{
			$this->_admin_theme = $config['admin_theme'];
		}

		$this->data = $this->array;

		$this	->on('output', function($output = ''){
					$output = (string)$output;

					$this->trigger('output_loaded');

					echo $output;

					if ($this->url->cli)
					{
						echo "\n";
					}

					if (NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
					{
						$this->debug('OUTPUT', 'HTTP_HEADER', json_encode(headers_list()));
					}

					$this->trigger('output_rendered');

					exit;
				})
				->debug->bar('output', function(){
					return $this->data->__toArray();
				});
	}

	public function __invoke()
	{
		header('Content-Type: text/html; charset=UTF-8');

		// R2.0 — IP banlist : reject early, avant tout routing/render.
		// Skip silencieusement si la migration nf_ip_banlist n'a pas encore été exécutée
		// (sinon une exception "Table doesn't exist" polluerait le query builder partagé,
		// cassant TOUTES les requêtes suivantes — j'ai vu le bug en live, vraiment).
		try
		{
			$banlist_active = FALSE;
			$driver         = $this->db->driver();
			if ($driver && method_exists($driver, 'tables'))
			{
				$tables = $driver->tables();
				$banlist_active = is_array($tables) && in_array('nf_ip_banlist', $tables, TRUE);
			}

			if ($banlist_active)
			{
				$client_ip = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
				$ban = $this->db	->select('ban_id', 'reason', 'UNIX_TIMESTAMP(expires_at) AS expires_ts')
									->from('nf_ip_banlist')
									->where('ip', $client_ip)
									->row(FALSE);

				if ($ban)
				{
					if (!empty($ban['expires_ts']) && (int)$ban['expires_ts'] < time())
					{
						// Expiré : on nettoie cette entrée mais on laisse passer
						$this->db->where('ban_id', (int)$ban['ban_id'])->delete('nf_ip_banlist');
					}
					else
					{
						http_response_code(403);
						header('Content-Type: text/html; charset=UTF-8');
						$reason = !empty($ban['reason']) ? htmlspecialchars($ban['reason']) : 'Accès refusé';
						echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title><style>body{font-family:-apple-system,sans-serif;max-width:600px;margin:80px auto;padding:24px;color:#333;}h1{color:#dc3545;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}</style></head><body>'
							.'<h1>403 — Accès refusé</h1>'
							.'<p>Ton IP <code>'.htmlspecialchars($client_ip).'</code> est bloquée sur ce site.</p>'
							.'<p><strong>Raison :</strong> '.$reason.'</p>'
							.'<p>Si tu penses qu\'il s\'agit d\'une erreur, contacte un administrateur.</p>'
							.'</body></html>';
						exit;
					}
				}
			}
		}
		catch (\Throwable $e)
		{
			// Choix fail-open (vs fail-closed) ASSUMÉ : sur erreur DB, on laisse passer.
			// Rationale : sur un CMS, une DB injoignable casse déjà tout le rendu — bloquer en
			// plus (403 pour tous) transformerait chaque hoquet DB en panne totale, sans réel
			// gain de sécurité (rien à abuser quand le site ne sert plus rien). On TRACE pour
			// qu'un contournement récurrent via erreurs DB ne passe pas inaperçu.
			error_log('[banlist] enforcement ignoré (fail-open assumé) : '.$e->getMessage());
		}

		$error = FALSE;

		try
		{
			$exec = function(){
				$segments = $this->url->segments;

				if ($this->_route)
				{
					call_user_func_array($this->_route, [&$segments]);
				}

				if ($segments[0] == 'index')
				{
					array_shift($segments);
					$segments = array_merge(explode('/', $this->config->nf_default_page), $segments);
				}
				else
				{
					if ($this->url->admin && $this->url->request != 'admin')
					{
						array_shift($segments);
					}

					if ($this->url->ajax)
					{
						array_shift($segments);
					}

					if (in_string('_', $segments[0]))
					{
						parent::error();
					}
				}

				if (!$this->url->admin && $this->url->ajax && $segments[0] == 'theme')
				{
					$module = $this->_theme = parent::theme($this->config->nf_default_theme);
					array_shift($segments);
				}
				else
				{
					if (($module = @parent::module(str_replace('-', '_', $segments[0]))) && $module->is_enabled())
					{
						array_shift($segments);
					}
					else if ($module || $this->url->admin || $this->url->ajax || !($module = @parent::module('pages')) || !$module->is_enabled())
					{
						parent::error();
					}
				}

				if ($this->url->admin)
				{
					// R1.9 — Exception : routes admin/access/preview/* doivent rester accessibles à l'admin réel
					// même quand effective_admin = FALSE (sinon admin coincé en preview, ne peut plus sortir).
					$is_preview_route = (strpos($this->url->request, 'admin/access/preview/') === 0);
					$is_real_admin    = isset($this->user) && !empty($this->user->admin);

					if (!($is_preview_route && $is_real_admin) && (!$this->access->effective_admin() || !$module->is_authorized()))
					{
						$this->error->unconnected();
						$this->error->unauthorized();
					}
				}

				$this->_module = $module;

				$this->data->set('module', 'title', $this->_module->info()->title);
				$this->data->set('module', 'icon',  $this->_module->info()->icon);

				$module->__init();

				//Méthode par défault
				if (empty($segments))
				{
					$method = 'index';
				}
				else if (strpos($segments[0], '_') === 0)
				{
					parent::error();
				}
				//Méthode définie par routage
				else if (!empty($module->info()->routes))
				{
					$method = $module->get_method($segments);
				}

				//Routage automatique
				if (!isset($method))
				{
					if (	array_key_exists(3, $segments) &&
							($model = @$module->model2($segments[0], $segments[2])) &&
							$model->check($segments[3]) &&
							($action = @$model->action(str_replace('-', '_', $segments[1]))) &&
							$action->__check()
						)
					{
						return $action;
					}
					else if (	array_key_exists(1, $segments) &&
								$segments[1] == 'create' &&
								($model = @$module->model2($segments[0])) &&
								($action = @$model->action(str_replace('-', '_', $segments[1]))) &&
								$action->__check()
						)
					{
						return $action;
					}

					$method = str_replace('-', '_', array_shift($segments));
				}

				$name = function($default = ''){
					$name = [];

					if ($this->url->cli)
					{
						$name[] = 'api';
					}
					else
					{
						if ($this->url->admin)
						{
							$name[] = 'admin';
						}

						if ($this->url->ajax)
						{
							$name[] = 'ajax';
						}
					}

					if ($default)
					{
						$name[] = $default;
					}

					return implode('_', $name);
				};

				$this->data->set('module', 'controller', $controller = $name());
				$this->data->set('module', 'method',     $method);

				if (!empty($page))
				{
					return $page();
				}

				//Checker Controller
				if ($has_checker = ($checker = @$module->controller($name('checker'))) && $checker->has_method($method))
				{
					$segments = call_user_func_array([$checker, $method], is_array($segments) ? array_values($segments) : $segments);
				}

				if ((!$has_checker && $this->url->extension == '') || ($has_checker && $checker->valid() && is_array($segments)))
				{
					//Controller
					if (($controller = @$module->controller($controller ?: 'index')) && $controller->has_method($method))
					{
						// PHP 8 : call_user_func_array refuse les arrays avec clés string+numeric mixtes
						// (interprété comme named args mélangés). On force un array purement indexé.
						return call_user_func_array([$controller, $method], array_values($segments));
					}
				}

				parent::error();
			};

			if ($this->url->cli)
			{
				$output = $exec();
			}
			else
			{
				ob_start();

				$output = $exec();

				$output = $this	->array()
								->append(preg_replace('/\xEF\xBB\xBF/', '', ob_get_clean()))
								->append($output);
			}
		}
		catch (\NF\NeoFrag\Exception $e)
		{
			$error = TRUE;
			$this->_error = (string)$e;
		}

		if ($this->url->ajax())
		{
			$output = $this->_error ?: $output;
		}
		else
		{
			// R1.9 — En preview : effective_admin=FALSE → bascule sur le thème public (puisque admin théorique = bob)
			// SAUF pour les routes admin/access/preview/* où l'admin réel doit garder le thème admin pour exit
			$is_preview_route = $this->url->admin && (strpos($this->url->request, 'admin/access/preview/') === 0);
			$is_real_admin    = isset($this->user) && !empty($this->user->admin);
			$show_admin_theme = $this->url->admin
				&& (
					($is_preview_route && $is_real_admin)
					|| ($this->access->effective_admin() && (!$this->_module || $this->_module->is_authorized()))
				);
			$this->_theme = NULL;

			// Sélecteur de thème côté visiteur : cookie `nf_theme` → thème public installé (jamais admin).
			if (!$show_admin_theme && !empty($_COOKIE['nf_theme']))
			{
				$choice = preg_replace('/[^a-z0-9_]/i', '', (string) $_COOKIE['nf_theme']);

				if ($choice !== '' && strtolower($choice) !== 'admin' && ($theme = @parent::theme($choice)))
				{
					$this->_theme = $theme;
				}
			}

			if (!$this->_theme)
			{
				$this->_theme = parent::theme($show_admin_theme ? $this->_admin_theme : $this->config->nf_default_theme);
			}

			$css     = $this->data->get('css');
			$js      = $this->data->get('js');
			$js_load = $this->data->get('js_load');

			$this->data->set('css',     []);
			$this->data->set('js',      []);
			$this->data->set('js_load', []);

			$this->_theme->__init();

			$this->data->merge_if($css,     'css',     $css);
			$this->data->merge_if($js,      'js',      $js);
			$this->data->merge_if($js_load, 'js_load', $js_load);

			if (!$error)
			{
				$this->data->set('module', 'content', $output);
			}

			notifications();

			if (nf_demo())
			{
				NeoFrag()->js_load("(function(){var d=document;if(d.getElementById('nf-demo-bar'))return;var b=d.createElement('div');b.id='nf-demo-bar';b.innerHTML='Démo <strong>NeoFrag Reborn</strong> — réinitialisée régulièrement · connexion : <strong>demo / demo</strong>';b.style.cssText='position:fixed;top:0;left:0;right:0;z-index:99999;background:#1abc9c;color:#fff;text-align:center;padding:6px 12px;font:600 13px/1.5 system-ui,Segoe UI,sans-serif;box-shadow:0 2px 6px rgba(0,0,0,.25)';d.body.insertBefore(b,d.body.firstChild);d.body.style.paddingTop='32px';})();");
			}

			if (!$error && $this->_module->info()->name == 'live_editor')
			{
				$body = $this->_module;
			}
			else
			{
				$body = '';

				if ($this->live_editor())
				{
					$body = '<div id="live_editor" data-module-title="'.utf8_htmlentities($this->url->segments[0] == 'index' ? $this->label('Accueil', 'fas fa-map-marker-alt') : $this->data->get('module', 'title')).'"></div>';

					parent	::css('fonts/open-sans')
							->css('live-editor')
							->css('jquery-ui.min')
							->js('jquery-ui.min');
				}

				$body .= $this->url->maintenance ? $this->module() : $this->_theme->view('body');

				if ($modals = $this->session('modals'))
				{
					foreach ($this->session('modals') as $url)
					{
						$this->modal->ajax($url);
					}

					$this->session->destroy('modals');
				}

				if ($modals = $this->data->get('modals'))
				{
					$body .= implode($modals);
				}
			}

			if (!$this->url->ajax() && $this->access->effective_admin() && $this->url->request != 'admin/monitoring' && parent::module('monitoring')->need_checking())
			{
				$this->js_load('$.post(\''.url('admin/ajax/monitoring.json').'\', {refresh: false});');
			}

			$output = $this->_theme->view('theme/main', [
				'title'       => call_user_func($this->_title),
				'description' => trim((string)($this->data->get('module', 'description') ?: $this->config->nf_description)),
				'body'        => $body,
				'debug_bar'   => $this->debug->bar()
			]);
		}

		if ($this->url->extension == 'xml')
		{
			header('Content-Type: application/xml; charset=UTF-8');
			$output = '<?xml version="1.0" encoding="UTF-8"?>'."\r\n".$output;
		}
		else if ($this->url->extension == 'txt')
		{
			header('Content-Type: text/plain; charset=UTF-8');
			$output = utf8_html_entity_decode($output);
		}

		$this->trigger('output', $output);
	}

	public function module()
	{
		return $this->_module;
	}

	public function theme()
	{
		return $this->_theme;
	}

	public function error()
	{
		return $this->_error;
	}

	public function css()
	{
		if ($css = $this->data->get('css'))
		{
			return implode("\n", array_unique(array_map('strval', $css)))."\n";
		}
	}

	public function js()
	{
		if ($js = $this->data->get('js'))
		{
			return implode("\n", array_unique(array_map('strval', $js)))."\n";
		}
	}

	public function js_load()
	{
		if ($js_load = $this->data->get('js_load'))
		{
			return implode("\n", array_unique(array_map('strval', $js_load)))."\n";
		}
	}

	public function zone($zone_id)
	{
		static $dispositions;

		if ($dispositions === NULL)
		{
			$this->db	->from('nf_dispositions')
						->where('theme', $this->_theme->info()->name)
						->order_by('page DESC');

			$pages = ['page', '*', 'OR'];

			if ($this->url->segments[0] == 'index')
			{
				$pages[] = 'page';
				$pages[] = '/';
				$pages[] = 'OR';
			}

			for ($i = count($segments = $this->url->segments); $i > 0; $i--)
			{
				$pages[] = 'page';
				$pages[] = implode('/', array_slice($segments, 0, $i)).'/*';
				$pages[] = 'OR';
			}

			call_user_func_array([$this->db, 'where'], $pages);

			foreach ($this->db->get() as $disposition)
			{
				if (!isset($dispositions[$zone = $disposition['zone']]))
				{
					$dispositions[$zone] = $disposition;
				}
			}
		}

		if (!empty($dispositions[$zone_id]))
		{
			return parent::zone()->display($dispositions[$zone_id]);
		}

		return '';
	}

	public function json($data = NULL)
	{
		if ($js_load = $this->js_load())
		{
			$data['script'] = $js_load;
		}

		if ($css = $this->css())
		{
			$data['css'] = $css;
		}

		if ($js = $this->data->get('js'))
		{
			$data['js'] = array_values(array_unique(array_map(function($a){
				return $a->path();
			}, $js)));
		}

		$json = parent::json($data);
		$this->trigger('output', $json);
	}

	public function live_editor()
	{
		if (($live_editor = post('live_editor')) !== NULL && $this->access->effective_admin())
		{
			$this->session->set('live_editor', $live_editor = $live_editor ?: self::WIDGETS);
			return $live_editor;
		}

		return 0;
	}

	public function email($callback)
	{
		$this->url->external(TRUE);

		$data = $this->_data;
		$this->_data = $this->array;

		$theme = $this->_theme;
		$this->_theme = parent::theme($this->config->nf_default_theme);
		$this->_theme->__init();

		$callback();

		$this->url->external(FALSE);
		$this->_data = $data;
		$this->_theme = $theme;
	}

	function asset($file_path, $file_name = '')
	{
		if (check_file($file_path))
		{
			$date = filemtime($file_path);

			if (in_array($ext = extension($file_path), ['css', 'js']))
			{
				ob_start();
				include $file_path;
				$content = ob_get_clean();
			}
			else
			{
				$content = file_get_contents($file_path);
			}

			ob_end_clean();

			header('Last-Modified: '.date('r', $date));
			header('Etag: '.($etag = md5($content)));
			header('Content-Type: '.get_mime_by_extension($ext));

			if ($ext == 'zip')
			{
				header('Content-Disposition: attachment; filename="'.basename($file_name ?: $file_path).'"');
			}

			if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $date) || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag))
			{
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
			else
			{
				header('HTTP/1.1 200 OK');
				header('Content-Length: '.strlen($content));
				exit($content);
			}
		}
		else if (NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
		{
			$this->debug('INFO', 'ASSET', 'Not exists on disk');
		}
	}
}
