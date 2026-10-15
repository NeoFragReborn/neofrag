# Konzepte

Diese fünf Begriffe zu verstehen genügt, um NeoFrag Reborn zu beherrschen.

## Module

Ein **Modul** ist eine vollständige Funktion: das Forum, die News, die Galerie, die Mitglieder, der Shop… Jedes Modul
bringt seine **öffentlichen Seiten**, seine **Verwaltungsbildschirme**, seine **Daten** (Tabellen) und seine
**Berechtigungen** mit.

Das Paket liefert **63 Module**, aber du installierst nicht alle: Bei der Installation hakt ein **Profil der Seite** —
*Vollständig*, *Gaming / eSport*, *Community*, *Verein / Club* oder *Nur der Kern* — vor, was passt, und du kannst
Modul für Modul abwählen. Was ein Modul braucht, wird automatisch hinzugefügt (die Auszeichnungen brauchen die Teams).
Die Module des **Kerns** (Konten, Berechtigungen, Einstellungen, Seiten, Werkzeuge…) sind immer da und lassen sich
nicht deinstallieren.

Danach installierst, aktivierst, deaktivierst oder deinstallierst du unter **Verwaltung → Themes & Addons** jedes
Modul. Ein deaktiviertes Modul verschwindet von der Seite, behält aber seine Daten. Die Adressen eines fehlenden
Moduls antworten mit einem **sauberen 404**, nie mit einem Fehler.

## Widgets

Ein **Widget** ist ein kleiner wiederverwendbarer Block: das Navigationsmenü, der Mitgliederbereich, die letzten
Kommentare, ein Countdown, ein Twitch-Player… Dasselbe Widget kann mehrmals platziert werden, an verschiedenen
Stellen, mit verschiedenen Einstellungen. Das Paket liefert **42 Widgets**.

Widgets werden mit dem **Live-Editor** in die **Regionen** des Themes gesetzt: ein Assistent in vier Schritten
(Widget, Typ, Titel, Konfiguration), und Drag & Drop, um sie zu verschieben.

## Themes

Ein **Theme** gibt der Seite ihr Aussehen: Farben, Schriften, Layout, Navigation, Fußzeile und den **hellen oder
dunklen Modus**. Es deklariert **Zonen**, gibt ihnen stabile **Regionsnamen** (`header`, `content`, `footer`…) und
legt seine Standard-**Layouts** an.

Das Paket liefert **Nebula**, ein allgemeines Community-Theme (Marineblau und Türkis, Glassmorphism), gedacht für ein
Team, eine Gilde, einen Club oder einen Verein. Sechs weitere Themes — **Granite**, **Forge**, **Blockcraft**,
**Extend**, **Chronique**, **Pulse** — sind im Paket und werden unter **Verwaltung → Themes & Addons** installiert;
eine Installation ohne sie findet sie im [Marketplace](marketplace.md). Das aktive Theme wird unter **Verwaltung →
Themes & Addons** gewählt; sind mehrere öffentliche Themes installiert, können die Besucher mit dem Wähler in der
Fußzeile wechseln.

Alle Themes sprechen **dasselbe Farbvokabular** (`--nf-accent`, `--nf-surface`…): Ein Widget übernimmt automatisch
das Aussehen des aktiven Themes.

## Zonen, Regionen und Layouts

Ein Theme teilt die Seite in **Zonen**: meist *Header*, *Vor dem Inhalt*, *Inhalt*, *Nach dem Inhalt*, *Footer*,
jede unter einem **Regionsnamen** bekannt, den die Templates verwenden.

Ein **Layout** beschreibt für eine bestimmte Seite (oder ein Seitenmuster), welche Widgets welche Zone belegen und in
welchem Raster. Die Muster reichen vom allgemeinsten zum genauesten:

| Muster | Gilt für |
|---|---|
| `*` | alle Seiten |
| `/` | nur die Startseite |
| `forum/*` | alle Seiten des Forums |

Das genaueste Muster gewinnt: Ein Layout `*` setzt den Inhalt auf acht Spalten plus eine Seitenspalte, ein Layout
`marketplace*` kann dieselbe Seite auf volle Breite stellen.

Du änderst Layouts mit der Maus im **Live-Editor**. Eine Seite kann auch **Blöcke** tragen: geordnete, konfigurierte
Modulinstanzen, eingefügt mit dem Shortcode `[block:…]`.

## Addons und Marketplace

**Addon** ist der Oberbegriff für alles, was sich installieren lässt: Module, Widgets, Themes,
Authentifizierungs-Connectoren (Discord, GitHub, Google) und Sprachpakete (sechs Sprachen mitgeliefert).

Der **[Marketplace](marketplace.md)** ist der Katalog des Projekts: Er **erkennt Updates** der installierten Addons
und lässt dich die **hinzufügen**, die nicht im Paket sind, mit SHA-256-Integritätsprüfung. Der **Kern des CMS** wird
mit einem Klick im **Monitoring** aktualisiert, mit Sicherung vor dem Schreiben und automatischem Zurückrollen bei
einem Fehler.

Technisch ist ein Addon nur ein **Ordner mit PHP-Dateien**: Du kannst ihn versionieren, teilen und auf jeder
NeoFrag-Reborn-Seite neu installieren. Das macht das CMS erweiterbar — siehe die Entwicklerleitfäden.

## In der Praxis: eine Community aufbauen

Für ein Gaming-Team: Wähle bei der Installation das Profil **Gaming / eSport** (Forum, Teams, Ereignisse,
Rekrutierung, Auszeichnungen…), und das Theme Nebula ist eingerichtet; setze dann mit dem Live-Editor das Widget
**Navigation** in die Region `header` und ein Inhalts-Widget (letzte Themen, kommende Matches…) in die Seitenspalte der
Startseite. Alles wird in der Verwaltung eingestellt, **ohne eine Zeile Code**.

Für einen Verein oder Club: Wähle das Profil **Verein / Club** — News, Forum, Galerien, Kalender, Kollektive, Spenden
und Mitgliedsbeiträge, Newsletter, Wiki und FAQ, ohne den eSport-Kram. Mitglieder, Seiten und Kontaktformular sind
immer da; Teams lassen sich bei Bedarf mit einem Klick hinzufügen.

## Überschreiben ohne Fork

Jede mitgelieferte Datei (View, Klasse, Asset) kann **überschrieben** werden, ohne den Originalcode zu ändern. Das
Framework löst in dieser Reihenfolge auf (die zuerst gefundene gewinnt):

1. `overrides/{type}/{file}` — globale Überschreibung
2. `themes/{aktives Theme}/overrides/{type}/{file}` — Überschreibung pro Theme
3. das mitgelieferte Original

So passt du ein Modul oder Widget an deine Seite an, ohne je den Kern anzufassen und ohne Updates zu brechen.

Weiter geht es mit: [Erste Schritte](premiers-pas.md) und [Eine Seite in mehreren Sprachen](multilingue.md).
