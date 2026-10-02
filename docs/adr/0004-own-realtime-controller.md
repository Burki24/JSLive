# ADR 0004: Eigene Echtzeitsteuerung fuer JSLive Chart

- Status: Abgeloest durch [ADR 0005](0005-maintained-streaming-fork.md); historischer Prototyp bleibt erhalten
- Datum: 02.10.2026
- Ausgangsstand: JSLive 0.78, Chart.js 4.5.1, Streaming-Fork 3.1.0

Die folgende Entscheidung dokumentiert den urspruenglichen Versuch. Nach dem
nicht bestandenen Performancegate hat der Eigentuemer den Wartungsfork als
weiteren Entwicklungsweg gewaehlt. Es gibt keinen Auftrag zur Weiterentwicklung
des Prototyps oder zum Render-Cache; die produktive Einbindung bleibt unveraendert.

## Kontext

Die Zukunft der Streaming-Forks ist ungewiss. Ein eigener Fork wuerde zwar die
Veroeffentlichung kontrollierbar machen, aber auch die bestehenden Eingriffe in
Chart.js-Interna uebernehmen. JSLive besitzt bereits WebSocket, Pull-Abfragen,
Archivzugriff, Wertaufbereitung und Offset-Nachladen. Die zusaetzliche
Streaming-Skala wird in der Standardvorlage fuer relative Minuten- und
Stundenfenster gebraucht.

Der Repository-Eigentuemer hat die Entwicklung einer eigenen, begrenzten
Echtzeitkomponente anstelle eines Wartungsforks beschlossen. Dies ist keine
Freigabe zur sofortigen Entfernung des Plugins oder zum Verlust bestehender
Funktionen.

## Entscheidung

Eine separate JSLive-JavaScript-Komponente steuert die normale Chart.js-
Zeitachse ueber `options.scales.x.min/max`, Datensaetze und `update('none')`.
Sie ersetzt weder Chart.js noch den Datumadapter oder die Symcon-Kommunikation.
Es gibt keinen neuen hochfrequenten PHP-Timer und keine neue npm-Abhaengigkeit.
Keine eigene Kopie des Upstream-Streaming-Plugins, keine private Chart.js-API,
kein Ueberschreiben von Chart.js-Methoden in der Komponente.

Der erste, nicht produktiv eingebundene Prototyp liegt bewusst unter
`tests/prototypes/realtime-window.js`. Es gibt genau einen Eigentuemer pro Chart,
einen begrenzten Zeichentakt und eine davon getrennte Datenbereinigung.
Eingehende Werte werden vor dem naechsten Zeichentakt gebuendelt. Verborgene
Dokumente zeichnen nicht, bereinigen aber bei Wartung und Datenzufuhr weiter.
Beim Einblenden folgt das Zeitfenster unmittelbar der aktuellen Zeit.

Pause friert das sichtbare Zeitfenster ein. Die Bereinigung behaelt dessen
Punkte und das aktuelle Livefenster, nicht die gesamte Luecke dazwischen.
Zwei Vorgaengerpunkte erhalten die Kurve am linken Rand. Diese zeitliche
Begrenzung ist keine feste Speicherobergrenze bei beliebig hoher Datenrate.
Die spaetere Transportintegration muss Datenannahme und Invalidierung gemeinsam
ausfuehren. Nach `destroy()` bleiben keine eigenen Timer/Listener zurueck;
der Aufrufer zerstoert anschliessend die Chart-Instanz.

## Umfang und Grenzen des ersten Prototyps

- Sortierte Objektpunkte mit numerischem Millisekunden-`x`, skalare Styles,
  Linie/Balken, Minuten-/Stundenfenster und normale Zeitachse.
- Punktobjekte samt `y`, `c` und weiteren Metadaten bleiben erhalten; der
  Controller aggregiert keine Werte und berechnet keine Zaehlerdifferenzen.
- Nicht unterstuetzte unsortierte Daten oder punktweise Optionsarrays werden
  explizit abgewiesen, nicht stillschweigend umgeordnet oder beschaedigt.
- Keine produktive Template-/PHP-Integration, keine neue Property, keine
  Aenderung an ausgegebenen JSON-Strukturen oder gespeicherter Konfiguration.
- Kein Nachweis fuer alle benutzerdefinierten Templates, IPSView-Geraete oder
  Symcon-Datenpfade. Ein funktionierender Prototyp ist keine Migrationsfreigabe.

## Abnahmekriterien vor einer produktiven Umstellung

1. **Darstellung:** relative Minute/Stunde, absolute/historische Ansichten,
   Linie/Balken/Mischdiagramme, Kategorien/Bool, Luecken, Nullwerte, mehrere
   Achsen, Labels, Tooltips und bisherige Animationseinstellungen vergleichen.
   Lesbarkeit und fluessige Bewegung duerfen nicht stillschweigend reduziert
   werden; eine andere Bildrate ist eine sichtbare Entscheidung.
2. **Datenvertrag:** originaler WebSocket-/Pull-Pfad, Aggregation, Zaehler,
   Praezision, Offset-Vergleich und asynchrones Laden bleiben erhalten.
   Doppelte, verspaetete und ungeordnete Zeitstempel brauchen vor Integration
   eine explizite Strategie; keine stillen Wertverluste.
3. **Lebenszyklus:** mehrfaches Starten, Reload, Periodenwechsel, Pause/Resume,
   Hintergrund/Vordergrund, Verbindungsunterbrechung und Abbau ohne Lecks.
4. **Ressourcen:** Langlauf mit wachsender Eingangsdatenmenge und Hintergrund-
   phasen; identische Lasten gegen das bestehende Plugin messen. Messungen
   muessen Zeichentakt, Hauptthreadzeit und Datenbestand gemeinsam bewerten.
   Eine deutliche Mehrlast wie im ersten Prototyp sperrt die Umstellung.
5. **Kompatibilitaet/Rueckfall:** bisherigen Pluginpfad fuer eigene Vorlagen
   erhalten. Der PHP-Ausgabevertrag `type: realtime` darf nicht nebenbei
   umgestellt werden; eine lokale Uebersetzung in der Standardvorlage ist vor
   einer Vertragsmigration zu pruefen. Rueckfall auf die bisherige Vorlage
   ohne Konfigurations- oder Archivverlust nachweisen.
6. **Freigabe:** CI und gezielte MCP-CURRENT-Abnahme nach dem Modulupdate.
   `ApplyChanges()` erfolgt dabei automatisch; kein zusaetzlicher Aufruf.

## Vorgehen und Alternativen

1. Isolierter Controller, deterministische Tests und reproduzierbarer Browser-
   vergleich (dieser Schritt).
2. Rechenaufwand profilieren und ueber oeffentliche APIs reduzieren. Danach
   erneut mit gleichem Zeichentakt messen; kein stilles Abschalten von Labels
   oder Ausduennen fachlicher Daten als Performancekorrektur.
3. Fehlende Daten-/Darstellungsfaelle und Transportintegration ergaenzen.
4. Erst nach bestandenen Kriterien die Standardvorlage separat umstellen.

Ein Wartungsfork bleibt eine Rueckfalloption, falls eine eigene Steuerung bei
vertretbarem Aufwand keine gleichwertige Darstellung erreicht. Ein direkter
Wechsel zu einem anderen Fork oder ein kompletter Chart.js-Ersatz ist nicht
beschlossen. Die Verantwortung fuer Tests und Wartung liegt bei JSLive;
oeffentliche Schnittstellen reduzieren das Aenderungsrisiko, beseitigen es aber
nicht.

## Nachweise und Quellen

- [Prototyp- und Vergleichsnachweis](../REALTIME_PROTOTYPE.md)
- [Chart.js: Time axis](https://www.chartjs.org/docs/latest/axes/cartesian/time.html)
- [Chart.js: Updating charts](https://www.chartjs.org/docs/latest/developers/updates.html)
- [Chart.js: API und aktive Elemente](https://www.chartjs.org/docs/latest/developers/api.html)
