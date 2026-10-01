# IPSModuleStrict-Migration

Dieses Dokument erfasst die verbindlichen Grenzen und den Implementierungsstand
der koordinierten Migration von JSLive auf `IPSModuleStrict`. Die
Produktivklassen sind lokal umgestellt; vor Abschluss fehlt noch die erneute
Abnahme auf der MCP-CURRENT-Testebene.

Die maschinenlesbare Liste aller 74 öffentlichen Methodendeklarationen und
ihrer vorgesehenen Zielsignaturen liegt unter
[`tests/fixtures/strict-module-public-methods.json`](../tests/fixtures/strict-module-public-methods.json).
Der Test `tests/strict-module-migration.php` prüft die tatsächlich
implementierten Zielsignaturen und verhindert, dass neue oder entfernte
öffentliche Methoden unbemerkt an dieser Bestandsaufnahme vorbeilaufen.

## Offizielle Grundlage

Maßgeblich sind die aktuelle
[PHP-Modul-SDK-Dokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
und die darin beschriebenen Unterschiede zwischen `IPSModule` und dem seit
IP-Symcon 8.1 verfügbaren `IPSModuleStrict`:

- alle öffentlichen Methoden benötigen vollständige Type Hints;
- `RegisterVariable*` erhält ein Darstellungsarray und liefert bei
  `IPSModuleStrict` einen Erzeugt-Status statt einer Variablen-ID;
- von einem Strict-Modul angelegte Variablen werden extern schreibgeschützt und
  intern über `$this->SetValue` aktualisiert;
- `ConnectParent()`, `RequireParent()` und `ForceParent()` stehen nicht zur
  Verfügung; die Verbindung wird aus den Datenflussverträgen und optional
  `GetCompatibleParents()` abgeleitet;
- binäre Datenflusswerte werden mit `bin2hex` und `hex2bin` statt über die alte
  UTF-8-Sonderbehandlung transportiert;
- Webhooks verwenden die nativen Methoden `RegisterHook()`,
  `ProcessHookData(): void` und `UnregisterHook()`.

## Umfang und Vererbung

| Quelle | Öffentliche Deklarationen | Zielbasis | Besonderheit |
| --- | ---: | --- | --- |
| `SymconJSLive/module.php` | 7 | `WebHookModule` auf `IPSModuleStrict` | Splitter, `ForwardData`, Hook und Kindkommunikation |
| `SymconJSLive/libs/WebHookModule.php` | 4 | `IPSModuleStrict` | alten manuellen Hook-Workaround durch native Hook-API ersetzen |
| `SymconJSLive/libs/JSLiveModule.php` | 16 | `IPSModuleStrict` | gemeinsame Basis und öffentlich geerbte PHP-/JSON-RPC-Funktionen |
| `SymconJSLiveAdvTextfield/module.php` | 4 | `JSLiveModule` | eigene `ReceiveData`-Route und Konfigurationsübernahme |
| `SymconJSLiveChart/module.php` | 9 | `JSLiveModule` | Actions, dynamische Formularfunktionen und Archivdaten |
| `SymconJSLiveColorPicker/module.php` | 4 | `JSLiveModule` | schreibfähige Browserroute |
| `SymconJSLiveCustom/module.php` | 5 | `JSLiveModule` | Skript-/Medienzugriffe und gemischter Rückgabevertrag |
| `SymconJSLiveDateTimePicker/module.php` | 4 | `JSLiveModule` | schreibfähige Browserroute |
| `SymconJSLiveDoughnutPie/module.php` | 5 | `JSLiveModule` | zusätzlicher Update-Endpunkt |
| `SymconJSLiveGauge/module.php` | 5 | `JSLiveModule` | zusätzlicher Daten-Endpunkt |
| `SymconJSLiveProgressbar/module.php` | 5 | `JSLiveModule` | SVG-Import mit gemischtem Rückgabevertrag |
| `SymconJSLiveRadarChart/module.php` | 6 | `JSLiveModule` | Actions und Archivdaten |

Alle 74 Deklarationen sind auf die inventarisierten Signaturen umgestellt. Die
beiden Konstruktoren verwenden entsprechend der auf MCP-CURRENT per Reflection
geprüften Laufzeitsignatur `int $InstanceID`; alle übrigen öffentlichen Methoden
besitzen vollständige Parameter- und Rückgabetypen. `mixed` bleibt auf die
bisher tatsächlich unterschiedlichen Rückgaben einschließlich `null`
beschränkt. Eine spätere Vereinheitlichung dieser Rückgaben ist eine getrennte
Vertragsänderung.

## Verbindliche Kernsignaturen

Die folgenden Symcon-Einstiege müssen bei der Migration exakt typisiert werden:

```php
public function Create(): void
public function ApplyChanges(): void
public function GetConfigurationForm(): string
public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
public function RequestAction(string $Ident, mixed $Value): void
public function ReceiveData(string $JSONString): string
public function ForwardData(string $JSONString): string
protected function ProcessHookData(): void
```

Ein PHP-Konstruktor darf keinen Rückgabetyp deklarieren. Seine Parameter werden
dennoch gemäß Inventar typisiert. Die vollständigen individuellen Signaturen,
einschließlich der öffentlich aufrufbaren JSLive-Hilfsfunktionen, stehen in der
maschinenlesbaren Inventardatei.

`ReceiveData()` lieferte zuvor in mehreren Modulen außerhalb des `switch` keinen
Wert. Diese Pfade liefern nun den kompatiblen Leerstring; die bestehenden
Datenfluss-, Routing- und Debugtests sichern ihn ab.

`LoadConnectAddress()` liefert beim öffentlichen Formularaufruf die gefundene
Connect-URL und gibt im Startpfad keinen Wert zurück. Die vorgesehene
Strict-Signatur ist deshalb `?string`; Discovery, fehlende Connect-Instanz und
das bedingte Speichern sind durch `tests/connect-address.php` charakterisiert.

## Variablenregistrierung und Schreibzugriff

JSLive registriert zehn eigene Variablen:

| Eigentümer | Idents | Bisherige Profile |
| --- | --- | --- |
| `JSLiveModule` | `IPSView`, `Output` | `~HTMLBox` |
| `SymconJSLiveAdvTextfield` | `Content` | kein Profil |
| `SymconJSLiveChart` | `Period`, `Now`, `Relativ`, `Offset`, `StartDate` | `JSLive_Periode`, `JSLive_Now`, `~Switch`, leer, `~UnixTimestamp` |
| `SymconJSLiveRadarChart` | `Period`, `Relativ` | `JSLive_Periode2`, `~Switch` |

Die Rückgabewerte von `RegisterVariable*` werden heute nirgends als Objekt-ID
verwendet. Der geänderte boolesche Rückgabevertrag verursacht daher keinen
direkten Datenflussbruch.

Für die erste Strict-Migration bleiben Ident, Typ, Name, Position und Profil
unverändert. Bestehende Profile werden als Legacy-Darstellung übergeben:

```php
[
    'PRESENTATION' => VARIABLE_PRESENTATION_LEGACY,
    'PROFILE'      => $profile
]
```

Eine Umstellung auf native Darstellungen oder moderne Kacheln gehört nicht in
denselben Schritt. Als mögliche zentrale Ergänzung ist eine generische
`LegacyProfilePresentation(string $profile): array` im
`VariablePresentationHelper` zu prüfen; eine lokale JSLive-Kopie dieses
Bausteins ist nicht vorgesehen.

Alle JSLive-eigenen Statuswerte werden bereits mit `$this->SetValue`
geschrieben. Die beiden globalen `SetValue()`-Aufrufe in DateTimePicker und
Custom richten sich dagegen absichtlich an konfigurierte Fremdvariablen. Diese
Ziel- und Action-Verträge bleiben separat durch die Webhook-Sicherheitstests
abgesichert und dürfen bei der Strict-Migration nicht auf JSLive-Idents
umgebogen werden.

## Parent-Verbindung

Alle neun Visualisierungsmodule rufen derzeit in `Create()`
`ConnectParent('{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}')` auf. Diese Methode
ist unter `IPSModuleStrict` nicht verfügbar.

`module.json` beschreibt bereits auf beiden Seiten die passende Verbindung:

- Kindanforderung: `{751AABD7-E31D-024C-5CC0-82AC15B84095}`;
- Splitter-Implementierung: `{751AABD7-E31D-024C-5CC0-82AC15B84095}`;
- Splitter-Kindanforderung beziehungsweise Kind-Implementierung:
  `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}`.

Die Umsetzung entfernt daher die neun direkten `ConnectParent()`-Aufrufe und
nutzt die automatische Kompatibilitätsauflösung. `GetCompatibleParents()` wird
nur ergänzt, wenn die Laufzeitabnahme belegt, dass die Standardheuristik den
vorhandenen oder neu anzulegenden JSLive-Splitter nicht korrekt anbietet.
Bestehende ConnectionIDs dürfen sich bei einem Upgrade nicht ändern.

## Datenfluss und Kodierung

Der aktuelle `DataFlowHelper` erzeugt einen JSON-Umschlag mit unveränderter
`DataID` und einem inneren JSON-String in `Buffer`. Es bestehen sieben
Sendepfade: fünf vom Kind zum Splitter und zwei vom Splitter zu den Kindern.
Die öffentlichen Verträge sind eine `ForwardData()`-Methode im Splitter sowie
`ReceiveData()` in der gemeinsamen Basis und allen neun Kindmodulen.

Splitter, `JSLiveModule` und alle neun Kindmodule sind koordiniert migriert.
Beide Data-IDs und die Struktur des inneren JSON bleiben unverändert. Die
offizielle HEX-Regel von `IPSModuleStrict` betrifft Binärwerte; JSLive
transportiert in `Buffer` ausschließlich JSON-Text und benötigt deshalb weder
`bin2hex` noch `hex2bin`. Der zentral synchronisierte `DataFlowHelper` bleibt
unverändert. Eine spätere Einführung echter Binärfelder benötigt einen eigenen
Transportvertrag und neue Negativtests.

Abnahmekriterien:

- alle sieben Sendepfade werden in beide Richtungen geprüft;
- unbekannte Data-IDs und ungültige JSON-Daten werden kontrolliert abgewiesen;
- `getGlobalConfig`, Links, Konfigurationsexport, Cache-Aktualisierung und
  WebSocket-Aktualisierung liefern dieselben fachlichen Payloads;
- keine `utf8_encode()`-/`utf8_decode()`-Kompatibilitätsschicht wird neu
  eingeführt.

## Native Webhook-Grenze

`WebHookModule` verwendet nun die native Hook-API von `IPSModuleStrict`. Die
frühere direkte Änderung der `Hooks`-Property des WebHook Control, die private
Namenskollision `RegisterHook()` und die zusätzliche Kernel-Ready-Nachricht
sind entfernt.

Die Migration erhält den externen Pfad `/hook/JSLive` einschließlich der
Unterpfade `/WS` und `/js`. Intern registriert die native API den Bezeichner
`JSLive` in `Create()`; `ProcessHookData(): void` bleibt die einzige
Routinggrenze. Ein zusätzlicher öffentlicher `Destroy()`-Einstieg wird nicht
eingeführt. Registrierung, Neustart und Entfernen der Instanz werden in der
MCP-CURRENT-Laufzeitabnahme geprüft.

## Weitere Signaturgrenzen

Folgende Punkte sind durch fokussierte Tests abgesichert:

- `GetConfigurationForm()` muss bei jedem Pfad einen gültigen String liefern;
- JSON-Encoding-Fehler dürfen keinen `false`-Wert in eine String-Signatur
  tragen;
- die heute gemischten Rückgaben von Konfigurationsimport, SVG-Import und
  Standardskripterzeugung bleiben zunächst `mixed`;
- keine Modul-ID, kein Präfix und kein automatisch erzeugter PHP-Funktionsname
  ändert sich.

## Schrittfolge und Rückfallgrenze

Das experimentelle SyncModule und das Calendar-Modul wurden vor der
Strict-Migration entfernt; ihre Upgrade-Folgen sind in
[`adr/0002-remove-sync-module.md`](adr/0002-remove-sync-module.md) und
[`adr/0003-remove-calendar-module.md`](adr/0003-remove-calendar-module.md)
dokumentiert.

1. Abgeschlossen: Der bestehende Datenfluss transportiert JSON-Text und bleibt
   ohne HEX-Zusatzkodierung unverändert.
2. Abgeschlossen: Bestehende Profile werden direkt als
   `VARIABLE_PRESENTATION_LEGACY`-Arrays übergeben; ein neuer zentraler Helper
   ist für diese wenigen festen Registrierungen nicht erforderlich.
3. Abgeschlossen: `JSLiveModule`, alle neun Kindmodule und der Splitter sind
   koordiniert migriert.
4. Abgeschlossen: Der manuelle Hook-Workaround ist durch die native Hook-API
   ersetzt, ohne das dokumentierte Sicherheits- und Routingmodell zu ändern.
5. Offen: Die Abnahme aus `SYCON_RUNTIME_MATRIX.md` muss nach Installation des
   Migrationsstands auf MCP-CURRENT vollständig ausgeführt werden.

Der Rückfallpunkt ist der letzte gemeinsam grüne Commit vor der jeweiligen
Strict-Gruppe. Es gibt keine automatische Rückmigration einer bereits
aktualisierten Instanz. Vor Upgrade-Tests wird daher ein vollständiger
Symcon-Snapshot erstellt; ein Fehlschlag wird durch Wiederherstellung dieses
Snapshots und Rückkehr zum vorherigen JSLive-Commit behandelt.

## Freigabebedingungen

Eine Strict-Gruppe ist erst abgeschlossen, wenn:

- die öffentliche Methodeninventur exakt bleibt oder eine Änderung ausdrücklich
  migriert wurde;
- Idents, Profile/Darstellungen, Actions, Werte und Parent-Verbindungen erhalten
  sind;
- zweimaliges `ApplyChanges()` und ein Service-Neustart ohne neue Seiteneffekte
  bleiben;
- Datenfluss und Hook-Routen auf der vereinbarten MCP-CURRENT-Testebene
  nachgewiesen sind;
- lokale Tests, PHP-8.5-Syntax, StylePHP, JSON-Prüfung und CI grün sind.

Der lokale Implementierungsstand ist noch keine Laufzeitfreigabe für
`IPSModuleStrict`; diese folgt erst nach der erneuten MCP-CURRENT-Abnahme.

## Bereits gehärtete Formular-Callbacks

Die öffentlich exportierten Formular-Callbacks verwenden bereits die vom
Legacy-Modullader unterstützten skalaren Parametertypen. Dynamische
Formularwerte werden als String übertragen und intern wieder in Boolean- oder
Integerwerte überführt. Chart-Listenzeilen werden als JSON-String transportiert
und vor der Verarbeitung defensiv dekodiert. Diese Transportkodierung bleibt
auch nach der Strict-Migration Teil des öffentlichen Callback-Vertrags.
