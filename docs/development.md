# Développement & déploiement — NeoFrag Reborn 1.0.0

Tout tourne dans Docker (pas de PHP requis sur l'hôte). Architecture : [architecture.md](architecture.md).

## 1. Démarrage Docker

4 services : **web** (Apache + PHP 8.3), **db** (MariaDB 11), **phpmyadmin**, **mailpit** (capture des mails).

```bash
docker compose up -d
# app        : http://localhost:8080
# phpMyAdmin : http://localhost:8081  (root / rootpass)
# Mailpit    : http://localhost:8025  (SMTP capturé sur :1025)
```

## 2. Bootstrap d'une base (clone frais)

**Base propre** (structure seule + migrations) :
```bash
docker compose up -d db
docker compose exec -T db mariadb -uroot -prootpass neofrag < schema.sql
docker compose run --rm web php tools/migrate.php up
```

> **Données de démo** : les anciens dumps démo (`backups/*.zip`) ont été supprimés (périmés, datant
> d'avant le retrait des données de démo). Un vrai jeu de démo avec **reset automatique** sera mis en
> place plus tard. En attendant, le dev se fait sur base propre (ci-dessus).

## 3. Runner de migrations — `tools/migrate.php`

Avant, les `migrations/*.up.sql` étaient appliqués à la main sans suivi (risque de drift). Le runner
trace l'état dans `nf_migrations`.

```bash
php tools/migrate.php status                 # appliqué / en attente
php tools/migrate.php up [--pretend]          # applique les migrations en attente (dry-run avec --pretend)
php tools/migrate.php down [--step=N]         # annule la/les dernière(s) migration(s)
php tools/migrate.php baseline --until=NAME   # marque comme appliquées sans exécuter (adopter une base existante)
```
- Connexion lue depuis `config/db.php`, surchargeable par `NF_DB_HOST/PORT/USER/PASS/NAME` (le host
  `db` du compose n'est résoluble que dans le réseau Docker).
- Ordre chronologique via le préfixe daté des fichiers.
- ⚠ `2026_05_04_user_messages_extension` n'a pas de `.down.sql` (non réversible, `--force` pour dé-tracer).
- ⚠ Le DDL MySQL auto-commit → faire un **backup avant `up` en prod**.

## 4. Tests — PHPUnit

```bash
docker compose run --rm web composer install        # installe phpunit (require-dev)
docker compose exec    web composer test             # tout (unit + intégration)
docker compose exec    web composer test:unit        # helpers purs uniquement
docker compose exec    web composer test:integration # logique DB (base réelle)
```
**90 tests / 366 assertions** (`composer test`) : `tests/Unit/` (~50 méthodes, fonctions pures des helpers —
testables sans bootstrapper `NeoFrag()`) + `tests/Integration/` (~17 méthodes, **base de données réelle** ;
data-providers inclus dans le total). Le socle
`IntegrationTestCase` ouvre une **transaction rollback par test** (isolation, aucune DDL) ; connexion via
`NF_TEST_DB_*` (défauts = stack Docker), suite *skipped* proprement si la base est injoignable. Il couvre
la logique money-critique (points/karma/VIP/boutique/pub/paiements + cascades FK). Tester les *objets* du
framework suppose toujours de découpler le service locator (voir roadmap).

## 5. CI — GitHub Actions

`.github/workflows/ci.yml` (à chaque push/PR) : **lint** `php -l` (hors vendor), **PHPUnit**, et un
**smoke** qui importe `schema.sql` dans un MariaDB, baseline puis applique les migrations via le runner.

## 6. Config & SCSS

- `config/` (gitignoré, secrets) : `db.php` (mysqli), `email.php` (SMTP/Mailpit), `neofrag.php`
  (flags debug/safe-mode), `crypt.php` (clé), `password.php` (salt). Sur un clone frais : renseigner + `composer install`.
- **SCSS** compilé serveur via scssphp : `docker compose run --rm web php index.php cli/tools/scss reload`
  (ou `watch`).

## 7. Override 3 niveaux (personnalisation sans fork)

Le framework résout vues/classes/fichiers dans cet ordre (premier trouvé gagne) :
1. `overrides/{type}/{fichier}` (global)
2. `themes/{thème actif}/overrides/{type}/{fichier}` (par thème)
3. `neofrag/{type}/{fichier}` (original)

→ on personnalise un module pour un thème, ou globalement, sans toucher au code livré.

## 8. Déploiement

- **Cible** : PHP ≥ 8.2 (ext mysqli, gd, intl, mbstring, zip, curl) + MariaDB 10.5+ / MySQL 5.7+.
- **Web** : le stack Docker tourne sous **Apache + mod_php** (port 8080). Un `nginx.conf` (php-fpm)
  est fourni comme alternative HORS Docker — à adapter avant usage réel.
- **Migrations en prod** : `php tools/migrate.php status` → `up --pretend` → backup → `up`.
- **Backups** : module `monitoring` (dump DB + zip du site). ⚠ L'**auto-update upstream est désactivé
  sur ce fork** (il écraserait le code forké).

## 9. Conventions

snake_case pour les fichiers, PascalCase pour les classes. `strict_types` **uniquement** avec des
types ajoutés + des tests qui couvrent le code (pas de passe aveugle → risque de TypeError sur les
chemins non exercés). Détails de style : voir le code existant + les règles globales du poste.
