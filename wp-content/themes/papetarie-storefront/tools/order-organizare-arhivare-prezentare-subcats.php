<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Organizare și arhivare"
 * subcategories so the mega menu lists them grupate logic în 3 coloane
 * (arhivare / organizarea documentelor / prezentare și etichetare) în loc de
 * alfabetic. Reuses the same 'order' term meta mechanism
 * papetarie_storefront_sort_terms() already reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-organizare-arhivare-prezentare-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Coloana 1 — arhivare (6).
// Coloana 2 — organizarea documentelor (7).
// Coloana 3 — prezentare și etichetare (7).
$slugs_in_order = [
    // Coloana 1 — Arhivare
    'bibliorafturi',
    'dosare-din-carton',
    'dosare-plastic',
    'dosare-suspendate',
    'arhivare',
    'accesorii-si-cutii-din-carton',
    // Coloana 2 — Organizarea documentelor
    'mape-si-accesorii',
    'folii-si-mape-de-protectie',
    'intercalatoare',
    'indexuri',
    'caiete-mecanice',
    'plicuri',
    'benzi-cauciuc-adezive',
    // Coloana 3 — Prezentare și etichetare
    'clipboarduri',
    'afisare-si-prezentare',
    'ecusoane-si-accesorii',
    'etichete-si-autoadezive',
    'etichete-universale-pentru-copiator',
    'aparat-aplicat-preturi',
    'tusiera-pentru-aparat-preturi-smart',
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
