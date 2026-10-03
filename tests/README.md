# JSLive tests

Run the project checks from the repository root:

```text
php tests/run.php
```

Der Runner verwendet neben PHP auch Python 3 fuer die Metadaten- und
Helper-Pruefungen sowie Node.js fuer die JavaScript-Renderingvertraege.

`php tests/gauge-rendering.php` prueft die echte PHP-HTML-Erzeugung des Gauge-
Moduls: 120 Kombinationen aus vier Standardvorlagen/eigener Vorlage, allen
vier Messwertpraezisionen und sechs Alpha-Werten. Highlight-Deckkraft bleibt
von Messwertpraezision unabhaengig; Bereichsrundung, Sortierung, RGB-Werte und
Konfigurationserhalt sind abgesichert. Teil des Standardrunners, nur synthetische
Symcon-Testdoubles. Details: [Gauge-Audit](../docs/GAUGE_AUDIT.md).

`php tests/visualization-output-contracts.php` prueft den echten gemeinsamen
Kind-/Splitter-Code mit prozesslokalen Symcon-Testdoubles: IPSView-/Output-
Schaltermatrix, wiederholte Aktualisierung, getrenntes Entfernen, Statussperre,
Cache, Iframe-Hoehen und Linkbildung. Der Test ist Teil des Standardrunners;
kein Symcon-Zugriff und keine Browser-/IPSView-Abnahme. Vertrag und Grenzen:
[Visualisierungsausgaben](../docs/VISUALIZATION_OUTPUT_CONTRACTS.md).

`node tests/progressbar-browser.js` prueft optional 32 animierte Loading-Bar-
Faelle mit echten Assets, lokaler SVG-Antwort, normalen/verzoegerten Frames
und drei Reverse-Bereichen. `--probe-animation` ist der gezielte Kurzlauf.
Die frueheren Fehlernachweise sind jetzt gruen: Reverse-Vertraege laufen immer
in `progressbar-rendering.js`, deterministische Animationstests in
`loading-bar-animation.js`; beide sind in die Standardsuite eingebunden.
Voraussetzungen und Grenzen: [Loading-Bar-Patch](../docs/LOADING_BAR_PATCH.md).

`node tests/jquery-browser.js` ist ein optionaler, isolierter Browservergleich
mit bereits vorhandenem Playwright. Er prueft echte jQuery-3.6.0-/4.0.0-Ajax-
Callbacks mit den originalen Chart-/Formularfunktionen und kontrollierten
HTTP-Antworten (inklusive Fehlerfaellen und vertauschter Reihenfolge).
`node tests/chart-streaming-browser.js` ergaenzt die echte Chart-Darstellung.
Beide akzeptieren `JSLIVE_BROWSER_EXECUTABLE`, installieren keine Pakete,
kontaktieren kein Symcon und sind nicht Teil der PHP-CI. Aufruf und Grenzen:
[jQuery-Migration](../docs/JQUERY_MIGRATION.md).

`node tests/gauge-browser.js` prueft optional die vier Gauge-Vorlagen mit den
echten Bibliotheken, lokal beantworteten Ajax-Anfragen und synthetischer
Konfiguration. Acht Faelle decken zwei Fensterbreiten, Werteumrechnung,
Formatierung, Animation und Destroy ab. Voraussetzungen und Grenzen stehen
im [Gauge-Audit](../docs/GAUGE_AUDIT.md); kein Zugriff auf Symcon, keine
automatische Paketinstallation und kein Bestandteil der PHP-CI.

Der isolierte Echtzeit-Prototyp wird mit `node tests/realtime-window.js`
deterministisch geprueft; dieser Test ist im Runner enthalten. Ein optionaler
Browser-/Lastvergleich mit bereits vorhandenem Playwright steht in
`tests/realtime-window-browser.js`. Er installiert keine Abhaengigkeiten und
ist noch nicht Teil der CI. `--profile` fuehrt getrenntes CPU-Sampling aus.
`--render-options` vergleicht in rund drei Minuten oeffentliche Parser-/
Labeloptionen gegen den unveraenderten Controller und das Streaming-Plugin
mit drei Wiederholungen je Last. Pixel-/Daten-/Tooltipvergleiche laufen davor;
sichtbare Labels duerfen durch die Lastoption nicht abgeschaltet werden.
Die Optionen wirken ausschliesslich auf synthetische Test-Fixtures, nicht auf
den Controller oder produktive Vorlagen. Die Ausgabe verwendet vier Leerzeichen
fuer JSON; die Messung selbst schreibt keine Dateien.
`node tests/realtime-window-maintenance.js` misst nur die Bereichsbereinigung.
Beide Skripte akzeptieren mit `JSLIVE_BASELINE_CONTROLLER` eine vertrauenswuerdige
lokale Baseline-Datei fuer einen Alt-/Neu-Vergleich. Der Browservergleich erfasst
auch Zeichenabstaende und Datenbestaende. Die Auswahlregression prueft Rohwert,
eingelesenen Wert und sichtbaren Tooltiptext nach Bereinigung, ohne zusaetzliche
Updates/Zeichnungen. Der Pixelvergleich verwendet fuer den bekannten alten
Tooltipfehler eine explizit korrigierte Referenz; die restliche Darstellung
bleibt unveraendert. Umfang und Wiederholung stehen im
[Prototypnachweis](../docs/REALTIME_PROTOTYPE.md).

The official Symcon style configuration is available through the `.style`
submodule. Its local read-only check is:

```text
php-cs-fixer fix --dry-run --diff --using-cache=no --allow-risky=yes --config=.style/.php-cs-fixer.php
```

The legacy style migration is tracked separately from behavioral changes. The
style check becomes a required workflow only after that mechanical migration is
reviewed and the command succeeds for the complete configured file set.

All PHP sources below `tests` already pass the official configuration. This
subset can be checked independently with:

```text
php-cs-fixer fix --dry-run --using-cache=no --allow-risky=yes --config=.style/.php-cs-fixer.php --path-mode=intersection tests
```

## Public contract snapshot

`fixtures/public-contracts.json` is the reviewed baseline for externally
relevant declarations. `public-contracts.php` compares it with the current
library and module metadata as well as the registered properties, variables,
actions, public PHP methods and established hook paths.

Do not regenerate the fixture merely to make the test pass. A changed contract
must first be classified as one of the following:

- an unintended regression, which requires a code correction;
- an intentional compatible extension, which requires documentation and an
  explicit fixture update;
- a breaking change, which additionally requires a migration decision and
  migration tests.

The snapshot records declarations, not their runtime behavior. It therefore
does not replace Symcon runtime, webhook, rendering, import/export or migration
tests.

## IPSModuleStrict migration contract

`strict-module-migration.php` vergleicht alle 74 öffentlichen Methoden mit den
vollständig typisierten Zielsignaturen. Zusätzlich sichert der Test die beiden
Strict-Basisklassen, automatische Parent-Kompatibilität, native
Hook-Registrierung, Legacy-Darstellungsarrays und den unveränderten
JSON-Text-Datenfluss ab.

## Webhook routing harness

`webhook-routing.php` loads the real splitter with a minimal synthetic Symcon
boundary. It verifies the established child DataID and JSON envelope, password
and instance gates, the direct global-configuration response and the historical
default command spelling `getContend`.

The harness intentionally does not assert sensitive debug output, unrestricted
asset paths or other known security risks. Those behaviors are not compatibility
requirements and may be tightened without updating a characterization fixture.

## Connect address harness

`connect-address.php` prueft den oeffentlichen Formularcallback und den
optionalen Startpfad des Splitters. Der Test sichert die korrekte
Connect-Control-GUID und Instanz-ID, die unveraenderte Ausgabe der gefundenen
URL, den Schutz bereits konfigurierter Adressen sowie das Verhalten ohne
Connect-Control-Instanz ab.

## Debug log masking harness

`debug-log-access.php` ruft den bestehenden oeffentlichen Diagnosepfad mit
einer synthetischen Symcon-Logdatei auf. Der Test belegt, dass harmlose
Logeintraege und Queryparameter weiterhin ausgegeben werden, waehrend
Passwortfelder sowie der JSLive-Parameter `pw` sichtbar maskiert bleiben.

## Configuration transfer harness

`configuration-transfer.php` verifies complete and form-filtered exports,
including nested list-column metadata. It also proves that empty, malformed,
incomplete and foreign-module imports are rejected without applying an instance
configuration. A valid import updates known properties, preserves omitted
properties, ignores unknown fields, records the uploaded payload and applies the
target instance exactly once.

### Chart-family export baseline for SymconEcharts

The same harness additionally runs 20 cases against the real static forms and
module metadata of Chart, DoughnutPie, RadarChart, Gauge and Progressbar:
complete/filtered export, each with/without an available template. All cases
are repeated and verify that the synthetic source state is unchanged. Complete
exports preserve representative variable/reference IDs, nested JSON strings,
axis/profile references, custom scales, highlight alpha and SVG/path content.
Explicit template inclusion preserves its content and name. The fixtures are
synthetic property slices, not complete installations or a new export format.

This invokes the real shared `ExportConfiguration()` implementation. It does
not exercise child routing, dynamic form processing, a live Symcon system or
an ECharts importer. In filtered Chart exports the ignored top-level title is
omitted. The ignored dataset Variable column is deliberately excluded from
the green comparison: retaining it is a defect, not a compatibility guarantee.

Run the optional negative probe separately:

```text
php tests/configuration-transfer.php --probe-export-gaps
```

On the inspected 0.93 source this exits **1**, reporting two existing defects
in five checks: available template content is included without opt-in (missing
`scripts` or `scripts=0`, for both complete and filtered export); the filtered
Chart export retains `Datasets.Variable` despite `ignoreExport=true`. The
assertions require the desired exclusion, never preservation of the defect.
This probe is not part of the green standard runner. A follow-up correction
must move the relevant checks into the required suite.

Source inspection explains the gaps: `$withScript` is unconditionally reset
to true; modified list data is not assigned back to the configuration string.
The five child receivers also call `ExportConfiguration()` without forwarding
query data. Removing the forced flag alone would therefore break explicit
template export through those receivers. Fix and test that whole path together.

For migration planning the old format has further limits:

- Its envelope contains `ModuleID`, `ModuleName`, `Config` and optional script
  fields, but no format/source-version marker or semantic ECharts mapping.
- JSON list properties remain JSON-encoded strings inside `Config`; they are
  not normalized datasets. The current filter is not a strict allow-list.
- Chart's current `Period`, `Now`, `Relativ`, `Offset` and `StartDate`, and
  Radar's `Period`/`Relativ`, are instance variables, not configuration
  properties. `ExportConfiguration()` does not capture their values.
- Profile names and object IDs are references only. The export does not
  resolve them on a different installation or include profile definitions or
  archive history. Template contents may contain user-supplied sensitive data.

The existing export is thus a useful configuration source, not an already
complete SymconEcharts migration contract. Do not silently change its envelope
or enable it as the new importer input. Agree source versions, field mapping,
template handling, warnings and original-instance preservation separately.

## MessageSink registration harness

`message-sink-registration.php` prueft die inkrementelle Verwaltung der
Variablenmeldungen. Unveraenderte Sender werden nicht erneut registriert, neue
Sender erhalten beide etablierten Meldungen und entfernte Sender werden von
beiden Meldungen abgemeldet. Gateway-Registrierung und gespeicherte
Variablenliste bleiben Teil des Vertrags.

## Configuration form harness

`configuration-form.php` loads the real Chart form through its public
configuration-form entry point. It verifies initial and live updates for the
shared `viewlevel` and `requireItem` metadata, including nested fields,
generated non-exported technical names and controller `onChange` wiring.
Random technical names are checked by behavior and are deliberately not stored
as fixtures.

## AdvTextfield rendering harness

`adv-textfield-rendering.php` exercises the bundled and script-provided
AdvTextfield templates and the public `getContend` data path. It verifies module
and parent placeholder stages, CSS color conversion, unique font inclusion,
current-value escaping and the cache-disabled, cache-hit and cache-rebuild
responses. The test asserts stable rendering contracts instead of storing the
complete generated HTML as a snapshot.

## DateTimePicker configuration-copy harness

`datetime-configuration-copy.php` prueft das Laden einer Konfiguration aus
einer zweiten DateTimePicker-Instanz. Darstellungswerte werden uebernommen,
waehrend die lokale `Variable`-Bindung erhalten bleibt und kein nicht
registrierter `Variables`-Schluessel erzeugt wird.

## Chart date-range harnesses

`chart-dates.php` und `radar-chart-dates.php` pruefen alle acht Perioden im
relativen und absoluten Modus sowie ihre Offset-Berechnung. Absolute Bereiche
muessen das Jahr des vorgegebenen Referenzzeitpunkts verwenden und duerfen
nicht vom Ausfuehrungsdatum des Tests abhaengen.

## Progressbar rendering harness

`progressbar-rendering.js` fuehrt die reale `LoadBarConfig()`-Funktion aus der
gebuendelten Progressbar-Vorlage aus. Der Test sichert ab, dass vollstaendige
SVG-Grafiken den unterstuetzten Fill-Modus verwenden, waehrend Presets und
benutzerdefinierte Pfade ihre konfigurierte Stroke-Darstellung behalten.
