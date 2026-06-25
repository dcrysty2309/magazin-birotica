# Checkout Rules

Acest document este standardul oficial pentru implementarea checkout-ului SupplyHub.

## 1. Ordinea pașilor

Ordinea este fixă și nu se schimbă:

1. Adresă de livrare
2. Tip de livrare
3. Facturare
4. Metodă de plată

Reguli:

- Numerele pașilor rămân vizibile în toate flow-urile.
- Titlul pasului rămâne vizibil și când pasul este completat.
- Nu se inversează ordinea pentru guest sau user logat.

## 2. Reguli pentru Pasul 1

Pasul 1 colectează sau selectează adresa folosită pentru comanda curentă.

Reguli:

- Guest-ul completează formularul manual.
- Userul logat poate selecta o adresă salvată din cont.
- Userul logat poate adăuga o adresă nouă doar pentru checkout-ul curent.
- După salvare, UI-ul trece în summary card view.
- Summary card-ul trebuie să reflecte exact datele care ajung în comandă.

## 3. Separarea dintre Checkout și My Account

Checkout-ul nu este zonă de administrare cont.

Reguli:

- Checkout-ul nu editează adrese salvate în My Account.
- Adresele din cont pot fi afișate și selectate în checkout.
- Modificările făcute din checkout se aplică doar comenzii curente.
- Dacă userul vrea să schimbe permanent adresele din cont, o face din My Account.

## 4. Reguli UX pentru salvare adresă

- Salvarea pentru Pasul 1 trebuie să fie rapidă și fără reload complet de pagină.
- Validările frontend existente trebuie păstrate.
- Validările backend rămân obligatorii pentru datele importante.
- Butonul principal păstrează layout-ul actual.
- Butonul secundar păstrează layout-ul actual.
- Se blochează doar zona relevantă în timpul requestului AJAX.

## 5. Reguli WooCommerce

- Adresa selectată sau completată trebuie să ajungă în sesiunea WooCommerce.
- Adresa trebuie să ajungă în comandă, livrare și facturare, conform flow-ului curent.
- Shipping-ul trebuie recalculat după schimbarea adresei.
- Nu se rescriu mecanismele native WooCommerce pentru order, shipping și payment.

## 6. Reguli UI

- Se păstrează designul SupplyHub existent.
- Summary card-ul de adresă se reutilizează.
- Nu se afișează simultan formularul și summary card-ul pentru aceeași adresă.
- Iconurile din summary card rămân SVG outline și consistente vizual.

