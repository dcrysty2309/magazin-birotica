# Deployment Checklist

Checklist operațional pentru orice deploy pe mediul oficial de staging.

Environment țintă:

- domain: `notix.ro`
- cPanel: `http://rs.nsh.ro/cpanel`
- FTP host: `rs.nsh.ro`
- port: `21`
- protocol recomandat: `FTP explicit TLS`
- public folder: `public_html`

## A. Înainte de deploy

- confirmă că ai backup la fișiere și DB
- confirmă că ai export SQL actualizat
- confirmă că ai identificat tema activă și toate pluginurile active
- confirmă că NU urci fișiere sensibile sau inutile
- confirmă că staging-ul nu este tratat ca producție
- confirmă că mediul local este development și staging este QA oficial
- confirmă că datele de test și conturile de test sunt aceleași pe local și staging

## B. Ce urcăm

- WordPress runtime din [wordpress](/D:/proiecte/mag-pap/magazin-birotica/wordpress)
- pluginurile necesare din [wp-content/plugins](/D:/proiecte/mag-pap/magazin-birotica/wp-content/plugins)
- tema custom din [wp-content/themes/papetarie-storefront](/D:/proiecte/mag-pap/magazin-birotica/wp-content/themes/papetarie-storefront)
- tema părinte `storefront` din [wordpress/wp-content/themes/storefront](/D:/proiecte/mag-pap/magazin-birotica/wordpress/wp-content/themes/storefront)
- upload-urile din [wordpress/wp-content/uploads](/D:/proiecte/mag-pap/magazin-birotica/wordpress/wp-content/uploads)

## C. Ce NU urcăm

- `.env`
- `node_modules`
- `db_data`
- `tmp`
- `tests`
- `docs`
- backup-uri locale
- fișiere `.sql` lăsate în `public_html`
- capturi și imagini de lucru din rădăcina repo-ului
- orice fișier temporar sau cache local

## D. Baza de date

- export local
- creare DB pe hosting
- creare user DB
- asociere user la DB cu toate privilegiile
- import SQL în staging
- search-replace URL local -> staging
- dacă testarea a modificat adrese sau comenzi, rulează procedura de reset a datelor de test înainte de următorul ciclu de QA

## E. Configurare WordPress

- actualizare `wp-config.php`
- verificare `home` și `siteurl`
- verificare salts
- verificare `WP_DEBUG`
- verificare conexiune DB

## F. După deploy

- flush permalinks
- verificare homepage
- verificare login/register
- verificare my account
- verificare cart
- verificare checkout
- verificare WooCommerce status
- verificare email

## G. QA minim obligatoriu

- homepage
- categorie
- produs
- coș
- checkout guest
- checkout user logat
- flow-uri adresă
- facturare
- metode de plată
- email comandă
- observații pentru livrare / curier, dacă sunt expuse în fluxul final
- compară rezultatul final local cu staging; staging rămâne referința de QA final
