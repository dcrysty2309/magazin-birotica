# Checkout Rules

Acest document este standardul oficial pentru implementarea checkout-ului SupplyHub.

## 0. Reguli de mediu

- Local înseamnă mediul de dezvoltare.
- Staging înseamnă mediul oficial de QA și validare finală.
- Implementarea se face local.
- Testarea rapidă se poate face local.
- Testarea finală a flow-urilor se face pe staging.
- Datele din staging sunt date de test, nu date reale.
- Nu se sincronizează automat staging înapoi peste local.
- Deploy-ul merge într-o singură direcție: local → staging.
- Conturile de test trebuie să fie aceleași pe local și staging.
- Dacă un test modifică adrese sau comenzi, trebuie să existe o metodă de resetare a datelor de test.
- Comentariile și bug-urile din pagina de testare trebuie păstrate pe staging și să poată fi exportate sau documentate.

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

## 2. Reguli pentru Pasul 1

Pasul 1 colectează sau selectează adresa folosită pentru comanda curentă.

Reguli:

- UI-ul se bazează pe numărul de adrese disponibile în checkout, nu pe tipul userului.
- 0 adrese: formular deschis.
- 1 adresă: card neutru, fără stare vizuală de selecție.
- 2+ adrese: listă de carduri, cu o singură adresă selectată.
- Border-ul de selecție apare doar când există minimum 2 adrese.
- Label-urile apar doar când există minimum 2 adrese.
- Label-ul principal este `Selectată pentru livrare`.
- Dacă adresa selectată este și adresa implicită din cont, apare și label-ul `Adresa implicită din cont`.
- Nu se folosesc checkbox-uri, radio button-uri sau iconuri pentru selecție.
- Guest-ul vede formularul când nu are nicio adresă și poate edita aceeași adresă după salvare.
- Userul logat poate selecta o adresă salvată din cont.
- Userul logat poate adăuga o adresă nouă doar pentru checkout-ul curent.
- După salvare, UI-ul trece în summary card view.
- Summary card-ul trebuie să reflecte exact datele care ajung în comandă.
- Ordinea câmpurilor în formularul de adresă este: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- Nu există câmp separat pentru „Bloc / Scară / Etaj / Apartament”; aceste detalii intră în Adresă sau în Observații pentru livrare / curier.
- Observațiile pentru livrare / curier nu se afișează în summary card, dar trebuie să rămână disponibile în checkout session și în datele comenzii.

## 3. Separarea dintre Checkout și My Account

Checkout-ul nu este zonă de administrare cont.

Reguli:

- Checkout-ul nu editează adrese salvate în My Account.
- Adresele din cont pot fi afișate și selectate în checkout.
- Modificările făcute din checkout se aplică doar comenzii curente.
- Dacă userul vrea să schimbe permanent adresele din cont, o face din My Account.
- Formularul de adresă din My Account folosește aceeași structură de câmpuri ca checkout-ul: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- Câmpul separat pentru Bloc / Scară / Etaj / Apartament nu mai face parte din UI-ul curent; compatibilitatea legacy poate rămâne doar pentru date istorice până la migrare completă.

## 4. Reguli UX pentru salvare adresă

- Salvarea pentru Pasul 1 trebuie să fie rapidă și fără reload complet de pagină.
- Validările frontend existente trebuie păstrate.
- Validările backend rămân obligatorii pentru datele importante.
- Butonul principal păstrează layout-ul actual.
- Butonul secundar păstrează layout-ul actual.
- Se blochează doar zona relevantă în timpul requestului AJAX.

## 5. Reguli WooCommerce

- Adresa selectată sau completată trebuie să ajungă în sesiunea WooCommerce.
- Adresa trebuie să ajungă în comandă, livrare și facturare, conform flow-ului curent.
- Shipping-ul trebuie recalculat după schimbarea adresei.
- Nu se rescriu mecanismele native WooCommerce pentru order, shipping și payment.

## 6. Reguli UI

- Se păstrează designul SupplyHub existent.
- Summary card-ul de adresă se reutilizează.
- Nu se afișează simultan formularul și summary card-ul pentru aceeași adresă.
- Iconurile din summary card rămân SVG outline și consistente vizual.
- Toate iconurile din address card au 22x22px.
- Textele din address card folosesc `font-weight: 400`, cu excepția numelui, care este `600`.
- Textele din rândurile cu icon au 14px.
- Numele are 14px, iar textele din rândurile cu icon au 14px.
- Rândurile folosesc `display: flex`, `align-items: center` și `gap: 16px`.
- Spațierea dintre rânduri este de 12px.
- Line-height-ul pentru toate liniile din card este uniform, `1.45`.
- Pentru guest, butonul `Adaugă adresă nouă` nu există deloc.

## 7. Reguli pentru butoane

- În fiecare pas există un singur CTA principal.
- CTA-ul principal folosește aceeași componentă vizuală, aceeași înălțime și aceeași bază de spacing în tot checkout-ul; lățimea este dictată de conținut și padding.
- Acțiunile secundare precum `Înapoi la adrese`, `Înapoi` sau `Renunță` folosesc aceeași înălțime ca CTA-ul principal, dar un stil mai discret.
- Butoanele dintr-un pas sunt tratate ca un singur grup de acțiuni, nu ca elemente independente.
- Pe desktop, alinierea implicită este stânga; pe mobil, layout-ul se poate stivui, dar rămâne identic în toate etapele.
- Nu se definesc dimensiuni sau animații diferite per componentă dacă există deja o variantă reutilizabilă în Checkout.
- Textul butoanelor intermediare este scurt și explicit, de tipul `Continuă`; contextul îl dă titlul pasului.
- Lățimea butoanelor este dictată de conținut și de padding-ul orizontal, nu de valori fixe per ecran.
- Toate butoanele din checkout au aceeași înălțime, aceeași tipografie și aceeași distanță între ele în grup.
- Butoanele principale și secundare folosesc același font-size, 14px, și aceeași line-height.

## 8. Design System Checkout

- Toate componentele reutilizabile din Checkout trebuie să provină dintr-un singur Design System.
- Nu se creează stiluri noi pentru butoane, carduri, input-uri, label-uri, mesaje de eroare sau badge-uri dacă există deja o componentă echivalentă.
- Orice implementare nouă trebuie să refolosească clasele, spațierile și comportamentul deja stabilite în Checkout.
- Orice excepție trebuie documentată înainte de implementare.


## 9. Regula pentru feedback-ul QA din staging

- Comentariile salvate in `Preview` pe staging sunt date QA oficiale pana la rezolvare.
- Daca utilizatorul cere citirea comentariilor sau a observatiilor, sursa oficiala este staging.
- Comentariile colectate de pe staging se transforma mai intai in lista de probleme, apoi in taskuri concrete de fix, polish sau retestare.
- Aceste comentarii nu se suprascriu prin sync de DB fara confirmare explicita.
- Deploy-ul de cod poate continua fara sa afecteze backlog-ul QA din staging, dar importurile de DB trebuie tratate separat.
