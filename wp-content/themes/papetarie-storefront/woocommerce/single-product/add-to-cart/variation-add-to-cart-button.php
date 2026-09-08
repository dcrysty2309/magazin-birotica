<?php

/**
 * Buton "adauga in cos" pentru produse variabile - suprascrie template-ul
 * implicit WooCommerce (input numeric simplu + buton generic) cu acelasi
 * stepper +/- si buton custom folosit deja la produsele simple (vezi
 * woocommerce/single-product.php) - pana acum doar produsele simple aveau
 * design-ul asta, produsele cu variante (majoritatea catalogului) cadeau pe
 * markup-ul brut WooCommerce.
 *
 * Structura (wrapper .variations_button, clasele single_add_to_cart_button
 * si variation_id) trebuie pastrata neschimbata - JS-ul WooCommerce
 * (add-to-cart-variation.js) le foloseste ca sa activeze/dezactiveze
 * butonul si sa completeze variation_id la alegerea unei variante.
 */

defined('ABSPATH') || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button pap-product-actions-row">
  <?php do_action('woocommerce_before_add_to_cart_button'); ?>

  <div class="pap-product-qty-stack" data-qty-stack>
    <div class="pap-product-qty-stepper" data-qty-stepper>
      <button type="button" class="pap-product-qty-btn" data-qty-decrease aria-label="<?php esc_attr_e('Scade cantitatea', 'papetarie-storefront'); ?>">
        <?php echo papetarie_storefront_icon('minus'); ?>
      </button>
      <input
        type="number"
        name="quantity"
        class="qty pap-product-qty-input"
        value="<?php echo esc_attr((string) $product->get_min_purchase_quantity()); ?>"
        min="<?php echo esc_attr((string) $product->get_min_purchase_quantity()); ?>"
        <?php if ($product->get_max_purchase_quantity() > 0) : ?>max="<?php echo esc_attr((string) $product->get_max_purchase_quantity()); ?>"<?php endif; ?>
        inputmode="numeric"
        aria-label="<?php esc_attr_e('Cantitate', 'papetarie-storefront'); ?>"
      >
      <button type="button" class="pap-product-qty-btn" data-qty-increase aria-label="<?php esc_attr_e('Crește cantitatea', 'papetarie-storefront'); ?>">
        <?php echo papetarie_storefront_icon('plus'); ?>
      </button>
    </div>
    <div class="pap-product-qty-tooltip" data-qty-tooltip hidden aria-hidden="true"></div>
  </div>

  <button type="submit" class="pap-product-add-to-cart single_add_to_cart_button">
    <span aria-hidden="true"><?php echo papetarie_storefront_icon('bag'); ?></span>
    <span><?php esc_html_e('Adaugă în coș', 'papetarie-storefront'); ?></span>
  </button>

  <?php do_action('woocommerce_after_add_to_cart_button'); ?>

  <input type="hidden" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>" />
  <input type="hidden" name="product_id" value="<?php echo absint($product->get_id()); ?>" />
  <input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
