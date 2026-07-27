<?php

declare(strict_types=1);

/**
 * Re-ruleaza extragerea de atribute din descriere/nume
 * (extract_description_attributes() + extract_text_attributes()) pe produsele
 * SIMPLE deja sincronizate - echivalentul lui backfill-attribute-filters.php,
 * dar pentru produse simple in loc de variabile (acela citeste doar atributul
 * WooCommerce de pe produsele variabile).
 *
 * De ce e nevoie: sincronizarea normala extrage aceste atribute doar cand
 * produsul e creat/actualizat (rândul din feed s-a schimbat) - fast-path-ul
 * "produs neschimbat" sare complet peste re-procesare. Produsele simple
 * sincronizate INAINTE sa existe acest extractor n-au fost niciodata
 * re-procesate, deci multe categorii arata mai putine filtre decat ar avea
 * de fapt din descrierile lor deja existente.
 *
 * Nu modifica pretul/stocul/imaginile - doar re-eticheteaza atributele
 * (product_attr_value), inlocuind setul anterior cu unul recalculat din
 * aceeasi descriere/nume deja stocate - sigur de rulat oricand.
 *
 * Run with (in bucati, ca sa nu epuizeze memoria pe cataloage mari):
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/backfill-simple-product-attributes.php --offset=0 --limit=500
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

    if (!($product instanceof WC_Product) || $product instanceof WC_Product_Variable) {
        $skipped++;
        continue;
    }

    $name = $product->get_name();
    $description = $product->get_description();
    $categoryNames = wp_get_post_terms($id, 'product_cat', ['fields' => 'names']);
    $categoryPath = is_array($categoryNames) ? implode('>', $categoryNames) : '';

    $descAttrs = papetarie_storefront_aperta_extract_description_attributes($description);
    $textAttrs = papetarie_storefront_aperta_extract_text_attributes($name, $categoryPath);
    $allAttrs = $descAttrs + $textAttrs;

    if (empty($allAttrs)) {
        $skipped++;
        continue;
    }

    papetarie_storefront_aperta_tag_multiple_attrs($id, $allAttrs);
    $tagged++;

    if ($tagged % 200 === 0) {
        echo "{$tagged} etichetate...\n";
    }
}

echo "Done. Etichetate: {$tagged}, sarite (fara atribute gasite / produse variabile): {$skipped}\n";
