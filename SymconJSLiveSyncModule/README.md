# SymconJSLiveSyncModule

Das Sync-Modul synchronisiert ausgewählte Konfigurationsparameter zwischen
mehreren Instanzen desselben JSLive-Modultyps. Eine Instanz wird als Master
markiert; Änderungen an synchronisierten Parametern werden auf die übrigen
ausgewählten Instanzen übertragen.

## Voraussetzungen und Installation

- IP-Symcon 9.0 oder 9.1 mit PHP 8.5
- mindestens zwei kompatible Instanzen eines JSLive-Moduls
- eine bereits lokal verfügbare Modultyp-Liste; der historische externe Dienst
  dafür ist nicht mehr erreichbar

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

Für die dynamische Modulliste wird noch der nicht mehr erreichbare externe
Dienst `https://jslive.babenschneider.net/` aufgerufen. Dadurch kann die
Modultyp-Auswahl bei neuen oder geänderten Instanzen derzeit nicht aufgebaut
werden. Eine bereits gültig konfigurierte Synchronisierung arbeitet dagegen
lokal und benötigt den Dienst während der Laufzeit nicht. Vor einer weiteren
Verwendung ist zu entscheiden, ob die dauerhafte Master-/Slave-Synchronisierung
benötigt wird; andernfalls kann das Modul entfallen.

Die HTTPS-Anfrage prüft Zertifikat und Hostnamen, erlaubt auch bei
Weiterleitungen nur HTTPS und begrenzt Verbindungs- sowie Gesamtlaufzeit.
Transport-, HTTP- und ungültige JSON-Antworten werden kontrolliert behandelt.
Die neue Option `Debug` ist standardmäßig ausgeschaltet. Bei Aktivierung
protokolliert der gemeinsame `DebugHelper` vollständige Synchronisations-
und Austauschdaten. Bekannte Zugangsdatenfelder werden maskiert; Debug nur in
einer geschützten Testumgebung verwenden.

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
