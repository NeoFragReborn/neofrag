'use strict';

/*
 * Playwright, pour les parcours de bout en bout (`tests/E2E/`).
 *
 * POURQUOI PLAYWRIGHT ET PAS LE HARNAIS EXISTANT. `tests/Browser/` éprouve des pages ISOLÉES : on y
 * charge un script et on vérifie ce qu'il fait. `nf_chrome_dom()` charge une page et rend son DOM.
 * Ni l'un ni l'autre ne sait CLIQUER, remplir un champ, ni suivre un visiteur d'un écran au suivant.
 * Or c'est précisément ce qui manquait : les contrôles HTTP existants rejouent des requêtes, pas des
 * gestes, et ce qui casse dans l'interface casse entre deux requêtes.
 *
 * AUCUN MOTEUR N'EST TÉLÉCHARGÉ. `channel: 'chrome'` emploie le Google Chrome déjà installé — celui
 * dont `check-js-console`, `check-contraste` et `check-responsive` se servent depuis des mois. Le
 * paquet npm ne pèse alors que sa propre taille, et l'installation reste reproductible.
 *
 * NE PAS L'APPELER DIRECTEMENT : `php tools/check-parcours.php --base=…` s'en charge, rend un verdict
 * au format du projet et refuse de conclure quand il n'a rien pu mesurer.
 */

/*
 * LA BARRE FINALE N'EST PAS UN DÉTAIL. Playwright résout les adresses avec `new URL(chemin, base)` :
 * un chemin qui commence par `/` REMPLACE tout le chemin de la base. Avec la base
 * `https://site.tld/demo`, `page.goto('/fr/news')` part donc sur `https://site.tld/fr/news` — un
 * AUTRE site. Mesuré le 2026-09-22 : les parcours visaient la production, qui ne porte aucun
 * contenu, et concluaient « aucune actualité » sur une démonstration qui en a six.
 *
 * D'où les deux règles tenues ici : la base finit toujours par `/`, et les parcours n'emploient
 * QUE des chemins relatifs. Le premier parcours vérifie en plus où il a réellement atterri.
 */
const base = (process.env.NF_BASE || '').replace(/\/+$/, '') + '/';

module.exports = {
    testDir: './tests/E2E',

    // Un parcours qui dépasse la minute n'est pas lent : il est bloqué. Le dire vite.
    timeout: 60000,
    expect: { timeout: 10000 },

    // Jamais de reprise silencieuse : un parcours qui ne passe qu'une fois sur deux est un défaut,
    // et le masquer derrière une seconde tentative est la meilleure façon de ne jamais le corriger.
    retries: 0,
    workers: 2,

    reporter: [['json', { outputFile: 'cache/e2e/rapport.json' }], ['list']],

    use: {
        baseURL: base,
        channel: 'chrome',
        headless: true,
        ignoreHTTPSErrors: false,
        // Le même agent que les autres contrôles du projet : un site peut traiter autrement un
        // navigateur qu'il ne reconnaît pas, et l'on veut mesurer ce que voit un vrai visiteur.
        userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36 NeoFragParcours',
        actionTimeout: 15000,
        navigationTimeout: 30000,
        screenshot: 'only-on-failure',
        video: 'off',
        trace: 'off',
    },

    outputDir: 'cache/e2e/artefacts',
};
