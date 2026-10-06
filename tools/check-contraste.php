<?php
declare(strict_types=1);

/**
 * check-contraste — le contraste WCAG du texte, thème par thème et mode par mode, mesuré dans un navigateur.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un texte illisible ne casse rien : la page répond 200, la feuille est valide, aucun journal ne
 * bouge. Il ne se voit qu'à l'œil, dans le bon thème et dans le bon mode — c'est-à-dire presque
 * jamais. Sept thèmes × deux modes × chaque écran, personne ne regarde tout.
 *
 * Et l'œil seul ne suffit pas : le 2026-09-16 j'ai signalé à tort un « mode jour illisible » sur le
 * thème `forge`, à partir d'une capture. Le thème était correct ; c'était l'outil de capture qui ne
 * savait pas basculer le mode. Une mesure chiffrée aurait tranché en une seconde.
 *
 * Ce que l'outil mesure
 * ---------------------
 * Le rapport de contraste WCAG 2.1 entre la couleur du texte et son fond EFFECTIF — celui du
 * premier ancêtre dont le fond est opaque. Seuils : **4,5:1** pour du texte courant, **3:1** pour
 * du grand texte (≥ 24 px, ou ≥ 18,66 px en gras), comme le niveau AA.
 *
 * Ce qu'il refuse de juger, et le dit : un texte posé sur une IMAGE ou un DÉGRADÉ n'a pas de fond
 * mesurable. Ces cas sont comptés à part, jamais silencieusement validés — c'est précisément la
 * situation du bandeau d'en-tête des thèmes gaming.
 *
 * Usage
 * -----
 *   php tools/check-contraste.php                       tous les thèmes publics, les deux modes
 *   php tools/check-contraste.php --themes=forge,nebula
 *   php tools/check-contraste.php --pages=/fr,/fr/news
 *   php tools/check-contraste.php --seuil=4.5 --port=8094
 *   php tools/check-contraste.php --widget=calendar:prochain [--reglages='{"…":…}']
 *   php tools/check-contraste.php --connecte                  en membre ordinaire, l'espace membre compris
 *
 * Un widget, dans tous ses habits
 * -------------------------------
 * `--widget=nom[:type]` mesure UN widget, et lui seul, dans chaque thème × chaque style de panneau (`panel-default`,
 * `panel-header`, `panel-color`) × chaque mode. Le banc (`lib/banc.php`) le pose sur la page de contact, dans la
 * zone du contenu, le temps d'une page, puis retire tout.
 *
 * Pourquoi : un widget se mesurait seulement là où un thème l'avait posé, au style que ce thème avait choisi. Le
 * « prochain rendez-vous » de Pulse cachait une case de date blanche sur blanc dans le panneau coloré, et aucune
 * épreuve ne la voyait : sur l'atelier, aucun rendez-vous n'était à venir, la case n'était jamais rendue
 * (2026-10-06). Un widget ABSENT de la page — pas de données, zone non rendue — est un échec, jamais un succès.
 *
 * Connecté
 * --------
 * `--connecte` mesure en MEMBRE ORDINAIRE (une session ouverte le temps de l'outil, `nf_session_membre()`), et par
 * défaut les pages qu'un membre voit en plus : son espace et ses notifications. Mesuré en visiteur seulement, l'outil
 * n'avait jamais vu l'espace membre : sa date d'inscription grise sur ardoise, dans Pulse, lui échappait (2026-10-06).
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';
require __DIR__.'/lib/banc.php';

[$o] = nf_options(['themes' => '', 'pages' => '/fr,/fr/news,/fr/forum,/fr/user', 'seuil' => '4.5', 'port' => 0, 'widget' => '', 'reglages' => '', 'connecte' => FALSE]);

$seuil  = (float) $o['seuil'];
$pages  = array_values(array_filter(array_map('trim', explode(',', $o['pages']))));
$themes = nf_themes_publics();

if ($o['themes'] !== '')
{
    $demandes = array_map('trim', explode(',', $o['themes']));
    $inconnus = array_diff($demandes, $themes);

    if ($inconnus)
    {
        nf_refus('thème(s) inconnu(s) : '.implode(', ', $inconnus).' — disponibles : '.implode(', ', $themes));
    }

    $themes = $demandes;
}

// ── Un widget, dans tous ses habits ─────────────────────────────────────────
$widget = NULL;

if ($o['widget'] !== '')
{
    [$nom, $type] = array_pad(explode(':', $o['widget'], 2), 2, 'index');

    if (!preg_match('/^[a-z0-9_]+$/', $nom) || !is_dir(nf_racine().'/widgets/'.$nom))
    {
        nf_refus('widget inconnu : '.$nom);
    }

    if ($o['reglages'] !== '' && !is_array(json_decode($o['reglages'], TRUE)))
    {
        nf_refus('--reglages doit être un objet JSON');
    }

    $widget = ['nom' => $nom, 'type' => $type, 'reglages' => $o['reglages'] !== '' ? $o['reglages'] : NULL];
    $pages  = ['/fr/contact'];
}

// En membre, les pages par défaut sont celles qu'il voit en plus du visiteur.
if ($o['connecte'] && $o['pages'] === '/fr,/fr/news,/fr/forum,/fr/user')
{
    $pages = ['/fr', '/fr/user', '/fr/user/notifications', '/fr/forum'];
}

// Ce que la sonde mesure : toute la page, ou le seul widget posé.
$portee = $widget ? '.widget-'.$widget['nom'] : 'body';

// ── La sonde ────────────────────────────────────────────────────────────────
$sonde = <<<'JS'
(function(){
    function canal(c){ c = c / 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
    function lum(rgb){ return 0.2126 * canal(rgb[0]) + 0.7152 * canal(rgb[1]) + 0.0722 * canal(rgb[2]); }
    // Deux écritures à comprendre, et la seconde est indispensable.
    //
    // Les thèmes dérivent leurs couleurs avec `color-mix(in srgb, …)`. Le navigateur rend alors la
    // valeur calculée sous la forme `color(srgb 0.64 0.20 0.08)` — canaux de 0 à 1 — et NON en
    // `rgb()`. Une sonde qui ne lit que `rgb()` croit donc le fond absent, remonte aux ancêtres et
    // compare le texte au fond de la PAGE au lieu du bouton.
    //
    // Constaté le 2026-09-16 : le premier balayage annonçait « blanc sur #f6f1ee, 1,12:1 » pour la
    // pagination de `forge` — alarmant et FAUX. Le bouton a bien un fond sombre ; c'était la sonde
    // qui ne le voyait pas. Plusieurs autres signalements venaient de la même cécité.
    function lire(s){
        s = s || '';

        var m = /rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?\)/.exec(s);
        if (m) { return [ +m[1], +m[2], +m[3], m[4] === undefined ? 1 : +m[4] ]; }

        // `color(srgb r g b)` ou `color(srgb r g b / a)`, canaux de 0 à 1.
        m = /color\(\s*srgb\s+([\d.eE+-]+)\s+([\d.eE+-]+)\s+([\d.eE+-]+)(?:\s*\/\s*([\d.eE+-]+))?\s*\)/.exec(s);
        if (m) {
            return [ +m[1] * 255, +m[2] * 255, +m[3] * 255, m[4] === undefined ? 1 : +m[4] ];
        }

        // Toute autre écriture (lab, oklch, color-mix non résolu…) : on ne devine pas.
        return null;
    }
    // Un GRAIN n'est pas une image : un dégradé sans image dont toutes les couleurs sont presque transparentes
    // (une trame, un grain de papier) ne change pas la couleur qu'on perçoit du fond. Granite 2.0 pose ainsi
    // un grain sur toute la page : sans cette lecture, 1 110 textes passaient pour « posés sur une image », et
    // la mesure ne voyait plus rien (2026-10-06).
    function grain(img){
        if (/url\(/.test(img)) { return false; }
        var couleurs = img.match(/rgba?\([^)]*\)|color\([^)]*\)/g) || [];
        return couleurs.length > 0 && couleurs.every(function(c){ var l = lire(c); return l && l[3] <= 0.1; });
    }
    // Un DÉGRADÉ sans image, aux couleurs pleines, a un fond qu'on sait lire : ses couleurs. Le texte se mesure contre
    // la MOINS favorable. Sans cette lecture, la carte d'adhérent de Pulse — un dégradé d'ardoise — passait pour une
    // image, et sa date d'inscription, grise sur ardoise, n'était jamais mesurée (2026-10-06). Une couleur qu'on ne
    // sait pas lire (color-mix non résolu, oklch…) ou un arrêt transparent : on ne devine pas, c'est « sur image ».
    // Seulement s'il COUVRE le bloc, comme le pseudo-élément plus bas : un dégradé de 2 px sous un lien est un
    // soulignement dessiné, pas un fond — les liens de Chronique et de Granite passaient pour « 1,87:1 » (2026-10-06).
    function couvre(n, taille){
        return taille.split(',').some(function(couche){
            var v = couche.trim().split(/\s+/);
            if (v[0] === 'auto' && (v.length === 1 || v[1] === 'auto') || v[0] === 'cover' || v[0] === 'contain') { return true; }
            var dims = [n.offsetWidth, n.offsetHeight];
            return [v[0], v[1] || 'auto'].every(function(x, i){
                if (x === 'auto') { return true; }
                var m = /^([\d.]+)(px|%)$/.exec(x);
                return !!m && (m[2] === '%' ? +m[1] >= 90 : +m[1] >= 0.9 * dims[i]);
            });
        });
    }
    function degrade(img){
        if (/url\(|color-mix|okl|lab\(|lch\(|hsla?\(|hwb\(/.test(img)) { return null; }
        var couleurs = (img.match(/rgba?\([^)]*\)|color\([^)]*\)/g) || []).map(lire);
        if (!couleurs.length || couleurs.some(function(c){ return !c || c[3] < 0.9; })) { return null; }
        return couleurs;
    }
    function rapport(a, b){
        var la = lum(a), lb = lum(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    }

    var verdict = { fautes: [], nonMesurables: 0, examines: 0, present: !!document.querySelector(NF_PORTEE) };
    var vus = {};

    function signature(el){
        var n = el.tagName.toLowerCase();
        var c = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
        return c.length ? n + '.' + c.join('.') : n;
    }

    document.querySelectorAll(NF_PORTEE + ' *').forEach(function(el){
        // Seuls les elements qui portent DIRECTEMENT du texte : sinon on juge un conteneur pour le
        // texte de ses enfants, avec le mauvais fond.
        var texte = '';
        for (var i = 0; i < el.childNodes.length; i++) {
            if (el.childNodes[i].nodeType === 3) { texte += el.childNodes[i].nodeValue; }
        }
        if (!texte.trim()) { return; }

        var s = getComputedStyle(el);
        if (s.visibility === 'hidden' || s.display === 'none' || parseFloat(s.opacity) < 0.1) { return; }

        var b = el.getBoundingClientRect();
        if (b.width < 4 || b.height < 4) { return; }

        var avant = lire(s.color);
        if (!avant || avant[3] < 0.5) { return; }

        // Un texte EN dégradé (background-clip: text) a pour couleur le dégradé lui-même, pas sa couleur déclarée :
        // le titre de Forge, le nom du site de Nebula. On ne devine pas, on le compte à part.
        var ps0 = el.parentElement ? getComputedStyle(el.parentElement) : null;
        if ((s.webkitBackgroundClip || s.backgroundClip) === 'text' || (ps0 && (ps0.webkitBackgroundClip || ps0.backgroundClip) === 'text')
            || /^(transparent|rgba\(0, 0, 0, 0\))$/.test(s.webkitTextFillColor || '')) {
            verdict.nonMesurables++;
            return;
        }

        // Fond effectif : premier ancetre opaque. Une image en chemin rend la mesure impossible — on le compte a
        // part plutot que de valider en silence ; un degrade aux couleurs pleines se mesure (cf. degrade()).
        var n = el, fond = null, fonds = null, surImage = false, illisible = false;
        while (n && n !== document.documentElement) {
            var sn = getComputedStyle(n);

            // Un bloc peut porter son fond sur son pseudo-élément ::before, posé dessous le contenu
            // (Forge 2.0 : les coins coupés se dessinent ainsi, pour ne couper ni les menus déroulants
            // ni le focus clavier). Sa couleur de fond est le fond du texte ; ses dégradés, des filets
            // de coin, ne comptent pas. Sans cette lecture, le texte blanc d'un panneau coloré était
            // mesuré contre le fond clair de la page : 1,12:1, un défaut inexistant (2026-10-06).
            //
            // Seulement s'il COUVRE le bloc : un pseudo-élément étroit est un ornement, pas un fond. Le liseré de
            // lave de 3 px posé à gauche du lien actif du rail de Forge était pris pour le fond de son texte —
            // « 2,86:1 », un défaut inexistant (2026-10-06).
            var ps = getComputedStyle(n, '::before');
            if (ps.content && ps.content !== 'none' && ps.content !== 'normal' && ps.position === 'absolute'
                && parseFloat(ps.width) >= 0.9 * n.offsetWidth && parseFloat(ps.height) >= 0.9 * n.offsetHeight) {
                var fondPs = lire((ps.backgroundColor || '').trim());
                if (fondPs && fondPs[3] >= 0.9) { fond = fondPs; break; }
            }

            if (sn.backgroundImage && sn.backgroundImage !== 'none' && !grain(sn.backgroundImage) && couvre(n, sn.backgroundSize || 'auto')) {
                fonds = degrade(sn.backgroundImage);
                if (!fonds) { surImage = true; }
                break;
            }

            var brut = (sn.backgroundColor || '').trim();
            var bg   = lire(brut);

            // Une couleur qu'on ne sait pas LIRE n'est pas une couleur absente. Remonter au parent
            // reviendrait à mesurer le texte contre le mauvais fond et à annoncer un défaut
            // inexistant — c'est exactement ce qui s'est produit avec `color(srgb …)`. On s'arrête
            // et on compte le cas comme non mesurable, plutôt que de deviner.
            if (!bg && brut !== '' && brut !== 'transparent' && !/^rgba\(0,\s*0,\s*0,\s*0\)$/.test(brut)) {
                illisible = true;
                break;
            }

            if (bg && bg[3] >= 0.9) { fond = bg; break; }
            n = n.parentElement;
        }

        if (illisible) { verdict.nonMesurables++; return; }
        if (!fond && !fonds && !surImage) {
            var sr = getComputedStyle(document.documentElement);
            if (sr.backgroundImage && sr.backgroundImage !== 'none' && !grain(sr.backgroundImage)) { fonds = degrade(sr.backgroundImage); surImage = !fonds; }
            else { fond = lire(sr.backgroundColor) || [255, 255, 255, 1]; }
        }

        if (surImage) { verdict.nonMesurables++; return; }

        verdict.examines++;

        var taille = parseFloat(s.fontSize) || 16;
        var gras   = (parseInt(s.fontWeight, 10) || 400) >= 700;
        var grand  = taille >= 24 || (taille >= 18.66 && gras);
        var exige  = grand ? 3 : NF_SEUIL;
        // Sur un dégradé, la couleur la moins favorable au texte.
        (fonds || []).forEach(function(c){ if (!fond || rapport(avant, c) < rapport(avant, fond)) { fond = c; } });
        var r      = rapport(avant, fond);

        if (r >= exige) { return; }

        var sig = signature(el);
        if (vus[sig]) { return; }
        vus[sig] = 1;

        verdict.fautes.push({
            el: sig,
            ratio: Math.round(r * 100) / 100,
            exige: exige,
            texte: texte.trim().replace(/\s+/g, ' ').slice(0, 44),
            couleur: s.color,
            fond: 'rgb(' + fond[0] + ', ' + fond[1] + ', ' + fond[2] + ')'
        });
    });

    verdict.fautes.sort(function(a, b){ return a.ratio - b.ratio; });
    verdict.fautes = verdict.fautes.slice(0, 10);

    var d = document.createElement('div');
    d.id = 'nf-contraste-verdict';
    d.setAttribute('data-verdict', JSON.stringify(verdict));
    document.body.appendChild(d);
})();
JS;

$fichier_sonde = nf_temp('sonde.js');
file_put_contents($fichier_sonde, str_replace(['NF_SEUIL', 'NF_PORTEE'], [(string) $seuil, (string) json_encode($portee)], $sonde));

// ── Base de données : bascule de thème, rétablie quoi qu'il arrive ──────────
$db = nf_connexion();

// Les thèmes non installés rendraient le thème COURANT : on les mesurerait deux fois sous deux noms.
$absents = array_diff($themes, nf_themes_installes($db));

if ($absents)
{
    nf_refus('thème(s) non installé(s) sur ce site : '.implode(', ', $absents).' — les mesurer rendrait le thème courant, pas le leur');
}

nf_theme_temporaire($db);

$session = $o['connecte'] ? nf_session_membre($db) : '';

printf("Thèmes : %s\nPages  : %s\nSeuil  : %s:1 (texte courant) · 3:1 (grand texte)\n%s\n",
    implode(', ', $themes), implode(', ', $pages), $o['seuil'],
    ($widget ? sprintf("Widget : %s, affichage « %s », dans les trois styles de panneau\n", $widget['nom'], $widget['type']) : '')
    .($session !== '' ? "Vu par  : un membre ordinaire, connecté\n" : ''));

// Chaque mesure d'un thème : une page, ou — pour un widget — un style de panneau sur la page de contact.
$mesures = $widget ? ['panel-default', 'panel-header', 'panel-color'] : $pages;
$absents = [];

$total_fautes = 0;
$total_muets  = 0;
$rapport      = [];
$port         = nf_port($o['port']);

foreach (['dark', 'light'] as $mode)
{
    // Un serveur par mode : le routeur force le mode dans toutes les clés de thème du localStorage.
    $serveur = nf_serveur($port, [
        'NF_OUTIL_CONSENT'  => 'essentials',
        'NF_OUTIL_THEME'    => $mode,
        'NF_OUTIL_SONDE'    => $fichier_sonde,
        'NF_OUTIL_SONDE_OU' => 'body',
    ] + ($session !== '' ? ['NF_OUTIL_SESSION' => $session] : []));

    foreach ($themes as $theme)
    {
        nf_reglage_poser($db, 'nf_default_theme', $theme);

        foreach ($mesures as $page)
        {
            $retirer = NULL;

            if ($widget)
            {
                $zone = nf_banc_zone_contenu($theme);

                if ($zone === NULL)
                {
                    nf_refus("le thème $theme ne déclare pas de région « content » : le banc ne sait pas où poser le widget");
                }

                $retirer = nf_banc_widget($db, $theme, $widget['nom'], $widget['type'], $widget['reglages'], zone: $zone, style: $page);
            }

            $dom = nf_chrome_dom($serveur->base.($widget ? '/fr/contact' : $page), ['largeur' => 1400, 'hauteur' => 1000, 'budget' => 7000, 'profil' => $mode]);
            $v   = nf_sonde_verdict($dom, 'nf-contraste-verdict');

            if ($retirer)
            {
                $retirer();
            }

            if ($widget && $v !== NULL && (empty($v['present']) || (int) ($v['examines'] ?? 0) === 0))
            {
                printf("  %-11s %-6s %-14s WIDGET ABSENT OU MUET\n", $theme, $mode, $page);
                $absents[] = "$theme · $mode · $page";
                continue;
            }

            if ($v === NULL)
            {
                printf("  %-11s %-6s %-14s SONDE MUETTE\n", $theme, $mode, $page);
                continue;
            }

            $f = $v['fautes'] ?? [];

            $total_fautes += count($f);
            $total_muets  += (int) ($v['nonMesurables'] ?? 0);

            printf("  %-11s %-6s %-14s %3d texte(s) mesuré(s), %d sur image, %s\n",
                $theme, $mode, $page,
                (int) ($v['examines'] ?? 0), (int) ($v['nonMesurables'] ?? 0),
                $f ? count($f).' SOUS LE SEUIL' : 'aucun défaut');

            if ($f)
            {
                $rapport[] = [$theme, $mode, $page, $f];
            }
        }
    }

    $serveur->arreter();
    usleep(300000);
}

@unlink($fichier_sonde);

echo "\n";

if ($absents)
{
    echo "WIDGET ABSENT OU SANS TEXTE — rien n'a été mesuré là, ce n'est pas un succès :\n\n  ".implode("\n  ", $absents)."\n\n";
}

if (!$rapport && !$absents)
{
    nf_ok(sprintf('aucun texte sous le seuil (%d posés sur une image, non mesurables)', $total_muets));
}

echo "TEXTES SOUS LE SEUIL — illisibles ou limite, sans que rien ne le signale :\n\n";

foreach ($rapport as [$theme, $mode, $page, $fautes])
{
    printf("  %s · %s · %s\n", $theme, $mode, $page);

    foreach ($fautes as $f)
    {
        printf("      %-28s %4.2f:1 (exigé %s) %s sur %s\n      %s« %s »\n",
            $f['el'], $f['ratio'], $f['exige'], $f['couleur'], $f['fond'], str_repeat(' ', 28), $f['texte']);
    }

    echo "\n";
}

nf_echec(sprintf('%d texte(s) sous le seuil, %d mesure(s) sans widget ; %d posés sur une image, non mesurables, à regarder à l\'œil',
    $total_fautes, count($absents), $total_muets));
