<?php

declare(strict_types=1);

function papetarie_storefront_is_color_attribute(string $attributeName): bool
{
    $normalized = strtolower($attributeName);

    return str_contains($normalized, 'culo');
}

function papetarie_storefront_color_name_to_hex(string $name): string
{
    $normalized = strtolower(trim($name));
    if (function_exists('papetarie_storefront_aperta_strip_diacritics')) {
        $normalized = strtolower(papetarie_storefront_aperta_strip_diacritics($normalized));
    }

    $keywordMap = [
        // romana (cuvinte mai lungi/specifice inaintea celor scurte care sunt
        // prefixul lor - ex. "albastru" inainte de "alb", altfel s-ar prinde
        // gresit ca "alb" oricare varianta care incepe cu "alb...")
        'negru' => '#1a1a1a',
        'albastru' => '#2f6fb3',
        'alb' => '#ffffff',
        'rosu' => '#d32f2f',
        'bleumarin' => '#1a2a4a',
        'bleu' => '#7ec8e3',
        'verde' => '#4caf50',
        'vernil' => '#8fae4a',
        'galben' => '#f5d020',
        'portocaliu' => '#f2811d',
        'mov' => '#7c4dff',
        'roz' => '#f4a6c6',
        'gri' => '#808080',
        'maro' => '#6b4226',
        'bej' => '#e8d4b0',
        'crem' => '#f0e6d2',
        'auriu' => '#d4af37',
        'argintiu' => '#c0c0c0',
        'turcoaz' => '#2fb8ab',
        // engleza
        'bavarian' => '#3d6cb9',
        'gentian' => '#3b5fa0',
        'cobalt' => '#1e4b9e',
        'steel' => '#5b7c99',
        'turquoise' => '#2fb8ab',
        'golden' => '#d4af37',
        'ochre' => '#cc9c3c',
        'ocher' => '#cc9c3c',
        'carmine' => '#960018',
        'russian' => '#5c6e3a',
        'olive' => '#6b7a3a',
        'graphite' => '#4a4a4a',
        'make-up' => '#e0ac8f',
        'makeup' => '#e0ac8f',
        'silver' => '#c0c0c0',
        'white' => '#ffffff',
        'black' => '#1a1a1a',
        'yellow' => '#f5d020',
        'gold' => '#d4af37',
        'orange' => '#f2811d',
        'brown' => '#6b4226',
        'rose' => '#e8b4c0',
        'pink' => '#f4a6c6',
        'violet' => '#7c4dff',
        'purple' => '#7c4dff',
        'blue' => '#2f6fb3',
        'green' => '#4caf50',
        'grey' => '#808080',
        'gray' => '#808080',
        'beige' => '#e8d4b0',
        'red' => '#d32f2f',
        // extindere - nume de vopsele/produse neacoperite de lista de mai sus
        // (cuvinte compuse ca "Pebble Stone" - cel mai specific primul)
        'pebble' => '#ccc5b9',
        'stone' => '#928e85',
        'antracit' => '#383e42',
        'anthracite' => '#383e42',
        'bordo' => '#6b0f1a',
        'bordeaux' => '#6b0f1a',
        'visiniu' => '#6b0f1a',
        'bronz' => '#cd7f32',
        'caramiziu' => '#b7410e',
        'caisa' => '#fbceb1',
        'apricot' => '#fbceb1',
        'piersica' => '#fbceb1',
        'peach' => '#fbceb1',
        'castaniu' => '#7b3f00',
        'cupru' => '#b87333',
        'copper' => '#b87333',
        'corai' => '#ff7f50',
        'coral' => '#ff7f50',
        'fistic' => '#93c572',
        'fumuriu' => '#848482',
        'grafit' => '#4a4a4a',
        'lavanda' => '#b57edc',
        'lavender' => '#b57edc',
        'lila' => '#c8a2c8',
        'liliac' => '#b57edc',
        'lilac' => '#b57edc',
        'menta' => '#aaf0d1',
        'mint' => '#aaf0d1',
        'natur' => '#e3dac9',
        'somon' => '#fa8072',
        'titaniu' => '#878681',
        'titanium' => '#878681',
        'vanilie' => '#f3e5ab',
        'vanille' => '#f3e5ab',
        'vanilla' => '#f3e5ab',
        'amber' => '#ffbf00',
        'aubergine' => '#4a1942',
        'blush' => '#f8c8c8',
        'cappucino' => '#8c6952',
        'cappuccino' => '#8c6952',
        'caramel' => '#c68e3f',
        'cherry' => '#d2042d',
        'citron' => '#e4d00a',
        'cognac' => '#9a463d',
        'daffodil' => '#ffff31',
        'needles' => '#2e4d2e',
        'flamingo' => '#fc8eac',
        'fucsia' => '#cc397b',
        'fuchsia' => '#cc397b',
        'henna' => '#8b4513',
        'hibiscus' => '#b43757',
        'indigo' => '#4b0082',
        'iris' => '#5a4fcf',
        'ivory' => '#fffff0',
        'jade' => '#00a86b',
        'kaki' => '#c3b091',
        'khaki' => '#c3b091',
        'lemon' => '#fff44f',
        'lime' => '#a1c935',
        'loam' => '#8a6642',
        'magenta' => '#ff00ff',
        'oat' => '#d2b48c',
        'ocean' => '#006994',
        'oil' => '#3b3c36',
        'onix' => '#353839',
        'onyx' => '#353839',
        'petrol' => '#005f6a',
        'pomegranate' => '#c02c38',
        'poppy' => '#e35335',
        'reseda' => '#6c7c59',
        'sage' => '#9caf88',
        'sienna' => '#e97451',
        'slate' => '#708090',
        'snow' => '#fffafa',
        'sunflower' => '#ffc512',
        'tangerine' => '#f28500',
        'teal' => '#008080',
        'terracotta' => '#e2725b',
        'tourmaline' => '#86a17d',
        'umber' => '#635147',
        'zinc' => '#71797e',
    ];

    $hex = null;
    foreach ($keywordMap as $keyword => $candidate) {
        // Potrivire pe granita de cuvant, nu substring brut - altfel
        // "galben" ar "contine" gresit "alb" (g-alb-en) si ar deveni alb.
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $normalized) === 1) {
            $hex = $candidate;
            break;
        }
    }

    if ($hex === null) {
        return '#d0d0d0';
    }

    if (str_contains($normalized, 'pastel') || str_contains($normalized, 'light')) {
        $hex = papetarie_storefront_adjust_hex($hex, 0.35);
    }

    if (str_contains($normalized, 'dark')) {
        $hex = papetarie_storefront_adjust_hex($hex, -0.25);
    }

    return $hex;
}

/**
 * Positive $amount lightens toward white, negative darkens toward black.
 */
function papetarie_storefront_adjust_hex(string $hex, float $amount): string
{
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    foreach ([&$r, &$g, &$b] as &$channel) {
        $channel = $amount >= 0
            ? $channel + (255 - $channel) * $amount
            : $channel * (1 + $amount);
        $channel = (int) round(max(0, min(255, $channel)));
    }
    unset($channel);

    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Gaseste ce valoare de atribut are variatia a carei imagine e chiar
 * thumbnail-ul produsului-parinte (poza de pe pagina de categorie/card de
 * produs), ca sa putem pune EXACT culoarea aia prima in lista de swatch-uri -
 * altfel poza principala poate sa nu corespunda cu niciun swatch evidentiat
 * (gasit live 2026-08-29). Facut strict la afisare (nu rescrie
 * _product_attributes) ca sa nu se piarda la urmatoarea resincronizare
 * Aperta, care rescrie oricum ordinea din feed la fiecare rulare.
 */
function papetarie_storefront_thumbnail_matched_color(WC_Product $product, string $attributeKey): ?string
{
    $thumbnailId = (int) $product->get_image_id();
    if ($thumbnailId === 0 || !$product->is_type('variable')) {
        return null;
    }

    foreach ($product->get_children() as $childId) {
        $child = wc_get_product($childId);
        if (!$child instanceof WC_Product_Variation || $child->get_status() !== 'publish') {
            continue;
        }
        if ((int) $child->get_image_id() === $thumbnailId) {
            $value = $child->get_attributes()[$attributeKey] ?? null;

            return $value !== null && $value !== '' ? $value : null;
        }
    }

    return null;
}

function papetarie_storefront_color_swatch_dropdown_html(string $html, array $args): string
{
    $attribute = (string) ($args['attribute'] ?? '');

    if ($attribute === '' || !papetarie_storefront_is_color_attribute($attribute) || taxonomy_exists($attribute)) {
        return $html;
    }

    $product = $args['product'] ?? null;
    $options = $args['options'] ?? [];

    if (empty($options) && $product instanceof WC_Product) {
        $variationAttributes = $product->get_variation_attributes();
        $options = $variationAttributes[$attribute] ?? [];
    }

    if (empty($options)) {
        return $html;
    }

    if ($product instanceof WC_Product) {
        $thumbnailColor = papetarie_storefront_thumbnail_matched_color($product, sanitize_title($attribute));
        if ($thumbnailColor !== null) {
            $matchIndex = array_search($thumbnailColor, array_values($options), true);
            if ($matchIndex !== false && $matchIndex !== 0) {
                $options = array_values($options);
                unset($options[$matchIndex]);
                array_unshift($options, $thumbnailColor);
                $options = array_values($options);
            }
        }
    }

    $selectName = $args['name'] ? (string) $args['name'] : 'attribute_' . sanitize_title($attribute);
    $selected = (string) ($args['selected'] ?? '');

    // Products with many color variants (some run 30-50+) would otherwise
    // grow the swatch box to several wrapped rows on first paint - collapse
    // anything past this count behind a "+N mai multe" toggle instead.
    // If the currently-selected option happens to be one of the collapsed
    // ones (e.g. restored from the URL), start already expanded so the
    // active swatch isn't hidden.
    $visibleLimit = 8;
    $selectedIndex = null;
    foreach (array_values($options) as $index => $option) {
        if ($selected !== '' && ($selected === $option || $selected === sanitize_title($option))) {
            $selectedIndex = $index;
            break;
        }
    }
    $startExpanded = $selectedIndex !== null && $selectedIndex >= $visibleLimit;
    $extraCount = max(0, count($options) - $visibleLimit);

    ob_start();
    ?>
    <div class="pap-color-swatches<?php echo $startExpanded ? ' is-expanded' : ''; ?>" data-select-name="<?php echo esc_attr($selectName); ?>" role="listbox" aria-label="<?php echo esc_attr($attribute); ?>">
        <?php foreach (array_values($options) as $index => $option) :
            $isSelected = $selected !== '' && ($selected === $option || $selected === sanitize_title($option));
            $hex = papetarie_storefront_color_name_to_hex($option);
            $isExtra = $index >= $visibleLimit;
        ?>
            <button
                type="button"
                class="pap-color-swatch<?php echo $isSelected ? ' is-selected' : ''; ?><?php echo $isExtra ? ' pap-color-swatch--extra' : ''; ?>"
                data-value="<?php echo esc_attr($option); ?>"
                style="--pap-swatch-color: <?php echo esc_attr($hex); ?>;"
                title="<?php echo esc_attr($option); ?>"
                aria-label="<?php echo esc_attr($option); ?>"
                aria-pressed="<?php echo $isSelected ? 'true' : 'false'; ?>"
            ></button>
        <?php endforeach; ?>
        <?php if ($extraCount > 0) : ?>
            <button
                type="button"
                class="pap-color-swatch-more"
                aria-label="<?php echo esc_attr(sprintf(_n('Arată încă %d culoare', 'Arată încă %d culori', $extraCount, 'papetarie-storefront'), $extraCount)); ?>"
            >+<?php echo (int) $extraCount; ?></button>
        <?php endif; ?>
    </div>
    <div class="pap-color-select-wrap screen-reader-text">
        <?php echo $html; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'papetarie_storefront_color_swatch_dropdown_html', 10, 2);

function papetarie_storefront_enqueue_color_swatch_script(): void
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    wp_add_inline_script(
        'jquery',
        <<<'JS'
        document.addEventListener('click', function (event) {
            var more = event.target.closest('.pap-color-swatch-more');
            if (more) {
                var moreContainer = more.closest('.pap-color-swatches');
                if (moreContainer) {
                    moreContainer.classList.add('is-expanded');
                }
                return;
            }

            var swatch = event.target.closest('.pap-color-swatch');
            if (!swatch) {
                return;
            }

            var container = swatch.closest('.pap-color-swatches');
            var form = swatch.closest('.variations_form');
            if (!container || !form) {
                return;
            }

            var selectName = container.getAttribute('data-select-name');
            var select = form.querySelector('select[name="' + selectName + '"]');
            if (!select) {
                return;
            }

            select.value = swatch.getAttribute('data-value');
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });

        document.addEventListener('change', function (event) {
            var select = event.target;
            if (!select.matches('.pap-color-select-wrap select')) {
                return;
            }

            var wrap = select.closest('.pap-color-select-wrap');
            var container = wrap ? wrap.previousElementSibling : null;
            if (!container || !container.classList.contains('pap-color-swatches')) {
                return;
            }

            var value = select.value;
            container.querySelectorAll('.pap-color-swatch').forEach(function (button) {
                var isMatch = button.getAttribute('data-value') === value;
                button.classList.toggle('is-selected', isMatch);
                button.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
            });
        });
        JS
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_color_swatch_script', 30);
