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

## Daten- und Webhook-Befehle

Der bestehende JSLive-Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetUpdate(array $querydata)` erzeugt die Datenantwort
für den angeforderten Zeitraum; `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen Chart-Instanz.

## Frontend und Sicherheit

Die Standardvorlage ist `Chart.html`. Chart.js, Moment und die benötigten
Plugins werden derzeit über den JSLive-Hook ausgeliefert. Eigene Vorlagen
werden als HTML/JavaScript ausgeführt und müssen vertrauenswürdig sein.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{4713B9C2-22C8-7A45-060C-8C678DE05CC6}` |
| Prefix | `SymconJSLiveChart` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
