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

## Abschlussabgleich auf Basis 0.96: Animationsgrenze

Bei der erneuten Browsermatrix trat einmal ein Kompass-Timeout auf; ein
anschliessender Einzellauf bestand. Eine begrenzte Diagnose mit Ereignisfolge
reproduzierte den Fehler erneut im siebten Kompass-Durchlauf. Keine Symcon-
Verbindung, keine Aenderung am ausgelieferten Gauge-Bundle oder Template.

Die Wartebedingung `abs(gerendert - Ziel) < 0.0001` kann vor dem tatsaechlichen
Animationsende zutreffen. Beobachtet wurde `179.99999994635587` fuer Ziel 180;
das `animationEnd`-Ereignis war noch nicht eingetroffen. Der Test schickte dann
bereits 400 (durch die Vorlage auf 360 begrenzt). Canvas Gauges interpolierte
bis 360, setzte am Ende aber wieder den vorherigen Zielwert 180.

Zwei getrennte Ergebnisse:

- Testkorrektur: Die Matrix fuer aufeinanderfolgende Animationen wartet jetzt
  auf `animationEnd` UND den erwarteten Wert. Der Listener fuer die initiale
  Kompassanimation wird vor der Ajax-Antwort registriert. Die drei anderen
  Vorlagen erzeugen ihr Gauge dagegen erst mit dem gelesenen Initialwert.
  Die Wert-, Bild-, Ereigniszahl- und Destroy-Pruefungen bleiben erhalten;
  weder laengerer Timeout noch Wiederholung bis zum Erfolg kaschieren den Fehler.
  Nach Korrektur bestanden drei vollstaendige Durchlaeufe mit jeweils acht
  Faellen in Edge 155.0.4283.18 / Node 24.19.0. Dies ist ein begrenzter
  Wiederholungsnachweis fuer sequentielle Animationen, kein Langlauftest.
- Offener Bibliotheksfehler: Ein Update waehrend einer laufenden Animation
  behaelt im BaseGauge-Setter einen bereits gesetzten internen Zielwert bei.
  Der Abschlusscallback verwendet diesen alten Wert. Dies kann auch reale
  schnell aufeinanderfolgende Updates betreffen und ist kein reiner Testfehler.
  Die korrigierte sequentielle Testmatrix ist ausdruecklich kein Nachweis fuer
  unterbrochene Animationen. Eine getrennte kompatible Korrektur samt gezielter
  Regression ist vor abschliessender Freigabe erforderlich; kein neuer Fork
  oder Wechsel der Gauge-Engine ist damit beschlossen.

## Lokale Animationskorrektur auf Basis 0.98

Die Standardvorlagen laden jetzt den lokalen Assetpatch `2.1.7-jslive.1`.
Kein Upstream-Update, neuer Wartungsfork oder Wechsel der Gauge-Engine.
Der bisherige Bundle und seine URL bleiben bytegleich fuer eigene Vorlagen.
Die Versionskennung der Bibliothek bleibt 2.1.7; die lokale Revision steht im
Pfad und im zusaetzlichen Banner. Lizenz, Hash und exakte Reproduktion:
[Patch-SOURCES](../SymconJSLive/js/canvas-gauges/2.1.7-jslive.1/SOURCES.md).

Der gemeinsame Setter verwirft nun die vorherige Animation und uebernimmt das
neueste Ziel, interpoliert aber weiter vom gerade gerenderten Wert. Ein bereits
laufendes identisches Ziel wird weder abgebrochen noch vorzeitig angesprungen.
Ein Stopp am Zwischenwert und direkte Werte bei deaktivierter Animation
beenden alte Frames. Fuer RadialGauge wird der Originalzielwert getrennt vom
kuerzesten Interpolationswinkel uebergeben; Drehungen ueber Nord bleiben kurz
und enden beim angeforderten Originalwert. Scheduler, Easing, Zeichencode,
Werteumrechnung, Textformatierung und gespeicherte Konfiguration bleiben gleich.

Nachweise:

- Vor Aenderungen Gesamtsuite und 59 PHP-Syntaxchecks gruen.
- `tests/gauge-animation.js`: 34 deterministische Faelle mit echten Settern
  und Scheduler aus dem Bundle, nur Uhr, Canvas-Ausgabe und Ereignissenke als
  Doubles. Der Originalbundle scheiterte bei 180 -> 360 mit Zielwert 180;
  der Patch besteht. Steigend/fallend, Mehrfachwechsel, Rueckkehr, Anfang/Mitte/
  kurz vor Ende, Duplikate, Zwischenwert-Stopp, sofortige/initiale Werte und
  kuerzester Weg ueber Nord in beide Richtungen. Teil der Standard-CI.
- `node .github/scripts/build-gauge-patch.js --check`: Originalhash und exakt reproduzierter
  Patch ohne Compiler oder Netzwerk. Nur der BaseGauge-Setter, eine radiale
  Zielwertuebergabe, Banner und abschliessender Zeilenumbruch unterscheiden sich.
- `tests/gauge-browser.js`: zwoelf Varianten, vier Vorlagen, zwei Breiten,
  Nadel-/Plattenanimation bei Radial/Compass. Pro Variante neun unterbrochene
  Folgen, insgesamt 108; neuestes Ziel, gerenderter Wert, Rohwerttext und Canvas
  nach direktem Ziel-Redraw geprueft. Echte Assets, lokales HTTP; kein Symcon.
  Die vorhandenen sequentiellen Animationen und Destroy bleiben geprueft.
- Asset-/Lizenzhash und genau eine neue Einbindung in allen vier Vorlagen;
  alter und neuer Pfad ueber den echten PHP-Webhook mit HTTP 200/Dateiinhalt.

Bei der Browser-Testentwicklung musste die virtuelle Uhr vor dem Bibliotheks-
laden installiert werden: der Bundle merkt sich requestAnimationFrame beim
Laden. Spaeteres Ersetzen vermischte reale Frames mit virtueller Zeit. Der
Canvas-Vergleich nutzt draw() statt update(), weil update() auch Geometrie und
statische Canvas-Caches erneuert. Diese Testkorrekturen aendern keinen Produktcode.

Update: Eigentuemer committet/pusht, wartet gruene CI ab, pullt Metadaten und
aktualisiert das Symcon-Modul. Danach Ansicht ausdruecklich neu laden, bei
Bedarf ohne Browser-Cache; kein zusaetzliches ApplyChanges oder Dienstneustart.
Eigene Templates werden nicht automatisch migriert und koennen den alten
Fehler behalten. Nur einen Gauge-Bundle je Seite laden. Rueckfall auf den
vollstaendigen Stand `be9a96a` (0.98) bringt bewusst auch den Altfehler zurueck.
Installierter Gauge-Nachtest und IPSView-Abnahme bleiben offen; kein Stable-PASS.

Abschluss lokal: Gesamtsuite einschliesslich Patchreproduktion, Animation,
Asset-/Webhook- und 120 PHP-Renderingfaellen bestanden. 59 PHP-Syntaxchecks
(PHP 8.5.10), JavaScript-Syntax, PHP-CS-Fixer-Trockenlauf (47 Dateien, keine
Aenderungen) und `git diff --check` gruen. Browser: Edge 155.0.4283.18,
Node 24.19.0, Playwright 1.63.0. CI folgt nach Push; kein Live-System geaendert.
