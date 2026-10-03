# Loading Bar / ldBar: Herkunft und Integrationsaudit

Fortschreibung: Beide unten historisch dokumentierten Fehler sind lokal durch
den freigegebenen [versionierten Patch](LOADING_BAR_PATCH.md) korrigiert.
Die Fehlerproben sind nun gruene Regressionen; CI und installierte Abnahme des
Patches stehen aus. Der folgende Text dokumentiert unveraendert den Auditstand.

Stand: 03.10.2026. Ausgangspunkt JSLive 0.87 (`21c6ed4`).
Ergebnis: Herkunft geklaert, statische Darstellung geprueft; zwei bestaetigte
Bestandsfehler, deshalb keine vollstaendige Integrationsfreigabe.

## Herkunft und Pflege

Das vorhandene JavaScript ist nach CRLF-/LF-Normalisierung bytegleich mit
`dist/loading-bar.js` aus dem offiziellen GitHub-Commit
`af5271ef7c675783fe870b5a60d6057f32f73e47` vom 20.10.2019. Dies war beim Audit
der letzte Default-Branch-Commit. Dort steht im Paket 0.1.1, aber dieser Stand
ist nicht identisch mit npm 0.1.1 vom 25.06.2018. npm `latest` ist weiterhin
0.1.1; die GitHub-Release-Abfrage liefert 404, die Tagliste nur 0.1.0.
Ein npm-Wechsel waere kein Upgrade des vorhandenen JSLive-Standes.

Das vorhandene CSS unterdrueckt das Upstream-Prozentzeichen und ergaenzt das
leere `:before`-Element fuer das konfigurierbare Praefix. Diese Anpassungen
bleiben bestehen. Die MIT-Lizenz stimmt mit dem npm-Paket ueberein.
Hashes, Paketintegritaet und feste Quellverweise:
[SOURCES.md](../SymconJSLive/js/loading-Bar/SOURCES.md).

Aktive Pflege ist nicht belegt. Kein neuer Fork, kein Build der historischen
Toolchain und keine Ersatzbibliothek werden in diesem Schritt eingefuehrt.
Bundle, CSS, Template, PHP-Code und Assetpfade bleiben unveraendert.

## Nachweise und Grenzen

- Die Ausgangssuite und Syntaxchecks waren vor Aenderungen gruen.
- `tests/frontend-dependencies.php` prueft die LF-normalisierten Hashes von
  JavaScript, angepasstem CSS und MIT-Lizenz sowie die Herkunftsdokumentation.
  Der neue Test scheiterte vor Ergaenzung des Herkunftsnachweises gezielt.
- `tests/webhook-routing.php` prueft HTTP 200 und exakten Inhalt beider Assets.
- `tests/progressbar-browser.js`: zehn PASS, Linie, Kreis mit umgekehrter
  Stroke-Richtung, Fill-Pfad, gestrichelter eigener Pfad und eigenes SVG,
  jeweils bei 1024/390 Pixeln Breite. Echte Bibliotheken, CSS und die originalen
  Lade-/Konfigurationsfunktionen aus der Vorlage, synthetische Konfiguration.
  SVG wird ueber einen lokal beantworteten GET geladen. Unerwartete Requests,
  Dialoge und Browserfehler schlagen fehl. Praefix/Suffix, Dezimalwerte,
  Grenzen 0/100, Geometrieaenderung und fremde Variablen-IDs werden geprueft.

Die gruene Browserbaseline verwendet ausdruecklich `bar.set(value, false)`
ohne Animation. Beim Bildladen erzwingt Upstream eine Animation; der Test
wartet deren Ende ab und setzt den Initialwert fuer die statische Baseline
anschliessend explizit. Das ist eine Testabgrenzung, kein Produktiv-Workaround
und kein Nachweis fuer korrekte Animationen oder normale Update-Abfolgen.
Die regulaere PHP-Suite prueft bereits die Konfigurationszuordnung von
SVG/Stroke/Fill; sie ist ebenfalls keine Animationsabnahme.

Lokal: Node 24.19.0, Playwright 1.63.0, Edge 155.0.4283.18.
Playwright muss bereits vorhanden sein; keine automatische Installation.

```powershell
$env:NODE_PATH = 'E:\git\chartjs-plugin-streaming\node_modules'
$env:JSLIVE_BROWSER_EXECUTABLE = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
node tests/progressbar-browser.js
```

Node 24 muss im PATH stehen. Kein Zugriff auf Symcon. Keine vollstaendige
Template-Startup-, WebSocket-/Pull-, PHP-, IPSView-, Fremdtemplate- oder
Langlaufabnahme. Browsertests sind optional und nicht Bestandteil der PHP-CI.

Abschluss: Standardlauf `php tests/run.php`, Syntaxcheck aller 45 Projekt-PHP-
Dateien (ohne vendorte Helper), PHP-CS-Fixer-Trockenlauf, JSON-Pruefung,
JavaScript-Syntaxcheck und `git diff --check` bestanden. PHP lokal 8.5.10.
Die zwei folgenden expliziten Fehlerproben sind davon ausgenommen: beide
wurden ausgefuehrt und sind fehlgeschlagen. CI der neuen Checks steht aus.

## Bestaetigte Fehler: separate Korrektur erforderlich

### 1. Animation endet nicht verlaesslich am Zielwert

In `transition.handler` berechnet Upstream die Easing-Kurve auch dann noch,
wenn `dt >= duration` gilt. Die quadratische Kurve wird ueber das Intervall
hinaus fortgesetzt; erst danach endet die Animation. Ein verspaeteter Frame
kann daher einen falschen Endwert in Label und SVG hinterlassen, waehrend
`bar.value` bereits den richtigen Zielwert enthaelt.

Reproduktion mit originalem `Update`, echtem Bundle und kontrollierter
Browseruhr: Start 25, Ziel 75, Dauer 0.1 Sekunden, nach einem Frame ein Sprung
um 500 ms. Ergebnis Label 0 statt 75, Animation beendet. Bereits ohne
Uhrsteuerung wurden falsche Endwerte beobachtet; deren Hoehe ist timingabhaengig.

```text
node tests/progressbar-browser.js --probe-animation
```

Dieser explizite Fehlernachweis endet aktuell mit Exitcode 1, weil der
geforderte Endwert nicht erscheint. Eine spaetere Korrektur muss die Animation
auf den exakten Zielwert abschliessen und verzögerte Frames mitpruefen.

### 2. Reverse-Modus kann Updates verschlucken

Die Vorlage speichert in `value` den umgerechneten Wert, vergleicht ihn aber
vor einem Update mit dem neuen Rohwert. Beispiel bei Bereich 0..100:
Rohwert 25 wird als 75 dargestellt. Beim folgenden Rohwert 75 unterbleibt
`bar.set`, obwohl die Anzeige auf 25 wechseln muesste.

```text
node tests/progressbar-rendering.js --probe-reverse
```

Dieser gezielte VM-Test fuehrt die originale Update-Funktion aus und endet
aktuell mit Exitcode 1 (Ist 75, Soll 25). Er schreibt nicht die falsche Ausgabe
als gewuenschten Vertrag fest. Rohwert und Darstellungswert muessen getrennt
behandelt werden; nicht bei null beginnende Bereiche sind mitzupruefen.

## Weiteres Vorgehen

Naechster abgegrenzter Schritt: beide Fehler korrigieren und die bisherigen
Fehlernachweise in verbindliche Regressionstests ueberfuehren. Fuer eine
Aenderung am Upstream-Code zuvor den Lieferweg entscheiden: dokumentierter
lokaler Patch mit geschuetztem Altpfad oder eigener Wartungsfork. Noch keine
solche Architekturentscheidung getroffen. Ein Bibliotheksersatz ist ebenfalls
keine automatische Konsequenz dieses Audits.

Fuer diese reinen Test-/Dokumentationsergaenzungen ist kein Symcon-Modulupdate
oder Neustart erforderlich. Die neuen optionalen Fehlernachweise sind bewusst
rot; eine gruene Standard-CI darf nicht als Behebung interpretiert werden.
