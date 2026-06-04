<?php

declare(strict_types=1);

/**
 * Assign the local test products to meaningful WooCommerce categories.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/seed-test-category-assignments.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$assignments = [
    'caiet-spirala-a5-160-file' => ['notebook-uri'],
    'pix-cu-bila-albastru-07' => ['pixuri-cu-mecanism'],
    'set-pixuri-albastre-10-buc' => ['pixuri-cu-mecanism'],
    'biblioraft-a4-75mm' => ['bibliorafturi'],
    'sticky-notes-neon-76x76' => ['accesorii-marunte-birou'],
    'organizator-birou-metalic' => ['organizatoare-birou'],
    'highlightere-set-4' => ['markere-si-evidentiatoare'],
    'marker-text-pastel' => ['markere-si-evidentiatoare'],
    'dosar-a4-cu-sina' => ['dosare-si-mape'],
    'bloc-notes-autoadeziv' => ['notebook-uri'],
    'caiet-a5-notite-zilnice' => ['notebook-uri'],
    'suport-birou-pentru-accesorii' => ['seturi-birou'],
];

$default_category = (int) get_option('default_product_cat');

foreach ($assignments as $product_slug => $category_slugs) {
    $post = get_page_by_path($product_slug, OBJECT, 'product');

    if (!$post instanceof WP_Post) {
        echo "Skipped {$product_slug}: product not found\n";
        continue;
    }

    $term_ids = [];
    foreach ($category_slugs as $category_slug) {
        $term = get_term_by('slug', $category_slug, 'product_cat');
        if ($term instanceof WP_Term) {
            $term_ids[] = (int) $term->term_id;
        }
    }

    if (!$term_ids) {
        echo "Skipped {$product_slug}: no matching categories\n";
        continue;
    }

    wp_set_object_terms($post->ID, $term_ids, 'product_cat', false);

    if ($default_category > 0) {
        wp_remove_object_terms($post->ID, [$default_category], 'product_cat');
    }

    echo "Assigned {$product_slug} -> " . implode(', ', $category_slugs) . PHP_EOL;
}

echo "Done." . PHP_EOL;
