<?php

/**
 * Suprascrie tab-urile orizontale implicite WooCommerce (Descriere |
 * Specificatii) cu un accordion vertical (un singur panou deschis o data),
 * cerut explicit de user pe baza unei referinte (2026-08-29). Pastreaza
 * clasa woocommerce-Tabs-panel pe fiecare panou ca stilurile deja
 * existente pentru continutul din interior (tipografie descriere, tabel
 * specificatii) sa ramana valabile neschimbate - dar NU si clasele
 * wc-tabs-wrapper/woocommerce-tabs (pe wrapper) sau panel/wc-tab (pe
 * panouri): scriptul propriu WooCommerce (assets/js/frontend/single-product.js)
 * asculta evenimentul "init" pe ".wc-tabs-wrapper, .woocommerce-tabs" si
 * ascunde necondiționat orice ".wc-tab, .panel" gasit in interior - daca
 * le pastram, JS-ul WC ascunde panoul chiar si cand noi il marcam explicit
 * ca deschis (gasit live 2026-08-29: "Descriere" ramanea invizibil, cu
 * style="display:none" inline pus de acel script, desi era is-open).
 */

defined('ABSPATH') || exit;

$product_tabs = apply_filters('woocommerce_product_tabs', array());

if (!empty($product_tabs)) :
    $first_key = array_key_first($product_tabs);
    ?>
    <div class="pap-product-accordion" data-product-accordion>
        <?php foreach ($product_tabs as $key => $product_tab) :
            $is_active = $key === $first_key;
            $title = apply_filters('woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key);
            ?>
            <div class="pap-accordion-item<?php echo $is_active ? ' is-open' : ''; ?>">
                <button
                    type="button"
                    class="pap-accordion-header"
                    aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>"
                    aria-controls="tab-<?php echo esc_attr($key); ?>"
                    id="tab-title-<?php echo esc_attr($key); ?>"
                    data-accordion-toggle
                >
                    <span><?php echo wp_kses_post($title); ?></span>
                    <span class="pap-accordion-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron-down'); ?></span>
                </button>
                <div
                    class="pap-accordion-panel woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr($key); ?> entry-content"
                    id="tab-<?php echo esc_attr($key); ?>"
                    role="region"
                    aria-labelledby="tab-title-<?php echo esc_attr($key); ?>"
                    <?php echo $is_active ? '' : 'hidden'; ?>
                >
                    <?php
                    if (isset($product_tab['callback'])) {
                        call_user_func($product_tab['callback'], $key, $product_tab);
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php do_action('woocommerce_product_after_tabs'); ?>
    </div>
<?php endif; ?>
