# Eigene Echtzeitsteuerung: Prototyp 1

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

```text
node tests/realtime-window.js
node tests/realtime-window-browser.js
```

Der zweite Aufruf benoetigt Playwright im Node-Suchpfad. Optional setzt
`JSLIVE_BROWSER_EXECUTABLE` den Pfad eines bereits vorhandenen Browsers. Das
Skript kann alternativ als Funktion mit einer vorhandenen Playwright-
Browserinstanz aufgerufen werden. Es schliesst seine isolierten Kontexte;
der CLI-Einstieg schliesst auch den Browser. Keine Symcon-Verbindung, keine
Produktivdaten, keine externen Netzwerkabrufe in den Testseiten.

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

## Noch offen

- Performancegleichwertigkeit, gemessene Bildabstaende und Langzeit-Speicherlast.
- Originale Templateintegration: WebSocket/Pull, asynchroner Reload, Perioden-
  wechsel, Offset, Zaehler, Bool, Animationen und punktweise Styles.
- Echte Hintergrundphasen auf Zielgeraeten, IPSView und installierte Abnahme.
- CI des neuen Testfalls nach Commit/Push.

Keine Aenderung von Properties, JSON-Ausgaben, Metadaten, Plugin-Dateien oder
Assetpfaden. Ein Symcon-Modulupdate oder Dienstneustart ist fuer die lokale
Bewertung dieses nicht eingebundenen Prototyps nicht erforderlich.
