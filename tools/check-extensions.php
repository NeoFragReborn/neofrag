<?php
declare(strict_types=1);

/**
 * check-extensions — chaque addon du marketplace s'installe sur un site qui n'a que le cœur, par le marketplace ou par son archive.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `check-marketplace` examine les archives — structure, empreintes, versions — sans en installer
 * aucune. Or c'est l'installation qui compte : un addon dont l'`install.sql` porte une clé étrangère
 * vers la table d'un autre ne s'installe pas sans lui ; un widget qu'aucun module n'emmène n'apparaît
 * pas dans la fenêtre du marketplace. Rien ne le jouait.
 *
 * Celui-ci monte un site JETABLE qui n'a que le cœur (profil « Cœur seul »), puis, en administrateur :
 *   1. installe par la fenêtre du MARKETPLACE tout ce qu'elle propose (modules, thèmes, widgets
 *      autonomes ; un module emmène ses widgets), tour après tour tant qu'elle en propose — un addon
 *      dont il manque une dépendance attend le tour suivant, comme le ferait l'administrateur ;
 *   2. installe par « AJOUTER » (l'envoi de l'archive) ce qui resterait du catalogue ; chaque archive est
 *      d'abord vérifiée contre l'empreinte du catalogue. Jusqu'à la 1.2.24, sept widgets n'étaient
 *      installables que par là : la fenêtre ne proposait pas ceux qu'aucun module n'emmène ;
 *   3. exige que chaque addon du catalogue soit installé, puis frappe les routes des modules (200),
 *      et lit le journal PHP du site : il doit être resté muet.
 * Les archives viennent du marketplace PUBLIÉ (celui du site officiel, la seule origine qu'accepte le
 * produit) : c'est ce que reçoivent les sites.
 *
 * Il RÉÉCRIT `config/` et `install/db.txt` le temps de l'épreuve, et les restaure quoi qu'il arrive ;
 * les addons installés réécrivent leurs dossiers avec le contenu de leur archive. Un site d'essai ou
 * la CI, jamais un site en service. Il lui faut un compte qui peut créer une base : `NF_DB_HOST`,
 * `NF_DB_PORT`, `NF_DB_USER`, `NF_DB_PASS`.
 *
 * Usage
 * -----
 *   NF_DB_HOST=… NF_DB_USER=… NF_DB_PASS=… php tools/check-extensions.php
 *   php tools/check-extensions.php --port=8116
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/profils.php';
require __DIR__.'/lib/journal.php';
require nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

[$o] = nf_options(['port' => 0]);

$root = nf_racine();
$host = getenv('NF_DB_HOST') ?: 'localhost';
$port = (int) (getenv('NF_DB_PORT') ?: 3306);
$user = getenv('NF_DB_USER') ?: 'neofrag';
$pass = getenv('NF_DB_PASS') ?: '';
$base = 'nfextensions_install_test';

// ── Le catalogue publié ───────────────────────────────────────────────────────
$origine   = Installer::sanitize_marketplace_url(NULL);
$catalogue = Installer::fetch_catalog($origine);

if ($catalogue === NULL)
{
    nf_refus("le marketplace publié ne répond pas ({$origine}) : il n'y a rien à installer");
}

$version = preg_match("/NEOFRAG_VERSION',\\s*'([^']+)'/", (string) file_get_contents($root.'/index.php'), $v) ? $v[1] : '?';
printf("Catalogue : %s — %d addons, base %s ; arbre en %s\n", $origine, count($catalogue['addons']), $catalogue['base_version'] ?? '?', $version);

if (($catalogue['base_version'] ?? '') !== $version)
{
    nf_avertir("  (le catalogue publié est celui de la {$catalogue['base_version']}, l'arbre est en {$version} : on éprouve les archives que reçoivent les sites)");
}

// ── La configuration, sauvegardée et restaurée quoi qu'il arrive ──────────────
$empreinte   = nf_temp('config-'.bin2hex(random_bytes(4)));
$a_restaurer = [];

foreach (array_merge(glob($root.'/config/*.php') ?: [], glob($root.'/install/db.txt') ?: []) as $fichier)
{
    $copie = $empreinte.'/'.substr($fichier, strlen($root) + 1);
    @mkdir(dirname($copie), 0775, TRUE);

    if (@copy($fichier, $copie))
    {
        $a_restaurer[$fichier] = $copie;
    }
}

$crees = [];

register_shutdown_function(static function () use (&$a_restaurer, &$crees, $empreinte): void {
    foreach ($crees as $fichier)
    {
        if (!isset($a_restaurer[$fichier]))
        {
            @unlink($fichier);
        }
    }

    foreach ($a_restaurer as $original => $copie)
    {
        @copy($copie, $original);
        @unlink($copie);
    }

    $a_restaurer = [];
});

// ── Un site qui n'a que le cœur ───────────────────────────────────────────────
$serveur_sql = nf_connexion_admin(['hostname' => $host, 'port' => $port, 'username' => $user, 'password' => $pass]);

if (!$serveur_sql->query("DROP DATABASE IF EXISTS `{$base}`") || !$serveur_sql->query("CREATE DATABASE `{$base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
{
    nf_refus("base jetable `{$base}` impossible : {$serveur_sql->error} — il faut un compte qui a le droit de créer et détruire des bases");
}

register_shutdown_function(static function () use ($serveur_sql, $base): void {
    @$serveur_sql->query("DROP DATABASE IF EXISTS `{$base}`");
});

$port_h = nf_port($o['port']);
$cfg    = ['hostname' => $host, 'username' => $user, 'password' => $pass, 'database' => $base, 'port' => $port];

foreach (['db', 'crypt', 'password', 'email', 'neofrag', 'url'] as $nom)
{
    $crees[] = $root.'/config/'.$nom.'.php';
}

$crees[] = $root.'/install/db.txt';

Installer::write_config($root.'/config', $cfg);
file_put_contents($root.'/config/neofrag.php', Installer::config_neofrag());
file_put_contents($root.'/config/url.php', "<?php\n\n\$url['site'] = 'http://127.0.0.1:{$port_h}';\n");

$db = Installer::connect($cfg);
Installer::import_sql_file($db, $root.'/install/schema.sql');
Installer::import_sql_file($db, $root.'/install/seed.sql');
Installer::run_migrations($db, $root.'/migrations', NULL);
Installer::install_complete($db, $root, ['module' => [], 'widget' => [], 'theme' => []]);
Installer::create_admin($db, ['username' => 'extensions-admin', 'email' => 'x@example.test', 'password' => 'Extensions-2026!']);
@file_put_contents($root.'/install/db.txt', date('c')."\n");
$db->close();

$db      = nf_connexion();
$journal = $root.'/logs/php.log';

if (!nf_journal_preparer($journal))
{
    nf_refus("le journal du site n'est pas inscriptible ({$journal}) : le contrôle serait aveugle");
}

$octet   = nf_journal_taille($journal);
$serveur = nf_serveur($port_h, ['NF_OUTIL_SESSION' => nf_session_admin($db), 'NF_OUTIL_CONSENT' => 'essentials']);

/** Les addons installés, `type:nom`. */
$installes = static function () use ($db): array {
    return array_map(static fn (array $l): string => $l['type'].':'.$l['name'],
        $db->query('SELECT a.name, t.name AS type FROM nf_addon a JOIN nf_addon_type t ON t.id = a.type_id')->fetch_all(MYSQLI_ASSOC));
};

$attendus = array_map(static fn (array $a): string => $a['type'].':'.$a['name'], $catalogue['addons']);
$avant    = $installes();
printf("Site « Cœur seul » : %d addon(s) du catalogue déjà là (le cœur), %d à installer\n\n", count(array_intersect($attendus, $avant)), count(array_diff($attendus, $avant)));

// ── 1. Par la fenêtre du marketplace, tour après tour ─────────────────────────
for ($tour = 1; $tour <= 8; $tour++)
{
    $fenetre = nf_balisage(nf_http($serveur->base.'/fr/admin/ajax/addons/marketplace', ['ajax' => TRUE, 'suivre' => 0, 'timeout' => 60])['corps']);

    if (!preg_match_all('#<input\b[^>]*type="checkbox"[^>]*name="([^"]+)"[^>]*value="((?:module|theme|widget):[^"]+)"#i', $fenetre, $cases, PREG_SET_ORDER))
    {
        break;
    }

    $formulaire = nf_formulaire($fenetre, 'type="checkbox"') ?? [];
    $champ      = $cases[0][1];

    foreach (array_keys($formulaire) as $nom)
    {
        if ($nom === $champ)
        {
            unset($formulaire[$nom]);
        }
    }

    $corps = http_build_query($formulaire);

    foreach ($cases as $case)
    {
        $corps .= '&'.rawurlencode($case[1]).'='.rawurlencode($case[2]);
    }

    parse_str($corps, $envoi);
    $compte = count($installes());
    nf_http($serveur->base.'/fr/admin/ajax/addons/marketplace', ['post' => $envoi, 'ajax' => TRUE, 'suivre' => 0, 'timeout' => 600]);
    $nouveaux = count($installes()) - $compte;

    printf("  marketplace, tour %d : %d proposé(s), %d installé(s)\n", $tour, count($cases), $nouveaux);

    if ($nouveaux === 0)
    {
        break;
    }
}

// ── 2. Par « Ajouter », l'archive envoyée, pour ce qui reste ──────────────────
$restants = array_values(array_diff($attendus, $installes()));
$echecs   = [];

if ($restants)
{
    // La fenêtre du marketplace doit tout proposer : ce qu'elle laisse est un défaut, même si « Ajouter »
    // sait l'installer ensuite (ce qu'on éprouve quand même, ci-dessous).
    foreach ($restants as $cle)
    {
        $echecs[] = "{$cle} : la fenêtre du marketplace ne le propose pas";
    }

    printf("\n  « Ajouter » pour ce que la fenêtre du marketplace ne propose pas : %s\n", implode(', ', $restants));
}

foreach ($catalogue['addons'] as $a)
{
    $cle = $a['type'].':'.$a['name'];

    if (!in_array($cle, $restants, TRUE))
    {
        continue;
    }

    $archive = (string) @file_get_contents(rtrim($origine, '/').'/'.$a['file']);

    if ($archive === '' || hash('sha256', $archive) !== ($a['sha256'] ?? ''))
    {
        $echecs[] = "{$cle} : archive introuvable ou d'une autre empreinte que le catalogue";
        continue;
    }

    $fichier = nf_temp('archive-'.$a['name'].'.zip');
    file_put_contents($fichier, $archive);

    $fenetre    = nf_balisage(nf_http($serveur->base.'/fr/admin/ajax/addons/install', ['ajax' => TRUE, 'suivre' => 0])['corps']);
    $champ      = preg_match('#<input\b[^>]*type="file"[^>]*name="([^"]+)"#i', $fenetre, $c) ? $c[1] : '';
    $formulaire = nf_formulaire($fenetre, 'type="file"') ?? [];

    if ($champ === '')
    {
        $echecs[] = "{$cle} : la fenêtre « Ajouter » ne porte pas de champ d'envoi";
        continue;
    }

    nf_http($serveur->base.'/fr/admin/ajax/addons/install', ['post' => $formulaire, 'fichiers' => [$champ => $fichier], 'ajax' => TRUE, 'suivre' => 0, 'timeout' => 120]);
    @unlink($fichier);
    printf("    %s %s\n", in_array($cle, $installes(), TRUE) ? '·' : '✗', $cle);
}

// ── 3. Tout le catalogue est-il là ? Les pages répondent-elles ? ──────────────
foreach (array_diff($attendus, $installes()) as $cle)
{
    $echecs[] = "{$cle} : pas installé, ni par le marketplace ni par son archive";
}

echo "\n  Routes des modules :\n";
$modules = array_map(static fn (string $c): string => substr($c, 7), array_filter($installes(), static fn (string $c): bool => str_starts_with($c, 'module:')));
$routes  = nf_frapper_profil($serveur->base, array_values($modules));

if ($routes)
{
    $echecs[] = "{$routes} route(s) sans la réponse attendue";
}

$classe  = nf_journal_classer(nf_journal_depuis_octet($journal, $octet));
$fautifs = nf_journal_montrer($classe, $root);

echo "\n";

foreach ($echecs as $echec)
{
    echo '  ✗ ', $echec, "\n";
}

if ($echecs || $fautifs)
{
    nf_echec(sprintf('%d défaut(s), %d message(s) au journal — un addon du marketplace doit s\'installer sur un site qui n\'a que le cœur', count($echecs), $fautifs));
}

nf_ok(sprintf('les %d addons du catalogue s\'installent sur un site « Cœur seul », et le journal est resté muet', count($attendus)));
