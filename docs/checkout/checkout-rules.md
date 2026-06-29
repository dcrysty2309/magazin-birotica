# Checkout Rules

Acesta este standardul oficial pentru Checkout v1.

## 0. Reguli de mediu

- Local înseamnă development.
- Staging înseamnă QA oficial.
- Implementarea se face local.
- Testarea rapidă se poate face local.
- Testarea finală a flow-urilor se face pe staging.
- Datele din staging sunt date de test, nu date reale.
- Nu se sincronizează automat staging înapoi peste local.
- Deploy-ul merge într-o singură direcție: local → staging.
- Conturile de test trebuie să fie aceleași pe local și staging.
- Dacă un test modifică adrese sau comenzi, trebuie să existe o metodă de resetare a datelor de test.
- Comentariile și bug-urile din pagina de testare trebuie păstrate pe staging și documentate.

## 1. Ordinea pașilor

Ordinea este fixă și nu se schimbă:

1. Adresă de livrare
2. Tip de livrare
3. Facturare
4. Metodă de plată

Reguli:

- Numerele pașilor rămân vizibile în toate flow-urile.
- Titlul pasului rămâne vizibil și când pasul este completat.
- Nu se inversează ordinea pentru guest sau user logat.

## 2. Pasul 1 - Adresă de livrare

Pasul 1 colectează adresa folosită pentru comanda curentă.

Reguli:

- Checkout-ul v1 nu mai afișează un address book cu multiple adrese salvate.
- Guest-ul completează adresa de la zero.
- Userul logat vede formularul completat din adresa standard WooCommerce, dacă există.
- Userul logat poate modifica datele pentru comanda curentă.
- La redeschiderea formularului, folosim mai întâi `currentOrderAddress`, apoi adresa salvată în cont, apoi formular gol.
- Userul logat salvează adresa în My Account doar dacă bifează explicit `Salvează această adresă pentru comenzile viitoare`.
- Salvarea în My Account trebuie să fie opt-in real: checkbox-ul trebuie bifat explicit în sesiunea curentă, nu doar restaurat de browser.
- Nu există checkbox-uri, radio button-uri sau badge-uri de selecție între mai multe adrese.
- Nu există buton `Adaugă adresă nouă` în checkout.
- După salvare, UI-ul trece în summary card view.
- Summary card-ul trebuie să reflecte exact datele care ajung în comandă.
- Dacă salvarea sau validarea eșuează, mesajul de eroare apare sus, imediat sub titlul secțiunii sau deasupra primului câmp, nu sub butoanele de acțiune.
- Ordinea câmpurilor în formularul de adresă este: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- Nu există câmp separat pentru `Bloc / Scară / Etaj / Apartament`; aceste detalii intră în Adresă sau în Observații pentru livrare / curier.
- Observațiile pentru livrare / curier nu se afișează în summary card, dar trebuie să rămână disponibile în checkout session și în datele comenzii.

## 3. Checkout și My Account

Reguli:

- Checkout-ul nu editează automat adresele salvate în My Account.
- Modificările făcute din checkout se aplică doar comenzii curente.
- Dacă userul vrea să schimbe permanent adresa, o face prin checkbox-ul de salvare sau direct din My Account.
- My Account afișează public un singur bloc, `Adresa mea`, iar intern WooCommerce păstrează billing și shipping sincronizate.
- Blocul `Adresa mea` apare doar dacă există o adresă standard completă; dacă lipsesc câmpurile obligatorii, My Account afișează empty state-ul cu butonul `Adaugă adresă nouă`.
- Formularul de adresă din My Account folosește aceeași structură de câmpuri ca checkout-ul.
- Standardul de formular de adresă este unic: Checkout și My Account folosesc aceeași ordine a câmpurilor, aceleași validări și aceeași structură vizuală pentru editare.
- Labelul pentru câmpul principal este `Adresă` în ambele zone; placeholder-ul poate rămâne specific variantei.
- În modalul My Account nu se folosesc iconuri pe inputuri; structura trebuie să rămână identică cu Checkout-ul curent, adică label deasupra și câmp simplu dedesubt.
- Standardul include și câmpul opțional `Observații pentru livrare / curier`; acesta are același comportament și aceeași prezentare în Checkout și în modalul My Account.
- Câmpul separat pentru Bloc / Scară / Etaj / Apartament nu mai face parte din UI-ul curent; compatibilitatea legacy poate rămâne doar pentru date istorice.

## 4. UX pentru salvare

- Salvarea și editarea adresei trebuie făcute prin AJAX, fără reload complet de pagină.
- După validare FE reușită, UI-ul trebuie să treacă rapid în summary/card view.
- Pentru userul logat, experiența trebuie să fie la fel de fluidă ca la guest.
- Backend-ul trebuie totuși apelat pentru actualizare WooCommerce checkout session, actualizare shipping fields și recalculare shipping dacă este necesar.
- Salvarea în user meta se face doar când checkbox-ul este bifat explicit.
- Dacă backend-ul returnează eroare, UI-ul trebuie să revină în formular, iar datele introduse nu trebuie pierdute.
- Mesajele generale de eroare pentru salvarea adresei se afișează sus, imediat sub titlul secțiunii sau deasupra primului câmp, nu sub butoanele de acțiune.
- Nu se permite submit complet al paginii pentru salvarea adresei, decât ca fallback dacă JavaScript nu funcționează.
- Butonul trebuie să aibă stare de loading în timpul requestului AJAX.
- Nu se blochează tot checkout-ul inutil; se blochează doar zona de adresă afectată.
- După salvare reușită, summary card-ul trebuie să se actualizeze instant, datele trebuie să persiste după refresh, iar WooCommerce session trebuie să conțină aceeași adresă afișată în UI.
- Ce vede userul în summary card trebuie să fie exact ce va ajunge în comandă, factură și livrare.

## 5. Reguli UI

- Se păstrează designul SupplyHub existent.
- Nu se introduc stiluri noi dacă există deja componente reutilizabile.
- Inputurile trebuie să respecte stilul Login/Register/My Account.
- Summary card-ul de adresă trebuie reutilizat peste tot.
- Butonul `Modifică` trebuie să rămână consistent.
- Iconurile trebuie să fie SVG outline, consistente ca dimensiune.
- Nu se dublează texte precum `Adresă de livrare`.
- Nu se afișează formular și summary card în același timp pentru aceeași adresă.

## 6. Reguli pentru butoane

- În fiecare pas există un singur CTA principal.
- CTA-ul principal păstrează aceeași componentă vizuală în tot checkout-ul.
- Acțiunile secundare precum `Anulează`, `Înapoi` sau `Renunță` au aceeași înălțime ca CTA-ul principal și se afișează ca parte din același grup de acțiuni.
- Pe desktop, butoanele se aliniază împreună și păstrează aceeași proporție în toate etapele.
- Pe mobil, ordinea și ierarhia butoanelor trebuie să rămână coerente între pași.
- Pentru pașii intermediari, CTA-ul principal folosește text scurt și explicit, de tipul `Continuă`.
- Lățimea butoanelor este dictată de conținut și padding, nu de valori fixe per pas.

## 7. Reguli frontend

- Validările FE trebuie să funcționeze pentru toate cazurile.
- Câmpurile obligatorii trebuie validate înainte de salvare.
- Erorile trebuie afișate vizibil lângă câmpuri.
- Datele introduse trebuie păstrate la editare.
- După salvare, summary card-ul trebuie actualizat.
- După refresh, datele trebuie să persiste corect.
- Nu trebuie să existe erori în browser console.

## 8. Design System Checkout

- Toate componentele reutilizabile din Checkout trebuie să provină dintr-un singur Design System.
- Nu se creează stiluri noi dacă există deja o componentă echivalentă.
- Butoanele, cardurile, input-urile, label-urile, mesajele de eroare și badge-urile trebuie refolosite consecvent.

## 9. Reguli pentru screenshot-uri

- Pentru fiecare caz important trebuie făcut screenshot.
- Screenshot-urile se salvează în `/docs/checkout/testing/screenshots/`.
- Numele screenshot-urilor trebuie să fie clare.
- Șterge pozele vechi când refaci testarea ca să nu se aglomereze spațiul.

## 10. Raportare QA

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

## 11. Regula finală

Orice task de checkout trebuie să se încheie cu:

- fișiere modificate;
- cazuri testate;
- screenshot-uri;
- bug-uri găsite;
- ce nu a putut fi testat;
- recomandări pentru următorul pas.
