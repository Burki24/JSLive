# Bestehende Ausgabe-, IPSView- und Link-Vertraege

Stand: 03.10.2026, Ausgangspunkt Library 0.89 (`7a419a9` / `1729c19`).
Phase 5, Schritt 1: Charakterisierung des vorhandenen Verhaltens, keine
Darstellungs- oder Datenmigration. Die neun Kindmodule nutzen die gemeinsame
Basisklasse `SymconJSLive/libs/JSLiveModule.php` und den JSLive-Splitter.

## Ausgabevariablen

| Vertrag | `IPSView` | `Output` |
| --- | --- | --- |
| Bedingung | `CreateIPSView` im Kind **und** Splitter aktiv | `CreateOutput` im Kind aktiv, unabhaengig von IPSView |
| Typ / Darstellung | String, `VARIABLE_PRESENTATION_LEGACY`, `~HTMLBox` | String, `VARIABLE_PRESENTATION_LEGACY`, `~HTMLBox` |
| Wert | vollstaendiges, vom Splitter verarbeitetes HTML | Iframe mit `GetLocalLink()` als Quelle |
| Sichtbarkeit | beim erstmaligen Anlegen verborgen | vom Modul nicht verborgen |
| Wiederholung | bestehende Variable weiterverwenden, nur Wert erneuern | erneut mit demselben Ident registrieren, Wert erneuern |
| Abschalten | Variable beim naechsten `UpdateOutput()` entfernen | vorhandene Variable beim naechsten `UpdateIframe()` entfernen |

Beide Registrierungen verwenden Position 0 und den uebersetzten Ident als
Namen. `UpdateOutput()` und `UpdateIframe()` arbeiten nur bei Kindstatus 102.
Bei anderem Status bleiben Ausgabevariablen, Buffer und Parent-Anfragen durch
diese beiden Methoden unveraendert. Die Methoden aktualisieren jeweils ihren
eigenen Ausgabepfad; `UpdateOutput()` allein erzeugt kein Iframe.

`IFrameHeight = 0` erzeugt `style="width:100%;height:100%;"`. Ein anderer Wert
wird als `height`-Attribut eingesetzt, zusaetzlich zu `width="100%"`.
Beide Varianten behalten `frameborder="0"` und `scrolling="no"` bei.
Abschalten und spaeteres Wiedereinschalten kann neue Variablen-IDs erzeugen;
eine Erhaltung geloeschter IDs ist kein zugesicherter Vertrag.

## HTML, Cache und Datenfluss

Das Kind sendet den DataID-Umschlag
`{751AABD7-E31D-024C-5CC0-82AC15B84095}` mit einem JSON-String in `Buffer`.
Fuer `Type = UpdateHtml` enthaelt dieser `Html`, `InstanceID` und `ViewPort`.
Der Splitter ersetzt `{GLOBAL}`, `{ADDRESS}`, `{PASSWORD}`, `{INSTANCE}` und
`{VIEWPORT}` und antwortet mit `output` sowie seinem `ipsview`-Schalter.
Der Viewport-Platzhalter wird anhand des uebergebenen Kind-Schalters durch
das Meta-Element mit `viewport_content` oder einen Leerstring ersetzt.

`UpdateOutput()` setzt den historischen Buffer `LastModifed` auf ein
GMT-HTTP-Datum. Kind-`EnableCache` bestimmt, ob der HTML-Buffer `Output` die
verarbeitete Antwort oder einen Leerstring enthaelt. Dieser Buffer ist nicht
die gleichnamige Iframe-Variable. IPSView erhaelt bei aktivierten Schaltern
auch ohne Cache das verarbeitete HTML.

`GetOutput()` liefert unveraendert die JSON-Felder `Contend`, `lastModify`,
`EnableCache`, `EnableViewport` und `InstanceID`:

- Cache gefuellt: vorhandenes HTML/Datum liefern, keine Neuerzeugung.
- Cache leer und aktiviert: `UpdateOutput()` und `UpdateIframe()` ausfuehren.
- Cache deaktiviert: frisches Kind-HTML liefern, Splitter-Platzhalter noch
  enthalten; keine Variablenaktualisierung durch diesen Leseaufruf.
  Der Webhook ersetzt die Platzhalter anschliessend bei der Auslieferung.

Kind-`EnableCache` (HTML-Buffer) und Splitter-`enableCache` (HTTP-Cache-Header)
sind verschiedene Properties. Schreibweisen einschliesslich `Contend`,
`LastModifed` und `ViewPort` werden nicht beilaufig korrigiert.
`ApplyChanges()` ruft die beiden Aktualisierungsmethoden auf; die neuen Tests
pruefen deren Ausgabeanteil, nicht einen vollstaendigen Symcon-Lebenszyklus.

## Links

| Methode | Ergebnis |
| --- | --- |
| `GetLink()` | `Address` + `/hook/JSLive?Instance=<ID>` |
| `GetLocalLink()` | `/hook/JSLive?Instance=<ID>`, bei `Iframe_useFullLink` mit `Address` davor |
| `GetConfigurationLink(false)` | `Address` + `/hook/JSLive/exportConfiguration?Instance=<ID>` |
| `GetConfigurationLink(true)` | Exportlink mit angehaengtem `&scripts=1` |

Ein nach der bestehenden PHP-`empty()`-Pruefung nichtleeres Kennwort wird
jeweils als `&pw=` mit PHP-`urlencode()`
angehaengt. Solche Links enthalten Zugangsdaten und duerfen nicht in Logs,
Screenshots oder Beispielkonfigurationen veroeffentlicht werden. Tests nutzen
ausschliesslich synthetische IDs, `.example` und erkennbare Testkennwoerter.

Bei fehlender Adresse liefern `GetLink()` und der Exportlink den bestehenden
Text `No Address in Main modul Set!`. `withScript=true` haengt auch daran
`&scripts=1` an. `GetLocalLink()` bleibt bei leerer Adresse relativ, auch im
Full-Link-Modus. Liefert der Parent `false`, geben alle drei Kindmethoden einen
Leerstring zurueck, beim Export auch mit `withScript=true`.
Diese historischen Randfaelle sind dokumentierter Bestand, keine neue
Gestaltungsempfehlung. Eine spaetere Korrektur benoetigt einen eigenen Schritt.

## Nachweise und Grenzen

`php tests/visualization-output-contracts.php` verwendet den echten
`JSLiveModule`- und Splitter-Code. Nur Symcon-Properties, Variablen, Buffer und
der Transport zum Parent werden durch prozesslokale Testdoubles bereitgestellt.
Es wird kein Symcon kontaktiert oder veraendert. Der Test ist in
`php tests/run.php` und damit in die bestehende PHP-8.5-CI eingebunden.

Abgedeckt sind die acht Kombinationen der drei Ausgabeschalter, wiederholte
Aktualisierung ohne Entfernen/Neuanlegen eingeschalteter Variablen, getrenntes
Abschalten, Statussperre, Cache-Treffer/-Fehlschlag/-Deaktivierung, Viewport,
beide Iframe-Hoehenvarianten und die Linkmatrix einschliesslich Kodierung,
Exportoption und fehlendem Parent. Die numerische ID-Vergabe ist im Testdouble
modelliert; die echte Symcon-Registrierung ist damit nicht eigenstaendig belegt.

Lokal am 03.10.2026 bestanden: gezielter Vertragstest, `php tests/run.php`,
Syntaxpruefung aller 46 Projekt-PHP-Dateien ohne vendorte Helper,
PHP-CS-Fixer-Trockenlauf, `.style/json-check.php` und `git diff --check`.
PHP-Version: 8.5.10. PHP-CS-Fixer meldet lediglich den bekannten Hinweis auf
die nicht vorhandene `composer.json`. Die CI dieses Schritts steht bis zum
Push aus; die vorherige gruene CI gehoert zum Loading-Bar-Patch.

Ergaenzend pruefen `public-contracts.php` die Deklarationen aller Module,
`adv-textfield-rendering.php` reale Vorlagen und Kind-Rendering sowie
`webhook-routing.php` die bestehenden HTTP-Routen. Der neue Harness ist kein
vollstaendiger Template-Startup-, Browser-, Transport- oder IPSView-Test und
kein Nachweis aller modulindividuellen Darstellungen.
Eine echte IPSView-Abnahme bleibt mangels Testlizenz offen. Es wurden keine
Variablen in einer Installation angelegt, umgestellt oder entfernt.

Naechster Schritt: ein einfaches Pilotmodul fuer eine separat freizugebende
native WebContent-/HTML-SDK-Kachel auswaehlen. Bestehende Idents, Legacy-
Ausgaben, Linkmethoden und eigene Templates bleiben dabei erhalten; dieser
Schritt waehlt noch keinen Piloten und implementiert keine neue Kachel.
