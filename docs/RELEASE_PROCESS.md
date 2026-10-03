# Release-Prozess

Dieses Verfahren erstellt Releases aus einem eindeutig geprueften Commit. Es
gilt gemeinsam fuer alle enthaltenen Module der JSLive-Library; einzelne Module
erhalten keine voneinander abweichenden Versionen.

## Versionsschema

- Die Symcon-Library-Version ist der Wert aus `library.json`, beispielsweise
  `0.10`.
- Der Git-Tag ergaenzt die SemVer-Patchstelle: `v0.10.0`.
- Tag, Release-Titel, `library.json` und Changelog muessen dieselbe Version
  nennen.
- Die Metadatenautomatik aktualisiert auf `dev` Version, Build und Datum. Ihr
  Lauf muss abgeschlossen sein, bevor der endgueltige Kandidaten-Commit
  gewaehlt wird.

## Kandidat vorbereiten

1. Den Abschnitt **Unreleased** im `CHANGELOG.md` vervollstaendigen.
2. Lokale Tests, Style-Pruefung und alle fuer den Aenderungsumfang notwendigen
   Symcon-9-Abnahmen ausfuehren.
3. Den Metadaten-Bot auf `dev` abwarten und den exakten Kandidaten-Commit
   festhalten.
4. **Unreleased** auf die erwartete Library-Version nach dem abschliessenden
   Dokumentationscommit und das geplante Veroeffentlichungsdatum umstellen.
   Auch Dokumentationscommits erhoehen den Nebenstand: bei genau einem
   weiteren Commit auf Basis von `0.103` ist daher `0.104` vorzubereiten,
   nicht erneut `0.103`. Bis zur Pruefung nach dem Botlauf als Kandidat
   kennzeichnen; `library.json` nicht manuell anpassen.
5. Die Dokumentationsaenderung committen, erneut den Metadaten-Bot abwarten und
   fuer exakt den resultierenden Commit die Pflichtchecks `tests`, `style` und
   `CodeQL` pruefen.
   Changelog-Version und `library.json` muessen jetzt uebereinstimmen. Weitere
   Commits oder ein anderes Veroeffentlichungsdatum erfordern einen erneuten
   Abgleich. Bei unveraenderten Angaben keinen weiteren reinen
   Bestaetigungscommit erzeugen; Commit und CI-Nachweis fuer die Freigabe im
   Pull Request festhalten.
6. Den geprueften `dev`-Stand per Pull Request kontrolliert nach `main`
   uebernehmen.
7. Auf dem unveraenderten `main`-Commit dieselben Pflichtchecks erneut pruefen.

## Veroeffentlichung

1. Den signierten oder annotierten Tag `v<Library-Version>.0` exakt auf dem
   geprueften `main`-Commit erstellen.
2. Den Tag pushen und einen GitHub Release mit dem entsprechenden
   Changelog-Text anlegen.
3. Installation und Update ueber den veroeffentlichten Stand stichprobenartig
   pruefen.
4. Falls der Symcon Module Store verwendet wird, exakt diesen Commit fuer den
   vorgesehenen Kanal einreichen.

Die Metadatenautomatik erzeugt selbst weder Tags noch Releases. Kein Tag wird
verschoben und kein veroeffentlichter Release ueberschrieben. Eine Korrektur
erhaelt eine neue Library-Version und einen neuen Tag.

## Ruecksynchronisierung

Nach jedem Release wird das vollstaendige Merge-Ergebnis aus `main`
einschliesslich des Merge-Commits wieder nach `dev` uebernommen. Erst danach
beginnt die naechste Entwicklung. Der dadurch ausgeloeste Metadatenlauf eroeffnet
den neuen Entwicklungsstand auf `dev`; Konflikte in `CHANGELOG.md`,
`library.json` und synchronisierten Helper-Manifesten muessen kumulativ geloest
werden.
