# Eigene Echtzeitsteuerung: Prototyp und Profiling

Stand: 02.10.2026, Ausgangsbasis 0.78 (`cf42e48`). Entscheidung und
Freigabekriterien: [ADR 0004](adr/0004-own-realtime-controller.md).

## Dateien und Wiederholung

- `tests/prototypes/realtime-window.js`: experimenteller Controller; wird von
  keiner Standardvorlage geladen und nicht unter den JSLive-JS-Assets abgelegt.
- `tests/realtime-window.js`: deterministische Tests mit gesteuerter Uhr und
  Scheduler; Bestandteil von `php tests/run.php`, ohne weitere Abhaengigkeiten.
- `tests/realtime-window-browser.js`: optionaler Vergleich mit den echten,
  bereits versionierten Bibliotheken. Er erwartet vorhandenes Playwright und
  einen Chromium-Browser; er installiert nichts und ist noch kein CI-Schritt.
- `tests/realtime-window-maintenance.js`: isolierter Bereinigungsbenchmark
  ohne Browser; kein Ersatz fuer den End-to-End-Lastvergleich.

```text
node tests/realtime-window.js
node tests/realtime-window-browser.js
node tests/realtime-window-browser.js --profile
node tests/realtime-window-maintenance.js
```

Der zweite Aufruf benoetigt Playwright im Node-Suchpfad. Optional setzt
`JSLIVE_BROWSER_EXECUTABLE` den Pfad eines bereits vorhandenen Browsers. Das
Skript kann alternativ als Funktion mit einer vorhandenen Playwright-
Browserinstanz aufgerufen werden. Es schliesst seine isolierten Kontexte;
der CLI-Einstieg schliesst auch den Browser. Keine Symcon-Verbindung, keine
Produktivdaten, keine externen Netzwerkabrufe in den Testseiten.

Optional bezeichnet `JSLIVE_BASELINE_CONTROLLER` eine lokal bereitgestellte,
vertrauenswuerdige Kopie von `tests/prototypes/realtime-window.js` aus Commit
`c8f5481`. Beide Benchmarks fuehren dann zusaetzlich diese Baseline aus; der
Browsertest vergleicht auch die Canvas-Pixel. Es wird kein Git-Checkout
umgeschaltet. Programmgesteuert akzeptieren `run(..., {baselineSource})`,
`measure`, `profile` und `compareRendering` denselben Quelltext. Der Profiling-
Lauf ist vom Lastbenchmark getrennt, damit das Sampling dessen Zeiten nicht
verfaelscht. Die Messwerte werden ausgegeben, nicht automatisch eingecheckt.

## Ergebnis

Der Prototyp existierte beim ersten Test noch nicht; die neue Pruefung war
erwartet rot. Nach Implementierung bestehen die Tests fuer beide Zeitfenster,
Burst-Buendelung, Zeichentakt, Randpunkte und Punktidentitaet, Bereinigung im
Hintergrund, eingefrorenes und aktuelles Fenster bei Pause, Wiederanlauf,
eindeutige Chart-Zustaendigkeit, Abbau, Renderfehler, Indexanpassung aktiver
Punkte sowie explizite Ablehnung nicht unterstuetzter Daten/Optionsarrays.
Eine Vertragspruefung sichert die unveraenderte produktive Plugin-Einbindung.
Gesamtsuite, PHP-Syntax aller 57 Dateien und JavaScript-Syntax der drei neuen
Dateien bestanden. PHP-CS-Fixer im Pruefmodus fuer `tests/run.php` meldet keine
Aenderungen (nur Hinweis auf die im Projekt nicht vorhandene `composer.json`).

Isolierter Edge 155.0.4283.18, 1024 x 768, Europe/Berlin, Chart.js 4.5.1,
Moment 2.31.0, Adapter 1.0.1, Datalabels 2.2.0; Vergleich mit dem vorhandenen
qultoltd-Streaming-Bundle 3.1.0. Die Varianten laufen in getrennten Kontexten.
Die eigene Variante laedt das Streaming-Plugin nicht.

| Pruefung | Plugin | Prototyp |
| --- | --- | --- |
| Linie/Balken, neue Werte, fortschreitende Zeitachse | PASS | PASS |
| Labels tatsaechlich gezeichnet, Tooltip mit Datenpunkt | PASS | PASS |
| Pause/Resume und Randpunkte bei Bereinigung | PASS | PASS |
| Auswahl und Tooltip nach Indexverschiebung | Nicht separat bewertet | PASS |
| Nach Abbau keine weitere Zeichnung/Chart-Instanz | PASS | PASS |
| Browserfehler/Dialoge | Keine | Keine |

Das Prototyp-Canvas wurde visuell kontrolliert. Dies ist kein pixelgleicher
Alt-/Neu-Nachweis und kein Ersatz fuer einen Test der vollstaendigen Vorlage.

## Erste Lastmessung: Umstellung noch nicht freigegeben

Zwei unabhaengige Durchlaeufe je Last/Variante, jeweils rund zwei Sekunden
Messfenster, vier Datensaetze abwechselnd Linie/Balken, ein neuer Punkt pro
Sekunde/Datensatz, Minutenfenster, 30 angeforderte Bilder/s. In beiden Varianten
Animation und Datalabels fuer diesen Lasttest abgeschaltet. Vorbereitungszeit
ausserhalb des Messfensters. Messgroesse ist CDP `TaskDuration` relativ zur
verstrichenen Zeit, also Browser-Hauptthreadzeit, nicht die CPU-Auslastung des
gesamten Rechners und auch keine garantierte erreichte Bildrate.

| Ausgangslast | Plugin | Prototyp |
| --- | --- | --- |
| 4 x 1.000 Punkte | 29,0-29,9 % | 55,5-60,6 % |
| 4 x 5.000 Punkte | 66,5-81,2 % | 99,5-99,6 % |

Diese kurzen lokalen Messungen sind ein Warnsignal, keine allgemeine
Leistungszusage. Der Ansatz mit vollstaendigem `update('none')` pro Zeichentakt
verbraucht erheblich mehr Rechenzeit. Vor der Template-Integration muessen
Profiling, Optimierung und Wiederholungsmessungen erfolgen. Das bestehende
Plugin bleibt produktiv; weder geringere Bildrate noch Datenreduktion wurden
als Ausweg beschlossen.

## Profiling und Optimierung vom 02.10.2026

Ausgangscontroller: `c8f5481`. Einzelmessungen, Browser-/Node-Version,
Hash des gemessenen Controllers, Datenbestaende, Bildabstaende und Sampling-Ergebnis stehen in
[realtime-profiling-2026-10-02.json](realtime-profiling-2026-10-02.json).
Nach der Lastmessung wurde lediglich die Bereichsgrenze fuer veraltete
Auswahlindizes nach externem Datenersatz ergaenzt; Regression und
Browser-/Pixelvergleich wurden auch mit dieser finalen Fassung wiederholt.

Das CPU-Sampling bei 4 x 5.000 Punkten zeigt weiterhin den vollstaendigen
Chart.js-Balkenupdate als Hauptengpass: im optimierten Lauf rund 366 ms eigene
Samplezeit in `_calculateBarIndexPixels`, 256 ms in
`_calculateBarValuePixels`, 206 ms in `_getAxis` und 261 ms Garbage Collection
bei 3.115 ms Samplingdauer. Diese internen Namen dienen nur zur Diagnose;
der Controller greift nicht auf diese Methoden zu.

Umgesetzt wurden zwei begrenzte, getrennt pruefbare Verbesserungen:

- Bereinigungsgrenzen per binaerer Suche und Indexumrechnung ueber maximal
  zwei entfernte Bereiche statt einer Map pro erhaltenem Punkt. Nur bei
  tatsaechlicher Entfernung wird ein neues Datenarray erzeugt. Die vollstaendige
  Eingabevalidierung, Punktidentitaeten und Randpunktregeln bleiben erhalten.
- Monotone Browserzeit fuer den Zeichentakt, Systemzeit weiterhin fuer die
  Zeitachse. Nicht verbrauchte Bruchteile eines Takts werden weitergetragen;
  lange Unterbrechungen erzeugen keine nachtraeglichen Zeichenbursts.

Ein direkter `splice`-Versuch wurde verworfen: Im echten Chart verschoben sich
markierte Elementobjekte beim Bereinigen. Der bewaehrte Arrayersatz bleibt
daher erhalten. Auch `parsing: false` wurde nur experimentell betrachtet:
bei 4 x 1.000 Punkten etwa 53-54 statt 56 % Hauptthreadzeit, aber kein Loesen
des Hauptengpasses. Ein automatisches Abschalten des Parsers ohne abgesicherten
Werte-/Achsenvertrag wurde nicht uebernommen. Die offiziellen
[Performancehinweise](https://www.chartjs.org/docs/latest/general/performance.html)
verlangen dafuer bereits korrekt vorbereitete Daten. Tickdichte, Labels,
Datenmenge und angeforderte Bildrate werden nicht reduziert.

### Isolierte Bereinigung

20.000 identische Punkte, 20 Aufwaermaufrufe, danach je 200 Aufrufe in drei
Durchlaeufen mit wechselnder Reihenfolge, gleiche Node-Laufzeit und JS-Realm.
Tabellenwerte sind Mediane der drei mittleren Aufrufzeiten. Jeder Fall prueft
alle erhaltenen Punktreferenzen gegen eine unabhaengige Filterreferenz.

| Fall | Vorher, ms/Aufruf | Nachher, ms/Aufruf | Erhaltene Punkte |
| --- | --- | --- | --- |
| Kein Punkt abgelaufen | 1,838 | 0,099 | 20.000 |
| Abgelaufener Anfang | 0,685 | 0,118 | 6.668 |
| Pause mit Luecke zum Livefenster | 1,052 | 0,155 | 13.337 |

Das entspricht hier etwa 5,8-18,6-fach schnellerer **Bereinigung**, nicht einer
entsprechenden Beschleunigung des gesamten Diagramms.

### Wiederholter Browservergleich

Je Last zwei Durchlaeufe, Variantenreihenfolge im zweiten Durchlauf umgekehrt.
Eine Sekunde Aufwaermphase, danach rund drei Sekunden Messfenster. Identische
Ausgangsdaten und Darstellungseinstellungen wie oben; jetzt exakt drei neue
Punkte pro Datensatz bei allen Varianten. Vorbereitungs- und Bereinigungstakte
fuehren zu leicht unterschiedlichen verbleibenden Punktzahlen; diese werden
mitprotokolliert. Keine Paralleltests waehrend der Messungen.

| Last / Variante | Hauptthreadzeit | Zeichnungen/s | Hauptthread-ms/Zeichnung |
| --- | --- | --- | --- |
| 4 x 1.000 / Plugin | 28,8-29,9 % | 32,2 | 8,9-9,3 |
| 4 x 1.000 / bisheriger Prototyp | 54,3-56,1 % | 27,5 | 19,7-20,4 |
| 4 x 1.000 / optimierter Prototyp | 60,0-60,3 % | 30,0-30,1 | 19,9-20,1 |
| 4 x 5.000 / Plugin | 66,2-72,8 % | 20,9-25,7 | 25,7-34,7 |
| 4 x 5.000 / bisheriger Prototyp | 98,5-99,5 % | 18,9-19,0 | 52,0-52,7 |
| 4 x 5.000 / optimierter Prototyp | 98,2-99,5 % | 18,6-19,2 | 51,9-52,8 |

Die Zeichnungen werden am oeffentlichen `afterDraw`-Hook gezaehlt, einschliesslich
zusaetzlicher Daten-/Wartungsupdates des Plugins. Das ist keine Messung realer
Bildschirmausgaben; 32,2 Zeichnungen/s bedeuten nicht 32,2 verschiedene sichtbare
Monitorbilder. Hauptthreadzeit stammt aus CDP `TaskDuration`; kleine
Abweichungen der Messgrenzen durch Browser-/CDP-Aufrufe bleiben enthalten.

**Bewertung: Performancegate weiterhin nicht bestanden.** Bei kleinerer Last
liefert der neue Scheduler die angeforderten 30 statt rund 27,5 Zeichnungen/s,
benoetigt entsprechend mehr gesamte Hauptthreadzeit. Pro Zeichnung ist keine
belastbare Gesamtbeschleunigung nachgewiesen. Bei hoher Last dominiert weiterhin
das Chart-Update; die Bereinigungsoptimierung allein loest dies nicht.

### Regression und naechste Entscheidung

Der neue Zeichentakttest scheitert am alten Controller reproduzierbar mit
20 statt mindestens 29 Updates bei 60 abwechselnd 16/17 ms langen Takten.
Die neue Fassung besteht ihn einschliesslich Uhrsprung und langer Unterbrechung.
60 zusaetzliche Referenzfaelle decken leere Arrays, doppelte Zeitstempel,
ueberlappende/getrennte Pausefenster und saemtliche Auswahlindizes ab.

Die realen Plugin-/Prototyp-Browsertests bestehen weiterhin. Fuenf fixierte
Zustaende sind zwischen altem und neuem Prototyp pixelgleich: Start, neue Daten,
Bereinigung, lange Pause und Resume, mit aktivierten Labels/Tooltip. Pixelgleichheit
belegt keine Fehlerfreiheit der Baseline: Bei programmatischer Auswahl direkt
vor Bereinigung zeigte die Diagnose in beiden Staenden einen veralteten
formatierten Tooltipwert trotz korrektem `raw`-Punkt. Dieser bestehende Fall
wird im unten beschriebenen Folgeschritt korrigiert und abgesichert.

Finale lokale Pruefung: `php tests/run.php` vollstaendig bestanden (PHP 8.5.10),
Syntax aller 57 PHP-Dateien und aller vier betroffenen JavaScript-Dateien sowie
`git diff --check` bestanden. Browsernachweis unter Edge 155.0.4283.18 ebenfalls
bestanden. CI nach Commit/Push bleibt eine separate, noch offene Zusatzpruefung.

Naechster Schritt ist die Entscheidung ueber den verbleibenden Renderengpass
innerhalb der oeffentlichen Chart.js-APIs; keine Template-Umstellung allein
aufgrund dieser lokalen Optimierung. Vollstaendige Daten-/Darstellungsfaelle,
Langlauf und installierte Abnahme bleiben anschliessende Gates.

## Folgeschritt: Tooltip-Konsistenz und Style-Korrektur

Ausgangsstand ist `c34e9c4` (Library 0.80), Implementierungscommit `4896125`.
Dessen Tests und CodeQL waren in der CI gruen; der Style-Check scheiterte
ausschliesslich an der Einrueckung mit zwei statt vier Leerzeichen in der
Profiling-JSON. Diese Datei ist jetzt mit dem vorhandenen StylePHP-Formatter
formatiert; alle dekodierten Messwerte sind gegen den Commitstand unveraendert.

Die naechste begrenzte Implementierung schliesst zuerst den bestaetigten
Tooltipfehler. Nach dem Ersetzen eines bereinigten Datenarrays waren die
aktiven Indizes zwar korrekt verschoben, Chart.js hatte seine eingelesenen
Werte aber noch nicht aktualisiert. Ein neuer Browser-Regressionsfall zeigte
`raw.y = 4`, jedoch `parsed.y = 3` und entsprechend einen falschen Anzeigewert.

Ein chart-lokaler `afterUpdate`-Hook erneuert jetzt bei ausstehender Bereinigung
den aktuellen Tooltip ueber dessen oeffentliche Aktivierungsfunktionen, nachdem
Chart.js die Daten neu eingelesen hat und bevor gezeichnet wird. Keine privaten
Felder oder ueberschriebenen Chart-Methoden; keine neue Bibliotheksabhaengigkeit.
Der Hook erzeugt weder einen weiteren Update-Aufruf noch eine zweite Zeichnung.
Er wird beim Abbau und nach Fehlern zusammen mit Timern und Listenern entfernt.
Normale Frames ohne vorausgegangene Bereinigung erneuern den Tooltipcache nicht.
Schnittstellen: [Plugin-Lebenszyklus](https://www.chartjs.org/docs/latest/api/interfaces/Plugin.html#afterupdate)
und [oeffentliche Chart-API](https://www.chartjs.org/docs/latest/developers/api.html).

Frische Nachweise unter Edge 155.0.4283.18 mit den bereits versionierten Assets:

- Vor der Korrektur scheitert der neue Test reproduzierbar mit `3 !== 4`.
- Danach stimmen Rohwert, eingelesener Wert, formatierter Wert und tatsaechlicher
  Tooltiptext fuer Linie und Balken ueberein.
- Beibehaltene, zwischenzeitlich geaenderte, geloeschte und abgelaufene Auswahl,
  deaktivierter/wieder aktivierter Tooltip sowie explizites Chart-Update waehrend Pause bestanden.
- Kein Rendern durch Bereinigung; genau ein Update und eine Zeichnung bei der
  anschliessenden Darstellung. Fremde lokale Plugins bleiben bei Abbau/Neustart
  erhalten; wiederholtes Starten registriert den eigenen Hook nur einmal.
- Deterministische Tests decken mehrfache Bereinigung ohne Zwischenzeichnung,
  Hintergrund/Vordergrund, ausbleibende Wiederholung des Cache-Refreshs und
  vollstaendigen Ressourcenabbau nach einem Tooltipfehler ab.
- Fuenf Canvas-Zustaende stimmen mit einer **korrigierten Referenz** ueberein:
  Im alten Controller wird nur der bekannte Tooltipfehler im Test explizit
  nachgezogen. Dies ist absichtlich kein Pixelgleichheitsnachweis mit dessen
  falschem Tooltiptext. Die historische Profiling-Datei bleibt unveraendert
  bis auf ihre Formatierung.

Dieser Schritt ist eine Korrektheitsverbesserung, keine neue Hochlastoptimierung.
Die bisherigen Lastmessungen bleiben historischer Befund; eine schnellere
Gesamtdarstellung wird nicht behauptet. Das Render-Performancegate bleibt offen.
Produktive Vorlagen, Plugin-Dateien und Library-Metadaten bleiben unveraendert.
Keine installierte Symcon-Abnahme fuer den isolierten Prototyp; CI folgt nach Push.
Lokal bestanden: `php tests/run.php`, PHP-Syntax aller 57 Dateien, Syntax der
drei geaenderten JavaScript-Dateien, `php .style/json-check.php`, PHP-CS-Fixer
im Dry-Run (0 von 45 Dateien beanstandet) und `git diff --check`. Der vorhandene
Hinweis auf die fehlende `composer.json` ist keine fehlgeschlagene Pruefung.

## Hochlast-Fortsetzung: oeffentliche Renderoptionen

Ausgangsstand: `984a3ce`, Library 0.81. Die vorherige Tooltip-/Style-Korrektur
ist mit Tests, Style und CodeQL in der CI bestanden. Der Controller bleibt in
diesem Untersuchungsschritt unveraendert; hinzu kommt ein reproduzierbarer
Optionsvergleich im vorhandenen Browser-Harness:

```text
node tests/realtime-window-browser.js --render-options
```

Wie bisher sind vorhandenes Playwright und ein Chromium-Browser erforderlich;
`JSLIVE_BROWSER_EXECUTABLE` waehlt dessen Pfad. Der Aufruf dauert lokal rund drei
Minuten. Er installiert nichts, sperrt Netzwerkzugriffe der Testseiten und
schreibt Ergebnisse nur nach stdout. Programmgesteuert stehen
`compareRenderOptions(browser)` und `renderOptionsTrial(browser, points, trial)`
zur Verfuegung. Die Einzelmessungen liegen in
[realtime-render-options-2026-10-02.json](realtime-render-options-2026-10-02.json).

Untersucht werden der vorhandene Streaming-Fork und vier Varianten desselben
Controllers: unveraendert, `parsing: false`, vollstaendig deaktiviertes
Datalabels-Plugin bei **ohnehin nicht angezeigten** Labels sowie die Kombination.
Nur die synthetische Fixture garantiert hier sortierte numerische x/y-Daten
auf Zeit-/Linearachsen. `normalized: true` wird nicht gesetzt; dessen zusaetzliche
Anforderungen an einheitliche und eindeutige Indizes sollen nicht stillschweigend
zum Datenvertrag werden. Quellen: [Chart.js Performance](https://www.chartjs.org/docs/latest/general/performance.html)
und [Plugin-Konfiguration](https://www.chartjs.org/docs/latest/developers/plugins.html).

Je 4 x 1.000 und 4 x 5.000 Ausgangspunkte, drei Durchlaeufe mit rotierter
Variantenreihenfolge, zwei Sekunden Aufwaermen und rund drei Sekunden Messzeit.
Exakt drei neue Punkte pro Datensatz in jedem Messfenster. Alle Varianten
fordern 30 Zeichnungen/s an; keine Animation und keine sichtbaren Datalabels
im Lastvergleich. Der separate Darstellungstest prueft zusaetzlich sichtbare
Labels beim Parservergleich. Keine parallelen Lasttests und kein CPU-Sampling
waehrend der Zeitmessung. Zahlen anderer Sitzungen sind nicht als direkte
Vorher-/Nachher-Beschleunigung zu verstehen.

### Ergebnis bei 4 x 5.000 Ausgangspunkten

| Variante | Hauptthreadzeit | Zeichnungen/s | Hauptthread-ms/Zeichnung |
| --- | --- | --- | --- |
| Streaming-Plugin | 61,4-66,5 % | 24,2-26,8 | 23,8-27,0 |
| Eigener Controller unveraendert | 99,0-99,3 % | 19,5-20,1 | 49,3-50,9 |
| Nur vorbereitete Daten | 98,4-98,6 % | 20,7-21,3 | 46,3-47,6 |
| Inaktives Label-Plugin aus | 98,9-99,8 % | 21,4-22,6 | 44,2-46,2 |
| Kombination | 99,0-99,6 % | 22,8-23,2 | 42,9-43,5 |

Bei 4 x 1.000 Punkten erreichen die eigenen Varianten 29,8-30,2 Zeichnungen/s.
Die Kombination braucht 32,6-34,0 % Hauptthreadzeit gegenueber 36,8-37,6 % fuer
den unveraenderten Controller; das Plugin liegt bei 18,8-23,3 %.

Hauptthreadzeit ist weiter CDP `TaskDuration`, Zeichnungen sind Aufrufe des
oeffentlichen `afterDraw`-Hooks einschliesslich zusaetzlicher Plugin-Updates,
nicht garantierte Monitorbilder. Datenbestaende und Bildabstaende sind ebenfalls
protokolliert; durch Aufwaerm-/Bereinigungstakte bleiben leicht unterschiedliche
Punktzahlen uebrig. Die Messung ist kurz und lokal, kein Langzeitnachweis.

Die zehn Darstellungskombinationen bei 20, 1.000 und 5.000 Punkten bestanden:
pixelgleiche Canvas-Ausgabe, identische Daten, Achsengrenzen und Tooltiptexte.
Sichtbare Labels bleiben beim Parservergleich erhalten; der Versuch, sie ueber
die Performanceoption abzuschalten, wird bereits vor dem Oeffnen einer Testseite
abgewiesen. Dieser Nachweis gilt fuer die Fixture, nicht fuer alle Modulkonfigurationen.

### Entscheidung aus diesen Messungen

**Keine der getesteten Optionen besteht das Hochlast-Performancegate.** Die
Kombination reduziert die mediane Rechenzeit pro Zeichnung von 49,4 auf 43,0 ms,
loest aber weder die Hauptthreadsaettigung noch den Abstand zum Plugin. Deshalb
wird kein automatischer Parser-/Label-Schnellpfad in den Controller eingebaut.
Das wuerde neue Vertraege fuer Kategorien, Bool, Nullwerte, Parsing-Mappings und
datensatzabhaengige oder dynamische Labeloptionen erzeugen, ohne das Ziel zu erreichen.
Es wird nicht behauptet, dass damit jede denkbare oeffentliche Optimierung ausgeschlossen sei.

**Vorschlag fuer den naechsten Schritt, noch nicht beschlossen:** einen
isolierten Render-Cache untersuchen, der die Geometrie unveraenderter Datensaetze
zwischen Datenereignissen wiederverwendet und die Zeitverschiebung getrennt
zeichnet. Daten-, Konfigurations-, Achsen- und Groessenaenderungen sowie
Interaktionen brauchen einen korrekt abgesicherten Vollupdate-/Rueckfallpfad.
Labels, Tooltips, Clipping und mehrere Achsen sind dabei Abnahmekriterien,
keine spaeter stillschweigend wegzulassenden Funktionen. Nur dokumentierte
Erweiterungspunkte, kein Zugriff auf Chart.js-Interna oder Methoden-Monkeypatching.

Diese Entkopplung geht ueber das bisherige vollstaendige `update('none')` pro
Zeichentakt hinaus und wird vor einer Architekturfortschreibung abgestimmt.
Ein Worker waere eine andere Architekturvariante, wuerde die Rechenarbeit aber
zunaechst nur verlagern; er ist daher hier weder implementiert noch als Loesung
fuer die erreichte Bildrate belegt. Produktive Vorlagen bleiben unveraendert.

Frische lokale Verifikation: Gesamtsuite, bestehende Browser-/Auswahlregressionen,
zehn Options-Darstellungsvergleiche und drei negative Optionspruefungen bestanden.
PHP-Syntax aller 57 Dateien vor der Arbeit, JavaScript-Syntax des geaenderten
Harnesses, JSON-Style, PHP-CS-Fixer im Pruefmodus (0/45) und `git diff --check`
bestanden. CI des neuen Testwerkzeugs steht nach Commit/Push noch aus.

## Noch offen

- Performancegleichwertigkeit und Langzeit-Speicherlast; Bildabstaende werden
  jetzt gemessen, der Hochlastfall bleibt unzureichend.
- Originale Templateintegration: WebSocket/Pull, asynchroner Reload, Perioden-
  wechsel, Offset, Zaehler, Bool, Animationen und punktweise Styles.
- Echte Hintergrundphasen auf Zielgeraeten, IPSView und installierte Abnahme.
- CI des neuen Testfalls nach Commit/Push.

Keine Aenderung von Properties, JSON-Ausgaben, Metadaten, Plugin-Dateien oder
Assetpfaden. Ein Symcon-Modulupdate oder Dienstneustart ist fuer die lokale
Bewertung dieses nicht eingebundenen Prototyps nicht erforderlich.
