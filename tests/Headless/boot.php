<?php
declare(strict_types=1);

/**
 * Boot HEADLESS du framework NeoFrag pour les tests d'OBJETS (instancier de vrais modèles
 * et appeler leurs méthodes « données » contre la DB de test). Réplique le prologue MINIMAL
 * de index.php (constantes, NeoFrag()/class_name()/check_file(), helpers, autoloader, __path)
 * MAIS :
 *   - ne câble QUE les services « données » : db puis config (config lit nf_settings via db) ;
 *     pas de url/output/session/access/groups/events (dépendances HTTP / routing) ;
 *   - n'exécute JAMAIS le tail HTTP de index.php (callback CSP ob_start + NeoFrag()->output()
 *     qui route et fait exit).
 *
 * ⚠ Réservé aux méthodes DATA (calcul + DB). Un modèle touchant $this->url/session/output
 * doit fournir un stub ciblé (cas par cas) ou rester en miroir SQL (IntegrationTestCase).
 *
 * Source de vérité du prologue : index.php (7-213). Garder en phase si le boot évolue.
 */

if (!defined('NEOFRAG_HEADLESS'))
{
	define('NEOFRAG_HEADLESS', TRUE);

	chdir(dirname(__DIR__, 2)); // repo root : le framework résout ses fichiers en chemins relatifs

	define('NEOFRAG_MEMORY',  memory_get_usage());
	define('NEOFRAG_TIME',    microtime(TRUE));
	define('NEOFRAG_CMS',     getcwd());
	define('NEOFRAG_VERSION', '1.0.0');

	// $_SERVER minimal au cas où un service data le lirait indirectement.
	$_SERVER += [
		'REQUEST_URI'    => '/',
		'HTTP_HOST'      => 'localhost',
		'SCRIPT_NAME'    => '/index.php',
		'REQUEST_METHOD' => 'GET',
		'REMOTE_ADDR'    => '127.0.0.1',
		'HTTPS'          => 'off',
		'QUERY_STRING'   => '',
	];

	require_once 'vendor/autoload.php';
	require_once 'config/neofrag.php'; // NEOFRAG_DEBUG_BAR / SAFE_MODE / LOGS…

	// Fonctions globales définies dans index.php (40-125) — répliquées (index.php n'est pas chargé).
	if (!function_exists('class_name'))
	{
		function class_name($name)
		{
			$name = explode('\\', $name);
			array_walk($name, function (&$a) {
				if (in_array(strtolower($a), ['array', 'bool', 'default', 'float', 'int', 'list', 'null', 'print']))
				{
					$a .= '_';
				}
			});
			return implode('\\', $name);
		}
	}

	if (!function_exists('NeoFrag'))
	{
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

				$object = $class->newInstanceArgs(array_shift($args) ?: []);

				if (!$NeoFrag)
				{
					$NeoFrag = $object;
				}

				return $object;
			}

			return $NeoFrag;
		}
	}

	if (!function_exists('check_file'))
	{
		function check_file($dir, $force = FALSE)
		{
			if ($dir === '')
			{
				return FALSE;
			}

			static $cache;

			if (!isset($cache[$dir]) || $force)
			{
				$dirs   = explode('/', $dir);
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
	}

	// Helpers framework (index.php:127-150).
	foreach ([
				'array', 'assets', 'color', 'consentement', 'countries', 'debug', 'file', 'dir', 'erreurs',
				'input', 'location', 'markdown', 'notify', 'relais', 'remote', 'sanitize', 'statistics', 'string',
				'system', 'theme', 'time', 'user_agent'
			] as $helper)
	{
		require_once 'neofrag/helpers/'.$helper.'.php';
	}

	// Autoloader NF\* (index.php:152-166).
	spl_autoload_register(function ($name) {
		$namespace = explode('\\', $name);
		if (array_shift($namespace) == 'NF' && $namespace)
		{
			array_walk($namespace, function (&$a) {
				$a = strtolower(rtrim($a, '_'));
			});
			if (file_exists($file = implode('/', $namespace).'.php'))
			{
				require_once $file;
			}
		}
	});

	// Core + stratégie de chemins (index.php:168-194). property_exists($caller,'output') est FALSE
	// en headless (output non câblé) → les overrides de thème sont ignorés, chemins de base utilisés.
	NeoFrag('NF\NeoFrag\NeoFrag')->__path(function ($caller, $type, $file) {
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

	// Câblage du cœur DATA + ACCÈS, dans l'ordre de index.php (input, debug, url, db, access, config) —
	// tous bootent proprement headless (vérifié) — plus `events`, le bus d'événements : pur PHP (écouteurs
	// statiques), aucune dépendance HTTP, et les modèles l'appellent (`NeoFrag()->events->fire(…)` dans
	// `access`) — sans lui, `NeoFrag()->events` valait FALSE et tout `fire()` d'un modèle sous test aurait
	// fatalisé (2026-09-17, en écrivant EventsTest). On s'arrête là : PAS de output/session/groups
	// (rendu/routing/HTTP), et PAS le tail HTTP de index.php (CSP ob_start + output()/exit).
	// Permet d'exercer les modèles « données » + le contrôle d'accès (Access::can), pas le rendu.
	// La base, essayée AVANT le câblage : injoignable, le site affiche sa page « momentanément indisponible »
	// et QUITTE (Core\Db) — PHPUnit partait avec lui, sans résumé et avec un code de sortie nul, 83 tests
	// n'ayant pas tourné (vu sur le poste le 2026-10-05). Une exception, elle, devient des tests sautés
	// (HeadlessTestCase), que `--fail-on-skipped` sait refuser.
	$db = [];
	require 'config/db.php';
	$base = (array) ($db[0] ?? []);
	mysqli_report(MYSQLI_REPORT_OFF);
	$essai = @new \mysqli((string) ($base['hostname'] ?? ''), (string) ($base['username'] ?? ''), (string) ($base['password'] ?? ''), (string) ($base['database'] ?? ''), (int) ($base['port'] ?? 3306));

	if ($essai->connect_errno)
	{
		throw new \RuntimeException('base de données injoignable ('.$essai->connect_error.')');
	}

	$essai->close();
	unset($db, $base, $essai);

	foreach (['input', 'debug', 'url', 'db', 'access', 'config', 'events'] as $core)
	{
		NeoFrag()->{'core_'.$core};
	}

	// Le visiteur. Sur le site, la session pose toujours `NeoFrag()->user` — anonyme, s'il n'est pas
	// connecté (Core\Session) ; ici, sans session, il manquait, et un modèle qui demande « qui
	// regarde ? » (`$this->user()`, le forum en tête) écrivait un avertissement à chaque test, tout en
	// passant (2026-10-03). Un utilisateur sans identifiant est un visiteur : `user()` rend NULL.
	NeoFrag()->user = NeoFrag()->module('user')->model2('user');
}
