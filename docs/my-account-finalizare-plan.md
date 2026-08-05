# Plan de finalizare: My Account, Login, Register, Parolă uitată

Data: 2026-08-05

Scop: să treacă în revistă fiecare pagină din zona de cont (inclusiv autentificare/înregistrare/resetare parolă, care fac parte din același flux), ce conține fiecare, ce e deja gata, ce lipsește, și ce decizii sunt de luat înainte de lansare. **De agreat înainte de implementare.**

Bazat pe: cod verificat direct (functions.php, template-parts/auth/*, woocommerce/myaccount/*, assets/js/account.js) + rapoartele existente (`docs/my-account-design-report.md`, `docs/my-account-polish-report.md`, `docs/my-account-checkout-handoff-2026-06-24.md`).

---

## 1. Autentificare (fereastră/modal)

**Ce conține:** câmp Email, câmp Parolă (cu buton arată/ascunde), "Ține-mă minte", link "Ai uitat parola?", buton Autentificare, link "Nu ai cont? Creează unul nou".

**Status:** ✅ funcțional — logarea propriu-zisă merge.

**De reparat:** nimic aici direct (butonul Google a fost eliminat deja).

---

## 2. "Ai uitat parola?" (în fereastra de autentificare)

**Ce ar trebui să se întâmple:** click pe link → fereastra comută la formularul de resetare (câmp Email, buton "Resetare parolă"), fără să părăsești pagina.

**Status:** 🔴 **stricat** — click-ul nu comută nimic, rămâi pe ecranul de login.

**Detaliu tehnic:** confirmat că mecanismul funcționează dacă intri direct pe un link special (`?pap_auth=lost-password` deschide fereastra direct pe ecranul corect) — deci partea vizuală a formularului de resetare există și e corectă. Bug-ul e specific în comutarea din interiorul unei ferestre deja deschise (funcția `showAuthView` din `assets/js/account.js`). Era deja o problemă cunoscută — raportul din 19 iunie 2026 nota explicit că "flow-ul de autentificare prin modal rămâne separat de My Account și nu a fost refactorizat" și că testele automate au fost mutate pe login WordPress direct tocmai ca să ocolească instabilitatea asta.

**Prioritate:** 🔴 blocant pentru lansare — clienții chiar uită parole.

---

## 3. "Creează unul nou" (înregistrare, din fereastra de autentificare)

**Ce ar trebui să se întâmple:** click pe link → fereastra comută la formularul de înregistrare (Prenume, Nume, Email, Parolă, Confirmă parolă, bifă acord confidențialitate, buton "Creare cont").

**Status:** 🔴 **stricat** — aceeași cauză ca #2, click-ul nu comută nimic.

**Decizie necesară:** dacă la checkout există deja opțiunea de a comanda ca musafir (fără cont), repararea asta e mai puțin urgentă — dar tot trebuie să funcționeze până la lansare, pentru cei care vor cont explicit.

**Prioritate:** 🔴 blocant, dar poate veni după #2 (aceeași reparație probabil rezolvă ambele).

---

## 4. Acasă (Dashboard)

**Ce conține:** mesaj de bun venit cu numele userului, card "Comenzi" (număr total), card "Ultima comandă" (număr comandă), tabel "Ultimele comenzi" (număr, dată, status, produse, total) cu link "Vezi toate comenzile".

**Status:** ✅ complet, validat — verificat în raportul din design QA (Dashboard: toate elementele "Match: Yes"), testat responsive pe 5 lățimi de ecran (390–1920px), teste automate treceau (8/8 în polish-report).

**De reparat:** nimic identificat. Opțional (nu blocant): un mic card cu adresa implicită, pentru acces rapid fără să mai intri în "Adrese".

---

## 5. Comenzile mele (listă comenzi)

**Ce conține:** titlu + subtitlu, bară de filtre (status, perioadă, căutare), listă de comenzi tip card (nu tabel), fiecare cu număr+dată, status colorat, total + buton "Detalii", paginare.

**Status:** ✅ complet, validat — la fel ca Dashboard, "Match: Yes" pe toate elementele, testat pe 1/5/20 comenzi.

**De reparat:** nimic identificat.

---

## 6. Detaliu comandă

**Ce conține:** titlu "Comanda #...", data plasării, insignă status, buton "Descarcă factura (PDF)" (doar dacă există factură atașată), card Livrare (metodă + curier), card Metodă de plată, tabel produse comandate (poză, nume, SKU, preț unitar, cantitate, total), bloc totaluri (Subtotal, Transport, TVA, Total comandă).

**Status:** ✅ complet, validat — inclusiv eliminarea blocurilor WooCommerce nedorite (totaluri duplicate, "comandă din nou").

**De reparat:** nimic identificat.

---

## 7. Adrese

**Ce conține:** un singur bloc de "Adresa mea" (adresă standard unificată — nu carte de adrese multiplă), afișat doar dacă adresa e completă; altfel, stare goală centrată cu îndemn de completare. Editarea se face printr-un modal reutilizat și de la Checkout (aceleași câmpuri, aceeași validare).

**Status:** ✅ complet — decizie explicită documentată (checkout-handoff, 24 iunie): "Nu expunem carte de adrese multiplă în UI-ul activ", adresa standard sincronizată automat cu billing/shipping WooCommerce.

**De reparat:** nimic identificat.

---

## 8. Detalii cont

**Ce conține:** editare Prenume/Nume/Email (de verificat exact câmpurile), plus secțiune separată de schimbare parolă (Parola curentă, Parola nouă, Confirmă parola nouă).

**Status:** ✅ câmpurile de schimbare parolă există și par complete în cod.

**De verificat live:** dacă salvarea chiar funcționează end-to-end (submit → confirmare vizuală → parola chiar se schimbă). Nu am testat efectiv trimiterea formularului în această verificare.

---

## 9. Deconectare

**Status:** ✅ funcțională (confirmat vizual — link-ul există și duce la logout WooCommerce standard).

---

## 10. Wishlist / Favorite

**Status:** ❌ **nu există în build-ul curent** — nu apare în meniul lateral (Acasă, Comenzile mele, Adrese, Detalii cont, Deconectare — atât), nicio referință în cod (`functions.php` nu conține deloc cuvintele "favorite"/"wishlist").

**Context:** checklist-ul vechi de lansare o avea notă ca "dacă rămâne" — se pare că, la un moment dat, a fost scoasă din UI-ul activ, deși exista o versiune mai veche testată (raport din 19 iunie).

**Decizie necesară:** o lăsăm scoasă (rămâne la nevoie post-lansare) sau vrem s-o repunem înainte de lansare?

---

## 11. Retururi

**Status:** ⚪ nu există flux automat de retur din cont (self-service). Clientul poate contacta manual prin pagina de contact.

**Decizie deja luată** (checklist original): retururile automate din cont sunt explicit în afara scopului de lansare (v1.1, post-lansare). Nimic de făcut acum.

---

## Rezumat priorități

| # | Element | Status | Prioritate |
|---|---|---|---|
| 2 | Comutare "Ai uitat parola?" în modal | 🔴 stricat | Blocant |
| 3 | Comutare "Creează cont" în modal | 🔴 stricat | Blocant |
| 8 | Verificare end-to-end schimbare parolă din cont | 🟡 neverificat | De confirmat |
| 10 | Wishlist/Favorite | ❌ absent | Decizie: repunem sau nu |
| 1, 4, 5, 6, 7, 9 | Restul | ✅ gata | — |

## Decizii de luat înainte să încep

1. **Register obligatoriu sau checkout musafir suficient?** — influențează cât de urgent e #3 față de #2.
2. **Wishlist/Favorite — repunem înainte de lansare, sau rămâne definitiv post-lansare?**
3. Confirm să reparăm #2 și #3 împreună (probabil aceeași cauză în `account.js`), apoi verificăm #8 live?

---

## Plan design: pagina "Detalii cont" (2026-08-05)

### Ce e stricat acum, exact

Fișier: `woocommerce/myaccount/form-edit-account.php`. Toate câmpurile (Prenume, Nume, Nume afișat, Email, cele 3 de parolă) sunt input-uri simple, fără iconiță, fără wrapper vizual — cu totul alt stil decât Autentificare/Creare cont, unde fiecare câmp are o iconiță (plic, lacăt, om) în interiorul input-ului, plus buton "ochi" la parolă.

Secțiunea de schimbare parolă e închisă într-un `<fieldset>` cu fundal gri-albăstrui deschis (`#f8fafc`) și colțuri rotunjite (`border-radius: 16px`) — cutia asta separată e ce nu-ți place ("bg aiurea").

### Plan propus

**1. Uniformizare input-uri** — fiecare câmp din Detalii cont primește același wrapper folosit deja la Autentificare/Creare cont (`.pap-auth-input-field` + iconiță `.pap-auth-input-icon`):

| Câmp | Iconiță |
|---|---|
| Prenume | om (`user`) |
| Nume | om (`user`) |
| Nume afișat | om (`user`) |
| Adresă de email | plic (`mail`) |
| Parola curentă / nouă / confirmare | lacăt (`lock`) + butonul "ochi" existent (`pap-password-toggle`) — **rămâne**, exact cum ai cerut |

**2. Eliminare cutie gri de la parolă** — scoatem `background`/`border-radius`/`padding` de pe `.pap-account-password-fieldset`; secțiunea de parolă curge natural în pagină, doar cu un titlu ("Schimbare parolă") ca separator vizual simplu (linie subțire deasupra, ca restul secțiunilor din My Account), nu o cutie închisă.

**3. Aranjare/spațiere** — păstrăm gruparea Prenume+Nume pe același rând (cum e acum), restul câmpurilor pe rând întreg; verificăm spațierea dintre grupuri să fie identică cu restul paginilor din My Account (Adrese, de exemplu), nu doar cu login-ul.

**4. Validare** — momentan formularul are doar `required`/`aria-required` (validare minimă de browser). Adăugăm validare inline ca la Creare cont (`account.js` are deja tot mecanismul: `setInlineValidation`, mesaje sub câmp, evidențiere roșie):
   - Email: format valid.
   - Parolă nouă: minim 8 caractere (sau ce prag decizi tu).
   - Confirmare parolă: trebuie să fie identică cu parola nouă.
   - Parola curentă: dacă userul completează parolă nouă dar lasă necompletată "parola curentă", eroare clară ("completează parola curentă ca să o schimbi").

**5. Verificare finală** — testez efectiv trimiterea formularului (salvare nume/email, schimbare parolă reală) end-to-end, ca să confirm și partea de server, nu doar aspectul.

### Ce NU se schimbă
- Structura de câmpuri rămâne aceeași (nu adăugăm/eliminăm câmpuri).
- Butonul "ochi" de arătare parolă rămâne exact cum e.
- Butonul de salvare (`pap-account-primary-button`) rămâne neschimbat ca stil.

**De confirmat**: pragul minim pentru parola nouă (8 caractere e un standard rezonabil, dar spune dacă vrei altceva), și dacă ești de acord cu eliminarea completă a cutiei gri (vs. doar schimbarea culorii ei).
