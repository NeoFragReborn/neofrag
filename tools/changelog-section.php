<?php
declare(strict_types=1);
/**
 * changelog-section — extrait une section de CHANGELOG.md, la source unique des notes de version.
 *
 * Famille : outil
 *
 * Pourquoi
 * --------
 * Donner une SOURCE DE VÉRITÉ UNIQUE aux notes de version. La release GitHub (workflow release.yml)
 * et la publication sur le site (tools/un outil interne.php) passent toutes deux par ce fichier,
 * donc leur texte ne peut pas diverger de CHANGELOG.md. Inclus sans être le script d'entrée
 * (require), il se contente de DÉFINIR ses fonctions, sans auto-exécution.
 *
 * Usage
 * -----
 *   php tools/changelog-section.php [<sélecteur>] [--html] [--with-title]
 *
 *   <sélecteur> : `1.1.0` numéro exact · `latest` la première section versionnée (défaut) ·
 *                 `unreleased` la section « Non publié »
 *   --html        convertit le Markdown en HTML via le helper du projet (nécessite vendor/)
 *   --with-title  préfixe la ligne de titre « ## [X.Y.Z] — … »
 *
 * Sortie : la section sur STDOUT. Codes retour : 0 OK, 2 mauvais usage, 3 sélecteur introuvable,
 * 4 --html demandé sans vendor/.
 */

require_once __DIR__.'/lib/outil.php';

const NF_CHANGELOG = __DIR__ . '/../CHANGELOG.md';

// Auto-exécution uniquement quand ce fichier est LE script lancé (pas quand il est require()).
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__))
{
    exit(changelog_section_main($argv));
}

function changelog_section_main(array $argv): int
{
    $selector   = 'latest';
    $html       = false;
    $with_title = false;
    $got_sel    = false;

    foreach (array_slice($argv, 1) as $a)
    {
        if ($a === '--html')
        {
            $html = true;
        }
        else if ($a === '--with-title')
        {
            $with_title = true;
        }
        else if ($a === '-h' || $a === '--help')
        {
            fwrite(STDOUT, changelog_section_usage());
            return 0;
        }
        else if (str_starts_with($a, '--'))
        {
            fwrite(STDERR, "Option inconnue : {$a}\n" . changelog_section_usage());
            return 2;
        }
        else if (!$got_sel)
        {
            $selector = $a;
            $got_sel  = true;
        }
        else
        {
            fwrite(STDERR, "Argument en trop : {$a}\n" . changelog_section_usage());
            return 2;
        }
    }

    if (!is_file(NF_CHANGELOG))
    {
        fwrite(STDERR, "CHANGELOG.md introuvable (" . NF_CHANGELOG . ").\n");
        return 3;
    }

    $section = changelog_section($selector, (string) file_get_contents(NF_CHANGELOG));

    if ($section === null)
    {
        fwrite(STDERR, "Section introuvable pour le sélecteur « {$selector} ».\n");
        return 3;
    }

    $out = $with_title ? $section['title'] . "\n\n" . $section['body'] : $section['body'];

    if ($html)
    {
        $out = changelog_section_to_html($out);

        if ($out === null)
        {
            fwrite(STDERR, "--html indisponible : vendor/ absent. Lancez composer install.\n");
            return 4;
        }
    }

    fwrite(STDOUT, rtrim($out) . "\n");
    return 0;
}

/**
 * Extrait une section du contenu de CHANGELOG.md.
 *
 * @return array{version:string,title:string,body:string}|null
 */
function changelog_section(string $selector, string $changelog): ?array
{
    $lines = preg_split('/\r\n|\r|\n/', $changelog) ?: [];

    // Repérer tous les en-têtes de section « ## [<id>] … » (id = version ou « Non publié »).
    $headers = [];
    foreach ($lines as $i => $line)
    {
        if (preg_match('/^##\s+\[([^\]]+)\]/', $line, $m))
        {
            $headers[] = ['idx' => $i, 'id' => trim($m[1]), 'line' => rtrim($line)];
        }
    }

    if (!$headers)
    {
        return null;
    }

    $pick = null;
    foreach ($headers as $k => $h)
    {
        if (changelog_section_matches($selector, $h['id']))
        {
            $pick = $k;
            break;
        }
    }

    if ($pick === null)
    {
        return null;
    }

    $start = $headers[$pick]['idx'] + 1;
    $end   = $headers[$pick + 1]['idx'] ?? count($lines);
    $body  = changelog_section_trim(array_slice($lines, $start, $end - $start));

    return [
        'version' => $headers[$pick]['id'],
        'title'   => $headers[$pick]['line'],
        'body'    => $body,
    ];
}

/** Le sélecteur désigne-t-il la section dont l'id (contenu des crochets) est $id ? */
function changelog_section_matches(string $selector, string $id): bool
{
    $sel = strtolower(trim($selector));

    if ($sel === 'unreleased' || $sel === 'non-publie' || $sel === 'non publié' || $sel === 'non-publié')
    {
        // « Non publié », insensible aux accents/casse.
        return str_starts_with(changelog_section_ascii(strtolower($id)), 'non publ');
    }

    if ($sel === 'latest')
    {
        // Première section réellement versionnée (ignore « Non publié »).
        return (bool) preg_match('/^\d+\.\d+\.\d+/', $id);
    }

    // Numéro exact : le contenu des crochets EST la version pour une section publiée.
    return strtolower($id) === $sel;
}

/** Retire les lignes vides et les séparateurs « --- » en tête et en queue de section. */
function changelog_section_trim(array $lines): string
{
    $is_boundary = static fn(string $l): bool => trim($l) === '' || (bool) preg_match('/^-{3,}\s*$/', trim($l));

    while ($lines && $is_boundary($lines[0]))
    {
        array_shift($lines);
    }
    while ($lines && $is_boundary(end($lines)))
    {
        array_pop($lines);
    }

    return implode("\n", $lines);
}

/** Translittère grossièrement en ASCII pour comparer « publié » ≈ « publie ». */
function changelog_section_ascii(string $s): string
{
    return strtr($s, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'ç' => 'c']);
}

/** Convertit une section Markdown en HTML (GFM) via le helper du projet, ou null si vendor/ absent. */
function changelog_section_to_html(string $markdown): ?string
{
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!is_file($autoload))
    {
        return null;
    }

    require_once $autoload;
    require_once __DIR__ . '/../neofrag/helpers/markdown.php';

    return markdown_to_html($markdown);
}

function changelog_section_usage(): string
{
    return "Usage : php tools/changelog-section.php [<version>|latest|unreleased] [--html] [--with-title]\n";
}
