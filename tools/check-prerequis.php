<?php
declare(strict_types=1);

/**
 * check-prerequis — les prérequis annoncés sont ceux que l'installation exige, écrits à un seul endroit.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-10-04, cinq listes des prérequis vivaient chacune à sa manière : l'assistant web exigeait
 * six extensions, la ligne de commande deux, le Monitoring cinq (dont `json`, qui ne peut plus
 * manquer), `composer.json` huit, et le guide d'installation six — sans `openssl`, dont le chiffrement
 * a besoin (erreur fatale sans lui), ni `fileinfo`, sans lequel tout envoi de fichier est refusé, ni
 * `iconv` (le QR code de la double authentification), ni Argon2 (le hachage des mots de passe). Le
 * code lit désormais une seule liste, `Installer::PREREQUIS` ; les documents ne se compilent pas, ce
 * contrôle les y confronte.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. `composer.json` : `require.php` vaut `>=` la version minimale, `config.platform.php` cette
 *      version exacte, et ses `ext-*` sont exactement les extensions de `PREREQUIS` ;
 *   2. le guide d'installation, seul document qui liste les extensions, les liste toutes et rien
 *      d'autre, nomme Argon2, et annonce la plage de PHP éprouvée (« PHP 8.2 à 8.5 ») — dans chacune
 *      de ses langues (`docs/guide/<langue>/installation.md`, m19) : une traduction est ce guide ;
 *   3. aucun autre document ne recopie la liste — trois extensions ou plus sur une ligne : il renvoie
 *      au guide ;
 *   4. toute version de PHP annoncée (`PHP 8.2+`, `PHP ≥ 8.2`, `PHP 8.2 à 8.5`) est la bonne ;
 *   5. l'assistant web, la ligne de commande, le Monitoring et le tableau de bord lisent
 *      `Installer::prerequis()`, et l'assistant n'a plus de liste à lui.
 *
 * Usage
 * -----
 *   php tools/check-prerequis.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require_once nf_racine().'/neofrag/installer.php';

nf_options([]);

use NF\NeoFrag\Installer;

$racine     = nf_racine();
$prerequis  = Installer::PREREQUIS;
$extensions = $prerequis['extensions'];
$minimum    = Installer::php_minimum();
$eprouve    = $prerequis['php_eprouve'];
$fautes     = [];

/** Les noms d'extensions qu'un document pourrait citer : celles du produit, et les courantes. */
$connues = array_merge($extensions, ['json', 'pdo', 'pdo_mysql', 'xml', 'dom', 'ctype', 'sodium', 'imagick', 'exif',
    'bcmath', 'soap', 'xmlwriter', 'simplexml', 'tokenizer', 'session', 'filter', 'hash', 'opcache', 'apcu']);

// ── 1. composer.json ──────────────────────────────────────────────────────────────────────────
$composer = json_decode((string) @file_get_contents($racine.'/composer.json'), TRUE);

if (!is_array($composer))
{
    nf_refus('composer.json est illisible');
}

if (($composer['require']['php'] ?? '') !== '>='.$minimum)
{
    $fautes[] = ['composer.json', 0, sprintf('require.php vaut « %s », attendu « >=%s »', $composer['require']['php'] ?? '', $minimum)];
}

if (($composer['config']['platform']['php'] ?? '') !== $prerequis['php'])
{
    $fautes[] = ['composer.json', 0, sprintf('config.platform.php vaut « %s », attendu « %s »', $composer['config']['platform']['php'] ?? '', $prerequis['php'])];
}

$ext_composer = array_map(static fn (string $k): string => substr($k, 4), array_values(array_filter(array_keys($composer['require'] ?? []), static fn (string $k): bool => str_starts_with($k, 'ext-'))));

foreach (array_diff($extensions, $ext_composer) as $manque)
{
    $fautes[] = ['composer.json', 0, "ext-{$manque} manque à require, alors que l'installation l'exige"];
}

foreach (array_diff($ext_composer, $extensions) as $trop)
{
    $fautes[] = ['composer.json', 0, "ext-{$trop} est exigée par composer.json, pas par l'installation (Installer::PREREQUIS)"];
}

// ── 2 à 4. Les documents ──────────────────────────────────────────────────────────────────────
$guide     = 'docs/guide/installation.md';
// Le guide traduit (m19) est le même guide : il liste les prérequis, comme lui.
$guides    = array_merge([$guide], array_map('nf_relatif', glob($racine.'/docs/guide/*/installation.md') ?: []));
// « PHP 8.2 à 8.5 », « to », « bis », « a », « da 8.2 a 8.5 » : la plage dans chaque langue des guides.
$re_plage  = '/PHP\s+(?:da\s+)?(\d+\.\d+)\s+(?:à|to|bis|a)\s+(\d+\.\d+)/u';
$documents = array_filter(array_merge(['README.md', 'ROADMAP.md', 'config/README.md', 'tools/README.md'],
    array_map('nf_relatif', array_values(nf_fichiers(['.github', 'docs'], ['md'])))),
    static fn (string $d): bool => is_file($racine.'/'.$d) && !str_starts_with($d, 'docs/internal/'));

/** Les extensions citées entre accents graves sur une ligne. */
$citees = static function (string $ligne) use ($connues): array {
    preg_match_all('/`([a-z_0-9]+)`/', $ligne, $m);

    return array_values(array_unique(array_intersect($m[1], $connues)));
};

$dans_le_guide = array_fill_keys($guides, []);
$argon2        = array_fill_keys($guides, FALSE);
$plage         = array_fill_keys($guides, FALSE);

foreach ($documents as $document)
{
    $lignes = file($racine.'/'.$document, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lignes as $i => $ligne)
    {
        $n = $i + 1;

        // 4. Les versions de PHP annoncées.
        if (preg_match_all('/PHP\s*(?:≥|>=)\s*(\d+\.\d+)|PHP\s+(\d+\.\d+)\+/u', $ligne, $m, PREG_SET_ORDER))
        {
            foreach ($m as $v)
            {
                $version = ($v[1] ?? '') !== '' ? $v[1] : $v[2];

                if ($version !== $minimum)
                {
                    $fautes[] = [$document, $n, "annonce PHP {$version} comme minimum, l'installation exige {$minimum}"];
                }
            }
        }

        if (preg_match_all($re_plage, $ligne, $m, PREG_SET_ORDER))
        {
            foreach ($m as $v)
            {
                if ($v[1] !== $minimum || $v[2] !== $eprouve)
                {
                    $fautes[] = [$document, $n, "annonce PHP {$v[1]} à {$v[2]}, la plage éprouvée est {$minimum} à {$eprouve}"];
                }
            }
        }

        $ici = $citees($ligne);

        if (isset($dans_le_guide[$document]))
        {
            $dans_le_guide[$document] = array_merge($dans_le_guide[$document], $ici);
            $argon2[$document]        = $argon2[$document] || str_contains($ligne, 'Argon2');
            $plage[$document]         = $plage[$document] || (preg_match($re_plage, $ligne, $v) === 1 && $v[1] === $minimum && $v[2] === $eprouve);
        }
        else if (count($ici) >= 3)
        {
            // 3. Une liste recopiée vieillit seule : on renvoie au guide.
            $fautes[] = [$document, $n, 'recopie la liste des extensions ('.implode(', ', $ici).") : renvoyer au guide d'installation"];
        }
    }
}

// 2. Le guide d'installation, seule liste — dans chacune de ses langues.
foreach ($guides as $g)
{
    foreach (array_diff($extensions, $dans_le_guide[$g]) as $manque)
    {
        $fautes[] = [$g, 0, "ne cite pas l'extension `{$manque}`, que l'installation exige"];
    }

    foreach (array_diff(array_unique($dans_le_guide[$g]), $extensions) as $trop)
    {
        $fautes[] = [$g, 0, "cite l'extension `{$trop}`, que l'installation n'exige pas"];
    }

    if (!$argon2[$g])
    {
        $fautes[] = [$g, 0, 'ne dit pas que PHP doit savoir hacher les mots de passe en Argon2'];
    }

    if (!$plage[$g])
    {
        $fautes[] = [$g, 0, "n'annonce pas la plage de PHP éprouvée : « PHP {$minimum} à {$eprouve} »"];
    }
}

// ── 5. Le code lit la liste ───────────────────────────────────────────────────────────────────
$lecteurs = [
    'install/steps/requirements.php'          => 'Installer::prerequis()',
    'install/cli.php'                         => 'Installer::prerequis()',
    'modules/monitoring/models/monitoring.php' => 'Installer::prerequis()',
    'modules/admin/controllers/admin.php'     => 'Installer::prerequis()',
];

foreach ($lecteurs as $fichier => $appel)
{
    $source = nf_sans_commentaires((string) @file_get_contents($racine.'/'.$fichier));

    if (!str_contains($source, $appel))
    {
        $fautes[] = [$fichier, 0, "ne lit pas {$appel} : sa liste divergera"];
    }
}

if (str_contains(nf_sans_commentaires((string) @file_get_contents($racine.'/install/steps/requirements.php')), 'extension_loaded('))
{
    $fautes[] = ['install/steps/requirements.php', 0, 'vérifie une extension à la main, hors de Installer::PREREQUIS'];
}

// ── Le verdict ────────────────────────────────────────────────────────────────────────────────
printf("Prérequis : PHP %s à %s · %s · Argon2.\n", $minimum, $eprouve, implode(', ', $extensions));

if (!$fautes)
{
    nf_ok(sprintf('composer.json, %d document(s) et le code annoncent les mêmes prérequis', count($documents)));
}

echo "\n";

foreach ($fautes as [$fichier, $ligne, $quoi])
{
    printf("  ✗ %s%s — %s\n", $fichier, $ligne ? ':'.$ligne : '', $quoi);
}

nf_echec(count($fautes).' écart(s) entre les prérequis exigés et ceux annoncés');
