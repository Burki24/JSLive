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
