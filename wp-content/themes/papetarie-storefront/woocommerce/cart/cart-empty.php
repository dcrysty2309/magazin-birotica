<?php

defined('ABSPATH') || exit;

$shop_url = home_url('/');

do_action('woocommerce_before_cart');
?>

<div class="pap-cart-page pap-cart-page--empty" data-cart-page>
  <div class="pap-shell pap-cart-page-shell" data-cart-page-shell>
    <div class="pap-cart-layout pap-cart-layout--empty">
      <section class="pap-cart-main pap-cart-main--empty" aria-label="<?php esc_attr_e('Coș gol', 'papetarie-storefront'); ?>">
        <h1 class="pap-cart-title"><?php esc_html_e('Coșul tău', 'papetarie-storefront'); ?></h1>

        <div class="pap-cart-empty-stack">
          <div class="pap-cart-empty-hero">
            <div class="pap-cart-empty-hero-visual" aria-hidden="true">
              <img class="pap-cart-empty-hero-image" src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/cart-empty-hero.png'); ?>" alt="" />
            </div>
            <div class="pap-cart-empty-hero-copy">
              <h3 class="pap-cart-empty-hero-title"><?php esc_html_e('Nu ai încă produse în coș', 'papetarie-storefront'); ?></h3>
              <p class="pap-cart-empty-hero-text"><?php esc_html_e('Se pare că nu ai adăugat încă niciun produs în coș. Descoperă gama noastră variată de produse de papetărie și birotică.', 'papetarie-storefront'); ?></p>
              <a class="button pap-cart-empty-continue" href="<?php echo esc_url($shop_url); ?>">
                <span><?php esc_html_e('Înapoi la magazin', 'papetarie-storefront'); ?></span>
              </a>
            </div>
          </div>

          <?php echo papetarie_storefront_render_cart_recommendations_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      </section>
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
