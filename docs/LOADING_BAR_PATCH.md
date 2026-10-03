# Loading Bar: lokaler Patch und Reverse-Korrektur

Stand: 03.10.2026. Ausgangspunkt JSLive 0.88 (`063ed18`, Quellcommit `8427641`).
Der Eigentuemer hat den versionierten lokalen Patch mit unveraendertem Altpfad
ausdruecklich freigegeben. Kein neuer Wartungsfork und kein npm-Update.

## Lieferumfang und Verhalten

Die Standardvorlage `Progressbar.html` laedt jetzt
`/hook/JSLive/js/loading-Bar/0.1.1-jslive.1/loading-bar.js`.
`0.1.1-jslive.1` bezeichnet ausschliesslich die lokale Assetrevision, nicht
eine neue Upstream- oder Library-Version. Grundlage ist der bereits vorhandene
GitHub-Stand `af5271e` von 2019, nicht das aeltere npm-Paket 0.1.1.

Die einzige Bibliotheksaenderung begrenzt die Interpolation auf `dt < dur`.
Ab dem Animationsende wird der Zielwert verwendet, anschliessend gelten wie
bisher die konfigurierte Genauigkeit und Wertebegrenzung. Ein verspaeteter
Frame extrapoliert nicht mehr ueber das Ende der Kurve hinaus. Der Scheduler
und die weiterhin erzwungene Initialanimation nach einem Bilddownload bleiben
unveraendert. Herkunft, MIT-Lizenz, reproduzierbarer Ein-Zeilen-Patch und Hashes:
[SOURCES.md](../SymconJSLive/js/loading-Bar/0.1.1-jslive.1/SOURCES.md).

In der Standardvorlage bleibt `value` stets der Rohwert. Die Konfiguration
liest ihn ohne Seiteneffekt, und ein Update vergleicht Rohwert mit Rohwert.
Nur fuer die Anzeige wird bei `reverse` die Spiegelung
`data_min + data_max - Rohwert` berechnet. Beispiele:

- Bereich 0..100: Rohwert 25 -> Anzeige 75, danach Rohwert 75 -> Anzeige 25.
- Bereich 20..120: Rohwert 25 -> Anzeige 115, danach Rohwert 75 -> Anzeige 65.
- Bereich -100..0: Rohwert -75 -> Anzeige -25, danach Rohwert -25 -> Anzeige -75.

Die Beruecksichtigung der Untergrenze ist eine sichtbare Fehlerkorrektur fuer
Reverse-Bereiche, die nicht bei null beginnen. Unveraenderte Rohwerte und
Updates fremder Variablen-IDs loesen weiterhin keine Aktualisierung aus.
Die generierte Konfiguration kann wiederholt ohne erneute Umkehrung gelesen
werden. Keine Aenderung an PHP, Properties, IDs, JSON oder gespeicherten Werten.

## Kompatibilitaet und Rueckfall

Der alte JavaScript-Pfad und `loading-Bar/loading-bar.css` bleiben bytegleich.
Eigene `TemplateScriptID`-Vorlagen werden nicht automatisch umgestellt; sie
behalten damit gegebenenfalls auch die alten Fehler. Fuer deren Migration
sowohl den neuen Bundle als auch die Rohwert-/Reverse-Logik der Standardvorlage
pruefen. Nie beide ldBar-Bundles in derselben Seite laden.

Nach Commit/Push gruene CI abwarten, Metadaten pullen, Symcon-Modulupdate
durchfuehren und Ansichten neu laden. `ApplyChanges()` laeuft beim Modulupdate
automatisch. Kein gesonderter Aufruf oder Dienstneustart erforderlich.
Rueckfall: vollstaendigen Stand `063ed18` (0.88) wiederherstellen, Modulupdate
und Neuladen; damit kehren bewusst auch beide alten Fehler zurueck.
Weitere Bibliothekspatches erhalten eine neue Assetrevision; veroeffentlichte
Revisionen werden nicht still ueberschrieben.

## Nachweise

Vor Aenderungen bestanden Standardtests und PHP-Syntaxpruefung. Beide alten
Fehlerproben wurden erneut ausgefuehrt und scheiterten erwartungsgemaess;
auch die erweiterten Regressionen waren vor der Korrektur rot.

- `tests/progressbar-rendering.js`: sechs Kombinationen aus Reverse/Normal,
  positiven/negativen/versetzten Wertebereichen und Dezimalwerten; Initialwert,
  wiederholte Konfiguration, beide Wechselrichtungen, Duplikate und fremde IDs.
  Jetzt immer Teil der Standard-CI, nicht mehr hinter `--probe-reverse` verborgen.
- `tests/loading-bar-animation.js`: echter Handler aus dem aktiven Bundle mit
  deterministischen Zeitwerten; steigende/fallende Animationen, exaktes/spaetes
  Ende, Zwischenwert, Praezision, sofortige Ausgabe und Bereichsbegrenzung.
  Ueber `tests/run.php` Teil der Standard-CI; DOM-Ausgabe ist hier ein Testdouble.
- `tests/frontend-dependencies.php`: Original-/Patch-/Lizenzhashes, exakter
  Ein-Zeilen-Unterschied, neuer Templatepfad und unveraendertes CSS.
- `tests/webhook-routing.php`: alter und neuer JavaScript-Pfad sowie CSS mit
  HTTP 200 und exaktem Dateiinhalt ueber den PHP-Webhook-Harness.
- `tests/progressbar-browser.js`: 32 PASS mit echten Bibliotheken und originaler
  Lade-/Update-Logik. Acht Szenarien (Linie, Kreis, Fill, Dash-Pfad, SVG und
  drei Reverse-Bereiche), zwei Fensterbreiten, normale/verzoegerte Frames.
  Animierte Endgeometrie stimmt jeweils mit direkter Zielausgabe ueberein.
  Die erzwungene SVG-Initialanimation wird nicht mehr durch einen manuell
  gesetzten Testwert kaschiert. `--probe-animation` bleibt als gruener
  gezielter Kurzlauf verfuegbar.

Browserumgebung: Node 24.19.0, Playwright 1.63.0, Edge 155.0.4283.18.
Aufruf/Voraussetzungen wie im [Audit](LOADING_BAR_AUDIT.md). Browsermatrix
optional, keine Paketinstallation und kein Symcon-Zugriff.

Abschliessend bestanden: `php tests/run.php` einschliesslich beider neuen
Standardregressionen, Syntaxchecks aller 45 Projekt-PHP-Dateien (ohne vendorte
Helper), PHP-CS-Fixer-Trockenlauf, JSON-Pruefung, JavaScript-Syntaxchecks und
`git diff --check`. PHP lokal 8.5.10. Die 32 Browserfaelle wurden nach Integration
erneut erfolgreich ausgefuehrt, ebenso der gezielte Animations-Kurzlauf.

Nachtrag 03.10.2026: Push, gruene CI und Symcon-Update wurden vom Eigentuemer
bestaetigt; lokal liegt 0.89 (`7a419a9` / `1729c19`) vor. Keine zusaetzliche
unabhaengige MCP-Abnahme durch diesen Nachtrag. Kein
vollstaendiger Template-Startup-, Transport-, Langlauf- oder IPSView-Nachweis.
Die bekannte fehlende IPSView-Testlizenz bleibt eine Testluecke. Die lokale
Korrektur begruendet keine aktive Pflegezusage fuer die alte Upstream-Bibliothek.
