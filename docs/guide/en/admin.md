# The administration panel

The administration lives under **/admin**. It follows the NeoFrag Reborn look (dark navy + teal, light or dark
as you like) and is organised around a **sidebar**.

## Landmarks

- **Sidebar**: modules are sorted into nine sections, plus *System* (Settings, Users, Permissions, Themes &
  Addons, Monitoring…). Sections fold out like an accordion; an empty section is hidden.

  | Section | Modules |
  |---|---|
  | *Content* | Pages, Blog, News, Slider, Quotes, Recipes, Menus |
  | *Community* | Forum, Collectives, Discussion, Comments, Guestbook, Emojis |
  | *Activities* | Calendar, Surveys, Gamification, Classifieds |
  | *Gaming* | Events, Teams, Games / maps, Recruitments, Awards, Partners |
  | *Knowledge* | Wiki, FAQ, Glossary, Downloads, Link directory, Places map |
  | *Media* | Media, Galleries, Files, Webradio |
  | *Outreach* | Newsletter, Email templates, RSS feed, Discord, Webhooks, API |
  | *Support* | Contact, Bugtracker, Moderation |
  | *Monetization* | Shop, Donations, Payments, Ad management |

  A marketplace extension this arrangement does not know goes into *Other modules*. The Live Editor's
  *Navigation* menu follows the same sections, as submenus, with a search.
- **Pins**: hover a module in the sidebar and click the pin to add it to *Pinned* (shortcuts at the top,
  remembered by your browser).
- **Quick search**: `Ctrl/Cmd + K` opens the command palette to jump to any module or action.
- **Header**: breadcrumb + contextual actions (Permissions, Configuration, Help), “View site” and the
  light/dark switch.

## Common tasks

| I want to… | I go to |
|---|---|
| Publish a news item / a blog post / a page | *Content* → the module concerned |
| Sort blog posts into categories and series (a post in several parts) | *Content → Blog*, “Categories” and “Series” buttons; a post's series and its rank are chosen in its form |
| Manage the forum and comments; moderation | *Community*; *Support → Moderation* |
| Create a private team — an editorial team, a moderation team — with its forum section and Discord channel | *Community → Collectives* — see [Collectives](#collectives) |
| Collect membership fees, follow paying members | *Monetization → Payments* — see [Membership fees](#membership-fees) |
| Link Ko-fi, HelloAsso, TipeeeStream or PayPal to donation campaigns | *Monetization → Donations*, “Linked services” card — see [Linked donations](#linked-donations) |
| Create forum topic prefixes (“Question”, “Tutorial”…), choose a forum's icon | *Community → Forum*, “Prefixes” button; the icon in the forum's form |
| Manage members, groups, sessions | *System → Users* |
| Decide who can do what | *System → Permissions (matrix)* — role × action grid (green = allowed, grey = default, red = never) |
| Change the theme / install an addon | *System → Themes & Addons* |
| Site settings (name, home page, registrations, security, copyright) | *System → Settings* |
| Build pages with the mouse | *System → Live Editor* |
| Link the site to a Discord server: roles, nicknames, forum ↔ Forum channel, Bugtracker, timed roles, sanctions | *Outreach → Discord* — see [The Discord bot](bot-discord.md) |
| Backups, updates, site health | *System → Monitoring* |
| Read the site's errors, find the one a visitor reported | *System → Monitoring → Error log* |
| Review sensitive actions (settings, addons, accounts, API keys) | *System → Users → Audit log* |

> **Monitoring** gathers the **site's health** (file integrity checks, disk space, server info), **updates**,
> **backups** (create / download / restore) and the address of the **scheduled task** (cron) that publishes
> scheduled content.

### Monitoring

*System → Monitoring* sums up the essentials at the top — version and update, PHP, backups, diagnostic tools
switched on — and then reads in five tabs: *Overview* (alerts, site health, storage, site address),
*Backups*, *Diagnostics*, *Server and security* (configuration, webmaster password, scheduled task) and
*Files* (the installation compared with the published release). The page comes back to the tab you were on;
an address like `admin/monitoring#diagnostic` opens the right one.

Every update through the button first takes a full **backup** of the site, database included. The site
always keeps the **five most recent**; the others remove themselves after **thirty days**, and never more than
**ten** remain. Download the one you want to keep longer.

### The error log

*Monitoring → Error log* shows what failed on the site, without going through FTP: errors grouped (the same
error seen a hundred times stays one line), from newest to oldest, sorted by severity — **fatal**, **error**,
**warning**, **information** —, with their last full occurrence to unfold. You filter by severity and period,
and search for a word.

When a page crashes, the visitor reads “An error occurred” and an eight-character **reference**
(`3F9A1C07`); if they pass it on to you, look it up in the log: it leads to the exact line. Server paths,
passwords, keys, e-mail addresses and IP addresses are masked on screen; the whole file can be **downloaded**
(to send to whoever helps you) and **emptied** (the old one is kept next to it, `logs/php.log.1`). Monitoring
also reports the errors of the last 24 hours, and a `logs/` folder the site can no longer write to.

### Diagnostic tools

The *Monitoring → Diagnostics* tab switches three tools on for an hour; each turns itself off, or with a
click. No more editing `config/neofrag.php` over FTP.

- **Debug mode**: a bar appears at the bottom of pages (database queries, computing time, memory, visit
  data), and a page that crashes shows the error's details — **for logged-in administrators only**: visitors
  see the normal site. The log tells you a problem exists; debug mode helps you understand it by reproducing
  it.
- **Page trace**: every page served, with its database queries and their duration. *Read the trace* shows the
  latest pages, newest first, searchable by address; the values passed to queries are masked on screen, the
  full file can be downloaded. Heavy: for the time of a diagnosis.
- **Translation check**: texts with no translation in the visit's language are recorded and listed in
  *Missing translations*, with the extension that uses them. Meanwhile, administrators see the language's
  flag in front of each translated text: a text without a flag did not go through translation.

A tool switched on in `config/neofrag.php` can only be switched off there; the card says so.

### The site address

Links in e-mails (forgotten password, registration confirmation), returns from external logins and shares
are built on the address recorded at installation. After a domain change, the *Site address* card
(Monitoring, *Overview* tab) points it out: open the administration through the new address, then the *Use*
button, followed by that address, records it (webmaster password asked if one is set).

## Permissions

NeoFrag Reborn works with **roles**. The **permission matrix** (per module) sets, for each role, access and
actions. You assign roles to members and groups from *System*.

**Example — create a “Moderator” role**:

1. In *System → Permissions*, **create a role** “Moderator”.
2. In its column of the **matrix**, tick the actions you want (moderate the forum, manage comments…) →
   **green = allowed**.
3. In *System → Users*, edit a member and **assign them this role**: they get these accesses at once.

### Collectives

A **collective** brings members together around a project, away from everyone else: an editorial team, a
moderation team, a group of creators. It is not a gaming team (*Gaming → Teams*, which is shown to the
public): everything a collective receives is reserved for it.

*Community → Collectives → New collective*: its name, its description, and its **leader** (a username). The
site creates in one go:

- a **private forum section** — a category and its “Discussions” forum —, that only its members can read and
  write in;
- two site **roles**, *Collective “name”* and *… — moderation*, which the module grants and removes by
  itself: do not assign them by hand;
- if the *Discord* box is ticked and the bot running (*Collectives* feature), a **role and a private channel**
  on the server — see [The Discord bot](bot-discord.md#collectives).

The leader then manages members from the collective's page (*My collectives*, in the member area), without
being a site administrator. The ranks:

| Rank | Can |
|---|---|
| Leader | add members, appoint administrators and moderators, remove anyone below them |
| Administrator | add members, appoint moderators, remove moderators and members |
| Moderator | moderate the forum section (edit, delete, pin, lock) |
| Member | read and write in the section |

The leader is only appointed in the administration: a new leader replaces the old one there, who stays on as
an administrator of the collective. Deleting a collective takes its space away from its members; its forum
section and its Discord channel **remain**, visible to administrators only — nothing written there is lost;
delete them by hand if you wish.

### Membership fees

*Monetization → Payments*. A membership fee makes a member a **paying member** for a period: they get the
**“Paying member”** role — open to it whatever you like in *System → Permissions* (a forum section, pages) —
and join the **“Paying members”** group (its badge, and a Discord role if you link it). Two ways to collect:

- **online**, with Stripe: a pack of type *Membership (days)* (“Annual fee”, 365 days) that the member pays on
  the *Payments* page;
- **otherwise** (cash, cheque, bank transfer): the *Paying members* card → *Record a membership fee*
  (username, duration).

A new fee extends the membership from its end date. Seven days before, the scheduled task warns the member (a
notification); on the end date, it removes the role. *End the membership* stops it at once (a typing error, a
refund). Membership fees do not need Gamification — points and VIP do.

### Linked donations

*Monetization → Donations*, **“Linked services”** card: a donation made on one of these services is recorded
by itself in the chosen campaign, **only once**, even if the service notifies twice. Keys are stored
encrypted and are never shown again.

| Service | What you need | How the site checks |
|---|---|---|
| **Ko-fi** | on Ko-fi, *More → API → Webhooks*: the address shown in the card, and the verification token | the token, which only the site knows |
| **HelloAsso** | in the association's space, *My account → Integrations and API*: the client ID and secret, and the notification address shown | it re-reads the order through HelloAsso's API: the notification alone is not trusted |
| **TipeeeStream** | the account's API key | nothing to check: the site reads the donations itself, at each scheduled task, from the day the key is saved |
| **PayPal** | the *Record donations automatically* box | PayPal confirms each notification, and the money must have gone to the campaign's PayPal address |

A **membership** paid on HelloAsso makes the site member with the same e-mail address a paying member, for the
duration you set (Payments module). HelloAsso and PayPal donors do not appear on the campaign page — they did
not consent to it —; Ko-fi and TipeeeStream donors who chose to, do. The total counts every donation. To try
without real money: the *sandbox* boxes for HelloAsso and PayPal.

## Essential settings

In **Settings**: site title and description, favicon, contact e-mail, home page, time zone, search engines,
registrations, anti-bot security (captcha), maintenance, copyright, social networks. These settings feed the
themes and widgets.

### Registrations

*Settings → Registration* opens or closes registrations, can have new members **confirm their e-mail
address**, and sets two texts, **language by language** (one tab per site language).

**E-mail confirmation** (off by default): at registration, the member receives a link, valid for two days,
and cannot log in before opening it; a login attempt sends a new one (three per hour at most). A registration
through Discord, GitHub or Google does not need it. The two texts:

- the **rules**, which the visitor accepts when registering — through the form as through Discord, GitHub or
  Google, which show them before creating the account;
- the **welcome message**, sent through the private messages to the new member — including when they register
  with Discord, GitHub or Google —, in the language of the page where they registered. `[pseudo]` becomes their
  username. It arrives as text: headings, lists and links are kept, formatting is not; an address written in
  full becomes a link.

A language that does not have its text yet shows the common text: the first text saved serves every language
until they are translated.

### Search engines

What search engines read happens **by itself**:

- a **sitemap** per language, gathered at the root (`/sitemap.xml`) and announced by `robots.txt` — each module
  adds its public pages (the blog, the forum and its topics, the wiki, the gallery…), only those a visitor can
  read;
- in each page, its **reference address** (canonical), **links to its other languages**, and what share
  previews show (title, description, image);
- on the home page, **structured data** describing the site, its logo and its social networks (*Settings →
  Social networks*);
- search, profiles and the member directory are **not** indexed.

What you fill in, in **Settings → Search engines**:

- for each language, a **tagline** (the home page title becomes “Site name — tagline”, 60 characters at most)
  and a **description** (what search engines show under the title, 160 characters at most; empty, the one from
  General Preferences is used);
- a **share image** of 1,200 × 630 pixels, shown when a link to the site is pasted on Discord, X or Facebook —
  otherwise, the logo;
- the verification code of **Google Search Console** and **Bing Webmaster Tools**: create the site's property
  in the tool, choose verification by **HTML tag**, paste the tag (or just the code), then submit the sitemap
  there, `https://your-site/sitemap.xml`;
- **Notify search engines (IndexNow)**: ticked, the site reports every page that appears, changes or
  disappears to Bing, Yandex, Seznam, Naver, Yep and Amazon, within minutes, instead of waiting for their next
  visit. Google does not take part: for Google, it is the sitemap. The site's **scheduled task** must be
  running (*Monitoring* gives the line to install). The first pass only records existing pages; after that,
  only changes are sent.

Three more screens, from the same section:

- **SEO report** — what a search engine sees, measured on the site: the number of sitemap pages and their
  module, the home page texts in each language, the share image, Google and Bing, IndexNow, `robots.txt`,
  maintenance. Each point links to what fixes it.
- **Redirects** — an old address that leads to the new one, instead of “Page not found”: a ranking you earned
  is not lost. A **renamed** page or wiki page leaves its own redirect by itself; add those of an old site by
  hand (`page.php`, `/fr/old-page`…). The *Visits* column tells you whether the old address is still used.
- **A content's search settings** — in the edit card of a news item, a blog post, a page or a wiki page, the
  **Search engines** button: a title for search engines (60 characters at most, the site name follows) and a
  description (160 at most), in each language. Empty, they are the title and the beginning of the text.

### The captcha

The contact form and registration ask visitors for an anti-robot check. In **Settings → Captcha**, choose the
provider:

- **ALTCHA** (default, recommended): nothing to configure. It is hosted by your site, with no account and no
  cookie; the visitor sees a box that ticks itself while they fill in the form.
- **Cloudflare Turnstile**, **hCaptcha** or **Google reCAPTCHA v2**: create a site in their console (links on
  the page), then paste the **site key** and the **secret key**. The secret key is encrypted and never shown
  again. Without its two keys, the provider is replaced by ALTCHA.
- **None**: not recommended, robots then write freely.

ALTCHA makes mass submission costly for a robot; against a determined attacker, Turnstile or hCaptcha judge
more.

### The time zone

Everyone sees the site's times in their own time zone:

- a **member** can choose theirs in their profile (**Profile → Time zone**);
- otherwise, the site uses their **browser's**, from their second page;
- otherwise — first visit, robots, automatic tasks —, the **site's**, set in **Settings → General Preferences →
  Time zone**.

A time entered in a form (the start of an event, reopening after maintenance) is understood in the time zone
of whoever enters it. A date on its own — a birthday, a whole day in the calendar — never changes day. A
webradio's schedule is written in the site's time.

### Push notifications

A member can receive their notifications in their browser, **even with the site closed** — a forum post, a
reaction, an event reminder. To offer it, tick **Settings → General Preferences → Push notifications**. The
site must be served over **https**, and the server able to encrypt (OpenSSL with the P-256 curve, and cURL:
almost every host has them; otherwise the box stays unticked and the site says why).

Nothing reaches anyone until they ask for it: each member turns it on **device by device**, in **My account →
Notification preferences → This device**, and their browser asks for their consent. There they also choose,
type by type, what they receive this way, and can send themselves a test.

What you need to know, and say in your **privacy policy**: the browser goes through its push service —
Google for Chrome and Edge, Mozilla for Firefox, Apple for Safari. The site keeps each device's subscription
address there as long as the member does not turn push off (or delete their account). The message is
encrypted for that browser alone: the service carries it without being able to read it.
