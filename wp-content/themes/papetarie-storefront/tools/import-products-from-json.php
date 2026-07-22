<?php

declare(strict_types=1);

/**
 * Import real products from a normalized JSON file (see tools/data/*.json,
 * exported from the Aperta product catalog spreadsheet). Creates one simple
 * WooCommerce product per entry using the real name and real price already
 * in the sheet — no image yet (WooCommerce's placeholder shows until one is
 * added), published immediately so the category is browsable.
 *
 * Idempotent: safe to re-run. Each product is tagged with a `_pap_import_key`
 * meta value; rows already imported are skipped rather than duplicated.
 *
 * Expected JSON shape:
 * {
 *   "top_level_slug": "articole-din-hartie",
 *   "subcategories": {
 *     "Hârtie copiator": {
 *       "slug": "hartie-copiator",
 *       "products": [{"name": "...", "price": 17.81, "obs": "optional"}]
 *     }
 *   }
 * }
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/import-products-from-json.php wp-content/themes/papetarie-storefront/tools/data/articole-din-hartie.json
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

function pap_find_product_by_import_key(string $key): ?int
{
    $ids = get_posts(
        [
            'post_type' => 'product',
            'post_status' => 'any',
            'meta_key' => '_pap_import_key',
            'meta_value' => $key,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]
    );

    return isset($ids[0]) ? (int) $ids[0] : null;
}

$jsonPath = $argv[1] ?? null;

if (!$jsonPath || !is_readable($jsonPath)) {
    fwrite(STDERR, "Usage: php import-products-from-json.php <path-to-json>\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($jsonPath), true);

if (!is_array($data) || !isset($data['subcategories']) || !is_array($data['subcategories'])) {
    fwrite(STDERR, "Invalid JSON structure in {$jsonPath}.\n");
    exit(1);
}

$topLevelSlug = $data['top_level_slug'] ?? basename($jsonPath, '.json');
$created = 0;
$skipped = 0;

foreach ($data['subcategories'] as $subcatName => $subcat) {
    $slug = $subcat['slug'] ?? null;

    if (!$slug) {
        fwrite(STDERR, "No slug mapped for subcategory '{$subcatName}', skipping its products.\n");
        continue;
    }

    $term = get_term_by('slug', $slug, 'product_cat');

    if (!($term instanceof WP_Term)) {
        fwrite(STDERR, "Category '{$slug}' not found — run the category seed script first. Skipping '{$subcatName}'.\n");
        continue;
    }

    foreach ($subcat['products'] as $i => $product) {
        $importKey = $topLevelSlug . ':' . $slug . ':' . $i;

        if (pap_find_product_by_import_key($importKey) !== null) {
            $skipped++;
            continue;
        }

        $wcProduct = new WC_Product_Simple();
        $wcProduct->set_name((string) $product['name']);
        $wcProduct->set_status('publish');
        $wcProduct->set_catalog_visibility('visible');

        if (isset($product['price'])) {
            $wcProduct->set_regular_price((string) $product['price']);
        }

        if (!empty($product['obs'])) {
            $wcProduct->set_short_description(esc_html((string) $product['obs']));
        }

        $wcProduct->set_category_ids([$term->term_id]);
        $wcProduct->set_manage_stock(false);
        $wcProduct->set_stock_status('instock');

        $productId = $wcProduct->save();

        update_post_meta($productId, '_pap_import_key', $importKey);
        update_post_meta($productId, '_pap_import_source', basename($jsonPath));

        $created++;
    }
}

echo "Created {$created} product(s), skipped {$skipped} already-imported.\n";
