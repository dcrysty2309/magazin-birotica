# Checkout Test Index

Indexul vizual pentru verificarea cazurilor de testare Checkout este disponibil la:

- `http://localhost:8080/checkout-test-cases/`
- pe staging trebuie să existe aceeași pagină de index pentru validarea finală a cazurilor;

Scop:

- să ai toate cazurile într-un tabel simplu;
- să poți deschide preview-ul fiecărui caz;
- să poți merge direct în checkout din headerul paginii.
- pagina este indexul oficial pentru QA manual al Pasului 1 atât local, cât și pe staging;
- comentariile din Preview sunt baza pentru backlog-ul de bug-uri și trebuie păstrate pentru staging.

Cum se citește pagina:

- tabelul principal conține doar ID, scenariu, tip utilizator, număr de adrese și Preview;
- panelul de Preview conține doar Tip cont, User / parolă, Cum se reproduce și Expected result;
- la finalul preview-ului există o zonă de Comentarii testare, salvată local pentru fiecare caz;
- pagina afișează și o listă compactă cu cazurile care au comentarii salvate;
- pagina este dedicată doar Pasului 1 - Adresa de livrare.
- comentariile și bug-urile trebuie păstrate și documentate și pe staging, nu doar local.

Reguli vizuale de bază rămase valabile:

- `0 adrese` = formular deschis;
- `1 adresă` = card neutru, border `#dbe4f0`, fără label de selecție;
- `2+ adrese` = card selectat cu border `rgba(13, 46, 97, 0.22)` și fundal `#f8fbff`;
- label-urile `Selectată pentru livrare` și `Adresa implicită din cont` apar doar la 2+ adrese.
