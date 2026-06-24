# SupplyHub Checkout - Cazuri de testare si cerinte UX

## Scop
Definim pas cu pas comportamentul checkout-ului pentru utilizatori guest si utilizatori autentificati, astfel incat implementarea sa fie clara si usor de continuat.

---

# 1. User fara cont / Guest checkout

## 1.1. Tab: Adresa de livrare - stare initiala

### Cand
Userul intra in checkout fara sa fie autentificat si nu exista date salvate.

### Atunci
Tabul „Adresa de livrare” trebuie sa fie deschis si sa afiseze formularul complet.
Rezumatul nu trebuie sa fie vizibil in aceasta stare.
Pasul 2 trebuie sa ramana dezactivat pana la salvarea adresei.

### Campuri afisate
- Nume *
- Prenume *
- Telefon *
- Email *
- Judet *
- Localitate *
- Strada *
- Numar *
- Bloc / scara / etaj / apartament
- Cod postal

### Optiuni
- Checkbox: „Datele de facturare sunt aceleasi cu adresa de livrare”
- Checkbox optional: „Creeaza cont dupa finalizarea comenzii”

### Buton
- „Continua catre livrare”

---

## 1.2. Validare formular adresa de livrare

### Cand
Userul apasa „Continua catre livrare” fara sa completeze campurile obligatorii.

### Atunci
Trebuie afisate erori clare langa campurile lipsa.

### Reguli
- Numele este obligatoriu.
- Prenumele este obligatoriu.
- Telefonul este obligatoriu.
- Emailul este obligatoriu si trebuie sa aiba format valid.
- Judetul este obligatoriu.
- Localitatea este obligatorie.
- Strada este obligatorie.
- Numarul este obligatoriu.

---

## 1.3. Tab: Adresa de livrare - dupa completare

### Cand
Userul completeaza corect formularul si apasa „Continua catre livrare”.

### Atunci
Tabul „Adresa de livrare” se inchide si devine un rezumat.
Formularul nu mai este vizibil.
Butonul „Continua catre livrare” nu mai este vizibil.
Doar summary-ul adresei ramane afisat.
Pasul 2 devine activ.
In DOM trebuie sa existe o singura stare activa: fie formularul in edit mode, fie summary-ul in completed mode, niciodata ambele simultan.

### Rezumat afisat
- Nume complet
- Adresa completa
- Telefon
- Email
- Link/buton: „Modifica”

### Exemplu UI
Cristian Diaconescu  
Str. Victoriei 45, Sector 1, Bucuresti  
0740 123 456  
d.crysty23@gmail.com  
Modifica

---

## 1.4. Actiune: Modifica adresa de livrare

### Cand
Userul apasa „Modifica” pe tabul „Adresa de livrare”.

### Atunci
Tabul se redeschide cu toate datele completate anterior.
Rezumatul se ascunde.
Pasul 2 se dezactiveaza pana la reconfirmarea adresei.

### Observatie
Datele nu trebuie pierdute dupa inchidere/deschidere.

---

# 2. User fara cont - Facturare persoana fizica

## 2.1. Cand checkbox-ul „Datele de facturare sunt aceleasi cu adresa de livrare” este bifat

### Atunci
Tabul „Facturare” trebuie sa afiseze doar un rezumat simplu.

### Continut
- Checkbox: „Doresc factura pe firma” - nebifat
- Card rezumat:
  - Nume complet
  - Adresa de livrare
  - Text discret: „Factura va fi emisa pe persoana fizica”
- Buton/link: „Modifica”

---

## 2.2. Cand userul debifeaza „Datele de facturare sunt aceleasi cu adresa de livrare”

### Atunci
In tabul „Facturare” trebuie afisat formular separat pentru adresa de facturare PF.

### Campuri
- Nume *
- Prenume *
- Judet *
- Localitate *
- Strada *
- Numar *
- Bloc / scara / etaj / apartament
- Cod postal

---

# 3. User fara cont - Facturare persoana juridica / firma

## 3.1. Activare factura pe firma

### Cand
Userul bifeaza „Doresc factura pe firma”.

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
Userul debifeaza „Doresc factura pe firma”.

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
Tabul „Adresa de livrare” trebuie sa afiseze adresa implicita ca rezumat.

### Continut
- Nume complet
- Adresa completa
- Telefon
- Email
- Buton: „Modifica”
- Buton/link: „Adauga adresa noua”

---

## 4.2. Selectare alta adresa

### Cand
Userul apasa „Modifica”.

### Atunci
Trebuie afisata lista de adrese salvate plus optiunea de a adauga una noua.

### Comportament
- Userul poate selecta o adresa existenta.
- Userul poate edita o adresa existenta.
- Userul poate adauga o adresa noua.

---

# 5. User autentificat fara adresa salvata

## 5.1. Tab: Adresa de livrare

### Cand
Userul este logat, dar nu are adrese salvate.

### Atunci
Se afiseaza acelasi formular ca la guest checkout.

### Diferenta
Dupa salvare, adresa poate fi salvata in contul userului.

### Optiune
- Checkbox: „Salveaza aceasta adresa in contul meu”

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
- Are buton „Modifica”.
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

---

# 7. Cazuri de testare rapide

## Guest checkout
- [ ] Guest vede formular complet la „Adresa de livrare”.
- [ ] Campurile obligatorii sunt validate.
- [ ] Dupa completare, tabul devine rezumat.
- [ ] Butonul „Modifica” redeschide formularul cu datele pastrate.
- [ ] Checkbox-ul „Doresc factura pe firma” afiseaza formularul de firma.
- [ ] Debifarea checkbox-ului ascunde formularul de firma.
- [ ] Datele firmei nu se trimit daca checkbox-ul este debifat.

## User logat
- [ ] User logat cu adresa salvata vede rezumatul adresei.
- [ ] User logat poate schimba adresa.
- [ ] User logat poate adauga o adresa noua.
- [ ] User logat fara adresa salvata vede formularul complet.
- [ ] User logat poate salva adresa noua in cont.

## Facturare
- [ ] Facturare PF foloseste adresa de livrare cand checkbox-ul este bifat.
- [ ] Facturare PF poate avea adresa separata.
- [ ] Facturare firma cere CUI si denumire firma.
- [ ] Facturare firma poate fi dezactivata fara sa afecteze adresa de livrare.
