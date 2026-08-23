<?php
/**
 * NeoFrag — wizard d'installation (standalone, hors framework).
 *
 * Inclus très tôt par index.php racine tant que install/db.txt est absent. Tourne
 * AVANT le boot du CMS : aucune dépendance au service locator, juste mysqli + FS,
 * via install/lib/installer.php.
 *
 * Garde « déjà installé » (double verrou) : sonde DB d'abord (robuste même si
 * db.txt a été perdu), fichier db.txt en complément. Si une install existe déjà,
 * on pose le verrou et on rend la main au boot — jamais de réinstallation.
 */

require_once __DIR__ . '/lib/installer.php';

use NF\Install\Lib\Installer;

$NF_ROOT   = dirname(__DIR__);
$NF_CONFIG = $NF_ROOT . '/config';
$NF_LOCK   = __DIR__ . '/db.txt';

// NEOFRAG_VERSION est défini par le root index.php au boot du CMS, mais l'installeur est servi
// EN DIRECT sur /install/ (hors de ce boot) → layout.php fatale sur la constante absente. On la
// lit donc nous-mêmes depuis le root index.php (même source que tools/build-release.php).
if (!defined('NEOFRAG_VERSION')) {
	$nf_index = (string) @file_get_contents($NF_ROOT . '/index.php');
	define('NEOFRAG_VERSION', preg_match("/NEOFRAG_VERSION',\\s*'([^']+)'/", $nf_index, $nf_m) ? $nf_m[1] : '1.0.0');
}

// --- Garde « déjà installé » ------------------------------------------------
if (Installer::is_already_installed($NF_CONFIG)) {
	if (!is_file($NF_LOCK)) {
		@file_put_contents($NF_LOCK, gmdate('c') . "\n");
	}
	return; // rend la main à index.php racine → boot normal du CMS
}

// --- Petit framework de wizard ----------------------------------------------
session_start();

if (empty($_SESSION['nf_install_csrf'])) {
	$_SESSION['nf_install_csrf'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['nf_install_csrf'];

// Modèle « tout bundlé » : l'install massive (tous les modules/widgets/thèmes locaux) se fait dans
// l'étape « Base de données » via Installer::install_complete() — il n'y a plus d'étape « Modules ».
const NF_STEPS = [
	'requirements' => 'Prérequis',
	'database'     => 'Base de données',
	'admin'        => 'Administrateur',
	'finish'       => 'Terminé',
];

function nf_e(?string $s): string
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function nf_redirect(string $step): never
{
	header('Location: ?step=' . $step);
	exit;
}

/** Détermine l'étape courante selon l'état (config présente ? ) + le paramètre. */
function nf_current_step(string $config_dir): string
{
	$step       = $_GET['step'] ?? '';
	$has_config = Installer::read_db_config($config_dir) !== null;

	if (!isset(NF_STEPS[$step])) {
		return $has_config ? 'admin' : 'requirements';
	}
	// Garde-fou de cohérence : pas d'admin sans config DB (schéma + modules importés).
	if ($step === 'admin' && !$has_config) {
		return 'database';
	}

	return $step;
}

$errors = [];
$old    = $_POST;
$done   = false; // passe à true après création de l'admin → écran final
$step   = nf_current_step($NF_CONFIG);

// --- Traitement des soumissions ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
		$errors[] = 'Session expirée, merci de recommencer l\'étape.';
	} elseif ($step === 'database') {
		$errors = nf_handle_database($NF_CONFIG, $NF_ROOT);
		if (!$errors) {
			nf_redirect('admin');
		}
	} elseif ($step === 'admin') {
		$errors = nf_handle_admin($NF_CONFIG, $NF_LOCK);
		if (!$errors) {
			$done = true;
			$step = 'finish';
		}
	}
}

/**
 * Étape « base de données » : teste la connexion, écrit la config, crée la base
 * au besoin, importe schéma + seed + migrations, puis installe TOUS les modules/widgets/thèmes
 * locaux (Installer::install_complete, modèle « tout bundlé »). Renvoie la liste d'erreurs (vide = OK).
 */
function nf_handle_database(string $config_dir, string $root): array
{
	$cfg = [
		'hostname' => trim($_POST['hostname'] ?? ''),
		'port'     => (int) ($_POST['port'] ?? 3306),
		'username' => trim($_POST['username'] ?? ''),
		'password' => (string) ($_POST['password'] ?? ''),
		'database' => trim($_POST['database'] ?? ''),
	];

	if ($cfg['hostname'] === '' || $cfg['username'] === '' || $cfg['database'] === '') {
		return ['Hôte, utilisateur et nom de la base sont obligatoires.'];
	}

	$test = Installer::test_db($cfg);
	if (!$test['ok']) {
		return ['Connexion impossible : ' . $test['error']];
	}

	try {
		$server = Installer::connect($cfg, false);
		Installer::ensure_database($server, $cfg['database']);

		if (Installer::table_has_rows($server, 'nf_user')) {
			return ['La base « ' . $cfg['database'] . ' » contient déjà une installation NeoFrag. Choisissez une base vierge.'];
		}

		Installer::write_config($config_dir, $cfg);
		Installer::write_site_url($config_dir, Installer::request_origin($_SERVER));

		$db = Installer::connect($cfg);
		Installer::import_sql_file($db, $root . '/install/schema.sql');
		Installer::import_sql_file($db, $root . '/install/seed.sql');
		Installer::run_migrations($db, $root . '/migrations', null);

		// Modèle « tout bundlé » : installe TOUS les modules/widgets/thèmes locaux (aucun choix, aucun
		// marketplace requis). Le résumé est gardé en session pour l'écran final.
		$summary = Installer::install_complete($db, $root);
		$_SESSION['nf_install_summary'] = [
			'modules'  => $summary['installed_modules'],
			'widgets'  => $summary['installed_widgets'],
			'themes'   => $summary['installed_themes'],
			'homepage' => $summary['default_page'],
			'errors'   => $summary['errors'],
		];

		$db->close();
	} catch (\Throwable $e) {
		return ['Échec de l\'initialisation de la base : ' . $e->getMessage()];
	}

	return [];
}

/** Étape « administrateur » : crée le super-admin, applique le nom du site, pose le verrou. */
function nf_handle_admin(string $config_dir, string $lock): array
{
	$site     = trim($_POST['site_name'] ?? '');
	$username = trim($_POST['username'] ?? '');
	$email    = trim($_POST['email'] ?? '');
	$pass     = (string) ($_POST['password'] ?? '');
	$pass2    = (string) ($_POST['password2'] ?? '');
	$wm       = (string) ($_POST['webmaster_password'] ?? '');
	$wm2      = (string) ($_POST['webmaster_password2'] ?? '');

	$errors = [];
	if ($site === '') {
		$errors[] = 'Le nom du site est obligatoire.';
	}
	if ($username === '') {
		$errors[] = 'Le pseudo administrateur est obligatoire.';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Adresse email invalide.';
	}
	if (strlen($pass) < 8) {
		$errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
	}
	if ($pass !== $pass2) {
		$errors[] = 'Les deux mots de passe ne correspondent pas.';
	}
	// Mot de passe webmaster : facultatif ici (peut être défini plus tard depuis Monitoring), mais
	// s'il est fourni il doit être valide et confirmé.
	if ($wm !== '' && strlen($wm) < 8) {
		$errors[] = 'Le mot de passe webmaster doit faire au moins 8 caractères.';
	}
	if ($wm !== '' && $wm !== $wm2) {
		$errors[] = 'Les deux mots de passe webmaster ne correspondent pas.';
	}
	if ($errors) {
		return $errors;
	}

	$cfg = Installer::read_db_config($config_dir);
	if ($cfg === null) {
		return ['Configuration de base de données introuvable, reprenez l\'étape précédente.'];
	}

	try {
		$db = Installer::connect($cfg);
		Installer::create_admin($db, ['username' => $username, 'email' => $email, 'password' => $pass]);
		Installer::set_setting($db, 'nf_name', $site);

		if ($wm !== '') {
			Installer::set_webmaster_password($config_dir, $wm);
		}

		// Email « De » par défaut sur le DOMAINE d'installation (sinon le défaut noreply@neofrag.com
		// est rejeté par le MTA : un serveur n'envoie pas « au nom de » un domaine qu'il ne possède pas).
		$host = preg_replace(['/:\d+$/', '/^www\./'], '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
		if ($host !== '' && strpos($host, '.') !== false && !str_contains($host, 'localhost')) {
			Installer::set_setting($db, 'nf_contact', 'noreply@' . $host);
		}

		// Contenu du wiki (documentation). En « tout bundlé », le module wiki est installé (install_complete)
		// donc nf_wiki_pages existe et la doc s'importe. La garde table_exists reste un filet (ex. un paquet
		// démo qui n'embarquerait pas le module wiki).
		if (is_file($wiki_sql = __DIR__ . '/wiki.sql') && Installer::table_exists($db, 'nf_wiki_pages')) {
			Installer::import_sql_file($db, $wiki_sql);
		}

		// Mise en page de la VITRINE (paquet PRINCIPAL uniquement) : dispositions + thème vitrine actif.
		// Le code du thème/widget landing et la doc (wiki.sql) sont bundlés à part ; ce fichier ne porte
		// que la mise en page, perdue à la réinstallation. Best-effort : un échec ne bloque pas l'install
		// (le site reste fonctionnel sur nebula). Appliqué APRÈS le seed (override nf_default_theme).
		if (is_file($vitrine_sql = __DIR__ . '/vitrine.sql')) {
			try {
				Installer::import_sql_file($db, $vitrine_sql);
			} catch (\Throwable $e) {
				error_log('[install] vitrine.sql ignoré : ' . $e->getMessage());
			}
		}

		// Paquet DÉMO : best-effort. demo.sql (dump cœur-riche) référence des tables de modules ; en
		// « tout bundlé » elles existent toutes, mais on garde le try/catch (un échec ne bloque pas l'install).
		if (is_file($demo_sql = __DIR__ . '/demo.sql')) {
			try {
				Installer::import_sql_file($db, $demo_sql);
			} catch (\Throwable $e) {
				error_log('[install] demo.sql ignoré : ' . $e->getMessage());
			}
		}

		$db->close();
	} catch (\Throwable $e) {
		return ['Création du compte impossible : ' . $e->getMessage()];
	}

	@file_put_contents($lock, gmdate('c') . "\n");
	unset($_SESSION['nf_install_csrf']);

	return [];
}

// --- Rendu -------------------------------------------------------------------
require __DIR__ . '/layout.php';
exit;
