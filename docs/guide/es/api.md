# La API REST

La API permite a un **programa** — el bot de Discord de NeoFrag Reborn, un script, una integración — leer los datos del
sitio y escribir en su foro o en su Bugtracker, sin navegador. Responde en **JSON**, en direcciones **versionadas**
(`/api/v1/…`), y solo acepta a los programas que tienen una **clave de acceso** creada por un administrador.

La proporciona el módulo **API**, que es opcional: *Sistema → Temas & Addons* para instalarlo si no lo está.

## Crear una clave de acceso

*Administración → API → Nueva clave*. Dale un **nombre** que diga quién la usa («Bot de Discord») y marca solo los
**derechos** que necesita el programa:

| Derecho | Lo que abre |
|---|---|
| `members:read` | Los miembros (por ID o por cuenta de Discord vinculada) y la lista de grupos |
| `forum:read` | El foro: su árbol, sus temas, sus mensajes — categorías restringidas incluidas |
| `forum:write` | Escribir en el foro **en nombre de** un miembro o de una cuenta de Discord: crear un tema, responder, modificar o eliminar un mensaje de ese autor |
| `events:read` | El flujo de eventos (nuevo tema, nuevo mensaje, cambio de grupo…) |
| `bugtracker:read` | El Bugtracker: sus tickets y sus comentarios |
| `bugtracker:write` | Escribir en el Bugtracker **en nombre de** un miembro o de una cuenta de Discord: abrir un ticket, comentar, modificar o eliminar un comentario de ese autor |
| `discord:bot` | Ser el bot de Discord del sitio: su configuración — **token de Discord incluido** —, su estado, su registro. El módulo Discord crea esta clave él mismo (mira [El bot de Discord](bot-discord.md)) |

La clave (`nfr_` seguido de 40 caracteres) **solo se muestra una vez**, justo después de crearla: el sitio solo
conserva su huella. Una clave perdida no se recupera — la **revocas** y creas otra. La lista de claves muestra su último
uso y la dirección de la que vino.

## Llamar a la API

Cada petición lleva la clave en la cabecera `Authorization`:

```bash
curl -H "Authorization: Bearer nfr_…" https://tu-sitio.example/api/v1/status
```

Una respuesta correcta envuelve sus datos en `data`:

```json
{
    "data": {
        "site": "Mi equipo",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Bot de Discord", "scopes": ["members:read", "forum:read"] }
    }
}
```

Los textos (títulos de foros y de grupos) se devuelven en el **idioma por defecto** del sitio, y **en texto plano**:
«é», no `&eacute;` como el sitio los guarda para sus páginas. A la inversa, lo que escribe un programa se guarda como lo
que se escribe en el sitio: una etiqueta en un título se muestra, no se ejecuta.

## Las direcciones de la versión 1

| Método y dirección | Derecho | Devuelve |
|---|---|---|
| `GET /api/v1/status` | ninguno | El sitio, su versión, y la clave usada con sus derechos — para comprobar una configuración |
| `GET /api/v1/members/{id}` | `members:read` | Un miembro: `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (claves de grupo), `discord` (`id`, `username`) o `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | El miembro que vinculó esta cuenta de Discord (misma forma) |
| `GET /api/v1/groups` | `members:read` | Los grupos: `key`, `title`, `color`, `icon`, `hidden`, `auto` (un grupo calculado por el sitio), `members` (número de miembros) |
| `GET /api/v1/forums` | `forum:read` | Las categorías, cada una con sus foros y sus subforos: `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, y `link` para un foro que redirige a otro sitio |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | Un tema: `forum_id`, `title`, `author` (`id`, `username`) o `null`, `external_author` (`provider`, `external_id`, `name`) para una cuenta de Discord no vinculada, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | Un mensaje: `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (como lo muestra el foro), `text` (texto plano), `url` — un mensaje eliminado devuelve `deleted: true` sin contenido |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | El flujo de eventos (mira más abajo) |
| `POST /api/v1/forum/topics` | `forum:write` | Crea un tema (mira «Escribir en el foro») y lo devuelve, con **201** |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Responde a un tema y devuelve el mensaje, con 201 |
| `POST /api/v1/forum/images?name={nombre}` | `forum:write` | Guarda una imagen para un mensaje: el cuerpo de la petición **es** la imagen (JPEG, PNG, GIF o WebP, 5 MB como máximo, comprobada y vuelta a codificar como las del editor). Devuelve `path` — para escribir en el contenido, `![nombre](path)` — y `url`, con 201 la primera vez; la misma imagen enviada de nuevo devuelve la misma dirección |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Modifica un mensaje — solo en nombre de su autor |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Pasa un mensaje a la papelera del foro — en nombre de su autor; no el primer mensaje de un tema |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Cambia el prefijo de un tema: `prefix_id` (`null` para ninguno); devuelve el tema |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | Los tickets en el orden de su número, por lotes (50 por defecto, 100 como máximo); solo los abiertos con `open=1`. Devuelve `tickets`, `next` (el cursor siguiente) y `more` |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | Un ticket: `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | Un comentario: `ticket_id`, `content`, `author`, `external_author` (una cuenta de Discord no vinculada), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Abre un ticket (mira «Escribir en el Bugtracker») y lo devuelve, con 201 |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Comenta un ticket y devuelve el comentario, con 201 |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Modifica un comentario — solo en nombre de su autor |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Elimina un comentario — solo en nombre de su autor |

Las direcciones `bugtracker/*` responden 404 `module_unavailable` si el Bugtracker no está instalado.

### Las direcciones del bot de Discord

Exigen el derecho `discord:bot` y responden 404 `module_unavailable` si el módulo Discord no está instalado. Sirven al
bot de NeoFrag Reborn; a otro programa no le sirven de nada.

| Método y dirección | Devuelve |
|---|---|
| `GET /api/v1/discord/config` | La configuración del bot: `token` (su token de Discord, descifrado), `client_id`, `guild_id`, `running`, `channels`, `roles` (cada uno `group_key`, `role_id`, y el `name` y el `color` de su grupo), `tags` (prefijo ↔ etiqueta), `features` (cada funcionalidad encendida o apagada, y sus ajustes), `lang`, `i18n` (las traducciones de los textos que muestra), `version` (el número que cambia con cada ajuste), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | La señal de vida del bot (`version`, `connected`, `intents`, `guild`: el servidor, sus canales, sus etiquetas y sus roles; `features`: sus funcionalidades y sus ajustes; `texts`: sus textos que traducir; `events_cursor`); devuelve `running`, `version` y los `commands` pendientes, cada uno `type` (`restart`, `resync`, `setup`, `setup-undo`) y `data` |
| `POST /api/v1/discord/logs` | Líneas de su registro: `entries`, cada una `level`, `message`, `template` y `args` (la plantilla que traduce la administración) |
| `GET /api/v1/discord/members` | Los miembros que vincularon su Discord: `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (o `discord_id=…`) | El vínculo entre un elemento del sitio y su equivalente en Discord — `type`: `topic`, `message`, `ticket` o `comment`; 404 `link_not_found` si no hay ninguno |
| `POST /api/v1/discord/links` | Guarda un vínculo: `type`, `site_id`, `discord_id`; con `replace: true`, el que ya tenía el elemento del sitio cede su lugar (un ticket que cambia de canal) |
| `POST /api/v1/discord/links/lookup` | Cuáles de estos elementos de Discord (`type`, `discord_ids`, 500 como máximo) ya tienen su equivalente en el sitio: `found` |
| `POST /api/v1/discord/link-request` | `/forum account link`: un enlace de un solo uso al sitio (`url`, `expires_in`), o `linked` y `member` si este Discord ya está vinculado |
| `POST /api/v1/discord/unlink` | `/forum account unlink`: desvincula este Discord (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | Cómo aparece esta cuenta en el foro: `linked` y `member`, o `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility`: `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | El informe de una configuración del servidor, o de su deshacer: el sitio pone o retira las correspondencias |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | Los roles temporales en curso — los de un miembro, o los vencidos con `due=1`: `timed_roles`, cada uno `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (marca de tiempo Unix), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Da o prolonga un rol temporal: `discord_id`, `username`, `role_id`, `duration` (segundos, de 60 a un año), `given_by`, `given_by_name`, `reason`; 409 `role_mapped` para un rol vinculado a un grupo del sitio |
| `DELETE /api/v1/discord/timed-roles/{id}` | Retira un rol temporal de la lista |
| `GET /api/v1/discord/sanctions` | Las sanciones que se aplican en Discord en este momento — silencios y baneos de todo el sitio, vigentes, de un miembro vinculado: `sanctions`, cada una `sanction_id`, `user_id`, `discord_id`, `type` (`mute`, `ban_temp`, `ban_perm`), `expires_at` (marca de tiempo Unix, o `null`), `origine` (`site` o `discord`) |
| `POST /api/v1/discord/sanctions` | Comunica una sanción impuesta en Discord: `type`, `executor_discord_id`, `target_discord_id`, `duration_seconds`, `reason`; devuelve 201 `sanction_id`, `requires_approval`. Rechazos: 422 `invalid_type`, 403 `executor_unlinked`, 404 `target_unlinked`, 403 `forbidden` (el moderador no tiene este derecho en el sitio), 403 `refused` (jerarquía, uno mismo) |
| `POST /api/v1/discord/sanctions/{id}/revoke` | Levanta una sanción venida de Discord: `executor_discord_id`; 404 `not_found`, 409 `site_sanction` (se levanta en el sitio), 403 `forbidden` |
| `GET /api/v1/discord/collectives` | Los colectivos que quieren su espacio en Discord: `collectives`, cada uno `collective_id`, `name`, `group_key` (la clave de su grupo), `role_id` y `channel_id` (o `null` mientras no existan) |
| `POST /api/v1/discord/collectives/{id}` | El rol o el canal que el bot acaba de crear: `role_id`, `channel_id` — el rol se convierte en la correspondencia del grupo del colectivo; 404 `not_found`, 422 `invalid_id` |

## Escribir en el foro

La API escribe siempre **en nombre de un autor**, dado en el cuerpo JSON de la petición:

- `"author": { "member_id": 7 }` — un miembro del sitio;
- `"author": { "discord": { "id": "123456789012345678", "username": "Apodo", "avatar": "https://cdn.discordapp.com/…" } }`
  — una cuenta de Discord. Si está **vinculada** a un miembro (conexión con Discord), el mensaje se publica a nombre de
  ese miembro: su cuenta real, su nombre y su avatar. Si no, se publica con la **identidad de Discord** de la persona:
  su nombre de Discord, marcado con el logo de Discord, sin enlace a un perfil del sitio.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json" \
     -d '{"forum_id": 3, "title": "Mi pregunta", "content": "**Hola** a todos", "author": {"discord": {"id": "123456789012345678", "username": "Apodo"}}}' \
     https://tu-sitio.example/api/v1/forum/topics
```

| Campo | Para | Contenido |
|---|---|---|
| `forum_id` | tema | El foro donde publicar (no un foro que redirige a otro sitio) |
| `title` | tema | 1 a 100 caracteres |
| `prefix_id` | tema | Opcional: uno de los prefijos de tema del foro |
| `content` | todos | El texto, 1 a 20 000 caracteres |
| `format` | todos | `markdown` (por defecto — el formato de Discord) o `html` |
| `reply_to` | respuesta | Opcional: el mensaje al que se responde |
| `author` | todos | El autor (más arriba) |

El Markdown se convierte en HTML; el HTML que se cuele en él se **escapa**, y el HTML pasa por la misma limpieza que el
resto del sitio. El autor debe tener el **derecho de escribir** en el foro, como en el sitio (si no, 403
`forum_forbidden`): un miembro, o una cuenta de Discord vinculada, por sus derechos sobre la categoría; una cuenta de
Discord sin cuenta en el sitio, por los de los miembros. Un tema **cerrado** rechaza las respuestas (409
`topic_locked`). Modificar o eliminar exige el **mismo autor** que el mensaje (si no, 403 `not_author`): quien lo
escribió lo corrige. Una petición mal formada devuelve 400 `invalid_json`; un campo no válido, 422 `validation_failed`
con el detalle en `error.fields`.

Las **sanciones de moderación** también se aplican a través de la API: un miembro silenciado o baneado del foro (o del
sitio) no escribe allí (403 `sanctioned`), y un miembro privado de enlaces externos no publica un texto que los lleve
(403 `links_forbidden`). `error.message` indica la sanción, para mostrársela al autor. Una cuenta de Discord sin cuenta
en el sitio no tiene sanciones.

Las escrituras de la API aparecen en el flujo de eventos con `source.token_id`: un programa que copia el foro en otro
lugar reconoce así lo que escribió él mismo.

## Escribir en el Bugtracker

Como en el foro, la API escribe **en nombre de un autor** (`author`, misma forma). Un **ticket** pertenece a un miembro:
una cuenta de Discord no vinculada a ningún miembro se rechaza (409 `not_linked`). Un **comentario**, en cambio, puede
venir de una cuenta de Discord no vinculada: aparece con su nombre de Discord. Las sanciones de moderación se aplican
como en el foro (403 `sanctioned`, `links_forbidden`).

| Campo | Para | Contenido |
|---|---|---|
| `title` | ticket | 1 a 200 caracteres |
| `description` | ticket | El texto, 1 a 20 000 caracteres |
| `type` | ticket | `bug` (por defecto), `feature`, `question` u `other` |
| `content` | comentario | El texto, 1 a 20 000 caracteres |
| `author` | todos | El autor |

Modificar o eliminar un comentario exige el **mismo autor** (si no, 403 `not_author`).

## El flujo de eventos

En lugar de consultar todo el sitio a intervalos regulares, un programa lee el **flujo de eventos**: «¿qué ha pasado
desde el evento n.º X?». El sitio no tiene que contactarlo — el programa no abre ningún puerto.

```bash
curl -H "Authorization: Bearer nfr_…" "https://tu-sitio.example/api/v1/events?after=0&limit=100"
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

Guarda el valor de `next` y devuélvelo como `after` la vez siguiente. Mientras `more` sea `true`, quedan eventos por
leer enseguida. Los eventos se quedan **30 días** en el flujo.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (el primer mensaje de un tema) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — el prefijo de un tema cambió |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (los campos que cambiaron) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (y `user_id` en la creación) |
| `user.groups.changed` | `user_id` — vuelve a leer el miembro para tener sus grupos al día |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — una cuenta de Discord acaba de vincularse o desvincularse |
| `discord.collective.removed` | `collective_id`, `role_id`, `channel_id` — un colectivo deja Discord (eliminado, o su casilla desmarcada): hay que eliminar su rol |
| `discord.sanction.created`, `.approved`, `.revoked` | `sanction_id`, `user_id`, `discord_id`, `type`, `expires_at`, `origine` — un silencio o un baneo de todo el sitio de un miembro vinculado acaba de aplicarse (impuesto, o validado) o de levantarse |

Un evento solo lleva **identificadores**: el contenido se lee con las direcciones de arriba, cuando hace falta (un
mensaje eliminado entretanto devuelve `deleted: true`). `source` dice de dónde vino el cambio: `null` para el sitio,
`{ "token_id": … }` para una escritura hecha con una clave de la API — un programa reconoce así sus propias escrituras, y
no las copia una segunda vez.

## Los errores

Un error devuelve el código HTTP adecuado y un objeto `error`, cuyo `code` es **estable** (un programa puede apoyarse en
él) y cuyo `message` es legible:

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | Cuándo |
|---|---|---|
| 401 | `unauthorized` | Clave ausente, desconocida o revocada (con la cabecera `WWW-Authenticate: Bearer`) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | La clave no tiene el derecho que exige la dirección; el autor dado no es el del mensaje; el autor no puede escribir en este foro; una sanción de moderación lo impide, o prohíbe el enlace que lleva su texto |
| 400 | `invalid_json` | El cuerpo de la petición no es un JSON válido |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Dirección desconocida; elemento ausente; módulo (el foro, el Bugtracker, Discord) no instalado |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Tema cerrado; eliminación del primer mensaje de un tema; cuenta de Discord no vinculada; rol vinculado a un grupo — y los rechazos propios de las direcciones del bot, dados con ellas |
| 422 | `validation_failed` | Un campo no es válido — el detalle está en `error.fields` |
| 405 | `method_not_allowed` | Método incorrecto (la cabecera `Allow` dice cuál) |
| 429 | `too_many_requests` | Límite de frecuencia alcanzado (la cabecera `Retry-After` dice en cuántos segundos volver a intentarlo) |

## El límite de frecuencia

Una clave puede hacer **120 peticiones por minuto**; cada respuesta dice cuántas quedan (`X-RateLimit-Remaining`). Más
allá, la API responde 429 durante un minuto. Una dirección IP que envía **20 claves rechazadas** en un minuto queda
bloqueada cinco minutos: nada permite machacar la API para adivinar una clave.

## Seguridad

- El sitio solo conserva las **huellas** de las claves: una fuga de la base no da ninguna clave utilizable.
- Una clave solo abre sus **derechos**, y se **revoca** con un clic; cada creación y cada revocación se inscribe en el
  registro de auditoría (*Usuarios → Registro de auditoría*).
- La API no pone ni cookie ni sesión, y nunca sirve una página: solo JSON.
- Guarda la clave del lado del servidor (variable de entorno, archivo no versionado), nunca en un código publicado ni en
  una página web.
