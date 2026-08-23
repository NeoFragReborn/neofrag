<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
    http_response_code(404);
    exit;
}

/**
 * Garde anti-dérive : les inventaires annoncés dans la doc doivent correspondre au code.
 *
 * Motivation : `docs/README.md` a annoncé « 53 modules · 6 thèmes » pendant des semaines alors que
 * le code en portait 54 et 7 — sur une ligne qui se présentait comme « vérifiée contre le code
 * réel ». `components.md` et `architecture.md`, mis à jour cinq jours plus tard, étaient justes.
 * C'est une dérive d'index, invisible en relecture. Ce script la rend impossible.
 *
 * Même esprit que tools/check-strict-types.php : bon marché, câblé au job `lint` de la CI.
 *
 * CE QUI EST VÉRIFIÉ — uniquement deux formes, pour ne jamais toucher à de la prose :
 *   1. les titres markdown d'inventaire  → `## 54 modules (par domaine)`
 *   2. les chaînes séparées par « · »    → `**54 modules · 39 widgets · 7 thèmes**`
 *
 * Tout le reste est ignoré délibérément. Une phrase comme « statistics (19 modules) » ou
 * « 2 modules couplés-mais-distincts » parle d'un sous-ensemble, pas de l'inventaire : la
 * contrôler produirait des faux positifs et le garde-fou finirait par être désactivé.
 *
 * Usage : php tools/check-docs-counts.php
 */

$root = dirname(__DIR__);

/** Règle du chargeur : un addon valide est un dossier `x/` contenant `x.php`. */
$count_addons = static function (string $dir): int {
    if (!is_dir($dir)) {
        return 0;
    }
    $n = 0;
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (is_dir("{$dir}/{$entry}") && is_file("{$dir}/{$entry}/{$entry}.php")) {
            $n++;
        }
    }
    return $n;
};

/** Paquets distribuables : les zips régénérés par tools/package-addons.php. */
$count_zips = static function (string $dir): int {
    if (!is_dir($dir)) {
        return 0;
    }
    $n  = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (strtolower($f->getExtension()) === 'zip') {
            $n++;
        }
    }
    return $n;
};

$expected = [
    'modules'              => $count_addons($root . '/modules'),
    'widgets'              => $count_addons($root . '/widgets'),
    'themes'               => $count_addons($root . '/themes'),
    'addons'               => $count_addons($root . '/addons'),
    'addons distribuables' => $count_zips($root . '/marketplace'),
];

// L'ordre compte : « addons distribuables » doit être tenté AVANT « addons », sinon la seconde
// alternative capturerait le préfixe et comparerait 52 zips à 10 dossiers.
$labels = [
    'addons distribuables' => 'addons?\s+distribuables?',
    'modules'              => 'modules?',
    'widgets'              => 'widgets?',
    'themes'               => 'th[èe]mes?',
    'addons'               => 'addons?',
];
$alternation = implode('|', $labels);
$label_of    = static function (string $found) use ($labels): string {
    foreach ($labels as $key => $pattern) {
        if (preg_match('/^' . $pattern . '$/ui', $found)) {
            return $key;
        }
    }
    return '';
};

$docs = array_merge(
    glob($root . '/docs/*.md') ?: [],
    [$root . '/README.md']
);

$errors  = [];
$checked = 0;

foreach ($docs as $doc) {
    if (!is_file($doc)) {
        continue;
    }
    $rel   = str_replace($root . DIRECTORY_SEPARATOR, '', str_replace('/', DIRECTORY_SEPARATOR, $doc));
    $lines = file($doc, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $i => $line) {
        $is_heading = (bool) preg_match('/^#{1,6}\s/u', $line);
        $is_chain   = str_contains($line, '·');

        // Seules ces deux formes énoncent un inventaire. Le reste est de la prose.
        if (!$is_heading && !$is_chain) {
            continue;
        }

        if (!preg_match_all('/(\d+)\s+(' . $alternation . ')/ui', $line, $m, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($m as $match) {
            $key = $label_of($match[2]);
            if ($key === '' || !isset($expected[$key])) {
                continue;
            }

            $checked++;
            $found = (int) $match[1];

            if ($found !== $expected[$key]) {
                $errors[] = sprintf(
                    "%s:%d — annonce %d %s, le code en compte %d\n      > %s",
                    $rel,
                    $i + 1,
                    $found,
                    $key,
                    $expected[$key],
                    trim($line)
                );
            }
        }
    }
}

echo "Inventaire réel : ";
foreach ($expected as $k => $v) {
    echo "{$v} {$k}  ";
}
echo "\n";

if ($errors !== []) {
    fwrite(STDERR, "\nDérive entre la documentation et le code :\n\n");
    foreach ($errors as $e) {
        fwrite(STDERR, "  - {$e}\n\n");
    }
    fwrite(STDERR, "Corriger les chiffres ci-dessus (ou le code, si c'est lui qui a changé par erreur).\n");
    exit(1);
}

echo "check-docs-counts OK : {$checked} chiffres d'inventaire vérifiés, aucun écart.\n";
