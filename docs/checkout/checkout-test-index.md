# Checkout Test Index

Indexul vizual pentru verificarea cazurilor de testare Checkout este disponibil la:

- `http://localhost:8080/checkout-test-cases/`
- pe staging trebuie să existe aceeași pagină de index pentru validarea finală a cazurilor;

Scop:

- să ai doar cazurile esențiale pentru Pasul 1 într-un tabel simplu;
- să poți deschide preview-ul fiecărui caz;
- să poți merge direct în checkout din headerul paginii;
- pagina este indexul oficial pentru QA manual al Checkout Phase 1 atât local, cât și pe staging;
- comentariile din Preview sunt baza pentru backlog-ul de bug-uri și trebuie păstrate pentru staging.

Cum se citește pagina:

- tabelul principal conține doar cazurile recomandate pentru testarea rapidă;
- coloanele din tabel sunt ID, scenariu, tip utilizator, număr de adrese și Preview;
- panelul de Preview conține Tip cont, User / parolă, Cum se reproduce, Expected result, istoricul comentariilor și formularul pentru comentariu nou;
- comentariile se salvează în WordPress, în CPT-ul intern `pap_checkout_comment`, prin AJAX;
- la redeschiderea Preview-ului se reîncarcă istoricul complet al comentariilor pentru cazul curent;
- pagina afișează și o listă compactă cu cazurile care au observații, cu mark vizual pentru cele care au comentarii deschise;
- pagina este organizată pe două secțiuni: Pasul 1 - Adresa de livrare și Phase 1 - shipping + payment.
- comentariile și bug-urile trebuie păstrate și documentate și pe staging, nu doar local;
- orice scenariu nou se adaugă în tabel înainte de a fi testat.

Setul curent de cazuri recomandate:

- Pasul 1 (adresă):
  - `1.1` Guest - stare inițială;
  - `1.2` Guest - formular completat și salvat;
  - `1.3` User logat - adresa din cont în summary;
  - `1.4` User logat - modifică adresa și salvează în cont;
- Phase 1 (shipping și plată):
  - `2.1` Guest - adresă incompletă, transport indisponibil;
  - `2.2` Guest - adresă completă, transport calculat;
  - `2.3` Guest - transport gratuit la pragul de 150 lei;
  - `2.4` Guest - ramburs activ, comandă test și redirect la thank-you page;
  - `2.5` Guest - nicio metodă de plată activă;
  - `2.6` Guest - ramburs și meta corecte în Admin.

Reguli vizuale de bază rămase valabile:

- guest = formular deschis;
- user logat cu adresă standard = formular completat;
- salvarea explicită în cont se face doar prin checkbox;
- nu apare niciodată badge-ul `Adresa implicită din cont`;
- nu există listă de adrese multiple în checkout-ul v1.
