<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

/**
 * Propriétés de l'URL courante (renseignées au routage, lues via __get). Annotations pour l'IDE + PHPStan.
 *
 * @property mixed $request
 * @property mixed $admin
 * @property mixed $segments
 * @property mixed $base
 * @property mixed $ajax
 * @property mixed $location
 * @property mixed $https
 * @property mixed $query
 * @property mixed $back
 * @property mixed $host
 * @property mixed $extension
 * @property mixed $cli
 * @property mixed $subdomain
 * @property mixed $redirect
 * @property mixed $production
 * @property mixed $maintenance
 * @property mixed $external
 * @property mixed $refresh
 * @property mixed $domain
 * @property mixed $ajax_header
 */
class Url extends Core
{
	/*
	 * L'adresse publique d'un module, quand elle diffère de son nom (2026-10-01). Le
	 * module `articles` est le Blog : son nom reste la clé de ses droits, de ses commentaires, de son
	 * widget et de la place de marché — le renommer casserait les sites qui l'ont installé —, mais il
	 * se visite sous `/blog`. Les liens que produit `url('articles/…')` deviennent `/blog/…`, et une
	 * ancienne adresse `/articles/…` redirige définitivement vers la nouvelle.
	 */
	public const ADRESSE_DE_MODULE = ['articles' => 'blog'];

	protected $_const      = [];
	protected $_external   = FALSE;
	protected $_production = FALSE;

	public function __construct($config = [])
	{
		if (preg_match('_/{2,}_', $_SERVER['REQUEST_URI']))
		{
			header('Location: '.preg_replace('_/+_', '/', $_SERVER['REQUEST_URI']));
			exit;
		}

		if (array_key_exists('production', $config))
		{
			$this->_production = $config['production'];
		}

		$this->_const['query']        = !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '';
		// HTTPS direct OU derrière un reverse-proxy / terminaison SSL (mutualisé Plesk/cPanel,
		// Cloudflare…) qui parle HTTP au PHP mais transmet le vrai schéma via X-Forwarded-Proto.
		// Sans ça les redirections canoniques (forum…) repartent en http → boucle derrière le proxy.
		$this->_const['https']        = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
		                             || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]) == 'https')
		                             || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on');
		$this->_const['location']     = ($this->https ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$this->_const['request']      = $_SERVER['REQUEST_URI'];
		$this->_const['cli']          = FALSE;

		$url = parse_url($this->location);

		$this->_const['host']         = $url['host'];
		$this->_const['domain']       = isset($config['domain']) && is_a($config['domain'], 'closure') ? call_user_func_array($config['domain'], [$this->_const]) : '';
		$this->_const['subdomain']    = $this->domain && $this->host != $this->domain ? substr($this->host, 0, -strlen($this->domain) - 1) : '';
		$this->_const['ajax_header']  = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
		$this->_const['base']         = $_SERVER['REDIRECT_CONTEXT'] ?? NULL;
		// La base du site se déduit de SCRIPT_NAME quand il se termine par `index.php` — ce que donnent
		// Apache, nginx et Caddy, à la racine (`/index.php`) comme en sous-dossier (`/site/index.php`).
		// Un SAPI qui y met autre chose (le serveur intégré de PHP 8.3 pose le chemin demandé pour une
		// adresse à extension sans fichier) donnait une base tronquée au hasard — `/fr/css/bootstrap` —
		// et tout asset partait en redirection de langue. Hors ce cas, la base est la racine.
		$script                       = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
		$base2                        = str_ends_with($script, 'index.php') ? substr($script, 0, -9) : '/';

		if (strpos($this->request, $this->base.$base2) === 0)
		{
			$this->_const['base'] .= $base2;
		}
		else
		{
			$this->_const['base'] .= '/';
		}

		if (substr($request = substr($url['path'], strlen($this->base)), -1) == '/')
		{
			header('Location: '.$this->base.substr($request, 0, -1));
			exit;
		}

		$segments = function($request) use ($config){
			$this->_const['request']   = $request;
			$this->_const['extension'] = extension($this->request);

			global $argv;

			if (php_sapi_name() == 'cli' && !empty($argv[1]))
			{
				$this->_const['cli']      = TRUE;
				$this->_const['segments'] = array_merge(explode('/', $argv[1]), array_slice($argv, 2));
				$this->_const['base']     = isset($config['base']) ? $config['base'] : '/';
				chdir(NEOFRAG_CMS);
			}
			else
			{
				$this->_const['segments'] = explode('/', $this->extension ? substr($this->request, 0, - strlen($this->extension) - 1) : ($this->request ?: 'index'));
			}

			if (preg_match('/^(humans|robots)\.txt$|^(favicon)\.ico$/', $this->request, $match))
			{
				$this->_const['segments'] = explode('/', 'ajax/settings/'.($match[1] ?: $match[2]));
			}
			else if (preg_match('/^sitemap\.xml$/', $this->request))
			{
				$this->_const['segments'] = explode('/', 'ajax/settings/sitemap');
			}
			else if (preg_match('/^[a-f0-9]{32}\.txt$/', $this->request))
			{
				// La clé IndexNow, que le moteur lit à la racine pour s'assurer qu'un envoi vient
				// bien du site. Toute autre clé de cette forme répond 404 (Settings\Controllers\Ajax::indexnow()).
				$this->_const['segments'] = explode('/', 'ajax/settings/indexnow');
			}
			else if (preg_match('/^manifest\.webmanifest$/', $this->request))
			{
				// Le manifeste d'application (PWA). Servi par le produit et non déposé en fichier :
				// son contenu dépend du site — nom, couleur, adresse de départ, favicon choisi.
				$this->_const['segments'] = explode('/', 'ajax/settings/manifest');
			}
			else if (preg_match('/^service-worker\.js$/', $this->request))
			{
				// Le service worker. Servi par le produit, et TOUJOURS : c'est en le récupérant
				// qu'un worker déjà installé apprend qu'il doit se retirer, quand le réglage est
				// éteint. Un fichier absent le laisserait en place pour toujours.
				//
				// À la RACINE, et pas ailleurs : la portée d'un worker est celle du dossier qui le
				// sert. Servi depuis `/js/`, il ne verrait que `/js/`.
				$this->_const['segments'] = explode('/', 'ajax/settings/service_worker');
			}

			if (isset($config['segments']) && is_a($config['segments'], 'closure'))
			{
				$this->_const['segments'] = call_user_func_array($config['segments'], [$this->_const]);
			}

			$this->_const['ancienne_adresse'] = FALSE;

			if (!$this->cli && isset($this->_const['segments'][0]))
			{
				if (($module = array_search($this->_const['segments'][0], self::ADRESSE_DE_MODULE, TRUE)) !== FALSE)
				{
					$this->_const['segments'][0] = $module;
				}
				else if (isset(self::ADRESSE_DE_MODULE[$this->_const['segments'][0]]))
				{
					$this->_const['ancienne_adresse'] = TRUE;
				}
			}

			$this->_const['admin'] = $this->segments[0] == 'admin';
			$this->_const['ajax']  = isset($this->segments[(int)$this->admin]) && $this->segments[(int)$this->admin] == 'ajax';

			if (isset($config['override']) && is_a($config['override'], 'closure'))
			{
				call_user_func_array($config['override'], [&$this->_const]);
			}
		};

		$segments($request);

		$this->on('config_lang_selected', function(){
			if (!empty($this->_const['ancienne_adresse']))
			{
				header('Location: '.url($this->request.$this->query), TRUE, 301);
				exit;
			}
		});

		$this->on('config_init', function() use ($segments){
			if (is_asset())
			{
				$this->output->asset($this->request);
			}
			else if ($this->config->nf_maintenance)
			{
				if ($this->config->nf_maintenance_opening && ($opening = $this->date($this->config->nf_maintenance_opening)) && $opening->diff() <= 0)
				{
					$this	->config('nf_maintenance', FALSE, 'bool')
							->config('nf_maintenance_opening', '');
				}
				else if (!$this->user->admin && !preg_match('#(ajax/user/(lost-password|login|auth)|user/lost-password/[a-z0-9]+|user/logout)#', $this->url->request))
				{
					header('HTTP/1.0 503 Service Unavailable');

					$this->_const['maintenance'] = TRUE;

					if (!empty($opening))
					{
						header('Retry-After: '.$opening->timezone('UTC')->format('D, d M Y H:i:s \G\M\T'));
					}

					if (!$this->url->ajax_header)
					{
						$segments('settings/maintenance');
					}
				}
			}

			if (nf_debogage_actif() || nf_trace_active())
			{
				$this->debug('URL', 'LOCATION', $this->location);
				$this->debug('URL', 'SEGMENTS', implode(' / ', $this->segments));
			}
		});

		$this->on('config_langs_listed', function($langs, &$lang) use (&$segments){
			if (array_key_exists($name = $this->segments[0], $langs))
			{
				$lang = $langs[$name];

				if (($request = preg_replace('_^'.$name.'/?_', '', $this->request)) != $this->request)
				{
					$segments($request);
				}
			}
			// Les fichiers RACINE que réclament les robots et les navigateurs — robots.txt, humans.txt, la clé IndexNow,
			// sitemap.xml, favicon.ico — n'ont pas de version par langue et ne doivent JAMAIS être
			// redirigés vers un préfixe : `Url::redirect()` répond en JSON dès que l'extension est txt,
			// xml ou json, si bien qu'un moteur de recherche demandant /sitemap.xml recevait
			// `{"redirect":"\/fr\/sitemap.xml"}` avec un code 200 — mesuré en production le 2026-09-20.
			// Ils sont déjà routés vers ajax/settings/* plus haut : on les sert, sans détour.
			// L'API (`api/…`) non plus : un programme ne suit pas une redirection de langue,
			// et un POST redirigé perdrait son corps. Elle répond dans la langue par défaut du site.
			else if (!defined('NEOFRAG_INSTALL') && !$this->cli && !preg_match('_^user/auth/|^api/_', $this->request)
			                                     && !preg_match('_^(humans|robots|[a-f0-9]{32})\.txt$|^sitemap\.xml$|^favicon\.ico$|^manifest\.webmanifest$|^service-worker\.js$_', $this->request))
			{
				$this->on('config_lang_selected', function(){
					// Une VRAIE redirection : sans cela, `/quoi.json` rendait 200 + JSON au lieu du
					// 404 que la page absente mérite. Vaut pour toute extension.
					$this->redirect_http(url($this->request.$this->query));
				});
			}
		});

		$this->on('output_loaded', function(){
			return;
			//TODO 0.2
			$url = preg_replace('#'.implode('|', [self::$route_patterns['pages'], self::$route_patterns['page']]).'#', '', $this->url->request);

			if (in_array($url, ['', 'index', 'admin']) || empty($_SERVER['HTTP_REFERER']))
			{
				$this->_data['session']['history'] = [];
			}

			if (!empty($this->_data['session']['history']) && end($this->_data['session']['history']) != $url && prev($this->_data['session']['history']) == $url)
			{
				array_pop($this->_data['session']['history']);
			}
			else
			{
				$this->_data['session']['history'][] = $url;
			}
		});

		$this->debug->bar('request', function(){
			return $this->_const;
		});
	}

	public function __set($name, $value)
	{
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->_const))
		{
			return $this->_const[$name];
		}

		return parent::__get($name);
	}

	public function __isset($name)
	{
		return isset($this->_const[$name]);
	}

	public function __invoke($url = '')
	{
		$domain = $args = '';

		if ($url == '#')
		{
			return $url;
		}
		else if (substr($url, 0, 2) == '//')
		{
			if (preg_match('_(.*)([?#].*)$_', $url, $match))
			{
				$url  = $match[1];
				$args = $match[2];
			}

			$url = explode('/', substr($url, 2));

			if (($subdomain = array_shift($url)) != $this->subdomain)
			{
				$domain = '//'.implode('.', array_filter([$subdomain, $this->domain]));
			}

			$url = implode('/', $url);
		}
		else if (is_valid_url($url))
		{
			// filter_var() juge valide « javascript://%0Aalert(1) » : une adresse absolue ne sort que si
			// son schéma est sans danger (nf_url_sure) — les liens des menus passent par ici (2026-10-02).
			return nf_url_sure((string) $url) ? $url : '#';
		}

		if (!$domain && $this->subdomain)
		{
			$url = explode('/', $url);

			if (current($url) == $this->config->nf_default_page)
			{
				array_shift($url);
			}

			$url = implode('/', $url);
		}

		$premier = strtok($url, '/?#');

		if ($premier !== FALSE && isset(self::ADRESSE_DE_MODULE[$premier]))
		{
			$url = self::ADRESSE_DE_MODULE[$premier].substr($url, strlen($premier));
		}

		if ($this->config->langs)
		{
			$url = rtrim($this->config->lang->info()->name.'/'.$url, '/');
		}

		if ($this->_external)
		{
			$domain = ($this->https ? 'https' : 'http').':'.($domain ?: '//'.$this->host);
		}

		$url = str_replace('/#', '#', $domain.$this->base.$url.$args);

		if ($this->_external && is_a($this->_external, 'closure'))
		{
			$url = call_user_func($this->_external, $url);
		}

		return $url;
	}

	/**
	 * La réponse attendue est-elle du JSON plutôt que du HTML ?
	 *
	 * Trois sources légitimes, et une quatrième qui a été retirée le 2026-09-21 :
	 *
	 *   - `cli`          : en ligne de commande, il n'y a pas de page à rendre ;
	 *   - `ajax`         : la route commence par `/ajax/` — elle le DÉCLARE (cf.
	 *                      `Module_Checker::ajax()`, employé à 49 endroits) ;
	 *   - le drapeau `ajax` du module, pour les cas qui se décident à l'exécution.
	 *
	 * CE QUI A ÉTÉ RETIRÉ : `in_array($this->extension, ['json', 'txt', 'xml'])`.
	 *
	 * Une extension de fichier ne dit rien de la nature de la requête. Cet amalgame a coûté deux
	 * fois : un moteur de recherche demandant `/sitemap.xml` recevait `{"redirect":…}` en **200**,
	 * et toute adresse inconnue en `.json`, `.xml` ou `.txt` faisait de même — dont
	 * `/update/version.json`, qu'interrogent les autres sites NeoFrag pour leurs mises à jour, et
	 * qui attendent un fichier ou un 404, pas un JSON de redirection.
	 *
	 * Les deux correctifs précédents avaient traité les symptômes — les quatre fichiers racine,
	 * puis la redirection de langue. Celui-ci traite la cause. un chantier interne.
	 *
	 * CE QU'ON N'A PAS FAIT, ET POURQUOI. `ajax_header` — l'en-tête `X-Requested-With` — est
	 * calculé juste à côté (ligne 71) et n'est lu nulle part. Le brancher ici semblait naturel ;
	 * mesuré, cela change le rendu de TOUTE requête XHR vers une page ordinaire : `/fr/forum`
	 * interrogé avec cet en-tête rendait alors un fragment au lieu d'une page. Deux scripts visent
	 * une adresse construite à l'exécution (`js/help.js`, `js/sortable.js`), qu'on ne peut pas
	 * énumérer ; le gain n'était pas mesurable, le risque si. C'est une question à instruire pour
	 * elle-même, pas un passager d'A17.
	 */
	public function ajax()
	{
		return $this->cli
			|| $this->ajax
			|| $this->output->data->get('module', 'ajax');
	}

	public function external($external)
	{
		$this->_external = $external;
		return $this;
	}

	public function production()
	{
		if (is_a($this->_production, 'closure'))
		{
			$this->_production = call_user_func($this->_production);
		}

		return $this->_production;
	}

	public function back()
	{
		if ($history = $this->session('session', 'history'))
		{
			if (($i = array_search($this->request, $history)) !== FALSE)
			{
				$history = array_slice($history, 0, $i);
			}

			$url = array_pop($history) ?: NULL;

			$this->session->set('session', 'history', $history);

			return $url;
		}
	}

	public function redirect($location)
	{
		if ($this->ajax())
		{
			$output = $this->json(['redirect' => $location], FALSE);
		}
		else
		{
			header('Location: '.$location);
			$output = '';
		}

		$this->trigger('output', $output);
	}

	/**
	 * Redirige par l'en-tête HTTP, TOUJOURS — là où `redirect()` répond un JSON dès que l'adresse
	 * finit par `.json`, `.txt` ou `.xml` (cf. `ajax()`).
	 *
	 * Ce mélange a coûté deux fois. Le 2026-09-17, un moteur de recherche demandant `/sitemap.xml`
	 * recevait `{"redirect":"\/fr\/sitemap.xml"}` avec un code **200** ; le correctif d'alors a sorti
	 * les quatre fichiers racine de la redirection de langue, sans toucher au motif. Le 2026-09-20,
	 * la mesure a montré que **toute** adresse inconnue en `.json`, `.xml` ou `.txt` rendait encore
	 * 200 — dont `/update/version.json`, que les autres sites NeoFrag interrogent pour leurs mises
	 * à jour, et qui n'attendent pas un JSON de redirection mais un fichier ou un 404.
	 *
	 * La cause est un amalgame : `ajax()` confond « la requête vient d'un XMLHttpRequest » et « la
	 * réponse attendue est du JSON ». Une extension de fichier ne dit rien de la première.
	 *
	 * `redirect()` n'est pas corrigée à la racine : 253 appels dans 67 fichiers en dépendent, dont des
	 * flux d'administration qui attendent bel et bien `{"redirect":…}`. Seule la redirection de langue
	 * — le chemin qu'emprunte toute adresse sans préfixe, donc toute adresse inconnue — passe ici.
	 */
	public function redirect_http($location, int $code = 302)
	{
		if (!headers_sent())
		{
			header('Location: '.$location, TRUE, $code);
		}

		// `trigger()` prend son second argument PAR RÉFÉRENCE : lui passer la chaîne vide en dur lève
		// une erreur fatale. Elle était invisible en HTTP — l'en-tête `Location` partant avant — et ne
		// se voyait que dans le journal, où elle a laissé 13 traces en production le 2026-09-20.
		$sortie = '';

		$this->trigger('output', $sortie);
	}

	public function refresh()
	{
		if ($this->ajax())
		{
			$output = $this->json(['success' => 'refresh'], FALSE);
		}
		else
		{
			//TODO la partie query est inclue dans request ?
			header('Location: '.$_SERVER['REQUEST_URI']);
			$output = '';
		}

		$this->trigger('output', $output);
	}

	public function query($query = [])
	{
		if (method_exists($query, '__toArray'))
		{
			$query = $query->__toArray();
		}

		$url = $this->request;

		if ($query)
		{
			$url .= '?'.http_build_query($query);
		}

		return $url;
	}

	public function __toString()
	{
		return ($this->https ? 'https' : 'http').'://'.$this->host;
	}
}
