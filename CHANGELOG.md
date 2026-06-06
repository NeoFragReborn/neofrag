# Changelog

Tous les changements notables de **NeoFrag Reborn** sont consignés ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ;
le projet suit le [versionnage sémantique](https://semver.org/lang/fr/).

NeoFrag Reborn est la continuité communautaire de **NeoFrag** (base Alpha 0.2.4), créé à l'origine par
Michaël BILCOT & Jérémy VALENTIN — projet open source sous licence LGPLv3.

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
