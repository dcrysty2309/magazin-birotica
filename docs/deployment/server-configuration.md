# Server Configuration

Acest document descrie configurația minimă necesară pe server pentru staging.

## Environment

- tip: `STAGING`
- hosting: `cPanel shared hosting`
- domeniu: `memoreaza.ro`
- cPanel: `http://rs.nsh.ro/cpanel`
- file manager: `Da`
- FTP: `Da`
- folder public: `public_html`

## Componente de verificat pe server

### PHP

De verificat în cPanel:

- versiunea PHP disponibilă
- extensii PHP necesare pentru WordPress/WooCommerce

Minim recomandat:

- PHP `8.2+`

Local, proiectul rulează în container:

- WordPress `6.8.1`
- PHP `8.3` în imaginea Docker

Pe staging trebuie confirmată compatibilitatea.

### MySQL / MariaDB

De verificat în cPanel:

- engine disponibil: `MySQL` sau `MariaDB`
- limită de dimensiune import
- acces prin phpMyAdmin

### HTTPS

De verificat:

- certificat SSL activ
- `https://memoreaza.ro` funcțional
- redirect HTTP -> HTTPS

### Email

De configurat:

- SMTP real sau mail relay disponibil pe hosting
- adresă `from` validă
- livrare email WooCommerce

### Cron

De verificat:

- dacă `wp-cron` rulează normal
- dacă există cron real disponibil în cPanel
- dacă WooCommerce Scheduled Actions rulează corect

## Extensii PHP recomandate

- `curl`
- `dom`
- `exif`
- `fileinfo`
- `gd` sau `imagick`
- `intl`
- `json`
- `mbstring`
- `mysqli`
- `openssl`
- `pcre`
- `zip`
- `zlib`

## Limite recomandate

De verificat în cPanel / MultiPHP INI Editor:

- `memory_limit`
- `upload_max_filesize`
- `post_max_size`
- `max_execution_time`
- `max_input_vars`

Pentru WooCommerce staging, recomandare minimă:

- `memory_limit`: `256M` sau mai mult
- `upload_max_filesize`: suficient pentru import media / pluginuri
- `post_max_size`: mai mare decât upload max
- `max_execution_time`: suficient pentru operații WooCommerce

## Foldere sensibile

Trebuie protejate operațional:

- `wp-config.php`
- fișiere temporare de import
- fișiere `.sql`
- eventuale loguri cu informații sensibile

Regulă:

- niciun dump SQL nu rămâne în `public_html` după import

## Cerință operațională

Orice modificare a configurației serverului care afectează deploy-ul trebuie documentată aici.
