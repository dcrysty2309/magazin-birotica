<?php

declare(strict_types=1);

/**
 * Muta in cos de gunoi produsele importate din vechiul JSON static
 * (_pap_import_key, fara SKU, nume reformatate manual - nu se pot
 * reconcilia fiabil cu feed-ul Aperta). Sync-ul Aperta populeaza
 * catalogul de la zero dupa aceasta curatare.
 *
 * Reversibil: wp_delete_post() cu force=false -> merg in cosul de gunoi
 * WordPress, nu se sterg definitiv.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/purge-legacy-import-products.php
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$ids = get_posts([
    'post_type' => 'product',
    'post_status' => 'any',
    'meta_key' => '_pap_import_key',
    'fields' => 'ids',
    'posts_per_page' => -1,
]);

echo "Gasite " . count($ids) . " produse din importul vechi (_pap_import_key).\n";

$trashed = 0;
foreach ($ids as $id) {
    // Sanity check: nu atinge un produs deja migrat prin sync-ul Aperta.
    if (get_post_meta($id, '_pap_aperta_cod_produs', true) || get_post_meta($id, '_pap_aperta_sku', true)) {
        continue;
    }

    wp_delete_post($id, false);
    $trashed++;
}

echo "Mutate in cosul de gunoi: {$trashed}\n";
