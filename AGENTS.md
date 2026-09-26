# Projektregeln fuer JSLive

Vor Arbeiten in diesem Repository sind zuerst die zentralen Vorgaben aus
`../SymconDevelopment/AGENTS.md` und `../SymconDevelopment/ARCHITECTURE.md`
zu lesen. Die dort referenzierten Prinzipien und Standards gelten auch fuer
JSLive. Projektspezifische Fakten und offene Entscheidungen stehen in
`PROJECT_CONTEXT.md`.

## Projektspezifische Leitplanken

- Zielplattform sind IP-Symcon 9.0/9.1 und PHP 8.5.
- Bestehende Modul-IDs, Praefixe, Data-IDs, Hook-Pfade, Property-Namen,
  Variablen-Idents, oeffentliche PHP-Funktionen und gespeicherte JSON-Strukturen
  sind oeffentliche Vertraege. Aenderungen daran benoetigen eine dokumentierte
  Migration und passende Regressionstests.
- Die bestehende Splitter-/Kindmodul-Architektur wird nicht ohne eine separate
  Architekturentscheidung ersetzt.
- Modernisierungen erfolgen in kleinen, getrennten Schritten. Sicherheits- und
  Kompatibilitaetsarbeiten werden nicht mit fachlichen Erweiterungen vermischt.
- Zentrale Helper werden ueber `.helper-sync.json` und `libs/helper/manifest.json`
  bezogen. Lokale Kopien oder konkurrierende Eigenimplementierungen sind zu
  vermeiden; eine Umstellung muss das bisherige Verhalten absichern.
- Frontend-Abhaengigkeiten sind zu inventarisieren, lokal und reproduzierbar zu
  machen und erst danach einzeln zu aktualisieren.
- Vor Aenderungen sind mindestens `php tests/run.php` und ein PHP-Syntaxcheck
  auszufuehren. Fuer PHP 8.5 gilt der CI-Lauf als verbindliche Zusatzpruefung.
- Commits, Pushes und Merges werden vom Repository-Eigentuemer ausgefuehrt.

