# NeoFrag Reborn documentation

Welcome to the public documentation of **NeoFrag Reborn**, the free CMS for communities, from gaming to
associations: build the site of your team, your guild, your club or your association without writing a line of
code, and extend it whenever you feel like it.

> NeoFrag Reborn is the community continuation of **NeoFrag**, originally created by **Michaël BILCOT** &
> **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). An open source project under the **LGPLv3** licence. These
> guides are translated from the French guides, which are **checked against the code**; they are also published in
> the wiki of [neofrag-reborn.xyz](https://neofrag-reborn.xyz).

## For users

| Guide | What you learn |
|---|---|
| [Installation](installation.md) | Putting NeoFrag Reborn online: requirements, the five-step installer and its profiles, the command line, the first settings |
| [Getting started](premiers-pas.md) | The ten steps that turn a fresh installation into a site ready to open: name, theme, home page, menu, forum, members, backups |
| [Concepts](concepts.md) | How the CMS is organised: modules, widgets, themes, zones and regions, layouts, addons |
| [The administration panel](admin.md) | Running your site: content, members, permissions, settings, monitoring |
| [The marketplace](marketplace.md) | Finding and installing addons, updating addons and the core |
| [A site in several languages](multilingue.md) | What translates by itself, what you translate, what a visitor sees when a translation is missing |
| [The Discord bot](bot-discord.md) | Linking your site to your Discord server: roles and nicknames, forum ↔ Forum channel, all set from the administration — and writing a bot feature |

## For developers

| Guide | What you learn |
|---|---|
| [The framework](framework.md) | Service locator, routing, database, forms, tables, translations, the front end without jQuery under a strict CSP, tests and checks |
| [Create a module](create-a-module.md) | A complete feature: declarations, routes, checker, data and migrations, dependencies, hubs, permissions, administration |
| [Create a widget](create-a-widget.md) | A reusable block: settings with fallback values, shared CSS vocabulary, vanilla JavaScript |
| [Create a theme](create-a-theme.md) | Zones and regions, skeleton, default layouts, the complete `--nf-*` vocabulary, adjustable colours, thumbnail, theme migrations |
| [The REST API](api.md) | Letting a program talk to the site — a bot, an integration: access keys, rights, addresses, errors, rate limit |

## In short

NeoFrag Reborn installs on ordinary **PHP 8.2+ / MySQL or MariaDB** hosting (shared hosting included), **à la
carte**: a site profile at installation, then modules to enable or add. Once in place, you build your site with the
mouse: you place **widgets** in the **regions** of your **theme** with the Live Editor, and you download extras
from the **marketplace**.

Everything can be **overridden without forking** (a three-level override system), and every extension is a simple
folder of PHP files that you can version and share.

Other languages: [Français](../README.md) · [Deutsch](../de/README.md) · [Español](../es/README.md) · [Italiano](../it/README.md) · [Português](../pt/README.md)
