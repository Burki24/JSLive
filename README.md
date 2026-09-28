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
- Bibliotheksversion: `0.10` (Migrationsstand für die automatische
  Versionierung; noch keine Produktfreigabe), Build 35
- Nicht vom Bot erzeugte Pushes nach `dev` erhalten automatisch eine gemeinsame
  neue Library-Version für alle 13 Module; der Workflow erzeugt keine Releases.
- Zielplattform: IP-Symcon 9.0/9.1 und PHP 8.5
- Lokale Vertrags-, Struktur- und Verhaltenstests sowie StylePHP laufen in der
  GitHub-CI.
- Eine vollständige Laufzeitmatrix auf realen Symcon-9-Installationen und die
  Freigabe als modernisierte stabile Version stehen noch aus.
- Die bestehende Ausgabe über `~HTMLBox` und IPSView bleibt vorerst erhalten.
  Eine native Kacheldarstellung wird später schrittweise ergänzt.

## Module

| Modul | Aufgabe |
| --- | --- |
| [`SymconJSLive`](SymconJSLive) | Splitter, Webhook `/hook/JSLive`, gemeinsame Links, Konfiguration und Assets |
| [`SymconJSLiveAdvTextfield`](SymconJSLiveAdvTextfield) | Erweitertes Textfeld mit HTML-/Skript-Template |
| [`SymconJSLiveCalendar`](SymconJSLiveCalendar) | Kalenderdarstellung und ICS-Quellen |
| [`SymconJSLiveChart`](SymconJSLiveChart) | Linien- und Balkendiagramme mit Archivdaten |
| [`SymconJSLiveColorPicker`](SymconJSLiveColorPicker) | Farbauswahl und Rückschreiben von Werten |
| [`SymconJSLiveCustom`](SymconJSLiveCustom) | Benutzerdefinierte HTML-/JavaScript-Ausgaben und Objektaktionen |
| [`SymconJSLiveDateTimePicker`](SymconJSLiveDateTimePicker) | Datum-/Zeitauswahl und Rückschreiben von Werten |
| [`SymconJSLiveDoughnutPie`](SymconJSLiveDoughnutPie) | Doughnut- und Tortendiagramme |
| [`SymconJSLiveGauge`](SymconJSLiveGauge) | Animierte Messinstrumente |
| [`SymconJSLiveProgressbar`](SymconJSLiveProgressbar) | Konfigurierbare Fortschrittsanzeigen und SVG-Import |
| [`SymconJSLiveRadarChart`](SymconJSLiveRadarChart) | Radardiagramme mit Archivdaten |
| [`SymconJSLiveConfigStore`](SymconJSLiveConfigStore) | Austausch und Import von Modulkonfigurationen über einen externen Dienst |
| [`SymconJSLiveSyncModule`](SymconJSLiveSyncModule) | Synchronisation ausgewählter Konfigurationsparameter zwischen Instanzen |

Die zehn Visualisierungsmodule verwenden den JSLive-Splitter als übergeordnete
Instanz. ConfigStore und SyncModule sind eigenständige Sondermodule.

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

- Die Symcon-9-Laufzeitprüfung aller Module ist noch nicht abgeschlossen.
- ConfigStore und SyncModule deaktivieren derzeit bei ihren externen
  HTTPS-Aufrufen die Zertifikatsprüfung. Diese Netzwerkpfade sind noch nicht für
  einen produktiven Einsatz freigegeben.
- Die Webhook-Authentisierung und sensible Debugausgaben werden in einer
  eigenen Sicherheitsphase überarbeitet. Der Webhook sollte nicht ungeschützt
  öffentlich erreichbar sein.
- Calendar und ColorPicker beziehen noch einzelne Frontend-Ressourcen von
  externen CDNs. Die vollständige lokale und reproduzierbare Auslieferung ist
  geplant.
- Mehrere Frontend-Bibliotheken liegen derzeit in unterschiedlichen Versionen
  im Repository. Aktualisierungen erfolgen erst nach einer Nutzungs- und
  Lizenzinventur.

Weitere technische Details, Risiken und der priorisierte Modernisierungsplan
stehen in [`PROJECT_CONTEXT.md`](PROJECT_CONTEXT.md).

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
