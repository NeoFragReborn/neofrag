<?php
declare(strict_types=1);

/**
 * stan-baseline — régénère la liste d'exceptions de PHPStan, et refuse d'y geler une erreur neuve.
 *
 * Famille : outil
 *
 * Pourquoi
 * --------
 * `phpstan-baseline.neon` gèle le legs : les erreurs qu'on connaît et qu'on ne corrige pas encore.
 * La régénérer est utile — quand du code est corrigé, ses entrées disparaissent — mais dangereux :
 * une régénération gèle AUSSI toute erreur apparue depuis, sans que personne ne la voie. La règle du
 * projet était donc de comparer l'ancienne et la nouvelle avant d'adopter la seconde ; elle se
 * faisait à la main, par un script jetable (le 2026-09-23 encore : 2 241 entrées avant, 2 057 après,
 * et il fallait prouver qu'aucune n'était neuve).
 *
 * L'outil régénère la liste dans un fichier voisin, la compare entrée par entrée (message + fichier,
 * et le nombre d'occurrences), et n'adopte la nouvelle que si elle ne contient RIEN de plus que
 * l'ancienne. Une entrée neuve, ou un compte en hausse, est une erreur à corriger : il la montre et
 * s'arrête.
 *
 * Usage
 * -----
 *   php tools/stan-baseline.php            régénère, compare, adopte si rien n'est neuf
 *   php tools/stan-baseline.php --forcer   adopte même avec des entrées neuves (à justifier)
 *   composer stan:baseline                  la même chose
 */

require __DIR__.'/lib/outil.php';

[$o] = nf_options(['forcer' => FALSE]);

$racine   = nf_racine();
$actuelle = $racine.'/phpstan-baseline.neon';
$nouvelle = $racine.'/phpstan-baseline.nouvelle.neon';
$phpstan  = $racine.'/vendor/bin/phpstan';

if (!is_file($phpstan))
{
    nf_refus('vendor/bin/phpstan introuvable — composer install');
}

/**
 * Les entrées d'une liste d'exceptions : (message, fichier) => nombre d'occurrences.
 *
 * @return array<string, int>
 */
function entrees_baseline(string $fichier): array
{
    $entrees = [];

    preg_match_all("/message: '(.*?)'\n\t+identifier: [^\n]*\n\t+count: (\d+)\n\t+path: ([^\n]*)/", (string) @file_get_contents($fichier), $trouves, PREG_SET_ORDER);

    foreach ($trouves as [, $message, $compte, $chemin])
    {
        $entrees[$message."\0".$chemin] = (int) $compte;
    }

    return $entrees;
}

// Le fichier est écrit DANS le projet : PHPStan y note les chemins relativement à son emplacement,
// et une liste écrite ailleurs porterait des chemins absolus, incomparables avec l'actuelle.
chdir($racine);
exec(escapeshellarg($phpstan).' analyse -c phpstan-base.neon --no-progress --memory-limit=2G --allow-empty-baseline --generate-baseline '.escapeshellarg(basename($nouvelle)).' 2>&1', $sortie, $code);

if (!is_file($nouvelle))
{
    nf_refus('PHPStan n\'a pas écrit la liste : '.implode(' | ', array_slice($sortie, -3)));
}

$avant = entrees_baseline($actuelle);
$apres = entrees_baseline($nouvelle);

$neuves  = array_diff_key($apres, $avant);
$hausses = array_filter($apres, static fn (int $n, string $cle): bool => isset($avant[$cle]) && $n > $avant[$cle], ARRAY_FILTER_USE_BOTH);

printf("Liste actuelle : %d entrée(s), %d occurrence(s).\n", count($avant), array_sum($avant));
printf("Liste régénérée : %d entrée(s), %d occurrence(s).\n\n", count($apres), array_sum($apres));

foreach ($neuves as $cle => $n)
{
    [$message, $chemin] = explode("\0", $cle);
    printf("  + %s — %s (%d)\n", $chemin, stripcslashes(trim($message, '#^$')), $n);
}

foreach ($hausses as $cle => $n)
{
    [$message, $chemin] = explode("\0", $cle);
    printf("  ↑ %s — %s (%d → %d)\n", $chemin, stripcslashes(trim($message, '#^$')), $avant[$cle], $n);
}

if (($neuves || $hausses) && !$o['forcer'])
{
    unlink($nouvelle);
    nf_echec(sprintf('%d entrée(s) neuve(s), %d compte(s) en hausse : ce sont des erreurs à corriger, pas à geler (--forcer pour les geler quand même)', count($neuves), count($hausses)));
}

rename($nouvelle, $actuelle);
nf_ok(sprintf('liste adoptée : %d occurrence(s) de moins, aucune neuve', array_sum($avant) - array_sum($apres)));
