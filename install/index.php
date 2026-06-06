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

// L'étape « Modules » (choix du profil + presets) vient AVANT « Administrateur » : le verrou
// « déjà installé » (Installer::is_already_installed) sonde la présence d'un membre dans nf_user ;
// dès que l'admin est créé, le wizard rendrait la main au framework. On applique donc le preset
// tant qu'aucun compte n'existe encore. (Spec : « étape Modules » — positionnée avant l'admin pour
// cette contrainte de verrou.)
const NF_STEPS = [
	'requirements' => 'Prérequis',
	'database'     => 'Base de données',
	'modules'      => 'Modules',
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
		return $has_config ? 'modules' : 'requirements';
	}
	// Garde-fous de cohérence : pas de modules/admin sans config DB (schéma importé).
	if (in_array($step, ['modules', 'admin'], true) && !$has_config) {
		return 'database';
	}

	return $step;
}

$errors = [];
$old    = $_POST;
$done   = false; // passe à true après création de l'admin → écran final
$step   = nf_current_step($NF_CONFIG);

// Catalogue marketplace (Tier 2 distant) — récupéré pour l'étape Modules (rendu + install).
// NULL si le domaine est injoignable → MODE DÉGRADÉ : presets locaux seuls, l'install ne bloque jamais.
$nf_catalog = ($step === 'modules') ? Installer::fetch_catalog(Installer::marketplace_url()) : null;

// --- Traitement des soumissions ---------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
		$errors[] = 'Session expirée, merci de recommencer l\'étape.';
	} elseif ($step === 'database') {
		$errors = nf_handle_database($NF_CONFIG, $NF_ROOT);
		if (!$errors) {
			nf_redirect('modules');
		}
	} elseif ($step === 'modules') {
		$errors = nf_handle_modules($NF_CONFIG, $NF_ROOT, $nf_catalog);
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
 * Étape « modules » : applique le preset choisi (active des modules Tier 1 locaux + leurs widgets,
 * joue leur install.sql, fixe la page d'accueil, crée la page « Bienvenue » pour « Site simple »),
 * puis TÉLÉCHARGE+INSTALLE les addons Tier 2 cochés depuis le marketplace distant (intégrité SHA-256,
 * anti-zip-slip — cf. Installer::install_remote_addon). Tourne tant qu'aucun admin n'existe (cf.
 * NF_STEPS). Le récap est gardé en session pour l'écran final.
 */
function nf_handle_modules(string $config_dir, string $root, ?array $catalog): array
{
	$cfg = Installer::read_db_config($config_dir);
	if ($cfg === null) {
		return ['Configuration de base de données introuvable, reprenez l\'étape précédente.'];
	}

	$presets = Installer::presets();
	$preset  = (string) ($_POST['preset'] ?? '');
	if (!isset($presets[$preset])) {
		$preset = (string) array_key_first($presets);
	}

	// Modules cochés (cases décochables). Absent = aucun module Tier 1 retenu (cœur seul).
	$selected = (isset($_POST['modules']) && is_array($_POST['modules'])) ? array_values($_POST['modules']) : [];

	try {
		$db      = Installer::connect($cfg);
		$summary = Installer::apply_preset($db, $root, $preset, $selected);

		// Tier 2 distant : le client n'envoie que des NOMS ; on résout les métadonnées (sha256, file…)
		// depuis le catalogue SERVEUR (jamais celles du client) avant tout téléchargement.
		$tier2_names = (isset($_POST['tier2']) && is_array($_POST['tier2'])) ? array_values($_POST['tier2']) : [];
		$installed_t2 = [];
		$skipped_t2   = [];

		if ($tier2_names && is_array($catalog)) {
			$by_name = $widget_metas = [];
			foreach ($catalog['addons'] as $a) {
				if (($a['tier'] ?? null) == 2 && in_array($a['type'] ?? '', ['module', 'theme'], true)) {
					$by_name[$a['name']] = $a;
				}
				if (($a['type'] ?? '') === 'widget') {
					$widget_metas[$a['name']] = $a; // pour télécharger les fichiers des widgets appariés
				}
			}
			$base = Installer::marketplace_url($db);
			foreach ($tier2_names as $name) {
				if (!isset($by_name[$name])) {
					continue; // nom non présent dans le catalogue serveur → ignoré
				}
				$r = Installer::install_remote_addon($db, $root, $by_name[$name], $base, $widget_metas);
				if ($r['ok']) {
					$installed_t2[] = $name;
				} else {
					$skipped_t2[] = $name . ' (' . $r['error'] . ')';
				}
			}
		}

		$db->close();
	} catch (\Throwable $e) {
		return ['Échec de la configuration des modules : ' . $e->getMessage()];
	}

	$_SESSION['nf_install_summary'] = [
		'preset'      => $presets[$preset]['title'],
		'modules'     => $summary['installed_modules'],
		'homepage'    => $summary['default_page'],
		'errors'      => $summary['errors'],
		'tier2'       => $installed_t2,
		'tier2_skipped' => $skipped_t2,
	];

	return [];
}

/**
 * Étape « base de données » : teste la connexion, écrit la config, crée la base
 * au besoin, importe schéma + seed + migrations. Renvoie la liste d'erreurs (vide = OK).
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

		$db = Installer::connect($cfg);
		Installer::import_sql_file($db, $root . '/install/schema.sql');
		Installer::import_sql_file($db, $root . '/install/seed.sql');
		Installer::run_migrations($db, $root . '/migrations', null);
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

		// Contenu du wiki (documentation) — UNIQUEMENT si le module wiki est installé (ses tables
		// existent). En cœur lean, wiki est un addon Tier 2 (marketplace) : aucun preset ne l'installe,
		// donc nf_wiki_pages est absent et wiki.sql ferait échouer toute l'install. La doc wiki sera
		// réintégrée à l'install du module wiki (Phase 2 marketplace).
		if (is_file($wiki_sql = __DIR__ . '/wiki.sql') && Installer::table_exists($db, 'nf_wiki_pages')) {
			Installer::import_sql_file($db, $wiki_sql);
		}

		// Paquet DÉMO : best-effort. demo.sql (dump cœur-riche) référence des tables de modules qui
		// peuvent ne pas exister selon le preset choisi → un échec ne doit pas bloquer l'install.
		// (Un demo.sql aligné sur un preset = chantier Phase 2 démo.)
		if (is_file($demo_sql = __DIR__ . '/demo.sql')) {
			try {
				Installer::import_sql_file($db, $demo_sql);
			} catch (\Throwable $e) {
				error_log('[install] demo.sql ignoré (cœur lean — voir Phase 2 démo) : ' . $e->getMessage());
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
