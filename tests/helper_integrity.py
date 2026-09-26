#!/usr/bin/env python3
"""Verify vendored helper files against the subscription and local manifest."""

from __future__ import annotations

import hashlib
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONFIG = json.loads((ROOT / ".helper-sync.json").read_text(encoding="utf-8"))
MANIFEST = json.loads((ROOT / "libs/helper/manifest.json").read_text(encoding="utf-8"))
VERSION_PATTERN = re.compile(r"@version\s+([0-9]+\.[0-9]+\.[0-9]+)")
IGNORED_PAYLOAD_FILES = {"manifest.json", "README.md"}

subscriptions = CONFIG.get("helpers", {})
entries = MANIFEST.get("helpers", {})
if set(subscriptions) != set(entries):
    raise SystemExit(
        "Helper subscription/manifest mismatch: "
        f"subscriptions={sorted(subscriptions)}, manifest={sorted(entries)}"
    )

referenced_paths: set[str] = set()


def verify_file(label: str, metadata: dict[str, object], expected_path: str | None = None) -> None:
    """Verify one helper or dependency plus its declared assets."""
    path_text = str(metadata["path"])
    if expected_path is not None and path_text != expected_path:
        raise SystemExit(f"Manifest path mismatch for {label}: {path_text} != {expected_path}")

    path = ROOT / path_text
    if not path.is_file():
        raise SystemExit(f"Missing vendored helper file for {label}: {path_text}")

    digest = hashlib.sha256(path.read_bytes()).hexdigest()
    if digest != metadata.get("sha256"):
        raise SystemExit(f"SHA-256 mismatch for {label}: {digest} != {metadata.get('sha256')}")

    if path.suffix == ".php":
        match = VERSION_PATTERN.search(path.read_text(encoding="utf-8"))
        version = metadata.get("version")
        if match is None or match.group(1) != version:
            source_version = match.group(1) if match is not None else None
            raise SystemExit(
                f"Version mismatch for {label}: source={source_version}, manifest={version}"
            )

    referenced_paths.add(path_text)

    assets = metadata.get("assets", [])
    if not isinstance(assets, list):
        raise SystemExit(f"Invalid asset declaration for {label}.")
    for asset in assets:
        if not isinstance(asset, dict):
            raise SystemExit(f"Invalid asset entry for {label}.")
        asset_path_text = str(asset["path"])
        asset_path = ROOT / asset_path_text
        if not asset_path.is_file():
            raise SystemExit(f"Missing vendored asset for {label}: {asset_path_text}")
        asset_digest = hashlib.sha256(asset_path.read_bytes()).hexdigest()
        if asset_digest != asset.get("sha256"):
            raise SystemExit(f"SHA-256 mismatch for {label} asset: {asset_path_text}")
        referenced_paths.add(asset_path_text)


for name, subscription in sorted(subscriptions.items()):
    if not isinstance(subscription, dict):
        raise SystemExit(f"Invalid helper subscription: {name}")
    metadata = entries[name]
    if not isinstance(metadata, dict):
        raise SystemExit(f"Invalid manifest entry: {name}")
    verify_file(name, metadata, str(subscription["target"]))

    dependencies = metadata.get("dependencies", [])
    if not isinstance(dependencies, list):
        raise SystemExit(f"Invalid dependency declaration for {name}.")
    for dependency in dependencies:
        if not isinstance(dependency, dict):
            raise SystemExit(f"Invalid dependency entry for {name}.")
        verify_file(f"{name}->{dependency['name']}", dependency)

actual_payload_paths = {
    path.relative_to(ROOT).as_posix()
    for path in (ROOT / "libs/helper").rglob("*")
    if path.is_file() and path.name not in IGNORED_PAYLOAD_FILES
}
if actual_payload_paths != referenced_paths:
    raise SystemExit(
        "Helper payload/manifest mismatch: "
        f"extra={sorted(actual_payload_paths - referenced_paths)}, "
        f"missing={sorted(referenced_paths - actual_payload_paths)}"
    )

readme = (ROOT / "libs/helper/README.md").read_text(encoding="utf-8")
for path_text in sorted(referenced_paths):
    helper_name = Path(path_text).name
    if Path(path_text).suffix == ".php" and f"`{helper_name}`" not in readme:
        raise SystemExit(f"Helper README does not document {helper_name}.")

print(
    f"Vendored helper integrity verified ({len(entries)} subscriptions, "
    f"{len(referenced_paths)} helper/asset files)."
)
