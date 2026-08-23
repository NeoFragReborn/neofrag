<?php
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}

/**
 * Ratchet anti-régression : le nombre de fichiers PHP avec declare(strict_types=1) ne doit jamais
 * baisser (chunk 2 de la modernisation — cf. les notes du mainteneur). À chaque conversion d'un fichier,
 * bumper $min ci-dessous. Lancé en CI (job Lint).
 */
$min  = 58;
$dirs = ['neofrag', 'modules', 'widgets', 'addons'];

$count = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        if (strpos(file_get_contents($file->getPathname()), 'declare(strict_types=1)') !== false) {
            $count++;
        }
    }
}

if ($count < $min) {
    fwrite(STDERR, "Regression strict_types : $count fichiers < minimum $min.\n");
    fwrite(STDERR, "Ne jamais retirer declare(strict_types=1) ; si ajout, bumper \$min dans tools/check-strict-types.php.\n");
    exit(1);
}

echo "strict_types OK : $count fichiers (minimum requis $min).\n";
