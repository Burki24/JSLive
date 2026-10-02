# chartjs-adapter-moment 1.0.1

Vendored on 2026-10-01 from the official npm distribution. Confirmed against
npm `latest` and the [upstream release](https://github.com/chartjs/chartjs-adapter-moment/releases/tag/v1.0.1).
This release declares Chart.js 4 support; its peer dependencies are Chart.js
`>=3.0.0` and Moment `^2.10.2`. JSLive uses Chart.js 4.5.1 and Moment 2.31.0.

## Reproduction

1. Download `https://registry.npmjs.org/chartjs-adapter-moment/-/chartjs-adapter-moment-1.0.1.tgz`.
2. Verify SHA-512 integrity:
   `sha512-Uz+nTX/GxocuqXpGylxK19YG4R3OSVf8326D+HwSTsNw1LgzyIGRo+Qujwro1wy6X+soNSnfj5t2vZ+r6EaDmA==`.
3. Copy `package/dist/chartjs-adapter-moment.min.js` and `package/LICENSE.md`
   unchanged into this directory. Preserve LF endings via `.gitattributes`.
   No rebuild, runtime CDN or additional package is required. Upstream no
   longer ships a source map and the bundle has no source-map reference.
4. Run `php tests/frontend-dependencies.php` and `node tests/moment-adapter.js`.

| File | SHA-256 (upstream and local) |
| --- | --- |
| `chartjs-adapter-moment.min.js` | `4ca6ddbc16c438c7decc60f16fbee9639d37277af609390f7794eb2729addb55` |
| `LICENSE.md` | `b4b8355c2cd2b18354980a0c6422181d7bd6e895d94ae88b3570e97c60eea03d` |

The old `../../chartjs-adapter-moment.js` stays unchanged at 1.0.0 for custom
templates. Only the three standard chart templates switch to this new path.
See `docs/FRONTEND_ASSET_MIGRATION.md` for update and rollback guidance.
