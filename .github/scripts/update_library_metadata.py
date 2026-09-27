#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any


VERSION_PATTERN = re.compile(r"^(\d+)\.(\d+)$")
SHA_PATTERN = re.compile(r"^[0-9a-fA-F]{40}$")


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Update Symcon library version, build and date metadata."
    )
    parser.add_argument("--file", required=True, type=Path)
    parser.add_argument("--sha", required=True)
    parser.add_argument("--date", required=True, type=int)
    parser.add_argument("--increment", required=True, type=int)
    parser.add_argument(
        "--base-version",
        help=(
            "Version to increment instead of the possibly overwritten version "
            "from the working-tree library.json."
        ),
    )
    return parser.parse_args()


def load_library(path: Path) -> dict[str, Any]:
    try:
        content = path.read_text(encoding="utf-8")
        data = json.loads(content)
    except FileNotFoundError as exc:
        raise SystemExit(f"Library file not found: {path}") from exc
    except json.JSONDecodeError as exc:
        raise SystemExit(f"Invalid JSON in {path}: {exc}") from exc

    if not isinstance(data, dict):
        raise SystemExit(f"{path} must contain a JSON object.")

    return data


def parse_version(version: object) -> tuple[int, int]:
    if not isinstance(version, str):
        raise SystemExit("library.json field 'version' must be a string.")

    match = VERSION_PATTERN.fullmatch(version)
    if match is None:
        raise SystemExit(
            "library.json field 'version' must use the format major.minor, "
            "for example 1.0."
        )

    return int(match.group(1)), int(match.group(2))


def calculate_version(current_version: object, increment: int) -> str:
    if increment < 1:
        raise SystemExit("--increment must be at least 1.")

    major, minor = parse_version(current_version)
    return f"{major}.{minor + increment}"


def calculate_build(commit_sha: str) -> int:
    if SHA_PATTERN.fullmatch(commit_sha) is None:
        raise SystemExit("--sha must be a complete 40-character Git commit SHA.")

    return int(commit_sha[:7], 16)


def main() -> None:
    args = parse_arguments()

    if args.date < 0:
        raise SystemExit("--date must be a non-negative Unix timestamp.")

    library = load_library(args.file)

    file_version = library.get("version")
    base_version = args.base_version if args.base_version is not None else file_version
    new_version = calculate_version(base_version, args.increment)
    if parse_version(file_version) > parse_version(new_version):
        new_version = file_version
    new_build = calculate_build(args.sha)

    library["version"] = new_version
    library["build"] = new_build
    library["date"] = args.date

    args.file.write_text(
        json.dumps(library, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
        newline="\n",
    )

    print(
        f"Updated {args.file}: "
        f"file version {file_version}, base version {base_version} -> {new_version}, "
        f"build {new_build}, date {args.date}"
    )


if __name__ == "__main__":
    main()
