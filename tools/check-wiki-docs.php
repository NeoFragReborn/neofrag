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
 * - chaque renvoi vers une section (`admin#…`, `#…`) mène à un titre de la page visée, dans sa langue : le wiki
 *   pose ses ancres sur les titres de niveau 2 à 4 (Wiki_Ancres), et les guides écrivent les leurs comme GitHub.
 *   Avant m19, `admin#le-référencement` ou `framework#tables--table2` ne menaient nulle part ;
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
$traductions = [];

foreach (NF_WIKI_LANGUES as $langue)
{
    $traductions[$langue] = nf_wiki_attendu_traduit($langue);
}

foreach (array_merge([$attendu], array_values($traductions)) as $jeu)
foreach ($jeu as $slug => $page)
{
    if (preg_match_all('/href="(\.\.\/[^"]*)"/', $page['content'], $morts))
    {
        printf("  ✗ %-18s %-18s lien mort vers %s — la conversion (tools/lib/wiki.php) doit garder le texte\n", 'docs/guide', $slug, implode(', ', $morts[1]));
        $ecarts++;
    }
}

// ── Un renvoi vers une section mène à un titre de la page visée ──────────────
$renvois = 0;

foreach (['fr' => $attendu] + $traductions as $langue => $jeu)
{
    $ids = array_map(static fn (array $page): array => NF\Modules\Wiki\Lib\Wiki_Ancres::ids($page['content']), $jeu);

    foreach ($jeu as $slug => $page)
    {
        if (!preg_match_all('/href="([a-z0-9-]*)#([^"]+)"/', $page['content'], $liens, PREG_SET_ORDER))
        {
            continue;
        }

        foreach ($liens as [, $cible, $ancre])
        {
            $cible = $cible === '' ? $slug : $cible;

            if (!isset($jeu[$cible]))
            {
                continue;
            }

            $renvois++;
            $ancre = rawurldecode(html_entity_decode($ancre, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if (!in_array($ancre, $ids[$cible], TRUE))
            {
                printf("  ✗ %-18s %-18s renvoi vers %s#%s, titre introuvable dans le wiki (%s)\n", 'docs/guide', $slug, $cible, $ancre, $langue);
                $ecarts++;
            }
        }
    }
}

// ── Le wiki d'un site en service ─────────────────────────────────────────────
foreach ((array) $o['site'] as $dossier)
foreach (['fr' => $attendu] + $traductions as $langue => $jeu)
{
    $pages = wiki_du_site((string) $dossier, $langue);

    foreach ($jeu as $slug => $page)
    {
        if (!isset($pages[$slug]))
        {
            printf("  ✗ %-18s %-18s page absente (%s)\n", $dossier, $slug, $langue);
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
            printf("  ✗ %-18s %-18s %s (%s)\n", $dossier, $slug, implode(' ; ', $raisons), $langue);
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

nf_ok(sprintf('%d fichier(s) livré(s) sans documentation ; %d renvoi(s) vers une section, tous menant à leur titre%s', $livres, $renvois,
    $o['site'] ? ' ; '.count($attendu).' page(s) identiques aux guides dans '.implode(', ', (array) $o['site']) : ''));

/**
 * Les pages du wiki d'un site installé, lues dans sa base (sa `config/db.php`).
 *
 * @return array<string, array{title: string, content: string}>
 */
function wiki_du_site(string $dossier, string $langue = 'fr'): array
{
    $base     = nf_connexion_site($dossier);
    $pages    = [];
    // Depuis m32 (2026-10-15), le titre et le texte vivent dans `nf_wiki_pages_lang` : les guides sont la version
    // FRANÇAISE. Un site pas encore mis à jour les porte encore dans `nf_wiki_pages`.
    $traduit  = ($t = $base->query("SHOW TABLES LIKE 'nf_wiki_pages_lang'")) instanceof mysqli_result && $t->num_rows > 0;
    $resultat = $base->query($traduit
        ? "SELECT p.slug, l.title, l.content FROM nf_wiki_pages p JOIN nf_wiki_pages_lang l ON l.page_id = p.id AND l.lang = '".$base->real_escape_string($langue)."'"
        : 'SELECT slug, title, content FROM nf_wiki_pages');

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
