# Changelog

Alle wesentlichen Aenderungen an JSLive werden in diesem Dokument festgehalten.
Die Library-Version folgt dem Format `Hauptversion.Nebenstand` aus
`library.json`; der dazugehoerige Git-Tag ergaenzt fuer SemVer eine
Patchstelle, beispielsweise `v0.10.0`.

## Unreleased

### Changed

- Die historische Vierkomponenten-Version `0.9.9.9` wurde als einmaliger
  Migrationsschritt auf den gemeinsamen Entwicklungsstand `0.10` ueberfuehrt.
- Changelog, Versionsschema und Release-Ablauf wurden nach dem freigegebenen
  OpenHomeAlarm-Verfahren vereinheitlicht.
- Alle verbliebenen Module und ihre PHP-8.5-Grenzen wurden ohne Aenderung der
  oeffentlichen Vertraege schrittweise auf den gemeinsamen StylePHP-Stand
  gebracht.
- Splitter und Kindmodul-Basisklasse verwenden den zentral synchronisierten
  `DataFlowHelper` fuer ihre bestehenden Datenaustausch-Umschlaege.
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

### Security

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
