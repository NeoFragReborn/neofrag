# Il framework

Un riferimento dei mattoni che maneggi scrivendo degli addon. Per l'architettura interna completa, vedi
[`docs/architecture.md`](../../architecture.md). Tutto ciò che segue è verificato sul codice.

## Il localizzatore di servizi — `NeoFrag()`

Tutto il framework è raggiungibile tramite il singleton globale `NeoFrag()` e, dentro una classe di addon, tramite
`$this` (che delega a lui). I servizi si ottengono con metodi magici:

```php
$this->db        // accesso al database
$this->config    // configurazione del sito (nf_name, nf_default_theme…)
$this->user      // membro corrente
$this->url       // richiesta / segmenti / base
$this->lang(...) // traduzione
$this->events    // eventi interni (fire / on)
$this->module('forum');        // un modulo — NULL se non è installato
NeoFrag()->model2('addon');    // un modello
```

> `__call` **inghiotte le chiamate sconosciute**: `$this->metodo_assente()` non rompe nulla. Per provare un controllo,
> inietta un guasto che la magia non intercetta (una funzione globale assente).

## Il routing

Un modulo dichiara le sue rotte in `__info().routes`: `schema => metodo`.

```php
'routes' => [
    ''                 => 'index',   // la pagina iniziale del modulo
    '{id}/{url_title}' => '_show',   // /modulo/42/slug
    'admin{pages}'     => 'index',   // amministrazione paginata
],
```

- Segnaposto **fissi**: `{id}` (intero), `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}` e `{pages}`
  (paginazione). Un segnaposto sconosciuto → 404 silenzioso.
- Ciclo: il **checker** (`controllers/checker.php`) convalida la rotta e carica i dati; ciò che **restituisce** diventa
  gli argomenti del metodo omonimo del **controller** (`controllers/index.php`).
- Il router attuale è `rotta → modulo → pagina → 404`. Le **regioni con nome** dei temi (`region('content')`) sono la
  base del futuro routing centrato sulle pagine (outlines, rotte riservate), non ancora scritto.

> Nei test o con `curl`, il CMS tratta gli agenti che non sono browser come robot e **non apre la sessione**: invia uno
> **User-Agent da browser**.

### Il rifiuto di un checker finisce nel registro

Quando un checker fallisce — un campo POST assente, un bersaglio che non esiste —, il sito risponde **404**; il motivo
(rotta, checker, metodo, campo assente e cosa è arrivato al suo posto) viene **sempre registrato** (`[checker] …`).
Quando il debug è visibile — lo strumento di debug acceso, per un amministratore connesso —, il motivo viene restituito
al client e lo stato è **400**: «la tua richiesta è malformata» invece di «questo indirizzo non esiste».
`post_check('a', 'b?')`: il suffisso `?` rende facoltativo un campo (`NULL` se manca).

## Database

Un costruttore di query fluente; le tabelle portano il prefisso `nf_`.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // array di righe; ->row() per una sola

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // restituisce l'id
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // oppure ->rollback()
```

- **`table_exists('nf_teams')`** — vero se la tabella esiste. Il pacchetto si installa à la carte: un modulo del nucleo
  che legge la tabella di un modulo opzionale **deve** proteggersi così (o dichiarare la dipendenza in `requires`). Il
  risultato viene messo in cache per la durata della richiesta.
- **`import($sql)`** — esegue un SQL a più istruzioni (DDL di installazione) fuori dalla catena delle istruzioni
  preparate.
- Una **join che conta o decora soltanto deve essere `LEFT`**: sette elenchi nascondevano ogni contenuto senza figli (un
  forum senza messaggi, un album senza immagini) a causa di una join stretta.
- Un **contatore** (visite, clic) su una tabella il cui `updated_at` porta `ON UPDATE current_timestamp()` riscrive la
  data di modifica a ogni visita: la pagina del wiki annunciava «modificata il» all'ora del suo ultimo lettore. Scrivi
  la colonna su se stessa — `SET views = views + 1, updated_at = updated_at` —; `check-db-compteurs` rifiuta la
  dimenticanza.
- `MATCH … AGAINST` (FULLTEXT) **non vede** una riga inserita in una transazione non confermata: InnoDB indicizza solo al
  commit. Un test di ricerca FULLTEXT non può essere avvolto nella transazione annullata del database di test.

Per le entità gestite (addon, file…), passa dai **modelli**: `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()`; per scorrere un insieme tipizzato, `NeoFrag()->collection('addon')->get()`.

## I moduli — `form()` e `form2()`

Convivono due API (una decisione registrata: la v1 è congelata, non migrata in blocco):

- **`form()`** — l'API delle **schermate di amministrazione**: campi dichiarati in un array con `add_rules([...])`,
  elaborati da `is_valid($post)`, mostrati da `->display()`. Un esempio completo in [Creare un modulo](create-a-module.md)
  (§7).
- **`form2()`** — fluente, ogni campo è un oggetto `form_*()`. Per i moduli **pubblici** o **ricchi**, e la **sola** che
  convalida un modulo **di sola conferma** (senza campi) — una conferma di eliminazione accetta **solo** il campo
  `delete`: una casella aggiunta la farebbe fallire in silenzio.

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

Entrambe portano il proprio token CSRF. Un'azione avviata da un **link** o da un **POST scritto a mano** deve
verificarlo da sé: `csrf_url()`, `check_csrf()`, `csrf_token()` (trait `Admin_Helpers`); fuori da un controller, lo
stesso token si legge con `nf_jeton_csrf()`.

**L'editor ricco** — il tipo `editor` in `form()`, `form_textarea()->editor()` in `form2()` — è TinyMCE 8, ospitato dal
sito; l'HTML che produce passa da `sanitize_html()`. Un'immagine **incollata o trascinata** al suo interno viene inviata
al sito (`ajax/user/editeur-image`: membro connesso, token di sessione, frequenza limitata), poi inserita con il suo
indirizzo definitivo. Il sito legge il suo tipo reale dai suoi byte (JPEG, PNG, GIF, WebP), rifiuta un file che porta
codice, limita il suo peso (5 MB) e le sue dimensioni, la **ricodifica** — niente più metadati, l'orientamento di una
foto applicato — e la salva sotto `upload/editeur/AAAA/MM/` con un nome casuale. Un `tinymce.init()` scritto a mano (un
modello, una schermata a parte) mette `Editeur_Images::tinymce()` in testa alle sue impostazioni
(`NF\NeoFrag\Libraries\Editeur_Images`): senza, un'immagine incollata resta rotta, poi scompare al salvataggio.
`EditeurImagesTest` lo verifica per tutto il prodotto.

**Il captcha** — `->captcha()` in `form2()`, `add_captcha()` in `form()` — mostra il fornitore scelto in
*Impostazioni → Captcha* e verifica la sua risposta sul server; un membro connesso non ne vede nessuno. Quello
predefinito è **ALTCHA**: ospitato dal sito, senza chiave né cookie, fa eseguire al browser un piccolo calcolo (circa un
secondo) che il server verifica, e rifiuta che la stessa soluzione serva due volte. Turnstile, hCaptcha e reCAPTCHA v2
hanno bisogno delle loro due chiavi; senza, subentra ALTCHA. Fuori da un modulo: `$this->captcha->element()` per
mostrarlo, `$this->captcha->is_valid()` per verificare (`NF\NeoFrag\Libraries\Captcha`).

## Tabelle — `table2()`

Mostra elenchi paginati, **ordinabili cliccando sull'intestazione** (Maiusc + clic: ordinamento su più colonne;
Ctrl + clic: rimuovere) e filtrabili, a partire da una query. Esempio: l'elenco dei membri,
`modules/user/controllers/admin.php`. L'ordinamento è servito da `js/table2.js` in JavaScript puro.

**Un elenco diviso in pagine mostra i suoi link di pagina.** Quando un checker divide un elenco — `->paginate($page)` su
una collezione, `->pagination->get_data($lignes, $page)` su un array —, il metodo del controller che lo mostra chiama
`->pagination->get_pagination()` (o passa da `table2()`, che lo mostra da sé). Senza, si vedono solo i primi elementi e
gli altri sono irraggiungibili. Un elenco che sta sempre in una pagina lo dice con un commento
`// pagination : <raison>` nel metodo. `check-pagination` lo verifica.

## Traduzioni

`$this->lang('Chiave')` restituisce la traduzione. I file `langs/fr.php` associano il **crc32b della chiave sorgente**
alla traduzione:

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // oppure direttamente '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` accetta argomenti (alla maniera di `sprintf`).
- **Il francese è la lingua sorgente**; sono incluse sei lingue e `tools/check-langs.php --toutes` rifiuta una chiave
  mancante in una di esse. `--fix` aggiunge le chiavi mancanti **solo al francese**.
- **Non tradurre mai contenuto del database** (un titolo scritto dall'amministratore): `lang()` lo cerca invano e
  registra un avviso a ogni visita. Avvolgilo in `$this->no_translate(...)`.

### Contenuto tradotto: `join_lang()` e `ecrire_lang()`

Il **contenuto** di un modulo (un titolo, una descrizione) si traduce in un altro modo: vive in una tabella
`<table>_lang`, una riga per lingua, e ciò che non ha lingua (una data, un'immagine, un prezzo) resta nella tabella
principale.

```sql
CREATE TABLE IF NOT EXISTS nf_notes_lang (
    note_id INT UNSIGNED NOT NULL,
    lang    VARCHAR(5)   NOT NULL,
    title   VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (note_id, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Leggere**: `join_lang()` unisce la traduzione **migliore** — quella della lingua richiesta, altrimenti quella
  francese, altrimenti quella della prima lingua scritta. Un contenuto scritto in una sola lingua resta così visibile
  nelle altre, invece di scomparire come con una join filtrata sulla lingua.

  ```php
  $this->db->select('n.id', 'nl.title')->from('nf_notes n')->join_lang('nf_notes_lang nl', 'note_id', 'n.id')->get();
  ```

- **Scrivere**: `ecrire_lang()` crea la riga quando manca — un `update()` filtrato sulla lingua non scriveva nulla la
  prima volta che si traduceva qualcosa, e il testo inserito andava perso. Fornisci tutte le colonne obbligatorie della
  tabella.

  ```php
  $this->db->ecrire_lang('nf_notes_lang', ['note_id' => $id, 'lang' => $langue], ['title' => $titre]);
  ```

- **La schermata di modifica** scrive nella lingua dell'amministrazione, e lo dice sotto il campo («Scritto per
  %s…»): traduci cambiando lingua. Ciò che vedono l'amministratore e il visitatore:
  [Un sito in più lingue](multilingue.md).

## Date e fusi orari

Il prodotto **salva** le sue date nel fuso orario che PHP ha all'avvio — l'ora universale in produzione — e non lo
cambia mai: il database allinea la sua sessione su di esso (`drivers/mysqli.php`). Le **mostra** nel fuso orario di chi
guarda, dato da `nf_fuseau()`: la scelta del membro (`nf_user_profile.timezone`, applicata all'apertura della sessione),
altrimenti quello del suo browser (cookie `nf_fuseau`, messo da `views/theme/main.tpl.php`), altrimenti l'impostazione
del sito (`nf_timezone`), altrimenti il fuso orario di salvataggio. Tutto è in `neofrag/helpers/time.php`.

- **Scrivere nel database**: `date('Y-m-d H:i:s')`, `NOW()` o `now()` — il fuso orario di salvataggio.
- **Mostrare**: `timetostr($format, $when)` o la libreria Date (`$this->date($when)`); mai `date()`, che
  `check-heures` rifiuta fuori dai formati macchina. Per una colonna data-e-ora letta così com'è (un elenco, una
  scheda): `nf_date_heure($valeur)` — `d/m/Y H:i` nella lingua e nel fuso orario del visitatore, una stringa vuota se il
  valore è vuoto; per una data sola, `nf_date($valeur)`. Mai un formato numerico scritto a mano in `timetostr()`
  (`'d/m/Y'`, `'Y-m-d H:i'`): il tedesco scrive `02.10.2026`, e `check-heures` lo rifiuta.
- **Leggere un inserimento**: i campi `datetime` dei due sistemi di moduli convertono da soli; a mano,
  `nf_heure_saisie()` (e `nf_heure_affichee()` per il senso inverso).
- **Una data sola o un'ora sola** (`Y-m-d`, `H:i`) non è un istante: `timetostr()` e Date non la convertono. Un giorno
  intero salvato in una colonna data-e-ora si passa come `Y-m-d` (vedi `Calendar::format_dt()`).
- **Un'ora nell'ora del sito**, chiunque sia il visitatore (un palinsesto): `new \DateTime('now', nf_fuseau_site())`.

## Il front end: Bootstrap 5, JavaScript puro, CSP rigorosa

- **Bootstrap 5.3**, **senza jQuery**. Le classi di griglia portano sempre un breakpoint (`col-12 col-lg-8`);
  `tools/check-classes-bs4.php` rifiuta le classi di Bootstrap 4.
- **CSP rigorosa basata su nonce**: ogni risposta HTML porta un nonce casuale, messo su ogni `<script>` in linea dal
  filtro di `index.php`, e `script-src` consente solo `'self'`, quel nonce, le origini del fornitore di captcha
  **attivo** — nessuna con ALTCHA, quello predefinito — e Google Analytics **se** è configurato un ID. **Nessuno script
  da un CDN.** Il JS inserito dinamicamente passa da `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`, che rieseguono i
  `<script>` aggiunti con il nonce giusto, anche nell'iframe dell'Editor live.
- **`window.NF`**, definito nel modello principale, sostituisce le poche primitive che servivano — e niente di più (non
  è un mini-jQuery):

| Primitiva | Ruolo |
|---|---|
| `NF.ready(fn)` | esegue `fn` quando il DOM è pronto (o subito se lo è già) |
| `NF.data(el, 'my-key')` | legge `data-my-key` con la conversione di jQuery (numero, booleano, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` con `X-Requested-With` sulla stessa origine, corpo `form-urlencoded`, array come `key[]`; **rifiuta** su uno stato di errore; `dataType: 'text'` altrimenti JSON |
| `NF.post(url, data)` | scorciatoia POST |
| `NF.setHtml(el, html)` | `innerHTML` + esecuzione degli script |
| `NF.insertHtml(target, position, html)` | `insertAdjacentHTML` + esecuzione dei soli script aggiunti |
| `NF.replaceHtml(el, html)` | sostituisce l'elemento, script eseguiti |
| `NF.runScripts(root)`, `NF.loadScript(src)` | rieseguire, caricare con il nonce |
| `NF.bandeaux()` | dispone i banner in cima alla pagina e ripubblica `--nf-haut` e `--nf-entete` (vedi *Creare un tema*); richiamala dopo aver aggiunto un banner |

- Le finestre modali (`js/modal.js`), le notifiche (`js/notify.js`) e le conferme (`js/confirm.js`) sono caricate dai
  temi.
- Due controlli proteggono questo front end: `check-js-sources` (sintassi, PHP interpolato tollerato, e nessun `$(`) e
  `check-js-console` (le pagine reali, in un browser reale: nessun errore, nessuna violazione della CSP). Il banco
  `tests/Browser/*.test.html` + `tools/check-js.php` prova il **contratto** di uno script sul file reale, con dei doppi
  di `fetch`.

## Sicurezza: ciò che esiste già

Prima di scrivere il tuo: `Rate_Limit` (per chiave, ad es. `contact:ip:<ip>`), `Audit_Log` (azioni sensibili),
`File_Jail` (percorsi di caricamento), `Moderation`, TOTP per l'autenticazione a due fattori, `sanitize_html()`
(HTMLPurifier) per l'HTML ricco, `is_dangerous_upload()`, e la modalità demo (`nf_demo()`), che blocca la scrittura dei
moduli sensibili.

**Un indirizzo scritto da qualcuno e messo in un `href` o in un `src`** passa da `nf_url_sure($url)`: restituisce
`FALSE` per uno schema diverso da `http`, `https`, `mailto`, `tel` o `geo` — un `javascript:` verrebbe eseguito al clic
con i diritti di chi clicca. Un indirizzo relativo passa. `nf_texte()` non basta: fa l'escape delle virgolette,
non dello schema. `url()` restituisce `#` per un indirizzo assoluto rifiutato.

### Il sito demo

La demo è condivisa: il suo account `demo` è amministratore. La rete di sicurezza è in `neofrag/helpers/system.php`, e
agisce davanti a ogni controller (`core/output.php`):

- **`NF_DEMO_MODULES_VERROUILLES`** — i moduli in cui nessuna azione passa nella demo: ogni POST, ogni link che porta un
  token (`?_=`), ogni metodo il cui nome contiene una parola d'azione (`NF_DEMO_MOTS_D_ACTION`: export, test, toggle…)
  viene rifiutato — un 403 JSON per una chiamata AJAX, altrimenti una notifica e un ritorno alla pagina precedente
  (`nf_demo_requete_refusee()`). **Un nuovo modulo che tocca la configurazione del sito si aggiunge a questo elenco**: è
  una lista di blocco, un modulo assente resta aperto. `check-demo-lock` lo ricorda.
- **`NF_DEMO_LECTURES`** — le poche letture consentite in un modulo bloccato (`controleur::methode`), che restituiscono
  allora esempi fittizi.
- **`nf_demo_ecriture_permise($module)`** — la domanda da fare in un codice che scrive fuori dalla rete; vera fuori
  dalla demo.
- **`nf_compte_masque()`** — l'account di emergenza della demo, che nessun elenco di membri mostra:
  `->where('u.id !=', nf_compte_masque())`. Fuori dalla demo, vale 0.
- Nella demo, `File::delete()` non elimina nulla e i caricamenti di file vengono rifiutati. Una pagina che mostrerebbe
  un file, un registro o un'impostazione sensibile mostra invece un esempio.
- L'account condiviso non si può cambiare dall'area membri: né nome utente, né indirizzo, né password, né autenticazione
  a due fattori, né eliminazione — un visitatore chiudeva fuori gli altri fino al ripristino. Poiché la rete copre solo
  l'amministrazione, queste pagine fanno il controllo da sé (`nf_demo()`, `modules/user/controllers/index.php`).

## Eventi

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` dal lato emittente;
`$this->events->on('forum.post.created', function (...) { … })` dal lato abbonato. Gli ascoltatori restano attivi per
tutta la richiesta.

## Helper utili

- `url('forum/42')` — costruisce un URL instradato (prefisso di lingua compreso).
- `notify('Messaggio')` — una notifica sullo schermo dopo un reindirizzamento.
- `nf_texte(...)` / `sanitize_html(...)` — escape / pulizia anti-XSS.
- `nf_url_sure($url)` — se un indirizzo scritto può andare in un link (vedi Sicurezza).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — una data del database, da mostrare (vedi Date).
- `nf_demo()` — vero sul sito demo.

## Migrazioni

Lo schema del **nucleo** evolve tramite migrazioni versionate, registrate in `nf_migrations`:

```bash
php tools/migrate.php status                 # applicate / in sospeso
php tools/migrate.php up [--pretend]          # applica (simulazione con --pretend)
php tools/migrate.php down [--step=N]         # annulla
php tools/migrate.php baseline --until=NAME   # adottare un database esistente
```

File: `migrations/YYYY_MM_DD_name.up.sql` (+ `.down.sql`). Il DDL di MySQL fa il commit da solo → **fai un backup prima
di `up` in produzione**. Gli **addon** hanno le proprie migrazioni (`<addon>/install/migrations/`, registrate in
`nf_addon_migrations`) — vedi [Creare un modulo](create-a-module.md).

**Il codice nuovo applica da sé le sue migrazioni**, una volta, alla prima pagina vista dopo l'aggiornamento — dal
pulsante come via FTP: quelle del nucleo, poi quelle degli addon installati (`nf_migrations_du_code()`, chiamata da
`index.php` quando l'impostazione `nf_migrations_version` differisce da `NEOFRAG_VERSION`). Vale per tutti i siti, demo
compresa. Un fallimento non impedisce alla pagina di mostrarsi: viene registrato, e il tentativo successivo aspetta
dieci minuti.

## Sovrascrittura a tre livelli

Risoluzione delle viste, delle classi e dei file (vince il primo trovato):

1. `overrides/{type}/{file}` — globale
2. `themes/{tema attivo}/overrides/{type}/{file}` — per tema
3. l'originale incluso

Personalizzi senza fare un fork e senza rompere gli aggiornamenti. Attenzione: due file con lo stesso nome su due
livelli, e `path()` serve l'uno o l'altro a seconda di chi chiama — una procedura guidata si è mostrata una volta senza
stile a causa di un duplicato (`tools/check-assets.php` ora lo rifiuta).

## Ambiente di sviluppo

Il progetto gira su **qualsiasi PHP 8.2+ con MySQL o MariaDB** (Apache + `mod_rewrite`, o nginx, o Caddy). Il percorso di
riferimento del progetto è un'**installazione di test su un server**, dove gira la batteria completa:
`php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped` e `composer stan`. L'ambiente, il
database di test e il ciclo di lavoro sono descritti in [`docs/development.md`](../../development.md); ogni strumento è
descritto in [`tools/README.md`](../../../tools/README.md).
