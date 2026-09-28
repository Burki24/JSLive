# ADR 0002: Experimentelles SyncModule entfernen

- Status: Angenommen
- Datum: 28.09.2026

## Kontext

Das `SymconJSLiveSyncModule` wurde 2023 als experimentelles Sondermodul
eingefuehrt. Es sollte ausgewaehlte Konfigurationsparameter einer Masterinstanz
direkt auf weitere JSLive-Instanzen verteilen.

Das Konfigurationsformular benoetigte eine Modultyp-Liste vom nicht mehr
erreichbaren Dienst `jslive.babenschneider.net` und konnte deshalb bei neuen
Installationen nicht mehr verlaesslich geoeffnet oder eingerichtet werden. Die
lokale Implementierung verwarf zudem gespeicherte Parameterauswahlen und
ueberschrieb Zielkonfigurationen unmittelbar mit anschliessendem
`IPS_ApplyChanges()`. Eine Vorschau, Bestaetigung, Transaktion oder automatische
Rueckfallmoeglichkeit existierte nicht. Die Tests charakterisierten Debug- und
TLS-Hilfen, aber nicht die eigentliche Synchronisationslogik oder deren
Fehlerfaelle.

Der spaeter geplante Anwendungsfall ist keine dauerhafte Master-/Slave-
Synchronisation, sondern eine bewusst angestossene lokale Einmalverteilung von
Konfigurationen. Dafuer ist die bestehende Implementierung keine geeignete
Grundlage.

## Entscheidung

Das `SymconJSLiveSyncModule` wird vollstaendig aus der Library entfernt. Dazu
gehoeren Modulcode, Formular, Lokalisierung, Dokumentation, modulbezogene Tests
und Vertrags-Fixtures.

Vorhandene SyncModule-Instanzen muessen vor dem Update auf diesen Stand manuell
geloescht werden. Es gibt keine automatische Migration. Die bisherige Modul-ID
`{6C44628E-B623-7B92-D61D-0B3EAF4D6345}`, das Praefix
`SymconJSLiveModuleSync` und die daraus erzeugten PHP-Funktionsnamen werden
nicht fuer eine neue Funktion wiederverwendet.

Die in ADR 0001 dokumentierte Aussage, dass das SyncModule erhalten bleibt und
separat bewertet wird, ist mit dieser Entscheidung ueberholt.

## Folgen

- Die Library enthaelt zehn Visualisierungsmodule und den Splitter, insgesamt
  elf Module.
- Eine kontinuierliche Master-/Slave-Synchronisation ist nicht mehr Teil von
  JSLive.
- Eine spaetere lokale Einmalverteilung wird als neue Funktion mit Vorschau,
  expliziter Zielauswahl, Fehlerbehandlung und eigenem Testvertrag entworfen.
- Falls kuenftig erneut eine kontinuierliche Synchronisation benoetigt wird,
  erfordert sie eine neue Architekturentscheidung und eine neue Modul-ID.
- Die `IPSModuleStrict`-Inventur umfasst nur noch die produktiv verbleibenden
  Klassen und muss keine inkompatiblen Buffer-Wrapper des SyncModule migrieren.

## Rueckfall

Ein Rueckfall ist nur durch Wiederherstellung eines Symcon-Snapshots und eines
JSLive-Commits vor dieser Entfernung vorgesehen. Eine geloeschte Instanz wird
nicht automatisch rekonstruiert; ihre bisherige Konfiguration muss fuer einen
Rueckfall im Snapshot enthalten sein.
