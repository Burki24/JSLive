# Moment.js 2.31.0

Vendored on 2026-10-01 from the official npm distribution. The version was
confirmed against npm `latest` and the
[upstream release](https://github.com/moment/moment/releases/tag/2.31.0).
The standard templates load this local file; no CDN is used at runtime.

## Reproduction

1. Download `https://registry.npmjs.org/moment/-/moment-2.31.0.tgz` and verify
   its SHA-512 integrity:
   `sha512-0acOTfMiWOheYS4eoWb80yYMb/JLvVv9SHbs2PehaDzfUG0Bw855SKyk0IKTnPGa5+U2bmi3W68l1+sGLX/pvw==`.
2. Copy `package/min/moment.min.js`, `package/min/moment.min.js.map` and
   `package/LICENSE` into this directory. Add one final LF to the JavaScript
   and map (both upstream files lack a terminal newline). Do not otherwise
   modify or rebuild the distribution. The license is unchanged.
3. Keep LF line endings, enforced by `.gitattributes`. Verify local hashes
   using `php tests/frontend-dependencies.php`.

| File | Local SHA-256 | Upstream SHA-256 before terminal LF |
| --- | --- | --- |
| `moment.min.js` | `db2cf339996ce8387e2750fabfe5161c1418b204f6f197167a09cfe5d6655892` | `c111ad2e4447c253765e679bb4a40ba91a3e23d80b4b69d603a0fd25164cb9de` |
| `moment.min.js.map` | `3a971634e403e4b43b35b1f857e4f8750f0f17121beab40aacb1a63ad8557887` | `0dfe57f1b3b1fa6d1de2fae1a22b5896e10fffa1aebe44410242bb7e391d070d` |
| `LICENSE` | `64419cc68debfd9b7c27e9cd926c756181e0857709d2701094874e0fb1a41d28` | unchanged |

The MIT license is included in full. The core-only bundle retains the previous
locale scope (`en`); this step does not add locales or Moment Timezone.
The existing `../2.27.0/Moment.js` remains unchanged for custom templates.
It does not receive the upstream fixes in 2.31.0; custom templates should be
tested and migrated separately. See `docs/FRONTEND_ASSET_MIGRATION.md`.
