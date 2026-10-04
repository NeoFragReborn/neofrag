# Contribuer à NeoFrag Reborn

Merci de ton intérêt ! NeoFrag Reborn est la continuité communautaire de [NeoFrag](https://neofr.ag)
(base Alpha 0.2.4), sous licence **LGPL-3.0-or-later**. Le projet est écrit et documenté **en français**.

## Démarrer

Il faut PHP 8.2 à 8.5, avec les extensions que liste le [guide d'installation](../docs/guide/installation.md#prérequis),
Composer, Node.js 22 ou plus (pour ESLint), et MySQL ou MariaDB avec un compte qui peut créer des bases
(celle du site, puis celle des tests). Le mot de passe de l'administrateur du site se lit dans une
variable d'environnement, pour ne pas rester dans l'historique du terminal : `export NF_ADMIN_PASS=…`
d'abord.

<!-- nouveau-venu : ces deux blocs sont joués tels quels, sur une machine vierge, par tools/check-nouveau-venu.php -->
```bash
git clone https://github.com/NeoFragReborn/neofrag.git
git clone https://github.com/NeoFragReborn/extensions.git
cd neofrag
composer install
npm ci                                             # ESLint, pour le contrôle du JavaScript
php tools/assembler.php --extensions=../extensions   # les addons à la carte : le produit entier
php install/cli.php --db-name=neofrag --create-db --db-user=… --db-pass=… --admin-user=admin \
    --admin-email=admin@exemple.test --admin-pass-env=NF_ADMIN_PASS --site-url=http://localhost:8080 --yes
php -S 127.0.0.1:8080 tools/router-builtin.php     # ou Apache / nginx / Caddy
```

Une pile Docker (`docker-compose.yml` : Apache + PHP, MariaDB, phpMyAdmin, Mailpit) est fournie pour
qui peut l'employer ; `php tools/ci-install.php` fait une installation complète non interactive, et
`php tools/seed-demo.php` remplit un jeu de démonstration. Détails : [docs/development.md](../docs/development.md).

## Avant d'ouvrir une PR — le filet

Fais tourner ce que la CI rejouera (huit jobs, PHP 8.2 à 8.5) :

<!-- nouveau-venu -->
```bash
php tools/prepare-test-db.php           # une fois : la base des tests (neofrag_test) et config/db-test.php
vendor/bin/phpunit --fail-on-skipped    # SANS base de test, les suites integration et headless se
                                        # sautent : --fail-on-skipped transforme ce silence en échec.
composer stan                           # PHPStan — attrape les fatales au chargement ; baseline dans phpstan-baseline.neon
composer stan:baseline                  # régénère la baseline (jamais à la main), puis on la compare à l'ancienne
php tools/check-all.php                 # composer audit + tous les contrôles statiques
php tools/seed-demo.php --sans-config-demo   # une fois : du contenu, que les épreuves en navigateur mesurent
php tools/check-all.php --navigateur    # + ceux qui servent le site dans un vrai navigateur (Chromium requis)
```

Chaque contrôle dit **pourquoi il existe** en tête de son fichier, et chacun a été éprouvé en cassant
volontairement ce qu'il garde. Si tu ajoutes un contrôle, fais-le échouer exprès avant de le brancher,
et suis les conventions de [tools/README.md](../tools/README.md) — `check-tools` les vérifie. Un
contrôle qui passe doit avoir eu quelque chose à mesurer.

## Conventions

- **Français** pour les commentaires, les messages de commit, la documentation et les interfaces (les
  identifiants du code hérité sont en anglais : suis le fichier voisin).
- **Commits** : [Conventional Commits](https://www.conventionalcommits.org/) — `feat(scope): …`,
  `fix(scope): …`, `docs:`, `refactor:`, `test:`, `build:`, `chore:`. Le corps dit **pourquoi**, avec ce
  qui a été vérifié.
- **Branches** : `feat/<sujet>`, `fix/<sujet>`, `docs/<sujet>`. Une PR = un sujet.
- **PHP** : style du fichier voisin (tabulations dans le cœur et les modules, quatre espaces dans
  `tools/`, cf. `.editorconfig` ; accolades sur leur ligne). `declare(strict_types=1)` dans tout fichier
  neuf — le cliquet `check-strict-types` ne doit jamais baisser.
- **Formulaires** : deux générations coexistent et la v1 est **gelée**. Les écrans d'administration
  emploient `form()` (`add_rules` / `is_valid`), les formulaires publics et de confirmation `form2()`.
  N'ajoute pas une troisième façon de faire.
- **Front** : Bootstrap 5, **jamais jQuery** (`check-js-sources` refuse), grille avec point de rupture
  (`col-12 col-lg-6`), primitives `NF.*` pour l'AJAX et l'insertion de HTML, adresses en `data-*` plutôt
  qu'en PHP interpolé dans les `.js`. Chaque script inline reçoit son nonce CSP automatiquement ; aucun
  script depuis un CDN.
- **Addons** : chaque module, widget ou thème déclare `core`, `presets`, `requires` ; un couplage fatal
  (table ou classe d'un autre addon) se déclare ou s'annote (`couplage(<addon>): raison`) ; les widgets
  complètent leurs réglages absents ; les thèmes déclarent `regions` et tout le vocabulaire `--nf-*`.
- **Sécurité** : requêtes via le constructeur de requêtes (jamais de SQL concaténé), sortie HTML échappée
  (`htmlspecialchars` / `sanitize_html`), entrées validées aux frontières, jeton CSRF sur toute action
  mutante (`csrf_url` / `check_csrf`).
- **Contenu de la base** : ne passe jamais un titre écrit par l'administrateur dans `lang()` —
  `no_translate()`.

## Étendre le CMS

Modules, widgets et thèmes sont des **addons**, un dossier chacun. Guides dédiés, vérifiés contre le code :
[Créer un module](../docs/guide/create-a-module.md) · [un widget](../docs/guide/create-a-widget.md) ·
[un thème](../docs/guide/create-a-theme.md) · [Le framework](../docs/guide/framework.md).

## Processus de PR

1. Forke et branche depuis `main`.
2. Code, écris le test ou l'épreuve qui prouve le changement, garde le filet vert.
3. Ouvre la PR (titre au format Conventional Commit, coche la liste du gabarit).
4. La CI doit passer. Une revue peut demander des ajustements.

## Versions et publication

Ce dépôt reçoit **une version à la fois** : chaque publication y arrive en un commit, avec son étiquette
`vX.Y.Z`, sa page de release et ses paquets (installation, démonstration, mise à jour). Le travail
entre deux versions se fait ailleurs. Une PR acceptée est donc **reprise dans la version suivante** —
elle n'est pas fusionnée telle quelle sur `main` —, et son auteur est crédité dans le `CHANGELOG.md`.

Les numéros suivent le [versionnage sémantique](https://semver.org/lang/fr/) ; la source de vérité est
`NEOFRAG_VERSION` dans `index.php`. Les addons à la carte vivent dans
[NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions), le bot Discord dans
[NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord), chacun avec ses propres
versions.

## Signaler un bug ou proposer une fonctionnalité

Utilise les **gabarits d'issue**. Pour une faille de sécurité : **n'ouvre pas d'issue publique**, voir
la [politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).
