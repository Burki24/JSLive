# Frontend-Abhaengigkeiten

Stand: 03.10.2026. Diese Inventur bildet die mitgelieferten Templates und die
vom Splitter unter `/hook/JSLive/js/` ausgelieferten Dateien ab. Die zuvor
extern geladenen Ressourcen sind lokalisiert; Bibliotheksversionen wurden dabei
nicht aktualisiert. MCDatepicker, zwei unbenutzte CSS-Dateien sowie die
historischen Chart.js-3.x-Dateien und unbenutzten Plugin-Kopien sind entfernt.
Die drei Standard-Diagrammvorlagen verwenden nun gemeinsam Chart.js 4.5.1.
Die alten URLs mit 4.3.3 und 4.4.1 bleiben fuer eigene Vorlagen unveraendert.

Die folgenden Abnahmemarkierungen dokumentieren die jeweiligen Einzelschritte.
Den aktuellen kumulativen Stand und echte Restpunkte fuehrt die
[Laufzeitmatrix](SYCON_RUNTIME_MATRIX.md#fortschreibung-nach-abnahme-von-096-03102026).
Insbesondere sind WebSocket/Pull und sequentielle historische Chart-Wechsel
inzwischen live geprueft; ueberlappende Reloads sind lokal korrigiert. Fuer
Canvas Gauges bleibt der neu nachgewiesene Fehler bei unterbrochenen
Animationen offen. Keine erneute pauschale Aktualisierung aller Ressourcen.

Benutzerdefinierte Templates aus `TemplateScriptID` koennen weitere, hier nicht
kontrollierbare Abhaengigkeiten laden. Sie gehoeren nicht zum reproduzierbaren
Lieferumfang der Library.

## Nutzung je Modul

| Modul | Template(s) | Aktiv geladene Frontend-Bausteine |
| --- | --- | --- |
| `SymconJSLive` | `htmlbox/HtmlBox-Chart.html` | jQuery 4.0.0, `util.js`, `init.js`; danach indirekt `loader.js`, `jslive/Chart.js` und die ausgewaehlten lokalen Font-CSS-/WOFF2-Dateien |
| `SymconJSLiveAdvTextfield` | `Textfield1.html`, `Textfield2.html`, `FormExample.html` | jQuery 4.0.0, `util.js`, `css/TextField.css` oder `css/FormExample.css` |
| `SymconJSLiveChart` | `Chart.html` | jQuery 4.0.0, Chart.js 4.5.1, Moment.js 2.31.0, chartjs-adapter-moment 1.0.1, chartjs-plugin-streaming 3.6.0, chartjs-plugin-datalabels 2.2.0, `util.js` |
| `SymconJSLiveColorPicker` | `ColorPicker.html` | jQuery 4.0.0, `util.js`, lokale iro.js 5.5.0 |
| `SymconJSLiveCustom` | `Default.html` oder benutzerdefiniertes Template | Das mitgelieferte Default-Template nutzt jQuery 4.0.0 und `util.js`; benutzerdefinierte Skripte liegen ausserhalb dieser Inventur |
| `SymconJSLiveDateTimePicker` | `TimePicker1.html`, `TimePicker2.html`, `TimePicker3.html`, `DatePicker1.html`, `DateTimePicker1.html` | jQuery 4.0.0, `util.js` und die jeweilige Template-CSS-Datei; DatePicker und DateTimePicker verwenden beide `css/DatePicker1.css` |
| `SymconJSLiveDoughnutPie` | `Doughnut-PIE.html` | jQuery 4.0.0, Chart.js 4.5.1, Moment.js 2.31.0, chartjs-adapter-moment 1.0.1, chartjs-plugin-datalabels 2.2.0, `util.js` |
| `SymconJSLiveGauge` | vier `CanvasGauges-*.html`-Templates | jQuery 4.0.0, Canvas Gauges 2.1.7, `util.js` |
| `SymconJSLiveProgressbar` | `Progressbar.html` | jQuery 4.0.0, Loading Bar/`ldBar` mit lokalem Patch `0.1.1-jslive.1` auf GitHub-Stand `af5271e`, angepasstes `loading-bar.css`, `util.js` |
| `SymconJSLiveRadarChart` | `RadarChart.html` | jQuery 4.0.0, Chart.js 4.5.1, Moment.js 2.31.0, chartjs-adapter-moment 1.0.1, chartjs-plugin-datalabels 2.2.0, `util.js` |

Alle mitgelieferten Visualisierungstemplates verwenden damit jQuery und
`util.js`. Die Schriftwahl wird zentral durch `JSLiveModule.php` beziehungsweise
im historischen HTMLBox-Lader auf eine der Dateien unter
`SymconJSLive/js/css/fonts/` abgebildet.

## Paket- und Asset-Inventur

| Paket/Baustein | Lokaler Bestand | Laufzeitstatus | Lizenznachweis im Repository |
| --- | --- | --- | --- |
| jQuery 4.0.0 | `SymconJSLive/js/jquery/4.0.0/jquery.min.js`, zugehoerige `.map` | vollstaendige Distribution, aktiv in allen mitgelieferten Templates | MIT-Lizenz, npm-Integritaet und SHA-256 in `SymconJSLive/js/jquery/4.0.0/SOURCES.md` |
| jQuery 3.6.0 | `SymconJSLive/js/jquery.min.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| JSLive-Browserlaufzeit | `SymconJSLive/js/util.js`, `SymconJSLive/js/init.js`, `SymconJSLive/js/loader.js`, `SymconJSLive/js/jslive/Chart.js` | `util.js` direkt aktiv; die drei uebrigen Dateien indirekt aktiv ueber `HtmlBox-Chart.html` | Projektlizenz `LICENSE` (GPL-3.0) |
| Chart.js 4.5.1 | `SymconJSLive/js/chartjs/4.5.1/chart.umd.min.js`, zugehoerige `.map` | aktiv in allen drei Chart-Modulen; offizielle npm-Distribution | MIT-Texte fuer Chart.js und eingebettetes @kurkle/color 0.3.2, Quellen und SHA-256 in `SymconJSLive/js/chartjs/4.5.1/SOURCES.md` |
| Chart.js 4.3.3 | `SymconJSLive/js/chartjs/chart.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| Chart.js 4.4.1 | `SymconJSLive/js/chartjs/chart.min.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| chartjs-adapter-moment 1.0.1 | `SymconJSLive/js/chartjs/plugins/moment/1.0.1/chartjs-adapter-moment.min.js` | aktiv in den drei Chart-Modulen | MIT-Lizenz, Quellen und Hashes in `SymconJSLive/js/chartjs/plugins/moment/1.0.1/SOURCES.md` |
| chartjs-adapter-moment 1.0.0 | `SymconJSLive/js/chartjs/plugins/chartjs-adapter-moment.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| chartjs-plugin-datalabels 2.2.0 | `SymconJSLive/js/chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js` | offizielle Distribution, aktiv in den drei Chart-Modulen | MIT-Lizenz, Quellen und Hashes in `SymconJSLive/js/chartjs/plugins/datalabels/2.2.0/SOURCES.md` |
| chartjs-plugin-datalabels 2.2.0 (lokal modifiziert) | `SymconJSLive/js/chartjs/plugins/chartjs-plugin-datalabels.min.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen; drei lokale Aenderungen der Elementerkennung | MIT-Hinweis im Dateikopf; Abweichungen und vollstaendige Upstream-Lizenz unter `datalabels/2.2.0/` dokumentiert |
| chartjs-plugin-streaming 3.6.0 | `SymconJSLive/js/chartjs/plugins/streaming/3.6.0/chartjs-plugin-streaming.min.js` | eigener Wartungsfork, aktiv nur in `SymconJSLiveChart` | MIT-Lizenz, Commit-Herkunft und SHA-256 in `SymconJSLive/js/chartjs/plugins/streaming/3.6.0/SOURCES.md` |
| chartjs-plugin-streaming 3.1.0 | `SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| Moment.js 2.31.0 | `SymconJSLive/js/moment/2.31.0/moment.min.js`, zugehoerige `.map` | aktiv in den drei Chart-Modulen; offizielle npm-Distribution | MIT-Lizenz, Quellen, Paketintegritaet und SHA-256 in `SymconJSLive/js/moment/2.31.0/SOURCES.md` |
| Moment.js 2.27.0 | `SymconJSLive/js/moment/2.27.0/Moment.js` | unveraenderter Kompatibilitaetspfad fuer eigene Vorlagen | MIT-Hinweis im Dateikopf |
| Canvas Gauges 2.1.7 | `SymconJSLive/js/canvas-gauges/gauge.min.js` | aktiv in `SymconJSLiveGauge`; unveraenderte offizielle Distribution | vollstaendiger MIT-Text im Dateikopf; npm-Integritaet und LF-normalisierter Hash in `canvas-gauges/SOURCES.md` |
| iro.js 5.5.0 | `SymconJSLive/js/iro/5.5.0/iro.js` | aktiv im ColorPicker; lokal und fest versioniert | MPL-2.0-Hinweis im Dateikopf und `SymconJSLive/js/iro/5.5.0/LICENSE.txt` |
| Loading Bar/ldBar `0.1.1-jslive.1` | `SymconJSLive/js/loading-Bar/0.1.1-jslive.1/loading-bar.js` | aktiver lokaler Ein-Zeilen-Patch fuer Animationsendwerte, kein npm-Release | MIT-Lizenz und Hashes in `loading-Bar/0.1.1-jslive.1/SOURCES.md` |
| Loading Bar/ldBar Altbestand | `SymconJSLive/js/loading-Bar/loading-bar.js`, `SymconJSLive/js/loading-Bar/loading-bar.css` | JS-Kompatibilitaetspfad identisch zu `af5271e`; angepasstes CSS weiterhin aktiv | `SymconJSLive/js/loading-Bar/LICENSE` (MIT), Herkunft und Hashes in `loading-Bar/SOURCES.md` |
| Template-CSS | `SymconJSLive/js/css/DatePicker1.css`, `SymconJSLive/js/css/FormExample.css`, `SymconJSLive/js/css/TextField.css`, `SymconJSLive/js/css/TimePicker1.css`, `SymconJSLive/js/css/TimePicker2.css`, `SymconJSLive/js/css/TimePicker3.css` | aktiv gemaess Modultabelle | Projektlizenz `LICENSE` (GPL-3.0) |
| Web Fonts | 20 CSS-Dateien unter `SymconJSLive/js/css/fonts/` und 20 WOFF2-Dateien unter `SymconJSLive/js/fonts/` | bei konfigurierter Schrift dynamisch aktiv; lokal und ueber SHA-256 reproduzierbar | Quellen, Hashes und Lizenzzuordnung in `SymconJSLive/js/fonts/SOURCES.md`; 17 familienbezogene OFL-Texte und Apache-2.0 liegen unter `fonts/licenses/` |

Chart.js 4.5.1 wurde am 01.10.2026 als aktuelle stabile Version gegen den
offiziellen GitHub-Release und npm `latest` geprueft. Die drei Standardvorlagen
laden ausschliesslich den versionierten neuen Bundle. Die beiden unversionierten
Dateien werden nicht ersetzt: Eigene Vorlagen erhalten keinen stillen
Versionswechsel. Der historische Streaming-Bestand
`@qultoltd/chartjs-plugin-streaming` 3.1.0 bleibt unveraendert; nur die
Standard-Chartvorlage verwendet jetzt den eigenen Wartungsfork 3.6.0.
Laufzeitnachweise und Grenzen stehen in `docs/STREAMING_INTEGRATION.md` und
`docs/SYCON_RUNTIME_MATRIX.md`.

MCDatepicker (`SymconJSLive/js/mc-calendar/mc-calendar.min.js`) sowie
`SymconJSLive/js/css/DateTimePicker1.css` und
`SymconJSLive/js/css/font-face.css` wurden als erste unbenutzte Asset-Gruppe
entfernt. Die bisherigen URLs liefern danach HTTP 404. Eigene Templates muessen
vor einem Update gemaess [Asset-Migration](FRONTEND_ASSET_MIGRATION.md) geprueft
werden. Die aktiven DatePicker- und DateTimePicker-Vorlagen nutzen weiterhin
`css/DatePicker1.css`, Schriften ihre familienbezogenen CSS-Dateien.

Im zweiten Schritt wurden die ungenutzten ES-Module/Helper von Chart.js 3.9.1,
der Parallelbestand Chart.js 3.6.0 mit seinen drei Plugins sowie
`chartjs/plugins/chartjs-plugin-datalabels.js` entfernt. Alle neun Pfade,
Umstiegshinweise fuer eigene Templates und der Rueckfall auf 0.70 stehen in der
[Asset-Migration](FRONTEND_ASSET_MIGRATION.md). Die aktiven Dateien und
mitgelieferten Templates sind dabei unveraendert geblieben.

## Externe Laufzeitressourcen

Die mitgelieferten Templates und Stylesheets laden keine fest eingebauten
externen Laufzeitressourcen mehr. iro.js wird als Version 5.5.0 ueber den
JSLive-Hook geladen. Die zuvor von `https://fonts.gstatic.com/` bezogenen
WOFF2-Dateien werden unveraendert lokal ausgeliefert; ihre urspruenglichen URLs
und SHA-256-Werte bleiben in `SymconJSLive/js/fonts/SOURCES.md` dokumentiert.

Die frei konfigurierbare Splitter-Adresse, IFrame-Ziele und Ressourcen in
benutzerdefinierten Templates sind Anwenderdaten und keine fest eingebauten
Frontend-Abhaengigkeiten.

## Konsequenzen fuer Phase 4

1. Erledigt: iro.js und die Schriftdateien werden lokal, fest versioniert und
   mit Quellen, Hashes und Lizenztexten bereitgestellt. Die Browserabnahme von
   0.70 nach Dienstneustart umfasst ColorPicker, DateTimePicker, die drei
   Diagrammtypen und einen clientseitigen Ladetest der lokalen Roboto-Schrift.
2. Erledigt: MCDatepicker, die beiden unbenutzten CSS-Dateien und die
   neun historischen Chart.js-/Plugin-Dateien sind nach Referenzpruefung
   einschliesslich der Skripte auf MCP-CURRENT entfernt. Migration und
   Rueckfall sind dokumentiert. CI und gezielte Laufzeitabnahme der
   Chart.js-Bereinigung auf JSLive 0.71 sind bestanden.
3. Erledigt: Chart.js 4.5.1 vereinheitlicht die drei Standardvorlagen.
   Hash-, Referenz- und Webhook-Tests sichern Distribution und Ladepfade ab.
   Der lokale Browservergleich ist in der Laufzeitmatrix dokumentiert;
   CI und gezielte Abnahme nach Modulupdate auf 0.72 und Neustart sind bestanden.
   Der dabei bestaetigte alte Radar-Tooltip-Fehler ist separat korrigiert und
   mit CI und gezielter Abnahme von 0.73 nach Neustart bestaetigt.
4. Erledigt: Moment.js 2.31.0 ersetzt 2.27.0 in den drei Standardvorlagen.
   Die aktuelle stabile Version wurde am 01.10.2026 gegen npm und den offiziellen
   GitHub-Release geprueft. Der Core-Bundle behaelt den bisherigen Locale-Umfang
   (`en`); Chart.js, Adapter und Plugins bleiben unveraendert. Der alte Moment-Pfad
   bleibt erhalten, erhaelt aber nicht die Upstream-Korrekturen der neuen Version.
   Adaptertests in UTC/Berlin und der gezielte Browservergleich sind bestanden;
   CI und gezielte Abnahme von 0.74 ohne Neustart sind bestanden.
5. Erledigt: chartjs-adapter-moment 1.0.1 ersetzt 1.0.0 in den drei
   Standardvorlagen. npm und offizieller Release bestaetigen am 01.10.2026 die
   aktuelle Version mit deklarierter Chart.js-4-Unterstuetzung. Der minifizierte
   Laufzeitcode ist nach Entfernen von Versionsbanner und altem Source-Map-Kommentar
   identisch zum bisherigen Bundle; der Schritt aendert keine Datumslogik.
   Die neue Distribution hat keine Source Map und keinen verwaisten Map-Verweis.
   Alter Pfad, Chart.js, Moment, Datalabels und Streaming bleiben unveraendert.
   Adaptertests, CI und Auslieferung auf 0.75 sind bestaetigt. Ein dabei gefundener
   Chart-Ladefehler trat mit beiden Adapterversionen auf. Er ist separat in 0.76
   korrigiert und einschliesslich beider Antwortreihenfolgen, Tooltips,
   Echtzeitachse, Vollreload und WebSocket abgenommen; Tests/Style/CodeQL gruen.
6. Erledigt: Datalabels bleibt auf 2.2.0, der am 02.10.2026 gegen npm und
   den offiziellen Release geprueften aktuellen stabilen Version. Der historische
   Bundle ist trotz `.min.js` unminifiziert und enthaelt drei lokale
   `constructor.name`-Pruefungen statt der originalen `instanceof`-Pruefungen.
   Die Standardvorlagen wechseln deshalb auf die unveraenderte offizielle
   Distribution im versionierten Pfad; der Altpfad bleibt erhalten. Lizenz,
   Paketintegritaet, Hashes und Unterschiede sind dokumentiert. Referenz- und
   Webhook-Tests sowie Browservergleich mit sichtbaren Labels sind bestanden;
   CI und gezielte installierte Abnahme von 0.77 sind ebenfalls bestanden.
7. Streaming-Audit abgeschlossen, zwei Einbindungsfehler lokal korrigiert:
   `frameRate` statt `framerate`, `update('quiet')` statt des alten
   `preservation`-Objekts bei Realtime-Achsen. Andere Zeitachsen verwenden den
   Standardmodus. Plugin und Pfad bleiben unveraendert. Automatisierte
   Regressionen und lokaler Browsertest bestanden; CI und gezielte installierte
   Abnahme von 0.78 sind ebenfalls bestanden. Der Prototyp nach ADR 0004 bestand
   das Performancegate nicht. ADR 0005 legt jetzt den eigenen Wartungsfork als
   Entwicklungsweg fest. Dessen Bundle 3.6.0 ist jetzt getrennt ueber einen
   versionierten Pfad integriert. CI und gezielte installierte Pruefung auf
   0.84 bestanden; Gesamtabnahme PARTIAL, siehe `STREAMING_INTEGRATION.md`.
8. Erledigt: jQuery 4.0.0 in allen 19 mitgelieferten HTML-Vorlagen;
   Altpfad erhalten. Der Eigentuemer bestaetigt Push, CI, Modulupdate und
   lokalen Chrome-Test von 0.85. Grenzen: `JQUERY_MIGRATION.md`.
9. Canvas Gauges 2.1.7 ist weiterhin die neueste offizielle Version und
   nach LF-Normalisierung identisch zum vorhandenen Bundle. Herkunft und
   Einbindung sind jetzt durch Hash-/Webhook-Tests und acht isolierte
   Browserfaelle abgesichert. Seit April 2020 kein neuer Release oder
   Default-Branch-Commit beobachtet: Pflege bleibt ein Risiko. Kein Wechsel
   von Engine, Bundle oder Pfad. Siehe `GAUGE_AUDIT.md`.
   iro.js bleibt auf Wunsch unveraendert.
10. Loading Bar/ldBar: Herkunft und CSS-Anpassungen geklaert. npm latest 0.1.1
    ist aelter als das bereits eingesetzte GitHub-Bundle. Kein Update und kein
    Bibliothekswechsel. Zehn statische Browserfaelle bestanden; Animation und
    Reverse-Updates hatten reproduzierte Bestandsfehler, siehe Audit.
    Der anschliessend freigegebene lokale Patch `0.1.1-jslive.1` und die
    Rohwertkorrektur in der Vorlage sind umgesetzt; Altpfad und CSS erhalten.
    Deterministische Standardtests und 32 animierte Browserfaelle bestanden;
    CI und installierte Abnahme folgen. Siehe `LOADING_BAR_PATCH.md`.

## Streaming: Herkunft und Pflegeentscheidung

Die folgende Fork-Abwaegung beschreibt den Ausgangspunkt. Der zwischenzeitliche
Versuch einer eigenen Echtzeitsteuerung nach [ADR 0004](adr/0004-own-realtime-controller.md)
wurde durch [ADR 0005](adr/0005-maintained-streaming-fork.md) abgeloest.
[Prototyp und Messungen](REALTIME_PROTOTYPE.md) bleiben historisch erhalten.
Die Entwicklung erfolgt im eigenen Wartungsfork. Die getrennte lokale
Integration von 3.6.0 ist umgesetzt; die gezielte installierte Pruefung von
0.84 ist bestanden, die Gesamtabnahme bleibt PARTIAL (siehe Integrationsnachweis).

Pruefstand 02.10.2026: Der historische unversionierte Bundle ist bytegleich mit
`dist/@qultoltd/chartjs-plugin-streaming.min.js` aus
[qultoltd-Commit aa653d8](https://github.com/qultoltd/chartjs-plugin-streaming/commit/aa653d89c224390c15ca39c22b9a28263a3ea981)
vom 03.08.2023 (Version 3.1.0, MIT). SHA-256:
`2e0ac91691bc76ff2c618c7d600a36cc3a10784ef34cd30ac968d72d6d15d839`.
Der bisherige npm-Name lieferte HTTP 404; daraus folgt keine gesicherte Aussage
ueber die Ursache. Das GitHub-Repository ist weiterhin verfuegbar.

Die dokumentierte [Push-/Async-Einbindung](https://nagix.github.io/chartjs-plugin-streaming/latest/guide/data-feed-models.html)
verwendet `update('quiet')`; die Option heisst `frameRate`. Der bisherige
Schreibfehler blieb durch den gleich hohen Defaultwert von 30 verdeckt.
Die beiden Korrekturen betreffen ausschliesslich die JSLive-Standardvorlage.

Urspruengliche Empfehlung vor ADR 0004: eigener, eng begrenzter
Wartungsfork vor einer vollstaendigen Eigenimplementierung. Ausgangspunkt
waere der bereits nachgewiesene Quellstand; vor Festlegung der Basis ist der
[modernisierte aziham-Fork](https://github.com/aziham/chartjs-plugin-streaming)
als Alternative zu vergleichen. Er nennt Chart.js ab 4.5.1, TypeScript und
Vite; dies ist noch kein Nachweis fuer JSLive-Kompatibilitaet oder langfristige
Pflege. Kein Fork wurde angelegt und kein fremder Ersatz eingebunden.

Ein eigener Fork muss reproduzierbare Builds, Lizenznachweise und Tests fuer
Scrolling, Aufbewahrung alter Punkte, Tooltips, Mischdiagramme, Pause/Resume
und Timerabbau erhalten. Er uebernimmt auch die Wartung der bestehenden
Zugriffe auf Chart.js-Interna. Eine Eigenimplementierung ueber Zeitachse,
`min`/`max` und oeffentliche Update-APIs koennte diese Kopplung reduzieren,
muesste aber Verhalten und Animation neu absichern. Sie ist erst nach einer
expliziten Anforderungsliste und einem getrennten Vergleichsprototyp sinnvoll.

Fortschreibung nach dem Prototyp: Der Eigentuemer hat den eigenen Wartungsfork
`Burki24/chartjs-plugin-streaming` als weiteren Weg gewaehlt, siehe
[ADR 0005](adr/0005-maintained-streaming-fork.md). Die eigene Steuerung bleibt
ein historischer Versuch. Nach isolierter Matrix, begrenztem Langlauf und
Lebenszykluskorrekturen im Fork folgt die Integration von 3.6.0 in die
Standard-Chartvorlage. Herkunft, Vergleiche und Freigabegrenzen stehen im
[Integrationsnachweis](STREAMING_INTEGRATION.md). Der Altpfad bleibt erhalten;
ein Luxon-Wechsel ist nicht beschlossen.

