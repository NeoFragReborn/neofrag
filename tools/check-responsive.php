<?php
declare(strict_types=1);

/**
 * check-responsive — mesure le débordement horizontal des pages, à plusieurs largeurs.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le responsive du projet ne reposait que sur des captures d'écran regardées à la main. Une entrée
 * du suivi affirmait « 0 débordement de 390 px à 2560 px, passe de fond faite » — c'était faux :
 * 309 colonnes s'appliquaient dès 0 px, et sur un téléphone la page débordait franchement. Rien ne
 * pouvait le dire, puisque rien ne mesurait.
 *
 * Un débordement horizontal est objectif : `scrollWidth > clientWidth` sur la racine du document.
 * L'outil charge chaque page dans un navigateur sans interface, aux largeurs demandées, puis
 * relève la mesure ET les éléments fautifs — leur balise, leurs classes et le nombre de pixels
 * dépassés, pour que la correction ne soit pas une devinette.
 *
 * Usage
 * -----
 *   php tools/check-responsive.php                       installation courante, largeurs par défaut
 *   php tools/check-responsive.php 390,768,1400
 *   php tools/check-responsive.php 390 -- /fr /fr/forum
 *   php tools/check-responsive.php --port=8101
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o, $reste] = nf_options(['port' => 0]);

$largeurs = [390, 768, 1400];
$chemins  = [];

if ($reste && preg_match('/^\d+(,\d+)*$/', $reste[0]))
{
    $largeurs = array_values(array_filter(array_map('intval', explode(',', array_shift($reste)))));
}

$chemins = $reste;

if (!$chemins)
{
    // Un échantillon qui couvre les trois familles d'écrans : site public, espace membre,
    // administration. Les pages d'administration ne rendent que si une session est fournie.
    $chemins = [
        '/fr', '/fr/forum', '/fr/contact', '/fr/user', '/fr/user/account', '/fr/user/profile',
        '/fr/admin', '/fr/admin/forum', '/fr/admin/events', '/fr/admin/settings', '/fr/admin/monitoring',
    ];
}

/**
 * Le script de mesure, injecté dans la page.
 *
 * Il ne se contente pas de dire « ça déborde » : il nomme les éléments dont le bord droit dépasse
 * la largeur du document. Sans cela, le constat serait vrai mais inexploitable — un débordement
 * vient toujours d'un élément précis, jamais de la page en général.
 *
 * Le seuil de 2 px absorbe les arrondis de sous-pixel des navigateurs.
 */
$sonde = <<<'JS'
(function(){
    var racine = document.documentElement;
    var large  = racine.clientWidth;
    var verdict = { deborde: racine.scrollWidth > large + 2, largeur: large, reel: racine.scrollWidth, coupables: [] };
    var vus = {};

    document.querySelectorAll('body *').forEach(function(el){
        var b = el.getBoundingClientRect();

        if (b.width <= 0 || b.height <= 0) { return; }
        if (b.right <= large + 2) { return; }

        var style = window.getComputedStyle(el);
        if (style.position === 'fixed' || style.visibility === 'hidden') { return; }

        var nom = el.tagName.toLowerCase();
        var classes = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 3);
        if (classes.length) { nom += '.' + classes.join('.'); }

        // Un parent qui deborde a des enfants qui debordent : on ne garde que le premier de chaque
        // signature, sinon le rapport liste cent fois la meme cause.
        if (vus[nom]) { return; }
        vus[nom] = 1;

        verdict.coupables.push({ el: nom, depasse: Math.round(b.right - large) });
    });

    verdict.coupables.sort(function(a, b){ return b.depasse - a.depasse; });
    verdict.coupables = verdict.coupables.slice(0, 8);

    var n = document.createElement('div');
    n.id = 'nf-responsive-verdict';
    n.setAttribute('data-verdict', JSON.stringify(verdict));
    document.body.appendChild(n);
})();
JS;

$fichier_sonde = nf_temp('sonde.js');
file_put_contents($fichier_sonde, $sonde);

$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), [
    'NF_OUTIL_SESSION'  => nf_session_admin($db),
    'NF_OUTIL_SONDE'    => $fichier_sonde,
    'NF_OUTIL_SONDE_OU' => 'body',
]);

printf("Base : %s\nLargeurs : %s\n\n", $serveur->base, implode(', ', $largeurs));

$anomalies = 0;
$mesures   = 0;
$muettes   = [];

foreach ($chemins as $chemin)
{
    foreach ($largeurs as $largeur)
    {
        $url     = $serveur->base.$chemin.(str_contains($chemin, '?') ? '&' : '?').'nf_sonde='.$largeur;
        $verdict = nf_sonde_verdict(nf_chrome_dom($url, ['largeur' => $largeur, 'hauteur' => 900]), 'nf-responsive-verdict');
        $mesures++;

        if ($verdict === NULL)
        {
            $muettes[] = sprintf('%s @ %dpx', $chemin, $largeur);
            continue;
        }

        if (empty($verdict['deborde']))
        {
            continue;
        }

        $anomalies++;

        printf("  ✗ %-28s %4dpx : le document fait %d px (%+d)\n",
            $chemin, $largeur, $verdict['reel'], $verdict['reel'] - $verdict['largeur']);

        foreach ($verdict['coupables'] as $c)
        {
            printf("       %-52s dépasse de %d px\n", $c['el'], $c['depasse']);
        }
    }
}

@unlink($fichier_sonde);

printf("\n%d mesure(s).\n", $mesures);

if ($muettes)
{
    printf("\n%d page(s) n'ont rendu AUCUN verdict — la sonde n'a pas été exécutée :\n", count($muettes));

    foreach (array_slice($muettes, 0, 10) as $m)
    {
        printf("   %s\n", $m);
    }

    nf_refus("une page muette n'est pas une page sans débordement : elle n'a pas été mesurée.\n"
        ."  Vérifier que le routeur injecte bien la sonde, et que la page répond 200.");
}

if ($anomalies)
{
    nf_echec(sprintf('%d débordement(s) sur %d mesures — un débordement horizontal force le lecteur à faire défiler la page de côté',
        $anomalies, $mesures));
}

nf_ok('aucun débordement horizontal');
