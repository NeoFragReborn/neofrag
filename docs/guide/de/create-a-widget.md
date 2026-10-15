# Ein Widget erstellen

Ein **Widget** ist ein wiederverwendbarer Block, der sich mit dem Live-Editor in jede Zone eines Themes setzen lässt.
Es ist das am einfachsten zu schreibende Addon: eine Klasse, ein Controller, eine View.

Wir erstellen ein Widget `hello`, das eine einstellbare Begrüßung zeigt. Alles ist am Code geprüft; `widgets/html` und
`widgets/about` sind gute echte Beispiele.

## Dateistruktur

```
widgets/hello/
├── hello.php                 # die Klasse des Widgets (Metadaten, Deklarationen, Typen)
├── controllers/
│   ├── index.php             # der Controller: bereitet die View vor und stellt sie dar
│   ├── checker.php           # prüft und ergänzt die Einstellungen (empfohlen, sobald es Einstellungen gibt)
│   └── admin.php             # das Einstellungsformular (optional)
├── views/
│   ├── index.tpl.php         # das HTML-Template
│   └── admin.tpl.php         # Template der Einstellungen (optional)
├── css/
│   └── hello.css             # Stile (optional)
├── images/
│   └── thumbnail.jpg         # sein Vorschaubild, 960 × 600 — eine Karte: Symbol, Name, was es tut
└── js/
    └── hello.js              # optional — Vanilla, nie jQuery
```

## 1. Die Klasse — `hello.php`

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
            'icon'        => 'fas fa-hand-spock',   // PFLICHT, und muss im mitgelieferten FontAwesome existieren
            'author'      => 'Dein Name',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // die Kernversion, die es mindestens braucht — ohne sie lehnt „Hinzufügen“ das Archiv ab

            // Entkopplungsdeklarationen — PFLICHT (die CI lehnt ein stummes Addon ab).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // z. B. ['teams'], wenn das Widget nf_teams liest

            // Optional: mehrere Typen = mehrere Controller-Methoden, beim Setzen gewählt.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- Der `namespace` **muss** dem Ordner folgen: `NF\Widgets\<Name>`.
- `icon` ist Pflicht: Der Assistent zum Hinzufügen von Widgets im Live-Editor zeigt Widgets als **Symbolkarten**, und
  `tools/check-addon-declarations.php` lehnt einen im mitgelieferten FontAwesome fehlenden Namen ab (Symbole wurden
  zwischen Version 5 und 6 umbenannt).
- `core`, `presets`, `requires`: dieselben Regeln wie für ein Modul — siehe [Ein Modul erstellen](create-a-module.md),
  „Die drei Entkopplungsdeklarationen“.
- Ein Widget, das **zeigt, was ein Modul tut** — seine News, sein Forum, seine Galerie —, deklariert dieses Modul in
  `requires`, auch wenn es seinen Namen trägt: Ohne es, oder wenn es deaktiviert ist, wird das Widget nicht angezeigt
  und der Live-Editor bietet es nicht an; „Hinzufügen“ weigert sich, es allein zu installieren, und der Installer
  lässt es in einem Profil ohne das Modul weg. Seine `presets` sind die des Moduls
  (`tools/check-addon-declarations.php`, Regel 9). Ein Widget, das ohne das Modul lebt (es liest Tabellen des Kerns),
  deklariert es nicht und schützt nur, was davon abhängt, einen Link etwa, hinter `$this->module('x')` und
  `is_enabled()`.
- Die **Einstellungen** werden nicht in dieser Klasse deklariert, sondern in `controllers/admin.php` (§5).

## 2. Der Controller — `controllers/index.php`

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

Der Controller erhält die `$settings` — **bereits vom Checker geprüft und ergänzt** (§6) —, bereitet die Daten vor, lädt
sein CSS und stellt seine View dar. Die Kette `->css('hello')->view('index', $data)` ist das Standardmuster. Ein Widget
mit mehreren `types` hat eine Methode pro Typ.

## 3. Die View — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo nf_texte($message) ?></span>
</div>
```

**Maskiere immer**, was aus einer Einstellung oder der Datenbank kommt (`nf_texte()`). Ein von einem Mitglied
geschriebener Titel, auf der JS-Seite als `innerHTML` gesetzt, würde zu einer gespeicherten Injection, die allen
ausgeliefert wird.

`nf_texte()` dekodiert und maskiert dann: Ein Text, den die Website kodiert gespeichert hat („`&eacute;`“), wird
richtig angezeigt, wo `htmlspecialchars()` ihn ein zweites Mal kodieren würde — der Besucher läse „`&eacute;`“.
`check-double-codage` lehnt `htmlspecialchars()` im Produkt ab.

## 4. Das CSS — `css/hello.css` (optional)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**Verwende nur das gemeinsame Vokabular `--nf-*`**, das **alle** Themes definieren: `--nf-bg`, `--nf-bg-elevated`,
`--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`, `--nf-border`, `--nf-border-strong`, `--nf-text`,
`--nf-text-strong`, `--nf-text-soft`, `--nf-text-muted`, `--nf-muted`, `--nf-muted-2`, `--nf-accent`,
`--nf-accent-soft`, `--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`, `--nf-info`,
`--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`, `--nf-font-display`.

Eine Variable, die ein Theme nicht definiert, fiele auf ihren Rückfallwert — so sind **weiße Blöcke** in dunklen Themes
entstanden. `tools/check-css-variables.php` lehnt jede von einem Widget oder Modul verwendete Variable ab, die nicht
alle Themes definieren. Setze keinen Rückfallwert: Er würde den Fehler verbergen, statt ihn zu zeigen.

Grid: `col-12 col-lg-6`, nie `col-6` allein (Bootstrap 5 wendet es ab 0 px an).

## 5. Die Einstellungen — `controllers/admin.php` (optional)

Damit ein Widget **einstellbar** ist, füge `controllers/admin.php` hinzu: Es stellt das Einstellungsformular dar, und
seine Felder speisen die `$settings`, die der Controller `index` erhält. Die Klasse erweitert `Controller` (nicht
`Widget`):

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

Die View `views/admin.tpl.php` stellt die Felder dar; jedes Feld heißt `settings[<schlüssel>]`. Jede Zeile trägt
`nf-field`, die Feldklasse des Produkts: Sie gibt den Abstand, den jedes Theme für alle Formulare einstellt.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo nf_texte($message) ?>">
    </div>
</div>
```

Im Live-Editor zeigt der Assistent zum Hinzufügen den Schritt „Konfiguration“ **genau dann**, wenn dieser Controller
etwas darstellt: Ein Widget ohne Einstellungen hat einfach kein `controllers/admin.php`.

## 6. Einstellungen prüfen und ergänzen — `controllers/checker.php`

**Ein Widget kann ohne Einstellungen ankommen**: gesetzt vom `install()` eines Themes, im Live-Editor hinzugefügt, bevor
sein Formular geöffnet wurde, oder aus einem alten Layout wiederhergestellt. Sein Checker muss auch dann brauchbare
Einstellungen zurückgeben, wenn er keine erhält. Zwölf Widgets taten es nicht; eines verwendete einen fehlenden
Schlüssel als Divisor — Division durch null, totes Widget.

Die Methode `index($settings)` erhält die **rohen** Einstellungen und **gibt** ein Array **zurück**, **geprüft und mit
seinen Standardwerten ergänzt**, das zu den `$settings` des Controllers wird:

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // Der Rückfall zuerst: `+` füllt nur fehlende Schlüssel, die gelieferten Werte bleiben unberührt.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` lehnt jedes Lesen von `$settings['schlüssel']` ab, das weder in seiner Zeile
geschützt ist (`isset`, `??`, `empty`, `array_key_exists`) noch von diesem Rückfall am Anfang der Methode abgedeckt
wird. Ein vollständiges echtes Beispiel: `widgets/about/controllers/checker.php`.

## 7. JavaScript in einem Widget

- Lade es mit `->js('hello')` im Controller; die Datei ist `js/hello.js`.
- **Vanilla, ohne jQuery**: `NF.ready(fn)`, `NF.data(el, 'schlüssel')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (eingefügte `<script>` werden mit dem richtigen Nonce erneut
  ausgeführt). `tools/check-js-sources.php` lehnt `$(…)` ab.
- AJAX-Adressen werden aus dem Markup gelesen (`data-url="…"`) statt als in das `.js` interpoliertes PHP, was die Datei
  im Gerüst (`tests/Browser/*.test.html`) unverändert testbar macht.
- Ein Skript, das abstürzt, bevor es seine Listener anhängt, bricht nichts Sichtbares: `tools/check-js-console.php`
  öffnet die Seiten in einem echten Browser und sieht es.

## 8. Installieren, setzen, verteilen

1. Lege `widgets/hello/` auf deine Seite.
2. **Verwaltung → Themes & Addons → Festplatte durchsuchen**, hake `hello` an, installiere.
3. Füge es im **Live-Editor** einer Zone hinzu: Der Assistent bietet das Widget, seinen Typ, einen Titel und dann seine
   Konfiguration an, falls es eine hat. Ein Widget kann mehrmals gesetzt werden, mit verschiedenen Einstellungen.
4. Zum Verteilen: Zippe den Ordner (`widgets/hello/` im Wurzelverzeichnis des Archivs) — er installiert sich über
   **Hinzufügen (ZIP)** — oder lass `tools/package-addons.php` ihn in den Katalog des Marketplace eintragen.
5. Sein Vorschaubild: eine **Karte**, aus seinem `__info()` gezeichnet, wie die eines Moduls:
   `php tools/vignettes-cartes.php --nom=hello --sortie=/tmp/cartes`, um sie anzusehen, dann `--poser`. Format und
   Prüfungen: [Ein Theme erstellen, § 5](create-a-theme.md).

`tools/check-widget-contract.php` ruft jedes Paar Widget/Typ wirklich auf, um zu prüfen, dass keines beim Setzen bricht.
