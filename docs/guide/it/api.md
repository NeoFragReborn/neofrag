# L'API REST

L'API permette a un **programma** — il bot Discord di NeoFrag Reborn, uno script, un'integrazione — di leggere i dati del
sito e di scrivere sul suo forum o nel suo Bugtracker, senza browser. Risponde in **JSON**, a indirizzi **versionati**
(`/api/v1/…`), e accetta solo i programmi che possiedono una **chiave di accesso** creata da un amministratore.

È fornita dal modulo **API**, che è opzionale: *Sistema → Temi & Addons* per installarlo se non lo è.

## Creare una chiave di accesso

*Amministrazione → API → Nuova chiave*. Dalle un **nome** che dica chi la usa («Bot Discord») e spunta solo i **diritti**
di cui il programma ha bisogno:

| Diritto | Cosa apre |
|---|---|
| `members:read` | I membri (per ID o per account Discord collegato) e l'elenco dei gruppi |
| `forum:read` | Il forum: il suo albero, le sue discussioni, i suoi messaggi — categorie riservate comprese |
| `forum:write` | Scrivere sul forum **a nome di** un membro o di un account Discord: creare una discussione, rispondere, modificare o eliminare un messaggio di quell'autore |
| `events:read` | Il flusso degli eventi (nuova discussione, nuovo messaggio, cambio di gruppo…) |
| `bugtracker:read` | Il Bugtracker: i suoi ticket e i loro commenti |
| `bugtracker:write` | Scrivere nel Bugtracker **a nome di** un membro o di un account Discord: aprire un ticket, commentare, modificare o eliminare un commento di quell'autore |
| `discord:bot` | Essere il bot Discord del sito: la sua configurazione — **token Discord compreso** —, il suo stato, il suo registro. Il modulo Discord crea questa chiave da sé (vedi [Il bot Discord](bot-discord.md)) |

La chiave (`nfr_` seguito da 40 caratteri) **viene mostrata una sola volta**, subito dopo la creazione: il sito ne
conserva solo l'impronta. Una chiave persa non si recupera — la **revochi** e ne crei un'altra. L'elenco delle chiavi
mostra il loro ultimo utilizzo e l'indirizzo da cui è venuto.

## Chiamare l'API

Ogni richiesta porta la chiave nell'intestazione `Authorization`:

```bash
curl -H "Authorization: Bearer nfr_…" https://tuo-sito.example/api/v1/status
```

Una risposta riuscita avvolge i suoi dati in `data`:

```json
{
    "data": {
        "site": "La mia squadra",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Bot Discord", "scopes": ["members:read", "forum:read"] }
    }
}
```

I testi (titoli dei forum e dei gruppi) vengono restituiti nella **lingua predefinita** del sito, e **in testo
semplice**: «é», non `&eacute;` come il sito li salva per le sue pagine. Al contrario, ciò che un programma scrive viene
salvato come ciò che si scrive sul sito: un tag in un titolo viene mostrato, non eseguito.

## Gli indirizzi della versione 1

| Metodo e indirizzo | Diritto | Restituisce |
|---|---|---|
| `GET /api/v1/status` | nessuno | Il sito, la sua versione, e la chiave usata con i suoi diritti — per verificare una configurazione |
| `GET /api/v1/members/{id}` | `members:read` | Un membro: `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (chiavi di gruppo), `discord` (`id`, `username`) o `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | Il membro che ha collegato questo account Discord (stessa forma) |
| `GET /api/v1/groups` | `members:read` | I gruppi: `key`, `title`, `color`, `icon`, `hidden`, `auto` (un gruppo calcolato dal sito), `members` (numero di membri) |
| `GET /api/v1/forums` | `forum:read` | Le categorie, ciascuna con i suoi forum e i loro sottoforum: `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, e `link` per un forum che rimanda altrove |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | Una discussione: `forum_id`, `title`, `author` (`id`, `username`) o `null`, `external_author` (`provider`, `external_id`, `name`) per un account Discord non collegato, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | Un messaggio: `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (come lo mostra il forum), `text` (testo semplice), `url` — un messaggio eliminato restituisce `deleted: true` senza contenuto |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | Il flusso degli eventi (vedi sotto) |
| `POST /api/v1/forum/topics` | `forum:write` | Crea una discussione (vedi «Scrivere sul forum») e la restituisce, con **201** |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Risponde a una discussione e restituisce il messaggio, con 201 |
| `POST /api/v1/forum/images?name={nome}` | `forum:write` | Conserva un'immagine per un messaggio: il corpo della richiesta **è** l'immagine (JPEG, PNG, GIF o WebP, 5 MB al massimo, verificata e ricodificata come quelle dell'editor). Restituisce `path` — da scrivere nel contenuto, `![nome](path)` — e `url`, con 201 la prima volta; la stessa immagine inviata di nuovo restituisce lo stesso indirizzo |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Modifica un messaggio — solo a nome del suo autore |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Mette un messaggio nel cestino del forum — a nome del suo autore; non il primo messaggio di una discussione |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Cambia il prefisso di una discussione: `prefix_id` (`null` per nessuno); restituisce la discussione |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | I ticket nell'ordine del loro numero, a lotti (50 per impostazione predefinita, 100 al massimo); solo quelli aperti con `open=1`. Restituisce `tickets`, `next` (il cursore successivo) e `more` |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | Un ticket: `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | Un commento: `ticket_id`, `content`, `author`, `external_author` (un account Discord non collegato), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Apre un ticket (vedi «Scrivere nel Bugtracker») e lo restituisce, con 201 |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Commenta un ticket e restituisce il commento, con 201 |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Modifica un commento — solo a nome del suo autore |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Elimina un commento — solo a nome del suo autore |

Gli indirizzi `bugtracker/*` rispondono 404 `module_unavailable` se il Bugtracker non è installato.

### Gli indirizzi del bot Discord

Richiedono il diritto `discord:bot` e rispondono 404 `module_unavailable` se il modulo Discord non è installato. Servono
il bot di NeoFrag Reborn; a un altro programma non servono a nulla.

| Metodo e indirizzo | Restituisce |
|---|---|
| `GET /api/v1/discord/config` | La configurazione del bot: `token` (il suo token Discord, decifrato), `client_id`, `guild_id`, `running`, `channels`, `roles` (ciascuno `group_key`, `role_id`, e il `name` e il `color` del suo gruppo), `tags` (prefisso ↔ tag), `features` (ogni funzionalità accesa o spenta, e le sue impostazioni), `lang`, `i18n` (le traduzioni dei testi che mostra), `version` (il numero che cambia a ogni impostazione), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | Il segnale di vita del bot (`version`, `connected`, `intents`, `guild`: il server, i suoi canali, i loro tag e i suoi ruoli; `features`: le sue funzionalità e le loro impostazioni; `texts`: i suoi testi da tradurre; `events_cursor`); restituisce `running`, `version` e i `commands` in sospeso, ciascuno `type` (`restart`, `resync`, `setup`, `setup-undo`) e `data` |
| `POST /api/v1/discord/logs` | Righe del suo registro: `entries`, ciascuna `level`, `message`, `template` e `args` (il modello che l'amministrazione traduce) |
| `GET /api/v1/discord/members` | I membri che hanno collegato il loro Discord: `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (o `discord_id=…`) | Il collegamento tra un elemento del sito e il suo corrispondente su Discord — `type`: `topic`, `message`, `ticket` o `comment`; 404 `link_not_found` se non ce n'è |
| `POST /api/v1/discord/links` | Conserva un collegamento: `type`, `site_id`, `discord_id`; con `replace: true`, quello che l'elemento del sito aveva già cede il posto (un ticket che cambia canale) |
| `POST /api/v1/discord/links/lookup` | Quali di questi elementi Discord (`type`, `discord_ids`, 500 al massimo) hanno già il loro corrispondente sul sito: `found` |
| `POST /api/v1/discord/link-request` | `/forum account link`: un link a uso singolo verso il sito (`url`, `expires_in`), o `linked` e `member` se questo Discord è già collegato |
| `POST /api/v1/discord/unlink` | `/forum account unlink`: scollega questo Discord (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | Come appare questo account sul forum: `linked` e `member`, o `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility`: `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | Il resoconto di una configurazione del server, o del suo annullamento: il sito imposta o toglie le corrispondenze |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | I ruoli temporanei in corso — quelli di un membro, o quelli scaduti con `due=1`: `timed_roles`, ciascuno `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (timestamp Unix), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Dà o prolunga un ruolo temporaneo: `discord_id`, `username`, `role_id`, `duration` (secondi, da 60 a un anno), `given_by`, `given_by_name`, `reason`; 409 `role_mapped` per un ruolo collegato a un gruppo del sito |
| `DELETE /api/v1/discord/timed-roles/{id}` | Toglie un ruolo temporaneo dall'elenco |
| `GET /api/v1/discord/sanctions` | Le sanzioni che si applicano su Discord in questo momento — silenziamenti e ban di tutto il sito, in vigore, di un membro collegato: `sanctions`, ciascuna `sanction_id`, `user_id`, `discord_id`, `type` (`mute`, `ban_temp`, `ban_perm`), `expires_at` (timestamp Unix, o `null`), `origine` (`site` o `discord`) |
| `POST /api/v1/discord/sanctions` | Fa risalire una sanzione messa su Discord: `type`, `executor_discord_id`, `target_discord_id`, `duration_seconds`, `reason`; restituisce 201 `sanction_id`, `requires_approval`. Rifiuti: 422 `invalid_type`, 403 `executor_unlinked`, 404 `target_unlinked`, 403 `forbidden` (il moderatore non ha questo diritto sul sito), 403 `refused` (gerarchia, se stessi) |
| `POST /api/v1/discord/sanctions/{id}/revoke` | Toglie una sanzione venuta da Discord: `executor_discord_id`; 404 `not_found`, 409 `site_sanction` (si toglie sul sito), 403 `forbidden` |
| `GET /api/v1/discord/collectives` | I collettivi che vogliono il loro spazio su Discord: `collectives`, ciascuno `collective_id`, `name`, `group_key` (la chiave del suo gruppo), `role_id` e `channel_id` (o `null` finché non esistono) |
| `POST /api/v1/discord/collectives/{id}` | Il ruolo o il canale che il bot ha appena creato: `role_id`, `channel_id` — il ruolo diventa la corrispondenza del gruppo del collettivo; 404 `not_found`, 422 `invalid_id` |

## Scrivere sul forum

L'API scrive sempre **a nome di un autore**, indicato nel corpo JSON della richiesta:

- `"author": { "member_id": 7 }` — un membro del sito;
- `"author": { "discord": { "id": "123456789012345678", "username": "Soprannome", "avatar": "https://cdn.discordapp.com/…" } }`
  — un account Discord. Se è **collegato** a un membro (accesso tramite Discord), il messaggio viene pubblicato a nome
  di quel membro: il suo vero account, il suo nome e il suo avatar. Altrimenti, viene pubblicato con l'**identità
  Discord** della persona: il suo nome Discord, contrassegnato dal logo di Discord, senza link a un profilo del sito.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json" \
     -d '{"forum_id": 3, "title": "La mia domanda", "content": "**Ciao** a tutti", "author": {"discord": {"id": "123456789012345678", "username": "Soprannome"}}}' \
     https://tuo-sito.example/api/v1/forum/topics
```

| Campo | Per | Contenuto |
|---|---|---|
| `forum_id` | discussione | Il forum in cui pubblicare (non un forum che rimanda altrove) |
| `title` | discussione | Da 1 a 100 caratteri |
| `prefix_id` | discussione | Facoltativo: uno dei prefissi di discussione del forum |
| `content` | tutti | Il testo, da 1 a 20 000 caratteri |
| `format` | tutti | `markdown` (predefinito — il formato di Discord) o `html` |
| `reply_to` | risposta | Facoltativo: il messaggio a cui si risponde |
| `author` | tutti | L'autore (sopra) |

Il Markdown viene convertito in HTML; l'HTML che vi si infila viene **sottoposto a escape**, e l'HTML passa dalla stessa
pulizia del resto del sito. L'autore deve avere il **diritto di scrivere** nel forum, come sul sito (altrimenti 403
`forum_forbidden`): un membro, o un account Discord collegato, tramite i suoi diritti sulla categoria; un account Discord
senza account sul sito, tramite quelli dei membri. Una discussione **chiusa** rifiuta le risposte (409 `topic_locked`).
Modificare o eliminare richiede lo **stesso autore** del messaggio (altrimenti 403 `not_author`): chi l'ha scritto lo
corregge. Una richiesta malformata restituisce 400 `invalid_json`; un campo non valido, 422 `validation_failed` con il
dettaglio in `error.fields`.

Le **sanzioni di moderazione** si applicano anche tramite l'API: un membro silenziato o bannato dal forum (o dal sito)
non vi scrive (403 `sanctioned`), e un membro privato dei link esterni non pubblica un testo che ne porta (403
`links_forbidden`). `error.message` indica la sanzione, da mostrare all'autore. Un account Discord senza account sul sito
non ha sanzioni.

Le scritture dell'API compaiono nel flusso degli eventi con `source.token_id`: un programma che copia il forum altrove
riconosce così ciò che ha scritto lui stesso.

## Scrivere nel Bugtracker

Come sul forum, l'API scrive **a nome di un autore** (`author`, stessa forma). Un **ticket** appartiene a un membro: un
account Discord non collegato a nessun membro viene rifiutato (409 `not_linked`). Un **commento**, invece, può venire da
un account Discord non collegato: compare con il suo nome Discord. Le sanzioni di moderazione si applicano come sul forum
(403 `sanctioned`, `links_forbidden`).

| Campo | Per | Contenuto |
|---|---|---|
| `title` | ticket | Da 1 a 200 caratteri |
| `description` | ticket | Il testo, da 1 a 20 000 caratteri |
| `type` | ticket | `bug` (predefinito), `feature`, `question` o `other` |
| `content` | commento | Il testo, da 1 a 20 000 caratteri |
| `author` | tutti | L'autore |

Modificare o eliminare un commento richiede lo **stesso autore** (altrimenti 403 `not_author`).

## Il flusso degli eventi

Invece di interrogare tutto il sito a intervalli regolari, un programma legge il **flusso degli eventi**: «cosa è
successo dall'evento n. X?». Il sito non deve contattarlo — il programma non apre alcuna porta.

```bash
curl -H "Authorization: Bearer nfr_…" "https://tuo-sito.example/api/v1/events?after=0&limit=100"
```

```json
{
    "data": {
        "events": [
            {
                "id": 41,
                "type": "forum.post.created",
                "data": { "message_id": 512, "topic_id": 88, "forum_id": 3, "user_id": 7, "is_starter": false },
                "source": null,
                "created_at": "2026-10-01T17:42:03+02:00"
            }
        ],
        "next": 41,
        "more": false
    }
}
```

Conserva il valore di `next` e ridallo come `after` la volta successiva. Finché `more` è `true`, restano eventi da
leggere subito. Gli eventi restano **30 giorni** nel flusso.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (il primo messaggio di una discussione) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — il prefisso di una discussione è cambiato |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (i campi che sono cambiati) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (e `user_id` alla creazione) |
| `user.groups.changed` | `user_id` — rileggi il membro per avere i suoi gruppi aggiornati |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — un account Discord è appena stato collegato o scollegato |
| `discord.collective.removed` | `collective_id`, `role_id`, `channel_id` — un collettivo lascia Discord (eliminato, o la sua casella tolta): il suo ruolo va eliminato |
| `discord.sanction.created`, `.approved`, `.revoked` | `sanction_id`, `user_id`, `discord_id`, `type`, `expires_at`, `origine` — un silenziamento o un ban di tutto il sito di un membro collegato ha appena preso effetto (messo, o convalidato) o è stato tolto |

Un evento porta solo **identificatori**: il contenuto si legge con gli indirizzi di sopra, quando serve (un messaggio
eliminato nel frattempo restituisce `deleted: true`). `source` dice da dove viene il cambiamento: `null` per il sito,
`{ "token_id": … }` per una scrittura fatta con una chiave API — un programma riconosce così le proprie scritture, e non
le copia una seconda volta.

## Gli errori

Un errore restituisce il codice HTTP adatto e un oggetto `error`, il cui `code` è **stabile** (un programma può
appoggiarsi su di esso) e il cui `message` è leggibile:

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | Quando |
|---|---|---|
| 401 | `unauthorized` | Chiave assente, sconosciuta o revocata (con l'intestazione `WWW-Authenticate: Bearer`) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | La chiave non ha il diritto che l'indirizzo richiede; l'autore indicato non è quello del messaggio; l'autore non può scrivere in questo forum; una sanzione di moderazione lo impedisce, o vieta il link che porta il suo testo |
| 400 | `invalid_json` | Il corpo della richiesta non è un JSON valido |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Indirizzo sconosciuto; elemento assente; modulo (il forum, il Bugtracker, Discord) non installato |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Discussione chiusa; eliminazione del primo messaggio di una discussione; account Discord non collegato; ruolo collegato a un gruppo — e i rifiuti propri degli indirizzi del bot, indicati con essi |
| 422 | `validation_failed` | Un campo non è valido — il dettaglio è in `error.fields` |
| 405 | `method_not_allowed` | Metodo sbagliato (l'intestazione `Allow` dice quale) |
| 429 | `too_many_requests` | Limite di frequenza raggiunto (l'intestazione `Retry-After` dice tra quanti secondi riprovare) |

## Il limite di frequenza

Una chiave può fare **120 richieste al minuto**; ogni risposta dice quante ne restano (`X-RateLimit-Remaining`). Oltre,
l'API risponde 429 per un minuto. Un indirizzo IP che invia **20 chiavi rifiutate** in un minuto viene bloccato per
cinque minuti: niente permette di martellare l'API per indovinare una chiave.

## Sicurezza

- Il sito conserva solo le **impronte** delle chiavi: una fuga del database non dà alcuna chiave utilizzabile.
- Una chiave apre solo i suoi **diritti**, e si **revoca** con un clic; ogni creazione e ogni revoca è scritta nel log di
  audit (*Utenti → Log di audit*).
- L'API non mette né cookie né sessione, e non serve mai una pagina: solo JSON.
- Tieni la chiave lato server (variabile d'ambiente, file non versionato), mai in un codice pubblicato o in una pagina
  web.
