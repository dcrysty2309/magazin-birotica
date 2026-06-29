# Checkout Payment and Shipping Rules

Acesta este standardul oficial pentru Phase 1 - transport real și plată ramburs.

## 1. Surse de adevăr

- WooCommerce rămâne sursa de adevăr pentru shipping, totals, taxes și payment gateways.
- UI-ul checkout doar afișează și stilizează datele venite din WooCommerce.
- Nu hardcodăm costuri, metode de shipping sau gateway-uri.
- Nu inventăm ETA, tarife sau etichete fallback.

## 2. Shipping

- Shipping-ul trebuie configurat în WooCommerce Admin prin zone și metode reale.
- Pentru România trebuie să existe cel puțin o zonă activă cu o metodă disponibilă pentru test.
- Configurația de bază pentru Phase 1 este: `Flat rate` pentru livrare standard și `Free shipping` la pragul de 150 lei, ambele gestionate din WooCommerce Admin.
- Dacă WooCommerce nu returnează metode de transport pentru adresa curentă, checkout-ul afișează un mesaj clar de business.
- Dacă adresa este incompletă, UI-ul explică faptul că trebuie completată adresa pentru a calcula transportul.
- Dacă transportul este gratuit și este configurat în WooCommerce, UI-ul afișează metoda reală și eticheta `Transport gratuit`, nu un cost inventat de tip `0.00 lei`.

## 3. Plată ramburs

- COD / Ramburs apare doar dacă este activ în WooCommerce.
- Dacă metoda nu este activă în admin, nu se afișează în checkout.
- Dacă nu există nicio metodă de plată disponibilă, UI-ul afișează un mesaj clar de business, nu o eroare tehnică.
- Checkout-ul nu trebuie să permită plasarea comenzii fără metodă de plată selectată.

## 4. Card payment

- Card payment rămâne out of scope pentru Phase 1.
- Nu se implementează gateway custom pentru card în această fază.
- Card payment va fi tratat separat, după ce shipping real și ramburs sunt PASS.

## 5. Verificări înainte de producție

- Shipping zone România activă.
- Cel puțin o metodă de shipping configurată și testată.
- COD activ și vizibil în checkout.
- Comandă test plasată cap-coadă cu ramburs.
- Metoda de shipping, costul, taxa și payment method apar corect în WooCommerce Admin.
- Checkout Test Index actualizat și rulat.

## 6. Checklist rapid WooCommerce Admin

Înainte de QA pe staging sau producție:

- Verifică `WooCommerce > Settings > Shipping` și asigură-te că există cel puțin o zonă activă pentru România.
- Confirmă că în zona respectivă există o metodă reală de shipping, de preferat `Flat rate`.
- Dacă folosești `Free shipping`, confirmă pragul de 150 lei și condiția setată din admin.
- Verifică `WooCommerce > Settings > Payments` și activează `Cash on delivery` pentru testul Phase 1.
- Dacă nu există gateway-uri active, checkout-ul trebuie să afișeze mesaj clar de business, nu costuri sau metode inventate.
- După modificări în admin, testează din nou checkout-ul cu o adresă completă și plasează o comandă ramburs.
- Confirmă în `WooCommerce > Orders` că adresa, shipping-ul, payment method și totalul au fost salvate corect.
