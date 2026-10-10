'use strict';

/*
 * LE PILOTE — la moitié navigateur de `tools/check-mise-en-page.php`.
 *
 * Un navigateur, plusieurs onglets en parallèle : chaque page est chargée UNE fois, puis la fenêtre
 * prend chaque largeur demandée et `sonde.js` y rend son verdict. Relancer Chrome pour chaque page et
 * chaque largeur — ce que font les contrôles plus anciens — aurait demandé des heures pour des
 * milliers de rendus ; redimensionner la fenêtre suffit, les règles `@media` s'appliquent aussitôt.
 *
 * Les animations et les transitions sont figées avant la première mesure : une capture prise pendant
 * la transition d'un carrousel a déjà fait signaler à tort un défaut de mise en page.
 *
 * Autour de la sonde, le pilote relève ce que la page ne montre pas mais que le navigateur voit :
 * les erreurs JavaScript, les messages d'erreur de la console (dont les violations de la politique
 * de sécurité), et les fichiers DU SITE qui ne se chargent pas — une feuille de style, un script,
 * une image ou une police en 404 ne casse pas la page, il la laisse de travers.
 *
 * Puis, si `bandeau` le demande, la page reçoit un faux bandeau de démo de cette hauteur, rangé par `NF.bandeaux()`
 * comme le vrai, et `sous-bandeau.js` juge, page en haut puis défilée, ce qui passe dessous — à quelques largeurs
 * seulement (`largeursBandeau`) : c'est le défilement qui coûte. `bandeau: -1` fait la passe sans rien poser : le site
 * a le sien (la démonstration).
 *
 * Pour un site qu'on ne sert pas soi-même (la démonstration) : `cookies` (le choix du thème), `stockage` (le mode jour
 * ou nuit, rangé dans le localStorage), et `connexion` — le VRAI formulaire, rempli une fois avant les pages, avec le
 * compte annoncé sur le site ; jamais une session posée à la main. La déconnexion suit la dernière page.
 *
 * Usage : node tests/MiseEnPage/pilote.js <travail.json> <resultat.json>
 *   travail = { base, pages: [chemin…], largeurs: [px…], hauteur, parallele, delai, bandeau: px, largeursBandeau: [px…],
 *               cookies: [{ name, value }…], stockage: { clé: valeur }, connexion: { chemin, login, motdepasse } }
 *   resultat = [{ chemin, code, erreur, connecte, console: [texte…], ressources: [« code adresse »…],
 *                 mesures: [verdict de la sonde, un par largeur], bandeau: [{ largeur, el, bande, detail }…] }]
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');

const FIGER = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';

/*
 * Une largeur, et les bandeaux recalés. Un onglet en arrière-plan — le pilote en mène plusieurs à la fois — ne reçoit
 * l'événement `resize` qu'en retard : `NF.bandeaux()` n'avait pas encore recalé `--nf-haut`, et l'en-tête de Chronique
 * « passait sous » le bandeau de la démo au téléphone, ce qu'aucune page visible ne montre (2026-10-10). On le recale
 * comme le ferait une page au premier plan.
 */
async function largeur(onglet, l, hauteur) {
    await onglet.setViewportSize({ width: l, height: hauteur });
    await onglet.evaluate(() => { if (window.NF && window.NF.bandeaux) window.NF.bandeaux(); }).catch(() => {});
}

async function main() {
    const [fichierTravail, fichierResultat] = process.argv.slice(2);

    if (!fichierTravail || !fichierResultat) {
        throw new Error('usage : node pilote.js <travail.json> <resultat.json>');
    }

    const travail = JSON.parse(fs.readFileSync(fichierTravail, 'utf8'));
    const sonde = fs.readFileSync(path.join(__dirname, 'sonde.js'), 'utf8');
    const sousBandeau = fs.readFileSync(path.join(__dirname, 'sous-bandeau.js'), 'utf8');
    const file = travail.pages.slice();
    const resultats = [];

    const navigateur = await chromium.launch({ channel: 'chrome', headless: true, args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'] });
    const contexte = await navigateur.newContext({
        viewport: { width: travail.largeurs[0], height: travail.hauteur },
        userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36 NeoFragMiseEnPage',
    });
    const hote = new URL(travail.base).hostname;
    let deconnexion = null;

    // La connexion d'abord, dans le thème par défaut (tous les thèmes n'ont pas le formulaire sur leur accueil) ; le
    // choix du thème vient ensuite.
    if (travail.connexion) {
        const p = await contexte.newPage();
        await p.goto(travail.base + travail.connexion.chemin, { waitUntil: 'networkidle', timeout: 30000 });
        await p.fill('input[name="login"]', travail.connexion.login);
        await p.fill('input[name="password"]', travail.connexion.motdepasse);
        await Promise.all([p.waitForLoadState('networkidle'), p.press('input[name="password"]', 'Enter')]);
        await p.waitForTimeout(1500);
        deconnexion = await p.locator('a[href*="logout"]').first().getAttribute('href').catch(() => null);
        await p.close();

        if (!deconnexion) {
            throw new Error('connexion refusée : aucun lien de déconnexion après le formulaire de ' + travail.connexion.chemin);
        }
    }

    if ((travail.cookies || []).length) {
        await contexte.addCookies(travail.cookies.map((c) => ({ name: c.name, value: String(c.value), domain: hote, path: '/' })));
    }

    if (travail.stockage && Object.keys(travail.stockage).length) {
        await contexte.addInitScript((s) => {
            try { Object.keys(s).forEach((k) => localStorage.setItem(k, s[k])); } catch (e) { /* stockage refusé : le mode par défaut */ }
        }, travail.stockage);
    }

    async function ouvrier() {
        const onglet = await contexte.newPage();
        const origine = new URL(travail.base).origin;
        let resultat = null;

        onglet.on('pageerror', (e) => {
            if (resultat && resultat.console.length < 10) {
                resultat.console.push('erreur JavaScript : ' + String((e && e.message) || e).split('\n')[0].slice(0, 160));
            }
        });

        onglet.on('console', (m) => {
            // « Failed to load resource » double ce que `response` relève déjà, avec l'adresse en moins.
            if (resultat && m.type() === 'error' && !/Failed to load resource/.test(m.text()) && resultat.console.length < 10) {
                resultat.console.push('console : ' + m.text().split('\n')[0].slice(0, 160));
            }
        });

        onglet.on('response', (r) => {
            if (resultat && r.status() >= 400 && r.url().startsWith(origine) && resultat.ressources.length < 10) {
                resultat.ressources.push(r.status() + ' ' + r.url().slice(origine.length).split('?')[0]);
            }
        });

        onglet.on('requestfailed', (r) => {
            const e = r.failure();

            // Une navigation interrompue par la suivante n'est pas un échec du site.
            if (resultat && r.url().startsWith(origine) && e && !/ERR_ABORTED/.test(e.errorText) && resultat.ressources.length < 10) {
                resultat.ressources.push(e.errorText + ' ' + r.url().slice(origine.length).split('?')[0]);
            }
        });

        while (file.length) {
            const chemin = file.shift();
            resultat = { chemin, code: 0, erreur: '', connecte: false, console: [], ressources: [], mesures: [], bandeau: [] };

            try {
                await onglet.setViewportSize({ width: travail.largeurs[0], height: travail.hauteur });

                const reponse = await onglet.goto(travail.base + chemin, { waitUntil: 'load', timeout: 30000 });

                resultat.code = reponse ? reponse.status() : 0;
                resultat.connecte = await onglet.evaluate(() => !!document.querySelector('a[href*="logout"]'));

                await onglet.addStyleTag({ content: FIGER });
                await onglet.evaluate(() => (document.fonts ? document.fonts.ready.then(() => true) : true));

                for (const l of travail.largeurs) {
                    await largeur(onglet, l, travail.hauteur);
                    await onglet.waitForTimeout(travail.delai);
                    resultat.mesures.push(await onglet.evaluate(sonde));
                }

                if (travail.bandeau > 0) {
                    await onglet.evaluate((h) => {
                        const b = document.createElement('div');
                        b.id = 'nf-demo-bar';
                        b.setAttribute('data-nf-bandeau', '');
                        b.style.cssText = 'height:' + h + 'px;background:#f0f';
                        document.body.insertBefore(b, document.body.firstChild);
                        if (window.NF && window.NF.bandeaux) window.NF.bandeaux();
                    }, travail.bandeau);
                }

                if (travail.bandeau) {
                    for (const l of travail.largeursBandeau || []) {
                        const vus = new Map();
                        await largeur(onglet, l, travail.hauteur);

                        for (const y of [0, 300, 700, 1600]) {
                            // Même retard pour l'événement `scroll` d'un onglet en arrière-plan : l'en-tête qui se
                            // replie au défilement, les bandeaux, réagissent ici comme sur une page au premier plan.
                            await onglet.evaluate((y) => {
                                window.scrollTo(0, y);
                                window.dispatchEvent(new Event('scroll'));
                                if (window.NF && window.NF.bandeaux) window.NF.bandeaux();
                            }, y);
                            await onglet.waitForTimeout(travail.delai);

                            for (const x of await onglet.evaluate(sousBandeau)) {
                                vus.set(x.el + '|' + x.bande, x);
                            }
                        }

                        for (const x of vus.values()) {
                            resultat.bandeau.push(Object.assign({ largeur: l }, x));
                        }
                    }
                }
            } catch (e) {
                resultat.erreur = String((e && e.message) || e).split('\n')[0];
            }

            // La page elle-même en erreur n'est pas une « ressource » : son code est déjà relevé.
            resultat.ressources = resultat.ressources.filter((x) => !x.endsWith(' ' + chemin));
            resultats.push(resultat);
            resultat = null;
        }

        await onglet.close();
    }

    await Promise.all(Array.from({ length: Math.max(1, travail.parallele) }, ouvrier));

    // Rien ne reste ouvert derrière nous.
    if (deconnexion) {
        const p = await contexte.newPage();
        await p.goto(new URL(deconnexion, travail.base).href, { waitUntil: 'load', timeout: 30000 }).catch(() => {});
    }

    await navigateur.close();

    fs.writeFileSync(fichierResultat, JSON.stringify(resultats));
}

main().catch((e) => {
    process.stderr.write(String((e && e.stack) || e) + '\n');
    process.exit(2);
});
