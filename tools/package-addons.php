<?php
declare(strict_types=1);

/**
 * package-addons — zippe chaque addon distribuable et génère le catalogue du marketplace.
 *
 * Famille : outil
 *
 * Ce qu'il fait
 * -------------
 * Lit tools/lib/addons-manifest.php, zippe chaque addon de Tier 1 (identité) et de Tier 2 (à la
 * carte) dans `marketplace/<type>/<nom>.zip`, et écrit `marketplace/catalog.json` (métadonnées,
 * empreinte SHA-256, dépendances). Le contenu de marketplace/ alimente le showcase du site vitrine :
 * navigation, fiches, téléchargement, puis installation via « Ajouter » (ZIP) de l'administration.
 *
 * Les dépendances publiées sont celles que chaque addon DÉCLARE dans son `'requires' => [...]`.
 * Le catalogue publiait `'addons' => []` en dur : il annonçait `events` comme installable seul,
 * alors que son `install.sql` porte des clés étrangères vers les tables de `games`.
 *
 * Le titre et la description sont publiés en français ET dans les cinq autres langues (`i18n`), lus
 * dans les fichiers de langue de l'addon : la place de marché du site anglais affichait « Actualités
 * du site avec catégories et commentaires. » (2026-09-23). `Installer::texte_catalogue()` choisit.
 *
 * Usage
 * -----
 *   php tools/package-addons.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/langues.php';

nf_options([]);

$root     = nf_racine();
$manifest = require __DIR__.'/lib/addons-manifest.php';
$out      = $root.'/marketplace';

$type_dir = ['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes', 'authenticator' => 'addons'];

/** Version de base du CMS (depuis index.php) — pour catalog.base_version + requires.base. */
function base_version(string $root): string
{
    if (preg_match("/NEOFRAG_VERSION',\s*'([^']+)'/", (string) @file_get_contents($root.'/index.php'), $m))
    {
        return $m[1];
    }

    return '1.0.0';
}

/**
 * Le bloc `__info()` et lui SEUL.
 *
 * Ailleurs dans le fichier d'un addon, `'title' => 'title'` désigne une COLONNE de table : la carte
 * de traduction de `news`, `gallery` et `articles` en contient une, quelques lignes AVANT leur
 * `__info()`. Les expressions régulières ci-dessous prenant le premier match, ces trois modules
 * s'appelaient « title » sur la place de marché — et « requires » pouvait se lire au mauvais
 * endroit de la même façon. Signalé le 2026-09-22.
 */
function bloc_info(string $src): string
{
    if (($debut = strpos($src, 'function __info')) === FALSE)
    {
        return $src;
    }

    $bloc = substr($src, $debut);

    // La déclaration de méthode SUIVANTE termine le bloc.
    if (preg_match('/\n\t+(?:public|protected|private|static|final|abstract)[^\n]*function\s/', $bloc, $m, PREG_OFFSET_CAPTURE))
    {
        $bloc = substr($bloc, 0, (int) $m[0][1]);
    }

    return $bloc;
}

/** Dépendances déclarées par l'addon, lues dans son `'requires' => [...]` — bloc __info() seul. */
function requires_addons(string $src): array
{
    if (!preg_match('/[\'"]requires[\'"]\s*=>\s*\[([^\]]*)\]/s', $src, $m))
    {
        return [];
    }

    preg_match_all('/[\'"]([a-z0-9_]+)[\'"]/i', $m[1], $noms);

    return array_values(array_unique($noms[1] ?? []));
}

/**
 * Les traductions du titre et de la description d'un addon, lues dans SES fichiers de langue, puis
 * dans ceux du cœur (le recours de `lang()`). Une langue sans traduction n'apparaît pas : le
 * catalogue retombe alors sur le français.
 *
 * @param  array<string, string> $champs  champ => texte français
 * @return array<string, array<string, string>>  langue => champ => texte
 */
function traductions(string $root, string $dossier, array $champs): array
{
    $i18n = [];

    foreach (array_diff(NF_LANGUES, ['fr']) as $code)
    {
        $connues = nf_langue_valeurs($dossier.'/langs/'.$code.'.php') + nf_langue_valeurs($root.'/neofrag/langs/'.$code.'.php');

        foreach ($champs as $champ => $francais)
        {
            if ($francais !== '' && isset($connues[$cle = nf_langue_cle($francais)]))
            {
                $i18n[$code][$champ] = $connues[$cle];
            }
        }
    }

    return $i18n;
}

/** Extrait un champ du tableau __info() (gère 'x' => 'literal' ET 'x' => $this->lang('literal')). */
function info_field(string $src, string $field): ?string
{
    if (preg_match('/[\'"]'.preg_quote($field, '/').'[\'"]\s*=>\s*(?:\$this->lang\(\s*)?\'((?:\\\\.|[^\'\\\\])*)\'/s', $src, $m))
    {
        return stripcslashes($m[1]);
    }

    return NULL;
}

/**
 * Ce qu'une archive n'emporte PAS.
 *
 * Les cartes de source (`*.map`) sont produites par le compilateur SCSS à l'exécution, sur chaque
 * installation. Elles ne sont pas versionnées — `.gitignore` les écarte — mais elles se trouvaient
 * dans le dossier au moment du packaging, et partaient donc dans l'archive. Conséquence : deux
 * archives du même addon, bâties sur deux machines, différaient sans que le code ait bougé, et
 * `check-marketplace` signalait à juste titre une archive « périmée » sur une installation servie.
 *
 * Une archive doit contenir ce que le DÉPÔT contient, rien d'autre.
 */
const EXCLUS = ['map'];

/**
 * Zippe un dossier d'addon sous la racine <name>/ (structure attendue par l'install ZIP).
 *
 * Pourquoi les dates des fichiers NE SONT PAS figées dans l'archive. L'empreinte d'une archive
 * change dès qu'un fichier est simplement touché, sans que son contenu bouge : figer la date de
 * chaque entrée rendrait le SHA-256 reproductible, et cela a été essayé le 2026-09-22. Mais
 * `ZipArchive::extractTo()` — celui de « Ajouter » et de l'installeur — RECOPIE la date de l'entrée
 * sur le fichier extrait, et le produit invalide le cache des navigateurs par `?v=<date du
 * fichier>` : toutes les versions d'un addon installé par ZIP auraient porté la même adresse, et un
 * visiteur aurait gardé l'ancienne feuille de style après une mise à jour. L'empreinte qui bouge
 * est un bruit ; `check-marketplace` juge le CONTENU, fichier par fichier, sur les CRC-32.
 */
function zip_addon(string $folder, string $name, string $zip_path): bool
{
    @unlink($zip_path);
    $zip = new ZipArchive();

    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE)
    {
        return FALSE;
    }

    foreach (nf_parcourir($folder) as $rel => $file)
    {
        if (in_array(strtolower($file->getExtension()), EXCLUS, TRUE))
        {
            continue;
        }

        $zip->addFile($file->getPathname(), $name.'/'.$rel);
    }

    $zip->close();

    return TRUE;
}

$all_widgets = array_merge($manifest['identity']['widget'] ?? [], $manifest['optional']['widget'] ?? []);

/** Tier d'un addon : 1 (identité, livré dans le paquet) / 2 (à la carte, marketplace seul). */
$tier_of = static fn (string $type, string $name): int => in_array($name, $manifest['identity'][$type] ?? [], TRUE) ? 1 : 2;

/** Catégorie d'affichage (groupes de l'UI marketplace). */
$category_of = static function (string $type, string $name) use ($manifest): string {
    if ($type === 'theme')
    {
        return 'theme';
    }

    if (in_array($name, ['shop', 'donations', 'payments', 'ads', 'newsletter'], TRUE))
    {
        return 'monetisation';
    }

    if (in_array($name, $manifest['identity']['module'] ?? [], TRUE) || in_array($name, $manifest['identity']['widget'] ?? [], TRUE))
    {
        return 'identite';
    }

    return 'contenu';
};

/**
 * Widgets fournis par un module : le widget de même nom s'il est packagé (apparié module↔widget).
 * Les appariements spéciaux Tier 1 (teams→about) sont gérés par les presets de l'installeur.
 */
$provides_of = static fn (string $type, string $name): array => ($type === 'module' && in_array($name, $all_widgets, TRUE)) ? [$name] : [];

if (!is_dir($out))
{
    mkdir($out, 0775, TRUE);
}

// Addons packagés = Tier 1 ∪ Tier 2, regroupés par type. Tier 0 jamais packagé.
$packageable = [];

foreach (['identity', 'optional'] as $tier)
{
    foreach ($manifest[$tier] ?? [] as $type => $names)
    {
        $packageable[$type] = array_merge($packageable[$type] ?? [], $names);
    }
}

$catalog = [];
$count   = 0;

foreach ($packageable as $type => $names)
{
    if (!($dir = $type_dir[$type] ?? NULL))
    {
        continue;
    }

    if (!is_dir("$out/$dir"))
    {
        mkdir("$out/$dir", 0775, TRUE);
    }

    foreach ($names as $name)
    {
        $folder = "$root/$dir/$name";
        $main   = "$folder/$name.php";

        if (!is_dir($folder) || !is_file($main))
        {
            nf_avertir("  skip $type:$name (dossier ou fichier principal introuvable)");
            continue;
        }

        $src      = (string) file_get_contents($main);
        $info     = bloc_info($src);
        $version  = info_field($info, 'version') ?? '1.0';
        $zip_path = "$out/$dir/$name.zip";

        if (!zip_addon($folder, $name, $zip_path))
        {
            nf_avertir("  zip KO pour $name");
            continue;
        }

        $size = (int) filesize($zip_path);

        // L'APERÇU. Chaque addon porte sa vignette dans `images/thumbnail.jpg` — une capture réelle,
        // produite par `tools/capturer-apercus.php`. On la copie à côté du zip pour que la place de
        // marché la serve sans ouvrir l'archive. Sans vignette, pas de clé `preview` : la fiche
        // n'affiche alors aucune bande, ce qui vaut mieux qu'une bande vide.
        $apercu = '';

        if (is_file($vignette = "$folder/images/thumbnail.jpg") && @copy($vignette, "$out/$dir/$name.jpg"))
        {
            $apercu = "$dir/$name.jpg";
        }

        $catalog[] = [
            'type'             => $type,
            'name'             => $name,
            'tier'             => $tier_of($type, $name),
            'category'         => $category_of($type, $name),
            'title'            => $titre = info_field($info, 'title') ?? ucfirst($name),
            'description'      => $description = info_field($info, 'description') ?? '',
            'i18n'             => traductions($root, $folder, ['title' => $titre, 'description' => $description]),
            'version'          => $version,
            'author'           => info_field($info, 'author') ?? 'NeoFrag Reborn',
            'license'          => info_field($info, 'license') ?? '',
            'file'             => "$dir/$name.zip",
            'preview'          => $apercu,
            'size'             => $size,
            'sha256'           => hash_file('sha256', $zip_path),
            'requires'         => ['base' => '>='.base_version($root), 'addons' => requires_addons($info)],
            'provides_widgets' => $provides_of($type, $name),
            // L'install ZIP ne gère pas les authenticators → install par scan disque.
            'install'          => $type === 'authenticator' ? 'scan' : 'zip',
        ];
        $count++;
        printf("  packed %-13s %-22s v%-6s %5d Ko\n", $type, $name, $version, (int) round($size / 1024));
    }
}

usort($catalog, static fn (array $a, array $b): int => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);

file_put_contents("$out/catalog.json", json_encode([
    'schema'       => 1,
    'project'      => 'NeoFrag Reborn',
    'base_version' => base_version($root),
    'generated_at' => gmdate('c'),
    'count'        => $count,
    'addons'       => $catalog,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

nf_ok("$count addons packagés → marketplace/catalog.json (schema 1, base ".base_version($root).')');
