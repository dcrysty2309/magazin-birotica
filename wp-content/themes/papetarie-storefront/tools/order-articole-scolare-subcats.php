<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Articole școlare" subcategories,
 * de la cel mai mare articol la cel mai mic (decizie Lavinia, 2026-07-29).
 * Reuses the same 'order' term meta mechanism papetarie_storefront_sort_terms()
 * already reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-articole-scolare-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$slugs_in_order = [
    'ghiozdane',
    'penare',
    'caiete',
    'coperti-si-etichete',
    'accesorii',
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
