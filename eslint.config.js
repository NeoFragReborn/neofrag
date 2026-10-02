'use strict';

/*
 * La configuration d'ESLint pour NeoFrag Reborn.
 *
 * CE QU'ELLE N'EST PAS. Ce n'est pas un formateur, et ce n'est pas un jugement de style. Le dépôt
 * porte 78 fichiers JavaScript écrits sur des années ; un linter réglé sur les conventions à la mode
 * en signalerait des milliers de lignes, et personne ne lirait plus rien. Les règles ci-dessous sont
 * choisies sur un seul critère : **avoir déjà mordu, ici**, ou pouvoir mordre en silence.
 *
 * ON NE LIT JAMAIS LES FICHIERS D'ORIGINE. Une partie du JavaScript du produit contient du PHP
 * interpolé — `url('admin/ajax/…')`, `$this->lang('…')` — qu'aucun analyseur JavaScript n'accepte.
 * `tools/check-js-lint.php` en fabrique des copies où chaque bloc PHP devient l'identifiant nu
 * `NF_PHP`, en gardant le compte des lignes pour que les numéros signalés restent justes, puis
 * lance ESLint sur ces copies. C'est cet outil qu'on appelle, jamais `npx eslint` directement.
 *
 * LE SITE SERVI NE DÉPEND D'AUCUN PAQUET NPM. Rien ici n'est déployé, rien n'est chargé par une
 * page : ceci est de l'outillage de développement, au même titre que PHPStan.
 */

const js      = require('@eslint/js');
const globals = require('globals');

module.exports = [
    {
        /*
         * Les bibliothèques d'autrui se gardent elles-mêmes. Les minifiées sortent par leur nom ;
         * celles livrées en clair doivent être nommées une par une, sinon on juge le code d'autrui
         * avec nos règles. Mesuré le 2026-09-21 : à elles seules, elles portaient une clé dupliquée,
         * un `typeof` invalide, trois affectations dans une condition et deux appels à jQuery —
         * autant de bruit sur lequel nous n'avons pas la main.
         */
        ignores: [
            // Les motifs commencent par `**/` : check-js-lint analyse des COPIES, sous un autre
            // préfixe, et un motif ancré à la racine n'y correspondrait pas.
            '**/node_modules/**', '**/vendor/**', '**/*.min.js',
            '**/js/tinymce/**', '**/js/codemirror/**', '**/js/altcha/**',
            '**/js/flatpickr/**',    // sélecteur de date
            '**/js/sortable.js',     // glisser-déposer
            '**/js/dropzone.js',     // téléversement (déjà listé par check-js-sources)
            '**/bot/dist/**',        // le bot compilé : TypeScript juge sa source (bot/src)
        ],
    },

    js.configs.recommended,

    {
        files: ['**/*.js'],

        languageOptions: {
            ecmaVersion: 2022,
            // `script`, et non `module` : ces fichiers sont servis tels quels par des balises
            // `<script>`, sans bundler. Les déclarer modules ferait taire `no-undef`, qui est
            // justement la règle qui compte ici.
            sourceType: 'script',
            globals: {
                ...globals.browser,

                // L'identifiant qui remplace un bloc PHP dans les copies analysées.
                NF_PHP: 'readonly',

                // Bibliothèques tierces chargées par une balise `<script>`, donc globales.
                // `$` et `jQuery` n'y sont PAS, et c'est délibéré : jQuery n'est plus chargé depuis
                // juin 2026, et trois fichiers l'appelaient encore trois mois plus tard en levant
                // « $ is not defined » avant d'attacher le moindre écouteur. `no-undef` les attrape.
                bootstrap:    'readonly',
                Chart:        'readonly',   // js/chart.umd.min.js
                CodeMirror:   'readonly',
                Dropzone:     'readonly',
                flatpickr:    'readonly',
                FullCalendar: 'readonly',   // modules/events/js/fullcalendar.min.js
                grecaptcha:   'readonly',
                L:            'readonly',   // Leaflet, carte des lieux
                mixitup:      'readonly',
                Sortable:     'readonly',
                tinymce:      'readonly',
                TomSelect:    'readonly',

                /*
                 * Les nôtres. Chacune est posée par un fichier du produit et consommée par un
                 * autre : c'est ainsi que ce JavaScript est écrit, sans modules ni bundler. Les
                 * déclarer ici est ce qui permet à `no-undef` de rester une règle utile — sans
                 * elles, quarante signalements légitimes noieraient les vrais.
                 */
                confirm_deletion: 'readonly',   // js/delete.js
                form:             'readonly',   // js/form.js
                modal:            'readonly',   // js/modal.js
                NeoFrag:          'readonly',
                NF:               'readonly',
                nfLeWizard:       'readonly',   // modules/live_editor/js/live-editor.js
                nfTreeview:       'readonly',   // js/treeview.js
                notify:           'readonly',   // js/notify.js
            },
        },

        rules: {
            /*
             * CE QUI FAIT ÉCHOUER. Chacune de ces règles décrit un défaut qui ne se voit pas à la
             * relecture et qui ne casse pas la page bruyamment : elle s'arrête, simplement.
             */

            // La règle qui compte. « $ is not defined » a tué le tri des tables de toute
            // l'administration pendant trois mois, sans une ligne dans aucun journal.
            'no-undef': 'error',

            // Du code qu'on croit exécuté et qui ne l'est pas.
            'no-unreachable': 'error',

            /*
             * Un même nom déclaré deux fois dans la même portée : la seconde écrase la première
             * sans un mot. `builtinGlobals: false` parce que ce JavaScript n'a pas de modules —
             * `js/form.js` DÉFINIT la globale `form` que les autres consomment, et la déclarer
             * plus haut la faisait passer pour une redéclaration. C'est la façon dont ce code est
             * écrit, pas un défaut.
             */
            'no-redeclare': ['error', { builtinGlobals: false }],

            // `eval` et ses déguisements : une chaîne qui devient du code, c'est une injection qui
            // attend son tour.
            'no-eval':        'error',
            'no-implied-eval':'error',
            'no-new-func':    'error',
            'no-script-url':  'error',

            /*
             * `innerHTML = <autre chose qu'un texte littéral>` : c'est par là que passe une
             * injection de balises. Un littéral est sûr par construction ; tout le reste demande
             * qu'on ait regardé. La règle n'interdit pas, elle oblige à écrire l'exception à la
             * main, ce qui laisse une trace lisible pour la relecture suivante.
             */
            'no-restricted-syntax': ['warn', {
                selector: 'AssignmentExpression[left.property.name="innerHTML"][right.type!="Literal"][right.type!="TemplateLiteral"]',
                message:  'innerHTML reçoit une valeur calculée : assainir, ou employer textContent. Écrire l\'exception avec eslint-disable-next-line et sa raison.',
            }],

            /*
             * CE QUI SIGNALE SANS FAIRE ÉCHOUER. Ces deux-là décrivent du désordre, pas un défaut.
             * Les passer en erreur demanderait de réécrire des dizaines de fichiers d'un coup, ce
             * qui n'est pas le but d'un premier passage. `check-js-lint` en tient le compte et
             * refuse qu'il MONTE : c'est le même cliquet que `check-strict-types`.
             */
            'no-unused-vars': ['warn', { args: 'none', caughtErrors: 'none' }],
            'no-console':     ['warn', { allow: ['warn', 'error'] }],

            // Un `catch {}` vide est souvent délibéré — « si ça échoue, tant pis ». Les autres
            // blocs vides sont du désordre, et le cliquet les empêche de se multiplier.
            'no-empty': ['warn', { allowEmptyCatch: true }],

            /*
             * CE QU'ON N'ACTIVE PAS, ET POURQUOI. `eqeqeq`, `curly`, `prefer-const` et la famille
             * du style décrivent des préférences, pas des défauts : sur ce dépôt elles produisent
             * des milliers de signalements qui noieraient les règles ci-dessus. Le jour où une de
             * ces préférences cause un vrai bug ici, elle rejoindra la liste avec son motif.
             */
        },
    },

    {
        /*
         * L'OUTILLAGE, qui tourne sous Node et non dans un navigateur : la configuration de
         * Playwright, les parcours de `tests/E2E/` et le pilote de `check-mise-en-page` (sa sonde,
         * elle, s'exécute dans la page, avec les globales du navigateur). Ils emploient `require`, `module` et
         * `process`, qui n'existent pas dans une page — sans ce bloc, `no-undef` les signalerait
         * tous, et l'on serait tenté de désactiver la règle qui compte le plus.
         */
        files: ['playwright.config.js', 'tests/E2E/**/*.js', 'tests/MiseEnPage/pilote.js', '**/cache/js-lint/playwright.config.js', '**/cache/js-lint/tests/E2E/**/*.js', '**/cache/js-lint/tests/MiseEnPage/pilote.js'],

        languageOptions: {
            sourceType: 'commonjs',
            globals: { ...globals.node },
        },
    },
];
