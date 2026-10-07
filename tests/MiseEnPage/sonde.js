/*
 * LA SONDE DE MISE EN PAGE — évaluée dans chaque page, à chaque largeur, par `pilote.js`.
 *
 * Elle ne modifie rien et rend un verdict : ce qu'un œil a relevé le 2026-09-22 sur des
 * captures, et que rien ne mesurait.
 *
 *   deborde         la page est plus large que la fenêtre (le débordement horizontal) ;
 *   tronques        un texte coupé NET par une boîte qui le masque — « Informations serveu » ;
 *   escaliers       des boutons d'un même groupe qui passent à la ligne là où ils ne devraient
 *                   pas — dans une cellule de tableau, ou quand ce sont des icônes seules ;
 *   chevauchements  un texte posé SUR le trait d'un dessin SVG (« 10.15Go » sur l'arc de la
 *                   jauge), ou deux textes l'un sur l'autre ;
 *
 * et, sur demande — « pas uniquement ces éléments-ci mais tout problème visuel, ou
 * autre » — tout ce qu'une page rendue peut montrer de travers et qu'une machine sait constater :
 *
 *   images          une image cassée (rien de chargé), ou déformée (proportions écrasées) ;
 *   icones          une icône dont la classe n'existe pas : elle s'affiche VIDE ;
 *   techniques      du texte technique affiché par erreur — `[object Object]`, `undefined`,
 *                   `%s` non remplacé, une entité HTML visible (`&eacute;`), un message d'erreur PHP ;
 *   contrastes      un texte sous le seuil WCAG AA (4,5:1, ou 3:1 en grand) contre son fond
 *                   effectif — l'algorithme de `check-contraste`, sur toutes les pages ;
 *   horsEcran       un texte coupé par le bord GAUCHE de la fenêtre, qui ne défile pas ;
 *   cibles          sur un téléphone, un bouton ou un champ de moins de 24 px de côté (WCAG 2.2) ;
 *   francais        sur une page ANGLAISE, un texte resté en français (écrit hors des traductions) ;
 *
 * Chaque mesure a ses exclusions, et chacune est justifiée : une sonde qui crie à tort finit
 * ignorée. Les points de suspension VOULUS ne sont pas une troncature ; une barre d'outils de
 * boutons légendés qui passe à la ligne sur un téléphone n'est pas un escalier.
 */
(function () {
    'use strict';

    var LARGEUR = document.documentElement.clientWidth;
    var MAX     = 25;
    var verdict = {
        largeur: LARGEUR, deborde: 0, tronques: [], escaliers: [], chevauchements: [],
        images: [], icones: [], techniques: [], contrastes: [], horsEcran: [], cibles: [],
    };

    /*
     * Mémorisés le temps d'UNE mesure : `visible()` remonte les ancêtres de chaque élément, et les mêmes
     * ancêtres revenaient des milliers de fois. Rien ne bouge pendant la mesure, la mémoire est sûre.
     */
    var styles = new Map();
    var visibles = new Map();

    function style(el) {
        var s = styles.get(el);

        if (!s) {
            s = window.getComputedStyle(el);
            styles.set(el, s);
        }

        return s;
    }

    /*
     * Visible POUR DE VRAI : ni masqué par son propre style, ni caché par un ancêtre — transparent,
     * ou qui rogne son contenu (overflow) sans lui laisser de place. La première version ne regardait
     * que l'élément : les liens des sections repliées du menu latéral, rognés par un conteneur de
     * hauteur nulle, passaient pour visibles et « chevauchaient » tous leurs voisins.
     */
    function visible(el) {
        if (visibles.has(el)) {
            return visibles.get(el);
        }

        var v = visibleCalcule(el);
        visibles.set(el, v);

        return v;
    }

    function visibleCalcule(el) {
        var s = style(el);

        if (s.display === 'none' || s.visibility !== 'visible' || parseFloat(s.opacity) === 0) {
            return false;
        }

        var r = el.getBoundingClientRect();

        if (r.width <= 1 || r.height <= 1) {
            return false;
        }

        for (var a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
            var sa = style(a);

            if (parseFloat(sa.opacity) === 0) {
                return false;
            }

            if (sa.overflowX !== 'visible' || sa.overflowY !== 'visible') {
                var ra = a.getBoundingClientRect();
                var l = Math.min(r.right, ra.right) - Math.max(r.left, ra.left);
                var h = Math.min(r.bottom, ra.bottom) - Math.max(r.top, ra.top);

                if (l < 1 || h < 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /* Une signature lisible et stable : balise, deux premières classes. */
    function nom(el) {
        var n = el.tagName.toLowerCase();
        var classes = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 2);

        if (classes.length) {
            n += '.' + classes.join('.');
        }

        if (el.parentElement && el.parentElement !== document.body) {
            var p = el.parentElement;
            var pc = (typeof p.className === 'string' ? p.className : '').trim().split(/\s+/).filter(Boolean).slice(0, 1);

            n = p.tagName.toLowerCase() + (pc.length ? '.' + pc[0] : '') + ' > ' + n;
        }

        return n;
    }

    /* Le texte porté DIRECTEMENT par l'élément — pas celui de ses descendants. */
    function textesDirects(el) {
        var noeuds = [];

        for (var i = 0; i < el.childNodes.length; i++) {
            var c = el.childNodes[i];

            if (c.nodeType === 3 && c.nodeValue.trim() !== '') {
                noeuds.push(c);
            }
        }

        return noeuds;
    }

    /* La boîte réelle des glyphes : un `Range` sur les nœuds de texte, pas la boîte de l'élément. */
    function boiteTexte(noeuds) {
        var g = Infinity, h = Infinity, d = -Infinity, b = -Infinity;
        var lignes = [];

        noeuds.forEach(function (n) {
            var r = document.createRange();
            r.selectNodeContents(n);

            Array.prototype.forEach.call(r.getClientRects(), function (x) {
                if (x.width < 0.5 || x.height < 0.5) {
                    return;
                }

                lignes.push({ left: x.left, top: x.top, right: x.right, bottom: x.bottom });
                g = Math.min(g, x.left);
                h = Math.min(h, x.top);
                d = Math.max(d, x.right);
                b = Math.max(b, x.bottom);
            });
        });

        // `lignes` : un fragment par ligne. Un texte coupé sur deux lignes a une boîte ENGLOBANTE qui
        // couvre toute la largeur des deux, et qui « touchait » ses voisins — faux chevauchement vu
        // dans le pied de page de nebula (2026-09-22). Les chevauchements se jugent fragment à fragment.
        return g === Infinity ? null : { left: g, top: h, right: d, bottom: b, lignes: lignes };
    }

    function extrait(noeuds) {
        return noeuds.map(function (n) { return n.nodeValue.trim(); }).join(' ').replace(/\s+/g, ' ').slice(0, 50);
    }

    function pousser(liste, entree) {
        if (liste.length < MAX) {
            liste.push(entree);
        }
    }

    // ── 1. Le débordement horizontal ────────────────────────────────────────────────────
    var sw = document.documentElement.scrollWidth;

    if (sw > LARGEUR + 2) {
        verdict.deborde = sw - LARGEUR;

        // Les éléments dont le bord droit dépasse : sans eux, le constat serait vrai et inexploitable.
        var depassent = [];
        var porteurs  = new Set();

        // Un élément dont un ancêtre défile ou rogne (overflow-x autre que visible) ne peut pas élargir
        // la page : il déborde DANS cet ancêtre. Le code d'un bloc `<pre>` défilant « dépassait » de
        // 670 px et masquait le vrai coupable (wiki, 2026-09-23). Si l'ancêtre déborde, c'est lui qui
        // est désigné.
        function contenu(el) {
            for (var a = el.parentElement; a && a !== document.body; a = a.parentElement) {
                var ox = style(a).overflowX;

                if (ox !== 'visible') {
                    return true;
                }
            }

            return false;
        }

        Array.prototype.forEach.call(document.body.querySelectorAll('*'), function (el) {
            var r = el.getBoundingClientRect();

            if (r.right > LARGEUR + 2 && r.width > 0 && r.height > 0 && style(el).position !== 'fixed' && !contenu(el)) {
                depassent.push({ el: el, depasse: Math.round(r.right - LARGEUR) });

                for (var p = el.parentElement; p; p = p.parentElement) {
                    porteurs.add(p);
                }
            }
        });

        // Le plus PROFOND parmi ceux qui dépassent le plus : c'est lui la cause, ses ancêtres n'en sont
        // que les porteurs. L'ordre du document place les descendants APRÈS leurs ancêtres.
        var pire = depassent.reduce(function (m, c) { return Math.max(m, c.depasse); }, 0);
        var retenus = depassent.filter(function (c) { return c.depasse >= pire - 1; }).slice(-2);

        // Et le plus large des éléments qui dépassent SANS qu'aucun de leurs descendants ne dépasse :
        // l'ÉLARGISSEUR. Une cellule de tableau déborde de 147 px parce qu'un bouton, une ligne sans
        // coupure ou un bloc de code l'y force — mais ce contenu, décalé par la marge de la cellule,
        // dépasse de quelques pixels de moins qu'elle, et la seule règle précédente désignait la
        // cellule entière (forum, 2026-09-23).
        var feuille = depassent.filter(function (c) { return !porteurs.has(c.el); })
            .sort(function (a, b) { return b.depasse - a.depasse; })[0];

        if (feuille && retenus.every(function (c) { return c.el !== feuille.el; })) {
            retenus.push({ el: feuille.el, depasse: feuille.depasse, elargisseur: true });
        }

        verdict.coupables = retenus.map(function (c) {
            return { el: (c.elargisseur ? 'élargi par ' : '') + nom(c.el), depasse: c.depasse };
        });
    }

    var tous    = document.body ? document.body.querySelectorAll('*') : [];
    var feuilles = [];   // les éléments porteurs de texte visible, pour les mesures 2 et 4

    Array.prototype.forEach.call(tous, function (el) {
        if (el.closest('svg, script, style, noscript, template, select, option, textarea')) {
            return;
        }

        var noeuds = textesDirects(el);

        if (!noeuds.length || !visible(el)) {
            return;
        }

        var boite = boiteTexte(noeuds);

        if (boite) {
            feuilles.push({ el: el, noeuds: noeuds, boite: boite });
        }
    });

    // ── 2. Le texte tronqué NET ─────────────────────────────────────────────────────────
    /*
     * Un texte dont les glyphes dépassent la zone visible de la première boîte qui MASQUE son
     * débordement (overflow hidden/clip) — l'élément lui-même ou l'un de ses quatre premiers
     * ancêtres. Ne comptent pas : une boîte qui défile (auto/scroll : on y accède), une boîte
     * minuscule (le texte réservé aux lecteurs d'écran), et les points de suspension VOULUS — mais
     * seulement quand ils s'affichent vraiment : `text-overflow: ellipsis` n'agit que sur un bloc,
     * pas sur un conteneur flex, et c'est exactement ce qui tranchait « Informations serveur ».
     */
    feuilles.forEach(function (f) {
        var boite  = null;
        var n      = 0;

        for (var a = f.el; a && a !== document.body && n < 5; a = a.parentElement, n++) {
            var s = style(a);

            if (s.overflowX === 'auto' || s.overflowX === 'scroll') {
                return;
            }

            if (s.overflowX === 'hidden' || s.overflowX === 'clip') {
                boite = a;
                break;
            }
        }

        if (!boite || boite.clientWidth <= 2 || boite.clientHeight <= 2) {
            return;
        }

        var r      = boite.getBoundingClientRect();
        var gauche = r.left + boite.clientLeft;
        var droite = gauche + boite.clientWidth;
        var depasse = Math.max(f.boite.right - droite, gauche - f.boite.left);

        // Sous 2 px, c'est le jambage ou l'approche du dernier glyphe qui déborde de sa boîte — rien
        // ne se voit. « ShadowFox dépasse de 1 px » était ce cas (2026-09-23).
        if (depasse < 2) {
            return;
        }

        var sb = style(boite);

        if (sb.textOverflow === 'ellipsis' && boite === f.el && !/flex|grid/.test(sb.display)) {
            return;   // les points de suspension s'affichent : c'est voulu
        }

        pousser(verdict.tronques, { el: nom(f.el), texte: extrait(f.noeuds), depasse: Math.round(depasse) });
    });

    // ── 3. Les boutons en escalier ──────────────────────────────────────────────────────
    /*
     * Un parent dont TOUS les enfants visibles sont des boutons, et que ces boutons occupent
     * plusieurs lignes. Ne compte que si c'est une cellule de tableau, ou si les boutons sont des
     * icônes seules : une rangée de boutons LÉGENDÉS qui passe à la ligne sur un téléphone est un
     * comportement voulu (les filtres d'une liste, par exemple).
     */
    function estBouton(el) {
        return el.matches('button, .btn, input[type="submit"], input[type="button"]');
    }

    Array.prototype.forEach.call(tous, function (p) {
        var enfants = Array.prototype.filter.call(p.children, visible);

        if (enfants.length < 2 || !enfants.every(estBouton)) {
            return;
        }

        var icones = enfants.every(function (b) { return (b.textContent || b.value || '').trim().length <= 2; });

        if (p.tagName !== 'TD' && p.tagName !== 'TH' && !icones) {
            return;
        }

        var hauts = [];

        enfants.forEach(function (b) {
            var r = b.getBoundingClientRect();
            var tolerance = r.height / 2;

            if (!hauts.some(function (h) { return Math.abs(h - r.top) < tolerance; })) {
                hauts.push(r.top);
            }
        });

        if (hauts.length > 1) {
            pousser(verdict.escaliers, { el: nom(p), boutons: enfants.length, lignes: hauts.length });
        }
    });

    // ── 4. Les chevauchements ───────────────────────────────────────────────────────────
    function croise(a, b) {
        return a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
    }

    /*
     * Le premier ancêtre FIXÉ ou COLLANT d'un élément (ou null). Un calque fixé — la bannière des
     * cookies, une barre collante — recouvre la page À DESSEIN : deux textes n'en sont en conflit que
     * s'ils appartiennent au même calque. La première version comptait vingt « chevauchements » de la
     * bannière sur le contenu de chaque page.
     */
    function calque(el) {
        for (var a = el; a && a !== document.body; a = a.parentElement) {
            var p = style(a).position;

            if (p === 'fixed' || p === 'sticky') {
                return a;
            }
        }

        return null;
    }

    /*
     * La part VISIBLE d'un texte : ce qui dépasse d'un ancêtre qui défile ou rogne (overflow autre que visible), ou de
     * l'élément lui-même (une description tronquée à trois lignes), ne se voit pas. Compter cette part cachée faisait
     * « chevaucher » la colonne voisine : le code d'un <pre> défilant du wiki dans Granite, les descriptions tronquées
     * des cartes d'extensions de l'administration — une centaine de faux défauts (2026-10-07). Les textes coupés (mesure
     * 2) gardent, eux, leur boîte entière : c'est la part cachée qu'ils jugent.
     */
    function cadreVisible(el) {
        var r = { left: -Infinity, top: -Infinity, right: Infinity, bottom: Infinity };

        for (var a = el; a && a !== document.body; a = a.parentElement) {
            var s = style(a);

            if (s.overflowX !== 'visible' || s.overflowY !== 'visible') {
                var b = a.getBoundingClientRect();

                if (s.overflowX !== 'visible') {
                    r.left  = Math.max(r.left, b.left);
                    r.right = Math.min(r.right, b.right);
                }

                if (s.overflowY !== 'visible') {
                    r.top    = Math.max(r.top, b.top);
                    r.bottom = Math.min(r.bottom, b.bottom);
                }
            }
        }

        return r;
    }

    function lignesVisibles(f) {
        if (!f.visibles) {
            var c = cadreVisible(f.el);

            f.visibles = f.boite.lignes.map(function (x) {
                return { left: Math.max(x.left, c.left), top: Math.max(x.top, c.top), right: Math.min(x.right, c.right), bottom: Math.min(x.bottom, c.bottom) };
            }).filter(function (x) {
                return x.right - x.left > 0.5 && x.bottom - x.top > 0.5;
            });
        }

        return f.visibles;
    }

    /* a. Un texte posé sur le TRAIT d'un dessin SVG : on éprouve 15 points de la boîte des glyphes. */
    var traces = document.querySelectorAll('svg path, svg circle, svg ellipse, svg line, svg polyline, svg polygon');

    Array.prototype.forEach.call(traces, function (t) {
        var st = style(t);

        if (st.stroke === 'none' || parseFloat(st.strokeWidth) <= 0 || typeof t.isPointInStroke !== 'function' || !visible(t.ownerSVGElement || t)) {
            return;
        }

        var cadre = t.getBoundingClientRect();
        var ecran = t.getScreenCTM();

        if (!ecran) {
            return;
        }

        var inverse = ecran.inverse();
        var point   = (t.ownerSVGElement || t).createSVGPoint();

        feuilles.forEach(function (f) {
            // Un texte d'un AUTRE calque (la bannière des cookies, une barre d'objets collée en bas de l'écran) passe
            // au-dessus d'un graphique à dessein : comme pour deux textes (b), seul un même calque fait conflit. Sans
            // cela, la barre fixe de Blockcraft « chevauchait » le graphique des distinctions (2026-10-07).
            if (!croise(f.boite, cadre) || calque(f.el) !== calque(t) || !lignesVisibles(f).length) {
                return;
            }

            var touches = 0;

            for (var i = 0; i <= 4; i++) {
                for (var j = 0; j <= 2; j++) {
                    point.x = f.boite.left + 1 + (f.boite.right - f.boite.left - 2) * i / 4;
                    point.y = f.boite.top + 1 + (f.boite.bottom - f.boite.top - 2) * j / 2;

                    if (t.isPointInStroke(point.matrixTransform(inverse))) {
                        touches++;
                    }
                }
            }

            if (touches > 0) {
                pousser(verdict.chevauchements, { el: nom(f.el), texte: extrait(f.noeuds), sur: 'le trait de ' + nom(t.ownerSVGElement || t), points: touches });
            }
        });
    });

    /* b. Deux textes l'un sur l'autre : recouvrement de plus d'un tiers de la plus petite boîte. */
    var grille = {};
    var CASE   = 120;

    feuilles.forEach(function (f, i) {
        for (var x = Math.floor(f.boite.left / CASE); x <= Math.floor(f.boite.right / CASE); x++) {
            for (var y = Math.floor(f.boite.top / CASE); y <= Math.floor(f.boite.bottom / CASE); y++) {
                (grille[x + ':' + y] = grille[x + ':' + y] || []).push(i);
            }
        }
    });

    var vus = {};

    Object.keys(grille).forEach(function (k) {
        var ids = grille[k];

        for (var a = 0; a < ids.length; a++) {
            for (var b = a + 1; b < ids.length; b++) {
                var cle = ids[a] + '-' + ids[b];

                if (vus[cle]) {
                    continue;
                }

                vus[cle] = 1;

                var A = feuilles[ids[a]], B = feuilles[ids[b]];

                if (A.el.contains(B.el) || B.el.contains(A.el) || !croise(A.boite, B.boite) || calque(A.el) !== calque(B.el)) {
                    continue;
                }

                var conflit = lignesVisibles(A).some(function (x) {
                    return lignesVisibles(B).some(function (y) {
                        var l = Math.min(x.right, y.right) - Math.max(x.left, y.left);
                        var h = Math.min(x.bottom, y.bottom) - Math.max(x.top, y.top);
                        var petite = Math.min((x.right - x.left) * (x.bottom - x.top), (y.right - y.left) * (y.bottom - y.top));

                        return l > 0 && h > 0 && petite > 0 && l * h > petite / 3;
                    });
                });

                if (conflit) {
                    pousser(verdict.chevauchements, { el: nom(A.el), texte: extrait(A.noeuds), sur: 'le texte « ' + extrait(B.noeuds) + ' » (' + nom(B.el) + ')', points: 0 });
                }
            }
        }
    });

    // ── 5. Les images cassées ou déformées ──────────────────────────────────────────────
    /*
     * Cassée : le navigateur a fini, et n'a rien eu (`naturalWidth` nul). Déformée : affichée avec
     * des proportions qui s'écartent de plus de 8 % des siennes, alors que rien ne la recadre
     * (`object-fit` à `fill`, la valeur par défaut). Les images minuscules (pixels de suivi,
     * espaceurs) ne comptent pas.
     */
    Array.prototype.forEach.call(document.images, function (img) {
        if (!visible(img)) {
            return;
        }

        var src = (img.currentSrc || img.src || '').replace(location.origin, '').slice(0, 90);

        if (img.complete && img.naturalWidth === 0) {
            pousser(verdict.images, { el: nom(img), defaut: 'cassée', src: src });
            return;
        }

        var r = img.getBoundingClientRect();
        var si = style(img);

        if (!img.naturalWidth || !img.naturalHeight || r.width < 16 || r.height < 16 || si.objectFit !== 'fill') {
            return;
        }

        // Les proportions se jugent sur la boîte du CONTENU : une image encadrée (`img-thumbnail`,
        // 4 px de marge et 1 px de cadre) a une boîte extérieure plus haute, sans être déformée —
        // la couverture de profil passait pour « déformée de 11 % » (2026-09-23).
        var l = r.width - parseFloat(si.paddingLeft) - parseFloat(si.paddingRight) - parseFloat(si.borderLeftWidth) - parseFloat(si.borderRightWidth);
        var h = r.height - parseFloat(si.paddingTop) - parseFloat(si.paddingBottom) - parseFloat(si.borderTopWidth) - parseFloat(si.borderBottomWidth);

        if (l < 16 || h < 16) {
            return;
        }

        var ecart = Math.abs((l / h) / (img.naturalWidth / img.naturalHeight) - 1);

        if (ecart > 0.08) {
            pousser(verdict.images, { el: nom(img), defaut: 'déformée de ' + Math.round(ecart * 100) + ' %', src: src });
        }
    });

    // ── 6. Les icônes inconnues ─────────────────────────────────────────────────────────
    /*
     * Une icône de police (Font Awesome) dont la classe n'existe pas n'a pas de contenu `::before` :
     * elle s'affiche vide, sans une erreur nulle part. C'est ce que laisse une classe de l'ancienne
     * version de la bibliothèque qui a changé de nom.
     */
    Array.prototype.forEach.call(document.querySelectorAll('i[class*="fa-"], span[class*="fa-"]'), function (i) {
        // Une icône inconnue fait zéro pixel de large : `visible()` l'écarterait. On demande donc
        // seulement qu'elle soit RENDUE — une boîte, même vide — et qu'aucun ancêtre ne la cache.
        if (!i.getClientRects().length || style(i).visibility !== 'visible' || (i.parentElement && !visible(i.parentElement))) {
            return;
        }

        var contenu = window.getComputedStyle(i, '::before').content;

        if (contenu === 'none' || contenu === 'normal' || contenu === '""') {
            pousser(verdict.icones, { el: nom(i), classes: String(i.className).slice(0, 60) });
        }
    });

    // ── 7. Le texte technique affiché par erreur ────────────────────────────────────────
    /*
     * Ce qu'aucun visiteur ne devrait lire : un objet JavaScript converti en texte, une valeur non
     * définie, un gabarit de traduction non rempli, une entité HTML échappée deux fois, un message
     * d'erreur de PHP. Le code montré VOLONTAIREMENT (`code`, `pre`, champs de saisie) ne compte pas.
     */
    var MOTIFS = [
        [/\[object (Object|Array|HTMLElement)\]/, 'objet converti en texte'],
        [/(^|[\s(:])(undefined|NaN)([\s).,:]|$)/, 'valeur non définie'],
        [/%[sd](\s|$|[.,;:)])/, 'gabarit de traduction non rempli'],
        [/&(?:[a-z]{2,8}|#\d{2,5});/i, 'entité HTML affichée'],
        [/\b(Notice|Warning|Deprecated|Fatal error|Parse error):\s/, 'message d’erreur PHP'],
        [/<\?(php|=)|\{\{|\}\}/, 'gabarit non interprété'],
    ];

    feuilles.forEach(function (f) {
        if (f.el.closest('code, pre, kbd, samp, textarea, input, [contenteditable]')) {
            return;
        }

        var texte = f.noeuds.map(function (n) { return n.nodeValue; }).join(' ');

        MOTIFS.forEach(function (m) {
            var t = m[0].exec(texte);

            if (t) {
                var i = Math.max(0, t.index - 15);
                pousser(verdict.techniques, { el: nom(f.el), defaut: m[1], texte: texte.slice(i, i + 50).replace(/\s+/g, ' ').trim() });
            }
        });
    });

    // ── 8. Le contraste ─────────────────────────────────────────────────────────────────
    /*
     * L'algorithme de `check-contraste`, repris tel quel : le fond EFFECTIF est celui du premier
     * ancêtre opaque ; une image ou un dégradé en chemin rend la mesure impossible — on s'abstient ;
     * une couleur qu'on ne sait pas LIRE n'est pas une couleur absente — on s'abstient aussi, sans
     * quoi on comparerait le texte au mauvais fond. Les couleurs dérivées par `color-mix()` arrivent
     * en `color(srgb r g b)`, et se lisent.
     */
    function canal(c) { c = c / 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
    function lum(rgb) { return 0.2126 * canal(rgb[0]) + 0.7152 * canal(rgb[1]) + 0.0722 * canal(rgb[2]); }

    function couleur(v) {
        v = v || '';

        var m = /rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?\)/.exec(v);

        if (m) {
            return [+m[1], +m[2], +m[3], m[4] === undefined ? 1 : +m[4]];
        }

        m = /color\(\s*srgb\s+([\d.eE+-]+)\s+([\d.eE+-]+)\s+([\d.eE+-]+)(?:\s*\/\s*([\d.eE+-]+))?\s*\)/.exec(v);

        return m ? [+m[1] * 255, +m[2] * 255, +m[3] * 255, m[4] === undefined ? 1 : +m[4]] : null;
    }

    feuilles.forEach(function (f) {
        // Un séparateur ou un signe décoratif — « / », « · », « | » — ne porte aucune information à lire.
        if (!/[\p{L}\p{N}]/u.test(extrait(f.noeuds))) {
            return;
        }

        // Une commande DÉSACTIVÉE est exemptée par la norme (WCAG 1.4.3, « composants inactifs ») :
        // son texte atténué dit justement qu'on ne peut pas s'en servir — « Points insuffisants »
        // dans la boutique (2026-09-23).
        if (f.el.closest('.disabled, :disabled, [aria-disabled="true"]')) {
            return;
        }

        var s = style(f.el);
        var texte = couleur(s.color);

        if (!texte || texte[3] < 0.5 || parseFloat(s.opacity) < 0.1) {
            return;
        }

        var fond = null;

        for (var n = f.el; n && n !== document.documentElement; n = n.parentElement) {
            var sn = style(n);

            if (sn.backgroundImage && sn.backgroundImage !== 'none') {
                return;   // sur une image ou un dégradé : pas de fond mesurable
            }

            var brut = (sn.backgroundColor || '').trim();
            var bg = couleur(brut);

            if (!bg && brut !== '' && brut !== 'transparent' && !/^rgba\(0,\s*0,\s*0,\s*0\)$/.test(brut)) {
                return;   // une couleur illisible : on ne devine pas
            }

            if (bg && bg[3] >= 0.9) {
                fond = bg;
                break;
            }
        }

        if (!fond) {
            var sr = style(document.documentElement);

            if (sr.backgroundImage && sr.backgroundImage !== 'none') {
                return;
            }

            fond = couleur(sr.backgroundColor) || [255, 255, 255, 1];
        }

        var la = lum(texte), lb = lum(fond);
        var ratio = (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
        var taille = parseFloat(s.fontSize) || 16;
        var grand = taille >= 24 || (taille >= 18.66 && (parseInt(s.fontWeight, 10) || 400) >= 700);
        var exige = grand ? 3 : 4.5;

        if (ratio < exige) {
            pousser(verdict.contrastes, { el: nom(f.el), texte: extrait(f.noeuds), ratio: Math.round(ratio * 100) / 100, exige: exige, couleur: s.color, fond: 'rgb(' + fond.slice(0, 3).map(Math.round).join(', ') + ')' });
        }
    });

    // ── 9. Le texte coupé par le bord gauche ────────────────────────────────────────────
    /*
     * Rien ne défile vers la gauche : un texte qui passe sous le bord gauche est perdu. Sauf dans un ancêtre qui défile
     * ou rogne de côté : l'onglet d'une bande qui défile (le menu de l'espace membre au téléphone) s'y retrouve en
     * faisant défiler la bande — il était compté perdu dans tous les thèmes (2026-10-07).
     */
    function dansUneBande(el) {
        for (var a = el.parentElement; a && a !== document.body; a = a.parentElement) {
            if (style(a).overflowX !== 'visible') {
                return true;
            }
        }

        return false;
    }

    feuilles.forEach(function (f) {
        if (f.boite.left < -2 && f.boite.right > 0 && !dansUneBande(f.el)) {
            pousser(verdict.horsEcran, { el: nom(f.el), texte: extrait(f.noeuds), depasse: Math.round(-f.boite.left) });
        }
    });

    // ── 10. Les cibles trop petites pour un doigt ───────────────────────────────────────
    /*
     * Sur un téléphone seulement (fenêtre de 480 px au plus) : un bouton, un champ, une liste
     * déroulante ou une icône cliquable de moins de 24 px de côté — le minimum de WCAG 2.2. Les liens
     * au fil d'un texte sont exemptés, comme le prévoit la règle.
     */
    if (LARGEUR <= 480) {
        var cibles = document.querySelectorAll('button, .btn, input:not([type="hidden"]), select, a');

        Array.prototype.forEach.call(cibles, function (c) {
            if (!visible(c)) {
                return;
            }

            if (c.tagName === 'A' && !c.classList.contains('btn') && (c.textContent || '').trim().length > 2) {
                return;   // un lien au fil du texte
            }

            if (c.type === 'checkbox' || c.type === 'radio') {
                return;   // leur étiquette, cliquable, porte la cible
            }

            var r = c.getBoundingClientRect();

            if (r.width < 24 && r.height < 24) {
                pousser(verdict.cibles, { el: nom(c), texte: ((c.textContent || c.value || c.getAttribute('aria-label') || c.getAttribute('title') || '').trim()).slice(0, 30), taille: Math.round(r.width) + '×' + Math.round(r.height) + ' px' });
            }
        });
    }

    // ── 11. Le texte resté en français sur une page anglaise ────────────────────────────
    /*
     * « Tout le CMS doit être multilingue » (2026-09-23). `check-langs` ne voit que les textes
     * passés par `lang()` ; un texte écrit en dur lui échappe. Ici, on regarde ce que la page ANGLAISE
     * affiche vraiment — texte, infobulle, texte indicatif, libellé d'accessibilité — et on relève ce
     * qui a l'air français : une lettre accentuée propre au français, ou un mot courant de l'interface.
     * Réservé à l'anglais : l'espagnol, l'italien et le portugais partagent des accents et des mots
     * (« la », « le ») avec le français. Les noms de langue du sélecteur sont écrits dans leur langue,
     * à dessein.
     */
    verdict.francais = [];

    if ((document.documentElement.lang || '').slice(0, 2).toLowerCase() === 'en') {
        var ACCENTS = /[àâçéèêëîïôûùÿœæ]/i;
        var MOTS = /(^|[^\p{L}])(le|la|les|des|du|une|et|est|pour|avec|dans|sur|par|pas|vos|votre|aucun|aucune|cette|ces|qui|aux|être|sont|tous|toutes|mes|rechercher|modifier|supprimer|retour|enregistrer|annuler|valider|ajouter|fermer|connexion|inscription|envoyer|membres|accueil|voir|suivant|lire|partager|publier|brouillon|nouveau|nouvelle|fichier|dossier|mot de passe|déconnexion)(?=$|[^\p{L}])/iu;
        var LANGUES = /^(Français|Deutsch|English|Español|Italiano|Português)$/;
        // Des noms propres accentués — les auteurs d'origine du CMS, cités dans chaque crédit — ne
        // sont pas du français à traduire. (« mon » a quitté la liste : c'est « Mon », lundi, en anglais.)
        var NOMS = /Michaël BILCOT|Jérémy VALENTIN/g;

        var releve = function (texte, el, ou) {
            texte = (texte || '').replace(NOMS, ' ').replace(/\s+/g, ' ').trim();

            if (texte.length < 2 || LANGUES.test(texte) || !/\p{L}/u.test(texte)) {
                return;
            }

            if (ACCENTS.test(texte) || MOTS.test(texte)) {
                pousser(verdict.francais, { el: nom(el), texte: texte.slice(0, 70), ou: ou });
            }
        };

        feuilles.forEach(function (f) {
            releve(f.noeuds.map(function (n) { return n.nodeValue; }).join(' '), f.el, 'texte');
        });

        Array.prototype.forEach.call(document.querySelectorAll('[title], [placeholder], [aria-label], input[type="submit"][value], input[type="button"][value]'), function (el) {
            if (!visible(el) && !el.closest('[data-bs-toggle]')) {
                return;
            }

            ['title', 'placeholder', 'aria-label', 'data-bs-original-title'].forEach(function (a) {
                if (el.hasAttribute(a)) {
                    releve(el.getAttribute(a), el, a);
                }
            });

            if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) {
                releve(el.value, el, 'value');
            }
        });
    }

    return verdict;
})();
