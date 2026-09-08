# Flux emailuri — notix.ro

Ultima verificare/actualizare: 2026-08-16 (versiune finală după integrarea Oblio + ajustări digest).

## Infrastructură de trimitere

- **Transport**: Brevo (SMTP relay), prin plugin-ul `GoSMTP` + `GoSMTP Pro` (Softaculous), activate în WordPress.
- **Domeniu `notix.ro` autentificat în Brevo** (SPF + DKIM x2 + DMARC, verificate prin DNS la NSHost). Orice adresă `@notix.ro` poate trimite fără restricții suplimentare.
- Înainte de asta, site-ul folosea `mail()` PHP brut de pe hosting — emailurile nu ajungeau deloc (nici măcar în spam). Motiv găsit: nicio autentificare de domeniu, transport nesigur pe shared hosting.
- Plan gratuit Brevo: **300 emailuri/zi**, se resetează zilnic, nu se acumulează.

## Adrese configurate

| Scop | Valoare |
|---|---|
| From (WooCommerce, toate emailurile de comandă) | `noreply@notix.ro` / nume afișat "Notix" |
| From (emailuri custom: activare cont, digest Aperta, AWB+document Oblio) | `wordpress@notix.ro` (implicit WordPress, domeniul fiind autentificat funcționează) |
| admin_email (WP core) | `d.crysty23@gmail.com` |
| Destinatari notificări comandă (nouă / anulată / eșuată) | `d.crysty23@gmail.com` + `laviniamuntean40@gmail.com` |
| Destinatari digest zilnic Aperta | `d.crysty23@gmail.com` + `laviniamuntean40@gmail.com` |

Înainte de fix: toate adresele de mai sus (From WooCommerce + admin_email) erau `admin@papetarie.local` — un domeniu fals, rămas de pe mediul local de dezvoltare. Niciun email de comandă nu ajungea nicăieri.

## Metodă de plată activă

**Doar COD (Numerar la livrare / "Plată la livrare")** este activă pe site. Transfer bancar (BACS) și plată cu cec sunt dezactivate. Plugin-ul de plată cu cardul (`bt-ipay-payments`, BT iPay) e instalat dar **niciodată activat** — nu există momentan nicio metodă de plată online funcțională.

Comportament WooCommerce implicit pentru COD: comanda trece direct pe status **Processing** (nu "On-hold") — nu are sens să se aștepte confirmarea plății, banii se colectează la livrare de curier, deci magazinul poate începe pregătirea/expedierea imediat.

## Fluxul complet al unei comenzi, în ordine

### 1. Client își face cont (opțional — se poate cumpăra și ca guest)
- **Email**: activare cont, cu link de confirmare (valabil 2 zile)
- **From**: `wordpress@notix.ro` · **Către**: client
- Emailul dublu de "cont nou creat" (WooCommerce nativ) a fost **dezactivat** — era redundant cu cel de activare.

### 2. Client plasează o comandă (COD)
- Status automat: **Processing**
- **Client primește**: confirmare comandă — include tabel cu produsele comandate (nume, cantitate, preț), totalul, și rândul "Metodă de plată: Plată la livrare" (template WooCommerce standard, nemodificat de temă, tradus în română)
- **Admin (tu + Lavinia) primiți**: notificare "comandă nouă"

### 3. Trimiți comanda la furnizor (dropshipping)
- **Manual**, în afara site-ului — nu există nicio integrare/automatizare cu furnizorul (Aperta) pentru trimiterea automată a comenzilor. Doar sincronizarea de produse/stoc e automată, nu și transmiterea comenzilor plasate de clienți.

### 4. Primești AWB de la furnizor → generezi document + trimiți, dintr-un singur loc
- Pe pagina comenzii (WooCommerce → Comenzi → deschide comanda), panoul **„AWB + document Oblio"** din dreapta
- Completezi AWB-ul, apeși **„Generează document și trimite"**
- Sistemul: generează automat un document (Proformă sau Factură, configurabil) în Oblio prin API, cu AWB-ul scris în câmpul „Mențiuni" al documentului; descarcă PDF-ul; trimite **un singur email** către client cu AWB-ul în text + PDF-ul atașat
- Emailul automat al Oblio e dezactivat pentru acest flux (nu se dublează) — testat end-to-end, confirmat livrat (Brevo: Sent → Delivered → Opened)
- **From**: `wordpress@notix.ro` · **Către**: client
- Protecție anti-dublare: dacă documentul a fost deja generat, butonul devine „Regenerează + retrimite" (cere reconfirmare explicită)
- Detalii tehnice complete: [docs/integrare-oblio.md](integrare-oblio.md)

### 5. Confirmi livrarea + încasarea (verificare manuală în platforma curierului) și marchezi „Completed"
- **Niciun email nu se trimite automat acum** — emailul standard „comandă finalizată" a fost **dezactivat**, pentru că ajungea la ore distanță de livrarea reală (verificare o dată/zi) și nu mai avea valoare informativă
- Vezi task #89: versiune îmbunătățită (mulțumire + cerere recenzie + eventual cupon), fază 2, post-lansare

### 6. Anulezi o comandă
- **Client primește**: notificare de anulare (activat)
- **Admin (tu + Lavinia) primiți**: notificare de anulare

### 7. Client cere retur (buton „Retur" pe o comandă finalizată/în procesare)
- **Admin (tu + Lavinia) primiți**: cererea de retur (nume, comandă, motiv, detalii)
- **Client nu primește** nicio confirmare automată că cererea a ajuns (nu a existat niciodată acest email)
- Notă: tab-ul „Retururi" **nu apare în meniul din Cont** — accesibil doar prin butonul de pe o comandă individuală. Decizie deschisă: îl facem vizibil permanent în meniu sau rămâne contextual?

### 8. Client uită parola
- **Email**: resetare parolă (WooCommerce nativ)
- **From**: `noreply@notix.ro` · **Către**: client

### 9. Plata eșuează (relevant doar când se activează o metodă online — momentan n/a, COD nu are acest caz)
- **Client + admin** primesc notificare

### 10. Digest zilnic Aperta, în fiecare zi la 18:15 (ora României)
- Se trimite **întotdeauna**, indiferent dacă a revenit ceva pe stoc sau nu — dacă nu-i nimic nou, mesajul spune explicit „Niciun produs revenit pe stoc azi" (confirmă doar că verificarea a rulat normal, nu lasă loc de ambiguitate între „nimic nou" și „cronul a picat")
- **Către**: `d.crysty23@gmail.com` + `laviniamuntean40@gmail.com`
- Programat la 18:15 (ora României) — după ultima verificare orară de stoc din ziua respectivă (rulează 9:10–17:10, ultima se termină practic pe la 17:53)

## Stilizare unitară (2026-08-16)

Toate emailurile — atât cele native WooCommerce, cât și cele 4 custom (activare cont, AWB+document Oblio, cerere retur, digest Aperta) — folosesc acum același wrapper vizual: antet cu logo text „Notix", culoare navy `#173764` (aliniată cu butonul, care înainte folosea alt navy iar antetul rămăsese pe mov implicit `#8526ff`), font Open Sans, colțuri drepte, footer „Notix — papetărie și birotică."

Mecanism tehnic: `papetarie_storefront_wrap_email_html($heading, $bodyHtml)` (în `functions.php`) — combină `WC()->mailer()->wrap_message()` (structura HTML) cu `style_inline()` (aplică CSS-ul ca inline styles, pas necesar separat, altfel culorile nu apar în clienți de email care ignoră `<style>`). Verificat direct: HTML-ul rezultat conține `#173764` inline pe elemente, nu doar într-un `<style>` neaplicat.

## Decizii deschise / de urmărit

- [ ] Tab „Retururi" — vizibil permanent în meniul Cont, sau rămâne accesibil doar contextual (buton pe comandă)?
- [ ] Task #89 — email „comandă finalizată" îmbunătățit (mulțumire + recenzie + cupon), fază 2 post-lansare
- [ ] Task #90 — combinarea AWB+factură în același email cu emailul de comandă (eMAG-style, „predat curierului" + link factură) — necesită dezvoltare suplimentară dacă se dorește, momentan sunt emailuri separate în timp (confirmare comandă la plasare, apoi AWB+document quando îl generezi)
- [ ] Automatizare trimitere comandă către furnizor (Aperta) — momentan 100% manual
- [ ] Activare plată cu cardul (BT iPay) — plugin instalat, neconfigurat
- [ ] Serie de test dedicată în Oblio (`TESTNOTIX` sau similar) pentru testări viitoare fără să atingă seria reală ART — nu a fost creată cu succes încă, testul de azi a folosit seria reală (proformă ștearsă după verificare)

## Infrastructură cron (relevant pentru sincronizarea Aperta, nu emailuri, dar verificat în aceeași sesiune)

- `DISABLE_WP_CRON` adăugat în `wp-config.php`
- Cron real în cPanel deja exista: `*/1 * * * * curl -s "https://notix.ro/wp-cron.php?doing_wp_cron" -o /dev/null`
- Cele două combinate elimină suprapunerea care cauza eșecuri nocturne la sincronizarea de produse (eroare "lista cu evenimente planificate nu a putut fi salvată").
