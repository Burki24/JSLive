# jQuery 4.0.0

Checked and vendored on 2026-10-03. The npm registry's `latest` tag and the
official [download page](https://jquery.com/download/) identify 4.0.0.

- Package: [jquery 4.0.0](https://registry.npmjs.org/jquery/-/jquery-4.0.0.tgz).
- Tarball SHA-512 integrity, verified before extraction:
  `sha512-TXCHVR3Lb6TZdtw1l3RTLf8RBWVGexdxL6AC8/e0xZKEpBflBsjh9/8LXw+dkNFuOyW9B7iB3O1sP7hS0Kiacg==`.
- Files: `dist/jquery.min.js`, `dist/jquery.min.map`, root `LICENSE.txt` (MIT).
- Full browser build, not Slim: JSLive requires Ajax and jqXHR/Deferred.
- No runtime code changes. A final LF is appended to the JavaScript and map
  (absent in the npm originals), following the repository's text-file convention.
  The license is byte-identical. LF checkout rules keep these hashes stable.
- Original JavaScript SHA-256: `39a546ea9ad97f8bfaf5d3e0e8f8556adb415e470e59007ada9759dce472adaa`.
- Original map SHA-256: `7fd7f832c10dfc0962dca1ba015cdefa69a310741edf89d28914184bd5ba1ceb`.
- The npm bundle has no `sourceMappingURL` directive. The original map is
  retained as an upstream artifact, not automatically loaded by the browser;
  it has no embedded `sourcesContent`. No debugging source file is vendored.

## Vendored SHA-256

| File | SHA-256 |
| --- | --- |
| `jquery.min.js` | `2526ee3df5d907ad4374102b8cfbec025e5992f99fe9abbb0e9b22cb23beb861` |
| `jquery.min.map` | `e2f9377576b10edc8ca4ec3e6399b59ae1bd3a89b9248ce9026098377d70406c` |
| `LICENSE.txt` | `d4db9ebe6f29f5168eac45ad713f055623ac5d0dcd5ba92da23d650ae012020d` |

The old `../../jquery.min.js` remains at 3.6.0 for custom templates (LF-normalized
SHA-256 `ff1523fb7389539c84c65aba19260648793bb4f5e29329d2ee8804bc37a3fe6e`).
No jQuery Migrate runtime dependency is added. See
`docs/JQUERY_MIGRATION.md` for the approved browser-support change and tests.
