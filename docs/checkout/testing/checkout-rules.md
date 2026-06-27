# Checkout Testing Rules

Acest document completează regulile oficiale din `docs/checkout/checkout-rules.md`.

## Reguli de testare

- Local = development.
- Staging = QA oficial.
- Implementarea se face local, dar validarea finală se face pe staging.
- Folosește întotdeauna conturile de test documentate.
- Dacă un test modifică adrese, comenzi sau sesiuni, trebuie făcut reset la datele de test.
- Comentariile din `Preview` de pe pagina de testare sunt backlog oficial până la rezolvare.
- Nu se consideră test trecut fără verificare manuală în browser.

## Pasul 1 - Adresă de livrare

Reguli de testare:

- Guest-ul completează adresa de la zero.
- Userul logat vede formularul completat din datele standard WooCommerce, dacă există.
- Userul logat poate edita datele pentru comanda curentă.
- Userul logat salvează adresa în My Account doar dacă bifează explicit checkbox-ul de salvare.
- Nu există address book cu multiple adrese salvate în checkout.
- Nu există badge de adrese multiple sau adresă implicită custom.
- Eroarea de salvare se afișează sus, nu sub butoane.
- Ordinea câmpurilor este: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- `Bloc / Scară / Etaj / Apartament` nu mai este câmp separat.

## Checklist de test

- guest checkout cu adresă nouă;
- user logat fără adresă salvată;
- user logat cu adresă standard completată;
- user logat editează fără să salveze;
- user logat editează și bifează salvarea;
- verificare că My Account se actualizează doar dacă bifează salvarea;
- verificare că adresa din comandă rămâne corectă indiferent de salvare.
