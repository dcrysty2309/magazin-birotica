# Cart Page - QA Recomandări produse

Document pentru verificarea manuală a secțiunii `S-ar putea să-ți placă și` înainte de live.

Reguli de folosire:
- rulează scenariile în ordinea de mai jos;
- după fiecare test notează `PASS` sau `FAIL`;
- verifică și pe desktop, și pe mobil;
- dacă apare o eroare, oprește-te și repară înainte de a continua.

---

## 1. Cross-sells pentru un singur produs

**Status:** PASS / FAIL

### Setup
1. Deschide un produs simplu în admin.
2. Adaugă 2-3 produse în `Cross-sells`.
3. Adaugă produsul în coș.
4. Deschide pagina de cart.

### Rezultat așteptat
- apare secțiunea `S-ar putea să-ți placă și`;
- produsele din cross-sells apar în slider;
- sliderul folosește aceleași carduri ca pe homepage;
- nu apar produse care sunt deja în coș.

---

## 2. Cross-sells pentru mai multe produse

**Status:** PASS / FAIL

### Setup
1. Adaugă în coș mai multe produse.
2. Configurează cross-sells diferite pentru fiecare produs.
3. Deschide pagina de cart.

### Rezultat așteptat
- recomandările sunt combinate din toate produsele din coș;
- produsele duplicate apar o singură dată;
- secțiunea nu este goală.

---

## 3. Duplicates remove

**Status:** PASS / FAIL

### Setup
1. Configurează același produs ca cross-sell pentru mai multe produse din coș.
2. Deschide pagina de cart.

### Rezultat așteptat
- produsul apare o singură dată în slider;
- nu există duplicate vizibile.

---

## 4. Produs recomandat deja în coș

**Status:** PASS / FAIL

### Setup
1. Adaugă în coș un produs care este și cross-sell pentru alt produs din coș.
2. Deschide pagina de cart.

### Rezultat așteptat
- produsul deja aflat în coș nu este recomandat din nou;
- sliderul rămâne curat și relevant.

---

## 5. Recomandare out of stock

**Status:** PASS / FAIL

### Setup
1. Configurează un cross-sell care este `Out of stock`.
2. Adaugă produsul sursă în coș.
3. Deschide pagina de cart.

### Rezultat așteptat
- produsul out of stock nu apare în slider;
- rămân doar produse cumpărabile.

---

## 6. Fără cross-sells

**Status:** PASS / FAIL

### Setup
1. Folosește produse fără cross-sells configurate.
2. Adaugă-le în coș.
3. Deschide pagina de cart.

### Rezultat așteptat
- secțiunea se completează cu produse din aceleași categorii;
- sliderul nu este gol.

---

## 7. Fără potriviri de categorie

**Status:** PASS / FAIL

### Setup
1. Folosește produse care nu au suficientă acoperire din categorii comune.
2. Deschide pagina de cart.

### Rezultat așteptat
- după cross-sells și categorii, sliderul se completează cu best sellers;
- secțiunea rămâne populată.

---

## 8. Maximum 8 produse

**Status:** PASS / FAIL

### Setup
1. Configurează multe cross-sells și produse din categorii relevante.
2. Deschide pagina de cart.

### Rezultat așteptat
- sunt afișate maximum 8 produse;
- nu apar mai multe produse decât limita setată.

---

## 9. Aspect identic cu homepage-ul

**Status:** PASS / FAIL

### Verificări
1. Compară cardurile cu sliderul de pe homepage.
2. Compară săgețile, spațierea și hover-ul.
3. Verifică comportamentul pe desktop și mobile.

### Rezultat așteptat
- cardurile arată identic cu homepage-ul;
- săgețile sunt identice;
- spațierea este identică;
- comportamentul responsive este identic;
- nu apare un carousel nou sau un card nou.

