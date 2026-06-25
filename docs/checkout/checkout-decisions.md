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

## 2026-06-25 - Sistemul de butoane din checkout

Decizie:

- Butoanele din checkout folosesc un design system unic, cu CTA principal și acțiuni secundare tratate ca grup.

Motiv:

- menține consistența vizuală între pași;
- reduce variațiile inutile de spațiere, înălțime și lățime;
- face checkout-ul mai ușor de întreținut pe termen lung.

Reguli aplicate:

- CTA principal rămâne vizibil și dominant;
- acțiunile secundare rămân discrete, dar la aceeași înălțime;
- pe desktop, acțiunile sunt aliniate ca un singur grup;
- pe mobil, layout-ul poate stivui butoanele, dar păstrează aceeași ordine și ierarhie.
- pașii intermediari folosesc textul scurt `Continuă`;
- lățimea butoanelor este determinată de conținut și padding, nu de valori fixe per pas.
- butoanele principale și secundare folosesc același font-size, 14px, și aceeași line-height.

## 2026-06-25 - Address Card Icons

Decizie:

- Address card-ul folosește un set fix de SVG-uri inline pentru user, locație, telefon și email.

Motiv:

- menține consistența vizuală între PHP render și re-randarea JS;
- evită iconuri diferite între stări;
- păstrează cardul aerisit și ușor de scanat.

## 2026-06-25 - Address Card Typography

Decizie:

- Address card-ul folosește text uniform la 14px; numele are `font-weight: 600`, iar restul textului rămâne la `400`.

Motiv:

- cardul trebuie să fie calm, ușor de scanat și să nu concureze vizual cu CTA-ul principal.

Reguli aplicate:

- toate iconurile au 22x22px;
- toate rândurile sunt aliniate cu `display: flex` și `gap: 16px`;
- spațierea dintre rânduri este de 12px;
- line-height-ul tuturor liniilor este 1.45;
- numele este ușor accentuat cu `font-weight: 600`, iar restul datelor rămâne la `400`.
- textele din rândurile cu icon au 14px, pentru o ierarhie mai discretă.

## 2026-06-25 - Guest Address Management

Decizie:

- Guest-ul are o singură adresă activă în checkout și nu primește Address Book.

Motiv:

- simplifică flow-ul;
- elimină stările inutile;
- reduce codul, AJAX-ul și riscul de bug-uri.

Reguli aplicate:

- guest flow = formular → summary card → edit aceeași adresă;
- nu există listă de adrese pentru guest;
- nu există buton `Adaugă adresă nouă` pentru guest;
- adresele guest rămân strict în sesiunea curentă de checkout.

## 2026-06-25 - Address Selection Rules

Decizie:

- UI-ul adreselor din Pasul 1 depinde de numărul de adrese disponibile în checkout.

Motiv:

- elimină diferențele artificiale între guest și user logat;
- păstrează un comportament previzibil și ușor de testat;
- evită stări vizuale inutile atunci când există o singură adresă.

Reguli aplicate:

- 0 adrese: formular deschis;
- 1 adresă: card neutru, fără label de selecție;
- 2+ adrese: listă de carduri, cu o singură adresă selectată;
- border-ul de selecție apare doar când există minimum 2 adrese;
- label-ul principal este `Selectată pentru livrare`;
- dacă adresa selectată este și adresa implicită din cont, apare și label-ul `Adresa implicită din cont`;
- nu se folosesc checkbox-uri, radio button-uri sau iconuri pentru selecție;
- selecția schimbată în checkout actualizează sesiunea WooCommerce și adresa folosită în comandă.

## 2026-06-25 - Address Form Field Order

Decizie:

- Formularul de adresă urmează ordinea: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.

Motiv:

- face formularul mai clar și mai ușor de completat;
- elimină un câmp separat care dubla informația de adresă;
- păstrează observațiile disponibile fără să încarce summary card-ul.

Reguli aplicate:

- nu există câmp separat pentru Bloc / Scară / Etaj / Apartament;
- detaliile suplimentare se introduc în Adresă sau în Observații pentru livrare / curier;
- observațiile nu apar în summary card, dar se păstrează pentru comanda curentă.

## 2026-06-26 - My Account address schema parity

Decizie:

- Formularul de adresă din My Account trebuie să urmeze aceeași structură ca formularul de adresă din checkout.

Motiv:

- evită dublarea logicii și diferențele de UI între cele două fluxuri;
- face adresele salvate și adresele temporare compatibile cu același standard de câmpuri;
- reduce bug-urile generate de câmpuri diferite între checkout și cont.

Reguli aplicate:

- câmpurile sunt: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier;
- nu se mai afișează un câmp separat pentru Bloc / Scară / Etaj / Apartament;
- dacă există date legacy `address_2`, ele sunt tratate doar ca compatibilitate istorică.
