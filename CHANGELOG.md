# Changelog

Alle wesentlichen Aenderungen an JSLive werden in diesem Dokument festgehalten.
Die Library-Version folgt dem Format `Hauptversion.Nebenstand` aus
`library.json`; der dazugehoerige Git-Tag ergaenzt fuer SemVer eine
Patchstelle, beispielsweise `v0.10.0`.

## Unreleased

### Changed

- Die historische Vierkomponenten-Version `0.9.9.9` wurde als einmaliger
  Migrationsschritt auf den gemeinsamen Entwicklungsstand `0.10` ueberfuehrt.
- Changelog, Versionsschema und Release-Ablauf wurden nach dem freigegebenen
  OpenHomeAlarm-Verfahren vereinheitlicht.
- Alle 13 Module und ihre PHP-8.5-Grenzen wurden ohne Aenderung der
  oeffentlichen Vertraege schrittweise auf den gemeinsamen StylePHP-Stand
  gebracht.
- Splitter und Kindmodul-Basisklasse verwenden den zentral synchronisierten
  `DataFlowHelper` fuer ihre bestehenden Datenaustausch-Umschlaege.
- Splitter, SyncModule und Kindmodul-Basisklasse verwenden den zentralen
  `DebugHelper`; sensible Konfigurations-, Webhook- und Nachrichtendaten werden
  strukturiert maskiert und nicht mehr ungefiltert protokolliert.

### Added

- Lokale Struktur-, Vertrags- und Verhaltenstests sichern die charakterisierte
  Bestandsarchitektur, Konfigurationspfade und ausgewaehlte Render-/Datenfluesse.
- Gemeinsame GitHub-Actions-Pruefungen fuer Tests und Style sowie CodeQL fuer
  JavaScript-/TypeScript-Quellen sind eingerichtet.
- Der Metadatenworkflow erhoeht auf `dev` die gemeinsame Library-Version pro
  Quellcommit und erzeugt Build und Datum reproduzierbar aus dessen Git-Daten.
  Ein Regressionstest sichert Berechnung und Rueckwaertsschutz ab.

### Verified

- Repository-Tests, PHP-Syntaxpruefung und repositoryweiter Style-/JSON-Check
  wurden fuer den beschriebenen Stand lokal ausgefuehrt.

Diese Umstellung aendert keine fachliche Modulfunktion und ist noch keine
Produktfreigabe. Der erste modernisierte Release folgt erst nach den
dokumentierten Abnahmen.
