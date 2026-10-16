<?php
declare(strict_types=1);

/**
 * fichiers-retires — écrit la liste des fichiers qu'un paquet a livrés puis que le produit a retirés.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Une mise à jour efface d'un site ce que la nouvelle version ne porte plus : la liste `remove` du manifeste de son
 * paquet. La fabrique la déduisait de l'inventaire de la version précédente — qu'elle n'a jamais sous la main, sur le
 * serveur, dans une copie sans `.git`. La liste sortait vide, et les fichiers retirés restaient sur chaque site mis
 * à jour : le 2026-10-16, le Monitoring de la vitrine en signalait six (les polices `.ttf` de Font Awesome, deux
 * langues renommées de TinyMCE), et quarante-trois autres dormaient là où il ne regarde pas.
 *
 * Cet outil lit l'historique git — tout fichier supprimé qu'un paquet de mise à jour aurait porté, et qui n'est pas
 * revenu — et l'écrit dans `tools/lib/fichiers-retires.php`, versionné. `build-release` le lit pour le manifeste ;
 * `check-mise-a-jour` prouve, à chaque version, qu'un fichier retiré quitte bien le site.
 *
 * Usage
 * -----
 *   php tools/fichiers-retires.php              réécrit la liste depuis l'historique git
 *   php tools/fichiers-retires.php --verifier   échoue si la liste versionnée n'est plus celle de l'historique
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/paquet.php';

[$o]    = nf_options(['verifier' => FALSE]);
$racine = nf_racine();

$retires = nf_paquet_retires_git($racine);

if ($retires === NULL)
{
    nf_refus("pas d'historique git ici ({$racine}) : la liste se calcule dans le dépôt de travail");
}

$texte = implode("\n", [
    '<?php',
    'declare(strict_types=1);',
    '',
    '/**',
    " * fichiers-retires — les fichiers qu'un paquet a livrés, et que le produit a retirés depuis : une mise à jour les efface d'un site.",
    ' *',
    ' * Diffusion : publique',
    ' *',
    " * Écrit par `php tools/fichiers-retires.php` depuis l'historique git — ne pas éditer à la main.",
    ' */',
    '',
    'return [',
    ...array_map(static fn (string $chemin): string => '    '.var_export($chemin, TRUE).',', $retires),
    '];',
    '',
]);

$actuel = (string) @file_get_contents($racine.'/'.NF_PAQUET_RETIRES);

if ($o['verifier'])
{
    if ($actuel !== $texte)
    {
        $versionnes = nf_paquet_retires($racine);
        $manquants  = array_diff($retires, $versionnes);
        $en_trop    = array_diff($versionnes, $retires);

        foreach ($manquants as $chemin)
        {
            echo "  + {$chemin}\n";
        }

        foreach ($en_trop as $chemin)
        {
            echo "  - {$chemin}\n";
        }

        nf_echec(NF_PAQUET_RETIRES." n'est plus celle de l'historique : php tools/fichiers-retires.php, puis la commiter");
    }

    nf_ok(count($retires).' fichier(s) retiré(s), la liste versionnée est à jour');
}

if ($actuel === $texte)
{
    nf_ok(count($retires).' fichier(s) retiré(s), la liste était déjà à jour');
}

file_put_contents($racine.'/'.NF_PAQUET_RETIRES, $texte);
nf_ok(count($retires).' fichier(s) retiré(s), écrits dans '.NF_PAQUET_RETIRES);
