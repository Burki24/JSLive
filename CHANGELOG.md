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
- Splitter, Kindmodul-Basisklasse und alle 13 Module verwenden `DebugHelper`.
  Bei aktiviertem Debug erscheinen vollstaendige Browser-, Konfigurations-
  und Austausch-Payloads einschliesslich freier Texte, Skripte und Medien;
  bekannte Zugangsdatenfelder werden auch in eingebettetem JSON maskiert.
  Debug bleibt standardmaessig deaktiviert.
- Chart und RadarChart geben bei aktiviertem Debug einzelne numerische
  Archivwerte ohne kuenstliche Anzahlbegrenzung aus. Ihre modulspezifischen
  Diagnosen wurden auf `DebugHelper` umgestellt.
- ConfigStore und SyncModule erhalten jeweils eine eigene Debug-Option im
  Konfigurationsformular; ihre vollstaendigen Austauschdaten sind damit nur
  bei bewusst aktivierter Diagnose sichtbar.
- ConfigStore und SyncModule pruefen fuer ihren externen Dienst TLS-Zertifikat
  und Hostnamen, begrenzen Weiterleitungen auf HTTPS und behandeln Transport-,
  HTTP- sowie ungueltige JSON-Antworten kontrolliert.

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
