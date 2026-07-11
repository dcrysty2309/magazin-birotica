# eMAG normalized localities dataset report

- Sources: `romania-localitati-emag-recovered.csv` and `romania-localitati-emag-rest-de-judete.csv`
- Counties: 42
- Canonical localities: 13821
- Raw recovered CSV rows: 11842
- Raw HTML locality options: 1979
- Deduplicated entries removed: 0

## Localities per county

| Code | County | Recovered CSV | HTML supplement | Final canonical |
| --- | --- | ---: | ---: | ---: |
| AB | Alba | 721 | 0 | 721 |
| AR | Arad | 283 | 0 | 283 |
| AG | Argeș | 595 | 0 | 595 |
| BC | Bacău | 508 | 0 | 508 |
| BH | Bihor | 460 | 0 | 460 |
| BN | Bistrița-Năsăud | 253 | 0 | 253 |
| BT | Botoșani | 341 | 0 | 341 |
| BR | Brăila | 0 | 143 | 143 |
| BV | Brașov | 0 | 172 | 172 |
| B | București | 0 | 6 | 6 |
| BZ | Buzău | 490 | 0 | 490 |
| CS | Caraș-Severin | 309 | 0 | 309 |
| CL | Călărași | 0 | 166 | 166 |
| CJ | Cluj | 437 | 0 | 437 |
| CT | Constanța | 216 | 0 | 216 |
| CV | Covasna | 0 | 129 | 129 |
| DB | Dâmbovița | 380 | 0 | 380 |
| DJ | Dolj | 387 | 0 | 387 |
| GL | Galați | 0 | 184 | 184 |
| GR | Giurgiu | 0 | 170 | 170 |
| GJ | Gorj | 0 | 437 | 437 |
| HR | Harghita | 263 | 0 | 263 |
| HD | Hunedoara | 487 | 0 | 487 |
| IL | Ialomița | 0 | 139 | 139 |
| IS | Iași | 427 | 0 | 427 |
| IF | Ilfov | 0 | 104 | 104 |
| MM | Maramureș | 247 | 0 | 247 |
| MH | Mehedinți | 361 | 0 | 361 |
| MS | Mureș | 514 | 0 | 514 |
| NT | Neamț | 362 | 0 | 362 |
| OT | Olt | 391 | 0 | 391 |
| PH | Prahova | 473 | 0 | 473 |
| SJ | Sălaj | 290 | 0 | 290 |
| SM | Satu Mare | 239 | 0 | 239 |
| SB | Sibiu | 0 | 188 | 188 |
| SV | Suceava | 415 | 0 | 415 |
| TR | Teleorman | 242 | 0 | 242 |
| TM | Timiș | 325 | 0 | 325 |
| TL | Tulcea | 0 | 141 | 141 |
| VS | Vaslui | 466 | 0 | 466 |
| VL | Vâlcea | 612 | 0 | 612 |
| VN | Vrancea | 348 | 0 | 348 |

## Validation notes

- No duplicate locality keys remain after accent, casing and prefix normalization.
- The generated JSON files are sorted by normalized locality label.
- The checkout and My Account dropdowns should consume the generated JSON files directly.
