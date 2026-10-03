# IP-Symcon-Laufzeitmatrix

Diese Matrix definiert die Laufzeitabnahme für JSLive auf der jeweils über den
Symcon-MCP erreichbaren IP-Symcon-9-Testebene mit PHP 8.5. Sie ergänzt die
lokalen Stubs und Vertragstests; ein grüner CI-Lauf ersetzt keine Ausführung in
der echten Symcon-Laufzeit.

Die Zielplattform bleibt IP-Symcon 9.0/9.1. Aufgrund der für JSLive
maßgeblichen Kompatibilität innerhalb dieser Produktlinie wird keine separate
9.0-Installation vorgehalten. Verbindlich ist die aktuelle MCP-Testebene. Nach
der vollständigen Abnahme des `IPSModuleStrict`-Migrationsstands deklariert
`library.json` IP-Symcon 9.0 als Mindestversion.

## Plattformgrundlage

IP-Symcon 9.0 hat PHP 8.5 eingeführt und verwaltet Datenflussverbindungen über
die Verwaltungskonsole. Seit IP-Symcon 8.1 steht außerdem `IPSModuleStrict`
bereit; der aktuelle JSLive-Migrationsstand verwendet diese Basisklasse, die
automatische Parent-Kompatibilität und die native Hook-API. Die folgenden
offiziellen Seiten sind die maßgeblichen externen Referenzen:

- [Migration von 8.1 auf 9.0](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v81-v90-q1-2026/)
- [PHP-Modul-SDK und IPSModuleStrict](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
- [WebHook Control](https://www.symcon.de/de/service/dokumentation/modulreferenz/core-instances/webhook-control/)

## Verbindliche Testebene

| ID | Ausgangspunkt | Ziel und Umfang | Status |
| --- | --- | --- | --- |
| `MCP-CURRENT` | aktuelle, über den Symcon-MCP erreichbare IP-Symcon-9-Installation mit isoliertem synthetischem JSLive-Testbaum | den installierten Prüfcommit mit allen 10 Modultypen nach Bibliotheksupdate, zweimaligem ApplyChanges, Browserzugriff und Dienstneustart prüfen | `PASS` für JSLive 0.65 am 01.10.2026 |

Die Testebene bleibt für folgende Prüfcommits bestehen. Bibliotheksupdates
werden gegen die vorhandenen Instanzen geprüft; Änderungen an Anlage- oder
Löschpfaden erfordern zusätzlich eine neu angelegte beziehungsweise entfernte
Testinstanz des betroffenen Modultyps. ConfigStore, SyncModule und Calendar
bleiben entfernt; ihre früheren Modul-IDs werden nicht wiederverwendet.

Der erste dokumentierte PASS vom 01.10.2026 ist die Vorher-Baseline auf JSLive
0.61. Der anschließend installierte `IPSModuleStrict`-Migrationsstand wurde mit
JSLive 0.65 erneut vollständig geprüft und nach einem Dienstneustart bestätigt.

## Nachweis je Durchlauf

Für jeden Durchlauf werden folgende Felder ohne private Objekt- oder
Installationsdaten protokolliert:

| Evidence field | Inhalt |
| --- | --- |
| IP-Symcon version and build | exakte Version und Kernel-Build beziehungsweise Revision |
| PHP version | durch die Symcon-Laufzeit gemeldete PHP-Version |
| JSLive commit | vollständiger geprüfter Git-Commit; der Metadaten-Bot-Commit wird ebenfalls notiert |
| Plattform | Betriebssystem beziehungsweise SymBox/Docker und Architektur |
| Test plane | Kennung der verwendeten MCP-Testebene |
| Update path | vorherige und installierte JSLive-Version beziehungsweise Neuinstallation |
| Browser/Client | Verwaltungskonsole, Browser und gegebenenfalls IPSView-Version |
| ApplyChanges twice | Ergebnis der zweimaligen unveränderten Anwendung |
| Service restart | Ergebnis nach vollständigem Neustart des IP-Symcon-Dienstes |
| Message log | fehlerfrei oder Liste reproduzierbarer Warnungen/Fehler ohne private Daten |
| Ergebnis | `PASS`, `FAIL` oder `BLOCKED` mit Verweis auf den betroffenen Prüfpunkt |

Screenshots, Exportdateien und Logs dürfen keine Kennwörter, privaten
Objekt-IDs, internen Adressen oder realen Messdaten enthalten.

## Isolierte Testdaten

Die Tests laufen nicht gegen produktive Objekte. In einer eigenen Kategorie
werden synthetisch angelegt:

- je eine Bool-, Integer-, Float- und Stringvariable;
- beschreibbare Varianten mit einer kontrollierten Testaktion;
- mindestens eine archivierte numerische Variable mit reproduzierbaren
  historischen Werten über mehrere Tage;
- ein Testskript, das ausschließlich synthetische Parameter zurückgibt;
- ein Textmedium, ein Link auf eine Testvariable und eine Kategorie mit
  direkten Testkindern;
- ein kleines lokales SVG und je ein lokales PNG/JPEG/GIF für Progressbar;
- eine zweite kompatible Modulinstanz für die Konfigurationsübernahme.

Nach jedem Durchlauf werden temporär geänderte Properties und Werte auf ihren
Ausgangszustand zurückgesetzt. Es werden keine realen Skripte ausgeführt und
keine produktiven Variablen, Medien oder Kalenderquellen verwendet.

## Gemeinsame Laufzeitprüfungen

Diese Prüfungen gelten für jede instanziierbare Modulklasse:

1. Bibliothek laden beziehungsweise aktualisieren; Module Control und
   Meldungsprotokoll enthalten keinen Fatal Error.
2. Instanz ohne vorbereitete Abhängigkeiten anlegen. Sie muss erzeugt werden
   können und fehlende Voraussetzungen kontrolliert anzeigen.
3. Bei einem Kindmodul die automatisch angebotene Splitterverbindung auswählen
   und Data-ID sowie Parent-Verbindung kontrollieren.
4. Konfigurationsformular öffnen, alle Bereiche aufklappen, synthetische
   Minimalwerte setzen und übernehmen.
5. ApplyChanges twice: dieselbe Konfiguration zweimal unverändert anwenden.
   Es dürfen keine doppelten Variablen, Profile, Hooks oder Meldungen entstehen.
6. Öffentliche Variablen, Idents, Actions und vorhandene Werte mit dem
   Vertrags-Snapshot unter `tests/fixtures/public-contracts.json` vergleichen.
7. HTML-/IPSView-Ausgabe öffnen und die Browserkonsole auf JavaScript- und
   Netzwerkfehler prüfen.
8. Service restart: IP-Symcon vollständig neu starten, Formular und Ausgabe
   erneut öffnen und Datenfluss sowie Cache erneut prüfen.
9. Message log: Während Anlage, ApplyChanges, Browserzugriff und Neustart dürfen
   keine ungeklärten PHP-8.5-Warnungen, TypeErrors oder Endlosschleifen auftreten.
10. Instanz löschen und prüfen, dass Hook-, Meldungs- und Parent-Zuordnungen
    ohne verwaiste, von JSLive verwaltete Objekte bereinigt werden.

## Modulmatrix

| Modul | Minimale Laufzeitabnahme | Besondere Grenze |
| --- | --- | --- |
| `SymconJSLive` | zufälliges nicht leeres Kennwort, Formular, Hook-Registrierung, lokale und vollständige Links, statische Assets, `getGlobalConfig`, Cache, Kompression und WebSocket-Aktualisierung prüfen | Hook-Pfad, Kennwort-Queryparameter und Antwortverträge müssen unverändert bleiben |
| `SymconJSLiveAdvTextfield` | Variable `Content`, Standardtemplate, HTMLBox/IPSView, Lesen und Schreiben eines synthetischen Textes sowie Konfigurationsübernahme prüfen | geschrieben werden darf nur die eigene `Content`-Variable |
| `SymconJSLiveChart` | archivierte Floatvariable, mehrere Zeiträume, Offset, Achsen, `getData`/`getUpdate`, Periodenvariablen und Browserdarstellung prüfen | Archiv-, Zeitzonen- und absolute Jahresgrenzen mit festen Testdaten prüfen |
| `SymconJSLiveColorPicker` | mindestens zwei konfigurierte Farbvariablen, Lesen, erlaubtes Schreiben über Variablenaktion und Ablehnung einer fremden ID prüfen | lokale iro.js 5.5.0 und mindestens eine lokal ausgelieferte Schrift ohne externen Netzwerkzugriff prüfen |
| `SymconJSLiveCustom` | Variable, Read-only-Dataset, Kategorie, Link, Testskript, Textmedium, lokale JS/CSS-Datei und Standardtemplate prüfen | keine Ziele außerhalb der Datasets; Read-only muss Schreiben, Medienänderung und Skriptausführung verhindern |
| `SymconJSLiveDateTimePicker` | konfigurierte Integer-Zeitvariable mit und ohne Aktion, Zeitzone, Lesen/Schreiben und Ablehnung einer fremden ID prüfen | Unix-Zeitwert und lokale Zeitzone dürfen nach Neustart nicht abweichen |
| `SymconJSLiveDoughnutPie` | mehrere numerische Variablen, Doughnut- und Pie-Modus, Farben, Legende, `getData`/`getUpdate` und Browserdarstellung prüfen | bekannte Dataset-Auswahlabweichung separat reproduzieren und nicht beiläufig korrigieren |
| `SymconJSLiveGauge` | numerische Variable, radialer und linearer Modus, Min/Max, Präzision, Highlights und Live-Aktualisierung prüfen | Canvas-Gauge-Asset muss lokal und ohne externen Dienst laden |
| `SymconJSLiveProgressbar` | numerische Variable, mindestens zwei Presets, eigenes SVG, alle vier erlaubten Füllbildtypen und Live-Aktualisierung prüfen | dynamischer Bildtyp muss auf der MIME-Allowlist bleiben; fremde Typen müssen binär zurückfallen |
| `SymconJSLiveRadarChart` | archivierte Variable, mehrere Datensätze, absolute/relative Perioden, Offset, Skala und Browserdarstellung prüfen | Archiv-, Zeitzonen- und absolute Jahresgrenzen mit festen Testdaten prüfen |

## Webhook- und Browser-Gate

Pro Prüfcommit werden auf `MCP-CURRENT` mindestens diese End-to-End-Pfade
geprüft:

- Standardausgabe `getContend` mit richtigem und falschem Kennwort;
- öffentliche statische Assets;
- JSON-, HTML-, CSS-, JavaScript-, SVG-, Text- und Bildantworten samt
  `nosniff`, Cache- und Content-Type-Headern;
- ETag und `If-Modified-Since` mit HTTP 304 ohne Body;
- `setData` per bestehendem GET-Vertrag für AdvTextfield, ColorPicker,
  DateTimePicker und Custom einschließlich negativer Zieltests;
- Konfigurationsexport mit normalem Namen, Umlaut und synthetischem
  Sonderzeichen-Namen;
- WebSocket-Aktualisierung sowie Pull-Modus mit dem konfigurierten Intervall;
- HTMLBox und IPSView mit relativer sowie vollständiger Splitteradresse.

Das detaillierte Zugriffsmodell steht in
[`WEBHOOK_SECURITY_MODEL.md`](WEBHOOK_SECURITY_MODEL.md). Eine Änderung von
CORS, Kennworttransport oder HTTP-Methode gehört nicht in diese Baseline.

## Update-Gate

Für Bibliotheksupdates auf `MCP-CURRENT` gelten zusätzlich:

- alle bestehenden Instanz-IDs und Parent-Verbindungen bleiben erhalten;
- Properties, JSON-Listen, TemplateScriptIDs und Variablen-Idents bleiben
  unverändert;
- Werte und Archivzuordnung der Testvariablen bleiben erhalten;
- vorhandene `Output`-/`IPSView`-Variablen werden weder dupliziert noch
  ungefragt gelöscht;
- eigene Templates, lokale Bibliotheken und eingebettete Medien bleiben
  referenziert;
- zweimaliges ApplyChanges nach dem Upgrade bleibt idempotent;
- ein anschließender Service restart erzeugt keine neue Migration und keinen
  erneuten Seiteneffekt.

## Abnahme vom 01.10.2026

| Evidence field | Ergebnis |
| --- | --- |
| IP-Symcon version and build | 9.1, Revision `rust-dab58090190ab6ce72c9c1d036c2e935edff313f` |
| PHP version | 8.5.8, SAPI `Symcon`, 64 Bit |
| JSLive commit | Quellcommit `766b68bff55ac38b245ab5e8785a91d351f1808c`; Metadatencommit `573f18de409b6e46441d2e5b05dfa7a237f396dd` |
| Plattform | Windows, amd64 |
| Test plane | `MCP-CURRENT` mit isoliertem synthetischem JSLive-Testbaum |
| Update path | JSLive 0.60 auf 0.61; installierte Produktivdateien inhaltlich identisch zum lokalen Prüfstand |
| Browser/Client | Microsoft Edge 155.0.4283.18, automatisierter Headless-Lauf |
| ApplyChanges twice | alle vorhandenen Instanzen der 10 Modultypen zweimal unverändert erfolgreich angewendet; Status anschließend aktiv, keine doppelten Variablen oder Hooks |
| Service restart | Kernel-Startzeit änderte sich nach vollständigem Dienstneustart; Instanzen, Formulare, Hook und Browserausgaben anschließend erneut erfolgreich geprüft |
| Message log | keine JSLive-Warnung und kein JSLive-Fehler während ApplyChanges, Browserprüfung oder Neustart |
| Ergebnis | `PASS` |

Alle neun Visualisierungsmodule lieferten nach dem Neustart HTTP 200, gültige
modulspezifische DOM-Strukturen und jeweils eine offene WebSocket-Verbindung
ohne JavaScript-, Seiten-, Netzwerk- oder Socketfehler. Eine synthetische
Variablenänderung und ihre Rücksetzung wurden als zwei WebSocket-Frames
empfangen. Im vorübergehend aktivierten Pull-Modus wurden bei drei Sekunden
Intervall vier erfolgreiche Datenabrufe in acht Sekunden beobachtet; Modus und
Testwert wurden danach auf ihren Ausgangszustand zurückgesetzt. Die einzige
beobachtete HTTP-404-Konsolenmeldung betraf das optionale `/favicon.ico` und
nicht JSLive.

## Abnahme des IPSModuleStrict-Stands vom 01.10.2026

| Evidence field | Ergebnis |
| --- | --- |
| IP-Symcon version and build | 9.1, Revision `rust-dab58090190ab6ce72c9c1d036c2e935edff313f` |
| PHP version | 8.5.8, SAPI `Symcon`, 64 Bit |
| JSLive commit | Quellcommit `263f90df89939efb2428c9d0117c7b01b21d332d`; Metadatencommit `7c5a15aa648b67b8e22441cb8f6cf4e37744a4ea` |
| Plattform | Windows, amd64 |
| Test plane | `MCP-CURRENT` mit isoliertem synthetischem JSLive-Testbaum |
| Update path | JSLive 0.64 auf 0.65; installierte Metadaten- und Produktivdateien inhaltlich identisch zum lokalen Prüfstand |
| Browser/Client | Microsoft Edge 155.0.4283.18, automatisierter Headless-Lauf |
| ApplyChanges twice | alle zwölf vorhandenen Instanzen der zehn Modultypen zweimal unverändert erfolgreich angewendet; Status anschließend aktiv, Zustands-Snapshot unverändert |
| Service restart | Kernel-Neustart nach Installation von JSLive 0.65 bestätigt; alle Instanzen, Formulare, nativen Hook-Routen, HTTP-Ausgaben und WebSocket-Verbindungen anschließend ohne zusätzliches ApplyChanges funktionsfähig |
| Message log | keine JSLive-Warnung und kein JSLive-Fehler während ApplyChanges, Browserprüfung, WebSocket-/Pull-Test oder Neustart |
| Ergebnis | `PASS` |

Die vorhandenen Instanzen behielten ihre Parent-Verbindungen, öffentlichen
Variablen, Profile, Actions und Werte. Eine temporär neu angelegte
AdvTextfield-Instanz bestätigte zusätzlich den Fresh-Create-Pfad und wurde
anschließend vollständig entfernt. Alle neun Browseransichten lieferten HTTP
200 und ihre erwartete DOM-Struktur ohne erkannten JavaScript- oder
Netzwerkfehler. Zwei synthetische Wertänderungen erzeugten zwei vollständige
WebSocket-Textframes; im Pull-Modus wurden in elf realen Sekunden fünf
`getData`-Abrufe beobachtet. Datenmodus und Testwert wurden danach auf ihren
Ausgangszustand zurückgesetzt.

## Gezielte Frontend-Abnahme von 0.70 am 01.10.2026

Der Dienstneustart ist durch die neue Kernel-Startzeit **17:08:05 Uhr MESZ**
bestaetigt. Geprueft wurde JSLive 0.70, Quellcommit `f582a6c`,
Metadatencommit `2722afa`, auf MCP-CURRENT unter Symcon 9.1 und PHP 8.5.8.
Alle elf vorhandenen JSLive-Instanzen melden Status 102.

Die gezielte Browserpruefung mit Edge 155.0.4283.18 (Headless, 1024 x 768)
umfasste DateTimePicker, ColorPicker, Chart, Doughnut/Pie und RadarChart.
Alle fuenf Ansichten lieferten HTTP 200, renderten die erwarteten
Bedienelemente beziehungsweise Diagramme und oeffneten jeweils eine
WebSocket-Verbindung. Es traten keine JavaScript-, Seiten- oder
Ressourcenfehler auf; die Screenshots wurden visuell kontrolliert.
Ein separater clientseitiger Schrift-Ladetest zeigte die lokale Roboto-Schrift
einschliesslich Umlauten mit `FontFace.status = loaded`. Dafuer wurde keine
Symcon-Konfiguration geaendert. Seit dem Neustart wurden keine
JSLive-Warnungen oder -Fehler im Symcon-Log gefunden.

Ergebnis: Die gezielte Abnahme der Lokalisierung und ersten Asset-Bereinigung
ist bestanden. Dies ist keine erneute vollstaendige Baseline: schreibende
Bedienaktionen, zweimaliges ApplyChanges und ein Wechsel des Datenmodus wurden
in diesem Lauf nicht ausgefuehrt.

Die nachfolgende lokale Chart.js-Bereinigung wurde zusaetzlich mit denselben
drei Diagrammansichten geprueft. Dabei lieferte der isolierte Testbrowser alle
statischen Assets aus dem lokalen Arbeitsbaum; HTML und lesende Datenabfragen
kamen weiterhin aus MCP-CURRENT. Chart verwendete weiter Version 4.3.3,
Doughnut/Pie und RadarChart weiter 4.4.1. Alle Diagramme renderten ohne
JavaScript- oder Ressourcenfehler und ohne horizontalen Ueberlauf; die
Screenshots wurden visuell kontrolliert. Die entfernten Dateien wurden nicht
angefordert. Dieser Kandidatentest wurde anschliessend durch die folgende
Abnahme des installierten Stands ergaenzt.

## Gezielte Frontend-Abnahme von 0.71 am 01.10.2026

Die Chart.js-Altdateibereinigung ist auf MCP-CURRENT installiert:
Quellcommit `9df72cb`, Metadatencommit `787d24a`, Library 0.71,
Build 165638859. Tests, StylePHP und CodeQL sind fuer diesen Stand gruen.
Alle elf JSLive-Instanzen sind aktiv; es wurden keine JSLive-Warnungen oder
-Fehler gefunden. Die neun entfernten Asset-URLs liefern HTTP 404, die
weiterhin aktiven Dateien stimmen per Hash mit dem Repository ueberein.

Chart (29122), Doughnut/Pie (46757) und RadarChart (27990) wurden nach dem
Modulupdate im Browser geprueft: HTTP 200, erwartete Diagramme, WebSocket 101,
keine JavaScript- oder Ressourcenfehler beim initialen Laden. Chart verwendet
4.3.3, die beiden anderen Vorlagen 4.4.1. Ergebnis: gezielte Abnahme der
Asset-Bereinigung bestanden; kein neuer vollstaendiger Matrixdurchlauf.

## Lokaler Kandidat: Chart.js 4.5.1 am 01.10.2026

Ausgangsstand ist die abgenommene 0.71. Die offizielle npm-Distribution 4.5.1
wurde mit Paketintegritaet, Einzeldateihashes, Source Map und MIT-Lizenzen
lokal aufgenommen. Die drei Standardvorlagen verwenden den neuen versionierten
Pfad; beide alten 4.x-Dateien und alle Plugins bleiben unveraendert.

Testverfahren: Edge 155.0.4283.18, Headless, jeweils getrennte Browserkontexte
fuer bisherigen und neuen Bundle. HTML und lesende Datenabfragen stammen aus
den drei genannten MCP-CURRENT-Instanzen. Nur im Testbrowser werden statische
Assets aus dem Arbeitsbaum geliefert und die bisherige Chart.js-URL fuer den
Kandidaten auf 4.5.1 abgebildet. Datenantworten werden fuer beide Varianten
identisch im Speicher wiederverwendet. Fixierte Browserzeit und gestoppte
Animationen machen den Bildvergleich reproduzierbar. Konfigurationen, Werte
und Assets auf Symcon werden dabei nicht geaendert; `setData` ist im
Testbrowser gesperrt. Zugangsdaten und Serverantworten werden nicht als
Testdateien gespeichert.

| Pruefung | Ergebnis |
| --- | --- |
| Initiales Laden aller drei Diagramme, beide Versionen | HTTP 200, WebSocket 101, kein initialer JavaScript-/Ressourcenfehler |
| Desktop 1024 x 768, bestehende Testdaten | Alle drei Canvas-Bilder pixelgleich |
| Mobil 390 x 844 | Chart pixelgleich; Doughnut/Pie und Radar mit einem Pixel Hoehenrundungsunterschied; visuell kontrolliert, kein horizontaler Ueberlauf |
| Clientseitige Zusatzdaten bei festem Canvas 700 x 500 | Linie/Balken mit unterschiedlichen Werten, Pie statt Doughnut und Radar mit zehn unterschiedlichen Werten einschliesslich Datalabels jeweils pixelgleich |
| Legende ein-/ausblenden | Alle drei Module in beiden Versionen erfolgreich |
| Tooltip | Chart und Doughnut/Pie erfolgreich; Radar scheitert bereits mit 4.4.1 und weiterhin mit 4.5.1, siehe Bestandsfehler unten |
| Moment-Adapter / Streaming-Fork 3.1.0 | Realtime-Achse rendert; Refresh-Callback mehrfach ausgefuehrt, Zeitachse schreitet fort; clientseitiges `UpdateChart` aendert Daten unter 4.3.3 und 4.5.1 |
| Clientseitiges Doughnut-Update | `UpdateChart` uebernimmt den Testwert in beiden Versionen |
| Neuladen | Chart synchron und asynchron in beiden Versionen erfolgreich; Radar initial asynchron und erneutes synchrones Laden in beiden Versionen erfolgreich |
| Pull-Pfad des Kandidaten | Je Modul vier lesende `getData`-Abrufe; Diagrammkoordinaten weiterhin endlich; keine Symcon-Property geaendert |
| Lokale PHP-Pruefungen | Gesamtsuite, Webhook-Auslieferung einschliesslich neuem Bundle/Map, Referenzen und Hashes sowie Syntax unter PHP 8.5.10 bestanden |

Bestaetigter Bestandsfehler: `RadarChart.html::UpdateTooltipLabel` erwartet
noch ein zweites `data`-Argument und `tooltipItem.index`. Chart.js 4 liefert
stattdessen den Tooltip-Kontext. Beim Zeigen auf einen Datenpunkt entsteht
`Cannot read properties of undefined (reading 'datasets')`. Der Fehler ist
mit der unveraenderten 4.4.1 ebenso reproduziert wie mit 4.5.1. Er wird als
separate Template-Korrektur mit Regressionstest behandelt und nicht mit dem
reinen Abhaengigkeitswechsel vermischt.

Ergebnis: In den geprueften Szenarien keine funktionale Versionsregression
gefunden; responsive Rundungsabweichung wie oben dokumentiert. **Kein
uneingeschraenktes PASS**: Radar-Tooltip bleibt fehlerhaft. Ausserdem stehen
CI und die gezielte Abnahme des neuen Pfads nach Commit/Push und Modulupdate
noch aus. Fuer diesen Asset-Schritt ist kein weiterer Dienstneustart
vorausgesetzt; bei aktivem HTML-Cache Ausgaben ueber das bestehende
`ApplyChanges()` erneuern und danach im Browser neu laden.

## Gezielte Abnahme von 0.72 und separater Radar-Tooltip-Fix

Am 01.10.2026 ist JSLive 0.72, Build 88703236, auf MCP-CURRENT bestaetigt
(Quellcommit `5498104`, Metadatencommit `eed3eef`). Der zweite gemeldete
Neustart ist durch die Kernel-Startzeit **20:20:27 Uhr MESZ** belegt; beim
ersten Versuch war noch 0.71 installiert. Symcon 9.1 / PHP 8.5.8, alle elf
Instanzen aktiv. Tests, Check Style und CodeQL fuer 0.72 sind erfolgreich.

Der installierte neue Bundle und seine Source Map liefern HTTP 200 und sind
SHA-256-identisch zum Repository. Beide alten 4.x-URLs bleiben erreichbar
und inhaltlich unveraendert (lokale CRLF-/Server-LF-Zeilenenden beruecksichtigt).
Alle drei Diagramme laden im Browser ausschliesslich 4.5.1 und rendern nach
Neustart mit HTTP 200, WebSocket 101, funktionierender Legende und ohne
initiale JavaScript-/Ressourcenfehler. Chart- und Doughnut-Tooltips funktionieren.
Keine JSLive-Warnungen oder -Fehler seit dem Neustart. Der bekannte
Radar-Tooltip-Fehler wurde erneut bestaetigt; daher kein uneingeschraenktes
Gesamt-PASS trotz bestandener Bereitstellung des Versionsupdates.

Der lokale Folgeschritt korrigiert ausschliesslich den Radar-Tooltip-Callback:
Datensatzname und `formattedValue` aus dem Chart.js-4-Kontext ersetzen das
nicht mehr uebergebene zweite Argument und den unpassenden `.y`-Zugriff.
Properties, Datensaetze, PHP-Vertraege, Chart.js und Plugins bleiben unveraendert.

Nachweise fuer den noch nicht installierten Fix:

- `node tests/radar-tooltip.js` reproduzierte zuerst den urspruenglichen
  `TypeError` und besteht nach der Korrektur mit sieben Faellen. Der Test
  fuehrt den Original-Callback aus dem Template aus und prueft seine
  Registrierung in der Standardkonfiguration, Nullwerte, negative Werte,
  Dezimal-/lokalisierte Werte, numerische Strings sowie leere/fehlende Namen.
  Er ist ueber `php tests/run.php` auch in der bestehenden CI eingebunden.
- Edge 155.0.4283.18, 1024 x 768: Die unveraenderte installierte Radar-Ansicht
  reproduzierte den Fehler. Nur im isolierten Testbrowser wurde der lokale
  Callback eingesetzt. Echtes Hover auf einen Datenpunkt zeigte danach
  Datensatzname und Wert; keine JavaScript-Fehler. Weitere rein clientseitige
  Daten prueften -12.5, 0 und 24.75 mit von Chart.js erzeugten Tooltip-Kontexten.
- Keine Symcon-Konfiguration und kein Variablenwert wurden geaendert.
  Eigene Template-Skripte bleiben unangetastet. CI und gezielte Laufzeitabnahme
  nach dem Modulupdate des Fixes stehen noch aus.

## Gezielte Radar-Tooltip-Abnahme von 0.73 am 01.10.2026

Quellcommit `e30de93`, Metadatencommit `73351d1`, Library 0.73,
Build 238083731: Tests, Check Style und CodeQL sind erfolgreich.
MCP-CURRENT meldet Symcon 9.1 / PHP 8.5.8, Kernel-Start um 21:36:16 Uhr MESZ
und alle elf JSLive-Instanzen mit Status 102. Keine JSLive-Warnungen oder
-Fehler seit dem Neustart gefunden.

Der ausgelieferte Radar-Callback entspricht dem lokalen Quelltext. Echtes
Hover zeigt Datensatzname und formatierten Wert, ohne im Browser eingesetzten
Ersatzcode. HTTP 200, WebSocket 101 und keine JavaScript-Fehler; die sieben
Callback-Regressionstests sowie Frontend-/Webhook-Pruefungen sind bestanden.
Ergebnis: Die gezielte Abnahme des Radar-Fixes ist bestanden. Dies ergaenzt
die Abnahme von 0.72; es ist kein neuer vollstaendiger Matrixdurchlauf.

## Lokaler Kandidat: Moment.js 2.31.0 am 01.10.2026

Ausgangsstand ist die abgenommene 0.73. Die drei Standardvorlagen verwenden
lokal den neuen versionierten Moment-Bundle. Der bisherige 2.27.0-Pfad,
Chart.js 4.5.1 und alle Plugins bleiben unveraendert.

Edge 155.0.4283.18, Headless, Desktop 1024 x 768, Zeitzone Europe/Berlin:
Getrennte Kontexte laden die originalen drei MCP-CURRENT-Ansichten (29122,
46757, 27990). Nur im Kandidaten wird die Moment-Assetantwort browserlokal
durch 2.31.0 ersetzt. Fixierte Browserzeit und identische im Arbeitsspeicher
wiederverwendete lesende Datenantworten sichern den Vergleich ab; Animationen
werden fuer Screenshots angehalten. `setData` ist gesperrt. Keine Symcon-Werte,
Konfigurationen oder Serverdateien werden veraendert, keine Zugangsdaten oder
API-Antworten als Testdateien gespeichert.

| Pruefung | Ergebnis |
| --- | --- |
| Alle drei Diagramme, beide Moment-Versionen | HTTP 200, WebSocket 101, keine JavaScript-/HTTP-Ressourcenfehler |
| Canvas-Bilder mit identischen Daten | Alle drei pixelgleich; Screenshots visuell kontrolliert |
| Achsen und Layout | Endliche Achsengrenzen; kein horizontaler Ueberlauf |
| Native Hover-Tooltips | Alle drei Diagramme mit beiden Versionen erfolgreich, gleiche Werte |
| Realtime-Achse | Refresh-Callback ausgefuehrt und Achse mit beiden Versionen um 500 ms fortgeschritten |
| Automatisierter echter Moment-/Chart.js-Adapter | 2.27.0 und 2.31.0 bestehen Parsing, Formatierung, ungueltige Daten, Monats-/Jahres-/ISO-Wochengrenzen und 23-/25-Stunden-Tage in UTC/Berlin |
| Lokale Abschlusspruefungen | Gesamtsuite, Syntax aller 57 PHP-Dateien unter PHP 8.5.10, JSON-Validierung (36 Dateien), Style-Pruefung der drei geaenderten PHP-Tests und `git diff --check` bestanden |

Ergebnis: Keine Regression in den geprueften Szenarien gefunden. Browserkontexte
sind geschlossen. CI und die gezielte Abnahme des neuen Pfads nach Commit/Push
und Modulupdate stehen noch aus; der Kandidatentest ist keine installierte
Symcon-Abnahme und kein neuer vollstaendiger Matrixdurchlauf.

## Gezielte Moment-Abnahme von 0.74 am 01.10.2026

Quellcommit `0589839`, Metadatencommit `540e1de`, Library 0.74,
Build 5806137: Tests, Check Style und CodeQL sind erfolgreich. Lokal und
GitHub stimmen ueberein. Symcon 9.1 / PHP 8.5.8 meldet alle elf Instanzen aktiv;
die Kernel-Startzeit bleibt unveraendert. Fuer diesen Asset-Schritt war kein
Neustart erforderlich.

Alle drei Diagramme laden den neuen Moment-Pfad mit Version 2.31.0,
HTTP 200 und WebSocket 101. Bundle und Source Map sind SHA-256-identisch zum
Repository. Der alte 2.27.0-Pfad ist erreichbar und bis auf lokale CRLF-/Server-
LF-Zeilenenden identisch. Native Tooltips einschliesslich Datum im Chart und
die fortschreitende Realtime-Achse funktionieren; keine Browserfehler.
Die gezielte Logsuche ab dem Metadatenzeitpunkt ergab keine JSLive-Warnungen
oder -Fehler. Adapter-, Referenz- und Webhook-Tests sind frisch bestanden.
Ergebnis: gezielte Abnahme bestanden, kein neuer vollstaendiger Matrixdurchlauf.

## Lokaler Kandidat: Moment-Adapter 1.0.1 am 01.10.2026

Ausgangsstand ist 0.74. Im isolierten Edge-Testbrowser (155.0.4283.18,
1024 x 768, Europe/Berlin) wird ausschliesslich die Adapterantwort fuer den
Kandidaten durch die lokale Version 1.0.1 ersetzt. Original-HTML und lesende
Datenabfragen stammen aus MCP-CURRENT; identische Datenantworten werden fuer
beide Varianten im Speicher wiederverwendet. Fixierte Browserzeit und
angehaltene Animationen sichern den Bildvergleich ab. `setData` ist gesperrt;
keine Symcon-Konfiguration, Werte oder Serverdateien werden geaendert.

| Pruefung | Ergebnis |
| --- | --- |
| Alle drei Diagramme mit beiden Adaptern | HTTP 200, WebSocket 101, keine JavaScript-/HTTP-Ressourcenfehler, endliche Achsengrenzen, kein horizontaler Ueberlauf |
| Canvas-Bilder | Alle drei pixelgleich bei identischen Daten; Chart-Screenshot visuell kontrolliert |
| Native Tooltips | Gleiche Werte und Titel in allen drei Diagrammen, einschliesslich Chart-Datumsformat |
| Realtime-Achse | Beide Varianten schreiten um 500 ms fort; Refresh-Callback des Kandidaten ausgefuehrt |
| Automatisierte Adaptertests | Alter/neuer Adapter mit Moment 2.31.0 sowie die alte 2.27.0/1.0.0-Kombination bestehen die Kalender-, Format- und DST-Pruefungen in UTC/Berlin |
| Negativnachweis | Neuer Pfadtest scheitert zuerst an der noch alten Einbindung und besteht nach Umstellung aller drei Vorlagen |

Browserkontexte sind geschlossen, Zugangsdaten und Datenantworten nicht als
Testdateien gespeichert. Keine Regression in den geprueften Szenarien gefunden.
CI und gezielte Abnahme des neuen Pfads nach dem Modulupdate stehen noch aus.

## Adapter-Abnahme 0.75 und Chart-Ladereihenfolge am 02.10.2026

0.75, Build 193520967, Quellcommit `b88e547`, Metadatencommit `453f95d`:
lokal und GitHub synchron, Tests/Style/CodeQL gruen. Symcon 9.1 / PHP 8.5.8,
alle elf Instanzen mit Status 102, ohne erneuten Dienstneustart.
Alle drei Diagramme laden den neuen Adapter mit HTTP 200 und WebSocket 101.
Der 1.0.1-Bundle ist bytegleich zum Repository; der Altpfad mit 1.0.0 bleibt
bis auf CRLF-/LF-Zeilenenden unveraendert. Keine passenden JSLive-Warnungen
oder -Fehler ab dem Metadatenzeitpunkt im Log gefunden.

Die erste Chart-Ansicht fiel auf eine Kategorienachse zurueck, stand still und
zeigte 60 Tooltip-Eintraege mit Titel `0`. Nach Neuladen blieb sie stabil.
Die gezielte Diagnose reproduzierte die Ursache mit Adapter 1.0.0 und 1.0.1:
Wird die Antwort fuer Datensatzindex 0 im Testbrowser zurueckgehalten, erzeugt
die zuerst eintreffende Antwort fuer Index 1 ein Array mit leerem ersten Slot.
`updateChartconfig()` greift auf dessen `.type` zu, faengt den TypeError per
`alert()` ab und liefert keine Konfiguration. Der Chart wird trotzdem erzeugt;
spaeter eintreffende Daten erhalten die fehlerhaften Standardoptionen.
Dialoge muessen mitgeprueft werden: Der abgefangene Fehler erscheint nicht
als ungefangener JavaScript-Fehler. Ergebnis: Adapter-Auslieferung bestanden,
Gesamtabnahme wegen reproduziertem Bestandsfehler weiterhin eingeschraenkt.

### Lokaler Fix der asynchronen Initialisierung

Die Standardvorlage sammelt nun alle Datensatzantworten eines Ladevorgangs,
bevor sie einmalig in konfigurierter Reihenfolge gerendert werden. Leere oder
fehlgeschlagene Antworten erzeugen keine Array-Luecken. Ein fehlgeschlagener
Datensatzabruf wird ohne URL in der Konsole gemeldet; vorhandene Datensaetze
bleiben darstellbar. Properties, HTTP-Vertraege, Bibliotheken und synchrone
Ladelogik bleiben unveraendert. Ueberlappende Ladevorgaenge und Fehler der
vorgelagerten Konfigurations-/Achsenabfragen sind nicht Gegenstand dieses Fixes.

- `node tests/chart-async-loading.js` reproduzierte zuerst denselben TypeError.
  Nach dem Fix bestehen 13 Szenarien: beide Zweier-Reihenfolgen, alle sechs
  Dreier-Permutationen jeweils mit Teil-/Vollreload, leere/fehlgeschlagene erste
  Antwort, leere Variablenliste, komplett leere Daten und synchrones Laden.
  Der Test fuehrt originale Template-Funktionen mit kontrollierter HTTP-Grenze
  und einem Chart-Konfigurationsempfaenger aus; er ersetzt keinen Renderer.
- Edge 155.0.4283.18, 1024 x 768, Europe/Berlin: Der installierte Originalcode
  reproduziert bei verzoegertem Index 0 den Fehler. Fuer den Kandidaten wird
  ausschliesslich die lokale `ReloadChart`-Funktion vor dem Start browserlokal
  eingesetzt, mit den vom Server gerenderten Anfragepfaden. HTML, Assets und
  lesende Daten stammen weiterhin aus MCP-CURRENT; `setData` ist gesperrt.
- Bei verzoegertem Index 0 ebenso wie Index 1 wartet der Kandidat auf beide
  Antworten. Danach genau ein Linienchart mit korrektem Titel, geordneter Linie
  und Balken, zwei Tooltip-Eintraegen mit Datum, fortschreitender Realtime-Achse
  und WebSocket 101. Keine Dialoge oder JavaScript-Fehler. Erneutes vollstaendiges
  Laden besteht ebenfalls in beiden Kontexten; Screenshot visuell kontrolliert.

Keine Symcon-Dateien, Properties oder Variablen wurden geaendert. Browserkontexte
sind geschlossen; Zugangsdaten und Serverantworten wurden nicht als Testdateien
gespeichert. CI und gezielte installierte Abnahme des Fixes stehen noch aus.
Nach Modulupdate Ausgabe neu laden, gegebenenfalls aktiven HTML-Cache mit dem
bestehenden `ApplyChanges()` erneuern; kein Dienstneustart erforderlich.

## Gezielte Chart-Fix-Abnahme von 0.76 am 02.10.2026

Quellcommit `c48f76a`, Metadatencommit `124cf6c`, Library 0.76,
Build 206108522: lokal und GitHub synchron; Tests, Check Style und CodeQL gruen.
MCP-CURRENT bestaetigt Symcon 9.1 / PHP 8.5.8 und alle elf Instanzen mit Status
102. Die Kernel-Startzeit blieb unveraendert; kein Dienstneustart erforderlich.

Die installierte Vorlage wurde ohne Codeersetzung in Edge 155.0.4283.18,
1024 x 768, Europe/Berlin geprueft. Je ein isolierter Browserkontext hielt die
Antwort fuer Index 0 beziehungsweise Index 1 zurueck: Bis beide Antworten
eingetroffen sind, bleibt der Ladevorgang aktiv und es entsteht noch kein Chart.
Danach korrekter Titel, konfigurierte Datensatzreihenfolge, zwei Tooltip-Eintraege
mit Datum, fortschreitende Realtime-Achse und genau eine Chart-Instanz.
Vollreload besteht in beiden Faellen; WebSocket 101, keine Dialoge,
JavaScript- oder HTTP-Ressourcenfehler. Die gezielte Logsuche ab dem
Metadatenzeitpunkt fand keine JSLive-Warnungen oder -Fehler. Gesamtsuite frisch
bestanden. Ergebnis: gezielter Fix-PASS; kein neuer vollstaendiger Matrixdurchlauf.

## Lokaler Kandidat: offizielle Datalabels 2.2.0 am 02.10.2026

Ausgangsstand ist 0.76. npm `latest` und der offizielle Release bestaetigen
weiterhin 2.2.0. Der historische `.min.js`-Bestand ist jedoch der unminifizierte
Bundle mit geaendertem Copyright-Jahr und drei `constructor.name`-Pruefungen
anstelle der originalen `instanceof`-Pruefungen fuer Arc, Point und Bar.
Der neue versionierte Bundle stammt unveraendert aus dem SHA-512-geprueften
npm-Paket; Dateihashes und MIT-Lizenz sind dokumentiert. Nur die drei
Standardvorlagen wechseln den Pfad, der modifizierte Altpfad bleibt erhalten.

Isolierter Edge 155.0.4283.18, 1024 x 768, Europe/Berlin: Fuer den
Alt-/Neu-Vergleich wurden lesende HTML-/Datenantworten von MCP-CURRENT im
Speicher wiederverwendet. Browserzeit fixiert, Animationsdauer in den
Testantworten auf null gesetzt und Datalabels ausschliesslich browserlokal
eingeblendet. Nur die Plugin-Antwort unterschied die beiden Varianten;
`setData` war gesperrt, Serverdateien, Properties und Werte blieben unveraendert.

| Pruefung | Ergebnis |
| --- | --- |
| Chart, Doughnut/Pie, Radar | Pixelgleiche Canvas-Bilder und gleiche Labeltexte; sichtbare Doughnut-Labels visuell kontrolliert |
| Elementerkennung | Zusaetzliche synthetische Browserfaelle fuer Linie, positive/negative/Null-Balken, Doughnut, Pie und Radar pixelgleich; erwartete Labeltexte tatsaechlich an `fillText` uebergeben |
| Native Tooltips und Realtime | Bei normal laufender Browserzeit Tooltips in allen drei Vorlagen, Chart mit zwei Eintraegen und Datum sowie fortschreitender Zeitachse; keine Dialoge, JavaScript- oder HTTP-Ressourcenfehler |
| Neuer Pfad / Neuladen | Browserlokal ersetzte HTML-Referenz laedt den lokalen Kandidaten, registriert das Plugin und besteht erneutes Laden mit genau einer Chart-Instanz |
| WebSocket | Chart-Kandidat bei unveraendert vom Server geladener HTML-Antwort mit HTTP 101; die HTML-Interception-Laeufe erbrachten keinen WebSocket-Nachweis |
| Automatisierte Grenzen | Neue Pfadpruefung zuerst rot an der alten Einbindung; danach Referenz-, Hash-, Kompatibilitaetspfad- und Webhook-Pruefungen gruen |

Die Browserpruefungen sind gezielte lokale Kandidatennachweise, keine dauerhaft
in CI ausgefuehrte visuelle Testsuite. CI und installierte Abnahme nach dem
Modulupdate stehen noch aus. Dabei alle drei neuen Assetpfade, sichtbare Labels,
native Tooltips, WebSocket und Realtime erneut pruefen. Ein Dienstneustart ist
fuer diesen Asset-Schritt nicht erforderlich; bei aktivem HTML-Cache diesen
ueber das bestehende `ApplyChanges()` erneuern. Private Browserantworten und
Zugangsdaten werden nicht als Testdateien gespeichert.

## Installierte Abnahme 0.77: Datalabels am 02.10.2026

Quellcommit `2b3034d`, Metadatencommit `30b652a`, Library 0.77,
Build 45286221: lokal, GitHub und MCP-CURRENT synchron; Tests, Check Style und
CodeQL gruen. Symcon 9.1 / PHP 8.5.8, alle elf Instanzen Status 102;
Kernel-Startzeit unveraendert. Neuer Datalabels-Pfad HTTP 200 und bytegleich,
Kompatibilitaetspfad nach Zeilenendennormalisierung unveraendert.

Die drei installierten Chart-Vorlagen wurden in Edge 155.0.4283.18 ohne
HTML-Ersetzung geprueft: neuer Pluginpfad jeweils einmal geladen, Registrierung,
native Tooltips und browserlokal eingeblendete Labels bestanden. Chart-Zeitachse
schreitet fort; nach Reload jeweils genau eine Chart-Instanz. WebSocket 101 in
allen drei Vorlagen vor/nach Reload; keine Dialoge, JavaScript-/HTTP-Fehler oder
JSLive-Logwarnungen/-fehler seit dem Metadatenzeitpunkt. Gezielt bestanden,
kein neuer vollstaendiger Matrixdurchlauf.

## Lokaler Kandidat: Streaming-Einbindung am 02.10.2026

Ausgangsstand 0.77. Die vorherige installierte Pruefung bestaetigte den
qultoltd-Streaming-Fork 3.1.0 und eine fortschreitende Realtime-Achse, aber
`framerate` statt `frameRate` sowie ein Update-Objekt `{preservation: true}`
statt des vorgesehenen Modus `'quiet'`. Beide Fehler wurden einzeln durch
zunaechst rote Regressionstests reproduziert und in `Chart.html` korrigiert.
Normale Zeitachsen verwenden weiterhin den Standardmodus; Plugin, Pfad,
Datenverarbeitung und Aktualisierungssperren bleiben unveraendert.

Lokaler, isolierter Edge 155.0.4283.18, 1024 x 768, Europe/Berlin: originale
Templatefunktionen mit synthetischen Daten und den echten lokalen Dateien
Chart.js 4.5.1, Moment 2.31.0, Adapter 1.0.1, Streaming 3.1.0 und Datalabels
2.2.0 ausgefuehrt. Der effektive `frameRate` ist 30 auch bei testweise auf 17
gesetztem Plugin-Default. `UpdateChart` erreicht den echten Chart mit `'quiet'`,
Messwert 42.259 wird wie bisher zu 42.25; die Zeitachse schreitet weiter fort.
Nach browserlokalem Wechsel auf `time` erfolgt ein Standardupdate mit Messwert
43.5. Keine Browserfehler/Dialoge; nach `destroy()` keine Chart-Instanz uebrig.
Dieser Kandidatenlauf verwendet keine Symcon-Verbindung und schreibt nichts
in die Installation. Er ersetzt weder eine installierte noch eine WebSocket-
oder Pull-Modus-Abnahme.

`tests/chart-streaming.js` fuehrt die Originalfunktionen fuer alle Perioden,
relative/absolute Ansichten, Realtime-/Time-Updates, wiederholte Datenzufuhr,
historische Ansichten, laufenden Reload, unbekannte Variablen und alte Werte
aus. Der Test ist in `php tests/run.php` eingebunden. Gesamtsuite und PHP-
Syntaxcheck aller 57 Dateien unter PHP CLI 8.5.10 bestanden; JavaScript-
Syntaxchecks der betroffenen Tests und `git diff --check` ebenfalls gruen.
CI und gezielte installierte Fix-Abnahme nach dem Modulupdate stehen aus.
Danach Seite neu laden; bei aktivem HTML-Cache vorher ueber `ApplyChanges()`
erneuern. Ein Dienstneustart ist fuer diesen Template-Schritt nicht erforderlich.

## Installierte Abnahme 0.78: Streaming-Einbindung am 02.10.2026

Quellcommit `cde5520`, Metadatencommit `cf42e48`, Library 0.78,
Build 215897376: lokal, GitHub und MCP-CURRENT synchron; Tests, Check Style
und CodeQL gruen. Symcon 9.1 / PHP 8.5.8, alle elf Instanzen Status 102.
Die Kernel-Startzeit blieb unveraendert.

Installierte Vorlage in Edge 155.0.4283.18 ohne Codeersetzung geladen:
`frameRate: 30`, Realtime-Achse schreitet fort, browserlokale Wertzufuhr
verwendet `'quiet'`. Nach ausschliesslich browserlokalem Wechsel zur normalen
Zeitachse erfolgt der Standardmodus. Reload stellt die installierte Realtime-
Ansicht wieder her; genau eine Chart-Instanz und zwei Datensaetze, WebSocket
jeweils HTTP 101. Zusaetzlicher lesender Pull-Abruf erreicht den Quiet-Modus.
Keine Browser-/HTTP-Fehler oder JSLive-Logwarnungen/-fehler im geprueften
Zeitraum seit den Metadaten. Gezielt bestanden, kein neuer Vollmatrixdurchlauf.

`ApplyChanges()` wurde durch das Modulupdate automatisch ausgefuehrt;
ein zusaetzlicher Aufruf ist nicht erforderlich. Der nachfolgende eigene
Realtime-Prototyp ist nicht in Symcon eingebunden und gehoert nicht zu dieser
Abnahme. Sein lokaler Vergleich steht in `docs/REALTIME_PROTOTYPE.md`.

## Lokaler Kandidat: Wartungsfork 3.6.0 am 02.10.2026

Die Standard-Chartvorlage verwendet lokal den versionierten eigenen
Streaming-Wartungsfork 3.6.0; der alte 3.1.0-Pfad bleibt erhalten.
Acht isolierte Browserdurchlaeufe mit echten Assets und Templatefunktionen
sind bestanden (UTC/Berlin, Desktop/schmale Ansicht, Alt-/Neuvergleich).
Die Datenantworten sind synthetisch; es fand kein Zugriff auf Symcon statt.
Details, Testbefehl, Grenzen und noch offene Abnahme stehen in
[STREAMING_INTEGRATION.md](STREAMING_INTEGRATION.md).
JSLive-CI und gezielte installierte WebSocket-/Pull-/IPSView-Abnahme nach
Modulupdate stehen aus. Kein neuer installierter PASS durch diesen lokalen Test.

## Fortschreibung 03.10.2026

Fortschreibung 03.10.2026: Fuer Streaming 3.6.0 auf JSLive 0.84 sind CI,
Asset-Integritaet und gezielte installierte Browserpruefungen bestanden.
Gesamtstatus PARTIAL: kein echter WebSocket-Datenwechsel nachgewiesen,
Zeitachsenwechsel nur browserlokal, IPSView auf MCP-CURRENT mangels Lizenz
nicht pruefbar. Details: [Streaming-Integration](STREAMING_INTEGRATION.md).
Der folgende jQuery-4.0.0-Kandidat ist lokal separat geprueft, noch nicht
installiert: [jQuery-Migration](JQUERY_MIGRATION.md). Die Weiterentwicklung ist
ausdruecklich freigegeben; daraus folgt kein vollstaendiger Runtime-PASS.

## Gezielte Abnahme von 0.95 und historischer Achsenfix (03.10.2026)

Ausgangspunkt: Quellcommit `87e020e`, Metadatencommit `c7438cb`, Library 0.95,
Build 142475790. MCP-CURRENT meldet Symcon 9.1, Revision
`rust-dab58090190ab6ce72c9c1d036c2e935edff313f`, PHP 8.5.8. Alle elf vorhandenen
JSLive-Instanzen waren aktiv. Dies ist eine gezielte Pruefung, kein neuer
Vollmatrix-, Neustart- oder CI-Nachweis.

- PASS: echter synthetischer Messwertwechsel und Ruecksetzung ueber den
  JSLive-WebSocket (`10603`) empfangen und ohne Reload im installierten Chart
  sichtbar. Kein ausschliesslicher Handshake- oder browserlokaler Ersatztest.
- PASS: Minutenansicht aktiviert den bestehenden Pull-Modus (drei Sekunden).
  Geaenderter synthetischer Wert wird per HTTP geliefert und im Browser
  dargestellt; im Minutenmodus unterdrueckt die Vorlage den WebSocket-Wertpfad.
- PASS im geprueften Umfang: 30 HTTP-Exportabrufe fuer zehn Instanzen aller
  neun Kindmodultypen mit fehlendem `scripts`, `scripts=0` und `scripts=1`.
  JSON, Downloadheader, `nosniff` und Chart-Listenspaltenfilter korrekt;
  Konfigurationen unveraendert. Keine Testinstanz hat ein eigenes Template
  oder exportierbare Bibliotheksskripte: deren positiver Opt-in bleibt durch
  lokale Regressionen, nicht durch diese Live-Abrufe nachgewiesen.
- FAIL auf 0.95: Stunde/Minute/Tag und relative/absolute Ansicht wechseln,
  aber beim historischen Offsetwechsel im asynchronen Modus bleiben die
  bisherigen Zeitachsengrenzen stehen. Ein zusaetzlicher leerer Tag erscheint.
  Dieselben Daten sind nach vollstaendigem Browser-Reload korrekt dargestellt.
  Die HTTP-Antwort enthaelt bereits die richtigen Grenzen; der bestehende
  Chart bekommt beim partiellen Reload nur die neuen Datensaetze.
- Keine Browserwarnungen/-fehler oder JSLive-Warnungen/-fehler im geprueften
  Protokollzeitraum. Die ausdruecklich freigegebenen synthetischen Mess- und
  Steuerwerte wurden vollstaendig zurueckgesetzt; Konfigurationshashes blieben
  gleich. Testwertwechsel koennen wie vereinbart im Testarchiv verbleiben.

Lokale Korrektur: Der asynchrone partielle Reload uebernimmt nun wie der
synchrone Pfad die geladenen Skalen vor `myChart.update()`. Die Instanz wird
nicht neu erzeugt. Daten und Achsen wechseln gemeinsam nach Abschluss aller
Datensatzantworten; API, Properties, Datenformate und Bibliotheken bleiben gleich.
Der neue Regressionstest schlug vorher an der veralteten Startgrenze fehl.
Alle 19 deterministischen Ladeszenarien sowie acht isolierte Browservarianten
(Edge 155.0.4283.18, UTC/Berlin, 1024/390 Pixel, Streaming 3.1.0/3.6.0) bestanden.
Der Browsertest verwendet echte Assets, aber synthetische HTTP-Antworten.

Noch erforderlich: Commit/Push, gruene CI, Metadatenabgleich und Modulupdate
durch den Eigentuemer; dann Ansicht neu laden und absoluten Tag mit historischem
Offset sowie Rueckkehr live nachpruefen. Kein zusaetzliches ApplyChanges oder
Dienstneustart fuer diesen Templatefix. Eigene Templates separat nachziehen.
IPSView folgt laut Eigentuemer nach Fertigstellung auf dem Produktivsystem.
SymconECharts-Exportplanung ist bis zur Reife des Nachfolgers zurueckgestellt.

## Fortschreibung nach Abnahme von 0.96 (03.10.2026)

Diese Fortschreibung ordnet die historischen Offen-Markierungen oben ein;
sie ersetzt keinen Gesamtmatrixdurchlauf. Gepruefter installierter Stand:
Quellcommit `24731c9123289fbee85160300d1af581e2a6e3a6`, Metadatencommit
`817046f9a8e74147a530a29e931e03ac5032c2a4`, Library 0.96, Build 38220233.
Lokal und GitHub-dev synchron; Tests, Style und CodeQL fuer exakt diesen
Metadatencommit erfolgreich. Alle elf JSLive-Instanzen Status 102.

Der gezielte installierte Achsennachtest auf MCP-CURRENT bestand:
heute -> gestern -> vorgestern -> heute ohne Zwischen-Reload, anschliessend
Rueckkehr zur relativen Stundenansicht. Jeweils nur der ausgewaehlte Tag statt
eines zusaetzlichen leeren Tages. Alle fuenf Steuerwerte wiederhergestellt,
Konfigurationen unveraendert, keine JSLive-Warnungen/-Fehler im geprueften Log.
Anfangs enthielt das frisch geoeffnete Browserdokument noch die alte Vorlage;
erst ausdrueckliches Neuladen revalidierte den aktivierten Browser-Cache.
Waehrend einer Zwischenruecksetzung trat ein Browserfehler mit einem
Epochendatum auf. Im anschliessenden eigentlichen Nachtest und seiner
Ruecksetzung keine neuen Browserfehler; der Zwischenfehler wird nicht als
unabhaengig behoben ausgegeben.

Der danach durchgefuehrte lokale Abschlussabgleich bestaetigte einen weiteren
Fehler: Ueberlappende Reloads verwenden gemeinsame Konfiguration/Skalen und
nehmen verspaetete Antworten alter Abrufe an. Bei zwei gueltigen Tagesfenstern
konnten nach Abschluss aller Abrufe Daten des vorherigen Tages unter der
neuen Achse stehen. Das erklaert eine Fehlerklasse, beweist aber nicht die
vollstaendige Ursache des zuvor beobachteten Epochendatum-Fehlers.

Lokale Korrektur: Jeder akzeptierte Reload bekommt eine fortlaufende Generation.
Alte Konfigurations-, Achsen-, Datensatz-, Fehler- und Abschlusscallbacks sowie
kombinierte Antworten werden verworfen. Nur der aktuelle Abruf rendert und
beendet den Ladezustand. Ein noch erforderlicher Vollreload wird uebernommen.
Die bisherige Ereignis-/Zeitstempel-Deduplizierung, HTTP-Vertraege, Properties,
Bibliotheken und gespeicherte Daten bleiben unveraendert.

37 lokale Ladeszenarien (19 bestehende, 18 Ueberholungsfaelle) und acht echte
Chart.js-Browservarianten bestanden. Die neue Regression war vor dem Fix rot;
die Browsermatrix prueft auch, dass zuletzt eintreffende alte Daten die reale
Zeitachse nicht wieder vergroessern. HTTP bleibt synthetisch, kein installierter
Nachweis fuer diesen neuen Fix. Gesamtsuite, 47 PHP-Syntaxchecks (PHP 8.5.10),
JavaScript-Syntax, PHP-CS-Fixer-Trockenlauf, JSON und `git diff --check` bestanden.
Auch zwoelf jQuery-Browserfaelle und drei vollstaendige sequentielle Gauge-
Durchlaeufe mit je acht Faellen bestanden (Edge 155.0.4283.18, Node 24.19.0).
Die neue CI und Live-Abnahme folgen nach Commit/Push und Modulupdate.

### Nachtest 0.97 und Korrektur gleicher Ereigniszeitstempel

Stand 0.97 (`8c453d7`, Metadaten `bf04cf1`) wurde lokal, remote und ueber MCP
installiert abgeglichen. Tests, Style und CodeQL waren gruen. Nach explizitem
Browser-Reload war der Generationenschutz im ausgelieferten Dokument vorhanden.
Der gezielte schnelle Live-Test ist dennoch **FAIL**: drei Offsetwechsel
innerhalb von 413 ms endeten serverseitig beim aktuellen Tag, die Anzeige
blieb dagegen auf dem vorletzten Tag. Getrennte Wechsel funktionierten.
Die bisherige Sperre `last_reload == dt_val` verwirft unterschiedliche
Steuerereignisse derselben Sekunde vor dem Generationenschutz. Alle fuenf
Test-Steuerwerte wurden wiederhergestellt, Chart-/Splitter-Konfigurationen
blieben unveraendert; keine Browserfehler oder JSLive-Warnungen/-Fehler im
geprueften Zeitraum. Keine Messwert- oder Produktivkonfiguration geaendert.

Die anschliessende lokale Korrektur entfernt ausschliesslich diese globale
Zeitstempel-Sperre und ihren Zustand. Jeder relevante Aufruf wird angenommen;
ueberholte Antworten werden weiterhin ueber Generationen verworfen. Dies kann
bei mehreren Steuerereignissen mehr Abrufe ausloesen; eine Sekunde ist keine
gueltige Ereigniskennung. Keine neue Timer-/Debounce-Schicht, keine Aenderung
an PHP, Assets, Transportvertraegen oder eigenen Vorlagen.

53 lokale Ladeszenarien bestanden, davon 16 neue Faelle mit gleichem Zeitstempel
(asynchron/kombiniert, alte Antworten zuerst/zuletzt, Offset-Rueckkehr,
verschiedene Steuerwerte, Poll-Aufrufe ohne Wert und erhaltener Vollreload).
Die neue Regression schlug vor dem Fix mit einem statt drei Abrufen fehl.
Acht reale Chart.js-Browservarianten bestanden auch mit drei gleich datierten
Wechseln und zuletzt eintreffenden alten Daten; zwoelf jQuery-Faelle bestanden.
HTTP-Antworten sind synthetisch. Kein installierter PASS fuer diese Korrektur:
CI und erneuter MCP-/Browser-Nachtest folgen erst nach Push und Modulupdate.
Die PHP-Gesamtsuite (inklusive Struktur-/Vertragspruefungen), 59 PHP-Syntaxchecks
einschliesslich Helper unter PHP 8.5.10, JavaScript-Syntax, PHP-CS-Fixer-Trockenlauf
(47 konfigurierte Dateien, keine Aenderungen) und `git diff --check` bestanden.

Aktive Restpunkte fuer den Wartungsabschluss:

1. Chart-Zeitstempel-Fix committen/pushen, CI und Metadaten abgleichen, Modulupdate,
   Ansicht ausdruecklich neu laden und schnelle Zeitraumwechsel live nachpruefen.
   Kein zusaetzliches ApplyChanges und kein geplanter Dienstneustart.
2. Separaten Canvas-Gauges-Fehler bei unterbrochenen Animationen korrigieren;
   siehe [Gauge-Audit](GAUGE_AUDIT.md#abschlussabgleich-auf-basis-096-animationsgrenze).
   Der zuvor sporadische Test wartet jetzt korrekt auf Animationsende; daraus
   folgt keine Freigabe fuer schnelle Gauge-Wertwechsel.
3. IPSView-Abnahme durch den Eigentuemer auf dem Produktivsystem nach Abschluss
   der Fehlerkorrekturen. Aktuelle WebViews, eigene Templates und verwendete
   Darstellungen pruefen; Desktop-Browsertests ersetzen diese Abnahme nicht.
4. Exakten Release-Kandidaten nach [Release-Prozess](RELEASE_PROCESS.md)
   vorbereiten und kontrolliert freigeben. Noch kein Stable-PASS.

WebSocket-/Minuten-Pull-Luecken sind durch die gezielte 0.95-Abnahme geschlossen;
der historische Achsenfix durch 0.96. Positive Exporte eigener Skriptinhalte sind
lokal abgesichert, mangels passender Live-Testkonfiguration nicht live nachgewiesen.
Die ECharts-Uebergabe bleibt zurueckgestellt. Historische Prototyp-, native
Kachel- und allgemeine Erweiterungsplaene sind keine aktiven Freigabepunkte.

## Ergebnisregeln

- `PASS`: alle verpflichtenden Punkte sind mit frischem Laufzeitnachweis grün.
- `FAIL`: reproduzierbarer Defekt in einem verpflichtenden Prüfpunkt.
- `BLOCKED`: benötigte Laufzeit, Abhängigkeit oder fachliche Entscheidung fehlt;
  der Grund und der nächste Schritt sind dokumentiert.
- Ein Modul darf nur aus dem Release-Gate entfernt werden, wenn seine Entfernung
  einschließlich Migrationsfolge ausdrücklich beschlossen und umgesetzt wurde.
- Eine reine Quelltext-, Stub- oder CI-Prüfung darf nie als bestandener
  Symcon-Laufzeittest eingetragen werden.
