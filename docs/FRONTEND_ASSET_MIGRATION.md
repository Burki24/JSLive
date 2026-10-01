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

Weitere unbenutzte Chart.js-Dateien werden in einem getrennten Schritt mit
eigener Kompatibilitaetsbewertung behandelt.
