# Canvas-Gauges-Pruefung

Stand: 03.10.2026, Ausgangspunkt JSLive 0.85 (`fc9a80a`).
Naechster isolierter Schritt nach dem jQuery-4-Wechsel in Phase 4.

## Ergebnis und Pflegeentscheidung

Canvas Gauges 2.1.7 ist weiterhin die neueste stabile Version laut npm und
offiziellem GitHub-Release. Der vorhandene Bundle stimmt nach CRLF-/LF-
Normalisierung bytegenau mit dem integritaetsgeprueften npm-Paket ueberein.
Der vollstaendige MIT-Lizenztext steht bereits im Dateikopf. Quellen, Datum,
Commit und Hash stehen in [SOURCES.md](../SymconJSLive/js/canvas-gauges/SOURCES.md).

Der letzte Release und der beim Audit gelieferte letzte Default-Branch-Commit
stammen von April 2020. Damit ist aktive Pflege nicht belegt; ein gruener
JSLive-Test beseitigt dieses Wartungsrisiko nicht. Eine neuere Version gibt es
zum Pruefzeitpunkt nicht. Die Bibliothek bleibt deshalb unveraendert erhalten.
Es wird weder ein neuer Fork noch eine zweite Gauge-Engine eingefuehrt.

Ein Ersatz muesste mindestens lineare/radiale Anzeigen und Kompass, Zeiger,
Wertbox, Ticks, nichtlineare Werteumrechnung, Hervorhebungen, Schriften und
Animationen abbilden. Die vorhandenen Properties und eigenen Templates sind
Kompatibilitaetsvertraege; ein optisch aehnliches Widget ist kein belegter
Eins-zu-eins-Ersatz. Ein solcher Wechsel benoetigt einen getrennten Vergleich
und eine ausdrueckliche Entscheidung. Die bestehende Chart.js-Distribution
wird in diesem Schritt nicht um eine neue Gauge-Implementierung erweitert.

## Neue Nachweise

- `tests/frontend-dependencies.php`: LF-normalisierter Bundle-Hash,
  Herkunftsnachweis und genau eine unveraenderte Gauge-URL in allen vier
  Vorlagen. Der neue Herkunftstest schlug vor Ergaenzung von `SOURCES.md`
  gezielt fehl und besteht danach.
- `tests/webhook-routing.php`: HTTP 200 und exakter Dateiinhalt am bestehenden
  oeffentlichen Pfad `/hook/JSLive/js/canvas-gauges/gauge.min.js`.
- `tests/gauge-browser.js`: acht isolierte Faelle, die vier Vorlagen Radial,
  Linear, Linear(vertical) und Compass jeweils bei 1024 und 390 Pixeln Breite.
  Echte jQuery-4.0.0-/Canvas-Gauges-2.1.7-Bundles, `util.js` und die aus den
  Vorlagen gelesenen Lade-/Update-/Umrechnungsfunktionen werden ausgefuehrt.
  Synthetische Konfiguration und lokal beantwortete GET-Anfragen ersetzen
  ausschliesslich die Symcon-Seite.

Die Browserfaelle pruefen Initialwert, Typ, Wertebegrenzung, nichtlineare
Skala und Highlights (ausser beim Kompass), Zahlenformatierung mit
Dezimal-/Tausendertrennzeichen, sichtbare Canvas-Aenderung, drei beendete
Wertanimationen, Schrift-Refresh und Deregistrierung nach `destroy()`.
Unerwartete Netzaufrufe, Dialoge und Browserfehler lassen den Test scheitern.
Die erste Testfassung wartete faelschlich auf eine geleerte interne
Animation-Frame-ID. Upstream behaelt diese nach Animationsende; der korrigierte
Test prueft den gerenderten Wert und das oeffentliche `animationEnd`-Ereignis.
Kein Produktfehler und keine Aenderung der Animation wurden daraus abgeleitet.

Lokaler Lauf: acht PASS mit Node 24.19.0, Playwright 1.63.0 und Edge
155.0.4283.18. Aufruf bei bereits vorhandenem Playwright:

```powershell
$env:NODE_PATH = 'E:\git\chartjs-plugin-streaming\node_modules'
$env:JSLIVE_BROWSER_EXECUTABLE = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
node tests/gauge-browser.js
```

Node 24 muss im PATH stehen. Der Test installiert keine Pakete und ist wie
die anderen optionalen Browsertests nicht Bestandteil der PHP-CI.

Abschlusspruefung: `php tests/run.php`, Syntaxcheck aller 45 Projekt-PHP-
Dateien (ohne vendorte Helper), PHP-CS-Fixer-Trockenlauf, JSON-Pruefung,
`node --check tests/gauge-browser.js` und `git diff --check` bestanden.
PHP-Laufzeit lokal: 8.5.10. Ausgangssuite und Syntax waren bereits vor den
Aenderungen gruen. CI dieser Aenderungen ist noch nicht ausgefuehrt.

## Grenzen und naechster Schritt

Kein vollstaendiger Template-/PHP-Renderingtest, kein WebSocket-/Pull-Dauertest,
kein Langlauf und keine reale Symcon-/IPSView-Abnahme. Andere Browserengines,
beliebige Konfigurationen und eigene Templates sind damit nicht freigegeben.
Der vom Eigentuemer gemeldete Chrome-Test des vorherigen jQuery-Schritts ist
kein gesonderter Nachweis fuer diese Gauge-Testmatrix.

Produktivcode, Bundle, Templates, Properties, gespeicherte Daten und Asset-URL
sind unveraendert. Fuer diesen reinen Nachweis-/Testschritt ist kein
Symcon-Modulupdate oder Dienstneustart erforderlich. CI der neuen Checks folgt
nach Commit/Push. Danach steht die getrennte Herkunfts-, Versions- und
Pflegepruefung von Loading Bar/ldBar an; iro.js bleibt vereinbarungsgemaess
unveraendert.
