<?php
declare(strict_types=1);

/**
 * check-assets — deux fichiers d'asset homonymes dont l'un masque l'autre, et les cartes de source absentes.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `path()` (neofrag/helpers/assets.php) résout un asset SELON SON APPELANT : d'abord dans les
 * assets de l'addon appelant, puis dans le dossier du cœur. Deux appelants différents qui demandent
 * `css('live-editor')` peuvent donc obtenir DEUX FICHIERS DIFFÉRENTS. Tant que les deux copies sont
 * identiques, rien ne se voit. Le jour où l'on en modifie une, la moitié des pages garde l'ancienne
 * — sans erreur, sans trace. C'est ce qui a fait afficher l'assistant d'ajout de widget sans aucun
 * style le 2026-09-15, alors que la feuille était bien déployée.
 *
 * Le contrôle signale les copies IDENTIQUES présentes à la fois dans le dossier du cœur et dans
 * celui d'un addon. Un homonyme au contenu DIFFÉRENT est le mécanisme de surcharge documenté :
 * pas signalé.
 *
 * Il refuse aussi une CARTE DE SOURCE annoncée mais absente : un minifié qui se termine par
 * `//# sourceMappingURL=….map` fait demander le fichier au navigateur ; s'il n'est pas distribué,
 * la console affiche une erreur sur CHAQUE page. On n'annonce que ce qu'on sert.
 *
 * Usage
 * -----
 *   php tools/check-assets.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

nf_options([]);

$types = ['css', 'js'];
$root  = nf_racine();

/** Assets du cœur : css/<nom>, js/<nom>. */
$coeur = [];

foreach ($types as $type)
{
    foreach (glob($root.'/'.$type.'/*.'.$type) ?: [] as $f)
    {
        $coeur[$type][basename($f)] = $f;
    }
}

/** Assets des addons : <modules|widgets|themes|addons>/<nom>/<css|js>/<fichier>. */
$doublons = [];
$assets   = 0;

foreach (['modules', 'widgets', 'themes', 'addons'] as $famille)
{
    foreach (glob($root.'/'.$famille.'/*', GLOB_ONLYDIR) ?: [] as $addon)
    {
        foreach ($types as $type)
        {
            foreach (glob($addon.'/'.$type.'/*.'.$type) ?: [] as $f)
            {
                $assets++;
                $nom = basename($f);

                // Seules les copies IDENTIQUES : elles ne peuvent que diverger, et la divergence ne
                // se verra qu'au moment où la moitié des pages servira une version périmée.
                if (isset($coeur[$type][$nom]) && file_get_contents($f) === file_get_contents($coeur[$type][$nom]))
                {
                    $doublons[] = ['addon' => nf_relatif($f), 'coeur' => nf_relatif($coeur[$type][$nom])];
                }
            }
        }
    }
}

printf("%d asset(s) d'addon examiné(s) contre %d du cœur.\n", $assets, array_sum(array_map('count', $coeur)));

// ── Cartes de source annoncées mais absentes ────────────────────────────────
// Les minifiés sont lus ici (c'est eux qui annoncent une carte) ; les éditeurs vendorisés
// (tinymce, codemirror) sont écartés par NF_EXCLUS.
$cartes_absentes = [];

foreach (nf_fichiers(['css', 'js'], ['css', 'js'], NF_EXCLUS, FALSE) as $rel => $chemin)
{
    $contenu = (string) file_get_contents($chemin);

    if (!preg_match('#sourceMappingURL=([^\s*\r\n]+)#', $contenu, $trouve))
    {
        continue;
    }

    // Une carte embarquée en base64 est toujours disponible : rien à vérifier.
    if (str_starts_with($carte = $trouve[1], 'data:'))
    {
        continue;
    }

    if (!is_file(dirname($chemin).'/'.$carte))
    {
        $cartes_absentes[] = ['fichier' => $rel, 'carte' => $carte];
    }
}

if ($cartes_absentes)
{
    printf("\n%d fichier(s) annoncent une carte de source ABSENTE :\n\n", count($cartes_absentes));

    foreach ($cartes_absentes as $c)
    {
        printf("  ✗ %s\n    annonce %s, qui n'est pas distribué\n", $c['fichier'], $c['carte']);
    }

    echo "\nLe navigateur demandera ces fichiers et affichera une erreur sur chaque page.\n";
    echo "Retirer la ligne `sourceMappingURL`, ou distribuer les cartes.\n";
}

if ($doublons)
{
    printf("\n%d copie(s) identique(s) d'un asset — l'une masque l'autre selon l'appelant :\n\n", count($doublons));

    foreach ($doublons as $d)
    {
        printf("  ✗ %s\n    masque %s\n", $d['addon'], $d['coeur']);
        echo "    Les deux fichiers sont identiques aujourd'hui : la panne attend simplement\n";
        echo "    la première modification de l'un des deux.\n\n";
    }

    echo "Garder UNE seule copie : path() retombe sur le dossier du cœur quand l'addon n'a pas la sienne.\n";
}

if ($cartes_absentes || $doublons)
{
    nf_echec(sprintf('%d asset(s) masqué(s), %d carte(s) de source manquante(s)', count($doublons), count($cartes_absentes)));
}

nf_ok('aucun asset masqué, aucune carte de source manquante');
