# Cart Page - QA Prag Minim Comandă

Document pentru verificarea manuală a pragului minim de comandă înainte de live.

## Unde se validează

- UI-ul și mesajul de warning vin din `papetarie_storefront_cart_warning_state()`.
- pragul minim se calculează doar din subtotalul produselor, înainte de reduceri și fără transport.
- blocarea server-side pentru checkout este aplicată prin `woocommerce_check_cart_items`.
- accesul direct la `/checkout/` cu coș sub prag este redirecționat înapoi la cart.
- valoarea configurabilă este `papetarie_minimum_order_amount` și trebuie să rămână la `50`.

Reguli de folosire:
- rulează scenariile în ordinea de mai jos;
- după fiecare test notează `PASS` sau `FAIL`;
- verifică și mesajele, și starea butonului de checkout;
- dacă apare o eroare, oprește-te și repară înainte de a continua.

---

## 1. Coș sub pragul minim

**Status:** PASS / FAIL

### Setup
1. Mergi în WooCommerce > Settings > General.
2. Verifică `Minimum order amount = 50`.
3. Creează un coș cu totalul produselor de `40 lei`.
4. Deschide pagina de cart.

### Rezultat așteptat
- apare alerta `Valoarea minimă a comenzii este 50 lei.`;
- apare linia cu suma rămasă de adăugat, calculată din subtotalul produselor înainte de reduceri;
- alerta este deasupra tabelului, nu în summary;
- `Continuă către finalizare` este dezactivat;
- `Continuă cumpărăturile` rămâne disponibil;
- summary-ul rămâne curat, fără mesaj duplicat.

---

## 2. Coș la 49.99 lei

**Status:** PASS / FAIL

### Setup
1. Creează un coș cu totalul produselor de `49.99 lei`.
2. Reîncarcă pagina de cart.

### Rezultat așteptat
- alerta de prag minim este vizibilă;
- checkout-ul este blocat;
- mesajul explică suma rămasă de adăugat pe baza subtotalului produselor, nu pe baza totalului după reducere;
- nu apare `Reducere 0 lei`;
- nu apar mesaje duplicate.

---

## 3. Coș la 50 lei exact

**Status:** PASS / FAIL

### Setup
1. Creează un coș cu totalul produselor de `50 lei`.
2. Reîncarcă pagina de cart.

### Rezultat așteptat
- alerta de prag minim nu mai apare;
- `Continuă către finalizare` este activ;
- nu există mesaje de warning în summary;
- pagina rămâne curată.

---

## 4. Coș la 80 lei

**Status:** PASS / FAIL

### Setup
1. Creează un coș cu totalul produselor de `80 lei`.
2. Reîncarcă pagina de cart.

### Rezultat așteptat
- checkout-ul este activ;
- nu apare alerta de prag minim;
- nu apar texte suplimentare inutile;
- butoanele și totalurile rămân sincronizate.

---

## 5. Coș la 80 lei cu cupon -40 lei

**Status:** PASS / FAIL

### Setup
1. Creează un coș cu totalul produselor de `80 lei`.
2. Aplică un cupon care reduce totalul cu `40 lei`.
3. Reîncarcă pagina de cart sau apasă `Aplică`.

### Rezultat așteptat
- totalul după reducere poate fi sub prag, dar pragul minim se evaluează tot pe subtotalul produselor înainte de reducere;
- apare alerta de prag minim;
- checkout-ul este blocat;
- alerta rămâne în zona produselor;
- summary-ul nu afișează warning duplicat.

---

## 6. Modificări nesalvate și prag minim

**Status:** PASS / FAIL

### Setup
1. Creează un coș sub pragul minim.
2. Mărește cantitatea fără să apeși `Actualizează coșul`.

### Rezultat așteptat
- apare alerta `Actualizează coșul pentru a continua.`;
- mesajul de prag minim nu se afișează simultan;
- checkout-ul rămâne dezactivat;
- după actualizare, apare doar starea corectă pentru totalul salvat.

---

## 7. Acces direct la checkout

**Status:** PASS / FAIL

### Setup
1. Creează un coș sub pragul minim.
2. Mergi direct la `/checkout`.

### Rezultat așteptat
- utilizatorul nu poate continua comanda;
- primește mesajul WooCommerce / redirectul corespunzător;
- nu poate ocoli pragul minim;
- după revenirea în cart, starea rămâne corectă.

---

## 8. Refresh după update

**Status:** PASS / FAIL

### Setup
1. Modifică un coș aflat sub prag.
2. Apasă `Actualizează coșul`.
3. Fă refresh la pagină.

### Rezultat așteptat
- starea se păstrează corect;
- alerta minimă reapare dacă totalul rămâne sub prag;
- checkout-ul rămâne blocat dacă este necesar;
- nu apar mesaje duplicate.
