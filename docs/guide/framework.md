# Le framework

Référence des briques que tu manipules en écrivant des addons. Pour l'architecture
interne complète, voir [`docs/architecture.md`](../architecture.md).

## Le service locator — `NeoFrag()`

Tout le framework est accessible via le singleton global `NeoFrag()` et, dans une
classe d'addon, via `$this` (qui y délègue). Les services s'obtiennent par méthodes
magiques :

```php
$this->db        // accès base de données
$this->config    // configuration du site (nf_name, nf_default_theme…)
$this->user      // membre courant
$this->url       // requête / segments / base
$this->lang(...) // traduction
$this->module('forum');         // un module
NeoFrag()->model2('addon');      // un modèle
```

## Routing

Un module déclare ses routes dans `__info().routes` : `motif => méthode`.

```php
'routes' => [
    ''                 => 'index',   // page d'accueil du module
    '{id}/{url_title}' => '_show',    // /module/42/slug
    'admin{pages}'     => 'index',    // admin paginée
],
```

- Placeholders **fixes** : `{id}` (entier), `{key_id}`, `{url_title}` (slug), `{url_title*}`,
  `{page}` et `{pages}` (pagination). Un placeholder inconnu → 404 silencieux.
- Cycle : le **checker** (`controllers/checker.php`) valide la route et charge les
  données ; ce qu'il **retourne** devient les arguments de la méthode homonyme du
  **contrôleur** (`controllers/index.php`).

> En test/curl, `is_crawler` saute la session : envoie un **User-Agent de navigateur**.

## ORM — Model2

Query builder fluide. La table porte le préfixe `nf_`.

```php
$rows = NeoFrag()->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // tableau de lignes ; ->row() pour une seule

$id = NeoFrag()->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // renvoie l'id
NeoFrag()->db->where('id', $id)->update('nf_news', ['title' => $t2]);
NeoFrag()->db->where('id', $id)->delete('nf_news');
```

Pour les entités gérées (addons, fichiers…), passe par les **modèles** :
`NeoFrag()->model2('addon')`, `NeoFrag()->model2('file', $id)->delete()`. Pour itérer un ensemble typé
(p. ex. tous les addons installés), `NeoFrag()->collection('addon')->get()`.

## Formulaires — `form()` & `form2()`

Deux API coexistent — choisis selon le contexte :

- **`form2()`** — fluide, chaque champ est un objet `form_*()`. Idéale pour les formulaires **publics** ou
  **riches**, et c'est la **seule** qui valide un formulaire de **confirmation seule** (sans champ).
- **`form()`** — l'API **historique**, employée par les **écrans d'administration** des modules : champs
  déclarés en tableau via `add_rules([...])`, traitée par `is_valid($post)`, rendue par `->display()`.
  Exemple complet dans [Créer un module](create-a-module.md) (§7 — l'administration).

`form2()` construit, valide (CSRF inclus) et traite un formulaire :

```php
return $this->form2()
    ->rule($this->form_text('title')->title($this->lang('Titre'))->required())
    ->rule($this->form_textarea('body')->title($this->lang('Contenu')))
    ->success(function ($data) {
        NeoFrag()->db->insert('nf_news', $data);
        notify('Enregistré');
    })
    ->submit('Publier');
```

> Un formulaire de **confirmation seule** (sans champ) ne se valide pas avec `form()` :
> utilise `form2`. Le checker reçoit le **vrai titre**, pas le slug.

## Tables — Table2

`table2()` rend des listes paginées, triables et cherchables à partir d'une requête —
idéal pour les écrans d'administration (ex. la liste des membres, `modules/user/controllers/admin.php`).

## Traductions

`$this->lang('Clé')` renvoie la traduction. Les fichiers `langs/fr.php` mappent le
**crc32b de la clé source** vers la traduction :

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // ou directement '467f39a3' => '…'
];
```

`$this->lang('%d élément(s)', $n)` accepte des arguments (style `sprintf`).

## Helpers utiles

- `url('forum/42')` — construit une URL routée (préfixe langue inclus).
- `$this->config->nf_name`, `->nf_default_theme` — config du site.
- `notify('Message', 'success')` — toast.
- `htmlspecialchars(...)` / `sanitize_html(...)` — échappement / nettoyage anti-XSS
  (HTMLPurifier) pour le HTML riche entrant et sortant.

## Migrations

Le schéma évolue par migrations versionnées, suivies dans `nf_migrations` :

```bash
php tools/migrate.php status                 # appliqué / en attente
php tools/migrate.php up [--pretend]          # applique (dry-run avec --pretend)
php tools/migrate.php down [--step=N]         # annule
php tools/migrate.php baseline --until=NAME   # adopter une base existante
```

Fichiers : `migrations/AAAA_MM_JJ_nom.up.sql` (+ `.down.sql` pour la réversibilité).
Le DDL MySQL est auto-commit → **backup avant `up` en prod**.

## Surcharge à trois niveaux

Résolution des vues/classes/fichiers (premier trouvé gagne) :

1. `overrides/{type}/{fichier}` — global
2. `themes/{thème actif}/overrides/{type}/{fichier}` — par thème
3. l'original livré

Tu personnalises sans forker et sans casser les mises à jour.

## Environnement de dev

Stack Docker : `web` (Apache + PHP 8.3), `db` (MariaDB 11), `phpmyadmin`, `mailpit`.

```bash
docker compose up -d                 # http://localhost:8080
docker compose exec web composer test
```

Voir [`docs/development.md`](../development.md) pour le détail (CI, SCSS, déploiement).
