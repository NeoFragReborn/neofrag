<?php
declare(strict_types=1);

/**
 * capture-vignettes — refabrique les vignettes d'aperçu des thèmes publics.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Les six vignettes livrées étaient des maquettes dessinées, pas des captures — et trois d'entre
 * elles étaient exactement le même fichier : `forge`, `nebula` et `vitrine` partageaient la même
 * empreinte. Un visiteur comparant les thèmes dans « Thèmes & addons » voyait donc trois fois la
 * même image pour trois rendus très différents. Le défaut était invisible tant que les vignettes ne
 * s'affichaient pas du tout (mauvais chemin, corrigé le 2026-09-16).
 *
 * Ce que fait cet outil : pour chaque thème public, il bascule le site sur ce thème, capture sa
 * page d'accueil réelle avec `tools/capture.php`, la réduit au format des vignettes (480 × 270, le
 * 16/9 attendu par `.addon-card-thumbnail`) et l'écrit dans `themes/<nom>/images/thumbnail.jpg`.
 * Le thème d'origine est rétabli à la fin, y compris si une capture échoue. Une vignette refaite
 * ainsi ne peut pas mentir : c'est le rendu du thème, pas une idée du rendu.
 *
 * Usage
 * -----
 *   php tools/capture-vignettes.php                 tous les thèmes publics
 *   php tools/capture-vignettes.php nebula forge    seulement ceux-là
 *   php tools/capture-vignettes.php --page=/fr/news page capturée (défaut : l'accueil)
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';

[$o, $demandes] = nf_options(['page' => '/fr']);

const LARGEUR = 480;
const HAUTEUR = 270;

$themes = nf_themes_publics();

if ($demandes)
{
    $inconnus = array_diff($demandes, $themes);

    if ($inconnus)
    {
        nf_refus('thème(s) inconnu(s) : '.implode(', ', $inconnus).' — disponibles : '.implode(', ', $themes));
    }

    $themes = $demandes;
}

if (!function_exists('imagecreatefrompng'))
{
    nf_refus("l'extension GD est requise");
}

$db = nf_connexion();

// Un thème absent de `nf_addon` n'est pas servi : le site retombe sur le thème courant, et on
// capture six fois la même page en croyant capturer six thèmes. Ce n'est pas théorique : lancé sur
// le site de DÉMO, l'outil a rendu `nebula` et `vitrine` identiques.
$absents = array_diff($themes, nf_themes_installes($db));

if ($absents)
{
    nf_refus('thème(s) non installé(s) sur ce site : '.implode(', ', $absents)." — les capturer rendrait le thème courant, pas le leur.\n"
        ."  Lancer l'outil sur une installation qui porte les six thèmes et leurs dispositions.");
}

$origine = nf_theme_temporaire($db);

echo "Thème d'origine : {$origine}\n";
echo 'Page capturée   : '.$o['page']."\n\n";

/** Réduit un PNG de capture en JPEG 480 × 270, en ne gardant que le haut de la page. */
function reduire(string $source, string $cible): bool
{
    $img = @imagecreatefrompng($source);

    if (!$img)
    {
        return FALSE;
    }

    $l = imagesx($img);
    $h = imagesy($img);

    // La capture peut être plus haute que 16/9 (page longue). On garde le HAUT, qui porte
    // l'en-tête, la navigation et la bannière — ce qui identifie un thème au premier coup d'œil.
    $h_utile  = min($h, (int) round($l * HAUTEUR / LARGEUR));
    $vignette = imagecreatetruecolor(LARGEUR, HAUTEUR);
    imagecopyresampled($vignette, $img, 0, 0, 0, 0, LARGEUR, HAUTEUR, $l, $h_utile);

    if (!is_dir($dossier = dirname($cible)))
    {
        mkdir($dossier, 0775, TRUE);
    }

    $ok = imagejpeg($vignette, $cible, 86);


    return (bool) $ok;
}

$faits = 0;
$rates = [];

foreach ($themes as $theme)
{
    printf('  %-12s ', $theme);

    nf_reglage_poser($db, 'nf_default_theme', $theme);

    // 1280 × 720 : le 16/9 de la vignette finale. Capturer en 4/3 puis recadrer couperait le haut de
    // page, qui est justement ce qui distingue un thème d'un autre. `--visiteur` : une vignette
    // montre le thème, pas la barre d'administration ni le bandeau de consentement aux cookies.
    $sortie = nf_temp('captures-'.getmypid());

    exec(sprintf('%s %s --visiteur --sortie=%s --largeur=1280 --hauteur=720 -- %s 2>&1',
        escapeshellarg(PHP_BINARY), escapeshellarg(__DIR__.'/capture.php'), escapeshellarg($sortie), escapeshellarg($o['page'])), $lignes, $code);

    $brut = $code === 0 ? (glob($sortie.'/*.png') ?: [NULL])[0] : NULL;

    if ($brut === NULL)
    {
        echo "capture KO\n";
        $rates[] = $theme;
        continue;
    }

    $cible = nf_racine()."/themes/{$theme}/images/thumbnail.jpg";

    if (!reduire($brut, $cible))
    {
        echo "réduction KO\n";
        $rates[] = $theme;
        @unlink($brut);
        continue;
    }

    @unlink($brut);
    printf("%d × %d, %d Ko\n", LARGEUR, HAUTEUR, (int) round((int) filesize($cible) / 1024));
    $faits++;
}

nf_reglage_poser($db, 'nf_default_theme', $origine);

echo "\n{$faits} vignette(s) refabriquée(s). Thème rétabli : {$origine}\n";

if ($rates)
{
    nf_echec('échecs : '.implode(', ', $rates));
}

// Une vignette identique à une autre est exactement le défaut que cet outil corrige : on le
// vérifie plutôt que de le supposer.
$empreintes = [];

foreach (nf_themes_publics() as $theme)
{
    if (is_file($f = nf_racine()."/themes/{$theme}/images/thumbnail.jpg"))
    {
        $empreintes[md5_file($f)][] = $theme;
    }
}

$doublons = array_filter($empreintes, static fn (array $t): bool => count($t) > 1);

if ($doublons)
{
    foreach ($doublons as $partages)
    {
        nf_avertir('Vignettes IDENTIQUES : '.implode(', ', $partages));
    }

    nf_echec('des vignettes sont identiques');
}

nf_ok('les '.count($empreintes).' vignettes sont distinctes');
