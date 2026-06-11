# Cart Page - QA Shipping

Document pentru verificarea manuală a rândului de transport din summary înainte de live.

Reguli de folosire:
- rulează scenariile în ordinea de mai jos;
- după fiecare test notează `PASS` sau `FAIL`;
- verifică și pe desktop, și pe mobil;
- dacă apare o eroare, oprește-te și repară înainte de a continua.

---

## 1. Shipping row există în DOM

**Status:** PASS / FAIL

### Setup
1. Deschide un coș cu un produs fizic.
2. Deschide pagina de cart.

### Rezultat așteptat
- rândul `Transport` există în summary;
- rândul este vizibil în DOM;
- nu apare `0 lei`;
- textul este aliniat cu restul summary-ului.

---

## 2. Sub pragul de transport gratuit

**Status:** PASS / FAIL

### Setup
1. Configurează în WooCommerce o metodă de `Free shipping` cu prag minim.
2. Creează un coș sub prag.
3. Deschide pagina de cart.

### Rezultat așteptat
- apare `Transport`;
- valoarea afișată este `Se calculează la checkout` sau echivalentul configurat;
- nu apare o sumă falsă;
- totalul rămâne sincronizat cu WooCommerce.

---

## 3. Peste pragul de transport gratuit

**Status:** PASS / FAIL

### Setup
1. Folosește aceeași configurare de la testul anterior.
2. Creează un coș peste pragul de transport gratuit.

### Rezultat așteptat
- apare `Transport`;
- valoarea afișată este `Gratuit`;
- nu apare `0 lei`;
- totalul rămâne sincronizat cu WooCommerce.

---

## 4. Flat rate configurat

**Status:** PASS / FAIL

### Setup
1. Configurează o metodă `Flat rate` în WooCommerce.
2. Adaugă produse în coș.
3. Deschide pagina de cart.

### Rezultat așteptat
- rândul `Transport` este vizibil;
- dacă WooCommerce a calculat metoda, apare suma reală;
- nu se afișează un placeholder fals după ce metoda este cunoscută.

---

## 5. Local pickup

**Status:** PASS / FAIL

### Setup
1. Configurează `Local pickup` în WooCommerce.
2. Adaugă produse în coș.
3. Deschide pagina de cart.

### Rezultat așteptat
- rândul `Transport` este vizibil;
- apare eticheta corespunzătoare metodei sau `Gratuit`, conform configurării WooCommerce;
- styling-ul rămâne identic cu restul summary-ului.

---

## 6. Fără adresă completată

**Status:** PASS / FAIL

### Setup
1. Adaugă un produs fizic în coș.
2. Nu completa adresa de livrare.
3. Deschide pagina de cart.

### Rezultat așteptat
- rândul `Transport` se vede;
- apare mesajul de tip `Se calculează la checkout`;
- nu apare o valoare inventată.

---

## 7. Desktop și mobile

**Status:** PASS / FAIL

### Viewport-uri de test
- `1440px`
- `1920px`
- `390px`
- `768px`

### Rezultat așteptat
- rândul `Transport` rămâne aliniat cu restul summary-ului;
- spațierea este consistentă;
- nu apare overflow;
- nu se rupe layout-ul pe mobil.
