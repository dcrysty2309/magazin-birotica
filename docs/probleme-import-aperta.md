# Probleme găsite la importul de produse Aperta

Aici notăm, pe rând, problemele găsite de Lavinia la produsele aduse din feedul Aperta. La final le luăm pe toate și le reparăm.

## 1. Categoria "Agende" — lipsesc produse

- Pe site, la categoria **Agende**, apare doar **1 singur produs**.
- În feedul Aperta (la ei), pentru aceeași categorie sunt **mai multe produse**.
- De verificat: de ce nu s-au adus toate produsele din feed în categoria asta.
- Status: **de reparat**.

## 2. Produs pus în categoria greșită — "Hârtie color A4 Aperta"

- Produsul **"Hârtie color A4 Aperta – Set 50 coli, 80 g/mp, 5 culori intense"** era pus greșit la categoria **Hârtie specială**.
- Ar fi trebuit să fie la categoria **Hârtie color**.
- Status: **rezolvat pe local** — produsul a fost mutat la categoria corectă.
- **De făcut și pe staging** — aceeași mutare trebuie repetată acolo (nu s-a făcut încă).

## 3. Culori "alese de client" care de fapt sunt un singur produs — decizie de-a noastră, nu a Aperta

- **Important: nu e o greșeală a Aperta.** Ei trimit corect un singur produs, cu un singur cod și un singur stoc, iar în descriere scriu doar generic că vine "în culori variate". **Noi** am decis, la import, să transformăm acel text generic într-un produs cu variante, cu un meniu de unde clientul "alege" culoarea — deși Aperta nu ne oferă cod sau stoc separat pentru fiecare culoare.
- Rezultat: clientul vede o alegere reală (ex. roșu/albastru/verde), dar toate culorile scad din același stoc unic, iar Aperta trimite orice culoare are ea disponibilă, nu neapărat cea aleasă de client.
- **Verificat pe tot catalogul (2026-09-05): exact 38 de produse** au problema asta, împărțite pe 12 categorii:

| Categorie | Produse afectate |
|---|---|
| Caiete | 18 |
| Ascuțitori | 6 |
| Accesorii pentru birou | 3 |
| Stilouri și rollere | 4 |
| Agende | 2 |
| Blocuri desen și schițe | 1 |
| Hârtie specială | 1 |
| Lipiciuri | 1 |
| Pixuri cu pastă | 1 |
| Rezerve cerneală/corector | 1 |

Produsul exact de la "Pixuri cu pastă" (verificat din nou 2026-09-06, în timpul auditului acelei categorii): **"Pix antibacterial ICO Olimpia"** — culorile din meniu (SKU comun) scad toate din același stoc.

Produsele exacte de la "Stilouri și rollere" (verificate din nou 2026-09-07, în timpul auditului acelei categorii): **"Stilou Schneider Ceod Shiny + 2 rezerve/blister"**, **"Stilou Schneider Tomo + Corry + 6 rezerve/blister"**, **"Stilou Schneider Base 1/blister"** și **"Roller Carioca + 4 rezerve/blister"**.

Produsul exact de la "Rezerve cerneală/corector" (verificat 2026-09-07, în timpul auditului acelei categorii): **"Pic + carioca Schneider Corry"** — 4 "variante" afișate, toate cu același cod și stoc.

- **De întrebat Aperta** (opțional, dacă vrem să păstrăm alegerea de culoare pe viitor): au vreun cod/stoc separat pe fiecare culoare pentru produsele astea, sau chiar trimit "culoare la întâmplare din stoc"?
- **Soluție posibilă fără să depindem de răspunsul Aperta**: scoatem meniul fals de alegere a culorii și afișăm produsul simplu, eventual cu o mențiune "culoare aleasă aleatoriu din stoc" — reflectă corect realitatea, nu necesită date noi de la furnizor.
- Status: **de decis** — lista completă (cele 38 de produse) e gata, nu s-a reparat nimic încă, așteptăm decizia finală (păstrăm alegerea și întrebăm Aperta, sau simplificăm produsele pe partea noastră).
