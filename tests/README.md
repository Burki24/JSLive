# JSLive tests

Run the project checks from the repository root:

```text
php tests/run.php
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
