<?php
declare(strict_types=1);

/**
 * wiki-docs — transfère docs/guide/*.md dans le wiki du site de ce dossier, d'où le site officiel se recopie.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La documentation publique est écrite en Markdown dans `docs/guide/`, et lue par les visiteurs dans le wiki du site
 * officiel. Elle ne se livre plus avec le produit (2026-10-09) : le wiki d'un site neuf arrive vide, et celui de la
 * démonstration porte des pages d'exemple, comme le site d'une communauté. Jusque-là, cet outil figeait aussi le wiki
 * en `install/wiki.sql`, que l'installateur chargeait partout, et réécrivait les pages de documentation du wiki de la
 * démonstration : tout site neuf recevait la documentation du produit à la place d'un wiki à lui.
 *
 * Le contenu est converti en HTML au moment du transfert : le wiki rend via render_content(), dont
 * l'heuristique Markdown est trompée par les balises HTML présentes dans nos blocs de code.
 * Idempotent : les pages de documentation sont purgées puis réinsérées.
 *
 * Ensuite, `check-wiki-docs --site=DOSSIER` compare le wiki d'un site en service aux guides.
 *
 * Usage
 * -----
 *   php tools/wiki-docs.php    les guides dans le wiki du site de ce dossier
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/wiki.php';

// `--peupler` reste accepté : c'était le nom de ce geste quand l'outil en faisait trois.
nf_options(['peupler' => FALSE]);

$db       = nf_connexion();
$sections = nf_wiki_sections();

// Le titre et le texte d'une page vivent dans `nf_wiki_pages_lang` depuis m32 (2026-10-15). Les guides sont écrits en
// français : la ligne française est posée, puis une ligne par langue traduite (`docs/guide/<langue>/`, m19) ; une
// langue sans traduction sert le français en repli.
if (!nf_table_existe($db, 'nf_wiki_pages_lang'))
{
    nf_refus('nf_wiki_pages_lang absente : cette base n\'a pas reçu la migration 2026_10_15_wiki_par_langue du module wiki');
}

$inserer = static function (string $slug, string $title, string $content, ?int $parent, int $sort) use ($db): int {
    if ($parent === NULL)
    {
        $stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,sort_order,published) VALUES (?,?,1)');
        $stmt->bind_param('si', $slug, $sort);
    }
    else
    {
        $stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,parent_id,sort_order,published) VALUES (?,?,?,1)');
        $stmt->bind_param('sii', $slug, $parent, $sort);
    }

    $stmt->execute();
    $id = (int) $db->insert_id;
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO nf_wiki_pages_lang (page_id,lang,title,content) VALUES (?,'fr',?,?)");
    $stmt->bind_param('iss', $id, $title, $content);
    $stmt->execute();
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

// Les traductions : une ligne par langue, pour chaque page qui en a une.
$traduites = 0;
$ligne     = $db->prepare('INSERT INTO nf_wiki_pages_lang (page_id,lang,title,content) SELECT id, ?, ?, ? FROM nf_wiki_pages WHERE slug = ?');

foreach (NF_WIKI_LANGUES as $langue)
{
    foreach (nf_wiki_attendu_traduit($langue) as $slug => $page)
    {
        $ligne->bind_param('ssss', $langue, $page['title'], $page['content'], $slug);
        $ligne->execute();
        $traduites++;
    }
}

$ligne->close();

echo "\n{$count} pages de doc transférées dans le wiki, et {$traduites} traduction(s)\n";

nf_ok('wiki peuplé');
