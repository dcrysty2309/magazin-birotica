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

- un tabel simplu cu toate cazurile de testare;
- preview pentru fiecare caz cu tip cont / user-parola / reproduce / expected;
- comentarii de testare persistate în WordPress, prin CPT-ul intern `pap_checkout_comment`;
- o listă compactă cu cazurile care au observații, plus istoricul fiecărui caz în Preview;
- pagina este dedicată doar Pasului 1 - Adresa de livrare;
- regulile pentru border, label-uri si numarul de adrese.

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

# 4. User autentificat cu adresa salvata

## 4.1. Tab: Adresa de livrare

### Cand
Userul este logat si are cel putin o adresa salvata.

### Atunci
Tabul "Adresa de livrare" trebuie sa afiseze un singur card neutru daca exista o singura adresa sau o lista de carduri daca exista 2+ adrese.

### Continut
- 1 adresă: card neutru, fara label de selectie.
- 2+ adrese: lista de carduri cu o singura adresa selectata.
- Label principal: "Selectata pentru livrare" doar la 2+ adrese.
- Label secundar: "Adresa implicita din cont" pentru adresa implicita, doar la 2+ adrese.
- Nume complet
- Adresa completa
- Judet + localitate
- Cod postal
- Telefon
- Email
- Buton/link: "Adauga adresa noua" doar daca exista cel putin o adresa in checkout si utilizatorul este logat.

---

## 4.2. Selectare alta adresa

### Cand
Userul apasa "Modifica".

### Atunci
Trebuie afisata lista de adrese salvate plus optiunea de a adauga una noua.

### Comportament
- Userul poate selecta o adresa existenta.
- Userul poate edita o adresa existenta.
- Userul poate adauga o adresa noua pentru comanda curenta.
- Schimbarea selectiei actualizeaza sesiunea WooCommerce, shipping fields si recalcularea transportului.
- Formularul folosit in modalul My Account pentru adrese trebuie sa aiba aceleasi campuri ca formularul de checkout: Prenume, Nume, Email, Telefon, Judet, Localitate, Adresa, Cod postal, Observatii pentru livrare / curier.
- Câmpul separat Bloc / Scară / Etaj / Apartament nu mai trebuie expus ca input separat; detaliile se pun în Adresă sau în Observații.

---

## 4.3. Adresa noua adaugata in checkout

### Cand
Userul logat are deja o adresa salvata in My Account si adauga o adresa noua din checkout.

### Atunci
- noua adresa apare ca al doilea card in checkout;
- noua adresa devine selectata pentru livrare;
- noua adresa este folosita pentru comanda curenta;
- noua adresa nu se salveaza automat in My Account;
- dupa refresh, daca sesiunea checkout este activa, adresa temporara ramane disponibila;
- My Account ramane neschimbat.
- Adresa adaugata in checkout trebuie sa reuseasca acelasi set de campuri ca My Account, fara bloc/scara/etaj/apartament separat.

### Reproducere rapida
- autentifica-te cu `checkout.oneaddress@test.local`;
- adauga un produs in cos;
- deschide `/checkout/`;
- apasa `Adauga adresa noua`;
- completeaza si salveaza adresa noua.

---

# 5. User autentificat fara adresa salvata

## 5.1. Tab: Adresa de livrare

### Cand
Userul este logat, dar nu are adrese salvate.

### Atunci
Se afiseaza acelasi formular ca la guest checkout.

### Diferenta
Dupa salvare, adresa poate fi salvata in contul userului, daca exista functionalitatea aferenta.

### Optiune
- Checkbox: "Salveaza aceasta adresa in contul meu"

## 5.2. Verificare selectie adrese

### Cand
Userul logat are 2+ adrese salvate.

### Atunci
- cardul selectat are border discret;
- label-ul "Selectata pentru livrare" apare doar pe cardul activ;
- daca adresa activa este si adresa implicita din cont, apare si label-ul "Adresa implicita din cont";
- celelalte carduri raman neutre;
- click pe alt card muta imediat selectia;
- selectionarea actualizeaza checkout session si summary-ul.

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
- [ ] User logat cu adresa salvata vede rezumatul adresei.
- [ ] User logat poate schimba adresa.
- [ ] User logat poate adauga o adresa noua pentru comanda curenta.
- [ ] User logat cu o adresa in My Account poate adauga o adresa noua in checkout si aceasta devine selectata.
- [ ] User logat fara adresa salvata vede formularul complet.
- [ ] User logat poate salva adresa noua in cont, daca este permis.
- [ ] User logat cu mai multe adrese vede lista de carduri si selectia corecta.

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
- checkout.oneaddress@test.local
  - Observatii pentru livrare / curier: "Livrare dupa ora 14:00."
- checkout.multiaddress@test.local
  - Observatii pentru livrare / curier: "Se livreaza doar in intervalul 12:00-16:00."
