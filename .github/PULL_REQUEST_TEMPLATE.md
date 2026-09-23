<!-- Titre de la PR au format Conventional Commit, ex. : feat(forum): sondages dans les sujets -->

## Résumé

<!-- Une à trois puces : quoi, et surtout pourquoi. -->
-

## Type

- [ ] `fix` — correction d'un défaut
- [ ] `feat` — nouvelle fonctionnalité
- [ ] `docs` — documentation
- [ ] `refactor` / `perf` / `test` / `build` / `chore`

## Ce qui a été vérifié

- [ ] `vendor/bin/phpunit --fail-on-skipped` est vert **contre une base de test** (aucune suite sautée).
- [ ] `composer stan` est vert (aucune nouvelle erreur hors baseline).
- [ ] Les contrôles `tools/check-*.php` concernés passent (déclarations, couplages, contrats, JS, CSS, langues, liens).
- [ ] Le changement est **prouvé** : test PHP, épreuve navigateur (`tests/Browser`), ou rejeu HTTP décrit ci-dessous.
- [ ] Si une page est touchée : regardée dans un vrai navigateur, `check-js-console` sans erreur.

## Règles du projet

- [ ] Français dans les commentaires et le commit ; style du fichier voisin ; `strict_types` sur les fichiers neufs.
- [ ] Pas de jQuery, grille avec point de rupture, aucun script depuis un CDN.
- [ ] Addon : `core` / `presets` / `requires` déclarés ; couplage fatal déclaré ou annoté ; réglages de widget avec repli.
- [ ] Une nouvelle table passe par `install/install.sql` ; un changement de schéma livré par `install/migrations/*.up.sql`.
- [ ] Entrées validées, sorties échappées, jeton CSRF sur toute action mutante ; aucun secret committé.
- [ ] Documentation mise à jour si le comportement visible change (`CHANGELOG.md`, guides).

## Notes pour la revue

<!-- Points d'attention, décisions, captures si l'interface change. -->
