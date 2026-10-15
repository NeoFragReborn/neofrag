# Changelog

Dies ist die deutsche Übersetzung von [CHANGELOG.md](CHANGELOG.md); maßgeblich ist die französische Fassung.

Alle nennenswerten Änderungen an **NeoFrag Reborn** werden hier festgehalten.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/);
die Versionsnummern orientieren sich an der [semantischen Versionierung](https://semver.org/lang/de/); eine Version, die
nur die letzte Ziffer ändert (1.2.x), kann dennoch Neuerungen und Entfernungen bringen.

NeoFrag Reborn ist die gemeinschaftliche Fortführung von **NeoFrag** (Basis Alpha 0.2.4), ursprünglich geschaffen von
Michaël BILCOT und Jérémy VALENTIN — ein Open-Source-Projekt unter der Lizenz LGPLv3.

---

## [1.2.50] — 2026-10-15

### Hinzugefügt

- **Ein Theme zeigt die Sprachauswahl mit einer Zeile an**: `nf_selecteur_langue()`, neben
  `nf_selecteur_theme()` und `nf_liens_legaux()`. Die mitgelieferten Themes kopierten jeweils ihr Formular.
- **Die Dokumentation und die Versionshinweise, in allen sechs Sprachen.** Die Wiki-Anleitungen — Installation, erste
  Schritte, Administration, Marketplace, mehrsprachige Website, Discord-Bot, Framework, ein Theme, ein Modul oder ein
  Widget erstellen — gab es nur auf Französisch und Englisch: Sie sind jetzt auch auf Deutsch, Spanisch, Italienisch
  und Portugiesisch geschrieben, mit den genauen Bezeichnungen, die der Bildschirm in jeder Sprache zeigt, und das Wiki
  liefert sie in der Sprache des Besuchers aus. Die Versionshinweise folgen: Die Wiki-Seite „Versionshinweise“ und die
  Blogbeiträge lassen sich in allen sechs Sprachen lesen. Maßgeblich bleibt das Französische; ein Test lehnt eine
  Übersetzung ab, die eine Version auslässt oder anders datiert.
- **Die Vorschaubilder der Module und Widgets, neu gezeichnet.** In *Themes & Addons* und im Marketplace war die
  Vorschau einer Erweiterung ein Foto von ihr bei der Arbeit — unlesbar, wenn sie keine Daten zu zeigen hatte: ein
  Rahmen, ein Titel, ein paar Zeilen. Jedes Modul und jedes Widget erhält jetzt eine gezeichnete Karte in der
  Bildsprache des Projekts: sein Symbol, sein Name und was es tut, in jeder Größe lesbar. Die Themes behalten das Foto
  ihrer Startseite: Ein Theme zeigt sich selbst.
- **Die mitgelieferten Bibliotheken auf dem neuesten Stand, und eine Schutzvorrichtung, damit sie es bleiben.** Der
  Texteditor wechselt zu TinyMCE 8 (die Reihe 7 verlor am 14. November ihre Unterstützung, und 8.9.3 behob eine Lücke in
  ihrem Plugin „media“) und die Symbole zu Font Awesome 7 — die 363 Symbole, die die Website verwendet, wurden vor dem
  Umstieg mit der neuen Sammlung abgeglichen, keines fehlt. Die doppelten Schriftdateien (.ttf) entfallen: Version 7
  liefert nur noch das moderne Format, 715 KB weniger im Paket. Vor allem wacht eine neue Prüfung: Sie schlägt nicht
  bei jeder Veröffentlichung der Upstream-Projekte Alarm, lehnt aber ab, wenn die ausgelieferten Versionen seit mehr als
  drei Monaten nicht mit dem Register abgeglichen wurden. Eine Abhängigkeit wird nicht auf einen Schlag gefährlich —
  sie driftet ab, und diese Drift ist jetzt sichtbar.
- **Push-Benachrichtigungen.** Ein Mitglied kann seine Benachrichtigungen im Browser erhalten, auch wenn die Website
  geschlossen ist: eine Forennachricht, eine Reaktion, eine Erinnerung an ein Event. Der Administrator bietet es an
  (Einstellungen → Allgemeine Einstellungen → Push-Benachrichtigungen); jedes Mitglied schaltet es dann Gerät für Gerät
  in seinen Benachrichtigungseinstellungen ein, wählt Typ für Typ, was es auf diesem Weg erhält, und kann sich einen
  Test schicken. Ohne zusätzliche Bibliothek: Die Nachricht wird für diesen einen Browser verschlüsselt (RFC 8291) und
  im Namen der Website signiert (VAPID, RFC 8292) — der Push-Dienst des Browsers überträgt sie, ohne sie lesen zu
  können. Der Server schreibt nur an bekannte Push-Dienste (Google, Mozilla, Apple, Microsoft), nie an eine Adresse,
  die sich ein Mitglied ausgedacht haben könnte.
- **Nebula und das Schaufenster-Theme erhalten eine Farbseite, und alle acht Themes eine Live-Vorschau.** Akzent,
  Hintergrund, Text: Die Paletten von Nebula und des Schaufensters standen in ihren Stylesheets; sie lassen sich jetzt
  wie die der sechs anderen Themes einstellen, mit derselben Ablehnung einer unlesbaren Farbe. Und unter den
  Farbwählern jedes Themes übernimmt eine echte Seite der Website — Startseite, News oder Forum — die Farben, während
  man sie wählt, ohne etwas zu speichern, mit live gemessenen Kontrasten: Man sieht das Ergebnis, bevor man auf
  „Speichern“ klickt.
- **Menülinks, öffentliche Räume, der eigene Titel eines Widgets und die Wiki-Seiten werden übersetzt.** Damit bleibt
  kein Text, den der Betreiber schreibt, mehr ohne Sprache. Eine Wiki-Seite führt einen Verlauf PRO SPRACHE: Eine
  Übersetzung ins Italienische archiviert nichts von der französischen Fassung, und der italienische Verlauf zeigt nur
  seine eigenen Revisionen. Eine Unterhaltung zwischen Mitgliedern behält einen einzigen Namen für alle Beteiligten —
  nur öffentliche Räume werden übersetzt. Die Wiki-Seite „Versionshinweise“ gibt es jetzt auch auf Englisch.
- **Shop, Spenden, Werbung, Zahlungspakete und Rekrutierung werden übersetzt.** Name und Beschreibung eines
  Shop-Artikels, Titel und Beschreibung einer Spendenkampagne, der Titel einer Werbung, die Bezeichnung eines Pakets und
  ein ganzes Rekrutierungsangebot — Titel, Einleitung, Beschreibung, Voraussetzungen und die Fragen seines
  Bewerbungsformulars — werden jetzt Sprache für Sprache geschrieben. Die Fragen hatten keinen Bildschirm, um sie zu
  bearbeiten (man konnte sie nur hinzufügen oder löschen): Ein Stift öffnet sie jetzt, und dort werden sie übersetzt.
  Gemeinsam bleibt, was kein Text ist: der Preis, der Bestand, die Adresse einer Kampagne, der Code eines Werbenetzes,
  die im Team vergebene Rolle — und die Antworten der Bewerber, die die Frage so behalten, wie sie sie gelesen haben.
- **Spielmodi, Karten, Sendungen des Webradios und Profilfelder werden übersetzt.** „Compétitif 5v5“ wird für einen
  englischsprachigen Besucher zu „Competitive 5v5“, „Le réveil du serveur“ zu „The server wake-up“, und die
  Bezeichnung eines Profilfelds wird in jeder Sprache geschrieben. Ein Kartenname bleibt ein Eigenname, wird aber
  ebenfalls pro Sprache geschrieben, für wen „Faille de l'invocateur“ übersetzen möchte. Eine Entscheidung ist
  getroffen, und der Bildschirm sagt es: Die LISTE DER OPTIONEN eines Profilfelds bleibt gemeinsam — eine Option wird
  unverändert in der Antwort des Mitglieds gespeichert, und sie zu übersetzen, würde die bereits gegebenen Antworten
  unauffindbar machen.
- **Event-Typen, Team-Rollen und Rubriken der Kleinanzeigen werden übersetzt.** „Turnier“, „Kapitän“, „Hardware“:
  drei kurze Namen, die der Betreiber schreibt und die eine Website in sechs Sprachen in nur einer zeigte. Sie werden
  jetzt Sprache für Sprache geschrieben, und Besucher sehen sie überall in ihrer eigenen Sprache — in der Eventliste und
  auf den Eventseiten, im Kalender, in der Aufstellung eines Teams, im Verzeichnis der Kleinanzeigen und in den
  Administrationsbildschirmen.
- **Eine Umfrage lässt sich bearbeiten, ohne ihre Stimmen zu verlieren — und sie lässt sich übersetzen.** Bisher löschte
  das Speichern einer Umfrage alle ihre Antworten, um sie neu anzulegen, und mit ihnen alle STIMMEN: Ein Komma in der
  Frage zu korrigieren, kostete die ganze Umfrage (der Bildschirm sagte es, was es nicht billiger machte). Eine Antwort
  behält jetzt ihre Stimmen, solange sie in der Liste bleibt — korrigiert, verschoben oder übersetzt; nur eine
  ENTFERNTE Antwort verliert sie, und der Bildschirm sagt es mit ihrer Anzahl. Erst das macht eine Umfrage übersetzbar:
  die Frage, ihre Beschreibung und die Bezeichnung jeder Antwort werden Sprache für Sprache geschrieben, und das
  Schreiben der italienischen Fassung berührt die französische nicht mehr.
- **Downloads, nützliche Links und die FAQ werden übersetzt.** Titel und Beschreibung einer Datei oder eines Links,
  eine Frage und ihre Antwort sowie die Titel ihrer Kategorien hatten keine Sprache: Eine Website in sechs Sprachen
  zeigte sie in nur einer — sichtbar auf der englischen Demo, die „Pack de fonds d'écran“ und „Chaîne Twitch“
  ankündigte. Sie werden jetzt Sprache für Sprache geschrieben, wie News oder Events: Der Bearbeitungsbildschirm öffnet
  die Sprache, in der man arbeitet, und sagt, welche, und Besucher sehen sie in ihrer Sprache — in der Bibliothek, im
  Linkverzeichnis, in den Widgets und in den Blöcken. Was keine Sprache hat (Adresse, Größe, Typ, Version, Zähler),
  bleibt gemeinsam, und was nur in einer Sprache geschrieben ist, bleibt in den fünf anderen sichtbar.
- **Eine Theme-Farbe, die die Website unlesbar machen würde, wird abgelehnt.** Die sechs Themes, bei denen man Akzent-,
  Text- und Hintergrundfarbe wählen kann, nahmen alles an: einen Akzent, der fast dem Hintergrund glich, und Links und
  Schaltflächen verschwanden; einen zu blassen Text, und die Seite war nicht mehr lesbar. Nichts warnte davor, und man
  musste die vorherige Farbe selbst wiederfinden. Jede Farbe wird jetzt an den beiden anderen gemessen — den
  gespeicherten, da die beiden Formulare getrennt speichern — und mit ihrem Wert abgelehnt, wenn sie sich nicht genug
  abhebt: 4,5 zu 1 für Text, 3 zu 1 für einen Akzent, wie es der Barrierefreiheitsstandard verlangt. Die
  Originalfarben aller neun Themes bestehen die Prüfung.
- **Die mitgelieferten Bibliotheken aktualisiert.** Der Texteditor (TinyMCE 7.6.1 → 7.9.3, das einen neueren
  HTML-Bereiniger mitbringt), die Statistikdiagramme (Chart.js 4.4.7 → 4.5.1) und die Auswahllisten (Tom Select 2.6.1 →
  2.6.2). Die drei anderen — Bootstrap, flatpickr, Sortable — waren bereits auf der neuesten veröffentlichten Version.
  Die von Hand korrigierten Übersetzungen des Editors bleiben erhalten.
- **Einunddreißig Bildschirmtitel sprachen in allen sechs Sprachen Französisch.** „Updates“, „Neue Karte“, „Meine
  Meinung zur Bewerbung“, „Mein Konto verwalten“, „Partner“, „Rekrutierungsangebote“…: Titel von Panels, Fenstern und
  Schaltflächen liefen nicht durch die Übersetzungen und erschienen auf Französisch, gleich in welcher Sprache die
  Website war. Ihre Übersetzungen existierten bereits in allen sechs Sprachen — nur der Aufruf fehlte. Die Prüfung, die
  über fest eingetragene Texte wacht, ließ sie durch, weil sie annahm, diese Titel würden vom Produkt übersetzt, was
  tatsächlich nur bei FORMULAR-Bezeichnungen geschieht; sie unterscheidet die beiden jetzt.
- **Drei Bildschirme erhalten das Design ihres Themes zurück.** Das Stylesheet eines Widgets wird nach dem des Themes
  ausgeliefert: Wo beide dasselbe mit demselben Gewicht sagten, gewann das Widget, und das Theme zeichnete ins Leere —
  ein vorzeigbarer Bildschirm, ein gewolltes Design, das nie ausgeliefert wurde, und nichts, das es sagte. Das rechte
  Panel von *Extend* (wer online ist, der Chatraum) und seine großen Zahlen erhalten ihre Schrift und Farben zurück;
  die Profil-Reiter von *Forge* sind wieder eckig, unterstrichen und in Großbuchstaben, wie bei seinen
  Geschwister-Themes; und die Gesundheitsanzeige des *Monitorings* passt endlich in ihren Kreis, statt überzulaufen.
  Eine Prüfung lehnt jetzt jede Theme-Deklaration ab, die nie greifen könnte.
- **Events werden übersetzt.** Titel, Beschreibung, private Beschreibung und Ort eines Events hatten keine Sprache:
  Eine Website in sechs Sprachen zeigte sie in nur einer. Sie werden jetzt Sprache für Sprache geschrieben — der
  Bearbeitungsbildschirm öffnet die Sprache, in der man arbeitet, und sagt, welche. Besucher sehen ein Event überall,
  wo es erscheint, in ihrer Sprache: in der Liste, auf der Eventseite, im Kalender, in den Erinnerungen per E-Mail, in
  der Sitemap und im Widget. Was keine Sprache hat (die Daten, der Typ, das Bild, der Autor, die Teilnehmer), bleibt
  gemeinsam; ein in nur einer Sprache geschriebenes Event bleibt in den fünf anderen sichtbar.
- **Der Palmarès wird übersetzt.** Name eines Titels, sein Ort und sein Bericht hatten keine Sprache: Eine Website in
  sechs Sprachen zeigte sie in nur einer. Sie werden jetzt Sprache für Sprache geschrieben, wie der Rest — der
  Bearbeitungsbildschirm öffnet die Sprache, in der man arbeitet, und sagt, welche. Was keine Sprache hat (Datum,
  Platzierung, Teilnehmerzahl, Plattform, Team, Spiel), bleibt gemeinsam.
- **Die Diashow wird übersetzt.** Titel und Bildunterschrift einer Folie hatten keine Sprache: Eine Website in sechs
  Sprachen zeigte sie in nur einer. Sie werden jetzt Sprache für Sprache geschrieben, wie eine Seite oder eine
  Kategorie — die Administration schreibt in der Sprache, in der sie arbeitet, und sagt es über dem Feld. Besucher sehen
  die Folie in ihrer Sprache; eine in nur eine Sprache übersetzte Folie bleibt in den fünf anderen sichtbar (der Text
  der angefragten Sprache, sonst Französisch, sonst der zuerst geschriebene), und eine Folie ganz ohne Text behält ihr
  Bild. Beim Update geht nichts verloren: Der heutige Text wird in alle installierten Sprachen kopiert, da ihn heute
  jeder Besucher sieht.
- **Die Sanktionen der Website folgen dem Mitglied auf Discord.** Mit dem Bot 0.3.0 und seiner Funktion *Verknüpfte
  Sanktionen* (anfangs ausgeschaltet) gelten websiteweite Stummschaltungen und Sperren für das verknüpfte
  Discord-Konto des Mitglieds — ein Timeout für eine Stummschaltung, ein Bann für eine Sperre — und werden auf Discord
  aufgehoben, wenn sie auf der Website aufgehoben werden. Eine Verwarnung, eine Einschränkung oder ein Shadow-Ban
  werden nie übertragen. In der Gegenrichtung, und nur wenn man es einschaltet: Ein Moderator, dessen Discord-Konto mit
  einem dazu berechtigten Konto der Website verknüpft ist, kann auf Discord bannen oder einen Timeout verhängen, und die
  Website empfängt die Sanktion und beurteilt sie erneut, als wäre sie bei ihr ausgesprochen worden. Die
  Sanktionsliste sagt jetzt, woher jede einzelne stammt.
- **Kollektive.** Ein neues Modul bringt Mitglieder um ein Projekt zusammen, abgeschirmt von den anderen: eine
  Redaktion, ein Moderationsteam, eine Gruppe von Kreativen. In der Administration angelegt (*Community → Kollektive*),
  erhält ein Kollektiv einen privaten Forenbereich, den nur seine Mitglieder lesen, und — mit dem Bot 0.3.0 und seiner
  Funktion *Kollektive* — eine Rolle und einen privaten Kanal auf Discord. Sein Leiter fügt Mitglieder hinzu und gibt
  ihnen einen Rang (Administrator, Moderator, Mitglied) von der Seite des Kollektivs aus, ohne Administrator der
  Website zu sein; die Moderatoren moderieren seinen Bereich. Wird ein Kollektiv gelöscht, bleiben sein Bereich und
  sein Kanal, nur für Administratoren: Nichts, was dort geschrieben wurde, geht verloren.
- **Mitgliedsbeiträge.** Ein Verein zieht seine Mitgliedsbeiträge über die Website ein: Ein Mitglied zahlt online
  (Stripe) — oder die Administration erfasst einen bar oder per Scheck gezahlten Beitrag — und wird für den gewählten
  Zeitraum **Vereinsmitglied**. Es erhält die Rolle „Vereinsmitglied“, die man für beliebige Bereiche öffnen kann
  (einen reservierten Forenbereich, Seiten), und das Abzeichen der Gruppe „Vereinsmitglieder“. Sieben Tage vor Ablauf
  wird es erinnert; bei Ablauf wird ihm die Rolle entzogen. Die Mitgliedsbeiträge brauchen das Modul Gamification
  nicht, das Zahlungen bisher voraussetzten.
- **Anderswo getätigte Spenden erfassen sich selbst.** Ko-fi, HelloAsso, TipeeeStream und der PayPal-Button der
  Kampagnen: Jede Spende landet in der gewählten Kampagne, nur einmal, und der Fortschrittsbalken bewegt sich ohne
  manuelle Eingabe. Nichts wird ohne Nachweis erfasst — das Token von Ko-fi, die bei HelloAsso erneut gelesene
  Bestellung, die Bestätigung von PayPal. Eine bei HelloAsso bezahlte Mitgliedschaft macht das Mitglied der Website mit
  derselben E-Mail-Adresse zum Vereinsmitglied. Die Namen der Spender bei HelloAsso und PayPal werden nicht öffentlich
  angezeigt.

### Sicherheit

- **Der Discord-Bot meldet, dass er Administrator des Servers ist.** Er braucht es nicht — der Einladungslink verlangt
  es nicht —, aber eine von Hand vergebene Rolle konnte ihm dieses Recht geben, und ein Administrator-Bot, dessen Token
  durchsickert, öffnet den ganzen Server. Jedes Mal, wenn er dem Server beitritt, sagt es das Protokoll des Bots, das
  die Administration anzeigt, und nennt die Berechtigungen, die seiner Rolle fehlen.
- **Der Link von „/forum account link“ verknüpfte nicht immer das richtige Konto.** Wer diesen Link von jemand anderem
  erhielt, verknüpfte das Discord-Konto *dieser Person* mit seinem EIGENEN Konto der Website — und da ein verknüpftes
  Konto die Anmeldung erlaubt, konnte derjenige, der den Link geschickt hatte, danach in das Konto seines Opfers
  gelangen. Die Verknüpfung wird jetzt bei Discord bestätigt: Die Website vergleicht die ID, die Discord ihr
  zurückgibt, mit der, die der Link nennt, und verknüpft nichts, wenn sie sich unterscheiden. Eine Warnung ersetzte
  diese Prüfung; sie genügte nicht.

### Behoben

- **Die Einstellung „Schriftart der Website“ sagte, die Schriften kämen von Google Fonts.** Die Website liefert sie
  selbst aus, seit kein Besucher mehr zu Google geschickt wird: Die Beschreibung sagt es jetzt.
- **Ein Addon-Update spielte alle seine Migrationen erneut ab.** Das Lesen der bereits ausgeführten Migrationen kam
  immer leer zurück: Bei jedem Update eines Moduls, Widgets oder Themes — über den Marketplace oder per Archiv —
  wurden alle seine Migrationen erneut ausgeführt, und die, die Daten schreiben, schrieben sie ein zweites Mal. Jetzt
  laufen nur noch die neuen. `check-db-query` überwacht die Form, die den Fehler verursachte: eine vorbereitete, aber
  nie gesendete Abfrage.
- **Der Countdown kommender Ereignisse blieb leer.** Sein Skript lebte in einem entfernten Theme: Die Liste der
  Ereignisse und die Seite eines Ereignisses zeigten eine Sanduhr neben nichts, in allen Themes. Es lebt jetzt beim
  Modul und schreibt die verbleibende Zeit in der Sprache der Seite („11 T 7h 22 Min.“, „11d 7h 22m“).
- **Direkt nach einem Update konnte die Website noch den alten Code ausliefern.** Der Cache von PHP (OPcache) behält
  den kompilierten Code einer Datei, bis er sie erneut prüft: meist zwei Sekunden, und bei Hostern, die diese Prüfung
  abschalten, bis zum Neustart von PHP. Das Update des Kerns, das eines Addons über den Marketplace und das Senden
  eines Archivs über „Hinzufügen“ gaben ihm nicht Bescheid: Eine Seite eines aktualisierten Moduls antwortete „Seite
  nicht gefunden“, bis seine neuen Adressen neu gelesen waren, und eine Website konnte mit einer Mischung aus altem und
  neuem Code laufen. Alle drei melden dem Cache jetzt jede Datei, die sie schreiben.
- **Ein Addon per Archiv zu aktualisieren übersprang seine Migrationen — und löschte das Layout eines Themes.**
  „Hinzufügen“ in *Themes & Addons* installierte das Addon neu, statt es zu aktualisieren: Die Änderungen an seiner
  Datenbank wurden als erledigt markiert, ohne ausgeführt zu werden, und ein Theme verlor alle seine Layouts samt
  ihren Widgets, ersetzt durch sein ursprüngliches Layout. Ein neueres Archiv aktualisiert jetzt wie über den
  Marketplace: Die Datenbank zieht mit, und das Layout bleibt das des Administrators.
- **Im Live-Editor von Nebula waren die Vorschauen der Stile die von Forge**: Orange auf Braun. Sie übernehmen jetzt
  die Farben von Nebula, die der Administrator gewählt hat. Dieselbe Korrektur für das Schaufenster-Theme.
- **Ein Navigations-Widget, das ein Menü anzeigte, behielt eine eingefrorene Kopie davon.** Es kopierte die Links des
  Menüs beim Speichern und vergaß sogar, welches Menü gewählt war: Ein später hinzugefügter oder umbenannter Link kam
  nie in der Navigation an. Das Menü wird jetzt bei jeder Anzeige neu gelesen, in der Sprache der Seite.
- **Ein Link zu einem Abschnitt einer Wiki-Seite führte an den Anfang der Seite.** Nur Überschriften der obersten Ebene
  trugen einen Anker: Ein Link zu einer Unterüberschrift fand nichts, und die Dokumentation, die oft auf einen genauen
  Abschnitt verweist („siehe Suchmaschinen“), ließ ihren Leser am Anfang der Seite stehen, in allen Sprachen. Die
  Unterüberschriften haben jetzt ihren Anker, und jeder Verweis der Dokumentation führt zu der Überschrift, die er
  nennt.
- **Eine Wiki-Seite, die unter einer ihrer eigenen Unterseiten eingeordnet wurde, verschwand aus dem
  Inhaltsverzeichnis**, mit ihrem ganzen Abschnitt: Der Bildschirm bot die Unterseiten als übergeordnete Seite an. Er
  tut es nicht mehr, und eine solche trotzdem gesendete Schleife wird abgelehnt.
- **Das Bewerbungsformular antwortete „Fehler 500“, sobald ein Angebot eine optionale Frage stellte.** Eine als leerer
  Text geschriebene Regel „keine“ wurde von der Formularbibliothek nicht verstanden, und die ganze Seite fiel aus.
  Gefunden beim Testen der Übersetzung der Fragen.
- **Das Widget *Rekrutierung* verbarg Angebote ohne Bewerbung** — also jedes gerade eröffnete Angebot. Das Modul war
  korrigiert worden; das Widget nicht.
- **Eine als ganze Seite geöffnete Löschbestätigung zeigte nichts.** In der Administration führte ein Link „Löschen“,
  in einem neuen Tab — oder ohne JavaScript — geöffnet, zu einer leeren Seite: keine Meldung, keine Schaltfläche, kein
  Weg zurück. Sie zeigt jetzt, was gelöscht werden soll, die Schaltfläche, die es tut, und einen Link zurück zum Modul.
- **Die Seiten Moderation und Monitoring sahen nicht aus wie der Rest der Administration.** Ihre Zahlenkarten, ihre
  Kopfbereiche und der Puls der Gesundheitsanzeige kamen aus einem Ersatz-Stylesheet — gedacht für Websites, die diese
  Bildschirme außerhalb der Administration zeigen —, das, zuletzt ausgeliefert, das Theme überschrieb. Der Ersatz
  greift jetzt nur noch dort, wo das Theme nichts sagt.
- **Auf Portugiesisch ließ sich ein Konto nicht löschen.** Der Bildschirm bat, „EXCLUIR“ abzutippen, während die
  Website „ELIMINAR“ erwartete: Die Bestätigung schlug jedes Mal fehl. Die fünf anderen Sprachen waren richtig. Eine
  Prüfung lehnt jetzt eine Anweisung ab, die ein anderes Wort zitiert als das, das die Website erwartet.
- **Das Portugiesische vermischte Brasilien und Portugal.** Achtzig Wendungen überprüft: *usuário* wird zu
  *utilizador*, *senha* zu *palavra-passe*, *excluir* zu *eliminar*, *registro* zu *registo*, und *arquivo* wird zu
  *ficheiro*, wenn eine Datei gemeint ist — und bleibt *arquivo*, wenn ein Archiv gemeint ist.
- **Einige holprige englische Sätze**: „All posts will be also deleted“ wird zu „will also be deleted“, in den vier
  Löschmeldungen, in denen es noch stand.
- **Einen Inhalt zum ersten Mal übersetzen: Der Text ging verloren, ohne ein Wort.** Eine News auf Französisch
  schreiben, die Administration auf Englisch umstellen, sie übersetzen und speichern: Nichts wurde behalten. Die Zeile
  dieser Sprache existierte noch nicht, und das Speichern berührte überhaupt keine Zeile — stillschweigend. Das galt für
  News, Blogbeiträge, Seiten, Kategorien, Galerien, Spiele, Teams, Gruppen und Partner. Eine Übersetzung legt jetzt
  ihre Zeile an.
- **Noch nicht übersetzte Inhalte waren in der Administration dieser Sprache unauffindbar.** Die Listen der News, der
  Blogbeiträge und der Seiten zeigten nur, was in der Sprache existierte, in der man arbeitet: Alles andere war
  unsichtbar, also unmöglich zu öffnen und zu übersetzen. Die Listen zeigen jetzt alles, mit dem besten verfügbaren
  Titel, und der Bearbeitungsbildschirm öffnet sich mit leeren Feldern, bereit für die Übersetzung.
- **Das Umbenennen einer Gruppe löschte alle ihre Übersetzungen.** Das Speichern zielte auf keine bestimmte Sprache: Es
  schrieb denselben Titel in jede Zeile der Gruppe — und schlug fehl, sobald die Website mehr als eine hatte.
- **Ein Partner konnte nur eine Sprache haben.** Die Übersetzungstabelle des Moduls *Partner* hatte als Schlüssel nur
  die Nummer des Partners: Eine zweite Sprache wurde abgelehnt oder überschrieb die erste. Sie führt jetzt eine Zeile
  pro Sprache, wie die sechzehn anderen Übersetzungstabellen, und einen Partner in einer neuen Sprache zu beschreiben,
  legt seine Zeile an, statt nichts zu schreiben.
- **Der nächste Termin: Der Wochentag klebte an der Tageszahl.** Im Datumsfeld des Widgets *Der nächste Termin*
  berührten sich die drei Zeilen — Wochentag, Tageszahl, Monat — und überlappten, sobald ein Theme seine Anzeigeschrift
  setzte: Die Ziffern ragten aus einer Zeilenhöhe heraus, die kürzer war als ihre Tinte. Die drei Zeilen behalten jetzt
  ihren Abstand in allen sieben Themes, im Hell- wie im Dunkelmodus. Dieselbe Korrektur für die großen Zahlen des
  Widgets *Die Website in Zahlen*.
- **Pulse zeichnet endlich seine Widgets.** Das Theme sah ein eigenes Aussehen für das Datumsfeld des Kalenders, die
  Zahlen der *Website in Zahlen*, die Woche und die Vorschaubilder der Galerie vor: Keines davon erschien, weil das
  Stylesheet des Widgets nach dem des Themes ausgeliefert wird und bei gleicher Spezifität gewann. Die Kachel von Pulse
  (abgerundete Ecken, große Ziffern, Schiefergrau) erscheint jetzt so, wie der Entwurf sie zeigte.

## [1.2.49] — 2026-10-10

Ein großes Paket: Eine Adresse mit falschem Titel führt zur richtigen, die Liste der nicht gefundenen Seiten, der
Texteditor in der Sprache der Seite, das Modul Ereignisse ohne Spiele nutzbar — für einen Verein oder einen Club —,
Bilder in Tickets, die von Discord kommen, und eine lange Liste von Korrekturen, gefunden beim Durchlesen des gesamten
Änderungsprotokolls, darunter eine Match-Seite, die ihr Match nicht mehr anzeigte. Der Discord-Bot geht auf
**Version 0.2.7** (siehe sein eigenes Änderungsprotokoll).

### Hinzugefügt

- **Bilder in einem Ticket des Bugtrackers**: Ein Screenshot, der auf Discord an ein Ticket oder eine Idee angehängt
  wird, beim Öffnen oder in einer Antwort, erscheint jetzt im Ticket auf der Website — mit Bot 0.2.7, der ihn der
  Website übergibt, wie er es schon für das Forum tut. Der Text eines Tickets bleibt reiner Text: Nur die von der
  Website aufbewahrten Bilder werden darin angezeigt; die Adresse eines anderswo gehosteten Bildes bleibt Text.
- **Die Liste der nicht gefundenen Seiten**, unter *Monitoring → Diagnose*: jede Adresse, die die Website nicht finden
  konnte — ohne Sprache und ohne Parameter —, wie viele Besucher und Bots sie aufgerufen haben, wann zuletzt, und die
  letzte Seite, von der Besucher kamen. Eine Schaltfläche „Umleiten“ öffnet das Umleitungsformular mit bereits
  ausgefüllter Adresse; die Proben von Bots auf der Suche nach einer Lücke werden getrennt aufgeführt. Keine
  IP-Adresse, kein Browser; eine Adresse, die 90 Tage lang nicht mehr aufgerufen wurde, wird vergessen.
- **Verwaiste Bilder des Editors**, unter *Monitoring → Diagnose*: Ein in einen Text hochgeladenes und dann entferntes
  Bild oder das Bild eines nie gespeicherten Entwurfs blieb auf der Festplatte. Die Seite findet sie — kein Text der
  Website zeigt sie mehr an, kein Inhalt, keine Nachricht, keine Einstellung, keine Revision, kein Papierkorb, und sie
  sind älter als 30 Tage —, zeigt sie und löscht die angekreuzten.
- **Der Texteditor spricht die Sprache der Seite**: Seine Menüs, Schaltflächen und Dialoge waren überall auf Englisch;
  sie sind jetzt auf Französisch, Deutsch, Spanisch, Italienisch oder Portugiesisch, je nach Sprache der Seite.
- **Ein animiertes GIF bleibt animiert** im Editor: Es wurde zu einem Standbild. Es wird neu geschrieben und behält nur
  seine Bilder, deren Timing und seine Schleife. Ein GIF mit mehr als 2.000 px pro Seite oder mehr als 1.000 Bildern
  wird weiterhin zu einem Standbild, wie bisher.
- **Ereignisse ohne Spiele**: Das Modul Ereignisse benötigt die Module Spiele und Teams nicht mehr. Ein Verein oder ein
  Club installiert es allein — Einladungen, Antworten, Wiederholung, Erinnerungen; Matches, ihre Gegner und ihre
  Ergebnisse kommen mit den Modulen Spiele und Teams. Es gehört jetzt zu den Installationsprofilen „Community“ und
  „Verein / Club“.

### Behoben

- **Eine Adresse mit falschem Titel leitet zur richtigen um** (dauerhafte Umleitung, 301): Der Titel des Inhalts hat
  sich geändert, oder der Link stammt aus einer anderen Sprache oder enthält einen Tippfehler. Blogartikel,
  Kategorien, Autoren und Serien, Auszeichnungen, Bugtracker-Tickets, Kalenderereignisse, Kleinanzeigen, Umfragen und
  Rezepte antworteten auf jeden beliebigen Titel — Duplikate für Suchmaschinen; News, Ereignisse,
  Rekrutierungsangebote, Teams, Alben und Bilder der Galerie, Foren und Themen, Profile, Gruppen und Partner
  antworteten „nicht gefunden“ unter der alten Adresse eines geänderten Titels. Die Seite einer Blogkategorie zeigt
  ihren Titel, nicht mehr den Text der Adresse; die Seite eines Ereignistyps oder eines Teams, das nicht existiert,
  antwortet jetzt „nicht gefunden“.
- **Ein im Editor hochgeladenes Bild ragt nicht mehr** über die Seite hinaus, auf Seiten, die es nicht begrenzten (der
  Reiter „Über“ eines Profils, Ereignisse, Partner, Alben, Rekrutierungsangebote, Teams), noch auf einem Telefon: Es
  nimmt höchstens die Breite der Spalte ein.
- Eine Größe unter 1.000 Byte lautet „685 B“, nicht mehr „685.00 B“.
- **Forge**, im Tagmodus: Der gedämpfte Text (Daten, Autoren, Orte, „Registriert am…“) ist etwas dunkler — er blieb
  knapp unter der Lesbarkeitsschwelle.
- **Forge und Extend**: Die dünnen Striche, mit denen man das Bild der Diashow wählt, lassen sich auf einem Telefon
  leicht antippen (sie waren nur 3 px hoch); optisch ändert sich nichts.
- **Chronique**: Die Zahl der ungelesenen Benachrichtigungen auf dem Kontosymbol ist viel besser lesbar, bei Tag wie
  bei Nacht.
- **Discord**: Wenn ein Forenthema oder -beitrag, ein Ticket oder ein Ticketkommentar gelöscht wird, wird der
  Datensatz, der es mit seinem Discord-Thread verbindet, jetzt eine Woche später automatisch entfernt; er blieb für
  immer in der Datenbank.
- **Nichts klebt mehr am Rand**: die Liste der gesperrten IPs und ihr Hinzufügeformular (der Hilfesatz und die
  Schaltfläche berührten die Ränder ihrer Karte), das Suchfeld der Ereignisliste, die Seitennavigation einer Tabelle
  in Granite, die Seitennavigation und die Schaltfläche „Folgen“ in Nebula, ein langer Benutzername in der
  Mitgliederliste. Auf einem Telefon scrollen die Diashow- und Berechtigungstabellen innerhalb ihrer Karte, statt über
  sie hinauszuragen.
- **Keine aneinanderklebenden Schaltflächen mehr**: „Einladen“, „Verlassen“… in einer Unterhaltung, auf einem
  Telefon; „Zurück“ und „Thema veröffentlichen“ im Forum; die beiden Schaltflächen der Forensuche in der Verwaltung.
- **Forge**: Auf einem Telefon ist das Menü des Mitgliederbereichs wieder da — es rutschte unter die Karte der Seite;
  auf einem Computer bleibt es beim Scrollen sichtbar. Die grüne Schaltfläche „Meine Bewerbung ansehen“ ist wieder
  lesbar.
- **Eine Match-Seite** zeigt wieder ihr Match — das Team, den Gegner, das Ergebnis, das Spiel und die Runden, die sie
  nicht mehr anzeigte. Team, Ergebnis und Gegner passen in eine Zeile, ebenso jede Runde, auf einem Telefon wie auf
  einem Computer.
- **Rekrutierung**: Die Seite „Meine Bewerbung“ zeigt wieder die richtige Position und das richtige Team — sie zeigte
  den Namen des Symbols statt der Position. **Forum**: Das Bearbeiten eines Forums in der Verwaltung zeigt wieder seine
  Beschreibung und seine Kategorie — es zeigte stattdessen den Titel, und das Speichern konnte das Forum woandershin
  verschieben.
- **Moderation**: Eine Sanktion wird drei Jahre nach ihrem Ende automatisch gelöscht (abgelaufen oder aufgehoben; eine
  Verwarnung drei Jahre nach ihrer Erteilung); eine Sanktion ohne Ende (zum Beispiel eine dauerhafte Sperre) bleibt,
  solange sie läuft. Sie wurden unbegrenzt aufbewahrt.
- Die Melde-Schaltfläche (das Fähnchen „Diesen Inhalt melden“) lässt sich auf einem Telefon leicht antippen; in der
  Liste *E-Mail-Vorlagen* der Verwaltung heben sich die Variablen im Betreff einer E-Mail (`{{date}}`) vom Text ab.
- **Newsletter**: Das Ziel „Gruppe“ und die Liste der Kampagnen nennen jede Gruppe in der Sprache der Verwaltung; sie
  nahmen eine zufällige Sprache („Criador de addons“, „Mitwirkender“).
- **Richtige Beschriftungen**: Auf dem Profil eines Mitglieds zeigte die Schaltfläche „Contacter“ auf Französisch
  „Contact“; das Theme Extend schreibt „Im Fokus“ und „Entdecken“ in der Sprache der Seite (sie blieben auf
  Französisch); die Schaltfläche „Raster“ der Blog- und Addon-Seite heißt nicht mehr „Programm“ (auf Englisch
  „Schedule“, ebenso falsch auf Spanisch und Italienisch); der französische Tickettyp „Demande de feature“ wird zu „Demande
  de fonctionnalité“. Auf Englisch sagt die Anmeldeschaltfläche „Log in“, das Menü „Log out“, und überall wird „View my
  profile“ verwendet. Im Monitoring schrieb die Karte „Speicher“ in jeder Sprache „Sauvegarde“, „Libre“ und „Total“ auf
  Französisch.
- **Pulse, bei Nacht**: Die Zahl der ungelesenen Benachrichtigungen auf dem Kontosymbol ist lesbar — eine dunkle Ziffer
  auf dem hellen Ziegelrot; in Weiß erreichte sie nur 3,9:1.
- **Das Widget Spenden** zeigt die Initiale eines Spenders, dessen Name mit einem Buchstaben mit Akzent beginnt; es
  zeigte „&“.
- **Das Zurücksetzen des Passworts** lehnt, wie die Registrierung und die Änderung, ein Passwort ab, das mit dem
  Benutzernamen identisch ist: Das Zurücksetzen verglich es nicht.
- Der Discord-Bot geht auf **Version 0.2.7** (0.2.6, dann 0.2.7): Er übergibt die an Tickets angehängten Bilder an die
  Website (siehe oben), und sein Protokoll verzeichnet keine gewöhnlichen Neuverbindungen zu Discord mehr, nur noch
  einen Ausfall von mehr als einer Minute (siehe sein eigenes Änderungsprotokoll).

## [1.2.48] — 2026-10-09

Ein großes Paket: ein Sicherheitsaudit (Zugriffe, Injektionen, Konten, Dateien), eine Moderation, die endlich
funktioniert — ihre Sanktionen gelten jetzt für alles, was Mitglieder schreiben —, eine neue E-Mail-Adresse, die
bestätigt werden muss, bevor sie zählt, das Löschen inaktiver Konten und die reparierte Anmeldung mit Discord, GitHub
oder Google. Der Discord-Bot geht auf **Version 0.2.5** (siehe sein eigenes Änderungsprotokoll). **Auf einer Website
mit HTTPS meldet sich nach dem Update jeder einmal neu an**: Das Sitzungscookie ändert seinen Namen.

### Sicherheit

- **Die Forensuche, für Besucher offen, erlaubte eine SQL-Injektion** über ein Wort in Anführungszeichen; die Suche der
  privaten Nachrichten hatte dieselbe Lücke. Dieses Wort wird jetzt wie die anderen bereinigt und von der Datenbank
  selbst maskiert.
- **Eine von einer anderen Website kommende Übermittlung wird abgelehnt** (POST, PUT, DELETE…), nach dem, was der
  Browser angibt (`Sec-Fetch-Site`, sonst `Origin`): Eine präparierte Seite kann ein angemeldetes Mitglied nicht mehr
  ohne sein Wissen handeln lassen. Ein Client ohne Browser — der Bot, ein Zahlungsdienst — ist nicht betroffen.
- **Zwanzig Aktionen, die über einen einfachen Link etwas änderten**, verlangen jetzt das Sitzungstoken oder nehmen nur
  noch POST an: einem Thema folgen, es zur Ankündigung machen, alles als gelesen markieren, eine Unterhaltung
  verlassen, archivieren, löschen oder wiederherstellen, reagieren, Benachrichtigungen als gelesen markieren, seine
  Verfügbarkeit für ein Ereignis angeben, die Website und die Registrierungen öffnen oder schließen, eine Sicherung
  oder ein Update starten, ein Rekrutierungsfeld löschen… Die Abmeldung über einen Link ohne Token fragt per
  Schaltfläche nach einer Bestätigung.
- **Unter HTTPS hat das Sitzungscookie einer im Stammverzeichnis ihrer Domain installierten Website das Präfix
  `__Host-`**: Eine Subdomain (eine Demo, ein Webmail) erhält es nicht mehr und kann keines mehr aufzwingen. Eine in
  einem Unterordner installierte Website behält ein Cookie ohne dieses Präfix, das nur seinen Namen ändert.
- **Konten**:
  - der Link „Passwort vergessen“ funktionierte auch als Bestätigungslink der Registrierung, der zwei Tage lang ohne
    Nachfrage anmeldet: Jeder Link funktioniert jetzt nur noch für seinen eigenen Zweck;
  - das Ändern des Passworts verwirft eine ausstehende neue Adresse und die Links „Passwort vergessen“;
  - fehlgeschlagene Anmeldungen werden pro Konto gezählt — wie auch immer es geschrieben wird — und pro Netzwerk: Eine
    IPv6-Adresse zählt für ihr /64-Netz, bei jeder Versuchsbegrenzung der Website;
  - das Einschalten der Zwei-Faktor-Authentifizierung oder das Verknüpfen eines Discord-, GitHub- oder Google-Kontos
    fragt erneut nach dem Passwort; fünf Fehler sperren diese Bestätigung für eine Viertelstunde;
  - „Passwort vergessen“ antwortet gleich, ob eine Adresse registriert ist oder nicht, und die Registrierung sagt nur
    fünfmal pro Stunde und pro Netzwerk, dass eine Adresse bereits vergeben ist;
  - das Anmeldeprotokoll verzeichnet die Adresse der Verbindung, nicht mehr einen Header, den der Browser wählt.
- **Zwölf Zugriffslecks geschlossen**: der Verlauf und die Revisionen einer Wiki-Seite im Entwurf; die VIP-Kategorien
  des Forums, über die Suche und die Profile; versteckte Gruppen; das Bild eines geschlossenen Albums; die Matches
  eines eingeschränkten Typs; die Anwesenheit, die Teilnehmer verborgen hatten; Kommentare und Reaktionen auf
  verborgenen Inhalten; das Melden einer Unterhaltung, an der man nicht teilnimmt.
- **Zwei alte Adressen der privaten Nachrichten lieferten die Nachrichten jeder Unterhaltung**, auch privater, an
  jeden mit Leserecht — Besucher eingeschlossen: entfernt. Das Posten eines Bildes in die Galerie verlangte nur das
  Recht, das Album zu sehen.
- **Die Anhänge des Forums und der Unterhaltungen werden von der Website ausgeliefert**, an jeden, der das Thema oder
  die Unterhaltung lesen kann, nicht mehr vom Webserver an jeden, der ihre Adresse hatte. Ein Bild oder ein PDF wird
  angezeigt, alles andere heruntergeladen; ein Anhang, den ein Browser ausführen würde, wird beim Hochladen abgelehnt.
  Unter Apache übernimmt das mitgelieferte `upload/.htaccess` das; unter nginx oder Caddy kopiere die Regel, die
  `upload/forum/` und `upload/talks/` sperrt, in deine Serverkonfiguration (siehe die mitgelieferten `nginx.conf` und
  `Caddyfile`).
- **Was der Server selbst abruft** (Bild-Relay, RSS-Feeds, Webhooks), ist auf streng öffentliche Adressen beschränkt,
  auf den Ports 80 und 443.
- **Die Stripe-Schlüssel sind in der Verwaltung nicht mehr im Klartext lesbar**: verschlüsselt gespeichert, nie wieder
  angezeigt.
- **Demo**: Die IP-Adressen der Besucher, Hostnamen und verweisenden Websites werden dort nicht mehr angezeigt; eine
  Werbung, ein Link, ein Partner, der aus der Website hinausführt, wird auf einer Seite gezeigt, die sagt, wohin er
  führt, statt dorthin umzuleiten.
- **Ein `javascript:`-Link kann nicht mehr anstelle einer Adresse angezeigt werden**: die Seite, von der eine Sitzung
  kam (Verwaltung), die Links eines Matches (Website des Gegners, Stream, Artikel). Der Link, den eine Meldung zum
  gemeldeten Inhalt gibt, kann nur zu einer Seite der Website führen.

### Hinzugefügt

- **Eine neue E-Mail-Adresse wartet auf ihre Bestätigung**: Der Link geht an die neue Adresse (zwei Tage gültig), die
  alte wird benachrichtigt, und „Mein Konto“ bietet an, den Link erneut zu senden oder abzubrechen. Bisher schnitt ein
  Tippfehler das Mitglied von allem ab, was die Website verschickt.
- **Inaktive Konten werden gelöscht**: ohne Besuch seit drei Jahren — einstellbar unter *Einstellungen*, `0` für nie —,
  eine E-Mail warnt das Mitglied einen Monat vorher, ein Besuch hebt alles auf. Nie ein Administrator.
- **Ein Mitglied sanktionieren, ohne auf eine Meldung zu warten**, aus seinem Moderationsverlauf, auf den seine
  Verwaltungsseite verweist.
- **Die Melde-Schaltfläche** (das Fähnchen „Diesen Inhalt melden“) im Gästebuch, an Bildern der Galerie,
  Kleinanzeigen, Tickets und ihren Kommentaren.
- **„Meine Sanktionen“** im Mitgliederbereich, für jeden, der im Jahr eine hatte: ihre Art, ihr Grund, ihr Zeitraum.
- **Mediation**: Ausgehend von einer Meldung schlägt der Moderator eine private Unterhaltung zwischen sich, dem
  gemeldeten Mitglied und dem Meldenden vor. Das gemeldete Mitglied wird dort sehen, wer es gemeldet hat; sie öffnet
  sich daher nur mit dem Einverständnis des Meldenden, gegeben auf einer Seite, die erklärt, was er akzeptiert; lehnt
  er ab, bleibt seine Meldung anonym und läuft wie gewohnt weiter.

### Behoben

- **Die Moderationssanktionen gelten für alles, was Mitglieder schreiben**: Forum, private Nachrichten, Kommentare,
  Gästebuch, Bugtracker, Rekrutierung, Kleinanzeigen, Galerie, Profil, im Texteditor hochgeladene Bilder. Nur die
  Stummschaltung im Forum und in den Nachrichten wurde geprüft, und erst nach dem Absenden. Das Formular weicht einem
  Hinweis, der sagt, was die Sanktion verbietet, bis wann und warum; die Sanktion „Einschränkung: Links“ lehnt einen
  Text ab, der einen Link enthält, ohne ihn zu verlieren.
- **Vom Moderationsbildschirm aus ließ sich keine Sanktion verhängen**: Ein interner Fehler verhinderte es, sodass die
  automatische Eskalation nie auslöste. Gleichzeitig überarbeitet: die Einstellungen (Kästchen, die sich nicht abwählen
  ließen, Schwelle für verdächtige Meldende), die Geltungsbereiche, die Dauern (eine befristete Sperre ohne Dauer wurde
  dauerhaft), die Bestätigung durch einen ranghöheren Moderator, das Moderatoren-Panel, doppelte Meldungen, die Seiten
  ab 2, die IP-Liste. Ein Moderator kann keine Sanktion mehr aufheben, die ihn betrifft, noch die eines Mitglieds
  gleichen oder höheren Rangs, noch eine, die ein ranghöherer Moderator verhängt hat.
- **Der Shadow Ban funktioniert**: Was ein mit Shadow Ban belegtes Mitglied schreibt, wird nur ihm und den Moderatoren
  angezeigt, und nichts wird verbreitet — keine Benachrichtigung, kein Discord, keine Abonnenten.
- **Eine zusätzliche Rolle — Moderator — entzog einem Mitglied Rechte**: Ein Moderator verlor das Forum und die privaten
  Nachrichten. Eine Rolle fügt Rechte hinzu, sie entzieht keine mehr.
- **E-Mails werden in der Sprache dessen verschickt, der sie erhält** — Sanktionen, Antworten im Forum, private
  Nachrichten —, nicht mehr in der des Mitglieds, das sie ausgelöst hat.
- **Die Anmeldung mit Discord, GitHub oder Google schlug fehl**, sobald ein Dienst konfiguriert war (die
  OAuth-Bibliothek hatte sich geändert), und schickte dem Dienst eine Rücksprungadresse, die er ablehnte. Ein
  abgelehnter Rücksprung sagt jetzt warum, statt einer Fehlerseite, und der Discord-Avatar wird wieder importiert.
- **Gästebuch, Verwaltung**: Das Freigeben, Ablehnen oder Löschen einer Nachricht führte zu einem Fehler „Seite nicht
  gefunden“.
- **Der Fehler eines Formularfelds steht unter dem Feld**, auf einem Telefon wie auf einem Computer: Er war nur in einem
  Tooltip beim Überfahren lesbar.
- **Die obere Leiste der Verwaltung bleibt oben** beim Scrollen; auf einem Telefon bleiben nur das Menü und der
  Brotkrumenpfad fixiert.
- **Vierunddreißig Übersetzungsfehler** in zwölf Sprachdateien („There are 1 user“, Löschbestätigungen mit
  `< br / >`, englische Plurale…).
- Die von 48 Addons des Kerns angegebene Website-Adresse ist die des Projekts, nicht mehr die des ursprünglichen
  NeoFrag. Die Seite *E-Mail-Vorlagen* der Verwaltung listet nicht mehr die Vorlagen eines nicht installierten Moduls.
  Ein Fehler in manchen Formularen (Begrenzung des Gästebuchs erreicht, Kontolöschung, Zwei-Faktor-Authentifizierung,
  Passwort, Anlegen eines Mitglieds in der Verwaltung) führt nicht mehr zu einem Fehler „Seite nicht gefunden“.
- **Demo**: Ein gewöhnliches Mitglied konnte kein Thema eröffnen, nirgends antworten und sich auf nichts bewerben.
- **Der Export „Meine Daten“ enthält die Meldungen, die das Mitglied gemacht hat**: Sie fehlten im Archiv.

### Geändert

- **Ein an ein Modul gebundenes Widget hängt jetzt von ihm ab** (21 Widgets): Ohne dieses Modul, oder wenn es
  deaktiviert ist, wird das Widget nicht mehr angezeigt und der Live-Editor bietet es nicht mehr an; „Hinzufügen“ lehnt
  ein Widget-Archiv ohne sein Modul ab.
- **Die Produktdokumentation wird nicht mehr mitgeliefert** im Wiki einer neuen Website, das leer ankommt; sie lebt auf
  der Website des Projekts. Eine neue Installation erhält nicht mehr die Einstellungen von Themes, die sie nicht hat.

### Entfernt

- Die alten Adressen `ajax/talks` der privaten Nachrichten und ihre alte Nachrichtenmeldung, die nur ins
  Audit-Protokoll schrieb, mit ihrer Verwaltungsseite „Nachrichtenmeldungen“: Eine Nachricht wird jetzt über die
  Moderation gemeldet.

## [1.2.47] — 2026-10-09

Die Schnellleiste von Blockcraft, auf Telefonen.

### Behoben

- **Die Schnellleiste von Blockcraft nimmt die ganze Breite des Telefons ein**: Ihre Felder teilen sich den Bildschirm
  gleichmäßig, und jeder Name passt in sein eigenes — auf zwei Zeilen für „Mein Bereich“, mit Trennstrich für ein
  deutsches oder portugiesisches Wort, das zu lang ist („Neuig-keiten“). Sie behielt unabhängig vom Bildschirm dieselbe
  Breite (sieben Felder zu 47 px): ein leerer Raum rechts, schmale Felder und „News“, das aus seinem Feld ragte. Der
  weiße Rahmen des gewählten Felds berührt seinen Namen nicht mehr.

## [1.2.46] — 2026-10-08

Die Leisten oben auf der Seite verdecken nichts mehr, die Glocke kommt zu Nebula, und die Seite *Themes & Addons* kann
ein Theme wieder neu installieren.

### Behoben

- **Was oben am Bildschirm klebt, sitzt jetzt unter den Leisten oben auf der Seite** — der Leiste der Demo, der Leiste
  der Wartung und der Leiste der Berechtigungsvorschau. Sobald man scrollte, rutschte die Hälfte der Kopfbereiche von
  Extend, Chronique, Pulse und Nebula unter die Leiste der Demo; das untere Ende der Leiste von Forge und der
  Seitenleiste der Verwaltung verschwand aus dem Bildschirm; auf Telefonen waren das Wappen und das Konto von Forge
  verdeckt. Die Hauptvorlage stapelt die Leisten jetzt und veröffentlicht ihre Höhe; die Themes, die Verwaltung und
  der Live-Editor richten sich darunter aus, und die Bildschirmbenachrichtigungen erscheinen darunter.
- **Die fixierten Spalten der Module richten sich unter dem Kopfbereich des Themes aus**, welcher es auch sei: das Menü
  des Mitgliederbereichs (16 px vom oberen Rand, es rutschte unter einen fixierten Kopfbereich von 60 bis 70 px), das
  Inhaltsverzeichnis des Wikis, das Inhaltsverzeichnis und der Seitenkasten eines Artikels. Eine Spalte, die höher ist
  als der ihr verbleibende Platz, klebt nicht mehr: Ihr unteres Ende blieb bis zum Ende der Seite außerhalb des
  Bildschirms — das Menü des Mitgliederbereichs von Extend, auf dem Bildschirm eines Laptops.
- **Ein Link zu einem Anker hält unter dem fixierten Kopfbereich an** — ein Forenbeitrag, ein Abschnitt des Wikis oder
  eines Artikels —, statt seinen Titel darunter zu legen: Jedes Theme setzte diesen Abstand von Hand, ohne die Leisten
  mitzuzählen.
- **„Standard wiederherstellen“ und „Löschen“ eines Themes gehen von der Seite „Themes & Addons“ aus durch**, und die
  Reihenfolge der Sprachen und der Authentifizierungsdienste wird gespeichert: Die Bestätigung wurde abgelehnt,
  „ungültiges Sicherheitstoken“.
- **Die Leiste der Demo ist lesbar**: Ihr Text, weiß auf Grün (Kontrast 2,4:1), wird dunkel.
- **Auf Telefonen rücken die Schaltflächen eines Verwaltungskopfs unter den Titel**: Auf der Seite der Artikel
  verdeckten „Kategorien“, „Serien“ und „Neuer Artikel“ den Text „4 veröffentlicht“, und die letzte verschwand aus dem
  Bildschirm.
- **Eine Website, die unter einem anderen Namen als dem ihrer Konfiguration geöffnet wird** (mit oder ohne `www.`, zum
  Beispiel), zeigt die mit der konfigurierten Adresse geschriebenen Bilder: Die Sicherheitsrichtlinie des Browsers
  blockierte sie, da sie aus seiner Sicht von einer anderen Website kamen; sie gehen jetzt über das Bild-Relay der
  Website.
- **Für Mitwirkende**: Das Werkzeug `check-mise-en-page` meldet keine falschen Überlappungen mehr innerhalb eines
  zugeklappten Blocks (`<details>`), den Chrome in der Seite behält, ohne ihn anzuzeigen — 48 Fehlalarme auf der Seite
  *Fehlerprotokoll* des Monitorings —, noch in den Fehlermeldungen, die diese Seite absichtlich zeigt.

### Hinzugefügt

- **Die Benachrichtigungsglocke in Nebula**, wie in den anderen Themes: Der Mitgliederbereich kündigt sie „oben auf
  jeder Seite“ an, und Nebula hatte keine.
- **Für Theme-Autoren**: Der fixierte Kopfbereich eines Themes trägt `data-nf-entete`, und was oben am Bildschirm
  klebt, richtet sich nach `--nf-haut` aus (und eine Spalte nach `--nf-entete`); eine neue Prüfung, `check-colles`,
  kontrolliert das — auf 1.2.45 angewendet, fand sie zwanzig fehlerhafte CSS-Regeln (*Ein Theme erstellen*).

### Entfernt

- **Ungenutzte Stilregeln im Stylesheet von Nebula** (eine Leiste, Schaltflächen und eine Fußzeile, von einem anderen
  Theme geerbt): Keine Vorlage von Nebula verwendete sie.

## [1.2.45] — 2026-10-08

Die Benachrichtigungsglocke, in jedem Theme überarbeitet, das sie zeigt, und die Zwei-Faktor-Anmeldung in einem Zug.

### Behoben

- **Mit Zwei-Faktor-Authentifizierung wird der Code direkt nach dem Passwort abgefragt**: Das Anmeldefenster weicht
  dem Code-Fenster, ohne die Seite neu zu laden. Es schloss sich, und man musste erneut auf „Anmelden“ klicken, um den
  Code zu sehen; vom Formular des Mitgliederbereichs aus öffnet sich das Code-Fenster beim Neuladen.
- **Das Abzeichen der Glocke sitzt in seiner Ecke** in jedem Theme, das die Glocke zeigt: Das Stylesheet des Moduls,
  nach dem des Themes geladen, setzte es wieder in den Fluss, und in Extend und Forge landete es mitten auf der
  Schaltfläche, auf der Glocke.
- **Die Glocke bleibt in der Mitte ihrer Schaltfläche**, wenn ein Abzeichen darauf sitzt: Ein Symbol, das nicht mehr
  allein in seinem Link war, bekam einen rechten Rand und rutschte um drei Pixel.
- **„Benachrichtigungen“ und „Alle als gelesen markieren“ berühren sich nicht mehr** oben in der Liste: Sie behält ihre
  Breite, wenn das Theme seine Menüs verengt, und die beiden Texte halten einen Abstand und brechen bei Bedarf um.
- **Daten werden im Sprachformat und in der Zeitzone des Besuchers angezeigt** in der Liste der Glocke, im
  Revisionsverlauf und im Tooltip einer Meldung, die den rohen Zeitstempel der Datenbank zeigten.

## [1.2.44] — 2026-10-08

Eine Korrektur der 1.2.43.

### Behoben

- **Der Filter für Drittanbieter funktioniert unter PHP-FPM und Apache.** Er läuft am Ende der Anfrage, wenn das
  aktuelle Verzeichnis nicht mehr das der Website ist: Er fand den Schlüssel des Bild-Relays nicht mehr, und die Seite
  ging ungefiltert hinaus. Bilder von anderen Websites, die die Sicherheitsrichtlinie jetzt ablehnt, erschienen nicht
  (die Avatare des Discord-Widgets), und ein eingebettetes Video wäre ohne seinen Hinweis geladen worden. Der Fehler
  zeigte sich nicht mit dem eingebauten Server von PHP, der am Ende der Anfrage das Verzeichnis nicht wechselt.
- **Das PHP-Fehlerprotokoll behält auch die Fehler vom Ende der Anfrage**, schwere Fehler eingeschlossen: Sein Pfad war
  relativ und wurde dann vom Wurzelverzeichnis des Dateisystems (`/`) aus aufgelöst.

## [1.2.43] — 2026-10-08

Datenschutz: Kein Besucher wird mehr ohne seine Einwilligung an einen Dritten geschickt.

### Hinzugefügt

- **Eine echte Wahl bei Diensten anderer Unternehmen.** Das Cookie-Banner steuerte nur Google Analytics, während
  Videos, das Discord-Widget und Captchas geladen wurden, was auch immer der Besucher antwortete. Das Fenster **„Meine
  Cookies verwalten“**, aus der Fußzeile jeder Seite geöffnet, zeigt die eigenen Cookies der Website und dann jeden
  Dienst, einzeln anzunehmen oder abzulehnen. Anstelle eines Videos von YouTube, Twitch, Vimeo oder Dailymotion, eines
  Titels von Spotify oder SoundCloud oder des Discord-Widgets sagt ein Hinweis, wer die IP-Adresse des Besuchers
  erhalten würde, mit „Anzeigen“ und „… immer erlauben“. Das Banner öffnet sich nur, wenn die Website wirklich vorab
  etwas zu fragen hat (Reichweitenmessung, ein von einem Dritten betriebenes Captcha); „Alle akzeptieren“ und „Alle
  ablehnen“ haben dasselbe Gewicht. Die Wahl wird sechs Monate aufbewahrt, und ihr Nachweis, ohne die IP-Adresse,
  dreizehn Monate.
- **Einstellungen → Datenschutz**: die Seiten Impressum und Datenschutzerklärung, unter den veröffentlichten Seiten
  gewählt. Die Fußzeile jedes Themes verlinkt sie, neben „Meine Cookies verwalten“.

### Geändert

- **Schriften werden von der Website ausgeliefert**: Die Themes und die Einstellung „Schriftart der Website“ holten sie
  von Google Fonts, und der Browser jedes Besuchers schickte seine IP-Adresse an Google, auf jeder Seite.
- **Bilder von anderen Websites werden von der Website ausgeliefert** — ein Avatar von Discord, Steam oder Twitch, ein
  GIF in einer Unterhaltung, ein in einen Artikel, ins Forum oder in eine Kleinanzeige eingefügtes Bild, ein
  Werbebanner: Die Website holt sie selbst und behält sie sieben Tage, und der Browser kontaktiert ihren Host nicht
  mehr. Die Kacheln der Ortskarte ebenso.
- **Die Sicherheitsrichtlinie akzeptiert jetzt nur noch die Website selbst** für Bilder und Skriptverbindungen (außer
  Google Analytics, falls konfiguriert). Sie lässt jetzt die Player bekannter Dienste (YouTube, Twitch, Vimeo,
  Dailymotion, Spotify, SoundCloud, Discord) anzeigen, die sie bisher blockierte: Ein Video in einem Artikel erschien
  nicht. Ein Skript einer anderen Website, eingefügt in ein Widget „HTML-Code“ oder eine Werbung, läuft nicht mehr: Nur
  Google Analytics und das gewählte Captcha kommen durch.
- **Solange der Besucher ein Captcha eines Dritten nicht akzeptiert, ersetzt ALTCHA es**, von der Website ausgeliefert:
  Niemand muss seine Daten Google, hCaptcha oder Cloudflare überlassen, um auf der Website zu schreiben oder sich zu
  registrieren.
- **Kein Zählpixel mehr im Newsletter**, und keine Öffnungsrate mehr: ein Tracker, um den das Abonnement nicht gebeten
  hatte (Empfehlung der CNIL vom 12. März 2026 zu Zählpixeln in E-Mails).
- **Aufbewahrungsfristen**: das Audit-Protokoll ein Jahr, die Zähler gegen Missbrauch einen Tag, nie bestätigte
  Newsletter-Abonnements dreißig Tage, die Versandwarteschlange des Newsletters neunzig Tage; bei einer vor mehr als
  einem Jahr bearbeiteten Meldung werden die IP-Adresse ihres Verfassers und die Kopie des Inhalts entfernt. Das
  Anmeldecookie endet, wenn der Browser geschlossen wird, außer mit „Angemeldet bleiben“ (ein Jahr), das nicht mehr
  standardmäßig angekreuzt ist.
- **Keine Google-Karten mehr in Texten**: Eine in einen Text eingebettete Google-Maps-Karte wird nicht mehr akzeptiert.
  Die Karte des Produkts ist das Modul „Ortskarte“, auf Basis von OpenStreetMap.
- Der Link „Betrieben von NeoFrag Reborn“ der Themes, das Copyright-Schlüsselwort `{neofrag}` und die Links in der
  Fußzeile der Verwaltung führen zur Website des Projekts; sie führten zur Website des ursprünglichen NeoFrag.

### Behoben

- **Keine IP-Adresse wird mehr an neofr.ag geschickt**: Die Länderflagge neben den Adressen der Sitzungen ist entfernt;
  um sie zu erhalten, schickte der Browser des Administrators all diese Adressen an die Website des ursprünglichen
  NeoFrag.
- **Das Löschen eines Kontos als Administrator löscht wirklich** das Profil, die verknüpften Konten, das
  Anmeldeprotokoll und die Benachrichtigungen, wie die Löschung, die ein Mitglied selbst beantragt; es schloss nur das
  Konto.
- **Das Archiv „Meine Daten“ lässt sich jetzt herunterladen**: Drei seiner Listen (Forenthemen, Kommentare, Nachrichten
  in Unterhaltungen) lasen Spalten, die nicht existieren, und das Archiv schlug fehl.
- Die Tooltips der Sitzungsliste und der Galerie erscheinen jetzt: Sie trugen ein Attribut von Bootstrap 4, das
  Bootstrap 5 ignoriert, und blieben leer.
- Die Adresse des Avatars im Steam-Widget wurde unverändert in die Seite geschrieben; sie wird jetzt maskiert, wie der
  Rest des Widgets.
- Der Tooltip „Kanal öffnen“ und die Schaltfläche „Schließen“ des Players im Twitch-Widget, die auf Französisch
  geblieben waren, sind übersetzt.

## [1.2.42] — 2026-10-07

Ein neu gestaltetes Theme: **Extend**, die Website als Launcher eines Online-Spiels.

### Hinzugefügt

- **Extend 2.0.0 „Launcher“** — ein völlig neues Layout, aus drei Entwürfen gewählt. Oben eine Leiste von Reitern, in
  enger Großschrift, der offene Reiter von einer blauen Linie beleuchtet; auf der Startseite ein **großes Schaufenster**
  (die Diashow, ihr Titel in großen Buchstaben und ihre Schaltfläche „Entdecken“; ohne Diashow der Name der Website auf
  dem Bild des Themes), dann die kommenden Ereignisse als Karten, die News als Vorschaubilder, die letzten Ergebnisse;
  anderswo der Titel der Seite auf diesem Bild. Rechts, auf jeder Seite, ein **immer offenes Panel**: wer online ist,
  mit Avataren, dann der Chatkanal (und auf den Seiten des Forums dessen Statistiken). Unten eine **Statusleiste**: der
  Platz für die Widgets „Spieleserver“, „TeamSpeak 3 Server“ und „Discord-Server“, die Sprache, dann „Betrieben von
  NeoFrag Reborn“, das Copyright und die rechtlichen Links. Das Forum als Bibliothek, der Mitgliederbereich als
  Spielerkarte (Banner, umringter Avatar, Gamification-Stufe in der Leiste). Auf Telefonen wandern die Reiter an den
  unteren Bildschirmrand, mit „Online“, das das Panel als Schublade öffnet, und „Mehr“ ab vier Bereichen. Stahlblau auf
  Marineblau, standardmäßig Nacht, Tag auf Wunsch; Überschriften in Saira Condensed, Text in Albert Sans. Logo,
  Schaufensterbild, Hintergrund und Farben sind einstellbar. Auf Basis des Themes von Chewbaka, dessen Namen und Lizenz
  es behält.
- **„Wer ist online? (Liste)“**, eine neue Anzeige des Widgets Mitglieder: die drei Zahlen (Administratoren,
  Mitglieder, Besucher), dann die Personen selbst mit ihrem Avatar und ihrer letzten Aktivität.
- **„Ein öffentlicher Kanal: seine letzten Nachrichten“**, eine neue Anzeige des Widgets Unterhaltungen: die letzten vier
  Nachrichten eines Kanals, der allen Mitgliedern offensteht, und der Link, um darin zu schreiben. Ein Besucher liest
  die Nachrichten nicht: Er wird eingeladen, sich anzumelden, wie in den Nachrichten selbst; der Kanal des Teams wird
  nie gezeigt.

### Behoben

- **Die verwandten Beiträge eines Blogartikels ragen auf Telefonen nicht mehr hinaus**: Der Kasten eines Beitrags ohne
  Bild nahm seine Breite aus der Höhe seiner Zeile und ragte um einige Pixel aus dem Bildschirm (bei 414 px, in jedem
  Theme).
- **Blockcraft, auf der veröffentlichten Demo überprüft**: Auf einem Telefon zeigt das Banner der Seite die ganze
  Landschaft (zugeschnitten zeigte es nur Baumstämme), ohne Sonne oder Mond, über die ein langer Titel lief; die Demo
  setzt eine Beispiel-Serveradresse (`play.example.org`), damit man die Schaltfläche „Adresse kopieren“ sieht.

## [1.2.41] — 2026-10-07

Ein neu gestaltetes Theme: **Blockcraft**, die Website eines Servers für ein Blockspiel.

### Hinzugefügt

- **Blockcraft 2.0.0 „Spawn + Inventar“** — ein neues Layout, aus vier Entwürfen gewählt. Oben ein Himmel über einer
  Blocklandschaft (Hügel, Bäume, Stein), die Sonne und ziehende Wolken, nachts der Mond und die Sterne; auf der
  Startseite der Name des Servers in großen Buchstaben und **seine Adresse, mit einem Klick kopiert** (Einstellung
  „Serveradresse“), und der Discord, wenn er eingestellt ist. Die Navigation ist eine **Gegenstandsleiste**: jeder
  Bereich in seinem Feld, mit dem Gegenstand, der für ihn steht (das Buch für die News, die Karte für das Forum…); auf
  einem Telefon klebt sie am unteren Bildschirmrand, in Reichweite des Daumens. Blöcke mit eckigen Ecken und harten
  Schatten, das **Forum als Truhen** (ein Brettdeckel pro Kategorie, jedes Forum in seinem Feld, ein verzaubertes Feld,
  das schimmert, wenn es etwas Neues gibt), der **Mitgliederbereich als Charakterbildschirm**, eine Fußzeile aus Stein.
  Überschriften in Jersey 10, Text in Rubik, Zahlen in VT323; „Wiese“ bei Tag, „Sternenhimmel“ bei Nacht. Jedes Pixel
  ist für das Theme gezeichnet. Bei Tag sind die Texte am Himmel dunkel: in Weiß wären sie nur durch ihren Schatten
  lesbar gewesen.

### Behoben

- **Die Seitenspalte von Pulse, Chronique und Blockcraft verzerrt die darin platzierten Widgets nicht mehr**: Ihr Layout
  reichte auch in die Widgets hinein, und „Wer ist online?“ schob dort seine Zahlen von ihren Beschriftungen weg.
- **Der gewählte Reiter des Profils eines Mitglieds ist in Pulse, Chronique und Granite lesbar**: Diese Themes änderten
  seinen Text, ohne seinen Hintergrund zu ändern, den das Modul in der Akzentfarbe malt — dunkler Text auf dem Akzent.
- **Das Symbol einer Gruppe berührt ihren Namen nicht mehr** (zum Beispiel „Hauptteam“, im Profil und im
  Mitgliederbereich): Die gemeinsame Basis der Themes ließ keinen Raum zwischen Symbol und Name — in den sechs Themes,
  die sie laden.
- **Die Kommentare eines Ereignisses behalten ihr Symbol** in Pulse, Chronique und Granite: Diese Themes entfernten das
  Symbol jedes Links in der Fußzeile einer Karte, und der Link sagte nur noch „0 →“. Nur noch die Fußzeilen von Widgets
  („Kalender ansehen →“) verlieren ihr Symbol.
- **Die gewählte Pille des Nachrichtenmoduls ist lesbar** („Alle“, „Archiv“…) in Pulse und Chronique: Die gemeinsame
  Basis gab ihrem Text die Farbe der Links und ihrem Symbol die Akzentfarbe, auf einem Hintergrund in Akzentfarbe
  (1,1:1).
- **Granite**: Die Schaltflächen in der Fußzeile einer Karte („Bild posten“, „Meine Bewerbung ansehen“, die
  Seitennavigation der Sitzungen) nahmen das Rot der Links auf der Tinte an (2:1); „Diese Sanktion aufheben“ hatte nur
  3,7:1; auf einem Telefon rutschte die Initiale der Titelseite über die Zeile „Von … am …“ darunter; die kleinen
  Schaltflächen sind 24 px hoch.
- **Pulse, bei Nacht**: Der Zähler im Menü des Mitgliederbereichs ist lesbar: weiß auf dem helleren Nacht-Ziegelrot
  hatte er nur 3,9:1; er behält jetzt das Ziegelrot des Tages.
- **Das Menü des Mitgliederbereichs rastet auf einem Telefon am Anfang eines Reiters ein**: Pixelgenau zentriert ließ es
  den vorherigen Reiter am Anfang seines Namens abgeschnitten („…chrichtigungen“).
- **Das Einschalten des „Debug-Modus“ aus dem Monitoring lässt seine Seite nicht mehr abstürzen**: Die Antwort stürzte
  ab, sobald eine Benachrichtigung wartete („Cannot use object of type stdClass as array“).
- **Die Leitfäden zählen die Themes des Pakets**: „Konzepte“ kündigte vier an (Chronique und Pulse fehlten), und „Ein
  Theme erstellen“ nannte vier Themes auf der gemeinsamen Basis statt sechs.

### Geändert

- **`check-mise-en-page` schlägt keinen falschen Alarm mehr**: Text im Cookie-Banner oder in einer am unteren
  Bildschirmrand klebenden Leiste läuft absichtlich über eine Grafik (nur dieselbe Ebene macht einen Konflikt); der
  verborgene Teil eines Textes — der Code eines scrollenden Blocks, eine gekürzte Beschreibung — „überlappt“ nicht mehr
  die nächste Spalte; ein Reiter in einem scrollenden Streifen ist nicht mehr „jenseits des linken Rands verloren“.
  Weniger falsche Fehler, und die echten kommen weiterhin heraus (geprüft auf einer präparierten Testseite, mit der
  alten und der neuen Sonde).

## [1.2.40] — 2026-10-07

Die Indexierung durch Suchmaschinen, überprüft nach den Warnungen der Search Console auf der offiziellen Website: Google
indexierte weder die Forenthemen noch die Wiki-Seiten, und ihm wurden leere Seiten angeboten. Dazu die
Willkommensnachricht, die nicht mehr verschickt wurde.

### Behoben

- **Inhalte ohne Sprache geben sich nicht mehr als Duplikate aus**: Ein Forenthema, eine Wiki-Seite, ein Ticket, ein
  Ereignis, eine Kleinanzeige, ein Rezept, eine Umfrage, ein Stellenangebot, eine Auszeichnung, eine Spendenkampagne —
  einmal geschrieben — antworteten unter jeder Sprache der Website, nur die Menüs übersetzt, und jede Adresse erklärte
  sich für kanonisch. Google wählte eine andere und indexierte keine davon („Duplikat – Google hat eine andere Seite als
  der Nutzer als kanonische Seite bestimmt“: auf der offiziellen Website, in sechs Sprachen, 59 Inhalte sechsfach
  aufgeführt, 354 der 580 Adressen ihrer Sitemap). Ihre kanonische Adresse liegt jetzt in der ersten Sprache der
  Website, der einzigen, die in `hreflang` und in der Sitemap angekündigt wird. Ebenso für die Seiten, die aus solchen
  Inhalten bestehen: die FAQ, das Glossar, die Zitate, die Links, die Downloads, der Shop und die Liste der Ereignisse.
- **Leere Seiten werden Suchmaschinen nicht mehr angeboten**: das Webradio ohne Stream und ohne Sendung, das Gästebuch
  ohne Nachricht und die Spendenseite ohne Kampagne standen in der Sitemap, in jeder Sprache — „Soft 404“ für Google.
  Sie bleiben draußen, solange sie leer sind, und erklären `noindex`.
- **`/fr/forum/` leitet jetzt dauerhaft auf `/fr/forum` um**: Die Umleitung einer Adresse mit abschließendem oder
  doppeltem Schrägstrich war vorübergehend (302) — und die erste verlor alles nach dem `?`; sie ist jetzt dauerhaft
  (301) und behält das Ende der Adresse.
- **Die Willkommensnachricht wird wieder verschickt**: Das Modul Mitglieder fand das Nachrichtenmodul nicht, und ein
  neues Mitglied erhielt nichts. Ein Modul, das ein anderes lädt (`$this->module(…)`), bekam immer „nichts“ — derselbe
  Fehler nahm den Webhooks der Kommentare Titel und Adresse des kommentierten Inhalts.
- **Die Sicherungen der Updates stapeln sich nicht mehr**: Die Website behält immer die fünf neuesten und entfernt die
  anderen nach dreißig Tagen, aber dicht aufeinanderfolgende Updates ließen sie alle durch — die offizielle Website trug
  vierzig, 663 MB. Jetzt bleiben nie mehr als zehn, wie alt sie auch seien.

### Geändert

- **`check-seo` vergleicht die Sprachen miteinander**: Es zieht denselben Pfad aus jeder Sitemap und meldet denselben
  Text, der unter mehreren Sprachen ausgeliefert wird, jeweils kanonisch. Es erklärte die offizielle Website für
  gesund; jetzt findet es dort diese Duplikate. Ein Modul kann seine Inhalte ohne Sprache angeben: Ein mit
  `'sans_langue' => TRUE` markierter Eintrag der Sitemap steht nur in der Sitemap der ersten Sprache, und
  `nf_seo_sans_langue()` setzt die kanonische Adresse der Seite (Leitfaden „Ein Modul erstellen“).

## [1.2.39] — 2026-10-06

Pulse, auf der veröffentlichten Demo überprüft: Das Forum auf einem Telefon klebt seine Schaltflächen nicht mehr
aneinander, in jedem Theme; und ein Blogbeitrag, der auf Telefonen noch über den Bildschirmrand hinausging.

### Behoben

- **Das Forum auf einem Telefon klebt seine Schaltflächen nicht mehr aneinander**: Die Leiste eines Themas (Zurück,
  Antworten und die Moderationswerkzeuge) und die Leiste eines Forums halten einen Abstand zwischen ihren Schaltflächen
  und zwischen ihren Reihen; die Schaltflächen einer Nachricht rücken über ihr Datum, das nicht mehr über drei Zeilen
  umbricht; ein Thementitel berührt die Schaltfläche „Folgen“ nicht mehr. In jedem Theme.
- **Ein Blogbeitrag ragt auf einem Telefon nicht mehr hinaus**: Ein langes Stück Code (zum Beispiel ein Dateipfad) bricht
  um, und eine Tabelle, die zu breit bleibt, scrollt innerhalb ihrer Spalte (der Beitrag zur 1.2.0 ging über den
  Bildschirmrand hinaus).

## [1.2.38] — 2026-10-06

Ein neues Theme: **Pulse**, das gemeinsame Zuhause von Vereinen, Clubs und Communities, mit drei Neuerungen, die jedem
Theme dienen — die Website in Zahlen, der nächste Termin und die neuesten Fotos.

### Hinzugefügt

- **Das Theme Pulse.** Eine helle Leiste, die oben bleibt, mit den Bereichen als Pillen, dem Konto und dem Aufruf
  „Beitreten“ immer in Sicht (eine Schaltfläche „Menü“ auf einem Telefon); die Startseite ist ein Mosaik aus Kacheln —
  der Name und der Leitspruch der Website auf dem Startbild, der nächste Termin, die Website in Zahlen, die News, die
  Termine, die Umfrage, die Unterhaltungen, die Dokumente, die Fotos, die Partner —, die der Live-Editor Kachel für
  Kachel neu anordnet (hell, Sonne oder Schiefer). Das Forum liest sich als Karten, mit seinen Statistiken und seiner
  Aktivität daneben; die dunkle Fußzeile trägt die Sitemap und die sozialen Netzwerke. Hell bei Tag, „Schiefer“ bei
  Nacht nach Wahl des Besuchers; Überschriften in Bricolage Grotesque, Text in Manrope. Einstellungen: Farben,
  Startbild, Logo und die Adresse der Schaltfläche „Beitreten“ (eine Mitgliedsseite, ein Formular auf einer anderen
  Plattform oder die Registrierung der Website).
- **Das Widget „Die Website in Zahlen“**: bis zu vier Zahlen, gewählt unter den Mitgliedern, den Unterhaltungen, den
  Forenbeiträgen, den News, den kommenden Ereignissen und den Fotos — gezählt wird nur, was der Besucher sehen darf
  (eingeschränkte Foren und Gruppenalben bleiben außerhalb der Zählung). In Pulse zählen die Zahlen hoch, wenn die
  Kachel erscheint, außer der Besucher hat um weniger Bewegung gebeten.
- **Der Kalender zeigt auch „Der nächste Termin“**: sein Datum in großer Schrift, sein Titel, in wie vielen Tagen, um
  welche Uhrzeit (oder „Ganztägig“) und wo, den Anfang seiner Beschreibung und eine Schaltfläche, um ihn zu öffnen.
- **Die Galerie zeigt auch „Die neuesten Fotos“**, als Raster — nur aus den Alben, die der Besucher sehen kann.

### Behoben

- **Der Stil „Farbiger Titel“ von Chronique und Granite**: Der Titel des Panels hatte die Farbe seines Hintergrunds
  (Petrol auf Petrol, Rot auf Rot) und war daher unsichtbar. Er ist jetzt farbig, auf dem Papier, wie diese Themes es
  vorsahen — und die Vorschau des Stils im Live-Editor zeigt ihn so.
- **Der Stil „Farbiges Widget“ von Granite**: Der Text, die Formularbeschriftungen und die Schaltfläche eines Widgets in
  diesem Stil konnten weiß auf dem Papier werden (oder Tinte auf Tinte, bei Nacht). Sie behalten die Farben der Zeitung.
  Die Kontrastprüfung kann jetzt ein Widget in jedem Theme und jedem Panel-Stil messen.
- **Der Brotkrumenpfad über einem News-Artikel**: Jedes Theme sah ihn vor, er erschien nie — die Regel, die ihn
  platzierte, zielte auf eine Adresse, die es nicht gibt. Er erscheint jetzt auf den Artikeln und auf der Liste der
  News, in jedem Theme.
- **Zwei nebeneinanderliegende Kästen bleiben ausgerichtet**: Unter einem News-Artikel saß „Weitere News dieses Autors“
  14 bis 22 px tiefer als „Über den Autor“ (ebenso auf der Seite eines Ereignisses und im Posteingang).
- **Chronique: Ein Artikel behält seine Ränder** innerhalb seiner Karte; Titel und Text berührten den Rand.
- **Text auf einem Verlauf ist lesbar**: die Schaltfläche „Server beitreten“ des Discord-Widgets, die Initialen des
  Wappens von Forge, Titel und Leitspruch des Banners von Blockcraft bei Tag. Die Kontrastprüfung misst jetzt Text auf
  einem Verlauf, und als angemeldetes Mitglied (den Mitgliederbereich eingeschlossen).
- **Ein Blogbeitrag mit einer einzigen Überschrift erscheint nicht mehr gequetscht**: Ohne Inhaltsverzeichnis behielt
  die Seite ihre Spalte, der Text drängte sich auf 200 px, und der Seitenkasten (Autor, Teilen) nahm seinen Platz ein —
  die Versionshinweise von 1.2.32, 1.2.33 und 1.2.37 auf der offiziellen Website. Danke an Blober für die Meldung.
- **`admin.php` und `ajax.php` antworten „Seite nicht gefunden“** statt mit einem Serverfehler (und zwei
  Protokollzeilen jedes Mal, wenn ein Bot diese Adressen abtastet).
- **Die Zeitleiste der Saison**: „In 5 Tagen“ bricht auf einem Telefon nicht mehr über zwei Zeilen um.

## [1.2.37] — 2026-10-06

Chronique, auf der Demo überprüft, wie man sie sieht — als Besucher und angemeldet, bei Tag, bei Nacht, auf einem
Telefon —, und eine Demo, die in der Gegenwart lebt.

### Behoben

- **Der Mitgliederbereich von Chronique.** In seinem Panel lag „Passwort vergessen?“ auf „Anmelden“, sobald die Spalte
  sie untereinander stellte; einmal angemeldet, blieben die Symbole des Menüs dunkelgrau auf dem Schiefer, und
  „Abmelden“ fiel in ein weißes Rechteck (schwarz bei Nacht). Es sind jetzt weiße Links, eine einzige gefüllte
  Schaltfläche und ein Abstand in beide Richtungen — auch mit „Konto erstellen“, wenn die Registrierung offen ist.
- **Chronique behält auf einem Telefon die Anmeldung im Kopfbereich**, als Symbol: Das Wort „Anmelden“ verschwand, und
  man musste bis ans Ende der Seite scrollen, um sich anzumelden.
- **Die Schaltflächen des Anmeldepanels behalten ihren Abstand, wenn sie umbrechen**, in den fünf Themes, die die
  gemeinsame Basis teilen (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend: Die Feldsymbole des farbigen Panels erscheinen** (Benutzername und Passwort): Sie waren weiß auf einem
  hellen Kasten.
- **Die Mitgliederliste, bei Nacht**: Ein soziales Netzwerk, das ein Mitglied nicht ausgefüllt hatte, wurde zu einem
  grauen Punkt, der am nächsten klebte. Es ist ein ausgegrautes Symbol ohne Hintergrund, und die Schaltflächen der
  ausgefüllten Netzwerke haben einen echten Abstand — auf einer Zeile in einer schmalen Karte.
- **Die Demo lebt in der Gegenwart.** Jede Viertelstunde zurückgesetzt, zeigte sie weiter die Inhalte vom Tag ihres
  Schnappschusses: „kommende“ Ereignisse und Matches, die seit Wochen vorbei waren, ein leerer Kalender, eine Zeitleiste
  der Saison, die sich geleert hätte. Jedes Zurücksetzen verschiebt jetzt die Daten der Inhalte um die verstrichene Zeit
  nach vorn (die Abstände zwischen ihnen ändern sich nicht); die Mitglieder behalten ihre.

## [1.2.36] — 2026-10-06

Ein neues Theme: **Chronique**, das Notizbuch der Saison für Vereine und Clubs, mit zwei neuen Funktionen, die jedem
Theme dienen — die Zeitleiste der Saison und die Woche im Kalender.

### Hinzugefügt

- **Das Theme Chronique.** Die Website als Notizbuch der Saison: ein dezenter Kopfbereich, der oben bleibt, mit einer
  dünnen Linie, die deiner Lektüre folgt, eine Schaltfläche „Inhaltsverzeichnis“, die die ganze Website im Vollbild
  öffnet (jeder Bereich nummeriert), und der Aufruf „Beitreten“ für Besucher; die Startseite öffnet sich auf eine große
  Schlagzeile — die Saison, der Name der Website, ihr Slogan — und die laufende Woche; dann erzählt die Zeitleiste die
  Saison Monat für Monat, neben einer Spalte, die an ihrem Platz bleibt (der Mitgliederbereich, die Dokumente, die
  Partner). Das Forum liest sich als nummerierte Kapitel, der Mitgliederbereich als Notizbuch, und die Fußzeile
  schließt das Buch. „Papier“ bei Tag, „Lampenlicht“ bei Nacht; Überschriften in Fraunces, Text in Work Sans, Daten in
  IBM Plex Mono. Einstellungen: Farben, Eröffnungsbild, Logo und die Adresse der Schaltfläche „Beitreten“ (eine
  Mitgliedsseite, ein Formular auf einer anderen Plattform oder die Registrierung der Website).
- **Das Widget „Zeitleiste der Saison“**: was die Website erlebt, Monat für Monat, auf einer Zeitleiste — kommende
  Kalenderereignisse (ihr Punkt pulsiert), News, Forenunterhaltungen und Fotoalben, jeweils wenn ihr Modul installiert
  ist, und nur, was der Besucher lesen darf (eingeschränkte Foren und Gruppenalben bleiben verborgen). Die Zahl der
  Einträge und die vergangenen Monate sind einstellbar.
- **Der Kalender zeigt auch „Diese Woche“**: die sieben Tage der laufenden Woche, die mit einem Ereignis markiert, heute
  eingekreist, und den nächsten Termin — in der Zeitzone dessen, der schaut.

### Behoben

- **Im Tagmodus nehmen die Bootstrap-Komponenten die Farben des Themes an**: Ein FAQ-Akkordeon, ein Kontrollkästchen,
  eine Tabelle oder ein Aufklappmenü blieben weiß, mit dunkelgrauem Text, auf dem Papier von Granite. Element für
  Element geprüft auf Forge, Granite, Blockcraft und Extend: Nur ihre Farben ändern sich, und der Nachtmodus bleibt, wie
  er war.
- **„Tagmodus“ und „Nachtmodus“ sind übersetzt**: Der Tooltip und der Name der Umschalt-Schaltfläche, den
  Bildschirmleser vorlesen, blieben auf einer Website in einer anderen Sprache auf Französisch (Forge, Granite,
  Blockcraft, Extend). Die Prüfung auf fest eingetragene Texte kennt jetzt die Wörter „jour“ und „nuit“.
- **Die Anpassungsseite eines Themes schreibt keine Warnung mehr ins Protokoll**, wenn seine Einstellungen noch nicht
  existieren (ein Theme, das registriert wurde, ohne dass seine Installation seine Einstellungen angelegt hat): Eine
  Bildposition fällt auf ihren Standard zurück.

## [1.2.35] — 2026-10-06

Das zweite von Grund auf neu gebaute Theme: **Granite 2.0.0 „Gazette“**, die Zeitung der Vereine und Clubs.

### Geändert

- **Granite 2.0.0 „Gazette“: ein neues Layout, kein neuer Anstrich.** Die Website wird zur Zeitung des Clubs: das Datum
  des Tages und das Konto auf einer Zeile ganz oben, der Name der Website sehr groß gedruckt (das Logo darüber, falls
  vorhanden), eine doppelte Linie, dann die Rubriken in Kapitälchen zwischen zwei Linien. Auf der Startseite lässt eine
  Zeile „Kurz notiert“ die neuesten Forenthemen durchlaufen; sie hält beim Überfahren an, mit der Tastatur und mit
  ihrer Pausenschaltfläche, und bleibt still, wenn der Besucher um reduzierte Bewegung bittet. Die Titelseite öffnet
  sich auf den Hauptartikel in großer Schrift, mit seiner verzierten Initiale, dann die folgenden in Spalten, getrennt
  durch Linien; die rechte Spalte trägt den Kalender, die Umfrage und wer online ist. Die Blöcke werden zu Panels ohne
  Kasten, in Kapitälchen über einer Linie betitelt; ein Artikel liest sich wie der Fließtext einer Zeitung; das Forum
  nimmt das Aussehen einer Seite mit Leserbriefen an; der Mitgliederbereich das eines Mitgliedsausweises, gestempelt mit
  der ersten Gruppe des Mitglieds; die Fußzeile wird zum Impressum (die Partner, dann wer herausgibt). Ein dünner
  Lesebalken folgt der Seite. Der Tag ist Papier, die Nacht eine „bedruckte“ Seite, nach Wahl des Besuchers;
  Überschriften in Playfair Display, Text in Source Serif. Auf Telefonen scrollen die Rubriken seitwärts, und alles geht
  in eine Spalte.
- **Die Zonen von Granite tragen den Namen ihres Platzes**: „Rubriken“, „Kurz notiert“, „Inhalt“, „Nach-Inhalt“,
  „Fußzeile“.
- **Die Einstellungen von Granite**: Das Bannerbild wird zum Titelbild, und die Farbe des Titels setzt, einmal
  geändert, ein Band hinter den Namen, wie der Titelkopf einer Tageszeitung; das Logo wird endlich verwendet (über dem
  Namen); eine Einstellung „Initialen“ ersetzt die Einstellung der fixierten oberen Leiste, die keinen Zweck mehr hat.

### Behoben

- **Kein Bootstrap-Blau mehr unter den Farben eines Themes.** Der Balken einer Umfrage, das angekreuzte Kästchen und
  der gewählte Optionsknopf (auch in der Verwaltung), die aktive Seite, der gedrückte Eintrag eines Menüs, der Pfeil
  eines Akkordeons, der Leuchtrand des Tastaturfokus und die Zustände einer Hauptschaltfläche behielten das
  Standardblau von Bootstrap. Sie nehmen die Akzentfarbe des Themes an, in jedem Theme; eine neue Prüfung,
  `check-bleu-bootstrap`, sucht dieses Blau in den ausgelieferten Seiten, in Ruhe und im Fokus, bei Tag und bei Nacht.
- **Die von Forge und Granite gesetzten Rubriken sind übersetzt**: „Matchs“, „Nous rejoindre“, „À la une“, „Agenda“ und
  „Documents“ blieben auf einer Website in einer anderen Sprache auf Französisch. Die Übersetzungsprüfung kontrolliert
  jetzt jeden Menütitel, den ein Theme setzt.
- **Der Live-Editor zeigt die Website mit ihren eigenen Schriften**: Er zwang dem Inhalt der Zonen, die er einrahmt,
  seine eigenen auf.
- **Die Kontrastprüfung misst auch Text auf einer Körnung** (Papier, ein Rastermuster): Sie hielt sie für ein Bild und
  gab das Messen auf.

## [1.2.34] — 2026-10-06

Das erste von Grund auf neu gebaute Theme: **Forge 2.0.0 „Coulée“**, für kompetitive Clans.

### Geändert

- **Forge 2.0.0 „Coulée“: ein neues Layout, kein neuer Anstrich.** Die Navigation verlässt den oberen Seitenrand für eine
  Stahlleiste an der Seite, mit dem Logo der Website oben und dem Konto unten (Avatar, Benachrichtigungen, Tagmodus,
  Menü des Mitgliederbereichs); auf Telefonen wandern Logo und Konto in eine obere Leiste und die Navigation in eine
  Reiterleiste am unteren Bildschirmrand, und über fünf Einträge hinaus gehen die letzten in „Mehr“. Die Startseite
  öffnet sich auf eine Feuerstelle aus Lava, aus der Glut aufsteigt: die Diashow, ihr Titel in Metall gegossen, und die
  Platte der letzten Ergebnisse. Darunter ein Armaturenbrett: die News (die erste hervorgehoben) und daneben die
  kommenden Matches, die Auszeichnungen und wer online ist. Die Blöcke sind Platten mit abgeschnittenen Ecken, deren
  Rand beim Überfahren glüht; das Forum legt jedes Forum in eine Stahlschublade mit einer Hitzeanzeige für seine
  Aktivität; der Mitgliederbereich und das öffentliche Profil erhalten das Namensschild und abgeschrägte Reiter; die
  Fußzeile ist vernietet und trägt die Partner. Der Tagmodus hellt den Inhalt auf, während Leiste und Feuerstelle Stahl
  und Feuer bleiben. Jede Bewegung hält an, wenn der Besucher um reduzierte Bewegung bittet. Die Blöcke der Matches, der
  Auszeichnungen und der Partner werden nur platziert, wenn ihre Module installiert sind.
- **Die Zonen von Forge tragen den Namen ihres Platzes**: „Navigationsleiste“, „Seitenkopf“, „Inhalt“, „Nach-Inhalt“,
  „Fußzeile“.
- **Die Einstellungen von Forge**: Das Logo wird endlich verwendet (das Emblem der Leiste; ohne Logo die Initialen der
  Website); das Bannerbild wird zum Bild der Feuerstelle; eine Einstellung „Glut in der Feuerstelle“ (keine, sanft,
  lebhaft) ersetzt die Einstellung der fixierten oberen Leiste, die keinen Zweck mehr hat.
- **Das Widget der Auszeichnungen** zeigt den Podestplatz („1.“, „2.“, „3.“) und den Namen des Wettbewerbs über die ganze
  verfügbare Breite, mit Ort und Plattform darunter; die Zeile begann mit der Plattform und schnitt den Namen bei
  zwanzig Zeichen ab.
- **Die Bilder der Diashow der Demo tragen ihren Titel nicht mehr**: Die Diashow schreibt ihn bereits darüber, und er
  erschien zweimal, auf Telefonen quer über der Beschriftung.

### Behoben

- **Eine Einstellung eines Themes erscheint sofort** (Akzentfarbe, Hintergrund- und Bannerbilder…): Die Adresse des
  Stylesheets folgte nur dem Datum der Datei, und der Browser behielt nach einer Änderung das alte Stylesheet.
- **Die Anpassungsseite der Themes Forge, Granite, Blockcraft und Extend** trägt den Namen des Themes als Titel, statt
  eines nicht übersetzten „Dashboard“, das auf jedem Reiter erschien.
- **Auf Englisch sagen die Auszeichnungen nicht mehr „2th“ oder „3th“**: Die Podestplätze haben ihre eigenen Wörter, die
  anderen Plätze lauten „#4“.
- **Das Neuinstallieren eines Themes schreibt keine Warnung mehr ins Protokoll** im Debug-Modus (eine Warnung
  „Deprecated“ von PHP 8.2).

## [1.2.33] — 2026-10-06

Eine Korrekturversion: Die öffentliche CI des Repositorys `neofrag`, in der 1.2.32 durch ein Problem der Werkzeuge
kaputt, ist repariert. Für die Websites ändert sich nichts.

### Behoben

- **Die automatischen Prüfungen (CI) des Repositorys `neofrag` laufen wieder durch.** Seit der 1.2.32 konnte der
  Schritt, der die passende Version des Repositorys `extensions` sucht, fehlschlagen: Er hörte auf, die Liste der
  Versionen zu lesen, sobald er die richtige gefunden hatte, und `git`, mitten im Schreiben abgeschnitten, schlug fehl,
  jetzt, da die Liste lang ist. Er liest jetzt die ganze Liste; dieselbe Korrektur in der CI von `extensions`.
- **Die Linkprüfung der Dokumente liest nicht mehr die der mitgelieferten Bibliotheken** (die Lizenz von TinyMCE): Sie
  lassen sich auf unserer Seite nicht korrigieren, und gnu.org schloss die Verbindung zu ihrer alten Adresse.

## [1.2.32] — 2026-10-06

Die Überarbeitung der Themes beginnt: Forge, Granite, Blockcraft und Extend teilen jetzt eine gemeinsame Basis. Auf
dem Bildschirm ändert sich nichts; es ist das Fundament, auf dem jedes seine eigene Identität erhalten wird.

### Geändert

- **Die Themes Forge, Granite, Blockcraft und Extend teilen eine gemeinsame Basis** (`css/nf-socle-themes.css`): 153 der
  Regeln, die sie gemeinsam hatten, sind jetzt nur noch einmal geschrieben, im Kern; etwa fünfzig andere bleiben in
  jedem Theme, weil ihre Position im Stylesheet zählt. Auf dem Bildschirm ändert sich nichts — geprüft Element für
  Element und Pixel für Pixel, auf den vier Themes, bei Tag und bei Nacht, auf dem Computer und auf dem Telefon. Es ist
  der erste Schritt ihrer Überarbeitung: Jedes wird bald sein eigenes Aussehen haben. Die vier Themes gehen auf 1.1.0
  und benötigen den Kern 1.2.32.

## [1.2.31] — 2026-10-06

Der Mitgliederbereich, letzte Schritte: die Sicherheit des Kontos — angemeldete Geräte lassen sich abmelden, die
Sanktionen für Avatar und Signatur greifen endlich — und die Benachrichtigungen, in den Mitgliederbereich verlegt, wobei
jedes Mitglied wählt, was es erhält. Die Überarbeitung des Mitgliederbereichs ist abgeschlossen.

### Hinzugefügt

- **„Sicherheit“ zeigt die Geräte, auf denen das Konto angemeldet ist, und lässt sie abmelden**: jede Sitzung mit ihrem
  Browser, ihrem System, ihrer IP-Adresse und ihrer letzten Aktivität, „Dieses Gerät“ für das, von dem aus man schaut;
  „Abmelden“ für ein anderes, oder „Alle anderen Geräte abmelden“. Bisher zeigte „Sicherheit“ nur das
  Anmeldeprotokoll, und eine anderswo offene Sitzung ließ sich nicht schließen. Auf einer Demo-Website, deren Konto
  geteilt ist, lässt sich keine Sitzung schließen.
- **Jedes Mitglied wählt die Benachrichtigungen, die es erhält**, in „Benachrichtigungseinstellungen“: ein Kästchen pro
  Art von Benachrichtigung (eine private Nachricht, eine Antwort in einem verfolgten Thema, eine Erwähnung, ein
  Kommentar, eine Reaktion, eine Erinnerung an ein Ereignis…), um sie auf der Website zu erhalten, und ein Kästchen „Per
  E-Mail“ für die, die die Website auch per E-Mail verschickt (private Nachrichten, Antworten im Forum und
  Erwähnungen). Ohne Einstellung erhält es alles, wie bisher. Für Modulautoren: Ein Modul gibt die seinen über
  `types_de_notification()` an (siehe den Leitfaden zum Erstellen eines Moduls).
- **„Meine Benachrichtigungen“ kommt in den Mitgliederbereich**, unter dem Eintrag „Benachrichtigungen“ seines Menüs,
  zwanzig pro Seite: Die Seite lebte abseits, unter `/notifications`, und zeigte nur die letzten fünfzig (jetzt bis zu
  den letzten fünfhundert). Die alte Adresse führt zur neuen.

### Behoben

- **Die Sanktionen für Avatar und Signatur greifen jetzt.** Ein Moderator konnte sie verhängen, aber das Profil prüfte
  sie nie: Ein sanktioniertes Mitglied änderte seinen Avatar und seine Signatur wie zuvor. Es sieht jetzt, was die
  Sanktion verbietet, ihren Grund und bis wann.
- **Acht Datenmodelle des Kerns lesen immer ihre eigene Tabelle** (Sitzungen, Anmeldeprotokoll, Addons,
  Übersetzungen, Protokolle…): Aus einem Modul geladen, suchten sie eine Tabelle, die es nicht gibt. Nichts zeigte es
  bisher auf dem Bildschirm; die neue Geräteliste hätte es getan. Es ist derselbe Fehler wie der hinter dem Favicon und
  den gelöschten Dateien, behoben in der 1.2.29.
- **Eine stumm geschaltete Erwähnung im Forum verschluckt die Antwort nicht mehr.** Ein Mitglied, das in einem Thema
  erwähnt wurde, dem es folgt, erhielt nichts, wenn die Erwähnung für es ausgeschaltet war — die E-Mail bei Erwähnung
  von der Website ausgeschaltet („@user-Erwähnungen per E-Mail benachrichtigen“) oder die Erwähnungen in seinen Einstellungen
  ausgeschaltet. Es erhält jetzt die Antwort. Wenn die Erwähnung es erreicht, ersetzt sie die Antwort, damit es nicht
  zweimal benachrichtigt wird.

## [1.2.30] — 2026-10-05

Der Mitgliederbereich, zweiter Schritt: das öffentliche Profil und das, was jeder dort zeigen möchte. Und eine
Korrektur, die zählt: Ein angemeldetes Mitglied kann wieder im Forum lesen und schreiben.

### Hinzugefügt

- **Das öffentliche Profil eines Mitglieds wird eine echte Seite.** Sein Titelbild als Banner (man konnte es
  hochladen, aber es erschien nirgends), sein Avatar, der es überlappt, sein Benutzername, sein Rang, seine Gruppen und
  seine Anwesenheit; für einen angemeldeten Besucher „Kontaktieren“ (wenn die privaten Nachrichten installiert sind)
  und das Fähnchen „Diesen Inhalt melden“, oder „Mein Profil bearbeiten“ auf dem eigenen; dann Reiter, jeder unter
  seiner eigenen Adresse: Über (Zitat, Identität, Links, die öffentlichen Felder der Website und seine Statistiken),
  Aktivität, und die, die installierte Module mitbringen — Forum, Blog, Teams, Kleinanzeigen. Ein Modul, das zu diesem
  Mitglied nichts zu zeigen hat, fügt keinen leeren Reiter hinzu.
- **Jedes Mitglied wählt, was sein Profil zeigt**, in „Datenschutz und Daten“: Seine Punkte, sein Karma und seine
  VIP-Tage bleiben für sich, bis es sie zeigt (sie waren für alle sichtbar, Besucher eingeschlossen); sein Alter und
  seine Online-Anwesenheit bleiben sichtbar, bis es sie verbirgt. Sein Rang bleibt öffentlich. Die Wahl gilt überall:
  die Karte, die sich beim Überfahren eines Benutzernamens öffnet, der Punkt des Avatars, die Kader der Teams, die
  Widgets „Wer ist online?“ und „Forum-Aktivität“, die Mitgliedersuche, die Geburtstage der Uhr. Auf seinem eigenen
  Profil sieht ein Mitglied alles, mit einem Schloss bei dem, was nur es selbst sehen kann.

### Behoben

- **Ein angemeldetes Mitglied kann wieder im Forum lesen und schreiben**, und sieht die Galerien, Seiten,
  Ereignistypen und Ordner, die Besuchern offenstehen. Seit der Überarbeitung der Berechtigungen hatte die Rolle
  „Mitglied“ nur, was ihr ausdrücklich gegeben worden war: Ein Mitglied sah „Kein Forum“, wo ein Besucher alles lesen
  konnte, und konnte nirgends schreiben — nur Administratoren, die die Berechtigungen umgehen, sahen kein Problem. Die
  ursprünglichen Regeln sind zurück: Was ein Besucher kann, kann ein Mitglied; was nur Besuchern verweigert wird, bleibt
  Mitgliedern erlaubt. Eine Migration stellt sie auf jeder Website wieder her, ohne eine bereits für Mitglieder
  gesetzte Regel anzutasten.
- **Die Reputationsränge (Neuling, Bronze, Silber…) sind übersetzt**: Sie erschienen in jeder Sprache auf Französisch.
  Und der Tooltip eines Rangs verrät die Karma-Punktzahl nicht mehr.
- **Auf einer Demo-Website zeigt das Widget „Forum-Aktivität“ das bei der Installation angelegte
  Administratorkonto nicht mehr** unter den Online-Mitgliedern; „Wer ist online?“ ließ es bereits aus.
- **Eine in einem Profil ohne „https://“ eingegebene Website führt jetzt zu dieser Website**: Sie wurde zu einem Link
  auf eine Seite der Website selbst.
- **Ein Update überschreibt nicht mehr den Katalog der Website, die den Marketplace ausliefert.** Das Update-Paket
  trug einen alten Katalog, gebaut zur 1.2.22: Auf dieser Website abgelegt, passte er nicht mehr zu den Archiven, und
  jede Addon-Installation wurde abgelehnt, bis der Katalog neu gebaut war. Eine gewöhnliche Website liest den
  Online-Katalog und war nicht betroffen.
- **In „Mein Bereich“ richtet sich die Gruppe des Mitglieds links unter seinem Benutzernamen aus**: Sie stand mittig
  im Kopf.

## [1.2.29] — 2026-10-05

Der Mitgliederbereich, erster Schritt seiner Überarbeitung: ein einziger Rahmen, ein einziges Menü, Einstellungen
dort, wo man sie erwartet. Und der Marketplace bietet einer Website kein Addon mehr an, das für eine neuere Version von
NeoFrag gemacht ist.

### Hinzugefügt

- **Ein neues Passwort muss mindestens 10 Zeichen lang sein.** Es wird abgelehnt, wenn es den Benutzernamen wiederholt,
  nur ein oder zwei verschiedene Zeichen verwendet („aaaaaaaaaa“, „ababababab“) oder zu den häufigsten Passwörtern
  gehört („qwertyuiop“, „1234567890“…). Die Regel gilt bei der Registrierung, beim Ändern und beim Zurücksetzen (beim
  Zurücksetzen wird der Benutzername nicht verglichen). Bestehende Passwörter bleiben gültig.

### Geändert

- **Der Mitgliederbereich hat einen einzigen Rahmen und ein einziges Menü.** Er wechselte von einer Seite zur nächsten
  die Form — ein Menü links unter der Profilkarte, eine zugeklappte Leiste „Menü“ oben, gar nichts — und eine Seite
  hatte drei Namen. Die Kontoseiten haben jetzt alle dasselbe Menü an derselben Stelle: eine Spalte links auf einem
  Computer, ein scrollender Streifen von Reitern auf einem Telefon, die aktuelle Seite hervorgehoben. Das Menü führt
  auch zu den Seiten der Module: Nachrichten und Benachrichtigungen mit ihrer Zahl an Ungelesenen, die Abonnements des
  Forums (die kein Link erreichte), die Moderation. Die obere Leiste der Themes Blockcraft, Extend, Forge und Granite
  und das Widget „Mitgliederbereich“ verwenden dieses Menü, mit denselben Wörtern.
- **Die Einstellungen sind dort, wo man sie erwartet.** „Mein Konto“ vereint Benutzername, E-Mail-Adresse, Passwort,
  Sprache (die man nur mit der Sprachauswahl der Website wählen konnte) und Zeitzone (die mitten im öffentlichen
  Profil stand); „Sicherheit“ behält die Zwei-Faktor-Authentifizierung und das Anmeldeprotokoll; eine Seite
  „Datenschutz und Daten“ enthält den Export deiner Daten und die Löschung deines Kontos, bisher unter „Sicherheit
  (2FA)“ eingeordnet.
- **„Mein Bereich“ öffnet sich auf einen kompakten Kopf** — Avatar, Benutzername, Gruppen, „Mein Profil ansehen“ und
  „Mein Profil bearbeiten“ — statt der großen Profilkarte, die das Menü auf einem Telefon um einen ganzen Bildschirm
  nach unten schob.

### Behoben

- **Der Marketplace bietet einer Website kein Addon mehr an, das für eine neuere Version von NeoFrag gemacht ist.** Nur
  das Aktualisieren eines Addons prüfte die NeoFrag-Version, die es verlangt, nicht seine Installation: Eine Website,
  die im Rückstand war, konnte ein Addon installieren, das Funktionen aufrief, die ihre Version nicht hatte, und
  kaputtgehen. Der offizielle Marketplace schickt jetzt jeder Website den für ihre Version gemachten Katalog (eine
  Website, die älter ist als alle aufbewahrten Kataloge, erhält den ältesten). Das Fenster des Marketplace lässt auch
  ein zu neues Addon aus und sagt, man solle zuerst die Website aktualisieren. Die Schaltfläche „Updates“ kündigt eine
  neue NeoFrag-Version anhand der Update-Prüfung des Monitorings an, nicht mehr nur anhand des Katalogs.
- **Das vom Administrator gewählte Favicon ist auch das Symbol der Website auf einem Telefon, und eine gelöschte Datei
  verlässt wirklich die Festplatte.** Ein interner Fehler hinderte die Website daran, diese Dateien zu finden: Das
  Favicon fehlte, wenn man die Website zum Startbildschirm eines Telefons hinzufügte (jedes Mal mit einer Warnung im
  Protokoll), und die Datei eines im Forum oder in den privaten Nachrichten gelöschten Anhangs blieb auf der Festplatte
  und in der Datenbank, ebenso Avatar und Titelbild eines Mitglieds, das sein Konto löscht.

## [1.2.28] — 2026-10-05

Eine Korrekturversion: der Mitgliederbereich eines mit Discord, GitHub oder Google angelegten Kontos, der Export und
die Löschung personenbezogener Daten und die Demo-Website.

### Behoben

- **Ein Mitglied, das sich mit Discord, GitHub oder Google registriert hat, ist nicht mehr in seinem Konto
  eingesperrt.** Ohne Passwort konnte es weder seinen Benutzernamen oder seine E-Mail-Adresse ändern, noch ein Passwort
  anlegen, noch die Zwei-Faktor-Authentifizierung ausschalten, noch sein Konto löschen: Jedes Formular verlangte das
  aktuelle Passwort. Es bestätigt jetzt seine Identität, indem es erneut über den verknüpften Dienst geht; die
  Bestätigung gilt zehn Minuten, und die Anmeldung über diesen Dienst zählt als eine.
- **Der Export „Meine Daten“ funktioniert, und er enthält mehr.** Er schlug mit einem Fehler fehl: Das Mitglied lud
  eine Fehlerseite statt seiner Daten herunter. Das Archiv enthält jetzt auch das Profil (Name, Geburtsdatum, Ort,
  Signatur, Links), die verknüpften Konten, das Anmeldeprotokoll, die Benachrichtigungen und das, was die Module der
  Website unter seinem Namen aufbewahren, im Klartext und ohne irgendein Geheimnis, nicht einmal seine
  Sitzungskennungen. Forenthemen, Kommentare und Nachrichten in Unterhaltungen kamen erst mit der 1.2.43 hinein.
- **Das Löschen deines Kontos löscht, was die Seite versprach.** Der Benutzername wird an den Beiträgen anonymisiert und
  das Profil geleert; verknüpfte Konten, Anmeldeprotokoll und Benachrichtigungen werden gelöscht, und ein verknüpftes
  Discord-, GitHub- oder Google-Konto wird für eine neue Registrierung wieder frei. Die Nachricht „Dein Konto wurde
  gelöscht“ wird endlich angezeigt.
- **Auf einer Demo-Website lässt sich das geteilte Konto nicht mehr ändern**: Ein Besucher konnte sein Passwort ändern,
  seine Zwei-Faktor-Authentifizierung einschalten oder es löschen und so alle anderen bis zum Zurücksetzen aussperren.
- **„Connexion“ wird im Sinne von „sich anmelden“ übersetzt** (Log in, Anmelden, Iniciar sesión, Accedi, Entrar): Die
  fünf anderen Sprachen sagten „Netzwerkverbindung“. Die Schaltfläche der Discord-Verwaltung, die den Bot verknüpft,
  wird zu „Bot verbinden“.

## [1.2.27] — 2026-10-05

Der Discord-Bot geht auf **Version 0.2.4**: Bilder reisen zwischen dem Forum und Discord in beide Richtungen (siehe
sein eigenes Änderungsprotokoll). Version 0.2.3 funktioniert weiterhin.

### Hinzugefügt

- **Ein auf Discord geschicktes Bild erscheint im Forum** (mit Discord-Bot 0.2.4): Der Bot bewahrt es auf der Website
  auf, geprüft wie ein in den Editor eingefügtes Bild, statt eines bloßen Links zu Discord. Die API erhält dafür den
  Endpunkt `POST /api/v1/forum/images`.
- **Die Bestätigung von Registrierungen per E-Mail** (*Einstellungen → Registrierungsverwaltung*, Kästchen „Bestätigung
  per E-Mail“, standardmäßig aus): Das neue Mitglied erhält einen Link, zwei Tage gültig. Es kann sich nicht anmelden,
  bevor es ihn geöffnet hat; versucht es das, wird ein neuer Link geschickt. Diese von NeoFrag geerbte Funktion war
  unvollendet geblieben und hatte keine Einstellung in der Verwaltung.

### Behoben

- **Text mit Akzenten erscheint nicht mehr kodiert.** Ein Titel, ein Benutzername, eine Beschriftung oder der Name der
  Website, die „é“ oder „—“ enthielten, konnten als `&eacute;` oder `&mdash;` erscheinen: in den Unterhaltungen, im
  Shop, bei den Spenden, im Inhaltsverzeichnis eines Beitrags, in den RSS-Feeds, in der Verwaltung… Diese Texte werden
  jetzt alle gleich angezeigt, und zwei automatische Prüfungen verhindern, dass der Fehler zurückkehrt (eine liest den
  Code, die andere die Seiten der Website). Die Vorschläge für Erwähnungen und Suche, die Betreffzeilen der E-Mails, die
  von Suchmaschinen gelesenen Daten (Name der Website, Titel und Autor eines Beitrags) und die Webhooks erhalten
  ebenfalls Klartext.
- **Die Initiale eines Avatars** zeigte „&“ für einen Namen, der mit einem Buchstaben mit Akzent beginnt (Verwaltung,
  Seite einer Spendenkampagne).
- **Die Registrierung mit Discord, GitHub oder Google verlangt die Annahme der Regeln**, wenn die Website welche hat:
  Ein Bildschirm zeigt sie, und das Konto wird erst angelegt, wenn das Kästchen angekreuzt ist — wie beim
  Registrierungsformular, das diese Konten umgingen.
- **Das Menü führt nicht mehr zu einem fehlenden Modul**: Der Link eines nicht installierten oder ausgeschalteten Moduls
  (Forum, Galerie, News…) blieb im Menü — ein Klick, eine nicht gefundene Seite. Er verschwindet und kehrt zurück, wenn
  das Modul wieder eingeschaltet wird. Ein Link zu einer veröffentlichten eigenen Seite bleibt; einer zu einer nicht
  veröffentlichten Seite verschwindet ebenfalls.
- **Auf einer Website ohne das Modul Ereignisse** schreibt die Seite der Teams keine Warnung mehr ins Fehlerprotokoll.
- **Die Schaltfläche ☰ der Verwaltung funktioniert auf großen Bildschirmen**: Sie wurde dort angezeigt, ohne etwas zu
  tun; sie klappt jetzt das Seitenmenü ein und wieder auf und merkt sich die Wahl. Auf einem Telefon öffnet sie das Menü
  wie bisher.
- **Der Avatar des Autors eines Forenbeitrags ist größer**: 80 px (36 px auf einem Telefon) statt der 40 px, die jedes
  Theme ihm gab.
- **Ein Bild in einer Signatur oder in der Beschreibung eines Galeriealbums wird angezeigt**: Die Seite zeigte den
  HTML-Code (`<p><img …></p>`) statt des Bildes. Bereits gespeicherte Signaturen und Beschreibungen werden angezeigt,
  ohne neu eingegeben werden zu müssen.
- **Die Textversion der E-Mails ist lesbar**: Absätze klebten aneinander, Akzente waren kodiert, und auf einen Link
  folgte das nächste Wort — unbrauchbar in einem E-Mail-Programm, das nur Text zeigt.
- **Ein manipuliertes Formular lässt die Seite nicht mehr abstürzen**: Ein Kontrollkästchen, das in einer Form
  geschickt wird, die kein Browser je schickt, wird ignoriert, wie ein nicht angekreuztes Kästchen.
- **Auf einer Demo-Website bleibt das bei der Installation angelegte Administratorkonto beim Überfahren und im
  Moderationsverlauf verborgen**, wie auf seiner Seite: Die beim Überfahren gezeigte Mitgliederkarte gab seinen Namen,
  seine Gruppen und seine Besuchsdaten preis, und der Moderationsverlauf seinen Namen.

## [1.2.26] — 2026-10-04

Der Discord-Bot geht auf **Version 0.2.3**: Er schreibt nicht mehr bei jeder privaten Antwort eine Warnung von
discord.js (siehe sein eigenes Änderungsprotokoll). Version 0.2.2 funktioniert weiterhin.

### Hinzugefügt

- **Die Regeln und die Willkommensnachricht werden Sprache für Sprache übersetzt**: *Einstellungen →
  Registrierungsverwaltung* hat einen Reiter pro Sprache der Website, und jeder Besucher liest die Regeln — und erhält
  die Nachricht — in der Sprache der Seite. Eine Sprache, die noch keinen eigenen Text hat, zeigt den gemeinsamen Text:
  Eine Website, die nur einen hatte, behält ihn für alle ihre Sprachen, nichts ändert sich, bis er übersetzt ist.
- **Das Änderungsprotokoll gibt es auf Englisch** (`CHANGELOG.en.md`; die französische Version bleibt die Referenz).
- **Jedes Addon hat jetzt sein Vorschaubild, im selben Format (960 × 600)**: Die Module API und Discord hatten keines,
  die vier Themes des Katalogs behielten ein älteres, kleineres Bild, und drei Widgets des Kerns hatten keines in
  *Themes & Addons*. Einige zeigen noch ein leeres oder nicht konfiguriertes Addon. Ein neues Addon kann nicht mehr
  ohne sein eigenes veröffentlicht werden.

### Geändert

- **Die Beschreibungen des Katalogs sagen, was jedes Addon tut**: Dreißig davon passten in wenige Wörter oder stellten
  sich nur als „Gaming“ vor. Sie beschreiben jetzt, was das Addon wirklich tut und für wen es ist, in allen sechs
  Sprachen; fünf waren sogar falsch (das Widget Teams zeigt keine Mitglieder, das Widget Downloads zeigt die meist
  heruntergeladenen Dateien…).
- **Das Modul Ereignisse heißt „Ereignisse“**, nicht mehr „Gaming-Ereignisse“: Kein Titel im Katalog trägt mehr das Wort
  „Gaming“.
- **Das Modul Zahlungen verlangt das Modul Gamification**, das die gekauften Punkte und VIP-Tage gutschreibt: Ohne es
  wurde eine Zahlung eingezogen und nichts gutgeschrieben. Bei der Installation der Website fügt die Wahl von Zahlungen
  Gamification hinzu; aus dem Marketplace muss es zuerst installiert werden. Fehlt es später oder ist es deaktiviert,
  schließen die Verkäufe, und die Verwaltung sagt es; eine eingegangene Zahlung wird nicht als bearbeitet markiert, und
  Stripe schickt sie erneut.

### Behoben

- **Ein in den Texteditor eingefügtes (Strg+V) oder hineingezogenes Bild wird gespeichert** — Antwort im Forum,
  Kommentar, Verwaltungsseite, Team-Raum der privaten Nachrichten: Es erschien kaputt und verschwand beim
  Veröffentlichen. Es wird jetzt an die Website geschickt und bleibt in der Nachricht. Das ist angemeldeten Mitgliedern
  vorbehalten: ein Bild in JPEG, PNG, GIF oder WebP von höchstens 5 MB. Die Website speichert eine neue Kopie, die die
  versteckten Informationen eines Fotos entfernt (zum Beispiel, wo es aufgenommen wurde). Über 2.000 Pixel pro Seite
  wird das Bild verkleinert; über 8.192 Pixel pro Seite oder etwa 12,6 Millionen Pixel wird es abgelehnt. Ein
  animiertes GIF wird zu einem Standbild. Auf der Demo-Website bleibt das Hochladen von Bildern geschlossen.
- **Die Willkommensnachricht wird sauber angezeigt**: Im Rich-Text-Editor geschrieben, kam sie mit sichtbaren Tags in
  den Nachrichten an (`<h3>`, `<p>`…). Sie wird jetzt in Text verwandelt (Überschriften, Aufzählungen oder nummerierte
  Listen, anklickbar gemachte Links), und ihr Titel zeigt nicht mehr `&eacute;` statt eines Buchstabens mit Akzent.
- **Die Willkommensnachricht wird auch verschickt, wenn sich jemand mit Discord, GitHub oder Google registriert**: Nur
  das Registrierungsformular verschickte sie.
- **Der QR-Code der Zwei-Faktor-Authentifizierung wird angezeigt**: Der Aktivierungsbildschirm (*Kontosicherheit →
  2FA aktivieren*) zeigte den Code des Bildes in einem Textfeld statt des zu scannenden Bildes — die Formulare kannten
  diese Art von Feld nicht und behandelten sie als Text.
- **Die Logos des Widgets Partner führen zur Website des Partners** und zählen den Besuch: Sie führten seit 1.0.0 zu
  einer fehlenden Seite, und der Zähler „Besuche“ bewegte sich nicht mehr. Auch die Seite der Partner geht über den
  gezählten Besuch.
- **Die Schaltflächen „Bezahlen“ (Zahlungen) und „Kaufen“ (Shop) funktionieren**: Sie riefen eine fehlende Adresse auf.
- **Das Abonnieren des Newsletters aus dem Widget funktioniert**: Die eingegebene Adresse ging unterwegs verloren. Der
  Bestätigungslink in der E-Mail war relativ (in einem E-Mail-Programm nutzlos), wie der Abmeldelink der Kampagnen:
  Beide sind jetzt vollständige Adressen. Und wenn die E-Mail nicht verschickt werden kann, wird das Abonnement
  storniert und der Besucher informiert, statt „verschickt“ zu lesen.
- **Die Links in den E-Mails des Forums und der Nachrichten** (Erwähnung, Abonnement, Unterhaltung) sind vollständige
  Adressen: relativ führten sie nirgendwohin.
- **Umfragen folgen der Einstellung „Ergebnisse anzeigen“** (nach der Abstimmung, wenn geschlossen, nie) — auf ihrer
  Seite wie in ihrem Widget, das sie immer zeigte. Verwalter sehen sie immer, mit einem Hinweis.
- **Widgets folgen den Regeln ihres Moduls**: Das Widget Galerien zeigt keine Alben mehr im Entwurf, geplant, im
  Papierkorb oder auf Gruppen beschränkt; das Widget Forum keinen Auszug aus den VIP-Kategorien und keine leeren
  Zeilen für gelöschte Beiträge; das Widget Ereignisse und der Kalender kein geplantes Ereignis vor seiner Zeit; das
  Widget Rekrutierung folgt „Nicht verfügbare Angebote ausblenden“.
- **Ein im Editor eingefügter Link zu einer Seite der Website behält seine vollständige Adresse**: Der Editor kürzte
  ihn zu einem Pfad (`../../…`), der nur von der Bearbeitungsseite aus funktionierte — anderswo angezeigt, führte er
  nirgendwohin.
- **Die Roadmap** (`ROADMAP.md`) sagt, dass der Code seit dem 4. Oktober 2026 veröffentlicht ist.

### Sicherheit

- **Zwei alte technische Adressen der Nachrichten zeigten Nachrichten an, ohne sie zu schützen**: Eine Nachricht mit
  Code hätte dort im Browser ausgeführt werden können. Keine Seite benutzte sie mehr, und der Zugang zur Unterhaltung
  war bereits erforderlich; sie zeigen Nachrichten jetzt an wie die Unterhaltung selbst.
- **Die Schaltflächen Facebook und Twitter der Seite der Partner folgen keinem `javascript:`-Link mehr**, der in der
  Verwaltung eingegeben wurde: Solche Adressen werden gefiltert und maskiert, wie es die Website des Partners bereits
  war.
- **Die Schaltfläche „Kaufen“ des Shops und die Schaltfläche „Bezahlen“ prüfen jetzt, dass die Anfrage wirklich von der
  Website kommt**: Eine bösartige Seite kann ein angemeldetes Mitglied nicht mehr ohne sein Wissen einen Artikel kaufen
  lassen.
- **Ein Kauf kann nicht mehr doppelt gezählt werden**: Die Abbuchung der Punkte, der Lagerbestand und der Besitz werden
  in einer einzigen Transaktion geprüft und geschrieben — zwei gleichzeitige Käufe verkaufen den letzten Artikel nicht
  mehr doppelt und buchen ein Guthaben nicht mehr doppelt ab. Verdiente Punkte und VIP-Tage werden ebenfalls in einem
  Zug addiert: Zwei Gewinne im selben Augenblick überschreiben sich nicht mehr. Ein Artikel zu 0 Punkten lässt sich
  jetzt ohne Abbuchung erhalten (er wurde abgelehnt, „nicht genug Punkte“).
- **Newsletter-Anmeldungen sind in ihrer Häufigkeit begrenzt**: 5 Anfragen pro Stunde und IP-Adresse, 3 pro Tag und
  E-Mail-Adresse — genug, um den massenhaften Versand von Bestätigungs-E-Mails an gewählte Adressen zu stoppen.
- **Eine Umfrage mit verborgenen Ergebnissen zeigt ihre Gesamtzahl an Stimmen nicht mehr** in der Liste.

## [1.2.25] — 2026-10-04

Der Discord-Bot geht auf **Version 0.2.2**: Die Forenbeiträge, die er weitergibt, verlieren alle ihre HTML-Tags, auch
verschachtelte (siehe sein eigenes Änderungsprotokoll). Version 0.2.1 funktioniert weiterhin.

### Behoben

- **Das Fenster des Marketplace bietet auch die Widgets an, die kein Modul liefert** (Über, Spieleserver, Feed-Leser,
  Saisonaler Effekt, Steam-Gruppe, TeamSpeak 3 Server, Live-Status): Es zeigte nur Module und Themes, und diese sieben
  Widgets ließen sich nur über „Hinzufügen“ installieren, mit dem Archiv in der Hand. Ein Widget, das bereits ein Modul
  liefert (das des Forums zum Beispiel), erscheint dort nicht doppelt.
- **Der Leitfaden zur Verwaltung nennt die Schaltfläche, die eine neue Adresse der Website speichert**, bei ihrer
  Beschriftung, der Adresse gefolgt von *verwenden* — und nicht mehr bei einer falschen Adresse `https://…`, die ein
  Leser für einen Link hielt.

### Sicherheit

- **Die Prüf-Workflows des Repositorys erhalten nur noch ein Token mit Lesezugriff** (`permissions: contents: read`):
  Sie haben nichts zu schreiben, also könnte ein kompromittierter Schritt nichts ändern. Gemeldet von der Codeanalyse
  von GitHub, als die Repositorys für die Öffentlichkeit geöffnet wurden.
- **Das Widget Video akzeptiert für einen Eintrag seiner Wiedergabeliste nur eine Adresse `http(s)` oder einen Pfad der
  Website**: Eine Adresse `javascript:` oder `data:` wird beim Klick ignoriert. Der Player führte nichts aus, aber eine
  solche Adresse hatte dort nichts zu suchen. Von derselben Analyse gemeldet.

### Hinzugefügt

- **Drei weitere Tests im Workflow `installation.yml`**: `check-extensions` installiert jedes der Addons des
  veröffentlichten Marketplace auf einer Website, die nur den Kern hat, über das Fenster des Marketplace und über
  „Hinzufügen“, was er nicht anbietet; `check-prerequis-absents` entfernt nacheinander jede benötigte PHP-Erweiterung und
  prüft, dass der Assistent und der Kommandozeilen-Installer sagen, welche fehlt; das veröffentlichte Paket wird über den
  Assistenten auf einem simulierten Shared Hosting installiert (Apache ohne Funktionen, die ein Programm starten,
  `open_basedir`, 128 MB). Und die Links in allen Dokumenten werden geprüft (lychee).

## [1.2.24] — 2026-10-04

**NeoFrag Reborn öffnet sich auf GitHub.** Der Code des CMS ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)),
die Addons à la carte ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) und der Discord-Bot
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) werden dort mit der Geschichte ihrer
Versionen veröffentlicht — seit 1.0.0 für das CMS und die Addons, seit 0.1.0 für den Bot: der Code jeder Version, ihre
Hinweise und die Pakete ab 1.2.23.

### Geändert

- **Die angegebenen Datenbanken sind die getesteten**: Jede Version installiert sich und besteht ihre ganze Testsuite,
  mit PHP 8.2 und 8.5, auf MySQL 5.7, 8.0 und 8.4 und auf MariaDB 10.5, 10.6, 10.11, 11.4 und 11.8 — der
  Installationsleitfaden nennt diese Versionen, und nicht mehr „5.7+“ und „10.5+“.
- **Die Leitfäden sagen, wo man das Projekt findet**: das Installationspaket und das Archiv des Bots auf ihrer Seite
  der Versionen, die Addons à la carte und ihren Katalog auf der von `extensions`, den Code für alle, die beitragen
  wollen. Im Wiki führen Verweise auf ein Dokument des Repositorys (Bereitstellung, Architektur, Werkzeuge) zu diesem
  Dokument auf GitHub, statt nur seinen Titel zu behalten.

### Behoben

- **Die Einstellungen der Module öffnen sich wieder**: Das Fenster „Konfiguration“ der Module Forum, Rekrutierung,
  News, Galerie, Ereignisse, Kalender und Artikel antwortete bei jedem Öffnen „Fehler 500“, auf jeder Website, seit der
  1.2.0: Das Feld „Zahl“ lehnte eine Ganzzahl ab (seine Schrittweite und den aus der Datenbank gelesenen Wert), seit der
  Code auf strikte Typisierung umgestellt hat.
- **Teams: Die Option „Gespielte Matches anzeigen“ lässt sich wieder abwählen.** Das Speichern der Einstellungen
  schrieb das Wort „Array“ statt eines Ja oder Nein, was immer als „Ja“ gelesen wurde.
- **Die Einstellungen eines nie konfigurierten Authentifizierungsdienstes** (Google, Discord, GitHub) öffnen sich, ohne
  acht Warnungen ins Protokoll zu schreiben.
- **Das Dashboard und die Statistiken einer Website ohne Artikel, Bugtracker oder Forum** schreiben nicht mehr bei jedem
  Öffnen Warnungen ins Protokoll: Die Karten dieser Module erscheinen nur, wenn sie installiert sind, und keine fehlende
  Tabelle wird mehr abgefragt.
- **Der Installationsassistent liest die Konfiguration, die er gerade geschrieben hat, neu**: Bei einem Hoster, dessen
  PHP-Cache Dateien nicht erneut prüft, las ein zweiter Versuch des Schritts „Datenbank“ noch die alte
  `config/db.php`.
- **Eine aus git bereitgestellte und über den Web-Assistenten installierte Website funktioniert**: Der Assistent schrieb
  `config/neofrag.php` nicht (das Paket trägt sie, ein Klon hat nur ihre Vorlage), und jede Seite antwortete „Fehler
  500“, ohne eine einzige Zeile im Protokoll.
- **Das Beispiel `nginx.conf` startet**: Die Regel für TinyMCE, ohne Anführungszeichen, ließ nginx die ganze
  Konfiguration ablehnen („missing closing parenthesis“). Seine Umleitungen tragen nicht mehr den internen Parameter
  `request_url`.
- **Hochgeladene Dateien werden unter Apache mit PHP als Modul ausgeliefert**: Der Schutz von `upload/` verwendete eine
  in einer `.htaccess` verbotene Direktive, und dieser ganze Ordner — Avatare, Galerie, Mediathek — antwortete „Fehler
  500“.
- **Unter Caddy werden hochgeladene Dateien, die keine Bilder sind** (ein PDF der Mediathek), ausgeliefert: Das Beispiel
  lieferte nur Bilder aus.
- **`/index.php` führt zur Startseite** (dauerhafte Umleitung) statt zu einer nicht gefundenen Seite; und eine Adresse
  mit einer Endung, die keine Seite ausliefert (`/newsletter/.env`, das Bots versuchen), antwortet 404, ohne eine
  Anomalie ins Protokoll zu schreiben.
- **`tools/check-all.php` unter PHP 8.2**: Jede Prüfung zählte dort als Fehlschlag, auch wenn sie bestand — der
  Rückgabecode wurde einmal zu oft gelesen, was PHP erst ab 8.3 erlaubt. Für alle, die unter PHP 8.2 beitragen.

### Sicherheit

- **Der Installationsassistent öffnet sich nicht mehr auf einer installierten Website, deren Datenbank nicht
  antwortet.** War die Sperre `install/db.txt` verschwunden, öffnete ein vorübergehender Ausfall der Datenbank den
  Assistenten wieder, dessen Schritt „Datenbank“ `config/db.php` neu schreibt: Ein Besucher hätte die Website dann auf
  seine eigene Datenbank umleiten können. Die Website zeigt jetzt „vorübergehend nicht verfügbar“. Und der Schritt
  „Administrator“ weigert sich, ein Konto anzulegen, sobald die Website eines hat.
- **Apache führt jetzt nur noch die `index.php` im Stammverzeichnis aus**: Die Umschreiberegel ließ jede Datei
  `index.php` eines Unterordners direkt laufen (mehr als achtzig interne Controller).

### Hinzugefügt

- **Vier weitere Prüfungen**: `check-assistant` durchläuft den Installationsassistenten von Anfang bis Ende, wie ein
  Besucher, für jedes Profil (Bildschirme, Ablehnungen, angelegtes Konto, Anmeldung mit dem eingegebenen Passwort,
  stilles Protokoll); `check-reglages` öffnet den Einstellungsbildschirm jedes Addons, speichert ihn unverändert und
  öffnet ihn erneut; `check-serveur-web` testet Apache, nginx und Caddy mit den mitgelieferten Konfigurationen, indem es
  Sonden platziert (verbotene Ordner und Dateien, Skripte, die nicht laufen dürfen, Umschreibung, Sicherheits-Header);
  `check-nouveau-venu` folgt der README und dem Leitfaden für Mitwirkende wortwörtlich auf einer sauberen Maschine. Die
  Workflows `installation.yml` und `nouveau-venu.yml` führen sie jede Woche erneut aus.
- **Die Befehle der README funktionieren auf einer frischen Maschine**: Ihr Block „Entwickeln“ endete mit einer
  Testsuite, die mangels Testdatenbank fehlschlug; er verweist jetzt auf den Leitfaden für Mitwirkende, der diese
  Datenbank anlegt (`php tools/prepare-test-db.php`).

## [1.2.23] — 2026-10-04

Der Discord-Bot geht auf **Version 0.2.1**: An seiner Funktionsweise ändert sich nichts — er wird ein eigenständiges
Projekt, mit seiner Lizenz, seiner NOTICE und seinem eigenen Änderungsprotokoll. Ihn zu installieren ist nur für die
nützlich, die diese Dateien wollen; 0.2.0 funktioniert weiterhin.

### Geändert

- **Die Sitemap führt keinen leeren Bereich mehr**: News, Rekrutierung, eine FAQ, Teams… ohne etwas zu zeigen werden
  Suchmaschinen nicht mehr angeboten, die dort nur eine Seite „noch nichts“ fänden. Der Bereich kehrt mit seinem ersten
  Inhalt von selbst zurück. Kontakt, das Gästebuch, der Newsletter, das Webradio und die Spenden bleiben aufgeführt:
  Sie haben immer etwas zu bieten.
- **Sicherungen stapeln sich nicht mehr**: Jedes Update macht eine vollständige (16 MB und mehr), und nichts entfernte
  sie. Die Website behält immer die fünf neuesten und entfernt die anderen nach dreißig Tagen; eine von Hand in
  `backups/` abgelegte Datei wird nie angetastet.
- **Die Installation prüft alles, was das CMS braucht.** Der Assistent prüfte weder `openssl` (ohne es schlägt das
  Speichern eines Geheimnisses — E-Mail-Server, Captcha, Zwei-Faktor-Authentifizierung — mit einem Fehler fehl), noch
  `fileinfo` (ohne es wird jedes Hochladen einer Datei abgelehnt), noch `iconv` (der QR-Code der
  Zwei-Faktor-Authentifizierung), noch das Hashing von Passwörtern mit Argon2, ohne das das Anlegen des
  Administratorkontos fehlschlug. Er prüft sie jetzt und weigert sich weiterzumachen, wenn eines fehlt;
  `install/cli.php` verlangt genau dieselbe Liste, PHP-Version eingeschlossen, statt zweier Erweiterungen. Das
  *Monitoring* und das Dashboard der Verwaltung zeigen dieselbe Liste. PHP 8.2 oder neuer ist erforderlich; das Produkt
  ist bis PHP 8.5 getestet.
- **nginx und Caddy**: Das Paket liefert endlich die Konfigurationsbeispiele, die die Leitfäden versprachen
  (`nginx.conf`, `Caddyfile`), generisch, und die dieselben sensiblen Ordner und Dateien sperren wie die `.htaccess` von
  Apache — das alte Beispiel für nginx ließ `install/`, `tools/`, `tests/` und `docs/` erreichbar, und der Auszug im
  Bereitstellungsleitfaden schützte nur drei von acht Ordnern.
- **Jedes Addon sagt, wer es geschrieben hat.** Die Addons des ursprünglichen NeoFrag behalten ihre Autoren, Michaël
  BILCOT und Jérémy VALENTIN — die Anmelde-Konnektoren und die Sprachen, die nichts signierten, nennen sie jetzt, und die
  ursprünglichen englischen Übersetzungen nennen wieder ihre Übersetzer, FoxLey und eResnova. Portierungen nennen ihren
  Autor: die Uhr (ArkaNiX), das Theme Extend (Chewbaka), der Dateimanager (HiddenBlob, HiddenCMS) und die Spenden
  (HiddenBlob, nach majiid). Der Rest ist mit „NeoFrag Reborn“ signiert. Die Themes Nebula, Blockcraft, Forge und
  Granite gehen zur LGPL über, wie das Produkt; Extend behält die Lizenz seines Autors (CC BY-NC-SA). Die Lizenz jedes
  LGPL-Addons verweist auf den offiziellen Text der LGPL.
- **HSTS**: Der von der `.htaccess` gesetzte Header erstreckt sich nicht mehr auf Subdomains und erklärt die Website
  nicht mehr für die Preload-Liste der Browser geeignet (`includeSubDomains` und `preload` entfernt): Standardmäßig
  gesetzt, banden sie alle Subdomains dessen, der das CMS installiert hatte, für ein Jahr.

### Behoben

- **Eine aus dem Paket installierte Website zeichnete keine Fehler auf.** Das Paket enthielt den Ordner `logs/` nicht,
  und nichts legte ihn an: PHP schrieb seine Fehler ins Protokoll des Servers, *Monitoring → Fehlerprotokoll* blieb leer
  und nannte den Ordner „nicht beschreibbar“. Das Paket liefert jetzt `logs/`, `cache/` und `backups/` mit ihrem Schutz,
  und eine Website ohne `logs/` legt ihn selbst neu an: Ein Update genügt, um eine bereits installierte Website zu
  reparieren.
- **Die Dokumentation im Wiki hat keine toten Links mehr.** Drei Verweise ihrer Seiten auf Dokumente, die die Website
  nicht hat (das Handbuch der Werkzeuge, ein Teil des Bereitstellungsleitfadens), führten zu einer Fehlerseite; sie
  behalten jetzt nur ihren Text. Der Leitfaden zum Marketplace erwähnt keine Addons „von Drittanbietern“ mehr, die es
  nicht gibt, noch Abläufe, die dem Server des Projekts vorbehalten sind.
- **Einstellungen → Suchmaschinen** weigerte sich zu speichern, sobald eine Beschreibung, ein Slogan oder ein Titel für
  Suchmaschinen viele Buchstaben mit Akzent enthielt: Jeder zählte als mehrere Zeichen, und eine portugiesische
  Beschreibung von 139 Zeichen überschritt die Grenze von 160. Ein als ganzes Tag eingefügter Bestätigungscode von
  Google oder Bing wurde ebenso abgelehnt. Die Weiterleitungen und die Suchmaschineneinstellungen eines Inhalts folgen
  derselben Regel.
- **Der SEO-Bericht** erkennt eine Property der Google Search Console, die über das DNS der Domain bestätigt wurde
  (statt sie „nicht angegeben“ zu nennen), und rät, die Sitemap einzureichen, die alle Sprachen vereint,
  `/sitemap.xml`, statt der nur für die angezeigte Sprache. Wenn Google bestätigt ist, nennt er Bing nicht mehr „nicht
  angegeben“: Die Bing Webmaster Tools können eine Website aus der Google Search Console importieren, ohne etwas auf der
  Website abzulegen, daher kann der Bericht es nicht sehen.
- **`humans.txt`, `robots.txt` und der IndexNow-Schlüssel**: Eine fehlende Adresse — eine leere `humans.txt`, ein
  unbekannter Schlüssel — antwortet ein schlichtes „nicht gefunden“, ohne für jeden Bot, der sie anfragt, einen Fehler
  ins Protokoll der Website zu schreiben.

---

## [1.2.22] — 2026-10-03

### Hinzugefügt

- **Suchmaschineneinstellungen pro Inhalt**: Eine Schaltfläche „Suchmaschinen“ in der Bearbeitungskarte einer News,
  eines Blogbeitrags, einer Seite oder einer Wiki-Seite gibt für jede Sprache den Titel und die Beschreibung, die
  Suchmaschinen und Teilen-Vorschauen zeigen. Leer gelassen, bleiben sie automatisch.
- **Einstellungen → Suchmaschinen → SEO-Bericht**: was eine Suchmaschine sieht, auf der Website gemessen — die Seiten
  der Sitemap pro Modul, die Texte jeder Sprache, das Bild zum Teilen, Google und Bing, `robots.txt`, die Wartung —, mit
  dem Link zu dem, was jeden Punkt behebt.
- **Weiterleitungen**: Eine alte Adresse führt zur neuen (301), statt „Seite nicht gefunden“ zu antworten, mit der Zahl
  der Besuche, die sie noch erhält. Eine umbenannte Seite oder Wiki-Seite hinterlässt ihre eigene von selbst; man kann
  weitere von Hand hinzufügen, zum Beispiel für die Adresse einer früheren Website.
- **Suchmaschinen benachrichtigen (IndexNow)**: Unter Einstellungen → Suchmaschinen eingeschaltet, meldet die Website
  binnen Minuten jede Seite, die erscheint, sich ändert oder verschwindet, an Bing, Yandex, Seznam, Naver, Yep und
  Amazon. Google nimmt nicht teil: Für Google bleibt die Sitemap der Weg. Die Seiten jedes in der Sitemap aufgeführten
  Moduls werden gemeldet, ohne etwas hinzuzufügen; die geplante Aufgabe der Website ist erforderlich.

### Behoben

- **Moderation**: In der Verwaltung konnte ein Moderator ohne das Recht „private Unterhaltungen“ die Meldung einer
  privaten Nachricht öffnen, und dieser Zugriff wurde nicht im Audit-Protokoll verzeichnet. Wer eine private Nachricht
  meldet, sieht jetzt endlich die für ihn bestimmte Warnung.
- **Nachrichten**: Die aus der Verwaltung an den Team-Raum geschickte Nachricht antwortete immer „Kein Team-Raum
  konfiguriert“.
- Keine PHP-Warnungen mehr im Protokoll, wenn die Meldung, die Nachricht oder das betroffene Mitglied gelöscht worden
  war.

---

## [1.2.21] — 2026-10-03

### Hinzugefügt

- **Einstellungen → Suchmaschinen**: für jede Sprache der Slogan des Titels der Startseite und die Beschreibung, die
  Suchmaschinen zeigen; ein Bild zum Teilen (1200 × 630) für die Vorschauen auf Discord, X oder Facebook; die
  Bestätigungscodes für die Google Search Console und die Bing Webmaster Tools.
- **Eine Sitemap pro Sprache**, im Stammverzeichnis vereint (`/sitemap.xml`): Jedes Modul führt dort seine eigenen
  öffentlichen Seiten auf — den Blog, das Forum und seine Themen, das Wiki, die Galerie, den Kalender, die Ereignisse… —,
  nur die, die ein Besucher lesen kann und die in der Sprache der Sitemap existieren.
- Auf der Startseite **strukturierte Daten**, die die Website, ihr Logo und ihre sozialen Netzwerke beschreiben.

### Geändert

- **Die Profile der Mitglieder und das Mitgliederverzeichnis** werden Suchmaschinen nicht mehr angeboten, ebenso wenig
  die Suchergebnisse: wenig Inhalt, und ein Mitglied soll nicht bei Google landen, ohne es gewählt zu haben.

### Behoben

- **Die Sitemap** enthielt nur relative Adressen — von Suchmaschinen ignoriert —, in einer einzigen Sprache, ohne das
  Wiki und ohne das Forum; `robots.txt` kündigte sie mit einer relativen Adresse an.
- **Jede Seite des Blogs** gab `/articles/…`, eine Umleitung, als ihre kanonische Adresse an; die Startseite `/index`.
  Die Links zwischen den Sprachen sind vollständig, mit der Standardversion (`x-default`).
- **Der Titel der Startseite** wiederholt nicht mehr den Namen der Website („NeoFrag Reborn | NeoFrag Reborn“).
- **Google Analytics**: Die Einstellung akzeptierte nur das alte Format `UA-…`, das Google 2023 eingestellt hat; sie
  akzeptiert die aktuellen IDs, `G-…`.
- **Sprache des Browsers**: Ein Besucher, dessen Browser nur `de-DE` angibt, landet auf Deutsch und nicht in der
  Standardsprache der Website.
- **Die Sprachauswahl** und das Banner „Dieser Inhalt existiert nicht in …“ führten aus dem Blog zu seiner alten
  Adresse.

### Sicherheit

- **Die vollständigen Adressen** im Kopf der Seite (kanonisch, Sprachen, Teilen) werden aus der Adresse der Website
  gebaut und nicht mehr aus dem Header `Host` der Anfrage, den jeder fälschen kann.

## [1.2.20] — 2026-10-03

### Hinzugefügt

- **Ein Installationsprofil „Verein / Club“**: News, Forum, Galerien, Kalender, Spenden, Newsletter, Wiki und FAQ, ohne
  die E-Sport-Ausstattung. Es kommt zu *Vollständig*, *Gaming / E-Sport*, *Community* und *Nur Kern* hinzu, in den sechs
  Sprachen des Assistenten.

### Behoben

- **Der Export der Mitglieder im JSON-Format** (DSGVO, Artikel 15) schreibt jetzt seine Beschriftung „Export der Daten
  der Mitglieder“: Sie kam leer heraus.
- **Blog**: Auf der Seite eines Autors führte „Profil ansehen“ zu einer nicht gefundenen Seite.

### Sicherheit

- **Der Schnappschuss der Demo trägt kein Geheimnis mehr.** Die Datei, die die Demo zurücksetzt und mit dem Demo-Paket
  ausgeliefert wird, hätte die Zugangsdaten des E-Mail-Versands, den Schlüssel des automatischen Übersetzungsdienstes
  des ursprünglichen NeoFrag und die Schlüssel der Widgets Twitch und TeamSpeak von einer Website kopiert, auf der sie
  eingegeben worden waren; sie trug noch die Zeile, leer, des geheimen Schlüssels des Captchas. Kein Schlüssel war
  durchgesickert. Eine Prüfung gleicht die Liste jetzt mit dem Code ab, und die ausgelieferte Datei selbst.

### Dokumentation

- NeoFrag Reborn stellt sich als **das Open-Source-CMS für Communities, vom Gaming bis zu Vereinen** vor; der Leitfaden
  zu den Konzepten sagt, welches Profil man für die Website eines Vereins oder eines Clubs wählt.

## [1.2.19] — 2026-10-02

### Hinzugefügt

- **Ein modernes Captcha, ab der Installation aktiv: ALTCHA.** Es wird von der Website selbst gehostet — kein Konto,
  kein Schlüssel, kein Cookie, kein Drittdienst. Der Besucher sieht ein Kästchen, das sich selbst ankreuzt, während er
  das Formular ausfüllt: Sein Browser führt eine kleine Berechnung aus, die der Server prüft. Es schützt das
  Kontaktformular, die Registrierung und die Rekrutierung. Beim Update wechselt eine Website ohne reCAPTCHA-Schlüssel zu
  ALTCHA; eine Website, die welche hatte, behält reCAPTCHA. ALTCHA macht den Massenversand für einen Bot teuer; gegen
  einen entschlossenen Angreifer sind die folgenden Anbieter, die ihre eigenen Prüfungen hinzufügen, robuster.
- **Alternativ Cloudflare Turnstile, hCaptcha oder Google reCAPTCHA v2**, unter *Einstellungen → Sicherheit gegen
  Bots*, mit dem Link zur Konsole jedes Anbieters. Der geheime Schlüssel wird verschlüsselt und nie wieder angezeigt.
  Ohne seine zwei Schlüssel wird ein Anbieter durch ALTCHA ersetzt, statt das Formular offen zu lassen.
- Das Captcha und seine Meldungen gibt es in allen sechs Sprachen.

### Behoben

- **reCAPTCHA**: Der geheime Schlüssel wurde in der Prüfadresse übertragen; er wird jetzt im Körper der Anfrage
  übertragen, wie Google es verlangt. Der veraltete Link zu seiner Konsole ist ersetzt, und die weitergegebene Adresse
  des Besuchers folgt der Regel, die der Rest der Website verwendet und die einen als vertrauenswürdig angegebenen Proxy
  berücksichtigt.
- Wenn die Prüfung gegen Bots nicht abgeschlossen wurde, steht die Meldung ausgeschrieben unter dem Captcha, statt
  eines einsamen Symbols, dessen Tooltip auf einem Touchscreen nicht erscheint.

### Sicherheit

- **Das Captcha einmal zu bestehen befreit nicht mehr davon, es erneut zu bestehen.** Seit dem ursprünglichen NeoFrag
  befreite ein bestandenes Captcha bei allen folgenden Übermittlungen desselben Formulars davon, es erneut zu
  bestehen, bis zum Ende der Sitzung: Ein Bot, der eine einzige Aufgabe löste, konnte dann unbegrenzt übermitteln. Die
  Befreiung gilt jetzt nur noch für eine einzige erneute Übermittlung, nach einem anderen Fehler im Formular.
- Eine Lösung von ALTCHA kann nur einmal verwendet werden: Wird sie erneut vorgelegt, wird sie abgelehnt.
- Die Sicherheitsrichtlinie der Seiten erlaubt Google nicht mehr standardmäßig: nur die Adressen des gewählten
  Captcha-Anbieters, und keine mit ALTCHA.

## [1.2.18] — 2026-10-02

### Behoben

- **Das Umsortieren per Drag-and-Drop funktioniert wieder**: die Kategorien und Foren des Forums, die Teams und ihre
  Rollen, die Partner, die Mitgliedergruppen, die Sprachen und die Anmelde-Konnektoren, und die Reihen, Spalten und
  Widgets des Live-Editors. Die Position kam vom Browser als Text an, und die Verschiebung brach mit einem Fehler ab —
  bemerkt im Protokoll der Demo.
- **Auf der Demo öffnet der Live-Editor** wieder die Einstellungen eines Widgets, und jede Änderung dort erhält die
  Meldung „Aktion auf der Demo-Website deaktiviert.“ statt eines stillen Fehlers. Die Layouts lassen sich dort nicht
  ändern: Ein von einem Besucher geschriebenes HTML-Widget würde allen anderen angezeigt.

### Dokumentation

- **Der Leitfaden zur Installation per FTP** nennt das richtige Paket (`neofrag-reborn-public-<version>.zip`),
  beschreibt das Update per Schaltfläche und verlangt nicht mehr, ein im Paket fehlendes Werkzeug auszuführen: Die
  Migrationen werden beim ersten Besuch von selbst angewendet, Demo eingeschlossen.
- **Die Leitfäden für Entwickler** beschreiben, wie man eine eingegebene Adresse erst nach ihrer Prüfung in einen Link
  setzt (`nf_url_sure()`), die Sperre der Demo-Website, auf Seiten aufgeteilte Listen, Zähler und Daten; die Zahlen der
  README und der Leitfäden folgen dem Code (62 Module, 40 Widgets).

## [1.2.17] — 2026-10-02

### Sicherheit

- **Auf der Demo zeigte der Reiter Dateien des Monitorings** noch den echten Baum der Installation (Namen von Ordnern
  und Dateien, ohne ihren Inhalt): Er zeigt jetzt einen Beispielbaum, wie der Dateimanager.

## [1.2.16] — 2026-10-02

### Sicherheit

- **Ein bösartiger Link wird nicht mehr aus der Adresse einer Meldung ausgeführt.** Das meldende Mitglied liefert diese
  Adresse, die dann dem Moderator angezeigt wird: Eine Adresse „javascript:“ wurde bei ihm beim Klick ausgeführt.
  Derselbe Schutz gilt für die Links eines RSS-Feeds (Widget), einer Diashow, des Linkverzeichnisses, der Partner und
  jede Adresse, die die Website baut: Nur Web-, E-Mail-, Telefon- oder websiteinterne Adressen kommen durch.
- **Das Löschen einer Rolle, das Anlegen des Schlüssels des Discord-Bots, das Schließen einer Kleinanzeige oder das
  Sperren eines Forenthemas** geschahen über einen einfachen Link: Ein bösartiger Link genügte, um die Aktion für einen
  angemeldeten Administrator, Autor oder Moderator auszulösen. Diese Links tragen jetzt ein Sicherheitstoken.
- **Die Demo abgeriegelt.** Ihr geteiltes Konto (demo / demo) ist Administrator: Es konnte den Debug-Modus für alle
  Besucher einschalten, seine Dateien, das Fehlerprotokoll und phpinfo durchsuchen und lesen, den Schlüssel der
  geplanten Aufgabe sehen, die Einstellungen der Website ändern, API-Schlüssel anlegen, IP-Adressen oder Mitglieder
  dauerhaft sperren, die PayPal-Adresse der Spenden umleiten und Bilder löschen, die das Zurücksetzen nicht
  zurückbringen konnte. Auf einer Demo wird jede Aktion in Richtung eines gesperrten Bereichs abgelehnt, bevor sie
  seinen Bildschirm erreicht, sensible Bildschirme zeigen einen Hinweis, der Dateimanager zeigt nur einen
  Beispielbaum, keine Datei wird gelöscht, und das Zurücksetzen stellt auch Karten und Spielmodi, Rollen der Teams,
  Benachrichtigungen, Punkte und Berechtigungen wieder her.

### Behoben

- **Eine beim Hochladen abgelehnte Datei** (verbotene Endung, oder Demo) ließ die Seite abstürzen (Fehler 500), zum
  Beispiel beim Ändern des Avatars: Das Feld sagt jetzt warum.
- **Statistiken über einen maßlosen Zeitraum** (Jahrhunderte, stundenweise) erschöpften den Speicher des Servers:
  Zeitraum und Zahl der Punkte sind begrenzt.
- **Der Export der Mitglieder** schrieb eine Warnung von PHP 8.5 pro Zeile ins Protokoll.
- **Auf der Demo** verdeckte das grüne Banner die Benachrichtigungen, und das Notfallkonto erschien in der
  Spielerliste eines Teams und in der Aktivität des Dashboards.
- **Ein dünner weißer Strich lief im Dunkelmodus am rechten Rand des Texteditors entlang** (Kommentare, Nachrichten,
  Verwaltung): Der Editor malte seine Eingabefläche weiß, und dieses Weiß ragte um einen Bruchteil eines Pixels
  hinaus.

## [1.2.15] — 2026-10-02

### Geändert

- **Die Schnellsuche der Verwaltung (Strg+K), überarbeitet**: Bereiche in dezenten Kapitälchen, an den Symbolen
  ausgerichtet, statt am Rand klebender Überschriften; der Name des Bereichs wird nicht mehr am Ende jeder Zeile
  wiederholt; eine Lupe im Feld; der gefundene Teil hervorgehoben. Die Suche ignoriert Akzente (auf einer französischen
  Website findet „evenements“ „Événements gaming“) und durchsucht auch die Bereiche („gaming“ listet die sechs Module
  dieses Bereichs).
- **Das Foto des Mitglieds in der Seitenleiste der Verwaltung**, statt des ersten Buchstabens seines Benutzernamens, der
  für ein Konto ohne Foto bleibt.

### Behoben

- **Die Pfeiltasten verschoben in der Schnellsuche** eine unsichtbare Auswahl: Nur die Maus hob eine Zeile hervor.
- **„Was diese Version bringt“** im Update-Fenster öffnete das Änderungsprotokoll oben auf der Seite, bei der vorigen
  Version: Es führt jetzt zur angekündigten Version.
- **Der Kommentarkasten eines Blogbeitrags** verlor seinen Innenabstand und die Linie unter seinem Titel:
  „Kommentare“ und der Zähler berührten den Rand. Fünf Themes wendeten auf jeden verschachtelten Kasten eine Regel an,
  die für den Kopf transparenter Kästen gedacht war.

## [1.2.14] — 2026-10-02

### Geändert

- **Die Seite Monitoring, in Reitern**: eine stets sichtbare Zusammenfassung (Version und Update, PHP, Sicherungen,
  Diagnosewerkzeuge), dann *Übersicht*, *Sicherungen*, *Diagnose*, *Server und Sicherheit* und *Dateien*. Sie
  stapelte etwa zehn Karten in einer schmalen Spalte, über fast 2.700 Pixel; jeder Reiter passt jetzt auf einen
  Bildschirm. Die Gesundheit der Website wird nüchtern angegeben („Alles in Ordnung“, „Punkte zu prüfen“, „Fehler zu
  beheben“).
- **Die Aktionsschaltflächen der Verwaltung, überall gleich**: Bearbeiten, Zugriff und Sortieren in neutralem Grau;
  Löschen mit rotem Rand, mit einem Papierkorb. Sie wechselten von einer Seite zur nächsten — Himmelblau, Petrol,
  gefüllte Schaltflächen, Kreuze oder Papierkörbe. Die Gestaltungsregeln der Verwaltung sind im Leitfaden „Ein Modul
  erstellen“ beschrieben, und eine Prüfung setzt sie durch.
- **Statusabzeichen, überall sanft**: ein blasser Hintergrund und ein kräftigerer Text derselben Farbe, wie
  „Veröffentlicht“. „Aktiv“ stand weiß auf gefülltem Grün, „Built-in“ in Schwarz. Keine gefüllten farbigen
  Schaltflächen mehr in den Zeilen der Listen (Vorschau, Duplizieren, Wiederherstellen…).
- **Die Schaltfläche zum Anlegen, überall an derselben Stelle**: oben auf der Karte der Liste, die sie füllt. Acht
  Seiten setzten sie in die obere Leiste, ans Ende der Liste oder über die Karte (Werbung, Shop, Spenden, Zahlungen,
  Diashow, Teams, Spiele, Sicherungen des Monitorings).
- **Themes & Addons, kompakt**: eine Suche, die Zahl der Erweiterungen jedes Typs, kombinierbare Filter nach Typ und
  Status („inaktive Module“) und eine dichtere Listenansicht, standardmäßig gezeigt, neben dem Raster. Die Seite öffnet
  sich auf den Modulen; sie reihte die rund 120 Erweiterungen als große Karten auf, über mehr als 10.000 Pixel.
- **E-Mail-Vorlagen, in einer einzigen Tabelle**: die Vorlagen nach Familie gruppiert (Konten, Forum, Nachrichten,
  Moderation, Newsletter), mit Betreff, Sprachen und Status auf einer Zeile. Die Seite stapelte eine Karte pro Vorlage,
  über 2.000 Pixel; jetzt ist es die Hälfte.
- **Discord, in Reitern**: *Übersicht*, *Funktionen*, *Einrichtung des Servers*, *Kanäle und Foren*, *Gruppen und
  Rollen*, *Befristete Rollen* — man wechselt von einem zum anderen, ohne zur Hauptseite zurückzukehren. Das Protokoll
  des Bots scrollt in seiner Karte, statt die Seite zu verlängern.
- **Statistiken, leichter zu lesen**: Daten und Intervall passen auf eine Zeile. Die Grafik wiederholt in ihrer
  Legende nicht mehr die Kontrollkästchen, die bereits die Farbe jeder Reihe tragen. Ihre Kurven fallen nicht mehr unter
  null, und ihre Achse endet an den Grenzen des gewählten Zeitraums. Ihre Daten folgen der Sprache der Seite statt der
  des Browsers (auf einer französischen Seite „2 oct. 2025“, nicht „Oct 2, 2025“). Ab drei Reihen nur noch Linien statt
  überlappender Flächen.
- **Berechtigungen → Matrixansicht**: Module nach Bereich gruppiert, wie in der Seitenleiste, in Spalten. Sie waren als
  rund vierzig große Kacheln aufgereiht, ohne Ordnung.
- **Das Audit-Protokoll, in Seiten zu 50**: Es reihte 200 Zeilen in einem Block auf, über mehr als 5.000 Pixel.
- **Benutzer: eine Suche** nach Benutzername oder E-Mail, über der Mitgliederliste.
- **Der Dateimanager sagt, was seine Schaltflächen tun**: „Dateien hinzufügen“, „Ordner erstellen“, „Verschieben“,
  „Löschen“, statt vier farbiger Pillen nur mit Symbol. Seine Dialoge haben ein neutrales „Abbrechen“ und eine benannte
  Bestätigungsschaltfläche.
- **„Speichern“ auf der Schaltfläche, die eine Änderung bestätigt**, überall. Zwanzig Formulare sagten „Bearbeiten“
  (Forum, News, Seiten, Ereignisse, Teams, Spiele, Galerien…).

### Behoben

- **Manche Listen der Verwaltung hörten bei ihrer ersten Seite auf**, ohne Link zum Rest: die Mitglieder und Kommentare
  jenseits der ersten 20, die Auszeichnungen und Rekrutierungen jenseits der ersten 10, sowie „Meine Abonnements“ des
  Forums auf Mitgliederseite. Der Zähler der Karte gab nur die gezeigte Seite an („20 Mitglieder“ für 25).
- **Auf Englisch und Deutsch eingegebene Daten.** Auf Englisch schrieb die Auswahl den 2. Oktober als „10/02/2026 g:05
  A“, füllte ein auf den 30/09/2026 datiertes Ereignis als „06/09/2028“ vor, und der 2. Oktober wurde als 10. Februar
  gespeichert. Auf Deutsch wurde ein als „02.10.2026“ angezeigtes Datum beim Speichern nicht zurückgelesen. Englische
  Kurzdaten werden jetzt Tag/Monat/Jahr mit 24-Stunden-Uhr geschrieben, wie in den anderen Sprachen („02/10/2026
  14:05“); ein Test prüft in allen sechs Sprachen, dass ein angezeigtes Datum zurückgelesen wird.
- **Keine falschen Warnungen „beschädigte Datei“ mehr vor einem Update.** Das Monitoring verglich die Dateien der
  Website mit der Prüfsummenliste der neuesten veröffentlichten Version, auch wenn die Website noch nicht auf diesem
  Stand war: Alles, was die neue Version ändert, erschien beschädigt (102 Warnungen auf einer Website kurz vor ihrem
  Wechsel auf 1.2.13). Die Prüfung wartet jetzt, bis die Website aktuell ist, und sagt es.
- **Statistiken**: Auf Deutsch lud die Grafik nie; die Schaltflächen „7 Tage“, „30 Tage“, „90 Tage“ und „1 Jahr“ taten
  nichts, in keiner Sprache.
- **Das Änderungsdatum einer Wiki-Seite** zeigte die Uhrzeit des letzten Besuchs: Der Aufrufzähler schrieb das
  Änderungsdatum neu. Derselbe Fehler bei den Kleinanzeigen.
- **Im Löschdialog des Dateimanagers** waren „Abbrechen“ und „Löschen“ zwei identische rote Papierkörbe. Die
  französischen Texte des Moduls hatten ihre Akzente verloren („Dossier cree avec succes“, „Element deplace“) und
  zählten in „élément(s)“.
- **Das Dashboard und das Audit-Protokoll** zeigen, was geschehen ist („Debug-Modus eingeschaltet“, „Einstellungen
  gespeichert“), und nicht mehr technische Kennungen (`monitoring.debogage.allume`).
- **Die Seite Kommentare der Verwaltung** zeigte Code statt des Datums jedes Kommentars.
- **Ein Titel mit Akzenten** zeigt seine Akzente im Kopf der Verwaltungskarten nicht mehr als HTML-Code.
- **Im Dunkelmodus** blieben die farbigen Texte der Verwaltung (Hinweise, Abzeichen, grüne Erfolgsmeldungen) dunkel auf
  dunklem Hintergrund, fast unlesbar.
- **Die Daten der Moderation** (Sanktionen, Meldungen, Verlauf eines Mitglieds), **des Papierkorbs, der Sicherungen, der
  Benachrichtigungen und des Protokolls des Discord-Bots** werden in der Sprache und Zeitzone des Besuchers angezeigt
  („21/09/2026 22:54“), und nicht mehr so, wie sie in der Datenbank stehen („2026-09-21 20:54:11“).
- **Auf Französisch war die Beschriftung „Connexions de membres“** (Anmeldungen von Mitgliedern) der Statistiken falsch
  geschrieben, „Connections de membres“.
- **Die Daten folgen der Sprache des Besuchers**: das Wiki, der Bugtracker, die Kleinanzeigen, das Gästebuch, der
  Newsletter, archivierte Unterhaltungen, der Dateimanager und das Widget der Ereignisse schrieben sie fest — so, wie sie
  in der Datenbank stehen („2026-09-20 22:54“), oder auf französische Art sogar auf Deutsch („02.10.2026“ erwartet).

## [1.2.13] — 2026-10-02

### Hinzugefügt

- **Das Fehlerprotokoll, in der Verwaltung** (*System → Monitoring → Fehlerprotokoll*). Kein FTP- oder Serverzugang
  mehr nötig, um zu wissen, was fehlgeschlagen ist: die Fehler der Website, gruppiert, vom neuesten zum ältesten, nach
  Schwere sortiert, nach Zeitraum oder Wort filterbar. Der Installationspfad, Passwörter, Schlüssel und Tokens werden
  auf dem Bildschirm maskiert, E-Mail- und IP-Adressen teilweise; die Datei lässt sich herunterladen und leeren (die
  alte wird daneben aufbewahrt). Das Monitoring meldet die Fehler der letzten 24 Stunden und einen Ordner `logs/`, in
  den die Website nicht mehr schreiben kann.
- **Die Diagnosewerkzeuge werden aus der Verwaltung eingeschaltet** (*Monitoring → Diagnose*), für eine Stunde, ohne
  `config/neofrag.php` per FTP zu bearbeiten:
  - **der Debug-Modus** — was er anzeigt, die Leiste unten auf der Seite und die Details der Fehler, wird jetzt nur
    angemeldeten Administratoren gezeigt; eingeschaltet wurde er allen Besuchern angezeigt, Abfragen und Besuchsdaten
    eingeschlossen;
  - **die Seitenablaufverfolgung**, die sich endlich in der Verwaltung lesen lässt: die zuletzt ausgelieferten Seiten,
    ihre Abfragen und ihre Dauer, mit maskierten sensiblen Werten;
  - **die Übersetzungsprüfung**, mit der Liste der fehlenden Texte pro Sprache und pro Erweiterung; die Flagge, die sie
    vor übersetzte Texte setzt, wird jetzt nur Administratoren gezeigt.
- **Die Verwaltung in neun Bereiche geordnet**: *Inhalt*, *Community*, *Aktivitäten*, *Gaming*, *Wissen*, *Medien*,
  *Verbreitung*, *Support*, *Monetarisierung* (und *System*). Die bisherigen sechs mischten den Kalender mit den Medien
  oder den Bugtracker mit der Community, und dreizehn Module landeten in „Andere Module“.
- **Das Menü „Navigation“ des Live-Editors** folgt denselben Bereichen, als aufklappbare Untermenüs, mit einer Suche; es
  reihte mehr als vierzig Seiten hintereinander auf.
- **Die Adresse der Website lässt sich aus der Verwaltung korrigieren** (*Monitoring → Adresse der Website*). Nach einem
  Domainwechsel führten die Links der E-Mails weiter zur alten, bis man `config/url.php` per FTP bearbeitete.
- **Eine Referenz für jeden Fehler.** Ein Besucher, der auf einen Fehler stößt, liest eine Referenz aus acht Zeichen;
  der Administrator sucht sie im Protokoll und landet auf der richtigen Zeile.
- **Uhrzeiten in der Zeitzone jedes Einzelnen.** Ein Mitglied wählt seine Zeitzone in seinem Profil; sonst verwendet
  die Website die seines Browsers, und sonst die der Website, eine neue Einstellung unter *Einstellungen → Allgemeine
  Einstellungen*. Die in Formularen eingegebenen Uhrzeiten werden in der Zeitzone dessen verstanden, der sie eingibt.
  Die Liste der Zeitzonen ist nach Region gruppiert, mit in der Sprache der Website benannten Städten.

### Behoben

- **Die Uhrzeiten stimmen wieder.** Die Website zeigte sie in Serverzeit — Weltzeit — für alle an: zwei Stunden zurück
  für einen Besucher in Frankreich im Sommer. Die Zeitzone des Mitgliederprofils galt ihrerseits nur auf den
  Profilseiten und verschob die Daten, die dieses Mitglied speicherte. Der Kalender, das Wiki, der Bugtracker,
  archivierte Unterhaltungen und der Dateimanager schrieben ihre Uhrzeiten ohne Umrechnung; die „Live“-Sendung des
  Webradios wurde in Serverzeit gelesen.
- **Der Kalender**: Beginn und Ende eines Ereignisses werden in einer Datumsauswahl gewählt, und nicht mehr in einem
  Textfeld im Format „YYYY-MM-DD HH:MM:SS“; ein Titel mit Akzenten zeigt seine Akzente nicht mehr als HTML-Code; die
  formatierte Beschreibung zeigt ihr Markup nicht mehr; der Kalenderexport erhält sauberen Text.
- **Listen mit Suche** sagen „Keine Ergebnisse“ in der Sprache der Website, nicht „No results found“.
- **Der Live-Editor** bietet jetzt an, die Seiten Forum, Galerien, Teams, Kontakt und Auszeichnungen zu gestalten: Sein
  Menü ließ sie aus, da es ihre Startseite nicht erkannte.
- **Eine abstürzende Seite sagt „Ein Fehler ist aufgetreten“ (500)**, mit ihrer Referenz, statt „Seite nicht gefunden“,
  was eine falsche Adresse vermuten ließ.
- **Keine weißen Seiten mehr.** Ein schwerer Fehler, oder ein außerhalb einer Seite ausgelöster Fehler, zeigt eine
  Fehlerseite in der Sprache des Besuchers. Ebenso eine unerreichbare Datenbank (503), statt einer rohen englischen
  Meldung, und der Ausfall wird endlich im Protokoll verzeichnet.
- **Eine fehlschlagende Aktion sagt es**: Ein Dialog, der sich nicht öffnet, eine abgelehnte Übermittlung, ein Server,
  der nicht mehr antwortet, zeigen eine Meldung, mit der Referenz des Fehlers — statt einer grau bleibenden
  Schaltfläche ohne ein Wort. Das Monitoring hält seine Sanduhr an, wenn es nicht aktualisieren kann.
- **Sicherung und Update sagen die Wahrheit.** Sie kündigten „Sicherung gespeichert“ oder „Aktualisierung erfolgreich
  abgeschlossen“ auch nach einem Fehlschlag an; sie kündigen jetzt den Erfolg nur an, wenn der Server ihn bestätigt, und
  sagen sonst, was fehlgeschlagen ist. Die Sicherung prüft auch, dass sie ihr Archiv wirklich geschrieben hat: In einem
  nicht beschreibbaren Ordner oder auf einer vollen Festplatte meldete sie Erfolg — und das Update, das sich darauf
  stützt, um zurückzurollen, hätte nichts wiederherzustellen gehabt.
- **Der Fehler eines Formularfelds wird unter dem Feld angezeigt**, und nicht mehr nur beim Überfahren eines kleinen
  Symbols.
- Zwei interne Fehler hinterlassen keine Spur mehr im Protokoll: ein ohne eines seiner Felder abgeschicktes Formular,
  und eine Datenbankabfrage, die nichts zu lesen zurückgibt — letztere ließ die Seite abstürzen.
- **Die Debug-Leiste wird wieder angezeigt**: Ein numerischer Wert ließ sie abstürzen, seit der Code seine Typen strikt
  prüft, und ihre Zeitleiste war falsch.
- **Die Ankündigung des Updates ist wieder sichtbar.** Sie war in der oberen Leiste zu einem farblosen „1.2.x“
  geschrumpft: Ein Aufräumen der Stile der Verwaltung hatte ihre eigenen weggefegt. Sie bekommt einen Kasten unter dem
  Logo zurück — „Update verfügbar · NeoFrag X.Y.Z“ — und ein lesbares Abzeichen in der oberen Leiste.
- **Das Update-Fenster** zeigte einen leeren Block und hinterließ eine Warnung im Protokoll: Es sagt jetzt, welche
  Version kommt, dass die Website vor dem Start gesichert wird und bei einem Fehlschlag in ihren vorherigen Zustand
  zurückkehrt, mit einem Link zu dem, was die Version bringt.

### Entfernt

- **`NEOFRAG_LOGS_DB`**, das nichts las: Neue Installationen schreiben es nicht mehr in `config/neofrag.php`. Eine
  bestehende Installation kann es behalten; es hat keine Wirkung.

## [1.2.12] — 2026-10-02

Der Discord-Bot geht auf **Version 0.2.0**. Zum Aktualisieren: Ersetze seinen Ordner durch den aus dem neuen Archiv,
behalte die Datei `.env`, führe `npm ci --omit=dev` aus und starte ihn dann neu. Lege anschließend unter
*Verwaltung → Discord* einen neuen Zugriffsschlüssel an — die Seite verlangt es — und ersetze den alten in `.env`: Ohne
ihn fehlen dem Bot die Berechtigungen des Bugtrackers. Die Einrichtung des Servers braucht außerdem die
Discord-Berechtigung „Kanäle verwalten“: Lade einen mit 1.2.11 eingeladenen Bot über die Schaltfläche „Bot einladen“
erneut ein, oder gib seiner Rolle diese Berechtigung in den Einstellungen des Servers.

### Hinzugefügt

- **Discord-Bot: Funktionen, die sich einzeln einschalten.** *Discord → Funktionen* listet, was der Bot kann (er gibt
  es selbst an). Jede wird aus der Verwaltung ein- und ausgeschaltet und eingestellt; die Änderung gilt binnen einer
  Minute, ohne den Bot neu zu starten. Die Schaltfläche **Neu synchronisieren** bringt alles wieder in Einklang, und
  beim Start holt das Forum nach, was während der Abwesenheit des Bots auf Discord geschrieben wurde.
- **Discord-Bot: Er holt die Website bei seiner Rückkehr ein.** Was auf der Website geschrieben wird, während der Bot
  aus ist, geht bei seinem Neustart zu Discord, innerhalb der 30 Tage an Ereignissen, die die Website aufbewahrt.
  Bisher fing er beim neuesten Ereignis wieder an, und was in der Zwischenzeit geschrieben worden war, erreichte
  Discord nie.
- **Discord-Bot: Einrichtung des Servers.** Aus der Verwaltung legt der Bot auf Discord eine Kategorie an, einen
  Forum-Kanal pro gewähltem Forum (mit seinen Präfixen als Tags) und eine Rolle pro gewählter Gruppe, richtet die
  Zuordnungen selbst ein und verwendet wieder, was schon existiert, statt es zu duplizieren. Zuerst kommt eine
  Vorschau, und die letzte Einrichtung lässt sich rückgängig machen.
- **Discord-Bot: `/forum`.** `/forum account link` gibt einen Einmal-Link, der dein Discord-Konto mit deinem Konto auf
  der Website verknüpft; du kannst auch wählen, das, was du von Discord aus veröffentlicht hattest, unter deinen Namen
  zu übernehmen. `/forum account unlink` hebt die Verknüpfung auf. Ohne verknüpftes Konto lässt `/forum visibility`
  wählen, wie man im Forum erscheint: dein Discord-Benutzername, ein anonymer Name oder ein Benutzername deiner Wahl,
  der alle sieben Tage geändert werden kann — bereits veröffentlichte Nachrichten folgen.
- **Discord-Bot: Präfixe des Forums ↔ Tags des Forum-Kanals**, in beide Richtungen: Ein Präfix an einem Thema setzt das
  Tag am Thread, und umgekehrt.
- **Discord-Bot: der Bugtracker in einem Forum-Kanal** (Funktion, die unter *Discord → Funktionen* einzuschalten ist).
  Jedes Ticket wird zu einem Thread: Sein Typ und sein Status sind seine Tags, die der Bot im Kanal anlegt und aktuell
  hält. Titel, Beschreibung und Priorität folgen; der Thread wird archiviert, wenn das Ticket geschlossen wird, und ein
  Duplikat verweist auf sein Original-Ticket. Die Kommentare gehen in beide Richtungen. Von Discord aus öffnen `/bug`
  und `/idee` ein Ticket über einen kleinen Dialog (Titel, Beschreibung), und ein von Hand im Kanal eröffneter Thread
  wird zu einem Ticket — für ein Mitglied, das sein Konto verknüpft hat. Noch offene Tickets erhalten ihren Thread,
  wenn der Kanal gewählt wird. Die Website ist maßgeblich: Ein auf Discord von Hand geändertes Tag wird auf das
  zurückgesetzt, was das Ticket sagt.
- **Discord-Bot: befristete Rollen** (einzuschaltende Funktion). Der Befehl `/role`, denen vorbehalten, die Rollen
  verwalten können, gibt einem Mitglied eine Rolle für eine festgelegte Zeit (eine Sanktion, ein Probezugang, eine
  Rolle für ein Ereignis), entfernt sie oder zeigt die laufenden. Der Bot entfernt sie bei Ablauf, auch nach einem
  Neustart, und gibt sie einem Mitglied zurück, das den Server verlässt und wieder beitritt, um ihr zu entgehen. Rollen,
  die mit einer Gruppe der Website verknüpft sind, von Discord verwaltete Rollen und Rollen auf oder über der höchsten
  Rolle des Bots oder dessen, der sie vergibt, werden abgelehnt. Die Seite *Discord → Befristete Rollen* listet sie
  auf, mit „Jetzt entfernen“.
- **API**: zwei neue Berechtigungen, „Den Bugtracker lesen“ (`bugtracker:read`) und „In den Bugtracker schreiben“
  (`bugtracker:write`). Sie lassen ein Programm Tickets lesen (`GET bugtracker/tickets`, nur die offenen mit `open=1`)
  und ihre Kommentare, ein Ticket im Namen eines Mitglieds öffnen, es im Namen eines Mitglieds oder eines
  Discord-Kontos kommentieren und diese Kommentare bearbeiten oder löschen. Das Präfix eines Forenthemas lässt sich
  ebenfalls über die API ändern (`PATCH forum/topics/{id}`). Der Bot erhält die Endpunkte `discord/*` seiner neuen
  Funktionen: befristete Rollen (`discord/timed-roles`), Einrichtung des Servers, Verknüpfung von Konten.

### Sicherheit

- **Ein über die API geschriebener Titel kann keinen Code mehr in die Seiten der Website einschleusen.** Der Titel
  eines über die API angelegten Themas — zum Beispiel der Name eines vom Bot kopierten Discord-Threads — wurde
  unverändert gespeichert, während das Forum seine Titel anzeigt, ohne sie neu zu kodieren: Ein HTML-Tag in diesem
  Titel wurde auf der Seite des Forums ausgeführt. Die API speichert ihre Texte jetzt so, wie die Website ihre eigenen
  speichert, und der Titel des Reiters wird immer kodiert.

### Behoben

- **Die Akzente im Bugtracker** wurden auf der Seite eines über das Formular geöffneten Tickets als `r&eacute;agit`
  angezeigt, und die Suche „Schon gemeldet?“ fand kein Wort mit Akzent. Die Texte, die die API zurückgibt —
  Benutzernamen, Titel, Beschreibungen, Kommentare —, sind ebenfalls Klartext: Der Discord-Bot kopierte sie kodiert. Die
  Vorschauen beim Teilen von Links (Discord eingeschlossen) zeigen ihre Akzente nicht mehr als HTML-Code.
- **Bugtracker**: Den Status „Duplikat“ ohne gültige Ticketnummer zu wählen behielt den alten Status, während
  „Ticket aktualisiert“ angekündigt wurde; die Meldung sagt jetzt, was nicht angewendet wurde.
- **Discord-Bot: Das Löschen der Kopie einer Nachricht der Website auf Discord löscht das Original nicht mehr.** Wenn
  der Bot diese Nachricht nicht mehr im Speicher hatte (nach einem Neustart), löschte das Löschen der Kopie einer
  Nachricht, die ein Mitglied mit verknüpftem Konto auf der Website geschrieben hatte, auf Discord auch die Nachricht auf
  der Website. In diesem Fall löscht der Bot auf der Website jetzt nur noch die Nachrichten, die von Discord kamen.
- **Das Wiki**: Auf der Startseite des Wikis erschien eine Seite der obersten Ebene ohne Unterseiten als leerer
  Bereich; sie zeigt jetzt ihre ersten Abschnitte (oder den Anfang ihres Textes) und einen Link, um sie zu lesen, und
  die Seitenleiste macht einen Link daraus.
- **Nicht gefundene und verbotene Seiten sind keine leeren Seiten mehr.** Auf allen öffentlichen Themes zeigten eine
  Adresse, die nirgendwohin führt (404), oder eine eingeschränkte Seite (403) nur den Kopf und die Fußzeile, ohne ein
  Wort — ein von NeoFrag geerbter Fehler. Sie sagen jetzt, was los ist, in allen sechs Sprachen, mit einer Schaltfläche
  zurück zur Startseite (zum Dashboard in der Verwaltung), und der Reiter des Browsers zeigt „Seite nicht gefunden“ oder
  „Unbefugter Zugriff“ statt des Modulnamens.

## [1.2.11] — 2026-10-01

### Hinzugefügt

- **Der Discord-Bot** (Version 0.1.0), der eine Website und ihren Discord-Server verbindet:
  - **Rollen und Benutzernamen** — ein Mitglied, das sein Discord-Konto verknüpft hat, erhält auf dem Server die mit
    seinen Gruppen verknüpften Rollen und verliert sie, wenn es diese Gruppen verlässt; ist die Option angekreuzt, ist
    sein Nickname auf dem Server sein Benutzername der Website. Die Website ist maßgeblich, und eine Rolle, mit der
    nichts verknüpft ist, wird nie angetastet;
  - **Forum ↔ Forum-Kanal von Discord**, in beide Richtungen — ein Thema der Website wird zu einem Thread, ein Thread zu
    einem Thema; Antworten, Bearbeitungen und Löschungen folgen. Von der Website kommende Nachrichten erscheinen unter
    Namen und Avatar ihres Autors; die von Discord unter dem Konto des verknüpften Mitglieds oder unter seinem
    Discord-Benutzernamen. Kanal für Kanal: alles synchronisieren, oder nur die Threads, die ein Moderator mit einer
    Reaktion markiert.

  Es ist ein eigenständiges Programm (Node.js 22.9 oder neuer, auf einer Maschine, die immer läuft), mit jeder Version
  in seinem eigenen Archiv ausgeliefert. Auf seiner Maschine behält es nur die Adresse der Website und einen
  Zugriffsschlüssel. Leitfaden: „Der Discord-Bot“ im Wiki.
- **Das Modul Discord** (optional, benötigt das Modul API): der Schlüssel des Bots, **verschlüsselt gespeichert**, der
  Server, der Schalter Ein / Pause, der Neustart, der Status des Bots (online, pausiert, offline) und sein **Protokoll
  in der Sprache des Administrators**, die Zuordnungen Kanal ↔ Forum und Gruppe ↔ Rolle, und der Link, der den Bot nur
  mit den Berechtigungen einlädt, die er braucht — nie „Administrator“.
- **API**: die Berechtigung `discord:bot` und die Endpunkte `discord/*`, die der Bot braucht.

## [1.2.10] — 2026-10-01

### Hinzugefügt

- **Eine REST-API** (Modul **API**, optional), für Programme, die ohne Browser mit der Website sprechen — allen voran
  der kommende Discord-Bot. Ein Administrator legt **Zugriffsschlüssel** mit nur den gewünschten Berechtigungen an; ein
  Schlüssel wird nur einmal angezeigt, die Website behält nur seinen Hash, und er lässt sich mit einem Klick widerrufen.
  Die Endpunkte `/api/v1/…` geben JSON zurück: den Status der Website, ein Mitglied (nach ID oder nach verknüpftem
  Discord-Konto), die Gruppen, das Forum (Baum, Themen, Nachrichten) und einen **Ereignisstrom** (neues Thema, neue
  Nachricht, Gruppenwechsel…). Sie **schreibt** auch ins Forum, im Namen eines Mitglieds oder eines nicht verknüpften
  Discord-Kontos — unter seinem Discord-Benutzernamen veröffentlicht, mit dem Discord-Logo markiert. Begrenzt auf 120
  Anfragen pro Minute und Schlüssel; die Fehler haben stabile Codes. Leitfaden: „Die REST-API“ im Wiki.
- **Meine verknüpften Konten** (Mitgliederbereich): seine verknüpften Discord-, GitHub- oder Google-Konten sehen, eines
  verknüpfen, eines lösen — außer es ist der einzige Weg, sich anzumelden.
- **Registrierung mit Discord** (oder GitHub, Google): Ein Konto, das niemand verknüpft hat, legt ein Mitglied an, von
  Anfang an verknüpft, wenn die Registrierung offen ist. Bisher erhielt es einen Fehler „unbekanntes Konto“.

### Behoben

- **Das Verknüpfen seines Discord-Kontos meldete einen im Konto eines anderen Mitglieds an**, wenn dieses Discord
  bereits mit letzterem verknüpft war: Die Sitzung wechselte zu ihm. Die Verknüpfung wird jetzt mit einer klaren
  Meldung abgelehnt, und dasselbe externe Konto kann nicht mehr mit zwei Mitgliedern verknüpft werden.

## [1.2.9] — 2026-10-01

### Behoben

- **Blog: Das Inhaltsverzeichnis eines Beitrags blieb in den anderen Sprachen mit „Sommaire“ betitelt** und hinterließ
  eine Warnung im Protokoll der Website. Eine Meldung der Mitgliederverwaltung („Mitgliedsgruppen aktualisiert“) hatte
  denselben Fehler.
- **Blog: Das Kategorie-Abzeichen dehnte sich über die ganze Breite der Karten**, statt eine kleine Beschriftung zu
  bleiben.

## [1.2.8] — 2026-10-01

### Hinzugefügt

- **Blog: Serien.** Ein Beitrag in mehreren Teilen: Jeder Teil zeigt die Liste der anderen und seinen Platz („Teil 2
  von 3“), und die Serie hat ihre eigene Seite. Serien werden in der Verwaltung des Blogs verwaltet, neben einer neuen
  Seite, die endlich die Kategorien auflistet.
- **Blog: eine Seite pro Autor** (seine Beiträge) und anklickbare **Monatsarchive**.
- **Blog: Das Teilen zeigt das Titelbild.** Ein in einem sozialen Netzwerk geteilter Beitrag zeigt sein Titelbild und
  nicht das Logo der Website; Suchmaschinen erhalten seine strukturierten Daten (Titel, Autor, Datum, Bild).
- **Sechs Widgets des Blogs**: die neuesten Beiträge mit ihrem Vorschaubild, die meistgelesenen, der hervorgehobene
  Beitrag, die Kategorien, die Tags und die Archive.

### Behoben

- **Forum: Die Liste der Foren verschwand für angemeldete Mitglieder**, sobald ein Forum Themen nur in seinen
  Unterforen hatte. Besucher waren nicht betroffen.
- **Die Abmeldung vom Newsletter meldete niemanden ab**: Die Abmeldeseite schlug mit einem Fehler fehl. Ebenso das
  Löschen eines Abonnenten in der Verwaltung.
- **Das Löschen deines Kontos** markierte das Konto als gelöscht und schlug dann mit einem Fehler fehl, bevor es seine
  Sitzungen schloss und seine Wiederherstellungscodes löschte.
- **Das Ausschalten der Zwei-Faktor-Authentifizierung** schlug mit einem Fehler fehl, sobald sie ausgeschaltet war,
  und ließ die alten Wiederherstellungscodes in der Datenbank.
- **Blog: Eine leere Kategorie ließ sich nicht löschen** (der Blog hielt sie immer für benutzt), und ihre Löschung war
  nicht gegen gefälschte Links geschützt. Sie verlangt jetzt eine Bestätigung.

## [1.2.7] — 2026-10-01

### Behoben

- **Forum: Ein Link-Forum zeigt sein eigenes Symbol.** Das in seinem Verwaltungsformular gewählte Symbol ersetzt jetzt
  den Globus, den es immer zeigte.

## [1.2.6] — 2026-10-01

### Hinzugefügt

- **Forum: die Antwort „Lösung“.** Der Autor eines Themas (oder ein Moderator) markiert die Antwort, die es löst. Sie
  wird unter der Frage hervorgehoben, im Verlauf gekennzeichnet, und das Thema erscheint in der Liste als „Gelöst“.
  Eine gelöschte oder verschobene Lösung lässt das Thema nicht gelöst zurück.
- **Forum: Präfixe für Themen** („Frage“, „Tutorial“, „Wichtig“…), vom Administrator mit ihrer Farbe angelegt und in
  jede aktive Sprache übersetzbar. Mitglieder wählen eines, wenn sie ihr Thema eröffnen, und die Liste eines Forums
  lässt sich danach filtern.
- **Forum: ein Symbol pro Forum**, in der Verwaltung gewählt.

### Behoben

- **Das Widget „Statistiken“ des Forums** zählte auch Kategorien, die der Besucher nicht lesen kann, und gelöschte
  Nachrichten: Es zählt jetzt nur, was der Besucher ansehen kann.
- **Die Demodaten des Forums** zählten die erste Nachricht als Antwort („4 Antworten“ in der Liste für ein Thema, das
  3 hat).

## [1.2.5] — 2026-10-01

### Hinzugefügt

- **Ein echter Blog.** Das Modul Artikel wird zum Blog, unter `/blog` (die alten Adressen `/articles/…` leiten
  dauerhaft um). Die Liste öffnet sich auf einen **hervorgehobenen** Beitrag, dann bebilderte Karten, Filter nach
  Kategorie und eine Seitenleiste (Suche, meistgelesen, Kategorien, Tags, Archive). Die Seite eines Beitrags hat ein
  Titelbild mit ihrem Titel, einen Lesefortschrittsbalken, ein Inhaltsverzeichnis, das dem gerade gelesenen Abschnitt
  folgt, den Autor, den vorherigen und den nächsten Beitrag, und „Als Nächstes lesen“. Der Administrator wählt das
  Layout der Liste und der Beitragsseite; Besucher wechseln von Raster zu Zeilen, und ihre Wahl wird gemerkt.
- **Bugtracker: Die Liste lässt sich nach Typ filtern** („Bug“, „Feature-Anfrage“, „Frage“, „Sonstige“), und „Neues
  Ticket“ behält den gewählten Typ. Ein Link zur gefilterten Liste (zum Beispiel `/bugtracker?type=bug`) zeigt die
  bereits gemeldeten Tickets dieses Typs, bevor man ein neues öffnet.
- **Bugtracker: „Schon gemeldet?“** Während man den Titel eines neuen Tickets schreibt, erscheinen darunter offene
  Tickets, die ihm ähneln, damit man das bestehende kommentiert, statt ein zweites zu öffnen. Ein Ticket lässt sich auch
  als **Duplikat** eines anderen markieren: Seine Seite verweist auf das Original.

## [1.2.4] — 2026-10-01

### Hinzugefügt

- **Forum: übersetzbare Kategorien und Foren.** Jedes behält seinen Standardtitel und kann in seinem
  Verwaltungsformular einen Titel pro aktiver Sprache erhalten (und, für ein Forum, eine Beschreibung). Besucher sehen
  die Übersetzung in ihrer Sprache, sonst den Standardtitel. Ein Link funktioniert mit dem Standardtitel wie mit jeder
  Übersetzung.

### Behoben

- **Eine bereits vorhandene Migration blockiert das Update nicht mehr.** Von Hand angewendet, ohne verzeichnet zu
  werden, schlug sie fehl („column already exists“) und hätte das ganze Update abgebrochen. Migrationen werden jetzt
  Anweisung für Anweisung angewendet, wobei nur übersprungen wird, was bereits erledigt ist.
- **Die mit dem Kern ausgelieferten Module erhalten endlich ihre Datenbankänderungen.** Nur das Update eines Addons über
  den Marketplace wendete sie an: Ein Modul wie das Forum oder der Kalender erhielt seinen neuen Code über das Update des
  Kerns, nie seine Datenbank. Sie werden jetzt zusammen mit denen des Kerns angewendet, per Schaltfläche wie nach einem
  Upload per FTP.
- **Artikel**: Die sechs Berechtigungen des Moduls (hinzufügen, bearbeiten, löschen und ihre Entsprechungen für die
  Kategorien) wurden nirgends geprüft. Jetzt werden sie es, Sammelaktionen eingeschlossen.
- **Geplante Inhalte erscheinen nicht mehr vor ihrem Datum**, ebenso wenig in den Papierkorb verschobene Inhalte: das
  Widget „Neueste Artikel“, die an Suchmaschinen geschickte Sitemap, die Suche in den News, die Aktivität eines
  Mitglieds und der Block „Neueste Artikel“ zeigten sie. Das Tags-Widget der News mischt die Sprachen nicht mehr, und das
  Kategorien-Widget zählt keine Entwürfe mehr.

## [1.2.3] — 2026-10-01

### Behoben

- **Das Update aktualisiert sich endlich selbst.** Sein Code — zusammen mit dem der Sicherung, der Wiederherstellung und
  des Marketplace — lebte im Ordner `install/`, den das Update nie neu schrieb: Eine Website behielt den vom Tag ihrer
  Installation, und seine Korrekturen erreichten sie nicht. Er lebt jetzt im Kern (`neofrag/installer.php`), mit jeder
  Version neu geschrieben, und das Monitoring prüft seine Integrität. Der Rest von `install/` folgt ebenfalls den
  Versionen; nur die Sperre `install/db.txt` bleibt der Website eigen. Auf einer bestehenden Website wird `install/` ab
  dem Update nach diesem aktualisiert.
- **Eine Website, die ihren Ordner `install/` nach der Installation löscht**, wie oft geraten wird, behält ihr Update
  und ihren Marketplace; ihre Meldungen bleiben dann auf Französisch.

## [1.2.2] — 2026-10-01

### Behoben

- **Teams, Spiele, Kategorien, Partner und Auszeichnungen verschwinden in den anderen Sprachen nicht mehr.** Die
  Verwaltung speichert einen Titel nur in der Sprache, in der man schreibt, und diese Objekte existierten nur in
  dieser: auf der Demo drei Teams auf Französisch, keines auf Englisch. Die Gruppe eines Teams, mit den Berechtigungen,
  die von ihr abhängen, verschwand mit ihm. Sie werden jetzt in der angefragten Sprache angezeigt, sonst auf
  Französisch, sonst in der, die existiert. Listen, die eine Zeile pro Übersetzung zurückgaben (Rekrutierungen,
  Matches, Aktivitätsstrom der Verwaltung), geben jetzt nur noch eine zurück. Die Inhaltslisten (News, Artikel, Seiten)
  bleiben in der angefragten Sprache.
- **Ein Update wendet endlich die Datenbankänderungen des Kerns an.** Bisher wendete nur die Installation sie an: Eine
  aktualisierte Website behielt ihre alte Datenbank unter neuem Code. Per Schaltfläche werden sie während des Updates
  angewendet, das abgebrochen wird, wenn sie fehlschlagen. Per FTP werden sie bei der ersten vom neuen Code
  ausgelieferten Seite angewendet. (Die eigenen Änderungen der Module folgten in der 1.2.4.)
- **Anmeldung mit Discord**: Die Adresse eines animierten Avatars (102 Zeichen) überschritt den Platz, den die
  Datenbank für sie vorsah (100), und die Anmeldung schlug fehl; dieser Platz wächst für alle verknüpften Konten auf
  255. Ein Discord-Mitglied ohne Avatar erhielt ein kaputtes Bild.

### Geändert

- **Der E-Mail-Versand geht auf PHPMailer 7** (7.1.1). Seine einzige inkompatible Änderung betrifft Klassen, die ihn
  erweitern, und NeoFrag hat keine. Mit einem echten Versand mit den Einstellungen einer Website getestet, über SMTP
  wie über `mail()`.

## [1.2.1] — 2026-10-01

### Sicherheit

- **Zwei Schwachstellen in der Markdown-Bibliothek behoben** (`league/commonmark` 2.10.3): ein Denial of Service über
  Tabellen, die präpariert sind, um den Server zu verlangsamen (hoher Schweregrad, GHSA-3q6v-r5mr-hxv8), und eine
  Umgehung des Filters, der nicht erlaubtes HTML entfernt (mittlerer Schweregrad, GHSA-97jj-33gv-5xf9). Am 21.
  September 2026 veröffentlicht. Die Bibliothek zur Bereinigung von HTML (`ezyang/htmlpurifier` 4.19.1) geht ebenfalls
  auf ihre neueste Version.

### Behoben

- **Das Monitoring meldet vorhandene Dateien nicht mehr als „fehlend“.** Seit der Veröffentlichung der 1.2.0 verglich
  es die Website mit der offiziellen Dateiliste der Version, schloss aber die Sass-Quellen (Stildateien vor der
  Kompilierung) nur auf Seiten der Website aus: vier fälschlich als fehlend gemeldete Dateien und ein Gesundheitsstatus
  „Das Schiff sinkt!“.

### Geändert

- **Ein Update des Kerns wird im Audit-Protokoll verzeichnet**: wer es gestartet hat, wann, von welcher Version zu
  welcher Version, wie viele Dateien geschrieben und wie viele alte Dateien entfernt wurden. Bisher wurde es ins
  Fehlerprotokoll geschrieben, wo es wie eine Anomalie aussah.

## [1.2.0] — 2026-09-23

### Hinzugefügt

- **Updates des Kerns mit einem einzigen Klick** (2026-09-23). Ab dieser Version meldet **Verwaltung → Monitoring** neue
  Versionen von NeoFrag Reborn und installiert sie: Sicherung der Website, Prüfung der Prüfsumme des Pakets, Anwenden
  des Pakets, Aktualisierung der Datenbank und Rückkehr zur Sicherung, wenn etwas fehlschlägt. Es ist die erste über
  diesen Weg veröffentlichte Version; sie wurde vor der Veröffentlichung von Anfang bis Ende auf einer frischen Website
  getestet. Eine Website auf 1.1.0 hat diese Schaltfläche noch nicht: Sie geht von Hand auf 1.2.0, indem sie ihre
  Dateien ersetzt. Für die Betreuer: `tools/build-release.php` erzeugt die drei Dateien, die zusammen zu veröffentlichen
  sind (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`); das Vorgehen steht in
  `docs/guide/marketplace.md`.

- **Ankündigungen auf Discord** (2026-09-23). Ein Webhook, der auf einen Discord-Kanal zeigt, erhält jetzt eine echte
  Nachricht, mit Titel, Link und Farbe: neue News, neuer Artikel, neues Mitglied, neuer Kommentar, neues Forenthema.
  Bisher lehnte Discord ab, was das Modul schickte, obwohl seine Beschreibung Discord versprach.

- **„Soundso ist live“** (2026-09-23). Wenn ein vom Widget „Live-Status“ verfolgter Twitch- oder YouTube-Kanal live
  geht, kündigen die Webhooks, die das Ereignis „Kanal ist live“ abonniert haben, es an, nur einmal pro Stream, mit dem
  Vorschaubild des Streams.

- **Die Demo spricht auch in ihren Inhalten Englisch** (2026-09-23): Ihre News, Artikel, Seiten, Kategorien und
  Galerien haben eine englische Version, die die Mehrsprachigkeit zeigt.

- **Das ganze Produkt spricht alle sechs Sprachen** (2026-09-23). Die Verwaltung, die Module, die Widgets, die Themes,
  die Einstellungen, die Schaltflächen, die Meldungen und die Skripte: Fast 800 Zeichenketten waren direkt auf
  Französisch geschrieben und blieben so auf einer englischen, deutschen, spanischen, italienischen oder
  portugiesischen Website. Sie gehen jetzt alle über die Übersetzungen, in allen sechs Sprachen. Insbesondere:

  - **Der Installationsassistent ist übersetzt**, mit einer Sprachauswahl; er folgt anfangs der Sprache des Browsers.
    Der Kommandozeilen-Installer nimmt `--lang=en`, oder die Sprache des Terminals.
  - **Der Marketplace** zeigt Namen und Beschreibung jedes Addons in der Sprache der Website.
  - **Die Länder** (Profil, Gegner) werden in der Sprache der Website angezeigt.
  - **Die mitgelieferten Rollen und E-Mail-Vorlagen** werden übersetzt angezeigt; ein vom Administrator geänderter
    Name bleibt so, wie der Administrator ihn geschrieben hat.
  - **Die Moderation** zeigt Status, Grund und Art einer Meldung und die Art einer Sanktion ausgeschrieben — sie zeigte
    die Codes (`pending`, `forum_message`, `ban_temp`).
  - **Eine in einer Sprache angelegte Gruppe verschwindet nicht mehr**, wenn die Website in einer anderen angezeigt wird
    — sie fiel aus der Liste, und ihre Berechtigungen mit ihr.
  - Das Theme der Verwaltung gab sich als auf Englisch geschrieben aus: Auf einer englischen Website wurden seine
    französischen Zeichenketten nie übersetzt. Das ist behoben, ebenso die Skripte der Module, die ihre Übersetzungen
    an der falschen Stelle suchten.
  - Die Bestätigung der Kontolöschung verlangt, das Wort in der Sprache der Website einzugeben („LÖSCHEN“ auf
    Deutsch), und nicht mehr überall „SUPPRIMER“.

  Eine Prüfung, `check-textes-en-dur`, lehnt jetzt in der CI jeden fest eingetragenen Text der Oberfläche ab.

- **Die Browser der Besucher können die Dateien der Website behalten, wenn du es wünschst** (2026-09-22). Das Manifest
  machte die Website bereits installierbar; die Website kann jetzt ihre Bilder, Stile und Skripte in den Browsern der
  Besucher behalten. **Standardmäßig aus**, einzuschalten unter **Verwaltung → Einstellungen → Allgemeine
  Einstellungen** („Installierbare App“).

  **Seiten dagegen werden nie behalten.** Eine im Speicher gehaltene Seite würde eine neue Bereitstellung überleben:
  Wer schon einmal da war, bekäme eine Tage alte Website ausgeliefert, ohne dass etwas darauf hinwiese. Eine Änderung
  bleibt daher sofort sichtbar.

  **Das Abwählen deinstalliert wirklich.** Das ist der heikle Teil dieser Technik: Eine Komponente dieser Art bleibt,
  einmal in einem Browser installiert, dort, auch wenn die Datei vom Server entfernt wird. Der Schalter entfernt daher
  nichts — er liefert eine Version aus, die sich beim nächsten Besuch selbst deinstalliert. Deshalb ist die Funktion
  standardmäßig aus, und deshalb schaltet ein Update des Kerns sie nie von selbst ein.

- **Die Website lässt sich als App installieren** (2026-09-21). `/manifest.webmanifest` wird von der Website selbst
  erzeugt — Name, Beschreibung, Themefarbe, Startadresse und Favicon kommen aus den Einstellungen —, statt als Datei
  abgelegt zu werden, die überall sonst falsch wäre.

- **Die sensiblen Ordner sind auch unter Apache geschlossen** (2026-09-21). Die `.htaccess`-Dateien des Pakets
  schützten **weniger** als die mitgelieferte Konfiguration für Caddy: Unter Apache blieb der Installer erreichbar,
  sobald die Website installiert war (nur seine eigene Prüfung hinderte ihn am Laufen), und auf einer aus dem
  git-Repository installierten Website waren auch `docs/`, `tools/` und `tests/` erreichbar — die Dokumentation ließ
  sich als reiner Text lesen. `cache/`, `config/`, `install/`, `tools/`, `tests/` und `docs/` haben jetzt jeweils ihren
  eigenen Schutz, das Stammverzeichnis sperrt auch `.neon`- und `.md`-Dateien, und `tools/check-htaccess.php` liest die
  Liste bei jedem Durchlauf neu, mit dem Grund für jede Zeile.

- **Eine unbekannte Adresse schreibt keinen Fehler mehr ins Protokoll der Website** (2026-09-21). Das Modul `pages`,
  das als letzte Instanz die Adressen erhält, die nichts sonst erkennt, schrieb für jede eine **Fehler**-Zeile — jeder
  tastende Bot fügte welche hinzu. Für Addon-Entwickler: Ein Controller kann eine Ablehnung jetzt als gewöhnlich
  erklären (`Module_Checker::refus_ordinaire()`); die Diagnose wird im Debug-Modus weiterhin auf dem Bildschirm gezeigt.
  Die Prüfung `check-journal` lehnt jetzt jede Zeile ab, die das Produkt selbst ins Fehlerprotokoll schreibt.

- **Zwei Prüfungen setzen die Regeln des Repositorys durch** (2026-09-21), für Mitwirkende. `tools/check-tools.php`
  prüft, dass jedes Werkzeug den gemeinsamen Regeln folgt: beschreibender Kopf, Rückgriff auf die gemeinsame
  Bibliothek statt kopierten Codes, Standardurteil und -rückgabecodes, reservierter Port, aktueller Katalog.
  `tools/check-docs.php` prüft die Dokumentation: richtige Zahlen, gültige Links und Anker, kein verwaistes Dokument,
  keine Erwähnung eines Werkzeugs, das es nicht mehr gibt, keine von einem Dokument ins andere kopierte Passage, lesbar
  bleibende Dokumente. Beide laufen in der kontinuierlichen Integration.

- **Acht neue Addons, alle optional** (2026-09-20). Ein Update installiert sie nicht: Auf einer bestehenden Website
  werden sie über **Verwaltung → Themes & Addons** hinzugefügt und auf demselben Weg entfernt. Eine neue Installation
  enthält sie mit dem Profil *Vollständig*, das standardmäßig angeboten wird, aber nicht mit den anderen Profilen.

  | Addon | Was es tut |
  |---|---|
  | **Saisonaler Effekt** (Widget) | Schnee, Konfetti oder Blätter über den ganzen Bildschirm, während eines Datumsbereichs. Die Daten werden `MM-DD` **ohne Jahr** geschrieben — die Saison kommt von selbst wieder — und können über Neujahr reichen (`von 12-15 bis 01-06`). Außerhalb der Saison erhält die Seite **gar nichts**: kein Bild, kein Skript. Die Systemeinstellung, die um reduzierte Bewegung bittet (`prefers-reduced-motion`), wird respektiert, und die Animation pausiert, wenn der Reiter in den Hintergrund geht. |
  | **Feed-Leser** (Widget) | Die neuesten Artikel eines externen RSS- oder Atom-Feeds. **Eine Seite wartet fast nie auf eine fremde Website**: Sie liest einen Cache und liefert sogar veraltete Inhalte aus, statt die Besucher warten zu lassen; die Aktualisierung geschieht in der geplanten Aufgabe. Nur die allererste Anzeige, mit leerem Cache, wartet auf den Feed, höchstens drei Sekunden. Ein ausgefallener Feed wird zehn Minuten beiseitegelegt, statt bei jedem Besuch erneut versucht zu werden. |
  | **Zitate** (Modul) | Eine nach Kategorien geordnete Sammlung, mit Autor und Quelle. |
  | **Rezepte** (Modul) | Zutaten und Schritte, eine pro Zeile eingegeben, Zeiten und Zahl der Portionen, Auszeichnung `schema.org/Recipe`, die Suchmaschinen lesen. |
  | **Glossar** (Modul) | Ein nach Buchstaben sortiertes Lexikon. „Éclaireur“ kommt unter E, „Æther“ unter A, „1v1“ unter #. Der Buchstabe wird berechnet, nie eingegeben. |
  | **Ortskarte** (Modul) | Orte auf einer OpenStreetMap-Karte, mit Adresse, Beschreibung und Link. Die Kartenbibliothek wird **von der Website gehostet**; nur die Kacheln der Hintergrundkarte kommen von OpenStreetMap. **Die Seite bleibt ohne JavaScript nützlich** — die Liste, die Adressen und die Links sind da. |
  | **Webradio** (Modul) | Ein Player für einen Stream, was gerade läuft, und das Wochenprogramm. Eine Sendung kann über Mitternacht reichen: „Samstag 22:00 → 02:00“ bleibt die Sendung vom Samstag. |
  | **Spielwiese** (Modul) | Nur für Mitglieder: Man probiert dort Formatierungen aus und sieht die **genaue** Darstellung der Website. Vor allem zeigt sie, **was die Website entfernt hat** — es ist die einzige Seite, die auf „Warum ist meine Tabelle verschwunden?“ antwortet. |

  Die drei Inhaltsmodule (Zitate, Rezepte, Glossar) teilen das Muster der FAQ: Kategorien, Suche, Filter, Sortierung und
  Entwürfe.

- **Inhalte bei der Installation wählen.** Der Web-Installer bietet jetzt zwischen den Voraussetzungen und der
  Datenbank ein **Profil der Website** an: *Vollständig*, *Gaming / E-Sport*, *Community* oder *Nur Kern*. Module lassen
  sich weiterhin einzeln abwählen, und die, die ein Modul benötigt, werden automatisch hinzugefügt (die Auszeichnungen
  brauchen die Teams), mit einer Meldung. Ohne JavaScript funktioniert das Formular weiterhin — der Server behält nur,
  was zum gewählten Profil gehört.

  **Keine Addon-Liste wird von Hand geschrieben**: Die Profile werden aus den Angaben `presets` jedes Addons gebaut. Das
  ist der Unterschied zum Versuch vom Juni 2026, der aufgegeben wurde, weil eine schlanke Installation mit einem
  Serverfehler (500) endete: Module des Kerns fragten die Tabellen optionaler Module ab, ohne zu prüfen, ob sie
  existierten. Diese Prüfungen gibt es jetzt, und sie werden bei jedem Durchlauf der CI kontrolliert.

- **Jedes mitgelieferte Modul, Widget und Theme gibt seine Rolle an**: In seinem `__info()` sagt es, ob es zum Kern
  gehört, zu welchen Installationsprofilen es gehört und welche Module es braucht. Die Zugehörigkeit zum Kern war bisher
  ein Nebeneffekt der Anwesenheit im Paket — so sind `emojis`, `files` und `webhooks` dort gelandet, ohne dass eine
  Entscheidung getroffen wurde.

- **Drei neue Schutzvorrichtungen der kontinuierlichen Integration**:

  | Prüfung | Was sie ablehnt |
  |---|---|
  | `check-addon-declarations.php` | ein Addon, das nichts angibt, eine nicht existierende Abhängigkeit, **ein Addon des Kerns, das von einem optionalen abhängt**, ein im Katalog veröffentlichtes nicht verteilbares Addon, eine Angabe, die `seed.sql` widerspricht |
  | `check-addon-coupling.php` | eine **fatale** Kopplung (Tabelle, Klasse), weder angegeben noch annotiert; ein harter Zyklus. Aus dem Code mit dem **PHP-Tokenizer** gelesen, nicht mit regulären Ausdrücken — also keine Fehlalarme bei Kommentaren |
  | `check-install-profiles.php` | ein Profil, das nicht startet. Es **installiert wirklich** auf einer Wegwerf-Datenbank, liefert die Website aus und schlägt beim kleinsten 5xx fehl — auch auf den Routen fehlender Module, die ein sauberes 404 zurückgeben müssen |

- **Ein Mitglied über die Suchleiste finden.** Die Suche der Website fand nur Inhalte — Forum, News, Seiten. Wer
  jemanden suchte, musste das Mitgliederverzeichnis öffnen und durchblättern, obwohl es auf einer Community-Website zu
  den am häufigsten gesuchten Dingen gehört. Die Mitglieder erscheinen jetzt in der globalen Suche **und** in den
  Sofortvorschlägen, nach Benutzername, Vorname oder Nachname.

  Dabei wird nichts Neues preisgegeben: Diese drei Felder werden bereits im öffentlichen Profil jedes Mitglieds
  angezeigt, und die Suche wendet genau denselben Filter an wie das Verzeichnis — ein gelöschtes Konto erscheint dort
  nicht.

- **Eine Serie von Terminen auf einmal verwalten.** Das Anlegen eines wiederkehrenden Termins erzeugte bereits alle
  seine Vorkommen mit einem Klick — sie zu überarbeiten hieß dann, sie einzeln zu öffnen.

  Jetzt hat ein Vorkommen einer Serie eine Schaltfläche **„Die ganze Serie löschen“**, deren Bestätigung angibt, wie
  viele Vorkommen verschwinden, und das Bearbeitungsformular bietet **„Auf alle Termine der Serie anwenden“** an. Titel,
  Typ, Beschreibungen, Ort, Bild und Veröffentlichungsstatus werden auf jedes kopiert; **die Daten nie** — sie sind es,
  die eine Sitzung von der nächsten unterscheiden.

- **Ein Update zurückrollen und eine Sicherung wiederherstellen.** Das CMS machte bereits eine vollständige Sicherung —
  Dateien und Datenbank — direkt vor jedem Update des Kerns. Es konnte sie nicht wieder verwenden: Das Sicherheitsnetz
  war da, aber niemand wusste, wie man hineinfällt.

  Jetzt wird die Website, wenn das Platzieren der Dateien oder eine Migration fehlschlägt, **automatisch in den Zustand
  zurückversetzt, in dem sie war**; die Meldung sagt sowohl, was fehlgeschlagen ist, als auch, ob das Zurückrollen
  gelungen ist. Jede Sicherung der Liste hat außerdem eine Schaltfläche **Wiederherstellen**, für die Fälle, in denen der
  Schaden nicht von einem Update kommt — ein Modul eines Drittanbieters, eine bereute Aktion. Die Bestätigung gibt an,
  was verloren geht.

  Was die Wiederherstellung **nicht** anrührt, mit Absicht: die Konfiguration der Website (veraltete Zugangsdaten zur
  Datenbank zurückzusetzen würde die Website von ihrer eigenen Datenbank abschneiden), die Protokolle (sie sind die
  Aufzeichnung des Vorfalls, der repariert wird) und der Cache (geleert, damit er neu aufgebaut wird, statt in den
  Zustand einer anderen Version zurückgesetzt zu werden).

  Nebenbei enthält die Sicherung jetzt `vendor/`, das das Update-Paket mitliefert und das sie nicht enthielt: Ohne es
  hätte ein Zurückrollen den alten Code über die neuen Abhängigkeiten gesetzt.

- **Auf einen Kommentar antworten.** Die im ursprünglichen Code deaktivierte Schaltfläche „Antworten“ funktioniert
  jetzt: Die Antwort wird eingerückt unter dem Kommentar angezeigt, auf den sie antwortet. Man kann nur auf einen nicht
  gelöschten Kommentar der obersten Ebene desselben Inhalts antworten (eine Ebene von Antworten); der Server prüft das.
- **Für Theme-Ersteller: Zonen beim Namen gerufen** (von HiddenCMS übernommene Idee). Eine Vorlage ruft eine Zone jetzt
  bei ihrem Namen — `$this->output->region('content')` — statt bei ihrer Nummer — `zone(2)`. Das Theme gibt die
  Zuordnung zwischen Namen und Zonen in seinem `__info()` an (`regions`). Der alte Aufruf `zone()` funktioniert
  weiterhin, und ein Theme ohne diese Angabe bleibt unverändert. Die fünf öffentlichen Themes (Nebula, Forge, Granite,
  Blockcraft, Extend) verwenden die Namen.
- **Installieren über die Kommandozeile** (`install/cli.php`, von HiddenCMS übernommene Idee): eine Alternative zum
  Web-Assistenten, praktisch, um einen Server reproduzierbar oder automatisiert zu installieren. Seine Optionen decken
  die Datenbank ab (`--db-*`, `--create-db`), das Administratorkonto (`--admin-*`), Namen und Adresse der Website
  (`--site-name`, `--site-url`), die Sprache (`--lang`), Demodaten (`--demo`) und die Ausführung (`--force`, `--yes`,
  `--dry-run`, `--no-lock`). Ohne Optionen stellt er die Fragen nacheinander und verbirgt das Passwort beim Eintippen;
  `--admin-pass-env` liest das Passwort aus einer Umgebungsvariable, damit es nicht in der Prozessliste erscheint. Er
  verwendet denselben Code wie der Web-Assistent und installiert jedes mitgelieferte Addon, wie das Profil
  *Vollständig*.

- **Ein Starter für die ganze Batterie**: `php tools/check-all.php`. Die Prüfungen hatten keinen gemeinsamen
  Einstiegspunkt — die CI ruft sie einzeln auf, und lokal schrieb jeder seine eigene Schleife von Hand, nie dieselbe.
  Der Starter entdeckt die vorhandenen Prüfungen (eine neue Prüfung kommt hinzu, ohne dass jemand ihn anfasst), führt
  zuerst `composer audit` aus, begrenzt die Dauer jeder Prüfung und gibt am Ende an, **was er nicht ausgeführt hat**:
  die Browser-Tests (`--navigateur`) und die Prüfungen mit einem ausdrücklichen Ziel, darunter `check-restauration`, das
  die Website absichtlich beschädigt.

- **Jedes Addon zeigt, wie es aussieht** (2026-09-22). Die Seite „Themes & Addons“ der Verwaltung und der Marketplace
  zeigen ein Vorschaubild: ein echter Screenshot des Moduls, Widgets, Themes oder der Sprache, wie es auf der Demo
  läuft. Alle 61 Addons des Marketplace haben eines, in 960×600 für Module und Widgets. Dafür wurden sechs Module und
  zwei Widgets mit Beispielinhalten auf der Demo installiert — ein Glossar, Zitate, Rezepte, eine Ortskarte und ein
  Webradio-Programm —, und die Widgets, die ohne Einstellungen nichts anzeigen, wurden mit Beispieleinstellungen
  aufgenommen. Was nichts zu zeigen hat, behält sein Symbol, wie die Konnektoren Discord, GitHub und Google, deren
  Symbol ihr Logo ist. Die Demo trägt außerdem endlich die Identität ihrer Community — Name, Typ, Gründungsdatum und
  Beschreibung —, die leer geblieben war.

- **Zehn Addons haben endlich eine Beschreibung** (2026-09-22): die sechs Sprachen und die vier Addons der externen
  Anmeldung (das Basis-Addon und seine Konnektoren Discord, GitHub und Google). Sie wird in der Sprache der Website
  angezeigt.

- **Vom Administrator definierte Profilfelder** (2026-09-20). Das Profil eines Mitglieds hatte nur feste Felder.
  **Verwaltung → Benutzer → Profilfelder** lässt eigene hinzufügen — Name im Spiel, Plattform, Rang… —, aus acht
  Feldtypen. Die Mitglieder füllen sie im Panel „Weitere Angaben“ ihres Profils aus. Ein Feld ist standardmäßig privat;
  einmal öffentlich gemacht, wird es auf der Seite des Mitglieds angezeigt. Die Werte sind im Export der
  personenbezogenen Daten des Mitglieds enthalten und werden mit seinem Konto gelöscht.

- **Die Schrift der Website lässt sich in der Verwaltung wählen** (2026-09-20). **Verwaltung → Einstellungen →
  Allgemeine Einstellungen → Schriftart der Website** bietet zwölf Schriften an, die die Schrift jedes Themes ersetzen.
  Standardmäßig behält „Schriftart des Themes“ die eigene Schrift jedes Themes und ruft keinen externen Dienst auf; die
  zwölf angebotenen Schriften werden von Google Fonts ausgeliefert.

- **Das Anmeldeprotokoll wird nicht mehr ewig aufbewahrt** (2026-09-20). Es verzeichnet die IP-Adresse, den Browser und
  das Datum jeder Anmeldung, und nichts löschte es je. Es wird jetzt jenseits von 395 Tagen (dreizehn Monaten) bereinigt,
  eine Dauer, die unter **Verwaltung → Einstellungen → Allgemeine Einstellungen** eingestellt wird; 0 schaltet die
  Bereinigung aus.

- **Erinnerungen für verfolgte Kalendertermine** (2026-09-20). Ein Mitglied, das einem Kalendertermin folgt, erhält vor
  seinem Beginn eine Erinnerung, wie die Teilnehmer eines Ereignisses im Modul Ereignisse. Die Frist wird in der
  Konfiguration des Kalenders eingestellt („Erinnerung vor einem verfolgten Termin“, standardmäßig 24 Stunden, 0 zum
  Deaktivieren).

- **Das Update-Paket gibt an, was es schützt und was es entfernt** (2026-09-20). Es trägt eine Datei
  `nf-manifest.json`: die Ordner der Website, die ein Update nie beschreibt (`config/`, `install/`, `upload/`, `logs/`,
  `backups/`, `cache/`), und die Dateien, die die Version entfernt. Bisher ermittelte das Update selbst, was zu löschen
  war; ein Paket ohne diese Datei wird weiterhin akzeptiert.

### Geändert

- **Die ganze Oberfläche spricht Bootstrap 5** (2026-09-23). Rund 470 Verwendungen von Klassen aus Bootstrap 3 und 4
  blieben, von einem Kompatibilitäts-Stylesheet am Leben gehalten: Formularfelder, farbige Abzeichen,
  Schließen-Schaltflächen, Schaltflächen in voller Breite, Blöcke „Avatar und Text“, Eingabegruppen,
  Kontrollkästchen, Formularraster. Das ganze Markup ist auf seine Entsprechungen in Bootstrap 5 umgestellt; was dem
  Produkt eigen ist, trägt jetzt einen eigenen Namen des Produkts. Eine Prüfung lehnt jeden zurückkehrenden alten Namen
  ab, selbst neu definiert. Was man sieht:
  - die **Auswahllisten** haben ihren Pfeil zurück — etwa hundert, in der Verwaltung und auf der Website, sahen wie
    Textfelder aus;
  - die **farbigen Abzeichen** (Status, Gruppen, Rollen, Sanktionen) sind in jedem Theme lesbar, hell wie dunkel: Ihr
    Text fiel durch die Kontrastprüfung;
  - die Gruppen **„Moderator“ und „Senior-Moderator“** werden angezeigt: Ihr Name stand weiß auf weißem Hintergrund;
  - die **Schließen-Schaltflächen** der Dialoge haben auf Telefonen eine ausreichend große Klickfläche.

- **Das JavaScript wird von einem Linter geprüft** (2026-09-22). Einundsiebzig Dateien des Projekts, über die Jahre
  geschrieben, ohne dass sie etwas außer ihrer Syntax und der Abwesenheit von jQuery prüfte. ESLint sieht, was die
  Syntax nicht sagt: eine durch ein vergessenes `var` angelegte globale Variable, ein verkapptes `eval`, Code nach einem
  `return`, ein mit einem berechneten Wert gespeistes `innerHTML`. Kein npm-Paket wird bereitgestellt: Es ist
  Entwicklungswerkzeug, genau wie die statische Analyse von PHP.

  Die Regeln sind nach einem einzigen Kriterium gewählt — hier schon einmal zugebissen zu haben. Es ist die Familie von
  „`$` is not defined“, die drei Monate lang die Tabellensortierung in der ganzen Verwaltung lahmgelegt hatte. Das
  vorhandene Durcheinander (zu bereinigende `innerHTML`-Verwendungen) wird **so eingefroren, wie es ist**, statt auf
  einmal neu geschrieben: Seine Zahl kann nicht mehr steigen.

- **Die PHPStan-Baseline wird mit einem Befehl neu erzeugt, und die Klasse `Config` des Kerns ist annotiert**
  (2026-09-21). Drei neue Fehler führten dazu, `phpstan-baseline.neon` wieder zu öffnen: Von Hand hinzugefügte Einträge
  waren anders geschrieben als die von PHPStan selbst, und fast dreihundert Blöcke passten auf nichts mehr — eine ganze
  Familie „Function NeoFrag not found“, tot, seit die Bootstrap-Datei die Funktion deklariert. Die Konfiguration lebt in
  `phpstan-base.neon`, `phpstan.neon` fügt nur die Baseline hinzu, und `composer stan:baseline` erzeugt sie vollständig
  neu. `Config` annotiert jetzt die aktuelle Sprache, die Liste der Sprachen und die Einstellungen des Kerns, die die
  Module lesen: Diese Zugriffe werden analysiert, statt eingefroren zu werden.

- **Die Werkzeuge in `tools/` auf einer gemeinsamen Bibliothek neu aufgebaut** (2026-09-21). Rund sechzig Werkzeuge,
  jedes auf seine Art geschrieben, kopierten dieselben Leitungen: einen PHP-Server starten, eine Administratorsitzung
  öffnen, Chrome starten, `config/db.php` lesen. Eine in einem gelernte Lektion erreichte die anderen nicht.
  `tools/lib/` trägt jetzt dieses Fundament — Verbindung, Sitzung, Server, Browser, Durchlaufen des Repositorys, SQL,
  Optionen, Urteile —, und jedes Werkzeug beschränkt sich auf seine eigene Logik. Zweiundfünfzig bleiben, ohne
  Funktionsverlust: `check-js-syntax` und `check-js-jquery` bilden `check-js-sources`; `check-docs-counts` und
  `check-docs-liens` bilden `check-docs`; `check-lang-args` geht in `check-langs` auf; `seed-wiki-docs` und `dump-wiki`
  bilden `wiki-docs`; `smoke-test` wird zu `check-smoke`, wie jede Prüfung; `addons-manifest` und `table-map`, die Daten
  sind, leben in der Bibliothek. Jedes Werkzeug hat jetzt seinen eigenen reservierten Port (mehrere teilten sich
  einen), drei Rückgabecodes, die „nichts zu melden“, „abgelehnt“ und „konnte nicht urteilen“ unterscheiden, und einen
  Kopf, aus dem der Katalog in `tools/README.md` erzeugt wird. `bs5-codemod`, das Werkzeug der Umstellung von Bootstrap
  4 auf 5 im Juni, ist entfernt: `check-classes-bs4` deckt alles ab, was es prüfte, einschließlich der Breakpoints der
  richtungsabhängigen Hilfsklassen.

- **`declare(strict_types=1)` im ganzen nützlichen Bereich** (2026-09-21): **1.365 Dateien** gegenüber 78 am Vortag.
  Die Arbeit ging seit Juni in kleinen Paketen voran; sie ist abgeschlossen. Die 209 verbleibenden Dateien sind die
  Vorlagen `views/**.tpl.php`, bei denen die Deklaration syntaktisch gültig wäre und nichts schützen würde.

  Die Umstellung erfolgte in der Reihenfolge **abnehmenden Risikos** — die Zahl der Aufrufe nativer Funktionen pro
  Datei —, mit der Testsuite zwischen jedem Paket.

  Eine tiefere Folge, diesem Produkt eigen: PHP im strikten Modus **verweigert `__toString()` für interne Funktionen**.
  `lang()` gibt aber ein *verzögertes* Übersetzungsobjekt zurück, das bei der Darstellung aufgelöst wird, sobald die
  Sprache endlich bekannt ist, und auch Daten sind Objekte. Die Leiste der Verwaltung, die Seite jeder News und mehrere
  Bildschirme stürzten daher mit einem schweren Fehler ab — Modul ausgeschaltet, Seite abgeschnitten, ohne ein Wort. Die
  Umwandlung geschieht jetzt an der **Stelle der Maskierung** (`htmlspecialchars`), also dort, wo der Wert per
  Definition zu einer Zeichenkette werden muss: fast 500 Stellen in 86 Dateien.

  Die Welle verlangte auch elf ausdrückliche Umwandlungen, drei davon auf Wegen, die bei jeder Seite genommen werden —
  die Anzeigereihenfolge der Sprachen und der Gruppen, gelesen beim Start jeder Sitzung, und die Berechnung des Tokens,
  das jedes Formular gegen gefälschte Anfragen schützt (CSRF): Ohne sie hätte eine Website mit zwei aktiven Sprachen auf
  allen ihren Seiten einen Fehler 500 zurückgegeben.

- **Das Protokoll sagt jetzt, WO, nicht nur was** (2026-09-21). Wenn ein Modul eine Ausnahme wirft, wird seine
  Darstellung aufgegeben und die Seite abgeschnitten angezeigt — still. Die Protokollzeile gab die Meldung, ohne je eine
  Datei zu nennen. Sie nennt jetzt die erste Stelle im eigenen Code des Produkts im Aufrufstapel. Die Herkunft der obigen
  Fehler zu finden wurde von einer Untersuchung zu einem einfachen Lesen.

- **Der SCSS-Compiler geht auf 2.1** (2026-09-20). Unter PHP 8.5 schrieb jede Kompilierung der Stile **zehn
  Veraltungswarnungen** ins Protokoll; es ist keine mehr übrig. 2.x minimiert etwas mehr (`.25rem` statt `0.25rem`,
  `#ccc` statt `#CCCCCC`), was drei Stylesheets ändert — die der Module Addons und Auszeichnungen und der Wartungsseite.
  Die Gleichwertigkeit wurde nicht aus dem Text abgeleitet: Beide CSS-Sätze wurden der Engine von Chrome vorgelegt, die
  darin genau dieselben Regeln, Eigenschaften und berechneten Werte sieht.

- **Die Dokumentation wurde vollständig mit dem Code abgeglichen** (2026-09-17). Die Leitfäden zur Installation, zu den
  Konzepten und zur Entwicklung (ein Modul, ein Widget, ein Theme erstellen, das Framework) stammten vom Juni und
  beschrieben das CMS, wie es früher war: eine Installation ohne Wahl des Profils, ein Theme, das jQuery lud, Raster
  von Bootstrap 4. Sie sagen jetzt, was das Produkt tut — Installationsprofile, Angaben der Addons, benannte Regionen,
  Front-End ohne jQuery unter einer strikten Sicherheitsrichtlinie, Widget-Einstellungen mit Rückfallwerten, gemeinsames
  Farbvokabular. Die README, der Leitfaden für Beiträge, die Sicherheitsrichtlinie und die technische Referenz sind
  ebenso abgeglichen.

- **Was ein Addon erlaubt, wird aus seiner Angabe gelesen**, nicht mehr aus fest eingetragenen Namenslisten. Drei davon
  entschieden über das Schicksal der Addons, und alle drei waren falsch: Die Liste der Widgets schützte sieben Namen,
  die keine Widgets sind; die Liste der Themes schützte ein nicht existierendes Theme `default` **und ließ `nebula`, das
  einzige öffentliche Theme des Kerns, löschbar**, sobald es inaktiv war; die Liste der Module duplizierte, im
  Widerspruch, was `__info()` bereits sagte. Addon für Addon gegen die alte Logik geprüft: keine Änderung bei dem, was
  sich deaktivieren lässt, keine Änderung des aktiven Zustands, und **39 Addons werden nicht löschbar** — was der Zweck
  der Korrektur ist.

- **Der Papierkorb kennt die anderen Module nicht mehr.** Er trug die Liste der Tabellen, Primärschlüssel und
  Wiederherstellungsmethoden von `news`, `articles`, `gallery`, `comments` und `forum` fest eingetragen: Er konnte daher
  nicht zum Kern gehören, ohne vier optionale Module mitzuziehen — `comments` selbst gehört zum Kern. Jedes Modul gibt
  jetzt seine wiederherstellbaren Typen an; der Kern sammelt sie ein. Dieselbe Umkehrung für die Inhaltsbeschreibungen
  (Reaktionen, Abonnements, Revisionen).

- **Die Liste der Addons des Kerns und der optionalen Addons wird nicht mehr von Hand gepflegt.**
  `tools/lib/addons-manifest.php`, eine von Hand geschriebene und im Juni eingefrorene Tabelle, wird jetzt aus den
  Angaben abgeleitet. Die beiden waren bereits auseinandergelaufen: `emojis` stand als Kern in der Liste, während es sich
  als à la carte erklärt — es ließ sich also bei der Installation abwählen, fehlte aber im Katalog und war daher
  **unmöglich neu zu installieren**. Katalog: 52 → **53 Addons** an jenem Tag (61 beim Erscheinen der 1.2.0).

- **Der Installer optisch überarbeitet**: zweispaltiges Layout, Stil-Tokens aus `nebula` übernommen (Marineblau und
  Petrol, Akzent `#2dd4bf`), Verlauf hinter dem Text hervorgeholt, Logo in einem Abzeichen.

### Behoben

- **Die Sicherung des Monitorings funktioniert wieder** (2026-09-23). Seit dem 21. September hielt sie bei der
  allerersten Zeile der Datenbank an: Die Schaltfläche „Sicherung starten“ und das Update des Kerns, das mit einer
  Sicherung beginnt, schlugen auf jeder Website fehl. Gefunden beim Testen des Updates vor seiner Veröffentlichung.

- **Der Sprachwechsel bei einer übersetzten News führt nicht mehr zu einer nicht gefundenen Seite** (2026-09-23). Die
  Sprachauswahl behält die Adresse, und mit ihr den Titel in der vorherigen Sprache: Die übersetzte Version lehnte ihn
  ab. Sie leitet jetzt auf ihre eigene Adresse um.

- **Eine übersetzte Seite wird in der richtigen Sprache angezeigt** (2026-09-23). Die öffentlichen Seiten wurden in der
  Version ausgeliefert, die die Datenbank zuerst zurückgab, unabhängig von der Sprache des Besuchers.

- **Das von einem Besucher gewählte Theme schwappt nicht mehr auf eine andere Website derselben Domain über**
  (2026-09-23). Die Wahl wurde für die ganze Domain gemerkt: Ein Besucher, der eine Website auf Forge umstellte, sah auch
  eine andere unter derselben Domain installierte Website in Forge. Die Wahl wird jetzt **für jede Website getrennt**
  gemerkt, und eine neue Einstellung, **Allgemeine Einstellungen → Designauswahl**, lässt sie abschalten: Das Menü
  verschwindet, und das Standard-Theme gilt für alle.

- **Die Demo setzt sich wieder zurück** (2026-09-23). Ein Fehler in ihrem Datensatz ließ das Zurücksetzen alle fünfzehn
  Minuten seit dem Vortag fehlschlagen: Was die Besucher dort änderten, blieb. Eine Prüfung lehnt diesen Fehler jetzt
  ab, bevor etwas online geht, und ein fehlgeschlagenes Zurücksetzen wird ins Protokoll geschrieben, statt unbemerkt zu
  bleiben.

- **Das Notfallkonto der Demo erscheint nirgends mehr** (2026-09-23): weder in der Mitgliederliste, noch in der Suche,
  noch unter den Online-Mitgliedern, noch in der Verwaltung; seine Seite antwortet „nicht gefunden“.

- **Die Website auf einem Telefon** (2026-09-23), anhand von auf einem Telefon aufgenommenen Screenshots:

  - **Das Menü ist immer erreichbar.** Im Theme Nebula verschwand es unter 860 Pixeln Breite, ohne Schaltfläche, um es
    wieder zu öffnen: Eine „Burger“-Schaltfläche öffnet es jetzt. In den anderen Themes klappt ein Menü, das zu lang für
    eine Zeile ist, hinter einer Schaltfläche „Menü“ zusammen, statt umzubrechen, bis es den Bildschirm füllt; ein kurzes
    Menü bleibt ausgeklappt.
  - **Gestapelte Blöcke berühren sich nicht mehr**: Der Mitgliederbereich klebte am Block darüber.
  - **Text klebt nicht mehr an den Avataren der Mitglieder** (Kommentare, Unterhaltungen, Autor einer News, Bewerbungen,
    Profil).
  - **Die Punkte der Diashow** sind echte Schaltflächen, unter dem Bild zentriert, und bleiben auf dunklen Themes
    **hell**, wie die Pfeile und die Beschriftung: Bootstrap machte sie schwarz.
  - **Die Suche des Themes Extend** behält ihre Schaltfläche neben dem Feld.
  - **Die Fotos eines Albums** füllen ihre Karte.
  - Der Kopf der Kommentare zeigt den Titel vor dem Zähler, der ihm vorausging.
  - Abgekürzte Daten werden in der Sprache der Website geschrieben („30 août 2026“, „Sonntag“), und nicht mehr auf
    Englisch; die Autorenzeile einer News hat wieder eine normale Textgröße.

- **Die Fotoseite der Galerie existiert** (2026-09-23). Das Widget „Zufallsbild“, die Diashow der Galerie und der Link
  eines zu einem Foto geschriebenen Kommentars führten alle zu einer nicht gefundenen Seite: Sie war nie angegeben
  worden. Sie zeigt das Foto, seine Beschreibung, den Rückweg zum Album und die Kommentare. Ein Foto folgt außerdem den
  Regeln seines Albums: kein Zugriff mehr über seine Adresse, wenn das Album im Papierkorb oder noch nicht
  veröffentlicht ist.

- **Letzte Reste von Bootstrap 3 und 4** (2026-09-23), die ihre Klassennamen nicht verrieten: die Struktur der
  Diashow, die Schließen-Kreuze, die ein „×“ über das Symbol legten, zwei Akkordeons (FAQ, Navigationslinks), noch nach
  Art von Bootstrap 4 gebaut (das der FAQ öffnete sich, aber ohne das Aussehen und den Pfeil der Komponente), Felder, die
  sich in eine leere Gruppe hüllten, der weiß gebliebene Pfeil der Tooltips unter einem dunklen Tooltip, und zwei
  Regeln des Widgets der Partner. `check-classes-bs4` liest jetzt auch die STRUKTUR der Komponenten und die in den
  Vorlagen geschriebenen Stile.

- **Fehlerseiten, die keine waren** (2026-09-23). In der Moderation und in der Verwaltung der Rollen, der
  E-Mail-Vorlagen, der Mitglieder und der Diashow sollte eine Adresse, die auf ein entferntes Element zeigt, „Seite nicht
  gefunden“ antworten: Sie rief eine Funktion auf, die nicht existiert, schrieb eine Warnung ins Protokoll, und die Seite
  der Moderation wurde leer angezeigt. Gefunden, indem der statischen Analyse die „magischen“ Funktionen des Frameworks
  beigebracht wurden — was 455 Vorkommen aus ihrer Ausnahmeliste strich.

- **Die Schaltfläche in den an Mitglieder geschickten E-Mails ist lesbar** (2026-09-23): Kontobestätigung, verlorenes
  Passwort und die anderen Vorlagen hatten eine weiße Schaltfläche auf hellem Türkis (Kontrast 2,3:1). Die Korrektur gilt
  für die mitgelieferten Vorlagen und für die bereits installierten, außer denen, die ein Administrator angepasst hat.

- **Letzte Nachbesserungen der Anzeige** (2026-09-23): Links im Wiki im lesbaren Farbton des Themes; Schaltflächen
  „Erfolg“ auf dunklen Themes; Status einer Bewerbung, deren Kasten keinen Hintergrund hatte (Klassen eines alten
  Verwaltungs-Themes); soziale Netzwerke auf den Mitgliederkarten; abgeschnittene Titel in der Verwaltung; ein Besucher,
  der dem Link zur Teilnahme an einem Ereignis folgt, erhält „Unbefugter Zugriff“ statt einer nicht gefundenen Seite.

- **Text ist überall lesbar, im hellen wie im dunklen Modus** (2026-09-23). Links und Schaltflächen der Verwaltung,
  Hinweise, Daten und Tabellenköpfe der Themes, Kategorien der News, die Marketplace-Seite auf hellen Themes, die
  Schaltfläche „Alle akzeptieren“ des Cookie-Banners, Abzeichen der Reputation und der Ereignistypen: Alle erreichen
  jetzt den empfohlenen Mindestkontrast (4,5:1). Im Dunkelmodus hat die Verwaltung ihre eigenen Statusfarben, und das
  Panel „Site-Gesundheit“ und die Fläche zum Hochladen von Bildern der Galerie folgen dem Theme.

- **Die Verwaltung auf einem Telefon** (2026-09-23). Die obere Leiste bricht auf zwei Zeilen um — der vollständige
  Brotkrumenpfad, dann die Schaltflächen —, statt den Titel der Seite abzuschneiden; die kleinen Ziele (Startsymbol,
  Zähler) sind groß genug für einen Finger; der Kopf der Forenkategorien rutscht nicht mehr unter seine Schaltflächen.

- **Das Forum auf einem Telefon** (2026-09-23): In einem Thema rückt der Autor über die Nachricht, statt ihr nur 200 px
  Breite zu lassen, und die Schaltflächen einer Nachricht verdecken ihr Datum nicht mehr.

- **Übersetzte Pluralformen** (2026-09-23). 47 Zeichenketten hatten bei der Übersetzung ihre Pluralform verloren: Eine
  Website auf Englisch zeigte „3 topic“, „5 image“, „2 year“. Der Zähler der Forenkategorien, in jeder Sprache auf
  Französisch geschrieben, ist jetzt übersetzt.

- **Die Dokumentation der Website ist aktuell** (2026-09-23). Die zehn mit dem Produkt ausgelieferten Wiki-Seiten und die
  der Demo hinkten den Leitfäden eine Woche hinterher; „Ein Widget erstellen“ lehrte noch eine Klasse von Bootstrap 4.
  Eine Prüfung vergleicht sie jetzt mit den Leitfäden.

- **Das Antwortformular des Forums passt auf ein Telefon** (2026-09-23): Die Liste der erlaubten Anhangstypen, ohne
  Leerzeichen geschrieben, bildete ein einziges Wort, zu breit für den Bildschirm.

- **Die ganze Website durchleuchtet, in jedem Theme, jedem Modus und jeder Breite** (2026-09-23). Eine automatische
  Prüfung stellt jetzt jede Seite der Verwaltung in ihrem Theme und jede öffentliche Seite in jedem öffentlichen Theme
  dar, hell und dunkel, als Administrator und als Besucher angemeldet, in acht Breiten von 360 bis 2560 px, mit und ohne
  Inhalt. Dieser erste Durchgang hat behoben:
  - **kaputte Symbole** in den Kategorielisten der Galerie, der Spiele, der Teams und der News, wenn eine Kategorie kein
    Symbol hatte;
  - das **Land auf den Profilen** der Demo, ausgeschrieben: Das Profil zeigte kein Land an und suchte eine Flagge, die
    sich nicht finden ließ;
  - die **Betreffzeilen der E-Mail-Vorlagen**, die `{{site_name}}` wörtlich in der Liste zeigten;
  - **für ein Telefon zu breite Tabellen** (Bugtracker, Downloads, Wiki-Verlauf, Moderation, Sitzungen, Teilnehmer eines
    Ereignisses, Auszeichnungen): Sie scrollen jetzt in ihrem Rahmen, statt die ganze Seite zu verbreitern;
  - die **Listen des Forums** auf einem Telefon: Vier Spalten teilten sich 360 px, und die letzte Nachricht wurde
    abgeschnitten; jeder Eintrag stapelt sich jetzt — Symbol und Titel, dann Statistiken und letzte Nachricht;
  - die **Leiste des Themes Nebula** auf einem Telefon, die über den Bildschirm hinausragte;
  - drei von Seiten angefragte Dateien, die nicht existierten, und zwei bei jeder Update-Prüfung ins Protokoll
    geschriebene Zeilen, solange keine Version veröffentlicht ist.

- **Die Schaltflächen „Bearbeiten“ und „Löschen“ sind nicht mehr versetzt** (2026-09-22). In den zweispaltigen Listen der
  Verwaltung — Kategorien der News, Kategorien der Galerie… — brach die zweite Schaltfläche auf die nächste Zeile um,
  versetzt unter die erste. Sie bleiben jetzt nebeneinander.

- **Die Karte „Speicher“ des Monitorings wird richtig angezeigt** (2026-09-22). Die Anzeige wurde als voller Kreis statt
  des vorgesehenen Halbkreises gezeichnet, mit einer rohen Zahl in der Mitte, und Wert und Prozentsatz, oben platziert,
  überlappten den Strich; sie sitzen jetzt in der Mulde der Anzeige. Der Titel „Serverinformationen“ auf derselben Seite
  bricht auf zwei Zeilen um, wenn die Spalte schmal ist, statt abgeschnitten zu werden. In der Gruppe „E-Mail-Versand“
  zeigte die Zeile „E-Mail-Transport“ `[object Object]` statt der verwendeten Methode.

- **Ein ohne Einstellungen platziertes Widget wird mit seinen Standardwerten angezeigt** (2026-09-22). Wenn ein Theme bei
  der Installation ein Widget platziert oder ein älteres Layout es wiederherstellt, kommt es ohne Einstellungen an: Es
  schrieb dann auf jeder angezeigten Seite Warnungen ins Protokoll, und das Navigationsmenü verschwand einfach. Es erhält
  jetzt die Werte, die sein Formular standardmäßig gespeichert hätte. Bereits konfigurierte Widgets ändern sich nicht.

- **Die Seite der Ereignisse wird wieder angezeigt** (2026-09-22). Sie zeigte eine Fehlerseite „nicht gefunden“, sobald
  sie Ereignisse auf mehrere Seiten zu verteilen hatte — unter einem ganz normalen Titel, was es schwer machte, es zu
  bemerken. Die Einstellung der Zahl der Ereignisse pro Seite wurde als Text gespeichert, und der am Vortag strenger
  gemachte Code lehnte sie ab. Alle Listen mit Seitennavigation akzeptieren diese Einstellung jetzt in beiden Formen.

- **Die Stimmen der Umfragen werden verzeichnet** (2026-09-22). Der Abstimmende las „Danke für deine Stimme!“, und die
  Stimme wurde nie gezählt: Keine Option wurde als zur Umfrage gehörig erkannt. Eine Stimme, die auf keine Option der
  Umfrage zielt, wird jetzt mit einer entsprechenden Meldung abgelehnt, statt willkommen geheißen zu werden.

- **Das Widget „Forum“ zeigt endlich Themen und Beiträge** (2026-09-22). Seine beiden Typen, „Neueste Themen“ und
  „Neueste Beiträge“, wählten überhaupt keine Forenkategorie und blieben daher auf jeder Website leer, wie viele
  Nachrichten es auch gab.

- **Drei weitere Funktionen tappten in dieselbe Falle wie die Umfragen** (2026-09-22), gefunden von der für diesen
  Fehler geschriebenen Prüfung:
  - **das Öffnen einer Bewerbung mit eigenen Feldern** ließ die Seite abstürzen, auf Seiten des Bewerbers wie in der
    Verwaltung;
  - **eine Benachrichtigung, die auf einen Kalendertermin verweist**, stürzte beim Bau ihres Links ab;
  - **die maximale Tiefe der Antworten im Forum** wurde nie angewendet: Die Berechnung hielt bei der ersten Ebene an.

- **Die Rückkehr auf Seite 1 einer Liste führt nicht mehr zu einer nicht gefundenen Seite** (2026-09-22). In den Listen
  der Verwaltung, deren Zahl der Elemente pro Seite sich einstellen lässt, baute die Schaltfläche „1“ eine Adresse, die
  die Website nicht erkannte.

- **Der Bearbeitungsbildschirm eines eigenen Profilfelds öffnet sich** (2026-09-22). Der Bildschirm stürzte bei jedem
  Öffnen ab: Er gab seiner Speichern-Schaltfläche keine Beschriftung.

- **Zwei Schaltflächen „Zurück“ führten zu einer nicht gefundenen Seite** (2026-09-22): von der Seite eines Bildes zu
  seinem Album; von einem Spielmodus zu seinem Spiel.

- **„Bewerben“ erscheint nicht mehr dort, wo die Bewerbung abgelehnt würde** (2026-09-22): bei einem geschlossenen oder
  vollen Angebot und für jeden, der sich nicht bewerben darf. Ebenso kann ein Administrator, der eine dem Team
  vorbehaltene Unterhaltung liest, jetzt dorthin einladen und eine Nachricht darin melden, statt nach dem Sehen der
  Schaltfläche abgewiesen zu werden.

- **Die Untermenüs des Navigations-Widgets öffnen sich** (2026-09-22). Ein Link, der andere Links gruppiert, klappte beim
  Klick nichts auf: Er trug noch das Attribut der alten Version von Bootstrap, nach dem sein eigenes Skript nicht mehr
  suchte.

- **Die übrigen Hilfe-Tooltips der Formulare erscheinen beim Überfahren** (2026-09-22). Die Korrektur vom Vortag deckte
  nur einen Teil der Formulare ab: Das Symbol (i) neben den Feldern der anderen Verwaltungsbildschirme und die Tooltips
  der Beschriftungen zeigten weiterhin nichts — derselbe Fehler des Attributs, in den beiden Bibliotheken, die sie bauen.

- **Menübeschriftungen mit Akzent werden übersetzt** (2026-09-22). Ein aus der Verwaltung gespeicherter Menülink —
  „Actualités“, „Équipes“ — wird kodiert gespeichert, und seine Übersetzung ließ sich nicht mehr finden: Er blieb in den
  fünf anderen Sprachen auf Französisch, mit einer Warnung im Protokoll auf jeder angezeigten Seite.

- **Die Archive des Marketplace sind reproduzierbar** (2026-09-22). Sie trugen die Source Maps `.map`, die der
  Stil-Compiler bei jeder Installation neu erzeugt und die das Repository ignoriert: Zwei Archive desselben Addons, auf
  zwei Maschinen gebaut, unterschieden sich, obwohl sich der Code nicht geändert hatte. Die Archive tragen diese Dateien
  nicht mehr.

- **Das Widget „Auszeichnungen“ lässt sich endlich durch Hochladen eines Archivs installieren** (2026-09-22). Es war das
  einzige der 61 verteilbaren Addons, das seine Abhängigkeit vom Kern nicht angab; der Installer, der sie verlangt, um
  ein Addon zu erkennen, übersprang es OHNE EIN WORT — keine Meldung, keine Spur.

- **Die Wartungsseite ist nicht mehr leer** (2026-09-22). Die beiden Felder „Titel“ und „Inhalt“ werden LEER
  ausgeliefert, sodass die Seite NICHTS außer dem Namen der Website zeigte — obwohl die Vorschau der Verwaltung einen
  Titel und einen Text versprach. Ein Standardtitel und eine Standardmeldung, in alle sechs Sprachen übersetzt, füllen
  die Lücke; sobald die Felder ausgefüllt sind, werden sie angezeigt.

- **Umriss-Schaltflächen haben ihren Rand zurück** (2026-09-22). Die Themes definierten nur die Variante „primary“ der
  Umriss-Schaltfläche neu, und ihre eigene Regel löschte den Rand der anderen: Die sechs anderen vom Produkt verwendeten
  Varianten — 90 Verwendungen im Code — sahen wie einfache Links aus. Am sichtbarsten war „Webseite öffnen“ im
  Wartungsbanner, das nur beim Überfahren einen Rand hatte.

- **Technischer Code ist in der Verwaltung nicht mehr rosa** (2026-09-22). Das Theme legte nur die Schrift der
  `<code>`-Ausschnitte fest, die daher das Rosa von Bootstrap behielten — eine jeder Palette des Produkts fremde Farbe.
  Sehr sichtbar in der Liste der Rollen.

- **Die Schaltfläche „Schließen“ der Fenster schließt endlich das Fenster** (2026-09-22). In ALLEN Fenstern des Produkts
  tat die Schaltfläche „Schließen“ (oder „Abbrechen“) unten nichts: Man brauchte das Kreuz oben rechts. Die Ursache:
  Bootstrap 5 hat das Attribut umbenannt, das das Schließen auslöst, und der Kern setzte noch den alten Namen; ein
  Browser ignoriert ein unbekanntes Attribut **still**. Die Schaltfläche ist außerdem eine echte Schaltfläche geworden —
  sie wurde als `<span>` dargestellt und war daher mit der Tastatur nicht erreichbar. Zwei Schutzvorrichtungen
  verhindern, dass das zurückkommt: Die Prüfung des Codes lehnt den alten Namen ab, und ein von Hand gestarteter
  Browserdurchlauf KLICKT die Schaltfläche.

- **Das Theme Nebula zeigt sein Menü nicht mehr doppelt** (2026-09-22). Jede neue Installation zeigte den Namen der
  Website zweimal und das Menü zweimal: Das Theme zeichnet seine eigene Leiste, und die Installation platzierte AUCH
  einen Titel und ein Menü direkt darunter. Das Menü lebt jetzt in der oberen Leiste, wo es nur einmal erscheint — und es
  ist aus der Verwaltung **einstellbar**, was vorher nicht der Fall war: Die Links der Leiste waren fest eingetragen,
  also fehlte „Kontakt“ und ein deaktiviertes Modul behielt seinen Link. Bereits installierte Websites werden durch eine
  Migration korrigiert, die den Kopfbereich nur anfasst, wenn er wie ausgeliefert geblieben ist.

- **Der Sprachwechsel landet nicht mehr auf einer Seite mit Code** (2026-09-22). Die Sprachauswahl in der Fußzeile — die
  von **fünf Themes**, darunter das Standard-Theme — schickte den Besucher auf eine Seite, die `{"redirect":"/en/…"}` als
  rohen Text zeigte, statt ihn zur übersetzten Seite zu bringen. Man musste zurückgehen, um herauszukommen.

  Die Ursache: Diese Auswahl ist ein echtes Formular, das nichts abfing, und sie zielte auf eine technische Adresse, die
  immer in JSON antwortet. Der Server unterscheidet jetzt einen Aufruf aus dem Fenster „Meine Sprache wählen“, das JSON
  erwartet, von einer gewöhnlichen Formularübermittlung, die erwartet, zur Seite gebracht zu werden. Gefunden, indem man
  der Geste in einem echten Browser folgte, was bis dahin keine Prüfung getan hatte.

- **In einer einzigen Sprache geschriebene Inhalte schicken die anderen Sprachen nicht mehr auf eine Fehlerseite**
  (2026-09-21). Eine auf Französisch geschriebene News bot in ihrer Auswahl noch die fünf anderen Sprachen an, und alle
  fünf antworteten 404: auf der Demo gemessen, **60 tote Adressen von 72**. Die natürlichste Geste eines ausländischen
  Besuchers landete auf einem Fehler. Die existierende Version wird jetzt ausgeliefert, mit einem Banner, das es in der
  Sprache des Besuchers sagt, und einem Link zum Original. Das gilt für News, Artikel und Alben (und ihre Kategorien)
  sowie für die Teams.

  Zwei untrennbare Vorsichtsmaßnahmen, ohne die die Heilung teurer wäre als die Krankheit: Die Seite kündigt
  Suchmaschinen jetzt nur die Sprachen an, die **wirklich existieren**, und sie bestimmt das Original als kanonische
  Adresse — sonst würden sechs Adressen für einen einzigen Text indexiert. **In der Verwaltung kein Rückfall**: Eine
  leere Version muss leer erscheinen, man kommt ja, um sie auszufüllen. `tools/check-langues-contenu.php` setzt alle drei
  Eigenschaften durch, in der kontinuierlichen Integration.

- **Die kontinuierliche Integration ist wieder grün** (2026-09-21); auf dem Hauptzweig war sie seit dem 10. Juni rot.
  Fast alle Ursachen lagen in den Testwerkzeugen und der Konfiguration der CI, nicht im Produkt. Die Ausnahme: Unter dem
  eingebauten Server von PHP 8.3 konnte die Website ihre Basisadresse falsch berechnen und Stylesheets und Skripte
  umleiten; sie leitet sie jetzt nur noch aus einem `SCRIPT_NAME` ab, das auf `index.php` endet. Eine Testsuite, die
  nicht laufen kann, schlägt jetzt fehl, statt sich still zu überspringen, und die meisten Jobs der CI haben eine
  Zeitgrenze.

- **Das Logo des Installers wurde bei einer neuen Installation nicht angezeigt** (2026-09-21): Der Pfad war relativ zum
  Ordner `install/`, den der Server nicht unter dieser Adresse ausliefert. Das Bild ist jetzt in die Seite eingebettet.

- **Komponenten, die sich seit dem Wechsel zu Bootstrap 5 nicht mehr öffneten** (2026-09-21). Bootstrap 5 hat all seinen
  Datenattributen das Präfix `bs-` gegeben. Ein unter dem alten Namen gebliebenes Attribut verursacht keinen Fehler: Die
  Komponente tut einfach nichts. Diese hier waren still tot:

  | Was nicht mehr funktionierte | Wo |
  |---|---|
  | **Das Akkordeon der FAQ** öffnete sich nicht | `modules/faq` |
  | **Die Teilnehmerliste eines Ereignisses** klappte nicht auf | `modules/events` |
  | **Die Hilfe-Popovers eines Teils der Formulare** waren leer | Kern — `neofrag/libraries/form.php` |
  | Das Popover des Forenprofils, das zum Löschen eines Navigationslinks | `modules/forum`, `widgets/navigation` |
  | Die Platzierung der Tooltips im Live-Editor, die Verzögerung des Karussells | `modules/live_editor`, `widgets/slider` |

- **Kaputte Layouts, auch stille** (2026-09-21). Bootstrap 5 hat seine richtungsabhängigen Hilfsklassen umbenannt; eine
  umbenannte Klasse ist nirgends mehr definiert, und das Element behält sein Standardlayout, ohne dass etwas darauf
  hinweist.

  - **Die Fußzeilen von Panels und Modalfenstern richteten sich nicht rechts aus.** Der Kern baute die Klasse durch
    Verkettung (`'float-'.$align`), also konnte keine Suche nach `float-right` sie finden. Die einzige
    Kompatibilitätsbrücke des Produkts deckte nur die Tabellen des Verwaltungs-Themes ab: nichts für die öffentlichen
    Themes. Dieselbe Ursache bei der Ausrichtung der Tabellenzellen, in beiden Bibliotheken.
  - **Kein abgelehntes Formularfeld wurde markiert**, in keinem Theme. Beide Bibliotheken setzten einen veralteten Namen
    — `has-error` (Bootstrap 3) und `has-danger` (eine Vorabversion von Bootstrap 4) —, den kein ausgeliefertes
    Stylesheet definiert. Die Meldung erschien zwar, aber nichts zeigte das betroffene Feld an. Sie sind durch eine
    eigene Markierung ersetzt, einmal definiert.
  - **Die Fußzeile** trug `<div class="float-right">`: „Betrieben von NeoFrag Reborn“ rutschte auf eine neue Zeile, nach
    links. Der Wert reicht weit zurück — eine Migration von Alpha 0.2.2 hatte ihn zur Zeit von Bootstrap 4 von
    `pull-right` in `float-right` geändert, und der nächste Schritt wurde nie gemacht. Eine Migration
    (`2026_09_20_bootstrap5_float`) korrigiert die bestehenden Websites; das Seed liefert jetzt `float-end`.
  - Und die letzten toten Klassen in den Vorlagen: die Debug-Leiste auf einem Telefon, der Abstand der Ereignislisten,
    die Beschriftungen des Menü-Editors, die beiden Balken der Abstimmung der Rekrutierung — die in **derselben Farbe**
    herauskamen, sodass die Grafik günstige von ungünstigen Meinungen nicht mehr unterschied.

- **Der Text eines Artikels konnte bei der Anzeige gelöscht werden** (2026-09-21). Der Bau des Inhaltsverzeichnisses
  ersetzte den Text durch das Ergebnis eines Verarbeitungsschritts, der bei einem langen Artikel aufgeben kann: Die Seite
  antwortete dann normal, mit einem **leeren** Artikel, und nichts in den Protokollen.

- **Ein oben oder unten auf der Seite platziertes Widget verschwand.** Das Theme *Nebula* bot im Layout-Editor die Plätze
  „Kopfbereich“ und „Fußzeile“ an, zeigte sie aber nicht an. Das Widget wurde zwar gespeichert, und nichts erschien —
  ohne Meldung. Die vier anderen öffentlichen Themes waren nicht betroffen.

- **Das Navigations-Widget bot zwei interne Module an** (`live_editor` und `pages`), die es hätte verbergen müssen.
  Derselbe Fehler ließ die Statistiken der Verwaltung ihren Modulnamen verlieren, ohne sichtbare Wirkung, solange nicht
  zwei Module denselben Statistiknamen verwenden.

- **Das Debug-Protokoll wuchs unbegrenzt**, wenn es eingeschaltet war. Es ist jetzt begrenzt: über 64 MB fängt es neu an
  und behält die vorherige Generation.

- **Auf einer englischen Website blieb „No“ auf Französisch („Non“).**

- **Fünf Verwaltungsseiten boten keinen Rückweg** (2026-09-20): die Detailseite eines Mitglieds, das Audit-Protokoll,
  das Hinzufügen einer Gruppe, die Sitzungsliste und die Dateien des Monitorings. Der Brotkrumenpfad macht den
  Modulnamen nur anklickbar, wenn das Modul angibt, eine Verwaltungsstartseite zu haben; `user` und `monitoring` gaben
  das Gegenteil an, obwohl sie eine haben. Einmal auf diesen Seiten, musste man über das Seitenmenü oder die
  Zurück-Schaltfläche des Browsers gehen.

- **Suchmaschinen erhielten JSON statt der Sitemap.** Im Stammverzeichnis angefragt, ohne Sprachpräfix — der einzige Weg,
  auf dem ein Robot sie anfragt —, antwortete `/sitemap.xml` `{"redirect":"/fr/sitemap.xml"}` mit dem Statuscode 200, und
  `robots.txt` und `humans.txt` taten dasselbe. Die Umleitung, die das Sprachpräfix hinzufügt, antwortete in JSON, wann
  immer die Adresse auf `.txt`, `.xml` oder `.json` endete. Da diese Dateien keine Version pro Sprache haben, gehen sie
  nicht mehr über diese Umleitung: Sie werden direkt ausgeliefert, mit ihrem tatsächlichen Inhaltstyp.

- **`/favicon.ico` antwortete 404.** Browser fragen diese Adresse an, was auch immer die Seite angibt; sie führt jetzt
  zum in den Einstellungen konfigurierten Favicon, oder zu dem des CMS.

- **Das Einschalten der Zwei-Faktor-Authentifizierung stürzte ab.** Beim Speichern der Wiederherstellungscodes
  unterbrach ein Fehler die Seite: Das Konto stand am Ende als „geschützt“ da, ohne einen einzigen Notfallcode. Gefunden
  durch einen Test, der beim Verschärfen der Typisierung dieser Bibliothek geschrieben wurde; behoben, und der Test
  bleibt.

- **Eine Grenze „ein einziger Versuch“ blockierte nie.** Die Schwelle wurde erst ab dem zweiten Versuch geprüft. Keine
  Wirkung bei den mitgelieferten Schwellen, aber die Meldegrenze der Moderation, die sich bis auf 1 einstellen lässt,
  blockierte dann nie. Die Schwelle gilt jetzt ab dem ersten Versuch.

- **Mancher Text blieb in den anderen Sprachen auf Französisch, und das Protokoll füllte sich weiter.** Drei Ursachen,
  alle behoben: Der Titel einer Wiki-Seite, eines Ereignistyps oder einer Spendenkampagne ging durch die Übersetzung, als
  wäre er Text der Oberfläche (er hat keine Übersetzung: Er ist Inhalt); bereits übersetzter Text ging erneut durch die
  Übersetzung — „Espace membre“ wurde zu „Member area“, dann wurde „Member area“ als Schlüssel gesucht und bei jeder
  Anzeige als fehlend gemeldet; und dreißig Zeichenketten der Oberfläche (das Feld „Benutzername oder E-Mail-Adresse“ des
  Anmeldeformulars, Panel-Titel in der Verwaltung der Spiele und der Rekrutierung, die sozialen Netzwerke des Profils…)
  hatten in **keiner** Sprache eine Übersetzung, weil die Prüfung der sechs Sprachen nur die ausdrücklichen
  Übersetzungsaufrufe betrachtete, nicht die weiter hinten übersetzten Titel von Feldern und Köpfen. Die Prüfung sieht
  sie jetzt; die dreißig Zeichenketten sind ins Englische, Deutsche, Spanische, Italienische und Portugiesische
  übersetzt.

- **Google Analytics wartet jetzt auf deine Einwilligung.** Wenn eine Analytics-ID konfiguriert ist, lädt das Skript von
  Google jetzt erst nach einem Klick auf „Alle akzeptieren“ im Cookie-Banner, nie vorher. Außerdem blockierte die
  Sicherheitsrichtlinie der Website dieses Skript in jedem Fall: Analytics funktionierte nicht, und nichts sagte es. Es
  funktioniert jetzt, und nur mit der Einwilligung des Besuchers.

- **Drei Funktionen der Verwaltung antworteten seit Juni nicht mehr.** Das Sortieren der Tabellen durch Klick auf einen
  Spaltenkopf, das Umsortieren von Kategorien, Foren und Unterforen per Drag-and-Drop, und die Schaltflächen
  Offen/Geschlossen der Seiten Wartung und Registrierungen: Ihre Skripte setzten noch jQuery voraus, obwohl es im Juni
  2026 aus dem CMS entfernt wurde, und blieben beim Laden der Seite stehen — ohne Meldung, die Schaltflächen blieben
  einfach reglos. Alle drei sind ohne jQuery neu geschrieben, durch Browser-Tests abgedeckt, und eine automatische Prüfung
  lehnt jetzt jedes Skript ab, das wieder jQuery aufrufen würde.

- **Eine fehlerhafte Adresse verursachte einen Serverfehler.** Eine Anfrage wie `/fr/a:80` oder `/:80`, wie Robots sie
  schicken, ließ die Berechnung der Endung der Adresse abstürzen und gab dem Besucher einen Fehler 500 zurück. Die
  Website antwortet jetzt mit einem gewöhnlichen 404.

- **Das Kontaktformular schrieb „Von:“ mit der Adresse des Besuchers.** Eine Nachricht von seinem Server im Namen von
  `visiteur@example.org` zu verschicken ist genau das, was die Schutzmechanismen der E-Mail-Anbieter gegen Fälschung
  (SPF, DMARC) ablehnen: Die Nachricht landete im Spam oder wurde abgelehnt. Der Absender ist jetzt die Kontaktadresse
  der Website, und die Adresse des Besuchers steht in „Antwort an“, was in der Praxis auf dasselbe hinausläuft. Dieselbe
  Korrektur für die Antworten auf eine Bewerbung, die mit der persönlichen Adresse des Administrators hinausgehen
  konnten.

- **Der Installer konnte eine ungültige Kontaktadresse speichern.** Wurde die Website installiert, indem man über ihre
  IP-Adresse auf sie zugriff, wurde die Kontaktadresse `noreply@<IP>`, die E-Mail-Anbieter ablehnen — und keine E-Mail
  ging mehr hinaus, ohne sichtbare Fehlermeldung. Die Adresse wird jetzt nur noch aus einem echten Domainnamen abgeleitet.

- **Ein leeres Banner erschien auf Seiten, bei denen eine Zone des Layouts leer ist.** Im Theme Extend zeigte jede Seite
  außer der Startseite ein breites farbiges Band, das nichts enthielt, sowie einen leeren Bereich über dem Inhalt.

  Die Ursache war nicht das Theme: Eine angegebene Zone ohne Widget gab Leerraum statt gar nichts zurück, sodass die
  Themes ihren Rahmen um nichts zeichneten. Einmal für alle Themes behoben. Wenn du ein Theme baust, kannst du
  `if ($zone = $this->output->region('banner'))` (oder `zone(n)`) schreiben: Die Bedingung sagt jetzt die Wahrheit.

- **Das Theme Extend zeigte seinen Hinweis „Betrieben von NeoFrag Reborn“ zweimal.** Sein mitgeliefertes Layout setzte
  einen Block in die Fußzeile, der wiederholte, was das Theme bereits selbst schreibt. Bereits installierte Websites
  werden beim Update automatisch korrigiert; neue werden ihn nie haben.

- **Die Suche nach einem häufigen Wort gab eine Fehlerseite zurück.** Eine Suche wie „le“ oder „de“ zeigte ihre
  Ergebnisse zwar auf dem Bildschirm an, aber der Server antwortete **404** — was Suchmaschinen, Überwachungswerkzeuge
  und Browser sehen, und was eine völlig gültige Seite wie eine verschwundene aussehen ließ.

  Das Hervorheben der gefundenen Wörter setzte voraus, dass das gesuchte Wort zwangsläufig im angezeigten Text vorkam.
  Das ist doppelt falsch: Die Suche untersucht **mehrere Felder** und zeigt nur eines an — ein Forenthema mit dem Titel
  „Salut tout le monde !“ passt auf „le“ über seinen Titel, nicht über die darunter angezeigte Nachricht —, und die
  Datenbank vergleicht **ohne Rücksicht auf Akzente**, während der Code sie berücksichtigte: „éléphant“ kam bei der Suche
  „ele“ zwar heraus, ohne dass darin ein Wort hervorgehoben wurde.

  Beide Fälle werden behandelt: Das Hervorheben erkennt jetzt die Varianten eines Buchstabens mit Akzent, wie es die
  Datenbank tut, und wenn das Wort wirklich nicht im angezeigten Text steht, beginnt der Auszug einfach an seinem
  Anfang.

- **Die automatischen Updates des Kerns konnten nicht funktionieren** — fünf Fehler, jeder für sich allein ausreichend:
  Die Adresse zum Prüfen neuer Versionen war nirgends festgelegt, also erschien die Schaltfläche nie; ein globaler
  Schalter, in der Konfiguration zu setzen, blockierte den Vorgang; der Download zeigte auf die Versionen des
  ursprünglichen NeoFrag, was NeoFrag Reborn überschrieben hätte; das Paket legte seine Dateien in einem Unterordner ab,
  sodass ein Update **nichts ersetzt hätte, ohne den geringsten Fehler**; und das Archiv wurde über eine seit PHP 8.0
  veraltete PHP-Schnittstelle entpackt.

  Der Schalter ist durch vier Garantien ersetzt: Die Quelle der Versionen muss auf einer **Positivliste von Adressen**
  stehen (dieselbe wie für den Marketplace); die Versionsdatei gibt nur einen **Dateinamen** an, nie eine Adresse; die
  Prüfsumme **SHA-256** des Pakets wird geprüft, **bevor** eine einzige Datei angefasst wird; jede Datei des Archivs wird
  geprüft (kein Pfad, der aus der Website hinausführt, keine symbolische Verknüpfung). Die Ordner `config/` und
  `install/` werden nie neu geschrieben, wenn sie bereits existieren.

- **Der Katalog des Marketplace war von keiner Website aus erreichbar.** Der Server, der ihn verteilt, antwortete
  `{"redirect":"/fr/…"}` **mit dem Statuscode 200** statt der statischen Datei — ein als JSON völlig gültiger Inhalt, und
  völlig falsch, also ein stiller Fehlschlag, der jede Website auf ihren lokalen Katalog zurückfallen ließ. Die Ursache
  war die Konfiguration dieses Servers, inzwischen behoben: Auf den Websites ist nichts zu tun. Im Code prüft das Update
  des Kerns jetzt die Form seiner Manifeste, nicht mehr nur, ob sie sich als JSON lesen lassen.

- **Die Schaltfläche „Löschen“ eines Themes** in „Themes & Addons“ schützte nur das Theme der Verwaltung, bei seinem fest
  eingetragenen Namen. Sie folgt jetzt der Angabe des Themes und schützt daher auch Nebula.

- **Kopplungen vom Kern zu optionalen Modulen.** Sieben Module fragten die Tabellen optionaler Module ab: fünf des Kerns
  (`admin`, `moderation`, `settings`, `statistics`, `user`), dazu `games` und `teams`. Zwei Abfragen waren ungeschützt: In
  `teams` stand die Existenzprüfung für `recruits` **nach** der Abfrage, die sie schützen sollte, und in `games` fragte
  das Löschen eines Spiels die Tabelle der Teams ab, ohne zu prüfen, ob sie existiert. Die anderen waren geschützt, aber
  stumm: Sie tragen jetzt eine von der CI geprüfte Annotation. Der einzige harte Zyklus des Pakets (`games` ↔ `teams`)
  ist aufgebrochen.

- **Live-Editor: Änderungen erscheinen, ohne die Seite neu zu laden.** Das Löschen eines Widgets ließ es auf dem
  Bildschirm, und das Hinzufügen einer Reihe zeigte sie erst nach dem Neuladen der Seite, obwohl der Server die Änderung
  gespeichert hatte. Das Skript erwartete ein anderes Antwortformat als das, das der Server schickt; der Fehler stammte
  von der Entfernung von jQuery.
- **Live-Editor: Das Verschieben von Widgets per Drag-and-Drop funktioniert wieder.** Mit eingeschaltetem
  Spaltenmodus blieb ein hinzugefügtes Widget am unteren Ende seiner Spalte hängen und ließ sich nicht über den Inhalt
  der Seite verschieben. Reihen und Spalten waren nicht betroffen.
- **Live-Editor: Widgets ohne Einstellungen lassen sich wieder hinzufügen.** Zehn Widgets ohne Einstellungsbildschirm
  (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`, `teams`) ließen
  sich nicht hinzufügen: Das Hinzufügen schlug mit einem Fehler 404 ohne nützliche Meldung fehl.
- **Ein Serverfehler wird nicht mehr als Inhalt angezeigt.** Wenn eine ohne Neuladen der Seite ausgeführte Aktion vom
  Server einen Fehler erhielt (zum Beispiel eine 404-Seite), fügten manche Bildschirme diese Fehlerseite in die
  Oberfläche ein. Der Fehler wird jetzt als Fehlschlag behandelt.
- **Live-Editor: Das Einstellungsformular entspricht immer dem gewählten Widget.** Bei schnellem Wechsel von Widget oder
  Typ konnte die langsamste Antwort das Formular eines anderen Widgets anzeigen. Die veraltete Anfrage wird jetzt
  abgebrochen, und das Formular wird nicht mehr neu geladen (und die laufende Eingabe geht nicht verloren), wenn sich
  die Auswahl nicht geändert hat.
- **Der Port der Datenbank wird jetzt berücksichtigt.** Das CMS verband sich immer auf Port 3306, welcher Port auch
  immer im Installationsassistenten eingegeben wurde: Es ließ sich auf einem MySQL- oder MariaDB-Server, der anderswo
  lauscht, weder installieren noch betreiben. Der Port wird jetzt in `config/db.php` gespeichert (nur wenn er von 3306
  abweicht) und für jede Verbindung verwendet.
- **Ein aktiviertes Theme ist nicht mehr leer.** Das Aktivieren von Forge, Granite, Blockcraft oder Extend wechselte das
  Theme, ohne sein Standardlayout anzuwenden, das nur über die Schaltfläche „Standard wiederherstellen“ angewendet wurde:
  Die Website zeigte überhaupt keine Widgets. Die Aktivierung wendet jetzt das Standardlayout des Themes an, wenn es noch
  keines hat, und überschreibt nie ein angepasstes Layout.
- **Das Ändern des Standard-Themes gilt jetzt auch für Besucher, die eines gewählt hatten.** Die Theme-Auswahl der
  Fußzeile merkte sich die Wahl eines Besuchers ein Jahr lang, und diese Wahl hatte Vorrang vor dem Standard-Theme:
  Wenn der Administrator das Theme wechselte, blieben die Besucher, und auch der Administrator, auf dem alten, ohne
  Ausweg aus einem Theme ohne Auswahl. Eine vor dem letzten Wechsel des Standard-Themes getroffene Wahl wird jetzt
  vergessen; eine danach getroffene wird weiterhin respektiert.
- **Der Browser behält keine veralteten Kopien der Seiten mehr.** Ohne jede Anweisung konnte er eine alte Version einer
  Seite erneut anzeigen: Ein Theme-Wechsel zum Beispiel erschien erst nach einem erzwungenen Neuladen (Strg+F5). Die
  Seiten und Antworten der Website verbieten jetzt dieses Zwischenspeichern (`Cache-Control: no-store`); Bilder, Stile
  und Skripte, die direkt vom Webserver ausgeliefert werden, sind nicht betroffen.

### Sicherheit

- **Ein gespeicherter Wert kann keine Tags mehr in ein Formular einschleusen** (2026-09-23). Das Bearbeitungsformular
  setzte den Wert eines Felds in die Seite, indem es die Anführungszeichen maskierte, wie es eine PHP-Zeichenkette tun
  würde — was in HTML keine Wirkung hat. Ein Wert, der nicht durch das Formular selbst gegangen war (Import, Demodaten,
  ein anderes Modul), konnte daher aus seinem Feld ausbrechen und dem Verwaltungsbildschirm Code hinzufügen: Ein Zitat,
  dessen Quelle `"><img src=x>` war, bewies es. Textfelder, Textbereiche, Listen, Kontrollkästchen und Optionsknöpfe
  kodieren jetzt ihren Wert; die Darstellung eines normal eingegebenen Werts ändert sich nicht. Die Benachrichtigungen
  („Zitat geändert“ usw.) werden außerdem in einer Form an das Skript der Seite übergeben, die es nicht mehr brechen
  kann.

- **Die Sicherheitsrichtlinie gibt eine Regel für Medien an** (`media-src`). Sie hatte keine und fiel auf die allgemeine
  Regel zurück (`default-src 'self'`). Sie erlaubt jetzt Medien der Website selbst und in die Seite eingebettete Medien
  (`'self' data:`); die Adresse des Streams eines Webradios wird **nur hinzugefügt, wenn ein Stream konfiguriert ist**:
  Eine Website ohne Webradio erlaubt keine zusätzliche fremde Herkunft.

- **Elf Sicherheitshinweise zu Abhängigkeiten geschlossen** (2026-09-20): `league/commonmark` geht von **2.8.2 auf
  2.10.1** (zehn Hinweise, acht davon schwerwiegend) und `phpseclib` von **3.0.52 auf 3.0.57** (ein Hinweis mittleren
  Schweregrads).

  Neun der zehn Hinweise zu commonmark sind **Denials of Service über präpariertes Markdown** — Verarbeitung in
  quadratischer Zeit, Überschriften mit kollidierenden Ankern, doppelte Fußnoten, benachbarte Attributblöcke, tief
  verschachtelte XML-Ausgabe —, und der zehnte ist eine **XSS-Lücke**: Der Filter für die Attribute `on*` ließ sich mit
  einem Seitenvorschub U+000C umgehen. Die Bibliothek stellt das Markdown des Wikis, der Artikel und der FAQ dar, von
  Mitgliedern geschrieben. Mehrere dieser Hinweise zielen auf Erweiterungen, die das Produkt nicht einschaltet
  (Fußnoten, Attribute, Überschriften-Anker, XML-Ausgabe); das Update schließt sie alle.

  Die Darstellung ändert sich nicht, und das ist nicht nur ein Eindruck: **zwölf repräsentative Fälle** — Überschriften,
  Tabelle, Aufgabenliste, Codeblock, eingeschleustes HTML, Link `javascript:`, Zitatblock, durchgestrichener Text,
  automatischer Link, verschachtelte Listen, Bild, Entitäten und Fußnote, tiefe Verschachtelung —, von beiden Versionen
  mit der tatsächlichen Konfiguration des Produkts dargestellt, ergeben dasselbe HTML **Byte für Byte** (1.568 Byte,
  derselbe Hash).

  Hinweis: Composer **weigert sich jetzt**, 2.8.2 zu installieren, genau wegen dieser zehn Hinweise. Die Richtlinie
  musste in einem Wegwerfordner abgeschaltet werden, um den Vergleich durchzuführen.

## [1.1.0] — 2026-08-23

### Sicherheit
- **Sicherheitsprüfung des gesamten Codes**, und Behebung der schweren Lücken, die sie aufgedeckt hat:
  - **Sicherungen**: Die Sicherungsarchive (eine Kopie der Datenbank und des Ordners `config/`, der die Passwörter
    enthält) lassen sich nicht mehr über eine einfache Webadresse herunterladen, weder unter Apache noch unter nginx;
    nur die Administration (Monitoring) gibt sie heraus. Ihr Name enthält jetzt einen zufälligen, nicht zu erratenden
    Teil. Der Ordner der Protokolle (`logs/`) ist auf dieselbe Weise geschützt.
  - **Geheime Token**: Sitzungs-IDs, Formular-Token und per E-Mail versandte Links (Passwort vergessen,
    Kontobestätigung) stammen aus einem sicheren Zufallsgenerator; sie waren vorhersagbar. Diese Links laufen nach
    einer Stunde ab, und eine neue Anfrage macht die vorherige ungültig.
  - **Anmeldung**: Die Zwei-Faktor-Authentifizierung (2FA) und Sperren werden geprüft, gleich auf welchem Weg man sich
    anmeldet — Passwort, Discord-, Google- oder GitHub-Konto, Link „Passwort vergessen“ —, und nicht mehr nur mit dem
    Passwort.
  - **Links in E-Mails**: Die Adresse der Website, die in per E-Mail versandten Links, bei der Rückkehr von einer
    Anmeldung über Discord, Google oder GitHub und bei der Rückkehr von einer Stripe-Zahlung verwendet wird, ist die bei
    der Installation gespeicherte (`config/url.php`), nicht mehr die, die die Anfrage angibt: Ein Angreifer kann keinen
    Link mehr versenden lassen, der zu seiner eigenen Website führt.
  - **Administrationsseiten**: Ein präparierter Anhangsname (in einer Moderationsmeldung) oder eine gefälschte
    IP-Adresse (Header `X-Real-IP`, im Sitzungsverlauf) konnten dort Code einschleusen; sie werden jetzt geprüft und
    unschädlich gemacht. Die Begrüßungsnachricht eines Spieleservers, die von einem externen Dienst stammt, wird vor der
    Anzeige bereinigt.
  - **Administrationsaktionen**: Löschen, Aktivieren oder Deaktivieren, Schließen, Freigeben, Wiederherstellen,
    Leeren… verlangen jetzt in den 23 Modulen, denen es fehlte, ein Sicherheitstoken. Es waren einfache Links oder
    Formulare ohne Token: Ein präparierter Link, angeklickt von einem angemeldeten Administrator, genügte, um die Aktion
    auszulösen.
  - **Werkzeuge**: Die Skripte im Ordner `tools/` laufen nur über die Kommandozeile, nie über eine Webadresse; bei jeder
    Änderung prüft die Continuous Integration, dass keine PHP-Abhängigkeit eine bekannte Lücke hat (`composer audit`).
  - **Von der Sicherheitsrichtlinie (CSP) stummgeschaltete Schaltflächen**: Die strikte CSP der Website blockiert im
    HTML geschriebene Aktionen (`onclick="…"`), sodass diese Schaltflächen nichts taten: Cookie-Banner, Zeilenzahl der
    Administrationstabellen, Bestätigungen des Papierkorbs und der Revisionen, Name der angehängten Datei in den
    Nachrichten, Sammelaktionen der Forenerwähnungen, Auswahl eines Feldes per Klick, Schaltfläche des Löschfensters.
    Sie sind anders angebunden und funktionieren auch in Inhalten, die ohne Neuladen der Seite geladen werden. Die
    Auswahl der Zeilen pro Seite rief außerdem noch jQuery auf, das bereits entfernt war.
- **Strikte Content Security Policy (CSP)**: Von der Website mit einem für jede Seite eigenen Token ausgeliefert, lehnt
  sie Skripte ab, die ohne dieses Token in die Seite geschrieben sind (`unsafe-inline`), zur Laufzeit erzeugten Code
  (`unsafe-eval`) und eingebettete Objekte (`object-src 'none'`); Skripte kommen nur noch von der Website und einer
  kurzen Liste von Adressen, da TinyMCE und CodeMirror jetzt von der Website selbst bereitgestellt werden.
- **Sitzung bei der Anmeldung erneuert**: Die Sitzungs-ID ändert sich bei der Anmeldung; eine vor der Anmeldung
  untergeschobene ID gibt keinen Zugang mehr zur geöffneten Sitzung.
- **Geheimnisse in der Datenbank verschlüsselt**: Das SMTP-Passwort und der Schlüssel der
  Zwei-Faktor-Authentifizierung (2FA) sind verschlüsselt (AES-256-GCM); eine Kopie der Datenbank gibt sie nicht mehr
  im Klartext preis. Ein vorhandener Wert wird beim nächsten Speichern verschlüsselt.
- **Webhooks**: Ein Webhook kann nicht mehr auf das interne Netz des Servers zielen (private, lokale oder reservierte
  Adressen werden abgelehnt, Weiterleitungen nicht verfolgt).

### Hinzugefügt
- **CSS- und JavaScript-Dateien immer aktuell**: Jede Datei wird mit ihrem Änderungsdatum angefordert (`?v=…`), sodass
  der Browser sie neu lädt, sobald sie sich ändert — auch eine von einem Theme überschriebene oder von Hand hochgeladene
  Datei —, ohne `nf_version_css` erhöhen zu müssen (das weiterhin verwendet wird, wenn die Datei nicht gefunden wird).
- **Marketplace**: Das Fenster „Updates“ der Seite *Themes & Addons* meldet, wenn eine neue Version von NeoFrag Reborn
  verfügbar ist; das Update des Kerns selbst bleibt manuell.
- **Addon-Updates**: Dasselbe Fenster vergleicht die installierten Addons mit dem Katalog des Marketplace und
  installiert die neuen Versionen samt ihren Datenbankmigrationen.
- **Installer**: neues Erscheinungsbild — NeoFrag-Reborn-Logo, Banner „Inoffizieller Fork von NeoFrag“, Nennung von
  Michaël BILCOT und Jérémy VALENTIN (Lizenz LGPLv3) in der Fußzeile, Schriften Inter und Space Grotesk,
  Türkistöne.
- **Dateien**: ein Dateimanager (Ordnerbaum, Hochladen, Leserechte pro Datei oder pro Ordner), portiert aus HiddenCMS
  von HiddenBlob (LGPLv3). Wird mit dem Kern ausgeliefert.
- **Eigene Emojis** (Modul Emojis, mit dem Kern ausgeliefert): Die Administration fügt benannte Bilder hinzu, die
  `:name:` unter anderem in Forenbeiträgen und Signaturen, News, Seiten, Events und der Rekrutierung anzeigt.
- **Newsletter**: geplanter Versand zum gewählten Datum, in kleinen Paketen, die die geplante Aufgabe der Website (Cron)
  abarbeitet, statt alles auf einmal zu senden; Öffnungsverfolgung (Öffnungsrate pro Kampagne, gemessen über ein
  unsichtbares Bild); wiederverwendbare E-Mail-Vorlagen; Wahl der Empfänger: alle Abonnenten, nur Mitglieder oder eine
  Gruppe.
- **Events**: Ein Event kann sich täglich, wöchentlich oder monatlich wiederholen, so oft wie gewählt — jedes Datum wird
  ein eigenständiges Event. Die Teilnehmer, außer denen, die abgesagt haben, erhalten vor dem Event eine Erinnerung auf
  der Website (Benachrichtigungsglocke), standardmäßig 24 Stunden vorher; die Erinnerungen laufen über die geplante
  Aufgabe der Website (Cron).
- **Emoji-Reaktionen**: sechs Reaktionen zur Auswahl (👍❤️😂😮😢😡) statt eines einzigen Herzens, wie bei Discord oder
  Facebook, jede mit eigener Zählung; die bisherigen „Gefällt mir“ werden zu ❤️.
- **Widget „Live-Status“** (das frühere Twitch-Widget): verfolgt mehrere Kanäle gleichzeitig, auf Twitch und YouTube
  (Twitch-Zugangsdaten und ein YouTube-API-Schlüssel erforderlich), mit Spiel, Zuschauern, Titel, Vorschaubild und
  eingebettetem Player.
- **Zusammengesetzte Seiten**: Unter ihrem Text kann eine Seite Blöcke anderer Module anzeigen (neueste News, News einer
  Kategorie, neueste Artikel, beliebte Downloads), in der gewünschten Reihenfolge angeordnet und einzeln beim Bearbeiten
  der Seite eingestellt. Der Shortcode nimmt auch Einstellungen an, zum Beispiel `[block:news.latest count=2]`.
- **Theme Extend**, von Chewbaka, auf NeoFrag Reborn portiert (Bootstrap 5, Tag- und Nachtmodus), unter der Lizenz
  CC BY-NC-SA 4.0; im Marketplace erhältlich.
- **Sofortsuche**: Das Such-Widget schlägt ab dem zweiten eingegebenen Buchstaben Ergebnisse vor (News, Forum,
  Seiten).
- **Sortieren der Administrationslisten**: Zehn Listen (News, Artikel, Kleinanzeigen, Downloads, FAQ, Gästebuch, Links,
  Medien, Umfragen, Wiki) lassen sich nach dem gewählten Kriterium sortieren.
- **Animierte Avatare**: Ein als Avatar hochgeladenes animiertes GIF bleibt animiert — immer, wenn der Server Imagick
  hat, sonst solange es 250 × 250 Pixel nicht überschreitet.
- **Berechtigungen**: Das Berechtigungsraster öffnet sich in einem Fenster, ohne die Seite zu verlassen.
- **Dateimanager des Webmasters** (Monitoring, Schaltfläche „Dateien verwalten“): die Dateien der Website durchsuchen,
  sie in einem Editor (CodeMirror) bearbeiten, welche anlegen, umbenennen oder löschen. Die Ordner `config/`, `logs/`
  und `backups/` bleiben außer Reichweite; jedes Speichern behält eine Kopie `.nfbak` und landet im Audit-Protokoll.
  Jeder Schreibvorgang verlangt das **Webmaster-Passwort**, getrennt vom Kontopasswort und außerhalb der Datenbank
  aufbewahrt (festgelegt bei der Installation oder im Monitoring, Karte „Webmaster-Sicherheit“); einmal festgelegt,
  wird es auch erneut verlangt, um Sicherungen zu löschen oder zu leeren und um den Schlüssel der geplanten Aufgabe zu
  ändern.
- **Versionshinweise aus einer einzigen Quelle**: `tools/changelog-section.php` extrahiert eine Version dieses
  Änderungsprotokolls (`CHANGELOG.md`), als Markdown oder HTML; die Hinweise der GitHub-Releases werden daraus gezogen,
  sodass ihre Texte nicht mehr auseinanderlaufen können.

### Geändert
- **Installer „alles inklusive“**: Der Assistent hat jetzt vier Schritte (Voraussetzungen → Datenbank → Administrator →
  Fertig). Alle mitgelieferten Module, Widgets und Themes werden im Schritt „Datenbank“ installiert und aktiviert, und
  die Startseite zeigt die News, sobald die Installation abgeschlossen ist; der Schritt „Module“ und seine Profile
  entfallen.
- **Bootstrap 4.6.2 → 5.3.8, und jQuery wird nicht mehr geladen**: Das JavaScript der Website stützt sich auf ein
  kleines hauseigenes Hilfsmittel (`window.NF`). Neun jQuery- oder Bootstrap-4-Plugins sind ersetzt (Benachrichtigungen
  → Toasts von Bootstrap 5, selectize → Tom Select, Datumsauswahl → flatpickr, FullCalendar 3 → 6, Farb- und
  Symbolauswahl, Baumansicht und Anzeigen → hauseigener Code, Scrollleisten → natives Scrollen), und jQuery UI durch
  SortableJS. Vier übersehene Skripte riefen noch jQuery auf: Sie sind in 1.2.0 neu geschrieben.
- **Dunkelmodus**: Tabellen, Karten, Dropdown-Menüs und der Texteditor übernehmen die dunklen Farben des Themes, statt
  weiß zu bleiben, in allen Themes.
- **Widget-Einstellungen und Seitenlayouts**: als JSON gespeichert statt im Serialisierungsformat von PHP, was einen
  Weg zur Codeeinschleusung schließt; bestehende Einstellungen werden weiterhin gelesen.
- **Statistikdiagramme**: Chart.js (MIT-Lizenz) ersetzt Highstock, eine kommerzielle Bibliothek, die eine freie
  Weitergabe des CMS verhinderte.

### Behoben
- **MySQL 8**: Ein Thema im Forum eröffnen oder darauf antworten, Themen teilen oder zusammenführen, eine Unterhaltung in
  den Nachrichten beginnen, die Bilder des Sliders neu ordnen… schlugen unter MySQL 8 mit einem fatalen Fehler fehl, weil
  es die Art ablehnte, wie die Website ihre Transaktionen öffnete. Sie laufen jetzt über die dafür vorgesehenen
  mysqli-Funktionen.
- **Emojis**: Ein Emoji — oder jedes 4-Byte-Zeichen — in einem Text verursachte einen Datenbankfehler. Die Verbindung
  und alle Tabellen wechseln zu `utf8mb4` (Kollation `utf8mb4_unicode_ci`, die MySQL 8 ebenso wie MariaDB 10 kennt; die
  nur unter MariaDB 11 verfügbaren Kollationen, an denen die Installation anderswo scheiterte, entfallen).
- **`where('col', [])`** erzeugte eine leere Bedingung (→ `DELETE`/`UPDATE` auf die ganze Tabelle): Es erzeugt jetzt
  `1 = 0` (leere Menge).
- **Daten nach 2038**: Der geplante Versand eines Newsletters, die geplante Veröffentlichung einer News, eines Artikels,
  einer Galerie oder einer Seite und die Daten eines Events konnten nicht über den 19. Januar 2038 hinausgehen: Danach
  lehnte MariaDB das Speichern ab, ohne jede Meldung. Diese Daten reichen jetzt bis zum Jahr 9999; bestehende
  Installationen werden per Migration aktualisiert.
- **`/user/login` und `/user/registration` antworteten „Seite nicht gefunden“**: Die Schaltflächen „Anmelden“ und
  „Registrierung“ der Themes führen dorthin, wenn JavaScript deaktiviert ist oder der Link in einem neuen Tab geöffnet
  wird. Diese Adressen öffnen jetzt das Anmelde- oder Registrierungsfenster.
- **Nebula**: Die Schaltfläche „Registrierung“ blieb sichtbar, wenn die Registrierungen geschlossen waren, und führte zu
  einer nicht gefundenen Seite. Sie ist in diesem Fall jetzt ausgeblendet.
- **Discord-Widget**: Es nennt die wahre Ursache eines Fehlschlags (Widget auf dem Server deaktiviert, unbekannte ID,
  Netzwerk) statt einer allgemeinen Meldung, zeigt das echte Logo des Servers (aus der öffentlichen Einladung gelesen)
  und zählt die Online-Mitglieder richtig — die von Discord gelieferte Liste endet bei 100, was die Zählung großer
  Server verfälschte.
- **TeamSpeak-Widget, Baumansicht**: Es stürzte unter PHP 8 ab und fiel auf die einfache Karte zurück. Der Baum der
  Kanäle und Clients wird jetzt vom Widget selbst gezeichnet, mit Font-Awesome-Symbolen (kein Symbolpaket mehr nötig);
  ein Netzwerkfehler zeigt eine allgemeine Meldung, ohne Adresse oder Port des Servers preiszugeben.
- **Widgets Discord, Twitch und Spieleserver**: Sie zeigten „nicht erreichbar“, selbst wenn der Dienst antwortete (die
  Antwort wurde zweimal dekodiert). Die Anfragen der Website senden außerdem eine Browserkennung (User-Agent): Ohne sie
  antworteten Dienste hinter Cloudflare mit einer Ablehnung (Fehler 403).
- **Sicherungen und Monitoring**: Das Anlegen einer Sicherung stürzte auf Servern mit PHP-FPM ab und schlug unter nginx
  oder Plesk fehl, die auf Adressen mit `.json` „Seite nicht gefunden“ antworteten; das Löschen oder Herunterladen
  einer Sicherung führte zu einer nicht gefundenen Seite; der Baum des Monitorings zeigte beim ersten Aktualisieren
  einen Fehler. Alles behoben.
- **Marketplace nicht erreichbar**: Standard-URL auf **ohne www** umgestellt (`https://neofrag-reborn.xyz/marketplace`).
- **Hochladen mehrerer Dateien**: Die erste Datei eines Mehrfach-Uploads ließ den Upload fehlschlagen („Seite nicht
  gefunden“) — das blockierte das Hochladen im Modul Dateien. Für Entwickler: `uploaded_file()` hielt den Index `0`
  für einen fehlenden Index.
- **Webhooks**: Die Registrierung eines Mitglieds (`user.registered`), ein neuer Kommentar (`comment.created`) und ein
  neues Forenthema (`forum.topic`) lösen endlich ihren Webhook aus; diese Ereignisse wurden angeboten, aber nie
  gesendet.

### Dokumentation
- **Dokumentation** (Wiki-Anleitungen): für Entwickler die Formulare (`form()` und `form2()`) und die Validierung der
  Einstellungen eines Widgets; für Administratoren die Fehlerbehebung bei der Installation, ein Beispiel für das
  Anlegen einer Rolle und was zu tun ist, wenn der Marketplace nicht erreichbar ist. Ein Hinweis erklärt, wie man nginx
  oder Plesk einstellt, die auf Adressen mit `.json` „Seite nicht gefunden“ antworteten.

---

## [1.0.0] — 2026-06-06 · Reborn-Fundament (auf Basis von Alpha 0.2.4)

Erster Zyklus des Forks: Modernisierung des Fundaments, Härtung der Sicherheit und eine breite Welle neuer
Funktionen.

### Hinzugefügt

**Plattform & Werkzeuge**
- **Datenbankmigrationen** mit Versionsnummern (`tools/migrate.php`, mit einem Befehl `baseline`, um eine bestehende
  Datenbank zu übernehmen) und erste automatisierte Tests (PHPUnit).
- **Neuer Web-Installer**, der den des ursprünglichen NeoFrag ersetzt: ein Assistent in fünf Schritten
  (Voraussetzungen → Datenbank → Module → Administrator → Fertig). Der Schritt „Module“ bot drei Website-Profile an
  (Community, Gaming / E-Sport, einfache Website) und konnte Addons aus dem Marketplace hinzufügen.
- **Entfernter Marketplace** (Katalog und Archive von neofrag-reborn.xyz ausgeliefert): Der Installer (Schritt „Module“)
  und die Administration (Schaltfläche „Marketplace“ der Seite *Themes & Addons*) fügen mit einem Klick die Addons des
  offiziellen Katalogs hinzu, die nicht mit der Website ausgeliefert werden. Sicherheit: HTTPS Pflicht, SHA-256-Prüfsumme
  geprüft, Archiv Eintrag für Eintrag kontrolliert (kein Pfad, der den Ordner des Addons verlässt, kein symbolischer
  Link), feste Katalogadresse, begrenzte Größen und Zeitlimits.
- **Werkzeuge zum Paketbau**: `package-addons` erstellt den Katalog des Marketplace, `build-release` die per FTP
  hochzuladenden Archive.
- **Wartungsskript** (`tools/maintenance.php`, von einer geplanten Aufgabe auszuführen): leert den Papierkorb von allem,
  was dort seit mehr als 30 Tagen liegt (einstellbar), und löscht nie bestätigte Konten nach 7 Tagen, wenn die
  Bestätigung der Registrierungen aktiviert ist — nie einen Administrator.

**Themes & Oberfläche**
- **Vier für NeoFrag Reborn geschaffene Themes**: Nebula, für Community-Websites gedacht, Forge, Blockcraft und
  Granite. Gemeinsame Farbvariablen (`--nf-*`) kleiden Module und Widgets in die Farben jedes Themes.
- **Vollständige Neugestaltung der Administration**: neues Erscheinungsbild, Seitenleiste, Befehlspalette (Strg+K),
  Hell- und Dunkelmodus.
- Auswahl von **Theme** und **Sprache** in der Fußzeile (auch für Besucher).
- **Menüs**: mehrstufiger Menü-Baukasten, nutzbar im Widget Navigation (Option „Verwaltetes Menü“).

**Inhalte**
- **News und Artikel**: geplante Veröffentlichung — der Inhalt erscheint zur gewählten Zeit, und Benachrichtigungen,
  Webhooks und Punkte gehen erst in diesem Moment hinaus, nicht mehr beim Speichern (eine Adresse, die eine geplante
  Aufgabe aufruft, durch einen Schlüssel geschützt); Sammelaktionen; Suche, Filter und Seitenumbruch in der
  Administration; eine eigene Seite zum Lesen einer News. Artikel erhalten ein Titelbild, wie es News bereits hatten,
  und der Aufrufzähler der News, der nie stieg, zählt endlich.
- **Geplante Veröffentlichung** auch für Seiten, Galerien und Events (bei Events ist das Erscheinungsdatum vom Datum
  der Durchführung getrennt).
- **Wiki**: dient als Dokumentation auf der Website (Inhaltsverzeichnis als Karten, verlässliche Markdown-Darstellung);
  Suche und Seitenumbruch; visueller Zeile-für-Zeile-Vergleich zweier Revisionen einer Seite.
- **Medien**: bearbeitbarer Titel und Beschreibung für jede Datei, Suche, Filter nach Typ und Seitenumbruch. Suche,
  Filter und Seitenumbruch auch in der Administration von Downloads, Linkverzeichnis, FAQ, Umfragen und Gästebuch.
- **RSS-2.0**-Feed (News + Artikel), Schaltflächen zum **Teilen** in sozialen Netzwerken, **SEO** (Meta-Description,
  Canonical, Open Graph, Twitter Card, Meta-Angaben pro Seite).

**Community & Engagement**
- **Benachrichtigungszentrale** auf der Website (Glocke und Zahl der ungelesenen), mit **Abonnements** (einem Inhalt
  oder einer Kategorie folgen). Die Benachrichtigungen melden Kommentare, Antworten im Forum, @Benutzername-Erwähnungen,
  private Nachrichten und Einladungen zu einem Event.
- **Reaktionen** „Gefällt mir“ auf News, Artikel, Kommentare und Forenbeiträge; **Revisionen** der Inhalte (Verlauf
  und Wiederherstellung); Widget „Neueste Kommentare“.
- Gemeinsamer **Papierkorb**: Gelöschte News, Artikel, Galerien, Kommentare und Forenbeiträge landen dort und können
  wiederhergestellt werden.
- **Forum**: Kategorie für VIP-Mitglieder reserviert, Bild pro Kategorie.

**Gaming**
- **Events**: vollständige Verwaltung der Gegner (Liste, Bearbeiten, Löschen; das ursprüngliche NeoFrag erlaubte nur,
  einen aus einem Match heraus hinzuzufügen), Live-Countdown, Einladungsbenachrichtigung.

**Monetarisierung & Gamification**
- Karma/Ruf → **Punkte** (einstellbare Skala) → **Shop** (Zahlung in Punkten) → **VIP**-Status.
- **Stripe** (Kauf von Punkten und VIP-Paketen), **Werbeverwaltung** (für VIP-Mitglieder ausgeblendet), **Spenden**
  (Modul und Widget, portiert aus „Donation v3“ von HiddenBlob, nach majiid; LGPLv3).

**Verbindungen zwischen Modulen**
- **Statistiken** auf 19 Module erweitert (das ursprüngliche NeoFrag deckte 3 ab: Kommentare, Forum, Mitglieder);
  **Aktivitätswand** im Profil, die die Aktivität eines Mitglieds über alle Module zusammenführt; Dashboard
  **„Zu erledigen“** in der Administration; **Seitenblöcke**: Der Shortcode `[block:…]` fügt einen Block eines Moduls
  in eine Seite ein (News, Artikel, Downloads).

**Webhooks & Audit**
- Signierte (HMAC) ausgehende **Webhooks**, aus der Administration verwaltet. Das **Audit-Protokoll** erfasst auch die
  Installation und Deinstallation von Addons, die Aktivierung eines Themes oder Moduls und das Speichern von
  Einstellungen.

**Kontenverwaltung**
- **Mitglieder**: Export der Mitglieder als CSV oder JSON (DSGVO); Moderatoren mit dem entsprechenden Recht stellen die
  Moderation aus ihrem Mitgliederbereich ein. Das Bearbeiten und Löschen eines Mitglieds aus der Administration, schon
  im ursprünglichen NeoFrag vorhanden, ist wieder an die neue Mitgliederliste angebunden.

### Geändert
- Versionierung in den Abhängigkeiten der Addons auf **reines SemVer** vereinheitlicht (Wegfall der Bezeichnungen
  „Alpha“).
- **Seiten** gehört jetzt zum Kern; **News**, **Forum** und **Galerien** lassen sich deinstallieren.
- **Rich-Text-Editor**: Er funktioniert endlich — das HTML, das er erzeugt, wird bereinigt, statt als Text angezeigt zu
  werden.

### Entfernt
- Die beiden Themes des ursprünglichen NeoFrag, „Thème par défaut“ (`default`) und Azuro, ersetzt durch die Themes von
  NeoFrag Reborn.
- Der BBCode-Editor des ursprünglichen NeoFrag (WysiBB) und die BBCode-Umwandlung: Der Rich-Text-Editor ersetzt ihn,
  und ein in BBCode geschriebener Text wird jetzt unverändert angezeigt.

### Behoben
- **Zehn Fehler behoben**, darunter: Events ohne Teilnehmer oder Match erschienen nicht; das Bearbeiten oder Löschen
  eines Spielmodus schlug fehl; das Löschen eines Rekrutierungsangebots schlug fehl; der Link zu einem Partner führte
  zu einer nicht gefundenen Seite (er führt jetzt zu dessen Website); der Verfasser einer Meldung wurde nie gezeigt,
  nicht einmal berechtigten Moderatoren; die Widgets Galerien und Forum prüften ihre Einstellungen oder die Rechte
  falsch. Die Aufrufzähler zählen jetzt nur einmal pro Besuch und ignorieren Bots.
- Ein geplanter Inhalt ist über seine direkte Adresse nicht mehr vor seiner Zeit sichtbar. Wirkungslose Schaltflächen
  funktionieren jetzt: Zeiträume der Statistiken, Aktivierung der E-Mail-Vorlagen, soziale Netzwerke (alle sechs werden
  angezeigt), Schaltflächen „Hinzufügen“ bei News, Seiten und Galerien.
- **Themes**: Kontrast der Schaltflächen auf WCAG AA angehoben, Anmeldefelder mit ihrem Symbol verbunden,
  Ausrichtungen korrigiert.
- **Kleine und große Bildschirme** (geprüft von 390 bis 2560 Pixel Breite): in der Administration ausklappbares
  Seitenmenü und Inhalt in voller Breite auf dem Telefon; Mitgliederprofil; kompakte Kontoleiste in Nebula, Forge,
  Blockcraft und Granite.
- Die Administrationsseiten Mitglieder, Statistiken und Live-Editor zeigten einen Fehler 500; einige Zähler blieben bei
  1 stehen (Moderationsabzeichen, Abonnenten des Newsletters, Statistiken der Medien), und eine leere Kategorie von
  Downloads, FAQ oder Links ließ sich nicht löschen. (Für Entwickler: Fehler der strikten Typisierung in `timetostr()`
  und den Helfern; `(int)` auf `row(FALSE)` eines `COUNT`/`SUM` ergab immer 1.)

### Sicherheit
- Von Mitgliedern gesendetes HTML wird serverseitig bereinigt (HTMLPurifier) gegen Codeeinschleusung; eine hochgeladene
  Datei wird anhand ihres tatsächlichen Inhalts geprüft, nicht nur anhand ihrer Endung; HSTS aktiviert (Browser fallen
  nicht mehr auf HTTP zurück).
- **Anti-CSRF-Token** in konstanter Zeit verglichen. **IP-Adresse**: Die Ratenbegrenzung und die IP-Sperrliste vertrauen
  fälschbaren Headern (`X-Forwarded-For`…) nicht mehr, es sei denn, ein vertrauenswürdiger Proxy ist angegeben.
  **Berechtigungen**: Rechteprüfungen hinzugefügt, wo sie fehlten (Gästebuch, Spiele, Galerien, Events, Statistiken).
- Alte Upgrade-Skripte können beim Wiedereinlesen der Einstellungen keine beliebigen PHP-Objekte mehr erzeugen
  (`unserialize` durch `allowed_classes` eingeschränkt).
- **Sitzungs-Fingerabdruck** gegen Sitzungsübernahme: Ein angemeldetes Mitglied wird abgemeldet, wenn sich sein Browser
  (User-Agent) während der Sitzung deutlich ändert — ein bloßes Browser-Update genügt dafür nicht. Die Adresse der
  geplanten Veröffentlichung ist durch einen in konstanter Zeit verglichenen Schlüssel geschützt.
