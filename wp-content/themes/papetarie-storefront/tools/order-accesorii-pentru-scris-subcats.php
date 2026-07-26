<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Accesorii pentru scris" subcategories
 * so the mega menu lists them grouped logically (writing instruments, then
 * markers/auxiliary) instead of alphabetically. Reuses the same 'order' term
 * meta mechanism papetarie_storefront_sort_terms() already reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-accesorii-pentru-scris-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Column 1 — instrumente principale de scris (+ rezervele lor).
// Column 2 — markere și produse auxiliare.
$slugs_in_order = [
    // Coloana 1
    'pixuri-cu-pasta',
    'pixuri-cu-gel',
    'pix-carioca',
    'mine-pixuri',
    'rollere',
    'stilouri-si-rollere-cu-rezerve',
    'rezerve-cerneala',
    'creioane-mecanice',
    'mine-creion-mecanic',
    // Coloana 2
    'markere-universale',
    'markere-permanente',
    'markere-whiteboard-flipchart',
    'markere-text',
    'linere',
    'corectoare',
    'gume-de-sters',
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
