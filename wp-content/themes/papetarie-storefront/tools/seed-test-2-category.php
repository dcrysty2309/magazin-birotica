<?php

declare(strict_types=1);

require '/var/www/html/wp-load.php';

if (!taxonomy_exists('product_cat')) {
    throw new RuntimeException('WooCommerce taxonomy product_cat is not available.');
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

$test2Id = pap_seed_category(
    'Test 2',
    'test-2',
    0,
    'Categorie de test pentru poziționare și hover în lista principală.'
);

update_term_meta($test2Id, 'order', 5);

echo 'Test 2 term id=' . $test2Id . PHP_EOL;
