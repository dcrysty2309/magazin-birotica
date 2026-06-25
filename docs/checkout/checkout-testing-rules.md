# Checkout Testing Rules

## 1. Reguli generale

- Orice modificare în checkout trebuie testată înainte de finalizare.
- Nu trecem la pasul următor până când pasul curent nu este stabil.
- Nu este suficient ca implementarea să aibă sintaxă validă.
- Trebuie testat flow-ul real în UI.
- Testarea rapidă se poate face local.
- Testarea finală a flow-urilor se face pe staging.
- Local este mediul de dezvoltare, staging este QA oficial.
- Datele de staging sunt date de test, nu date reale.
- Nu se face sincronizare automată din staging înapoi în local.
- Conturile de test sunt aceleași pe local și staging.
- Dacă un test schimbă adrese sau comenzi, trebuie să existe o metodă clară de resetare a datelor de test.
- Comentariile din Preview devin lista oficială de bug-uri pentru staging.
- După fix, scenariile afectate se retestează tot pe staging înainte de următorul deploy.

## 2. Reguli pentru Pasul 1

Teste minime:

1. guest fără cont, formular gol
2. guest completează și salvează
3. guest refresh după salvare
4. user logat cu adresă salvată, afișare listă
5. user logat cu adresă salvată, selectare adresă
6. user logat, Adaugă adresă nouă
7. user logat, salvare adresă temporară
8. user logat, refresh după adresă temporară

Pentru referinta rapida a tuturor cazurilor, foloseste indexul vizual:

- `http://localhost:8080/checkout-test-cases/`
- acolo gasesti ordinea cazurilor, regulile de border, label-uri si datele de test;
- `Preview` deschide panoul lateral cu detaliile cazului și comentariile de testare;
- pagina afișează o listă compactă cu cazurile care au observații salvate în WordPress.
- Pentru verificarea finală pe QA, același index trebuie folosit împreună cu staging.
- Comentariile salvate în Preview sunt persistate în WordPress, în CPT-ul intern `pap_checkout_comment`, și se reîncarcă la fiecare redeschidere a Preview-ului.
- Cazurile cu observații apar și în lista compactă de pe index, iar cazurile cu comentarii deschise primesc un marcaj vizual discret în tabel.

Pentru guest:

- trebuie să existe doar un singur flux: formular → salvare → summary card → editare aceleiași adrese;
- nu se testează listă de adrese sau adresă nouă pentru guest, pentru că acele stări nu există;
- butonul `Adaugă adresă nouă` nu trebuie să apară niciodată în guest flow.

## 3. Reguli de validare

- Validările frontend existente trebuie să continue să funcționeze.
- Mesajele de eroare trebuie să rămână consistente cu restul aplicației.
- Validările backend trebuie verificate separat de cele frontend.

## 4. Reguli pentru butoane

- Fiecare pas trebuie verificat vizual pentru un singur CTA principal.
- Acțiunile secundare, inclusiv `Înapoi la adrese`, trebuie să aibă aceeași înălțime și să fie tratate ca un grup împreună cu CTA-ul principal.
- Pe desktop, verifică alinierea și proporțiile butoanelor.
- Pe mobil, verifică aceeași ordine și aceeași ierarhie în toate etapele checkout-ului.
- Pentru pașii intermediari, verifică textul scurt `Continuă`, nu texte extinse precum `Continuă către livrare`.
- Verifică faptul că lățimea butoanelor este determinată de conținut și padding, nu de o lățime fixă pe ecran.
- Verifică faptul că butoanele principale și secundare au același font-size, 14px, și aceeași line-height.

## 4.1. Reguli pentru Address Card

- Summary card-ul folosește iconuri outline de 22x22px pentru user, locație, telefon și email.
- Toate textele sunt la `font-weight: 400` și au 14px în address card, cu excepția numelui, care este `600`.
- Textele din rândurile cu icon sunt la 14px.
- Fiecare rând este `display: flex`, `align-items: center`, cu `gap: 16px`.
- Spațierea dintre rânduri este de 12px.
- Line-height-ul liniilor din card este 1.45 și trebuie să fie uniform peste tot.
- 0 adrese: formular deschis.
- 1 adresă: card neutru, fără label de selecție.
- 2+ adrese: listă de carduri, cu o singură adresă selectată.
- Border-ul de selecție apare doar când există minimum 2 adrese.
- Label-ul principal este `Selectată pentru livrare`.
- Label-ul secundar este `Adresa implicită din cont`.
- Label-urile apar doar când există minimum 2 adrese.
- Nu se folosesc checkbox-uri, radio button-uri sau iconuri pentru selecție.
- Pentru guest, nu există butonul `Adaugă adresă nouă`.
- Userul logat poate vedea `Adaugă adresă nouă` doar când există deja cel puțin o adresă în checkout.
- Verifică dacă adresa afișată în card este identică cu datele salvate în sesiunea checkout-ului.
- Verifică faptul că observațiile pentru livrare / curier nu apar în summary card, dacă acesta este standardul ales pentru UI.

## 4.2. Reguli pentru formularul de adresă

- Ordinea câmpurilor este: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- Nu trebuie să existe câmp separat pentru Bloc / Scară / Etaj / Apartament.
- Detaliile de tip apartament sau scară se notează în Adresă sau în Observații pentru livrare / curier.
- Observațiile trebuie să fie opționale și să nu blocheze fluxul dacă sunt goale.
- Formularul de adresă din My Account trebuie să folosească aceeași ordine și aceleași câmpuri ca formularul din checkout.
- Dacă există date legacy cu `address_2`, ele se tratează doar ca backward compatibility și nu mai apar ca input separat în UI.

## 5. Reguli pentru screenshot-uri

- Screenshot-urile se salvează în `docs/checkout/testing/screenshots/`.
- La rerulare se șterg capturile vechi care nu mai sunt relevante.
- Denumirile trebuie să fie clare și predictibile.

## 6. Reguli pentru comentarii de testare

- Fiecare caz poate avea un comentariu salvat din Preview.
- Fiecare caz poate avea mai multe comentarii salvate din Preview.
- Comentariile se păstrează în WordPress, în CPT-ul intern `pap_checkout_comment`, și se reîncarcă la redeschiderea Preview-ului.
- Comentariile sunt afișate cronologic în Preview.
- Pagina afișează o listă compactă cu cazurile care au observații și marchează discret rândurile cu comentarii deschise.
- Statusurile oficiale sunt: `open`, `in_progress`, `fixed`, `ignored`.
- Un comentariu nou pornește cu status `open`.
- Un comentariu se marchează ca `fixed` după ce bug-ul a fost reparat și retestat.
- `ignored` se folosește doar pentru observații confirmate ca nefiind bug.
- Dacă editezi un comentariu existent, se actualizează aceeași intrare și se schimbă doar `updated_at`.
- Dacă adaugi un comentariu nou, se creează o intrare nouă în istoricul test case-ului.
- Comentariile servesc ca bază pentru trierea bug-urilor și pentru generarea de fix-uri ulterioare.
- Comentariile și bug-urile relevante trebuie păstrate și documentate și pe staging, nu doar local.
- Comentariile oficiale sunt sursa de backlog pentru bug fix și pot fi exportate din baza de date / staging.

## 7. Raport final

Orice task de checkout se încheie cu:

- fișiere modificate;
- cazuri testate;
- screenshot-uri;
- bug-uri găsite;
- ce nu a putut fi testat;
- recomandări pentru pasul următor.
