# Webhook-Sicherheitsmodell

Dieses Dokument beschreibt den bestehenden Vertrag des JSLive-Webhooks auf
dem Entwicklungsstand von September 2026. Es ist eine Charakterisierung des
Bestands und keine Aussage, dass jede historische Entscheidung dem künftigen
Zielmodell entspricht.

## Vertrauensgrenze

Der Splitter stellt unter `/hook/JSLive` statische Browserdateien sowie
dynamische Antworten der Kindmodule bereit. Ein automatisch erzeugtes
`Password` schützt die dynamischen Routen. Das Kennwort wird als Queryparameter
`pw` in generierte Links und Browseraufrufe aufgenommen und ist damit ein
Bearer-Geheimnis: Wer eine vollständige URL kennt, kann dieselben Operationen
aufrufen.

Die Auslieferung muss deshalb bei externer Erreichbarkeit über TLS erfolgen.
Zusätzlich sollte der IP-Symcon-WebHook nur für die tatsächlich benötigten
Netze erreichbar sein. Ein CORS-Header ersetzt weder diese Netzgrenze noch die
Kennwortprüfung.

## Routen- und CORS-Modell

| Route | Kennwort | CORS | Methode | Wirkung |
| --- | --- | --- | --- | --- |
| `/hook/JSLive/js/*` | keines | `Access-Control-Allow-Origin: *`; beworben werden `POST, GET, OPTIONS` | nicht erzwungen | öffentliche, nur lesende Asset-Auslieferung |
| `/hook/JSLive/WS[/...]` | keines im Splitter | keine Antwort des Splitters | nicht erzwungen | historischer WebSocket-Pfad des WebHook-Control |
| `/hook/JSLive/getCSS` | bewusst ausgenommen | `Access-Control-Allow-Origin: *` | nicht erzwungen | nur lesendes, instanzbezogenes CSS |
| `/hook/JSLive/getGlobalConfig` | erforderlich, sofern konfiguriert | kein CORS-Header | nicht erzwungen | vollständige Splitter-Konfiguration einschließlich Kennwort |
| übrige dynamische Routen | erforderlich, sofern konfiguriert | `Access-Control-Allow-Origin: *` | nicht erzwungen | Lesen, Export oder bei `setData` Schreiben |

Für dynamische Routen gilt zusätzlich:

- Ein nicht leeres konfiguriertes Kennwort wird mit `hash_equals` exakt mit
  dem decodierten `pw`-Wert verglichen.
- Ein falsches oder fehlendes Kennwort liefert absichtlich eine leere
  HTTP-200-Antwort und erreicht kein Kindmodul. Dadurch wird der Grund der
  Ablehnung nicht nach außen unterschieden.
- Ein leeres konfiguriertes Kennwort deaktiviert die Prüfung vollständig. Das
  Konfigurationsformular verhindert dies bei regulärer Eingabe, der
  Laufzeitvertrag erlaubt es historisch dennoch.
- `getCSS` ist auch bei gesetztem Kennwort ohne `pw` erreichbar. Statische
  Assets und der WebSocket-Pfad durchlaufen die dynamische Kennwortprüfung
  ebenfalls nicht.
- Die Instanz-ID und alle modulspezifischen Werte werden als Queryparameter an
  das Kindmodul weitergereicht. Der aktuelle Browsercode verwendet auch für
  `setData` eine GET-Anfrage.

Wildcard-CORS erlaubt fremden Browser-Ursprüngen, Antworten zu lesen, sobald
sie die vollständige URL einschließlich Kennwort kennen. Es werden keine
Cookie-Credentials freigegeben. Der Splitter wertet `Origin` derzeit nicht aus
und besitzt keine Origin-Allowlist.

## Befehle der Kindmodule

| Modul | Nur lesende Befehle | Schreibender Befehl |
| --- | --- | --- |
| AdvTextfield | `exportConfiguration`, `getContend`, `getData` | `setData` |
| Calendar | `exportConfiguration`, `getContend`, `getData`, `getFeed`, `getCSS`, `getICS` | `setData` ist deklariert, aber nicht implementiert |
| Chart | `getConfiguration`, `getLanguage`, `getFonts`, `exportConfiguration`, `getContend`, `getUpdate`, `getData` | keiner |
| ColorPicker | `exportConfiguration`, `getContend`, `getData` | `setData` |
| Custom | `exportConfiguration`, `getContend`, `getData`, `loadFile` | `setData` |
| DateTimePicker | `exportConfiguration`, `getContend`, `getData` | `setData` |
| DoughnutPie | `exportConfiguration`, `getContend`, `getUpdate`, `getData` | keiner |
| Gauge | `exportConfiguration`, `getContend`, `getData` | keiner |
| Progressbar | `exportConfiguration`, `getContend`, `getData`, `getSVG`, `getFillImg` | keiner |
| RadarChart | `exportConfiguration`, `getContend`, `getUpdate`, `getData` | keiner |

Die historische Schreibweise `getContend` ist Teil des bestehenden Vertrags.
Der Splitter leitet den Routenbezeichner unverändert als `cmd` weiter; die
Kindmodule vergleichen ihre Befehle case-sensitiv.

## Schreibberechtigungen

Ein gültiges Splitter-Kennwort berechtigt nicht automatisch zum Schreiben auf
jedes Symcon-Objekt. Die Kindmodule begrenzen ihre Ziele zusätzlich:

- AdvTextfield schreibt ausschließlich die eigene Variable `Content`.
- ColorPicker akzeptiert nur Variablen aus seiner konfigurierten
  `Datasets`-Liste und ruft deren Aktion auf.
- DateTimePicker akzeptiert nur seine konfigurierte Variable. Ist eine
  Variablenaktion vorhanden, wird sie verwendet; andernfalls wird der Wert
  direkt gesetzt.
- Custom akzeptiert nur konfigurierte Objekte oder unmittelbar enthaltene
  Kinder und beachtet das konfigurierte Feld `ReadOnly`. Abhängig vom
  Objekttyp kann es Variablen direkt setzen, Medieninhalt schreiben, Links
  auflösen oder konfigurierte Skripte mit Browserparametern ausführen. Custom
  besitzt daher die weitreichendste Schreibgrenze.
- Calendar führt seinen deklarierten `setData`-Befehl derzeit nicht aus, weil
  die aufgerufene Methode fehlt. Das ist eine bekannte Bestandsabweichung und
  keine nutzbare Berechtigung.

Der Konfigurationsimport liegt außerhalb des Webhook-
Kennwortmodells. Er wird über öffentliche Modulfunktionen beziehungsweise die
lokale Symcon-Datenverbindung ausgeführt und benötigt eigene Laufzeit- und
Berechtigungsprüfungen.

## Verbindliche Betriebsregeln

- Das automatisch erzeugte, nicht leere Kennwort beibehalten oder durch ein
  eigenes starkes Kennwort ersetzen.
- Den Webhook außerhalb des lokalen Netzes nur über TLS und eine kontrollierte
  Netzfreigabe veröffentlichen.
- Vollständige Webhook-URLs wegen des enthaltenen `pw`-Werts wie Zugangsdaten
  behandeln und nicht in Tickets, Screenshots oder Logs übernehmen.
- Custom-Datasets minimal halten, `ReadOnly` für reine Anzeigen aktivieren und
  Skript-, Medien- sowie Linkziele besonders prüfen.
- Debug nur in einer geschützten Testumgebung aktivieren.

## Noch nicht geänderte Verträge

Eine Einschränkung von Wildcard-CORS, die Verlagerung des Kennworts aus der
URL, eine verpflichtende POST-Methode für Schreibzugriffe, die Entfernung der
`getCSS`-Ausnahme oder geänderte Fehlerstatus würden bestehende Browser-,
IPSView- und Template-Aufrufe beeinflussen. Solche Änderungen benötigen eine
eigene Migrationsentscheidung, aktualisierte Clients und eine Abnahme unter
IP-Symcon 9; sie sind nicht Teil dieser Charakterisierung.
