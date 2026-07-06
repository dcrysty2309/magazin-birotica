# Checkout Testing Rules

## 1. Reguli generale

- Orice modificare in checkout trebuie testata inainte de finalizare.
- Nu trecem la pasul urmator pana cand pasul curent nu este stabil.
- Nu este suficient ca implementarea sa aiba sintaxa valida.
- Trebuie testat flow-ul real in UI.
- Testarea rapida se poate face local.
- Testarea finala a flow-urilor se face pe staging.
- Local este mediul de dezvoltare, staging este QA oficial.
- Datele de staging sunt date de test, nu date reale.
- Nu se face sincronizare automata din staging inapoi in local.
- Conturile de test sunt aceleasi pe local si staging.
- Daca un test schimba adrese sau comenzi, trebuie sa existe o metoda clara de resetare a datelor de test.
- Comentariile din Preview devin lista oficiala de bug-uri pentru staging.
- Dupa fix, scenariile afectate se retesteaza tot pe staging inainte de urmatorul deploy.

## 2. Reguli pentru indexul de testare

Indexul vizual este organizat in doua grupe: Pasul 1 (adresa) si Phase 1 (shipping + payment).

Rezumatul final pentru source of truth al județelor/localităților este în `docs/checkout/localities-source-of-truth.md`.

Pentru referinta rapida a tuturor cazurilor, foloseste indexul vizual:

- `http://localhost:8080/checkout-test-cases/`
- acolo gasesti ordinea cazurilor, regulile de border, label-uri si datele de test;
- `Preview` deschide panoul lateral cu detaliile cazului si comentariile de testare;
- pagina afiseaza o lista compacta cu cazurile care au observatii salvate in WordPress;
- Pasul 1 si Phase 1 sunt grupate separat in index pentru a ramane usor de scanat;
- pentru verificarea finala pe QA, acelasi index trebuie folosit impreuna cu staging;
- comentariile salvate in Preview sunt persistate in WordPress, in CPT-ul intern `pap_checkout_comment`, si se reincarca la fiecare redeschidere a Preview-ului;
- cazurile cu observatii apar si in lista compacta de pe index, iar cazurile cu comentarii deschise primesc un marcaj vizual discret in tabel.
- județele și localitățile trebuie să provină din dataset-ul canonic eMAG-normalizat și pot fi comparate cu un export eMAG prin:
  - `python3 wp-content/themes/papetarie-storefront/tools/compare-siruta-with-emag.py --emag /cale/catre/export-emag.csv`
- validarea dataset-ului canonic se face cu:
  - `python3 wp-content/themes/papetarie-storefront/tools/validate-siruta.py`
- checkout-ul și My Account trebuie testate doar cu dataset-ul canonic generat din cele două exporturi eMAG din repo; nu se acceptă fallback-uri locale, liste legacy sau surse paralele de localități.

Pentru guest:

- trebuie sa existe doar un singur flux: formular -> salvare -> summary card -> editare aceleiasi adrese;
- nu se testeaza lista de adrese sau adresa noua pentru guest, pentru ca acele stari nu exista;
- butonul `Adauga adresa noua` nu trebuie sa apara niciodata in guest flow.


## 2.1. Reguli pentru Phase 1 shipping + payment

- Shipping-ul și plata trebuie validate separat de flow-ul de adresă.
- Nu trecem la card payment până când transportul real și ramburs nu sunt PASS.
- Cazurile de shipping/payment sunt grupate în Checkout Test Index într-o secțiune separată pentru Phase 1.
- Pentru Phase 1, shipping-ul de referință este `Flat rate`, iar transportul gratuit se testează la pragul de 150 lei.
- Dacă WooCommerce nu are metode active, testul trebuie să confirme mesajul de business, nu o valoare fallback.
- Documentația de referință pentru shipping și payment este `docs/checkout/payment-and-shipping-rules.md`.

## 3. Reguli de validare

- Validarile frontend existente trebuie sa continue sa functioneze.
- Mesajele de eroare trebuie sa ramana consistente cu restul aplicatiei.
- Validarile backend trebuie verificate separat de cele frontend.
- Dacă backend-ul trimite mai multe mesaje pentru aceeași cauză, testul trebuie să confirme că UI afișează un singur mesaj canonic, prietenos, nu tehnic + traducere în paralel.
- Dacă apar simultan mai multe erori din câmpuri diferite, ele se afișează în ordine, dar fiecare câmp/cauză are un singur mesaj.
- După confirmarea adresei și refresh, summary-ul trebuie să păstreze localitatea, județul și codul poștal exact așa cum au fost confirmate în checkout.

## 4. Reguli pentru butoane

- Fiecare pas trebuie verificat vizual pentru un singur CTA principal.
- Actiunile secundare, inclusiv `Inapoi la adrese`, trebuie sa aiba aceeasi inaltime si sa fie tratate ca un grup impreuna cu CTA-ul principal.
- Pe desktop, verifica alinierea si proportiile butoanelor.
- Pe mobil, verifica aceeasi ordine si aceeasi ierarhie in toate etapele checkout-ului.
- Pentru pasii intermediari, verifica textul scurt `Continua`, nu texte extinse precum `Continua catre livrare`.
- Verifica faptul ca latimea butoanelor este determinata de continut si padding, nu de o latime fixa pe ecran.
- Verifica faptul ca butoanele principale si secundare au acelasi font-size, 14px, si aceeasi line-height.

## 4.1. Reguli pentru Address Card

- Summary card-ul foloseste iconuri outline de 22x22px pentru user, locatie, telefon si email.
- Toate textele sunt la `font-weight: 400` si au 14px in address card, cu exceptia numelui, care este `600`.
- Textele din randurile cu icon sunt la 14px.
- Fiecare rand este `display: flex`, `align-items: center`, cu `gap: 16px`.
- Spatierea dintre randuri este de 12px.
- Line-height-ul liniilor din card este 1.45 si trebuie sa fie uniform peste tot.
- 0 adrese: formular deschis.
- 1 adresa: formular precompletat sau card neutru, fara label de selectie.
- Nu exista lista de carduri pentru multiple adrese in checkout-ul v1.
- Nu exista badge-ul `Adresa implicita din cont`.
- Nu se folosesc checkbox-uri, radio button-uri sau iconuri pentru selectie intre adrese.
- Pentru guest, nu exista butonul `Adauga adresa noua`.
- Userul logat poate salva adresa in cont doar prin checkbox-ul explicit.
- Verifica daca adresa afisata in card este identica cu datele salvate in sesiunea checkout-ului.
- Verifica faptul ca observatiile pentru livrare / curier nu apar in summary card, daca acesta este standardul ales pentru UI.

## 4.2. Reguli pentru formularul de adresa

- Ordinea campurilor este: Prenume, Nume, Email, Telefon, Judet, Localitate, Adresa, Cod postal, Observatii pentru livrare / curier.
- Nu trebuie sa existe camp separat pentru Bloc / Scara / Etaj / Apartament.
- Detaliile de tip apartament sau scara se noteaza in Adresa sau in Observatii pentru livrare / curier.
- Observatiile trebuie sa fie optionale si sa nu blocheze fluxul daca sunt goale.
- Formularul de adresa din My Account trebuie sa foloseasca aceeasi ordine si aceleasi campuri ca formularul din checkout.
- Checkout-ul nu mai afiseaza un address book cu mai multe adrese salvate.
- Daca exista date legacy cu `address_2`, ele se trateaza doar ca backward compatibility si nu mai apar ca input separat in UI.
- La testare, dropdown-urile de județ și localitate trebuie să pornească din dataset-ul canonic eMAG-normalizat; dacă o localitate lipsește din UI, se consideră bug de date, nu se completează manual în altă sursă.

## 5. Reguli pentru screenshot-uri

- Screenshot-urile se salveaza in `docs/checkout/testing/screenshots/`.
- La rerulare se sterg capturile vechi care nu mai sunt relevante.
- Denumirile trebuie sa fie clare si predictibile.

## 6. Reguli pentru comentarii de testare

- Fiecare caz poate avea un comentariu salvat din Preview.
- Fiecare caz poate avea mai multe comentarii salvate din Preview.
- Comentariile se pastreaza in WordPress, in CPT-ul intern `pap_checkout_comment`, si se reincarca la redeschiderea Preview-ului.
- Comentariile sunt afisate cronologic in Preview.
- Pagina afiseaza o lista compacta cu cazurile care au observatii si marcheaza discret randurile cu comentarii deschise.
- Statusurile oficiale sunt: `open`, `in_progress`, `fixed`, `ignored`.
- Un comentariu nou porneste cu status `open`.
- Un comentariu se marcheaza ca `fixed` dupa ce bug-ul a fost reparat si retestat.
- `ignored` se foloseste doar pentru observatii confirmate ca nefiind bug.
- Daca editezi un comentariu existent, se actualizeaza aceeasi intrare si se schimba doar `updated_at`.
- Daca adaugi un comentariu nou, se creeaza o intrare noua in istoricul test case-ului.
- Comentariile servesc ca baza pentru trierea bug-urilor si pentru generarea de fix-uri ulterioare.
- Comentariile si bug-urile relevante trebuie pastrate si documentate si pe staging, nu doar local.
- Comentariile oficiale sunt sursa de backlog pentru bug fix si pot fi exportate din baza de date / staging.
- Daca observatiile sunt salvate pe staging, staging devine sursa oficiala pentru acele observatii pana la rezolvare.
- Cand utilizatorul cere citirea comentariilor, acestea se citesc de pe staging si se colecteaza intr-o lista de probleme.
- Dupa colectare, problemele se transforma in taskuri concrete de fix, polish sau retestare.
- Comentariile QA active de pe staging nu se suprascriu prin sync de DB fara confirmare explicita.

## 7. Raport final

Orice task de checkout se incheie cu:

- fisiere modificate;
- cazuri testate, limitate la scenariile esentiale;
- screenshot-uri;
- bug-uri gasite;
- ce nu a putut fi testat;
- recomandari pentru pasul urmator.
