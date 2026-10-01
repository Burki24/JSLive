# IP-Symcon-Laufzeitmatrix

Diese Matrix definiert die Laufzeitabnahme für JSLive auf der jeweils über den
Symcon-MCP erreichbaren IP-Symcon-9-Testebene mit PHP 8.5. Sie ergänzt die
lokalen Stubs und Vertragstests; ein grüner CI-Lauf ersetzt keine Ausführung in
der echten Symcon-Laufzeit.

Die Zielplattform bleibt IP-Symcon 9.0/9.1. Aufgrund der für JSLive
maßgeblichen Kompatibilität innerhalb dieser Produktlinie wird keine separate
9.0-Installation vorgehalten. Verbindlich ist die aktuelle MCP-Testebene; aus
dieser Festlegung werden keine Kompatibilitätsangaben in `library.json`
abgeleitet.

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
| `MCP-CURRENT` | aktuelle, über den Symcon-MCP erreichbare IP-Symcon-9-Installation mit isoliertem synthetischem JSLive-Testbaum | den installierten Prüfcommit mit allen 10 Modultypen nach Bibliotheksupdate, zweimaligem ApplyChanges, Browserzugriff und Dienstneustart prüfen | `PASS` am 01.10.2026 |

Die Testebene bleibt für folgende Prüfcommits bestehen. Bibliotheksupdates
werden gegen die vorhandenen Instanzen geprüft; Änderungen an Anlage- oder
Löschpfaden erfordern zusätzlich eine neu angelegte beziehungsweise entfernte
Testinstanz des betroffenen Modultyps. ConfigStore, SyncModule und Calendar
bleiben entfernt; ihre früheren Modul-IDs werden nicht wiederverwendet.

Der dokumentierte PASS vom 01.10.2026 ist die Vorher-Baseline auf JSLive 0.61.
Für den `IPSModuleStrict`-Migrationscommit ist nach Installation ein neuer
vollständiger Durchlauf erforderlich; bis dahin ist dieser Kandidat nur lokal
verifiziert.

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
| `SymconJSLiveColorPicker` | mindestens zwei konfigurierte Farbvariablen, Lesen, erlaubtes Schreiben über Variablenaktion und Ablehnung einer fremden ID prüfen | iro.js wird noch extern geladen; Offlinefehler getrennt vom PHP-Modul bewerten |
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

## Ergebnisregeln

- `PASS`: alle verpflichtenden Punkte sind mit frischem Laufzeitnachweis grün.
- `FAIL`: reproduzierbarer Defekt in einem verpflichtenden Prüfpunkt.
- `BLOCKED`: benötigte Laufzeit, Abhängigkeit oder fachliche Entscheidung fehlt;
  der Grund und der nächste Schritt sind dokumentiert.
- Ein Modul darf nur aus dem Release-Gate entfernt werden, wenn seine Entfernung
  einschließlich Migrationsfolge ausdrücklich beschlossen und umgesetzt wurde.
- Eine reine Quelltext-, Stub- oder CI-Prüfung darf nie als bestandener
  Symcon-Laufzeittest eingetragen werden.
