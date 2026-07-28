<?php

declare(strict_types=1);

/**
 * Migreaza produse simple deja publicate individual (fiecare culoare = un
 * produs separat) in variatii ale unui produs-parinte comun, pentru
 * familiile detectate de papetarie_storefront_aperta_consolidate_singleton_colors()
 * (nume+culoare) unde membrii sunt deja PUBLICATI ca produse simple - cazul
 * pe care sincronizarea normala il sare in siguranta (vezi commit-ul
 * "Skip (don't crash) when a variation SKU already belongs to a simple
 * product") si care a fost logat in optiunea
 * pap_aperta_pending_variation_migrations.
 *
 * Pentru fiecare membru:
 *   1. Citeste datele produsului simplu vechi (SKU, pret, stoc, poza).
 *   2. Elibereaza SKU-ul de pe produsul vechi (WooCommerce nu permite doua
 *      produse cu acelasi SKU simultan).
 *   3. Creeaza o variatie noua sub parintele comun, cu datele copiate.
 *   4. VERIFICA ca variatia noua are exact aceleasi date.
 *   5. Doar daca verificarea trece: trece produsul vechi la gunoi (nu sterge
 *      definitiv). Daca verificarea eseaza, reface SKU-ul pe produsul vechi
 *      si NU il trece la gunoi - raporteaza eroarea.
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport). Adauga --apply ca sa chiar
 * migrezi. Suporta --offset/--limit (pe familii) pentru rulare in bucati.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/migrate-legacy-simple-to-variation.php [--apply] [--offset=0] [--limit=1]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$apply = false;
$offset = 0;
$limit = null;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $apply = true;
    } elseif (preg_match('/^--offset=(\d+)$/', $arg, $m)) {
        $offset = (int) $m[1];
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    }
}

echo $apply ? "Mod APLICARE (migrare reala).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

$grouped = papetarie_storefront_aperta_read_products_grouped();

$allTargetFamilies = [];
foreach ($grouped as $code => $rows) {
    if (strpos((string) $code, 'merged-') !== 0) {
        continue;
    }

    $needsMigration = false;
    foreach ($rows as $row) {
        $sku = trim((string) $row['Cod unic']);
        $existingId = papetarie_storefront_aperta_find_by_sku_meta($sku);
        if ($existingId !== null && get_post_type($existingId) === 'product' && get_post_status($existingId) === 'publish') {
            $needsMigration = true;
            break;
        }
    }

    if ($needsMigration) {
        $allTargetFamilies[$code] = $rows;
    }
}

$targetFamilies = array_slice($allTargetFamilies, $offset, $limit ?? (count($allTargetFamilies) - $offset), true);

echo 'Familii cu membri publicati individual de migrat: ' . count($allTargetFamilies) . "\n";
echo "Procesez " . count($targetFamilies) . " incepand de la offset {$offset}.\n";
echo str_repeat('=', 70) . "\n\n";

$migrated = 0;
$failed = 0;

foreach ($targetFamilies as $clusterKey => $rows) {
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

    echo "Familie: \"{$name}\" ({$brand})\n";

    $parentId = papetarie_storefront_aperta_find_parent_by_cod_produs($clusterKey);
    $isNewParent = $parentId === null;

    if (!$apply) {
        echo '  Parinte: ' . ($isNewParent ? 'NOU (nu exista inca)' : "#{$parentId} (existent)") . "\n";
    }

    if ($apply && $isNewParent) {
        $categoryPath = (string) $rows[0]['Categorie produs'];
        $categoryId = papetarie_storefront_aperta_resolve_category($categoryPath);
        $brandId = papetarie_storefront_aperta_resolve_brand($brand);

        $parent = new WC_Product_Variable();
        $parent->set_name($name);
        $parent->set_description((string) $rows[0]['Descriere produs']);
        // Spre deosebire de produsele chiar noi (draft implicit, de revizuit
        // manual), membrii acestei familii erau deja publicati individual -
        // migrarea reorganizeaza structura (produse simple -> variatii ale
        // unui parinte comun), nu introduce ceva nou nevazut inca de un
        // admin. Parintele ramane publicat, ca sa nu dispara de pe site
        // produse deja live.
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
        update_post_meta($parentId, '_pap_aperta_cod_produs', $clusterKey);

        echo "  Parinte creat: #{$parentId}\n";
    }

    $firstImageId = null;
    $galleryImageIds = [];

    foreach ($rows as $row) {
        $sku = trim((string) $row['Cod unic']);
        $variantValue = trim((string) $row['Variant']);
        $existingId = papetarie_storefront_aperta_find_by_sku_meta($sku);

        if ($existingId === null) {
            echo "    - {$sku}: nu exista inca, va fi creat de sincronizarea normala - sarit aici.\n";
            continue;
        }

        if (get_post_type($existingId) === 'product_variation') {
            echo "    - {$sku}: deja variatie (#{$existingId}) - nimic de facut.\n";
            continue;
        }

        // $existingId e un produs SIMPLU deja publicat - de migrat.
        $oldProduct = wc_get_product($existingId);
        if (!$oldProduct) {
            echo "    - {$sku}: #{$existingId} nu s-a putut incarca ca produs WC - sarit.\n";
            $failed++;
            continue;
        }

        $oldData = [
            'sku' => $oldProduct->get_sku(),
            'regular_price' => $oldProduct->get_regular_price(),
            'sale_price' => $oldProduct->get_sale_price(),
            'stock_quantity' => $oldProduct->get_stock_quantity(),
            'stock_status' => $oldProduct->get_stock_status(),
            'image_id' => $oldProduct->get_image_id(),
            'gallery_image_ids' => $oldProduct->get_gallery_image_ids(),
        ];

        echo "    - {$sku} (#{$existingId}, publish): pret={$oldData['regular_price']} stoc={$oldData['stock_quantity']} poza=" . ($oldData['image_id'] ?: '—') . "\n";

        if (!$apply) {
            continue;
        }

        // 1. Eliberam SKU-ul de pe produsul vechi (WooCommerce nu permite
        // acelasi SKU pe doua produse simultan).
        $oldProduct->set_sku('');
        $oldProduct->save();
        delete_post_meta($existingId, '_pap_aperta_sku');

        // 2. Creem variatia noua cu datele copiate.
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parentId);
        $variation->set_attributes([$attributeKey => $variantValue]);
        $variation->set_sku($oldData['sku']);
        $variation->set_regular_price((string) $oldData['regular_price']);
        if ($oldData['sale_price'] !== '') {
            $variation->set_sale_price((string) $oldData['sale_price']);
        }
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($oldData['stock_quantity']);
        $variation->set_stock_status($oldData['stock_status']);
        if ($oldData['image_id']) {
            $variation->set_image_id($oldData['image_id']);
        }
        $newVariationId = $variation->save();

        // 3. Verificam.
        $check = wc_get_product($newVariationId);
        $verified = $check
            && $check->get_sku() === $oldData['sku']
            && (string) $check->get_regular_price() === (string) $oldData['regular_price']
            && (int) $check->get_image_id() === (int) $oldData['image_id'];

        if (!$verified) {
            echo "      => EROARE la verificare, refac produsul vechi, NU trec nimic la gunoi.\n";
            $oldProduct->set_sku($oldData['sku']);
            $oldProduct->save();
            update_post_meta($existingId, '_pap_aperta_sku', $oldData['sku']);
            $failed++;
            continue;
        }

        update_post_meta($newVariationId, '_pap_aperta_sku', $oldData['sku']);

        // 4. Verificat OK - trecem produsul vechi la gunoi (recuperabil).
        wp_trash_post($existingId);

        if ($oldData['image_id']) {
            if ($firstImageId === null) {
                $firstImageId = (int) $oldData['image_id'];
            } else {
                $galleryImageIds[(int) $oldData['image_id']] = true;
            }
        }

        echo "      => Migrat: variatie noua #{$newVariationId}, produs vechi #{$existingId} trecut la gunoi.\n";
        $migrated++;
    }

    // Parintele insusi nu are poza proprie (doar variatiile) - fara asta,
    // pagina de produs nu arata nicio poza pana nu alegi o culoare (bug
    // gasit live pe staging 2026-07-28). Acelasi lucru se intampla si la
    // sincronizarea normala (sync_variations()), care seteaza poza
    // parintelui dupa prima variatie procesata.
    if ($apply && $firstImageId !== null) {
        $parentProduct = wc_get_product($parentId);
        if ($parentProduct && !$parentProduct->get_image_id()) {
            $parentProduct->set_image_id($firstImageId);
            $parentProduct->set_gallery_image_ids(array_keys($galleryImageIds));
            $parentProduct->save();
            echo "  Poza parintelui setata din prima variatie: #{$firstImageId}\n";
        }
    }

    echo "\n";
}

echo str_repeat('=', 70) . "\n";
if ($apply) {
    echo "Migrate cu succes: {$migrated}\n";
    echo "Esuate (nimic modificat pentru ele): {$failed}\n";
} else {
    echo "Nimic nu a fost modificat (dry-run). Ruleaza cu --apply ca sa migrezi.\n";
}
