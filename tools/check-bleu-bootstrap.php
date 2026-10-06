<?php
declare(strict_types=1);

/**
 * check-bleu-bootstrap — le bleu de Bootstrap resté dans un thème qui ne l'a pas choisi, cherché dans les pages servies.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Bootstrap 5.3 écrit sa couleur primaire en dur (#0d6efd et ses teintes) dans plusieurs composants au
 * lieu de la lire dans une variable : la barre de progression, la case cochée, la page active, l'entrée
 * pressée d'un menu, la flèche d'un accordéon, les lueurs de focus, les utilitaires `text-primary` ou
 * `border-primary`. Un thème qui ne les reprend pas un par un les sert en bleu au milieu de sa palette.
 * Rien ne le signale : la feuille est valide, le contraste peut être bon, la page répond 200. Le
 * 2026-10-06, les barres du sondage sortaient en bleu Bootstrap sur le papier de Granite, et les cases
 * de l'administration aussi — vus à l'œil, sur des captures, longtemps après.
 *
 * Ce que l'outil cherche
 * ----------------------
 * Dans chaque élément rendu (et ses pseudo-éléments `::before` / `::after`) : la couleur du texte, du fond,
 * des bordures tracées, du contour, des ombres, du remplissage et du trait d'un SVG, et les images SVG
 * intégrées ; puis, sur un échantillon d'éléments qu'on peut atteindre au clavier (champ, case, bouton,
 * lien de page, accordéon…), les mêmes couleurs une fois le FOCUS posé. Est fautive toute valeur égale à
 * l'un des bleus de Bootstrap (la liste `BLEUS` de la sonde, relevée dans `css/bootstrap.min.css`). Le
 * survol ne se simule pas depuis une page : il reste à l'œil.
 *
 * Le remède est dans `css/nf-bs5-bridge.css` (raccorder les variables du composant aux jetons `--nf-*`),
 * ou dans la feuille du thème s'il veut autre chose.
 *
 * Usage
 * -----
 *   php tools/check-bleu-bootstrap.php                       tous les thèmes publics, les deux modes
 *   php tools/check-bleu-bootstrap.php --themes=granite,forge
 *   php tools/check-bleu-bootstrap.php --pages=/fr,/fr/forum --port=8118
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o] = nf_options([
    'themes' => '',
    'pages'  => '/fr,/fr/news,/fr/forum,/fr/members,/fr/contact,/fr/faq,/fr/surveys,/fr/user',
    'port'   => 0,
]);

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

// ── La sonde ────────────────────────────────────────────────────────────────
$sonde = <<<'JS'
(function(){
    // Les bleus de Bootstrap 5.3.8, en canaux rouge, vert, bleu : la couleur primaire, ses teintes de survol
    // et d'appui, celles du mode sombre, les fonds et bordures « subtle », et l'ombre de focus d'un bouton.
    var BLEUS = {
        '13,110,253': '#0d6efd', '11,94,215': '#0b5ed7', '10,88,202': '#0a58ca', '134,183,254': '#86b7fe',
        '110,168,254': '#6ea8fe', '5,44,101': '#052c65', '207,226,255': '#cfe2ff', '158,197,254': '#9ec5fe',
        '139,185,254': '#8bb9fe', '8,66,152': '#084298', '3,22,51': '#031633', '10,83,190': '#0a53be',
        '49,132,253': '#3184fd'
    };
    // Les mêmes, dans les images SVG que Bootstrap intègre à sa feuille (flèches, coches, boutons).
    var SVG = /%23(0d6efd|86b7fe|052c65|6ea8fe|0a58ca|9ec5fe|cfe2ff)/i;

    // Toutes les couleurs rgb()/rgba() d'une valeur calculée : une ombre en porte parfois plusieurs.
    function bleus(valeur){
        var trouves = [], re = /rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\s*\)/g, m;
        while ((m = re.exec(valeur || ''))) {
            if (m[4] !== undefined && parseFloat(m[4]) < 0.05) { continue; }
            var cle = m[1] + ',' + m[2] + ',' + m[3];
            if (BLEUS[cle]) { trouves.push(BLEUS[cle]); }
        }
        return trouves;
    }

    function signature(el){
        var n = el.tagName.toLowerCase();
        var c = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 3);
        return c.length ? n + '.' + c.join('.') : n;
    }

    var verdict = { fautes: [], examines: 0 };
    var vus = {};

    function noter(el, pseudo, propriete, valeur, teinte){
        var cle = signature(el) + pseudo + '|' + propriete;
        if (vus[cle]) { return; }
        vus[cle] = 1;
        var texte = (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40);
        verdict.fautes.push({ el: signature(el) + pseudo, propriete: propriete, bleu: teinte, valeur: String(valeur).slice(0, 80), texte: texte });
    }

    // Les couleurs d'un style calculé, sauf celles qui ne se voient pas (bordure sans épaisseur, contour absent).
    function examiner(el, s, pseudo, prefixe){
        var a_texte = false;
        for (var i = 0; i < el.childNodes.length; i++) {
            if (el.childNodes[i].nodeType === 3 && el.childNodes[i].nodeValue.trim()) { a_texte = true; break; }
        }
        var props = [['background-color', s.backgroundColor], ['box-shadow', s.boxShadow], ['fill', s.fill], ['stroke', s.stroke]];
        // La couleur du texte ne compte que là où il y a un texte, une icône ou un pseudo-élément qui s'en sert.
        if (a_texte || pseudo || /^(i|svg|span)$/i.test(el.tagName)) { props.push(['color', s.color]); }
        ['Top', 'Right', 'Bottom', 'Left'].forEach(function(cote){
            if (parseFloat(s['border' + cote + 'Width']) > 0 && s['border' + cote + 'Style'] !== 'none') {
                props.push(['border-' + cote.toLowerCase() + '-color', s['border' + cote + 'Color']]);
            }
        });
        if (s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0) { props.push(['outline-color', s.outlineColor]); }
        if (s.textDecorationLine && s.textDecorationLine !== 'none') { props.push(['text-decoration-color', s.textDecorationColor]); }

        props.forEach(function(p){
            var t = bleus(p[1]);
            if (t.length) { noter(el, pseudo, prefixe + p[0], p[1], t[0]); }
        });

        // Une image SVG bleue ne se voit que peinte en fond : posée en masque, elle ne donne que sa forme.
        var image = s.backgroundImage || '';
        if (image !== 'none' && SVG.test(image)) { noter(el, pseudo, prefixe + 'background-image', 'svg ' + image.match(SVG)[0], '#' + image.match(SVG)[1]); }
    }

    function visible(el, s){
        if (s.display === 'none' || s.visibility === 'hidden' || parseFloat(s.opacity) === 0) { return false; }
        var b = el.getBoundingClientRect();
        return b.width >= 1 && b.height >= 1;
    }

    document.querySelectorAll('body *').forEach(function(el){
        if (el.id === 'nf-bleu-verdict') { return; }
        var s = getComputedStyle(el);
        if (!visible(el, s)) { return; }
        verdict.examines++;
        examiner(el, s, '', '');
        ['::before', '::after'].forEach(function(p){
            var ps = getComputedStyle(el, p);
            if (ps.content && ps.content !== 'none' && ps.content !== 'normal' && ps.display !== 'none') {
                examiner(el, ps, p, '');
            }
        });
    });

    // Le focus : le premier élément visible de chaque sorte, focalisé le temps de lire ses couleurs. Un champ de
    // saisie, focalisé d'abord, met le navigateur en « mode clavier » : sans lui, un bouton ou un lien focalisé par
    // script ne prend pas toujours son style `:focus-visible`, et le résultat dépendrait de l'ordre de la page.
    var amorce = document.createElement('input');
    amorce.setAttribute('aria-hidden', 'true');
    amorce.style.cssText = 'position:fixed;left:-9999px;top:0;width:10px;height:10px';
    document.body.appendChild(amorce);
    try { amorce.focus({ preventScroll: true }); } catch (e) {}
    [
        'input.form-control', 'textarea.form-control', 'select.form-select', 'input.form-check-input', '.btn',
        '.page-link', '.accordion-button', '.btn-close', '.nav-link', '.dropdown-item', '.list-group-item-action', 'a[href]'
    ].forEach(function(sel){
        var liste = document.querySelectorAll(sel);
        for (var i = 0; i < liste.length; i++) {
            var el = liste[i];
            if (!visible(el, getComputedStyle(el))) { continue; }
            try { el.focus({ preventScroll: true }); } catch (e) { return; }
            if (document.activeElement === el) { examiner(el, getComputedStyle(el), '', 'focus:'); }
            el.blur();
            return;
        }
    });

    amorce.remove();
    verdict.fautes = verdict.fautes.slice(0, 12);

    var d = document.createElement('div');
    d.id = 'nf-bleu-verdict';
    d.setAttribute('data-verdict', JSON.stringify(verdict));
    document.body.appendChild(d);
})();
JS;

$fichier_sonde = nf_temp('sonde.js');
file_put_contents($fichier_sonde, $sonde);

// ── Base de données : bascule de thème, rétablie quoi qu'il arrive ──────────
$db = nf_connexion();

// Un thème non installé rendrait le thème COURANT : on l'examinerait deux fois sous deux noms.
$absents = array_diff($themes, nf_themes_installes($db));

if ($absents)
{
    nf_refus('thème(s) non installé(s) sur ce site : '.implode(', ', $absents).' — les examiner rendrait le thème courant, pas le leur');
}

nf_theme_temporaire($db);

printf("Thèmes : %s\nPages  : %s\n\n", implode(', ', $themes), implode(', ', $pages));

$rapport = [];
$total   = 0;
$port    = nf_port($o['port']);

foreach (['dark', 'light'] as $mode)
{
    // Un serveur par mode : le routeur force le mode dans toutes les clés de thème du localStorage.
    $serveur = nf_serveur($port, [
        'NF_OUTIL_CONSENT'  => 'essentials',
        'NF_OUTIL_THEME'    => $mode,
        'NF_OUTIL_SONDE'    => $fichier_sonde,
        'NF_OUTIL_SONDE_OU' => 'body',
    ]);

    foreach ($themes as $theme)
    {
        nf_reglage_poser($db, 'nf_default_theme', $theme);

        foreach ($pages as $page)
        {
            $dom = nf_chrome_dom($serveur->base.$page, ['largeur' => 1400, 'hauteur' => 1000, 'budget' => 7000, 'profil' => $mode]);
            $v   = nf_sonde_verdict($dom, 'nf-bleu-verdict');

            if ($v === NULL)
            {
                printf("  %-11s %-6s %-14s SONDE MUETTE\n", $theme, $mode, $page);
                continue;
            }

            $f      = $v['fautes'] ?? [];
            $total += count($f);

            printf("  %-11s %-6s %-14s %4d élément(s) examiné(s), %s\n",
                $theme, $mode, $page, (int) ($v['examines'] ?? 0), $f ? count($f).' EN BLEU BOOTSTRAP' : 'aucun bleu');

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

if (!$rapport)
{
    nf_ok('aucun bleu de Bootstrap dans les pages servies, au repos comme au focus');
}

echo "LE BLEU DE BOOTSTRAP, là où le thème ne l'a pas choisi :\n\n";

foreach ($rapport as [$theme, $mode, $page, $fautes])
{
    printf("  %s · %s · %s\n", $theme, $mode, $page);

    foreach ($fautes as $f)
    {
        printf("      %-34s %-26s %s  « %s »\n", $f['el'], $f['propriete'], $f['bleu'], $f['texte']);
    }

    echo "\n";
}

nf_echec(sprintf('%d couleur(s) restée(s) au bleu de Bootstrap — les raccorder aux jetons du thème (css/nf-bs5-bridge.css) ou les redéfinir dans le thème', $total));
