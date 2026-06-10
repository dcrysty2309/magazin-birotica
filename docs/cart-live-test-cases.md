# Cart Page - QA Manual înainte de live

Document pentru verificarea manuală a flow-ului de coș înainte de lansare.

Reguli de folosire:
- rulează scenariile în ordinea de mai jos;
- după fiecare test notează `PASS` sau `FAIL`;
- dacă apare o eroare, oprește-te și repară înainte de a continua;
- verifică și pe desktop, și pe mobil când este cerut.

## 0. Setup rapid

Înainte de a începe:
1. Deschide WordPress admin.
2. Verifică faptul că WooCommerce este activ.
3. Asigură-te că există produse simple și variabile de test.
4. Creează sau verifică existența unui cupon valid.
5. Verifică setările de shipping și taxe ale magazinului.
6. Deschide site-ul într-o fereastră incognito / guest.

---

## 1. Coș gol

**Status:** PASS / FAIL

### Setup
1. Go to WooCommerce admin.
2. Golește coșul din frontend sau șterge manual toate produsele din coș.
3. Reîncarcă pagina de cart.

### Rezultat așteptat
- apare mesajul `Coșul tău este gol.`;
- apare CTA `Continuă cumpărăturile`;
- nu există summary gol;
- nu apar erori PHP sau mesaje vizibile în engleză.

---

## 2. Actualizare cantitate

**Status:** PASS / FAIL

### Setup
1. Adaugă un produs simplu în coș.
2. Deschide pagina de cart.

### Pași
1. Apasă `+`.
2. Apasă `-`.
3. Introdu manual o cantitate diferită.
4. Apasă `Actualizează coșul`.

### Rezultat așteptat
- butonul `Actualizează coșul` devine activ doar după modificare;
- după update revine dezactivat;
- totalurile se recalculează corect;
- mini cart-ul și badge-ul din header se actualizează corect;
- fără refresh inutil dacă flow-ul este AJAX;
- după refresh, cantitatea rămâne salvată.

---

## 3. Limită de stoc

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stock quantity `2`.
2. Adaugă produsul în coș.

### Pași
1. Încearcă să setezi cantitatea la `5`.
2. Apasă `Actualizează coșul`.

### Rezultat așteptat
- cantitatea nu depășește stocul permis;
- apare mesaj WooCommerce clar, în română;
- nu apar erori PHP;
- totalul rămâne corect.

---

## 4. Produs devine indisponibil după adăugare

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu cu stock quantity `5`.
2. Adaugă produsul în coș.
3. Lasă pagina de cart deschisă.
4. Mergi în admin și setează produsul `Out of stock`.
5. Salvează.
6. Revino pe frontend și dă refresh la cart.

### Rezultat așteptat
- produsul rămâne vizibil în coș;
- apare bannerul `Acest produs nu mai este disponibil.`;
- controalele de cantitate sunt dezactivate;
- butonul `Continuă către finalizare` este dezactivat;
- apare mesajul `Elimină produsele indisponibile pentru a continua.`;
- produsul poate fi șters manual.

---

## 5. Variație indisponibilă

**Status:** PASS / FAIL

### Setup
1. Creează un produs variabil.
2. Creează o variație activă și una inactivă.
3. Adaugă în coș variația disponibilă.
4. În admin, dezactivează variația sau seteaz-o `Out of stock`.
5. Revino pe frontend și reîncarcă pagina de cart.

### Rezultat așteptat
- variația rămâne afișată în coș;
- apare bannerul de indisponibil;
- cantitatea nu poate fi modificată;
- checkout-ul este blocat;
- utilizatorul poate elimina produsul.

---

## 6. Produs șters din admin

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu.
2. Adaugă-l în coș.
3. Șterge produsul definitiv din admin.
4. Revino în frontend și reîncarcă pagina de cart.

### Rezultat așteptat
- pagina nu se rupe;
- nu apar erori PHP;
- produsul este tratat elegant ca indisponibil;
- utilizatorul nu este blocat cu un layout stricat;
- checkout-ul nu este permis dacă există produse indisponibile.

---

## 7. Cupon valid

**Status:** PASS / FAIL

### Setup
1. Creează un cupon valid în WooCommerce.
2. Adaugă produse în coș astfel încât cuponul să se poată aplica.

### Pași
1. Introdu cuponul.
2. Apasă `Aplică`.

### Rezultat așteptat
- cuponul se aplică;
- apare reducerea în summary doar dacă valoarea este mai mare decât `0`;
- reducerea este afișată cu verde;
- totalul se actualizează corect;
- mini cart-ul se actualizează corect.

---

## 8. Cupon invalid sau expirat

**Status:** PASS / FAIL

### Setup
1. Adaugă produse în coș.
2. Folosește un cod invalid sau expirat.

### Pași
1. Introdu codul.
2. Apasă `Aplică`.

### Rezultat așteptat
- apare mesaj clar în română;
- nu apar texte în engleză;
- layout-ul nu se rupe;
- totalul rămâne neschimbat;
- mini cart-ul rămâne sincronizat.

---

## 9. Eliminare cupon

**Status:** PASS / FAIL

### Setup
1. Aplică un cupon valid.

### Pași
1. Apasă `×` de pe chip-ul cuponului.

### Rezultat așteptat
- cuponul dispare;
- reducerea dispare din summary;
- totalul revine la valoarea corectă;
- mini cart-ul se actualizează;
- nu rămân valori vechi în UI.

---

## 10. Transport gratuit

**Status:** PASS / FAIL

### Setup
1. Configurează o metodă de transport gratuit în WooCommerce.
2. Setează pragul de transport gratuit.
3. Creează un coș sub prag și unul peste prag.

### Pași
1. Testează coșul sub prag.
2. Testează coșul peste prag.
3. Testează și o adresă din Cluj-Napoca, și una din alt oraș.
4. Testează și fără adresă completată.

### Rezultat așteptat
- sub prag: se afișează costul normal de transport sau mesajul de calcul, după caz;
- peste prag: se afișează `Transport gratuit`;
- dacă transportul nu este calculat încă, apare textul `Transportul se calculează la finalizare.`;
- totalul rămâne sincronizat cu WooCommerce;
- nu apar valori zero inutile.

---

## 11. TVA afișat separat

**Status:** PASS / FAIL

### Setup
1. Activează taxele în WooCommerce.
2. Configurează magazinul să afișeze taxele separat.
3. Adaugă un produs taxabil în coș.

### Rezultat așteptat
- apare rândul `TVA` doar dacă WooCommerce afișează taxele separat;
- nu apare rând gol;
- totalul rămâne corect;
- nu apar texte în engleză.

---

## 12. Produs simplu + produs variabil + backorder

**Status:** PASS / FAIL

### Setup
1. Creează un produs simplu.
2. Creează un produs variabil.
3. Creează un produs cu backorder activ.
4. Adaugă toate cele 3 produse în coș.

### Rezultat așteptat
- fiecare produs este afișat corect;
- backorder-ul nu blochează checkout-ul;
- layout-ul rămâne stabil;
- mini cart-ul și badge-ul din header sunt corecte.

---

## 13. Cantitate maximă și minimă

**Status:** PASS / FAIL

### Setup
1. Creează un produs cu stock limitat.
2. Setează o cantitate minimă sau o regulă care impune un pas diferit de `1`, dacă există.

### Pași
1. Încearcă să cobori sub minim.
2. Încearcă să urci peste maxim.

### Rezultat așteptat
- nu se poate coborî sub minim;
- nu se poate depăși maximul;
- butoanele `+` și `-` se dezactivează când trebuie;
- cantitatea afișată este corectă.

---

## 14. Refresh după update

**Status:** PASS / FAIL

### Setup
1. Adaugă unul sau mai multe produse în coș.

### Pași
1. Modifică o cantitate.
2. Apasă `Actualizează coșul`.
3. Fă refresh la pagină.

### Rezultat așteptat
- coșul păstrează valorile;
- session-ul WooCommerce este stabil;
- totalurile nu devin stale;
- nu apar erori la reload.

---

## 15. Mini cart și header count

**Status:** PASS / FAIL

### Setup
1. Adaugă produse în coș.

### Pași
1. Schimbă cantitatea.
2. Elimină un produs.
3. Aplică un cupon.
4. Elimină cuponul.
5. Golește coșul.

### Rezultat așteptat
- badge-ul din header este corect după fiecare acțiune;
- mini cart-ul afișează cantitățile corecte;
- subtotalul din mini cart este corect;
- nu rămân valori vechi după acțiuni;
- la coș gol, mini cart-ul reflectă starea goală.

---

## 16. User guest

**Status:** PASS / FAIL

### Setup
1. Deschide site-ul într-o fereastră incognito.

### Rezultat așteptat
- tot flow-ul de cart funcționează fără cont;
- cupoanele și update-urile merg corect;
- totalurile sunt corecte;
- nu apar erori la sesiune.

---

## 17. User autentificat

**Status:** PASS / FAIL

### Setup
1. Autentifică-te într-un cont de test.

### Rezultat așteptat
- coșul se comportă identic cu guest;
- session-ul și totalurile se păstrează;
- nu apar diferențe de UI neintenționate.

---

## 18. Mobile și responsive

**Status:** PASS / FAIL

### Viewport-uri de test
- `390px`
- `768px`
- `1024px`
- `1280px`
- `1440px`
- `1920px`

### Rezultat așteptat
- pe mobil summary-ul vine după produse;
- butoanele ocupă lățimea disponibilă când este necesar;
- quantity controls nu ies din card;
- imaginile nu se deformează;
- nu există overflow orizontal;
- layout-ul rămâne curat la toate lățimile.

---

## 19. Console și erori tehnice

**Status:** PASS / FAIL

### Verificări
1. Deschide DevTools.
2. Repetă scenariile principale:
   - adăugare în coș;
   - update cantitate;
   - aplicare cupon;
   - eliminare produs;
   - eliminare cupon;
   - coș gol;
   - produs indisponibil.

### Rezultat așteptat
- `0` erori JavaScript;
- `0` request-uri eșuate inutile;
- `0` warnings PHP;
- `0` notices vizibile în frontend;
- `0` texte în engleză în flow-ul cart.

---

## 20. Check final înainte de live

Înainte de publicare, verifică încă o dată:
- `Subtotal` există și este corect;
- `Reducere` apare doar dacă există reducere reală;
- `Transport` apare doar când are sens;
- `TVA` apare doar când WooCommerce îl afișează separat;
- `Total estimat` este sincronizat cu WooCommerce;
- `Continuă către finalizare` nu este activ dacă există produse indisponibile;
- cart page și mini cart sunt sincronizate;
- nu există erori în console;
- nu există erori PHP.

