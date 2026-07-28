<?php

declare(strict_types=1);

/**
 * Muta produsele din categoriile-junk duplicate (create de bug-ul reparat in
 * papetarie_storefront_aperta_resolve_category() - vezi commit-ul "Stop
 * auto-creating product categories during Aperta sync") pe categoria-parinte
 * reala, curatoriata. NU sterge categoriile-junk ramase goale - stergerea
 * unei taxonomii e definitiva (nu exista cos de gunoi pentru product_cat),
 * deci ramane o actiune manuala pentru administrator (Produse > Categorii),
 * usor de facut odata ce sunt goale (count=0).
 *
 * ATENTIE: doua categorii cu ACELASI NUME nu inseamna neaparat ca una e
 * junk - "Accesorii" poate fi un nume legitim, refolosit separat sub mai
 * multe sectiuni reale (Articole scolare>Accesorii, Arta>Accesorii etc.),
 * fiecare cu propriul continut real. Semnul de junk e alt - un termen creat
 * de bug are intotdeauna foarte putine produse (de obicei exact 1, produsul
 * care a declansat crearea lui), spre deosebire de categoria reala din
 * acelasi grup, care are un numar de produse mult mai mare.
 *
 * Regula de siguranta: intr-un grup de nume identice, gasim termenul cu CEL
 * MAI MARE numar de produse - il pastram neatins (e clar categoria reala).
 * Din rest, mutam DOAR termenii cu putine produse (implicit <= --max-junk-count,
 * 2 by default). Orice termen din grup care nu e maximul dar are totusi
 * multe produse (peste prag) NU e atins automat - se raporteaza separat
 * pentru verificare manuala, ca sa nu ghicim gresit.
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport). Adauga --apply ca sa chiar
 * muti produsele.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/cleanup-junk-categories.php [--apply] [--max-junk-count=2]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

$apply = false;
$maxJunkCount = 2;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $apply = true;
    } elseif (preg_match('/^--max-junk-count=(\d+)$/', $arg, $m)) {
        $maxJunkCount = (int) $m[1];
    }
}

echo $apply ? "Mod APLICARE (produsele vor fi mutate).\n\n" : "Mod DRY-RUN (nimic nu e modificat).\n\n";
echo "Prag junk: termeni cu cel mult {$maxJunkCount} produse (si care NU sunt maximul grupului) sunt tratati ca junk.\n\n";

function pap_cleanup_product_count(int $termId): int
{
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'tax_query' => [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $termId,
        ]],
    ]);

    return count($ids);
}

$terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

$byName = [];
foreach ($terms as $t) {
    $byName[$t->name][] = $t;
}

$dupGroups = array_filter($byName, static fn (array $list): bool => count($list) > 1);

echo 'Nume de categorie cu duplicate: ' . count($dupGroups) . "\n";
echo str_repeat('=', 70) . "\n\n";

$movedProducts = 0;
$emptiedTerms = [];
$needsManualReview = [];

foreach ($dupGroups as $name => $list) {
    $withCounts = [];
    foreach ($list as $term) {
        $withCounts[] = ['term' => $term, 'count' => pap_cleanup_product_count($term->term_id)];
    }
    usort($withCounts, static fn ($a, $b) => $b['count'] <=> $a['count']);

    $kept = $withCounts[0];
    $rest = array_slice($withCounts, 1);

    echo "\"{$name}\":\n";

    // Daca nici termenul cel mai populat nu se detaseaza clar (cazuri gen
    // "Pixuri cu gel"/"Tastaturi" unde toti membrii au 0-2 produse), nu
    // ghicim care e "cel real" - lasam tot grupul neatins, pentru verificare
    // manuala. Altfel riscam sa mutam produse dintr-o categorie care e la
    // fel de legitima ca cea "pastrata".
    if ($kept['count'] <= 2 * $maxJunkCount) {
        echo "  AMBIGUU - niciun termen din grup nu are clar mai multe produse decat restul, tot grupul necesita verificare manuala:\n";
        foreach ($withCounts as $entry) {
            $t = $entry['term'];
            $p = $t->parent ? get_term($t->parent, 'product_cat') : null;
            $pName = $p instanceof WP_Term ? $p->name : '(top-level)';
            echo "    - #{$t->term_id} slug={$t->slug} parinte=\"{$pName}\" produse={$entry['count']}\n";
            $needsManualReview[] = "#{$t->term_id} \"{$name}\" (slug={$t->slug}, {$entry['count']} produse, parinte=\"{$pName}\")";
        }
        echo "\n";
        continue;
    }

    echo "  PASTRAT (cel mai populat): #{$kept['term']->term_id} slug={$kept['term']->slug} produse={$kept['count']}\n";

    foreach ($rest as $entry) {
        $term = $entry['term'];
        $count = $entry['count'];
        $parent = $term->parent ? get_term($term->parent, 'product_cat') : null;
        $parentName = $parent instanceof WP_Term ? $parent->name : '(top-level)';

        if ($count > $maxJunkCount) {
            echo "  NEATINS (are {$count} produse, peste pragul de junk) - #{$term->term_id} slug={$term->slug} - VERIFICA MANUAL\n";
            $needsManualReview[] = "#{$term->term_id} \"{$name}\" (slug={$term->slug}, {$count} produse, parinte=\"{$parentName}\")";
            continue;
        }

        echo "  JUNK: #{$term->term_id} slug={$term->slug} parinte=\"{$parentName}\" produse={$count}\n";

        $productIds = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'tax_query' => [[
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $term->term_id,
            ]],
        ]);

        foreach ($productIds as $productId) {
            $title = get_the_title($productId);
            echo "    - #{$productId} \"{$title}\" -> muta pe \"{$parentName}\"\n";

            if ($apply && $parent instanceof WP_Term) {
                wp_set_object_terms($productId, [$parent->term_id], 'product_cat', true);
                wp_remove_object_terms($productId, [$term->term_id], 'product_cat');
                $movedProducts++;
            }
        }

        if ($apply) {
            $emptiedTerms[] = "#{$term->term_id} \"{$name}\" (slug={$term->slug})";
        } elseif ($count === 0) {
            $emptiedTerms[] = "#{$term->term_id} \"{$name}\" (slug={$term->slug}) - deja goala";
        }
    }

    echo "\n";
}

echo str_repeat('=', 70) . "\n";
if ($apply) {
    echo "Produse mutate: {$movedProducts}\n";
}
echo 'Categorii-junk goale (de sters manual din Produse > Categorii): ' . count($emptiedTerms) . "\n";
foreach ($emptiedTerms as $line) {
    echo "  - {$line}\n";
}
echo "\nTermeni care necesita verificare manuala (nu au fost atinsi): " . count($needsManualReview) . "\n";
foreach ($needsManualReview as $line) {
    echo "  - {$line}\n";
}
if (!$apply) {
    echo "\nNimic nu a fost modificat (dry-run). Ruleaza cu --apply ca sa muti produsele.\n";
}
