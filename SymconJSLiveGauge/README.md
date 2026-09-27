# SymconJSLiveGauge

Das Modul visualisiert den Wert einer Symcon-Variable als animiertes
Messinstrument. Unterstützt werden die mitgelieferten radialen und linearen
Canvas-Gauge-Vorlagen sowie konfigurierbare Skalen, Zeiger, Wertanzeige,
Fortschrittsbalken und Hervorhebungsbereiche.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)
- eine numerische Symcon-Variable als Datenquelle

Eine `SymconJSLiveGauge`-Instanz anlegen, `Variable`, `min`, `max` und
`precision` setzen und den Splitter verbinden.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Datenquelle | `Variable`, Wertebereich `min`/`max`, `precision` und optionale automatische Konvertierung |
| Vorlage | `template` (`CanvasGauges-Radial`, `CanvasGauges-Linear` oder `CanvasGauges-Compass`) |
| Skala | Ticks, Zwischenwerte, Hervorhebungen sowie radiale/lineare Winkel und Seiten |
| Anzeige | Titel, Einheit, Platte, Nadel, ValueBox und Fortschrittsbalken |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | Browser-Cache und Debug |

## Daten- und Webhook-Befehle

Der bestehende Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetData(array $querydata)` liefert die aktuelle
Gauge-Konfiguration; `LoadOtherConfiguration(int $id)` übernimmt eine andere
Gauge-Instanz.

## Frontend und Sicherheit

Die Standardvorlagen verwenden die lokal im JSLive-Hook ausgelieferte
Canvas-Gauges-Bibliothek. Eigene Vorlagen laufen als HTML/JavaScript im Browser
und müssen vertrauenswürdig sein.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{71B93700-9659-97C6-AD83-984C2B44139F}` |
| Prefix | `SymconJSLiveGauge` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
