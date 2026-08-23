# Gamification, boutique & monétisation — NeoFrag Reborn 1.0.0

Pile **engagement → monnaie → boutique → VIP → argent réel**, construite le 2026-06-03. Toute la boucle
fonctionne **en points internes** ; l'argent réel (Stripe) est branché mais reste à activer (clés + revue
sécu). Vérifié par le [harness de tests d'intégration](#tests-dintégration) (102 tests / 407 assertions).

## Vue d'ensemble

```
   ACTIVITÉ                 STATUT (non dépensable)        MONNAIE (dépensable)
   commenter, poster,  ┌──► KARMA / réputation             POINTS  ───┐
   réagir, publier,    │    (paliers, badges, droits)      (solde)     │
   se connecter ───────┤                                               ▼
                       └──► POINTS (gain, anti-farm) ───────────►  BOUTIQUE
                                                                  (dépense points)
                                                       ┌────────────┼────────────┐
                                                    cosmétiques  grades/badges   VIP
                                                                              (durée + perks)
   Stripe (argent réel) ──recharge points / packs VIP──► crédit via gamification
   VIP / perk no_ads ──masque──► Régie pub
```

**Karma ≠ points** : le karma est une **réputation non dépensable** (prestige, paliers, déblocage de
droits) ; les points sont une **monnaie** gagnée puis dépensée. Les deux dérivent de l'activité.

**Packaging** : 2 modules couplés-mais-distincts — `gamification` (karma + points + VIP) et `shop`
(catalogue découplé, *merch-ready*) — plus `ads` (régie pub) et `payments` (rail Stripe).

## Module `gamification`

`modules/gamification/` — socle karma + points + VIP. API publique appelable via
`$this->module('gamification')->…`.

### Karma (réputation)
- Table `nf_karma` (cache par membre : `score`, `reactions_received`, `content_count`, `updated_at`).
- Score **dérivé** et recalculé : *réactions reçues × N + contenu publié × N + ancienneté × N/mois* (plafonnée).
- Recalcul **lazy** (si périmé > 24 h) et **immédiat** au toggle d'une réaction.
- Paliers : Novice → Bronze → Argent → Or → Platine → Diamant (badge coloré sur le profil).
- API : `get($uid)`, `recompute($uid)`, `tier($score)`, `badge($uid)`, `content_owner($type,$id)`, `on_reaction()`.

### Points (monnaie)
- Tables `nf_user_points` (solde `total`/`earned`/`spent`) + `nf_points_log` (transactions, audit + plafonds).
- API : `get_points($uid)`, `add_points($uid,$amount,$type,$reason)`, `earn($uid,$action)` (plafonné/jour),
  `spend_points($uid,$amount,$reason)` (refus si solde insuffisant), `daily_presence()`.
- **Anti-farm** : plafond quotidien par action (somme des gains positifs du jour).
- **Hooks de gain** (pattern « appel direct » car les listeners cross-module sont lazy) :
  - commentaire posté → `modules/comments/comments.php`
  - réaction donnée / reçue → `modules/reactions/controllers/ajax.php`
  - sujet / message forum → listeners dans `modules/forum/forum.php::__init` (forum chargé pendant l'action)
  - news/article publié → `modules/news/controllers/admin.php`
  - présence quotidienne → `daily_presence()` (lazy, plafonné 1/jour)

### VIP (statut premium à durée)
- Table `nf_vip` (`expires_at`, `source`) — expiration vérifiée **en lazy** (pas de cron).
- API : `grant_vip($uid,$days,$source)` (cumule si déjà VIP), `is_vip($uid)`, `vip_expires($uid)`,
  `vip_days_left($uid)`, `vip_badge($uid)`.
- Octroyé par la boutique (item type `vip`) ou Stripe. Perks branchables sur `is_vip()` (zone forum, sans-pub…).

### Barème configurable
Admin → **Gamification**. Tous les gains/plafonds/poids sont en config (`nf_settings`), lus avec valeurs par
défaut (`cfg()`). Clés : `gam_karma_{reaction,content,seniority}`, `gam_pt_{action}`, `gam_cap_{action}`.

| Action | Points (défaut) | Plafond/jour |
|---|---|---|
| Commentaire | 5 | 50 |
| Message forum | 3 | 60 |
| Sujet forum | 10 | 30 |
| Réaction reçue | 2 | illimité |
| Réaction donnée | 1 | 20 |
| News/article publié | 20 | illimité |
| Connexion quotidienne | 5 | 5 (= 1×/jour) |

## Module `shop`

`modules/shop/` — boutique découplée (peut vendre des biens virtuels **et** du merch ; paiement
**enfichable** : points maintenant, argent réel plus tard).
- Tables `nf_shop_items` (catalogue : titre, **prix en points**, `type`, `payload`, stock, `unique_per_user`,
  actif, position) + `nf_shop_purchases` (inventaire).
- Page publique `/shop` (grille thémée, états possédé / points insuffisants / connexion). Achat AJAX
  `/shop/ajax/buy/{id}` : valide → débite via `gamification->spend_points` → enregistre → applique l'effet.
- **Types d'items** : `group` (grade → assigne `nf_users_groups`, badge profil) · `vip` (→ `grant_vip`) ·
  `cosmetic` / `perk` / `merch` (possession enregistrée, consommée ailleurs : régie pub, profil, fulfilment).
- API : `items()`, `item($id)`, `owns($uid,$id)`, `owned_ids($uid)`, `has_perk($uid,$key)` (ex. `no_ads`).
- Admin : CRUD du catalogue.

## Module `ads` (régie publicitaire)

`modules/ads/` + widget `widgets/ads/` (plaçable dans toute zone via le Live Editor).
- Table `nf_ads` (bannière image ou bloc HTML/AdSense, `placement`, dates de diffusion, position,
  compteurs vues/clics).
- `render($placement)` : choisit une annonce active dans sa fenêtre de dates → **masquée si** le membre a
  le perk boutique `no_ads` **ou** est VIP (`is_vip`). Tracking de clic via `/ads/click/{id}`.
- Admin : CRUD des annonces. Rend effectifs le « Pass Sans-Pub » et le VIP.

## Zone forum VIP

Colonne `nf_forum_categories.vip_only` + case « Réservé aux membres VIP » à la création/édition d'une
catégorie (admin forum). Gate `_vip_locked()` dans `modules/forum/models/forum.php` : appliqué aux 3
chokepoints de lecture (`check_forum`/`check_topic`/`check_message` → inaccessible) et au filtrage des
listings (`get_categories`/`get_forums_tree` → masquée). Les **administrateurs voient tout**.

## Module `payments` (Stripe — argent réel)

`modules/payments/` — rail monétaire pour la **recharge de points** et les **packs VIP**.
- Tables `nf_payment_packs` (catalogue : `points` ou `vip`, prix, devise) + `nf_payments`
  (journal + **idempotence** via `event_id` unique).
- Flux : `/payments` → **Stripe Checkout Session** (API cURL, métadonnées `user_id`/`kind`/`units`) →
  redirection paiement → **webhook** `checkout.session.completed`.
- **Webhook sécurisé** (`/payments/webhook`) : vérification **signature HMAC-SHA256 horodatée**
  (anti-rejeu, tolérance 5 min, `hash_equals`) + **idempotence** (clé unique `event_id`) avant de créditer
  via `gamification->add_points` / `grant_vip`.
- **Inerte sans clés** : `is_configured()` (⚠ ne PAS nommer `is_enabled()` — réservé au routage des addons,
  cf. `output.php`). Boutons « Indisponible », webhook → 400.
- Admin : réglages Stripe (clés publique/secrète, secret de signature webhook) + CRUD packs.

### Activer Stripe (à faire, hors code)
1. Admin → **Paiements** → coller les clés Stripe (commencer en **mode test**) + activer.
2. Stripe Dashboard → créer un webhook sur `https://<site>/payments/webhook`, événement
   `checkout.session.completed` → coller son **secret de signature** dans l'admin.
3. ⚠ **Revue sécu recommandée avant prod** (manipulation d'argent réel). Non testable E2E en dev local
   (pas de clés ni d'URL de webhook publique).

## Tests d'intégration

`tests/Integration/` — socle `IntegrationTestCase` (base de données réelle, **transaction rollback par
test** → isolation sans DDL ; connexion via `NF_TEST_DB_*`, skip propre si injoignable).

```bash
docker compose exec web composer test:integration   # suite intégration
docker compose exec web composer test                # tout (unit + intégration)
```

Couvre la logique money-critique : math du solde de points, fenêtre du plafond quotidien, expiration VIP,
ownership boutique, perk `no_ads`, accès zone forum VIP, **idempotence du webhook**, et **cascades FK**
(suppression d'un membre → karma/points/log/vip/achats supprimés). **102 tests / 407 assertions au vert.**

## Thèmes publics

Le thème `dungeon` a été retiré. Trois thèmes publics, chacun avec une identité distincte et un mode
jour/nuit : **Blockcraft** (vert herbe, « blocs »), **Granite** (teal/ardoise, Oswald), **Forge** (rouge
lave, nuit par défaut, lueur de braise). Réseaux sociaux **globaux** (`nf_social_*`, partagés par tous les
thèmes). Installation d'un addon déposé sur disque : admin → Addons → **« Scanner le disque »** (ou ZIP) ;
suppression d'un thème via l'action **« Supprimer »**.
