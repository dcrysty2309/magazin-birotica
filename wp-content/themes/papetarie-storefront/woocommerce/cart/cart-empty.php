<?php

defined('ABSPATH') || exit;

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

do_action('woocommerce_before_cart');
?>

<div class="pap-cart-page pap-cart-page--empty" data-cart-page>
  <div class="pap-cart-page-shell" data-cart-page-shell>
    <div class="pap-cart-empty">
      <h1 class="pap-cart-title"><?php esc_html_e('Coșul tău', 'papetarie-storefront'); ?></h1>
      <div class="pap-cart-empty-card">
        <p class="pap-cart-empty-title"><?php esc_html_e('Coșul este gol.', 'papetarie-storefront'); ?></p>
        <p class="pap-cart-empty-copy"><?php esc_html_e('Adaugă produse pentru a începe comanda.', 'papetarie-storefront'); ?></p>
        <a class="pap-cart-continue" href="<?php echo esc_url($shop_url); ?>">
          <span class="pap-cart-continue-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
          <span><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></span>
        </a>
      </div>
    </div>
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
