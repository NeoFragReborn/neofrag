# Ein Modul erstellen

Ein **Modul** ist eine vollständige Funktion: seine öffentlichen Seiten, seine Routen, seine Verwaltung, seine Daten und
seine Berechtigungen. Es ist das reichhaltigste Addon.

Wir erstellen ein Modul `notes`, das eine Liste öffentlicher Notizen zeigt. Alles Folgende ist am Code geprüft; die
mitgelieferten Module (`modules/contact`, `modules/news`) sind die besten Beispiele zum Weiterlesen.

## Dateistruktur

```
modules/notes/
├── notes.php                 # Klasse: Metadaten, Deklarationen, Routen, Berechtigungen
├── controllers/
│   ├── checker.php           # prüft die Route und lädt die Daten
│   ├── index.php             # stellt die öffentlichen Seiten dar
│   └── admin.php             # Verwaltungsbildschirm (optional)
├── install/
│   ├── install.sql           # die Tabellen des Moduls (bei der Installation ausgeführt, idempotent)
│   ├── uninstall.sql         # Entfernen der Tabellen
│   └── migrations/           # Schemaänderungen zwischen zwei Versionen (optional)
├── views/
│   └── index.tpl.php
├── css/                      # optional
├── images/
│   └── thumbnail.jpg         # sein Vorschaubild, 960 × 600 — eine Karte: Symbol, Name, was es tut
├── js/                       # optional — Vanilla, nie jQuery
└── langs/
    └── fr.php                # Übersetzungen (optional)
```

## 1. Die Klasse — `notes.php`

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
            'icon'        => 'fas fa-note-sticky',   // muss im mitgelieferten FontAwesome existieren
            'author'      => 'Dein Name',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // die Kernversion, die es mindestens braucht — ohne sie lehnt „Hinzufügen“ das Archiv ab
            'admin'       => TRUE,                   // stellt eine Verwaltungsseite bereit

            // Entkopplungsdeklarationen — PFLICHT (die CI lehnt ein stummes Addon ab).
            'core'        => FALSE,                  // TRUE = immer mitgeliefert und nicht deinstallierbar
            'presets'     => ['communaute'],         // Installationsprofile, die es vorab anhaken
            'requires'    => [],                     // Addons, die es BRAUCHT (ohne sie bricht es)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/meine-notiz
            ],
        ];
    }
}
```

### Die drei Entkopplungsdeklarationen

Seit dem 15.09.2026 installiert sich das Paket **modular**: Der Installer bietet Profile an (*Vollständig*, *Gaming /
eSport*, *Community*, *Verein / Club*, *Nur der Kern*), **aus diesen Deklarationen** gebildet. Keine Liste wird
anderswo von Hand geschrieben.

- `core` — `TRUE` für ein Infrastruktur- oder CMS-Modul, das immer mitgeliefert wird und nicht deinstallierbar ist;
  `FALSE` für alles Optionale. Ein Modul des Kerns kann **nie** von einem optionalen abhängen (Regel 3 von
  `tools/check-addon-declarations.php`).
- `presets` — die Profile, die es vorab anhaken: `'gaming'`, `'communaute'`, `'association'` (die Bezeichnungen von
  `install/lib/presets.php`). Leer: Es erscheint nur im Profil *Vollständig*.
- `requires` — die Addons, die es braucht, **um nicht zu brechen**: eine gelesene Tabelle, eine genannte Klasse. Der
  Installer fügt sie automatisch hinzu, wenn dein Modul angehakt wird. Eine **weiche** Abhängigkeit (ein Dienst, der
  fehlen kann) wird hier nicht deklariert: Sie wird im Code **abgesichert** (siehe §5).

Diese drei Schlüssel werden in der CI statisch geprüft, ebenso die Existenz des deklarierten Symbols (Regel 7:
FontAwesome hat zwischen 5 und 6 Symbole umbenannt). Ein Addon, das du nicht im Katalog des Marketplace willst, fügt
`'distributed' => FALSE` hinzu.

### Die Routen

Eine Route ordnet ein **URL-Muster** einer **Controller-Methode** zu. Das Muster ist relativ zum Namen des Moduls (`''` =
die Startseite des Moduls, hier `/notes`). Verwaltungsrouten beginnen mit `admin` (`'admin{pages}' => 'index'` für eine
paginierte Liste unter `/admin/notes`).

> Die Platzhalter sind eine **feste** Menge: `{id}` (Ganzzahl), `{key_id}`, `{url_title}` (Slug), `{url_title*}`
> (Slug mit `/`), `{page}` und `{pages}` (Paginierung). Ein unbekannter Platzhalter erzeugt ein stilles 404. Für eine
> Detailseite ist das idiomatische Muster `{id}/{url_title}`: Die `id` identifiziert das Element, der Slug ist
> kosmetisch.

## 2. Der Checker — `controllers/checker.php`

Der **Checker** läuft vor dem Controller: Er prüft die Route und lädt die Daten. `FALSE` oder nichts zurückzugeben löst
ein 404 aus. Was er **zurückgibt, wird zu den Argumenten** der gleichnamigen Controller-Methode.

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
        // nichts: 404
    }
}
```

Für einen Einstiegspunkt, der einen **POST** erhält (AJAX, ein von Hand geschriebenes Formular), liest der Checker die
Felder mit `post_check('titre', 'corps', 'options?')`: Jeder Name ist **Pflicht**, außer er trägt das Suffix `?`, dann
ist er `NULL`, wenn er fehlt. Ein fehlendes Pflichtfeld lässt den Checker scheitern: eine **404**-Antwort in der
Produktion (der genaue Grund — welches Feld, was stattdessen kam — wird **protokolliert**) und **400 mit dem Grund**,
wenn das Debugging sichtbar ist (ein angemeldeter Administrator, das Debug-Werkzeug an). Dieses Verhalten stammt von zehn
Widgets, die sich im Live-Editor nicht mehr hinzufügen ließen, weil ein optionales Feld verlangt wurde.

## 3. Der öffentliche Controller — `controllers/index.php`

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

`title()`, `icon()`, `breadcrumb()` füllen den Kopf der Seite. Der Controller stellt danach seine View dar, oder ein
Panel über `$this->panel()->title()->body($html)` für in PHP gebautes HTML.

> **Leite Inhalte aus der Datenbank nie durch `$this->title()` und dann durch die Übersetzung.** Ein vom Administrator
> geschriebener Titel hat keine Übersetzung: `lang()` sucht ihn vergeblich und protokolliert bei jedem Besuch in einer
> anderen Sprache eine Warnung. Pack, was aus der Datenbank kommt, in `$this->no_translate(...)`.

## 4. Die View — `views/index.tpl.php`

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

Die an `view()` übergebenen Variablen sind direkt verfügbar. **Maskiere immer**, was aus der Datenbank kommt
(`nf_texte()`); für von einem Mitglied eingegebenes reichhaltiges HTML `sanitize_html()` (HTMLPurifier). Eine
eingegebene **Adresse**, die in einem `href` landet, geht zusätzlich durch `nf_url_sure()`, das `javascript:` und die
anderen ausführbaren Schemata ablehnt (siehe [Das Framework](framework.md#sicherheit-was-es-schon-gibt)).

**Das Frontend ist Bootstrap 5, ohne jQuery, unter strikter CSP.**
- Grid-Klassen schreibt man `col-12 col-lg-8`, nie `col-8` allein (das ab 0 px gilt und das Telefon bricht);
  `tools/check-classes-bs4.php` lehnt verschwundene Klassen von Bootstrap 4 ab.
- Ein Inline-`<script>` in einer View erhält automatisch das **Nonce** der Antwort (Filter von `index.php`); eine
  JS-Datei lädt man mit `->js('name')` (Ordner `js/` des Moduls).
- Schreibe das JavaScript mit den Primitiven des Kerns — `NF.ready`, `NF.data(el, 'schlüssel')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`, `NF.replaceHtml` —,
  nie `$(…)`: jQuery wird nicht mehr geladen, und `tools/check-js-sources.php` lehnt jeden Aufruf ab. Die Adressen, die
  das Skript braucht, kommen als `data-*` ins Markup statt als in das `.js` interpoliertes PHP.

## 5. Die Daten

Ein Modul **liefert seine Tabellen** in `install/install.sql` (und ihr Entfernen in `uninstall.sql`). Dieses SQL wird
**bei der Installation des Moduls** ausgeführt (Installer, Scan der Verwaltung, ZIP des Marketplace), idempotent dank
`CREATE TABLE IF NOT EXISTS`:

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Ein übersetzbarer Inhalt** (ein Titel, eine Beschreibung) lebt in einer Tabelle `<table>_lang`, eine Zeile pro
> Sprache, gelesen mit `join_lang()` und geschrieben mit `ecrire_lang()`:
> [Das Framework](framework.md#übersetzte-inhalte-join_lang-und-ecrire_lang).

> **Keine eigenen Tabellen? Kein `install.sql`.** Ein Modul, das Tabellen des Kerns wiederverwendet (zum Beispiel
> `nf_file` über `model2('file')`), installiert sich, ohne das Schema anzufassen.

### Das Schema weiterentwickeln — Migrationen pro Addon

`install.sql` legt fehlende Tabellen an, **ändert** aber keine Tabelle, die bei den Nutzern schon vorhanden ist. Für eine
Schemaänderung zwischen zwei Versionen deines Moduls:

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Name = **datiertes Präfix** (`JJJJ_MM_TT_beschreibung`), Ausführung in Reihenfolge.
- Verfolgt in `nf_addon_migrations`: Jede Migration läuft **nur einmal**.
- Bei einer Neuinstallation (`install.sql` trägt schon das aktuelle Schema) werden Migrationen **als Basis gesetzt** —
  markiert, ohne ausgeführt zu werden. Beim Update (Marketplace → „Updates“, oder ein über „Hinzufügen“ gesendetes
  Archiv) werden nur die neuen **ausgeführt** (`Addon::update()`). Ein mit dem Kern geliefertes Modul erhält seine beim
  ersten Aufruf mit dem neuen Code (`nf_migrations_du_code()`, siehe [das Framework](framework.md#migrationen)).
- Goldene Regel: eine Migration für eine echte Änderung an einem **schon ausgelieferten** Schema; eine neue Tabelle
  gehört in `install.sql`, nie in eine Migration — **außer** das Modul ist auf Seiten schon installiert: Dann kommt die
  neue Tabelle in beide, `CREATE TABLE IF NOT EXISTS` in der Migration.
- Ein Update über den Marketplace oder per Archiv spielt `install.sql` **vor** den Migrationen erneut ab: Eine Website,
  die Versionen überspringt, erhält die Tabellen der neuesten Version und führt die älteren Migrationen darauf aus. Eine
  Migration, die Daten schreibt, zielt deshalb auf das Schema der neuesten `install.sql` und wird umgeschrieben, wenn es
  sich ändert — eine Website, die sie schon ausgeführt hat, erkennt sie an ihrem Namen und führt sie nicht erneut aus.
  Benenne eine Migration aus demselben Grund nie um.
- Auf der Demo-Seite spielt ein aus der Verwaltung gestartetes Addon-Update nichts ab (`Addon::migrate()` bricht ab,
  wenn `nf_demo()` wahr ist); mit neuem Code kommende Migrationen gelten dort wie anderswo.

Der Ordner `migrations/` **im Wurzelverzeichnis** des Projekts ist den übergreifenden Änderungen des Kerns vorbehalten.

### Von einem anderen Modul abhängen, ohne zu brechen

Das Paket installiert sich modular: Das Modul, das du brauchst, ist vielleicht **nicht da**. Vier Arten der
Abhängigkeit, und was passiert, wenn es fehlt (`tools/check-addon-coupling.php` erkennt sie mit dem Tokenizer):

| Mittel | Wenn das Addon fehlt | Was zu tun ist |
|---|---|---|
| **seine Tabelle** lesen (`from('nf_teams')`) | **fatal** — „Table doesn't exist“ | in `requires` deklarieren oder mit `$this->db->table_exists('nf_teams')` absichern und **annotieren** |
| **seine Klasse** nennen | **fatal** — „Class not found“ | ebenso |
| **seinen Dienst** aufrufen (`$this->module('teams')`, `model2('team')`) | tolerant: gibt `NULL` zurück | die Rückgabe prüfen: `if ($teams = $this->module('teams'))` |
| **seine URL** schreiben (`url('teams/…')`) | kosmetisch: ein toter Link | annehmbar |

Eine fatale Kopplung, **weder deklariert noch annotiert, lässt die CI scheitern**. Die Annotation steht an der genauen
Stelle, als Kommentar: `// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, oder am Anfang
der Datei `couplage(teams): …`, wenn die Verweise verteilt sind.

### Inhaltstypen: Reaktionen, Abonnements, Revisionen, Papierkorb

Veröffentlicht dein Modul **Inhalte** (wie News, Artikel, Themen), deklariere sie: Die übergreifenden Module —
Reaktionen, Benachrichtigungen, Revisionen, Papierkorb, Gamification — sammeln sie ein, statt jeweils eine eigene Liste
von Tabellen zu führen.

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

Ein echtes Beispiel: `modules/news/news.php`.

### Die Suchmaschinen-Einstellungen eines Inhalts

Ein oben deklarierter Inhaltstyp (mit seiner `table` und `pk`) kann, wie News oder das Wiki, einen Titel und eine
Beschreibung für Suchmaschinen erhalten — die gemeinsame Seite ist die der Rubrik Suchmaschinen, auf dieser Seite ist
nichts zu schreiben. Drei Aufrufe:

```php
// Die Bearbeitungskarte der Verwaltung: der Knopf, der zur gemeinsamen Seite führt.
return $this->admin_card('fas fa-edit', $titre, $formulaire, '', nf_seo_bouton('note', (int) $id));

// Die Aktion der öffentlichen Seite, vor dem Darstellen der View: wendet den eingegebenen Titel und die Beschreibung an.
nf_seo_contenu('note', (int) $id);

// Wenn sich die Adresse eines Inhalts ändert (ein Name, ein Slug), führt die alte zur neuen (301).
nf_redirection_ajouter('notes/'.$ancien_slug, 'notes/'.$nouveau_slug);
```

Echte Beispiele: `modules/wiki/controllers/admin.php` und `modules/wiki/controllers/index.php`.

### Knotenpunkte: Statistiken, Aktivität, Dashboard, Suche, Sitemap

Ein Modul klinkt sich in eine **sammelnde** Seite ein, indem es einen Controller mit dem Namen des Knotenpunkts
bereitstellt. Der Knotenpunkt ruft die gleichnamige Methode **ohne jede Prüfung** auf: umbenannt, privat gemacht oder mit
einem weiteren Pflichtparameter versehen, zeigt sich der Fehler erst beim Öffnen der Seite — darum friert
`tools/check-addon-contracts.php` diese Verträge in der CI ein.

| Controller | Erwartete Methode | Aufgerufen von |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | der Statistikseite der Verwaltung |
| `controllers/activity.php` | `activity($user_id, $limit)` | dem Profil eines Mitglieds |
| `controllers/dashboard.php` | `dashboard()` | dem Dashboard der Verwaltung |
| `controllers/block.php` | `block()` | den Blöcken `[block:…]` der Seiten |
| `controllers/search.php` | `search()` **und** `suggest()` | der globalen Suche und dem Sofortvorschlag — ein Modul, das nur `search()` hat, wird **still ignoriert** |
| `controllers/sitemap.php` | `sitemap()` | der Sitemap (`/sitemap.xml`, eine pro Sprache): gibt `[['adresse' => 'meinmodul/12/titel', 'date' => …], …]` zurück, Pfade wie die, die `url()` nimmt, nur was ein **Besucher** lesen kann (`$this->access('meinmodul', 'lesen', $id, 'visitors')`) und was **in der Sprache der Sitemap** existiert — ein Inhalt **ohne eigene Sprache** (ein Thema, ein Ticket, eine aus solchen Inhalten gebaute Seite) trägt `'sans_langue' => TRUE`: Er erscheint nur in der Sitemap der ersten Sprache der Seite, und seine Seite ruft `nf_seo_sans_langue()` auf, das dort seine kanonische Adresse setzt (sechs Sprachen desselben Textes, jede kanonisch: Google behält eine andere und indexiert keine); **eine leere Rubrik gibt `[]` zurück**: Ihre Seite hätte nur „noch nichts“ zu zeigen; ohne den Knotenpunkt fehlt das Modul in den Suchmaschinen, und IndexNow meldet seine Seiten nicht: Die geplante Aufgabe vergleicht dieselbe Sitemap von einem Durchlauf zum nächsten |

### Der Mitgliederbereich und das öffentliche Profil

Drei Methoden der **Klasse** des Moduls, optional, und nur gelesen, wenn sie existieren:

- **`espace_membre($user)`** fügt Seiten zum **Menü des Mitgliederbereichs** hinzu — die linke Spalte am Computer, die
  Reiterleiste am Telefon, auch in der oberen Leiste der Themes und im Widget „Mitgliederbereich“ wiederholt. Sie gibt
  eine Liste von Einträgen zurück: `url`, `titre`, `icone`, bei Bedarf `badge` (eine zu meldende Zahl) und `compact`
  (auch in kurzen Menüs gezeigt), und `ordre`, um sie zu ordnen.
- **`profil_membre($membre)`** fügt dem **öffentlichen Profil** eines Mitglieds **Reiter** hinzu, jeder unter seiner
  Adresse `user/<id>/<benutzername>/<reiter>`. Sie gibt eine Liste von Reitern zurück: `onglet` (das Adresssegment:
  Kleinbuchstaben, Ziffern und Bindestriche), `titre`, `icone`, `ordre`, bei Bedarf `nombre` (neben dem Titel und in
  „In Zahlen“ gezeigt), und `contenu`, eine Funktion, die die Seite des Reiters darstellt — erst beim Öffnen
  aufgerufen. **Nichts zu zeigen, nichts zurückzugeben**: Ein leerer Reiter erscheint nicht, und seine Adresse antwortet
  mit 404.
- **`types_de_notification()`** deklariert die Benachrichtigungen, die das Modul verschickt, für die **Einstellungen**
  jedes Mitglieds (`user/notifications/preferences`): `type` (den, den `push()` erhält), `titre` (was das Mitglied
  liest, „Eine Antwort in einem Thema, dem ich folge“), `email` auf `TRUE`, wenn das Modul sie auch per E-Mail schicken
  kann, und `ordre`. **Ein verschickter Typ muss deklariert sein**: Sonst kann das Mitglied ihn nicht abschalten —
  `tests/Unit/TypesDeNotificationTest.php` liest die Versendungen des Repositorys und lehnt es ab.

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

Das Profil zeigt, was **der Betrachter** lesen darf: Ein Reiter, der Inhalte auflistet, filtert wie seine eigenen
Seiten (`$this->access(…)`) — das Forum zeigt dort nur lesbare Kategorien. Was das Mitglied von sich verbergen will
(Punkte, Karma, VIP, Alter, Online-Präsenz), fragt man bei seinem Modell nach: `$membre->montre('statut')`. Echte
Beispiele: `modules/forum/forum.php`, `modules/teams/teams.php`.

Eine Benachrichtigung geht über `push($user_id, $type, $titre, $adresse, $acteur)` des Moduls `notifications` hinaus
(oder `push_unique()`, das keine zweite desselben Typs an dieselbe Adresse hinzufügt, solange die erste ungelesen ist):
Was das Mitglied **auf der Seite** abgeschaltet hat, wird nicht geschrieben. Ein Modul, das sie auch per E-Mail
schickt, stellt die Frage zuerst selbst, `$notifications->veut($user_id, $type, 'email')`, und schreibt nicht, wenn die
Antwort nein ist. Aus der Klasse des Moduls lädst du es mit `\NF\NeoFrag\Addons\Module::__load(\NeoFrag(),
['notifications'])` — `$this->module()` irrt sich dort im Addon-Typ. Ein echtes Beispiel: `modules/talks/talks.php`.

## 6. Die Berechtigungen (optional)

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

Die Berechtigungen werden unter **Verwaltung → Berechtigungen** bearbeitbar (Matrix Rolle × Aktion). Im Code:
`$this->access('notes', 'add_note')`. Ein echtes Beispiel: `modules/news/news.php`.

Eine Berechtigung **pro Element** (eine Kategorie, eine Galerie) deklariert ihre Startwerte in `init`, die
`$this->access->init('notes', 'categorie', $id)` beim Anlegen des Elements setzt. Da die Rolle „Mitglied“ nicht von der
Rolle „Besucher“ erbt, folgt `init` den ursprünglichen Regeln von NeoFrag, wenn `members` nicht genannt ist: Eine
**leere** Liste macht die Aktion öffentlich (Besucher und Mitglieder); `['visitors', TRUE]` gilt auch für Mitglieder;
`['visitors', FALSE]` lehnt nur Besucher ab — Mitglieder behalten die Aktion; und `['admins', TRUE]` behält sie den
Administratoren vor. Ein echtes Beispiel: `modules/forum/forum.php` (eine Kategorie lesen, schreiben, moderieren).

## 7. Die Verwaltung (optional)

Mit `'admin' => TRUE` fügst du `controllers/admin.php` hinzu (Klasse `Admin extends Controller_Module`). Seine Methode
`index()` wird unter `/admin/notes` ausgeliefert.

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

**`form()`** (`add_rules()` / `is_valid()` / `display()`) ist die Formular-API der Verwaltungsbildschirme; `form2()`
(siehe [Das Framework](framework.md)) dient öffentlichen, reichhaltigen oder reinen Bestätigungsformularen. Der Trait
`Admin_Helpers`, auf jedem Controller eines Moduls verfügbar, kleidet den Inhalt ein: `admin_card()`,
`admin_create()`, `admin_back()`, `admin_split()`, `admin_action_bar()`, `admin_empty()`, `admin_stats()`,
`sort_select()`. Jede Unterseite muss einen Rückweg anbieten (`admin_back()` oder die Brotkrumen):
`tools/check-admin-back.php` prüft es auf jeder Verwaltungsseite.

### Ändernde Aktionen: ein CSRF-Token verlangen

Jede Aktion, die etwas **ändert** und durch einen **GET-Link** oder einen **von Hand geschriebenen POST** ausgelöst wird
(außerhalb von `form()` / `form2()`, die ihr eigenes Token tragen), muss geschützt werden: `SameSite=Lax` genügt nicht.

```php
// Den Link bauen: Token in der Query.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Controller: prüft das Token, BEVOR er etwas ändert, sonst Umleitung.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Für einen von Hand geschriebenen POST kommt das Token in ein verstecktes Feld:
`<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### Verwaltungstabellen

`table2()` stellt paginierte Listen dar, sortierbar per Klick auf den Kopf und filterbar (Beispiel:
`modules/user/controllers/admin.php`). Die Sortierung übernimmt `js/table2.js`, in reinem JavaScript. Eine Liste, die der
Checker von Hand teilt (`->paginate()`), stellt ihre Seitenlinks dar: Der Vertrag steht in
[Das Framework](framework.md#tabellen--table2), und `check-pagination` prüft ihn.

### Die Gestaltungsregeln der Verwaltung

Die Verwaltung ist **schlicht**: Der Hintergrund tritt hinter die Daten zurück, die Akzentfarbe ist selten, und leuchtende
Farben bleiben dem vorbehalten, was zu einer Aktion auffordert oder einen Zustand meldet. Eine Modulseite ist so
aufgebaut:

- **Eine Karte pro Liste**: `admin_card(symbol, titel, inhalt, untertitel, aktionen)` — der Untertitel trägt die Anzahl
  („3 veröffentlicht · 1 Entwurf“), die Aktionen den Knopf, der anlegt.
- **Der Knopf, der anlegt** („Neues Zitat“): `admin_create(url, bezeichnung)`, ein `btn btn-primary btn-sm` im Kopf der
  Karte der Liste, die er speist. Die obere Leiste behält die Werkzeuge der Seite — Berechtigungen, Konfiguration, Hilfe.
- **Die Aktionen einer Zeile**: Knöpfe nur mit Symbol, klein. *Bearbeiten* (Stift), *Zugriff* (Schloss), *sortieren*:
  `btn-outline-secondary`, neutral. *Löschen*: `btn-outline-danger` mit dem Symbol `far fa-trash-alt`. Nie ein gefüllter
  Knopf in einer Zeile. `button_update()`, `button_access()` und `button_delete()` stellen sie so dar; ein von Hand
  geschriebener Knopf folgt derselben Regel, die `tools/check-actions-admin.php` prüft.
- **Die anderen Aktionen einer Zeile** (Vorschau, duplizieren, wiederherstellen): eine Kontur, nie ein gefüllter Knopf;
  eine Tönung nur, wenn sie eine Bedeutung trägt (`btn-outline-warning` für eine Vorschau, die den Navigationsmodus
  ändert, `btn-outline-success` zum Wiederherstellen).
- **Die Status-Plaketten**: `badge text-bg-success` (veröffentlicht, aktiv), `text-bg-secondary` (Entwurf, inaktiv),
  `text-bg-warning` (wartend), `text-bg-danger` (Fehler) — das Verwaltungs-Theme macht sie sanft (blasser Hintergrund,
  kräftiger Text), hell wie dunkel.
- **Eine Tabelle** für Daten in Spalten (Titel, Anzahl, Status); **Karten** (`nf-content-card`) für geschriebene Inhalte,
  die man an ihrem Auszug erkennt. Eine nach Familien geordnete Liste (E-Mail-Vorlagen) bleibt **eine einzige
  Tabelle**, mit einer Zeile `<tr class="nf-table-groupe">` am Anfang jeder Familie, statt einer Karte pro Familie. Ein
  Datum aus der Datenbank wird dort mit `nf_date_heure()` angezeigt, nie unverändert.
- **Der leere Zustand**: `admin_empty(symbol, titel, text)`, das sagt, was man für den Anfang tun soll.
- **Der Knopf, der ein Formular abschickt**: „Speichern“ für ein bestehendes Element — nie „Bearbeiten“, man bearbeitet
  ja schon —, „Hinzufügen“ oder „Anlegen“ für ein neues, wie der Titel der Seite („Ein Forum hinzufügen“).
- **Mehrere Ansichten desselben Themas** (Monitoring, Benutzer, Discord): Reiter `nf-local-nav` / `nf-local-tab` — als
  Links zu anderen Adressen oder als Knöpfe, die Panels umschalten.

## 8. Das Modul testen

Das Projekt hat ein Sicherheitsnetz, und ein neues Modul muss hinein:

- **Tests** — `tests/Unit` (ohne Datenbank), `tests/Headless` (das Framework gebootet, echte Modelle gegen die
  Testdatenbank, jeder Test in einer zurückgerollten Transaktion — `HeadlessTestCase`), `tests/Integration`
  (SQL-Spiegel), `tests/Browser/*.test.html` (der Vertrag eines Skripts in einem echten Browser, über
  `tools/check-js.php`). `vendor/bin/phpunit --fail-on-skipped`.
- **Prüfungen**, die vor der Auslieferung bestanden werden müssen: `check-addon-declarations`, `check-addon-coupling`,
  `check-addon-contracts`, `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (kein sichtbarer Text
  außerhalb von `lang()`), `check-actions-admin` (die Knopfregeln), `check-pagination`, `check-heures` (kein Datum ohne
  Zeitzone angezeigt), `check-db-compteurs`, `check-demo-lock` (ein Modul, das die Konfiguration berührt, ist in der
  Demo gesperrt), `check-vignettes` (sein Vorschaubild existiert, im richtigen Format), `check-strict-types` (der Zähler
  darf nie sinken — deklariere `declare(strict_types=1)` in deinen neuen Dateien), dann `check-install-profiles`
  (installiert jedes Profil wirklich und ruft die Routen fehlender Module auf, die ein **sauberes 404** zurückgeben
  müssen, nie ein 500) und `check-js-console` (öffnet die Verwaltungsseiten in einem Browser und lehnt jeden JS-Fehler
  ab).
- PHPStan: `vendor/bin/phpstan analyse`.

Die vollständige Liste steht in [`tools/README.md`](../../../tools/README.md); `php tools/check-all.php --navigateur` führt
sie alle aus.

## 9. Installieren und verteilen

1. Lege `modules/notes/` an seinen Platz.
2. **Verwaltung → Themes & Addons → Festplatte durchsuchen** → hake `notes` an → installiere (die Installation führt
   `install/install.sql` aus, setzt die Migrationen als Basis, setzt die Berechtigungen).
3. Zum Verteilen: Zippe den Ordner (`modules/notes/` im Wurzelverzeichnis des Archivs), er installiert sich über
   **Hinzufügen (ZIP)**. Um es im Katalog des Marketplace des Projekts zu veröffentlichen, muss es `'core' => FALSE` und
   kein `'distributed' => FALSE` haben; `tools/package-addons.php` zippt und trägt jedes optionale Addon mit seinem
   SHA-256-Fingerabdruck in `marketplace/catalog.json` ein.
4. Sein Vorschaubild, vor dem Packen: eine **Karte**, aus seinem `__info()` gezeichnet — sein Symbol, sein Name und seine
   französische Beschreibung —, in jeder Größe lesbar, auch wenn das Modul nichts zu zeigen hat:
   `php tools/vignettes-cartes.php --nom=notes --sortie=/tmp/cartes`, um sie anzusehen, dann `--poser`, um sie an ihren
   Platz zu setzen. Format und Prüfungen: [Ein Theme erstellen, § 5](create-a-theme.md).
