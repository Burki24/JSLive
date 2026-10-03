# SymconJSLiveDateTimePicker

Das Modul stellt eine browserbasierte Datums-/Zeitauswahl für eine bestehende
Symcon-Variable bereit. Der ausgewählte Unix-Zeitstempel wird über den
JSLive-Splitter gelesen und zurück in die konfigurierte Variable geschrieben.

## Voraussetzungen und Installation

Die erste modernisierte Ausgabe wird zunächst im Kanal **Testing** bereitgestellt.
Dieses Modul verwendet die gemeinsame JSLive-Version im Format
`Hauptversion.Nebenstand`, nicht eine eigene Modulversion. Details und
Update-Hinweise: [Versionierung](../README.md#versionierung-und-veröffentlichung)
und [Testing-Versionshinweise](../CHANGELOG.md#testing-versionshinweise).

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine beschreibbare Datums-/Zeitvariable in IP-Symcon
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveDateTimePicker`-Instanz anlegen, unter `Variable` das Ziel
auswählen und die Instanz mit dem Splitter verbinden. Die Variable muss für
den verwendeten Wertetyp geeignet sein; bei einer Variablenaktion wird
`RequestAction` verwendet, andernfalls `SetValue`.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Ziel | `Variable` mit dem zu lesenden und zu schreibenden Unix-Zeitwert |
| Vorlage | `Template` (Standard `TimePicker1`) oder `TemplateScriptID` |
| Ausgabe | HTMLBox-/IPSView-Ausgabe, Viewport, IFrame-Größe und Cache |
| Darstellung | fünf Highlight-Farben, Hintergrund, Schrift und Rahmen |
| Betrieb | Debug, `ViewLevel` und `DataUpdateRate` |

Vorlagen erhalten `{CONFIG}`, `{VALUE}` und `{FONTS}`. Eine eigene Vorlage
muss aus einer vertrauenswürdigen Quelle stammen.

DatePicker und DateTimePicker verwenden `css/DatePicker1.css`. Die früher nur
auskommentiert eingebundene MCDatepicker-Bibliothek und das ungenutzte
`css/DateTimePicker1.css` sind entfernt. Bei eigenen Vorlagen die
[Asset-Migration](../docs/FRONTEND_ASSET_MIGRATION.md) vor dem Update beachten.

## Daten und Befehle

Der bestehende Datenvertrag umfasst:

- `getContend` für die Ausgabe,
- `getData` für Ziel-ID und aktuellen Wert,
- `setData` für das Schreiben eines geprüften Zielwerts,
- `exportConfiguration` für den Konfigurationsexport.

`LoadOtherConfiguration(int $id)` übernimmt die Konfiguration einer anderen
DateTimePicker-Instanz desselben Typs und erhält dabei die lokale
Variablenzuordnung.

## Sicherheit und Grenzen

Der Browserwert wird gegen die konfigurierte Variablen-ID geprüft. Trotzdem
sollten nur passende und bewusst beschreibbare Variablen verwendet werden.
Bei aktiviertem `Debug` enthält die gemeinsame Browser-Diagnose auch den
übergebenen Zeitwert.
Die bestehende `~HTMLBox`-/IPSView-Ausgabe bleibt kompatibel; die native
Symcon-Kachel ist eine spätere Migrationsstufe.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{B6ABE101-157B-3F68-CE81-25CBE5A8444B}` |
| Prefix | `SymconJSLiveDateTimePicker` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Ziel-Property | `Variable` (Integer-ID) |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
