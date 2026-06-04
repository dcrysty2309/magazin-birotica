<?php

require '/var/www/html/wp-load.php';

$seedPath = __DIR__ . '/seed-navigation.php';
$seedText = file_get_contents($seedPath);

if ($seedText === false) {
    throw new RuntimeException('Could not read seed-navigation.php');
}

preg_match_all("/'name'\\s*=>\\s*'([^']+)'\\s*,\\s*'slug'\\s*=>\\s*'([^']+)'/u", $seedText, $matches, PREG_SET_ORDER);

$updated = 0;

foreach ($matches as $match) {
    $name = $match[1];
    $slug = $match[2];
    $term = get_term_by('slug', $slug, 'product_cat');

    if ($term instanceof WP_Term) {
        $result = wp_update_term(
            $term->term_id,
            'product_cat',
            [
                'name' => $name,
                'slug' => $slug,
            ]
        );

        if (!is_wp_error($result)) {
            $updated++;
        }
    }
}

echo 'updated=' . $updated . PHP_EOL;
