# Canvas Gauges 2.1.7 — JSLive patch 1

Local asset revision, 2026-10-03. Not an upstream release or a new maintenance
fork. Upstream version identifiers remain 2.1.7. The original bundle and URL
one directory above remain unchanged for custom templates.

Base: verified npm canvas-gauges 2.1.7, LF SHA-256
`44b0a4ac54e0b980371e8788f7ce8215dab5a2181cda460fc344276b50385904`.
Package integrity and MIT provenance: [original SOURCES.md](../SOURCES.md).
The complete upstream MIT license remains in the patched bundle header.

Patched `gauge.min.js` SHA-256 (LF):
`2cde1666441e112088cf3094f29e187f0d818ce4a2b9939091b890e9f132520e`.

## Reproduction and exact scope

`node scripts/build-gauge-patch.js` emits the asset to stdout.
`node scripts/build-gauge-patch.js --check` verifies the checked-in asset.
No compiler, network or package installation required. The script verifies the
original hash, replaces the BaseGauge value setter, changes one RadialGauge
target handoff, adds the local-revision banner and normalizes LF/final newline.
All other bundle content remains unchanged. The setter is intentionally readable.

The old setter retained a pending target when a different update interrupted
animation. It also cancelled a repeated target and ignored updates to the
currently rendered intermediate value. The new setter cancels obsolete frames,
starts from the current displayed value and records the latest target. Repeated
pending targets keep animating. Immediate updates cancel the prior animation.
RadialGauge passes its original target separately from its shortest-path angle;
the final value therefore stays normalized even when interpolation crosses north.

Relevant upstream implementation:
[BaseGauge](https://github.com/Mikhus/canvas-gauges/blob/6b179f83b7f03926a4d58ac09e15525d19fad3d1/lib/BaseGauge.js),
[RadialGauge](https://github.com/Mikhus/canvas-gauges/blob/6b179f83b7f03926a4d58ac09e15525d19fad3d1/lib/RadialGauge.js).

The scheduler, easing functions, geometry, formatting and template conversion
remain unchanged. Regression coverage and deployment limits: `docs/GAUGE_AUDIT.md`.
Future changes use a new asset revision; do not overwrite a released patch.
