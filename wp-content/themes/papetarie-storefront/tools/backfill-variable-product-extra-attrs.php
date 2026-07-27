<?php

declare(strict_types=1);

/**
 * Ca backfill-attribute-filters.php, dar re-eticheteaza produsele variabile
 * cu ATÂT variantele lor (culoare/tip) CÂT ȘI atributele suplimentare
 * extrase din descriere/nume (Format, Gramaj, Ambalare etc.) - pana acum,
 * produsele variabile primeau doar variantele, restul informatiei
 * structurate din descrierea lor era ignorata complet (extract_description_
 * attributes()/extract_text_attributes() rulau doar pe ramura produselor
 * simple in sincronizarea normala).
 *
 * Nu modifica pretul/stocul/imaginile - doar re-eticheteaza atributele
 * (product_attr_value), inlocuind setul anterior cu unul recalculat -
 * sigur de rulat oricand.
 *
 * Run with (in bucati, ca sa nu epuizeze memoria pe cataloage mari):
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/backfill-variable-product-extra-attrs.php --offset=0 --limit=500
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
    $variantGroup = $attribute->get_name();
    $variantValues = $attribute->get_options();

    if ($variantGroup === '' || empty($variantValues)) {
        $skipped++;
        continue;
    }

    $name = $product->get_name();
    $description = $product->get_description();
    $categoryNames = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
    $categoryPath = is_array($categoryNames) ? implode('>', $categoryNames) : '';

    $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
    $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
    $extraAttrs = $descAttrs + $textAttrs;
    unset($extraAttrs[$variantGroup]);

    papetarie_storefront_aperta_tag_variant_and_extra_attrs($id, $variantGroup, $variantValues, $extraAttrs);
    $tagged++;

    if ($tagged % 200 === 0) {
        echo "{$tagged} etichetate...\n";
    }
}

echo "Done. Etichetate: {$tagged}, sarite (fara variante/atribute): {$skipped}\n";
