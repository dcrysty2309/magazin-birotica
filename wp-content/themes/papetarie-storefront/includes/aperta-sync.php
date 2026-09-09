<?php

defined('ABSPATH') || exit;

/**
 * Sync produse din feed-urile Aperta/Scribant Distribution (dropshipping).
 * Vezi contractul de discount (Anexa dropshipping Artflex) pentru procentele
 * de mai jos — nu apar nicăieri în CSV.
 */

const PAP_APERTA_PRODUCTS_FEED_URL = 'https://www.aperta.ro/feed.csv';
const PAP_APERTA_STOCK_FEED_URL = 'https://www.aperta.ro/feed-stoc.csv';
// Categorii de top din feed excluse definitiv de la import (decizie user
// 2026-07-28) - verificate pe segmentul 0 al coloanei "Categorie produs" din
// feed, nu pe taxonomia noastra rezolvata (Molotow nu are propria categorie
// de top pe site, se mapeaza in subcategorii sub "Arta" - vezi
// papetarie_storefront_aperta_top_level_map()). "Curatenie si sanitare"
// adaugata 2026-09-02 (decizie user) - categorie de risc legal, produsele
// chimice de curatenie/dezinfectie pot necesita autorizatii/notificari
// suplimentare pe care afacerea nu le are; cele 201 produse deja importate
// au fost mutate manual la cosul de gunoi in aceeasi sesiune.
const PAP_APERTA_EXCLUDED_TOP_LEVEL_CATEGORIES = ['Molotow', 'Universul copiilor', 'Curățenie și sanitare'];
// Sub-categorii excluse (nu sunt segmentul 0, deci nu pot fi prinse de lista
// de mai sus) - verificate dupa slug (sanitize_title), pe orice segment al
// caii brute din feed, nu doar ultimul. "intretinere-si-curatenie" adaugata
// 2026-09-02 (decizie user, acelasi motiv ca mai sus - spray-uri/solutii
// chimice de curatare electronice); cele 13 produse deja importate au fost
// mutate manual la cosul de gunoi in aceeasi sesiune.
const PAP_APERTA_EXCLUDED_SUBCATEGORY_SLUGS = ['intretinere-si-curatenie'];
const PAP_APERTA_CHUNK_SIZE = 25;
// Produsele nu mai sunt impartite pe un numar fix per bucata (vezi
// PAP_APERTA_PRODUCTS_CHUNK_TIME_BUDGET mai jos) - un numar fix de 10 insemna
// bucati la fel de "scumpe" indiferent daca produsele erau neschimbate
// (rapid) sau noi, cu poze multe (lent), risipind timp pe cele neschimbate
// (marea majoritate) doar ca sa stea sub pragul de 300s la care Action
// Scheduler marcheaza automat actiunea "esuata" (descoperit live pe staging
// 2026-07-26). Cu buget de timp, o bucata proceseaza cat incape sub prag,
// oricate produse ar fi asta - rapida cand sunt neschimbate, protejata cand
// pica pe un cluster de produse noi.
//
// Masurat live pe staging 2026-07-28 cu bugetul initial de 45s: fiecare
// bucata dura ~50-58s indiferent cate produse continea (45s lucru + ~5-10s
// bootstrap WP/WC + delay-ul de 5s pana la urmatoarea) - adica timpul total
// era dominat de NUMARUL de bucati, nu de continutul lor. Marind bugetul,
// acelasi cost fix de bootstrap se plateste de mai putine ori. Ramanem cu
// marja mare sub pragul de 300s de la care Action Scheduler marcheaza
// actiunea esuata.
const PAP_APERTA_PRODUCTS_CHUNK_TIME_BUDGET_SECONDS = 150;
// Plasa de siguranta - opreste bucata chiar daca timpul nu s-a scurs inca,
// ca sa nu ramana intr-o bucla foarte lunga intr-un singur request daca timpul
// per produs ar fi neasteptat de mic (nu ar trebui sa se intample, dar evitam
// orice risc de request nesfarsit). Marit proportional cu bugetul de timp de
// mai sus, ca sa nu devina el insusi noul plafon pe bucatile ieftine.
const PAP_APERTA_PRODUCTS_CHUNK_MAX_ITEMS = 900;
const PAP_APERTA_SYNC_DELAY_MINUTES = 20;
// Temporar (investigare 2026-07-29): o bucata a fost marcata "esuata" de
// Action Scheduler dupa ~379s, peste bugetul nostru de 150s, dar fara nicio
// urma in error_log-ul PHP (nici macar un fatal de "maximum execution time")
// - semn ca procesul a fost omorat din afara PHP (server), nu ca a picat
// intr-un mod normal, catchable. Logam un checkpoint per produs (inainte si
// dupa upsert) ca sa vedem exact la ce produs se blocheaza data viitoare -
// de scos dupa ce cauza reala e confirmata.
const PAP_APERTA_DEBUG_TIMING = true;
// Un produs cu foarte multe variante costa mult intr-o singura iteratie
// (fiecare varianta = un save() WooCommerce complet, plus verificarea pozelor
// ei). Verificarea de buget de timp se face doar INTRE produse, deci un
// asemenea produs pornit tarziu in bucata poate trece singur peste pragul de
// 300s la care Action Scheduler marcheaza actiunea esuata - si atunci nici
// hash-ul lui nu se mai salveaza, deci esueaza identic la fiecare rulare, la
// infinit (confirmat live 2026-07-30: LIN034, 32 variante, a blocat importul
// exact la produsul 261 din 2395, doua nopti la rand). Un produs peste pragul
// asta primeste o bucata intreaga numai pentru el, cu tot bugetul disponibil.
const PAP_APERTA_HEAVY_PRODUCT_ROWS = 12;
// Stocul se procesa in bucati fixe de 100 SKU-uri => 54 de bucati pentru cele
// ~5340 SKU-uri din feed-stoc.csv. Fiecare bucata isi programa urmatoarea si
// apoi ISI TERMINA rularea, deci de multe ori urmatoarea nu mai era luata de
// aceeasi trecere a cozii, ci de urmatoarea declansare a cronului de sistem -
// care pe notix.ro e din 5 in 5 minute (masurat live 2026-07-30: pauze de
// exact 299-301s intre bucati consecutive). 54 de bucati x pana la 5 minute
// de asteptare = ore intregi pentru o munca reala de ~3 minute (o rulare a
// durat 8950s = 2h29m). Bucatile de stoc sunt acum limitate de TIMP, nu de un
// numar fix, exact ca la produse - o singura bucata acopera tot feed-ul in
// cazul normal, deci nu se mai aduna asteptari intre bucati.
const PAP_APERTA_STOCK_CHUNK_TIME_BUDGET_SECONDS = 120;
const PAP_APERTA_STOCK_CHUNK_MAX_ITEMS = 4000;

/**
 * Stare de progres pentru o rulare (produse sau stoc), ca sa poata fi
 * urmarita live din admin: cate au fost procesate din cate, de cand a
 * pornit, si un log scurt cu ultimele produse afectate (SKU + nume).
 */
function papetarie_storefront_aperta_progress_option(string $flow): string
{
    return 'pap_aperta_' . $flow . '_progress';
}

function papetarie_storefront_aperta_progress_get(string $flow): array
{
    $default = [
        'status' => 'idle',
        'total' => 0,
        'processed' => 0,
        'matched' => 0,
        'changed' => 0,
        'unchanged' => 0,
        'started_at' => null,
        'finished_at' => null,
        'recent' => [],
        'trigger' => 'auto',
    ];

    $stored = get_option(papetarie_storefront_aperta_progress_option($flow), []);

    return is_array($stored) ? array_merge($default, $stored) : $default;
}

/**
 * Ca progress_get(), dar pentru AFISARE: o rulare care a murit pe parcurs
 * (bucata omorata de server / marcata esuata de Action Scheduler) ramane
 * inregistrata ca "running" pentru totdeauna, fiindca nimeni nu mai ajunge sa
 * o marcheze terminata. Panoul "Progres live" arata atunci "Rulează…" ore sau
 * zile la rand, ceea ce induce complet in eroare (semnalat 2026-07-30: rularea
 * picata la 00:15 se afisa "pornit acum 325m" a doua zi dimineata).
 *
 * O rulare vie are MEREU o bucata fie programata, fie chiar in executie in
 * Action Scheduler; daca nu exista niciuna, rularea e abandonata.
 */
/**
 * O rulare vie are MEREU o bucata fie programata, fie chiar in executie in
 * Action Scheduler; daca, dupa fereastra de gratie de 5 minute (pornirea
 * descarca + parseaza feed-ul inainte sa programeze prima bucata), nu exista
 * niciuna, rularea a murit pe parcurs (proces omorat de server, fara nicio
 * urma catchable - vezi PAP_APERTA_DEBUG_TIMING) si e considerata abandonata.
 */
function papetarie_storefront_aperta_progress_is_abandoned(string $flow): bool
{
    $progress = papetarie_storefront_aperta_progress_get($flow);

    if (!in_array($progress['status'], ['starting', 'running'], true)) {
        return false;
    }

    if (!$progress['started_at'] || (time() - $progress['started_at']) < 5 * MINUTE_IN_SECONDS) {
        return false;
    }

    if (!function_exists('as_get_scheduled_actions') || !class_exists('ActionScheduler_Store')) {
        return false;
    }

    // Doar hook-ul de BUCATA e un semnal valid de "rulare vie". Cel de start
    // nu e: actiunea recurenta pentru rularea de mâine e mereu "pending", deci
    // ar face orice rulare sa para vesnic in desfasurare.
    $chunkHook = $flow === 'stock' ? 'pap_aperta_sync_stock_chunk' : 'pap_aperta_sync_products_chunk';

    foreach ([ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING] as $status) {
        $live = as_get_scheduled_actions([
            'hook' => $chunkHook,
            'status' => $status,
            'group' => 'aperta-sync',
            'per_page' => 1,
        ], 'ids');

        if (!empty($live)) {
            return false;
        }
    }

    return true;
}

function papetarie_storefront_aperta_progress_for_display(string $flow): array
{
    $progress = papetarie_storefront_aperta_progress_get($flow);

    if (papetarie_storefront_aperta_progress_is_abandoned($flow)) {
        $progress['status'] = 'interrupted';
    }

    return $progress;
}

/**
 * Watchdog: elibereaza o rulare abandonata (vezi is_abandoned() mai sus) ca
 * sa nu mai blocheze un start nou. Fara asta, o rulare de produse picata la
 * 3 dimineata ramanea blocata pana a doua zi - nimic n-o reincerca activ
 * intre timp (Action Scheduler programeaza urmatoarea aparitie a cronului
 * recurent abia peste 24h, nu la esec). Apelata din schedule_cron(), care
 * oricum ruleaza la fiecare ~10 minute - deci o rulare moarta e reincercata
 * in maximum 10-15 minute, nu a doua zi (confirmat live: rularea de produse
 * a murit de 2 nopti la rand, 2026-08-01 si 2026-08-02, fara nicio
 * reincercare pana la cronul urmator).
 *
 * @return bool true daca rularea era abandonata si a fost eliberata acum
 */
function papetarie_storefront_aperta_progress_reset_if_abandoned(string $flow): bool
{
    if (!papetarie_storefront_aperta_progress_is_abandoned($flow)) {
        return false;
    }

    $progress = papetarie_storefront_aperta_progress_get($flow);
    $startedAt = $progress['started_at'];
    $progress['status'] = 'interrupted';
    update_option(papetarie_storefront_aperta_progress_option($flow), $progress, false);

    papetarie_storefront_aperta_debug_checkpoint(
        sprintf(
            "watchdog: rularea '%s' era blocata din %s - eliberata, reincercare programata peste 1 minut",
            $flow,
            $startedAt ? date('d.m.Y H:i:s', $startedAt) : '?'
        ),
        microtime(true)
    );

    return true;
}

/**
 * O rulare (produse sau stoc) e "activa" cat timp e starting/running - alta
 * pornire (fie click pe "Ruleaza acum", fie programul recurent) trebuie sa
 * astepte, nu sa suprascrie afisarea de progres a celei deja in curs.
 * O rulare care n-a mai avansat de peste 2 ore e considerata blocata/abandonata,
 * ca sa nu impiedice pornirea uneia noi la nesfarsit.
 */
function papetarie_storefront_aperta_progress_is_active(string $flow): bool
{
    $progress = papetarie_storefront_aperta_progress_get($flow);

    if (!in_array($progress['status'], ['starting', 'running'], true)) {
        return false;
    }

    if ($progress['started_at'] && (time() - $progress['started_at']) > 2 * HOUR_IN_SECONDS) {
        return false;
    }

    return true;
}

/**
 * Mai ingusta decat is_active(): adevarat doar daca bucatile chiar
 * proceseaza in acest moment (status "running"). Folosita ca sa nu blocam
 * propria pornire dintr-un click pe "Ruleaza acum" (care marcheaza singur
 * "starting" inainte sa apeleze acest cod) - doar o rulare CU ADEVARAT in
 * desfasurare (alta decat pornirea curenta) trebuie sa opreasca un start nou.
 */
function papetarie_storefront_aperta_progress_has_running_chunks(string $flow): bool
{
    $progress = papetarie_storefront_aperta_progress_get($flow);

    if ($progress['status'] !== 'running') {
        return false;
    }

    if ($progress['started_at'] && (time() - $progress['started_at']) > 2 * HOUR_IN_SECONDS) {
        return false;
    }

    return true;
}

/**
 * @return bool false daca o rulare e deja activa (nu s-a pornit una noua)
 */
function papetarie_storefront_aperta_progress_mark_starting(string $flow): bool
{
    if (papetarie_storefront_aperta_progress_is_active($flow)) {
        return false;
    }

    update_option(
        papetarie_storefront_aperta_progress_option($flow),
        [
            'status' => 'starting',
            'total' => 0,
            'processed' => 0,
            'started_at' => time(),
            'finished_at' => null,
            'recent' => [],
            'trigger' => 'manual',
        ],
        false
    );

    return true;
}

/**
 * @param ?string $trigger 'manual' (buton "Rulează acum") sau 'auto' (programul recurent) -
 *                         null pastreaza valoarea deja existenta (folosit la a doua apelare
 *                         din interiorul aceleiasi porniri, ex. chunk_cb(0) dupa start_cb).
 */
function papetarie_storefront_aperta_progress_start(string $flow, int $total, ?string $trigger = null): void
{
    $progress = papetarie_storefront_aperta_progress_get($flow);
    $progress['status'] = 'running';
    $progress['total'] = $total;
    $progress['processed'] = 0;
    $progress['matched'] = 0;
    $progress['matched_published'] = 0;
    $progress['changed'] = 0;
    $progress['unchanged'] = 0;
    // progress_start() inseamna mereu "o rulare noua incepe acum" - fie una
    // de sine statatoare, fie prima bucata (offset 0) a unei secvente
    // inlantuite - in ambele cazuri, "acum" e momentul corect de pornire.
    $progress['started_at'] = time();
    $progress['finished_at'] = null;
    $progress['recent'] = [];
    if ($trigger !== null) {
        $progress['trigger'] = $trigger;
    } elseif (!isset($progress['trigger'])) {
        $progress['trigger'] = 'auto';
    }

    update_option(papetarie_storefront_aperta_progress_option($flow), $progress, false);
}

/**
 * @param array<int, array{sku: string, name: string, changed?: bool, trashed?: bool}> $affectedItems fiecare item = un cod gasit pe site (potrivit); cele nepotrivite nu apar aici
 */
function papetarie_storefront_aperta_progress_tick(string $flow, int $scannedCount, array $affectedItems): void
{
    $progress = papetarie_storefront_aperta_progress_get($flow);
    $progress['processed'] = min($progress['total'], $progress['processed'] + $scannedCount);
    $progress['matched'] += count($affectedItems);

    foreach ($affectedItems as $item) {
        if (!empty($item['changed'])) {
            $progress['changed']++;
        } else {
            $progress['unchanged']++;
        }
        // 'matched' include si produsele din cosul de gunoi (gasite explicit
        // acolo, ca sa le tinem stocul la zi) - 'matched_published' e cel
        // afisat in raport, ca sa se potriveasca cu numararea de la cardul
        // "Total products" (care exclude trash).
        if (empty($item['trashed'])) {
            $progress['matched_published']++;
        }
    }

    // Pastram toate produsele/codurile atinse (nu doar ultimele) - lista
    // completa curge in pagina, ca sa poti cauta un SKU anume cu Ctrl+F.
    $progress['recent'] = array_merge($progress['recent'], $affectedItems);

    update_option(papetarie_storefront_aperta_progress_option($flow), $progress, false);
}

function papetarie_storefront_aperta_progress_finish(string $flow): void
{
    $progress = papetarie_storefront_aperta_progress_get($flow);
    $progress['status'] = 'complete';
    $progress['processed'] = $progress['total'];
    $progress['finished_at'] = time();

    update_option(papetarie_storefront_aperta_progress_option($flow), $progress, false);

    papetarie_storefront_aperta_record_history($flow, $progress);
}

/**
 * Istoric permanent al rulărilor complete (separat de Action Scheduler, care
 * își șterge singur acțiunile vechi) - ca administratorul să poată vedea în
 * timp ce s-a întâmplat la fiecare sincronizare, nu doar la ultima.
 */
function papetarie_storefront_aperta_record_history(string $flow, array $progress): void
{
    $history = get_option('pap_aperta_sync_history', []);
    if (!is_array($history)) {
        $history = [];
    }

    $runId = $flow . '_' . $progress['finished_at'];

    $history[] = [
        'flow' => $flow,
        'finished_at' => $progress['finished_at'],
        'duration' => ($progress['started_at'] && $progress['finished_at']) ? ($progress['finished_at'] - $progress['started_at']) : null,
        'total' => $progress['total'],
        'matched' => $progress['matched'],
        'matched_published' => $progress['matched_published'] ?? $progress['matched'],
        'changed' => $progress['changed'],
        'unchanged' => $progress['unchanged'],
        'run_id' => $runId,
        'trigger' => $progress['trigger'] ?? 'auto',
    ];

    // Pastram ultimele 100 de rulari (produse + stoc combinate) - suficient
    // pentru istoric fara ca optiunea sa creasca la nesfarsit.
    if (count($history) > 100) {
        $history = array_slice($history, -100);
    }

    update_option('pap_aperta_sync_history', $history, false);

    papetarie_storefront_aperta_store_run_log($runId, $progress['recent']);
}

function papetarie_storefront_aperta_run_log_option(string $runId): string
{
    return 'pap_aperta_run_log_' . $runId;
}

/**
 * Produsele schimbate apar primele (utilizatorul trebuie sa le gaseasca rapid
 * intr-o lista de mii de randuri neschimbate) - restul raman in ordinea in
 * care au fost procesate.
 *
 * @return array<int, array{sku: string, name: string, changed?: bool}>
 */
function papetarie_storefront_aperta_get_run_log(string $runId): array
{
    $log = get_option(papetarie_storefront_aperta_run_log_option($runId), []);

    if (!is_array($log)) {
        return [];
    }

    $changed = array_values(array_filter($log, static fn(array $item): bool => !empty($item['changed'])));
    $unchanged = array_values(array_filter($log, static fn(array $item): bool => empty($item['changed'])));

    return array_merge($changed, $unchanged);
}

/**
 * Salveaza log-ul detaliat (SKU + nume + schimbare) al unei rulari intr-o
 * optiune separata de istoricul-rezumat, ca sa poata fi incarcat la cerere
 * (nu odata cu toata pagina). Pastram detaliul complet doar pentru ultimele
 * PAP_APERTA_RUN_LOG_RETENTION rulari - un log de stoc are pana la ~5341
 * linii, deci pastrarea nelimitata ar umfla baza de date fara rost; rulările
 * mai vechi de atat raman in istoric doar cu rezumatul (fara buton "Vezi loguri").
 *
 * @param array<int, array{sku: string, name: string, changed?: bool}> $items
 */
function papetarie_storefront_aperta_store_run_log(string $runId, array $items): void
{
    update_option(papetarie_storefront_aperta_run_log_option($runId), $items, false);

    $ids = get_option('pap_aperta_run_log_ids', []);
    if (!is_array($ids)) {
        $ids = [];
    }
    $ids[] = $runId;

    $keep = 20;
    if (count($ids) > $keep) {
        $toDrop = array_slice($ids, 0, count($ids) - $keep);
        foreach ($toDrop as $oldId) {
            delete_option(papetarie_storefront_aperta_run_log_option((string) $oldId));
        }
        $ids = array_slice($ids, -$keep);
    }

    update_option('pap_aperta_run_log_ids', $ids, false);
}

function papetarie_storefront_aperta_discount_percent(string $group): float
{
    $group = trim($group);

    $map = [
        'SCOALA PRO' => 20.0,
        'SCOALA ECO' => 15.0,
        'BIROU PRO' => 20.0,
        'BIROU ECO' => 15.0,
        'ARTA PRO' => 0.0,
    ];

    return $map[$group] ?? 0.0;
}

/**
 * Calculeaza pretul de vanzare dintr-un rand din feed, cu garda de
 * siguranta: NU intoarce niciodata 0, negativ sau NaN. Un rand cu
 * "Pret produs" gol/nenumeric (celula lipsa in CSV, eroare de export la
 * Aperta etc.) ar fi produs altfel un pret de 0 lei scris direct pe site -
 * (float) pe un string gol/invalid da 0.0 in PHP, fara nicio eroare. Decizie
 * user 2026-09-04: niciodata nu trebuie sa fie posibil sa apara pe site un
 * produs cu pret 0, negativ, sau sub costul real de achizitie.
 *
 * @return float|null null daca randul nu ofera un pret valid - apelantul
 *                     trebuie sa NU scrie pretul (pastreaza pretul vechi
 *                     daca produsul exista deja, sau il lasa nesetat daca e
 *                     nou) si sa raporteze un avertisment.
 */
function papetarie_storefront_aperta_safe_price(string $rawPretProdus, float $discountPercent): ?float
{
    $normalized = trim(str_replace(',', '.', $rawPretProdus));
    // is_numeric() accepta si notatie stiintifica ("1e10") si hex ("0x1A" pe
    // PHP vechi) - un rand corupt in acel format ar trece testul de mai jos
    // si ar produce un pret absurd (ex. 8 miliarde de lei), nu 0/negativ, dar
    // tot un pret care n-are ce cauta pe site (gasit prin testare directa
    // 2026-09-04). Acceptam doar forma normala de pret: cifre, punct zecimal
    // optional, semn minus optional la inceput (respins mai jos oricum de
    // verificarea <= 0).
    if ($normalized === '' || !preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
        return null;
    }

    $base = (float) $normalized;
    if ($base <= 0) {
        return null;
    }

    // Plasa de siguranta suplimentara: niciun produs real de birotica/papetarie
    // nu costa peste acest prag - un pret care il depaseste e aproape sigur o
    // eroare de date (virgula zecimala lipsa, cifra in plus etc.), nu un pret
    // real. Prag generos, cu marja, ca sa nu blocheze din greseala un produs
    // scump dar real (cel mai scump produs vazut in studiul de pricing a fost
    // ~1.500 lei, un distrugator de documente).
    if ($base > 50000) {
        return null;
    }

    $price = round($base * (1 - $discountPercent / 100), 2);
    if ($price <= 0) {
        return null;
    }

    return $price;
}

/**
 * Inregistreaza un avertisment de pret invalid din feed, intr-o lista
 * capata (acelasi tipar ca pap_aperta_pending_variation_migrations), ca sa
 * poata fi verificata oricand, nu doar in log-ul unei singure rulari.
 */
function papetarie_storefront_aperta_record_price_warning(string $sku, string $name, string $rawValue, string $context): void
{
    $warnings = get_option('pap_aperta_price_warnings', []);
    if (!is_array($warnings)) {
        $warnings = [];
    }

    $warnings[] = [
        'sku' => $sku,
        'name' => $name,
        'raw_value' => $rawValue,
        'context' => $context,
        'found_at' => current_time('mysql'),
    ];

    $keep = 200;
    if (count($warnings) > $keep) {
        $warnings = array_slice($warnings, -$keep);
    }

    update_option('pap_aperta_price_warnings', $warnings, false);
}

/**
 * Retine produsele nou-create de sincronizare azi (intra ca ciorna, vezi
 * mai sus), ca sa poata fi mentionate in digest-ul zilnic - inainte nu
 * exista nicio notificare pentru "produs nou" (user 2026-09-04: "nu am
 * vazut niciodata un email cu produsul acesta este nou").
 */
function papetarie_storefront_aperta_record_new_product(int $productId, string $name): void
{
    $newToday = get_option('pap_new_products_today', []);
    if (!is_array($newToday)) {
        $newToday = [];
    }

    $newToday[$productId] = ['name' => $name, 'found_at' => current_time('mysql')];

    update_option('pap_new_products_today', $newToday, false);
}

/**
 * Produse/variatii PUBLICATE si INCA COMANDABILE (in stoc sau on-backorder)
 * pe site, al caror SKU nu mai apare deloc in feedul curent Aperta - adica
 * probabil Aperta nu le mai are in oferta, dar la noi tot pot fi comandate.
 * Risc de business direct: un client comanda, noi n-avem de unde sa il
 * aprovizionam (decizie user 2026-09-04, "asta e foarte important").
 *
 * Sincronizarea normala nu poate detecta singura acest caz - merge "dinspre
 * feed spre site" (parcurge doar randurile primite de la Aperta), deci un
 * SKU disparut complet din feed nu mai e vizitat niciodata. Verificarea de
 * aici merge invers: dinspre catalogul nostru spre feed.
 *
 * @return array<int, array{post_id:int, sku:string, name:string, type:string, stock:?int, price:?string}>
 */
function papetarie_storefront_aperta_products_missing_from_feed_but_orderable(): array
{
    $feedPath = papetarie_storefront_aperta_feed_path('feed');
    if (!file_exists($feedPath)) {
        return [];
    }

    $fh = fopen($feedPath, 'r');
    if ($fh === false) {
        return [];
    }
    $header = fgetcsv($fh, 0, ',', '"', '\\');
    if ($header === false) {
        fclose($fh);
        return [];
    }
    $colIndex = array_flip($header);
    if (!isset($colIndex['Cod unic'])) {
        fclose($fh);
        return [];
    }

    $feedSkus = [];
    while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        $sku = trim((string) ($row[$colIndex['Cod unic']] ?? ''));
        if ($sku !== '') {
            $feedSkus[$sku] = true;
        }
    }
    fclose($fh);

    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT p.ID, p.post_type,
               MAX(CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END) AS sku,
               MAX(CASE WHEN pm.meta_key = '_stock_status' THEN pm.meta_value END) AS stock_status,
               MAX(CASE WHEN pm.meta_key = '_stock' THEN pm.meta_value END) AS stock,
               MAX(CASE WHEN pm.meta_key = '_regular_price' THEN pm.meta_value END) AS regular_price
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
        WHERE p.post_type IN ('product', 'product_variation')
          AND p.post_status IN ('publish', 'private')
          AND pm.meta_key IN ('_sku', '_stock_status', '_stock', '_regular_price')
        GROUP BY p.ID
    ");

    $atRisk = [];
    foreach ($rows as $r) {
        $sku = trim((string) $r->sku);
        if ($sku === '' || isset($feedSkus[$sku])) {
            continue;
        }
        // Doar cele chiar comandabile - "outofstock" deja e blocat la
        // adaugare in cos, nu reprezinta risc real.
        if (!in_array($r->stock_status, ['instock', 'onbackorder'], true)) {
            continue;
        }

        $atRisk[] = [
            'post_id' => (int) $r->ID,
            'post_type' => $r->post_type,
            'sku' => $sku,
            'name' => get_the_title($r->ID),
            'type' => $r->post_type === 'product_variation' ? 'variantă' : 'produs',
            'stock' => $r->stock !== null && $r->stock !== '' ? (int) $r->stock : null,
            'price' => $r->regular_price !== '' ? $r->regular_price : null,
        ];
    }

    return $atRisk;
}

/**
 * Muta automat in ciorna orice produs/varianta INCA comandabil(a) al carui
 * SKU a disparut din feedul curent Aperta - decizie user 2026-09-04: nu
 * doar raportare (vezi papetarie_storefront_aperta_products_missing_from_feed_but_orderable()
 * mai sus), ci inchiderea efectiva a posibilitatii de comanda, automat,
 * fara sa astepte verificarea manuala. Lasa o nota vizibila pe fiecare
 * produs atins (comentariu WP standard, vizibil in Comentarii din admin) si
 * retine lista intr-o optiune capata, citita si golita de digest-ul de la
 * 18:00 (vezi papetarie_storefront_aperta_send_restock_digest_cb()).
 *
 * De ce "draft" si nu doar "stoc epuizat": la o VARIANTA, WooCommerce
 * verifica si post_status in variation_is_visible() - o varianta cu status
 * 'draft' dispare complet din selectorul de pe pagina de produs, nu doar
 * apare "indisponibila". La un produs simplu/parinte, e acelasi
 * comportament ca la orice alt produs pus manual pe ciorna.
 *
 * @return array<int, array{post_id:int, sku:string, name:string, type:string}>
 */
function papetarie_storefront_aperta_auto_draft_vanished_products(): array
{
    $candidates = papetarie_storefront_aperta_products_missing_from_feed_but_orderable();
    if (empty($candidates)) {
        return [];
    }

    $drafted = [];
    $autoDraftedToday = get_option('pap_auto_drafted_today', []);
    if (!is_array($autoDraftedToday)) {
        $autoDraftedToday = [];
    }

    foreach ($candidates as $item) {
        $product = wc_get_product($item['post_id']);
        if (!($product instanceof WC_Product)) {
            continue;
        }

        $product->set_stock_quantity(0);
        $product->set_stock_status('outofstock');
        $product->save();

        wp_update_post(['ID' => $item['post_id'], 'post_status' => 'draft']);

        $note = sprintf(
            'Mutat automat în ciornă de sincronizarea Aperta pe %s: SKU %s nu mai apare deloc în feedul curent Aperta (probabil produsul a fost scos din oferta lor). Verifică manual dacă mai există la Aperta înainte să-l republici.',
            date('d.m.Y H:i'),
            $item['sku']
        );
        papetarie_storefront_aperta_note_product($item['post_id'], $note);
        // Comentariul WP e o urma persistenta, dar WooCommerce redenumeste
        // cutia de comentarii in "Reviews" pe ecranul de editare produs -
        // usor de ratat, se amesteca vizual cu recenzii reale de clienti
        // (gasit prin intrebarea directa a user-ului 2026-09-04, dupa un
        // test real). Metadata de mai jos alimenteaza un banner vizibil sus
        // pe pagina - vezi papetarie_storefront_aperta_vanished_notice().
        update_post_meta($item['post_id'], '_pap_aperta_vanished_note', $note);

        $entry = [
            'post_id' => $item['post_id'],
            'sku' => $item['sku'],
            'name' => $item['name'],
            'type' => $item['type'],
        ];
        $drafted[] = $entry;
        $autoDraftedToday[$item['post_id']] = $entry;
    }

    update_option('pap_auto_drafted_today', $autoDraftedToday, false);

    return $drafted;
}

/**
 * Adauga un comentariu WP standard (vizibil in Comentarii din admin,
 * filtrat pe acel produs) - urma persistenta, nu doar emailul de moment.
 */
function papetarie_storefront_aperta_note_product(int $postId, string $note): void
{
    wp_insert_comment([
        'comment_post_ID' => $postId,
        'comment_content' => $note,
        'comment_author' => 'Sincronizare Aperta',
        'comment_author_email' => '',
        'comment_type' => '',
        'comment_approved' => 1,
        'user_id' => 0,
    ]);
}

/**
 * Banner vizibil, sus pe ecranul de editare produs, cu motivul mutarii
 * automate in ciorna (vezi meta '_pap_aperta_vanished_note' mai sus) - nu
 * doar comentariul din cutia "Reviews", usor de ratat.
 */
function papetarie_storefront_aperta_vanished_notice(): void
{
    global $pagenow, $post;

    if ($pagenow !== 'post.php' || !($post instanceof WP_Post) || $post->post_type !== 'product') {
        return;
    }

    $note = get_post_meta($post->ID, '_pap_aperta_vanished_note', true);
    if ($note === '') {
        return;
    }

    echo '<div class="notice notice-warning"><p><strong>De ce e acest produs în ciornă:</strong> '
        . esc_html($note)
        . '</p></div>';
}
add_action('admin_notices', 'papetarie_storefront_aperta_vanished_notice');

/**
 * Curata meta banner-ului de mai sus cand un admin republica manual
 * produsul - altfel avertismentul ar ramane afisat la nesfarsit, chiar si
 * dupa ce problema a fost rezolvata.
 */
function papetarie_storefront_aperta_clear_vanished_notice(int $postId): void
{
    if (get_post_meta($postId, '_pap_aperta_vanished_note', true) !== '') {
        delete_post_meta($postId, '_pap_aperta_vanished_note');
    }
}
add_action('publish_product', 'papetarie_storefront_aperta_clear_vanished_notice');

/**
 * Callback pentru cron-ul de la 01:30 - ruleaza auto-draft-ul si trimite
 * imediat un email (nu asteapta digest-ul de la 18:00), cu formularea
 * ceruta de user: "am mutat in draft acest produs pentru ca nu mai apare
 * in feedul de la Aperta".
 */
function papetarie_storefront_aperta_auto_draft_vanished_cb(): void
{
    $drafted = papetarie_storefront_aperta_auto_draft_vanished_products();
    if (empty($drafted)) {
        return;
    }

    $lines = [];
    foreach ($drafted as $item) {
        $lines[] = sprintf(
            "- [%s] %s (SKU %s)\n  Am mutat în draft acest produs pentru că nu mai apare în feedul de la Aperta.\n  %s",
            $item['type'],
            $item['name'],
            $item['sku'],
            admin_url('post.php?post=' . $item['post_id'] . '&action=edit')
        );
    }

    $to = ['d.crysty23@gmail.com', 'laviniamuntean40@gmail.com'];
    $subject = sprintf('[Notix] %d produse mutate automat în ciornă (dispărute din feedul Aperta)', count($drafted));
    $body = "Sincronizarea a găsit produse/variante încă în stoc pe site, al căror SKU nu mai apare deloc în feedul curent Aperta.\n"
        . "Au fost mutate automat în ciornă (nu mai pot fi comandate) și marcate cu o notă pe fiecare produs.\n\n"
        . implode("\n\n", $lines)
        . "\n\nVerifică manual fiecare - dacă produsul chiar mai există la Aperta, poți să-l republici.";

    wp_mail($to, $subject, $body);
}
add_action('pap_aperta_auto_draft_vanished', 'papetarie_storefront_aperta_auto_draft_vanished_cb');

function papetarie_storefront_aperta_feed_dir(): string
{
    $upload = wp_upload_dir();

    return trailingslashit($upload['basedir']) . 'aperta-sync';
}

function papetarie_storefront_aperta_feed_path(string $which): string
{
    return papetarie_storefront_aperta_feed_dir() . '/' . $which . '.csv';
}

function papetarie_storefront_aperta_download_feed(string $which): bool
{
    $url = $which === 'stoc' ? PAP_APERTA_STOCK_FEED_URL : PAP_APERTA_PRODUCTS_FEED_URL;

    $response = wp_remote_get(
        $url,
        [
            'timeout' => 120,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
            ],
        ]
    );

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return false;
    }

    $dir = papetarie_storefront_aperta_feed_dir();
    wp_mkdir_p($dir);

    $body = wp_remote_retrieve_body($response);

    return (bool) file_put_contents(papetarie_storefront_aperta_feed_path($which), $body);
}

/**
 * Citeste feed.csv si grupeaza randurile dupa "Cod produs" (parintele
 * unui produs variabil). Pastreaza ordinea de aparitie a codurilor.
 *
 * @return array<string, array<int, array<string, string>>>
 */
function papetarie_storefront_aperta_read_products_grouped(): array
{
    $path = papetarie_storefront_aperta_feed_path('feed');
    $grouped = [];

    $handle = fopen($path, 'r');
    if ($handle === false) {
        return $grouped;
    }

    // BOM-ul UTF-8 de la inceputul fisierului strica citirea primei coloane
    // din antet daca ajunge la fgetcsv (nu mai recunoaste ghilimeaua de start).
    if (fread($handle, 3) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return $grouped;
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header)) {
            continue;
        }

        $assoc = array_combine($header, $row);
        $code = trim((string) ($assoc['Cod produs'] ?? ''));

        if ($code === '') {
            continue;
        }

        // Fara nicio poza - nu aducem randul deloc (nici macar in rapoarte),
        // la cererea explicita a userului 2026-07-28.
        if (papetarie_storefront_aperta_image_urls((string) ($assoc['Imagine produs'] ?? '')) === []) {
            continue;
        }

        // Categorii excluse definitiv de la import (Molotow, Universul
        // copiilor) - verificam segmentul de top de pe calea RAW din feed
        // ("Categorie produs"), inainte de orice mapare a noastra. Molotow
        // in special nu are propria categorie de top pe site (se mapeaza in
        // subcategorii sub "Arta"), deci excluderea trebuie facuta aici, pe
        // datele brute din feed, nu pe taxonomia noastra rezolvata.
        $categorySegments = array_map('trim', explode('>', (string) ($assoc['Categorie produs'] ?? '')));
        if (in_array($categorySegments[0] ?? '', PAP_APERTA_EXCLUDED_TOP_LEVEL_CATEGORIES, true)) {
            continue;
        }

        // Sub-categorii excluse (nu sunt segmentul 0) - verificam slug-ul
        // fiecarui segment, nu doar continutul brut, ca sa nu ratam din
        // cauza vreunei diferente de diacritice fata de numele site-ului.
        $isExcludedSubcategory = false;
        foreach ($categorySegments as $segment) {
            if (in_array(sanitize_title($segment), PAP_APERTA_EXCLUDED_SUBCATEGORY_SLUGS, true)) {
                $isExcludedSubcategory = true;
                break;
            }
        }
        if ($isExcludedSubcategory) {
            continue;
        }

        $grouped[$code][] = $assoc;
    }

    fclose($handle);

    // Ordinea conteaza doar in masura in care cele doua consolidari vizeaza
    // seturi disjuncte de randuri (Variant gol vs Variant completat), deci
    // nu se calca reciproc pe picioare.
    $grouped = papetarie_storefront_aperta_consolidate_by_shared_link($grouped);

    return papetarie_storefront_aperta_consolidate_singleton_colors($grouped);
}

/**
 * Cache pe disc al rezultatului (deja grupat + consolidat) al functiei de
 * mai sus - folosit de lantul de bucati Action Scheduler, ca sa nu se mai
 * re-parseze feed.csv (5341 randuri) + regex-ul de consolidare culori la
 * FIECARE bucata (~330 re-parsari redundante pe o rulare completa, cel mai
 * mare consumator de timp gasit la investigatia din 2026-07-28). Scris o
 * singura data la pornirea sincronizarii (sync_products_start_cb), citit de
 * fiecare bucata in loc de reparsare.
 */
function papetarie_storefront_aperta_products_grouped_cache_path(): string
{
    return papetarie_storefront_aperta_feed_dir() . '/grouped-cache.php';
}

function papetarie_storefront_aperta_cache_products_grouped(array $grouped): void
{
    file_put_contents(papetarie_storefront_aperta_products_grouped_cache_path(), serialize($grouped));
}

function papetarie_storefront_aperta_read_products_grouped_cached(): array
{
    $path = papetarie_storefront_aperta_products_grouped_cache_path();

    if (is_file($path)) {
        $raw = file_get_contents($path);
        if ($raw !== false) {
            $data = @unserialize($raw, ['allowed_classes' => false]);
            if (is_array($data)) {
                return $data;
            }
        }
    }

    // Plasa de siguranta (cache lipsa/corupt, ex. rulare manuala din
    // tools/sync-aperta-feed.php care nu trece prin start_cb) - re-parsam
    // din feed, mai lent dar corect.
    return papetarie_storefront_aperta_read_products_grouped();
}

/**
 * Lista de culori cunoscute (fara diacritice, litere mici) pentru detectarea
 * unui sufix de culoare la finalul denumirii unui produs.
 *
 * @return array<int, string>
 */
function papetarie_storefront_aperta_color_words(): array
{
    return [
        'negru', 'alb', 'rosu', 'roz', 'albastru', 'bleu', 'bleumarin', 'verde',
        'vernil', 'galben', 'portocaliu', 'mov', 'gri', 'maro', 'bej', 'auriu',
        'argintiu', 'turcoaz', 'crem', 'multicolor',
    ];
}

/**
 * Elimina diacriticele romanesti frecvente, pentru comparatii tolerante.
 */
function papetarie_storefront_aperta_strip_diacritics(string $text): string
{
    $map = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
    ];

    return strtr($text, $map);
}

/**
 * Incearca sa detecteze si sa elimine un cuvant de culoare de la finalul unei
 * denumiri de produs (cu sau fara virgula inainte). Returneaza numele fara
 * sufix si culoarea detectata (cu litera mare), sau null daca numele nu se
 * termina cu un cuvant de culoare cunoscut.
 *
 * @return array{base: string, color: string}|null
 */
function papetarie_storefront_aperta_strip_color_suffix(string $name): ?array
{
    $trimmed = trim($name);
    if ($trimmed === '') {
        return null;
    }

    if (!preg_match('/^(.*?),?\s+([A-Za-zĂÂÎȘȚăâîșț]+)$/u', $trimmed, $matches)) {
        return null;
    }

    $base = trim($matches[1]);
    $lastWord = $matches[2];
    $normalized = strtolower(papetarie_storefront_aperta_strip_diacritics($lastWord));

    if ($base === '' || !in_array($normalized, papetarie_storefront_aperta_color_words(), true)) {
        return null;
    }

    return [
        'base' => $base,
        'color' => mb_convert_case(mb_strtolower($lastWord), MB_CASE_TITLE),
    ];
}

/**
 * Cheie sintetica, stabila intre rulari, pentru un grup unificat de produse
 * multi-culoare (nu depinde de codurile originale din feed, doar de nume+brand,
 * ca sa recunoasca acelasi grup la fiecare sincronizare viitoare).
 */
function papetarie_storefront_aperta_synthetic_group_key(string $baseName, string $brandName): string
{
    return 'merged-' . sanitize_title($baseName . '-' . $brandName);
}

/**
 * Consolideaza randurile "singleton" (un singur rand, fara Variant) care par
 * sa fie de fapt culori diferite ale aceluiasi produs, dupa nume+brand -
 * previne ca Aperta sa creeze produse simple separate per culoare cand
 * feed-ul nu le leaga printr-un "Cod produs" comun.
 *
 * @param array<string, array<int, array<string, string>>> $grouped
 * @return array<string, array<int, array<string, string>>>
 */
function papetarie_storefront_aperta_consolidate_singleton_colors(array $grouped): array
{
    $clusters = [];

    foreach ($grouped as $code => $rows) {
        if (count($rows) !== 1) {
            continue;
        }

        $row = $rows[0];
        if (trim((string) ($row['Variant'] ?? '')) !== '') {
            continue;
        }

        $name = trim((string) ($row['Denumire produs'] ?? ''));
        $stripped = papetarie_storefront_aperta_strip_color_suffix($name);
        if ($stripped === null) {
            continue;
        }

        $brandName = trim((string) ($row['Brand produs'] ?? ''));
        $clusterKey = papetarie_storefront_aperta_synthetic_group_key($stripped['base'], $brandName);

        $clusters[$clusterKey][] = ['code' => $code, 'color' => $stripped['color']];
    }

    foreach ($clusters as $clusterKey => $members) {
        if (count($members) < 2) {
            continue;
        }

        $mergedRows = [];
        foreach ($members as $member) {
            $row = $grouped[$member['code']][0];
            $row['Variant'] = $member['color'];
            $row['Tip variant'] = 'Culoare';
            $row['Cod produs'] = $clusterKey;
            $mergedRows[] = $row;
            unset($grouped[$member['code']]);
        }

        $grouped[$clusterKey] = $mergedRows;
    }

    return $grouped;
}

/**
 * Consolideaza randurile "singleton" (Cod produs unic, un singur rand) care
 * au totusi "Variant"/"Tip variant" completate de Aperta, dar impart acelasi
 * "Link produs" (pagina de produs de pe site-ul Aperta) - semn explicit ca
 * Aperta insusi le trateaza ca UN produs cu mai multe culori/marimi, chiar
 * daca exportul CSV le da coduri de produs separate (confirmat pe feed
 * 2026-07-28: ex. "One4All 627HS 15mm" - 18 culori, acelasi Link produs,
 * Cod produs diferit per culoare).
 *
 * Apelata automat din read_products_grouped() (deci si de sincronizarea
 * zilnica, nu doar de tools/consolidate-color-families.php). Aceasta functie
 * NU verifica ea insasi statusul produselor din baza de date - siguranta
 * vine din doua straturi mai jos in lant: sync_variations() sare (nu
 * atinge) orice rand al carui SKU e deja un produs SIMPLU existent (vezi
 * "Skip (don't crash) when a variation SKU already belongs to a simple
 * product"), iar upsert_product() sterge imediat parintele nou-creat daca
 * TOTI membrii au fost sariti asa (ramane 0 variatii) - altfel ar ramane o
 * coaja goala, fara poza, ca cele 15 gasite si curatate manual 2026-07-28.
 *
 * Grupeaza DOAR daca toate randurile clusterului au: acelasi nume, acelasi
 * brand, acelasi "Tip variant" (nevid) si "Variant" completat pe fiecare
 * rand - orice abatere de la asta inseamna ca nu e sigur si le lasam
 * neatinse (raportate separat ca ambigue).
 *
 * @param array<string, array<int, array<string, string>>> $grouped
 * @return array<string, array<int, array<string, string>>>
 */
function papetarie_storefront_aperta_consolidate_by_shared_link(array $grouped): array
{
    $byLink = [];

    foreach ($grouped as $code => $rows) {
        if (count($rows) !== 1) {
            continue;
        }

        $link = trim((string) ($rows[0]['Link produs'] ?? ''));
        if ($link === '') {
            continue;
        }

        $byLink[$link][] = $code;
    }

    foreach ($byLink as $link => $codes) {
        if (count($codes) < 2) {
            continue;
        }

        $rows = array_map(static fn ($code) => $grouped[$code][0], $codes);

        $names = array_unique(array_map(static fn ($r) => trim((string) $r['Denumire produs']), $rows));
        $brands = array_unique(array_map(static fn ($r) => trim((string) $r['Brand produs']), $rows));
        $tipVariants = array_unique(array_map(static fn ($r) => trim((string) $r['Tip variant']), $rows));
        $variantsFilled = array_filter($rows, static fn ($r) => trim((string) $r['Variant']) !== '');

        $safe = count($names) === 1
            && count($brands) === 1
            && count($tipVariants) === 1
            && $tipVariants[0] !== ''
            && count($variantsFilled) === count($rows);

        if (!$safe) {
            continue;
        }

        $clusterKey = 'link-' . md5($link);
        $mergedRows = [];
        foreach ($codes as $code) {
            $row = $grouped[$code][0];
            // Esential: fara asta, "Cod produs" ramane cel individual al
            // fiecarui membru (ex. codul propriu al culorii Rosu) - upsert
            // l-ar folosi ca sa caute produsul-parinte si ar gasi/rescrie
            // din greseala parintele existent, deja publicat, al ACELUI
            // membru, in loc sa trateze grupul ca familie noua. Gasit live
            // 2026-07-28 (lavete Kimberly-Clark).
            $row['Cod produs'] = $clusterKey;
            $mergedRows[] = $row;
            unset($grouped[$code]);
        }

        $grouped[$clusterKey] = $mergedRows;
    }

    return $grouped;
}

/**
 * @return array<string, array{stock: int, cod_produs: string}>
 */
function papetarie_storefront_aperta_read_stock_map(): array
{
    $path = papetarie_storefront_aperta_feed_path('stoc');
    $map = [];

    $handle = fopen($path, 'r');
    if ($handle === false) {
        return $map;
    }

    if (fread($handle, 3) !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return $map;
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($header)) {
            continue;
        }

        $assoc = array_combine($header, $row);
        $codUnic = trim((string) ($assoc['Cod unic'] ?? ''));

        if ($codUnic === '') {
            continue;
        }

        $map[$codUnic] = [
            'stock' => (int) ($assoc['Stoc'] ?? 0),
            'cod_produs' => trim((string) ($assoc['Cod produs'] ?? '')),
        ];
    }

    fclose($handle);

    return $map;
}

/**
 * Mapare explicita a arborilor top-level din feed catre slug-urile deja
 * seedate. Orice nume care nu e in lista devine un termen top-level nou
 * (ex. "Bagajerie", "Universul copiilor").
 */
function papetarie_storefront_aperta_top_level_map(): array
{
    return [
        'Articole din hârtie' => 'articole-din-hartie',
        'Articole școlare' => 'articole-scolare',
        'Articole pentru birou' => 'articole-pentru-birou',
        'Scris și accesorii' => 'accesorii-pentru-scris',
        'Organizare, arhivare, prezentare' => 'organizare-arhivare-prezentare',
        'Ambalare și etichetare' => 'organizare-arhivare-prezentare',
        'Artă' => 'arta',
        'Molotow' => 'arta',
        'Creativitate' => 'creativitate',
        'Periferice' => 'periferice',
        'Curățenie și sanitare' => 'curatenie-si-sanitare',
    ];
}

/**
 * Rezolva "Categorie produs" (ex. "Organizare, arhivare, prezentare>Bibliorafturi")
 * la un term_id product_cat existent - categoriile site-ului sunt curatoriate
 * manual (fixe), deci NU se creeaza niciodata una noua; daca un segment nu
 * are corespondent, ne oprim la ultima categorie reala gasita (vezi
 * papetarie_storefront_aperta_log_unmatched_category()).
 */
/**
 * Categoriile site-ului sunt curatoriate manual (fixe) - sincronizarea NU
 * trebuie sa creeze niciodata categorii noi, doar sa asigneze produse in
 * cele deja existente. Gasit live 2026-07-28: cand un segment din calea de
 * categorie a feed-ului nu se potrivea exact cu vreun copil existent sub
 * parintele curent, codul (versiunea veche) crea un termen nou - iar cum
 * WordPress cere sloguri unice global (nu doar per-parinte), acelasi nume
 * generic ("Accesorii", "Linere", "Pixuri cu gel"...) aparut sub mai multi
 * parinti diferiti a produs zeci de categorii-duplicat numerotate
 * (accesorii-creativitate-2, -3, ... -10), fiecare cu un singur produs.
 *
 * Acum: daca un segment nu se potriveste cu niciun copil existent, ne oprim
 * din coborat si folosim ultimul parinte gasit (produsul ramane asignat
 * celei mai apropiate categorii reale, nu se creeaza nimic nou). Daca nici
 * segmentul de top nu se potriveste cu nimic existent, produsul ramane
 * neasignat (0) - se logheaza separat pentru revizuire manuala, nu se
 * ghiceste o categorie noua.
 */
function papetarie_storefront_aperta_resolve_category(string $feedCategoryPath): int
{
    $segments = array_map('trim', explode('>', $feedCategoryPath));
    $segments = array_values(array_filter($segments, static fn (string $s): bool => $s !== ''));

    if (empty($segments)) {
        return 0;
    }

    $topLevelName = $segments[0];
    $map = papetarie_storefront_aperta_top_level_map();
    $topLevelSlug = $map[$topLevelName] ?? sanitize_title($topLevelName);

    $topTerm = get_term_by('slug', $topLevelSlug, 'product_cat');
    if (!($topTerm instanceof WP_Term)) {
        papetarie_storefront_aperta_log_unmatched_category($feedCategoryPath, $topLevelName);

        return 0;
    }

    $parentId = (int) $topTerm->term_id;

    // Molotow>Artist>Markere -> subcategorie noua "Artist Markere" sub "arta".
    $subSegments = array_slice($segments, 1);
    if ($topLevelName === 'Molotow') {
        $subSegments = [implode(' ', $subSegments)];
    }

    $currentParentId = $parentId;

    foreach ($subSegments as $subName) {
        $subSlug = sanitize_title($subName);
        $children = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $currentParentId,
        ]);

        $match = null;
        if (!is_wp_error($children)) {
            foreach ($children as $child) {
                if ($child->slug === $subSlug) {
                    $match = $child;
                    break;
                }
            }
        }

        if (!($match instanceof WP_Term)) {
            papetarie_storefront_aperta_log_unmatched_category($feedCategoryPath, $subName);
            break;
        }

        $currentParentId = (int) $match->term_id;
    }

    return $currentParentId;
}

/**
 * Salveaza in optiunea pap_aperta_unmatched_categories orice cale de
 * categorie din feed care nu are corespondent exact in taxonomia noastra
 * (dupa segmentul care s-a oprit), pentru revizuire manuala ulterioara.
 */
function papetarie_storefront_aperta_log_unmatched_category(string $feedCategoryPath, string $missingSegment): void
{
    $log = get_option('pap_aperta_unmatched_categories', []);
    $key = $feedCategoryPath;

    if (isset($log[$key])) {
        $log[$key]['count']++;
        $log[$key]['last_seen'] = current_time('mysql');
    } else {
        $log[$key] = [
            'feed_path' => $feedCategoryPath,
            'missing_segment' => $missingSegment,
            'count' => 1,
            'last_seen' => current_time('mysql'),
        ];
    }

    update_option('pap_aperta_unmatched_categories', $log, false);
}

/**
 * Cand Aperta trimite "Brand produs" gol dar denumirea produsului contine
 * clar un brand cunoscut (ex. "Pix Schneider K1", "Pix ICO Metal" - gasit
 * 2026-09-06 la 4 produse din Pixuri cu pasta, verificat direct in feed ca
 * Aperta chiar trimite campul gol pentru ele, desi la alte produse Schneider/
 * ICO il completeaza corect), incercam sa ghicim brandul din nume - dar DOAR
 * dintre brandurile deja existente in taxonomia noastra (product_brand), ca
 * sa nu inventam brand-uri noi dintr-un cuvant oarecare din titlu. Potrivire
 * pe granita de cuvant (nu substring brut), altfel un brand scurt gen "M" ar
 * "gasi" potriviri false peste tot. Decizie user 2026-09-06.
 */
function papetarie_storefront_aperta_guess_brand_from_name(string $name): string
{
    static $knownBrands = null;

    if ($knownBrands === null) {
        $knownBrands = [];
        foreach (get_terms(['taxonomy' => 'product_brand', 'hide_empty' => false]) as $term) {
            if ($term instanceof WP_Term && mb_strlen($term->name) >= 3) {
                $knownBrands[] = $term->name;
            }
        }
        // brandurile mai lungi primele, ca "Erich Krause" sa fie gasit
        // inaintea unui eventual brand mai scurt continut in el.
        usort($knownBrands, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
    }

    foreach ($knownBrands as $brand) {
        if (preg_match('/\b' . preg_quote($brand, '/') . '\b/iu', $name) === 1) {
            return $brand;
        }
    }

    return '';
}

function papetarie_storefront_aperta_resolve_brand(string $brandName): int
{
    $brandName = trim($brandName);

    if ($brandName === '') {
        return 0;
    }

    $slug = sanitize_title($brandName);
    $existing = get_term_by('slug', $slug, 'product_brand');

    if ($existing instanceof WP_Term) {
        return (int) $existing->term_id;
    }

    $created = wp_insert_term($brandName, 'product_brand', ['slug' => $slug]);

    if (is_wp_error($created)) {
        $byName = get_term_by('name', $brandName, 'product_brand');
        return $byName instanceof WP_Term ? (int) $byName->term_id : 0;
    }

    return (int) $created['term_id'];
}

/**
 * Descarca (daca lipseste deja) o imagine externa si o ataseaza produsului.
 * Dedup prin postmeta _pap_aperta_image_source, ca sa nu redescarce aceeasi
 * poza la fiecare sincronizare zilnica.
 */
/**
 * Termen-limita dur (microtime absolut) pentru descarcarea de imagini,
 * setat doar de bucata cronulu de produse - fara argument = citire, cu
 * argument = scriere (inclusiv null, ca sa resetam la iesirea din bucata).
 * Un singur produs cu o galerie mare de poze (fiecare pana la 20s) putea
 * singur sa impinga bucata peste pragul de 300s la care Action Scheduler
 * marcheaza automat actiunea esuata (vezi comentariul din
 * sync_products_chunk_cb) - verificarea de buget dintre produse nu ajuta
 * daca UN produs consuma tot bugetul intre doua verificari.
 */
function papetarie_storefront_aperta_sideload_deadline(?float $newDeadline = null): ?float
{
    static $deadline = null;
    if (func_num_args() > 0) {
        $deadline = $newDeadline;
    }
    return $deadline;
}

function papetarie_storefront_aperta_sideload_image(string $url, int $productId): ?int
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $deadline = papetarie_storefront_aperta_sideload_deadline();
    if ($deadline !== null && microtime(true) >= $deadline) {
        // Bugetul de timp al bucatii e epuizat - sarim peste imaginile
        // ramase (se recupereaza la urmatoarea sincronizare) in loc sa
        // riscam sa impingem intregul proces peste pragul extern de 300s.
        return null;
    }

    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'any',
        'meta_key' => '_pap_aperta_image_source',
        'meta_value' => $url,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    // Nu e suficient ca inregistrarea de attachment sa existe in baza de date -
    // pe un mediu restaurat dintr-un export SQL fara wp-content/uploads (ex.
    // clona locala a unui coleg), postmeta-ul de dedup vine din dump, dar
    // fisierul fizic lipseste. Fara verificarea asta, sideload-ul s-ar opri
    // aici crezand ca poza exista deja si n-ar mai descarca niciodata nimic.
    if (!empty($existing)) {
        $existingId = (int) $existing[0];
        $attachedFile = get_attached_file($existingId);
        if ($attachedFile && file_exists($attachedFile)) {
            return $existingId;
        }

        // Fisierul fizic lipseste (tipic dupa o restaurare dintr-un export SQL
        // fara wp-content/uploads) - scoatem marcajul de dedup de pe randul
        // orfan (nu stergem inregistrarea in sine), altfel urmatoarea
        // sincronizare l-ar gasi din nou, tot fara fisier, la infinit.
        delete_post_meta($existingId, '_pap_aperta_image_source', $url);
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // media_sideload_image() nu accepta un timeout explicit - foloseste
    // implicit 300s (download_url()), acelasi prag la care Action Scheduler
    // marcheaza automat actiunea "esuata". O singura poza care raspunde lent
    // de pe serverul Aperta putea astfel bloca toata bucata pana exact la
    // acel prag (confirmat live pe staging 2026-07-28: o bucata a esuat dupa
    // exact 300s, fara nicio alta explicatie in loguri). Plafonam timeout-ul
    // la 20s cat timp e activa cererea asta - suficient pentru o poza produs
    // normala, dar nu lasa un singur raspuns lent sa opreasca tot importul.
    $capTimeout = static function (array $args) {
        $args['timeout'] = min($args['timeout'] ?? 20, 20);
        // Serverul Aperta respinge cu "403 Forbidden" cererile fara un
        // User-Agent de browser (acelasi motiv pentru care descarcarea
        // feed-ului insusi seteaza unul mai jos) - fara asta, WordPress
        // trimite implicit un User-Agent generic si o parte din poze pica
        // silentios cu 403 in loc sa se descarce, chiar daca alte poze ale
        // aceluiasi produs, cerute in acelasi lot, trec (protectia lor pare
        // sa fie inconsistenta/pe bursturi, nu un blocaj total). Confirmat
        // live 2026-08-09: "stilou-schneider-688-verde.jpg" respinsa cu 403
        // fara User-Agent, 200 OK cu unul de browser.
        $args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
        return $args;
    };
    add_filter('http_request_args', $capTimeout);
    $attachmentId = media_sideload_image($url, $productId, null, 'id');
    remove_filter('http_request_args', $capTimeout);

    if (is_wp_error($attachmentId)) {
        return null;
    }

    update_post_meta((int) $attachmentId, '_pap_aperta_image_source', $url);

    return (int) $attachmentId;
}

function papetarie_storefront_aperta_normalize_name(string $name): string
{
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
}

/**
 * Coloana "Imagine produs" poate contine mai multe URL-uri despartite prin
 * "|" (galerie) - prima e imaginea principala, restul intra in galerie.
 *
 * @return array<int, string>
 */
function papetarie_storefront_aperta_image_urls(string $raw): array
{
    $parts = array_map('trim', explode('|', $raw));

    return array_values(array_filter($parts, static fn (string $url): bool => $url !== ''));
}

/**
 * Descarca toate imaginile unei liste de URL-uri si returneaza ID-urile de
 * attachment (in ordinea din feed).
 *
 * @param array<int, string> $urls
 * @return array<int, int>
 */
function papetarie_storefront_aperta_sideload_images(array $urls, int $productId): array
{
    $ids = [];
    foreach ($urls as $url) {
        $id = papetarie_storefront_aperta_sideload_image($url, $productId);
        if ($id !== null) {
            $ids[] = $id;
        }
    }

    return $ids;
}

/**
 * Amprenta de continut a unui attachment - Aperta uneste uneori acelasi
 * fisier de imagine sub URL-uri diferite (ex. aceeasi coperta de produs
 * folosita ca a treia poza la doua variante de culoare diferite) - dedup-ul
 * existent, dupa URL, nu prinde asta fiindca URL-urile chiar sunt diferite.
 * Amprenta se calculeaza o singura data (cache in postmeta), nu la fiecare
 * sincronizare.
 */
function papetarie_storefront_aperta_image_content_hash(int $attachmentId): ?string
{
    $cached = get_post_meta($attachmentId, '_pap_aperta_image_hash', true);
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }

    $path = get_attached_file($attachmentId);
    if (!$path || !file_exists($path)) {
        return null;
    }

    $hash = md5_file($path);
    if ($hash === false) {
        return null;
    }

    update_post_meta($attachmentId, '_pap_aperta_image_hash', $hash);

    return $hash;
}

/**
 * Elimina duplicatele DUPA CONTINUT dintr-o lista ordonata de attachment
 * ID-uri, pastrand prima aparitie - folosit doar pentru galeria comuna a
 * unui produs (thumbnail-ul fiecarei variatii ramane neatins, ca fiecare
 * varianta sa aiba tot o poza chiar daca e identica cu a alteia).
 *
 * @param array<int, int> $ids
 * @return array<int, int>
 */
function papetarie_storefront_aperta_dedupe_image_ids(array $ids): array
{
    $seenHashes = [];
    $deduped = [];

    foreach ($ids as $id) {
        $hash = papetarie_storefront_aperta_image_content_hash($id);
        if ($hash !== null && isset($seenHashes[$hash])) {
            continue;
        }
        if ($hash !== null) {
            $seenHashes[$hash] = true;
        }
        $deduped[] = $id;
    }

    return $deduped;
}

// NOTA: 'any' in WP_Query/get_posts EXCLUDE 'trash' (particularitate WP,
// nu include tot ce pare "orice status"). Includem explicit 'trash' aici,
// altfel sincronizarea nu vede produsele excluse manual (curatenia facuta
// impreuna cu Lavinia) si le recreeaza ca produse noi la fiecare rulare -
// gasit 2026-07-27 chiar inainte de cronul de noapte.
const PAP_APERTA_ALL_STATUSES = ['publish', 'draft', 'pending', 'private', 'future', 'trash'];

/**
 * Preincarca TOATE perechile SKU/Cod produs -> post ID intr-o singura
 * interogare, cache-uita static (o data per proces/bucata) - inlocuieste
 * cate un get_posts() individual per produs (N+1), care era al treilea cel
 * mai mare consumator de timp gasit la investigatia din 2026-07-28. Nu
 * filtram dupa post_status: tabela wp_posts contine oricum toate starile
 * (inclusiv trash), la fel cum facea si get_posts() cu PAP_APERTA_ALL_STATUSES.
 *
 * @return array{sku: array<string, int>, cod_produs: array<string, int>}
 */
function papetarie_storefront_aperta_lookup_maps(): array
{
    static $maps = null;

    if ($maps !== null) {
        return $maps;
    }

    global $wpdb;

    $skuToId = [];
    $codProdusToId = [];

    $rows = $wpdb->get_results(
        "SELECT pm.meta_key, pm.meta_value, pm.post_id
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key IN ('_pap_aperta_sku', '_pap_aperta_cod_produs')
         AND p.post_type IN ('product', 'product_variation')
         ORDER BY p.ID DESC"
    );

    foreach ($rows as $row) {
        $value = trim((string) $row->meta_value);
        if ($value === '') {
            continue;
        }

        // ORDER BY p.ID DESC + "seteaza doar daca lipseste" => in caz de
        // coliziune (nu ar trebui sa existe) pastram cel mai recent post,
        // acelasi comportament ca get_posts() implicit (orderby date DESC).
        if ($row->meta_key === '_pap_aperta_sku') {
            if (!isset($skuToId[$value])) {
                $skuToId[$value] = (int) $row->post_id;
            }
        } elseif (!isset($codProdusToId[$value])) {
            $codProdusToId[$value] = (int) $row->post_id;
        }
    }

    $maps = ['sku' => $skuToId, 'cod_produs' => $codProdusToId];

    return $maps;
}

function papetarie_storefront_aperta_find_by_sku_meta(string $codUnic): ?int
{
    $maps = papetarie_storefront_aperta_lookup_maps();

    return $maps['sku'][$codUnic] ?? null;
}

function papetarie_storefront_aperta_find_parent_by_cod_produs(string $codProdus): ?int
{
    $maps = papetarie_storefront_aperta_lookup_maps();

    return $maps['cod_produs'][$codProdus] ?? null;
}

/**
 * Cauta un produs vechi (importat din JSON, fara SKU) dupa numele exact
 * normalizat, ca sa il "upgradam" in loc sa creem un duplicat.
 */
function papetarie_storefront_aperta_find_legacy_product_by_name(string $name): ?int
{
    global $wpdb;

    $normalized = papetarie_storefront_aperta_normalize_name($name);
    if ($normalized === '') {
        return null;
    }

    $candidates = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.ID, p.post_title FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type = 'product' AND p.post_status IN ('publish','draft','pending')
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->postmeta} pm2
                 WHERE pm2.post_id = p.ID AND pm2.meta_key = '_pap_aperta_sku'
             )",
            '_pap_import_key'
        )
    );

    foreach ($candidates as $candidate) {
        if (papetarie_storefront_aperta_normalize_name((string) $candidate->post_title) === $normalized) {
            return (int) $candidate->ID;
        }
    }

    return null;
}

/**
 * Feed-ul Aperta foloseste nume diferite de "Tip variant" pentru acelasi
 * concept, in special la culoare (ex. "Culori Molotow", "Culori Kreul",
 * "Culori Clairefontaine" sunt toate, de fapt, culoare) - fara normalizare,
 * ar aparea cate un card de filtru separat per brand in loc de un singur
 * filtru "Culoare" unificat pe pagina de categorie.
 */
function papetarie_storefront_aperta_normalize_attr_group(string $group): string
{
    // Verificat INAINTE de regula generica "culo" -> Culoare de mai jos:
    // "Numar culori"/"N culori/set" e CATE nuante vin intr-un set, nu
    // culoarea in sine - dar "culo" e substring in "culori", deci regula
    // generica l-ar fi inghitit gresit daca ar fi verificata prima.
    if (mb_stripos($group, 'culori') !== false && preg_match('/\bnr\.?\b|n[uă]m[aă]r/iu', $group)) {
        return 'Număr culori';
    }

    if (mb_stripos($group, 'culo') !== false) {
        return 'Culoare';
    }

    // Aceeasi idee, extinsa la etichetele din descriere care variaza usor
    // intre produse pentru acelasi concept (ex. "Dimensiune" vs "Dimensiuni
    // Produs", "Compartiment Laptop" vs "Compartiment Pentru Laptop").
    if (mb_stripos($group, 'dimensiune') !== false) {
        return 'Dimensiuni';
    }

    if (mb_stripos($group, 'laptop') !== false) {
        return 'Compartiment pentru laptop';
    }

    // Aceleasi variatii mici de eticheta intre descrieri diferite - gasite
    // auditand toate grupurile distincte de pe site: "Nr. File" / "Numar
    // File" / "Numar file" sunt toate acelasi lucru. Verificam "numar" +
    // "file" impreuna (nu doar "file") ca sa nu prindem si "Grosime File"
    // (gramaj hartie, g/mp - alt concept, nu numar de pagini).
    if (mb_stripos($group, 'file') !== false && preg_match('/\bnr\.?\b|n[uă]m[aă]r/iu', $group)) {
        return 'Număr file';
    }

    // La fel, "grosime" + "scriere" impreuna (nu doar "scriere") ca sa nu
    // prindem si "Lungime (de) Scriere" (metri de scris ai unei rezerve -
    // alt concept, nu grosimea varfului).
    if (mb_stripos($group, 'grosime') !== false && mb_stripos($group, 'scriere') !== false) {
        return 'Grosime de scriere';
    }

    if (mb_stripos($group, 'diametr') !== false && mb_stripos($group, 'min') !== false) {
        return 'Diametrul minei';
    }

    if (mb_stripos($group, 'strat') !== false) {
        return 'Număr straturi';
    }

    return $group;
}

/**
 * Normalizare pe VALOARE, condiționată de grup (spre deosebire de
 * normalize_attr_group() de mai sus, care normalizeaza doar eticheta de
 * grup). Cazuri unde valoarea are o precizare in plus fata de conceptul
 * general - "Matematica, 4 x 4 mm" e tot liniatura "Matematica", doar cu
 * dimensiunea exacta a patratelelor mentionata - un filtru separat per
 * dimensiune ar fragmenta inutil o lista deja mica de liniaturi.
 */
function papetarie_storefront_aperta_normalize_attr_value(string $group, string $value): string
{
    if ($group === 'Liniatură' && mb_stripos($value, 'matematic') === 0) {
        return 'Matematică';
    }

    // "Franceză" si "Tip francez" sunt aceeasi liniatura, scrisa diferit intre
    // descriere si titlu - unificate sub "Tip francez" (mai frecventa, 11 vs 2
    // produse la data verificarii). Decizie user 2026-09-05.
    if ($group === 'Liniatură' && mb_stripos($value, 'francez') !== false) {
        return 'Tip francez';
    }

    // "Vowen" e o greseala de scriere chiar din feedul Aperta ("Logo: vowen"
    // la gama Pulse Anatomic Cube) - probabil voiau "Woven" (tesut). Unificat
    // cu valoarea corecta deja existenta "Țesătură". Decizie user 2026-09-05.
    if ($group === 'Logo' && mb_strtolower(trim($value)) === 'vowen') {
        return 'Țesătură';
    }

    // "Golden" si "Gold" sunt aceeasi culoare (auriu), scrisa diferit -
    // unificate sub "Gold" (deja folosita de 8 produse in alte categorii, ex.
    // vopsele acrilice), la penarul din piele Cuirise care era singurul cu
    // "Golden". Decizie user 2026-09-05.
    if ($group === 'Culoare' && mb_strtolower(trim($value)) === 'golden') {
        return 'Gold';
    }

    // "Albă"/"Neagră" (forma de feminin) sunt aceeasi culoare ca "Alb"/"Negru"
    // (forma de masculin, majoritara covarsitor - 114 si 454 produse) - apar
    // ocazional cand descrierea Aperta foloseste un substantiv feminin (ex.
    // "folie albă" in loc de "folie alb"). Verificat 2026-09-08 pe tot site-ul
    // ca nu mai exista alte perechi de genul asta (Roșie/Galbenă/Portocalie
    // etc. nu apar deloc separat). Decizie user.
    if ($group === 'Culoare' && trim($value) === 'Albă') {
        return 'Alb';
    }
    if ($group === 'Culoare' && trim($value) === 'Neagră') {
        return 'Negru';
    }

    // "100 bucăți în cutie de carton" si "100 buc/cutie" sunt acelasi lucru,
    // scris diferit ("Pioneze color 100/cutie EK" era singurul cu formularea
    // lunga). Decizie user 2026-09-06.
    if ($group === 'Detalii' && mb_stripos($value, 'bucăți în cutie de carton') !== false) {
        return preg_replace('/^(\d+)\s*bucăți în cutie de carton$/iu', '$1 buc/cutie', trim($value));
    }

    // "Capacitate de capsare" avea 9 valori aproape unice (10, 15, 16, 20,
    // 25, 30, 50, 70, 100 coli, scrise si "Max."/"Maxim" inconsecvent) pe
    // doar 18 produse - un filtru unde bifezi o valoare si gasesti 1 singur
    // produs nu ajuta la alegere. Regrupat in 3 intervale late, ca la "Numar
    // file" - spre deosebire de Greutate/Dimensiuni, capacitatea de capsare
    // chiar conteaza la alegere, deci pastram filtrul, doar il facem
    // folositor. Decizie user 2026-09-06.
    if ($group === 'Capacitate De Capsare' && preg_match('/(\d+)\s*coli/iu', trim($value), $m)) {
        $n = (int) $m[1];
        if ($n <= 20) {
            return 'Până la 20 coli';
        }
        if ($n <= 30) {
            return '21–30 coli';
        }
        return 'Peste 30 coli';
    }

    // Aceeasi problema, acelasi remediu, la "Adâncime de capsare" (13 valori
    // aproape unice intre 29-96 mm, unele cu "Max." altele fara). Decizie
    // user 2026-09-06.
    if ($group === 'Adâncime De Capsare' && preg_match('/(\d+)\s*mm/iu', trim($value), $m)) {
        $n = (int) $m[1];
        if ($n <= 40) {
            return 'Până la 40 mm';
        }
        if ($n <= 60) {
            return '41–60 mm';
        }
        return 'Peste 60 mm';
    }

    // Grupul generic "Capacitate" e refolosit de MAI MULTE categorii fara
    // nicio legatura intre ele - "10 coli"/"25 coli"/etc la decapsatoare si
    // capse (capacitatea capsatorului potrivit), dar si "120 coli" la mape
    // (Folii si mape de protectie) sau "400 coli" la tavi de documente
    // (Suporturi pentru birou) - ACELASI text de valoare, produse complet
    // diferite. O regula pe baza de regex numeric ar fi grupat gresit si
    // acelea sub etichetele de mai jos, care au sens doar la capse. De-aia
    // verificam o lista EXACTA de valori (cele 7 confirmate manual ca apartin
    // categoriei Decapsatoare si capse), nu un tipar general. Gasit si
    // verificat 2026-09-06 inainte sa aplic vreo schimbare - nu generaliza
    // fara sa verifici din nou la ce categorie apartine fiecare valoare.
    $decapsatoareCapacityBuckets = [
        '10 coli' => 'Până la 25 coli',
        '25 coli' => 'Până la 25 coli',
        '40 coli' => '26–50 coli',
        '50 coli' => '26–50 coli',
        '70 coli' => 'Peste 50 coli',
        '100 coli' => 'Peste 50 coli',
        '130 coli' => 'Peste 50 coli',
    ];
    if ($group === 'Capacitate' && isset($decapsatoareCapacityBuckets[trim($value)])) {
        return $decapsatoareCapacityBuckets[trim($value)];
    }

    if ($group === 'Capacitate De Perforare' && preg_match('/^(?:Max\.?\s*)?(\d+)\s*coli$/iu', trim($value), $m)) {
        $n = (int) $m[1];
        if ($n <= 20) {
            return 'Până la 20 coli';
        }
        if ($n <= 40) {
            return '21–40 coli';
        }
        return 'Peste 40 coli';
    }

    // "Etichete/A4" (cate etichete incap pe o coala) avea 16 valori aproape
    // unice pe 21 de produse la "Etichete universale copiator" - un numar
    // mic inseamna etichete mari, un numar mare inseamna etichete mici, deci
    // conteaza real la alegere. Grupat in 3 marimi, ca la Capacitate/
    // Adancime de mai sus. Decizie user 2026-09-08.
    if ($group === 'Etichete/A4' && preg_match('/^(\d+)$/', trim($value), $m)) {
        $n = (int) $m[1];
        if ($n <= 8) {
            return 'Etichete mari (1-8/coală)';
        }
        if ($n <= 24) {
            return 'Etichete medii (10-24/coală)';
        }
        return 'Etichete mici (36-96/coală)';
    }

    // "Grosime de scriere" de 0,4 mm (varful de mijloc, cel mai comun) era
    // scrisa in 4 feluri diferite pe tot site-ul (63 de produse: pixuri,
    // mine, linere, markere) - "0,4 (M)" (fara "mm"), "0,4 mm" (fara "(M)"),
    // "0,4 mm (M)" (forma completa, majoritara - 39 produse) si "0.4 mm (M)"
    // (punct in loc de virgula zecimala). Unificate initial sub forma completa
    // "0,4 mm (M)" (decizie 2026-09-06). Codul "(M)" era insa adaugat de NOI,
    // nu venea de la Aperta pentru toate SKU-urile (ex. Linere Link-It) - crea
    // o asimetrie vizibila fata de valori vecine fara cod (ex. "1,0 mm" din
    // aceeasi categorie). Simplificat 2026-09-08 (decizie user) la doar "0,4
    // mm", fara cod - alte coduri (S/F/B/XB), cand vin efectiv din descrierea
    // Aperta, raman neatinse de regula de mai jos.
    if ($group === 'Grosime de scriere' && preg_match('/^0[,.]4\s*(?:mm)?\s*(?:\(M\))?$/iu', trim($value))) {
        return '0,4 mm';
    }

    // "Diametrul minei" avea unele valori cu simbolul "Ø" in fata ("Ø 2,9
    // mm") si altele fara ("3 mm") - aceeasi informatie, scrisa inconsecvent.
    // Verificat 2026-09-08 ca grupul e folosit STRICT la Creioane color/HB
    // (nicio suprapunere cu alta categorie), deci sigur de uniformizat.
    // Decizie user.
    if ($group === 'Diametrul Minei' && preg_match('/^Ø\s*(.+)$/u', trim($value), $m)) {
        return trim($m[1]);
    }

    // "Dimensiuni" (grup generic, refolosit cu sensuri diferite - vezi alte
    // comentarii din acest fisier) contine uneori formatul standard de
    // hartie A3/A4/A5/A6 cu milimetrii redundanti adaugati ("A3, 420 x 297
    // mm") langa produse care au acelasi format scris simplu ("A3") - cum
    // dimensiunile ISO 216 sunt fixe (A3 e mereu 420x297mm), sufixul in mm nu
    // aduce nicio informatie noua, doar sparge acelasi format in 2 valori de
    // filtru. Verificat 2026-09-09: doar 3 valori afectate site-wide (2 in
    // Blocuri desen si schite, 1 in Agende), pattern ingust (necesita
    // "A<cifra>," la inceput), deci sigur de aplicat general. Decizie user.
    if ($group === 'Dimensiuni' && preg_match('/^(A[0-9]+)\s*,\s*\d+\s*x\s*\d+\s*mm/iu', trim($value), $m)) {
        return strtoupper($m[1]);
    }

    // La markerele OHP Schneider unitatea "mm" lipsea inconsecvent din
    // valoare - unele o au ("0,7 mm"), altele nu ("0,3 (S)", "0,5 (F)",
    // "1-1,5 (M)", "2-3 (B)") desi e aceeasi masuratoare, doar scrisa diferit
    // - facea filtrul sa para dezordonat. Adaugam "mm" oriunde lipseste,
    // pastram codul de varf (S/M/F/B) neschimbat. Decizie user 2026-09-07.
    // Extins 2026-09-08: intervalul poate fi despartit si cu "/" (ex.
    // "2,6/4,7", varf dublu Birello), nu doar cu "-" (ex. "1-4,7").
    if ($group === 'Grosime de scriere' && mb_stripos($value, 'mm') === false
        && preg_match('/^([\d.,]+(?:[-\/][\d.,]+)?)\s*(\([^)]*\))?$/u', trim($value), $m)) {
        $code = isset($m[2]) ? ' ' . trim($m[2]) : '';
        return trim($m[1]) . ' mm' . $code;
    }

    // Multe stilouri/rollere Schneider au anul de editie lipit direct in
    // fata culorii in descrierea Aperta (ex. "2024 Negru", "2025/2026
    // Vernil+Roz") desi e acelasi Negru/Vernil ca la restul produselor fara
    // an - doar sparge aceeasi culoare in mai multe optiuni de filtru.
    // Eliminam anul, pastram doar culoarea. Decizie user 2026-09-07.
    if ($group === 'Culoare' && preg_match('/^\d{4}(?:\/\d{4})?\s+(.+)$/u', trim($value), $m)) {
        return trim($m[1]);
    }

    // "Alb/Albstru" - litera lipsa in feedul Aperta pentru un calculator de
    // birou (celelalte combinatii de 2 culori, ex. "Negru/Albastru", sunt
    // scrise corect). Decizie user 2026-09-06.
    if ($group === 'Culoare' && trim($value) === 'Alb/Albstru') {
        return 'Alb/Albastru';
    }

    // "Baterie + solar" si "Baterie+solar" sunt aceeasi sursa de alimentare,
    // scrisa cu/fara spatii - unificate sub forma majoritara (17 vs 2
    // produse). Decizie user 2026-09-06.
    if ($group === 'Sursă Alimentare' && preg_match('/^baterie\s*\+\s*solar$/iu', trim($value))) {
        return 'Baterie+solar';
    }

    // "160 g", "160 g/mp", "80g/mp" sunt aceeasi gramaj, doar scrise diferit
    // intre descriere (Aperta) si titlu (extract_text_attributes) - pastram
    // doar numarul + "g", indiferent de sufixul "/mp" sau spatiere.
    if ($group === 'Gramaj' && preg_match('/^(\d+)\s*g/i', $value, $m)) {
        return $m[1] . ' g';
    }

    // "250 coli/top" si "250 coli/top, 4 topuri/cutie" sunt acelasi numar de
    // coli per top - detaliul suplimentar (topuri/cutie) are oricum propriul
    // grup separat ("Topuri/cutie"), deci nu se pierde, doar nu mai
    // fragmenteaza si Ambalare in 2 optiuni pentru acelasi numar.
    if ($group === 'Ambalare' && preg_match('/^(\d+\s*coli\s*\/\s*top)/iu', $value, $m)) {
        return trim($m[1]);
    }

    // "Numar file" avea pana la 19 valori distincte pe o singura categorie
    // (Notesuri adezive), majoritatea cu 1-2 produse fiecare - un filtru cu
    // atatea bife aproape unice nu ajuta clientul sa aleaga. Regrupam in 3
    // intervale late. Pentru formatul "12 x 100" (pachet de 12 blocuri a
    // cate 100 file), folosim numarul per bloc (100), nu totalul - clientul
    // vede oricum numarul exact in titlul produsului, aici conteaza doar
    // gruparea utila la filtrare. Decizie user 2026-09-04, regula globala.
    if ($group === 'Număr file') {
        $n = null;
        if (preg_match('/^\s*\d+\s*x\s*(\d+)\s*$/i', $value, $m)) {
            $n = (int) $m[1];
        } elseif (preg_match('/^\s*(\d+)\s*$/', $value, $m)) {
            $n = (int) $m[1];
        }
        if ($n !== null) {
            if ($n <= 100) {
                return 'Până la 100 file';
            }
            if ($n <= 400) {
                return '100–400 file';
            }
            return 'Peste 400 file';
        }
    }

    // "Dimensiuni: 135" pentru "Foarfecă 13.5 cm zigzag" (SD-000349) - eroare
    // de scriere chiar in descrierea trimisa de Aperta (lipseste virgula
    // zecimala), corectata punctual aici ca sa nu fie suprascrisa la
    // urmatoarea resincronizare. Decizie user 2026-09-04.
    if ($group === 'Dimensiuni' && trim($value) === '135') {
        return '13,5 cm';
    }

    return $value;
}

/**
 * Taxonomie unica pentru VALORI de atribute filtrabile (culoare, format etc.),
 * indiferent de numele atributului - un singur termen = o pereche (grup, valoare),
 * ex. slug "culoare-rosu", cu grupul ("Culoare") retinut in term meta
 * "pap_attr_group". Asta evita sa inregistram cate o taxonomie separata pentru
 * fiecare din cele ~17 nume de atribute din feed, dar tot permite filtrare
 * eficienta prin tax_query, la fel ca la Brand/Subcategorie - grupate vizual
 * dupa meta, nu dupa taxonomie separata.
 */
function papetarie_storefront_aperta_register_attr_taxonomy(): void
{
    register_taxonomy(
        'product_attr_value',
        'product',
        [
            'label' => __('Atribut produs', 'papetarie-storefront'),
            'hierarchical' => false,
            'public' => true,
            'show_in_nav_menus' => false,
            'show_ui' => true,
            'show_admin_column' => false,
            'show_in_rest' => false,
            'rewrite' => false,
        ]
    );
}
add_action('init', 'papetarie_storefront_aperta_register_attr_taxonomy');

/**
 * Gaseste (sau creeaza) termenul pentru o pereche (grup, valoare) - ex.
 * grup "Culoare", valoare "Roșu" -> slug "culoare-rosu", nume afisat "Roșu"
 * (grupul se stie separat, din term meta, nu trebuie repetat in numele termenului).
 */
function papetarie_storefront_aperta_get_or_create_attr_term(string $group, string $value): ?int
{
    $group = papetarie_storefront_aperta_normalize_attr_group(trim($group));
    $value = papetarie_storefront_aperta_normalize_attr_value($group, trim($value));

    if ($group === '' || $value === '') {
        return null;
    }

    // Regula generala pentru orice valoare de filtru, indiferent de sursa
    // (variantele produselor variabile vin ca text brut din feed - Aperta
    // scrie "tip francez", "matematică, 4 x 4 mm" cu litera mica - fara
    // asta, aceeasi optiune reala apare de 2 ori in filtru, o data cu
    // majuscula (din alta sursa) si o data fara).
    $value = mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');

    $slug = sanitize_title($group . '-' . $value);
    $existing = get_term_by('slug', $slug, 'product_attr_value');

    if ($existing instanceof WP_Term) {
        // Slug-ul e mereu minuscul (sanitize_title) - un termen creat cu
        // regula veche, necapitalizata, e gasit tot aici, dar cu numele
        // vechi neschimbat daca nu-l actualizam explicit.
        if ($existing->name !== $value) {
            wp_update_term($existing->term_id, 'product_attr_value', ['name' => $value]);
        }

        return (int) $existing->term_id;
    }

    $created = wp_insert_term($value, 'product_attr_value', ['slug' => $slug]);

    if (is_wp_error($created)) {
        $byName = get_term_by('name', $value, 'product_attr_value');
        return $byName instanceof WP_Term ? (int) $byName->term_id : null;
    }

    $termId = (int) $created['term_id'];
    update_term_meta($termId, 'pap_attr_group', $group);

    return $termId;
}

/**
 * Construieste atribute WooCommerce reale (locale, nu taxonomie) dintr-un
 * set de perechi grup=>valoare extrase din descriere/nume - astea sunt ce
 * populeaza efectiv tab-ul "Specificatii" de pe pagina de produs
 * ($product->set_attributes()), spre deosebire de taxonomia
 * product_attr_value (tag_attr_terms()/tag_multiple_attrs()/
 * tag_variant_and_extra_attrs() de mai jos), care serveste DOAR filtrul de
 * pe pagina de arhiva si nu are nicio legatura cu ce se afiseaza pe tab-ul
 * de produs. Pana acum doar taxonomia era populata - Specificatii ramanea
 * gol la toate produsele simple si arata doar culoarea la cele variabile,
 * desi extract_description_attributes()/extract_text_attributes() gaseau
 * deja Format/Material/Dimensiuni/Greutate etc., pur si simplu nimeni nu le
 * punea si pe produs, doar in taxonomia de filtrare. Gasit 2026-08-30.
 *
 * $plainListItems (vezi extract_plain_list_items()) sunt bullet-uri fara
 * eticheta scurta - merg toate sub UN singur atribut generic "Caracteristici"
 * (WooCommerce afiseaza mai multe valori pe acelasi atribut local ca lista
 * separata prin virgula) - fara exceptie, orice bullet ajunge in
 * Specificatii, decizie explicita a userului 2026-08-30.
 *
 * @param array<string, string> $groupValuePairs grup => valoare
 * @param string[] $plainListItems
 * @return WC_Product_Attribute[]
 */
function papetarie_storefront_aperta_build_extra_wc_attributes(array $groupValuePairs, array $plainListItems = []): array
{
    $attributes = [];

    foreach ($groupValuePairs as $group => $value) {
        $group = trim((string) $group);
        $value = trim((string) $value);
        if ($group === '' || $value === '') {
            continue;
        }

        // "Dimensiuni: 135" pentru "Foarfecă 13.5 cm zigzag" (SD-000349) -
        // aceeasi eroare de scriere din descrierea Aperta ca la
        // normalize_attr_value(), dar aici corectam tab-ul Specificatii
        // (atribut local WooCommerce, nu taxonomia de filtrare) - altfel
        // orice corectie manuala e suprascrisa la urmatoarea resincronizare.
        // Nu refolosim normalize_attr_value() intreaga aici pentru ca are si
        // reguli cu scop opus (ex. "Numar file" transforma "80" in intervalul
        // "Pana la 100 file" - potrivit pentru un filtru, dar gresit/vag pe
        // tab-ul de Specificatii, unde vrem valoarea exacta). Decizie user
        // 2026-09-04.
        if ($group === 'Dimensiuni' && $value === '135') {
            $value = '13,5 cm';
        }

        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name($group);
        $attribute->set_options([$value]);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $attributes[] = $attribute;
    }

    $plainListItems = array_values(array_filter(array_map('trim', $plainListItems), static fn (string $v): bool => $v !== ''));
    if (!empty($plainListItems)) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id(0);
        $attribute->set_name(__('Caracteristici', 'papetarie-storefront'));
        $attribute->set_options($plainListItems);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        $attributes[] = $attribute;
    }

    return $attributes;
}

/**
 * Eticheteaza produsul-parinte cu termenii (grup, valoare) pentru toate
 * valorile distincte gasite la variantele lui - asa poate fi gasit prin
 * filtrare chiar daca pagina de arhiva listeaza doar produsul-parinte, nu
 * fiecare varianta separat. Inlocuieste (nu adauga la) setul anterior, ca
 * variantele disparute intre timp sa nu ramana ca filtre fantoma.
 *
 * @param array<int, string> $values
 */
function papetarie_storefront_aperta_tag_attr_terms(int $productId, string $group, array $values): void
{
    $termIds = [];

    foreach (array_unique($values) as $value) {
        $termId = papetarie_storefront_aperta_get_or_create_attr_term($group, $value);
        if ($termId !== null) {
            $termIds[] = $termId;
        }
    }

    wp_set_object_terms($productId, $termIds, 'product_attr_value', false);
}

/**
 * La fel ca tag_attr_terms(), dar pentru un produs simplu care poate avea
 * MAI MULTE atribute extrase din text deodata (Format + Gramaj + Nr. coli) -
 * seteaza-le pe toate intr-un singur apel, ca sa nu se suprascrie una pe alta
 * (fiecare apel la wp_set_object_terms cu append=false ar sterge ce a pus
 * apelul anterior).
 *
 * @param array<string, string> $groupValuePairs grup => valoare (o singura valoare per grup)
 */
function papetarie_storefront_aperta_tag_multiple_attrs(int $productId, array $groupValuePairs): void
{
    $termIds = [];

    foreach ($groupValuePairs as $group => $value) {
        $termId = papetarie_storefront_aperta_get_or_create_attr_term($group, $value);
        if ($termId !== null) {
            $termIds[] = $termId;
        }
    }

    wp_set_object_terms($productId, $termIds, 'product_attr_value', false);
}

/**
 * Ca tag_attr_terms(), dar combina si atributele suplimentare extrase din
 * descriere/nume (Format, Gramaj, Ambalare etc.) - necesar pentru produsele
 * VARIABILE, care altfel primesc doar atributul de variatie (culoare) si
 * pierd restul informatiei structurate din descriere (multe produse cu
 * variante de culoare au si ele liste <li>Format: A4</li> etc., dar pana
 * acum nimic nu le citea, fiindca extract_description_attributes() /
 * extract_text_attributes() rulau doar pe ramura produselor simple).
 *
 * @param array<int, string> $variantValues
 * @param array<string, string> $extraGroupValuePairs grup => valoare (o singura valoare per grup)
 */
function papetarie_storefront_aperta_tag_variant_and_extra_attrs(int $productId, string $variantGroup, array $variantValues, array $extraGroupValuePairs): void
{
    $termIds = [];

    foreach (array_unique($variantValues) as $value) {
        $termId = papetarie_storefront_aperta_get_or_create_attr_term($variantGroup, $value);
        if ($termId !== null) {
            $termIds[] = $termId;
        }
    }

    foreach ($extraGroupValuePairs as $group => $value) {
        $termId = papetarie_storefront_aperta_get_or_create_attr_term($group, $value);
        if ($termId !== null) {
            $termIds[] = $termId;
        }
    }

    wp_set_object_terms($productId, array_unique($termIds), 'product_attr_value', false);
}

/**
 * Aplica filtrele de atribute pe produsele Aperta DEJA sincronizate, fara sa
 * refaca sincronizarea completa (fara re-descarcare poze, fara reluare pret/
 * stoc) - citeste direct din datele deja salvate in WordPress (nume,
 * descriere, categorie, atribute existente), nu din feed.csv. Mult mai rapid
 * decat o resincronizare completa, util cand codul de extragere a atributelor
 * se schimba si vrem sa-l aplicam retroactiv pe produsele existente.
 *
 * @return array{processed: int, tagged: int, total: int}
 */
function papetarie_storefront_aperta_backfill_attributes_chunk(int $offset, int $limit): array
{
    $allIds = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'meta_key' => '_pap_aperta_cod_produs',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
    ]);

    $total = count($allIds);
    $ids = array_slice($allIds, $offset, $limit);
    $tagged = 0;

    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!($product instanceof WC_Product)) {
            continue;
        }

        $name = $product->get_name();
        $description = $product->get_description();
        $categoryNames = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        $categoryPath = is_array($categoryNames) ? implode('>', $categoryNames) : '';

        $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
        $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
        $extraAttrs = $descAttrs + $textAttrs;
        $plainItems = papetarie_storefront_aperta_extract_plain_list_items($description, $descAttrs);

        if ($product instanceof WC_Product_Variable) {
            // Nu recalculam atributul de variatie (culoare) - nu avem
            // randurile din feed aici, doar ce e deja salvat. Pastram
            // atributul de variatie existent neatins, adaugam doar
            // extra-atributele descoperite acum in descriere/nume - vezi
            // build_extra_wc_attributes() (populeaza tab-ul "Specificatii",
            // gol pana acum la produsele variabile in afara de culoare).
            $existingAttributes = $product->get_attributes();
            $variationAttribute = null;
            foreach ($existingAttributes as $attr) {
                if ($attr->get_variation()) {
                    $variationAttribute = $attr;
                    break;
                }
            }
            if ($variationAttribute === null) {
                continue;
            }

            $group = $variationAttribute->get_name();
            $values = $variationAttribute->get_options();
            if ($group === '' || empty($values)) {
                continue;
            }
            unset($extraAttrs[$group]);

            $product->set_attributes(array_merge(
                [$variationAttribute],
                papetarie_storefront_aperta_build_extra_wc_attributes($extraAttrs, $plainItems)
            ));
            $product->save();

            papetarie_storefront_aperta_tag_variant_and_extra_attrs($id, $group, $values, $extraAttrs);
            $tagged++;
            continue;
        }

        if ($extraAttrs || $plainItems) {
            $product->set_attributes(papetarie_storefront_aperta_build_extra_wc_attributes($extraAttrs, $plainItems));
            $product->save();
            if ($extraAttrs) {
                papetarie_storefront_aperta_tag_multiple_attrs($id, $extraAttrs);
            }
            $tagged++;
        }
    }

    return [
        'processed' => count($ids),
        'tagged' => $tagged,
        'total' => $total,
    ];
}

/**
 * Extrage atribute filtrabile din NUMELE produsului, pentru produse simple
 * care nu au deloc date structurate de atribut in feed (spre deosebire de
 * produsele cu variante, unde Aperta trimite explicit "Tip variant"/"Variant").
 *
 * Fiecare tipar are propriul nivel de "siguranta":
 * - Format (A3-A6) e sigur peste tot - cod de format de hartie/document,
 *   foarte putin probabil sa apara intamplator in alt sens.
 * - Numar file e sigur peste tot - "file" e specific documentelor.
 * - Liniatura e potrivire de cuvinte cheie, nu numar - sigura oriunde.
 * - Gramaj si Numar coli raman scopate strict la categoria hârtie, fiindca
 *   un regex generic de "gramaj" ar prinde si greutati nelegate
 *   (ex. "Plastilina ... 500 g", care n-are legatura cu gramajul hartiei).
 *
 * @return array<string, string> grup => valoare
 */
function papetarie_storefront_aperta_extract_text_attributes(string $name, string $categoryPath): array
{
    $attrs = [];

    $isPaperCategory = mb_stripos($categoryPath, 'hârtie') !== false || mb_stripos($categoryPath, 'hartie') !== false;

    if (preg_match('/\bA([3-6])\b/i', $name, $m)) {
        $attrs['Format'] = 'A' . $m[1];
    }

    if (preg_match('/(\d+)\s*file\b/i', $name, $m)) {
        // Valoare bruta (fara " file") - extractorul de descriere (Nr. File:
        // 100) produce deja doar numarul, iar eticheta grupului ("Numar
        // file") spune deja ce reprezinta; cu sufix, aceeasi valoare reala
        // aparea de 2 ori in filtru ("100" si "100 file" separat).
        $attrs['Număr file'] = $m[1];
    }

    $liniaturaKeywords = [
        'dictando' => 'Dictando',
        'matematică' => 'Matematică',
        'matematica' => 'Matematică',
        'velin' => 'Velin',
        'pătrățele' => 'Pătrățele',
        'patratele' => 'Pătrățele',
    ];
    foreach ($liniaturaKeywords as $needle => $label) {
        if (mb_stripos($name, $needle) !== false) {
            $attrs['Liniatură'] = $label;
            break;
        }
    }

    if ($isPaperCategory) {
        if (preg_match('/(\d+)\s*g(?:\/mp)?\b/i', $name, $m)) {
            $attrs['Gramaj'] = $m[1] . ' g';
        }

        if (preg_match('/(\d+)\s*\/\s*top\b/i', $name, $m)) {
            // Acelasi grup si acelasi format ca "Ambalare: 250 coli/top" din
            // descriere (extract_description_attributes) - daca descrierea
            // nu are bulletul respectiv, titlul ofera aceeasi informatie;
            // scriind sub grup diferit ("Numar coli") am fi avut 2 optiuni
            // de filtru separate pentru exact acelasi lucru.
            $attrs['Ambalare'] = $m[1] . ' coli/top';
        }
    }

    // Culoare, dupa un vocabular de cuvinte cunoscute (nu pozitie fixa in
    // nume) - multe categorii (ghiozdane, bagajerie) scriu culoarea in
    // engleza, amestecata liber cu numele modelului
    // ("Pulse Fusion Cationic Blue", "Servietă ... Exacompta 17124E, gri"),
    // deci un regex pozitional n-ar functiona, dar o cautare de cuvinte
    // cunoscute merge peste tot. Separat de tag_attr_terms() de la
    // produsele cu variante - aici e doar pentru produse SIMPLE.
    $colorKeywords = [
        'negru' => 'Negru', 'black' => 'Negru',
        'alb' => 'Alb', 'white' => 'Alb',
        'gri' => 'Gri', 'gray' => 'Gri', 'grey' => 'Gri',
        'bleumarin' => 'Albastru', 'albastru' => 'Albastru', 'blue' => 'Albastru', 'navy' => 'Albastru',
        'roșu' => 'Roșu', 'rosu' => 'Roșu', 'red' => 'Roșu',
        'verde' => 'Verde', 'green' => 'Verde',
        'galben' => 'Galben', 'yellow' => 'Galben',
        'roz' => 'Roz', 'pink' => 'Roz',
        'mov' => 'Mov', 'violet' => 'Mov', 'purple' => 'Mov',
        'maro' => 'Maro', 'brown' => 'Maro',
        'bej' => 'Bej', 'beige' => 'Bej',
        'portocaliu' => 'Portocaliu', 'orange' => 'Portocaliu',
        'turcoaz' => 'Turcoaz', 'turquoise' => 'Turcoaz',
        'auriu' => 'Auriu', 'gold' => 'Auriu',
        'argintiu' => 'Argintiu', 'silver' => 'Argintiu',
    ];
    foreach ($colorKeywords as $needle => $label) {
        if (preg_match('/\b' . preg_quote($needle, '/') . '\b/iu', $name)) {
            $attrs['Culoare'] = $label;
            break;
        }
    }

    // Tip produs, dupa un vocabular de cuvinte cunoscute - utile mai ales in
    // categoriile "cos comun" (ex. "Accesorii" de la articole scolare, unde
    // stau amestecate foarfece, rigle, compasuri) care altfel n-au niciun
    // filtru care sa desparta tipurile de produse intre ele. Extindem lista
    // pe masura ce gasim alte categorii cu aceeasi problema.
    $productTypeKeywords = [
        'foarfec' => 'Foarfecă',
        'trusă geometrie' => 'Trusă geometrie',
        'trusa geometrie' => 'Trusă geometrie',
        'set geometrie' => 'Set geometrie',
        'compas' => 'Compas',
        'riglă' => 'Riglă', 'rigla' => 'Riglă',
        'echer' => 'Echer',
        'raportor' => 'Raportor',
    ];
    foreach ($productTypeKeywords as $needle => $label) {
        if (mb_stripos($name, $needle) !== false) {
            $attrs['Tip produs'] = $label;
            break;
        }
    }

    // Numar role/set sau role/bax - verificat INAINTE de "bucati/set" generic,
    // ca sa nu se suprapuna (ex. "6 role/set" nu trebuie sa devina si
    // "Numar bucati/set" = 6, sunt lucruri diferite).
    if (preg_match('/(\d+)\s*role\s*\/\s*(set|bax|pachet)\b/i', $name, $m)) {
        $attrs['Număr role/set'] = $m[1] . ' role/' . mb_strtolower($m[2]);
    } elseif (preg_match('/(\d+)\s*(?:buc)?\s*\/\s*(set|cutie|blister|pachet)\b/i', $name, $m)) {
        $attrs['Număr bucăți/set'] = $m[1] . '/' . mb_strtolower($m[2]);
    }

    if (preg_match('/(\d+)\s*strat(uri)?\b/i', $name, $m)) {
        $attrs['Număr straturi'] = $m[1] . ' straturi';
    }

    // Tiparul "numar+" din denumire NU inseamna mereu varsta - apare si la
    // specificatii tehnice ("8+8 digits" la un aparat de preturi, "3.0 + QC
    // 3.0" la un incarcator, "15 + capse" la un capsator cu set, "12 + 3
    // culori" la un set de creioane) - gasite 9 produse gresit etichetate cu
    // varsta din cauza asta (2026-09-08), inclusiv 2 produse de colorat
    // pentru copii mici etichetate absurd "18+ ani". Toate cele 15 etichete
    // CORECTE de varsta au cuvantul "Baby" in denumire (conventia reala
    // Aperta pt produse pentru bebelusi/copii mici, ex. "Carioca Baby 1+") -
    // cerem explicit acest cuvant inainte sa presupunem ca e varsta. Decizie
    // user.
    if (mb_stripos($name, 'baby') !== false && preg_match('/\b(\d{1,2})\s*\+/', $name, $m)) {
        $attrs['Vârstă'] = $m[1] . '+ ani';
    }

    // "20 culori", "4 culori" - tipar frecvent la plastilină/seturi creative,
    // separat de culoarea in sine (o singura nuanta) - aici e CATE nuante
    // vin in set.
    if (preg_match('/(\d+)\s*culori\b/iu', $name, $m)) {
        $attrs['Număr culori'] = $m[1] . ' culori';
    }

    return $attrs;
}

/**
 * Multe descrieri Aperta sunt liste <li>Etichetă: Valoare</li> - o sursa
 * mult mai bogata si 100% generica decat titlul (~jumatate din produsele
 * simple au asa ceva), gasita analizand un produs cu Capse -> "Capacitate:
 * 25 coli" era chiar acolo, nu in titlu. Extragem TOATE perechile gasite,
 * nu doar cateva anume - functioneaza pe orice categorie, fara reguli
 * speciale per tip de produs.
 *
 * Prinde perechile atat in <li>...</li> CAT SI pe rand simplu de text, fara
 * <li> (ex. "Dimensiuni: L 32 x h 41 x l 15 cm." pe propriul rand) - gasit
 * 2026-08-30 la rucsacul Exacompta: "Dimensiuni" nu era in <li>, ramanea
 * needetectat cu regex-ul vechi (doar <li>), deci nepromovat ca atribut si
 * nescos din tab-ul Descriere - inconsecventa semnalata de user.
 *
 * Doar valorile scurte devin atribute - unele etichete (ex.
 * "Caracteristici") au propozitii intregi ca valoare, care nu functioneaza
 * nici ca optiune de filtru, nici ca randul unui tabel de specificatii -
 * lungimea e un filtru simplu, generic, pentru asta. Astea NU se pierd -
 * vezi extract_plain_list_items() mai jos, care le string sub un atribut
 * generic "Caracteristici" (orice bullet ajunge in Specificatii, fara
 * exceptie, decizie explicita a userului 2026-08-30).
 *
 * @return array<string, string> grup => valoare
 */
function papetarie_storefront_aperta_extract_description_attributes(string $description): array
{
    $attrs = [];
    $maxValueLength = 40;

    if (preg_match_all('/<li>\s*([^:<]{2,40}):\s*([^<]+?)\s*<\/li>/iu', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            papetarie_storefront_aperta_collect_desc_attr($attrs, $match[1], $match[2], $maxValueLength);
        }
    }

    foreach (preg_split('/\r\n|\r|\n/', $description) as $line) {
        $line = trim(wp_strip_all_tags($line));
        if ($line === '' || !str_contains($line, ':')) {
            continue;
        }

        // "Ghiozdan Pulse Saturn Black" (SD-018788) are doua perechi
        // eticheta:valoare pe ACELASI rand in descrierea Aperta
        // ("Fermoare: SBS, Logo: metal"), format neobisnuit fata de restul
        // feed-ului (o pereche pe rand) - regula generala de mai jos ar fi
        // citit gresit tot ce e dupa primul ":" ca fiind valoarea lui
        // "Fermoare", pierzand complet "Logo: metal". Separate punctual aici,
        // fara sa schimbam regula generala (risc de fals-pozitive pe alte
        // produse). Decizie user 2026-09-05.
        if (preg_match('/^Fermoare:\s*SBS,\s*Logo:\s*metal$/iu', $line)) {
            papetarie_storefront_aperta_collect_desc_attr($attrs, 'Fermoare', 'SBS', $maxValueLength);
            papetarie_storefront_aperta_collect_desc_attr($attrs, 'Logo', 'metal', $maxValueLength);
            continue;
        }

        if (preg_match('/^([\p{L}][\p{L}\s\/\-]{1,39}):\s*(\S.*)$/u', $line, $m)) {
            papetarie_storefront_aperta_collect_desc_attr($attrs, $m[1], $m[2], $maxValueLength);
        }
    }

    // "Format A4" scris in proza, fara "Format:" - vocabular inchis (formate
    // de hartie standard), deci sigur de extras oriunde apare in text, spre
    // deosebire de o eticheta libera. Nu suprascrie un "Format: ..." deja
    // gasit mai sus prin perechea eticheta:valoare. Gasit 2026-08-31 la un
    // set creativ Carioca unde "Format A4" era ingropat intr-o singura
    // propozitie lunga, fara ":", deci needetectat de regulile de mai sus.
    if (!isset($attrs['Format']) && preg_match('/\bFormat\s+(A[0-9]|B[0-9]|DL)\b/iu', $description, $m)) {
        $attrs['Format'] = strtoupper($m[1]);
    }

    return $attrs;
}

function papetarie_storefront_aperta_collect_desc_attr(array &$attrs, string $rawGroup, string $rawValue, int $maxValueLength): void
{
    $rawGroup = trim($rawGroup);
    $value = trim($rawValue);

    if ($rawGroup === '' || $value === '' || mb_strlen($value) > $maxValueLength) {
        return;
    }

    // Normalizare simpla de capitalizare, ca "Nr. file" si "Nr. FILE" sa
    // devina acelasi grup (altfel am avea carduri de filtru duplicate).
    // Capitalizarea VALORII se face centralizat, in
    // get_or_create_attr_term() - acolo trec si variantele produselor
    // variabile (text brut din feed), nu doar cele extrase aici.
    $group = mb_convert_case($rawGroup, MB_CASE_TITLE, 'UTF-8');

    // Aperta foloseste 2 etichete diferite pentru acelasi lucru la
    // perforatoare ("Capacitate de perforare" vs "Capacitate maxima de
    // perforare") - fara asta ajungeau 2 grupuri de filtru separate pentru
    // aceeasi informatie. Decizie user 2026-09-06.
    if ($group === 'Capacitate Maximă De Perforare') {
        $group = 'Capacitate De Perforare';
    }

    // Aceeasi informatie (cat scrie o mina/rezerva pana se termina) apare sub
    // 2 nume la "Mine pentru pixuri" - "Lungime Scriere" (9 produse) si
    // "Lungime De Scriere" (1 produs). Unificate sub forma corecta
    // gramatical. Decizie user 2026-09-07.
    if ($group === 'Lungime Scriere') {
        $group = 'Lungime De Scriere';
    }

    $attrs[$group] = $value;
}

/**
 * Extrage textul TUTUROR elementelor de lista care NU sunt deja perechi
 * "Eticheta: valoare" (vezi extract_description_attributes() mai sus) -
 * bullet-uri pur descriptive (<li>Compact si usor de transportat</li>) sau
 * cu valoare prea lunga ca sa fi devenit atribut. Decizie explicita a
 * userului 2026-08-30: ORICE bullet/lista trebuie sa ajunga in tab-ul
 * Specificatii, fara exceptie, nu doar cele care arata ca o specificatie
 * scurta - randurile astea nu mai raman deloc in Descriere (vezi
 * includes/product-description.php).
 *
 * @param array<string, string> $labeledAttrs perechile deja extrase, ca sa nu le numaram de doua ori
 * @return string[] textul fiecarui bullet, in ordinea din sursa, fara duplicate
 */
function papetarie_storefront_aperta_extract_plain_list_items(string $description, array $labeledAttrs): array
{
    $items = [];

    if (preg_match_all('/<li>\s*(.*?)\s*<\/li>/isu', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $text = trim(wp_strip_all_tags($match[1]));
            if ($text !== '' && !papetarie_storefront_aperta_text_matches_labeled_attr($text, $labeledAttrs)) {
                $items[] = $text;
            }
        }
    }

    foreach (preg_split('/\r\n|\r|\n/', $description) as $line) {
        $line = trim($line);
        if (!preg_match('/^(?:[-*•]|\d+[.\)])\s+(\S.*)$/u', $line, $m)) {
            continue;
        }
        $text = trim(wp_strip_all_tags($m[1]));
        if ($text !== '' && !papetarie_storefront_aperta_text_matches_labeled_attr($text, $labeledAttrs)) {
            $items[] = $text;
        }
    }

    return array_values(array_unique($items));
}

function papetarie_storefront_aperta_text_matches_labeled_attr(string $text, array $labeledAttrs): bool
{
    if (!preg_match('/^([\p{L}][\p{L}\s\/\-]{1,39}):\s*(\S.*)$/u', $text, $m)) {
        return false;
    }

    $group = mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
    $value = trim($m[2]);

    return isset($labeledAttrs[$group]) && $labeledAttrs[$group] === $value;
}

function papetarie_storefront_aperta_stock_status_from_text(string $statusText): string
{
    $statusText = mb_strtolower($statusText);

    if (str_contains($statusText, 'indisponibil')) {
        return 'outofstock';
    }

    if (str_contains($statusText, 'comand')) {
        return 'onbackorder';
    }

    return 'instock';
}

/**
 * Estimare ieftina: produsul asta ar face munca reala la o sincronizare, sau
 * ar fi sarit pe calea rapida (hash identic)? Folosita DOAR ca sa decidem cum
 * impartim bucatile (vezi PAP_APERTA_HEAVY_PRODUCT_ROWS) - nu ia nicio decizie
 * despre date, deci daca se desincronizeaza vreodata de logica reala din
 * upsert_product() consecinta e strict una de programare (o bucata rupta
 * degeaba, sau un produs greu neizolat), nimic incorect pe site.
 *
 * Costa o citire de postmeta, care oricum incalzeste cache-ul pentru
 * upsert_product() imediat dupa.
 *
 * @param array<int, array<string, string>> $rows
 */
function papetarie_storefront_aperta_product_needs_work(array $rows): bool
{
    if (empty($rows)) {
        return false;
    }

    $first = $rows[0];
    $isVariable = count($rows) > 1 || trim((string) $first['Variant']) !== '';
    $productId = $isVariable
        ? papetarie_storefront_aperta_find_parent_by_cod_produs(trim((string) $first['Cod produs']))
        : papetarie_storefront_aperta_find_by_sku_meta(trim((string) $first['Cod unic']));

    if ($productId === null) {
        return true;
    }

    return get_post_meta($productId, '_pap_aperta_row_hash', true) !== md5(serialize($rows));
}

/**
 * Creeaza/actualizeaza un produs (simplu sau variabil) dintr-un grup de
 * randuri feed.csv care au acelasi "Cod produs".
 *
 * @param array<int, array<string, string>> $rows
 */
/**
 * @return array{product_id: int, is_new: bool, is_variable: bool, old_price: ?float, new_price: ?float}
 */
function papetarie_storefront_aperta_upsert_product(array $rows): array
{
    if (empty($rows)) {
        return ['product_id' => 0, 'is_new' => false, 'is_variable' => false, 'old_price' => null, 'new_price' => null, 'was_trashed' => false, 'price_warning' => null];
    }

    $first = $rows[0];
    $name = trim((string) $first['Denumire produs']);
    $description = (string) $first['Descriere produs'];
    $categoryPath = (string) $first['Categorie produs'];
    $brandName = (string) $first['Brand produs'];
    $discountGroup = (string) $first['Discount'];
    $discountPercent = papetarie_storefront_aperta_discount_percent($discountGroup);

    // Denumirea Aperta pentru "Copertă caiet A5 margine color" (Cod produs
    // 2660) nu spune ca e un pachet de 50 de bucati - pretul de 35,35 lei
    // parea gresit langa celelalte coperti de 0,54-2,12 lei/bucata. Adaugat
    // punctual sufixul, ca clientul sa inteleaga pretul dintr-o privire, fara
    // sa deschida tab-ul de Specificatii. Decizie user 2026-09-05.
    if (trim((string) $first['Cod produs']) === '2660') {
        $name .= ' (set 50 buc.)';
    }

    // 6 produse "Benzi de cauciuc" au ramas cu "Forster" in denumirea trimisa
    // de Aperta, desi campul lor de brand a fost actualizat la producatorul
    // real, Scriva (probabil furnizorul s-a schimbat si Aperta nu a
    // actualizat si titlul) - confuz pentru client, care vede "Forster" in
    // titlu dar "Scriva" la brand. Corectat punctual, pe codurile de produs
    // confirmate cu aceasta problema. Decizie user 2026-09-08.
    $forsterToScrivaCodes = ['2577', '2581', '2580', '2579', '2578', '5931'];
    if (in_array(trim((string) $first['Cod produs']), $forsterToScrivaCodes, true)) {
        $name = str_ireplace('Forster', 'Scriva', $name);
    }

    // Denumirile Aperta pentru gama Kreul (categoriile Fun, Craft, Sticla si
    // portelan din Arta) erau in mare parte netraduse din germana/engleza
    // ("KREUL Glass & Porcelain Pen...", "KREUL Tattoo Pen..."), spre
    // deosebire de tot restul catalogului care e in romana. Traduse punctual,
    // pe cele 29 de coduri de produs confirmate cu aceasta problema (numele
    // de gama ale brandului, ex. "Happy Effects", "PaperLove", "Golden
    // Elegance", raman nemodificate, la fel ca "Slider"/"Faber-Castell" in
    // alte categorii). Decizie user 2026-09-08.
    $kreulTranslations = [
        'SKP172' => 'Marker tatuaje temporare Kreul Tribal, set 4 buc.',
        'SKP245' => 'Marker tatuaje temporare Kreul',
        'SKP173' => 'Marker tatuaje temporare Kreul (ancoră, stele, fluture), set 4 buc.',
        'SKP263' => 'Lac lucios pentru transfer foto Kreul Potch 50 ml',
        'SKP262' => 'Lac satinat pentru transfer foto pe lumânări Kreul Potch 50 ml',
        'SKP261' => 'Adeziv pentru transfer foto Kreul Potch 150 ml',
        'SKP168' => 'Set Kreul Happy Effects, 6 buc.',
        'SKP175' => 'Marker grund Kreul pentru foiță metalică',
        'SKP174' => 'Set aurire cu foiță metalică Kreul Golden Elegance',
        'SKP166' => 'Set markere pentru lumânări Kreul Glamour Lighting, 4 buc.',
        'SKP169' => 'Set Kreul PaperLove',
        'SKP162' => 'Set markere lucioase Kreul, vârf mediu, 4 buc.',
        'SKP125' => 'Vopsea acrilică Kreul care luminează în întuneric 150 ml',
        'SKR258' => 'Marker sticlă și porțelan Kreul Glitter, vârf mediu, 5/set',
        'SKP260' => 'Marker sticlă și porțelan Kreul transparent, vârf fin',
        'SKP254' => 'Marker-pensulă sticlă și porțelan Kreul',
        'SKP251' => 'Marker sticlă și porțelan Kreul metalizat, vârf mediu, 5/set',
        'SKP256' => 'Marker sticlă și porțelan Kreul transparent, vârf mediu',
        'SKP255' => 'Marker sticlă și porțelan Kreul Glitter, vârf mediu',
        'SKP253' => 'Marker sticlă și porțelan Kreul Classic, vârf caligrafic',
        'SKP252' => 'Marker sticlă și porțelan Kreul Classic, vârf fin',
        'SKP250' => 'Marker sticlă și porțelan Kreul metalizat, vârf fin',
        'SKP249' => 'Marker sticlă și porțelan Kreul metalizat, vârf mediu',
        'SKP248' => 'Marker sticlă și porțelan Kreul Classic, vârf mediu',
        'SKP247' => 'Marker porțelan Kreul, vârf mediu',
        'PMK077' => 'Set markere porțelan Kreul pentru copii „Colorează-ți cana", vârf mediu',
        'SKP159' => 'Marker sticlă și porțelan Kreul transparent, 5/set',
        'SKP069' => 'Vopsea sticlă și porțelan Kreul Classic Color Living, 6 culori x 20 ml',
        'SKP068' => 'Vopsea sticlă și porțelan Kreul Chalky, 6 culori x 20 ml',
        // Continuare traduceri Kreul, gasite la auditul "Acrilice si culori
        // ulei" / "Mucki" (2026-09-08) - acelasi motiv ca mai sus.
        'SKP269' => 'Vopsea acrilică Solo Goya Fluorescentă 100 ml',
        'SKP151' => 'Set Solo Goya premixat Pouring 6 x 80 ml',
        'SKP078' => 'Vopsea Mucki pentru copii, care luminează în întuneric, 150 ml',
        'SKP077' => 'Vopsea Mucki sclipitoare, 4/set',
        'SKP076' => 'Vopsea decorativă Mucki 80 ml 6/set',
        'SKP081' => 'Vopsea textilă pentru pictat cu degetele Mucki 150 ml 4/set',
        'SKP075' => 'Vopsea pentru pictat cu degetele Mucki, efect sclipitor, 150 ml 4/set',
        'SKP072' => 'Vopsea pentru pictat cu degetele Mucki Royal Children, 50 ml, 6 culori/set',
        'SKP071' => 'Vopsea pentru pictat cu degetele Mucki Children of Fortune, 50 ml, 6 culori/set',
        'SKP074' => 'Vopsea pentru pictat cu degetele Mucki 150 ml 6/set',
        'SKP073' => 'Vopsea pentru pictat cu degetele Mucki 150 ml 4/set',
    ];
    $codProdusForKreul = trim((string) $first['Cod produs']);
    if (isset($kreulTranslations[$codProdusForKreul])) {
        $name = $kreulTranslations[$codProdusForKreul];
    }

    $isVariable = count($rows) > 1 || trim((string) $first['Variant']) !== '';
    $primaryCodUnic = trim((string) $first['Cod unic']);
    $codProdus = trim((string) $first['Cod produs']);

    // Produsele simple sunt identificate prin propriul SKU (Cod unic).
    // Produsele variabile nu au un SKU propriu (fiecare variatie are al ei),
    // deci parintele e identificat prin "Cod produs" - alta cheie, ca sa nu
    // se suprapuna cu SKU-ul primei variatii.
    $productId = $isVariable
        ? papetarie_storefront_aperta_find_parent_by_cod_produs($codProdus)
        : papetarie_storefront_aperta_find_by_sku_meta($primaryCodUnic);

    // papetarie_storefront_aperta_lookup_maps() cauta INTENTIONAT si prin
    // 'product_variation' (nu doar 'product'), ca sa detecteze exact cazul
    // de mai jos - un cod care a ajuns sa fie deja variatia altui produs.
    // Daca $productId gasit aici e de fapt o variatie, WC_Product_Variable/
    // WC_Product_Simple('$productId') arunca "Invalid product." la citire
    // (WC_Product_Data_Store_CPT::read() cere post_type==='product') si
    // crapa tot lotul de sincronizare - fara recuperare, pentru ca $offset-ul
    // urmator nu mai apuca sa fie programat. Confirmat live: sincronizarea
    // de produse s-a blocat la acelasi offset la fiecare rulare de cand a
    // aparut coliziunea (jurnal Action Scheduler, "Produsul nu este valid.").
    // Acelasi tip de coliziune e deja tratat mai jos la nivel de variatie
    // individuala (cauta "WC_Product_Variation arunca eroare") - aici lipsea
    // echivalentul la nivel de produs-parinte. Tratam la fel: nu il folosim
    // ca match valid (ramane candidat pentru migrarea manuala existenta),
    // ca sa nu crape restul sincronizarii pentru toate produsele de dupa el.
    if ($productId !== null && get_post_type($productId) !== 'product') {
        $pendingMigrations = get_option('pap_aperta_pending_variation_migrations', []);
        $pendingMigrations[$isVariable ? $codProdus : $primaryCodUnic] = [
            'existing_post_id' => $productId,
            'existing_post_type' => get_post_type($productId),
            'context' => 'parent_lookup',
            'found_at' => current_time('mysql'),
        ];
        update_option('pap_aperta_pending_variation_migrations', $pendingMigrations, false);
        $productId = null;
    }

    // Potrivirea dupa nume e gandita pentru produse vechi din importul JSON
    // original (fara SKU) - pentru un grup sintetic (creat de consolidare,
    // 'merged-'/'link-') nu trebuie folosita: mai multe produse-parinte
    // individuale, deja publicate, pot avea EXACT acelasi nume generic de
    // familie (ex. "Lavete Kimberly-Clark WypAll X50, 50 buc/set" pe fiecare
    // culoare) - potrivirea dupa nume ar "adopta" din greseala unul dintre
    // ele ca tinta a fuziunii, in loc sa trateze grupul ca fiind chiar nou.
    // Gasit live 2026-07-28.
    $isSyntheticGroup = str_starts_with($codProdus, 'merged-') || str_starts_with($codProdus, 'link-');
    if ($productId === null && !$isSyntheticGroup) {
        $productId = papetarie_storefront_aperta_find_legacy_product_by_name($name);
    }

    $isNew = $productId === null;
    // find_by_sku_meta/find_parent_by_cod_produs cauta si prin trash (ca sa
    // tina stocul la zi la produse posibil restaurabile) - retinem starea
    // gasita ca sa raportam "gasit pe site" excluzand trash-ul, la fel ca la
    // cardul "Total products".
    $wasTrashed = !$isNew && get_post_status($productId) === 'trash';
    $oldPrice = (!$isNew && !$isVariable) ? get_post_meta($productId, '_regular_price', true) : '';
    $oldPrice = $oldPrice !== '' ? (float) $oldPrice : null;

    // Optimizare majora: daca produsul exista deja si toate randurile lui
    // din feed sunt identice cu ultima rulare (hash comparat), sarim complet
    // peste rezolvare categorie/brand, verificare poze si save() - in
    // practica marea majoritate a produselor nu se schimba de la o noapte
    // la alta, iar munca aia (query-uri, save() cu toate hook-urile
    // WooCommerce) e ce facea rularile de noapte sa dureze ore in loc de
    // minute. Fara asta, verificam identic aceleasi date in gol, in fiecare
    // noapte, pentru cele ~3000 de produse nemodificate.
    // Daca feed-ul indica poze pentru produsul asta dar produsul de pe site
    // nu are nicio poza (ex. sarite din cauza plafonului de timp - vezi
    // papetarie_storefront_aperta_sideload_deadline()), NU consideram randul
    // "neschimbat" doar pentru ca datele din feed sunt identice - altfel
    // pozele lipsa ar ramane lipsa pentru totdeauna, pana cand altceva se
    // schimba la produsul asta (pret, stoc etc.) si strica hash-ul din
    // intamplare. Asa, incercarea se repeta la fiecare sincronizare pana
    // reuseste.
    $feedHasImages = false;
    // "Imagine produs" poate contine mai multe URL-uri despartite prin "|"
    // (vezi papetarie_storefront_aperta_image_urls) - numaram cate ofera
    // randul principal, ca sa putem detecta un produs caruia ii lipsesc
    // poze de galerie, nu doar unul fara nicio poza deloc.
    $expectedImageCount = papetarie_storefront_aperta_image_urls((string) ($first['Imagine produs'] ?? ''));
    $expectedImageCount = count($expectedImageCount);
    foreach ($rows as $row) {
        if (trim((string) ($row['Imagine produs'] ?? '')) !== '') {
            $feedHasImages = true;
            break;
        }
    }

    $rowHash = md5(serialize($rows));
    if (!$isNew) {
        $storedHash = get_post_meta($productId, '_pap_aperta_row_hash', true);
        // Produsele de la coșul de gunoi sunt excluse din reincercare: nu se
        // afiseaza nicaieri, deci n-are rost sa le descarcam poze, iar cele
        // cinci cochilii goale ramase din consolidare (fara nicio variatie, deci
        // fara nicio sansa sa capete vreodata imagine principala) ar fi
        // reprocesate integral la fiecare rulare, la infinit - inclusiv pana la
        // 88 de descarcari de poze fiecare (gasit live 2026-07-30).
        // NU e suficient sa verificam doar has_post_thumbnail() - asta e
        // adevarat dupa ce produsul a primit DOAR prima poza, chiar daca
        // feed-ul ofera si poze de galerie neluate niciodata (gasit live
        // 2026-08-09: ~46% din feed are 2+ poze per rand, dar has_post_thumbnail
        // facea "imagesLookComplete" adevarat dupa prima poza si sarea peste
        // restul definitiv). Numaram cate poze are deja produsul (thumbnail +
        // galerie) si le comparam cu cate ofera feed-ul.
        $currentImageCount = has_post_thumbnail($productId) ? 1 : 0;
        $galleryIds = get_post_meta($productId, '_product_image_gallery', true);
        if (is_string($galleryIds) && $galleryIds !== '') {
            $currentImageCount += count(array_filter(explode(',', $galleryIds)));
        }
        $imagesLookComplete = !$feedHasImages || $wasTrashed || $currentImageCount >= $expectedImageCount;
        if ($storedHash === $rowHash && $imagesLookComplete) {
            return [
                'product_id' => $productId,
                'is_new' => false,
                'is_variable' => $isVariable,
                'old_price' => $oldPrice,
                'new_price' => $oldPrice,
                'variations' => null,
                'was_trashed' => $wasTrashed,
                'price_warning' => null,
            ];
        }
    }

    $product = $isVariable ? new WC_Product_Variable($productId ?? 0) : new WC_Product_Simple($productId ?? 0);

    $product->set_name($name);
    $product->set_description($description);
    // Produsele noi (necunoscute pana acum) intra ca ciorna, nu publicate
    // direct - trebuie verificate manual in admin inainte sa apara pe site.
    // Produsele deja existente isi pastreaza starea curenta neatinsa (daca
    // un admin le-a scos manual de pe site, sincronizarea nu le repune).
    if ($isNew) {
        $product->set_status('draft');
    }
    $product->set_catalog_visibility('visible');

    $categoryId = papetarie_storefront_aperta_resolve_category($categoryPath);
    if ($categoryId > 0) {
        $product->set_category_ids([$categoryId]);
    }

    if ($brandName === '') {
        $brandName = papetarie_storefront_aperta_guess_brand_from_name($name);
    }
    $brandId = papetarie_storefront_aperta_resolve_brand($brandName);

    $productId = $product->save();

    if ($brandId > 0) {
        wp_set_object_terms($productId, [$brandId], 'product_brand');
    }

    update_post_meta($productId, '_pap_aperta_cod_produs', $codProdus);
    if ($isVariable) {
        // Curata o eventuala urma de SKU pe parinte (produs care a trecut
        // intre timp de la simplu la variabil), ca sa nu se suprapuna cu
        // SKU-ul vreunei variatii la cautarea prin _pap_aperta_sku.
        delete_post_meta($productId, '_pap_aperta_sku');
    } else {
        update_post_meta($productId, '_pap_aperta_sku', $primaryCodUnic);
    }

    $newPrice = null;
    $priceWarning = null;
    $variationsSummary = null;

    if ($isVariable) {
        $variationsSummary = papetarie_storefront_aperta_sync_variations($productId, $rows, $discountPercent, $name, $description, $categoryPath);
        if (!empty($variationsSummary['price_warnings'])) {
            $priceWarning = implode('; ', $variationsSummary['price_warnings']);
        }

        // Daca TOTI membrii familiei erau deja produse simple publicate
        // (sync_variations sare peste fiecare, vezi "Skip (don't crash)..."),
        // ramane un parinte nou-creat fara nicio variatie - o coaja goala,
        // fara poza, identica cu cele 15 gasite si curatate manual pe
        // staging 2026-07-28. Daca parintele e chiar nou (nu exista inainte
        // de randul asta), il stergem imediat in loc sa lasam o coaja
        // orfana pentru cineva sa o gaseasca mai tarziu.
        if ($isNew && $variationsSummary['total'] === 0) {
            wp_trash_post($productId);

            return [
                'product_id' => $productId,
                'is_new' => true,
                'is_variable' => true,
                'old_price' => null,
                'new_price' => null,
                'variations' => $variationsSummary,
                'was_trashed' => true,
                'price_warning' => !empty($variationsSummary['price_warnings']) ? implode('; ', $variationsSummary['price_warnings']) : null,
            ];
        }
    } else {
        $price = papetarie_storefront_aperta_safe_price((string) $first['Pret produs'], $discountPercent);

        if ($price === null) {
            // Nu scriem niciodata 0/negativ pe site: pastram pretul vechi la
            // un produs existent, sau il lasam nesetat la unul nou (oricum
            // intra ca ciorna) - vezi papetarie_storefront_aperta_safe_price().
            $priceWarning = sprintf(
                'preț invalid în feed ("%s") pentru %s — prețul NU a fost modificat',
                (string) $first['Pret produs'],
                $primaryCodUnic
            );
            papetarie_storefront_aperta_record_price_warning($primaryCodUnic, $name, (string) $first['Pret produs'], 'produs simplu');
            $newPrice = $oldPrice;
        } else {
            $newPrice = $price;
        }

        $simple = new WC_Product_Simple($productId);
        $simple->set_sku($primaryCodUnic);
        if ($price !== null) {
            $simple->set_regular_price((string) $price);
        }
        $simple->set_manage_stock(true);
        $simple->set_stock_status(papetarie_storefront_aperta_stock_status_from_text((string) $first['Status stoc']));

        $imageUrls = papetarie_storefront_aperta_image_urls((string) ($first['Imagine produs'] ?? ''));
        $imageIds = papetarie_storefront_aperta_sideload_images($imageUrls, $productId);
        $imageIds = papetarie_storefront_aperta_dedupe_image_ids($imageIds);
        if (!empty($imageIds)) {
            $simple->set_image_id($imageIds[0]);
            $simple->set_gallery_image_ids(array_slice($imageIds, 1));
        }

        // Descrierea e o sursa mai bogata si mai fiabila (liste structurate
        // "Eticheta: Valoare") decat titlul - o citim intai, apoi completam
        // cu ce mai gasim in titlu daca nu a fost deja acolo. Setate direct
        // pe produs (nu doar in taxonomia de filtrare de mai jos) ca sa
        // populeze si tab-ul "Specificatii" de pe pagina de produs, gol pana
        // acum la toate produsele simple (vezi build_extra_wc_attributes()).
        // Bullet-urile fara eticheta scurta (extract_plain_list_items) merg
        // si ele acolo, sub "Caracteristici" - fara exceptie, orice bullet
        // ajunge in Specificatii, nu ramane in Descriere.
        $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
        $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
        $allAttrs = $descAttrs + $textAttrs;
        $plainItems = papetarie_storefront_aperta_extract_plain_list_items($description, $descAttrs);
        if ($allAttrs || $plainItems) {
            $simple->set_attributes(papetarie_storefront_aperta_build_extra_wc_attributes($allAttrs, $plainItems));
        }

        $simple->save();

        if ($allAttrs) {
            papetarie_storefront_aperta_tag_multiple_attrs($productId, $allAttrs);
        }
    }

    update_post_meta($productId, '_pap_aperta_row_hash', $rowHash);

    if ($isNew) {
        papetarie_storefront_aperta_record_new_product($productId, $name);
    }

    return [
        'product_id' => $productId,
        'is_new' => $isNew,
        'is_variable' => $isVariable,
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'variations' => $variationsSummary,
        'was_trashed' => $wasTrashed,
        'price_warning' => $priceWarning,
    ];
}

/**
 * Formuleaza pe scurt ce s-a intamplat la un produs, pentru log-ul de progres.
 *
 * @param array{product_id: int, is_new: bool, is_variable: bool, old_price: ?float, new_price: ?float} $result
 */
/**
 * Descrie pe limba omului ce s-a intamplat la un produs, pentru log-ul de
 * progres vazut de administrator - nu doar "actualizat", ci exact ce anume.
 */
function papetarie_storefront_aperta_describe_upsert(array $result): string
{
    if ($result['is_new']) {
        if ($result['is_variable'] && $result['variations']) {
            return sprintf(
                /* translators: %d: number of variations (color/size) */
                __('produs nou, cu %d variante (culori/mărimi)', 'papetarie-storefront'),
                $result['variations']['total']
            );
        }

        return __('produs nou, adăugat pe site', 'papetarie-storefront');
    }

    if ($result['is_variable'] && $result['variations']) {
        $v = $result['variations'];

        if ($v['new'] > 0 && $v['changed'] > 0) {
            return sprintf(
                /* translators: 1: new variations count, 2: changed-price variations count, 3: total variations count */
                __('produs existent — %1$d variante noi, preț schimbat la %2$d din %3$d variante', 'papetarie-storefront'),
                $v['new'],
                $v['changed'],
                $v['total']
            );
        }

        if ($v['new'] > 0) {
            return sprintf(
                /* translators: 1: new variations count, 2: total variations count */
                __('produs existent — %1$d variante noi adăugate (din %2$d total)', 'papetarie-storefront'),
                $v['new'],
                $v['total']
            );
        }

        if ($v['changed'] > 0) {
            return sprintf(
                /* translators: 1: changed-price variations count, 2: total variations count */
                __('produs existent — preț schimbat la %1$d din %2$d variante', 'papetarie-storefront'),
                $v['changed'],
                $v['total']
            );
        }

        return sprintf(
            /* translators: %d: total variations count */
            __('produs existent — verificat, nimic schimbat (%d variante)', 'papetarie-storefront'),
            $v['total']
        );
    }

    if ($result['old_price'] !== null && $result['new_price'] !== null && $result['old_price'] !== $result['new_price']) {
        return sprintf(
            /* translators: 1: old price, 2: new price */
            __('produs existent — preț schimbat: %1$s lei → %2$s lei', 'papetarie-storefront'),
            $result['old_price'],
            $result['new_price']
        );
    }

    return __('produs existent — verificat, nimic schimbat', 'papetarie-storefront');
}

/**
 * @param array{product_id: int, is_new: bool, is_variable: bool, old_price: ?float, new_price: ?float, variations: ?array{total: int, new: int, changed: int}} $result
 */
function papetarie_storefront_aperta_upsert_is_changed(array $result): bool
{
    if ($result['is_new']) {
        return true;
    }

    if ($result['is_variable']) {
        return $result['variations'] && ($result['variations']['new'] > 0 || $result['variations']['changed'] > 0);
    }

    return $result['old_price'] !== $result['new_price'];
}

/**
 * @param array<int, array<string, string>> $rows
 * @return array{total: int, new: int, changed: int}
 */
function papetarie_storefront_aperta_sync_variations(int $productId, array $rows, float $discountPercent, string $name = '', string $description = '', string $categoryPath = ''): array
{
    $attributeName = '';
    foreach ($rows as $row) {
        if (trim((string) $row['Tip variant']) !== '') {
            $attributeName = trim((string) $row['Tip variant']);
            break;
        }
    }
    if ($attributeName === '') {
        $attributeName = 'Variantă';
    }

    $values = [];
    foreach ($rows as $row) {
        $value = trim((string) $row['Variant']);
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);

    // Atributele suplimentare extrase din descriere/nume (Format, Gramaj,
    // Ambalare etc.) - multe produse variabile au si ele liste structurate
    // in descriere, pierdute pana acum fiindca doar culoarea/varianta
    // ajungea pe produs (vezi build_extra_wc_attributes() mai sus).
    // Bullet-urile fara eticheta scurta merg si ele acolo, sub
    // "Caracteristici" - fara exceptie, orice bullet ajunge in
    // Specificatii, nu ramane in Descriere.
    $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
    $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
    $extraAttrs = $descAttrs + $textAttrs;
    unset($extraAttrs[$attributeName]);
    $plainItems = papetarie_storefront_aperta_extract_plain_list_items($description, $descAttrs);

    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($attributeName);
    $attribute->set_options($values);
    $attribute->set_visible(true);
    $attribute->set_variation(true);

    $variable = new WC_Product_Variable($productId);
    $variable->set_attributes(array_merge(
        [$attribute],
        papetarie_storefront_aperta_build_extra_wc_attributes($extraAttrs, $plainItems)
    ));
    $variable->save();

    // Etichetare pentru filtrare pe pagina de arhiva (separat de atributele
    // WooCommerce de mai sus, care alimenteaza tab-ul "Specificatii" - asta
    // e doar pentru query-ul de filtrare de pe pagina de arhiva).
    papetarie_storefront_aperta_tag_variant_and_extra_attrs($productId, $attributeName, $values, $extraAttrs);

    $attributeKey = sanitize_title($attributeName);
    $firstImageId = null;
    $galleryImageIds = [];
    $totalVariations = 0;
    $newVariations = 0;
    $changedVariations = 0;
    $priceWarnings = [];

    foreach ($rows as $row) {
        $codUnic = trim((string) $row['Cod unic']);
        if ($codUnic === '') {
            continue;
        }

        $variantValue = trim((string) $row['Variant']);
        $variationId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);

        // SKU-ul poate fi deja atasat unui produs SIMPLU (nu o variatie) -
        // de exemplu un produs importat individual inainte ca acest cod sa
        // fie recunoscut ca parte a unei familii de culori/marimi (vezi
        // consolidate_singleton_colors). WC_Product_Variation arunca eroare
        // daca primeste ID-ul unui post care nu e chiar 'product_variation'
        // - confirmat live pe staging 2026-07-28 ("Pix Klick-fix Schneider
        // alb/negru", deja publicate individual). Migrarea lor reala intr-o
        // variatie e o operatie separata (task de consolidare) - aici doar
        // sarim peste randul asta, ca sa nu stricam produsul vechi existent
        // si sa nu crape tot lotul incercand sa-l tratam gresit ca variatie.
        if ($variationId !== null && get_post_type($variationId) !== 'product_variation') {
            $pendingMigrations = get_option('pap_aperta_pending_variation_migrations', []);
            $pendingMigrations[$codUnic] = [
                'existing_post_id' => $variationId,
                'existing_post_type' => get_post_type($variationId),
                'parent_product_id' => $productId,
                'variant' => $variantValue,
                'found_at' => current_time('mysql'),
            ];
            update_option('pap_aperta_pending_variation_migrations', $pendingMigrations, false);
            continue;
        }

        // SKU-ul poate fi deja o variatie REALA, dar a altui produs-parinte
        // deja PUBLICAT (gasit live 2026-07-28: lavete Kimberly-Clark,
        // markere Schneider Paint-It - fiecare culoare deja e propriul
        // produs variabil publicat, cu 1 singura variatie). Re-parentarea
        // ei catre noul produs comun ar lasa in urma un parinte vechi
        // PUBLICAT, live, fara nicio variatie - o "gaura" vizibila pe site.
        // Sarim si aici, la fel ca la produsele simple - migrarea asta ramane
        // manuala/deliberata (tools/migrate-legacy-simple-to-variation.php).
        if ($variationId !== null) {
            $oldParentId = (int) get_post_field('post_parent', $variationId);
            if ($oldParentId > 0 && $oldParentId !== $productId && get_post_status($oldParentId) === 'publish') {
                $pendingMigrations = get_option('pap_aperta_pending_variation_migrations', []);
                $pendingMigrations[$codUnic] = [
                    'existing_post_id' => $variationId,
                    'existing_post_type' => 'product_variation',
                    'existing_parent_id' => $oldParentId,
                    'existing_parent_status' => 'publish',
                    'parent_product_id' => $productId,
                    'variant' => $variantValue,
                    'found_at' => current_time('mysql'),
                ];
                update_option('pap_aperta_pending_variation_migrations', $pendingMigrations, false);
                continue;
            }
        }

        $isNewVariation = $variationId === null;
        $oldPrice = !$isNewVariation ? get_post_meta($variationId, '_regular_price', true) : '';
        $oldPrice = $oldPrice !== '' ? (float) $oldPrice : null;

        $variation = new WC_Product_Variation($variationId ?? 0);
        $variation->set_parent_id($productId);
        $variation->set_attributes([$attributeKey => $variantValue]);
        $variation->set_sku($codUnic);

        $price = papetarie_storefront_aperta_safe_price((string) $row['Pret produs'], $discountPercent);

        if ($price === null) {
            // Nu scriem niciodata 0/negativ pe site: pastram pretul vechi al
            // variantei (sau il lasam nesetat daca e noua) - vezi
            // papetarie_storefront_aperta_safe_price().
            $priceWarnings[] = sprintf(
                'variantă "%s" (SKU %s): preț invalid în feed ("%s") — prețul NU a fost modificat',
                $variantValue,
                $codUnic,
                (string) $row['Pret produs']
            );
            papetarie_storefront_aperta_record_price_warning($codUnic, $name . ' — ' . $variantValue, (string) $row['Pret produs'], 'variantă');
        } else {
            $variation->set_regular_price((string) $price);
        }

        $totalVariations++;
        if ($isNewVariation) {
            $newVariations++;
        } elseif ($price !== null && ($oldPrice === null || $oldPrice !== $price)) {
            $changedVariations++;
        }
        $variation->set_manage_stock(true);
        $variation->set_stock_status(papetarie_storefront_aperta_stock_status_from_text((string) $row['Status stoc']));

        // WC_Product_Variation nu suporta galerie proprie - prima poza a
        // variantei devine imaginea ei, restul intra in galeria parintelui.
        $imageUrls = papetarie_storefront_aperta_image_urls((string) ($row['Imagine produs'] ?? ''));
        $imageIds = papetarie_storefront_aperta_sideload_images($imageUrls, $productId);
        if (!empty($imageIds)) {
            $variation->set_image_id($imageIds[0]);
            if ($firstImageId === null) {
                $firstImageId = $imageIds[0];
            }
            foreach (array_slice($imageIds, 1) as $extraId) {
                $galleryImageIds[$extraId] = true;
            }
        }

        $savedVariationId = $variation->save();
        update_post_meta($savedVariationId, '_pap_aperta_sku', $codUnic);
    }

    if ($firstImageId !== null) {
        // Dedup pe grupul intreg (thumbnail parinte + galeria comuna), nu
        // doar per-varianta - Aperta poate trimite exact aceeasi poza (ex.
        // coperta produsului) sub URL-uri diferite pentru variante diferite,
        // iar fiecare ajunge in galeria comuna prin cate un apel separat de
        // sideload_images(). Thumbnail-ul FIECAREI variatii ramane neatins
        // (setat separat mai sus) - doar galeria comuna a parintelui se
        // curata de duplicate.
        $combinedIds = papetarie_storefront_aperta_dedupe_image_ids(array_merge([$firstImageId], array_keys($galleryImageIds)));

        $parent = new WC_Product_Variable($productId);
        $parent->set_image_id($combinedIds[0]);
        $parent->set_gallery_image_ids(array_slice($combinedIds, 1));
        $parent->save();
    }

    return [
        'total' => $totalVariations,
        'new' => $newVariations,
        'changed' => $changedVariations,
        'price_warnings' => $priceWarnings,
    ];
}

/**
 * Aplica doar stocul numeric (fara sa atinga pret/descriere/categorie).
 *
 * @param array<string, array{stock: int}> $stockByCodUnic
 */
/**
 * @return array<int, array{sku: string, name: string, changed: bool, trashed: bool}> produsele chiar afectate (SKU-uri gasite pe site)
 */
function papetarie_storefront_aperta_apply_stock(array $stockByCodUnic): array
{
    $applied = [];

    foreach ($stockByCodUnic as $codUnic => $data) {
        $postId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);
        if ($postId === null) {
            continue;
        }

        // find_by_sku_meta cauta si prin trash (ca sa tina stocul la zi la
        // produse posibil restaurabile) - retinem starea gasita ca sa
        // raportam "gasit pe site" excluzand trash-ul, la fel ca la cardul
        // "Total products".
        $isTrashed = get_post_status($postId) === 'trash';

        $quantity = (int) $data['stock'];

        // Verificare rapida (doar postmeta, fara sa incarcam tot obiectul
        // WC_Product) inainte sa decidem daca chiar trebuie scris ceva -
        // aceeasi idee ca la produse: marea majoritate a stocurilor nu se
        // schimba intre doua verificari orare, deci evitam save()-ul greu
        // pentru ele.
        $oldQuantityRaw = get_post_meta($postId, '_stock', true);
        $oldQuantity = $oldQuantityRaw === '' ? null : (int) $oldQuantityRaw;
        $oldStatus = get_post_meta($postId, '_stock_status', true);
        $expectedStatus = $quantity > 0 ? 'instock' : ($oldStatus === 'onbackorder' ? 'onbackorder' : 'outofstock');

        if ($oldQuantity === $quantity && $oldStatus === $expectedStatus) {
            $applied[] = [
                'sku' => $codUnic,
                // get_post_field (nu get_the_title) - get_the_title trece prin
                // filtrul 'the_title' (wptexturize), care transforma "-" in
                // entitatea HTML "&#8211;"; cum acest text ajunge in JS printr-un
                // simplu text node (nu html()), entitatea ar aparea literal pe ecran.
                'name' => get_post_field('post_title', $postId) . ' (stoc: ' . $quantity . ' → ' . $quantity . ')',
                'changed' => false,
                'trashed' => $isTrashed,
            ];
            continue;
        }

        $product = wc_get_product($postId);
        if (!($product instanceof WC_Product)) {
            continue;
        }

        $currentPostStatus = get_post_status($postId);

        $product->set_manage_stock(true);
        $product->set_stock_quantity($quantity);
        $product->set_stock_status($expectedStatus);
        $product->save();

        // Decizie Lavinia 2026-08-07: un produs PUBLICAT care ajunge la stoc 0
        // (sau onbackorder, care tot inseamna 0 bucati fizice) trece automat
        // la draft - nu vrem clienti care comanda ceva indisponibil. Stocul
        // (cantitate + status) tot se actualizeaza normal mai sus, indiferent
        // de post_status - doar vizibilitatea pe site se schimba.
        $isZeroStock = in_array($expectedStatus, ['outofstock', 'onbackorder'], true);
        if ($isZeroStock && $currentPostStatus === 'publish') {
            wp_update_post(['ID' => $postId, 'post_status' => 'draft']);

            // Acelasi banner vizibil ca la produsele disparute din feed (vezi
            // papetarie_storefront_aperta_vanished_notice()) - decizie user
            // 2026-09-04: motivul mutarii in ciorna trebuie sa fie clar,
            // indiferent de cauza (stoc 0 sau disparut din feed), nu doar de
            // ghicit sau de cautat prin log-uri.
            update_post_meta($postId, '_pap_aperta_vanished_note', sprintf(
                'Mutat automat în ciornă de sincronizarea Aperta pe %s: stocul a scăzut de la %s la %d bucăți (status: %s).',
                date('d.m.Y H:i'),
                $oldQuantity === null ? 'necunoscut' : (string) $oldQuantity,
                $quantity,
                $expectedStatus === 'onbackorder' ? 'precomandă' : 'stoc epuizat'
            ));
        } elseif (!$isZeroStock && $currentPostStatus === 'draft'
            && in_array($oldStatus, ['outofstock', 'onbackorder'], true)) {
            // Revenit pe stoc, dar ramane draft - publicarea e decizie
            // manuala. Il notam intr-o lista citita de job-ul de notificare
            // zilnica (vezi docs/plan-auto-draft-stoc-zero.md).
            $restockedToday = get_option('pap_restocked_today', []);
            $restockedToday[$postId] = time();
            update_option('pap_restocked_today', $restockedToday);
        }

        // Aceeasi regula, extinsa la produsele cu variante: daca varianta de
        // mai sus e ULTIMA ramasa cumparabila a familiei ei (parintele are
        // alte culori, dar toate sunt acum epuizate), ascundem si parintele -
        // decizie user 2026-09-04: "daca nu poate fi cumparat acum, nu
        // trebuie sa fie vizibil pe site", aceeasi regula ca la stoc 0 pe
        // produs simplu, doar ca aici cauza e "toate variantele lui, nu doar
        // el". Cazul normal (macar o culoare inca cumparabila) NU e atins -
        // parintele ramane vizibil neschimbat, exact ca acum.
        if ($product instanceof WC_Product_Variation) {
            $parentId = $product->get_parent_id();
            $parentStatus = $parentId ? get_post_status($parentId) : false;
            $parent = ($parentId && in_array($parentStatus, ['publish', 'draft'], true)) ? wc_get_product($parentId) : null;

            if ($parent instanceof WC_Product_Variable) {
                $anyInStock = false;
                foreach ($parent->get_children() as $siblingId) {
                    $sibling = ((int) $siblingId === $postId) ? $product : wc_get_product($siblingId);
                    if ($sibling && $sibling->get_stock_status() === 'instock') {
                        $anyInStock = true;
                        break;
                    }
                }

                if (!$anyInStock && $parentStatus === 'publish') {
                    wp_update_post(['ID' => $parentId, 'post_status' => 'draft']);
                    update_post_meta($parentId, '_pap_aperta_vanished_note', sprintf(
                        'Mutat automat în ciornă de sincronizarea Aperta pe %s: toate variantele (culorile) au ajuns pe stoc epuizat.',
                        date('d.m.Y H:i')
                    ));
                } elseif ($anyInStock && $parentStatus === 'draft'
                    && get_post_meta($parentId, '_pap_aperta_vanished_note', true) !== '') {
                    // Revenit pe stoc (macar o culoare), dar ramane draft -
                    // publicarea e decizie manuala, la fel ca la produsele
                    // simple. Verificam nota, nu doar statusul, ca sa nu
                    // notificam din greseala un produs pus manual pe draft
                    // dintr-un alt motiv, nelegat de stoc.
                    $restockedToday = get_option('pap_restocked_today', []);
                    $restockedToday[$parentId] = time();
                    update_option('pap_restocked_today', $restockedToday);
                }
            }
        }

        $oldLabel = $oldQuantity === null ? '—' : (string) $oldQuantity;
        $applied[] = [
            'sku' => $codUnic,
            'name' => $product->get_name() . ' (stoc: ' . $oldLabel . ' → ' . $quantity . ')',
            'changed' => true,
            'trashed' => $isTrashed,
        ];
    }

    return $applied;
}

/**
 * Checkpoint pe disc (nu error_log - inaccesibil pe gazduirea shared, vezi
 * investigarea 2026-07-31) pentru pasii DINAINTE de prima bucata (descarcare
 * + parsare feed), ca sa vedem exact unde moare rularea daca serverul omoara
 * din nou procesul fara nicio urma catchable. Scriere imediata (append, fara
 * buffer) - daca procesul e omorat la mijloc, ultimul checkpoint scris tot
 * ramane pe disc.
 */
function papetarie_storefront_aperta_debug_checkpoint(string $label, float $startedAt): void
{
    if (!PAP_APERTA_DEBUG_TIMING) {
        return;
    }

    $line = sprintf(
        "[%s] %s elapsed=%.2fs mem=%.1fMB peak=%.1fMB\n",
        date('d.m.Y H:i:s'),
        $label,
        microtime(true) - $startedAt,
        memory_get_usage(true) / 1048576,
        memory_get_peak_usage(true) / 1048576
    );

    $dir = papetarie_storefront_aperta_feed_dir();
    wp_mkdir_p($dir);
    file_put_contents($dir . '/debug-timing.log', $line, FILE_APPEND);
}

/**
 * Procesare in bucati (Action Scheduler) - fiecare rulare proceseaza cat
 * incape sub PAP_APERTA_PRODUCTS_CHUNK_TIME_BUDGET_SECONDS, apoi programeaza
 * urmatoarea bucata (vezi sync_products_chunk_cb).
 */
function papetarie_storefront_aperta_sync_products_start_cb(string $trigger = 'auto'): void
{
    // O rulare anterioara e deja in plina procesare a bucatilor - nu pornim
    // una noua peste ea (ex. programul zilnic a picat exact cand o rulare
    // manuala anterioara inca proceseaza).
    if (papetarie_storefront_aperta_progress_has_running_chunks('products')) {
        return;
    }

    $startedAt = microtime(true);
    if (PAP_APERTA_DEBUG_TIMING) {
        // Curatam logul la inceputul fiecarei rulari noi, ca fisierul sa nu
        // creasca la nesfarsit peste mai multe nopti si sa fie usor de citit
        // a doua zi (contine doar ultima rulare).
        $dir = papetarie_storefront_aperta_feed_dir();
        wp_mkdir_p($dir);
        file_put_contents($dir . '/debug-timing.log', '');
    }
    papetarie_storefront_aperta_debug_checkpoint('products_start_cb: intrare', $startedAt);

    // Marcam "running" IMEDIAT, sincron, inainte de orice altceva - altfel,
    // daca mai multe sloturi restante (ex. dintr-un backlog WP-Cron) sunt
    // procesate unul dupa altul in aceeasi rulare a cozii, fiecare ar trece
    // de verificarea de mai sus inainte ca vreunul sa apuce sa marcheze
    // starea, pornind mai multe lanturi paralele care se calca pe picioare.
    papetarie_storefront_aperta_progress_start('products', 0, $trigger);

    papetarie_storefront_aperta_debug_checkpoint('inainte de download_feed', $startedAt);
    $downloaded = papetarie_storefront_aperta_download_feed('feed');
    papetarie_storefront_aperta_debug_checkpoint('dupa download_feed ok=' . ($downloaded ? '1' : '0'), $startedAt);

    if (!$downloaded) {
        // Feed-ul nu s-a descarcat (Aperta jos/lent sau eroare de retea) - nu
        // continuam cu parsarea unui feed.csv vechi/inexistent ca si cum ar
        // fi actual (asta ar sincroniza date invechite in tacere). Rularea
        // ramane "running" in progres, dar fara nicio bucata programata -
        // progress_for_display() o va arata drept "intrerupta" dupa fereastra
        // de gratie de 5 minute (vezi acolo).
        papetarie_storefront_aperta_debug_checkpoint('ABANDONAT: download_feed a esuat', $startedAt);
        return;
    }

    // Parsam + consolidam feed-ul o singura data aici, nu la fiecare bucata.
    $grouped = papetarie_storefront_aperta_read_products_grouped();
    papetarie_storefront_aperta_debug_checkpoint('dupa read_products_grouped groups=' . count($grouped), $startedAt);

    papetarie_storefront_aperta_cache_products_grouped($grouped);
    papetarie_storefront_aperta_debug_checkpoint('dupa cache_products_grouped', $startedAt);

    as_enqueue_async_action('pap_aperta_sync_products_chunk', [0], 'aperta-sync');
    papetarie_storefront_aperta_debug_checkpoint('dupa enqueue chunk 0 - iesire', $startedAt);
}

/**
 * Verificare suplimentara de siguranta, pe langa timp/numar de produse -
 * memoria PHP creste progresiv cu fiecare produs incarcat in acelasi proces
 * (cache-uri interne WP/WC ce nu se elibereaza intre produse), confirmat
 * local (2026-07-28): o bucla neintrerupta de bucati in ACELASI proces PHP a
 * epuizat memory_limit dupa ~900 produse. Action Scheduler are propriul
 * prag de memorie intre actiuni diferite, dar verificam si aici, in bucla
 * proprie, ca sa oprim bucata curenta din timp daca memoria creste neasteptat
 * de repede (ex. poze foarte mari), nu doar sa ne bazam pe pragul din urma.
 *
 * Comparam fata de un nivel de referinta (memoria chiar dupa ce s-a incarcat
 * WordPress/WooCommerce, INAINTE de primul produs din bucata), nu procentul
 * absolut din memory_limit - bootstrap-ul singur poate ocupa deja 80-90% din
 * memory_limit pe un mediu cu limita mica (confirmat local: 110M din 128M
 * inainte de orice produs), ceea ce ar opri bucata dupa un singur produs desi
 * nu exista niciun risc real de epuizare.
 */
function papetarie_storefront_aperta_memory_budget_exceeded(int $baselineBytes): bool
{
    $limit = ini_get('memory_limit');
    if ($limit === false || $limit === '-1' || $limit === '') {
        return false;
    }

    $unit = strtolower(substr($limit, -1));
    $value = (int) $limit;
    $limitBytes = match ($unit) {
        'g' => $value * 1024 * 1024 * 1024,
        'm' => $value * 1024 * 1024,
        'k' => $value * 1024,
        default => (int) $limit,
    };

    if ($limitBytes <= 0) {
        return false;
    }

    $current = memory_get_usage(true);

    // Prag absolut: opreste indiferent de baseline daca a mai ramas foarte
    // putina memorie libera pana la limita reala.
    if ($limitBytes - $current <= 16 * 1024 * 1024) {
        return true;
    }

    // Prag relativ: opreste daca INSASI bucata curenta a acumulat o crestere
    // mare fata de cum a pornit (semn ca ceva din bucata asta creste memoria
    // neobisnuit de repede, indiferent cat de generoasa e limita totala).
    return ($current - $baselineBytes) >= 48 * 1024 * 1024;
}

function papetarie_storefront_aperta_sync_products_chunk_cb(int $offset = 0): void
{
    $grouped = papetarie_storefront_aperta_read_products_grouped_cached();
    $codes = array_keys($grouped);
    $total = count($codes);

    if ($offset === 0) {
        papetarie_storefront_aperta_progress_start('products', $total);
    }

    $startedAt = microtime(true);
    $memoryBaseline = memory_get_usage(true);
    $items = [];
    $processed = 0;

    // Vezi papetarie_storefront_aperta_sideload_deadline() - 260s lasa o
    // marja de 40s sub pragul de 300s al Action Scheduler pentru bootstrap-ul
    // WP/WC de dinainte de $startedAt si pentru finalizarea bucatii dupa
    // ultima poza. Resetat la null in finally, ca sa nu afecteze alti
    // apelanti (tool-uri manuale, migrari) care nu au acest risc de timeout.
    papetarie_storefront_aperta_sideload_deadline($startedAt + 260);

    try {
    for ($i = $offset; $i < $total; $i++) {
        // Vezi PAP_APERTA_HEAVY_PRODUCT_ROWS - un produs cu foarte multe
        // variante primeste o bucata doar pentru el, ca sa aiba tot bugetul
        // la dispozitie si sa nu poata trece singur peste pragul de 300s.
        // Conditia de "needs_work" e esentiala: exista 34 de produse cu 12+
        // variante, iar in mod normal niciunul nu se schimba de la o noapte la
        // alta. Fara ea, fiecare ar rupe bucata degeaba (un produs neschimbat
        // costa ~0.05s), transformand o rulare de 3 bucati in 37 - exact boala
        // pe care o rezolvam la stoc.
        $isHeavy = count($grouped[$codes[$i]]) >= PAP_APERTA_HEAVY_PRODUCT_ROWS
            && papetarie_storefront_aperta_product_needs_work($grouped[$codes[$i]]);
        if ($isHeavy && $processed > 0) {
            break;
        }

        papetarie_storefront_aperta_debug_checkpoint(
            sprintf('chunk offset=%d item=%d/%d cod=%s - START', $offset, $i, $total, (string) ($grouped[$codes[$i]][0]['Cod produs'] ?? $codes[$i])),
            $startedAt
        );

        // Plasa de siguranta: un singur produs cu date neasteptate (ex. o
        // coliziune de tip post nedetectata de garda de mai sus, sau orice
        // alta exceptie neprevazuta din WooCommerce) nu mai are voie sa
        // opreasca sincronizarea pentru TOATE produsele ramase dupa el -
        // exact ce se intampla inainte (fara try/catch aici, o exceptie
        // scapa din bucla, intreaga bucata pica "esuata" in Action Scheduler
        // si $nextOffset nu mai apuca sa fie programat, deci sincronizarea
        // ramane blocata definitiv la acelasi offset). Rezultatul sintetic
        // de mai jos are aceeasi forma ca cea folosita la "randuri goale"
        // (linia ~2070) - deja verificata sigura de describe_upsert()/
        // upsert_is_changed() cu is_new=false, is_variable=false.
        try {
            $result = papetarie_storefront_aperta_upsert_product($grouped[$codes[$i]]);
        } catch (Throwable $e) {
            papetarie_storefront_aperta_debug_checkpoint(
                sprintf('chunk offset=%d item=%d/%d cod=%s - EROARE: %s', $offset, $i, $total, (string) ($grouped[$codes[$i]][0]['Cod produs'] ?? $codes[$i]), $e->getMessage()),
                $startedAt
            );
            $result = [
                'product_id' => 0,
                'is_new' => false,
                'is_variable' => false,
                'old_price' => null,
                'new_price' => null,
                'variations' => null,
                'was_trashed' => false,
                'price_warning' => null,
                'sync_error' => $e->getMessage(),
            ];
        }

        papetarie_storefront_aperta_debug_checkpoint(
            sprintf('chunk offset=%d item=%d/%d cod=%s - DONE', $offset, $i, $total, (string) ($grouped[$codes[$i]][0]['Cod produs'] ?? $codes[$i])),
            $startedAt
        );

        $itemDescription = isset($result['sync_error'])
            ? ('sărit, eroare: ' . $result['sync_error'])
            : papetarie_storefront_aperta_describe_upsert($result);
        if (!empty($result['price_warning'])) {
            $itemDescription .= ' — ⚠ ' . $result['price_warning'];
        }

        $items[] = [
            'sku' => trim((string) $grouped[$codes[$i]][0]['Cod unic']),
            'name' => trim((string) $grouped[$codes[$i]][0]['Denumire produs']) . ' (' . $itemDescription . ')',
            'changed' => papetarie_storefront_aperta_upsert_is_changed($result),
            'trashed' => $result['was_trashed'],
        ];
        $processed++;

        // Produsul greu a consumat probabil o bucata buna din buget - inchidem
        // bucata aici, ca urmatoarele produse sa porneasca de la zero.
        if ($isHeavy) {
            break;
        }
        if ($processed >= PAP_APERTA_PRODUCTS_CHUNK_MAX_ITEMS) {
            break;
        }
        if ((microtime(true) - $startedAt) >= PAP_APERTA_PRODUCTS_CHUNK_TIME_BUDGET_SECONDS) {
            break;
        }
        if (papetarie_storefront_aperta_memory_budget_exceeded($memoryBaseline)) {
            break;
        }
    }
    } finally {
        papetarie_storefront_aperta_sideload_deadline(null);
    }

    papetarie_storefront_aperta_progress_tick('products', $processed, $items);

    $nextOffset = $offset + $processed;
    if ($nextOffset < $total) {
        // Scadenta imediata - vezi nota de la bucatile de stoc.
        as_schedule_single_action(time(), 'pap_aperta_sync_products_chunk', [$nextOffset], 'aperta-sync');
    } else {
        update_option('pap_aperta_last_full_sync', time());
        papetarie_storefront_aperta_progress_finish('products');
    }
}

function papetarie_storefront_aperta_sync_stock_start_cb($hour = null, string $trigger = 'auto'): void
{
    if (papetarie_storefront_aperta_progress_has_running_chunks('stock')) {
        return;
    }

    papetarie_storefront_aperta_progress_start('stock', 0, $trigger);

    papetarie_storefront_aperta_download_feed('stoc');
    as_enqueue_async_action('pap_aperta_sync_stock_chunk', [0], 'aperta-sync');
}

function papetarie_storefront_aperta_sync_stock_chunk_cb(int $offset = 0): void
{
    $stockMap = papetarie_storefront_aperta_read_stock_map();
    $codes = array_keys($stockMap);
    $total = count($codes);

    if ($offset === 0) {
        papetarie_storefront_aperta_progress_start('stock', $total);
    }

    // Vezi PAP_APERTA_STOCK_CHUNK_TIME_BUDGET_SECONDS - procesam in sub-loturi
    // de 100 (granularitatea la care verificam bugetul), dar continuam cat
    // timp incape, ca sa nu mai fie nevoie de 54 de bucati separate, fiecare
    // asteptand urmatoarea declansare a cronului.
    $startedAt = microtime(true);
    $memoryBaseline = memory_get_usage(true);
    $processed = 0;
    $applied = [];

    while ($offset + $processed < $total) {
        $slice = array_slice($codes, $offset + $processed, PAP_APERTA_CHUNK_SIZE * 4);
        if (empty($slice)) {
            break;
        }

        $chunkMap = [];
        foreach ($slice as $code) {
            $chunkMap[$code] = $stockMap[$code];
        }

        $applied = array_merge($applied, papetarie_storefront_aperta_apply_stock($chunkMap));
        $processed += count($slice);

        if ($processed >= PAP_APERTA_STOCK_CHUNK_MAX_ITEMS) {
            break;
        }
        if ((microtime(true) - $startedAt) >= PAP_APERTA_STOCK_CHUNK_TIME_BUDGET_SECONDS) {
            break;
        }
        if (papetarie_storefront_aperta_memory_budget_exceeded($memoryBaseline)) {
            break;
        }
    }

    papetarie_storefront_aperta_progress_tick('stock', $processed, $applied);

    if ($offset + $processed < $total) {
        // Scadenta imediata (nu +5s): asa aceeasi trecere a cozii Action
        // Scheduler o poate prelua pe loc, fara sa astepte cronul urmator.
        as_schedule_single_action(time(), 'pap_aperta_sync_stock_chunk', [$offset + $processed], 'aperta-sync');
    } else {
        update_option('pap_aperta_last_stock_sync', time());
        papetarie_storefront_aperta_progress_finish('stock');
    }
}

add_action('pap_aperta_sync_products_start', 'papetarie_storefront_aperta_sync_products_start_cb');
add_action('pap_aperta_sync_products_chunk', 'papetarie_storefront_aperta_sync_products_chunk_cb');
// accepted_args=2 explicit - implicit WordPress trimite doar primul argument
// din actiune, deci $trigger (al 2-lea parametru) nu ar ajunge niciodata la
// callback; fara asta, rularile pornite manual din "Ruleaza acum" erau
// etichetate gresit "Automat (program)" in istoric, mereu.
add_action('pap_aperta_sync_stock_start', 'papetarie_storefront_aperta_sync_stock_start_cb', 10, 2);
add_action('pap_aperta_sync_stock_chunk', 'papetarie_storefront_aperta_sync_stock_chunk_cb');

/**
 * Programeaza sync-urile recurente, cu un mic delay fata de orele oficiale
 * ale sursei (produsele se actualizeaza la 2:50, stocul la 1:10 si din ora
 * in ora intre 9:10-17:10).
 */
/**
 * Aperta isi publica orele de actualizare (2:50 pt. produse, 1:10 + din ora
 * in ora 9:10-17:10 pt. stoc) in ora Romaniei, nu UTC. Serverul nostru ruleaza
 * in UTC (confirmat: PHP + setarea de timezone din WP sunt ambele UTC), deci
 * un simplu strtotime('today HH:MM:00') calculeaza ora gresita cu 2-3 ore
 * (offset-ul UTC+2/+3 al Romaniei, in functie de ora de vara/iarna) - gasit
 * 2026-07-27. Aceasta functie converteste explicit ora Romaniei catre UTC,
 * indiferent de fusul orar implicit al serverului.
 */
function papetarie_storefront_aperta_romania_time_today(string $time): int
{
    $dt = new DateTime('today ' . $time, new DateTimeZone('Europe/Bucharest'));

    return $dt->getTimestamp();
}

/**
 * Auto-vindecare: daca o rulare recurenta esueaza (ex. feed-ul Aperta a
 * raspuns prea incet intr-o noapte si Action Scheduler a marcat actiunea
 * "blocata" ca esuata dupa timeout-ul lui intern), Action Scheduler NU mai
 * programeaza singur urmatoarea aparitie - "Urmatoarea rulare" ramane
 * "niciodata" la nesfarsit pana observa cineva manual. Functia de mai jos
 * e deja idempotenta per-actiune (verifica as_next_scheduled_action inainte
 * sa (re)programeze fiecare cron) - inainte avea un "paznic" (optiunea
 * pap_aperta_cron_registered) care o oprea sa mai ruleze deloc dupa prima
 * inregistrare reusita, exact ce impiedica auto-vindecarea. Throttle cu
 * tranzient (nu la fiecare cerere) ca verificarea sa nu coste nimic pe
 * majoritatea paginilor.
 */
function papetarie_storefront_aperta_schedule_cron(): void
{
    if (!function_exists('as_schedule_recurring_action')) {
        return;
    }

    if (get_transient('pap_aperta_cron_healthcheck') !== false) {
        return;
    }
    set_transient('pap_aperta_cron_healthcheck', 1, 10 * MINUTE_IN_SECONDS);

    // Mutex real la nivel de baza de date - fara el, doua cereri concurente
    // (posibil oricand exista trafic real pe site, chiar si la miezul
    // noptii) puteau trece AMANDOUA de verificarea "exista deja o actiune
    // programata?" inainte ca vreuna sa apuce sa o creeze, rezultand in 3-4
    // actiuni pap_aperta_sync_products_start duplicate pentru exact acelasi
    // moment (confirmat live 2026-08-01 SI 2026-08-02, acelasi tipar in
    // ambele nopti). GET_LOCK cu timeout 0 face ca doar UN singur proces sa
    // poata intra aici la un moment dat - restul ies imediat. MySQL elibereaza
    // singur lock-ul daca procesul moare inainte sa apuce sa-l elibereze
    // explicit (la inchiderea conexiunii), deci nu exista risc de blocare
    // permanenta.
    global $wpdb;
    $lockName = 'pap_aperta_schedule_cron';
    $gotLock = (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lockName));

    if ($gotLock !== 1) {
        return;
    }

    try {
        $delay = PAP_APERTA_SYNC_DELAY_MINUTES * MINUTE_IN_SECONDS;

        if (!as_next_scheduled_action('pap_aperta_sync_products_start', [], 'aperta-sync')) {
            $timestamp = papetarie_storefront_aperta_romania_time_today('02:50:00') + $delay;
            if ($timestamp < time()) {
                $timestamp += DAY_IN_SECONDS;
            }
            as_schedule_recurring_action($timestamp, DAY_IN_SECONDS, 'pap_aperta_sync_products_start', [], 'aperta-sync');
        }

        foreach ([1, 9, 10, 11, 12, 13, 14, 15, 16, 17] as $hour) {
            $args = ['hour' => $hour];
            if (as_next_scheduled_action('pap_aperta_sync_stock_start', $args, 'aperta-sync')) {
                continue;
            }

            $timestamp = papetarie_storefront_aperta_romania_time_today(sprintf('%02d:10:00', $hour)) + $delay;
            if ($timestamp < time()) {
                $timestamp += DAY_IN_SECONDS;
            }
            as_schedule_recurring_action($timestamp, DAY_IN_SECONDS, 'pap_aperta_sync_stock_start', $args, 'aperta-sync');
        }

        // Watchdog: o rulare de produse moarta pe parcurs (proces omorat de
        // server) ramanea blocata pana a doua zi - nimic n-o reincerca activ
        // intre timp (vezi progress_reset_if_abandoned()). Aici o eliberam si
        // programam o reincercare in ~1 minut. schedule_cron() ruleaza oricum
        // la fiecare ~10 minute (throttle-ul de mai sus), deci o rulare moarta
        // e reincercata in maximum 10-15 minute, nu a doua zi.
        if (papetarie_storefront_aperta_progress_reset_if_abandoned('products')) {
            as_schedule_single_action(time() + MINUTE_IN_SECONDS, 'pap_aperta_sync_products_start', [], 'aperta-sync');
        }
    } finally {
        $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
    }

    if (!as_next_scheduled_action('pap_aperta_send_restock_digest', [], 'aperta-sync')) {
        $timestamp = papetarie_storefront_aperta_romania_time_today('18:00:00');
        if ($timestamp < time()) {
            $timestamp += DAY_IN_SECONDS;
        }
        as_schedule_recurring_action($timestamp, DAY_IN_SECONDS, 'pap_aperta_send_restock_digest', [], 'aperta-sync');
    }

    // 01:30 - dupa ce feed.csv de noapte s-a redescarcat (~00:43-01:xx, vezi
    // pap_aperta_sync_products_start), cu marja de siguranta. Decizie user
    // 2026-09-04: nu doar raportare, ci actiune automata - vezi
    // papetarie_storefront_aperta_auto_draft_vanished_cb().
    if (!as_next_scheduled_action('pap_aperta_auto_draft_vanished', [], 'aperta-sync')) {
        $timestamp = papetarie_storefront_aperta_romania_time_today('01:30:00');
        if ($timestamp < time()) {
            $timestamp += DAY_IN_SECONDS;
        }
        as_schedule_recurring_action($timestamp, DAY_IN_SECONDS, 'pap_aperta_auto_draft_vanished', [], 'aperta-sync');
    }
}
add_action('init', 'papetarie_storefront_aperta_schedule_cron');

/**
 * Raport zilnic de sincronizare (18:00 ora Romaniei), trimis intotdeauna -
 * chiar si cand nu e nimic de semnalat - ca sa nu existe ambiguitate intre
 * "azi n-a fost nimic" si "s-a stricat ceva" (user 2026-09-04: emailul
 * vechi tacea complet cand nu erau restock-uri, ceea ce a fost confundat cu
 * o defectiune). Trei sectiuni:
 *
 * 1. Produse revenite pe stoc azi, dar ramase draft (comportament vechi,
 *    vezi papetarie_storefront_aperta_apply_stock() - decizie Lavinia
 *    2026-08-07: publicarea ramane manuala).
 * 2. Produse noi adaugate azi de sincronizare (nou - nu exista deloc
 *    notificare pentru asta pana acum).
 * 3. Produse INCA publicate si comandabile la noi, al caror SKU a disparut
 *    complet din feedul curent Aperta - risc direct de a primi o comanda
 *    pe care n-o putem onora. Nou, si cel mai important (user: "asta e
 *    foarte important") - lista curenta completa, nu doar ce s-a schimbat
 *    azi, ca sa ramana vizibila pana e rezolvata, nu doar o data.
 *
 * Merge la ambele adrese (user + Lavinia) - inainte mergea doar la Lavinia.
 */
function papetarie_storefront_aperta_send_restock_digest_cb(): void
{
    $to = ['d.crysty23@gmail.com', 'laviniamuntean40@gmail.com'];
    $today = date('d.m.Y');
    $sections = [];

    // 1. Revenite pe stoc, ramase draft.
    $restockedToday = get_option('pap_restocked_today', []);
    $restockLines = [];
    foreach ($restockedToday as $postId => $timestamp) {
        $product = wc_get_product($postId);
        if (!($product instanceof WC_Product)) {
            continue;
        }
        $restockLines[] = sprintf(
            '- %s (stoc: %d) — %s',
            $product->get_name(),
            $product->get_stock_quantity(),
            admin_url('post.php?post=' . $postId . '&action=edit')
        );
    }
    $sections[] = "PRODUSE REVENITE PE STOC AZI (raman draft pana le publici manual):\n"
        . (empty($restockLines) ? 'Niciunul azi.' : implode("\n", $restockLines));
    update_option('pap_restocked_today', []);

    // 2. Produse noi adaugate azi (intra ca draft, de verificat manual).
    $newToday = get_option('pap_new_products_today', []);
    $newLines = [];
    foreach ($newToday as $postId => $info) {
        if (!get_post($postId)) {
            continue;
        }
        $newLines[] = sprintf(
            '- %s — %s',
            is_array($info) ? ($info['name'] ?? '') : '',
            admin_url('post.php?post=' . $postId . '&action=edit')
        );
    }
    $sections[] = "PRODUSE NOI ADAUGATE AZI (intra ca ciorna, de verificat manual):\n"
        . (empty($newLines) ? 'Niciunul azi.' : implode("\n", $newLines));
    update_option('pap_new_products_today', []);

    // 3a. Ce a mutat automat azi job-ul de 01:30 (vezi
    // papetarie_storefront_aperta_auto_draft_vanished_cb()) - doar raportare
    // aici, actiunea deja s-a intamplat, cu email separat, imediat, la ora
    // aceea.
    $autoDrafted = get_option('pap_auto_drafted_today', []);
    $autoDraftedLines = [];
    foreach ($autoDrafted as $postId => $info) {
        $autoDraftedLines[] = sprintf(
            '- [%s] %s (SKU %s) — %s',
            is_array($info) ? ($info['type'] ?? '') : '',
            is_array($info) ? ($info['name'] ?? '') : '',
            is_array($info) ? ($info['sku'] ?? '') : '',
            admin_url('post.php?post=' . $postId . '&action=edit')
        );
    }
    $sections[] = "PRODUSE MUTATE AUTOMAT IN CIORNA AZI (disparute din feedul Aperta, deja opriti sa mai fie comandate - vezi si comentariul de pe fiecare produs):\n"
        . (empty($autoDraftedLines) ? 'Niciunul azi.' : implode("\n", $autoDraftedLines));
    update_option('pap_auto_drafted_today', []);

    // 3b. Plasa de siguranta: re-verificare live, in caz ca a scapat ceva
    // intre rularea de la 01:30 si acum (ex. un produs revenit pe stoc la
    // pranz si disparut din feed abia dupa aceea). In mod normal gol.
    $atRisk = papetarie_storefront_aperta_products_missing_from_feed_but_orderable();
    $riskLines = [];
    foreach ($atRisk as $item) {
        $riskLines[] = sprintf(
            '- [%s] %s (SKU %s, stoc: %s) — %s',
            $item['type'],
            $item['name'],
            $item['sku'],
            $item['stock'] ?? '?',
            admin_url('post.php?post=' . $item['post_id'] . '&action=edit')
        );
    }
    $riskHeader = empty($riskLines)
        ? "PLASA DE SIGURANTA - INCA COMANDABILE SI DISPARUTE DIN FEED (neasteptat, ar trebui sa fie mereu gol): Niciunul."
        : sprintf(
            "⚠ NEASTEPTAT — %d PRODUSE INCA COMANDABILE, DISPARUTE DIN FEEDUL APERTA, NEACTIONATE de job-ul automat (verifica manual, ceva pare in neregula cu automatizarea):\n%s",
            count($riskLines),
            implode("\n", $riskLines)
        );
    $sections[] = $riskHeader;

    $issueCount = count($restockLines) + count($newLines) + count($autoDraftedLines) + count($riskLines);
    $subject = $issueCount > 0
        ? sprintf('[Notix] Raport sincronizare Aperta (%s) — %d de verificat', $today, $issueCount)
        : sprintf('[Notix] Raport sincronizare Aperta (%s) — totul OK', $today);

    $body = "Raport zilnic automat de sincronizare Aperta.\n\n"
        . implode("\n\n", $sections)
        . "\n\n---\nAcest email se trimite in fiecare zi, indiferent daca e ceva de semnalat sau nu.";

    wp_mail($to, $subject, $body);
}
add_action('pap_aperta_send_restock_digest', 'papetarie_storefront_aperta_send_restock_digest_cb');
