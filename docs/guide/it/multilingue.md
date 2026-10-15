# Un sito in più lingue

NeoFrag Reborn parla sei lingue: francese, inglese, tedesco, spagnolo, italiano e portoghese. Questa guida spiega ciò
che si traduce da solo, ciò che traduci tu e ciò che vede un visitatore quando manca una traduzione.

## Due tipi di testo

- **L'interfaccia** — i pulsanti, i menu dell'amministrazione, i messaggi, le email: arriva tradotta con i **pacchetti
  di lingua** (addon, inclusi nel prodotto). Niente da fare.
- **Il tuo contenuto** — le tue notizie, le tue pagine, le tue categorie, il tuo menu, i titoli dei tuoi widget, le
  pagine del tuo wiki, le tue campagne di donazione…: lo scrivi tu, e lo scrivi **lingua per lingua**.

## Scrivere in un'altra lingua

Una schermata di modifica scrive nella **lingua dell'amministrazione**, e lo dice sotto il campo: «Scritto per
Italiano». Per tradurre, cambia lingua — il selettore di lingua del sito, o l'indirizzo stesso: `/en/admin/…` invece di
`/it/admin/…` — e riapri lo stesso contenuto: i suoi campi sono vuoti dove non c'è ancora niente di scritto in quella
lingua. Salva: le altre lingue conservano il loro testo.

Ciò che non ha lingua resta comune a tutte: una data, un'immagine, un prezzo, un ordine di visualizzazione, un autore.
Lo inserisci una volta sola.

## Cosa vede il visitatore

Il visitatore legge il sito nella lingua dell'indirizzo (`/it/…`), che sceglie con il selettore di lingua del tema. Per
ogni contenuto, vede **la migliore traduzione che esiste**: quella della sua lingua, altrimenti quella francese,
altrimenti quella della prima lingua scritta. Un contenuto scritto in una sola lingua resta visibile ovunque:
semplicemente non è tradotto.

L'amministrazione mostra ciò che resta da tradurre: gli elenchi segnalano i contenuti senza testo nella lingua della
schermata, e il controllo delle traduzioni annota i testi dell'interfaccia mancanti in una lingua
([Il pannello di amministrazione](admin.md)).

## Ciò che non si traduce

- Ciò che scrivono **i membri** — una discussione del forum, un messaggio, un commento, un annuncio — resta nella lingua
  in cui è stato scritto.
- Le **emoji personalizzate** conservano il loro nome ovunque: è un codice, non un testo.

## I motori di ricerca

Ogni lingua ha la sua mappa del sito, e ogni pagina indica le sue altre lingue. Un contenuto che non ha una lingua
propria — una discussione del forum, o una pagina il cui testo è lo stesso in tutte le lingue — si dichiara solo nella
prima lingua del sito: sei copie di uno stesso testo, ciascuna «ufficiale», farebbero sì che non se ne conservi
nessuna. Il dettaglio: [Indicizzazione](admin.md#indicizzazione).

## Per gli sviluppatori

Un modulo che salva contenuto traducibile lo mette in una tabella `<table>_lang` (una riga per lingua), lo legge con
`join_lang()` e lo scrive con `ecrire_lang()`: [Il framework](framework.md).
