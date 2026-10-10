<?php
declare(strict_types=1);

/**
 * wiki — la documentation publique : des guides Markdown (`docs/guide/`) aux pages du module wiki.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le même texte vit à deux endroits : le guide, qu'on écrit, et le wiki du site officiel, où on le lit.
 * Il vivait aussi dans le produit — `install/wiki.sql`, chargé à chaque installation, et le wiki de la
 * démonstration —, régénérés à la main sans que rien ne dise quand : le 2026-09-23, la page « Créer un
 * widget » livrée enseignait encore `form-group`, une classe de Bootstrap 4. Depuis le 2026-10-09, la
 * documentation ne se livre plus : le wiki d'un site neuf arrive vide.
 *
 * Ce fichier dit, une seule fois, quelles pages existent et comment un guide devient une page :
 * `wiki-docs` s'en sert pour écrire, `check-wiki-docs` pour vérifier — et pour refuser un fichier livré
 * qui porterait l'une de ces pages.
 */

require_once __DIR__.'/outil.php';

/**
 * Les sections du wiki et leurs pages, dans l'ordre d'affichage.
 *
 * @return array<string, array{title: string, intro: string, pages: array<string, string>}>
 */
function nf_wiki_sections(): array
{
    return [
        'guide-utilisateur' => [
            'title' => 'Guide utilisateur',
            'intro' => "# Guide utilisateur\n\nTout pour installer et piloter ton site **NeoFrag Reborn**.",
            'pages' => ['installation' => 'Installation', 'concepts' => 'Concepts', 'admin' => 'Administration', 'marketplace' => 'Marketplace', 'bot-discord' => 'Le bot Discord'],
        ],
        'guide-developpeur' => [
            'title' => 'Guide développeur',
            'intro' => "# Guide développeur\n\nÉtends NeoFrag Reborn : crée tes **thèmes**, **widgets** et **modules**, maîtrise le framework, et fais parler tes programmes au site par son **API**.",
            'pages' => ['create-a-theme' => 'Créer un thème', 'create-a-widget' => 'Créer un widget', 'create-a-module' => 'Créer un module', 'framework' => 'Le framework', 'api' => 'L’API REST'],
        ],
    ];
}

/**
 * Un guide Markdown en HTML de page wiki : liens adaptés à /wiki/, puis conversion.
 *
 * Le contenu est converti au moment du transfert : le wiki rend via render_content(), dont
 * l'heuristique Markdown est trompée par les balises HTML présentes dans nos blocs de code.
 */
function nf_wiki_convertir(string $md): string
{
    static $converter = NULL;

    if ($converter === NULL)
    {
        require_once nf_racine().'/vendor/autoload.php';
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input'         => 'escape',
            'allow_unsafe_links' => FALSE,
            'max_nesting_level'  => 20,
        ]);
    }

    // liens internes vers un autre guide : strip .md → lien relatif (résolu sous /wiki/)
    $md = (string) preg_replace('/\]\(\.?\/?([a-z0-9-]+)\.md(#[^)]*)?\)/i', ']($1$2)', $md);
    // liens qui sortent des guides (../architecture.md, ../deploy-ftp.md#ancre, ../../tools/README.md) :
    // le wiki n'a pas ces documents ; ils mènent au dépôt public, où ils vivent (depuis l'ouverture des
    // dépôts, 1.2.24). Avant, le wiki n'en gardait que le texte.
    $md = (string) preg_replace_callback('/\[([^\]]+)\]\(((?:\.\.\/)+)([^)\s#]*)(#[^)\s]*)?\)/', static function (array $m): string
    {
        $dossiers = array_slice(['docs', 'guide'], 0, max(0, 2 - substr_count($m[2], '../')));

        return '['.$m[1].'](https://github.com/NeoFragReborn/neofrag/blob/main/'.implode('/', array_merge($dossiers, [$m[3]])).($m[4] ?? '').')';
    }, $md);
    // lien d'index
    $md = str_replace('](README.md)', '](.)', $md);

    return (string) $converter->convert($md);
}

/**
 * Chaque page de documentation telle qu'elle DOIT être : titre et contenu HTML, par slug. Un guide
 * absent est rendu sous la clé `manquants`, pour que l'appelant le dise.
 *
 * @return array{pages: array<string, array{title: string, content: string}>, manquants: list<string>}
 */
function nf_wiki_attendu(): array
{
    $pages     = [];
    $manquants = [];

    foreach (nf_wiki_sections() as $slug_section => $section)
    {
        $pages[$slug_section] = ['title' => $section['title'], 'content' => nf_wiki_convertir($section['intro'])];

        foreach ($section['pages'] as $slug => $titre)
        {
            $fichier = nf_racine()."/docs/guide/{$slug}.md";

            if (!is_file($fichier))
            {
                $manquants[] = "docs/guide/{$slug}.md";
                continue;
            }

            $pages[$slug] = ['title' => $titre, 'content' => nf_wiki_convertir((string) file_get_contents($fichier))];
        }
    }

    return ['pages' => $pages, 'manquants' => $manquants];
}
