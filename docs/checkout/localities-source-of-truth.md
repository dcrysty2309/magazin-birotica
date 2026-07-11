# Final source of truth for Romanian counties and localities

Checkout and My Account must consume only one canonical dataset for Romania:

- `wp-content/themes/papetarie-storefront/data/siruta-localities-by-county.json`

This file is generated from the two eMAG exports stored in the repo:

- `romania-localitati-emag-recovered.csv`
- `romania-localitati-emag-rest-de-judete.csv`

## What this removes

- legacy county/locality lists;
- manual per-county dropdown patches;
- fallback data from old `ro-localities-by-county.json`;
- mixed sources that could cause duplicated or missing localities;
- any runtime fallback to browser/session data for county/locality options.

## Canonical guarantees

- 42 counties are present;
- each county has one normalized locality list;
- locality names are deduplicated after accent-insensitive, casing-insensitive and prefix-insensitive normalization;
- the final ordering is alphabetical by canonical locality label;
- checkout and My Account read the same source, so they cannot diverge.

## Validation commands

- Rebuild the canonical dataset:
  - `python3 wp-content/themes/papetarie-storefront/tools/import-siruta.py`
- Validate the generated dataset:
  - `python3 wp-content/themes/papetarie-storefront/tools/validate-siruta.py`
- Compare against an eMAG export:
  - `python3 wp-content/themes/papetarie-storefront/tools/compare-siruta-with-emag.py --emag /cale/catre/export-emag.csv`

## Generated report

The county-by-county comparison report is stored at:

- `docs/checkout/localities-dataset-report.md`

