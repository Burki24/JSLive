# SymconJSLiveProgressbar

Das Modul stellt den Wert einer Symcon-Variable als animierten Fortschritts-
balken dar. Neben den vorgegebenen Formen können eigene SVG-Pfade und
Füllungen verwendet werden.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)
- eine numerische Symcon-Variable als Datenquelle

Eine `SymconJSLiveProgressbar`-Instanz anlegen, `Variable`, `Type`,
Wertebereich und Form konfigurieren und mit dem Splitter verbinden.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Daten | `Variable`, `data_min`, `data_max`, Präzision und Animationszeiten |
| Form | `shape_preset` (unter anderem Linie, Kreis, Fan, Rainbow, Energy oder Text) |
| Eigene Form | `shape_svg` beziehungsweise `shape_path`; vollständige SVG-Grafiken werden technisch im Fill-Modus dargestellt, SVG-Pfade können über `LoadSvg(string $base64)` für den Stroke-Modus geladen werden |
| Darstellung | Stroke-/Fill-Richtung, Farben, Alpha, Trail, Dash, Schrift und Position |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | Browser-Cache, Debug sowie optionale Größenüberschreibungen |

## Daten- und Webhook-Befehle

Der bestehende Vertrag umfasst `getContend`, `getData`, `getSVG` und
`exportConfiguration`. `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen Progressbar-Instanz.

## Frontend und Sicherheit

Die Standardvorlage ist `Progressbar.html`; die benötigte Loading-Bar-
Bibliothek wird derzeit lokal über den JSLive-Hook geladen. Eigene SVG-
Inhalte und Vorlagen werden im Browser dargestellt und sollten nur aus
vertrauenswürdigen Quellen übernommen werden.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{934051F5-EE82-953D-5241-A29D74CBC251}` |
| Prefix | `SymconJSLiveProgressbar` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
