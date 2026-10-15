# The Discord bot

The NeoFrag Reborn Discord bot links your site and your Discord server. Its features are switched on one by
one:

- **roles and nicknames** — a member who linked their Discord account receives on the server the roles linked
  to their groups, and their site username if you want;
- **forum and Forum channels** — a site forum and a Discord Forum channel live together: a topic becomes a
  thread, a thread becomes a topic, replies follow both ways, and the forum's prefixes become the channel's
  tags;
- **account and appearance** — the `/forum` command: link your account from Discord, and choose how you appear
  on the forum without a linked account;
- **Bugtracker** — each ticket becomes a thread, its type and status as tags; `/bug` and `/idee` open a ticket
  from Discord;
- **timed roles** — the `/role` command: give a role for a limited time, which the bot removes at the end;
- **linked sanctions** — site mutes and bans apply on Discord, and those of a linked moderator can reach the
  site;
- **collectives** — each collective on the site gets a role and a private channel that only its members see.

It is **set up entirely from the site's administration**: its token, its server, its features and their
settings, the start / pause switch, restarting, the mappings, and even the creation of channels and roles on
the server. The administration also shows its status and its log, in your language.

The bot is a **separate program** that runs all the time. It needs a machine that stays on — a VPS, a
Raspberry Pi, a PC — with **Node.js 22.9 or later**. Shared hosting cannot run it; the site itself does not
need it and stays where it is.

## 1. Create the Discord application

1. On the [Discord developer portal](https://discord.com/developers/applications): *New Application*, give it
   a name.
2. *General Information* tab: note the **Application ID**.
3. *Bot* tab: *Reset Token*, and copy the **bot token** — it is shown only once.
4. Still in *Bot*, tick the two **privileged intents**:
   - **Server Members Intent** — for roles, nicknames and timed roles;
   - **Message Content Intent** — to copy the text of Discord messages to the site (forum, Bugtracker).

   Without them, the bot runs in reduced mode and says so in its log: it never asks Discord for more than the
   application is allowed to receive.

## 2. Set up the Discord module

The bot relies on two site modules, **API** and **Discord**. The *Full* installation profile installs both;
otherwise, *System → Themes & Addons* adds them (Discord, an optional addon, comes from the marketplace if it
is not on the server). Forum synchronisation also needs the **Forum** module, ticket synchronisation the
**Bugtracker** module.

*Administration → Discord → Connection*:

| Field | Where to find it |
|---|---|
| Application ID | the *Application ID* from step 1 |
| Server ID | in Discord, with developer mode on: right-click the server → *Copy Server ID* |
| Bot token | the token from step 1. It is **stored encrypted** and never shown again; leave the field empty to keep the saved one |

Then **Invite the bot** (the module's home page): the link adds the bot to the server with only its own
permissions — never “Administrator”:

| Permission | For |
|---|---|
| View Channels, Send Messages, Read Message History | reading and writing in linked channels |
| Send Messages in Threads, Create Public Threads, Manage Threads | copying a topic or a ticket as a thread, renaming it, archiving it, setting its tags |
| Manage Channels | server setup: creating a category, Forum channels and their tags |
| Manage Messages | carrying a deletion made on the site over to Discord |
| Manage Webhooks | posting the site's messages under their author's name and avatar |
| Add Reactions, Embed Links, Attach Files | complete messages |
| Manage Roles, Manage Nicknames | roles, nicknames and timed roles |
| Use Application Commands | `/forum`, `/bug`, `/idee`, `/role` |

In the server settings (*Roles*), place the bot's role **above** the roles it gives: Discord forbids a bot to
give a role placed higher than its own.

## 3. Install the bot on its machine

The bot is downloaded as an already built archive, `neofrag-reborn-bot-X.Y.Z.tar.gz`, from its repository's
[releases page](https://github.com/NeoFragReborn/bot-discord/releases/latest). It has its own version numbers,
independent of the site's.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Administration → Discord → Create the access key*: the page gives **two lines** — the site address and the
bot's access key — to paste into `.env`. They are shown only once; creating a new key revokes the old one.
Then:

```bash
npm start
```

To run it as a service, starting with the machine and restarting if it falls: `neofrag-bot.service`
(systemd, hardened) explains its installation at the top of the file. Its log: `journalctl -u neofrag-bot -f`
— and, above all, the site's administration.

On the machine, only those two lines remain. Everything else comes through the site.

**Updating the bot**: replace the folder with the one from the new archive, keeping your configuration
(`.env`, or `/etc/neofrag-bot/bot.env` under systemd), then `npm ci --omit=dev` and restart the service. A new
bot version that needs more rights on the site says so in the administration: then create a new access key.

## 4. Running it from the administration

*Administration → Discord* shows:

- its **status** — *Online*, *Paused* (alive, switch off), *Connecting to Discord…*, or *Offline* (no news for
  a minute and a half) —, its version, its last sign of life;
- the **Start / Pause**, **Restart** and **Resynchronize** buttons (bring everything back in line: roles and
  nicknames, and what was written on Discord while the bot was away), which it applies within a minute;
- its **log**: connections, synchronisations, and what stops it from working (intent not ticked, role badly
  placed, missing permission, access key too old), with what to do.

**Changing the bot token** (leak, regeneration): paste the new one in *Connection*. The bot reads it again
and reconnects by itself; the machine is not touched.

### Features

*Discord → Features* lists what the bot can do — it declares them itself at each sign of life: a feature added
to the bot appears there without touching the site. Each one is **switched on** or **off**, and its
**settings** are changed there; the bot applies the change within a minute, without restarting. The
Bugtracker, timed roles, linked sanctions and collectives are off at first.

### Server setup

*Discord → Server setup* creates on Discord what is needed to link the site, without doing anything by hand:

- a **category** (reused if it already exists);
- a **Forum channel per chosen forum**, with the forum's prefixes as tags;
- a **role per chosen group**, placed below the bot's so it can give it.

A channel or role with the same name is reused instead of being duplicated. A **preview** shows what will be
created and reused; **Apply** does it, and the mappings (channel ↔ forum, group ↔ role) set themselves up.
**Undo the last setup** deletes what it created — never what it reused.

## Roles and nicknames

*Discord → Groups and roles* links a site group to a server role. The **site has the final word**:

- a linked member who is in the group receives the role; one who no longer is loses it;
- a role nothing links to is **never touched**; nor is a member who has not linked their account;
- with the *Nicknames* setting, the linked member carries their site username on the server (except the server
  owner, whom Discord protects);
- a linked role carries the **name and colour of its group** (bot 0.2.5): a group renamed or recoloured on the
  site is too on the server. A role linked to several groups keeps its own, and a group with no colour leaves
  the role's. The “Give linked roles the name and colour of their group” setting turns this off.

The bot applies a group change within half a minute, a linked member's arrival on the server and the linking
of an account at once, and goes over everyone again at startup, at each settings change and at the chosen
interval (ten minutes by default).

## Account and appearance: `/forum`

A member links their account by logging in to the site with Discord, from *My account → My linked accounts*,
or **from Discord**:

- `/forum account link` gives a single-use link, valid for fifteen minutes. On the site, logged in, the member
  confirms the link — and can take back under their name what they had posted from Discord;
- `/forum account unlink` unlinks, unless Discord is their only way to log in to the site.

Someone who has not linked their account chooses how they appear on the site's forum:

- `/forum visibility public` — under their Discord username;
- `/forum visibility guest` — under an anonymous name;
- `/forum visibility custom` — under a name of their choice (2 to 32 characters, changeable every seven days,
  never a site member's);
- `/forum visibility status` — what is in place.

Their messages already posted follow their choice. All replies are private, in each person's Discord language.

## The forum and Forum channels

*Discord → Channels and forums* links a site forum to a Discord **Forum channel** (not a text channel). Two
modes, channel by channel:

- **Everything**: each thread of the channel becomes a site topic, and each forum topic a thread;
- **On demand**: a thread only reaches the site when someone who can *manage threads* in the channel adds the
  chosen **reaction** (📌 by default) — the whole thread, then what follows. Site topics, already public,
  always go to Discord.

What follows, and how:

| | From the site to Discord | From Discord to the site |
|---|---|---|
| New topic / thread | a thread, under the author's name and avatar | a topic |
| Reply | a message in the thread | a reply (a message's quote follows) |
| Edit | the message is rewritten, the thread title follows | the message is rewritten |
| Deletion | the message disappears; a deleted topic takes its thread with it | the message goes to the forum's trash |
| Prefix / tag | the matching tag is set | the matching prefix is set |

- **Prefixes and tags** (button of each linked channel): a forum prefix ↔ a channel tag. Server setup links
  them by itself.
- A message from Discord is posted under the **account of the member** who linked their Discord; otherwise
  under their **Discord identity**, as they chose with `/forum visibility`, marked with the Discord logo.
- The site's **moderation sanctions** follow the linked member: muted or banned from the forum, their Discord
  messages are not copied; denied external links, a message carrying some is not either (bot 0.2.5). The bot's
  log records it, without saying anything in the channel; `/bug` and `/idee` answer the author alone.
- A message too long for Discord (2,000 characters) is cut there, with a link to the rest.
- The **images** of a site message go to Discord as previews, under the text; the message's links show no
  preview card there.
- An **image attached on Discord** (JPEG, PNG, GIF or WebP, 5 MB at most) is kept on the site and shown in the
  forum message — a site too old to receive it keeps the link. Other attachments, and all of the Bugtracker's,
  are shown as a link to the Discord message.
- Settings: a link to the site under each copied topic, and the **catch-up** at startup of what was written on
  Discord while the bot was off.
- The **permissions** of each linked channel follow the rights of its forum on the site (bot 0.2.5, setting on
  by default). The whole server counts as the site's members: whoever cannot read the forum does not see the
  channel, whoever cannot write in it does not post there — writing in **Everything** mode only; in **On
  demand** mode, Discord stays a place for discussion. A linked role gives its group what members do not
  have: the channel of a team's forum is shown only to the team's roles. The bot only touches viewing and
  writing, for @everyone, linked roles and itself; any other setting made by hand stays.
- The site, for its part, refuses to write on behalf of someone who does not have the right: a message from
  Discord in a forum where its author cannot write stays on Discord, and the bot's log says so.
- Not carried over: deleting a whole thread on Discord (the topic stays, the log reports it), deleting its
  opening message, and on Discord the editing of a message written on Discord (it belongs to its author). The
  Discord copy of a site message, deleted by a moderator, does not delete the original.

## The Bugtracker

In the feature's settings, choose the **tickets Forum channel** (not a channel already linked to a forum). The
bot creates there the tags for the types (Bug, Idea, Question, Other) and statuses (Open, In progress,
Resolved, Closed, Won't do, Duplicate), in the site's language.

To keep ideas apart from bugs, also choose a **suggestions Forum channel** (bot 0.2.5): tickets of type Idea
have their thread there, with the Idea tag and the status tags; the others stay in the tickets channel.

- Each ticket becomes a **thread**, under its author's name and avatar, with its number, type, priority and a
  link to the site. Tickets still open get their thread when you choose the channel — and, when you choose the
  suggestions channel, ideas still open move there.
- A ticket that **changes type** (a bug that became an idea) changes channel: Discord does not move a thread,
  so the bot opens a new one in the right channel. The old thread, locked, points to it, and the new one points
  to the old one. On the site, the ticket keeps all its comments.
- When the ticket changes on the site, the thread follows: tags, title, description. It **archives itself**
  when the ticket is closed; a duplicate points to its original ticket; a deleted ticket takes its thread with
  it.
- **Comments** go both ways. On Discord, someone who has not linked their account comments under their Discord
  username.
- `/bug` and `/idee` open a ticket through a small window (title, description), and a thread opened by hand in
  one of the channels becomes a ticket — an idea in the suggestions channel. A ticket belongs to a member: you
  must have linked your account (otherwise the bot reminds you).
- The **site has the final word**: a tag changed by hand on Discord is set back to what the ticket says.

## Timed roles: `/role`

Reserved for those who can **manage roles** on the server:

- `/role give` — give a member a role for a period (minutes, hours, days, weeks), with a reason if you like.
  Given again, it is extended;
- `/role remove` — remove it at once;
- `/role list` — those in progress, for the whole server or for one member.

The bot removes the role when it expires (checked every minute), **even after a restart**: the list lives on
the site, in *Discord → Timed roles*, where **Remove now** brings the end date forward. A member who leaves and
rejoins the server before the end gets their role back (*Give the role back* setting).

Refused: `@everyone` and roles held by an integration, roles **linked to a site group** (group synchronisation
manages them), and — as Discord does for this right — a role placed above the bot, or at the level of whoever
gives it or above. The duration is capped by the *Maximum duration* setting (90 days by default, one year at
most).

## Linked sanctions

Off by default: *Discord → Features → Linked sanctions*. Two directions, each with its own setting.

**From the site to Discord** (*Apply site-wide mutes and bans on Discord*, on with the feature): a sanction
covering **the whole site** follows the member's linked Discord account.

| On the site | On Discord |
|---|---|
| Mute | a timeout until the end date — renewed by the bot beyond the 28 days Discord allows |
| Temporary ban | a ban, lifted by the bot at the end date |
| Permanent ban | a ban |

A warning, a restriction, a shadow ban (it must stay invisible) and a sanction limited to one module (the
forum, the chat rooms…) never carry over. A sanction awaiting approval carries over once it is approved.
Lifted on the site, it is lifted on Discord — the bot only removes what it set (its reason reads “NeoFrag
#number”) or what came from Discord. It reads the current sanctions again at startup, then every ten minutes:
an end date passed while it was stopped is caught up.

**From Discord to the site** (*Report sanctions imposed on Discord to the site*, off by default): a ban or a
timeout set on Discord reaches the site if the moderator is a person whose Discord account is linked to a site
account that has that right — *moderate* for a timeout (a mute on the site), *ban permanently* for a ban —,
and if the sanctioned member linked theirs. The site judges it again as if it had been issued there
(hierarchy, approval); otherwise it stays on Discord, and the bot's log says why. A sanction that came from
Discord is lifted on Discord (unban, remove the timeout) or on the site; a site sanction is only lifted on the
site.

The invite link does not give the bot these rights: add them to its role — *Ban Members* and *Timeout
Members* for the first direction, *View Audit Log* for the second. The bot says in its log what it is missing.
Discord refuses to sanction the server owner and a member placed above the bot's role.

## Collectives

Off by default: *Discord → Features → Collectives*. Each site collective whose *Discord* box is ticked
([Collectives](admin.md#collectives)) receives on the server:

- a **role**, named like it — it is the collective's group, linked to this role: the *Roles and nicknames*
  feature gives it to its members who linked their account, and removes it from those who leave;
- a **text channel** in the *Collectifs* category (the name can be set; the bot creates it), closed to
  `@everyone`, open to this role.

The bot creates what is missing at startup, when the configuration changes (a collective created or
renamed), and every five minutes: a role or channel deleted by hand comes back, a renamed collective is
renamed. A collective deleted, or whose box is unticked: the bot deletes its role, and its channel **stays**,
seen only by the server's administrators. The bot's role needs *Manage Roles* and *Manage Channels* (in the
invite link), and must sit above the roles it creates.

## Security

- On the bot's machine: the site address and its access key, nothing else.
- The bot token is encrypted in the site's database with the site's own key. Only an access key with the
  `discord:bot` right can read it — the one the administration creates for the bot, revocable at any time.
- The bot never mentions anyone: a copied message cannot notify `@everyone`.
- It does not need “Administrator”, and the invite link does not ask for it. If it has it anyway (a role given
  by hand), it says so in its log at every startup: remove it — an administrator bot whose token leaks opens
  the whole server. It also names the invite link permissions its role is missing.
- What comes from Discord — a thread name, a username, a comment — is stored on the site like what you write
  there yourself: it is displayed, it is not executed.

## Writing a feature

The bot is extended through **features**, each in its own folder, in TypeScript. How to do it — a feature's
contract, its settings, its commands, its translated texts, its tests — is in the bot's contributor guide,
`CONTRIBUTING.md`, at the root of its code: [NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
