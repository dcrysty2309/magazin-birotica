# SupplyHub Checkout - Cazuri de testare si cerinte UX

## Scop
Definim comportamentul checkout-ului pentru utilizatori guest si utilizatori autentificati, astfel incat implementarea sa fie clara, stabila si usor de continuat.

## Index vizual pentru testare

Pentru navigare rapida intre scenarii foloseste pagina:

- `http://localhost:8080/checkout-test-cases/`

Workflow oficial:

- dezvoltarea se face local;
- validarea finală se face pe staging;
- comentariile din Preview se folosesc ca backlog oficial de bug-uri;
- după fix, scenariile afectate se retestează pe staging;
- dacă un test schimbă date de adresă sau comandă, trebuie făcut resetul datelor de test înainte de următorul ciclu.

Pe aceasta pagina gasesti:

- un tabel compact cu cazurile recomandate pentru verificarea rapidă;
- preview pentru fiecare caz cu tip cont / user-parola / reproduce / expected;
- comentarii de testare persistate în WordPress, prin CPT-ul intern `pap_checkout_comment`;
- o listă compactă cu cazurile care au observații, plus istoricul fiecărui caz în Preview;
- pagina este dedicată doar Pasului 1 - Adresa de livrare;
- regulile pentru border, label-uri si numarul de adrese.
- reset rapid pentru conturile de test se poate face din:
  - `wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.sh`
  - `wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.sh checkout.oneaddress@test.local`

## Flux recomandat pentru testare rapidă

În practică, pentru o verificare manuală rapidă, începe cu:

- 1.1 - Guest: stare inițială;
- 1.3 - Guest: după completare și summary;
- 4.1 - User logat: adresă salvată în summary;
- 4.2 - User logat: fără adresă salvată;
- 4.3 - User logat: editare fără salvare;
- C13 - User salvează ulterior adresa curentă a comenzii;
- C14 - fără eroare goală la load;
- C17 - sesiune expirată;
- C19 - cod poștal persistă la tab;
- C22 - cod poștal nu declanșează reset/focus jump;
- C25 - checkout cu coș gol nu afișează flow de comandă.

Pentru coș gol, checkout-ul trebuie să afișeze doar mesajul „Coșul tău este gol.” și CTA către magazin, fără formular, shipping, payment sau butonul de plasare a comenzii.

Restul cazurilor rămân disponibile în setul complet, dar nu sunt necesare la fiecare rundă de testare.

---

# 1. User fara cont / Guest checkout

## 1.1. Tab: Adresa de livrare - stare initiala

### Cand
Userul intra in checkout fara sa fie autentificat si nu exista date salvate.

### Atunci
Tabul "Adresa de livrare" trebuie sa fie deschis si sa afiseze formularul complet.
Rezumatul nu trebuie sa fie vizibil in aceasta stare.
Pasul 2 trebuie sa ramana dezactivat pana la salvarea adresei.

### Campuri afisate
- Prenume *
- Nume *
- Email *
- Telefon *
- Judet *
- Localitate *
- Adresa *
- Cod postal *
- Observatii pentru livrare / curier (optional)

### Optiuni
- Checkbox optional: "Creeaza cont dupa finalizarea comenzii"

### Buton
- "Continua"

---

## 1.2. Validare formular adresa de livrare

### Cand
Userul apasa "Continua" fara sa completeze campurile obligatorii.

### Atunci
Trebuie afisate erori clare langa campurile lipsa.

### Reguli
- Prenumele este obligatoriu.
- Numele este obligatoriu.
- Emailul este obligatoriu si trebuie sa aiba format valid.
- Telefonul este obligatoriu.
- Judetul este obligatoriu.
- Localitatea este obligatorie.
- Adresa este obligatorie.
- Codul postal este obligatoriu.
- Observatiile pentru livrare / curier raman optionale.

---

## 1.3. Tab: Adresa de livrare - dupa completare

### Cand
Userul completeaza corect formularul si apasa "Continua".

### Atunci
Tabul "Adresa de livrare" se inchide si devine un rezumat.
Formularul nu mai este vizibil.
Butonul "Continua" nu mai este vizibil.
Doar summary-ul adresei ramane afisat.
Pasul 2 devine activ.
In DOM trebuie sa existe o singura stare activa: fie formularul in edit mode, fie summary-ul in completed mode, niciodata ambele simultan.

### Rezumat afisat
- Nume complet
- Adresa completa
- Judet + localitate
- Cod postal
- Telefon
- Email
- Link/buton: "Modifica"

### Observatie
Observatiile pentru livrare / curier nu se afiseaza in summary card. Ele se salveaza pentru comanda curenta si, daca este cazul, ajung in checkout session, in comanda, in emailurile de comanda si in adminul WooCommerce.

### Exemplu UI
Cristian Diaconescu
Str. Victoriei 45
Bucuresti, Bucuresti
010061
0740 123 456
d.crysty23@gmail.com
Modifica

---

## 1.4. Actiune: Modifica adresa de livrare

### Cand
Userul apasa "Modifica" pe tabul "Adresa de livrare".

### Atunci
Tabul se redeschide cu toate datele completate anterior.
Rezumatul se ascunde.
Pasul 2 se dezactiveaza pana la reconfirmarea adresei.

### Observatie
Datele nu trebuie pierdute dupa inchidere/deschidere.

---

## 1.5. Cod poștal persistă la tab și la redeschiderea formularului

### Cand
Userul guest completează codul poștal, apoi mută focusul către câmpul următor sau redeschide formularul cu "Modifica".

### Atunci
Codul poștal nu trebuie să se șteargă la blur/tab și trebuie să fie precompletat la reeditare.

### Reguli
- Codul poștal se păstrează în snapshot-ul curent al checkout-ului.
- Când formularul revine în edit mode, codul poștal trebuie să fie completat.
- Schimbarea altor câmpuri nu șterge codul poștal.

### Cazuri asociate
- C19 - Guest cod poștal nu se pierde la tab
- C20 - Guest Modifică precompletează codul poștal
- C21 - Cod poștal persistă în currentOrderAddress

---

## 1.6. Cod poștal nu declanșează reset sau focus jump

### Când
Userul completează codul poștal și continuă navigarea prin formular.

### Atunci
Codul poștal nu se golește la input/tab/blur, focusul nu sare și formularul nu se resetează după recalculări.

### Reguli
- codul poștal se păstrează în draft-ul curent;
- recalcularea checkout-ului nu trebuie să rescrie formularul;
- `updated_checkout` nu suprascrie valorile editate;
- focusul rămâne pe fluxul normal al formularului.

### Cazuri asociate
- C22 - Codul poștal nu declanșează reset sau focus jump
- C23 - updated_checkout nu suprascrie formularul în edit mode

---

# 2. User fara cont - Facturare persoana fizica

## 2.1. Cand adresa de livrare este finalizata

### Atunci
Tabul "Facturare" trebuie sa respecte adresa de livrare salvata.

### Continut
- Checkbox: "Datele de facturare sunt aceleasi cu adresa de livrare"
- Card rezumat:
  - Nume complet
  - Adresa de livrare
  - Text discret: "Factura va fi emisa pe persoana fizica"
- Buton/link: "Modifica"

### Observatie
Daca observatiile pentru livrare / curier exista, ele nu se dubleaza in summary-ul de facturare decat daca exista o regula explicita pentru asta.

---

## 2.2. Cand userul debifeaza "Datele de facturare sunt aceleasi cu adresa de livrare"

### Atunci
In tabul "Facturare" trebuie afisat formular separat pentru adresa de facturare PF.

### Campuri
- Prenume *
- Nume *
- Email *
- Telefon *
- Judet *
- Localitate *
- Adresa *
- Cod postal *
- Observatii pentru livrare / curier (optional, daca se reutilizeaza acelasi model de formular)

---

# 3. User fara cont - Facturare persoana juridica / firma

## 3.1. Activare factura pe firma

### Cand
Userul bifeaza "Doresc factura pe firma".

### Atunci
Trebuie afisat formularul pentru persoana juridica.

### Campuri firma
- CUI *
- Denumire firma *
- Nr. Registrul Comertului
- Judet *
- Localitate *
- Adresa sediu *
- Banca
- IBAN

### Observatie UX
Campul CUI ar trebui sa fie primul. Ideal, dupa completarea CUI-ului, se poate popula automat denumirea firmei si adresa, daca exista integrare disponibila.

---

## 3.2. Dezactivare factura pe firma

### Cand
Userul debifeaza "Doresc factura pe firma".

### Atunci
Formularul de firma dispare si checkout-ul revine la facturare persoana fizica.

### Regula importanta
Datele completate la firma pot ramane temporar in state, dar nu trebuie trimise in comanda daca checkbox-ul este debifat.

---

# 4. User autentificat cu adresa standard

## 4.1. Tab: Adresa de livrare

### Cand
Userul este logat si are date standard WooCommerce pentru livrare.

### Atunci
Tabul "Adresa de livrare" trebuie sa afiseze cardul de summary pentru adresa standard.

### Continut
- Nume complet
- Adresa completa
- Judet + localitate
- Cod postal
- Telefon
- Email
- Buton "Modifica"

### Observatie
Nu exista lista de adrese multiple, nu exista badge-uri de selectie si nu exista buton "Adauga adresa noua".

---

## 4.2. Editare pentru comanda curenta

### Cand
Userul modifica unul sau mai multe campuri din formular.

### Atunci
Modificarile se aplica doar comenzii curente.

### Comportament
- Userul poate edita orice camp relevant pentru comanda curenta.
- Checkbox-ul de salvare decide daca modificarile se scriu si in My Account.
- Schimbarea datelor actualizeaza checkout session si summary-ul.
- La redeschiderea formularului, trebuie folosita adresa curenta a comenzii (`currentOrderAddress`), nu adresa veche din cont.
- Formularul de checkout si My Account trebuie sa ramana compatibile cu aceleasi campuri standard.
- Daca userul nu bifeaza salvarea, My Account ramane neschimbat.
- Daca userul bifeaza salvarea, My Account se sincronizeaza cu adresa curenta.
- Scenariul de test folosește contul `checkout.cleannoaddress@test.local`, adică user logat fără adresă salvată la pornire. Contul este recreat din reset fixture pentru a elimina datele reziduale.

---

## 4.3. Salvare explicita in My Account

### Cand
Userul bifeaza checkbox-ul de salvare si continua checkout-ul.

### Atunci
Datele sunt salvate si in My Account pentru utilizari viitoare.

### Regula
Salvarea permanenta se face doar daca checkbox-ul este bifat explicit.

---

# 5. User autentificat fara adresa salvata

## 5.1. Tab: Adresa de livrare

### Cand
Userul este logat, dar nu are adrese standard completate.

### Atunci
Se afiseaza formularul gol, identic cu guest checkout.

### Optiune
- Checkbox: "Salveaza aceasta adresa pentru comenzile viitoare"

### Observatie
Nu exista lista de adrese multiple si nu exista badge-uri de selectie.

---

# 6. Reguli generale UX checkout

## 6.1. Ordinea taburilor

1. Adresa de livrare
2. Tip de livrare
3. Facturare
4. Metoda de plata

---

## 6.2. Taburi completate

### Cand
Un tab este completat corect.

### Atunci
- Se inchide automat.
- Afiseaza rezumat scurt.
- Are buton "Modifica".
- Urmatorul tab devine activ.

---

## 6.3. Taburi inactive

### Cand
Un tab anterior nu este completat.

### Atunci
Taburile urmatoare trebuie sa fie inactive sau estompate vizual.

---

## 6.4. Persistenta datelor

### Regula
Datele completate de user nu trebuie pierdute la:
- inchiderea/redeschiderea taburilor;
- schimbarea intre PF si firma;
- refresh, daca se poate salva temporar local/session;
- intoarcerea din pagina de plata.

Observatiile pentru livrare / curier se trateaza la fel ca restul datelor de adresă: se pastreaza la refresh si la redeschiderea pasului, dar nu se dubleaza in summary card.

---

# 7. Cazuri de testare rapide

## Guest checkout
- [ ] Guest vede formular complet la "Adresa de livrare".
- [ ] Campurile obligatorii sunt validate.
- [ ] Dupa completare, tabul devine rezumat.
- [ ] Butonul "Modifica" redeschide formularul cu datele pastrate.
- [ ] Checkbox-ul "Doresc factura pe firma" afiseaza formularul de firma.
- [ ] Debifarea checkbox-ului ascunde formularul de firma.
- [ ] Datele firmei nu se trimit daca checkbox-ul este debifat.
- [ ] Observatiile pentru livrare / curier pot fi completate si se pastreaza la redeschidere.

## User logat
- [ ] User logat cu adresa standard vede formularul completat.
- [ ] User logat poate modifica datele pentru comanda curenta.
- [ ] User logat poate salva adresa in My Account doar daca bifeaza checkbox-ul.
- [ ] User logat fara adresa salvata vede formularul complet.
- [ ] Nu exista lista de adrese multiple in checkout-ul v1.

## Facturare
- [ ] Facturare PF foloseste adresa de livrare cand checkbox-ul este bifat.
- [ ] Facturare PF poate avea adresa separata.
- [ ] Facturare firma cere CUI si denumire firma.
- [ ] Facturare firma poate fi dezactivata fara sa afecteze adresa de livrare.
- [ ] Observatiile pentru livrare / curier ajung in comanda si in email, daca aceasta este regula finala stabilita.

---

# 8. Mock data pentru userii de test

- guest.checkout@test.local
  - Observatii pentru livrare / curier: "Interfon 12. Curierul sa sune inainte."
- checkout.noaddress@test.local
  - Observatii pentru livrare / curier: "Acces prin spatele cladirii."
- checkout.cleannoaddress@test.local
  - Observatii pentru livrare / curier: "Acces prin poarta secundara."
- checkout.oneaddress@test.local
  - Observatii pentru livrare / curier: "Livrare dupa ora 14:00."
- cont legacy istoric
  - Folosit doar pentru verificari de compatibilitate, daca este nevoie.

## Reset rapid fixture-uri

Pentru a relua testele curat, ruleaza:

```bash
wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.sh
```

Pentru un singur cont:

```bash
wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.sh checkout.oneaddress@test.local
```
