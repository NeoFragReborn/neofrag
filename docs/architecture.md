# Architecture — NeoFrag Reborn

Document technique de référence (**vérifié contre le code ; dernière relecture d'ensemble le 2026-10-02**). Pour
l'inventaire des composants, [components.md](components.md).

## 1. Identité & pile

CMS PHP **monolithique modulaire** pour communautés gaming/esport, fork local de NeoFrag.
La version courante est `NEOFRAG_VERSION`, dans [index.php](../index.php).

- **PHP ≥ 8.2** (intégration continue sur 8.2 → 8.5 ; production sur PHP 8.5-FPM derrière Caddy)
- **MariaDB 11**, driver **mysqli** (seul driver implémenté : `neofrag/drivers/mysqli.php`)
- **Composer** (deps dans `vendor/` ; plus de dossier `lib/`)
- **Bootstrap 5.3.8** (jQuery entièrement retiré — JS vanilla + helper `window.NF`), **SCSS** compilé serveur (scssphp)
- **62 modules · 42 widgets · 67 addons distribuables · 8 thèmes distribués** (admin, nebula, blockcraft, granite, forge, extend, chronique, pulse)

## 2. Cycle de requête

```
index.php
 ├─ define NEOFRAG_VERSION ; require vendor/autoload.php ; config/neofrag.php
 ├─ spl_autoload_register pour le préfixe NF\ (NF\Foo\Bar → foo/bar.php, minuscule)
 ├─ NeoFrag('NF\NeoFrag\NeoFrag')  → instancie le SINGLETON global (fonction globale NeoFrag())
 ├─ enregistre le callback __path() (mécanisme d'override 3 niveaux)
 ├─ charge les 10 services core : input, debug, url, db, access, config, output, session, groups, events
 └─ NeoFrag()->output()  → dispatch
      segments URL → module → contrôleur (variante) → méthode → vue *.tpl.php
```

Le dispatch (`neofrag/addons/module.php::get_method`) préfixe l'URL selon `admin`/`ajax`, filtre le
tableau `routes` du manifeste, puis matche par regex les placeholders `{id}/{key_id}/{url_title}/{url_title*}/{page}/{pages}`
(définis par `NeoFrag::$route_patterns`, dans `neofrag/neofrag.php`).

## 3. Le pattern réel : service locator implicite + méthodes magiques

Tout repose sur la fonction globale **`NeoFrag()`** (définie dans `index.php`) qui retourne un singleton. La classe
de base `NF\NeoFrag\NeoFrag` (`neofrag/neofrag.php`) donne à toute classe un accès magique aux services
et libraries :

- **`__get($name)`** : lazy-load des services `core_*` et des libraries, avec
  injection de la config `config/{name}.php`.
- **`__call($name, $args)`** : route dynamiquement vers displayables / addons / loadables / libraries.
- **`__path()` / `___load()`** : résolution de fichiers avec l'override 3 niveaux.

Détail PHP 8.2 : l'attribut `#[\AllowDynamicProperties]`, posé sur cette classe, ré-autorise
explicitement les propriétés dynamiques (dépréciées en 8.2).

**Conséquence (dette) :** pas de DI container ; la résolution dynamique casse l'autocomplétion IDE,
rend l'analyse statique et le mocking difficiles. Elle freine, elle ne bloque plus : les objets du
framework se testent par `HeadlessTestCase` (le framework démarré), le PhpDoc a rendu le locator
analysable par PHPStan, et l'API REST v1 existe depuis la 1.2.10 (`modules/api`).

## 4. Loadables & Displayables

- **Loadables** (`neofrag/loadables/`) : `Model` (legacy), `Model2` (ORM), `Controller`, `Install`,
  et les addons spécialisés (`Module`, `Widget`, `Theme`, `Language`, `Authenticator`). Chargés
  paresseusement via `__load()`.
- **Displayables** (`neofrag/displayables/`) : composants de layout **Zone → Row → Col → Widget**,
  stockés par thème/page dans `nf_dispositions` (arbre **JSON** via `libraries/disposition.php` ; decode
  rétro-compatible de l'ancien format PHP-serialized), éditables via le live editor.

## 5. ORM Model2

Chaque modèle déclare un `__schema()` retournant des `Field` chaînables (`neofrag/field.php` +
`neofrag/fields/` : bool, date, datetime, depends, enum, file, float, i18n, index, int, json, primary,
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

- **39 libraries** (`neofrag/libraries/`) : `form`, `form2`, `table`, `table2`, `panel`, `pagination`,
  `breadcrumb`, `modal`, `tab`, `html`, `js`, `css`, `view`, `date`, `lang`, `crypt`, `password`,
  `email`, `network`, `audit_log`, `rate_limit`, `anti_flood`, `captcha`, `totp_service`,
  `social_connect_session`, `moderation`, `mysqldump`, `disposition`, `json`, `collection`,
  `file_jail`… Accédées magiquement
  (`$this->form2`, `$this->rate_limit`, etc.).
- **26 helpers** (`neofrag/helpers/`, fonctions globales pures) : `array`, `string`, `color`,
  `countries`, `time`, `file`, `dir`, `fonts`, `input`, `location`, `markdown`, `notify`, `remote`,
  `sanitize`, `statistics`, `system`, `user_agent`, `assets`, `seo`, `debug`, `consentement`, `relais`,
  `bootstrap`, `erreurs`, `journal`, `theme`. Ce sont les **seuls éléments testables sans bootstrapper le singleton** (cf. tests
  pilotes). `sanitize` expose `sanitize_html()` (HTMLPurifier, anti XSS stocké) ; `bootstrap` expose
  `nf_bs_align()`, le point unique qui traduit un alignement en classe Bootstrap 5 — les
  bibliothèques du cœur fabriquaient la leur par concaténation, et émettaient donc des noms de
  Bootstrap 4 qu'aucune recherche textuelle ne pouvait trouver.

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

## 9. Carrefours de contribution inter-modules

Un **carrefour** est un point du CMS qui agrège de la donnée venant de *plusieurs* modules
(statistiques, activité d'un profil, tableau de bord, blocs de page). Plutôt que le carrefour
connaisse chaque module, **chaque module se branche** en exposant un contrôleur nommé d'après le
carrefour ; le carrefour scanne les modules installés et appelle ceux qui répondent.

```
Carrefour X  ──scanne──▶  pour chaque module : controller('X') existe ?
                                   │ oui
                                   ▼
                         module->controller('X')->X()  ──▶  donnée agrégée
```

Conséquence : **un nouveau module s'auto-intègre partout** rien qu'en posant ses fichiers de
contribution. C'est la brique « hooks » du CMS, sans bus d'événements lourd — complémentaire du
système d'événements (§8), qui sert au découplage *temporel* (publication, audit).

Exemple côté carrefour (`modules/statistics/models/statistics.php`) :

```php
foreach (NeoFrag()->model2('addon')->get('module') as $module) {
    if ($controller = @$module->controller('statistics')) {
        foreach ($controller->statistics() as $name => $statistic) { /* agrège */ }
    }
}
```

Les carrefours en place (`search` et `cron` sont décrits dans `tools/check-addon-contracts.php`) :

| Carrefour | Contrôleur attendu | Portée constatée |
|---|---|---|
| **Statistiques** | `controllers/statistics.php` | 19 modules |
| **Activité de profil** | `controllers/activity.php` | forum, news, articles |
| **Tableau de bord admin** | `controllers/dashboard.php` | bugtracker, newsletter |
| **Blocs de page** | `controllers/block.php` | news, articles, downloads |
| **Plan du site** | `controllers/sitemap.php` | 29 modules : chacun rend ses adresses publiques, visibles d'un visiteur, dans la langue du plan. Le même plan nourrit IndexNow : la tâche planifiée le compare, toutes langues, à son passage précédent (`nf_indexnow()`) |

> **Contrat Stats** : `statistics()` retourne `['clé' => ['title' => string, 'data' => callable,
> 'group_by'? => string]]`. Le `callable` configure une requête sur `$this->db` et **retourne le nom
> de la colonne datetime** à grouper dans le temps ; le carrefour ajoute la couleur et la série.

La **gamification déclarative par module** a été étudiée puis **écartée** : le système de points
existant (barème configurable) couvre le besoin sans toucher au cœur.

Le pattern « plusieurs modules, un point commun » existe déjà sous d'autres formes : commentaires
polymorphes (`module` + `module_id`), réactions multi-cibles, notifications/abonnements génériques,
corbeille (`Trash::TYPES`), recherche et flux RSS par module.

**Convention de scaffolding** — tout nouveau module de contenu fournit, selon pertinence :
`controllers/statistics.php` · `controllers/activity.php` · `controllers/dashboard.php` ·
`controllers/block.php` · `controllers/search.php`. Cf. [guide/create-a-module.md](guide/create-a-module.md).

## 10. Données

- **Schéma** : `install/schema.sql` (structure de référence du cœur, **Tier 0** uniquement, régénéré depuis la base vive via `tools/dump-schema.php` ; les modules apportent leurs tables via leur `install/install.sql`) ; les
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

## 11. Dette technique notable

- **Service locator + méthodes magiques** partout → testabilité et IDE freinés (cf. §3).
- **`strict_types`** : 1460 fichiers sur 1707 dans `neofrag`, `modules`, `widgets`, `addons` (2026-10-09) — tout le
  périmètre utile ; les 246 restants sont les gabarits `views/**.tpl.php`, où un `declare` ne protégerait rien.
  Le cliquet
  `tools/check-strict-types.php` interdit de reculer, la conversion se fait par petits lots éprouvés, la machinerie
  magique `__get` / `__call` pouvant révéler des coercitions à l'exécution.
- **`forum/models/forum.php`** : ramené de 1797 à **826 lignes** (six traits, trois bibliothèques pures) ; ce n'est plus
  un objet-dieu.
- **Tests** : **569 tests, 0 sauté** (2026-10-02) — `tests/Unit` (36 classes, sans base),
  `tests/Integration` (18 classes, base réelle en transaction annulée), `tests/Headless` (20 classes : le
  framework booté, de vrais modèles), `tests/Browser` (12 épreuves dans un vrai navigateur via
  `tools/check-js.php`). `--fail-on-skipped` : une suite qui se saute est un échec. Tester les *objets*
  du framework passe par `HeadlessTestCase`.

  Ce que couvre `tests/Unit` en priorité : les classes **pures** des addons — celles qui ne connaissent ni
  la base ni le service locator — parce que ce sont les seules qu'on peut éprouver sans monter
  l'application. C'est la raison pour laquelle chaque addon récent range sa décision dans un `lib/` :
  une plage de dates, un cadrage de carte, une grille d'antenne s'y testent en millisecondes.
- **Pas de bundler** : les JS sont servis tels quels (jQuery a été entièrement retiré en 2026-06 — le
  front est vanilla + primitives `window.NF`). `package.json` ne porte que des outils de développement
  (ESLint, Playwright), dont rien ne part au site servi. Les contrôles : `check-js-sources` (syntaxe et
  vocabulaire), `check-js-lint` et `check-js-console` (les vraies pages dans un vrai navigateur). Le
  bundler a été écarté le 2026-09-23 : le gain est faible (le serveur compresse déjà), le coût réel (une
  étape de fabrication, deux versions des fichiers, un débogage plus dur), et des fichiers simples sont un
  atout sur un hébergement mutualisé.
