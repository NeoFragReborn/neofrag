# Carrefours de contribution inter-modules

> Décision 2026-06-04, **LIVRÉE** : 4 carrefours en place (Stats étendues, Activité profil, Dashboard
> admin, **Blocs de page**). But : que les modules **se détectent les uns les autres et croisent leurs
> données** sans couplage en dur. La gamification déclarative a été **écartée** (cf. §4.4).

## 1. Le principe

Un **carrefour** est un point du CMS qui agrège de la donnée venant de *plusieurs* modules
(statistiques, profil d'un membre, tableau de bord, gamification…). Plutôt que le carrefour
connaisse chaque module, **chaque module se branche** en exposant un contrôleur nommé d'après le
carrefour. Le carrefour scanne tous les modules installés et appelle ceux qui répondent.

```
Carrefour X  ──scanne──▶  pour chaque module : controller('X') existe ?
                                   │ oui
                                   ▼
                         module->controller('X')->X()  ──▶  data agrégée
```

Conséquence : **un nouveau module s'auto-intègre partout** rien qu'en posant ses fichiers de
contribution. C'est la check-list de scaffolding d'un module (cf. §6). C'est aussi la brique qui
rapproche NeoFrag d'un « framework à la WordPress » (hooks), sans bus d'événements lourd.

## 2. Le pattern, déjà éprouvé sur les Stats

Le module `statistics` fait exactement ça aujourd'hui — `modules/statistics/models/statistics.php` :

```php
foreach (NeoFrag()->model2('addon')->get('module') as $module) {
    if ($controller = @$module->controller('statistics')) {
        foreach ($controller->statistics() as $name => $statistic) { /* agrège */ }
    }
}
```

Et un module contributeur (`modules/forum/controllers/statistics.php`) :

```php
class Statistics extends Controller_Module {
    public function statistics() {
        return [
            'topics' => [
                'title' => $this->lang('Nouveaux sujets'),
                'data'  => function(){ $this->db->from('nf_forum_topics t')->join(...); return 'm.date'; }
            ],
        ];
    }
}
```

> **Contrat Stats** : `statistics()` retourne `['clé' => ['title' => string, 'data' => callable,
> 'group_by'? => string]]`. Le `callable` configure une requête sur `$this->db` et **retourne le
> nom de la colonne datetime** à grouper dans le temps. Le carrefour ajoute la couleur et la série.

## 3. Ce qui croise déjà les modules (acquis)

Le pattern « plusieurs modules, un point commun » est déjà présent ailleurs, sous d'autres formes :

| Acquis | Forme | Portée |
|---|---|---|
| Commentaires | polymorphe (`module` + `module_id`) | s'attache à n'importe quel contenu |
| Réactions | multi-cible | commentaires, articles, messages forum |
| Notifications + abonnements | génériques (suivre contenu/catégorie) | tout contenu |
| Corbeille | `Trash::TYPES` | news, articles, gallery, comments, forum |
| Recherche / Feeds RSS | par module | news, articles… |

→ On ne part pas de zéro : on **formalise et étend** un réflexe déjà là.

## 4. Les carrefours

### 4.1 Stats étendues ✅ FAIT

**État** : **19 modules** contribuent via `controllers/statistics.php` (news, articles, gallery,
downloads, reactions, classifieds, surveys, guestbook, links, donations, recruits, bugtracker, talks,
media, events, awards…). Le graphe `/admin/statistics` se remplit tout seul (cases à cocher dynamiques),
sans toucher au module `statistics`.

### 4.2 Profil membre — mur d'activité ✅ FAIT

**But** : sur la fiche d'un membre, agréger son activité issue de tous les modules (ses news, ses
sujets, ses images, ses dons, ses awards, son karma…).

**Carrefour** : `activity` (scan `controllers/activity.php` dans `modules/user/controllers/index.php::_panel_activities`). Contrat :

```php
// modules/news/controllers/activity.php
public function activity($user_id, $limit) {
    // retourne une liste d'items normalisés
    return [
        ['date' => $ts, 'icon' => 'far fa-newspaper', 'type' => 'news',
         'title' => $title, 'url' => $url],
        // …
    ];
}
```

Le module `user` scanne `controller('activity')`, fusionne, trie par date desc, normalise via la vue
`modules/user/views/activity.tpl.php`. **Contributeurs livrés** : `forum`, `news`, `articles`.
Extensible (comments/gallery/downloads à ajouter ; cache si besoin).

### 4.3 Tableau de bord admin contribué ✅ FAIT

**But** : chaque module pose sa carte sur le dashboard (compteurs, éléments en attente), au lieu
d'un dashboard qui connaît tous les modules.

**Carrefour** : `dashboard` (scan `controllers/dashboard.php` dans `modules/admin/controllers/admin.php::_collect_notifications`). Contrat (éléments « à traiter ») :

```php
// modules/bugtracker/controllers/dashboard.php
public function dashboard() {
    return [
        ['title' => $this->lang('Bugs ouverts'), 'action' => $this->lang('Traiter'), 'url' => 'admin/bugtracker'],
    ];
}
```

**Contributeurs livrés** : `bugtracker`, `newsletter` (déplacés du hardcode). Extensible
(moderation/guestbook/comments à ajouter).

### 4.4 Blocs de page (page_blocks) ✅ FAIT

**But** : injecter le contenu d'un module dans une page statique via un shortcode.

**Carrefour** : `block` (scan `controllers/block.php` dans `modules/pages/models/pages.php::block_registry()`).
Le contenu d'une page peut contenir `[block:clé]`, remplacé au rendu par le HTML du bloc. Contrat :

```php
// modules/news/controllers/block.php
public function block() {
    return ['news.latest' => ['title' => $this->lang('Dernières actualités'), 'render' => function(){ /* HTML */ }]];
}
```

**Blocs livrés** : `news.latest`, `articles.latest`, `downloads.popular`. L'éditeur de page liste les
shortcodes disponibles.

### 4.5 Gamification déclarative (écartée)

> **Décision 2026-06-04 : écartée.** Le système de points existe déjà et suffit (`gamification->earn()`
> appelé par comments/forum/news/payments + barème central `POINT_RULES` configurable en admin +
> `OWNER_MAP`). Rendre le barème déclaratif par module = faible valeur / risque cœur points. Conservé
> ci-dessous pour mémoire.

**But** : chaque module déclare les actions qui rapportent des points, au lieu que `gamification`
hardcode « poster une news = +X ».

⚠ **Différence importante** : Stats / Profil / Dashboard sont en **pull** (le carrefour lit quand il
veut). La gamification est **événementielle** (« au moment où l'action se produit »). Le scan de
contrôleurs sert à déclarer le **barème** ; il faut en plus un **point d'émission**. Deux options :

- **A — Helper d'émission** : le module appelle `award_points('news.publish', $user_id)` au moment
  de l'action ; `gamification` détient le barème (`event → points`) déclaré via `controller('gamification')->points()`. Simple, explicite, peu magique.
- **B — Bus d'événements** : `gamification` s'abonne à des événements émis par un dispatcher
  central. Plus puissant, plus lourd ; relève du chantier « NeoPHP / framework ».

**Reco** : option A (barème déclaré + helper d'émission). Contrat de déclaration :

```php
// modules/news/controllers/gamification.php
public function points() {
    return ['news.publish' => ['points' => 10, 'label' => $this->lang('Publier une actualité')]];
}
```

## 5. État d'implémentation

1. **Stats étendues** — ✅ FAIT (19 modules).
2. **Profil membre / activité** — ✅ FAIT (forum/news/articles).
3. **Dashboard contribué** — ✅ FAIT (bugtracker/newsletter).
4. **Blocs de page (page_blocks)** — ✅ FAIT (news/articles/downloads).
5. **Gamification déclarative** — ❌ écartée (système de points existant suffisant).

## 6. Convention de scaffolding (à terme)

Tout nouveau module de contenu fournit, selon pertinence :
`controllers/statistics.php` · `controllers/activity.php` · `controllers/dashboard.php` ·
`controllers/gamification.php` (+ `controllers/search.php` déjà attendu par la recherche).

→ À intégrer au générateur de module / à la doc dev (`docs/guide/`).
