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

Die Deckkraft `HighlightColor_Alpha` der Hervorhebungsbereiche wird mit zwei
Nachkommastellen ausgegeben, unabhängig von `precision` für Messwerte und
Bereichsgrenzen. Auch bei ganzzahliger Anzeige bleibt beispielsweise eine
Deckkraft von `0.25` erhalten.

## Daten- und Webhook-Befehle

Der bestehende Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetData(array $querydata)` liefert die aktuelle
Gauge-Konfiguration; `LoadOtherConfiguration(int $id)` übernimmt eine andere
Gauge-Instanz.

## Frontend und Sicherheit

Die Standardvorlagen verwenden die lokal im JSLive-Hook ausgelieferte
Canvas-Gauges-Bibliothek. Eigene Vorlagen laufen als HTML/JavaScript im Browser
und müssen vertrauenswürdig sein.

Die vier Standardvorlagen verwenden Canvas Gauges 2.1.7 mit dem lokalen Patch
`2.1.7-jslive.1`. Bei schnellen Wertwechseln endet die Animation am zuletzt
angeforderten Ziel, statt auf einen überholten Wert zurückzuspringen. Wiederholte
Zielwerte unterbrechen die laufende Animation nicht. Werteumrechnung,
Formatierung und Konfiguration bleiben erhalten; keine neue Gauge-Engine.
Herkunft und Dateiintegrität sind abgesichert; aktive Upstream-Pflege ist nicht
belegt. Umfang und Grenzen: [Gauge-Audit](../docs/GAUGE_AUDIT.md).

Der alte Pfad `canvas-gauges/gauge.min.js` bleibt unverändert. Eigene
`TemplateScriptID`-Vorlagen werden nicht automatisch umgestellt und behalten
gegebenenfalls den alten Fehler. Für sie den Bibliothekspfad auf
`canvas-gauges/2.1.7-jslive.1/gauge.min.js` umstellen und separat prüfen;
nicht beide Bibliotheken in derselben Seite laden.
Nach dem Modulupdate die Ansicht ausdrücklich neu laden, bei Bedarf ohne
Browser-Cache. `ApplyChanges()` erfolgt automatisch; kein zusätzlicher Aufruf
oder Dienstneustart ist für diese Korrektur erforderlich.

## Technische Daten

Die vier Standardvorlagen passen ihre vorhandene Canvas-Anzeige auch nach
Groessenaenderungen an die HTMLBox an. Automatische Abmessungen benoetigen
`overrideWidth = 0` und `overrideHeight = 0`. Die horizontale Anzeige begrenzt
ihre Hoehe zusaetzlich auf den verfuegbaren Platz; der Kompass passt als Quadrat
in beide Achsen. Positive Overrides bleiben bewusst gesetzte Vorgaben.


| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{71B93700-9659-97C6-AD83-984C2B44139F}` |
| Prefix | `SymconJSLiveGauge` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
