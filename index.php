<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

define('NEOFRAG_MEMORY',  memory_get_usage());
define('NEOFRAG_TIME',    microtime(TRUE));
define('NEOFRAG_CMS',     __DIR__);
define('NEOFRAG_VERSION', '1.2.46');

error_reporting(E_ALL);

// Le journal des erreurs s'écrit dans logs/php.log. Les paquets d'installation ne portaient pas ce
// dossier jusqu'à la 1.2.22, et rien ne le créait : PHP se rabattait sur le journal du serveur, et
// Monitoring → Journal des erreurs restait vide. Un site qui ne l'a pas le recrée, avec sa garde.
if (!is_dir(NEOFRAG_CMS.'/logs') && @mkdir(NEOFRAG_CMS.'/logs', 0775, TRUE))
{
	@file_put_contents(NEOFRAG_CMS.'/logs/.htaccess', "Require all denied\n");
}

// Chemin ABSOLU : un chemin relatif se résout contre le dossier courant au moment d'écrire, et en fin de requête,
// sous PHP-FPM, ce dossier est `/` — les erreurs fatales et celles du filtre de sortie se perdaient (2026-10-08).
ini_set('error_log',       NEOFRAG_CMS.'/logs/php.log');
// display_errors OFF par défaut au bootstrap (avant le chargement de config/neofrag.php) : évite de
// divulguer chemins/structure si une erreur survient tôt. Le core Debug le gère ensuite selon
// NEOFRAG_DEBUG_BAR (la debug bar affiche les erreurs en dev). Les erreurs restent journalisées.
ini_set('display_errors',  FALSE);
ini_set('default_charset', 'UTF-8');

// Tant que l'install n'est pas verrouillée (install/db.txt absent), l'assistant
// d'installation prend la main. Il est autonome (ne boote pas le framework) :
// si une install existe déjà, il pose le verrou et rend la main (return) ; sinon
// il rend le wizard et coupe (exit). Cf. install/index.php.
if (file_exists('install/index.php') && !file_exists('install/db.txt'))
{
	require_once 'install/index.php';
}

mb_regex_encoding('UTF-8');
mb_internal_encoding('UTF-8');

if (file_exists('vendor/autoload.php'))
{
	require_once 'vendor/autoload.php';
}

require_once 'config/neofrag.php';

function class_name($name)
{
	$name = explode('\\', $name);

	array_walk($name, function(&$a){
		if (in_array(strtolower($a), ['array', 'bool', 'default', 'float', 'int', 'list', 'null', 'print']))
		{
			$a .= '_';
		}
	});

	return implode('\\', $name);
}

function NeoFrag()
{
	static $NeoFrag;

	if ($args = func_get_args())
	{
		try
		{
			$class = new ReflectionClass(class_name(array_shift($args)));
		}
		catch (ReflectionException $e)
		{
			return;
		}

		if ($debug = nf_debogage_actif() || nf_trace_active())
		{
			$memory = memory_get_usage();
			$time   = microtime(TRUE);
		}

		$object = $class->newInstanceArgs(array_shift($args) ?: []);

		// `__debug` est une propriété DYNAMIQUE, posée sur chaque objet en mode débogage seulement.
		// Toute classe instanciée ici doit donc porter #[\AllowDynamicProperties] : PHP 8.2 déprécie
		// le reste, et les champs de `neofrag/fields/` écrivaient ainsi plus de deux mille lignes au
		// journal d'un site d'essai pour trois cents pages (2026-09-22).
		if ($debug)
		{
			$object->__debug = (object)[
				'memory' => [$memory, memory_get_usage()],
				'time'   => [$time, microtime(TRUE)]
			];
		}

		if (!$NeoFrag)
		{
			$NeoFrag = $object;
		}

		return $object;
	}
	else
	{
		return $NeoFrag;
	}
}

function check_file($dir, $force = FALSE)
{
	if ($dir === '')
	{
		return FALSE;
	}

	static $cache;

	if (!isset($cache[$dir]) || $force)
	{
		$dirs = explode('/', $dir);

		$exists = TRUE;

		foreach (array_keys($dirs) as $i)
		{
			if (!isset($cache[$path = implode('/', array_slice($dirs, 0, $i + 1))]) || $force)
			{
				$cache[$path] = $exists ? file_exists($path) : FALSE;
			}

			$exists = $cache[$path];
		}
	}

	return $cache[$dir];
}

foreach ([
			'array',
			'assets',
			'bootstrap',
			'color',
			'consentement',
			'countries',
			'debug',
			'file',
			'dir',
			'erreurs',
			'input',
			'location',
			'markdown',
			'notify',
			'fonts',
			'remote',
			'relais',
			'sanitize',
			'statistics',
			'seo',
			'string',
			'system',
			'theme',
			'time',
			'user_agent'
		] as $helper
	)
{
	require_once 'neofrag/helpers/'.$helper.'.php';
}

// Plus de page blanche : une exception que rien n'a rattrapée, ou une erreur fatale, affiche une page
// d'erreur avec sa référence, celle que porte le journal (cf. neofrag/helpers/erreurs.php).
nf_filet_erreurs();

spl_autoload_register(function($name){
	$namespace = explode('\\', $name);

	if (array_shift($namespace) == 'NF' && $namespace)
	{
		array_walk($namespace, function(&$a){
			$a = strtolower(rtrim($a, '_'));
		});

		if (file_exists($file = implode('/', $namespace).'.php'))
		{
			require_once $file;
		}
	}
});

NeoFrag('NF\NeoFrag\NeoFrag')->__path(function($caller, $type, $file){
	$file = [$file];

	if (!in_array($type, ['addons', 'assets']))
	{
		if ($type)
		{
			array_unshift($file, $type);
		}

		array_unshift($file, 'neofrag');
	}

	$file = implode('/', $file);

	if (!NEOFRAG_SAFE_MODE)
	{
		yield 'overrides/'.$file;

		if (property_exists($caller, 'output') && ($theme = $caller->output->theme()))
		{
			yield 'themes/'.$theme->info()->name.'/overrides/'.$file;
		}
	}

	yield $file;
});

foreach ([
			'input',
			'debug',
			'url',
			'db',
			'access',
			'config',
			'output',
			'session',
			'groups',
			'events'
		] as $core
	)
{
	NeoFrag()->{'core_'.$core};
}

define('NEOFRAG_CORE', TRUE);

/*
 * La base suit le code. Un code neuf — posé par le bouton de mise à jour ou par FTP — applique ici,
 * UNE fois, les migrations qu'il apporte : jusqu'au 2026-10-01, seule l'installation le faisait. En
 * temps normal, cela coûte une comparaison avec un réglage déjà en mémoire. Un échec ne casse pas la
 * page : il est journalisé, et la tentative suivante attend dix minutes.
 *
 * Le réglage est `nf_migrations_version`, et non `nf_schema_version` qu'employait la 1.2.3 : le code
 * de mise à jour d'une 1.2.3 marque `nf_schema_version` à la version cible sans appliquer les
 * migrations des modules, et le rattrapage se serait cru à jour.
 */
if (($schema = (string) NeoFrag()->config->nf_migrations_version) !== NEOFRAG_VERSION
	&& !(str_starts_with($schema, 'echec:') && time() - (int) substr($schema, 6) < 600)
	&& is_file(NEOFRAG_CMS.'/neofrag/installer.php'))
{
	try
	{
		if (nf_migrations_du_code(NEOFRAG_CMS) !== NULL)
		{
			NeoFrag()->config('nf_migrations_version', NEOFRAG_VERSION);
		}
	}
	catch (\Throwable $e)
	{
		NeoFrag()->config('nf_migrations_version', 'echec:'.time());
		error_log('[migrations] '.$e->getMessage());
	}
}

// CSP stricte : un nonce par requête, injecté sur TOUS les <script> du HTML final + dans l'en-tête CSP
// (servie ici, plus dans .htaccess). Permet de retirer 'unsafe-inline' du script-src sans noncer chaque
// template à la main, et couvre aussi le JS inline généré dynamiquement (qui lit window.__nfNonce).
$GLOBALS['nf_csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

ob_start(function($html){
	$nonce = $GLOBALS['nf_csp_nonce'];

	// Ce filtre tourne à la fin de la requête : sous PHP-FPM (et Apache), le dossier courant y est déjà `/`, et tout ce
	// que le framework charge par un chemin relatif (config/crypt.php, une bibliothèque, un fichier de langue) échoue.
	// Le filtre des services tiers en est mort en production le 2026-10-08 — la clé du relais introuvable, la page
	// partait sans être filtrée — alors que le serveur intégré de PHP, qui garde le dossier, ne le montrait pas.
	if (defined('NEOFRAG_CMS') && getcwd() !== NEOFRAG_CMS)
	{
		@chdir(NEOFRAG_CMS);
	}

	// Uniquement les réponses HTML : les réponses JSON (modales AJAX) peuvent contenir « <script »
	// dans leur champ `content` — il ne faut SURTOUT pas y injecter de nonce (ça casserait le JSON).
	$content_type = '';
	foreach (headers_list() as $h)
	{
		if (stripos($h, 'content-type:') === 0)
		{
			$content_type = strtolower($h);
			break;
		}
	}
	$is_html = strpos($content_type, 'text/html') !== FALSE;

	// Réponses DYNAMIQUES (HTML/JSON) : jamais mises en cache par le navigateur. Sans en-tête, le
	// navigateur applique un cache heuristique et sert du périmé (ex. thème changé côté admin qui
	// n'apparaît qu'au Ctrl+F5). Les assets statiques (CSS/JS/images) sont servis par le serveur web,
	// PAS par index.php → non concernés, ils gardent leur cache (cache-bust par mtime).
	$is_json = strpos($content_type, 'application/json') !== FALSE;
	if (($is_html || $is_json) && !headers_sent())
	{
		header('Cache-Control: no-store, max-age=0');
	}

	// Les services tiers, pour toutes les pages, tous les fragments et toutes les réponses JSON, quel que soit
	// le module qui les a écrits : un cadre (une vidéo YouTube dans un article, le widget Discord) que le
	// visiteur n'a pas accepté devient son avis, qui le garde sans le charger ; une image d'un autre site est
	// servie par le site lui-même, et le navigateur n'a plus à la demander ailleurs (helpers/consentement.php,
	// helpers/relais.php). Un échec ici laisse la réponse telle quelle plutôt que de la perdre.
	if ($is_html || $is_json)
	{
		try {
			$html = $is_html ? nf_tiers_page($html) : nf_tiers_json($html);
		} catch (\Throwable $e) {
			error_log('[tiers] filtre des services tiers : '.$e->getMessage());
		}
	}

	if (!$is_html || stripos($html, '<script') === FALSE)
	{
		return $html;
	}

	if (!headers_sent())
	{
		// script-src : plus de `https:` générique (n'importe quelle origine https). Allowlist précise —
		// 'self' couvre tout le JS NeoFrag + TinyMCE/CodeMirror/ALTCHA auto-hébergés.
		// style-src garde 'unsafe-inline' (styles inline BS5/TinyMCE). Plus aucune origine de polices : depuis le
		// 2026-10-08, le site sert ses polices lui-même (tools/polices-locales.php) — `font-src 'self' data:`, et
		// un thème ou un addon qui rappellerait Google serait refusé par le navigateur. img-src n'a plus que le
		// site non plus : depuis le 2026-10-08, toute image d'un autre site est servie par le site lui-même
		// (helpers/relais.php), les tuiles de la carte des lieux comprises ; seul Analytics y ajoute les siennes.
		// connect-src, de même : plus aucun script du produit n'envoie quoi que ce soit ailleurs (le drapeau des
		// adresses IP, qui les envoyait à neofr.ag, est retiré) ; seul Analytics ajoute les adresses de Google.
		// googletagmanager.com n'entre dans l'allowlist QUE si un identifiant Analytics est configuré :
		// un site sans Analytics ne déclare aucune origine tierce de plus. Sans cette ligne, le chargeur de
		// Google était refusé par la politique — Analytics n'a jamais pu fonctionner sous la CSP stricte.
		$analytics        = '';
		$analytics_envois = '';
		$images           = '';
		try {
			if ((string) NeoFrag()->config->nf_analytics !== '')
			{
				$analytics        = ' https://www.googletagmanager.com';
				$analytics_envois = ' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com';
				$images           = ' https://*.google-analytics.com https://*.googletagmanager.com';
			}
		} catch (\Throwable $e) {}

		// media-src : la directive n'existait pas et héritait donc de `default-src 'self'`. Tant que
		// tout l'audio et toute la vidéo venaient de `upload/`, cela suffisait — un flux de webradio,
		// lui, est par nature distant et aurait été bloqué sans que rien ne l'explique à l'écran.
		//
		// On la déclare explicitement, et on n'y ajoute l'origine du flux QUE si un flux est
		// configuré : un site sans webradio ne déclare aucune origine tierce de plus. Même traitement
		// que googletagmanager.com juste au-dessus.
		//
		// `webradio_origin` est écrite par le module, réduite au schéma, à l'hôte et au port par
		// `Schedule::origine()`. On revérifie sa forme ici : l'espace sépare les sources dans cet
		// en-tête, et un réglage peut avoir été posé autrement que par le formulaire.
		$media = '';
		try {
			$origine = (string) NeoFrag()->config->webradio_origin;
			$media   = preg_match('#^https?://[a-z0-9.-]+(?::\d{1,5})?$#i', $origine) ? ' '.$origine : '';
		} catch (\Throwable $e) {}

		// Le captcha : seules les origines du fournisseur ACTIF. Google était ouvert à tout
		// site, qu'il ait un captcha ou non ; ALTCHA, le fournisseur par défaut, n'en demande aucune.
		$captcha = ['script' => '', 'frame' => '', 'style' => ''];
		try {
			$config = NeoFrag()->config;
			$actif  = \NF\NeoFrag\Libraries\Captcha::cle_active((string) $config->nf_captcha_provider, (string) $config->nf_captcha_public_key, (string) $config->nf_captcha_private_key);

			foreach (\NF\NeoFrag\Libraries\Captcha::csp($actif) as $directive => $origines)
			{
				$captcha[$directive] = ' '.implode(' ', $origines);
			}
		} catch (\Throwable $e) {}

		// img-src `blob:` : l'aperçu d'une image collée ou glissée dans l'éditeur riche, le temps de son
		// envoi. TinyMCE l'affiche sous une adresse `blob:` avant de la remplacer par l'adresse rendue par
		// le site (cf. Editeur_Images) ; refusée, l'image s'affichait cassée (2026-10-04). Le risque est
		// nul : une adresse `blob:` ne peut être fabriquée que par un script de la page elle-même, et
		// `img-src` ne règle que l'affichage d'images — rien ne s'y exécute.
		//
		// frame-src : les lecteurs des services que le produit connaît (YouTube, Twitch, Discord…,
		// nf_consentement_cadres()). Ils n'y étaient pas : la vidéo d'un article, le widget Discord en cadre
		// et le lecteur du widget Twitch étaient refusés par le navigateur. Ce n'est pas la politique qui
		// décide s'ils se chargent, c'est le visiteur : tant qu'il ne les a pas acceptés, l'avis posé à leur
		// place les garde sans les charger (le filtre plus haut).
		header("Content-Security-Policy: default-src 'self'; object-src 'none'; ".
			"script-src 'self' 'nonce-$nonce'{$captcha['script']}$analytics; ".
			"style-src 'self' 'unsafe-inline'{$captcha['style']}; ".
			"img-src 'self' data: blob:$images; font-src 'self' data:; connect-src 'self'$analytics_envois; ".
			"media-src 'self' data:$media; ".
			"frame-src 'self' ".nf_consentement_cadres()."{$captcha['frame']}; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
	}

	// Le nonce va aux scripts du site, écrits dans la page ou servis par lui ; jamais à un script d'un autre site
	// (`<script src="https://…">`), qui ne s'exécute donc que si son origine est dans script-src — Analytics,
	// le captcha. Avant le 2026-10-08, chaque <script> recevait le nonce : un script tiers collé dans un widget
	// « Code HTML » ou une publicité s'exécutait quelle que soit son origine, et la liste ne servait à rien.
	$hote = strtolower((string) parse_url(site_origin(), PHP_URL_HOST));

	return (string) preg_replace_callback('/<script(?=[\s>])(?![^>]*\bnonce=)([^>]*)>/i', function(array $m) use ($nonce, $hote): string {
		if (preg_match('#\bsrc\s*=\s*(["\']?)\s*((?:https?:)?//[^"\'\s>]+)#i', $m[1], $src)
			&& strtolower((string) parse_url(str_starts_with($src[2], '//') ? 'https:'.$src[2] : $src[2], PHP_URL_HOST)) !== $hote)
		{
			return $m[0];
		}

		return '<script nonce="'.$nonce.'"'.$m[1].'>';
	}, $html);
});

NeoFrag()->output();
