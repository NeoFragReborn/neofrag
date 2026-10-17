# Ein Theme erstellen

Ein **Theme** gibt der Seite ihr vollständiges Erscheinungsbild: das Layout (Navigation, Regionen, Fußzeile), den Stil
(Farben, Schriften) und die Standard-**Layouts** (welche Widgets, wo).

Wir skizzieren ein Theme `aurora`. Am einfachsten startest du, indem du **`themes/nebula/` klonst** (das mitgelieferte
öffentliche Theme) und es anpasst. Alles Folgende ist am Code geprüft.

## Dateistruktur

```
themes/aurora/
├── aurora.php                # Klasse: Zonen, Regionen, Assets, Layouts
├── views/
│   ├── body.tpl.php          # das Seitengerüst (stellt die Regionen dar)
│   └── live_editor/          # row.tpl.php, widget.tpl.php: im Live-Editor angebotene Stile
├── css/
│   ├── style.css             # der Stil — definiert das GANZE Vokabular --nf-*
│   └── sass/                 # optional: auf dem Server kompiliertes SCSS
├── js/
│   ├── theme.js              # heller/dunkler Modus des Themes
│   └── aurora.js             # Interaktionen des Themes
├── images/
│   └── thumbnail.jpg         # Vorschaubild 960 × 600: Addon-Karte und Marketplace
└── install/
    └── migrations/           # Änderungen der mitgelieferten Layouts (optional)
```

## 1. Die Klasse — `aurora.php`

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
            'author'      => 'Dein Name',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // die Kernversion, die es mindestens braucht — ohne sie lehnt „Hinzufügen“ das Archiv ab

            // Entkopplungsdeklarationen — PFLICHT.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // Die Zonen, der Reihe nach, mit ihrem TITEL (install() verwendet diesen Titel)…
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … und die benannten REGIONEN, die die Templates darstellen. PFLICHT für ein öffentliches Theme.
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
        // Die Assets jeder Seite, in Einbindungsreihenfolge. Die des Kerns zuerst: Bootstrap 5, die Basis des
        // Produkts (css/nf-bs5-bridge.css: seine Komponenten und die Farben von Bootstrap aus der Palette des Themes),
        // FontAwesome; dann der Stil des Themes; dann die Skripte.
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
        // … Standard-Layouts (siehe §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` deklariert die Zonen der Reihe nach; `regions` gibt ihnen einen **stabilen Namen**, den die Templates
  verwenden. `tools/check-addon-contracts.php` **verlangt** den Schlüssel `regions` bei jedem öffentlichen Theme: Er ist
  das Fundament der Page-Builder-Arbeit, und ein Template, das `zone(2)` darstellt, hinge von der Reihenfolge ab.
- **Kein jQuery**: Der Kern lädt es nicht mehr, und `tools/check-js-sources.php` lehnt jedes Skript ab, das es aufruft.
  Bootstrap 5 wird über `bootstrap.bundle.min` geladen (Popper eingeschlossen).
- `styles_row()` / `styles_widget()` stellen die Listen der Zeilen- und Panel-Stile dar, die im Live-Editor angeboten
  werden; kopiere `themes/nebula/views/live_editor/` für den Anfang.

## 2. Das Gerüst — `views/body.tpl.php`

`body.tpl.php` ist das HTML der Seite. Es **stellt die Regionen** über ihren Namen **dar**:

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

Die Absicherung `if ($zone = …)` sagt die Wahrheit: Eine deklarierte Region **ohne Widget** gibt einen leeren String
zurück (seit dem 17.09.2026; vorher ließen Leerzeichen ein verziertes Banner um nichts zeichnen). Du kannst also jede
Region gefahrlos in einen gestalteten Container packen.

> **Die Startseite erkennen**: `((string) $this->url->request === '')`.

Du bist frei: Entweder stellst du die Regionen unverändert dar (Navigation von den Widgets der Region `header`
gesteuert), oder du **programmierst eine eigene Navigationsleiste oder Fußzeile** im Template, wie Nebula, für eine
einzigartige Identität. Wenn du eine Fußzeile schreibst, **setze nicht zusätzlich** ein Copyright-Widget in die Region
`footer` deiner Layouts: Das Theme Extend zeigte es doppelt.

Wenn du Besuchern den Wechsel des Themes anbietest, schreibe `<?php echo nf_selecteur_theme() ?>` in deine Fußzeile: Der
Kern stellt das Menü der installierten Themes dar (außer `admin`) und merkt sich die Wahl in einem eigenen Cookie der
Seite. Es erscheint nur, wenn es mehr als ein öffentliches Theme gibt und der Administrator die Wahl nicht geschlossen
hat (**Allgemeine Einstellungen → Designauswahl**).

Für die Sprache rendert `<?php echo nf_selecteur_langue() ?>` ebenso das Menü der Sprachen der Website, wenn
sie mehrere hat: ein echtes Formular, das den Besucher auf dieselbe Seite zurückführt, in der gewählten Sprache.

Schreibe auch `<?php echo nf_liens_legaux() ?>` in deine Fußzeile, neben „Betrieben von“: Der Kern stellt die Links zum
Impressum und zur Datenschutzerklärung dar, wenn ihre Seiten veröffentlicht sind (**Einstellungen → Datenschutz**), und
**„Meine Cookies verwalten“**, immer. Dieser letzte Link ist der einzige Weg eines Besuchers, seine Wahl zu ändern: Die
Zustimmung zurückzuziehen muss so einfach sein, wie sie zu geben. Wenn deine Fußzeile nicht umbricht
(`white-space: nowrap`), lass wenigstens diese Links umbrechen: Am Telefon, an den Rest geklebt, lassen sie die Seite
überlaufen.

### Was oben am Bildschirm klebt

Drei Banner können den oberen Fensterrand belegen: das der Demo, das der Wartung und das der Rechtevorschau. Der Kern
stapelt sie, reserviert ihren Platz oben auf der Seite und veröffentlicht ihre Höhe in `--nf-haut`. Bleibt dein Kopf
beim Scrollen kleben, setze ihn darunter und markiere ihn mit `data-nf-entete`: Der Kern misst seine Höhe und
veröffentlicht sie in `--nf-entete`, an der sich wiederum die klebenden Spalten der Module (das Menü des
Mitgliederbereichs, das Inhaltsverzeichnis des Wikis) ausrichten.

```php
<header class="au-tete" data-nf-entete>…</header>
<aside class="au-cote" data-nf-colle>…</aside>
```

```css
.au-tete { position: sticky; top: var(--nf-haut, 0px); z-index: 1030; }
.au-cote { position: sticky; top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
```

Eine klebende Spalte, die höher als der Bildschirm sein kann, trägt `data-nf-colle`: Passt sie nicht in den ihr
verbleibenden Platz, löst der Kern sie — klebend bliebe ihr unteres Ende bis zum Ende der Seite außerhalb des
Bildschirms. Anker (`#section`) halten von selbst unter den Bannern und unter deinem Kopf an: Setze kein
`scroll-padding-top`. Ein oben fixiertes Element (`position: fixed`) richtet sich ebenso an `--nf-haut` aus; nur ein
Panel, das absichtlich die ganze Seite bedeckt (ein Fenster, eine Schublade), darf bei null beginnen. `check-colles`
lehnt ein oben klebendes oder fixiertes Element ab, das die Banner ignoriert: In der Demo verschwand der halbe Kopf von
vier Themes beim Scrollen unter dem eigenen.

## 3. Die Standard-Layouts — `install()`

`install()` beschreibt pro Seitenmuster und pro **Zonentitel** das Raster der Widgets, das bei der Installation des
Themes gesetzt wird:

```php
// Alle Seiten: Hauptmodul (8 Spalten) + Seitenspalte (4 Spalten).
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

// Nur die Startseite: ein Karussell über die volle Breite vor dem Inhalt.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...Zeilen])`: `$page` ist ein Muster (`*`, `/`, `forum/*`…), das genaueste gewinnt.
- `$this->widget($id)` erhält die **id** einer frisch eingefügten Zeile von `nf_widgets` (Widget, Typ, Einstellungen als
  JSON). Ein **ohne Einstellungen** gesetztes Widget muss zurechtkommen: Das ist die Aufgabe seines Checkers.
- `->size('col-12 col-lg-8')` — Bootstrap-5-Grid, **immer mit einem Breakpoint**: `col-8` allein gilt ab 0 px und
  quetscht die Seite am Telefon. `->style('row-default' | 'row-dark')` und
  `->style('panel-default' | 'panel-color' | 'panel-header')` gestalten Zeilen und Panels.
- Setze in mitgelieferte Layouts nur Widgets **des Kerns**: Das Paket installiert sich modular, ein optionales Widget ist
  vielleicht nicht da.
- Die **Menübezeichnungen**, die du setzt (Widget `navigation`, `$this->lang('Actualités')`), werden beim Anzeigen vom
  Widget unter seinem eigenen Schlüssel übersetzt: Jede muss in `widgets/navigation/langs/*.php` existieren, sonst
  bleibt sie auf einer Seite in einer anderen Sprache französisch. `check-langs` prüft es.

Eine bestehende Installation spielt `install()` nie erneut ab: Um ein mitgeliefertes Layout bei denen zu korrigieren, die
das Theme schon haben, schreibe eine **Theme-Migration** (`install/migrations/JJJJ_MM_TT_name.up.sql`, verfolgt in
`nf_addon_migrations`, beim Update ausgeführt). Ein echtes Beispiel: `themes/extend/install/migrations/`.

## 4. Der Stil — `css/style.css`

**Jedes Theme definiert das gesamte Vokabular `--nf-*`**, in `:root` (und in seiner hellen oder dunklen Variante). Die
Stylesheets von Modulen und Widgets verwenden nur dieses Vokabular; eine Variable, die ein Theme vergisst, fällt auf
einen Rückfallwert — so sind **weiße Blöcke** in dunklen Themes entstanden. `tools/check-css-variables.php` lehnt jede
von einem Modul oder Widget verwendete Variable ab, die nicht **alle** Themes definieren. Das vollständige Vokabular —
siebenundzwanzig Tokens — mit **Beispiel**werten (Nebula lässt jedes auf seine private Palette `--fg-*` zeigen, was ihm
einen hellen und einen dunklen Modus erlaubt, indem es nur diese Palette ändert):

```css
:root {
    /* Hintergründe und Flächen */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* Linien */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* Texte */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* Akzent — und die Farbe, die DARAUF liegt */
    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;
    --nf-on-accent: #041014;
    /* Zustände */
    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;
    --nf-danger-soft: rgba(239,68,68,.18);
    /* Formen */
    --nf-radius: 14px;  --nf-radius-sm: 8px;
    --nf-font-display: "Space Grotesk", system-ui, sans-serif;
}
```

Zwei an den mitgelieferten Themes gemessene Fallen:

- **`--nf-on-accent` muss neu berechnet werden, wenn sich der Akzent ändert.** Ersetzt deine zweite Palette (heller
  Modus, Variante) `--nf-accent`, definiere auch die Farbe darauf neu, sonst behält der Text der Knöpfe die des anderen
  Akzents — Weiß auf Türkis, 1,86:1. `tools/check-contraste.php` misst den WCAG-Kontrast aller Themes in beiden Modi.
- **Kein `.row { margin: -15px }`**: Das ist die Rinne von Bootstrap 4; Bootstrap 5 nutzt −12 px über
  `--bs-gutter-x`, und die 3 px Unterschied lassen die Seite überlaufen. `tools/check-mise-en-page.php` misst den
  horizontalen Überlauf jeder Seite, von 360 bis 2560 px, in jedem Theme und Modus; `tools/check-classes-bs4.php` lehnt aus Bootstrap **3 und 4**
  verschwundene Klassen ab (`card-columns`, `float-right`, `panel-body`, `badge-danger`…), in Templates wie in
  Stylesheets — `.badge-danger` in deinem Theme neu zu definieren erweckt sie nicht wieder, die Prüfung lehnt es ab —,
  `data-*`-Attribute ohne das Präfix `bs` — ein `data-target` tut still nichts mehr — und Klassen, die der Code durch
  Verkettung **erzeugt**. Ein Theme muss die Namen von Bootstrap 5 verwenden: `float-end`, `text-start`, `g-0`, `ms-2`,
  `bg-danger-subtle text-danger-emphasis`. Um diese Farben an deinen Stil anzupassen, definiere die Variablen von
  Bootstrap (`--bs-danger-text-emphasis`…) neu statt der Klassen.

### Einstellbare Farben

Ein Theme lässt über seine Konfigurationsseite drei Farben wählen — den Akzent, den Hintergrund, den Text —, mit einer
**Live-Vorschau** auf einer echten Seite der Website und der **Ablehnung einer unlesbaren Farbe**. Der Kern macht
alles; das Theme deklariert seine Ausgangsfarben in `controllers/admin.php`:

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

Sein Stylesheet ist dann ein **PHP-Template**, das die Einstellungen `<theme>_theme_color`,
`<theme>_background_color` und `<theme>_text_color` liest, mit denselben Ausgangswerten als Rückfall:

```php
<?php $accent = $this->config->aurora_theme_color ?: '#2dd4bf'; ?>
:root { --nf-accent: <?php echo $accent ?>; }
```

Ein Theme, das schon eine Konfigurationsseite hat, fügt ihr nur die Vorschau unter seinen Farbwählern hinzu:
`nf_apercu_couleurs($this->__caller, [...Ausgangsfarben])`. Ein echtes Beispiel: `themes/nebula/`.

### Die gemeinsame Basis der Themes — `css/nf-socle-themes.css` (optional)

Blockcraft, Chronique, Extend, Forge, Granite und Pulse stützen sich auf eine **gemeinsame Basis**: was ihre Stylesheets
gemeinsam hatten (der Reset, Links, Karten, Formulare, Listen, die Mitgliederleiste, Präsenzpunkte…), einmal im Kern
geschrieben, nur mit dem Vokabular `--nf-*`. Ein Theme übernimmt sie, indem es sie **vor** seinem eigenen Stylesheet
lädt, das seine Identität behält und alles neu definieren kann:

```php
$this->css('bootstrap.min')->css('nf-bs5-bridge')
     ->css('icons/fontawesome.min')
     ->css('nf-socle-themes')   // die gemeinsame Basis der Themes
     ->css('style')
     ->css('nf-apres-theme');
```

Sie verwendet zusätzlich zu den Tokens oben diese, die das ladende Theme definiert (die sechs Themes, die sie laden,
lassen sie auf ihre private Palette zeigen) — `tools/check-css-variables.php` lehnt ein Theme ab, das die Basis lädt,
ohne jedes verwendete Token zu definieren:

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

Ein Theme, das sie nicht lädt — Nebula oder ein Theme in einer früheren Version —, muss nichts ändern. Die Basis kam mit
Version 1.2.32 des Kerns: Ein Theme, das sie lädt, installiert sich nur auf einem Kern, der sie mitbringt (der
Marketplace liefert jeder Seite den Katalog ihrer eigenen Version).

Das CSS kann ein **PHP-Template** sein; das `?v=` beruht auf dem Datum der Datei, jede Bearbeitung macht den Cache
ungültig. Ein Ordner `css/sass/` wird auf dem Server kompiliert (scssphp), bei der Installation und über Verwaltung →
Werkzeuge.

**Heller / dunkler Modus.** Jedes Theme verwaltet seinen eigenen in `js/theme.js` (Attribut `data-theme` auf `<html>`,
Vorliebe im Browser unter **seinem eigenen Schlüssel** gespeichert, nicht unter einem gemeinsamen). Ein Theme „nur
dunkel“ erzwingt `document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. Das Vorschaubild — `images/thumbnail.jpg`

Die Karte des Themes unter **Themes & Addons** und seine Seite im Marketplace zeigen `images/thumbnail.jpg`: ein **JPEG
von 960 × 600** (16:10), höchstens 200 KB. Es ist ein **Foto der Startseite, wie ein Besucher sie sieht**, nie ein
Entwurf. `tools/capturer-apercus.php` nimmt es auf einer Seite auf, auf der das Theme installiert ist; mit
`--basculer-theme` macht es das Theme für die Dauer des Fotos zum Standard und stellt danach das alte wieder her. Tu es
auf einer Testseite mit dem Inhalt der Demo (`install/cli.php --demo`), nie auf einer laufenden Seite:

```bash
php tools/capturer-apercus.php --type=theme --nom=aurora --basculer-theme
```

`check-vignettes` lehnt dann ein Addon ohne Vorschaubild, ein Vorschaubild im falschen Format und zwei identische
Vorschaubilder ab; `check-marketplace` lehnt einen Katalogeintrag ohne Vorschau ab. Das Format und die wenigen
begründeten Ausnahmen leben in `tools/lib/vignettes.php`. Module und Widgets folgen demselben Format, aber ihr
Vorschaubild ist eine gezeichnete Karte (`tools/vignettes-cartes.php`), kein Foto: Ein Modul ohne Daten zeigte nur einen
leeren Rahmen, während sich ein Theme zeigt.

## 6. Installieren, aktivieren, testen

1. Lege `themes/aurora/` an seinen Platz.
2. **Verwaltung → Themes & Addons → Festplatte durchsuchen** → hake `aurora` an → installiere.
3. **Aktiviere** es auf seiner Karte (setzt das Standard-Theme). Wurde das Theme ohne Layouts registriert, startet die
   Aktivierung `install()` von selbst. Der Knopf **Standard wiederherstellen** führt `install()` erneut aus.
4. Vor der Auslieferung durchlaufen: `check-addon-declarations`, `check-addon-contracts` (der Schlüssel `regions`),
   `check-css-variables`, `check-colles`, `check-classes-bs4`, `check-js-sources`, dann `check-mise-en-page`,
   `check-contraste`, `check-bleu-bootstrap` (das Standardblau von Bootstrap, das in einer Komponente geblieben ist, die
   deine Palette nicht übernommen hat) und `check-js-console` auf einer Installation, auf der das Theme aktiv ist.
   Sieh dir die dargestellte Seite an: Ein leeres Banner, ein doppeltes Copyright oder ein unlesbarer Kommentar sind im
   Code nicht zu sehen.

## Gute Praxis

- **Schriften**: Liefere sie aus dem Theme oder dem Kern, nie von Google Fonts — die Sicherheitsrichtlinie erlaubt
  keinen Schriftursprung mehr (`font-src 'self' data:`), und ein solches Laden schickte die IP-Adresse jedes Besuchers an
  Google. `php tools/polices-locales.php --nom=…` lädt eine Schrift von Google Fonts nach `fonts/` herunter und schreibt
  ihr Stylesheet `css/fonts/<name>.css`, das das Theme mit `->css('fonts/<name>')` lädt.
- **Kein Skript von einem CDN**: Die strikte CSP (`script-src 'self' 'nonce-…'`) würde es ablehnen, und das Projekt
  hängt von keinem Dritten ab. Alles JS lebt im Theme oder im Kern.
- **Nenne** das ursprüngliche Projekt (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3), wenn du von einem bestehenden
  Theme ausgehst.
