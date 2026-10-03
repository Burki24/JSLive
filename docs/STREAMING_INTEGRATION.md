# Integration des Streaming-Wartungsforks 3.6.0

Stand: 03.10.2026. Die folgenden Abschnitte beschreiben den Integrationsstand
0.84. WebSocket-/Minuten-Pull-Nachweise wurden auf 0.95, historische
Zeitraumwechsel auf 0.96 ergaenzt. Der lokale Schutz gegen ueberholte
Chart-Antworten ist noch nicht installiert; IPSView bleibt Anwenderabnahme.
Aktuelle Freigabegrenzen: [Laufzeitmatrix](SYCON_RUNTIME_MATRIX.md#fortschreibung-nach-abnahme-von-096-03102026).
Ausgangspunkt: JSLive 0.83, `ff22ac2`; Entscheidung: [ADR 0005](adr/0005-maintained-streaming-fork.md).

## Lieferumfang und Herkunft

Nur `SymconJSLive/templates/Chart.html` wechselt seinen Streaming-Skriptpfad
auf `chartjs/plugins/streaming/3.6.0/chartjs-plugin-streaming.min.js`.
Der alte 3.1.0-Bundle bleibt unveraendert fuer eigene Templates und historische
Prototypvergleiche. Alle anderen Bibliotheken, PHP-Funktionen, Datenformate und
Modulkonfigurationen bleiben gleich; insbesondere kein Wechsel zu Luxon.

Der neue Bundle und die MIT-Lizenz sind bytegleich aus Fork-Commit
`054f9fd535b9002aa5f9d7ebcba0267d931e4a11` uebernommen. Quellstand:
`ab87b7700ef888e2a803f85b93e8adcd06c0dc8f`. Lokaler und entfernter `dev`
stimmten bei der Uebernahme ueberein;
[Fork-CI](https://github.com/Burki24/chartjs-plugin-streaming/actions/runs/37031433191)
war erfolgreich. Dies ist ein gepinnter Entwicklungsstand, kein Tag-/npm-Release.
Dateihashes und Herkunft stehen in
[`SOURCES.md`](../SymconJSLive/js/chartjs/plugins/streaming/3.6.0/SOURCES.md).

Die Lebenszyklus-, Quiet-Update-, Hover- und Tooltipkorrekturen werden im Fork
gepflegt. Dessen `docs/JSLIVE_COMPATIBILITY.md` dokumentiert Matrix und begrenzten
Langlauf. Die frueheren 120-Sekunden-Vergleiche zu 3.4.0 werden hier nicht als
erneute Leistungsmessung von 3.6.0 ausgegeben.

## Reproduzierbare lokale Pruefung

- `php tests/run.php`: gesamte Suite bestanden, einschliesslich Integritaet,
  einmaligem neuen Templateverweis, Ladereihenfolge, Altpfad und Auslieferung
  beider Streaming-Dateien ueber den PHP-Webhook-Harness. Der neue Inventartest
  schlug vor der Uebernahme erwartungsgemaess wegen des fehlenden 3.6.0-Stands fehl.
- PHP-Syntax (45 Projektdateien, PHP 8.5.10), PHP-CS-Fixer im Pruefmodus,
  JSON-Style, JavaScript-Syntax und `git diff --check`: bestanden. Der Fixer
  meldet nur den Hinweis auf die hier nicht vorhandene `composer.json`;
  null verbleibende Formatierungsbefunde.
- `tests/chart-streaming-browser.js`: acht erfolgreiche Browserdurchlaeufe
  (je 3.1.0/3.6.0, UTC/Europe/Berlin, 1024/390 Pixel Breite), Edge 155.0.4283.18,
  Node 24.19.0, Playwright 1.63.0. Hoehe jeweils 720 Pixel.

Der optionale Browsercheck liest die Assetliste und Funktionen direkt aus der
Standardvorlage. Echte Chart.js-, Moment-, Adapter-, Datalabels- und
Streaming-Bundles werden ausgefuehrt. Nur RPC-Antworten, Abmessungen und
Hintergrund-/Transportloops sind durch feste lokale Fixtures ersetzt; saemtliche
Netzwerkzugriffe sind gesperrt. Der Check installiert keine Pakete.

```powershell
# Node 24 und Playwright muessen vorhanden sein; NODE_PATH bei externer Installation setzen.
$env:NODE_PATH = 'E:\git\chartjs-plugin-streaming\node_modules'
$env:JSLIVE_BROWSER_EXECUTABLE = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
node tests/chart-streaming-browser.js
```

Nachgewiesen sind Mischdiagramme aus Linie/Balken, sichtbare Datenlabels,
Livewerte ueber `UpdateChart` (42.259 wird wie bisher 42.25), laufende Achse,
Moment-Tooltipdatum mit Zeitzone, JSLive-Tooltiptext mit Einheit, Pause/Resume,
Datenbereinigung sowie die korrigierte Tooltipauswahl nach Bereinigung im
neuen Fork. `ReloadChart` wechselt im synchronen Datenladezweig von Realtime
auf historische Zeitachse und zurueck auf relative Stunden-/Minutenansicht.
Dabei wird die alte Instanz zerstoert und genau eine neue erzeugt. Nach
endgueltigem Abbau bleibt keine Chart-Instanz und kein weiterer Draw-Aufruf.
Keine Browserfehler oder Dialoge im geprueften Ablauf.

Die initialen Canvas-PNGs sind pro Zeitzone/Breite bei beiden Versionen exakt
gleich (SHA-256 ueber die PNG-Data-URL):

| Zeitzone | Breite | Gemeinsamer SHA-256 |
| --- | --- | --- |
| UTC | 1024 | `2a91d10a7d7b42bab5c41616fbc8e758ec2db651b79ad5d10799b1d41763759a` |
| UTC | 390 | `9f201cc1ee9cad8144afb67eea21b304872ffd1569ec10160834fd8fb7b5b6c6` |
| Europe/Berlin | 1024 | `c48990df14a0fd3189426fa31a7fe2c15e0cf08c1da8635c77e431c02cdafd82` |
| Europe/Berlin | 390 | `27503ddc38e937b56f6b428f466ea981abd0bc04f8d1a73c48f58224c7196470` |

Dies ist kein Vollseiten-, echter Touch-/IPSView-, WebSocket-/Pull- oder
installierter Symcon-Test. Fortschreibung: Die Browsermatrix prueft inzwischen
auch den asynchronen historischen Datenladezweig, einschliesslich zuletzt
eintreffender ueberholter Datensatzantworten. 37 Node-Szenarien ergaenzen die
Pruefung aller HTTP-Stufen und Antwortreihenfolgen.
Die Bildgleichheit betrifft den definierten Ausgangszustand, nicht alle
Konfigurationen oder spaetere Zustaende mit absichtlich korrigierten Tooltips.

## Noch offene Abnahme und Rueckfall

Fortschreibung 03.10.2026: JSLive 0.84 (`5fe45ad` / `8bc5e02`) war lokal,
auf GitHub und auf MCP-CURRENT synchron; Tests, Style und CodeQL gruen.
Neuer Asset-Hash stimmt exakt. Beim alten Bundle erklaert ausschliesslich
CRLF statt LF den abweichenden Rohhash. Alle elf Instanzen aktiv, keine
JSLive-Logfehler im geprueften Zeitraum. Installierte Chart-Ansicht: Realtime,
Tooltipwerte, lesender Pull-Aufruf und Reload bestanden; WebSocket dreimal
HTTP 101, aber kein eingehender Datenwechsel beobachtet. Zeitachsenwechsel
nur browserlokal. Die einzige HTTP-Fehlermeldung betraf `/favicon.ico` (404),
nicht JSLive. Keine Konfigurationsaenderungen oder ApplyChanges-Aufrufe durch
den Pruefer. Echte IPSView-Abnahme auf der Testebene ist aus Lizenzgruenden
nicht verfuegbar; Entwicklung wird auf Wunsch des Eigentuemers fortgesetzt.
Die folgende urspruengliche Checkliste bleibt als Ablauf erhalten; Punkte 1/2
sind fuer 0.84 erledigt, Punkt 3 ist nur im oben genannten Umfang nachgewiesen.

1. Eigentuemer: Commit/Push in JSLive, CI abwarten und Bot-Metadaten lokal pullen.
2. Modulupdate in Symcon; `ApplyChanges()` laeuft dabei automatisch. Ansichten
   neu laden. Kein gesonderter Aufruf oder Dienstneustart fuer diesen Assetwechsel.
3. Auf MCP-CURRENT neue Template-URL und ausgelieferte Version/Hashes pruefen;
   Chart mit echten Daten, WebSocket, Pull, Reload, relativen/historischen
   Perioden sowie eingesetzten IPSView-Geraeten abnehmen. Konsole/Logs pruefen.

Kein Symcon-Schreibzugriff wurde fuer die lokale Integration ausgefuehrt.
Die installierte Gesamtabnahme bleibt wegen der oben genannten Luecken offen;
fruehere Ergebnisse gelten nicht automatisch fuer spaetere Aenderungen.
[Migrations- und Rueckfallanleitung](FRONTEND_ASSET_MIGRATION.md): vorherigen
JSLive-Stand `ff22ac2` (0.83) vollstaendig wiederherstellen, Modulupdate und
Neuladen; eigene Templates bei Bedarf aus separater Sicherung zuruecksetzen.
