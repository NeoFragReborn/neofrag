<?php
declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

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
