# Sync Workflow

Acest document transformă regulile de sincronizare în pași practici.

Se aplică pentru:

- `HOME-LOCAL`
- `OFFICE-LOCAL`
- `STAGING`

## 1. Obiectiv

La finalul oricărei iterații importante trebuie să putem răspunde clar la 4 întrebări:

1. Ce commit rulează?
2. Ce dump DB corespunde acelui commit?
3. Care este mediul sursă?
4. Ce flow-uri au fost validate?

Dacă nu putem răspunde la aceste întrebări, mediile nu sunt considerate sincronizate.

## 2. Artefactele obligatorii

Pentru o sincronizare completă folosim 3 artefacte:

### A. Commit git

Conține:

- codul;
- documentația;
- scripturile;
- orice schimbare structurală versionabilă.

### B. Export DB

Conține:

- pagini WordPress;
- setări WooCommerce;
- useri și adrese de test;
- conținut administrat din WP;
- comentarii QA din staging sau local.

Naming standard pentru export DB:

- `dbsync-<source-environment>-<timestamp>-<short-sha>-<label>.sql`
- exemplu:
  - `dbsync-office-local-20260626-084438-2c422f62-checkout-sync-test.sql`

### C. Sync Manifest

Conține:

- commit SHA;
- fișierul dump folosit;
- mediul sursă;
- mediul țintă;
- URL-uri de test;
- userii de test;
- flow-ul validat;
- observații.

## 3. Workflow oficial home -> office

### Caz 1: doar cod

Pe `HOME-LOCAL`:

1. verifici `git status`
2. rulezi testul relevant
3. faci `git add -A`
4. faci `git commit`
5. faci `git push`
6. generezi sau actualizezi manifestul dacă este un milestone important

Pe `OFFICE-LOCAL`:

1. faci `git pull --ff-only`
2. pornești proiectul
3. verifici use case-ul relevant

### Caz 2: cod + DB

Pe `HOME-LOCAL`:

1. faci commit și push la cod
2. exporți DB cu naming standard
3. generezi manifestul de sync
4. transmiți commit-ul și dump-ul

Pe `OFFICE-LOCAL`:

1. faci `git pull --ff-only`
2. imporți dump-ul
3. faci flush permalinks dacă este nevoie
4. verifici aceleași flow-uri ca în manifest

## 4. Workflow oficial office -> home

Este identic cu secțiunea anterioară.

Nu există “reguli speciale” în funcție de calculator.

Calculatorul care conține schimbarea devine sursa de adevăr doar după:

- `push` pentru cod;
- export DB pentru date.

## 5. Workflow oficial local -> staging

### Variante suportate

#### Varianta A: deploy doar cod

Folosești când:

- ai modificat doar tema / pluginuri / JS / CSS / PHP;
- flow-ul nu depinde de schimbări noi în WP admin.

Pași:

1. confirmi commit-ul
2. faci deploy de cod
3. rulezi smoke test
4. validezi flow-ul în staging
5. notezi rezultatul în manifest

#### Varianta B: deploy cod + DB

Folosești când:

- ai schimbat checkout pages;
- ai schimbat opțiuni WooCommerce;
- ai creat useri de test;
- ai configurat shipping;
- ai schimbat date care afectează flow-ul.

Pași:

1. confirmi commit-ul
2. exporți DB local
3. deploy cod pe staging
4. imporți dump DB în staging
5. flush permalinks
6. rulezi QA
7. actualizezi manifestul

## 6. Când declarăm că local și staging sunt “la fel”

Două medii sunt considerate aliniate doar dacă toate condițiile sunt adevărate:

1. rulează același commit SHA pentru codul custom relevant;
2. rulează aceeași familie de date / același dump funcțional;
3. au aceleași setări WooCommerce relevante pentru flow;
4. use case-urile principale sunt validate pe ambele;
5. diferențele de sesiune, cache sau user sunt excluse.

Fără aceste condiții, similitudinea vizuală nu este suficientă.

## 7. Când trebuie exportat DB obligatoriu

Exportul DB devine obligatoriu dacă ai făcut oricare dintre acestea:

- ai creat/modificat pagini WordPress;
- ai schimbat `Settings > Permalinks`;
- ai modificat WooCommerce settings;
- ai schimbat metode de livrare;
- ai schimbat metode de plată;
- ai creat sau modificat useri/adrese de test;
- ai adăugat conținut de QA care trebuie păstrat;
- ai schimbat orice opțiune care afectează direct checkout-ul.

## 8. Când NU este necesar export DB

Nu este necesar dacă ai modificat doar:

- CSS;
- JS;
- PHP din temă;
- documentație;
- tool-uri de build/deploy;
- markup și layout care nu depind de schimbări de admin.

## 9. Validarea minimă după sync

După orice sync important verificăm minim:

1. homepage
2. my account
3. cart
4. checkout
5. `checkout-test-cases`
6. user guest
7. user logat cu adresă
8. user logat fără adresă

## 10. Regula finală

Un sync nu este considerat terminat când “pare ok”.

Este terminat doar când există:

- commit;
- dump DB, dacă era necesar;
- manifest;
- test de verificare.

## 11. Scripturi oficiale

Pentru acest proces folosim:

- [export-sync-db.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/export-sync-db.ps1)
- [new-sync-manifest.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/new-sync-manifest.ps1)
- [build-staging-package.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/build-staging-package.ps1)
- [deploy-staging.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/deploy-staging.ps1)
- [prepare-sync-package.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/prepare-sync-package.ps1)
- [sync-staging-db.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/sync-staging-db.ps1)
- [sync-staging.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/sync-staging.ps1)
