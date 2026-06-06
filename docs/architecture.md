# Architecture — NeoFrag Reborn 1.0.0

Document technique de référence (vérifié contre le code 2026-06-02). Pour l'état du projet et le
reste à faire, voir [historique.md](historique.md) ; pour l'inventaire des composants,
[components.md](components.md).

## 1. Identité & pile

CMS PHP **monolithique modulaire** pour communautés gaming/esport, fork local de NeoFrag.
Version **1.0.0** (`NEOFRAG_VERSION`, index.php).

- **PHP ≥ 8.2** (image Docker `php:8.3-apache`)
- **MariaDB 11**, driver **mysqli** (seul driver implémenté : `neofrag/drivers/mysqli.php`)
- **Composer** (deps dans `vendor/` ; plus de dossier `lib/`)
- **Bootstrap 4.6.2** + **jQuery 3.7.1**, **SCSS** compilé serveur (scssphp)
- **52 modules · 39 widgets · 10 addons · 6 thèmes** (admin + vitrine/nebula/blockcraft/granite/forge)

## 2. Cycle de requête

```
index.php
 ├─ define NEOFRAG_VERSION 1.0.0 ; require vendor/autoload.php ; config/neofrag.php
 ├─ spl_autoload_register pour le préfixe NF\ (NF\Foo\Bar → foo/bar.php, minuscule)
 ├─ NeoFrag('NF\NeoFrag\NeoFrag')  → instancie le SINGLETON global (fonction globale NeoFrag())
 ├─ enregistre le callback __path() (mécanisme d'override 3 niveaux)
 ├─ charge les 10 services core : input, debug, url, db, access, config, output, session, groups, events
 └─ NeoFrag()->output()  → dispatch
      segments URL → module → contrôleur (variante) → méthode → vue *.tpl.php
```

Le dispatch (`neofrag/addons/module.php::get_method`) préfixe l'URL selon `admin`/`ajax`, filtre le
tableau `routes` du manifeste, puis matche par regex les placeholders `{id}/{url_title}/{page}/{pages}`
(définis dans `neofrag/neofrag.php:13-20`).

## 3. Le pattern réel : service locator implicite + méthodes magiques

Tout repose sur la fonction globale **`NeoFrag()`** (index.php:54) qui retourne un singleton. La classe
de base `NF\NeoFrag\NeoFrag` (`neofrag/neofrag.php`) donne à toute classe un accès magique aux services
et libraries :

- **`__get($name)`** (neofrag/neofrag.php:78) : lazy-load des services `core_*` et des libraries, avec
  injection de la config `config/{name}.php`.
- **`__call($name, $args)`** : route dynamiquement vers displayables / addons / loadables / libraries.
- **`__path()` / `___load()`** : résolution de fichiers avec l'override 3 niveaux.

Détail PHP 8.2 : l'attribut `#[\AllowDynamicProperties]` (neofrag/neofrag.php:9) ré-autorise
explicitement les propriétés dynamiques (dépréciées en 8.2).

**Conséquence (dette) :** pas de DI container ; la résolution dynamique casse l'autocomplétion IDE,
rend l'analyse statique et le mocking difficiles → **c'est ce qui bloque les tests et une API REST
native** (voir roadmap).

## 4. Loadables & Displayables

- **Loadables** (`neofrag/loadables/`) : `Model` (legacy), `Model2` (ORM), `Controller`, `Install`,
  et les addons spécialisés (`Module`, `Widget`, `Theme`, `Language`, `Authenticator`). Chargés
  paresseusement via `__load()`.
- **Displayables** (`neofrag/displayables/`) : composants de layout **Zone → Row → Col → Widget**,
  sérialisés par thème/page dans `nf_dispositions`, éditables via le live editor.

## 5. ORM Model2

Chaque modèle déclare un `__schema()` retournant des `Field` chaînables (`neofrag/field.php` +
`neofrag/fields/` : bool, date, datetime, depends, enum, file, float, i18n, index, int, primary,
serialized, smallint, text, unique) :

```php
'id'       => static::field()->primary()->int(),
'forum_id' => static::field()->depends('forum'),     // FK
'content'  => static::field()->i18n('multiline')->text(),
```

Les FK déclarées avec **`depends()`** sont résolues **paresseusement** en objets `Model2` au premier
accès (suffixe de colonne `_id`). Cache d'instances par `table + clé primaire`. Le Model2 **ne crée
pas les tables** (aucun DDL dans l'ORM ; le schéma vient de `schema.sql` + migrations).

## 6. Libraries & Helpers

- **36 libraries** (`neofrag/libraries/`) : `form`, `form2`, `table`, `table2`, `panel`, `pagination`,
  `breadcrumb`, `modal`, `tab`, `html`, `js`, `css`, `view`, `date`, `lang`, `crypt`, `password`,
  `email`, `network`, `audit_log`, `rate_limit`, `anti_flood`, `captcha`, `totp_service`,
  `social_connect_session`, `moderation`, `mysqldump`… Accédées magiquement
  (`$this->form2`, `$this->rate_limit`, etc.).
- **18 helpers** (`neofrag/helpers/`, fonctions globales pures) : `array`, `string`, `color`,
  `countries`, `time`, `file`, `dir`, `input`, `location`, `markdown`, `notify`, `sanitize`,
  `statistics`, `system`, `user_agent`, `assets`, `geolocalisation`, `debug`. Ce sont les **seuls
  éléments testables sans bootstrapper le singleton** (cf. tests pilotes). `sanitize` expose
  `sanitize_html()` (HTMLPurifier, anti XSS stocké).

## 7. Override 3 niveaux

`NeoFrag::__path()` cherche dans cet ordre (premier trouvé gagne) :
1. `overrides/{type}/{fichier}` (surcharge globale)
2. `themes/{thème actif}/overrides/{type}/{fichier}` (surcharge par thème)
3. `neofrag/{type}/{fichier}` (original)

→ personnalisation sans toucher au code livré (sauf `NEOFRAG_SAFE_MODE`).

## 8. Système d'événements (v0.4)

`neofrag/core/events.php` : pub/sub persistant pour la requête (`->on(event, cb)` / `->fire(event,
...args)`). Permet le découplage cross-module — ex. `talks` s'abonne à `news.published` /
`forum.topic.created` pour auto-poster un flux, `access` logge chaque `permissions.*` pour l'audit.

## 9. Données

- **Schéma** : `schema.sql` (structure de référence, **126 tables**, régénéré depuis la base vive via `tools/dump-schema.php`) ; les
  migrations `migrations/*.up.sql`/`.down.sql` s'appliquent par-dessus via le runner `tools/migrate.php`
  (suivi dans **`nf_migrations`**).
- **Domaines** : comptes & sessions (`nf_user`, `nf_session`, `nf_user_profile`), permissions/rôles
  (`nf_roles`, `nf_role_permissions`, `nf_users_roles`, `nf_groups_roles`, `nf_groups`), config
  (`nf_settings`), addons (`nf_addon`), layout (`nf_dispositions`, `nf_widgets`), contenu
  (`nf_news`, `nf_articles`, `nf_forum*`, `nf_comment`…), gaming (`nf_teams*`, `nf_games*`,
  `nf_events*`, `nf_recruits*`), modération (`nf_reports`, `nf_sanctions`, `nf_ip_banlist`), logs
  (`nf_log_db`, `nf_audit_log`).
- **i18n** : double stratégie — table centrale `nf_i18n` (utilisée par le field-type `i18n`) +
  tables legacy `*_lang` par entité. Routing préfixé `/{lang}/...`.

## 10. Dette technique notable

- **Service locator + méthodes magiques** partout → testabilité/IDE/API REST bloqués.
- **`strict_types`** quasi absent (seulement les helpers + les fichiers ajoutés récemment).
- **`forum/models/forum.php`** = god object **1756 LOC**.
- **Tests** : **90 tests / 366 assertions** (`composer test` ; ~50 méthodes unitaires sur helpers purs + ~17 d'intégration DB, data-providers inclus, cf. [gamification.md](gamification.md)).
  Le métier reste peu couvert — un **harness d'intégration** existe désormais (DB réelle + rollback) ;
  tester les *objets* du framework suppose toujours de découpler le service locator.
- Front 100 % jQuery/ES5, sans tooling. Détail et priorisation : [historique.md](historique.md).
