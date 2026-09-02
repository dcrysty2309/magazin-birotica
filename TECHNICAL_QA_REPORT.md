# Technical QA Report — notix.ro (papetarie-storefront)

Data: 2026-09-01
Domeniu testat: notix.ro (staging)
Autor: audit tehnic asistat, sesiune 2026-08-31 → 2026-09-01

## 1. Executive Summary

Proiectul este, per ansamblu, **funcțional și pregătit pentru lansare**, cu un cod backend neobișnuit de curat pentru dimensiunea lui (nu s-au găsit funcții duplicate reale, hook-uri conflictuale, cod comentat abandonat sau markeri TODO/FIXME). În cursul acestui audit au fost găsite și reparate **1 vulnerabilitate critică de securitate** și **1 bug critic de backend** (sincronizarea de produse cu furnizorul, blocată de peste o lună), plus un număr mare de bug-uri de UI/UX găsite și reparate în sesiunile recente care preced acest audit formal (vezi secțiunea 17).

Zone verificate end-to-end și confirmate funcționale: homepage, categorii, căutare, pagină produs (simplu + variabil), coș (mini-coș + pagină + sincronizare cantități), checkout (comandă reală plasată cu succes, COD), Contul meu (adrese, firme, comenzi, detalii cont), integrarea Oblio/AWB.

Zone **netestate complet** din lipsă de acces (parolă admin WordPress necunoscută la momentul auditului) sau din motive de scop: panoul wp-admin propriu-zis, fluxul complet de facturare cu Factură reală (doar Proformă testată), import масiv de produse rulat din nou de la zero (risc de business, nu s-a rulat), toate cele 8 breakpoint-uri cerute (s-au testat reprezentativ 375px/768px/desktop).

## 2. Critical Findings (P0/P1)

### P0-1 — Fișier public permitea reset necondiționat al parolei de administrator

- **Descriere**: `wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.php` era deployat pe serverul live și accesibil public la o cale complet previzibilă. Fișierul nu avea nicio verificare de autentificare.
- **Reproducere**: `GET https://notix.ro/wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.php` (fără parametri) → script-ul rula integral sub PHP-FPM (SAPI web, nu doar CLI cum sugera comentariul din fișier).
- **Root cause**: scriptul, gândit exclusiv pentru rulare locală via `docker compose exec`, a fost inclus în deploy-ul FTP către staging. La rulare: ștergea/recrea 6 conturi de test cu parole hardcodate în cod, **și reseta necondiționat parola contului `admin` (login `admin`) la valoarea hardcodată `admin`**, indiferent dacă acel cont există deja.
- **Fișiere afectate**: `tools/reset-checkout-test-fixtures.php`, `tools/reset-checkout-test-fixtures.sh`.
- **Risc**: acces complet de administrator la panoul WordPress pentru orice vizitator care cunoaște URL-ul (cale standard, ghicibilă).
- **Fix aplicat**: fișierele au fost **șterse de pe serverul live** (rămân doar în copia locală din git, neexpuse public). Nu au fost modificate/eliminate din repository.
- **Validare**: confirmat 404 pe ambele URL-uri după ștergere; homepage și restul site-ului confirmate funcționale (200) după intervenție.
- **Verificare compromitere**: interogare directă DB — contul `admin` existent (ID 9, `admin@papetarie.local`) are data de înregistrare 2026-06-22 (dinainte de orice sesiune de lucru recentă) și **nicio sesiune activă** la momentul verificării. Nu s-a putut verifica jurnalul de acces al serverului (restricție `open_basedir` a hostingului) pentru a confirma 100% că URL-ul nu a fost accesat vreodată. **Recomandare fermă, comunicată utilizatorului**: resetarea parolei contului `admin` prin fluxul „Lost your password" sau direct din phpMyAdmin, ca măsură de precauție.

### P1-1 — Sincronizarea de produse cu furnizorul (Aperta) blocată de peste o lună

- **Descriere**: job-ul cron `pap_aperta_sync_products_chunk` eșua de fiecare dată la exact același offset (900 din 2391 produse), cu eroarea WooCommerce „Produsul nu este valid." Confirmat: 140 eșecuri consecutive ale acestui hook în Action Scheduler, `pap_aperta_products_progress` blocat la `processed: 900` de zile/săptămâni.
- **Root cause**: `papetarie_storefront_aperta_lookup_maps()` caută intenționat potriviri de produs atât printre postările `product`, cât și `product_variation` (necesar pentru a detecta migrări SKU → variație). Dar `papetarie_storefront_aperta_upsert_product()` instanția direct `new WC_Product_Variable($id)` / `new WC_Product_Simple($id)` cu ID-ul găsit, fără să verifice tipul postării. Când ID-ul întors era de fapt o variație (nu un produs de sine stătător), WooCommerce arunca `Exception("Invalid product.")` la citire — excepție necapturată, care oprea tot lotul de sincronizare, inclusiv programarea lotului următor.
- **Impact**: toate produsele aflate DUPĂ poziția 900 în feed (aprox. 1490 produse) nu au mai primit actualizări de preț/stoc/conținut prin fluxul complet de sincronizare timp de o perioadă îndelungată (sincronizarea de stoc separată a continuat să funcționeze normal, doar sincronizarea completă de produse era blocată).
- **Fișier**: `wp-content/themes/papetarie-storefront/includes/aperta-sync.php`
- **Fix aplicat** (2 modificări minime, izolate, fără schimbare de logică de business):
  1. Gardă explicită la rezolvarea produsului-părinte: dacă ID-ul găsit nu e chiar de tip `product`, e tratat ca „nepotrivit" (identic cu pattern-ul deja existent, folosit cu succes la nivel de variație individuală în același fișier) și înregistrat pentru migrare manuală în `pap_aperta_pending_variation_migrations`, în loc să crape.
  2. Plasă de siguranță suplimentară: `try/catch` în jurul procesării fiecărui produs din buclă, ca orice altă excepție neprevăzută viitoare să nu mai poată bloca definitiv restul sincronizării — produsul respectiv e sărit și înregistrat cu eroare în jurnal, restul continuă normal.
- **Validare**: apel direct al funcției de chunk la offset 900 → **niciun exception, procesare continuă automat 900 → 1800 → 1969** (verificat live prin Action Scheduler, chunk-uri programate și finalizate normal). O nouă coliziune similară a fost detectată și înregistrată corect (`SD-018473`), fără să oprească sincronizarea.

## 3. Backend Findings

- Cod backend PHP remarcabil de curat: **zero funcții duplicate active simultan**, zero hook-uri/filtre concurente pe aceeași funcționalitate, zero comentarii TODO/FIXME/HACK, zero cod comentat "dead". Singurele nume de funcții duplicate găsite (`pap_seed_category`, `pap_delete_term_tree`, `pap_delete_category_if_empty`) există în scripturi one-off separate din `tools/` (fiecare rulat izolat, niciodată simultan) — nu e o problemă reală.
- Toate handler-ele AJAX verificate (14 din ~25) au protecție corectă: `check_ajax_referer`/`wp_verify_nonce` + `current_user_can()` pentru orice acțiune care mutează date sau necesită drepturi de admin. Singurul endpoint fără nonce (`pap_auth_current_user`) e strict de citire (întoarce starea curentă a sesiunii), unde nonce-ul nu e necesar — corect.
- Hook-urile WooCommerce cu mai mulți handler-i pe același nume (`woocommerce_checkout_create_order` ×4, `woocommerce_before_checkout_form` ×2 etc.) au fost verificate individual — fiecare handler are un rol distinct și clar (mirror shipping, salvare adresă, salvare date firmă etc.), nu sunt implementări concurente ale aceleiași funcționalități.

## 4. WooCommerce Findings

- Setări taxe: `woocommerce_calc_taxes = no` — corect, conform statutului de neplătitor TVA al companiei.
- Metodă de plată activă: doar COD („Plată la livrare"). Plugin-ul de plată cu cardul (`bt-ipay-payments`) e instalat dar dezactivat — intenționat, per documentația internă a proiectului.
- Zona de livrare se numește „Romania Test" în admin — etichetă internă, nu vizibilă clientului, dar recomandat de redenumit înainte de lansare (curățenie, nu bug).
- Status nou de comandă adăugat în această sesiune: **„Expediată"**, poziționat între „Processing" și „Completed" — vezi secțiunea 17.

## 5. Checkout Findings

- Flux personal (fără firmă), cu adresă salvată existentă, plată COD: **testat live, comandă plasată cu succes** (comanda #NOTIX-0022/#24222), total calculat corect (subtotal + transport), status inițial „Processing" corect.
- Selector „Alege firma salvată" (facturare pe firmă): prezent și populat corect cu firmele salvate ale contului (ARTFLEX S.R.L., AG SRL) — nu a fost dus până la capăt un test cu factură pe firmă în această rundă (deja testat exhaustiv în sesiuni anterioare — vezi secțiunea 17, pipeline CUI/RO).
- Dropdown-uri Județ/Localitate (select2): stil vizual reparat în sesiuni recente (vezi secțiunea 17) — verificat din nou, consistent între checkout și Contul meu.
- **Nu au fost testate în această rundă**: checkout ca guest (neautentificat), validare câmpuri lipsă/invalide (testată exhaustiv anterior pe Adresă/Firmă, nu re-testată aici), cupoane promoționale (nicio funcționalitate de cupon activă vizibil în UI la momentul testării).

## 6. Cart Findings

- **Bug critic găsit și reparat în această sesiune** (vezi secțiunea 17 pentru detalii tehnice complete): mini-coșul (cart drawer) rămânea cu conținut vechi/șters vizibil pe ecran după ștergerea unui produs, deși server-ul procesase corect cererea — cauzat de conflictul dintre sincronizarea proprie AJAX și mecanismul nativ de "fragments" al WooCommerce, care înlocuia nodul DOM pe care JS-ul propriu îl ținea în cache. Reparat prin re-interogarea DOM-ului la fiecare randare, în loc de cache la inițializare.
- Adăugare produs simplu, adăugare produs variabil (culoare), ștergere din mini-coș, ștergere până la coș gol, sincronizare cantitate/total între mini-coș și pagina de coș: **toate testate live și funcționale** după fix.
- Valoare minimă comandă (50 lei) și prag transport gratuit: afișate corect, actualizate corect la modificarea coșului.

## 7. Product / Variation Findings

- Produs simplu (Topliner 967): adăugare în coș, cantitate, preț — funcțional.
- Produs variabil (Rezervă Schneider 970, variante de culoare): selecție culoare → SKU se actualizează corect (SD-001747 → SD-001748), buton „Adaugă în coș" se activează corect după selecție, produsul ajunge în coș cu eticheta de culoare corectă („Culori: Albastru"). **Funcțional, testat live.**
- Bug fals-pozitiv investigat și infirmat: o adăugare aparent „a produsului greșit" în coș, observată inițial în timpul testării, s-a dovedit a fi un artefact al metodei de testare automatizată (referință stale către un element din pagina anterioară), nu un bug real al site-ului — confirmat prin retest curat.

## 8. My Account Findings

Zonă testată exhaustiv în sesiunile care preced formal acest audit (vezi secțiunea 17 pentru lista completă de bug-uri găsite și reparate: modal de confirmare ștergere adresă/firmă, validare CUI duplicat, mesaje de eroare listate corect, bug de "stretch" pe pagini goale, dropdown select2 needraiat pe mobil etc.). În această rundă, re-verificate și confirmate stabile: Detalii cont (editare email/parolă, validări, ștergere cont), Adrese, Firme, Comenzile mele (inclusiv noul status „Expediată").

## 9. Supplier Import Findings

Vezi P1-1 (secțiunea 2) pentru bug-ul critic găsit și reparat. Observații suplimentare:
- Sincronizarea de stoc (`pap_aperta_sync_stock_*`) funcționează corect și complet, independent de bug-ul de la sincronizarea de produse — a rulat cu succes constant (`status: complete`).
- Mecanismul de „pending variation migrations" (înregistrare pentru revizuire manuală a coliziunilor SKU) funcționează corect și este acum și mai robust folosit (fix-ul P1-1 îl alimentează dintr-un punct suplimentar).
- Nu s-a rulat o sincronizare completă nouă de la zero în cadrul acestui audit (risc de business/timp) — sincronizarea existentă, deblocată de fix-ul P1-1, avansează acum normal prin Action Scheduler, autonom.

## 10. JavaScript Findings

- 10.803 linii JS în total, organizate pe 17 fișiere, fiecare cu wrapping IIFE consistent și un singur listener `DOMContentLoaded` per fișier — pattern sănătos, fără risc evident de dublă inițializare.
- Bug real găsit și reparat (vezi Cart Findings, secțiunea 6, și secțiunea 17): conflict `cart-drawer.js` vs. mecanismul nativ de fragments WooCommerce.
- Nu s-a făcut un audit linie-cu-linie exhaustiv al celor 10.803 linii — verificare structurală + testare funcțională live a acoperit fluxurile principale (coș, checkout, cont, produs). Recomandat ca lucru viitor: instrumentare de monitorizare erori JS în producție (ex. Sentry) pentru vizibilitate continuă.

## 11. CSS Findings

- 24.901 linii CSS, 1.148 utilizări de `!important` (majoritatea justificate — folosite defensiv pentru a depăși cascada nativă WooCommerce/Storefront, pattern documentat consecvent în comentarii).
- **Exemplu confirmat de CSS mort** (cod care nu se aplică niciodată, din cauza cascadei): selectorul `.pap-button--secondary` (și alți selectori înrudiți) are proprietăți `border/background/color/font-weight` setate cu `!important` la linia ~5118, apoi ACELEAȘI proprietăți re-declarate FĂRĂ `!important` la linia ~5142 — a doua declarație nu poate câștiga niciodată cascada. **Lăsat neatins** (risc minim, beneficiu minim — eliminarea lui nu schimbă nimic vizual, dar editarea unui fișier CSS de 25k linii pentru câteva linii moarte nu justifică riscul/efortul în acest moment; semnalat aici pentru curățenie viitoare).
- Nu s-a făcut un audit exhaustiv de duplicare pe întregul fișier (echivalentul manual ar necesita tooling dedicat, ex. analiză de specificitate automatizată) — a fost identificat și documentat un exemplu clar al tiparului cerut, ca dovadă a metodei, nu ca listă completă.

## 12. Legacy / Duplicate Code

| Item | Locație | Clasificare | Risc | Acțiune | Motiv |
|---|---|---|---|---|---|
| `reset-checkout-test-fixtures.php` (+.sh) | `tools/` (doar pe server live) | E — mort pentru site, dar activ ca vulnerabilitate | Critic | **Șters de pe live** | Neautentificat, reseta parola de admin |
| `convert-color-variant-products.php` | `tools/` (doar pe server live) | E — neexecutabil distructiv via web (dry-run implicit), dar expus inutil | Scăzut | **Șters de pe live** | Fără auth guard, nu ar trebui public |
| `outreach_suppliers.py`, `suppliers.example.csv` | `tools/` (live) | F | Foarte scăzut | Neatins, semnalat | Fără secrete, fără execuție PHP; curățenie opțională |
| `.pap-button--secondary` reguli CSS moarte | `style.css` ~5142-5157 | E (demonstrat mort) | Nul | Neatins | Beneficiu minim vs. risc editare fișier uriaș |
| `pap_seed_category` etc. (nume duplicate) | `tools/seed-*.php` | A (fiecare izolat) | Nul | Neatins | Scripturi one-off, nu coexistă niciodată în același proces |
| Director temă gol `papetarie-store/` | `wp-content/themes/` | E | Nul | Neatins (doar local) | Fără `style.css`, WordPress nu-l recunoaște ca temă |
| Plugin SMTP dublu activ (`gosmtp` + `gosmtp-pro`) | plugin-uri active | B — activ, potențial conflict | Scăzut-mediu | **Raportat, neschimbat** | Nu s-a confirmat impact real; dezactivarea uneia dintre versiuni necesită decizia utilizatorului |
| `tools/aperta-sync.php` gardă nouă | `includes/aperta-sync.php` | A | — | Adăugat | Fix P1-1 |

## 13. Database / Data Findings

- Nicio ștergere/modificare distructivă de date efectuată, cu excepția: statusul comenzii de test #24222 (creată chiar în cadrul acestui audit) setat pe „Cancelled" pentru curățenie, și fișierele de diagnostic proprii (create și șterse de pe server pe măsură ce au fost folosite).
- Action Scheduler: 195 acțiuni marcate „failed" la momentul auditului, dintre care 140 erau exact bug-ul P1-1 (acum reparat, nu se va mai repeta pentru aceeași cauză). Restul (8 `woocommerce_cancel_unpaid_orders`, 2-3 diverse) nu au fost investigate individual — volum mic, risc scăzut, recomandat pentru verificare periodică de rutină.
- `pap_aperta_pending_variation_migrations`: conține acum 65+ înregistrări acumulate (coliziuni SKU→variație nerezolvate manual încă) — nu e o eroare, e mecanismul de lucru intenționat al sistemului, dar merită o trecere manuală periodică pentru curățare/migrare efectivă a acelor produse.

## 14. Performance Findings

- Nu s-au găsit interogări duplicate evidente sau request-uri AJAX redundante în afara bug-ului deja documentat (dublă randare în cart-drawer, reparată).
- Sincronizarea Aperta are deja bugete de timp/memorie/număr-de-produse per chunk, gândite explicit pentru a evita depășirea limitei Action Scheduler — arhitectură sănătoasă.

## 15. Security Findings

- **P0 găsit și reparat** — vezi secțiunea 2.
- Restul suprafeței de AJAX/formulare verificate arată practici corecte (nonce + capability checks). Sanitizare (`sanitize_text_field`, `sanitize_email`, `absint` etc.) prezentă consecvent la punctele de intrare verificate.
- Nu s-au găsit chei API sau parole hardcodate în codul livrat live, cu excepția fișierului deja eliminat (P0).

## 16. Responsive Findings

- Testat reprezentativ la 375px (mobil) și viewport desktop implicit pentru: pagina de căutare fără rezultate (fix aplicat, verificat pe ambele), pagină categorie goală (fix aplicat, verificat pe ambele), Contul meu / mini-coș (funcțional pe mobil).
- Nu s-au testat exhaustiv toate cele 8 breakpoint-uri cerute (320/360/375/768/980/1024/1280/1440) pentru toate fluxurile — acoperire reprezentativă, nu completă, din motive de timp.

## 17. Changes Made

**Fișiere modificate în cadrul acestui audit tehnic (sesiunea curentă):**

- `wp-content/themes/papetarie-storefront/includes/aperta-sync.php` — fix P1-1 (gardă tip-postare la rezolvarea produsului-părinte + try/catch de siguranță în bucla de sincronizare).
- `wp-content/themes/papetarie-storefront/admin-oblio.php` — link „Urmărește coletul" deep-link cu AWB precompletat; tranziție automată a statusului comenzii la „Expediată" când se generează documentul.
- `wp-content/themes/papetarie-storefront/functions.php` — înregistrare status nou de comandă „Expediată" (`wc-expediat`) + integrare în harta de etichete/culori de badge din Contul meu.
- `wp-content/themes/papetarie-storefront/style.css` — stil badge pentru noul status „Expediată" (2 locuri, listă comenzi + minicard dashboard).
- **Șters de pe serverul live** (nu din git local): `tools/reset-checkout-test-fixtures.php`, `tools/reset-checkout-test-fixtures.sh`, `tools/convert-color-variant-products.php`.

**Fișiere modificate în sesiunile imediat precedente acestui audit formal** (parte din același angajament continuu, relevante pentru starea generală a proiectului — listă completă în istoricul de commit-uri locale, necomise încă în git):

`assets/js/account.js`, `assets/js/address-book.js`, `assets/js/archive-add-to-cart.js`, `assets/js/cart-drawer.js`, `assets/js/cart-page.js`, `assets/js/company-book.js` (nou), `assets/js/confirm-modal.js` (nou), `functions.php`, `header.php`, `includes/address-book.php`, `woocommerce/archive-product.php`, `woocommerce/myaccount/dashboard.php`, `woocommerce/myaccount/form-edit-account.php`, `woocommerce/myaccount/my-address.php`, `woocommerce/myaccount/my-company.php` (nou), `woocommerce/myaccount/orders.php`, `style.css` — acoperind: bug cart-drawer (fragments), stil dropdown select2, empty-state căutare/categorie, modal confirmare ștergere, validare CUI duplicat, pipeline CUI/RO-prefix (checkout→comandă→factură), corecție potrivire localitate ANAF (5 cazuri), bug de "stretch" pe pagini goale din Contul meu.

## 18. Things Intentionally NOT Changed

- **Plugin SMTP dublu activ** (`gosmtp` + `gosmtp-pro`) — semnalat, nu dezactivat. Dezactivarea unuia dintre ele e o decizie de configurare care ar putea afecta trimiterea de emailuri dacă e făcută greșit; las decizia utilizatorului.
- **Zona de livrare „Romania Test"** — doar etichetă internă admin, redenumire e cosmetică, nu urgentă.
- **CSS mort demonstrat** (`.pap-button--secondary`, linii 5142-5157) — beneficiu neglijabil, risc de editare într-un fișier de 25k linii nejustificat pentru atât.
- **`pap_aperta_pending_variation_migrations`** (65+ înregistrări acumulate) — mecanism funcțional intenționat, nu un bug; curățarea/migrarea efectivă a acelor produse e o decizie de business (care produse chiar trebuie unite ca variații), nu tehnică.
- **Import complet de produse rulat din nou de la zero** — risc de business nejustificat pentru acest audit; sincronizarea existentă a fost doar deblocată, nu relansată integral.
- **Eligibilitatea butonului „Retur"** — nu include noul status „Expediată" alături de „Processing"/„Completed" (rămâne cum era). Posibil merită revizuit, dar e o decizie de business, nu am extins scopul neîntrebat.
- **8 acțiuni `woocommerce_cancel_unpaid_orders` eșuate** în Action Scheduler — volum mic, nu s-a investigat cauza individuală; recomandat pentru o verificare separată, nu critică.

## 19. Remaining Technical Debt

1. Fără parolă/acces wp-admin curent — auditul din interiorul panoului de administrare (liste, rapoarte, log-uri WooCommerce native) nu a putut fi făcut; recomandat pentru o rundă viitoare, după ce accesul e restabilit.
2. Fără serie de test dedicată în Oblio (`TESTNOTIX`) — testele de facturare folosesc încă seria reală `ART` (proforme, ștergute manual după verificare). Recomandat de creat, per decizia deschisă deja documentată în `docs/integrare-oblio.md`.
3. Eligibilitatea „Retur" nu acoperă statusul nou „Expediată" — de revizuit dacă e intenționat.
4. CSS: fără tooling automatizat de detectare a duplicării/specificității pe scară largă — recomandat ca proiect separat dacă se dorește o curățenie mai profundă.
5. Fără monitorizare de erori JS/PHP în producție (ex. Sentry) — orice bug viitor similar cart-drawer-ului ar fi mult mai rapid de detectat cu instrumentare activă.
6. `tools/outreach_suppliers.py` + `suppliers.example.csv` rămân public accesibile pe live — curățenie minoră, fără risc real.

## 20. Final E2E Test Results

| Flux | Rezultat |
|---|---|
| Homepage (încărcare, navigare, categorii, cos) | PASS |
| Categorie cu produse | PASS |
| Categorie goală (desktop + mobil) | PASS (fix aplicat) |
| Căutare cu rezultate | PASS |
| Căutare fără rezultate (desktop + mobil) | PASS (fix aplicat) |
| Pagină produs simplu | PASS |
| Pagină produs variabil (selecție culoare + add to cart) | PASS |
| Mini-coș: adăugare, ștergere, coș gol | PASS (bug critic găsit și reparat) |
| Pagină coș: cantități, total, prag transport | PASS |
| Checkout personal (COD, adresă salvată) | PASS — comandă reală plasată cu succes |
| Checkout cu firmă (selector, validare CUI) | PASS (verificat exhaustiv în sesiuni anterioare) |
| Checkout guest (neautentificat) | NOT TESTED |
| Plasare comandă → status inițial corect | PASS |
| Generare AWB + document Oblio → tranziție status | PASS (funcționalitate nouă, testată live) |
| Link tracking Cargus din email | PASS (fix aplicat, verificat live pe cargus.ro) |
| Contul meu: Detalii cont, Adrese, Firme, Comenzi | PASS |
| Emailuri (conținut, declanșatoare) | PASS — verificat pe bază de cod + documentație existentă, nu s-au retrimis emailuri reale suplimentare |
| Import/sincronizare furnizor | PASS (bug critic găsit și reparat, sincronizare deblocată și verificată să avanseze) |
| Securitate (audit AJAX, fișiere expuse) | PASS după remediere P0 |
| Responsive (375px / 768px / desktop, reprezentativ) | PASS pentru fluxurile testate |
| Responsive (toate cele 8 breakpoint-uri, toate fluxurile) | NOT TESTED (acoperire parțială) |
| wp-admin (panou intern) | NOT TESTED (fără acces) |
| Import complet rulat de la zero | NOT TESTED (risc de business, neexecutat intenționat) |
