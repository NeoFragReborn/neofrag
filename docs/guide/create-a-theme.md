# Créer un thème

Un **thème** donne au site son identité visuelle complète : la mise en page (navbar,
zones, footer), la charte (couleurs, typographies) et les **dispositions** par défaut
(quels widgets, où).

Nous esquissons un thème `aurora`. Le plus simple pour démarrer est de **cloner un
thème existant** (`themes/nebula/`) puis d'adapter.

## Structure des fichiers

```
themes/aurora/
├── aurora.php                # classe : zones, assets, dispositions
├── views/
│   ├── body.tpl.php          # le squelette de page (rend les zones)
│   ├── userbar.tpl.php       # barre utilisateur (si réutilisée)
│   └── socials.tpl.php
├── css/
│   └── style.css             # la charte (gabarit PHP possible)
├── js/
│   └── aurora.js             # interactions du thème
└── images/
    └── thumbnail.jpg         # vignette pour l'admin
```

## 1. La classe — `aurora.php`

```php
<?php
namespace NF\Themes\Aurora;

use NF\NeoFrag\Addons\Theme;

class Aurora extends Theme
{
    protected function __info()
    {
        return [
            'title'   => 'Aurora',
            'description' => $this->lang('Thème communautaire dark + néon.'),
            'author'  => 'Ton Nom',
            'license' => 'Creative Commons CC BY-NC-SA 4.0',
            'version' => '1.0.0',
            'depends' => ['neofrag' => '0.2.1'],
            'zones'   => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
        ];
    }

    public function __init()
    {
        // Charge les assets de la page (ordre = ordre d'inclusion).
        $this->css('bootstrap.min')
             ->css('icons/fontawesome.min')
             ->css('style')
             ->js('jquery-3.7.1.min')->js('popper.min')->js('bootstrap.min')
             ->js('theme')->js('aurora');
    }

    public function styles_row()    { return $this->view('live_editor/row'); }
    public function styles_widget() { return $this->view('live_editor/widget'); }

    public function install($dispositions = [])
    {
        $dispositions = $this->array();
        // … dispositions par défaut (voir §3) …
        return parent::install($dispositions);
    }
}
```

- `__info().zones` déclare les zones dans l'ordre. Les indices comptent : `zone(0)` =
  *Header*, `zone(2)` = *Contenu*, etc.
- `__init()` enregistre les CSS/JS de chaque page. `->css('style')` charge
  `css/style.css` du thème.

## 2. Le squelette — `views/body.tpl.php`

Le `body.tpl.php` est le HTML de la page. Il **rend les zones** via `$this->output->zone(n)` :

```php
<?php $is_home = ((string) $this->url->request === ''); ?>

<nav class="au-nav">
    <a href="<?php echo url('') ?>"><?php echo htmlspecialchars($this->config->nf_name) ?></a>
    <!-- … menu, connexion … -->
</nav>

<main>
    <?php if ($zone = $this->output->zone(1)): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
    <?php if ($zone = $this->output->zone(2)): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
    <?php if ($zone = $this->output->zone(3)): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
</main>

<footer><!-- … --></footer>
```

> **Détecter l'accueil** : sur la home, `$this->url->segments` vaut `['index']` (pas
> vide). Utilise `((string) $this->url->request === '')`.

Tu es libre : soit tu rends les zones telles quelles (navbar pilotée par les widgets de
la zone *Header*), soit tu **codes une navbar/footer sur mesure** dans le `body.tpl`
(comme les thèmes Vitrine et Nebula) pour une identité unique.

## 3. Les dispositions par défaut — `install()`

`install()` décrit, par motif de page et par zone, la grille de widgets posée à
l'installation du thème :

```php
// Toutes les pages : module principal (8 col) + colonne latérale (4 col).
$dispositions->set('*', 'Contenu', $this->array([
    $this->row(
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'module', 'type' => 'index']))
        )->size('col-md-8'),
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'user', 'type' => 'index']))->style('panel-color')
        )->size('col-md-4')
    )->style('row-default')
]));

// Accueil uniquement : un slider pleine largeur en avant-contenu.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...rows])` : `$page` est un motif (`*`, `/`, `forum/*`…), le plus
  précis l'emporte.
- `$this->widget($id)` reçoit l'**id** d'une ligne `nf_widgets` fraîchement insérée
  (widget + type + réglages sérialisés).
- `->size('col-md-8')` (grille Bootstrap), `->style('row-default' | 'row-dark')` et
  `->style('panel-default' | 'panel-color' | 'panel-header')` stylent lignes et panneaux.

Pour rendre une page **pleine largeur** (sans colonne latérale), ajoute une disposition
plus précise, ex. `set('marketplace*', 'Contenu', [ row(col(widget(module))) ])`.

## 4. La charte — `css/style.css`

Le CSS du thème peut être un **gabarit PHP** (servi et traité par NeoFrag ; le
`?v=` est basé sur le mtime → toute édition invalide le cache automatiquement).

NeoFrag partage des **tokens génériques `--nf-*`** entre tous les CSS de widgets et de
modules. **Chaque thème doit aliaser ces tokens** vers sa charte dans `:root`, sinon les
composants tombent sur des valeurs de repli :

```css
:root {
    --nf-bg:      #070a10;
    --nf-surface: rgba(255, 255, 255, .035);
    --nf-border:  rgba(255, 255, 255, .09);
    --nf-text:    #e7eef6;
    --nf-muted:   #8593a6;
    --nf-accent:  #2dd4bf;   /* la couleur d'accent de TA charte */
}
```

Ainsi un widget tiers (qui écrit `color: var(--nf-accent)`) prend automatiquement la
couleur de ton thème.

## 5. Installer & activer le thème

1. Dépose `themes/aurora/`.
2. **Admin → Thèmes & Addons → Scanner le disque** → coche `aurora` → installe (les
   dispositions par défaut sont créées).
3. Active-le : bouton **Activer** sur sa fiche (définit le thème par défaut), ou via le
   sélecteur de thème en pied de page (cookie `nf_theme`).

Le bouton **Réinstaller par défaut** (fiche du thème) ré-exécute `install()` : utile
après avoir modifié tes dispositions par défaut.

## Bonnes pratiques

- **Dark-only ?** force l'attribut via `js/theme.js`
  (`document.documentElement.setAttribute('data-theme', 'dark')`).
- **Polices** : préfère charger les Google Fonts via `<link>` plutôt qu'un `@import` en
  tête de CSS (un `@import` bloque le rendu et peut provoquer un flash au chargement).
- **Crédite** le projet original (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) si
  tu pars d'un thème existant.
