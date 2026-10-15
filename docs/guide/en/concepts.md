# Concepts

Understanding these five notions is enough to master NeoFrag Reborn.

## Modules

A **module** is a complete feature: the forum, news, the gallery, members, the shop… Each module brings its
**public pages**, its **administration screens**, its **data** (tables) and its **permissions**.

The package ships **63 modules**, but you don't install them all: at installation, a **site profile** —
*Full*, *Gaming / eSport*, *Community*, *Association / club* or *Core only* — pre-ticks what fits, and you
can untick module by module. What a module needs is added automatically (honours need teams). **Core**
modules (accounts, permissions, settings, pages, tools…) are always there and cannot be uninstalled.

Then, from **Administration → Themes & Addons**, you install, enable, disable or uninstall each module. A
disabled module disappears from the site but keeps its data. The addresses of a missing module answer with a
**clean 404**, never with an error.

## Widgets

A **widget** is a small reusable block: the navigation menu, the member area, the latest comments, a
countdown, a Twitch player… The same widget can be placed several times, in different places, with different
settings. The package ships **42 widgets**.

Widgets are placed in the theme's **regions** with the **Live Editor**: a four-step wizard (widget, type,
title, configuration), and drag and drop to move them.

## Themes

A **theme** gives the site its look: colours, typefaces, layout, navigation, footer, and **light or dark
mode**. It declares **zones**, gives them stable **region names** (`header`, `content`, `footer`…), and sets
its default **layouts**.

The package ships **Nebula**, a general-purpose community theme (navy and turquoise, glassmorphism), made
for a team, a guild, a club or an association. Six other themes — **Granite**, **Forge**, **Blockcraft**,
**Extend**, **Chronique**, **Pulse** — are in the package and are installed from **Administration → Themes &
Addons**; an installation that lacks them finds them in the [marketplace](marketplace.md). The active theme
is chosen in **Administration → Themes & Addons**; if several public themes are installed, visitors can
switch with the selector in the footer.

Every theme speaks the **same colour vocabulary** (`--nf-accent`, `--nf-surface`…): a widget automatically
takes on the active theme's look.

## Zones, regions and layouts

A theme splits the page into **zones**: usually *Header*, *Before content*, *Content*, *After content*,
*Footer*, each known by a **region name** that the templates use.

A **layout** describes, for a given page (or a page pattern), which widgets occupy which zone and in which
grid. Patterns go from the most general to the most specific:

| Pattern | Applies to |
|---|---|
| `*` | every page |
| `/` | the home page only |
| `forum/*` | every forum page |

The most specific pattern wins: a `*` layout puts the content on eight columns plus a side column, a
`marketplace*` layout can switch the same page to full width.

You change layouts with the mouse in the **Live Editor**. A page can also carry **blocks**: ordered,
configured module instances, inserted with the `[block:…]` shortcode.

## Addons and the marketplace

**Addon** is the generic word for anything that installs: modules, widgets, themes, authentication
connectors (Discord, GitHub, Google) and language packs (six languages shipped).

The **[marketplace](marketplace.md)** is the project's catalogue: it **detects updates** to installed addons
and lets you **add** those that are not in the package, with a SHA-256 integrity check. The **CMS core**
updates in one click from **Monitoring**, with a backup before writing and an automatic rollback if it fails.

Technically, an addon is just a **folder of PHP files**: you can version it, share it and reinstall it on any
NeoFrag Reborn site. That is what makes the CMS extensible — see the developer guides.

## In practice: building a community

For a gaming team: at installation, choose the **Gaming / eSport** profile (forum, teams, events,
recruitment, honours…) and the Nebula theme is in place; then, with the Live Editor, place the
**navigation** widget in the `header` region and a content widget (latest topics, upcoming matches…) in the
home page's side column. Everything is set in the administration, **without a line of code**.

For an association or a club: choose the **Association / club** profile — news, forum, galleries, calendar,
collectives, donations and membership fees, newsletter, wiki and FAQ, without the esport kit. Members, pages
and the contact form are always there; teams, if you need them, are added in one click.

## Overriding without forking

Any shipped file (view, class, asset) can be **overridden** without changing the original code. The
framework resolves in this order (the first one found wins):

1. `overrides/{type}/{file}` — global override
2. `themes/{active theme}/overrides/{type}/{file}` — per-theme override
3. the shipped original

This way you adapt a module or a widget to your site without ever touching the core, and without breaking
updates.

To go further: [Getting started](premiers-pas.md) and [A site in several languages](multilingue.md).
