<?php

declare(strict_types=1);

/**
 * Ruleaza sincronizarea Aperta manual, sincron (fara Action Scheduler) -
 * util pentru primul import si pentru testare pe un subset.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/sync-aperta-feed.php [--offset=0] [--limit=20] [--skip-stock] [--skip-download] [--stock-only] [--force] [--resume]
 *
 * Daca o rulare reala (din cron sau din "Ruleaza acum" in admin) e deja in
 * desfasurare pentru acelasi flux, acest script refuza sa porneasca (ca sa
 * nu suprascrie afisarea ei de progres) - foloseste --force doar daca stii
 * sigur ca vrei sa suprascrii oricum (treaba efectiva de sincronizare nu e
 * afectata, doar afisarea de progres din admin).
 *
 * Ruleaza intr-un singur proces PHP - pentru catalogul intreg (~3330 produse)
 * memoria creste progresiv (cache-uri interne WP/WC) si poate epuiza
 * memory_limit. Pentru un import complet, ruleaza in bucati mici
 * (--offset/--limit), fiecare invocare fiind un proces PHP nou, curat -
 * adauga si --resume la FIECARE bucata din secventa, ca afisarea de progres
 * din admin sa arate cumulat fata de totalul real (nu doar bucata curenta)
 * si sa nu se "finalizeze" decat la ultima bucata. Fara --resume (implicit),
 * fiecare rulare e tratata de sine statatoare (util pt. teste rapide cu
 * --limit mic, fara sa lase afisarea de progres intr-o stare falsa).
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$offset = 0;
$limit = null;
$skipStock = false;
$skipDownload = false;
$stockOnly = false;
$force = false;
$resume = false;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--offset=(\d+)$/', $arg, $m)) {
        $offset = (int) $m[1];
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    } elseif ($arg === '--skip-stock') {
        $skipStock = true;
    } elseif ($arg === '--skip-download') {
        $skipDownload = true;
    } elseif ($arg === '--stock-only') {
        $stockOnly = true;
    } elseif ($arg === '--force') {
        $force = true;
    } elseif ($arg === '--resume') {
        $resume = true;
    }
}

function pap_aperta_guard_running(string $flow, bool $force): void
{
    if (!$force && papetarie_storefront_aperta_progress_has_running_chunks($flow)) {
        fwrite(STDERR, "O sincronizare de {$flow} rulează deja (din cron sau din \"Rulează acum\" în admin) - verifică acolo progresul. Folosește --force dacă vrei să suprascrii oricum afișarea (treaba efectivă de sincronizare nu e afectată).\n");
        exit(1);
    }
}

if ($stockOnly) {
    if (!$skipDownload) {
        echo "Descarc feed-stoc.csv...\n";
        if (!papetarie_storefront_aperta_download_feed('stoc')) {
            fwrite(STDERR, "Nu am putut descarca feed-stoc.csv.\n");
            exit(1);
        }
    }
    $stockMap = papetarie_storefront_aperta_read_stock_map();
    $allStockCodes = array_keys($stockMap);
    $stockCodes = array_slice($allStockCodes, $offset, $limit ?? (count($allStockCodes) - $offset));
    $chunk = [];
    foreach ($stockCodes as $code) {
        $chunk[$code] = $stockMap[$code];
    }
    echo "Aplic stocul pentru " . count($chunk) . " coduri incepand de la offset {$offset} (din " . count($stockMap) . " total)...\n";

    // Fara --resume, rularea e de sine statatoare (util pt. teste rapide cu
    // --limit mic - nu ramane "agatata" la un total mare care nu se va mai
    // completa). Cu --resume, e o bucata dintr-o secventa mai mare: pornim
    // progresul (cu totalul real) doar la offset 0 si il finalizam doar cand
    // chiar am ajuns la capat.
    $stockTotal = $resume ? count($allStockCodes) : count($chunk);
    if (!$resume || $offset === 0) {
        pap_aperta_guard_running('stock', $force);
        papetarie_storefront_aperta_progress_start('stock', $stockTotal);
    }
    $applied = papetarie_storefront_aperta_apply_stock($chunk);
    papetarie_storefront_aperta_progress_tick('stock', count($chunk), $applied);
    if (!$resume || $offset + count($chunk) >= count($allStockCodes)) {
        papetarie_storefront_aperta_progress_finish('stock');
    }
    echo "Done.\n";
    exit(0);
}

if (!$skipDownload) {
    echo "Descarc feed.csv...\n";
    if (!papetarie_storefront_aperta_download_feed('feed')) {
        fwrite(STDERR, "Nu am putut descarca feed.csv.\n");
        exit(1);
    }
}

$grouped = papetarie_storefront_aperta_read_products_grouped();
$allCodes = array_keys($grouped);
$codes = array_slice($allCodes, $offset, $limit ?? (count($allCodes) - $offset));

echo "Sincronizez " . count($codes) . " produse incepand de la offset {$offset} (din " . count($grouped) . " total din feed)...\n";

$productsTotal = $resume ? count($allCodes) : count($codes);
if (!$resume || $offset === 0) {
    pap_aperta_guard_running('products', $force);
    papetarie_storefront_aperta_progress_start('products', $productsTotal);
}

$created = 0;
foreach ($codes as $i => $code) {
    $result = papetarie_storefront_aperta_upsert_product($grouped[$code]);
    $description = papetarie_storefront_aperta_describe_upsert($result);
    $created++;
    echo ($i + 1) . "/" . count($codes) . " -> #{$result['product_id']} \"{$grouped[$code][0]['Denumire produs']}\" ({$description})\n";

    papetarie_storefront_aperta_progress_tick('products', 1, [[
        'sku' => trim((string) $grouped[$code][0]['Cod unic']),
        'name' => trim((string) $grouped[$code][0]['Denumire produs']) . ' (' . $description . ')',
        'changed' => papetarie_storefront_aperta_upsert_is_changed($result),
    ]]);
}

if (!$resume || $offset + count($codes) >= count($allCodes)) {
    papetarie_storefront_aperta_progress_finish('products');
}
echo "Produse sincronizate: {$created}\n";

if ($skipStock) {
    echo "Skip stoc (--skip-stock).\n";
    exit(0);
}

echo "Descarc feed-stoc.csv...\n";
if (!papetarie_storefront_aperta_download_feed('stoc')) {
    fwrite(STDERR, "Nu am putut descarca feed-stoc.csv.\n");
    exit(1);
}

$stockMap = papetarie_storefront_aperta_read_stock_map();

if (count($codes) < count($allCodes)) {
    $allowedCodUnic = [];
    foreach ($codes as $code) {
        foreach ($grouped[$code] as $row) {
            $allowedCodUnic[trim((string) $row['Cod unic'])] = true;
        }
    }
    $stockMap = array_intersect_key($stockMap, $allowedCodUnic);
}

echo "Aplic stocul pentru " . count($stockMap) . " coduri...\n";
pap_aperta_guard_running('stock', $force);
papetarie_storefront_aperta_progress_start('stock', count($stockMap));
$applied = papetarie_storefront_aperta_apply_stock($stockMap);
papetarie_storefront_aperta_progress_tick('stock', count($stockMap), $applied);
papetarie_storefront_aperta_progress_finish('stock');

echo "Done.\n";
