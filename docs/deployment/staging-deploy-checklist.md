# Staging Deploy Checklist

Checklist dedicat primului deploy și deploy-urilor viitoare pe `notix.ro`.

## 1. Pregătire locală

- export DB doar dacă este cerut explicit pentru o sincronizare separată
- verificare pluginuri active
- verificare temă activă
- verificare upload-uri
- verificare fișiere sensibile care nu trebuie urcate

## 2. Pregătire cPanel

- login în cPanel
- verificare `public_html`
- verificare SSL
- creare baza de date
- creare user baza de date
- asociere user + privilegii

## 3. Upload fișiere

- upload pachet de cod în `public_html`
- confirmare că `wp-content/themes/papetarie-storefront` există
- confirmare că `wp-content/themes/storefront` există
- confirmare că `wp-content/plugins/woocommerce` există
- confirmare că fișierele de cod au ajuns, fără a suprascrie DB sau uploads

## 4. Import DB

- nu se face automat
- dacă este nevoie, se execută manual conform [staging-manual-steps.md](./staging-manual-steps.md)

## 5. Configurare după import

- actualizare `wp-config.php` doar dacă este nevoie
- search-replace URL doar pentru o sincronizare manuală aprobată
- verificare `home/siteurl`
- verificare conexiune DB

## 6. Finalizare tehnică

- flush permalinks
- verificare cron
- verificare email
- ștergere fișier SQL de pe server dacă a fost urcat temporar
- confirmă că staging nu conține dump-uri SQL sau runner-e de sincronizare rămase din proces

## 7. Validare funcțională

- homepage
- categorii
- produse
- login
- register
- my account
- cart
- checkout
- metode de livrare
- facturare
- metode de plată
- comandă test
- email comandă

## 8. Semn-off

- staging funcțional
- fără erori majore console/PHP
- WooCommerce status verificat
- problemele rămase documentate
