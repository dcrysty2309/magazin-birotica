<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<div class="pap-cart-page pap-cart-page--empty" data-cart-page>
  <div class="pap-shell pap-cart-page-shell" data-cart-page-shell>
    <?php echo papetarie_storefront_render_empty_cart_hero_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  </div>

  <div class="pap-cart-loading-overlay" data-cart-loading-overlay hidden aria-hidden="true">
    <div class="pap-cart-loading-overlay__panel" role="status" aria-live="polite">
      <span class="pap-cart-loading-overlay__spinner" aria-hidden="true"></span>
      <span class="pap-cart-loading-overlay__text"><?php esc_html_e('Actualizare coș...', 'papetarie-storefront'); ?></span>
    </div>
  </div>
</div>

<?php
do_action('woocommerce_after_cart');
