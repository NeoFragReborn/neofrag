'use strict';

/*
 * Le parcours du MEMBRE : se connecter, puis atteindre l'administration.
 *
 * La connexion passe par une MODALE ouverte en JavaScript — `/user/login` répond 302, et c'est
 * délibéré. Aucun contrôle HTTP ne peut donc reproduire ce geste : il faut un navigateur qui clique,
 * attende la modale, remplisse et envoie. C'est précisément le trou que ces parcours comblent.
 *
 * CE PARCOURS N'ÉCRIT RIEN. Les écritures réelles — livre d'or, administration, et surtout ce qui
 * doit être REFUSÉ en démonstration — sont couvertes par `check-demo-ecriture`, qui les rejoue en
 * HTTP et défait tout ce qu'il a écrit. Un parcours de navigateur ne saurait pas défaire aussi bien ;
 * il reste donc sur ce qu'il est seul à pouvoir faire.
 */

const { test, expect } = require('@playwright/test');

const COMPTE = process.env.NF_PARCOURS_COMPTE || 'demo';
const SECRET = process.env.NF_PARCOURS_MOTDEPASSE || 'demo';

test.describe('Le membre se connecte', () => {

    test('la modale de connexion s\'ouvre, accepte le compte, et la session tient', async ({ page }) => {
        await page.goto('fr', { waitUntil: 'domcontentloaded' });

        const declencheur = page.locator('a[href*="/user/login"]').first();
        expect(await declencheur.count(), 'la page doit proposer un accès à la connexion').toBeGreaterThan(0);

        await declencheur.click();

        // La modale arrive en AJAX : on attend le CHAMP, pas un délai fixe.
        const identifiant = page.locator('input[name="login"]');
        await expect(identifiant.first(), 'la modale de connexion doit apparaître').toBeVisible({ timeout: 15000 });

        await identifiant.first().fill(COMPTE);
        await page.locator('input[name="password"]').first().fill(SECRET);

        await Promise.all([
            page.waitForLoadState('domcontentloaded'),
            page.locator('input[name="password"]').first().press('Enter'),
        ]);

        // La session tient-elle vraiment ? On le demande à une page qui exige d'être connecté,
        // plutôt que de se fier à ce que la page d'accueil affiche.
        const compte = await page.goto('fr/user/account', { waitUntil: 'domcontentloaded' });

        expect(compte.status(), 'l\'espace membre doit répondre à un membre connecté').toBeLessThan(400);
        expect(page.url(), 'un visiteur non connecté serait renvoyé ailleurs').toContain('/user/account');
    });

    test('l\'administration s\'ouvre et porte son menu', async ({ page }) => {
        await page.goto('fr', { waitUntil: 'domcontentloaded' });

        await page.locator('a[href*="/user/login"]').first().click();
        await expect(page.locator('input[name="login"]').first()).toBeVisible({ timeout: 15000 });
        await page.locator('input[name="login"]').first().fill(COMPTE);
        await page.locator('input[name="password"]').first().fill(SECRET);

        await Promise.all([
            page.waitForLoadState('domcontentloaded'),
            page.locator('input[name="password"]').first().press('Enter'),
        ]);

        const admin = await page.goto('fr/admin', { waitUntil: 'domcontentloaded' });

        expect(admin.status(), 'le tableau de bord doit répondre').toBeLessThan(400);
        expect(page.url(), 'un compte sans droits serait renvoyé ailleurs').toContain('/admin');

        // Un tableau de bord sans navigation est une impasse : c'est le menu qui en fait un outil.
        const entrees = await page.locator('a[href*="/admin/"]').count();
        expect(entrees, 'l\'administration doit proposer son menu').toBeGreaterThan(3);
    });
});
