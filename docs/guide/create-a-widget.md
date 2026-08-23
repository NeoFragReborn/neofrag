# Créer un widget

Un **widget** est un bloc réutilisable plaçable dans n'importe quelle zone d'un thème.
C'est l'addon le plus simple à écrire : une classe, un contrôleur, une vue.

Nous allons créer un widget `hello` qui affiche un message de bienvenue paramétrable.

## Structure des fichiers

```
widgets/hello/
├── hello.php                 # la classe du widget (métadonnées)
├── controllers/
│   ├── index.php             # le contrôleur (prépare et rend la vue)
│   └── admin.php             # formulaire de réglages (optionnel)
├── views/
│   ├── index.tpl.php         # le gabarit HTML
│   └── admin.tpl.php         # gabarit des réglages (optionnel)
└── css/
    └── hello.css             # styles (optionnel)
```

## 1. La classe — `hello.php`

```php
<?php
namespace NF\Widgets\Hello;

use NF\NeoFrag\Addons\Widget;

class Hello extends Widget
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Bienvenue'),
            'description' => 'Affiche un message de bienvenue personnalisable.',
            'icon'        => 'fas fa-hand-spock',
            'author'      => 'Ton Nom',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.0.0'],
        ];
    }
}
```

- `namespace` **doit** suivre le dossier : `NF\Widgets\<Name>`.
- `__info()` renvoie les métadonnées. `version` et `depends.neofrag` sont obligatoires
  (l'installeur les vérifie).
- Les **réglages** d'un widget configurable ne se déclarent **pas** dans cette classe, mais dans un
  contrôleur dédié `controllers/admin.php` (voir l'étape « Réglages » plus bas). Sans lui, le widget
  n'a pas de réglages.

## 2. Le contrôleur — `controllers/index.php`

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
    public function index($settings = [])
    {
        return $this->css('hello')->view('index', [
            'message' => $settings['message'] ?? $this->lang('Bienvenue !'),
        ]);
    }
}
```

Le contrôleur reçoit les `$settings` saisis par l'admin, prépare les données, charge
son CSS et rend sa vue. Le chaînage `->css('hello')->view('index', $data)` est le
pattern standard.

## 3. La vue — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo htmlspecialchars($message) ?></span>
</div>
```

Les variables passées à `view()` sont disponibles directement (`$message`). **Échappe
toujours** les données affichées (`htmlspecialchars`).

## 4. Le CSS — `css/hello.css` (optionnel)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; }
.hello-widget i { color: var(--nf-accent, #2dd4bf); }
```

> Pour rester cohérent avec tous les thèmes, utilise les tokens partagés `--nf-*`
> (avec une valeur de repli). Chaque thème les redéfinit selon sa charte. Voir
> [Créer un thème](create-a-theme.md).

## 5. Les réglages — `controllers/admin.php` (optionnel)

Pour qu'un widget soit **configurable**, ajoute un contrôleur `controllers/admin.php` : il rend le
formulaire de réglages, et ses champs alimentent les `$settings` que reçoit le contrôleur `index`. La
classe étend `Controller` (pas `Widget`) et expose `index($settings = [])` :

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
    public function index($settings = [])
    {
        return $this->view('admin', [
            'message' => $settings['message'] ?? $this->lang('Bienvenue sur le site !'),
        ]);
    }
}
```

La vue `views/admin.tpl.php` rend les champs ; chaque champ doit être nommé `settings[<clé>]` pour que
sa valeur revienne dans `$settings['<clé>']` :

```php
<div class="form-group row">
    <label for="settings-message" class="col-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-7">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo htmlspecialchars($message) ?>">
    </div>
</div>
```

## 6. Valider les réglages — `controllers/checker.php` (optionnel)

Pour un widget à **plusieurs réglages**, plutôt que de semer des `?? défaut` dans le contrôleur, ajoute
un **checker** : sa méthode `index($settings)` reçoit les réglages **bruts** et **retourne** un tableau
**validé et complété de ses défauts**. Ce tableau devient les `$settings` du contrôleur `index` — qui peut
alors leur faire confiance (valeurs bornées, jamais d'entrée brute affichée).

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        return [
            'message' => isset($settings['message']) && $settings['message'] !== ''
                       ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'] ?? '', ['left', 'center', 'right'], TRUE)
                       ? $settings['align'] : 'left',
        ];
    }
}
```

C'est le pattern du widget `about` (`widgets/about/controllers/checker.php`) : chaque réglage est borné à
des valeurs connues, avec un défaut sûr.

## 7. Installer & placer le widget

1. Dépose le dossier `widgets/hello/` sur ton site.
2. Va dans **Admin → Thèmes & Addons → Scanner le disque**, coche `hello`, installe.
3. Place-le dans une zone via le **Live Editor**, configure son message, enregistre.

## Aller plus loin

- Un widget peut exposer **plusieurs types** (méthodes du contrôleur autres que
  `index`) : `news` propose `index`, `categories`… Le type se choisit à la pose.
- Pour un widget distribuable, ajoute un `README` et une capture, puis zippe le dossier
  (`widgets/hello/` à la racine de l'archive) : il s'installera via **Ajouter** (ZIP).
- Besoin de données ? Lis-les avec l'ORM (`NeoFrag()->db->select(...)`). Voir
  [Le framework](framework.md).
