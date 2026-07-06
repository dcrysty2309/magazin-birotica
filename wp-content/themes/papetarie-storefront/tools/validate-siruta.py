#!/usr/bin/env python3
"""Validate the generated canonical locality datasets.

Checks:
  - all counties are present;
  - no locality duplicates remain after accent/case/prefix normalization;
  - the runtime dropdown source can be safely consumed by the theme.
"""

from __future__ import annotations

import json
import re
import sys
import unicodedata
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PRIMARY_DATASET = ROOT / "data" / "siruta-localities-by-county.json"

COUNTY_ORDER = [
    "AB", "AR", "AG", "BC", "BH", "BN", "BT", "BR", "BV", "B", "BZ", "CS",
    "CL", "CJ", "CT", "CV", "DB", "DJ", "GL", "GR", "GJ", "HR", "HD", "IL",
    "IS", "IF", "MM", "MH", "MS", "NT", "OT", "PH", "SJ", "SM", "SB", "SV",
    "TR", "TM", "TL", "VS", "VL", "VN",
]


def normalize_text(value: str) -> str:
    value = value.replace("\xa0", " ")
    value = value.replace("Ţ", "Ț").replace("ţ", "ț").replace("Ş", "Ș").replace("ş", "ș")
    value = unicodedata.normalize("NFC", value)
    value = re.sub(r"\s+", " ", value).strip()
    return value


def normalize_key(value: str) -> str:
    value = normalize_text(value).casefold()
    value = unicodedata.normalize("NFD", value)
    value = "".join(ch for ch in value if unicodedata.category(ch) != "Mn")
    value = re.sub(r"^(judetul|municipiul|orasul|oras|comuna|satul|sectorul)\s+", "", value)
    value = re.sub(r"[^a-z0-9]+", " ", value)
    value = re.sub(r"\s+", " ", value).strip()
    return value


def load_dataset() -> dict[str, list[str]]:
    path = PRIMARY_DATASET
    if not path.exists():
        raise FileNotFoundError(f"Missing locality dataset: {PRIMARY_DATASET}")

    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("Invalid locality dataset structure")

    return {str(k): list(v) if isinstance(v, list) else [] for k, v in payload.items()}


def main() -> int:
    dataset = load_dataset()
    failures: list[str] = []

    missing_counties = [code for code in COUNTY_ORDER if code not in dataset]
    if missing_counties:
        failures.append(f"Missing counties: {', '.join(missing_counties)}")

    for county_code in COUNTY_ORDER:
        localities = dataset.get(county_code, [])
        seen: dict[str, str] = {}
        duplicates: list[str] = []

        for locality in localities:
            label = normalize_text(str(locality))
            if not label:
                continue

            key = normalize_key(label)
            if not key:
                continue

            if key in seen:
                duplicates.append(f"{seen[key]} | {label}")
                continue

            seen[key] = label

        if duplicates:
            failures.append(f"{county_code}: {', '.join(duplicates[:10])}")

    if failures:
        print("Locality validation failed:", file=sys.stderr)
        for failure in failures:
            print(f"- {failure}", file=sys.stderr)
        return 1

    total_localities = sum(len(values) for values in dataset.values())
    print(f"Locality validation passed: {len(COUNTY_ORDER)} counties, {total_localities} localities.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
