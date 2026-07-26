<?php

declare(strict_types=1);

/**
 * Set explicit 'order' term meta on the "Creativitate" subcategories so the
 * mega menu le listează grupate logic (nu alfabetic) în 4 coloane:
 * instrumente de scris/desenat, accesorii de scris/desenat, pictură/modelaj,
 * markere speciale/seturi. 4 coloane (nu 2-3) pentru că panoul de pe homepage
 * are o înălțime fixă (dictată de imaginea din slider) — cu 30 de
 * subcategorii, doar max. 8 pe coloană încap fără să se taie. Reuses the
 * same 'order' term meta mechanism papetarie_storefront_sort_terms() already
 * reads.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/order-creativitate-subcats.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

// Coloana 1 — instrumente de scris și desenat.
// Coloana 2 — accesorii de scris și desenat.
// Coloana 3 — pictură și modelaj.
// Coloana 4 — markere speciale și seturi.
$slugs_in_order = [
    // Coloana 1
    'creioane-cerate',
    'creioane-colorate',
    'creioane-hb',
    'carioci',
    'carioci-textile',
    'brushpen',
    'linere-color',
    'linere-creative',
    // Coloana 2
    'ascutitoare',
    'stilou-caligrafie',
    'pixuri-cu-gel-creative',
    'lipici',
    'accesorii-creative',
    'art-si-craft',
    'blocuri-desen-si-schite',
    'blocuri-mix-media',
    // Coloana 3
    'acuarele',
    'acuarele-tempera',
    'pensule',
    'plastilina',
    'mask-up',
    'spray-acrilic',
    'seturi-colorat-si-pictura',
    // Coloana 4
    'markere-acrilice',
    'markere-acrilice-efect-metalic',
    'markere-creta-lichida',
    'markere-cu-efect-chrom',
    'markere-cu-vopsea',
    'markere-twin',
    'seturi-creative',
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
