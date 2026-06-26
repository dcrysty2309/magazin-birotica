# Environment

## STAGING

- Environment: `STAGING`
- Domain: `notix.ro`
- Hosting: `cPanel shared hosting`
- Server/IP: `89.40.19.79`
- cPanel: `http://rs.nsh.ro/cpanel`
- FTP Host: `rs.nsh.ro`
- FTP Port: `21`
- FTP Protocol recomandat: `FTP explicit TLS`
- Public Folder: `public_html`
- Platform: `WordPress + WooCommerce`
- Theme activă: `papetarie-storefront`
- Theme părinte necesară: `storefront`
- PHP Version: `de verificat în cPanel`
- Database Engine: `MySQL/MariaDB de verificat în cPanel`
- HTTPS: `de verificat`
- FTP Available: `Da`
- File Manager: `Da`
- cPanel Available: `Da`
- Nameservers:
  - `ns1.nsh.ro`
  - `ns2.nsh.ro`
  - `ns3.nsh.ro`
  - `ns4.nsh.ro`

## Reguli

Nu se salvează în repository:

- parole
- username-uri de acces
- token-uri
- chei API
- credențiale FTP
- credențiale MySQL
- chei private

Reguli de lucru:

- Local este development.
- Staging este QA oficial.
- Testarea finală se face pe staging, nu pe local.
- Datele de staging sunt date de test.
- Nu se sincronizează automat staging înapoi peste local.
- Deploy-ul merge doar local → staging.
- Conturile de test trebuie să fie aceleași pe ambele medii.
- Dacă testele modifică adrese sau comenzi, trebuie să existe o procedură de reset pentru datele de test.

## Observații

- acest fișier conține doar informații permanente și nesensibile
- orice schimbare de infrastructură relevantă pentru deploy trebuie actualizată aici
