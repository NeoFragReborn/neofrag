# Create a theme

A **theme** gives the site its complete look: the layout (navigation, regions, footer), the style (colours,
typefaces) and the default **layouts** (which widgets, where).

We sketch an `aurora` theme. The simplest way to start is to **clone `themes/nebula/`** (the shipped public
theme) and adapt it. Everything below is checked against the code.

## File structure

```
themes/aurora/
├── aurora.php                # class: zones, regions, assets, layouts
├── views/
│   ├── body.tpl.php          # the page skeleton (renders the regions)
│   └── live_editor/          # row.tpl.php, widget.tpl.php: styles offered in the Live Editor
├── css/
│   ├── style.css             # the style — defines the WHOLE --nf-* vocabulary
│   └── sass/                 # optional: SCSS compiled on the server
├── js/
│   ├── theme.js              # the theme's light/dark mode
│   └── aurora.js             # the theme's interactions
├── images/
│   └── thumbnail.jpg         # 960 × 600 thumbnail: addon card and marketplace
└── install/
    └── migrations/           # changes to the shipped layouts (optional)
```

## 1. The class — `aurora.php`

```php
<?php
namespace NF\Themes\Aurora;

use NF\NeoFrag\Addons\Theme;

class Aurora extends Theme
{
    protected function __info()
    {
        return [
            'title'       => 'Aurora',
            'description' => $this->lang('Thème communautaire sombre et néon.'),
            'author'      => 'Your Name',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // the core version it needs at least — without it, “Add” refuses the archive

            // Decoupling declarations — REQUIRED.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // The zones, in order, by their TITLE (install() uses this title)…
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … and the named REGIONS the templates render. REQUIRED for a public theme.
            'regions'     => [
                'header'         => 'Header',
                'before_content' => 'Avant-contenu',
                'content'        => 'Contenu',
                'after_content'  => 'Post-contenu',
                'footer'         => 'Footer',
            ],
        ];
    }

    public function __init()
    {
        // Each page's assets, in inclusion order. The core's first: Bootstrap 5, the product's base
        // (css/nf-bs5-bridge.css: its components and Bootstrap's colours drawn from the theme's palette),
        // FontAwesome; then the theme's style; then the scripts.
        $this->css('bootstrap.min')->css('nf-bs5-bridge')
             ->css('icons/fontawesome.min')
             ->css('style')
             ->js('bootstrap.bundle.min')
             ->js('modal')->js('notify')->js('confirm')
             ->js('theme')->js('aurora');
    }

    public function styles_row()    { return $this->view('live_editor/row'); }
    public function styles_widget() { return $this->view('live_editor/widget'); }

    public function install($dispositions = [])
    {
        $dispositions = $this->array();
        // … default layouts (see §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` declares the zones in order; `regions` gives them a **stable name** the templates use.
  `tools/check-addon-contracts.php` **requires** the `regions` key on every public theme: it is the foundation of
  the page-builder work, and a template that rendered `zone(2)` would depend on the order.
- **No jQuery**: the core no longer loads it, and `tools/check-js-sources.php` refuses any script that calls it.
  Bootstrap 5 is loaded through `bootstrap.bundle.min` (Popper included).
- `styles_row()` / `styles_widget()` render the lists of row and panel styles offered in the Live Editor; copy
  `themes/nebula/views/live_editor/` to start.

## 2. The skeleton — `views/body.tpl.php`

`body.tpl.php` is the page's HTML. It **renders the regions** by their name:

```php
<nav class="au-nav">
    <a href="<?php echo url('') ?>"><?php echo nf_texte($this->config->nf_name) ?></a>
    <?php echo $this->output->region('header') ?>
</nav>

<main>
    <?php if ($zone = $this->output->region('before_content')): ?><section class="au-banner"><?php echo $zone ?></section><?php endif ?>
    <?php if ($zone = $this->output->region('content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
    <?php if ($zone = $this->output->region('after_content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
</main>

<footer>
    <?php echo $this->output->region('footer') ?>
</footer>
```

The `if ($zone = …)` guard tells the truth: a declared region **with no widget** renders an empty string (since
2026-09-17; before, whitespace made a decorated banner be drawn around nothing). So you can wrap each region in a
styled container safely.

> **Detecting the home page**: `((string) $this->url->request === '')`.

You are free: either render the regions as they are (navigation driven by the widgets of the `header` region), or
**code a custom navigation bar or footer** in the template, like Nebula, for a unique identity. If you write a
footer, **do not also place** a copyright widget in the `footer` region of your layouts: the Extend theme showed
it twice.

If you let visitors change theme, write `<?php echo nf_selecteur_theme() ?>` in your footer: the core renders the
menu of installed themes (except `admin`) and remembers the choice in a cookie of the site's own. It only shows if
there is more than one public theme, and the administrator has not closed the choice (**General Preferences →
Theme choice**).

For the language, `<?php echo nf_selecteur_langue() ?>` likewise renders the menu of the site's languages,
when it has several: a real form, which brings the visitor back to the same page, in the chosen language.

Also write `<?php echo nf_liens_legaux() ?>` in your footer, next to “Powered by”: the core renders the links to
the legal notice and the privacy policy, when their pages are published (**Settings → Privacy**), and **“Manage
my cookies”**, always. That last link is the only way a visitor has to change their choices: withdrawing consent
must be as easy as giving it. If your footer line does not wrap (`white-space: nowrap`), at least let these links
wrap: on a phone, stuck to the rest, they make the page overflow.

### What sticks to the top of the screen

Three banners can occupy the top of the window: the demo's, the maintenance one and the permissions preview's.
The core stacks them, reserves their space at the top of the page and publishes their height in `--nf-haut`. If
your header stays stuck when scrolling, place it below them and mark it `data-nf-entete`: the core measures its
height and publishes it in `--nf-entete`, on which the modules' stuck columns (the member area menu, the wiki's
table of contents) in turn align.

```php
<header class="au-tete" data-nf-entete>…</header>
<aside class="au-cote" data-nf-colle>…</aside>
```

```css
.au-tete { position: sticky; top: var(--nf-haut, 0px); z-index: 1030; }
.au-cote { position: sticky; top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
```

A stuck column that can be taller than the screen carries `data-nf-colle`: when it does not fit in the space left
to it, the core unsticks it — stuck, its bottom would stay off screen until the end of the page. Anchors
(`#section`) stop by themselves below the banners and below your header: do not set `scroll-padding-top`. An
element fixed at the top (`position: fixed`) also aligns on `--nf-haut`; only a panel that deliberately covers the
whole page (a window, a drawer) may start from zero. `check-colles` refuses an element stuck or fixed at the top
that ignores the banners: on the demo, half the header of four themes disappeared under its own as soon as you
scrolled.

## 3. Default layouts — `install()`

`install()` describes, per page pattern and per **zone title**, the grid of widgets placed when the theme is
installed:

```php
// Every page: main module (8 columns) + side column (4 columns).
$dispositions->set('*', 'Contenu', $this->array([
    $this->row(
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'module', 'type' => 'index']))
        )->size('col-12 col-lg-8'),
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'user', 'type' => 'index']))->style('panel-color')
        )->size('col-12 col-lg-4')
    )->style('row-default')
]));

// Home page only: a full-width carousel before the content.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...rows])`: `$page` is a pattern (`*`, `/`, `forum/*`…), the most specific wins.
- `$this->widget($id)` receives the **id** of a freshly inserted `nf_widgets` row (widget, type, settings as
  JSON). A widget placed **without settings** must cope: that is its checker's job.
- `->size('col-12 col-lg-8')` — Bootstrap 5 grid, **always with a breakpoint**: `col-8` alone applies from 0 px
  and crushes the page on phones. `->style('row-default' | 'row-dark')` and
  `->style('panel-default' | 'panel-color' | 'panel-header')` style rows and panels.
- Only place **core** widgets in shipped layouts: the package installs à la carte, an optional widget may not be
  there.
- The **menu labels** you set (the `navigation` widget, `$this->lang('Actualités')`) are translated on display by
  the widget, under its own key: each must exist in `widgets/navigation/langs/*.php`, otherwise it stays in French
  on a site in another language. `check-langs` checks it.

An existing installation never replays `install()`: to fix a shipped layout for those who already have the
theme, write a **theme migration** (`install/migrations/YYYY_MM_DD_name.up.sql`, tracked in
`nf_addon_migrations`, played on update). A real example: `themes/extend/install/migrations/`.

## 4. The style — `css/style.css`

**Each theme defines the whole `--nf-*` vocabulary**, in `:root` (and in its light or dark variant). Module and
widget style sheets only use this vocabulary; a variable a theme forgets falls back to a fallback — **white
blocks** appeared in dark themes this way. `tools/check-css-variables.php` refuses any variable used by a module or
widget that **every** theme does not define. The complete vocabulary — twenty-seven tokens — with **example** values
(Nebula points each one to its private `--fg-*` palette, which lets it have a light and a dark mode by changing
only that palette):

```css
:root {
    /* backgrounds and surfaces */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* lines */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* texts */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* accent — and the colour set ON it */
    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;
    --nf-on-accent: #041014;
    /* states */
    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;
    --nf-danger-soft: rgba(239,68,68,.18);
    /* shapes */
    --nf-radius: 14px;  --nf-radius-sm: 8px;
    --nf-font-display: "Space Grotesk", system-ui, sans-serif;
}
```

Two traps measured on the shipped themes:

- **`--nf-on-accent` must be recomputed when the accent changes.** If your second palette (light mode, a variant)
  replaces `--nf-accent`, also redefine the colour set on it, otherwise button text keeps the other accent's — white
  on turquoise, 1.86:1. `tools/check-contraste.php` measures the WCAG contrast of every theme in both modes.
- **No `.row { margin: -15px }`**: that is Bootstrap 4's gutter; Bootstrap 5 uses −12 px through `--bs-gutter-x`,
  and the 3 px difference makes the page overflow. `tools/check-mise-en-page.php` measures the horizontal overflow
  of every page, from 360 to 2560 px, in every theme and mode; `tools/check-classes-bs4.php` refuses classes that disappeared from Bootstrap **3 and 4**
  (`card-columns`, `float-right`, `panel-body`, `badge-danger`…), in templates as in style sheets — redefining
  `.badge-danger` in your theme does not bring it back, the check refuses it —, `data-*` attributes left without
  the `bs` prefix — a `data-target` silently does nothing any more — and classes the code **builds** by
  concatenation. A theme must use Bootstrap 5 names: `float-end`, `text-start`, `g-0`, `ms-2`,
  `bg-danger-subtle text-danger-emphasis`. To tint these colours to your style, redefine Bootstrap's variables
  (`--bs-danger-text-emphasis`…) rather than the classes.

### Adjustable colours

A theme lets you choose three colours — the accent, the background, the text — through its configuration page,
with a **live preview** on a real page of the site and the **refusal of an unreadable colour**. The core does
everything; the theme declares its original colours in `controllers/admin.php`:

```php
class Admin extends Controller
{
    const COULEURS = ['theme_color' => '#2dd4bf', 'background_color' => '#070a10', 'text_color' => '#e7eef6'];

    public function index()
    {
        return nf_reglage_couleurs($this, $this->__caller, self::COULEURS);
    }
}
```

Its style sheet is then a **PHP template** that reads the `<theme>_theme_color`, `<theme>_background_color` and
`<theme>_text_color` settings, with the same original values as fallbacks:

```php
<?php $accent = $this->config->aurora_theme_color ?: '#2dd4bf'; ?>
:root { --nf-accent: <?php echo $accent ?>; }
```

A theme that already has its configuration page only adds the preview to it, under its pickers:
`nf_apercu_couleurs($this->__caller, [...original colours])`. A real example: `themes/nebula/`.

### The themes' common base — `css/nf-socle-themes.css` (optional)

Blockcraft, Chronique, Extend, Forge, Granite and Pulse rely on a **common base**: what their style sheets had in
common (the reset, links, cards, forms, lists, the member bar, presence dots…), written once in the core, with only
the `--nf-*` vocabulary. A theme adopts it by loading it **before** its own style sheet, which keeps its identity
and can redefine everything:

```php
$this->css('bootstrap.min')->css('nf-bs5-bridge')
     ->css('icons/fontawesome.min')
     ->css('nf-socle-themes')   // the themes' common base
     ->css('style')
     ->css('nf-apres-theme');
```

Besides the tokens above, it uses these, which the theme that loads it defines (the six themes that load it point
them to their private palette) — `tools/check-css-variables.php` refuses a theme that loads the base without
defining each token it uses:

```css
:root {
    --nf-radius-xs: 2px;
    --nf-shadow-sm: 0 1px 3px rgba(0,0,0,.4);  --nf-shadow: 0 6px 20px -8px rgba(0,0,0,.6);  --nf-shadow-md: 0 12px 32px -12px rgba(0,0,0,.7);
    --nf-success-soft: rgba(34,197,94,.12);  --nf-warning-soft: rgba(245,158,11,.12);  --nf-info-soft: rgba(56,189,248,.12);
    --nf-link: var(--nf-accent-text);  --nf-link-hover: var(--nf-accent);
    --nf-font-mono: "JetBrains Mono", ui-monospace, monospace;
    --nf-font: "Inter", system-ui, sans-serif;
}
```

A theme that does not load it — Nebula, or a theme still at an earlier version — has nothing to change. The base
arrived with version 1.2.32 of the core: a theme that loads it only installs on a core that carries it (the
marketplace serves each site the catalogue of its own version).

The CSS can be a **PHP template**; the `?v=` is based on the file's date, any edit invalidates the cache. A
`css/sass/` folder is compiled on the server (scssphp) at installation and from Administration → Tools.

**Light / dark mode.** Each theme manages its own in `js/theme.js` (a `data-theme` attribute on `<html>`, the
preference remembered by the browser under **its own key**, not a shared one). A “dark only” theme forces
`document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. The thumbnail — `images/thumbnail.jpg`

The theme's card in **Themes & Addons** and its page in the marketplace show `images/thumbnail.jpg`: a **960 × 600
JPEG** (16:10), 200 KB at most. It is a **photo of the home page as a visitor sees it**, never a mock-up.
`tools/capturer-apercus.php` takes it on a site where the theme is installed; with `--basculer-theme`, it makes it
the default theme for the time of the photo, then restores the old one. Do it on a test site filled with the demo
content (`install/cli.php --demo`), never on a live site:

```bash
php tools/capturer-apercus.php --type=theme --nom=aurora --basculer-theme
```

`check-vignettes` then refuses an addon without a thumbnail, a thumbnail of the wrong format and two identical
thumbnails; `check-marketplace` refuses a catalogue entry without a preview. The format and the few justified
exemptions live in `tools/lib/vignettes.php`. Modules and widgets follow the same format, but their thumbnail is a
drawn card (`tools/vignettes-cartes.php`), not a photo: a module without data would only show an empty frame,
whereas a theme shows itself.

## 6. Installing, enabling, testing

1. Put `themes/aurora/` in place.
2. **Administration → Themes & Addons → Scan the disk** → tick `aurora` → install.
3. **Enable** it on its card (sets the default theme). If the theme was registered without layouts, enabling runs
   `install()` by itself. The **Reinstall defaults** button runs `install()` again.
4. Before shipping, pass: `check-addon-declarations`, `check-addon-contracts` (the `regions` key),
   `check-css-variables`, `check-colles`, `check-classes-bs4`, `check-js-sources`, then `check-mise-en-page`,
   `check-contraste`, `check-bleu-bootstrap` (Bootstrap's default blue left in a component your palette did not
   take over) and `check-js-console` on an installation where the theme is active. Look at the rendered page: an
   empty banner, a duplicate copyright or an unreadable comment do not show in the code.

## Good practices

- **Fonts**: serve them from the theme or the core, never from Google Fonts — the security policy no longer
  allows any font origin (`font-src 'self' data:`), and such loading sent every visitor's IP address to Google.
  `php tools/polices-locales.php --nom=…` downloads a Google Fonts font into `fonts/` and writes its style sheet
  `css/fonts/<name>.css`, which the theme loads with `->css('fonts/<name>')`.
- **No script from a CDN**: the strict CSP (`script-src 'self' 'nonce-…'`) would refuse it, and the project depends
  on no third party. All JS lives in the theme or the core.
- **Credit** the original project (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) if you start from an
  existing theme.
