<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

// Autoloader NF\* (même résolution que index.php:152-166) — permet aux tests unitaires de
// charger les classes PURES du framework (ex. modules/forum/lib/*) sans bootstrapper le locator.
// N'inclut une classe que si son fichier existe (les classes locator-couplées ne sont pas testées ici).
spl_autoload_register(function (string $name): void {
    $namespace = explode('\\', $name);
    if (array_shift($namespace) !== 'NF' || !$namespace) {
        return;
    }
    array_walk($namespace, static function (&$a): void {
        $a = strtolower(rtrim($a, '_'));
    });
    $file = __DIR__ . '/../' . implode('/', $namespace) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Helpers globaux sous test : fonctions pures, chargées sans bootstrapper le
// framework (pas de singleton NeoFrag()). C'est précisément parce que ces
// helpers n'ont pas de dépendance au service locator qu'ils sont testables.
require_once __DIR__ . '/../neofrag/helpers/string.php';
require_once __DIR__ . '/../neofrag/helpers/array.php';
require_once __DIR__ . '/../neofrag/helpers/file.php';
require_once __DIR__ . '/../neofrag/helpers/assets.php';
require_once __DIR__ . '/../neofrag/helpers/color.php';
require_once __DIR__ . '/../neofrag/helpers/sanitize.php';

// Socle des tests d'intégration (DB). Chargé ici car PHPUnit n'auto-include que
// les fichiers *Test.php ; la classe de base ne l'est pas. Inoffensif pour la suite unit.
if (is_file($itc = __DIR__ . '/Integration/IntegrationTestCase.php')) {
    require_once $itc;
}

// Socle des tests d'objets headless (la classe de base n'est pas un *Test.php auto-inclus).
if (is_file($htc = __DIR__ . '/Headless/HeadlessTestCase.php')) {
    require_once $htc;
}
