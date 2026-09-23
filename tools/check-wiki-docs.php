<?php
declare(strict_types=1);
/**
 * check-wiki-docs — le wiki livré et celui de la démonstration disent ce que disent les guides.
 *
 * Famille : statique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * La documentation publique s'écrit dans `docs/guide/*.md`. Elle atteint les sites par deux fichiers :
 * `install/wiki.sql`, que l'installateur charge pour que `/wiki` soit peuplé dès l'installation, et
 * `install/demo.sql`, qui porte le wiki de la démonstration. Tous deux se régénèrent par un outil
 * (`wiki-docs`), et rien ne disait qu'il fallait le relancer.
 *
 * Le 2026-09-23, les dix pages de documentation des deux fichiers avaient une semaine de retard sur
 * les guides : la page « Créer un widget » montrait encore `form-group`, classe de Bootstrap 4 que le
 * guide avait déjà quittée — et c'est `check-classes-bs4`, pas un lecteur, qui l'a vu.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Chaque guide est converti exactement comme `wiki-docs` le convertit (tools/lib/wiki.php), et la
 * page de même slug, dans chacun des deux fichiers, doit porter ce titre et ce contenu. Aucune base
 * n'est nécessaire : le contrôle lit les fichiers.
 *
 * Usage
 * -----
 *   php tools/check-wiki-docs.php            code 1 si une page est en retard
 *
 * Réparer : `php tools/wiki-docs.php` (base de l'atelier, puis install/wiki.sql) et
 * `php tools/wiki-docs.php --demo` (install/demo.sql).
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/wiki.php';

nf_options([]);

['pages' => $attendu, 'manquants' => $manquants] = nf_wiki_attendu();

if ($manquants)
{
    nf_refus('guide(s) introuvable(s) : '.implode(', ', $manquants));
}

$ecarts = 0;

foreach (['install/wiki.sql', 'install/demo.sql'] as $relatif)
{
    $sql = @file_get_contents(nf_racine().'/'.$relatif);

    if ($sql === FALSE)
    {
        nf_refus("{$relatif} illisible");
    }

    ['colonnes' => $colonnes, 'tuples' => $tuples] = nf_sql_tuples($sql, 'nf_wiki_pages');
    $i_slug    = array_search('slug', $colonnes, TRUE);
    $i_titre   = array_search('title', $colonnes, TRUE);
    $i_contenu = array_search('content', $colonnes, TRUE);

    if ($i_slug === FALSE || $i_titre === FALSE || $i_contenu === FALSE || !$tuples)
    {
        nf_refus("{$relatif} : aucune ligne de nf_wiki_pages lisible — le format du fichier a changé ?");
    }

    $vus = [];

    foreach ($tuples as ['valeurs' => $v])
    {
        $slug = (string) $v[$i_slug];

        if (!isset($attendu[$slug]))
        {
            continue;
        }

        $vus[$slug] = TRUE;
        $raisons    = [];

        if ($v[$i_titre] !== $attendu[$slug]['title'])
        {
            $raisons[] = 'titre « '.$v[$i_titre].' » au lieu de « '.$attendu[$slug]['title'].' »';
        }

        if ($v[$i_contenu] !== $attendu[$slug]['content'])
        {
            $raisons[] = 'contenu différent du guide';
        }

        if ($raisons)
        {
            printf("  ✗ %-18s %-18s %s\n", $relatif, $slug, implode(' ; ', $raisons));
            $ecarts++;
        }
    }

    foreach (array_diff_key($attendu, $vus) as $slug => $_)
    {
        printf("  ✗ %-18s %-18s page absente\n", $relatif, $slug);
        $ecarts++;
    }
}

if ($ecarts)
{
    echo "\nRéparer : php tools/wiki-docs.php (puis rapatrier install/wiki.sql) et php tools/wiki-docs.php --demo\n";
    nf_echec("{$ecarts} page(s) de documentation en retard sur docs/guide/");
}

nf_ok(count($attendu).' page(s) de documentation identiques aux guides, dans install/wiki.sql et install/demo.sql');
