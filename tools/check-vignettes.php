<?php
declare(strict_types=1);

/**
 * check-vignettes — chaque addon a sa vignette, au bon format, ou une exemption écrite qui dit pourquoi.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La vignette d'un addon (`images/thumbnail.jpg`) fait sa carte dans « Thèmes & addons » et sa fiche
 * dans la place de marché. Rien n'exigeait qu'elle existe, ni qu'elle ait le bon format. Constaté le
 * 2026-10-04 : les modules `api` et `discord`, ajoutés trois jours plus tôt, étaient au catalogue
 * sans vignette ; quatre thèmes gardaient un ancien 480 × 270, deux addons un 640 × 400 ; trois
 * widgets du cœur n'en avaient aucune. `check-marketplace` ne s'en apercevait qu'au moment d'une
 * publication, et seulement pour les aperçus ANNONCÉS.
 *
 * Ce contrôle juge le DÉPÔT, sans site ni navigateur : il voit l'addon sans vignette le jour où il
 * arrive, pas le jour où il part.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. tout addon (module, widget, thème, addon de `addons/`) a `images/thumbnail.jpg`, JPEG, au
 *      format et sous le poids de `lib/vignettes.php` — sauf exemption ;
 *   2. chaque EXEMPTION nomme un addon qui existe, et qui n'a toujours pas de vignette : une
 *      exemption devenue fausse se retire ;
 *   3. aucune vignette n'est la copie d'une autre : trois thèmes ont longtemps partagé la même image.
 *
 * Une vignette se produit avec `capturer-apercus`, jamais à la main : c'est une photo du produit.
 *
 * Usage
 * -----
 *   php tools/check-vignettes.php
 *   php tools/check-vignettes.php --verbeux    liste aussi les vignettes conformes et les exemptions
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/vignettes.php';

[$o] = nf_options(['verbeux' => FALSE]);

$racine     = nf_racine();
$total      = 0;
$ecarts     = [];
$conformes  = [];
$exemptes   = [];
$empreintes = [];

foreach (['modules', 'widgets', 'themes', 'addons'] as $famille)
{
    foreach (glob($racine.'/'.$famille.'/*', GLOB_ONLYDIR) ?: [] as $dossier)
    {
        $nom = basename($dossier);

        // Un dossier sans fichier principal n'est pas un addon (restes, fichiers partagés).
        if (!is_file($dossier.'/'.$nom.'.php'))
        {
            continue;
        }

        $cle     = $famille.'/'.$nom;
        $fichier = $dossier.'/images/thumbnail.jpg';
        $total++;

        if (isset(NF_VIGNETTES_EXEMPTEES[$cle]))
        {
            if (is_file($fichier))
            {
                $ecarts[] = [$cle, 'exempté, mais il a maintenant une vignette : retirer son exemption de tools/lib/vignettes.php'];
            }
            else
            {
                $exemptes[$cle] = NF_VIGNETTES_EXEMPTEES[$cle];
            }

            continue;
        }

        if (($defaut = nf_vignette_defaut($fichier)) !== NULL)
        {
            $ecarts[] = [$cle, 'vignette '.$defaut.' — à produire avec php tools/capturer-apercus.php, ou à exempter avec sa raison'];
            continue;
        }

        $conformes[] = $cle;
        $empreintes[(string) md5_file($fichier)][] = $cle;
    }
}

// ── 2. Une exemption qui ne nomme plus rien ──────────────────────────────────────────────────
foreach (array_keys(NF_VIGNETTES_EXEMPTEES) as $cle)
{
    if (!is_file($racine.'/'.$cle.'/'.basename($cle).'.php'))
    {
        $ecarts[] = [$cle, 'exempté, mais cet addon n’existe pas : retirer l’exemption de tools/lib/vignettes.php'];
    }
}

// ── 3. Deux addons, une seule image ──────────────────────────────────────────────────────────
foreach ($empreintes as $partage)
{
    if (count($partage) > 1)
    {
        $ecarts[] = [implode(', ', $partage), 'vignettes IDENTIQUES : chacune doit être la photo de son addon'];
    }
}

if ($o['verbeux'])
{
    foreach ($conformes as $cle)
    {
        printf("  ✓ %s\n", $cle);
    }

    foreach ($exemptes as $cle => $raison)
    {
        printf("  · %-30s %s\n", $cle, $raison);
    }

    echo "\n";
}

printf("%d addon(s) : %d vignette(s) conforme(s) (%d × %d), %d exempté(s).\n",
    $total, count($conformes), NF_VIGNETTE_LARGEUR, NF_VIGNETTE_HAUTEUR, count($exemptes));

if ($ecarts)
{
    echo "\nÉCARTS :\n\n";

    foreach ($ecarts as [$cle, $raison])
    {
        printf("  %-30s %s\n", $cle, $raison);
    }

    echo "\n";
    nf_echec(sprintf('%d écart(s) sur les vignettes', count($ecarts)));
}

nf_ok(sprintf('les %d addons ont leur vignette au format %d × %d, ou une exemption motivée (%d)',
    $total, NF_VIGNETTE_LARGEUR, NF_VIGNETTE_HAUTEUR, count($exemptes)));
