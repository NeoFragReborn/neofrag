/**
 * Le parcours de la MODALE : elle doit se fermer par son bouton, pas seulement par sa croix.
 *
 * POURQUOI CE PARCOURS EXISTE. Bootstrap 5 a renommé ses attributs de comportement en `data-bs-*`.
 * Le pied de chaque modale du produit est fabriqué par `Modal::dismiss()`, qui posait encore
 * `data-dismiss` — la forme de Bootstrap 4. Un attribut inconnu n'est pas une erreur pour un
 * navigateur : il est ignoré EN SILENCE. Le bouton « Fermer » s'affichait donc normalement, au bon
 * endroit, avec le bon libellé, et ne faisait rien ; la croix de l'en-tête, écrite en dur et
 * correctement, fermait bien. Signalé à l'œil le 2026-09-22.
 *
 * Rien d'autre ne pouvait le voir : la page est valide, aucun script ne plante, aucune adresse ne
 * répond mal. `check-classes-bs4` refuse désormais l'attribut à la lecture du code ; ce parcours
 * vérifie le GESTE, qui est la seule preuve que le bouton ferme vraiment.
 *
 * CE PARCOURS N'ÉCRIT RIEN : « Scanner le disque » est une lecture, et la modale est refermée.
 */

const { test, expect } = require('@playwright/test');

const COMPTE = process.env.NF_PARCOURS_COMPTE || 'demo';
const SECRET = process.env.NF_PARCOURS_MOTDEPASSE || 'demo';

test.describe('Une modale se ferme', () => {

    test('le bouton « Fermer » du pied referme la modale, comme la croix', async ({ page }) => {
        await page.goto('fr', { waitUntil: 'domcontentloaded' });

        await page.locator('a[href*="/user/login"]').first().click();
        await expect(page.locator('input[name="login"]').first()).toBeVisible({ timeout: 15000 });
        await page.locator('input[name="login"]').first().fill(COMPTE);
        await page.locator('input[name="password"]').first().fill(SECRET);

        await Promise.all([
            page.waitForLoadState('domcontentloaded'),
            page.locator('input[name="password"]').first().press('Enter'),
        ]);

        const addons = await page.goto('fr/admin/addons', { waitUntil: 'domcontentloaded' });
        expect(addons.status(), 'la page des addons doit répondre à un administrateur').toBeLessThan(400);

        // « Scanner le disque » ouvre une modale en AJAX. Son pied porte « Fermer » quand le disque
        // n'a rien de neuf, et « Annuler » PUIS « Installer la sélection » quand il trouve un addon
        // à installer — `Modal::dismiss()` place le bouton de fermeture en premier.
        const declencheur = page.locator('a[data-modal-ajax*="addons/scan"], a[data-modal-ajax*="scan"]').first();
        expect(await declencheur.count(), 'la page doit proposer le scan du disque').toBeGreaterThan(0);

        await declencheur.click();

        const modale = page.locator('.modal.show').first();
        await expect(modale, 'la modale doit s\'ouvrir').toBeVisible({ timeout: 15000 });

        // LE GESTE : le bouton du PIED, pas la croix de l'en-tête. On le désigne par sa CLASSE et non
        // par son attribut : viser `[data-bs-dismiss]` ferait échouer le parcours sur « bouton
        // introuvable » dès que l'attribut manque, alors que le défaut à nommer est qu'il NE FERME
        // RIEN. `.btn` tient dans les deux états, et c'est bien le verdict de fermeture qui décide.
        //
        // Et jamais le bouton qui ENVOIE : la première version prenait le dernier bouton du pied,
        // qui est « Installer la sélection » dès que le disque porte un addon non installé — le
        // thème `vitrine` sur la démonstration, le 2026-09-22. Le parcours échouait alors sur un
        // produit sain, et cliquait un bouton qui écrit.
        const fermer = modale.locator('.modal-footer .btn:not([type="submit"])').first();
        expect(await fermer.count(), 'la modale doit porter un bouton dans son pied').toBeGreaterThan(0);

        await fermer.click();

        await expect(modale, 'la modale doit être refermée par son bouton').toBeHidden({ timeout: 10000 });
    });
});
