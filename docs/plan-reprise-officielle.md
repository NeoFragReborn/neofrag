# Plan — NeoFrag Reborn (reprise officielle de NeoFrag)

> Rédigé le 2026-06-04. NeoFrag officiel (neofr.ag) est en sommeil depuis ~2020 (dernier
> article blog 2020, doc jamais réalisée, addons figés « aucun compatible »). Notre fork Reborn
> est très en avance (52 modules, 39 widgets, migrations, tests, installeur web, soft-delete…).
> Objectif : **reprendre officiellement le projet sous le nom « NeoFrag Reborn »** — site vitrine
> vivant + doc + démo + communauté.

## État au 2026-06-04 (avancement)

- **Chantier 1 — Thème/landing vitrine : FAIT.** Thème `vitrine` (dark navy + teal, glassmorphism,
  Space Grotesk) + widget `landing` (hero, features, dev, **roadmap**, CTA). Navbar glass : vraie nav
  (Fonctionnalités, Thèmes & Addons → /marketplace, Documentation → /wiki, Forum, Blog, Roadmap) +
  **Connexion/Inscription en modals** (`ajax/user/login|register`). **Modal « Démarrer »** (compte →
  forum/Discord, ou GitHub, ou guide). Blog restylé **en override vitrine** (cartes modernes, pleine
  largeur). Forum modernisé pour la vitrine (CSS thème). Popover profil passé en sombre.
- **Bonus — rework admin & 2e thème : FAIT.** Thème `admin` refondu (sidebar, IA repensée, clair/sombre,
  pins, command palette). 2e thème front **`nebula`** (communautaire, chrome 100% custom).
- **Chantier 2 — Marketplace : FAIT (showcase).** Manifeste **3 tiers** (`tools/addons-manifest.php`),
  packaging (`tools/package-addons.php` → 52 entrées `marketplace/catalog.json`), module `marketplace`
  (showcase public /marketplace, fiches + filtres + téléchargement). ⏳ **Seed/schema core-only + étape
  installeur (téléchargement distant) = PHASE FINALE** (cf. TODO §1.1). granite/forge/blockcraft → marketplace.
- **Chantier 3 — Documentation : FAIT.** `docs/guide/` (9 pages : install/concepts/admin/marketplace +
  guides dev thème/widget/module + framework) **+ on-site via le module wiki** (`tools/seed-wiki-docs.php`
  transfère les .md ; wiki restylé : sommaire en cartes + page pleine largeur avec nav docs). Page article
  news refaite (vue dédiée, générale).
- **Chantier 4 — Démo : EN ATTENTE** (sous-domaine **`demo.neofrag-reborn.xyz`** à venir, auto-reset cron ;
  liens démo en placeholder). Phase finale : peuplement de données + tests e2e complets.
- **Chantier 5 — Forum + Discord : à faire** (forum déjà restylé pour la vitrine ; structure des
  catégories + Discord à monter).
- ⚠ **À régler** : repo GitHub public Reborn (liens download = placeholder `NeoFrag/neofrag`) ; éditeur de
  commentaire TinyMCE en skin clair (blanc) sur thème sombre ; le **CSS gabarit PHP** du thème vitrine est
  caché serveur par `?v=` (mtime non propagé Docker/Windows) → Ctrl+F5 pour voir les éditions CSS.

## Nom & héritage

- Le projet de reprise s'appelle **NeoFrag Reborn** (continuité, pas un rebrand opportuniste).
- **Crédit explicite au projet original** partout (footer, page À propos, doc, README, dépôt) :
  NeoFrag créé par **Michaël BILCOT & Jérémy VALENTIN** — https://neofr.ag. Licence **LGPLv3**
  respectée (le fork reste open source, mêmes libertés). Ton : « NeoFrag Reborn — la suite
  communautaire de NeoFrag », reconnaissant, jamais en effaçant les auteurs d'origine.
- La **roadmap affichée** est celle du FORK (état réel 1.0.0 + ce qui vient), pas la roadmap 2018
  de neofr.ag.

## Cadrage acté (user)

- **Hébergement** : mutualisé (cPanel/OVH-type). → PHP/MySQL natif, **pas de Docker en prod** (le dev
  local reste Docker), sous-domaines via panel, **cron du panel** pour l'auto-reset démo, déploiement FTP/git.
- **Site vitrine** : **dogfooding** — le site officiel EST une instance du fork (thème vitrine + modules).
- **Charte** : reprise de l'identité NeoFrag (**dark navy low-poly + accent teal/cyan**, esprit eSport),
  ajustable.
- **Premier chantier** : le thème/landing vitrine.

## Architecture cible (sous-domaines)

| Domaine | Rôle | Comment |
|---------|------|---------|
| `neofrag-reborn.xyz` | Site officiel (vitrine + blog + forum + FAQ + download) | Instance NeoFrag, thème `vitrine` |
| `demo.neofrag-reborn.xyz` | Démo publique gaming | Instance NeoFrag + données démo + **cron de reset** |
| `docs.neofrag-reborn.xyz` | Documentation | Depuis `docs/*.md` (générateur statique ou pages NeoFrag) |
| (marketplace) | Thèmes & Addons téléchargeables | Module `addons` étendu + dépôt d'addons |
| Discord | Communauté | Salons calqués sur le forum |

## Chantiers

### 1. Thème / landing vitrine — **FAIT** (voir « État » ci-dessus)
- Thème `vitrine` (charte NeoFrag dark+teal), basé sur la structure granite.
- **DA moderne 2026, PAS une copie du neofr.ag de 2018** : on reprend l'ESPRIT (dark, teal/cyan,
  gaming/eSport) mais avec une esthétique actuelle — typographie soignée (titres condensés + corps
  lisible), dégradés et accents néon maîtrisés, **glassmorphism/cartes modernes**, ombres douces,
  espacements généreux, **animations subtiles au scroll** (reveal, parallax léger), dark mode élégant,
  responsive impeccable. Référence d'esprit : landing SaaS/gaming moderne (pas le low-poly daté).
- Home = **landing** (widget `landing` full-width) : hero (« NeoFrag Reborn — crée ton site eSport &
  Gaming ») → features (modules/perf/sécurité/multilingue…) → section développeurs → **roadmap du fork**
  → newsletter → footer (avec crédit au projet original). Mention « basé sur NeoFrag de M. Bilcot &
  J. Valentin ».
- Nav : Fonctionnalités, Thèmes & Addons (marketplace), Documentation, Forum, Blog. Boutons Démo + Download.
- Pages internes (forum/blog/faq) : layout CMS standard aux couleurs NeoFrag.

### 2. Découplage core / marketplace
- **Décider d'un « core » minimal** installé par défaut (user, access, admin, settings, menu, forum,
  news, comments, members, trash, monitoring, addons, contact…) vs **addons optionnels** (teams, events,
  matches, gallery, downloads, faq, wiki, guestbook, bugtracker, partners, awards, classifieds, ads,
  shop, gamification, recruits, surveys, donations, feeds, newsletter…, widgets de niche, thèmes extra).
- Retirer les optionnels du **seed** (`install/seed.sql`) → install fraîche = core only.
- **Marketplace** : étendre le module `addons` (déjà présent) pour lister/télécharger/installer des
  addons depuis un dépôt (chaque addon packagé en .zip + métadonnées : nom, version, type, compat,
  capture, description). Réutiliser la logique de téléchargement/extraction du module `monitoring`
  (auto-update) — déjà du `ZipArchive` + scan. Garde-fou : signature/checksum.
- Peupler le dépôt avec nos propres modules/widgets/thèmes sortis du core → marketplace non vide d'emblée.
- ⚠️ Décision à acter : la **liste exacte** core vs optionnel.

### 3. Documentation (tech + non-tech) → `docs.neofrag-reborn.xyz`
- **Non-technique** (admin/utilisateur) : prise en main, installation (le wizard), gestion des modules/
  widgets/thèmes, configuration, modération, permissions/rôles, sauvegardes.
- **Par module/widget** : à quoi il sert, réglages, cas d'usage.
- **Technique (développeur)** : architecture du CMS (service locator, addons, routing, dispositions/zones,
  models/model2, migrations, helpers), **création d'un module / thème / widget pas à pas**, API, hooks.
- Source : nos `docs/*.md` existants (architecture.md, components.md, development.md…) à étendre.
- Forme : générateur statique (Docusaurus/MkDocs/Astro Starlight) hébergé sur le sous-domaine, OU pages
  NeoFrag. À décider.

### 4. Démo auto-reset → `demo.neofrag-reborn.xyz`
- Jeu de **données de démo** (équipes, matchs, news, forum, membres fictifs aux noms générés) = un
  `demo.sql` (réutiliser `tools/dump-schema.php` pour produire un dump démo).
- **Script de reset** (PHP, lançable en cron du mutualisé) : DROP/recrée la base démo, réimporte
  schema + demo.sql, régénère les dates « du jour ». Fréquence : horaire/quotidien.
- Bandeau « démo — réinitialisée chaque heure », comptes de test affichés.

### 5. Forum + Discord (communauté)
- **Forum** (sur `neofrag-reborn.xyz`) : reproduire la structure observée — **Général** (Annonces, Discussions,
  Présentation membres, Présentation de votre site) · **NeoFrag/Fork** (Fonctionnalités, Bugs, FAQ,
  Traductions) · **Coin des bidouilleurs** (Guides, HTML/CSS/JS, Modules, Thèmes, Widgets, Overrides) ·
  **English area**.
- **Discord** public structuré, salons calqués sur le forum (annonces, support, dev, présentation de
  sites, EN), + webhooks (nouvelles releases, posts forum).

## Ordre proposé
1. Thème/landing vitrine (en cours). 2. Découplage core + marketplace. 3. Documentation. 4. Démo
auto-reset. 5. Forum + Discord. (2 et 3 peuvent avancer en parallèle de la mise en ligne.)

## Décisions à acter en chemin
- Couleur définitive de la charte vitrine (teal NeoFrag conservé par défaut).
- Liste exacte **core vs addons optionnels** (chantier 2).
- Techno de la doc (générateur statique vs pages NeoFrag).
- NDD réel + structure des sous-domaines côté panel mutualisé.
