# Creare un widget

Un **widget** è un blocco riutilizzabile che si colloca in qualsiasi zona di un tema, con l'Editor live. È l'addon più
semplice da scrivere: una classe, un controller, una vista.

Creiamo un widget `hello` che mostra un messaggio di benvenuto configurabile. Tutto è verificato sul codice;
`widgets/html` e `widgets/about` sono buoni esempi reali.

## Struttura dei file

```
widgets/hello/
├── hello.php                 # la classe del widget (metadati, dichiarazioni, tipi)
├── controllers/
│   ├── index.php             # il controller: prepara e mostra la vista
│   ├── checker.php           # convalida e completa le impostazioni (consigliato appena ci sono impostazioni)
│   └── admin.php             # il modulo delle impostazioni (facoltativo)
├── views/
│   ├── index.tpl.php         # il modello HTML
│   └── admin.tpl.php         # modello delle impostazioni (facoltativo)
├── css/
│   └── hello.css             # stili (facoltativo)
├── images/
│   └── thumbnail.jpg         # la sua miniatura, 960 × 600 — una scheda: la sua icona, il suo nome, cosa fa
└── js/
    └── hello.js              # facoltativo — JavaScript puro, mai jQuery
```

## 1. La classe — `hello.php`

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
            'icon'        => 'fas fa-hand-spock',   // OBBLIGATORIO, e deve esistere nel FontAwesome incluso
            'author'      => 'Il Tuo Nome',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versione del nucleo che richiede come minimo — senza, «Aggiungi» rifiuta l'archivio

            // Dichiarazioni di disaccoppiamento — OBBLIGATORIE (la CI rifiuta un addon muto).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // ad es. ['teams'] se il widget legge nf_teams

            // Facoltativo: più tipi = più metodi del controller, scelti al momento del posizionamento.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- Il `namespace` **deve** seguire la cartella: `NF\Widgets\<Nome>`.
- `icon` è obbligatorio: la procedura guidata per aggiungere widget dell'Editor live mostra i widget come **schede con
  icona**, e `tools/check-addon-declarations.php` rifiuta un nome assente dal FontAwesome incluso (le icone sono state
  rinominate tra la versione 5 e la 6).
- `core`, `presets`, `requires`: le stesse regole di un modulo — vedi [Creare un modulo](create-a-module.md), «Le tre
  dichiarazioni di disaccoppiamento».
- Un widget che **mostra ciò che fa un modulo** — le sue notizie, il suo forum, la sua galleria — dichiara quel modulo
  in `requires`, anche se ne porta il nome: senza, o quando è disattivato, il widget non si mostra e l'Editor live non
  lo offre; «Aggiungi» si rifiuta di installarlo da solo, e l'installer lo lascia fuori da un profilo che non ha il
  modulo. I suoi `presets` sono quelli del modulo (`tools/check-addon-declarations.php`, regola 9). Un widget che vive
  senza il modulo (legge tabelle del nucleo) non lo dichiara, e mette solo ciò che dipende da esso, un link per esempio,
  dietro `$this->module('x')` e `is_enabled()`.
- Le **impostazioni** non si dichiarano in questa classe ma in `controllers/admin.php` (§5).

## 2. Il controller — `controllers/index.php`

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

Il controller riceve i `$settings` — **già convalidati e completati dal checker** (§6) —, prepara i dati, carica il suo
CSS e mostra la sua vista. La catena `->css('hello')->view('index', $data)` è lo schema abituale. Un widget con più
`types` ha un metodo per tipo.

## 3. La vista — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo nf_texte($message) ?></span>
</div>
```

**Fai sempre l'escape** di ciò che viene da un'impostazione o dal database (`nf_texte()`). Un titolo scritto da un
membro e messo come `innerHTML` lato JS diventerebbe un'iniezione memorizzata, servita a tutti.

`nf_texte()` decodifica e poi fa l'escape: un testo che il sito ha salvato codificato («`&eacute;`») si mostra
correttamente, dove `htmlspecialchars()` lo codificherebbe una seconda volta — il visitatore leggerebbe
«`&eacute;`». `check-double-codage` rifiuta `htmlspecialchars()` nel prodotto.

## 4. Il CSS — `css/hello.css` (facoltativo)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**Usa solo il vocabolario `--nf-*` condiviso**, quello che **tutti** i temi definiscono: `--nf-bg`, `--nf-bg-elevated`,
`--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`, `--nf-border`, `--nf-border-strong`, `--nf-text`,
`--nf-text-strong`, `--nf-text-soft`, `--nf-text-muted`, `--nf-muted`, `--nf-muted-2`, `--nf-accent`,
`--nf-accent-soft`, `--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`, `--nf-info`,
`--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`, `--nf-font-display`.

Una variabile che un tema non avesse definito ricadrebbe sul suo valore di riserva — è così che sono comparsi **blocchi
bianchi** nei temi scuri. `tools/check-css-variables.php` rifiuta ogni variabile usata da un widget o da un modulo che
non tutti i temi definiscono. Non mettere un valore di riserva: nasconderebbe il difetto invece di rivelarlo.

Griglia: `col-12 col-lg-6`, mai `col-6` da solo (Bootstrap 5 lo applica da 0 px).

## 5. Le impostazioni — `controllers/admin.php` (facoltativo)

Perché un widget sia **configurabile**, aggiungi `controllers/admin.php`: mostra il modulo delle impostazioni, e i suoi
campi alimentano i `$settings` che riceve il controller `index`. La classe estende `Controller` (non `Widget`):

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

La vista `views/admin.tpl.php` mostra i campi; ogni campo si chiama `settings[<chiave>]`. Ogni riga porta `nf-field`, la
classe di campo del prodotto: dà la spaziatura che ogni tema fissa per tutti i moduli.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo nf_texte($message) ?>">
    </div>
</div>
```

Nell'Editor live, la procedura guidata di aggiunta mostra il passo «Configurazione» **se e solo se** questo controller
mostra qualcosa: un widget senza impostazioni semplicemente non ha `controllers/admin.php`.

## 6. Convalidare e completare le impostazioni — `controllers/checker.php`

**Un widget può arrivare senza impostazioni**: posizionato dall'`install()` di un tema, aggiunto nell'Editor live prima
di aprire il suo modulo, o ripristinato da una vecchia disposizione. Il suo checker deve restituire impostazioni
utilizzabili anche quando non gliene viene data nessuna. Dodici widget non lo facevano; uno usava una chiave assente come
divisore — divisione per zero, widget morto.

Il metodo `index($settings)` riceve le impostazioni **grezze** e **restituisce** un array **convalidato e completato con
i suoi valori predefiniti**, che diventa i `$settings` del controller:

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // Prima la riserva: `+` riempie solo le chiavi assenti, i valori forniti restano intatti.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` rifiuta ogni lettura `$settings['chiave']` che non sia né protetta sulla sua riga
(`isset`, `??`, `empty`, `array_key_exists`) né coperta da questa riserva in testa al metodo. Un esempio reale completo:
`widgets/about/controllers/checker.php`.

## 7. JavaScript in un widget

- Caricalo con `->js('hello')` nel controller; il file è `js/hello.js`.
- **JavaScript puro, senza jQuery**: `NF.ready(fn)`, `NF.data(el, 'key')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (i `<script>` inseriti vengono rieseguiti con il nonce giusto).
  `tools/check-js-sources.php` rifiuta `$(…)`.
- Gli indirizzi AJAX si leggono nel markup (`data-url="…"`) invece che come PHP interpolato nel `.js`, il che rende il
  file verificabile così com'è nel banco di prova (`tests/Browser/*.test.html`).
- Uno script che si blocca prima di agganciare i suoi ascoltatori non rompe nulla di visibile:
  `tools/check-js-console.php` apre le pagine in un browser reale e lo vede.

## 8. Installare, posizionare, distribuire

1. Metti `widgets/hello/` sul tuo sito.
2. **Amministrazione → Temi & Addons → Scansiona il disco**, spunta `hello`, installa.
3. Nell'**Editor live**, aggiungilo a una zona: la procedura guidata offre il widget, il suo tipo, un titolo e poi la
   sua configurazione se ne ha una. Un widget si può collocare più volte, con impostazioni diverse.
4. Per distribuirlo: comprimi la cartella (`widgets/hello/` nella radice dell'archivio) — si installa con **Aggiungi
   (ZIP)** — o lascia che `tools/package-addons.php` lo iscriva nel catalogo del marketplace.
5. La sua miniatura: una **scheda** disegnata a partire dal suo `__info()`, come quella di un modulo:
   `php tools/vignettes-cartes.php --nom=hello --sortie=/tmp/cartes` per guardarla, poi `--poser`. Formato e controlli:
   [Creare un tema, § 5](create-a-theme.md).

`tools/check-widget-contract.php` chiama davvero ogni coppia widget/tipo per verificare che nessuna si rompa una volta
posizionata.
