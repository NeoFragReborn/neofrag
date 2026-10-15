# Der Marketplace

Der **Marketplace** dient dazu, deine Addons zu **aktualisieren** und die zu **installieren, die nicht auf deinem
Server sind**. Er bietet nur die offiziellen Addons von NeoFrag Reborn an, die das Installationspaket bereits alle
enthält: Er dient also vor allem für Updates und um ein Addon zurückzuholen, das du gelöscht hattest. Er kommt *nach*
der Installation ins Spiel, aus der Verwaltung heraus.

Katalog und Archive werden von **neofrag-reborn.xyz** über HTTPS ausgeliefert; jedes Archiv wird beim Herunterladen
anhand seines **SHA-256-Fingerabdrucks** geprüft. Jede Version veröffentlicht sie auch, mit dem Katalog, auf der
[Release-Seite](https://github.com/NeoFragReborn/extensions/releases) des Repositorys der optionalen Addons.

Deine Seite erhält **den Katalog ihrer Version**: Einer Seite, die noch nicht aktuell ist, werden die Addons angeboten,
die für die Version gebaut sind, die sie betreibt, nicht die der neuesten, die Funktionen aufrufen könnten, die ihr
Kern noch nicht hat. Und wenn ein Addon eine neuere Version von NeoFrag braucht, bietet das Fenster des Marketplace es
nicht an: Es sagt dir, dass du zuerst deine Seite aktualisieren sollst.

## Ein Addon installieren

Zwei Wege:

### 1. Mit einem Klick aus der Verwaltung

**Admin → Themes & Addons → Marketplace**. Das Fenster listet die verfügbaren, nicht installierten Addons; hake sie an
und klicke **Installieren** — NeoFrag lädt herunter, prüft den SHA-256-Fingerabdruck, entpackt und registriert das
Addon (und gegebenenfalls sein zugehöriges Widget).

### 2. Von Hand (ZIP-Archiv)

Die (öffentliche) Seite **Marketplace** zeigt jedes Addon auf einer Karte mit einem Knopf **Herunterladen**. Du kannst
auch das `.zip` herunterladen, dann **Admin → Themes & Addons → Hinzufügen** und das Archiv hochladen (der Upload wird
geprüft: Archive mit unsicherem Inhalt werden abgelehnt). Das ist auch der Weg für ein Addon, das du selbst
geschrieben hast: Zippe seinen Ordner und lade ihn hoch.

## Aktualisieren oder entfernen

- **Aktualisieren**: **Admin → Themes & Addons → Updates** vergleicht die installierte Version jedes Addons mit der des
  Katalogs und listet die zu aktualisierenden; wende sie mit einem Klick an (Download + SHA-256, Dateien ersetzt,
  **ausstehende Schema-Migrationen angewendet**). Sonst aktualisiert auch das Hochladen eines neuen `.zip` über
  *Hinzufügen* (eine höhere Version ersetzt die alte).
- **Entfernen**: auf der Karte des Addons unter **Themes & Addons**, **Löschen** (das Addon muss deaktiviert sein).
  Seine Tabellen und Daten werden entfernt.

> Die **Authentifizierungs-Connectoren** (Discord, GitHub, Google) gehören zum **Kern**: Sie sind schon da und werden in
> den Einstellungen eingerichtet — sie gehen nicht über den Marketplace.

> **Marketplace nicht erreichbar?** Zeigt der Bildschirm „Updates“ „Marketplace nicht erreichbar“, prüfe, ob dein
> Server ausgehende **HTTPS**-Verbindungen zu `neofrag-reborn.xyz` aufbauen kann. Der Katalog ist an diesen Ursprung
> gebunden: Die Liste der erlaubten Hosts steht im Code (`MARKETPLACE_HOSTS`, `neofrag/installer.php`), und die
> Einstellung `nf_marketplace_url`, die keinen Verwaltungsbildschirm hat, kann nur auf eine HTTPS-Adresse dieser Hosts
> zeigen.

## Sicherheit

Der Marketplace lädt nur von einem **festen Ursprung** über **HTTPS** herunter (nie von einer vom Benutzer
eingegebenen URL), prüft den **SHA-256** jedes Archivs gegen den Katalog und **lehnt jedes präparierte Archiv ab**
(Anti-Zip-Slip: absolute Pfade, `..`, Symlinks). Beim Hinzufügen oder Aktualisieren eines Addons wird kein entfernter
Code ausgeführt (nur Entpacken + idempotente `install.sql`/SQL-Migrationen).

## Für Addon-Autoren

Der Code der optionalen Addons lebt in [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions): Ein
Addon oder eine Korrektur vorzuschlagen heißt, dort einen Pull-Request zu öffnen. Der Katalog wird von
`tools/package-addons.php` erzeugt, das jedes Addon zippt (ein Ordner `<name>/` im Wurzelverzeichnis des Archivs) und
`marketplace/catalog.json` erstellt. Ein echter, gekürzter Eintrag:

```json
{
  "schema": 1, "base_version": "1.2.22",
  "addons": [{
    "type": "module", "name": "quotes", "tier": 2, "category": "contenu",
    "title": "Citations", "description": "Recueil de citations classées, avec leur auteur et leur source.",
    "i18n": { "en": { "title": "Quotes", "description": "…" } },
    "version": "1.0", "author": "NeoFrag Reborn", "license": "LGPLv3 <https://neofr.ag/license>",
    "file": "modules/quotes.zip", "preview": "modules/quotes.jpg", "size": 60556, "sha256": "…",
    "requires": { "base": ">=1.2.22", "addons": [] }, "provides_widgets": [], "install": "zip"
  }]
}
```

`catalog.json` und die `.zip`-Dateien werden **zusammen** veröffentlicht: Die SHA-256-Fingerabdrücke gelten nur für die
Archive desselben Durchlaufs. Der offizielle Katalog wird mit jeder Version veröffentlicht, und eine Seite kann keinem
anderen folgen, ohne `MARKETPLACE_HOSTS` zu ändern. Ein Addon, das du schreibst — Leitfäden:
[ein Modul erstellen](create-a-module.md), [ein Widget](create-a-widget.md), [ein Theme](create-a-theme.md) —
installiert sich auf jeder Seite über *Themes & Addons → Hinzufügen*.

## Den Kern aktualisieren (NeoFrag selbst)

**Admin → Themes & Addons → Updates** **meldet** gegebenenfalls eine neue Version des Kerns: die, die der
Update-Kanal ankündigt (das Monitoring fragt ihn ab), und sonst die `base_version` des Katalogs, die Version des CMS,
für die er gebaut wurde.

Zwei Wege, sie anzuwenden.

### Mit einem Klick aus dem Monitoring

**Admin → Monitoring → Aktualisieren**. Die Seite legt zuerst eine **Sicherung** an, lädt dann das Update-Paket
herunter, prüft seinen Fingerabdruck, legt die Dateien über die alten, wendet ausstehende Migrationen an und kompiliert
die Stylesheets neu.

Der Knopf nimmt nur das richtige Update an, durch vier Garantien:

1. der **Ursprung** stammt aus derselben Positivliste wie der Marketplace, und ein in die Datenbank eingeschleuster Wert
   kann sie nicht verlassen;
2. `version.json` liefert nur einen **Dateinamen**, nie eine URL: kein Host, kein Pfad, also weder Umleitung noch
   Verzeichnisdurchquerung;
3. der **SHA-256**-Fingerabdruck wird geprüft, **bevor** eine einzige Datei der Seite berührt wird;
4. das Archiv wird **Eintrag für Eintrag** geprüft (Anti-Zip-Slip, Symlinks abgelehnt).

Bereits vorhandene Dateien in `config/` und die Sperre `install/db.txt` werden nie überschrieben: Die Konfiguration einer
laufenden Seite bleibt erhalten. Der Rest von `install/` ist Code des Produkts und folgt den Versionen.

### Von Hand

Lade `neofrag-reborn-public-<version>.zip` von der
[Release-Seite](https://github.com/NeoFragReborn/neofrag/releases) herunter und ersetze die Dateien (außer `config/`,
`upload/`, `backups/`), dann besuche die Seite — die Migrationen wenden sich selbst an
([Deployment-Leitfaden](../../deploy-ftp.md#mettre-à-jour-un-site-déjà-en-ligne)).

### Was jede Version veröffentlicht

Jede Version veröffentlicht auf dem Update-Ursprung (`https://neofrag-reborn.xyz/update/`) drei Dateien, die einen
stimmigen Satz bilden: das Update-Paket, `version.json` (die Version, der Name des Pakets, sein SHA-256 und seine Größe)
und `checksum.json` (ein Fingerabdruck pro ausgelieferter Datei, für die Integritätsprüfung des Monitorings). Der Knopf
prüft jede gegen die anderen: Eine ohne die anderen zu veröffentlichen lässt das Update scheitern, ohne die Seite zu
berühren.
