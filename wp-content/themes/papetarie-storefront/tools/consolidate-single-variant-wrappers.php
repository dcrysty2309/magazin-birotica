<?php

declare(strict_types=1);

/**
 * Consolideaza o familie multi-culoare deja detectata de
 * papetarie_storefront_aperta_consolidate_by_shared_link() (membrii impart
 * acelasi "Link produs" Aperta, dar au coduri de produs separate) in cazul
 * in care membrii au fost deja publicati INDIVIDUAL, fiecare ca propriul lui
 * "produs variabil cu o singura variatie" - nu ca produse simple.
 *
 * De ce nu e suficient tools/migrate-legacy-simple-to-variation.php: acel
 * script asteapta ca membrii vechi sa fie produse SIMPLE (post_type=product,
 * fara copii) - le converteste in variatii. Aici membrii vechi sunt deja
 * fiecare cate un WC_Product_Variable cu exact 1 variatie (asa au fost
 * importati, inainte sa existe gruparea dupa "Link produs"), deci
 * find_by_sku_meta() gaseste direct variatia (post_type=product_variation),
 * iar scriptul vechi le-ar fi sarit ca "deja variatie, nimic de facut" -
 * confirmat gasit live 2026-09-04 (familia "Marker metalic Schneider
 * Paint-It 011 2 mm", 8 culori, fiecare propriul ei produs-parinte cu 1
 * variatie).
 *
 * Ce face, per membru vechi:
 *   1. Ia variatia lui existenta (SKU, pret, stoc, poza - neatinse).
 *   2. O RE-PARENTEAZA sub parintele-tinta comun (nu recreeaza - risc mai
 *      mic, mai putine date de verificat).
 *   3. Verifica ca noua parentare a prins corect.
 *   4. Doar daca verificarea trece: trece parintele vechi (acum gol) la
 *      gunoi si retine slug-ul lui vechi pentru redirect 301.
 *
 * Implicit DRY-RUN (doar raport, nimic modificat). Adauga --apply ca sa
 * chiar migrezi. --cluster=<cheie> alege familia (implicit: markerele
 * metalice Paint-It 011, singurul caz confirmat pana acum).
 *
 * Deschide in browser cu ?t=<token> (vezi PAP_CONSOLIDATE_TOOL_TOKEN) -
 * scriptul e in tools/, servit direct de PHP, nu prin wp-admin.
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

const PAP_CONSOLIDATE_TOOL_TOKEN = 'consolidate-2026-09-04-x7f3';

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (($_GET['t'] ?? '') !== PAP_CONSOLIDATE_TOOL_TOKEN) {
        http_response_code(404);
        exit;
    }
}

$apply = $isCli ? in_array('--apply', $argv, true) : (($_GET['apply'] ?? '') === '1');
$clusterArg = null;
if ($isCli) {
    foreach ($argv as $arg) {
        if (preg_match('/^--cluster=(.+)$/', $arg, $m)) {
            $clusterArg = $m[1];
        }
    }
} else {
    $clusterArg = $_GET['cluster'] ?? null;
}
$targetCluster = $clusterArg ?: 'link-d523733847f39fb4bcc2633d58827972'; // markere Paint-It 011

echo $apply ? "Mod APLICARE (migrare reala).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat). Adauga &apply=1 ca sa aplici.\n\n";
echo "Cluster tinta: $targetCluster\n\n";

$grouped = papetarie_storefront_aperta_read_products_grouped();
if (!isset($grouped[$targetCluster])) {
    echo "EROARE: clusterul '$targetCluster' nu (mai) exista in gruparea curenta a feedului.\n";
    exit;
}
$rows = $grouped[$targetCluster];

$name = trim((string) $rows[0]['Denumire produs']);
$brand = trim((string) $rows[0]['Brand produs']);
$attributeName = '';
foreach ($rows as $row) {
    if (trim((string) $row['Tip variant']) !== '') {
        $attributeName = trim((string) $row['Tip variant']);
        break;
    }
}
if ($attributeName === '') {
    $attributeName = 'Variantă';
}
$attributeKey = sanitize_title($attributeName);

echo "Familie: \"$name\" ($brand) - " . count($rows) . " membri\n\n";

$parentId = papetarie_storefront_aperta_find_parent_by_cod_produs($targetCluster);
$isNewParent = $parentId === null;
echo 'Parinte: ' . ($isNewParent ? 'NOU (nu exista inca)' : "#$parentId (existent)") . "\n\n";

if (!$isNewParent) {
    $existingStatus = get_post_status($parentId);
    echo "Status curent parinte existent: $existingStatus" . ($existingStatus !== 'publish' ? " -> va fi republicat" : '') . "\n\n";
}

if ($apply) {
    $categoryPath = (string) $rows[0]['Categorie produs'];
    $categoryId = papetarie_storefront_aperta_resolve_category($categoryPath);
    $brandId = papetarie_storefront_aperta_resolve_brand($brand);

    // Aceeasi configurare, indiferent daca parintele e nou-creat sau unul
    // existent (posibil ramas in "trash" dintr-o incercare anterioara a
    // sincronizarii normale - vezi upsert_product(), cazul "0 variatii" -
    // trebuie republicat explicit, altfel intreaga familie consolidata ar
    // ramane invizibila sub un parinte inca la gunoi).
    $parent = new WC_Product_Variable($parentId ?? 0);
    $parent->set_name($name);
    $parent->set_description((string) $rows[0]['Descriere produs']);
    $parent->set_status('publish');
    $parent->set_catalog_visibility('visible');
    if ($categoryId > 0) {
        $parent->set_category_ids([$categoryId]);
    }

    $values = [];
    foreach ($rows as $row) {
        $v = trim((string) $row['Variant']);
        if ($v !== '') {
            $values[$v] = true;
        }
    }
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($attributeName);
    $attribute->set_options(array_keys($values));
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    $parent->set_attributes([$attribute]);

    $parentId = $parent->save();

    if ($brandId > 0) {
        wp_set_object_terms($parentId, [$brandId], 'product_brand');
    }
    update_post_meta($parentId, '_pap_aperta_cod_produs', $targetCluster);

    echo ($isNewParent ? "Parinte creat" : "Parinte existent actualizat/republicat") . ": #$parentId\n\n";
}

$migrated = 0;
$failed = 0;
$firstImageId = null;
$galleryImageIds = [];
$oldSlugsForRedirect = [];

foreach ($rows as $row) {
    $sku = trim((string) $row['Cod unic']);
    $variantValue = trim((string) $row['Variant']);
    $existingVariationId = papetarie_storefront_aperta_find_by_sku_meta($sku);

    if ($existingVariationId === null) {
        echo "  - $sku: nu exista inca pe site, va fi creat de sincronizarea normala - sarit aici.\n";
        continue;
    }

    $existingType = get_post_type($existingVariationId);
    if ($existingType !== 'product_variation') {
        echo "  - $sku: #$existingVariationId nu e o variatie (e $existingType) - neasteptat pentru acest script, sarit, verifica manual.\n";
        $failed++;
        continue;
    }

    $variation = wc_get_product($existingVariationId);
    if (!$variation) {
        echo "  - $sku: #$existingVariationId nu s-a putut incarca ca produs WC - sarit.\n";
        $failed++;
        continue;
    }

    $oldParentId = $variation->get_parent_id();
    if ($oldParentId === $parentId) {
        echo "  - $sku: deja sub parintele tinta (#$parentId) - nimic de facut.\n";
        continue;
    }

    $oldData = [
        'sku' => $variation->get_sku(),
        'regular_price' => $variation->get_regular_price(),
        'stock_quantity' => $variation->get_stock_quantity(),
        'stock_status' => $variation->get_stock_status(),
        'image_id' => $variation->get_image_id(),
    ];

    $oldParentPost = get_post($oldParentId);
    $oldSlug = $oldParentPost ? $oldParentPost->post_name : null;

    printf(
        "  - %s (variatie #%d, parinte vechi #%d \"%s\", slug vechi: %s): pret=%s stoc=%s\n",
        $sku,
        $existingVariationId,
        $oldParentId,
        $oldParentPost ? $oldParentPost->post_title : '?',
        $oldSlug ?? '?',
        $oldData['regular_price'],
        $oldData['stock_quantity']
    );

    if (!$apply) {
        continue;
    }

    // 1. Re-parentare + atribut de varianta pe noul parinte.
    $variation->set_parent_id($parentId);
    $variation->set_attributes([$attributeKey => $variantValue]);
    $variation->save();

    // 2. Verificare.
    $check = wc_get_product($existingVariationId);
    $verified = $check
        && $check->get_parent_id() === $parentId
        && $check->get_sku() === $oldData['sku']
        && (string) $check->get_regular_price() === (string) $oldData['regular_price'];

    if (!$verified) {
        echo "      => EROARE la verificare, incerc sa revin la parintele vechi, NU sterg nimic.\n";
        $variation->set_parent_id($oldParentId);
        $variation->save();
        $failed++;
        continue;
    }

    // 3. Parintele vechi (acum gol) - la gunoi, retinut pentru redirect.
    if ($oldParentId && $oldParentId !== $parentId) {
        wp_trash_post($oldParentId);
        if ($oldSlug) {
            $oldSlugsForRedirect[$oldSlug] = $parentId;
        }
    }

    if ($oldData['image_id']) {
        if ($firstImageId === null) {
            $firstImageId = (int) $oldData['image_id'];
        } else {
            $galleryImageIds[(int) $oldData['image_id']] = true;
        }
    }

    echo "      => Migrat: variatie #$existingVariationId acum sub parintele #$parentId, parintele vechi #$oldParentId la gunoi.\n";
    $migrated++;
}

if ($apply && $firstImageId !== null) {
    $parentProduct = wc_get_product($parentId);
    if ($parentProduct && !$parentProduct->get_image_id()) {
        $parentProduct->set_image_id($firstImageId);
        $parentProduct->set_gallery_image_ids(array_keys($galleryImageIds));
        $parentProduct->save();
        echo "\nPoza parintelui setata din prima variatie migrata: #$firstImageId\n";
    }
}

if ($apply && !empty($oldSlugsForRedirect)) {
    $redirects = get_option('pap_old_product_slug_redirects', []);
    if (!is_array($redirects)) {
        $redirects = [];
    }
    $redirects = array_merge($redirects, $oldSlugsForRedirect);
    update_option('pap_old_product_slug_redirects', $redirects, false);
    echo "\nRedirect-uri 301 inregistrate pentru " . count($oldSlugsForRedirect) . " slug-uri vechi (vezi papetarie_storefront_old_product_redirect() in functions.php).\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
if ($apply) {
    echo "Migrate cu succes: $migrated\n";
    echo "Esuate (nimic modificat pentru ele): $failed\n";
    echo "Produs consolidat: " . get_permalink($parentId) . "\n";
} else {
    echo "Nimic nu a fost modificat (dry-run). Adauga &apply=1 ca sa aplici.\n";
}
