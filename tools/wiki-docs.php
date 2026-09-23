<?php
declare(strict_types=1);

/**
 * wiki-docs — transfère docs/guide/*.md dans le module wiki, puis fige le wiki en install/wiki.sql.
 *
 * Famille : outil
 *
 * Pourquoi
 * --------
 * La documentation publique est écrite en Markdown dans `docs/guide/`, et lue par les visiteurs
 * dans le module wiki du site. `install/wiki.sql` est chargé par l'installateur pour que `/wiki`
 * soit peuplé dès l'installation — sinon le module serait installé mais vide, `docs/` étant hors du
 * paquet FTP. Les deux gestes allaient toujours ensemble (peupler, puis figer) et vivaient dans
 * deux outils : les voici dans un seul, dans l'ordre où ils se font.
 *
 * Le contenu est converti en HTML au moment du transfert : le wiki rend via render_content(), dont
 * l'heuristique Markdown est trompée par les balises HTML présentes dans nos blocs de code.
 * Idempotent : les pages de documentation sont purgées puis réinsérées.
 *
 * Le wiki de la DÉMONSTRATION vit dans `install/demo.sql`, que `dump-demo` écrit depuis la base de la
 * démo. Le régénérer pour dix pages n'aurait pas de sens : `--demo` réécrit seulement les lignes des
 * pages de documentation, titre et contenu, sans rien toucher d'autre du fichier. Le 2026-09-23, ce
 * troisième geste manquait, et la démo avait une semaine de retard ; `check-wiki-docs` le voit
 * désormais.
 *
 * Usage
 * -----
 *   php tools/wiki-docs.php              les trois gestes : base, install/wiki.sql, install/demo.sql
 *   php tools/wiki-docs.php --peupler    seulement le transfert dans la base
 *   php tools/wiki-docs.php --figer      seulement install/wiki.sql depuis la base
 *   php tools/wiki-docs.php --demo       seulement les pages de documentation de install/demo.sql
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/wiki.php';

[$o] = nf_options(['peupler' => FALSE, 'figer' => FALSE, 'demo' => FALSE]);

if (!$o['peupler'] && !$o['figer'] && !$o['demo'])
{
    $o['peupler'] = $o['figer'] = $o['demo'] = TRUE;
}

$db = ($o['peupler'] || $o['figer']) ? nf_connexion() : NULL;

// ── Peupler : docs/guide/*.md → nf_wiki_pages ───────────────────────────────
if ($o['peupler'])
{
    $sections = nf_wiki_sections();

    $inserer = static function (string $slug, string $title, string $content, ?int $parent, int $sort) use ($db): int {
        if ($parent === NULL)
        {
            $stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,title,content,sort_order,published) VALUES (?,?,?,?,1)');
            $stmt->bind_param('sssi', $slug, $title, $content, $sort);
        }
        else
        {
            $stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,title,content,parent_id,sort_order,published) VALUES (?,?,?,?,?,1)');
            $stmt->bind_param('sssii', $slug, $title, $content, $parent, $sort);
        }

        $stmt->execute();
        $id = (int) $db->insert_id;
        $stmt->close();

        return $id;
    };

    // Purge des anciennes pages de doc.
    $slugs = array_keys($sections);

    foreach ($sections as $sec)
    {
        $slugs = array_merge($slugs, array_keys($sec['pages']));
    }

    $db->query('DELETE FROM nf_wiki_pages WHERE slug IN ('.implode(',', array_map(static fn (string $s): string => "'".$db->real_escape_string($s)."'", $slugs)).')');

    $top   = 0;
    $count = 0;

    foreach ($sections as $pslug => $sec)
    {
        $pid = $inserer($pslug, $sec['title'], nf_wiki_convertir($sec['intro']), NULL, ++$top);
        $sub = 0;

        foreach ($sec['pages'] as $slug => $title)
        {
            $file = nf_racine()."/docs/guide/{$slug}.md";

            if (!is_file($file))
            {
                nf_avertir("  ⚠ manquant : docs/guide/{$slug}.md");
                continue;
            }

            $inserer($slug, $title, nf_wiki_convertir((string) file_get_contents($file)), $pid, ++$sub);
            $count++;
            echo "  + {$pslug}/{$slug}\n";
        }
    }

    echo "\n{$count} pages de doc transférées dans le wiki\n";
}

// ── Figer : nf_wiki_pages → install/wiki.sql ────────────────────────────────
if ($o['figer'])
{
    $out  = nf_sql_entete('wiki-docs', 'contenu du wiki (documentation), chargé à l\'installation');
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET NAMES utf8mb4;\n\n";
    $out .= "TRUNCATE TABLE `nf_wiki_pages`;\n";

    $inserts = nf_sql_inserts($db, 'nf_wiki_pages', 'ORDER BY `parent_id` IS NOT NULL, `sort_order`, `id`');

    if (str_starts_with($inserts, '-- '))
    {
        nf_refus('nf_wiki_pages est vide — lancer d\'abord le transfert (php tools/wiki-docs.php --peupler)');
    }

    $out .= $inserts."\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents(nf_racine().'/install/wiki.sql', $out);

    $pages = (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_wiki_pages');
    echo "install/wiki.sql écrit ({$pages} pages).\n";
}

// ── Démo : les pages de documentation de install/demo.sql ───────────────────
if ($o['demo'])
{
    $chemin = nf_racine().'/install/demo.sql';
    $sql    = (string) file_get_contents($chemin);

    ['colonnes' => $colonnes, 'tuples' => $tuples] = nf_sql_tuples($sql, 'nf_wiki_pages');
    ['pages' => $attendu] = nf_wiki_attendu();

    $i_slug    = array_search('slug', $colonnes, TRUE);
    $i_titre   = array_search('title', $colonnes, TRUE);
    $i_contenu = array_search('content', $colonnes, TRUE);

    if ($i_slug === FALSE || $i_titre === FALSE || $i_contenu === FALSE)
    {
        nf_refus('install/demo.sql : aucune ligne de nf_wiki_pages lisible');
    }

    $reecrites = 0;
    $vues      = [];

    // De la fin vers le début : chaque remplacement laisse intactes les positions qui le précèdent.
    foreach (array_reverse($tuples) as $tuple)
    {
        $valeurs = $tuple['valeurs'];
        $slug    = (string) $valeurs[$i_slug];

        if (!isset($attendu[$slug]))
        {
            continue;
        }

        $vues[$slug]         = TRUE;
        $valeurs[$i_titre]   = $attendu[$slug]['title'];
        $valeurs[$i_contenu] = $attendu[$slug]['content'];

        if ($valeurs !== $tuple['valeurs'])
        {
            $sql = substr_replace($sql, '('.implode(', ', array_map('nf_sql_valeur', $valeurs)).')', $tuple['debut'], $tuple['fin'] - $tuple['debut']);
            $reecrites++;
        }
    }

    foreach (array_diff_key($attendu, $vues) as $slug => $_)
    {
        nf_avertir("  ⚠ install/demo.sql n'a pas de page « {$slug} » : la régénérer par dump-demo après l'avoir créée");
    }

    file_put_contents($chemin, $sql);
    echo "install/demo.sql : {$reecrites} page(s) de documentation réécrite(s).\n";
}

nf_ok('wiki'.($o['peupler'] ? ' peuplé' : '').($o['figer'] ? ', figé dans install/wiki.sql' : '').($o['demo'] ? ', démo à jour' : ''));
