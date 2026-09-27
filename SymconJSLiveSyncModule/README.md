# SymconJSLiveSyncModule

Das Sync-Modul synchronisiert ausgewählte Konfigurationsparameter zwischen
mehreren Instanzen desselben JSLive-Modultyps. Eine Instanz wird als Master
markiert; Änderungen an synchronisierten Parametern werden auf die übrigen
ausgewählten Instanzen übertragen.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- mindestens zwei kompatible Instanzen eines JSLive-Moduls
- Netzwerkzugriff auf den vom Modul verwendeten JSLive-Moduldienst für die
  Modulliste

Eine `SymconJSLiveSyncModule`-Instanz anlegen. Unter `SelectType` den zu
synchronisierenden Modultyp auswählen, unter `InstanceList` die Instanzen
hinterlegen und genau die gewünschte Master-Instanz markieren. `Parameterlist`
zeigt die aus dem Formular des gewählten Modultyps abgeleiteten Parameter.

## Konfiguration und Synchronisation

- `SelectType` bestimmt die Modul-GUID der zu synchronisierenden Instanzen.
- `InstanceList` enthält Instanz-ID und Master-Markierung.
- `Parameterlist` legt pro Parameter fest, ob er synchronisiert wird.
- Formularfelder mit `ignoreexport` werden standardmäßig nicht synchronisiert;
  verschachtelte JSON-Listen werden anhand ihrer Spaltenparameter verarbeitet.

Das Modul registriert `IM_CHANGESETTINGS`-Nachrichten für Master-Instanzen.
Ändert eine Master-Instanz ihre Konfiguration, werden freigegebene Parameter in
anderen passenden Instanzen aktualisiert und dort `ApplyChanges()` ausgelöst.

## Technische Grenzen und Sicherheit

Die Synchronisation kann Konfigurationen anderer Instanzen überschreiben und
`ApplyChanges()` auslösen. Nur kompatible Instanzen und bewusst freigegebene
Parameter verwenden. Ungültige oder gelöschte Instanzen werden im Formular
markiert und übersprungen.

Der Bestandsstand ruft für die dynamische Modulliste den externen Dienst
`https://jslive.babenschneider.net/` auf und deaktiviert dabei die TLS-
Zertifikatsprüfung. Dieser Pfad ist vor einem produktiven Einsatz zu härten.
Synchronisierte Konfigurationen können sensible Daten enthalten und gehören
nicht in ungeschützte Logs oder Exporte.

## Technische Daten

| Eintrag | Wert |
| --- | --- |
| Modul-ID | `{6C44628E-B623-7B92-D61D-0B3EAF4D6345}` |
| Modulname in `module.json` | `SymconJSLiveModuleSync` |
| Prefix | `SymconJSLiveModuleSync` |
| Parent-/Child-Anforderung | keine |
| Öffentliche Formularaktionen | `changeModule`, `changeInstance` |

## Lizenz

JSLive steht unter der [GNU General Public License Version 3](../LICENSE).
