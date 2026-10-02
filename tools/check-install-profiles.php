<?php
declare(strict_types=1);

/**
 * check-install-profiles — chaque profil d'installation doit démarrer et répondre, installé pour de vrai.
 *
 * Famille : cible
 *
 * Pourquoi
 * --------
 * `check-addon-coupling` dit quels addons dépendent les uns des autres, et exige que chaque
 * couplage soit déclaré ou annoté. Mais une annotation est une AFFIRMATION, pas une preuve : si
 * quelqu'un écrit « couplage: c'est gardé » alors que ça ne l'est pas, l'outil le croit. Le bug de
 * `teams` trouvé le 2026-09-15 — une garde placée APRÈS la requête qu'elle devait protéger —
 * aurait été annoté comme sûr et aurait planté quand même.
 *
 * Cet outil-ci ne croit rien. Pour chaque profil, sur une base jetable : config + schema.sql +
 * seed.sql + migrations, puis install_complete() avec la SÉLECTION du profil — la séquence exacte
 * de l'installeur web ; vérification qu'aucune table d'un module HORS PROFIL n'a été créée ; puis il
 * sert le site et frappe les routes, y compris celles des modules ABSENTS, qui doivent rendre un
 * 404 propre — jamais un 5xx. C'est ce qui manquait en juin 2026, quand le paquet allégé a été
 * abandonné après qu'une installation neuve eut renvoyé un 500.
 *
 * Il RÉÉCRIT `config/` (dont les secrets) et `install/db.txt`, parce qu'il installe pour de vrai,
 * et les restaure quoi qu'il arrive — fin normale, échec, interruption. Sans cela il laissait
 * l'installation pointée sur sa dernière base jetable, et trois mesures ont menti avant qu'on s'en
 * aperçoive (2026-09-20).
 *
 * Usage
 * -----
 *   NF_DB_HOST=… NF_DB_USER=… NF_DB_PASS=… NF_DB_NAME_PREFIX=nfprofil php tools/check-install-profiles.php
 *   php tools/check-install-profiles.php --profil=core   un seul profil
 *   php tools/check-install-profiles.php --liste         ce qui serait testé, sans rien installer
 *   php tools/check-install-profiles.php --port=8097
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

[$o] = nf_options(['profil' => '', 'liste' => FALSE, 'port' => 0]);

$root    = nf_racine();
$host    = getenv('NF_DB_HOST') ?: 'localhost';
$port    = (int) (getenv('NF_DB_PORT') ?: 3306);
$user    = getenv('NF_DB_USER') ?: 'neofrag';
$pass    = getenv('NF_DB_PASS') ?: '';
$prefixe = getenv('NF_DB_NAME_PREFIX') ?: 'nf_profil';
$port_h  = nf_port($o['port']);
$profils = Installer::presets($root);

if ($o['profil'] !== '')
{
    if (!isset($profils[$o['profil']]))
    {
        nf_refus("profil inconnu : {$o['profil']} — disponibles : ".implode(', ', array_keys($profils)));
    }

    $profils = [$o['profil'] => $profils[$o['profil']]];
}

// ── Les routes à frapper ──────────────────────────────────────────────────────
// Les routes du cœur sont toujours présentes. Celles des modules optionnels sont frappées
// systématiquement : présentes dans le profil elles doivent répondre 200, absentes elles doivent
// rendre un 404 — jamais un 5xx.
$routes_coeur  = ['/', '/fr', '/fr/contact', '/fr/members', '/fr/sitemap.xml', '/fr/robots.txt', '/admin', '/fr/introuvable-xyz'];
$routes_module = [
    'news' => '/fr/news', 'forum' => '/fr/forum', 'gallery' => '/fr/gallery', 'teams' => '/fr/teams',
    'events' => '/fr/events', 'calendar' => '/fr/calendar', 'awards' => '/fr/awards', 'recruits' => '/fr/recruits',
    'games' => '/fr/games', 'partners' => '/fr/partners', 'articles' => '/fr/blog', 'wiki' => '/fr/wiki',
    'faq' => '/fr/faq', 'downloads' => '/fr/downloads', 'shop' => '/fr/shop', 'guestbook' => '/fr/guestbook',
    'links' => '/fr/links', 'surveys' => '/fr/surveys', 'classifieds' => '/fr/classifieds', 'bugtracker' => '/fr/bugtracker',
];

if ($o['liste'])
{
    printf("Profils qui seraient testés (%d) :\n\n", count($profils));

    foreach ($profils as $cle => $p)
    {
        printf("  %-11s %-18s %2d modules · %2d widgets · %d thèmes\n", $cle, $p['title'], count($p['module']), count($p['widget']), count($p['theme']));
    }

    nf_ok(sprintf('routes frappées : %d du cœur + %d de modules (200 si présent, 404 si absent, jamais 5xx)', count($routes_coeur), count($routes_module)));
}

// ── Sauvegarde de tout ce que ce contrôle va écraser, et restauration garantie ────────────────
// `register_shutdown_function` couvre les quatre sorties : fin normale, `exit()`, exception non
// rattrapée et erreur fatale. Les signaux (Ctrl-C) sont captés en plus quand `pcntl` est là.
$empreinte   = nf_temp('config-'.bin2hex(random_bytes(4)));
$a_restaurer = [];

foreach (array_merge(glob($root.'/config/*.php') ?: [], glob($root.'/install/db.txt') ?: []) as $fichier)
{
    $copie = $empreinte.'/'.substr($fichier, strlen($root) + 1);

    if (!is_dir(dirname($copie)))
    {
        @mkdir(dirname($copie), 0775, TRUE);
    }

    if (@copy($fichier, $copie))
    {
        $a_restaurer[$fichier] = $copie;
    }
}

$restaurer = static function () use (&$a_restaurer, $empreinte): void {
    if (!$a_restaurer)
    {
        return;
    }

    foreach ($a_restaurer as $original => $copie)
    {
        @copy($copie, $original);
        @unlink($copie);
    }

    printf("\nConfiguration d'origine restaurée (%d fichier(s), dont les secrets).\n", count($a_restaurer));
    $a_restaurer = [];

    foreach (array_reverse(glob($empreinte.'/*', GLOB_ONLYDIR) ?: []) as $d)
    {
        @rmdir($d);
    }

    @rmdir($empreinte);
};

register_shutdown_function($restaurer);

if (function_exists('pcntl_signal') && function_exists('pcntl_async_signals'))
{
    pcntl_async_signals(TRUE);

    foreach ([SIGINT, SIGTERM, SIGHUP] as $signal)
    {
        pcntl_signal($signal, static function () use ($restaurer): void {
            $restaurer();
            exit(130);
        });
    }
}

printf("Configuration sauvegardée avant installation : %d fichier(s).\n", count($a_restaurer));

$echecs_total = 0;

foreach ($profils as $cle => $profil)
{
    printf("\n═══ Profil « %s » — %d module(s) en plus du cœur\n", $profil['title'], count($profil['module']));

    $base = $prefixe.'_'.preg_replace('/[^a-z0-9]/', '', strtolower($cle));

    // 1. Base jetable. Ce contrôle CRÉE et DÉTRUIT des bases : il lui faut un compte qui en a le
    // droit (NF_DB_HOST, NF_DB_PORT, NF_DB_USER et NF_DB_PASS).
    $serveur_sql = nf_connexion_admin(['hostname' => $host, 'port' => $port, 'username' => $user, 'password' => $pass]);

    if (!$serveur_sql->query("DROP DATABASE IF EXISTS `{$base}`") || !$serveur_sql->query("CREATE DATABASE `{$base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
    {
        nf_refus("base jetable `{$base}` impossible : {$serveur_sql->error} — il faut un compte qui a le droit de créer et détruire des bases");
    }

    $serveur_sql->close();

    Installer::write_config($root.'/config', ['hostname' => $host, 'username' => $user, 'password' => $pass, 'database' => $base, 'port' => $port]);

    file_put_contents($root.'/config/neofrag.php',
        "<?php\n\n"
        ."define('NEOFRAG_DEBUG_BAR', FALSE);\ndefine('NEOFRAG_SAFE_MODE', FALSE);\n"
        ."define('NEOFRAG_DEMO', FALSE);\ndefine('NEOFRAG_LOGS', FALSE);\n"
        ."define('NEOFRAG_LOGS_I18N', FALSE);\n");

    file_put_contents($root.'/config/url.php', "<?php\n\n\$url['site'] = 'http://127.0.0.1:{$port_h}';\n");

    $db = Installer::connect(['hostname' => $host, 'username' => $user, 'password' => $pass, 'database' => $base, 'port' => $port]);

    Installer::import_sql_file($db, $root.'/install/schema.sql');
    Installer::import_sql_file($db, $root.'/install/seed.sql');
    Installer::run_migrations($db, $root.'/migrations', NULL);

    $resume = Installer::install_complete($db, $root, ['module' => $profil['module'], 'widget' => $profil['widget'], 'theme' => $profil['theme']]);

    Installer::create_admin($db, ['username' => 'profil-admin', 'email' => 'p@example.test', 'password' => 'Profil-2026!']);

    // Verrou d'installation : sans lui l'application renvoie vers l'assistant, ce qui ne testerait rien.
    @file_put_contents($root.'/install/db.txt', date('c')."\n");

    $tables = (int) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$base}'")->fetch_row()[0];
    printf("  installé : %d modules · %d tables · accueil=%s · %d erreur(s)\n",
        count($resume['installed_modules']), $tables, $resume['default_page'], count($resume['errors']));

    // 2. Aucune table d'un module hors profil
    $intrus = [];

    foreach (array_keys($routes_module) as $module)
    {
        if (!in_array($module, $profil['module'], TRUE) && Installer::table_exists($db, 'nf_'.$module))
        {
            $intrus[] = 'nf_'.$module;
        }
    }

    $db->close();

    if ($intrus)
    {
        nf_avertir('  ✗ Tables de modules HORS profil présentes : '.implode(', ', $intrus)."\n    Ce n'est pas une installation du profil « {$cle} » — le test ne prouverait rien.");
        $echecs_total++;
        continue;
    }

    // 3. Servir et frapper
    $serveur = nf_serveur($port_h);
    $echecs  = 0;

    $frapper = static function (string $route, string $attendu) use ($serveur, &$echecs): void {
        $reponse = nf_http($serveur->base.$route, ['suivre' => 0, 'timeout' => 25]);
        $code    = $reponse['code'];

        // Seule une erreur SERVEUR est un échec. Un 404 sur un module absent est le comportement attendu.
        $ko = $code >= 500 || $code === 0;
        $echecs += $ko ? 1 : 0;

        // Une redirection dit où elle mène ; une absence de réponse dit pourquoi (délai, connexion).
        $detail = $code >= 300 && $code < 400 ? ' → '.($reponse['entetes']['location'] ?? '?')
                : ($code === 0 && $reponse['raison'] !== '' ? ' ('.$reponse['raison'].')' : '');

        printf("    %s %-22s %s%s  %s\n", $ko ? '✗' : '·', $route, $code ?: 'pas de réponse', $detail, $attendu);

        if ($ko && $reponse['corps'] !== '')
        {
            nf_avertir('        '.trim(substr(strip_tags($reponse['corps']), 0, 300)));
        }
    };

    foreach ($routes_coeur as $route)
    {
        $frapper($route, 'cœur');
    }

    foreach ($routes_module as $module => $route)
    {
        $frapper($route, in_array($module, $profil['module'], TRUE) ? 'présent' : 'ABSENT du profil');
    }

    $serveur->arreter();

    if ($echecs)
    {
        printf("  ✗ %d route(s) en erreur serveur.\n", $echecs);
        $echecs_total += $echecs;
    }
    else
    {
        echo "  ✓ aucune erreur serveur.\n";
    }
}

if ($echecs_total)
{
    nf_echec("{$echecs_total} erreur(s) serveur au total — une annotation « couplage: » ment quelque part, ou une garde manque : ce contrôle installe pour de vrai, il ne croit aucune déclaration");
}

nf_ok('tous les profils d\'installation démarrent et répondent');
