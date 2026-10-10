<?php
declare(strict_types=1);
/**
 * check-colles — tout ce qui colle en haut de l'écran se cale sous les bandeaux du haut de page.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Trois bandeaux peuvent occuper le haut de la fenêtre : celui de la démonstration, celui de la maintenance et celui
 * de l'aperçu des droits. Le gabarit principal les empile et publie leur hauteur totale dans `--nf-haut`, et la
 * hauteur de l'en-tête collé du thème dans `--nf-entete` (NF.bandeaux(), neofrag/views/theme/main.tpl.php). Un
 * élément collé à `top: 0` passe dessous. Le 2026-10-08, sur la démonstration, la moitié de l'en-tête d'Extend, de
 * Chronique, de Pulse et de Nebula disparaissait sous son bandeau dès qu'on faisait défiler la page, et le bas du
 * rail de Forge et de la barre latérale de l'administration sortait de l'écran. Chaque feuille, prise seule, était
 * correcte ; le défaut ne se voyait que sur la démonstration, page défilée.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Dans les feuilles des thèmes, des modules, des widgets et du cœur :
 *   1. une règle qui COLLE un élément (`position: sticky`) avec un `top` le cale sur `--nf-haut` ; celle d'un module
 *      ou d'un widget — une colonne, sous l'en-tête du thème quel qu'il soit — aussi sur `--nf-entete` ;
 *   2. une règle qui FIXE un élément en haut (`position: fixed` et un `top`) le cale sur `--nf-haut` — sauf un
 *      panneau qui descend jusqu'en bas (`bottom`, `inset: 0`), qui recouvre la page à dessein, et un lien
 *      d'évitement rangé hors de l'écran (`top` négatif) ;
 *   3. un thème qui colle un élément sous les bandeaux marque son en-tête `data-nf-entete` dans ses gabarits — sans
 *      quoi `--nf-entete` reste à zéro, et les colonnes collées des modules passent sous lui.
 *
 * Les éléments qui collent DANS un cadre qui défile — pas dans la fenêtre — sont hors de cause : décalés de la
 * hauteur des bandeaux, ils descendraient sur leur contenu. Ils sont nommés ci-dessous, chacun avec sa raison.
 *
 * C'est un contrôle STATIQUE : il lit les feuilles, il n'affiche rien. Ce qu'il ne voit pas — un élément qui passe
 * sous un bandeau pour une autre raison — se mesure page défilée, dans un navigateur.
 *
 * Usage
 * -----
 *   php tools/check-colles.php            liste les règles fautives, code 1 s'il y en a
 *   php tools/check-colles.php --detail   montre aussi les règles conformes
 */

require __DIR__.'/lib/outil.php';

[$o]    = nf_options(['detail' => FALSE]);
$racine = nf_racine();
$detail = $o['detail'];

/** Les éléments qui collent dans un cadre qui défile, et non dans la fenêtre — « feuille sélecteur » => raison. */
const DANS_UN_CADRE = [
    'themes/admin/css/style.css .nf-topbar'                => 'colle dans `.nf-main`, que son `overflow-x: hidden` fait conteneur de défilement : décalée de la hauteur des bandeaux, elle descendait sur le contenu',
    'modules/files/css/file_manager.css .files-sidebar-card' => 'colle dans `.nf-main` de l\'administration, comme la barre du haut',
    'modules/access/css/access.css .matrix-table thead th'   => 'colle en haut du tableau des permissions, qui défile dans son propre cadre',
];

/** Les thèmes dont l'élément collé sous les bandeaux n'est pas un en-tête : le rail de l'administration. */
const SANS_ENTETE = [
    'admin' => 'son seul élément collé sous les bandeaux est la barre latérale, pas un en-tête au-dessus du contenu',
];

/** Les déclarations d'un bloc : nom => valeur (la dernière l'emporte, comme dans le navigateur). */
function declarations(string $bloc): array
{
    $sortie = [];

    foreach (explode(';', $bloc) as $declaration)
    {
        if (($deux_points = strpos($declaration, ':')) !== FALSE)
        {
            $sortie[strtolower(trim(substr($declaration, 0, $deux_points)))] = trim(str_replace('!important', '', substr($declaration, $deux_points + 1)));
        }
    }

    return $sortie;
}

$feuilles = array_merge(
    glob($racine.'/themes/*/css/*.css') ?: [],
    glob($racine.'/modules/*/css/*.css') ?: [],
    glob($racine.'/widgets/*/css/*.css') ?: [],
    glob($racine.'/css/*.css') ?: []
);

$fautes     = [];
$conformes  = [];
$exceptions = [];
$themes_colles = [];

foreach ($feuilles as $feuille)
{
    if (str_ends_with($feuille, '.min.css'))
    {
        continue;
    }

    $relatif = substr($feuille, strlen($racine) + 1);
    $source  = preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents($feuille));
    $coin    = explode('/', $relatif)[0];

    // Les blocs les plus intérieurs : `sélecteur { déclarations }`. Une règle d'un `@media` en est un comme un autre.
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', $source, $blocs, PREG_SET_ORDER);

    foreach ($blocs as [, $selecteur, $bloc])
    {
        $selecteur = trim(preg_replace('/\s+/', ' ', $selecteur));
        $d         = declarations($bloc);
        $position  = strtolower($d['position'] ?? '');

        if (!in_array($position, ['sticky', 'fixed'], TRUE) || !isset($d['top']))
        {
            continue;
        }

        $top = $d['top'];

        if ($top === 'auto' || str_starts_with($top, '-') || preg_match('/^calc\(\s*-/', $top))
        {
            continue;
        }

        if ($position === 'fixed' && (isset($d['bottom']) || preg_match('/^0(px)?$/', $d['inset'] ?? '')))
        {
            continue;
        }

        $lieu = $relatif.' '.$selecteur;

        if (isset(DANS_UN_CADRE[$lieu]))
        {
            $exceptions[$lieu] = DANS_UN_CADRE[$lieu];
            continue;
        }

        $manque = [];

        if (!str_contains($top, '--nf-haut'))
        {
            $manque[] = '--nf-haut';
        }

        if ($position === 'sticky' && in_array($coin, ['modules', 'widgets'], TRUE) && !str_contains($top, '--nf-entete'))
        {
            $manque[] = '--nf-entete';
        }

        if ($manque)
        {
            $fautes[] = sprintf('%s — %s { position: %s; top: %s } ne se cale pas sur %s', $relatif, $selecteur, $position, $top, implode(' ni sur ', $manque));
        }
        else
        {
            $conformes[] = sprintf('%s — %s', $relatif, $selecteur);

            if ($coin === 'themes' && $position === 'sticky')
            {
                $themes_colles[explode('/', $relatif)[1]] = TRUE;
            }
        }
    }
}

// 3. Un thème qui colle un élément sous les bandeaux marque son en-tête.
foreach (array_keys($themes_colles) as $theme)
{
    if (isset(SANS_ENTETE[$theme]))
    {
        continue;
    }

    $marque = FALSE;

    foreach (glob($racine.'/themes/'.$theme.'/views/{,*/}*.tpl.php', GLOB_BRACE) ?: [] as $gabarit)
    {
        if (str_contains((string) file_get_contents($gabarit), 'data-nf-entete'))
        {
            $marque = TRUE;
            break;
        }
    }

    if (!$marque)
    {
        $fautes[] = sprintf('themes/%s — colle un élément sous les bandeaux, mais aucun gabarit ne marque son en-tête `data-nf-entete`', $theme);
    }
}

if ($detail)
{
    echo "Conformes :\n";

    foreach ($conformes as $c)
    {
        echo "  ✓ $c\n";
    }

    echo "\nDans un cadre qui défile (hors de cause) :\n";

    foreach ($exceptions as $lieu => $raison)
    {
        echo "  · $lieu — $raison\n";
    }

    echo "\n";
}

if ($fautes)
{
    printf("%d règle(s) collent un élément sans tenir compte des bandeaux du haut de page :\n\n", count($fautes));

    foreach ($fautes as $faute)
    {
        echo "  ✗ $faute\n";
    }

    echo "\n  Un en-tête ou un rail : top: var(--nf-haut, 0px). Une colonne : top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px).\n";
    echo "  L'en-tête collé d'un thème porte data-nf-entete dans son gabarit (cf. neofrag/views/theme/main.tpl.php).\n\n";

    nf_echec(sprintf('%d règle(s) passent sous les bandeaux du haut de page', count($fautes)));
}

nf_ok(sprintf('%d élément(s) collés ou fixés en haut de l\'écran se calent sous les bandeaux du haut de page', count($conformes)));
