'use strict';

/*
 * Le parcours du visiteur ÉTRANGER : il arrive sur un contenu écrit dans une seule langue et clique
 * sur son drapeau.
 *
 * C'est le geste qui rendait 404 jusqu'au 2026-09-21 — soixante adresses mortes sur soixante-douze,
 * mesurées sur la démonstration. Le correctif est couvert côté base (`LangueDuContenuTest`) et côté
 * réponse HTTP (`check-langues-contenu`), mais personne ne vérifiait le GESTE : ouvrir le sélecteur,
 * cliquer, et voir ce que le visiteur voit. Le sélecteur poste un formulaire et le serveur redirige :
 * exactement le genre d'enchaînement qu'une requête isolée ne reproduit pas.
 */

const { test, expect } = require('@playwright/test');

test.describe('Le visiteur change de langue', () => {

    test('un contenu monolingue reste lisible dans une autre langue, et le dit', async ({ page }) => {
        // On part de la LISTE pour atteindre un contenu réel, plutôt que d'écrire une adresse en
        // dur : la démonstration se repeuple, et une adresse figée vieillit à la première remise
        // à zéro.
        await page.goto('fr/news', { waitUntil: 'domcontentloaded' });

        const lien = page.locator('a[href*="/news/"]:not([href*="#"])').first();
        expect(await lien.count(), 'il faut une actualité pour mesurer quoi que ce soit').toBeGreaterThan(0);

        await lien.click();
        await page.waitForLoadState('domcontentloaded');

        const adresseFr = page.url();
        expect(adresseFr).toContain('/news/');

        // Le sélecteur de langue est un vrai formulaire, dans un menu déroulant : il faut l'ouvrir
        // avant de pouvoir cliquer dedans, comme un visiteur le ferait.
        const selecteur = page.locator('form.fg-lang');
        expect(await selecteur.count(), 'la page doit porter le sélecteur de langue').toBeGreaterThan(0);

        const bascule = selecteur.locator('[data-bs-toggle="dropdown"]').first();

        if (await bascule.count() > 0) {
            await bascule.click();
        }

        const anglais = selecteur.locator('button[name="language"][value="en"]');
        expect(await anglais.count(), 'le sélecteur doit proposer l\'anglais').toBeGreaterThan(0);

        await anglais.click({ force: true });
        await page.waitForLoadState('domcontentloaded');

        // 1. La page répond. C'est le défaut d'origine : elle rendait 404.
        await expect(page.locator('body')).toBeVisible();
        expect(page.url(), 'on doit rester sur une page d\'actualité').toContain('/news/');

        // 2. Elle le DIT. Servir une version française sans l'annoncer ferait passer une page pour
        //    la version anglaise du site.
        const bandeau = page.locator('.alert-info .fa-language, .alert-info i.fas.fa-language');
        await expect(bandeau.first(), 'un bandeau doit annoncer la langue réellement servie').toBeVisible();

        // 3. Elle ne ment pas aux moteurs : seules les langues qui existent sont annoncées.
        const alternes = await page.locator('link[rel="alternate"][hreflang]').evaluateAll(
            (liens) => liens.map((l) => l.getAttribute('hreflang'))
        );

        expect(alternes, 'aucune langue annoncée ne doit mener à une page absente').not.toContain('en');
    });
});
