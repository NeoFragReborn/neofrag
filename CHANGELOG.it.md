# Changelog

Questa è la traduzione italiana di [CHANGELOG.md](CHANGELOG.md); la versione francese è quella di riferimento.

Tutte le modifiche rilevanti di **NeoFrag Reborn** sono registrate qui.

Formato ispirato a [Keep a Changelog](https://keepachangelog.com/it-IT/1.1.0/);
i numeri di versione si ispirano al [versionamento semantico](https://semver.org/lang/it/); una versione che cambia solo
l'ultimo numero (1.2.x) può comunque portare novità e rimozioni.

NeoFrag Reborn è la continuazione comunitaria di **NeoFrag** (base Alpha 0.2.4), creato originariamente da
Michaël BILCOT e Jérémy VALENTIN — un progetto open source con licenza LGPLv3.

---

## [1.2.54] — 2026-10-17

### Sicurezza

- **Il ripristino della password rivelava, alla seconda richiesta, che un indirizzo ha un account.** La risposta è
  la stessa che l'indirizzo sia registrato o no; ma una seconda richiesta subito dopo, solo per un indirizzo
  registrato, rispondeva «attendi» invece della risposta comune. Ora risponde sempre allo stesso modo; i limiti per
  indirizzo e per connessione, validi per tutti, frenano le richieste ripetute.
- **Un captcha risolto dispensava anche il messaggio successivo.** La dispensa, pensata per reinviare un modulo
  rifiutato per un altro motivo, sopravviveva a un invio riuscito: un robot che risolveva una sola sfida inviava due
  messaggi. Ora cade appena il modulo è accettato.

### Corretto

- **La schermata di un gruppo, aperta in una lingua in cui non ha ancora un nome, proponeva quello di un'altra
  lingua** — il francese, in mancanza d'altro: salvarla senza modificarla lo scriveva nella nuova lingua. Ora si apre
  vuota, come gli altri contenuti che si traducono. E il nome di un gruppo creato dall'amministratore non passa più
  per le traduzioni dell'interfaccia: un gruppo «Modérateur» scriveva un avviso nel registro a ogni pagina mostrata
  in una lingua diversa dal francese.
- **La schermata di una mappa o di una modalità di gioco, aperta in una lingua in cui non ha ancora un nome,
  proponeva il nome francese** — salvarla senza modificarla lo scriveva come nome della nuova lingua. Ora si apre
  vuota, come quella di un gruppo.
- **Rinominare una stanza pubblica in una lingua diversa dal francese cambiava anche il suo nome di riserva**, quello
  che mostra quando nessuna traduzione è adatta. Quel nome ora segue il francese.
- **La navigazione faceva passare un link di menu per i testi dell'interfaccia** quando non aveva una traduzione nella
  lingua della pagina: «À propos» diventava «About» in inglese e «Über» in tedesco, invece del titolo scritto
  dall'amministratore. Ora mostra il titolo francese, come gli altri contenuti che si traducono.
- **Il modulo di contatto, nella sua pagina, mostrava dopo l'invio una risposta tecnica**
  (`{"modal":"dispose",…}`) invece di tornare alla pagina con «Messaggio inviato». Un secondo messaggio troppo
  ravvicinato mostrava allo stesso modo la parola «anti_flood»; ora chiede di attendere qualche minuto, nelle sei
  lingue.
- **Il rifiuto del captcha si scriveva due volte** sotto il widget.
- **In inglese, il pulsante di accesso dell'intestazione diceva «Login»** (il sostantivo) su Nebula, Chronique e il
  piccolo widget dell'area membri, e «Log in» (il verbo) sugli altri temi. Ora dice «Log in» ovunque, come le altre
  lingue usano un verbo.

## [1.2.53] — 2026-10-16

### Sicurezza

- **I file privati di un sito si potevano leggere aggiungendo una lingua davanti al loro indirizzo.** Ogni indirizzo
  con estensione passava dal sito, che leggeva il file con quel nome ovunque nella sua cartella: `/it/logs/php.log`
  restituiva il registro del sito, e lo stesso valeva per i suoi backup (`backups/`, database compreso), la sua cache,
  gli allegati privati del forum e dei messaggi, o una cartella nascosta come `.git/`. Le regole del server web
  rifiutavano queste cartelle, ma non lo stesso percorso dietro una lingua. Il sito ora serve soltanto i suoi fogli di
  stile, script, immagini, caratteri e i file pubblici caricati dai membri. **Aggiornate senza aspettare.**
- **Le configurazioni fornite per Apache, nginx e Caddy rifiutano i percorsi nascosti** (`.git/`, `.env`…): un sito
  distribuito con `git clone` lasciava leggibile l'intero repository. `/.well-known/` resta raggiungibile, per i
  certificati.

### Corretto

- **Un aggiornamento cancella finalmente i file che la nuova versione rimuove.** Da diverse versioni, l'elenco di
  questi file usciva vuoto dalla fabbrica dei pacchetti: restavano sul sito, e il Monitoring segnalava per esempio «Il
  file `fonts/fontawesome/fa-solid-900.ttf` non dovrebbe trovarsi qui». Questo aggiornamento rimuove i 99 file che il
  prodotto ha distribuito e poi abbandonato (vecchi caratteri, vecchie lingue dell'editor, viste sostituite…). Un
  modulo, un widget o un tema rimosso del tutto dal prodotto resta al suo posto: può essere ancora in uso.
- **Il menu di navigazione segnalava a volte un errore nella console del browser** («ResizeObserver loop completed
  with undelivered notifications»), a seconda del momento in cui arrivavano i caratteri: la sua misura, che ripiega il
  menu dietro il suo pulsante quando non sta più su una riga, ora aspetta il fotogramma successivo.

## [1.2.52] — 2026-10-15

### Corretto

- **Le pagine del forum dichiaravano la loro canonica in un'altra lingua.** Un forum conserva un nome predefinito
  per le lingue in cui non è tradotto; la 1.2.51 guardava solo le sue traduzioni, e una pagina del forum nella
  lingua del nome predefinito mandava i motori in un'altra lingua, a un indirizzo che reindirizzava. Ogni forum è di
  nuovo annunciato in tutte le lingue, ciascuna all'indirizzo del proprio nome.

## [1.2.51] — 2026-10-15

### Aggiunto

- **Disinstallare un modulo o un widget, e azzerare un modulo, dalla sua scheda.** Finora un modulo o un widget si
  poteva solo disattivare. **Disinstalla** rimuove le sue tabelle, i suoi dati e i suoi file; un modulo di cui un
  altro addon ha bisogno viene rifiutato, e la finestra dice quali. **Azzera** cancella i dati di un modulo e lo fa
  ripartire come il giorno della sua installazione; le sue impostazioni, i suoi permessi e i link che vi portano
  restano. Entrambi fanno prima un backup (cartella `backups/addons` del server), e la conferma dice quante righe se ne
  vanno. Gli addon del nucleo non si rimuovono.

### Corretto

- **Le anteprime del marketplace restavano le vecchie nel browser.** Una miniatura mantiene il suo indirizzo da una
  versione all'altra: chi l'aveva già vista teneva la vecchia in cache — le miniature disegnate della 1.2.50
  sembravano ancora schermate. L'indirizzo porta ora l'impronta dell'archivio.
- **Un album delle Gallerie non si poteva tradurre.** Nell'amministrazione di un'altra lingua, un album non ancora
  tradotto mancava dall'elenco, e la sua schermata di modifica rispondeva «pagina non trovata». Ora vi compare, e la
  sua schermata si apre con i campi vuoti, pronti per la traduzione; il testo delle altre lingue non cambia.
- **Nebula: «Disconnetti» introvabile nelle notizie, nel forum e nei profili.** Queste pagine non hanno colonna laterale,
  quindi nemmeno il blocco dell'area membri, e il nome utente in alto portava solo a «Il mio spazio». Ora apre il
  menu dell'account, lo stesso degli altri temi: profilo, account, messaggi, amministrazione, disconnetti.
- **Il menu di un tema restava nella lingua dell'amministratore che l'aveva attivato.** Attivato o reinstallato da
  un'amministrazione in inglese, un tema scriveva «Home», «News»… e tutti i visitatori, francesi compresi, lo
  leggevano così. I suoi link ora si scrivono in francese, la lingua di partenza, e la navigazione li mostra nella
  lingua di ogni pagina. Un tema già installato conserva il suo menu: «Reinstalla i valori predefiniti» lo riscrive.
- **I motori di ricerca conoscono le traduzioni dei contenuti.** Gli eventi, il palmarès, le offerte di
  reclutamento, i sondaggi, le campagne di donazione, le pagine del wiki, i download, le FAQ, i link e il negozio si
  dichiaravano ancora senza lingua: un solo indirizzo annunciato, in francese. Ora annunciano le lingue in cui sono
  scritti, e una lingua in cui non lo sono rimanda la sua canonica a una in cui lo sono. E i link tra le lingue
  delle notizie, degli articoli del Blog, dei forum e delle categorie di annunci portavano, nelle altre lingue, a un
  reindirizzamento verso il titolo tradotto — i motori scartano un link del genere: ognuno porta ora all'indirizzo
  del proprio titolo.

## [1.2.50] — 2026-10-15

### Aggiunto

- **Un tema mostra la scelta della lingua in una riga**: `nf_selecteur_langue()`, accanto a
  `nf_selecteur_theme()` e `nf_liens_legaux()`. I temi inclusi ne copiavano ciascuno il modulo.
- **La documentazione e le note di rilascio, in tutte e sei le lingue.** Le guide della wiki — installazione, primi
  passi, amministrazione, marketplace, sito multilingue, bot Discord, framework, creare un tema, un modulo o un widget —
  esistevano solo in francese e in inglese: ora sono scritte anche in tedesco, spagnolo, italiano e portoghese, con le
  etichette esatte che lo schermo mostra in ogni lingua, e la wiki le serve nella lingua del visitatore. Le note di
  rilascio seguono: la pagina «Note di rilascio» della wiki e i post del Blog si leggono in tutte e sei le lingue. Il
  francese resta il riferimento; un test rifiuta una traduzione che salti una versione o la dati diversamente.
- **Le miniature dei moduli e dei widget, ridisegnate.** In *Temi & addon* e nel marketplace, l'anteprima di
  un'estensione era una sua foto al lavoro — illeggibile quando non aveva dati da mostrare: una cornice, un titolo,
  qualche riga. Ogni modulo e ogni widget riceve ora una scheda disegnata nel linguaggio visivo del progetto: la sua
  icona, il suo nome e ciò che fa, leggibili a qualsiasi dimensione. I temi conservano la foto della loro home page: un
  tema si mostra da sé.
- **Le librerie incluse aggiornate, e una salvaguardia perché lo restino.** L'editor di testo passa a TinyMCE 8 (la
  linea 7 perdeva il supporto il 14 novembre, e la 8.9.3 correggeva una falla del suo plugin «media») e le icone a Font
  Awesome 7 — le 363 icone usate dal sito sono state confrontate con la nuova collezione prima del passaggio, e non ne
  manca nessuna. I file di font doppi (.ttf) spariscono: la versione 7 serve solo il formato moderno, 715 KB in meno
  nel pacchetto. Soprattutto, un nuovo controllo vigila: non lancia l'allarme a ogni rilascio dei progetti a monte, ma
  rifiuta quando le versioni distribuite non sono state confrontate con il registro da più di tre mesi. Una dipendenza
  non diventa pericolosa di colpo — va alla deriva, e ora la deriva si vede.
- **Le notifiche push.** Un membro può ricevere le sue notifiche nel browser, anche a sito chiuso: un messaggio del
  forum, una reazione, un promemoria di un evento. L'amministratore lo propone (Impostazioni → Preferenze generali →
  Notifiche push); ogni membro lo attiva poi dispositivo per dispositivo, nelle sue preferenze di notifica, sceglie tipo
  per tipo ciò che riceve così, e può inviarsi una prova. Senza librerie aggiunte: il messaggio è cifrato per quel solo
  browser (RFC 8291) e firmato a nome del sito (VAPID, RFC 8292) — il servizio push del browser lo trasporta senza poterlo
  leggere. Il server scrive solo a servizi push noti (Google, Mozilla, Apple, Microsoft), mai a un indirizzo che un
  membro potrebbe essersi inventato.
- **Nebula e il tema vetrina hanno la loro pagina dei colori, e gli otto temi un'anteprima dal vivo.** Accento, sfondo,
  testo: le palette di Nebula e della vetrina erano scritte nei loro fogli di stile; ora si regolano come quelle degli
  altri sei temi, con lo stesso rifiuto di un colore illeggibile. E sotto i selettori di ogni tema, una vera pagina del
  sito — la home, le notizie o il forum — prende i colori man mano che li si sceglie, senza salvare nulla, con i
  contrasti misurati dal vivo: si vede il risultato prima di cliccare su «Salva».
- **I link dei menu, le stanze pubbliche, il titolo dato a un widget e le pagine della wiki si traducono.** Con loro,
  nessun testo scritto dal gestore del sito resta più senza lingua. Una pagina della wiki tiene una cronologia PER
  LINGUA: tradurre in italiano non archivia nulla della versione francese, e la cronologia italiana mostra solo le sue
  revisioni. Una conversazione tra membri mantiene un solo nome per tutti i partecipanti — si traducono solo le stanze
  pubbliche. La pagina «Note di rilascio» della wiki esiste ora anche in inglese.
- **Il negozio, le donazioni, la pubblicità, i pacchetti di pagamento e il reclutamento si traducono.** Il nome e la
  descrizione di un articolo del negozio, il titolo e la descrizione di una campagna di donazioni, il titolo di un
  annuncio pubblicitario, l'etichetta di un pacchetto e un'intera offerta di reclutamento — titolo, introduzione,
  descrizione, requisiti e le domande del suo modulo di candidatura — si scrivono ora lingua per lingua. Le domande non
  avevano una schermata per modificarle (si potevano solo aggiungere o eliminare): ora le apre una matita, ed è lì che
  si traducono. Resta comune ciò che non è testo: il prezzo, la scorta, l'indirizzo di una campagna, il codice di un
  circuito pubblicitario, il ruolo dato nella squadra — e le risposte dei candidati, che conservano la domanda così
  come l'hanno letta.
- **Le modalità di gioco, le mappe, le trasmissioni della web radio e i campi del profilo si traducono.** «Compétitif
  5v5» diventa «Competitive 5v5» per un visitatore anglofono, «Le réveil du serveur» diventa «The server wake-up», e
  l'etichetta di un campo del profilo si scrive in ogni lingua. Il nome di una mappa resta un nome proprio, ma anche lui
  si scrive per lingua, per chi vuole tradurre «Faille de l'invocateur». È stata fatta una scelta, e la schermata lo
  dice: l'ELENCO DELLE OPZIONI di un campo del profilo resta comune — un'opzione è salvata così com'è nella risposta del
  membro, e tradurla renderebbe introvabili le risposte già date.
- **I tipi di evento, i ruoli di squadra e le rubriche degli annunci si traducono.** «Torneo», «Capitano», «Hardware»:
  tre nomi brevi che scrive il gestore del sito, e che un sito in sei lingue mostrava in una sola. Ora si scrivono
  lingua per lingua, e il visitatore li vede nella sua ovunque compaiano — l'elenco degli eventi e le loro schede, il
  calendario, la formazione di una squadra, l'elenco degli annunci e le schermate dell'amministrazione.
- **Un sondaggio si modifica senza perdere i suoi voti — e si traduce.** Finora, salvare un sondaggio cancellava tutte
  le sue risposte per ricrearle, e tutti i VOTI con loro: correggere una virgola nella domanda costava l'intero
  sondaggio (la schermata lo diceva, il che non lo rendeva meno costoso). Ora una risposta conserva i suoi voti finché
  resta nell'elenco — corretta, spostata o tradotta; solo una risposta RIMOSSA perde i suoi, e la schermata lo dice
  contandoli. È ciò che permette finalmente di tradurre un sondaggio: la domanda, la sua descrizione e l'etichetta di
  ogni risposta si scrivono lingua per lingua, e scrivere la versione italiana non tocca più quella francese.
- **I download, i link utili e le FAQ si traducono.** Il titolo e la descrizione di un file o di un link, la domanda e
  la sua risposta, e i titoli delle loro categorie, non avevano lingua: un sito in sei lingue li mostrava in una sola —
  lo si vedeva sulla demo in inglese, che annunciava «Pack de fonds d'écran» e «Chaîne Twitch». Ora si scrivono lingua
  per lingua, come le notizie o gli eventi: la schermata di modifica apre la lingua in cui si lavora e dice quale, e il
  visitatore li vede nella sua lingua — nella libreria, nell'elenco dei link, nei widget e nei blocchi. Ciò che non ha
  lingua (indirizzo, dimensione, tipo, versione, contatori) resta comune, e ciò che è scritto in una sola lingua resta
  visibile nelle altre cinque.
- **Un colore del tema che renderebbe il sito illeggibile viene rifiutato.** I sei temi che permettono di scegliere il
  colore d'accento, del testo e dello sfondo accettavano qualsiasi cosa: un accento quasi identico allo sfondo, e link
  e pulsanti sparivano; un testo troppo pallido, e la pagina non si leggeva più. Nulla lo segnalava, e bisognava
  ritrovare da sé il colore di prima. Ogni colore è ora misurato rispetto agli altri due — quelli salvati, dato che i
  due moduli si salvano separatamente — e rifiutato, con il suo valore, quando non risalta abbastanza: 4,5 a 1 per il
  testo, 3 a 1 per un accento, come richiede la norma sull'accessibilità. I colori originali dei nove temi passano
  tutti.
- **Le librerie incluse aggiornate.** L'editor di testo (TinyMCE 7.6.1 → 7.9.3, che include un sanificatore HTML più
  recente), i grafici delle statistiche (Chart.js 4.4.7 → 4.5.1) e gli elenchi a discesa (Tom Select 2.6.1 → 2.6.2). Le
  altre tre — Bootstrap, flatpickr, Sortable — erano già all'ultima versione pubblicata. Le traduzioni dell'editor,
  corrette a mano, sono conservate.
- **Trentuno titoli di schermata parlavano francese in tutte e sei le lingue.** «Aggiornamenti», «Nuova mappa», «Il
  mio parere sulla candidatura», «Gestisci il mio account», «Partner», «Offerte di reclutamento»…: i titoli di
  pannelli, finestre e pulsanti non passavano per le traduzioni, e apparivano in francese qualunque fosse la lingua del
  sito. La loro traduzione esisteva già, in tutte e sei le lingue: mancava solo la chiamata. Il controllo che vigila sui
  testi scritti nel codice li lasciava passare perché supponeva che quei titoli fossero tradotti dal prodotto — cosa
  che fanno davvero solo le etichette dei MODULI; ora distingue i due casi.
- **Tre schermate ritrovano il design del loro tema.** Il foglio di stile di un widget è servito dopo quello del tema:
  dove entrambi dicevano la stessa cosa con lo stesso peso, vinceva il widget, e il tema disegnava nel vuoto — una
  schermata presentabile, un design voluto mai servito, e niente che lo dicesse. Il pannello destro di *Extend* (chi è
  online, la chat) e le sue grandi cifre riprendono il suo carattere e i suoi colori; le schede del profilo di *Forge*
  tornano quadrate, sottolineate e in maiuscolo, come nei temi fratelli; e l'indicatore di salute del *Monitoraggio*
  sta finalmente dentro il suo cerchio, invece di uscirne. Un controllo rifiuta ora qualsiasi dichiarazione di tema che
  non potrebbe mai applicarsi.
- **Gli eventi si traducono.** Il titolo di un evento, la sua descrizione, la sua descrizione privata e il suo luogo
  non avevano lingua: un sito in sei lingue li mostrava in una sola. Ora si scrivono lingua per lingua — la schermata
  di modifica apre la lingua in cui si lavora, e dice quale. Il visitatore vede un evento nella sua lingua ovunque
  compaia: l'elenco, la scheda, il calendario, i promemoria via e-mail, la sitemap e il widget. Ciò che non ha lingua
  (le date, il tipo, l'immagine, l'autore, i partecipanti) resta comune; un evento scritto in una sola lingua resta
  visibile nelle altre cinque.
- **Il palmarès si traduce.** Il nome di un titolo vinto, il suo luogo e il suo racconto non avevano lingua: un sito in
  sei lingue li mostrava in una sola. Ora si scrivono lingua per lingua, come il resto — la schermata di modifica apre
  la lingua in cui si lavora, e dice quale. Ciò che non ha lingua (la data, il piazzamento, il numero di partecipanti,
  la piattaforma, la squadra, il gioco) resta comune.
- **Lo slideshow si traduce.** Il titolo e la didascalia di una slide non avevano lingua: un sito in sei lingue li
  mostrava in una sola. Ora si scrivono lingua per lingua, come una pagina o una categoria — l'amministrazione scrive
  nella lingua in cui lavora, e lo dice sopra il campo. Il visitatore vede la slide nella sua lingua; una slide
  tradotta in una sola lingua resta visibile nelle altre cinque (il testo della lingua richiesta, altrimenti il
  francese, altrimenti il primo scritto), e una slide senza alcun testo conserva la sua immagine. Nulla si perde con
  l'aggiornamento: il testo di oggi è copiato in tutte le lingue installate, dato che è ciò che ogni visitatore vede
  oggi.
- **Le sanzioni del sito seguono il membro su Discord.** Con il bot 0.3.0 e la sua funzione *Sanzioni collegate*
  (spenta all'inizio), i silenziamenti e i ban di tutto il sito si applicano all'account Discord collegato del membro —
  un timeout per un silenziamento, un ban per un ban — e vengono revocati su Discord quando li si revoca sul sito. Un
  avvertimento, una restrizione o uno shadow ban non passano mai. Nel senso opposto, e solo se lo si accende: un
  moderatore il cui account Discord è collegato a un account del sito che ne ha il diritto può bannare o mettere in
  timeout su Discord, e il sito riceve la sanzione e la giudica di nuovo come se fosse stata pronunciata da lui.
  L'elenco delle sanzioni dice ora da dove viene ciascuna.
- **I collettivi.** Un nuovo modulo riunisce dei membri attorno a un progetto, al riparo dagli altri: una redazione,
  una squadra di moderazione, un gruppo di creatori. Creato nell'amministrazione (*Community → Collettivi*), un
  collettivo riceve una sezione privata del forum, che leggono solo i suoi membri, e — con il bot 0.3.0 e la sua
  funzione *Collettivi* — un ruolo e un canale privato su Discord. Il suo responsabile aggiunge i membri e dà loro un
  grado (amministratore, moderatore, membro) dalla pagina del collettivo, senza essere amministratore del sito; i
  moderatori moderano la sua sezione. Se un collettivo viene eliminato, la sua sezione e il suo canale restano,
  riservati agli amministratori: nulla di ciò che vi è stato scritto va perso.
- **Le quote associative.** Un'associazione incassa le sue quote sul sito: un membro paga online (Stripe) — oppure
  l'amministrazione registra una quota pagata in contanti o con assegno — e diventa **iscritto** per il periodo scelto.
  Riceve il ruolo «Iscritto», che si può aprire a ciò che si vuole (una sezione riservata del forum, delle pagine), e il
  badge del gruppo «Iscritti». Sette giorni prima della scadenza viene avvisato; alla scadenza, il ruolo gli viene
  tolto. Le quote non hanno bisogno del modulo Gamification, che finora i pagamenti richiedevano.
- **Le donazioni fatte altrove si registrano da sole.** Ko-fi, HelloAsso, TipeeeStream e il pulsante PayPal delle
  campagne: ogni donazione arriva nella campagna scelta, una sola volta, e la barra di avanzamento si muove senza
  inserimento manuale. Nulla si registra senza prova — il token di Ko-fi, l'ordine riletto su HelloAsso, la conferma di
  PayPal. Un'adesione pagata su HelloAsso rende iscritto il membro del sito che ha lo stesso indirizzo e-mail. I nomi
  dei donatori di HelloAsso e PayPal non vengono mostrati pubblicamente.

### Sicurezza

- **Il bot Discord segnala di essere amministratore del server.** Non ne ha bisogno — il link di invito non lo chiede
  —, ma un ruolo assegnato a mano poteva concedergli quel diritto, e un bot amministratore il cui token trapela apre
  l'intero server. Ogni volta che entra nel server, il registro del bot, che l'amministrazione mostra, lo dice, e nomina
  i permessi che mancano al suo ruolo.
- **Il link di «/forum account link» non collegava sempre l'account giusto.** Chi riceveva quel link da qualcun altro
  collegava l'account Discord *di quella persona* al PROPRIO account del sito — e dato che un account collegato permette
  di accedere, chi aveva inviato il link poteva poi entrare nell'account della sua vittima. Il collegamento ora si
  conferma presso Discord: il sito confronta l'identificativo che Discord gli restituisce con quello che il link
  nomina, e non collega nulla se differiscono. Un avvertimento sostituiva questo controllo; non bastava.

### Corretto

- **L'impostazione «Carattere del sito» diceva che i caratteri erano forniti da Google Fonts.** Li fornisce il sito
  stesso da quando nessun visitatore viene più mandato a Google: ora la descrizione lo dice.
- **Un aggiornamento di addon rieseguiva tutte le sue migrazioni.** La lettura delle migrazioni già fatte tornava
  sempre vuota: a ogni aggiornamento di un modulo, di un widget o di un tema — dal marketplace o tramite un archivio
  —, tutte le sue migrazioni venivano rieseguite, e quelle che scrivono dati li scrivevano una seconda volta. Ora
  vengono eseguite solo le nuove. `check-db-query` sorveglia la forma che causava il difetto: una query preparata
  senza essere inviata.
- **Il conto alla rovescia degli eventi in arrivo restava vuoto.** Il suo script viveva in un tema rimosso: l'elenco
  degli eventi e la pagina di un evento mostravano una clessidra accanto al nulla, in tutti i temi. Ora vive con il
  modulo, e scrive il tempo che manca nella lingua della pagina («11gg 7h 22min», «11d 7h 22m»).
- **Subito dopo un aggiornamento, il sito poteva ancora servire il vecchio codice.** La cache di PHP (OPcache)
  conserva il codice compilato di un file finché non lo ricontrolla: due secondi di solito, e fino al riavvio di PHP
  presso gli hosting che disattivano questo controllo. L'aggiornamento del nucleo, quello di un addon dal marketplace e
  l'invio di un archivio con «Aggiungi» non la avvisavano: una pagina di un modulo aggiornato rispondeva «pagina non
  trovata» finché i suoi nuovi indirizzi non venivano riletti, e un sito poteva girare con un misto di codice vecchio e
  nuovo. Tutti e tre ora avvisano la cache di ogni file che scrivono.
- **Aggiornare un addon inviandone l'archivio saltava le sue migrazioni — e cancellava la disposizione di un
  tema.** «Aggiungi», in *Temi & addon*, reinstallava l'addon invece di aggiornarlo: le modifiche al suo database
  venivano segnate come fatte senza esserlo, e un tema perdeva tutte le sue disposizioni con i loro widget,
  sostituite dalla disposizione originale. Un archivio più recente ora si aggiorna come dal marketplace: il database
  segue, e la disposizione resta quella dell'amministratore.
- **Nell'editor live di Nebula, le anteprime degli stili erano quelle di Forge**: arancione su marrone. Ora prendono
  i colori di Nebula, quelli scelti dall'amministratore. Stessa correzione per il tema vetrina.
- **Un widget di navigazione che mostrava un menu ne teneva una copia congelata.** Copiava i link del menu al momento
  del salvataggio, e dimenticava persino quale menu fosse stato scelto: un link aggiunto o rinominato in seguito non
  arrivava mai nella navigazione. Il menu viene ora riletto a ogni visualizzazione, nella lingua della pagina.
- **Un link a una sezione di una pagina della wiki portava in cima alla pagina.** Solo i titoli di primo livello
  avevano un'ancora: un link a un sottotitolo non trovava nulla, e la documentazione, che rimanda spesso a una sezione
  precisa («vedi Indicizzazione»), lasciava il lettore all'inizio della pagina, in tutte le lingue. I sottotitoli hanno
  ora la loro ancora, e ogni rimando della documentazione porta al titolo che nomina.
- **Una pagina della wiki messa sotto una delle sue stesse sottopagine spariva dal sommario**, con tutta la sua sezione:
  la schermata proponeva le sottopagine come pagina madre. Non lo fa più, e un ciclo del genere inviato comunque viene
  rifiutato.
- **Il modulo di candidatura rispondeva «Errore 500» appena un'offerta poneva una domanda facoltativa.** Una regola
  «nessuna» scritta come testo vuoto non era capita dalla libreria dei moduli, e l'intera pagina cadeva. Trovato
  provando la traduzione delle domande.
- **Il widget *Reclutamento* nascondeva le offerte senza candidature** — cioè ogni offerta appena aperta. Il modulo era
  stato corretto; il widget no.
- **Una conferma di eliminazione aperta a pagina intera non mostrava nulla.** Nell'amministrazione, seguire un link
  «Elimina» in una nuova scheda — o senza JavaScript — portava a una pagina vuota: niente messaggio, niente pulsante,
  niente ritorno. Ora mostra ciò che sta per eliminare, il pulsante che lo fa e un link per tornare al modulo.
- **Le pagine Moderazione e Monitoraggio non avevano l'aspetto del resto dell'amministrazione.** Le loro schede di
  cifre, le loro intestazioni e il battito dell'indicatore di salute venivano da un foglio di stile di riserva —
  pensato per i siti che mostrano quelle schermate fuori dall'amministrazione — che, servito per ultimo, sovrascriveva
  il tema. La riserva si applica ora solo dove il tema non dice nulla.
- **In portoghese, un account non si poteva eliminare.** La schermata chiedeva di digitare «EXCLUIR» e il sito si
  aspettava «ELIMINAR»: la conferma falliva ogni volta. Le altre cinque lingue erano corrette. Un controllo rifiuta ora
  un'istruzione che citi una parola diversa da quella che il sito si aspetta.
- **Il portoghese mescolava Brasile e Portogallo.** Ottanta espressioni riviste: *usuário* diventa *utilizador*,
  *senha* diventa *palavra-passe*, *excluir* diventa *eliminar*, *registro* diventa *registo*, e *arquivo* diventa
  *ficheiro* quando indica un file — e resta *arquivo* quando indica un archivio.
- **Alcune frasi inglesi maldestre**: «All posts will be also deleted» diventa «will also be deleted», nei quattro
  messaggi di eliminazione in cui restava.
- **Tradurre un contenuto per la prima volta: il testo andava perso, senza una parola.** Scrivere una notizia in
  francese, passare l'amministrazione all'inglese, tradurla e salvare: non veniva conservato nulla. La riga di quella
  lingua non esisteva ancora, e il salvataggio non toccava nessuna riga — in silenzio. Valeva per notizie, post del
  blog, pagine, categorie, gallerie, giochi, squadre, gruppi e partner. Una traduzione crea ora la sua riga.
- **Un contenuto non ancora tradotto era introvabile nell'amministrazione di quella lingua.** Gli elenchi di notizie,
  post del blog e pagine mostravano solo ciò che esisteva nella lingua in cui si lavora: il resto era invisibile, quindi
  impossibile da aprire per tradurlo. Gli elenchi mostrano ora tutto, con il miglior titolo disponibile, e la schermata
  di modifica si apre con i campi vuoti, pronti per la traduzione.
- **Rinominare un gruppo cancellava tutte le sue traduzioni.** Il salvataggio non puntava a nessuna lingua in
  particolare: scriveva lo stesso titolo su tutte le righe del gruppo — e falliva appena il sito ne aveva più di una.
- **Un partner poteva avere una sola lingua.** La tabella delle traduzioni del modulo *Partner* aveva come chiave solo
  il numero del partner: una seconda lingua veniva rifiutata o sovrascriveva la prima. Ora ha una riga per lingua, come
  le altre sedici tabelle di traduzione, e descrivere un partner in una nuova lingua crea la sua riga invece di non
  scrivere nulla.
- **Il prossimo appuntamento: il giorno della settimana incollato al numero del giorno.** Nella casella della data del
  widget *Il prossimo appuntamento*, le tre righe — giorno della settimana, numero del giorno, mese — si toccavano, e si
  sovrapponevano appena un tema applicava il suo carattere da titoli: le cifre uscivano da un'altezza di riga più bassa
  del loro inchiostro. Le tre righe mantengono ora la loro distanza nei sette temi, in modalità chiara e scura. Stessa
  correzione per i grandi numeri del widget *Il sito in cifre*.
- **Pulse disegna finalmente i suoi widget.** Il tema prevedeva un proprio aspetto per la casella della data del
  calendario, i numeri de *Il sito in cifre*, la settimana e le miniature della galleria: nessuno si vedeva, perché il
  foglio di stile del widget è servito dopo quello del tema e vinceva a parità di specificità. La piastrella di Pulse
  (angoli arrotondati, cifre grandi, grigio ardesia) appare ora come la mostrava il bozzetto.

## [1.2.49] — 2026-10-10

Un pacchetto corposo: un indirizzo con il titolo sbagliato porta a quello giusto, l'elenco delle pagine non trovate,
l'editor di testo nella lingua della pagina, il modulo Eventi utilizzabile senza giochi — per un'associazione o un
club —, le immagini nei ticket che arrivano da Discord, e un lungo elenco di correzioni trovate rileggendo tutto il
registro delle modifiche, tra cui la pagina di una partita che non mostrava più la sua partita. Il bot Discord passa
alla **versione 0.2.7** (vedi il suo registro delle modifiche).

### Aggiunto

- **Immagini in un ticket del Bug tracker**: uno screenshot allegato su Discord a un ticket o a un'idea, all'apertura o
  in una risposta, compare ora nel ticket sul sito — con il bot 0.2.7, che lo consegna al sito come fa già per il
  forum. Il testo di un ticket resta testo semplice: vi si mostrano solo le immagini conservate dal sito; l'indirizzo
  di un'immagine ospitata altrove resta testo.
- **L'elenco delle pagine non trovate**, in *Monitoraggio → Diagnostica*: ogni indirizzo che il sito non è riuscito a
  trovare — senza la lingua né i parametri —, quanti visitatori e robot l'hanno richiesto, quando l'ultima volta e
  l'ultima pagina da cui venivano i visitatori. Un pulsante «Reindirizza» apre il modulo di reindirizzamento con
  l'indirizzo già compilato; i sondaggi dei robot in cerca di una falla sono elencati a parte. Nessun indirizzo IP,
  nessun browser; un indirizzo non più richiesto da 90 giorni viene dimenticato.
- **Le immagini abbandonate dell'editor**, in *Monitoraggio → Diagnostica*: un'immagine caricata in un testo e poi
  tolta, o l'immagine di una bozza mai salvata, restava sul disco. La pagina le trova — nessun testo del sito le mostra
  più, nessun contenuto, messaggio, impostazione, revisione né elemento del cestino, e hanno più di 30 giorni —, le
  mostra ed elimina quelle che spunti.
- **L'editor di testo parla la lingua della pagina**: i suoi menu, pulsanti e finestre erano in inglese ovunque; ora
  sono in francese, tedesco, spagnolo, italiano o portoghese secondo la lingua della pagina.
- **Una GIF animata resta animata** nell'editor: diventava un'immagine fissa. Viene riscritta conservando solo i suoi
  fotogrammi, i loro tempi e il suo ciclo. Una GIF di più di 2000 px di lato o di più di 1000 fotogrammi diventa
  ancora un'immagine fissa, come prima.
- **Eventi senza giochi**: il modulo Eventi non richiede più i moduli Giochi e Squadre. Un'associazione o un club lo
  installa da solo — inviti, risposte, ricorrenza, promemoria; le partite, i loro avversari e i loro risultati arrivano
  con i moduli Giochi e Squadre. Entra nei profili di installazione «Community» e «Associazione / club».

### Corretto

- **Un indirizzo con il titolo sbagliato reindirizza a quello giusto** (reindirizzamento permanente, 301): il titolo
  del contenuto è cambiato, o il link viene da un'altra lingua o contiene un errore di battitura. Gli articoli del
  blog, le categorie, gli autori e le serie, il palmarès, i ticket del Bug tracker, gli eventi del calendario, gli
  annunci, i sondaggi e le ricette rispondevano a qualsiasi titolo — duplicati per i motori di ricerca; le notizie, gli
  eventi, le offerte di reclutamento, le squadre, gli album e le immagini della galleria, i forum e le discussioni, i
  profili, i gruppi e i partner rispondevano «non trovato» al vecchio indirizzo di un titolo cambiato. La pagina di una
  categoria del blog mostra il suo titolo, non più il testo dell'indirizzo; la pagina di un tipo di evento o di una
  squadra che non esiste risponde ora «non trovato».
- **Un'immagine caricata nell'editor non deborda più** dalla pagina nelle pagine che non la limitavano (la scheda
  «Informazioni» di un profilo, eventi, partner, album, offerte di reclutamento, squadre) né su un telefono: occupa al
  massimo la larghezza della colonna.
- Una dimensione inferiore a 1000 byte si legge «685 B», non più «685.00 B».
- **Forge**, in modalità giorno: il testo attenuato (date, autori, luoghi, «Registrato il…») è un po' più scuro —
  restava appena sotto la soglia di leggibilità.
- **Forge ed Extend**: le linee sottili che scelgono l'immagine del carosello si toccano facilmente su un telefono
  (erano alte solo 3 px); visivamente non cambia nulla.
- **Chronique**: il numero di notifiche non lette, sull'icona dell'account, si legge molto meglio, di giorno e di
  notte.
- **Discord**: quando si elimina una discussione o un messaggio del forum, un ticket o un commento di ticket, il dato
  che lo collega al suo thread Discord viene ora tolto automaticamente una settimana dopo; restava per sempre nel
  database.
- **Niente più attaccato al bordo**: l'elenco degli IP bloccati e il suo modulo di aggiunta (la frase di aiuto e il
  pulsante toccavano i bordi della loro scheda), il campo di ricerca dell'elenco degli eventi, la paginazione di una
  tabella in Granite, la paginazione e il pulsante «Segui» in Nebula, un nome utente lungo nell'elenco dei membri. Su un
  telefono, le tabelle del carosello e dei permessi scorrono dentro la loro scheda invece di debordare.
- **Niente più pulsanti attaccati**: «Invita», «Esci»… in una conversazione, su un telefono; «Indietro» e «Pubblica la
  discussione» nel forum; i due pulsanti della ricerca del forum nell'amministrazione.
- **Forge**: su un telefono, il menu dell'area membri è tornato — scivolava sotto la scheda della pagina; su un
  computer, resta in vista durante lo scorrimento. Il pulsante verde «Vedi la mia candidatura» è di nuovo leggibile.
- **La pagina di una partita** mostra di nuovo la sua partita — la squadra, l'avversario, il risultato, il gioco e i
  round, che non mostrava più. Squadra, risultato e avversario stanno su una riga, come ogni round, su un telefono come
  su un computer.
- **Reclutamento**: la pagina «La mia candidatura» mostra di nuovo il ruolo e la squadra giusti — mostrava il nome
  dell'icona al posto del ruolo. **Forum**: modificare un forum, nell'amministrazione, mostra di nuovo la sua
  descrizione e la sua categoria — mostrava invece il titolo, e il salvataggio poteva spostare il forum altrove.
- **Moderazione**: una sanzione viene eliminata automaticamente tre anni dopo la sua fine (scaduta o revocata; un
  avvertimento, tre anni dopo essere stato dato); una sanzione senza fine (un ban definitivo, per esempio) resta finché
  dura. Venivano conservate senza limite.
- Il pulsante di segnalazione (la bandierina «Segnala questo contenuto») si tocca facilmente su un telefono;
  nell'elenco *Modelli email* dell'amministrazione, le variabili nell'oggetto di un'email (`{{date}}`) si distinguono
  dal testo.
- **Newsletter**: il destinatario «Gruppo» e l'elenco delle campagne nominano ogni gruppo nella lingua
  dell'amministrazione; lo prendevano in una lingua a caso («Criador de addons», «Mitwirkender»).
- **Etichette giuste**: sul profilo di un membro, il pulsante «Contacter» mostrava «Contact» in francese; il tema
  Extend scrive «In primo piano» e «Scopri» nella lingua della pagina (restavano in francese); il pulsante «Griglia»
  della pagina del blog e degli addon non si chiama più «Palinsesto» (né «Schedule» in inglese, né il suo equivalente
  in tedesco e spagnolo); il tipo di ticket francese «Demande de feature» diventa «Demande de fonctionnalité». In
  inglese, il pulsante di accesso dice «Log in», il menu «Log out», e «View my profile» è usato ovunque. Nel
  Monitoraggio, la scheda «Archiviazione» scriveva «Sauvegarde», «Libre» e «Total» in francese in tutte le lingue.
- **Pulse, di notte**: il numero di notifiche non lette, sull'icona dell'account, è leggibile — una cifra scura sul
  mattone chiaro; in bianco arrivava solo a 3,9:1.
- **Il widget Donazioni** mostra l'iniziale di un donatore il cui nome comincia con una lettera accentata; mostrava
  «&».
- **Reimpostare la password** rifiuta, come l'iscrizione e il cambio, una password identica al nome utente: la
  reimpostazione non la confrontava.
- Il bot Discord passa alla **versione 0.2.7** (0.2.6, poi 0.2.7): consegna al sito le immagini allegate ai ticket
  (vedi sopra), e il suo registro non annota più le riconnessioni ordinarie a Discord, solo un'interruzione di più di un
  minuto (vedi il suo registro delle modifiche).

## [1.2.48] — 2026-10-09

Un pacchetto corposo: un audit di sicurezza (accessi, iniezioni, account, file), una moderazione che finalmente
funziona — le sue sanzioni si applicano ora a tutto ciò che scrivono i membri —, un nuovo indirizzo email confermato
prima di contare, l'eliminazione degli account inattivi, e l'accesso con Discord, GitHub o Google riparato. Il bot
Discord passa alla **versione 0.2.5** (vedi il suo registro delle modifiche). **Su un sito in HTTPS, tutti accedono di
nuovo una volta dopo l'aggiornamento**: il cookie di sessione cambia nome.

### Sicurezza

- **La ricerca del forum, aperta ai visitatori, permetteva un'iniezione SQL** tramite una parola scritta tra
  virgolette; la ricerca della messaggistica privata aveva la stessa falla. Quella parola viene ora ripulita come le
  altre, e protetta dal database stesso.
- **Un invio che arriva da un altro sito viene rifiutato** (POST, PUT, DELETE…), in base a ciò che dice il browser
  (`Sec-Fetch-Site`, altrimenti `Origin`): una pagina trappola non può più far agire un membro connesso a sua insaputa.
  Un client senza browser — il bot, un servizio di pagamento — non è toccato.
- **Venti azioni che cambiavano qualcosa con un semplice link** richiedono ora il token di sessione, o accettano solo
  POST: seguire una discussione, farne un annuncio, segnare tutto come letto, lasciare una conversazione, archiviarla,
  eliminarla o ripristinarla, reagire, segnare le notifiche come lette, dare la propria disponibilità per un evento,
  aprire o chiudere il sito e le iscrizioni, avviare un backup o un aggiornamento, eliminare un campo di
  reclutamento… La disconnessione con un link senza token chiede una conferma con un pulsante.
- **In HTTPS, per un sito installato nella radice del suo dominio, il cookie di sessione ha il prefisso `__Host-`**: un
  sottodominio (una demo, una webmail) non lo riceve più e non può più imporne uno. Un sito installato in una
  sottocartella conserva un cookie senza questo prefisso, che cambia solo nome.
- **Account**:
  - il link «password dimenticata» funzionava anche come link di conferma dell'iscrizione, che fa accedere senza
    chiedere nulla per due giorni: ogni link funziona ora solo per il suo scopo;
  - cambiare la password annulla un nuovo indirizzo in sospeso e i link «password dimenticata»;
  - gli accessi falliti si contano per account — comunque sia scritto — e per rete: un indirizzo IPv6 conta per la sua
    rete /64, in tutti i limiti di tentativi del sito;
  - attivare l'autenticazione a due fattori o collegare un account Discord, GitHub o Google richiede di nuovo la
    password; cinque errori bloccano questa conferma per un quarto d'ora;
  - «password dimenticata» risponde allo stesso modo che un indirizzo sia registrato o no, e l'iscrizione dice che un
    indirizzo è già usato solo cinque volte all'ora e per rete;
  - lo storico degli accessi registra l'indirizzo della connessione, non più un'intestazione che sceglie il browser.
- **Dodici fughe di accesso chiuse**: lo storico e le revisioni di una pagina del wiki in bozza; le categorie VIP del
  forum, tramite la ricerca e i profili; i gruppi nascosti; l'immagine di un album chiuso; le partite di un tipo
  riservato; la presenza che i partecipanti avevano nascosto; i commenti e le reazioni su contenuti nascosti; segnalare
  una conversazione di cui non si fa parte.
- **Due vecchi indirizzi della messaggistica privata restituivano i messaggi di qualsiasi conversazione**, anche
  privata, a chiunque avesse il diritto di lettura — visitatori compresi: rimossi. Pubblicare un'immagine nella galleria
  richiedeva solo il diritto di vedere l'album.
- **Gli allegati del forum e delle conversazioni sono serviti dal sito**, a chi può leggere la discussione o la
  conversazione, non più dal server web a chiunque ne avesse l'indirizzo. Un'immagine o un PDF si mostra, tutto il
  resto si scarica; un allegato che un browser eseguirebbe viene rifiutato al caricamento. Su Apache se ne occupa
  l'`upload/.htaccess` incluso; su nginx o Caddy, copia nella configurazione del tuo server la regola che blocca
  `upload/forum/` e `upload/talks/` (vedi i `nginx.conf` e `Caddyfile` inclusi).
- **Ciò che il server va a prendere da sé** (relay di immagini, feed RSS, webhook) è limitato a indirizzi strettamente
  pubblici, sulle porte 80 e 443.
- **Le chiavi Stripe non si rileggono più in chiaro** nell'amministrazione: conservate cifrate, mai più mostrate.
- **Demo**: gli indirizzi IP dei visitatori, i nomi di host e i siti di provenienza non vi sono più mostrati; una
  pubblicità, un link, un partner che porta fuori dal sito si mostra su una pagina che dice dove porta, invece di
  reindirizzarvi.
- **Un link `javascript:` non può più mostrarsi** al posto di un indirizzo: la pagina da cui veniva una sessione
  (amministrazione), i link di una partita (sito dell'avversario, stream, articolo). Il link che una segnalazione dà
  verso il contenuto segnalato può portare solo a una pagina del sito.

### Aggiunto

- **Un nuovo indirizzo email attende la sua conferma**: il link va al nuovo indirizzo (valido due giorni), quello
  vecchio viene avvisato, e «Il mio account» propone di inviare di nuovo il link o di annullare. Finora un errore di
  battitura tagliava fuori il membro da tutto ciò che il sito invia.
- **Gli account inattivi si eliminano**: senza visite da tre anni — regolabile in *Impostazioni*, `0` per mai —, un'email
  avvisa il membro un mese prima, una visita annulla tutto. Mai un amministratore.
- **Sanzionare un membro senza aspettare una segnalazione**, dal suo storico di moderazione, a cui rimanda la sua pagina
  di amministrazione.
- **Il pulsante di segnalazione** (la bandierina «Segnala questo contenuto») nel libro degli ospiti, sulle immagini
  della galleria, gli annunci, i ticket e i loro commenti.
- **«Le mie sanzioni»** nell'area membri, per chi ne ha avuta una nell'anno: il suo tipo, il suo motivo, il suo periodo.
- **La mediazione**: a partire da una segnalazione, il moderatore propone una conversazione privata tra lui, il membro
  segnalato e chi ha segnalato. Il membro segnalato vi vedrà chi l'ha segnalato, quindi si apre solo con il consenso di
  chi ha segnalato, dato su una pagina che spiega ciò che accetta; se rifiuta, la sua segnalazione resta anonima e
  prosegue come al solito.

### Corretto

- **Le sanzioni di moderazione si applicano a tutto ciò che scrivono i membri**: forum, messaggistica privata,
  commenti, libro degli ospiti, Bug tracker, reclutamento, annunci, galleria, profilo, immagini caricate nell'editor di
  testo. Si controllava solo il silenziamento del forum e della messaggistica, e dopo l'invio. Il modulo lascia il posto
  a un avviso che dice cosa vieta la sanzione, fino a quando e perché; la sanzione «Restrizione: link» rifiuta un testo
  che contiene un link, senza perderlo.
- **Nessuna sanzione poteva essere emessa dalla schermata di moderazione**: un errore interno lo impediva, quindi
  l'escalation automatica non scattava mai. Rivisti allo stesso tempo: le impostazioni (caselle che non si potevano
  togliere, soglia del segnalatore sospetto), gli ambiti, le durate (un ban temporaneo senza durata diventava
  definitivo), la convalida da parte di un moderatore di grado superiore, il pannello dei moderatori, le segnalazioni
  doppie, le pagine dalla 2 in poi, l'elenco degli IP. Un moderatore non può più revocare una sanzione che lo riguarda,
  né quella di un membro di grado pari o superiore, né una emessa da un moderatore di grado superiore.
- **Lo shadow ban funziona**: ciò che scrive un membro con shadow ban si mostra solo a lui e ai moderatori, e nulla viene
  diffuso — nessuna notifica, niente Discord, nessun iscritto.
- **Un ruolo in più — Moderatore — toglieva diritti a un membro**: un moderatore perdeva il forum e la messaggistica
  privata. Un ruolo aggiunge diritti, non ne toglie più.
- **Le email si inviano nella lingua di chi le riceve** — sanzioni, risposte nel forum, messaggistica privata —, non più
  in quella del membro che le ha provocate.
- **L'accesso con Discord, GitHub o Google falliva** appena un servizio era configurato (la libreria OAuth era cambiata),
  e mandava al servizio un indirizzo di ritorno che rifiutava. Un ritorno rifiutato dice ora perché invece di una pagina
  di errore, e l'avatar di Discord viene di nuovo importato.
- **Libro degli ospiti, amministrazione**: approvare, rifiutare o eliminare un messaggio portava a un errore «pagina non
  trovata».
- **L'errore di un campo di un modulo è scritto sotto il campo**, su un telefono come su un computer: si poteva leggere
  solo in un fumetto al passaggio del mouse.
- **La barra in alto dell'amministrazione resta in alto** durante lo scorrimento; su un telefono, restano fissi solo il
  menu e le briciole di pane.
- **Trentaquattro errori di traduzione** in dodici file di lingua («There are 1 user», conferme di eliminazione che
  mostravano `< br / >`, plurali inglesi…).
- L'indirizzo del sito dichiarato da 48 addon del nucleo è quello del progetto, non più quello del NeoFrag originale.
  La pagina *Modelli email* dell'amministrazione non elenca più i modelli di un modulo non installato. Un errore in
  alcuni moduli (limite del libro degli ospiti raggiunto, eliminazione dell'account, autenticazione a due fattori,
  password, creazione di un membro nell'amministrazione) non porta più a un errore «pagina non trovata».
- **Demo**: un membro comune non poteva aprire una discussione, rispondere da nessuna parte né candidarsi a nulla.
- **L'esportazione «I miei dati» comprende le segnalazioni fatte dal membro**: mancavano dall'archivio.

### Modificato

- **Un widget legato a un modulo ora dipende da esso** (21 widget): senza quel modulo, o se è disattivato, il widget
  non si mostra più e l'Editor live non lo propone più; «Aggiungi» rifiuta un archivio di widget senza il suo modulo.
- **La documentazione del prodotto non viene più fornita** nel wiki di un sito nuovo, che arriva vuoto; vive sul sito
  del progetto. Una nuova installazione non riceve più le impostazioni di temi che non ha.

### Rimosso

- I vecchi indirizzi `ajax/talks` della messaggistica privata, e la sua vecchia segnalazione dei messaggi, che scriveva
  solo nel log di audit, con la sua pagina di amministrazione «Segnalazioni di messaggi»: un messaggio si segnala ora
  tramite la moderazione.

## [1.2.47] — 2026-10-09

La barra degli oggetti di Blockcraft, sui telefoni.

### Corretto

- **La barra degli oggetti di Blockcraft occupa tutta la larghezza del telefono**: le sue caselle si dividono lo
  schermo in parti uguali e ogni nome sta nella sua — su due righe per «Il mio spazio», con il trattino per una parola
  tedesca o portoghese troppo lunga («Neuig-keiten»). Manteneva la stessa larghezza qualunque fosse lo schermo (sette
  caselle da 47 px): uno spazio vuoto a destra, caselle strette e «Notizie» che usciva dalla sua casella. La cornice
  bianca della casella scelta non tocca più il suo nome.

## [1.2.46] — 2026-10-08

Le barre in cima alla pagina non coprono più nulla, la campanella arriva su Nebula, e la pagina *Temi & Addons* può di
nuovo reinstallare un tema.

### Corretto

- **Ciò che resta incollato in cima allo schermo si colloca ora sotto le barre in cima alla pagina** — la barra della
  demo, quella della manutenzione e quella dell'anteprima dei permessi. Appena si scorreva, metà delle intestazioni di
  Extend, Chronique, Pulse e Nebula scivolava sotto la barra della demo; il fondo della barra laterale di Forge e della
  barra laterale dell'amministrazione usciva dallo schermo; sui telefoni, lo stemma e l'account di Forge erano nascosti.
  Il modello principale ora impila le barre e pubblica la loro altezza; i temi, l'amministrazione e l'Editor live si
  allineano sotto di esse, e le notifiche sullo schermo compaiono sotto.
- **Le colonne fisse dei moduli si allineano sotto l'intestazione del tema**, qualunque essa sia: il menu dell'area
  membri (a 16 px dall'alto, scivolava sotto un'intestazione fissa di 60-70 px), l'indice del wiki, l'indice e il
  riquadro laterale di un articolo. Una colonna più alta dello spazio che le resta non si incolla più: il suo fondo
  restava fuori schermo fino alla fine della pagina — il menu dell'area membri di Extend, sullo schermo di un portatile.
- **Un link verso un'ancora si ferma sotto l'intestazione fissa** — un messaggio del forum, una sezione del wiki o di
  un articolo — invece di lasciarvi sotto il suo titolo: ogni tema fissava questo scarto a mano, senza contare le barre.
- **«Reinstalla predefinito» ed «Elimina» un tema funzionano dalla pagina «Temi & Addons»**, e l'ordine delle lingue e
  dei servizi di autenticazione viene salvato: la conferma era rifiutata, «token di sicurezza non valido».
- **La barra della demo è leggibile**: il suo testo, bianco su verde (contrasto di 2,4:1), diventa scuro.
- **Sui telefoni, i pulsanti di un'intestazione dell'amministrazione passano sotto il titolo**: nella pagina degli
  articoli, «Categorie», «Serie» e «Nuovo articolo» coprivano «4 pubblicati», e l'ultimo usciva dallo schermo.
- **Un sito aperto con un nome diverso da quello della sua configurazione** (con o senza `www.`, per esempio) mostra le
  immagini scritte con l'indirizzo configurato: la politica di sicurezza del browser le bloccava, perché ai suoi occhi
  venivano da un altro sito; ora passano dal relay di immagini del sito.
- **Per i contributori**: lo strumento `check-mise-en-page` non segnala più false sovrapposizioni dentro un blocco
  chiuso (`<details>`), che Chrome mantiene nella pagina senza mostrarlo — 48 falsi allarmi nella pagina *Registro degli
  errori* del Monitoraggio —, né nei messaggi di errore che quella pagina mostra di proposito.

### Aggiunto

- **La campanella delle notifiche su Nebula**, come negli altri temi: l'area membri la annuncia «in cima a ogni
  pagina», e Nebula non ne aveva.
- **Per gli autori di temi**: l'intestazione fissa di un tema porta `data-nf-entete`, e ciò che resta incollato in cima
  allo schermo si allinea su `--nf-haut` (e una colonna su `--nf-entete`); un nuovo controllo, `check-colles`, lo
  verifica — eseguito sulla 1.2.45, ha trovato venti regole CSS difettose (*Creare un tema*).

### Rimosso

- **Regole di stile inutilizzate nel foglio di stile di Nebula** (una barra, dei pulsanti e un piè di pagina ereditati
  da un altro tema): nessun modello di Nebula le usava.

## [1.2.45] — 2026-10-08

La campanella delle notifiche, rivista in ogni tema che la mostra, e l'accesso a due fattori in un solo passaggio.

### Corretto

- **Con l'autenticazione a due fattori, il codice viene chiesto subito dopo la password**: la finestra di accesso lascia
  il posto a quella del codice, senza ricaricare la pagina. Si chiudeva, e bisognava cliccare di nuovo su «Accedi» per
  vedere il codice; dal modulo dell'area membri, la finestra del codice si apre al ricaricamento.
- **Il badge della campanella sta nel suo angolo** in ogni tema che mostra la campanella: il foglio di stile del
  modulo, caricato dopo quello del tema, lo rimetteva nel flusso, e in Extend e Forge finiva in mezzo al pulsante, sulla
  campanella.
- **La campanella resta al centro del suo pulsante** quando vi sta sopra un badge: un'icona che non era più sola nel suo
  link prendeva un margine destro e scivolava di tre pixel.
- **«Notifiche» e «Segna tutto come letto» non si toccano più** in cima all'elenco: mantiene la sua larghezza quando il
  tema restringe i suoi menu, e i due testi mantengono uno spazio, andando a capo se serve.
- **Le date si mostrano nel formato della lingua e nel fuso orario del visitatore** nell'elenco della campanella, nello
  storico delle revisioni e nel fumetto di una segnalazione, che mostravano il timestamp grezzo del database.

## [1.2.44] — 2026-10-08

Una correzione della 1.2.43.

### Corretto

- **Il filtro dei servizi terzi funziona con PHP-FPM e Apache.** Viene eseguito alla fine della richiesta, quando la
  cartella corrente non è più quella del sito: non trovava più la chiave del relay di immagini, e la pagina usciva senza
  filtro. Le immagini di altri siti, che la politica di sicurezza ora rifiuta, non si mostravano (gli avatar del widget
  Discord), e un video incorporato si sarebbe caricato senza il suo avviso. Il difetto non si vedeva con il server
  integrato di PHP, che non cambia cartella alla fine della richiesta.
- **Il registro degli errori PHP conserva anche gli errori della fine della richiesta**, errori fatali compresi: il suo
  percorso era relativo, e veniva allora risolto a partire dalla radice del filesystem (`/`).

## [1.2.43] — 2026-10-08

Privacy: nessun visitatore viene più mandato a un terzo senza il suo consenso.

### Aggiunto

- **Una vera scelta sui servizi di altre aziende.** Il banner dei cookie controllava solo Google Analytics, mentre i
  video, il widget Discord e i captcha si caricavano qualunque cosa rispondesse il visitatore. La finestra **«Gestisci i
  miei cookie»**, aperta dal piè di pagina di ogni pagina, mostra i cookie propri del sito, poi ogni servizio, da
  accettare o rifiutare uno per uno. Al posto di un video di YouTube, Twitch, Vimeo o Dailymotion, di un brano di
  Spotify o SoundCloud, o del widget Discord, un avviso dice chi riceverebbe l'indirizzo IP del visitatore, con «Mostra»
  e «Consenti sempre…». Il banner si apre solo se il sito ha davvero qualcosa da chiedere subito (misurazione del
  pubblico, un captcha gestito da un terzo); «Accetta tutto» e «Rifiuta tutto» hanno lo stesso peso. La scelta viene
  conservata sei mesi, e la sua registrazione, senza l'indirizzo IP, tredici mesi.
- **Impostazioni → Privacy**: le pagine delle note legali e dell'informativa sulla privacy, scelte tra le pagine
  pubblicate. Il piè di pagina di ogni tema vi rimanda, accanto a «Gestisci i miei cookie».

### Modificato

- **I caratteri sono serviti dal sito**: i temi e l'impostazione «Carattere del sito» li prendevano da Google Fonts, e il
  browser di ogni visitatore inviava il suo indirizzo IP a Google, a ogni pagina.
- **Le immagini di altri siti sono servite dal sito** — un avatar di Discord, Steam o Twitch, una GIF in una
  conversazione, un'immagine incollata in un articolo, nel forum o in un annuncio, un banner pubblicitario: il sito le va
  a prendere da sé e le conserva sette giorni, e il browser non contatta più il loro host. Anche le tessere della mappa
  dei luoghi.
- **La politica di sicurezza accetta ora solo il sito stesso** per le immagini e le connessioni degli script (tranne
  Google Analytics, se configurato). Lascia ora mostrarsi i lettori dei servizi noti (YouTube, Twitch, Vimeo,
  Dailymotion, Spotify, SoundCloud, Discord), che prima bloccava: un video in un articolo non si vedeva. Uno script di un
  altro sito incollato in un widget «Codice HTML» o in una pubblicità non viene più eseguito: passano solo Google
  Analytics e il captcha scelto.
- **Finché il visitatore non accetta un captcha di un terzo, ALTCHA lo sostituisce**, servito dal sito: nessuno deve
  consegnare i suoi dati a Google, hCaptcha o Cloudflare per scrivere sul sito o iscriversi.
- **Niente più pixel di tracciamento nella newsletter**, e niente più tasso di apertura: un tracciatore che l'iscrizione
  non aveva chiesto (raccomandazione della CNIL del 12 marzo 2026 sui pixel di tracciamento nelle email).
- **Tempi di conservazione**: il log di audit un anno, i contatori anti-abuso un giorno, le iscrizioni alla newsletter
  mai confermate trenta giorni, la coda di invio della newsletter novanta giorni; per una segnalazione trattata più di
  un anno fa, si cancellano l'indirizzo IP del suo autore e la copia del contenuto. Il cookie di accesso termina alla
  chiusura del browser, tranne con «Ricordami» (un anno), che non è più spuntato per impostazione predefinita.
- **Niente più mappe di Google nei testi**: una mappa di Google Maps incorporata in un testo non viene più accettata. La
  mappa del prodotto è il modulo «Mappa dei luoghi», basato su OpenStreetMap.
- Il link «Realizzato con NeoFrag Reborn» dei temi, la parola chiave del copyright `{neofrag}` e i link del piè di
  pagina dell'amministrazione portano al sito del progetto; portavano al sito del NeoFrag originale.

### Corretto

- **Nessun indirizzo IP viene più inviato a neofr.ag**: la bandiera del paese accanto agli indirizzi delle sessioni è
  stata tolta; per ottenerla, il browser dell'amministratore inviava tutti questi indirizzi al sito del NeoFrag
  originale.
- **Eliminare un account da amministratore cancella davvero** il profilo, gli account collegati, lo storico degli
  accessi e le notifiche, come l'eliminazione che un membro chiede da sé; prima chiudeva solo l'account.
- **L'archivio «I miei dati» ora si scarica**: tre dei suoi elenchi (discussioni del forum, commenti, messaggi delle
  conversazioni) leggevano colonne che non esistono, e l'archivio falliva.
- I fumetti dell'elenco delle sessioni e della galleria ora si mostrano: portavano un attributo di Bootstrap 4, che
  Bootstrap 5 ignora, e restavano vuoti.
- L'indirizzo dell'avatar del widget Steam veniva scritto così com'era nella pagina; ora viene protetto, come il resto
  del widget.
- Il fumetto «Apri il canale» e il pulsante «Chiudi» del lettore del widget Twitch, rimasti in francese, sono tradotti.

## [1.2.42] — 2026-10-07

Un tema ridisegnato: **Extend**, il sito come launcher di un gioco online.

### Aggiunto

- **Extend 2.0.0 «Launcher»** — un'impaginazione del tutto nuova, scelta tra tre bozze. Una barra di schede in alto, in
  maiuscole strette, la scheda aperta illuminata da una linea blu; nella pagina iniziale, una **grande vetrina** (il
  carosello, il suo titolo in grandi lettere e il suo pulsante «Scopri»; senza carosello, il nome del sito
  sull'immagine del tema), poi i prossimi eventi in schede, le notizie in miniature, gli ultimi risultati; altrove, il
  titolo della pagina su quell'immagine. A destra, in ogni pagina, un **pannello sempre aperto**: chi è online, con gli
  avatar, poi il canale della chat (e, nelle pagine del forum, le sue statistiche). In basso, una **barra di stato**: il
  posto dei widget «Server di gioco», «Server TeamSpeak 3» e «Server Discord», la lingua, poi «Realizzato con NeoFrag
  Reborn», il copyright e i link legali. Il forum come biblioteca, l'area membri come scheda del giocatore (banner,
  avatar cerchiato, livello di gamification nella barra). Sui telefoni, le schede passano in fondo allo schermo, con
  «Online», che apre il pannello come un cassetto, e «Altro» oltre quattro sezioni. Blu acciaio su blu notte, notte
  per impostazione predefinita, giorno su richiesta; titoli in Saira Condensed, testo in Albert Sans. Il logo,
  l'immagine della vetrina, lo sfondo e i colori sono regolabili. Basato sul tema di Chewbaka, di cui conserva il nome
  e la licenza.
- **«Chi è online? (elenco)»**, una nuova visualizzazione del widget Membri: i tre conteggi (amministratori, membri,
  visitatori), poi le persone stesse con il loro avatar e la loro ultima attività.
- **«Un canale pubblico: i suoi ultimi messaggi»**, una nuova visualizzazione del widget Conversazioni: gli ultimi
  quattro messaggi di un canale aperto a tutti i membri, e il link per scriverci. Un visitatore non legge i messaggi:
  viene invitato ad accedere, come nella messaggistica stessa; il canale dello staff non viene mai mostrato.

### Corretto

- **I post correlati di un articolo del blog non debordano più sui telefoni**: il riquadro di un post senza immagine
  prendeva la sua larghezza dall'altezza della sua riga e usciva dallo schermo di qualche pixel (a 414 px, in ogni
  tema).
- **Blockcraft, rivisto sulla demo pubblicata**: su un telefono, il banner della pagina mostra tutto il paesaggio
  (ritagliato, mostrava solo tronchi d'albero), senza il sole né la luna, su cui passava un titolo lungo; la demo
  imposta un indirizzo di server di esempio (`play.example.org`), così si vede il pulsante «Copia l'indirizzo».

## [1.2.41] — 2026-10-07

Un tema ridisegnato: **Blockcraft**, il sito di un server di un gioco a blocchi.

### Aggiunto

- **Blockcraft 2.0.0 «Spawn + Inventario»** — una nuova impaginazione, scelta tra quattro bozze. In alto, un cielo sopra
  un paesaggio di blocchi (colline, alberi, pietra), il sole e nuvole che passano, la luna e le stelle di notte; nella
  pagina iniziale, il nome del server in grandi lettere e **il suo indirizzo, copiato con un clic** (impostazione
  «Indirizzo del server»), e il Discord quando è impostato. La navigazione è una **barra degli oggetti**: ogni sezione
  nella sua casella, con l'oggetto che la rappresenta (il libro per le notizie, la mappa per il forum…); su un telefono
  resta incollata in fondo allo schermo, a portata di pollice. Blocchi ad angoli retti con ombre nette, il **forum come
  bauli** (un coperchio di assi per categoria, ogni forum nella sua casella, una casella incantata che luccica quando
  c'è qualcosa di nuovo), l'**area membri come schermata del personaggio**, un piè di pagina di pietra. Titoli in
  Jersey 10, testo in Rubik, cifre in VT323; «prato» di giorno, «cielo stellato» di notte. Ogni pixel è disegnato per il
  tema. Di giorno, i testi del cielo sono scuri: in bianco, sarebbero stati leggibili solo grazie alla loro ombra.

### Corretto

- **La colonna laterale di Pulse, Chronique e Blockcraft non deforma più i widget che vi si collocano**: la sua
  impaginazione arrivava anche dentro i widget, e «Chi è online?» vi allontanava le sue cifre dalle loro etichette.
- **La scheda scelta del profilo di un membro è leggibile in Pulse, Chronique e Granite**: questi temi cambiavano il suo
  testo senza cambiarne lo sfondo, che il modulo dipinge con il colore d'accento — testo scuro sull'accento.
- **L'icona di un gruppo non tocca più il suo nome** (per esempio «Squadra principale», nel profilo e nell'area membri):
  la base comune dei temi non lasciava spazio tra l'icona e il nome — nei sei temi che la caricano.
- **I commenti di un evento conservano la loro icona** in Pulse, Chronique e Granite: questi temi toglievano l'icona da
  ogni link nel piè di una scheda, e il link diceva solo «0 →». Ora solo i piè dei widget («Visualizza il calendario →»)
  perdono la loro icona.
- **La pillola scelta del modulo di messaggistica è leggibile** («Tutti», «Archivio»…) in Pulse e Chronique: la base
  comune dava al suo testo il colore dei link e alla sua icona il colore d'accento, su uno sfondo del colore d'accento
  (1,1:1).
- **Granite**: i pulsanti nel piè di una scheda («Pubblica un'immagine», «Vedi la mia candidatura», la paginazione
  delle sessioni) prendevano il rosso dei link sull'inchiostro (2:1); «Revoca questa sanzione» aveva solo 3,7:1; su un
  telefono, il capolettera della prima pagina scivolava sulla riga «Di … il …» sotto; i pulsanti piccoli sono alti
  24 px.
- **Pulse, di notte**: il contatore del menu dell'area membri è leggibile: bianco sul mattone notturno più chiaro, aveva
  solo 3,9:1; ora conserva il mattone del giorno.
- **Il menu dell'area membri, su un telefono, si aggancia all'inizio di una scheda**: centrato al pixel, lasciava la
  scheda precedente tagliata all'inizio del suo nome («…fiche»).
- **Attivare la «Modalità debug» dal Monitoraggio non fa più bloccare la sua pagina**: la risposta si bloccava appena
  una notifica era in attesa («Cannot use object of type stdClass as array»).
- **Le guide contano i temi del pacchetto**: «Concetti» ne annunciava quattro (mancavano Chronique e Pulse), e «Creare
  un tema» nominava quattro temi sulla base comune invece di sei.

### Modificato

- **`check-mise-en-page` non grida più al lupo**: il testo del banner dei cookie o di una barra incollata in fondo allo
  schermo passa di proposito sopra un grafico (solo lo stesso livello crea un conflitto); la parte nascosta di un testo
  — il codice di un blocco che scorre, una descrizione troncata — non «si sovrappone» più alla colonna successiva; una
  scheda in una fascia che scorre non è più «persa oltre il bordo sinistro». Meno falsi difetti, e quelli veri escono
  ancora (verificato su una pagina di prova truccata, con la vecchia sonda e la nuova).

## [1.2.40] — 2026-10-07

L'indicizzazione da parte dei motori di ricerca, rivista dopo gli avvisi della Search Console sul sito ufficiale:
Google non indicizzava le discussioni del forum né le pagine del wiki, e gli si offrivano pagine vuote. E il messaggio
di benvenuto, che non veniva più inviato.

### Corretto

- **I contenuti senza lingua non si presentano più come duplicati**: una discussione del forum, una pagina del wiki, un
  ticket, un evento, un annuncio, una ricetta, un sondaggio, un'offerta di lavoro, un riconoscimento, una campagna di
  donazioni — scritti una sola volta — rispondevano sotto ogni lingua del sito, tradotti solo i menu, e ogni indirizzo
  si dichiarava canonico. Google ne sceglieva un altro e non ne indicizzava nessuno («Duplicate, Google chose different
  canonical than user»: sul sito ufficiale, in sei lingue, 59 contenuti elencati sei volte, 354 dei 580 indirizzi della
  sua mappa del sito). La loro pagina canonica è ora nella prima lingua del sito, l'unica annunciata in `hreflang` e
  nella mappa del sito. Lo stesso vale per le pagine fatte di tali contenuti: le FAQ, il glossario, le citazioni, i
  link, i download, il negozio e l'elenco degli eventi.
- **Le pagine vuote non vengono più offerte ai motori di ricerca**: la web radio senza flusso né programma, il libro
  degli ospiti senza messaggi e la pagina delle donazioni senza campagne erano nella mappa del sito, in ogni lingua —
  «soft 404» per Google. Restano fuori finché sono vuote, e dichiarano `noindex`.
- **`/fr/forum/` reindirizza ora in modo permanente a `/fr/forum`**: il reindirizzamento di un indirizzo che finisce con
  una barra, o con una barra doppia, era temporaneo (302) — e il primo perdeva tutto ciò che seguiva il `?`; ora è
  permanente (301) e conserva la fine dell'indirizzo.
- **Il messaggio di benvenuto viene di nuovo inviato**: il modulo Membri non trovava il modulo di messaggistica, e un
  nuovo membro non riceveva nulla. Un modulo che ne caricava un altro (`$this->module(…)`) otteneva sempre «niente» — lo
  stesso difetto privava i webhook dei commenti del titolo e dell'indirizzo del contenuto commentato.
- **I backup degli aggiornamenti non si accumulano più**: il sito conserva sempre i cinque più recenti e toglie gli altri
  dopo trenta giorni, ma aggiornamenti ravvicinati li lasciavano passare tutti — il sito ufficiale ne portava quaranta,
  663 MB. Ora non ne restano mai più di dieci, qualunque sia la loro età.

### Modificato

- **`check-seo` confronta le lingue tra loro**: prende uno stesso percorso in ogni mappa del sito e segnala lo stesso
  testo servito sotto più lingue, ciascuna canonica. Dichiarava sano il sito ufficiale; ora vi trova questi duplicati.
  Un modulo può dichiarare i suoi contenuti senza lingua: una voce della mappa del sito marcata `'sans_langue' => TRUE`
  è elencata solo nella mappa della prima lingua, e `nf_seo_sans_langue()` imposta la pagina canonica (guida «Creare un
  modulo»).

## [1.2.39] — 2026-10-06

Pulse, rivisto sulla demo pubblicata: il forum su un telefono non attacca più i suoi pulsanti, in ogni tema; e un
articolo del blog che usciva ancora dal bordo dello schermo sui telefoni.

### Corretto

- **Il forum su un telefono non attacca più i suoi pulsanti**: la barra di una discussione (Indietro, Rispondi e gli
  strumenti di moderazione) e la barra di un forum lasciano uno spazio tra i loro pulsanti e tra le loro righe; i
  pulsanti di un messaggio passano sopra la sua data, che non va più a capo su tre righe; il titolo di una discussione
  non tocca più il pulsante «Segui». In ogni tema.
- **Un articolo del blog non deborda più su un telefono**: un pezzo di codice lungo (un percorso di file, per esempio)
  va a capo, e una tabella che resta troppo larga scorre dentro la sua colonna (l'articolo della 1.2.0 usciva dal bordo
  dello schermo).

## [1.2.38] — 2026-10-06

Un nuovo tema: **Pulse**, la casa comune di associazioni, club e community, con tre aggiunte che servono a ogni tema —
il sito in cifre, il prossimo appuntamento e le ultime foto.

### Aggiunto

- **Il tema Pulse.** Una barra chiara che resta in alto, con le sezioni come pillole, l'account e l'invito «Aderisci»
  sempre in vista (un pulsante «Menu» su un telefono); la pagina iniziale è un mosaico di tessere — il nome e il motto
  del sito sull'immagine iniziale, il prossimo appuntamento, il sito in cifre, le notizie, l'agenda, il sondaggio, le
  conversazioni, i documenti, le foto, i partner — che l'Editor live riordina tessera per tessera (chiara, sole o
  ardesia). Il forum si legge come schede, con le sue statistiche e la sua attività a fianco; il piè di pagina scuro
  porta la mappa del sito e i social network. Chiaro di giorno, «ardesia» di notte a scelta del visitatore; titoli in
  Bricolage Grotesque, testo in Manrope. Impostazioni: colori, immagine iniziale, logo, e l'indirizzo del pulsante
  «Aderisci» (una pagina di iscrizione, un modulo su un'altra piattaforma, o la registrazione del sito).
- **Il widget «Il sito in cifre»**: fino a quattro numeri, scelti tra i membri, le conversazioni, i messaggi del forum,
  le notizie, i prossimi eventi e le foto — contando solo ciò che il visitatore ha il diritto di vedere (i forum
  riservati e gli album di gruppo restano fuori dal conteggio). In Pulse i numeri salgono quando la tessera compare, a
  meno che il visitatore non abbia chiesto meno movimento.
- **Il calendario mostra anche «Il prossimo appuntamento»**: la sua data in grande, il suo titolo, tra quanti giorni, a
  che ora (o «Tutto il giorno») e dove, l'inizio della sua descrizione, e un pulsante per aprirlo.
- **La galleria mostra anche «Le ultime foto»**, in griglia — solo dagli album che il visitatore può vedere.

### Corretto

- **Lo stile «Titolo colorato» di Chronique e Granite**: il titolo del pannello aveva il colore del suo sfondo (verde
  acqua su verde acqua, rosso su rosso), quindi era invisibile. Ora è colorato, sulla carta, come questi temi
  prevedevano — e l'anteprima dello stile nell'Editor live lo mostra così.
- **Lo stile «Widget colorato» di Granite**: il testo, le etichette dei moduli e il pulsante di un widget in questo
  stile potevano diventare bianchi sulla carta (o inchiostro su inchiostro, di notte). Conservano i colori del giornale.
  Il controllo del contrasto può ora misurare un widget in ogni tema e ogni stile di pannello.
- **Le briciole di pane sopra una notizia**: ogni tema le prevedeva, non comparivano mai — la regola che le collocava
  puntava a un indirizzo che non esiste. Ora compaiono sulle notizie e sull'elenco delle notizie, in ogni tema.
- **Due riquadri affiancati restano allineati**: sotto una notizia, «Altre notizie di questo autore» stava da 14 a 22 px
  più in basso di «Sull'autore» (lo stesso nella pagina di un evento e nella posta in arrivo).
- **Chronique: un articolo conserva i suoi margini** dentro la sua scheda; il titolo e il testo toccavano il bordo.
- **Il testo su una sfumatura è leggibile**: il pulsante «Unisciti al server» del widget Discord, le iniziali dello
  stemma di Forge, il titolo e il motto del banner di Blockcraft di giorno. Il controllo del contrasto misura ora il
  testo su una sfumatura, e da membro connesso (area membri compresa).
- **Un articolo del Blog con un solo titolo non appare più schiacciato**: senza indice, la pagina conservava la sua
  colonna, il testo si stringeva in 200 px e il riquadro laterale (autore, condivisione) prendeva il suo posto — le note
  di rilascio della 1.2.32, della 1.2.33 e della 1.2.37 sul sito ufficiale. Grazie a Blober per la segnalazione.
- **`admin.php` e `ajax.php` rispondono «pagina non trovata»** invece di un errore del server (e due righe di registro
  ogni volta che un robot sonda questi indirizzi).
- **La cronologia della stagione**: «Tra 5 giorni» non va più a capo su due righe su un telefono.

## [1.2.37] — 2026-10-06

Chronique, rivisto sulla demo come la si vede — da visitatore e connesso, di giorno, di notte, su un telefono —, e una
demo che vive nel presente.

### Corretto

- **L'area membri di Chronique.** Nel suo pannello, «Hai dimenticato la password?» stava sopra «Accedi» appena la
  colonna li metteva uno sotto l'altro; una volta connessi, le icone del menu restavano grigio scuro sull'ardesia e
  «Disconnetti» cadeva in un rettangolo bianco (nero di notte). Ora sono link bianchi, un solo pulsante pieno e uno
  spazio nei due sensi — anche con «Crea un account», quando le iscrizioni sono aperte.
- **Chronique su un telefono conserva l'accesso nell'intestazione**, come icona: la parola «Accedi» spariva, e bisognava
  scorrere fino in fondo alla pagina per accedere.
- **I pulsanti del pannello di accesso conservano il loro spazio quando vanno a capo**, nei cinque temi che condividono
  la base comune (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend: le icone dei campi del pannello colorato si vedono** (nome utente e password): erano bianche su un riquadro
  chiaro.
- **L'elenco dei membri, di notte**: un social network che un membro non aveva compilato diventava un punto grigio
  attaccato al successivo. È un'icona attenuata senza sfondo, e i pulsanti dei social compilati hanno un vero spazio —
  su una riga in una scheda stretta.
- **La demo vive nel presente.** Ripristinata ogni quarto d'ora, continuava a mostrare i contenuti del giorno in cui era
  stata scattata la sua istantanea: eventi e partite «prossimi» passati da settimane, un calendario vuoto, una
  cronologia della stagione che si sarebbe svuotata. Ogni ripristino sposta ora in avanti le date dei contenuti del
  tempo trascorso (gli intervalli tra loro non cambiano); i membri conservano le loro.

## [1.2.36] — 2026-10-06

Un nuovo tema: **Chronique**, il quaderno della stagione per associazioni e club, con due nuove funzionalità che servono
a ogni tema — la cronologia della stagione e la settimana del calendario.

### Aggiunto

- **Il tema Chronique.** Il sito come quaderno della stagione: un'intestazione discreta che resta in alto, con una linea
  sottile che segue la tua lettura, un pulsante «Sommario» che apre tutto il sito a schermo intero (ogni sezione
  numerata) e l'invito «Aderisci» per i visitatori; la pagina iniziale si apre su un grande titolo — la stagione, il
  nome del sito, il suo slogan — e la settimana in corso; poi la cronologia racconta la stagione mese per mese, accanto
  a una colonna che resta al suo posto (l'area membri, i documenti, i partner). Il forum si legge come capitoli
  numerati, l'area membri come un quaderno, e il piè di pagina chiude il libro. «Carta» di giorno, «luce di lampada» di
  notte; titoli in Fraunces, testo in Work Sans, date in IBM Plex Mono. Impostazioni: colori, immagine di apertura,
  logo, e l'indirizzo del pulsante «Aderisci» (una pagina di iscrizione, un modulo su un'altra piattaforma, o la
  registrazione del sito).
- **Il widget «Cronologia della stagione»**: ciò che il sito vive, mese per mese, su una linea del tempo — i prossimi
  eventi del calendario (il loro punto pulsa), le notizie, le discussioni del forum e gli album di foto, ciascuno se il
  suo modulo è installato, e solo ciò che il visitatore può leggere (i forum riservati e gli album di gruppo restano
  nascosti). Il numero di voci e i mesi passati sono regolabili.
- **Il calendario mostra anche «Questa settimana»**: i sette giorni della settimana in corso, quelli con un evento
  segnati, oggi cerchiato, e il prossimo appuntamento — nel fuso orario di chi guarda.

### Corretto

- **In modalità giorno, i componenti di Bootstrap prendono i colori del tema**: una fisarmonica delle FAQ, una casella,
  una tabella o un menu a tendina restavano bianchi, con testo grigio scuro, sulla carta di Granite. Verificato elemento
  per elemento su Forge, Granite, Blockcraft ed Extend: cambiano solo i loro colori, e la modalità notte resta com'era.
- **«Modalità giorno» e «Modalità notte» sono tradotte**: il fumetto e il nome, letto dai lettori di schermo, del
  pulsante che le alterna restavano in francese su un sito in un'altra lingua (Forge, Granite, Blockcraft, Extend). Il
  controllo dei testi scritti nel codice conosce ora le parole «jour» e «nuit».
- **La pagina di personalizzazione di un tema non scrive più un avviso nel registro** quando le sue impostazioni non
  esistono ancora (un tema registrato senza che la sua installazione abbia creato le sue impostazioni): la posizione di
  un'immagine torna al suo valore predefinito.

## [1.2.35] — 2026-10-06

Il secondo tema rifatto da zero: **Granite 2.0.0 «Gazette»**, il giornale delle associazioni e dei club.

### Modificato

- **Granite 2.0.0 «Gazette»: una nuova impaginazione, non una riverniciatura.** Il sito diventa il giornale del club:
  la data del giorno e l'account su una riga in cima, il nome del sito stampato molto grande (il logo sopra, se c'è), una
  doppia riga, poi le rubriche in maiuscoletto tra due righe. Nella pagina iniziale, una riga «In breve» fa scorrere le
  ultime discussioni del forum; si ferma al passaggio del mouse, con la tastiera e con il suo pulsante di pausa, e resta
  ferma quando il visitatore chiede meno movimento. La prima pagina si apre sull'articolo principale in grande, con il
  suo capolettera ornato, poi i seguenti in colonne separate da righe; la colonna di destra porta il calendario, il
  sondaggio e chi è online. I blocchi diventano pannelli senza riquadro, titolati in maiuscoletto sopra una riga; un
  articolo si legge come il corpo del testo di un giornale; il forum prende l'aspetto di una pagina di lettere dei
  lettori; l'area membri, quello di una tessera d'iscrizione timbrata con il primo gruppo del membro; il piè di pagina
  diventa una gerenza (i partner, poi chi pubblica). Una sottile barra di lettura segue la pagina. Il giorno è carta, la
  notte una pagina «inchiostrata», a scelta del visitatore; titoli in Playfair Display, testo in Source Serif. Sui
  telefoni, le rubriche scorrono di lato e tutto passa su una colonna.
- **Le zone di Granite portano il nome del loro posto**: «Rubriche», «In breve», «Contenuto», «Post-contenuto», «Piè di
  pagina».
- **Le impostazioni di Granite**: l'immagine del banner diventa l'immagine del titolo, e il colore del titolo, una volta
  cambiato, mette una fascia dietro il nome, come la testata di un quotidiano; il logo viene finalmente usato (sopra il
  nome); un'impostazione «Capilettera» sostituisce quella della barra in alto fissa, che non ha più ragione d'essere.

### Corretto

- **Niente più blu di Bootstrap tra i colori di un tema.** La barra di un sondaggio, la casella spuntata e il pulsante
  di opzione scelto (anche nell'amministrazione), la pagina attiva, la voce premuta di un menu, la freccia di una
  fisarmonica, l'alone del focus da tastiera e gli stati di un pulsante principale conservavano il blu predefinito di
  Bootstrap. Prendono il colore d'accento del tema, in ogni tema; un nuovo controllo, `check-bleu-bootstrap`, cerca quel
  blu nelle pagine servite, a riposo e con il focus, di giorno e di notte.
- **Le rubriche impostate da Forge e Granite sono tradotte**: «Matchs», «Nous rejoindre», «À la une», «Agenda» e
  «Documents» restavano in francese su un sito in un'altra lingua. Il controllo delle traduzioni verifica ora ogni
  titolo di menu che un tema imposta.
- **L'Editor live mostra il sito con i suoi caratteri**: imponeva i propri al contenuto delle zone che incornicia.
- **Il controllo del contrasto misura anche il testo posato su una grana** (carta, una retinatura): lo prendeva per
  un'immagine e rinunciava a misurarlo.

## [1.2.34] — 2026-10-06

Il primo tema rifatto da zero: **Forge 2.0.0 «Coulée»**, per i clan competitivi.

### Modificato

- **Forge 2.0.0 «Coulée»: una nuova impaginazione, non una riverniciatura.** La navigazione lascia la parte alta della
  pagina per una barra d'acciaio laterale, con il logo del sito in alto e l'account in basso (avatar, notifiche,
  modalità giorno, menu dell'area membri); sui telefoni, il logo e l'account passano in una barra in alto e la
  navigazione in una barra di schede in fondo allo schermo, e oltre cinque voci le ultime vanno in «Altro». La pagina
  iniziale si apre su un focolare di lava da cui salgono braci: il carosello, il suo titolo colato nel metallo, e la
  placca degli ultimi risultati. Sotto, un cruscotto: le notizie (la prima in evidenza) e, accanto, le prossime partite,
  il palmarès e chi è online. I blocchi sono placche ad angoli tagliati il cui bordo si accende al passaggio del mouse;
  il forum mette ogni forum in un cassetto d'acciaio con un indicatore di calore per la sua attività; l'area membri e il
  profilo pubblico ricevono la targa d'identità e schede smussate; il piè di pagina è rivettato e porta i partner. La
  modalità giorno schiarisce il contenuto, mentre la barra e il focolare restano acciaio e fuoco. Ogni movimento si
  ferma quando il visitatore chiede meno movimento. I blocchi delle partite, del palmarès e dei partner vengono collocati
  solo quando i loro moduli sono installati.
- **Le zone di Forge portano il nome del loro posto**: «Barra di navigazione laterale», «Parte alta della pagina»,
  «Contenuto», «Post-contenuto», «Piè di pagina».
- **Le impostazioni di Forge**: il logo viene finalmente usato (l'emblema della barra; senza logo, le iniziali del
  sito); l'immagine del banner diventa l'immagine del focolare; un'impostazione «Braci del focolare» (nessuna, lente,
  vive) sostituisce quella della barra in alto fissa, che non ha più ragione d'essere.
- **Il widget del palmarès** mostra il posto sul podio («1º», «2º», «3º») e il nome della competizione su tutta la
  larghezza disponibile, con il luogo e la piattaforma sotto; la riga cominciava con la piattaforma e tagliava il nome a
  venti caratteri.
- **Le immagini del carosello della demo non portano più il loro titolo**: il carosello lo scrive già sopra, e compariva
  due volte, attraverso la didascalia sui telefoni.

### Corretto

- **Un'impostazione di un tema si vede subito** (colore d'accento, immagini di sfondo e di banner…): l'indirizzo del
  foglio di stile seguiva solo la data del file, e il browser conservava il vecchio foglio dopo una modifica.
- **La pagina di personalizzazione dei temi Forge, Granite, Blockcraft ed Extend** ha come titolo il nome del tema,
  invece di un «Dashboard» non tradotto mostrato su ogni scheda.
- **In inglese, il palmarès non dice più «2th» o «3th»**: i posti sul podio hanno le loro parole, gli altri posti si
  leggono «#4».
- **Reinstallare un tema non scrive più un avviso nel registro** in modalità debug (un avviso «Deprecated» di PHP 8.2).

## [1.2.33] — 2026-10-06

Una versione di correzione: la CI pubblica del repository `neofrag`, rotta nella 1.2.32 da un problema degli strumenti,
è riparata. Per i siti non cambia nulla.

### Corretto

- **I controlli automatici (CI) del repository `neofrag` passano di nuovo.** Dalla 1.2.32, il passaggio che cerca la
  versione corrispondente del repository `extensions` poteva fallire: smetteva di leggere l'elenco delle versioni appena
  trovava quella giusta, e `git`, interrotto a metà scrittura, falliva ora che l'elenco è lungo. Ora legge tutto
  l'elenco; stessa correzione nella CI di `extensions`.
- **Il controllo dei link dei documenti non legge più quelli delle librerie incluse** (la licenza di TinyMCE): non si
  possono correggere da parte nostra, e gnu.org chiudeva la connessione al loro vecchio indirizzo.

## [1.2.32] — 2026-10-06

Comincia il rinnovamento dei temi: Forge, Granite, Blockcraft ed Extend condividono ora una base comune. Sullo schermo
non cambia nulla; è il fondamento su cui ciascuno riceverà la propria identità.

### Modificato

- **I temi Forge, Granite, Blockcraft ed Extend condividono una base comune** (`css/nf-socle-themes.css`): 153 delle
  regole che avevano in comune sono ora scritte una sola volta, nel nucleo; una cinquantina d'altre restano in ogni tema,
  perché la loro posizione nel foglio di stile conta. Sullo schermo non cambia nulla — verificato elemento per elemento
  e pixel per pixel, sui quattro temi, di giorno e di notte, su computer e su telefono. È il primo passo del loro
  rinnovamento: ciascuno avrà presto il proprio aspetto. I quattro temi passano alla 1.1.0 e richiedono il nucleo
  1.2.32.

## [1.2.31] — 2026-10-06

L'area membri, ultimi passi: la sicurezza dell'account — i dispositivi connessi si possono disconnettere, le sanzioni su
avatar e firma si applicano finalmente — e le notifiche, spostate nell'area membri, dove ogni membro sceglie ciò che
riceve. Il rinnovamento dell'area membri è completo.

### Aggiunto

- **«Sicurezza» mostra i dispositivi su cui l'account ha effettuato l'accesso, e permette di disconnetterli**: ogni
  sessione con il suo browser, il suo sistema, il suo indirizzo IP e la sua ultima attività, «Questo dispositivo» per
  quello da cui si guarda; «Disconnetti» per un altro, o «Disconnetti tutti gli altri dispositivi». Finora «Sicurezza»
  mostrava solo lo storico degli accessi, e una sessione aperta altrove non si poteva chiudere. Su un sito demo, il cui
  account è condiviso, non si può chiudere nessuna sessione.
- **Ogni membro sceglie le notifiche che riceve**, in «Preferenze di notifica»: una casella per tipo di notifica (un
  messaggio privato, una risposta in una discussione seguita, una menzione, un commento, una reazione, un promemoria di
  evento…) per riceverla sul sito, e una casella «Tramite email» per quelle che il sito invia anche per email (messaggi
  privati, risposte nel forum e menzioni). Senza alcuna impostazione, riceve tutto, come prima. Per gli autori di
  moduli: un modulo dichiara le proprie con `types_de_notification()` (vedi la guida alla creazione di un modulo).
- **«Le mie notifiche» entra nell'area membri**, sotto la voce «Notifiche» del suo menu, venti per pagina: la pagina
  viveva a parte, in `/notifications`, e mostrava solo le ultime cinquanta (ora fino alle ultime cinquecento). Il
  vecchio indirizzo porta al nuovo.

### Corretto

- **Le sanzioni su avatar e firma si applicano ora.** Un moderatore poteva emetterle, ma il profilo non le controllava
  mai: un membro sanzionato cambiava il suo avatar e la sua firma come prima. Ora vede ciò che la sanzione vieta, il suo
  motivo e fino a quando.
- **Otto modelli di dati del nucleo leggono sempre la propria tabella** (sessioni, storico degli accessi, addon,
  traduzioni, registri…): caricati da un modulo, cercavano una tabella che non esiste. Nulla lo mostrava ancora sullo
  schermo; il nuovo elenco dei dispositivi l'avrebbe fatto. È lo stesso difetto di quello dietro la favicon e i file
  eliminati, corretto nella 1.2.29.
- **Una menzione silenziata nel forum non inghiotte più la risposta.** Un membro menzionato in una discussione che segue
  non riceveva nulla quando la menzione era disattivata per lui — l'email di menzione disattivata dal sito («Notifica le
  menzioni @user per email»), o le menzioni disattivate nelle sue preferenze. Ora riceve la risposta. Quando la menzione
  gli arriva, prende il posto della risposta, così non viene avvisato due volte.

## [1.2.30] — 2026-10-05

L'area membri, secondo passo: il profilo pubblico, e ciò che ognuno sceglie di mostrarvi. E una correzione che conta:
un membro connesso può di nuovo leggere e scrivere nel forum.

### Aggiunto

- **Il profilo pubblico di un membro diventa una vera pagina.** La sua copertina come banner (si poteva caricare ma non
  compariva da nessuna parte), il suo avatar sovrapposto, il suo nome utente, il suo grado, i suoi gruppi e la sua
  presenza; per un visitatore connesso, «Contatta» (se la messaggistica privata è installata) e la bandierina «Segnala
  questo contenuto», o «Modifica il mio profilo» sul proprio; poi delle schede, ciascuna al suo indirizzo: Informazioni
  (citazione, identità, link, i campi pubblici del sito e le sue statistiche), Attività, e quelle che portano i moduli
  installati — Forum, Blog, Squadre, Annunci. Un modulo che non ha nulla da mostrare su questo membro non aggiunge
  nessuna scheda vuota.
- **Ogni membro sceglie ciò che mostra il suo profilo**, in «Privacy e dati»: i suoi punti, il suo karma e i suoi giorni
  VIP restano per sé finché non li mostra (erano visibili a tutti, visitatori compresi); la sua età e la sua presenza
  online restano mostrate finché non le nasconde. Il suo grado resta pubblico. La scelta si applica ovunque: la scheda
  che si apre passando sopra un nome utente, il punto dell'avatar, le rose delle squadre, i widget «Chi è online?» e
  «Attività del forum», la ricerca dei membri, i compleanni dell'orologio. Sul proprio profilo, un membro vede tutto,
  con un lucchetto su ciò che solo lui può vedere.

### Corretto

- **Un membro connesso può di nuovo leggere e scrivere nel forum**, e vede le gallerie, le pagine, i tipi di evento e le
  cartelle aperti ai visitatori. Dal rinnovamento dei permessi, il ruolo «Membro» aveva solo ciò che gli era stato dato
  espressamente: un membro vedeva «Nessun forum» dove un visitatore poteva leggere tutto, e non poteva scrivere da
  nessuna parte — solo gli amministratori, che aggirano i permessi, non vedevano nulla di strano. Tornano le regole
  originali: ciò che può fare un visitatore, lo può fare un membro; ciò che viene negato solo ai visitatori resta
  permesso ai membri. Una migrazione le ripristina su ogni sito, senza toccare una regola già impostata per i membri.
- **I gradi di reputazione (Novizio, Bronzo, Argento…) sono tradotti**: comparivano in francese in tutte le lingue. E il
  fumetto di un grado non rivela più il punteggio di karma.
- **Su un sito demo, il widget «Attività del forum» non mostra più l'account amministratore creato all'installazione**
  tra i membri online; «Chi è online?» lo lasciava già fuori.
- **Un sito web inserito senza «https://» in un profilo porta ora a quel sito web**: diventava un link a una pagina del
  sito stesso.
- **Un aggiornamento non sovrascrive più il catalogo del sito che serve il marketplace.** Il pacchetto di aggiornamento
  portava un vecchio catalogo, generato alla 1.2.22: messo su quel sito, non corrispondeva più agli archivi, e ogni
  installazione di addon veniva rifiutata finché il catalogo non era rigenerato. Un sito normale legge il catalogo
  online e non era toccato.
- **In «Il mio spazio», il gruppo del membro si allinea a sinistra sotto il suo nome utente**: era centrato in mezzo
  all'intestazione.

## [1.2.29] — 2026-10-05

L'area membri, primo passo del suo rinnovamento: una sola cornice, un solo menu, le impostazioni dove ci si aspetta di
trovarle. E il marketplace non propone più a un sito un addon fatto per una versione più recente di NeoFrag.

### Aggiunto

- **Una nuova password deve avere almeno 10 caratteri.** Viene rifiutata se ripete il nome utente, usa solo uno o due
  caratteri diversi («aaaaaaaaaa», «ababababab») o è una delle password più comuni («qwertyuiop», «1234567890»…). La
  regola si applica all'iscrizione, al cambio e alla reimpostazione (alla reimpostazione, il nome utente non viene
  confrontato). Le password esistenti restano valide.

### Modificato

- **L'area membri ha una sola cornice e un solo menu.** Cambiava forma da una pagina all'altra — un menu a sinistra
  sotto la scheda del profilo, una barra «Menu» chiusa in alto, niente del tutto — e una pagina aveva tre nomi. Le pagine
  dell'account hanno ora tutte lo stesso menu, nello stesso posto: una colonna a sinistra su un computer, una fascia di
  schede che scorre su un telefono, la pagina corrente evidenziata. Il menu porta anche alle pagine dei moduli: messaggi
  e notifiche con il loro numero di non letti, gli abbonamenti del forum (che nessun link raggiungeva), la moderazione.
  La barra in alto dei temi Blockcraft, Extend, Forge e Granite e il widget «Area membri» usano questo menu, con le
  stesse parole.
- **Le impostazioni sono dove ci si aspetta di trovarle.** «Il mio account» riunisce il nome utente, l'indirizzo email,
  la password, la lingua (che si poteva scegliere solo con il selettore di lingua del sito) e il fuso orario (che stava
  in mezzo al profilo pubblico); «Sicurezza» conserva l'autenticazione a due fattori e lo storico degli accessi; una
  pagina «Privacy e dati» contiene l'esportazione dei tuoi dati e l'eliminazione del tuo account, finora sistemate in
  «Sicurezza (2FA)».
- **«Il mio spazio» si apre su un'intestazione compatta** — avatar, nome utente, gruppi, «Vedi il mio profilo» e
  «Modifica il mio profilo» — invece della grande scheda del profilo, che su un telefono spingeva il menu giù di un
  intero schermo.

### Corretto

- **Il marketplace non propone più a un sito un addon fatto per una versione più recente di NeoFrag.** Solo
  l'aggiornamento di un addon controllava la versione di NeoFrag che richiede, non la sua installazione: un sito rimasto
  indietro poteva installare un addon che chiamava funzioni che la sua versione non aveva, e rompersi. Il marketplace
  ufficiale invia ora a ogni sito il catalogo fatto per la sua versione (un sito più vecchio di tutti i cataloghi
  conservati riceve il più vecchio). La finestra del marketplace lascia fuori anche un addon troppo recente e dice di
  aggiornare prima il sito. Il pulsante «Aggiornamenti» annuncia una nuova versione di NeoFrag in base al controllo
  degli aggiornamenti del Monitoraggio, non più solo in base al catalogo.
- **La favicon scelta dall'amministratore è anche l'icona del sito su un telefono, e un file eliminato lascia davvero il
  disco.** Un errore interno impediva al sito di trovare questi file: la favicon mancava quando si aggiungeva il sito
  alla schermata iniziale di un telefono (con un avviso nel registro ogni volta), e il file di un allegato eliminato dal
  forum o dai messaggi privati restava sul disco e nel database, come l'avatar e la copertina di un membro che cancella
  il suo account.

## [1.2.28] — 2026-10-05

Una versione di correzione: l'area membri di un account creato con Discord, GitHub o Google, l'esportazione e
l'eliminazione dei dati personali, e il sito demo.

### Corretto

- **Un membro iscritto con Discord, GitHub o Google non è più chiuso nel suo account.** Senza password, non poteva né
  cambiare il suo nome utente o il suo indirizzo email, né creare una password, né disattivare l'autenticazione a due
  fattori, né eliminare il suo account: ogni modulo chiedeva la password attuale. Ora conferma la sua identità
  ripassando dal servizio collegato; la conferma dura dieci minuti, e l'accesso tramite quel servizio vale come una.
- **L'esportazione «I miei dati» funziona, e contiene di più.** Falliva con un errore: il membro scaricava una pagina di
  errore invece dei suoi dati. L'archivio contiene ora anche il profilo (nome, data di nascita, località, firma, link),
  gli account collegati, lo storico degli accessi, le notifiche e ciò che i moduli del sito conservano a suo nome, in
  testo semplice e senza alcun segreto, nemmeno i suoi identificatori di sessione. Le discussioni del forum, i commenti e
  i messaggi delle conversazioni vi sono entrati solo con la 1.2.43.
- **Eliminare il tuo account cancella ciò che la pagina prometteva.** Il nome utente viene reso anonimo sui messaggi e il
  profilo svuotato; gli account collegati, lo storico degli accessi e le notifiche vengono cancellati, e un account
  Discord, GitHub o Google collegato torna libero per una nuova iscrizione. Il messaggio «Il tuo account è stato
  eliminato» viene finalmente mostrato.
- **Su un sito demo, l'account condiviso non si può più modificare**: un visitatore poteva cambiarne la password,
  attivarne l'autenticazione a due fattori o eliminarlo, chiudendo fuori tutti gli altri fino al ripristino.
- **«Connexion» è tradotto nel senso di «accedere»** (Log in, Anmelden, Iniciar sesión, Accedi, Entrar): le altre cinque
  lingue dicevano «connessione di rete». Il pulsante dell'amministrazione di Discord che collega il bot diventa «Collega
  il bot».

## [1.2.27] — 2026-10-05

Il bot Discord passa alla **versione 0.2.4**: le immagini viaggiano tra il forum e Discord nei due sensi (vedi il suo
registro delle modifiche). La versione 0.2.3 continua a funzionare.

### Aggiunto

- **Un'immagine inviata su Discord compare nel forum** (con il bot Discord 0.2.4): il bot la conserva sul sito,
  verificata come un'immagine incollata nell'editor, invece di un semplice link a Discord. L'API guadagna per questo
  l'endpoint `POST /api/v1/forum/images`.
- **La convalida via email delle iscrizioni** (*Impostazioni → Gestione registrazioni*, casella «Convalida via e-mail»,
  disattivata per impostazione predefinita): il nuovo membro riceve un link, valido due giorni. Non può accedere prima
  di averlo aperto; se ci prova, gli viene inviato un nuovo link. Questa funzione, ereditata da NeoFrag, era rimasta
  incompiuta e non aveva alcuna impostazione nell'amministrazione.

### Corretto

- **Il testo accentato non compare più codificato.** Un titolo, un nome utente, un'etichetta o il nome del sito che
  contenessero «é» o «—» potevano comparire come `&eacute;` o `&mdash;`: nelle conversazioni, nel Negozio, nelle
  Donazioni, nell'indice di un articolo, nei feed RSS, nell'amministrazione… Questi testi si mostrano ora tutti allo
  stesso modo, e due controlli automatici impediscono all'errore di tornare (uno legge il codice, l'altro le pagine del
  sito). I suggerimenti delle menzioni e della ricerca, gli oggetti delle email, i dati letti dai motori di ricerca (nome
  del sito, titolo e autore di un articolo) e i webhook ricevono anch'essi testo semplice.
- **L'iniziale di un avatar** mostrava «&» per un nome che comincia con una lettera accentata (amministrazione, pagina
  di una campagna di donazioni).
- **Iscriversi con Discord, GitHub o Google richiede di accettare il regolamento**, quando il sito ne ha uno: una
  schermata lo mostra, e l'account viene creato solo una volta spuntata la casella — come con il modulo di iscrizione,
  che questi account aggiravano.
- **Il menu non porta più a un modulo assente**: il link di un modulo non installato o disattivato (Forum, Galleria,
  Notizie…) restava nel menu — un clic, una pagina non trovata. Sparisce, e ritorna quando il modulo viene riattivato.
  Un link a una pagina personalizzata pubblicata resta; uno a una pagina non pubblicata sparisce anch'esso.
- **Su un sito senza il modulo Eventi**, la pagina delle Squadre non scrive più un avviso nel registro degli errori.
- **Il pulsante ☰ dell'amministrazione funziona sui grandi schermi**: vi si mostrava senza fare nulla; ora chiude il
  menu laterale, e lo riapre, ricordando la scelta. Su un telefono, apre il menu come prima.
- **L'avatar dell'autore di un messaggio del forum è più grande**: 80 px (36 px su un telefono), invece dei 40 px che
  gli dava ogni tema.
- **Un'immagine in una firma, o nella descrizione di un album della galleria, viene mostrata**: la pagina mostrava il
  codice HTML (`<p><img …></p>`) invece dell'immagine. Le firme e le descrizioni già salvate si mostrano senza doverle
  reinserire.
- **La versione testuale delle email è leggibile**: i paragrafi erano attaccati, gli accenti codificati, e un link
  finiva seguito dalla parola successiva — inutilizzabile in un client di posta che mostra solo testo.
- **Un modulo manomesso non fa più bloccare la pagina**: una casella inviata in una forma che nessun browser invia mai
  viene ignorata, come una casella non spuntata.
- **Su un sito demo, l'account amministratore creato all'installazione resta nascosto al passaggio del mouse e nello
  storico di moderazione**, come sulla sua pagina: la scheda del membro mostrata al passaggio del mouse dava il suo
  nome, i suoi gruppi e le sue date di visita, e lo storico di moderazione il suo nome.

## [1.2.26] — 2026-10-04

Il bot Discord passa alla **versione 0.2.3**: non scrive più un avviso di discord.js a ogni risposta privata (vedi il
suo registro delle modifiche). La versione 0.2.2 continua a funzionare.

### Aggiunto

- **Il regolamento e il messaggio di benvenuto si traducono lingua per lingua**: *Impostazioni → Gestione
  registrazioni* ha una scheda per lingua del sito, e ogni visitatore legge il regolamento — e riceve il messaggio —
  nella lingua della pagina. Una lingua che non ha ancora il suo testo mostra il testo comune: un sito che ne aveva uno
  solo lo conserva per tutte le sue lingue, nulla cambia finché non viene tradotto.
- **Il registro delle modifiche esiste in inglese** (`CHANGELOG.en.md`; la versione francese resta il riferimento).
- **Ogni addon ha ora la sua miniatura, nello stesso formato (960 × 600)**: i moduli API e Discord non ne avevano, i
  quattro temi del catalogo conservavano un'immagine più vecchia e più piccola, e tre widget del nucleo non ne avevano
  in *Temi & addon*. Alcune mostrano ancora un addon vuoto o non configurato. Un nuovo addon non può più essere
  pubblicato senza la sua.

### Modificato

- **Le descrizioni del catalogo dicono cosa fa ogni addon**: trenta di esse stavano in poche parole o si presentavano
  solo come «gaming». Ora descrivono cosa fa davvero l'addon, e per chi è, in tutte e sei le lingue; cinque erano
  perfino sbagliate (il widget Squadre non mostra membri, il widget Download mostra i file più scaricati…).
- **Il modulo Eventi si chiama «Eventi»**, non più «Eventi gaming»: nessun titolo del catalogo porta più la parola
  «gaming».
- **Il modulo Pagamenti richiede il modulo Gamification**, che accredita i punti e i giorni VIP acquistati: senza di
  esso, un pagamento veniva incassato e non si accreditava nulla. All'installazione del sito, scegliere Pagamenti
  aggiunge Gamification; dal marketplace, va installato prima. Se manca o viene disattivato in seguito, le vendite si
  chiudono e l'amministrazione lo dice; un pagamento ricevuto non viene segnato come trattato, e Stripe lo invierà di
  nuovo.

### Corretto

- **Un'immagine incollata (Ctrl+V) o trascinata nell'editor di testo viene salvata** — risposta nel forum, commento,
  pagina dell'amministrazione, sala dello staff dei messaggi privati: compariva rotta, poi spariva alla pubblicazione.
  Ora viene inviata al sito e resta nel messaggio. È riservato ai membri connessi: un'immagine JPEG, PNG, GIF o WebP di
  5 MB al massimo. Il sito salva una nuova copia, che toglie le informazioni nascoste di una foto (dove è stata scattata,
  per esempio). Oltre i 2000 pixel di lato, l'immagine viene ridotta; oltre gli 8192 pixel di lato o circa 12,6 milioni
  di pixel, viene rifiutata. Una GIF animata diventa un'immagine fissa. Sul sito demo, il caricamento di immagini resta
  chiuso.
- **Il messaggio di benvenuto si mostra pulito**: scritto nell'editor ricco, arrivava nei messaggi con i tag in vista
  (`<h3>`, `<p>`…). Ora viene trasformato in testo (titoli, elenchi puntati o numerati, link resi cliccabili), e il suo
  titolo non mostra più `&eacute;` al posto di una lettera accentata.
- **Il messaggio di benvenuto viene inviato anche quando qualcuno si iscrive con Discord, GitHub o Google**: lo inviava
  solo il modulo di iscrizione.
- **Il codice QR dell'autenticazione a due fattori viene mostrato**: la schermata di attivazione (*Sicurezza
  dell'account → Attiva 2FA*) mostrava il codice dell'immagine in un campo di testo invece dell'immagine da scansionare
  — i moduli non conoscevano questo tipo di campo e lo trattavano come testo.
- **I loghi del widget Partner portano al sito del partner** e contano la visita: portavano a una pagina inesistente
  dalla 1.0.0, e il contatore «Visite» non si muoveva più. Anche la pagina dei Partner passa dalla visita contata.
- **I pulsanti «Paga» (Pagamenti) e «Acquista» (Negozio) funzionano**: chiamavano un indirizzo inesistente.
- **Iscriversi alla newsletter dal widget funziona**: l'indirizzo scritto si perdeva per strada. Il link di conferma
  dell'email era relativo (inutile in un client di posta), come il link di disiscrizione delle campagne: entrambi sono
  ora indirizzi completi. E se l'email non può essere inviata, l'iscrizione viene annullata e il visitatore avvisato,
  invece di leggere «inviato».
- **I link nelle email del forum e della messaggistica** (menzione, abbonamento, conversazione) sono indirizzi completi:
  relativi, non portavano da nessuna parte.
- **I sondaggi seguono l'impostazione «Mostra i risultati»** (dopo aver votato, alla chiusura, mai) — sulla loro pagina
  come nel loro widget, che li mostrava sempre. I gestori li vedono sempre, con una nota.
- **I widget seguono le regole del loro modulo**: il widget Gallerie non mostra più album in bozza, programmati, nel
  cestino o riservati a un gruppo; il widget Forum, nessun estratto dalle categorie VIP né righe vuote per i messaggi
  eliminati; il widget Eventi e il calendario, nessun evento programmato prima della sua ora; il widget Reclutamento
  segue «Nascondi offerte non disponibili».
- **Un link a una pagina del sito, inserito nell'editor, conserva il suo indirizzo completo**: l'editor lo accorciava in
  un percorso (`../../…`) che funzionava solo dalla pagina di modifica — mostrato altrove, non portava da nessuna parte.
- **La roadmap** (`ROADMAP.md`) dice che il codice è pubblicato dal 4 ottobre 2026.

### Sicurezza

- **Due vecchi indirizzi tecnici della messaggistica mostravano i messaggi senza proteggerli**: un messaggio contenente
  codice avrebbe potuto eseguirsi lì nel browser. Nessuna pagina li usava più, e l'accesso alla conversazione era già
  richiesto; ora mostrano i messaggi come la conversazione stessa.
- **I pulsanti Facebook e Twitter della pagina dei Partner non seguono più un link `javascript:`** inserito
  nell'amministrazione: questi indirizzi vengono filtrati e protetti, come lo era già il sito web del partner.
- **Il pulsante «Acquista» del Negozio e il pulsante «Paga» controllano ora che la richiesta venga davvero dal sito**:
  una pagina malevola non può più far acquistare un articolo a un membro connesso a sua insaputa.
- **Un acquisto non può più essere contato due volte**: l'addebito dei punti, lo stock e il possesso vengono controllati
  e scritti in una sola transazione — due acquisti simultanei non vendono più due volte l'ultimo articolo, né addebitano
  due volte un saldo. Anche i punti guadagnati e i giorni VIP si sommano in un colpo solo: due guadagni nello stesso
  istante non si sovrascrivono più. Un articolo da 0 punti si può ora ottenere senza addebito (veniva rifiutato, «punti
  insufficienti»).
- **Le iscrizioni alla newsletter hanno un limite di frequenza**: 5 richieste all'ora per indirizzo IP, 3 al giorno per
  indirizzo email — abbastanza per fermare l'invio in massa di email di conferma a indirizzi scelti.
- **Un sondaggio i cui risultati sono nascosti non mostra più il suo numero totale di voti** nell'elenco.

## [1.2.25] — 2026-10-04

Il bot Discord passa alla **versione 0.2.2**: i messaggi del forum che inoltra perdono tutti i loro tag HTML, anche
quelli annidati (vedi il suo registro delle modifiche). La versione 0.2.1 continua a funzionare.

### Corretto

- **La finestra del marketplace propone anche i widget che nessun modulo fornisce** (Informazioni, Server di gioco,
  Lettore di feed, Effetto stagionale, Gruppo Steam, Server TeamSpeak 3, Stato live): mostrava solo moduli e temi, e
  questi sette widget si potevano installare solo con «Aggiungi», con l'archivio in mano. Un widget già fornito da un
  modulo (quello del Forum, per esempio) non vi compare due volte.
- **La guida all'amministrazione nomina il pulsante che salva un nuovo indirizzo del sito** con la sua etichetta,
  *Usa*, seguita dall'indirizzo — e non più con un falso indirizzo `https://…` che un lettore ha preso per un link.

### Sicurezza

- **I flussi di verifica del repository ricevono solo un token in sola lettura** (`permissions: contents: read`): non
  hanno nulla da scrivere, quindi un passaggio compromesso non potrebbe cambiare nulla. Segnalato dall'analisi del
  codice di GitHub quando i repository sono stati aperti al pubblico.
- **Il widget Video accetta, per un elemento della sua playlist, solo un indirizzo `http(s)` o un percorso del sito**:
  un indirizzo `javascript:` o `data:` viene ignorato al clic. Il lettore non eseguiva nulla, ma un indirizzo del genere
  non aveva nulla da fare lì. Segnalato dalla stessa analisi.

### Aggiunto

- **Tre test in più nel flusso `installation.yml`**: `check-extensions` installa ciascuno degli addon del marketplace
  pubblicato su un sito che ha solo il nucleo, tramite la finestra del marketplace, e tramite «Aggiungi» ciò che questa
  non propone; `check-prerequis-absents` toglie a turno ogni estensione PHP richiesta, e controlla che l'installer
  guidato e quello da riga di comando dicano quale manca; il pacchetto pubblicato viene installato tramite l'installer
  guidato su un hosting condiviso simulato (Apache senza le funzioni che avviano un programma, `open_basedir`, 128 MB).
  E i link di tutti i documenti vengono verificati (lychee).

## [1.2.24] — 2026-10-04

**NeoFrag Reborn si apre su GitHub.** Il codice del CMS ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)),
gli addon à la carte ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) e il bot Discord
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) vi sono pubblicati con la storia delle loro
versioni — dalla 1.0.0 per il CMS e gli addon, dalla 0.1.0 per il bot: il codice di ogni versione, le sue note, e i
pacchetti dalla 1.2.23 in poi.

### Modificato

- **I database indicati sono quelli che vengono provati**: ogni versione si installa e supera tutta la sua suite di
  test, con PHP 8.2 e 8.5, su MySQL 5.7, 8.0 e 8.4 e su MariaDB 10.5, 10.6, 10.11, 11.4 e 11.8 — la guida
  all'installazione elenca queste versioni, e non più «5.7+» e «10.5+».
- **Le guide dicono dove trovare il progetto**: il pacchetto di installazione e l'archivio del bot nella loro pagina
  delle versioni, gli addon à la carte e il loro catalogo in quella di `extensions`, il codice per chi vuole
  contribuire. Nel wiki, i riferimenti a un documento del repository (deployment, architettura, strumenti) portano a
  quel documento su GitHub, invece di conservarne solo il titolo.

### Corretto

- **Le impostazioni dei moduli si aprono di nuovo**: la finestra «Configurazione» dei moduli Forum, Reclutamento,
  Notizie, Galleria, Eventi, Calendario e Articoli rispondeva «errore 500» a ogni apertura, su ogni sito, dalla 1.2.0:
  il campo «numero» rifiutava un intero (il suo passo, e il valore letto dal database) da quando il codice è passato
  alla tipizzazione rigorosa.
- **Squadre: l'opzione «Mostra le partite giocate» si può di nuovo togliere.** Il salvataggio delle impostazioni
  memorizzava la parola «Array» invece di un sì o di un no, che veniva sempre letto come «sì».
- **Le impostazioni di un servizio di autenticazione mai configurato** (Google, Discord, GitHub) si aprono senza scrivere
  otto avvisi nel registro.
- **Il cruscotto e le statistiche di un sito senza Articoli, Bug tracker o Forum** non scrivono più avvisi nel registro
  a ogni apertura: le schede di questi moduli compaiono solo se sono installati, e nessuna tabella assente viene più
  interrogata.
- **L'installer guidato rilegge la configurazione che ha appena scritto**: su un hosting la cui cache di PHP non
  ricontrolla i file, un secondo tentativo del passo «Database» leggeva ancora il vecchio `config/db.php`.
- **Un sito messo online da git e installato con l'installer web funziona**: l'installer non scriveva
  `config/neofrag.php` (il pacchetto lo porta, un clone ha solo il suo modello), e ogni pagina rispondeva «errore 500»
  senza una sola riga nel registro.
- **L'esempio `nginx.conf` si avvia**: la regola di TinyMCE, senza virgolette, faceva rifiutare a nginx tutta la
  configurazione («missing closing parenthesis»). I suoi reindirizzamenti non portano più il parametro interno
  `request_url`.
- **I file caricati vengono serviti con Apache e PHP come modulo**: la protezione di `upload/` usava una direttiva
  vietata in un `.htaccess`, e tutta quella cartella — avatar, galleria, mediateca — rispondeva «errore 500».
- **Con Caddy, i file caricati che non sono immagini** (un PDF della mediateca) vengono serviti: l'esempio serviva solo
  immagini.
- **`/index.php` porta alla pagina iniziale** (reindirizzamento permanente) invece che a una pagina non trovata; e un
  indirizzo con un'estensione che nessuna pagina serve (`/newsletter/.env`, che i robot provano) risponde 404 senza
  scrivere un'anomalia nel registro.
- **`tools/check-all.php` con PHP 8.2**: ogni controllo vi contava come fallito, anche quando passava — il codice di
  uscita veniva letto una volta di troppo, cosa che PHP permette solo dalla 8.3. Per chi contribuisce con PHP 8.2.

### Sicurezza

- **L'installer guidato non si riapre più su un sito installato il cui database non risponde.** Se il blocco
  `install/db.txt` era sparito, un'interruzione temporanea del database riapriva l'installer, il cui passo «Database»
  riscrive `config/db.php`: un visitatore poteva allora dirottare il sito verso il proprio database. Il sito mostra ora
  «temporaneamente non disponibile». E il passo «Amministratore» si rifiuta di creare un account appena il sito ne ha
  uno.
- **Apache esegue ora solo l'`index.php` della radice**: la regola di riscrittura lasciava eseguire direttamente
  qualsiasi file `index.php` di una sottocartella (più di ottanta controller interni).

### Aggiunto

- **Quattro controlli in più**: `check-assistant` percorre l'installer guidato dall'inizio alla fine, come un
  visitatore, per ogni profilo (schermate, rifiuti, account creato, accesso con la password inserita, registro
  silenzioso); `check-reglages` apre la schermata delle impostazioni di ogni addon, la salva così com'è e la riapre;
  `check-serveur-web` prova Apache, nginx e Caddy, con le configurazioni incluse, collocando delle sonde (cartelle e
  file vietati, script che non devono essere eseguiti, riscrittura, intestazioni di sicurezza); `check-nouveau-venu`
  segue alla lettera il README e la guida per i contributori su una macchina pulita. I flussi `installation.yml` e
  `nouveau-venu.yml` li rieseguono ogni settimana.
- **I comandi del README funzionano su una macchina nuova**: il suo blocco «Sviluppare» finiva con una suite di test
  che falliva per mancanza di un database di test; ora rimanda alla guida per i contributori, che crea quel database
  (`php tools/prepare-test-db.php`).

## [1.2.23] — 2026-10-04

Il bot Discord passa alla **versione 0.2.1**: nel suo funzionamento non cambia nulla — diventa un progetto a sé, con la
sua licenza, il suo NOTICE e il suo registro delle modifiche. Installarlo serve solo a chi vuole questi file; la 0.2.0
continua a funzionare.

### Modificato

- **La mappa del sito non elenca più una sezione vuota**: notizie, reclutamento, una FAQ, squadre… senza nulla da
  mostrare non vengono più offerte ai motori di ricerca, che vi troverebbero solo una pagina «ancora niente». La sezione
  torna da sola con il suo primo contenuto. Il contatto, il libro degli ospiti, la newsletter, la web radio e le
  donazioni restano elencati: hanno sempre qualcosa da offrire.
- **I backup non si accumulano più**: ogni aggiornamento ne fa uno completo (16 MB e oltre), e nulla li toglieva. Il
  sito conserva sempre i cinque più recenti, e toglie gli altri dopo trenta giorni; un file messo a mano in `backups/`
  non viene mai toccato.
- **L'installazione controlla tutto ciò di cui il CMS ha bisogno.** L'installer non controllava né `openssl` (senza,
  salvare un segreto — server di posta, captcha, autenticazione a due fattori — fallisce con un errore), né `fileinfo`
  (senza, ogni caricamento di file viene rifiutato), né `iconv` (il codice QR dell'autenticazione a due fattori), né la
  cifratura delle password Argon2, senza la quale la creazione dell'account amministratore falliva. Ora li controlla e
  si rifiuta di proseguire se ne manca uno; `install/cli.php` richiede esattamente lo stesso elenco, versione di PHP
  compresa, invece di due estensioni. Il *Monitoraggio* e il cruscotto dell'amministrazione mostrano lo stesso elenco.
  Serve PHP 8.2 o successivo; il prodotto è provato fino a PHP 8.5.
- **nginx e Caddy**: il pacchetto fornisce finalmente gli esempi di configurazione che le guide promettevano
  (`nginx.conf`, `Caddyfile`), generici, e che bloccano le stesse cartelle e gli stessi file sensibili dell'`.htaccess`
  di Apache — il vecchio esempio di nginx lasciava raggiungibili `install/`, `tools/`, `tests/` e `docs/`, e l'estratto
  della guida al deployment proteggeva solo tre cartelle su otto.
- **Ogni addon dice chi l'ha scritto.** Gli addon del NeoFrag originale conservano i loro autori, Michaël BILCOT e Jérémy
  VALENTIN — i connettori di accesso e le lingue, che non firmavano nulla, ora li nominano, e le traduzioni inglesi
  originali citano di nuovo i loro traduttori, FoxLey ed eResnova. I porting citano il loro autore: l'Orologio
  (ArkaNiX), il tema Extend (Chewbaka), il gestore di file (HiddenBlob, HiddenCMS) e Donazioni (HiddenBlob, da majiid).
  Il resto è firmato «NeoFrag Reborn». I temi Nebula, Blockcraft, Forge e Granite passano alla LGPL, come il prodotto;
  Extend conserva la licenza del suo autore (CC BY-NC-SA). La licenza di ogni addon LGPL rimanda al testo ufficiale
  della LGPL.
- **HSTS**: l'intestazione impostata dall'`.htaccess` non si estende più ai sottodomini e non dichiara più il sito
  idoneo alla lista di precaricamento dei browser (`includeSubDomains` e `preload` tolti): impostati per impostazione
  predefinita, vincolavano per un anno tutti i sottodomini di chi aveva installato il CMS.

### Corretto

- **Un sito installato dal pacchetto non registrava alcun errore.** Il pacchetto non conteneva la cartella `logs/`, e
  nulla la creava: PHP scriveva i suoi errori nel registro del server, *Monitoraggio → Registro degli errori* restava
  vuoto e diceva la cartella «non scrivibile». Il pacchetto fornisce ora `logs/`, `cache/` e `backups/` con la loro
  protezione, e un sito che non ha `logs/` la ricrea da sé: basta aggiornare per riparare un sito già installato.
- **La documentazione del wiki non ha più link morti.** Tre riferimenti delle sue pagine a documenti che il sito non ha
  (il manuale degli strumenti, una parte della guida al deployment) portavano a una pagina di errore; ora conservano solo
  il loro testo. La guida al marketplace non menziona più addon «di terze parti» che non esistono, né procedure
  riservate al server del progetto.
- **Impostazioni → Indicizzazione** si rifiutava di salvare appena una descrizione, uno slogan o un titolo per i motori
  di ricerca conteneva molte lettere accentate: ognuna contava come più caratteri, e una descrizione portoghese di 139
  caratteri superava il limite di 160. Un codice di verifica di Google o Bing incollato come tag intero veniva rifiutato
  allo stesso modo. I reindirizzamenti e l'indicizzazione per contenuto seguono la stessa regola.
- **Il resoconto SEO** riconosce una proprietà di Google Search Console verificata tramite il DNS del dominio (invece di
  dirla «non dichiarata»), e consiglia di inviare la mappa del sito che riunisce tutte le lingue, `/sitemap.xml`,
  piuttosto che quella della sola lingua mostrata. Quando Google è verificato, non dice più Bing «non dichiarato»: Bing
  Webmaster Tools può importare un sito da Google Search Console senza mettere nulla sul sito, quindi il resoconto non
  può vederlo.
- **`humans.txt`, `robots.txt` e la chiave IndexNow**: un indirizzo mancante — un `humans.txt` vuoto, una chiave
  sconosciuta — risponde un semplice «non trovato», senza scrivere un errore nel registro del sito per ogni robot che lo
  richiede.

---

## [1.2.22] — 2026-10-03

### Aggiunto

- **L'indicizzazione per contenuto**: un pulsante «Indicizzazione» nella scheda di modifica di una notizia, di un
  articolo del Blog, di una pagina o di una pagina del wiki dà, per ogni lingua, il titolo e la descrizione mostrati dai
  motori di ricerca e dalle anteprime di condivisione. Lasciati vuoti, restano automatici.
- **Impostazioni → Indicizzazione → Resoconto SEO**: ciò che vede un motore di ricerca, misurato sul sito — le pagine
  della mappa del sito per modulo, i testi di ogni lingua, l'immagine di condivisione, Google e Bing, `robots.txt`, la
  manutenzione —, con, per ogni punto, il link a ciò che lo corregge.
- **Reindirizzamenti**: un vecchio indirizzo porta al nuovo (301) invece di rispondere «Pagina non trovata», con il
  numero di visite che riceve ancora. Una pagina o una pagina del wiki rinominata lascia da sola il proprio; se ne
  possono aggiungere altri a mano, per l'indirizzo di un vecchio sito per esempio.
- **Avvisare i motori (IndexNow)**: attivato in Impostazioni → Indicizzazione, il sito segnala, in pochi minuti, ogni
  pagina che compare, cambia o scompare, a Bing, Yandex, Seznam, Naver, Yep e Amazon. Google non vi partecipa: per
  Google, la mappa del sito resta la strada. Le pagine di ogni modulo elencato nella mappa del sito vengono segnalate,
  senza nulla da aggiungere; serve l'attività pianificata del sito.

### Corretto

- **Moderazione**: nell'amministrazione, un moderatore senza il diritto «conversazioni private» poteva aprire la
  segnalazione di un messaggio privato, e questo accesso non veniva registrato nel log di audit. Chi segnala un messaggio
  privato vede finalmente l'avviso a lui destinato.
- **Messaggi**: il messaggio inviato alla sala dello staff dall'amministrazione rispondeva sempre «Nessuna sala dello
  staff configurata».
- Niente più avvisi PHP nel registro quando la segnalazione, il messaggio o il membro interessato erano stati
  eliminati.

---

## [1.2.21] — 2026-10-03

### Aggiunto

- **Impostazioni → Indicizzazione**: per ogni lingua, lo slogan del titolo della pagina iniziale e la descrizione
  mostrata dai motori di ricerca; un'immagine di condivisione (1200 × 630) per le anteprime su Discord, X o Facebook; i
  codici di verifica di Google Search Console e Bing Webmaster Tools.
- **Una mappa del sito per lingua**, riunite nella radice (`/sitemap.xml`): ogni modulo vi elenca le sue pagine
  pubbliche — il blog, il forum e le sue discussioni, il wiki, la galleria, il calendario, gli eventi… —, solo quelle che
  un visitatore può leggere e che esistono nella lingua della mappa.
- Nella pagina iniziale, **dati strutturati** che descrivono il sito, il suo logo e i suoi social network.

### Modificato

- **I profili dei membri e l'elenco dei membri** non vengono più offerti ai motori di ricerca, né i risultati della
  ricerca: poco contenuto, e un membro non dovrebbe finire su Google senza averlo scelto.

### Corretto

- **La mappa del sito** conteneva solo indirizzi relativi — ignorati dai motori di ricerca —, in una sola lingua, senza
  il wiki né il forum; `robots.txt` la annunciava con un indirizzo relativo.
- **Ogni pagina del Blog** dichiarava `/articles/…`, un reindirizzamento, come suo indirizzo canonico; la pagina
  iniziale, `/index`. I link tra le lingue sono completi, con la versione predefinita (`x-default`).
- **Il titolo della pagina iniziale** non ripete più il nome del sito («NeoFrag Reborn | NeoFrag Reborn»).
- **Google Analytics**: l'impostazione accettava solo il vecchio formato `UA-…`, abbandonato da Google nel 2023; accetta
  gli identificativi attuali, `G-…`.
- **Lingua del browser**: un visitatore il cui browser annuncia solo `de-DE` arriva in tedesco, e non nella lingua
  predefinita del sito.
- **Il selettore di lingua** e l'avviso «Questo contenuto non esiste in …», dal Blog, portavano al suo vecchio
  indirizzo.

### Sicurezza

- **Gli indirizzi completi** dell'intestazione della pagina (canonico, lingue, condivisione) vengono costruiti a partire
  dall'indirizzo del sito, e non più dall'intestazione `Host` della richiesta, che chiunque può falsificare.

## [1.2.20] — 2026-10-03

### Aggiunto

- **Un profilo di installazione «Associazione / club»**: notizie, forum, gallerie, calendario, donazioni, newsletter,
  wiki e FAQ, senza l'attrezzatura esport. Si aggiunge a *Completo*, *Gaming / eSport*, *Community* e *Solo nucleo*,
  nelle sei lingue dell'installer guidato.

### Corretto

- **L'esportazione dei membri in formato JSON** (GDPR, articolo 15) scrive ora la sua etichetta «Esportazione dei dati
  dei membri»: usciva vuota.
- **Blog**: nella pagina di un autore, «Vedi il profilo» portava a una pagina non trovata.

### Sicurezza

- **L'istantanea della demo non porta più alcun segreto.** Il file che ripristina la demo, fornito con il pacchetto
  della demo, avrebbe copiato le credenziali dell'invio di email, la chiave del servizio di traduzione automatica del
  NeoFrag originale e le chiavi dei widget Twitch e TeamSpeak da un sito in cui fossero state inserite; portava ancora
  la riga, vuota, della chiave segreta del captcha. Nessuna chiave era trapelata. Un controllo verifica ora l'elenco
  rispetto al codice, e il file fornito stesso.

### Documentazione

- NeoFrag Reborn si presenta come **il CMS open source per le community, dal gaming alle associazioni**; la guida ai
  concetti dice quale profilo scegliere per il sito di un'associazione o di un club.

## [1.2.19] — 2026-10-02

### Aggiunto

- **Un captcha moderno, attivo dall'installazione: ALTCHA.** È ospitato dal sito stesso — nessun account, nessuna
  chiave, nessun cookie, nessun servizio di terze parti. Il visitatore vede una casella che si spunta da sola mentre
  compila il modulo: il suo browser esegue un piccolo calcolo, che il server verifica. Protegge il modulo di contatto,
  l'iscrizione e il reclutamento. All'aggiornamento, un sito senza chiavi reCAPTCHA passa ad ALTCHA; un sito che ne
  aveva conserva reCAPTCHA. ALTCHA rende costoso l'invio in massa per un robot; contro un attaccante determinato, i
  fornitori seguenti, che aggiungono i propri controlli, sono più solidi.
- **In alternativa, Cloudflare Turnstile, hCaptcha o Google reCAPTCHA v2**, in *Impostazioni → Sicurezza anti-bot*, con
  il link alla console di ciascuno. La chiave segreta è cifrata e non viene più mostrata. Senza le sue due chiavi, un
  fornitore viene sostituito da ALTCHA invece di lasciare il modulo aperto.
- Il captcha e i suoi messaggi esistono in tutte e sei le lingue.

### Corretto

- **reCAPTCHA**: la chiave segreta veniva inviata nell'URL di verifica; ora viene inviata nel corpo della richiesta,
  come richiede Google. Il link superato alla sua console è sostituito, e l'indirizzo del visitatore trasmesso segue la
  regola usata dal resto del sito, che tiene conto di un proxy dichiarato fidato.
- Quando la verifica anti-bot non è stata completata, il messaggio è scritto per intero sotto il captcha, invece di
  un'icona solitaria il cui fumetto non compare su uno schermo tattile.

### Sicurezza

- **Superare il captcha una volta non esonera più dal superarlo di nuovo.** Dal NeoFrag originale, un captcha superato
  esonerava dal superarlo di nuovo per tutti gli invii successivi dello stesso modulo, fino alla fine della sessione: un
  robot che risolveva una sola sfida poteva poi inviare senza limiti. L'esonero vale ora solo per un unico nuovo invio,
  dopo un altro errore nel modulo.
- Una soluzione ALTCHA si può usare una sola volta: ripresentarla viene rifiutato.
- La politica di sicurezza delle pagine non permette più Google per impostazione predefinita: solo gli indirizzi del
  fornitore di captcha scelto, e nessuno con ALTCHA.

## [1.2.18] — 2026-10-02

### Corretto

- **Il riordino per trascinamento funziona di nuovo**: le categorie e i forum del forum, le squadre e i loro ruoli, i
  partner, i gruppi di membri, le lingue e i connettori di accesso, e le righe, le colonne e i widget dell'Editor live.
  La posizione arrivava dal browser come testo, e lo spostamento si fermava su un errore — notato nel registro della
  demo.
- **Sulla demo, l'Editor live** apre di nuovo le impostazioni di un widget, e ogni modifica vi riceve il messaggio
  «Azione disattivata sul sito di dimostrazione.» invece di un errore silenzioso. Le disposizioni non vi si possono
  cambiare: un widget HTML scritto da un visitatore verrebbe mostrato a tutti gli altri.

### Documentazione

- **La guida all'installazione via FTP** nomina il pacchetto giusto (`neofrag-reborn-public-<version>.zip`), descrive
  l'aggiornamento con il pulsante, e non chiede più di eseguire uno strumento assente dal pacchetto: le migrazioni si
  applicano da sole alla prima visita, demo compresa.
- **Le guide per gli sviluppatori** descrivono come mettere un indirizzo inserito in un link solo dopo averlo
  verificato (`nf_url_sure()`), il blocco del sito demo, gli elenchi divisi in pagine, i contatori e le date; le cifre
  del README e delle guide seguono il codice (62 moduli, 40 widget).

## [1.2.17] — 2026-10-02

### Sicurezza

- **Sulla demo, la scheda File del Monitoraggio** mostrava ancora l'albero reale dell'installazione (nomi di cartelle e
  file, senza il loro contenuto): ora mostra un albero di esempio, come il gestore di file.

## [1.2.16] — 2026-10-02

### Sicurezza

- **Un link malevolo non viene più eseguito dall'indirizzo di una segnalazione.** Il membro che segnala fornisce questo
  indirizzo, che viene poi mostrato al moderatore: un indirizzo «javascript:» veniva eseguito da lui al clic. La stessa
  protezione vale per i link di un feed RSS (widget), di un carosello, dell'elenco link, dei partner e per ogni
  indirizzo che il sito costruisce: passano solo gli indirizzi web, email, telefono o interni al sito.
- **Eliminare un ruolo, creare la chiave del bot Discord, chiudere un annuncio o chiudere una discussione del forum** si
  faceva con un semplice link: bastava un link malevolo per far scattare l'azione per un amministratore, un autore o un
  moderatore connesso. Questi link portano ora un token di sicurezza.
- **La demo blindata.** Il suo account condiviso (demo / demo) è amministratore: poteva attivare la modalità debug per
  tutti i visitatori, sfogliare e leggere i suoi file, il registro degli errori e phpinfo, vedere la chiave
  dell'attività pianificata, cambiare le impostazioni del sito, creare chiavi API, bannare definitivamente indirizzi IP
  o membri, deviare l'indirizzo PayPal delle donazioni, e cancellare immagini che il ripristino non poteva riportare.
  Su una demo, ogni azione verso una zona bloccata viene rifiutata prima di raggiungere la sua schermata, le schermate
  sensibili mostrano un avviso, il gestore di file mostra solo un albero di esempio, nessun file viene eliminato, e il
  ripristino rimette anche mappe e modalità di gioco, ruoli delle squadre, notifiche, punti e permessi.

### Corretto

- **Un file rifiutato al caricamento** (estensione vietata, o demo) faceva bloccare la pagina (errore 500), per esempio
  cambiando il proprio avatar: ora il campo dice perché.
- **Le statistiche su un periodo smisurato** (secoli, ora per ora) esaurivano la memoria del server: il periodo e il
  numero di punti sono limitati.
- **L'esportazione dei membri** scriveva un avviso di PHP 8.5 per riga nel registro.
- **Sulla demo**, il banner verde copriva le notifiche, e l'account di emergenza compariva nell'elenco dei giocatori di
  una squadra e nell'attività del cruscotto.
- **Una sottile linea bianca correva lungo il bordo destro dell'editor di testo in modalità scura** (commenti,
  messaggistica, amministrazione): l'editor dipingeva di bianco la sua zona di scrittura, e quel bianco debordava di una
  frazione di pixel.

## [1.2.15] — 2026-10-02

### Modificato

- **La ricerca rapida dell'amministrazione (Ctrl+K), rifatta**: sezioni in maiuscoletto discreto, allineate alle icone,
  invece di titoli attaccati al bordo; il nome della sezione non viene più ripetuto alla fine di ogni riga; una lente
  nel campo; la parte trovata evidenziata. La ricerca ignora gli accenti (su un sito francese, «evenements» trova
  «Événements gaming») e cerca anche nelle sezioni («gaming» elenca i sei moduli di quella sezione).
- **La foto del membro nella barra laterale dell'amministrazione**, invece della prima lettera del suo nome utente, che
  resta per un account senza foto.

### Corretto

- **I tasti freccia, nella ricerca rapida**, spostavano una selezione invisibile: solo il mouse evidenziava una riga.
- **«Cosa porta questa versione»**, nella finestra di aggiornamento, apriva il registro delle modifiche in cima alla
  pagina, sulla versione precedente: ora porta alla versione annunciata.
- **Il riquadro dei commenti di un articolo del Blog** perdeva il suo margine interno e la linea sotto il suo titolo:
  «Commenti» e il contatore toccavano il bordo. Cinque temi applicavano a ogni riquadro annidato una regola pensata per
  l'intestazione dei riquadri trasparenti.

## [1.2.14] — 2026-10-02

### Modificato

- **La pagina Monitoraggio, in schede**: un riepilogo sempre visibile (versione e aggiornamento, PHP, backup, strumenti
  di diagnostica), poi *Panoramica*, *Backup*, *Diagnostica*, *Server e sicurezza* e *File*. Impilava una decina di
  schede in una colonna stretta, su quasi 2700 pixel; ogni scheda sta ora in uno schermo. La salute del sito è detta con
  sobrietà («Tutto a posto», «Punti da verificare», «Errori da correggere»).
- **I pulsanti di azione dell'amministrazione, uguali ovunque**: modifica, accesso e ordina in grigio neutro; elimina con
  contorno rosso, con un cestino. Cambiavano da una pagina all'altra — azzurro, verde acqua, pulsanti pieni, croci o
  cestini. Le regole di design dell'amministrazione sono descritte nella guida «Creare un modulo», e un controllo le fa
  rispettare.
- **I badge di stato, morbidi ovunque**: uno sfondo pallido e un testo più marcato dello stesso colore, come
  «Pubblicato». «Attivo» era scritto in bianco su verde pieno, «Built-in» in nero. Niente più pulsanti colorati pieni
  nelle righe degli elenchi (anteprima, duplica, ripristina…).
- **Il pulsante di creazione, nello stesso posto ovunque**: in cima alla scheda dell'elenco che alimenta. Otto pagine
  lo mettevano nella barra in alto, in fondo all'elenco o sopra la scheda (pubblicità, negozio, donazioni, pagamenti,
  carosello, squadre, giochi, backup del Monitoraggio).
- **Temi & addon, compatto**: una ricerca, il numero di estensioni di ogni tipo, filtri di tipo e di stato che si
  combinano («moduli inattivi»), e una vista a elenco più densa, mostrata per impostazione predefinita, accanto alla
  griglia. La pagina si apre sui moduli; allineava le circa 120 estensioni come grandi schede, su più di 10.000 pixel.
- **I modelli email, in una sola tabella**: i modelli raggruppati per famiglia (account, forum, messaggistica,
  moderazione, newsletter), con oggetto, lingue e stato su una riga. La pagina impilava una scheda per modello, su
  2000 pixel; ora è la metà.
- **Discord, in schede**: *Panoramica*, *Funzionalità*, *Configurazione del server*, *Canali e forum*, *Gruppi e ruoli*,
  *Ruoli temporanei* — si passa dall'una all'altra senza tornare alla pagina principale. Il registro del bot scorre
  dentro la sua scheda invece di allungare la pagina.
- **Le statistiche, più leggibili**: le date e l'intervallo stanno su una riga. Il grafico non ripete più nella sua
  legenda le caselle, che portano già il colore di ogni serie. Le sue curve non scendono più sotto zero e il suo asse si
  ferma ai limiti del periodo scelto. Le sue date seguono la lingua della pagina invece di quella del browser (su una
  pagina francese, «2 oct. 2025», non «Oct 2, 2025»). Oltre tre serie, solo linee invece di aree sovrapposte.
- **Permessi → Vista a matrice**: i moduli raggruppati per sezione, come nella barra laterale, in colonne. Erano
  allineati come una quarantina di grandi tessere, senza ordine.
- **Il log di audit, in pagine da 50**: allineava 200 righe in un solo blocco, su più di 5000 pixel.
- **Utenti: una ricerca** per nome utente o email, sopra l'elenco dei membri.
- **Il gestore di file dice cosa fanno i suoi pulsanti**: «Aggiungi file», «Crea una cartella», «Sposta», «Elimina»,
  invece di quattro pillole colorate con la sola icona. Le sue finestre hanno un «Annulla» neutro e un pulsante di
  conferma con un nome.
- **«Salva» sul pulsante che conferma una modifica**, ovunque. Venti moduli dicevano «Modifica» (forum, notizie, pagine,
  eventi, squadre, giochi, gallerie…).

### Corretto

- **Alcuni elenchi dell'amministrazione si fermavano alla loro prima pagina**, senza link al resto: i membri e i commenti
  oltre i primi 20, i riconoscimenti e i reclutamenti oltre i primi 10, così come «I miei abbonamenti» del forum dal lato
  del membro. Il contatore della scheda dava solo la pagina mostrata («20 membri» per 25).
- **Date inserite in inglese e in tedesco.** In inglese, il selettore scriveva il 2 ottobre come «10/02/2026 g:05 A»,
  precompilava un evento datato 30/09/2026 come «06/09/2028», e il 2 ottobre veniva salvato come 10 febbraio. In
  tedesco, una data mostrata come «02.10.2026» non veniva riletta al salvataggio. Le date brevi in inglese si scrivono
  ora giorno/mese/anno con un orologio a 24 ore, come nelle altre lingue («02/10/2026 14:05»); un test verifica, in
  tutte e sei le lingue, che una data mostrata venga riletta.
- **Niente più falsi allarmi di «file danneggiato» prima di un aggiornamento.** Il Monitoraggio confrontava i file del
  sito con l'elenco delle impronte dell'ultima versione pubblicata, anche quando il sito non era ancora a quel livello:
  tutto ciò che la nuova versione cambia compariva danneggiato (102 allarmi su un sito poco prima del suo passaggio alla
  1.2.13). Il controllo aspetta ora che il sito sia aggiornato, e lo dice.
- **Statistiche**: in tedesco, il grafico non si caricava mai; i pulsanti «7 giorni», «30 giorni», «90 giorni» e
  «1 anno» non facevano nulla, in nessuna lingua.
- **La data di modifica di una pagina del wiki** mostrava l'ora dell'ultima visita: il contatore di visite riscriveva la
  data di modifica. Stesso difetto sugli annunci.
- **Nella finestra di eliminazione del gestore di file**, «Annulla» ed «Elimina» erano due cestini rossi identici. I
  testi francesi del modulo avevano perso gli accenti («Dossier cree avec succes», «Element deplace») e contavano in
  «élément(s)».
- **Il cruscotto e il log di audit** mostrano ciò che è successo («Modalità debug attivata», «Impostazioni salvate») e
  non più identificativi tecnici (`monitoring.debogage.allume`).
- **La pagina Commenti dell'amministrazione** mostrava codice invece della data di ogni commento.
- **Un titolo accentato** non mostra più i suoi accenti come codice HTML nell'intestazione delle schede
  dell'amministrazione.
- **In modalità scura**, i testi colorati dell'amministrazione (avvisi, badge, messaggi di successo verdi) restavano
  scuri su sfondo scuro, quasi illeggibili.
- **Le date della moderazione** (sanzioni, segnalazioni, storico di un membro), **del cestino, dei backup, delle notifiche
  e del registro del bot Discord** sono mostrate nella lingua e nel fuso orario del visitatore («21/09/2026 22:54»), e
  non più come sono memorizzate nel database («2026-09-21 20:54:11»).
- **In francese, l'etichetta delle statistiche «Connexions de membres»** (accessi dei membri) era scritta male,
  «Connections de membres».
- **Le date seguono la lingua del visitatore**: il wiki, il Bug tracker, gli annunci, il libro degli ospiti, la
  newsletter, le conversazioni archiviate, il gestore di file e il widget degli eventi le scrivevano fisse — come sono
  nel database («2026-09-20 22:54») o alla francese anche in tedesco (ci si aspettava «02.10.2026»).

## [1.2.13] — 2026-10-02

### Aggiunto

- **Il registro degli errori, nell'amministrazione** (*Sistema → Monitoraggio → Registro degli errori*). Non servono
  più FTP né accesso al server per sapere cosa è fallito: gli errori del sito, raggruppati, dal più recente al più
  vecchio, ordinati per gravità, filtrabili per periodo o per parola. Il percorso di installazione, le password, le
  chiavi e i token sono mascherati sullo schermo, gli indirizzi email e IP in parte; il file si può scaricare e svuotare
  (il vecchio viene conservato accanto). Il Monitoraggio segnala gli errori delle ultime 24 ore, e una cartella `logs/`
  in cui il sito non riesce più a scrivere.
- **Gli strumenti di diagnostica si accendono dall'amministrazione** (*Monitoraggio → Diagnostica*), per un'ora, senza
  modificare `config/neofrag.php` via FTP:
  - **la modalità debug** — ciò che mostra, la barra in fondo alla pagina e il dettaglio degli errori, viene ora
    mostrato solo agli amministratori connessi; accesa, veniva mostrata a tutti i visitatori, query e dati di visita
    compresi;
  - **la traccia delle pagine**, che si può finalmente leggere nell'amministrazione: le ultime pagine servite, le loro
    query e la loro durata, con i valori sensibili mascherati;
  - **il controllo delle traduzioni**, con l'elenco dei testi mancanti per lingua e per estensione; la bandiera che
    mette davanti ai testi tradotti viene ora mostrata solo agli amministratori.
- **L'amministrazione ordinata in nove sezioni**: *Contenuto*, *Community*, *Animazione*, *Gaming*, *Sapere*, *Media*,
  *Diffusione*, *Supporto*, *Monetizzazione* (e *Sistema*). Le sei precedenti mescolavano il calendario con i media o il
  Bug tracker con la community, e tredici moduli finivano in «Altri moduli».
- **Il menu «Navigazione» dell'Editor live** segue le stesse sezioni, come sottomenu che si aprono, con una ricerca;
  allineava più di quaranta pagine una dopo l'altra.
- **L'indirizzo del sito si corregge dall'amministrazione** (*Monitoraggio → Indirizzo del sito*). Dopo un cambio di
  dominio, i link delle email portavano ancora al vecchio finché non si modificava `config/url.php` via FTP.
- **Un riferimento per ogni errore.** Un visitatore che incappa in un errore legge un riferimento di otto caratteri;
  l'amministratore lo cerca nel registro e arriva alla riga giusta.
- **Gli orari nel fuso orario di ciascuno.** Un membro sceglie il suo fuso orario nel profilo; altrimenti il sito usa
  quello del suo browser, e altrimenti quello del sito, una nuova impostazione in *Impostazioni → Preferenze generali*.
  Gli orari inseriti nei moduli si intendono nel fuso orario di chi li inserisce. L'elenco dei fusi orari è raggruppato
  per regione, con le città nominate nella lingua del sito.

### Corretto

- **Gli orari non sono più sfasati.** Il sito li mostrava in ora del server — ora universale — per tutti: due ore
  indietro per un visitatore in Francia in estate. Il fuso orario del profilo del membro, da parte sua, si applicava
  solo nelle pagine del profilo, e spostava le date che quel membro salvava. Il calendario, il wiki, il Bug tracker, le
  conversazioni archiviate e il gestore di file scrivevano i loro orari senza conversione; la trasmissione «in diretta»
  della web radio veniva letta in ora del server.
- **Il calendario**: l'inizio e la fine di un evento si scelgono in un selettore di date, e non più in un campo di testo
  nel formato «AAAA-MM-GG HH:MM:SS»; un titolo accentato non mostra più i suoi accenti come codice HTML; la descrizione
  formattata non mostra più il suo markup; l'esportazione del calendario riceve un testo pulito.
- **Gli elenchi con ricerca** dicono «Nessun risultato» nella lingua del sito, e non «No results found».
- **L'Editor live** propone ora di impaginare le pagine Forum, Gallerie, Squadre, Contatto e Palmarès: il suo menu le
  lasciava fuori, perché non riconosceva la loro pagina iniziale.
- **Una pagina che si blocca dice «Si è verificato un errore» (500)**, con il suo riferimento, invece di «Pagina non
  trovata», che faceva pensare a un indirizzo sbagliato.
- **Niente più pagine bianche.** Un errore fatale, o un errore sollevato fuori da una pagina, mostra una pagina di errore
  nella lingua del visitatore. Lo stesso per un database irraggiungibile (503), invece di un messaggio grezzo in inglese,
  e l'interruzione viene finalmente registrata nel registro.
- **Un'azione che fallisce lo dice**: una finestra che non si apre, un invio rifiutato, un server che non risponde più
  mostrano un messaggio, con il riferimento dell'errore — invece di un pulsante rimasto grigio senza una parola. Il
  Monitoraggio, quando non riesce ad aggiornarsi, ferma la sua clessidra.
- **Backup e aggiornamento dicono la verità.** Annunciavano «Backup salvato» o «Aggiornamento completato con successo»
  anche dopo un fallimento; ora annunciano il successo solo se il server lo conferma, e altrimenti dicono cosa è
  fallito. Il backup verifica anche di aver davvero scritto il suo archivio: in una cartella non scrivibile o su un
  disco pieno, annunciava un successo — e l'aggiornamento, che si appoggia su di esso per tornare indietro, non avrebbe
  avuto nulla da ripristinare.
- **L'errore di un campo di un modulo si mostra sotto il campo**, e non più solo passando sopra una piccola icona.
- Due errori interni non lasciano più traccia nel registro: un modulo inviato senza uno dei suoi campi, e una query al
  database che non restituisce nulla da leggere — quest'ultima faceva bloccare la pagina.
- **La barra di debug si mostra di nuovo**: un valore numerico la faceva bloccare da quando il codice controlla i suoi
  tipi in modo rigoroso, e la sua linea temporale era sbagliata.
- **L'annuncio di aggiornamento è di nuovo visibile.** Si era ridotto a un «1.2.x» incolore nella barra in alto: una
  pulizia degli stili dell'amministrazione aveva spazzato via i suoi. Ritrova un riquadro sotto il logo —
  «Aggiornamento disponibile · NeoFrag X.Y.Z» — e un badge leggibile nella barra in alto.
- **La finestra di aggiornamento** mostrava un blocco vuoto e lasciava un avviso nel registro: ora dice quale versione
  arriva, che il sito viene salvato prima di cominciare e torna al suo stato precedente in caso di fallimento, con un
  link a ciò che porta la versione.

### Rimosso

- **`NEOFRAG_LOGS_DB`**, che nulla leggeva: le nuove installazioni non lo scrivono più in `config/neofrag.php`.
  Un'installazione esistente può conservarlo; non ha alcun effetto.

## [1.2.12] — 2026-10-02

Il bot Discord passa alla **versione 0.2.0**. Per aggiornarlo: sostituisci la sua cartella con quella del nuovo
archivio, conservando il file `.env`, esegui `npm ci --omit=dev`, poi riavvialo. Crea poi una nuova chiave di accesso in
*Amministrazione → Discord* — la pagina lo chiede — e sostituisci la vecchia in `.env`: senza, al bot mancano i permessi
del Bug tracker. La configurazione del server ha bisogno anche del permesso Discord «Gestire i canali»: invita di nuovo
un bot invitato con la 1.2.11 tramite il pulsante «Invita il bot», o dai quel permesso al suo ruolo nelle impostazioni
del server.

### Aggiunto

- **Bot Discord: funzionalità che si accendono una per una.** *Discord → Funzionalità* elenca ciò che il bot sa fare (lo
  dichiara lui stesso). Ognuna si accende, si spegne e si configura dall'amministrazione; il cambiamento si applica entro
  un minuto, senza riavviare il bot. Il pulsante **Risincronizza** rimette tutto d'accordo, e all'avvio il forum
  recupera ciò che è stato scritto su Discord durante l'assenza del bot.
- **Bot Discord: si rimette al passo con il sito al suo ritorno.** Ciò che si scrive sul sito mentre il bot è spento va
  su Discord quando riparte, entro i 30 giorni di eventi che il sito conserva. Finora ripartiva dall'ultimo evento, e
  ciò che era stato scritto nel frattempo non arrivava mai su Discord.
- **Bot Discord: la configurazione del server.** Dall'amministrazione, il bot crea su Discord una categoria, un canale
  Forum per ogni forum scelto (con i suoi prefissi come tag) e un ruolo per ogni gruppo scelto, imposta da sé le
  corrispondenze, e riutilizza ciò che esiste già invece di duplicarlo. Prima viene un'anteprima, e l'ultima
  configurazione si può annullare.
- **Bot Discord: `/forum`.** `/forum account link` dà un link a uso singolo che collega il tuo account Discord al tuo
  account del sito; puoi anche scegliere di riprendere a tuo nome ciò che avevi pubblicato da Discord.
  `/forum account unlink` lo scollega. Senza account collegato, `/forum visibility` fa scegliere come si appare sul
  forum: il tuo nome utente Discord, un nome anonimo o un nome utente a tua scelta, modificabile ogni sette giorni — i
  messaggi già pubblicati seguono.
- **Bot Discord: prefissi del forum ↔ tag del canale Forum**, nei due sensi: mettere un prefisso su una discussione
  mette il tag sul thread, e viceversa.
- **Bot Discord: il Bug tracker in un canale Forum** (funzionalità da accendere in *Discord → Funzionalità*). Ogni
  ticket diventa un thread: il suo tipo e il suo stato sono i suoi tag, che il bot crea nel canale e tiene aggiornati.
  Titolo, descrizione e priorità seguono; il thread viene archiviato quando il ticket è chiuso, e un duplicato rimanda al
  suo ticket originale. I commenti passano nei due sensi. Da Discord, `/bug` e `/idee` aprono un ticket tramite una
  piccola finestra (titolo, descrizione), e un thread aperto a mano nel canale diventa un ticket — per un membro che ha
  collegato il suo account. I ticket ancora aperti ricevono il loro thread quando si sceglie il canale. Il sito fa fede:
  un tag cambiato a mano su Discord viene rimesso come dice il ticket.
- **Bot Discord: i ruoli temporanei** (funzionalità da accendere). Il comando `/role`, riservato a chi può gestire i
  ruoli, dà a un membro un ruolo per un tempo stabilito (una sanzione, un accesso di prova, un ruolo per un evento), lo
  toglie o mostra quelli in corso. Il bot lo toglie alla scadenza, anche dopo un riavvio, e lo ridà a un membro che
  lascia il server e vi rientra per sfuggirgli. Vengono rifiutati i ruoli collegati a un gruppo del sito, i ruoli gestiti
  da Discord e i ruoli posti al livello del ruolo più alto del bot o di chi li dà, o più in alto. La pagina *Discord →
  Ruoli temporanei* li elenca, con «Togli ora».
- **API**: due nuovi permessi, «Leggere il Bugtracker» (`bugtracker:read`) e «Scrivere nel Bugtracker»
  (`bugtracker:write`). Permettono a un programma di leggere i ticket (`GET bugtracker/tickets`, solo quelli aperti con
  `open=1`) e i loro commenti, aprire un ticket a nome di un membro, commentarlo a nome di un membro o di un account
  Discord, e modificare o eliminare quei commenti. Anche il prefisso di una discussione del forum si può cambiare tramite
  l'API (`PATCH forum/topics/{id}`). Il bot riceve gli endpoint `discord/*` delle sue nuove funzionalità: ruoli
  temporanei (`discord/timed-roles`), configurazione del server, collegamento degli account.

### Sicurezza

- **Un titolo scritto tramite l'API non può più iniettare codice nelle pagine del sito.** Il titolo di una discussione
  creata tramite l'API — per esempio il nome di un thread Discord copiato dal bot — veniva salvato così com'era, mentre
  il forum mostra i suoi titoli senza ricodificarli: un tag HTML in quel titolo veniva eseguito nella pagina del forum.
  L'API salva ora i suoi testi come il sito salva i propri, e il titolo della scheda è sempre codificato.

### Corretto

- **Gli accenti del Bug tracker** si mostravano come `r&eacute;agit` nella pagina di un ticket aperto con il modulo, e la
  ricerca «Già segnalato?» non trovava nessuna parola accentata. I testi che restituisce l'API — nomi utente, titoli,
  descrizioni, commenti — sono anch'essi in testo semplice: il bot Discord li copiava codificati. Le anteprime di
  condivisione dei link (Discord compreso) non mostrano più i loro accenti come codice HTML.
- **Bug tracker**: scegliere lo stato «Duplicato» senza un numero di ticket valido manteneva il vecchio stato
  annunciando «Ticket aggiornato»; il messaggio dice ora ciò che non è stato applicato.
- **Bot Discord: eliminare su Discord la copia di un messaggio del sito non cancella più l'originale.** Quando il bot
  non aveva più quel messaggio in memoria (dopo un riavvio), eliminare su Discord la copia di un messaggio scritto sul
  sito da un membro con l'account collegato cancellava anche il messaggio sul sito. In quel caso, il bot cancella ora sul
  sito solo i messaggi venuti da Discord.
- **Il wiki**: nella pagina iniziale del wiki, una pagina di primo livello senza sottopagine compariva come una sezione
  vuota; ora mostra le sue prime sezioni (o l'inizio del suo testo) e un link per leggerla, e la barra laterale ne fa un
  link.
- **Le pagine non trovate e vietate non sono più pagine vuote.** In tutti i temi pubblici, un indirizzo che non porta da
  nessuna parte (404) o una pagina riservata (403) mostravano solo l'intestazione e il piè di pagina, senza una parola —
  un difetto ereditato da NeoFrag. Ora dicono cosa succede, in tutte e sei le lingue, con un pulsante per tornare alla
  pagina iniziale (al cruscotto nell'amministrazione), e la scheda del browser dice «Pagina non trovata» o «Accesso non
  autorizzato» invece del nome del modulo.

## [1.2.11] — 2026-10-01

### Aggiunto

- **Il bot Discord** (versione 0.1.0), che unisce un sito e il suo server Discord:
  - **ruoli e nomi utente** — un membro che ha collegato il suo account Discord riceve, sul server, i ruoli collegati ai
    suoi gruppi, e li perde uscendo da quei gruppi; se l'opzione è spuntata, il suo soprannome sul server è il suo nome
    utente del sito. Il sito fa fede, e un ruolo a cui nulla è collegato non viene mai toccato;
  - **forum ↔ canale Forum di Discord**, nei due sensi — una discussione del sito diventa un thread, un thread una
    discussione; risposte, modifiche ed eliminazioni seguono. I messaggi che vengono dal sito compaiono con il nome e
    l'avatar del loro autore; quelli da Discord, con l'account del membro collegato o con il suo nome utente Discord.
    Canale per canale: sincronizzare tutto, o solo i thread che un moderatore segna con una reazione.

  È un programma a parte (Node.js 22.9 o successivo, su una macchina sempre accesa), fornito nel suo archivio con ogni
  versione. Sulla sua macchina conserva solo l'indirizzo del sito e una chiave di accesso. Guida: «Il bot Discord» nel
  wiki.
- **Il modulo Discord** (facoltativo, richiede il modulo API): la chiave del bot, **conservata cifrata**, il server,
  l'interruttore avvio / pausa, il riavvio, lo stato del bot (online, in pausa, offline) e il suo **registro nella
  lingua dell'amministratore**, le corrispondenze canale ↔ forum e gruppo ↔ ruolo, e il link che invita il bot con i soli
  permessi di cui ha bisogno — mai «Amministratore».
- **API**: il permesso `discord:bot` e gli endpoint `discord/*` di cui il bot ha bisogno.

## [1.2.10] — 2026-10-01

### Aggiunto

- **Un'API REST** (modulo **API**, facoltativo), per i programmi che parlano con il sito senza browser — prima di tutto
  il futuro bot Discord. Un amministratore crea **chiavi di accesso** con i soli permessi voluti; una chiave viene
  mostrata una sola volta, il sito ne conserva solo l'impronta, e si revoca con un clic. Gli endpoint `/api/v1/…`
  restituiscono JSON: lo stato del sito, un membro (per ID o per account Discord collegato), i gruppi, il forum (albero,
  discussioni, messaggi) e un **flusso di eventi** (nuova discussione, nuovo messaggio, cambio di gruppo…). **Scrive**
  anche nel forum a nome di un membro, o di un account Discord non collegato — pubblicato con il suo nome utente
  Discord, contrassegnato dal logo di Discord. Limitata a 120 richieste al minuto per chiave; gli errori hanno codici
  stabili. Guida: «L'API REST» nel wiki.
- **I miei account collegati** (area membri): vedere i propri account Discord, GitHub o Google collegati, collegarne uno,
  scollegarne uno — tranne se è l'unico modo di accedere.
- **Iscriversi con Discord** (o GitHub, Google): un account che nessuno ha collegato crea un membro, collegato fin
  dall'inizio, quando le iscrizioni sono aperte. Finora riceveva un errore «account sconosciuto».

### Corretto

- **Collegare il proprio account Discord faceva accedere all'account di un altro membro** quando quel Discord era già
  collegato a quest'ultimo: la sessione passava a lui. Il collegamento viene ora rifiutato, con un messaggio chiaro, e
  uno stesso account esterno non può più essere collegato a due membri.

## [1.2.9] — 2026-10-01

### Corretto

- **Blog: l'indice di un articolo restava intitolato «Sommaire» nelle altre lingue**, e lasciava un avviso nel registro
  del sito. Un messaggio dell'amministrazione dei membri («Gruppi del membro aggiornati») aveva lo stesso difetto.
- **Blog: il badge della categoria si allungava su tutta la larghezza delle schede** invece di restare una piccola
  etichetta.

## [1.2.8] — 2026-10-01

### Aggiunto

- **Blog: le serie.** Un articolo in più parti: ogni parte mostra l'elenco delle altre e il suo posto («Parte 2 di 3»),
  e la serie ha la sua pagina. Le serie si gestiscono nell'amministrazione del Blog, accanto a una nuova pagina che
  elenca finalmente le categorie.
- **Blog: una pagina per autore** (i suoi articoli) e **archivi mensili** cliccabili.
- **Blog: la condivisione mostra la copertina.** Un articolo condiviso su un social network mostra la sua copertina e
  non il logo del sito; i motori di ricerca ricevono i suoi dati strutturati (titolo, autore, data, immagine).
- **Sei widget del Blog**: gli ultimi articoli con la loro miniatura, i più letti, l'articolo in evidenza, le categorie,
  i tag e gli archivi.

### Corretto

- **Forum: l'elenco dei forum spariva per i membri connessi** appena un forum aveva discussioni solo nei suoi
  sottoforum. I visitatori non erano toccati.
- **Disiscriversi dalla newsletter non disiscriveva nessuno**: la pagina di disiscrizione falliva con un errore. Lo
  stesso per l'eliminazione di un iscritto nell'amministrazione.
- **Eliminare il proprio account** segnava l'account come eliminato, poi falliva con un errore prima di chiuderne le
  sessioni e cancellarne i codici di recupero.
- **Disattivare l'autenticazione a due fattori** falliva con un errore una volta disattivata, e lasciava i vecchi
  codici di recupero nel database.
- **Blog: una categoria vuota non si poteva eliminare** (il Blog la credeva sempre in uso), e la sua eliminazione non era
  protetta dai link contraffatti. Ora chiede una conferma.

## [1.2.7] — 2026-10-01

### Corretto

- **Forum: un forum-link mostra la propria icona.** L'icona scelta nel suo modulo di amministrazione sostituisce ora il
  globo che mostrava sempre.

## [1.2.6] — 2026-10-01

### Aggiunto

- **Forum: la risposta «soluzione».** L'autore di una discussione (o un moderatore) segna la risposta che la risolve.
  Viene messa in evidenza sotto la domanda, segnalata nel filo, e la discussione compare come «Risolto» nell'elenco. Una
  soluzione eliminata o spostata non lascia la discussione risolta.
- **Forum: i prefissi delle discussioni** («Domanda», «Tutorial», «Importante»…), creati dall'amministratore con il loro
  colore, e traducibili in ogni lingua attiva. I membri ne scelgono uno aprendo la loro discussione, e l'elenco di un
  forum si può filtrare in base a essi.
- **Forum: un'icona per forum**, scelta nell'amministrazione.

### Corretto

- **Il widget «Statistiche» del forum** contava anche le categorie che il visitatore non può leggere, e i messaggi
  eliminati: ora conta solo ciò che il visitatore può andare a vedere.
- **I dati di dimostrazione del forum** contavano il primo messaggio come una risposta («4 risposte» nell'elenco per una
  discussione che ne ha 3).

## [1.2.5] — 2026-10-01

### Aggiunto

- **Un vero Blog.** Il modulo Articoli diventa il Blog, sotto `/blog` (i vecchi indirizzi `/articles/…` reindirizzano in
  modo permanente). L'elenco si apre su un articolo **in evidenza**, poi schede illustrate, filtri per categoria e una
  colonna laterale (ricerca, più letti, categorie, tag, archivi). La pagina di un articolo ha un'immagine di copertina con
  il suo titolo, una barra di avanzamento della lettura, un indice che segue la sezione in lettura, l'autore, gli articoli
  precedente e successivo, e «Da leggere poi». L'amministratore sceglie l'impaginazione dell'elenco e della pagina
  dell'articolo; i visitatori passano dalla griglia alle righe, e la loro scelta viene ricordata.
- **Bug tracker: l'elenco si può filtrare per tipo** («Bug», «Richiesta funzionalità», «Domanda», «Altro»), e «Nuovo
  ticket» conserva il tipo scelto. Un link all'elenco filtrato (per esempio `/bugtracker?type=bug`) mostra i ticket già
  segnalati di quel tipo prima di aprirne uno nuovo.
- **Bug tracker: «Già segnalato?»** Mentre si scrive il titolo di un nuovo ticket, sotto compaiono i ticket aperti che
  gli somigliano, così si commenta quello esistente invece di aprirne un secondo. Un ticket si può anche segnare come
  **duplicato** di un altro: la sua pagina rimanda all'originale.

## [1.2.4] — 2026-10-01

### Aggiunto

- **Forum: categorie e forum traducibili.** Ognuno conserva il suo titolo predefinito e può ricevere, nel suo modulo di
  amministrazione, un titolo per ogni lingua attiva (e, per un forum, una descrizione). I visitatori vedono la traduzione
  nella loro lingua, altrimenti il titolo predefinito. Un link funziona con il titolo predefinito come con ogni
  traduzione.

### Corretto

- **Una migrazione già applicata non blocca più l'aggiornamento.** Applicata a mano senza essere registrata, falliva
  («column already exists») e avrebbe annullato l'intero aggiornamento. Le migrazioni si applicano ora istruzione per
  istruzione, saltando solo ciò che è già fatto.
- **I moduli forniti con il nucleo ricevono finalmente le loro modifiche al database.** Solo l'aggiornamento di un addon
  tramite il marketplace le applicava: un modulo come il forum o il calendario riceveva il suo codice nuovo con
  l'aggiornamento del nucleo, mai il suo database. Ora si applicano insieme a quelle del nucleo, con il pulsante come
  dopo un invio via FTP.
- **Articoli**: i sei permessi del modulo (aggiungere, modificare, eliminare, e i loro equivalenti per le categorie) non
  venivano controllati da nessuna parte. Ora sì, azioni di gruppo comprese.
- **I contenuti programmati non compaiono più prima della loro data**, né i contenuti messi nel cestino: il widget
  «Ultimi articoli», la mappa del sito inviata ai motori di ricerca, la ricerca nelle notizie, l'attività di un membro e
  il blocco «Ultimi articoli» li mostravano. Il widget Tag delle notizie non mescola più le lingue, e il widget delle
  categorie non conta più le bozze.

## [1.2.3] — 2026-10-01

### Corretto

- **L'aggiornamento finalmente si aggiorna da sé.** Il suo codice — insieme a quello del backup, del ripristino e del
  marketplace — viveva nella cartella `install/`, che l'aggiornamento non riscriveva mai: un sito conservava quello del
  giorno della sua installazione, e le sue correzioni non gli arrivavano. Ora vive nel nucleo (`neofrag/installer.php`),
  riscritto a ogni versione, e il Monitoraggio ne verifica l'integrità. Anche il resto di `install/` segue le versioni;
  solo il blocco `install/db.txt` resta proprio del sito. Su un sito esistente, `install/` viene aggiornato a partire
  dall'aggiornamento successivo a questo.
- **Un sito che elimina la sua cartella `install/` dopo l'installazione**, come si consiglia spesso, conserva il suo
  aggiornamento e il suo marketplace; i loro messaggi restano allora in francese.

## [1.2.2] — 2026-10-01

### Corretto

- **Squadre, giochi, categorie, partner e riconoscimenti non spariscono più nelle altre lingue.** L'amministrazione
  salva un titolo solo nella lingua in cui si scrive, e questi oggetti esistevano solo in quella: sulla demo, tre
  squadre in francese, nessuna in inglese. Il gruppo di una squadra, con i permessi che ne dipendono, spariva con lei.
  Ora si mostrano nella lingua richiesta, altrimenti in francese, altrimenti in quella che esiste. Gli elenchi che
  restituivano una riga per traduzione (reclutamenti, partite, flusso di attività dell'amministrazione) ne restituiscono
  ora una sola. Gli elenchi di contenuti (notizie, articoli, pagine) restano nella lingua richiesta.
- **Un aggiornamento applica finalmente le modifiche al database del nucleo.** Finora le applicava solo
  l'installazione: un sito aggiornato conservava il suo vecchio database sotto un codice nuovo. Con il pulsante, si
  applicano durante l'aggiornamento, che viene annullato se falliscono. Via FTP, si applicano alla prima pagina servita
  dal codice nuovo. (Le modifiche proprie dei moduli sono seguite nella 1.2.4.)
- **Accesso con Discord**: l'indirizzo di un avatar animato (102 caratteri) superava lo spazio che il database gli
  riservava (100), e l'accesso falliva; questo spazio passa a 255 per tutti gli account collegati. Un membro Discord
  senza avatar riceveva un'immagine rotta.

### Modificato

- **L'invio delle email passa a PHPMailer 7** (7.1.1). Il suo unico cambiamento incompatibile riguarda le classi che lo
  estendono, e NeoFrag non ne ha. Provato con un invio reale con le impostazioni di un sito, via SMTP come con `mail()`.

## [1.2.1] — 2026-10-01

### Sicurezza

- **Due vulnerabilità corrette nella libreria Markdown** (`league/commonmark` 2.10.3): un denial of service tramite
  tabelle costruite per rallentare il server (gravità alta, GHSA-3q6v-r5mr-hxv8), e un aggiramento del filtro che toglie
  l'HTML non consentito (gravità moderata, GHSA-97jj-33gv-5xf9). Rese pubbliche il 21 settembre 2026. Anche la libreria
  di pulizia dell'HTML (`ezyang/htmlpurifier` 4.19.1) passa alla sua ultima versione.

### Corretto

- **Il Monitoraggio non segnala più come «mancanti» file presenti.** Dall'uscita della 1.2.0, confrontava il sito con
  l'elenco ufficiale dei file della versione, ma escludeva le sorgenti Sass (file di stile prima della compilazione) solo
  dal lato del sito: quattro file segnalati mancanti per errore, e uno stato di salute «La nave sta affondando!».

### Modificato

- **Un aggiornamento del nucleo viene registrato nel log di audit**: chi l'ha avviato, quando, da quale versione a quale
  versione, quanti file sono stati scritti e quanti vecchi file sono stati tolti. Finora veniva scritto nel registro
  degli errori, dove sembrava un'anomalia.

## [1.2.0] — 2026-09-23

### Aggiunto

- **Gli aggiornamenti del nucleo, in un solo clic** (2026-09-23). A partire da questa versione, **Amministrazione →
  Monitoraggio** segnala le nuove versioni di NeoFrag Reborn e le installa: backup del sito, verifica dell'impronta del
  pacchetto, applicazione del pacchetto, aggiornamento del database, e ritorno al backup se qualcosa fallisce. È la prima
  versione pubblicata per questa via; è stata provata dall'inizio alla fine su un sito nuovo prima della pubblicazione.
  Un sito in 1.1.0 non ha ancora questo pulsante: passa alla 1.2.0 a mano, sostituendo i suoi file. Per i manutentori:
  `tools/build-release.php` produce i tre file da pubblicare insieme (`neofrag-reborn-update-<v>.zip`,
  `version.json`, `checksum.json`); la procedura è in `docs/guide/marketplace.md`.

- **Gli annunci su Discord** (2026-09-23). Un webhook che punta a un canale Discord riceve ora un vero messaggio, con
  titolo, link e colore: nuova notizia, nuovo articolo, nuovo membro, nuovo commento, nuova discussione del forum.
  Finora Discord rifiutava ciò che il modulo inviava, benché la sua descrizione promettesse Discord.

- **«Tizio è in diretta»** (2026-09-23). Quando un canale Twitch o YouTube seguito dal widget «Stato live» va in diretta,
  i webhook iscritti all'evento «Canale in diretta» lo annunciano, una sola volta per diretta, con la miniatura della
  diretta.

- **La demo parla inglese anche nei suoi contenuti** (2026-09-23): le sue notizie, articoli, pagine, categorie e gallerie
  hanno una versione inglese, che mostra il multilinguismo.

- **Tutto il prodotto parla le sei lingue** (2026-09-23). L'amministrazione, i moduli, i widget, i temi, le
  impostazioni, i pulsanti, i messaggi e gli script: quasi 800 stringhe erano scritte direttamente in francese e restavano
  così su un sito in inglese, tedesco, spagnolo, italiano o portoghese. Ora passano tutte dalle traduzioni, nelle sei
  lingue. In particolare:

  - **L'installer guidato è tradotto**, con un selettore di lingua; segue all'inizio la lingua del browser.
    L'installer da riga di comando accetta `--lang=en`, o la lingua del terminale.
  - **Il marketplace** mostra il nome e la descrizione di ogni addon nella lingua del sito.
  - **I paesi** (profilo, avversari) si mostrano nella lingua del sito.
  - **I ruoli e i modelli email inclusi** si mostrano tradotti; un nome cambiato dall'amministratore resta come
    l'amministratore l'ha scritto.
  - **La moderazione** mostra lo stato, il motivo e il tipo di una segnalazione, e il tipo di una sanzione, scritti per
    intero — mostrava i codici (`pending`, `forum_message`, `ban_temp`).
  - **Un gruppo creato in una lingua non sparisce più** quando il sito si mostra in un'altra — usciva dall'elenco, e i
    suoi permessi con lui.
  - Il tema dell'amministrazione si dichiarava scritto in inglese: su un sito inglese, le sue stringhe francesi non
    venivano mai tradotte. È corretto, così come gli script dei moduli, che cercavano le loro traduzioni nel posto
    sbagliato.
  - La conferma dell'eliminazione dell'account chiede di scrivere la parola nella lingua del sito («ELIMINA» in
    italiano), e non più «SUPPRIMER» ovunque.

  Un controllo, `check-textes-en-dur`, rifiuta ora nella CI ogni testo dell'interfaccia scritto nel codice.

- **I browser dei visitatori possono conservare i file del sito, se lo chiedi** (2026-09-22). Il manifest rendeva già il
  sito installabile; ora il sito può conservare le sue immagini, i suoi stili e i suoi script nei browser dei visitatori.
  **Spento per impostazione predefinita**, si accende in **Amministrazione → Impostazioni → Preferenze generali**
  («Applicazione installabile»).

  **Le pagine, invece, non vengono mai conservate.** Una pagina tenuta in memoria sopravvivrebbe a una nuova messa
  online: a chi fosse già passato verrebbe servito un sito vecchio di giorni, senza che nulla lo segnali. Una modifica
  resta quindi visibile subito.

  **Togliere la spunta disinstalla davvero.** È la parte delicata di questa tecnica: un componente di questo tipo, una
  volta installato in un browser, vi resta anche se il file viene tolto dal server. L'interruttore non toglie quindi
  nulla — serve una versione che si disinstalla da sola alla visita successiva. Per questo la funzione è spenta per
  impostazione predefinita, e per questo un aggiornamento del nucleo non la accende mai da solo.

- **Il sito si può installare come applicazione** (2026-09-21). `/manifest.webmanifest` è generato dal sito stesso —
  nome, descrizione, colore del tema, indirizzo di partenza e favicon vengono dalle impostazioni — invece di essere messo
  come file, che sarebbe sbagliato ovunque altrove.

- **Le cartelle sensibili sono chiuse anche con Apache** (2026-09-21). I `.htaccess` del pacchetto proteggevano **meno**
  della configurazione per Caddy inclusa: con Apache, l'installer restava raggiungibile una volta installato il sito
  (solo il suo controllo gli impediva di funzionare), e su un sito installato dal repository git, anche `docs/`, `tools/`
  e `tests/` erano raggiungibili — la documentazione si leggeva in chiaro. `cache/`, `config/`, `install/`, `tools/`,
  `tests/` e `docs/` hanno ora ciascuna la propria protezione, la radice blocca anche i file `.neon` e `.md`, e
  `tools/check-htaccess.php` rilegge l'elenco a ogni esecuzione, con il motivo di ogni riga.

- **Un indirizzo sconosciuto non scrive più un errore nel registro del sito** (2026-09-21). Il modulo `pages`, che
  riceve in ultima istanza gli indirizzi che nient'altro riconosce, scriveva una riga di **errore** per ciascuno — ogni
  robot che sondava ne aggiungeva. Per gli sviluppatori di addon: un controller può ora dichiarare un rifiuto come
  ordinario (`Module_Checker::refus_ordinaire()`); la diagnosi resta mostrata sullo schermo in modalità debug. Il
  controllo `check-journal` rifiuta ora ogni riga che il prodotto stesso scrive nel registro degli errori.

- **Due controlli fanno rispettare le regole del repository** (2026-09-21), per i contributori.
  `tools/check-tools.php` verifica che ogni strumento segua le regole comuni: intestazione descrittiva, ricorso alla
  libreria comune invece di codice copiato, verdetto e codici di uscita standard, porta riservata, catalogo aggiornato.
  `tools/check-docs.php` controlla la documentazione: cifre esatte, link e ancore validi, nessun documento orfano,
  nessuna menzione di uno strumento che non esiste più, nessun passaggio copiato da un documento all'altro, documenti
  che restano leggibili. Entrambi girano nell'integrazione continua.

- **Otto nuovi addon, tutti facoltativi** (2026-09-20). Un aggiornamento non li installa: su un sito esistente, si
  aggiungono da **Amministrazione → Temi & addon**, e si tolgono allo stesso modo. Una nuova installazione li comprende
  con il profilo *Completo*, proposto per impostazione predefinita, ma non con gli altri profili.

  | Addon | Cosa fa |
  |---|---|
  | **Effetto stagionale** (widget) | Neve, coriandoli o foglie su tutto lo schermo, durante un intervallo di date. Le date si scrivono `MM-DD` **senza anno** — la stagione torna da sola — e possono passare per Capodanno (`dal 12-15 al 01-06`). Fuori stagione, la pagina non riceve **assolutamente nulla**: né immagine, né script. L'impostazione di sistema che chiede meno movimento (`prefers-reduced-motion`) è rispettata, e l'animazione si ferma quando la scheda passa in secondo piano. |
  | **Lettore di feed** (widget) | Gli ultimi articoli di un feed RSS o Atom esterno. **Una pagina non aspetta quasi mai un sito terzo**: legge una cache, e serve perfino contenuti scaduti piuttosto che far aspettare i visitatori; l'aggiornamento avviene nell'attività pianificata. Solo la primissima visualizzazione, con la cache vuota, aspetta il feed, tre secondi al massimo. Un feed fuori servizio viene messo da parte dieci minuti invece di essere ritentato a ogni visita. |
  | **Citazioni** (modulo) | Una raccolta per categorie, con autore e fonte. |
  | **Ricette** (modulo) | Ingredienti e passaggi inseriti uno per riga, tempi e numero di porzioni, marcatura `schema.org/Recipe` che leggono i motori di ricerca. |
  | **Glossario** (modulo) | Un lessico ordinato per lettera. «Éclaireur» va sotto la E, «Æther» sotto la A, «1v1» sotto #. La lettera è calcolata, mai inserita. |
  | **Mappa dei luoghi** (modulo) | Luoghi su una mappa OpenStreetMap, con indirizzo, descrizione e link. La libreria della mappa è **ospitata dal sito**; solo le tessere della mappa di sfondo vengono da OpenStreetMap. **La pagina resta utile senza JavaScript** — l'elenco, gli indirizzi e i link ci sono. |
  | **Web radio** (modulo) | Un lettore di flusso, ciò che va in onda, e il palinsesto della settimana. Una trasmissione può passare la mezzanotte: «sabato 22:00 → 02:00» resta la trasmissione del sabato. |
  | **Sabbiera** (modulo) | Riservato ai membri: vi si prova una formattazione e si vede la resa **esatta** del sito. Soprattutto, mostra **ciò che il sito ha tolto** — è l'unica pagina che risponde a «perché la mia tabella è sparita?». |

  I tre moduli di contenuto (citazioni, ricette, glossario) condividono lo schema delle FAQ: categorie, ricerca, filtro,
  ordinamento e bozze.

- **Scegliere i contenuti all'installazione.** L'installer web propone ora un **profilo del sito** tra i requisiti e il
  database: *Completo*, *Gaming / eSport*, *Community* o *Solo nucleo*. I moduli si possono sempre togliere uno per uno,
  e quelli di cui un modulo ha bisogno vengono aggiunti automaticamente (il palmarès ha bisogno delle squadre), con un
  messaggio. Senza JavaScript, il modulo continua a funzionare — il server conserva solo ciò che appartiene al profilo
  scelto.

  **Nessun elenco di addon è scritto a mano**: i profili sono costruiti a partire dalle dichiarazioni `presets` di ogni
  addon. È la differenza dal tentativo di giugno 2026, abbandonato perché un'installazione alleggerita finiva in un
  errore del server (500): moduli del nucleo interrogavano le tabelle di moduli facoltativi senza verificare che
  esistessero. Questi controlli ora esistono e vengono verificati a ogni esecuzione della CI.

- **Ogni modulo, widget e tema incluso dichiara il proprio ruolo**: nel suo `__info()`, dice se appartiene al nucleo, a
  quali profili di installazione partecipa, e di quali moduli ha bisogno. L'appartenenza al nucleo era finora un effetto
  collaterale della presenza nel pacchetto — è così che `emojis`, `files` e `webhooks` ci erano finiti senza che fosse
  presa alcuna decisione.

- **Tre nuove salvaguardie di integrazione continua**:

  | Controllo | Cosa rifiuta |
  |---|---|
  | `check-addon-declarations.php` | un addon che non dichiara nulla, una dipendenza inesistente, **un addon del nucleo che dipende da uno facoltativo**, un addon non distribuibile pubblicato nel catalogo, una dichiarazione in disaccordo con `seed.sql` |
  | `check-addon-coupling.php` | un accoppiamento **fatale** (tabella, classe) né dichiarato né annotato; un ciclo duro. Letto dal codice con il **tokenizer di PHP**, non con espressioni regolari — quindi nessun falso positivo sui commenti |
  | `check-install-profiles.php` | un profilo che non si avvia. **Installa davvero** su un database usa e getta, serve il sito e fallisce al minimo 5xx — anche sulle rotte dei moduli assenti, che devono restituire un 404 pulito |

- **Trovare un membro dalla barra di ricerca.** La ricerca del sito trovava solo contenuti — forum, notizie, pagine.
  Cercare qualcuno obbligava ad aprire l'elenco dei membri e a sfogliarlo, benché sia una delle cose che si cercano più
  spesso su un sito di community. I membri compaiono ora nella ricerca globale **e** nei suggerimenti istantanei, per
  nome utente, nome o cognome.

  Nulla di nuovo viene esposto per strada: questi tre campi sono già mostrati sul profilo pubblico di ogni membro, e la
  ricerca applica esattamente lo stesso filtro dell'elenco — un account eliminato non vi compare.

- **Gestire una serie di eventi in un colpo solo.** Creare un evento ricorrente generava già tutte le sue occorrenze con
  un clic — tornarci sopra obbligava poi ad aprirle una per una.

  Ora un'occorrenza di una serie ha un pulsante **«Elimina tutta la serie»**, la cui conferma dice quante occorrenze se
  ne andranno, e il modulo di modifica propone **«Applica a tutte le occorrenze della serie»**. Il titolo, il tipo, le
  descrizioni, il luogo, l'immagine e lo stato di pubblicazione vengono copiati su ciascuna; **le date mai** — sono loro
  a distinguere una sessione dalla successiva.

- **Annullare un aggiornamento e ripristinare un backup.** Il CMS faceva già un backup completo — file e database — subito
  prima di ogni aggiornamento del nucleo. Non sapeva riutilizzarlo: la rete di sicurezza c'era, ma nessuno sapeva
  caderci.

  Ora, se il posizionamento dei file o una migrazione fallisce, **il sito viene rimesso automaticamente nello stato in cui
  era**; il messaggio dice sia cosa è fallito sia se il ritorno indietro è riuscito. Ogni backup dell'elenco ha anche un
  pulsante **Ripristina**, per i casi in cui il danno non viene da un aggiornamento — un modulo di terze parti, un'azione
  di cui ci si pente. La conferma indica ciò che andrà perso.

  Ciò che il ripristino **non** tocca, di proposito: la configurazione del sito (rimettere credenziali del database
  scadute taglierebbe il sito fuori dal proprio database), i registri (sono la memoria dell'incidente che si sta
  riparando) e la cache (svuotata perché si ricostruisca, invece di essere rimessa nello stato di un'altra versione).

  Per strada, il backup comprende ora `vendor/`, che il pacchetto di aggiornamento fornisce e che lui non comprendeva:
  senza, un ritorno indietro avrebbe rimesso il vecchio codice sopra le nuove dipendenze.

- **Rispondere a un commento.** Il pulsante «Rispondi», disattivato nel codice originale, funziona ora: la risposta si
  mostra rientrata sotto il commento a cui risponde. Si può rispondere solo a un commento di primo livello dello stesso
  contenuto che non sia stato eliminato (un livello di risposte); il server lo verifica.
- **Per i creatori di temi: zone chiamate per nome** (idea presa da HiddenCMS). Una vista chiama ora una zona per nome —
  `$this->output->region('content')` — invece che per numero — `zone(2)`. Il tema dichiara la corrispondenza tra nomi e
  zone nel suo `__info()` (`regions`). La vecchia chiamata `zone()` funziona ancora, e un tema senza questa
  dichiarazione resta invariato. I cinque temi pubblici (Nebula, Forge, Granite, Blockcraft, Extend) usano i nomi.
- **Installare da riga di comando** (`install/cli.php`, idea presa da HiddenCMS): un'alternativa all'installer web,
  pratica per installare un server in modo riproducibile o automatizzato. Le sue opzioni coprono il database (`--db-*`,
  `--create-db`), l'account amministratore (`--admin-*`), il nome e l'indirizzo del sito (`--site-name`, `--site-url`),
  la lingua (`--lang`), i dati di dimostrazione (`--demo`) e l'esecuzione (`--force`, `--yes`, `--dry-run`,
  `--no-lock`). Senza opzioni, fa le domande una per una e nasconde la password mentre la si digita; `--admin-pass-env`
  legge la password da una variabile d'ambiente perché non compaia nell'elenco dei processi. Usa lo stesso codice
  dell'installer web e installa ogni addon incluso, come il profilo *Completo*.

- **Un avviatore per tutta la batteria**: `php tools/check-all.php`. I controlli non avevano un punto d'ingresso comune
  — la CI li chiama uno per uno, e in locale ognuno scriveva il proprio ciclo a mano, mai lo stesso. L'avviatore scopre i
  controlli presenti (un nuovo controllo si aggiunge senza che nessuno lo tocchi), esegue prima `composer audit`, limita
  la durata di ogni controllo, e dice alla fine **ciò che non ha eseguito**: i test nel browser (`--navigateur`) e i
  controlli con un obiettivo esplicito, tra cui `check-restauration`, che danneggia il sito di proposito.

- **Ogni addon mostra il suo aspetto** (2026-09-22). La pagina «Temi & addon» dell'amministrazione e il marketplace
  mostrano una miniatura: un vero screenshot del modulo, widget, tema o lingua come gira sulla demo. Tutti i 61 addon del
  marketplace ne hanno una, in 960×600 per i moduli e i widget. Per arrivarci, sulla demo sono stati installati sei
  moduli e due widget con contenuti di esempio — un glossario, citazioni, ricette, una mappa dei luoghi e un palinsesto
  di web radio —, e i widget che senza impostazioni non mostrano nulla sono stati catturati con impostazioni di esempio.
  Ciò che non ha nulla da mostrare conserva la sua icona, come i connettori Discord, GitHub e Google, la cui icona è il
  loro logo. La demo porta inoltre finalmente l'identità della sua community — nome, tipo, data di creazione e
  descrizione —, rimasta vuota.

- **Dieci addon hanno finalmente una descrizione** (2026-09-22): le sei lingue e i quattro addon di accesso esterno
  (quello di base e i suoi connettori Discord, GitHub e Google). Viene mostrata nella lingua del sito.

- **Campi del profilo definiti dall'amministratore** (2026-09-20). Il profilo di un membro aveva solo campi fissi.
  **Amministrazione → Utenti → Campi del profilo** permette di aggiungerne di propri — nome nel gioco, piattaforma,
  grado… —, tra otto tipi di campo. I membri li compilano nel pannello «Informazioni aggiuntive» del loro profilo. Un
  campo è privato per impostazione predefinita; una volta reso pubblico, si mostra sulla pagina del membro. I valori
  sono compresi nell'esportazione dei dati personali del membro e vengono eliminati con il suo account.

- **Il carattere del sito si sceglie nell'amministrazione** (2026-09-20). **Amministrazione → Impostazioni → Preferenze
  generali → Carattere del sito** propone dodici caratteri, che sostituiscono quello di ogni tema. Per impostazione
  predefinita, «Carattere del tema» conserva il carattere proprio di ogni tema e non chiama alcun servizio esterno; i
  dodici caratteri proposti sono serviti da Google Fonts.

- **Lo storico degli accessi non è più conservato per sempre** (2026-09-20). Registra l'indirizzo IP, il browser e la
  data di ogni accesso, e nulla lo cancellava mai. Viene ora ripulito oltre i 395 giorni (tredici mesi), una durata
  regolata in **Amministrazione → Impostazioni → Preferenze generali**; 0 disattiva la pulizia.

- **Promemoria per gli eventi seguiti del calendario** (2026-09-20). Un membro che segue un evento del calendario riceve
  un promemoria prima del suo inizio, come i partecipanti di un evento del modulo Eventi. Il preavviso si regola nella
  configurazione del calendario («Promemoria prima di un evento seguito», 24 ore per impostazione predefinita, 0 per
  disattivarlo).

- **Il pacchetto di aggiornamento dice ciò che protegge e ciò che toglie** (2026-09-20). Porta un file
  `nf-manifest.json`: le cartelle del sito in cui un aggiornamento non scrive mai (`config/`, `install/`, `upload/`,
  `logs/`, `backups/`, `cache/`), e i file che la versione toglie. Finora l'aggiornamento deduceva da sé cosa eliminare;
  un pacchetto senza questo file è ancora accettato.

### Modificato

- **Tutta l'interfaccia parla Bootstrap 5** (2026-09-23). Restavano circa 470 usi di classi di Bootstrap 3 e 4, tenuti in
  funzione da un foglio di stile di compatibilità: campi dei moduli, badge colorati, pulsanti di chiusura, pulsanti a
  tutta larghezza, blocchi «avatar e testo», gruppi di input, caselle, griglie dei moduli. Tutto il markup è migrato ai
  suoi equivalenti di Bootstrap 5; ciò che è proprio del prodotto porta ora un nome proprio del prodotto. Un controllo
  rifiuta ogni vecchio nome che ritorni, anche ridefinito. Ciò che si vede:
  - gli **elenchi a discesa** ritrovano la loro freccia — un centinaio, nell'amministrazione e sul sito, sembravano
    campi di testo;
  - i **badge colorati** (stati, gruppi, ruoli, sanzioni) sono leggibili in ogni tema, in chiaro come in scuro: il loro
    testo non superava il controllo del contrasto;
  - i gruppi **«Moderatore» e «Moderatore senior»** si vedono: il loro nome era scritto in bianco su sfondo bianco;
  - i **pulsanti di chiusura** delle finestre hanno un'area di clic sufficiente sui telefoni.

- **Il JavaScript è rivisto da un linter** (2026-09-22). Settantuno file del progetto scritti nel corso degli anni, senza
  che nulla li controllasse oltre la loro sintassi e l'assenza di jQuery. ESLint vede ciò che la sintassi non dice: una
  variabile globale creata dimenticando un `var`, un `eval` mascherato, codice dopo un `return`, un `innerHTML`
  alimentato da un valore calcolato. Nessun pacchetto npm viene messo online: è uno strumento di sviluppo, proprio come
  l'analisi statica di PHP.

  Le regole sono scelte con un solo criterio — aver già morso qui. È la famiglia di «`$` is not defined», che aveva
  bloccato l'ordinamento delle tabelle in tutta l'amministrazione per tre mesi. Il disordine esistente (usi di
  `innerHTML` da ripulire) viene **congelato così com'è** invece di essere riscritto in un colpo: il suo numero non può
  più salire.

- **La baseline di PHPStan si rigenera con un comando, e la classe `Config` del nucleo è annotata** (2026-09-21). Tre
  nuovi errori hanno portato a riaprire `phpstan-baseline.neon`: le voci aggiunte a mano erano scritte diversamente da
  quelle di PHPStan stesso, e quasi trecento blocchi non corrispondevano più a nulla — un'intera famiglia «Function
  NeoFrag not found», morta da quando il file di bootstrap dichiara la funzione. La configurazione vive in
  `phpstan-base.neon`, `phpstan.neon` vi aggiunge solo la baseline, e `composer stan:baseline` la rigenera per intero.
  `Config` annota ora la lingua corrente, l'elenco delle lingue e le impostazioni del nucleo che i moduli leggono: questi
  accessi vengono analizzati invece di essere congelati.

- **Gli strumenti di `tools/` rifatti su una libreria comune** (2026-09-21). Una sessantina di strumenti, scritti
  ciascuno a modo suo, copiavano le stesse tubature: avviare un server PHP, aprire una sessione da amministratore,
  lanciare Chrome, leggere `config/db.php`. Una lezione imparata in uno non arrivava agli altri. `tools/lib/` porta ora
  queste fondamenta — connessione, sessione, server, browser, attraversamento del repository, SQL, opzioni, verdetti —,
  e ogni strumento si riduce alla propria logica. Ne restano cinquantadue, senza perdita di funzioni: `check-js-syntax`
  e `check-js-jquery` formano `check-js-sources`; `check-docs-counts` e `check-docs-liens` formano `check-docs`;
  `check-lang-args` confluisce in `check-langs`; `seed-wiki-docs` e `dump-wiki` formano `wiki-docs`; `smoke-test`
  diventa `check-smoke`, come ogni controllo; `addons-manifest` e `table-map`, che sono dati, vivono nella libreria. Ogni
  strumento ha ora la propria porta riservata (diversi ne condividevano una), tre codici di uscita che distinguono
  «niente da segnalare», «rifiutato» e «non ha potuto giudicare», e un'intestazione da cui si genera il catalogo di
  `tools/README.md`. `bs5-codemod`, lo strumento della migrazione da Bootstrap 4 a 5 di giugno, è tolto:
  `check-classes-bs4` copre tutto ciò che controllava, compresi i breakpoint delle utility direzionali.

- **`declare(strict_types=1)` su tutto il perimetro utile** (2026-09-21): **1.365 file** contro 78 il giorno prima. Il
  lavoro avanzava a piccoli lotti da giugno; è finito. I 209 file restanti sono i modelli `views/**.tpl.php`, dove la
  dichiarazione sarebbe sintatticamente valida e non proteggerebbe nulla.

  La conversione è stata fatta in ordine di **rischio decrescente** — il numero di chiamate a funzioni native per file —,
  con la suite di test eseguita tra un lotto e l'altro.

  Una conseguenza di fondo, propria di questo prodotto: PHP in modalità rigorosa **rifiuta `__toString()` per le
  funzioni interne**. Ora, `lang()` restituisce un oggetto di traduzione *differito*, risolto al momento della resa,
  quando la lingua è finalmente nota, e anche le date sono oggetti. La barra dell'amministrazione, la pagina di ogni
  notizia e diverse schermate si bloccavano quindi con un errore fatale — modulo fuori uso, pagina troncata, senza una
  parola. La conversione avviene ora nel **punto di escape** (`htmlspecialchars`), cioè dove il valore deve diventare una
  stringa per definizione: quasi 500 punti in 86 file.

  L'ondata ha richiesto anche undici conversioni esplicite, tre delle quali su percorsi presi a ogni pagina — l'ordine
  di visualizzazione delle lingue e dei gruppi, letto all'inizio di ogni sessione, e il calcolo del token che protegge
  ogni modulo dalle richieste contraffatte (CSRF): senza di esse, un sito con due lingue attive avrebbe restituito un
  errore 500 su tutte le sue pagine.

- **Il registro dice ora DOVE, non solo cosa** (2026-09-21). Quando un modulo solleva un'eccezione, la sua resa viene
  abbandonata e la pagina si mostra troncata — in silenzio. La riga del registro dava il messaggio senza mai nominare un
  file. Ora nomina il primo punto del codice proprio del prodotto nello stack delle chiamate. Risalire all'origine degli
  errori qui sopra è passato da un'indagine a una semplice lettura.

- **Il compilatore SCSS passa alla 2.1** (2026-09-20). Su PHP 8.5, ogni compilazione degli stili scriveva **dieci avvisi
  di deprecazione** nel registro; non ne resta nessuno. La 2.x minimizza un po' di più (`.25rem` invece di `0.25rem`,
  `#ccc` invece di `#CCCCCC`), il che cambia tre fogli di stile — quelli dei moduli addon e palmarès e della pagina di
  manutenzione. L'equivalenza non è stata dedotta dal testo: i due insiemi di CSS sono stati dati al motore di Chrome,
  che vi vede esattamente le stesse regole, proprietà e valori calcolati.

- **La documentazione è stata riletta per intero sul codice** (2026-09-17). Le guide all'installazione, ai concetti e
  allo sviluppo (creare un modulo, un widget, un tema, il framework) risalivano a giugno e descrivevano il CMS di prima:
  un'installazione senza scelta di profilo, un tema che caricava jQuery, griglie di Bootstrap 4. Ora dicono ciò che fa il
  prodotto — profili di installazione, dichiarazioni degli addon, regioni con nome, front end senza jQuery sotto una
  politica di sicurezza rigorosa, impostazioni dei widget con valori di riserva, vocabolario di colori condiviso. Il
  README, la guida al contributo, la politica di sicurezza e il riferimento tecnico sono allineati allo stesso modo.

- **Ciò che un addon consente si legge dalla sua dichiarazione**, non più da elenchi di nomi scritti nel codice. Tre di
  essi decidevano la sorte degli addon, e tutti e tre erano sbagliati: l'elenco dei widget proteggeva sette nomi che non
  sono widget; quello dei temi proteggeva un tema `default` inesistente **lasciando `nebula`, l'unico tema pubblico del
  nucleo, eliminabile** appena era inattivo; quello dei moduli duplicava, in disaccordo, ciò che `__info()` diceva già.
  Verificato addon per addon rispetto alla vecchia logica: nessun cambiamento in ciò che si può disattivare, nessun
  cambiamento di stato attivo, e **39 addon diventano non eliminabili** — che è lo scopo della correzione.

- **Il Cestino non conosce più gli altri moduli.** Aveva scritto nel codice l'elenco delle tabelle, chiavi primarie e
  metodi di ripristino di `news`, `articles`, `gallery`, `comments` e `forum`: non poteva quindi appartenere al nucleo
  senza trascinare con sé quattro moduli facoltativi — `comments` appartiene esso stesso al nucleo. Ogni modulo dichiara
  ora i suoi tipi ripristinabili; il nucleo li raccoglie. Stessa inversione per i descrittori di contenuto (reazioni,
  abbonamenti, revisioni).

- **L'elenco degli addon del nucleo e facoltativi non è più mantenuto a mano.** `tools/lib/addons-manifest.php`, una
  tabella scritta a mano e congelata a giugno, è ora dedotta dalle dichiarazioni. Le due avevano già divergito: `emojis`
  era elencato come nucleo mentre si dichiara à la carte — si poteva quindi togliere all'installazione ma mancava nel
  catalogo, ed era perciò **impossibile da reinstallare**. Catalogo: 52 → **53 addon** quel giorno (61 all'uscita della
  1.2.0).

- **L'installer, rifatto visivamente**: impaginazione su due colonne, token di stile presi da `nebula` (blu notte e
  verde acqua, accento `#2dd4bf`), sfumatura tolta da dietro il testo, logo messo in un badge.

### Corretto

- **Il backup del Monitoraggio funziona di nuovo** (2026-09-23). Dal 21 settembre, si fermava alla primissima riga del
  database: il pulsante «Avvia backup», e l'aggiornamento del nucleo, che comincia con un backup, fallivano su ogni sito.
  Trovato provando l'aggiornamento prima di pubblicarlo.

- **Cambiare lingua su una notizia tradotta non porta più a una pagina non trovata** (2026-09-23). Il selettore di lingua
  conserva l'indirizzo, e con esso il titolo nella lingua precedente: la versione tradotta lo rifiutava. Ora reindirizza
  al proprio indirizzo.

- **Una pagina tradotta si mostra nella lingua giusta** (2026-09-23). Le pagine pubbliche venivano servite nella versione
  che il database restituiva per prima, qualunque fosse la lingua del visitatore.

- **Il tema scelto da un visitatore non si estende più a un altro sito dello stesso dominio** (2026-09-23). La scelta
  veniva ricordata per tutto il dominio: un visitatore che passava un sito a Forge vedeva anche in Forge un altro sito
  installato sotto lo stesso dominio. La scelta viene ora ricordata **per ogni sito separatamente**, e una nuova
  impostazione, **Preferenze generali → Scelta del tema**, permette di disattivarla: il menu sparisce e il tema
  predefinito vale per tutti.

- **La demo si ripristina di nuovo** (2026-09-23). Un errore nel suo insieme di dati faceva fallire il ripristino ogni
  quarto d'ora dal giorno prima: ciò che i visitatori vi cambiavano restava. Un controllo rifiuta ora questo difetto prima
  che qualcosa vada online, e un ripristino fallito viene scritto nel registro invece di passare inosservato.

- **L'account di emergenza della demo non compare più da nessuna parte** (2026-09-23): né nell'elenco dei membri, né nella
  ricerca, né tra i membri online, né nell'amministrazione; la sua pagina risponde «non trovato».

- **Il sito su un telefono** (2026-09-23), sulla base di screenshot presi su un telefono:

  - **Il menu è sempre raggiungibile.** Nel tema Nebula, spariva sotto gli 860 pixel di larghezza senza un pulsante per
    riaprirlo: ora lo apre un pulsante «hamburger». Negli altri temi, un menu troppo lungo per una riga si raccoglie
    dietro un pulsante «Menu», invece di andare a capo fino a riempire lo schermo; un menu corto resta aperto.
  - **I blocchi impilati non si toccano più**: l'area membri era attaccata al blocco sopra.
  - **Il testo non si attacca più agli avatar dei membri** (commenti, conversazioni, autore di una notizia, candidature,
    profilo).
  - **I punti del carosello** sono veri pulsanti, centrati sotto l'immagine, e restano **chiari** sui temi scuri, come le
    frecce e la didascalia: Bootstrap li rendeva neri.
  - **La ricerca del tema Extend** conserva il suo pulsante accanto al campo.
  - **Le foto di un album** riempiono la loro scheda.
  - L'intestazione dei commenti mostra il titolo prima del contatore, che prima lo precedeva.
  - Le date abbreviate si scrivono nella lingua del sito («30 août 2026», «Sonntag»), e non più in inglese; la riga
    dell'autore di una notizia ritrova una dimensione di testo normale.

- **La pagina di una foto della galleria esiste** (2026-09-23). Il widget «Immagine casuale», il carosello della galleria
  e il link di un commento scritto su una foto portavano tutti a una pagina non trovata: non era mai stata dichiarata.
  Mostra la foto, la sua descrizione, la via del ritorno all'album e i commenti. Una foto segue inoltre le regole del suo
  album: niente più accesso dal suo indirizzo quando l'album è nel cestino o non ancora pubblicato.

- **Gli ultimi resti di Bootstrap 3 e 4** (2026-09-23), che i loro nomi di classe non tradivano: la struttura del
  carosello, le croci di chiusura che mostravano una «×» sopra l'icona, due fisarmoniche (FAQ, link di navigazione)
  ancora costruite alla maniera di Bootstrap 4 (quella delle FAQ si apriva, ma senza l'aspetto né la freccia del
  componente), campi che si avvolgevano in un gruppo vuoto, la freccia dei fumetti rimasta bianca sotto un fumetto scuro,
  e due regole del widget dei partner. `check-classes-bs4` legge ora anche la STRUTTURA dei componenti e gli stili scritti
  nelle viste.

- **Pagine di errore che non lo erano** (2026-09-23). Nella moderazione, e nella gestione dei ruoli, dei modelli email,
  dei membri e del carosello, un indirizzo che puntava a un elemento tolto doveva rispondere «pagina non trovata»:
  chiamava una funzione che non esiste, scriveva un avviso nel registro, e la pagina della moderazione si mostrava vuota.
  Trovato insegnando all'analisi statica le funzioni «magiche» del framework — il che ha tolto 455 occorrenze dalla sua
  lista di eccezioni.

- **Il pulsante delle email inviate ai membri è leggibile** (2026-09-23): la convalida dell'account, la password persa e
  gli altri modelli avevano un pulsante bianco su turchese chiaro (contrasto di 2,3:1). La correzione vale per i modelli
  inclusi e per quelli già installati, tranne quelli che un amministratore ha personalizzato.

- **Ultimi ritocchi di visualizzazione** (2026-09-23): i link del wiki nella tinta leggibile del tema; i pulsanti di
  «successo» sui temi scuri; lo stato di una candidatura, il cui riquadro non aveva sfondo (classi di un vecchio tema di
  amministrazione); i social network sulle schede dei membri; i titoli troncati nell'amministrazione; un visitatore che
  segue il link per partecipare a un evento riceve «Accesso non autorizzato» invece di una pagina non trovata.

- **Il testo è leggibile ovunque, in modalità chiara come in modalità scura** (2026-09-23). I link e i pulsanti
  dell'amministrazione, gli avvisi, le date e le intestazioni delle tabelle dei temi, le categorie delle notizie, la pagina
  del marketplace sui temi chiari, il pulsante «Accetta tutto» del banner dei cookie, i badge di reputazione e di tipo di
  evento: tutti raggiungono ora il contrasto minimo raccomandato (4,5:1). In modalità scura, l'amministrazione ha i suoi
  colori di stato, e il pannello «Salute del sito» e la zona di caricamento delle immagini della galleria seguono il
  tema.

- **L'amministrazione su un telefono** (2026-09-23). La barra in alto va a capo su due righe — le briciole di pane
  complete, poi i pulsanti — invece di troncare il titolo della pagina; i bersagli piccoli (icona della home, contatori)
  sono abbastanza grandi per un dito; l'intestazione delle categorie del forum non scivola più sotto i suoi pulsanti.

- **Il forum su un telefono** (2026-09-23): in una discussione, l'autore passa sopra il messaggio invece di lasciargli
  solo 200 px di larghezza, e i pulsanti di un messaggio non coprono più la sua data.

- **I plurali tradotti** (2026-09-23). 47 stringhe avevano perso la loro forma plurale nella traduzione: un sito in
  inglese mostrava «3 topic», «5 image», «2 year». Il contatore delle categorie del forum, scritto in francese in ogni
  lingua, è ora tradotto.

- **La documentazione del sito è aggiornata** (2026-09-23). Le dieci pagine del wiki fornite con il prodotto e quelle
  della demo erano indietro di una settimana rispetto alle guide; «Creare un widget» insegnava ancora una classe di
  Bootstrap 4. Un controllo le confronta ora con le guide.

- **Il modulo di risposta del forum sta su un telefono** (2026-09-23): l'elenco dei tipi di allegato consentiti, scritto
  senza spazi, formava una sola parola troppo larga per lo schermo.

- **Tutto il sito passato al vaglio, in ogni tema, ogni modalità e ogni larghezza** (2026-09-23). Un controllo automatico
  mostra ora ogni pagina dell'amministrazione nel suo tema e ogni pagina pubblica in ogni tema pubblico, in chiaro e in
  scuro, connesso come amministratore e come visitatore, in otto larghezze da 360 a 2560 px, con e senza contenuti.
  Questo primo passaggio ha corretto:
  - **icone rotte** negli elenchi di categorie della galleria, dei giochi, delle squadre e delle notizie, quando una
    categoria non aveva icona;
  - il **paese dei profili** della demo, scritto per intero: il profilo non mostrava nessun paese e cercava una bandiera
    che non si trovava;
  - gli **oggetti dei modelli email**, che mostravano `{{site_name}}` così com'era nell'elenco;
  - **tabelle troppo larghe per un telefono** (Bug tracker, download, storico del wiki, moderazione, sessioni,
    partecipanti a un evento, palmarès): ora scorrono dentro la loro cornice invece di allargare tutta la pagina;
  - gli **elenchi del forum** su un telefono: quattro colonne si dividevano 360 px e l'ultimo messaggio veniva tagliato;
    ogni voce ora si impila — icona e titolo, poi statistiche e ultimo messaggio;
  - la **barra del tema Nebula** su un telefono, che debordava dallo schermo;
  - tre file richiesti da pagine senza esistere, e due righe scritte nel registro a ogni controllo degli aggiornamenti
    finché nessuna versione è pubblicata.

- **I pulsanti «Modifica» ed «Elimina» non sono più sfalsati** (2026-09-22). Negli elenchi dell'amministrazione a due
  colonne — categorie delle notizie, categorie della galleria… — il secondo pulsante andava a capo, spostato sotto il
  primo. Ora restano affiancati.

- **La scheda «Archiviazione» del monitoraggio si mostra correttamente** (2026-09-22). L'indicatore veniva disegnato come
  un cerchio pieno invece del semicerchio previsto, con una cifra grezza al centro, e il valore e la percentuale, posti in
  alto, si sovrapponevano al tratto; ora stanno nell'incavo dell'indicatore. Il titolo «Informazioni server» della stessa
  pagina va a capo su due righe quando la colonna è stretta, invece di essere tagliato. Nel gruppo «Invio email», la riga
  «Trasporto email» mostrava `[object Object]` invece del metodo usato.

- **Un widget collocato senza impostazioni si mostra con i suoi valori predefiniti** (2026-09-22). Quando un tema colloca
  un widget all'installazione, o una vecchia disposizione lo ripristina, arriva senza impostazioni: scriveva allora avvisi
  nel registro a ogni pagina mostrata, e il menu di navigazione semplicemente spariva. Ora riceve i valori che il suo
  modulo avrebbe salvato per impostazione predefinita. I widget già configurati non cambiano.

- **La pagina degli eventi si mostra di nuovo** (2026-09-22). Mostrava una pagina di errore «non trovato» appena aveva
  eventi da distribuire su più pagine — sotto un titolo del tutto normale, il che rendeva difficile accorgersene.
  L'impostazione del numero di eventi per pagina veniva salvata come testo, e il codice, reso più rigoroso il giorno
  prima, la rifiutava. Tutti gli elenchi paginati accettano ora questa impostazione nelle sue due forme.

- **I voti dei sondaggi vengono registrati** (2026-09-22). Chi votava leggeva «Grazie per il tuo voto!», e il voto non
  veniva mai contato: nessuna opzione veniva riconosciuta come appartenente al sondaggio. Un voto che non punta a nessuna
  opzione del sondaggio viene ora rifiutato con un messaggio che lo dice, invece di essere accolto.

- **Il widget «Forum» mostra finalmente discussioni e messaggi** (2026-09-22). I suoi due tipi, «Ultimi argomenti» e
  «Ultimi messaggi», non selezionavano nessuna categoria del forum, e restavano quindi vuoti su ogni sito, quanti che
  fossero i messaggi.

- **Altre tre funzioni cadevano nella stessa trappola dei sondaggi** (2026-09-22), trovate dal controllo scritto per
  questo difetto:
  - **aprire una candidatura con campi personalizzati** faceva bloccare la pagina, dal lato del candidato come
    nell'amministrazione;
  - **una notifica che rimanda a un evento del calendario** si bloccava costruendo il suo link;
  - **la profondità massima delle risposte del forum** non veniva mai applicata: il calcolo si fermava al primo livello.

- **Tornare alla pagina 1 di un elenco non porta più a una pagina non trovata** (2026-09-22). Negli elenchi
  dell'amministrazione il cui numero di elementi per pagina è regolabile, il pulsante «1» costruiva un indirizzo che il
  sito non riconosceva.

- **La schermata di modifica di un campo del profilo personalizzato si apre** (2026-09-22). La schermata si bloccava a
  ogni apertura: non dava un'etichetta al suo pulsante di salvataggio.

- **Due pulsanti «Indietro» portavano a una pagina non trovata** (2026-09-22): dalla pagina di un'immagine, verso il suo
  album; da una modalità di gioco, verso il suo gioco.

- **«Candidati» non compare più dove la candidatura verrebbe rifiutata** (2026-09-22): su un'offerta chiusa o completa, e
  per chi non ha il diritto di candidarsi. Allo stesso modo, un amministratore che legge una conversazione riservata allo
  staff può ora invitarvi e segnalarvi un messaggio, invece di essere respinto dopo aver visto il pulsante.

- **I sottomenu del widget di navigazione si aprono** (2026-09-22). Un link che raggruppa altri link non apriva nulla al
  clic: portava ancora l'attributo della vecchia versione di Bootstrap, che il suo stesso script non cercava più.

- **Gli altri fumetti di aiuto dei moduli compaiono al passaggio del mouse** (2026-09-22). La correzione del giorno prima
  copriva solo una parte dei moduli: l'icona (i) accanto ai campi delle altre schermate dell'amministrazione, e i fumetti
  delle etichette, continuavano a non mostrare nulla — lo stesso difetto di attributo, nelle due librerie che li
  costruiscono.

- **Le etichette di menu accentate vengono tradotte** (2026-09-22). Un link di menu salvato dall'amministrazione —
  «Actualités», «Équipes» — viene memorizzato in forma codificata, e la sua traduzione non si trovava più: restava in
  francese nelle altre cinque lingue, con un avviso nel registro a ogni pagina mostrata.

- **Gli archivi del marketplace sono riproducibili** (2026-09-22). Portavano le source map `.map`, che il compilatore
  degli stili rigenera a ogni installazione e che il repository ignora: due archivi dello stesso addon, costruiti su due
  macchine, differivano anche se il codice non era cambiato. Gli archivi non portano più questi file.

- **Il widget «Palmarès» si può finalmente installare caricando un archivio** (2026-09-22). Era l'unico dei 61 addon
  distribuibili a non dichiarare la sua dipendenza dal nucleo; l'installer, che la richiede per riconoscere un addon, lo
  saltava SENZA UNA PAROLA — nessun messaggio, nessuna traccia.

- **La pagina di manutenzione non è più bianca** (2026-09-22). I due campi «Titolo» e «Contenuto» vengono forniti VUOTI,
  quindi la pagina non mostrava NULLA oltre al nome del sito — anche se l'anteprima dell'amministrazione prometteva un
  titolo e un testo. Un titolo e un messaggio predefiniti, tradotti nelle sei lingue, riempiono il vuoto; appena i campi
  sono compilati, sono loro a essere mostrati.

- **I pulsanti con contorno ritrovano il loro bordo** (2026-09-22). I temi ridefinivano solo la variante «primary» del
  pulsante con contorno, e la loro regola cancellava il bordo delle altre: le altre sei varianti usate dal prodotto — 90
  usi nel codice — sembravano semplici link. Il più visibile era «Apri il sito», nel banner di manutenzione, che aveva un
  bordo solo al passaggio del mouse.

- **Il codice tecnico non è più rosa nell'amministrazione** (2026-09-22). Il tema impostava solo il carattere dei
  frammenti `<code>`, che conservavano quindi il rosa di Bootstrap — un colore estraneo a tutte le tavolozze del prodotto.
  Molto visibile nell'elenco dei ruoli.

- **Il pulsante «Chiudi» delle finestre chiude finalmente la finestra** (2026-09-22). In TUTTE le finestre del prodotto,
  il pulsante «Chiudi» (o «Annulla») in basso non faceva nulla: serviva la croce in alto a destra. La causa: Bootstrap 5 ha
  rinominato l'attributo che fa scattare la chiusura, e il nucleo metteva ancora il vecchio nome; un browser ignora un
  attributo sconosciuto **in silenzio**. Il pulsante è anche diventato un vero pulsante — veniva reso come uno `<span>`, ed
  era quindi irraggiungibile da tastiera. Due salvaguardie impediscono che torni: il controllo del codice rifiuta il
  vecchio nome, e un passaggio nel browser, lanciato a mano, CLICCA il pulsante.

- **Il tema Nebula non mostra più il suo menu due volte** (2026-09-22). Ogni nuova installazione mostrava due volte il
  nome del sito e due volte il menu: il tema disegna la propria barra, e l'installazione collocava IN PIÙ un titolo e un
  menu subito sotto. Il menu vive ora nella barra in alto, dove compare una sola volta — ed è **configurabile**
  dall'amministrazione, cosa che prima non era: i link della barra erano scritti nel codice, quindi mancava «Contatto» e
  un modulo disattivato conservava il suo link. I siti già installati vengono corretti da una migrazione, che tocca
  l'intestazione solo se è rimasta come fornita.

- **Cambiare lingua non porta più a una pagina di codice** (2026-09-22). Il selettore di lingua del piè di pagina — quello
  di **cinque temi**, tra cui quello predefinito — mandava il visitatore su una pagina che mostrava `{"redirect":"/en/…"}`
  come testo grezzo, invece di portarlo alla pagina tradotta. Bisognava tornare indietro per uscirne.

  La causa: questo selettore è un vero modulo, che nulla intercettava, e puntava a un indirizzo tecnico che risponde
  sempre in JSON. Il server distingue ora una chiamata dalla finestra «Scegli la mia lingua», che si aspetta JSON, da un
  invio di modulo ordinario, che si aspetta di essere portato alla pagina. Trovato seguendo il gesto in un browser vero,
  cosa che nessun controllo aveva fatto fino ad allora.

- **I contenuti scritti in una sola lingua non mandano più le altre lingue su una pagina di errore** (2026-09-21). Una
  notizia scritta in francese proponeva ancora le altre cinque lingue nel suo selettore, e tutte e cinque rispondevano
  404: misurato sulla demo, **60 indirizzi morti su 72**. Il gesto più naturale di un visitatore straniero finiva su un
  errore. Ora viene servita la versione che esiste, con un avviso che lo dice nella lingua del visitatore e un link
  all'originale. Questo vale per le notizie, gli articoli e gli album (e le loro categorie), così come per le squadre.

  Due precauzioni inseparabili, senza le quali il rimedio costerebbe più del male: la pagina annuncia ora ai motori di
  ricerca solo le lingue che **esistono davvero**, e designa l'originale come indirizzo canonico — altrimenti verrebbero
  indicizzati sei indirizzi per un solo testo. **Nell'amministrazione, nessun ripiego**: una versione vuota deve
  mostrarsi vuota, è proprio quello che si viene a compilare. `tools/check-langues-contenu.php` fa rispettare tutte e tre
  le proprietà, nell'integrazione continua.

- **L'integrazione continua è di nuovo verde** (2026-09-21); sul ramo principale, era rossa dal 10 giugno. Quasi tutte le
  cause stavano negli strumenti di test e nella configurazione della CI, non nel prodotto. L'eccezione: con il server
  integrato di PHP 8.3, il sito poteva calcolare male il suo indirizzo di base e reindirizzare fogli di stile e script;
  ora lo deduce solo da uno `SCRIPT_NAME` che finisce con `index.php`. Una suite di test che non può essere eseguita ora
  fallisce invece di saltarsi in silenzio, e la maggior parte dei job della CI ha un limite di tempo.

- **Il logo dell'installer non si mostrava in una nuova installazione** (2026-09-21): il percorso era relativo alla
  cartella `install/`, che il server non serve a quell'indirizzo. L'immagine è ora incorporata nella pagina.

- **Componenti che non si aprivano più dal passaggio a Bootstrap 5** (2026-09-21). Bootstrap 5 ha dato il prefisso `bs-`
  a tutti i suoi attributi di dati. Un attributo rimasto con il vecchio nome non provoca alcun errore: il componente
  semplicemente non fa nulla. Questi erano morti in silenzio:

  | Cosa non funzionava più | Dove |
  |---|---|
  | **La fisarmonica delle FAQ** non si apriva | `modules/faq` |
  | **L'elenco dei partecipanti di un evento** non si apriva | `modules/events` |
  | **I popover di aiuto di una parte dei moduli** erano vuoti | nucleo — `neofrag/libraries/form.php` |
  | Il popover del profilo del forum, quello per eliminare un link di navigazione | `modules/forum`, `widgets/navigation` |
  | Il posizionamento dei fumetti nell'Editor live, l'intervallo del carosello | `modules/live_editor`, `widgets/slider` |

- **Impaginazioni rotte, anch'esse silenziose** (2026-09-21). Bootstrap 5 ha rinominato le sue utility direzionali; una
  classe rinominata non è più definita da nessuna parte, e l'elemento conserva la sua impaginazione predefinita senza che
  nulla lo segnali.

  - **I piè dei pannelli e delle finestre modali non si allineavano a destra.** Il nucleo costruiva la classe per
    concatenazione (`'float-'.$align`), quindi nessuna ricerca di `float-right` poteva trovarla. L'unico ponte di
    compatibilità del prodotto copriva solo le tabelle del tema dell'amministrazione: niente per i temi pubblici. Stessa
    causa per l'allineamento delle celle delle tabelle, nelle due librerie.
  - **Nessun campo di modulo rifiutato veniva segnato**, in nessun tema. Le due librerie mettevano un nome superato —
    `has-error` (Bootstrap 3) e `has-danger` (una versione preliminare di Bootstrap 4) — che nessun foglio di stile
    servito definisce. Il messaggio si mostrava, ma nulla indicava il campo interessato. Sono sostituiti da un marcatore
    proprio, definito una volta sola.
  - **Il piè di pagina** portava `<div class="float-right">`: «Realizzato con NeoFrag Reborn» scendeva su una nuova riga, a
    sinistra. Il valore viene da lontano — una migrazione di Alpha 0.2.2 l'aveva cambiato da `pull-right` a `float-right`
    ai tempi di Bootstrap 4, e il passo successivo non è mai stato fatto. Una migrazione (`2026_09_20_bootstrap5_float`)
    corregge i siti esistenti; il seed fornisce ora `float-end`.
  - E le ultime classi morte nelle viste: la barra di debug su un telefono, lo spazio degli elenchi di eventi, le
    etichette dell'editor dei menu, le due barre del voto del reclutamento — che uscivano dello **stesso colore**, così il
    grafico non distingueva più i pareri favorevoli da quelli sfavorevoli.

- **Il corpo di un articolo poteva essere cancellato alla visualizzazione** (2026-09-21). La costruzione dell'indice
  sostituiva il testo con il risultato di un trattamento che può arrendersi davanti a un articolo lungo: la pagina
  rispondeva allora normalmente, con un articolo **vuoto**, e nulla nei registri.

- **Un widget collocato in cima o in fondo alla pagina spariva.** Il tema *Nebula* proponeva le posizioni «Intestazione» e
  «Piè di pagina» nell'editor delle disposizioni, ma non le mostrava. Il widget veniva sì salvato, e non compariva nulla
  — senza messaggio. Gli altri quattro temi pubblici non erano toccati.

- **Il widget di navigazione proponeva due moduli interni** (`live_editor` e `pages`) che avrebbe dovuto nascondere. Lo
  stesso difetto faceva perdere il nome del modulo alle statistiche dell'amministrazione, senza effetto visibile finché due
  moduli non usano lo stesso nome di statistica.

- **Il registro di debug cresceva senza limite** quando era acceso. Ora ha un tetto: oltre i 64 MB, ricomincia
  conservando la generazione precedente.

- **Su un sito inglese, «No» restava in francese («Non»).**

- **Cinque pagine dell'amministrazione non offrivano alcuna via di ritorno** (2026-09-20): la scheda di un membro, il log
  di audit, l'aggiunta di un gruppo, l'elenco delle sessioni e i file del monitoraggio. Le briciole di pane rendono
  cliccabile il nome del modulo solo se il modulo dichiara di avere una pagina iniziale di amministrazione; `user` e
  `monitoring` dichiaravano il contrario pur avendone una. Una volta su queste pagine, bisognava passare dal menu laterale
  o dal pulsante indietro del browser.

- **I motori di ricerca ricevevano JSON invece della mappa del sito.** Richiesta nella radice, senza prefisso di lingua —
  l'unico modo in cui la richiede un robot —, `/sitemap.xml` rispondeva `{"redirect":"/fr/sitemap.xml"}` con il codice di
  stato 200, e `robots.txt` e `humans.txt` facevano lo stesso. Il reindirizzamento che aggiunge il prefisso di lingua
  rispondeva in JSON ogni volta che l'indirizzo finiva con `.txt`, `.xml` o `.json`. Poiché questi file non hanno una
  versione per lingua, non passano più da questo reindirizzamento: vengono serviti direttamente, con il loro vero tipo di
  contenuto.

- **`/favicon.ico` rispondeva 404.** I browser richiedono questo indirizzo qualunque cosa dichiari la pagina; ora porta
  alla favicon configurata nelle impostazioni, o a quella del CMS.

- **Attivare l'autenticazione a due fattori si bloccava.** Al salvataggio dei codici di recupero, un errore interrompeva
  la pagina: l'account finiva segnato come «protetto» senza un solo codice di emergenza. Trovato da un test scritto
  rafforzando la tipizzazione di questa libreria; corretto, e il test resta.

- **Un limite di «un solo tentativo» non bloccava mai.** La soglia veniva controllata solo dal secondo tentativo. Nessun
  effetto sulle soglie fornite, ma il limite di segnalazioni della moderazione, che si può abbassare fino a 1, non
  bloccava allora mai. La soglia vale ora dal primo tentativo.

- **Alcuni testi restavano in francese nelle altre lingue, e il registro continuava a riempirsi.** Tre cause, tutte
  corrette: il titolo di una pagina del wiki, di un tipo di evento o di una campagna di donazioni passava dalla traduzione
  come se fosse testo dell'interfaccia (non ha traduzione: è contenuto); un testo già tradotto passava di nuovo dalla
  traduzione — «Espace membre» diventava «Member area», poi «Member area» veniva cercato come chiave e segnalato mancante
  a ogni visualizzazione; e trenta stringhe dell'interfaccia (il campo «Nome utente o indirizzo e-mail» del modulo di
  accesso, titoli di pannello nell'amministrazione dei giochi e del reclutamento, i social network del profilo…) non
  avevano traduzione in **nessuna** lingua, perché il controllo che verifica le sei lingue guardava solo le chiamate di
  traduzione esplicite, non i titoli di campi e intestazioni tradotti più avanti. Il controllo ora li vede; le trenta
  stringhe sono tradotte in inglese, tedesco, spagnolo, italiano e portoghese.

- **Google Analytics aspetta ora il tuo consenso.** Se è configurato un ID di Analytics, lo script di Google si carica ora
  solo dopo un clic su «Accetta tutto» nel banner dei cookie, mai prima. Inoltre, la politica di sicurezza del sito
  bloccava questo script in ogni caso: Analytics non funzionava, e nulla lo diceva. Ora funziona, e solo con il consenso
  del visitatore.

- **Tre funzioni dell'amministrazione non rispondevano più da giugno.** L'ordinamento delle tabelle cliccando
  sull'intestazione di una colonna, il riordino per trascinamento di categorie, forum e sottoforum, e i pulsanti
  Aperto/Chiuso delle pagine Manutenzione e Iscrizioni: i loro script presupponevano ancora la presenza di jQuery,
  benché sia stato tolto dal CMS a giugno 2026, e si fermavano di colpo al caricamento della pagina — senza messaggio, i
  pulsanti restavano semplicemente inerti. Tutti e tre sono stati riscritti senza jQuery, sono coperti da test nel
  browser, e un controllo automatico rifiuta ora ogni script che richiami jQuery.

- **Un indirizzo malformato causava un errore del server.** Una richiesta come `/fr/a:80` o `/:80`, di quelle che mandano
  i robot, faceva bloccare il calcolo dell'estensione dell'indirizzo e restituiva un errore 500 al visitatore. Il sito
  risponde ora con un normale 404.

- **Il modulo di contatto scriveva «Da:» con l'indirizzo del visitatore.** Inviare un messaggio dal proprio server a nome
  di `visiteur@example.org` è esattamente ciò che rifiutano le protezioni contro la falsificazione dei fornitori di posta
  (SPF, DMARC): il messaggio finiva nello spam o veniva rifiutato. Il mittente è ora l'indirizzo di contatto del sito, e
  l'indirizzo del visitatore è messo in «Rispondi a», che in pratica è la stessa cosa. Stessa correzione per le risposte
  a una candidatura, che potevano partire con l'indirizzo personale dell'amministratore.

- **L'installer poteva salvare un indirizzo di contatto non valido.** Quando il sito veniva installato accedendovi dal
  suo indirizzo IP, l'indirizzo di contatto diventava `noreply@<IP>`, che i fornitori di posta rifiutano — e non partiva
  più nessuna email, senza alcun messaggio di errore visibile. L'indirizzo viene ora dedotto solo da un vero nome di
  dominio.

- **Un banner vuoto compariva nelle pagine in cui una zona della disposizione è vuota.** Nel tema Extend, ogni pagina
  diversa dalla pagina iniziale mostrava un'ampia fascia colorata che non conteneva nulla, oltre a una sezione vuota sopra
  il contenuto.

  La causa non era il tema: una zona dichiarata ma senza widget restituiva spazi invece di niente del tutto, quindi i temi
  disegnavano la loro cornice attorno al nulla. Corretto una volta per tutti i temi. Se costruisci un tema, puoi scrivere
  `if ($zone = $this->output->region('banner'))` (o `zone(n)`): la condizione ora dice il vero.

- **Il tema Extend mostrava due volte la sua menzione «Realizzato con NeoFrag Reborn».** La sua disposizione fornita
  metteva nel piè di pagina un blocco che ripeteva ciò che il tema scrive già da sé. I siti già installati vengono
  corretti automaticamente all'aggiornamento; i nuovi non l'avranno mai.

- **Cercare una parola comune restituiva una pagina di errore.** Una ricerca come «le» o «de» mostrava i suoi risultati
  sullo schermo, ma il server rispondeva **404** — che è ciò che vedono i motori di ricerca, gli strumenti di
  monitoraggio e i browser, e che faceva sembrare scomparsa una pagina perfettamente valida.

  L'evidenziazione delle parole trovate presupponeva che la parola cercata comparisse per forza nel testo mostrato. È
  falso due volte: la ricerca esamina **più campi** e ne mostra uno solo — una discussione del forum intitolata «Salut
  tout le monde !» corrisponde a «le» per il suo titolo, non per il messaggio mostrato sotto — e il database confronta
  **senza tenere conto degli accenti**, mentre il codice ne teneva conto: «éléphant» usciva sì per la ricerca «ele»,
  senza che vi venisse evidenziata alcuna parola.

  Entrambi i casi sono trattati: l'evidenziazione riconosce ora le varianti accentate di una lettera, come fa il
  database, e quando la parola davvero non è nel testo mostrato, l'estratto comincia semplicemente dal suo inizio.

- **Gli aggiornamenti automatici del nucleo non potevano funzionare** — cinque difetti, ciascuno sufficiente da solo:
  l'indirizzo per controllare le nuove versioni non era impostato da nessuna parte, quindi il pulsante non compariva mai;
  un interruttore globale, da impostare nella configurazione, bloccava l'operazione; il download puntava alle versioni
  del NeoFrag originale, che avrebbero sovrascritto NeoFrag Reborn; il pacchetto conservava i suoi file in una
  sottocartella, quindi un aggiornamento **non avrebbe sostituito nulla, senza il minimo errore**; e l'archivio veniva
  estratto con un'interfaccia PHP deprecata da PHP 8.0.

  L'interruttore è sostituito da quattro garanzie: l'origine delle versioni deve essere in una **lista bianca di
  indirizzi** (la stessa del marketplace); il file della versione dà solo un **nome di file**, mai un indirizzo;
  l'impronta **SHA-256** del pacchetto viene verificata **prima** di toccare un solo file; ogni file dell'archivio viene
  controllato (nessun percorso che esca dal sito, nessun link simbolico). Le cartelle `config/` e `install/` non vengono
  mai riscritte se esistono già.

- **Il catalogo del marketplace era irraggiungibile da qualsiasi sito.** Il server che lo distribuisce rispondeva
  `{"redirect":"/fr/…"}` **con il codice di stato 200** invece del file statico — un corpo perfettamente valido come JSON,
  e perfettamente sbagliato, quindi un fallimento silenzioso che faceva ricadere ogni sito sul suo catalogo locale. La
  causa era la configurazione di quel server, ora corretta: niente da fare sui siti. Nel codice, l'aggiornamento del
  nucleo convalida ora la forma dei suoi manifest, non più solo se si leggono come JSON.

- **Il pulsante «Elimina» di un tema**, in «Temi & addon», proteggeva solo il tema dell'amministrazione, con il suo nome
  scritto nel codice. Ora segue la dichiarazione del tema, e protegge quindi anche Nebula.

- **Accoppiamenti dal nucleo verso il facoltativo.** Sette moduli interrogavano le tabelle di moduli facoltativi: cinque
  del nucleo (`admin`, `moderation`, `settings`, `statistics`, `user`), più `games` e `teams`. Due query erano senza
  protezione: in `teams`, il controllo di esistenza di `recruits` era posto **dopo** la query che doveva proteggere, e in
  `games`, eliminare un gioco interrogava la tabella delle squadre senza verificare che esista. Le altre erano protette ma
  mute: portano ora un'annotazione verificata dalla CI. L'unico ciclo duro del pacchetto (`games` ↔ `teams`) è spezzato.

- **Editor live: le modifiche compaiono senza ricaricare la pagina.** Eliminare un widget lo lasciava sullo schermo e
  aggiungere una riga non la mostrava finché la pagina non veniva ricaricata, anche se il server aveva salvato la
  modifica. Lo script si aspettava un formato di risposta diverso da quello che invia il server; il difetto risaliva alla
  rimozione di jQuery.
- **Editor live: il trascinamento dei widget funziona di nuovo.** Con la modalità Colonne attiva, un widget aggiunto
  restava bloccato in fondo alla sua colonna e non si poteva spostare sopra il contenuto della pagina. Le righe e le
  colonne non erano toccate.
- **Editor live: i widget senza impostazioni si possono di nuovo aggiungere.** Dieci widget che non hanno una schermata
  di impostazioni (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`,
  `teams`) non si potevano aggiungere: l'aggiunta falliva con un errore 404 e nessun messaggio utile.
- **Un errore del server non si mostra più come contenuto.** Quando un'azione eseguita senza ricaricare la pagina
  riceveva un errore dal server (una pagina 404, per esempio), alcune schermate inserivano quella pagina di errore
  nell'interfaccia. L'errore viene ora trattato come un fallimento.
- **Editor live: il modulo delle impostazioni corrisponde sempre al widget scelto.** Cambiando rapidamente widget o tipo,
  la risposta più lenta poteva mostrare il modulo di un altro widget. La richiesta superata viene ora annullata, e il
  modulo non viene più ricaricato (né l'inserimento in corso perso) quando la selezione non è cambiata.
- **La porta del database viene ora rispettata.** Il CMS si collegava sempre sulla porta 3306, qualunque porta fosse
  stata inserita nell'installer guidato: non si poteva installare né far funzionare su un server MySQL o MariaDB in
  ascolto altrove. La porta viene ora salvata in `config/db.php` (solo se diversa da 3306) e usata per ogni connessione.
- **Un tema attivato non è più vuoto.** Attivare Forge, Granite, Blockcraft o Extend cambiava il tema senza applicare la
  sua disposizione predefinita, che si applicava solo con il pulsante «Reinstalla predefinito»: il sito non mostrava
  nessun widget. L'attivazione applica ora la disposizione predefinita del tema se non ne ha ancora una, senza mai
  sovrascrivere una disposizione personalizzata.
- **Cambiare il tema predefinito vale ora anche per i visitatori che ne avevano scelto uno.** Il selettore di tema del
  piè di pagina ricordava la scelta di un visitatore per un anno, e quella scelta prevaleva sul tema predefinito: quando
  l'amministratore cambiava tema, i visitatori, e anche l'amministratore, restavano sul vecchio, senza via d'uscita da un
  tema senza selettore. Una scelta fatta prima dell'ultimo cambio di tema predefinito viene ora dimenticata; una fatta
  dopo resta rispettata.
- **Il browser non conserva più copie scadute delle pagine.** In mancanza di istruzioni, poteva rimostrare una vecchia
  versione di una pagina: un cambio di tema, per esempio, compariva solo dopo un ricaricamento forzato (Ctrl+F5). Le
  pagine e le risposte del sito vietano ora questa conservazione (`Cache-Control: no-store`); immagini, stili e script,
  serviti direttamente dal server web, non sono toccati.

### Sicurezza

- **Un valore salvato non può più iniettare tag in un modulo** (2026-09-23). Il modulo di modifica metteva il valore di un
  campo nella pagina proteggendo le virgolette come farebbe una stringa PHP — il che in HTML non ha alcun effetto. Un
  valore che non fosse passato dal modulo stesso (importazione, dati di dimostrazione, un altro modulo) poteva quindi
  uscire dal suo campo e aggiungere codice alla schermata di amministrazione: una citazione la cui fonte era
  `"><img src=x>` l'ha dimostrato. Campi di testo, aree di testo, elenchi, caselle e pulsanti di opzione codificano ora il
  loro valore; la resa di un valore inserito normalmente non cambia. Anche le notifiche («Citazione modificata», ecc.)
  vengono passate allo script della pagina in una forma che non può più romperlo.

- **La politica di sicurezza dichiara ora una regola per i media** (`media-src`). Non ne aveva e ricadeva sulla regola
  generale (`default-src 'self'`). Ora consente i media del sito stesso e i media incorporati nella pagina
  (`'self' data:`); l'indirizzo del flusso di una web radio viene aggiunto **solo se un flusso è configurato**: un sito
  senza web radio non consente alcuna origine esterna aggiuntiva.

- **Undici avvisi di sicurezza chiusi sulle dipendenze** (2026-09-20): `league/commonmark` passa dalla **2.8.2 alla
  2.10.1** (dieci avvisi, otto dei quali gravi) e `phpseclib` dalla **3.0.52 alla 3.0.57** (un avviso di gravità media).

  Nove dei dieci avvisi di commonmark sono **denial of service tramite Markdown costruito ad arte** — analisi in tempo
  quadratico, titoli con ancore che collidono, note a piè di pagina duplicate, blocchi di attributi adiacenti, uscita XML
  profondamente annidata — e il decimo è una **vulnerabilità XSS**: il filtro degli attributi `on*` si poteva aggirare con
  un salto pagina U+000C. La libreria rende il Markdown del wiki, degli articoli e delle FAQ, scritto dai membri. Diversi
  di questi avvisi riguardano estensioni che il prodotto non attiva (note a piè di pagina, attributi, ancore dei titoli,
  uscita XML); l'aggiornamento li chiude tutti.

  La resa non cambia, e non è solo un'impressione: **dodici casi rappresentativi** — titoli, tabella, elenco di
  attività, blocco di codice, HTML iniettato, link `javascript:`, citazione, testo barrato, link automatico, elenchi
  annidati, immagine, entità e nota a piè di pagina, annidamento profondo —, resi dalle due versioni con la
  configurazione reale del prodotto, danno lo stesso HTML **byte per byte** (1568 byte, stessa impronta).

  Nota: Composer **si rifiuta ora di installare** la 2.8.2, proprio per questi dieci avvisi. Si è dovuta disattivare la
  politica in una cartella usa e getta per fare il confronto.

## [1.1.0] — 2026-08-23

### Sicurezza
- **Revisione di sicurezza di tutto il codice**, e correzione delle falle gravi che ha rilevato:
  - **Backup**: gli archivi di backup (una copia del database e della cartella `config/`, che contiene le password) non
    si possono più scaricare da un semplice indirizzo web, né con Apache né con nginx; solo l'amministrazione
    (Monitoraggio) li consegna. Il loro nome contiene ora una parte casuale, impossibile da indovinare. La cartella dei
    log (`logs/`) è protetta allo stesso modo.
  - **Token segreti**: gli identificativi di sessione, i token dei moduli e i link inviati per e-mail (password
    dimenticata, convalida dell'account) vengono da un generatore casuale sicuro; erano prevedibili. Questi link
    scadono dopo un'ora, e una nuova richiesta annulla la precedente.
  - **Accesso**: l'autenticazione a due fattori (2FA) e i ban vengono verificati qualunque sia il modo di accedere —
    password, account Discord, Google o GitHub, link «password dimenticata» —, e non più solo con la password.
  - **Link nelle e-mail**: l'indirizzo del sito usato nei link inviati per e-mail, al ritorno da un accesso tramite
    Discord, Google o GitHub e al ritorno da un pagamento Stripe è quello salvato all'installazione (`config/url.php`),
    e non più quello che la richiesta dichiara: un attaccante non può più far inviare un link che porti al proprio sito.
  - **Pagine di amministrazione**: un nome di allegato truccato (in una segnalazione di moderazione) o un falso
    indirizzo IP (intestazione `X-Real-IP`, nella cronologia delle sessioni) potevano iniettarvi codice; ora vengono
    verificati e neutralizzati. Il messaggio di benvenuto di un server di gioco, fornito da un servizio esterno, viene
    ripulito prima di essere mostrato.
  - **Azioni di amministrazione**: eliminare, attivare o disattivare, chiudere, approvare, ripristinare, svuotare…
    richiedono ora un token di sicurezza nei 23 moduli che non ne avevano. Erano semplici link o moduli senza token: un
    link truccato, cliccato da un amministratore connesso, bastava a scatenare l'azione.
  - **Strumenti**: gli script della cartella `tools/` si eseguono solo da riga di comando, mai da un indirizzo web; a
    ogni modifica, l'integrazione continua verifica che nessuna dipendenza PHP abbia una falla nota (`composer audit`).
  - **Pulsanti resi muti dalla politica di sicurezza (CSP)**: la CSP rigorosa del sito blocca le azioni scritte
    nell'HTML (`onclick="…"`), per cui questi pulsanti non facevano nulla: banner dei cookie, numero di righe delle
    tabelle di amministrazione, conferme del cestino e delle revisioni, nome del file allegato nella messaggistica,
    azioni di gruppo delle menzioni del forum, selezione di un campo al clic, pulsante della finestra di eliminazione.
    Sono ricollegati in un altro modo e funzionano anche nei contenuti caricati senza ricaricare la pagina. Il selettore
    del numero di righe chiamava inoltre jQuery, che era già stato rimosso.
- **Politica di sicurezza dei contenuti (CSP) rigorosa**: servita dal sito con un token proprio di ogni pagina, rifiuta
  gli script scritti nella pagina senza quel token (`unsafe-inline`), il codice costruito al volo (`unsafe-eval`) e gli
  oggetti incorporati (`object-src 'none'`); gli script arrivano ormai solo dal sito e da un breve elenco di indirizzi,
  dato che TinyMCE e CodeMirror sono ora ospitati dal sito stesso.
- **Sessione rinnovata all'accesso**: l'identificativo di sessione cambia quando si accede; un identificativo imposto
  prima dell'accesso non dà più accesso alla sessione aperta.
- **Segreti cifrati nel database**: la password SMTP e la chiave dell'autenticazione a due fattori (2FA) sono cifrate
  (AES-256-GCM); una copia del database non le consegna più in chiaro. Un valore esistente viene cifrato al successivo
  salvataggio.
- **Webhook**: un webhook non può più puntare alla rete interna del server (indirizzi privati, locali o riservati
  rifiutati, reindirizzamenti non seguiti).

### Aggiunto
- **File CSS e JavaScript sempre aggiornati**: ogni file viene richiesto con la sua data di modifica (`?v=…`), così il
  browser lo ricarica appena cambia — compreso un file sostituito da un tema o caricato a mano —, senza dover aumentare
  `nf_version_css` (che resta usato quando il file non si trova).
- **Marketplace**: la finestra «Aggiornamenti» della pagina *Temi & Addons* segnala che è disponibile una nuova
  versione di NeoFrag Reborn; l'aggiornamento del nucleo stesso resta manuale.
- **Aggiornamenti degli addon**: la stessa finestra confronta gli addon installati con il catalogo del marketplace e
  installa le nuove versioni, con le loro migrazioni del database.
- **Installer**: nuovo aspetto — logo di NeoFrag Reborn, banner «Fork non ufficiale di NeoFrag», riconoscimento a
  Michaël BILCOT e Jérémy VALENTIN (licenza LGPLv3) a piè di pagina, caratteri Inter e Space Grotesk, colori turchese.
- **File**: un gestore di file (albero delle cartelle, caricamento, permessi di lettura per file o per cartella), portato
  da HiddenCMS, di HiddenBlob (LGPLv3). Incluso nel nucleo.
- **Emoji personalizzate** (modulo Emoji, incluso nel nucleo): l'amministrazione aggiunge immagini con un nome, che
  `:nome:` mostra nei messaggi e nelle firme del forum, nelle notizie, nelle pagine, negli eventi e nel reclutamento,
  tra gli altri.
- **Newsletter**: invio programmato alla data scelta, a piccoli lotti trattati dall'attività pianificata del sito (cron)
  invece di inviare tutto in una volta; monitoraggio delle aperture (tasso di apertura per campagna, misurato con
  un'immagine invisibile); modelli di e-mail riutilizzabili; scelta dei destinatari: tutti gli iscritti, solo i membri
  o un gruppo.
- **Eventi**: un evento può ripetersi ogni giorno, ogni settimana o ogni mese, per il numero di volte scelto — ogni data
  diventa un evento a tutti gli effetti. I partecipanti, tranne quelli che hanno declinato, ricevono un promemoria sul
  sito (campanella delle notifiche) prima dell'evento, 24 ore prima per impostazione predefinita; i promemoria passano
  per l'attività pianificata del sito (cron).
- **Reazioni con emoji**: sei reazioni a scelta (👍❤️😂😮😢😡) invece di un solo cuore, come su Discord o Facebook, ognuna
  con il suo conteggio; i vecchi «mi piace» diventano ❤️.
- **Widget «Stato live»** (l'ex widget Twitch): segue più canali alla volta, su Twitch e su YouTube (credenziali Twitch
  e chiave API YouTube da fornire), con gioco, spettatori, titolo, miniatura e player integrato.
- **Pagine composte**: sotto il suo testo, una pagina può mostrare blocchi di altri moduli (ultime notizie, notizie di
  una categoria, ultimi articoli, download popolari), disposti nell'ordine voluto e impostati uno per uno dalla modifica
  della pagina. Lo shortcode accetta anche impostazioni, per esempio `[block:news.latest count=2]`.
- **Tema Extend**, di Chewbaka, portato su NeoFrag Reborn (Bootstrap 5, modalità giorno e notte), con licenza
  CC BY-NC-SA 4.0; disponibile nel marketplace.
- **Ricerca istantanea**: il widget di ricerca propone risultati dalla seconda lettera digitata (notizie, forum,
  pagine).
- **Ordinamento degli elenchi di amministrazione**: dieci elenchi (notizie, articoli, annunci, download, FAQ, libro
  degli ospiti, link, media, sondaggi, wiki) si ordinano secondo il criterio scelto.
- **Avatar animati**: una GIF animata caricata come avatar resta animata — sempre se il server dispone di Imagick,
  altrimenti finché non supera i 250 × 250 pixel.
- **Permessi**: la griglia dei permessi si apre in una finestra, senza lasciare la pagina.
- **Gestore di file del webmaster** (Monitoraggio, pulsante «Gestisci file»): sfogliare i file del sito, modificarli in
  un editor (CodeMirror), crearne, rinominarli o eliminarli. Le cartelle `config/`, `logs/` e `backups/` restano fuori
  portata; ogni salvataggio conserva una copia `.nfbak` ed entra nel registro di audit. Ogni scrittura richiede la
  **password webmaster**, distinta da quella dell'account e conservata fuori dal database (definita all'installazione o
  nel Monitoraggio, scheda «Sicurezza webmaster»); una volta definita, viene richiesta di nuovo anche per eliminare o
  svuotare i backup e per cambiare la chiave dell'attività pianificata.
- **Note di rilascio da un'unica fonte**: `tools/changelog-section.php` estrae una versione di questo registro delle
  modifiche (`CHANGELOG.md`), in Markdown o in HTML; le note delle release GitHub ne derivano, e i loro testi non
  possono più divergere.

### Modificato
- **Installer «tutto compreso»**: la procedura guidata passa a quattro passaggi (Requisiti → Database →
  Amministratore → Terminato). Tutti i moduli, widget e temi inclusi vengono installati e attivati al passaggio
  «Database», e la home page mostra le notizie appena l'installazione è finita; il passaggio «Moduli» e i suoi profili
  scompaiono.
- **Bootstrap 4.6.2 → 5.3.8, e jQuery non viene più caricato**: il JavaScript del sito si appoggia a un piccolo
  strumento interno (`window.NF`). Nove plugin jQuery o Bootstrap 4 sono sostituiti (notifiche → toast di Bootstrap 5,
  selectize → Tom Select, selettore di date → flatpickr, FullCalendar 3 → 6, selettori di colore e di icona, albero e
  indicatori → codice interno, barre di scorrimento → scorrimento nativo), e jQuery UI da SortableJS. Quattro script
  dimenticati chiamavano ancora jQuery: sono riscritti nella 1.2.0.
- **Modalità scura**: tabelle, schede, menu a tendina ed editor di testo prendono i colori scuri del tema invece di
  restare bianchi, in tutti i temi.
- **Impostazioni dei widget e disposizione delle pagine**: salvate in JSON invece che nel formato di serializzazione di
  PHP, il che chiude una via di iniezione di codice; le vecchie impostazioni continuano a essere lette.
- **Grafici delle statistiche**: Chart.js (licenza MIT) sostituisce Highstock, una libreria commerciale che impediva di
  ridistribuire liberamente il CMS.

### Corretto
- **MySQL 8**: aprire una discussione o rispondere nel forum, dividere o unire discussioni, avviare una conversazione
  nella messaggistica, riordinare le immagini dello Slider… fallivano con un errore fatale su MySQL 8, che rifiutava il
  modo in cui il sito apriva le sue transazioni. Ora passano per le funzioni di mysqli previste a questo scopo.
- **Emoji**: un'emoji — o qualsiasi carattere a 4 byte — in un testo provocava un errore del database. La connessione e
  tutte le tabelle passano a `utf8mb4` (collation `utf8mb4_unicode_ci`, riconosciuta sia da MySQL 8 sia da
  MariaDB 10; le collation proprie di MariaDB 11, che facevano fallire l'installazione altrove, scompaiono).
- **`where('col', [])`** generava una condizione vuota (→ `DELETE`/`UPDATE` su tutta la tabella): ora produce `1 = 0`
  (insieme vuoto).
- **Date oltre il 2038**: l'invio programmato di una newsletter, la pubblicazione programmata di una notizia, di un
  articolo, di una galleria o di una pagina, e le date di un evento non potevano superare il 19 gennaio 2038: oltre,
  MariaDB rifiutava il salvataggio, senza alcun messaggio. Queste date arrivano ora fino all'anno 9999; le installazioni
  esistenti sono aggiornate tramite una migrazione.
- **`/user/login` e `/user/registration` rispondevano «pagina non trovata»**: i pulsanti «Accedi» e «Registrazione» dei
  temi portano lì quando JavaScript è disattivato o il link viene aperto in una nuova scheda. Questi indirizzi aprono
  ora la finestra di accesso o di registrazione.
- **Nebula**: il pulsante «Registrazione» restava visibile quando le registrazioni erano chiuse, e portava a una pagina
  non trovata. Ora è nascosto in quel caso.
- **Widget Discord**: indica la vera causa di un errore (widget disattivato sul server, identificativo sconosciuto,
  rete) invece di un messaggio generico, mostra il vero logo del server (letto nell'invito pubblico) e conta
  correttamente i membri online — l'elenco fornito da Discord si ferma a 100, il che falsava il conteggio dei server
  grandi.
- **Widget TeamSpeak, vista ad albero**: andava in crash con PHP 8 e ripiegava sulla scheda semplice. L'albero dei
  canali e dei client è ora disegnato dal widget stesso, con icone Font Awesome (non serve più un pacchetto di icone);
  un errore di rete mostra un messaggio generico, senza rivelare l'indirizzo né la porta del server.
- **Widget Discord, Twitch e Server di gioco**: mostravano «irraggiungibile» anche quando il servizio rispondeva (la
  risposta veniva decodificata due volte). Le richieste del sito inviano inoltre un identificativo del browser
  (User-Agent): senza di esso, i servizi protetti da Cloudflare rispondevano con un rifiuto (errore 403).
- **Backup e Monitoraggio**: creare un backup andava in crash sui server con PHP-FPM, e falliva con nginx o Plesk, che
  rispondevano «pagina non trovata» agli indirizzi in `.json`; eliminare o scaricare un backup portava a una pagina non
  trovata; l'albero del Monitoraggio mostrava un errore al primo aggiornamento. Tutto corretto.
- **Marketplace irraggiungibile**: URL predefinito passato a **senza www** (`https://neofrag-reborn.xyz/marketplace`).
- **Caricamento di più file**: il primo file di un caricamento multiplo faceva fallire il caricamento («pagina non
  trovata») — era ciò che bloccava il caricamento nel modulo File. Per gli sviluppatori: `uploaded_file()` scambiava
  l'indice `0` per l'assenza di indice.
- **Webhook**: la registrazione di un membro (`user.registered`), un nuovo commento (`comment.created`) e una nuova
  discussione del forum (`forum.topic`) attivano finalmente il loro webhook; questi eventi venivano proposti ma mai
  emessi.

### Documentazione
- **Documentazione** (guide della wiki): per gli sviluppatori, i moduli (`form()` e `form2()`) e la validazione delle
  impostazioni di un widget; per gli amministratori, la risoluzione dei problemi di installazione, un esempio di
  creazione di un ruolo e cosa fare quando il marketplace è irraggiungibile. Una nota spiega come configurare nginx o
  Plesk, che rispondevano «pagina non trovata» agli indirizzi in `.json`.

---

## [1.0.0] — 2026-06-06 · base di Reborn (su Alpha 0.2.4)

Primo ciclo del fork: modernizzazione della base, rafforzamento della sicurezza e un'ampia ondata di nuove
funzionalità.

### Aggiunto

**Piattaforma e strumenti**
- **Migrazioni del database** numerate (`tools/migrate.php`, con un comando `baseline` per riprendere un database
  esistente) e primi test automatici (PHPUnit).
- **Nuovo installer web**, che sostituisce quello del NeoFrag originale: una procedura guidata in cinque passaggi
  (Requisiti → Database → Moduli → Amministratore → Terminato). Il passaggio «Moduli» proponeva tre profili di sito
  (Community, Gaming / eSport, Sito semplice) e poteva aggiungere addon dal marketplace.
- **Marketplace remoto** (catalogo e archivi serviti da neofrag-reborn.xyz): l'installer (passaggio «Moduli») e
  l'amministrazione (pulsante «Marketplace» della pagina *Temi & Addons*) vi aggiungono con un clic gli addon del
  catalogo ufficiale che non sono inclusi nel sito. Sicurezza: HTTPS obbligatorio, impronta SHA-256 verificata, archivio
  controllato voce per voce (nessun percorso che esca dalla cartella dell'addon, nessun collegamento simbolico),
  indirizzo del catalogo fisso, dimensioni e tempi limitati.
- **Strumenti per costruire i pacchetti**: `package-addons` prepara il catalogo del marketplace, `build-release` gli
  archivi da caricare via FTP.
- **Script di manutenzione** (`tools/maintenance.php`, da lanciare con un'attività pianificata): svuota il cestino di
  ciò che vi giace da più di 30 giorni (regolabile) ed elimina gli account mai confermati dopo 7 giorni, quando la
  convalida delle registrazioni è attivata — mai un amministratore.

**Temi e interfaccia**
- **Quattro temi creati per NeoFrag Reborn**: Nebula, pensato per i siti di community, Forge, Blockcraft e Granite.
  Variabili di colore comuni (`--nf-*`) vestono moduli e widget dei colori di ciascun tema.
- **Rifacimento completo dell'amministrazione**: nuovo aspetto, barra laterale, tavolozza dei comandi (Ctrl+K),
  modalità chiara e scura.
- Selettori di **tema** e di **lingua** a piè di pagina (visitatori compresi).
- **Menu**: costruttore di menu a più livelli, utilizzabile nel widget Navigazione (opzione «Menu gestito»).

**Contenuti**
- **Notizie e articoli**: pubblicazione programmata — il contenuto compare all'ora scelta, e notifiche, webhook e punti
  partono solo in quel momento, non più al salvataggio (un indirizzo da far chiamare a un'attività pianificata,
  protetto da una chiave); azioni di gruppo; ricerca, filtri e paginazione nell'amministrazione; una pagina dedicata
  per leggere una notizia. Gli articoli ricevono un'immagine in evidenza, come già le notizie, e il contatore delle
  visualizzazioni delle notizie, che non aumentava mai, finalmente conta.
- **Pubblicazione programmata** anche per pagine, gallerie ed eventi (per gli eventi, data di pubblicazione distinta
  dalla data in cui si svolgono).
- **Wiki**: fa da documentazione sul sito (sommario a schede, resa Markdown affidabile); ricerca e paginazione;
  confronto visivo di due revisioni di una pagina, riga per riga.
- **Media**: titolo e descrizione modificabili per ogni file, ricerca, filtro per tipo e paginazione. Ricerca, filtri e
  paginazione anche nell'amministrazione di Download, Elenco link, FAQ, Sondaggi e Libro degli ospiti.
- Feed **RSS 2.0** (notizie + articoli), pulsanti di **condivisione** social, **SEO** (meta description, canonical,
  Open Graph, Twitter Card, meta per pagina).

**Community e coinvolgimento**
- **Centro notifiche** sul sito (campanella e numero di non lette), con **iscrizioni** (seguire un contenuto o una
  categoria). Le notifiche segnalano i commenti, le risposte del forum, le menzioni @utente, i messaggi privati e gli
  inviti a un evento.
- **Reazioni** «mi piace» su notizie, articoli, commenti e messaggi del forum; **revisioni** dei contenuti (cronologia e
  ripristino); widget «Ultimi commenti».
- **Cestino** comune: notizie, articoli, gallerie, commenti e messaggi del forum eliminati vi finiscono, e possono
  esserne ripristinati.
- **Forum**: categoria riservata ai membri VIP, immagine per categoria.

**Gaming**
- **Eventi**: gestione completa degli avversari (elenco, modifica, eliminazione; il NeoFrag originale permetteva solo di
  aggiungerne uno da una partita), conto alla rovescia in diretta, notifica di invito.

**Monetizzazione e gamification**
- Karma/reputazione → **punti** (scala configurabile) → **negozio** (pagamento in punti) → stato **VIP**.
- **Stripe** (acquisto di punti e di pacchetti VIP), **gestione della pubblicità** (nascosta ai membri VIP),
  **donazioni** (modulo e widget portati dal «Donation v3» di HiddenBlob, da majiid; LGPLv3).

**Collegamenti tra moduli**
- **Statistiche** estese a 19 moduli (il NeoFrag originale ne copriva 3: commenti, forum, membri); **bacheca delle
  attività** sul profilo, che raccoglie l'attività di un membro in tutti i moduli; dashboard **«Da gestire»**
  nell'amministrazione; **blocchi di pagina**: lo shortcode `[block:…]` inserisce in una pagina un blocco di un modulo
  (notizie, articoli, download).

**Webhook e audit**
- **Webhook** in uscita firmati (HMAC), gestiti dall'amministrazione. Il **registro di audit** registra anche
  l'installazione e la disinstallazione di un addon, l'attivazione di un tema o di un modulo e il salvataggio delle
  impostazioni.

**Amministrazione degli account**
- **Membri**: esportazione dei membri in CSV o JSON (GDPR); i moderatori che ne hanno il permesso regolano la
  moderazione dalla loro area membri. La modifica e l'eliminazione di un membro dall'amministrazione, già presenti nel
  NeoFrag originale, sono ricollegate al nuovo elenco dei membri.

### Modificato
- Versionamento normalizzato in **SemVer puro** nelle dipendenze degli addon (rimozione delle etichette «Alpha»).
- **Pagine** fa ora parte del nucleo; **Notizie**, **Forum** e **Gallerie** si possono disinstallare.
- **Editor di testo avanzato**: finalmente funziona — l'HTML che produce viene ripulito invece di essere mostrato come
  testo.

### Rimosso
- I due temi del NeoFrag originale, «Thème par défaut» (`default`) e Azuro, sostituiti dai temi di NeoFrag Reborn.
- L'editor BBCode del NeoFrag originale (WysiBB) e la conversione del BBCode: l'editor di testo avanzato lo sostituisce,
  e un testo scritto in BBCode viene ora mostrato così com'è.

### Corretto
- **Dieci difetti corretti**, tra cui: gli eventi senza partecipanti né partita non comparivano; modificare o eliminare
  una modalità di gioco falliva; eliminare un'offerta di reclutamento falliva; il link verso un partner portava a una
  pagina non trovata (ora porta al suo sito); l'autore di una segnalazione non veniva mai mostrato, nemmeno ai
  moderatori autorizzati; i widget Gallerie e Forum verificavano male le loro impostazioni o i permessi. I contatori
  delle visualizzazioni contano ormai una sola volta per visita e ignorano i bot.
- Un contenuto programmato non è più visibile dal suo indirizzo diretto prima dell'ora prevista. Pulsanti che non
  facevano nulla ora funzionano: periodi delle statistiche, attivazione dei modelli di e-mail, social network (sono
  mostrati tutti e sei), pulsanti «Aggiungi» di notizie, pagine e gallerie.
- **Temi**: contrasto dei pulsanti portato al livello WCAG AA, campi di accesso uniti alla loro icona, allineamenti
  corretti.
- **Schermi piccoli e grandi** (verificato da 390 a 2560 pixel di larghezza): nell'amministrazione, menu laterale a
  scomparsa e contenuto a tutta larghezza sul telefono; profilo membro; barra dell'account compatta in Nebula, Forge,
  Blockcraft e Granite.
- Le pagine Membri, Statistiche ed Editor live dell'amministrazione mostravano un errore 500; alcuni contatori restavano
  bloccati a 1 (badge della moderazione, iscritti alla newsletter, statistiche dei media), e una categoria vuota di
  Download, FAQ o link non si poteva eliminare. (Per gli sviluppatori: errori di tipizzazione rigorosa in `timetostr()`
  e negli helper; `(int)` su `row(FALSE)` di un `COUNT`/`SUM` valeva sempre 1.)

### Sicurezza
- L'HTML inviato dai membri viene ripulito lato server (HTMLPurifier) contro l'iniezione di codice; un file caricato
  viene verificato in base al suo contenuto reale, non solo alla sua estensione; HSTS attivato (i browser non ripiegano
  più su HTTP).
- **Token anti-CSRF** confrontati a tempo costante. **Indirizzo IP**: la limitazione della frequenza e l'elenco degli IP
  bannati non si fidano più delle intestazioni falsificabili (`X-Forwarded-For`…), a meno che non sia dichiarato un
  proxy fidato. **Permessi**: controlli dei permessi aggiunti dove mancavano (Libro degli ospiti, Giochi, Gallerie,
  Eventi, Statistiche).
- I vecchi script di aggiornamento non possono più ricreare oggetti PHP arbitrari rileggendo le impostazioni
  (`unserialize` limitato da `allowed_classes`).
- **Impronta di sessione** contro il furto di sessione: un membro connesso viene disconnesso se il suo browser
  (user-agent) cambia in modo marcato durante la sessione — un semplice aggiornamento del browser non basta. L'indirizzo
  della pubblicazione programmata è protetto da una chiave confrontata a tempo costante.
