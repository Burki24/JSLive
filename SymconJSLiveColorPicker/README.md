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

- Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveColorPicker`-Instanz anlegen, die Variablenliste
`Datasets` konfigurieren und den Splitter auswählen.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Layout | `Layout`, Richtung sowie Wheel-, Box- und Slider-Komponenten |
| Farben | RGB (`Farbe`), xy (`Farbe (xy)`), Kelvin und Mired pro Dataset; Wheel/Box/Slider für die Auswahl |
| Darstellung | Rahmenbreite/-farbe, Griffgröße, Helligkeitsrad und Drehrichtung |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | `DataUpdateRate`, Cache, Debug und optionale Größenüberschreibungen |

## Daten- und Webhook-Befehle

### Hue-Lampen mit xy-Farbe

Für jede Lampe ihre **String-Farbvariable** in der Variablenliste auswählen und
den Modus **Farbe (xy)** einstellen. Die Quellvariable muss die native Darstellung
**Farbe** mit Kodierung **xy** verwenden; ihr Wert ist beispielsweise
`{"x":0.3127,"y":0.329}`. Die vorhandene Variablenaktion steuert die Lampe.
Keine zusätzliche RGB-Hilfsvariable und kein Umrechnungsskript sind erforderlich.

Jede eingetragene Variable erhält einen eigenen Griff im Farbkreis. Das Verschieben
eines Griffs steuert genau diese Lampe. Für eine gemeinsame Farbe mehrerer Lampen
kann stattdessen die xy-Farbvariable einer vorhandenen Hue-Gruppe verwendet werden.
JSLive legt selbst keine Lampengruppen an.

Die Umrechnung übernehmen Symcons `GetValueColor()` und `RequestActionColor()`.
Dabei gilt der Farbraum der jeweiligen Quellvariable. Beide Funktionen müssen im
installierten Symcon-Build vorhanden sein; sonst Symcon aktualisieren. Die
Farbkreis-Anzeige bleibt eine RGB-Darstellung, keine CIE-Normfarbtafel. Abweichungen
bei Farben außerhalb des Lampenfarbraums sind möglich.
xy speichert nur die Farbart: Die Helligkeit und Ein/Aus werden damit **nicht**
gesteuert und bleiben separat an der Lampe zu bedienen.

WebSocket-Meldungen lösen für xy einen erneuten, von Symcon umgerechneten Lesezugriff
aus; der Pull-Modus liefert dieselbe Anzeige. Empfangene Werte lösen keine
Schreibaktionen aus. Ein nicht lesbarer xy-Eintrag zeigt einen Hinweis und einen
schwarzen, nicht schreibenden Platzhaltergriff; andere Lampen bleiben bedienbar.
Eine deaktivierte/fehlende Variablenaktion wird beim Schaltversuch als Fehler gemeldet.

Bestehende RGB-, Kelvin- und Mired-Einstellungen behalten ihren Modus. Bei eigenen
Vorlagen muss die aktualisierte Standardvorlage übernommen oder die xy-Anbindung
entsprechend ergänzt werden; Browseransichten nach dem Modulupdate neu laden.

### Schnittstellen

Der Datenvertrag enthält `getContend`, `getData`, `setData` und
`exportConfiguration`. `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen ColorPicker-Instanz.

Für `Mode: "xy"` bleiben `Variable`, der rohe JSON-String `Value` und `Mode` im
Lesepaket erhalten. Zusätzlich enthält es `ColorValue` als RGB-Integer für die
Anzeige (oder `null` bei einem Lesefehler) und `Error`. `setData` erwartet im
xy-Modus für `val` einen RGB-Integer von 0 bis 16777215; erst der Server übergibt
den umgerechneten xy-Wert an die Variablenaktion. Die alten Modi behalten ihr
bisheriges Datenformat. Eine fehlgeschlagene xy-Aktion liefert kein `OK`.

Offizielle Referenzen: [GetValueColor](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/variablenzugriff/getvaluecolor/)
und [RequestActionColor](https://www.symcon.de/de/service/dokumentation/befehlsreferenz/variablenzugriff/requestactioncolor/).

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
