# ADR 0003: Calendar-Modul entfernen

- Status: Angenommen
- Datum: 30.09.2026

## Kontext

`SymconJSLiveCalendar` stellte eine umfangreiche Kalenderdarstellung mit
FullCalendar, Symcon-Kalenderquellen und eingebetteten beziehungsweise externen
iCalendar-Daten bereit. Das Modul brachte dafuer eine grosse Formular- und
CSS-Implementierung, eigene `getFeed`-, `getCSS`- und `getICS`-Webhook-Pfade,
lokale FullCalendar-Assets sowie weitere CDN-Abhaengigkeiten mit.

Die Kalenderfunktion wird im Testsystem nicht mehr benoetigt. Eine Reparatur
des fehlenden `setData`-Handlers, die Konsolidierung der Frontend-Abhaengigkeiten
und weitere Laufzeittests wuerden deshalb Wartungsaufwand fuer eine bewusst
aufgegebene Funktion erzeugen.

## Entscheidung

`SymconJSLiveCalendar` wird vollstaendig aus der Library entfernt. Dazu gehoeren
Modulcode, Formular, Lokalisierung, Dokumentation, Calendar-Templates,
FullCalendar-Assets, die Calendar-spezifischen Webhook-Sonderfaelle sowie seine
Vertrags-, Debug- und Laufzeitpruefungen.

Vorhandene Calendar-Instanzen muessen vor dem Update auf diesen Stand manuell
geloescht werden. Es gibt keine automatische Migration. Die historische
Modul-ID `{46B41C3B-DDAE-BA35-2A1E-6CF4B7F9BF7A}`, das Praefix
`SymconJSLiveCalendar` und die daraus erzeugten PHP-Funktionsnamen werden nicht
fuer eine andere Funktion wiederverwendet.

## Folgen

- Die Library enthaelt neun Visualisierungsmodule und den Splitter, insgesamt
  zehn Module.
- Die Routen `getFeed`, `getCSS` und `getICS` besitzen keine verbleibenden
  Kindmodul-Implementierungen. Die bisherige kennwortfreie `getCSS`-Ausnahme
  und die Calendar-spezifische Antwortaufbereitung entfallen.
- Der allgemeine Formularvertrag bleibt anhand des Chart-Formulars fuer
  `viewlevel`, `requireItem`, technische Feldnamen und Live-Aktualisierungen
  abgesichert.
- Vom Calendar verwendete Symcon-Objekte, Medien oder externe Kalenderquellen
  werden nicht automatisch geloescht. Nur die Calendar-Instanz ist vor dem
  Bibliotheksupdate verpflichtend zu entfernen.
- Die in ADR 0002 dokumentierte Modulanzahl ist mit dieser Entscheidung
  ueberholt.

## Rueckfall

Ein Rueckfall ist nur durch Wiederherstellung eines Symcon-Snapshots und eines
JSLive-Commits vor dieser Entfernung vorgesehen. Eine geloeschte Instanz wird
nicht automatisch rekonstruiert; ihre bisherige Konfiguration muss fuer einen
Rueckfall im Snapshot enthalten sein.
