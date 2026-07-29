<?php

declare(strict_types=1);

/**
 * Unifica produsele simple deja importate care sunt de fapt culori diferite
 * ale aceluiasi articol (ex. "Pix Reco Schneider negru" / "...alb" /
 * "...albastru" - trei produse separate in loc de unul variabil cu 3 culori).
 *
 * Grupeaza dupa (nume-fara-sufix-de-culoare, brand), folosind aceeasi logica
 * de detectie ca fix-ul permanent din includes/aperta-sync.php
 * (papetarie_storefront_aperta_strip_color_suffix), ca cele doua sa se
 * recunoasca reciproc la sincronizarile viitoare.
 *
 * Implicit ruleaza in modul --dry-run (doar raport, nicio modificare).
 * Adauga --confirm ca sa aplice efectiv unificarea.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/consolidate-color-variants.php [--confirm]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$confirm = in_array('--confirm', array_slice($argv, 1), true);

$productIds = get_posts([
    'post_type' => 'product',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'fields' => 'ids',
    'posts_per_page' => -1,
]);

echo 'Analizez ' . count($productIds) . " de produse...\n";

$clusters = [];

foreach ($productIds as $productId) {
    $product = wc_get_product($productId);

    if (!$product instanceof WC_Product || !$product->is_type('simple')) {
        continue;
    }

    $stripped = papetarie_storefront_aperta_strip_color_suffix($product->get_name());
    if ($stripped === null) {
        continue;
    }

    $brandTerms = get_the_terms($productId, 'product_brand');
    $brandName = (!is_wp_error($brandTerms) && !empty($brandTerms)) ? $brandTerms[0]->name : '';

    $clusterKey = papetarie_storefront_aperta_synthetic_group_key($stripped['base'], $brandName);

    $clusters[$clusterKey][] = [
        'id' => $productId,
        'title' => $product->get_name(),
        'base' => $stripped['base'],
        'color' => $stripped['color'],
        'brand' => $brandName,
    ];
}

$clustersToMerge = array_filter($clusters, static fn(array $members): bool => count($members) >= 2);

echo 'Găsite ' . count($clustersToMerge) . " grupuri cu 2+ produse-culoare separate:\n\n";

foreach ($clustersToMerge as $clusterKey => $members) {
    $brand = $members[0]['brand'] !== '' ? $members[0]['brand'] : '(fără brand)';
    echo "— \"{$members[0]['base']}\" [{$brand}]\n";
    foreach ($members as $member) {
        echo "    #{$member['id']} \"{$member['title']}\" -> culoare: {$member['color']}\n";
    }
    echo "\n";
}

if (!$confirm) {
    echo "Mod dry-run (implicit) — nimic nu a fost modificat. Rulează cu --confirm ca să aplici unificarea.\n";
    exit(0);
}

echo "Aplic unificarea (--confirm)...\n\n";

$report = [];

foreach ($clustersToMerge as $clusterKey => $members) {
    usort($members, static fn(array $a, array $b): int => $a['id'] <=> $b['id']);
    $parentMember = array_shift($members);
    $parentId = $parentMember['id'];

    $parentProduct = wc_get_product($parentId);
    $parentSnapshot = [
        'sku' => $parentProduct->get_sku(),
        'price' => $parentProduct->get_regular_price(),
        'stock_status' => $parentProduct->get_stock_status(),
        'image_id' => $parentProduct->get_image_id(),
        'gallery_ids' => $parentProduct->get_gallery_image_ids(),
    ];

    $allColors = array_values(array_unique(array_map(
        static fn(array $m): string => $m['color'],
        array_merge([$parentMember], $members)
    )));

    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name('Culoare');
    $attribute->set_options($allColors);
    $attribute->set_visible(true);
    $attribute->set_variation(true);

    $variable = new WC_Product_Variable($parentId);
    $variable->set_name($parentMember['base']);
    $variable->set_attributes([$attribute]);
    $variable->set_sku('');
    $variable->save();

    update_post_meta($parentId, '_pap_aperta_cod_produs', $clusterKey);
    delete_post_meta($parentId, '_pap_aperta_sku');

    $variation = new WC_Product_Variation();
    $variation->set_parent_id($parentId);
    $variation->set_attributes(['culoare' => $parentMember['color']]);
    if ($parentSnapshot['sku'] !== '') {
        $variation->set_sku($parentSnapshot['sku']);
    }
    if ($parentSnapshot['price'] !== '') {
        $variation->set_regular_price($parentSnapshot['price']);
    }
    $variation->set_manage_stock(true);
    $variation->set_stock_status($parentSnapshot['stock_status']);
    if ($parentSnapshot['image_id']) {
        $variation->set_image_id($parentSnapshot['image_id']);
    }
    $newVariationId = $variation->save();
    if ($parentSnapshot['sku'] !== '') {
        update_post_meta($newVariationId, '_pap_aperta_sku', $parentSnapshot['sku']);
    }

    $absorbed = [];
    foreach ($members as $member) {
        $memberProduct = wc_get_product($member['id']);
        $sku = $memberProduct->get_sku();
        $price = $memberProduct->get_regular_price();
        $stockStatus = $memberProduct->get_stock_status();
        $imageId = $memberProduct->get_image_id();

        // WC_Product_Variation's data store refuses to read() a post whose
        // post_type isn't already 'product_variation' - trebuie schimbat
        // tipul postarii inainte de a instantia obiectul, nu dupa.
        wp_update_post([
            'ID' => $member['id'],
            'post_type' => 'product_variation',
            'post_parent' => $parentId,
        ]);

        $memberVariation = new WC_Product_Variation($member['id']);
        $memberVariation->set_parent_id($parentId);
        $memberVariation->set_attributes(['culoare' => $member['color']]);
        if ($sku !== '') {
            $memberVariation->set_sku($sku);
        }
        if ($price !== '') {
            $memberVariation->set_regular_price($price);
        }
        $memberVariation->set_manage_stock(true);
        $memberVariation->set_stock_status($stockStatus);
        if ($imageId) {
            $memberVariation->set_image_id($imageId);
        }
        $memberVariation->save();
        if ($sku !== '') {
            update_post_meta($member['id'], '_pap_aperta_sku', $sku);
        }

        $absorbed[] = "#{$member['id']} ({$member['color']})";
    }

    $galleryIds = array_values(array_filter(array_unique(array_merge(
        [$parentSnapshot['image_id']],
        $parentSnapshot['gallery_ids']
    ))));
    $variable = new WC_Product_Variable($parentId);
    if ($parentSnapshot['image_id']) {
        $variable->set_image_id($parentSnapshot['image_id']);
    }
    $variable->set_gallery_image_ids($galleryIds);
    $variable->save();

    $report[] = [
        'parent_id' => $parentId,
        'base_name' => $parentMember['base'],
        'parent_own_color' => $parentMember['color'],
        'parent_own_variation_id' => $newVariationId,
        'absorbed' => $absorbed,
    ];

    echo "Unificat: \"{$parentMember['base']}\" -> produs #{$parentId} (variabil), " . (count($members) + 1) . " culori.\n";
}

echo "\nGata. " . count($report) . " produse unificate.\n\n";
echo "=== Exemplu concret ===\n";
if (!empty($report)) {
    $example = $report[0];
    echo "Produs: \"{$example['base_name']}\" (acum #{$example['parent_id']}, produs variabil)\n";
    echo "  Variație proprie (culoarea originală a produsului-bază): #{$example['parent_own_variation_id']} ({$example['parent_own_color']})\n";
    echo '  Variații absorbite: ' . implode(', ', $example['absorbed']) . "\n";
}
