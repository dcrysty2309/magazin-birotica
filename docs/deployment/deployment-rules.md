# Deployment Rules

Acest document stabilește procesul standard pentru deploy-urile viitoare pe staging.

## 1. Principii

- Local este mediul de dezvoltare.
- Staging este mediul oficial de QA și validare.
- Production primește doar cod validat în staging.
- Deploy-ul merge într-o singură direcție: local → staging.
- Nu se sincronizează automat staging înapoi în local.
- Datele din staging sunt date de test, nu date reale.
- După orice deploy pe staging sau production, mediul trebuie lăsat curat, coerent și testabil.
- Dacă developmentul a atins conturi de test, acestea trebuie restaurate la o stare cunoscută.
- Dacă developmentul a modificat adrese, coșuri, comenzi sau alte fixtures de test, acestea trebuie resetate la baseline.
- Documentația trebuie actualizată ori de câte ori se schimbă conturi, parole, adrese, note QA sau alte prerechizite de test.
- Comentariile QA vizibile în staging sunt backlog oficial și trebuie păstrate până la rezolvare.

## 2. Sursa de adevăr

- Git este sursa de adevăr pentru cod.
- Nu se face deploy din modificări locale neversionate dacă vrem un staging reproductibil.
- Orice deploy trebuie să poată fi repetat după documentația din repo.

## 3. Ce se deployează uzual

Pentru iterații de temă și checkout:

- se deployează tema `papetarie-storefront`;
- dacă este nevoie, se deployează și pluginuri custom;
- baza de date nu se reimportă la fiecare deploy de cod.

## 4. Baza de date

- Importul complet de DB se face doar la bootstrap sau când este necesară sincronizarea mediului.
- Modificările de cod nu trebuie să depindă de dump-uri SQL lăsate în `public_html`.
- Nu se publică fișiere SQL pe serverul public.
- Dacă un test modifică adrese, comenzi sau alte date de checkout, trebuie să existe o procedură clară de reset pentru datele de test.
- Dacă un test sau un deploy a schimbat fixture-uri, conturi sau adrese, acestea trebuie recreate sau restaurate înainte de închiderea taskului.

## 5. Secrete

- Credențialele FTP, MySQL, API și SMTP nu se salvează în repo.
- Scripturile de deploy citesc secretele din parametri sau variabile de mediu.

## 6. Regula FTP pentru acest hosting

- Contul FTP de deploy pentru `notix.ro` este deja chroot-uit în `public_html`.
- Asta înseamnă că path-urile remote trebuie scrise relativ la `public_html`, nu cu prefix suplimentar `/public_html/...`.
- Exemplu corect pentru temă:
  - `/wp-content/themes/papetarie-storefront`
- Exemplu greșit:
  - `/public_html/wp-content/themes/papetarie-storefront`
- Dacă folosim prefixul greșit, ajungem să uploadăm într-o structură paralelă de tip `public_html/public_html/...`, iar site-ul live nu va servi acele fișiere.

## 7. Flow standard pentru deploy

1. verifici statusul local
2. testezi local schimbările relevante
3. deploy pe staging
4. rulezi smoke checks
5. testezi flow-ul afectat în UI
6. documentezi dacă s-a schimbat procesul

## 7.1. Ciclul oficial de QA pentru checkout

- rulăm verificările relevante înainte de fiecare deploy pe staging;
- folosim indexul vizual `/checkout-test-cases/` pentru scenariile checkout;
- scenariile testate pe staging devin sursa oficială de bug-uri prin comentariile salvate în Preview;
- comentariile de QA sunt păstrate în WordPress, în CPT-ul intern `pap_checkout_comment`, și pot fi exportate împreună cu backup-ul / dump-ul bazei de date;
- comentariile pot avea status `open`, `in_progress`, `fixed` sau `ignored`, iar istoricul se păstrează per comentariu;
- orice bug descoperit pe staging trebuie transformat în task concret înainte de următorul deploy;
- după fix, se retestează scenariile afectate pe staging;
- staging rămâne mediul oficial de validare finală până când scenariile relevante nu mai au observații.
- dacă un scenariu schimbă comportamentul implementat, documentația și test cases trebuie actualizate în aceeași iterație.
- dacă staging are comentarii QA active, acestea nu se pierd prin import de DB fără confirmare explicită.

## 8. Automatizare

- Deploy-ul automat de temă rulează din GitHub Actions prin `.github/workflows/deploy-staging.yml`.
- Workflow-ul pornește la `push` pe `main` doar când se schimbă tema `papetarie-storefront` sau scriptul de deploy.
- Workflow-ul poate fi pornit și manual din tab-ul Actions.
- Conturile de test trebuie să rămână aliniate între local și staging.
- Secrete necesare în GitHub:
  - `STAGING_FTP_HOST`
  - `STAGING_FTP_USER`
  - `STAGING_FTP_PASSWORD`

## 9. Regula de întreținere

- Dacă se schimbă procesul real de deploy, se actualizează acest fișier și documentația din `docs/deployment/` în aceeași iterație.
- Sincronizarea între `HOME-LOCAL`, `OFFICE-LOCAL` și `STAGING` urmează obligatoriu regulile din [environment-sync-rules.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/environment-sync-rules.md).
- 
## 10. Scripturi oficiale pentru sync complet

- Pentru sync de DB local -> staging folosim [sync-staging-db.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/sync-staging-db.ps1).
- Pentru sync complet `tema + DB + smoke check` folosim [sync-staging.ps1](/D:/proiecte/mag-pap/magazin-birotica/tools/sync-staging.ps1).
- Nu lÄƒsÄƒm dump-uri SQL sau runner-e PHP Ã®n `public_html` dupÄƒ sincronizare.
## 11. Strategie de propagare a datelor

- Pentru a decide cand sincronizam DB complet si cand lasam staging-ul neschimbat pe continut, folosim [data-sync-strategy.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/data-sync-strategy.md).
- Regula practica este simpla: pentru checkout, My Account si configurari WooCommerce relevante preferam paritate controlata; pentru continut comercial preferam sync selectiv, nu dump complet automat.
- Pentru configuratiile stabile de business, staging trebuie tratat ca oglinda a live-ului, nu ca oglinda a ultimului local.
- Daca sync-ul atinge metode de plata, metode de transport, taxe, shipping zones sau pagini WooCommerce oficiale, se cere confirmare explicita inainte de propagare.


## 12. Regula pentru comentarii QA din staging

- Comentariile salvate din `Preview` pe `checkout-test-cases` in staging sunt backlog QA activ.
- Aceste comentarii raman pe staging pana cand problema este fixata si retestata.
- Comentariile QA din staging nu se suprascriu prin import de DB fara confirmare explicita.
- Inainte de orice sync de DB spre staging se verifica daca exista observatii QA active care trebuie protejate.
- Daca utilizatorul cere citirea observatiilor, sursa oficiala este staging.
- Observatiile citite de pe staging trebuie transformate mai intai in lista de probleme, apoi in taskuri concrete.

## 13. Regula de finalizare

- Nu raportăm un task ca fiind gata dacă staging nu este curat, coerent și testabil.
- Nu raportăm "Ready for staging QA" până când:
  - test accounts sunt în stare cunoscută;
  - test data este resetată la baseline;
  - documentația este sincronizată;
  - comentariile QA sunt păstrate și disponibile;
  - nu există debug temporar rămas în cod.
