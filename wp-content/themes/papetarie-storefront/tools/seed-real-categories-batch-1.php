<?php

declare(strict_types=1);

/**
 * Seed batch 1 of the real product category tree, replacing the
 * placeholder/demo categories it directly supersedes.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/seed-real-categories-batch-1.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

function pap_seed_category(string $name, string $slug, int $parentId = 0): int
{
    $existing = get_term_by('slug', $slug, 'product_cat');

    if ($existing instanceof WP_Term) {
        wp_update_term(
            $existing->term_id,
            'product_cat',
            [
                'name' => $name,
                'parent' => $parentId,
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
        ]
    );

    if (is_wp_error($created)) {
        throw new RuntimeException('Could not create category ' . $name . ': ' . $created->get_error_message());
    }

    echo "Created category '{$name}' ({$slug}).\n";

    return (int) $created['term_id'];
}

function pap_delete_term_tree(string $slug): void
{
    $term = get_term_by('slug', $slug, 'product_cat');

    if (!($term instanceof WP_Term)) {
        return;
    }

    $children = get_terms(
        [
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $term->term_id,
        ]
    );

    if (!is_wp_error($children)) {
        foreach ($children as $child) {
            pap_delete_term_tree($child->slug);
        }
    }

    if ((int) $term->count > 0) {
        echo "Skipped deleting '{$term->name}' ({$slug}) — has {$term->count} real product(s).\n";

        return;
    }

    wp_delete_term($term->term_id, 'product_cat');
    echo "Deleted category '{$term->name}' ({$slug}).\n";
}

// --- 1. Remove old categories fully consolidated into the new tree ---
foreach (['capsatoare-si-perforatoare', 'accesorii-pentru-birou', 'echipamente-birou', 'instrumente-de-scris-si-corectura'] as $old_slug) {
    pap_delete_term_tree($old_slug);
}

// --- 2. Rebuild subcategories for existing parents ---
$articole_hartie_id = pap_seed_category('Articole din hârtie', 'articole-din-hartie');

foreach (['registre-si-repertoare', 'agende', 'organizere', 'notebook-uri', 'calendare', 'rezerve-organizere'] as $old_child_slug) {
    pap_delete_term_tree($old_child_slug);
}

foreach (['Hârtie copiator', 'Hârtie color', 'Hârtie specială', 'Post-it / Sticky Notes'] as $name) {
    pap_seed_category($name, sanitize_title($name), $articole_hartie_id);
}

$articole_scolare_id = pap_seed_category('Articole școlare', 'articole-scolare');

foreach (['creioane-colorate', 'creioane-cerate', 'carioci', 'rechizite-creative'] as $old_child_slug) {
    pap_delete_term_tree($old_child_slug);
}

foreach (['Caiete', 'Coperți și etichete', 'Penare', 'Ghiozdane', 'Accesorii'] as $name) {
    pap_seed_category($name, sanitize_title($name), $articole_scolare_id);
}

// --- 3. New top-level categories ---
$parents = get_terms(
    [
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0,
    ]
);

$max_order = 0;
if (!is_wp_error($parents)) {
    foreach ($parents as $parent) {
        $max_order = max($max_order, (int) get_term_meta((int) $parent->term_id, 'order', true));
    }
}

$articole_birou_id = pap_seed_category('Articole pentru birou', 'articole-pentru-birou');
update_term_meta($articole_birou_id, 'order', $max_order + 1);

foreach ([
    'Capsatoare',
    'Decapsatoare / Capse',
    'Perforatoare',
    'Distrugătoare hârtie',
    'Ghilotine hârtie',
    'Laminatoare',
    'Suporturi birou',
    'Coșuri hârtie',
    'Benzi adezive',
    'Foarfeci',
    'Accesorii birou',
    'Întreținere și curățenie',
    'Calculatoare',
] as $name) {
    pap_seed_category($name, sanitize_title($name), $articole_birou_id);
}

$accesorii_scris_id = pap_seed_category('Accesorii pentru scris', 'accesorii-pentru-scris');
update_term_meta($accesorii_scris_id, 'order', $max_order + 2);

foreach ([
    'Pixuri cu pastă',
    'Pixuri cu gel',
    'Mine pixuri',
    'Rollere',
    'Linere',
    'Stilouri și rollere cu rezerve',
    'Rezerve cerneală',
    'Creioane mecanice',
    'Mine creion mecanic',
    'Markere universale',
    'Markere permanente',
    'Markere whiteboard / flipchart',
    'Markere text',
    'Gume de șters',
    'Corectoare',
] as $name) {
    pap_seed_category($name, sanitize_title($name), $accesorii_scris_id);
}

echo "Done.\n";
