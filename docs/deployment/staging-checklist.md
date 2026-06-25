# Staging Checklist

Acest checklist se execută după primul deploy pe mediul online de staging.

Scop:

- validare funcțională
- validare UX
- validare WooCommerce
- validare email
- pregătire pentru QA și regresii

## Workflow oficial de QA

- implementarea se face local;
- testarea rapidă se face local;
- validarea finală se face pe staging;
- staging este mediul oficial de QA;
- bug-urile observate pe staging se notează în pagina de testare Checkout, în câmpul Comentarii din Preview;
- comentariile devin lista oficială de bug-uri pentru cazul respectiv;
- după fiecare rundă de fix, se retestează scenariile afectate;
- dacă datele de test sunt modificate, se rulează resetul de date înainte de următorul ciclu.

## Reguli de lansare

- nu se lansează un nou deploy pe staging până când scenariile relevante nu au trecut pe mediul curent;
- nu se tratează staging ca mediu de producție;
- staging trebuie să folosească aceleași conturi de test ca local.

## 1. Homepage

- homepage se deschide fără erori PHP
- logo-ul se încarcă
- header-ul complet se afișează corect
- meniurile funcționează
- search funcționează
- toate imaginile importante se încarcă
- nu există layout shift evident
- nu există erori în console

## 2. Categorii

- paginile de categorie se încarcă
- filtrele funcționează
- sortarea funcționează
- paginarea funcționează
- cardurile de produs afișează imagine, nume, preț și butoane corect

## 3. Produse

- pagina de produs se deschide corect
- galeria și imaginile se încarcă
- prețul este corect
- add to cart funcționează
- produsul apare corect în coș și mini-cart

## 4. Login

- login standard funcționează
- mesajele de eroare sunt afișate corect
- resetarea parolei trimite email
- după login userul ajunge în zona corectă

## 5. Register

- înregistrarea funcționează
- validările frontend funcționează
- validările backend funcționează
- emailurile aferente sunt trimise corect

## 6. My Account

- dashboard se încarcă
- navigația custom funcționează
- comenzile se încarcă
- datele contului se pot edita
- logout funcționează

## 7. Coș

- add to cart funcționează de pe listing și pagina de produs
- update cantitate funcționează
- remove produs funcționează
- totalurile se recalculează corect
- empty cart state arată corect

## 8. Checkout

- pagina se încarcă fără erori
- layout-ul simplificat pentru checkout este activ
- sidebar-ul de sumar se actualizează corect
- nu există elemente duplicate
- nu există erori JS sau PHP

## 9. Toate flow-urile de adresă

Teste obligatorii:

- guest fără cont
- guest după refresh
- guest editare adresă
- user logat fără adresă
- user logat cu o adresă
- user logat cu mai multe adrese
- user logat adăugare adresă nouă
- user logat editare adresă
- selecție altă adresă salvată
- câmpul Observații pentru livrare / curier este păstrat și verificat dacă apare în comanda finală și emailuri

Pentru fiecare:

- fără reload complet la salvare, dacă JS funcționează
- summary/list update imediat
- persistă după refresh
- aceeași adresă ajunge în sesiunea WooCommerce

## 10. Livrare

- pasul `Tip de livrare` este afișat corect
- costul livrării este vizibil
- estimarea de livrare este vizibilă
- lista de produse din cardul de livrare se afișează corect
- comportamentul `Arată mai mult / Arată mai puțin` funcționează

## 11. Facturare

- pasul de facturare se afișează corect
- câmpurile obligatorii sunt validate
- datele se păstrează după erori
- rezumatul este coerent cu adresa și comanda

## 12. Metode de plată

- metodele de plată se încarcă
- pot fi selectate fără erori
- orice integrare sandbox răspunde corect
- schimbarea metodei nu rupe checkout-ul

## 13. Plasare comandă

- comanda poate fi trimisă în sandbox
- order received / thank you page se afișează corect
- header-ul simplificat se vede și acolo
- footer-ul simplificat se vede și acolo
- detaliile comenzii sunt corecte

## 14. Email comandă

- email către client trimis
- email către admin trimis
- diacriticele sunt corecte
- linkurile din email folosesc domeniul de staging

## 15. QA mobil

Verifică minim:

- `390px`
- `768px`
- `1024px`

Zone obligatorii:

- header
- homepage
- categorie
- produs
- cart
- checkout
- my account

## 16. Performance Audit după publicarea staging-ului

Acest audit se execută doar după ce staging-ul are URL public.

### Timp de încărcare

- TTFB
- Largest Contentful Paint
- fully loaded time

### Request-uri HTTP

- număr total de request-uri
- request-uri blocate sau lente
- request-uri duplicate

### CSS încărcat

- verifică fișierele CSS încărcate
- identifică CSS foarte mare sau nefolosit
- verifică dacă `style.css` devine un bottleneck

### JS încărcat

- inventariază scripturile front-end
- verifică scripturile încărcate global vs condițional
- verifică request-urile AJAX din checkout și account

### Query-uri WooCommerce

- pagină shop
- pagină produs
- cart
- checkout
- order received

### Imagini

- dimensiuni reale
- formate
- compresie
- lazy-loading

### Cache

- cache pagină
- object cache, dacă există
- browser cache pentru assets

### Pluginuri lente

- pluginuri cu impact mare în frontend
- pluginuri cu impact mare în admin
- pluginuri inutile pentru staging

## 17. Raport final după deploy

La finalul validării pe staging trebuie livrat:

- lista fișierelor modificate pentru deploy
- lista pluginurilor instalate
- lista pluginurilor active
- eventuale incompatibilități
- probleme găsite
- capturi relevante
- recomandări de optimizare
- ce NU a putut fi testat
