<?php

declare(strict_types=1);

/**
 * Seed the real product brand list (product_brand taxonomy terms).
 * No products are assigned — brands start at 0 until real product
 * data is added, same as the batch-1 categories.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/seed-brands.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

if (!taxonomy_exists('product_brand')) {
    fwrite(STDERR, "Taxonomy 'product_brand' is not registered.\n");
    exit(1);
}

function pap_seed_brand(string $name, string $slug): int
{
    $existing = get_term_by('slug', $slug, 'product_brand');

    if ($existing instanceof WP_Term) {
        wp_update_term($existing->term_id, 'product_brand', ['name' => $name]);

        return (int) $existing->term_id;
    }

    $created = wp_insert_term($name, 'product_brand', ['slug' => $slug]);

    if (is_wp_error($created)) {
        throw new RuntimeException('Could not create brand ' . $name . ': ' . $created->get_error_message());
    }

    echo "Created brand '{$name}' ({$slug}).\n";

    return (int) $created['term_id'];
}

foreach ([
    'Esselte',
    'Leitz',
    'Exacompta',
    'Durable',
    'Avery',
    'Fellowes',
    'Herlitz',
] as $brand_name) {
    pap_seed_brand($brand_name, sanitize_title($brand_name));
}

echo "Done.\n";
