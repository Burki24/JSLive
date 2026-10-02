# SymconJSLiveChart

Das Modul erzeugt konfigurierbare Zeit- und Balkendiagramme für die
JSLive-Weboberfläche. Messwerte werden aus dem IP-Symcon-Archiv gelesen und
als mehrere Datensätze mit eigenen Achsen, Farben und Darstellungsoptionen
ausgegeben.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)
- aktivierte Archivierung für die zu visualisierenden Variablen

Eine `SymconJSLiveChart`-Instanz anlegen und mit dem Splitter verbinden. Der
Splitter kann beim Anlegen automatisch erzeugt werden.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Datensätze | `Datasets` mit Variablen, Datentyp (Linie/Balken), Farben, Profilen und Offset |
| Achsen | `Axes` sowie lineare/kategoriale Skalierung und dynamische X-Achse |
| Zeitraum | Periodenvariable `Period`, `Now`, `Relativ`, `Offset` und `StartDate` |
| Darstellung | Titel, Legende, Tooltips, Punkte, Animation und Datalabels |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | Browser-Cache, Debug sowie asynchrones Laden und Datenpräzision |

Die historischen Werte werden abhängig vom Zeitraum über die Archiv-
Aggregation geladen. Für Echtzeitansichten kann der relative Zeitraum aktiviert
werden.

Beim asynchronen Laden werden die Datensatzabrufe parallel ausgeführt. Die
Ansicht wird erst übernommen, wenn alle Datensatzabrufe abgeschlossen sind;
die konfigurierte Reihenfolge bleibt unabhängig von der Antwortreihenfolge
erhalten. Leere Antworten werden ausgelassen. Bei fehlgeschlagenen Abrufen
werden die übrigen Datensätze dargestellt und der Fehler mit Datensatzindex
ohne Anfrage-URL in der Browserkonsole gemeldet.

Nach einem Modulupdate die Ansicht neu laden. `ApplyChanges()` wird beim
Modulupdate automatisch ausgeführt und erneuert dabei den HTML-Cache;
ein zusätzlicher Aufruf ist nicht erforderlich. Ein Dienstneustart ist für
diese Template-Korrektur nicht erforderlich. Eigene `TemplateScriptID`-Vorlagen
werden nicht automatisch geändert.

## Daten- und Webhook-Befehle

Der bestehende JSLive-Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetUpdate(array $querydata)` erzeugt die Datenantwort
für den angeforderten Zeitraum; `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen Chart-Instanz.

Die vom Konfigurationsformular verwendeten öffentlichen Callbacks
`ReloadFormAxes(string $arr, int $type)` und
`ReloadFormDatasets(string $arr, int $type)` erwarten die jeweilige Listenzeile
als JSON-String. Eigene Skriptaufrufe müssen Arraywerte daher mit
`json_encode()` übergeben.

## Frontend und Sicherheit

Die Standardvorlage ist `Chart.html`. Chart.js, Moment und die benötigten
Plugins werden derzeit über den JSLive-Hook ausgeliefert. Eigene Vorlagen
werden als HTML/JavaScript ausgeführt und müssen vertrauenswürdig sein.
Die Standardvorlage verwendet die lokal versionierte Chart.js 4.5.1.
Moment.js wird lokal als 2.31.0 geladen; der alte 2.27.0-Pfad bleibt für eigene
Vorlagen erhalten. Der Moment-Adapter wird als 1.0.1 lokal versioniert geladen;
sein alter 1.0.0-Pfad bleibt ebenfalls erhalten. Datalabels 2.2.0 wird aus der
unveränderten offiziellen Distribution im versionierten Pfad geladen; der
historisch modifizierte Datalabels-Pfad bleibt für eigene Vorlagen erhalten.
Die Standardvorlage lädt den eigenen Streaming-Wartungsfork 3.6.0 über
`chartjs/plugins/streaming/3.6.0/chartjs-plugin-streaming.min.js`. Der alte
unversionierte 3.1.0-Pfad bleibt für eigene Vorlagen unverändert. Die
Standardvorlage setzt `frameRate: 30` und aktualisiert eingehende Livewerte
bei Realtime-Achsen mit `update('quiet')`, damit die laufende Animation nicht
unterbrochen wird. Gewöhnliche Zeitachsen verwenden den Standardmodus.
Der Wartungsfork folgt [ADR 0005](../docs/adr/0005-maintained-streaming-fork.md).
Der eigene Echtzeit-Prototyp bleibt als historischer Versuch erhalten und ist
nicht produktiv eingebunden. Herkunft, lokale Tests und noch offene installierte
Abnahme stehen im [Integrationsnachweis](../docs/STREAMING_INTEGRATION.md).
Die alten URLs mit 4.3.3 und 4.4.1 bleiben für eigene Vorlagen unverändert.
Historische Chart.js-3.x-Dateien und
ungenutzte Plugin-Kopien sind entfernt; eigene Vorlagen vor dem Update anhand
der [Asset-Migration](../docs/FRONTEND_ASSET_MIGRATION.md) prüfen.
Die Diagnose verwendet `DebugHelper`: Bei aktivem `Debug` werden numerische
Messwerte einzeln und ohne künstliche Anzahlbegrenzung ausgegeben. Zusätzlich
werden vollständige Browser-Abfragen und Konfigurationstransfers protokolliert;
bekannte Zugangsdatenfelder werden maskiert.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{4713B9C2-22C8-7A45-060C-8C678DE05CC6}` |
| Prefix | `SymconJSLiveChart` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
