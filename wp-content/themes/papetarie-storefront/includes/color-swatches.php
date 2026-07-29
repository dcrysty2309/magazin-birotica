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

    $selectName = $args['name'] ? (string) $args['name'] : 'attribute_' . sanitize_title($attribute);
    $selected = (string) ($args['selected'] ?? '');

    ob_start();
    ?>
    <div class="pap-color-swatches" data-select-name="<?php echo esc_attr($selectName); ?>" role="listbox" aria-label="<?php echo esc_attr($attribute); ?>">
        <?php foreach ($options as $option) :
            $isSelected = $selected !== '' && ($selected === $option || $selected === sanitize_title($option));
            $hex = papetarie_storefront_color_name_to_hex($option);
        ?>
            <button
                type="button"
                class="pap-color-swatch<?php echo $isSelected ? ' is-selected' : ''; ?>"
                data-value="<?php echo esc_attr($option); ?>"
                style="--pap-swatch-color: <?php echo esc_attr($hex); ?>;"
                title="<?php echo esc_attr($option); ?>"
                aria-label="<?php echo esc_attr($option); ?>"
                aria-pressed="<?php echo $isSelected ? 'true' : 'false'; ?>"
            ></button>
        <?php endforeach; ?>
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
