<?php

defined('ABSPATH') || exit;

/**
 * Sync produse din feed-urile Aperta/Scribant Distribution (dropshipping).
 * Vezi contractul de discount (Anexa dropshipping Artflex) pentru procentele
 * de mai jos — nu apar nicăieri în CSV.
 */

const PAP_APERTA_PRODUCTS_FEED_URL = 'https://www.aperta.ro/feed.csv';
const PAP_APERTA_STOCK_FEED_URL = 'https://www.aperta.ro/feed-stoc.csv';
const PAP_APERTA_CHUNK_SIZE = 25;
// Bucata de produse e mai mica decat cea de stoc, fiindca descarca imagini -
// o bucata de 25 cu poze multe/grele poate depasi cele 300s dupa care
// Action Scheduler marcheaza automat actiunea "esuata", rupand lantul (nu se
// mai programeaza bucata urmatoare). Descoperit live pe staging 2026-07-26.
const PAP_APERTA_PRODUCTS_CHUNK_SIZE = 10;
const PAP_APERTA_SYNC_DELAY_MINUTES = 20;

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

        $grouped[$code][] = $assoc;
    }

    fclose($handle);

    return papetarie_storefront_aperta_consolidate_singleton_colors($grouped);
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

function papetarie_storefront_aperta_get_or_create_term(string $name, string $slug, int $parentId): int
{
    // Cautarea dupa slug trebuie scopata la parinte: get_term_by('slug', ...)
    // e globala pe toata taxonomia, deci doua categorii cu acelasi nume sub
    // parinti diferiti (ex. un rand de feed cu o cale corupta gen
    // "Periferice>Mouse,Periferice>Tastaturi" alaturi de randul corect
    // "Periferice>Tastaturi") ar fi fost legate gresit de primul termen
    // creat, indiferent de parintele lui real.
    $existing = get_term_by('slug', $slug, 'product_cat');

    if ($existing instanceof WP_Term && (int) $existing->parent === $parentId) {
        return (int) $existing->term_id;
    }

    $created = wp_insert_term($name, 'product_cat', ['slug' => $slug, 'parent' => $parentId]);

    if (is_wp_error($created)) {
        // Poate exista deja cu alt slug (coliziune de nume la acelasi parinte).
        $byName = get_term_by('name', $name, 'product_cat');
        if ($byName instanceof WP_Term && (int) $byName->parent === $parentId) {
            return (int) $byName->term_id;
        }

        // Slug-ul e ocupat de un termen cu alt parinte - lasam WP sa genereze
        // un slug unic (ex. tastaturi-2) in loc sa esuam sau sa ne agatam gresit.
        $created = wp_insert_term($name, 'product_cat', ['parent' => $parentId]);

        if (is_wp_error($created)) {
            throw new RuntimeException('Nu am putut crea categoria ' . $name . ': ' . $created->get_error_message());
        }
    }

    return (int) $created['term_id'];
}

/**
 * Rezolva "Categorie produs" (ex. "Organizare, arhivare, prezentare>Bibliorafturi")
 * la un term_id product_cat existent, creand subcategoria (sau, rar, un arbore
 * top-level nou) daca nu gaseste un copil potrivit.
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
    $parentId = $topTerm instanceof WP_Term
        ? (int) $topTerm->term_id
        : papetarie_storefront_aperta_get_or_create_term($topLevelName, $topLevelSlug, 0);

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

        $currentParentId = $match instanceof WP_Term
            ? (int) $match->term_id
            : papetarie_storefront_aperta_get_or_create_term($subName, $subSlug, $currentParentId);
    }

    return $currentParentId;
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
function papetarie_storefront_aperta_sideload_image(string $url, int $productId): ?int
{
    $url = trim($url);
    if ($url === '') {
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

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachmentId = media_sideload_image($url, $productId, null, 'id');

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

// NOTA: 'any' in WP_Query/get_posts EXCLUDE 'trash' (particularitate WP,
// nu include tot ce pare "orice status"). Includem explicit 'trash' aici,
// altfel sincronizarea nu vede produsele excluse manual (curatenia facuta
// impreuna cu Lavinia) si le recreeaza ca produse noi la fiecare rulare -
// gasit 2026-07-27 chiar inainte de cronul de noapte.
const PAP_APERTA_ALL_STATUSES = ['publish', 'draft', 'pending', 'private', 'future', 'trash'];

function papetarie_storefront_aperta_find_by_sku_meta(string $codUnic): ?int
{
    $ids = get_posts([
        'post_type' => ['product', 'product_variation'],
        'post_status' => PAP_APERTA_ALL_STATUSES,
        'meta_key' => '_pap_aperta_sku',
        'meta_value' => $codUnic,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    return isset($ids[0]) ? (int) $ids[0] : null;
}

function papetarie_storefront_aperta_find_parent_by_cod_produs(string $codProdus): ?int
{
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => PAP_APERTA_ALL_STATUSES,
        'meta_key' => '_pap_aperta_cod_produs',
        'meta_value' => $codProdus,
        'fields' => 'ids',
        'posts_per_page' => 1,
    ]);

    return isset($ids[0]) ? (int) $ids[0] : null;
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
    $value = trim($value);

    if ($group === '' || $value === '') {
        return null;
    }

    $slug = sanitize_title($group . '-' . $value);
    $existing = get_term_by('slug', $slug, 'product_attr_value');

    if ($existing instanceof WP_Term) {
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

        if ($product instanceof WC_Product_Variable) {
            $attributes = $product->get_attributes();
            if (empty($attributes)) {
                continue;
            }
            $attribute = reset($attributes);
            $group = $attribute->get_name();
            $values = $attribute->get_options();
            if ($group === '' || empty($values)) {
                continue;
            }
            papetarie_storefront_aperta_tag_attr_terms($id, $group, $values);
            $tagged++;
            continue;
        }

        $name = $product->get_name();
        $description = $product->get_description();
        $categoryNames = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
        $categoryPath = is_array($categoryNames) ? implode('>', $categoryNames) : '';

        $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
        $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
        $allAttrs = $descAttrs + $textAttrs;

        if ($allAttrs) {
            papetarie_storefront_aperta_tag_multiple_attrs($id, $allAttrs);
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
        $attrs['Număr file'] = $m[1] . ' file';
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
            $attrs['Număr coli'] = $m[1] . '/top';
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

    if (preg_match('/\b(\d{1,2})\s*\+/', $name, $m)) {
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
 * Doar valorile scurte devin filtre (sub PAP_ATTR_DESC_MAX_VALUE_LENGTH) -
 * unele etichete (ex. "Compartimente", "Caracteristici suplimentare") au
 * propozitii intregi ca valoare, unice per produs, care n-ar functiona ca
 * optiune de filtru (fiecare produs ar avea alta valoare, deci n-ar filtra
 * nimic util) - lungimea e un filtru simplu, generic, pentru asta.
 *
 * @return array<string, string> grup => valoare
 */
function papetarie_storefront_aperta_extract_description_attributes(string $description): array
{
    $attrs = [];
    $maxValueLength = 40;

    if (!preg_match_all('/<li>\s*([^:<]{2,40}):\s*([^<]+?)\s*<\/li>/iu', $description, $matches, PREG_SET_ORDER)) {
        return $attrs;
    }

    foreach ($matches as $match) {
        $rawGroup = trim($match[1]);
        $value = trim($match[2]);

        if ($rawGroup === '' || $value === '' || mb_strlen($value) > $maxValueLength) {
            continue;
        }

        // Normalizare simpla de capitalizare, ca "Nr. file" si "Nr. FILE" sa
        // devina acelasi grup (altfel am avea carduri de filtru duplicate).
        $group = mb_convert_case($rawGroup, MB_CASE_TITLE, 'UTF-8');

        $attrs[$group] = $value;
    }

    return $attrs;
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
        return ['product_id' => 0, 'is_new' => false, 'is_variable' => false, 'old_price' => null, 'new_price' => null, 'was_trashed' => false];
    }

    $first = $rows[0];
    $name = trim((string) $first['Denumire produs']);
    $description = (string) $first['Descriere produs'];
    $categoryPath = (string) $first['Categorie produs'];
    $brandName = (string) $first['Brand produs'];
    $discountGroup = (string) $first['Discount'];
    $discountPercent = papetarie_storefront_aperta_discount_percent($discountGroup);

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

    if ($productId === null) {
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
    $rowHash = md5(serialize($rows));
    if (!$isNew) {
        $storedHash = get_post_meta($productId, '_pap_aperta_row_hash', true);
        if ($storedHash === $rowHash) {
            return [
                'product_id' => $productId,
                'is_new' => false,
                'is_variable' => $isVariable,
                'old_price' => $oldPrice,
                'new_price' => $oldPrice,
                'variations' => null,
                'was_trashed' => $wasTrashed,
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
    $variationsSummary = null;

    if ($isVariable) {
        $variationsSummary = papetarie_storefront_aperta_sync_variations($productId, $rows, $discountPercent);
    } else {
        $price = round(((float) str_replace(',', '.', (string) $first['Pret produs'])) * (1 - $discountPercent / 100), 2);
        $newPrice = $price;

        $simple = new WC_Product_Simple($productId);
        $simple->set_sku($primaryCodUnic);
        $simple->set_regular_price((string) $price);
        $simple->set_manage_stock(true);
        $simple->set_stock_status(papetarie_storefront_aperta_stock_status_from_text((string) $first['Status stoc']));

        $imageUrls = papetarie_storefront_aperta_image_urls((string) ($first['Imagine produs'] ?? ''));
        $imageIds = papetarie_storefront_aperta_sideload_images($imageUrls, $productId);
        if (!empty($imageIds)) {
            $simple->set_image_id($imageIds[0]);
            $simple->set_gallery_image_ids(array_slice($imageIds, 1));
        }

        $simple->save();

        // Descrierea e o sursa mai bogata si mai fiabila (liste structurate
        // "Eticheta: Valoare") decat titlul - o citim intai, apoi completam
        // cu ce mai gasim in titlu daca nu a fost deja acolo.
        $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
        $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
        $allAttrs = $descAttrs + $textAttrs;
        if ($allAttrs) {
            papetarie_storefront_aperta_tag_multiple_attrs($productId, $allAttrs);
        }
    }

    update_post_meta($productId, '_pap_aperta_row_hash', $rowHash);

    return [
        'product_id' => $productId,
        'is_new' => $isNew,
        'is_variable' => $isVariable,
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'variations' => $variationsSummary,
        'was_trashed' => $wasTrashed,
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
function papetarie_storefront_aperta_sync_variations(int $productId, array $rows, float $discountPercent): array
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

    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($attributeName);
    $attribute->set_options($values);
    $attribute->set_visible(true);
    $attribute->set_variation(true);

    $variable = new WC_Product_Variable($productId);
    $variable->set_attributes([$attribute]);
    $variable->save();

    // Etichetare pentru filtrare pe pagina de arhiva (separat de atributul
    // WooCommerce de mai sus, care e pentru afisare/variatii, nu pentru query).
    papetarie_storefront_aperta_tag_attr_terms($productId, $attributeName, $values);

    $attributeKey = sanitize_title($attributeName);
    $firstImageId = null;
    $galleryImageIds = [];
    $totalVariations = 0;
    $newVariations = 0;
    $changedVariations = 0;

    foreach ($rows as $row) {
        $codUnic = trim((string) $row['Cod unic']);
        if ($codUnic === '') {
            continue;
        }

        $variantValue = trim((string) $row['Variant']);
        $variationId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);
        $isNewVariation = $variationId === null;
        $oldPrice = !$isNewVariation ? get_post_meta($variationId, '_regular_price', true) : '';
        $oldPrice = $oldPrice !== '' ? (float) $oldPrice : null;

        $variation = new WC_Product_Variation($variationId ?? 0);
        $variation->set_parent_id($productId);
        $variation->set_attributes([$attributeKey => $variantValue]);
        $variation->set_sku($codUnic);

        $price = round(((float) str_replace(',', '.', (string) $row['Pret produs'])) * (1 - $discountPercent / 100), 2);
        $variation->set_regular_price((string) $price);

        $totalVariations++;
        if ($isNewVariation) {
            $newVariations++;
        } elseif ($oldPrice === null || $oldPrice !== $price) {
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
        $parent = new WC_Product_Variable($productId);
        $parent->set_image_id($firstImageId);
        $parent->set_gallery_image_ids(array_keys($galleryImageIds));
        $parent->save();
    }

    return [
        'total' => $totalVariations,
        'new' => $newVariations,
        'changed' => $changedVariations,
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

        $product->set_manage_stock(true);
        $product->set_stock_quantity($quantity);
        $product->set_stock_status($expectedStatus);
        $product->save();

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
 * Procesare in bucati (Action Scheduler) - fiecare rulare proceseaza
 * PAP_APERTA_CHUNK_SIZE produse-parinte, apoi programeaza urmatoarea bucata.
 */
function papetarie_storefront_aperta_sync_products_start_cb(string $trigger = 'auto'): void
{
    // O rulare anterioara e deja in plina procesare a bucatilor - nu pornim
    // una noua peste ea (ex. programul zilnic a picat exact cand o rulare
    // manuala anterioara inca proceseaza).
    if (papetarie_storefront_aperta_progress_has_running_chunks('products')) {
        return;
    }

    // Marcam "running" IMEDIAT, sincron, inainte de orice altceva - altfel,
    // daca mai multe sloturi restante (ex. dintr-un backlog WP-Cron) sunt
    // procesate unul dupa altul in aceeasi rulare a cozii, fiecare ar trece
    // de verificarea de mai sus inainte ca vreunul sa apuce sa marcheze
    // starea, pornind mai multe lanturi paralele care se calca pe picioare.
    papetarie_storefront_aperta_progress_start('products', 0, $trigger);

    papetarie_storefront_aperta_download_feed('feed');
    as_enqueue_async_action('pap_aperta_sync_products_chunk', [0], 'aperta-sync');
}

function papetarie_storefront_aperta_sync_products_chunk_cb(int $offset = 0): void
{
    $grouped = papetarie_storefront_aperta_read_products_grouped();
    $codes = array_keys($grouped);
    $total = count($codes);

    if ($offset === 0) {
        papetarie_storefront_aperta_progress_start('products', $total);
    }

    $slice = array_slice($codes, $offset, PAP_APERTA_PRODUCTS_CHUNK_SIZE);
    $items = [];

    foreach ($slice as $code) {
        $result = papetarie_storefront_aperta_upsert_product($grouped[$code]);
        $items[] = [
            'sku' => trim((string) $grouped[$code][0]['Cod unic']),
            'name' => trim((string) $grouped[$code][0]['Denumire produs']) . ' (' . papetarie_storefront_aperta_describe_upsert($result) . ')',
            'changed' => papetarie_storefront_aperta_upsert_is_changed($result),
            'trashed' => $result['was_trashed'],
        ];
    }

    papetarie_storefront_aperta_progress_tick('products', count($slice), $items);

    if ($offset + PAP_APERTA_PRODUCTS_CHUNK_SIZE < $total) {
        as_schedule_single_action(time() + 5, 'pap_aperta_sync_products_chunk', [$offset + PAP_APERTA_PRODUCTS_CHUNK_SIZE], 'aperta-sync');
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

    $slice = array_slice($codes, $offset, PAP_APERTA_CHUNK_SIZE * 4);

    $chunkMap = [];
    foreach ($slice as $code) {
        $chunkMap[$code] = $stockMap[$code];
    }

    $applied = papetarie_storefront_aperta_apply_stock($chunkMap);
    papetarie_storefront_aperta_progress_tick('stock', count($slice), $applied);

    if ($offset + PAP_APERTA_CHUNK_SIZE * 4 < $total) {
        as_schedule_single_action(time() + 5, 'pap_aperta_sync_stock_chunk', [$offset + PAP_APERTA_CHUNK_SIZE * 4], 'aperta-sync');
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

function papetarie_storefront_aperta_schedule_cron(): void
{
    if (!function_exists('as_schedule_recurring_action') || get_option('pap_aperta_cron_registered') === 'yes') {
        return;
    }

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

    update_option('pap_aperta_cron_registered', 'yes');
}
add_action('init', 'papetarie_storefront_aperta_schedule_cron');
