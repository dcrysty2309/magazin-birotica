# Data Sync Strategy

Acest document defineste logica oficiala pentru sincronizarea datelor intre:

- `HOME-LOCAL`
- `OFFICE-LOCAL`
- `STAGING`

Scopul este sa evitam sync-uri complete de DB atunci cand nu sunt necesare si sa pastram staging-ul relevant pentru QA, fara sa il suprascriem inutil cu continut administrativ care nu conteaza pentru flow-ul testat.

## 1. Principiul de baza

Nu sincronizam baza de date "pentru ca s-a schimbat ceva".

Sincronizam doar datele care sunt necesare pentru:

- reproducerea unui flow;
- validarea unui use case;
- alinierea unui fixture de test;
- configurarea unui mecanism WooCommerce relevant pentru checkout.

## 2. Tipuri de date

### A. Date de produs / continut comercial

Exemple:

- nume produs;
- descriere produs;
- imagini produs;
- categorii comerciale;
- continut editorial;
- bannere;
- pagini de marketing.

Regula:

- aceste date NU se sincronizeaza automat din local in staging;
- se sincronizeaza doar daca staging trebuie sa valideze explicit acel continut.

### B. Date de configurare functionala

Exemple:

- pagini WooCommerce;
- slug-uri;
- permalinks;
- shipping zones;
- shipping methods;
- payment methods;
- checkout pages;
- optiuni care afecteaza direct flow-ul.

Regula:

- acestea se sincronizeaza in staging cand flow-ul depinde de ele.

### C. Date de test pentru checkout

Exemple:

- useri de test;
- adrese salvate;
- sesiuni/fixtures relevante;
- comentarii din `checkout-test-cases`;
- configurari speciale pentru reproducerea bugurilor.

Regula:

- acestea se sincronizeaza in staging atunci cand vrem paritate reala pentru QA.

## 3. Ce facem in practica

### Caz 1. Modificare doar de cod

Exemple:

- CSS;
- JS;
- PHP in tema;
- markup checkout;
- iconuri;
- validari FE deja bazate pe structura existenta.

Actiune:

- `git push`
- deploy tema pe staging
- fara sync de DB

### Caz 2. Modificare de flow care depinde de configurari WP/WooCommerce

Exemple:

- pagina checkout;
- setari WooCommerce;
- shipping logic;
- payment logic;
- fixture-uri de checkout;
- useri si adrese de test.

Actiune:

- commit cod
- export DB din mediul sursa
- sync spre staging
- QA pe staging

### Caz 3. Modificare comerciala/editoriala

Exemple:

- ai schimbat numele unui produs doar pentru test local;
- ai editat un banner;
- ai schimbat copy de continut temporar.

Actiune:

- NU faci sync complet de DB spre staging
- fie refaci manual acea schimbare pe staging daca e necesara
- fie ignori diferenta daca nu afecteaza QA-ul

## 4. Sursa de adevar pentru DB

Nu exista "o singura baza buna" tot timpul.

Sursa de adevar pentru date devine mediul in care ai facut schimbarea relevanta, dar numai pe categoria de date afectata.

Exemple:

- daca ai lucrat la checkout fixtures acasa, `HOME-LOCAL` devine sursa pentru checkout test data;
- daca ai lucrat la produse comerciale in admin la birou, `OFFICE-LOCAL` poate fi sursa pentru acele produse;
- daca pe staging apar doar comentarii QA, staging este sursa pentru acele comentarii pana le exportam sau le notam.

## 5. Regula importanta: nu dam mereu dump complet

Dump-ul complet este acceptabil pentru:

- bootstrap;
- reset de mediu;
- checkout parity;
- milestone major.

Dar nu este solutia standard pentru orice schimbare mica din admin.

## 6. Strategia recomandata de acum inainte

### Layer 1. Cod

- tot codul merge prin Git;
- localurile se sincronizeaza intre ele prin `push` si `pull`;
- staging primeste cod prin deploy script.

### Layer 2. Date functionale de checkout

- le tratam ca "data fixtures";
- cand sunt importante pentru QA, le urcam in staging prin sync controlat.

### Layer 3. Continut comercial

- nu il propagam automat din local;
- staging il schimbam doar daca are relevanta pentru testul curent.

## 7. Decizia simpla inainte de orice sync

Inainte de sync intrebi doar:

1. schimbarea afecteaza flow-ul sau doar continutul?
2. staging trebuie sa reproduca exact aceasta stare?
3. fara aceste date, QA-ul ar fi fals sau incomplet?

Daca raspunsul este:

- `nu` -> deploy doar cod;
- `da` -> sync cod + date relevante.

## 8. Regula operationala

Pentru checkout, My Account, shipping, billing si payment:

- preferam paritate reala intre local si staging.

Pentru produse, continut si marketing:

- preferam sync selectiv, nu dump complet automat.
