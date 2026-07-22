# Import de produse reale — Aperta

Cum populăm treptat magazinul cu produsele reale din `Produse Aperta.xlsx` /
`PRODUSE_APERTA.docx` (catalogul furnizat, ~2238 produse în 9 categorii
principale / ~89 subcategorii).

## Progres

| Categorie principală | Subcategorii | Produse (xlsx) | Status |
|---|---|---|---|
| Articole din hârtie | 4 | 66 | ✅ importat |
| Articole pentru birou | 13 | 141 | ✅ importat |
| Accesorii pentru scris | 16 | 282 | ✅ importat |
| Articole școlare | 5 | 256 | ✅ importat |
| Organizare, arhivare, prezentare | 20 | 343 | ✅ importat |
| Artă | 8 | 141 | ✅ importat |
| Creativitate | 30 | 620 | ✅ importat |
| Periferice | 9 | 196 | ✅ importat |
| Curățenie și sanitare | 11 | 193 | ✅ importat |

**Toate cele 2238 de produse din xlsx sunt importate** (2026-07-23), fiecare
categorie principală verificată — numărul de produse pe site se potrivește
exact cu numărul din xlsx pentru fiecare din cele 9 categorii.

Categoriile (taxonomie `product_cat`) sunt seedate din
`tools/seed-real-categories-batch-1.php` (primele 4) și
`tools/seed-real-categories-batch-2.php` (restul de 5). O singură
subcategorie lipsea din arborele seedat inițial — „Pix + cariocă" (1 produs,
sub Accesorii pentru scris) — adăugată manual în timpul importului.

### Coliziuni de nume rezolvate

Câteva subcategorii din xlsx au acelaşi nume ca altele, dar sub categorii
principale diferite. Ca să nu se suprapună (WordPress cere sloguri unice per
taxonomie), au fost redenumite/dezambiguizate la import:

| Categorie principală (xlsx) | Nume în xlsx | Nume folosit pe site | Motiv |
|---|---|---|---|
| Creativitate | Linere | Linere creative | coliziune cu „Linere” din Accesorii pentru scris |
| Creativitate | Pixuri cu gel | Pixuri cu gel creative | coliziune cu „Pixuri cu gel” din Accesorii pentru scris |
| Periferice | Accesorii | Accesorii periferice | coliziune cu „Accesorii” din Articole școlare |

De asemenea, câteva subcategorii din xlsx foloseau forma de singular
(„Perforator”, „Laminator”, „Coș hârtie” etc.) în timp ce arborele deja
seedat (din lotul 1) folosea pluralul („Perforatoare”, „Laminatoare”,
„Coșuri hârtie”) — produsele au fost mapate manual la categoria corectă
existentă, fără să creeze categorii noi duplicate.

## Produse configurabile (variabile) — de discutat mai târziu

Toate cele 2238 de produse au fost importate ca produse **simple** (un
singur preț, fără opțiuni). Userul a semnalat că la unele produse din
catalogul real vor exista variante reale (ex. culoare, mărime) pentru care
clientul trebuie să poată alege — nu s-a stabilit încă *care* produse anume.
Când vin pozele/prețurile reale și se identifică produsele configurabile,
acelea vor trebui convertite din `WC_Product_Simple` în
`WC_Product_Variable` + atribute WooCommerce + variații — nu s-a implementat
nimic din asta încă, e doar semnalat aici ca să nu se piardă din vedere.

## Decizii luate cu userul (2026-07-22)

- **Ordine**: categorie principală cu categorie principală, nu tot dintr-o
  dată — verificăm fiecare pas înainte să trecem mai departe.
- **Preț**: prețul real din xlsx, folosit direct ca preț de vânzare (nu un
  preț fake placeholder, deși asta se discutase inițial).
- **Vizibilitate**: produsele sunt publicate imediat (`publish`), vizibile pe
  categorii de îndată ce sunt importate.
- **Fără imagini încă** — fiecare produs nou arată imaginea placeholder
  WooCommerce standard până se adaugă poze reale.

## Cum se adaugă o categorie nouă

1. **Export din xlsx → JSON normalizat.** Nu există parser xlsx în PHP/docker,
   așa că pasul de export se face cu Python (`openpyxl`) local, în afara
   proiectului. Structura fiecărui fișier `tools/data/<slug>.json`:
   ```json
   {
     "top_level_slug": "articole-din-hartie",
     "subcategories": {
       "Hârtie copiator": {
         "slug": "hartie-copiator",
         "products": [{"name": "...", "price": 17.81, "obs": "opțional"}]
       }
     }
   }
   ```
   Numele produsului se construiește din coloana `Produs` + orice alte
   coloane distinctive (Format, Gramaj, Ambalare, Capacitate etc. — variază
   pe subcategorie), separate prin virgulă. Coloana `OBS` devine
   `short_description`. Valorile placeholder (`—`, `-`) sunt ignorate.
   **Atenție la coliziuni de slug**: dacă un subcategorie din noul lot are
   exact același nume ca una deja existentă sub alt părinte (ex. „Accesorii”,
   „Linere”, „Pixuri cu gel” apar de mai multe ori în xlsx sub categorii
   diferite), trebuie dezambiguizat manual înainte de seed (altfel scriptul
   de categorii ar redenumi/muta termenul existent în loc să creeze unul nou).
2. **Seed categoria/subcategoriile** — dacă nu există deja un
   `tools/seed-real-categories-batch-N.php` care s-o acopere, se scrie unul
   nou după modelul batch-1/batch-2 (retrage orice categorie orfană veche cu
   `pap_delete_term_tree()`, apoi creează arborele nou cu `pap_seed_category()`).
3. **Importă produsele**:
   ```
   docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/import-products-from-json.php wp-content/themes/papetarie-storefront/tools/data/<slug>.json
   ```
   Scriptul e idempotent (marchează fiecare produs cu meta `_pap_import_key`,
   sare peste cele deja importate la o rerulare).
4. **Verifică în browser** — pagina categoriei ar trebui să arate produsele
   reale, contorul „N produse”, filtrele de preț recalculate pentru
   intervalul real de prețuri al categoriei, și filtrul de subcategorie cu
   numărătoarea corectă.

## Bug găsit și reparat în acest proces

Imaginea placeholder WooCommerce (`woocommerce-placeholder.webp`, atașamentul
cu ID 6) nu avea generată dimensiunea `324×324` folosită de temă pentru
cardurile de produs — orice produs fără imagine proprie afișa o imagine
spartă (404), nu placeholder-ul. Nu era vizibil până acum pentru că toate
produsele de test aveau imagini reale. Reparat o singură dată, rulând
`wp_generate_attachment_metadata()` pe atașamentul respectiv — nu mai
trebuie repetat la fiecare import.
