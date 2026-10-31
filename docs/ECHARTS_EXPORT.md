# Vollständiger Export für SymconECharts

Der neue Producer erzeugt `de.burki24.jslive-echarts` 1.0 für Chart,
DoughnutPie, Gauge, RadarChart und Progressbar. Er ist vom bisherigen
Konfigurationsexport getrennt. Dieser bleibt einschließlich Filterung und
Skriptauswahl erhalten.

## Schnittstellen und Erfassung

- `ExportECharts(bool $includeScripts = false): string` ist die neue öffentliche
  Kindmodul-Methode. Ein Aufruf erfasst genau ein vollständiges Chart.
- `GetEChartsExportLink(bool $includeScripts = false): string` liefert einen
  Downloadlink für `/hook/JSLive/exportECharts` mit dem bestehenden
  Authentifizierungsmodell. Links können Zugangsdaten enthalten und werden
  nicht in das Paket übernommen.
- Die fünf Formulare bieten einen getrennten Exportbereich mit ausdrücklicher
  Auswahl für eigene Vorlageninhalte. Der Link lädt eine `.jslive-echarts.json`.
- `GetMigrationInstallationID(): string` am Splitter ist ausschließlich lesend.
  Ein neues internes Attribut wird im normalen Modul-Lebenszyklus unter Sperre
  initialisiert. Weitere Splitter übernehmen dieselbe Kennung. Konflikte
  überschreiben keine Kennung und unterbrechen die bisherigen Visualisierungen
  nicht; der neue Export bleibt dann gesperrt.

Der Producer liest Originalkonfiguration, Idents/Steuerwerte, Bindungen,
Achsen-/Ringprofile und native Quelldarstellungen. Die bereits vorhandenen
privaten Modellfunktionen liefern über einen festen internen Methodensatz die
wirksamen Titel-/Legenden-/Tooltip-/Skalenwerte und die originale Zeitplanung.
Die Methodennamen sind keine benutzersteuerbare Ausführungsschnittstelle.
Roharchive werden beim Export nicht geladen oder verändert. Originale Roh- und
Aggregatregeln einschließlich Zählerdifferenzen, Ausdünnung, Ersatzlinien und
Radarreduktion werden separat benannt. `reference` bleibt optional null.

Standardvorlagen werden unverändert als inerte Textressourcen mit Hash und
Herkunft beigelegt, nicht als neue Runtime ausgeführt. Lokale Schrift-CSS und
deren Schriftdateien sowie SVG-/Pfadinhalte werden eingebunden. Eigene
Template-Skripte nur nach ausdrücklicher Auswahl. Externe Abhängigkeiten werden
gemeldet, nie automatisch geladen. Das Paket ist kein Archivbackup.

## Darstellungen und Fehler

Die gemeinsame Quelle ist `Symcon_ModuleHelper/src/VariablePresentationNormalizationHelper.php`.
JSLive und SymconECharts abonnieren denselben Helper. Er erhält vollständige
Originaldefinitionen, native Parameter und eine Konvertierungsbilanz. Unterstützt
sind native Wertanzeige, Slider und Enumeration sowie gewöhnliche numerische
Legacy-Profile, Boolean-/String-Zustände und konstante numerische Assoziationen.
Numerische Assoziationen folgen den dokumentierten Schwellenintervallen.

Native Prozentformatierung bleibt von JSLives bloßer Suffixverwendung getrennt.
Schrittweiten werden bei lesenden Darstellungen als Metadaten erhalten. Es
werden weder neue Actions noch Darstellungen an bestehenden Quellen gesetzt.

Dynamische Assoziationstexte mit printf-Platzhaltern, spezielle Legacy-Profile
wie Zeit-/Farb-/HTML-Profile, unbekannte native Darstellungen/Parameter und die
Kombination nativer Prozentnormalisierung mit Assoziationsintervallen werden
bewusst als `blocked` mit vollständigem Original erhalten. Ein Paket mit solchen
Problemen ist ein Diagnoseexport, keine Zusage vollständiger Übernehmbarkeit.
Fehlende Quellen/Profile, Ressourcen und nicht auflösbare Modelle erscheinen
ebenfalls in `issues`. Defekte oder übergroße Hüllen brechen vor Ausgabe ab.

`coverage` beschreibt derzeit die vollständige Erhaltung der Originalfelder
beim **Export**. Ein späterer Importer muss Zielabbildung und Darstellungstreue
unabhängig prüfen. Die Source-Pakete beweisen weder einen vorhandenen Importer
noch die Gleichheit von Zielbildern.

## Verteilung und Abnahme

Der Helper muss zuerst aus dem zentralen Repository regulär verteilt werden.
Die Consumer-Subscriptions sind vorbereitet. Fehlender Helper erzeugt beim
neuen Export eine eindeutige Voraussetzungsmeldung; alte Chart-/Exportpfade
benötigen ihn nicht. Lokale Testbundles werden vor Übergabe wieder entfernt.
Erst nach Verteilung und Pull werden die fachlichen Consumer-Änderungen committet.

Die Verteilung liest die **remote** vorhandenen Subscriptions. Deshalb müssen
die neuen Abonnements vor dem eigentlichen Sync auf `dev` liegen:

1. Zentrale Helper-Änderung nach dem Eigentümerverfahren freigeben. JSLive ist
   zusätzlich in `.github/helper-consumers.json` für `dev` registriert; dieser
   Consumer fehlte bislang im tatsächlichen Verteilungsregister.
2. Als getrennte Abhängigkeitsvorbereitung in JSLive nur `.helper-sync.json`
   und in SymconECharts `.helper-sync.json`, `tests/validate_structure.php` und
   `tests/helper_integrity.py` committen/pushen. Der Zwischenstand ist noch nicht
   lauffähig für die neue Funktion; die Integritätsprüfung meldet bis zur Lieferung
   die fehlende Abhängigkeit. Kein Release oder Modulupdate dieses Zwischenstands.
3. Den vorhandenen Helper-Sync nach Bereitstellung der Subscriptions für
   `VariablePresentationNormalizationHelper` ausführen lassen beziehungsweise
   regulär über `workflow_dispatch` anstoßen. Die ursprüngliche automatische
   Verteilung sieht Abonnements nicht, die erst nach ihrem Lauf gepusht wurden.
4. Die regulären Bundle-Commits in beide Consumer pullen und deren Integrität
   prüfen. Erst dann die übrigen fachlichen Änderungen dieser Libraries
   committen/pushen. Keine lokalen Testkopien oder Test-Hashmanifeste einchecken.

Die Arbeitspaket-Prüfung verwendet ein temporär über den bestehenden Bundler
erzeugtes, unveröffentlichtes Paket; vor Übergabe werden die ursprünglichen
Consumer-Manifeste und -READMEs wiederhergestellt und nur die neue Testdatei
entfernt. Fachliche Änderungen und neue Subscriptions bleiben erhalten. Der
ungebündelte Arbeitsbaum ist daher noch kein abschließend integrierter Consumer-
Stand. Eine erneute Integritäts-/Migrationstestprüfung nach dem echten Pull ist
erforderlich. Commit, Push, Merge und das Auslösen der Verteilung übernimmt
ausschließlich der Eigentümer.

Für die vorbereitenden Abo-Commits in beiden Consumern kann derselbe Betreff
verwendet werden; die fachlichen Export-/Snapshot-Änderungen folgen erst nach
der regulären Lieferung:

```text
CHORE: Subscribe to shared presentation normalization
```

```text
Subscribe to VariablePresentationNormalizationHelper and align the required
helper inventory checks. Prepare regular bundle delivery before committing
the migration runtime changes.
```

Lokale Tests: `php tests/echarts-export.php` prüft echte öffentliche Einstiegspunkte
aller fünf Familien, originale Zeitplanung, wiederholte Bindungen, gleiche
Quellnamen, unveränderte Konfiguration, native Schnappschüsse, Ressourcen und
Template-Opt-in. `php tests/echarts-origin.php` prüft Kennung, Wiederholung und
Konflikte über zwei Splitter. Beide sind Teil von `php tests/run.php`.
Die bisherigen 20 Exportfälle und 108 ReceiveData-Fälle bleiben aktiv.

Offen sind der reale Formular-/HTTP-Download auf Symcon nach Helper-Verteilung
und Modulupdate, die übrigen Konvertierungsregeln, neue Ziel-Fachmodelle,
ECharts-Importer und die gemeinsame Migrationsabnahme. Die bestehenden
Kachel-/IPSView-Bestandsabnahmen bleiben unverändert gültig.
