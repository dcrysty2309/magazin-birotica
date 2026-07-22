<?php

declare(strict_types=1);

/**
 * Seed batch 2 of the real product category tree: the 5 remaining
 * top-level categories from the Aperta catalog (Organizare/Arhivare/
 * Prezentare, Artă, Creativitate, Periferice, Curățenie și sanitare),
 * replacing the old orphan demo categories they supersede.
 *
 * 3 subcategory names collide with ones already seeded in batch 1
 * ("Pixuri cu gel", "Linere" under Accesorii pentru scris; "Accesorii"
 * under Articole școlare) — those get an explicit disambiguating slug
 * here so they don't get renamed/reparented in place of the existing term.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/seed-real-categories-batch-2.php
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

/**
 * @param array<int, string|array{0: string, 1: string}> $items
 */
function pap_seed_subcategories(array $items, int $parentId): void
{
    foreach ($items as $item) {
        if (is_array($item)) {
            [$name, $slug] = $item;
        } else {
            $name = $item;
            $slug = sanitize_title($name);
        }

        pap_seed_category($name, $slug, $parentId);
    }
}

// --- 1. Remove old orphan demo categories fully consolidated into the new tree ---
foreach ([
    'cd-uri',
    'flipchart-uri',
    'folii-pentru-laminat',
    'folii-protectie-documente',
    'seturi-birou',
    'dvd-uri',
    'folii-autolaminante',
    'suporturi-pentru-cub-hartie',
    'whiteboard-uri',
    'carcase-cd-dvd',
    'coperti-pentru-indosariere',
    'dosare-si-mape',
    'organizatoare-birou',
    'whiteboard-uri-rotative',
    'coperti-termice',
    'plicuri-cd',
    'separatoare-si-index',
    'suporturi-documente',
    'table-din-sticla',
    'copy-holder',
    'inele-plastic',
    'organizare',
    'plannere-magnetice',
    'suporturi-cd-dvd',
    'accesorii-whiteboard',
    'inele-metal',
    'kituri-curatare',
    'sisteme-de-prezentare-si-afisare',
    'consumabile-si-indosariere',
    'accesorii-it',
] as $old_slug) {
    pap_delete_term_tree($old_slug);
}

// --- 2. New top-level categories ---
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

$organizare_id = pap_seed_category('Organizare, arhivare, prezentare', 'organizare-arhivare-prezentare');
update_term_meta($organizare_id, 'order', $max_order + 1);

pap_seed_subcategories(
    [
        'Bibliorafturi',
        'Caiete mecanice',
        'Intercalatoare',
        'Indexuri',
        'Arhivare',
        'Dosare din carton',
        'Dosare suspendate',
        'Dosare plastic',
        'Folii și mape de protecție',
        'Clipboarduri',
        'Mape și accesorii',
        'Ecusoane și accesorii',
        'Afișare și prezentare',
        'Etichete universale pentru copiator',
        'Aparat aplicat prețuri',
        'Tușieră pentru aparat prețuri Smart',
        'Etichete și autoadezive',
        'Plicuri',
        'Benzi cauciuc/adezive',
        'Accesorii și cutii din carton',
    ],
    $organizare_id
);

$arta_id = pap_seed_category('Artă', 'arta');
update_term_meta($arta_id, 'order', $max_order + 2);

pap_seed_subcategories(
    [
        'Acrilice',
        'Culori ulei',
        'Crafts',
        'Markere și culori pentru sticlă și porțelan',
        'Marker pentru textile',
        'Mucki',
        'Fun/Tatoo',
        'Accesorii pictură',
    ],
    $arta_id
);

$creativitate_id = pap_seed_category('Creativitate', 'creativitate');
update_term_meta($creativitate_id, 'order', $max_order + 3);

pap_seed_subcategories(
    [
        'Markere acrilice',
        'Markere acrilice efect metalic',
        'Markere cu efect chrom',
        'Markere cretă lichidă',
        'Markere cu vopsea',
        'Markere Twin',
        'Spray acrilic',
        'Linere color',
        ['Linere creative', 'linere-creative'],
        'Stilou caligrafie',
        'Blocuri desen și schițe',
        'Blocuri mix media',
        ['Pixuri cu gel creative', 'pixuri-cu-gel-creative'],
        'Brushpen',
        'Carioci',
        'Carioci textile',
        'Creioane colorate',
        'Creioane HB',
        'Creioane cerate',
        'Ascuțitoare',
        'Acuarele Tempera',
        'Acuarele',
        'Mask-up',
        'Plastilină',
        'Seturi creative',
        'Lipici',
        'Pensule',
        'Accesorii creative',
        'Art și craft',
        'Seturi colorat și pictură',
    ],
    $creativitate_id
);

$periferice_id = pap_seed_category('Periferice', 'periferice');
update_term_meta($periferice_id, 'order', $max_order + 4);

pap_seed_subcategories(
    [
        'Căști',
        'Boxe',
        'Mouse',
        'Tastatură',
        'Camere',
        'Baterii externe',
        'Încărcătoare',
        'Cabluri',
        ['Accesorii periferice', 'accesorii-periferice'],
    ],
    $periferice_id
);

$curatenie_id = pap_seed_category('Curățenie și sanitare', 'curatenie-si-sanitare');
update_term_meta($curatenie_id, 'order', $max_order + 5);

pap_seed_subcategories(
    [
        'Bureți',
        'Lavete',
        'Prosoape de hârtie și dispensere',
        'Hârtie igienică și dispensere',
        'Săpunuri și dispensere',
        'Detergenți de vase și geamuri',
        'Soluții diverse pentru curățenie',
        'Mănuși menaj',
        'Mături și mopuri',
        'Accesorii menaj',
        'Sanitare',
    ],
    $curatenie_id
);

echo "Done.\n";
