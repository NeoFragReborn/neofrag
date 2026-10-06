# Composants — NeoFrag Reborn

Inventaire des **62 modules · 41 widgets · 7 thèmes distribués**. L'architecture du framework est dans
[architecture.md](architecture.md).
La pile gamification/boutique/monétisation a sa doc dédiée : [gamification.md](gamification.md).

## Anatomie d'un module

Un module = un dossier `modules/{x}/` avec un manifeste `{x}.php` (`namespace NF\Modules\{X}`,
étend `NF\NeoFrag\Addons\Module`) qui déclare :
- **`__info()`** : titre, icône (qui doit exister dans le FontAwesome embarqué), les trois **déclarations de
  découplage** `core` / `presets` / `requires` (obligatoires — la CI refuse un addon muet), le tableau
  **`routes`** (URL → méthode), `'admin' => TRUE` s'il a une administration ; parfois `settings` ;
- **`declare_content_types()`** (modules de contenu) : les tables que réactions, abonnements, révisions,
  corbeille et gamification collectent ;
- **`controllers/statistics|activity|dashboard|block|search|sitemap.php`** (optionnels) : les **carrefours**, dont
  les méthodes sont figées par `tools/check-addon-contracts.php` ;
- **`permissions()`** (optionnel) : arbre de permissions RBAC ;
- **`__init()`** (optionnel) : listeners sur l'event bus v0.4.
- **controllers/** : variantes `index` / `admin` / `ajax` / `admin_ajax` / `api` + `*_checker.php`
  (contrôle d'accès avant exécution).
- **models/** (Model2), **views/** `*.tpl.php`, **forms/**, **langs/** (`{code}.php`, clés crc32b).

Un module **livre ses tables** dans `modules/{x}/install/install.sql` (suppression dans
`install/uninstall.sql`), joué automatiquement à l'installation. Les évolutions de schéma entre
versions passent par des **migrations par-addon** (`modules/{x}/install/migrations/*.up.sql`, suivies
dans `nf_addon_migrations`).

## Anatomie d'un widget

`widgets/{x}/{x}.php` (`namespace NF\Widgets\{X}`, étend `…Addons\Widget`) avec `__info()` (icône
obligatoire, `core` / `presets` / `requires`, + souvent un tableau `types` de variantes de rendu),
`controllers/` (`index` = rendu, `admin` = formulaire de réglages — son absence fait sauter l'étape
« Configuration » de l'assistant, `checker` = validation **avec valeurs de repli** pour un widget posé sans
réglages, cf. `tools/check-widget-reglages.php`), `views/*.tpl.php`, `langs/`. Les feuilles n'emploient
que le vocabulaire `--nf-*` que tous les thèmes définissent (`tools/check-css-variables.php`).

## 62 modules (par domaine)

**Système & core (21)** — `access` (RBAC : rôles/permissions, audit log) · `addons` (install/activation) ·
`admin` (dashboard back-office) · `user` (inscription, login 2FA, profil, RGPD, export membres CSV/JSON) ·
`settings` (config globale) · `search` (agrégateur cross-modules) · `statistics` (dashboard agrégé) ·
`comments` (commentaires polymorphes) · `pages` (pages statiques + **routeur fallback** + injection de blocs
de module via `[block:clé]`) · `monitoring` (intégrité, backups ; auto-update upstream **désactivé** sur le
fork) · `tools` (SCSS, cache) · `files` (gestionnaire de fichiers : arborescence de `upload/files/`, ACL par
fichier/dossier, téléchargement public par slug) · `emails` (templates email) · `emojis` (émojis personnalisés
`:nom:` uploadés en admin, rendus partout via le helper `bbcode()` ; cœur, hors marketplace) · `revisions` (historique + restauration
générique, news/articles) · `trash` (corbeille soft-delete cross-module : news/articles/galerie/commentaires/forum) ·
`menu` (constructeur de menus nommés réutilisables, items hiérarchiques, rendu via widget `navigation`) ·
`webhooks` (webhooks sortants signés HMAC déclenchés par les événements) · `api` (l'API REST `/api/v1` : clés d'accès hachées et révocables, droits par clé, débit limité ; pour le bot Discord et les intégrations — optionnel) · `discord` (le bot Discord du site, réglé depuis l'administration : sa clé gardée chiffrée, marche/pause, redémarrage, état, journal, correspondances salons ↔ forums et groupes ↔ rôles — optionnel, demande `api`) · `marketplace` (catalogue public
d'addons et téléchargement).

**Contenu (15)** — `news` · `articles` (blog) · `wiki` (+ révisions) · `faq` · `downloads` · `links` ·
`gallery` · `media` (bibliothèque d'uploads) · `slider` · `guestbook` · `calendar` (+ export iCal) ·
`feeds` (flux RSS 2.0 des news et articles) · `quotes` (citations classées, avec auteur et source) ·
`recipes` (recettes : ingrédients et étapes une par ligne, durées, balisage `schema.org/Recipe`) ·
`glossary` (lexique rangé par lettre, accents et ligatures ramenés à leur lettre, synonymes) ·
`places` (carte OpenStreetMap, Leaflet auto-hébergé, aucune image de la bibliothèque embarquée,
page utilisable sans JavaScript) · `webradio` (lecteur `<audio>` d'un flux distant et grille
hebdomadaire ; **le seul module qui ouvre la politique de sécurité** — `media-src` reçoit
l'origine du flux configuré, et rien si aucun flux ne l'est) · `sandbox` (bac à sable de mise
en forme réservé aux membres : le rendu passe par la MÊME fonction que le forum, et le module
affiche ce que l'assainissement a retiré — la seule page du produit qui l'explique).
Les trois derniers partagent le moule de `faq` : catégories d'un côté, entrées de l'autre, page
publique et écran d'administration avec recherche, filtre et tri.

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

## 41 widgets

- **Contenu** : `news`, `articles`, `awards`, `calendar`, `donations`, `downloads`, `events`, `forum`,
  `gallery`, `guestbook`, `links`, `members`, `newsletter`, `partners`, `recruits`, `slider`,
  `surveys`, `talks`, `teams`, `user`, `video` (player HTML5 + playlist depuis la médiathèque),
  `latest_comments` (derniers commentaires cross-module), `frise` (la saison mois par mois : rendez-vous du calendrier,
  actualités, discussions du forum et albums photo, chacun si son module est installé) — présentateurs sur leur module.
- **Services externes** : `discord`, `steam`, `twitch`, `teamspeak`, `gameserver` (utilisent les libs
  vendor planetteamspeak/ts3 + xpaw/php-source-query), `socials`.
- **Structure / divers** : `navigation`, `breadcrumb`, `header`, `about`, `clock`, `copyright`,
  `html` (saisie de code HTML), `search`, `module` (méta-widget d'insertion), `ads` (annonce de la régie
  par emplacement, masquée pour no_ads/VIP), `rss` (lecteur d'un flux RSS/Atom extérieur, en cache, rafraîchi par le cron —
  à ne pas confondre avec le module `feeds`, qui PUBLIE nos propres flux), `seasonal` (neige, confettis ou feuilles sur une plage de
  dates `MM-JJ` pouvant enjamber le Nouvel An ; rendu côté serveur, rien du tout hors saison).

## 10 addons

- **Authentification (4)** : `authenticator` (base) + 3 providers OAuth : `discord`, `github`,
  `google`. *(OAuth câblé côté code mais désactivé tant qu'aucune clé n'est configurée. Les providers
  `steam`/`twitch`/`linkedin`/`facebook`/`twitter`/`battle_net` ont été retirés — voir roadmap, OAuth
  nettoyé `e075cfe`.)*
- **Langues (6)** : `language_en`, `language_fr`, `language_de`, `language_es`, `language_it`,
  `language_pt`.

## 7 thèmes distribués

- **admin** — back-office (dark mode complet, command palette).
- **nebula** — thème communautaire en DA Reborn (chrome propre, clair/sombre). Core.
- **blockcraft** — public, identité « blocs » (vert herbe, coins carrés, ombres-blocs), jour/nuit.
- **granite** — public, « Gazette » (2.0.0) : le journal du club — la date et le titre imprimés, les rubriques entre deux
  filets, la ligne « En bref » qui défile, la une à colonnes et ses lettrines ; papier le jour, encre la nuit.
- **forge** — public, « Coulée » (2.0.0) : le rail d'acier sur le côté, le foyer de lave et ses braises, le tableau de
  bord, le forum en tiroirs ; nuit par défaut, mode jour au choix.
- **chronique** — public, le carnet de la saison : un en-tête discret et un « Sommaire » plein écran, l'ouverture avec
  la semaine en cours, la frise de la saison, une colonne à côté ; papier le jour, nuit « à la lampe ».
- **extend** — port BS5 du thème « Extend » de Chewbaka (navy & bleu acier, titres Economica, jour/nuit, multi-zones). Distribuable via la marketplace.

> Le thème `dungeon` a été retiré. CSS thème = template PHP à tokens (`--bc-*`/`--gz-*`/`--fg-*`/`--ch-*`), couleurs
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
