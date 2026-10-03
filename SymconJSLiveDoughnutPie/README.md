# SymconJSLiveDoughnutPie

Das Modul erzeugt animierte Ring- (`doughnut`) oder Kreisdiagramme (`pie`) für
die JSLive-Weboberfläche. Die Segmente werden aus konfigurierten Symcon-
Variablen gebildet und können mit eigenen Farben, Labels und Tooltips
dargestellt werden.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- eine JSLive-Splitterinstanz (`SymconJSLive`)

Eine `SymconJSLiveDoughnutPie`-Instanz anlegen, `Datasets` konfigurieren und
mit dem Splitter verbinden. Dieser kann beim Anlegen automatisch erzeugt
werden.

## Konfiguration

| Bereich | Eigenschaften |
| --- | --- |
| Diagramm | `type` (`doughnut` oder `pie`), Rotation und optionales Seitenverhältnis `Ratio` |
| Datensätze | Variablen, Reihenfolge, Titel, Farben, Rahmen und Datalabels in `Datasets` |
| Darstellung | Titel, Legende, Tooltips, Animation und Rundungspräzision |
| Ausgabe | HTMLBox/IPSView, eigene Vorlage über `TemplateScriptID`, Viewport und IFrame-Größe |
| Betrieb | Browser-Cache und Debug |

## Daten- und Webhook-Befehle

Der bestehende Vertrag umfasst `getContend`, `getData` und
`exportConfiguration`. `GetUpdate()` liefert die aktualisierten Werte;
`LoadOtherConfiguration(int $id)` übernimmt eine andere Doughnut-/Pie-
Konfiguration.

## Frontend und Sicherheit

Die Standardvorlage ist `Doughnut-PIE.html`. Chart.js, Moment und das
Datalabels-Plugin werden aus dem JSLive-Bestand ausgeliefert. Eigene Vorlagen
werden als HTML/JavaScript ausgeführt und dürfen nur aus vertrauenswürdigen
Quellen stammen. Bei aktiviertem `Debug` werden vollständige Browser-Abfragen
über die gemeinsame Diagnose protokolliert; bekannte Zugangsdatenfelder werden
maskiert.

Die Standardvorlage verwendet die lokal versionierte Chart.js 4.5.1.
Moment.js wird lokal als 2.31.0 geladen; der alte 2.27.0-Pfad bleibt für eigene
Vorlagen erhalten. Der Moment-Adapter wird als 1.0.1 lokal versioniert geladen;
sein alter 1.0.0-Pfad bleibt ebenfalls erhalten. Datalabels 2.2.0 wird aus der
unveränderten offiziellen Distribution im versionierten Pfad geladen; der
historisch modifizierte Datalabels-Pfad bleibt für eigene Vorlagen erhalten.
Die alten URLs mit 4.3.3 und 4.4.1 bleiben für eigene Vorlagen unverändert.
Für eigene Vorlagen mit historischen
Chart.js-3.x-/Plugin-Pfaden gilt die
[Asset-Migration](../docs/FRONTEND_ASSET_MIGRATION.md).

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{9419245E-CE2E-F949-AAB6-714E2045632F}` |
| Prefix | `SymconJSLiveDoughnutPie` |
| Parent-Anforderung | `{751AABD7-E31D-024C-5CC0-82AC15B84095}` |
| Kindmodul-Schnittstelle | `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}` |

## Konfigurationsexport

Skriptauswahl, gefilterter/vollständiger Export und Hinweise für eigene Aufrufe:
[gemeinsamer Exportvertrag](../README.md#bestehender-konfigurationsexport).

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
