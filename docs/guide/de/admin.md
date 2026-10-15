# Das Verwaltungspanel

Die Verwaltung liegt unter **/admin**. Sie trägt das Erscheinungsbild von NeoFrag Reborn (dunkles Marineblau +
Petrol, hell oder dunkel nach Wahl) und ist um eine **Seitenleiste** herum aufgebaut.

## Orientierung

- **Seitenleiste**: Die Module sind in neun Rubriken geordnet, dazu *System* (Einstellungen, Benutzer,
  Berechtigungen, Themes & Addons, Monitoring…). Die Bereiche klappen wie ein Akkordeon auf; eine leere Rubrik wird
  ausgeblendet.

  | Rubrik | Module |
  |---|---|
  | *Inhalt* | Seiten, Blog, News, Slider, Zitate, Rezepte, Menüs |
  | *Community* | Forum, Kollektive, Diskussion, Kommentare, Gästebuch, Emojis |
  | *Aktivitäten* | Kalender, Umfragen, Gamification, Kleinanzeigen |
  | *Gaming* | Ereignisse, Teams, Spiele / Karten, Rekrutierungen, Auszeichnungen, Partner |
  | *Wissen* | Wiki, FAQ, Wörterbuch, Downloads, Linkverzeichnis, Ortskarte |
  | *Medien* | Medien, Galerien, Dateien, Webradio |
  | *Verbreitung* | Newsletter, E-Mail-Vorlagen, RSS-Feed, Discord, Webhooks, API |
  | *Support* | Kontakt, Bugtracker, Moderation |
  | *Monetarisierung* | Shop, Spenden, Zahlungen, Werbeverwaltung |

  Eine Erweiterung aus dem Marketplace, die diese Ordnung nicht kennt, landet unter *Andere Module*. Das Menü
  *Navigation* des Live-Editors folgt denselben Rubriken, als Untermenüs, mit einer Suche.
- **Anheften**: Fahre in der Seitenleiste über ein Modul und klicke die Stecknadel, um es zu *Angeheftet*
  hinzuzufügen (Abkürzungen oben, von deinem Browser gemerkt).
- **Schnellsuche**: `Strg/Cmd + K` öffnet die Befehlspalette, um zu jedem Modul oder jeder Aktion zu springen.
- **Kopfzeile**: Brotkrumen + Kontextaktionen (Berechtigungen, Konfiguration, Hilfe), „Website ansehen“ und der
  Hell/Dunkel-Schalter.

## Häufige Aufgaben

| Ich will… | Ich gehe zu |
|---|---|
| Eine News / einen Blogbeitrag / eine Seite veröffentlichen | *Inhalt* → das betreffende Modul |
| Blogbeiträge in Kategorien und Serien ordnen (ein Beitrag in mehreren Teilen) | *Inhalt → Blog*, Knöpfe „Kategorien“ und „Serien“; Serie und Rang eines Beitrags wählt man in seinem Formular |
| Forum und Kommentare verwalten; die Moderation | *Community*; *Support → Moderation* |
| Ein privates Team anlegen — eine Redaktion, ein Moderationsteam — mit eigenem Forenbereich und Discord-Kanal | *Community → Kollektive* — siehe [Kollektive](#kollektive) |
| Mitgliedsbeiträge einnehmen, die Vereinsmitglieder verfolgen | *Monetarisierung → Zahlungen* — siehe [Mitgliedsbeiträge](#mitgliedsbeiträge) |
| Ko-fi, HelloAsso, TipeeeStream oder PayPal mit den Spendenkampagnen verbinden | *Monetarisierung → Spenden*, Karte „Verbundene Dienste“ — siehe [Verbundene Spenden](#verbundene-spenden) |
| Themenpräfixe des Forums anlegen („Frage“, „Anleitung“…), das Symbol eines Forums wählen | *Community → Forum*, Knopf „Präfixe“; das Symbol im Formular des Forums |
| Mitglieder, Gruppen, Sitzungen verwalten | *System → Benutzer* |
| Regeln, wer was darf | *System → Berechtigungen (Matrix)* — Raster Rolle × Aktion (grün = erlaubt, grau = Standard, rot = nie) |
| Das Theme wechseln / ein Addon installieren | *System → Themes & Addons* |
| Einstellungen der Seite (Name, Startseite, Registrierungen, Sicherheit, Copyright) | *System → Einstellungen* |
| Seiten mit der Maus gestalten | *System → Live-Editor* |
| Die Seite mit einem Discord-Server verbinden: Rollen, Spitznamen, Forum ↔ Forum-Kanal, Bugtracker, befristete Rollen, Sanktionen | *Verbreitung → Discord* — siehe [Der Discord-Bot](bot-discord.md) |
| Sicherungen, Updates, Zustand der Seite | *System → Monitoring* |
| Die Fehler der Seite lesen, den finden, den ein Besucher gemeldet hat | *System → Monitoring → Fehlerprotokoll* |
| Sensible Aktionen nachlesen (Einstellungen, Addons, Konten, API-Schlüssel) | *System → Benutzer → Audit-Log* |

> **Monitoring** vereint den **Zustand der Seite** (Integritätsprüfungen der Dateien, Speicherplatz, Serverinfos), die
> **Updates**, die **Sicherungen** (anlegen / herunterladen / wiederherstellen) und die Adresse der **geplanten
> Aufgabe** (Cron), die geplante Inhalte veröffentlicht.

### Monitoring

*System → Monitoring* fasst oben das Wichtigste zusammen — Version und Update, PHP, Sicherungen, eingeschaltete
Diagnosewerkzeuge — und gliedert sich dann in fünf Reiter: *Übersicht* (Warnungen, Zustand der Seite, Speicher,
Adresse der Seite), *Sicherungen*, *Diagnose*, *Server und Sicherheit* (Konfiguration, Webmaster-Passwort, geplante
Aufgabe) und *Dateien* (die Installation verglichen mit der veröffentlichten Version). Die Seite kehrt zu dem Reiter
zurück, auf dem du warst; eine Adresse wie `admin/monitoring#diagnostic` öffnet den richtigen.

Jedes Update über den Knopf legt zuerst eine vollständige **Sicherung** der Seite an, Datenbank eingeschlossen. Die
Seite behält immer die **fünf neuesten**; die anderen entfernen sich nach **dreißig Tagen** selbst, und nie bleiben
mehr als **zehn**. Lade die herunter, die du länger behalten willst.

### Das Fehlerprotokoll

*Monitoring → Fehlerprotokoll* zeigt, was auf der Seite fehlgeschlagen ist, ohne FTP: Fehler gruppiert (derselbe
Fehler hundertmal gesehen bleibt eine Zeile), vom neuesten zum ältesten, nach Schwere geordnet — **fatal**,
**Fehler**, **Warnung**, **Information** —, mit ihrem letzten vollständigen Auftreten zum Aufklappen. Man filtert nach
Schwere und Zeitraum und sucht nach einem Wort.

Wenn eine Seite abstürzt, liest der Besucher „Ein Fehler ist aufgetreten“ und eine achtstellige **Referenz**
(`3F9A1C07`); gibt er sie dir weiter, such sie im Protokoll: Sie führt zur genauen Zeile. Serverpfade, Passwörter,
Schlüssel, E-Mail-Adressen und IP-Adressen werden am Bildschirm maskiert; die ganze Datei lässt sich
**herunterladen** (um sie an jemanden zu schicken, der dir hilft) und **leeren** (die alte bleibt daneben erhalten,
`logs/php.log.1`). Das Monitoring meldet auch die Fehler der letzten 24 Stunden und einen Ordner `logs/`, in den die
Seite nicht mehr schreiben kann.

### Die Diagnosewerkzeuge

Der Reiter *Monitoring → Diagnose* schaltet drei Werkzeuge für eine Stunde ein; jedes schaltet sich selbst ab, oder
per Klick. Kein Bearbeiten von `config/neofrag.php` per FTP mehr.

- **Debug-Modus**: Unten auf den Seiten erscheint eine Leiste (Datenbankabfragen, Rechenzeit, Speicher, Daten des
  Besuchs), und eine abstürzende Seite zeigt die Details des Fehlers — **nur für angemeldete Administratoren**: Besucher
  sehen die normale Seite. Das Protokoll sagt dir, dass es ein Problem gibt; der Debug-Modus hilft, es zu verstehen,
  indem du es nachstellst.
- **Seitenprotokoll**: jede ausgelieferte Seite, mit ihren Datenbankabfragen und deren Dauer. *Protokoll lesen* zeigt
  die letzten Seiten, die neueste zuerst, durchsuchbar nach Adresse; die an Abfragen übergebenen Werte werden am
  Bildschirm maskiert, die vollständige Datei lässt sich herunterladen. Umfangreich: für die Dauer einer Diagnose.
- **Übersetzungsprüfung**: Texte ohne Übersetzung in der Sprache des Besuchs werden notiert und unter *Fehlende
  Übersetzungen* aufgeführt, mit der Erweiterung, die sie verwendet. Währenddessen sehen Administratoren die Flagge
  der Sprache vor jedem übersetzten Text: Ein Text ohne Flagge ging nicht durch die Übersetzung.

Ein in `config/neofrag.php` eingeschaltetes Werkzeug lässt sich nur dort ausschalten; die Karte weist darauf hin.

### Die Adresse der Seite

Die Links in E-Mails (vergessenes Passwort, Bestätigung der Registrierung), die Rückkehr von externen Anmeldungen und
das Teilen bauen auf der Adresse auf, die bei der Installation gespeichert wurde. Nach einem Domainwechsel meldet es
die Karte *Adresse der Website* (Monitoring, Reiter *Übersicht*): Öffne die Verwaltung über die neue Adresse, dann
speichert der Knopf mit dieser Adresse und *verwenden* sie (Webmaster-Passwort wird verlangt, falls eines
gesetzt ist).

## Berechtigungen

NeoFrag Reborn arbeitet mit **Rollen**. Die **Berechtigungsmatrix** (pro Modul) regelt für jede Rolle Zugriff und
Aktionen. Du weist die Rollen unter *System* Mitgliedern und Gruppen zu.

**Beispiel — eine Rolle „Moderator“ anlegen**:

1. Unter *System → Berechtigungen* **legst du eine Rolle** „Moderator“ an.
2. In ihrer Spalte der **Matrix** hakst du die gewünschten Aktionen an (das Forum moderieren, Kommentare verwalten…)
   → **grün = erlaubt**.
3. Unter *System → Benutzer* bearbeitest du ein Mitglied und **weist ihm diese Rolle zu**: Es erhält diese Zugriffe
   sofort.

### Kollektive

Ein **Kollektiv** vereint Mitglieder um ein Projekt, abgeschirmt von allen anderen: eine Redaktion, ein
Moderationsteam, eine Gruppe von Kreativen. Es ist kein Gaming-Team (*Gaming → Teams*, das öffentlich gezeigt wird):
Alles, was ein Kollektiv erhält, ist ihm vorbehalten.

*Community → Kollektive → Neues Kollektiv*: sein Name, seine Beschreibung und sein **Leiter** (ein Benutzername). Die
Seite legt auf einen Schlag an:

- einen **privaten Forenbereich** — eine Kategorie und ihr Forum „Discussions“ —, in dem nur seine Mitglieder lesen
  und schreiben;
- zwei **Rollen** der Seite, *Kollektiv „Name“* und *… — Moderation*, die das Modul selbst vergibt und entzieht:
  Vergib sie nicht von Hand;
- wenn das Feld *Discord* angehakt und der Bot eingeschaltet ist (Funktion *Kollektive*), eine **Rolle und einen
  privaten Kanal** auf dem Server — siehe [Der Discord-Bot](bot-discord.md#kollektive).

Der Leiter verwaltet die Mitglieder danach auf der Seite des Kollektivs (*Meine Kollektive*, im Mitgliederbereich),
ohne Administrator der Seite zu sein. Die Ränge:

| Rang | Darf |
|---|---|
| Leiter | Mitglieder hinzufügen, Administratoren und Moderatoren ernennen, jeden unter sich entfernen |
| Administrator | Mitglieder hinzufügen, Moderatoren ernennen, Moderatoren und Mitglieder entfernen |
| Moderator | den Forenbereich moderieren (bearbeiten, löschen, anheften, sperren) |
| Mitglied | im Bereich lesen und schreiben |

Der Leiter wird nur in der Verwaltung ernannt: Ein neuer Leiter ersetzt dort den alten, der als Administrator des
Kollektivs bleibt. Ein Kollektiv zu löschen nimmt seinen Mitgliedern den Bereich; sein Forenbereich und sein
Discord-Kanal **bleiben**, nur für Administratoren sichtbar — was dort geschrieben wurde, geht nicht verloren; lösche
sie von Hand, wenn du willst.

### Mitgliedsbeiträge

*Monetarisierung → Zahlungen*. Ein Beitrag macht ein Mitglied für eine Dauer zum **Vereinsmitglied**: Es erhält die
Rolle **„Vereinsmitglied“** — öffne ihr unter *System → Berechtigungen*, was du willst (einen Forenbereich, Seiten) —
und gehört zur Gruppe **„Vereinsmitglieder“** (ihr Abzeichen, und eine Discord-Rolle, wenn du sie verbindest). Zwei
Wege der Einnahme:

- **online**, über Stripe: ein Paket vom Typ *Mitgliedschaft (Tage)* („Jahresbeitrag“, 365 Tage), das das Mitglied
  auf der Seite *Zahlungen* bezahlt;
- **anders** (bar, Scheck, Überweisung): die Karte *Vereinsmitglieder* → *Einen Beitrag erfassen* (Benutzername,
  Dauer).

Ein neuer Beitrag verlängert die Mitgliedschaft ab ihrem Enddatum. Sieben Tage vorher benachrichtigt die geplante
Aufgabe das Mitglied (eine Benachrichtigung); am Enddatum entzieht sie ihm die Rolle. *Mitgliedschaft beenden* stoppt
sie sofort (ein Eingabefehler, eine Erstattung). Mitgliedsbeiträge brauchen keine Gamification — Punkte und VIP
schon.

### Verbundene Spenden

*Monetarisierung → Spenden*, Karte **„Verbundene Dienste“**: Eine Spende über einen dieser Dienste wird von selbst in
der gewählten Kampagne erfasst, **nur einmal**, auch wenn der Dienst zweimal Bescheid gibt. Die Schlüssel werden
verschlüsselt gespeichert und nie wieder angezeigt.

| Dienst | Was du brauchst | Wie die Seite prüft |
|---|---|---|
| **Ko-fi** | auf Ko-fi, *More → API → Webhooks*: die in der Karte angezeigte Adresse und das Verifizierungstoken | das Token, das nur die Seite kennt |
| **HelloAsso** | im Bereich des Vereins, *Mein Konto → Integrationen und API*: Client-ID und Client-Geheimnis, und die angezeigte Benachrichtigungsadresse | sie liest die Bestellung über die API von HelloAsso erneut: die Benachrichtigung allein gilt nicht |
| **TipeeeStream** | den API-Schlüssel des Kontos | nichts zu prüfen: Die Seite liest die Spenden selbst, bei jeder geplanten Aufgabe, ab dem Tag, an dem der Schlüssel gespeichert wird |
| **PayPal** | das Feld *Spenden automatisch erfassen* | PayPal bestätigt jede Benachrichtigung, und das Geld muss an die PayPal-Adresse der Kampagne gegangen sein |

Eine auf HelloAsso bezahlte **Mitgliedschaft** macht das Mitglied der Seite mit derselben E-Mail-Adresse für die
eingestellte Dauer zum Vereinsmitglied (Modul Zahlungen). Spender von HelloAsso und PayPal erscheinen nicht auf der
Seite der Kampagne — sie haben nicht zugestimmt —; die von Ko-fi und TipeeeStream, die es wollten, schon. Die Summe
zählt alle Spenden. Zum Ausprobieren ohne echtes Geld: die *Sandbox*-Felder von HelloAsso und PayPal.

## Wichtige Einstellungen

Unter **Einstellungen**: Titel und Beschreibung der Seite, Favicon, Kontakt-E-Mail, Startseite, Zeitzone,
Suchmaschinen, Registrierungen, Bot-Schutz (Captcha), Wartung, Copyright, soziale Netzwerke. Diese Einstellungen
speisen die Themes und Widgets.

### Registrierungen

*Einstellungen → Registrierung* öffnet oder schließt die Registrierungen, kann neue Mitglieder **ihre E-Mail-Adresse
bestätigen** lassen und regelt zwei Texte, **Sprache für Sprache** (ein Reiter pro Sprache der Seite).

Die **Bestätigung per E-Mail** (standardmäßig aus): Bei der Registrierung erhält das Mitglied einen Link, zwei Tage
gültig, und kann sich nicht anmelden, bevor es ihn geöffnet hat; ein Anmeldeversuch schickt einen neuen (höchstens
drei pro Stunde). Eine Registrierung über Discord, GitHub oder Google braucht ihn nicht. Die beiden Texte:

- die **Regeln**, die der Besucher bei der Registrierung annimmt — über das Formular wie über Discord, GitHub oder
  Google, die sie vor dem Anlegen des Kontos zeigen;
- die **Willkommensnachricht**, die dem neuen Mitglied per Privatnachricht geschickt wird — auch wenn es sich mit
  Discord, GitHub oder Google registriert —, in der Sprache der Seite, auf der es sich registriert hat. `[pseudo]`
  wird zu seinem Benutzernamen. Sie kommt als Text an: Überschriften, Listen und Links bleiben, die Formatierung
  nicht; eine ausgeschriebene Adresse wird zu einem Link.

Eine Sprache, die ihren Text noch nicht hat, zeigt den gemeinsamen Text: Der zuerst gespeicherte Text dient allen
Sprachen, bis sie übersetzt sind.

### Suchmaschinen

Was Suchmaschinen lesen, geschieht **von selbst**:

- eine **Sitemap** pro Sprache, im Wurzelverzeichnis gesammelt (`/sitemap.xml`) und von `robots.txt` angekündigt —
  jedes Modul trägt seine öffentlichen Seiten ein (der Blog, das Forum und seine Themen, das Wiki, die Galerie…), nur
  die, die ein Besucher lesen kann;
- in jeder Seite ihre **Referenzadresse** (kanonisch), **Links zu ihren anderen Sprachen** und das, was
  Teilen-Vorschauen zeigen (Titel, Beschreibung, Bild);
- auf der Startseite **strukturierte Daten**, die die Seite, ihr Logo und ihre sozialen Netzwerke beschreiben
  (*Einstellungen → Soziale Netzwerke*);
- die Suche, die Profile und das Mitgliederverzeichnis werden **nicht** indexiert.

Was du ausfüllst, unter **Einstellungen → Suchmaschinen**:

- für jede Sprache einen **Slogan** (der Titel der Startseite wird zu „Name der Seite — Slogan“, höchstens 60 Zeichen)
  und eine **Beschreibung** (was Suchmaschinen unter dem Titel zeigen, höchstens 160 Zeichen; leer dient die aus den
  Allgemeinen Einstellungen);
- ein **Teilen-Bild** von 1.200 × 630 Pixeln, gezeigt, wenn man einen Link der Seite auf Discord, X oder Facebook
  einfügt — sonst das Logo;
- den Verifizierungscode der **Google Search Console** und der **Bing Webmaster Tools**: Lege die Property der Seite im
  Werkzeug an, wähle die Verifizierung per **HTML-Tag**, füge das Tag (oder nur den Code) ein und reiche dort die
  Sitemap ein, `https://deine-seite/sitemap.xml`;
- **Suchmaschinen benachrichtigen (IndexNow)**: Angehakt meldet die Seite jede Seite, die erscheint, sich ändert oder
  verschwindet, innerhalb von Minuten an Bing, Yandex, Seznam, Naver, Yep und Amazon, statt auf ihren nächsten Besuch
  zu warten. Google macht nicht mit: Für Google ist es die Sitemap. Die **geplante Aufgabe** der Seite muss laufen
  (*Monitoring* nennt die einzurichtende Zeile). Der erste Durchlauf erfasst nur die vorhandenen Seiten; danach gehen
  nur Änderungen hinaus.

Drei weitere Bildschirme, aus derselben Rubrik:

- **SEO-Bericht** — was eine Suchmaschine sieht, auf der Seite gemessen: die Zahl der Seiten der Sitemap und ihr
  Modul, die Texte der Startseite in jeder Sprache, das Teilen-Bild, Google und Bing, IndexNow, `robots.txt`, die
  Wartung. Jeder Punkt verlinkt, was ihn behebt.
- **Weiterleitungen** — eine alte Adresse, die zur neuen führt, statt „Seite nicht gefunden“: Eine erarbeitete
  Platzierung geht nicht verloren. Eine **umbenannte** Seite oder Wiki-Seite hinterlässt ihre Weiterleitung von
  selbst; die einer alten Seite fügst du von Hand hinzu (`page.php`, `/fr/alte-seite`…). Die Spalte *Besuche* sagt,
  ob die alte Adresse noch genutzt wird.
- **Die Suchmaschinen-Einstellungen eines Inhalts** — in der Bearbeitungskarte einer News, eines Blogbeitrags, einer
  Seite oder einer Wiki-Seite der Knopf **Suchmaschinen**: ein Titel für Suchmaschinen (höchstens 60 Zeichen, der Name
  der Seite folgt) und eine Beschreibung (höchstens 160), in jeder Sprache. Leer sind es der Titel und der Anfang des
  Textes.

### Das Captcha

Das Kontaktformular und die Registrierung verlangen von Besuchern eine Prüfung gegen Roboter. Unter **Einstellungen →
Captcha** wählst du den Anbieter:

- **ALTCHA** (Standard, empfohlen): nichts einzurichten. Es wird von deiner Seite gehostet, ohne Konto und ohne
  Cookie; der Besucher sieht ein Feld, das sich von selbst anhakt, während er das Formular ausfüllt.
- **Cloudflare Turnstile**, **hCaptcha** oder **Google reCAPTCHA v2**: Lege in ihrer Konsole eine Seite an (Links auf
  der Seite) und füge dann den **Website-Schlüssel** und den **geheimen Schlüssel** ein. Der geheime Schlüssel wird
  verschlüsselt und nie wieder angezeigt. Ohne seine beiden Schlüssel wird der Anbieter durch ALTCHA ersetzt.
- **Keiner**: nicht empfohlen, Roboter schreiben dann frei.

ALTCHA macht Massenversand für einen Roboter teuer; gegen einen hartnäckigen Angreifer urteilen Turnstile oder
hCaptcha strenger.

### Die Zeitzone

Jeder sieht die Uhrzeiten der Seite in seiner eigenen Zeitzone:

- ein **Mitglied** kann seine in seinem Profil wählen (**Profil → Zeitzone**);
- sonst nimmt die Seite die seines **Browsers**, ab seiner zweiten Seite;
- sonst — erster Besuch, Roboter, automatische Aufgaben — die der **Seite**, eingestellt unter **Einstellungen →
  Allgemeine Einstellungen → Zeitzone**.

Eine in einem Formular eingegebene Uhrzeit (der Beginn eines Ereignisses, die Wiedereröffnung nach einer Wartung)
gilt in der Zeitzone dessen, der sie eingibt. Ein Datum allein — ein Geburtstag, ein ganzer Tag im Kalender —
wechselt nie den Tag. Der Sendeplan eines Webradios ist in der Uhrzeit der Seite geschrieben.

### Push-Benachrichtigungen

Ein Mitglied kann seine Benachrichtigungen im Browser erhalten, **auch bei geschlossener Seite** — ein Forenbeitrag,
eine Reaktion, eine Ereigniserinnerung. Um es anzubieten, hake **Einstellungen → Allgemeine Einstellungen →
Push-Benachrichtigungen** an. Die Seite muss über **https** ausgeliefert werden, und der Server verschlüsseln können
(OpenSSL mit der Kurve P-256, und cURL: das haben fast alle Hoster; sonst bleibt das Feld leer und die Seite sagt
warum).

Niemand erhält etwas, bevor er es verlangt hat: Jedes Mitglied schaltet es **Gerät für Gerät** ein, unter **Mein
Konto → Benachrichtigungseinstellungen → Dieses Gerät**, und sein Browser bittet um seine Zustimmung. Dort wählt es
auch, Art für Art, was es so erhält, und kann sich einen Test schicken.

Was du wissen und in deiner **Datenschutzerklärung** sagen musst: Der Browser geht über seinen Push-Dienst — Google
für Chrome und Edge, Mozilla für Firefox, Apple für Safari. Die Seite speichert dort die Abonnementadresse jedes Geräts,
solange das Mitglied Push nicht abschaltet (oder sein Konto nicht löscht). Die Nachricht ist nur für diesen Browser
verschlüsselt: Der Dienst transportiert sie, ohne sie lesen zu können.
