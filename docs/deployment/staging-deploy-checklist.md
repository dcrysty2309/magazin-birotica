# Staging Deploy Checklist

Checklist dedicat primului deploy și deploy-urilor viitoare pe `notix.ro`.

## 1. Pregătire locală

- export DB actualizat
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

- upload pachet WordPress în `public_html`
- confirmare că `wp-content/themes/papetarie-storefront` există
- confirmare că `wp-content/themes/storefront` există
- confirmare că `wp-content/plugins/woocommerce` există
- confirmare că `wp-content/uploads` există

## 4. Import DB

- import SQL prin phpMyAdmin
- verificare tabele importate
- verificare prefix tabele

## 5. Configurare după import

- actualizare `wp-config.php`
- search-replace URL
- verificare `home/siteurl`
- verificare conexiune DB

## 6. Finalizare tehnică

- flush permalinks
- verificare cron
- verificare email
- ștergere fișier SQL de pe server dacă a fost urcat temporar

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
