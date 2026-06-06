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
            'depends'     => ['neofrag' => '0.2.0'],
            'routes'      => [
                ''                 => 'index',     // /notes
                '{url_title}_{id}' => '_show',      // /notes/ma-note_42
            ],
        ];
    }
}
```

### Les routes

Une route mappe un **motif d'URL** vers une **méthode de contrôleur**. Le motif est
relatif au nom du module (`''` = la page d'accueil du module, ici `/notes`).

> **Important** — les placeholders sont un ensemble **fixe** : `{id}` (entier),
> `{url_title}` (slug). Un placeholder inconnu produit un 404 silencieux. Pour une fiche,
> le motif idiomatique est `{url_title}_{id}`.

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

Crée la table dans une **migration** versionnée (`migrations/AAAA_MM_JJ_notes.up.sql`) :

```sql
CREATE TABLE nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Applique-la avec le runner : `php tools/migrate.php up`. (Voir [Le framework](framework.md).)

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

## Installer le module

1. Dépose `modules/notes/` + applique la migration.
2. **Admin → Thèmes & Addons → Scanner le disque** → coche `notes` → installe.
   (Les modules sont décochés par défaut : leur install est plus intrusive — tables,
   permissions, navigation.)

Pour distribuer le module, zippe le dossier (`modules/notes/` à la racine de l'archive).
