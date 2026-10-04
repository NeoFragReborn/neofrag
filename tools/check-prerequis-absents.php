<?php
declare(strict_types=1);

/**
 * check-prerequis-absents — à un PHP auquel manque une extension exigée, l'assistant web et l'installeur en ligne de commande disent laquelle, et s'arrêtent.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `check-prerequis` vérifie que la liste des extensions exigées (`Installer::PREREQUIS`) est la même
 * partout où elle est écrite. Il ne prouve pas qu'un hébergement auquel il en MANQUE une reçoit le bon
 * message. Or c'est précisément ce que vit celui qui installe chez un hébergeur mal équipé : sans
 * `mbstring`, une page qui appellerait `mb_strlen()` pour afficher les prérequis tomberait en erreur
 * fatale avant de dire ce qui manque.
 *
 * Pour chaque extension exigée que ce PHP charge en module (une extension compilée dans PHP ne peut pas
 * manquer : elle est écartée, et dite), il lance un PHP sans php.ini, avec toutes les autres extensions
 * sauf celle-là, et vérifie :
 *   1. l'écran des prérequis de l'assistant s'affiche, marque cette extension en rouge, et ne propose
 *      pas de continuer ;
 *   2. l'installeur en ligne de commande refuse, en la nommant.
 * Un premier passage AVEC toutes les extensions prouve que le montage lui-même n'en retire aucune.
 *
 * Il lui faut un arbre sans installation (pas de `config/db.php`) : l'assistant ne s'affiche que là. La
 * CI en a un ; sur un poste installé, il refuse.
 *
 * Usage
 * -----
 *   php tools/check-prerequis-absents.php
 *   php tools/check-prerequis-absents.php --port=8117
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';
require nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

[$o] = nf_options(['port' => 0]);

$root = nf_racine();

if (is_file($root.'/config/db.php') || is_file($root.'/install/db.txt'))
{
    nf_refus("cet arbre est installé : l'assistant ne s'y affiche pas — à jouer sur un dépôt sans installation, comme en CI");
}

// ── Les extensions de ce PHP, et celles qu'on peut lui retirer ─────────────────
$dossier  = (string) ini_get('extension_dir');
$windows  = stripos(PHP_OS, 'WIN') === 0;
$fichier  = static fn (string $e): string => $dossier.'/'.($windows ? 'php_'.$e.'.dll' : $e.'.so');
$modules  = array_values(array_filter(array_map('strtolower', get_loaded_extensions()), static fn (string $e): bool => is_file($fichier($e))));

// L'ordre compte : mysqli a besoin de mysqlnd, chargé avant lui.
usort($modules, static fn (string $a, string $b): int => ($b === 'mysqlnd') <=> ($a === 'mysqlnd'));

if (!$modules)
{
    nf_refus("ce PHP ne charge aucune extension en module ({$dossier}) : on ne peut rien lui retirer");
}

/** Les options d'un PHP sans php.ini, avec ces extensions-là. */
$php_sans = static function (string $retiree) use ($modules, $fichier): array {
    $options = ['-n'];

    foreach ($modules as $e)
    {
        if ($e !== $retiree && !($retiree === 'mysqlnd' && $e === 'mysqli'))
        {
            array_push($options, '-d', 'extension='.$fichier($e));
        }
    }

    return $options;
};

$port   = nf_port($o['port']);
$echecs = 0;

function juger(string $titre, bool $ok, string $detail = ''): void
{
    global $echecs;

    $echecs += $ok ? 0 : 1;
    printf("  %-6s %-58s %s\n", $ok ? 'OK' : 'ÉCHEC', $titre, $detail);
}

/** L'écran des prérequis, servi par un PHP lancé avec ces options. */
$ecran = static function (array $options) use ($port, $root): array {
    $serveur = nf_serveur($port, [], $root, NULL, $options);
    $r       = nf_http($serveur->base.'/?lang=fr', ['suivre' => 0, 'timeout' => 30]);
    $serveur->arreter();

    preg_match_all('#<li class="ko">.*?<span class="t">(.*?)</span>#s', $r['corps'], $ko);

    return ['code' => $r['code'], 'ko' => array_map(static fn (string $t): string => html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8'), $ko[1]),
        'continuer' => str_contains($r['corps'], 'href="?step=database"'), 'corps' => $r['corps']];
};

/** L'installeur en ligne de commande, à blanc, avec ces options : [code de sortie, sortie]. */
$cli = static function (array $options) use ($root): array {
    $commande = array_merge([PHP_BINARY], $options, [$root.'/install/cli.php', '--db-name=nf', '--db-user=nf', '--db-pass=nf',
        '--admin-user=admin', '--admin-email=admin@example.test', '--admin-pass=Prerequis-2026!', '--dry-run', '--lang=fr']);
    $p = proc_open($commande, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $t, $root);
    $sortie = (string) stream_get_contents($t[1]);
    fclose($t[1]);

    return [proc_close($p), $sortie];
};

$exigees  = Installer::PREREQUIS['extensions'];
$retirees = array_values(array_intersect($exigees, $modules));
$fixes    = array_values(array_diff($exigees, $modules));

printf("PHP %s — %d extension(s) en module ; exigées : %d, dont %d qu'on peut retirer\n", PHP_VERSION, count($modules), count($exigees), count($retirees));

if ($fixes)
{
    printf("  compilées dans ce PHP, elles ne peuvent pas manquer ici : %s\n", implode(', ', $fixes));
}

// Le témoin : rien de retiré, l'écran est tout vert et propose de continuer.
echo "\nTémoin (toutes les extensions) :\n";
$e = $ecran($php_sans(''));
juger('l\'écran des prérequis est tout vert et propose de continuer', $e['code'] === 200 && !$e['ko'] && $e['continuer'],
    'HTTP '.$e['code'].($e['ko'] ? ' — en rouge : '.implode(', ', $e['ko']) : ''));

foreach ($retirees as $extension)
{
    echo "\nSans {$extension} :\n";

    $options = $php_sans($extension);
    $sonde   = proc_open(array_merge([PHP_BINARY], $options, ['-r', 'echo extension_loaded('.var_export($extension, TRUE).') ? "là" : "absente";']), [1 => ['pipe', 'w']], $t);
    $etat    = trim((string) stream_get_contents($t[1]));
    fclose($t[1]);
    proc_close($sonde);

    if ($etat !== 'absente')
    {
        juger("{$extension} est bien retirée", FALSE, 'le PHP de l\'épreuve la charge encore');
        continue;
    }

    $e = $ecran($options);
    juger('l\'écran s\'affiche, la marque en rouge, et ne propose pas de continuer',
        $e['code'] === 200 && in_array('Extension '.$extension, $e['ko'], TRUE) && !$e['continuer'],
        'HTTP '.$e['code'].' — en rouge : '.($e['ko'] ? implode(', ', $e['ko']) : 'rien')
        .($e['code'] >= 500 || $e['code'] === 0 ? ' — '.mb_strimwidth(trim(strip_tags($e['corps'])), 0, 120, '…') : ''));

    [$code, $sortie] = $cli($options);
    juger('l\'installeur en ligne de commande refuse en la nommant', $code !== 0 && str_contains($sortie, $extension),
        'code '.$code.' — '.mb_strimwidth(trim((string) strrchr("\n".trim($sortie), "\n")), 0, 90, '…'));
}

echo "\n";

if ($echecs)
{
    nf_echec("{$echecs} vérification(s) en échec — un hébergement auquel manque une extension doit savoir laquelle");
}

nf_ok(sprintf('sans chacune des %d extension(s) qu\'on peut retirer, l\'assistant et l\'installeur disent laquelle manque, et s\'arrêtent', count($retirees)));
