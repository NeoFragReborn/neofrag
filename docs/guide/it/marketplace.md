# Il marketplace

Il **marketplace** serve ad **aggiornare** i tuoi addon e a **installare quelli che non sono sul tuo server**. Offre
solo gli addon ufficiali di NeoFrag Reborn, che il pacchetto di installazione contiene già tutti: serve quindi
soprattutto per gli aggiornamenti, e per recuperare un addon che avevi eliminato. Interviene *dopo* l'installazione,
dall'amministrazione.

Il catalogo e gli archivi sono serviti da **neofrag-reborn.xyz**, in HTTPS; ogni archivio viene verificato tramite la
sua **impronta SHA-256** al download. Ogni versione li pubblica anche, con il catalogo, nella
[pagina delle versioni](https://github.com/NeoFragReborn/extensions/releases) del repository degli addon opzionali.

Il tuo sito riceve **il catalogo della sua versione**: a un sito non ancora aggiornato vengono offerti gli addon fatti
per la versione che esegue, non quelli dell'ultima, che potrebbero chiamare funzioni che il suo nucleo non ha ancora. E
se un addon chiede una versione più recente di NeoFrag, la finestra del marketplace non lo offre: ti dice di aggiornare
prima il tuo sito.

## Installare un addon

Due strade:

### 1. Con un clic dall'amministrazione

**Admin → Temi & Addons → Marketplace**. La finestra elenca gli addon disponibili non installati; spuntali e clicca su
**Installa** — NeoFrag scarica, verifica l'impronta SHA-256, estrae e registra l'addon (e il suo widget associato, se
c'è).

### 2. A mano (archivio ZIP)

La pagina (pubblica) **Marketplace** presenta ogni addon in una scheda con un pulsante **Scarica**. Puoi anche scaricare
lo `.zip` e poi **Admin → Temi & Addons → Aggiungi** e inviare l'archivio (il caricamento viene convalidato: gli archivi
con contenuto non sicuro vengono rifiutati). È anche la strada per un addon che hai scritto tu: comprimi la sua cartella
e inviala.

## Aggiornare o rimuovere

- **Aggiornare**: **Admin → Temi & Addons → Aggiornamenti** confronta la versione installata di ogni addon con quella
  del catalogo ed elenca quelle da aggiornare; applicale con un clic (download + SHA-256, file sostituiti, **migrazioni
  di schema in sospeso applicate**). Altrimenti, inviare un nuovo `.zip` tramite *Aggiungi* aggiorna anch'esso (una
  versione superiore sostituisce la vecchia).
- **Rimuovere**: dal menu della sua scheda in **Temi & Addons**. Un modulo o un widget: **Disinstalla** — le sue
  tabelle, i suoi dati e i suoi file vengono rimossi, dopo un backup fatto subito prima (cartella `backups/addons`
  del server); un modulo di cui un altro addon ha bisogno viene rifiutato, e la finestra dice quali. Un tema:
  **Elimina** (non il tema attivo). Gli addon del nucleo non si rimuovono.
- **Azzera** (un modulo): i suoi dati vengono cancellati e riparte come il giorno della sua installazione; le sue
  impostazioni, i suoi permessi e i link che vi portano restano. Prima viene fatto un backup delle sue tabelle.

> I **connettori di autenticazione** (Discord, GitHub, Google) fanno parte del **nucleo**: ci sono già, e si
> configurano nelle impostazioni — non passano dal marketplace.

> **Marketplace irraggiungibile?** Se la schermata «Aggiornamenti» mostra «Marketplace irraggiungibile», controlla che
> il tuo server possa uscire in **HTTPS** verso `neofrag-reborn.xyz`. Il catalogo è fissato a quell'origine: l'elenco
> degli host autorizzati è scritto nel codice (`MARKETPLACE_HOSTS`, `neofrag/installer.php`), e l'impostazione
> `nf_marketplace_url`, senza schermata di amministrazione, può indicare solo un indirizzo HTTPS su quegli host.

## Sicurezza

Il marketplace scarica solo da un'**origine fissa** in **HTTPS** (mai un URL inserito dall'utente), verifica lo
**SHA-256** di ogni archivio rispetto al catalogo e **rifiuta ogni archivio manomesso** (anti zip-slip: percorsi
assoluti, `..`, link simbolici). Nessun codice remoto viene eseguito all'aggiunta o all'aggiornamento di un addon (solo
estrazione + `install.sql` e migrazioni SQL idempotenti).

## Per gli autori di addon

Il codice degli addon opzionali vive in [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions): proporre
un addon o una correzione significa aprire lì una richiesta di fusione. Il catalogo è generato da
`tools/package-addons.php`, che comprime ogni addon (una cartella `<name>/` nella radice dell'archivio) e produce
`marketplace/catalog.json`. Una voce reale, abbreviata:

```json
{
  "schema": 1, "base_version": "1.2.22",
  "addons": [{
    "type": "module", "name": "quotes", "tier": 2, "category": "contenu",
    "title": "Citations", "description": "Recueil de citations classées, avec leur auteur et leur source.",
    "i18n": { "en": { "title": "Quotes", "description": "…" } },
    "version": "1.0", "author": "NeoFrag Reborn", "license": "LGPLv3 <https://neofr.ag/license>",
    "file": "modules/quotes.zip", "preview": "modules/quotes.jpg", "size": 60556, "sha256": "…",
    "requires": { "base": ">=1.2.22", "addons": [] }, "provides_widgets": [], "install": "zip"
  }]
}
```

`catalog.json` e gli `.zip` si pubblicano **insieme**: le impronte SHA-256 valgono solo per gli archivi dello stesso
passaggio. Il catalogo ufficiale viene pubblicato con ogni versione, e un sito non può seguirne un altro senza modificare
`MARKETPLACE_HOSTS`. Un addon che scrivi tu — guide: [creare un modulo](create-a-module.md),
[un widget](create-a-widget.md), [un tema](create-a-theme.md) — si installa su qualsiasi sito tramite *Temi & Addons →
Aggiungi*.

## Aggiornare il nucleo (NeoFrag stesso)

**Admin → Temi & Addons → Aggiornamenti** **segnala** una nuova versione del nucleo se ce n'è una: quella annunciata dal
canale di aggiornamento (il Monitoraggio lo consulta) e, altrimenti, il `base_version` del catalogo, la versione del CMS
per cui è stato costruito.

Due strade per applicarla.

### Con un clic dal Monitoraggio

**Admin → Monitoraggio → Aggiorna**. Il sito fa prima un **backup**, poi scarica il pacchetto di aggiornamento, ne
verifica l'impronta, sovrappone i file, applica le migrazioni in sospeso e ricompila i fogli di stile.

Il pulsante accetta solo l'aggiornamento giusto, grazie a quattro garanzie:

1. l'**origine** viene dalla stessa lista bianca del marketplace, e un valore iniettato nel database non può uscirne;
2. `version.json` fornisce solo un **nome di file**, mai un URL: né host, né percorso, quindi né reindirizzamento né
   risalita di cartelle;
3. l'impronta **SHA-256** viene verificata **prima** di toccare un solo file del sito;
4. l'archivio viene controllato **voce per voce** (anti zip-slip, link simbolici rifiutati).

I file già presenti in `config/` e il blocco `install/db.txt` non vengono mai riscritti: la configurazione di un sito in
funzione viene conservata. Il resto di `install/` è codice del prodotto, e segue le versioni.

### A mano

Scarica `neofrag-reborn-public-<version>.zip` dalla
[pagina delle versioni](https://github.com/NeoFragReborn/neofrag/releases) e sostituisci i file (tranne `config/`,
`upload/`, `backups/`), poi visita il sito — le migrazioni si applicano da sole
([guida al deployment](../../deploy-ftp.md#mettre-à-jour-un-site-déjà-en-ligne)).

### Cosa pubblica ogni versione

Ogni versione pubblica sull'origine di aggiornamento (`https://neofrag-reborn.xyz/update/`) tre file che formano un
insieme coerente: il pacchetto di aggiornamento, `version.json` (la versione, il nome del pacchetto, il suo SHA-256 e
la sua dimensione) e `checksum.json` (un'impronta per ogni file incluso, per il controllo di integrità del
Monitoraggio). Il pulsante verifica ciascuno rispetto agli altri: pubblicarne uno senza gli altri fa fallire
l'aggiornamento, senza toccare nulla del sito.
