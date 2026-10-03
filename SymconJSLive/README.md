# SymconJSLive

Der zentrale Splitter für alle JSLive-Kindmodule. Er stellt den Webhook
`/hook/JSLive` bereit, liefert HTML-, CSS- und JavaScript-Ressourcen aus und
routet die Datenanforderungen der Kindmodule. Die bestehende Splitter-/Kindmodul-
Architektur ist Teil des öffentlichen JSLive-Vertrags.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine erreichbare Webhook-Adresse des IP-Symcon-Servers

Die Library installieren und eine `SymconJSLive`-Instanz anlegen. Beim Anlegen
eines JSLive-Kindmoduls kann IP-Symcon den Splitter automatisch erzeugen.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Verbindung | `Address` (Standard `http://127.0.0.1:3777`) und automatisch erzeugtes `Password` |
| Datenübertragung | `DataMode` (Pull/WebSocket) und `RefreshTime` für Pull-Abfragen in Sekunden |
| Browserausgabe | `EnableViewport`, `viewport_content`, `enableCache`, `enableCompression` |
| Integration | `CreateIPSView` und `Iframe_useFullLink` für die erzeugten Ausgabelinks |
| Diagnose | `Debug` |

Die Standardadresse ist nur für einen lokalen Symcon-Dienst gedacht. Für eine
externe Erreichbarkeit sollte der Hook ausschließlich über eine abgesicherte
TLS-Verbindung und mit gesetztem Passwort veröffentlicht werden.

Das bestehende CORS-, Kennwort- und Schreibmodell einschließlich der bewusst
öffentlichen Asset-Pfade ist im
[Webhook-Sicherheitsmodell](../docs/WEBHOOK_SECURITY_MODEL.md) dokumentiert.
Vollständige JSLive-Links enthalten das Kennwort und sind wie Zugangsdaten zu
behandeln.

## Vorlagen und Datenvertrag

`UpdateTemplates(int $category)` kopiert die mitgelieferten HTML-Vorlagen in
eine Symcon-Kategorie. Eigene Vorlagen werden als HTML/JavaScript im Browser
ausgeführt und dürfen nur aus vertrauenswürdigen Quellen stammen.

Die Kindmodule sprechen über den Hook unter anderem die bestehenden Aktionen
`getContend`, `getData`, `setData`, `getSVG` und
`exportConfiguration` an. Die Namen und JSON-Strukturen sind öffentliche
Verträge und werden bei der Modernisierung rückwärtskompatibel behandelt.

Das Zusammenspiel der Ausgabevariablen `IPSView` und `Output`, ihrer Schalter,
des HTML-Caches und der öffentlichen Linkmethoden ist im
[Ausgabevertrag](../docs/VISUALIZATION_OUTPUT_CONTRACTS.md) dokumentiert.
Die bestehende Legacy-Darstellung bleibt unverändert.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}` |
| Prefix | `SymconJSLive` |
| Kindmodul-Anforderung | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |
| Implementierte Schnittstelle | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Hook | `/hook/JSLive` |

## Bekannte Einschränkungen

### Automatische HTMLBox-Groesse

Bei `overrideWidth = 0` und `overrideHeight = 0` folgen die Standardanzeigen
der verfuegbaren Breite und Hoehe ihres Browser-/Iframe-Fensters, auch nach
Groessenaenderungen. Gauge, Chart, Doughnut/Pie, Radar, Colorpicker und
Progressbar behalten dabei Instanz und aktuellen Wert. Beim Colorpicker muss
auch `manWidth = 0` sein. Positive Groessenvorgaben bleiben absichtliche
Overrides; sie koennen in kleineren Boxen weiterhin ueberlaufen.

`IFrameHeight` betrifft den `Output`-Iframe, nicht das vollstaendige HTML der
Variable `IPSView`. Bei eigenen `TemplateScriptID`-Vorlagen und Custom-Inhalten
bleibt das Layout Aufgabe der Vorlage. Native Text-/Datumsfelder behalten ihre
konfigurierte Schriftgroesse; sehr grosse Schriften, Rahmen oder viele feste
Slider benoetigen entsprechend Platz. Kein pauschales Abschneiden per CSS.

Nach dem Modulupdate die Ansicht vollstaendig neu laden, bei Bedarf den
Browser-/WebView-Cache leeren. `ApplyChanges()` erfolgt beim Modulupdate
automatisch; ein Dienstneustart ist fuer diesen Frontend-Fix nicht erforderlich.


Die mitgelieferten Templates und Stylesheets laden ihre Frontend-Ressourcen
lokal über `/hook/JSLive/js/`. Quellen, Versionen, Hashes und Lizenznachweise
der vendorten Bibliotheken und Schriften sind in der zentralen
Frontend-Inventur dokumentiert. Benutzerdefinierte Templates können weiterhin
eigene externe Ressourcen einbinden.

Alle mitgelieferten HTML-Vorlagen laden die vollständige jQuery-4.0.0-Distribution
aus `js/jquery/4.0.0/`. Ältere Browser/WebViews fallen aus dem zugesicherten
Umfang. Der unversionierte `js/jquery.min.js`-Pfad bleibt mit 3.6.0 für eigene
Vorlagen erhalten. Eigene Skripte werden nicht automatisch angepasst;
[Migration, Tests und Rückfall](../docs/JQUERY_MIGRATION.md) beachten.

Die unbenutzten Dateien `mc-calendar/mc-calendar.min.js`,
`css/DateTimePicker1.css` und `css/font-face.css` werden nicht mehr ausgeliefert.
Eigene Templates vor dem Update anhand der
[Asset-Migration](../docs/FRONTEND_ASSET_MIGRATION.md) prüfen.

Auch die historischen Chart.js-3.x-Dateien und ungenutzten Plugin-Kopien wurden
entfernt. Die aktiven Chart.js-Bundles 4.3.3 und 4.4.1 bleiben erhalten; die
Asset-Migration listet die neun zusätzlich entfallenden URLs einzeln auf.

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
