<?php
declare(strict_types=1);

/**
 * check-mise-a-jour — un site neuf, à la version précédente, se met à jour par le vrai bouton depuis l'origine publiée, et arrive à la version annoncée.
 * Famille : cible
 *
 * Pourquoi
 * --------
 * La chaîne de mise à jour du cœur existait de bout en bout — `build-release` fabrique le paquet et
 * ses deux manifestes, le bouton « Mettre à jour » du Monitoring sauvegarde, vérifie l'empreinte,
 * applique et migre — mais rien ne l'avait jamais fait tourner EN ENTIER : le dossier `/update/` de
 * l'origine n'existait pas. Seuls le site vitrine, la démonstration et l'atelier emploient NeoFrag
 * Reborn ; c'est donc sur un site jetable qu'on découvre les défauts de la chaîne, pas sur celui des
 * premiers utilisateurs (2026-09-23).
 *
 * Ce que l'outil fait
 * -------------------
 *   1. il lit `version.json` à l'origine : la version annoncée, le nom et l'empreinte du paquet ;
 *   2. il monte un site NEUF (`lib/vierge.php`) et le RAMÈNE à la version PRÉCÉDENTE — la dernière
 *      publiée avant la version annoncée, lue dans `CHANGELOG.md` : les fichiers de son paquet publié,
 *      sans ceux apportés depuis, et sa base sans les migrations arrivées depuis ;
 *   3. il pointe ce site vers l'origine, ouvre une session d'administrateur et rejoue les deux
 *      requêtes du panneau : « actualiser » (le manifeste est téléchargé), puis « mettre à jour » ;
 *   4. il vérifie chaque promesse : la version annoncée est installée, CHAQUE fichier du paquet est
 *      identique à l'empreinte de `checksum.json`, l'accueil et l'administration répondent, la mise à
 *      jour est inscrite au journal d'audit, et le journal d'erreurs du site est resté muet.
 *
 * Une origine de répétition (`--origine=https://neofrag-reborn.xyz/update/repetition`) permet
 * d'éprouver une version AVANT de la publier là où les sites la cherchent. Seuls les hôtes de
 * l'allow-list sont acceptés par l'updater : l'outil refuse les autres avant de commencer.
 *
 * Usage
 * -----
 *   php tools/check-mise-a-jour.php                                             l'origine publiée
 *   php tools/check-mise-a-jour.php --origine=https://neofrag-reborn.xyz/update/repetition
 *   php tools/check-mise-a-jour.php --depart=1.1.0                              la version de départ
 *   php tools/check-mise-a-jour.php --origine-depart=https://…/update           où lire son paquet
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/vierge.php';
require_once __DIR__.'/../install/lib/installer.php';

use NF\Install\Lib\Installer;

[$o] = nf_options([
    'origine'        => 'https://neofrag-reborn.xyz/update',
    'origine-depart' => 'https://neofrag-reborn.xyz/update',
    'depart'         => '',
    'port'           => 0,
]);

$racine  = nf_racine();
$origine = rtrim((string) $o['origine'], '/');

// ── 1. L'origine, et ce qu'elle annonce ─────────────────────────────────────────────────────
if (Installer::sanitize_update_url($origine) !== $origine)
{
    nf_refus("l'origine « {$origine} » n'est pas dans l'allow-list de l'updater (HTTPS, port 443, hôte "
        .implode(' ou ', Installer::MARKETPLACE_HOSTS).') : le site la remplacerait par l\'origine par défaut');
}

$annonce  = Installer::fetch_version_manifest($origine);
$empreintes = Installer::fetch_checksum_manifest($origine);

if ($annonce === NULL || $empreintes === NULL)
{
    nf_refus("l'origine « {$origine} » ne publie pas de jeu complet : "
        .($annonce === NULL ? 'version.json absent ou invalide' : 'checksum.json absent ou invalide')
        .' — publier les trois fichiers de build-release ensemble');
}

$cible = (string) $annonce['neofrag']['version'];

// ── 2. La version de départ : la précédente publiée, lue dans le CHANGELOG ──────────────────
$depart = (string) $o['depart'];

if ($depart === '')
{
    preg_match_all('/^## \[(\d+\.\d+\.\d+)\]/m', (string) file_get_contents($racine.'/CHANGELOG.md'), $versions);

    foreach ($versions[1] as $version)
    {
        if (version_compare($version, $cible, '<'))
        {
            $depart = $version;
            break;
        }
    }
}

if ($depart === '' || !version_compare($depart, $cible, '<'))
{
    nf_refus("aucune version de départ inférieure à {$cible} — la donner avec --depart=X.Y.Z");
}

printf("Origine : %s\nAnnoncée : %s (%s)\nDépart : %s\n\n", $origine, $cible, (string) ($annonce['neofrag']['file'] ?? '?'), $depart);

// ── 3. Le site neuf, ramené à la version de départ ──────────────────────────────────────────
//
// Jusqu'au 2026-10-01, c'était une copie de ce dépôt dont on rabaissait le numéro : sa base avait
// déjà les migrations de la cible, et c'est le NOUVEAU code de mise à jour qui tournait. L'épreuve
// prouvait le passage du neuf au neuf — et n'a donc jamais vu qu'une mise à jour n'appliquait aucune
// migration. Le site est désormais ramené à la vraie version de départ : les fichiers de son paquet
// publié, sans ceux apportés depuis, et sa base sans les migrations arrivées depuis.
echo "Installation d'un site neuf…\n";
$vierge = nf_site_vierge('neofrag_maj_install_test');
$site   = $vierge['racine'];
$db     = $vierge['db'];

// Les versions publiées restent sur l'origine réelle, même quand la cible est en répétition.
$url_depart = $o['origine-depart'].'/neofrag-reborn-update-'.$depart.'.zip';
$paquet     = nf_http($url_depart, ['timeout' => 300]);
$zip        = new ZipArchive();
$fichier    = $site.'/../paquet-depart-'.$depart.'.zip';

if ($paquet['code'] !== 200 || file_put_contents($fichier, $paquet['corps']) === FALSE || $zip->open($fichier) !== TRUE)
{
    nf_refus("le paquet de départ {$depart} est introuvable ({$url_depart} : HTTP {$paquet['code']}) — le donner avec --origine-depart= ou --depart=");
}

$du_depart = [];

for ($i = 0; $i < $zip->numFiles; $i++)
{
    $entree = (string) $zip->getNameIndex($i);

    // Ce que l'updater écrit, et rien d'autre : ni dossiers, ni fichiers de racine hors index.php,
    // ni config/ et install/ déjà en place.
    if (str_ends_with($entree, '/') || !preg_match('#/|^index\.php$#', $entree) || (preg_match('#^(config|install)/#', $entree) && file_exists($site.'/'.$entree)))
    {
        continue;
    }

    $du_depart[$entree] = TRUE;
    @mkdir(dirname($site.'/'.$entree), 0775, TRUE);
    file_put_contents($site.'/'.$entree, (string) $zip->getFromIndex($i));
}

$zip->close();
@unlink($fichier);

// Ce que la cible apporte et que le départ n'avait pas : retiré, et ses migrations défaites, de la
// plus récente à la plus ancienne.
$apportees = [];

foreach (array_keys($empreintes) as $chemin)
{
    $chemin = (string) $chemin;

    if (isset($du_depart[$chemin]) || preg_match('#^(config|install)/#', $chemin))
    {
        continue;
    }

    if (preg_match('#^migrations/(.+)\.up\.sql$#', $chemin, $m))
    {
        $apportees[] = $m[1];
    }

    @unlink($site.'/'.$chemin);
}

rsort($apportees);

foreach ($apportees as $migration)
{
    Installer::import_sql_file($db, $racine.'/migrations/'.$migration.'.down.sql');
    $db->query("DELETE FROM nf_migrations WHERE name = '".$db->real_escape_string($migration)."'");
}

if (!preg_match("/define\('NEOFRAG_VERSION', '([^']*)'\);/", (string) file_get_contents($site.'/index.php'), $m) || $m[1] !== $depart)
{
    nf_refus(sprintf('le site ramené au paquet %s annonce %s', $depart, $m[1] ?? '?'));
}

printf("  ramené à %s : %d fichier(s) du paquet publié, %d migration(s) de la cible défaite(s)%s\n",
    $depart, count($du_depart), count($apportees), $apportees ? ' ('.implode(', ', $apportees).')' : '');

// Le réglage n'existe pas forcément sur un site neuf : nf_reglage_poser() ne ferait rien.
$db->query("INSERT INTO nf_settings (name, site, lang, value, type) VALUES ('nf_monitoring_check_url', '', '', '"
    .$db->real_escape_string($origine)."', 'string') ON DUPLICATE KEY UPDATE value = VALUES(value)");

$journal = $site.'/logs/php.log';
@mkdir(dirname($journal), 0775, TRUE);
@file_put_contents($journal, '');

$serveur = nf_serveur(nf_port((int) $o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db)], $site);

// ── 4. Les deux gestes du panneau ───────────────────────────────────────────────────────────
echo "Actualisation du Monitoring (téléchargement du manifeste)…\n";
$reponse = nf_http($serveur->base.'/fr/admin/ajax/monitoring.json', ['post' => ['refresh' => '1'], 'ajax' => TRUE, 'timeout' => 120]);

if ($reponse['code'] !== 200)
{
    nf_echec("l'actualisation du Monitoring a répondu {$reponse['code']} ".$reponse['raison']);
}

if (!is_file($site.'/cache/monitoring/version.json'))
{
    nf_echec("le Monitoring n'a pas enregistré version.json : le site ne verra jamais la mise à jour");
}

echo "Mise à jour par le bouton…\n";
$reponse = nf_http($serveur->base.'/fr/admin/ajax/monitoring/update.json', ['ajax' => TRUE, 'timeout' => 900]);
$flux    = trim($reponse['corps']);

$echecs = [];

if ($reponse['code'] !== 200)
{
    $echecs[] = "la mise à jour a répondu {$reponse['code']} ".$reponse['raison'];
}

if ($flux === '')
{
    $echecs[] = 'la mise à jour n\'a rien rendu : le site se croyait déjà à jour, ou n\'a pas lu le manifeste';
}
elseif (!str_contains($flux, '[4,100]'))
{
    $echecs[] = 'la mise à jour ne va pas au bout — elle dit : '.substr((string) preg_replace('/\s+/', ' ', $flux), -300);
}

// ── 5. Chaque promesse ──────────────────────────────────────────────────────────────────────
if (!$echecs)
{
    clearstatcache();

    if (!preg_match("/define\('NEOFRAG_VERSION', '([^']*)'\);/", (string) file_get_contents($site.'/index.php'), $m) || $m[1] !== $cible)
    {
        $echecs[] = sprintf('index.php annonce %s après la mise à jour, pas %s', $m[1] ?? '?', $cible);
    }

    $reglage = nf_reglage($db, 'nf_version');

    if ($reglage !== NULL && $reglage !== $cible)
    {
        $echecs[] = "le réglage nf_version vaut {$reglage}, pas {$cible}";
    }

    // Le paquet ne réécrit jamais config/ ni install/ quand ils existent : ce sont les fichiers du site.
    $differents = [];
    $absents    = [];

    foreach ($empreintes as $chemin => $md5)
    {
        if (preg_match('#^(config|install)/#', (string) $chemin))
        {
            continue;
        }

        $fichier = $site.'/'.$chemin;

        if (!is_file($fichier))
        {
            $absents[] = $chemin;
        }
        elseif (md5_file($fichier) !== $md5)
        {
            $differents[] = $chemin;
        }
    }

    foreach ([['absent(s)', $absents], ['différent(s) du paquet', $differents]] as [$quoi, $liste])
    {
        if ($liste)
        {
            $echecs[] = sprintf('%d fichier(s) %s, dont : %s', count($liste), $quoi, implode(', ', array_slice($liste, 0, 5)));
        }
    }

    foreach (['/fr' => 'l\'accueil', '/fr/admin' => 'l\'administration'] as $chemin => $nom)
    {
        if (($statut = nf_http($serveur->base.$chemin)['code']) !== 200)
        {
            $echecs[] = "{$nom} répond {$statut} après la mise à jour";
        }
    }

    // La base suit le code. Jusqu'au 2026-10-01, une mise à jour posait les fichiers et laissait la
    // base telle quelle : les migrations n'étaient appliquées qu'à l'installation. Les deux pages
    // ci-dessus ont fait passer le site par son rattrapage (index.php) : plus rien n'est en attente.
    $appliquees = array_flip(array_map('strval', array_column($db->query('SELECT name FROM nf_migrations')->fetch_all(MYSQLI_ASSOC), 'name')));
    $manquantes = array_values(array_filter(
        array_map(static fn (string $f): string => basename($f, '.up.sql'), glob($site.'/migrations/*.up.sql') ?: []),
        static fn (string $nom): bool => !isset($appliquees[$nom])
    ));

    if ($manquantes)
    {
        $echecs[] = sprintf('%d migration(s) livrée(s) mais pas appliquée(s) : %s', count($manquantes), implode(', ', array_slice($manquantes, 0, 5)));
    }

    if (($schema = nf_reglage($db, 'nf_schema_version')) !== $cible)
    {
        $echecs[] = 'le réglage nf_schema_version vaut '.($schema ?? '(absent)').", pas {$cible}";
    }
}

// Le journal d'erreurs doit rester MUET : une mise à jour réussie s'inscrit au journal d'audit.
$lignes = array_values(array_filter(array_map('trim', explode("\n", (string) @file_get_contents($journal)))));
$autres = $lignes;

if (!$echecs && (int) nf_scalar($db, "SELECT COUNT(*) FROM nf_audit_log WHERE action = 'core.updated' AND target_id = '".$db->real_escape_string($cible)."'") === 0)
{
    $echecs[] = "la mise à jour n'est pas inscrite au journal d'audit (action core.updated)";
}

foreach (array_slice($autres, 0, 5) as $ligne)
{
    $echecs[] = 'journal : '.substr($ligne, 0, 200);
}

$serveur->arreter();

if ($echecs)
{
    echo "\n";

    foreach ($echecs as $echec)
    {
        echo "  ✗ {$echec}\n";
    }

    // Ce qu'il faut pour comprendre sans rejouer : ce que le site a mis en cache, la version qu'il
    // croit avoir, et ce que la requête du bouton a vraiment reçu.
    preg_match("/define\('NEOFRAG_VERSION', '([^']*)'\);/", (string) @file_get_contents($site.'/index.php'), $v);
    printf("\n  Diagnostic :\n    version du site jetable : %s\n    manifeste en cache : %s\n    réponse du bouton : HTTP %d, %d octet(s)%s\n",
        $v[1] ?? '?',
        substr((string) preg_replace('/\s+/', ' ', (string) @file_get_contents($site.'/cache/monitoring/version.json')), 0, 300) ?: '(absent)',
        $reponse['code'] ?? 0,
        strlen((string) ($reponse['corps'] ?? '')),
        !empty($reponse['entetes']) ? ' — '.implode(', ', array_map(static fn ($k, $val) => $k.': '.$val, array_keys($reponse['entetes']), $reponse['entetes'])) : '');

    foreach (array_slice($lignes, -8) as $ligne)
    {
        echo '    journal : '.substr($ligne, 0, 220)."\n";
    }

    nf_echec(sprintf('la mise à jour %s → %s depuis %s ne tient pas ses promesses (%d point(s))', $depart, $cible, $origine, count($echecs)));
}

printf("\n  ✓ %s installée, %d fichier(s) du paquet identiques à checksum.json, accueil et administration en 200, migrations livrées toutes appliquées, mise à jour inscrite à l'audit, journal muet.\n",
    $cible, count($empreintes));

nf_ok("un site en {$depart} se met à jour en {$cible} par le bouton, depuis {$origine}");
