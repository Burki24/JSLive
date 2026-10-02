# Bereinigung historischer Frontend-Assets

## Erster Schritt: MCDatepicker und unbenutzte CSS-Dateien

Ausgangspunkt ist Commit `78f9131` (Lokalisierung von iro.js und Fonts).
Im nachfolgenden Entwicklungsstand entfallen ausschliesslich diese Dateien
und damit ihre bisherigen URLs unter `/hook/JSLive/js/`:

| Entfallender Pfad unter `js/` | Migration eigener Templates |
| --- | --- |
| `mc-calendar/mc-calendar.min.js` | Auskommentierte Einbindungen koennen entfernt werden. Bei aktiver Nutzung vor dem Update auf die mitgelieferten DatePicker-/DateTimePicker-Vorlagen umstellen oder eine eigene, versionierte und lizenzierte MCDatepicker-Abhaengigkeit bereitstellen. Es gibt keinen automatischen API-Ersatz. |
| `css/DateTimePicker1.css` | Die mitgelieferten Vorlagen `DatePicker1.html` und `DateTimePicker1.html` verwenden bereits `css/DatePicker1.css`. Eigene Vorlagen anhand dieser Vorlagen anpassen und visuell pruefen; die Stylesheets sind kein zugesicherter Eins-zu-eins-Ersatz. |
| `css/font-face.css` | Die benoetigten familienbezogenen Stylesheets aus `css/fonts/` gezielt laden. Die gemeinsame Font-Erzeugung und der HTMLBox-Lader verwenden diese Pfade bereits. |

Die aktive Datums-/Zeitauswahl, alle 20 lokalen Fonts, iro.js und saemtliche
Chart.js-Versionen bleiben in diesem Schritt unveraendert. Modul-IDs,
Properties, Variablen und gespeicherte Konfigurationen werden nicht migriert.

## Nachweise und Grenzen

- Die mitgelieferten Vorlagen referenzieren MCDatepicker nur in zwei
  HTML-Kommentaren; diese Einbindungen werden ebenfalls entfernt.
- Die beiden CSS-Dateien werden weder von den mitgelieferten Templates noch
  vom historischen HTMLBox-Lader oder der gemeinsamen PHP-Font-Erzeugung
  eingebunden.
- Am 01.10.2026 wurden die drei Skripte auf MCP-CURRENT rein lesend nach den
  drei Dateinamen beziehungsweise `mc-calendar` durchsucht: keine Treffer.
  Dies belegt keine Nichtnutzung in anderen Installationen, externem HTML,
  Medieninhalten oder dynamisch zusammengesetzten URLs.
- Der Template-Referenztest prueft die Existenz lokal eingebundener Dateien.
  Der Webhook-Test sichert HTTP 404 mit leerem Body fuer die entfernten Pfade
  sowie die weitere Auslieferung von `DatePicker1.css` und Font-CSS ab.

## Update, Wiederholung und Rueckfall

Vor einem Update eigene Templates und externe Einbindungen nach den genannten
Pfaden durchsuchen. Bei aktiver Nutzung zuerst die jeweilige Migration
abschliessen; andernfalls den bisherigen Library-Stand beibehalten. Ein Update
ersetzt keine benutzerdefinierten Skripte. `UpdateTemplates()` nicht pauschal
zur Migration aufrufen, da es vorhandene gleichnamige Vorlagen ueberschreibt.

Nach dem Update werden die drei entfallenen URLs mit HTTP 404 beantwortet.
Es entstehen weder Weiterleitungen noch automatische Versionswechsel.
Erneutes `ApplyChanges()` und Dienstneustarts fuehren keine Datenmigration aus.
Bei einer unvollstaendigen Aktualisierung den gesamten Library-Stand erneut
bereitstellen, damit Templates und Assets zusammenpassen.

Zum Rueckfall den vorherigen vollstaendigen Library-Stand verwenden; die Dateien
sind in der Git-Historie unter `78f9131` erhalten. Gesicherte eigene Templates
gegebenenfalls separat zurueckspielen. Danach Browsercache leeren und die
Datums-/Zeitauswahl sowie konfigurierte Schriften erneut pruefen.

## Zweiter Schritt: historische Chart.js-Dateien und Plugins

Ausgangspunkt ist JSLive 0.70, Commit `2722afa`. Im nachfolgenden
Entwicklungsstand werden neun weitere Dateien entfernt:

| Entfallende Pfade unter `js/` | Migration eigener Templates |
| --- | --- |
| `chartjs/chart.esm.js`, `chartjs/chart.mjs`, `chartjs/helpers.esm.js`, `chartjs/helpers.mjs` | Historische ES-Module von Chart.js 3.9.1. Eigene `import`-Aufrufe vor dem Update auf eine selbst bereitgestellte, vollstaendige und fest versionierte Distribution umstellen oder die Vorlage bewusst auf die mitgelieferte Chart.js-4-API migrieren. Die aktiven klassischen Browserbundles sind kein direkter Ersatz fuer ES-Module. |
| `chartjs/3.6.0/chart.min.js` | Chart.js 3.6.0. Eigene Vorlagen vor dem Update mit passender Plugin-Kombination auf Chart.js 4 migrieren und visuell pruefen oder ihre bisherige Version selbst bereitstellen. Kein automatischer Versionswechsel. |
| `chartjs/3.6.0/plugins/chartjs-adapter-moment.js` | Bytegleich zur weiterhin bereitgestellten Version 1.0.0 unter `chartjs/plugins/chartjs-adapter-moment.js`; nur den Pfad anpassen. |
| `chartjs/3.6.0/plugins/chartjs-plugin-datalabels.min.js`, `chartjs/3.6.0/plugins/chartjs-plugin-streaming.min.js` | Historische Plugins nur zusammen mit dem jeweiligen Chart.js-Hauptversionswechsel migrieren und testen. Die aktiven Plugins liegen unter `chartjs/plugins/`; sie werden nicht automatisch an den alten URLs ausgeliefert. |
| `chartjs/plugins/chartjs-plugin-datalabels.js` | Die ebenfalls enthaltene Version 2.2.0 unter `chartjs/plugins/chartjs-plugin-datalabels.min.js` verwenden und die Beschriftungen pruefen. |

Kein mitgeliefertes Template, keine PHP-Assetreferenz und auch der historische
HTMLBox-Lader binden diese neun Dateien ein. Die erneute rein lesende Suche in
den drei Skripten auf MCP-CURRENT am 01.10.2026 fand keine Verweise auf
`chartjs/3.6.0`, die vier ES-Module oder `chartjs-plugin-datalabels.js`.
Die Grenzen dieser Suche entsprechen dem ersten Schritt.

Die aktiven Dateien `chartjs/chart.js` (4.3.3), `chartjs/chart.min.js` (4.4.1)
und ihre drei Plugins bleiben bytegleich erhalten. Ebenso bleiben alle
mitgelieferten Templates und Modulkonfigurationen in diesem Schritt erhalten.
Der Webhook-Test prueft die Auslieferung dieser aktiven Dateien und HTTP 404
mit leerem Body fuer alle neun entfallenden URLs. Die Diagramme werden im
Browser zusaetzlich mit den lokalen Kandidaten-Assets geprueft; dies ersetzt
nicht die anschliessende Abnahme nach dem Modulupdate in Symcon.

Die Update- und Wiederholungsregeln des ersten Schritts gelten auch hier.
Bei eigenen aktiven Referenzen zuerst migrieren, bei Fehlern den vollstaendigen
Stand 0.70 wiederherstellen; alle neun Dateien bleiben unter `2722afa` in der
Git-Historie verfuegbar. Es werden keine Anwenderskripte automatisch veraendert.

## Dritter Schritt: gemeinsame Chart.js-Version der Standardvorlagen

Ausgangspunkt ist JSLive 0.71, Commit `787d24a`. Die mitgelieferten Vorlagen
`Chart.html`, `Doughnut-PIE.html` und `RadarChart.html` wechseln von
Chart.js 4.3.3 beziehungsweise 4.4.1 gemeinsam auf
`chartjs/4.5.1/chart.umd.min.js`. Diese am 01.10.2026 gepruefte aktuelle stabile
Version stammt aus der offiziellen npm-Distribution; Source Map, MIT-Lizenzen,
Paketintegritaet und Dateihashes sind unter `chartjs/4.5.1/` mitgeliefert.
Moment, Adapter, Datalabels und Streaming-Plugin bleiben
auf ihren bisherigen Versionen; neue externe Laufzeitabhaengigkeiten entstehen
nicht.

Die historischen Pfade `chartjs/chart.js` und `chartjs/chart.min.js` bleiben
mit ihrem bisherigen Inhalt (4.3.3 und 4.4.1) erreichbar.
Eigene `TemplateScriptID`-Skripte behalten dadurch ihre
bisherige Version. Eine automatische Anpassung oder ein pauschales
`UpdateTemplates()` findet nicht statt. Eigene Vorlagen koennen nach einer
separaten visuellen und funktionalen Pruefung auf
`chartjs/4.5.1/chart.umd.min.js` umgestellt werden. Niemals mehrere
Chart.js-Versionen gemeinsam in dieselbe Seite laden.

Es aendern sich keine Properties, Instanz-IDs, Variablen, Datenformate oder
PHP-Funktionen. Nach dem Modulupdate die drei Diagrammausgaben neu laden; bei aktivem
HTML-Cache diesen ueber das bestehende `ApplyChanges()` neu aufbauen. Wiederholtes
Anwenden oder Neustarts fuehren keine persistente Migration aus. Bei Problemen
den vollstaendigen Stand 0.71 wiederherstellen und die Ausgabe neu laden.

Der Referenztest sichert die einmalige gemeinsame Einbindung in allen drei
Standardvorlagen, die neue Distribution und die Versionen der beiden
weiterhin ausgelieferten Altpfade ab.
Der lokale Browservergleich umfasst identische Daten, Desktop/Mobil, alle
drei Diagrammtypen, Adapter, Datalabels, Streaming, Neuladen und Interaktionen.
Die Ergebnisse und ein bereits mit 4.4.1 vorhandener Radar-Tooltip-Fehler sind
in `SYCON_RUNTIME_MATRIX.md` festgehalten. Der Versionsschritt behebt diesen
Bestandsfehler nicht. Ein lokaler Browsernachweis ersetzt nicht CI und die
gezielte Abnahme nach dem noch ausstehenden Modulupdate.

## Vierter Schritt: Moment.js 2.31.0

Ausgangspunkt ist die abgenommene JSLive 0.73, Commit `73351d1`.
Nur die drei Standardvorlagen wechseln von `moment/2.27.0/Moment.js` auf
`moment/2.31.0/moment.min.js`. Die am 01.10.2026 gegen npm und den offiziellen
Release gepruefte aktuelle stabile Distribution wird mit Source Map, MIT-Lizenz,
Paketintegritaet und Dateihashes geliefert. Chart.js 4.5.1, Adapter 1.0.0,
Datalabels und Streaming bleiben unveraendert. Der Core-Bundle enthaelt weiterhin
nur die Locale `en`; weder weitere Locales noch Moment Timezone kommen hinzu.

Der alte Pfad bleibt bytegleich erreichbar. Eigene `TemplateScriptID`-Vorlagen
werden nicht automatisch umgestellt und erhalten dadurch auch nicht die
Upstream-Korrekturen von 2.31.0. Solche Vorlagen separat mit ihren Datumsformaten,
Zeitzonen und Plugins pruefen und danach gezielt auf den neuen Pfad umstellen.
Nur eine Moment-Version und diese vor dem Adapter laden.

Properties, Datensaetze, PHP-Vertraege und gespeicherte Konfigurationen aendern
sich nicht. Nach dem Modulupdate Diagramme neu laden; einen aktiven HTML-Cache
ueber das bestehende `ApplyChanges()` erneuern. Wiederholung und Neustart fuehren
keine persistente Migration aus. Bei Problemen den vollstaendigen Stand 0.73
wiederherstellen und die Ausgaben erneut laden; eigene Vorlagen separat sichern.

Adaptertests vergleichen beide Distributionen in UTC und Europe/Berlin,
einschliesslich Parsing, Formatierung, Schaltjahr und Zeitumstellung. Referenz-,
Hash- und Webhook-Tests sichern den neuen sowie den beibehaltenen Ladepfad ab.
Der isolierte Browservergleich ist in `SYCON_RUNTIME_MATRIX.md` dokumentiert;
CI und die gezielte Abnahme nach dem Modulupdate stehen noch aus.

## Fuenfter Schritt: Moment-Adapter 1.0.1

Ausgangspunkt ist die abgenommene JSLive 0.74, Commit `540e1de`.
Die drei Standardvorlagen wechseln von `chartjs/plugins/chartjs-adapter-moment.js`
auf `chartjs/plugins/moment/1.0.1/chartjs-adapter-moment.min.js`. Version 1.0.1
ist am 01.10.2026 gegen npm und den offiziellen Release geprueft und deklariert
Chart.js-4-Unterstuetzung. Chart.js 4.5.1, Moment 2.31.0 und die anderen Plugins
bleiben unveraendert. Paketintegritaet, Dateihashes und MIT-Lizenz liegen im neuen
Verzeichnis; eine Source Map ist in dieser Upstream-Distribution nicht enthalten.

Der alte Adapterpfad bleibt mit 1.0.0 bytegleich erhalten. Eigene Vorlagen werden
nicht automatisch umgestellt. Bei manueller Umstellung Chart.js und Moment vor
genau einem Adapter laden und Datumsachsen, Tooltips und Streaming pruefen.
Keine Properties, Datenformate oder gespeicherten Konfigurationen aendern sich.

Nach dem Modulupdate Ausgaben neu laden; bei aktivem HTML-Cache diesen ueber
das bestehende `ApplyChanges()` erneuern. Ein Dienstneustart ist fuer diesen
Asset-Schritt nicht erforderlich. Wiederholung fuehrt keine persistente
Migration aus. Bei Problemen den vollstaendigen Stand 0.74 wiederherstellen;
eigene Vorlagen vorher separat sichern und gegebenenfalls zurueckspielen.

Die vorhandenen Adaptertests vergleichen alte und neue Kombination in UTC und
Berlin. Referenz-, Integritaets- und Webhook-Tests sichern neue und alte Pfade.
Der isolierte Browservergleich ersetzt nicht die noch ausstehende CI und
gezielte Abnahme nach dem Modulupdate.

## Sechster Schritt: offizielle Datalabels-Distribution 2.2.0

Ausgangspunkt ist die abgenommene JSLive 0.76, Commit `124cf6c`.
Die drei Standardvorlagen wechseln von
`chartjs/plugins/chartjs-plugin-datalabels.min.js` auf
`chartjs/plugins/datalabels/2.2.0/chartjs-plugin-datalabels.min.js`.
Die Plugin-Version bleibt 2.2.0 (am 02.10.2026 weiterhin npm `latest` und
aktueller offizieller Release). Chart.js, Moment, Adapter und Streaming
bleiben unveraendert. Der neue Bundle und die MIT-Lizenz sind unveraendert aus
dem integritaetsgeprueften npm-Paket uebernommen; keine Source Map enthalten.

Der historische Bundle ist trotz seines Dateinamens unminifiziert und ersetzt
an drei Stellen die originale `instanceof`-Elementerkennung durch
`constructor.name`. Dieser lokal modifizierte Altpfad bleibt unveraendert;
eigene `TemplateScriptID`-Vorlagen werden nicht automatisch angepasst. Die
Original-Erkennung funktioniert mit dem gemeinsamen Chart.js-4.5.1-Bundle;
eigene Kombinationen vor einer manuellen Umstellung gesondert pruefen.
Nur ein Datalabels-Plugin und dieses nach Chart.js laden.

Formatierer, Styles, Properties, Datenformate und gespeicherte Konfigurationen
bleiben gleich. Nach dem Modulupdate die Diagramme neu laden; bei aktivem
HTML-Cache diesen ueber das bestehende `ApplyChanges()` erneuern. Kein
Dienstneustart und keine persistente Migration erforderlich. Bei Problemen
den vollstaendigen Stand 0.76 wiederherstellen und die Ausgaben neu laden;
eigene Vorlagen vor einer Anpassung separat sichern.

Referenz-, Hash- und Webhook-Tests sichern beide Pfade. Der isolierte
Browservergleich umfasst sichtbare Datenbeschriftungen in allen drei
Diagrammvorlagen. CI und die gezielte Abnahme nach dem Modulupdate bleiben
erforderlich; Details stehen in `SYCON_RUNTIME_MATRIX.md`.
