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
 *
 * Les LANGUES (m19, 2026-10-16) : le guide français (`docs/guide/<slug>.md`) est la référence ; sa traduction vit dans
 * `docs/guide/<langue>/<slug>.md`, titrée par son premier `#`. Une page du wiki reçoit une ligne par langue traduite,
 * et le wiki sert le français en repli là où il n'y en a pas.
 */

require_once __DIR__.'/outil.php';
// Les ancres que le wiki pose sur ses titres : un renvoi de guide doit viser la même.
require_once __DIR__.'/../../modules/wiki/lib/wiki_ancres.php';

use NF\Modules\Wiki\Lib\Wiki_Ancres;

/**
 * Les sections du wiki et leurs pages, dans l'ordre d'affichage.
 *
 * @return array<string, array{title: string, intro: string, pages: array<string, string>}>
 */
/** Les langues dans lesquelles les guides se traduisent, outre le français. */
const NF_WIKI_LANGUES = ['en', 'de', 'es', 'it', 'pt'];

function nf_wiki_sections(): array
{
    return [
        'guide-utilisateur' => [
            'title' => 'Guide utilisateur',
            'intro' => "# Guide utilisateur\n\nTout pour installer et piloter ton site **NeoFrag Reborn**.",
            'traductions' => [
                'en' => ['User guide', 'Everything you need to install and run your **NeoFrag Reborn** site.'],
                'de' => ['Benutzerhandbuch', 'Alles, um deine **NeoFrag Reborn**-Seite zu installieren und zu betreiben.'],
                'es' => ['Guía del usuario', 'Todo para instalar y gestionar tu sitio **NeoFrag Reborn**.'],
                'it' => ['Guida utente', 'Tutto per installare e gestire il tuo sito **NeoFrag Reborn**.'],
                'pt' => ['Guia do utilizador', 'Tudo para instalar e gerir o teu site **NeoFrag Reborn**.'],
            ],
            'pages' => ['installation' => 'Installation', 'premiers-pas' => 'Premiers pas', 'concepts' => 'Concepts', 'admin' => 'Administration', 'marketplace' => 'Marketplace', 'multilingue' => 'Un site en plusieurs langues', 'bot-discord' => 'Le bot Discord'],
        ],
        'guide-developpeur' => [
            'title' => 'Guide développeur',
            'intro' => "# Guide développeur\n\nÉtends NeoFrag Reborn : crée tes **thèmes**, **widgets** et **modules**, maîtrise le framework, et fais parler tes programmes au site par son **API**.",
            'traductions' => [
                'en' => ['Developer guide', 'Extend NeoFrag Reborn: build your own **themes**, **widgets** and **modules**, master the framework, and let your programs talk to the site through its **API**.'],
                'de' => ['Entwicklerhandbuch', 'Erweitere NeoFrag Reborn: Baue eigene **Themes**, **Widgets** und **Module**, beherrsche das Framework und lass deine Programme über die **API** mit der Seite sprechen.'],
                'es' => ['Guía del desarrollador', 'Amplía NeoFrag Reborn: crea tus **temas**, **widgets** y **módulos**, domina el framework y haz que tus programas hablen con el sitio a través de su **API**.'],
                'it' => ['Guida per sviluppatori', 'Estendi NeoFrag Reborn: crea i tuoi **temi**, **widget** e **moduli**, padroneggia il framework e fai parlare i tuoi programmi con il sito tramite la sua **API**.'],
                'pt' => ['Guia do programador', 'Expande o NeoFrag Reborn: cria os teus **temas**, **widgets** e **módulos**, domina a framework e põe os teus programas a falar com o site através da sua **API**.'],
            ],
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
function nf_wiki_convertir(string $md, int $profondeur = 0, string $langue = ''): string
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

    // Les guides écrivent leurs ancres comme GitHub (`tables--table2`), le wiki pose les siennes (`tables-table2`) :
    // chaque renvoi vers une section prend l'ancre que le wiki met sur le titre visé, dans le guide de la même langue.
    $dossier = nf_racine().'/docs/guide'.($langue !== '' ? '/'.$langue : '');
    $ici     = nf_wiki_ancres_du_guide($md);

    // liens internes vers un autre guide : strip .md → lien relatif (résolu sous /wiki/)
    $md = (string) preg_replace_callback('/\]\(\.?\/?([a-z0-9-]+)\.md(#[^)]*)?\)/i', static function (array $m) use ($dossier): string
    {
        $fichier = "{$dossier}/{$m[1]}.md";

        return ']('.$m[1].(($m[2] ?? '') !== '' ? '#'.nf_wiki_ancre_vers(substr($m[2], 1), nf_wiki_ancres_du_fichier($fichier)) : '').')';
    }, $md);
    // renvois vers une section de la même page
    $md = (string) preg_replace_callback('/\]\(#([^)\s]+)\)/', static fn (array $m): string => '](#'.nf_wiki_ancre_vers($m[1], $ici).')', $md);
    // liens qui sortent des guides (../architecture.md, ../deploy-ftp.md#ancre, ../../tools/README.md) :
    // le wiki n'a pas ces documents ; ils mènent au dépôt public, où ils vivent (depuis l'ouverture des
    // dépôts, 1.2.24). Avant, le wiki n'en gardait que le texte.
    $md = (string) preg_replace_callback('/\[([^\]]+)\]\(((?:\.\.\/)+)([^)\s#]*)(#[^)\s]*)?\)/', static function (array $m) use ($profondeur): string
    {
        // Un guide traduit vit un dossier plus bas (`docs/guide/en/`) : ses `../` comptent un de plus.
        $dossiers = array_slice(['docs', 'guide'], 0, max(0, 2 - (substr_count($m[2], '../') - $profondeur)));

        return '['.$m[1].'](https://github.com/NeoFragReborn/neofrag/blob/main/'.implode('/', array_merge($dossiers, [$m[3]])).($m[4] ?? '').')';
    }, $md);
    // lien d'index
    $md = str_replace('](README.md)', '](.)', $md);

    return (string) $converter->convert($md);
}

/**
 * L'ancre GitHub d'un titre Markdown — celle qu'écrivent les renvois des guides, et que check-docs vérifie :
 * minuscules, ponctuation retirée sauf le tiret et le souligné, espaces en tirets.
 */
function nf_wiki_ancre_github(string $titre): string
{
    $titre = mb_strtolower(trim($titre));
    $titre = (string) preg_replace('/[`*\[\]()]/u', '', $titre);
    $titre = (string) preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $titre);

    return str_replace(' ', '-', $titre);
}

/**
 * Les titres d'un guide : l'ancre GitHub de chacun → celle que le wiki pose sur le même titre.
 *
 * @return array<string, string>
 */
function nf_wiki_ancres_du_guide(string $md): array
{
    $ancres = [];
    // Un « # » de bloc de code n'est pas un titre.
    $md = (string) preg_replace('/^```.*?^```/msu', '', $md);

    if (preg_match_all('/^#{1,6}\s+(.+?)\s*$/mu', $md, $m))
    {
        foreach ($m[1] as $titre)
        {
            // Le texte que lit le visiteur : sans le code, l'emphase ni la cible d'un lien.
            $texte = (string) preg_replace(['/\[([^\]]*)\]\([^)]*\)/u', '/[`*]/u'], ['$1', ''], $titre);
            $ancres[nf_wiki_ancre_github($titre)] = Wiki_Ancres::ancre(Wiki_Ancres::texte($texte));
        }
    }

    return $ancres;
}

/**
 * Les titres d'un guide, lu dans son fichier.
 *
 * @return array<string, string>
 */
function nf_wiki_ancres_du_fichier(string $fichier): array
{
    static $cache = [];

    return $cache[$fichier] ??= is_file($fichier) ? nf_wiki_ancres_du_guide((string) file_get_contents($fichier)) : [];
}

/**
 * L'ancre du wiki pour un renvoi de guide : celle du titre visé, ou à défaut la forme que le wiki donne à ce texte.
 *
 * @param array<string, string> $ancres
 */
function nf_wiki_ancre_vers(string $fragment, array $ancres): string
{
    $fragment = rawurldecode($fragment);

    return $ancres[$fragment] ?? Wiki_Ancres::ancre($fragment);
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

/**
 * Les pages traduites dans une langue : celles dont `docs/guide/<langue>/<slug>.md` existe, titrées par leur premier
 * `#`, et les sections dans cette langue. Une page sans traduction n'y figure pas : le wiki sert le français.
 *
 * @return array<string, array{title: string, content: string}>
 */
function nf_wiki_attendu_traduit(string $langue): array
{
    $pages = [];

    foreach (nf_wiki_sections() as $slug_section => $section)
    {
        if (isset($section['traductions'][$langue]))
        {
            [$titre, $intro] = $section['traductions'][$langue];
            $pages[$slug_section] = ['title' => $titre, 'content' => nf_wiki_convertir("# {$titre}\n\n{$intro}", 0, $langue)];
        }

        foreach (array_keys($section['pages']) as $slug)
        {
            $fichier = nf_racine()."/docs/guide/{$langue}/{$slug}.md";

            if (is_file($fichier))
            {
                $md = (string) file_get_contents($fichier);
                $pages[$slug] = ['title' => preg_match('/^#\s+(.+)$/m', $md, $t) ? trim($t[1]) : $slug, 'content' => nf_wiki_convertir($md, 1, $langue)];
            }
        }
    }

    return $pages;
}
