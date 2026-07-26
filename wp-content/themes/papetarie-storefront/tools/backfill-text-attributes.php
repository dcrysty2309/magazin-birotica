<?php

declare(strict_types=1);

/**
 * Extrage atribute din text (Format, Gramaj, Nr. coli) pentru produsele
 * simple deja sincronizate, inainte sa existe
 * papetarie_storefront_aperta_extract_text_attributes() - re-citeste
 * feed.csv (trebuie sa fie deja descarcat local) si aplica extragerea
 * pe fiecare produs simplu existent.
 *
 * Run with (in bucati, ca la celelalte scripturi de backfill):
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/backfill-text-attributes.php --offset=0 --limit=500
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$offset = 0;
$limit = null;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--offset=(\d+)$/', $arg, $m)) {
        $offset = (int) $m[1];
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    }
}

$grouped = papetarie_storefront_aperta_read_products_grouped();

$simpleCodes = [];
foreach ($grouped as $code => $rows) {
    $isVariable = count($rows) > 1 || trim((string) $rows[0]['Variant']) !== '';
    if (!$isVariable) {
        $simpleCodes[] = $code;
    }
}

$codes = array_slice($simpleCodes, $offset, $limit ?? (count($simpleCodes) - $offset));

echo "Procesez " . count($codes) . " din " . count($simpleCodes) . " produse simple, incepand de la offset {$offset}.\n";

$tagged = 0;
$skipped = 0;

foreach ($codes as $code) {
    $row = $grouped[$code][0];
    $name = trim((string) $row['Denumire produs']);
    $categoryPath = (string) $row['Categorie produs'];
    $codUnic = trim((string) $row['Cod unic']);

    $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);

    if (!$textAttrs) {
        $skipped++;
        continue;
    }

    $productId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);

    if ($productId === null) {
        $skipped++;
        continue;
    }

    papetarie_storefront_aperta_tag_multiple_attrs($productId, $textAttrs);
    $tagged++;
}

echo "Done. Etichetate: {$tagged}, sarite (fara tipare gasite sau produs negasit): {$skipped}\n";
