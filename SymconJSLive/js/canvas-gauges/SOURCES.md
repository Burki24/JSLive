# Canvas Gauges 2.1.7

Verified on 2026-10-03. The existing `gauge.min.js` is retained without edits,
including its public URL. After CRLF-to-LF normalization it is byte-identical
to `package/gauge.min.js` from the official npm distribution.

- Package: [canvas-gauges 2.1.7](https://registry.npmjs.org/canvas-gauges/2.1.7).
- Tarball: <https://registry.npmjs.org/canvas-gauges/-/canvas-gauges-2.1.7.tgz>.
- Tarball integrity verified before comparison:
  `sha512-z9cXBVTZdaUIOh32g21NU8gwxEeaxpEMvkZr9t8Y0QDbZiCDq05SJ17aIt+DM12oTJAlWGluN21D+bQ0NCv5GA==`.
- JavaScript SHA-256 (LF):
  `44b0a4ac54e0b980371e8788f7ce8215dab5a2181cda460fc344276b50385904`.
- License: complete MIT notice already embedded in the bundle header.
- No runtime dependencies, local modifications or source-map URL in this file.

## Maintenance evidence

The npm `latest` tag and [latest GitHub release](https://github.com/Mikhus/canvas-gauges/releases/tag/v2.1.7)
both identify 2.1.7. npm publication: 2020-04-09; GitHub release: 2020-04-10.
The default-branch commit returned by the GitHub API was
[`6b179f83b7f03926a4d58ac09e15525d19fad3d1`](https://github.com/Mikhus/canvas-gauges/commit/6b179f83b7f03926a4d58ac09e15525d19fad3d1),
dated 2020-04-09. These observations do not establish ongoing maintenance
or absence of defects. No newer stable version was available at audit time.

Retain the verified distribution for now; changing the gauge engine or taking
ownership of a fork requires a separate decision. JSLive's four templates
expose numerous library-specific presentation options, so another engine is
not a demonstrated drop-in replacement. See `docs/GAUGE_AUDIT.md` for coverage
and remaining risks.
