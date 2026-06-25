# Deployment Rules

Acest document stabilește procesul standard pentru deploy-urile viitoare pe staging.

## 1. Principii

- Local este mediul de dezvoltare.
- Staging este mediul oficial de QA și validare.
- Production primește doar cod validat în staging.
- Deploy-ul merge într-o singură direcție: local → staging.
- Nu se sincronizează automat staging înapoi în local.
- Datele din staging sunt date de test, nu date reale.

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

## 5. Secrete

- Credențialele FTP, MySQL, API și SMTP nu se salvează în repo.
- Scripturile de deploy citesc secretele din parametri sau variabile de mediu.

## 6. Flow standard pentru deploy

1. verifici statusul local
2. testezi local schimbările relevante
3. deploy pe staging
4. rulezi smoke checks
5. testezi flow-ul afectat în UI
6. documentezi dacă s-a schimbat procesul

## 6.1. Ciclul oficial de QA pentru checkout

- rulăm verificările relevante înainte de fiecare deploy pe staging;
- folosim indexul vizual `/checkout-test-cases/` pentru scenariile checkout;
- scenariile testate pe staging devin sursa oficială de bug-uri prin comentariile salvate în Preview;
- orice bug descoperit pe staging trebuie transformat în task concret înainte de următorul deploy;
- după fix, se retestează scenariile afectate pe staging;
- staging rămâne mediul oficial de validare finală până când scenariile relevante nu mai au observații.

## 7. Automatizare

- Deploy-ul automat de temă rulează din GitHub Actions prin `.github/workflows/deploy-staging.yml`.
- Workflow-ul pornește la `push` pe `main` doar când se schimbă tema `papetarie-storefront` sau scriptul de deploy.
- Workflow-ul poate fi pornit și manual din tab-ul Actions.
- Conturile de test trebuie să rămână aliniate între local și staging.
- Secrete necesare în GitHub:
  - `STAGING_FTP_HOST`
  - `STAGING_FTP_USER`
  - `STAGING_FTP_PASSWORD`

## 8. Regula de întreținere

- Dacă se schimbă procesul real de deploy, se actualizează acest fișier și documentația din `docs/deployment/` în aceeași iterație.
