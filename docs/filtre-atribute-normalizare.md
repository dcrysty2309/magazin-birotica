# Normalizarea grupurilor de filtre (atribute produse)

## Cum funcționează extragerea de atribute

Nu e un proces manual (nu citește nimeni fiecare produs) — e cod determinist care rulează automat la fiecare sincronizare Aperta, în `includes/aperta-sync.php`:

1. **Din descriere** (`papetarie_storefront_aperta_extract_description_attributes()`) — multe descrieri Aperta sunt liste `<li>Etichetă: Valoare</li>`; funcția extrage toate perechile găsite, generic, fără reguli speciale per categorie. Doar valorile scurte (sub 40 caractere) devin filtre — restul ar fi unice per produs și n-ar filtra nimic.
2. **Din nume** (`papetarie_storefront_aperta_extract_text_attributes()`) — regex-uri și liste de cuvinte cunoscute (culori în română/engleză, format A3-A6, „X file", liniatură, gramaj etc.), pentru produsele simple (fără variante).
3. Fiecare pereche (grup, valoare) găsită devine un termen în taxonomia unică `product_attr_value`, cu grupul reținut ca term meta `pap_attr_group` — pagina de categorie grupează filtrele vizual după acest meta (`papetarie_storefront_get_category_attribute_filters()` din `functions.php`).

Eticheta de grup e salvată **exact cum a scris-o Aperta** în descriere — de-aia apar variații între produse pentru același concept ("Nr. File" vs "Număr file"), fiindcă sursa nu e consistentă.

## Normalizarea grupurilor

`papetarie_storefront_aperta_normalize_attr_group()` unifică variantele cunoscute înainte ca termenul să fie creat/reutilizat. Reguli existente dinainte (2026-07, sincronizare Aperta):

| Dacă grupul conține | Devine |
|---|---|
| `culo` (orice, ex. "Culori Molotow", "Culori Kreul") | Culoare |
| `dimensiune` | Dimensiuni |
| `laptop` | Compartiment pentru laptop |

Reguli noi adăugate 2026-07-27, după un audit al tuturor celor 42 de grupuri distincte folosite pe site:

| Dacă grupul conține | Devine | Atenție |
|---|---|---|
| `file` **și** (`nr.`/`nr` **sau** `num[ăa]r`) | Număr file | NU prinde "Grosime File" (gramaj hârtie, alt concept) |
| `grosime` **și** `scriere` | Grosime de scriere | NU prinde "Lungime (de) scriere" (metri de scris, alt concept) |
| `diametr` **și** `min` | Diametrul minei | NU prinde "Diametru" simplu sau "Diametrul perforației" |
| `strat` | Număr straturi | — |

Regulile sunt intenționat restrictive (cer mai multe cuvinte simultan), tocmai ca să nu unească din greșeală concepte diferite care doar sună asemănător (ex. grosimea vârfului ≠ lungimea de scris a unei rezerve).

## Migrarea termenilor deja creați

O regulă nouă de normalizare se aplică automat doar la termeni **noi** (creați la sincronizări viitoare). Termenii deja existenți rămân cu grupul vechi în meta până sunt corectați explicit — `tools/normalize-attribute-groups.php` face exact asta: parcurge toți termenii `product_attr_value`, recalculează grupul normalizat și corectează meta-ul `pap_attr_group` acolo unde diferă. Nu atinge relațiile produs-termen, doar eticheta de grup — sigur de rulat oricând.

## Corecțiile aplicate pe staging (2026-07-27, 67 termeni)

**Grosime de scriere** (din "Grosime De Scriere" / "Grosime Scriere") — 19 valori:
0,3 mm · 0,4 mm · 0,4 mm (M) · 0,5 mm · 0,6 mm (XB) · 0,7 mm · 0,8 · 0,8 mm · 0.4 mm (M) · 1 mm · 1-1,5 mm · 1-4,7 mm · 1-5 mm · 1-7 mm · 1,0 mm · 2-3 mm · 2,6 mm · 2,6/4,7 · 6 mm

**Număr file** (din "Nr. File" / "Număr File" / "Număr File/Set") — 38 valori:
10 · 100 · 12 x 100 · 16 · 160 file/set (4 x 40) · 20 · 24 · 3 x 100 · 40 · 400 · 48 · 5 x 100 · 5 x 25 · 50 · 500 · 6 x 100 · 72 · 8 · 80 · 800 · 90 · 96 · 4 x 24 · 4 x 35 · 4 x 50 · 4 x 60 · 4 x 80 · 4 x 100

**Diametrul minei** (din "Diametru Mină" / "Diametrul Minei") — 8 valori:
10 mm · 2,2 mm · 3 mm · 3,3 mm · 3,5 mm · 4 mm · Ø 2,9 mm · Ø 3,2 mm

**Număr straturi** (din "Număr Straturi" / "Numar Straturi") — 3 valori: 1 · 2 · 3

## De reținut pentru viitor

Dacă un audit viitor găsește alte grupuri duplicate, se adaugă o regulă nouă în `normalize_attr_group()` + se rulează din nou `tools/normalize-attribute-groups.php` (idempotent — rulat de mai multe ori nu strică nimic, doar raportează 0 corectări dacă nu mai e nimic de făcut).
