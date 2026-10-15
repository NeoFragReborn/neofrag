# Il bot Discord

Il bot Discord di NeoFrag Reborn collega il tuo sito e il tuo server Discord. Le sue funzionalità si accendono una per
una:

- **ruoli e soprannomi** — un membro che ha collegato il suo account Discord riceve sul server i ruoli collegati ai
  suoi gruppi, e il suo nome del sito se lo desideri;
- **forum e canali Forum** — un forum del sito e un canale Forum di Discord vivono insieme: una discussione diventa un
  thread, un thread una discussione, le risposte seguono nei due sensi, e i prefissi del forum diventano i tag del
  canale;
- **account e aspetto** — il comando `/forum`: collegare l'account da Discord, e scegliere come si appare sul forum
  senza account collegato;
- **Bugtracker** — ogni ticket diventa un thread, il suo tipo e il suo stato dei tag; `/bug` e `/idee` aprono un
  ticket da Discord;
- **ruoli temporanei** — il comando `/role`: dare un ruolo per un tempo limitato, che il bot toglie alla fine;
- **sanzioni collegate** — i silenziamenti e i ban del sito si applicano su Discord, e quelli di un moderatore
  collegato possono passare al sito;
- **collettivi** — ogni collettivo del sito riceve un ruolo e un canale privato, che solo i suoi membri vedono.

Si **regola interamente dall'amministrazione** del sito: il suo token, il suo server, le sue funzionalità e le loro
impostazioni, l'interruttore avvio / pausa, il riavvio, le corrispondenze, e perfino la creazione di canali e ruoli sul
server. L'amministrazione mostra anche il suo stato e il suo registro, nella tua lingua.

Il bot è un **programma a parte** che gira senza sosta. Ha bisogno di una macchina accesa — un VPS, un Raspberry Pi, un
PC — con **Node.js 22.9 o successivo**. Un hosting condiviso non può farlo girare; il sito, invece, non ne ha bisogno e
resta dov'è.

## 1. Creare l'applicazione Discord

1. Nel [portale degli sviluppatori di Discord](https://discord.com/developers/applications): *New Application*, dagli un
   nome.
2. Scheda *General Information*: annota l'**Application ID**.
3. Scheda *Bot*: *Reset Token*, e copia il **token del bot** — viene mostrato una sola volta.
4. Sempre in *Bot*, spunta i due **intent privilegiati**:
   - **Server Members Intent** — per i ruoli, i soprannomi e i ruoli temporanei;
   - **Message Content Intent** — per copiare sul sito il testo dei messaggi di Discord (forum, Bugtracker).

   Senza di loro, il bot funziona in modalità ridotta e lo dice nel suo registro: non chiede mai a Discord più di quanto
   l'applicazione abbia il diritto di ricevere.

## 2. Configurare il modulo Discord

Il bot si appoggia a due moduli del sito, **API** e **Discord**. Il profilo di installazione *Completo* li installa
entrambi; altrimenti, *Sistema → Temi & Addons* li aggiunge (Discord, un addon opzionale, arriva dal marketplace se non
è sul server). La sincronizzazione del forum ha bisogno anche del modulo **Forum**, quella dei ticket del modulo
**Bugtracker**.

*Amministrazione → Discord → Connessione*:

| Campo | Dove trovarlo |
|---|---|
| Application ID | l'*Application ID* del passo 1 |
| ID del server | in Discord, con la modalità sviluppatore attiva: clic destro sul server → *Copia ID server* |
| Token del bot | il token del passo 1. Viene **conservato cifrato** e non viene più mostrato; lascia il campo vuoto per tenere quello registrato |

Poi **Invita il bot** (pagina iniziale del modulo): il link aggiunge il bot al server con i soli permessi che gli
servono — mai «Amministratore»:

| Permesso | Per |
|---|---|
| Visualizzare i canali, Inviare messaggi, Leggere la cronologia dei messaggi | leggere e scrivere nei canali collegati |
| Inviare messaggi nei thread, Creare thread pubblici, Gestire i thread | copiare una discussione o un ticket come thread, rinominarlo, archiviarlo, mettere i suoi tag |
| Gestire i canali | la configurazione del server: creare una categoria, dei canali Forum e i loro tag |
| Gestire i messaggi | riportare su Discord un'eliminazione fatta sul sito |
| Gestire i webhook | pubblicare i messaggi del sito con il nome e l'avatar del loro autore |
| Aggiungere reazioni, Incorporare link, Allegare file | messaggi completi |
| Gestire i ruoli, Gestire i nickname | i ruoli, i soprannomi e i ruoli temporanei |
| Usare i comandi dell'applicazione | `/forum`, `/bug`, `/idee`, `/role` |

Nelle impostazioni del server (*Ruoli*), metti il ruolo del bot **sopra** i ruoli che assegna: Discord vieta a un bot di
dare un ruolo posto più in alto del suo.

## 3. Installare il bot sulla sua macchina

Il bot si scarica come archivio già compilato, `neofrag-reborn-bot-X.Y.Z.tar.gz`, dalla
[pagina delle versioni](https://github.com/NeoFragReborn/bot-discord/releases/latest) del suo repository. Ha i suoi
numeri di versione, indipendenti da quelli del sito.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Amministrazione → Discord → Crea la chiave di accesso*: la pagina dà **due righe** — l'indirizzo del sito e la chiave
di accesso del bot — da incollare in `.env`. Vengono mostrate una sola volta; creare una nuova chiave revoca la vecchia.
Poi:

```bash
npm start
```

Perché giri come servizio, parta con la macchina e si riavvii se cade: `neofrag-bot.service` (systemd, rinforzato)
spiega la sua installazione all'inizio del file. Il suo registro: `journalctl -u neofrag-bot -f` — e, soprattutto,
l'amministrazione del sito.

Sulla macchina restano solo queste due righe. Tutto il resto arriva dal sito.

**Aggiornare il bot**: sostituisci la cartella con quella del nuovo archivio conservando la tua configurazione (`.env`,
o `/etc/neofrag-bot/bot.env` con systemd), poi `npm ci --omit=dev` e riavvia il servizio. Una nuova versione del bot
che ha bisogno di più diritti sul sito lo dice nell'amministrazione: crea allora una nuova chiave di accesso.

## 4. Guidarlo dall'amministrazione

*Amministrazione → Discord* mostra:

- il suo **stato** — *Online*, *In pausa* (vivo, interruttore spento), *Connessione a Discord…* oppure *Offline*
  (nessuna notizia da un minuto e mezzo) —, la sua versione, il suo ultimo segnale di vita;
- i pulsanti **Avvia / Metti in pausa**, **Riavvia** e **Risincronizza** (rimettere tutto d'accordo: ruoli e
  soprannomi, e ciò che è stato scritto su Discord durante un'assenza del bot), che applica entro il minuto successivo;
- il suo **registro**: connessioni, sincronizzazioni e ciò che gli impedisce di funzionare (intent non spuntato, ruolo
  mal posizionato, permesso mancante, chiave di accesso troppo vecchia), con ciò che bisogna fare.

**Cambiare il token del bot** (fuga, rigenerazione): incolla il nuovo in *Connessione*. Il bot lo rilegge e si riconnette
da solo; la macchina non si tocca.

### Funzionalità

*Discord → Funzionalità* elenca ciò che il bot sa fare — le dichiara lui stesso a ogni segnale di vita: una
funzionalità aggiunta al bot compare lì senza toccare il sito. Ognuna si **accende** o si **spegne**, e le sue
**impostazioni** si cambiano lì; il bot applica il cambiamento entro il minuto successivo, senza riavvio. Il
Bugtracker, i ruoli temporanei, le sanzioni collegate e i collettivi sono spenti all'inizio.

### Configurazione del server

*Discord → Configurazione del server* crea su Discord ciò che serve per collegare il sito, senza fare nulla a mano:

- una **categoria** (riutilizzata se esiste già);
- un **canale Forum per ogni forum** scelto, con i prefissi del forum come tag;
- un **ruolo per ogni gruppo** scelto, posto sotto quello del bot perché possa assegnarlo.

Un canale o un ruolo con lo stesso nome viene riutilizzato invece di essere duplicato. Un'**anteprima** mostra ciò che
verrà creato e riutilizzato; **Applica** lo fa, e le corrispondenze (canale ↔ forum, gruppo ↔ ruolo) si impostano da
sole. **Annulla l'ultima configurazione** elimina ciò che ha creato — mai ciò che ha riutilizzato.

## Ruoli e soprannomi

*Discord → Gruppi e ruoli* collega un gruppo del sito a un ruolo del server. **Il sito fa fede**:

- un membro collegato che è nel gruppo riceve il ruolo; chi non c'è più lo perde;
- un ruolo che nulla collega **non viene mai toccato**; e nemmeno un membro che non ha collegato il suo account;
- con l'impostazione *Soprannomi*, il membro collegato porta sul server il suo nome del sito (tranne il proprietario del
  server, che Discord protegge);
- un ruolo collegato porta il **nome e il colore del suo gruppo** (bot 0.2.5): un gruppo rinominato o ricolorato sul
  sito lo è anche sul server. Un ruolo collegato a più gruppi conserva i suoi, e un gruppo senza colore lascia quello
  del ruolo. L'impostazione «Dare ai ruoli collegati il nome e il colore del loro gruppo» lo spegne.

Il bot applica un cambio di gruppo entro mezzo minuto, l'arrivo di un membro collegato sul server e il collegamento di
un account all'istante, e ripassa su tutti all'avvio, a ogni cambio di impostazione e all'intervallo scelto (dieci minuti
per impostazione predefinita).

## L'account e l'aspetto: `/forum`

Un membro collega il suo account accedendo al sito con Discord, da *Il mio account → I miei account collegati*, oppure
**da Discord**:

- `/forum account link` dà un link a uso singolo, valido quindici minuti. Sul sito, connesso, il membro conferma il
  collegamento — e può riprendere a suo nome ciò che aveva pubblicato da Discord;
- `/forum account unlink` scollega, tranne se Discord è il suo unico modo di accedere al sito.

Chi non ha collegato il suo account sceglie come appare sul forum del sito:

- `/forum visibility public` — con il suo nome Discord;
- `/forum visibility guest` — con un nome anonimo;
- `/forum visibility custom` — con un nome a sua scelta (da 2 a 32 caratteri, modificabile ogni sette giorni, mai
  quello di un membro del sito);
- `/forum visibility status` — ciò che è impostato.

I suoi messaggi già pubblicati seguono la sua scelta. Tutte le risposte sono private, nella lingua Discord di ciascuno.

## Il forum e i canali Forum

*Discord → Canali e forum* collega un forum del sito a un **canale Forum** di Discord (non un canale testuale). Due
modalità, canale per canale:

- **Tutto**: ogni thread del canale diventa una discussione del sito, e ogni discussione del forum un thread;
- **Su richiesta**: un thread passa al sito solo quando qualcuno che può *gestire i thread* del canale mette la
  **reazione** scelta (📌 per impostazione predefinita) — il thread intero, e poi ciò che segue. Le discussioni del
  sito, già pubbliche, vanno sempre su Discord.

Cosa segue, e come:

| | Dal sito a Discord | Da Discord al sito |
|---|---|---|
| Nuova discussione / thread | un thread, con il nome e l'avatar dell'autore | una discussione |
| Risposta | un messaggio nel thread | una risposta (la citazione di un messaggio viene conservata) |
| Modifica | il messaggio viene riscritto, il titolo del thread segue | il messaggio viene riscritto |
| Eliminazione | il messaggio scompare; una discussione eliminata si porta via il suo thread | il messaggio va nel cestino del forum |
| Prefisso / tag | viene messo il tag corrispondente | viene messo il prefisso corrispondente |

- **Prefissi e tag** (pulsante di ogni canale collegato): un prefisso del forum ↔ un tag del canale. La configurazione
  del server li collega da sola.
- Un messaggio venuto da Discord viene pubblicato con l'**account del membro** che ha collegato il suo Discord;
  altrimenti, con la sua **identità Discord**, come l'ha scelta con `/forum visibility`, contrassegnata dal logo di
  Discord.
- Le **sanzioni di moderazione** del sito seguono il membro collegato: silenziato o bannato dal forum, i suoi messaggi
  Discord non vengono copiati; privato dei link esterni, nemmeno un messaggio che ne contiene (bot 0.2.5). Il registro
  del bot lo annota, senza dire nulla nel canale; `/bug` e `/idee` rispondono solo all'autore.
- Un messaggio troppo lungo per Discord (2000 caratteri) viene tagliato lì, con un link al seguito.
- Le **immagini** di un messaggio del sito vanno su Discord come anteprime, sotto il testo; i link del messaggio non vi
  mostrano una scheda di anteprima.
- Un'**immagine allegata su Discord** (JPEG, PNG, GIF o WebP, 5 MB al massimo) viene conservata sul sito e mostrata nel
  messaggio del forum — un sito troppo vecchio per riceverla conserva il link. Gli altri allegati, e tutti quelli del
  Bugtracker, sono segnalati con un link al messaggio Discord.
- Impostazioni: un link al sito sotto ogni discussione copiata, e il **recupero** all'avvio di ciò che è stato scritto
  su Discord mentre il bot era spento.
- I **permessi** di ogni canale collegato seguono i diritti del suo forum sul sito (bot 0.2.5, impostazione accesa per
  impostazione predefinita). Tutto il server conta come i membri del sito: chi non può leggere il forum non vede il
  canale, chi non può scriverci non vi pubblica — la scrittura solo in modalità **Tutto**; in modalità **Su richiesta**,
  Discord resta un luogo di conversazione. Un ruolo collegato dà al suo gruppo ciò che i membri non hanno: il canale di
  un forum dello staff si mostra solo ai ruoli dello staff. Il bot tocca solo la visualizzazione e la scrittura, per
  @everyone, i ruoli collegati e se stesso; qualsiasi altra impostazione fatta a mano resta.
- Il sito, da parte sua, si rifiuta di scrivere a nome di chi non ne ha il diritto: un messaggio venuto da Discord in un
  forum in cui il suo autore non può scrivere resta su Discord, e il registro del bot lo dice.
- Non vengono riportate: l'eliminazione di un thread intero su Discord (la discussione resta, il registro lo segnala),
  quella del suo messaggio iniziale, e su Discord la modifica di un messaggio scritto su Discord (appartiene al suo
  autore). La copia su Discord di un messaggio del sito, eliminata da un moderatore, non elimina l'originale.

## Il Bugtracker

Nelle impostazioni della funzionalità, scegli il **canale Forum dei ticket** (non un canale già collegato a un forum). Il
bot vi crea i tag dei tipi (Bug, Idea, Domanda, Altro) e degli stati (Aperto, In corso, Risolto, Chiuso, Non si farà,
Duplicato), nella lingua del sito.

Per separare le idee dai bug, scegli anche un **canale Forum dei suggerimenti** (bot 0.2.5): i ticket di tipo Idea vi
hanno il loro thread, con il tag Idea e quelli degli stati; gli altri restano nel canale dei ticket.

- Ogni ticket diventa un **thread**, con il nome e l'avatar del suo autore, con il suo numero, il suo tipo, la sua
  priorità e un link al sito. I ticket ancora aperti ricevono il loro thread quando scegli il canale — e, quando scegli
  il canale dei suggerimenti, le idee ancora aperte vi passano.
- Un ticket che **cambia tipo** (un bug diventato un'idea) cambia canale: Discord non sposta un thread, quindi il bot ne
  apre uno nuovo nel canale giusto. Il vecchio thread, chiuso, rimanda al nuovo, e il nuovo rimanda al vecchio. Sul
  sito, il ticket conserva tutti i suoi commenti.
- Quando il ticket cambia sul sito, il thread segue: tag, titolo, descrizione. Si **archivia** quando il ticket viene
  chiuso; un duplicato rimanda al suo ticket originale; un ticket eliminato si porta via il suo thread.
- I **commenti** passano nei due sensi. Su Discord, chi non ha collegato il suo account commenta con il suo nome
  Discord.
- `/bug` e `/idee` aprono un ticket tramite una piccola finestra (titolo, descrizione), e un thread aperto a mano in uno
  dei canali diventa un ticket — un'idea nel canale dei suggerimenti. Un ticket appartiene a un membro: bisogna aver
  collegato il proprio account (altrimenti il bot lo ricorda).
- **Il sito fa fede**: un tag cambiato a mano su Discord viene rimesso come dice il ticket.

## I ruoli temporanei: `/role`

Riservato a chi può **gestire i ruoli** sul server:

- `/role give` — dare un ruolo a un membro per un tempo (minuti, ore, giorni, settimane), con un motivo se si vuole. Dato
  di nuovo, si prolunga;
- `/role remove` — toglierlo subito;
- `/role list` — quelli in corso, per tutto il server o per un membro.

Il bot toglie il ruolo alla scadenza (controllata ogni minuto), **anche dopo un riavvio**: l'elenco vive sul sito, in
*Discord → Ruoli temporanei*, dove **Togli ora** anticipa la scadenza. Un membro che lascia il server e torna prima della
fine ritrova il suo ruolo (impostazione *Ridare il ruolo*).

Vengono rifiutati: `@everyone` e i ruoli di un'integrazione, i ruoli **collegati a un gruppo** del sito (li gestisce la
sincronizzazione dei gruppi), e — come fa Discord per questo diritto — un ruolo posto sopra il bot, o al livello di chi
lo dà o più in alto. La durata è limitata dall'impostazione *Durata massima* (90 giorni per impostazione predefinita,
un anno al massimo).

## Le sanzioni collegate

Spenta per impostazione predefinita: *Discord → Funzionalità → Sanzioni collegate*. Due sensi, ciascuno con la sua
impostazione.

**Dal sito a Discord** (*Applicare su Discord i silenziamenti e i ban di tutto il sito*, accesa con la funzionalità):
una sanzione di **tutto il sito** segue l'account Discord collegato del membro.

| Sul sito | Su Discord |
|---|---|
| Silenziamento | un timeout fino alla scadenza — rinnovato dal bot oltre i 28 giorni che Discord consente |
| Ban temporaneo | un ban, tolto dal bot alla scadenza |
| Ban definitivo | un ban |

Un avvertimento, una restrizione, uno shadow ban (deve restare invisibile) e la sanzione di un solo modulo (il forum, le
chat…) non passano mai. Una sanzione in attesa di convalida passa quando viene convalidata. Tolta sul sito, viene tolta
su Discord — il bot toglie solo ciò che ha messo lui (il suo motivo porta «NeoFrag #numero») o ciò che veniva da
Discord. Rilegge le sanzioni in corso all'avvio, poi ogni dieci minuti: una scadenza passata mentre era fermo viene
recuperata.

**Da Discord al sito** (*Far risalire al sito le sanzioni messe su Discord*, spenta per impostazione predefinita): un ban
o un timeout messo su Discord passa al sito se il moderatore è una persona il cui account Discord è collegato a un
account del sito che ha quel diritto — *moderare* per un timeout (un silenziamento sul sito), *bannare definitivamente*
per un ban —, e se il membro sanzionato ha collegato il suo. Il sito la giudica di nuovo come se l'avesse messa lui
(gerarchia, convalida); altrimenti resta su Discord, e il registro del bot dice perché. Una sanzione venuta da Discord
si toglie su Discord (sbannare, togliere il timeout) o sul sito; una sanzione del sito si toglie solo sul sito.

Il link di invito non dà questi diritti al bot: aggiungili al suo ruolo — *Bannare i membri* e *Mettere i membri in
timeout* per il primo senso, *Visualizzare il registro di controllo* per il secondo. Il bot dice nel suo registro ciò che
gli manca. Discord si rifiuta di sanzionare il proprietario del server e un membro posto sopra il ruolo del bot.

## Collettivi

Spenta per impostazione predefinita: *Discord → Funzionalità → Collettivi*. Ogni collettivo del sito la cui casella
*Discord* è spuntata ([Collettivi](admin.md#collettivi)) riceve sul server:

- un **ruolo**, con il suo nome — è il gruppo del collettivo, collegato a questo ruolo: la funzionalità *Ruoli e
  soprannomi* lo dà ai suoi membri che hanno collegato il loro account, e lo toglie a chi esce;
- un **canale testuale** nella categoria *Collectifs* (il nome si regola; il bot la crea), chiuso a `@everyone`, aperto
  a questo ruolo.

Il bot crea ciò che manca all'avvio, quando cambia la configurazione (un collettivo creato o rinominato) e ogni cinque
minuti: un ruolo o un canale eliminato a mano ritorna, un collettivo rinominato viene rinominato. Un collettivo
eliminato, o la cui casella viene tolta: il bot elimina il suo ruolo, e il suo canale **resta**, visibile solo agli
amministratori del server. Il ruolo del bot ha bisogno di *Gestire i ruoli* e *Gestire i canali* (nel link di invito),
e deve stare sopra i ruoli che crea.

## Sicurezza

- Sulla macchina del bot: l'indirizzo del sito e la sua chiave di accesso, niente di più.
- Il token del bot è cifrato nel database del sito con la chiave propria del sito. Solo una chiave di accesso con il
  diritto `discord:bot` può leggerlo — quella che l'amministrazione crea per il bot, revocabile in qualsiasi momento.
- Il bot non menziona mai nessuno: un messaggio copiato non può notificare `@everyone`.
- Non ha bisogno di «Amministratore», e il link di invito non lo chiede. Se ce l'ha comunque (un ruolo dato a mano), lo
  dice nel suo registro a ogni avvio: toglieglielo — un bot amministratore il cui token trapela apre tutto il server.
  Nomina anche i permessi del link di invito che mancano al suo ruolo.
- Ciò che arriva da Discord — un nome di thread, un nome utente, un commento — viene salvato sul sito come ciò che vi si
  scrive: viene mostrato, non eseguito.

## Scrivere una funzionalità

Il bot si estende con delle **funzionalità**, ciascuna nella sua cartella, in TypeScript. Come fare — il contratto di
una funzionalità, le sue impostazioni, i suoi comandi, i suoi testi tradotti, i suoi test — è nella guida per i
contributori del bot, `CONTRIBUTING.md`, nella radice del suo codice:
[NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
