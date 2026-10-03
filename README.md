# JSLive

[![Tests](https://github.com/Burki24/JSLive/actions/workflows/tests.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/tests.yml)
[![Style](https://github.com/Burki24/JSLive/actions/workflows/style.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/style.yml)
[![CodeQL](https://github.com/Burki24/JSLive/actions/workflows/codeql-analysis.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/codeql-analysis.yml)

JSLive ist eine IP-Symcon-Modulbibliothek für browserbasierte Visualisierungen.
Ein zentraler Splitter stellt den Webhook, gemeinsame Konfiguration und statische
Assets bereit. Die Visualisierungsmodule erzeugen daraus HTML-, CSS- und
JavaScript-Ausgaben für IP-Symcon und bestehende IPSView-Installationen.

Der aktuelle Entwicklungsstand unterstützt IP-Symcon 9.0/9.1 und PHP 8.5.
JSLive wird für Symcon 9.0/9.1 stabilisiert: notwendige Ressourcenupdates und
Fehlerkorrekturen werden umgesetzt, eine allgemeine Weiterentwicklung der
Modulsammlung ist nicht geplant. Die öffentlichen Modulverträge und
vorhandenen Installationen bleiben erhalten.

Die zukünftige Chart-Weiterentwicklung erfolgt im separaten Modul
**SymconEcharts** auf Basis von Apache ECharts. Dafür erhält JSLive eine
Exportfunktion für bestehende Charts und SymconEcharts die passende
Übernahmefunktion. Ziel ist ein möglichst nahtloser Umstieg ohne vollständigen
manuellen Neuaufbau. Dieser spezielle Migrationsweg ist angekündigt, noch
nicht implementiert. Nicht automatisch übertragbare Einstellungen müssen
transparent ausgewiesen werden; die Original-Charts bleiben erhalten.
Die bestehende Chart.js-Engine in JSLive wird nicht ausgetauscht.

JSLive bleibt außerdem für Funktionen bestehen, die nicht durch ECharts
ersetzt werden, beispielsweise den Colorpicker. Diese werden weiter gepflegt.
Wird die Entwicklung ihrer externen Ressourcen eingestellt, werden geeignete
Alternativen gesucht und eingebaut oder eigene Lösungen entwickelt.
Siehe [Wartungs- und Migrationsentscheidung](docs/adr/0006-maintenance-scope.md).

## Projektstatus

- Entwicklungszweig: `dev`
- Die Bibliotheksversion und der Build werden auf `dev` automatisch aus dem
  jeweiligen Quellcommit erzeugt. Die Felder in `library.json` werden nicht
  manuell gepflegt; der aktuelle Entwicklungsstand ist noch keine
  Produktfreigabe.
- Nicht vom Bot erzeugte Pushes nach `dev` erhalten automatisch eine gemeinsame
  neue Library-Version für alle enthaltenen Module; der Workflow erzeugt keine
  Releases.
- Zielplattform und deklarierte Mindestversion: IP-Symcon 9.0/9.1 und PHP 8.5
- Lokale Vertrags-, Struktur- und Verhaltenstests sowie StylePHP laufen in der
  GitHub-CI.
- Die [Laufzeitmatrix für IP-Symcon 9 und PHP 8.5](docs/SYCON_RUNTIME_MATRIX.md)
  wird auf der aktuellen, über den Symcon-MCP erreichbaren Testebene
  ausgeführt. Die Baseline unter IP-Symcon 9.1 und PHP 8.5.8 ist bestanden;
  die Freigabe als modernisierte stabile Version steht noch aus.
- Die bestehende Ausgabe über `~HTMLBox` und IPSView bleibt erhalten.
  [Ausgabe-, IPSView- und Link-Verträge](docs/VISUALIZATION_OUTPUT_CONTRACTS.md)
  sind durch lokale Charakterisierungstests abgesichert. Die bisher geplante
  native Kachelmigration ist nicht mehr Teil des aktiven JSLive-Plans.
- Die Standardvorlagen verwenden jQuery 4.0.0 und setzen aktuelle Browser bzw.
  WebViews voraus. Ältere Clients werden nicht mehr zugesichert. Der alte
  jQuery-Pfad bleibt für eigene Vorlagen erhalten; siehe
  [Migration und Prüfgrenzen](docs/JQUERY_MIGRATION.md).
- Splitter und Visualisierungsmodule sind im aktuellen Arbeitsstand koordiniert
  auf `IPSModuleStrict`, automatische Parent-Kompatibilität und die native
  Hook-API umgestellt. Die abschließende MCP-CURRENT-Laufzeitabnahme dieses
  Migrationsstands einschließlich Dienstneustart ist bestanden.

## Module

| Modul | Aufgabe |
| --- | --- |
| [`SymconJSLive`](SymconJSLive) | Splitter, Webhook `/hook/JSLive`, gemeinsame Links, Konfiguration und Assets |
| [`SymconJSLiveAdvTextfield`](SymconJSLiveAdvTextfield) | Erweitertes Textfeld mit HTML-/Skript-Template |
| [`SymconJSLiveChart`](SymconJSLiveChart) | Linien- und Balkendiagramme mit Archivdaten |
| [`SymconJSLiveColorPicker`](SymconJSLiveColorPicker) | Farbauswahl und Rückschreiben von Werten |
| [`SymconJSLiveCustom`](SymconJSLiveCustom) | Benutzerdefinierte HTML-/JavaScript-Ausgaben und Objektaktionen |
| [`SymconJSLiveDateTimePicker`](SymconJSLiveDateTimePicker) | Datum-/Zeitauswahl und Rückschreiben von Werten |
| [`SymconJSLiveDoughnutPie`](SymconJSLiveDoughnutPie) | Doughnut- und Tortendiagramme |
| [`SymconJSLiveGauge`](SymconJSLiveGauge) | Animierte Messinstrumente |
| [`SymconJSLiveProgressbar`](SymconJSLiveProgressbar) | Konfigurierbare Fortschrittsanzeigen und SVG-Import |
| [`SymconJSLiveRadarChart`](SymconJSLiveRadarChart) | Radardiagramme mit Archivdaten |

Die neun Visualisierungsmodule verwenden den JSLive-Splitter als übergeordnete
Instanz.

## Installation und Verwendung

Für Entwicklungs- und Testinstallationen kann das Repository
`https://github.com/Burki24/JSLive` in der IP-Symcon-Modulverwaltung eingebunden
und der Zweig `dev` gewählt werden.

Für ein Visualisierungsmodul wird eine JSLive-Splitterinstanz benötigt. Beim
Anlegen eines Kindmoduls kann IP-Symcon die fehlende Splitterinstanz automatisch
erzeugen. Im Splitter werden insbesondere die erreichbare Adresse und ein
Webhook-Kennwort konfiguriert. Die erzeugten Links beziehungsweise
Ausgabevariablen können anschließend in der Visualisierung oder in IPSView
verwendet werden.

Die detaillierte Modulkonfiguration ist in den jeweiligen Modulverzeichnissen
dokumentiert. Diese Dokumentation wird im Zuge der Modernisierung schrittweise
überarbeitet.

Die Option `Debug` ist standardmäßig ausgeschaltet. Wird sie aktiviert,
protokollieren Splitter und Module vollständige Browser-, Konfigurations- und
Austauschdaten, auch freie Texte, Skripte und Medien. Bekannte Zugangsdatenfelder
werden maskiert; beliebig im Inhalt versteckte Geheimnisse können nicht sicher
erkannt werden. Debug daher nur in einer geschützten Testumgebung verwenden.

## Bestehender Konfigurationsexport

Die Exportkorrektur auf Basis von 0.94 erhält Format und öffentliche
Funktionssignaturen. Sie ändert folgende bisher fehlerhafte Verhaltensweisen:

- Template- und Custom-Bibliotheksskripte werden nur mit `scripts=1`
  mitgeliefert (numerische Werte ab 1 werden akzeptiert). Ohne Parameter oder
  mit `scripts=0` werden diese zusätzlichen Skriptinhalte nicht exportiert.
  Konfigurierte IDs, URLs und freie Inhalte in `Config` bleiben davon unberührt;
  dies ist keine vollständige Anonymisierung des Exports.
- Die normalen Export-Schaltflächen fordern Skripte weiterhin ausdrücklich
  an. Im Custom-Modul entscheidet die vorhandene Skriptauswahl.
- Eigene PHP-Aufrufe müssen Skripte ausdrücklich anfordern, zum Beispiel mit
  `SymconJSLiveChart_ExportConfiguration($id, false, ['scripts' => 1])`.
  `GetConfigurationLink($id, true)` erzeugt weiterhin einen Link mit Skripten;
  `false` erzeugt einen ohne. Bereits heruntergeladene Dateien ändern sich nicht.
- Der gefilterte Export lässt mit `ignoreExport` markierte Felder jetzt auch
  in Listenspalten aus. Beim Chart betrifft dies neben dem Titel die
  Variablenbindung der Datensätze. Vorhandene Zielbindungen bleiben beim Import
  in passende bestehende Zeilen erhalten; fehlende Bindungen sind neu zuzuordnen.
- Für einen vollständigen Konfigurationsexport über PHP bleibt
  `ExportConfiguration($id, true, ...)` verfügbar. Er erhält auch die
  Datenbezüge; die Skriptauswahl ist davon unabhängig.

Gespeicherte Instanzen, Quelldaten und Archivhistorien werden durch den Export
nicht verändert. Es entsteht dadurch noch kein SymconEcharts-Migrationsformat.
Nach Commit, grüner CI und Modulupdate ist die installierte Exportfunktion zu
prüfen; ein zusätzlicher `ApplyChanges()`-Aufruf oder geplanter Dienstneustart
ist für diese Korrektur nicht erforderlich.

## Bekannte Einschränkungen

- Der nicht mehr erreichbare, vollständig von einem externen Dienst abhängige
  ConfigStore wurde entfernt. Vorhandene ConfigStore-Instanzen müssen vor einem
  Update auf diesen Stand gelöscht werden.
- Das experimentelle SyncModule wurde ebenfalls entfernt. Vorhandene
  SyncModule-Instanzen müssen vor einem Update auf diesen Stand gelöscht
  werden. Seine Modul-ID wird nicht wiederverwendet; die früher geplante lokale
  Konfigurationsverteilung ist nicht mehr Teil des aktiven JSLive-Plans.
- Das Calendar-Modul wurde entfernt. Vorhandene Calendar-Instanzen müssen vor
  einem Update auf diesen Stand gelöscht werden. Seine Modul-ID und sein Präfix
  werden nicht wiederverwendet.
- Das bestehende [Webhook-Sicherheitsmodell](docs/WEBHOOK_SECURITY_MODEL.md)
  ist dokumentiert und durch Vertragstests charakterisiert. Es verwendet
  weiterhin Kennwörter in URLs, Wildcard-CORS und GET für Schreibzugriffe; der
  Webhook sollte deshalb nicht ungeschützt öffentlich erreichbar sein.
- Mehrere Frontend-Bibliotheken liegen derzeit in unterschiedlichen Versionen
  im Repository. Aktualisierungen erfolgen erst nach der dokumentierten
  Nutzungs-, Quellen- und Lizenzinventur. Die von den mitgelieferten Templates
  benötigten iro.js- und Font-Ressourcen werden bereits lokal ausgeliefert.

Weitere technische Details, Risiken und der priorisierte Wartungsplan
stehen in [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md). Die
[`IPSModuleStrict`-Migration](docs/STRICT_MODULE_MIGRATION.md) dokumentiert die
implementierten Signaturen, Kompatibilitätsgrenzen und die bestandene
Laufzeitabnahme.

## Entwicklung und Prüfungen

Die lokale Mindestprüfung besteht aus:

```text
php tests/run.php
php-cs-fixer fix --dry-run --using-cache=no --allow-risky=yes --config=.style/.php-cs-fixer.php
php .style/json-check.php
```

GitHub Actions führt zusätzlich die gemeinsamen Prüfungen aus
`Burki24/Symcon_ModuleCI` mit PHP 8.5 sowie CodeQL für die
JavaScript-/TypeScript-Quellen aus.

Wesentliche Änderungen werden im [Changelog](CHANGELOG.md) festgehalten. Die
Freigabe von `dev` nach `main`, Tagging und Rücksynchronisierung sind im
[Release-Prozess](docs/RELEASE_PROCESS.md) beschrieben.

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](LICENSE).
