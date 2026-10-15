# Das Framework

Eine Referenz der Bausteine, mit denen du beim Schreiben von Addons arbeitest. Für die vollständige interne Architektur
siehe [`docs/architecture.md`](../../architecture.md). Alles Folgende ist am Code geprüft.

## Der Service-Locator — `NeoFrag()`

Das ganze Framework ist über das globale Singleton `NeoFrag()` erreichbar und, in einer Addon-Klasse, über `$this`
(das dorthin delegiert). Dienste erhält man über magische Methoden:

```php
$this->db        // Datenbankzugriff
$this->config    // Konfiguration der Seite (nf_name, nf_default_theme…)
$this->user      // aktuelles Mitglied
$this->url       // Anfrage / Segmente / Basis
$this->lang(...) // Übersetzung
$this->events    // interne Ereignisse (fire / on)
$this->module('forum');        // ein Modul — NULL, wenn es nicht installiert ist
NeoFrag()->model2('addon');    // ein Modell
```

> `__call` **verschluckt unbekannte Aufrufe**: `$this->fehlende_methode()` bricht nichts. Um eine Prüfung zu testen,
> injiziere einen Fehler, den die Magie nicht abfängt (eine fehlende globale Funktion).

## Routing

Ein Modul deklariert seine Routen in `__info().routes`: `Muster => Methode`.

```php
'routes' => [
    ''                 => 'index',   // Startseite des Moduls
    '{id}/{url_title}' => '_show',   // /module/42/slug
    'admin{pages}'     => 'index',   // paginierte Verwaltung
],
```

- **Feste** Platzhalter: `{id}` (Ganzzahl), `{key_id}`, `{url_title}` (Slug), `{url_title*}`, `{page}` und `{pages}`
  (Paginierung). Ein unbekannter Platzhalter → stilles 404.
- Ablauf: Der **Checker** (`controllers/checker.php`) prüft die Route und lädt die Daten; was er **zurückgibt**, wird
  zu den Argumenten der gleichnamigen Methode des **Controllers** (`controllers/index.php`).
- Der heutige Router ist `Route → Modul → Seite → 404`. Die **benannten Regionen** der Themes (`region('content')`)
  sind das Fundament des künftigen seitenzentrierten Routings (Outlines, reservierte Routen), noch nicht geschrieben.

> In Tests oder mit `curl` behandelt das CMS Nicht-Browser-Agenten als Roboter und **öffnet keine Sitzung**: Sende einen
> **Browser-User-Agent**.

### Eine Ablehnung des Checkers steht im Protokoll

Scheitert ein Checker — ein fehlendes POST-Feld, ein nicht vorhandenes Ziel —, antwortet die Seite mit **404**; der
Grund (Route, Checker, Methode, fehlendes Feld und was stattdessen kam) wird **immer protokolliert** (`[checker] …`).
Ist das Debugging sichtbar — das Debug-Werkzeug eingeschaltet, für einen angemeldeten Administrator —, wird der Grund an
den Client zurückgegeben und der Status ist **400**: „deine Anfrage ist fehlerhaft“ statt „diese Adresse gibt es
nicht“. `post_check('a', 'b?')`: Das Suffix `?` macht ein Feld optional (`NULL`, wenn es fehlt).

## Datenbank

Ein flüssiger Query-Builder; Tabellen tragen das Präfix `nf_`.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // Array von Zeilen; ->row() für eine einzige

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // gibt die id zurück
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // oder ->rollback()
```

- **`table_exists('nf_teams')`** — wahr, wenn die Tabelle existiert. Das Paket installiert sich modular: Ein Modul des
  Kerns, das die Tabelle eines optionalen Moduls liest, **muss** sich so absichern (oder die Abhängigkeit in `requires`
  deklarieren). Das Ergebnis wird für die Anfrage zwischengespeichert.
- **`import($sql)`** — führt mehrteiliges SQL aus (DDL der Installation), außerhalb der Pipeline vorbereiteter
  Anfragen.
- Ein **Join, der nur zählt oder ausschmückt, muss `LEFT` sein**: Sieben Listen ließen jeden Inhalt ohne Kinder
  verschwinden (ein Forum ohne Nachricht, ein Album ohne Bild) durch einen strikten Join.
- Ein **Zähler** (Aufrufe, Klicks) auf einer Tabelle, deren `updated_at` `ON UPDATE current_timestamp()` trägt,
  schreibt das Änderungsdatum bei jedem Besuch neu: Die Wiki-Seite meldete „geändert am“ zur Zeit ihres letzten Lesers.
  Schreibe die Spalte auf sich selbst — `SET views = views + 1, updated_at = updated_at` —; `check-db-compteurs` lehnt
  das Vergessen ab.
- `MATCH … AGAINST` (FULLTEXT) **sieht keine** Zeile, die in einer nicht bestätigten Transaktion eingefügt wurde:
  InnoDB indexiert erst beim Commit. Ein FULLTEXT-Suchtest lässt sich nicht in die zurückgerollte Transaktion der
  Testbasis einpacken.

Für verwaltete Entitäten (Addons, Dateien…) geh über die **Modelle**: `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()`; um über eine typisierte Menge zu iterieren,
`NeoFrag()->collection('addon')->get()`.

## Formulare — `form()` & `form2()`

Zwei APIs bestehen nebeneinander (eine festgehaltene Entscheidung: v1 ist eingefroren, nicht massenhaft migriert):

- **`form()`** — die API der **Verwaltungsbildschirme**: Felder als Array über `add_rules([...])` deklariert, von
  `is_valid($post)` verarbeitet, mit `->display()` dargestellt. Ein vollständiges Beispiel in
  [Ein Modul erstellen](create-a-module.md) (§7).
- **`form2()`** — flüssig, jedes Feld ist ein `form_*()`-Objekt. Für **öffentliche** oder **reichhaltige** Formulare,
  und die **einzige**, die ein **reines Bestätigungsformular** (ohne Feld) prüft — eine Löschbestätigung akzeptiert
  **nur** das Feld `delete`: Ein hinzugefügtes Feld ließe sie still scheitern.

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

Beide tragen ihr eigenes CSRF-Token. Eine Aktion, die durch einen **Link** oder einen **von Hand geschriebenen POST**
ausgelöst wird, muss es selbst prüfen: `csrf_url()`, `check_csrf()`, `csrf_token()` (Trait `Admin_Helpers`); außerhalb
eines Controllers liest man dasselbe Token mit `nf_jeton_csrf()`.

**Der Rich-Editor** — der Typ `editor` in `form()`, `form_textarea()->editor()` in `form2()` — ist TinyMCE 8, selbst
gehostet; das HTML, das herauskommt, geht durch `sanitize_html()`. Ein **eingefügtes oder hineingezogenes** Bild wird an
die Seite geschickt (`ajax/user/editeur-image`: angemeldetes Mitglied, Sitzungstoken, begrenzte Rate) und dann über
seine endgültige Adresse eingefügt. Die Seite liest seinen echten Typ aus seinen Bytes (JPEG, PNG, GIF, WebP), lehnt
eine Datei mit Code ab, begrenzt Gewicht (5 MB) und Abmessungen, **kodiert es neu** — keine Metadaten mehr, die
Ausrichtung eines Fotos angewendet — und legt es unter `upload/editeur/JJJJ/MM/` mit zufälligem Namen ab. Ein von Hand
geschriebenes `tinymce.init()` (ein Template, ein eigener Bildschirm) setzt `Editeur_Images::tinymce()` an den Anfang
seiner Einstellungen (`NF\NeoFrag\Libraries\Editeur_Images`): Ohne das bleibt ein eingefügtes Bild kaputt und
verschwindet beim Speichern. `EditeurImagesTest` prüft es für das ganze Produkt.

**Das Captcha** — `->captcha()` in `form2()`, `add_captcha()` in `form()` — zeigt den unter *Einstellungen → Captcha*
gewählten Anbieter und prüft seine Antwort auf dem Server; ein angemeldetes Mitglied sieht keins. Standard ist
**ALTCHA**: von der Seite gehostet, ohne Schlüssel und ohne Cookie, lässt es den Browser eine kleine Rechnung ausführen
(etwa eine Sekunde), die der Server prüft, und verhindert, dass dieselbe Lösung zweimal dient. Turnstile, hCaptcha und
reCAPTCHA v2 brauchen ihre zwei Schlüssel; ohne sie übernimmt ALTCHA. Außerhalb eines Formulars:
`$this->captcha->element()` zum Anzeigen, `$this->captcha->is_valid()` zum Prüfen (`NF\NeoFrag\Libraries\Captcha`).

## Tabellen — `table2()`

Stellt paginierte Listen dar, **sortierbar per Klick auf den Kopf** (Umschalt + Klick: Sortierung über mehrere
Spalten; Strg + Klick: entfernen) und filterbar, aus einer Abfrage. Beispiel: die Mitgliederliste,
`modules/user/controllers/admin.php`. Die Sortierung liefert `js/table2.js`, in reinem JavaScript.

**Eine in Seiten geteilte Liste zeigt ihre Seitenlinks.** Teilt ein Checker eine Liste — `->paginate($page)` auf einer
Collection, `->pagination->get_data($lignes, $page)` auf einem Array —, ruft die Controller-Methode, die sie darstellt,
`->pagination->get_pagination()` auf (oder geht über `table2()`, das es selbst tut). Ohne das sind nur die ersten
Elemente sichtbar und die anderen unerreichbar. Eine Liste, die immer auf eine Seite passt, sagt es mit einem Kommentar
`// pagination : <raison>` in der Methode. `check-pagination` prüft es.

## Übersetzungen

`$this->lang('Schlüssel')` gibt die Übersetzung zurück. Die Dateien `langs/fr.php` ordnen den **crc32b des
Quellschlüssels** der Übersetzung zu:

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // oder direkt '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` nimmt Argumente an (im Stil von `sprintf`).
- **Französisch ist die Quellsprache**; sechs Sprachen werden geliefert, und `tools/check-langs.php --toutes` lehnt
  einen Schlüssel ab, der in einer von ihnen fehlt. `--fix` fügt die fehlenden Schlüssel **nur dem Französischen**
  hinzu.
- **Übersetze nie Inhalte aus der Datenbank** (einen vom Administrator geschriebenen Titel): `lang()` sucht ihn
  vergeblich und protokolliert bei jedem Besuch eine Warnung. Pack ihn in `$this->no_translate(...)`.

### Übersetzte Inhalte: `join_lang()` und `ecrire_lang()`

Der **Inhalt** eines Moduls (ein Titel, eine Beschreibung) wird anders übersetzt: Er lebt in einer Tabelle
`<table>_lang`, eine Zeile pro Sprache, und was keine Sprache hat (ein Datum, ein Bild, ein Preis), bleibt in der
Haupttabelle.

```sql
CREATE TABLE IF NOT EXISTS nf_notes_lang (
    note_id INT UNSIGNED NOT NULL,
    lang    VARCHAR(5)   NOT NULL,
    title   VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (note_id, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Lesen**: `join_lang()` verknüpft die **beste** Übersetzung — die der angefragten Sprache, sonst die französische,
  sonst die zuerst geschriebene. Ein in nur einer Sprache geschriebener Inhalt bleibt so in den anderen sichtbar, statt
  zu verschwinden wie bei einem nach Sprache gefilterten Join.

  ```php
  $this->db->select('n.id', 'nl.title')->from('nf_notes n')->join_lang('nf_notes_lang nl', 'note_id', 'n.id')->get();
  ```

- **Schreiben**: `ecrire_lang()` legt die Zeile an, wenn sie fehlt — ein nach Sprache gefiltertes `update()` schrieb
  beim ersten Übersetzen nichts, und der eingegebene Text ging verloren. Gib alle Pflichtspalten der Tabelle an.

  ```php
  $this->db->ecrire_lang('nf_notes_lang', ['note_id' => $id, 'lang' => $langue], ['title' => $titre]);
  ```

- **Der Bearbeitungsbildschirm** schreibt in der Sprache der Verwaltung und sagt es unter dem Feld („Für %s
  geschrieben…“): Man übersetzt, indem man die Sprache wechselt. Was Administrator und Besucher sehen:
  [Eine Seite in mehreren Sprachen](multilingue.md).

## Daten und Zeitzonen

Das Produkt **speichert** seine Daten in der Zeitzone, die PHP beim Start hat — in der Produktion die Weltzeit — und
ändert sie nie: Die Datenbank richtet ihre Sitzung danach aus (`drivers/mysqli.php`). Es **zeigt** sie in der Zeitzone
dessen, der hinschaut, gegeben von `nf_fuseau()`: die Wahl des Mitglieds (`nf_user_profile.timezone`, beim Öffnen der
Sitzung angewendet), sonst die seines Browsers (Cookie `nf_fuseau`, gesetzt von `views/theme/main.tpl.php`), sonst die
Einstellung der Seite (`nf_timezone`), sonst die Speicherzeitzone. Alles steht in `neofrag/helpers/time.php`.

- **In die Datenbank schreiben**: `date('Y-m-d H:i:s')`, `NOW()` oder `now()` — die Speicherzeitzone.
- **Anzeigen**: `timetostr($format, $wann)` oder die Bibliothek Date (`$this->date($wann)`); nie `date()`, das
  `check-heures` außerhalb von Maschinenformaten ablehnt. Für eine unverändert gelesene Datum-Uhrzeit-Spalte (eine
  Liste, eine Karte): `nf_date_heure($valeur)` — `d/m/Y H:i` in Sprache und Zeitzone des Besuchers, ein leerer String,
  wenn der Wert leer ist; für ein Datum allein `nf_date($valeur)`. Nie ein in `timetostr()` fest kodiertes Zahlenformat
  (`'d/m/Y'`, `'Y-m-d H:i'`): Deutsch schreibt `02.10.2026`, und `check-heures` lehnt es ab.
- **Eine Eingabe lesen**: Die `datetime`-Felder beider Formularsysteme rechnen selbst um; von Hand `nf_heure_saisie()`
  (und `nf_heure_affichee()` für die Gegenrichtung).
- **Ein Datum allein oder eine Uhrzeit allein** (`Y-m-d`, `H:i`) ist kein Zeitpunkt: `timetostr()` und Date rechnen es
  nicht um. Ein ganzer Tag, der in einer Datum-Uhrzeit-Spalte gespeichert ist, wird als `Y-m-d` übergeben (siehe
  `Calendar::format_dt()`).
- **Eine Uhrzeit in der Zeit der Seite**, unabhängig vom Besucher (ein Sendeplan):
  `new \DateTime('now', nf_fuseau_site())`.

## Das Frontend: Bootstrap 5, Vanilla, strikte CSP

- **Bootstrap 5.3**, **ohne jQuery**. Grid-Klassen tragen immer einen Breakpoint (`col-12 col-lg-8`);
  `tools/check-classes-bs4.php` lehnt Klassen von Bootstrap 4 ab.
- **Strikte CSP mit Nonce**: Jede HTML-Antwort trägt ein zufälliges Nonce, das der Filter von `index.php` auf alle
  Inline-`<script>` setzt, und `script-src` erlaubt nur `'self'`, dieses Nonce, die Ursprünge des **aktiven**
  Captcha-Anbieters — keine bei ALTCHA, dem Standard — und Google Analytics, **wenn** eine Kennung eingerichtet ist.
  **Kein Skript von einem CDN.** Dynamisch eingefügtes JS geht über `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`,
  die die hinzugefügten `<script>` mit dem richtigen Nonce erneut ausführen, auch im iframe des Live-Editors.
- **`window.NF`**, im Haupt-Template definiert, ersetzt die wenigen Primitive, die man brauchte — und nicht mehr (es ist
  kein Mini-jQuery):

| Primitive | Rolle |
|---|---|
| `NF.ready(fn)` | führt `fn` aus, wenn das DOM bereit ist (oder sofort, wenn es das schon ist) |
| `NF.data(el, 'mein-schluessel')` | liest `data-mein-schluessel` mit der Typumwandlung von jQuery (Zahl, Boolean, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` mit `X-Requested-With` auf demselben Ursprung, `form-urlencoded`-Body, Arrays als `key[]`; **lehnt** bei einem Fehlerstatus **ab**; `dataType: 'text'`, sonst JSON |
| `NF.post(url, data)` | POST-Abkürzung |
| `NF.setHtml(el, html)` | `innerHTML` + Ausführen der Skripte |
| `NF.insertHtml(ziel, position, html)` | `insertAdjacentHTML` + Ausführen nur der hinzugefügten Skripte |
| `NF.replaceHtml(el, html)` | ersetzt das Element, Skripte werden ausgeführt |
| `NF.runScripts(root)`, `NF.loadScript(src)` | erneut ausführen, mit dem Nonce laden |
| `NF.bandeaux()` | ordnet die Banner oben auf der Seite und veröffentlicht `--nf-haut` und `--nf-entete` neu (siehe *Ein Theme erstellen*); nach dem Hinzufügen eines Banners erneut aufrufen |

- Modale (`js/modal.js`), Benachrichtigungen (`js/notify.js`) und Bestätigungen (`js/confirm.js`) werden von den Themes
  geladen.
- Zwei Prüfungen schützen dieses Frontend: `check-js-sources` (Syntax, interpoliertes PHP geduldet, und kein `$(`),
  `check-js-console` (die echten Seiten, in einem echten Browser: kein Fehler, keine CSP-Verletzung). Das Gerüst
  `tests/Browser/*.test.html` + `tools/check-js.php` testet den **Vertrag** eines Skripts an der echten Datei, mit
  `fetch`-Doubles.

## Sicherheit: was es schon gibt

Bevor du deins schreibst: `Rate_Limit` (pro Schlüssel, z. B. `contact:ip:<ip>`), `Audit_Log` (sensible Aktionen),
`File_Jail` (Upload-Pfade), `Moderation`, TOTP für die Zwei-Faktor-Authentifizierung, `sanitize_html()` (HTMLPurifier)
für reichhaltiges HTML, `is_dangerous_upload()`, und der Demo-Modus (`nf_demo()`), der das Schreiben sensibler Module
sperrt.

**Eine von jemandem eingegebene Adresse, die in ein `href` oder `src` kommt**, geht durch `nf_url_sure($url)`: Es gibt
`FALSE` für ein anderes Schema als `http`, `https`, `mailto`, `tel` oder `geo` zurück — ein `javascript:` würde beim
Klick mit den Rechten des Klickenden ausgeführt. Eine relative Adresse geht durch. `nf_texte()` genügt nicht:
Es maskiert Anführungszeichen, nicht das Schema. `url()` gibt `#` für eine abgelehnte absolute Adresse zurück.

### Die Demo-Seite

Die Demo wird geteilt: Ihr Konto `demo` ist Administrator. Das Sicherheitsnetz steckt in `neofrag/helpers/system.php`
und hält vor jedem Controller (`core/output.php`):

- **`NF_DEMO_MODULES_VERROUILLES`** — die Module, in denen in der Demo keine Aktion durchgeht: jeder POST, jeder Link mit
  einem Token (`?_=`), jede Methode, deren Name ein Aktionswort enthält (`NF_DEMO_MOTS_D_ACTION`: export, test,
  toggle…), wird abgelehnt — ein JSON-403 für einen AJAX-Aufruf, sonst eine Benachrichtigung und die Rückkehr zur
  vorigen Seite (`nf_demo_requete_refusee()`). **Ein neues Modul, das die Konfiguration der Seite berührt, kommt auf
  diese Liste**: Es ist eine Sperrliste, ein fehlendes Modul ist offen. `check-demo-lock` erinnert daran.
- **`NF_DEMO_LECTURES`** — die wenigen erlaubten Lesezugriffe in einem gesperrten Modul (`controleur::methode`), die
  dann fiktive Beispiele zurückgeben.
- **`nf_demo_ecriture_permise($module)`** — die Frage, die in Code zu stellen ist, der außerhalb des Netzes schreibt;
  außerhalb der Demo wahr.
- **`nf_compte_masque()`** — das Notfallkonto der Demo, das keine Mitgliederliste zeigt:
  `->where('u.id !=', nf_compte_masque())`. Außerhalb der Demo ist es 0.
- In der Demo löscht `File::delete()` nichts, und Datei-Uploads werden abgelehnt. Eine Seite, die eine Datei, ein
  Protokoll oder eine sensible Einstellung zeigen würde, zeigt stattdessen ein Beispiel.
- Das geteilte Konto lässt sich im Mitgliederbereich nicht ändern: weder Benutzername, Adresse, Passwort,
  Zwei-Faktor-Authentifizierung noch Löschung — ein Besucher sperrte die anderen bis zum Zurücksetzen aus. Da das Netz
  nur die Verwaltung abdeckt, prüfen diese Seiten es selbst (`nf_demo()`, `modules/user/controllers/index.php`).

## Ereignisse

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` auf der auslösenden Seite;
`$this->events->on('forum.post.created', function (...) { … })` auf der abonnierenden Seite. Listener bleiben für die
ganze Anfrage aktiv.

## Nützliche Helfer

- `url('forum/42')` — baut eine geroutete URL (Sprachpräfix eingeschlossen).
- `notify('Nachricht')` — eine Bildschirmbenachrichtigung nach einer Umleitung.
- `nf_texte(...)` / `sanitize_html(...)` — Maskieren / Anti-XSS-Bereinigung.
- `nf_url_sure($url)` — darf eine eingegebene Adresse in einen Link (siehe Sicherheit).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — ein Datum aus der Datenbank, zum Anzeigen (siehe Daten).
- `nf_demo()` — wahr auf der Demo-Seite.

## Migrationen

Das Schema des **Kerns** entwickelt sich über versionierte Migrationen, verfolgt in `nf_migrations`:

```bash
php tools/migrate.php status                 # angewendet / ausstehend
php tools/migrate.php up [--pretend]          # wendet an (Simulation mit --pretend)
php tools/migrate.php down [--step=N]         # macht rückgängig
php tools/migrate.php baseline --until=NAME   # eine bestehende Datenbank übernehmen
```

Dateien: `migrations/JJJJ_MM_TT_name.up.sql` (+ `.down.sql`). MySQL-DDL bestätigt automatisch → **vor `up` in der
Produktion sichern**. **Addons** haben ihre eigenen Migrationen (`<addon>/install/migrations/`, verfolgt in
`nf_addon_migrations`) — siehe [Ein Modul erstellen](create-a-module.md).

**Neuer Code wendet seine Migrationen selbst an**, einmal, auf der ersten nach dem Update aufgerufenen Seite — über den
Knopf wie über FTP: die des Kerns, dann die der installierten Addons (`nf_migrations_du_code()`, aufgerufen von
`index.php`, wenn die Einstellung `nf_migrations_version` von `NEOFRAG_VERSION` abweicht). Das gilt für jede Seite,
die Demo eingeschlossen. Ein Fehlschlag hindert die Seite nicht am Anzeigen: Er wird protokolliert, und der nächste
Versuch wartet zehn Minuten.

## Überschreiben auf drei Ebenen

Auflösung von Views, Klassen und Dateien (die zuerst gefundene gewinnt):

1. `overrides/{type}/{file}` — global
2. `themes/{aktives Theme}/overrides/{type}/{file}` — pro Theme
3. das mitgelieferte Original

Du passt an, ohne zu forken und ohne Updates zu brechen. Vorsicht: zwei gleichnamige Dateien auf zwei Ebenen, und
`path()` liefert je nach Aufrufer die eine oder die andere — ein Assistent wurde wegen eines Duplikats ohne Stil
angezeigt (`tools/check-assets.php` lehnt das jetzt ab).

## Entwicklungsumgebung

Das Projekt läuft auf **jedem PHP 8.2+ mit MySQL oder MariaDB** (Apache + `mod_rewrite`, oder nginx, oder Caddy). Der
Referenzweg des Projekts ist eine **Testinstallation auf einem Server**, auf der die ganze Batterie läuft:
`php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped` und `composer stan`. Die Umgebung, die
Testdatenbank und die Arbeitsschleife beschreibt [`docs/development.md`](../../development.md); jedes Werkzeug ist in
[`tools/README.md`](../../../tools/README.md) beschrieben.
