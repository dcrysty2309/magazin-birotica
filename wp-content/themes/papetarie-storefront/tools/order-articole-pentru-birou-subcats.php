<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Articole pentru birou" subcategories
 * so the mega menu le listează grupate logic (nu alfabetic) în 2 coloane:
 * instrumente/accesorii de birou vs. echipamente de birou. Reuses the same
 * 'order' term meta mechanism papetarie_storefront_sort_terms() already reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-articole-pentru-birou-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Coloana 1 — instrumente și accesorii de birou (folosite manual, zilnic).
// Coloana 2 — echipamente de birou (aparate) + întreținere.
$slugs_in_order = [
    // Coloana 1
    'accesorii-birou',
    'benzi-adezive',
    'capsatoare',
    'decapsatoare-capse',
    'foarfeci',
    'perforatoare',
    'suporturi-birou',
    // Coloana 2
    'calculatoare',
    'cosuri-hartie',
    'distrugatoare-hartie',
    'ghilotine-hartie',
    'laminatoare',
    'intretinere-si-curatenie',
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
