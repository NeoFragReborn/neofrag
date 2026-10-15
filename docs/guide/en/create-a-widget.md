# Create a widget

A **widget** is a reusable block that can be placed in any zone of a theme, with the Live Editor. It is the
simplest addon to write: a class, a controller, a view.

We are going to create a `hello` widget that shows a configurable welcome message. Everything is checked
against the code; `widgets/html` and `widgets/about` are good real examples.

## File structure

```
widgets/hello/
├── hello.php                 # the widget class (metadata, declarations, types)
├── controllers/
│   ├── index.php             # the controller: prepares and renders the view
│   ├── checker.php           # validates and completes the settings (recommended as soon as there are settings)
│   └── admin.php             # the settings form (optional)
├── views/
│   ├── index.tpl.php         # the HTML template
│   └── admin.tpl.php         # settings template (optional)
├── css/
│   └── hello.css             # styles (optional)
├── images/
│   └── thumbnail.jpg         # its thumbnail, 960 × 600 — a card: its icon, its name, what it does
└── js/
    └── hello.js              # optional — vanilla, never jQuery
```

## 1. The class — `hello.php`

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
            'description' => $this->lang('Affiche un message de bienvenue personnalisable.'),
            'icon'        => 'fas fa-hand-spock',   // REQUIRED, and must exist in the bundled FontAwesome
            'author'      => 'Your Name',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // the core version it needs at least — without it, “Add” refuses the archive

            // Decoupling declarations — REQUIRED (CI refuses a silent addon).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // e.g. ['teams'] if the widget reads nf_teams

            // Optional: several types = several controller methods, chosen when placing it.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- The `namespace` **must** follow the folder: `NF\Widgets\<Name>`.
- `icon` is required: the Live Editor's add-widget wizard shows widgets as **icon cards**, and
  `tools/check-addon-declarations.php` refuses a name missing from the bundled FontAwesome (icons were renamed
  between version 5 and 6).
- `core`, `presets`, `requires`: same rules as for a module — see [Create a module](create-a-module.md), “The
  three decoupling declarations”.
- A widget that **shows what a module does** — its news, its forum, its gallery — declares that module in
  `requires`, even if it carries its name: without it, or when it is disabled, the widget does not show and the
  Live Editor does not offer it; “Add” refuses to install it alone, and the installer leaves it out of a profile
  that does not have the module. Its `presets` are the module's (`tools/check-addon-declarations.php`, rule 9). A
  widget that lives without the module (it reads core tables) does not declare it, and only keeps what depends on
  it, a link for example, behind `$this->module('x')` and `is_enabled()`.
- **Settings** are not declared in this class but in `controllers/admin.php` (§5).

## 2. The controller — `controllers/index.php`

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
    public function index($settings = [])
    {
        return $this->css('hello')->view('index', [
            'message' => $settings['message'],
        ]);
    }
}
```

The controller receives the `$settings` — **already validated and completed by the checker** (§6) —, prepares
the data, loads its CSS and renders its view. The chain `->css('hello')->view('index', $data)` is the standard
pattern. A widget with several `types` has one method per type.

## 3. The view — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo nf_texte($message) ?></span>
</div>
```

**Always escape** what comes from a setting or from the database (`nf_texte()`). A title written by a
member and set as `innerHTML` on the JS side would become a stored injection, served to everyone.

`nf_texte()` decodes, then escapes: a text the site stored encoded (“`&eacute;`”) displays correctly, where
`htmlspecialchars()` would encode it a second time — the visitor would read “`&eacute;`”. `check-double-codage`
refuses `htmlspecialchars()` in the product.

## 4. The CSS — `css/hello.css` (optional)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**Only use the shared `--nf-*` vocabulary**, the one **every** theme defines: `--nf-bg`, `--nf-bg-elevated`,
`--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`, `--nf-border`, `--nf-border-strong`,
`--nf-text`, `--nf-text-strong`, `--nf-text-soft`, `--nf-text-muted`, `--nf-muted`, `--nf-muted-2`,
`--nf-accent`, `--nf-accent-soft`, `--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`,
`--nf-info`, `--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`, `--nf-font-display`.

A variable a theme did not define would fall back to its fallback — that is how **white blocks** appeared in
dark themes. `tools/check-css-variables.php` refuses any variable used by a widget or module that not every theme
defines. Do not set a fallback value: it would hide the defect instead of revealing it.

Grid: `col-12 col-lg-6`, never `col-6` alone (Bootstrap 5 applies it from 0 px).

## 5. Settings — `controllers/admin.php` (optional)

For a widget to be **configurable**, add `controllers/admin.php`: it renders the settings form, and its fields
feed the `$settings` the `index` controller receives. The class extends `Controller` (not `Widget`):

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
    public function index($settings = [])
    {
        $settings = (array) $settings + ['message' => ''];

        return $this->view('admin', [
            'message' => $settings['message'] ?: $this->lang('Bienvenue sur le site !'),
        ]);
    }
}
```

The view `views/admin.tpl.php` renders the fields; each field is named `settings[<key>]`. Each row carries
`nf-field`, the product's field class: it gives the spacing each theme sets for every form.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo nf_texte($message) ?>">
    </div>
</div>
```

In the Live Editor, the add wizard shows the “Configuration” step **if and only if** this controller renders
something: a widget without settings simply has no `controllers/admin.php`.

## 6. Validating and completing settings — `controllers/checker.php`

**A widget can arrive without settings**: placed by a theme's `install()`, added in the Live Editor before its form
is opened, or restored from an old layout. Its checker must return usable settings even when it is given none.
Twelve widgets did not; one used a missing key as a divisor — division by zero, dead widget.

The `index($settings)` method receives the **raw** settings and **returns** an array **validated and completed
with its defaults**, which becomes the controller's `$settings`:

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // The fallback first: `+` only fills missing keys, the values provided stay intact.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` refuses any `$settings['key']` read that is neither protected on its line
(`isset`, `??`, `empty`, `array_key_exists`) nor covered by this fallback at the head of the method. A complete
real example: `widgets/about/controllers/checker.php`.

## 7. JavaScript in a widget

- Load it with `->js('hello')` in the controller; the file is `js/hello.js`.
- **Vanilla, without jQuery**: `NF.ready(fn)`, `NF.data(el, 'key')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (inserted `<script>` elements are re-run with the right nonce).
  `tools/check-js-sources.php` refuses `$(…)`.
- AJAX addresses are read from the markup (`data-url="…"`) rather than PHP interpolated into the `.js`, which makes
  the file testable as is in the harness (`tests/Browser/*.test.html`).
- A script that crashes before attaching its listeners breaks nothing visible: `tools/check-js-console.php` opens
  the pages in a real browser and sees it.

## 8. Installing, placing, distributing

1. Put `widgets/hello/` on your site.
2. **Administration → Themes & Addons → Scan the disk**, tick `hello`, install.
3. In the **Live Editor**, add it to a zone: the wizard offers the widget, its type, a title, then its
   configuration if it has one. A widget can be placed several times, with different settings.
4. To distribute it: zip the folder (`widgets/hello/` at the root of the archive) — it installs through **Add
   (ZIP)** — or let `tools/package-addons.php` list it in the marketplace catalogue.
5. Its thumbnail: a **card** drawn from its `__info()`, like a module's:
   `php tools/vignettes-cartes.php --nom=hello --sortie=/tmp/cartes` to look at it, then `--poser`. Format and
   checks: [Create a theme, § 5](create-a-theme.md).

`tools/check-widget-contract.php` really calls each widget/type pair to check that none breaks when placed.
