<?php
declare(strict_types=1);

/**
 * vignettes-cartes — la vignette d'un addon dessinée comme une carte : son nom, son icône, ce qu'il fait.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `capturer-apercus` PHOTOGRAPHIE chaque addon tel qu'il tourne. C'est la meilleure vignette quand
 * l'addon a quelque chose à montrer — un thème, une galerie peuplée, un forum vivant. Mais un module
 * ou un widget sans données rend un cadre, un titre et des lignes : le mainteneur, le 2026-10-09, « elles
 * sont peu lisibles et peu intelligibles si le widget ou module n'avait pas de data de démo ».
 *
 * Cet outil dessine l'autre vignette : une CARTE, dans l'habillage des visuels de partage du projet
 * (fond d'ardoise, grille fine, marque NeoFrag Reborn), portant l'icône de l'addon, son nom et son
 * descriptif. Elle ne montre pas le produit : elle le NOMME, lisiblement, à toute taille.
 *
 * Ce qu'il lit, et pourquoi il ne démarre pas le site
 * ---------------------------------------------------
 * Le nom, le descriptif et l'icône se lisent dans le `__info()` du fichier de l'addon, au même
 * endroit que `check-addon-declarations` les lit : une expression régulière sur la source. Démarrer
 * le site demanderait une base, une installation et une langue ; ici, la source suffit, et l'outil
 * tourne sur n'importe quelle machine, y compris sans base.
 *
 * Le descriptif est celui du français (l'argument de `$this->lang(…)`), comme les visuels de partage.
 *
 * Le rendu
 * --------
 * Un fichier HTML autonome par addon, écrit dans un dossier temporaire avec les polices et la marque
 * recopiées à côté — aucune adresse réseau, aucun service tiers —, puis photographié par le Chrome
 * installé (Playwright) à deux points par pixel, et réduit au format des vignettes
 * (960 × 600, `lib/vignettes.php`).
 *
 * Il n'écrase RIEN par défaut : il écrit dans `--sortie`, pour qu'on regarde avant. `--poser` remet
 * chaque carte à sa place (`<addon>/images/thumbnail.jpg`) — c'est le geste qu'on ne fait qu'après
 * avoir regardé.
 *
 * Usage
 * -----
 *   php tools/vignettes-cartes.php --sortie=/tmp/cartes             toutes, dans un dossier à regarder
 *   php tools/vignettes-cartes.php --sortie=/tmp/cartes --echantillon=12   un échantillon varié
 *   php tools/vignettes-cartes.php --type=widget --sortie=/tmp/w     une famille
 *   php tools/vignettes-cartes.php --nom=forum --sortie=/tmp/f       un seul nom
 *   php tools/vignettes-cartes.php --poser                           les met en place dans le dépôt
 *   php tools/vignettes-cartes.php --liste                           ce qu'il dessinerait, sans rien faire
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/vignettes.php';

[$o] = nf_options(['sortie' => '', 'poser' => FALSE, 'liste' => FALSE, 'type' => '', 'nom' => '', 'echantillon' => 0]);

$racine = nf_racine();

if (!$o['liste'] && !$o['poser'] && $o['sortie'] === '')
{
    nf_refus('où écrire ? `--sortie=<dossier>` pour regarder d\'abord, `--poser` pour les mettre en place');
}

/* ── Ce qu'on dessine ──────────────────────────────────────────────────────────────────────────── */

/*
 * Les modules et les widgets, PAS les thèmes : un thème se montre, lui — sa vignette est la photo de son accueil
 * (`capturer-apercus`), et une carte à icône n'en dirait rien. C'est la règle que la place de marché affiche :
 * une carte quand l'addon ne se photographie pas bien, une photo quand il se photographie.
 */
const TYPES = [
    'module'        => ['dossier' => 'modules',  'etiquette' => 'Module'],
    'widget'        => ['dossier' => 'widgets',  'etiquette' => 'Widget'],
];

/** Un descriptif coupé au dernier MOT entier : « … y appa… » se lit mal. */
function nf_carte_court(string $texte, int $max): string
{
    $texte = trim($texte);

    if (mb_strlen($texte) <= $max)
    {
        return $texte;
    }

    $coupe = mb_substr($texte, 0, $max);
    $espace = mb_strrpos($coupe, ' ');

    return rtrim($espace ? mb_substr($coupe, 0, $espace) : $coupe, " ,;:.").'…';
}

/** Le texte français d'un `$this->lang('…')`, ou la chaîne elle-même si elle est écrite en clair. */
function nf_carte_texte(string $source, string $cle): ?string
{
    if (preg_match("/'".$cle."'\s*=>\s*\\\$this->lang\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $m)
        || preg_match("/'".$cle."'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $m))
    {
        return str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1]);
    }

    return NULL;
}

$addons = [];

foreach (TYPES as $type => $t)
{
    foreach (nf_addons($type) as $nom => $dossier)
    {
        $relatif = $t['dossier'].'/'.$nom;

        if (isset(NF_VIGNETTES_EXEMPTEES[$relatif]))
        {
            continue;
        }

        if (($o['type'] !== '' && $o['type'] !== $type) || ($o['nom'] !== '' && $o['nom'] !== $nom))
        {
            continue;
        }

        $fichier = $dossier.'/'.$nom.'.php';

        if (!is_file($fichier))
        {
            continue;
        }

        $source = (string) file_get_contents($fichier);

        $addons[$relatif] = [
            'type'      => $type,
            'etiquette' => $t['etiquette'],
            'nom'       => $nom,
            'dossier'   => $dossier,
            'titre'     => nf_carte_texte($source, 'title') ?: ucfirst($nom),
            'texte'     => nf_carte_texte($source, 'description') ?: '',
            'icone'     => nf_carte_texte($source, 'icon') ?: 'fas fa-puzzle-piece',
            'coeur'     => (bool) preg_match("/'core'\s*=>\s*TRUE/i", $source),
        ];
    }
}

ksort($addons);

if ($o['echantillon'] > 0)
{
    // Un échantillon VARIÉ : on prend un addon sur n dans chaque famille, pour que la planche montre
    // des descriptifs courts et longs, des icônes différentes, et les trois familles.
    $par_type = [];

    foreach ($addons as $cle => $a)
    {
        $par_type[$a['type']][$cle] = $a;
    }

    $garde = [];

    foreach ($par_type as $liste)
    {
        $pas = max(1, (int) floor(count($liste) / max(1, (int) ceil($o['echantillon'] / count($par_type)))));
        $i = 0;

        foreach ($liste as $cle => $a)
        {
            if ($i++ % $pas === 0 && count($garde) < $o['echantillon'])
            {
                $garde[$cle] = $a;
            }
        }
    }

    $addons = $garde;
    ksort($addons);
}

if (!$addons)
{
    nf_refus('aucun addon ne correspond');
}

if ($o['liste'])
{
    foreach ($addons as $cle => $a)
    {
        printf("%-28s %-7s %-22s %s\n", $cle, $a['icone'], mb_strimwidth($a['titre'], 0, 22, '…'), mb_strimwidth($a['texte'], 0, 70, '…'));
    }

    nf_ok(sprintf('%d carte(s) à dessiner', count($addons)));
}

/* ── Le dessin ─────────────────────────────────────────────────────────────────────────────────── */

$atelier = nf_temp('atelier');
nf_supprimer($atelier, FALSE);

if (!@mkdir($atelier, 0o777, TRUE) && !is_dir($atelier))
{
    nf_refus("dossier de travail impossible : $atelier");
}

// Les polices et la marque, recopiées à côté du HTML : le rendu ne demande aucune adresse réseau.
foreach ([
    'manrope-400.woff2'  => 'fonts/manrope/xn7gYHE41ni1AdIRggmxSuXd.woff2',
    'manrope-800.woff2'  => 'fonts/manrope/xn7gYHE41ni1AdIRggexSg.woff2',
    'fa-solid.woff2'     => 'fonts/fontawesome/fa-solid-900.woff2',
    'fa-regular.woff2'   => 'fonts/fontawesome/fa-regular-400.woff2',
    'fa-brands.woff2'    => 'fonts/fontawesome/fa-brands-400.woff2',
    'marque.png'         => 'images/branding/icon-reborn-dark.png',
] as $nom => $source)
{
    if (!is_file($racine.'/'.$source))
    {
        nf_refus("fichier de marque introuvable : $source");
    }

    copy($racine.'/'.$source, $atelier.'/'.$nom);
}

// Le code du glyphe FontAwesome, lu dans la feuille embarquée : la carte dessine l'icône elle-même,
// sans charger la feuille entière (qui vise des classes et non un caractère).
$fa = (string) file_get_contents($racine.'/css/icons/fontawesome.min.css');

function nf_carte_glyphe(string $fa, string $icone): array
{
    $nom = preg_replace('/^.*\bfa-([a-z0-9-]+)\s*$/', '$1', trim($icone));
    $familles = ['fab' => 'brands', 'fa-brands' => 'brands', 'far' => 'regular', 'fa-regular' => 'regular'];
    $famille = 'solid';

    foreach ($familles as $prefixe => $f)
    {
        if (str_starts_with(trim($icone), $prefixe.' '))
        {
            $famille = $f;
        }
    }

    /*
     * FontAwesome 6 minifié écrit le code du glyphe dans une variable — `.fa-comments{--fa:"\f086"}` — et il
     * GROUPE les synonymes : `.fa-desktop,.fa-desktop-alt{--fa:"\f390"}`. Le nom cherché peut donc être suivi
     * d'une virgule autant que d'une accolade ; la première version ne voyait que la seconde, et dessinait un
     * point d'interrogation à la place de l'icône du Live Editor.
     */
    $q = preg_quote((string) $nom, '/');

    if (preg_match('/\.fa-'.$q.'(?=[,{])[^{}]*\{[^{}]*--fa:\s*"\\\\([0-9a-f]+)"/i', $fa, $m)
        || preg_match('/\.fa-'.$q.'(?=[,:])[^{}]*\{[^{}]*content:\s*"\\\\([0-9a-f]+)"/i', $fa, $m))
    {
        return [$m[1], $famille];
    }

    return [NULL, $famille];   // l'outil refuse plutôt que de dessiner une carte au point d'interrogation
}

$gabarit = (string) file_get_contents(nf_racine().'/tests/Vignettes/carte.html');
$cartes = [];

foreach ($addons as $cle => $a)
{
    [$glyphe, $famille] = nf_carte_glyphe($fa, $a['icone']);

    if ($glyphe === NULL)
    {
        nf_refus(sprintf("« %s » déclare l'icône « %s », que le FontAwesome embarqué ne connaît pas : la carte dirait « ? »", $cle, $a['icone']));
    }

    $html = strtr($gabarit, [
        '{{TITRE}}'      => htmlspecialchars($a['titre'], ENT_QUOTES, 'UTF-8'),
        '{{TEXTE}}'      => htmlspecialchars(nf_carte_court($a['texte'], 190), ENT_QUOTES, 'UTF-8'),
        '{{ETIQUETTE}}'  => htmlspecialchars($a['etiquette'], ENT_QUOTES, 'UTF-8'),
        // La pastille ne dit QUE ce qui s'ajoute : sur une place de marché, « Extension » n'apprend rien.
        '{{APPOINT}}'    => $a['coeur'] ? '<span class="pastille">Livré avec le cœur</span>' : '',
        // Le glyphe part dans le HTML, pas dans une feuille : une entité, et non une échappée CSS.
        '{{GLYPHE}}'     => '&#x'.$glyphe.';',
        '{{FAMILLE}}'    => $famille === 'solid' ? "'FA Solid'" : ($famille === 'brands' ? "'FA Brands'" : "'FA Regular'"),
        '{{POIDS}}'      => $famille === 'regular' ? '400' : '900',
    ]);

    $fichier = $atelier.'/'.str_replace('/', '-', $cle).'.html';
    file_put_contents($fichier, $html);
    $cartes[$cle] = ['html' => $fichier, 'addon' => $a];
}

// Le pilote : il ouvre chaque carte et l'enregistre en JPEG au format des vignettes.
$liste = [];

foreach ($cartes as $cle => $c)
{
    $liste[] = ['html' => $c['html'], 'jpeg' => $atelier.'/'.str_replace('/', '-', $cle).'.jpg'];
}

file_put_contents($atelier.'/cartes.json', json_encode($liste, JSON_UNESCAPED_SLASHES));

// Le pilote reste DANS le dépôt : Node cherche `node_modules` au-dessus du script, pas du dossier
// courant — recopié dans un dossier temporaire, il ne trouvait pas Playwright.
$sortie = [];
$code = 0;
exec('node '.escapeshellarg(nf_racine().'/tests/Vignettes/pilote.mjs').' '.escapeshellarg($atelier.'/cartes.json')
     .' '.NF_VIGNETTE_LARGEUR.' '.NF_VIGNETTE_HAUTEUR.' 2>&1', $sortie, $code);

if ($code !== 0)
{
    nf_refus("le dessin a échoué :\n  ".implode("\n  ", array_slice($sortie, -8)));
}

/* ── La mise en place ──────────────────────────────────────────────────────────────────────────── */

$faites = $refusees = 0;

foreach ($cartes as $cle => $c)
{
    $jpeg = $atelier.'/'.str_replace('/', '-', $cle).'.jpg';

    if (!is_file($jpeg))
    {
        printf("  %-28s RATÉE\n", $cle);
        $refusees++;
        continue;
    }

    if (($defaut = nf_vignette_defaut($jpeg)) !== NULL)
    {
        printf("  %-28s %s\n", $cle, $defaut);
        $refusees++;
        continue;
    }

    if ($o['poser'])
    {
        $cible = $racine.'/'.$cle.'/images';

        if (!is_dir($cible) && !@mkdir($cible, 0o777, TRUE))
        {
            nf_refus("dossier d'images impossible : $cible");
        }

        copy($jpeg, $cible.'/thumbnail.jpg');
    }

    if ($o['sortie'] !== '')
    {
        if (!is_dir($o['sortie']) && !@mkdir($o['sortie'], 0o777, TRUE))
        {
            nf_refus('dossier de sortie impossible : '.$o['sortie']);
        }

        copy($jpeg, rtrim($o['sortie'], '/\\').'/'.str_replace('/', '-', $cle).'.jpg');
    }

    $faites++;
}

nf_supprimer($atelier, FALSE);

if ($refusees)
{
    nf_echec(sprintf('%d carte(s) dessinée(s), %d refusée(s)', $faites, $refusees));
}

nf_ok(sprintf('%d carte(s) dessinée(s)%s', $faites,
    $o['poser'] ? ', posées dans le dépôt' : ' dans '.$o['sortie']));
