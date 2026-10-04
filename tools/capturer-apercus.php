<?php
declare(strict_types=1);

/**
 * capturer-apercus — produit la VIGNETTE de chaque addon, par capture d'écran réelle.
 *
 * Famille : cible
 * Diffusion : publique
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
 * Les recettes
 * ------------
 *   thème          la page d'accueil, vue par un VISITEUR — le thème servi, et les autres avec
 *                  `--basculer-theme` (voir plus bas)
 *   module         sa page publique `/fr/<nom>`, découpée sur le CONTENU (`.module-<nom>`) pour que
 *                  la vignette montre le module et non le bandeau du site, identique partout ;
 *                  à défaut de page publique, sa page d'administration, prise en entier
 *   widget         la zone `.widget-<nom>` découpée dans une page qui le porte — et si aucune page
 *                  ne le porte, un BANC D'ESSAI le pose le temps d'une photo, puis défait tout
 *   langue         la page d'accueil dans cette langue
 *
 * Le FORMAT (960 × 600) et les addons EXEMPTÉS — ceux qui n'ont rien à photographier, chacun avec
 * sa raison — vivent dans `lib/vignettes.php`, que `check-vignettes` et `check-marketplace` lisent
 * aussi : une vignette absente hors de cette liste est une faute, et ces deux contrôles la voient.
 *
 * L'outil DIT ce qu'il n'a pas pu capturer, et pourquoi. Il n'écrit jamais une vignette fausse :
 * mieux vaut une faute que `check-vignettes` signale qu'une photo qui parle d'autre chose.
 *
 * LA PHOTO EST PRISE À DEUX POINTS PAR PIXEL. La page est mise en page à 1 280 px, comme avant, mais
 * l'image en compte 2 560 : un widget de 400 px découpé puis porté à 960 restait flou — le texte d'un
 * encart TeamSpeak se lisait à peine (2026-10-04). Toute zone d'au moins 480 px de large est
 * désormais RÉDUITE à la vignette, jamais agrandie.
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
 * LES THÈMES, ET POURQUOI LA BASCULE EST UN CHOIX. La première version écrivait le nom du thème dans
 * les réglages avant de rendre la page, et rendait SEPT vignettes identiques : le réglage ne
 * s'appelle pas `nf_theme` mais `nf_default_theme`, et surtout un thème NON INSTALLÉ ne se charge
 * pas — le cœur retombe sur nebula, discrètement. Par défaut, l'outil ne photographie donc que le
 * thème SERVI. Avec `--basculer-theme`, il fait des autres le thème par défaut le temps d'une photo,
 * s'ils sont INSTALLÉS sur ce site (sinon il les nomme), et rétablit le thème d'origine quoi qu'il
 * arrive (`nf_theme_temporaire`). Les visiteurs du site voient la bascule : c'est une option pour un
 * site jetable ou un atelier, jamais pour un site en service. Un second outil, `capture-vignettes`,
 * faisait la même bascule pour les seuls thèmes, mais en 480 × 270 : il a été retiré le 2026-10-04,
 * et les quatre thèmes qu'il avait produits refaits ici.
 *
 * Un thème se photographie en VISITEUR : la barre d'administration, le nom du compte et le bouton
 * « Admin » ne sont pas le thème. Les modules et les widgets, eux, se photographient connecté —
 * certaines de leurs pages n'existent que pour un administrateur.
 *
 * Usage
 * -----
 *   php tools/capturer-apercus.php                    tout
 *   php tools/capturer-apercus.php --type=module      une famille
 *   php tools/capturer-apercus.php --nom=forum        un seul nom (le module ET le widget forum)
 *   php tools/capturer-apercus.php --type=theme --basculer-theme   chaque thème installé (site jetable)
 *   php tools/capturer-apercus.php --liste            ce qui serait capturé, sans rien faire
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';
require __DIR__.'/lib/banc.php';
require __DIR__.'/lib/vignettes.php';

[$o] = nf_options(['type' => '', 'nom' => '', 'liste' => FALSE, 'port' => 0, 'qualite' => 84, 'basculer-theme' => FALSE]);

const LARGEUR = 1280;
const HAUTEUR = 800;

// Les points par pixel de la capture (cf. l'en-tête) : la mise en page reste celle d'un écran de
// 1 280 px, l'image en a deux fois plus.
const ECHELLE = 2;

// Le format de la vignette : `lib/vignettes.php`, commun aux trois outils qui la lisent.
const V_LARGEUR = NF_VIGNETTE_LARGEUR;
const V_HAUTEUR = NF_VIGNETTE_HAUTEUR;

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

    // Les thèmes EN DERNIER : ils se photographient en visiteur, sur un serveur sans session, qui
    // remplace celui des modules et des widgets une fois ceux-ci faits.
    usort($sortie, static fn (array $a, array $b): int => ($a['type'] === 'theme') <=> ($b['type'] === 'theme'));

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

    $exemptes = array_filter($addons, static fn (array $a): bool => isset(NF_VIGNETTES_EXEMPTEES[nf_relatif($a['dossier'])]));

    if ($exemptes)
    {
        printf("\ndont %d exempté(s), qui ne seront pas photographiés (lib/vignettes.php) :\n", count($exemptes));

        foreach ($exemptes as $a)
        {
            printf("  %-24s %s\n", $a['nom'], NF_VIGNETTES_EXEMPTEES[nf_relatif($a['dossier'])]);
        }

        echo "\n";
    }

    nf_ok(sprintf('%d addon(s) seraient capturés', count($addons) - count($exemptes)));
}

printf("%d addon(s) à capturer.\n\n", count($addons));

$manques = [];

$db      = nf_connexion();
$session = nf_session_admin($db);

// La sonde est un fichier VIDE la plupart du temps : seule la mesure d'un widget y écrit, le temps
// d'un rendu. Le serveur le relit à chaque requête.
$fichier_sonde = nf_temp('sonde-apercu.js');
file_put_contents($fichier_sonde, '');

/**
 * Le serveur d'épreuve : connecté en administrateur, ou en VISITEUR (sans session) pour les thèmes.
 *
 * Le serveur des thèmes fige aussi la page (`NF_OUTIL_FIGER`) : carrousel arrêté sur sa première
 * diapositive, transitions à zéro. Sans cela, le menu d'`extend` sortait une fois sur deux à moitié
 * de sa transition, sombre sur sombre. Pas pour les widgets : la neige de `seasonal` est une
 * animation, et c'est elle qu'on photographie.
 */
function serveur_apercus(int $port, string $session, string $sonde): NfServeur
{
    return nf_serveur($port, array_filter([
        'NF_OUTIL_CONSENT'  => 'all',
        'NF_OUTIL_SESSION'  => $session,
        'NF_OUTIL_FIGER'    => $session === '' ? '1' : '',
        'NF_OUTIL_SONDE'    => $sonde,
        'NF_OUTIL_SONDE_OU' => 'body',
    ], static fn (string $v): bool => $v !== ''));
}

$port     = nf_port($o['port']);
$serveur  = serveur_apercus($port, $session, $fichier_sonde);
$visiteur = FALSE;

// Le thème SERVI : le seul que l'outil photographie sans changer le site, hors `--basculer-theme`.
$theme_servi = nf_reglage($db, 'nf_default_theme') ?? '';
$installes   = nf_themes_installes($db);

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
 * Certains lisent un réglage du SITE plutôt que les leurs — les réseaux sociaux lisent les adresses
 * de « Réglages → Réseaux sociaux » : `__reglages_site` les pose le temps de la photo, et `$nettoyer`
 * remet les valeurs d'origine.
 *
 * @return array{0: string, 1: callable}
 */
function banc_essai(mysqli $db, string $theme, string $widget, string $base, string $publique): array
{
    $reglages = REGLAGES[$widget] ?? [];

    // `__type` n'est pas un reglage du widget : il dit LAQUELLE de ses vues photographier. Les autres
    // clés en `__` sont des consignes de l'outil (cf. REGLAGES) : elles ne partent pas dans la base.
    $type     = $reglages['__type'] ?? 'index';
    $site     = $reglages['__reglages_site'] ?? [];
    $colonne  = $reglages['__colonne'] ?? NULL;
    $reglages = array_filter($reglages, static fn (string $cle): bool => !str_starts_with($cle, '__'), ARRAY_FILTER_USE_KEY);

    $json = $reglages === []
        ? NULL
        : str_replace(['URL_SITE', 'URL_PUBLIQUE'], [rtrim($base, '/'), $publique], (string) json_encode($reglages));

    // Chaque réglage du site revient à son état d'avant — y compris l'absence de sa ligne —, et
    // une interruption ne laisse pas les adresses d'exemple derrière elle (`nf_reglage_temporaire`).
    $remettre = [];

    foreach ($site as $nom => $valeur)
    {
        $remettre[] = nf_reglage_temporaire($db, $nom, $valeur);
    }

    // La pose et le retrait vivent dans `lib/banc.php`, partagés avec `check-widget-contract`.
    $retirer = nf_banc_widget($db, $theme, $widget, $type, $json, taille: $colonne);

    $nettoyer = static function () use ($retirer, $remettre): void {
        $retirer();

        foreach ($remettre as $remise)
        {
            $remise();
        }
    };

    return ['/fr/contact', $nettoyer];
}

/**
 * La boîte d'un élément dans la page, ou NULL s'il n'y est pas.
 *
 * Plusieurs sélecteurs donnent la boîte qui les ENGLOBE tous : le menu de la recherche instantanée
 * est posé en absolu sous son champ, et la boîte du seul widget l'ignorait. Une `$scene` — ce qu'un
 * visiteur ferait, taper dans le champ — se joue d'abord, et l'on mesure trois secondes plus tard,
 * l'état qu'elle a produit.
 *
 * @param  list<string> $selecteurs
 * @return array{x: int, y: int, w: int, h: int, texte: int}|NULL
 */
function boite(string $url, array $selecteurs, string $fichier, string $scene = ''): ?array
{
    $sonde = strtr(<<<'JS'
(function(){
    function mesurer() {
        var x1 = Infinity, y1 = Infinity, x2 = -Infinity, y2 = -Infinity, texte = 0, vu = false;
        SELECTEURS.forEach(function (s) {
            var e = document.querySelector(s);
            if (!e) { return; }
            var r = e.getBoundingClientRect();
            if (r.width < 1 || r.height < 1) { return; }
            vu = true;
            x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom);
            texte += (e.innerText || '').replace(/\s+/g, ' ').trim().length;
        });
        var bal = document.createElement('div');
        bal.id = 'nf-boite';
        bal.setAttribute('data-verdict', JSON.stringify(vu
            ? { x: Math.round(x1), y: Math.round(y1), w: Math.round(x2 - x1), h: Math.round(y2 - y1), texte: texte }
            : { absent: true }));
        document.body.appendChild(bal);
    }
    SCENE
    if (SCENE_JOUEE) { setTimeout(mesurer, 3000); } else { mesurer(); }
})();
JS, [
        'SELECTEURS'  => json_encode(array_values($selecteurs)) ?: '[]',
        'SCENE_JOUEE' => $scene !== '' ? 'true' : 'false',
        'SCENE'       => $scene,
    ]);

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
 * Les clés en `__` sont des consignes de l'OUTIL, jamais enregistrées comme réglages :
 *   __type            la vue du widget à poser (défaut : `index`)
 *   __page_entiere    photographier toute la page plutôt que la boîte du widget
 *   __colonne         la largeur de sa colonne sur le banc (`col-4`), au lieu de toute la zone
 *   __reglages_site   des réglages du SITE posés le temps de la photo, puis remis
 *   __scene           du JavaScript joué dans la page avant la mesure ET la photo — un geste de visiteur
 *   __mesurer         les sélecteurs dont la boîte englobante est photographiée (défaut : le widget)
 *   __cadre           la largeur minimale de la découpe, en px : un widget étroit se montre dans son
 *                     contexte plutôt que d'être agrandi jusqu'au flou
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
    'search' => [
        // Un champ vide n'a RIEN à lire, et la boîte d'un formulaire flottant est nulle : l'outil
        // l'écartait (« ne rend rien »). On fait ce que fait un visiteur — taper « match » — et l'on
        // photographie le champ AVEC le menu des résultats instantanés, dans le haut de la page.
        '__scene'   => "setTimeout(function () { var c = document.querySelector('.widget-search input[name=q]'); if (c) { c.focus(); c.value = 'match'; c.dispatchEvent(new Event('input', { bubbles: true })); } }, 500);",
        '__mesurer' => ['.widget-search .nf-search', '.widget-search .nf-search-suggest'],
        '__cadre'   => 640,
        'align'     => 'float-start',
    ],
    'socials' => [
        // Le widget lit les adresses des RÉGLAGES DU SITE, vides sur une installation neuve comme sur
        // la démonstration : il ne rendait rien. Des adresses d'exemple, le temps de la photo ; seul
        // le nom de chaque réseau s'affiche. Une colonne de barre latérale, sa place ordinaire.
        '__reglages_site' => [
            'nf_social_discord'   => 'https://example.com/discord',
            'nf_social_youtube'   => 'https://example.com/youtube',
            'nf_social_twitch'    => 'https://example.com/twitch',
            'nf_social_instagram' => 'https://example.com/instagram',
            'nf_social_steam'     => 'https://example.com/steam',
            'nf_social_bluesky'   => 'https://example.com/bluesky',
        ],
        '__colonne'       => 'col-4',
        '__cadre'         => 640,
        'display_panel'   => 'oui',
        'social_display'  => 'col-12',
        'social_style'    => 'btn btn-social',
        'content_display' => 'all',
        'icon_size'       => 'fa-1x',
    ],
    'video' => [
        // Le lecteur ET sa liste, dans une colonne de barre latérale. Il lit les vidéos de la
        // médiathèque : il en faut sur le site photographié, sinon il ne rend rien.
        '__colonne' => 'col-4',
        '__cadre'   => 640,
        'count'     => 3,
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
 * La boîte est mesurée en px de la PAGE ; l'image en compte ECHELLE fois plus, et la découpe se fait
 * à cette résolution. Rend 1.0 si la vignette est écrite, 0.0 sinon.
 *
 * @param array{x: int, y: int, w: int, h: int, texte: int}|NULL $boite
 */
function vignette(string $png, string $sortie, ?array $boite, int $qualite): float
{
    $source = @imagecreatefrompng($png);

    if ($source === FALSE)
    {
        return 0.0;
    }

    $k = imagesx($source) / LARGEUR;

    if ($boite !== NULL)
    {
        $x = (int) round(max(0, $boite['x'] - 8) * $k);
        $y = (int) round(max(0, $boite['y'] - 8) * $k);
        $w = min(imagesx($source) - $x, (int) round(($boite['w'] + 16) * $k));
        $h = min(imagesy($source) - $y, (int) round(($boite['h'] + 16) * $k));

        // HORS CADRE : on refuse. Continuer sans découper rendrait la page entière à la place du
        // widget, ce qui ressemble à une vignette et n'en est pas une.
        if ($w < 40 * $k || $h < 30 * $k)
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

// Une page de 3 200 px de haut, prise à deux points par pixel, fait une image de 2 560 × 6 400 :
// 65 Mo une fois décodée, avant la découpe.
ini_set('memory_limit', '512M');

$faits    = [];
$exemptes = [];

foreach ($addons as $a)
{
    $nom     = $a['nom'];
    $dossier = $a['dossier'];
    $page    = NULL;
    $sel     = NULL;
    $recette = $a['type'] === 'widget' ? (REGLAGES[$nom] ?? []) : [];

    // Rien à photographier, et la raison est écrite : ce n'est pas un manque.
    if (isset(NF_VIGNETTES_EXEMPTEES[$relatif = nf_relatif($dossier)]))
    {
        $exemptes[] = [$nom, NF_VIGNETTES_EXEMPTEES[$relatif]];
        printf("  %-24s ·  %s\n", $nom, 'exempté');
        continue;
    }

    if ($a['type'] === 'theme')
    {
        if ($nom !== $theme_servi && !$o['basculer-theme'])
        {
            $manques[] = [$nom, 'ce n\'est pas le thème servi : relancer avec --basculer-theme, sur un site jetable où il est installé'];
            printf("  %-24s —  %s\n", $nom, 'pas le thème servi');
            continue;
        }

        if (!in_array($nom, $installes, TRUE))
        {
            $manques[] = [$nom, 'non installé sur ce site : il ne se chargerait pas, et la photo montrerait un autre thème'];
            printf("  %-24s —  %s\n", $nom, 'non installé');
            continue;
        }

        // En VISITEUR : le serveur connecté laisse la place, une fois pour tous les thèmes.
        if (!$visiteur)
        {
            $serveur->arreter();
            $serveur  = serveur_apercus($port, '', $fichier_sonde);
            $visiteur = TRUE;
        }

        if ($o['basculer-theme'])
        {
            nf_theme_temporaire($db);   // retient le thème d'origine, et le rétablit quoi qu'il arrive
            nf_reglage_poser($db, 'nf_default_theme', $nom);
        }

        // Le thème photographié doit être celui qui SERT la page : sept vignettes identiques ont
        // déjà été produites en croyant le contraire (cf. l'en-tête).
        if (!str_contains(nf_http($serveur->base.'/fr', ['timeout' => 20])['corps'], 'themes/'.$nom.'/'))
        {
            $manques[] = [$nom, 'la page d\'accueil n\'est pas servie par ce thème : la photo en montrerait un autre'];
            printf("  %-24s —  %s\n", $nom, 'autre thème servi');
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
        // quatre connecteurs. Ils sont exemptés (lib/vignettes.php) ; un connecteur NOUVEAU arrive ici.
        $manques[] = [$nom, 'connecteur sans exemption : son bouton ne s’affiche que dans la fenêtre de connexion'];
        printf("  %-24s —  %s\n", $nom, 'pas de page à photographier');
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
        // porte un autre chemin. Les modules SANS page — `reactions`, `revisions`, `tools` — sont
        // exemptés (lib/vignettes.php).
        $exceptions = [
            'access'      => '/fr/admin/access/matrix',
            'live_editor' => '/fr/admin/live-editor',
        ];

        $page = $exceptions[$nom] ?? '/fr/'.$nom;

        if (!isset($exceptions[$nom]) && !repond($serveur->base.$page))
        {
            $page = '/fr/admin/'.$nom;
        }

        // Le CONTENU, jamais le chrome. Sur une page publique il vit dans `.module-<nom>` ; sur une
        // page d'administration, dans le `<main class="nf-main">` du theme. Sans ce decoupage,
        // cinquante vignettes montrent le meme bandeau, ou la meme barre laterale, et l'on ne
        // distingue plus un module d'un autre.
        $sel = str_starts_with($page, '/fr/admin')
            ? '.nf-main'
            : '.module-'.str_replace('_', '-', $nom);

        // L'éditeur en direct n'est pas une page de l'administration : c'est un écran entier, sa
        // barre d'outils au-dessus du site en cours d'édition, sans `.nf-main`. La découpe ne trouvait
        // rien (« ne rend rien », 2026-10-04) : il se photographie en entier, ce qui est son sujet.
        if ($nom === 'live_editor')
        {
            $sel = NULL;
        }
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

    $mesurer  = $sel !== NULL ? ($recette['__mesurer'] ?? [$sel]) : [];
    $scene    = (string) ($recette['__scene'] ?? '');
    $boite    = $sel !== NULL ? boite($serveur->base.$page, $mesurer, $fichier_sonde, $scene) : NULL;
    $nettoyer = NULL;

    // Pas trouvé sur le site : on le pose nous-mêmes, le temps d'une photo.
    if ($sel !== NULL && $boite === NULL && $a['type'] === 'widget')
    {
        [$page, $nettoyer] = banc_essai($db, $theme_servi, $nom, $serveur->base, $url_publique);

        // Certains widgets ne sont pas des blocs : ils se posent SUR la page. On la photographie
        // en entier, ce qui est exactement ce qu'ils font.
        if (!empty($recette['__page_entiere']))
        {
            $sel   = NULL;
            $boite = NULL;
        }
        else
        {
            $boite = boite($serveur->base.$page, $mesurer, $fichier_sonde, $scene);
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
     * UN WIDGET ÉTROIT SE MONTRE DANS SON CONTEXTE (`__cadre`). Un champ de recherche de 250 px,
     * porté à 960, devenait une bouillie agrandie quatre fois. La découpe s'élargit autour de lui
     * jusqu'à la largeur demandée, sans sortir de la page : on voit le widget, et où il vit.
     */
    $cadre = (int) ($recette['__cadre'] ?? 0);

    if ($boite !== NULL && $cadre > $boite['w'])
    {
        $centre     = $boite['x'] + intdiv($boite['w'], 2);
        $boite['x'] = max(0, min(LARGEUR - $cadre, $centre - intdiv($cadre, 2)));
        $boite['w'] = $cadre;
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
        // autre. Au-dela de 280 px, on prefere rogner la droite (cf. vignette()). Un cadre demandé,
        // lui, est déjà borné : il prend ses proportions entières.
        $plafond = $cadre > 0 ? PHP_INT_MAX : max($boite['h'], 280);
        $besoin  = min((int) round($boite['w'] / (V_LARGEUR / V_HAUTEUR)), $plafond);

        if ($boite['h'] < $besoin)
        {
            // Un widget qui tient ENTIER dans une découpe partant du haut de la page y est montré
            // sous l'en-tête du site, plutôt qu'au milieu d'une découpe qui tranche le logo en deux
            // (les réseaux sociaux, 2026-10-04). Cadre demandé seulement : c'est là qu'on l'a vu.
            $boite['y'] = $cadre > 0 && $boite['y'] + $boite['h'] + 8 <= $besoin
                ? 0
                : max(0, $boite['y'] - (int) (($besoin - $boite['h']) / 2));
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

    // La scène se rejoue pour la photo : la page montre l'état que la mesure a vu.
    file_put_contents($fichier_sonde, $scene);

    // Le visiteur a son propre profil de navigateur : rien de ce que la session d'administrateur y a
    // laissé — un cookie, un choix de mode — ne doit le suivre.
    $capture = nf_chrome_capture($serveur->base.$page, $png, ['largeur' => LARGEUR, 'hauteur' => $hauteur, 'budget' => 20000,
        'echelle' => ECHELLE, 'profil' => $visiteur ? 'visiteur' : 'defaut']) && is_file($png);

    file_put_contents($fichier_sonde, '');

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

// Le thème d'origine revient TOUT DE SUITE, pas seulement à la sortie de l'outil.
if ($o['basculer-theme'] && $theme_servi !== '')
{
    nf_reglage_poser($db, 'nf_default_theme', $theme_servi);
}

echo "\n";
printf("%d vignette(s) écrite(s), %d exemptée(s), %d non capturée(s).\n", count($faits), count($exemptes), count($manques));

if ($manques)
{
    echo "\nNon photographiés — leur vignette précédente reste en place, s'ils en avaient une :\n";

    foreach ($manques as [$nom, $raison])
    {
        printf("  %-24s %s\n", $nom, $raison);
    }

    echo "\n";
    nf_echec(sprintf('%d addon(s) non photographié(s) sur %d — php tools/check-vignettes.php dit lesquels restent sans vignette conforme', count($manques), count($addons)));
}

nf_ok(sprintf('%d vignette(s) sur %d addon(s), %d exemptée(s)', count($faits), count($addons), count($exemptes)));
