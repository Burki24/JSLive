# SymconJSLiveConfigStore

`SymconJSLiveConfigStore` ist ein eigenständiges Symcon-Modul zum Suchen,
Hochladen, Bewerten und Wiederherstellen von JSLive-Konfigurationen über den
externen JSLive-Config-Store. Es ist kein Kindmodul des JSLive-Splitters.

## Voraussetzungen und Einrichtung

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- Netzwerkzugriff auf den vom Modul verwendeten Config-Store-Dienst

Eine `SymconJSLiveConfigStore`-Instanz anlegen. `ForumUsername` ist für
Benutzerbezug und Zugriffsanfragen vorgesehen; `UserID` wird standardmäßig aus
der Modulidentität erzeugt und kann im Formular gelesen werden. Anschließend
kann ein unterstützter Modultyp ausgewählt und nach Instanzen oder Store-
Einträgen gesucht werden.

## Funktionen

Das dynamische Formular unterstützt:

- Abruf der verfügbaren JSLive-Modultypen und eigener Instanzen;
- Upload einer exportierten Modulkonfiguration mit Beschreibung und Bild;
- Suche, Sortierung und Seitennavigation im Config-Store;
- Laden einer Konfiguration in ausgewählte kompatible Instanzen oder Erzeugen
  einer neuen Instanz;
- Aktualisieren, Löschen und Bewerten eigener Store-Einträge;
- Anfordern des Zugriffs und Vorschau eines Modul-Links.

Die öffentlichen Aktionen umfassen unter anderem `UploadConfig`,
`LoadConfiguration`, `UpdateConfiguration`, `DeleteConfiguration`, `SetVote`,
`RequestAccess`, `Preview`, `SearchStoreList` und `ReadUserID`.

## Sicherheits- und Betriebsgrenzen

Der Dienst wird über `https://jslive.babenschneider.net/` angesprochen. Die
Bestandsimplementierung deaktiviert bei diesen cURL-Aufrufen die TLS-
Zertifikatsprüfung; der Pfad ist daher vor einer produktiven Freigabe zu
überarbeiten. Die Konfigurationsdaten können sensible Eigenschaften enthalten
und werden an den externen Dienst übertragen.

Das Laden kann bestehende Modulkonfigurationen überschreiben, `ApplyChanges()`
auslösen oder eine neue Instanz erzeugen. Nur vertrauenswürdige und zum
Modultyp passende Konfigurationen verwenden. Der externe Dienst, sein Protokoll
und seine Verfügbarkeit sind nicht Bestandteil der lokalen JSLive-Testumgebung.
Die neue Option `Debug` ist standardmäßig ausgeschaltet. Bei Aktivierung
protokolliert der gemeinsame `DebugHelper` vollständige Formular- und
Store-Austauschdaten einschließlich exportierter Konfigurationen und Medien.
Bekannte Zugangsdatenfelder werden maskiert; Debug nur in einer geschützten
Testumgebung verwenden.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{39EE8DDC-C72A-CEA0-2774-CB86F244A515}` |
| Prefix | `SymconJSLiveConfigStore` |
| Parent-/Child-Anforderung | keine |
| Externer Dienst | `https://jslive.babenschneider.net/` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
