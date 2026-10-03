# ADR 0006: JSLive-Wartungsumfang und getrennte ECharts-Neuentwicklung

- Status: Angenommen auf Entscheidung des Eigentuemers
- Datum: 03.10.2026
- Ausgangsstand: JSLive 0.90 (`2aaf0ae` / `3770085`)
- Begrenzt: bisherige Modernisierungsplanung, insbesondere Phase 5 und Phase 6
- Konkretisiert: durch den vom Eigentuemer im Chat bereitgestellten Community-Beitrag vom 03.10.2026

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

Die anschliessend mitgeteilte oeffentliche Community-Zusage konkretisiert die
Zustaendigkeiten: Der Nachfolger heisst `SymconEcharts`; JSLive liefert den
Export, SymconEcharts die Uebernahmefunktion. Noch notwendige Ressourcenupdates
bleiben Teil der Stabilisierung. JSLive bleibt fuer nicht durch ECharts
ersetzte Funktionen wie den Colorpicker erhalten und gepflegt, einschliesslich
Alternativen oder eigener Ersatzloesungen bei eingestellten Ressourcen.
Diese Konkretisierung ersetzt die zuvor zu enge Begrenzung auf Fehlerkorrektur
und einen noch voellig offenen Migrationsweg. Quelle ist der bereitgestellte
Beitrag; eine unabhaengige Pruefung der Community-Veroeffentlichung erfolgte nicht.

## Entscheidung

1. JSLive bleibt ein Wartungsprojekt fuer Symcon 9.0/9.1 mit der bestehenden
   PHP-8.5-Zielbasis. Bestehende Funktionen, gespeicherte Konfigurationen,
   Ausgabevariablen, Hook-Pfade und oeffentliche Schnittstellen bleiben erhalten.
2. Notwendige Kompatibilitaetsarbeiten, ausstehende Ressourcenupdates,
   Fehlerkorrekturen sowie zugehoerige Tests und Dokumentation bleiben im
   Umfang. Es gibt keine allgemeine Weiterentwicklung der Modulsammlung und
   keine neue native Kachelmigration. Export und dauerhafte Pflege der nicht
   ersetzten Funktionen sind gesondert zugesagt. Die allgemeine
   Konfigurationsverteilung und ein rein gestalterischer Gauge-Engine-Wechsel
   werden dadurch nicht wieder zu aktiven Auftraegen.
3. Gauge wird als bevorzugte Wahl festgehalten. Unter dem neuen Umfang kann
   das bestehende Gauge-Modul auf Kompatibilitaet und Fehler geprueft werden;
   eine neue Gauge-Kachel ist damit nicht freigegeben. Eine Ausnahme von der
   Wartungsgrenze benoetigt eine gesonderte Entscheidung des Eigentuemers.
4. Das separate Modul `SymconEcharts` verwendet Apache ECharts. JSLive erhaelt
   eine Exportfunktion fuer bestehende Charts; SymconEcharts die zugehoerige
   Uebernahmefunktion. Kein Engine-Umbau innerhalb von JSLive. Repository,
   Architektur, genaue Versionen, Austauschformat, Oberflaeche und Abdeckung
   sind separat festzulegen. In diesem Schritt werden weder ein neues
   Repository noch ein neuer Task oder Prototyp angelegt.
5. Die bestehende Chart.js-/Streaming-Integration bleibt erhalten.
   [ADR 0005](0005-maintained-streaming-fork.md) dokumentiert ihren Lieferweg;
   diese Entscheidung entfernt keine Bundles und aendert nicht den separaten
   Fork. Dessen weitere Wartung oder Stilllegung ist gesondert zu entscheiden.
6. Nicht durch ECharts ersetzte JSLive-Funktionen bleiben erhalten und werden
   weiter gepflegt. Colorpicker ist ausdruecklich genannt; eine abschliessende
   Zuordnung aller Module ist noch offen. Bei Entwicklungsstopp der externen
   Ressourcen werden Alternativen gesucht und integriert oder eigene
   Ersatzloesungen entwickelt. Lizenz, bestehende Vertraege und Regressionen
   sind vor einem Wechsel zu pruefen. Daraus folgt weder eine allgemeine
   Funktionserweiterung noch die Pflicht, alle alten Ressourcen zu forken.

## Anforderungen an den Anwenderumstieg

Ziel ist ein moeglichst nahtloser Umstieg ohne vollstaendigen manuellen Neuaufbau
der Charts. JSLive ist fuer den Export zustaendig, SymconEcharts fuer die
Uebernahme. Diese spezielle Migrationsfunktion ist noch nicht implementiert;
der vorhandene JSLive-Konfigurationsexport allein belegt keine Kompatibilitaet
mit SymconEcharts. Vor einer Umsetzung muessen mindestens folgende Punkte
spezifiziert und mit Tests belegt werden:

- Unterstuetzte JSLive-Ausgangsversionen und Chart-Varianten sowie ein
  gemeinsamer versionierter Export-/Uebernahmevertrag. Bestehenden Export
  samt Regressionstests auf Wiederverwendung pruefen und kompatibel erhalten;
  konkrete Datei-/Datenstruktur und Bedienoberflaeche bleiben offen.
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

Die zugesagte Exportfunktion auf der JSLive-Seite wird getrennt spezifiziert
und umgesetzt, abgestimmt auf die Uebernahme in SymconEcharts. Die bestehende
Konfigurationsuebertragung und ihre Tests dienen als Ausgangspunkt fuer die
Analyse. Die frueher geplante allgemeine
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
Fehler sind vor einer Korrektur nachzuweisen. Fuer die Migration ist der
naechste Planungsschritt die Spezifikation des gemeinsamen Export-/Uebernahme-
vertrags; Arbeiten in SymconEcharts bleiben von diesem Repository getrennt.
