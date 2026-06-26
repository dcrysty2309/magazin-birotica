# Environment Sync Rules

Acest document stabilește regula oficială pentru sincronizarea între cele 3 medii:

- `HOME-LOCAL`
- `OFFICE-LOCAL`
- `STAGING`

Scopul este simplu:

- să știm întotdeauna care versiune este corectă;
- să putem muta munca între acasă și birou fără ambiguități;
- să putem urca pe staging nu doar codul, ci și datele relevante din baza de date;
- să nu mai existe situații în care codul, DB-ul și comportamentul UI sunt desincronizate.

## 1. Rolul fiecărui mediu

### HOME-LOCAL

Mediu de dezvoltare.

Se folosește pentru:

- implementare;
- UI polish;
- prototipare;
- experimente controlate;
- pregătirea schimbărilor pentru commit.

### OFFICE-LOCAL

Tot mediu de dezvoltare.

Se folosește pentru:

- continuarea muncii începute acasă;
- QA rapid;
- verificări de integrare;
- retestări înainte de push.

### STAGING

Mediu oficial de validare.

Se folosește pentru:

- QA final;
- testare checkout;
- testare WooCommerce;
- testare pe mobil;
- testare UX;
- testare performanță;
- validarea schimbărilor înainte de production.

## 2. Sursa de adevăr

Trebuie separate clar:

### Codul

Git este singura sursă oficială de adevăr pentru cod.

Asta înseamnă:

- dacă o schimbare importantă nu este în git, ea nu există oficial;
- nu considerăm niciodată că una dintre mașini este “versiunea bună” doar pentru că arată altfel;
- versiunea corectă de cod este commit-ul sau branch-ul din git.

### Baza de date

Pentru datele de dezvoltare, sursa de adevăr nu este git, ci dump-ul DB decis explicit pentru sincronizare.

Asta înseamnă:

- DB-ul nu este implicit sincronizat între `HOME-LOCAL`, `OFFICE-LOCAL` și `STAGING`;
- sincronizarea DB trebuie făcută intenționat;
- trebuie să știm clar din ce mediu a fost exportat dump-ul și pentru ce scop.

## 3. Regulă critică: codul și DB-ul se versionează separat

Problema principală de până acum este că s-au amestecat două tipuri de schimbări:

- schimbări de cod;
- schimbări de conținut/configurație/date din WordPress/WooCommerce.

De acum înainte, le tratăm separat:

### Schimbări de cod

Exemple:

- PHP în temă;
- override-uri WooCommerce;
- CSS;
- JS;
- template-uri;
- scripturi de deploy;
- documentație.

Acestea merg prin:

`edit local -> test local -> commit -> push -> deploy staging`

### Schimbări de date / DB

Exemple:

- pagini WordPress create sau modificate;
- setări WooCommerce;
- checkout pages;
- shipping zones / methods;
- useri de test;
- adrese salvate;
- order statuses de test;
- comentarii QA din WordPress;
- setări pluginuri;
- meniuri și opțiuni din admin.

Acestea merg prin:

`schimbare într-un mediu -> export DB controlat -> import în mediul țintă`

Nu presupunem niciodată că un `git pull` sincronizează și baza de date.

## 4. Principiul de lucru zilnic

### Regula 4.1

Când termini o sesiune de lucru importantă pe unul dintre mediile locale, trebuie să salvezi:

- codul în git;
- și, dacă ai schimbat admin/config/date, un dump de DB sau o acțiune documentată care reconstruiește acele date.

### Regula 4.2

Dacă ai schimbat doar cod:

- faci commit;
- faci push;
- pe celălalt calculator faci `pull`.

Nu este nevoie de dump DB.

### Regula 4.3

Dacă ai schimbat și DB:

- faci commit la cod;
- exporți DB;
- notezi clar că dump-ul aparține unei stări funcționale;
- imporți dump-ul pe mediul pe care vrei să continui.

### Regula 4.4

Nu lucrăm mai multe ore cu schimbări importante care rămân doar în local și nu sunt nici în git, nici în dump DB.

Aceasta este cauza principală a confuziei între medii.

## 5. Ce tipuri de date trebuie sincronizate între home și office

Trebuie sincronizate când influențează UX-ul sau flow-ul:

- pagini WordPress folosite de flow;
- setări WooCommerce;
- shipping methods;
- checkout settings;
- useri de test;
- adrese de test;
- pagini speciale de QA;
- conținut administrat din WordPress care afectează layout-ul sau flow-ul.

Nu este obligatoriu să sincronizăm:

- comenzi vechi fără relevanță;
- loguri;
- cache;
- transients temporari;
- sesiuni expirate;
- email-uri locale de test.

## 6. Pachetul oficial de mutare între calculatoare

Când vrei să continui munca de pe `HOME-LOCAL` pe `OFFICE-LOCAL` sau invers, transferul complet trebuie să însemne:

### A. Cod

- `git status` curat sau înțeles;
- `git add`, `commit`, `push`;
- pe celălalt calculator: `git pull`.

### B. DB, doar dacă este nevoie

Se exportă un dump de DB doar dacă s-au schimbat date importante din admin.

Exemple de cazuri în care trebuie export DB:

- ai creat sau modificat pagina checkout;
- ai configurat shipping;
- ai configurat users/adrese de test;
- ai modificat opțiuni WooCommerce din admin;
- ai creat conținut de test care afectează flow-ul.

### C. Fișier de context

Pentru iterațiile mari trebuie să existe și context scris:

- ce commit trebuie folosit;
- dacă trebuie importat și DB;
- ce user de test trebuie folosit;
- ce pagini sau URL-uri trebuie verificate;
- ce flow este considerat corect.

## 7. Regula de aur pentru staging

Staging nu trebuie tratat ca “merge cumva”.

Pentru staging, deploy-ul corect înseamnă:

### Cod

- se urcă dintr-un commit concret din git;
- ideal, commit-ul este deja pe `main`;
- staging trebuie să poată fi asociat unui SHA clar.

### DB

DB-ul de staging se actualizează doar în unul dintre următoarele moduri:

- import complet controlat;
- schimbări manuale documentate;
- migrare controlată / pași repetați manual în admin.

Nu amestecăm:

- cod nou dintr-un mediu;
- DB vechi din alt mediu;
- plus schimbări manuale uitate în staging.

Aceasta produce exact situația în care “nu mai știm care versiune este corectă”.

## 8. Modelul recomandat de lucru

### Varianta simplă și sănătoasă

#### Pas 1

Lucrezi local, fie acasă, fie la birou.

#### Pas 2

Dacă ai schimbat doar cod:

- commit;
- push;
- pull pe celălalt calculator.

#### Pas 3

Dacă ai schimbat și date în admin:

- commit la cod;
- export DB;
- imporți DB pe celălalt mediu dacă vrei continuitate 1:1.

#### Pas 4

Când vrei staging fidel:

- deploy cod din commit-ul dorit;
- import DB compatibil cu acel cod, dacă flow-ul depinde de datele respective;
- rulezi QA.

## 9. Ce nu mai facem

De acum înainte evităm următoarele situații:

- modificări importante doar pe un local fără commit;
- modificări importante în admin fără dump DB;
- staging modificat manual fără să notăm ce s-a schimbat;
- comparații de tip “la mine arată altfel” fără să știm:
  - ce commit rulează;
  - ce DB rulează;
  - ce user de test este folosit;
  - ce stare a coșului există;
  - ce shipping config există.

## 10. Regula pentru checkout și flow-uri sensibile

Checkout-ul depinde de:

- cod;
- opțiuni WooCommerce;
- useri;
- adrese;
- shipping;
- produse în coș;
- sesiune.

De aceea, pentru orice validare de checkout trebuie notate minim:

- commit SHA;
- mediu: `home-local`, `office-local` sau `staging`;
- user folosit;
- produse din coș;
- dacă userul este guest sau logat;
- dacă DB-ul a fost sincronizat sau nu.

Fără aceste informații, două capturi de ecran nu sunt comparabile corect.

## 11. Workflow oficial home <-> office

### Caz A: doar cod

Pe calculatorul sursă:

1. `git status`
2. test local
3. `git add -A`
4. `git commit -m "..."`
5. `git push`

Pe calculatorul destinație:

1. `git pull --ff-only`
2. pornești proiectul
3. verifici flow-ul relevant

### Caz B: cod + DB

Pe calculatorul sursă:

1. commit + push la cod
2. export DB
3. notezi că dump-ul aparține commit-ului respectiv

Pe calculatorul destinație:

1. `git pull --ff-only`
2. import DB
3. flush permalinks dacă e nevoie
4. verifici flow-ul relevant

## 12. Workflow oficial local -> staging

### Deploy mic, doar cod

1. codul este în git
2. staging primește exact commit-ul dorit
3. se golește cache-ul relevant
4. se rulează smoke test

### Deploy care depinde de DB

1. codul este în git
2. se decide explicit ce DB trebuie folosit
3. se importă DB sau se reaplică manual schimbările necesare
4. se rulează QA pe use case-urile afectate

## 13. Regula pentru “versiunea corectă”

Când există dubii, ordinea de verificare este:

1. Care este commit-ul?
2. Ce DB rulează?
3. Ce user de test este folosit?
4. Ce produse sunt în coș?
5. Ce setări WooCommerce influențează flow-ul?

Nu mai decidem “versiunea bună” după memorie sau după cum arată o captură izolată.

## 14. Recomendare operațională

Ideal, pentru schimbările mari de checkout:

- codul merge în git în aceeași zi;
- DB-ul relevant se exportă în aceeași zi;
- staging este actualizat în aceeași zi sau cel târziu după validarea locală;
- testele de checkout se fac pe un mediu despre care știm exact:
  - ce cod are;
  - ce DB are.

## 15. Standard final

Standardul oficial de acum înainte este:

- `Git` = adevărul pentru cod
- `DB export explicit` = adevărul pentru date mutabile
- `Staging` = adevărul pentru validarea finală

Dacă un lucru nu este ori în git, ori într-un dump DB clar identificat, îl considerăm nesincronizat și nereproductibil.
