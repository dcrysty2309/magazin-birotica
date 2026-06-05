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

function pap_seed_category(string $name, string $slug, int $parentId = 0, string $description = ''): int
{
    $existing = get_term_by('slug', $slug, 'product_cat');

    if ($existing instanceof WP_Term) {
        wp_update_term(
            $existing->term_id,
            'product_cat',
            [
                'name' => $name,
                'parent' => $parentId,
                'description' => $description,
                'slug' => $slug,
            ]
        );

        return (int) $existing->term_id;
    }

    $created = wp_insert_term(
        $name,
        'product_cat',
        [
            'slug' => $slug,
            'parent' => $parentId,
            'description' => $description,
        ]
    );

    if (is_wp_error($created)) {
        throw new RuntimeException('Could not create category ' . $name . ': ' . $created->get_error_message());
    }

    return (int) $created['term_id'];
}

function pap_delete_category_if_empty(string $slug): void
{
    $term = get_term_by('slug', $slug, 'product_cat');

    if (!($term instanceof WP_Term)) {
        return;
    }

    if ((int) $term->count === 0) {
        wp_delete_term($term->term_id, 'product_cat');
    }
}

$test_parent_id = pap_seed_category(
    'Test',
    'test',
    0,
    'Categorie de lucru pentru produsele aflate în dezvoltare.'
);

$test_child_id = pap_seed_category(
    'Produse test',
    'produse-test',
    $test_parent_id,
    'Produse de test folosite pentru dezvoltarea site-ului.'
);

$parents = get_terms(
    [
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
        'exclude' => array_filter([(int) get_option('default_product_cat')]),
    ]
);

$max_order = 0;
if (!is_wp_error($parents) && $parents) {
    foreach ($parents as $parent) {
        $max_order = max($max_order, (int) get_term_meta((int) $parent->term_id, 'order', true));
    }
}

update_term_meta($test_parent_id, 'order', $max_order + 1);
update_term_meta($test_child_id, 'order', 0);

$default_category = (int) get_option('default_product_cat');
$product_ids = get_posts(
    [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
    ]
);

foreach ($product_ids as $product_id) {
    wp_set_object_terms((int) $product_id, [$test_child_id], 'product_cat', false);

    if ($default_category > 0) {
        wp_remove_object_terms((int) $product_id, [$default_category], 'product_cat');
    }

    echo "Assigned product {$product_id} -> produse-test" . PHP_EOL;
}

pap_delete_category_if_empty('uncategorized');
pap_delete_category_if_empty('casual');
pap_delete_category_if_empty('travel');

echo "Done." . PHP_EOL;
