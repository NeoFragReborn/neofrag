<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

define('NEOFRAG_MEMORY',  memory_get_usage());
define('NEOFRAG_TIME',    microtime(TRUE));
define('NEOFRAG_CMS',     __DIR__);
define('NEOFRAG_VERSION', '1.1.0');

error_reporting(E_ALL);

ini_set('error_log',       'logs/php.log');
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

		if ($debug = NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
		{
			$memory = memory_get_usage();
			$time   = microtime(TRUE);
		}

		$object = $class->newInstanceArgs(array_shift($args) ?: []);

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
			'color',
			'countries',
			'debug',
			'file',
			'geolocalisation',
			'dir',
			'input',
			'location',
			'markdown',
			'notify',
			'sanitize',
			'statistics',
			'string',
			'system',
			'time',
			'user_agent'
		] as $helper
	)
{
	require_once 'neofrag/helpers/'.$helper.'.php';
}

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

// CSP stricte : un nonce par requête, injecté sur TOUS les <script> du HTML final + dans l'en-tête CSP
// (servie ici, plus dans .htaccess). Permet de retirer 'unsafe-inline' du script-src sans noncer chaque
// template à la main, et couvre aussi le JS inline généré dynamiquement (qui lit window.__nfNonce).
$GLOBALS['nf_csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

ob_start(function($html){
	$nonce = $GLOBALS['nf_csp_nonce'];

	// Uniquement les réponses HTML : les réponses JSON (modales AJAX) peuvent contenir « <script »
	// dans leur champ `content` — il ne faut SURTOUT pas y injecter de nonce (ça casserait le JSON).
	$is_html = FALSE;
	foreach (headers_list() as $h)
	{
		if (stripos($h, 'content-type:') === 0)
		{
			$is_html = stripos($h, 'text/html') !== FALSE;
			break;
		}
	}

	if (!$is_html || stripos($html, '<script') === FALSE)
	{
		return $html;
	}

	if (!headers_sent())
	{
		// script-src : plus de `https:` générique (n'importe quelle origine https). Allowlist précise —
		// 'self' couvre tout le JS NeoFrag + TinyMCE/CodeMirror auto-hébergés ; google/gstatic = reCAPTCHA.
		// style-src garde 'unsafe-inline' (styles inline BS5/TinyMCE) + fonts.googleapis.com (@import des
		// thèmes). img/font/connect gardent `https:` (avatars, fonts gstatic, widgets Steam/Twitch).
		header("Content-Security-Policy: default-src 'self'; object-src 'none'; ".
			"script-src 'self' 'nonce-$nonce' https://www.google.com https://www.gstatic.com; ".
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
			"img-src 'self' data: https:; font-src 'self' data: https:; connect-src 'self' https:; ".
			"frame-src 'self' https://www.google.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
	}

	return preg_replace('/<script(?=[\s>])(?![^>]*\bnonce=)/i', '<script nonce="'.$nonce.'"', $html);
});

NeoFrag()->output();
