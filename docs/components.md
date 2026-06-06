# Composants — NeoFrag Reborn 1.0.0

Inventaire des **52 modules · 39 widgets · 6 thèmes**. L'état/bugs de chaque composant
est dans [historique.md](historique.md) ; l'architecture du framework dans [architecture.md](architecture.md).
La pile gamification/boutique/monétisation a sa doc dédiée : [gamification.md](gamification.md).

## Anatomie d'un module

Un module = un dossier `modules/{x}/` avec un manifeste `{x}.php` (`namespace NF\Modules\{X}`,
étend `NF\NeoFrag\Addons\Module`) qui déclare :
- **`__info()`** : titre, icône, dépendances, et le tableau **`routes`** (URL → méthode) ; parfois un
  callback **`settings`** (form de config) ;
- **`permissions()`** (optionnel) : arbre de permissions RBAC ;
- **`__init()`** (optionnel) : listeners sur l'event bus v0.4.
- **controllers/** : variantes `index` / `admin` / `ajax` / `admin_ajax` / `api` + `*_checker.php`
  (contrôle d'accès avant exécution).
- **models/** (Model2), **views/** `*.tpl.php`, **forms/**, **langs/** (`{code}.php`, clés crc32b).

Il n'y a **pas** de dossier `install/` par module (le schéma vit dans `migrations/` + `schema.sql`).

## Anatomie d'un widget

`widgets/{x}/{x}.php` (`namespace NF\Widgets\{X}`, étend `…Addons\Widget`) avec `__info()` (+ souvent
un tableau `types` de variantes de rendu), `controllers/` (`index`=rendu, `admin`=config,
`checker`=validation), `views/*.tpl.php`, `langs/`.

## 52 modules (par domaine)

**Système & core (17)** — `access` (RBAC : rôles/permissions, audit log) · `addons` (install/activation) ·
`admin` (dashboard back-office) · `user` (inscription, login 2FA, profil, RGPD, export membres CSV/JSON) ·
`settings` (config globale) · `search` (agrégateur cross-modules) · `statistics` (dashboard agrégé) ·
`comments` (commentaires polymorphes) · `pages` (pages statiques + **routeur fallback** + injection de blocs
de module via `[block:clé]`) · `monitoring` (intégrité, backups ; auto-update upstream **désactivé** sur le
fork) · `tools` (SCSS, cache) · `emails` (templates email) · `revisions` (historique + restauration
générique, news/articles) · `trash` (corbeille soft-delete cross-module : news/articles/galerie/commentaires/forum) ·
`menu` (constructeur de menus nommés réutilisables, items hiérarchiques, rendu via widget `navigation`) ·
`webhooks` (webhooks sortants signés HMAC déclenchés par les événements) · `marketplace` (catalogue public
d'addons + téléchargement, miroir de la vitrine).

**Contenu (12)** — `news` · `articles` (blog) · `wiki` (+ révisions) · `faq` · `downloads` · `links` ·
`gallery` · `media` (bibliothèque d'uploads) · `slider` · `guestbook` · `calendar` (+ export iCal) ·
`feeds` (flux RSS 2.0 des news et articles).

**Gaming (6)** — `teams` · `games` (jeux/maps/modes) · `events` (tournois/matchs/rounds) · `recruits`
(recrutement + formulaire personnalisable) · `awards` · `partners`.

**Communauté & communication (13)** — `talks` (chat + MP unifiés : `public`/`group`/`direct`) ·
`members` (liste/profils, façade sur `user`) · `forum` (catégories, sujets, messages, recherche FT,
mentions, PJ, modération) · `moderation` (signalements, sanctions, banlist IP) · `contact` (formulaire
+ rate-limit + captcha) · `newsletter` · `bugtracker` · `donations` (campagnes PayPal) · `surveys`
(sondages) · `live_editor` (éditeur visuel de layouts) · `notifications` (cloche + abonnements
contenu/catégorie) · `reactions` (likes polymorphes : commentaires/articles/news/forum) ·
`classifieds` (petites annonces membres : offres/demandes, contact vendeur, modération optionnelle).

**Monétisation & engagement (4)** — `gamification` (karma/réputation + points + VIP, barème configurable) ·
`shop` (boutique découplée : grades/cosmétiques/perks/VIP/merch, paiement en points) · `ads` (régie
publicitaire, masquée pour les membres no_ads/VIP) · `payments` (Stripe : recharge de points + packs VIP,
webhook signé). Détail : [gamification.md](gamification.md).

## 39 widgets

- **Contenu** : `news`, `articles`, `awards`, `calendar`, `donations`, `downloads`, `events`, `forum`,
  `gallery`, `guestbook`, `links`, `members`, `newsletter`, `partners`, `recruits`, `slider`,
  `surveys`, `talks`, `teams`, `user`, `video` (player HTML5 + playlist depuis la médiathèque),
  `latest_comments` (derniers commentaires cross-module) — présentateurs sur leur module.
- **Landing / vitrine** : `landing` (page d'accueil vitrine : hero, features, roadmap publique).
- **Services externes** : `discord`, `steam`, `twitch`, `teamspeak`, `gameserver` (utilisent les libs
  vendor planetteamspeak/ts3 + xpaw/php-source-query), `socials`.
- **Structure / divers** : `navigation`, `breadcrumb`, `header`, `about`, `clock`, `copyright`,
  `html` (saisie de code HTML), `search`, `module` (méta-widget d'insertion), `ads` (annonce de la régie
  par emplacement, masquée pour no_ads/VIP).

## 10 addons

- **Authentification (4)** : `authenticator` (base) + 3 providers OAuth : `discord`, `github`,
  `google`. *(OAuth câblé côté code mais désactivé tant qu'aucune clé n'est configurée. Les providers
  `steam`/`twitch`/`linkedin`/`facebook`/`twitter`/`battle_net` ont été retirés — voir roadmap, OAuth
  nettoyé `e075cfe`.)*
- **Langues (6)** : `language_en`, `language_fr`, `language_de`, `language_es`, `language_it`,
  `language_pt`.

## 6 thèmes

- **admin** — back-office (dark mode complet, command palette).
- **vitrine** — thème vitrine « NeoFrag Reborn » (navbar glass full-dark, landing, cloche + compte dans la nav). Core.
- **nebula** — thème communautaire en DA Reborn (chrome propre, clair/sombre). Core.
- **blockcraft** — public, identité « blocs » (vert herbe, coins carrés, ombres-blocs), jour/nuit.
- **granite** — public, « pierre taillée » (teal/ardoise, titres Oswald, plat hairline), jour/nuit.
- **forge** — public, « fonte en fusion » (rouge lave, Rajdhani, lueur de braise), nuit par défaut.

> Le thème `dungeon` a été retiré. CSS thème = template PHP à tokens (`--bc-*`/`--gr-*`/`--fg-*`), couleurs
> d'accent/fond/images **configurables en admin**. Installation d'un thème déposé sur disque : admin →
> Addons → **« Scanner le disque »** ; suppression via l'action **« Supprimer »**. Réseaux sociaux du
> footer = **globaux** (`nf_social_*`, partagés par tous les thèmes).

## Patterns transversaux

- **`comments` polymorphe** : s'attache à n'importe quelle entité via `(module, module_id)` + `__invoke()`.
- **Carrefours de contribution** : un carrefour énumère les modules installés et appelle leur contributeur
  homonyme (`controller('X')->X()`). Carrefours livrés : `search`, `statistics` (19 modules), `activity`
  (mur d'activité profil), `dashboard` (cartes « à traiter » admin), `block` (blocs injectables dans les
  pages, news/articles/downloads).
- **`pages` = fallback + page_blocks** : toute URL non matchée tombe sur une page statique ; le contenu peut
  injecter un bloc de module via `[block:clé]`.
- **`talks` = chat + MP unifiés** ; les vieilles tables `nf_users_messages*` sont orphelines.
- **Event bus v0.4** : découple les réactions cross-module (flux talks, audit log access).
