# PROJECT_CONTEXT - JSLive

Stand dieser Bestandsaufnahme: 26.09.2026, Branch `dev`, Commit
`b0ab43bb727f534e788793b0a0ae7abc2b1231d7`.

## 1. Zweck und Zielbild

JSLive ist eine IP-Symcon-Modulbibliothek fuer browserbasierte Visualisierungen.
Ein zentraler Splitter stellt Webhook, statische Assets, gemeinsame Konfiguration
und den Datenaustausch mit den Visualisierungsmodulen bereit.

Das Modernisierungsziel ist ein unter IP-Symcon 9.0 und 9.1 sowie PHP 8.5
wartbarer Bestand. Bestehende Installationen, Konfigurationen, Skriptaufrufe und
IPSView-Nutzung muessen waehrend der Umstellung funktionsfaehig bleiben. Eine
moderne Kacheldarstellung und eine Minimierung fremder Ressourcen sind spaetere,
separat zu validierende Ausbaustufen.

Nicht Ziel der ersten Phase sind neue Fachfunktionen, ein Komplettumbau der
Architektur oder ein gleichzeitiger Austausch aller Frontend-Bibliotheken.

## 2. Repository- und Branch-Stand

- Arbeitsbranch: `dev`; Arbeitsbaum war bei Beginn der Bestandsaufnahme sauber
  und mit `origin/dev` synchron.
- `dev` liegt 34 Commits vor `main` und vor `upstream/main`; beide enthalten
  keine Commits, die `dev` fehlen.
- `upstream/Beta` ist ein historischer Vorfahr und liegt 121 Commits hinter
  `dev`, ohne eigene Abweichung.
- Bibliotheksversion: `0.9.9.9`, Build 35.
- Gegenueber `main` enthaelt `dev` im Wesentlichen CI, Struktur-/Integritaetstests,
  den zentral bezogenen Helper-Bestand und die bereits erfolgte Anbindung des
  `DataFlowHelper`.

## 3. Module

| Modul | Typ | Aufgabe |
| --- | ---: | --- |
| `SymconJSLive` | 2 | Splitter, Webhook `/hook/JSLive`, Asset-Auslieferung, gemeinsame Links und Konfiguration |
| `SymconJSLiveAdvTextfield` | 3 | Erweitertes Textfeld |
| `SymconJSLiveCalendar` | 3 | Kalenderdarstellung und ICS-Quellen |
| `SymconJSLiveChart` | 3 | Linien-/Balkendiagramme und Archivdaten |
| `SymconJSLiveColorPicker` | 3 | Farbauswahl und Rueckschreiben von Werten |
| `SymconJSLiveCustom` | 3 | Benutzerdefinierte HTML-/JavaScript-Ausgaben und Objektaktionen |
| `SymconJSLiveDateTimePicker` | 3 | Datum-/Zeitauswahl und Rueckschreiben von Werten |
| `SymconJSLiveDoughnutPie` | 3 | Doughnut-/Tortendiagramme |
| `SymconJSLiveGauge` | 3 | Messinstrumente |
| `SymconJSLiveProgressbar` | 3 | Fortschrittsanzeigen und SVG-Import |
| `SymconJSLiveRadarChart` | 3 | Radardiagramme und Archivdaten |
| `SymconJSLiveConfigStore` | 4 | Externer Austausch und Import von Modulkonfigurationen |
| `SymconJSLiveSyncModule` | 3 | Synchronisation ausgewaehlter Konfigurationsparameter zwischen Instanzen |

Die zehn Visualisierungsmodule verwenden die gemeinsame Basisklasse
`SymconJSLive/libs/JSLiveModule.php`. `ConfigStore` und `SyncModule` sind
eigenstaendige Sondermodule und nicht Teil der Splitter-Datenverbindung.

## 4. Architektur und Datenfluss

Die Splitter-Instanz implementiert die Data-ID
`{751AABD7-E31D-024C-5CC0-82AC15B84095}`. Die Kindmodule erwarten diese als
Parent und implementieren die Data-ID
`{79D59629-E9C5-44F1-0F34-0FBC5C88F307}`.

Vereinfachter Ablauf:

1. Ein Browser ruft `/hook/JSLive/...` auf.
2. Der Splitter prueft Request und Kennwort, liefert Assets aus oder sendet einen
   JSON-Befehl ueber `SendDataToChildren`.
3. Das adressierte Kindmodul verarbeitet Befehle wie `getContend`, `getData`,
   `getUpdate`, `setData`, `getCSS`, `getSVG` oder Konfigurationsimport/-export.
4. Das Kindmodul fordert gemeinsame Werte und Links mit `SendDataToParent` an.
5. Variablenaenderungen werden ueber MessageSink verarbeitet; Aktualisierungen
   koennen ueber den WebHook-Control-WebSocket an den Browser gepusht werden.

Die DataFlow-Kodierung wird in Splitter und Basisklasse bereits durch den
zentralen `DataFlowHelper` gekapselt. HTML, CSS und JavaScript werden weiterhin
ueber Templates, Platzhalter und grosse Property-Mengen erzeugt.

## 5. Oeffentliche Vertraege

Zu erhalten und vor Refactorings durch Charakterisierungstests abzusichern sind:

- alle 13 Modul-IDs, Praefixe und die beiden Data-IDs;
- der Hook-Pfad `/hook/JSLive` und seine Unterpfade;
- die JSON-Umschlaege `DataID`, `Buffer`, `Type`, `InstanceID` sowie die
  bestehenden Befehlsnamen, einschliesslich historischer Schreibweisen;
- registrierte Properties, gespeicherte Listen/JSON-Strukturen und Buffer;
- die Variablen-Idents `Output`, `IPSView`, `Content`, `Period`, `Now`,
  `Relativ`, `Offset` und `StartDate`;
- oeffentliche Modulmethoden fuer Links, Konfigurationsimport/-export,
  Formaktualisierung, Datenabfragen, Aktionen, Store und Synchronisation;
- vorhandene Template- und Assetpfade.

Der Konfigurationsimport kann Skripte anlegen und Instanzkonfigurationen
ueberschreiben. `ConfigStore` und `SyncModule` koennen fremde
Instanzkonfigurationen setzen und `ApplyChanges` ausloesen. Diese Pfade brauchen
vor jeder Modernisierung eigene Sicherheits- und Regressionstests.

## 6. Helper-Abgleich mit SymconDevelopment

`.helper-sync.json` bezieht elf Helper-Pakete. Aktuell produktiv eingebunden ist
nur `DataFlowHelper`.

| Zentraler Helper | Heutiger lokaler Bestand bzw. moegliche Nutzung |
| --- | --- |
| `ConfigurationFormHelper` | Teilueberschneidung mit eigener dynamischer Formularlogik; nicht ohne Charakterisierung ersetzbar |
| `DataFlowHelper` | Bereits in Splitter und Kindmodul-Basisklasse integriert |
| `DebugHelper` | Ersatz fuer unmaskierte Debug-Ausgaben und sensible Payloads |
| `HttpResponseHelper` | Ersatz fuer einen Teil der manuellen Header-/Echo-Antworten |
| `PersistentJsonCacheHelper` | Kandidat fuer JSON-Buffer; Altmodule serialisieren teils PHP-Daten |
| `ResponsiveVisualizationHelper` | Kandidat fuer Viewport-/Iframe-CSS |
| `VariableHelper` | Kandidat fuer wiederholte Objekt-/Variablenzugriffe |
| `VariablePresentationHelper` | Basis fuer native Darstellungen statt neuer Legacy-Profile |
| `VisualizationAssetHelper` | Kandidat fuer kontrollierte lokale Asset-Auslieferung |
| `VisualizationThemeHelper` | Kandidat fuer gemeinsame Theme-CSS-Werte |
| `VisualizationThemeConfigurationHelper` | Kandidat fuer gemeinsame Theme-Konfiguration |

Lokale Doppelungen bestehen insbesondere bei `json_encode_advanced`,
`HexToRGB` und `isAssoc` in beiden Basisklassen sowie bei `GetWebData` und den
serialisierenden `SetBuffer`-/`GetBuffer`-Wrappern in `ConfigStore` und
`SyncModule`. Eine Uebernahme in zentrale Helper ist erst sinnvoll, wenn der
Vertrag generalisierbar und durch Tests belegt ist.

`SymconDevelopment` enthaelt keine konkurrierenden fachlichen JSLive-Module.
Die Ueberschneidung liegt bei Standards, CI und Helper-Infrastruktur.

## 7. Tests und CI

Vorhandene lokale Pruefungen:

- `tests/validate_structure.php`: Bibliothek, 13 Module, Metadaten,
  Helper-Konfiguration und CodeQL-Sprache;
- `tests/public-contracts.php`: maschinenlesbare Charakterisierung von 13
  Modulvertraegen und 15 PHP-Quellen mit derzeit 569 Properties, 10 Variablen,
  7 Actions, 107 oeffentlichen Methoden sowie den bestehenden Hook-Pfaden;
- `tests/webhook-routing.php`: Verhaltens-Harness fuer Authentisierungs- und
  Instanz-Gates, Data-ID/JSON-Umschlag, direkte globale Konfiguration und den
  historischen Standardbefehl `getContend`;
- `tests/data-flow-integration.php`: statische Charakterisierung der
  DataFlowHelper-Anbindung;
- `tests/helper_integrity.py`: Versionen, Hashes und Vollstaendigkeit der
  vendorten Helper;
- PHP-Syntaxcheck aller PHP-Dateien.

GitHub Actions fuehrt die Tests ueber
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0` mit PHP 8.5 aus. CodeQL prueft
JavaScript/TypeScript. Der aktuelle Commit war in beiden Workflows erfolgreich.

Noch nicht abgedeckt sind Symcon-Laufzeitverhalten, Webhook-Authentisierung und
Pfadbehandlung, Formulare, Properties, Migrationen, Renderausgaben,
Browserbibliotheken, einzelne Modulaktionen sowie Store-/Sync-Netzwerkpfade.
StylePHP und ein php-cs-fixer-Check sind noch nicht Teil der JSLive-Workflowdatei.

## 8. Symcon-9-/PHP-8.5-Stand

- Die vorhandenen Tests laufen lokal unter PHP 8.2; die CI laeuft fuer den
  aktuellen Commit erfolgreich unter PHP 8.5.
- Der zuvor problematische Datenfluss verwendet kein `utf8_encode` mehr.
- Alle Module verwenden noch `IPSModule`; der Webhook basiert auf einer lokalen
  `WebHookModule`-Basisklasse. Symcon 9 empfiehlt fuer neue Module
  `IPSModuleStrict` und dessen native Hook-API. Eine Umstellung ist wegen der
  zahlreichen untypisierten oeffentlichen Methoden ein eigenes Projekt.
- `library.json` deklariert derzeit keine Symcon-Kompatibilitaet.
- `Output` und `IPSView` werden als Stringvariablen mit dem Legacy-Profil
  `~HTMLBox` angelegt. Symcon 9 unterstuetzt dies weiter, fuer modernisierte
  Variablen sind native Variablendarstellungen vorzuziehen.
- Es gibt keine explizite, versionierte Migration fuer umbenannte Properties,
  Idents, Datentypen, Profile oder persistierte Daten.
- Symcon 9.1 bietet erweiterte Formularoptionen und einen HTML-SDK-
  Visualisierungstyp fuer normale Kachel und Vollbild. Die Nutzung ist eine
  spaetere Darstellungsentscheidung und kein Drop-in-Ersatz fuer den bestehenden
  IPSView-/HTMLBox-Vertrag.

## 9. Konfigurationsformulare und Darstellungen

Alle Module besitzen `form.json` und `locale.json`. Die grossen
Visualisierungsmodule, besonders Calendar, Chart, Gauge und RadarChart, haben
umfangreiche, tief verschachtelte Formulare. Eigene Metafelder wie `viewlevel`,
`viewdisable`, `viewlevelexactly` und `requireItem` werden durch
`JSLiveModule.php` ausgewertet. Dieses Verhalten ist Teil des bestehenden
Formularvertrags.

Neben den dynamisch erzeugten `Output`-/`IPSView`-Variablen existieren nur wenige
Modulvariablen. Chart und RadarChart registrieren steuerbare Zeitraumvariablen,
AdvTextfield registriert `Content`. Eine Umstellung auf native Darstellungen muss
Idents, Datentypen, Aktionen und bestehende Anwenderkonfigurationen erhalten.

## 10. Frontend-Abhaengigkeiten

Das Repository vendort unter anderem Chart.js 3.6.0 und 4.3.3,
chartjs-plugin-datalabels 2.2.0, chartjs-plugin-streaming 3.1.0, FullCalendar
5.10.0, iro.js 5.5.0, Moment.js 2.27.0, jQuery, canvas-gauges,
MCDatepicker und loading-bar. Mehrere Versionen und unminifizierte/minifizierte
Kopien liegen parallel vor.

Calendar laedt zusaetzlich FullCalendar 5.10.1, dessen iCalendar-Plugin und
ical.js 1.4.0 von CDNs. ColorPicker laedt iro.js trotz lokaler Kopie extern.
Damit sind Darstellung, Offline-Betrieb und Lieferkette nicht vollstaendig
reproduzierbar. Es fehlen ein Paketmanifest, ein dokumentierter Buildprozess,
eine Lizenz-/Versionsliste und automatisierte Browserpruefungen.

## 11. Technische Schulden und Risiken

Prioritaet hoch:

- `ConfigStore` und `SyncModule` deaktivieren die TLS-Zertifikatspruefung fuer
  externe HTTPS-Aufrufe.
- Der Webhook schreibt bei Fehlern gesendetes und erwartetes Kennwort in den
  Debugkanal; Debugpfade koennen komplette Request-, Server- und Nutzdaten
  enthalten. Das Kennwort wird zudem als Query-Parameter transportiert. Die
  oeffentliche Methode `Debug_LoadLogFile` gibt ausserdem die komplette
  Symcon-Logdatei ungefiltert aus.
- Die statische Asset-Auslieferung bildet den Requestpfad ohne kanonische
  Begrenzungspruefung auf einen Dateipfad ab. Ob daraus in der Symcon-Runtime ein
  ausnutzbarer Pfadzugriff entsteht, muss mit einem gezielten Test geklaert
  werden.
- Import-, Store-, Custom- und Sync-Funktionen koennen Skripte erzeugen,
  Variablen/Medien schreiben, Skripte ausfuehren oder komplette
  Instanzkonfigurationen anwenden.

Prioritaet mittel:

- manuelles Query-Parsing, rohe Header-/Echo-Antworten, breite CORS-Freigabe,
  ungepruefte Server-Arrayzugriffe und `rand()` fuer Kennwoerter;
- serialisierte PHP-Daten in Buffern und `unserialize` ohne erlaubte Klassen;
- grosse Basisklasse, duplizierte Hilfsfunktionen und sehr grosse Moduldateien;
- der Konfigurationsimport prueft Property-Namen mit `in_array` gegen die Werte
  der aktuellen Konfiguration statt mit `array_key_exists` gegen deren
  Schluessel; regulaere Importfelder werden dadurch voraussichtlich uebersprungen;
- `LoadConnectAddress` enthaelt einen bedingungslosen fruehen Rueckgabepfad und
  kann die ermittelte Connect-URL derzeit nicht zurueckgeben;
- die MessageSink-Verwaltung registriert bereits bekannte Variablen erneut,
  weil sie vor der Mitgliedschaftspruefung aus der Altliste entfernt werden;
- Abhaengigkeit des ConfigStore von `jslive.babenschneider.net` und einem dort
  betriebenen Protokoll ohne lokale Schnittstellendokumentation.

Dokumentationsluecken:

- Root-README nennt nur einen Teil der 13 Module, verwendet einen falschen
  RadarChart-Pfad und nennt noch IP-Symcon 5.3 als Ziel;
- sechs Module besitzen keine README; vorhandene README-Dateien enthalten
  teilweise kopierte Namen und GUIDs;
- Datenfluss, Hook-Protokoll, oeffentliche Methoden, Migrationsregeln,
  Frontend-Lizenzen und Store-/Sync-Protokolle sind nicht vollstaendig
  dokumentiert.

## 12. Priorisierter Arbeitsplan

### Phase 0 - Bestand einfrieren und absichern

Begonnen: Der erste Vertrags-Snapshot liegt unter
`tests/fixtures/public-contracts.json` und wird durch
`tests/public-contracts.php` geprueft. Er fixiert Metadaten, Verbindungen,
Properties, Variablen, Actions, oeffentliche Methoden und Hook-Pfade, ohne
bekannte fehlerhafte Fachlogik als Sollverhalten festzuschreiben.
Das grundlegende Webhook-Routing wird zusaetzlich durch einen synthetischen
Symcon-Harness geprueft; sensible Debug-Ausgaben und unsichere Asset-Pfade sind
ausdruecklich nicht als erhaltenswerte Vertraege festgeschrieben.

1. Modul-/Property-/Variablen-/Methoden- und Hook-Vertraege maschinenlesbar
   erfassen.
2. Charakterisierungstests fuer DataFlow, Hook-Routing, Formularaufbereitung,
   HTML-Erzeugung und Import/Export ergaenzen.
3. CI an den zentralen Standard angleichen: Struktur, PHP 8.5, StylePHP,
   php-cs-fixer-Check und Tests als getrennt erkennbare Schritte.
4. Root- und Modul-Dokumentation auf den Ist-Stand bringen.

### Phase 1 - Sicherheitsgrenzen

1. TLS-Pruefung in Store/Sync aktivieren und Fehlerbehandlung ergaenzen.
2. Kennwoerter und private Payloads aus Logs entfernen; `DebugHelper` nutzen.
3. Webhook-Pfade kanonisch begrenzen, Query-Verarbeitung und HTTP-Antworten
   haerten; `HttpResponseHelper`/`VisualizationAssetHelper` gezielt integrieren.
4. CORS-, Authentisierungs- und Schreibberechtigungsmodell dokumentieren und
   testen.

### Phase 2 - Symcon 9.0 / PHP 8.5 stabilisieren

1. Laufzeittests auf einer Symcon-9-Testinstanz fuer alle 13 Module definieren.
2. Type Hints, Arrayzugriffe, JSON-Fehler, Buffer und Netzwerkfehler schrittweise
   haerten, ohne oeffentliche Signaturen unkontrolliert zu aendern.
3. Kompatibilitaetsangaben erst nach erfolgreicher Laufzeitmatrix setzen.
4. Migrationen fuer jede unvermeidbare Vertragsaenderung vor der Aenderung
   spezifizieren und testen.

### Phase 3 - Helper-Integration

1. Zuerst risikoarme Querschnittsfunktionen: Debug und HTTP-Antworten.
2. Danach Assets, Variablenzugriffe und persistente JSON-Caches.
3. Form-, Responsive- und Theme-Helper pro Pilotmodul einfuehren; Verhalten vor
   und nach der Umstellung vergleichen.
4. Nur nach erfolgreicher Wiederverwendung generalisierbare JSLive-Funktionen
   in `SymconDevelopment` bzw. `Symcon_ModuleHelper` vorschlagen.

### Phase 4 - Frontend konsolidieren

1. Nutzung jeder Bibliothek und jedes Plugins je Modul erfassen.
2. Externe CDN-Ressourcen lokal, versioniert und lizenzdokumentiert bereitstellen.
3. Unbenutzte oder doppelte Assets entfernen, danach Bibliotheken einzeln mit
   visuellen Regressionstests aktualisieren.

### Phase 5 - IPSView und Kacheldarstellung

1. Bestehenden `IPSView`-/`Output`-/Link-Vertrag als Kompatibilitaetsschicht
   dokumentieren und testen.
2. Ein einfaches Modul als Pilot fuer native WebContent-Darstellung und die
   Symcon-9.1-HTML-SDK-Kachel waehlen.
3. Konfigurationsmoeglichkeiten und Theme-/Responsive-Helper am Pilot pruefen.
4. Erst nach Abnahme modulweise migrieren; IPSView nicht vorzeitig entfernen.

### Phase 6 - Fachliche Weiterentwicklung

Neue Funktionen, UI-Erweiterungen und weitergehende Architekturarbeiten folgen
erst nach den Sicherheits-, Kompatibilitaets- und Migrationsgrundlagen.

## 13. Offene Entscheidungen

- Welche der 13 Module werden produktiv noch benoetigt, und welche werden nur
  kompatibel erhalten oder stillgelegt?
- Soll `ConfigStore` weiter betrieben werden, und wer betreibt/dokumentiert den
  externen Dienst?
- Welche IPSView-Versionen und vorhandenen Projekte muessen als reale
  Regressionstestfaelle dienen?
- Welche Browser und Geraeteklassen sind fuer die Kacheldarstellung verbindlich?
- Welches kleine Visualisierungsmodul eignet sich als erster Pilot? Aufgrund der
  begrenzten Komplexitaet sind AdvTextfield oder Progressbar naheliegende
  Kandidaten; die Entscheidung folgt nach realen Nutzungsdaten.
