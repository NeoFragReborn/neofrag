# The REST API

The API lets a **program** — the NeoFrag Reborn Discord bot, a script, an integration — read the site's data and
write on its forum or its Bugtracker, without a browser. It answers in **JSON**, at **versioned** addresses
(`/api/v1/…`), and only accepts programs holding an **access key** created by an administrator.

It is provided by the **API** module, which is optional: *System → Themes & Addons* to install it if it is not.

## Creating an access key

*Administration → API → New key*. Give it a **name** that says who uses it (“Discord bot”) and tick only the
**rights** the program needs:

| Right | What it opens |
|---|---|
| `members:read` | Members (by ID or by linked Discord account) and the list of groups |
| `forum:read` | The forum: its tree, its topics, its messages — restricted categories included |
| `forum:write` | Writing on the forum **on behalf of** a member or a Discord account: creating a topic, replying, editing or deleting a message by that author |
| `events:read` | The event feed (new topic, new message, group change…) |
| `bugtracker:read` | The Bugtracker: its tickets and their comments |
| `bugtracker:write` | Writing in the Bugtracker **on behalf of** a member or a Discord account: opening a ticket, commenting, editing or deleting a comment by that author |
| `discord:bot` | Being the site's Discord bot: its configuration — **Discord token included** —, its status, its log. The Discord module creates this key itself (see [The Discord bot](bot-discord.md)) |

The key (`nfr_` followed by 40 characters) is **shown only once**, right after it is created: the site only keeps
its fingerprint. A lost key cannot be recovered — you **revoke** it and create another. The list of keys shows their
last use and the address it came from.

## Calling the API

Each request carries the key in the `Authorization` header:

```bash
curl -H "Authorization: Bearer nfr_…" https://your-site.example/api/v1/status
```

A successful response wraps its data in `data`:

```json
{
    "data": {
        "site": "My team",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Discord bot", "scopes": ["members:read", "forum:read"] }
    }
}
```

Texts (forum and group titles) are returned in the site's **default language**, and **in plain text**: “é”, not
`&eacute;` as the site stores them for its pages. Conversely, what a program writes is stored like what you write on
the site: a tag in a title is displayed, it is not executed.

## Version 1 addresses

| Method and address | Right | Returns |
|---|---|---|
| `GET /api/v1/status` | none | The site, its version, and the key used with its rights — to check a configuration |
| `GET /api/v1/members/{id}` | `members:read` | A member: `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (group keys), `discord` (`id`, `username`) or `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | The member who linked this Discord account (same shape) |
| `GET /api/v1/groups` | `members:read` | Groups: `key`, `title`, `color`, `icon`, `hidden`, `auto` (a group computed by the site), `members` (head count) |
| `GET /api/v1/forums` | `forum:read` | Categories, each with its forums and their subforums: `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, and `link` for a forum that redirects elsewhere |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | A topic: `forum_id`, `title`, `author` (`id`, `username`) or `null`, `external_author` (`provider`, `external_id`, `name`) for an unlinked Discord account, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | A message: `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (as the forum shows it), `text` (plain text), `url` — a deleted message returns `deleted: true` without content |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | The event feed (see below) |
| `POST /api/v1/forum/topics` | `forum:write` | Creates a topic (see “Writing on the forum”) and returns it, with **201** |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Replies to a topic and returns the message, with 201 |
| `POST /api/v1/forum/images?name={name}` | `forum:write` | Keeps an image for a message: the request body **is** the image (JPEG, PNG, GIF or WebP, 5 MB at most, checked and re-encoded like the editor's). Returns `path` — to write in the content, `![name](path)` — and `url`, with 201 the first time; the same image sent again returns the same address |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Edits a message — on behalf of its author only |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Moves a message to the forum's trash — on behalf of its author; not a topic's first message |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Changes a topic's prefix: `prefix_id` (`null` for none); returns the topic |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | Tickets in the order of their number, in batches (50 by default, 100 at most); only open ones with `open=1`. Returns `tickets`, `next` (the next cursor) and `more` |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | A ticket: `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | A comment: `ticket_id`, `content`, `author`, `external_author` (an unlinked Discord account), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Opens a ticket (see “Writing in the Bugtracker”) and returns it, with 201 |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Comments on a ticket and returns the comment, with 201 |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Edits a comment — on behalf of its author only |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Deletes a comment — on behalf of its author only |

The `bugtracker/*` addresses answer 404 `module_unavailable` if the Bugtracker is not installed.

### The Discord bot's addresses

They require the `discord:bot` right and answer 404 `module_unavailable` if the Discord module is not installed.
They serve the NeoFrag Reborn bot; another program has no use for them.

| Method and address | Returns |
|---|---|
| `GET /api/v1/discord/config` | The bot's configuration: `token` (its Discord token, decrypted), `client_id`, `guild_id`, `running`, `channels`, `roles` (each `group_key`, `role_id`, and its group's `name` and `color`), `tags` (prefix ↔ tag), `features` (each feature on or off, and its settings), `lang`, `i18n` (the translations of the texts it shows), `version` (the number that changes at every setting), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | The bot's sign of life (`version`, `connected`, `intents`, `guild`: the server, its channels, their tags and its roles; `features`: its features and their settings; `texts`: its texts to translate; `events_cursor`); returns `running`, `version` and the pending `commands`, each `type` (`restart`, `resync`, `setup`, `setup-undo`) and `data` |
| `POST /api/v1/discord/logs` | Lines of its log: `entries`, each `level`, `message`, `template` and `args` (the template the administration translates) |
| `GET /api/v1/discord/members` | Members who linked their Discord: `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (or `discord_id=…`) | The link between a site item and its Discord counterpart — `type`: `topic`, `message`, `ticket` or `comment`; 404 `link_not_found` if there is none |
| `POST /api/v1/discord/links` | Keeps a link: `type`, `site_id`, `discord_id`; with `replace: true`, the one the site item already had gives way (a ticket that changes channel) |
| `POST /api/v1/discord/links/lookup` | Which of these Discord items (`type`, `discord_ids`, 500 at most) already have their counterpart on the site: `found` |
| `POST /api/v1/discord/link-request` | `/forum account link`: a single-use link to the site (`url`, `expires_in`), or `linked` and `member` if this Discord is already linked |
| `POST /api/v1/discord/unlink` | `/forum account unlink`: unlinks this Discord (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | How this account appears on the forum: `linked` and `member`, or `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility`: `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | The report of a server setup, or of its undoing: the site sets or removes the mappings |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | Timed roles in progress — a member's, or those that expired with `due=1`: `timed_roles`, each `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (Unix timestamp), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Gives or extends a timed role: `discord_id`, `username`, `role_id`, `duration` (seconds, from 60 to one year), `given_by`, `given_by_name`, `reason`; 409 `role_mapped` for a role linked to a site group |
| `DELETE /api/v1/discord/timed-roles/{id}` | Removes a timed role from the list |
| `GET /api/v1/discord/sanctions` | The sanctions in force on Discord right now — site-wide mutes and bans, in effect, of a linked member: `sanctions`, each `sanction_id`, `user_id`, `discord_id`, `type` (`mute`, `ban_temp`, `ban_perm`), `expires_at` (Unix timestamp, or `null`), `origine` (`site` or `discord`) |
| `POST /api/v1/discord/sanctions` | Reports a sanction imposed on Discord: `type`, `executor_discord_id`, `target_discord_id`, `duration_seconds`, `reason`; returns 201 `sanction_id`, `requires_approval`. Refusals: 422 `invalid_type`, 403 `executor_unlinked`, 404 `target_unlinked`, 403 `forbidden` (the moderator does not have this right on the site), 403 `refused` (hierarchy, oneself) |
| `POST /api/v1/discord/sanctions/{id}/revoke` | Lifts a sanction that came from Discord: `executor_discord_id`; 404 `not_found`, 409 `site_sanction` (it is lifted on the site), 403 `forbidden` |
| `GET /api/v1/discord/collectives` | The collectives that want their space on Discord: `collectives`, each `collective_id`, `name`, `group_key` (its group's key), `role_id` and `channel_id` (or `null` as long as they do not exist) |
| `POST /api/v1/discord/collectives/{id}` | The role or channel the bot has just created: `role_id`, `channel_id` — the role becomes the mapping of the collective's group; 404 `not_found`, 422 `invalid_id` |

## Writing on the forum

The API always writes **on behalf of an author**, given in the request's JSON body:

- `"author": { "member_id": 7 }` — a site member;
- `"author": { "discord": { "id": "123456789012345678", "username": "Nickname", "avatar": "https://cdn.discordapp.com/…" } }`
  — a Discord account. If it is **linked** to a member (login through Discord), the message is posted under that
  member: their real account, username and avatar. Otherwise, it is posted under the person's **Discord
  identity**: their Discord username, marked with the Discord logo, without a link to a site profile.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json" \
     -d '{"forum_id": 3, "title": "My question", "content": "**Hello** everyone", "author": {"discord": {"id": "123456789012345678", "username": "Nickname"}}}' \
     https://your-site.example/api/v1/forum/topics
```

| Field | For | Content |
|---|---|---|
| `forum_id` | topic | The forum to post in (not a forum that redirects elsewhere) |
| `title` | topic | 1 to 100 characters |
| `prefix_id` | topic | Optional: one of the forum's topic prefixes |
| `content` | all | The text, 1 to 20,000 characters |
| `format` | all | `markdown` (default — Discord's format) or `html` |
| `reply_to` | reply | Optional: the message being replied to |
| `author` | all | The author (above) |

Markdown is converted to HTML; any HTML slipped into it is **escaped**, and HTML goes through the same cleaning as
the rest of the site. The author must have the **right to write** in the forum, as on the site (403
`forum_forbidden` otherwise): a member, or a linked Discord account, through their rights on the category; a Discord
account without a site account, through the members'. A **locked** topic refuses replies (409 `topic_locked`).
Editing or deleting requires the **same author** as the message (403 `not_author` otherwise): the person who wrote
it corrects it. A malformed request returns 400 `invalid_json`; an invalid field, 422 `validation_failed` with the
details in `error.fields`.

**Moderation sanctions** also apply through the API: a member muted or banned from the forum (or the site) does not
write there (403 `sanctioned`), and a member denied external links does not post a text carrying some (403
`links_forbidden`). `error.message` states the sanction, to show to the author. A Discord account without a site
account has no sanction.

The API's writes appear in the event feed with `source.token_id`: a program that copies the forum elsewhere thus
recognises what it wrote itself.

## Writing in the Bugtracker

As on the forum, the API writes **on behalf of an author** (`author`, same shape). A **ticket** belongs to a member:
a Discord account linked to no member is refused (409 `not_linked`). A **comment**, however, can come from an
unlinked Discord account: it appears under its Discord username. Moderation sanctions apply as on the forum (403
`sanctioned`, `links_forbidden`).

| Field | For | Content |
|---|---|---|
| `title` | ticket | 1 to 200 characters |
| `description` | ticket | The text, 1 to 20,000 characters |
| `type` | ticket | `bug` (default), `feature`, `question` or `other` |
| `content` | comment | The text, 1 to 20,000 characters |
| `author` | all | The author |

Editing or deleting a comment requires the **same author** (403 `not_author` otherwise).

## The event feed

Rather than polling the whole site at regular intervals, a program reads the **event feed**: “what happened since
event no. X?”. The site does not have to contact it — the program opens no port.

```bash
curl -H "Authorization: Bearer nfr_…" "https://your-site.example/api/v1/events?after=0&limit=100"
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

Keep the value of `next` and give it back as `after` the next time. As long as `more` is `true`, there are events
left to read right away. Events stay **30 days** in the feed.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (a topic's first message) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — a topic's prefix changed |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (the fields that changed) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (and `user_id` on creation) |
| `user.groups.changed` | `user_id` — read the member again for their up-to-date groups |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — a Discord account has just been linked or unlinked |
| `discord.collective.removed` | `collective_id`, `role_id`, `channel_id` — a collective leaves Discord (deleted, or its box unticked): its role is to be deleted |
| `discord.sanction.created`, `.approved`, `.revoked` | `sanction_id`, `user_id`, `discord_id`, `type`, `expires_at`, `origine` — a site-wide mute or ban of a linked member has just taken effect (issued, or approved) or been lifted |

An event only carries **identifiers**: the content is read through the addresses above, when needed (a message
deleted in the meantime returns `deleted: true`). `source` says where the change came from: `null` for the site,
`{ "token_id": … }` for a write made with an API key — a program thus recognises its own writes, and does not copy
them a second time.

## Errors

An error returns the appropriate HTTP code and an `error` object, whose `code` is **stable** (a program can rely on
it) and whose `message` is readable:

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | When |
|---|---|---|
| 401 | `unauthorized` | Key missing, unknown or revoked (with the `WWW-Authenticate: Bearer` header) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | The key does not have the right the address requires; the given author is not the message's; the author may not write in this forum; a moderation sanction prevents it, or forbids the link its text carries |
| 400 | `invalid_json` | The request body is not valid JSON |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Unknown address; missing item; module (the forum, the Bugtracker, Discord) not installed |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Locked topic; deleting a topic's first message; Discord account not linked; role linked to a group — and the refusals specific to the bot's addresses, given with them |
| 422 | `validation_failed` | A field is invalid — the details are in `error.fields` |
| 405 | `method_not_allowed` | Wrong method (the `Allow` header says which) |
| 429 | `too_many_requests` | Rate limit reached (the `Retry-After` header says in how many seconds to retry) |

## Rate limit

A key can make **120 requests per minute**; each response says how many are left (`X-RateLimit-Remaining`). Beyond
that, the API answers 429 for a minute. An IP address that sends **20 refused keys** in a minute is blocked for five
minutes: nothing allows hammering the API to guess a key.

## Security

- The site only keeps keys' **fingerprints**: a database leak gives no usable key.
- A key only opens its **rights**, and is **revoked** in one click; each creation and each revocation is written to
  the audit log (*Users → Audit log*).
- The API sets neither cookie nor session, and never serves a page: only JSON.
- Keep the key on the server side (environment variable, unversioned file), never in published code or in a web
  page.
