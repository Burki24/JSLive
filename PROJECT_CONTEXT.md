# PROJECT_CONTEXT - JSLive

Stand dieser Bestandsaufnahme: 27.09.2026, Branch `dev`, Ausgangscommit
`25fd163340dac13cafdb5b39d32dd14cada2cbb1`. Am 28.09.2026 wurde die
Bestandsaufnahme um die beschlossene Entfernung des ConfigStore fortgeschrieben.

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
- Bibliotheksversion: `0.10`, Build 35. Die Version wurde als isolierter
  Bootstrap vom historischen Stand `0.9.9.9` auf das kuenftige Schema
  `Hauptversion.Nebenstand` umgestellt. Der vorbereitete Metadatenworkflow wird
  diese drei Felder nach dem ersten nicht vom Bot erzeugten Push auf `dev`
  aktualisieren.
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
| `SymconJSLiveSyncModule` | 3 | Synchronisation ausgewaehlter Konfigurationsparameter zwischen Instanzen |

Die zehn Visualisierungsmodule verwenden die gemeinsame Basisklasse
`SymconJSLive/libs/JSLiveModule.php`. Das `SyncModule` ist ein eigenstaendiges
Sondermodul und nicht Teil der Splitter-Datenverbindung.

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

- alle 12 verbliebenen Modul-IDs, Praefixe und die beiden Data-IDs;
- der Hook-Pfad `/hook/JSLive` und seine Unterpfade;
- die JSON-Umschlaege `DataID`, `Buffer`, `Type`, `InstanceID` sowie die
  bestehenden Befehlsnamen, einschliesslich historischer Schreibweisen;
- registrierte Properties, gespeicherte Listen/JSON-Strukturen und Buffer;
- die Variablen-Idents `Output`, `IPSView`, `Content`, `Period`, `Now`,
  `Relativ`, `Offset` und `StartDate`;
- oeffentliche Modulmethoden fuer Links, Konfigurationsimport/-export,
  Formaktualisierung, Datenabfragen, Aktionen und Synchronisation;
- vorhandene Template- und Assetpfade.

Der Konfigurationsimport kann Skripte anlegen und Instanzkonfigurationen
ueberschreiben. Das `SyncModule` kann fremde Instanzkonfigurationen setzen und
`ApplyChanges` ausloesen. Diese Pfade brauchen vor jeder Modernisierung eigene
Sicherheits- und Regressionstests.

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
`HexToRGB` und `isAssoc` in beiden Basisklassen. Das `SyncModule` besitzt
zusaetzlich einen eigenen HTTPS-Client sowie serialisierende
`SetBuffer`-/`GetBuffer`-Wrapper. Eine Uebernahme in zentrale Helper ist erst
sinnvoll, wenn der Vertrag generalisierbar und durch Tests belegt ist.

`SymconDevelopment` enthaelt keine konkurrierenden fachlichen JSLive-Module.
Die Ueberschneidung liegt bei Standards, CI und Helper-Infrastruktur.

## 7. Tests und CI

Vorhandene lokale Pruefungen:

- `tests/validate_structure.php`: Bibliothek, 12 Module, Modul-READMEs,
  Metadaten, Helper-Konfiguration, CodeQL-Sprache sowie die feste Einbindung
  der gemeinsamen Style- und Metadatenworkflows;
- `tests/test_update_library_metadata.py`: Erhoehung der gemeinsamen
  Library-Version ab `0.10`, Build-Ableitung aus dem Quell-SHA, Commit-Zeit und
  Schutz vor einer Rueckstufung;
- `tests/public-contracts.php`: maschinenlesbare Charakterisierung der 12
  verbliebenen Modulvertraege, ihrer PHP-Quellen und der bestehenden Hook-Pfade;
- `tests/webhook-routing.php`: Verhaltens-Harness fuer Authentisierungs- und
  Instanz-Gates, Data-ID/JSON-Umschlag, direkte globale Konfiguration und den
  historischen Standardbefehl `getContend`; zusaetzlich sichert er die
  Auslieferung erlaubter JavaScript-Assets und die Abweisung kanonischer
  Pfadausbrueche ab;
- `tests/configuration-transfer.php`: vollstaendiger und formulargefilterter
  Export, erfolgreicher Import bekannter Properties mit Erhalt ausgelassener
  Werte sowie nebenwirkungsfreie Ablehnung leerer, fehlerhafter,
  unvollstaendiger und modulfremder Importe;
- `tests/configuration-form.php`: reales Calendar-Formular mit initialer und
  dynamischer Auswertung von `viewlevel`, `viewdisable`,
  `viewlevelexactly` und `requireItem` in verschachtelten Strukturen;
- `tests/adv-textfield-rendering.php`: gebuendeltes und skriptbasiertes
  AdvTextfield-Template mit zweistufiger Platzhalterverarbeitung,
  CSS-Farb-/Fontaufbereitung sowie cachefreiem, gecachtem und neu aufgebautem
  HTML-Ergebnis;
- `tests/radar-chart-dates.php`: Datumsbereiche und Offset-Berechnung des
  RadarChart fuer alle acht Perioden in relativem und absolutem Modus sowie
  numerische Labels im Custom-Data-Pfad;
- `tests/chart-dates.php`: Datumsbereiche und Offset-Berechnung des Chart fuer
  alle acht Perioden in beiden Zeitmodi sowie numerische Webhook-Querywerte fuer
  Dataset- und Variablen-IDs;
- `tests/custom-data.php`: RGBA-Aufbereitung numerischer Stringwerte und sichere
  Ablehnung eines unbekannten numerischen Webhook-Objektparameters im
  Custom-Modul, ohne schreibende Aktionen auszufuehren;
- `tests/data-flow-integration.php`: statische Charakterisierung der
  DataFlowHelper-Anbindung;
- `tests/helper_integrity.py`: Versionen, Hashes und Vollstaendigkeit der
  vendorten Helper;
- PHP-Syntaxcheck aller PHP-Dateien.

GitHub Actions fuehrt die Tests ueber
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0` mit PHP 8.5 aus. CodeQL prueft
JavaScript/TypeScript. Tests, StylePHP und CodeQL waren fuer den aktuellen
Commit erfolgreich.

Das offizielle `symcon/StylePHP`-Repository ist als `.style`-Submodul auf Commit
`ec73bf742e18b049ad5c90de09033987d3ce671e` eingebunden. Der erste lokale
php-cs-fixer-Prueflauf mit dieser Konfiguration weist 20 von 23 erfassten
PHP-Dateien als noch zu formatieren aus. Als erste isolierte Gruppe wurden die
Testquellen mechanisch formatiert: Die damals vorhandenen acht PHP-Dateien unter
`tests` bestehen den StylePHP-Prueflauf; Vertrags- und Verhaltenstests bleiben
unveraendert gruen. Als erste Produktionsgruppe folgen die verwandten Einzelwertmodule
`SymconJSLiveGauge` und `SymconJSLiveProgressbar`. Beide bestehen nun ebenfalls
den StylePHP-Prueflauf; die oeffentlichen Vertraege bleiben unveraendert. Als
zweite Produktionsgruppe wurden `SymconJSLiveColorPicker` und
`SymconJSLiveDateTimePicker` entsprechend bearbeitet. Danach wurde
`SymconJSLiveAdvTextfield` einzeln formatiert und mit seinem gezielten
Rendering-Harness geprueft. Anschliessend wurde die sicherheitsnahe Basisklasse
`SymconJSLive/libs/WebHookModule.php` isoliert formatiert und mit dem
Webhook-Routing-Harness geprueft. Danach wurde die zentrale Kindmodul-Basisklasse
`SymconJSLive/libs/JSLiveModule.php` isoliert formatiert und mit den Harnesses
fuer Konfigurationstransfer, Formulare, Rendering und DataFlow geprueft. Danach
wurde der zentrale Splitter `SymconJSLive/module.php` isoliert formatiert und mit
Webhook-Routing, DataFlow und Vertragspruefung abgesichert. Danach wurde
`SymconJSLiveDoughnutPie` einzeln formatiert und gegen den Vertrags-Snapshot und
die Gesamtsuite geprueft. Danach wurde `SymconJSLiveCalendar` isoliert formatiert
und mit seinem realen Konfigurationsformular-Harness sowie dem Vertrags-Snapshot
geprueft. Danach wurde `SymconJSLiveSyncModule` isoliert formatiert und gegen den
Vertrags-Snapshot sowie die Gesamtsuite geprueft. Danach wurde
`SymconJSLiveRadarChart` isoliert formatiert. Die durch `strict_types` notwendigen
Integer-Konvertierungen der `DateTime`-Bestandteile sind durch einen neuen Test
fuer alle Perioden und beide Zeitmodi abgesichert. Danach wurde
`SymconJSLiveChart` entsprechend isoliert formatiert. Sein neuer Test deckt
zusaetzlich den konfigurierten Startzeitpunkt und numerische Webhook-Querywerte
ab. Danach wurde `SymconJSLiveCustom` isoliert formatiert und seine numerischen
Konfigurations- und Webhook-Grenzen ohne schreibende Aktionen geprueft. Der
spaeter als nicht mehr betreibbar bewertete `SymconJSLiveConfigStore` wurde am
28.09.2026 einschliesslich seiner isolierten Tests aus der Library entfernt.
Damit ist der verbliebene Produktionsbestand vollstaendig formatiert. Der
repositoryweite Prueflauf findet keine verbleibende StylePHP-Abweichung. Der
verpflichtende Workflow `.github/workflows/style.yml` fuehrt nun bei Pushes,
Pull Requests und manueller Ausloesung den gemeinsamen Check
`Burki24/Symcon_ModuleCI/style@v1.0.0` aus. Die Strukturpruefung sichert diese
Versionierung ab. Der erste GitHub-Lauf deckte zusaetzlich 41 bislang nicht nach
StylePHP formatierte JSON-Dateien auf. Sie wurden mit dem offiziellen
`json-check.php fix` rein mechanisch formatiert; ein kanonischer Inhaltsvergleich
gegen den Ausgangscommit bestaetigt unveraenderte JSON-Daten.

Noch nicht abgedeckt sind Symcon-Laufzeitverhalten, Webhook-Authentisierung und
Pfadbehandlung, modulspezifische Formulardynamik ausserhalb des gemeinsamen
Metadatenvertrags, Migrationen, Renderausgaben der weiteren Module,
Browserbibliotheken, einzelne Modulaktionen sowie der Sync-Netzwerkpfad.

## 8. Symcon-9-/PHP-8.5-Stand

- Die vorhandenen Tests laufen lokal und in der CI unter PHP 8.5.
- Der zuvor problematische Datenfluss verwendet kein `utf8_encode` mehr.
- Die Aktivierung von `strict_types` in `JSLiveModule.php` deckte eine implizite
  Float-zu-Integer-Konvertierung beim Aufruf von `srand()` auf. Der Seed wird nun
  explizit nach `int` konvertiert; der Formular-Harness sichert diesen Pfad ab.
- Die Aktivierung von `strict_types` in `SymconJSLiveRadarChart` erforderte
  explizite Integer-Konvertierungen fuer Datumsbestandteile aus
  `DateTime::format()` sowie explizite String-Konvertierungen fuer numerische
  Diagrammwerte. Der RadarChart-Test prueft alle Perioden in beiden Zeitmodi und
  den isolierten Custom-Data-Pfad auf erneute PHP-8.5-`TypeError`.
- `SymconJSLiveChart` benoetigt unter `strict_types` dieselben expliziten
  Datumskonvertierungen. Zusaetzlich werden der konfigurierte Startzeitpunkt,
  numerische Webhook-Querywerte und zuvor implizit konvertierte Variablenwerte
  an ihren bestehenden Typgrenzen explizit normalisiert.
- `SymconJSLiveCustom` normalisiert numerische Stringwerte fuer Alpha-Kanaele
  und Webhook-Objekt-IDs explizit. Der Test fuehrt dabei keine Variablen-,
  Skript- oder Medienschreiboperation aus.
- Das SyncModule erzwingt fuer seinen externen Dienst verifiziertes HTTPS,
  begrenzt Verbindungs- und Gesamtlaufzeit und gibt bei Transport-, HTTP- oder
  ungueltigen JSON-Antworten einen kontrollierten `success=false`-Fehler zurueck.
  Der negative Fehlerpfad ist lokal getestet; reale Dienst-, Schreib- und
  Symcon-Laufzeitpfade bleiben ungeprueft.
- Alle Module verwenden noch `IPSModule`; der Webhook basiert auf einer lokalen
  `WebHookModule`-Basisklasse. Symcon 9 empfiehlt fuer neue Module
  `IPSModuleStrict` und dessen native Hook-API. Eine Umstellung ist wegen der
  zahlreichen untypisierten oeffentlichen Methoden ein eigenes, in Phase 2
  ausdruecklich eingeplantes Migrationsprojekt.
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

Bereits abgesicherte Sicherheitsgrenzen:

- Die statische Asset-Auslieferung loest angeforderte Dateien kanonisch auf,
  akzeptiert nur regulaere Dateien innerhalb von `SymconJSLive/js` und weist
  relative Pfadausbrueche mit HTTP 404 ab. Ein Regressionstest belegt sowohl
  die regulaere Auslieferung als auch die Abweisung des frueher moeglichen
  Zugriffs auf `module.php` ueber `../`.

Prioritaet hoch:

- Der Webhook transportiert das Kennwort als Query-Parameter. Bekannte
  Zugangsdatenfelder werden im Debug maskiert; die bewusst vollstaendige
  Diagnose kann jedoch unbekannte Geheimnisse aus freien Texten, Skripten
  und Medien enthalten. Die oeffentliche Methode `Debug_LoadLogFile` gibt
  ausserdem die komplette Symcon-Logdatei ungefiltert aus.
- Import-, Store-, Custom- und Sync-Funktionen koennen Skripte erzeugen,
  Variablen/Medien schreiben, Skripte ausfuehren oder komplette
  Instanzkonfigurationen anwenden.

Prioritaet mittel:

- manuelles Query-Parsing, rohe Header-/Echo-Antworten, breite CORS-Freigabe,
  ungepruefte Server-Arrayzugriffe und `rand()` fuer Kennwoerter;
- serialisierte PHP-Daten in Buffern und `unserialize` ohne erlaubte Klassen;
- grosse Basisklasse, duplizierte Hilfsfunktionen und sehr grosse Moduldateien;
- `LoadConnectAddress` enthaelt einen bedingungslosen fruehen Rueckgabepfad und
  kann die ermittelte Connect-URL derzeit nicht zurueckgeben;
- die MessageSink-Verwaltung registriert bereits bekannte Variablen erneut,
  weil sie vor der Mitgliedschaftspruefung aus der Altliste entfernt werden;
- `SymconJSLiveDateTimePicker::LoadOtherConfiguration()` liest die nicht
  registrierte Property `Variables`; eine Korrektur benoetigt einen getrennten
  Verhaltens- und Regressionstest;
- `SymconJSLiveDoughnutPie::GetData()` prueft neue Variablen gegen die falsche
  Liste und verwendet `array_column()` auf einer Liste skalarer Variablen-IDs;
  der Pfad benoetigt vor einer Korrektur einen gezielten Datensatztest;
- `SymconJSLiveRadarChart::GetCorrectStartDate()` und
  `SymconJSLiveChart::GetCorrectStartDate()` verwenden in mehreren absoluten
  Perioden das Jahr eines neu erzeugten `DateTime`-Objekts statt durchgaengig das
  Jahr des uebergebenen Zeitstempels. Dieses bestehende Zeitverhalten benoetigt
  vor einer fachlichen Korrektur eine eigene Entscheidung und Regressionstests;
- Das SyncModule bezieht seine Modultyp-Liste weiterhin von
  `jslive.babenschneider.net`, obwohl der Dienst nicht mehr verfuegbar ist. Die
  eigentliche Synchronisierung arbeitet lokal; ueber Behalten oder Entfernen ist
  nach Klaerung des Bedarfs an dauerhafter Instanzsynchronisierung zu entscheiden.
- Fuer den geplanten einmaligen Export und Import fertig konfigurierter
  Chart-Ansichten ist das SyncModule nicht erforderlich. Sein eigener Nutzen
  besteht ausschliesslich in der fortlaufenden Master-/Slave-Synchronisierung.
  Dieser Schreibpfad besitzt noch keinen Verhaltenstest unter Symcon 9; zudem
  sind Mehrfach-Master, Fehlerwiederanlauf und der Erhalt gespeicherter
  Parameterauswahlen unzureichend abgesichert.

Dokumentationsluecken:

- Die Root-README beschreibt nun alle 12 verbliebenen Module, den
  Modernisierungsstatus, Installation, CI und bekannte
  Sicherheits-/Betriebseinschraenkungen;
- Alle verbliebenen Module besitzen eine README. Die zuvor fehlenden Dateien
  wurden aus dem tatsaechlichen Modulvertrag erstellt; die vorhandenen
  README-Dateien wurden anschliessend auf korrekte Titel, GUIDs, Zielplattform,
  Datenvertraege und bekannte Frontend-Einschraenkungen synchronisiert.
- Datenfluss, Hook-Protokoll, oeffentliche Methoden, Migrationsregeln,
  Frontend-Lizenzen und das Sync-Protokoll sind nicht vollstaendig
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
Export, sichere Import-Ablehnung und erfolgreicher Import bekannter Properties
sind ebenfalls charakterisiert. Die zuvor fehlerhafte Schluesselpruefung des
Imports ist korrigiert und durch einen gezielten Regressionstest abgesichert.
Der gemeinsame dynamische Formularvertrag ist anhand des realen
Calendar-Formulars fuer initiale Darstellung und Live-Aktualisierung
charakterisiert.
Die zweistufige HTML-Erzeugung ist fuer AdvTextfield als erstes Pilotmodul mit
Template-, Platzhalter-, CSS- und Cache-Verhalten charakterisiert.

1. Modul-/Property-/Variablen-/Methoden- und Hook-Vertraege maschinenlesbar
   erfassen.
2. Charakterisierungstests fuer DataFlow, Hook-Routing, Formularaufbereitung,
   HTML-Erzeugung und Import/Export ergaenzen.
3. CI an den zentralen Standard angleichen:
   - offizielles `.style`-Submodul anbinden und Altbestand messen;
   - Testquellen als erste mechanische Gruppe formatieren und pruefen
     (abgeschlossen);
   - `Gauge` und `Progressbar` als erste Produktionsgruppe formatieren und
     pruefen (abgeschlossen; Symcon-Laufzeitpruefung weiterhin ausstehend);
   - `ColorPicker` und `DateTimePicker` als zweite Produktionsgruppe formatieren
     und pruefen (abgeschlossen; Symcon-Laufzeitpruefung weiterhin ausstehend);
   - `AdvTextfield` einzeln formatieren und mit dem Rendering-Harness pruefen
     (abgeschlossen; Symcon-Laufzeitpruefung weiterhin ausstehend);
   - `WebHookModule.php` isoliert formatieren und mit dem Routing-Harness pruefen
     (abgeschlossen; Sicherheits- und Symcon-Laufzeitpruefung ausstehend);
   - `JSLiveModule.php` isoliert formatieren und mit Konfigurations-, Formular-,
     Rendering- und DataFlow-Harnesses pruefen (abgeschlossen; ein notwendiger
     expliziter `srand()`-Seed-Cast ist enthalten, Symcon-Laufzeitpruefung steht
     weiterhin aus);
   - den zentralen Splitter isoliert formatieren und mit Webhook-, DataFlow- und
     Vertragspruefungen absichern (abgeschlossen; Sicherheits- und
     Symcon-Laufzeitpruefung ausstehend);
   - `DoughnutPie` einzeln formatieren und gegen Vertrags-Snapshot und
     Gesamtsuite pruefen (abgeschlossen; Rendering-, Browser- und
     Symcon-Laufzeitpruefung ausstehend);
   - `Calendar` isoliert formatieren und mit Formular- und Vertragspruefung
     absichern (abgeschlossen; ICS-, Event-, Browser- und
     Symcon-Laufzeitpruefung ausstehend);
   - `SyncModule` isoliert formatieren und gegen Vertrags-Snapshot und
     Gesamtsuite pruefen (abgeschlossen; TLS-Haertung und negativer
     Transporttest ebenfalls abgeschlossen; reale Dienst- und
     Symcon-Laufzeitpruefung ausstehend);
   - `RadarChart` isoliert formatieren und seine Datums- und Offset-Berechnung
     fuer PHP 8.5 absichern (abgeschlossen; Archivdaten-, Rendering-, Browser-
     und Symcon-Laufzeitpruefung ausstehend);
   - `Chart` isoliert formatieren und Datums-, Offset- und Webhook-Querygrenzen
     fuer PHP 8.5 absichern (abgeschlossen; Archivdaten-, Rendering-, Browser-
     und Symcon-Laufzeitpruefung ausstehend);
   - `Custom` isoliert formatieren und numerische Konfigurations- und
     Webhook-Grenzen fuer PHP 8.5 absichern (abgeschlossen; schreibende Objekt-,
     Skript-, Medien-, Browser- und Symcon-Laufzeitpruefung ausstehend);
   - bestehenden PHP-Code schrittweise mit Vertrags- und Verhaltenstests
     formatieren (abgeschlossen; repositoryweit keine Style-Abweichungen);
   - `Symcon_ModuleCI/style@v1.0.0` als verpflichtenden, getrennt erkennbaren
     Workflow aktivieren (abgeschlossen; der erste Lauf deckte 41
     JSON-Styleabweichungen auf);
   - alle vom offiziellen StylePHP-JSON-Pruefer erfassten Dateien mechanisch
     formatieren und ihre kanonischen Inhalte vergleichen (abgeschlossen; CI
     erfolgreich).
4. Root- und Modul-Dokumentation auf den Ist-Stand bringen:
   - Root-README mit allen verbliebenen Modulen, Zielplattform, Installation, CI und
     bekannten Einschraenkungen aktualisieren (abgeschlossen);
   - fehlende Modul-READMEs erstellen und die sieben vorhandenen Modul-READMEs
     mit dem tatsächlichen Modulvertrag synchronisieren (abgeschlossen).
5. Die automatische Library-Versionierung nach dem freigegebenen Vorbild von
   `OpenHomeAlarm` einfuehren:
   - den Uebergang von der historischen Vierkomponenten-Version `0.9.9.9` auf
     das gemeinsame Schema `Hauptversion.Nebenstand` mit `0.10` als isolierten
     Bootstrap vollziehen (umgesetzt; `0.10` ist nach PHP-/Symcon-
     Versionsvergleich neuer als `0.9.9.9`);
   - Workflow, Python-Updater und Regressionstest aus dem Referenzverfahren
     projektspezifisch uebernehmen (lokal umgesetzt und geprueft);
   - bei nicht vom Bot erzeugten Pushes nach `dev` den Nebenstand pro
     Quellcommit erhoehen, `build` aus den ersten sieben Zeichen des Quell-SHA
     und `date` aus dessen Commit-Zeit bilden;
   - den Workflow durch Concurrency und Erkennung des eigenen
     `CHORE: Update library metadata`-Commits gegen Schleifen absichern und vor
     jeder Metadatenaktualisierung die gemeinsame Testsuite ausfuehren;
   - fuer den schreibenden Bot die vorhandene GitHub-App-Konvention mit
     `HELPER_SYNC_APP_CLIENT_ID` und `HELPER_SYNC_APP_PRIVATE_KEY` verwenden;
     die App `Burki24 Helper Sync` ist fuer JSLive installiert, die Pruefung am
     27.09.2026 ergab jedoch, dass beide Repository-Eintraege noch fehlen und
     vor dem ersten Push des Workflows einzurichten sind;
   - die generierten Felder in `library.json` nicht mehr manuell pflegen und
     diese Regel in `AGENTS.md`, Strukturtests und Projektdokumentation sichern;
   - ein `CHANGELOG.md` mit einem dauerhaft gepflegten Abschnitt `Unreleased`
     einfuehren; jeder Release benennt dort Library-Version,
     Veroeffentlichungsdatum und die wesentlichen Aenderungen;
   - einen projektspezifischen Release-Prozess dokumentieren: Metadaten-Bot auf
     `dev` abwarten, exakten Kandidaten pruefen, `Unreleased` auf die
     Release-Version umstellen, `dev` per Pull Request nach `main` uebernehmen,
     den unveraenderten `main`-Commit erneut pruefen und erst danach Tag und
     GitHub Release erzeugen;
   - Git-Tags nach dem OpenHomeAlarm-Schema als
     `v<Library-Version>.0` bilden und weder Tags noch Releases nachtraeglich
     verschieben oder ueberschreiben;
   - das Merge-Ergebnis aus `main` einschliesslich des Merge-Commits nach jedem
     Release wieder nach `dev` synchronisieren. Der dadurch folgende
     Metadaten-Bot-Commit eroeffnet den naechsten Entwicklungsstand auf `dev`;
   - `main`, Tags und GitHub Releases weiterhin nur ueber den ausdruecklichen
     Release-Prozess aktualisieren; die Metadatenautomatik veroeffentlicht
     selbst keinen Release.
6. Das Verfahren nach erfolgreicher JSLive-Einfuehrung auf alle Burki24-
   Modulbibliotheken ausrollen:
   - die gemeinsame Versionierungs-, Changelog-, Release- und
     Ruecksynchronisierungsregel in `SymconDevelopment` verbindlich
     dokumentieren;
   - je Repository Ausgangsversion, Branch-Modell, vorhandene Tags,
     GitHub-App-Zugriff und projektspezifische Release-Gates inventarisieren;
   - jedes Repository einzeln mit Updater-Test, Strukturpruefung und erstem
     beobachtetem Bot-Lauf migrieren, statt alle Repositories gleichzeitig
     umzuschalten;
   - innerhalb von JSLive gilt eine zentrale Library-Version gemeinsam fuer
     alle enthaltenen Module; es werden keine voneinander abweichenden
     Modulversionen eingefuehrt.

### Phase 1 - Sicherheitsgrenzen

1. TLS-Pruefung im SyncModule aktivieren und Fehlerbehandlung ergaenzen
   (abgeschlossen; der nicht mehr verfuegbare Dienst wird nicht weiter
   integriert und die Zukunft des SyncModule separat entschieden).
2. Debug-Ausgaben aller Module ueber `DebugHelper` fuehren. Die bewusst
   aktivierbare Diagnose enthaelt vollstaendige Browser-, Konfigurations- und
   Austausch-Payloads einschliesslich freier Texte, einzelner Messwerte,
   Skripte und Medien ohne Laengenbegrenzung. Bekannte Zugangsdatenfelder
   werden strukturiert maskiert, auch in eingebettetem JSON und Base64-JSON.
   Zugangsdaten in beliebigem Freitext oder Skriptcode koennen nicht verlaesslich
   erkannt werden; Debug bleibt deshalb standardmaessig ausgeschaltet und
   darf nur in einer geschuetzten Testumgebung aktiviert werden.
3. Statische Webhook-Assetpfade kanonisch auf `SymconJSLive/js` begrenzen
   (abgeschlossen; erlaubte Dateien und relative Pfadausbrueche sind getestet).
   Der `VisualizationAssetHelper` ist fuer diesen historischen, dynamischen
   Webhook-Pfad nicht geeignet und bleibt der spaeteren Visualisierungs-
   umstellung vorbehalten.
4. Query-Verarbeitung und die verbliebenen HTTP-Antworten haerten;
   `HttpResponseHelper` gezielt weiter integrieren.
5. CORS-, Authentisierungs- und Schreibberechtigungsmodell dokumentieren und
   testen.

### Phase 2 - Symcon 9.0 / PHP 8.5 stabilisieren

1. Laufzeittests auf einer Symcon-9-Testinstanz fuer alle verbliebenen Module
   definieren.
2. Die Migration von `IPSModule` auf `IPSModuleStrict` als eigenes Vorhaben
   vorbereiten. Vor jeder Codeaenderung sind die notwendigen Type Hints aller
   oeffentlichen Methoden sowie die geaenderten Vertraege fuer
   Variablenregistrierung/-schreibzugriff, Parent-Automatik, Datenfluss und
   Hooks zu erfassen und durch Tests zu sichern.
3. Das direkt von `IPSModule` erbende Sondermodul `SyncModule` nur dann einzeln
   migrieren und in einer Symcon-9-Testinstanz pruefen, wenn sein Bedarf an
   dauerhafter Instanzsynchronisierung bestaetigt wurde.
4. Die gemeinsame Basisklasse `JSLiveModule` und ihre zehn Kindmodule in einem
   koordinierten, eigenen Schritt migrieren. Data-IDs, Parent-Verbindung,
   Datenkodierung, Variablen-Idents, Actions und oeffentliche PHP-Aufrufe muessen
   dabei kompatibel bleiben.
5. Den Splitter erst nach den Webhook-Sicherheits- und Pfadtests von der lokalen
   `WebHookModule`-Basisklasse auf die native Hook-API von `IPSModuleStrict`
   umstellen. Hook-Pfad, Authentisierung und bestehende Browseraufrufe sind als
   Migrationsvertraege zu erhalten.
6. Type Hints, Arrayzugriffe, JSON-Fehler, Buffer und Netzwerkfehler schrittweise
   haerten, ohne oeffentliche Signaturen unkontrolliert zu aendern.
7. Kompatibilitaetsangaben erst nach erfolgreicher Laufzeitmatrix setzen.
8. Migrationen fuer jede unvermeidbare Vertragsaenderung vor der Aenderung
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

1. Den vorhandenen lokalen Konfigurationsimport/-export vor einer Erweiterung
   charakterisieren und absichern. Das Austauschformat erhaelt eine Version;
   installationsgebundene Werte werden ausgeschlossen und Skripte nur nach
   ausdruecklicher Auswahl uebertragen.
2. `SymconJSLiveChart` als Pilot fuer eine lokale Aktion
   **Konfiguration verteilen** verwenden. Die geoeffnete Instanz dient als
   Quelle; kompatible Zielinstanzen werden automatisch mit Name und Objektpfad
   angeboten und koennen gemeinsam ausgewaehlt werden.
3. Vor der Verteilung auswaehlbare Konfigurationsbereiche, eine
   Aenderungsvorschau und eine ausdrueckliche Bestaetigung bereitstellen. Das
   Ergebnis wird pro Zielinstanz ausgewiesen; die vorherige Konfiguration wird
   fuer eine kontrollierte Ruecksicherung aufbewahrt.
4. Dasselbe versionierte Austauschformat fuer benannte lokale Vorlagen sowie
   Datei-Export und Datei-Import fertig konfigurierter Chart-Ansichten
   verwenden. Ein externer Dienst ist dafuer nicht vorgesehen.
5. Zunaechst keine automatische Dauersynchronisierung einfuehren. Erst nach der
   Abnahme des Chart-Piloten entscheiden, ob die lokale Verteilung auf weitere
   Module ausgerollt wird und ob das bisherige `SyncModule` noch einen eigenen
   produktiven Anwendungsfall besitzt oder entfernt werden kann.

## 13. Entschiedene Punkte und offene Entscheidungen

- Der einmalige Versionsuebergang von `0.9.9.9` auf `0.10` wurde als getrennter
  Bootstrap committed und erfolgreich in der CI geprueft.
- Die GitHub-App-Variable `HELPER_SYNC_APP_CLIENT_ID` und das Secret
  `HELPER_SYNC_APP_PRIVATE_KEY` fehlen im JSLive-Repository. Die App
  `Burki24 Helper Sync` ist bereits installiert; die beiden Eintraege bleiben
  das externe Gate vor dem ersten Push des schreibenden Workflows.
- Welche der 12 verbliebenen Module werden produktiv noch benoetigt, und welche
  werden nur kompatibel erhalten oder stillgelegt?
- Der `ConfigStore` wird nicht weiter betrieben. Seine Modulimplementierung,
  Tests und Dokumentation wurden entfernt; vorhandene Instanzen muessen vor dem
  Update geloescht werden. Die Entscheidung ist in
  `docs/adr/0001-remove-config-store.md` dokumentiert.
- Fuer fertig konfigurierte Chart-Ansichten wird nach Abschluss der vorherigen
  Modernisierungsphasen die in Phase 6 beschriebene lokale,
  dienstunabhaengige Verteilung mit gemeinsamer Export-/Importbasis umgesetzt.
- Wird die automatische dauerhafte Master-/Slave-Synchronisierung des
  `SyncModule` produktiv benoetigt? Fuer einmaligen Export, Import oder Kopieren
  ist das Modul nicht erforderlich.
- Welche IPSView-Versionen und vorhandenen Projekte muessen als reale
  Regressionstestfaelle dienen?
- Welche Browser und Geraeteklassen sind fuer die Kacheldarstellung verbindlich?
- Welches kleine Visualisierungsmodul eignet sich als erster Pilot? Aufgrund der
  begrenzten Komplexitaet sind AdvTextfield oder Progressbar naheliegende
  Kandidaten; die Entscheidung folgt nach realen Nutzungsdaten.
