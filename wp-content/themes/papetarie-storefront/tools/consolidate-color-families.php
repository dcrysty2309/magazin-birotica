<?php

declare(strict_types=1);

/**
 * Consolidare produse-culoare intr-un singur produs variabil, folosind
 * "Link produs" (pagina de pe site-ul Aperta) ca semnal de familie - vezi
 * papetarie_storefront_aperta_consolidate_by_shared_link() in
 * includes/aperta-sync.php.
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport, nicio scriere in baza de
 * date). Adauga --apply ca sa chiar creezi produsele-parinte + variatiile.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/consolidate-color-families.php [--apply]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$apply = in_array('--apply', array_slice($argv, 1), true);

echo $apply ? "Mod APLICARE (se vor crea/actualiza produse).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

$grouped = papetarie_storefront_aperta_read_products_grouped();
$beforeCount = count($grouped);

$grouped = papetarie_storefront_aperta_consolidate_by_shared_link($grouped);
$afterCount = count($grouped);

$newFamilies = [];
foreach ($grouped as $code => $rows) {
    if (strpos((string) $code, 'link-') === 0) {
        $newFamilies[$code] = $rows;
    }
}

echo "Coduri de produs inainte de consolidare: {$beforeCount}\n";
echo "Coduri de produs dupa consolidare: {$afterCount}\n";
echo "Familii noi gasite (Link produs comun, Cod produs diferit): " . count($newFamilies) . "\n";
echo str_repeat('=', 70) . "\n\n";

$totalMembers = 0;
$alreadyPublishedCount = 0;
$applied = 0;

foreach ($newFamilies as $clusterKey => $rows) {
    $name = trim((string) $rows[0]['Denumire produs']);
    $brand = trim((string) $rows[0]['Brand produs']);
    $tipVariant = trim((string) $rows[0]['Tip variant']);
    $totalMembers += count($rows);

    echo "Familie: \"{$name}\" ({$brand})\n";
    echo "  Atribut variatie: {$tipVariant}\n";
    echo "  Membri (" . count($rows) . "):\n";

    $hasPublished = false;

    foreach ($rows as $row) {
        $codUnic = trim((string) $row['Cod unic']);
        $variant = trim((string) $row['Variant']);
        $existingId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);
        $status = 'nou (nu exista inca)';

        if ($existingId !== null) {
            $postType = get_post_type($existingId);
            $postStatus = get_post_status($existingId);
            if ($postType === 'product_variation') {
                $status = "deja variatie existenta (#{$existingId}, {$postStatus})";
            } else {
                $status = "PRODUS SIMPLU DEJA EXISTENT (#{$existingId}, {$postType}, {$postStatus}) - necesita migrare manuala";
                $hasPublished = $hasPublished || $postStatus === 'publish';
            }
        }

        echo "    - {$codUnic} ({$variant}): {$status}\n";
    }

    if ($hasPublished) {
        $alreadyPublishedCount++;
        echo "  ATENTIE: cel putin un membru e deja PUBLICAT ca produs simplu - SARIT, necesita decizie separata.\n";
    }

    if ($apply) {
        if ($hasPublished) {
            echo "  => Neaplicat (are membru deja publicat individual - vezi mai sus).\n";
        } else {
            $result = papetarie_storefront_aperta_upsert_product($rows);
            echo '  => Aplicat: produs-parinte #' . $result['product_id'] . ' (' . papetarie_storefront_aperta_describe_upsert($result) . ")\n";
            $applied++;
        }
    }

    echo "\n";
}

echo str_repeat('=', 70) . "\n";
echo "Total familii noi: " . count($newFamilies) . "\n";
echo "Total produse incluse: {$totalMembers}\n";
echo "Familii cu cel putin un membru deja PUBLICAT individual: {$alreadyPublishedCount}\n";
if ($apply) {
    echo "Familii aplicate (produs-parinte creat/actualizat): {$applied}\n";
} else {
    echo "Nimic nu a fost scris in baza de date (dry-run). Ruleaza cu --apply ca sa aplici.\n";
}
