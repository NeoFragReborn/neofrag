'use strict';

/*
 * Le visiteur change de THÈME, et son choix reste sur ce site.
 *
 * Signalé le 2026-09-23 : un visiteur qui choisissait Forge sur la démonstration voyait
 * aussi le site vitrine en Forge. Le cookie du choix valait pour tout le domaine, et la démonstration
 * est servie depuis un sous-dossier du site vitrine. Il porte désormais le chemin du site et un nom
 * qui en dérive (cf. neofrag/helpers/theme.php).
 *
 * Ce parcours clique dans le menu du pied de page et vérifie trois choses : le thème change, le
 * cookie posé est celui annoncé par le menu, et il ne vaut que pour le chemin du site. Il remet le
 * thème par défaut en partant, en effaçant ses cookies. Sans menu (choix fermé par l'administrateur,
 * ou un seul thème installé), il n'y a rien à mesurer : le parcours le dit et s'arrête.
 */

const { test, expect } = require('@playwright/test');

test.describe('Le visiteur change de thème', () => {

    test('le choix s\'applique, et son cookie ne vaut que pour ce site', async ({ page, context }) => {
        await page.goto('fr', { waitUntil: 'load' });

        const menu = page.locator('.nf-theme-switch[data-nf-cookie]').first();
        test.skip(await menu.count() === 0, 'aucun menu de thème : choix fermé, ou un seul thème installé');

        const nom    = await menu.getAttribute('data-nf-cookie');
        const chemin = await menu.getAttribute('data-nf-chemin');
        const avant  = (await menu.locator('.dropdown-item.active').getAttribute('data-theme-pick')) || '';

        await menu.locator('.dropdown-toggle').click();

        const autre = menu.locator('.dropdown-item:not(.active)').first();
        const cible = await autre.getAttribute('data-theme-pick');
        expect(cible, 'le menu doit proposer un autre thème que celui affiché').toBeTruthy();

        await Promise.all([
            page.waitForEvent('load'),
            autre.click(),
        ]);

        // 1. Le thème a changé : sa feuille est servie, et le menu l'annonce.
        await expect(page.locator(`link[href*="themes/${cible}/css/"]`).first()).toBeAttached();
        await expect(page.locator('.nf-theme-switch .dropdown-item.active').first()).toHaveAttribute('data-theme-pick', cible);

        // 2. Le cookie est celui du site, borné à son chemin — pas au domaine entier.
        const cookies = await context.cookies();
        const pose    = cookies.find((c) => c.name === nom);
        expect(pose, `le cookie « ${nom} » annoncé par le menu doit être posé`).toBeTruthy();
        expect(pose.value).toBe(cible);
        expect(pose.path, 'le cookie doit valoir pour le chemin du site seulement').toBe(chemin);

        // Remise en état : le visiteur suivant, et le parcours suivant, retrouvent le thème par défaut.
        await context.clearCookies();
        await page.goto('fr', { waitUntil: 'load' });
        await expect(page.locator(`link[href*="themes/${avant}/css/"]`).first()).toBeAttached();
    });
});
