# Installation

NeoFrag Reborn lässt sich auf gewöhnlichem Webhosting installieren, **Shared Hosting eingeschlossen**: Weder Docker
noch Composer noch Shell-Zugang sind nötig — das Paket bringt seine Abhängigkeiten mit.

## Voraussetzungen

- **PHP 8.2 bis 8.5** mit den Erweiterungen `mysqli`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`,
  `fileinfo` und `iconv`, und ein PHP, das Passwörter mit **Argon2** hashen kann (das können die meisten). Sie
  werden im Panel deines Hosters aktiviert, oft unter „PHP-Version“ oder „Erweiterungen“. Der Installer prüft
  jede einzelne und fährt nicht fort, wenn eine fehlt.
- **MySQL 5.7, 8.0 oder 8.4**, oder **MariaDB 10.5, 10.6, 10.11, 11.4 oder 11.8**, mit der Engine **InnoDB** und
  dem Zeichensatz **utf8mb4**. Das sind die Versionen, gegen die jede Version des CMS vor ihrer Veröffentlichung
  getestet wird, mit PHP 8.2 und 8.5: vollständige Installation, die ganze Testsuite und jedes
  Installationsprofil.
- **Apache** mit `mod_rewrite` (eine `.htaccess` liegt bei); oder **nginx** (`nginx.conf`); oder **Caddy**
  (`Caddyfile`).
- Eine **leere** Datenbank und ihre Zugangsdaten.
- Ein echter **Domainname**, der auf den Server zeigt: Der Installer leitet daraus die Kontaktadresse der Seite ab
  (`noreply@deine-domain`) — über eine IP-Adresse installiert, hätte eine Seite keine gültige Absenderadresse.

## Der Installer

Der Installer läuft in **fünf Schritten** ab: *Voraussetzungen* → *Profil der Seite* → *Datenbank* →
*Administrator* → *Fertig*.

1. **Lade** die Dateien von NeoFrag Reborn in dein Web-Root hoch: den Inhalt des Ordners `neofrag-reborn/` aus dem
   Paket `neofrag-reborn-public-<version>.zip` (per FTP oder indem du es auf dem Server entpackst). Das Paket gibt
   es auf der [Release-Seite](https://github.com/NeoFragReborn/neofrag/releases/latest) des Projekts.
2. Öffne deine Domain: Der Installer startet und zeigt die **Voraussetzungen** mit dem gefundenen Wert
   (PHP-Version, vorhandene Erweiterungen).
3. Wähle das **Profil der Seite**:

   | Profil | Was es installiert |
   |---|---|
   | **Vollständig** | den Kern und alle Module des Pakets |
   | **Gaming / eSport** | den Kern und die Gaming-Identität: Forum, Teams, Spiele, Ereignisse, Rekrutierung, Auszeichnungen… |
   | **Community** | den Kern, News, Forum, Galerie, Ereignisse, Kollektive und die API (für den Discord-Bot und Integrationen) |
   | **Verein / Club** | den Kern, News, Forum, Galerie, Ereignisse, Kalender, Kollektive, Spenden, Mitgliedsbeiträge (Zahlungen), Newsletter, Wiki und FAQ |
   | **Nur der Kern** | Konten, Berechtigungen, Seiten, Einstellungen — sonst nichts |

   Module lassen sich weiterhin einzeln abwählen; Module, die ein anderes Modul braucht, werden automatisch
   hinzugefügt, und der letzte Bildschirm sagt es. Das Formular funktioniert auch ohne JavaScript.
4. Gib die **Datenbankverbindung** an: Der Installer importiert das Schema, installiert die gewählten Module,
   Widgets und Themes und legt die Standard-Layouts an.
5. Lege das **Administratorkonto** an. Fertig.

Nicht installierte Module lassen sich später unter **Verwaltung → Themes & Addons** hinzufügen, und solche, die nicht
im Paket sind, über den [Marketplace](marketplace.md).

## Installation über die Kommandozeile

Für ein **skriptbares** Deployment (Server, Provisionierung) macht `install/cli.php` dasselbe wie der Installer, ohne
Browser — Voraussetzungen eingeschlossen: Es fährt nicht fort, wenn eine fehlt. Es installiert das Profil
**Vollständig**.

```bash
# Administratorpasswort über eine Umgebungsvariable (in der Prozessliste unsichtbar):
export NF_ADMIN_PASS='mein-starkes-passwort'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="Meine Community" --site-url=https://site.tld --yes
```

Ohne Argumente wechselt es in den **interaktiven Modus** (verdecktes Passwort). Optionen: `--db-port`,
`--create-db` (legt die Datenbank an), `--dry-run` (prüft die Konfiguration und testet die Verbindung, ohne etwas zu
schreiben), `--force` (installiert neu), `--no-lock` (setzt die Sperre nicht), `--lang=fr|en|de|es|it|pt` (die
Sprache seiner Meldungen), `--help`.

## Den Zugang nach der Installation absichern

Am Ende setzt der Installer eine **Sperre** (`install/db.txt`): Ein erneuter Besuch von `/install/` zeigt nichts
Verwertbares mehr. Zur Sicherheit **lösche oder benenne den Ordner `install/` um** — er wird nicht mehr gebraucht.
Aktiviere **HTTPS**, falls dein Hoster es nicht getan hat. Unter nginx oder Caddy gehst du von den mitgelieferten
Beispielen im Wurzelverzeichnis aus (`nginx.conf`, `Caddyfile`): Sie sperren bereits den Zugriff auf sensible Ordner
und Dateien, wie es die `.htaccess` unter Apache tut.

## Erste Einstellungen

1. **Verwaltung → Einstellungen**: Name der Seite, Beschreibung, Favicon, **Kontaktadresse** (prüfe, dass sie gültig
   ist und ein Mailserver senden kann — sonst kommen das Kontaktformular und die Registrierungs-E-Mails nie an; ein
   SMTP-Server wird unter *Einstellungen → E-Mail* eingerichtet, und *E-Mails → Testversand* beweist, dass es
   funktioniert), Startseite.
2. **Verwaltung → Themes & Addons**: dein Theme, deine Module.
3. **Verwaltung → Live-Editor**: Baue deine Seiten (setze deine Widgets in die Regionen).
4. **Verwaltung → Benutzer / Berechtigungen**: Lege deine Rollen an und regle die Zugriffe.
5. **Geplante Aufgaben**: Geplante Beiträge, Newsletter und Ereigniserinnerungen werden über den **Cron** der Seite
   verschickt (das Monitoring nennt seine Adresse und seinen Schlüssel). Richte bei deinem Hoster eine Aufgabe alle
   fünf Minuten ein; ohne sie wird nichts Geplantes verschickt.

Die nächsten Schritte, der Reihe nach: [Erste Schritte](premiers-pas.md).

## Fehlerbehebung

- **„Verbindung zur Datenbank nicht möglich“**: Prüfe den Host (oft `localhost`, bei Shared Hosting manchmal eine
  eigene Adresse), den Port (`3306`), den Datenbanknamen — **sie muss existieren und leer sein** — und die
  Zugangsdaten.
- **„Fehlende PHP-Erweiterung“**: Aktiviere die gemeldete Erweiterung im Panel deines Hosters; auf der
  Kommandozeile `php -m`.
- **Ordner `config/` nicht beschreibbar**: Der Installer muss dort `db.php` und die Geheimnisse schreiben. Gib
  Schreibrechte für `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Installation unterbrochen**: MySQL kann ein halb ausgeführtes `CREATE TABLE` nicht rückgängig machen — **lege eine
  frische, leere Datenbank an**, bevor du es erneut versuchst.
- **Adressen mit 404 oder kaputtes AJAX unter nginx oder Plesk**: Die `.htaccess` wird nur von Apache gelesen; siehe
  den Hinweis „nginx / Plesk“ im [Deployment-Leitfaden](../../deploy-ftp.md).
- **Es wird keine E-Mail verschickt**: Sieh unter *System → Monitoring → Fehlerprotokoll* nach (eine Zeile
  `[email] échec envoi …`); die Kontaktadresse muss gültig sein und ein Transport muss existieren (ein konfigurierter
  SMTP-Server oder ein lokaler Mail-Agent).
- **„Ein Fehler ist aufgetreten“, mit einer Referenz**: Die Seite ist abgestürzt. Die Details stehen im
  Fehlerprotokoll; die Referenz findet die richtige Zeile.

## Entwicklung

Der Code liegt auf GitHub: das CMS in [NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag), die
optionalen Addons in [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) — `tools/assembler.php`
legt sie in den Baum des CMS. Zum Entwickeln oder Testen genügt jeder Rechner mit PHP 8.2+ und MariaDB oder MySQL; ein
Docker-Stack (`docker-compose.yml`) liegt bei. Details (Datenbank, Migrationen, Tests, Prüfungen, Deployment):
[`docs/development.md`](../../development.md).
