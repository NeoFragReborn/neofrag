<?php
/**
 * Bootstrap PHPStan : réenregistre l'autoloader maison de NeoFrag (namespace NF\ → chemin lowercase),
 * car le projet n'a pas d'autoload PSR-4 composer pour ses propres classes (cf. index.php).
 */

require __DIR__ . '/vendor/autoload.php';

// Les helpers globaux (url(), notify(), icon(), lang()…) sont require'd au boot dans index.php, pas
// autoloadés. On les charge ici pour que PHPStan connaisse ces fonctions (sinon « function.notFound »).
foreach (glob(__DIR__ . '/neofrag/helpers/*.php') as $helper) {
    require_once $helper;
}

spl_autoload_register(function ($name) {
    $namespace = explode('\\', $name);

    if (array_shift($namespace) === 'NF' && $namespace) {
        array_walk($namespace, function (&$a) {
            $a = strtolower(rtrim($a, '_'));
        });

        $file = __DIR__ . '/' . implode('/', $namespace) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});
