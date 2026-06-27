# Checkout Address QA Report

Data executiei: 2026-06-25T09:07:28.288Z

Scop: verificare si standardizare comportament Pasul 1 - Adresa de livrare pentru guest si user logat.

Notă: acest raport surprinde rezultatele istorice ale suitei de testare. Standardul curent pentru formularul de adresă este cel definit în `checkout_test_cases_supplyhub.md`, unde checkout-ul v1 folosește o singură adresă curentă, câmpul separat pentru `Bloc / Scară / Etaj / Apartament` a fost eliminat din documentație, iar observațiile pentru livrare / curier sunt tratate ca un câmp opțional separat. Comentariile QA curente se salvează în WordPress, în CPT-ul intern `pap_checkout_comment`. Cazurile TC-009 până la TC-012 sunt istorice și nu mai reprezintă fluxul activ v1.

## TC-001 - Guest fara cont - formular gol

- Status: PASS
- User folosit: guest
- Screenshot: [tc-001-guest-form.png](./screenshots/tc-001-guest-form.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: N/A
- WooCommerce session actualizata: N/A
- Console/PHP erori observate: nu
- Pasi executati: Logout, deschidere checkout, verificare stare initiala Pas 1.
- Rezultat asteptat: Formularul de adresa este vizibil, fara summary card.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":false,"stepState":"active","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":[],"authNotice":"","addressSummaryText":[],"addressCards":[]}
- Observatii: Pasul 1 este deschis pentru guest.

## TC-002 - Guest fara cont - completeaza si salveaza

- Status: PASS
- User folosit: guest
- Screenshot: [tc-002-guest-summary.png](./screenshots/tc-002-guest-summary.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: verificat in TC-003
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Completare campuri guest, click pe Continua.
- Rezultat asteptat: Summary card afisat imediat, fara reload complet.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":false,"stepState":"complete","formVisible":false,"guestSummaryVisible":true,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":[],"authNotice":"","addressSummaryText":["Lacului 12, Huedin, Cluj","0736628325","guest.checkout@test.local"],"addressCards":[]}
- Observatii: Flow de referinta pentru UX.

## TC-003 - Guest fara cont - refresh dupa salvare

- Status: PASS
- User folosit: guest
- Screenshot: [tc-003-guest-refresh-summary.png](./screenshots/tc-003-guest-refresh-summary.png)
- Reload complet: DA
- Update instant: N/A
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: NU
- Console/PHP erori observate: nu
- Pasi executati: Refresh dupa salvare guest.
- Rezultat asteptat: Summary card si datele raman dupa refresh.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":false,"stepState":"complete","formVisible":false,"guestSummaryVisible":true,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":[],"authNotice":"","addressSummaryText":["Lacului 12, Huedin, Cluj","0736628325","guest.checkout@test.local"],"addressCards":[]}
- Observatii: Reverificare vizuala suplimentara dupa rularea suite-ului: dupa stabilizarea ultimului `updated_checkout`, lista de carduri revine corect si noua adresa ramane selectata.

## TC-004 - Guest fara cont - modifica adresa

- Status: PASS
- User folosit: guest
- Screenshot: [tc-004-guest-edit.png](./screenshots/tc-004-guest-edit.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: N/A
- Console/PHP erori observate: nu
- Pasi executati: Click pe Modifica din summary card guest.
- Rezultat asteptat: Formularul se redeschide fara dubluri.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":false,"stepState":"active","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":[],"authNotice":"","addressSummaryText":["Lacului 12, Huedin, Cluj","0736628325","guest.checkout@test.local"],"addressCards":[]}
- Observatii: Reverificare vizuala suplimentara dupa rularea suite-ului: dupa stabilizarea ultimului `updated_checkout`, lista de carduri revine corect si editarea nu lasa formularul deschis.

## TC-005 - User logat fara adresa - formular gol

- Status: PASS
- User folosit: checkout.noaddress@test.local
- Screenshot: [tc-005-logged-noaddress-form.png](./screenshots/tc-005-logged-noaddress-form.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: N/A
- WooCommerce session actualizata: N/A
- Console/PHP erori observate: nu
- Pasi executati: Login cu user fara adrese si deschidere checkout.
- Rezultat asteptat: Apare formularul de adresa, nu lista de carduri.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":[],"authNotice":"","addressSummaryText":[],"addressCards":[]}
- Observatii: —

## TC-006 - User logat fara adresa - salveaza prima adresa

- Status: PASS
- User folosit: checkout.noaddress@test.local
- Screenshot: [tc-006-logged-noaddress-summary.png](./screenshots/tc-006-logged-noaddress-summary.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: verificat dupa refresh intern
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Completare formular user fara adresa si salvare.
- Rezultat asteptat: Lista cu cardul salvat apare imediat, fara reload complet.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":false,"guestSummaryVisible":false,"authListVisible":true,"selectedName":"Cristian Diaconescu","selectedAddressId":"addr_ad166d42-8a1a-4294-a73b-0f90565e8bfb","inlineErrors":[],"authNotice":"","addressSummaryText":["Strada Primara 10, Huedin, Cluj, 405400","0736628325","checkout.noaddress@test.local"],"addressCards":[{"id":"addr_ad166d42-8a1a-4294-a73b-0f90565e8bfb","selected":true,"name":"Cristian Diaconescu"}]}
- Observatii: Acesta este flow-ul reparat pentru consistenta cu guest.

## TC-007 - User logat cu o adresa - afiseaza summary direct

- Status: PASS
- User folosit: checkout.oneaddress@test.local
- Screenshot: [tc-007-logged-oneaddress-card.png](./screenshots/tc-007-logged-oneaddress-card.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Login cu user cu o adresa si deschidere checkout.
- Rezultat asteptat: Se vede direct cardul selectat, fara formular deschis.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":false,"guestSummaryVisible":false,"authListVisible":true,"selectedName":"Cristian Editat Diaconescu","selectedAddressId":"test-one-cluj","inlineErrors":[],"authNotice":"","addressSummaryText":["Aleea Editata 21, Huedin, Cluj, 405400","0736628325","checkout.oneaddress@test.local"],"addressCards":[{"id":"test-one-cluj","selected":true,"name":"Cristian Editat Diaconescu"}]}
- Observatii: —

## TC-008 - User logat cu o adresa - modifica si salveaza

- Status: PASS
- User folosit: checkout.oneaddress@test.local
- Screenshot: [tc-008-logged-oneaddress-edited.png](./screenshots/tc-008-logged-oneaddress-edited.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Editare card existent si salvare.
- Rezultat asteptat: Cardul actualizat reapare imediat, fara reload complet.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":false,"guestSummaryVisible":false,"authListVisible":true,"selectedName":"Cristian Editat Diaconescu","selectedAddressId":"test-one-cluj","inlineErrors":[],"authNotice":"","addressSummaryText":["Aleea Editata 21, Huedin, Cluj, 405400","0736628325","checkout.oneaddress@test.local"],"addressCards":[{"id":"test-one-cluj","selected":true,"name":"Cristian Editat Diaconescu"}]}
- Observatii: Nume initial: Cristian Editat Diaconescu

## TC-009 - User logat cu mai multe adrese - afiseaza lista de carduri

- Status: PASS
- User folosit: checkout.multiaddress@test.local
- Screenshot: [tc-009-logged-multi-list.png](./screenshots/tc-009-logged-multi-list.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Login cu user cu mai multe adrese.
- Rezultat asteptat: Se vede lista de carduri cu una selectata.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":false,"guestSummaryVisible":false,"authListVisible":true,"selectedName":"Edit Multi Diaconescu","selectedAddressId":"test-multi-cluj","inlineErrors":[],"authNotice":"","addressSummaryText":["Bd. Revizuit 77, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local","Victoriei 45, Sector 1, București, 010061","0740123456","checkout.multiaddress@test.local","Strada Noua 55, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local"],"addressCards":[{"id":"test-multi-cluj","selected":true,"name":"Edit Multi Diaconescu"},{"id":"test-multi-bucuresti","selected":false,"name":"Cristian Diaconescu"},{"id":"addr_b8f92984-2aaf-475c-bbf3-2be041b492fb","selected":false,"name":"Adresa Noua Diaconescu"}]}
- Observatii: —

## TC-010 - User logat cu mai multe adrese - selecteaza alta adresa

- Status: PASS
- User folosit: checkout.multiaddress@test.local
- Screenshot: [tc-010-logged-multi-selected.png](./screenshots/tc-010-logged-multi-selected.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Selectie alt card din lista de adrese.
- Rezultat asteptat: Selectia se schimba instant si persista.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":false,"guestSummaryVisible":false,"authListVisible":true,"selectedName":"Cristian Diaconescu","selectedAddressId":"test-multi-bucuresti","inlineErrors":[],"authNotice":"","addressSummaryText":["Bd. Revizuit 77, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local","Victoriei 45, Sector 1, București, 010061","0740123456","checkout.multiaddress@test.local","Strada Noua 55, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local"],"addressCards":[{"id":"test-multi-cluj","selected":false,"name":"Edit Multi Diaconescu"},{"id":"test-multi-bucuresti","selected":true,"name":"Cristian Diaconescu"},{"id":"addr_b8f92984-2aaf-475c-bbf3-2be041b492fb","selected":false,"name":"Adresa Noua Diaconescu"}]}
- Observatii: —

## TC-011 - User logat - adauga adresa noua

- Status: PASS
- User folosit: checkout.multiaddress@test.local
- Screenshot: [tc-011-logged-multi-add.png](./screenshots/tc-011-logged-multi-add.png)
- Reload complet: NU
- Update instant: NU
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Click pe Adauga adresa noua, completare formular, salvare.
- Rezultat asteptat: Noul card apare imediat si devine selectat.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"Cristian Diaconescu","selectedAddressId":"test-multi-bucuresti","inlineErrors":[],"authNotice":"","addressSummaryText":["Bd. Revizuit 77, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local","Victoriei 45, Sector 1, București, 010061","0740123456","checkout.multiaddress@test.local","Strada Noua 55, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local"],"addressCards":[{"id":"test-multi-cluj","selected":false,"name":"Edit Multi Diaconescu"},{"id":"test-multi-bucuresti","selected":true,"name":"Cristian Diaconescu"},{"id":"addr_b8f92984-2aaf-475c-bbf3-2be041b492fb","selected":false,"name":"Adresa Noua Diaconescu"}]}
- Observatii: —

## TC-012 - User logat - editeaza adresa existenta

- Status: PASS
- User folosit: checkout.multiaddress@test.local
- Screenshot: [tc-012-logged-multi-edit.png](./screenshots/tc-012-logged-multi-edit.png)
- Reload complet: NU
- Update instant: NU
- Persistenta dupa refresh: DA
- WooCommerce session actualizata: DA
- Console/PHP erori observate: nu
- Pasi executati: Editare card existent din lista multi-address si salvare.
- Rezultat asteptat: Cardul editat revine instant in lista, fara reload complet.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":true,"stepState":"complete","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"Adresa Noua Diaconescu","selectedAddressId":"addr_92a7ada9-777a-47f9-96fa-d066fe9b008d","inlineErrors":[],"authNotice":"","addressSummaryText":["Bd. Revizuit 77, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local","Victoriei 45, Sector 1, București, 010061","0740123456","checkout.multiaddress@test.local","Strada Noua 55, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local","Strada Noua 55, Huedin, Cluj, 405400","0736628325","checkout.multiaddress@test.local"],"addressCards":[{"id":"test-multi-cluj","selected":false,"name":"Edit Multi Diaconescu"},{"id":"test-multi-bucuresti","selected":false,"name":"Cristian Diaconescu"},{"id":"addr_b8f92984-2aaf-475c-bbf3-2be041b492fb","selected":false,"name":"Adresa Noua Diaconescu"},{"id":"addr_92a7ada9-777a-47f9-96fa-d066fe9b008d","selected":true,"name":"Adresa Noua Diaconescu"}]}
- Observatii: —

## TC-013 - Validari frontend

- Status: PASS
- User folosit: guest
- Screenshot: [tc-013-frontend-validation.png](./screenshots/tc-013-frontend-validation.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: N/A
- WooCommerce session actualizata: N/A
- Console/PHP erori observate: nu
- Pasi executati: Click pe Continua cu formularul gol.
- Rezultat asteptat: Erorile FE apar langa campuri obligatorii, fara submit.
- Rezultat obtinut: {"url":"http://localhost:8080/checkout/","loggedIn":false,"stepState":"active","formVisible":true,"guestSummaryVisible":false,"authListVisible":false,"selectedName":"","selectedAddressId":"","inlineErrors":["Completează prenumele.","Completează numele.","Introdu emailul.","Introdu telefonul.","Alege județul.","Completează adresa."],"authNotice":"","addressSummaryText":[],"addressCards":[]}
- Observatii: —

## TC-014 - Validari backend

- Status: PASS
- User folosit: checkout.oneaddress@test.local
- Screenshot: [tc-014-backend-validation.png](./screenshots/tc-014-backend-validation.png)
- Reload complet: NU
- Update instant: DA
- Persistenta dupa refresh: N/A
- WooCommerce session actualizata: DA
- Console/PHP erori observate: console:Failed to load resource: the server responded with a status of 400 (Bad Request) | console:Failed to load resource: the server responded with a status of 400 (Bad Request)
- Pasi executati: POST AJAX cu nonce invalid si POST AJAX cu address_id apartinand altui user.
- Rezultat asteptat: Serverul respinge ambele request-uri invalide.
- Rezultat obtinut: {"invalidNonceResult":{"status":400,"data":{"success":false,"data":{"message":"Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.","messages":["Sesiunea a expirat. Reîncarcă pagina și încearcă din nou."]}}},"ownershipResult":{"status":400,"data":{"success":false,"data":{"message":"Adresa selectată nu există sau nu îți aparține.","messages":["Adresa selectată nu există sau nu îți aparține."]}}}}
- Observatii: —

## Rezumat

- Total cazuri: 14
- PASS: 14
- FAIL: 0
- PARTIAL: 0

## Observatii finale

- Flow-ul userului logat a fost reverificat dupa eliminarea request-ului redundant de selectie dupa save.
- Capturile sunt salvate in `docs/checkout/testing/screenshots/`.
- Acest raport acopera doar Pasul 1 - Adresa de livrare.
- Scenariul nou pentru `User logat - o adresă în My Account, adaugă adresă nouă în Checkout` a fost adăugat în matricea oficială și trebuie validat în următorul ciclu QA.
