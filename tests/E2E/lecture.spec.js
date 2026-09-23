'use strict';

/*
 * Le parcours du VISITEUR : ce que fait quelqu'un qui arrive sur le site sans compte.
 *
 * Ces gestes-là sont les plus fréquents du produit, et ce sont ceux qu'aucun contrôle ne suivait :
 * `check-liens` vérifie que les adresses répondent, `check-js-console` qu'aucun script ne plante,
 * mais personne ne vérifiait qu'on peut ALLER d'un écran au suivant en cliquant — or c'est entre
 * deux requêtes que l'interface casse.
 *
 * DEUX RÈGLES TENUES DANS TOUS LES PARCOURS
 *
 *   1. **Des chemins RELATIFS**, jamais commençant par `/`. Un chemin absolu remplace le chemin de
 *      la base : avec la base `…/demo/`, `goto('/fr/news')` part sur `…/fr/news`, un autre site.
 *   2. **Aucun `test.skip`.** Un parcours sauté ressemble à un parcours réussi dans un rapport, et
 *      c'est ainsi qu'on finit par ne plus rien mesurer. Quand une donnée manque, le parcours
 *      ÉCHOUE en le disant.
 */

const { test, expect } = require('@playwright/test');

test.describe('Le visiteur lit le site', () => {

    test('l\'accueil répond, au bon endroit, et porte la navigation', async ({ page, baseURL }) => {
        const reponse = await page.goto('', { waitUntil: 'domcontentloaded' });

        expect(reponse.status(), 'l\'accueil doit répondre').toBeLessThan(400);

        // Où a-t-on réellement atterri ? Un parcours qui mesure le mauvais site passe ou échoue
        // pour de mauvaises raisons, et c'est la pire sorte de verdict.
        const attendu = new URL(baseURL).pathname.replace(/\/+$/, '');

        if (attendu !== '') {
            expect(page.url(), 'le parcours doit rester sur le site visé').toContain(attendu);
        }

        await expect(page.locator('body')).toBeVisible();

        const liens = await page.locator('a[href]').count();
        expect(liens, 'l\'accueil doit proposer des liens').toBeGreaterThan(5);
    });

    test('une actualité s\'ouvre depuis sa liste, en cliquant', async ({ page }) => {
        await page.goto('fr/news', { waitUntil: 'domcontentloaded' });

        // Une page de DÉTAIL porte un identifiant et un slug ; on écarte les ancres de commentaires,
        // qui mènent au même endroit et feraient croire à un clic qui n'a rien ouvert.
        const lien = page.locator('a[href*="/news/"]:not([href*="#"])').first();

        expect(await lien.count(), 'la liste des actualités doit proposer au moins une actualité').toBeGreaterThan(0);

        const titre = (await lien.textContent() || '').trim();
        await lien.click();
        await page.waitForLoadState('domcontentloaded');

        expect(page.url(), 'le clic doit mener à une page d\'actualité').toContain('/news/');
        await expect(page.locator('body')).toBeVisible();

        if (titre.length > 8) {
            // Le titre cliqué doit se retrouver dans la page atteinte : c'est ce qui distingue
            // « la page répond » de « la page répond avec le bon contenu ».
            await expect(page.locator('body')).toContainText(titre.slice(0, 20));
        }
    });

    test('le forum mène d\'une catégorie à ses sujets', async ({ page }) => {
        const reponse = await page.goto('fr/forum', { waitUntil: 'domcontentloaded' });

        expect(reponse.status(), 'le forum doit répondre').toBeLessThan(400);

        const categorie = page.locator('a[href*="/forum/"]:not([href*="#"]):not([href*="."])').first();

        expect(await categorie.count(), 'le forum doit proposer au moins une catégorie').toBeGreaterThan(0);

        await categorie.click();
        await page.waitForLoadState('domcontentloaded');

        expect(page.url()).toContain('/forum/');
        await expect(page.locator('body')).toBeVisible();
    });

    test('la recherche rend une page de résultats', async ({ page }) => {
        // Par l'adresse plutôt que par le champ : le champ vit dans un widget, qui n'est pas
        // garanti sur toutes les dispositions — c'est une leçon déjà payée sur le formulaire de
        // connexion. La page de résultats, elle, est du produit.
        const reponse = await page.goto('fr/search?q=a', { waitUntil: 'domcontentloaded' });

        expect(reponse.status(), 'la recherche doit répondre').toBeLessThan(400);
        await expect(page.locator('body')).toBeVisible();
    });
});
