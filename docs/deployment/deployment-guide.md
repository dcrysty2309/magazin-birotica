# Deployment Guide

Acest document descrie procesul complet și repetabil de deploy pentru mediul de staging pe hosting cPanel.

Regulile de sincronizare între `HOME-LOCAL`, `OFFICE-LOCAL` și `STAGING` sunt definite în:

- [environment-sync-rules.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/environment-sync-rules.md)
- [data-sync-strategy.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/data-sync-strategy.md)

## 1. Scop

Acest mediu este mediul oficial de `STAGING`.

Roluri:

- Local: dezvoltare
- Staging: QA, validare, testare integrări, testare performanță
- Production: doar cod validat în staging

## 2. Context infrastructură

- domeniu: `notix.ro`
- cPanel: `http://rs.nsh.ro/cpanel`
- FTP host: `rs.nsh.ro`
- FTP port: `21`
- protocol recomandat: `FTP explicit TLS`
- folder public: **rădăcina contului FTP** (`/`), NU `public_html` — vezi 6.2
- platformă: WordPress + WooCommerce

## 3. Structura reală a proiectului

Acest proiect nu este un simplu folder WordPress standalone.

Local există split între:

- [wordpress](/D:/proiecte/mag-pap/magazin-birotica/wordpress)
- [wp-content](/D:/proiecte/mag-pap/magazin-birotica/wp-content)

În Docker, directoarele custom din rădăcina repo-ului sunt montate peste `wordpress/wp-content/...`.

Pe hosting acest mecanism nu există.

Asta înseamnă că pentru deploy trebuie construit un pachet final coerent, nu doar copiat `wordpress/`.

## 4. Tema și pluginurile necesare

Temă activă:

- `papetarie-storefront`

Temă părinte obligatorie:

- `storefront`

Pluginuri active local:

- `blocksy-companion`
- `loco-translate`
- `stackable-ultimate-gutenberg-blocks`
- `woocommerce`
- `wpforms-lite`

Pluginuri instalate dar neactive:

- `akismet`

## 5. Override-uri WooCommerce

Există override-uri custom în:

- [woocommerce](/D:/proiecte/mag-pap/magazin-birotica/wp-content/themes/papetarie-storefront/woocommerce)

În special sunt relevante:

- checkout
- payment
- review-order
- cart
- my account

După deploy trebuie verificat `WooCommerce > Status` pentru template-uri outdated.

## 6. Fișiere care trebuie publicate pe server (rădăcina contului FTP — vezi 6.2)

În fluxul actual de staging publicăm doar codul care trebuie executat de site:

- `wp-content/themes/papetarie-storefront`
- `wp-content/themes/storefront`
- `wp-content/plugins`

Nu publicăm automat:

- WordPress core complet
- `wp-admin`
- `wp-includes`
- `wp-content/uploads`
- cache
- logs
- DB

## 6.1. Flux activ de deploy staging: upload direct FTP, doar tema copil

`tools/deploy-staging.ps1` este activ și este singurul script de deploy folosit în prezent. Urcă direct pe FTP, fișier cu fișier, doar `wp-content/themes/papetarie-storefront` (tema copil, ~85 de fișiere după excluderi). Rulare manuală, tipic cu `-SkipPlugins`:

```powershell
pwsh -File tools/deploy-staging.ps1 -SkipPlugins -DryRun
pwsh -File tools/deploy-staging.ps1 -SkipPlugins
```

Scriptul include verificare automată: după upload, re-preia `style.css` de pe `notix.ro` printr-un URL cu query param unic (anti-cache) și compară hash-ul SHA256 cu fișierul local, apoi rulează un smoke check pe `/checkout/` și `/checkout-test-cases/`.

Ce rămâne în afara acestui flux, intenționat, deocamdată:

- **pluginurile** (`wp-content/plugins`, ~11.800 de fișiere) — prea multe pentru upload file-by-file; nu sunt incluse implicit (`-SkipPlugins`), se urcă manual doar când chiar se schimbă;
- **tema părinte `storefront`** — statică, rar modificată;
- **`tools/build-staging-package.ps1`, `tools/sync-staging.ps1`, `tools/sync-staging-db.ps1`, `tools/prepare-sync-package.ps1`** — varianta cu pachet ZIP + runner PHP server-side care le folosea a cauzat majoritatea problemelor istorice (cale de extragere greșită, timeout-uri, verificare nesigură) și rămân dezactivate; nu se reactivează fără o decizie explicită;
- **workflow-ul GitHub Actions** (`.github/workflows/deploy-staging.yml`) — a fost șters (2026-07-23); reactivarea automatizării CI e o decizie separată, ulterioară, după ce fluxul manual e validat.

## 6.2. IMPORTANT — calea reală de pe server e `/wp-content/...` (rădăcina FTP), NU `/public_html/wp-content/...`

Descoperit pe 2026-07-23, apoi **răsturnat din nou pe 2026-07-27** — istoricul contează, ca să nu se repete confuzia:

- Inițial (până pe 2026-07-23): se credea că `/public_html/wp-content/` e folderul real servit de Apache (confirmat atunci în cPanel → Domains → Document Root: `/public_html`), iar `/wp-content/` de la rădăcina contului FTP era ținta veche/greșită.
- Pe **2026-07-27** configurația de pe server s-a schimbat (document root real re-mutat) și situația s-a **inversat complet**: acum contul cPanel `memoreaz` are `wp-load.php`, `wp-config.php`, `wp-admin`, `wp-includes` etc. direct în **rădăcina contului FTP** (`/home/memoreaz/`) — acela e docroot-ul live, real, servit de Apache. `/public_html/` a rămas un folder orfan, mort, care conține doar un `wp-content` vechi (fără `wp-load.php` alături) — nu mai e văzut de site din 2026-07-27.
- Reconfirmat live pe 2026-09-04: `ftp://rs.nsh.ro/` listează `wp-load.php`, `wp-config.php`, `wp-admin/` etc. direct la rădăcină; `ftp://rs.nsh.ro/public_html/` conține doar `wp-content/` (ultima modificare 27 iulie), fără niciun fișier core WordPress alături — exact tiparul unui folder mort.

Simptomul dacă publici din greșeală în locul greșit: upload-ul FTP "reușește" complet, fără nicio eroare, fișierele chiar ajung pe disc — dar cererile HTTP către site tot ajung goale/404 (WordPress le prinde prin propriul rewrite și randează pagina lui de "not found"), pentru că Apache/PHP nu execută niciodată din acel folder.

**Configurația corectă, actuală**: `tools/deploy-staging.ps1` are implicit `-RemoteThemePath "/wp-content/themes/papetarie-storefront"` și `-RemotePluginPath "/wp-content/plugins"` — fără niciun prefix `/public_html`. Scriptul e deja corect (comentariul din el documentează exact această situație). Dacă faci vreun upload manual prin FTP (script de diagnostic, fix punctual etc.), **țintește direct rădăcina FTP** (`ftp://rs.nsh.ro/wp-content/...`), niciodată `/public_html/wp-content/...`.

Cum verifici rapid, dacă bănuiești din nou o desincronizare:

```bash
curl --ssl-reqd -s --user "FTP_USER:FTP_PASS" "ftp://rs.nsh.ro/wp-content/themes/papetarie-storefront/style.css" -o /tmp/live.css
diff /tmp/live.css wp-content/themes/papetarie-storefront/style.css && echo "OK, identic"
```

Dacă vrei să reconfirmi din nou care e configurația curentă (poate să se schimbe iar, ca pe 27 iulie): listează ambele locații prin FTP și vezi unde stau `wp-load.php`/`wp-config.php` direct alături de `wp-content/` — acolo e docroot-ul live, nu presupune nimic doar din acest document.

## 7. Fișiere care NU trebuie publicate

Nu urca niciodată:

- `.env`
- `.git`
- `node_modules`
- `db_data`
- `docs`
- `tests`
- `tmp`
- dump-uri SQL pe server (nici la rădăcina FTP, nici în `public_html`)
- fișiere de backup
- screenshot-uri locale
- fișiere de tooling care nu sunt folosite de WordPress în runtime

## 8. Export baza de date locală

Comandă recomandată:

```powershell
docker compose exec -T db mariadb-dump -uwordpress -pwordpress wordpress > database/wordpress-staging.sql
```

Script reutilizabil din repo:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\export-staging-db.ps1
```

Recomandări:

- exportul trebuie făcut chiar înainte de primul deploy
- nu folosi implicit dump-uri vechi dacă există modificări locale noi
- după export, verifică dimensiunea și că fișierul nu este gol

## 9. Import baza de date pe hosting

În cPanel:

1. mergi în `MySQL Databases`
2. creezi baza de date pentru staging
3. creezi user MySQL nou
4. atașezi userul la baza de date cu `ALL PRIVILEGES`
5. mergi în `phpMyAdmin`
6. selectezi baza nouă
7. imporți fișierul SQL

Dacă importul e prea mare pentru phpMyAdmin:

- folosești import comprimat `.sql.zip` dacă hostingul permite
- sau ceri acces SSH / import asistat de hosting

## 10. Search-replace URL local -> staging

URL-ul local actual:

- `http://localhost:8080`

URL-ul de staging:

- `https://notix.ro`

După import trebuie înlocuite URL-urile.

Varianta recomandată cu WP-CLI:

```bash
wp search-replace 'http://localhost:8080' 'https://notix.ro' --all-tables
```

Dacă nu există WP-CLI:

- folosește un plugin de search-replace doar temporar
- sau rulează operația prin script controlat

Nu folosi search-replace SQL brut pentru câmpuri serializate fără tool WordPress-aware.

## 11. Configurare `wp-config.php`

Trebuie adaptate:

- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_HOST`
- salts
- `WP_DEBUG`

Recomandări pentru staging:

- `WP_DEBUG` poate rămâne `false` în UI public
- `WP_DEBUG_LOG` poate fi activ doar dacă logul nu este expus public
- `WP_HOME` și `WP_SITEURL` pot fi definite explicit dacă vrei mai mult control

Exemplu orientativ:

```php
define('DB_NAME', 'staging_db');
define('DB_USER', 'staging_user');
define('DB_PASSWORD', '***');
define('DB_HOST', 'localhost');

define('WP_HOME', 'https://notix.ro');
define('WP_SITEURL', 'https://notix.ro');

define('WP_DEBUG', false);
```

## 12. Permisiuni

Recomandare standard:

- directoare: `755`
- fișiere: `644`

Obligatoriu writeable:

- `wp-content/uploads`

De verificat după upload:

- upload media
- generare thumbnails
- actualizare plugin/theme doar dacă vrei să permiți din admin

## 13. Flush permalinks

După import și configurare URL:

1. intri în `Settings > Permalinks`
2. apeși `Save Changes`

Structura actuală locală este:

- `/%postname%/`

## 14. Verificare checkout și WooCommerce

După deploy verifici minim:

- pagina shop
- pagina cart
- pagina checkout
- pagina my account
- metodele de livrare
- metodele de plată
- flow guest
- flow user logat
- flow adresă salvată
- dacă deploy-ul a folosit artifact-ul, verifici că ZIP-ul a fost extras corect și că pagina live servește fișierele noi

## 15. Pași manuali de business

Pașii manuali care nu trebuie automatizați prin deploy sunt documentați separat în:

- [staging-manual-steps.md](./staging-manual-steps.md)
- order received
- email de comandă
- conturile de test afectate sunt restaurate la o stare cunoscută
- fixture-urile de test modificate sunt resetate sau recreate
- comentariile QA din `checkout-test-cases` rămân disponibile și neatinse dacă fac parte din backlog-ul activ
- documentația aferentă este actualizată dacă au apărut conturi, parole, adrese sau reguli noi
- dacă deploy-ul pare că nu a ajuns pe staging, verificarea inițială este cache/opcache/browser cache, nu repo-ul
- un deploy de temă nu este considerat final până când pagina live servește efectiv schimbările așteptate

Checklist-ul operațional detaliat este în:

- [staging-checklist.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/staging-checklist.md)
- [staging-deploy-checklist.md](/D:/proiecte/mag-pap/magazin-birotica/docs/deployment/staging-deploy-checklist.md)

## 15. Observații importante pentru acest proiect

- local folosește Mailpit, staging are nevoie de SMTP real
- există multe template override WooCommerce
- proiectul nu are pipeline de build pentru CSS/JS al temei
- `style.css` este mare și trebuie monitorizat la auditul de performanță
- performanța reală se validează doar după publicarea online
- după orice deploy trebuie verificat că staging rămâne curat, coerent și testabil
- dacă debug-ul temporar a fost folosit în dezvoltare, nu se publică pe staging
- dacă staging servește încă markup vechi după upload, procedeul corect este cache purge / opcache refresh, nu re-upload orb

## 16. Regula de închidere a ciclului de deploy

- Un deploy nu se consideră finalizat până când:
  - conturile de test sunt funcționale;
  - datele de test sunt la baseline;
  - comentariile QA sunt păstrate;
  - documentația este sincronizată;
  - nu există debug temporar rămas;
  - staging este pregătit pentru o nouă rundă QA.

## 17. Sync automat local -> staging

Scriptul complet pentru sync este:

`tools/sync-staging.ps1` este legacy și dezactivat (aruncă eroare la rulare). Nu îl mai folosim pentru staging.

Scriptul de DB-only, `tools/sync-staging-db.ps1`, este de asemenea legacy și dezactivat. Nu îl mai folosim pentru staging.

Când erau active, aceste scripturi urcau tema pe staging, urcau temporar dump-ul SQL, rulau importul controlat în baza staging, făceau search-replace pentru `http://localhost:8080` -> `https://notix.ro`, flush la permalinks și smoke checks pe `checkout` și `checkout-test-cases`, apoi ștergeau artefactele temporare de pe server. Pentru deploy de cod folosim în schimb fluxul activ din secțiunea 6.1.
