# Der Discord-Bot

Der Discord-Bot von NeoFrag Reborn verbindet deine Seite und deinen Discord-Server. Seine Funktionen werden einzeln
eingeschaltet:

- **Rollen und Spitznamen** — ein Mitglied, das sein Discord-Konto verknüpft hat, erhält auf dem Server die Rollen, die
  mit seinen Gruppen verknüpft sind, und auf Wunsch seinen Benutzernamen der Seite;
- **Forum und Forum-Kanäle** — ein Forum der Seite und ein Forum-Kanal auf Discord leben zusammen: Ein Thema wird zu
  einem Thread, ein Thread zu einem Thema, Antworten folgen in beide Richtungen, und die Präfixe des Forums werden zu
  den Tags des Kanals;
- **Konto und Erscheinungsbild** — der Befehl `/forum`: das Konto von Discord aus verknüpfen und wählen, wie man im
  Forum ohne verknüpftes Konto erscheint;
- **Bugtracker** — jedes Ticket wird zu einem Thread, Typ und Status als Tags; `/bug` und `/idee` eröffnen ein Ticket von
  Discord aus;
- **befristete Rollen** — der Befehl `/role`: eine Rolle für begrenzte Zeit vergeben, die der Bot am Ende entzieht;
- **verknüpfte Sanktionen** — Stummschaltungen und Sperren der Seite gelten auf Discord, und die eines verknüpften
  Moderators können zur Seite gelangen;
- **Kollektive** — jedes Kollektiv der Seite erhält eine Rolle und einen privaten Kanal, den nur seine Mitglieder sehen.

Er wird **vollständig aus der Verwaltung** der Seite eingerichtet: sein Token, sein Server, seine Funktionen und ihre
Einstellungen, der Schalter Start / Pause, der Neustart, die Zuordnungen und sogar das Anlegen von Kanälen und Rollen
auf dem Server. Die Verwaltung zeigt auch seinen Zustand und sein Protokoll, in deiner Sprache.

Der Bot ist ein **eigenes Programm**, das ständig läuft. Er braucht einen eingeschalteten Rechner — einen VPS, einen
Raspberry Pi, einen PC — mit **Node.js 22.9 oder neuer**. Shared Hosting kann ihn nicht betreiben; die Seite selbst
braucht ihn nicht und bleibt, wo sie ist.

## 1. Die Discord-Anwendung anlegen

1. Im [Discord-Entwicklerportal](https://discord.com/developers/applications): *New Application*, gib ihr einen Namen.
2. Reiter *General Information*: Notiere die **Application ID**.
3. Reiter *Bot*: *Reset Token*, und kopiere das **Bot-Token** — es wird nur einmal angezeigt.
4. Noch unter *Bot* hakst du die beiden **privilegierten Intents** an:
   - **Server Members Intent** — für Rollen, Spitznamen und befristete Rollen;
   - **Message Content Intent** — um den Text der Discord-Nachrichten auf die Seite zu kopieren (Forum, Bugtracker).

   Ohne sie läuft der Bot im eingeschränkten Modus und sagt es in seinem Protokoll: Er verlangt von Discord nie mehr,
   als die Anwendung empfangen darf.

## 2. Das Discord-Modul einrichten

Der Bot stützt sich auf zwei Module der Seite, **API** und **Discord**. Das Installationsprofil *Vollständig*
installiert beide; sonst fügt *System → Themes & Addons* sie hinzu (Discord, ein optionales Addon, kommt aus dem
Marketplace, wenn es nicht auf dem Server ist). Die Synchronisierung des Forums braucht auch das Modul **Forum**, die der
Tickets das Modul **Bugtracker**.

*Verwaltung → Discord → Verbindung*:

| Feld | Wo du es findest |
|---|---|
| Application ID | die *Application ID* aus Schritt 1 |
| Server-ID | in Discord, mit eingeschaltetem Entwicklermodus: Rechtsklick auf den Server → *Server-ID kopieren* |
| Bot-Token | das Token aus Schritt 1. Es wird **verschlüsselt gespeichert** und nie wieder angezeigt; lass das Feld leer, um das gespeicherte zu behalten |

Dann **Bot einladen** (Startseite des Moduls): Der Link fügt den Bot dem Server mit nur seinen eigenen Rechten hinzu —
nie „Administrator“:

| Recht | Wofür |
|---|---|
| Kanäle ansehen, Nachrichten senden, Nachrichtenverlauf lesen | in verknüpften Kanälen lesen und schreiben |
| Nachrichten in Threads senden, Öffentliche Threads erstellen, Threads verwalten | ein Thema oder Ticket als Thread kopieren, umbenennen, archivieren, seine Tags setzen |
| Kanäle verwalten | die Einrichtung des Servers: eine Kategorie, Forum-Kanäle und ihre Tags anlegen |
| Nachrichten verwalten | eine auf der Seite vorgenommene Löschung auf Discord übertragen |
| Webhooks verwalten | die Nachrichten der Seite unter dem Namen und Avatar ihres Autors posten |
| Reaktionen hinzufügen, Links einbetten, Dateien anhängen | vollständige Nachrichten |
| Rollen verwalten, Spitznamen verwalten | Rollen, Spitznamen und befristete Rollen |
| Anwendungsbefehle verwenden | `/forum`, `/bug`, `/idee`, `/role` |

In den Servereinstellungen (*Rollen*) setzt du die Rolle des Bots **über** die Rollen, die er vergibt: Discord verbietet
einem Bot, eine Rolle zu vergeben, die höher steht als seine eigene.

## 3. Den Bot auf seinem Rechner installieren

Der Bot wird als fertig gebautes Archiv heruntergeladen, `neofrag-reborn-bot-X.Y.Z.tar.gz`, auf der
[Release-Seite](https://github.com/NeoFragReborn/bot-discord/releases/latest) seines Repositorys. Er hat eigene
Versionsnummern, unabhängig von denen der Seite.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Verwaltung → Discord → Zugriffsschlüssel erstellen*: Die Seite gibt **zwei Zeilen** — die Adresse der Seite und den
Zugriffsschlüssel des Bots —, die in `.env` einzufügen sind. Sie werden nur einmal angezeigt; ein neuer Schlüssel
widerruft den alten. Dann:

```bash
npm start
```

Damit er als Dienst läuft, mit dem Rechner startet und neu startet, wenn er abstürzt: `neofrag-bot.service` (systemd,
gehärtet) erklärt seine Installation am Anfang der Datei. Sein Protokoll: `journalctl -u neofrag-bot -f` — und vor allem
die Verwaltung der Seite.

Auf dem Rechner bleiben nur diese zwei Zeilen. Alles andere kommt über die Seite.

**Den Bot aktualisieren**: Ersetze den Ordner durch den des neuen Archivs und behalte deine Konfiguration (`.env` oder
unter systemd `/etc/neofrag-bot/bot.env`), dann `npm ci --omit=dev` und starte den Dienst neu. Eine neue Version des
Bots, die auf der Seite mehr Rechte braucht, sagt es in der Verwaltung: Lege dann einen neuen Zugriffsschlüssel an.

## 4. Ihn aus der Verwaltung steuern

*Verwaltung → Discord* zeigt:

- seinen **Zustand** — *Online*, *Pausiert* (lebt, Schalter aus), *Verbinde mit Discord…* oder *Offline* (seit
  anderthalb Minuten keine Nachricht) —, seine Version, sein letztes Lebenszeichen;
- die Knöpfe **Starten / Pausieren**, **Neu starten** und **Neu synchronisieren** (alles wieder in Einklang bringen:
  Rollen und Spitznamen, und was während einer Abwesenheit des Bots auf Discord geschrieben wurde), die er innerhalb
  einer Minute anwendet;
- sein **Protokoll**: Verbindungen, Synchronisierungen und was ihn am Funktionieren hindert (nicht angehakter Intent,
  falsch platzierte Rolle, fehlendes Recht, zu alter Zugriffsschlüssel), mit dem, was zu tun ist.

**Das Bot-Token ändern** (Leck, Neuerzeugung): Füge das neue unter *Verbindung* ein. Der Bot liest es erneut und
verbindet sich von selbst neu; der Rechner wird nicht angefasst.

### Funktionen

*Discord → Funktionen* listet, was der Bot kann — er meldet sie bei jedem Lebenszeichen selbst: Eine dem Bot
hinzugefügte Funktion erscheint dort, ohne die Seite anzufassen. Jede wird **eingeschaltet** oder **ausgeschaltet**,
und ihre **Einstellungen** werden dort geändert; der Bot wendet die Änderung innerhalb einer Minute an, ohne Neustart.
Bugtracker, befristete Rollen, verknüpfte Sanktionen und Kollektive sind anfangs aus.

### Einrichtung des Servers

*Discord → Einrichtung des Servers* legt auf Discord an, was nötig ist, um die Seite zu verbinden, ohne Handarbeit:

- eine **Kategorie** (wiederverwendet, wenn es sie schon gibt);
- einen **Forum-Kanal pro gewähltem Forum**, mit den Präfixen des Forums als Tags;
- eine **Rolle pro gewählter Gruppe**, unter die des Bots gesetzt, damit er sie vergeben kann.

Ein Kanal oder eine Rolle gleichen Namens wird wiederverwendet statt verdoppelt. Eine **Vorschau** zeigt, was angelegt
und wiederverwendet wird; **Anwenden** führt es aus, und die Zuordnungen (Kanal ↔ Forum, Gruppe ↔ Rolle) setzen sich
selbst. **Letzte Einrichtung rückgängig machen** löscht, was sie angelegt hat — nie, was sie wiederverwendet hat.

## Rollen und Spitznamen

*Discord → Gruppen und Rollen* verknüpft eine Gruppe der Seite mit einer Rolle des Servers. Die **Seite hat das letzte
Wort**:

- ein verknüpftes Mitglied, das in der Gruppe ist, erhält die Rolle; wer nicht mehr darin ist, verliert sie;
- eine Rolle, die nichts verknüpft, wird **nie angefasst**; ebenso ein Mitglied, das sein Konto nicht verknüpft hat;
- mit der Einstellung *Spitznamen* trägt das verknüpfte Mitglied auf dem Server seinen Benutzernamen der Seite (außer
  dem Besitzer des Servers, den Discord schützt);
- eine verknüpfte Rolle trägt **Namen und Farbe ihrer Gruppe** (Bot 0.2.5): Eine auf der Seite umbenannte oder neu
  eingefärbte Gruppe wird es auch auf dem Server. Eine mit mehreren Gruppen verknüpfte Rolle behält ihre eigenen, und
  eine Gruppe ohne Farbe lässt die der Rolle. Die Einstellung „Verknüpften Rollen Namen und Farbe ihrer Gruppe geben“
  schaltet das ab.

Der Bot wendet einen Gruppenwechsel innerhalb einer halben Minute an, die Ankunft eines verknüpften Mitglieds auf dem
Server und die Verknüpfung eines Kontos sofort, und geht beim Start, bei jeder Änderung der Einstellungen und im
gewählten Intervall (standardmäßig zehn Minuten) alle erneut durch.

## Konto und Erscheinungsbild: `/forum`

Ein Mitglied verknüpft sein Konto, indem es sich über Discord an der Seite anmeldet, unter *Mein Konto → Meine
verknüpften Konten* oder **von Discord aus**:

- `/forum account link` gibt einen Einmal-Link, fünfzehn Minuten gültig. Auf der Seite, angemeldet, bestätigt das
  Mitglied die Verknüpfung — und kann übernehmen, was es zuvor von Discord aus veröffentlicht hatte;
- `/forum account unlink` hebt die Verknüpfung auf, außer wenn Discord sein einziger Weg ist, sich an der Seite
  anzumelden.

Wer sein Konto nicht verknüpft hat, wählt, wie er im Forum der Seite erscheint:

- `/forum visibility public` — unter seinem Discord-Namen;
- `/forum visibility guest` — unter einem anonymen Namen;
- `/forum visibility custom` — unter einem Namen seiner Wahl (2 bis 32 Zeichen, alle sieben Tage änderbar, nie der eines
  Mitglieds der Seite);
- `/forum visibility status` — was eingestellt ist.

Seine bereits veröffentlichten Nachrichten folgen seiner Wahl. Alle Antworten sind privat, in der Discord-Sprache jedes
Einzelnen.

## Das Forum und die Forum-Kanäle

*Discord → Kanäle und Foren* verknüpft ein Forum der Seite mit einem **Forum-Kanal** auf Discord (keinem Textkanal).
Zwei Modi, Kanal für Kanal:

- **Alles**: Jeder Thread des Kanals wird zu einem Thema der Seite und jedes Thema des Forums zu einem Thread;
- **Auf Anfrage**: Ein Thread gelangt erst auf die Seite, wenn jemand, der im Kanal *Threads verwalten* darf, die
  gewählte **Reaktion** setzt (standardmäßig 📌) — der ganze Thread, dann die Fortsetzung. Die Themen der Seite, schon
  öffentlich, gehen immer auf Discord.

Was folgt, und wie:

| | Von der Seite zu Discord | Von Discord zur Seite |
|---|---|---|
| Neues Thema / neuer Thread | ein Thread, unter Name und Avatar des Autors | ein Thema |
| Antwort | eine Nachricht im Thread | eine Antwort (das Zitat einer Nachricht folgt) |
| Bearbeitung | die Nachricht wird neu geschrieben, der Thread-Titel folgt | die Nachricht wird neu geschrieben |
| Löschung | die Nachricht verschwindet; ein gelöschtes Thema nimmt seinen Thread mit | die Nachricht kommt in den Papierkorb des Forums |
| Präfix / Tag | der passende Tag wird gesetzt | das passende Präfix wird gesetzt |

- **Präfixe und Tags** (Knopf jedes verknüpften Kanals): ein Präfix des Forums ↔ ein Tag des Kanals. Die Einrichtung
  verknüpft sie von selbst.
- Eine Nachricht von Discord wird unter dem **Konto des Mitglieds** veröffentlicht, das sein Discord verknüpft hat;
  sonst unter seiner **Discord-Identität**, wie mit `/forum visibility` gewählt, mit dem Discord-Logo markiert.
- Die **Moderationssanktionen** der Seite folgen dem verknüpften Mitglied: Im Forum stummgeschaltet oder gesperrt,
  werden seine Discord-Nachrichten nicht kopiert; ohne Recht auf externe Links auch keine Nachricht, die welche trägt
  (Bot 0.2.5). Das Protokoll des Bots vermerkt es, ohne etwas im Kanal zu sagen; `/bug` und `/idee` antworten nur dem
  Autor.
- Eine für Discord zu lange Nachricht (2.000 Zeichen) wird dort gekürzt, mit einem Link zum Rest.
- Die **Bilder** einer Nachricht der Seite gehen als Vorschauen unter dem Text auf Discord; die Links der Nachricht
  zeigen dort keine Vorschaukarte.
- Ein **auf Discord angehängtes Bild** (JPEG, PNG, GIF oder WebP, höchstens 5 MB) wird auf der Seite behalten und in der
  Forennachricht angezeigt — eine zu alte Seite behält den Link. Andere Anhänge, und alle des Bugtrackers, werden als
  Link zur Discord-Nachricht angezeigt.
- Einstellungen: ein Link zur Seite unter jedem kopierten Thema, und das **Nachholen** beim Start dessen, was auf
  Discord geschrieben wurde, während der Bot aus war.
- Die **Rechte** jedes verknüpften Kanals folgen den Rechten seines Forums auf der Seite (Bot 0.2.5, Einstellung
  standardmäßig an). Der ganze Server zählt wie die Mitglieder der Seite: Wer das Forum nicht lesen darf, sieht den
  Kanal nicht, wer darin nicht schreiben darf, postet dort nicht — Schreiben nur im Modus **Alles**; im Modus **Auf
  Anfrage** bleibt Discord ein Ort der Diskussion. Eine verknüpfte Rolle gibt ihrer Gruppe, was Mitglieder nicht haben:
  Der Kanal eines Team-Forums wird nur den Rollen des Teams gezeigt. Der Bot berührt nur Sehen und Schreiben, für
  @everyone, die verknüpften Rollen und sich selbst; jede andere von Hand gesetzte Einstellung bleibt.
- Die Seite ihrerseits weigert sich, im Namen von jemandem ohne Recht zu schreiben: Eine Nachricht von Discord in einem
  Forum, in dem ihr Autor nicht schreiben darf, bleibt auf Discord, und das Protokoll des Bots sagt es.
- Nicht übertragen werden: das Löschen eines ganzen Threads auf Discord (das Thema bleibt, das Protokoll meldet es), das
  seiner Eröffnungsnachricht, und auf Discord die Bearbeitung einer auf Discord geschriebenen Nachricht (sie gehört
  ihrem Autor). Die Discord-Kopie einer Nachricht der Seite, von einem Moderator gelöscht, löscht das Original nicht.

## Der Bugtracker

In den Einstellungen der Funktion wählst du den **Forum-Kanal der Tickets** (keinen Kanal, der schon mit einem Forum
verknüpft ist). Der Bot legt dort die Tags der Typen (Fehler, Idee, Frage, Sonstiges) und der Status (Offen, In
Arbeit, Gelöst, Geschlossen, Wird nicht gemacht, Duplikat) in der Sprache der Seite an.

Um Ideen von Fehlern zu trennen, wähle auch einen **Forum-Kanal der Vorschläge** (Bot 0.2.5): Tickets vom Typ Idee
haben dort ihren Thread, mit dem Tag Idee und denen der Status; die anderen bleiben im Kanal der Tickets.

- Jedes Ticket wird zu einem **Thread**, unter Name und Avatar seines Autors, mit Nummer, Typ, Priorität und einem Link
  zur Seite. Noch offene Tickets erhalten ihren Thread, wenn du den Kanal wählst — und wenn du den Kanal der Vorschläge
  wählst, ziehen die noch offenen Ideen dorthin um.
- Ein Ticket, das **den Typ wechselt** (ein Fehler, der eine Idee wurde), wechselt den Kanal: Discord verschiebt keinen
  Thread, also eröffnet der Bot einen neuen im richtigen Kanal. Der alte Thread, gesperrt, verweist darauf, und der
  neue auf den alten. Auf der Seite behält das Ticket alle Kommentare.
- Ändert sich das Ticket auf der Seite, folgt der Thread: Tags, Titel, Beschreibung. Er **archiviert sich**, wenn das
  Ticket geschlossen wird; ein Duplikat verweist auf sein ursprüngliches Ticket; ein gelöschtes Ticket nimmt seinen
  Thread mit.
- **Kommentare** gehen in beide Richtungen. Auf Discord kommentiert, wer sein Konto nicht verknüpft hat, unter seinem
  Discord-Namen.
- `/bug` und `/idee` eröffnen ein Ticket über ein kleines Fenster (Titel, Beschreibung), und ein von Hand in einem der
  Kanäle eröffneter Thread wird zu einem Ticket — eine Idee im Kanal der Vorschläge. Ein Ticket gehört einem Mitglied:
  Man muss sein Konto verknüpft haben (sonst erinnert der Bot daran).
- Die **Seite hat das letzte Wort**: Ein auf Discord von Hand geänderter Tag wird so zurückgesetzt, wie es das Ticket
  sagt.

## Befristete Rollen: `/role`

Vorbehalten für die, die auf dem Server **Rollen verwalten** dürfen:

- `/role give` — einem Mitglied eine Rolle für eine Dauer geben (Minuten, Stunden, Tage, Wochen), auf Wunsch mit einem
  Grund. Erneut gegeben, wird sie verlängert;
- `/role remove` — sie sofort entziehen;
- `/role list` — die laufenden, für den ganzen Server oder für ein Mitglied.

Der Bot entzieht die Rolle bei Ablauf (jede Minute geprüft), **auch nach einem Neustart**: Die Liste lebt auf der Seite,
unter *Discord → Befristete Rollen*, wo **Jetzt entfernen** das Ende vorzieht. Ein Mitglied, das den Server vor dem Ende
verlässt und wieder beitritt, erhält seine Rolle zurück (Einstellung *Die Rolle zurückgeben*).

Abgelehnt werden: `@everyone` und Rollen einer Integration, Rollen, die **mit einer Gruppe** der Seite verknüpft sind
(die Synchronisierung der Gruppen verwaltet sie), und — wie Discord es für dieses Recht tut — eine Rolle über dem Bot
oder auf Höhe dessen, der sie gibt, oder darüber. Die Dauer ist durch die Einstellung *Höchstdauer* begrenzt
(standardmäßig 90 Tage, höchstens ein Jahr).

## Verknüpfte Sanktionen

Standardmäßig aus: *Discord → Funktionen → Verknüpfte Sanktionen*. Zwei Richtungen, jede mit eigener Einstellung.

**Von der Seite zu Discord** (*Seitenweite Stummschaltungen und Sperren auf Discord anwenden*, mit der Funktion
eingeschaltet): Eine Sanktion für **die ganze Seite** folgt dem verknüpften Discord-Konto des Mitglieds.

| Auf der Seite | Auf Discord |
|---|---|
| Stummschaltung | ein Timeout bis zum Enddatum — vom Bot über die 28 Tage hinaus erneuert, die Discord erlaubt |
| Befristete Sperre | ein Bann, vom Bot zum Enddatum aufgehoben |
| Dauerhafte Sperre | ein Bann |

Eine Verwarnung, eine Einschränkung, ein Shadow-Ban (er muss unsichtbar bleiben) und die Sanktion eines einzelnen
Moduls (das Forum, die Chaträume…) gehen nie hinüber. Eine Sanktion, die auf ihre Bestätigung wartet, geht hinüber,
sobald sie bestätigt ist. Auf der Seite aufgehoben, wird sie auf Discord aufgehoben — der Bot entfernt nur, was er
gesetzt hat (sein Grund trägt „NeoFrag #Nummer“), oder was von Discord kam. Er liest die laufenden Sanktionen beim Start
und dann alle zehn Minuten erneut: Ein Enddatum, das verstrich, während er aus war, wird nachgeholt.

**Von Discord zur Seite** (*Auf Discord verhängte Sanktionen an die Seite melden*, standardmäßig aus): Ein auf Discord
gesetzter Bann oder Timeout gelangt zur Seite, wenn der Moderator ein Mensch ist, dessen Discord-Konto mit einem Konto
der Seite verknüpft ist, das dieses Recht hat — *moderieren* für einen Timeout (eine Stummschaltung auf der Seite),
*dauerhaft sperren* für einen Bann —, und wenn das sanktionierte Mitglied seins verknüpft hat. Die Seite beurteilt sie
erneut, als wäre sie bei ihr verhängt worden (Hierarchie, Bestätigung); sonst bleibt sie auf Discord, und das Protokoll
des Bots sagt warum. Eine von Discord gekommene Sanktion wird auf Discord (Bann aufheben, Timeout entfernen) oder auf
der Seite aufgehoben; eine Sanktion der Seite nur auf der Seite.

Der Einladungslink gibt dem Bot diese Rechte nicht: Füge sie seiner Rolle hinzu — *Mitglieder bannen* und *Mitglieder
in Timeout versetzen* für die erste Richtung, *Audit-Log anzeigen* für die zweite. Der Bot sagt in seinem Protokoll,
was ihm fehlt. Discord weigert sich, den Besitzer des Servers und ein Mitglied über der Rolle des Bots zu sanktionieren.

## Kollektive

Standardmäßig aus: *Discord → Funktionen → Kollektive*. Jedes Kollektiv der Seite, dessen Feld *Discord* angehakt ist
([Kollektive](admin.md#kollektive)), erhält auf dem Server:

- eine **Rolle**, benannt wie das Kollektiv — es ist die Gruppe des Kollektivs, mit dieser Rolle verknüpft: Die Funktion
  *Rollen und Spitznamen* gibt sie seinen Mitgliedern, die ihr Konto verknüpft haben, und entzieht sie denen, die
  austreten;
- einen **Textkanal** in der Kategorie *Collectifs* (der Name ist einstellbar; der Bot legt sie an), für `@everyone`
  geschlossen, für diese Rolle offen.

Der Bot legt an, was fehlt, beim Start, wenn sich die Konfiguration ändert (ein Kollektiv angelegt oder umbenannt) und
alle fünf Minuten: Eine von Hand gelöschte Rolle oder ein gelöschter Kanal kommt zurück, ein umbenanntes Kollektiv wird
umbenannt. Ein gelöschtes Kollektiv, oder eines, dessen Feld abgehakt wurde: Der Bot löscht seine Rolle, und sein Kanal
**bleibt**, nur für die Administratoren des Servers sichtbar. Die Rolle des Bots braucht *Rollen verwalten* und *Kanäle
verwalten* (im Einladungslink) und muss über den Rollen stehen, die er anlegt.

## Sicherheit

- Auf dem Rechner des Bots: die Adresse der Seite und ihr Zugriffsschlüssel, sonst nichts.
- Das Bot-Token ist in der Datenbank der Seite mit dem eigenen Schlüssel der Seite verschlüsselt. Nur ein
  Zugriffsschlüssel mit dem Recht `discord:bot` kann es lesen — den die Verwaltung für den Bot anlegt, jederzeit
  widerrufbar.
- Der Bot erwähnt nie jemanden: Eine kopierte Nachricht kann `@everyone` nicht benachrichtigen.
- Er braucht kein „Administrator“, und der Einladungslink verlangt es nicht. Hat er es trotzdem (eine von Hand gegebene
  Rolle), sagt er es bei jedem Start in seinem Protokoll: Entzieh es ihm — ein Administrator-Bot, dessen Token leckt,
  öffnet den ganzen Server. Er nennt auch die Rechte des Einladungslinks, die seiner Rolle fehlen.
- Was von Discord kommt — ein Thread-Name, ein Benutzername, ein Kommentar — wird auf der Seite gespeichert wie das, was
  man dort selbst schreibt: Es wird angezeigt, nicht ausgeführt.

## Eine Funktion schreiben

Der Bot wird über **Funktionen** erweitert, jede in einem eigenen Ordner, in TypeScript. Wie das geht — der Vertrag einer
Funktion, ihre Einstellungen, ihre Befehle, ihre übersetzten Texte, ihre Tests — steht im Leitfaden für Mitwirkende des
Bots, `CONTRIBUTING.md`, im Wurzelverzeichnis seines Codes:
[NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
