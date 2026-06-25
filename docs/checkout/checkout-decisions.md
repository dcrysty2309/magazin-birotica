# Checkout Decisions

## 2026-06-25 - Pasul 1 pentru user logat

Decizie:

- Checkout-ul nu mai editează adresele salvate în cont.

Motiv:

- Flow-ul devenise prea încărcat și amesteca două responsabilități diferite:
  - plasarea comenzii;
  - administrarea profilului.

Ce rămâne în checkout:

- selecția unei adrese salvate;
- adăugarea unei adrese temporare pentru comanda curentă;
- summary card pentru adresa temporară.

Ce rămâne în My Account:

- creare adresă salvată;
- editare adresă salvată;
- ștergere adresă salvată;
- alegere adresă implicită.

Impact tehnic:

- checkout nu mai apelează handler-ul generic de address book pentru editare/salvare în cont;
- checkout nu mai actualizează user meta pentru adrese din My Account;
- selecția adresei salvate și adresa temporară actualizează doar sesiunea WooCommerce folosită pentru comandă.

