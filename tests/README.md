# JSLive tests

Run the project checks from the repository root:

```text
php tests/run.php
```

Der Runner verwendet neben PHP auch Python 3 fuer die Metadaten- und
Helper-Pruefungen sowie Node.js fuer die JavaScript-Renderingvertraege.

The official Symcon style configuration is available through the `.style`
submodule. Its local read-only check is:

```text
php-cs-fixer fix --dry-run --diff --using-cache=no --allow-risky=yes --config=.style/.php-cs-fixer.php
```

The legacy style migration is tracked separately from behavioral changes. The
style check becomes a required workflow only after that mechanical migration is
reviewed and the command succeeds for the complete configured file set.

All PHP sources below `tests` already pass the official configuration. This
subset can be checked independently with:

```text
php-cs-fixer fix --dry-run --using-cache=no --allow-risky=yes --config=.style/.php-cs-fixer.php --path-mode=intersection tests
```

## Public contract snapshot

`fixtures/public-contracts.json` is the reviewed baseline for externally
relevant declarations. `public-contracts.php` compares it with the current
library and module metadata as well as the registered properties, variables,
actions, public PHP methods and established hook paths.

Do not regenerate the fixture merely to make the test pass. A changed contract
must first be classified as one of the following:

- an unintended regression, which requires a code correction;
- an intentional compatible extension, which requires documentation and an
  explicit fixture update;
- a breaking change, which additionally requires a migration decision and
  migration tests.

The snapshot records declarations, not their runtime behavior. It therefore
does not replace Symcon runtime, webhook, rendering, import/export or migration
tests.

## Webhook routing harness

`webhook-routing.php` loads the real splitter with a minimal synthetic Symcon
boundary. It verifies the established child DataID and JSON envelope, password
and instance gates, the direct global-configuration response and the historical
default command spelling `getContend`.

The harness intentionally does not assert sensitive debug output, unrestricted
asset paths or other known security risks. Those behaviors are not compatibility
requirements and may be tightened without updating a characterization fixture.

## Connect address harness

`connect-address.php` prueft den oeffentlichen Formularcallback und den
optionalen Startpfad des Splitters. Der Test sichert die korrekte
Connect-Control-GUID und Instanz-ID, die unveraenderte Ausgabe der gefundenen
URL, den Schutz bereits konfigurierter Adressen sowie das Verhalten ohne
Connect-Control-Instanz ab.

## Debug log masking harness

`debug-log-access.php` ruft den bestehenden oeffentlichen Diagnosepfad mit
einer synthetischen Symcon-Logdatei auf. Der Test belegt, dass harmlose
Logeintraege und Queryparameter weiterhin ausgegeben werden, waehrend
Passwortfelder sowie der JSLive-Parameter `pw` sichtbar maskiert bleiben.

## Configuration transfer harness

`configuration-transfer.php` verifies complete and form-filtered exports,
including nested list-column metadata. It also proves that empty, malformed,
incomplete and foreign-module imports are rejected without applying an instance
configuration. A valid import updates known properties, preserves omitted
properties, ignores unknown fields, records the uploaded payload and applies the
target instance exactly once.

## Configuration form harness

`configuration-form.php` loads the real Chart form through its public
configuration-form entry point. It verifies initial and live updates for the
shared `viewlevel` and `requireItem` metadata, including nested fields,
generated non-exported technical names and controller `onChange` wiring.
Random technical names are checked by behavior and are deliberately not stored
as fixtures.

## AdvTextfield rendering harness

`adv-textfield-rendering.php` exercises the bundled and script-provided
AdvTextfield templates and the public `getContend` data path. It verifies module
and parent placeholder stages, CSS color conversion, unique font inclusion,
current-value escaping and the cache-disabled, cache-hit and cache-rebuild
responses. The test asserts stable rendering contracts instead of storing the
complete generated HTML as a snapshot.

## Progressbar rendering harness

`progressbar-rendering.js` fuehrt die reale `LoadBarConfig()`-Funktion aus der
gebuendelten Progressbar-Vorlage aus. Der Test sichert ab, dass vollstaendige
SVG-Grafiken den unterstuetzten Fill-Modus verwenden, waehrend Presets und
benutzerdefinierte Pfade ihre konfigurierte Stroke-Darstellung behalten.
