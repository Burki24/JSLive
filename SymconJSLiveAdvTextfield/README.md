# SymconJSLiveAdvTextfield

Das Modul erzeugt ein konfigurierbares Textfeld für die JSLive-Weboberfläche.
Der Inhalt wird in der Instanzvariable `Content` gehalten und kann aus dem
Browser gelesen und zurückgeschrieben werden. Die HTML-Vorlage stammt aus dem
JSLive-Templatebestand oder optional aus einem Symcon-Skript.

## Voraussetzungen und Installation

Die erste modernisierte Ausgabe wird zunächst im Kanal **Testing** bereitgestellt.
Dieses Modul verwendet die gemeinsame JSLive-Version im Format
`Hauptversion.Nebenstand`, nicht eine eigene Modulversion. Details und
Update-Hinweise: [Versionierung](../README.md#versionierung-und-veröffentlichung)
und [Testing-Versionshinweise](../CHANGELOG.md#testing-versionshinweise).

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Die Library über den Branch beziehungsweise Release installieren, eine
`SymconJSLiveAdvTextfield`-Instanz anlegen und mit dem JSLive-Splitter verbinden.
IP-Symcon kann die fehlende Splitterinstanz beim Anlegen automatisch erzeugen.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Vorlage | `Template` (Standard `Textfield1`) oder `TemplateScriptID` für eine eigene HTML-/JavaScript-Vorlage |
| Ausgabe | HTMLBox-/IPSView-Ausgabe über `CreateOutput` und `CreateIPSView` |
| Darstellung | fünf Highlight-Farben, Hintergrund, Schrift, Rahmen und Alpha-Werte |
| Anzeige | `ViewLevel`, Viewport, IFrame-Höhe sowie optionale Breiten-/Höhenüberschreibungen |
| Betrieb | Browser-Cache, Debug und Aktualisierungsintervall `DataUpdateRate` |

Bei einer eigenen Vorlage werden die Platzhalter `{CONFIG}`, `{VALUE}` und
`{FONTS}` ersetzt. Vorlagen können über die Konfigurationsfunktionen des
Splitters übernommen werden.

## Daten und Befehle

Die Variable `Content` ist der öffentliche Textwert. Der bestehende
JSLive-Datenvertrag unterstützt unter anderem:

- `getContend` für die Ausgabe,
- `getData` zum Lesen von Variable und Inhalt,
- `setData` zum Schreiben des Inhalts,
- `exportConfiguration` für den Konfigurationsexport.

`LoadOtherConfiguration(int $id)` übernimmt die Konfiguration einer anderen
AdvTextfield-Instanz desselben Modultyps.

## Hinweise und Sicherheit

Eigene Vorlagen werden als HTML/JavaScript im Browser ausgeführt und dürfen nur
aus vertrauenswürdigen Quellen stammen. Bei aktiviertem `Debug` protokolliert
die gemeinsame Browser-Diagnose auch den geschriebenen Text und die
Konfigurationsdaten. Die bestehende `~HTMLBox`-/
IPSView-Kompatibilität bleibt erhalten; eine native Kacheldarstellung ist eine
spätere Ausbaustufe.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{DBAF2DB0-0FCF-8396-9476-3C087457D012}` |
| Prefix | `SymconJSLiveAdvTextfield` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Content-Variable | `Content` (String) |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
