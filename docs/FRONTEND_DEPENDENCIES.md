# Frontend-Abhaengigkeiten

Stand: 01.10.2026. Diese Inventur bildet die mitgelieferten Templates und die
vom Splitter unter `/hook/JSLive/js/` ausgelieferten Dateien ab. Die zuvor
extern geladenen Ressourcen sind lokalisiert; Bibliotheksversionen wurden dabei
nicht aktualisiert. Im ersten Bereinigungsschritt wurden MCDatepicker und zwei
unbenutzte CSS-Dateien entfernt; die Chart.js-Bestaende bleiben unveraendert.

Benutzerdefinierte Templates aus `TemplateScriptID` koennen weitere, hier nicht
kontrollierbare Abhaengigkeiten laden. Sie gehoeren nicht zum reproduzierbaren
Lieferumfang der Library.

## Nutzung je Modul

| Modul | Template(s) | Aktiv geladene Frontend-Bausteine |
| --- | --- | --- |
| `SymconJSLive` | `htmlbox/HtmlBox-Chart.html` | jQuery 3.6.0, `util.js`, `init.js`; danach indirekt `loader.js`, `jslive/Chart.js` und die ausgewaehlten lokalen Font-CSS-/WOFF2-Dateien |
| `SymconJSLiveAdvTextfield` | `Textfield1.html`, `Textfield2.html`, `FormExample.html` | jQuery 3.6.0, `util.js`, `css/TextField.css` oder `css/FormExample.css` |
| `SymconJSLiveChart` | `Chart.html` | jQuery 3.6.0, Chart.js 4.3.3, Moment.js 2.27.0, chartjs-adapter-moment 1.0.0, chartjs-plugin-streaming 3.1.0, chartjs-plugin-datalabels 2.2.0, `util.js` |
| `SymconJSLiveColorPicker` | `ColorPicker.html` | jQuery 3.6.0, `util.js`, lokale iro.js 5.5.0 |
| `SymconJSLiveCustom` | `Default.html` oder benutzerdefiniertes Template | Das mitgelieferte Default-Template nutzt jQuery 3.6.0 und `util.js`; benutzerdefinierte Skripte liegen ausserhalb dieser Inventur |
| `SymconJSLiveDateTimePicker` | `TimePicker1.html`, `TimePicker2.html`, `TimePicker3.html`, `DatePicker1.html`, `DateTimePicker1.html` | jQuery 3.6.0, `util.js` und die jeweilige Template-CSS-Datei; DatePicker und DateTimePicker verwenden beide `css/DatePicker1.css` |
| `SymconJSLiveDoughnutPie` | `Doughnut-PIE.html` | jQuery 3.6.0, Chart.js 4.4.1, Moment.js 2.27.0, chartjs-adapter-moment 1.0.0, chartjs-plugin-datalabels 2.2.0, `util.js` |
| `SymconJSLiveGauge` | vier `CanvasGauges-*.html`-Templates | jQuery 3.6.0, Canvas Gauges 2.1.7, `util.js` |
| `SymconJSLiveProgressbar` | `Progressbar.html` | jQuery 3.6.0, Loading Bar/`ldBar` mit nicht im Asset ausgewiesener Version, `loading-bar.css`, `util.js` |
| `SymconJSLiveRadarChart` | `RadarChart.html` | jQuery 3.6.0, Chart.js 4.4.1, Moment.js 2.27.0, chartjs-adapter-moment 1.0.0, chartjs-plugin-datalabels 2.2.0, `util.js` |

Alle mitgelieferten Visualisierungstemplates verwenden damit jQuery und
`util.js`. Die Schriftwahl wird zentral durch `JSLiveModule.php` beziehungsweise
im historischen HTMLBox-Lader auf eine der Dateien unter
`SymconJSLive/js/css/fonts/` abgebildet.

## Paket- und Asset-Inventur

| Paket/Baustein | Lokaler Bestand | Laufzeitstatus | Lizenznachweis im Repository |
| --- | --- | --- | --- |
| jQuery 3.6.0 | `SymconJSLive/js/jquery.min.js` | aktiv in allen mitgelieferten Templates | MIT-Hinweis im Dateikopf |
| JSLive-Browserlaufzeit | `SymconJSLive/js/util.js`, `SymconJSLive/js/init.js`, `SymconJSLive/js/loader.js`, `SymconJSLive/js/jslive/Chart.js` | `util.js` direkt aktiv; die drei uebrigen Dateien indirekt aktiv ueber `HtmlBox-Chart.html` | Projektlizenz `LICENSE` (GPL-3.0) |
| Chart.js 4.3.3 | `SymconJSLive/js/chartjs/chart.js` | aktiv nur in `SymconJSLiveChart` | MIT-Hinweis im Dateikopf |
| Chart.js 4.4.1 | `SymconJSLive/js/chartjs/chart.min.js` | aktiv in DoughnutPie und RadarChart | MIT-Hinweis im Dateikopf |
| Chart.js 3.9.1 Module/Helper | `SymconJSLive/js/chartjs/chart.esm.js`, `chart.mjs`, `helpers.esm.js`, `helpers.mjs` | unbenutzt; kein mitgeliefertes Template importiert diese Dateien | MIT-Hinweis im Dateikopf |
| Chart.js 3.6.0 samt Plugins | `SymconJSLive/js/chartjs/3.6.0/` | unbenutzt; historischer Parallelbestand | MIT-Hinweise in den Dateikoepfen |
| chartjs-adapter-moment 1.0.0 | `SymconJSLive/js/chartjs/plugins/chartjs-adapter-moment.js` | aktiv in den drei Chart-Modulen; bytegleiches Duplikat im unbenutzten Verzeichnis `3.6.0/plugins/` | MIT-Hinweis im Dateikopf |
| chartjs-plugin-datalabels 2.2.0 | `SymconJSLive/js/chartjs/plugins/chartjs-plugin-datalabels.min.js` | aktiv in den drei Chart-Modulen; die unminifizierte Datei `chartjs-plugin-datalabels.js` ist unbenutzt | MIT-Hinweis im Dateikopf |
| chartjs-plugin-streaming 3.1.0 | `SymconJSLive/js/chartjs/plugins/chartjs-plugin-streaming.min.js` | aktiv nur in `SymconJSLiveChart` | MIT-Hinweis im Dateikopf |
| Moment.js 2.27.0 | `SymconJSLive/js/moment/2.27.0/Moment.js` | aktiv in den drei Chart-Modulen | MIT-Hinweis im Dateikopf |
| Canvas Gauges 2.1.7 | `SymconJSLive/js/canvas-gauges/gauge.min.js` | aktiv in `SymconJSLiveGauge` | vollstaendiger MIT-Text im Dateikopf |
| iro.js 5.5.0 | `SymconJSLive/js/iro/5.5.0/iro.js` | aktiv im ColorPicker; lokal und fest versioniert | MPL-2.0-Hinweis im Dateikopf und `SymconJSLive/js/iro/5.5.0/LICENSE.txt` |
| Loading Bar/ldBar | `SymconJSLive/js/loading-Bar/loading-bar.js`, `SymconJSLive/js/loading-Bar/loading-bar.css` | aktiv in `SymconJSLiveProgressbar`; Version im Bestand nicht ausgewiesen | `SymconJSLive/js/loading-Bar/LICENSE` (MIT) |
| Template-CSS | `SymconJSLive/js/css/DatePicker1.css`, `SymconJSLive/js/css/FormExample.css`, `SymconJSLive/js/css/TextField.css`, `SymconJSLive/js/css/TimePicker1.css`, `SymconJSLive/js/css/TimePicker2.css`, `SymconJSLive/js/css/TimePicker3.css` | aktiv gemaess Modultabelle | Projektlizenz `LICENSE` (GPL-3.0) |
| Web Fonts | 20 CSS-Dateien unter `SymconJSLive/js/css/fonts/` und 20 WOFF2-Dateien unter `SymconJSLive/js/fonts/` | bei konfigurierter Schrift dynamisch aktiv; lokal und ueber SHA-256 reproduzierbar | Quellen, Hashes und Lizenzzuordnung in `SymconJSLive/js/fonts/SOURCES.md`; 17 familienbezogene OFL-Texte und Apache-2.0 liegen unter `fonts/licenses/` |

Die parallelen Chart.js-Dateien sind nicht alle austauschbare Duplikate:
`Chart.html` laedt 4.3.3, waehrend Doughnut/Pie und Radar 4.4.1 laden. Eine
Bereinigung muss deshalb zuerst visuell und funktional nachweisen, dass alle
drei Module mit derselben Version kompatibel sind.

MCDatepicker (`SymconJSLive/js/mc-calendar/mc-calendar.min.js`) sowie
`SymconJSLive/js/css/DateTimePicker1.css` und
`SymconJSLive/js/css/font-face.css` wurden als erste unbenutzte Asset-Gruppe
entfernt. Die bisherigen URLs liefern danach HTTP 404. Eigene Templates muessen
vor einem Update gemaess [Asset-Migration](FRONTEND_ASSET_MIGRATION.md) geprueft
werden. Die aktiven DatePicker- und DateTimePicker-Vorlagen nutzen weiterhin
`css/DatePicker1.css`, Schriften ihre familienbezogenen CSS-Dateien.

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
   mit Quellen, Hashes und Lizenztexten bereitgestellt. Die Browserabnahme muss
   ColorPicker und mindestens eine dynamisch geladene Schrift pruefen.
2. Begonnen: MCDatepicker und die beiden unbenutzten CSS-Dateien sind nach
   Referenzpruefung einschliesslich der Skripte auf MCP-CURRENT entfernt.
   Migration und Rueckfall sind dokumentiert. Als naechste Gruppe folgen die
   unbenutzten Chart.js-3.x-Dateien; benutzerdefinierte Template-Skripte und der
   historische HTMLBox-Pfad bleiben dabei zu beruecksichtigen.
3. Ein Versionsupdate oder die Vereinheitlichung von Chart.js folgt erst nach
   dieser Bereinigung und benoetigt visuelle Regressionstests fuer Chart,
   Doughnut/Pie und RadarChart.

