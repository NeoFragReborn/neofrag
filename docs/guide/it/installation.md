# Installazione

NeoFrag Reborn si installa su un normale hosting web, **hosting condiviso compreso**: non servono né Docker, né
Composer, né accesso alla shell — il pacchetto include le sue dipendenze.

## Requisiti

- **PHP da 8.2 a 8.5** con le estensioni `mysqli`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo` e
  `iconv`, e un PHP capace di cifrare le password con **Argon2** (quasi tutti lo sono). Si attivano nel pannello del tuo
  hosting, spesso alla voce «Versione PHP» o «Estensioni». L'installer le controlla tutte e si rifiuta di proseguire se
  ne manca una.
- **MySQL 5.7, 8.0 o 8.4**, oppure **MariaDB 10.5, 10.6, 10.11, 11.4 o 11.8**, con il motore **InnoDB** e il set di
  caratteri **utf8mb4**. Sono le versioni su cui ogni rilascio del CMS viene provato prima di uscire, con PHP 8.2 e 8.5:
  installazione completa, tutta la suite di test e ogni profilo di installazione.
- **Apache** con `mod_rewrite` (un file `.htaccess` è incluso); oppure **nginx** (`nginx.conf`); oppure **Caddy**
  (`Caddyfile`).
- Un database **vuoto** e le sue credenziali.
- Un vero **nome di dominio** che punta al server: l'installer ne ricava l'indirizzo di contatto del sito
  (`noreply@tuo-dominio`) — installato su un indirizzo IP, un sito non avrebbe un indirizzo mittente valido.

## L'installer

L'installer si svolge in **cinque passi**: *Requisiti* → *Profilo del sito* → *Database* → *Amministratore* →
*Terminato*.

1. **Carica** i file di NeoFrag Reborn nella radice del tuo sito: il contenuto della cartella `neofrag-reborn/` del
   pacchetto `neofrag-reborn-public-<versione>.zip` (via FTP, o decomprimendolo sul server). Il pacchetto si scarica
   dalla [pagina delle versioni](https://github.com/NeoFragReborn/neofrag/releases/latest) del progetto.
2. Apri il tuo dominio: l'installer si avvia ed elenca i **requisiti** con il valore che ha trovato (versione di PHP,
   estensioni presenti).
3. Scegli il **profilo del sito**:

   | Profilo | Cosa installa |
   |---|---|
   | **Completo** | il nucleo e tutti i moduli del pacchetto |
   | **Gaming / eSport** | il nucleo e l'identità gaming: forum, squadre, giochi, eventi, reclutamento, palmarès… |
   | **Community** | il nucleo, notizie, forum, galleria, eventi, collettivi e l'API (per il bot Discord e le integrazioni) |
   | **Associazione / club** | il nucleo, notizie, forum, galleria, eventi, calendario, collettivi, donazioni, quote associative (pagamenti), newsletter, wiki e FAQ |
   | **Solo nucleo** | account, permessi, pagine, impostazioni — niente di più |

   I moduli si possono comunque deselezionare uno per uno; i moduli di cui un modulo ha bisogno vengono aggiunti
   automaticamente, e la schermata finale lo dice. Il modulo funziona anche senza JavaScript.
4. Compila la **connessione al database**: l'installer importa lo schema, installa i moduli, i widget e i temi scelti,
   e prepara le disposizioni predefinite.
5. Crea l'**account amministratore**. Fatto.

I moduli che non hai installato si aggiungono più tardi da **Amministrazione → Temi & Addons**, e quelli che non sono
nel pacchetto dal [marketplace](marketplace.md).

## Installazione da riga di comando

Per un deployment **automatizzabile** (server, provisioning), `install/cli.php` fa quello che fa l'installer, senza
browser — requisiti compresi: si rifiuta di proseguire se ne manca uno. Installa il profilo **Completo**.

```bash
# Password dell'amministratore tramite una variabile d'ambiente (nascosta dall'elenco dei processi):
export NF_ADMIN_PASS='la-mia-password-robusta'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="La mia community" --site-url=https://site.tld --yes
```

Senza argomenti, passa in **modalità interattiva** (password nascosta). Opzioni: `--db-port`, `--create-db` (crea il
database), `--dry-run` (convalida la configurazione e prova la connessione senza scrivere nulla), `--force`
(reinstalla), `--no-lock` (non mette il blocco), `--lang=fr|en|de|es|it|pt` (la lingua dei suoi messaggi), `--help`.

## Mettere in sicurezza l'accesso dopo l'installazione

Alla fine, l'installer mette un **blocco** (`install/db.txt`): tornare su `/install/` non mostra più nulla di
utilizzabile. Per prudenza, **elimina o rinomina la cartella `install/`** — non serve più. Attiva l'**HTTPS** se il tuo
hosting non l'ha fatto. Con nginx o Caddy, parti dagli esempi nella radice (`nginx.conf`, `Caddyfile`): bloccano già
l'accesso alle cartelle e ai file sensibili, come fa il file `.htaccess` con Apache.

## Prime impostazioni

1. **Amministrazione → Impostazioni**: nome del sito, descrizione, favicon, **indirizzo di contatto** (controlla che sia
   valido e che un server di posta possa inviare — altrimenti il modulo di contatto e le email di iscrizione non
   arrivano mai; un server SMTP si imposta in *Impostazioni → Email*, e *Email → Invio di prova* lo dimostra), pagina
   iniziale.
2. **Amministrazione → Temi & Addons**: il tuo tema, i tuoi moduli.
3. **Amministrazione → Editor live**: componi le tue pagine (metti i tuoi widget nelle regioni).
4. **Amministrazione → Utenti / Permessi**: crea i tuoi ruoli e regola gli accessi.
5. **Attività pianificate**: le pubblicazioni programmate, le newsletter e i promemoria degli eventi partono tramite il
   **cron** del sito (il Monitoraggio ne dà l'indirizzo e la chiave). Aggiungi un'attività ogni cinque minuti presso il
   tuo hosting; senza, nulla di programmato viene inviato.

I passi successivi, nell'ordine: [Primi passi](premiers-pas.md).

## Risoluzione dei problemi

- **«Impossibile connettersi al database»**: controlla l'host (spesso `localhost`, a volte un indirizzo dedicato sugli
  hosting condivisi), la porta (`3306`), il nome del database — **deve esistere ed essere vuoto** — e le credenziali.
- **«Estensione PHP mancante»**: attiva l'estensione segnalata dal pannello del tuo hosting; da riga di comando,
  `php -m`.
- **Cartella `config/` non scrivibile**: l'installer deve scriverci `db.php` e i segreti. Dai i permessi di scrittura
  a `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Installazione interrotta**: MySQL non può annullare un `CREATE TABLE` eseguito a metà — **crea un nuovo database
  vuoto** prima di riprovare.
- **Indirizzi in 404 o AJAX rotto con nginx o Plesk**: `.htaccess` viene letto solo da Apache; vedi la nota
  «nginx / Plesk» nella [guida al deployment](../../deploy-ftp.md).
- **Nessuna email parte**: guarda *Sistema → Monitoraggio → Registro degli errori* (una riga `[email] échec envoi …`);
  l'indirizzo di contatto deve essere valido e deve esistere un trasporto (un server SMTP configurato, o un agente di
  posta locale).
- **«Si è verificato un errore», con un riferimento**: la pagina si è bloccata. Il dettaglio è nel registro degli
  errori; il riferimento ritrova la riga giusta.

## Sviluppo

Il codice è su GitHub: il CMS in [NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag), gli addon opzionali
in [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) — `tools/assembler.php` li colloca nell'albero
del CMS. Per sviluppare o provare va bene qualsiasi macchina con PHP 8.2+ e MariaDB o MySQL; è fornito uno stack Docker
(`docker-compose.yml`). I dettagli (database, migrazioni, test, controlli, deployment):
[`docs/development.md`](../../development.md).
