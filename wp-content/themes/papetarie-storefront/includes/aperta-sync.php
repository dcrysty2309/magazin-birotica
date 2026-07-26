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
 * @param array<int, array{sku: string, name: string, changed?: bool}> $affectedItems fiecare item = un cod gasit pe site (potrivit); cele nepotrivite nu apar aici
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
 * @return array<int, array{sku: string, name: string, changed?: bool}>
 */
function papetarie_storefront_aperta_get_run_log(string $runId): array
{
    $log = get_option(papetarie_storefront_aperta_run_log_option($runId), []);

    return is_array($log) ? $log : [];
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
    $existing = get_term_by('slug', $slug, 'product_cat');

    if ($existing instanceof WP_Term) {
        return (int) $existing->term_id;
    }

    $created = wp_insert_term($name, 'product_cat', ['slug' => $slug, 'parent' => $parentId]);

    if (is_wp_error($created)) {
        // Poate exista deja cu alt slug (coliziune de nume la acelasi parinte).
        $byName = get_term_by('name', $name, 'product_cat');
        if ($byName instanceof WP_Term && (int) $byName->parent === $parentId) {
            return (int) $byName->term_id;
        }

        throw new RuntimeException('Nu am putut crea categoria ' . $name . ': ' . $created->get_error_message());
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

function papetarie_storefront_aperta_find_by_sku_meta(string $codUnic): ?int
{
    $ids = get_posts([
        'post_type' => ['product', 'product_variation'],
        'post_status' => 'any',
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
        'post_status' => 'any',
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
        return ['product_id' => 0, 'is_new' => false, 'is_variable' => false, 'old_price' => null, 'new_price' => null];
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
    $oldPrice = (!$isNew && !$isVariable) ? get_post_meta($productId, '_regular_price', true) : '';
    $oldPrice = $oldPrice !== '' ? (float) $oldPrice : null;

    $product = $isVariable ? new WC_Product_Variable($productId ?? 0) : new WC_Product_Simple($productId ?? 0);

    $product->set_name($name);
    $product->set_description($description);
    $product->set_status('publish');
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
    }

    return [
        'product_id' => $productId,
        'is_new' => $isNew,
        'is_variable' => $isVariable,
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'variations' => $variationsSummary,
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
 * @return array<int, array{sku: string, name: string}> produsele chiar afectate (SKU-uri gasite pe site)
 */
function papetarie_storefront_aperta_apply_stock(array $stockByCodUnic): array
{
    $applied = [];

    foreach ($stockByCodUnic as $codUnic => $data) {
        $postId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);
        if ($postId === null) {
            continue;
        }

        $product = wc_get_product($postId);
        if (!($product instanceof WC_Product)) {
            continue;
        }

        $oldQuantity = $product->get_stock_quantity();
        $quantity = (int) $data['stock'];
        $product->set_manage_stock(true);
        $product->set_stock_quantity($quantity);
        $product->set_stock_status($quantity > 0 ? 'instock' : $product->get_stock_status());

        if ($quantity <= 0 && $product->get_stock_status() !== 'onbackorder') {
            $product->set_stock_status('outofstock');
        }

        $product->save();

        $oldLabel = $oldQuantity === null ? '—' : (string) $oldQuantity;
        $applied[] = [
            'sku' => $codUnic,
            'name' => $product->get_name() . ' (stoc: ' . $oldLabel . ' → ' . $quantity . ')',
            'changed' => $oldQuantity !== $quantity,
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
add_action('pap_aperta_sync_stock_start', 'papetarie_storefront_aperta_sync_stock_start_cb');
add_action('pap_aperta_sync_stock_chunk', 'papetarie_storefront_aperta_sync_stock_chunk_cb');

/**
 * Programeaza sync-urile recurente, cu un mic delay fata de orele oficiale
 * ale sursei (produsele se actualizeaza la 2:50, stocul la 1:10 si din ora
 * in ora intre 9:10-17:10).
 */
function papetarie_storefront_aperta_schedule_cron(): void
{
    if (!function_exists('as_schedule_recurring_action') || get_option('pap_aperta_cron_registered') === 'yes') {
        return;
    }

    $delay = PAP_APERTA_SYNC_DELAY_MINUTES * MINUTE_IN_SECONDS;

    if (!as_next_scheduled_action('pap_aperta_sync_products_start', [], 'aperta-sync')) {
        $timestamp = strtotime('today 02:50:00') + $delay;
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

        $timestamp = strtotime(sprintf('today %02d:10:00', $hour)) + $delay;
        if ($timestamp < time()) {
            $timestamp += DAY_IN_SECONDS;
        }
        as_schedule_recurring_action($timestamp, DAY_IN_SECONDS, 'pap_aperta_sync_stock_start', $args, 'aperta-sync');
    }

    update_option('pap_aperta_cron_registered', 'yes');
}
add_action('init', 'papetarie_storefront_aperta_schedule_cron');
