<?php

declare(strict_types=1);

/**
 * Eticheteaza produsele variabile deja sincronizate (inainte sa existe
 * papetarie_storefront_aperta_tag_attr_terms()) cu termenii de filtrare
 * pe atribute - citeste atributul WooCommerce deja salvat pe fiecare produs
 * variabil si il transforma in termeni product_attr_value.
 *
 * Run with (in bucati, ca sa nu epuizeze memoria pe cataloage mari):
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/backfill-attribute-filters.php --offset=0 --limit=500
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

$allIds = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'meta_key' => '_pap_aperta_cod_produs',
    'fields' => 'ids',
    'posts_per_page' => -1,
]);

$ids = array_slice($allIds, $offset, $limit ?? (count($allIds) - $offset));

echo "Procesez " . count($ids) . " din " . count($allIds) . " produse Aperta, incepand de la offset {$offset}.\n";

$tagged = 0;
$skipped = 0;

foreach ($ids as $id) {
    $product = wc_get_product($id);

    if (!($product instanceof WC_Product_Variable)) {
        $skipped++;
        continue;
    }

    $attributes = $product->get_attributes();

    if (empty($attributes)) {
        $skipped++;
        continue;
    }

    $attribute = reset($attributes);
    $group = $attribute->get_name();
    $values = $attribute->get_options();

    if ($group === '' || empty($values)) {
        $skipped++;
        continue;
    }

    papetarie_storefront_aperta_tag_attr_terms($id, $group, $values);
    $tagged++;

    if ($tagged % 200 === 0) {
        echo "{$tagged} etichetate...\n";
    }
}

echo "Done. Etichetate: {$tagged}, sarite (fara variante/atribute): {$skipped}\n";
