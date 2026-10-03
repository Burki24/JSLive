# SymconJSLiveColorPicker

Das Modul stellt einen konfigurierbaren Farbwähler in der JSLive-
Weboberfläche bereit. Mehrere Farb- und Lichtparameter können an Symcon-
Variablen gebunden und über den Browser gelesen beziehungsweise geschrieben
werden.

## Voraussetzungen und Installation

Die erste modernisierte Ausgabe wird zunächst im Kanal **Testing** bereitgestellt.
Dieses Modul verwendet die gemeinsame JSLive-Version im Format
`Hauptversion.Nebenstand`, nicht eine eigene Modulversion. Details und
Update-Hinweise: [Versionierung](../README.md#versionierung-und-veröffentlichung)
und [Testing-Versionshinweise](../CHANGELOG.md#testing-versionshinweise).

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveColorPicker`-Instanz anlegen, die Variablenliste
`Datasets` konfigurieren und den Splitter auswählen.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Layout | `Layout`, Richtung sowie Wheel-, Box- und Slider-Komponenten |
| Farben | HSL/HSV-, RGB-, Kelvin- und Mired-Modi pro Dataset |
| Darstellung | Rahmenbreite/-farbe, Griffgröße, Helligkeitsrad und Drehrichtung |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | `DataUpdateRate`, Cache, Debug und optionale Größenüberschreibungen |

## Daten- und Webhook-Befehle

Der Datenvertrag enthält `getContend`, `getData`, `setData` und
`exportConfiguration`. `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen ColorPicker-Instanz.

## Frontend und Sicherheit

Die Standardvorlage ist `ColorPicker.html`. Sie lädt die Bibliothek iro.js in
der fest versionierten Ausgabe 5.5.0 lokal über den JSLive-Hook. Damit benötigt
die Standardvorlage keine externe CDN-Verbindung.

Eigene Vorlagen laufen als HTML/JavaScript im Browser. Schreibzugriffe auf
Variablen sollten nur über geschützte JSLive-Hooks und vertrauenswürdige
Clients zugelassen werden.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{D65FDF52-B207-0EFD-4D5F-6E39E25B3759}` |
| Prefix | `SymconJSLiveColorPicker` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
