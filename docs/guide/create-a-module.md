# Créer un module

Un **module** est une fonctionnalité complète : ses pages publiques, ses routes, son
administration, ses données et ses permissions. C'est l'addon le plus riche.

Nous allons créer un module `notes` qui affiche une liste de notes publiques.

## Structure des fichiers

```
modules/notes/
├── notes.php                 # classe : métadonnées, routes, permissions
├── controllers/
│   ├── checker.php           # charge les données et valide la route
│   ├── index.php             # rend les pages publiques
│   └── admin.php             # interface d'administration (optionnel)
├── install/
│   ├── install.sql           # tables du module (joué à l'installation)
│   └── uninstall.sql         # suppression des tables
├── views/
│   └── index.tpl.php
├── css/
│   └── notes.css             # optionnel
└── langs/
    └── fr.php                # traductions (optionnel)
```

## 1. La classe — `notes.php`

```php
<?php
namespace NF\Modules\Notes;

use NF\NeoFrag\Addons\Module;

class Notes extends Module
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Notes'),
            'description' => 'Petites notes publiques.',
            'icon'        => 'fas fa-note-sticky',
            'author'      => 'Ton Nom',
            'license'     => 'LGPLv3',
            'admin'       => TRUE,          // expose une page d'administration
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.0.0'],
            'routes'      => [
                ''                 => 'index',     // /notes
                '{id}/{url_title}' => '_show',      // /notes/42/ma-note
            ],
        ];
    }
}
```

### Les routes

Une route mappe un **motif d'URL** vers une **méthode de contrôleur**. Le motif est
relatif au nom du module (`''` = la page d'accueil du module, ici `/notes`).

> **Important** — les placeholders sont un ensemble **fixe** : `{id}` (entier),
> `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}` et `{pages}` (pagination). Un
> placeholder inconnu produit un 404 silencieux. Pour une fiche, le motif idiomatique est
> `{id}/{url_title}` (l'`id` valide la fiche, le slug est cosmétique).

## 2. Le checker — `controllers/checker.php`

Le **checker** s'exécute avant le contrôleur : il charge les données et **valide** la
route (renvoyer `FALSE` ou rien déclenche un 404). Ce qu'il **retourne devient les
arguments** de la méthode de même nom du contrôleur.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
    public function index()
    {
        $notes = NeoFrag()->db->select('id', 'title', 'body')
                              ->from('nf_notes')
                              ->order_by('id DESC')
                              ->get();

        return [$notes];   // → index($notes)
    }
}
```

## 3. Le contrôleur public — `controllers/index.php`

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
    public function index($notes)
    {
        $this->title($this->lang('Notes'))
             ->icon('fas fa-note-sticky')
             ->breadcrumb();

        return $this->css('notes')->view('index', ['notes' => $notes]);
    }
}
```

`title()`, `icon()`, `breadcrumb()` renseignent l'en-tête de page. Le contrôleur rend
ensuite sa vue (ou un panneau via `$this->panel()->title()->body($html)` pour du HTML
construit en PHP).

## 4. La vue — `views/index.tpl.php`

```php
<div class="notes">
    <?php if (empty($notes)): ?>
        <div class="alert alert-info"><?php echo $this->lang('Aucune note.') ?></div>
    <?php else: foreach ($notes as $n): ?>
        <article class="note">
            <h3><?php echo htmlspecialchars($n['title']) ?></h3>
            <p><?php echo htmlspecialchars($n['body']) ?></p>
        </article>
    <?php endforeach; endif ?>
</div>
```

## 5. Les données

Un module **livre ses tables** dans `modules/notes/install/install.sql` (et leur suppression dans
`install/uninstall.sql`). Ce SQL est joué **automatiquement à l'installation du module** (scan admin,
reset, ou install d'un ZIP marketplace), idempotent grâce à `CREATE TABLE IF NOT EXISTS` :

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Pas de tables propres ? Pas de `install.sql`.** Un module qui réutilise des tables du cœur (par
> exemple `nf_file` via `model2('file')`, comme les modules `files` ou `menu`) n'a **ni `install.sql` ni
> `uninstall.sql`** — il s'installe sans rien toucher au schéma. Ne livre du SQL que pour **tes propres** tables.

Le dossier `migrations/` **à la racine** du projet est réservé aux **évolutions transverses du cœur**
(multi-modules). `php tools/extract-module-sql.php` (re)génère les `install.sql` par module à partir de
la base de dev. (Voir [Le framework](framework.md).)

### Faire évoluer le schéma entre deux versions — migrations par-addon

`install.sql` est **idempotent** : il crée les tables manquantes mais ne **modifie pas** une table déjà
présente chez les utilisateurs. Pour un changement de schéma (ajouter/renommer/supprimer une colonne,
migrer des données) entre deux versions de ton module, ajoute un fichier de **migration** :

```
modules/notes/install/migrations/2026_07_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Nom = **préfixe daté** (`YYYY_MM_DD_description`) → exécution dans l'ordre.
- Suivi dans `nf_addon_migrations` : chaque migration ne s'exécute **qu'une seule fois**.
- À l'**install neuve** (`install.sql` porte déjà le schéma à jour), les migrations sont **baselinées**
  (marquées sans être jouées). À la **mise à jour** (admin → marketplace → « Mises à jour »), seules les
  migrations **nouvelles** sont **exécutées** (`Addon::update()`).
- Règle d'or : **n'écris une migration que pour une vraie évolution d'un schéma déjà livré**. Une nouvelle
  table va dans `install.sql` (idempotent), jamais dans une migration.

## 6. Les permissions (optionnel)

```php
public function permissions()
{
    return [
        'default' => ['access' => [[
            'title'  => 'Notes',
            'icon'   => 'fas fa-note-sticky',
            'access' => [
                'manage' => ['title' => $this->lang('Gérer les notes'), 'admin' => TRUE],
            ],
        ]]],
    ];
}
```

Les permissions deviennent éditables dans **Admin → Permissions (matrice)** : tu y
règles, par rôle, qui peut voir le module et qui peut le gérer.

## 7. L'administration (optionnel)

Si `'admin' => TRUE`, ajoute `controllers/admin.php` (classe `Admin extends
Controller_Module`). Sa méthode `index()` est servie sous `/admin/notes` et hérite
automatiquement de la nouvelle interface (sidebar, breadcrumb, actions).

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
    public function index()
    {
        $this->title($this->lang('Notes'))->icon('fas fa-note-sticky');

        // En admin, on utilise l'API form() + add_rules() (celle des modules du cœur).
        $this->form()
             ->add_rules([
                 'title' => ['label' => $this->lang('Titre'),   'type' => 'text',   'rules' => 'required'],
                 'body'  => ['label' => $this->lang('Contenu'), 'type' => 'editor'],
             ])
             ->add_submit($this->lang('Ajouter'));

        if ($this->form()->is_valid($post))
        {
            NeoFrag()->db->insert('nf_notes', ['title' => $post['title'], 'body' => $post['body']]);
            notify($this->lang('Note ajoutée.'));
            redirect('admin/notes');
        }

        return $this->admin_card('fas fa-note-sticky', $this->lang('Notes'), $this->form()->display());
    }
}
```

Les helpers `admin_card()`, `admin_back()` et `admin_empty()` habillent le contenu (carte, bouton retour,
état vide). C'est **`form()`** (avec `add_rules()` / `is_valid()`) qui est l'API de formulaire d'admin
employée par les modules ; `form2()` (cf. [Le framework](framework.md)) est réservé aux formulaires riches
ou de confirmation.

### Actions mutantes : exiger un jeton CSRF

Toute action qui **modifie** quelque chose (suppression, bascule, clôture…) déclenchée par un **lien GET**
ou un **POST écrit à la main** (hors `form()`/`form2()`, qui portent déjà leur propre jeton) doit être
protégée contre la CSRF — `SameSite=Lax` ne suffit pas (un lien piégé passe). Le trait `Admin_Helpers`
(disponible sur tout contrôleur de module) fournit le nécessaire :

```php
// Vue / génération du lien : jeton en query.
$body .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'" data-confirm="…">'.icon('far fa-trash-alt').'</a>';

// Contrôleur : vérifie le jeton AVANT de muter, sinon redirige.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    NeoFrag()->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Pour un POST manuel, passer le jeton en champ caché : `<input type="hidden" name="_" value="'.$this->csrf_token().'">`.

## Installer le module

1. Dépose `modules/notes/`.
2. **Admin → Thèmes & Addons → Scanner le disque** → coche `notes` → installe (l'install
   joue `install/install.sql` : les tables sont créées à ce moment).
   (Les modules sont décochés par défaut : leur install est plus intrusive — tables,
   permissions, navigation.)

Pour distribuer le module, zippe le dossier (`modules/notes/` à la racine de l'archive).
