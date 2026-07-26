<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Curățenie și sanitare" subcategories
 * so the mega menu le listează grupate logic (nu alfabetic) în 2 coloane:
 * produse de curățenie vs. igienă și accesorii menaj. Reuses the same
 * 'order' term meta mechanism papetarie_storefront_sort_terms() already
 * reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-curatenie-si-sanitare-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Coloana 1 — produse de curățenie.
// Coloana 2 — igienă și accesorii menaj.
$slugs_in_order = [
    // Coloana 1
    'bureti',
    'lavete',
    'maturi-si-mopuri',
    'manusi-menaj',
    'detergenti-de-vase-si-geamuri',
    'solutii-diverse-pentru-curatenie',
    // Coloana 2
    'accesorii-menaj',
    'hartie-igienica-si-dispensere',
    'prosoape-de-hartie-si-dispensere',
    'sapunuri-si-dispensere',
    'sanitare',
];

$order = 1;

foreach ($slugs_in_order as $slug) {
    $term = get_term_by('slug', $slug, 'product_cat');

    if (!($term instanceof WP_Term)) {
        fwrite(STDERR, "Nu am găsit termenul cu slug '{$slug}'.\n");
        continue;
    }

    update_term_meta($term->term_id, 'order', $order);
    echo "order={$order} -> {$term->name} ({$slug})\n";
    $order++;
}

echo "Done.\n";
