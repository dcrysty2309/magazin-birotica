<?php
/**
 * Seed 12 featured WooCommerce products for the homepage carousel.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/seed-featured-products.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product_object')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$theme_dir = dirname(__DIR__);
$assets_dir = $theme_dir . '/assets/images';

$product_rows = [
    ['slug' => 'caiet-spirala-a5-160-file', 'name' => 'Caiet spirală A5', 'subtitle' => '160 file, dictando', 'price' => '14.90', 'image' => $assets_dir . '/product-notebook-a5.png'],
    ['slug' => 'pix-cu-bila-albastru-07', 'name' => 'Pix cu bilă albastru', 'subtitle' => '0.7 mm, corp ergonomic', 'price' => '2.49', 'image' => $assets_dir . '/product-pens-blue.png'],
    ['slug' => 'set-pixuri-albastre-10-buc', 'name' => 'Set pixuri albastre', 'subtitle' => 'Pachet 10 bucăți', 'price' => '18.90', 'image' => $assets_dir . '/product-pens-blue.png'],
    ['slug' => 'biblioraft-a4-75mm', 'name' => 'Biblioraft A4', 'subtitle' => '75 mm, albastru', 'price' => '7.90', 'image' => $assets_dir . '/product-binders-a4.png'],
    ['slug' => 'sticky-notes-neon-76x76', 'name' => 'Sticky notes neon', 'subtitle' => '76x76 mm, 100 file', 'price' => '5.90', 'image' => $assets_dir . '/product-sticky-notes.png'],
    ['slug' => 'organizator-birou-metalic', 'name' => 'Organizator birou', 'subtitle' => 'Plasă metalică, compartimente multiple', 'price' => '19.90', 'image' => $assets_dir . '/product-mesh-organizer.png'],
    ['slug' => 'highlightere-set-4', 'name' => 'Set highlightere', 'subtitle' => '4 culori fluorescente', 'price' => '9.90', 'image' => $assets_dir . '/product-highlighters.png'],
    ['slug' => 'marker-text-pastel', 'name' => 'Marker pastel', 'subtitle' => 'Vârf teșit, uscare rapidă', 'price' => '4.90', 'image' => $assets_dir . '/product-highlighters.png'],
    ['slug' => 'dosar-a4-cu-sina', 'name' => 'Dosar A4 cu șină', 'subtitle' => 'Pentru documente și proiecte', 'price' => '3.90', 'image' => $assets_dir . '/product-binders-a4.png'],
    ['slug' => 'bloc-notes-autoadeziv', 'name' => 'Bloc notes autoadeziv', 'subtitle' => 'Pastel, 400 file', 'price' => '8.50', 'image' => $assets_dir . '/product-sticky-notes.png'],
    ['slug' => 'caiet-a5-notite-zilnice', 'name' => 'Caiet A5 pentru notițe', 'subtitle' => 'Copertă rigidă, 120 file', 'price' => '16.90', 'image' => $assets_dir . '/product-notebook-a5.png'],
    ['slug' => 'suport-birou-pentru-accesorii', 'name' => 'Suport birou accesorii', 'subtitle' => 'Pentru pixuri, markere și rigle', 'price' => '24.90', 'image' => $assets_dir . '/product-mesh-organizer.png'],
];

function pap_seed_attachment(string $file_path): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'meta_key' => '_pap_seed_source',
        'meta_value' => $file_path,
        'fields' => 'ids',
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $uploads = wp_upload_dir();
    $target_dir = trailingslashit($uploads['path']);
    wp_mkdir_p($target_dir);

    $filename = wp_unique_filename($target_dir, basename($file_path));
    $target = $target_dir . $filename;
    copy($file_path, $target);

    $filetype = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content' => '',
        'post_status' => 'inherit',
    ], $target);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $target);
    wp_update_attachment_metadata($attachment_id, $metadata);
    update_post_meta($attachment_id, '_pap_seed_source', $file_path);

    return (int) $attachment_id;
}

foreach ($product_rows as $row) {
    $existing = get_page_by_path($row['slug'], OBJECT, 'product');
    $product = $existing instanceof WP_Post ? wc_get_product($existing->ID) : wc_get_product_object('simple');

    if (!$product instanceof WC_Product) {
        continue;
    }

    $product->set_name($row['name']);
    $product->set_slug($row['slug']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price($row['price']);
    $product->set_price($row['price']);
    $product->set_featured(true);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_short_description($row['subtitle']);
    $product->set_description($row['subtitle'] . '. Produs recomandat pentru birou, școală și organizare zilnică.');
    $product_id = $product->save();

    $attachment_id = pap_seed_attachment($row['image']);
    if ($attachment_id > 0) {
        set_post_thumbnail($product_id, $attachment_id);
    }

    echo "Seeded product #{$product_id}: {$row['name']}\n";
}

echo "Done.\n";
