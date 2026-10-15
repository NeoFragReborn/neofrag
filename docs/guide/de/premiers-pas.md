# Erste Schritte

Deine Seite ist gerade installiert ([Installation](installation.md)): Hier steht der Reihe nach, was sie von einer
leeren Seite zu einer Seite macht, die du deiner Community öffnen kannst. Rechne mit einer Stunde. Jeder Schritt
verweist auf den Leitfaden, der ihn im Detail erklärt.

## 1. Die Verwaltung betreten

Melde dich mit dem Konto an, das am Ende des Installers angelegt wurde, und öffne dann `/de/admin` (oder den Link
*Verwaltung* in der Mitgliederleiste). Die Seitenleiste ordnet die Module in Rubriken — *Inhalt*, *Community*,
*System*… —; `Strg + K` öffnet die Schnellsuche. Den ganzen Rundgang gibt es in [Das Verwaltungspanel](admin.md).

## 2. Der Seite ihren Namen geben

*System → Einstellungen*: der Name der Seite, ihre Beschreibung, die Kontaktadresse, die Zeitzone. Der Name erscheint
im Kopf des Themes und im Browser-Tab; die Beschreibung dient den Suchmaschinen, solange eine Seite keine eigene
angibt ([Suchmaschinen](admin.md#suchmaschinen)).

## 3. Theme und Farben wählen

*System → Themes & Addons*: Jedes Theme zeigt seine Startseite als Vorschaubild. Aktiviere das, das zu deiner
Community passt, und öffne seine Einstellungen: Die Seite der **Farben** zeigt die Änderung live und lehnt eine Farbe
ab, die den Text unlesbar machen würde. Was ein Theme ist, seine Zonen und Regionen: [Konzepte](concepts.md).

## 4. Die Startseite gestalten

*System → Live-Editor*: Die Seite erscheint so, wie Besucher sie sehen, mit umrandeten Regionen. Ziehe **Widgets**
hinein — die neuesten News, das Forum, die Mitglieder online, eine Diashow — und stelle jedes mit einem Klick ein. Ein
Layout gilt für eine Seite oder für die ganze Website.

## 5. Das Menü bauen

*Inhalt → Menüs*: Füge die Links der Navigationsleiste hinzu — Startseite, Forum, News, eine Seite. Ein Link zu einem
Modul wird aus einer Liste gewählt; ein externer Link wird eingefügt.

## 6. Eine erste News veröffentlichen

*Inhalt → News → neue News*: ein Titel, ein Bild, der Text. Sie erscheint auf der Startseite, wenn du dort das
News-Widget platziert hast, und die Leser können sie kommentieren, wenn Kommentare offen sind.

## 7. Das Forum eröffnen

*Community → Forum*: Lege eine **Kategorie** an („Allgemein“), dann ihre **Foren** („Vorstellungen“, „Hilfe“).
**Präfixe** („Frage“, „Gelöst“) helfen, Themen zu ordnen. Wer liest und wer schreibt, wird pro Kategorie mit dem
Schloss-Knopf geregelt.

## 8. Mitglieder empfangen

*System → Einstellungen → Registrierungen*: offen, auf Einladung oder geschlossen, mit oder ohne Captcha
([Registrierungen](admin.md#registrierungen)). **Rollen** entscheiden, was jeder tun darf: Lege eine Rolle
„Moderator“ an, hake ihre Rechte in der Matrix an und gib sie vertrauenswürdigen Mitgliedern
([Berechtigungen](admin.md#berechtigungen)).

## 9. Hinzufügen, was fehlt

Die Seite wurde mit einem Profil installiert — Gaming, Community, Verein. Ein fehlendes Modul (der Kalender, Spenden,
Kollektive, der Discord-Bot…) wird unter *System → Themes & Addons* oder über den [Marketplace](marketplace.md)
hinzugefügt, ohne Neuinstallation.

## 10. Sie in Sicherheit bringen

*System → Monitoring*: Lege eine erste **Sicherung** an und notiere die Adresse der **geplanten Aufgabe**. Gib sie
deinem Hoster (eine Cron-Aufgabe, stündlich): Sie veröffentlicht geplante Inhalte, verschickt Erinnerungen und holt
ab, was externe Dienste nicht melden ([Monitoring](admin.md#monitoring)).

Spricht deine Seite mehrere Sprachen? Lies danach [Eine Seite in mehreren Sprachen](multilingue.md).
