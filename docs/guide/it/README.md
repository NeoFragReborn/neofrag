# Documentazione di NeoFrag Reborn

Benvenuto nella documentazione pubblica di **NeoFrag Reborn**, il CMS libero per le community, dal gaming alle
associazioni: monta il sito della tua squadra, del tuo clan, del tuo club o della tua associazione senza scrivere una
riga di codice, e amplialo quando ne hai voglia.

> NeoFrag Reborn è la continuazione comunitaria di **NeoFrag**, creato in origine da **Michaël BILCOT** e
> **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Un progetto open source sotto licenza **LGPLv3**. Queste guide sono
> tradotte dalle guide in francese, che sono **verificate sul codice**; sono pubblicate anche nel wiki di
> [neofrag-reborn.xyz](https://neofrag-reborn.xyz).

## Per gli utenti

| Guida | Cosa impari |
|---|---|
| [Installazione](installation.md) | Mettere online NeoFrag Reborn: requisiti, l'installer in cinque passi e i suoi profili, la riga di comando, le prime impostazioni |
| [Primi passi](premiers-pas.md) | I dieci passi che trasformano un'installazione nuova in un sito pronto da aprire: nome, tema, pagina iniziale, menu, forum, membri, backup |
| [Concetti](concepts.md) | Come è organizzato il CMS: moduli, widget, temi, zone e regioni, disposizioni, addon |
| [Il pannello di amministrazione](admin.md) | Gestire il tuo sito: contenuto, membri, permessi, impostazioni, monitoraggio |
| [Il marketplace](marketplace.md) | Trovare e installare addon, aggiornare gli addon e il nucleo |
| [Un sito in più lingue](multilingue.md) | Ciò che si traduce da solo, ciò che traduci tu, ciò che vede un visitatore quando manca una traduzione |
| [Il bot Discord](bot-discord.md) | Collegare il tuo sito al tuo server Discord: ruoli e soprannomi, forum ↔ canale Forum, tutto regolato dall'amministrazione — e scrivere una funzionalità del bot |

## Per gli sviluppatori

| Guida | Cosa impari |
|---|---|
| [Il framework](framework.md) | Localizzatore di servizi, routing, database, moduli, tabelle, traduzioni, il front end senza jQuery sotto una CSP rigorosa, test e controlli |
| [Creare un modulo](create-a-module.md) | Una funzionalità completa: dichiarazioni, rotte, checker, dati e migrazioni, dipendenze, concentratori, permessi, amministrazione |
| [Creare un widget](create-a-widget.md) | Un blocco riutilizzabile: impostazioni con valori di riserva, vocabolario CSS condiviso, JavaScript puro |
| [Creare un tema](create-a-theme.md) | Zone e regioni, scheletro, disposizioni predefinite, il vocabolario `--nf-*` completo, colori regolabili, miniatura, migrazioni del tema |
| [L'API REST](api.md) | Far parlare un programma con il sito — un bot, un'integrazione: chiavi di accesso, diritti, indirizzi, errori, limite di frequenza |

## In breve

NeoFrag Reborn si installa su un normale hosting **PHP 8.2+ / MySQL o MariaDB** (hosting condiviso compreso), **à la
carte**: un profilo del sito all'installazione, poi moduli da attivare o aggiungere. Una volta in funzione, costruisci il
tuo sito con il mouse: metti dei **widget** nelle **regioni** del tuo **tema** con l'Editor live, e scarichi dei
complementi dal **marketplace**.

Tutto può essere **sovrascritto senza fare un fork** (un sistema di sovrascrittura a tre livelli), e ogni estensione è
una semplice cartella di file PHP che puoi versionare e condividere.

Altre lingue: [Français](../README.md) · [English](../en/README.md) · [Deutsch](../de/README.md) · [Español](../es/README.md) · [Português](../pt/README.md)
