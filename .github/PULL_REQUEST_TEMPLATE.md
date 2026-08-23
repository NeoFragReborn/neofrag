<!-- Titre de la PR = format Conventional Commit, ex: feat(forum): support des sondages -->

## Résumé

<!-- 1 à 3 puces : quoi et pourquoi. -->
-

## Type

- [ ] `fix` — correction de bug
- [ ] `feat` — nouvelle fonctionnalité
- [ ] `docs` — documentation
- [ ] `refactor` / `perf` / `test` / `build` / `chore`

## Checklist

- [ ] Le code suit le style du fichier voisin (anglais, tabulations).
- [ ] `composer test` est vert (PHPUnit).
- [ ] `composer stan` est vert (PHPStan — pas de nouvelle erreur hors baseline).
- [ ] Si flux runtime touché : `composer smoke` est vert (ou testé manuellement).
- [ ] Pas de secret committé ; entrées validées, sorties échappées.
- [ ] Une nouvelle table de module passe par `install/install.sql` ; un changement de schéma entre versions passe par `install/migrations/*.up.sql`.
- [ ] Doc mise à jour si le comportement public change.

## Notes pour la revue

<!-- Points d'attention, décisions, captures si UI. -->
