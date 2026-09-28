# ADR 0001: ConfigStore aus der Library entfernen

- Status: angenommen
- Datum: 28.09.2026

## Kontext

`SymconJSLiveConfigStore` stellte keine lokale Speicherfunktion bereit. Das
Modul lud Modul- und Zugriffsdaten von `jslive.babenschneider.net`, uebertrug
vollstaendige Instanzkonfigurationen an diesen Dienst und konnte dort
veroeffentlichte Konfigurationen suchen, bewerten, herunterladen und auf lokale
Instanzen anwenden.

Der externe Dienst ist nicht mehr verfuegbar. Bereits der Aufbau des
Konfigurationsformulars benoetigte mehrere synchrone Serveranfragen. Dadurch war
das Modul ohne den Dienst funktionslos und konnte die Konfiguration in
IP-Symcon lange blockieren. Eine Serverimplementierung und eine belastbare
Protokolldokumentation sind nicht Bestandteil des Repositories.

## Entscheidung

Der ConfigStore wird einschliesslich Modulimplementierung, Vertragsfixture und
isolierter Tests aus JSLive entfernt. Der unveraenderte Weiterbetrieb oder ein
Ersatzserver sind nicht Teil der Modernisierung.

Vorhandene ConfigStore-Instanzen muessen vor einem Update auf den ersten Stand
ohne dieses Modul manuell geloescht werden. Die historische Modul-ID
`{39EE8DDC-C72A-CEA0-2774-CB86F244A515}` wird nicht fuer eine andere Funktion
wiederverwendet.

## Folgen

- Die zehn Visualisierungsmodule, der Splitter und das SyncModule bleiben davon
  technisch unberuehrt.
- Der zentrale Austausch, die Bewertung und das servergestuetzte Laden fremder
  Konfigurationen entfallen.
- Der nicht mehr erreichbare Dienst wird aus dem ConfigStore-Pfad vollstaendig
  entfernt.
- Eine spaetere Export-/Importfunktion fuer fertig konfigurierte Chart-Ansichten
  wird lokal und dienstunabhaengig als eigene Funktion entworfen. Sie uebernimmt
  weder die ConfigStore-Modul-ID noch dessen Serverprotokoll.
- Das SyncModule wird separat bewertet, weil seine lokale Master-/Slave-
  Synchronisierung fachlich nicht vom ConfigStore abhaengt.
