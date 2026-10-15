# Concetti

Capire queste cinque nozioni basta per padroneggiare NeoFrag Reborn.

## Moduli

Un **modulo** è una funzionalità completa: il forum, le notizie, la galleria, i membri, il negozio… Ogni modulo porta
le sue **pagine pubbliche**, le sue **schermate di amministrazione**, i suoi **dati** (tabelle) e i suoi **permessi**.

Il pacchetto include **63 moduli**, ma non li installi tutti: all'installazione, un **profilo del sito** — *Completo*,
*Gaming / eSport*, *Community*, *Associazione / club* o *Solo nucleo* — spunta in anticipo ciò che corrisponde, e puoi
deselezionare modulo per modulo. Ciò di cui un modulo ha bisogno viene aggiunto automaticamente (il palmarès ha bisogno
delle squadre). I moduli del **nucleo** (account, permessi, impostazioni, pagine, strumenti…) ci sono sempre e non si
disinstallano.

Poi, da **Amministrazione → Temi & Addons**, installi, attivi, disattivi o disinstalli ogni modulo. Un modulo
disattivato scompare dal sito ma conserva i suoi dati. Gli indirizzi di un modulo assente rispondono con un **404
pulito**, mai con un errore.

## Widget

Un **widget** è un piccolo blocco riutilizzabile: il menu di navigazione, l'area membri, gli ultimi commenti, un conto
alla rovescia, un lettore Twitch… Uno stesso widget si può collocare più volte, in posti diversi, con impostazioni
diverse. Il pacchetto include **42 widget**.

I widget si collocano nelle **regioni** del tema con l'**Editor live**: una procedura guidata in quattro passi (widget,
tipo, titolo, configurazione), e il trascinamento per spostarli.

## Temi

Un **tema** dà al sito il suo aspetto: colori, caratteri, impaginazione, navigazione, piè di pagina, e la **modalità
chiara o scura**. Dichiara delle **zone**, dà loro dei **nomi di regione** stabili (`header`, `content`, `footer`…) e
colloca le sue **disposizioni** predefinite.

Il pacchetto include **Nebula**, un tema community generalista (blu notte e turchese, glassmorphism), pensato per una
squadra, un clan, un club o un'associazione. Altri sei temi — **Granite**, **Forge**, **Blockcraft**, **Extend**,
**Chronique**, **Pulse** — sono nel pacchetto e si installano da **Amministrazione → Temi & Addons**; un'installazione
che non li ha li trova nel [marketplace](marketplace.md). Il tema attivo si sceglie in **Amministrazione → Temi &
Addons**; se sono installati più temi pubblici, i visitatori possono cambiare con il selettore del piè di pagina.

Tutti i temi parlano lo **stesso vocabolario di colori** (`--nf-accent`, `--nf-surface`…): un widget adotta
automaticamente l'aspetto del tema attivo.

## Zone, regioni e disposizioni

Un tema divide la pagina in **zone**: di solito *Header*, *Prima del contenuto*, *Contenuto*, *Dopo il contenuto*,
*Footer*, ciascuna nota con un **nome di regione** usato dai modelli.

Una **disposizione** descrive, per una data pagina (o uno schema di pagine), quali widget occupano quale zona e in quale
griglia. Gli schemi vanno dal più generale al più preciso:

| Schema | Si applica a |
|---|---|
| `*` | tutte le pagine |
| `/` | solo la pagina iniziale |
| `forum/*` | tutte le pagine del forum |

Vince lo schema più preciso: una disposizione `*` mette il contenuto su otto colonne più una colonna laterale, una
disposizione `marketplace*` può passare la stessa pagina a larghezza piena.

Cambi le disposizioni con il mouse nell'**Editor live**. Una pagina può anche portare dei **blocchi**: istanze di moduli
ordinate e configurate, inserite con lo shortcode `[block:…]`.

## Addon e marketplace

**Addon** è il termine generico per tutto ciò che si installa: moduli, widget, temi, connettori di autenticazione
(Discord, GitHub, Google) e pacchetti di lingua (sei lingue incluse).

Il **[marketplace](marketplace.md)** è il catalogo del progetto: **rileva gli aggiornamenti** degli addon installati e
permette di **aggiungere** quelli che non sono nel pacchetto, con verifica di integrità SHA-256. Il **nucleo del CMS**
si aggiorna con un clic dal **Monitoraggio**, con un backup prima di scrivere e un ritorno automatico allo stato
precedente in caso di errore.

Tecnicamente, un addon non è altro che una **cartella di file PHP**: puoi versionarlo, condividerlo e reinstallarlo su
qualsiasi sito NeoFrag Reborn. È ciò che rende il CMS estendibile — vedi le guide per gli sviluppatori.

## In pratica: avviare una community

Per una squadra di gioco: all'installazione, scegli il profilo **Gaming / eSport** (forum, squadre, eventi,
reclutamento, palmarès…) e il tema Nebula viene installato; poi, con l'Editor live, metti il widget **navigazione**
nella regione `header` e un widget di contenuto (ultime discussioni, prossime partite…) nella colonna laterale della
pagina iniziale. Tutto si regola nell'amministrazione, **senza una riga di codice**.

Per un'associazione o un club: scegli il profilo **Associazione / club** — notizie, forum, gallerie, calendario,
collettivi, donazioni e quote associative, newsletter, wiki e FAQ, senza l'apparato esport. I membri, le pagine e il
modulo di contatto ci sono sempre; le squadre, se servono, si aggiungono con un clic.

## Sovrascrivere senza fare un fork

Qualsiasi file incluso (vista, classe, risorsa) può essere **sovrascritto** senza modificare il codice originale. Il
framework risolve in questo ordine (vince il primo trovato):

1. `overrides/{type}/{file}` — sovrascrittura globale
2. `themes/{tema attivo}/overrides/{type}/{file}` — sovrascrittura per tema
3. l'originale incluso

Così adatti un modulo o un widget al tuo sito senza mai toccare il nucleo, e senza rompere gli aggiornamenti.

Per continuare: [Primi passi](premiers-pas.md) e [Un sito in più lingue](multilingue.md).
