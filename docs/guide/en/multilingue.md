# A site in several languages

NeoFrag Reborn speaks six languages: French, English, German, Spanish, Italian and Portuguese. This guide explains
what translates by itself, what you translate yourself, and what a visitor sees when a translation is missing.

## Two kinds of text

- **The interface** — buttons, administration menus, messages, e-mails: it arrives translated with the
  **language packs** (addons, shipped with the product). Nothing to do.
- **Your content** — your news, your pages, your categories, your menu, your widget titles, your wiki pages,
  your donation campaigns…: you write it, and you write it **language by language**.

## Writing in another language

An edit screen writes in **the administration's language**, and says so under the field: “Written for English”.
To translate, switch language — the site's language selector, or the address itself: `/de/admin/…` instead of
`/en/admin/…` — and open the same content again: its fields are empty where nothing has been written in that
language yet. Save: the other languages keep their text.

What has no language stays shared by all of them: a date, an image, a price, a display order, an author. You
enter it only once.

## What the visitor sees

The visitor reads the site in the language of the address (`/de/…`), which they choose with the theme's language
selector. For each piece of content, they see **the best translation that exists**: the one in their language,
otherwise French, otherwise the first language written. Content written in a single language therefore stays
visible everywhere: it is simply not translated.

The administration shows what is left to translate: lists flag content with no text in the screen's language,
and the translation check records the interface texts missing in a language
([The administration panel](admin.md)).

## What does not translate

- What **members** write — a forum topic, a message, a comment, a classified ad — stays in the language it was
  written in.
- **Custom emojis** keep their name everywhere: it is a code, not a text.

## Search engines

Each language has its sitemap, and each page points to its other languages. Content that has no language of its
own — a forum topic, or a page whose text is the same in every language — declares itself only in the site's
first language: six copies of the same text, each one “official”, would mean none of them is kept. The details:
[Search engines](admin.md#search-engines).

## For developers

A module that stores translatable content keeps it in a `<table>_lang` table (one row per language), reads it
with `join_lang()` and writes it with `ecrire_lang()`: [The framework](framework.md).
