# Primi passi

Il tuo sito è appena stato installato ([Installazione](installation.md)): ecco, nell'ordine, ciò che lo trasforma da
pagina vuota in un sito che puoi aprire alla tua community. Conta un'ora. Ogni passo rimanda alla guida che lo spiega in
dettaglio.

## 1. Entrare nell'amministrazione

Accedi con l'account creato alla fine dell'installer, poi apri `/it/admin` (o il link *Amministrazione* nella barra del
membro). La colonna laterale ordina i moduli per sezioni — *Contenuto*, *Community*, *Sistema*… —; `Ctrl + K` apre la
ricerca rapida. Il giro completo è in [Il pannello di amministrazione](admin.md).

## 2. Dare un nome al sito

*Sistema → Impostazioni*: il nome del sito, la sua descrizione, l'indirizzo di contatto, il fuso orario. Il nome
compare nell'intestazione del tema e nella scheda del browser; la descrizione serve ai motori di ricerca finché una
pagina non ne dà una propria ([Indicizzazione](admin.md#indicizzazione)).

## 3. Scegliere il tema e i colori

*Sistema → Temi & Addons*: ogni tema mostra la sua pagina iniziale in miniatura. Attiva quello che somiglia alla tua
community, poi apri le sue impostazioni: la pagina dei **colori** mostra il cambiamento dal vivo, e rifiuta un colore
che renderebbe il testo illeggibile. Cos'è un tema, le sue zone e le sue regioni: [Concetti](concepts.md).

## 4. Comporre la pagina iniziale

*Sistema → Editor live*: il sito si mostra come lo vedono i visitatori, con le sue regioni evidenziate. Trascinaci dei
**widget** — le ultime notizie, il forum, i membri online, uno slideshow — e regola ciascuno con un clic. Una
disposizione vale per una pagina o per tutto il sito.

## 5. Costruire il menu

*Contenuto → Menu*: aggiungi i link della barra di navigazione — home, il forum, le notizie, una pagina. Un link verso
un modulo si sceglie da un elenco; un link esterno si incolla.

## 6. Pubblicare una prima notizia

*Contenuto → Notizie → Aggiungi notizia*: un titolo, un'immagine, il testo. Compare nella pagina iniziale se ci hai messo
il widget delle notizie, e i lettori possono commentarla se i commenti sono aperti.

## 7. Aprire il forum

*Community → Forum*: crea una **categoria** («Generale»), poi i suoi **forum** («Presentazioni», «Aiuto»). I
**prefissi** («Domanda», «Risolto») aiutano a ordinare le discussioni. Chi legge e chi scrive si regola per categoria,
con il pulsante del lucchetto.

## 8. Accogliere i membri

*Sistema → Impostazioni → Iscrizioni*: aperte, su invito o chiuse, con o senza captcha
([Iscrizioni](admin.md#iscrizioni)). I **ruoli** decidono cosa può fare ciascuno: crea un ruolo «Moderatore», spunta i
suoi diritti nella matrice, e dallo ai membri di fiducia ([Permessi](admin.md#permessi)).

## 9. Aggiungere ciò che manca

Il sito è stato installato con un profilo — gaming, community, associazione. Un modulo mancante (il calendario, le
donazioni, i collettivi, il bot Discord…) si aggiunge da *Sistema → Temi & Addons* o dal [marketplace](marketplace.md),
senza reinstallare.

## 10. Metterlo al sicuro

*Sistema → Monitoraggio*: crea un primo **backup**, e annota l'indirizzo dell'**attività pianificata**. Affidala al tuo
hosting (un'attività cron, ogni ora): è lei che pubblica i contenuti programmati, invia i promemoria e raccoglie ciò che
i servizi esterni non annunciano ([Monitoraggio](admin.md#monitoraggio)).

Il tuo sito parla più lingue? Leggi poi [Un sito in più lingue](multilingue.md).
