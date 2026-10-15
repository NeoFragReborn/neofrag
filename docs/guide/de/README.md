# Dokumentation von NeoFrag Reborn

Willkommen in der öffentlichen Dokumentation von **NeoFrag Reborn**, dem freien CMS für Communitys, vom Gaming bis zum
Verein: Baue die Seite deines Teams, deiner Gilde, deines Clubs oder deines Vereins, ohne eine Zeile Code zu schreiben,
und erweitere sie, wann immer du Lust hast.

> NeoFrag Reborn ist die Community-Fortsetzung von **NeoFrag**, ursprünglich geschaffen von **Michaël BILCOT** &
> **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Ein Open-Source-Projekt unter der Lizenz **LGPLv3**. Diese
> Leitfäden sind aus den französischen Leitfäden übersetzt, die **am Code geprüft** werden; sie erscheinen auch im Wiki
> von [neofrag-reborn.xyz](https://neofrag-reborn.xyz).

## Für Anwender

| Leitfaden | Was du lernst |
|---|---|
| [Installation](installation.md) | NeoFrag Reborn online bringen: Voraussetzungen, der Installer in fünf Schritten und seine Profile, die Kommandozeile, die ersten Einstellungen |
| [Erste Schritte](premiers-pas.md) | Die zehn Schritte, die aus einer frischen Installation eine Seite machen, die bereit zum Öffnen ist: Name, Theme, Startseite, Menü, Forum, Mitglieder, Sicherungen |
| [Konzepte](concepts.md) | Wie das CMS aufgebaut ist: Module, Widgets, Themes, Zonen und Regionen, Layouts, Addons |
| [Das Verwaltungspanel](admin.md) | Deine Seite führen: Inhalte, Mitglieder, Berechtigungen, Einstellungen, Monitoring |
| [Der Marketplace](marketplace.md) | Addons finden und installieren, Addons und den Kern aktualisieren |
| [Eine Seite in mehreren Sprachen](multilingue.md) | Was sich von selbst übersetzt, was du übersetzt, was ein Besucher sieht, wenn eine Übersetzung fehlt |
| [Der Discord-Bot](bot-discord.md) | Deine Seite mit deinem Discord-Server verbinden: Rollen und Spitznamen, Forum ↔ Forum-Kanal, alles aus der Verwaltung eingerichtet — und eine Funktion des Bots schreiben |

## Für Entwickler

| Leitfaden | Was du lernst |
|---|---|
| [Das Framework](framework.md) | Service-Locator, Routing, Datenbank, Formulare, Tabellen, Übersetzungen, das Frontend ohne jQuery unter strikter CSP, Tests und Prüfungen |
| [Ein Modul erstellen](create-a-module.md) | Eine vollständige Funktion: Deklarationen, Routen, Checker, Daten und Migrationen, Abhängigkeiten, Knotenpunkte, Berechtigungen, Verwaltung |
| [Ein Widget erstellen](create-a-widget.md) | Ein wiederverwendbarer Block: Einstellungen mit Rückfallwerten, gemeinsames CSS-Vokabular, reines JavaScript |
| [Ein Theme erstellen](create-a-theme.md) | Zonen und Regionen, Gerüst, Standard-Layouts, das vollständige Vokabular `--nf-*`, einstellbare Farben, Vorschaubild, Theme-Migrationen |
| [Die REST-API](api.md) | Ein Programm mit der Seite sprechen lassen — ein Bot, eine Integration: Zugriffsschlüssel, Rechte, Adressen, Fehler, Ratenbegrenzung |

## Kurz gesagt

NeoFrag Reborn lässt sich auf gewöhnlichem **PHP 8.2+ / MySQL oder MariaDB**-Hosting installieren (Shared Hosting
eingeschlossen), **modular**: ein Profil der Seite bei der Installation, dann Module zum Aktivieren oder Hinzufügen. Ist
sie eingerichtet, gestaltest du deine Seite mit der Maus: Du setzt **Widgets** mit dem Live-Editor in die **Regionen**
deines **Themes** und lädst Ergänzungen aus dem **Marketplace** herunter.

Alles lässt sich **ohne Fork überschreiben** (ein Überschreibsystem auf drei Ebenen), und jede Erweiterung ist ein
einfacher Ordner mit PHP-Dateien, den du versionieren und teilen kannst.

Andere Sprachen: [Français](../README.md) · [English](../en/README.md) · [Español](../es/README.md) · [Italiano](../it/README.md) · [Português](../pt/README.md)
