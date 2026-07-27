<?php

declare(strict_types=1);

/**
 * Re-normalizeaza pap_attr_group pe termenii product_attr_value deja creati,
 * dupa ce papetarie_storefront_aperta_normalize_attr_group() a capatat reguli
 * noi (ex. "Nr. File" / "Numar File" / "Numar file" -> "Numar file"). Fara
 * asta, regula noua se aplica doar la termeni noi - cei deja creati raman cu
 * grupul vechi in meta, deci filtrele tot ar aparea fragmentate.
 *
 * Nu atinge sloturile/relatiile produs-termen, doar corecteaza eticheta de
 * grup din term meta - sigur de rulat oricand, fara risc de date.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/normalize-attribute-groups.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('papetarie_storefront_aperta_normalize_attr_group')) {
    fwrite(STDERR, "Functia de normalizare nu e disponibila.\n");
    exit(1);
}

$terms = get_terms([
    'taxonomy' => 'product_attr_value',
    'hide_empty' => false,
]);

if (is_wp_error($terms)) {
    fwrite(STDERR, "Eroare la citirea termenilor: " . $terms->get_error_message() . "\n");
    exit(1);
}

$updated = 0;
$unchanged = 0;

foreach ($terms as $term) {
    $currentGroup = (string) get_term_meta($term->term_id, 'pap_attr_group', true);
    if ($currentGroup === '') {
        continue;
    }

    $normalizedGroup = papetarie_storefront_aperta_normalize_attr_group($currentGroup);

    if ($normalizedGroup === $currentGroup) {
        $unchanged++;
        continue;
    }

    update_term_meta($term->term_id, 'pap_attr_group', $normalizedGroup);
    $updated++;
    echo "  [{$term->name}] \"{$currentGroup}\" -> \"{$normalizedGroup}\"\n";
}

echo "Gata. Corectate: {$updated}, neschimbate: {$unchanged}.\n";
