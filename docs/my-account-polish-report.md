# My Account Polish Report

Date: 2026-06-19

## 1. Probleme găsite

- Sidebar-ul My Account avea lățime inconsistentă și putea să se „strângă” la dimensiuni mici.
- Mai multe wrapper-e din zona My Account aveau `border-radius` mare sau inconsistent.
- Headerele de pagină, cardurile și tabelele nu respectau aceeași scară tipografică.
- Orders și View Order păstrau layout de tabel desktop prea mult timp și produceau overflow pe tabletă.
- Empty states și CTA-urile aferente nu aveau aceeași structură vizuală pe toate paginile.
- Copy-ul și etichetele WooCommerce erau amestecate între engleză și română.
- Testele existente pentru My Account depindeau de flow-ul de auth modal, care nu era stabil pentru e2e.

## 2. Probleme reparate

- Am refăcut shell-ul My Account cu sidebar și conținut aliniate pe același grid.
- Am normalizat spațierea pentru dashboard, orders, view order, addresses, edit account și favorite.
- Am eliminat colțurile rotunjite din carduri, panel-uri, formulare și tabele din My Account.
- Am adus titlurile paginilor și subtitlurile la o scară tipografică consistentă.
- Am refăcut afișarea orders și view-order pe carduri la viewport-uri mai mici, fără scroll orizontal.
- Am tratat empty states și linkurile de acțiune cu aceeași componentizare vizuală.
- Empty state-ul pentru `Adresa mea` apare acum când userul nu are o adresă standard completă; nu mai afișăm un card gol cu date parțiale sau fallback-uri.
- Am adăugat seed-uri locale pentru adrese și favorite, ca să pot valida state-uri reale, nu doar empty state.
- Am mutat testele My Account pe login WordPress stabil, fără să depind de auth modal.

## 3. Pagini verificate

- Dashboard
- Comenzile mele
- View Order / Detalii comandă
- Favorite
- Adrese
- Detalii cont
- Logout state
- Empty states pentru cont, comenzi, favorite și adrese

## 4. Responsive Test Results

- `390px`: passed, fără overflow orizontal.
- `768px`: passed, orders și shell fără overflow orizontal.
- `1024px`: passed, orders și shell fără overflow orizontal.
- `1440px`: passed.
- `1920px`: passed.

## 5. E2E Test Results

Suite rulată: `tests/my-account.spec.js`

- 8 teste trecute.
- Scenarii validate:
  - dashboard pentru user fără comenzi
  - dashboard pentru user cu istoric de comenzi
  - orders pentru 1, 5 și 20 comenzi
  - favorite, addresses și edit account states
  - favorite și addresses cu date seed-uite
  - view order cu shipping și TVA
  - layout responsive la breakpoints cerute
  - logout state

## 6. Diferențe rămase față de mockup-uri

- Există încă mici variații de pixel în proporțiile avatarului, iconurilor și badge-urilor față de mockup.
- Unele spațieri fine între elementele din dashboard și Orders pot diferi ușor de referințele vizuale.
- Flow-ul global de autentificare prin modal rămâne separat de My Account shell și nu a fost refactorizat aici.

## 7. Fișiere modificate

- `/wp-content/themes/papetarie-storefront/functions.php`
- `/wp-content/themes/papetarie-storefront/style.css`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/dashboard.php`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/form-edit-account.php`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/my-address.php`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/navigation.php`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/orders.php`
- `/wp-content/themes/papetarie-storefront/woocommerce/myaccount/view-order.php`
- `/tests/my-account.spec.js`
- `/docs/my-account-polish-report.md`
