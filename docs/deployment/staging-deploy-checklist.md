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

- upload pachet de cod în `public_html` — **niciodată la rădăcina FTP** (`/wp-content/...` fără `/public_html/` e o cale moartă, nevăzută de Apache; vezi [deployment-guide.md §6.2](./deployment-guide.md#62-important--calea-reală-de-pe-server-e-public_htmlwp-content-nu-wp-content))
- confirmare că `public_html/wp-content/themes/papetarie-storefront` există
- confirmare că `public_html/wp-content/themes/storefront` există
- confirmare că `public_html/wp-content/plugins/woocommerce` există
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
- **cron real pentru `wp-cron.php` (obligatoriu pentru sincronizarea Aperta)** — vezi detalii mai jos
- verificare email
- ștergere fișier SQL de pe server dacă a fost urcat temporar
- confirmă că staging nu conține dump-uri SQL sau runner-e de sincronizare rămase din proces

### 6.1. Cron real pentru wp-cron.php (Aperta sync)

WP-Cron implicit se declanșează doar din traficul de vizitatori — la ore de noapte cu trafic scăzut (sincronizarea Aperta rulează ~1:10–3:10), rulările automate pot întârzia sau nu pot porni deloc dacă nimeni nu încarcă o pagină. Fără acest pas, coloana „Pornit” din „Sincronizare Aperta” → „Istoric sincronizări” ar putea arăta rulări automate întârziate sau lipsă, la fel cum se întâmplă azi în mediul local (vezi nota din pagina de admin despre WP-Cron care nu se declanșează singur în Docker).

**De configurat în cPanel → Cron Jobs** (necesită login manual — nu se automatizează, parola nu se introduce prin script):

- Common Settings: „Once Per Five Minutes” (`*/5 * * * *`), sau manual: Minute `*/5`, Hour `*`, Day `*`, Month `*`, Weekday `*`
- Command:
  ```
  curl -s "https://notix.ro/wp-cron.php?doing_wp_cron" -o /dev/null
  ```
  Dacă `curl` nu e disponibil pe host, alternativă cu `wget`:
  ```
  wget -q -O /dev/null "https://notix.ro/wp-cron.php?doing_wp_cron"
  ```
  Dacă niciuna dintre variantele HTTP nu funcționează fiabil (posibil, dacă hostingul blochează request-uri "loopback" la propriul domeniu, ca în mediul local), alternativă prin PHP CLI direct (necesită calea reală către binarul PHP din cPanel → MultiPHP Manager / Select PHP CLI Version):
  ```
  /usr/local/bin/php /home/memoreaz/public_html/wp-cron.php
  ```

După configurare, verifică:
- WooCommerce → Stare → Acțiuni programate: acțiunile `pap_aperta_sync_products_start` / `pap_aperta_sync_stock_start` rulează la ora programată, nu rămân "restante".
- Pagina „Sincronizare Aperta” din admin: după prima rulare automată de după cron, apare un rând nou în „Istoric sincronizări” cu „Pornit” = „Automat (program)”.

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
