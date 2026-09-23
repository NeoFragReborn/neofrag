<?php
declare(strict_types=1);
/**
 * check-css-variables — toute variable CSS qu'un module ou un widget emploie est définie par tous les thèmes.
 *
 * Famille : statique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * `var(--nf-bg-elevated, #fff)` a l'air sûr : il y a un repli. C'est précisément le piège. La
 * variable n'était définie que dans les six thèmes PUBLICS ; sous l'administration, chaque usage
 * retombait donc sur le repli — BLANC — et posait un bloc blanc au milieu d'une page sombre. Le
 * navigateur ne signale rien, la feuille est valide, et le défaut ne se voit qu'à l'œil, dans le
 * bon thème, sur la bonne page. le mainteneur l'a trouvé avant nous.
 *
 * Une variable sans définition n'est pas toujours une erreur — une feuille de module peut
 * légitimement s'appuyer sur un jeton que chaque thème lui fournit. L'outil regarde donc si CHAQUE
 * thème la définit, et ne signale que celles qu'AUCUN ne définit, ou que certains seulement : c'est
 * là que le rendu diffère d'un thème à l'autre sans que personne l'ait voulu.
 *
 * Usage
 * -----
 *   php tools/check-css-variables.php
 *   php tools/check-css-variables.php --verbeux
 */

require __DIR__.'/lib/outil.php';

[$o]     = nf_options(['verbeux' => FALSE]);
$racine  = nf_racine();
$verbeux = $o['verbeux'];

/**
 * Variables qu'aucun thème n'a à définir, et pourquoi.
 *
 * Une variable posée en ligne par un gabarit est légitimement absente des feuilles : c'est la
 * valeur d'une donnée, pas un jeton de charte.
 */
const TOLEREES = [
    '--online-pct' => 'posée en ligne par widgets/steam/views/index.tpl.php (pourcentage de joueurs en ligne)',
];

/** Les feuilles d'un thème définissent son vocabulaire ; celles des modules et widgets l'emploient. */
$themes = [];

foreach (glob($racine.'/themes/*', GLOB_ONLYDIR) as $dossier)
{
    $themes[basename($dossier)] = [];

    foreach (glob($dossier.'/css/*.css') as $feuille)
    {
        $source = (string) file_get_contents($feuille);

        preg_match_all('/(--[A-Za-z0-9_-]+)\s*:/', $source, $trouves);

        foreach ($trouves[1] as $nom)
        {
            $themes[basename($dossier)][$nom] = TRUE;
        }
    }
}

if (!$themes)
{
    nf_refus("aucun thème trouvé : l'arborescence a-t-elle changé ?");
}

// Les feuilles partagées (css/) valent pour tous les thèmes : ce qu'elles définissent est acquis.
$communes = [];

foreach (glob($racine.'/css/*.css') as $feuille)
{
    preg_match_all('/(--[A-Za-z0-9_-]+)\s*:/', (string) file_get_contents($feuille), $trouves);

    foreach ($trouves[1] as $nom)
    {
        $communes[$nom] = TRUE;
    }
}

// ── Ce que les modules et widgets EMPLOIENT ─────────────────────────────────
$emplois = [];

foreach (['modules', 'widgets', 'css'] as $coin)
{
    foreach (glob($racine.'/'.$coin.'/*/css/*.css') ?: [] as $feuille)
    {
        $relatif = str_replace($racine.'/', '', $feuille);
        $source  = (string) file_get_contents($feuille);

        // Ce que la feuille définit elle-même est évidemment disponible pour elle.
        preg_match_all('/(--[A-Za-z0-9_-]+)\s*:/', $source, $definies);
        $locales = array_flip($definies[1]);

        preg_match_all('/var\(\s*(--[A-Za-z0-9_-]+)/', $source, $trouves);

        foreach (array_unique($trouves[1]) as $nom)
        {
            if (!isset($locales[$nom]) && !isset($communes[$nom]) && !isset(TOLEREES[$nom]))
            {
                $emplois[$nom][] = $relatif;
            }
        }
    }
}

ksort($emplois);

// ── Confrontation ───────────────────────────────────────────────────────────
$orphelines = [];
$partielles = [];

foreach ($emplois as $nom => $feuilles)
{
    $definie_par = [];
    $absente_de  = [];

    foreach ($themes as $theme => $variables)
    {
        if (isset($variables[$nom])) { $definie_par[] = $theme; } else { $absente_de[] = $theme; }
    }

    if (!$definie_par)
    {
        $orphelines[$nom] = ['feuilles' => $feuilles, 'absente_de' => $absente_de];
    }
    else if ($absente_de)
    {
        $partielles[$nom] = ['feuilles' => $feuilles, 'absente_de' => $absente_de];
    }
}

printf("%d thème(s), %d variable(s) employée(s) par les modules et widgets.\n\n",
    count($themes), count($emplois));

if ($verbeux)
{
    foreach ($emplois as $nom => $feuilles)
    {
        printf("  %-28s %s\n", $nom, implode(', ', array_unique($feuilles)));
    }

    echo "\n";
}

if ($orphelines)
{
    printf("%d variable(s) qu'AUCUN thème ne définit — le repli s'applique toujours :\n\n", count($orphelines));

    foreach ($orphelines as $nom => $x)
    {
        printf("  ✗ %s\n      employée par : %s\n", $nom, implode(', ', array_unique($x['feuilles'])));
    }

    echo "\n";
}

if ($partielles)
{
    printf("%d variable(s) que CERTAINS thèmes seulement définissent — le rendu diffère selon le thème :\n\n",
        count($partielles));

    foreach ($partielles as $nom => $x)
    {
        printf("  ✗ %s\n      absente de   : %s\n      employée par : %s\n",
            $nom, implode(', ', $x['absente_de']), implode(', ', array_unique($x['feuilles'])));
    }

    echo "\n";
}

if ($orphelines || $partielles)
{
    nf_echec(sprintf('%d variable(s) sans définition sûre', count($orphelines) + count($partielles)));
}

nf_ok('chaque variable employée est définie par tous les thèmes');
