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

Historisch war auf Wunsch des Eigentuemers die Suche nach einer moderneren
Gauge-Alternative vorgemerkt. Vergleichskriterien bleiben im
[Projektplan](../PROJECT_CONTEXT.md#vorgemerkt-fuer-ein-spaeteres-update-moderne-gauges).
Seit der Wartungsentscheidung vom 03.10.2026 ist dies kein aktiver JSLive-ToDo
mehr: [ADR 0006](adr/0006-maintenance-scope.md). Gauge ist die bevorzugte Wahl
des Eigentuemers fuer weitere Bestandspruefung; ein Bibliothekswechsel oder
eine native Kachel ist nicht freigegeben. Das separat geplante ECharts-Modul
wird durch dieses Audit weder implementiert noch fachlich festgelegt.

Nachtrag 03.10.2026: Der Eigentuemer hat ein Test-Gauge angelegt und bestaetigt
die ordentliche Darstellung in der Kachel; ein Screenshot zeigt die vorhandene
Gauge-Ausgabe. Dies ist ein erfolgreicher anwenderseitiger Sichttest einer
Konfiguration. Verwendete Transportart, Browser und genaue Laufzeitversion
sind damit nicht separat belegt; kein HTML-SDK- oder IPSView-Gesamtnachweis.
Ein Neubau der Kachelausgabe wird daraus nicht erforderlich.

## Wartungsschritt: Highlight-Deckkraft am 03.10.2026

Ausgangsstand 0.92, Quellcommit `8860c44`, Metadatencommit `ad94f5e`.
Bei der begrenzten Gauge-Bestandspruefung wurde ein PHP-Renderingfehler
nachgewiesen: `GenerateHighlights()` verwendete die Messwert-Property
`precision` auch fuer die Alpha-Komponente der Farbe. Dadurch wurde
beispielsweise `HighlightColor_Alpha = 0.25` bei `precision = 0` zu `0`
(unsichtbar) und bei `precision = 1` zu `0.3`.

Die bestehende Formularspalte definiert Alpha mit zwei Nachkommastellen,
ebenso behandelt `GetConfigurationData()` die anderen Gauge-Farben. Die
Ein-Zeilen-Korrektur verwendet daher auch fuer Highlight-Alpha fest zwei
Nachkommastellen. Bereichsgrenzen und Messwerte folgen weiter `precision`;
Properties, gespeicherte Konfiguration, Bibliothek und Vorlagen sind unveraendert.

`tests/gauge-rendering.php` rendert ueber das echte Gauge-Modul die vier
Standardvorlagen und eine synthetische eigene Vorlage mit allen vier
Messwertpraezisionen sowie Alpha 0, 0.01, 0.25, 0.75, 0.99 und 1. Insgesamt
120 Faelle pruefen die erzeugten Highlight-Farben, sortierte/gerundete
Bereichsgrenzen, Vergleich mit Platten-Alpha und unveraenderte Konfiguration.
Die eigene Vorlage prueft zusaetzlich den formatierten Messwert.
Vor dem Fix scheiterte der Test gezielt bei Radial/precision 0/Alpha 0.25
mit `rgba(17, 34, 51, 0)`; nach der Korrektur bestehen alle Faelle.
Der neue Test ist ueber `tests/run.php` Teil der Standard-CI.

Auch die acht vorhandenen isolierten Faelle in `tests/gauge-browser.js`
bestanden erneut mit Edge 155.0.4283.18. Diese Browsermatrix verwendet
synthetische Konfiguration und ist kein Ende-zu-Ende-Nachweis des PHP-Fixes.
Der neue PHP-Test belegt dessen tatsaechliche Templateausgabe; auch der
Kompassfall prueft nur den erzeugten Highlight-Datenblock, nicht sichtbare
Kompass-Highlight-Bereiche. Keine neue Symcon-/WebSocket-/Pull-/IPSView-Abnahme.

Nach Push und gruener CI: Modulupdate, danach betroffene Ansicht neu laden.
`ApplyChanges()` erfolgt beim Modulupdate automatisch und erneuert die
HTML-Ausgabe; ein zusaetzlicher Aufruf oder geplanter Dienstneustart ist fuer
diesen Fix nicht erforderlich. Die installierte Abnahme soll an einem Gauge
mit Teiltransparenz und Messwertpraezision 0/1 erfolgen. Bestehende Ansichten
koennen durch die nun korrekt angewendete Deckkraft sichtbar anders aussehen.

### Abnahme-Nachtrag fuer 0.93

Der Eigentuemer bestaetigte Push, lokalen Abgleich und Symcon-Modulupdate.
Lokal und auf der MCP-Testebene wurde Version 0.93 nachgewiesen
(Quellcommit `e9b51e5`, Metadatencommit `69c96c5`). Die lesende MCP-Pruefung
fand die feste zweistellige Alpha-Formatierung im installierten Gauge-Code
und eine aktive Gauge-Instanz. Deren Konfiguration hatte jedoch keine
Highlights und Messwertpraezision 2; der konkrete Fehlerfall konnte dort
ohne Konfigurationsaenderung nicht geprueft werden. Der lokale PHP-Test
bestand erneut mit 120 Faellen. Es wurden keine Live-Werte oder Properties
veraendert und kein zusaetzliches ApplyChanges ausgefuehrt.

Anschliessend bestaetigte der Eigentuemer den Live-Test auf einem anderen
Produktivsystem. Damit ist der gezielte Gauge-Fix abgeschlossen. Die Aussage
zum Produktivsystem ist eine Anwenderbestaetigung, kein unabhaengiger MCP-
Nachweis; genaue dortige Testparameter wurden nicht uebermittelt. Weder eine
neue Gesamtmatrix-/IPSView-Abnahme noch ein unabhaengig abgerufener CI-Status
wird daraus abgeleitet.
