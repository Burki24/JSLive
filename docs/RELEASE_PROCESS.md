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

## Kanal- und Branch-Regel

Die offizielle Beta wird aus einem exakt geprueften `dev`-Commit veroeffentlicht.
`main` bleibt waehrend der Community-Testphase unveraendert. Erst wenn keine
offenen Fehlermeldungen aus dieser Phase vorliegen und der Eigentuemer die
stabile Freigabe ausdruecklich erteilt, folgt die Uebernahme nach `main`.
Ausbleibende Meldungen allein ersetzen weder Pflichtchecks noch Freigabe.
Eine Beta-Einreichung bezieht sich auf eine feste Commit-ID, nicht auf den
spaeter beweglichen Branch-Kopf.

## Kandidat vorbereiten

1. Den Abschnitt **Unreleased** im `CHANGELOG.md` vervollstaendigen.
2. Lokale Tests, Style-Pruefung und alle fuer den Aenderungsumfang notwendigen
   Symcon-9-Abnahmen ausfuehren.
3. Den Metadaten-Bot auf `dev` abwarten und den exakten Kandidaten-Commit
   festhalten.
4. Die fertigen **Unreleased**-Eintraege in einen Abschnitt fuer die erwartete
   Library-Version nach dem abschliessenden Dokumentationscommit und das
   geplante Veroeffentlichungsdatum uebernehmen. Die Ueberschrift **Unreleased**
   fuer weitere Aenderungen stehen lassen; die Strukturpruefung verlangt sie.
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
   Bestaetigungscommit erzeugen; Commit und CI-Nachweis fuer die Beta-
   Einreichung festhalten, spaeter auch im Stable-Pull-Request.

## Beta veroeffentlichen

1. Den signierten oder annotierten Tag `v<Library-Version>.0` exakt auf dem
   geprueften `dev`-Commit erstellen.
2. Den Tag pushen und einen GitHub Release als **Pre-release** mit dem
   entsprechenden Changelog-Text anlegen; keine Stable-Veroeffentlichung.
3. Installation und Update ueber den veroeffentlichten Stand stichprobenartig
   pruefen.
4. Falls der Symcon Module Store verwendet wird, exakt diesen `dev`-Commit
   fuer den Kanal **Beta** einreichen. Kein vorheriger Merge nach `main`.
5. Community-Rueckmeldungen dem Beta-Stand zuordnen. Fehler auf `dev` beheben,
   testen und erforderlichenfalls eine neue Beta mit eigener Version
   veroeffentlichen. Einreichung, Tags und Releases bleiben Eigentuemerschritte.

## Nach der Community-Testphase: Stable

1. Ohne offene Community-Fehlermeldungen die ausdrueckliche Stable-Freigabe des
   Eigentuemers einholen; dokumentierte Pruefgrenzen und Pflichtchecks bleiben
   massgeblich. Den ausgetesteten Stand benennen, nicht ungeprueft den neuesten
   `dev`-Kopf uebernehmen.
2. Einen eigenen Stable-Kandidaten mit neuer Library-Version und Changelog
   auf `dev` vorbereiten. Damit muss kein Beta-Tag verschoben oder Beta-Release
   ueberschrieben werden. Metadatenlauf und Pflichtchecks wie oben abwarten.
3. Den geprueften Stand per Pull Request kontrolliert nach `main` uebernehmen
   und auf dem resultierenden `main`-Commit die Pflichtchecks erneut pruefen.
4. Den neuen Stable-Tag auf exakt diesem `main`-Commit erstellen, GitHub Release
   veroeffentlichen und Installation/Update pruefen. Fuer den Store denselben
   Commit im Kanal **Stable** einreichen.

Die Metadatenautomatik erzeugt selbst weder Tags noch Releases. Kein Tag wird
verschoben und kein veroeffentlichter Release ueberschrieben. Eine Korrektur
erhaelt eine neue Library-Version und einen neuen Tag.

## Ruecksynchronisierung

Nach einer Stable-Uebernahme wird das vollstaendige Merge-Ergebnis aus `main`
einschliesslich des Merge-Commits wieder nach `dev` uebernommen. Erst danach
beginnt die naechste Entwicklung. Der dadurch ausgeloeste Metadatenlauf eroeffnet
den neuen Entwicklungsstand auf `dev`; Konflikte in `CHANGELOG.md`,
`library.json` und synchronisierten Helper-Manifesten muessen kumulativ geloest
werden.

Eine Beta aus `dev` erzeugt keinen `main`-Merge und benoetigt deshalb keine
Ruecksynchronisierung aus `main`.
