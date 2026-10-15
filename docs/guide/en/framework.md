# The framework

A reference of the building blocks you handle when writing addons. For the complete internal architecture, see
[`docs/architecture.md`](../../architecture.md). Everything below is checked against the code.

## The service locator — `NeoFrag()`

The whole framework is reachable through the global singleton `NeoFrag()` and, inside an addon class, through
`$this` (which delegates to it). Services are obtained through magic methods:

```php
$this->db        // database access
$this->config    // site configuration (nf_name, nf_default_theme…)
$this->user      // current member
$this->url       // request / segments / base
$this->lang(...) // translation
$this->events    // internal events (fire / on)
$this->module('forum');        // a module — NULL if it is not installed
NeoFrag()->model2('addon');    // a model
```

> `__call` **swallows unknown calls**: `$this->missing_method()` breaks nothing. To test a check, inject a
> fault the magic does not catch (a missing global function).

## Routing

A module declares its routes in `__info().routes`: `pattern => method`.

```php
'routes' => [
    ''                 => 'index',   // the module's home page
    '{id}/{url_title}' => '_show',   // /module/42/slug
    'admin{pages}'     => 'index',   // paginated administration
],
```

- **Fixed** placeholders: `{id}` (integer), `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}` and
  `{pages}` (pagination). An unknown placeholder → silent 404.
- Cycle: the **checker** (`controllers/checker.php`) validates the route and loads the data; what it **returns**
  becomes the arguments of the **controller**'s method of the same name (`controllers/index.php`).
- Today's router is `route → module → page → 404`. The themes' **named regions** (`region('content')`) are the
  foundation of the future page-centred routing (outlines, reserved routes), not written yet.

> In tests or with `curl`, the CMS treats non-browser agents as robots and **does not open the session**: send a
> **browser User-Agent**.

### A checker refusal shows in the log

When a checker fails — a missing POST field, a target that does not exist — the site answers **404**; the
reason (route, checker, method, missing field and what came instead) is **always logged** (`[checker] …`).
When debugging is visible — the debug tool switched on, for a logged-in administrator —, the reason is returned
to the client and the status is **400**: “your request is malformed” rather than “this address does not
exist”. `post_check('a', 'b?')`: the `?` suffix makes a field optional (`NULL` if it is missing).

## Database

A fluent query builder; tables carry the `nf_` prefix.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // array of rows; ->row() for a single one

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // returns the id
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // or ->rollback()
```

- **`table_exists('nf_teams')`** — true if the table exists. The package installs à la carte: a core module that
  reads an optional module's table **must** guard itself this way (or declare the dependency in `requires`).
  The result is cached for the request.
- **`import($sql)`** — runs multi-statement SQL (installation DDL) outside the prepared-statement pipeline.
- A **join that only counts or decorates must be `LEFT`**: seven lists used to hide any content without
  children (a forum with no message, an album with no image) through a strict join.
- A **counter** (views, clicks) on a table whose `updated_at` carries `ON UPDATE current_timestamp()` rewrites
  the modification date at every visit: the wiki page announced “modified on” at the time of its last reader.
  Write the column onto itself — `SET views = views + 1, updated_at = updated_at` —; `check-db-compteurs`
  refuses the omission.
- `MATCH … AGAINST` (FULLTEXT) **does not see** a row inserted in an uncommitted transaction: InnoDB only
  indexes at commit. A FULLTEXT search test cannot be wrapped in the test base's rolled-back transaction.

For managed entities (addons, files…), go through **models**: `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()`; to iterate over a typed set, `NeoFrag()->collection('addon')->get()`.

## Forms — `form()` & `form2()`

Two APIs coexist (a recorded decision: v1 is frozen, not migrated wholesale):

- **`form()`** — the API of **administration screens**: fields declared as an array through `add_rules([...])`,
  processed by `is_valid($post)`, rendered by `->display()`. A complete example in
  [Create a module](create-a-module.md) (§7).
- **`form2()`** — fluent, each field is a `form_*()` object. For **public** or **rich** forms, and the **only**
  one that validates a **confirmation-only** form (no field) — a deletion confirmation accepts **only** the
  `delete` field: an added box would make it fail silently.

```php
return $this->form2()
    ->rule($this->form_text('title')->title($this->lang('Titre'))->required())
    ->rule($this->form_textarea('body')->title($this->lang('Contenu')))
    ->captcha()
    ->success(function ($data) {
        $this->db->insert('nf_news', $data);
        notify($this->lang('Enregistré'));
    })
    ->submit($this->lang('Publier'));
```

Both carry their own CSRF token. An action triggered by a **link** or a **hand-written POST** must check it
itself: `csrf_url()`, `check_csrf()`, `csrf_token()` (`Admin_Helpers` trait); outside a controller, the same token
is read with `nf_jeton_csrf()`.

**The rich editor** — the `editor` type in `form()`, `form_textarea()->editor()` in `form2()` — is TinyMCE 8,
self-hosted; the HTML it produces goes through `sanitize_html()`. An image **pasted or dropped** into it is sent
to the site (`ajax/user/editeur-image`: logged-in member, session token, rate limited), then inserted by its
final address. The site reads its real type from its bytes (JPEG, PNG, GIF, WebP), refuses a file that carries
code, caps its weight (5 MB) and dimensions, **re-encodes** it — no more metadata, a photo's orientation
applied — and stores it under `upload/editeur/YYYY/MM/` with a random name. A hand-written `tinymce.init()` (a
template, a separate screen) puts `Editeur_Images::tinymce()` at the head of its settings
(`NF\NeoFrag\Libraries\Editeur_Images`): without it, a pasted image stays broken, then disappears on saving.
`EditeurImagesTest` checks it for the whole product.

**The captcha** — `->captcha()` in `form2()`, `add_captcha()` in `form()` — shows the provider chosen in
*Settings → Captcha* and checks its answer on the server; a logged-in member sees none. The default is
**ALTCHA**: hosted by the site, with no key and no cookie, it has the browser run a small computation (about a
second) that the server checks, and refuses that the same solution serves twice. Turnstile, hCaptcha and
reCAPTCHA v2 need their two keys; without them, ALTCHA takes over. Outside a form: `$this->captcha->element()` to
show it, `$this->captcha->is_valid()` to check (`NF\NeoFrag\Libraries\Captcha`).

## Tables — `table2()`

Renders paginated lists, **sortable by clicking the header** (Shift + click: multi-column sort; Ctrl + click:
remove) and filterable, from a query. Example: the member list, `modules/user/controllers/admin.php`. Sorting is
served by `js/table2.js` in vanilla JavaScript.

**A list split into pages shows its page links.** When a checker splits a list — `->paginate($page)` on a
collection, `->pagination->get_data($lignes, $page)` on an array —, the controller method that renders it calls
`->pagination->get_pagination()` (or goes through `table2()`, which renders it itself). Without it, only the first
items are visible and the others unreachable. A list that always fits on one page says so with a
`// pagination : <raison>` comment in the method. `check-pagination` checks it.

## Translations

`$this->lang('Key')` returns the translation. The `langs/fr.php` files map the **crc32b of the source key** to
the translation:

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // or directly '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` accepts arguments (`sprintf` style).
- **French is the source language**; six languages are shipped and `tools/check-langs.php --toutes` refuses a key
  missing in one of them. `--fix` adds the missing keys to **French only**.
- **Never translate content from the database** (a title written by the administrator): `lang()` looks for it in
  vain and logs a warning at every visit. Wrap it in `$this->no_translate(...)`.

### Translated content: `join_lang()` and `ecrire_lang()`

A module's **content** (a title, a description) is translated differently: it lives in a `<table>_lang` table,
one row per language, and what has no language (a date, an image, a price) stays in the main table.

```sql
CREATE TABLE IF NOT EXISTS nf_notes_lang (
    note_id INT UNSIGNED NOT NULL,
    lang    VARCHAR(5)   NOT NULL,
    title   VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (note_id, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Reading**: `join_lang()` joins the **best** translation — the one in the requested language, otherwise
  French, otherwise the first one written. Content written in a single language thus stays visible in the
  others, instead of disappearing as with a join filtered on the language.

  ```php
  $this->db->select('n.id', 'nl.title')->from('nf_notes n')->join_lang('nf_notes_lang nl', 'note_id', 'n.id')->get();
  ```

- **Writing**: `ecrire_lang()` creates the row when it is missing — an `update()` filtered on the language wrote
  nothing the first time something was translated, and the text entered was lost. Give every required column
  of the table.

  ```php
  $this->db->ecrire_lang('nf_notes_lang', ['note_id' => $id, 'lang' => $langue], ['title' => $titre]);
  ```

- **The edit screen** writes in the administration's language, and says so under the field (“Written for
  %s…”): you translate by switching language. What the administrator and the visitor see:
  [A site in several languages](multilingue.md).

## Dates and time zones

The product **stores** its dates in the time zone PHP has at startup — universal time in production — and never
changes it: the database aligns its session on it (`drivers/mysqli.php`). It **displays** them in the time zone
of whoever is looking, given by `nf_fuseau()`: the member's choice (`nf_user_profile.timezone`, applied when the
session opens), otherwise their browser's (`nf_fuseau` cookie, set by `views/theme/main.tpl.php`), otherwise the
site setting (`nf_timezone`), otherwise the storage time zone. Everything is in `neofrag/helpers/time.php`.

- **Writing to the database**: `date('Y-m-d H:i:s')`, `NOW()` or `now()` — the storage time zone.
- **Displaying**: `timetostr($format, $when)` or the Date library (`$this->date($when)`); never `date()`, which
  `check-heures` refuses outside machine formats. For a date-and-time column read as is (a list, a card):
  `nf_date_heure($valeur)` — `d/m/Y H:i` in the visitor's language and time zone, an empty string if the value is
  empty; for a date alone, `nf_date($valeur)`. Never a numeric format hard-coded in `timetostr()` (`'d/m/Y'`,
  `'Y-m-d H:i'`): German writes `02.10.2026`, and `check-heures` refuses it.
- **Reading an input**: the `datetime` fields of both form systems convert by themselves; by hand,
  `nf_heure_saisie()` (and `nf_heure_affichee()` for the reverse).
- **A date alone or a time alone** (`Y-m-d`, `H:i`) is not an instant: `timetostr()` and Date do not convert it.
  A whole day stored in a date-and-time column is passed as `Y-m-d` (see `Calendar::format_dt()`).
- **A time in the site's time**, whatever the visitor (a programme schedule):
  `new \DateTime('now', nf_fuseau_site())`.

## The front end: Bootstrap 5, vanilla, strict CSP

- **Bootstrap 5.3**, **without jQuery**. Grid classes always carry a breakpoint (`col-12 col-lg-8`);
  `tools/check-classes-bs4.php` refuses Bootstrap 4 classes.
- **Strict nonce-based CSP**: each HTML response carries a random nonce, set on every inline `<script>` by the
  `index.php` filter, and `script-src` only allows `'self'`, that nonce, the origins of the **active** captcha
  provider — none with ALTCHA, the default — and Google Analytics **if** an ID is configured. **No script from a
  CDN.** Dynamically inserted JS goes through `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`, which re-run the
  added `<script>` elements with the right nonce, including in the Live Editor's iframe.
- **`window.NF`**, defined in the main template, replaces the few primitives that were needed — and nothing more
  (it is not a mini-jQuery):

| Primitive | Role |
|---|---|
| `NF.ready(fn)` | runs `fn` when the DOM is ready (or at once if it already is) |
| `NF.data(el, 'my-key')` | reads `data-my-key` with jQuery's coercion (number, boolean, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` with `X-Requested-With` on the same origin, `form-urlencoded` body, arrays as `key[]`; **rejects** on an error status; `dataType: 'text'` otherwise JSON |
| `NF.post(url, data)` | POST shortcut |
| `NF.setHtml(el, html)` | `innerHTML` + running the scripts |
| `NF.insertHtml(target, position, html)` | `insertAdjacentHTML` + running only the added scripts |
| `NF.replaceHtml(el, html)` | replaces the element, scripts run |
| `NF.runScripts(root)`, `NF.loadScript(src)` | re-run, load with the nonce |
| `NF.bandeaux()` | arranges the banners at the top of the page and republishes `--nf-haut` and `--nf-entete` (see *Create a theme*); call it again after adding a banner |

- Modals (`js/modal.js`), notifications (`js/notify.js`) and confirmations (`js/confirm.js`) are loaded by the
  themes.
- Two checks guard this front end: `check-js-sources` (syntax, interpolated PHP tolerated, and no `$(`),
  `check-js-console` (the real pages, in a real browser: no error, no CSP violation). The
  `tests/Browser/*.test.html` + `tools/check-js.php` harness tests a script's **contract** on the real file, with
  `fetch` doubles.

## Security: what already exists

Before writing your own: `Rate_Limit` (per key, e.g. `contact:ip:<ip>`), `Audit_Log` (sensitive actions),
`File_Jail` (upload paths), `Moderation`, TOTP for two-factor authentication, `sanitize_html()` (HTMLPurifier) for
rich HTML, `is_dangerous_upload()`, and demo mode (`nf_demo()`), which locks the writing of sensitive modules.

**An address typed by someone and placed in an `href` or a `src`** goes through `nf_url_sure($url)`: it returns
`FALSE` for a scheme other than `http`, `https`, `mailto`, `tel` or `geo` — a `javascript:` would run on click
with the rights of whoever clicks. A relative address passes. `nf_texte()` is not enough: it escapes
quotes, not the scheme. `url()` returns `#` for a refused absolute address.

### The demo site

The demo is shared: its `demo` account is an administrator. The safety net is in `neofrag/helpers/system.php`,
and holds in front of every controller (`core/output.php`):

- **`NF_DEMO_MODULES_VERROUILLES`** — the modules where no action goes through in the demo: any POST, any link
  carrying a token (`?_=`), any method whose name contains an action word (`NF_DEMO_MOTS_D_ACTION`: export, test,
  toggle…) is refused — a JSON 403 for an AJAX call, otherwise a notification and a return to the previous page
  (`nf_demo_requete_refusee()`). **A new module that touches the site's configuration is added to this list**: it
  is a deny list, a missing module is open. `check-demo-lock` reminds you.
- **`NF_DEMO_LECTURES`** — the few reads allowed in a locked module (`controleur::methode`), which then return
  fictitious examples.
- **`nf_demo_ecriture_permise($module)`** — the question to ask in code that writes outside the net; true outside
  the demo.
- **`nf_compte_masque()`** — the demo's emergency account, which no member list shows:
  `->where('u.id !=', nf_compte_masque())`. Outside the demo, it is 0.
- In the demo, `File::delete()` deletes nothing and file uploads are refused. A page that would show a file, a log
  or a sensitive setting shows an example instead.
- The shared account cannot be changed from the member area: no username, address, password, two-factor
  authentication or deletion — a visitor used to lock the others out until the reset. Since the net only covers
  the administration, these pages run the test themselves (`nf_demo()`, `modules/user/controllers/index.php`).

## Events

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` on the emitting side;
`$this->events->on('forum.post.created', function (...) { … })` on the subscribing side. Listeners stay active for
the whole request.

## Useful helpers

- `url('forum/42')` — builds a routed URL (language prefix included).
- `notify('Message')` — an on-screen notification after a redirect.
- `nf_texte(...)` / `sanitize_html(...)` — escaping / anti-XSS cleaning.
- `nf_url_sure($url)` — can a typed address go into a link (see Security).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — a date from the database, to show (see Dates).
- `nf_demo()` — true on the demo site.

## Migrations

The **core** schema evolves through versioned migrations, tracked in `nf_migrations`:

```bash
php tools/migrate.php status                 # applied / pending
php tools/migrate.php up [--pretend]          # applies (simulation with --pretend)
php tools/migrate.php down [--step=N]         # rolls back
php tools/migrate.php baseline --until=NAME   # adopt an existing database
```

Files: `migrations/YYYY_MM_DD_name.up.sql` (+ `.down.sql`). MySQL DDL auto-commits → **back up before `up` in
production**. **Addons** have their own migrations (`<addon>/install/migrations/`, tracked in
`nf_addon_migrations`) — see [Create a module](create-a-module.md).

**New code applies its migrations by itself**, once, on the first page viewed after the update — through the
button as through FTP: the core's, then those of installed addons (`nf_migrations_du_code()`, called by
`index.php` when the `nf_migrations_version` setting differs from `NEOFRAG_VERSION`). This holds for every site,
the demo included. A failure does not stop the page from showing: it is logged, and the next attempt waits ten
minutes.

## Three-level overriding

Resolution of views, classes and files (the first one found wins):

1. `overrides/{type}/{file}` — global
2. `themes/{active theme}/overrides/{type}/{file}` — per theme
3. the shipped original

You customise without forking and without breaking updates. Careful: two files with the same name at two levels,
and `path()` serves one or the other depending on the caller — a wizard once showed without style because of a
duplicate (`tools/check-assets.php` now refuses it).

## Development environment

The project runs on **any PHP 8.2+ with MySQL or MariaDB** (Apache + `mod_rewrite`, or nginx, or Caddy). The
project's reference path is a **test installation on a server**, where the full battery runs:
`php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped` and `composer stan`. The
environment, the test database and the work loop are described in [`docs/development.md`](../../development.md);
each tool is described in [`tools/README.md`](../../../tools/README.md).
