<?php

declare(strict_types=1);

/**
 * Trece la gunoi (nu sterge definitiv) toate produsele/variatiile Aperta deja
 * existente (orice status - publish/draft/pending) care nu au NICIO poza -
 * nici imagine principala, nici galerie, si (pentru produse variabile)
 * nicio variatie cu poza proprie. Regula "fara poza -> nu se importa" e deja
 * aplicata la nivel de sincronizare (papetarie_storefront_aperta_read_products_grouped),
 * asta e partea de curatenie retroactiva, ceruta separat de user 2026-07-28.
 *
 * Un produs variabil e "fara poza" doar daca NICIUNA din variatiile lui (si
 * nici parintele) nu are poza - daca macar o culoare are poza, familia ramane
 * (doar acea varianta fara poza e, oricum, un caz normal de-a lungul feed-ului).
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport). Adauga --apply ca sa chiar
 * treci produsele la gunoi.
 *
 * Ca si celelalte scripturi din tools/, pentru un catalog intreg foloseste
 * --offset/--limit ca sa procesezi in bucati mici (fiecare invocare = proces
 * PHP nou), altfel memoria creste progresiv si poate epuiza memory_limit.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/purge-imageless-products.php [--apply] [--offset=0] [--limit=200]
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

echo $apply ? "Mod APLICARE (produsele gasite vor fi trecute la gunoi).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

global $wpdb;

// Toate produsele-parinte (simple sau variabile) importate de Aperta -
// identificate prin _pap_aperta_sku (simple) sau _pap_aperta_cod_produs
// (parinti de produse variabile).
$allParentIds = $wpdb->get_col(
    "SELECT DISTINCT p.ID
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
     WHERE p.post_type = 'product'
     AND pm.meta_key IN ('_pap_aperta_sku', '_pap_aperta_cod_produs')
     AND p.post_status != 'trash'
     ORDER BY p.ID ASC"
);

$parentIds = array_slice($allParentIds, $offset, $limit ?? (count($allParentIds) - $offset));

echo 'Total produse Aperta (orice status, exceptand deja la gunoi): ' . count($allParentIds) . "\n";
echo "Procesez " . count($parentIds) . " incepand de la offset {$offset}.\n";
echo str_repeat('=', 70) . "\n\n";

$toTrash = [];
$totalMembers = 0;

foreach ($parentIds as $parentId) {
    $product = wc_get_product((int) $parentId);
    if (!$product) {
        continue;
    }

    $hasAnyImage = $product->get_image_id() !== '' || !empty($product->get_gallery_image_ids());
    $variationIds = [];

    if ($product->is_type('variable')) {
        $variationIds = $product->get_children();
        foreach ($variationIds as $variationId) {
            $variation = wc_get_product($variationId);
            if ($variation && ($variation->get_image_id() !== '' || !empty($variation->get_gallery_image_ids()))) {
                $hasAnyImage = true;
            }
        }
    }

    if ($hasAnyImage) {
        continue;
    }

    $name = $product->get_name();
    $memberCount = 1 + count($variationIds);
    $totalMembers += $memberCount;
    $toTrash[] = ['id' => (int) $parentId, 'variation_ids' => $variationIds, 'name' => $name, 'status' => get_post_status($parentId)];

    echo "  #{$parentId} [{$product->get_type()}, " . get_post_status($parentId) . "] \"{$name}\" (" . count($variationIds) . " variatii)\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo 'Familii fara nicio poza gasite: ' . count($toTrash) . "\n";
echo "Total produse+variatii incluse: {$totalMembers}\n";

if ($apply) {
    $trashed = 0;
    foreach ($toTrash as $entry) {
        wp_trash_post($entry['id']);
        $trashed++;
        foreach ($entry['variation_ids'] as $variationId) {
            if (get_post_status($variationId) !== 'trash') {
                wp_trash_post($variationId);
                $trashed++;
            }
        }
    }
    echo "Trecute la gunoi acum: {$trashed}\n";
} else {
    echo "Nimic nu a fost modificat (dry-run). Ruleaza cu --apply ca sa treci la gunoi.\n";
}
