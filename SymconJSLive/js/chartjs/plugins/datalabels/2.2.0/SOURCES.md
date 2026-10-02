# chartjs-plugin-datalabels 2.2.0

Vendored on 2026-10-02 from the official npm distribution. Version 2.2.0 is
still npm `latest` and the [latest upstream release](https://github.com/chartjs/chartjs-plugin-datalabels/releases/tag/v2.2.0)
at this check. It declares Chart.js `>=3.0.0` as a peer dependency; the release
explicitly adds Chart.js 4 support. JSLive uses Chart.js 4.5.1.

## Reproduction

1. Download `https://registry.npmjs.org/chartjs-plugin-datalabels/-/chartjs-plugin-datalabels-2.2.0.tgz`.
2. Verify SHA-512 integrity:
   `sha512-14ZU30lH7n89oq+A4bWaJPnAG8a7ZTk7dKf48YAzMvJjQtjrgg5Dpk9f+LbjCF6bpx3RAGTeL13IXpKQYyRvlw==`.
3. Copy `package/dist/chartjs-plugin-datalabels.min.js` and `package/LICENSE.md`
   unchanged into this directory. Preserve LF endings via `.gitattributes`.
   No rebuild or runtime CDN is required. The package has no source map and
   the minified bundle has no source-map reference.
4. Run `php tests/frontend-dependencies.php` and `php tests/webhook-routing.php`.

| File | SHA-256 (upstream and local) |
| --- | --- |
| `chartjs-plugin-datalabels.min.js` | `20c08f3d9c6d2ef76df6d6a6f1127c0013339fe32add24222276c398c6308c38` |
| `LICENSE.md` | `075bb10eabebc9356311ffca1b18fdd470fca8e5c2ce0f6e098430c81c59a624` |

## Historical compatibility asset

`../../chartjs-plugin-datalabels.min.js` is not the official minified release.
It is the unminified 2.2.0 bundle with a 2023 copyright banner and three local
changes: the ArcElement, PointElement and BarElement checks use
`el.constructor.name` instead of upstream `instanceof chart_js.<Element>`.
The rest of the code matches the npm unminified distribution. Its LF-normalized
SHA-256 is `b990332d6a689719ce37714c499f51523c4ffa830b426948a60ebe9b020a25cc`.
Its original build provenance is unknown; do not describe it as an unchanged
official release. It stays untouched for custom templates.

Only the three standard chart templates switch to the official versioned path.
The existing formatters and styling remain unchanged. See
`docs/FRONTEND_ASSET_MIGRATION.md` and `docs/SYCON_RUNTIME_MATRIX.md` for
compatibility, browser evidence and rollback guidance.
