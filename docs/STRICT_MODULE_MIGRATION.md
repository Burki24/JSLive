# Vorbereitung der IPSModuleStrict-Migration

Dieses Dokument erfasst den technischen Ausgangszustand und die verbindlichen
Grenzen für eine spätere Migration von JSLive auf `IPSModuleStrict`. In diesem
Schritt wird noch keine Modulklasse umgestellt und kein Laufzeitverhalten
geändert.

Die maschinenlesbare Liste aller 81 öffentlichen Methodendeklarationen und
ihrer vorgesehenen Zielsignaturen liegt unter
[`tests/fixtures/strict-module-public-methods.json`](../tests/fixtures/strict-module-public-methods.json).
Der Test `tests/strict-module-migration.php` verhindert, dass neue oder
entfernte öffentliche Methoden unbemerkt an dieser Bestandsaufnahme
vorbeilaufen.

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
| `SymconJSLiveCalendar/module.php` | 7 | `JSLiveModule` | zusätzliche Formular- und CSS-Link-Funktionen |
| `SymconJSLiveChart/module.php` | 9 | `JSLiveModule` | Actions, dynamische Formularfunktionen und Archivdaten |
| `SymconJSLiveColorPicker/module.php` | 4 | `JSLiveModule` | schreibfähige Browserroute |
| `SymconJSLiveCustom/module.php` | 5 | `JSLiveModule` | Skript-/Medienzugriffe und gemischter Rückgabevertrag |
| `SymconJSLiveDateTimePicker/module.php` | 4 | `JSLiveModule` | schreibfähige Browserroute |
| `SymconJSLiveDoughnutPie/module.php` | 5 | `JSLiveModule` | zusätzlicher Update-Endpunkt |
| `SymconJSLiveGauge/module.php` | 5 | `JSLiveModule` | zusätzlicher Daten-Endpunkt |
| `SymconJSLiveProgressbar/module.php` | 5 | `JSLiveModule` | SVG-Import mit gemischtem Rückgabevertrag |
| `SymconJSLiveRadarChart/module.php` | 6 | `JSLiveModule` | Actions und Archivdaten |

Alle 81 Deklarationen benötigen mindestens eine Signaturanpassung: Die beiden
Konstruktoren besitzen untypisierte Parameter; alle übrigen öffentlichen
Methoden besitzen aktuell keinen Rückgabetyp. Die Zieldatei verwendet `mixed`
nur dort, wo der bisherige öffentliche Vertrag tatsächlich unterschiedliche
Rückgabetypen einschließlich `null` enthält. Eine spätere Vereinheitlichung
dieser Rückgaben ist eine getrennte Vertragsänderung.

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

`ReceiveData()` fällt heute in mehreren Modulen ohne Rückgabewert aus dem
`switch`. Für die Strict-Signatur muss jeder Pfad einen String liefern; der
kompatible leere Rückgabewert ist vor der Umstellung durch die bestehenden
Datenfluss- und Webhook-Tests zu charakterisieren.

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

Alle zehn Visualisierungsmodule rufen derzeit in `Create()`
`ConnectParent('{9FFF3FC0-FD51-C289-FA36-BC1C370946CF}')` auf. Diese Methode
ist unter `IPSModuleStrict` nicht verfügbar.

`module.json` beschreibt bereits auf beiden Seiten die passende Verbindung:

- Kindanforderung: `{751AABD7-E31D-024C-5CC0-82AC15B84095}`;
- Splitter-Implementierung: `{751AABD7-E31D-024C-5CC0-82AC15B84095}`;
- Splitter-Kindanforderung beziehungsweise Kind-Implementierung:
  `{79D59629-E9C5-44F1-0F34-0FBC5C88F307}`.

Die erste Umsetzung entfernt daher die zehn direkten `ConnectParent()`-Aufrufe
und nutzt die automatische Kompatibilitätsauflösung. `GetCompatibleParents()`
wird nur ergänzt, wenn die Fresh-Installation aus der Laufzeitmatrix belegt,
dass die Standardheuristik den vorhandenen oder neu anzulegenden
JSLive-Splitter nicht korrekt anbietet. Bestehende ConnectionIDs dürfen sich
bei einem Upgrade nicht ändern.

## Datenfluss und Kodierung

Der aktuelle `DataFlowHelper` erzeugt einen JSON-Umschlag mit unveränderter
`DataID` und einem inneren JSON-String in `Buffer`. Es bestehen sieben
Sendepfade: fünf vom Kind zum Splitter und zwei vom Splitter zu den Kindern.
Die öffentlichen Verträge sind eine `ForwardData()`-Methode im Splitter sowie
`ReceiveData()` in der gemeinsamen Basis und allen zehn Kindmodulen.

Die Strict-Umstellung muss Splitter, `JSLiveModule` und alle zehn Kindmodule in
einem koordinierten Schritt migrieren. Dabei bleiben beide Data-IDs und die
Struktur des inneren JSON unverändert; nur der von Strict geforderte binäre
Transport wird an einer zentralen Helper-Grenze mit `bin2hex` und `hex2bin`
behandelt. Vor einer Änderung des zentral synchronisierten Helpers ist mit
einem echten 9.0/9.1-Harness festzustellen, welche Schicht die HEX-Kodierung
liefert. Eine parallele Mischung aus altem und neuem Transport ist nicht
freigegeben.

Abnahmekriterien:

- alle sieben Sendepfade werden in beide Richtungen geprüft;
- unbekannte Data-IDs und ungültige HEX-/JSON-Daten werden kontrolliert
  abgewiesen;
- `getGlobalConfig`, Links, Konfigurationsexport, Cache-Aktualisierung und
  WebSocket-Aktualisierung liefern dieselben fachlichen Payloads;
- keine `utf8_encode()`-/`utf8_decode()`-Kompatibilitätsschicht wird neu
  eingeführt.

## Native Webhook-Grenze

`WebHookModule` schreibt aktuell die `Hooks`-Property des WebHook Control
direkt und registriert sich zusätzlich über eine Kernel-Ready-Nachricht. Unter
`IPSModuleStrict` kollidiert dessen private Methode `RegisterHook()` mit der
nativen API und muss entfernt werden.

Die Migration erhält den externen Pfad `/hook/JSLive` einschließlich der
Unterpfade `/WS` und `/js`. Intern registriert die native API den Bezeichner
`JSLive`; `ProcessHookData(): void` bleibt die einzige Routinggrenze. Ein
`Destroy(): void` gibt die Registrierung mit `UnregisterHook()` frei. Ob die
zusätzliche Kernel-Ready-Nachricht entfallen kann, wird in der 9.0/9.1-
Laufzeitmatrix geprüft und nicht allein aus dem Quelltext angenommen.

## Weitere Signaturgrenzen

Folgende Punkte benötigen vor dem Umschalten einen fokussierten Test:

- `GetConfigurationForm()` muss bei jedem Pfad einen gültigen String liefern;
- JSON-Encoding-Fehler dürfen keinen `false`-Wert in eine String-Signatur
  tragen;
- die heute gemischten Rückgaben von Konfigurationsimport, SVG-Import und
  Standardskripterzeugung bleiben zunächst `mixed`;
- keine Modul-ID, kein Präfix und kein automatisch erzeugter PHP-Funktionsname
  ändert sich.

## Schrittfolge und Rückfallgrenze

Das experimentelle SyncModule wurde vor der Strict-Migration entfernt; die
Entscheidung und ihre Upgrade-Folge sind in
[`adr/0002-remove-sync-module.md`](adr/0002-remove-sync-module.md) dokumentiert.

1. Strict-Datenflusskodierung in einer isolierten 9.0/9.1-Testinstanz belegen
   und den zentralen `DataFlowHelper` nur bei nachgewiesenem Bedarf erweitern.
2. Falls benötigt, die Legacy-Profil-Darstellung zentral ergänzen und über den
   bestehenden Helper-Sync beziehen.
3. `JSLiveModule`, alle zehn Kindmodule und den Splitter koordiniert migrieren.
4. Den manuellen Hook-Workaround durch die native Hook-API ersetzen, ohne das
   dokumentierte Sicherheits- und Routingmodell zu verändern.
5. Die vier Szenarien aus `SYCON_RUNTIME_MATRIX.md` vollständig ausführen.

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
- Datenfluss und Hook-Routen auf IP-Symcon 9.0 und 9.1 nachgewiesen sind;
- lokale Tests, PHP-8.5-Syntax, StylePHP, JSON-Prüfung und CI grün sind.

Die vorliegende Bestandsaufnahme ist noch keine Freigabe für
`IPSModuleStrict`; sie definiert die Voraussetzungen dafür.

## Bereits gehärtete Formular-Callbacks

Die öffentlich exportierten Formular-Callbacks verwenden bereits die vom
Legacy-Modullader unterstützten skalaren Parametertypen. Dynamische
Formularwerte werden als String übertragen und intern wieder in Boolean- oder
Integerwerte überführt. Chart-Listenzeilen werden als JSON-String transportiert
und vor der Verarbeitung defensiv dekodiert. Diese Transportkodierung bleibt
auch bei der späteren Strict-Migration Teil des öffentlichen Callback-Vertrags.
