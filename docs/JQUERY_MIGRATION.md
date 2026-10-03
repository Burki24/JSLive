# jQuery 4.0.0 in JSLive

Stand: 03.10.2026. Ausgangspunkt: JSLive 0.84, Commit `8bc5e02`.
Der Eigentuemer hat den direkten Wechsel von 3.6.0 auf 4.0.0 und den Wegfall
der Unterstuetzung aelterer Browser/WebViews ausdruecklich freigegeben.

## Umfang und Kompatibilitaet

Alle 18 Standardvorlagen und `htmlbox/HtmlBox-Chart.html` laden genau einmal
`/hook/JSLive/js/jquery/4.0.0/jquery.min.js`. Verwendet wird die vollstaendige
offizielle npm-Distribution: Slim ist wegen Ajax/jqXHR nicht geeignet.
MIT-Lizenz, Source Map, npm-Integritaet und Dateihashes liegen im neuen
[Assetverzeichnis](../SymconJSLive/js/jquery/4.0.0/SOURCES.md).
Es gibt keine neue externe Laufzeitquelle und keine Migrate-Laufzeitabhaengigkeit.

Der Altpfad `/hook/JSLive/js/jquery.min.js` bleibt mit 3.6.0 unveraendert.
Eigene `TemplateScriptID`-Vorlagen werden nicht automatisch umgestellt. Ihre
Plugins und API-Verwendungen muessen vor einem manuellen Wechsel separat
geprueft werden. Niemals beide jQuery-Versionen gleichzeitig laden.
Der Altpfad dient der Kompatibilitaet, nicht einer Zusage laufender Updates.

Die [offizielle 4.0-Browsermatrix](https://jquery.com/upgrade-guide/4.0/#browser-support)
entfernt unter anderem Edge Legacy, IE bis 10 und aeltere mobile Browser.
Fuer JSLive aktuelle Chromium-/Firefox-/Safari-Versionen beziehungsweise
entsprechende aktuelle WebViews verwenden. Die jQuery-Matrix allein ist keine
Zusicherung fuer alle JSLive-Bibliotheken, insbesondere nicht fuer IE11.
Die reale IPSView-Abnahme bleibt mangels Lizenz auf der Testebene offen;
sie wurde nicht durch einen Desktop-Browsertest ersetzt. Dies blockiert auf
Wunsch des Eigentuemers nicht die weitere Entwicklung, bleibt aber ein Release-Risiko.

Chart.js, Moment, Adapter, Datalabels, Streaming, iro.js und die anderen
Bibliotheken bleiben in diesem Schritt unveraendert. Auch PHP-Vertraege,
Properties, IDs, Datenstrukturen und gespeicherte Werte aendern sich nicht.

## API-Pruefung

Die mitgelieferten Vorlagen verwenden jQuery fuer `getJSON`,
`$(document).ready(...)` und im Formularbeispiel fuer Selektoren mit `.val()`.
Chart nutzt zusaetzlich `.fail()`/`.always()` zur Zusammenfuehrung asynchroner
Antworten. Der historische HTMLBox-Lader wartet mit `await` auf jqXHR.
Die API-Suche fand in diesem eigenen Code keine Verwendung der in 4.0
entfernten Helfer wie `$.isNumeric`, `$.parseJSON` oder `$.trim`; auch keine
JSONP-Automatik oder implizite Skriptausfuehrung ueber Ajax.
Ein API-Shim oder Umbau der Datenladefunktionen war deshalb nicht erforderlich.
Die [Upgrade-Hinweise](https://jquery.com/upgrade-guide/4.0/) bleiben fuer
eigene Templates verbindlicher Pruefstoff; deren Code wurde nicht untersucht.

## Lokale Nachweise

- Die Ausgangssuite und PHP-Syntaxpruefung waren vor Aenderungen gruen.
- `tests/frontend-dependencies.php` sichert alle 19 Einbindungen, die neue
  Distribution, Herkunft/Hashes und den unveraenderten Altbestand ab.
  Vor Integration schlug der erweiterte Inventartest erwartungsgemaess fehl.
- `tests/webhook-routing.php` sichert beide jQuery-URLs sowie die neue Source
  Map mit dem echten PHP-Webhook im Symcon-Stub ab.
- `tests/jquery-browser.js` verwendet echte jQuery-3.6.0-/4.0.0-Bundles und die
  originalen Chart-/Formularfunktionen. Keine Symcon-Verbindung: HTTP-Antworten
  werden lokal kontrolliert geliefert; Chart zeichnet hier nicht, sondern
  zeichnet die empfangene Konfiguration fuer Assertions auf.
  Zwoelf Szenarien sind bestanden: beide Antwortreihenfolgen, leere Daten,
  HTTP-503, ungueltiges JSON und null Datensaetze, jeweils fuer beide Versionen.
  Jeder Fall umfasst initialen Aufbau, Update und Destroy/Recreate; ausserdem
  DOM-Ready, Formularwerte und `await $.getJSON` mit Sonderzeichen.
- Der bestehende `tests/chart-streaming-browser.js` prueft ergaenzend echte
  Chart-Darstellung mit dem jQuery-Pfad der Vorlage. Dies ist ein isolierter
  Renderingtest mit synthetischen Daten, kein installierter Symcon-Nachweis.
  Alle acht Durchlaeufe sind bestanden. Beide Browsertests liefen mit Node
  24.19.0, Playwright 1.63.0 und Edge 155.0.4283.18.
- Die PHP-Gesamtsuite, PHP-Syntaxpruefung, PHP-CS-Fixer-Trockenlauf,
  JSON-Pruefung und `git diff --check` sind gruen.

Optionale Browserpruefungen (Playwright vom Aufrufer bereitgestellt, keine
automatische Installation; Node 24 und Chromium erforderlich):

```powershell
$env:NODE_PATH = 'E:\git\chartjs-plugin-streaming\node_modules'
$env:JSLIVE_BROWSER_EXECUTABLE = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
node tests/jquery-browser.js
node tests/chart-streaming-browser.js
```

Die Browsertests sind nicht Teil der PHP-CI. Eine vollstaendige visuelle
Abnahme aller Module, anderer Browserengines und realer IPSView-Geraete ist
damit nicht behauptet. CI und installierte Abnahme dieses Kandidaten stehen aus.

## Update und Rueckfall

Nach Commit/Push CI abwarten, Bot-Metadaten pullen und Modulupdate durchfuehren.
`ApplyChanges()` laeuft beim Modulupdate automatisch und erneuert die
HTML-Ausgaben. Danach Ansichten neu laden. Kein zusaetzlicher Aufruf oder
Dienstneustart und keine persistente Migration erforderlich.

Bei Problemen den vollstaendigen Stand `8bc5e02` (0.84) wiederherstellen,
Modulupdate ausfuehren und Ansichten neu laden. Eigene Templates vor einer
manuellen Umstellung separat sichern. Bei identischem Ursprung darf eine
Seite nicht parallel alten und neuen jQuery-Code in dasselbe Fenster laden.
