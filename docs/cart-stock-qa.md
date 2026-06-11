# Cart Page - QA Suplimentar pentru Stoc și Indisponibilitate

Acest document completează [cart-live-test-cases.md](./cart-live-test-cases.md) cu scenariile noi pentru:
- limită de stoc;
- tooltip informativ;
- produse indisponibile;
- produse șterse;
- backorders.

Folosește-l împreună cu lista principală de QA înainte de live.

---

## 1. Limită de stoc atinsă

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stoc `2`.
2. Adaugă produsul în coș.
3. Setează cantitatea la `2`.

### Rezultat așteptat
- butonul `+` devine dezactivat;
- cantitatea rămâne editabilă în jos;
- apare indicatorul discret `i` lângă qty;
- tooltip-ul apare automat imediat când cantitatea ajunge la maxim;
- la hover sau focus tooltip-ul apare din nou;
- tooltip-ul nu schimbă layout-ul;
- nu apare banner de eroare;
- checkout-ul rămâne disponibil.

### Setup suplimentar
1. Creează un produs simplu cu stoc `1`.
2. Adaugă produsul în coș.
3. Reîncarcă pagina de cart.

### Rezultat așteptat
- inputul de cantitate afișează `1`, nu este gol;
- plus-ul este dezactivat;
- minus-ul rămâne activ doar dacă poate scădea cantitatea;
- nu apare `[-] [ ] [+]` niciodată.

---

## 2. Tooltip stock limit pe desktop și mobil

**Status:** PASS / FAIL

### Setup
1. Folosește produsul din scenariul anterior.
2. Deschide pagina de cart pe desktop.
3. Testează și pe mobil sau în responsive mode.

### Pași
1. Mută cursorul peste indicatorul de info.
2. Dă click pe indicator.
3. Treci cu tastatura pe control și pe indicator.

### Rezultat așteptat
- tooltip-ul apare deasupra controlului de cantitate;
- textul este clar și în română;
- tooltip-ul se închide automat;
- nu împinge niciun element în pagină;
- pe mobil apare la tap și dispare fără să blocheze interfața.

---

## 3. Cantitate introdusă manual peste stoc

**Status:** PASS / FAIL

### Setup
1. Folosește un produs cu stoc `2`.
2. Introdu manual cantitatea `5`.
3. Apasă `Actualizează coșul`.

### Rezultat așteptat
- WooCommerce ajustează cantitatea la valoarea permisă;
- apare mesajul nativ WooCommerce, dacă este cazul;
- totalul se recalculează corect;
- nu apar erori PHP;
- layout-ul rămâne stabil.

---

## 3.1 Stoc redus sub cantitatea din coș

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stoc `10`.
2. Adaugă `3` bucăți în coș.
3. Lasă pagina de cart deschisă.
4. În admin schimbă stocul la `2`.
5. Salvează.
6. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- inputul de cantitate nu este niciodată gol;
- valoarea rămâne vizibilă și validă în input;
- se afișează valoarea curentă din coș;
- apare warning-ul full-width în card;
- butonul `+` este dezactivat;
- butonul `-` rămâne activ;
- totalurile se recalculează corect;
- checkout-ul este dezactivat până când utilizatorul reduce cantitatea sau elimină produsul;
- nu apar erori JS sau PHP;
- după apăsarea `Actualizează coșul`, warning-ul dispare doar când cantitatea devine validă.

---

## 3.2 Stoc redus din admin după ce produsul a fost adăugat

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stoc `5`.
2. Adaugă `4` bucăți în coș.
3. Lasă pagina de cart deschisă.
4. În admin schimbă stocul la `2`.
5. Salvează.
6. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- produsul rămâne vizibil în coș;
- inputul rămâne cu valoarea curentă din coș;
- apare warning full-width în card;
- mesajul spune clar că cantitatea din coș depășește stocul disponibil;
- apare și mesajul cu numărul exact de bucăți rămase;
- butonul `+` este dezactivat;
- butonul `-` rămâne activ;
- checkout-ul este dezactivat până când utilizatorul reduce cantitatea;
- produsul poate fi eliminat manual;
- după reducerea cantității la `2` și apăsarea `Actualizează coșul`, warning-ul dispare;
- totalurile se recalculează corect;
- nu apar erori PHP.

---

## 3.3 Stoc redus drastic la o singură bucată

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stoc `10`.
2. Adaugă `3` bucăți în coș.
3. Lasă pagina de cart deschisă.
4. În admin schimbă stocul la `1`.
5. Salvează.
6. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- inputul de cantitate nu devine gol;
- valoarea rămâne vizibilă în input;
- apare warning-ul full-width în card;
- mesajul arată că doar `1` bucată mai este disponibilă;
- butonul `+` este dezactivat;
- butonul `-` rămâne activ;
- checkout-ul este dezactivat până când utilizatorul reduce cantitatea sau elimină produsul;
- totalurile se recalculează corect;
- nu apar erori JS sau PHP.

---

## 4. Produs devine Out of Stock după ce a fost adăugat

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stock `5`.
2. Adaugă-l în coș.
3. Lasă pagina de cart deschisă.
4. În admin setează produsul `Out of stock`.
5. Salvează.
6. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- produsul rămâne vizibil;
- apare bannerul `Produsul nu mai este disponibil în stoc. Elimină-l din coș pentru a continua comanda.`;
- controalele de cantitate sunt dezactivate;
- butonul `Continuă către finalizare` este dezactivat;
- apare mesajul `Unele produse din coș nu mai sunt disponibile. Elimină-le pentru a continua.`;
- utilizatorul poate șterge produsul manual.

---

## 5. Variație indisponibilă

**Status:** PASS / FAIL

### Setup
1. Creează un produs variabil.
2. Adaugă în coș o variație disponibilă.
3. În admin dezactivează variația sau seteaz-o `Out of stock`.
4. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- variația rămâne afișată;
- apare bannerul `Varianta selectată nu mai este disponibilă. Elimin-o din coș pentru a continua comanda.`;
- cantitatea este blocată;
- checkout-ul este dezactivat;
- utilizatorul poate elimina produsul.

---

## 6. Produs șters din admin

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu.
2. Adaugă-l în coș.
3. Șterge produsul definitiv din admin.
4. Revino în frontend și dă refresh la cart.

### Rezultat așteptat
- pagina nu se rupe;
- nu apar erori PHP;
- rândul produsului rămâne vizibil ca indisponibil;
- apare mesajul `Acest produs nu mai există în catalog. Elimină-l din coș pentru a continua.`;
- checkout-ul este blocat până când utilizatorul elimină produsul.

---

## 7. Backorder activ

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu sau variabil cu backorder activ.
2. Adaugă-l în coș.

### Rezultat așteptat
- butonul `+` nu se blochează din cauza stocului;
- nu apare tooltip-ul de limită de stoc;
- apare badge-ul `Disponibil la comandă`;
- checkout-ul funcționează normal.

---

## 8. Produse simple cu stoc diferit

**Status:** PASS / FAIL

### Setup
1. Creează trei produse simple separate.
2. Setează stocurile la `1`, `2` și `10`.
3. Adaugă-le pe rând în coș.

### Rezultat așteptat
- la stoc `1`, plus se dezactivează imediat la cantitatea `1`;
- la stoc `2`, tooltip-ul apare automat când ajungi la `2`;
- la stoc `10`, tooltip-ul apare automat când ajungi la `10`;
- tooltip-ul apare și la hover;
- tooltip-ul apare și la focus;
- cantitatea poate fi redusă în continuare.

---

## 9. Verificare finală de consistență

**Status:** PASS / FAIL

### Verificări
1. Încearcă să actualizezi coșul după orice modificare.
2. Verifică mini cart-ul din header.
3. Verifică badge-ul de produse din header.
4. Testează refresh după update.
5. Verifică pe desktop și pe mobil.

### Rezultat așteptat
- nu rămân valori vechi în UI;
- totalurile sunt corecte;
- nu există erori în console;
- nu există erori vizibile în frontend.
