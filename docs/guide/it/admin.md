# Il pannello di amministrazione

L'amministrazione si trova sotto **/admin**. Segue l'aspetto di NeoFrag Reborn (blu notte + turchese, chiaro o scuro a
scelta) ed è organizzata attorno a una **barra laterale**.

## Punti di riferimento

- **Barra laterale**: i moduli sono ordinati in nove sezioni, più *Sistema* (Impostazioni, Utenti, Permessi, Temi &
  Addons, Monitoraggio…). Le sezioni si aprono a fisarmonica; una sezione vuota è nascosta.

  | Sezione | Moduli |
  |---|---|
  | *Contenuto* | Pagine, Blog, Notizie, Slider, Citazioni, Ricette, Menu |
  | *Community* | Forum, Collettivi, Discussione, Commenti, Libro degli ospiti, Emoji |
  | *Animazione* | Calendario, Sondaggi, Gamification, Annunci |
  | *Gaming* | Eventi, Squadre, Giochi / mappe, Reclutamenti, Palmares, Partner |
  | *Sapere* | Wiki, FAQ, Dizionario, Download, Elenco link, Mappa dei luoghi |
  | *Media* | Media, Gallerie, File, Web radio |
  | *Diffusione* | Newsletter, Modelli email, Feed RSS, Discord, Webhooks, API |
  | *Supporto* | Contatto, Bugtracker, Moderazione |
  | *Monetizzazione* | Negozio, Donazioni, Pagamenti, Gestione pubblicitaria |

  Un'estensione del marketplace che questa disposizione non conosce va in *Altri moduli*. Il menu *Navigazione*
  dell'Editor live segue le stesse sezioni, come sottomenu, con una ricerca.
- **Fissati**: passa sopra un modulo nella barra laterale e clicca sulla puntina per aggiungerlo a *Fissati*
  (scorciatoie in alto, ricordate dal tuo browser).
- **Ricerca rapida**: `Ctrl/Cmd + K` apre la tavolozza dei comandi per raggiungere qualsiasi modulo o azione.
- **Intestazione**: briciole di pane + azioni contestuali (Permessi, Configurazione, Aiuto), «Visualizza il sito» e
  l'interruttore chiaro/scuro.

## Compiti frequenti

| Voglio… | Vado in |
|---|---|
| Pubblicare una notizia / un articolo del blog / una pagina | *Contenuto* → il modulo interessato |
| Ordinare gli articoli del blog in categorie e in serie (un articolo in più parti) | *Contenuto → Blog*, pulsanti «Categorie» e «Serie»; la serie di un articolo e il suo rango si scelgono nel suo modulo |
| Gestire il forum e i commenti; la moderazione | *Community*; *Supporto → Moderazione* |
| Creare una squadra privata — una redazione, un team di moderazione — con la sua sezione del forum e il suo canale Discord | *Community → Collettivi* — vedi [Collettivi](#collettivi) |
| Riscuotere le quote associative, seguire gli iscritti | *Monetizzazione → Pagamenti* — vedi [Quote associative](#quote-associative) |
| Collegare Ko-fi, HelloAsso, TipeeeStream o PayPal alle campagne di donazione | *Monetizzazione → Donazioni*, scheda «Servizi collegati» — vedi [Donazioni collegate](#donazioni-collegate) |
| Creare i prefissi delle discussioni del forum («Domanda», «Tutorial»…), scegliere l'icona di un forum | *Community → Forum*, pulsante «Prefissi»; l'icona nel modulo del forum |
| Gestire membri, gruppi, sessioni | *Sistema → Utenti* |
| Decidere chi può fare cosa | *Sistema → Permessi (matrice)* — griglia ruolo × azione (verde = consentito, grigio = predefinito, rosso = mai) |
| Cambiare il tema / installare un addon | *Sistema → Temi & Addons* |
| Impostazioni del sito (nome, pagina iniziale, iscrizioni, sicurezza, copyright) | *Sistema → Impostazioni* |
| Comporre le pagine con il mouse | *Sistema → Editor live* |
| Collegare il sito a un server Discord: ruoli, soprannomi, forum ↔ canale Forum, Bugtracker, ruoli temporanei, sanzioni | *Diffusione → Discord* — vedi [Il bot Discord](bot-discord.md) |
| Backup, aggiornamenti, salute del sito | *Sistema → Monitoraggio* |
| Leggere gli errori del sito, ritrovare quello segnalato da un visitatore | *Sistema → Monitoraggio → Registro degli errori* |
| Rivedere le azioni sensibili (impostazioni, addon, account, chiavi API) | *Sistema → Utenti → Log di audit* |

> Il **Monitoraggio** riunisce la **salute del sito** (controlli di integrità dei file, spazio su disco, informazioni
> sul server), gli **aggiornamenti**, i **backup** (creare / scaricare / ripristinare) e l'indirizzo dell'**attività
> pianificata** (cron) che pubblica i contenuti programmati.

### Monitoraggio

*Sistema → Monitoraggio* riassume l'essenziale in alto — versione e aggiornamento, PHP, backup, strumenti di diagnostica
accesi — e poi si legge in cinque schede: *Panoramica* (avvisi, salute del sito, spazio, indirizzo del sito), *Backup*,
*Diagnostica*, *Server e sicurezza* (configurazione, password del webmaster, attività pianificata) e *File*
(l'installazione confrontata con la versione pubblicata). La pagina torna sulla scheda in cui eri; un indirizzo come
`admin/monitoring#diagnostic` apre quella giusta.

Ogni aggiornamento tramite il pulsante fa prima un **backup** completo del sito, database compreso. Il sito conserva
sempre i **cinque più recenti**; gli altri si cancellano da soli dopo **trenta giorni**, e non ne restano mai più di
**dieci**. Scarica quello che vuoi tenere più a lungo.

### Il registro degli errori

*Monitoraggio → Registro degli errori* mostra ciò che è fallito sul sito, senza passare dall'FTP: gli errori
raggruppati (lo stesso errore visto cento volte resta una riga), dal più recente al più vecchio, ordinati per gravità —
**fatale**, **errore**, **avviso**, **informazione** —, con la loro ultima occorrenza completa da espandere. Filtri per
gravità e per periodo, e cerchi una parola.

Quando una pagina si blocca, il visitatore legge «Si è verificato un errore» e un **riferimento** di otto caratteri
(`3F9A1C07`); se te lo trasmette, cercalo nel registro: porta alla riga esatta. I percorsi del server, le password, le
chiavi, gli indirizzi email e gli indirizzi IP sono mascherati sullo schermo; il file intero si può **scaricare** (da
mandare a chi ti aiuta) e **svuotare** (il vecchio viene conservato accanto, `logs/php.log.1`). Il Monitoraggio segnala
anche gli errori delle ultime 24 ore, e una cartella `logs/` in cui il sito non riesce più a scrivere.

### Gli strumenti di diagnostica

La scheda *Monitoraggio → Diagnostica* accende tre strumenti per un'ora; ciascuno si spegne da solo, o con un clic.
Niente più modifiche a `config/neofrag.php` via FTP.

- **Modalità debug**: una barra compare in fondo alle pagine (query al database, tempo di calcolo, memoria, dati della
  visita), e una pagina che si blocca mostra il dettaglio dell'errore — **solo per gli amministratori connessi**: i
  visitatori vedono il sito normale. Il registro ti dice che c'è un problema; la modalità debug ti aiuta a capirlo
  riproducendolo.
- **Traccia delle pagine**: ogni pagina servita, con le sue query al database e la loro durata. *Leggi la traccia*
  mostra le ultime pagine, dalla più recente, con una ricerca per indirizzo; i valori passati alle query sono mascherati
  sullo schermo, il file completo si può scaricare. Pesante: per il tempo di una diagnosi.
- **Controllo delle traduzioni**: i testi senza traduzione nella lingua della visita vengono registrati ed elencati in
  *Traduzioni mancanti*, con l'estensione che li usa. Nel frattempo, gli amministratori vedono la bandiera della lingua
  davanti a ogni testo tradotto: un testo senza bandiera non è passato dalla traduzione.

Uno strumento acceso in `config/neofrag.php` si spegne solo lì; la scheda lo dice.

### L'indirizzo del sito

I link delle email (password dimenticata, conferma dell'iscrizione), i ritorni dagli accessi esterni e le condivisioni
si costruiscono sull'indirizzo registrato all'installazione. Dopo un cambio di dominio, la scheda *Indirizzo del sito*
(Monitoraggio, scheda *Panoramica*) lo segnala: apri l'amministrazione dal nuovo indirizzo, poi il pulsante *Usa*,
seguito da quell'indirizzo, lo registra (password del webmaster richiesta se ce n'è una).

## Permessi

NeoFrag Reborn funziona a **ruoli**. La **matrice dei permessi** (per modulo) definisce, per ogni ruolo, gli accessi e
le azioni. Assegni i ruoli ai membri e ai gruppi da *Sistema*.

**Esempio — creare un ruolo «Moderatore»**:

1. In *Sistema → Permessi*, **crea un ruolo** «Moderatore».
2. Nella sua colonna della **matrice**, spunta le azioni volute (moderare il forum, gestire i commenti…) →
   **verde = consentito**.
3. In *Sistema → Utenti*, modifica un membro e **assegnagli questo ruolo**: ottiene subito questi accessi.

### Collettivi

Un **collettivo** riunisce dei membri attorno a un progetto, al riparo dagli altri: una redazione, un team di
moderazione, un gruppo di creatori. Non è una squadra di gioco (*Gaming → Squadre*, che si mostra al pubblico): tutto
ciò che un collettivo riceve è riservato a lui.

*Community → Collettivi → Nuovo collettivo*: il suo nome, la sua descrizione e il suo **capo** (un nome utente). Il
sito crea in un colpo solo:

- una **sezione privata del forum** — una categoria e il suo forum «Conversazioni» —, che solo i suoi membri possono
  leggere e in cui solo loro possono scrivere;
- due **ruoli** del sito, *Collettivo «nome»* e *… — moderazione*, che il modulo dà e toglie da solo: non assegnarli a
  mano;
- se la casella *Discord* è spuntata e il bot è in funzione (funzionalità *Collettivi*), un **ruolo e un canale
  privato** sul server — vedi [Il bot Discord](bot-discord.md#collettivi).

Il capo gestisce poi i membri dalla pagina del collettivo (*I miei collettivi*, nell'area membri), senza essere
amministratore del sito. I ranghi:

| Rango | Può |
|---|---|
| Capo | aggiungere membri, nominare amministratori e moderatori, togliere chiunque sia sotto di lui |
| Amministratore | aggiungere membri, nominare moderatori, togliere moderatori e membri |
| Moderatore | moderare la sezione del forum (modificare, eliminare, fissare, chiudere) |
| Membro | leggere e scrivere nella sezione |

Il capo si nomina solo nell'amministrazione: un nuovo capo sostituisce lì il precedente, che resta amministratore del
collettivo. Eliminare un collettivo toglie il suo spazio ai suoi membri; la sua sezione del forum e il suo canale
Discord **restano**, visibili solo agli amministratori — nulla di ciò che vi è scritto va perso; eliminali a mano se lo
desideri.

### Quote associative

*Monetizzazione → Pagamenti*. Una quota fa di un membro un **iscritto** per un periodo: riceve il ruolo **«Iscritto»**
— aprigli quello che vuoi in *Sistema → Permessi* (una sezione del forum, delle pagine) — ed entra nel gruppo
**«Iscritti»** (il suo badge, e un ruolo Discord se lo colleghi). Due modi di riscuotere:

- **online**, con Stripe: un pacchetto di tipo *Iscrizione (giorni)* («Quota annuale», 365 giorni) che il membro paga
  nella pagina *Pagamenti*;
- **altrimenti** (contanti, assegno, bonifico): la scheda *Iscritti* → *Registra una quota* (nome utente, durata).

Una nuova quota prolunga l'iscrizione a partire dalla sua data di fine. Sette giorni prima, l'attività pianificata
avvisa il membro (una notifica); alla data di fine, gli toglie il ruolo. *Termina l'iscrizione* la ferma subito (un
errore di inserimento, un rimborso). Le quote non hanno bisogno della Gamification — i punti e il VIP sì.

### Donazioni collegate

*Monetizzazione → Donazioni*, scheda **«Servizi collegati»**: una donazione fatta su uno di questi servizi si registra
da sola nella campagna scelta, **una sola volta**, anche se il servizio avvisa due volte. Le chiavi sono conservate
cifrate e non vengono più mostrate.

| Servizio | Cosa serve | Come verifica il sito |
|---|---|---|
| **Ko-fi** | su Ko-fi, *More → API → Webhooks*: l'indirizzo mostrato nella scheda, e il token di verifica | il token, che solo il sito conosce |
| **HelloAsso** | nello spazio dell'associazione, *Il mio account → Integrazioni e API*: l'ID e il segreto client, e l'indirizzo di notifica mostrato | rilegge l'ordine tramite l'API di HelloAsso: la notifica da sola non fa fede |
| **TipeeeStream** | la chiave API dell'account | niente da verificare: il sito legge le donazioni da sé, a ogni attività pianificata, a partire dal giorno in cui la chiave è registrata |
| **PayPal** | la casella *Registrare le donazioni automaticamente* | PayPal conferma ogni notifica, e il denaro deve essere arrivato all'indirizzo PayPal della campagna |

Un'**adesione** pagata su HelloAsso rende iscritto il membro del sito con lo stesso indirizzo email, per la durata che
imposti (modulo Pagamenti). I donatori di HelloAsso e PayPal non compaiono nella pagina della campagna — non hanno dato
il consenso —; quelli di Ko-fi e TipeeeStream che l'hanno scelto sì. Il totale conta tutte le donazioni. Per provare
senza denaro vero: le caselle *sandbox* di HelloAsso e PayPal.

## Impostazioni essenziali

In **Impostazioni**: titolo e descrizione del sito, favicon, email di contatto, pagina iniziale, fuso orario,
indicizzazione, iscrizioni, sicurezza anti-bot (captcha), manutenzione, copyright, social network. Queste impostazioni
alimentano i temi e i widget.

### Iscrizioni

*Impostazioni → Registrazione* apre o chiude le iscrizioni, può far **confermare il loro indirizzo email** ai nuovi
membri, e definisce due testi, **lingua per lingua** (una scheda per lingua del sito).

**La conferma via email** (spenta per impostazione predefinita): all'iscrizione, il membro riceve un link, valido due
giorni, e non può accedere prima di averlo aperto; un tentativo di accesso ne manda uno nuovo (tre all'ora al
massimo). Un'iscrizione tramite Discord, GitHub o Google non ne ha bisogno. I due testi:

- il **regolamento**, che il visitatore accetta iscrivendosi — dal modulo come da Discord, GitHub o Google, che lo
  mostrano prima di creare l'account;
- il **messaggio di benvenuto**, inviato nei messaggi privati al nuovo membro — anche quando si iscrive con Discord,
  GitHub o Google —, nella lingua della pagina in cui si è iscritto. `[pseudo]` diventa il suo nome utente. Arriva come
  testo: titoli, elenchi e link restano, la formattazione no; un indirizzo scritto per intero diventa un link.

Una lingua che non ha ancora il suo testo mostra il testo comune: il primo testo registrato serve per tutte le lingue
finché non vengono tradotte.

### Indicizzazione

Ciò che leggono i motori di ricerca si fa **da solo**:

- una **mappa del sito** per lingua, riunite nella radice (`/sitemap.xml`) e annunciate da `robots.txt` — ogni modulo
  aggiunge le sue pagine pubbliche (il blog, il forum e le sue discussioni, il wiki, la galleria…), solo quelle che un
  visitatore può leggere;
- in ogni pagina, il suo **indirizzo di riferimento** (canonico), i **link verso le sue altre lingue**, e ciò che
  mostrano le anteprime di condivisione (titolo, descrizione, immagine);
- nella pagina iniziale, i **dati strutturati** che descrivono il sito, il suo logo e i suoi social network
  (*Impostazioni → Social network*);
- la ricerca, i profili e l'elenco dei membri **non** vengono indicizzati.

Ciò che compili, in **Impostazioni → Indicizzazione**:

- per ogni lingua, uno **slogan** (il titolo della pagina iniziale diventa «Nome del sito — slogan», 60 caratteri al
  massimo) e una **descrizione** (ciò che i motori mostrano sotto il titolo, 160 caratteri al massimo; vuota, si usa
  quella delle Preferenze generali);
- un'**immagine di condivisione** di 1200 × 630 pixel, mostrata quando un link al sito viene incollato su Discord, X o
  Facebook — altrimenti, il logo;
- il codice di verifica di **Google Search Console** e di **Bing Webmaster Tools**: crea la proprietà del sito nello
  strumento, scegli la verifica tramite **tag HTML**, incolla il tag (o solo il codice), poi invia lì la mappa del
  sito, `https://tuo-sito/sitemap.xml`;
- **Avvisare i motori (IndexNow)**: spuntata, il sito segnala ogni pagina che compare, cambia o scompare a Bing,
  Yandex, Seznam, Naver, Yep e Amazon, in pochi minuti, invece di aspettare il loro prossimo passaggio. Google non vi
  partecipa: per Google, è la mappa del sito. L'**attività pianificata** del sito deve essere in funzione (il
  *Monitoraggio* dà la riga da installare). Il primo passaggio registra solo le pagine esistenti; dopo, vengono inviati
  solo i cambiamenti.

Altre tre schermate, dalla stessa sezione:

- **Resoconto SEO** — ciò che vede un motore di ricerca, misurato sul sito: il numero di pagine della mappa e il loro
  modulo, i testi della pagina iniziale in ogni lingua, l'immagine di condivisione, Google e Bing, IndexNow,
  `robots.txt`, la manutenzione. Ogni punto rimanda a ciò che lo corregge.
- **Reindirizzamenti** — un vecchio indirizzo che porta al nuovo, invece di «Pagina non trovata»: un posizionamento
  guadagnato non va perso. Una pagina o una pagina del wiki **rinominata** lascia da sola il proprio reindirizzamento;
  aggiungi a mano quelli di un vecchio sito (`page.php`, `/fr/vecchia-pagina`…). La colonna *Visite* dice se il vecchio
  indirizzo serve ancora.
- **L'indicizzazione di un contenuto** — nella scheda di modifica di una notizia, di un articolo del blog, di una pagina
  o di una pagina del wiki, il pulsante **Indicizzazione**: un titolo per i motori (60 caratteri al massimo, il nome del
  sito segue) e una descrizione (160 al massimo), in ogni lingua. Vuoti, sono il titolo e l'inizio del testo.

### Il captcha

Il modulo di contatto e l'iscrizione chiedono ai visitatori una verifica anti-robot. In **Impostazioni → Captcha**,
scegli il fornitore:

- **ALTCHA** (predefinito, consigliato): niente da configurare. È ospitato dal tuo sito, senza account e senza cookie;
  il visitatore vede una casella che si spunta da sola mentre compila il modulo.
- **Cloudflare Turnstile**, **hCaptcha** o **Google reCAPTCHA v2**: crea un sito nella loro console (link nella
  pagina), poi incolla la **chiave del sito** e la **chiave segreta**. La chiave segreta è cifrata e non viene più
  mostrata. Senza le sue due chiavi, il fornitore viene sostituito da ALTCHA.
- **Nessuno**: sconsigliato, i robot scrivono allora liberamente.

ALTCHA rende costoso per un robot l'invio in massa; contro un attaccante determinato, Turnstile o hCaptcha giudicano di
più.

### Il fuso orario

Ognuno vede gli orari del sito nel proprio fuso orario:

- un **membro** può scegliere il suo nel profilo (**Profilo → Fuso orario**);
- altrimenti, il sito usa quello del suo **browser**, dalla sua seconda pagina;
- altrimenti — prima visita, robot, attività automatiche —, quello del **sito**, impostato in **Impostazioni →
  Preferenze generali → Fuso orario**.

Un orario inserito in un modulo (l'inizio di un evento, la riapertura dopo una manutenzione) si intende nel fuso orario
di chi lo inserisce. Una data da sola — un compleanno, un giorno intero nel calendario — non cambia mai giorno. La
programmazione di una web radio si scrive nell'ora del sito.

### Le notifiche push

Un membro può ricevere le sue notifiche nel browser, **anche a sito chiuso** — un messaggio sul forum, una reazione, un
promemoria di un evento. Per offrirlo, spunta **Impostazioni → Preferenze generali → Notifiche push**. Il sito deve
essere servito in **https**, e il server capace di cifrare (OpenSSL con la curva P-256, e cURL: quasi tutti gli hosting
li hanno; altrimenti la casella resta non spuntata e il sito dice perché).

Nulla arriva a nessuno finché non lo chiede: ogni membro lo attiva **dispositivo per dispositivo**, in **Il mio account
→ Preferenze di notifica → Questo dispositivo**, e il suo browser gli chiede il consenso. Lì sceglie anche, tipo per
tipo, cosa riceve in questo modo, e può inviarsi una prova.

Cosa devi sapere, e dire nella tua **informativa sulla privacy**: il browser passa dal suo servizio push — Google per
Chrome ed Edge, Mozilla per Firefox, Apple per Safari. Il sito conserva lì l'indirizzo di abbonamento di ogni
dispositivo finché il membro non disattiva il push (o non elimina il suo account). Il messaggio è cifrato per quel solo
browser: il servizio lo trasporta senza poterlo leggere.
