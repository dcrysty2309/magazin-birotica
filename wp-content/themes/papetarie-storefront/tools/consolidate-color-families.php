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
 * Ca si celelalte scripturi din tools/ (backfill-*.php, sync-aperta-feed.php),
 * ruleaza intr-un singur proces PHP - pentru toate familiile odata, memoria
 * creste progresiv (cache-uri interne WP/WC) si poate epuiza memory_limit pe
 * un mediu cu limita mica. Pentru o rulare completa, foloseste --offset/
 * --limit ca sa procesezi in bucati mici, fiecare invocare fiind un proces
 * PHP nou, curat.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/consolidate-color-families.php [--apply] [--offset=0] [--limit=10]
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

echo $apply ? "Mod APLICARE (se vor crea/actualiza produse).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

$grouped = papetarie_storefront_aperta_read_products_grouped();
$beforeCount = count($grouped);

$grouped = papetarie_storefront_aperta_consolidate_by_shared_link($grouped);
$afterCount = count($grouped);

$allNewFamilies = [];
foreach ($grouped as $code => $rows) {
    if (strpos((string) $code, 'link-') === 0) {
        $allNewFamilies[$code] = $rows;
    }
}

$newFamilies = array_slice($allNewFamilies, $offset, $limit ?? (count($allNewFamilies) - $offset), true);

echo "Coduri de produs inainte de consolidare: {$beforeCount}\n";
echo "Coduri de produs dupa consolidare: {$afterCount}\n";
echo "Familii noi gasite (Link produs comun, Cod produs diferit): " . count($allNewFamilies) . "\n";
echo "Procesez " . count($newFamilies) . " incepand de la offset {$offset}.\n";
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
    $oldParentIds = [];

    foreach ($rows as $row) {
        $codUnic = trim((string) $row['Cod unic']);
        $variant = trim((string) $row['Variant']);
        $existingId = papetarie_storefront_aperta_find_by_sku_meta($codUnic);
        $status = 'nou (nu exista inca)';

        if ($existingId !== null) {
            $postType = get_post_type($existingId);
            $postStatus = get_post_status($existingId);
            if ($postType === 'product_variation') {
                $oldParentId = (int) get_post_field('post_parent', $existingId);
                $oldParentStatus = get_post_status($oldParentId);
                $status = "deja variatie existenta (#{$existingId}, {$postStatus}), parinte actual #{$oldParentId} ({$oldParentStatus})";
                if ($oldParentStatus === 'publish') {
                    // Re-parentarea variatiei catre noul produs comun ar
                    // lasa in urma un produs-parinte PUBLICAT, live, fara
                    // nicio variatie - o "gaura" vizibila pe site. Nu aplicam
                    // automat aici, e nevoie de o decizie separata (fie
                    // arhivam parintele vechi, fie il redirectionam).
                    $status .= ' - PARINTE VECHI PUBLICAT, necesita curatare separata';
                    $hasPublished = true;
                } else {
                    $oldParentIds[$oldParentId] = true;
                }
            } else {
                $status = "PRODUS SIMPLU DEJA EXISTENT (#{$existingId}, {$postType}, {$postStatus}) - necesita migrare manuala";
                $hasPublished = $hasPublished || $postStatus === 'publish';
            }
        }

        echo "    - {$codUnic} ({$variant}): {$status}\n";
    }

    if ($hasPublished) {
        $alreadyPublishedCount++;
        echo "  ATENTIE: cel putin un membru e legat de ceva deja PUBLICAT (produs simplu sau parinte de variatie) - SARIT, necesita decizie separata.\n";
    }

    if ($apply) {
        if ($hasPublished) {
            echo "  => Neaplicat (are ceva deja publicat legat de familia asta - vezi mai sus).\n";
        } else {
            $result = papetarie_storefront_aperta_upsert_product($rows);
            echo '  => Aplicat: produs-parinte #' . $result['product_id'] . ' (' . papetarie_storefront_aperta_describe_upsert($result) . ")\n";
            $applied++;

            // Variatiile mutate mai sus (set_parent_id + save(), in
            // sync_variations) isi lasa vechiul produs-parinte (draft,
            // dinainte de consolidare) fara nicio variatie - il trecem la
            // gunoi (nu il stergem definitiv) daca a ramas gol. Verificat
            // deja mai sus ca niciunul dintre acesti parinti vechi nu era
            // publicat.
            foreach (array_keys($oldParentIds) as $oldParentId) {
                if ($oldParentId === (int) $result['product_id']) {
                    continue;
                }
                $remainingChildren = get_posts([
                    'post_type' => 'product_variation',
                    'post_status' => 'any',
                    'post_parent' => $oldParentId,
                    'fields' => 'ids',
                    'posts_per_page' => 1,
                ]);
                if (empty($remainingChildren)) {
                    wp_trash_post($oldParentId);
                    echo "  => Produs-parinte vechi, ramas gol, trecut la gunoi: #{$oldParentId}\n";
                }
            }
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
