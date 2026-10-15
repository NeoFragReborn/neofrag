# Create a module

A **module** is a complete feature: its public pages, its routes, its administration, its data and its
permissions. It is the richest kind of addon.

We are going to create a `notes` module that shows a list of public notes. Everything below is checked against the
code; the shipped modules (`modules/contact`, `modules/news`) are the best examples to read next.

## File structure

```
modules/notes/
├── notes.php                 # class: metadata, declarations, routes, permissions
├── controllers/
│   ├── checker.php           # validates the route and loads the data
│   ├── index.php             # renders the public pages
│   └── admin.php             # administration screen (optional)
├── install/
│   ├── install.sql           # the module's tables (played at installation, idempotent)
│   ├── uninstall.sql         # dropping the tables
│   └── migrations/           # schema changes between two versions (optional)
├── views/
│   └── index.tpl.php
├── css/                      # optional
├── images/
│   └── thumbnail.jpg         # its thumbnail, 960 × 600 — a card: its icon, its name, what it does
├── js/                       # optional — vanilla, never jQuery
└── langs/
    └── fr.php                # translations (optional)
```

## 1. The class — `notes.php`

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
            'description' => $this->lang('Petites notes publiques.'),
            'icon'        => 'fas fa-note-sticky',   // must exist in the bundled FontAwesome
            'author'      => 'Your Name',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // the core version it needs at least — without it, “Add” refuses the archive
            'admin'       => TRUE,                   // exposes an administration page

            // Decoupling declarations — REQUIRED (CI refuses a silent addon).
            'core'        => FALSE,                  // TRUE = always shipped and cannot be uninstalled
            'presets'     => ['communaute'],         // installation profiles that pre-tick it
            'requires'    => [],                     // addons it NEEDS (without them, it breaks)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/my-note
            ],
        ];
    }
}
```

### The three decoupling declarations

Since 2026-09-15, the package installs **à la carte**: the installer offers profiles (*Full*, *Gaming / eSport*,
*Community*, *Association / club*, *Core only*) built **from these declarations**. No list is written by hand
anywhere else.

- `core` — `TRUE` for an infrastructure or CMS module that is always shipped and cannot be uninstalled; `FALSE` for
  anything optional. A core module can **never** depend on an optional one (rule 3 of
  `tools/check-addon-declarations.php`).
- `presets` — the profiles that pre-tick it: `'gaming'`, `'communaute'`, `'association'` (the labels of
  `install/lib/presets.php`). Empty: it only appears in the *Full* profile.
- `requires` — the addons it needs **so as not to break**: a table it reads, a class it names. The installer adds
  them automatically when your module is ticked. A **soft** dependency (a service that may be missing) is not
  declared here: it is **guarded** in the code (see §5).

These three keys are checked statically in CI, as is the existence of the declared icon (rule 7: FontAwesome
renamed icons between 5 and 6). An addon you do not want in the marketplace catalogue adds
`'distributed' => FALSE`.

### Routes

A route maps a **URL pattern** to a **controller method**. The pattern is relative to the module's name (`''` = the
module's home page, here `/notes`). Administration routes start with `admin` (`'admin{pages}' => 'index'` for a
paginated list under `/admin/notes`).

> Placeholders are a **fixed** set: `{id}` (integer), `{key_id}`, `{url_title}` (slug), `{url_title*}` (slug with
> `/`), `{page}` and `{pages}` (pagination). An unknown placeholder produces a silent 404. For a detail page, the
> idiomatic pattern is `{id}/{url_title}`: the `id` identifies the item, the slug is cosmetic.

## 2. The checker — `controllers/checker.php`

The **checker** runs before the controller: it validates the route and loads the data. Returning `FALSE` or
nothing triggers a 404. What it **returns becomes the arguments** of the controller method of the same name.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
    public function index()
    {
        $notes = $this->db->select('id', 'title', 'body')
                          ->from('nf_notes')
                          ->order_by('id DESC')
                          ->get();

        return [$notes];   // → index($notes)
    }

    public function _show($id, $url_title)
    {
        if ($note = $this->db->from('nf_notes')->where('id', $id)->row())
        {
            return [$note];   // → _show($note)
        }
        // nothing: 404
    }
}
```

For an entry point that receives a **POST** (AJAX, a hand-written form), the checker reads the fields with
`post_check('titre', 'corps', 'options?')`: each name is **required**, unless it carries the `?` suffix, in which
case it is `NULL` when missing. A missing required field makes the checker fail: a **404** response in production
(the exact reason — which field, what came instead — is **logged**) and **400 with the reason** when debugging is
visible (a logged-in administrator, the debug tool on). This behaviour comes from ten widgets that could no longer
be added in the Live Editor because an optional field was required.

## 3. The public controller — `controllers/index.php`

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

`title()`, `icon()`, `breadcrumb()` fill in the page header. The controller then renders its view, or a panel through
`$this->panel()->title()->body($html)` for HTML built in PHP.

> **Never pass database content through `$this->title()` and then through translation.** A title written by the
> administrator has no translation: `lang()` looks for it in vain and logs a warning at every visit in another
> language. Wrap what comes from the database in `$this->no_translate(...)`.

## 4. The view — `views/index.tpl.php`

```php
<div class="notes">
    <?php if (empty($notes)): ?>
        <div class="alert alert-info"><?php echo $this->lang('Aucune note.') ?></div>
    <?php else: foreach ($notes as $n): ?>
        <article class="note">
            <h3><?php echo nf_texte($n['title']) ?></h3>
            <p><?php echo nf_texte($n['body']) ?></p>
        </article>
    <?php endforeach; endif ?>
</div>
```

The variables passed to `view()` are available directly. **Always escape** what comes from the database
(`nf_texte()`); for rich HTML typed by a member, `sanitize_html()` (HTMLPurifier). An **address** typed by
someone that ends up in an `href` also goes through `nf_url_sure()`, which refuses `javascript:` and the other
executable schemes (see [The framework](framework.md#security-what-already-exists)).

**The front end is Bootstrap 5, without jQuery, under a strict CSP.**
- Grid classes are written `col-12 col-lg-8`, never `col-8` alone (which applies from 0 px and breaks phones);
  `tools/check-classes-bs4.php` refuses Bootstrap 4 classes that disappeared.
- An inline `<script>` in a view automatically receives the response's **nonce** (the `index.php` filter); a JS
  file is loaded with `->js('name')` (the module's `js/` folder).
- Write JavaScript with the core's primitives — `NF.ready`, `NF.data(el, 'key')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`, `NF.replaceHtml`
  — never `$(…)`: jQuery is no longer loaded, and `tools/check-js-sources.php` refuses any call. The addresses the
  script needs are set as `data-*` on the markup rather than as PHP interpolated into the `.js`.

## 5. Data

A module **ships its tables** in `install/install.sql` (and their removal in `uninstall.sql`). This SQL is played
**when the module is installed** (installer, administration scan, marketplace ZIP), idempotent thanks to
`CREATE TABLE IF NOT EXISTS`:

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Content that is translated** (a title, a description) lives in a `<table>_lang` table, one row per language,
> read with `join_lang()` and written with `ecrire_lang()`:
> [The framework](framework.md#translated-content-join_lang-and-ecrire_lang).

> **No tables of your own? No `install.sql`.** A module that reuses core tables (for example `nf_file` through
> `model2('file')`) installs without touching the schema.

### Evolving the schema — per-addon migrations

`install.sql` creates missing tables but does not **change** a table already present on users' sites. For a schema
change between two versions of your module:

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Name = **dated prefix** (`YYYY_MM_DD_description`), run in order.
- Tracked in `nf_addon_migrations`: each migration runs **only once**.
- On a fresh installation (`install.sql` already carries the up-to-date schema), migrations are **baselined** — marked
  without being run. On update (marketplace → “Updates”, or an archive sent with “Add”), only the new ones are **run**
  (`Addon::update()`). A module shipped with the core receives its own on the first visit with the new code
  (`nf_migrations_du_code()`, see [the framework](framework.md#migrations)).
- Golden rule: a migration for a real change to a schema **already shipped**; a new table goes into `install.sql`, never
  into a migration — **unless** the module is already installed on sites: then the new table goes into both, `CREATE
  TABLE IF NOT EXISTS` in the migration.
- An update from the marketplace or by an archive replays `install.sql` **before** the migrations: a site that skips
  versions receives the tables of the latest one, then runs the older migrations on them. A migration that writes data
  therefore targets the schema of the latest `install.sql`, and is rewritten when it changes — a site that already ran
  it recognises it by its name and does not run it again. For the same reason, never rename a migration.
- On the demo site, an addon update launched from the administration plays nothing (`Addon::migrate()` exits when
  `nf_demo()` is true); migrations arriving with new code apply there as elsewhere.

The `migrations/` folder **at the project root** is reserved for the core's cross-cutting changes.

### Depending on another module without breaking

The package installs à la carte: the module you need may **not be there**. Four ways to depend, and what happens if
it is missing (`tools/check-addon-coupling.php` detects them with the tokenizer):

| Means | If the addon is missing | What to do |
|---|---|---|
| reading **its table** (`from('nf_teams')`) | **fatal** — “Table doesn't exist” | declare it in `requires`, or guard with `$this->db->table_exists('nf_teams')` and **annotate** |
| naming **its class** | **fatal** — “Class not found” | same |
| calling **its service** (`$this->module('teams')`, `model2('team')`) | tolerant: returns `NULL` | test the return: `if ($teams = $this->module('teams'))` |
| writing **its URL** (`url('teams/…')`) | cosmetic: a dead link | acceptable |

A fatal coupling **neither declared nor annotated fails CI**. The annotation goes at the exact spot, as a comment:
`// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, or at the top of the file
`couplage(teams): …` when the references are spread out.

### Content types: reactions, subscriptions, revisions, trash

If your module publishes **content** (like news, articles, topics), declare it: the cross-cutting modules —
reactions, notifications, revisions, trash, gamification — collect it instead of each carrying their own list of
tables.

```php
public function declare_content_types()
{
    return [
        'note' => [
            'table' => 'nf_notes', 'pk' => 'id', 'author' => 'user_id',
            'reactable' => TRUE, 'subscribable' => TRUE, 'revisable' => FALSE,
        ],
    ];
}
```

A real example: `modules/news/news.php`.

### A content's search engine settings

A content type declared above (with its `table` and `pk`) can receive, like news or the wiki, a title and a
description for search engines — the shared page is the Search engines section's, nothing to write on that side.
Three calls:

```php
// The administration's edit card: the button that leads to the shared page.
return $this->admin_card('fas fa-edit', $titre, $formulaire, '', nf_seo_bouton('note', (int) $id));

// The public page's action, before rendering the view: applies the title and description entered.
nf_seo_contenu('note', (int) $id);

// When a content's address changes (a name, a slug), the old one leads to the new one (301).
nf_redirection_ajouter('notes/'.$ancien_slug, 'notes/'.$nouveau_slug);
```

Real examples: `modules/wiki/controllers/admin.php` and `modules/wiki/controllers/index.php`.

### Hubs: statistics, activity, dashboard, search, sitemap

A module plugs into a page that **aggregates** by providing a controller named after the hub. The hub calls the
method of the same name **without checking anything**: renamed, made private or given one more required parameter,
the error only appears when the page is opened — which is why `tools/check-addon-contracts.php` freezes these
contracts in CI.

| Controller | Expected method | Called by |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | the administration's Statistics page |
| `controllers/activity.php` | `activity($user_id, $limit)` | a member's profile |
| `controllers/dashboard.php` | `dashboard()` | the administration dashboard |
| `controllers/block.php` | `block()` | pages' `[block:…]` blocks |
| `controllers/search.php` | `search()` **and** `suggest()` | the global search and the instant suggestion — a module that only has `search()` is **silently ignored** |
| `controllers/sitemap.php` | `sitemap()` | the sitemap (`/sitemap.xml`, one per language): returns `[['adresse' => 'mymodule/12/title', 'date' => …], …]`, paths like those `url()` takes, only what a **visitor** can read (`$this->access('mymodule', 'read', $id, 'visitors')`) and what exists **in the sitemap's language** — content that has **no language of its own** (a topic, a ticket, a page made of such content) carries `'sans_langue' => TRUE`: it only appears in the sitemap of the site's first language, and its page calls `nf_seo_sans_langue()`, which puts its canonical there (six languages of the same text, each canonical: Google keeps another one and indexes none of them); **an empty section returns `[]`**: its page would only have “nothing yet” to show; without the hub, the module is absent from search engines, and IndexNow does not report its pages: the scheduled task compares this same sitemap from one run to the next |

### The member area and the public profile

Three methods of the module's **class**, optional, and only read if they exist:

- **`espace_membre($user)`** adds pages to the **member area menu** — the left column on a computer, the tab strip
  on a phone, also repeated in the themes' top bar and the “Member area” widget. It returns a list of entries:
  `url`, `titre`, `icone`, if needed `badge` (a number to signal) and `compact` (also shown in short menus), and
  `ordre` to sort them.
- **`profil_membre($membre)`** adds **tabs to a member's public profile**, each at its address
  `user/<id>/<username>/<tab>`. It returns a list of tabs: `onglet` (the address segment: lowercase letters, digits
  and hyphens), `titre`, `icone`, `ordre`, if needed `nombre` (shown next to the title and in “In figures”), and
  `contenu`, a function that renders the tab's page — only called when it is opened. **Nothing to show, nothing to
  return**: an empty tab does not appear, and its address answers 404.
- **`types_de_notification()`** declares the notifications the module sends, for each member's **preferences**
  (`user/notifications/preferences`): `type` (the one `push()` receives), `titre` (what the member reads, “A reply in
  a topic I follow”), `email` set to `TRUE` if the module can also send it by e-mail, and `ordre`. **A sent type must
  be declared**: otherwise the member cannot turn it off — `tests/Unit/TypesDeNotificationTest.php` reads the
  repository's sends and refuses it.

```php
public function profil_membre($membre): array
{
    $notes = $this->db->from('nf_notes')->where('user_id', (int) $membre->id)->count();

    return $notes ? [[
        'onglet'  => 'notes',
        'titre'   => (string) $this->lang('Notes'),
        'icone'   => 'fas fa-note-sticky',
        'ordre'   => 50,
        'nombre'  => $notes,
        'contenu' => fn () => $this->view('profil-membre', ['notes' => $this->model()->du_membre((int) $membre->id)]),
    ]] : [];
}
```

The profile shows what **whoever is looking** can read: a tab that lists content filters like its own pages
(`$this->access(…)`) — the forum only shows readable categories there. What the member chooses to hide about
themselves (points, karma, VIP, age, online presence) is asked from their model: `$membre->montre('statut')`. Real
examples: `modules/forum/forum.php`, `modules/teams/teams.php`.

A notification is sent through `push($user_id, $type, $titre, $adresse, $acteur)` of the `notifications` module
(or `push_unique()`, which does not add a second one of the same type to the same address while the first is
unread): what the member turned off **on the site** is not written. A module that also sends it by e-mail first asks
the question itself, `$notifications->veut($user_id, $type, 'email')`, and does not write if the answer is no. From
the module's class, load it with `\NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])` —
`$this->module()` gets the addon type wrong there. A real example: `modules/talks/talks.php`.

## 6. Permissions (optional)

```php
public function permissions()
{
    return [
        'default' => [
            'access' => [
                [
                    'title'  => $this->lang('Notes'),
                    'icon'   => 'fas fa-note-sticky',
                    'access' => [
                        'add_note'    => ['title' => $this->lang('Ajouter'),   'icon' => 'fas fa-plus', 'admin' => TRUE],
                        'delete_note' => ['title' => $this->lang('Supprimer'), 'icon' => 'far fa-trash-alt', 'admin' => TRUE],
                    ],
                ],
            ],
        ],
    ];
}
```

Permissions become editable in **Administration → Permissions** (role × action matrix). In the code:
`$this->access('notes', 'add_note')`. A real example: `modules/news/news.php`.

A **per-item** permission (a category, a gallery) declares its starting values in `init`, which
`$this->access->init('notes', 'categorie', $id)` sets when the item is created. Since the “member” role does not
inherit from the “visitor” role, `init` follows NeoFrag's original rules when `members` is not named: an **empty**
list makes the action public (visitors and members); `['visitors', TRUE]` also applies to members;
`['visitors', FALSE]` only refuses visitors — members keep the action; and `['admins', TRUE]` reserves it to
administrators. A real example: `modules/forum/forum.php` (read, write, moderate a category).

## 7. Administration (optional)

If `'admin' => TRUE`, add `controllers/admin.php` (class `Admin extends Controller_Module`). Its `index()` method
is served under `/admin/notes`.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
    public function index()
    {
        $this->title($this->lang('Notes'))->icon('fas fa-note-sticky');

        $this->form()
             ->add_rules([
                 'title' => ['label' => $this->lang('Titre'),   'type' => 'text',   'rules' => 'required'],
                 'body'  => ['label' => $this->lang('Contenu'), 'type' => 'editor'],
             ])
             ->add_submit($this->lang('Ajouter'));

        if ($this->form()->is_valid($post))
        {
            $this->db->insert('nf_notes', ['title' => $post['title'], 'body' => $post['body']]);
            notify($this->lang('Note ajoutée.'));
            redirect('admin/notes');
        }

        return $this->admin_card('fas fa-note-sticky', $this->lang('Notes'), $this->form()->display());
    }
}
```

**`form()`** (`add_rules()` / `is_valid()` / `display()`) is the form API of administration screens; `form2()`
(see [The framework](framework.md)) is for public, rich or confirmation-only forms. The `Admin_Helpers` trait,
available on any module controller, dresses the content: `admin_card()`, `admin_create()`, `admin_back()`,
`admin_split()`, `admin_action_bar()`, `admin_empty()`, `admin_stats()`, `sort_select()`. Every sub-page must offer
a way back (`admin_back()` or the breadcrumb): `tools/check-admin-back.php` checks it on every administration page.

### Mutating actions: require a CSRF token

Any action that **changes** something and is triggered by a **GET link** or a **hand-written POST** (outside
`form()` / `form2()`, which carry their own token) must be protected: `SameSite=Lax` is not enough.

```php
// Building the link: token in the query.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Controller: checks the token BEFORE changing anything, otherwise redirects.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

For a hand-written POST, the token goes in a hidden field:
`<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### Administration tables

`table2()` renders paginated lists, sortable by clicking the header and filterable (example:
`modules/user/controllers/admin.php`). Sorting is handled by `js/table2.js`, in vanilla JavaScript. A list the
checker splits by hand (`->paginate()`) renders its page links: the contract is in
[The framework](framework.md#tables--table2), and `check-pagination` checks it.

### The administration's style guide

The administration is **sober**: the background fades behind the data, the accent colour is rare, and bright
colours are kept for what calls for an action or signals a state. A module page is built like this:

- **One card per list**: `admin_card(icon, title, body, subtitle, actions)` — the subtitle carries the count (“3
  published · 1 draft”), the actions the button that creates.
- **The button that creates** (“New quote”): `admin_create(url, label)`, a `btn btn-primary btn-sm` in the header
  of the card of the list it feeds. The top bar keeps the page's tools — Permissions, Configuration, Help.
- **A row's actions**: icon-only buttons, small. *Edit* (pencil), *access* (padlock), *sort*:
  `btn-outline-secondary`, neutral. *Delete*: `btn-outline-danger` with the `far fa-trash-alt` icon. Never a filled
  button in a row. `button_update()`, `button_access()` and `button_delete()` render them this way; a hand-written
  button follows the same rule, which `tools/check-actions-admin.php` checks.
- **A row's other actions** (preview, duplicate, restore): an outline, never a filled button; a tint only if it
  carries a meaning (`btn-outline-warning` for a preview that changes the browsing mode, `btn-outline-success` to
  restore).
- **Status badges**: `badge text-bg-success` (published, active), `text-bg-secondary` (draft, inactive),
  `text-bg-warning` (pending), `text-bg-danger` (error) — the administration theme makes them soft (pale
  background, strong text), in light as in dark.
- **A table** for column data (title, count, status); **cards** (`nf-content-card`) for written content recognised by
  its excerpt. A list grouped by family (Email templates) stays **a single table**, with a
  `<tr class="nf-table-groupe">` row at the head of each family, rather than a card per family. A date from the
  database is shown there with `nf_date_heure()`, never as is.
- **The empty state**: `admin_empty(icon, title, text)`, which says what to do to get started.
- **The button that submits a form**: “Save” for an item that exists — never “Edit”, you are already editing it —,
  “Add” or “Create” for a new one, like the page title (“Add a forum”).
- **Several views of the same subject** (Monitoring, Users, Discord): `nf-local-nav` / `nf-local-tab` tabs — as
  links to other addresses, or as buttons that switch panels.

## 8. Testing the module

The project has a safety net, and a new module must go into it:

- **Tests** — `tests/Unit` (no database), `tests/Headless` (the framework booted, real models against the test
  database, each test in a rolled-back transaction — `HeadlessTestCase`), `tests/Integration` (SQL mirror),
  `tests/Browser/*.test.html` (a script's contract in a real browser, through `tools/check-js.php`).
  `vendor/bin/phpunit --fail-on-skipped`.
- **Checks** to pass before shipping: `check-addon-declarations`, `check-addon-coupling`, `check-addon-contracts`,
  `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (no visible text outside `lang()`),
  `check-actions-admin` (the button style guide), `check-pagination`, `check-heures` (no date shown without a time
  zone), `check-db-compteurs`, `check-demo-lock` (a module that touches configuration is locked in the demo),
  `check-vignettes` (its thumbnail exists, in the right format), `check-strict-types` (the counter must never go
  down — declare `declare(strict_types=1)` in your new files), then `check-install-profiles` (installs each profile
  for real and hits the routes of missing modules, which must return a **clean 404**, never a 500) and
  `check-js-console` (opens the administration pages in a browser and refuses any JS error).
- PHPStan: `vendor/bin/phpstan analyse`.

The complete list is in [`tools/README.md`](../../../tools/README.md); `php tools/check-all.php --navigateur` runs
them all.

## 9. Installing and distributing

1. Put `modules/notes/` in place.
2. **Administration → Themes & Addons → Scan the disk** → tick `notes` → install (installation plays
   `install/install.sql`, baselines the migrations, sets the permissions).
3. To distribute it: zip the folder (`modules/notes/` at the root of the archive), it installs through **Add
   (ZIP)**. To publish it in the project's marketplace catalogue, it must have `'core' => FALSE` and no
   `'distributed' => FALSE`; `tools/package-addons.php` zips and lists every optional addon in
   `marketplace/catalog.json` with its SHA-256 fingerprint.
4. Its thumbnail, before packaging: a **card** drawn from its `__info()` — its icon, its name and its French
   description —, readable at any size, even when the module has nothing to show:
   `php tools/vignettes-cartes.php --nom=notes --sortie=/tmp/cartes` to look at it, then `--poser` to put it in
   place. Format and checks: [Create a theme, § 5](create-a-theme.md).
