#!/usr/bin/env python3
"""Compare the canonical locality list with an eMAG-style export.

Usage:
  python3 tools/compare-siruta-with-emag.py --emag /path/to/emag-export.csv

Supported export formats:
  - CSV/TSV with county and locality columns;
  - JSON as either a county -> localities mapping or a list of locality records.

The comparison is accent-insensitive, case-insensitive and prefix-insensitive
(`Oraș`, `Municipiul`, `Comuna`, `Satul`, etc.). The goal is to verify that the
canonical checkout dataset matches the reference export county by county.
"""

from __future__ import annotations

import argparse
import csv
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

COUNTY_NAME_TO_CODE = {
    "alba": "AB",
    "arad": "AR",
    "arges": "AG",
    "bacau": "BC",
    "bihor": "BH",
    "bistrita-nasaud": "BN",
    "botosani": "BT",
    "braila": "BR",
    "brasov": "BV",
    "bucuresti": "B",
    "buzau": "BZ",
    "caras-severin": "CS",
    "calarasi": "CL",
    "cluj": "CJ",
    "constanta": "CT",
    "covasna": "CV",
    "dambovita": "DB",
    "dolj": "DJ",
    "galati": "GL",
    "giurgiu": "GR",
    "gorj": "GJ",
    "harghita": "HR",
    "hunedoara": "HD",
    "ialomita": "IL",
    "iasi": "IS",
    "ilfov": "IF",
    "maramures": "MM",
    "mehedinti": "MH",
    "mures": "MS",
    "neamt": "NT",
    "olt": "OT",
    "prahova": "PH",
    "salaj": "SJ",
    "satu-mare": "SM",
    "sibiu": "SB",
    "suceava": "SV",
    "teleorman": "TR",
    "timis": "TM",
    "tulcea": "TL",
    "vaslui": "VS",
    "valcea": "VL",
    "vrancea": "VN",
}


def normalize_text(value: str) -> str:
    value = value.replace("\xa0", " ")
    value = value.replace("Ţ", "Ț").replace("ţ", "ț").replace("Ş", "Ș").replace("ş", "ș")
    value = unicodedata.normalize("NFC", value)
    value = re.sub(r"\s+", " ", value).strip()
    return value


def normalize_locality_label(value: str) -> str:
    value = normalize_text(value)
    value = value.replace("Ţ", "Ț").replace("ţ", "ț").replace("Ş", "Ș").replace("ş", "ș")
    value = re.sub(
        r"^(JUDEȚUL|JUDEŢUL|JUDETUL|MUNICIPIUL|ORAȘUL|ORAŞUL|ORASUL|ORAȘ|ORAŞ|ORAS|COMUNA|SATUL|SECTORUL)\s+",
        "",
        value,
        flags=re.IGNORECASE,
    ) or value
    value = normalize_text(value)
    if value.upper() in {"BUCUREȘTI", "BUCURESTI"}:
        return "București"
    return value.title()


def normalize_locality_key(value: str) -> str:
    value = normalize_text(value)
    value = value.replace("Ţ", "Ț").replace("ţ", "ț").replace("Ş", "Ș").replace("ş", "ș")
    value = value.casefold()
    value = unicodedata.normalize("NFD", value)
    value = "".join(ch for ch in value if unicodedata.category(ch) != "Mn")
    value = re.sub(r"^(judetul|municipiul|orasul|oras|comuna|satul|sectorul)\s+", "", value)
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def normalize_county_key(value: str) -> str:
    value = normalize_text(value).casefold()
    value = unicodedata.normalize("NFD", value)
    value = "".join(ch for ch in value if unicodedata.category(ch) != "Mn")
    value = re.sub(r"^(judetul|județul|judet|municipiul bucuresti|municipiul|orasul|oras|comuna|satul)\s+", "", value)
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def county_to_code(value: str) -> str | None:
    candidate = normalize_text(value)
    candidate_code = candidate.upper().replace("JUDETUL ", "").replace("JUDEȚUL ", "")
    if candidate_code in COUNTY_ORDER:
        return candidate_code

    normalized = normalize_county_key(candidate)
    if normalized in COUNTY_NAME_TO_CODE:
        return COUNTY_NAME_TO_CODE[normalized]

    return None


def load_our_dataset() -> dict[str, list[str]]:
    path = PRIMARY_DATASET
    if not path.exists():
        raise FileNotFoundError(f"Missing locality dataset: {PRIMARY_DATASET}")

    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("Invalid locality dataset structure")

    return {str(code): list(values) if isinstance(values, list) else [] for code, values in payload.items()}


def load_emag_rows(path: Path) -> list[dict[str, str]]:
    if not path.exists():
        raise FileNotFoundError(f"Missing eMAG export: {path}")

    suffix = path.suffix.lower()
    if suffix == '.json':
        payload = json.loads(path.read_text(encoding='utf-8'))
        if isinstance(payload, dict):
            rows: list[dict[str, str]] = []
            for county_key, localities in payload.items():
                county_code = county_to_code(str(county_key)) or str(county_key).upper()
                if isinstance(localities, list):
                    for item in localities:
                        if isinstance(item, dict):
                            row = dict(item)
                            row.setdefault('county_code', county_code)
                            rows.append({k: str(v) for k, v in row.items()})
                        else:
                            rows.append({'county_code': county_code, 'locality': str(item)})
            return rows

        if isinstance(payload, list):
            rows = []
            for item in payload:
                if isinstance(item, dict):
                    rows.append({k: str(v) for k, v in item.items()})
            return rows

        raise ValueError('Unsupported eMAG JSON structure')

    with path.open('r', encoding='utf-8', newline='') as handle:
        sample = handle.read(4096)
        handle.seek(0)
        try:
            dialect = csv.Sniffer().sniff(sample, delimiters=';,|\t')
        except csv.Error:
            dialect = csv.excel
            dialect.delimiter = ';'

        reader = csv.DictReader(handle, dialect=dialect)
        return [{k: str(v) for k, v in row.items()} for row in reader]


def extract_row_county_code(row: dict[str, str]) -> str | None:
    candidate_keys = (
        'county_code',
        'county',
        'county_name',
        'judet',
        'județ',
        'judet_code',
        'county_abbr',
    )
    for key in candidate_keys:
        if key in row and str(row[key]).strip():
            code = county_to_code(str(row[key]))
            if code:
                return code
    return None


def extract_row_locality_label(row: dict[str, str]) -> str:
    candidate_keys = (
        'locality',
        'locality_name',
        'localitate',
        'denloc',
        'name',
        'city',
        'oras',
        'town',
    )
    for key in candidate_keys:
        value = str(row.get(key, '')).strip()
        if value:
            return value

    # Fallback to any readable field if the export uses a custom schema.
    for value in row.values():
        candidate = str(value).strip()
        if candidate:
            return candidate

    return ''


def build_emag_dataset(path: Path) -> dict[str, dict[str, set[str]]]:
    rows = load_emag_rows(path)
    grouped: dict[str, dict[str, set[str]]] = {code: {'keys': set(), 'labels': set()} for code in COUNTY_ORDER}

    for row in rows:
        county_code = extract_row_county_code(row)
        if not county_code or county_code not in grouped:
            continue

        locality_label = extract_row_locality_label(row)
        if not locality_label:
            continue

        normalized_label = normalize_locality_label(locality_label)
        locality_key = normalize_locality_key(locality_label)
        if not normalized_label or not locality_key:
            continue

        grouped[county_code]['keys'].add(locality_key)
        grouped[county_code]['labels'].add(normalized_label)

    return grouped


def compare_datasets(
    our_dataset: dict[str, list[str]],
    emag_dataset: dict[str, dict[str, set[str]]],
    county_codes: list[str],
) -> list[str]:
    failures: list[str] = []

    for county_code in county_codes:
        our_localities = our_dataset.get(county_code, [])
        emag_keys = emag_dataset.get(county_code, {}).get('keys', set())

        our_keys = {}
        for locality in our_localities:
            label = normalize_locality_label(str(locality))
            if not label:
                continue
            key = normalize_locality_key(label)
            if key and key not in our_keys:
                our_keys[key] = label

        missing_in_ours = sorted(
            emag_keys.difference(our_keys.keys()),
            key=lambda key: key,
        )
        extra_in_ours = sorted(
            set(our_keys.keys()).difference(emag_keys),
            key=lambda key: key,
        )

        if missing_in_ours or extra_in_ours:
            lines = [f"{county_code}:"]
            if missing_in_ours:
                lines.append(f"  missing in theme ({len(missing_in_ours)}): {', '.join(missing_in_ours[:15])}")
            if extra_in_ours:
                lines.append(f"  extra in theme ({len(extra_in_ours)}): {', '.join(extra_in_ours[:15])}")
            failures.append("\n".join(lines))

    return failures


def main() -> int:
    parser = argparse.ArgumentParser(description='Compare the canonical locality dataset with an eMAG export.')
    parser.add_argument('--emag', required=True, help='Path to the eMAG export file (CSV or JSON).')
    parser.add_argument('--our-data', default=str(PRIMARY_DATASET), help='Path to the canonical theme dataset.')
    parser.add_argument(
        '--county',
        action='append',
        default=[],
        help='Optional county code or county name to limit the comparison (can be passed multiple times).',
    )
    args = parser.parse_args()

    our_path = Path(args.our_data)
    emag_path = Path(args.emag)

    if not our_path.exists():
        print(f"Missing canonical dataset: {our_path}", file=sys.stderr)
        return 1

    try:
        our_dataset = load_our_dataset()
        emag_dataset = build_emag_dataset(emag_path)
    except (FileNotFoundError, ValueError, json.JSONDecodeError, csv.Error) as exc:
        print(f"Could not load datasets: {exc}", file=sys.stderr)
        return 1

    selected_counties = []
    for raw_county in args.county:
        code = county_to_code(raw_county)
        if code and code not in selected_counties:
            selected_counties.append(code)

    if selected_counties:
        our_dataset = {code: our_dataset.get(code, []) for code in selected_counties}
        emag_dataset = {code: emag_dataset.get(code, {'keys': set(), 'labels': set()}) for code in selected_counties}

    comparison_counties = selected_counties or COUNTY_ORDER
    failures = compare_datasets(our_dataset, emag_dataset, comparison_counties)

    if failures:
        print('Locality vs eMAG comparison failed:', file=sys.stderr)
        for failure in failures:
            print(f'- {failure}', file=sys.stderr)
        return 1

    total_localities = sum(len(values) for values in our_dataset.values())
    print(
        f"Locality vs eMAG comparison passed: {len(comparison_counties)} counties, {total_localities} canonical localities matched."
    )
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
