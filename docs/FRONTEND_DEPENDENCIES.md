# Frontend-Abhaengigkeiten

Stand: 01.10.2026. Diese Inventur bildet die mitgelieferten Templates und die
vom Splitter unter `/hook/JSLive/js/` ausgelieferten Dateien ab. Sie beschreibt
den Ist-Zustand; insbesondere werden in diesem Schritt weder Bibliotheken
aktualisiert noch Assets entfernt oder Ladepfade geaendert.

Benutzerdefinierte Templates aus `TemplateScriptID` koennen weitere, hier nicht
kontrollierbare Abhaengigkeiten laden. Sie gehoeren nicht zum reproduzierbaren
Lieferumfang der Library.

## Nutzung je Modul

| Modul | Template(s) | Aktiv geladene Frontend-Bausteine |
| --- | --- | --- |
| `SymconJSLive` | `htmlbox/HtmlBox-Chart.html` | jQuery 3.6.0, `util.js`, `init.js`; danach indirekt `loader.js`, `jslive/Chart.js` und die ausgewaehlten Google-Font-CSS-Dateien |
| `SymconJSLiveAdvTextfield` | `Textfield1.html`, `Textfield2.html`, `FormExample.html` | jQuery 3.6.0, `util.js`, `css/TextField.css` oder `css/FormExample.css` |
| `SymconJSLiveChart` | `Chart.html` | jQuery 3.6.0, Chart.js 4.3.3, Moment.js 2.27.0, chartjs-adapter-moment 1.0.0, chartjs-plugin-streaming 3.1.0, chartjs-plugin-datalabels 2.2.0, `util.js` |
| `SymconJSLiveColorPicker` | `ColorPicker.html` | jQuery 3.6.0, `util.js`, externes `@jaames/iro@5` von jsDelivr |
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
| iro.js 5.5.0 | `SymconJSLive/js/iro/5.5.0/iro.js` | lokal unbenutzt; die lokale Referenz ist im ColorPicker auskommentiert | MPL-2.0-Hinweis im Dateikopf, kein separater Lizenztext |
| Loading Bar/ldBar | `SymconJSLive/js/loading-Bar/loading-bar.js`, `SymconJSLive/js/loading-Bar/loading-bar.css` | aktiv in `SymconJSLiveProgressbar`; Version im Bestand nicht ausgewiesen | `SymconJSLive/js/loading-Bar/LICENSE` (MIT) |
| MCDatepicker | `SymconJSLive/js/mc-calendar/mc-calendar.min.js` | unbenutzt; Referenzen in DatePicker und DateTimePicker sind auskommentiert | Version und Lizenz im Bestand nicht ausgewiesen; separater Lizenztext fehlt |
| Template-CSS | `SymconJSLive/js/css/DatePicker1.css`, `SymconJSLive/js/css/FormExample.css`, `SymconJSLive/js/css/TextField.css`, `SymconJSLive/js/css/TimePicker1.css`, `SymconJSLive/js/css/TimePicker2.css`, `SymconJSLive/js/css/TimePicker3.css` | aktiv gemaess Modultabelle | Projektlizenz `LICENSE` (GPL-3.0) |
| Unbenutztes Template-CSS | `SymconJSLive/js/css/DateTimePicker1.css`, `SymconJSLive/js/css/font-face.css` | unbenutzt; keine aktive Referenz | Projektlizenz `LICENSE` (GPL-3.0), eingebettete Font-URLs separat zu betrachten |
| Google-Font-Definitionen | 20 Dateien unter `SymconJSLive/js/css/fonts/` | bei konfigurierter Schrift dynamisch aktiv | keine lokalen Fontdateien und keine zugeordneten Font-Lizenztexte |

Die parallelen Chart.js-Dateien sind nicht alle austauschbare Duplikate:
`Chart.html` laedt 4.3.3, waehrend Doughnut/Pie und Radar 4.4.1 laden. Eine
Bereinigung muss deshalb zuerst visuell und funktional nachweisen, dass alle
drei Module mit derselben Version kompatibel sind.

## Externe Laufzeitressourcen

Zwei Ressourcengruppen verlassen zur Laufzeit das Symcon-System:

- `SymconJSLive/templates/ColorPicker.html` laedt
  `https://cdn.jsdelivr.net/npm/@jaames/iro@5`. Die Major-Version ist nicht auf
  einen konkreten Release fixiert; die vorhandene lokale Version 5.5.0 wird
  nicht verwendet.
- Die Dateien unter `SymconJSLive/js/css/fonts/` und die unbenutzte Sammeldatei
  `SymconJSLive/js/css/font-face.css` laden WOFF2-Dateien von
  `https://fonts.gstatic.com/`. Somit sind auch die dynamisch ausgewaehlten
  Schriften nicht offline reproduzierbar.

Die frei konfigurierbare Splitter-Adresse, IFrame-Ziele und Ressourcen in
benutzerdefinierten Templates sind Anwenderdaten und keine fest eingebauten
Frontend-Abhaengigkeiten.

## Konsequenzen fuer Phase 4

1. Als naechster getrennter Schritt werden iro.js und die Schriftdateien lokal,
   fest versioniert und mit den jeweils erforderlichen Lizenztexten
   bereitgestellt. Der ColorPicker sollte dabei zunaechst gegen die bereits
   vorhandene iro.js-Version 5.5.0 visuell regressionsgetestet werden.
2. Danach koennen die nachweislich unbenutzten Chart.js-3.x-Dateien,
   MCDatepicker sowie die beiden unbenutzten CSS-Dateien einzeln entfernt
   werden. Vorher sind benutzerdefinierte Template-Skripte und der historische
   HTMLBox-Pfad als moegliche externe Nutzer zu beruecksichtigen.
3. Ein Versionsupdate oder die Vereinheitlichung von Chart.js folgt erst nach
   dieser Bereinigung und benoetigt visuelle Regressionstests fuer Chart,
   Doughnut/Pie und RadarChart.

