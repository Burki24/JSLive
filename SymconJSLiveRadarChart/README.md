# SymconJSLiveRadarChart

Das Modul erzeugt Radar-/Netzdiagramme für die JSLive-Weboberfläche. Mehrere
Datensätze können aus Symcon-Archivwerten gebildet und mit eigenen Skalen,
Achsen, Punkten, Legenden und Datalabels dargestellt werden.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)
- aktivierte Archivierung für die zu visualisierenden Variablen

Eine `SymconJSLiveRadarChart`-Instanz anlegen, `Datasets` konfigurieren und
mit dem Splitter verbinden. Dieser kann beim Anlegen automatisch erzeugt
werden.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Datensätze | `Datasets` mit Variablen, Reihenfolge, Farben, Offsets und Profilen |
| Skala | `customScale`, Minimal-/Maximalwert, Sektoren und Präzision |
| Achsen | Gitterlinien, Winkelachsen, Punktlabels, Ticks und Farben |
| Darstellung | Titel, Legende, Tooltips, Punkte, Animation und Datalabels |
| Zeitraum | `Period` und `Relativ` für die Archivabfrage |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |

## Daten- und Webhook-Befehle

Der bestehende Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetUpdate(array $querydata)` erzeugt die Datenantwort
aus dem Symcon-Archiv; `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen RadarChart-Instanz.

## Frontend und Sicherheit

Die Standardvorlage ist `RadarChart.html`. Chart.js, Moment und das
Datalabels-Plugin werden aus dem JSLive-Bestand ausgeliefert. Eigene Vorlagen
werden als HTML/JavaScript ausgeführt und müssen vertrauenswürdig sein.
Die modulspezifische Diagnose verwendet `DebugHelper`. Bei aktivem `Debug`
werden numerische Archivwerte einzeln und ohne künstliche Anzahlbegrenzung
ausgegeben. Zusätzlich werden vollständige Browser- und Konfigurationsdaten
einschließlich frei eingegebener Texte protokolliert; bekannte Zugangsdatenfelder
werden maskiert.

Die Standardvorlage behält Chart.js 4.4.1. Für eigene Vorlagen mit historischen
Chart.js-3.x-/Plugin-Pfaden gilt die
[Asset-Migration](../docs/FRONTEND_ASSET_MIGRATION.md).

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{95C8F306-4E51-949E-E25B-FD5C1F173295}` |
| Prefix | `SymconJSLiveRadarChart` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
