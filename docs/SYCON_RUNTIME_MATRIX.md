# IP-Symcon-Laufzeitmatrix

Diese Matrix definiert die noch auszuführenden Laufzeitabnahmen für JSLive
unter IP-Symcon 9.0 und 9.1 mit PHP 8.5. Sie ergänzt die lokalen Stubs und
Vertragstests; ein grüner CI-Lauf ersetzt keine Ausführung in der echten
Symcon-Laufzeit.

Bis Ergebnisse mit Commit, Symcon-Build und Testumgebung protokolliert wurden,
bleiben alle Szenarien **nicht ausgeführt**. Aus der Definition dieser Matrix
werden keine Kompatibilitätsangaben in `library.json` abgeleitet.

## Plattformgrundlage

IP-Symcon 9.0 hat PHP 8.5 eingeführt und verwaltet Datenflussverbindungen über
die Verwaltungskonsole. Seit IP-Symcon 8.1 steht außerdem `IPSModuleStrict`
bereit; JSLive verwendet in dieser Baseline weiterhin `IPSModule` und seine
lokalen Basisklassen. Die folgenden offiziellen Seiten sind die maßgeblichen
externen Referenzen:

- [Migration von 8.1 auf 9.0](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v81-v90-q1-2026/)
- [PHP-Modul-SDK und IPSModuleStrict](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
- [WebHook Control](https://www.symcon.de/de/service/dokumentation/modulreferenz/core-instances/webhook-control/)
- [Download-Archiv für ältere Testversionen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/download-archiv/)

Am 28.09.2026 wurde auf dem lokalen Rechner ausschließlich lesend ein
verfügbarer Testkandidat mit IP-Symcon 9.1 und PHP 8.5.8 festgestellt. Das ist
noch kein Laufzeitergebnis. Vor der ersten Ausführung sind ein isolierter
Testbaum und ein Backup beziehungsweise Snapshot festzulegen.

## Verbindliche Szenarien

| ID | Ausgangspunkt | Ziel und Umfang | Status |
| --- | --- | --- | --- |
| `S90-FRESH` | frische IP-Symcon-9.0-Testinstallation | JSLive auf dem zu prüfenden Commit installieren und alle 12 Module neu anlegen | nicht ausgeführt |
| `S90-UPGRADE` | IP-Symcon 9.0 mit dem bisherigen JSLive-`main` und repräsentativen Instanzen | auf denselben JSLive-Prüfcommit aktualisieren; Konfiguration, Idents, Werte und Verbindungen erhalten | nicht ausgeführt |
| `S91-FRESH` | frische IP-Symcon-9.1-Testinstallation | JSLive auf dem Prüfcommit installieren und alle 12 Module neu anlegen | nicht ausgeführt |
| `S91-UPGRADE` | erfolgreich abgenommener 9.0-Snapshot | IP-Symcon auf 9.1 aktualisieren, ohne den JSLive-Commit zu wechseln, und alle Instanzen erneut prüfen | nicht ausgeführt |

`S90-UPGRADE` berücksichtigt die dokumentierte Entfernung des ConfigStore:
Eine eventuell vorhandene ConfigStore-Instanz wird vor dem Bibliotheksupdate
manuell gelöscht. Sie darf weder automatisch migriert noch unter ihrer alten
Modul-ID neu verwendet werden.

## Nachweis je Durchlauf

Für jeden Durchlauf werden folgende Felder ohne private Objekt- oder
Installationsdaten protokolliert:

| Evidence field | Inhalt |
| --- | --- |
| IP-Symcon version and build | exakte Version und Kernel-Build beziehungsweise Revision |
| PHP version | durch die Symcon-Laufzeit gemeldete PHP-Version |
| JSLive commit | vollständiger geprüfter Git-Commit; der Metadaten-Bot-Commit wird ebenfalls notiert |
| Plattform | Betriebssystem beziehungsweise SymBox/Docker und Architektur |
| Fresh installation | ja/nein sowie Szenario-ID |
| Upgrade installation | Ausgangsversion und Upgrade-Pfad oder nicht zutreffend |
| Browser/Client | Verwaltungskonsole, Browser und gegebenenfalls IPSView-Version |
| ApplyChanges twice | Ergebnis der zweimaligen unveränderten Anwendung |
| Service restart | Ergebnis nach vollständigem Neustart des IP-Symcon-Dienstes |
| Message log | fehlerfrei oder Liste reproduzierbarer Warnungen/Fehler ohne private Daten |
| Ergebnis | `PASS`, `FAIL` oder `BLOCKED` mit Verweis auf den betroffenen Prüfpunkt |

Screenshots, Exportdateien und Logs dürfen keine Kennwörter, privaten
Objekt-IDs, internen Adressen oder realen Kalender-/Messdaten enthalten.

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
- eine eingebettete minimale iCalendar-Datei ohne Personen- oder Ortsdaten;
- ein kleines lokales SVG und je ein lokales PNG/JPEG/GIF für Progressbar;
- eine zweite kompatible Modulinstanz für Konfigurationsübernahme und, falls
  das SyncModule beibehalten wird, für die lokale Synchronisation.

Nach jedem Szenario wird der Testbaum aus dem Snapshot zurückgesetzt. Es werden
keine realen Skripte ausgeführt und keine produktiven Variablen, Medien oder
Kalenderquellen verwendet.

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
| `SymconJSLiveCalendar` | Formular, eingebettete ICS-Datei, `getICS`, `getFeed`, `getCSS`, Toolbar und Browserdarstellung prüfen | `setData` ist deklariert, aber nicht implementiert; Entfernung oder Reparatur ist vor Freigabe zu entscheiden; CDN-Abhängigkeiten separat protokollieren |
| `SymconJSLiveChart` | archivierte Floatvariable, mehrere Zeiträume, Offset, Achsen, `getData`/`getUpdate`, Periodenvariablen und Browserdarstellung prüfen | Archiv-, Zeitzonen- und absolute Jahresgrenzen mit festen Testdaten prüfen |
| `SymconJSLiveColorPicker` | mindestens zwei konfigurierte Farbvariablen, Lesen, erlaubtes Schreiben über Variablenaktion und Ablehnung einer fremden ID prüfen | iro.js wird noch extern geladen; Offlinefehler getrennt vom PHP-Modul bewerten |
| `SymconJSLiveCustom` | Variable, Read-only-Dataset, Kategorie, Link, Testskript, Textmedium, lokale JS/CSS-Datei und Standardtemplate prüfen | keine Ziele außerhalb der Datasets; Read-only muss Schreiben, Medienänderung und Skriptausführung verhindern |
| `SymconJSLiveDateTimePicker` | konfigurierte Integer-Zeitvariable mit und ohne Aktion, Zeitzone, Lesen/Schreiben und Ablehnung einer fremden ID prüfen | Unix-Zeitwert und lokale Zeitzone dürfen nach Neustart nicht abweichen |
| `SymconJSLiveDoughnutPie` | mehrere numerische Variablen, Doughnut- und Pie-Modus, Farben, Legende, `getData`/`getUpdate` und Browserdarstellung prüfen | bekannte Dataset-Auswahlabweichung separat reproduzieren und nicht beiläufig korrigieren |
| `SymconJSLiveGauge` | numerische Variable, radialer und linearer Modus, Min/Max, Präzision, Highlights und Live-Aktualisierung prüfen | Canvas-Gauge-Asset muss lokal und ohne externen Dienst laden |
| `SymconJSLiveProgressbar` | numerische Variable, mindestens zwei Presets, eigenes SVG, alle vier erlaubten Füllbildtypen und Live-Aktualisierung prüfen | dynamischer Bildtyp muss auf der MIME-Allowlist bleiben; fremde Typen müssen binär zurückfallen |
| `SymconJSLiveRadarChart` | archivierte Variable, mehrere Datensätze, absolute/relative Perioden, Offset, Skala und Browserdarstellung prüfen | Archiv-, Zeitzonen- und absolute Jahresgrenzen mit festen Testdaten prüfen |
| `SymconJSLiveSyncModule` | Instanz und Formular ohne Endlosschleife öffnen; bereits gültige lokale Master-/Slave-Konfiguration nur in isolierten Fixtures prüfen | Modultyp-Liste hängt vom abgeschalteten Dienst ab; produktiver Bedarf und Entfernung sind vor Strict-Migration zu entscheiden |

## Webhook- und Browser-Gate

Pro Plattformversion werden mindestens diese End-to-End-Pfade geprüft:

- Standardausgabe `getContend` mit richtigem und falschem Kennwort;
- öffentliche statische Assets und die bestehende `getCSS`-Ausnahme;
- JSON-, HTML-, CSS-, JavaScript-, SVG-, ICS-, Text- und Bildantworten samt
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

## Upgrade-Gate

Für `S90-UPGRADE` und `S91-UPGRADE` gelten zusätzlich:

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

## Ergebnisregeln

- `PASS`: alle verpflichtenden Punkte sind mit frischem Laufzeitnachweis grün.
- `FAIL`: reproduzierbarer Defekt in einem verpflichtenden Prüfpunkt.
- `BLOCKED`: benötigte Laufzeit, Abhängigkeit oder fachliche Entscheidung fehlt;
  der Grund und der nächste Schritt sind dokumentiert.
- Ein Modul darf nur aus dem Release-Gate entfernt werden, wenn seine Entfernung
  einschließlich Migrationsfolge ausdrücklich beschlossen und umgesetzt wurde.
- Eine reine Quelltext-, Stub- oder CI-Prüfung darf nie als bestandener
  Symcon-Laufzeittest eingetragen werden.

Bekannte Startblocker sind der fehlende Calendar-`setData`-Handler und die
externe Modultyp-Liste des SyncModule. Beide bleiben sichtbar, bis über
Reparatur, Ersatz oder Entfernung entschieden wurde.
