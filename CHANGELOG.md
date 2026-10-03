# Changelog

Alle wesentlichen Aenderungen an JSLive werden in diesem Dokument festgehalten.
Die Library-Version folgt dem Format `Hauptversion.Nebenstand` aus
`library.json`; der dazugehoerige Git-Tag ergaenzt fuer SemVer eine
Patchstelle, beispielsweise `v0.10.0`.

## Unreleased

### Development

- Planung an die Community-Zusage angepasst: notwendige Ressourcenupdates,
  JSLive-Chart-Export und Uebernahme in SymconEcharts festgehalten. Nicht durch
  ECharts ersetzte Funktionen (z.B. Colorpicker) bleiben gepflegt, bei Bedarf
  mit alternativen oder eigenen Ressourcen. Keine Laufzeitaenderung und noch
  keine Implementierung des zugesagten Migrationswegs.

- JSLive auf Wartung fuer Symcon 9.0/9.1 und vorhandene Fehler begrenzt
  (ADR 0006). Kachelmigration, neue Konfigurationsverteilung und Gauge-
  Bibliotheksmodernisierung sind keine aktiven Auftraege mehr. Gauge-Praeferenz
  und separat geplantes ECharts-Modul festgehalten. Anwenderfreundliche
  Uebernahme vorhandener JSLive-Charts ist als ausdrueckliche Ausnahme
  eingeplant, nicht ausgeschlossen und noch nicht implementiert. Erfolgreichen
  Gauge-Kachel-Sichttest des Eigentuemers dokumentiert; keine Laufzeitaenderung.

- Bestehende IPSView-/Output-/Link-Vertraege dokumentiert und mit echtem
  Kind-/Splitter-Code sowie lokalen Symcon-Testdoubles abgesichert. Tests fuer
  Schalterkombinationen, Wiederholungen, Entfernen, Cache, Iframe und Links sind
  Teil der Standardsuite. Keine Laufzeitaenderung oder native Kachelmigration.

- Loading-Bar-Herkunft und JSLive-CSS-Anpassungen dokumentiert; Hash-/Webhook-
  Tests und zehn statische Browserfaelle ergaenzt. Separate Fehlernachweise
  reproduzieren falsche Animationsendwerte und uebersprungene Reverse-Updates.
  Beide Korrekturen stehen aus; Produktivbestand unveraendert.

- Canvas Gauges 2.1.7 gegen die offizielle Distribution abgeglichen;
  Quellen-/Hashnachweis, Asset-/Webhook-Vertraege und acht isolierte
  Gauge-Browserfaelle ergaenzt. Pflege- und Laufzeitgrenzen dokumentiert;
  keine Aenderung an Bundle, Vorlagen oder Modulkonfiguration.

- ADR 0005 dokumentiert den beschlossenen eigenen Streaming-Wartungsfork als
  weiteren Entwicklungsweg. ADR 0004, Prototyp und Performanceversuche bleiben
  historisch erhalten; Plan und Freigabegrenzen sind nachgezogen. Keine
  produktive Plugin-/Templateaenderung und kein Wechsel von Moment zu Luxon.

- Reproduzierbarer Hochlastvergleich oeffentlicher Chart.js-Parser-/Labeloptionen
  mit drei Wiederholungen, Daten-/Bildratenmessung und pixelgleichen Test-Fixtures.
  Keine untersuchte Variante erreicht die Performancefreigabe; der Controller
  und die produktive Integration bleiben unveraendert. Ein Render-Cache ist als
  naechster, noch abzustimmender Architekturversuch dokumentiert.
- Der isolierte Echtzeit-Prototyp erneuert Tooltips nach Datenbereinigung erst
  nach dem Chart-Datenupdate. Rohwert und sichtbarer Anzeigewert bleiben dadurch
  konsistent, ohne zusaetzlichen Renderdurchlauf. Auswahl- und Lebenszyklustests
  sind ergaenzt; die Profiling-JSON ist ohne Wertveraenderungen StylePHP-konform
  formatiert. Die produktive Plugin-Einbindung bleibt unveraendert.
- Profiling des isolierten Echtzeit-Prototyps mit Bildabstaenden, Datenbestand,
  CPU-Sampling und reproduzierbaren Alt-/Neu-Vergleichen. Die Bereichsbereinigung
  vermeidet unnoetige Punkt-Maps; ein monotoner Zeichentakt verhindert Drift.
  Referenztests und pixelgleiche Vergleichszustaende sichern die Optimierung ab.
  Der Hochlast-Renderengpass bleibt offen; keine produktive Plugin-Abloesung.
- Isolierter Prototyp einer eigenen Echtzeitsteuerung über öffentliche
  Chart.js-Schnittstellen mit deterministischen Tests und optionalem
  Browservergleich. ADR 0004 beschreibt die Zielarchitektur und Freigabekriterien.
  Das Streaming-Plugin bleibt produktiv unverändert; die erste Lastmessung
  verlangt weitere Optimierung vor einer Umstellung.

### Changed

- Alle mitgelieferten HTML-Vorlagen laden jQuery 4.0.0 aus einem versionierten
  lokalen Pfad mit MIT-Lizenz, Source Map und Integritaetsnachweisen. Der
  bisherige 3.6.0-Pfad bleibt fuer eigene Templates unveraendert. Aeltere
  Browser/WebViews werden nach ausdruecklicher Freigabe nicht mehr zugesichert.
  Ajax-/Formularregressionen sowie Migrations- und Rueckfallhinweise sind ergaenzt.
- Die Standard-Chartvorlage verwendet den eigenen Streaming-Wartungsfork 3.6.0
  mit versioniertem Assetpfad, MIT-Lizenz und Commit-/Hash-Nachweisen. Der alte
  3.1.0-Pfad bleibt fuer eigene Vorlagen unveraendert. Chart.js, Moment, Adapter,
  Datalabels und PHP-/Datenvertraege bleiben gleich. Lokale Integrations- und
  Browsertests sichern den Wechsel ab; installierte Abnahme folgt nach Update.
- Die drei Diagrammvorlagen laden Datalabels 2.2.0 jetzt aus der unveränderten
  offiziellen Distribution mit versioniertem Pfad, vollständiger MIT-Lizenz und
  Integritätsnachweisen. Der historisch modifizierte Bundle bleibt unter seiner
  bisherigen URL für eigene Templates erhalten. Die Plugin-Version bleibt
  2.2.0; Chart.js, Moment, Adapter und Streaming sind unverändert.
- Die drei Diagrammvorlagen verwenden jetzt den lokal versionierten
  chartjs-adapter-moment 1.0.1 mit offiziell deklarierter Chart.js-4-Unterstützung.
  Der bisherige Adapterpfad mit 1.0.0 bleibt für eigene Templates unverändert;
  Chart.js, Moment, Datalabels und Streaming werden dabei nicht aktualisiert.
- Chart, Doughnut/Pie und RadarChart verwenden jetzt Moment.js 2.31.0 aus
  einer lokal versionierten Distribution mit Lizenz- und Integritätsnachweisen.
  Der alte 2.27.0-Pfad bleibt für eigene Templates unverändert. Adaptertests
  sichern Datumsformatierung und Kalender-/Zeitumstellungsgrenzen in UTC/Berlin
  ab; Chart.js und die Plugins bleiben bei diesem Schritt unverändert.
- Chart, Doughnut/Pie und RadarChart verwenden gemeinsam die lokal gebündelte
  Chart.js-Version 4.5.1 mit Herkunfts-, Integritäts- und Lizenznachweisen.
  Die bisherigen URLs mit 4.3.3 und 4.4.1 bleiben für eigene Templates
  unverändert; Moment und die Plugins wurden bei diesem Schritt nicht mit aktualisiert.
- Neun ungenutzte Chart.js-/Plugin-Dateien wurden entfernt: die ES-Module von
  Chart.js 3.9.1, Chart.js 3.6.0 samt Plugins und die zusätzliche Datalabels-Datei.
  Aktive Chart.js-Versionen und Templates blieben bei dieser Bereinigung unverändert. Eigene Templates
  müssen vor dem Update anhand von `docs/FRONTEND_ASSET_MIGRATION.md` auf die
  entfallenden URLs geprüft werden.
- Der unbenutzte MCDatepicker und die alten Stylesheets `DateTimePicker1.css`
  und `font-face.css` wurden entfernt. Mitgelieferte Ansichten verwenden
  weiterhin ihre bisherigen aktiven Ressourcen. Für eigene Templates sind
  die entfallenden URLs und Umstiegsschritte in
  `docs/FRONTEND_ASSET_MIGRATION.md` dokumentiert.
- Library und alle zehn Module nennen neben dem ursprünglichen Autor Swen
  Babenschneider nun auch den aktuellen Maintainer Burkhard Kneiseler. Die
  Library- und Modulmetadaten verlinken außerdem vollständig auf das
  JSLive-Repository.
- Splitter, gemeinsame Kindmodul-Basis und alle neun Visualisierungsmodule
  verwenden `IPSModuleStrict`. Alle 74 öffentlichen Methodendeklarationen sind
  vollständig typisiert, `ReceiveData()` liefert auf leeren Pfaden einen
  kompatiblen Leerstring, bestehende Legacy-Profile werden als
  Darstellungsarrays registriert und die Parent-Verbindung wird aus den
  unveränderten Data-IDs automatisch aufgelöst. Der Splitter registriert
  `/hook/JSLive` über die native Hook-API statt über direkte Änderungen am
  WebHook Control. Die vollständige MCP-CURRENT-Abnahme des Migrationsstands
  einschließlich Dienstneustart ist bestanden; `library.json` deklariert daher
  IP-Symcon 9.0 als Mindestversion.
- Die Laufzeitabnahme verwendet die aktuelle, ueber den Symcon-MCP erreichbare
  IP-Symcon-9-Testebene statt separater 9.0-/9.1-Fresh- und Upgrade-Systeme.
  Die erste Baseline unter IP-Symcon 9.1 und PHP 8.5.8 ist mit allen zehn
  Modultypen, zweimaligem ApplyChanges, Browser-, WebSocket-, Pull- und
  Neustartpruefung bestanden.
- Die historische Vierkomponenten-Version `0.9.9.9` wurde als einmaliger
  Migrationsschritt auf den gemeinsamen Entwicklungsstand `0.10` ueberfuehrt.
- Changelog, Versionsschema und Release-Ablauf wurden nach dem freigegebenen
  OpenHomeAlarm-Verfahren vereinheitlicht.
- Alle verbliebenen Module und ihre PHP-8.5-Grenzen wurden ohne Aenderung der
  oeffentlichen Vertraege schrittweise auf den gemeinsamen StylePHP-Stand
  gebracht.
- Splitter und Kindmodul-Basisklasse verwenden den zentral synchronisierten
  `DataFlowHelper` fuer ihre bestehenden Datenaustausch-Umschlaege.
- Die gemeinsame Kindmodul-Basisklasse lädt und validiert die statischen
  `form.json`-Dateien über den zentral synchronisierten
  `ConfigurationFormHelper`. Die öffentliche dynamische Formularlogik und
  `LoadConfigurationForm()` bleiben unverändert.
- Die mitgelieferten Frontend-Abhaengigkeiten sind erstmals je Modul mit
  Version, Ladeweg, Laufzeitstatus und vorhandenem Lizenznachweis inventarisiert.
  Ein Regressionstest stellt sicher, dass neue Template-Ressourcen in dieser
  Inventur erfasst werden.
- ColorPicker lädt iro.js 5.5.0 nun aus dem lokalen Modulbestand statt über
  jsDelivr. Die 20 bisher von Google geladenen WOFF2-Schriften werden ebenfalls
  lokal über den JSLive-Hook ausgeliefert; Ursprungs-URLs, SHA-256-Werte und
  zugehörige OFL-/Apache-/MPL-Lizenztexte sind im Repository enthalten.
- Nachrichten vom Splitter an Kindmodule tragen die Zielinstanz zusaetzlich als
  eindeutiges aeusseres Routingfeld `InstanceID`. Der Empfangsfilter wertet
  dieses Feld aus, statt die Instanz-ID im maskierten inneren `Buffer` zu suchen.
- Die HTML-Platzhalterersetzung konvertiert numerische Instanz-IDs unter PHP 8.5
  explizit in Strings und bricht dadurch gecachte Chart-Ausgaben nicht mehr ab.
- Der DateTimePicker konvertiert numerische Zeitwerte bei der
  HTML-Platzhalterersetzung explizit in Strings und erzeugt unter PHP 8.5 wieder
  seine HTML-/IPSView-Ausgabe.
- Ungecachte HTML-Webhooks und DateTimePicker-Schreibaufrufe konvertieren ihre
  numerischen Querywerte vor strikt typisierten PHP-8.5-Aufrufen explizit in
  Ganzzahlen. Direkte Modulansichten und Zeitaktionen brechen dadurch nicht
  mehr mit einem `TypeError` ab.
- Der DateTimePicker fuehrt beim Schreiben eine konfigurierte benutzerdefinierte
  Variablenaktion aus und faellt nur ohne Custom- oder Profilaktion auf das
  direkte Setzen des Zeitwerts zurueck.
- Der Progressbar stellt vollstaendige benutzerdefinierte SVG-Grafiken auch bei
  gespeicherter Stroke-Auswahl im von der Loading-Bar-Bibliothek unterstuetzten
  Fill-Modus dar. Dadurch bleiben Wertaktualisierungen ohne JavaScript-Fehler
  funktionsfaehig; benutzerdefinierte SVG-Pfade und Presets behalten ihren
  konfigurierten Stroke-Modus.
- Die oeffentlichen Callbacks dynamischer Konfigurationsformulare verwenden
  unter PHP 8.5 unterstuetzte skalare Parametertypen. Boolesche und numerische
  Werte werden ueber einen String transportiert und intern typisiert;
  Chart-Listenzeilen werden als JSON-String uebergeben und validiert. Dadurch
  registriert IP-Symcon die betroffenen Modulfunktionen ohne Type-Hint-Warnungen.
  Eigene Aufrufe der beiden Chart-Formularcallbacks muessen Arraywerte nun mit
  `json_encode()` uebergeben.
- Splitter, Kindmodul-Basisklasse und alle verbliebenen Module verwenden
  `DebugHelper`.
  Bei aktiviertem Debug erscheinen vollstaendige Browser-, Konfigurations-
  und Austausch-Payloads einschliesslich freier Texte, Skripte und Medien;
  bekannte Zugangsdatenfelder werden auch in eingebettetem JSON maskiert.
  Debug bleibt standardmaessig deaktiviert.
- Chart und RadarChart geben bei aktiviertem Debug einzelne numerische
  Archivwerte ohne kuenstliche Anzahlbegrenzung aus. Ihre modulspezifischen
  Diagnosen wurden auf `DebugHelper` umgestellt.
- Die Chart-Kompatibilitaetsroute `getLanguage` liefert wieder die vom
  bestehenden Frontend-Loader erwartete Konfigurationsstruktur, ohne unter
  PHP 8.5 auf nicht initialisierte Variablen zuzugreifen.
- Dynamische Webhook-Antworten behandeln Clients ohne `Accept-Encoding`-Header
  als nicht gzip-faehig, statt unter PHP 8.5 eine Warnung in den Response-Body
  zu schreiben.
- Doughnut-/Pie-Aktualisierungen liefern dieselbe konfigurierte Variable aus
  mehreren Datensatzzeilen nur noch einmal.

### Fixed

- Progressbar verwendet den lokalen Loading-Bar-Patch `0.1.1-jslive.1`:
  Animationen extrapolieren bei verspaeteten Frames nicht mehr ueber ihren
  Zielwert hinaus. Altpfad und CSS bleiben unveraendert.
- Reverse-Updates vergleichen Rohwerte statt umgekehrter Anzeigewerte;
  die Spiegelung beruecksichtigt auch nicht bei null beginnende Wertebereiche.
  Deterministische Regressionen und animierte Browserfaelle sichern dies ab.

- Chart übergibt dem Streaming-Plugin die korrekt geschriebene Option
  `frameRate`. Eingehende Livewerte aktualisieren Realtime-Achsen mit
  `update('quiet')` statt der veralteten `preservation`-Option; gewöhnliche
  Zeitachsen verwenden weiterhin den Standardmodus. Plugin und Assetpfad
  bleiben unverändert.
- Chart sammelt beim asynchronen Laden die Datensatzantworten vor dem Rendern
  in konfigurierter Reihenfolge. Kommt ein späterer Datensatz zuerst an,
  gehen Zeitachse, Titel und Tooltip-Konfiguration nicht mehr verloren.
  Leere oder fehlgeschlagene Datensatzantworten hinterlassen keine Array-Lücken;
  fehlgeschlagene Abrufe werden ohne Anfrage-URL in der Browserkonsole gemeldet.
- Radar-Tooltips verwenden den Chart.js-4-Kontext statt der alten
  Zwei-Argument-Signatur und zeigen Datensatzname sowie formatierten Wert
  ohne JavaScript-Fehler an. Sie setzen keine kartesischen `.y`-Daten mehr
  voraus; sieben Regressionstestfälle sind in die Testsuite eingebunden.
- `LoadConnectAddress` findet wieder das Connect Control statt des Archive
  Control, uebergibt dessen Instanz-ID an `CC_GetUrl` und liefert die URL an
  den bestehenden Formularcallback zurueck. Der optionale Startpfad schreibt
  nur eine gefundene URL in eine zuvor leere Adresse.
- Die MessageSink-Verwaltung registriert unveraenderte Variablen nicht mehr
  erneut. Neue Sender werden weiterhin fuer beide Variablenmeldungen
  registriert und entfernte Sender von beiden abgemeldet.
- `SymconJSLiveDateTimePicker::LoadOtherConfiguration()` bewahrt beim Kopieren
  die lokale Integer-Property `Variable`, statt die nicht registrierte
  String-Property `Variables` zu lesen und in die Konfiguration zu schreiben.
- Absolute Zeitraeume von Chart und RadarChart leiten ihr Jahr nun durchgaengig
  aus dem gewaehlten Referenzzeitpunkt ab, statt teilweise das aktuelle Jahr
  eines separat erzeugten `DateTime`-Objekts zu verwenden.

### Security

- `Debug_LoadLogFile` gibt die Symcon-Logdatei weiterhin ueber den bestehenden
  oeffentlichen Diagnosepfad aus, maskiert dabei aber bekannte Passwort- und
  Zugangsdatenformen einschliesslich des JSLive-Parameters `pw`.
- Die statische Webhook-Auslieferung akzeptiert nur noch regulaere Dateien,
  deren kanonischer Pfad innerhalb von `SymconJSLive/js` liegt. Relative
  Pfadausbrueche werden mit HTTP 404 abgewiesen; ein Regressionstest sichert
  erlaubte Assets und den bisherigen Quelltextzugriff ueber `../` ab.
- Webhook-Querywerte werden zentral und URL-konform decodiert, ohne eingebettete
  Gleichheitszeichen zu verlieren. Kennwoerter werden exakt mit `hash_equals`
  verglichen; dynamische `init.js`-Strings werden JavaScript-sicher maskiert und
  ungueltige UTF-8-Bytes ersetzt. Die unquoted Instanz-ID akzeptiert
  ausschliesslich positive Ganzzahlen.
  AdvTextfield uebernimmt die bereits decodierten Werte ohne eine zweite,
  inhaltsveraendernde Decodierung.
- Plain-Text-Abbrueche des Webhooks verwenden durchgaengig den
  `HttpResponseHelper`: unvollstaendige Anfragen liefern HTTP 400, fehlende
  Kindinstanzen HTTP 404. Der absichtlich nicht unterscheidbare
  Authentisierungsfehler bleibt eine leere HTTP-200-Antwort. Erfolgreiche
  Routen verwenden keine benutzerdefinierte `200 X`-Statuszeile mehr und setzen
  `X-Content-Type-Options: nosniff`.
- Conditional Requests fuer statische Assets und gecachte Modulantworten
  pruefen ETags und `If-Modified-Since` sicher, akzeptieren quotierte ETags und
  liefern bei unveraendertem Inhalt HTTP 304 ohne Response-Body. Fehlende
  Conditional-Request-Header loesen unter PHP 8.5 keinen Fehler mehr aus.
- `getGlobalConfig` sowie alle etablierten JSON-Kindbefehle deklarieren
  `application/json`. Die globale Konfigurationsantwort wird explizit nicht
  gecacht und wie die uebrigen Erfolgsantworten gegen MIME-Sniffing geschuetzt.
- Erfolgreiche HTML-, CSS-, JavaScript-, SVG- und Plain-Text-
  Antworten deklarieren feste Inhaltstypen mit UTF-8-Zeichensatz. Vom
  Progressbar-Kindmodul gelieferte Bildtypen werden auf die vier tatsaechlich
  konfigurierbaren Formate begrenzt; unbekannte oder manipulierte Angaben
  verwenden `application/octet-stream`, ohne den Bildinhalt zu veraendern.
- Der Downloadname des Konfigurationsexports kann keine zusaetzlichen
  HTTP-Header oder ungueltigen Dateipfade mehr einschleusen. Lesbare
  ASCII-Namen bleiben erhalten; Namen mit Umlauten werden zusaetzlich als
  UTF-8-`filename*` uebertragen.

### Removed

- Der vollstaendig vom nicht mehr erreichbaren Dienst
  `jslive.babenschneider.net` abhaengige `SymconJSLiveConfigStore` wurde aus der
  Library entfernt. Vorhandene ConfigStore-Instanzen muessen vor einem Update
  auf diesen Stand geloescht werden. Ein spaeterer lokaler Export und Import
  fertig konfigurierter Chart-Ansichten wird als getrennte Funktion entwickelt.
- Das experimentelle `SymconJSLiveSyncModule` wurde entfernt. Seine
  Modultyp-Liste hing am selben abgeschalteten Dienst, gespeicherte
  Parameterauswahlen wurden verworfen und die direkte Konfigurationsverteilung
  besass weder Vorschau noch Rueckfallmechanismus. Vorhandene SyncModule-
  Instanzen muessen vor einem Update geloescht werden. Die Modul-ID
  `{6C44628E-B623-7B92-D61D-0B3EAF4D6345}` wird nicht wiederverwendet; eine
  spaetere lokale Einmalverteilung wird getrennt entworfen.
- `SymconJSLiveCalendar` wurde einschliesslich seiner Templates,
  FullCalendar-Assets, ICS-/CSS-Webhooks und modulspezifischen Tests entfernt.
  Vorhandene Calendar-Instanzen muessen vor einem Update auf diesen Stand
  geloescht werden. Die Modul-ID `{46B41C3B-DDAE-BA35-2A1E-6CF4B7F9BF7A}`
  und das Praefix `SymconJSLiveCalendar` werden nicht wiederverwendet.

### Added

- Eine getestete `IPSModuleStrict`-Migrationsinventur erfasst alle 74
  oeffentlichen Methodendeklarationen mit Zielsignaturen sowie die geaenderten
  Grenzen fuer Variablenregistrierung, Schreibzugriff, Parent-Verbindung,
  Datenfluss und Webhooks. Die produktiven Modulklassen bleiben in diesem
  Schritt unveraendert.
- Eine verbindliche Laufzeitmatrix definiert Fresh- und Upgrade-Abnahmen aller
  verbliebenen Module unter IP-Symcon 9.0/9.1 und PHP 8.5. Ein Vertragstest
  sichert Szenarien, Module und erforderliche Nachweise, ohne noch nicht
  ausgeführte Laufzeittests als Kompatibilitätsfreigabe auszugeben.
- Lokale Struktur-, Vertrags- und Verhaltenstests sichern die charakterisierte
  Bestandsarchitektur, Konfigurationspfade und ausgewaehlte Render-/Datenfluesse.
- Das Webhook-Sicherheitsmodell dokumentiert die bestehenden oeffentlichen und
  kennwortgeschuetzten Routen, Wildcard-CORS, GET-Schreibzugriffe sowie die
  zusaetzlichen Ziel- und Read-only-Grenzen der schreibfaehigen Kindmodule.
  Vertragstests sichern dieses Bestandsverhalten ab, ohne die Zugriffspolitik
  in diesem Schritt zu aendern.
- Gemeinsame GitHub-Actions-Pruefungen fuer Tests und Style sowie CodeQL fuer
  JavaScript-/TypeScript-Quellen sind eingerichtet.
- Der Metadatenworkflow erhoeht auf `dev` die gemeinsame Library-Version pro
  Quellcommit und erzeugt Build und Datum reproduzierbar aus dessen Git-Daten.
  Ein Regressionstest sichert Berechnung und Rueckwaertsschutz ab.

### Verified

- Repository-Tests, PHP-Syntaxpruefung und repositoryweiter Style-/JSON-Check
  wurden fuer den beschriebenen Stand lokal ausgefuehrt.

Diese Umstellung aendert keine fachliche Modulfunktion und ist noch keine
Produktfreigabe. Der erste modernisierte Release folgt erst nach den
dokumentierten Abnahmen.
