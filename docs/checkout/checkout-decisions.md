# Checkout Decisions

## 2026-06-27 - Pasul 1 simplificat pentru versiunea 1

Decizie:

- Checkout-ul v1 nu mai folosește multiple saved addresses.

Motiv:

- Flow-ul devenise prea încărcat și amesteca două responsabilități diferite:
  - plasarea comenzii;
  - administrarea profilului.

Ce rămâne în checkout:

- formularul de adresă completat de la zero pentru guest;
- formularul precompletat din adresa standard WooCommerce pentru user logat;
- editarea datelor pentru comanda curentă;
- checkbox opțional pentru salvarea adresei în My Account.

Ce rămâne în My Account:

- adresa standard WooCommerce de facturare;
- adresa standard WooCommerce de livrare;
- editare din pagina Contul meu → Adrese.

Impact tehnic:

- checkout nu mai citește listă de adrese multiple în UI;
- checkout actualizează WooCommerce session pentru comanda curentă;
- user meta se actualizează doar când checkbox-ul de salvare este bifat explicit;
- handler-ele legacy de address book rămân doar pentru compatibilitate istorică și nu sunt rulate în UI-ul curent.

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

- Guest-ul completează adresa de la zero și nu primește Address Book.

Motiv:

- simplifică flow-ul;
- elimină stările inutile;
- reduce codul, AJAX-ul și riscul de bug-uri.

Reguli aplicate:

- guest flow = formular → summary card → edit aceeași adresă;
- nu există listă de adrese pentru guest;
- nu există buton `Adaugă adresă nouă` pentru guest;
- adresele guest rămân strict în sesiunea curentă de checkout.

## 2026-06-27 - Address Selection Rules

Decizie:

- UI-ul adreselor din Pasul 1 nu mai depinde de un address book multiplu; checkout-ul folosește o singură adresă curentă.

Motiv:

- elimină diferențele artificiale între guest și user logat;
- păstrează un comportament previzibil și ușor de testat;
- evită stări vizuale inutile atunci când există o singură adresă.

Reguli aplicate:

- checkout v1 folosește o singură adresă activă;
- nu există selecție între mai multe carduri în UI-ul curent;
- nu se folosește badge-ul `Adresa implicită din cont`;
- modificările din checkout afectează doar comanda curentă, iar salvarea în cont este explicită prin checkbox;
- summary card-ul trebuie să reflecte exact datele care ajung în comandă.

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


## 2026-06-26 - Comentariile QA din staging sunt backlog oficial

Decizie:

- Comentariile salvate in `Preview` pe staging devin backlog QA oficial pana la rezolvare.

Motiv:

- feedback-ul lasat direct pe staging este legat de mediul oficial de validare;
- observatiile trebuie sa ramana vizibile pana la fix si retestare;
- fara aceasta regula, un sync de DB poate sterge exact problemele pe care trebuie sa le rezolvam.

Reguli aplicate:

- cand utilizatorul cere citirea comentariilor, ele se citesc de pe staging;
- comentariile colectate se transforma in lista de probleme si apoi in taskuri concrete;
- comentariile QA active de pe staging nu se suprascriu prin import de DB fara confirmare explicita.
