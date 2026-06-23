<?php
/**
 * Checkout Form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>
<div class="pap-shell pap-checkout-page-shell">
  <div class="pap-checkout-shell">
    <header class="pap-checkout-hero">
      <h1><?php esc_html_e('Finalizează comanda', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Completează datele de livrare și confirmă comanda în pasul final.', 'papetarie-storefront'); ?></p>
    </header>

    <?php if (function_exists('wc_notice_count') && wc_notice_count()) : ?>
      <div class="pap-checkout-notices woocommerce-notices-wrapper" role="status" aria-live="polite">
        <?php woocommerce_output_all_notices(); ?>
      </div>
    <?php endif; ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout pap-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
      <?php if ($checkout->get_checkout_fields()) : ?>
        <?php do_action('woocommerce_checkout_before_customer_details'); ?>

        <div class="pap-checkout-layout">
          <main id="customer_details" class="pap-checkout-main" aria-label="<?php esc_attr_e('Informații client', 'papetarie-storefront'); ?>">
            <?php wc_get_template('checkout/form-shipping.php', ['checkout' => $checkout]); ?>
            <?php echo papetarie_storefront_get_checkout_shipping_methods_card_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo papetarie_storefront_get_checkout_address_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo papetarie_storefront_get_checkout_payment_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </main>

          <aside class="pap-checkout-sidebar" aria-label="<?php esc_attr_e('Rezumat comandă', 'papetarie-storefront'); ?>">
            <div class="pap-checkout-sidebar__sticky">
              <?php do_action('woocommerce_checkout_before_order_review'); ?>
              <div id="order_review" class="woocommerce-checkout-review-order">
                <?php papetarie_storefront_render_checkout_order_review(); ?>
              </div>
              <?php do_action('woocommerce_checkout_after_order_review'); ?>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php echo papetarie_storefront_get_checkout_address_modal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
