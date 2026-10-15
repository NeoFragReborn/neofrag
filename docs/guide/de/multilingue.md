# Eine Seite in mehreren Sprachen

NeoFrag Reborn spricht sechs Sprachen: Französisch, Englisch, Deutsch, Spanisch, Italienisch und Portugiesisch. Dieser
Leitfaden erklärt, was sich von selbst übersetzt, was du selbst übersetzt und was ein Besucher sieht, wenn eine
Übersetzung fehlt.

## Zwei Arten von Text

- **Die Oberfläche** — Knöpfe, Menüs der Verwaltung, Meldungen, E-Mails: Sie kommt übersetzt mit den
  **Sprachpaketen** (Addons, mit dem Produkt geliefert). Nichts zu tun.
- **Dein Inhalt** — deine News, deine Seiten, deine Kategorien, dein Menü, die Titel deiner Widgets, die Seiten deines
  Wikis, deine Spendenkampagnen…: Den schreibst du, und zwar **Sprache für Sprache**.

## In einer anderen Sprache schreiben

Ein Bearbeitungsbildschirm schreibt in **der Sprache der Verwaltung** und sagt es unter dem Feld: „Für
Deutsch geschrieben“. Zum Übersetzen wechselst du die Sprache — der Sprachwähler der Seite oder die Adresse selbst: `/en/admin/…`
statt `/de/admin/…` — und öffnest denselben Inhalt erneut: Seine Felder sind leer, wo in dieser Sprache noch nichts
geschrieben ist. Speichere: Die anderen Sprachen behalten ihren Text.

Was keine Sprache hat, bleibt allen gemeinsam: ein Datum, ein Bild, ein Preis, eine Anzeigereihenfolge, ein Autor. Du
gibst es nur einmal ein.

## Was der Besucher sieht

Der Besucher liest die Seite in der Sprache der Adresse (`/de/…`), die er mit dem Sprachwähler des Themes wählt. Für
jeden Inhalt sieht er **die beste vorhandene Übersetzung**: die in seiner Sprache, sonst die französische, sonst die
zuerst geschriebene. Ein in nur einer Sprache geschriebener Inhalt bleibt also überall sichtbar: Er ist einfach nicht
übersetzt.

Die Verwaltung zeigt, was noch zu übersetzen ist: Listen markieren Inhalte ohne Text in der Sprache des Bildschirms, und
die Übersetzungsprüfung notiert die Texte der Oberfläche, die in einer Sprache fehlen
([Das Verwaltungspanel](admin.md)).

## Was sich nicht übersetzt

- Was **die Mitglieder** schreiben — ein Forenthema, eine Nachricht, ein Kommentar, eine Kleinanzeige — bleibt in der
  Sprache, in der es geschrieben wurde.
- **Eigene Emojis** behalten überall ihren Namen: Es ist ein Code, kein Text.

## Die Suchmaschinen

Jede Sprache hat ihre Sitemap, und jede Seite verweist auf ihre anderen Sprachen. Ein Inhalt ohne eigene Sprache — ein
Forenthema oder eine Seite, deren Text in allen Sprachen derselbe ist — gibt sich nur in der ersten Sprache der Seite
an: Sechs Kopien desselben Textes, jede „offiziell“, würden dazu führen, dass keine behalten wird. Die Details:
[Suchmaschinen](admin.md#suchmaschinen).

## Für Entwickler

Ein Modul, das übersetzbare Inhalte speichert, legt sie in einer Tabelle `<table>_lang` ab (eine Zeile pro Sprache),
liest sie mit `join_lang()` und schreibt sie mit `ecrire_lang()`: [Das Framework](framework.md).
