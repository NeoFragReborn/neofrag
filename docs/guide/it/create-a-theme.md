# Creare un tema

Un **tema** dà al sito tutto il suo aspetto: l'impaginazione (navigazione, regioni, piè di pagina), lo stile (colori,
caratteri) e le **disposizioni** predefinite (quali widget, dove).

Abbozziamo un tema `aurora`. Il modo più semplice per cominciare è **clonare `themes/nebula/`** (il tema pubblico
incluso) e adattarlo. Tutto ciò che segue è verificato sul codice.

## Struttura dei file

```
themes/aurora/
├── aurora.php                # classe: zone, regioni, risorse, disposizioni
├── views/
│   ├── body.tpl.php          # lo scheletro della pagina (mostra le regioni)
│   └── live_editor/          # row.tpl.php, widget.tpl.php: stili offerti nell'Editor live
├── css/
│   ├── style.css             # lo stile — definisce TUTTO il vocabolario --nf-*
│   └── sass/                 # facoltativo: SCSS compilato sul server
├── js/
│   ├── theme.js              # la modalità chiara/scura del tema
│   └── aurora.js             # le interazioni del tema
├── images/
│   └── thumbnail.jpg         # miniatura 960 × 600: scheda dell'addon e marketplace
└── install/
    └── migrations/           # modifiche alle disposizioni incluse (facoltativo)
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
            'title'       => 'Aurora',
            'description' => $this->lang('Thème communautaire sombre et néon.'),
            'author'      => 'Il Tuo Nome',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versione del nucleo che richiede come minimo — senza, «Aggiungi» rifiuta l'archivio

            // Dichiarazioni di disaccoppiamento — OBBLIGATORIE.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // Le zone, in ordine, con il loro TITOLO (install() usa questo titolo)…
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … e le REGIONI con nome che mostrano i modelli. OBBLIGATORIE per un tema pubblico.
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
        // Le risorse di ogni pagina, in ordine di inclusione. Prima quelle del nucleo: Bootstrap 5, la base del
        // prodotto (css/nf-bs5-bridge.css: i suoi componenti e i colori di Bootstrap presi dalla tavolozza del tema),
        // FontAwesome; poi lo stile del tema; poi gli script.
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
        // … disposizioni predefinite (vedi §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` dichiara le zone in ordine; `regions` dà loro un **nome stabile** che usano i modelli.
  `tools/check-addon-contracts.php` **esige** la chiave `regions` su ogni tema pubblico: è la base del lavoro sul
  costruttore di pagine, e un modello che mostrasse `zone(2)` dipenderebbe dall'ordine.
- **Niente jQuery**: il nucleo non lo carica più, e `tools/check-js-sources.php` rifiuta ogni script che lo chiama.
  Bootstrap 5 si carica con `bootstrap.bundle.min` (Popper compreso).
- `styles_row()` / `styles_widget()` mostrano gli elenchi degli stili di riga e di pannello offerti nell'Editor live;
  copia `themes/nebula/views/live_editor/` per cominciare.

## 2. Lo scheletro — `views/body.tpl.php`

`body.tpl.php` è l'HTML della pagina. **Mostra le regioni** con il loro nome:

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

La protezione `if ($zone = …)` dice il vero: una regione dichiarata **senza widget** restituisce una stringa vuota (dal
2026-09-17; prima, degli spazi facevano disegnare un banner decorato attorno al nulla). Puoi quindi avvolgere ogni
regione in un contenitore stilizzato senza rischi.

> **Riconoscere la pagina iniziale**: `((string) $this->url->request === '')`.

Sei libero: o mostri le regioni così come sono (navigazione guidata dai widget della regione `header`), o **programmi
una barra di navigazione o un piè di pagina su misura** nel modello, come Nebula, per un'identità unica. Se scrivi un
piè di pagina, **non mettere anche** un widget di copyright nella regione `footer` delle tue disposizioni: il tema Extend
lo mostrava due volte.

Se lasci che i visitatori cambino tema, scrivi `<?php echo nf_selecteur_theme() ?>` nel tuo piè di pagina: il nucleo
mostra il menu dei temi installati (tranne `admin`) e ricorda la scelta in un cookie del sito stesso. Si mostra solo se
c'è più di un tema pubblico, e se l'amministratore non ha chiuso la scelta (**Preferenze generali → Scelta del tema**).

Per la lingua, `<?php echo nf_selecteur_langue() ?>` mostra allo stesso modo il menu delle lingue del sito,
quando ne ha più d'una: un vero modulo, che riporta il visitatore alla stessa pagina, nella lingua scelta.

Scrivi anche `<?php echo nf_liens_legaux() ?>` nel tuo piè di pagina, accanto a «Realizzato con»: il nucleo mostra i
link alle note legali e all'informativa sulla privacy, quando le loro pagine sono pubblicate (**Impostazioni →
Privacy**), e **«Gestisci i miei cookie»**, sempre. Quest'ultimo link è l'unico mezzo che un visitatore ha per cambiare
le sue scelte: ritirare il consenso deve essere facile quanto darlo. Se la riga del tuo piè di pagina non va a capo
(`white-space: nowrap`), lascia almeno andare a capo questi link: su un telefono, attaccati al resto, fanno debordare
la pagina.

### Ciò che resta incollato in cima allo schermo

Tre banner possono occupare la parte alta della finestra: quello della demo, quello della manutenzione e quello
dell'anteprima dei permessi. Il nucleo li impila, riserva il loro posto in cima alla pagina e pubblica la loro altezza in
`--nf-haut`. Se la tua intestazione resta incollata durante lo scorrimento, mettila sotto di loro e marcala con
`data-nf-entete`: il nucleo misura la sua altezza e la pubblica in `--nf-entete`, su cui si allineano a loro volta le
colonne incollate dei moduli (il menu dell'area membri, l'indice del wiki).

```php
<header class="au-tete" data-nf-entete>…</header>
<aside class="au-cote" data-nf-colle>…</aside>
```

```css
.au-tete { position: sticky; top: var(--nf-haut, 0px); z-index: 1030; }
.au-cote { position: sticky; top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
```

Una colonna incollata che può essere più alta dello schermo porta `data-nf-colle`: quando non sta nello spazio che le
viene lasciato, il nucleo la scolla — incollata, la sua parte bassa resterebbe fuori schermo fino alla fine della
pagina. Le ancore (`#section`) si fermano da sole sotto i banner e sotto la tua intestazione: non mettere
`scroll-padding-top`. Un elemento fisso in alto (`position: fixed`) si allinea anch'esso su `--nf-haut`; solo un
pannello che copre di proposito tutta la pagina (una finestra, un cassetto) può partire da zero. `check-colles` rifiuta
un elemento incollato o fisso in alto che ignora i banner: nella demo, metà dell'intestazione di quattro temi spariva
sotto la sua appena si scorreva.

## 3. Le disposizioni predefinite — `install()`

`install()` descrive, per schema di pagina e per **titolo di zona**, la griglia di widget collocata quando si installa il
tema:

```php
// Tutte le pagine: modulo principale (8 colonne) + colonna laterale (4 colonne).
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

// Solo la pagina iniziale: un carosello a larghezza piena prima del contenuto.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...righe])`: `$page` è uno schema (`*`, `/`, `forum/*`…), vince il più preciso.
- `$this->widget($id)` riceve l'**id** di una riga `nf_widgets` appena inserita (widget, tipo, impostazioni in JSON). Un
  widget collocato **senza impostazioni** deve cavarsela: è il lavoro del suo checker.
- `->size('col-12 col-lg-8')` — griglia di Bootstrap 5, **sempre con un breakpoint**: `col-8` da solo si applica da 0 px
  e schiaccia la pagina sui telefoni. `->style('row-default' | 'row-dark')` e
  `->style('panel-default' | 'panel-color' | 'panel-header')` danno stile alle righe e ai pannelli.
- Colloca solo widget **del nucleo** nelle disposizioni incluse: il pacchetto si installa à la carte, un widget
  opzionale potrebbe non esserci.
- Le **etichette di menu** che metti (il widget `navigation`, `$this->lang('Actualités')`) vengono tradotte dal widget
  alla visualizzazione, con la sua chiave: ognuna deve esistere in `widgets/navigation/langs/*.php`, altrimenti resta
  in francese su un sito in un'altra lingua. `check-langs` lo verifica.

Un'installazione esistente non riesegue mai `install()`: per correggere una disposizione inclusa per chi ha già il tema,
scrivi una **migrazione del tema** (`install/migrations/YYYY_MM_DD_name.up.sql`, registrata in `nf_addon_migrations`,
eseguita all'aggiornamento). Un esempio reale: `themes/extend/install/migrations/`.

## 4. Lo stile — `css/style.css`

**Ogni tema definisce tutto il vocabolario `--nf-*`**, in `:root` (e nella sua variante chiara o scura). I fogli di stile
dei moduli e dei widget usano solo questo vocabolario; una variabile che un tema dimentica ricade su un valore di
riserva — è così che sono comparsi **blocchi bianchi** nei temi scuri. `tools/check-css-variables.php` rifiuta ogni
variabile usata da un modulo o da un widget che **tutti** i temi non definiscono. Il vocabolario completo — ventisette
token — con valori **di esempio** (Nebula punta ciascuno sulla sua tavolozza privata `--fg-*`, il che gli permette di
avere una modalità chiara e una scura cambiando solo quella tavolozza):

```css
:root {
    /* sfondi e superfici */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* linee */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* testi */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* accento — e il colore posato SOPRA */
    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;
    --nf-on-accent: #041014;
    /* stati */
    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;
    --nf-danger-soft: rgba(239,68,68,.18);
    /* forme */
    --nf-radius: 14px;  --nf-radius-sm: 8px;
    --nf-font-display: "Space Grotesk", system-ui, sans-serif;
}
```

Due trappole misurate sui temi inclusi:

- **`--nf-on-accent` va ricalcolato quando cambia l'accento.** Se la tua seconda tavolozza (modalità chiara, una
  variante) sostituisce `--nf-accent`, ridefinisci anche il colore posato sopra, altrimenti il testo dei pulsanti
  conserva quello dell'altro accento — bianco su turchese, 1,86:1. `tools/check-contraste.php` misura il contrasto WCAG
  di ogni tema nelle due modalità.
- **Niente `.row { margin: -15px }`**: è la spaziatura di Bootstrap 4; Bootstrap 5 usa −12 px tramite `--bs-gutter-x`, e
  i 3 px di differenza fanno debordare la pagina. `tools/check-mise-en-page.php` misura il debordamento orizzontale
  di ogni pagina, da 360 a 2560 px, in ogni tema e modalità; `tools/check-classes-bs4.php` rifiuta le classi scomparse da Bootstrap **3 e 4** (`card-columns`,
  `float-right`, `panel-body`, `badge-danger`…), nei modelli come nei fogli di stile — ridefinire `.badge-danger` nel
  tuo tema non la fa tornare, il controllo lo rifiuta —, gli attributi `data-*` rimasti senza il prefisso `bs` — un
  `data-target` non fa più nulla, in silenzio — e le classi che il codice **costruisce** per concatenazione. Un tema deve
  usare i nomi di Bootstrap 5: `float-end`, `text-start`, `g-0`, `ms-2`, `bg-danger-subtle text-danger-emphasis`. Per
  tingere questi colori al tuo stile, ridefinisci le variabili di Bootstrap (`--bs-danger-text-emphasis`…) invece delle
  classi.

### Colori regolabili

Un tema lascia scegliere tre colori — l'accento, lo sfondo, il testo — nella sua pagina di configurazione, con
un'**anteprima dal vivo** su una vera pagina del sito e il **rifiuto di un colore illeggibile**. Il nucleo fa tutto; il
tema dichiara i suoi colori originali in `controllers/admin.php`:

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

Il suo foglio di stile è allora un **modello PHP** che legge le impostazioni `<tema>_theme_color`,
`<tema>_background_color` e `<tema>_text_color`, con gli stessi valori originali come riserva:

```php
<?php $accent = $this->config->aurora_theme_color ?: '#2dd4bf'; ?>
:root { --nf-accent: <?php echo $accent ?>; }
```

Un tema che ha già la sua pagina di configurazione vi aggiunge solo l'anteprima, sotto i suoi selettori:
`nf_apercu_couleurs($this->__caller, [...colori originali])`. Un esempio reale: `themes/nebula/`.

### La base comune dei temi — `css/nf-socle-themes.css` (facoltativo)

Blockcraft, Chronique, Extend, Forge, Granite e Pulse si appoggiano su una **base comune**: ciò che i loro fogli di stile
avevano in comune (il reset, i link, le schede, i moduli, gli elenchi, la barra del membro, i punti di presenza…),
scritto una sola volta nel nucleo, solo con il vocabolario `--nf-*`. Un tema la adotta caricandola **prima** del suo
foglio di stile, che conserva la sua identità e può ridefinire tutto:

```php
$this->css('bootstrap.min')->css('nf-bs5-bridge')
     ->css('icons/fontawesome.min')
     ->css('nf-socle-themes')   // la base comune dei temi
     ->css('style')
     ->css('nf-apres-theme');
```

Oltre ai token di sopra, usa questi, che il tema che la carica definisce (i sei temi che la caricano li puntano sulla
loro tavolozza privata) — `tools/check-css-variables.php` rifiuta un tema che carica la base senza definire ogni token
che usa:

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

Un tema che non la carica — Nebula, o un tema ancora a una versione precedente — non ha nulla da cambiare. La base è
arrivata con la versione 1.2.32 del nucleo: un tema che la carica si installa solo su un nucleo che la porta (il
marketplace serve a ogni sito il catalogo della sua versione).

Il CSS può essere un **modello PHP**; il `?v=` si basa sulla data del file, qualsiasi modifica invalida la cache. Una
cartella `css/sass/` viene compilata sul server (scssphp) all'installazione e da Amministrazione → Strumenti.

**Modalità chiara / scura.** Ogni tema gestisce la sua in `js/theme.js` (un attributo `data-theme` su `<html>`, la
preferenza ricordata dal browser con **la sua chiave**, non una condivisa). Un tema «solo scuro» forza
`document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. La miniatura — `images/thumbnail.jpg`

La scheda del tema in **Temi & Addons** e la sua pagina nel marketplace mostrano `images/thumbnail.jpg`: un **JPEG di
960 × 600** (16:10), 200 KB al massimo. È una **foto della pagina iniziale come la vede un visitatore**, mai un mockup.
`tools/capturer-apercus.php` la scatta su un sito in cui il tema è installato; con `--basculer-theme`, lo rende il tema
predefinito per il tempo della foto, poi ripristina il precedente. Fallo su un sito di test riempito con il contenuto
della demo (`install/cli.php --demo`), mai su un sito in funzione:

```bash
php tools/capturer-apercus.php --type=theme --nom=aurora --basculer-theme
```

`check-vignettes` rifiuta poi un addon senza miniatura, una miniatura nel formato sbagliato e due miniature identiche;
`check-marketplace` rifiuta una voce del catalogo senza anteprima. Il formato e le poche eccezioni motivate vivono in
`tools/lib/vignettes.php`. I moduli e i widget seguono lo stesso formato, ma la loro miniatura è una scheda disegnata
(`tools/vignettes-cartes.php`), non una foto: un modulo senza dati mostrerebbe solo una cornice vuota, mentre un tema
mostra se stesso.

## 6. Installare, attivare, provare

1. Metti `themes/aurora/` al suo posto.
2. **Amministrazione → Temi & Addons → Scansiona il disco** → spunta `aurora` → installa.
3. **Attivalo** sulla sua scheda (imposta il tema predefinito). Se il tema è stato registrato senza disposizioni,
   l'attivazione esegue `install()` da sola. Il pulsante **Reinstalla predefinito** riesegue `install()`.
4. Prima di distribuirlo, passa: `check-addon-declarations`, `check-addon-contracts` (la chiave `regions`),
   `check-css-variables`, `check-colles`, `check-classes-bs4`, `check-js-sources`, poi `check-mise-en-page`,
   `check-contraste`, `check-bleu-bootstrap` (il blu predefinito di Bootstrap rimasto in un componente che la tua
   tavolozza non ha ripreso) e `check-js-console` su un'installazione in cui il tema è attivo. Guarda la pagina mostrata:
   un banner vuoto, un copyright doppio o un commento illeggibile non si vedono nel codice.

## Buone pratiche

- **Caratteri**: servili dal tema o dal nucleo, mai da Google Fonts — la politica di sicurezza non consente più alcuna
  origine di caratteri (`font-src 'self' data:`), e quel caricamento inviava l'indirizzo IP di ogni visitatore a Google.
  `php tools/polices-locales.php --nom=…` scarica un carattere di Google Fonts in `fonts/` e scrive il suo foglio di
  stile `css/fonts/<nome>.css`, che il tema carica con `->css('fonts/<nome>')`.
- **Nessuno script da un CDN**: la CSP rigorosa (`script-src 'self' 'nonce-…'`) lo rifiuterebbe, e il progetto non
  dipende da nessuna terza parte. Tutto il JS vive nel tema o nel nucleo.
- **Cita** il progetto originale (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) se parti da un tema esistente.
