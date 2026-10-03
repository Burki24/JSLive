# Loading Bar / ldBar provenance

Verified on 2026-10-03. Existing public URLs and files remain unchanged.
All hashes below use CRLF-to-LF normalization.

## Actual JavaScript origin

`loading-bar.js` is byte-identical after normalization to `dist/loading-bar.js`
at [loadingio/loading-bar commit af5271ef7c675783fe870b5a60d6057f32f73e47](https://github.com/loadingio/loading-bar/commit/af5271ef7c675783fe870b5a60d6057f32f73e47),
dated 2019-10-20. This was the latest default-branch commit returned by the
GitHub API at audit time. Its package.json declares 0.1.1, but it is NOT the
published npm 0.1.1 distribution. There is no version banner in this bundle.

## npm comparison (not adopted)

- npm latest: `@loadingio/loading-bar` 0.1.1, published 2018-06-25.
- Tarball: <https://registry.npmjs.org/@loadingio/loading-bar/-/loading-bar-0.1.1.tgz>.
- Verified SHA-512 before in-memory extraction:
  `sha512-yplZliYit8MQIxKi6WX6HiuOzlcGD4OK+sJpzxI+ls4qQUm3ozJtrKObJvCGpv3zIdZ8XxFqUetWs2/iL2gWng==`.
- npm `dist/loading-bar.js` SHA-256:
  `5be1e6df4730e199a5868c6960e0ee7b05af61efa7cea160588971e61e162686`.
- npm `dist/loading-bar.css` SHA-256:
  `97dbd7a6029fa5ddbf7b3205545bafbd00d29978e47e5e9400b9f37556598a71`.
- No GitHub latest release was returned (HTTP 404); tags returned only 0.1.0.

Using npm latest would not be a forward update from the actual JSLive source.
No package installation or build-toolchain migration was performed.

## Local files

| File | SHA-256 (LF) |
| --- | --- |
| `loading-bar.js` | `6edf7feefaa7ae547fe7674ffb66d746e35a9e10a66fe6d2221a4ee3b7dcbe16` |
| `loading-bar.css` | `b42f3187ec8aa70fa17024cb5260ccd481bbaddf70ae0423b44c5c0fc2541131` |
| `LICENSE` | `ddefaa5e04ba32fe6f7a8b3a152b8f1f4499bb07e9d5cc727a6b584b22656d53` |

The MIT license matches the npm license after normalization.
Upstream commit CSS hash:
`f708f07ef55ee4f866553075ed1bccfdfcdcba146c3d9a4825871407f3ab5550`.
JSLive removes the default percent suffix, adds an empty inline `:before`
pseudo-element for the configured prefix, and includes extra blank lines.
These local CSS changes are retained; the template supplies prefix/suffix.

Maintenance is not demonstrated by this old commit, and passing local checks
does not establish future support. See `docs/LOADING_BAR_AUDIT.md` for evidence,
integration findings and limits.
