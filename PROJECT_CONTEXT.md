# PROJECT_CONTEXT - JSLive

Stand dieser Bestandsaufnahme: 27.09.2026, Branch `dev`, Ausgangscommit
`25fd163340dac13cafdb5b39d32dd14cada2cbb1`. Am 28.09.2026 wurde die
Bestandsaufnahme um die beschlossene Entfernung von ConfigStore und SyncModule
fortgeschrieben. Am 30.09.2026 wurde auch das Calendar-Modul einschliesslich
seiner spezifischen Webhook-Pfade und Frontend-Assets entfernt. Am 01.10.2026
wurden der lokale und der entfernte `dev`-Stand zuletzt auf Commit
`7c5a15aa648b67b8e22441cb8f6cf4e37744a4ea` abgeglichen und die nachfolgenden
Statusangaben nach der `IPSModuleStrict`-Laufzeitabnahme aktualisiert.

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

- Arbeitsbranch: `dev`; vor diesem Kompatibilitaetsschritt waren Arbeitsbaum,
  lokaler Branch und `origin/dev` auf `7c5a15a` synchron.
- `dev` lag zu diesem Zeitpunkt 148 Commits vor `main` und vor
  `upstream/main`; beide enthielten keine Commits, die `dev` fehlten.
- `upstream/Beta` ist ein historischer Vorfahr und lag 235 Commits hinter
  `dev`, ohne eigene Abweichung.
- `library.json` stand auf Version `0.65`, Build `40106253`. Version, Build und
  Datum werden auf `dev` vom Metadatenworkflow aus dem jeweiligen Quellcommit
  erzeugt und nicht manuell gepflegt. Der einmalige Bootstrap vom historischen
  Stand `0.9.9.9` auf das Schema `Hauptversion.Nebenstand` begann mit `0.10`.
- Gegenueber `main` enthaelt `dev` neben CI, Tests und zentral bezogenen
  Helpern inzwischen die dokumentierten Sicherheitsgrenzen, PHP-8.5-
  Korrekturen sowie die beschlossene Entfernung von ConfigStore, SyncModule
  und Calendar. Ein erster modernisierter Release wurde noch nicht erstellt.

## 3. Module

| Modul | Typ | Aufgabe |
| --- | ---: | --- |
| `SymconJSLive` | 2 | Splitter, Webhook `/hook/JSLive`, Asset-Auslieferung, gemeinsame Links und Konfiguration |
| `SymconJSLiveAdvTextfield` | 3 | Erweitertes Textfeld |
| `SymconJSLiveChart` | 3 | Linien-/Balkendiagramme und Archivdaten |
| `SymconJSLiveColorPicker` | 3 | Farbauswahl und Rueckschreiben von Werten |
| `SymconJSLiveCustom` | 3 | Benutzerdefinierte HTML-/JavaScript-Ausgaben und Objektaktionen |
| `SymconJSLiveDateTimePicker` | 3 | Datum-/Zeitauswahl und Rueckschreiben von Werten |
| `SymconJSLiveDoughnutPie` | 3 | Doughnut-/Tortendiagramme |
| `SymconJSLiveGauge` | 3 | Messinstrumente |
| `SymconJSLiveProgressbar` | 3 | Fortschrittsanzeigen und SVG-Import |
| `SymconJSLiveRadarChart` | 3 | Radardiagramme und Archivdaten |

Die neun Visualisierungsmodule verwenden die gemeinsame Basisklasse
`SymconJSLive/libs/JSLiveModule.php` und den gemeinsamen Splitter.

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
   `getUpdate`, `setData`, `getSVG` oder Konfigurationsimport/-export.
4. Das Kindmodul fordert gemeinsame Werte und Links mit `SendDataToParent` an.
5. Variablenaenderungen werden ueber MessageSink verarbeitet; Aktualisierungen
   koennen ueber den WebHook-Control-WebSocket an den Browser gepusht werden.

Die DataFlow-Kodierung wird in Splitter und Basisklasse bereits durch den
zentralen `DataFlowHelper` gekapselt. HTML, CSS und JavaScript werden weiterhin
ueber Templates, Platzhalter und grosse Property-Mengen erzeugt.

## 5. Oeffentliche Vertraege

Zu erhalten und vor Refactorings durch Charakterisierungstests abzusichern sind:

- alle 10 verbliebenen Modul-IDs, Praefixe und die beiden Data-IDs;
- der Hook-Pfad `/hook/JSLive` und seine Unterpfade;
- die JSON-Umschlaege `DataID`, `Buffer`, `Type`, `InstanceID` sowie die
  bestehenden Befehlsnamen, einschliesslich historischer Schreibweisen;
- registrierte Properties, gespeicherte Listen/JSON-Strukturen und Buffer;
- die Variablen-Idents `Output`, `IPSView`, `Content`, `Period`, `Now`,
  `Relativ`, `Offset` und `StartDate`;
- oeffentliche Modulmethoden fuer Links, Konfigurationsimport/-export,
  Formaktualisierung, Datenabfragen und Aktionen;
- vorhandene Template- und Assetpfade.

Der Konfigurationsimport kann Skripte anlegen und Instanzkonfigurationen
ueberschreiben. Dieser Pfad braucht vor jeder Modernisierung eigene Sicherheits-
und Regressionstests.

## 6. Helper-Abgleich mit SymconDevelopment

`.helper-sync.json` bezieht elf Helper-Pakete. Produktiv eingebunden sind
`ConfigurationFormHelper`, `DataFlowHelper`, `DebugHelper` und
`HttpResponseHelper`.

| Zentraler Helper | Heutiger lokaler Bestand bzw. moegliche Nutzung |
| --- | --- |
| `ConfigurationFormHelper` | Laedt und validiert in `JSLiveModule` den statischen `form.json`-Anteil; die charakterisierte dynamische Filter- und Reload-Logik bleibt lokal |
| `DataFlowHelper` | Bereits in Splitter und Kindmodul-Basisklasse integriert |
| `DebugHelper` | In Splitter, gemeinsamer Kindmodul-Basis und allen Kindmodulen integriert |
| `HttpResponseHelper` | Fuer die fuenf Plain-Text-Abbruchantworten des Splitters integriert |
| `PersistentJsonCacheHelper` | Nicht fuer die heute bewusst fluechtigen Laufzeitbuffer geeignet; eine Umstellung auf persistente Attribute benoetigt eine eigene Migration |
| `ResponsiveVisualizationHelper` | Kandidat fuer Viewport-/Iframe-CSS |
| `VariableHelper` | Passt nicht auf konfigurierte externe Objekt-IDs; eigene Strict-Modulvariablen verwenden bereits den nativen Ident-Zugriff |
| `VariablePresentationHelper` | Basis fuer native Darstellungen statt neuer Legacy-Profile |
| `VisualizationAssetHelper` | Passt nicht auf den historischen dynamischen Webhook-Pfad; bleibt einer spaeteren HTML-SDK-Visualisierung vorbehalten |
| `VisualizationThemeHelper` | Kandidat fuer gemeinsame Theme-CSS-Werte |
| `VisualizationThemeConfigurationHelper` | Kandidat fuer gemeinsame Theme-Konfiguration |

Lokale Doppelungen bestehen insbesondere bei `json_encode_advanced`,
`HexToRGB` und `isAssoc` in beiden Basisklassen. Eine Uebernahme in zentrale
Helper ist erst sinnvoll, wenn der Vertrag generalisierbar und durch Tests
belegt ist.

`SymconDevelopment` enthaelt keine konkurrierenden fachlichen JSLive-Module.
Die Ueberschneidung liegt bei Standards, CI und Helper-Infrastruktur.

## 7. Tests und CI

Vorhandene lokale Pruefungen:

- `tests/validate_structure.php`: Bibliothek, 10 Module, Modul-READMEs,
  Metadaten, Helper-Konfiguration, CodeQL-Sprache sowie die feste Einbindung
  der gemeinsamen Style- und Metadatenworkflows;
- `tests/test_update_library_metadata.py`: Erhoehung der gemeinsamen
  Library-Version ab `0.10`, Build-Ableitung aus dem Quell-SHA, Commit-Zeit und
  Schutz vor einer Rueckstufung;
- `tests/public-contracts.php`: maschinenlesbare Charakterisierung der 10
  verbliebenen Modulvertraege, ihrer PHP-Quellen und der bestehenden Hook-Pfade;
- `tests/webhook-routing.php`: Verhaltens-Harness fuer Authentisierungs- und
  Instanz-Gates, Data-ID/JSON-Umschlag, direkte globale Konfiguration und den
  historischen Standardbefehl `getContend`; zusaetzlich sichert er die
  Auslieferung erlaubter JavaScript-Assets und die Abweisung kanonischer
  Pfadausbrueche, URL-decodierte Querywerte mit eingebetteten
  Gleichheitszeichen, exakte Kennwortvergleiche und JavaScript-sichere
  `init.js`-Parameter einschliesslich ungueltiger UTF-8-Eingaben sowie die
  Statuscodes der Plain-Text-Abbruchpfade und Conditional Requests fuer
  statische Assets sowie gecachte Modulantworten ab. Der Test charakterisiert
  ausserdem die explizite JSON-Kennzeichnung der direkten globalen
  Konfiguration und aller etablierten JSON-Kindbefehle, die feste
  Kennzeichnung der uebrigen Antworttypen sowie den abgesicherten
  Downloadnamen des Konfigurationsexports. Ausserdem sind die
  Authentisierungsausnahmen, der kennwortgeschuetzte GET-Schreibpfad und das
  Verhalten bei leerem Splitter-Kennwort sowie die PHP-8.5-sichere
  Platzhalterersetzung numerischer Instanz-IDs charakterisiert;
- `tests/webhook-security-model.php`: exakte Bestandsaufnahme aller vom
  Browser erreichbaren Kindmodulbefehle und der Module mit `setData`;
- `tests/runtime-matrix.php`: Vollstaendigkeitspruefung der verbindlichen,
  ueber den Symcon-MCP erreichbaren IP-Symcon-9-Testebene, aller 10 Module und
  der erforderlichen Ergebnisnachweise;
- `tests/strict-module-migration.php`: exakte Inventur der 74 oeffentlichen
  Methodendeklarationen und ihrer vorgesehenen Type Hints sowie der Grenzen
  fuer Variablen, Parent-Verbindung, Datenfluss und native Hooks;
- `tests/configuration-transfer.php`: vollstaendiger und formulargefilterter
  Export, erfolgreicher Import bekannter Properties mit Erhalt ausgelassener
  Werte sowie nebenwirkungsfreie Ablehnung leerer, fehlerhafter,
  unvollstaendiger und modulfremder Importe;
- `tests/configuration-form.php`: reales Chart-Formular mit initialer und
  dynamischer Auswertung von `viewlevel` und `requireItem` in verschachtelten
  Strukturen sowie Integration des `ConfigurationFormHelper` fuer das statische
  Laden und Validieren von `form.json`;
- `tests/adv-textfield-rendering.php`: gebuendeltes und skriptbasiertes
  AdvTextfield-Template mit zweistufiger Platzhalterverarbeitung,
  CSS-Farb-/Fontaufbereitung sowie cachefreiem, gecachtem und neu aufgebautem
  HTML-Ergebnis;
- `tests/radar-chart-dates.php`: Datumsbereiche und Offset-Berechnung des
  RadarChart fuer alle acht Perioden in relativem und absolutem Modus sowie
  numerische Labels im Custom-Data-Pfad;
- `tests/radar-tooltip.js`: der aus der Standardvorlage geladene und in deren
  Chart-Konfiguration registrierte Tooltip-Callback mit sieben Faellen fuer
  Zahlenwerte, Formatierung und optionale Datensatznamen;
- `tests/moment-adapter.js`: echte alte/neue Moment-Distribution mit Chart.js
  und Adapter, Parsing, Formatierung, Kalendergrenzen und Zeitumstellung in
  UTC/Berlin sowie eindeutige Einbindung und Ladereihenfolge der Vorlagen;
- `tests/chart-dates.php`: Datumsbereiche und Offset-Berechnung des Chart fuer
  alle acht Perioden in beiden Zeitmodi sowie numerische Webhook-Querywerte fuer
  Dataset- und Variablen-IDs;
- `tests/chart-async-loading.js`: originale Template-Funktionen mit kontrollierter
  HTTP-Antwortreihenfolge, zwei/drei Datensaetzen, Teil-/Vollreload, leeren und
  fehlgeschlagenen Datensatzantworten sowie unveraendertem synchronen Ladepfad;
- `tests/custom-data.php`: RGBA-Aufbereitung numerischer Stringwerte und sichere
  Ablehnung eines unbekannten numerischen Webhook-Objektparameters im
  Custom-Modul, ohne schreibende Aktionen auszufuehren;
- `tests/data-flow-integration.php`: statische Charakterisierung der
  DataFlowHelper-Anbindung;
- `tests/child-routing-filter.php`: reale Data-Flow-Umschlaege fuer die eigene
  Zielinstanz und Broadcast `0` sowie Negativfaelle fuer fremde, aehnliche und
  nur im inneren `Buffer` passende Instanz-IDs;
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
geprueft; es wurde am 30.09.2026 gemaess ADR 0003 vollstaendig entfernt. Danach
wurde das damals noch enthaltene `SymconJSLiveSyncModule`
isoliert formatiert und gegen den Vertrags-Snapshot sowie die Gesamtsuite
geprueft; es wurde am 28.09.2026 nach separater Bewertung vollstaendig entfernt.
Danach wurde
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

Die verbindliche Symcon-Laufzeitmatrix, Webhook-Authentisierung und
Pfadbehandlung sind abgedeckt. Noch nicht vollstaendig automatisiert sind
modulspezifische Formulardynamik ausserhalb der vorhandenen Harnesses,
Migrationen, visuelle Regressionen der Browserbibliotheken sowie einzelne
schreibende Modulaktionen.

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
- Splitter, gemeinsame Kindmodul-Basis und alle neun Visualisierungsmodule sind
  koordiniert auf `IPSModuleStrict` umgestellt. Die 74 inventarisierten
  oeffentlichen Methoden besitzen vollstaendige Type Hints, die Kindmodule
  verwenden die automatische Parent-Kompatibilitaet und der Splitter die native
  Hook-API. Die lokale Test- und Syntaxpruefung sowie die erneute
  MCP-CURRENT-Laufzeitabnahme des Migrationsstands einschliesslich
  Dienstneustart sind bestanden.
- `library.json` deklariert nach der bestandenen Laufzeitmatrix IP-Symcon 9.0
  als Mindestversion.
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
Visualisierungsmodule, besonders Chart, Gauge und RadarChart, haben
umfangreiche, tief verschachtelte Formulare. Eigene Metafelder wie `viewlevel`,
`viewdisable`, `viewlevelexactly` und `requireItem` werden durch
`JSLiveModule.php` ausgewertet. Dieses Verhalten ist Teil des bestehenden
Formularvertrags.

Neben den dynamisch erzeugten `Output`-/`IPSView`-Variablen existieren nur wenige
Modulvariablen. Chart und RadarChart registrieren steuerbare Zeitraumvariablen,
AdvTextfield registriert `Content`. Eine Umstellung auf native Darstellungen muss
Idents, Datentypen, Aktionen und bestehende Anwenderkonfigurationen erhalten.

## 10. Frontend-Abhaengigkeiten

Die Nutzung je Modul, lokale und externe Ladewege, erkannte Versionen,
Lizenznachweise sowie unbenutzte und parallele Bestandsdateien sind in
`docs/FRONTEND_DEPENDENCIES.md` inventarisiert. Ein Regressionstest gleicht die
Ressourcenreferenzen der mitgelieferten Templates mit dieser Inventur ab.

Aktiv eingesetzt werden in den Standardvorlagen unter anderem Chart.js 4.5.1,
chartjs-plugin-datalabels 2.2.0, chartjs-plugin-streaming 3.1.0, Moment.js
2.31.0, jQuery 3.6.0, Canvas Gauges 2.1.7 und Loading Bar. Moment 2.27.0 bleibt
unter seinem alten Pfad fuer eigene Vorlagen erhalten. Die neun unbenutzten
Chart.js-3.x-/Plugin-Dateien sind mit JSLive 0.71 entfernt und abgenommen.
Die Altpfade fuer 4.3.3 und 4.4.1 bleiben fuer eigene Vorlagen unveraendert;
die lokal umgesetzte Umstellung der Standardvorlagen auf 4.5.1 ist ein
separater Versionsschritt. MCDatepicker und die beiden
unbenutzten CSS-Dateien `DateTimePicker1.css` und `font-face.css` wurden im
ersten Bereinigungsschritt entfernt. Migration, Referenzpruefung und Rueckfall
sind in `docs/FRONTEND_ASSET_MIGRATION.md` dokumentiert.

ColorPicker laedt die vorhandene iro.js-Version 5.5.0 nun lokal. Auch die 20
dynamisch ausgewaehlten Schriftdateien werden unveraendert als lokale
WOFF2-Dateien ueber den JSLive-Hook ausgeliefert. Ursprungs-URLs, SHA-256-Werte
und die familienbezogenen OFL-/Apache-Lizenztexte sind im Asset-Verzeichnis
dokumentiert. Es fehlen weiterhin ein Paketmanifest, ein dokumentierter
Buildprozess sowie automatisierte Browserpruefungen. Eine vorschnelle
Bereinigung ist nicht zulaessig, weil benutzerdefinierte Template-Skripte und
der historische HTMLBox-Pfad weitere lokale Assets verwenden koennen.

## 11. Technische Schulden und Risiken

Bereits abgesicherte Sicherheitsgrenzen:

- Die statische Asset-Auslieferung loest angeforderte Dateien kanonisch auf,
  akzeptiert nur regulaere Dateien innerhalb von `SymconJSLive/js` und weist
  relative Pfadausbrueche mit HTTP 404 ab. Ein Regressionstest belegt sowohl
  die regulaere Auslieferung als auch die Abweisung des frueher moeglichen
  Zugriffs auf `module.php` ueber `../`.
- Webhook-Querywerte werden an einer gemeinsamen Grenze URL-decodiert und
  behalten eingebettete Gleichheitszeichen. Kennwoerter werden ohne lockere
  Typumwandlung mit `hash_equals` verglichen. Fuer `init.js` werden Strings als
  JavaScript-Inhalte escaped, ungueltige UTF-8-Bytes ersetzt und die Instanz-ID
  auf positive Ganzzahlen begrenzt. AdvTextfield verarbeitet den bereits
  decodierten Wert ohne eine zweite Decodierung, sodass woertliche
  Prozentsequenzen erhalten bleiben.
- Alle fuenf Plain-Text-Abbruchpfade verwenden den `HttpResponseHelper`.
  Fehlende Queryparameter liefern HTTP 400, nicht erreichbare oder nicht
  passende Kindinstanzen HTTP 404. Der Authentisierungsfehler behaelt bewusst
  seine leere HTTP-200-Antwort. Erfolgreiche Routen setzen ihren Status
  standardkonform und senden `X-Content-Type-Options: nosniff`.
- Statische Assets und gecachte Modulantworten pruefen ETag und
  `If-Modified-Since` ohne unsichere Server-Arrayzugriffe, akzeptieren quotierte
  ETags und beenden unveraenderte Antworten mit HTTP 304 ohne Body.
- `getGlobalConfig`, `getConfiguration`, `getData`, `getFonts`,
  `getLanguage` und `getUpdate` sind als JSON-Antworten charakterisiert und
  senden `application/json`. Die globale Konfiguration wird mit `no-store` und
  `nosniff` ausgeliefert.
- Erfolgreiche HTML-, CSS-, JavaScript-, SVG- und Plain-Text-
  Antworten deklarieren feste Inhaltstypen mit UTF-8-Zeichensatz. Der
  dynamische Bildpfad akzeptiert nur die vier im Progressbar-Formular
  angebotenen MIME-Typen und faellt bei unbekannten oder manipulierten Angaben
  auf `application/octet-stream` zurueck. Der bestehende Binaerinhalt bleibt
  dabei unveraendert.
- Der `Content-Disposition`-Header des Konfigurationsexports entfernt
  Steuerzeichen und ungueltige Dateipfadzeichen aus Modul- und Objektnamen.
  Ein kompatibler ASCII-Name und ein UTF-8-`filename*` erhalten weiterhin
  lesbare Downloadnamen einschliesslich Umlauten.
- `docs/WEBHOOK_SECURITY_MODEL.md` beschreibt die tatsaechliche CORS-,
  Authentisierungs- und Schreibgrenze. Tests belegen insbesondere die
  Deaktivierung der Pruefung bei leerem Kennwort, den historischen
  `setData`-GET-Aufruf und die Zielbegrenzungen von Custom, ColorPicker und
  DateTimePicker.

Bereits behobene technische Schulden:

- `SymconJSLiveChart::GetCorrectStartDate()` und
  `SymconJSLiveRadarChart::GetCorrectStartDate()` verwenden fuer Start und Ende
  absoluter Zeitraeume denselben ausgewaehlten Zeitanker. Ein fester
  Referenzzeitpunkt aus 2024 belegt in beiden Harnesses, dass die Perioden nicht
  mehr unbemerkt in das aktuelle Jahr wechseln. Historische Intervallgrenzen
  wurden in diesem Schritt nicht veraendert.
- `SymconJSLiveDateTimePicker::LoadOtherConfiguration()` uebernimmt weiterhin
  die Darstellungswerte einer anderen DateTimePicker-Instanz, bewahrt aber die
  lokale Integer-Property `Variable`. Die nicht registrierte String-Property
  `Variables` wird weder gelesen noch in die Zielkonfiguration geschrieben.
  Ein Regressionstest prueft den vollstaendigen Kopierpfad.
- Die MessageSink-Verwaltung prueft die bisherige Variablenliste jetzt vor dem
  Entfernen bereits bekannter Sender. Unveraenderte Variablen werden dadurch
  nicht erneut registriert; neue und entfernte Sender behalten ihre bisherigen
  An- und Abmeldepfade. Ein Regressionstest prueft auch einen wiederholten
  unveraenderten Abgleich.
- `LoadConnectAddress` verwendet die offizielle Connect-Control-Modul-ID,
  liefert die gefundene URL an den bestehenden Formularcallback und befuellt
  im Startpfad nur eine leere Adresse. Fehlt die Connect-Instanz oder ihre URL,
  bleibt die Konfiguration unveraendert. Ein Regressionstest deckt alle drei
  Pfade ab.
- `Debug_LoadLogFile` behaelt seinen oeffentlichen Diagnosevertrag und gibt
  die Symcon-Logdatei weiterhin aus. Jede Zeile wird dabei ueber den zentralen
  `DebugHelper` gefiltert; der JSLive-spezifische Queryparameter `pw` wird
  zusaetzlich maskiert. Ein Regressionstest sichert sichtbare harmlose Inhalte
  und verdeckte Passwortwerte ab.
- `SymconJSLiveDoughnutPie::GetData()` prueft konfigurierte Variablen seit
  Commit `7f666fe` gegen die korrekte Liste und liefert mehrfach konfigurierte
  Variablen nur noch einmal. Ein gezielter Regressionstest sichert den Pfad ab.

Prioritaet hoch:

- Der Webhook transportiert das Kennwort als Query-Parameter. Bekannte
  Zugangsdatenfelder werden im Debug maskiert; die bewusst vollstaendige
  Diagnose kann jedoch unbekannte Geheimnisse aus freien Texten, Skripten
  und Medien enthalten.
- Konfigurationsimport und Custom-Funktionen koennen Skripte erzeugen,
  Variablen oder Medien schreiben, Skripte ausfuehren oder komplette
  Instanzkonfigurationen anwenden. ConfigStore und SyncModule wurden entfernt.

Prioritaet mittel:

- rohe Header-/Echo-Antworten, breite CORS-Freigabe, ungepruefte
  Server-Arrayzugriffe und `rand()` fuer Kennwoerter;
- serialisierte PHP-Daten in Buffern und `unserialize` ohne erlaubte Klassen;
- grosse Basisklasse, duplizierte Hilfsfunktionen und sehr grosse Moduldateien;
- Das experimentelle SyncModule wurde entfernt. Seine externe Modultyp-Liste
  war nicht mehr erreichbar; ausserdem verwarf es gespeicherte
  Parameterauswahlen und besass fuer direkte Zielaenderungen keine Vorschau oder
  Rueckfallmoeglichkeit. Die Entscheidung steht in
  `docs/adr/0002-remove-sync-module.md`.

Dokumentationsluecken:

- Die Root-README beschreibt nun alle 10 verbliebenen Module, den
  Modernisierungsstatus, Installation, CI und bekannte
  Sicherheits-/Betriebseinschraenkungen;
- Alle verbliebenen Module besitzen eine README. Die zuvor fehlenden Dateien
  wurden aus dem tatsaechlichen Modulvertrag erstellt; die vorhandenen
  README-Dateien wurden anschliessend auf korrekte Titel, GUIDs, Zielplattform,
  Datenvertraege und bekannte Frontend-Einschraenkungen synchronisiert.
- Datenfluss, Hook-Protokoll, oeffentliche Methoden, Migrationsregeln,
  Frontend-Lizenzen und die spaeter geplante lokale Konfigurationsverteilung
  sind nicht vollstaendig dokumentiert.

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
Der gemeinsame dynamische Formularvertrag ist anhand des realen Chart-Formulars
fuer initiale Darstellung und Live-Aktualisierung charakterisiert.
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
     pruefen (abgeschlossen; Laufzeitmatrix am 01.10.2026 bestanden);
   - `ColorPicker` und `DateTimePicker` als zweite Produktionsgruppe formatieren
     und pruefen (abgeschlossen; Laufzeitmatrix am 01.10.2026 bestanden);
   - `AdvTextfield` einzeln formatieren und mit dem Rendering-Harness pruefen
     (abgeschlossen; Laufzeitmatrix am 01.10.2026 bestanden);
   - `WebHookModule.php` isoliert formatieren und mit dem Routing-Harness pruefen
     (abgeschlossen; Sicherheits- und Laufzeitpruefung bestanden);
   - `JSLiveModule.php` isoliert formatieren und mit Konfigurations-, Formular-,
     Rendering- und DataFlow-Harnesses pruefen (abgeschlossen; ein notwendiger
     expliziter `srand()`-Seed-Cast ist enthalten; Laufzeitmatrix bestanden);
   - den zentralen Splitter isoliert formatieren und mit Webhook-, DataFlow- und
     Vertragspruefungen absichern (abgeschlossen; Sicherheits- und
     Laufzeitpruefung bestanden);
   - `DoughnutPie` einzeln formatieren und gegen Vertrags-Snapshot und
     Gesamtsuite pruefen (abgeschlossen; Browser- und Laufzeit-Smoke-Test
     bestanden, visuelle Regressionstests bleiben ausstehend);
   - `Calendar` isoliert formatieren und mit Formular- und Vertragspruefung
     absichern (historisch abgeschlossen; das Modul wurde anschliessend gemaess
     ADR 0003 entfernt);
   - das damals noch enthaltene `SyncModule` isoliert formatieren und gegen
     Vertrags-Snapshot und Gesamtsuite pruefen (historisch abgeschlossen; das
     Modul wurde anschliessend gemaess ADR 0002 entfernt);
   - `RadarChart` isoliert formatieren und seine Datums- und Offset-Berechnung
     fuer PHP 8.5 absichern (abgeschlossen; Browser- und Laufzeitmatrix
     bestanden, visuelle Regressionstests bleiben ausstehend);
   - `Chart` isoliert formatieren und Datums-, Offset- und Webhook-Querygrenzen
     fuer PHP 8.5 absichern (abgeschlossen; Browser- und Laufzeitmatrix
     bestanden, visuelle Regressionstests bleiben ausstehend);
   - `Custom` isoliert formatieren und numerische Konfigurations- und
     Webhook-Grenzen fuer PHP 8.5 absichern (abgeschlossen; Browser- und
     Laufzeit-Smoke-Test bestanden, weitergehende schreibende Objekt-, Skript-
     und Medienpfade bleiben ausstehend);
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
     abgeschlossen: Die App `Burki24 Helper Sync` ist fuer JSLive installiert,
     die erforderlichen Repository-Eintraege sind eingerichtet und mehrere
     erfolgreiche Bot-Commits bis einschliesslich `e3af8ff` belegen den
     schreibenden Workflow;
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

1. Das experimentelle SyncModule und seinen externen Dienst bewerten
   (abgeschlossen: Das Modul wurde gemaess ADR 0002 vollstaendig entfernt).
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
4. Query-Verarbeitung zentralisieren und haerten (abgeschlossen; URL-Decodierung,
   eingebettete Gleichheitszeichen, exakter Kennwortvergleich sowie sichere
   `init.js`-Parameter und der Erhalt woertlicher Prozentsequenzen sind
   getestet).
5. Plain-Text-Abbruchantworten haerten und den `HttpResponseHelper` gezielt
   integrieren (abgeschlossen; Statuscodes 200/400/404, Antworttexte und
   `nosniff` sind getestet).
6. Conditional Requests fuer statische Assets und gecachte Modulantworten
   haerten (abgeschlossen; ETag-, Datums- und Body-Verhalten sind getestet).
7. Erfolgreiche JSON-Antworten charakterisieren und ihre Header haerten
   (abgeschlossen; globale Konfiguration und alle etablierten JSON-Kindbefehle
   sind erfasst).
8. Erfolgreiche HTML-, CSS-, JavaScript-, SVG- und Binaerantworten
   charakterisieren und ihre HTTP-Header schrittweise haerten (abgeschlossen;
   feste Texttypen sowie eine Allowlist mit sicherem Fallback fuer dynamische
   Bildtypen sind getestet).
9. Den dynamisch erzeugten Dateinamen der Exportantwort charakterisieren und
   den `Content-Disposition`-Header gegen ungueltige Zeichen absichern
   (abgeschlossen; regulaere, internationale und manipulierte Namen sind
   getestet).
10. CORS-, Authentisierungs- und Schreibberechtigungsmodell dokumentieren und
   testen (abgeschlossen; das bestehende Modell ist dokumentiert und durch
   Vertrags- sowie Negativtests charakterisiert. Eine spaetere Aenderung von
   URL-Kennwort, Wildcard-CORS oder GET-Schreibzugriffen benoetigt eine eigene
   Migration und Symcon-Laufzeitabnahme).

### Phase 2 - Symcon 9.0 / PHP 8.5 stabilisieren

1. Laufzeittests auf Symcon-9-Testinstanzen fuer alle verbliebenen Module
   definieren und ausfuehren (abgeschlossen: `docs/SYCON_RUNTIME_MATRIX.md`
   beschreibt die aktuelle, ueber den Symcon-MCP erreichbare Testebene sowie
   gemeinsame und modulspezifische Abnahmen. Die Baseline wurde am 01.10.2026
   unter IP-Symcon 9.1 und PHP 8.5.8 erfolgreich ausgefuehrt. Aufgrund der
   festgelegten Kompatibilitaet innerhalb IP-Symcon 9 wird keine separate
   9.0-Testinstallation vorgehalten).
2. Die Migration von `IPSModule` auf `IPSModuleStrict` als eigenes Vorhaben
   vorbereiten und lokal umsetzen (abgeschlossen: `docs/STRICT_MODULE_MIGRATION.md` und
   `tests/fixtures/strict-module-public-methods.json` erfassen alle 74
   oeffentlichen Methodendeklarationen, Zielsignaturen, Variablenregistrierung/-
   schreibzugriff, Parent-Automatik, Datenfluss, Hooks und Rueckfallgrenzen. Die
   Produktivklassen und Test-Stubs sind umgestellt; die Laufzeitabnahme auf
   MCP-CURRENT ist einschliesslich Dienstneustart bestanden).
3. Das experimentelle `SyncModule` und das Calendar-Modul wurden vor der
   Strict-Migration entfernt; ihre Modul-IDs und Praefixe werden nicht
   wiederverwendet.
4. Abgeschlossen: Die gemeinsame Basisklasse `JSLiveModule` und ihre neun
   Kindmodule sind koordiniert migriert. Data-IDs, JSON-Text-Datenfluss,
   Variablen-Idents, Actions und oeffentliche PHP-Namen bleiben unveraendert;
   `ConnectParent()` wurde durch die bereits deklarierten Datenflussvertraege
   ersetzt.
5. Abgeschlossen: Der Splitter verwendet nach den Webhook-Sicherheits- und
   Pfadtests die native Hook-API von `IPSModuleStrict`. Hook-Pfad,
   Authentisierung und bestehende Browseraufrufe bleiben Migrationsvertraege.
6. Fortlaufend: Die oeffentlichen Type Hints sowie die in String-Rueckgaben
   erreichbaren JSON-Fehlerpfade sind fuer die Strict-Migration gehaertet.
   Weitergehende Array-, Buffer- und Netzwerkhaertung bleibt in getrennten
   Schritten moeglich.
7. Abgeschlossen: Nach erfolgreicher Laufzeitmatrix deklariert `library.json`
   IP-Symcon 9.0 als Mindestversion.
8. Migrationen fuer jede unvermeidbare Vertragsaenderung vor der Aenderung
   spezifizieren und testen.

### Phase 3 - Helper-Integration

1. Abgeschlossen: Die risikoarmen Querschnittsfunktionen fuer Debug und die
   Plain-Text-HTTP-Antworten verwenden `DebugHelper` und `HttpResponseHelper`.
2. Bewertet: `VisualizationAssetHelper` passt nicht auf den historischen
   dynamischen Webhook-Pfad, `VariableHelper` nicht auf konfigurierte externe
   Objekt-IDs und `PersistentJsonCacheHelper` nicht ohne Migration auf die
   heute fluechtigen Laufzeitbuffer. Diese Helper werden deshalb nicht
   erzwungen integriert.
3. Begonnen: `ConfigurationFormHelper` uebernimmt in der gemeinsamen
   Kindmodul-Basis das statische Laden und Validieren von `form.json`; die
   bestehende dynamische Formularlogik bleibt unveraendert. Responsive- und
   Theme-Helper folgen erst an einem geeigneten Darstellungspiloten.
4. Nur nach erfolgreicher Wiederverwendung generalisierbare JSLive-Funktionen
   in `SymconDevelopment` bzw. `Symcon_ModuleHelper` vorschlagen.

### Phase 4 - Frontend konsolidieren

1. Erledigt: Nutzung jeder Bibliothek und jedes Plugins je Modul ist in
   `docs/FRONTEND_DEPENDENCIES.md` erfasst und durch einen Referenztest
   abgesichert.
2. Erledigt: Die festen externen CDN-/Font-Ressourcen der mitgelieferten
   Templates sind lokal, versioniert sowie mit Quellen, Hashes und
   Lizenztexten dokumentiert.
3. Erledigt: MCDatepicker, zwei unbenutzte CSS-Dateien und neun
   historische Chart.js-/Plugin-Dateien sind mit dokumentierter Migration und
   Webhook-Regressionstests entfernt. Die Referenzsuche auf MCP-CURRENT fand in
   drei Skripten keine Treffer. Die Browserabnahme von 0.70 nach Dienstneustart
   ist erfolgt; auch CI und gezielte Laufzeitabnahme der Chart.js-Bereinigung
   auf 0.71 (Metadatencommit `787d24a`) sind bestanden.
4. Erledigt: Die drei Diagrammvorlagen verwenden gemeinsam Chart.js
   4.5.1 aus einer integritaetsgeprueften, lokal versionierten Distribution.
   Alte 4.x-Pfade, Moment und Plugins bleiben unveraendert. Der lokale
   Browservergleich ist in der Laufzeitmatrix dokumentiert; CI und gezielte
   Laufzeitabnahme des Versionsschritts auf 0.72 nach Neustart sind bestanden.
   Der Browservergleich hat einen bereits mit 4.4.1 vorhandenen Radar-Tooltip-
   Fehler bestaetigt (alte Callback-Signatur).
5. Erledigt: Radar-Tooltips verwenden den Chart.js-4-Kontext und dessen
   formatierten Wert. Sieben Regressionstestfaelle und der Browser-Kandidatentest
   sind bestanden. CI und gezielte Abnahme von 0.73 nach Neustart sind bestanden
   (Quellcommit `e30de93`, Metadatencommit `73351d1`).
6. Erledigt: Moment.js 2.31.0 in den drei Standardvorlagen, mit
   unveraendertem Altpfad, Chart.js und Plugins. Herkunft, Integritaet und Lizenz
   sind dokumentiert. Adaptertests in UTC/Berlin sowie Browservergleich mit
   pixelgleichen Bildern, Tooltips, WebSocket und Realtime-Achse sind bestanden.
   CI und gezielte Abnahme nach Modulupdate auf 0.74 ohne Neustart sind bestanden
   (Quellcommit `0589839`, Metadatencommit `540e1de`).
7. Erledigt: chartjs-adapter-moment 1.0.1 in den drei Standardvorlagen.
   Der alte 1.0.0-Pfad bleibt erhalten; Chart.js, Moment, Datalabels und Streaming
   bleiben unveraendert. Integritaet, MIT-Lizenz, Adaptertests und Migration sind
   dokumentiert. Browservergleich: pixelgleiche Diagramme, gleiche Tooltips,
   WebSocket und fortschreitende Echtzeitachse. CI und Auslieferung von 0.75
   sind bestaetigt (`b88e547` / `453f95d`). Die Abnahme fand einen bestehenden
   Fehler bei vertauschter asynchroner Datensatzreihenfolge, reproduzierbar
   mit beiden Adapterversionen. Dieser ist im folgenden Schritt separat behoben.
8. Erledigt: Chart rendert asynchrone Datensaetze erst nach Abschluss
   der Datensatzabrufe und in konfigurierter Reihenfolge ohne Array-Luecken.
   13 Regressionstest-Szenarien und Browserpruefungen beider Antwortreihenfolgen
   einschliesslich Tooltips, Zeitachse und Vollreload sind bestanden.
   CI und gezielte installierte Abnahme von 0.76 sind am 02.10.2026 bestanden
   (`c48f76a` / `124cf6c`), ohne Dienstneustart. Kein neuer Vollmatrixdurchlauf.
9. Erledigt: Datalabels 2.2.0 bleibt die aktuelle stabile Version,
   die drei Standardvorlagen wechseln aber vom historisch modifizierten Bundle
   auf die offizielle Distribution im neuen versionierten Pfad. Der Altpfad
   bleibt fuer eigene Vorlagen erhalten. Quellen, MIT-Lizenz, Hashes,
   Pfad-/Webhook-Tests und Browservergleich mit sichtbaren Labels sind ergaenzt.
   CI und gezielte installierte Abnahme von 0.77 sind am 02.10.2026 bestanden
   (`2b3034d` / `30b652a`), ohne Dienstneustart. Kein neuer Vollmatrixdurchlauf.
10. Streaming-Audit abgeschlossen: Bestand entspricht dem qultoltd-Fork 3.1.0,
    dessen letzter Commit vom 03.08.2023 stammt; npm-Abfrage HTTP 404.
    Lokal korrigiert sind `frameRate` und `update('quiet')` fuer Realtime-Achsen;
    gewoehnliche Zeitachsen verwenden den Standardmodus. Regressionstests und
    lokaler Browsernachweis mit echten Bibliotheken bestanden. Plugin und
    Assetpfad unveraendert; CI und gezielte installierte Abnahme von 0.78 sind
    am 02.10.2026 bestanden (`cde5520` / `cf42e48`), einschliesslich Pull,
    WebSocket, Reload und beider Update-Modi. Kein neuer Vollmatrixdurchlauf.
11. Historischer Versuch: eigene Echtzeitsteuerung gemaess ADR 0004.
    Ein isolierter Prototyp mit oeffentlichen Chart.js-APIs und Tests liegt unter
    `tests/prototypes/`. Grundfunktionen im Browservergleich bestanden, aber
    deutliche Mehrlast bei vielen Punkten: keine produktive Umstellung.
    Profiling und erste Optimierung sind lokal abgeschlossen: schnellere
    Bereichsbereinigung und driftfreier Zeichentakt, neue Messungen erfassen
    Bildabstaende und Datenbestand. Der volle Chart.js-Balkenupdate dominiert
    weiterhin; das Performancegate ist nicht bestanden. Oeffentliche Parser-/
    Labeloptionen wurden in drei wiederholten Hochlastvergleichen geprueft:
    selbst die Kombination erreicht nur rund 23 Zeichnungen/s bei fast voller
    Hauptthreadlast. Kein automatischer Schnellpfad uebernommen. Der damals
    vorgeschlagene Render-Cache wird nicht weiterverfolgt; ADR 0005 loest die
    Entwicklungsentscheidung ab. Prototyp und Messungen bleiben erhalten.
    Als begrenzter Folgeschritt
    ist der veraltete formatierte Tooltipwert nach Auswahl/Bereinigung lokal
    behoben: chart-lokaler oeffentlicher Update-Hook, kein zusaetzlicher Render.
    Auswahlwechsel, Pause, Hintergrund und Hook-Abbau sind abgesichert.
    Die Formatierung der Profiling-JSON ist nach dem CI-Stylefehler korrigiert;
    Messwerte unveraendert. CI dieser Korrekturen ist fuer `d990f9a`/`984a3ce`
    (0.81) gruen; CI des neuen Optionsvergleichs folgt nach Commit/Push.
    Ergebnisse und Einzelmessungen in `docs/REALTIME_PROTOTYPE.md`.
12. Beschlossen: eigener Wartungsfork unter
    `Burki24/chartjs-plugin-streaming`, gemaess ADR 0005. Stand 3.4.0
    (`fc0dd2e`) besitzt reproduzierbare Builds, Versionsautomatik und abgesicherte
    Timer-, Quiet-Update-, Hover- und Tooltipkorrekturen; CI ist bestanden.
    Die isolierte JSLive-Matrix prueft zusaetzlich zum Luxon-Bestand Chart.js
    4.5.1 mit Moment 2.31.0, Moment-Adapter 1.0.1 und Datalabels 2.2.0.
    Vier lokale Browserfaelle (UTC/Berlin, beide UMD-Bundles) sind bestanden.
    Der begrenzte Langlauf gegen Bundle 3.1.0 ist mit zwei Wiederholungen je
    Variante a 120 Sekunden bestanden: pixelgleicher Ausgangszustand,
    begrenzter Datenbestand und keine beobachtete Leistungsverschlechterung.
    Kein Langzeit-, Transport- oder installierter Symcon-Nachweis. Ein direkter
    In-place-Achsentausch wurde anschliessend im Fork korrigiert; Stand 3.6.0
    (`054f9fd`, Quellstand `ab87b77`) und seine erweiterten Tests sind CI-gruen.
    JSLive nutzt weiterhin den Destroy-/Recreate-Pfad.
    Nachweise stehen im Fork unter `docs/JSLIVE_COMPATIBILITY.md`.
    Die getrennte Integration von 3.6.0 in die Standard-Chartvorlage ist lokal
    umgesetzt: neuer versionierter Assetpfad, MIT-Lizenz, Herkunft und Hashes;
    der alte 3.1.0-Pfad bleibt unveraendert. Auf 0.84 (`5fe45ad` / `8bc5e02`)
    sind CI und die gezielte installierte Pruefung von Asset-Hashes, Realtime,
    Tooltips, Pull und Reload bestanden. WebSocket-Handshake 101 bestaetigt;
    ein echter eingehender Datenwechsel wurde nicht beobachtet. Der Zeitachsen-
    wechsel wurde nur browserlokal geprueft. Gesamtstatus bleibt PARTIAL:
    echte IPSView-Geraete sind mangels Lizenz auf der Testebene nicht pruefbar.
    Die Entwicklung wird auf ausdruecklichen Wunsch trotzdem fortgesetzt.
    Nachweise und Grenzen: `docs/STREAMING_INTEGRATION.md`.
    Moment bleibt vorerst erhalten;
    weitere Bibliotheken folgen einzeln, iro.js bleibt unveraendert.
13. Erledigt: direkter Wechsel von jQuery 3.6.0 auf 4.0.0 in allen
    19 mitgelieferten HTML-Vorlagen. Wegfall aelterer Browser/WebViews ist
    ausdruecklich freigegeben. Neuer versionierter Full-Bundle mit Lizenz,
    Source Map und Integritaetsnachweisen; Altpfad bleibt erhalten.
    Zwoelf Ajax-/Formularfaelle und acht Chart-Browserdurchlaeufe bestanden.
    Push, CI, Metadaten-Pull und Modulupdate sind vom Eigentuemer bestaetigt,
    ebenso der erfolgreiche lokale Chrome-Test. Lokaler Stand 0.85
    (`0aa3f50` / `fc9a80a`); keine neue unabhaengige MCP-Abnahme behauptet.
    IPSView bleibt eine bekannte Testluecke. Details: `docs/JQUERY_MIGRATION.md`.
14. Canvas-Gauges-Audit abgeschlossen: 2.1.7 ist weiter npm-/GitHub-Latest;
    Bestand nach LF-Normalisierung identisch zur offiziellen Distribution.
    Letzter Release/Default-Branch-Commit von April 2020: Pflege bleibt ein
    Risiko. Bundle und vier Vorlagen unveraendert; neue Herkunfts-/Hash- und
    Webhook-Vertraege sowie acht isolierte Gauge-Browserfaelle bestanden.
    CI der neuen Checks folgt nach Push. Kein installierter Runtime-PASS.
    Details und Grenzen: `docs/GAUGE_AUDIT.md`. Naechster isolierter Schritt:
    Herkunft, Version und Pflege von Loading Bar/ldBar pruefen.

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
   Module ausgerollt wird. Eine spaetere Dauersynchronisierung benoetigt eine
   neue Architekturentscheidung und darf die alte SyncModule-ID nicht
   wiederverwenden.

## 13. Entschiedene Punkte und offene Entscheidungen

- Der einmalige Versionsuebergang von `0.9.9.9` auf `0.10` wurde als getrennter
  Bootstrap committed und erfolgreich in der CI geprueft.
- Die GitHub-App `Burki24 Helper Sync` und ihre Repository-Eintraege sind
  eingerichtet. Der Metadatenworkflow hat bis zum abgeglichenen Stand
  `e3af8ff` wiederholt erfolgreich geschrieben; dieses fruehere externe Gate
  ist geschlossen.
- Welche der 10 verbliebenen Module werden produktiv noch benoetigt, und welche
  werden nur kompatibel erhalten oder stillgelegt?
- Der `ConfigStore` wird nicht weiter betrieben. Seine Modulimplementierung,
  Tests und Dokumentation wurden entfernt; vorhandene Instanzen muessen vor dem
  Update geloescht werden. Die Entscheidung ist in
  `docs/adr/0001-remove-config-store.md` dokumentiert.
- Fuer fertig konfigurierte Chart-Ansichten wird nach Abschluss der vorherigen
  Modernisierungsphasen die in Phase 6 beschriebene lokale,
  dienstunabhaengige Verteilung mit gemeinsamer Export-/Importbasis umgesetzt.
- Das experimentelle `SyncModule` wurde vollstaendig entfernt. Vorhandene
  Instanzen muessen vor dem Update geloescht werden; Modul-ID und Praefix werden
  nicht wiederverwendet. Die Entscheidung ist in
  `docs/adr/0002-remove-sync-module.md` dokumentiert.
- Das Calendar-Modul wurde vollstaendig entfernt. Vorhandene Instanzen muessen
  vor dem Update geloescht werden; Modul-ID und Praefix werden nicht
  wiederverwendet. Die Entscheidung ist in
  `docs/adr/0003-remove-calendar-module.md` dokumentiert.
- Welche IPSView-Versionen und vorhandenen Projekte muessen als reale
  Regressionstestfaelle dienen?
- Welche Browser und Geraeteklassen sind fuer die Kacheldarstellung verbindlich?
- Welches kleine Visualisierungsmodul eignet sich als erster Pilot? Aufgrund der
  begrenzten Komplexitaet sind AdvTextfield oder Progressbar naheliegende
  Kandidaten; die Entscheidung folgt nach realen Nutzungsdaten.
