# JSLive

[![Tests](https://github.com/Burki24/JSLive/actions/workflows/tests.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/tests.yml)
[![Style](https://github.com/Burki24/JSLive/actions/workflows/style.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/style.yml)
[![CodeQL](https://github.com/Burki24/JSLive/actions/workflows/codeql-analysis.yml/badge.svg?branch=dev)](https://github.com/Burki24/JSLive/actions/workflows/codeql-analysis.yml)

JSLive ist eine IP-Symcon-Modulbibliothek für browserbasierte Visualisierungen.
Ein zentraler Splitter stellt den Webhook, gemeinsame Konfiguration und statische
Assets bereit. Die Visualisierungsmodule erzeugen daraus HTML-, CSS- und
JavaScript-Ausgaben für IP-Symcon und bestehende IPSView-Installationen.

Das Bestandsprojekt wird derzeit auf IP-Symcon 9.0/9.1 und PHP 8.5 vorbereitet.
Die öffentlichen Modulverträge und vorhandenen Installationen sollen während
der Modernisierung kompatibel bleiben.

## Projektstatus

- Entwicklungszweig: `dev`
- Die Bibliotheksversion und der Build werden auf `dev` automatisch aus dem
  jeweiligen Quellcommit erzeugt. Die Felder in `library.json` werden nicht
  manuell gepflegt; der aktuelle Entwicklungsstand ist noch keine
  Produktfreigabe.
- Nicht vom Bot erzeugte Pushes nach `dev` erhalten automatisch eine gemeinsame
  neue Library-Version für alle enthaltenen Module; der Workflow erzeugt keine
  Releases.
- Zielplattform: IP-Symcon 9.0/9.1 und PHP 8.5
- Lokale Vertrags-, Struktur- und Verhaltenstests sowie StylePHP laufen in der
  GitHub-CI.
- Die [Laufzeitmatrix für IP-Symcon 9 und PHP 8.5](docs/SYCON_RUNTIME_MATRIX.md)
  wird auf der aktuellen, über den Symcon-MCP erreichbaren Testebene
  ausgeführt. Die Baseline unter IP-Symcon 9.1 und PHP 8.5.8 ist bestanden;
  die Freigabe als modernisierte stabile Version steht noch aus.
- Die bestehende Ausgabe über `~HTMLBox` und IPSView bleibt vorerst erhalten.
  Eine native Kacheldarstellung wird später schrittweise ergänzt.
- Splitter und Visualisierungsmodule sind im aktuellen Arbeitsstand koordiniert
  auf `IPSModuleStrict`, automatische Parent-Kompatibilität und die native
  Hook-API umgestellt. Die abschließende MCP-CURRENT-Laufzeitabnahme dieses
  Migrationsstands steht noch aus.

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

## Bekannte Einschränkungen

- Die vor der Strict-Migration bestandene
  [Symcon-9-Laufzeitmatrix](docs/SYCON_RUNTIME_MATRIX.md) muss nach Installation
  des aktuellen Migrationsstands erneut vollständig ausgeführt werden.
- Der nicht mehr erreichbare, vollständig von einem externen Dienst abhängige
  ConfigStore wurde entfernt. Vorhandene ConfigStore-Instanzen müssen vor einem
  Update auf diesen Stand gelöscht werden.
- Das experimentelle SyncModule wurde ebenfalls entfernt. Vorhandene
  SyncModule-Instanzen müssen vor einem Update auf diesen Stand gelöscht
  werden. Seine Modul-ID wird nicht wiederverwendet; eine spätere lokale
  Verteilung von Konfigurationen wird als neue, getrennte Funktion entwickelt.
- Das Calendar-Modul wurde entfernt. Vorhandene Calendar-Instanzen müssen vor
  einem Update auf diesen Stand gelöscht werden. Seine Modul-ID und sein Präfix
  werden nicht wiederverwendet.
- Das bestehende [Webhook-Sicherheitsmodell](docs/WEBHOOK_SECURITY_MODEL.md)
  ist dokumentiert und durch Vertragstests charakterisiert. Es verwendet
  weiterhin Kennwörter in URLs, Wildcard-CORS und GET für Schreibzugriffe; der
  Webhook sollte deshalb nicht ungeschützt öffentlich erreichbar sein.
- ColorPicker bezieht noch eine Frontend-Ressource von einem externen CDN. Die
  vollständige lokale und reproduzierbare Auslieferung ist geplant.
- Mehrere Frontend-Bibliotheken liegen derzeit in unterschiedlichen Versionen
  im Repository. Aktualisierungen erfolgen erst nach einer Nutzungs- und
  Lizenzinventur.

Weitere technische Details, Risiken und der priorisierte Modernisierungsplan
stehen in [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md). Die
[`IPSModuleStrict`-Migration](docs/STRICT_MODULE_MIGRATION.md) dokumentiert die
implementierten Signaturen, Kompatibilitätsgrenzen und die noch ausstehende
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
