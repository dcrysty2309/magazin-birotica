<?php
/**
 * Checkout payment section.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!wp_doing_ajax()) {
    do_action('woocommerce_review_order_before_payment');
}
$step_state = function_exists('papetarie_storefront_checkout_step_state') ? papetarie_storefront_checkout_step_state('payment') : 'active';
?>
<section id="payment" class="woocommerce-checkout-payment pap-checkout-card pap-checkout-card--payment pap-checkout-step pap-checkout-step--payment is-step-<?php echo esc_attr($step_state); ?>" data-pap-checkout-section="payment" data-pap-checkout-step="payment" data-pap-step-state="<?php echo esc_attr($step_state); ?>" aria-disabled="<?php echo esc_attr($step_state === 'disabled' ? 'true' : 'false'); ?>">
  <div class="pap-checkout-card__head">
    <h2><?php esc_html_e('Alege metoda de plată', 'papetarie-storefront'); ?></h2>
    <?php if ($step_state === 'disabled') : ?>
      <p class="pap-checkout-card__intro"><?php esc_html_e('Finalizează pașii anteriori pentru a alege metoda de plată.', 'papetarie-storefront'); ?></p>
    <?php else : ?>
      <p class="pap-checkout-card__intro"><?php esc_html_e('Alege metoda potrivită și confirmă comanda.', 'papetarie-storefront'); ?></p>
    <?php endif; ?>
  </div>

  <div class="pap-checkout-step__body"<?php echo $step_state === 'disabled' ? ' hidden aria-hidden="true"' : ' aria-hidden="false"'; ?>>
  <?php if (WC()->cart && WC()->cart->needs_payment()) : ?>
    <ul class="wc_payment_methods payment_methods methods pap-checkout-payment-methods">
      <?php if (!empty($available_gateways)) : ?>
        <?php foreach ($available_gateways as $gateway) : ?>
          <?php wc_get_template('checkout/payment-method.php', ['gateway' => $gateway]); ?>
        <?php endforeach; ?>
      <?php else : ?>
        <li>
          <?php
          wc_print_notice(
              apply_filters(
                  'woocommerce_no_available_payment_methods_message',
                  WC()->customer->get_billing_country()
                      ? esc_html__('Nu este disponibilă nicio metodă de plată. Te rog să ne contactezi pentru ajutor la plasarea comenzii.', 'woocommerce')
                      : esc_html__('Completează datele de livrare pentru a vedea metodele de plată disponibile.', 'woocommerce')
              ),
              'notice'
          );
          ?>
        </li>
      <?php endif; ?>
    </ul>
  <?php endif; ?>
  </div>
</section>
<?php
if (!wp_doing_ajax()) {
    do_action('woocommerce_review_order_after_payment');
}
