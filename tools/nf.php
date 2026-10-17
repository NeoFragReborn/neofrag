<?php
declare(strict_types=1);

/**
 * nf — la porte d'entrée des outils : les liste par famille, lus dans leurs en-têtes, et lance celui qu'on nomme.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Une centaine d'outils dans `tools/`, chacun avec son nom : on ne les retient pas, on les cherche. Cette porte les
 * montre rangés par ce qu'ils coûtent — les contrôles qui lisent le code, ceux qui ouvrent les pages, ceux qui éprouvent
 * un site qui tourne, les outils qui fabriquent ou publient — chacun avec la phrase que son en-tête donne, et lance
 * n'importe lequel par un nom court. Elle ne tient aucune liste : un outil ajouté demain y paraît de lui-même.
 *
 * Usage
 * -----
 *   php tools/nf.php                         tous les outils, par famille, avec ce que chacun fait
 *   php tools/nf.php check                   toute la batterie (check-all) ; `nf.php check --navigateur` aussi
 *   php tools/nf.php check seo               un contrôle par son nom court (check-seo)
 *   php tools/nf.php migrate status          n'importe quel outil, ses arguments passés tels quels
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/entetes.php';

$arguments = array_slice($_SERVER['argv'] ?? [], 1);

// ── Lancer un outil ──────────────────────────────────────────────────────────────────────────────────────────────
if ($arguments !== [] && !str_starts_with($arguments[0], '--'))
{
    $nom = (string) array_shift($arguments);

    // `check` seul : toute la batterie ; `check <x>` : le contrôle check-<x>.
    if ($nom === 'check')
    {
        $nom = $arguments !== [] && !str_starts_with($arguments[0], '--') ? 'check-'.array_shift($arguments) : 'check-all';
    }

    $outil = is_file(__DIR__.'/'.$nom.'.php') ? $nom : (is_file(__DIR__.'/check-'.$nom.'.php') ? 'check-'.$nom : NULL);

    if ($outil === NULL || $outil === 'nf')
    {
        nf_refus("aucun outil « {$nom} » dans tools/ — php tools/nf.php pour la liste");
    }

    $processus = proc_open(array_merge([PHP_BINARY, __DIR__.'/'.$outil.'.php'], $arguments), [STDIN, STDOUT, STDERR], $tubes, nf_racine());
    exit(is_resource($processus) ? proc_close($processus) : NF_REFUS);
}

// ── Les lister ───────────────────────────────────────────────────────────────────────────────────────────────────
$familles = ['statique' => 'Contrôles qui lisent le code', 'navigateur' => 'Contrôles qui ouvrent les pages',
             'cible' => 'Contrôles sur un site qui tourne', 'outil' => 'Outils : fabriquer, publier, installer, entretenir'];
$rangés   = array_fill_keys(array_keys($familles), []);

foreach (glob(__DIR__.'/*.php') ?: [] as $chemin)
{
    $nom = basename($chemin, '.php');

    if ($nom === 'router-builtin')
    {
        continue;
    }

    $entete = nf_entete_outil($nom, $chemin);
    $rangés[isset($familles[$entete['famille']]) ? $entete['famille'] : 'outil'][$nom] = $entete;
}

foreach ($familles as $famille => $titre)
{
    printf("\n%s (%d)\n", $titre, count($rangés[$famille]));
    ksort($rangés[$famille]);

    foreach ($rangés[$famille] as $nom => $entete)
    {
        $court = str_starts_with($nom, 'check-') && $nom !== 'check-all' ? 'check '.substr($nom, 6) : $nom;
        printf("  %-26s %s%s\n", $court, mb_strimwidth($entete['resume'], 0, 96, '…'), $entete['diffusion'] === 'interne' ? '  [interne]' : '');
    }
}

echo "\nLancer : php tools/nf.php <nom> [arguments] — par exemple php tools/nf.php check seo --base=https://…\n";
