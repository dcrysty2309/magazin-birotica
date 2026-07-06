#!/usr/bin/env python3
"""Build the canonical Romania county/locality datasets from the merged eMAG sources.

Inputs:
  - romania-localitati-emag-recovered.csv
  - romania-localitati-emag-rest-de-judete.csv

Outputs:
  - data/siruta-counties.json
  - data/siruta-localities-by-county.json
  - docs/checkout/localities-dataset-report.md

The script keeps the historical output filenames used by the theme, but the
actual source of truth is now the merged eMAG export data stored in the repo.
"""

from __future__ import annotations

import csv
import html
import json
import re
import unicodedata
from collections import OrderedDict
from pathlib import Path


REPO_ROOT = Path(__file__).resolve().parents[4]
THEME_ROOT = Path(__file__).resolve().parents[1]
DATA_DIR = THEME_ROOT / "data"
REPORT_PATH = REPO_ROOT / "docs" / "checkout" / "localities-dataset-report.md"
RECOVERED_CSV = REPO_ROOT / "romania-localitati-emag-recovered.csv"
REST_HTML = REPO_ROOT / "romania-localitati-emag-rest-de-judete.csv"

COUNTY_ORDER = [
    "AB", "AR", "AG", "BC", "BH", "BN", "BT", "BR", "BV", "B", "BZ", "CS",
    "CL", "CJ", "CT", "CV", "DB", "DJ", "GL", "GR", "GJ", "HR", "HD", "IL",
    "IS", "IF", "MM", "MH", "MS", "NT", "OT", "PH", "SJ", "SM", "SB", "SV",
    "TR", "TM", "TL", "VS", "VL", "VN",
]

MISSING_HTML_COUNTIES = ["B", "BR", "BV", "CL", "CV", "GL", "GR", "GJ", "IL", "IF", "SB", "TL"]

COUNTY_CODE_TO_NAME = {
    "AB": "Alba",
    "AR": "Arad",
    "AG": "Argeș",
    "BC": "Bacău",
    "BH": "Bihor",
    "BN": "Bistrița-Năsăud",
    "BT": "Botoșani",
    "BR": "Brăila",
    "BV": "Brașov",
    "B": "București",
    "BZ": "Buzău",
    "CS": "Caraș-Severin",
    "CL": "Călărași",
    "CJ": "Cluj",
    "CT": "Constanța",
    "CV": "Covasna",
    "DB": "Dâmbovița",
    "DJ": "Dolj",
    "GL": "Galați",
    "GR": "Giurgiu",
    "GJ": "Gorj",
    "HR": "Harghita",
    "HD": "Hunedoara",
    "IL": "Ialomița",
    "IS": "Iași",
    "IF": "Ilfov",
    "MM": "Maramureș",
    "MH": "Mehedinți",
    "MS": "Mureș",
    "NT": "Neamț",
    "OT": "Olt",
    "PH": "Prahova",
    "SJ": "Sălaj",
    "SM": "Satu Mare",
    "SB": "Sibiu",
    "SV": "Suceava",
    "TR": "Teleorman",
    "TM": "Timiș",
    "TL": "Tulcea",
    "VS": "Vaslui",
    "VL": "Vâlcea",
    "VN": "Vrancea",
}

COUNTY_NAME_TO_CODE = {value.casefold(): code for code, value in COUNTY_CODE_TO_NAME.items()}


def normalize_text(value: str) -> str:
    value = value.replace("\xa0", " ")
    value = value.replace("Ţ", "Ț").replace("ţ", "ț").replace("Ş", "Ș").replace("ş", "ș")
    value = unicodedata.normalize("NFC", value)
    value = re.sub(r"\s+", " ", value).strip()
    return value


def strip_prefix(value: str) -> str:
    value = normalize_text(value)
    prefixes = (
        "JUDEȚUL ",
        "JUDEŢUL ",
        "JUDETUL ",
        "MUNICIPIUL ",
        "ORAȘUL ",
        "ORAŞUL ",
        "ORASUL ",
        "COMUNA ",
        "SATUL ",
        "SECTORUL ",
    )
    for prefix in prefixes:
        if value.upper().startswith(prefix):
            value = value[len(prefix):]
            break

    value = normalize_text(value)
    if value.upper() in {"BUCUREȘTI", "BUCURESTI"}:
        return "București"
    if value.upper().startswith("SECTOR "):
        return "Sector " + value.split(" ", 1)[1]
    return value


def normalize_locality_label(value: str) -> str:
    value = normalize_text(value)
    value = re.sub(
        r"^(JUDEȚUL|JUDEŢUL|JUDETUL|MUNICIPIUL|ORAȘUL|ORAŞUL|ORASUL|ORAȘ|ORAŞ|ORAS|COMUNA|SATUL|SECTORUL)\s+",
        "",
        value,
        flags=re.IGNORECASE,
    )
    value = normalize_text(value)
    if value.upper() in {"BUCUREȘTI", "BUCURESTI"}:
        return "București"
    return value


def locality_has_prefix(value: str) -> bool:
    normalized = normalize_text(value).casefold()
    normalized = unicodedata.normalize("NFD", normalized)
    normalized = "".join(ch for ch in normalized if unicodedata.category(ch) != "Mn")
    normalized = re.sub(r"[^a-z0-9]+", " ", normalized)
    normalized = re.sub(r"\s+", " ", normalized).strip()
    return normalized.startswith(
        (
            "judetul ",
            "municipiul ",
            "orasul ",
            "oras ",
            "comuna ",
            "satul ",
            "sectorul ",
        )
    )


def normalize_locality_key(value: str) -> str:
    value = normalize_text(value)
    value = value.casefold()
    value = unicodedata.normalize("NFD", value)
    value = "".join(ch for ch in value if unicodedata.category(ch) != "Mn")
    value = re.sub(r"^(judetul|municipiul|orasul|oras|comuna|satul|sectorul)\s+", "", value)
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def sort_key(value: str) -> str:
    normalized = normalize_text(value).casefold()
    normalized = unicodedata.normalize("NFD", normalized)
    normalized = "".join(ch for ch in normalized if unicodedata.category(ch) != "Mn")
    return normalized


def parse_recovered_csv(path: Path) -> tuple[OrderedDict[str, str], dict[str, OrderedDict[str, dict[str, object]]], int]:
    if not path.exists():
        raise FileNotFoundError(f"Missing recovered eMAG CSV: {path}")

    with path.open("r", encoding="utf-8", newline="") as handle:
        rows = list(csv.DictReader(handle, delimiter=","))
    counties: OrderedDict[str, str] = OrderedDict()
    localities: dict[str, OrderedDict[str, dict[str, object]]] = {
        code: OrderedDict() for code in COUNTY_ORDER
    }

    for row in rows:
        county_name = strip_prefix(str(row.get("county", "")))
        county_code = COUNTY_NAME_TO_CODE.get(normalize_text(county_name).casefold())
        if not county_code:
            continue

        counties[county_code] = county_name

        display = normalize_text(str(row.get("display_name", "")))
        if not display:
            locality_name = normalize_text(str(row.get("name", "")))
            parent = normalize_text(str(row.get("parent", "")))
            display = f"{locality_name} ({parent})" if parent else locality_name

        display = normalize_locality_label(display)
        key = normalize_locality_key(display)
        if not key:
            continue

        prefixed = locality_has_prefix(display)
        existing = localities[county_code].get(key)
        if existing is None or (existing.get("prefixed") and not prefixed):
            localities[county_code][key] = {
                "label": display,
                "prefixed": prefixed,
            }

    return counties, localities, len(rows)


def parse_html_supplement(path: Path) -> tuple[dict[str, OrderedDict[str, dict[str, object]]], int]:
    if not path.exists():
        raise FileNotFoundError(f"Missing eMAG HTML supplement: {path}")

    html_text = path.read_text(encoding="utf-8")
    blocks = re.findall(r"<select\b.*?</select>", html_text, flags=re.S | re.I)
    if len(blocks) != len(MISSING_HTML_COUNTIES):
        raise ValueError(
            f"Expected {len(MISSING_HTML_COUNTIES)} HTML county blocks, found {len(blocks)}"
        )

    localities: dict[str, OrderedDict[str, dict[str, object]]] = {
        code: OrderedDict() for code in COUNTY_ORDER
    }
    total_options = 0

    for county_code, block in zip(MISSING_HTML_COUNTIES, blocks):
        options = re.findall(r"<option[^>]*>(.*?)</option>", block, flags=re.S | re.I)
        total_options += len(options)
        for option in options:
            display = normalize_locality_label(html.unescape(re.sub(r"<.*?>", "", option)))
            key = normalize_locality_key(display)
            if not key:
                continue

            prefixed = locality_has_prefix(display)
            existing = localities[county_code].get(key)
            if existing is None or (existing.get("prefixed") and not prefixed):
                localities[county_code][key] = {
                    "label": display,
                    "prefixed": prefixed,
                }

    return localities, total_options


def merge_sources() -> tuple[
    OrderedDict[str, str],
    OrderedDict[str, list[str]],
    dict[str, int],
    dict[str, OrderedDict[str, int]],
]:
    csv_counties, csv_localities, recovered_rows = parse_recovered_csv(RECOVERED_CSV)
    html_localities, html_options = parse_html_supplement(REST_HTML)

    counties: OrderedDict[str, str] = OrderedDict()
    localities: OrderedDict[str, list[str]] = OrderedDict((code, []) for code in COUNTY_ORDER)
    source_counts: dict[str, OrderedDict[str, int]] = {
        "recovered": OrderedDict((code, 0) for code in COUNTY_ORDER),
        "rest": OrderedDict((code, 0) for code in COUNTY_ORDER),
    }

    for code in COUNTY_ORDER:
        counties[code] = csv_counties.get(code) or COUNTY_CODE_TO_NAME[code]

    for code in COUNTY_ORDER:
        source_counts["recovered"][code] = len(csv_localities.get(code, OrderedDict()))
        source_counts["rest"][code] = len(html_localities.get(code, OrderedDict()))

        merged_bucket: OrderedDict[str, dict[str, object]] = OrderedDict()
        for source_bucket in (csv_localities.get(code, OrderedDict()), html_localities.get(code, OrderedDict())):
            for key, entry in source_bucket.items():
                existing = merged_bucket.get(key)
                if existing is None or (existing.get("prefixed") and not entry.get("prefixed")):
                    merged_bucket[key] = dict(entry)

        values = [str(entry.get("label", "")) for entry in merged_bucket.values() if str(entry.get("label", ""))]
        values.sort(key=sort_key)
        localities[code] = values

    counts = {
        "recovered_rows": recovered_rows,
        "html_options": html_options,
        "canonical_total": sum(len(values) for values in localities.values()),
    }
    counts["deduped_entries"] = counts["recovered_rows"] + counts["html_options"] - counts["canonical_total"]

    return counties, localities, counts, source_counts


def write_json(path: Path, payload: object) -> None:
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def write_report(
    path: Path,
    counties: OrderedDict[str, str],
    localities: OrderedDict[str, list[str]],
    counts: dict[str, int],
    source_counts: dict[str, OrderedDict[str, int]],
) -> None:
    lines = [
        "# eMAG normalized localities dataset report",
        "",
        f"- Sources: `{RECOVERED_CSV.name}` and `{REST_HTML.name}`",
        f"- Counties: {len(counties)}",
        f"- Canonical localities: {counts['canonical_total']}",
        f"- Raw recovered CSV rows: {counts['recovered_rows']}",
        f"- Raw HTML locality options: {counts['html_options']}",
        f"- Deduplicated entries removed: {counts['deduped_entries']}",
        "",
        "## Localities per county",
        "",
        "| Code | County | Recovered CSV | HTML supplement | Final canonical |",
        "| --- | --- | ---: | ---: | ---: |",
    ]

    for code in COUNTY_ORDER:
        lines.append(
            f"| {code} | {counties[code]} | {source_counts['recovered'][code]} | {source_counts['rest'][code]} | {len(localities.get(code, []))} |"
        )

    lines.append("")
    lines.append("## Validation notes")
    lines.append("")
    lines.append("- No duplicate locality keys remain after accent, casing and prefix normalization.")
    lines.append("- The generated JSON files are sorted by normalized locality label.")
    lines.append("- The checkout and My Account dropdowns should consume the generated JSON files directly.")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")


def main() -> int:
    counties, localities, counts, source_counts = merge_sources()
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    REPORT_PATH.parent.mkdir(parents=True, exist_ok=True)

    write_json(DATA_DIR / "siruta-counties.json", counties)
    write_json(DATA_DIR / "siruta-localities-by-county.json", localities)
    write_report(REPORT_PATH, counties, localities, counts, source_counts)

    print(
        f"Generated {len(counties)} counties and {counts['canonical_total']} canonical localities "
        f"from {RECOVERED_CSV.name} + {REST_HTML.name}."
    )
    print(f"Report written to {REPORT_PATH}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
