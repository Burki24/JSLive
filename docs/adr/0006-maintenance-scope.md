# ADR 0006: JSLive-Wartungsumfang und getrennte ECharts-Neuentwicklung

- Status: Angenommen auf Entscheidung des Eigentuemers
- Datum: 03.10.2026
- Ausgangsstand: JSLive 0.90 (`2aaf0ae` / `3770085`)
- Begrenzt: bisherige Modernisierungsplanung, insbesondere Phase 5 und Phase 6

## Kontext

Die bestehenden IPSView-, Output- und Link-Vertraege sind charakterisiert.
Push, gruene CI und Modulupdate dieses Schritts wurden vom Eigentuemer
bestaetigt. Als naechstes war ein Pilot fuer eine native Kacheldarstellung
vorgesehen; der Eigentuemer bevorzugt Gauge statt AdvTextfield/Progressbar.
Ein vom Eigentuemer neu angelegtes Test-Gauge wird bereits ordentlich in einer
Kachel dargestellt (Rueckmeldung und Screenshot vom 03.10.2026). Der Sichttest
belegt diese konkrete Ausgabe, nicht den Einsatz des nativen HTML-SDK oder
eine vollstaendige Gauge-/Transport-/IPSView-Abnahme.

Gleichzeitig hat er das Projektziel geaendert: JSLive soll nur noch fuer
Symcon 9.0/9.1 lauffaehig gehalten und von vorhandenen Fehlern bereinigt werden.
Fuer die zukuenftige Weiterentwicklung plant er ein neues Modul mit ECharts
anstelle von Chart.js. Diese Bibliothekswahl ist eine Produktentscheidung,
kein in diesem Schritt durchgefuehrter Vergleich oder Wartungsnachweis.
Vor dem Push wurde die Migrationsanforderung praezisiert: Anwender sollen
bestehende JSLive-Charts moeglichst nahtlos in das neue ECharts-Modul uebernehmen
koennen. Der Ausschluss eines Engine-Umbaus innerhalb von JSLive schliesst
diese anwenderseitige Migration ausdruecklich nicht aus.

## Entscheidung

1. JSLive bleibt ein Wartungsprojekt fuer Symcon 9.0/9.1 mit der bestehenden
   PHP-8.5-Zielbasis. Bestehende Funktionen, gespeicherte Konfigurationen,
   Ausgabevariablen, Hook-Pfade und oeffentliche Schnittstellen bleiben erhalten.
2. Zulässig sind notwendige Kompatibilitaetsarbeiten, Korrekturen vorhandener
   Fehler und die dazu erforderlichen Tests und Dokumentation. Als ausdrueckliche
   Ausnahme ist gezielte Unterstuetzung fuer die Uebernahme vorhandener Charts
   in das neue ECharts-Modul eingeplant. Sonstige neue Funktionen,
   eine native Kachelmigration und Bibliothekswechsel allein zur Modernisierung
   werden nicht weiterverfolgt. Erweiterte Konfigurationsverteilung und die
   Suche nach einer moderneren Gauge-Engine entfallen als aktive JSLive-ToDos.
3. Gauge wird als bevorzugte Wahl festgehalten. Unter dem neuen Umfang kann
   das bestehende Gauge-Modul auf Kompatibilitaet und Fehler geprueft werden;
   eine neue Gauge-Kachel ist damit nicht freigegeben. Eine Ausnahme von der
   Wartungsgrenze benoetigt eine gesonderte Entscheidung des Eigentuemers.
4. ECharts gehoert in ein separates neues Modul, nicht in einen Engine-Umbau
   von JSLive. Die Uebernahme bestehender JSLive-Charts wird als verbindliche
   Anwenderfunktion des Uebergangs eingeplant. Projektname, Repository,
   Architektur, genaue Versionen, weitere Funktionen und der technische
   Migrationsweg sind noch nicht festgelegt.
   Es werden weder ein neues Repository noch ein neuer Task oder Prototyp angelegt.
5. Die bestehende Chart.js-/Streaming-Integration bleibt erhalten.
   [ADR 0005](0005-maintained-streaming-fork.md) dokumentiert ihren Lieferweg;
   diese Entscheidung entfernt keine Bundles und aendert nicht den separaten
   Fork. Dessen weitere Wartung oder Stilllegung ist gesondert zu entscheiden.

## Anforderungen an den Anwenderumstieg

Ziel ist ein moeglichst nahtloser Umstieg ohne vollstaendigen manuellen Neuaufbau
der Charts. Die Funktion ist noch nicht implementiert. Vor einer Umsetzung
muessen mindestens folgende Punkte spezifiziert und mit Tests belegt werden:

- Unterstuetzte JSLive-Ausgangsversionen und Chart-Varianten sowie ein
  nachvollziehbarer, versionierter Uebernahmevertrag; der konkrete Lieferweg
  (beispielsweise Import oder Assistent) bleibt offen.
- Zuordnung von Datenreihen, Variablen-/Archivbezuegen, Zeitbereichen, Achsen,
  Einheiten, Beschriftungen und Darstellungsoptionen. Bestehende Quelldaten
  und Archivhistorien duerfen durch den Umstieg nicht veraendert werden.
- Vorschau und ausdrueckliche Bestaetigung vor dem Erzeugen oder Aendern der
  Zielkonfiguration. Das JSLive-Original bleibt als Vergleich und Rueckfall
  erhalten; kein automatisches Loeschen oder Ueberschreiben.
- Sichtbare Meldungen fuer nicht automatisch uebertragbare Optionen,
  eigene Templates, Skripte oder Chart.js-spezifische Erweiterungen. Keine
  stillschweigende Verwerfung und kein unbelegtes Versprechen vollstaendiger
  1:1-Uebernahme beliebiger Anpassungen.
- Sichere Wiederholung, kontrollierter Umgang mit Teilfehlern und
  Regressionstests anhand repraesentativer anonymisierter Konfigurationen.

Gezielte Vorbereitung auf der JSLive-Seite ist innerhalb dieser Ausnahme
zulaessig, sofern fuer den beschlossenen Migrationsweg erforderlich. Sie wird
getrennt spezifiziert und umgesetzt. Die frueher geplante allgemeine
Konfigurationsverteilung ist dadurch nicht wieder freigegeben.

## Folgen und Nachweise

- Bereits umgesetzte Korrekturen, lokale Assets und Regressionstests bleiben
  bestehen. Historische Erweiterungsplaene werden kenntlich gemacht, nicht
  stillschweigend auf das neue Modul uebertragen.
- Die aktuelle per MCP erreichbare Symcon-Testebene bleibt massgeblich;
  der Eigentuemer verlangt keine separate 9.0-Installation. Daraus entsteht
  kein unabhaengiger Laufzeitnachweis fuer jede Versionskombination.
- Die fehlende IPSView-Testlizenz bleibt eine offen ausgewiesene Testluecke.
- Diese Aenderung betrifft ausschliesslich Regeln und Dokumentation, keine
  Laufzeitdateien, Metadaten oder bestehenden Installationen.

Naechster moeglicher Wartungsschritt ist eine begrenzte Bestandspruefung von
Gauge anhand der vorhandenen Tests und offenen Laufzeitluecken. Konkrete
Fehler sind vor einer Korrektur nachzuweisen; die ECharts-Neuentwicklung wird
separat beauftragt und geplant.
