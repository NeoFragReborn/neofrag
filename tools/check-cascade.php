<?php
declare(strict_types=1);
/**
 * check-cascade — une déclaration de thème qui ne peut JAMAIS s'appliquer est refusée.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * La feuille d'un widget ou d'un module est servie APRÈS celle du thème. À égalité de
 * spécificité, c'est donc l'addon qui gagne : une règle de thème qui vise une classe de widget
 * (`.nf-chiffre-valeur { font-family: … }`) ne s'applique pas, et rien ne le dit — la feuille est
 * valide, l'écran reste présentable, et personne ne voit que le dessin voulu par le thème n'est
 * jamais apparu. Pulse l'a fait pour quatre widgets d'un coup : sa dalle (grands chiffres, coins
 * arrondis, ardoise) était écrite depuis des mois et n'avait jamais été servie (2026-10-13).
 *
 * Ce qu'il mesure, et pourquoi si étroitement, est dit dans `tools/lib/cascade.php`
 * (`nf_cascade_couvre`) ; `tests/Unit/ToolsCascadeTest.php` l'éprouve sur ce qu'il doit attraper
 * comme sur ce qu'il doit laisser.
 *
 * La réparation n'est jamais « ajouter !important » : le thème monte sa spécificité (`body .nf-…`,
 * ou son enveloppe), l'addon pose son dessin par défaut dans une couche (`@layer nf-repli`), ou
 * bien la déclaration du thème était une survivance et s'enlève.
 *
 * Usage
 * -----
 *   php tools/check-cascade.php
 *   php tools/check-cascade.php --theme=extend
 *   php tools/check-cascade.php --verbeux
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/cascade.php';

[$o]     = nf_options(['theme' => '', 'verbeux' => FALSE]);
$racine  = nf_racine();
$verbeux = $o['verbeux'];

/**
 * Ce qui est laissé passer, et pourquoi.
 *
 * Une entrée se justifie par le rendu, pas par la gêne : la déclaration du thème est bien morte,
 * et c'est VOULU — l'addon a la charge de cette propriété. La clé est
 * « thème|sélecteur|propriété ».
 */
const TOLEREES = [
    // rien pour l'instant
];

// ── Ce que les addons déclarent ─────────────────────────────────────────────
// Seules leurs règles à UN SEUL bloc de sélecteur sont retenues : celles-là s'appliquent partout.
$addons = [];

foreach (['modules', 'widgets'] as $coin)
{
    foreach (glob($racine.'/'.$coin.'/*/css/*.css') ?: [] as $feuille)
    {
        $relatif = str_replace($racine.'/', '', $feuille);

        foreach (nf_cascade_regles((string) file_get_contents($feuille)) as $regle)
        {
            // Une couche perd par construction : c'est un secours, pas une charte.
            if ($regle['couche'] || !nf_cascade_classes($regle['selecteur']))
            {
                continue;
            }

            $une = [
                'feuille'   => $relatif,
                'selecteur' => $regle['selecteur'],
                'propriete' => $regle['propriete'],
                'ligne'     => $regle['ligne'],
            ];

            // Indexée par ce qu'elle pose : une forme courte se retrouve sous chacune de ses formes longues.
            foreach (nf_cascade_touche($regle['propriete']) as $pose)
            {
                $addons[$pose][] = $une;
            }
        }
    }
}

if (!$addons)
{
    nf_refus("aucune règle d'addon lue : l'arborescence a-t-elle changé ?");
}

// ── Les déclarations de thème qui ne peuvent pas s'appliquer ────────────────
$mortes   = [];
$lues     = 0;
$feuilles = 0;

$dossiers = $o['theme'] !== ''
    ? [$racine.'/themes/'.$o['theme']]
    : (glob($racine.'/themes/*', GLOB_ONLYDIR) ?: []);

foreach ($dossiers as $dossier)
{
    if (!is_dir($dossier))
    {
        nf_refus(sprintf('thème « %s » inconnu', basename($dossier)));
    }

    $theme = basename($dossier);

    foreach (glob($dossier.'/css/*.css') ?: [] as $feuille)
    {
        $feuilles++;
        $relatif = str_replace($racine.'/', '', $feuille);

        foreach (nf_cascade_regles((string) file_get_contents($feuille)) as $regle)
        {
            $lues++;

            if ($regle['couche'] || !nf_cascade_classes(nf_cascade_dernier_bloc($regle['selecteur'])))
            {
                continue;
            }

            // Seules les règles d'addon qui posent la même chose sont candidates.
            $candidates = [];

            foreach (nf_cascade_touche($regle['propriete']) as $pose)
            {
                foreach ($addons[$pose] ?? [] as $addon)
                {
                    $candidates[implode('|', $addon)] = $addon;
                }
            }

            foreach ($candidates as $addon)
            {
                if (!nf_cascade_couvre($addon['selecteur'], $regle['selecteur'], $regle['important'])
                 || isset(TOLEREES[$theme.'|'.$regle['selecteur'].'|'.$regle['propriete']]))
                {
                    continue;
                }

                $mortes[$theme][] = [
                    'feuille'   => $relatif,
                    'ligne'     => $regle['ligne'],
                    'selecteur' => $regle['selecteur'],
                    'propriete' => $regle['propriete'],
                    'valeur'    => $regle['valeur'],
                    'addon'     => $addon['feuille'],
                    'regle'     => $addon['selecteur'],
                    'sa_ligne'  => $addon['ligne'],
                ];

                break;
            }
        }
    }
}

printf("%d feuille(s) de thème, %d déclaration(s) lue(s), %d propriété(s) déclarée(s) par les addons.\n\n",
    $feuilles, $lues, count($addons));

if ($mortes)
{
    ksort($mortes);

    $total = array_sum(array_map('count', $mortes));

    printf("%d déclaration(s) de thème qui ne peuvent JAMAIS s'appliquer :\n\n", $total);

    foreach ($mortes as $theme => $liste)
    {
        printf("  ✗ %s — %d déclaration(s)\n", $theme, count($liste));

        if ($verbeux)
        {
            foreach ($liste as $x)
            {
                printf("      %s:%d\n        %s { %s: %s }\n        perd contre %s:%d — %s\n",
                    $x['feuille'], $x['ligne'], $x['selecteur'], $x['propriete'], $x['valeur'],
                    $x['addon'], $x['sa_ligne'], $x['regle']);
            }

            continue;
        }

        $vues = [];

        foreach ($liste as $x)
        {
            $vues[$x['selecteur']][] = $x['propriete'];
        }

        foreach ($vues as $selecteur => $proprietes)
        {
            printf("      %s { %s }\n", $selecteur, implode('; ', $proprietes));
        }
    }

    echo "\n";

    nf_echec(sprintf('%d déclaration(s) de thème morte(s) : monter la spécificité (body …), poser le dessin de l’addon dans une couche, ou retirer la déclaration', $total));
}

nf_ok('aucune déclaration de thème écrasée par la feuille d’un addon de même spécificité');
