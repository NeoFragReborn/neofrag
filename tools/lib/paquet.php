<?php
declare(strict_types=1);
require_once __DIR__.'/outil.php';

/**
 * paquet — ce qui a le droit d'entrer dans un paquet d'installation ou de mise à jour.
 *
 * Diffusion : publique
 *
 * Une LISTE BLANCHE de la racine, et non plus des exclusions une à une. Avec des exclusions, tout
 * fichier posé à la racine du dépôt partait dans les trois paquets tant que personne ne l'avait exclu :
 * le 2026-10-01, un mémo d'exploitation (adresse du serveur, organisation DNS et messagerie, aucun mot
 * de passe) y a été déposé, et il est parti dans les paquets de la 1.2.8 à la 1.2.18 — la mise à jour
 * 1.2.18 restait téléchargeable le 2026-10-04 (audit des releases). Une entrée nouvelle à la racine
 * n'entre désormais dans un paquet que si on l'ajoute ici, en connaissance de cause.
 *
 * Lu par `build-release`, qui fabrique, et par l'inventaire de la fabrique des paquets, qui vérifie le produit
 * fini (`php tools/build-release.php --racine-autorisee`).
 */

/** Les seules entrées de premier niveau qu'un paquet puisse porter. */
const NF_PAQUET_RACINE = [
    // Le site.
    'index.php', '.htaccess', 'addons', 'config', 'css', 'fonts', 'images', 'install', 'js',
    'migrations', 'modules', 'neofrag', 'themes', 'upload', 'vendor', 'widgets',
    // Ses dossiers d'exécution, vides : seulement leur garde (cf. nf_paquet_exclu()).
    'cache', 'logs', 'backups',
    // Ce qui l'accompagne : la licence, la feuille de route, le catalogue livré, l'éditeur, et les
    // exemples de configuration pour nginx et Caddy (le `.htaccess` sert Apache) — les guides les
    // disaient livrés, aucun paquet ne les portait jusqu'au 2026-10-04.
    'COPYING', 'COPYING.LESSER', 'NOTICE', 'LICENSES', 'ROADMAP.md', 'marketplace', '.editorconfig', 'nginx.conf', 'Caddyfile',
];

/** Les entrées que la fabrique ajoute elle-même au paquet, absentes du dépôt. */
const NF_PAQUET_ENGENDRES = ['nf-manifest.json'];

/**
 * Ce fichier du dépôt (chemin relatif) reste-t-il HORS du paquet `$variant` (demo | public | update) ?
 *
 * Après la liste blanche, des règles à l'intérieur des dossiers admis : les secrets de `config/` (seuls
 * les gabarits `.dist`, le `.htaccess` et le README passent ; le `neofrag.php` du paquet est engendré à
 * part), le verrou d'installation, le contenu de la démonstration hors de son paquet, la vitrine
 * (jamais diffusée), ce que les visiteurs déposent dans `upload/`, et les fichiers de travail.
 *
 * `cache/`, `logs/` et `backups/` partent VIDES dans les paquets d'installation, avec leur seule garde
 * Apache : l'archive crée ainsi le dossier. Jusqu'à la 1.2.22, aucun paquet ne les portait et rien ne
 * créait `logs/` : un site installé depuis le paquet n'enregistrait aucune erreur (PHP écrit dans
 * `logs/php.log`), et son Monitoring le disait « non inscriptible ». La mise à jour ne les porte pas :
 * elle ne touche jamais à ces dossiers.
 */
function nf_paquet_exclu(string $rel, string $variant): bool
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');

    if (!in_array(explode('/', $rel, 2)[0], NF_PAQUET_RACINE, TRUE))
    {
        return TRUE;
    }

    if (str_starts_with($rel, 'config/') && !preg_match('#^config/([^/]+\.php\.dist|\.htaccess|README\.md)$#', $rel))
    {
        return TRUE;
    }

    if ($rel === 'install/db.txt' || $rel === 'install/vitrine.sql')
    {
        return TRUE;
    }

    // Le contenu de la démonstration n'est que dans le paquet démo.
    if ($rel === 'install/demo.sql')
    {
        return $variant !== 'demo';
    }

    // La vitrine (thème « vitrine », widget « landing ») est le site officiel : aucun paquet ne la porte.
    foreach (['themes/vitrine/', 'widgets/landing/'] as $dossier)
    {
        if (str_starts_with($rel.'/', $dossier))
        {
            return TRUE;
        }
    }

    foreach (['cache/', 'logs/', 'backups/'] as $dossier)
    {
        if (str_starts_with($rel, $dossier))
        {
            return $variant === 'update' || $rel !== $dossier.'.htaccess';
        }
    }

    // upload/ : seulement sa garde ; marketplace/ : seulement le catalogue (les archives sont servies à part).
    if ((str_starts_with($rel, 'upload/') && $rel !== 'upload/.htaccess')
        || (str_starts_with($rel, 'marketplace/') && $rel !== 'marketplace/catalog.json'))
    {
        return TRUE;
    }

    // Le catalogue part avec une installation, pas avec une mise à jour : sur la vitrine, c'est le marketplace
    // VIVANT. Le paquet de la 1.2.29 portait celui du dépôt, fabriqué à la 1.2.22 : le clic de mise à jour l'a
    // posé sur la vitrine, à côté des archives de la 1.2.28, et chaque installation par le marketplace refusait
    // son empreinte jusqu'à ce que l'étape d'après le clic repose le bon (2026-10-05, une dizaine de minutes).
    if ($rel === 'marketplace/catalog.json')
    {
        return $variant === 'update';
    }

    return in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), ['log', 'map', 'scssc'], TRUE);
}

/** La liste versionnée des fichiers qu'un paquet a livrés puis que le produit a retirés (`php tools/fichiers-retires.php`). */
const NF_PAQUET_RETIRES = 'tools/lib/fichiers-retires.php';

/**
 * Les fichiers qu'un paquet a livrés, et que le produit a retirés depuis : ce qu'une mise à jour doit effacer d'un site.
 *
 * Ils se lisent dans l'historique git, ce que la fabrique ne peut pas faire : elle travaille sur une copie sans `.git`
 * (`git archive`), et sans l'inventaire de la version précédente sous la main. La liste `remove` du manifeste sortait
 * donc VIDE, de la 1.2.48 au moins à la 1.2.52 : aucun fichier retiré ne quittait les sites mis à jour, et le
 * Monitoring de la vitrine signalait les anciennes polices de Font Awesome et deux langues renommées de TinyMCE
 * (2026-10-16). La liste se calcule donc ici, sur le poste, et se versionne ; la fabrique la lit.
 *
 * Un chemin revenu dans l'arbre n'en fait plus partie, et seul compte ce qu'un paquet de mise à jour porte. Un
 * addon que le produit a retiré EN ENTIER (le thème Dungeon, le widget lang_switcher…) n'en fait pas partie non
 * plus : un site peut l'avoir encore installé, et actif — effacer ses fichiers le désinstallerait à moitié. Seuls
 * partent les fichiers morts du cœur et des addons qui vivent toujours.
 *
 * @return list<string>|null NULL hors d'un dépôt git
 */
function nf_paquet_retires_git(string $racine): ?array
{
    if (!file_exists($racine.'/.git'))
    {
        return NULL;
    }

    exec('git -c core.quotePath=false -C '.escapeshellarg($racine).' log --no-renames --diff-filter=D --name-only --format= 2>&1', $lignes, $code);

    if ($code !== 0)
    {
        return NULL;
    }

    $retires = [];

    foreach ($lignes as $chemin)
    {
        $chemin = trim($chemin);

        if ($chemin === '' || file_exists($racine.'/'.$chemin) || nf_paquet_exclu($chemin, 'update'))
        {
            continue;
        }

        if (preg_match('#^(?:modules|widgets|themes|addons)/[^/]+(?=/)#', $chemin, $addon) && !is_dir($racine.'/'.$addon[0]))
        {
            continue;
        }

        $retires[$chemin] = TRUE;
    }

    ksort($retires, SORT_STRING);

    return array_keys($retires);
}

/**
 * La liste versionnée (NF_PAQUET_RETIRES) ; vide si elle n'existe pas encore.
 *
 * @return list<string>
 */
function nf_paquet_retires(string $racine): array
{
    $liste = is_file($fichier = $racine.'/'.NF_PAQUET_RETIRES) ? require $fichier : [];

    return is_array($liste) ? array_values(array_filter($liste, 'is_string')) : [];
}
