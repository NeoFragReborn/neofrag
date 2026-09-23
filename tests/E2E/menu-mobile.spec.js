'use strict';

/*
 * Le menu du site sur un TÉLÉPHONE.
 *
 * Signalé le 2026-09-23 : sur le thème Nebula, le menu disparaissait sous 860 px sans
 * aucun bouton pour le rouvrir ; sur les autres thèmes, les entrées passaient à la ligne jusqu'à
 * occuper l'écran. Le correctif donne un burger à Nebula et un bouton « Menu » au widget de
 * navigation, qui replie le menu dès qu'il ne tient plus sur une ligne.
 *
 * Aucune requête ne dit si un menu est ATTEIGNABLE : le lien est dans la page, qu'on puisse le
 * toucher ou non. Il faut une fenêtre étroite, un clic, et un lien qui devient visible.
 */

const { test, expect } = require('@playwright/test');

test.use({ viewport: { width: 390, height: 844 } });

test.describe('Le visiteur ouvre le menu sur un téléphone', () => {

    test('le menu est atteignable, et un de ses liens mène à sa page', async ({ page }) => {
        await page.goto('fr', { waitUntil: 'load' });

        // Le burger d'un thème (Nebula) ou le bouton du widget de navigation, quand le menu ne tient
        // pas sur une ligne. Un menu COURT n'a pas de bouton : il reste déplié, et c'est correct.
        const bouton = page.locator('#nb-burger:visible, .nf-nav-repliable.nf-nav-replie > .nf-nav-toggle:visible').first();
        let menu;

        if (await bouton.count() > 0) {
            await expect(bouton).toHaveAttribute('aria-expanded', 'false');

            const cible = await bouton.getAttribute('aria-controls');
            expect(cible, 'le bouton doit désigner le menu qu\'il ouvre (aria-controls)').toBeTruthy();

            menu = page.locator('#' + cible);

            // Fermé, le menu ne doit laisser voir AUCUN lien : c'est ce qui libère l'écran.
            expect(await menu.locator('a[href]:visible').count(), 'replié, le menu ne doit rien montrer').toBe(0);

            await bouton.click();
            await expect(bouton).toHaveAttribute('aria-expanded', 'true');
        } else {
            menu = page.locator('.nf-nav-repliable > .nav').first();
            expect(await menu.count(), 'la page doit porter un menu, replié ou non').toBeGreaterThan(0);
        }

        const lien = menu.locator('a[href]:not([href="#"]):not([data-nf-sous-menu]):visible').first();
        await expect(lien, 'un lien du menu doit être visible').toBeVisible();

        // Visible ne suffit pas : le lien doit être DANS l'écran, pas débordé sur la droite.
        const boite = await lien.boundingBox();
        expect(boite, 'le lien doit occuper une place à l\'écran').not.toBeNull();
        expect(boite.x, 'le lien ne doit pas sortir de l\'écran à gauche').toBeGreaterThanOrEqual(0);
        expect(boite.x + boite.width, 'le lien ne doit pas sortir de l\'écran à droite').toBeLessThanOrEqual(391);

        // Et il mène à sa page. Le chemin se compare sans la langue ni la base : la démonstration
        // est servie depuis un sous-dossier.
        const cibleLien = new URL(await lien.getAttribute('href'), page.url()).pathname.replace(/\/+$/, '');

        // `toHaveURL` attend la navigation : pas de `waitForLoadState`, qui répondrait tout de suite
        // pour la page de départ, déjà chargée.
        await lien.click();
        await expect(page).toHaveURL(new RegExp(cibleLien.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '/?$'));
    });
});
