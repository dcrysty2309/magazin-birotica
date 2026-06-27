# Checkout Test Index

Indexul vizual pentru verificarea cazurilor de testare Checkout este disponibil la:

- `http://localhost:8080/checkout-test-cases/`
- pe staging trebuie să existe aceeași pagină de index pentru validarea finală a cazurilor;

Scop:

- să ai toate cazurile într-un tabel simplu;
- să poți deschide preview-ul fiecărui caz;
- să poți merge direct în checkout din headerul paginii;
- pagina este indexul oficial pentru QA manual al Pasului 1 atât local, cât și pe staging;
- comentariile din Preview sunt baza pentru backlog-ul de bug-uri și trebuie păstrate pentru staging.

Cum se citește pagina:

- tabelul principal conține doar ID, scenariu, tip utilizator, număr de adrese și Preview;
- panelul de Preview conține Tip cont, User / parolă, Cum se reproduce, Expected result, istoricul comentariilor și formularul pentru comentariu nou;
- comentariile se salvează în WordPress, în CPT-ul intern `pap_checkout_comment`, prin AJAX;
- la redeschiderea Preview-ului se reîncarcă istoricul complet al comentariilor pentru cazul curent;
- pagina afișează și o listă compactă cu cazurile care au observații, cu mark vizual pentru cele care au comentarii deschise;
- pagina este dedicată doar Pasului 1 - Adresa de livrare.
- comentariile și bug-urile trebuie păstrate și documentate și pe staging, nu doar local;
- orice scenariu nou se adaugă în tabel înainte de a fi testat.

Reguli vizuale de bază rămase valabile:

- guest = formular deschis;
- user logat cu adresă standard = formular completat;
- salvarea explicită în cont se face doar prin checkbox;
- nu apare niciodată badge-ul `Adresa implicită din cont`;
- nu există listă de adrese multiple în checkout-ul v1.
