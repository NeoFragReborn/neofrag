<?php
declare(strict_types=1);
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

					if (nf_debogage_actif() || nf_trace_active())
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
		//
		// Le refus se DÉCIDE dans le `try` (fail-open sur erreur DB) mais s'AFFICHE après lui : une
		// panne dans la traduction de la page ne doit pas se changer en « laisse passer ».
		$refus = NULL;

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
						$refus = [
							'ip'     => (string) $client_ip,
							'raison' => (string) ($ban['reason'] ?? ''),
						];
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

		if ($refus)
		{
			http_response_code(403);
			header('Content-Type: text/html; charset=UTF-8');
			$titre  = htmlspecialchars((string) $this->lang('Accès refusé'));
			$reason = $refus['raison'] !== '' ? htmlspecialchars($refus['raison']) : $titre;
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 — '.$titre.'</title><style>body{font-family:-apple-system,sans-serif;max-width:600px;margin:80px auto;padding:24px;color:#333;}h1{color:#dc3545;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}</style></head><body>'
				.'<h1>403 — '.$titre.'</h1>'
				.'<p>'.$this->lang('Ton IP %s est bloquée sur ce site.', '<code>'.htmlspecialchars($refus['ip']).'</code>').'</p>'
				.'<p><strong>'.$this->lang('Raison :').'</strong> '.$reason.'</p>'
				.'<p>'.$this->lang('Si tu penses qu\'il s\'agit d\'une erreur, contacte un administrateur.').'</p>'
				.'</body></html>';
			exit;
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
				// Méthode interne protégée : un segment commençant par `_` est rejeté. On rejette AUSSI
				// `-` car l'auto-routage convertit `-`→`_` (str_replace plus bas) → `/module/-foo`
				// atteindrait sinon la méthode interne `_foo`.
				else if (strpos($segments[0], '_') === 0 || strpos($segments[0], '-') === 0)
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

				//Checker Controller — durci : une erreur inattendue (ex. table d'un module à moitié
				//installé) bascule sur la page d'erreur standard au lieu de fataler tout le site.
				// Les deux variables sont posées AVANT le try : si `controller()` lève, le bloc de
				// diagnostic plus bas les lit quand même, et il les lit définies.
				$checker     = NULL;
				$has_checker = FALSE;

				// Le filet de la démonstration (nf_demo_requete_refusee) : sur un module verrouillé, une
				// requête qui agit est refusée ici, avant le checker comme le contrôleur.
				if ($this->url->admin && nf_demo_requete_refusee((string) $module->info()->name, (string) $controller, (string) $method))
				{
					$refus = (string) NeoFrag()->lang('Action désactivée sur le site de démonstration.');

					if ($this->url->ajax || $this->url->extension !== '')
					{
						http_response_code(403);
						header('Content-Type: application/json; charset=utf-8');
						exit(json_encode(['error' => $refus, 'demo' => TRUE], JSON_UNESCAPED_UNICODE));
					}

					notify($refus, 'warning');
					redirect_back('admin/'.$module->info()->name);
				}

				// Une adresse à laquelle il manque un segment (`user/lost-password` sans son jeton) fait
				// lever au checker une ArgumentCountError : ce n'est pas une panne, c'est un 404.
				$adresse_incomplete = FALSE;

				try
				{
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
				}
				catch (\NF\NeoFrag\Exception $e)
				{
					throw $e; // erreurs framework (404, redirections…) : flux inchangé
				}
				catch (\Throwable $e)
				{
					if ($has_checker && $checker && $e instanceof \ArgumentCountError
						&& str_starts_with($e->getMessage(), 'Too few arguments to function '.get_class($checker).'::'.$method.'()'))
					{
						$adresse_incomplete = TRUE;
					}

					/*
					 * On dit OÙ, pas seulement QUOI.
					 *
					 * Un message seul — « htmlspecialchars((string) ()): Argument #1 must be of type string,
					 * Lang given » — ne désigne aucun fichier, et le module fautif est neutralisé
					 * en silence : la page s'affiche, amputée, sans rien dire. Retrouver l'origine
					 * demandait de rejouer le flux et de chercher à la main.
					 *
					 * L'endroit où l'erreur est née est le bon quand il appartient au PRODUIT — pour une
					 * fonction de PHP comme `htmlspecialchars`, c'est déjà la ligne qui l'appelle. On ne
					 * remonte la pile que si elle est née dans une bibliothèque (`vendor/`) : remonter à
					 * chaque fois désignait l'aiguillage du cœur (`output.php`) au lieu du module fautif
					 * (relevé le 2026-10-02).
					 */
					$origine = $e->getFile().':'.$e->getLine();

					if (str_contains(str_replace('\\', '/', $e->getFile()), '/vendor/'))
					{
						foreach ($e->getTrace() as $image)
						{
							if (!empty($image['file']) && !str_contains(str_replace('\\', '/', $image['file']), '/vendor/'))
							{
								$origine = $image['file'].':'.$image['line'];
								break;
							}
						}
					}

					$reference = $adresse_incomplete ? '' : nf_journaliser_erreur('output', $e->getMessage(), nf_chemin_relatif($origine));

					// En débogage, l'erreur remonte en brut — pour un administrateur connecté seulement.
					if (nf_debogage_visible())
					{
						throw $e;
					}
					if (ob_get_level())
					{
						ob_clean(); // jette la sortie partielle du module en échec → page d'erreur propre
					}

					// Une page qui plante est une ERREUR INTERNE (500), pas une adresse introuvable : le
					// visiteur lit la référence que porte la ligne du journal.
					if ($reference !== '')
					{
						$this->error->interne($reference);
					}
				}

				// POURQUOI ce 404 ? Un checker qui refuse rendait un `404` nu, impossible à
				// distinguer d'une mauvaise adresse — alors que le serveur connaît le motif exact.
				// Il part désormais TOUJOURS aux journaux ; en mode debug il est rendu au client,
				// et en 400 (« ta requête est mal formée ») plutôt qu'en 404 (« ça n'existe pas »),
				// ce qui est déjà la moitié du diagnostic.
				if ($has_checker && $checker)
				{
					$motifs  = function_exists('nf_refus') ? nf_refus() : [];
					$deposes = (bool) $motifs;

					if (!$motifs)
					{
						// Aucun motif déposé : on décrit au moins LEQUEL des deux verrous a cédé.
						$motifs[] = $adresse_incomplete
							? 'adresse incomplète : il manque un segment à '.$method.'()'
							: (!$checker->valid()
								? 'extension d\'URL refusée par le checker (demandée : '.($this->url->extension ?: 'aucune').')'
								: 'le checker a rendu '.gettype($segments).' au lieu d\'un tableau de segments');
					}

					$diagnostic = sprintf(
						'%s → %s::%s — %s',
						$this->url->request ?: '/',
						get_class($checker),
						$method,
						implode(' ; ', $motifs)
					);

					/*
					 * Un refus ORDINAIRE ne va pas aux journaux.
					 *
					 * Le module `pages` est le routeur de repli : toute adresse qui n'appartient à
					 * aucun module lui arrive, et son « non » est la définition même d'un 404. Le
					 * journal de production — notre premier instrument de diagnostic — recevait
					 * donc une ligne d'ERREUR à chaque visiteur égaré et à chaque robot qui sonde.
					 *
					 * Le diagnostic reste entier pour le développeur : il part à l'écran juste en
					 * dessous quand `NEOFRAG_DEBUG_BAR` est actif.
					 */
					/*
					 * Sont ordinaires aussi, depuis le 2026-09-23 :
					 *   - l'élément INTROUVABLE : le checker ne rend rien, sans déposer de motif — c'est la
					 *     définition d'un 404 (`news/5/ancien-titre`, `user/99999/x`). Le journal de la
					 *     démonstration en portait 104 lignes d'« erreur », écrites par des outils qui sondent ;
					 *   - l'adresse INCOMPLÈTE, à laquelle il manque un segment.
					 * Un checker qui veut qu'un refus se lise au journal le dit avec `nf_refus('…')`.
					 */
					$ordinaire = (method_exists($checker, 'refus_ordinaire') && $checker->refus_ordinaire())
						|| $adresse_incomplete
						|| (!$deposes && $checker->valid() && ($segments === NULL || $segments === FALSE));

					if (!$ordinaire)
					{
						error_log('[checker] '.$diagnostic);
					}

					if (nf_debogage_visible() && !$this->url->cli)
					{
						if (ob_get_level())
						{
							ob_clean();
						}

						if (!headers_sent())
						{
							http_response_code(400);
							header('Content-Type: text/plain; charset=utf-8');
						}

						exit("Requête refusée par un checker.\n\n".$diagnostic
							."\n\n(réponse 400 et non 404 parce que NEOFRAG_DEBUG_BAR est actif ;"
							." en production, ce motif ne part qu'aux journaux)\n");
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

			// Le thème choisi par le visiteur (menu du pied de page, cf. helpers/theme.php) : jamais en
			// administration, seulement si l'administrateur laisse choisir, et seulement s'il est installé.
			// Le cookie est propre à CE site : le choix fait sur la démonstration ne suit plus le visiteur
			// sur le site vitrine (2026-09-23).
			if (!$show_admin_theme && ($choix = nf_theme_du_visiteur()) !== '' && ($theme = @parent::theme($choix)))
			{
				$this->_theme = $theme;
			}

			$demande = $show_admin_theme ? $this->_admin_theme : $this->config->nf_default_theme;

			if (!$this->_theme)
			{
				$this->_theme = parent::theme($demande);
			}

			/**
			 * FILET : un thème peut ne pas se charger.
			 *
			 * Le réglage `nf_default_theme` n'est qu'un nom en base. Il suffit que le thème
			 * correspondant soit désinstallé, que son dossier disparaisse, ou que le réglage soit
			 * écrit à la main pour que la résolution rende NULL — et le `__init()` juste en dessous
			 * faisait alors tomber TOUT LE SITE sur un 500 muet, page blanche, pas un mot pour dire
			 * pourquoi. Observé pour de vrai en désignant un thème non installé.
			 *
			 * Un site debout dans un thème de secours vaut mieux qu'un site éteint : on retombe sur
			 * le thème par défaut du produit, puis sur celui de l'administration, et on laisse une
			 * trace explicite pour que la cause ne soit pas à redécouvrir.
			 */
			if (!$this->_theme)
			{
				foreach (['nebula', $this->_admin_theme] as $secours)
				{
					if ($secours !== $demande && ($this->_theme = parent::theme($secours)))
					{
						error_log('[theme] « '.$demande.' » introuvable ou non installé : repli sur « '.$secours.' »');
						break;
					}
				}
			}

			if (!$this->_theme)
			{
				// Même le secours manque : l'installation est cassée. On le dit, plutôt que de
				// fataler sur un appel de méthode dont le message ne désigne pas la cause.
				error_log('[theme] aucun thème chargeable (demandé : « '.$demande.' »)');
				http_response_code(500);
				exit((string) $this->lang('Aucun thème n\'est installé : le site ne peut pas être affiché.'));
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

			/*
			 * Une page d'erreur (introuvable, interdite) prend la place du contenu du module.
			 *
			 * Jusqu'ici, seul le thème d'administration affichait l'erreur, en la lisant lui-même :
			 * sur tous les thèmes publics, le bloc du module restait vide et un 404 n'était qu'une
			 * page blanche entre l'en-tête et le pied de page, sans un mot (relevé le
			 * 2026-10-01 sur un lien du bot Discord). Défaut hérité de NeoFrag 0.4.0.
			 */
			$this->data->set('module', 'content', $error ? $this->_error : $this->bandeau_langue().$output);

			notifications();

			/*
			 * L'inscription du service worker, et seulement quand le réglage est actif.
			 *
			 * Ne PAS inscrire quand il est éteint ne laisse personne en plan : un worker déjà
			 * installé revérifie son script à chaque navigation, reçoit alors la version qui se
			 * retire, et disparaît de lui-même. C'est le chemin de sortie, et il ne dépend pas
			 * d'une page qui penserait à le déclencher.
			 *
			 * Jamais en administration : rien n'y gagne à être gardé, et c'est l'endroit d'où l'on
			 * éteint l'interrupteur — il doit rester joignable quoi qu'il arrive.
			 */
			if ($this->config->nf_pwa && !$this->url->admin && !$this->url->ajax)
			{
				NeoFrag()->js_load("if ('serviceWorker' in navigator) { navigator.serviceWorker.register(".json_encode($this->url->base.'service-worker.js').").catch(function(){}); }");
			}

			if (nf_demo())
			{
				NeoFrag()->js_load("(function(){var d=document;if(d.getElementById('nf-demo-bar'))return;var b=d.createElement('div');b.id='nf-demo-bar';b.innerHTML=".json_encode((string) $this->lang('Démo %s — réinitialisée régulièrement · connexion : %s', '<strong>NeoFrag Reborn</strong>', '<strong>demo / demo</strong>')).";b.style.cssText='position:fixed;top:0;left:0;right:0;z-index:99999;background:#1abc9c;color:#fff;text-align:center;padding:6px 12px;font:600 13px/1.5 system-ui,Segoe UI,sans-serif;box-shadow:0 2px 6px rgba(0,0,0,.25)';d.body.insertBefore(b,d.body.firstChild);var s=d.createElement('style');s.textContent='#nf-toast-container{top:var(--nf-demo-bar,32px)!important}';d.head.appendChild(s);var h=function(){var px=b.offsetHeight+'px';d.documentElement.style.setProperty('--nf-demo-bar',px);d.body.style.paddingTop=px;};h();window.addEventListener('resize',h);})();");
			// La bannière mesure sa hauteur (deux lignes sur un téléphone) et décale d'autant la page et
			// les notifications, qu'elle recouvrait (relevé le 2026-10-02).
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
							->js('sortable.lib.min');
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
				$this->js_load('NF.post(\''.url('admin/ajax/monitoring.json').'\', {refresh: false});');
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
		else if ($this->url->extension == 'webmanifest')
		{
			/*
			 * Le manifeste d'application a son type propre.
			 *
			 * Servi en `text/html` — ce qui arrivait avant cette ligne — certains navigateurs
			 * refusent de l'interpréter, et le site cesse d'être installable sans qu'aucun message
			 * ne le dise. Le JSON part tel quel : `json_encode()` produit déjà de l'UTF-8, et le
			 * passer par `utf8_html_entity_decode()` comme le texte brut le casserait.
			 */
			header('Content-Type: application/manifest+json; charset=UTF-8');
		}
		else if ($this->url->request === 'service-worker.js')
		{
			/*
			 * Le service worker, et lui seul parmi les `.js` qui passent par ici — les feuilles et
			 * les scripts du produit sont servis par le routeur d'assets, pas par le rendu.
			 *
			 * `Service-Worker-Allowed` : sans cet en-tête, un navigateur refuse une portée plus
			 * large que le dossier du script. Ici les deux coïncident, mais l'écrire rend la règle
			 * explicite le jour où le site vit dans un sous-dossier.
			 */
			header('Content-Type: text/javascript; charset=UTF-8');
			header('Service-Worker-Allowed: '.$this->url->base);
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
			// `trim` n'est pas cosmétique. Une zone DÉCLARÉE mais SANS widget — le cas de la
			// bannière d'extend sur toute page autre que l'accueil — produit ici un rendu fait de
			// seuls blancs : non vide, donc VRAI. Or les thèmes gardent leurs conteneurs décorés
			// par « if ($zone = $this->output->region('banner')) » avant d'émettre un div décoré
			// qui contient $zone.
			//
			// Le garde passait, et la page affichait un bandeau bleu de 96 px parfaitement vide —
			// ainsi qu'une section « avant-contenu » vide de 28 px. Constaté sur le forum en thème
			// extend, mais le motif vaut pour tout thème qui décore une région : c'est le cœur qui
			// doit dire « il n'y a rien », pas chaque gabarit qui doit y penser.
			//
			// Le cast est nécessaire : `display()` rend un objet à __toString, que `trim()` refuse.
			return trim((string) parent::zone()->display($dispositions[$zone_id]));
		}

		return '';
	}

	/**
	 * Rendu d'une zone par NOM de région (sémantique) plutôt que par index numérique. Le thème déclare
	 * une correspondance `regions` (nom → titre de zone) dans son __info() ; on résout le titre en index
	 * via info()->zones, puis on délègue à zone(). Purement additif : zone() reste utilisable tel quel.
	 *   body.tpl :  $this->output->region('content')   au lieu de   $this->output->zone(2)
	 * Un thème sans map `regions`, ou un nom inconnu, renvoie '' (comportement neutre).
	 */
	public function region($name)
	{
		$regions = $this->_theme->info()->regions ?? [];

		if (empty($regions[$name]))
		{
			return '';
		}

		$index = array_search($regions[$name], $this->_theme->info()->zones, TRUE);

		return $index === FALSE ? '' : $this->zone($index);
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
			$ext  = extension($file_path);

			/**
			 * Les feuilles et les scripts du projet peuvent contenir du PHP — une couleur de
			 * thème calculée, un libellé traduit — et sont donc interprétés. Mais ils l'étaient
			 * TOUS, bibliothèques tierces comprises : le greffon `codesample` de TinyMCE embarque
			 * des expressions régulières qui décrivent la syntaxe de PHP, et la suite `<?=` qu'on
			 * y trouve suffit à faire basculer l'interpréteur. Il s'arrêtait dessus sur une erreur
			 * de syntaxe, journalisée à chaque chargement, et servait un fichier tronqué : le
			 * greffon ne fonctionnait pas.
			 *
			 * Chercher une balise dans le contenu ne suffit donc pas à distinguer les deux cas —
			 * une bibliothèque qui PARLE de PHP en contient forcément. La règle retenue est celle
			 * que la configuration du serveur applique déjà : un fichier MINIFIÉ est une
			 * bibliothèque tierce, jamais une source du projet, et ne s'interprète pas. Pour les
			 * autres, on n'interprète que s'il y a réellement une balise — ce qui évite au passage
			 * qu'un fichier déposé par un utilisateur soit exécuté par ce chemin.
			 */
			$brut    = (string)file_get_contents($file_path);
			$minifie = (bool)preg_match('/\.min\.(?:css|js)$/i', $file_path);

			if (in_array($ext, ['css', 'js']) && !$minifie && preg_match('/<\?(?:php\b|=)/', $brut))
			{
				ob_start();

				// Le script d'un ADDON s'interprète au nom de cet addon : `$this->lang()` y cherche alors
				// dans SES fichiers de langue, puis dans ceux du cœur — comme ses vues. Interprété au nom
				// du cœur, il ne trouvait que les traductions du cœur : `modules/access/js/matrix.js`
				// affichait « Erreur de sauvegarde » sur le site anglais, sa traduction étant rangée
				// dans le module (mesuré en production le 2026-09-23).
				if ($proprietaire = $this->proprietaire_asset($file_path))
				{
					(function() use ($file_path){
						include $file_path;
					})->call($proprietaire);
				}
				else
				{
					include $file_path;
				}

				$content = ob_get_clean();
			}
			else
			{
				$content = $brut;
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
		else if (nf_debogage_actif() || nf_trace_active())
		{
			$this->debug('INFO', 'ASSET', 'Not exists on disk');
		}
	}

	/**
	 * L'addon auquel appartient un fichier d'asset : `modules/forum/js/forum.js` → le module forum.
	 *
	 * Une surcharge appartient à l'addon qu'elle remplace — `themes/nebula/overrides/modules/events/js/…`
	 * est un fichier du module events : c'est donc la DERNIÈRE mention d'un addon dans le chemin qui
	 * compte. Rien pour un fichier du cœur (`js/…`), ni pour un addon absent ou désactivé.
	 */
	private function proprietaire_asset(string $file_path): ?object
	{
		if (!preg_match_all('#(?:^|/)(modules|widgets|themes)/([a-z0-9_]+)/#', $file_path, $mentions, PREG_SET_ORDER))
		{
			return NULL;
		}

		[, $dossier, $nom] = end($mentions);

		$addon = NeoFrag()->{rtrim($dossier, 's')}($nom);

		return is_object($addon) ? $addon : NULL;
	}

	/**
	 * Le bandeau qui annonce qu'on sert une autre langue que celle demandee.
	 *
	 * POURQUOI ICI, ET PAS DANS LES THEMES. C'est le seul point ou TOUS les themes recoivent le
	 * contenu du module : le poser ici le donne aux sept d'un coup, et a ceux qu'on ecrira ensuite.
	 * Un theme qui voudrait le presenter autrement peut toujours lire `module.langue_servie`.
	 *
	 * POURQUOI UN BANDEAU TOUT COURT. Servir une autre langue sans le dire fait passer une page
	 * francaise pour la version anglaise du site. Le visiteur doit comprendre en une phrase ce qu'il
	 * regarde, et pouvoir revenir a une langue qui existe : d'ou la phrase, et les liens.
	 *
	 * La phrase est rendue dans la langue DEMANDEE, celle du visiteur, et non dans celle du contenu.
	 */
	private function bandeau_langue()
	{
		if (!($servie = $this->data->get('module', 'langue_servie')))
		{
			return '';
		}

		// `config->langs` est une liste NUMÉROTÉE d'addons de langue : on la range par nom pour
		// pouvoir nommer une langue à partir de son code. La lire comme associative ne rendait rien.
		$langs = [];

		foreach ((array)$this->config->langs as $addon)
		{
			$langs[(string)$addon->info()->name] = $addon;
		}

		$demandee = $this->config->lang->info()->name;

		if ($servie == $demandee || empty($langs[$servie]))
		{
			return '';
		}

		$nom = function($code) use ($langs){
			return !empty($langs[$code]) ? (string)$langs[$code]->info()->title : (string)$code;
		};

		$lien = $this->url->base.implode('/', array_merge([$servie], $this->url->segments)).$this->url->query;

		return '<div class="alert alert-info d-flex align-items-center gap-2" role="alert">'
			.'<i class="fas fa-language" aria-hidden="true"></i>'
			.'<span>'
			.utf8_htmlentities($this->lang('Ce contenu n\'existe pas en %s. Voici la version en %s.', $nom($demandee), $nom($servie)))
			.' <a href="'.utf8_htmlentities($lien).'" hreflang="'.utf8_htmlentities($servie).'">'
			.utf8_htmlentities($this->lang('Ouvrir la version d\'origine'))
			.'</a>'
			.'</span>'
			.'</div>';
	}
}
