# Maintained streaming fork 3.6.0

Vendored on 2026-10-02 from [Burki24/chartjs-plugin-streaming](https://github.com/Burki24/chartjs-plugin-streaming).
This is a pinned development build, not an npm publication or tagged release.

- Distribution/metadata commit: [`054f9fd535b9002aa5f9d7ebcba0267d931e4a11`](https://github.com/Burki24/chartjs-plugin-streaming/commit/054f9fd535b9002aa5f9d7ebcba0267d931e4a11).
- Source change: [`ab87b7700ef888e2a803f85b93e8adcd06c0dc8f`](https://github.com/Burki24/chartjs-plugin-streaming/commit/ab87b7700ef888e2a803f85b93e8adcd06c0dc8f).
- [CI for the pinned commit](https://github.com/Burki24/chartjs-plugin-streaming/actions/runs/37031433191): successful when checked on 2026-10-02.
- Bundle source: `dist/@qultoltd/chartjs-plugin-streaming.min.js`; copied byte-for-byte, including its original banner. The historical package name is retained by the fork.
- License source: repository `LICENSE.md` (MIT), copied unchanged.
- Rebuild in that checkout with its pinned Node toolchain: `npm ci --ignore-scripts`, then `npm run verify` (includes the build). See the fork's lockfile and CI workflow for the build environment.
- No source map is included or referenced by this minified distribution.

| File | SHA-256 |
| --- | --- |
| `chartjs-plugin-streaming.min.js` | `ae787d33e000a9b0abc21eb95bd5d86613587e3ad7d6dcdb8e07dd271c46d058` |
| `LICENSE.md` | `f73f043ba331cd7327bfb1cbb186820651d878ce7802c82928d65cd9e3a1ecf5` |

Only the standard Chart template switches to this versioned URL. Chart.js 4.5.1,
Moment 2.31.0, its adapter 1.0.1 and Datalabels 2.2.0 remain unchanged.
The unversioned `../../chartjs-plugin-streaming.min.js` stays at 3.1.0 for custom
templates (SHA-256 `2e0ac91691bc76ff2c618c7d600a36cc3a10784ef34cd30ac968d72d6d15d839`).
The fork maintains the Chart.js-internal integration; lifecycle, tooltip and
quiet-update fixes are covered by its own tests. JSLive integration evidence and
remaining installed-runtime checks are recorded in `docs/STREAMING_INTEGRATION.md`.
