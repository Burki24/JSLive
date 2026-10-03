# Loading Bar 0.1.1-jslive.1 (local patch revision)

The owner approved a versioned local patch on 2026-10-03. This is a JSLive
asset revision, not a new upstream release, npm package or library.json version.

## Base and license

The base is the existing, LF-normalized `../loading-bar.js`, identical to
`dist/loading-bar.js` at upstream commit
[`af5271ef7c675783fe870b5a60d6057f32f73e47`](https://github.com/loadingio/loading-bar/commit/af5271ef7c675783fe870b5a60d6057f32f73e47).
That commit declares package version 0.1.1 but postdates npm 0.1.1; do not
rebuild this patch from the older npm tarball. Full audit: `../SOURCES.md`.
The MIT license is included unchanged after LF normalization.

## Exact patch and reproduction

Only one line differs from the retained base:

```diff
-        v = doTransition
+        v = doTransition && dt < dur
```

In `transition.handler`, interpolate only before the animation duration has
elapsed; otherwise use the exact destination before existing precision and
range handling. This prevents extrapolation on a delayed final frame.
No changes to presets, image loading, CSS, public APIs or scheduling.
The template's raw/reversed value correction is separate from this bundle.

Reproduce by copying the LF-normalized base and applying this exact replacement
once. No npm installation, compilation or minification is required. The
frontend dependency test verifies both the full hash and this exact delta.
Future code changes require a new local patch revision and new evidence;
do not silently overwrite an already shipped revision.

| File | SHA-256 (LF) |
| --- | --- |
| Original `../loading-bar.js` | `6edf7feefaa7ae547fe7674ffb66d746e35a9e10a66fe6d2221a4ee3b7dcbe16` |
| Patched `loading-bar.js` | `6e79d56f0c289cdebd4c0351451a8a28a431cacc18405241be4530df4f2a4a86` |
| `LICENSE` | `ddefaa5e04ba32fe6f7a8b3a152b8f1f4499bb07e9d5cc727a6b584b22656d53` |

The old asset URL and the existing JSLive stylesheet remain unchanged.
Checks and rollback: `docs/LOADING_BAR_PATCH.md`.
