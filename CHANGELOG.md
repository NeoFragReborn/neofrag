# Changelog

Tous les changements notables de **NeoFrag Reborn** sont consignés ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ;
le projet suit le [versionnage sémantique](https://semver.org/lang/fr/).

NeoFrag Reborn est la continuité communautaire de **NeoFrag** (base Alpha 0.2.4), créé à l'origine par
Michaël BILCOT & Jérémy VALENTIN — projet open source sous licence LGPLv3.

---

## [1.1.0] — 2026-08-23

### Sécurité
- **Audit complet + durcissement** :
  - **Sauvegardes** : les archives (`backups/*.zip`, contenant le dump SQL + `config/`) ne sont plus
    servies en HTTP (`backups/.htaccess` + `logs/.htaccess`, parité dans `nginx.conf`) et leur nom est
    suffixé par `random_bytes` (plus devinable).
  - **Jetons** : `unique_id()` utilise un CSPRNG (`random_bytes`) — concerne ID de session, jetons CSRF,
    liens de reset/validation. Les liens de reset/validation **expirent en 1 h** et sont uniques par compte.
  - **Connexion sociale & reset** : le 2FA (TOTP) et le bannissement sont désormais vérifiés sur **tous**
    les chemins de connexion (mot de passe, OAuth, reset), pas seulement la voie mot de passe.
  - **Liens d'e-mail** : URL absolues construites sur une **origine canonique** (`config/url.php`, figée à
    l'installation) au lieu de l'en-tête `Host` (anti *host header injection* / *password-reset poisoning*).
    Idem callbacks OAuth et retours Stripe.
  - **XSS admin** : noms de pièces jointes (snapshot de modération) et IP `X-Real-IP` (forgeable) validés
    et échappés au stockage et au rendu ; MOTD de serveur de jeu (API tierce) assaini par HTMLPurifier.
  - **CSRF** : jeton exigé sur **toutes** les actions admin mutantes (suppression/bascule/clôture/
    approbation/activation/restauration/purge) de 22 modules — auparavant de simples liens GET.
  - **Outils** : `tools/*.php` refusent toute exécution hors CLI ; `composer audit` ajouté à la CI.
  - **CSP effective — fin des gestionnaires d'événements inline** : le `script-src` strict (nonce, sans
    `unsafe-inline`) bloque les attributs `on*="…"` inline (les nonces ne les couvrent pas). Tous les
    handlers inline restants (bandeau cookies, pagination des tables admin, confirmations trash/revisions,
    aperçu de fichier des messages privés, actions groupées des mentions forum, sélection au clic, modal de
    suppression) sont passés en `addEventListener` délégué (via `main.tpl`, `js/delete.js`, `js/confirm.js`).
    La délégation couvre en prime le contenu injecté en AJAX. Corrige aussi un `onchange` de pagination qui
    référençait encore `$()` (jQuery pourtant retiré).

### Corrigé (compatibilité base de données)
- **Transactions sur MySQL 8** : `START TRANSACTION` passait par le pipeline *prepared statement* (refusé
  par MySQL 8, erreur 1295) → contrôle transactionnel via l'API mysqli (`begin_transaction`/`commit`/
  `rollback`). Tous les écrits forum/talks/slider étaient fatals sur MySQL 8.
- **Emojis (utf8mb4)** : la connexion forçait `utf8` (= utf8mb3) → tout caractère 4 octets provoquait une
  erreur 1366. Connexion en `utf8mb4` + migration de toutes les tables en `utf8mb4_unicode_ci` (collation
  portable MySQL 8 / MariaDB 10, fin des `uca1400` spécifiques MariaDB 11).
- **`where('col', [])`** générait une condition vide (→ `DELETE`/`UPDATE` sur toute la table) : produit
  désormais `1 = 0` (ensemble vide).
- **Dates futures en `TIMESTAMP` (limite 2038)** : les colonnes stockant une date choisie dans le futur
  — envoi programmé de newsletter (`scheduled_at`), publication programmée de news/articles/gallery/pages
  (`date`), dates d'événement (`date`/`date_end`/`publish_date`) — étaient en `TIMESTAMP`, dont la plage
  s'arrête au 19/01/2038. Au-delà, MariaDB en mode strict **rejette l'écriture** (errno 1292) et
  l'insertion échouait silencieusement (driver en `mysqli_report(OFF)`). Passées en `DATETIME` (jusqu'à
  l'an 9999). Migrations fournies pour les installations existantes. Bug attrapé par
  `tests/Headless/NewsletterSchedulingTest` (jamais exécuté en CI faute de base de données branchée).

### Corrigé (routage & interface)
- **`/user/login` et `/user/registration` renvoyaient 404** : les thèmes exposent ces URLs en lien des
  boutons d'en-tête (repli sans JavaScript de la modale), mais les méthodes de contrôleur correspondantes
  n'existaient que côté AJAX. Ajoutées à `modules/user/controllers/index.php` + gardes dans `checker.php`.
- **Bouton « Inscription » mort quand les inscriptions sont fermées** : le thème Nebula
  affichait le bouton sans vérifier `nf_registration_status` → un clic menait à un 404.
  Masqué quand les inscriptions sont fermées.

### Ajouté
- **Cache-bust des assets par mtime** : chaque CSS/JS est servi `?v=<mtime du fichier résolu>`
  (overrides inclus, helper `asset_version()`) → **auto-invalidation par fichier** à chaque
  modification/upload, sans bumper `nf_version_css` à la main (repli sur `nf_version_css` si le fichier
  n'est pas localisable).
- **Marketplace** : signalement d'une nouvelle version du **cœur** (lit `base_version` du catalogue) dans
  l'écran « Mises à jour ».
- **Installeur** : refonte esthétique — logo NeoFrag (SVG vectoriel), bandeau « Fork non officiel de
  NeoFrag », crédit Michaël BILCOT & Jérémy VALENTIN (LGPLv3), polices Reborn (Inter + Space Grotesk),
  palette teal.
- Module **files** (gestionnaire de fichiers, arborescence + ACL par fichier/dossier) validé et embarqué
  → **54 modules**.
- Module **emojis** (cœur) : émojis personnalisés rendus partout via `:nom:` (helper `bbcode()`), CRUD admin.
- **Newsletter** : programmation d'envoi + file batchée pilotée par cron, suivi des ouvertures (pixel + taux),
  modèles d'e-mail réutilisables, segmentation (tous / membres / groupe).
- **Events** : événements récurrents (occurrences matérialisées) + rappels cron aux participants.
- **Réactions multi-emoji** (👍❤️😂😮😢😡, façon Discord/FB) sur le cœur de réactions polymorphe.
- Widget **« Statut live »** multi-chaînes / multi-plateformes (Twitch + YouTube, abstraction provider).
- **Page-builder** : blocs de module paramétriques `[block:clé p=v]` + blocs ordonnés/configurés par page
  (composer admin, table `nf_pages_instances`).
- Thème **Extend** (port BS5) distribuable via la marketplace ; **recherche instantanée** (typeahead),
  **tri** sur 10 grilles admin, **avatars GIF animés** préservés, **ACL** éditable en modale.
- **Chaîne de publication à source unique** : `tools/changelog-section.php` extrait une section de ce
  fichier (Markdown ou HTML) ; les notes de version en sont tirées, et leurs textes ne peuvent plus
  diverger.

### Modifié
- **Bootstrap 4.6.2 → 5.3.8** + **jQuery entièrement retiré** : JS 100 % vanilla derrière un helper minimal
  `window.NF` (ready/data/ajax/setHtml/loadScript avec nonce CSP). 9 plugins jQuery/BS4 remplacés
  (notify→toasts BS5, selectize→tom-select, datetimepicker→flatpickr, FullCalendar 3→6, color/iconpicker
  /treeview/knob→vanilla, mCustomScrollbar→scroll natif), jQuery UI→SortableJS.
- **Dark mode** harmonisé sur tous les thèmes (`data-bs-theme` + remap des variables BS5 sur les tokens
  `--nf-*`, TinyMCE suit le thème).
- **Réglages widgets & dispositions encodés en JSON** (remplace `serialize` PHP) — supprime la surface
  d'injection d'objet ; décodage rétro-compatible de l'ancien format.

### Corrigé
- **Widget Discord** : vrai diagnostic d'échec (widget désactivé / ID introuvable / réseau) au lieu d'un
  message générique, **vrai logo du serveur** (via l'invitation publique), compteur en ligne **autoritatif**
  (`presence_count`, la liste des membres est plafonnée à 100).
- **Widget TeamSpeak (mode arbre)** : le viewer du framework crashait sous PHP 8 → **rendu maison** (arbre
  canaux/clients, icônes FontAwesome 6, plus aucun pack d'icônes requis) ; erreurs réseau génériques (ne
  fuitent plus `host:port`).
- **Widgets réseau** : `Network` auto-décodait déjà le JSON → double-décodage = faux « inaccessible » (Discord,
  Twitch, gameserver) ; + **User-Agent par défaut** (sans lui, les API derrière Cloudflare renvoient 403).
- **Monitoring / sauvegarde sous PHP-FPM** : `_stream()` appelait `@apache_setenv()` (disponible seulement
  sous mod_php) → fatal sous Apache fpm-fcgi (en PHP 8 le `@` ne masque pas l'`Error`) ; gardé par
  `function_exists()`. Backup AJAX servi **sans extension `.json`** (avalée par le « smart static »
  nginx/Plesk → 404). Suppression/téléchargement de sauvegarde (le placeholder `{url_title}` n'accepte pas
  le `.zip`). Garde treeview « Not initialized » + `padding-bottom` invalide.
- **Marketplace injoignable** : URL par défaut passée en **non-www** (`https://neofrag-reborn.xyz/marketplace`).
- **Upload** : `uploaded_file($files, …, $var)` traitait `$var = 0` (1er fichier d'un envoi multiple) comme
  falsy → `basename(array)` → 404 ; corrigé (`$var !== NULL`).

### Documentation
- Wiki dev/utilisateur enrichi (`form()`/`form2()`, checker de widget, dépannage installation, workflow
  rôle, marketplace injoignable…) + note de déploiement **nginx/Plesk** (`.json` en static → 404).

---

## [1.0.0] — 2026-06-06 · socle Reborn (base Alpha 0.2.4)

Premier cycle du fork : modernisation du socle, durcissement sécurité et large vague de
fonctionnalités.

### Ajouté

**Plateforme & outillage**
- Runner de migrations versionnées + commande `baseline`, tests PHPUnit pilotes, bootstrap tolérant.
- **Installeur web** (assistant 4 étapes : Prérequis → Base de données → Administrateur → Terminé) :
  modèle **« tout bundlé »** — tous les modules, widgets et thèmes livrés sont installés et activés
  automatiquement à l'étape « Base de données » (page d'accueil garantie non vide).
- **Marketplace distant** (catalogue + archives servis depuis neofrag-reborn.xyz) : sert **après**
  l'installation — **détection des mises à jour** des addons installés (+ migrations de schéma par-addon)
  et **ajout d'addons tiers** en un clic depuis l'admin. Sécurité : HTTPS strict, vérification **SHA-256**,
  **anti-zip-slip** (chemins/`..`/symlinks), origine fixe (anti-SSRF), tailles/timeout bornés.
- Outils de packaging : `package-addons` (catalogue du marketplace) / `build-release` (paquets FTP).
- CLI de **maintenance** : purge de la corbeille et des comptes jamais confirmés (cron externe).

**Thèmes & interface**
- Thème communautaire **Nebula** + thèmes **Forge**, **Blockcraft**, **Granite** ; pont de tokens `--nf-*` pour la cohérence.
- **Rework complet du panel admin** (nouvelle direction artistique, sidebar, palette de commandes, clair/sombre).
- Sélecteurs de **thème** et de **langue** en pied de page (visiteurs inclus).

**Contenu**
- News & articles : image à la une, **publication programmée** (parution à l'heure réelle via endpoint cron gardé par token : notifications/webhooks/gamification émis au bon moment, plus à l'enregistrement), compteur de vues, actions en masse, recherche/filtre/pagination, page article dédiée.
- **Publication programmée** aussi sur pages, galeries et événements (date de parution distincte de la date de tenue pour les événements).
- Wiki : documentation on-site (sommaire en cartes, markdown fiable), recherche & pagination, **diff visuel entre révisions** (comparaison ligne à ligne, moteur LCS maison).
- Médias & galeries : éditeur de métadonnées, recherche/filtre ; recherche/filtre/pagination aussi sur downloads, links, faq, surveys, guestbook.
- Flux **RSS 2.0** (news + articles), boutons de **partage** social, **SEO** (meta description, canonical, Open Graph, Twitter Card, meta par page).

**Communauté & engagement**
- Centre de **notifications** in-site (cloche + non-lus) avec **abonnements** (suivre contenu/catégorie) et triggers (commentaires, forum, @mention, MP, invitations d'événement).
- **Réactions** « j'aime » polymorphes (news/articles/commentaires/forum), **révisions** de contenu (historique + restauration), widget « derniers commentaires ».
- **Corbeille** générique (soft-delete + restauration) sur news, articles, galeries, commentaires, **messages forum**.

**Gaming**
- Événements : CRUD complet des adversaires, compte à rebours live, notification d'invitation.
- Forum : zone **VIP**, image par catégorie ; **menu builder** (constructeur de menus hiérarchiques) intégré au widget navigation.

**Monétisation & gamification**
- Karma/réputation → **points** (barème configurable) → **boutique** (paiement en points) → statut **VIP**.
- **Stripe** (recharge de points + packs VIP), régie **publicitaire** (option sans-pub VIP), dons.

**Carrefours de contribution inter-modules**
- Statistiques agrégées (19 modules), **mur d'activité** cross-module sur le profil, **tableau de bord** « à traiter », et **blocs de page** : injection d'un bloc de module dans une page statique via le shortcode `[block:clé]` (news/articles/downloads).

**Webhooks & audit**
- **Webhooks** sortants signés HMAC (lib + admin CRUD). **Journal d'audit** : installation/désinstallation d'addon, activation thème/module, **sauvegarde de réglages** (`settings.saved`).

**Administration des comptes**
- Édition et suppression d'utilisateurs côté admin, **export des membres CSV/JSON (RGPD)**, réglages de modération côté espace membre.

### Modifié
- Versionnage normalisé en **SemVer pur** dans les dépendances d'addons (retrait des libellés « Alpha »).
- Packaging : `pages` reclassé dans le **core** ; news/forum/gallery désinstallables.
- Éditeur riche fonctionnel (sanitisation au lieu d'échappement).

### Corrigé
- 10 bugs fonctionnels relevés à l'audit ; déduplication des compteurs de vues (anti-gonflage).
- Publication programmée masquée aussi en accès URL direct ; câblages morts réparés (boutons de période des stats, toggles de templates email, réseaux sociaux, routes « Ajouter »).
- Thèmes : lisibilité & contrastes WCAG, login soudé, alignements.
- **Responsive** (tous supports, téléphone→TV) : tiroir mobile + contenu pleine largeur de l'admin, profil membre, userbar compacte des 4 thèmes communautaires.
- `strict_types` : TypeErrors corrigés (timetostr, helpers) ; `(int)` sur `row(FALSE)` d'un COUNT/SUM.

### Sécurité
- Sanitisation HTML serveur anti-XSS stocké (HTMLPurifier), validation d'upload par **magic bytes**, **HSTS**.
- **CSRF** durci (`hash_equals`, IP client secure-by-default), checks de permission **RBAC** manquants ajoutés.
- `unserialize` legacy bornés via `allowed_classes`.
- **Empreinte de session** (anti-détournement) : déconnexion si le user-agent diffère fortement de celui d'origine (tolérant, membres connectés). Endpoint de parution cron gardé par token (`hash_equals`).
