# Changelog

This is the English translation of [CHANGELOG.md](CHANGELOG.md); the French version is the reference.

All notable changes to **NeoFrag Reborn** are recorded here.

Format inspired by [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
version numbers are inspired by [semantic versioning](https://semver.org/); a release that only changes the last number
(1.2.x) may nevertheless bring new features and removals.

NeoFrag Reborn is the community continuation of **NeoFrag** (Alpha 0.2.4 base), originally created by
Michaël BILCOT and Jérémy VALENTIN — an open source project under the LGPLv3 license.

---

## [1.2.49] — 2026-10-10

A big batch: an address with the wrong title leads to the right one, the list of pages not found, the text editor in
the page's language, the Events module usable without games — for a nonprofit or a club —, images of tickets coming
from Discord, and a long list of fixes found while rereading the whole changelog, including a match page that no
longer showed its match. The Discord bot moves to **version 0.2.7** (see its own changelog).

### Added

- **Images in a Bugtracker ticket**: a screenshot attached on Discord to a ticket or an idea, when it is opened or in
  a reply, now shows in the ticket on the site — with bot 0.2.7, which hands it over to the site as it already does
  for the forum. Ticket text stays plain text: only images kept by the site are displayed in it; the address of an
  image hosted elsewhere stays as text.
- **The list of pages not found**, in *Monitoring → Diagnostics*: every address the site could not find — without
  the language or the parameters —, how many visitors and bots requested it, when it was last requested and the
  last page visitors came from. A "Redirect" button opens the redirection form with the address already filled in;
  probes from bots looking for a flaw are listed separately. No IP address, no browser; an address no longer
  requested for 90 days is forgotten.
- **Abandoned editor images**, in *Monitoring → Diagnostics*: an image uploaded into a text and then removed, or
  the image of a draft never saved, stayed on the disk. The page finds them — no text of the site displays them any
  more, no content, message, setting, revision or trash item, and they are more than 30 days old —, shows them, and
  deletes the ones you tick.
- **The text editor speaks the page's language**: its menus, buttons and dialogs were in English everywhere; they
  are now in French, German, Spanish, Italian or Portuguese according to the page's language.
- **An animated GIF stays animated** in the editor: it used to become a still image. It is rewritten keeping only its
  frames, their timing and its loop. A GIF larger than 2,000 px on a side or with more than 1,000 frames still
  becomes a still image, as before.
- **Events without games**: the Events module no longer requires the Games and Teams modules. An association or a
  club installs it on its own — invitations, responses, recurrence, reminders; matches, their opponents and their
  scores come with the Games and Teams modules. It joins the "Community" and "Nonprofit / club" installation
  profiles.

### Fixed

- **An address with the wrong title redirects to the right one** (permanent redirect, 301): the content's title has
  changed, or the link comes from another language or has a typo. Blog posts, categories, authors and series,
  Awards, Bugtracker tickets, Calendar events, classified ads, surveys and recipes answered to any title —
  duplicates for search engines; news, events, recruitment offers, teams, Gallery albums and images, forums and
  topics, profiles, groups and partners answered "not found" at the old address of a changed title. A blog category
  page shows its title, no longer the text of the address; the page of an event type or team that does not exist
  now answers "not found".
- **An image uploaded in the editor no longer overflows** the page on pages that did not constrain it (a profile's
  "About" tab, events, partners, albums, recruitment offers, teams) nor on a phone: it takes at most the width of
  the column.
- A size below 1,000 bytes reads "685 B", no longer "685.00 B".
- **Forge**, in day mode: the muted text (dates, authors, places, "Registered on…") is slightly darker — it stayed
  just below the readability threshold.
- **Forge and Extend**: the thin lines that pick the slideshow image are easy to tap on a phone (they were only 3 px
  high); nothing changes visually.
- **Chronique**: the number of unread notifications, on the account icon, is much easier to read, by day and by night.
- **Discord**: when a forum topic or post, a ticket or a ticket comment is deleted, the record linking it to its
  Discord thread is now removed automatically a week later; it used to stay in the database forever.
- **Nothing stuck to the edge any more**: the list of banned IPs and its add form (the help sentence and the button
  touched the edges of their card), the search field of the event list, a table's pagination in Granite, the pagination
  and the "Follow" button in Nebula, a long username in the member list. On a phone, the slideshow and permission
  tables scroll inside their card instead of overflowing it.
- **No more buttons touching each other**: "Invite", "Exit"… in a conversation, on a phone; "Back" and "Post topic"
  on the forum; the two buttons of the forum search in the administration.
- **Forge**: on a phone, the member area menu is back — it slipped under the page's card; on a computer, it stays in
  view while scrolling. The green "View my application" button is readable again.
- **A match page** shows its match again — the team, the opponent, the score, the game and the rounds, which it no
  longer displayed. The team, the score and the opponent fit on one line, as does each round, on a phone as on a
  computer.
- **Recruitment**: the "My application" page shows the right position and team again — it displayed the icon's name
  instead of the position. **Forum**: editing a forum, in the administration, shows its description and category
  again — it showed the title instead, and saving could move the forum elsewhere.
- **Moderation**: a sanction is deleted automatically three years after it ends (expired or lifted; a warning, three
  years after it was given); a sanction with no end (a permanent ban, for example) stays as long as it runs. They
  were kept without limit.
- The report button (the "Report this content" flag) is easy to tap on a phone; in the administration's *Email
  templates* list, the variables in an email subject (`{{date}}`) stand out from the text.
- **Newsletter**: the "Group" target and the list of campaigns name each group in the administration's language;
  they took it in a random language ("Criador de addons", "Mitwirkender").
- **Correct labels**: on a member's profile, the "Contacter" button showed "Contact" in French; the Extend theme
  writes "Featured" and "Discover" in the page's language (they stayed in French); the Blog and addons page "Grid"
  button is no longer translated as "Schedule" in English, German, Spanish and Italian; the French ticket type
  "Demande de feature" becomes "Demande de fonctionnalité". In English, the login button says "Log in", the menu
  "Log out", and "View my profile" is used everywhere. In Monitoring, the "Storage" card wrote "Sauvegarde",
  "Libre" and "Total" in French in every language.
- **Pulse, at night**: the number of unread notifications, on the account icon, is readable — a dark figure on the
  light brick; in white, it only had 3.9:1.
- **The Donations widget** shows the initial of a donor whose name starts with an accented letter; it displayed "&".
- **Resetting your password** refuses, like registration and changing it, a password identical to the username: the
  reset did not compare it.
- The Discord bot moves to **version 0.2.7** (0.2.6, then 0.2.7): it hands the images attached to tickets over to the
  site (see above), and its log no longer records ordinary reconnections to Discord, only an outage lasting more
  than a minute (see its own changelog).

## [1.2.48] — 2026-10-09

A big batch: a security audit (access, injections, accounts, files), moderation that finally works — its sanctions
now apply to everything members write —, a new email address confirmed before it counts, deletion of inactive
accounts, and sign-in with Discord, GitHub or Google fixed. The Discord bot moves to **version 0.2.5** (see its own
changelog). **On an HTTPS site, everyone signs in again once after the update**: the session cookie changes its name.

### Security

- **The forum search, open to visitors, allowed SQL injection** through a word written between quotes; the private
  messaging search had the same flaw. That word is now cleaned like the others, and escaped by the database itself.
- **A submission coming from another site is refused** (POST, PUT, DELETE…), based on what the browser says
  (`Sec-Fetch-Site`, otherwise `Origin`): a trapped page can no longer make a signed-in member act without their
  knowledge. A client without a browser — the bot, a payment service — is not affected.
- **Twenty actions that changed something through a plain link** now require the session token, or only accept
  POST: following a topic, making it an announcement, marking everything as read, leaving, archiving, deleting or
  restoring a conversation, reacting, marking notifications as read, giving one's availability for an event,
  opening or closing the site and registrations, starting a backup or an update, deleting a recruitment field…
  Signing out through a link without a token asks for confirmation with a button.
- **Over HTTPS, for a site installed at the root of its domain, the session cookie has the `__Host-` prefix**: a
  subdomain (a demo, a webmail) no longer receives it and can no longer impose one. A site installed in a subfolder
  keeps a cookie without this prefix, which only changes its name.
- **Accounts**:
  - the "forgot password" link also worked as a registration confirmation link, which signs in without asking
    anything for two days: each link now only works for its own purpose;
  - changing one's password drops a pending new address and "forgot password" links;
  - sign-in failures are counted per account — however it is written — and per network: an IPv6 address counts for
    its /64 network, in every attempt limit of the site;
  - turning on two-factor authentication or linking a Discord, GitHub or Google account asks for the password again;
    five errors lock this confirmation for a quarter of an hour;
  - "forgot password" answers the same whether an address is registered or not, and registration only says that an
    address is already taken five times per hour and per network;
  - the sign-in history records the address of the connection, no longer a header the browser chooses.
- **Twelve access leaks closed**: the history and revisions of a draft wiki page; VIP-only forum categories, through
  search and profiles; hidden groups; the image of a closed album; matches of a restricted type; the presence that
  participants had hidden; comments and reactions on hidden content; reporting a conversation one is not part of.
- **Two old private messaging addresses returned the messages of any conversation**, even private, to anyone with
  the read right — visitors included: removed. Posting an image to the gallery only required the right to see the
  album.
- **Forum and conversation attachments are served by the site**, to whoever can read the topic or the conversation,
  no longer by the web server to anyone who had their address. An image or a PDF is displayed, anything else is
  downloaded; an attachment a browser would execute is refused on upload. On Apache, the shipped `upload/.htaccess`
  takes care of it; on nginx or Caddy, copy into your server configuration the rule that denies `upload/forum/` and
  `upload/talks/` (see the shipped `nginx.conf` and `Caddyfile`).
- **What the server fetches itself** (image relay, RSS feeds, webhooks) is limited to strictly public addresses, on
  ports 80 and 443.
- **Stripe keys are no longer shown in clear** in the administration: stored encrypted, never displayed again.
- **Demo**: visitors' IP addresses, host names and referring sites are no longer shown there; an ad, a link, a partner
  leading outside the site is shown on a page that says where it leads, instead of redirecting there.
- **A `javascript:` link can no longer be displayed** in place of an address: the page a session came from
  (administration), a match's links (opponent's website, stream, article). The link a report gives to the reported
  content can only lead to a page of the site.

### Added

- **A new email address waits for its confirmation**: the link goes to the new address (valid for two days), the old
  one is notified, and "My account" offers to resend the link or cancel. Until now a typo cut the member off from
  everything the site sends.
- **Inactive accounts are deleted**: with no visit for three years — adjustable in *Settings*, `0` for never —, an
  email warns the member a month before, a visit cancels everything. Never an administrator.
- **Sanctioning a member without waiting for a report**, from their moderation history, which their administration
  page links to.
- **The report button** (the "Report this content" flag) on the guestbook, gallery images, classified ads, tickets
  and their comments.
- **"My sanctions"** in the member area, for whoever had one in the year: its kind, its reason, its period.
- **Mediation**: from a report, the moderator proposes a private conversation between them, the reported member and
  the one who reported. The reported member will see who reported them there, so it only opens with the reporter's
  agreement, given on a page that explains what they accept; if they decline, their report stays anonymous and goes
  on as usual.

### Fixed

- **Moderation sanctions apply to everything members write**: forum, private messaging, comments, guestbook,
  bug tracker, recruitment, classified ads, gallery, profile, images uploaded in the text editor. Only the forum and
  messaging mute were checked, and after sending. The form gives way to a notice that says what the sanction
  forbids, until when, and why; the "Restriction: links" sanction refuses a text that carries a link, without losing
  it.
- **No sanction could be issued from the moderation screen**: an internal error prevented it, so automatic
  escalation never triggered. Reviewed at the same time: the settings (boxes that could not be unchecked, suspicious
  reporter threshold), scopes, durations (a temporary ban without a duration became permanent), approval by a
  higher-ranking moderator, the moderators' panel, duplicate reports, pages 2 and beyond, the IP list. A moderator
  can no longer lift a sanction aimed at them, nor that of a member of equal or higher rank, nor one issued by a
  higher-ranking moderator.
- **Shadow ban works**: what a shadow-banned member writes is only shown to them and to moderators, and nothing is
  broadcast — no notification, no Discord, no subscribers.
- **An extra role — Moderator — removed a member's rights**: a moderator lost the forum and private messaging. A role
  adds rights, it no longer removes any.
- **Emails are sent in the language of whoever receives them** — sanctions, forum replies, private messaging —, no
  longer in that of the member who triggered them.
- **Signing in with Discord, GitHub or Google failed** as soon as a service was configured (the OAuth library had
  changed), and sent the service a return address it refused. A refused return now says why instead of an error
  page, and the Discord avatar is imported again.
- **Guestbook, administration**: approving, rejecting or deleting a message led to a "page not found" error.
- **A form field's error is written under the field**, on a phone as on a computer: it could only be read in a
  tooltip on hover.
- **The administration's top bar stays at the top** when scrolling; on a phone, only the menu and the breadcrumb stay
  stuck.
- **Thirty-four translation mistakes** in twelve language files ("There are 1 user", deletion confirmations showing
  `< br / >`, English plurals…).
- The website address declared by 48 core addons is the project's, no longer that of the original NeoFrag. The
  administration's *Email templates* page no longer lists the templates of a module that is not installed. An error
  in some forms (guestbook rate limit reached, account deletion, two-factor authentication, password, creating a
  member in the administration) no longer leads to a "page not found" error.
- **Demo**: an ordinary member could not open a topic, reply anywhere or apply to anything.
- **The "My data" export includes the reports the member made**: they were missing from the archive.

### Changed

- **A widget tied to a module now depends on it** (21 widgets): without that module, or if it is disabled, the
  widget is no longer displayed and the live editor no longer offers it; "Add" refuses a widget archive without its
  module.
- **The product documentation is no longer shipped** in the wiki of a new site, which arrives empty; it lives on the
  project's site. A new installation no longer receives the settings of themes it does not have.

### Removed

- The old `ajax/talks` addresses of private messaging, and its old message report, which only wrote to the audit
  log, with its "Message reports" administration page: a message is now reported through moderation.

## [1.2.47] — 2026-10-09

The Blockcraft hotbar, on phones.

### Fixed

- **The Blockcraft hotbar spans the whole width of the phone**: its slots share the screen equally and each name fits
  in its own — on two lines for "My space", hyphenated for a German or Portuguese word that is too long
  ("Neuig-keiten"). It kept the same width whatever the screen (seven 47 px slots): an empty space on the right,
  narrow slots and "News" spilling out of its slot. The white frame of the selected slot no longer touches its name.

## [1.2.46] — 2026-10-08

The bars at the top of the page no longer cover anything, the bell comes to Nebula, and the *Themes & Addons* page
can reinstall a theme again.

### Fixed

- **Whatever sticks to the top of the screen now sits below the bars at the top of the page** — the demo bar, the
  maintenance bar and the permissions preview bar. As soon as you scrolled, half of the Extend, Chronique, Pulse and
  Nebula headers slid under the demo bar; the bottom of the Forge rail and of the administration sidebar went off
  screen; on phones, the Forge crest and account were hidden. The main template now stacks the bars and publishes
  their height; the themes, the administration and the live editor line up below them, and on-screen notifications
  appear under them.
- **Sticky module columns line up below the theme header**, whatever it is: the member area menu (16 px from the top,
  it slid under a 60 to 70 px sticky header), the wiki contents, an article's contents and side box. A column taller
  than the room left to it no longer sticks: its bottom stayed off screen until the end of the page — the Extend
  member area menu, on a laptop screen.
- **A link to an anchor stops below the sticky header** — a forum post, a wiki or article section — instead of placing
  its title underneath: each theme set this offset by hand, without counting the bars.
- **"Reinstall to defaults" and "Delete" a theme go through from the "Themes & Addons" page**, and the order of
  languages and authenticators is saved: the confirmation was refused, "invalid security token".
- **The demo bar is readable**: its text, white on green (2.4:1 contrast), turns dark.
- **On phones, the buttons of an administration header move below the title**: on the articles page, "Categories",
  "Series" and "New article" covered "4 published", and the last one went off screen.
- **A site opened under another name than the one in its configuration** (with or without `www.`, for example)
  shows the images written with the configured address: the browser's security policy blocked them, as they came
  from another site in its eyes; they now go through the site's image relay.
- **For contributors**: the `check-mise-en-page` tool no longer reports false overlaps inside a collapsed block
  (`<details>`), which Chrome keeps in the page without showing it — 48 false alerts on the Monitoring *Error log*
  page —, nor in the error messages that page shows on purpose.

### Added

- **The notification bell in Nebula**, as in the other themes: the member area announces it "at the top of every
  page", and Nebula had none.
- **For theme authors**: a theme's sticky header carries `data-nf-entete`, and whatever sticks to the top of the screen
  lines up on `--nf-haut` (and a column on `--nf-entete`); a new check, `check-colles`, verifies it — run on 1.2.45,
  it found twenty faulty CSS rules (*Create a theme*).

### Removed

- **Unused style rules in the Nebula stylesheet** (a bar, buttons and a footer inherited from another theme): no
  Nebula template used them.

## [1.2.45] — 2026-10-08

The notification bell, reviewed in every theme that shows it, and two-factor login in one go.

### Fixed

- **With two-factor authentication, the code is asked for right after the password**: the login window gives way to
  the code window, without reloading the page. It used to close, and you had to click "Log in" again to see the code;
  from the member area form, the code window opens on reload.
- **The bell badge sits in its corner** in every theme that shows the bell: the module's stylesheet, loaded after
  the theme's, put it back inline, and in Extend and Forge it landed in the middle of the button, on the bell.
- **The bell stays in the middle of its button** when a badge sits on it: an icon that was no longer alone in its
  link took a right margin, and slid three pixels.
- **"Notifications" and "Mark all as read" no longer touch** at the top of the list: it keeps its width when the theme
  narrows its menus, and the two texts keep a gap, wrapping if needed.
- **Dates are shown in the visitor's language format and time zone** in the bell list, the revision history and a
  report's tooltip, which showed the raw database timestamp.

## [1.2.44] — 2026-10-08

A fix for 1.2.43.

### Fixed

- **The third-party filter works under PHP-FPM and Apache.** It runs at the end of the request, when the current
  directory is no longer the site's: it could not find the image relay key any more, and the page went out unfiltered.
  Images from other sites, which the security policy now refuses, did not show (the Discord widget avatars), and an
  embedded video would have loaded without its notice. The defect did not show with PHP's built-in server, which does
  not change directory at the end of the request.
- **The PHP error log also keeps errors from the end of the request**, fatal errors included: its path was relative,
  and was then resolved from the filesystem root (`/`).

## [1.2.43] — 2026-10-08

Privacy: no visitor is sent to a third party any more without their consent.

### Added

- **A real choice about services from other companies.** The cookie banner only controlled Google Analytics, while
  videos, the Discord widget and captchas loaded whatever the visitor answered. The **"Manage my cookies"** window,
  opened from the footer of every page, shows the site's own cookies, then each service, to accept or refuse one by
  one. In place of a YouTube, Twitch, Vimeo or Dailymotion video, a Spotify or SoundCloud track, or the Discord widget,
  a notice says who would receive the visitor's IP address, with "Show" and "Always allow". The banner only opens if
  the site really has something to ask up front (audience measurement, a captcha run by a third party); "Accept all"
  and "Refuse all" carry the same weight. The choice is kept for six months, and its record, without the IP address,
  for thirteen months.
- **Settings → Privacy**: the legal notice and privacy policy pages, chosen from the published pages. Every theme's
  footer links to them, next to "Manage my cookies".

### Changed

- **Fonts are served by the site**: the themes and the "Site font" setting took them from Google Fonts, and every
  visitor's browser sent its IP address to Google, on every page.
- **Images from other sites are served by the site** — a Discord, Steam or Twitch avatar, a GIF in a conversation, an
  image pasted into an article, the forum or a classified ad, an ad banner: the site fetches them itself and keeps them
  for seven days, and the browser no longer contacts their host. The places map tiles too.
- **The security policy now only accepts the site itself** for images and script connections (except Google
  Analytics, if configured). It now lets the players of known services (YouTube, Twitch, Vimeo, Dailymotion, Spotify,
  SoundCloud, Discord) display, which it used to block: a video in an article did not show. A script from another site
  pasted into an "HTML code" widget or an ad no longer runs: only Google Analytics and the chosen captcha get through.
- **Until the visitor accepts a third-party captcha, ALTCHA replaces it**, served by the site: no one has to hand their
  data to Google, hCaptcha or Cloudflare to write to the site or sign up.
- **No more tracking pixel in the newsletter**, and no more open rate: a tracker the subscription did not ask for
  (CNIL recommendation of 12 March 2026 on tracking pixels in emails).
- **Retention periods**: the audit log one year, the anti-abuse counters one day, newsletter subscriptions never
  confirmed thirty days, the newsletter sending queue ninety days; for a report handled more than a year ago, its
  author's IP address and the copy of the content are removed. The login cookie ends when the browser closes, except
  with "Remember me" (one year), which is no longer ticked by default.
- **No more Google maps in texts**: a Google Maps map embedded in a text is no longer accepted. The product's map is
  the "Places map" module, based on OpenStreetMap.
- The themes' "Powered by NeoFrag Reborn" link, the `{neofrag}` copyright keyword and the admin footer links lead to
  the project's site; they used to lead to the original NeoFrag site.

### Fixed

- **No IP address is sent to neofr.ag any more**: the country flag next to session addresses is removed; to get it, the
  administrator's browser sent all of these addresses to the original NeoFrag site.
- **Deleting an account as an administrator really erases** the profile, linked accounts, login history and
  notifications, like the deletion a member requests themselves; it used to only close the account.
- **The "My data" archive now downloads**: three of its lists (forum topics, comments, conversation messages) read
  columns that do not exist, and the archive failed.
- The tooltips of the session list and the gallery now show: they carried a Bootstrap 4 attribute, which Bootstrap 5
  ignores, and stayed empty.
- The Steam widget avatar address was written into the page as is; it is now escaped, like the rest of the widget.
- The "Open the channel" tooltip and the "Close" button of the Twitch widget player, left in French, are translated.

## [1.2.42] — 2026-10-07

A redesigned theme: **Extend**, the website as an online game launcher.

### Added

- **Extend 2.0.0 "Launcher"** — a brand-new layout, chosen from three mock-ups. A tab bar at the top, in
  tight capitals, the open tab lit by a blue line; on the home page, a **large showcase** (the slideshow, its title in
  big letters and its "Discover" button; without a slideshow, the site name on the theme image), then the upcoming
  events as cards, the news as thumbnails, the latest results; elsewhere, the page title on that image. On the right,
  on every page, an **always-open panel**: who is online, with avatars, then the chat channel (and, on forum pages, the
  forum's statistics). At the bottom, a **status bar**: the place for the "Game server", "TeamSpeak 3 server" and
  "Discord server" widgets, the language, then "Powered by NeoFrag Reborn", the copyright and the legal links. The
  forum as a library, the member area as a player card (banner, ringed avatar, gamification tier in the bar). On
  phones, the tabs move to the bottom of the screen, with "Online", which opens the
  panel as a drawer, and "More" beyond four sections. Steel blue on navy, night by default, day on demand; Saira
  Condensed headings, Albert Sans text. The logo, showcase image, background and colors are adjustable. Based on
  Chewbaka's theme, whose name and license it keeps.
- **"Who is online? (list)"**, a new display of the Members widget: the three counts (administrators, members,
  visitors), then the people themselves with their avatar and last activity.
- **"A public channel: its latest messages"**, a new display of the Discussions widget: the last four messages of a channel
  open to all members, and the link to write in it. A visitor does not read the messages: they are invited to log in,
  as in the messaging itself; the staff channel is never shown.

### Fixed

- **A blog article's related posts no longer overflow on phones**: the box of a post without an image took its width from
  its row height and stuck out of the screen by a few pixels (at 414 px, in every theme).
- **Blockcraft, reviewed on the published demo**: on a phone, the page banner shows the whole landscape (cropped, it
  only showed tree trunks), without the sun or moon, which a long title ran over; the demo sets an example server
  address (`play.example.org`), so the "Copy the address" button can be seen.

## [1.2.41] — 2026-10-07

A redesigned theme: **Blockcraft**, the website of a block-game server.

### Added

- **Blockcraft 2.0.0 "Spawn + Inventory"** — a new layout, chosen from four mock-ups. On top,
  a sky above a block landscape (hills, trees, stone), the sun and drifting clouds, the moon and stars at night; on the
  home page, the server name in large letters and **its address, copied in one click** ("Server address" setting), and
  the Discord when it is set. Navigation is an **item hotbar**: each section in its slot, with the item that stands for
  it (the book for news, the map for the forum…); on a phone, it sticks to the bottom of the screen, within thumb's
  reach. Square-cornered blocks with hard shadows, the **forum as chests** (a plank lid per category, each forum in its
  slot, an enchanted slot that shimmers when there is something new), the **member area as the character screen**, a
  stone footer. Jersey 10 headings, Rubik text, VT323 figures; "meadow" day, "starry sky" night. Every pixel is drawn
  for the theme. By day, the sky texts are dark: white, they would only have been readable through their shadow.

### Fixed

- **The side column of Pulse, Chronique and Blockcraft no longer distorts the widgets placed in it**: its layout also
  reached inside the widgets, and "Who is online" pushed its figures away from their labels there.
- **The selected tab of a member's profile is readable in Pulse, Chronique and Granite**: these themes changed its text
  without changing its background, which the module paints in the accent color — dark text on the accent.
- **A group's icon no longer touches its name** (for example "Main team", in the profile and the member area): the
  themes' shared base left no space between the icon and the name — in the six themes that load it.
- **An event's comments keep their icon** in Pulse, Chronique and Granite: these themes removed the icon from every link
  in a card footer, and the link only said "0 →". Only widget footers ("View the calendar →") now lose their icon.
- **The selected pill of the messaging module is readable** ("All", "Archives"…) in Pulse and Chronique: the shared base
  gave its text the link color and its icon the accent color, on an accent background (1.1:1).
- **Granite**: buttons in a card footer ("Post an image", "View my application", the sessions pagination) took the red
  of links on the ink (2:1); "Lift this sanction" only had 3.7:1; on a phone, the front-page drop cap slid over the "By …
  on …" line below it; small buttons are 24 px high.
- **Pulse, at night**: the counter in the member area menu is readable: white on the lighter night brick, it only had
  3.9:1; it now keeps the day brick.
- **The member area menu, on a phone, snaps to the start of a tab**: centered to the pixel, it left the previous tab cut
  off at the start of its name ("…fications").
- **Turning on "Debug mode" from Monitoring no longer crashes its page**: the response crashed whenever a notification
  was waiting ("Cannot use object of type stdClass as array").
- **The guides count the themes in the package**: "Concepts" announced four (Chronique and Pulse were missing), and
  "Create a theme" named four themes on the shared base instead of six.

### Changed

- **`check-mise-en-page` no longer cries wolf**: text in the cookie banner or in a bar stuck to the bottom of the screen
  passes over a chart on purpose (only the same layer makes a conflict); the hidden part of a text — the code of a
  scrolling block, a clamped description — no longer "overlaps" the next column; a tab in a scrolling strip is no
  longer "lost past the left edge". Fewer false defects, and the real ones still come out (tested on a booby-trapped
  test page, with the old probe and the new one).

## [1.2.40] — 2026-10-07

Search engine indexing reviewed after the Search Console alerts on the official site: Google did not index forum
topics or wiki pages, and was offered empty pages. And the welcome message, which was no longer sent.

### Fixed

- **Language-less content no longer presents itself as duplicates**: a forum topic, a wiki page, a ticket, an event,
  a classified ad, a recipe, a survey, a job offer, an award, a donation campaign — written once — answered under each
  of the site's languages, only the menus translated, and every address declared itself canonical. Google picked
  another one and indexed none of them ("Duplicate, Google chose different canonical than user": on the official site,
  in six languages, 59 pieces of content listed six times, 354 of the 580 addresses in its sitemap). Their canonical is
  now in the site's first language, the only one announced in `hreflang` and in the sitemap. The same goes for the pages
  made of such content: the FAQ, the glossary, quotes, links, downloads, the shop and the event list.
- **Empty pages are no longer offered to search engines**: the web radio with no stream or show, the guestbook with no
  message and the donations page with no campaign were in the sitemap, in every language — "soft 404s" for Google.
  They are left out while empty, and declare `noindex`.
- **`/fr/forum/` now redirects permanently to `/fr/forum`**: the redirect of an address ending with a slash, or with a
  doubled one, was temporary (302) — and the first one dropped everything after the `?`; it is now permanent (301) and
  keeps the end of the address.
- **The welcome message is sent again**: the Members module could not find the messaging module, and a new member
  received nothing. A module loading another one (`$this->module(…)`) always got "nothing" — the same defect deprived
  comment webhooks of the title and address of the commented content.
- **Update backups no longer pile up**: the site always keeps the five most recent ones and removes the others after
  thirty days, but closely spaced updates let them all through — the official site carried forty, 663 MB. No more than
  ten ever remain now, whatever their age.

### Changed

- **`check-seo` compares languages with each other**: it samples the same path in each sitemap and reports the same
  text served under several languages, each one canonical. It declared the official site sound; it now finds these
  duplicates there. A module can declare its language-less content: a sitemap entry marked `'sans_langue' => TRUE` is
  only listed in the first language's sitemap, and `nf_seo_sans_langue()` sets the page's canonical ("Create a module"
  guide).

## [1.2.39] — 2026-10-06

Pulse reviewed on the published demo: the forum on a phone no longer sticks its buttons together, in every
theme; and a blog post that still went past the screen edge on phones.

### Fixed

- **The forum on a phone no longer sticks its buttons together**: the bar of a topic (Back, Reply and the moderation
  tools) and the bar of a forum keep a gap between their buttons and between their rows; the buttons of a message move
  above its date, which no longer breaks over three lines; a topic title no longer touches the "Follow" button. In
  every theme.
- **A blog post no longer overflows on a phone**: a long piece of code (a file path, for example) breaks, and a table
  that stays too wide scrolls within its column (the 1.2.0 post went past the screen edge).

## [1.2.38] — 2026-10-06

A new theme: **Pulse**, the common home of associations, clubs and communities, with three additions that serve every
theme — the site in figures, the next event and the latest photos.

### Added

- **The Pulse theme.** A light bar that stays on top, with the sections as pills, the account and the "Join" call always
  in sight (a "Menu" button on a phone); the home page is a mosaic of tiles — the site name and motto on the home image,
  the next event, the site in figures, the news, the agenda, the poll, the discussions, the documents, the photos, the
  partners — that the live editor rearranges tile by tile (light, sun or slate). The forum reads as cards, with its
  statistics and activity alongside; the dark footer carries the site map and the social networks. Light by day, "slate"
  night at the visitor's choice; Bricolage Grotesque headings, Manrope text. Settings: colors, home image, logo, and the
  address of the "Join" button (a membership page, a form on another platform, or the site's registration).
- **The "The site in figures" widget**: up to four numbers, chosen among the members, the discussions, the forum
  messages, the news, the upcoming events and the photos — counting only what the visitor is allowed to see (restricted
  forums and group albums stay out of the count). In Pulse the numbers count up when the tile appears, unless the
  visitor asked for less motion.
- **The calendar also shows "The next event"**: its date in large type, its title, in how many days, at what time (or
  "all day") and where, the start of its description, and a button to open it.
- **The gallery also shows "The latest photos"**, as a grid — from the albums the visitor can see only.

### Fixed

- **The "Colored title" style of Chronique and Granite**: the panel title had the color of its background (teal on
  teal, red on red), so it was invisible. It is now colored, on the paper, as these themes intended — and the style's
  preview in the live editor shows it that way.
- **Granite's "Colored widget" style**: the text, the form labels and the button of a widget in this style
  could turn white on the paper (or ink on ink, at night). They keep the newspaper's colors. The contrast check can now
  measure a widget in every theme and every panel style.
- **The breadcrumb above a news article**: every theme planned it, it never showed — the rule placing it targeted an
  address that does not exist. It now shows on articles and on the news list, in every theme.
- **Two side-by-side boxes stay aligned**: under a news article, "Other news from this author" sat 14 to 22 px lower than
  "About the author" (likewise on an event page and in the inbox).
- **Chronique: an article keeps its margins** inside its card; the title and the text touched the border.
- **Text on a gradient is readable**: the "Join the server" button of the Discord widget, the initials of Forge's
  crest, the title and motto of Blockcraft's banner by day. The contrast check now measures text on a gradient, and as
  a logged-in member (the member area included).
- **A Blog post with a single heading no longer appears squeezed**: without a table of contents, the page kept its
  column, the text was cramped into 200 px and the side box (author, sharing) took its place — the release notes of
  1.2.32, 1.2.33 and 1.2.37 on the official site. Thanks to Blober for reporting it.
- **`admin.php` and `ajax.php` answer "page not found"** instead of a server error (and two log lines every time a bot
  probes these addresses).
- **The season timeline**: "In 5 days" no longer breaks over two lines on a phone.

## [1.2.37] — 2026-10-06

Chronique reviewed on the demo as people see it — as a visitor and logged in, by day, by night, on a phone —, and a demo that lives in the present.

### Fixed

- **Chronique's member area.** In its panel, "Forgot password?" sat on top of "Log in" as soon as the
  column put them one under the other; once logged in, the menu icons stayed dark gray on the slate and "Log out" fell
  into a white rectangle (black at night). They are now white links, a single filled button, and a gap both ways — with
  "Create an account" too, when registrations are open.
- **Chronique on a phone keeps the login in the header**, as an icon: the word "Log in" disappeared, and you had to scroll
  to the bottom of the page to log in.
- **The login panel's buttons keep their gap when they wrap**, in the five themes that share the common base
  (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend: the field icons of the colored panel show** (username and password): they were white on a light box.
- **The members list, at night**: a social network a member had not filled in became a gray dot stuck to the next one. It
  is a grayed icon without background, and the buttons of the networks that are filled in have a real gap — on one line
  in a narrow card.
- **The demo lives in the present.** Reset every quarter of an hour, it kept showing the content from the day its
  snapshot was taken: "upcoming" events and matches that had passed weeks ago, an empty calendar, a season timeline
  that would have emptied itself. Each reset now moves the content's dates forward by the time elapsed (the gaps
  between them do not change); members keep theirs.

## [1.2.36] — 2026-10-06

A new theme: **Chronique**, the season’s notebook for associations and clubs, with two new features that serve every
theme — the season timeline and the calendar week.

### Added

- **The Chronique theme.** The site as the season’s notebook: a discreet header that stays at the top, with a thin line
  that follows your reading, a "Table of contents" button that opens the whole site full screen (each section
  numbered) and the "Join" call for visitors; the home page opens on a large headline — the season, the site name,
  its tagline — and the current week; then the timeline tells the season month by month, beside a column that stays
  in place (the member area,
  the documents, the partners). The forum reads as numbered chapters, the member area as a notebook, and the footer
  closes the book. "Paper" by day, "lamplight" by night; Fraunces headings, Work Sans text, IBM Plex Mono dates.
  Settings: colors, opening image, logo, and the address of the "Join" button (a membership page, a form on another
  platform, or the site’s registration).
- **The "Season timeline" widget**: what the site lives through, month by month, on a timeline — upcoming calendar
  events (their dot pulses), news, forum discussions and photo albums, each if its module is installed, and only what
  the visitor may read (restricted forums and group albums stay hidden). The number of entries and the past months can
  be set.
- **The calendar also shows "This week"**: the seven days of the current week, the ones with an event marked, today
  circled, and the next event — in the viewer’s time zone.

### Fixed

- **In day mode, Bootstrap components take the theme’s colors**: a FAQ accordion, a checkbox, a table or a dropdown
  stayed white, with dark gray text, on Granite’s paper. Checked element by element on Forge, Granite, Blockcraft and
  Extend: only their colors change, and night mode stays as it was.
- **"Day mode" and "Night mode" are translated**: the tooltip and the name, read by screen readers, of the toggle button
  stayed in French on a site in another language (Forge, Granite, Blockcraft, Extend). The hard-coded text check now
  knows the words "jour" and "nuit".
- **A theme’s customization page no longer logs a warning** when its settings do not exist yet (a theme registered
  without its installation having created its settings): an image position falls back to its default.

## [1.2.35] — 2026-10-06

The second theme rebuilt from the ground up: **Granite 2.0.0 "Gazette"**, the newspaper of associations and clubs.

### Changed

- **Granite 2.0.0 "Gazette": a new layout, not a reskin.** The site becomes the club’s newspaper: today’s date and the
  account on one line at the top, the site name printed very large (the logo above it, if there is one), a double rule,
  then the sections in small capitals between two rules. On the home page, an "In brief" line scrolls the latest forum
  topics; it stops on hover, with the keyboard and with its pause button, and stays still when the visitor asks for
  reduced motion. The front page opens on the main story in large type, with its ornate capital, then the next ones in
  columns separated by rules; the right-hand column carries the calendar, the poll and who is online. Blocks become
  boxless panels, titled in small capitals over a rule; an article reads like newspaper body text; the forum takes the
  look of a readers’ letters page; the member area, that of a membership card stamped with the member’s first group; the
  footer becomes an imprint (the partners, then who publishes). A thin reading bar follows the page. Day is paper, night
  is an "inked" page, at the visitor’s choice; Playfair Display headings, Source Serif text. On phones, the sections
  scroll sideways and everything goes into one column.
- **Granite’s zones are named after their place**: "Sections", "In brief", "Content", "After content", "Footer".
- **Granite’s settings**: the banner image becomes the title image, and the title color, once changed, sets a band
  behind the name, like a daily paper’s nameplate; the logo is finally used (above the name); a "Drop caps" setting
  replaces the fixed top bar setting, which no longer has a purpose.

### Fixed

- **No more Bootstrap blue among a theme’s colors.** A poll’s bar, the checked box and the selected radio button (even in
  the administration), the active page, a menu’s pressed entry, an accordion’s arrow, the keyboard focus glow and the
  states of a primary button kept Bootstrap’s default blue. They take the theme’s accent color, in every theme; a new
  check, `check-bleu-bootstrap`, looks for that blue in the served pages, at rest and on focus, by day and by night.
- **The sections set by Forge and Granite are translated**: "Matchs", "Nous rejoindre", "À la une", "Agenda" and
  "Documents" stayed in French on a site in another language. The translation check now verifies every menu title a
  theme sets.
- **The live editor shows the site with its own fonts**: it imposed its own on the content of the zones it frames.
- **The contrast check also measures text set on a grain** (paper, a screen pattern): it took it for an image and gave
  up measuring it.

## [1.2.34] — 2026-10-06

The first theme rebuilt from the ground up: **Forge 2.0.0 "Coulée"**, for competitive clans.

### Changed

- **Forge 2.0.0 "Coulée": a new layout, not a reskin.** Navigation leaves the top of the page for a steel rail on the
  side, with the site logo at the top and the account at the bottom (avatar, notifications, day mode, member area menu);
  on phones, the logo and the account move to a top bar and the navigation to a tab bar at the bottom of the screen, and
  beyond five entries the last ones go into "More". The home page opens on a lava hearth where embers rise: the slider,
  its title cast in metal, and the latest results plate. Below, a dashboard: the news (the first one featured) and,
  beside it, the upcoming matches, the awards and who is online. Blocks are cut-corner plates whose edge glows on hover;
  the forum puts each forum in a steel drawer with a heat gauge for its activity; the member area and the public profile
  get the identity plate and beveled tabs; the footer is riveted and carries the partners. Day mode lightens the
  content, while the rail and the hearth stay steel and fire. All motion stops when the visitor asks for reduced motion.
  The matches, awards and partners blocks are only placed when their modules are installed.
- **Forge’s zones are named after their place**: "Navigation rail", "Top of page", "Content", "After content",
  "Footer".
- **Forge’s settings**: the logo is finally used (the rail’s emblem; without a logo, the site’s initials); the banner
  image becomes the hearth image; an "Embers in the hearth" setting (none, gentle, lively) replaces the fixed top bar
  setting, which no longer has a purpose.
- **The awards** widget shows the podium place ("1st", "2nd", "3rd") and the competition name across the full available
  width, with the location and platform below; the line used to start with the platform and cut the name at twenty
  characters.
- **The demo’s slider images no longer carry their title**: the slider already writes it on top, and it showed twice,
  across the caption on phones.

### Fixed

- **A theme setting shows immediately** (accent color, background and banner images…): the stylesheet address only followed the
  file date, and the browser kept the old stylesheet after a change.
- **The customization page of the Forge, Granite, Blockcraft and Extend themes** is titled with the theme's name,
  instead of an untranslated "Dashboard" shown on every tab.
- **In English, the awards no longer say "2th" or "3th"**: podium places have their own words, other places read "#4".
- **Reinstalling a theme no longer writes a warning to the log** in debug mode (a PHP 8.2 "Deprecated" warning).

## [1.2.33] — 2026-10-06

A fix release: the public CI of the `neofrag` repository, broken at 1.2.32 by a tooling issue, is repaired. Nothing
changes for sites.

### Fixed

- **The automatic checks (CI) of the `neofrag` repository pass again.** Since 1.2.32, the step that looks for the
  matching version of the `extensions` repository could fail: it stopped reading the list of versions as soon as it
  found the right one, and `git`, cut off mid-write, failed now that the list is long. It now reads the whole list; same
  fix in the `extensions` CI.
- **The documents' link check no longer reads those of bundled libraries** (the TinyMCE license): they cannot be fixed
  on our side, and gnu.org was closing the connection to their old address.

## [1.2.32] — 2026-10-06

The themes overhaul begins: Forge, Granite, Blockcraft and Extend now share a common base. Nothing changes on
screen; it is the foundation on which each will receive its own identity.

### Changed

- **The Forge, Granite, Blockcraft and Extend themes share a common base** (`css/nf-socle-themes.css`): 153 of the
  rules they had in common are now written only once, in the core; some fifty others stay in each theme, because
  their position in the stylesheet matters. Nothing changes on screen — checked element by element and pixel by
  pixel, on the four themes, by day and by night, on desktop and on phone. It is the first step of their overhaul:
  each will soon have its own look. The four themes move to 1.1.0 and require core 1.2.32.

## [1.2.31] — 2026-10-06

The member area, final steps: account security — signed-in devices can be logged out, avatar and signature
sanctions finally apply — and notifications, moved into the member area with each member choosing what they
receive. The member area overhaul is complete.

### Added

- **"Security" shows the devices where the account is signed in, and lets you log them out**: each session with its
  browser, system, IP address and last activity, "This device" for the one you are looking from; "Log out" for
  another one, or "Log out all other devices". Until now, "Security" only showed the sign-in history, and a session
  open elsewhere could not be closed. On a demo site, whose account is shared, no session can be closed.
- **Each member chooses the notifications they receive**, in "Notification preferences": one box per kind of
  notification (a private message, a reply in a followed topic, a mention, a comment, a reaction, an event
  reminder…) to receive it on the site, and a "By email" box for those the site also sends by email (private
  messages, forum replies and mentions). Without any setting, they receive everything, as before. For module
  authors: a module declares its own through `types_de_notification()` (see the module creation guide).
- **"My notifications" joins the member area**, under the "Notifications" entry of its menu, twenty per page: the
  page lived apart, at `/notifications`, and only showed the last fifty (now up to the last five hundred). The old
  address leads to the new one.

### Fixed

- **Avatar and signature sanctions now apply.** A moderator could issue them, but the profile never checked them:
  a sanctioned member changed their avatar and signature as before. They now see what the sanction forbids, its
  reason and until when.
- **Eight core data models always read their own table** (sessions, sign-in history, addons, translations, logs…):
  loaded from a module, they looked for a table that does not exist. Nothing showed it on screen yet; the new device
  list would have. It is the same flaw as the one behind the favicon and deleted files, fixed in 1.2.29.
- **A muted forum mention no longer swallows the reply.** A member mentioned in a topic they follow received nothing
  when the mention was off for them — the mention email turned off by the site ("Notify @user mentions by email"), or
  mentions turned off in their preferences. They now get the reply. When the mention does reach them, it stands in
  for the reply, so they are not notified twice.

## [1.2.30] — 2026-10-05

The member area, second step: the public profile, and what everyone chooses to show on it. And a fix that
matters: a logged-in member can read and write in the forum again.

### Added

- **A member's public profile becomes a real page.** Their cover as a banner (it could be uploaded but showed
  nowhere), their avatar overlapping it, their username, rank, groups and presence; for a signed-in visitor,
  "Contact" (if private messaging is installed) and the "Report this content" flag, or "Edit my profile" on your
  own; then tabs, each at its own address: About (quote, identity, links, the site's public fields, and their
  stats), Activity, and the ones installed modules bring — Forum, Blog, Teams, Classifieds. A module with nothing to
  show about this member adds no empty tab.
- **Each member chooses what their profile shows**, in "Privacy and data": their points, karma and VIP days are
  kept to themselves until they show them (they were visible to everyone, visitors included); their age and
  online presence stay shown until they hide them. Their rank stays public. The choice applies everywhere: the
  card that opens when hovering a username, the avatar's dot, team rosters, the "Who is online?" and "Forum
  activity" widgets, the member search, the clock's birthdays. On their own profile, a member sees everything,
  with a padlock on what only they can see.

### Fixed

- **A logged-in member can read and write in the forum again**, and sees the galleries, pages, event types and
  folders open to visitors. Since the permissions overhaul, the "Member" role only had what it was explicitly
  given: a member saw "No forum" where a visitor could read everything, and could write nowhere — only
  administrators, who bypass permissions, saw nothing wrong. The original rules are back: what a visitor can do,
  a member can do; what is refused to visitors only stays allowed to members. A migration restores them on every
  site, without touching a rule already set for members.
- **Reputation ranks (Novice, Bronze, Silver…) are translated**: they showed in French in every language. And a
  rank's tooltip no longer gives away the karma score.
- **On a demo site, the "Forum activity" widget no longer shows the administrator account created at
  installation** among online members; "Who is online?" already left it out.
- **A website entered without "https://" in a profile now leads to that website**: it became a link to a page of the
  site itself.
- **An update no longer overwrites the catalog of the site that serves the marketplace.** The update package
  carried an old catalog, built at 1.2.22: placed on that site, it no longer matched the archives, and every addon
  installation was refused until the catalog was rebuilt. An ordinary site reads the online catalog and was not
  affected.
- **In "My space", the member's group lines up on the left under their username**: it was centered in the middle of
  the header.

## [1.2.29] — 2026-10-05

The member area, first step of its overhaul: a single frame, a single menu, settings where you would expect them.
And the marketplace no longer offers a site an addon made for a newer version of NeoFrag.

### Added

- **A new password must be at least 10 characters long.** It is refused if it repeats the username, uses only one
  or two different characters ("aaaaaaaaaa", "ababababab") or is one of the most common passwords ("qwertyuiop",
  "1234567890"…). The rule applies at sign-up, when changing it and at reset (at reset, the username is not
  compared). Existing passwords stay valid.

### Changed

- **The member area has a single frame and a single menu.** It changed shape from one page to the next — a
  menu on the left under the profile card, a folded "Menu" bar at the top, nothing at all — and one page had
  three names. The account pages now all have the same menu, in the same place: a column on the left on a
  computer, a scrolling strip of tabs on a phone, the current page highlighted. The menu also leads to the
  modules' pages: messages and notifications with their unread counts, forum subscriptions (which no link
  reached), moderation. The top bar of the Blockcraft, Extend, Forge and Granite themes and the "Member area"
  widget use this menu, with the same words.
- **Settings are where you would expect them.** "My account" gathers the username, email address, password,
  language (which could only be chosen with the site's language switcher) and time zone (which sat in the
  middle of the public profile); "Security" keeps two-factor authentication and the login history; a "Privacy
  and data" page holds the export of your data and the deletion of your account, filed until now under
  "Security (2FA)".
- **"My space" opens on a compact header** — avatar, username, groups, "View my profile" and "Edit my profile"
  — instead of the large profile card, which pushed the menu a whole screen down on a phone.

### Fixed

- **The marketplace no longer offers a site an addon made for a newer version of NeoFrag.** Only updating an
  addon checked the NeoFrag version it requires, not installing it: a site that had fallen behind could install
  an addon calling functions its version did not have, and break. The official marketplace now sends each site
  the catalog made for its version (a site older than all the catalogs kept gets the oldest one). The marketplace
  window also leaves out an addon that is too recent and says to update the site first. The "Updates" button
  announces a new NeoFrag version from the Monitoring update check, no longer only from the catalog.
- **The favicon chosen by the administrator is also the site's icon on a phone, and a deleted file really leaves
  the disk.** An internal error kept the site from finding these files: the favicon was missing when adding the
  site to a phone's home screen (with a warning in the log each time), and the file of an attachment deleted from
  the forum or private messages stayed on the disk and in the database, as did the avatar and cover of a member who
  erases their account.

## [1.2.28] — 2026-10-05

A bug-fix release: the member area of an account created with Discord, GitHub or Google, the export and
deletion of personal data, and the demo site.

### Fixed

- **A member who signed up with Discord, GitHub or Google is no longer locked into their account.** Without
  a password, they could neither change their username or email address, nor create a password, nor turn
  off two-factor authentication, nor delete their account: every form asked for the current password. They
  now confirm their identity by going through the linked service again; the confirmation lasts ten minutes,
  and logging in through that service counts as one.
- **The "My data" export works, and it contains more.** It failed with an error: the member downloaded an
  error page instead of their data. The archive now also contains the profile (name, date of birth,
  location, signature, links), linked accounts, login history, notifications and what the site's modules keep
  under their name, in plain text and without any secret, not even their session IDs. Forum topics, comments
  and discussion messages only made it in with 1.2.43.
- **Deleting your account erases what the page promised.** The username is anonymized on posts and the
  profile emptied; linked accounts, login history and notifications are erased, and a linked Discord,
  GitHub or Google account becomes free again for a new sign-up. The "Your account has been deleted" message
  is finally shown.
- **On a demo site, the shared account can no longer be changed**: a visitor could change its password,
  turn on its two-factor authentication or delete it, locking everyone else out until the reset.
- **"Connexion" is translated in the sense of "log in"** (Log in, Anmelden, Iniciar sesión, Accedi,
  Entrar): the five other languages said "network connection". The Discord administration button that
  links the bot becomes "Connect the bot".

## [1.2.27] — 2026-10-05

The Discord bot moves to **version 0.2.4**: images travel between the forum and Discord in both directions
(see its own changelog). Version 0.2.3 keeps working.

### Added

- **An image sent on Discord shows up in the forum** (with Discord bot 0.2.4): the bot keeps it on the
  site, checked like an image pasted in the editor, instead of a mere link to Discord. The API gains the
  `POST /api/v1/forum/images` endpoint for this.
- **Email validation of sign-ups** (*Settings → Registration management*, "Email validation" box, off by
  default): the new member receives a link, valid for two days. They cannot log in before opening it; if they try,
  a new link is sent. This feature, inherited from NeoFrag, was left unfinished and had no setting in the
  administration.

### Fixed

- **Accented text no longer shows up encoded.** A title, a username, a label or the site name containing
  "é" or "—" could appear as `&eacute;` or `&mdash;`: in conversations, the Shop, Donations, a post's
  table of contents, RSS feeds, the administration… These texts are now all displayed the same way, and two
  automatic checks keep the mistake from coming back (one reads the code, the other the site's pages). Mention
  and search suggestions, email subjects, the data read by search engines (site name, a post's title and
  author) and webhooks also receive plain text.
- **An avatar's initial** showed "&" for a name starting with an accented letter (administration, a
  donation campaign page).
- **Signing up with Discord, GitHub or Google requires accepting the rules**, when the site has some: a
  screen shows them, and the account is only created once the box is ticked — as with the sign-up
  form, which these accounts bypassed.
- **The menu no longer leads to a missing module**: the link of a module that is not installed or is turned off
  (Forum, Gallery, News…) stayed in the menu — one click, a page not found. It disappears, and comes back when the
  module is turned on again. A link to a published custom page stays; one to an unpublished page disappears too.
- **On a site without the Events module, the Teams page** no longer writes a warning to the error log.
- **The ☰ button of the administration works on large screens**: it was shown there without doing
  anything; it now folds the side menu, and opens it again, remembering the choice. On a phone, it opens
  the menu as before.
- **The avatar of a forum post's author is larger**: 80 px (36 px on a phone), instead of the 40 px every
  theme gave it.
- **An image in a signature, or in a gallery album's description, is displayed**: the page showed the HTML
  code (`<p><img …></p>`) instead of the image. Signatures and descriptions already saved are displayed without
  being entered again.
- **The plain-text version of emails is readable**: paragraphs were glued together, accents encoded, and
  a link ended up followed by the next word — unusable in a mail client that only shows text.
- **A tampered form no longer crashes the page**: a checkbox sent in a form no browser ever sends is ignored, like
  an unticked box.
- **On a demo site, the administrator account created at installation stays hidden on hover and in the moderation
  history**, as on its page: the member card shown on hover gave its name, groups and visit dates, and the
  moderation history its name.

## [1.2.26] — 2026-10-04

The Discord bot moves to **version 0.2.3**: it no longer writes a discord.js warning on every private
reply (see its own changelog). Version 0.2.2 keeps working.

### Added

- **The rules and the welcome message are translated language by language**: *Settings → Registration management*
  has one tab per site language, and every visitor reads the rules — and receives the message — in the
  language of the page. A language that does not have its own text yet shows the shared text: a site that
  had only one keeps it for all its languages, nothing changes until it is translated.
- **The changelog exists in English** (`CHANGELOG.en.md`; the French version remains the reference).
- **Every addon now has its thumbnail, in the same format (960 × 600)**: the API and Discord modules had none, the
  four catalog themes kept an older, smaller picture, and three core widgets had none in *Themes & addons*. A few
  still show an empty or unconfigured addon. A new addon can no longer be published without its own.

### Changed

- **The catalog descriptions say what each addon does**: thirty of them fit in a few words or only
  presented themselves as "gaming". They now describe what the addon really does, and who it is for, in all
  six languages; five were even wrong (the Teams widget does not show members, the Downloads widget shows the
  most downloaded files…).
- **The Events module is called "Events"**, no longer "Gaming events": no catalog title carries the word "gaming"
  anymore.
- **The Payments module requires the Gamification module**, which credits the purchased points and VIP
  days: without it, a payment was collected and nothing was credited. When installing the site, choosing Payments
  adds Gamification; from the marketplace, it must be installed first. If it is missing or disabled later, sales
  close and the administration says so; a payment received is not marked as processed, and Stripe will send it
  again.

### Fixed

- **An image pasted (Ctrl+V) or dropped into the text editor is saved** — forum reply, comment,
  administration page, staff room of private messages: it showed up broken, then disappeared on publishing. It
  is now sent to the site and stays in the message. This is reserved for signed-in members: a JPEG, PNG, GIF or
  WebP image of 5 MB at most. The site saves a fresh copy, which strips the hidden information of a photo (where it
  was taken, for instance). Beyond 2,000 pixels on a side, the image is scaled down; beyond 8,192 pixels on a side
  or about 12.6 million pixels, it is refused. An animated GIF becomes a still image. On the demo site, image
  uploads stay closed.
- **The welcome message displays cleanly**: written in the rich editor, it reached the messages with its
  tags showing (`<h3>`, `<p>`…). It is now turned into text (headings, bulleted or numbered lists, links
  made clickable), and its title no longer shows `&eacute;` instead of an accented letter.
- **The welcome message is also sent when someone signs up with Discord, GitHub or Google**: only the
  sign-up form sent it.
- **The two-factor authentication QR code is displayed**: the activation screen (*Account security →
  Enable 2FA*) showed the image code in a text box instead of the image to scan — forms did not know this
  kind of field and treated it as text.
- **The Partners widget logos lead to the partner's site** and count the visit: they led to a missing page
  since 1.0.0, and the "Visits" counter no longer moved. The Partners page also goes through the counted
  visit.
- **The "Pay" (Payments) and "Buy" (Shop) buttons work**: they called a missing address.
- **Subscribing to the newsletter from the widget works**: the address typed in was lost on the way. The
  confirmation link in the email was relative (useless in an email client), like the unsubscribe link of
  campaigns: both are now full addresses. And if the email cannot be sent, the subscription is canceled
  and the visitor is told, instead of reading "sent".
- **The links in forum and messaging emails** (mention, subscription, conversation) are full addresses:
  relative, they led nowhere.
- **Polls follow the "Show results" setting** (after voting, when closed, never) — on their page as in
  their widget, which always showed them. Managers always see them, with a note.
- **Widgets follow their module's rules**: the Galleries widget no longer shows draft, scheduled, trashed or
  group-restricted albums; the Forum widget, no excerpt from VIP-only categories nor empty lines for
  deleted messages; the Events widget and calendar, no scheduled event before its time; the Recruitment
  widget follows "Hide unavailable offers".
- **A link to a page of the site, inserted in the editor, keeps its full address**: the editor shortened it into
  a path (`../../…`) that only worked from the editing page — shown anywhere else, it led nowhere.
- **The roadmap** (`ROADMAP.md`) says the code has been published since October 4, 2026.

### Security

- **Two old technical messaging addresses displayed messages without protecting them**: a message containing code
  could have run in the browser there. No page used them anymore, and access to the conversation was already
  required; they now display messages like the conversation itself.
- **The Facebook and Twitter buttons of the Partners page no longer follow a `javascript:` link** entered in the
  administration: such addresses are filtered and escaped, as the partner's website already was.
- **The Shop's "Buy" button and the "Pay" button now check that the request really comes from the site**: a
  malicious page can no longer make a signed-in member buy an item without knowing it.
- **A purchase can no longer be counted twice**: the point debit, the stock and ownership are checked and
  written in a single transaction — two simultaneous purchases no longer sell the last item twice, nor
  debit a balance twice. Points earned and VIP days are also added in one go: two gains at the same
  moment no longer overwrite each other. An item worth 0 points can now be obtained without a debit (it was
  refused, "not enough points").
- **Newsletter sign-ups are rate-limited**: 5 requests per hour per IP address, 3 per day per email address —
  enough to stop confirmation emails being sent in bulk to chosen addresses.
- **A poll whose results are hidden no longer shows its total vote count** in the list.

## [1.2.25] — 2026-10-04

The Discord bot moves to **version 0.2.2**: the forum messages it relays lose all their HTML
tags, even nested ones (see its own changelog). Version 0.2.1 keeps working.

### Fixed

- **The marketplace window also offers the widgets that no module provides** (About, Game server, Feed
  reader, Seasonal effect, Steam group, TeamSpeak 3 server, Live status): it only showed
  modules and themes, and these seven widgets could only be installed through "Add", with the archive
  in hand. A widget already provided by a module (the Forum's, for example) does not appear there twice.
- **The administration guide names the button that saves a new site address** by its label,
  *Use*, followed by the address — and no longer by a fake `https://…` address that a reader mistook
  for a link.

### Security

- **The repository's verification workflows only receive a read-only token** (`permissions: contents:
  read`): they have nothing to write, so a compromised step could not change anything. Flagged by
  GitHub's code analysis when the repositories were opened to the public.
- **The Video widget only accepts, for an item in its playlist, an `http(s)` address or a
  site path**: a `javascript:` or `data:` address is ignored on click. The player did not execute
  anything, but such an address had no business being there. Flagged by the same analysis.

### Added

- **Three more tests in the `installation.yml` workflow**: `check-extensions` installs each of the
  addons in the published marketplace on a site that has only the core, through the marketplace window, and
  through "Add" whatever it does not offer; `check-prerequis-absents` removes each required PHP extension in turn,
  and checks that the wizard and the command-line installer say which one is missing; the published package is
  installed through the wizard on a simulated shared host (Apache without functions that launch a program,
  `open_basedir`, 128 MB). And the links in all documents are checked (lychee).

## [1.2.24] — 2026-10-04

**NeoFrag Reborn opens up on GitHub.** The CMS code ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)), the à la carte
addons ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) and the Discord bot
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) are published there with the history of
their versions — since 1.0.0 for the CMS and the addons, since 0.1.0 for the bot: the code of each version, its
notes, and the packages from 1.2.23 onward.

### Changed

- **The databases listed are the ones that are tested**: each version installs and passes
  its whole test suite, with PHP 8.2 and 8.5, on MySQL 5.7, 8.0 and 8.4 and on MariaDB 10.5, 10.6,
  10.11, 11.4 and 11.8 — the installation guide lists those versions, and no longer "5.7+" and "10.5+".
- **The guides say where to find the project**: the installation package and the bot archive on their
  releases page, the à la carte addons and their catalog on the `extensions` one, the code for anyone who wants to
  contribute. In the wiki, references to a repository document (deployment, architecture, tools)
  lead to that document on GitHub, instead of keeping only its title.

### Fixed

- **Module settings open again**: the "Configuration" window of the Forum,
  Recruitment, News, Gallery, Events, Calendar and Articles modules returned
  "error 500" every time it was opened, on every site, since 1.2.0: the "number" field rejected an
  integer (its step, and the value read back from the database) since the code switched to strict typing.
- **Teams: the "Show matches played" option can be unchecked again.** Saving the
  settings stored the word "Array" instead of a yes or a no, which was always read as "yes".
- **The settings of an authenticator that was never configured** (Google, Discord, GitHub) open without writing
  eight warnings to the log.
- **The dashboard and statistics of a site without Articles, Bugtracker or Forum** no longer write
  warnings to the log every time they are opened: the cards of these modules only appear if they are
  installed, and no missing table is queried anymore.
- **The installation wizard rereads the configuration it has just written**: on a host whose
  PHP cache does not recheck files, a second attempt at the "Database" step still read
  the old `config/db.php`.
- **A site deployed from git and installed through the web wizard works**: the wizard did not write
  `config/neofrag.php` (the package carries it, a clone only has its template), and every page returned
  "error 500" without a single line in the log.
- **The example `nginx.conf` starts**: the TinyMCE rule, without quotes, made nginx reject the whole
  configuration ("missing closing parenthesis"). Its redirects no longer carry the internal parameter
  `request_url`.
- **Uploaded files are served under Apache with PHP as a module**: the guard on `upload/` used a
  directive that is forbidden in an `.htaccess`, and that whole folder — avatars, gallery, media library — returned
  "error 500".
- **Under Caddy, uploaded files that are not images** (a PDF from the media library) are served:
  the example only served images.
- **`/index.php` leads to the home page** (permanent redirect) instead of a not-found page; and an
  address with an extension that no page serves (`/newsletter/.env`, which bots try) returns 404 without
  writing an anomaly to the log.
- **`tools/check-all.php` under PHP 8.2**: every check counted there as a failure, even when it passed — the exit
  code was read one time too many, which PHP only allows from 8.3 onward. For anyone contributing
  under PHP 8.2.

### Security

- **The installation wizard no longer reopens on an installed site whose database is not responding.** If the
  `install/db.txt` lock had disappeared, a temporary database outage reopened the wizard, whose
  "Database" step rewrites `config/db.php`: a visitor could then redirect the site to their own
  database. The site now displays "temporarily unavailable". And the "Administrator" step refuses to
  create an account as soon as the site has one.
- **Apache now only executes the root `index.php`**: the rewrite rule let any `index.php` file in a
  subfolder run directly (more than eighty internal controllers).

### Added

- **Four more checks**: `check-assistant` runs the installation wizard end to end, like
  a visitor, for each profile (screens, refusals, account created, login with the password entered,
  silent log); `check-reglages` opens the settings screen of each addon, saves it as is and
  reopens it; `check-serveur-web` tests Apache, nginx and Caddy, with the shipped configurations, by placing
  probes (forbidden folders and files, scripts that must not run, rewriting, security
  headers); `check-nouveau-venu` follows the README and the contributor guide verbatim on a
  clean machine. The `installation.yml` and `nouveau-venu.yml` workflows rerun them every week.
- **The README's commands work on a fresh machine**: its "Develop" block ended on a test suite that
  failed for lack of a test database; it now points to the contributor guide, which creates that database
  (`php tools/prepare-test-db.php`).

## [1.2.23] — 2026-10-04

The Discord bot moves to **version 0.2.1**: nothing changes in how it works — it becomes a project
in its own right, with its license, its NOTICE and its own changelog. Installing it is only useful
to those who want these files; 0.2.0 keeps working.

### Changed

- **The sitemap no longer lists an empty section**: news, recruitment, an FAQ,
  teams… with nothing to show are no longer offered to search engines, which would only find
  a "nothing yet" page there. The section comes back by itself with its first content.
  Contact, the guestbook, the newsletter, the web radio and donations remain listed: they always have
  something to offer.
- **Backups no longer pile up**: each update takes a full one (16 MB and more), and nothing
  removed them. The site always keeps the five most recent ones, and removes the others after
  thirty days; a file placed by hand in `backups/` is never touched.
- **The installation checks everything the CMS needs.** The wizard checked neither `openssl` (without
  it, saving a secret — email server, captcha, two-factor authentication — fails with an error), nor
  `fileinfo` (without it, every file upload is refused), nor `iconv` (the QR code for two-factor
  authentication), nor Argon2 password hashing, without which creating the
  administrator account failed. It now checks them and refuses to continue if one is missing;
  `install/cli.php` requires exactly the same list, PHP version included, instead of two extensions.
  *Monitoring* and the administration dashboard show the same list. PHP 8.2 or later is required;
  the product is tested up to PHP 8.5.
- **nginx and Caddy**: the package finally ships the configuration examples the guides promised
  (`nginx.conf`, `Caddyfile`), generic, and which deny the same sensitive folders and files as
  Apache's `.htaccess` — the old nginx example left `install/`, `tools/`, `tests/` and `docs/`
  reachable, and the excerpt in the deployment guide only protected three folders out of eight.
- **Each addon says who wrote it.** The addons from the original NeoFrag keep their authors, Michaël
  BILCOT and Jérémy VALENTIN — the login connectors and the languages, which signed nothing, now
  name them, and the original English translations credit their translators again, FoxLey and eResnova.
  Ports credit their author: the Clock (ArkaNiX), the Extend theme (Chewbaka), the file
  manager (HiddenBlob, HiddenCMS) and Donations (HiddenBlob, after majiid). The rest is signed
  "NeoFrag Reborn". The Nebula, Blockcraft, Forge and Granite themes move to the LGPL, like the
  product; Extend keeps its author's license (CC BY-NC-SA). Each LGPL addon's license points to the
  official LGPL text.
- **HSTS**: the header set by the `.htaccess` no longer extends to subdomains and no longer declares the
  site eligible for the browsers' preload list (`includeSubDomains` and `preload` removed): set
  by default, they bound all the subdomains of whoever installed the CMS for a year.

### Fixed

- **A site installed from the package recorded no errors.** The package did not contain the
  `logs/` folder, and nothing created it: PHP wrote its errors to the server log, *Monitoring
  → Error log* stayed empty and called the folder "not writable". The package now ships
  `logs/`, `cache/` and `backups/` with their protection, and a site that has no `logs/` recreates it
  by itself: updating is enough to repair a site that is already installed.
- **The wiki documentation no longer has dead links.** Three references from its pages to documents
  the site does not have (the tools manual, part of the deployment guide) led to an error
  page; they now keep only their text. The marketplace guide no longer mentions "third-party" addons
  that do not exist, nor procedures reserved for the project's server.
- **Settings → Search engines** refused to save as soon as a description, a tagline or a
  title for search engines contained many accented letters: each one counted as several
  characters, and a 139-character Portuguese description exceeded the 160 limit. A Google or Bing
  verification code pasted as a whole tag was refused in the same way. Redirects
  and per-content SEO follow the same rule.
- **The SEO report** recognizes a Google Search Console property verified through the domain's DNS
  (instead of calling it "not declared"), and advises submitting the sitemap that gathers all languages,
  `/sitemap.xml`, rather than the one for the displayed language only. When Google is verified, it no
  longer calls Bing "not declared": Bing Webmaster Tools can import a site from Google Search Console
  without placing anything on the site, so the report cannot see it.
- **`humans.txt`, `robots.txt` and the IndexNow key**: a missing address — an empty `humans.txt`, an
  unknown key — returns a plain "not found", without writing an error to the site log for every
  bot that requests it.

---

## [1.2.22] — 2026-10-03

### Added

- **Per-content SEO**: a "Search engines" button in the edit card of a
  news item, a Blog post, a page or a wiki page gives, for each language, the title and
  the description shown by search engines and share previews. Left empty, they stay automatic.
- **Settings → Search engines → SEO report**: what a search engine sees, measured on the site — the
  sitemap pages per module, the texts of each language, the share image, Google and Bing, `robots.txt`,
  maintenance —, with, for each item, the link to what fixes it.
- **Redirects**: an old address leads to the new one (301) instead of answering "Page
  not found", with the number of visits it still receives. A renamed page or wiki page
  leaves its own behind by itself; you can add more by hand, for the address of a former site for example.
- **Notify search engines (IndexNow)**: when turned on in Settings → Search engines, the site reports, within
  minutes, every page that appears, changes or disappears, to Bing, Yandex, Seznam, Naver, Yep and
  Amazon. Google does not take part: for Google, the sitemap remains the way. The pages of every module
  listed in the sitemap are reported, with nothing to add; the site's scheduled task is required.

### Fixed

- **Moderation**: in the administration, a moderator without the "private conversations" right could
  open the report of a private message, and this access was not recorded in the audit log. Whoever
  reports a private message now finally sees the warning meant for them.
- **Messages**: the message sent to the staff room from the administration always answered
  "No staff room configured".
- No more PHP warnings in the log when the report, message or member concerned had been deleted.

---

## [1.2.21] — 2026-10-03

### Added

- **Settings → Search engines**: for each language, the tagline of the home page title and the
  description shown by search engines; a share image (1200 × 630) for previews on Discord,
  X or Facebook; the verification codes for Google Search Console and Bing Webmaster Tools.
- **One sitemap per language**, gathered at the root (`/sitemap.xml`): each module lists its own
  public pages there — the blog, the forum and its topics, the wiki, the gallery, the calendar, the
  events… —, only those a visitor can read and that exist in the sitemap's language.
- On the home page, **structured data** describing the site, its logo and its social networks.

### Changed

- **Member profiles and the member directory** are no longer offered to search engines, nor are
  search results: little content, and a member should not end up in Google without
  having chosen to.

### Fixed

- **The sitemap** only contained relative addresses — ignored by search engines —, in a single
  language, without the wiki or the forum; `robots.txt` announced it with a relative address.
- **Each Blog page** declared `/articles/…`, a redirect, as its canonical address;
  the home page, `/index`. The links between languages are complete, with the default version (`x-default`).
- **The home page title** no longer repeats the site name ("NeoFrag Reborn | NeoFrag Reborn").
- **Google Analytics**: the setting only accepted the old `UA-…` format, which Google discontinued in
  2023; it accepts the current IDs, `G-…`.
- **Browser language**: a visitor whose browser only announces `de-DE` lands in German,
  and not in the site's default language.
- **The language selector** and the "This content does not exist in …" banner, from the Blog, led to
  its old address.

### Security

- **The full addresses** in the page head (canonical, languages, sharing) are built from
  the site address, and no longer from the request's `Host` header, which anyone can forge.

## [1.2.20] — 2026-10-03

### Added

- **A "Nonprofit / club" installation profile**: news, forum, galleries, calendar, donations,
  newsletter, wiki and FAQ, without the esports gear. It joins *Complete*, *Gaming / eSports*,
  *Community* and *Core only*, in the wizard's six languages.

### Fixed

- **The member export in JSON format** (GDPR, article 15) now writes its "Export of member
  data" label: it came out empty.
- **Blog**: on an author's page, "View profile" led to a not-found page.

### Security

- **The demo snapshot no longer carries any secret.** The file that resets the demo,
  shipped with the demo package, would have copied the email sending login, the key of the
  original NeoFrag's automatic translation service and the keys of the Twitch and TeamSpeak widgets
  from a site where they had been entered; it still carried the line, empty, for the captcha secret
  key. No key had leaked. A check now verifies the list against the code, and the shipped file itself.

### Documentation

- NeoFrag Reborn presents itself as **the open source CMS for communities, from gaming to nonprofits**; the
  concepts guide says which profile to choose for the site of a nonprofit or a club.

## [1.2.19] — 2026-10-02

### Added

- **A modern captcha, active from installation: ALTCHA.** It is hosted by the site itself —
  no account, no key, no cookie, no third-party service. The visitor sees a box that checks itself
  while they fill in the form: their browser performs a small calculation, which the server verifies. It
  protects the contact form, registration and recruitment. On update, a site without reCAPTCHA
  keys switches to ALTCHA; a site that had some keeps reCAPTCHA. ALTCHA makes mass submission costly
  for a bot; against a determined attacker, the providers below, which add their own checks, are sturdier.
- **As an alternative, Cloudflare Turnstile, hCaptcha or Google reCAPTCHA v2**, in
  *Settings → Anti-bot security*, with the link to each one's console. The secret key is encrypted and
  never displayed again. Without its two keys, a provider is replaced by ALTCHA rather than leaving the
  form open.
- The captcha and its messages exist in all six languages.

### Fixed

- **reCAPTCHA**: the secret key was sent in the verification URL; it is now sent in the
  request body, as Google requires. The outdated link to its console is replaced, and
  the visitor address that is passed on follows the rule used by the rest of the site, which takes into account a proxy
  declared as trusted.
- When the anti-bot check has not been completed, the message is written out under the captcha,
  instead of a lone icon whose tooltip does not appear on a touch screen.

### Security

- **Passing the captcha once no longer exempts you from passing it again.** Ever since the original
  NeoFrag, a passed captcha exempted the user from passing it again for all subsequent submissions of the
  same form, until the end of the session: a bot that solved a single challenge could then submit without
  limit. The exemption now only applies to a single resubmission, after another error in the form.
- An ALTCHA solution can only be used once: presenting it again is refused.
- The pages' security policy no longer allows Google by default: only the addresses of the
  chosen captcha provider, and none with ALTCHA.

## [1.2.18] — 2026-10-02

### Fixed

- **Drag-and-drop reordering works again**: the forum's categories and forums, the
  teams and their roles, partners, member groups, languages and login
  connectors, and the rows, columns and widgets of the live editor. The position arrived from the
  browser as text, and the move stopped on an error — spotted in the log of the
  demo.
- **On the demo, the live editor** opens a widget's settings again, and any
  change there receives the message "Action disabled on the demo site." instead of a
  silent error. Layouts cannot be changed there: an HTML widget written by a visitor
  would be displayed to everyone else.

### Documentation

- **The FTP installation guide** names the right package (`neofrag-reborn-public-<version>.zip`),
  describes updating with the button, and no longer asks you to run a tool missing from the package: the
  migrations apply on their own on the first visit, demo included.
- **The developer guides** describe how to put an entered address in a link only once it has been
  checked (`nf_url_sure()`), the demo site lock, lists split into pages, counters and dates; the figures
  in the README and the guides follow the code (62 modules, 40 widgets).

## [1.2.17] — 2026-10-02

### Security

- **On the demo, the Monitoring Files tab** still showed the real tree of
  the installation (folder and file names, without their contents): it now shows a sample
  tree, like the file manager.

## [1.2.16] — 2026-10-02

### Security

- **A malicious link no longer runs from the address in a report.** The reporting member supplies
  this address, which is then shown to the moderator: a "javascript:" address ran for them on click. The same
  guard applies to the links of an RSS feed (widget), a slideshow, the link directory, the partners and
  any address the site builds: only web, email, phone or site-internal addresses
  get through.
- **Deleting a role, creating the Discord bot key, closing a classified ad or locking a forum
  topic** were done through a simple link: a malicious link was enough to trigger the action for a
  logged-in administrator, author or moderator. These links now carry a security token.
- **The demo locked down.** Its shared account (demo / demo) is an administrator: it could
  turn on debug mode for all visitors, browse and read its files, the error
  log and phpinfo, see the scheduled task key, change the site settings, create
  API keys, ban IP addresses or members for good, redirect the PayPal address for donations, and
  erase images that the reset could not bring back. On a demo, any action toward
  a locked area is refused before reaching its screen, sensitive screens display a
  notice, the file manager shows only a sample tree, no file is
  deleted, and the reset also restores maps and game modes, team roles,
  notifications, points and permissions.

### Fixed

- **A file rejected on upload** (forbidden extension, or demo) crashed the page
  (error 500), for example when changing your avatar: the field now says why.
- **Statistics over an outsized period** (centuries, hour by hour) exhausted the server's
  memory: the period and the number of points are capped.
- **The member export** wrote one PHP 8.5 warning per row to the log.
- **On the demo**, the green banner covered notifications, and the emergency account
  appeared in a team's player list and in the dashboard activity.
- **A thin white line ran along the right edge of the text editor in dark mode** (comments,
  messaging, administration): the editor painted its input area white, and that white overflowed
  by a fraction of a pixel.

## [1.2.15] — 2026-10-02

### Changed

- **The administration quick search (Ctrl+K), reworked**: sections in subtle small caps,
  aligned with the icons, instead of headings stuck to the edge; the section name is no
  longer repeated at the end of each row; a magnifying glass in the field; the matched part highlighted. The
  search ignores accents (on a French site, "evenements" finds "Événements gaming") and also searches the
  sections ("gaming" lists the six modules in that section).
- **The member's photo in the administration sidebar**, instead of the first letter of their
  username, which remains for an account without a photo.

### Fixed

- **The arrow keys, in the quick search**, moved an invisible selection: only the
  mouse highlighted a row.
- **"What this version brings"**, in the update window, opened the changelog
  at the top of the page, on the previous version: it now leads to the announced version.
- **The comments box of a Blog post** lost its inner padding and the line under its
  title: "Comments" and the counter touched the edge. Five themes applied to every nested
  box a rule meant for the header of transparent boxes.

## [1.2.14] — 2026-10-02

### Changed

- **The Monitoring page, in tabs**: an always-visible summary (version and update, PHP,
  backups, diagnostic tools), then *Overview*, *Backups*, *Diagnostics*, *Server and
  security* and *Files*. It stacked about ten cards in a narrow column, over nearly
  2,700 pixels; each tab now fits on one screen. Site health is stated soberly ("All
  good", "Points to check", "Errors to fix").
- **Administration action buttons, the same everywhere**: edit, access and sort in neutral
  gray; delete in red outline, with a trash can. They changed from one page to the next — sky
  blue, teal, solid buttons, crosses or trash cans. The administration's design rules are described in the guide
  "Create a module", and a check enforces them.
- **Status badges, soft everywhere**: a pale background and a stronger text of the same color, like
  "Published". "Active" was written white on solid green, "Built-in" in black. No more solid
  colored buttons in list rows (preview, duplicate, restore…).
- **The create button, in the same place everywhere**: at the top of the card of the list it feeds.
  Eight pages put it in the top bar, at the bottom of the list or above the card
  (ads, shop, donations, payments, slideshow, teams, games, Monitoring backups).
- **Themes & addons, compact**: a search, the number of extensions of each type, type
  and status filters that combine ("inactive modules"), and a denser list view, shown by default, next to
  the grid. The page opens on modules; it lined up the 120 or so extensions as
  large cards, over more than 10,000 pixels.
- **Email templates, in a single table**: templates grouped by family (accounts, forum,
  messaging, moderation, newsletter), with their subject, languages and status on one row. The
  page stacked one card per template, over 2,000 pixels; it is now half that.
- **Discord, in tabs**: *Overview*, *Features*, *Server setup*, *Channels and
  forums*, *Groups and roles*, *Timed roles* — you move from one to another without going back to the main
  page. The bot log scrolls within its card instead of lengthening the page.
- **Statistics, easier to read**: the dates and the interval fit on one line. The chart no longer repeats
  in its legend the checkboxes, which already carry each series' color. Its curves no longer
  dip below zero and its axis stops at the bounds of the chosen period. Its dates follow the page
  language rather than the browser's (on a French page, "2 oct. 2025", not "Oct 2, 2025"). Beyond three
  series, lines only rather than overlapping areas.
- **Permissions → Matrix view**: modules grouped by section, as in the sidebar, in
  columns. They were lined up as some forty large tiles, in no order.
- **The audit log, in pages of 50**: it lined up 200 rows in one block, over more than 5,000 pixels.
- **Users: a search** by username or email, above the member list.
- **The file manager says what its buttons do**: "Add files", "Create a
  folder", "Move", "Delete", instead of four colored icon-only pills. Its
  dialogs have a neutral "Cancel" and a named confirm button.
- **"Save" on the button that confirms a change**, everywhere. Twenty forms said
  "Edit" (forum, news, pages, events, teams, games, galleries…).

### Fixed

- **Some administration lists stopped at their first page**, with no link to the rest: the
  members and comments beyond the first 20, the awards and recruitments beyond the first 10,
  as well as the forum's "My subscriptions" on the member side. The card's counter only gave
  the page shown ("20 members" for 25).
- **Dates entered in English and German.** In English, the picker wrote October 2
  as "10/02/2026 g:05 A", prefilled an event dated 30/09/2026 as "06/09/2028", and October 2
  was saved as February 10. In German, a date displayed as "02.10.2026" was not read back on
  saving. English short dates are now written day/month/year with a 24-hour clock, as in the other
  languages ("02/10/2026 14:05"); a test checks, in all six languages, that a displayed date reads back.
- **No more false "corrupted file" alerts before an update.** Monitoring compared the
  site's files with the checksum list of the latest published version, even when the site was
  not yet at that level: everything the new version changes appeared corrupted (102 alerts
  on a site just before its move to 1.2.13). The check now waits until the site
  is up to date, and says so.
- **Statistics**: in German, the chart never loaded; the "7 days",
  "30 days", "90 days" and "1 year" buttons did nothing, in any language.
- **The modification date of a wiki page** showed the time of the last visit: the view counter
  rewrote the modification date. Same defect on classified ads.
- **In the file manager's delete dialog**, "Cancel" and "Delete" were
  two identical red trash cans. The module's French texts had lost their accents ("Dossier cree
  avec succes", "Element deplace") and counted in "élément(s)".
- **The dashboard and the audit log** show what happened ("Debug mode turned on",
  "Settings saved") and no longer technical identifiers (`monitoring.debogage.allume`).
- **The administration Comments page** displayed code instead of the date of each
  comment.
- **An accented title** ("journ&amp;eacute;e") no longer shows as code in the header of
  administration cards.
- **In dark mode**, the administration's colored texts (alerts, badges, green success messages)
  stayed dark on a dark background, almost unreadable.
- **Dates in moderation** (sanctions, reports, a member's history), **in the trash,
  backups, notifications and the Discord bot log** are displayed in the visitor's language and
  time zone ("21/09/2026 22:54"), and no longer as stored in the database ("2026-09-21 20:54:11").
- **In French, the statistics label "Connexions de membres"** (member logins) was misspelled
  "Connections de membres".
- **Dates follow the visitor's language**: the wiki, the Bugtracker, classified ads, the
  guestbook, the newsletter, archived conversations, the file manager and the events
  widget hard-coded them — as stored in the database ("2026-09-20 22:54") or French style even in
  German ("02.10.2026" expected).

## [1.2.13] — 2026-10-02

### Added

- **The error log, in the administration** (*System → Monitoring → Error log*). No more
  need for FTP or server access to know what failed: the site's errors, grouped,
  from most recent to oldest, sorted by severity, filterable by period or by word. The installation
  path, passwords, keys and tokens are masked on screen, email and IP addresses partly; the
  file can be downloaded and emptied (the old one is kept alongside). Monitoring flags errors from the
  last 24 hours, and a `logs/` folder the site can no longer write to.
- **Diagnostic tools are turned on from the administration** (*Monitoring → Diagnostics*), for one
  hour, without editing `config/neofrag.php` over FTP:
  - **debug mode** — what it displays, the bar at the bottom of the page and error details, is now
    shown only to logged-in administrators; when on, it was displayed to all visitors, queries
    and visit data included;
  - **the page trace**, which can finally be read in the administration: the latest pages served, their
    queries and their duration, with sensitive values masked;
  - **the translation check**, with the list of missing texts per language and per extension; the
    flag it adds in front of translated texts is now shown only to administrators.
- **The administration organized into nine sections**: *Content*, *Community*, *Activities*, *Gaming*,
  *Knowledge*, *Media*, *Outreach*, *Support*, *Monetization* (and *System*). The previous six
  mixed the calendar with media or the Bugtracker with community, and thirteen modules ended up in
  "Other modules".
- **The live editor's "Navigation" menu** follows the same sections, as collapsible submenus,
  with a search; it lined up more than forty pages one after another.
- **The site address can be corrected from the administration** (*Monitoring → Site address*). After a
  domain change, email links still led to the old one until you edited
  `config/url.php` over FTP.
- **A reference for every error.** A visitor who hits an error reads an eight-character
  reference; the administrator searches for it in the log and lands on the right line.
- **Times in everyone's own time zone.** A member chooses their time zone in their profile; failing
  that, the site uses their browser's, and failing that the site's, a new setting in
  *Settings → General Preferences*. Times entered in forms are understood in the
  time zone of whoever enters them. The time zone list is grouped by region, with cities named in
  the site language.

### Fixed

- **Times are no longer off.** The site displayed them in server time — universal time — for
  everyone: two hours behind for a visitor in France in summer. The member profile's time zone, for its
  part, applied only on profile pages, and shifted the dates that member saved. The calendar, the wiki,
  the Bugtracker, archived conversations and the file manager wrote their times without conversion; the
  web radio's "live" show was read in server time.
- **The calendar**: the start and end of an event are chosen in a date picker, and
  no longer in a text field in "YYYY-MM-DD HH:MM:SS" format; an accented title no longer shows as
  "journ&amp;eacute;e"; the formatted description no longer shows its markup; the calendar export
  receives clean text.
- **Lists with search** say "No results" in the site language, not "No results
  found".
- **The live editor** now offers to lay out the Forum, Galleries, Teams, Contact and Awards pages: its
  menu left them out, as it did not recognize their home page.
- **A page that crashes says "An error occurred" (500)**, with its reference, instead of "Page
  not found", which suggested a wrong address.
- **No more blank pages.** A fatal error, or an error raised outside a page, displays an error page
  in the visitor's language. So does an unreachable database (503), instead of a raw English message,
  and the outage is finally recorded in the log.
- **A failing action says so**: a dialog that does not open, a rejected submission, a server that no longer
  responds display a message, with the error reference — instead of a button left grayed out without
  a word. Monitoring, when it fails to refresh, stops its hourglass.
- **Backup and update tell the truth.** They announced "Backup saved" or
  "Update completed successfully" even after a failure; they now announce success only if the
  server confirms it, and otherwise say what failed. The backup also checks that it actually wrote
  its archive: in a non-writable folder or on a full disk, it reported success — and the
  update, which relies on it to roll back, would have had nothing to restore.
- **A form field's error is shown under the field**, and no longer only when hovering over a
  small icon.
- Two internal errors no longer leave a trace in the log: a form submitted without one of its fields,
  and a database query that returns nothing to read — the latter crashed the page.
- **The debug bar displays again**: a numeric value crashed it since the code started checking its
  types strictly, and its timeline was wrong.
- **The update announcement is visible again.** It had shrunk to a colorless "1.2.x"
  in the top bar: a cleanup of the administration styles had swept away its own. It
  gets back a box under the logo — "Update available · NeoFrag X.Y.Z" — and a readable
  badge in the top bar.
- **The update window** showed an empty block and left a warning in the log: it now says
  which version is coming, that the site is backed up before starting and returns to its previous
  state on failure, with a link to what the version brings.

### Removed

- **`NEOFRAG_LOGS_DB`**, which nothing read: new installations no longer write it to
  `config/neofrag.php`. An existing installation can keep it; it has no effect.

## [1.2.12] — 2026-10-02

The Discord bot moves to **version 0.2.0**. To update it: replace its folder with the one from the new archive,
keeping the `.env` file, run `npm ci --omit=dev`, then restart it. Then create a new access key in
*Administration → Discord* — the page asks for it — and replace the old one in `.env`: without it, the bot
lacks the Bugtracker permissions. Server setup also needs the Discord "Manage Channels" permission: re-invite
a bot invited with 1.2.11 through the "Invite the bot" button, or give its role that permission in the server
settings.

### Added

- **Discord bot: features that turn on one by one.** *Discord → Features* lists what the bot can do (it
  declares this itself). Each one is turned on, off and configured from the administration; the change applies
  within a minute, without restarting the bot. The **Resynchronize** button brings everything back in line,
  and on startup the forum catches up on what was written on Discord while the bot was away.
- **Discord bot: it catches up with the site when it returns.** What is written on the site while the bot is
  off goes to Discord when it restarts, within the 30 days of events the site keeps. Until now it started
  again from the latest event, and what had been written in the meantime never reached Discord.
- **Discord bot: server setup.** From the administration, the bot creates a category on Discord, one Forum
  channel per chosen forum (with its prefixes as tags) and one role per chosen group, sets up the mappings
  itself, and reuses what already exists instead of duplicating it. A preview comes first, and the last setup
  can be undone.
- **Discord bot: `/forum`.** `/forum account link` gives a single-use link that links your Discord account to
  your site account; you can also choose to move what you had posted from Discord under your name.
  `/forum account unlink` unlinks it. Without a linked account, `/forum visibility` lets you choose how you
  appear on the forum: your Discord username, an anonymous name, or a username of your choice, which can be
  changed every seven days — messages already posted follow suit.
- **Discord bot: forum prefixes ↔ Forum channel tags**, in both directions: setting a
  prefix on a topic sets the tag on the thread, and vice versa.
- **Discord bot: the Bugtracker in a Forum channel** (feature to turn on in *Discord →
  Features*). Each ticket becomes a thread: its type and status are its tags, which
  the bot creates in the channel and keeps up to date. Title, description and priority follow; the thread
  is archived when the ticket is closed, and a duplicate points to its original ticket. Comments
  go both ways. From Discord, `/bug` and `/idee` open a ticket through a small
  dialog (title, description), and a thread opened by hand in the channel becomes a ticket — for a
  member who has linked their account. Tickets still open get their thread when the
  channel is chosen. The site is authoritative: a tag changed by hand on Discord is set back to what the
  ticket says.
- **Discord bot: timed roles** (feature to turn on). The `/role` command, reserved for
  those who can manage roles, gives a member a role for a set time (a sanction, trial access,
  an event role), removes it or shows the active ones. The bot removes it when it expires, even after
  a restart, and gives it back to a member who leaves then rejoins the server to escape it. Roles
  linked to a site group, roles managed by Discord and roles placed at or above the highest role of the bot or
  of whoever gives them are refused. The *Discord → Timed roles* page lists them, with "Remove now".
- **API**: two new permissions, "Read the Bugtracker" (`bugtracker:read`) and "Write to the Bugtracker"
  (`bugtracker:write`). They let a program read tickets (`GET bugtracker/tickets`, only the open ones with
  `open=1`) and their comments, open a ticket on behalf of a member, comment on it on behalf of a member or a
  Discord account, and edit or delete those comments. A forum topic's prefix can also be changed through the
  API (`PATCH forum/topics/{id}`). The bot gets the `discord/*` endpoints of its new features: timed roles
  (`discord/timed-roles`), server setup, account linking.

### Security

- **A title written through the API can no longer inject code into the site's pages.** The title of a
  topic created through the API — for example the name of a Discord thread copied by the bot — was stored
  as is, while the forum displays its titles without re-encoding them: an HTML tag in that title
  ran on the forum page. The API now stores its texts the way the site stores its own,
  and the tab title is always encoded.

### Fixed

- **Accents in the Bugtracker** were displayed as "r&eacute;agit" on the page of a ticket opened through the
  form, and the "Already reported?" search found no accented word. The texts the API
  returns — usernames, titles, descriptions, comments — are also in plain text: the Discord bot
  copied them encoded. Link sharing previews (Discord included) no longer show
  "&amp;eacute;".
- **Bugtracker**: choosing the "Duplicate" status without a valid ticket number kept the old
  status while announcing "Ticket updated"; the message now says what was not applied.
- **Discord bot: deleting on Discord the copy of a site message no longer erases the original.** When the
  bot no longer had that message in memory (after a restart), deleting on Discord the copy of a message
  written on the site by a member with a linked account also erased the message on the site. In that case,
  the bot now only erases on the site the messages that came from Discord.
- **The wiki**: on the wiki home page, a top-level page without subpages showed as an empty section; it now
  shows its first sections (or the start of its text) and a link to read it, and the sidebar turns it into a
  link.
- **Not-found and forbidden pages are no longer empty pages.** On all public themes, an
  address that leads nowhere (404) or a restricted page (403) showed only the header and the
  footer, without a word — a defect inherited from NeoFrag. They now say what is happening, in all six
  languages, with a button back to the home page (to the dashboard in the administration), and the browser
  tab reads "Page not found" or "Unauthorized access" instead of the module name.

## [1.2.11] — 2026-10-01

### Added

- **The Discord bot** (version 0.1.0), which connects a site and its Discord server:
  - **roles and usernames** — a member who has linked their Discord account gets, on the server, the roles
    linked to their groups, and loses them on leaving those groups; if the option is checked, their server
    nickname is their site username. The site is authoritative, and a role that nothing is linked to is never
    touched;
  - **forum ↔ Discord Forum channel**, in both directions — a site topic becomes a thread, a thread
    becomes a topic; replies, edits and deletions follow. Messages coming from the site
    appear under their author's name and avatar; those from Discord, under the linked member's
    account or under their Discord username. Channel by channel: sync everything, or only the threads a
    moderator marks with a reaction.

  It is a separate program (Node.js 22.9 or later, on a machine that is always on), shipped in
  its own archive with each version. On its machine it keeps only the site address and an access
  key. Guide: "The Discord bot" in the wiki.
- **The Discord module** (optional, requires the API module): the bot key, **stored encrypted**, the
  server, the on / pause switch, restart, the bot's status (online, paused, offline)
  and its **log in the administrator's language**, the channel ↔ forum and
  group ↔ role mappings, and the link that invites the bot with only the permissions it needs — never
  "Administrator".
- **API**: the `discord:bot` permission and the `discord/*` endpoints the bot needs.

## [1.2.10] — 2026-10-01

### Added

- **A REST API** (**API** module, optional), for programs that talk to the site without a
  browser — first of all the upcoming Discord bot. An administrator creates **access keys** with
  only the permissions wanted; a key is shown only once, the site keeps only its hash, and
  it can be revoked in one click. The `/api/v1/…` endpoints return JSON: the site status, a member (by
  ID or by linked Discord account), the groups, the forum (tree, topics, messages) and an
  **event feed** (new topic, new message, group change…). It also **writes**
  to the forum on behalf of a member, or of an unlinked Discord account — posted under its Discord username,
  marked with the Discord logo. Rate limited to 120 requests per minute per key; errors have stable
  codes. Guide: "The REST API" in the wiki.
- **My linked accounts** (member area): see your linked Discord, GitHub or Google accounts, link one,
  unlink one — unless it is the only way to log in.
- **Sign up with Discord** (or GitHub, Google): an account nobody has linked creates a member, linked
  from the start, when registrations are open. Until now it got an "unknown account" error.

### Fixed

- **Linking your Discord account logged you into another member's account** when that Discord was already linked to
  the latter: the session switched over to them. Linking is now refused, with a clear message, and
  the same external account can no longer be linked to two members.

## [1.2.9] — 2026-10-01

### Fixed

- **Blog: a post's table of contents stayed titled "Sommaire" in other languages**, and left a warning in the
  site log. A message in the members administration ("Member groups updated") had the same defect.
- **Blog: the category badge stretched across the full width of the cards** instead of staying a
  small label.

## [1.2.8] — 2026-10-01

### Added

- **Blog: series.** A post in several parts: each part shows the list of the others and
  its place ("Part 2 of 3"), and the series has its own page. Series are managed in the Blog
  administration, next to a new page that finally lists the categories.
- **Blog: a page per author** (their posts) and clickable **monthly archives**.
- **Blog: sharing shows the cover.** A post shared on a social network displays its
  cover and not the site logo; search engines receive its structured data
  (title, author, date, image).
- **Six Blog widgets**: latest posts with their thumbnail, most read, the featured post, the
  categories, the tags and the archives.

### Fixed

- **Forum: the forum list disappeared for logged-in members** as soon as a forum had
  topics only in its subforums. Visitors were not affected.
- **Unsubscribing from the newsletter unsubscribed no one**: the unsubscribe page failed
  with an error. So did deleting a subscriber in the administration.
- **Deleting your account** marked the account as deleted, then failed with an error before closing its
  sessions and erasing its recovery codes.
- **Turning off two-factor authentication** failed with an error once it was turned off, and left the old
  recovery codes in the database.
- **Blog: an empty category could not be deleted** (the Blog always thought it was in use), and
  its deletion was not protected against forged links. It now asks for confirmation.

## [1.2.7] — 2026-10-01

### Fixed

- **Forum: a link forum shows its own icon.** The icon chosen in its administration form now replaces the
  globe it always displayed.

## [1.2.6] — 2026-10-01

### Added

- **Forum: the "solution" reply.** The author of a topic (or a moderator) marks the reply that
  solves it. It is highlighted under the question, flagged in the thread, and the topic shows as
  "Resolved" in the list. A deleted or moved solution does not leave the topic resolved.
- **Forum: topic prefixes** ("Question", "Tutorial", "Important"…), created by
  the administrator with their color, and translatable into each active language. Members choose one when
  opening their topic, and a forum's list can be filtered by them.
- **Forum: an icon per forum**, chosen in the administration.

### Fixed

- **The forum "Statistics" widget** also counted categories the visitor cannot
  read, and deleted messages: it now counts only what the visitor can go and see.
- **The forum demo data** counted the first message as a reply ("4
  replies" in the list for a topic that has 3).

## [1.2.5] — 2026-10-01

### Added

- **A real Blog.** The Articles module becomes the Blog, under `/blog` (the old `/articles/…` addresses
  redirect permanently). The list opens on a **featured** post, then illustrated cards,
  category filters and a sidebar (search, most read, categories, tags, archives).
  A post's page has a cover image with its title, a reading progress bar, a table of contents that follows
  the section being read, the author, the previous and next posts, and "Read next". The administrator
  chooses the layout of the list and of the post page; visitors switch from grid to rows, and their choice
  is remembered.
- **Bugtracker: the list can be filtered by type** ("Bug", "Feature request", "Question", "Other"), and
  "New ticket" keeps the chosen type. A link to the filtered list (for example `/bugtracker?type=bug`) shows
  the tickets already reported of that type before you open a new one.
- **Bugtracker: "Already reported?"** While you write the title of a new ticket, open tickets
  that resemble it appear below it, so you comment on the existing one rather than open a
  second. A ticket can also be marked as a **duplicate** of another: its page points to the original.

## [1.2.4] — 2026-10-01

### Added

- **Forum: translatable categories and forums.** Each keeps its default title and can receive, in its
  administration form, a title per active language (and, for a forum, a description). Visitors see the
  translation for their language, otherwise the default title. A link works with the default title as well as
  with each translation.

### Fixed

- **A migration already in place no longer blocks the update.** Applied by hand without being
  recorded, it failed ("column already exists") and would have canceled the entire update. Migrations
  are now applied statement by statement, skipping only what is already
  done.
- **Modules shipped with the core finally receive their database changes.** Only the
  update of an addon through the marketplace applied them: a module like the forum or the
  calendar received its new code through the core update, never its database. They are now applied
  along with the core's, through the button as after an FTP upload.
- **Articles**: the module's six permissions (add, edit, delete, and their equivalents for
  categories) were checked nowhere. They now are, bulk actions included.
- **Scheduled content no longer appears before its date**, nor does content moved to the trash: the
  "Latest articles" widget, the sitemap sent to search engines, the news search, a member's
  activity and the "Latest articles" block showed them. The news Tags widget no longer mixes
  languages, and the categories widget no longer counts drafts.

## [1.2.3] — 2026-10-01

### Fixed

- **The update finally updates itself.** Its code — along with that of backup,
  restore and the marketplace — lived in the `install/` folder, which the update never
  rewrote: a site kept the one from the day it was installed, and its fixes did not
  reach it. It now lives in the core (`neofrag/installer.php`), rewritten with each
  version, and Monitoring checks its integrity. The rest of `install/` also follows the versions;
  only the `install/db.txt` lock stays specific to the site. On an existing site, `install/` is updated
  starting from the update after this one.
- **A site that deletes its `install/` folder after installation**, as is often
  advised, keeps its update and its marketplace; their messages then stay in French.

## [1.2.2] — 2026-10-01

### Fixed

- **Teams, games, categories, partners and awards no longer disappear in other
  languages.** The administration saves a title only in the language you write in, and these objects
  existed only in that one: on the demo, three teams in French, none in English.
  A team's group, with the permissions that depend on it, disappeared along with it. They are now displayed
  in the requested language, otherwise in French, otherwise in whichever one exists. Lists that
  returned one row per translation (recruitments, matches, administration activity feed) now
  return only one. Content lists (news, articles, pages) stay in the requested
  language.
- **An update finally applies the core's database changes.** Until now, only
  installation applied them: an updated site kept its old database under new code. Through the
  button, they are applied during the update, which is canceled if they fail. Over FTP, they
  are applied on the first page served by the new code. (Modules' own changes followed in 1.2.4.)
- **Login with Discord**: the address of an animated avatar (102 characters) exceeded the space the database
  kept for it (100), and login failed; that space grows to 255 for all linked accounts. A Discord member
  without an avatar got a broken image.

### Changed

- **Email sending moves to PHPMailer 7** (7.1.1). Its only breaking change concerns classes that
  extend it, and NeoFrag has none. Tested with a real send using a site's settings,
  over SMTP as well as through `mail()`.

## [1.2.1] — 2026-10-01

### Security

- **Two vulnerabilities fixed in the Markdown library** (`league/commonmark` 2.10.3): a denial of
  service through tables crafted to slow down the server (high severity, GHSA-3q6v-r5mr-hxv8), and a bypass
  of the filter that strips disallowed HTML (moderate severity, GHSA-97jj-33gv-5xf9). Made public on
  September 21, 2026. The HTML sanitizing library (`ezyang/htmlpurifier` 4.19.1) also moves to its latest
  version.

### Fixed

- **Monitoring no longer reports present files as "missing".** Since 1.2.0 was released, it compared the site
  with the official file list of the version, but excluded the Sass sources (style files before compilation)
  only on the site's side: four files wrongly reported missing, and a health status of "The ship is sinking!".

### Changed

- **A core update is recorded in the audit log**: who started it, when, from which version to which version,
  how many files were written and how many old files were removed. Until now it was written to the error
  log, where it looked like an anomaly.

## [1.2.0] — 2026-09-23

### Added

- **Core updates take a single click** (2026-09-23). Starting with this version, **Administration
  → Monitoring** reports new versions of NeoFrag Reborn and installs them: site backup,
  package checksum verification, applying the package, database update, and a return to the backup
  if anything fails. This is the first version released through this path; it was tested end to
  end on a fresh site before release. A site on 1.1.0 does not have this button yet: it moves to 1.2.0
  by hand, by replacing its files. For maintainers: `tools/build-release.php` produces the three files to
  publish together (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`); the procedure is in
  `docs/guide/marketplace.md`.

- **Announcements on Discord** (2026-09-23). A webhook pointing to a Discord channel now
  receives a real message, with title, link and color: new news item, new article, new
  member, new comment, new forum topic. Until now Discord rejected what the module
  sent, even though its description promised Discord.

- **"So-and-so is live"** (2026-09-23). When a Twitch or YouTube channel followed by the
  "Live status" widget goes live, the webhooks subscribed to the "Channel goes live" event announce it,
  only once per stream, with the stream's thumbnail.

- **The demo also speaks English in its content** (2026-09-23): its news items, articles,
  pages, categories and galleries have an English version, which shows off the multilingual feature.

- **The whole product speaks all six languages** (2026-09-23). The administration, the
  modules, the widgets, the themes, the settings, the buttons, the messages and the scripts: nearly
  800 strings were written directly in French and stayed that way on an English,
  German, Spanish, Italian or Portuguese site. They all go through the translations now, in all six
  languages. In particular:

  - **The installation wizard is translated**, with a language selector; it initially follows the
    browser's language. The command-line installer takes `--lang=en`, or the
    terminal's language.
  - **The marketplace** shows each addon's name and description in the site's language.
  - **Countries** (profile, opponents) are displayed in the site's language.
  - **The bundled roles and email templates** are displayed translated; a name changed by
    the administrator stays as the administrator wrote it.
  - **Moderation** shows a report's status, reason and type, and a sanction's type,
    spelled out — it used to display the codes (`pending`, `forum_message`, `ban_temp`).
  - **A group created in one language no longer disappears** when the site is displayed in another —
    it dropped out of the list, and its permissions with it.
  - The administration theme declared itself as written in English: on an English site, its French
    strings were never translated. This is fixed, as are the module scripts, which
    looked for their translations in the wrong place.
  - The account deletion confirmation asks you to type the word in the site's language
    ("DELETE" in English), and no longer "SUPPRIMER" everywhere.

  A check, `check-textes-en-dur`, now rejects any hard-coded interface text in CI.

- **Visitors' browsers can keep the site's files, if you ask for it** (2026-09-22). The manifest already
  made the site installable; the site can now keep its images, styles and scripts in visitors'
  browsers. **Off by default**, to be turned on in **Administration → Settings → General Preferences**
  ("Installable app").

  **Pages, however, are never kept.** A page held in memory would survive a new
  deployment: anyone who had already visited would be served a days-old site, with nothing to signal it.
  A change therefore remains visible immediately.

  **Unchecking really uninstalls.** This is the tricky part of this technology: a component
  of this kind, once installed in a browser, stays there even if the file is removed from the
  server. The switch therefore removes nothing — it serves a version that uninstalls
  itself on the next visit. That is why the feature is off by
  default, and why a core update never turns it on by itself.

- **The site can be installed as an app** (2026-09-21). `/manifest.webmanifest` is generated by
  the site itself — name, description, theme color, start URL and favicon come from the
  settings — rather than dropped in as a file, which would be wrong everywhere else.

- **Sensitive folders are closed under Apache too** (2026-09-21). The package's `.htaccess` files
  protected **less** than the supplied Caddy configuration: under Apache, the installer stayed reachable
  once the site was installed (only its own check kept it from running), and on a site installed from the
  git repository, `docs/`, `tools/` and `tests/` were reachable too — the documentation could be read as
  plain text. `cache/`, `config/`, `install/`, `tools/`, `tests/` and `docs/` now each have their own
  guard, the root also denies `.neon` and `.md` files, and `tools/check-htaccess.php` rereads the list on
  every run, with the reason for each line.

- **An unknown address no longer writes an error to the site log** (2026-09-21). The `pages` module,
  which receives as a last resort the addresses nothing else recognizes, wrote an **error** line for each
  one — every probing bot added some. For addon developers: a controller can now declare a refusal as
  routine (`Module_Checker::refus_ordinaire()`); the diagnostic is still shown on screen in debug mode.
  The `check-journal` check now rejects any line the product itself writes to the error log.

- **Two checks enforce the repository's rules** (2026-09-21), for contributors. `tools/check-tools.php`
  verifies that every tool follows the shared rules: descriptive header, reliance on the shared library
  rather than copied code, standard verdict and exit codes, reserved port, up-to-date catalog.
  `tools/check-docs.php` checks the documentation: correct figures, valid links and anchors, no orphan
  document, no mention of a tool that no longer exists, no passage copied from one document to another,
  documents that stay readable. Both run in continuous integration.

- **Eight new addons, all optional** (2026-09-20). An update does not install them: on an existing site,
  they are added from **Administration → Themes & addons**, and removed the same way. A fresh installation
  includes them with the *Complete* profile, offered by default, but not with the other profiles.

  | Addon | What it does |
  |---|---|
  | **Seasonal effect** (widget) | Snow, confetti or leaves across the whole screen, during a date range. Dates are written `MM-DD` **without a year** — the season comes back on its own — and can span New Year (`from 12-15 to 01-06`). Out of season, the page receives **nothing at all**: no image, no script. The system setting that asks for reduced motion (`prefers-reduced-motion`) is respected, and the animation pauses when the tab goes into the background. |
  | **Feed reader** (widget) | The latest articles from an external RSS or Atom feed. **A page almost never waits for a third-party site**: it reads a cache, and even serves stale content rather than keep visitors waiting; refreshing happens in the scheduled task. Only the very first display, with an empty cache, waits for the feed, for three seconds at most. A feed that is down is set aside for ten minutes instead of being retried on every visit. |
  | **Quotes** (module) | A categorized collection, with author and source. |
  | **Recipes** (module) | Ingredients and steps entered one per line, times and number of servings, `schema.org/Recipe` markup that search engines read. |
  | **Glossary** (module) | A lexicon sorted by letter. "Éclaireur" goes under E, "Æther" under A, "1v1" under #. The letter is computed, never typed in. |
  | **Places map** (module) | Places on an OpenStreetMap map, with address, description and link. The map library is **hosted by the site**; only the background map tiles come from OpenStreetMap. **The page stays useful without JavaScript** — the list, the addresses and the links are there. |
  | **Web radio** (module) | A stream player, what's on air, and the weekly schedule. A show can span midnight: "Saturday 22:00 → 02:00" remains Saturday's show. |
  | **Sandbox** (module) | Members only: you try out formatting there and see the site's **exact** rendering. Above all, it shows **what the site removed** — it is the only page that answers "why did my table disappear?". |

  The three content modules (quotes, recipes, glossary) share the FAQ's pattern:
  categories, search, filter, sorting and drafts.

- **Choosing content at installation.** The web installer now offers a **site profile**
  between the requirements and the database: *Complete*, *Gaming / eSports*, *Community* or *Core only*.
  Modules can still be unchecked one by one, and those a module requires are added automatically
  (awards needs teams) with a message. Without JavaScript, the form still works
  — the server keeps only what belongs to the chosen profile.

  **No addon list is written by hand**: profiles are built from each addon's `presets`
  declarations. That is the difference from the June 2026 attempt, abandoned because a
  slimmed-down installation ended in a server error (500): core modules queried the tables of optional
  modules without checking that they existed. Those checks now exist and are verified on every CI run.

- **Every shipped module, widget and theme declares its role**: in its `__info()`, it states whether it
  belongs to the core, which installation profiles it is part of, and which modules it needs. Core
  membership was until now a side effect of being in the package — which is how `emojis`, `files`
  and `webhooks` ended up there without any decision being made.

- **Three new continuous integration safeguards**:

  | Check | What it rejects |
  |---|---|
  | `check-addon-declarations.php` | an addon that declares nothing, a nonexistent dependency, **a core addon that depends on an optional one**, a non-distributable addon published to the catalog, a declaration that disagrees with `seed.sql` |
  | `check-addon-coupling.php` | a **fatal** coupling (table, class) neither declared nor annotated; a hard cycle. Read from the code with the **PHP tokenizer**, not with regular expressions — so no false positives on comments |
  | `check-install-profiles.php` | a profile that does not boot. It **really installs** on a throwaway database, serves the site and fails on the slightest 5xx — including on the routes of absent modules, which must return a clean 404 |

- **Finding a member from the search bar.** The site search only found
  content — forum, news, pages. Looking for someone meant opening the member directory and
  browsing through it, even though it is one of the things people search for most often on a community
  site. Members now appear in the global search **and** in the
  instant suggestions, by username, first name or last name.

  Nothing new is exposed along the way: these three fields are already shown on each member's
  public profile, and the search applies exactly the same filter as the directory — a deleted
  account does not appear in it.

- **Managing a series of events in one go.** Creating a recurring event already generated
  all its occurrences in one click — going back over them then meant opening them one by one.

  Now, an occurrence in a series has a **"Delete the whole series"** button, whose
  confirmation states how many occurrences will go, and the edit form offers
  **"Apply to every occurrence in the series"**. The title, type, descriptions, location, image
  and publication status are copied to each one; **the dates never are** — they are what
  distinguish one session from the next.

- **Rolling back an update, and restoring a backup.** The CMS already took a
  full backup — files and database — just before each core update. It did not know how
  to use it again: the safety net was in place, but no one knew how to fall into it.

  Now, if putting the files in place or a migration fails, **the site is automatically put back
  in the state it was in**; the message says both what failed and whether the rollback
  succeeded. Each backup in the list also has a **Restore** button, for cases where the
  damage does not come from an update — a third-party module, an action you regret. The
  confirmation states what will be lost.

  What the restore does **not** touch, on purpose: the site configuration (putting back outdated
  database credentials would cut the site off from its own database), the logs (they are the record of
  the incident being repaired) and the cache (emptied so it gets rebuilt, rather than put back in the state
  of another version).

  Along the way, the backup now includes `vendor/`, which the update package ships and
  which it did not include: without it, a rollback would have put the old code back on top of the new
  dependencies.

- **Replying to a comment.** The "Reply" button, disabled in the original code, now works: the reply is
  shown indented under the comment it answers. Only a top-level comment of the same content that has not
  been deleted can be answered (one level of replies); the server checks this.
- **For theme creators: zones called by name** (idea borrowed from HiddenCMS). A view now calls a zone by
  its name — `$this->output->region('content')` — rather than by its number — `zone(2)`. The theme
  declares the mapping between names and zones in its `__info()` (`regions`). The old `zone()` call still
  works, and a theme without this declaration is unchanged. The five public themes (Nebula, Forge,
  Granite, Blockcraft, Extend) use the names.
- **Installing from the command line** (`install/cli.php`, idea borrowed from HiddenCMS): an alternative
  to the web wizard, handy for installing a server in a reproducible or automated way. Its options cover
  the database (`--db-*`, `--create-db`), the administrator account (`--admin-*`), the site name and
  address (`--site-name`, `--site-url`), the language (`--lang`), demo data (`--demo`) and how it runs
  (`--force`, `--yes`, `--dry-run`, `--no-lock`). Without options, it asks the questions one by one and
  hides the password as it is typed; `--admin-pass-env` reads the password from an environment variable so
  it does not show in the process list. It uses the same code as the web wizard and installs every shipped
  addon, like the *Complete* profile.

- **A runner for the whole battery**: `php tools/check-all.php`. The checks had
  no common entry point — CI calls them one by one, and locally everyone wrote their own
  loop by hand, never the same one. The runner discovers the checks present (a new check
  joins without anyone touching it), runs `composer audit` first, caps each check's duration, and
  states at the end **what it did not run**: the browser tests (`--navigateur`) and the
  checks with an explicit target, including `check-restauration`, which deliberately damages the site.

- **Each addon shows what it looks like** (2026-09-22). The administration's "Themes & addons" page and
  the marketplace show a thumbnail: a real screenshot of the module, widget, theme or language as it runs
  on the demo. All 61 marketplace addons have one, at 960×600 for modules and widgets. To get there, six
  modules and two widgets were installed on the demo with sample content — a glossary, quotes, recipes, a
  places map and a web radio schedule — and the widgets that display nothing without settings were
  captured with sample settings. Whatever has nothing to show keeps its icon, such as the Discord, GitHub
  and Google connectors, whose icon is their logo. The demo also finally carries its community's identity
  — name, type, creation date and description — which had remained empty.

- **Ten addons finally have a description** (2026-09-22): the six languages and the four external login
  addons (the base one and its Discord, GitHub and Google connectors). It is shown in the site's language.

- **Profile fields defined by the administrator** (2026-09-20). A member's profile only had fixed fields.
  **Administration → Users → Profile fields** lets you add your own — in-game name, platform, rank… —
  from eight field types. Members fill them in the "Additional information" panel of their profile. A
  field is private by default; once made public, it is shown on the member's page. The values are included
  in the member's personal data export and are deleted with their account.

- **The site font can be chosen in the administration** (2026-09-20). **Administration → Settings →
  General Preferences → Site font** offers twelve fonts, which replace the font of every theme. By default,
  "Theme font" keeps each theme's own font and calls no outside service; the twelve fonts on offer are
  served by Google Fonts.

- **Sign-in history is no longer kept forever** (2026-09-20). It records the IP address, browser and date
  of every sign-in, and nothing ever deleted it. It is now purged beyond 395 days (thirteen months), a
  duration set in **Administration → Settings → General Preferences**; 0 turns the purge off.

- **Reminders for followed calendar events** (2026-09-20). A member who follows a calendar event gets a
  reminder before it starts, like the participants of an event in the Events module. The delay is set in
  the calendar's configuration ("Reminder before a followed event", 24 hours by default, 0 to disable).

- **The update package states what it protects and what it removes** (2026-09-20). It carries an
  `nf-manifest.json` file: the site folders an update never writes (`config/`, `install/`, `upload/`,
  `logs/`, `backups/`, `cache/`), and the files the version removes. Until now, the update worked out on
  its own what to delete; a package without this file is still accepted.

### Changed

- **The whole interface speaks Bootstrap 5** (2026-09-23). About 470 uses of Bootstrap 3 and 4
  classes remained, kept working by a compatibility stylesheet: form
  fields, color badges, close buttons, full-width buttons, "avatar and
  text" blocks, input groups, checkboxes, form grids. All the markup is migrated to
  its Bootstrap 5 equivalents; what is specific to the product now carries a name of the product's own.
  A check refuses any old name coming back, even redefined. What you can see:
  - **drop-down lists** have their arrow back — about a hundred, in the administration and
    on the site, looked like text fields;
  - **color badges** (statuses, groups, roles, sanctions) are readable in every
    theme, in light and dark alike: their text failed the contrast check;
  - the **"Moderator" and "Senior Moderator"** groups are displayed: their name was written in
    white on a white background;
  - dialog **close buttons** have a large enough click area on phones.

- **JavaScript is reviewed by a linter** (2026-09-22). Seventy-one project files written over the
  years, with nothing checking them beyond their syntax and the absence of jQuery. ESLint sees what syntax does
  not say: a global variable created by forgetting a `var`, a disguised `eval`, code after a
  `return`, an `innerHTML` fed from a computed value. No npm package is deployed: this is
  development tooling, just like PHP static analysis.

  The rules are chosen on a single criterion — having already bitten here. It is the family of
  "`$` is not defined" that had killed table sorting across the entire administration for three
  months. The existing mess (`innerHTML` uses to sanitize) is **frozen as is** rather than rewritten
  all at once: its count can no longer go up.

- **The PHPStan baseline is regenerated by one command, and the core `Config` class is annotated**
  (2026-09-21). Three new errors led to reopening `phpstan-baseline.neon`: entries added by
  hand were written differently from PHPStan's own, and nearly three hundred blocks no longer
  matched anything — an entire "Function NeoFrag not found" family, dead since the
  bootstrap file declares the function. The configuration lives in `phpstan-base.neon`,
  `phpstan.neon` only adds the baseline to it, and `composer stan:baseline` regenerates it in full. `Config`
  now annotates the current language, the list of languages and the core settings that
  modules read: these accesses are analyzed instead of being frozen.

- **The tools in `tools/` rebuilt on a shared library** (2026-09-21). About sixty tools, each written its
  own way, copied the same plumbing: starting a PHP server, opening an administrator session, launching
  Chrome, reading `config/db.php`. A lesson learned in one did not spread to the others. `tools/lib/` now
  carries this foundation — connection, session, server, browser, repository traversal, SQL, options,
  verdicts — and each tool comes down to its own logic. Fifty-two remain, with no loss of function:
  `check-js-syntax` and `check-js-jquery` form `check-js-sources`; `check-docs-counts` and
  `check-docs-liens` form `check-docs`; `check-lang-args` joins `check-langs`;
  `seed-wiki-docs` and `dump-wiki` form `wiki-docs`;
  `smoke-test` becomes `check-smoke`, like every check; `addons-manifest` and `table-map`, which are data,
  live in the library. Each tool now has its own reserved port (several were sharing one), three exit codes
  that distinguish "nothing to report", "rejected" and "could not judge", and a header from which the
  catalog in `tools/README.md` is generated. `bs5-codemod`, the tool from the June Bootstrap 4 → 5
  migration, is removed: `check-classes-bs4` covers everything it checked, including the breakpoints of
  directional utilities.

- **`declare(strict_types=1)` across the whole useful scope** (2026-09-21): **1,365 files** versus
  78 the day before. The work had been moving forward in small batches since June; it is
  finished. The 209 remaining files are the `views/**.tpl.php` templates, where the declaration would be
  syntactically valid and would protect nothing.

  The conversion was carried out in order of **decreasing risk** — the number of native function
  calls per file — with the test suite run between each batch.

  An underlying consequence, specific to this product: PHP in strict mode **refuses `__toString()` for
  internal functions**. Yet `lang()` returns a *deferred* translation object, resolved at render time once the
  language is finally known, and dates are objects too. The administration bar, each news item's
  page and several screens therefore crashed with a fatal error — module knocked out,
  page truncated, without a word. The conversion now happens at the **escaping point**
  (`htmlspecialchars`), that is, where the value must become a string by definition:
  nearly 500 locations in 86 files.

  The wave also required eleven explicit conversions, three of them on paths taken on every page — the
  display order of languages and groups, read when every session starts, and the computation of the token
  that protects every form against forged requests (CSRF): without them, a site with two active languages
  would have returned a 500 error on all its pages.

- **The log now says WHERE, not just what** (2026-09-21). When a module throws an
  exception, its rendering is abandoned and the page is displayed truncated — silently. The log line
  gave the message without ever naming a file. It now names the first location in the product's own
  code in the call stack. Tracing the origin of the errors above went from an investigation to a
  simple read.

- **The SCSS compiler moves to 2.1** (2026-09-20). On PHP 8.5, each style compilation wrote
  **ten deprecations** to the log; there are none left. 2.x minifies a little more (`.25rem`
  instead of `0.25rem`, `#ccc` instead of `#CCCCCC`), which changes three stylesheets — those of the addons
  and awards modules and of the maintenance page. The equivalence was not deduced from the
  text: both sets of CSS were fed to Chrome's engine, which sees exactly the
  same rules, properties and computed values in them.

- **The documentation was reread in full against the code** (2026-09-17). The installation,
  concepts and development guides (creating a module, a widget, a theme, the framework) dated from June and
  described the CMS as it used to be: an installation with no choice of profile, a theme that loaded jQuery,
  Bootstrap 4 grids. They now say what the product does — installation profiles, addon
  declarations, named regions, jQuery-free front end under a strict security policy, widget settings with
  fallback values, shared color vocabulary. The README,
  the contribution guide, the security policy and the technical reference are aligned likewise.

- **What an addon allows is read from its declaration**, no longer from hard-coded name lists. Three
  of them decided the fate of addons, and all three were wrong: the widgets list
  protected seven names that are not widgets; the themes list protected a nonexistent `default` theme
  **while leaving `nebula`, the only public theme in the core, deletable** as soon as it was inactive;
  the modules list duplicated, in disagreement, what `__info()` already said. Checked addon by addon
  against the old logic: no change in what can be disabled, no change in active state, and
  **39 addons become non-deletable** — which is the purpose of the fix.

- **The Trash no longer knows about other modules.** It hard-coded the list of tables, primary
  keys and restore methods of `news`, `articles`, `gallery`, `comments` and `forum`: it
  therefore could not belong to the core without pulling four optional modules along with it — `comments`
  itself is in the core. Each module
  now declares its restorable types; the core collects them. Same inversion for the content
  descriptors (reactions, subscriptions, revisions).

- **The list of core and optional addons is no longer maintained by hand.** `tools/lib/addons-manifest.php`,
  a table written by hand and frozen in June, is now derived from the declarations. The two had already
  diverged: `emojis` was listed as core while it declares itself à la carte — so it could be unchecked at
  installation but was missing from the catalog, and therefore **impossible to reinstall**. Catalog: 52 →
  **53 addons** that day (61 when 1.2.0 shipped).

- **Installer reworked visually**: two-column layout, style tokens taken from `nebula`
  (navy and teal, accent `#2dd4bf`), gradient moved out from behind the text, logo set in a badge.

### Fixed

- **Monitoring backup works again** (2026-09-23). Since September 21, it
  stopped at the very first row of the database: the "Run backup" button, and the core update, which
  starts with a backup, failed on every site. Found while testing the update before
  releasing it.

- **Switching languages on a translated news item no longer leads to a not-found page** (2026-09-23).
  The language selector keeps the address, and with it the title in the previous language: the translated
  version rejected it. It now redirects to its own address.

- **A translated page is displayed in the right language** (2026-09-23). Public pages were served
  in whichever version the database returned first, regardless of the visitor's language.

- **The theme chosen by a visitor no longer spills over onto another site on the same domain** (2026-09-23).
  The choice was remembered for the whole domain: a visitor who switched one site to Forge also saw
  another site installed under the same domain in Forge. The choice is now remembered **for each
  site separately**, and a new setting, **General Preferences → Theme choice**, lets you
  turn it off: the menu disappears and the default theme applies to everyone.

- **The demo resets itself again** (2026-09-23). An error in its dataset had been making
  the fifteen-minute reset fail since the day before: whatever visitors changed there
  stayed. A check now rejects this defect before anything goes live, and a failed
  reset is written to the log instead of going unnoticed.

- **The demo's emergency account no longer appears anywhere** (2026-09-23): not in the
  member list, not in search, not among online members, not in the administration;
  its page answers "not found".

- **The site on a phone** (2026-09-23), based on screenshots taken on a phone:

  - **The menu is always reachable.** On the Nebula theme, it disappeared below 860 pixels
    wide with no button to reopen it: a "burger" button now opens it. On the other
    themes, a menu too long to fit on one line collapses behind a "Menu" button,
    instead of wrapping until it fills the screen; a short menu stays
    expanded.
  - **Stacked blocks no longer touch**: the member area was stuck to the block above.
  - **Text no longer sticks to members' avatars** (comments, discussions, author of a
    news item, applications, profile).
  - **The slideshow dots** are real buttons, centered under the image, and stay **light**
    on dark themes, like the arrows and the caption: Bootstrap turned them black.
  - **The Extend theme's search** keeps its button next to the field.
  - **Photos in an album** fill their card.
  - The comments header shows the title before the counter, which used to precede it.
  - Abbreviated dates are written in the site's language ("30 août 2026", "Sonntag"), and no
    longer in English; the author line of a news item is back to a normal text size.

- **The gallery photo page exists** (2026-09-23). The "Random image" widget, the
  gallery slideshow and the link of a comment posted on a photo all led to a
  not-found page: it had never been declared. It shows the photo, its description, the way back to
  the album and the comments. A photo also follows its album's rules: no more access through its
  address when the album is in the trash or not yet published.

- **Last remnants of Bootstrap 3 and 4** (2026-09-23), which their class names did not give away:
  the slideshow structure, the close crosses that displayed a "×" on top of the icon,
  two accordions (FAQ, navigation links) still built the Bootstrap 4 way (the FAQ one opened, but
  without the component's look or arrow), fields that wrapped themselves in an empty group,
  the tooltip arrow left white under a dark tooltip, and two rules of the partners
  widget. `check-classes-bs4` now also reads the STRUCTURE of components and the styles
  written in views.

- **Error pages that weren't** (2026-09-23). In moderation, and in the management of
  roles, email templates, members and the slideshow, an address pointing to a removed item
  was supposed to answer "page not found": it called a function that does not exist, wrote a
  warning to the log, and the moderation page displayed empty. Found by teaching
  static analysis the framework's "magic" functions — which trimmed 455 occurrences from its
  list of exceptions.

- **The button in emails sent to members is readable** (2026-09-23): account validation, lost
  password and the other templates had a white button on light turquoise (contrast 2.3:1). The fix
  applies to the shipped templates and to those already installed, except those an administrator has
  customized.

- **Last display touch-ups** (2026-09-23): wiki links in the theme's readable tint; "success" buttons on
  dark themes; status of an application, whose box had no background (classes from an old administration
  theme); social networks on member cards; truncated titles in the administration; a visitor who follows
  the link to take part in an event gets "Unauthorized access" instead of a not-found page.

- **Text is readable everywhere, in light and dark mode alike** (2026-09-23). Links and buttons in
  the administration, alerts, dates and table headers of the themes, news categories,
  the marketplace page on light themes, the cookie banner's "Accept all" button,
  reputation and event-type badges: all now reach the minimum recommended
  contrast (4.5:1). In dark mode, the administration has its own status colors, and the
  "Site health" panel and the gallery's image upload area follow the theme.

- **The administration on a phone** (2026-09-23). The top bar wraps onto two lines — the
  full breadcrumb, then the buttons — instead of truncating the page title; small
  targets (home icon, counters) are large enough for a finger; the header of the forum
  categories no longer slides under its buttons.

- **The forum on a phone** (2026-09-23): in a topic, the author moves above the message instead
  of leaving it only 200 px of width, and a message's buttons no longer cover its date.

- **Translated plurals** (2026-09-23). 47 strings had lost their plural form in
  translation: a site in English displayed "3 topic", "5 image", "2 year". The forum
  categories counter, written in French in every language, is now translated.

- **The site documentation is up to date** (2026-09-23). The ten wiki pages shipped with the product
  and those of the demo were a week behind the guides; "Create a widget"
  still taught a Bootstrap 4 class. A check now compares them with the guides.

- **The forum reply form fits on a phone** (2026-09-23): the list of allowed attachment
  types, written without spaces, formed a single word too wide for the screen.

- **The whole site screened, in every theme, every mode and at every width** (2026-09-23).
  An automated check now renders every administration page in its theme and every public page in every
  public theme, light and dark, logged in as an administrator and as a visitor, at eight widths from 360
  to 2560 px, with and without content.
  This first pass fixed:
  - **broken icons** in the category lists of the gallery, games, teams and
    news, when a category had no icon;
  - the **country on profiles** in the demo, written out in full: the profile displayed
    no country and looked for a flag that could not be found;
  - **email template subjects**, which displayed `{{site_name}}` verbatim in the list;
  - **tables too wide for a phone** (bug tracker, downloads, wiki
    history, moderation, sessions, event participants, awards): they now scroll
    within their frame instead of widening the whole page;
  - **forum lists** on a phone: four columns shared 360 px and the last
    message was cut off; each entry now stacks — icon and title, then statistics and
    last message;
  - the **Nebula theme bar** on a phone, which overflowed the screen;
  - three files requested by pages without existing, and two lines written to the log on every
    update check as long as no version is published.

- **The "Edit" and "Delete" buttons are no longer staggered** (2026-09-22). In the
  two-column administration lists — news categories, gallery categories… — the second
  button wrapped onto the next line, offset under the first. They now
  stay side by side.

- **The monitoring "Storage" card displays correctly** (2026-09-22). The gauge was drawn as a full
  circle instead of the intended half-circle, with a raw number in its center, and the value and
  percentage, placed at the top, overlapped the stroke; they now sit in the hollow of the gauge. The
  "Server information" title on the same page wraps onto two lines when the column is narrow, instead of
  being cut off. In the "Email delivery" group, the "Email transport" line showed `[object Object]`
  instead of the method used.

- **A widget placed without settings is displayed with its default values** (2026-09-22). When a
  theme places a widget at installation, or an older layout restores it, it arrives
  without settings: it then wrote warnings to the log on every page displayed, and the
  navigation menu simply disappeared. It now receives the values that
  its form would have saved by default. Widgets already configured do not change.

- **The events page displays again** (2026-09-22). It rendered a "not found" error
  page as soon as it had events to spread over several pages — under a
  perfectly normal title, which made it hard to notice. The setting for the number of
  events per page was saved as text, and the code, made stricter the
  day before, rejected it. All paginated lists now accept this setting in both
  forms.

- **Poll votes are recorded** (2026-09-22). The voter read "Thanks for your
  vote!", and the vote was never counted: no option was recognized as belonging
  to the poll. A vote that targets no option of the poll is now rejected with a message saying so,
  instead of being welcomed.

- **The "Forum" widget finally shows topics and posts** (2026-09-22). Its two types, "Latest topics"
  and "Latest posts", selected no forum category at all, and so stayed empty on every site, however
  many messages there were.

- **Three other functions fell into the same trap as polls** (2026-09-22), found
  by the check written for this defect:
  - **opening an application that carries custom fields** crashed the page, on the
    applicant side as well as in the administration;
  - **a notification that links to a calendar event** crashed while
    building its link;
  - **the maximum depth of forum replies** was never applied: the calculation
    stopped at the first level.

- **Going back to page 1 of a list no longer leads to a not-found page** (2026-09-22). In the
  administration lists whose number of items per page can be adjusted, the "1" button
  built an address the site did not recognize.

- **The edit screen for a custom profile field opens** (2026-09-22). The screen crashed
  every time it was opened: it gave no label to its save button.

- **Two "Back" buttons led to a not-found page** (2026-09-22): from an
  image's page, to its album; from a game mode, to its game.

- **"Apply" no longer appears where the application would be refused** (2026-09-22): on a
  closed or full offer, and for anyone not allowed to apply. Likewise, an administrator
  who reads a staff-only conversation can now invite people to it and report a
  message in it, instead of being refused after seeing the button.

- **Navigation widget submenus open** (2026-09-22). A link that groups
  other links expanded nothing on click: it still carried the attribute of the old
  version of Bootstrap, which its own script no longer looked for.

- **The remaining form help tooltips show on hover** (2026-09-22). The previous day's fix only covered
  part of the forms: the (i) icon next to fields on the other administration screens, and label
  tooltips, still showed nothing — the same attribute defect, in both libraries that build them.

- **Accented menu labels are translated** (2026-09-22). A menu link saved
  from the administration — "Actualités", "Équipes" — is stored in encoded form, and its
  translation could no longer be found: it stayed in French in the five other languages, with
  a warning in the log on every page displayed.

- **Marketplace archives are reproducible** (2026-09-22). They carried the
  `.map` source maps, which the style compiler regenerates on each installation and which
  the repository ignores: two archives of the same addon, built on two machines, differed even though
  the code had not changed. Archives no longer carry these files.

- **The "Awards" widget can finally be installed by uploading an archive** (2026-09-22). It was the only one
  of the 61 distributable addons not to declare its dependency on the core; the installer, which requires it
  to recognize an addon, skipped it WITHOUT A WORD — no message, no trace.

- **The maintenance page is no longer blank** (2026-09-22). The two fields "Title" and
  "Content" ship EMPTY, so the page showed NOTHING other than the site name —
  even though the administration preview promised a title and a text. A default title and
  message, translated into all six languages, fill the gap; as soon as the fields
  are filled in, they are what is displayed.

- **Outline buttons have their border back** (2026-09-22). The themes redefined only the "primary"
  outline button variant, and their own rule erased the border of the others: the six other variants used
  by the product — 90 uses in the code — looked like plain links. The most visible was "Open the website",
  in the maintenance banner, which only had a border on hover.

- **Technical code is no longer pink in the administration** (2026-09-22). The theme only set
  the font of `<code>` snippets, which therefore kept Bootstrap's pink — a color
  foreign to every palette in the product. Very visible on the roles list.

- **The "Close" button of windows finally closes the window** (2026-09-22). In ALL the
  product's windows, the "Close" (or "Cancel") button at the bottom did nothing: you needed
  the cross in the top right corner. The cause: Bootstrap 5 renamed the attribute that triggers
  closing, and the core still set the old name; a browser ignores an unknown attribute
  **silently**. The button has also become a real button — it was rendered as a `<span>`,
  and so unreachable by keyboard. Two safeguards keep it from coming back: the code check rejects
  the old name, and a browser run, launched by hand, CLICKS the button.

- **The Nebula theme no longer shows its menu twice** (2026-09-22). Every fresh installation
  showed the site name twice and the menu twice: the theme draws its own bar, and
  the installation ALSO placed a title and a menu right below it. The menu now lives in
  the top bar, where it appears only once — and it is **configurable** from
  the administration, which was not the case before: the bar's links were hard-coded, so
  "Contact" was missing and a disabled module kept its link. Sites
  already installed are fixed by a migration, which only touches the header if it has remained
  as shipped.

- **Switching languages no longer lands on a page of code** (2026-09-22). The footer
  language selector — the one in **five themes**, including the default — sent the visitor to a
  page displaying `{"redirect":"/en/…"}` as raw text, instead of taking them to the translated page.
  You had to go back to get out of it.

  The cause: this selector is a real form, which nothing intercepted, and it targeted a
  technical address that always answers in JSON. The server now distinguishes a call from the
  "Choose my language" window, which expects JSON, from an ordinary form submission, which expects to be
  taken to the page. Found by following the gesture in a real browser, which no check had
  done until then.

- **Content written in a single language no longer sends the other languages to an error page**
  (2026-09-21). A news item written in French still offered the five other languages in its
  selector, and all five returned 404: measured on the demo, **60 dead addresses out of 72**.
  A foreign visitor's most natural gesture landed on an error. The version that exists is
  now served, with a banner saying so in the visitor's language and a link to the original.
  This applies to news, articles and albums (and their categories), as well as teams.

  Two inseparable precautions, without which the cure would cost more than the disease: the page
  now announces to search engines only the languages that **really exist**, and it designates the original
  as the canonical address — otherwise six addresses would be indexed for a single text.
  **In the administration, no fallback**: an empty version must show as empty, that is what one comes
  to fill in. `tools/check-langues-contenu.php` enforces all three properties, in continuous integration.

- **Continuous integration is green again** (2026-09-21); on the main branch, it had been red since
  June 10. Almost all the causes lay in the test tools and the CI configuration, not in the product. The
  exception: under PHP 8.3's built-in server, the site could miscompute its base address and redirect
  stylesheets and scripts; it now only derives it from a `SCRIPT_NAME` ending in `index.php`. A test
  suite that cannot run now fails instead of silently skipping itself, and most CI jobs have a time limit.

- **The installer logo did not display on a fresh installation** (2026-09-21): the
  path was relative to the `install/` folder, which the server does not serve under that address. The image
  is now embedded in the page.

- **Components that no longer opened since the switch to Bootstrap 5** (2026-09-21). Bootstrap 5
  prefixed all its data attributes with `bs-`. An attribute left under the old name causes
  no error: the component simply does nothing. These were silently dead:

  | What no longer worked | Where |
  |---|---|
  | **The FAQ accordion** did not open | `modules/faq` |
  | **An event's participant list** did not expand | `modules/events` |
  | **Help popovers on part of the forms** were empty | core — `neofrag/libraries/form.php` |
  | The forum profile popover, the one for deleting a navigation link | `modules/forum`, `widgets/navigation` |
  | Tooltip placement in the live editor, the carousel delay | `modules/live_editor`, `widgets/slider` |

- **Broken layouts, silent ones too** (2026-09-21). Bootstrap 5 renamed its
  directional utilities; a renamed class is no longer defined anywhere, and the element keeps
  its default layout without anything flagging it.

  - **Panel and modal footers did not align right.** The core built the class
    by concatenation (`'float-'.$align`), so no search for `float-right` could
    find it. The product's only compatibility shim covered just the administration theme's tables:
    nothing for the public themes. Same cause for table cell alignment, in both
    libraries.
  - **No rejected form field was marked**, on any theme. Both libraries
    set a legacy name — `has-error` (Bootstrap 3) and `has-danger` (a Bootstrap 4 pre-release)
    — that no served stylesheet defines. The message did display, but nothing indicated the field
    concerned. They are replaced by an in-house marker, defined once.
  - **The footer** carried `<div class="float-right">`: "Powered by NeoFrag Reborn" dropped onto a new
    line, on the left. The value goes back a long way — an Alpha 0.2.2 migration had changed it from
    `pull-right` to `float-right` at the time of Bootstrap 4, and the next step was never taken. A migration
    (`2026_09_20_bootstrap5_float`) fixes existing sites; the seed now ships `float-end`.
  - And the last dead classes in views: the debug bar on a phone, the gutter of
    event lists, the menu editor labels, the two bars of the recruitment vote
    — which came out in the **same color**, the chart no longer distinguishing favorable opinions from
    unfavorable ones.

- **An article's body could be wiped out on display** (2026-09-21). Building the table of contents
  replaced the text with the result of a processing step that can give up on a long article: the page
  then answered normally, with an **empty** article, and nothing in the logs.

- **A widget placed at the top or bottom of the page disappeared.** The *Nebula* theme
  offered the "Header" and "Footer" slots in the layout editor, but did not
  display them. The widget was indeed saved, and nothing appeared — without a message. The four
  other public themes were not affected.

- **The navigation widget offered two internal modules** (`live_editor` and `pages`) it should have
  hidden. The same defect made the administration statistics lose their module name, with no visible
  effect as long as two modules do not use the same statistic name.

- **The debug log grew without limit** when enabled. It is now capped:
  beyond 64 MB, it starts over while keeping the previous generation.

- **On an English site, "No" stayed in French ("Non").**

- **Five admin pages offered no way back** (2026-09-20): a member's detail page, the
  audit log, adding a group, the session list and the monitoring files. The
  breadcrumb only makes the module name clickable if the module declares that it has an admin
  home page; `user` and `monitoring` declared the opposite even though they have one. Once
  on these pages, you had to go through the side menu or the browser's back button.

- **Search engines received JSON instead of the sitemap.** Requested at the root,
  without a language prefix — the only way a robot requests them —, `/sitemap.xml` responded
  `{"redirect":"/fr/sitemap.xml"}` with a 200 status code, and `robots.txt` and `humans.txt` did
  the same. The redirect that adds the language prefix responded in JSON whenever the URL ended
  in `.txt`, `.xml` or `.json`. Since these files have no per-language version, they no longer
  go through this redirect: they are served directly, with their actual content type.

- **`/favicon.ico` returned 404.** Browsers request this URL whatever the page declares;
  it now leads to the favicon configured in the settings, or to the CMS's own.

- **Enabling two-factor authentication crashed.** When the recovery codes were being saved,
  an error interrupted the page: the account ended up marked "protected" without a single backup
  code. Found by a test written while tightening the typing of this library; fixed, and the test
  stays.

- **A "single attempt" limit never blocked.** The threshold was only checked starting from
  the second attempt. No effect on the shipped thresholds, but the moderation's report limit, which can
  be set as low as 1, then never blocked. The threshold now applies from the first attempt.

- **Some text stayed in French in the other languages, and the log kept filling up.** Three
  causes, all fixed: the title of a wiki page, an event type or a donation campaign
  went through translation as if it were interface text (it has no translation: it is
  content); text that was already translated went through translation again — "Espace membre" became "Member
  area", then "Member area" was looked up as a key and reported missing on every display; and
  thirty interface strings (the "Username or email address" field on the login form, panel titles
  in the games and recruitment admin, the profile's social networks…) had no translation in
  **any** language, because the check that verifies the six languages only looked at explicit
  translation calls, not at field and header titles translated further down the line. The check now
  sees them; the thirty strings are translated into English, German, Spanish, Italian and Portuguese.

- **Google Analytics now waits for your consent.** If an Analytics ID is
  configured, Google's script now only loads after a click on "Accept all" in the
  cookie banner, never before. In addition, the site's security policy blocked this script
  in every case: Analytics was not working, and nothing said so. It works now, and
  only with the visitor's consent.

- **Three admin features had stopped responding since June.** Sorting tables by
  clicking a column header, drag-and-drop to reorder categories, forums and
  subforums, and the Open/Closed buttons on the Maintenance and Registrations pages: their scripts
  still assumed jQuery was present, although it was removed from the CMS in June 2026, and stopped dead
  when the page loaded — with no message, the buttons simply remaining inert. All three have been
  rewritten without jQuery, are covered by browser tests, and an automated check now rejects
  any script that would call on jQuery again.

- **A malformed URL caused a server error.** A request such as `/fr/a:80` or `/:80`,
  the kind robots send, crashed the computation of the URL's extension and returned
  a 500 error to the visitor. The site now responds with an ordinary 404.

- **The contact form wrote "From:" with the visitor's address.** Sending a message from your
  server on behalf of `visiteur@example.org` is exactly what mail providers' anti-spoofing
  protections (SPF, DMARC) reject: the message ended up in spam or was refused.
  The sender is now the site's contact address, and the visitor's address is placed in
  "Reply-To", which amounts to the same thing in practice. Same fix for replies to an application,
  which could go out with the administrator's personal address.

- **The installer could save an invalid contact address.** When the site was installed
  by accessing it through its IP address, the contact address became `noreply@<IP>`, which
  mail providers reject — and no email went out anymore, without any visible error message. The address
  is now only derived from a real domain name.

- **An empty banner appeared on pages where a layout zone is empty.** In the
  Extend theme, every page other than the home page showed a wide colored band containing
  nothing, as well as an empty section above the content.

  The cause was not the theme: a zone that was declared but had no widget returned whitespace rather
  than nothing at all, so themes drew their frame around nothing. Fixed once for
  all themes. If you build a theme, you can write
  `if ($zone = $this->output->region('banner'))` (or `zone(n)`): the condition now tells the truth.

- **The Extend theme displayed its "Powered by NeoFrag Reborn" credit twice.** Its shipped
  layout placed a block in the footer repeating what the theme already writes itself. Sites
  already installed are fixed automatically on update; new ones will never have it.

- **Searching for a common word returned an error page.** A search such as "le" or "de"
  did display its results on screen, but the server responded **404** — which is what search
  engines, monitoring tools and browsers see, and which made a perfectly valid
  page look like one that had disappeared.

  Highlighting the matched words assumed that the searched word necessarily appeared
  in the displayed text. That is wrong twice over: the search examines **several fields** and only
  displays one — a forum topic titled "Salut tout le monde !" matches "le" through its
  title, not through the message shown below it — and the database compares **without regard to
  accents**, whereas the code did take them into account: "éléphant" did come up for the search
  "ele", without any word being highlighted in it.

  Both cases are handled: highlighting now recognizes the accented variants of a
  letter, as the database does, and when the word really is not in the displayed text,
  the excerpt simply starts at its beginning.

- **Automatic core updates could not work** — five defects, each one enough on its own: the address to
  check for new versions was set nowhere, so the button never appeared; a global switch, to be set in the
  configuration, blocked the operation; the download pointed at the original NeoFrag releases, which would
  have overwritten NeoFrag Reborn; the package stored its files in a subfolder, so an update would have
  **replaced nothing, without the slightest error**; and the archive was extracted through a PHP interface
  deprecated since PHP 8.0.

  The switch is replaced by four guarantees: the version source must be on an **allowlist of addresses**
  (the same as for the marketplace); the version file only gives a **file name**, never an address; the
  package's **SHA-256** checksum is verified **before** a single file is touched; every file in the archive
  is checked (no path leading outside the site, no symbolic link). The `config/` and `install/` folders
  are never rewritten if they already exist.

- **The marketplace catalog was unreachable from any site.** The server that distributes it returned
  `{"redirect":"/fr/…"}` **with a 200 status code** instead of the static file — a body perfectly valid
  as JSON, and perfectly wrong, hence a silent failure that made every site fall back on its local
  catalog. The cause was that server's configuration, now fixed: nothing to do on the sites. In the code,
  the core update now validates the shape of its manifests, no longer just whether they parse as JSON.

- **A theme's "Delete" button**, in "Themes & addons", only protected the administration theme, by its
  hard-coded name. It now follows the theme's declaration, and so also protects Nebula.

- **Core-to-optional couplings.** Seven modules queried the tables of optional modules: five core ones
  (`admin`, `moderation`, `settings`, `statistics`, `user`), plus `games` and `teams`. Two queries were
  unprotected: in `teams`, the existence guard for `recruits` was placed **after** the query it was
  supposed to protect, and in `games`, deleting a game queried the teams table without checking that it
  exists. The others were guarded but silent: they now carry an annotation verified by the CI. The
  package's only hard cycle (`games` ↔ `teams`) is broken.

- **Live Editor: changes appear without reloading the page.** Deleting a widget left it on screen and
  adding a row did not show it until the page was reloaded, even though the server had saved the change.
  The script expected a different response format from the one the server sends; the defect dated from
  the removal of jQuery.
- **Live Editor: dragging and dropping widgets works again.** With Columns mode on, an added widget stayed
  stuck at the bottom of its column and could not be moved above the page content. Rows and columns were
  not affected.
- **Live Editor: widgets without settings can be added again.** Ten widgets that have no settings screen
  (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`,
  `teams`) could not be added: adding them failed with a 404 error and no useful message.
- **A server error is no longer displayed as content.** When an action performed without reloading the
  page got an error from the server (a 404 page, for instance), some screens inserted that error page into
  the interface. The error is now treated as a failure.
- **Live Editor: the settings form always matches the chosen widget.** When switching widget or type
  quickly, the slowest response could display another widget's form. The outdated request is now
  canceled, and the form is no longer reloaded (nor the input in progress lost) when the selection has not
  changed.
- **The database port is now honored.** The CMS always connected on port 3306, whatever port was entered
  in the installation wizard: it could not be installed or run on a MySQL or MariaDB server listening
  elsewhere. The port is now saved in `config/db.php` (only if it differs from 3306) and used for every
  connection.
- **An activated theme is no longer empty.** Activating Forge, Granite, Blockcraft or Extend switched the
  theme without applying its default layout, which only applied through the "Reinstall to defaults"
  button: the site showed no widgets at all. Activation now applies the theme's default layout if it has
  none yet, never overwriting a customized layout.
- **Changing the default theme now also applies to visitors who had picked one.** The footer theme
  selector remembered a visitor's choice for a year, and that choice overrode the default theme: when the
  administrator changed theme, visitors, and the administrator too, stayed on the old one, with no way out
  from a theme without a selector. A choice made before the last change of default theme is now
  forgotten; a choice made after it is still respected.
- **The browser no longer keeps stale copies of pages.** Lacking any instruction, it could redisplay an
  old version of a page: a theme change, for instance, only showed after a forced reload (Ctrl+F5). The
  site's pages and responses now forbid this caching (`Cache-Control: no-store`); images, styles and
  scripts, served directly by the web server, are not affected.

### Security

- **A stored value can no longer inject tags into a form** (2026-09-23). The
  edit form placed a field's value in the page by escaping quotation marks the way
  a PHP string would — which has no effect in HTML. A value that had not gone through the form
  itself (import, demo data, another module) could therefore break out of its field and
  add code to the admin screen: a quote whose source was `"><img src=x>`
  demonstrated it. Text fields, text areas, lists, checkboxes and radio buttons now encode
  their value; the rendering of a normally entered value does not change. Notifications
  ("Quote updated", etc.) are also passed to the page's script in a form that can no longer
  break it.

- **The security policy now declares a rule for media** (`media-src`). It had none and fell back on the
  general rule (`default-src 'self'`). It now allows media from the site itself and media embedded in the
  page (`'self' data:`); a webradio stream's address is added **only if a stream is configured**: a site
  without webradio allows no additional outside origin.

- **Eleven security advisories closed on dependencies** (2026-09-20): `league/commonmark` goes from
  **2.8.2 to 2.10.1** (ten advisories, eight of them severe) and `phpseclib` from **3.0.52 to 3.0.57** (one medium-severity advisory).

  Nine of the ten commonmark advisories are **denials of service through crafted Markdown** — quadratic-time
  parsing, headings with colliding anchors, duplicate footnotes, adjacent attribute
  blocks, deeply nested XML output — and the tenth is an **XSS vulnerability**: the filter on
  `on*` attributes could be bypassed with a U+000C form feed. The library renders the Markdown of the wiki,
  articles and FAQ, written by members. Several of these advisories target extensions the product does
  not enable (footnotes, attributes, heading anchors, XML output); the update closes them all.

  Rendering does not change, and that is not just an impression: **twelve representative cases** — headings,
  table, task list, code block, injected HTML, `javascript:` link, blockquote, strikethrough text,
  autolink, nested lists, image, entities and footnote, deep nesting —
  rendered by both versions with the product's actual configuration produce the same HTML
  **byte for byte** (1,568 bytes, same hash).

  Note: Composer **now refuses to install** 2.8.2, precisely because of these ten
  advisories. The policy had to be disabled in a throwaway folder to carry out the comparison.

## [1.1.0] — 2026-08-23

### Security
- **Security review of the whole codebase**, and fixes for the serious flaws it found:
  - **Backups**: backup archives (a copy of the database and of the `config/` folder, which holds the passwords) can no
    longer be downloaded from a plain web address, under Apache or nginx; only the admin (Monitoring) hands them out.
    Their name now includes a random part that cannot be guessed. The log folder (`logs/`) is protected the same way.
  - **Secret tokens**: session IDs, form tokens and links sent by email (forgotten password, account validation) come
    from a secure random generator; they used to be predictable. These links expire after one hour, and a new request
    cancels the previous one.
  - **Sign-in**: two-factor authentication (2FA) and bans are checked whichever way you sign in — password, Discord,
    Google or GitHub account, "forgotten password" link — not just with the password.
  - **Links in emails**: the site address used in links sent by email, on return from a Discord, Google or GitHub
    sign-in and on return from a Stripe payment is the one saved at installation (`config/url.php`), no longer the one
    the request claims: an attacker can no longer get a link sent that leads to their own site.
  - **Admin pages**: a rigged attachment name (in a moderation report) or a fake IP address (`X-Real-IP` header, in the
    session history) could inject code into them; they are now checked and neutralized. A game server's welcome message,
    supplied by an outside service, is cleaned before display.
  - **Admin actions**: deleting, enabling or disabling, closing, approving, restoring, purging… now require a security
    token in the 23 modules that lacked one. They were plain links or token-less forms: a rigged link clicked by a
    logged-in administrator was enough to trigger the action.
  - **Tools**: scripts in the `tools/` folder only run from the command line, never from a web address; on every change,
    continuous integration checks that no PHP dependency has a known flaw (`composer audit`).
  - **Buttons silenced by the security policy (CSP)**: the site's strict CSP blocks actions written into the HTML
    (`onclick="…"`), so these buttons did nothing: cookie banner, number of rows in admin tables, trash and revision
    confirmations, attached file name in the messaging, forum mention bulk actions, click-to-select fields, the delete
    window's button. They are wired up differently and also work in content loaded without a page reload. The
    rows-per-page selector also still called jQuery, which had been removed.
- **Strict content security policy (CSP)**: served by the site with a token unique to each page, it refuses scripts
  written into the page without that token (`unsafe-inline`), code built on the fly (`unsafe-eval`) and embedded objects
  (`object-src 'none'`); scripts now only come from the site and a short list of addresses, as TinyMCE and CodeMirror
  are now hosted by the site.
- **Session renewed at sign-in**: the session ID changes when you sign in; an ID planted before sign-in no longer gives
  access to the opened session.
- **Secrets encrypted in the database**: the SMTP password and the two-factor authentication (2FA) key are encrypted
  (AES-256-GCM); a copy of the database no longer gives them away in plain text. An existing value is encrypted the next
  time it is saved.
- **Webhooks**: a webhook can no longer target the server's internal network (private, local or reserved addresses
  refused, redirects not followed).

### Added
- **CSS and JavaScript files always up to date**: each file is requested with its modification date (`?v=…`), so the
  browser reloads it as soon as it changes — including a file overridden by a theme or uploaded by hand — without having
  to bump `nf_version_css` (still used when the file cannot be found).
- **Marketplace**: the "Updates" window of the *Themes & Addons* page tells you when a new NeoFrag Reborn version is
  available; updating the core itself remains manual.
- **Addon updates**: the same window compares installed addons with the marketplace catalog and installs new versions,
  along with their database migrations.
- **Installer**: new look — NeoFrag Reborn logo, "Unofficial fork of NeoFrag" banner, credit to Michaël BILCOT and
  Jérémy VALENTIN (LGPLv3 license) in the footer, Inter and Space Grotesk fonts, teal colors.
- **Files**: a file manager (folder tree, uploads, read permissions per file or per folder), ported from HiddenBlob's
  HiddenCMS (LGPLv3). Ships with the core.
- **Custom emojis** (Emojis module, ships with the core): admins add named images, which `:name:` displays in forum
  posts and signatures, news, pages, events and recruitment, among others.
- **Newsletter**: scheduled sending at the chosen date, in small batches handled by the site's scheduled task (cron)
  instead of all at once; open tracking (open rate per campaign, measured by an invisible image); reusable email
  templates; choice of recipients: all subscribers, members only, or a group.
- **Events**: an event can repeat daily, weekly or monthly, for the chosen number of times — each date becomes an event
  in its own right. Participants, except those who declined, get an on-site reminder (notification bell) before the
  event, 24 hours before by default; reminders go through the site's scheduled task (cron).
- **Emoji reactions**: six reactions to choose from (👍❤️😂😮😢😡) instead of a single heart, as on Discord or Facebook, each
  with its own count; existing "likes" become ❤️.
- **"Live status" widget** (formerly the Twitch widget): follows several channels at once, on Twitch and YouTube (Twitch
  credentials and a YouTube API key required), with game, viewers, title, thumbnail and embedded player.
- **Composed pages**: below its text, a page can show blocks from other modules (latest news, news from a category,
  latest articles, popular downloads), placed in the desired order and configured one by one when editing the page. The
  shortcode also accepts settings, for example `[block:news.latest count=2]`.
- **Extend theme**, by Chewbaka, ported to NeoFrag Reborn (Bootstrap 5, day and night modes), under the CC BY-NC-SA 4.0
  license; available from the marketplace.
- **Instant search**: the search widget suggests results from the second letter typed (news, forum, pages).
- **Sorting admin lists**: ten lists (news, articles, classifieds, downloads, FAQ, guestbook, links, media, surveys,
  wiki) can be sorted by the chosen criterion.
- **Animated avatars**: an animated GIF uploaded as an avatar stays animated — always if the server has Imagick,
  otherwise as long as it is no larger than 250 × 250 pixels.
- **Permissions**: the permission grid opens in a window, without leaving the page.
- **Webmaster file manager** (Monitoring, "Manage files" button): browse the site's files, edit them in an editor
  (CodeMirror), create, rename or delete them. The `config/`, `logs/` and `backups/` folders stay out of reach; every
  save keeps a `.nfbak` copy and goes into the audit log. Any write requires the **webmaster password**, separate from
  the account password and kept out of the database (set at installation or in Monitoring, "Webmaster security" card);
  once set, it is also required again to delete or purge backups and to change the scheduled-task key.
- **Release notes from a single source**: `tools/changelog-section.php` extracts one version of this changelog
  (`CHANGELOG.md`), as Markdown or HTML; GitHub release notes are drawn from it, so their texts can no longer diverge.

### Changed
- **"Everything included" installer**: the wizard now has four steps (Requirements → Database → Administrator → Done).
  All shipped modules, widgets and themes are installed and enabled at the "Database" step, and the home page shows the
  news as soon as installation is over; the "Modules" step and its profiles are gone.
- **Bootstrap 4.6.2 → 5.3.8, and jQuery is no longer loaded**: the site's JavaScript relies on a small in-house helper
  (`window.NF`). Nine jQuery or Bootstrap 4 plugins are replaced (notifications → Bootstrap 5 toasts, selectize → Tom
  Select, date picker → flatpickr, FullCalendar 3 → 6, color and icon pickers, tree view and gauges → in-house code,
  scrollbars → native scrolling), and jQuery UI by SortableJS. Four overlooked scripts still called jQuery: they are
  rewritten in 1.2.0.
- **Dark mode**: tables, cards, drop-down menus and the text editor take on the theme's dark colors instead of staying
  white, in every theme.
- **Widget settings and page layouts**: saved as JSON instead of PHP's serialization format, which closes a
  code-injection path; existing settings are still read.
- **Statistics charts**: Chart.js (MIT license) replaces Highstock, a commercial library that prevented free
  redistribution of the CMS.

### Fixed
- **MySQL 8**: starting a topic or replying on the forum, splitting or merging topics, starting a conversation in the
  messaging, reordering Slider images… failed with a fatal error on MySQL 8, which rejected the way the site opened its
  transactions. They now go through the dedicated mysqli functions.
- **Emojis**: an emoji — or any 4-byte character — in a text caused a database error. The connection and all tables
  switch to `utf8mb4` (`utf8mb4_unicode_ci` collation, supported by MySQL 8 and MariaDB 10 alike; the MariaDB 11-only
  collations, which made installation fail elsewhere, are gone).
- **`where('col', [])`** generated an empty condition (→ `DELETE`/`UPDATE` on the whole table): it now
  produces `1 = 0` (empty set).
- **Dates after 2038**: a newsletter's scheduled sending, the scheduled publishing of a news item, article, gallery or
  page, and an event's dates could not go past January 19, 2038: beyond that, MariaDB refused to save them, without any
  message. These dates now go up to the year 9999; existing installations are updated by migration.
- **`/user/login` and `/user/registration` returned "page not found"**: the themes' "Login" and "Registration" buttons
  lead there when JavaScript is off or the link is opened in a new tab. These addresses now open the login or
  registration window.
- **Nebula**: the "Registration" button stayed visible when registrations were closed, and led to a "page not found". It
  is now hidden in that case.
- **Discord widget**: it gives the real cause of a failure (widget disabled on the server, unknown ID, network) instead
  of a generic message, shows the server's real logo (read from the public invite) and counts online members correctly —
  the list Discord provides stops at 100, which skewed the count for large servers.
- **TeamSpeak widget, tree view**: it crashed under PHP 8 and fell back to the simple card. The tree of channels and
  clients is now drawn by the widget itself, with Font Awesome icons (no icon pack needed); a network error shows a
  generic message, without revealing the server's address or port.
- **Discord, Twitch and Game server widgets**: they showed "unreachable" even when the service answered (the response
  was decoded twice). The site's requests also send a browser identifier (User-Agent): without it, services behind
  Cloudflare answered with a refusal (error 403).
- **Backups and Monitoring**: creating a backup crashed on PHP-FPM servers, and failed under nginx or Plesk, which
  answered "page not found" to `.json` addresses; deleting or downloading a backup led to "page not found"; the
  Monitoring tree showed an error on first refresh. All fixed.
- **Marketplace unreachable**: default URL switched to **non-www** (`https://neofrag-reborn.xyz/marketplace`).
- **Multiple file upload**: the first file of a multiple upload made the upload fail ("page not found") — which is what
  blocked uploads in the Files module. For developers: `uploaded_file()` mistook index `0` for a missing index.
- **Webhooks**: a member registering (`user.registered`), a new comment (`comment.created`) and a new forum topic
  (`forum.topic`) finally trigger their webhook; these events were offered but never sent.

### Documentation
- **Documentation** (wiki guides): for developers, forms (`form()` and `form2()`) and validating a widget's settings;
  for administrators, installation troubleshooting, an example of creating a role, and what to do when the marketplace
  is unreachable. A note explains how to configure nginx or Plesk, which answered "page not found" to `.json` addresses.

---

## [1.0.0] — 2026-06-06 · Reborn foundation (based on Alpha 0.2.4)

First cycle of the fork: modernization of the foundation, security hardening and a broad wave of
features.

### Added

**Platform & tooling**
- **Database migrations**, versioned (`tools/migrate.php`, with a `baseline` command to take over an existing database),
  and first automated tests (PHPUnit).
- **New web installer**, replacing the original NeoFrag one: a five-step wizard
  (Requirements → Database → Modules → Administrator → Done). The "Modules" step offers three site profiles (Community,
  Gaming / eSport, Simple site) and can add addons from the marketplace.
- **Remote marketplace** (catalog and archives served by neofrag-reborn.xyz): the installer ("Modules" step) and the
  admin ("Marketplace" button on the *Themes & Addons* page) add, in one click, the official catalog's addons that do
  not ship with the site. Security: HTTPS required, SHA-256 checksum verified, archive checked entry by entry (no path
  leaving the addon's folder, no symbolic link), fixed catalog address, bounded sizes and timeouts.
- **Packaging tools**: `package-addons` builds the marketplace catalog, `build-release` the archives to upload by FTP.
- **Maintenance script** (`tools/maintenance.php`, to be run by a scheduled task): empties trash items older than 30
  days (configurable) and deletes never-confirmed accounts after 7 days, when registration validation is on — never an
  administrator.

**Themes & interface**
- **Four themes created for NeoFrag Reborn**: Nebula, designed for community sites, Forge, Blockcraft and Granite.
  Shared color variables (`--nf-*`) dress modules and widgets in each theme's colors.
- **Complete redesign of the admin**: new look, sidebar, command palette (Ctrl+K), light and dark modes.
- **Theme** and **language** selectors in the footer (visitors included).
- **Menus**: multi-level menu builder, usable in the Navigation widget ("Managed menu" option).

**Content**
- **News and articles**: scheduled publishing — content goes live at the chosen time, and notifications, webhooks and
  points are only sent at that moment, no longer on save (an address to be called by a scheduled task, protected by a
  key); bulk actions; search, filters and pagination in the admin; a dedicated page to read a news item. Articles get a
  featured image, as news items already had, and the news view counter, which never went up, finally counts.
- **Scheduled publishing** on pages, galleries and events too (publication date separate from the date the event takes place, for events).
- **Wiki**: serves as on-site documentation (table of contents as cards, reliable Markdown rendering); search and
  pagination; visual line-by-line comparison of two revisions of a page.
- **Media**: editable title and description for each file, search, filter by type and pagination. Search, filters and
  pagination also in the admin of Downloads, the Link directory, FAQ, Surveys and the Guestbook.
- **RSS 2.0** feed (news + articles), social **share** buttons, **SEO** (meta description, canonical, Open Graph, Twitter Card, per-page meta).

**Community & engagement**
- On-site **notification center** (bell and unread count), with **subscriptions** (follow a piece of content or a
  category). Notifications cover comments, forum replies, @username mentions, private messages and event invitations.
- "Like" **reactions** on news, articles, comments and forum posts; content **revisions** (history and restore); "Latest
  comments" widget.
- Shared **trash**: deleted news, articles, galleries, comments and forum posts go there and can be restored.
- **Forum**: category reserved for VIP members, image per category.

**Gaming**
- **Events**: full management of opponents (list, edit, delete; the original NeoFrag only let you add one from a match),
  live countdown, invitation notification.

**Monetization & gamification**
- Karma/reputation → **points** (configurable scale) → **shop** (payment in points) → **VIP** status.
- **Stripe** (buying points and VIP packs), **ad management** (hidden for VIP members), **donations** (module and widget
  ported from HiddenBlob's "Donation v3", after majiid; LGPLv3).

**Links between modules**
- **Statistics** extended to 19 modules (the original NeoFrag covered 3: comments, forum, members); **activity wall** on
  the profile, gathering a member's activity across all modules; **"To handle"** dashboard in the admin; **page
  blocks**: the `[block:…]` shortcode inserts a module block into a page (news, articles, downloads).

**Webhooks & audit**
- Signed (HMAC) outgoing **webhooks**, managed from the admin. The **audit log** also records addon installs and
  uninstalls, theme or module activation, and settings saves.

**Account administration**
- **Members**: CSV or JSON member export (GDPR); moderators with the right permission adjust moderation settings from
  their member area. Editing and deleting a member from the admin, already in the original NeoFrag, are wired back to
  the new member list.

### Changed
- Versioning normalized to **pure SemVer** in addon dependencies (removal of the "Alpha" labels).
- **Pages** is now part of the core; **News**, **Forum** and **Galleries** can be uninstalled.
- **Rich text editor**: it finally works — the HTML it produces is cleaned instead of being shown as text.

### Removed
- The original NeoFrag's two themes, "Thème par défaut" (`default`) and Azuro, replaced by the NeoFrag Reborn themes.
- The original NeoFrag's BBCode editor (WysiBB) and BBCode conversion: the rich text editor replaces it, and text
  written in BBCode is now shown as is.

### Fixed
- **Ten defects fixed**, including: events without participants or matches did not appear; editing or deleting a game
  mode failed; deleting a recruitment offer failed; a partner's link led to "page not found" (it now leads to their
  site); the author of a report was never shown, even to authorized moderators; the Galleries and Forum widgets checked
  their settings or permissions wrongly. View counters now count once per visit and ignore bots.
- Scheduled content is no longer visible through its direct address before its time. Buttons that did nothing now work:
  statistics periods, enabling email templates, social networks (all six are shown), "Add" buttons for news, pages and
  galleries.
- **Themes**: button contrast raised to WCAG AA, login fields joined to their icons, alignment fixes.
- **Small and large screens** (checked from 390 to 2,560 pixels wide): in the admin, sliding side menu and full-width
  content on phones; member profile; compact account bar in Nebula, Forge, Blockcraft and Granite.
- The Members, Statistics and Live Editor admin pages showed a 500 error; some counters were stuck at 1 (moderation
  badge, newsletter subscribers, media statistics), and an empty Downloads, FAQ or links category could not be deleted.
  (For developers: strict typing errors in `timetostr()` and helpers; `(int)` on `row(FALSE)` of a `COUNT`/`SUM` was
  always 1.)

### Security
- HTML sent by members is cleaned server-side (HTMLPurifier) against code injection; an uploaded file is checked by its
  actual content, not just its extension; HSTS enabled (browsers no longer fall back to HTTP).
- **Anti-CSRF tokens** compared in constant time. **IP address**: rate limiting and the IP ban list no longer trust
  spoofable headers (`X-Forwarded-For`…), unless a trusted proxy is declared. **Permissions**: permission checks added
  where they were missing (Guestbook, Games, Galleries, Events, Statistics).
- Old upgrade scripts can no longer recreate arbitrary PHP objects when reading settings back (`unserialize` restricted
  by `allowed_classes`).
- **Session fingerprint** against session hijacking: a logged-in member is logged out if their browser (user-agent)
  changes markedly during the session — a mere browser update is not enough. The scheduled-publishing address is
  protected by a key compared in constant time.
