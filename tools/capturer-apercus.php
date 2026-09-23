<?php
declare(strict_types=1);

/**
 * capturer-apercus — produit la VIGNETTE de chaque addon, par capture d'écran réelle.
 *
 * Famille : cible
 *
 * Pourquoi
 * --------
 * La page « Thèmes & addons » de l'administration affiche `images/thumbnail.jpg` quand l'addon en
 * livre un. SEULS LES SEPT THÈMES en livraient un : les soixante modules, les quarante widgets, les
 * six langues et les quatre authentificateurs retombaient sur une icône. Deux conséquences, toutes
 * deux signalées le 2026-09-22 : les cartes d'une même ligne n'avaient pas la même
 * hauteur, et l'on ne voyait pas à quoi ressemble un addon avant de l'activer.
 *
 * Une vignette dessinée à la main serait un troisième inventaire à tenir à jour. Celle-ci est une
 * PHOTO du produit tel qu'il tourne : elle ne peut pas mentir, et se refait en une commande.
 *
 * Les cinq recettes
 * -----------------
 *   thème          la page d'accueil — mais SEULEMENT pour le thème actuellement servi (voir plus bas)
 *   module         sa page publique `/fr/<nom>`, découpée sur le CONTENU (`.module-<nom>`) pour que
 *                  la vignette montre le module et non le bandeau du site, identique partout ;
 *                  à défaut de page publique, sa page d'administration, prise en entier
 *   widget         la zone `.widget-<nom>` découpée dans une page qui le porte — et si aucune page
 *                  ne le porte, un BANC D'ESSAI le pose le temps d'une photo, puis défait tout
 *   langue         la page d'accueil dans cette langue
 *   authentificateur   AUCUNE : son bouton ne vit que dans la fenêtre de connexion, et son icône
 *                  est déjà le logo de la marque
 *
 * L'outil DIT ce qu'il n'a pas pu capturer, et pourquoi. Une vignette absente reste une icône :
 * c'est moins grave qu'une vignette fausse.
 *
 * LA CAPTURE SUIT LA BOÎTE. Une fenêtre de 800 px ne voit que le haut de la page : un widget placé
 * dans une colonne, sous la ligne de flottaison, tombait HORS du cadre. Le découpage échouait alors
 * en silence et la vignette montrait la page entière — deux widgets, `news` et `members`, avaient
 * ainsi la même image, celle de l'accueil. La fenêtre s'agrandit donc jusqu'à contenir la boîte, et
 * si elle n'y parvient pas, l'outil REFUSE plutôt que de rendre une image qui parle d'autre chose.
 *
 * ET UNE VIGNETTE VIDE EST REFUSÉE. Un widget posé seul sur une page, sans données, rend un cadre,
 * un titre et des lignes de séparation — rien à lire. La première série en a produit une trentaine,
 * des rectangles sombres moins parlants que l'icône qu'ils remplaçaient.
 *
 * Mesurer la LUMINANCE ne les attrape pas : ces cadres affichent 20 % de pixels écartés de la
 * couleur dominante, autant qu'une vraie capture, à cause des lignes. Ce qui les distingue, c'est
 * qu'il n'y a RIEN À LIRE. L'outil compte donc les caractères réellement affichés dans la zone
 * photographiée et refuse en dessous de 15. Le seuil est BAS à dessein : une bannière publicitaire,
 * un fil d'Ariane, un pied de page tiennent en vingt caractères et sont parfaitement lisibles. Ce
 * qu'il écarte, ce sont les cadres qui n'ont vraiment rien — ceux-là ne rendent même pas d'élément.
 *
 * Et les widgets se photographient sur une installation PEUPLÉE — la démonstration — où ils ont
 * quelque chose à montrer.
 *
 * POURQUOI IL NE BASCULE PAS LE THÈME. La première version écrivait le nom du thème dans les
 * réglages avant de rendre la page. Deux raisons l'en empêchent, et toutes deux donnent le même
 * résultat : SEPT vignettes identiques, toutes dans le thème du site. D'abord le réglage ne
 * s'appelle pas `nf_theme` mais `nf_default_theme`. Ensuite, et surtout, un thème NON INSTALLÉ ne
 * se charge pas : le cœur retombe sur nebula, discrètement, avec une ligne dans le journal.
 * Photographier un thème demande donc de l'installer et de l'activer — une manipulation qui change
 * le site pour de vrai. L'outil s'y refuse : il ne photographie que le thème SERVI, et nomme les
 * autres.
 *
 * Usage
 * -----
 *   php tools/capturer-apercus.php                    tout
 *   php tools/capturer-apercus.php --type=module      une famille
 *   php tools/capturer-apercus.php --nom=forum        un seul addon
 *   php tools/capturer-apercus.php --liste            ce qui serait capturé, sans rien faire
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';
require __DIR__.'/lib/banc.php';

[$o] = nf_options(['type' => '', 'nom' => '', 'liste' => FALSE, 'port' => 0, 'qualite' => 84]);

const LARGEUR = 1280;
const HAUTEUR = 800;

// La vignette est affichée sur environ 300 px de large dans une carte, et près du double dans la
// fiche de la place de marché — soit 1 000 px réels sur un écran à deux points par pixel. 960x600
// donne du net partout sans peser : une quarantaine de kilo-octets pièce.
const V_LARGEUR = 960;
const V_HAUTEUR = 600;

if (!extension_loaded('gd'))
{
    nf_refus('extension GD absente : impossible de redimensionner les captures');
}

/**
 * Les addons à couvrir, par type, avec leur dossier.
 *
 * @return list<array{type: string, nom: string, dossier: string}>
 */
function inventaire(string $racine): array
{
    $sortie = [];

    foreach (['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes', 'addon' => 'addons'] as $type => $dossier)
    {
        foreach (glob($racine.'/'.$dossier.'/*', GLOB_ONLYDIR) ?: [] as $chemin)
        {
            $nom = basename($chemin);

            if (!is_file($chemin.'/'.$nom.'.php'))
            {
                continue;
            }

            // Les addons du dossier `addons/` se distinguent par leur nom : langues et connecteurs.
            $vrai = $type;

            if ($type === 'addon')
            {
                $vrai = str_starts_with($nom, 'language_') ? 'langue' : 'authentificateur';
            }

            $sortie[] = ['type' => $vrai, 'nom' => $nom, 'dossier' => $chemin];
        }
    }

    return $sortie;
}

$racine = nf_racine();
$addons = inventaire($racine);

// L'adresse publique du site, telle que l'installation la connaît : le lecteur de flux doit lire un
// flux SERVI, pas celui du serveur d'épreuve qui ne peut pas se répondre à lui-même.
$url_publique = '';

if (is_file($racine.'/config/url.php'))
{
    $url = [];
    require $racine.'/config/url.php';
    $url_publique = rtrim((string) ($url['site'] ?? ''), '/');
}

if ($o['type'] !== '')
{
    $addons = array_values(array_filter($addons, static fn (array $a): bool => $a['type'] === $o['type']));
}

if ($o['nom'] !== '')
{
    $addons = array_values(array_filter($addons, static fn (array $a): bool => $a['nom'] === $o['nom']));
}

if (!$addons)
{
    nf_refus('aucun addon ne correspond à --type= / --nom=');
}

if ($o['liste'])
{
    $par_type = [];

    foreach ($addons as $a)
    {
        $par_type[$a['type']][] = $a['nom'];
    }

    foreach ($par_type as $type => $noms)
    {
        printf("%-16s %3d : %s\n", $type, count($noms), implode(', ', array_slice($noms, 0, 12)).(count($noms) > 12 ? '…' : ''));
    }

    nf_ok(sprintf('%d addon(s) seraient capturés', count($addons)));
}

printf("%d addon(s) à capturer.\n\n", count($addons));

$manques = [];

$db      = nf_connexion();
$session = nf_session_admin($db);

// La sonde est un fichier VIDE la plupart du temps : seule la mesure d'un widget y écrit, le temps
// d'un rendu. Le serveur le relit à chaque requête.
$fichier_sonde = nf_temp('sonde-apercu.js');
file_put_contents($fichier_sonde, '');

$serveur = nf_serveur(nf_port($o['port']), [
    'NF_OUTIL_CONSENT'  => 'all',
    'NF_OUTIL_SESSION'  => $session,
    'NF_OUTIL_SONDE'    => $fichier_sonde,
    'NF_OUTIL_SONDE_OU' => 'body',
]);

// Le thème SERVI : le seul que l'outil sait photographier sans changer le site.
$theme_servi = '';
$r = $db->query("SELECT `value` FROM `nf_settings` WHERE `name` = 'nf_default_theme'");

if ($ligne = $r->fetch_assoc())
{
    $theme_servi = (string) $ligne['value'];
}

printf("Thème servi : %s\n\n", $theme_servi !== '' ? $theme_servi : '(inconnu)');

/** Vrai si la page répond vraiment, redirections suivies. */
function repond(string $url): bool
{
    return nf_http($url, ['timeout' => 20])['code'] === 200;
}

/**
 * LE BANC D'ESSAI.
 *
 * Trente-six des quarante widgets ne sont posés sur AUCUNE page du site : il n'y a donc rien à
 * photographier, et une carte sans aperçu reste une carte sans aperçu. Le banc crée une instance
 * du widget, la place dans une zone d'une SEULE page (`contact/*`, pour ne toucher à aucune autre),
 * et rend `$nettoyer` — qu'il faut appeler ensuite, quoi qu'il arrive.
 *
 * Le widget est créé AVEC des réglages d'exemple quand il en demande (cf. REGLAGES) : sept d'entre
 * eux ne rendent littéralement RIEN sans configuration — un bloc libre sans texte, un lecteur de
 * flux sans adresse, un effet saisonnier sans effet choisi. Les photographier vides aurait été
 * fidèle à l'instant de la pose, et inutile à qui regarde la place de marché : ce qu'on veut
 * montrer, c'est ce que le widget SAIT faire.
 *
 * @return array{0: string, 1: callable}
 */
function banc_essai(mysqli $db, string $theme, string $widget, string $base, string $publique): array
{
    $reglages = REGLAGES[$widget] ?? [];

    // `__type` n'est pas un reglage du widget : il dit LAQUELLE de ses vues photographier.
    $type = $reglages['__type'] ?? 'index';
    unset($reglages['__type'], $reglages['__page_entiere']);

    $json = $reglages === []
        ? NULL
        : str_replace(['URL_SITE', 'URL_PUBLIQUE'], [rtrim($base, '/'), $publique], (string) json_encode($reglages));

    // La pose et le retrait vivent dans `lib/banc.php`, partagés avec `check-widget-contract`.
    $nettoyer = nf_banc_widget($db, $theme, $widget, $type, $json);

    return ['/fr/contact', $nettoyer];
}

/**
 * La boîte d'un élément dans la page, ou NULL s'il n'y est pas.
 *
 * @return array{x: int, y: int, w: int, h: int}|NULL
 */
function boite(string $url, string $selecteur, string $fichier): ?array
{
    $sonde = strtr(<<<'JS'
(function(){
    var e = document.querySelector(SELECTEUR);
    var bal = document.createElement('div');
    bal.id = 'nf-boite';
    if (e) {
        var r = e.getBoundingClientRect();
        var t = (e.innerText || '').replace(/\s+/g, ' ').trim();
        bal.setAttribute('data-verdict', JSON.stringify({ x: Math.round(r.left), y: Math.round(r.top), w: Math.round(r.width), h: Math.round(r.height), texte: t.length }));
    } else {
        bal.setAttribute('data-verdict', JSON.stringify({ absent: true }));
    }
    document.body.appendChild(bal);
})();
JS, ['SELECTEUR' => json_encode($selecteur) ?: '""']);

    // Le serveur injecte le CONTENU de ce fichier dans chaque page : on l'écrit juste avant de
    // mesurer, puis on le vide pour que les CAPTURES qui suivent ne portent aucune sonde.
    file_put_contents($fichier, $sonde);

    $verdict = nf_sonde_verdict(nf_chrome_dom($url, [
        'largeur' => LARGEUR,
        'hauteur' => HAUTEUR,
        'budget'  => 15000,
    ]), 'nf-boite');

    file_put_contents($fichier, '');

    if ($verdict === NULL || !empty($verdict['absent']) || ($verdict['w'] ?? 0) < 40 || ($verdict['h'] ?? 0) < 30)
    {
        return NULL;
    }

    return ['x' => (int) $verdict['x'], 'y' => (int) $verdict['y'], 'w' => (int) $verdict['w'],
        'h' => (int) $verdict['h'], 'texte' => (int) ($verdict['texte'] ?? 0)];
}

// Un widget qui n'affiche que son titre n'a rien à montrer : il faut de quoi LIRE.
const TEXTE_MINIMUM = 15;

/**
 * Les RÉGLAGES D'EXEMPLE, pour les widgets qui ne rendent rien sans configuration.
 *
 * Ce ne sont pas des valeurs par défaut du produit : ce sont celles d'une pose réussie, choisies
 * pour que la vignette montre le widget à son avantage. `URL_SITE` est remplacé par l'adresse du
 * serveur d'épreuve, pour que le lecteur de flux lise le flux du site lui-même.
 *
 * @var array<string, array<string, mixed>>
 */
const REGLAGES = [
    'about' => [
        'display_panel'      => 'oui',
        'display_teamname'   => 'oui',
        'teamname_align'     => 'text-center',
        'display_biographie' => 'oui',
        'biographie_align'   => 'text-start',
        'display_logo'       => 'non',
        'display_date'       => 'oui',
        'display_type'       => 'oui',
    ],
    'html' => [
        'content' => 'Ce widget affiche le texte de votre choix : une annonce, un règlement, un encart de bienvenue, les horaires de la prochaine soirée.',
    ],
    'rss' => [
        // L'adresse PUBLIQUE, et non celle du serveur d'épreuve : celui-ci est mono-processus, il
        // ne peut pas répondre à sa propre requête pendant qu'il rend la page. Le widget attendait
        // trois secondes puis renonçait, et la vignette était vide.
        'url'          => 'URL_PUBLIQUE/fr/feeds/news',
        'title'        => 'Actualités du site',
        'count'        => 4,
        'ttl'          => 900,
        'show_date'    => 1,
        'show_summary' => 1,
    ],
    'seasonal' => [
        // Cet effet n'est pas un bloc : c'est une couche de particules posée sur TOUTE la page, dans
        // un conteneur sans dimensions. Il n'y a donc rien à découper — on photographie la page
        // entière, effet compris, ce qui est exactement ce qu'il fait.
        '__page_entiere' => TRUE,
        'effect'         => 'snow',
        'density'        => 'high',
        'from'           => '',
        'to'             => '',
    ],
    'ads' => [
        'placement' => 'sidebar',
    ],
    'partners' => [
        'display_number' => 3,
        'display_height' => 140,
        'display_style'  => 'light',
        'id'             => 0,
    ],
    'events' => [
        // `index` rend un calendrier dessine PAR JAVASCRIPT apres coup : la sonde n'y lisait que le
        // titre. `upcoming` rend la liste des prochains evenements cote serveur.
        '__type'   => 'upcoming',
        'type_id'  => 0,
        'event_id' => 0,
    ],
];

/**
 * Découpe puis réduit une capture, et l'écrit en JPEG.
 *
 * Rend 1.0 si la vignette est écrite, et sinon MOINS la part d'encre mesurée — de quoi dire
 * à l'appelant à quel point l'image était vide.
 */
function vignette(string $png, string $sortie, ?array $boite, int $qualite): float
{
    $source = @imagecreatefrompng($png);

    if ($source === FALSE)
    {
        return 0.0;
    }

    if ($boite !== NULL)
    {
        $x = max(0, $boite['x'] - 8);
        $y = max(0, $boite['y'] - 8);
        $w = min(imagesx($source) - $x, $boite['w'] + 16);
        $h = min(imagesy($source) - $y, $boite['h'] + 16);

        // HORS CADRE : on refuse. Continuer sans découper rendrait la page entière à la place du
        // widget, ce qui ressemble à une vignette et n'en est pas une.
        if ($w < 40 || $h < 30)
        {
            return 0.0;
        }

        $coupe = imagecreatetruecolor($w, $h);
        imagecopy($coupe, $source, 0, 0, $x, $y, $w, $h);
        $source = $coupe;
    }

    /*
     * LES PROPORTIONS SONT TENUES. La première version étirait la région capturée jusqu'à 640x400
     * quelles que soient ses dimensions : une zone large et basse — un widget en bandeau, une barre
     * de résultats — s'en trouvait étirée EN HAUTEUR, textes compris. Signalé le
     * 2026-09-22 : « certaines sont déformées ».
     *
     * Deux cas, et aucune déformation dans l'un ni dans l'autre — on ROGNE, on n'étire jamais :
     *   - capture plus HAUTE que la vignette : on garde le HAUT, c'est là que se joue l'identité
     *     d'un écran, et le bas part ;
     *   - capture plus LARGE : on garde la GAUCHE, où vivent le titre et la première colonne, et la
     *     droite part. L'encadrer sur un fond neutre aurait été plus fidèle, mais un widget large
     *     et bas n'y laissait qu'un ruban entre deux bandes vides : moins lisible que la coupe.
     */
    $sw    = imagesx($source);
    $sh    = imagesy($source);
    $ratio = V_LARGEUR / V_HAUTEUR;

    $cible = imagecreatetruecolor(V_LARGEUR, V_HAUTEUR);

    if ($sw / max(1, $sh) <= $ratio)
    {
        $utile = min($sh, (int) round($sw / $ratio));
        imagecopyresampled($cible, $source, 0, 0, 0, 0, V_LARGEUR, V_HAUTEUR, $sw, $utile);
    }
    else
    {
        $utile = max(1, (int) round($sh * $ratio));
        imagecopyresampled($cible, $source, 0, 0, 0, 0, V_LARGEUR, V_HAUTEUR, $utile, $sh);
    }

    $ok = imagejpeg($cible, $sortie, $qualite);


    return $ok ? 1.0 : 0.0;
}

$faits = [];

foreach ($addons as $a)
{
    $nom     = $a['nom'];
    $dossier = $a['dossier'];
    $page    = NULL;
    $sel     = NULL;

    if ($a['type'] === 'theme')
    {
        if ($nom !== $theme_servi)
        {
            $manques[] = [$nom, 'ce n\'est pas le thème servi : l\'activer sur le site, puis relancer avec --nom='.$nom];
            printf("  %-24s —  %s\n", $nom, 'pas le thème servi');
            continue;
        }

        $page = '/fr';
    }
    elseif ($a['type'] === 'langue')
    {
        $page = '/'.substr($nom, strlen('language_'));
    }
    elseif ($a['type'] === 'authentificateur')
    {
        // Leur bouton ne vit que dans la FENETRE de connexion, qu'un rendu simple n'ouvre pas :
        // `/fr/user/login` redirige vers l'accueil, et la capture montrait donc l'accueil pour les
        // quatre connecteurs. Leur icone est le logo de la marque — plus parlante qu'une capture.
        $manques[] = [$nom, 'son bouton ne s’affiche que dans la fenêtre de connexion ; son icône est son logo'];
        printf("  %-24s —  %s
", $nom, 'pas de page a photographier');
        continue;
    }
    elseif ($a['type'] === 'widget')
    {
        $sel  = '.widget-'.$nom;
        $page = '/fr';
    }
    elseif ($a['type'] === 'module')
    {
        // Deux modules ne suivent pas la regle « /fr/admin/<nom> » : leur page d'administration
        // porte un autre chemin. Les autres modules sans page — `reactions`, `revisions`, `tools` —
        // n'en ont pas du tout : ils travaillent en arriere-plan, et l'outil le dit.
        $exceptions = [
            'access'      => '/fr/admin/access/matrix',
            'live_editor' => '/fr/admin/live-editor',
        ];

        $page = $exceptions[$nom] ?? '/fr/'.$nom;

        if (!isset($exceptions[$nom]) && !repond($serveur->base.$page))
        {
            $page = '/fr/admin/'.$nom;
        }

        // Sur une page PUBLIQUE, le contenu du module vit dans le widget `module`. On y découpe :
        // sans cela, cinquante vignettes montrent le meme bandeau et la meme colonne laterale, et
        // l'on ne distingue plus un module d'un autre. Les pages d'administration, elles, sont
        // prises en entier : elles n'ont pas ce probleme et leur interieur EST le sujet.
        // Le CONTENU, jamais le chrome. Sur une page publique il vit dans `.module-<nom>` ; sur une
        // page d'administration, dans le `<main class="nf-main">` du theme. Sans ce decoupage,
        // cinquante vignettes montrent le meme bandeau, ou la meme barre laterale, et l'on ne
        // distingue plus un module d'un autre.
        $sel = str_starts_with($page, '/fr/admin')
            ? '.nf-main'
            : '.module-'.str_replace('_', '-', $nom);
    }
    else
    {
        $page = '/fr/'.$nom;
    }

    if (!repond($serveur->base.$page))
    {
        $manques[] = [$nom, 'aucune page ne répond ('.$page.')'];
        printf("  %-24s —  %s\n", $nom, 'page absente');
        continue;
    }

    $boite    = $sel !== NULL ? boite($serveur->base.$page, $sel, $fichier_sonde) : NULL;
    $nettoyer = NULL;

    // Pas trouvé sur le site : on le pose nous-mêmes, le temps d'une photo.
    if ($sel !== NULL && $boite === NULL && $a['type'] === 'widget')
    {
        [$page, $nettoyer] = banc_essai($db, $theme_servi, $nom, $serveur->base, $url_publique);

        // Certains widgets ne sont pas des blocs : ils se posent SUR la page. On la photographie
        // en entier, ce qui est exactement ce qu'ils font.
        if (!empty(REGLAGES[$nom]['__page_entiere']))
        {
            $sel   = NULL;
            $boite = NULL;
        }
        else
        {
            $boite = boite($serveur->base.$page, $sel, $fichier_sonde);
        }
    }

    if ($sel !== NULL && $boite === NULL)
    {
        if ($nettoyer !== NULL)
        {
            $nettoyer();
        }

        $manques[] = [$nom, 'ne rend rien, même posé seul sur une page'];
        printf("  %-24s —  %s\n", $nom, 'ne rend rien');
        continue;
    }

    /*
     * LA GÉOMÉTRIE DE LA DÉCOUPE, apprise en regardant la planche des vignettes produites.
     *
     * Une découpe qui couvre presque la fenêtre n'est pas l'aperçu d'un widget : c'est la page
     * entière, et elle ne dit rien de lui. Elle se juge sur la boîte D'ORIGINE, avant tout
     * élargissement — sans quoi l'élargissement ci-dessous la déclencherait lui-même.
     */
    if ($a['type'] === 'widget' && $boite !== NULL && $boite['w'] * $boite['h'] > 0.75 * LARGEUR * HAUTEUR)
    {
        if ($nettoyer !== NULL)
        {
            $nettoyer();
        }

        $manques[] = [$nom, sprintf('couvre presque toute la page (%d x %d) : ce serait la page, pas le widget', $boite['w'], $boite['h'])];
        printf("  %-24s —  %s\n", $nom, 'toute la page');
        continue;
    }

    /*
     * UN WIDGET MINCE EST ÉLARGI, PAS REFUSÉ.
     *
     * Un menu, un pied de page, une bannière publicitaire tiennent en quarante pixels de haut :
     * réduits à la vignette, ils donnaient un ruban illisible, et l'outil les écartait. On les
     * montre EN CONTEXTE — la découpe s'élargit autour d'eux jusqu'aux proportions de la vignette,
     * et l'on voit le widget à sa place dans la page. C'est plus parlant qu'une icône.
     */
    if ($a['type'] === 'widget' && $boite !== NULL)
    {
        // L'elargissement est PLAFONNE. Sans plafond, un widget large de 1 130 px reclamait 706 px
        // de haut pour tenir les proportions : la vignette montrait alors la page entiere, barre
        // de navigation et colonne laterale comprises, et l'on ne distinguait plus un widget d'un
        // autre. Au-dela de 280 px, on prefere rogner la droite (cf. vignette()).
        $besoin = min((int) round($boite['w'] / (V_LARGEUR / V_HAUTEUR)), max($boite['h'], 280));

        if ($boite['h'] < $besoin)
        {
            $boite['y'] = max(0, $boite['y'] - (int) (($besoin - $boite['h']) / 2));
            $boite['h'] = $besoin;
        }
    }

    // Un cadre sans contenu fait une vignette sombre et muette, pire que l'icône qu'elle remplace.
    if ($boite !== NULL && $boite['texte'] < TEXTE_MINIMUM)
    {
        if ($nettoyer !== NULL)
        {
            $nettoyer();
        }

        $manques[] = [$nom, sprintf('rien à montrer ici : %d caractère(s) affiché(s), il en faut %d', $boite['texte'], TEXTE_MINIMUM)];
        printf("  %-24s —  %s (%d car.)\n", $nom, 'rien à montrer', $boite['texte']);
        continue;
    }

    $png = nf_temp('apercu-'.$nom.'.png');

    // La fenêtre doit contenir la boîte, sinon il n'y a rien à découper à cet endroit.
    $hauteur = $boite === NULL ? HAUTEUR : min(3200, max(HAUTEUR, $boite['y'] + $boite['h'] + 40));

    $capture = nf_chrome_capture($serveur->base.$page, $png, ['largeur' => LARGEUR, 'hauteur' => $hauteur, 'budget' => 20000]) && is_file($png);

    if ($nettoyer !== NULL)
    {
        $nettoyer();
    }

    if (!$capture)
    {
        $manques[] = [$nom, 'la capture a échoué'];
        printf("  %-24s —  %s\n", $nom, 'capture KO');
        continue;
    }

    if (!is_dir($dossier.'/images'))
    {
        mkdir($dossier.'/images', 0775, TRUE);
    }

    $cible = $dossier.'/images/thumbnail.jpg';

    if (vignette($png, $cible, $boite, (int) $o['qualite']) < 1.0)
    {
        $manques[] = [$nom, 'le redimensionnement a échoué'];
        printf("  %-24s —  %s\n", $nom, 'vignette KO');
        @unlink($png);
        continue;
    }

    @unlink($png);
    $faits[] = $nom;
    printf("  %-24s ✓  %s  (%d Ko)\n", $nom, $page, (int) round((int) filesize($cible) / 1024));
}

echo "\n";
printf("%d vignette(s) écrite(s), %d non capturée(s).\n", count($faits), count($manques));

if ($manques)
{
    echo "\nSans vignette — la carte garde son icône :\n";

    foreach ($manques as [$nom, $raison])
    {
        printf("  %-24s %s\n", $nom, $raison);
    }

    echo "\n";
}

if (!$faits)
{
    nf_echec('aucune vignette produite');
}

nf_ok(sprintf('%d vignette(s) sur %d addon(s)', count($faits), count($addons)));
