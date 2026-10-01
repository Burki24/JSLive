# Chart.js 4.5.1

Vendored on 2026-10-01 from the official npm distribution. Stable version checked
against the [upstream release](https://github.com/chartjs/Chart.js/releases/tag/v4.5.1)
and npm's `latest` metadata. No CDN is contacted by the built-in templates.

## Reproduction

1. Download `https://registry.npmjs.org/chart.js/-/chart.js-4.5.1.tgz`.
   Verify its SHA-512 integrity before extraction:
   `sha512-GIjfiT9dbmHRiYi6Nl2yFCq7kkwdkp1W/lp2J99rX0yo9tgJGn3lKQATztIjb5tVtevcBtIdICNWqlq5+E8/Pw==`.
2. Copy `package/dist/chart.umd.min.js`, its adjacent `.map` file and
   `package/LICENSE.md`. The JavaScript and license are unchanged. The map has
   one final LF added (upstream has no terminal newline); its JSON is unchanged.
   The original map SHA-256 is
   `8c328df49d295935c81d64d3b36d6ac1c20c385f1ba5b4df3ec12a26b3d64d9b`.
3. The bundle embeds `@kurkle/color` 0.3.2. Download
   `https://registry.npmjs.org/@kurkle/color/-/color-0.3.2.tgz`, verify
   `sha512-fuscdXJ9G1qb7W8VdHi+IwRqij3lBkosAm4ydQtEmbY58OzHXqQhvlxqEkoz0yssNVn38bcpRWgA9PP+OGoisw==`
   and copy its unchanged `package/LICENSE.md` to `KURKLE-LICENSE.md`.
4. Keep LF line endings (enforced by `.gitattributes`) and verify the following
   hashes with `php tests/frontend-dependencies.php`. No local compilation or
   minification is required; the source map retains the upstream source content.

Both packages are MIT-licensed. The complete texts are included below.

| File | SHA-256 |
| --- | --- |
| `chart.umd.min.js` | `48444a82d4edcb5bec0f1965faacdde18d9c17db3063d042abada2f705c9f54a` |
| `chart.umd.min.js.map` | `fecb66dd71acd07201280ad726d94a0e45f0c42762ac1d47fa1132f8af3bff25` |
| `LICENSE.md` | `41a84aa2caba645f966a18d9c2056b73e6d3a81d80bc0046bc0011a2634d4cce` |
| `KURKLE-LICENSE.md` | `2859c50313bad2ba77b081410c477beaf92b60ca13a77a248d89859a6dd6ac81` |

The unversioned sibling paths `../chart.js` (4.3.3) and `../chart.min.js`
(4.4.1) deliberately retain their previous content for custom templates.
Moment, the moment adapter, Datalabels and the vendored streaming fork are not
updated in this step. See `docs/FRONTEND_ASSET_MIGRATION.md` for migration and
rollback and `docs/SYCON_RUNTIME_MATRIX.md` for validation scope.
