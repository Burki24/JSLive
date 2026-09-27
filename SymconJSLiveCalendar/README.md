# SymconJSLiveCalendar

Das Modul stellt einen konfigurierbaren Kalender auf Basis der JSLive-
Webausgabe bereit. Unterstützt werden Kalenderdaten aus Symcon-Modulen,
eingebettete iCalendar-Daten und iCalendar-URLs. Darstellung, Toolbar, Farben,
Typografie und eigene Ansichten werden über das Symcon-Konfigurationsformular
festgelegt.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveCalendar`-Instanz anlegen und mit dem Splitter verbinden.
Die Splitterinstanz kann beim Anlegen eines Kindmoduls automatisch erzeugt
werden.

## Kalenderquellen

In `dataEvents` werden die gewünschten Quellen gepflegt:

- `moduleInstance`: Kalender- oder Notifier-Instanz aus Symcon;
- `ical`: eingebettete, Base64-kodierte iCalendar-Daten;
- `icalLink`: externe iCalendar-URL.

Quellen können mit Namen und Ereignisfarben versehen werden. Eigene Calendar-
Ansichten werden über `customViews` definiert; Header und Footer besitzen jeweils
getrennte Toolbar-Listen. `CustomCSS` kann die erzeugte Standardformatierung
ersetzen.

## Darstellung und Ausgabe

Das Formular bietet unter anderem:

- Initialansicht sowie eigene Tages-, Wochen-, Monats- und Listenansichten;
- Header-/Footer-Anzeige und FullCalendar-Toolbar;
- Tabellen-, Wochenend-, Wochen- und Ereignisformatierung;
- Schrift-, Rahmen-, Hintergrund- und Alpha-Werte;
- Viewport-, IFrame-, Cache-, Debug-, HTMLBox- und IPSView-Einstellungen.

Die Standardvorlage ist `Calendar.html`. Über `TemplateScriptID` kann eine
vertrauenswürdige eigene Vorlage verwendet werden.

## Daten- und Webhook-Befehle

Der bestehende JSLive-Vertrag umfasst:

- `getContend` und `getData` für die Kalenderausgabe,
- `getFeed` für unterstützte Symcon-Kalender-/Notifier-Instanzen,
- `getICS` für eingebettete oder verlinkte iCalendar-Quellen,
- `getCSS` für die erzeugte CSS-Ausgabe,
- `setData` und `exportConfiguration`.

Zusätzlich stellt das Modul `GetCSSLink()` und `GetDefaultCSSLink()` für die
CSS-Auslieferung bereit. `LoadOtherConfiguration(int $id)` übernimmt die
Konfiguration einer anderen Calendar-Instanz desselben Typs.

## Bekannte Einschränkungen

Die aktuelle Bestandsversion lädt Teile von FullCalendar und iCalendar noch von
externen CDNs. Dadurch ist die Auslieferung nicht vollständig offline und
reproduzierbar. Die Konsolidierung und lokale Versionierung dieser Ressourcen
ist als eigene Modernisierungsphase vorgesehen. iCalendar-URLs sollten nur aus
vertrauenswürdigen Quellen verwendet werden.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{46B41C3B-DDAE-BA35-2A1E-6CF4B7F9BF7A}` |
| Prefix | `SymconJSLiveCalendar` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
