<?php

defined('ABSPATH') || exit;

$cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

do_action('woocommerce_before_cart');
?>

<div class="pap-cart-page" data-cart-page>
  <div class="pap-shell pap-cart-page-shell" data-cart-page-shell>
    <div class="pap-cart-layout">
      <section class="pap-cart-main" aria-label="<?php esc_attr_e('Produse din coș', 'papetarie-storefront'); ?>">
        <h1 class="pap-cart-title"><?php esc_html_e('Coșul tău', 'papetarie-storefront'); ?></h1>

        <?php $cart_warning_state = function_exists('papetarie_storefront_cart_warning_state') ? papetarie_storefront_cart_warning_state() : ['type' => 'none', 'message' => '', 'visible' => false]; ?>

        <div class="pap-cart-alert-area">
          <div class="pap-cart-alert" data-cart-alert data-cart-alert-state="<?php echo esc_attr($cart_warning_state['type']); ?>"<?php echo !empty($cart_warning_state['visible']) ? '' : ' hidden'; ?> role="status" aria-live="polite">
            <svg class="pap-cart-alert__icon" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 1.66699L18.3337 17.5003H1.66699L10 1.66699Z" fill="#F59E0B"/>
              <path d="M10 6.66699V11.667" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M10 14.167H10.0083" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
            <span class="pap-cart-alert__text" data-cart-alert-text><?php echo wp_kses_post((string) $cart_warning_state['message']); ?></span>
          </div>
        </div>
      </section>

      <div class="pap-cart-columns">
        <div class="pap-cart-left" data-cart-left-column>
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

          <div class="pap-cart-actions-row">
            <a class="pap-cart-continue" href="<?php echo esc_url($shop_url); ?>">
              <span class="pap-cart-continue-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
              <span><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></span>
            </a>
            <button type="submit" class="pap-cart-update-submit" data-cart-update-submit form="pap-cart-form" name="update_cart" value="1">
              <?php esc_html_e('Actualizează coșul', 'papetarie-storefront'); ?>
            </button>
          </div>
        </div>

        <aside class="pap-cart-right" aria-label="<?php esc_attr_e('Sumar comandă', 'papetarie-storefront'); ?>">
          <div class="pap-cart-summary">
            <?php echo papetarie_storefront_render_cart_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </div>
        </aside>
      </div>

      <?php echo papetarie_storefront_render_cart_recommendations_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
