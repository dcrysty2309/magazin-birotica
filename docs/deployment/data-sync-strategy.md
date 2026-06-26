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

- acestea NU se sincronizeaza automat in staging;
- daca sunt configuratii stabile de business, staging trebuie pastrat aliniat cu live;
- propagarea lor spre staging se face doar cu confirmare explicita.

### B1. Configuratii stabile de business

Exemple:

- metode de plata;
- metode de transport;
- shipping zones;
- taxe;
- checkout pages oficiale;
- setari WooCommerce care trebuie sa ramana compatibile cu live.

Regula:

- staging trebuie sa ramana 1 la 1 cu live pentru aceasta categorie;
- localul poate fi folosit pentru experimente, dar acele schimbari nu se urca automat;
- orice sync care atinge aceasta categorie trebuie confirmat explicit de utilizator.

### C. Date de test pentru checkout

Exemple:

- useri de test;
- adrese salvate;
- sesiuni/fixtures relevante;
- comentarii din `checkout-test-cases`;
- configurari speciale pentru reproducerea bugurilor.

Regula:

- acestea se sincronizeaza in staging atunci cand vrem paritate reala pentru QA.
- ele nu trebuie sa rescrie configuratiile stabile de business fara acord explicit.

### C1. QA data salvata direct pe staging

Exemple:

- comentarii salvate din `Preview` in `checkout-test-cases`;
- statusuri de bug precum `open`, `in_progress`, `fixed`, `ignored`;
- observatii de retestare lasate direct pe staging;
- note QA salvate in WordPress pentru scenarii de checkout.

Regula:

- aceste date apartin staging-ului si trebuie pastrate acolo pana la inchiderea problemelor;
- ele nu se suprascriu prin import de DB fara confirmare explicita;
- daca utilizatorul cere citirea observatiilor, sursa este staging, nu local;
- observatiile colectate din staging trebuie transformate in lista de probleme si apoi in taskuri concrete.

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

- daca schimbarea atinge doar fixtures de test, exporti datele relevante si faci sync controlat;
- daca schimbarea atinge configuratii stabile de business, nu faci sync automat;
- inainte de sync trebuie confirmat explicit daca staging trebuie sa devina diferit de live.

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

Inainte de orice import de DB peste staging trebuie verificat explicit daca exista QA data activa care trebuie pastrata:

- comentarii din Preview;
- statusuri de retestare;
- observatii nesolutionate legate de checkout.

Daca exista, importul complet se amana sau se face doar dupa backup/export al acelor date.

## 5.1. Regula de confirmare explicita

Pentru urmatoarele categorii trebuie intrebat utilizatorul inainte de sync:

- payment methods;
- shipping methods;
- shipping zones;
- taxe;
- pagini WooCommerce oficiale;
- setari WooCommerce de business.

Intrebarea standard este:

- `Vrei sa propag si configuratiile de business spre staging sau pastram staging aliniat cu live si urcam doar fixture-urile de test?`

## 6. Strategia recomandata de acum inainte

### Layer 1. Cod

- tot codul merge prin Git;
- localurile se sincronizeaza intre ele prin `push` si `pull`;
- staging primeste cod prin deploy script.

### Layer 2. Date functionale de checkout

- le tratam ca "data fixtures";
- cand sunt importante pentru QA, le urcam in staging prin sync controlat.

### Layer 2B. Configuratii de business

- staging trebuie sa ramana aliniat cu live;
- nu le modificam automat din local;
- orice propagare se face doar dupa confirmare explicita.

### Layer 3. Continut comercial

- nu il propagam automat din local;
- staging il schimbam doar daca are relevanta pentru testul curent.

## 7. Decizia simpla inainte de orice sync

Inainte de sync intrebi doar:

1. schimbarea afecteaza flow-ul sau doar continutul?
2. staging trebuie sa reproduca exact aceasta stare?
3. fara aceste date, QA-ul ar fi fals sau incomplet?
4. schimbarea atinge configuratii stabile de business?

Daca raspunsul este:

- `nu` -> deploy doar cod;
- `da`, dar doar pentru fixtures -> sync cod + date relevante;
- `da`, iar schimbarea atinge business config -> ceri confirmare explicita inainte de sync.

## 8. Regula operationala

Pentru checkout, My Account, shipping, billing si payment:

- preferam paritate reala intre local si staging doar pentru fixture-urile necesare QA;
- pentru configuratiile de business preferam paritate intre staging si live.

Pentru produse, continut si marketing:

- preferam sync selectiv, nu dump complet automat.
