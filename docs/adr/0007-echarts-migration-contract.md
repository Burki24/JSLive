# ADR 0007: Gemeinsamer Export-/Importvertrag fuer SymconECharts

- Status: Angenommen; Spezifikation, noch kein implementierter Exporter
- Datum: 2026-10-31
- Ergaenzt: [ADR 0006](0006-maintenance-scope.md)

## Entscheidung

Die zugesagte Migration wird durch den Vertrag `de.burki24.jslive-echarts`,
Formatversion `1.0`, konkretisiert. Kanonische Definition ist
[JSLIVE_MIGRATION_CONTRACT.md im Nachfolger](../../../SymconECharts/docs/JSLIVE_MIGRATION_CONTRACT.md).
Im veroeffentlichten Repository ist sie unter
[SymconECharts/docs/JSLIVE_MIGRATION_CONTRACT.md](https://github.com/Burki24/SymconECharts/blob/dev/docs/JSLIVE_MIGRATION_CONTRACT.md)
auffindbar. JSLive pflegt keine abweichende Kopie. Die Gegenentscheidung ist
SymconECharts ADR 0109. Aenderungen erfolgen abgestimmt in beiden Repositories;
Formatrevisionen sind keine automatischen Library-Versionsaenderungen.

JSLive erhaelt einen eigenen ausdruecklichen Export fuer SymconECharts. Der
bestehende Export-/Importvertrag und seine Skriptauswahl bleiben erhalten.
Der neue Export erfasst die vollstaendige fachliche Konfiguration, Bindungen,
Achsen, Steuerzustaende, Zeit-/Archivregeln, Gestaltung und benoetigte Ressourcen.
Profile werden nicht nur benannt, sondern vollstaendig aufgeloest und in native
Darstellungsdefinitionen uebersetzt; bestehende native Darstellungen ebenso.
Praefixe, Suffixe, Genauigkeit, Bereiche, Schrittweiten, Zustaende, Icons und
Farben bleiben nachvollziehbar. Keine neue Profilabhaengigkeit im Ziel.

Die Erfassung bleibt lesend. Bestehende Quellvariablen, Profile, Darstellungen,
Actions, Archivwerte und Designs werden nicht umgestellt. Historie bleibt bei
derselben Installation ueber dieselben IDs erreichbar. Eigene Vorlagen werden
ausdruecklich als inerte Inhalte behandelt, nicht automatisch ausgefuehrt.
SymconECharts besitzt Vorschau, Feldbericht, konkrete Bestaetigung, Neuanlage
und wiederaufnehmbares Importjournal. Gemeinsame Konverter werden erst mit
tatsaechlicher Nutzung beider Libraries zentral ueber Symcon_ModuleHelper verteilt.

## Abdeckung und Nachweise

JSLive 0.105 ist lediglich der lokale Analyse-Ausgangsstand. Die erste Version
mit dem neuen Export und die unterstuetzten Altvarianten werden erst nach
Implementierung und beidseitigen Vertragstests festgelegt. Das alte
`ModuleID`-/`ModuleName`-/`Config`-Format ist kein Paket 1.0.

Die Pflichtmatrix im kanonischen Vertrag verlangt Profil-/Native-Gleichheit,
Erhalt von Rohwerten, unabhaengige historische Vergleichswerte, Bildvergleiche,
Wiederholung und Teilfehlerpruefung. Fehlende Zielmodelle werden sichtbar gemeldet;
insbesondere historisches Radar wird nicht als aktuelles EChartsRadar ausgegeben.
Die bestehenden Exporttests belegen diese neue Funktion noch nicht.

Mit dieser ADR ist die Spezifikationsanforderung aus ADR 0006 erfuellt.
Exporter, Importer und gemeinsame Live-Abnahme bleiben offen. Weder eine
JSLive-Engineumstellung noch eine allgemeine Weiterentwicklung ist beauftragt.
