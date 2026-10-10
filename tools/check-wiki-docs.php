<?php
declare(strict_types=1);
/**
 * check-wiki-docs — la documentation ne se livre pas, et le wiki d'un site en service dit ce que disent les guides.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * La documentation publique s'écrit dans `docs/guide/*.md` et se lit dans le wiki du site officiel. Jusqu'au
 * 2026-10-09, elle partait aussi dans le produit : `install/wiki.sql`, chargé à chaque installation, et le wiki de la
 * démonstration (`install/demo.sql`). Tout site neuf recevait ainsi la documentation du produit à la place d'un wiki à
 * lui. Elle ne se livre plus : le wiki d'un site neuf arrive vide, celui de la démonstration porte des pages
 * d'exemple. Sans option, le contrôle s'assure qu'aucun fichier livré ne la porte de nouveau.
 *
 * Le wiki d'un site EN SERVICE ne se régénère pas : `docs/` n'est pas dans le paquet, et la publication recopie les
 * pages à la main. Le 2026-10-01, la page « L'API REST », parue en 1.2.10, n'avait jamais atteint le site officiel :
 * la recopie mettait à jour les pages existantes et n'en créait aucune. `--site=DOSSIER` compare le wiki de ce site,
 * lu dans sa base, aux guides.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * - `install/wiki.sql` n'existe plus, et aucun autre `install/*.sql` livré ne porte une page de documentation (les
 *   slugs de tools/lib/wiki.php) ; `install/vitrine.sql`, propre au site officiel et jamais livré, est hors du jugement ;
 * - aucune page convertie ne renvoie hors du wiki par un chemin relatif : le site n'a pas `docs/` ;
 * - avec `--site`, chaque page de documentation du site porte le titre et le contenu de son guide, converti exactement
 *   comme `wiki-docs` le convertit.
 *
 * Usage
 * -----
 *   php tools/check-wiki-docs.php                              code 1 si un fichier livré porte la documentation
 *   php tools/check-wiki-docs.php --site=/var/www/neofrag      et le wiki de ce site, dans sa base
 *
 * Réparer le wiki d'un site en service : `php tools/wiki-docs.php` sur une base de travail, puis la recopie de ses
 * pages de documentation — les pages neuves comprises.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/wiki.php';

[$o] = nf_options(['site' => []]);

['pages' => $attendu, 'manquants' => $manquants] = nf_wiki_attendu();

if ($manquants)
{
    nf_refus('guide(s) introuvable(s) : '.implode(', ', $manquants));
}

$ecarts = 0;

// ── La documentation ne se livre pas ─────────────────────────────────────────
if (is_file(nf_racine().'/install/wiki.sql'))
{
    printf("  ✗ %-18s le fichier est revenu — la documentation ne se livre plus avec le produit\n", 'install/wiki.sql');
    $ecarts++;
}

$livres = 0;

foreach (glob(nf_racine().'/install/*.sql') ?: [] as $fichier)
{
    if (in_array(basename($fichier), ['wiki.sql', 'vitrine.sql'], TRUE))
    {
        continue;
    }

    $livres++;
    ['colonnes' => $colonnes, 'tuples' => $tuples] = nf_sql_tuples((string) file_get_contents($fichier), 'nf_wiki_pages');

    if (($i_slug = array_search('slug', $colonnes, TRUE)) === FALSE)
    {
        continue;
    }

    foreach ($tuples as ['valeurs' => $v])
    {
        if (isset($attendu[(string) $v[$i_slug]]))
        {
            printf("  ✗ %-18s %-18s page de documentation dans un fichier livré\n", 'install/'.basename($fichier), $v[$i_slug]);
            $ecarts++;
        }
    }
}

// ── Une page du wiki ne renvoie jamais hors du wiki par un chemin relatif ────
foreach ($attendu as $slug => $page)
{
    if (preg_match_all('/href="(\.\.\/[^"]*)"/', $page['content'], $morts))
    {
        printf("  ✗ %-18s %-18s lien mort vers %s — la conversion (tools/lib/wiki.php) doit garder le texte\n", 'docs/guide', $slug, implode(', ', $morts[1]));
        $ecarts++;
    }
}

// ── Le wiki d'un site en service ─────────────────────────────────────────────
foreach ((array) $o['site'] as $dossier)
{
    $pages = wiki_du_site((string) $dossier);

    foreach ($attendu as $slug => $page)
    {
        if (!isset($pages[$slug]))
        {
            printf("  ✗ %-18s %-18s page absente\n", $dossier, $slug);
            $ecarts++;
            continue;
        }

        $raisons = [];

        if ($pages[$slug]['title'] !== $page['title'])
        {
            $raisons[] = 'titre « '.$pages[$slug]['title'].' » au lieu de « '.$page['title'].' »';
        }

        if ($pages[$slug]['content'] !== $page['content'])
        {
            $raisons[] = 'contenu différent du guide';
        }

        if ($raisons)
        {
            printf("  ✗ %-18s %-18s %s\n", $dossier, $slug, implode(' ; ', $raisons));
            $ecarts++;
        }
    }
}

if ($ecarts)
{
    echo "\nRéparer : retirer la documentation du fichier livré ; pour un site en service, php tools/wiki-docs.php sur une\n"
        ."base de travail, puis la recopie de ses pages de documentation — les pages neuves comprises.\n";
    nf_echec("{$ecarts} écart(s) : documentation livrée, ou wiki d'un site en retard sur docs/guide/");
}

nf_ok(sprintf('%d fichier(s) livré(s) sans documentation%s', $livres,
    $o['site'] ? ' ; '.count($attendu).' page(s) identiques aux guides dans '.implode(', ', (array) $o['site']) : ''));

/**
 * Les pages du wiki d'un site installé, lues dans sa base (sa `config/db.php`).
 *
 * @return array<string, array{title: string, content: string}>
 */
function wiki_du_site(string $dossier): array
{
    $db = [];

    if (!is_file($fichier = rtrim($dossier, '/').'/config/db.php'))
    {
        nf_refus("{$dossier} : pas de config/db.php — est-ce un site installé ?");
    }

    require $fichier;
    $cfg = is_array($db[0] ?? NULL) ? $db[0] : [];

    $base = nf_connexion_admin([
        'hostname' => (string) ($cfg['hostname'] ?? '127.0.0.1'),
        'port'     => (int) ($cfg['port'] ?? 3306),
        'username' => (string) ($cfg['username'] ?? ''),
        'password' => (string) ($cfg['password'] ?? ''),
    ], 'du site '.$dossier);

    if (!$base->select_db((string) ($cfg['database'] ?? '')))
    {
        nf_refus("{$dossier} : base « ".($cfg['database'] ?? '')." » introuvable");
    }

    $base->set_charset('utf8mb4');
    $pages    = [];
    $resultat = $base->query('SELECT slug, title, content FROM nf_wiki_pages');

    if (!$resultat instanceof mysqli_result)
    {
        nf_refus("{$dossier} : table nf_wiki_pages illisible — le module wiki est-il installé ?");
    }

    while ($ligne = $resultat->fetch_assoc())
    {
        $pages[(string) $ligne['slug']] = ['title' => (string) $ligne['title'], 'content' => (string) $ligne['content']];
    }

    return $pages;
}
