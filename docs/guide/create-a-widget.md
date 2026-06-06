# Créer un widget

Un **widget** est un bloc réutilisable plaçable dans n'importe quelle zone d'un thème.
C'est l'addon le plus simple à écrire : une classe, un contrôleur, une vue.

Nous allons créer un widget `hello` qui affiche un message de bienvenue paramétrable.

## Structure des fichiers

```
widgets/hello/
├── hello.php                 # la classe du widget (métadonnées + réglages)
├── controllers/
│   └── index.php             # le contrôleur (prépare et rend la vue)
├── views/
│   └── index.tpl.php         # le gabarit HTML
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
            'depends'     => ['neofrag' => '0.2.0'],
        ];
    }

    // Réglages éditables par l'admin quand il pose le widget.
    public function settings($settings = [])
    {
        return [
            $this->form_input('message')
                 ->label($this->lang('Message'))
                 ->value($settings['message'] ?? $this->lang('Bienvenue sur le site !')),
        ];
    }
}
```

- `namespace` **doit** suivre le dossier : `NF\Widgets\<Name>`.
- `__info()` renvoie les métadonnées. `version` et `depends.neofrag` sont obligatoires
  (l'installeur les vérifie).
- `settings()` est optionnel : il décrit le formulaire de configuration affiché à
  l'admin. Sans lui, le widget n'a pas de réglages.

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

## 5. Installer & placer le widget

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
