# Die REST-API

Die API lässt ein **Programm** — den Discord-Bot von NeoFrag Reborn, ein Skript, eine Integration — die Daten der Seite
lesen und in ihr Forum oder ihren Bugtracker schreiben, ohne Browser. Sie antwortet in **JSON**, unter **versionierten**
Adressen (`/api/v1/…`), und nimmt nur Programme an, die einen von einem Administrator angelegten **Zugriffsschlüssel**
haben.

Sie wird vom Modul **API** geliefert, das optional ist: *System → Themes & Addons*, um es zu installieren, falls es das
nicht ist.

## Einen Zugriffsschlüssel anlegen

*Verwaltung → API → Neuer Schlüssel*. Gib ihm einen **Namen**, der sagt, wer ihn nutzt („Discord-Bot“), und hake nur die
**Rechte** an, die das Programm braucht:

| Recht | Was es öffnet |
|---|---|
| `members:read` | Die Mitglieder (nach Kennung oder nach verknüpftem Discord-Konto) und die Liste der Gruppen |
| `forum:read` | Das Forum: seine Struktur, seine Themen, seine Nachrichten — geschützte Kategorien eingeschlossen |
| `forum:write` | **Im Namen** eines Mitglieds oder eines Discord-Kontos ins Forum schreiben: ein Thema anlegen, antworten, eine Nachricht dieses Autors bearbeiten oder löschen |
| `events:read` | Der Ereignis-Feed (neues Thema, neue Nachricht, Gruppenwechsel…) |
| `bugtracker:read` | Der Bugtracker: seine Tickets und ihre Kommentare |
| `bugtracker:write` | **Im Namen** eines Mitglieds oder eines Discord-Kontos in den Bugtracker schreiben: ein Ticket eröffnen, kommentieren, einen Kommentar dieses Autors bearbeiten oder löschen |
| `discord:bot` | Der Discord-Bot der Seite sein: seine Konfiguration — **Discord-Token eingeschlossen** —, sein Zustand, sein Protokoll. Das Discord-Modul legt diesen Schlüssel selbst an (siehe [Der Discord-Bot](bot-discord.md)) |

Der Schlüssel (`nfr_` gefolgt von 40 Zeichen) wird **nur einmal angezeigt**, direkt nach dem Anlegen: Die Seite behält
nur seinen Fingerabdruck. Ein verlorener Schlüssel lässt sich nicht wiederfinden — man **widerruft** ihn und legt einen
anderen an. Die Liste der Schlüssel zeigt ihre letzte Nutzung und die Adresse, von der sie kam.

## Die API aufrufen

Jede Anfrage trägt den Schlüssel im Header `Authorization`:

```bash
curl -H "Authorization: Bearer nfr_…" https://deine-seite.example/api/v1/status
```

Eine erfolgreiche Antwort verpackt ihre Daten in `data`:

```json
{
    "data": {
        "site": "Mein Team",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Discord-Bot", "scopes": ["members:read", "forum:read"] }
    }
}
```

Texte (Titel von Foren und Gruppen) kommen in der **Standardsprache** der Seite und **im Klartext**: „é“, nicht
`&eacute;`, wie die Seite sie für ihre Seiten speichert. Umgekehrt wird, was ein Programm schreibt, gespeichert wie das,
was man auf der Seite schreibt: Ein Tag in einem Titel wird angezeigt, nicht ausgeführt.

## Die Adressen der Version 1

| Methode und Adresse | Recht | Gibt zurück |
|---|---|---|
| `GET /api/v1/status` | keins | Die Seite, ihre Version und den verwendeten Schlüssel mit seinen Rechten — um eine Konfiguration zu prüfen |
| `GET /api/v1/members/{id}` | `members:read` | Ein Mitglied: `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (Gruppenschlüssel), `discord` (`id`, `username`) oder `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | Das Mitglied, das dieses Discord-Konto verknüpft hat (gleiche Form) |
| `GET /api/v1/groups` | `members:read` | Die Gruppen: `key`, `title`, `color`, `icon`, `hidden`, `auto` (eine von der Seite berechnete Gruppe), `members` (Anzahl) |
| `GET /api/v1/forums` | `forum:read` | Die Kategorien, jede mit ihren Foren und deren Unterforen: `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, und `link` für ein Forum, das woandershin verweist |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | Ein Thema: `forum_id`, `title`, `author` (`id`, `username`) oder `null`, `external_author` (`provider`, `external_id`, `name`) für ein nicht verknüpftes Discord-Konto, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | Eine Nachricht: `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (wie das Forum sie zeigt), `text` (Klartext), `url` — eine gelöschte Nachricht gibt `deleted: true` ohne Inhalt zurück |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | Der Ereignis-Feed (siehe unten) |
| `POST /api/v1/forum/topics` | `forum:write` | Legt ein Thema an (siehe „Ins Forum schreiben“) und gibt es mit **201** zurück |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Antwortet auf ein Thema und gibt die Nachricht mit 201 zurück |
| `POST /api/v1/forum/images?name={name}` | `forum:write` | Behält ein Bild für eine Nachricht: Der Body der Anfrage **ist** das Bild (JPEG, PNG, GIF oder WebP, höchstens 5 MB, geprüft und neu kodiert wie die des Editors). Gibt `path` zurück — in den Inhalt zu schreiben, `![name](path)` — und `url`, beim ersten Mal mit 201; dasselbe erneut geschickte Bild gibt dieselbe Adresse zurück |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Bearbeitet eine Nachricht — nur im Namen ihres Autors |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Verschiebt eine Nachricht in den Papierkorb des Forums — im Namen ihres Autors; nicht die erste Nachricht eines Themas |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Ändert das Präfix eines Themas: `prefix_id` (`null` für keins); gibt das Thema zurück |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | Die Tickets in der Reihenfolge ihrer Nummer, stapelweise (standardmäßig 50, höchstens 100); nur die offenen mit `open=1`. Gibt `tickets`, `next` (den nächsten Cursor) und `more` zurück |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | Ein Ticket: `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | Ein Kommentar: `ticket_id`, `content`, `author`, `external_author` (ein nicht verknüpftes Discord-Konto), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Eröffnet ein Ticket (siehe „In den Bugtracker schreiben“) und gibt es mit 201 zurück |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Kommentiert ein Ticket und gibt den Kommentar mit 201 zurück |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Bearbeitet einen Kommentar — nur im Namen seines Autors |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Löscht einen Kommentar — nur im Namen seines Autors |

Die Adressen `bugtracker/*` antworten mit 404 `module_unavailable`, wenn der Bugtracker nicht installiert ist.

### Die Adressen des Discord-Bots

Sie verlangen das Recht `discord:bot` und antworten mit 404 `module_unavailable`, wenn das Discord-Modul nicht
installiert ist. Sie dienen dem Bot von NeoFrag Reborn; ein anderes Programm hat keine Verwendung für sie.

| Methode und Adresse | Gibt zurück |
|---|---|
| `GET /api/v1/discord/config` | Die Konfiguration des Bots: `token` (sein Discord-Token, entschlüsselt), `client_id`, `guild_id`, `running`, `channels`, `roles` (jede mit `group_key`, `role_id` sowie `name` und `color` ihrer Gruppe), `tags` (Präfix ↔ Tag), `features` (jede Funktion an oder aus, und ihre Einstellungen), `lang`, `i18n` (die Übersetzungen der Texte, die er zeigt), `version` (die Nummer, die sich bei jeder Einstellung ändert), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | Das Lebenszeichen des Bots (`version`, `connected`, `intents`, `guild`: der Server, seine Kanäle, ihre Tags und seine Rollen; `features`: seine Funktionen und ihre Einstellungen; `texts`: seine zu übersetzenden Texte; `events_cursor`); gibt `running`, `version` und die wartenden `commands` zurück, jeder mit `type` (`restart`, `resync`, `setup`, `setup-undo`) und `data` |
| `POST /api/v1/discord/logs` | Zeilen seines Protokolls: `entries`, jede mit `level`, `message`, `template` und `args` (die Vorlage, die die Verwaltung übersetzt) |
| `GET /api/v1/discord/members` | Die Mitglieder, die ihr Discord verknüpft haben: `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (oder `discord_id=…`) | Die Verknüpfung eines Elements der Seite mit seinem Gegenstück auf Discord — `type`: `topic`, `message`, `ticket` oder `comment`; 404 `link_not_found`, wenn es keine gibt |
| `POST /api/v1/discord/links` | Behält eine Verknüpfung: `type`, `site_id`, `discord_id`; mit `replace: true` weicht die, die das Element der Seite schon hatte (ein Ticket, das den Kanal wechselt) |
| `POST /api/v1/discord/links/lookup` | Welche dieser Discord-Elemente (`type`, `discord_ids`, höchstens 500) schon ihr Gegenstück auf der Seite haben: `found` |
| `POST /api/v1/discord/link-request` | `/forum account link`: ein Einmal-Link zur Seite (`url`, `expires_in`), oder `linked` und `member`, wenn dieses Discord schon verknüpft ist |
| `POST /api/v1/discord/unlink` | `/forum account unlink`: hebt die Verknüpfung dieses Discords auf (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | Wie dieses Konto im Forum erscheint: `linked` und `member`, oder `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility`: `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | Der Bericht einer Einrichtung des Servers oder ihrer Rücknahme: Die Seite setzt oder entfernt die Zuordnungen |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | Die laufenden befristeten Rollen — die eines Mitglieds, oder mit `due=1` die abgelaufenen: `timed_roles`, jede mit `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (Unix-Zeitstempel), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Gibt oder verlängert eine befristete Rolle: `discord_id`, `username`, `role_id`, `duration` (Sekunden, von 60 bis zu einem Jahr), `given_by`, `given_by_name`, `reason`; 409 `role_mapped` für eine mit einer Gruppe der Seite verknüpfte Rolle |
| `DELETE /api/v1/discord/timed-roles/{id}` | Entfernt eine befristete Rolle aus der Liste |
| `GET /api/v1/discord/sanctions` | Die Sanktionen, die gerade auf Discord gelten — seitenweite Stummschaltungen und Sperren, in Kraft, eines verknüpften Mitglieds: `sanctions`, jede mit `sanction_id`, `user_id`, `discord_id`, `type` (`mute`, `ban_temp`, `ban_perm`), `expires_at` (Unix-Zeitstempel oder `null`), `origine` (`site` oder `discord`) |
| `POST /api/v1/discord/sanctions` | Meldet eine auf Discord verhängte Sanktion: `type`, `executor_discord_id`, `target_discord_id`, `duration_seconds`, `reason`; gibt 201 `sanction_id`, `requires_approval` zurück. Ablehnungen: 422 `invalid_type`, 403 `executor_unlinked`, 404 `target_unlinked`, 403 `forbidden` (der Moderator hat dieses Recht auf der Seite nicht), 403 `refused` (Hierarchie, man selbst) |
| `POST /api/v1/discord/sanctions/{id}/revoke` | Hebt eine von Discord gekommene Sanktion auf: `executor_discord_id`; 404 `not_found`, 409 `site_sanction` (sie wird auf der Seite aufgehoben), 403 `forbidden` |
| `GET /api/v1/discord/collectives` | Die Kollektive, die ihren Bereich auf Discord wollen: `collectives`, jedes mit `collective_id`, `name`, `group_key` (der Schlüssel seiner Gruppe), `role_id` und `channel_id` (oder `null`, solange sie nicht existieren) |
| `POST /api/v1/discord/collectives/{id}` | Die Rolle oder der Kanal, die der Bot gerade angelegt hat: `role_id`, `channel_id` — die Rolle wird zur Zuordnung der Gruppe des Kollektivs; 404 `not_found`, 422 `invalid_id` |

## Ins Forum schreiben

Die API schreibt immer **im Namen eines Autors**, angegeben im JSON-Body der Anfrage:

- `"author": { "member_id": 7 }` — ein Mitglied der Seite;
- `"author": { "discord": { "id": "123456789012345678", "username": "Spitzname", "avatar": "https://cdn.discordapp.com/…" } }`
  — ein Discord-Konto. Ist es mit einem Mitglied **verknüpft** (Anmeldung über Discord), wird die Nachricht unter diesem
  Mitglied veröffentlicht: sein echtes Konto, sein Benutzername, sein Avatar. Sonst wird sie unter der
  **Discord-Identität** der Person veröffentlicht: ihr Discord-Name, mit dem Discord-Logo markiert, ohne Link zu einem
  Profil der Seite.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json" \
     -d '{"forum_id": 3, "title": "Meine Frage", "content": "**Hallo** zusammen", "author": {"discord": {"id": "123456789012345678", "username": "Spitzname"}}}' \
     https://deine-seite.example/api/v1/forum/topics
```

| Feld | Für | Inhalt |
|---|---|---|
| `forum_id` | Thema | Das Forum, in dem veröffentlicht wird (kein Forum, das woandershin verweist) |
| `title` | Thema | 1 bis 100 Zeichen |
| `prefix_id` | Thema | Optional: ein Themenpräfix des Forums |
| `content` | alles | Der Text, 1 bis 20.000 Zeichen |
| `format` | alles | `markdown` (Standard — das Format von Discord) oder `html` |
| `reply_to` | Antwort | Optional: die Nachricht, auf die geantwortet wird |
| `author` | alles | Der Autor (oben) |

Markdown wird in HTML umgewandelt; jedes hineingeschmuggelte HTML wird **maskiert**, und HTML durchläuft dieselbe
Bereinigung wie der Rest der Seite. Der Autor muss das **Recht haben, ins Forum zu schreiben**, wie auf der Seite (sonst
403 `forum_forbidden`): ein Mitglied oder ein verknüpftes Discord-Konto über seine Rechte an der Kategorie; ein
Discord-Konto ohne Konto der Seite über die der Mitglieder. Ein **gesperrtes** Thema lehnt Antworten ab (409
`topic_locked`). Bearbeiten oder Löschen verlangt **denselben Autor** wie die Nachricht (sonst 403 `not_author`): Wer sie
geschrieben hat, korrigiert sie. Eine fehlerhafte Anfrage gibt 400 `invalid_json` zurück; ein ungültiges Feld 422
`validation_failed` mit den Details in `error.fields`.

Die **Moderationssanktionen** gelten auch über die API: Ein im Forum (oder auf der Seite) stummgeschaltetes oder
gesperrtes Mitglied schreibt dort nicht (403 `sanctioned`), und ein Mitglied ohne Recht auf externe Links veröffentlicht
keinen Text, der welche trägt (403 `links_forbidden`). `error.message` nennt die Sanktion, um sie dem Autor zu zeigen.
Ein Discord-Konto ohne Konto der Seite hat keine Sanktion.

Die Schreibvorgänge der API erscheinen im Ereignis-Feed mit `source.token_id`: Ein Programm, das das Forum anderswohin
kopiert, erkennt so, was es selbst geschrieben hat.

## In den Bugtracker schreiben

Wie im Forum schreibt die API **im Namen eines Autors** (`author`, gleiche Form). Ein **Ticket** gehört einem Mitglied:
Ein Discord-Konto, das mit keinem Mitglied verknüpft ist, wird abgelehnt (409 `not_linked`). Ein **Kommentar** dagegen
kann von einem nicht verknüpften Discord-Konto kommen: Er erscheint unter dessen Discord-Namen. Die
Moderationssanktionen gelten wie im Forum (403 `sanctioned`, `links_forbidden`).

| Feld | Für | Inhalt |
|---|---|---|
| `title` | Ticket | 1 bis 200 Zeichen |
| `description` | Ticket | Der Text, 1 bis 20.000 Zeichen |
| `type` | Ticket | `bug` (Standard), `feature`, `question` oder `other` |
| `content` | Kommentar | Der Text, 1 bis 20.000 Zeichen |
| `author` | alles | Der Autor |

Einen Kommentar bearbeiten oder löschen verlangt **denselben Autor** (sonst 403 `not_author`).

## Der Ereignis-Feed

Statt die ganze Seite in regelmäßigen Abständen abzufragen, liest ein Programm den **Ereignis-Feed**: „Was ist seit
Ereignis Nr. X passiert?“. Die Seite muss es nicht kontaktieren — das Programm öffnet keinen Port.

```bash
curl -H "Authorization: Bearer nfr_…" "https://deine-seite.example/api/v1/events?after=0&limit=100"
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

Behalte den Wert von `next` und gib ihn beim nächsten Mal als `after` zurück. Solange `more` `true` ist, gibt es sofort
weitere Ereignisse zu lesen. Ereignisse bleiben **30 Tage** im Feed.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (die erste Nachricht eines Themas) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — das Präfix eines Themas hat sich geändert |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (die geänderten Felder) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (und `user_id` beim Anlegen) |
| `user.groups.changed` | `user_id` — das Mitglied für seine aktuellen Gruppen erneut lesen |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — ein Discord-Konto wurde gerade verknüpft oder getrennt |
| `discord.collective.removed` | `collective_id`, `role_id`, `channel_id` — ein Kollektiv verlässt Discord (gelöscht oder sein Feld abgehakt): seine Rolle ist zu löschen |
| `discord.sanction.created`, `.approved`, `.revoked` | `sanction_id`, `user_id`, `discord_id`, `type`, `expires_at`, `origine` — eine seitenweite Stummschaltung oder Sperre eines verknüpften Mitglieds ist gerade in Kraft getreten (verhängt oder bestätigt) oder aufgehoben worden |

Ein Ereignis trägt nur **Kennungen**: Der Inhalt wird über die Adressen oben gelesen, wenn er gebraucht wird (eine
inzwischen gelöschte Nachricht gibt `deleted: true` zurück). `source` sagt, woher die Änderung kam: `null` für die Seite,
`{ "token_id": … }` für einen mit einem API-Schlüssel ausgeführten Schreibvorgang — ein Programm erkennt so seine
eigenen Schreibvorgänge und kopiert sie nicht ein zweites Mal.

## Die Fehler

Ein Fehler gibt den passenden HTTP-Code und ein Objekt `error` zurück, dessen `code` **stabil** ist (ein Programm kann
sich darauf verlassen) und dessen `message` lesbar ist:

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | Wann |
|---|---|---|
| 401 | `unauthorized` | Schlüssel fehlt, unbekannt oder widerrufen (mit dem Header `WWW-Authenticate: Bearer`) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | Der Schlüssel hat das von der Adresse verlangte Recht nicht; der angegebene Autor ist nicht der der Nachricht; der Autor darf in diesem Forum nicht schreiben; eine Moderationssanktion hindert ihn daran oder verbietet ihm den Link, den sein Text trägt |
| 400 | `invalid_json` | Der Body der Anfrage ist kein gültiges JSON |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Unbekannte Adresse; fehlendes Element; Modul (das Forum, der Bugtracker, Discord) nicht installiert |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Gesperrtes Thema; Löschen der ersten Nachricht eines Themas; Discord-Konto nicht verknüpft; mit einer Gruppe verknüpfte Rolle — und die eigenen Ablehnungen der Adressen des Bots, dort genannt |
| 422 | `validation_failed` | Ein Feld ist ungültig — die Details stehen in `error.fields` |
| 405 | `method_not_allowed` | Falsche Methode (der Header `Allow` sagt, welche) |
| 429 | `too_many_requests` | Ratenbegrenzung erreicht (der Header `Retry-After` sagt, in wie vielen Sekunden man es erneut versucht) |

## Die Ratenbegrenzung

Ein Schlüssel kann **120 Anfragen pro Minute** stellen; jede Antwort sagt, wie viele bleiben (`X-RateLimit-Remaining`).
Darüber hinaus antwortet die API eine Minute lang mit 429. Eine IP-Adresse, die in einer Minute **20 abgelehnte
Schlüssel** schickt, wird fünf Minuten gesperrt: Nichts erlaubt, die API zu bombardieren, um einen Schlüssel zu erraten.

## Sicherheit

- Die Seite behält nur den **Fingerabdruck** der Schlüssel: Ein Datenbankleck liefert keinen brauchbaren Schlüssel.
- Ein Schlüssel öffnet nur seine **Rechte** und wird mit einem Klick **widerrufen**; jedes Anlegen und jeder Widerruf
  wird im Audit-Log eingetragen (*Benutzer → Audit-Log*).
- Die API setzt weder Cookie noch Sitzung und liefert nie eine Seite aus: nur JSON.
- Bewahre den Schlüssel auf der Serverseite auf (Umgebungsvariable, nicht versionierte Datei), nie in veröffentlichtem
  Code oder in einer Webseite.
