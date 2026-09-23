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
 * Usage : node tests/MiseEnPage/pilote.js <travail.json> <resultat.json>
 *   travail = { base, pages: [chemin…], largeurs: [px…], hauteur, parallele, delai }
 *   resultat = [{ chemin, code, erreur, console: [texte…], ressources: [« code adresse »…],
 *                 mesures: [verdict de la sonde, un par largeur] }]
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');

const FIGER = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';

async function main() {
    const [fichierTravail, fichierResultat] = process.argv.slice(2);

    if (!fichierTravail || !fichierResultat) {
        throw new Error('usage : node pilote.js <travail.json> <resultat.json>');
    }

    const travail = JSON.parse(fs.readFileSync(fichierTravail, 'utf8'));
    const sonde = fs.readFileSync(path.join(__dirname, 'sonde.js'), 'utf8');
    const file = travail.pages.slice();
    const resultats = [];

    const navigateur = await chromium.launch({ channel: 'chrome', headless: true, args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'] });
    const contexte = await navigateur.newContext({
        viewport: { width: travail.largeurs[0], height: travail.hauteur },
        userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36 NeoFragMiseEnPage',
    });

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
            resultat = { chemin, code: 0, erreur: '', console: [], ressources: [], mesures: [] };

            try {
                await onglet.setViewportSize({ width: travail.largeurs[0], height: travail.hauteur });

                const reponse = await onglet.goto(travail.base + chemin, { waitUntil: 'load', timeout: 30000 });

                resultat.code = reponse ? reponse.status() : 0;

                await onglet.addStyleTag({ content: FIGER });
                await onglet.evaluate(() => (document.fonts ? document.fonts.ready.then(() => true) : true));

                for (const largeur of travail.largeurs) {
                    await onglet.setViewportSize({ width: largeur, height: travail.hauteur });
                    await onglet.waitForTimeout(travail.delai);
                    resultat.mesures.push(await onglet.evaluate(sonde));
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
    await navigateur.close();

    fs.writeFileSync(fichierResultat, JSON.stringify(resultats));
}

main().catch((e) => {
    process.stderr.write(String((e && e.stack) || e) + '\n');
    process.exit(2);
});
