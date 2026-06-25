# Checkout Rules

Acest document reprezintă sursa oficială de reguli pentru dezvoltarea checkout-ului.

Orice implementare nouă trebuie să respecte acest document.

Înainte de orice modificare în checkout:

1. Citește acest document.
2. Respectă toate regulile existente.
3. Dacă este nevoie de o excepție, documenteaz-o înainte de implementare.
4. Dacă apare o regulă nouă, actualizează acest document înainte sau odată cu implementarea.

## 0. Structura checkout-ului (Regulă de arhitectură)

Ordinea pașilor din checkout este fixă și nu trebuie modificată.

Fiecare pas este identificat printr-un număr și un titlu.

Ordinea este întotdeauna:

**1. Adresă de livrare**

**2. Tip de livrare**

**3. Facturare**

**4. Metodă de plată**

Reguli:

- Nu se schimbă ordinea pașilor.
- Nu se renumerotează pașii.
- Nu se inversează poziția lor în UI.
- Fiecare pas trebuie să afișeze întotdeauna numărul corespunzător.
- Toate flow-urile (guest, user logat, user cu una sau mai multe adrese) trebuie să respecte aceeași ordine.
- Chiar dacă un pas este completat și afișează doar un summary card, numărul și titlul pasului rămân vizibile.
- Toate implementările viitoare trebuie să respecte această structură fără excepții.

Această ordine reprezintă standardul oficial al checkout-ului SupplyHub și nu trebuie modificată fără o decizie explicită de arhitectură.

## 1. Reguli generale

- Orice modificare în checkout trebuie testată înainte de a fi considerată finalizată.
- Nu se trece la următorul pas până când pasul curent nu are status stabil.
- Nu este suficient ca implementarea să compileze.
- Trebuie testat flow-ul real în UI.
- Orice regresie vizuală trebuie raportată.
- Orice eroare în console trebuie raportată.
- Orice eroare PHP/WooCommerce trebuie raportată.

## 2. Reguli UI

- Se păstrează designul SupplyHub existent.
- Nu se introduc stiluri noi dacă există deja componente reutilizabile.
- Inputurile trebuie să respecte stilul Login/Register/My Account.
- Summary card-ul de adresă trebuie reutilizat peste tot.
- Butonul „Modifică” trebuie să rămână consistent.
- Iconurile trebuie să fie SVG outline, consistente ca dimensiune.
- Nu se dublează texte precum „Adresă de livrare”.
- Nu se afișează formular și summary card în același timp pentru aceeași adresă.

## 3. Reguli frontend

- Validările FE trebuie să funcționeze pentru toate cazurile.
- Câmpurile obligatorii trebuie validate înainte de salvare.
- Erorile trebuie afișate vizibil lângă câmpuri.
- Datele introduse trebuie păstrate la editare.
- După salvare, summary card-ul trebuie actualizat.
- După refresh, datele trebuie să persiste corect.
- Nu trebuie să existe erori în browser console.

## 3.1. Checkout Address Save UX

Pentru Pasul 1 - Adresă de livrare, salvarea adresei trebuie să fie rapidă și consistentă pentru toate tipurile de utilizatori.

Se aplică pentru:

- guest;
- user logat fără adresă;
- user logat cu o adresă;
- user logat cu mai multe adrese;
- adăugare adresă nouă;
- editare adresă existentă.

Reguli:

- Salvarea, adăugarea și editarea adresei trebuie făcute prin AJAX, fără reload complet de pagină.
- După validare FE reușită, UI-ul trebuie să treacă rapid în summary/card view.
- Pentru userul logat, experiența trebuie să fie la fel de fluidă ca la guest.
- Backend-ul trebuie totuși apelat pentru salvare în user meta, actualizare WooCommerce checkout session, actualizare shipping fields și recalculare shipping dacă este necesar.
- Dacă backend-ul returnează eroare, UI-ul trebuie să revină în formular, eroarea trebuie afișată lângă câmpul relevant sau într-un mesaj general, iar datele introduse nu trebuie pierdute.
- Nu se permite submit complet al paginii pentru salvarea adresei, decât ca fallback dacă JavaScript nu funcționează.
- Butonul trebuie să aibă stare de loading în timpul requestului AJAX.
- Nu se blochează tot checkout-ul inutil; se blochează doar zona de adresă afectată.
- După salvare reușită, summary card-ul trebuie să se actualizeze instant, datele trebuie să persiste după refresh, iar WooCommerce session trebuie să conțină aceeași adresă afișată în UI.
- Ce vede userul în summary card trebuie să fie exact ce va ajunge în comandă, factură și livrare.

## 4. Reguli backend

- Backend-ul trebuie să valideze toate câmpurile importante.
- FE validation nu este suficientă.
- Request-urile fără nonce valid trebuie respinse.
- Userul nu poate edita adrese care nu îi aparțin.
- Datele invalide nu trebuie salvate.
- WooCommerce session trebuie actualizată după schimbarea adresei.
- Shipping-ul trebuie recalculat când adresa se schimbă.
- Nu se rescriu mecanismele native WooCommerce de order/payment/shipping.

## 5. Reguli pentru screenshot-uri

- Pentru fiecare caz important trebuie făcut screenshot.
- Screenshot-urile se salvează în:

  `/docs/checkout/testing/screenshots/`

- Numele screenshot-urilor trebuie să fie clare:
  - `tc-001-guest-form.png`
  - `tc-002-guest-summary.png`
  - `tc-003-logged-one-address.png`
- trebuie sa stergi mereu pozele vechi atuncic and refaci testarea ca sa nu se aglomereze spatiul

## 6. Raportare QA

Pentru fiecare test case trebuie documentat:

- TC-ID
- Nume test
- User folosit
- Pași executați
- Rezultat așteptat
- Rezultat obținut
- Screenshot-uri
- Status: PASS / FAIL / PARTIAL
- Observații

## 7. Regula finală

Orice task de checkout trebuie să se încheie cu:

- fișiere modificate;
- cazuri testate;
- screenshot-uri;
- bug-uri găsite;
- ce nu a putut fi testat;
- recomandări pentru următorul pas.
