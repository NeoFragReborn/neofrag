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
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o] = nf_options(['themes' => '', 'pages' => '/fr,/fr/news,/fr/forum,/fr/user', 'seuil' => '4.5', 'port' => 0]);

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
    function rapport(a, b){
        var la = lum(a), lb = lum(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    }

    var verdict = { fautes: [], nonMesurables: 0, examines: 0 };
    var vus = {};

    function signature(el){
        var n = el.tagName.toLowerCase();
        var c = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
        return c.length ? n + '.' + c.join('.') : n;
    }

    document.querySelectorAll('body *').forEach(function(el){
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

        // Fond effectif : premier ancetre opaque. Une image ou un degrade en chemin rend la mesure
        // impossible — on le compte a part plutot que de valider en silence.
        var n = el, fond = null, surImage = false, illisible = false;
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

            if (sn.backgroundImage && sn.backgroundImage !== 'none') { surImage = true; break; }

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
        if (!fond && !surImage) {
            var sr = getComputedStyle(document.documentElement);
            if (sr.backgroundImage && sr.backgroundImage !== 'none') { surImage = true; }
            else { fond = lire(sr.backgroundColor) || [255, 255, 255, 1]; }
        }

        if (surImage) { verdict.nonMesurables++; return; }

        verdict.examines++;

        var taille = parseFloat(s.fontSize) || 16;
        var gras   = (parseInt(s.fontWeight, 10) || 400) >= 700;
        var grand  = taille >= 24 || (taille >= 18.66 && gras);
        var exige  = grand ? 3 : NF_SEUIL;
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
file_put_contents($fichier_sonde, str_replace('NF_SEUIL', (string) $seuil, $sonde));

// ── Base de données : bascule de thème, rétablie quoi qu'il arrive ──────────
$db = nf_connexion();

// Les thèmes non installés rendraient le thème COURANT : on les mesurerait deux fois sous deux noms.
$absents = array_diff($themes, nf_themes_installes($db));

if ($absents)
{
    nf_refus('thème(s) non installé(s) sur ce site : '.implode(', ', $absents).' — les mesurer rendrait le thème courant, pas le leur');
}

nf_theme_temporaire($db);

printf("Thèmes : %s\nPages  : %s\nSeuil  : %s:1 (texte courant) · 3:1 (grand texte)\n\n",
    implode(', ', $themes), implode(', ', $pages), $o['seuil']);

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
    ]);

    foreach ($themes as $theme)
    {
        nf_reglage_poser($db, 'nf_default_theme', $theme);

        foreach ($pages as $page)
        {
            $dom = nf_chrome_dom($serveur->base.$page, ['largeur' => 1400, 'hauteur' => 1000, 'budget' => 7000, 'profil' => $mode]);
            $v   = nf_sonde_verdict($dom, 'nf-contraste-verdict');

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

if (!$rapport)
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

nf_echec(sprintf('%d texte(s) sous le seuil ; %d posés sur une image, non mesurables, à regarder à l\'œil',
    $total_fautes, $total_muets));
