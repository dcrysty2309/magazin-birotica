<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Periferice" subcategories so the
 * mega menu le listează grupate logic (nu alfabetic) în 2 coloane: dispozitive
 * periferice vs. alimentare și accesorii. Reuses the same 'order' term meta
 * mechanism papetarie_storefront_sort_terms() already reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-periferice-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Coloana 1 — dispozitive periferice.
// Coloana 2 — alimentare și accesorii.
$slugs_in_order = [
    // Coloana 1
    'casti',
    'boxe',
    'camere',
    'mouse',
    'tastatura',
    // Coloana 2
    'baterii-externe',
    'incarcatoare',
    'cabluri',
    'accesorii-periferice',
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
