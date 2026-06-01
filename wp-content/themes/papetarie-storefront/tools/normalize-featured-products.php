<?php
declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_products')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$theme_dir = dirname(__DIR__);
$assets_dir = $theme_dir . '/assets/images';

function pap_attach_seed_asset(string $source): int
{
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'meta_key' => '_pap_seed_source',
        'meta_value' => $source,
        'fields' => 'ids',
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $uploads = wp_upload_dir();
    $target_dir = trailingslashit($uploads['path']);
    wp_mkdir_p($target_dir);
    $filename = wp_unique_filename($target_dir, basename($source));
    $target = $target_dir . $filename;
    copy($source, $target);

    $filetype = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
        'post_status' => 'inherit',
    ], $target);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $target);
    wp_update_attachment_metadata($attachment_id, $metadata);
    update_post_meta($attachment_id, '_pap_seed_source', $source);

    return (int) $attachment_id;
}

$map = [
    'Caiet spirală A5' => ['name' => 'Caiet spirală A5', 'subtitle' => '160 file, dictando', 'image' => $assets_dir . '/product-notebook-a5.png'],
    'Pix cu bilă albastru' => ['name' => 'Pix cu bilă albastru', 'subtitle' => '0.7 mm, corp ergonomic', 'image' => $assets_dir . '/product-pens-blue.png'],
    'Set pixuri albastre' => ['name' => 'Calculator de birou', 'subtitle' => 'Display mare, taste soft touch', 'image' => $assets_dir . '/product-calculator.png'],
    'Biblioraft A4' => ['name' => 'Biblioraft A4', 'subtitle' => '75 mm, albastru', 'image' => $assets_dir . '/product-binders-a4.png'],
    'Sticky notes neon' => ['name' => 'Sticky notes neon', 'subtitle' => '76x76 mm, 100 file', 'image' => $assets_dir . '/product-sticky-notes.png'],
    'Organizator birou' => ['name' => 'Organizator birou', 'subtitle' => 'Plasă metalică, compartimente multiple', 'image' => $assets_dir . '/product-mesh-organizer.png'],
    'Set highlightere' => ['name' => 'Set highlightere', 'subtitle' => '4 culori fluorescente', 'image' => $assets_dir . '/product-highlighters.png'],
    'Marker pastel' => ['name' => 'Foarfecă birou', 'subtitle' => 'Lame din oțel, mâner ergonomic', 'image' => $assets_dir . '/product-scissors.png'],
    'Dosar A4 cu șină' => ['name' => 'Clipboard A4', 'subtitle' => 'Prindere metalică, finisaj mat', 'image' => $assets_dir . '/product-clipboard.png'],
    'Bloc notes autoadeziv' => ['name' => 'Corector bandă', 'subtitle' => 'Aplicare curată, uscare instant', 'image' => $assets_dir . '/product-correction-tape.png'],
    'Caiet A5 pentru notițe' => ['name' => 'Hârtie copiator A4', 'subtitle' => 'Top 500 coli, 80 g/mp', 'image' => $assets_dir . '/product-paper-ream.png'],
    'Suport birou accesorii' => ['name' => 'Lipici stick', 'subtitle' => 'Aplicare uniformă, uscare rapidă', 'image' => $assets_dir . '/product-glue-stick.png'],
];

$products = wc_get_products([
    'status' => 'publish',
    'limit' => 30,
    'featured' => true,
]);

foreach ($products as $product) {
    $current_name = $product->get_name();
    if (!isset($map[$current_name])) {
        continue;
    }

    $row = $map[$current_name];
    $product->set_name($row['name']);
    $product->set_short_description($row['subtitle']);
    $product->set_description($row['subtitle'] . '. Produs recomandat pentru birou și utilizare zilnică.');
    $product->save();

    $attachment_id = pap_attach_seed_asset($row['image']);
    if ($attachment_id > 0) {
        set_post_thumbnail($product->get_id(), $attachment_id);
    }

    echo "Updated #{$product->get_id()} => {$row['name']}\n";
}

echo "Done.\n";
