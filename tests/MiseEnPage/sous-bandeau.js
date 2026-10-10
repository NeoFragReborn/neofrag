/*
 * CE QUI PASSE SOUS UN BANDEAU DU HAUT DE PAGE — évaluée par `pilote.js`, la page défilée, avec un faux bandeau de démo.
 *
 * Le 2026-10-08, le bandeau vert de la démo (fixé en haut de chaque page) cachait l'en-tête collé de plusieurs thèmes
 * et le haut de leurs panneaux collés — une fois la page défilée seulement : la sonde de mise en page, qui mesure la page
 * en haut, ne pouvait pas le voir. `check-colles` tient le motif dans les feuilles ; cette sonde le constate rendu.
 *
 * Les BANDES : le bandeau de la démo (`#nf-demo-bar`), et tout élément collé ou fixé qui touche le haut de l'écran sur
 * plus de la moitié de sa largeur, sans le remplir — un en-tête, pas un panneau plein écran. Tout autre élément collé
 * ou fixé, visible et ACCROCHÉ (fixé, ou collé et arrêté à sa place), qui chevauche une bande est rapporté, avec la
 * bande ; une bande sous une autre aussi (l'en-tête du thème sous le bandeau de la démo). Un élément collé qui défile
 * encore avec sa colonne passe sous un bandeau comme tout le reste de la page — à bon droit.
 *
 * Ce qui flotte au-dessus de tout par nature — les notifications (« toasts »), une fenêtre, un menu déroulant, une
 * infobulle — n'est ni une bande ni un élément qu'elle masque (2026-10-10).
 *
 * Rend une liste de { el, bande, detail } ; ne modifie rien.
 */
(function () {
    'use strict';

    function nom(e) {
        return e.tagName.toLowerCase() + (e.id ? '#' + e.id : '') + (e.classList.length ? '.' + Array.prototype.slice.call(e.classList, 0, 2).join('.') : '');
    }

    var hauteur = window.innerHeight;
    var largeur = window.innerWidth;
    var colles = [];

    Array.prototype.forEach.call(document.body.querySelectorAll('*'), function (e) {
        var cs = window.getComputedStyle(e);

        if ((cs.position !== 'sticky' && cs.position !== 'fixed') || cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0) {
            return;
        }

        if (colles.some(function (c) { return c.e.contains(e); }) || e.closest('.toast-container, .toast, .modal, .offcanvas, .dropdown-menu, .popover, .tooltip')) {
            return;
        }

        var r = e.getBoundingClientRect();

        if (r.width < 2 || r.height < 2 || r.bottom <= 0 || r.top >= hauteur) {
            return;
        }

        var accroche = cs.position === 'fixed' || (cs.top !== 'auto' && Math.abs(r.top - parseFloat(cs.top)) < 2);

        if (accroche || e.id === 'nf-demo-bar') {
            colles.push({ e: e, r: r, cs: cs });
        }
    });

    var bandes = colles.filter(function (c) {
        return c.e.id === 'nf-demo-bar' || (c.r.top <= 60 && c.r.width >= largeur * 0.5 && c.r.height <= 160);
    });

    var z = function (c) { return parseInt(c.cs.zIndex, 10) || 0; };
    var vus = [];

    colles.forEach(function (c) {
        var r = c.r;

        if (bandes.indexOf(c) !== -1) {
            bandes.forEach(function (b) {
                if (b === c || b.e.contains(c.e) || c.e.contains(b.e) || (z(b) <= z(c) && b.e.id !== 'nf-demo-bar')) {
                    return;
                }

                var recouvre = Math.min(r.bottom, b.r.bottom) - Math.max(r.top, b.r.top);

                if (recouvre > 1 && b.r.top <= r.top) {
                    vus.push({ el: nom(c.e), bande: nom(b.e), detail: Math.round(recouvre) + ' px cachés' });
                }
            });

            return;
        }

        // Un panneau plein écran, une barre posée en bas : ce ne sont pas des éléments que le bandeau masque.
        if ((r.height > hauteur * 0.95 && r.width > largeur * 0.95) || (r.top > hauteur * 0.5 && c.cs.bottom !== 'auto')) {
            return;
        }

        bandes.forEach(function (b) {
            var recouvre = Math.min(r.bottom, b.r.bottom) - Math.max(r.top, b.r.top);
            var cote = Math.min(r.right, b.r.right) - Math.max(r.left, b.r.left);

            if (recouvre > 1 && cote > 1) {
                vus.push({ el: nom(c.e), bande: nom(b.e), detail: Math.round(recouvre) + ' px cachés' });
            }
        });

        // Un panneau collé plus haut que ce qui reste d'écran sous les bandes : son bas ne se voit jamais.
        if (r.height > hauteur * 0.6 && r.bottom > hauteur + 1 && c.cs.position === 'sticky' && parseFloat(c.cs.height) <= hauteur + 1) {
            vus.push({ el: nom(c.e), bande: '', detail: 'coupé en bas de ' + Math.round(r.bottom - hauteur) + ' px' });
        }
    });

    return vus;
})();
