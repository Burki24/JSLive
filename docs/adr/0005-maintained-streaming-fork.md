# ADR 0005: Eigener Wartungsfork fuer Chart.js Streaming

- Status: Angenommen; versionierte Integration 3.6.0 lokal umgesetzt, JSLive-CI und installierte Abnahme offen
- Datum: 02.10.2026
- Ersetzt: [ADR 0004](0004-own-realtime-controller.md)
- Ausgangsstand: JSLive 0.82 mit Streaming 3.1.0; Wartungsfork 3.4.0

## Kontext und Entscheidung

Der isolierte Controller ueber oeffentliche Chart.js-APIs konnte die Funktion,
aber nicht die benoetigte Hochlastleistung nachweisen. Auch die dokumentierten
Parser-/Labelversuche bestanden das Performancegate nicht. Der Eigentuemer hat
daraufhin einen vollstaendigen Fork unter
https://github.com/Burki24/chartjs-plugin-streaming angelegt und dessen Wartung
als weiteren Weg beschlossen.

Streaming wird im eigenstaendigen JavaScript-Repository weiterentwickelt, nicht
als zweite Implementierung innerhalb von JSLive. Der bestehende Prototyp samt
Tests und Messungen bleibt als historischer Versuch erhalten. Ein Render-Cache
ist kein aktiver Arbeitsschritt. Ein Wechsel zu Luxon ist nicht beschlossen.

## Vertraege und Verantwortung

- Chart.js, Moment, dessen Adapter und Datalabels werden nicht gleichzeitig mit
  dem Streaming-Plugin aktualisiert. Der aktuelle Vergleich verwendet Chart.js
  4.5.1, Moment 2.31.0, chartjs-adapter-moment 1.0.1 und Datalabels 2.2.0.
- Die Wartung der Chart.js-internen Eingriffe des Forks wird bewusst uebernommen;
  Tests gegen unterstuetzte Chart.js-Versionen bleiben erforderlich.
- PHP-Ausgaben, `type: realtime`, Properties, gespeicherte Konfigurationen,
  Archivdaten, WebSocket-/Pull-Vertraege und eigene Vorlagen bleiben erhalten.
- JSLive bezieht ein festes, nachvollziehbares Bundle mit Lizenz,
  Herkunft und Hash. Der bisherige Assetpfad bleibt als Kompatibilitaetspfad.
- Der Fork verwendet `dev`/`main` und sein eigenes `major.minor.0`-Schema.
  Versionsautomatik ist keine Produktfreigabe. Commit, Push, Tag und Release
  bleiben Aufgabe des Eigentuemers.

## Naechste Schritte und Freigabegrenzen

1. Isolierte Browsermatrix mit dem tatsaechlichen JSLive-Bibliotheksmix:
   Linie/Balken, sichtbare Labels, Datenbereinigung, Tooltiptexte und -auswahl,
   Pause/Resume sowie Abbau. Luxon-Tests des Forks bleiben zusaetzlich erhalten.
2. Begrenzter, reproduzierbarer Langlauf gegen das bisherige JSLive-Bundle 3.1.0
   mit identischer Last. Hauptthreadzeit, Zeichentakt, Datenbestand und Speicher
   gemeinsam beurteilen; Rohmessungen und Grenzen festhalten.
3. Erst danach eine getrennte Freigabe/Integration mit versioniertem Assetpfad,
   Rueckfallmoeglichkeit und CI. Keine produktive Templateaenderung in Schritt 1/2.
4. Nach Modulupdate gezielte MCP-CURRENT-/Browserabnahme mit WebSocket, Pull,
   Reload, relativen/historischen Perioden und den verwendeten IPSView-Geraeten.
   `ApplyChanges()` erfolgt beim Modulupdate automatisch.

Ein lokaler Browservergleich ist weder ein installierter Symcon-Nachweis noch
eine vollstaendige Freigabe aller Konfigurationen oder ein mehrtaegiger Dauertest.

## Nachweise

- [Integration 3.6.0, Herkunft und offene Abnahme](../STREAMING_INTEGRATION.md)
- [Historischer Prototyp und Performancevergleich](../REALTIME_PROTOTYPE.md)
- [Frontend-Inventar](../FRONTEND_DEPENDENCIES.md)
- Fork: `docs/JSLIVE_COMPATIBILITY.md` mit Testbefehlen und Vergleichsergebnissen.
