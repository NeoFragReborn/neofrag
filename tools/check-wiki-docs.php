<?php
declare(strict_types=1);
/**
 * check-wiki-docs — le wiki livré et celui de la démonstration disent ce que disent les guides.
 *
 * Famille : statique
 * Diffusion : publique
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
 * Le wiki d'un site EN SERVICE (la vitrine) ne se régénère pas : `docs/` n'est pas dans le paquet, et
 * la publication recopie les pages à la main. Le 2026-10-01, la page « L'API REST », livrée en 1.2.10,
 * n'avait jamais atteint la vitrine : la recopie mettait à jour les pages existantes et n'en créait
 * aucune. `--site=DOSSIER` compare aussi le wiki de ce site, lu dans sa base.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Chaque guide est converti exactement comme `wiki-docs` le convertit (tools/lib/wiki.php), et la
 * page de même slug, dans chacun des deux fichiers — et dans chaque site demandé —, doit porter ce
 * titre et ce contenu. Sans `--site`, aucune base n'est nécessaire : le contrôle lit les fichiers.
 *
 * Usage
 * -----
 *   php tools/check-wiki-docs.php                              code 1 si une page est en retard
 *   php tools/check-wiki-docs.php --site=/var/www/neofrag      et le wiki d'un site installé, dans sa base
 *
 * Réparer : `php tools/wiki-docs.php --sur-place` quand une page a seulement changé (install/wiki.sql et
 * install/demo.sql réécrits sans base) ; `php tools/wiki-docs.php` sur une base quand une page s'ajoute
 * ou se renomme ; pour un site en service, recopier ses pages de documentation depuis une base où
 * `wiki-docs` les a écrites.
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

/** @var array<string, array<string, array{title: string, content: string}>> $sources  source => slug => page */
$sources = [];

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

    foreach ($tuples as ['valeurs' => $v])
    {
        $sources[$relatif][(string) $v[$i_slug]] = ['title' => (string) $v[$i_titre], 'content' => (string) $v[$i_contenu]];
    }
}

foreach ((array) $o['site'] as $dossier)
{
    $sources[(string) $dossier] = wiki_du_site((string) $dossier);
}

$ecarts = 0;

// Une page du wiki ne renvoie jamais hors du wiki par un chemin relatif : le site n'a pas `docs/`.
foreach ($attendu as $slug => $page)
{
    if (preg_match_all('/href="(\.\.\/[^"]*)"/', $page['content'], $morts))
    {
        printf("  ✗ %-18s %-18s lien mort vers %s — la conversion (tools/lib/wiki.php) doit garder le texte\n", 'docs/guide', $slug, implode(', ', $morts[1]));
        $ecarts++;
    }
}

foreach ($sources as $source => $pages)
{
    foreach ($attendu as $slug => $page)
    {
        if (!isset($pages[$slug]))
        {
            printf("  ✗ %-18s %-18s page absente\n", $source, $slug);
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
            printf("  ✗ %-18s %-18s %s\n", $source, $slug, implode(' ; ', $raisons));
            $ecarts++;
        }
    }
}

if ($ecarts)
{
    echo "\nRéparer : php tools/wiki-docs.php --sur-place (une page seulement modifiée), ou php tools/wiki-docs.php\n"
        ."sur une base (une page ajoutée ou renommée) ; pour un site en service, la recopie de ses pages de\n"
        ."documentation — les pages neuves comprises.\n";
    nf_echec("{$ecarts} page(s) de documentation en retard sur docs/guide/");
}

nf_ok(count($attendu).' page(s) de documentation identiques aux guides, dans '.implode(', ', array_keys($sources)));

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
