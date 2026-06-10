<?php

defined('ABSPATH') || exit;

$cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

do_action('woocommerce_before_cart');
?>

<div class="pap-cart-page" data-cart-page>
  <div class="pap-cart-page-shell" data-cart-page-shell>
    <div class="pap-cart-layout">
      <section class="pap-cart-main" aria-label="<?php esc_attr_e('Produse din coș', 'papetarie-storefront'); ?>">
        <h1 class="pap-cart-title"><?php esc_html_e('Coșul tău', 'papetarie-storefront'); ?></h1>

        <div class="pap-cart-headings" aria-hidden="true">
          <span><?php esc_html_e('PRODUS', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('TOTAL', 'papetarie-storefront'); ?></span>
        </div>

        <form id="pap-cart-form" class="woocommerce-cart-form pap-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
          <?php do_action('woocommerce_before_cart_contents'); ?>

          <div class="pap-cart-items">
            <?php foreach ($cart as $cart_item_key => $cart_item) : ?>
              <?php echo papetarie_storefront_render_cart_item_row_html((string) $cart_item_key, (array) $cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endforeach; ?>
          </div>

          <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
          <input type="hidden" name="update_cart" value="1">

          <?php do_action('woocommerce_after_cart_contents'); ?>
        </form>

        <a class="pap-cart-continue" href="<?php echo esc_url($shop_url); ?>">
          <span class="pap-cart-continue-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
          <span><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></span>
        </a>
      </section>

      <aside class="pap-cart-summary" aria-label="<?php esc_attr_e('Sumar comandă', 'papetarie-storefront'); ?>">
        <?php echo papetarie_storefront_render_cart_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </aside>
    </div>
  </div>

  <div class="pap-cart-loading-overlay" data-cart-loading-overlay hidden aria-hidden="true">
    <div class="pap-cart-loading-overlay__panel" role="status" aria-live="polite">
      <span class="pap-cart-loading-overlay__spinner" aria-hidden="true"></span>
      <span class="pap-cart-loading-overlay__text"><?php esc_html_e('Coșul se actualizează...', 'papetarie-storefront'); ?></span>
    </div>
  </div>
</div>

<?php
do_action('woocommerce_after_cart');
