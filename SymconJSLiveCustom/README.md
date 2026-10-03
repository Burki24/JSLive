# SymconJSLiveCustom

Das Custom-Modul stellt eine frei konfigurierbare JSLive-Ausgabe bereit.
Benutzerdefinierte HTML-/JavaScript-Vorlagen können Daten aus Variablen,
Skripten, Medien, Links und Objektbäumen lesen und – sofern freigegeben – Werte
zurückschreiben. Zusätzlich lassen sich eigene JavaScript- und CSS-Bibliotheken
einbinden.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveCustom`-Instanz anlegen und mit dem Splitter verbinden. Eine
Standardvorlage kann über `GenerateDefaultScript()` als untergeordnete Symcon-
Scriptinstanz erzeugt werden. Alternativ wird `TemplateScriptID` auf ein
vertrauenswürdiges eigenes Vorlagenskript gesetzt.

## Konfiguration

| Bereich | Inhalt |
| --- | --- |
| `Datasets` | Objektwurzeln mit Titel, Schreibschutz und Hervorhebungsfarben |
| `Libraries` | Reihenfolge, Typ, Ident, URL, Script oder eingebettete Datei für JS/CSS |
| Vorlage | eigenes Script oder `Default.html` |
| Ausgabe | HTMLBox-/IPSView-Ausgabe, Viewport, IFrame-Höhe, Cache und Aktualisierungsintervall |
| Darstellung | Farben, Alpha-Werte, Schrift und Rahmen |

Bei Datasets werden sichtbare Variablen, direkte Skripte, Medien und Links
ausgewertet. Schreibzugriffe sind nur innerhalb eines konfigurierten Dataset-
Baums und bei deaktiviertem `ReadOnly` zulässig.

## Daten- und Webhook-Befehle

Unterstützt werden die bestehenden JSLive-Befehle:

- `getContend` für die erzeugte Ausgabe;
- `getData` für alle Datasets oder ein einzelnes Objekt;
- `setData` für freigegebene Variablen, Medien und Skriptaktionen;
- `loadFile` für eine konfigurierte Bibliothek;
- `exportConfiguration` für den Konfigurationsexport.

`LoadOtherConfiguration(int $id)` übernimmt die Konfiguration einer anderen
Custom-Instanz desselben Typs.

## Sicherheit

Vorlagen, Bibliotheken und Skripte können JavaScript ausführen. `setData` kann
Symcon-Werte ändern, Medien schreiben oder direkt konfigurierte Skripte
ausführen. Deshalb nur eigene, geprüfte Inhalte und ausdrücklich freigegebene
Objekte verwenden. Bei aktiviertem `Debug` protokolliert die gemeinsame
Diagnose vollständige Browser- und Konfigurationsdaten. Diese können auch
Bibliotheksinhalte sowie gelesene oder geschriebene Werte enthalten; die
Hinweise zum Debug-Betrieb in der zentralen README gelten auch hier.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{784C3E34-F175-98D1-6022-8ADDFAE45CE5}` |
| Prefix | `SymconJSLiveCustom` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
