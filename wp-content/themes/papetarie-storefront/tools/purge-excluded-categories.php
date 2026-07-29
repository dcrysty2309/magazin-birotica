<?php

declare(strict_types=1);

/**
 * Trece la gunoi (nu sterge definitiv) toate produsele/variatiile deja
 * importate din categoriile excluse definitiv (Molotow, Universul copiilor -
 * vezi PAP_APERTA_EXCLUDED_TOP_LEVEL_CATEGORIES in includes/aperta-sync.php).
 *
 * Foloseste SKU-ul (Cod unic) din feed ca sa gaseasca produsele/variatiile
 * corespunzatoare in baza de date, nu categoria noastra rezolvata - Molotow
 * nu are propria categorie de top pe site (se mapeaza sub "Arta"), deci
 * cautarea dupa taxonomie ar prinde si produse Arta nelegate de Molotow.
 * Cauta si prin _pap_aperta_cod_produs (pentru un eventual parinte "orfan"
 * ramas fara variatii cu SKU direct).
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport). Adauga --apply ca sa chiar
 * treci produsele la gunoi.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/purge-excluded-categories.php [--apply]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$apply = in_array('--apply', array_slice($argv, 1), true);

echo $apply ? "Mod APLICARE (produsele gasite vor fi trecute la gunoi).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

$path = papetarie_storefront_aperta_feed_path('feed');
$handle = fopen($path, 'r');
if ($handle === false) {
    fwrite(STDERR, "Nu pot deschide feed.csv - descarca-l intai.\n");
    exit(1);
}

if (fread($handle, 3) !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$header = fgetcsv($handle);
if ($header === false) {
    fwrite(STDERR, "Feed gol sau invalid.\n");
    exit(1);
}

$excludedSkus = [];
$excludedCodProdus = [];

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) !== count($header)) {
        continue;
    }

    $assoc = array_combine($header, $row);
    $topLevel = trim((string) explode('>', (string) ($assoc['Categorie produs'] ?? ''))[0]);

    if (!in_array($topLevel, PAP_APERTA_EXCLUDED_TOP_LEVEL_CATEGORIES, true)) {
        continue;
    }

    $sku = trim((string) ($assoc['Cod unic'] ?? ''));
    if ($sku !== '') {
        $excludedSkus[$sku] = true;
    }

    $codProdus = trim((string) ($assoc['Cod produs'] ?? ''));
    if ($codProdus !== '') {
        $excludedCodProdus[$codProdus] = true;
    }
}

fclose($handle);

echo 'SKU-uri (Cod unic) din categoriile excluse: ' . count($excludedSkus) . "\n";
echo 'Coduri de produs din categoriile excluse: ' . count($excludedCodProdus) . "\n\n";

global $wpdb;

$postIdsToTrash = [];

foreach (array_keys($excludedSkus) as $sku) {
    $id = papetarie_storefront_aperta_find_by_sku_meta($sku);
    if ($id !== null) {
        $postIdsToTrash[$id] = true;
        $parentId = (int) get_post_field('post_parent', $id);
        if ($parentId > 0) {
            $postIdsToTrash[$parentId] = true;
        }
    }
}

foreach (array_keys($excludedCodProdus) as $codProdus) {
    $id = papetarie_storefront_aperta_find_parent_by_cod_produs($codProdus);
    if ($id !== null) {
        $postIdsToTrash[$id] = true;
    }
}

echo 'Produse/variatii gasite in baza de date de trecut la gunoi: ' . count($postIdsToTrash) . "\n";
echo str_repeat('=', 70) . "\n\n";

$byStatus = [];
$trashed = 0;

foreach (array_keys($postIdsToTrash) as $id) {
    $post = get_post($id);
    if (!$post) {
        continue;
    }

    $key = $post->post_type . ' / ' . $post->post_status;
    $byStatus[$key] = ($byStatus[$key] ?? 0) + 1;

    echo "  #{$id} [{$post->post_type}, {$post->post_status}] {$post->post_title}\n";

    if ($apply && $post->post_status !== 'trash') {
        wp_trash_post($id);
        $trashed++;
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Pe status/tip:\n";
foreach ($byStatus as $key => $count) {
    echo "  {$key}: {$count}\n";
}

if ($apply) {
    echo "\nTrecute la gunoi acum: {$trashed}\n";
} else {
    echo "\nNimic nu a fost modificat (dry-run). Ruleaza cu --apply ca sa treci la gunoi.\n";
}
