# Integrare Oblio — AWB + document + un singur email

Data: 2026-08-16.

## Ce face

Pe pagina fiecărei comenzi din WooCommerce apare un panou nou, „AWB + document Oblio", cu:
- un câmp pentru AWB
- un buton „Generează document și trimite"

La apăsare: se generează automat un document (proformă sau factură, configurabil) în Oblio, folosind datele reale ale comenzii (client, produse, prețuri), cu AWB-ul scris în câmpul „Mențiuni" al documentului. Documentul (PDF) e descărcat și atașat la **un singur email** trimis clientului, care conține și AWB-ul în text. Emailul automat al Oblio e dezactivat pentru acest flux (`sendEmail: 0` la generare) — nu se trimit două emailuri.

Motivul: la origine, adăugarea AWB-ului (notă WooCommerce) și generarea facturii (Oblio) erau două acțiuni separate, fiecare cu emailul ei — la fel ca la eMAG, dar corect, ar trebui să fie un singur email.

## Fișiere

- `wp-content/themes/papetarie-storefront/admin-oblio.php` — tot codul (setări, meta box, client API Oblio, handler AJAX). Inclus din `functions.php`, doar în admin.

## Configurare necesară (o singură dată)

WooCommerce → **Oblio** (submeniu nou) → completezi:

| Câmp | De unde |
|---|---|
| Email cont Oblio | contul folosit la ARTFLEX |
| API Secret | Oblio → Setări → Date generale → Date Cont |
| CIF firmă | CIF-ul ARTFLEX din Oblio |
| Serie document | o serie deja creată în Oblio (recomandat: una nouă, dedicată, ex. `TESTNOTIX`, separată de seria reală ARTFLEX) |
| Tip document implicit | **Proformă** pentru testare (nu-i document fiscal, nu se trimite la SPV) → schimbi pe **Factură** doar când sunteți siguri că totul merge corect |

Câmpul de API Secret nu se completează programatic din motive de securitate — se pune manual, direct în formular.

## Cum funcționează tehnic

1. La apăsarea butonului, JS trimite AWB-ul + ID-ul comenzii către un handler AJAX (`pap_oblio_generate`), protejat cu nonce per-comandă.
2. Handler-ul: obține un token de acces Oblio (cache-uit 50 min, via `wp_remote_post` la `/api/authorize/token`), construiește payload-ul documentului din datele reale ale comenzii (produse, client, adresă), și îl trimite la `/api/docs/proforma` sau `/api/docs/invoice`, în funcție de setare.
3. Cota de TVA folosită pe produse e luată automat din contul Oblio (`/api/nomenclature/vat_rates`), nu-i hardcodată — se potrivește automat cu statusul de neplătitor TVA al ARTFLEX.
4. La succes: numărul documentului + link-ul se salvează pe comandă (postmeta), PDF-ul se descarcă temporar, se trimite emailul combinat către client, apoi fișierul temporar se șterge.
5. **Protecție împotriva dublării**: dacă documentul a fost deja generat pentru acea comandă, butonul cere confirmare explicită ("Regenerează + retrimite") înainte să genereze din nou — plus `idempotencyKey` trimis și către Oblio (bazat pe ID-ul comenzii), care blochează dublarea și la nivelul lor.

## De testat, în ordine

1. Completează setările (Proformă, serie de test)
2. Deschide o comandă existentă → pune un AWB de test → apasă butonul
3. Verifică: documentul apare corect în Oblio (contul de test/serie separată), emailul ajunge la client cu AWB-ul în text + PDF atașat, un singur email (nu două)
4. După ce confirmi că totul e corect, poți șterge proformele de test din Oblio (nu-s documente fiscale, se pot șterge complet)
5. Abia după testare completă, schimbi setarea pe **Factură** pentru comenzile reale

## Ce NU a fost testat încă (necesită prezența utilizatorului)

Cheia API a fost furnizată direct în conversație, dar nu a fost introdusă nicăieri de asistent (nici în `.env`, nici în baza de date) — din motive de securitate, acest pas rămâne strict manual, făcut de proprietarul contului. Simularea completă end-to-end (buton → document → email) nu a fost rulată de asistent din același motiv — se face împreună cu utilizatorul, la revenire.
