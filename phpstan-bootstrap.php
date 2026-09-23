<?php
/**
 * Bootstrap PHPStan : réenregistre l'autoloader maison de NeoFrag (namespace NF\ → chemin lowercase),
 * car le projet n'a pas d'autoload PSR-4 composer pour ses propres classes (cf. index.php).
 */

require __DIR__ . '/vendor/autoload.php';

/**
 * `NeoFrag()` — le service locator global — est défini dans index.php, que PHPStan n'analyse pas :
 * ses 223 points d'appel produisaient « Function NeoFrag not found », tous gelés dans le baseline.
 * Conséquence : toute ligne NEUVE qui l'employait faisait échouer l'analyse, et on était poussé à
 * contourner (2026-09-20, en servant /favicon.ico). On la déclare donc ici, comme les helpers juste
 * en dessous. Le retour reste `mixed` — c'est la réalité d'un service locator : rien de plus n'est
 * promis à l'analyse, mais la fonction cesse d'être « inconnue ».
 *
 * @return mixed
 */
function NeoFrag(...$args)
{
    return null; // jamais exécuté : PHPStan lit la déclaration, il n'appelle pas la fonction.
}

/**
 * `NEOFRAG_CMS` — la racine du produit sur le disque — est définie dans index.php, que PHPStan
 * n'analyse pas. Ses emplois existants sont gelés dans le baseline ; sans cette déclaration,
 * toute ligne NEUVE qui l'utilise fait échouer l'analyse, et on est poussé à la contourner au
 * lieu de s'en servir. Même raisonnement que pour `NeoFrag()` ci-dessus.
 */
if (!defined('NEOFRAG_CMS')) {
    define('NEOFRAG_CMS', __DIR__);
}

/**
 * `NEOFRAG_VERSION` — même cas, même raison : définie dans index.php, que PHPStan n'analyse pas.
 * Le service worker s'en sert pour nommer son cache, et sans cette déclaration une ligne neuve
 * qui l'emploie fait échouer l'analyse. La valeur n'a aucune importance ici : PHPStan lit la
 * déclaration, il n'exécute rien.
 */
if (!defined('NEOFRAG_VERSION')) {
    define('NEOFRAG_VERSION', '0.0.0');
}

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
