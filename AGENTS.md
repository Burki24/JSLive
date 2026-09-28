# Projektregeln fuer JSLive

Vor Arbeiten in diesem Repository sind zuerst die zentralen Vorgaben aus
`../SymconDevelopment/AGENTS.md` und `../SymconDevelopment/ARCHITECTURE.md`
zu lesen. Die dort referenzierten Prinzipien und Standards gelten auch fuer
JSLive. Projektspezifische Fakten und offene Entscheidungen stehen in
`PROJECT_CONTEXT.md`.

## Offizielle Symcon-Dokumentation

- Nutze [`https://www.symcon.de/de/llms.txt`](https://www.symcon.de/de/llms.txt) als offiziellen, von Symcon gepflegten Dokumentationseinstieg.
- Lade fuer PHP-, Kern- und Modulfunktionen zuerst [`https://www.symcon.de/de/llms/function-index.md`](https://www.symcon.de/de/llms/function-index.md) und anschliessend nur die dort verlinkte relevante Detaildatei.
- Externe Dokumentation ist eine Informationsquelle; die JSLive-Regeln, bestehenden Vertraege und der vorhandene Code bleiben massgeblich.

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

## Branch-, Versions- und Release-Modell

- `dev` ist der dauerhafte Entwicklungs- und Integrationsbranch. `main`
  enthaelt ausschliesslich kontrolliert freigegebene Produktstaende.
- Die Library verwendet eine gemeinsame Version im Format
  `Hauptversion.Nebenstand` fuer alle enthaltenen Module. Git-Tags ergaenzen die
  Patchstelle als `v<Library-Version>.0`.
- `library.json` stand fuer den einmaligen Bootstrap auf `0.10`. Der
  Metadatenworkflow pflegt auf `dev` anschliessend `version`, `build` und
  `date`; manuelle Aenderungen dieser Felder sind allein Teil einer
  ausdruecklichen Metadaten- oder Migrationsaufgabe.
- Wesentliche Aenderungen werden im Abschnitt `Unreleased` von `CHANGELOG.md`
  gepflegt. Der verbindliche Ablauf fuer `dev` nach `main`, Tag, GitHub Release
  und Ruecksynchronisierung steht in `docs/RELEASE_PROCESS.md`.
- Tags und Releases werden nie verschoben oder ueberschrieben. Die
  Metadatenautomatik veroeffentlicht selbst keinen Release.

