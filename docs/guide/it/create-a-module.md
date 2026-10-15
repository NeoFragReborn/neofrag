# Creare un modulo

Un **modulo** è una funzionalità completa: le sue pagine pubbliche, le sue rotte, la sua amministrazione, i suoi dati e i
suoi permessi. È il tipo di addon più ricco.

Creiamo un modulo `notes` che mostra un elenco di note pubbliche. Tutto ciò che segue è verificato sul codice; i moduli
inclusi (`modules/contact`, `modules/news`) sono i migliori esempi da leggere dopo.

## Struttura dei file

```
modules/notes/
├── notes.php                 # classe: metadati, dichiarazioni, rotte, permessi
├── controllers/
│   ├── checker.php           # convalida la rotta e carica i dati
│   ├── index.php             # mostra le pagine pubbliche
│   └── admin.php             # schermata di amministrazione (facoltativo)
├── install/
│   ├── install.sql           # le tabelle del modulo (eseguito all'installazione, idempotente)
│   ├── uninstall.sql         # eliminazione delle tabelle
│   └── migrations/           # modifiche di schema tra due versioni (facoltativo)
├── views/
│   └── index.tpl.php
├── css/                      # facoltativo
├── images/
│   └── thumbnail.jpg         # la sua miniatura, 960 × 600 — una scheda: la sua icona, il suo nome, cosa fa
├── js/                       # facoltativo — JavaScript puro, mai jQuery
└── langs/
    └── fr.php                # traduzioni (facoltativo)
```

## 1. La classe — `notes.php`

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
            'icon'        => 'fas fa-note-sticky',   // deve esistere nel FontAwesome incluso
            'author'      => 'Il Tuo Nome',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versione del nucleo che richiede come minimo — senza, «Aggiungi» rifiuta l'archivio
            'admin'       => TRUE,                   // espone una pagina di amministrazione

            // Dichiarazioni di disaccoppiamento — OBBLIGATORIE (la CI rifiuta un addon muto).
            'core'        => FALSE,                  // TRUE = sempre incluso e non disinstallabile
            'presets'     => ['communaute'],         // profili di installazione che lo spuntano in anticipo
            'requires'    => [],                     // addon di cui HA BISOGNO (senza, si rompe)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/la-mia-nota
            ],
        ];
    }
}
```

### Le tre dichiarazioni di disaccoppiamento

Dal 2026-09-15, il pacchetto si installa **à la carte**: l'installer offre dei profili (*Completo*, *Gaming / eSport*,
*Community*, *Associazione / club*, *Solo nucleo*) costruiti **a partire da queste dichiarazioni**. Nessun elenco è
scritto a mano altrove.

- `core` — `TRUE` per un modulo di infrastruttura o di CMS sempre incluso e non disinstallabile; `FALSE` per tutto ciò
  che è opzionale. Un modulo del nucleo non può **mai** dipendere da uno opzionale (regola 3 di
  `tools/check-addon-declarations.php`).
- `presets` — i profili che lo spuntano in anticipo: `'gaming'`, `'communaute'`, `'association'` (le etichette di
  `install/lib/presets.php`). Vuoto: compare solo nel profilo *Completo*.
- `requires` — gli addon di cui ha bisogno **per non rompersi**: una tabella che legge, una classe che nomina.
  L'installer li aggiunge automaticamente quando il tuo modulo è spuntato. Una dipendenza **morbida** (un servizio che
  può mancare) non si dichiara qui: si **protegge** nel codice (vedi §5).

Queste tre chiavi sono verificate staticamente nella CI, come l'esistenza dell'icona dichiarata (regola 7: FontAwesome ha
rinominato delle icone tra la 5 e la 6). Un addon che non vuoi nel catalogo del marketplace aggiunge
`'distributed' => FALSE`.

### Le rotte

Una rotta associa uno **schema di URL** a un **metodo del controller**. Lo schema è relativo al nome del modulo (`''` =
la pagina iniziale del modulo, qui `/notes`). Le rotte di amministrazione cominciano con `admin`
(`'admin{pages}' => 'index'` per un elenco paginato sotto `/admin/notes`).

> I segnaposto sono un insieme **fisso**: `{id}` (intero), `{key_id}`, `{url_title}` (slug), `{url_title*}` (slug con
> `/`), `{page}` e `{pages}` (paginazione). Un segnaposto sconosciuto produce un 404 silenzioso. Per una pagina di
> dettaglio, lo schema idiomatico è `{id}/{url_title}`: l'`id` identifica l'elemento, lo slug è decorativo.

## 2. Il checker — `controllers/checker.php`

Il **checker** viene eseguito prima del controller: convalida la rotta e carica i dati. Restituire `FALSE` o niente
provoca un 404. Ciò che **restituisce diventa gli argomenti** del metodo omonimo del controller.

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
        // niente: 404
    }
}
```

Per un punto di ingresso che riceve un **POST** (AJAX, un modulo scritto a mano), il checker legge i campi con
`post_check('titre', 'corps', 'options?')`: ogni nome è **obbligatorio**, tranne se porta il suffisso `?`, nel qual caso
vale `NULL` quando manca. Un campo obbligatorio assente fa fallire il checker: una risposta **404** in produzione (il
motivo esatto — quale campo, cosa è arrivato al suo posto — viene **registrato**) e **400 con il motivo** quando il debug
è visibile (un amministratore connesso, lo strumento di debug acceso). Questo comportamento viene da dieci widget che non
si potevano più aggiungere nell'Editor live perché un campo facoltativo veniva richiesto.

## 3. Il controller pubblico — `controllers/index.php`

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

`title()`, `icon()`, `breadcrumb()` compilano l'intestazione della pagina. Il controller mostra poi la sua vista, o un
pannello tramite `$this->panel()->title()->body($html)` per un HTML costruito in PHP.

> **Non far mai passare contenuto del database da `$this->title()` e poi dalla traduzione.** Un titolo scritto
> dall'amministratore non ha traduzione: `lang()` lo cerca invano e registra un avviso a ogni visita in un'altra
> lingua. Avvolgi ciò che viene dal database in `$this->no_translate(...)`.

## 4. La vista — `views/index.tpl.php`

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

Le variabili passate a `view()` sono disponibili direttamente. **Fai sempre l'escape** di ciò che viene dal database
(`nf_texte()`); per un HTML ricco scritto da un membro, `sanitize_html()` (HTMLPurifier). Un **indirizzo** scritto
da qualcuno che finisce in un `href` passa inoltre da `nf_url_sure()`, che rifiuta `javascript:` e gli altri schemi
eseguibili (vedi [Il framework](framework.md#sicurezza-ciò-che-esiste-già)).

**Il front end è Bootstrap 5, senza jQuery, sotto una CSP rigorosa.**
- Le classi di griglia si scrivono `col-12 col-lg-8`, mai `col-8` da solo (che si applica da 0 px e rompe i telefoni);
  `tools/check-classes-bs4.php` rifiuta le classi di Bootstrap 4 scomparse.
- Uno `<script>` in linea in una vista riceve automaticamente il **nonce** della risposta (il filtro di `index.php`); un
  file JS si carica con `->js('nome')` (la cartella `js/` del modulo).
- Scrivi il JavaScript con le primitive del nucleo — `NF.ready`, `NF.data(el, 'key')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`, `NF.replaceHtml` —,
  mai `$(…)`: jQuery non viene più caricato, e `tools/check-js-sources.php` rifiuta qualsiasi chiamata. Gli indirizzi di
  cui lo script ha bisogno si mettono come `data-*` sul markup invece che come PHP interpolato nel `.js`.

## 5. I dati

Un modulo **include le sue tabelle** in `install/install.sql` (e la loro rimozione in `uninstall.sql`). Questo SQL viene
eseguito **quando si installa il modulo** (installer, scansione dall'amministrazione, ZIP del marketplace), idempotente
grazie a `CREATE TABLE IF NOT EXISTS`:

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Il contenuto che si traduce** (un titolo, una descrizione) vive in una tabella `<table>_lang`, una riga per lingua,
> letta con `join_lang()` e scritta con `ecrire_lang()`:
> [Il framework](framework.md#contenuto-tradotto-join_lang-e-ecrire_lang).

> **Nessuna tabella propria? Nessun `install.sql`.** Un modulo che riusa tabelle del nucleo (per esempio `nf_file`
> tramite `model2('file')`) si installa senza toccare lo schema.

### Far evolvere lo schema — migrazioni per addon

`install.sql` crea le tabelle mancanti ma non **modifica** una tabella già presente sui siti degli utenti. Per una
modifica di schema tra due versioni del tuo modulo:

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Nome = **prefisso datato** (`YYYY_MM_DD_descrizione`), eseguite in ordine.
- Registrate in `nf_addon_migrations`: ogni migrazione viene eseguita **una sola volta**.
- In un'installazione nuova (`install.sql` porta già lo schema aggiornato), le migrazioni vengono **marcate come base**
  — annotate senza essere eseguite. In un aggiornamento (marketplace → «Aggiornamenti», o un archivio inviato con
  «Aggiungi»), vengono **eseguite** solo le nuove (`Addon::update()`). Un modulo incluso con il nucleo riceve le sue
  alla prima visita con il codice nuovo (`nf_migrations_du_code()`, vedi [il framework](framework.md#migrazioni)).
- Regola d'oro: una migrazione per una vera modifica a uno schema **già distribuito**; una tabella nuova va in
  `install.sql`, mai in una migrazione — **tranne** se il modulo è già installato su dei siti: allora la tabella nuova
  va in entrambi, `CREATE TABLE IF NOT EXISTS` nella migrazione.
- Un aggiornamento dal marketplace o tramite un archivio riesegue `install.sql` **prima** delle migrazioni: un sito che
  salta delle versioni riceve le tabelle dell'ultima, poi esegue su di esse le migrazioni più vecchie. Una migrazione
  che scrive dati punta quindi allo schema dell'ultimo `install.sql`, e si riscrive quando questo cambia — un sito che
  l'ha già eseguita la riconosce dal nome e non la riesegue. Per lo stesso motivo, non rinominare mai una migrazione.
- Sul sito demo, un aggiornamento di addon lanciato dall'amministrazione non esegue nulla (`Addon::migrate()` esce
  quando `nf_demo()` è vero); le migrazioni che arrivano con il codice nuovo si applicano lì come altrove.

La cartella `migrations/` **nella radice del progetto** è riservata alle modifiche trasversali del nucleo.

### Dipendere da un altro modulo senza rompersi

Il pacchetto si installa à la carte: il modulo di cui hai bisogno può **non esserci**. Quattro modi di dipendere, e cosa
succede se manca (`tools/check-addon-coupling.php` li rileva con il tokenizer):

| Mezzo | Se l'addon manca | Cosa fare |
|---|---|---|
| leggere **la sua tabella** (`from('nf_teams')`) | **fatale** — «Table doesn't exist» | dichiararlo in `requires`, o proteggersi con `$this->db->table_exists('nf_teams')` e **annotare** |
| nominare **la sua classe** | **fatale** — «Class not found» | idem |
| chiamare **il suo servizio** (`$this->module('teams')`, `model2('team')`) | tollerante: restituisce `NULL` | provare il ritorno: `if ($teams = $this->module('teams'))` |
| scrivere **il suo URL** (`url('teams/…')`) | estetico: un link morto | accettabile |

Un accoppiamento fatale **né dichiarato né annotato fa fallire la CI**. L'annotazione va nel punto esatto, come
commento: `// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, o in testa al file
`couplage(teams): …` quando i riferimenti sono sparsi.

### Tipi di contenuto: reazioni, iscrizioni, revisioni, cestino

Se il tuo modulo pubblica **contenuto** (come le notizie, gli articoli, le discussioni), dichiaralo: i moduli trasversali
— reazioni, notifiche, revisioni, cestino, gamification — lo raccolgono invece di portarsi ciascuno il proprio elenco di
tabelle.

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

Un esempio reale: `modules/news/news.php`.

### L'indicizzazione di un contenuto

Un tipo di contenuto dichiarato sopra (con la sua `table` e la sua `pk`) può ricevere, come le notizie o il wiki, un
titolo e una descrizione per i motori di ricerca — la pagina condivisa è quella della sezione Indicizzazione, niente da
scrivere da quel lato. Tre chiamate:

```php
// La scheda di modifica dell'amministrazione: il pulsante che porta alla pagina condivisa.
return $this->admin_card('fas fa-edit', $titre, $formulaire, '', nf_seo_bouton('note', (int) $id));

// L'azione della pagina pubblica, prima di mostrare la vista: applica il titolo e la descrizione inseriti.
nf_seo_contenu('note', (int) $id);

// Quando cambia l'indirizzo di un contenuto (un nome, uno slug), il vecchio porta al nuovo (301).
nf_redirection_ajouter('notes/'.$ancien_slug, 'notes/'.$nouveau_slug);
```

Esempi reali: `modules/wiki/controllers/admin.php` e `modules/wiki/controllers/index.php`.

### I concentratori: statistiche, attività, cruscotto, ricerca, mappa del sito

Un modulo si collega a una pagina che **aggrega** fornendo un controller con il nome del concentratore. Il
concentratore chiama il metodo omonimo **senza verificare nulla**: rinominato, reso privato o con un parametro
obbligatorio in più, l'errore compare solo all'apertura della pagina — per questo `tools/check-addon-contracts.php`
fissa questi contratti nella CI.

| Controller | Metodo atteso | Chiamato da |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | la pagina Statistiche dell'amministrazione |
| `controllers/activity.php` | `activity($user_id, $limit)` | il profilo di un membro |
| `controllers/dashboard.php` | `dashboard()` | il cruscotto dell'amministrazione |
| `controllers/block.php` | `block()` | i blocchi `[block:…]` delle pagine |
| `controllers/search.php` | `search()` **e** `suggest()` | la ricerca globale e il suggerimento istantaneo — un modulo che ha solo `search()` viene **ignorato in silenzio** |
| `controllers/sitemap.php` | `sitemap()` | la mappa del sito (`/sitemap.xml`, una per lingua): restituisce `[['adresse' => 'miomodulo/12/titolo', 'date' => …], …]`, percorsi come quelli che riceve `url()`, solo ciò che un **visitatore** può leggere (`$this->access('miomodulo', 'read', $id, 'visitors')`) e ciò che esiste **nella lingua della mappa** — un contenuto che **non ha una lingua propria** (una discussione, un ticket, una pagina fatta di tale contenuto) porta `'sans_langue' => TRUE`: compare solo nella mappa della prima lingua del sito, e la sua pagina chiama `nf_seo_sans_langue()`, che vi mette la sua canonica (sei lingue dello stesso testo, ciascuna canonica: Google ne tiene un'altra e non ne indicizza nessuna); **una sezione vuota restituisce `[]`**: la sua pagina avrebbe solo «ancora niente» da mostrare; senza il concentratore, il modulo è assente dai motori di ricerca, e IndexNow non segnala le sue pagine: l'attività pianificata confronta questa stessa mappa da un passaggio all'altro |

### L'area membri e il profilo pubblico

Tre metodi della **classe** del modulo, facoltativi, e letti solo se esistono:

- **`espace_membre($user)`** aggiunge pagine al **menu dell'area membri** — la colonna di sinistra su un computer, la
  fascia di schede su un telefono, ripresa anche nella barra in alto dei temi e nel widget «Area membri». Restituisce un
  elenco di voci: `url`, `titre`, `icone`, se serve `badge` (un numero da segnalare) e `compact` (mostrata anche nei
  menu brevi), e `ordre` per ordinarle.
- **`profil_membre($membre)`** aggiunge **schede al profilo pubblico di un membro**, ciascuna al suo indirizzo
  `user/<id>/<nome>/<scheda>`. Restituisce un elenco di schede: `onglet` (il segmento dell'indirizzo: lettere minuscole,
  cifre e trattini), `titre`, `icone`, `ordre`, se serve `nombre` (mostrato accanto al titolo e in «In cifre»), e
  `contenu`, una funzione che mostra la pagina della scheda — chiamata solo quando la si apre. **Niente da mostrare,
  niente da restituire**: una scheda vuota non compare, e il suo indirizzo risponde 404.
- **`types_de_notification()`** dichiara le notifiche che il modulo invia, per le **preferenze** di ogni membro
  (`user/notifications/preferences`): `type` (quello che riceve `push()`), `titre` (ciò che legge il membro, «Una
  risposta in una discussione che seguo»), `email` a `TRUE` se il modulo sa inviarla anche via email, e `ordre`. **Un
  tipo inviato deve essere dichiarato**: altrimenti il membro non può spegnerlo — `tests/Unit/TypesDeNotificationTest.php`
  legge gli invii del repository e lo rifiuta.

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

Il profilo mostra ciò che **chi guarda** può leggere: una scheda che elenca contenuto filtra come le sue pagine
(`$this->access(…)`) — il forum vi mostra solo le categorie leggibili. Ciò che il membro sceglie di nascondere di sé
(punti, karma, VIP, età, presenza online) si chiede al suo modello: `$membre->montre('statut')`. Esempi reali:
`modules/forum/forum.php`, `modules/teams/teams.php`.

Una notifica si invia con `push($user_id, $type, $titre, $adresse, $acteur)` del modulo `notifications` (o
`push_unique()`, che non ne aggiunge una seconda dello stesso tipo sullo stesso indirizzo finché la prima non è letta):
ciò che il membro ha spento **sul sito** non viene scritto. Un modulo che la invia anche via email fa prima la domanda
da sé, `$notifications->veut($user_id, $type, 'email')`, e non scrive se la risposta è no. Dalla classe del modulo,
caricalo con `\NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])` — `$this->module()` lì sbaglia tipo di
addon. Un esempio reale: `modules/talks/talks.php`.

## 6. I permessi (facoltativo)

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

I permessi diventano modificabili in **Amministrazione → Permessi** (matrice ruolo × azione). Nel codice:
`$this->access('notes', 'add_note')`. Un esempio reale: `modules/news/news.php`.

Un permesso **per elemento** (una categoria, una galleria) dichiara i suoi valori di partenza in `init`, che
`$this->access->init('notes', 'categorie', $id)` imposta quando si crea l'elemento. Poiché il ruolo «membro» non eredita
dal ruolo «visitatore», `init` segue le regole originali di NeoFrag quando `members` non è nominato: un elenco **vuoto**
rende pubblica l'azione (visitatori e membri); `['visitors', TRUE]` vale anche per i membri; `['visitors', FALSE]`
rifiuta solo i visitatori — i membri conservano l'azione —; e `['admins', TRUE]` la riserva agli amministratori. Un
esempio reale: `modules/forum/forum.php` (leggere, scrivere, moderare una categoria).

## 7. L'amministrazione (facoltativo)

Se `'admin' => TRUE`, aggiungi `controllers/admin.php` (classe `Admin extends Controller_Module`). Il suo metodo
`index()` viene servito sotto `/admin/notes`.

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

**`form()`** (`add_rules()` / `is_valid()` / `display()`) è l'API dei moduli delle schermate di amministrazione;
`form2()` (vedi [Il framework](framework.md)) è per i moduli pubblici, ricchi o di sola conferma. Il trait
`Admin_Helpers`, disponibile su ogni controller di modulo, veste il contenuto: `admin_card()`, `admin_create()`,
`admin_back()`, `admin_split()`, `admin_action_bar()`, `admin_empty()`, `admin_stats()`, `sort_select()`. Ogni
sottopagina deve offrire un ritorno (`admin_back()` o le briciole di pane): `tools/check-admin-back.php` lo verifica su
ogni pagina dell'amministrazione.

### Le azioni che modificano: esigere un token CSRF

Ogni azione che **cambia** qualcosa e che viene avviata da un **link GET** o da un **POST scritto a mano** (fuori da
`form()` / `form2()`, che portano il proprio token) deve essere protetta: `SameSite=Lax` non basta.

```php
// Costruzione del link: token nella query.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Controller: verifica il token PRIMA di cambiare qualsiasi cosa, altrimenti reindirizza.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Per un POST scritto a mano, il token va in un campo nascosto:
`<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### Le tabelle dell'amministrazione

`table2()` mostra elenchi paginati, ordinabili cliccando sull'intestazione e filtrabili (esempio:
`modules/user/controllers/admin.php`). L'ordinamento è gestito da `js/table2.js`, in JavaScript puro. Un elenco che il
checker divide a mano (`->paginate()`) mostra i suoi link di pagina: il contratto è in
[Il framework](framework.md#tabelle--table2), e `check-pagination` lo verifica.

### La guida di stile dell'amministrazione

L'amministrazione è **sobria**: lo sfondo si cancella dietro i dati, il colore d'accento è raro, e i colori vivi sono
riservati a ciò che chiede un'azione o segnala uno stato. Una pagina di modulo si costruisce così:

- **Una scheda per elenco**: `admin_card(icona, titolo, corpo, sottotitolo, azioni)` — il sottotitolo porta il conteggio
  («3 pubblicate · 1 bozza»), le azioni il pulsante che crea.
- **Il pulsante che crea** («Nuova citazione»): `admin_create(url, etichetta)`, un `btn btn-primary btn-sm`
  nell'intestazione della scheda dell'elenco che alimenta. La barra in alto conserva gli strumenti della pagina —
  Permessi, Configurazione, Aiuto.
- **Le azioni di una riga**: pulsanti con sola icona, piccoli. *Modifica* (matita), *accesso* (lucchetto), *ordina*:
  `btn-outline-secondary`, neutri. *Elimina*: `btn-outline-danger` con l'icona `far fa-trash-alt`. Mai un pulsante pieno
  in una riga. `button_update()`, `button_access()` e `button_delete()` li mostrano così; un pulsante scritto a mano segue
  la stessa regola, che `tools/check-actions-admin.php` verifica.
- **Le altre azioni di una riga** (anteprima, duplica, ripristina): un contorno, mai un pulsante pieno; una tinta solo se
  porta un significato (`btn-outline-warning` per un'anteprima che cambia la modalità di navigazione,
  `btn-outline-success` per ripristinare).
- **I badge di stato**: `badge text-bg-success` (pubblicato, attivo), `text-bg-secondary` (bozza, inattivo),
  `text-bg-warning` (in attesa), `text-bg-danger` (errore) — il tema dell'amministrazione li ammorbidisce (sfondo
  pallido, testo marcato), in chiaro come in scuro.
- **Una tabella** per i dati in colonne (titolo, conteggio, stato); **schede** (`nf-content-card`) per il contenuto
  redatto che si riconosce dal suo estratto. Un elenco raggruppato per famiglia (Modelli email) resta **una sola
  tabella**, con una riga `<tr class="nf-table-groupe">` in testa a ogni famiglia, invece di una scheda per famiglia.
  Una data del database vi si mostra con `nf_date_heure()`, mai così com'è.
- **Lo stato vuoto**: `admin_empty(icona, titolo, testo)`, che dice cosa fare per cominciare.
- **Il pulsante che invia un modulo**: «Salva» per un elemento che esiste — mai «Modifica», lo stai già modificando —,
  «Aggiungi» o «Crea» per uno nuovo, come il titolo della pagina («Aggiungi un forum»).
- **Più viste dello stesso argomento** (Monitoraggio, Utenti, Discord): schede `nf-local-nav` / `nf-local-tab` — come
  link verso altri indirizzi, o come pulsanti che cambiano pannello.

## 8. Provare il modulo

Il progetto ha una rete di sicurezza, e un modulo nuovo deve entrarci:

- **Test** — `tests/Unit` (senza database), `tests/Headless` (il framework avviato, modelli reali contro il database di
  test, ogni test in una transazione annullata — `HeadlessTestCase`), `tests/Integration` (specchio SQL),
  `tests/Browser/*.test.html` (il contratto di uno script in un browser reale, tramite `tools/check-js.php`).
  `vendor/bin/phpunit --fail-on-skipped`.
- **Controlli** da passare prima di distribuire: `check-addon-declarations`, `check-addon-coupling`,
  `check-addon-contracts`, `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (nessun testo visibile
  fuori da `lang()`), `check-actions-admin` (la guida di stile dei pulsanti), `check-pagination`, `check-heures`
  (nessuna data mostrata senza fuso orario), `check-db-compteurs`, `check-demo-lock` (un modulo che tocca la
  configurazione è bloccato nella demo), `check-vignettes` (la sua miniatura esiste, nel formato giusto),
  `check-strict-types` (il contatore non deve mai scendere — dichiara `declare(strict_types=1)` nei tuoi file nuovi),
  poi `check-install-profiles` (installa davvero ogni profilo e chiama le rotte dei moduli assenti, che devono
  restituire un **404 pulito**, mai un 500) e `check-js-console` (apre le pagine dell'amministrazione in un browser e
  rifiuta qualsiasi errore JS).
- PHPStan: `vendor/bin/phpstan analyse`.

L'elenco completo è in [`tools/README.md`](../../../tools/README.md); `php tools/check-all.php --navigateur` li esegue
tutti.

## 9. Installare e distribuire

1. Metti `modules/notes/` al suo posto.
2. **Amministrazione → Temi & Addons → Scansiona il disco** → spunta `notes` → installa (l'installazione esegue
   `install/install.sql`, marca le migrazioni come base, imposta i permessi).
3. Per distribuirlo: comprimi la cartella (`modules/notes/` nella radice dell'archivio), si installa con **Aggiungi
   (ZIP)**. Per pubblicarlo nel catalogo del marketplace del progetto, deve avere `'core' => FALSE` e nessun
   `'distributed' => FALSE`; `tools/package-addons.php` comprime e iscrive ogni addon opzionale in
   `marketplace/catalog.json` con la sua impronta SHA-256.
4. La sua miniatura, prima di impacchettare: una **scheda** disegnata a partire dal suo `__info()` — la sua icona, il
   suo nome e la sua descrizione in francese —, leggibile a qualsiasi dimensione, anche quando il modulo non ha niente da
   mostrare: `php tools/vignettes-cartes.php --nom=notes --sortie=/tmp/cartes` per guardarla, poi `--poser` per
   collocarla. Formato e controlli: [Creare un tema, § 5](create-a-theme.md).
