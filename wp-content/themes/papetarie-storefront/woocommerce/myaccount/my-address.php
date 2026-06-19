<?php
/**
 * My Addresses
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __('Billing address', 'woocommerce'),
            'shipping' => __('Shipping address', 'woocommerce'),
        ],
        $customer_id
    );
} else {
    $get_addresses = apply_filters(
        'woocommerce_my_account_get_addresses',
        [
            'billing' => __('Billing address', 'woocommerce'),
        ],
        $customer_id
    );
}
?>

<div class="pap-account-page pap-account-page--addresses">
  <header class="pap-account-page-head">
    <p class="pap-account-page-eyebrow"><?php esc_html_e('Cont', 'papetarie-storefront'); ?></p>
    <h1><?php esc_html_e('Adrese', 'papetarie-storefront'); ?></h1>
    <p><?php esc_html_e('Adresele salvate sunt folosite automat la checkout.', 'papetarie-storefront'); ?></p>
  </header>

  <section class="pap-account-panel pap-account-panel--addresses">
    <div class="pap-account-address-grid">
      <?php foreach ($get_addresses as $name => $address_title) : ?>
        <?php $address = wc_get_account_formatted_address($name); ?>
        <article class="pap-account-address-card">
          <div class="pap-account-address-card__head">
            <h2><?php echo esc_html($address_title); ?></h2>
            <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', $name)); ?>" class="pap-account-row-action">
              <?php echo esc_html($address ? sprintf(__('Edit %s', 'woocommerce'), $address_title) : sprintf(__('Add %s', 'woocommerce'), $address_title)); ?>
            </a>
          </div>
          <div class="pap-account-address-card__content">
            <?php if ($address) : ?>
              <?php echo wp_kses_post($address); ?>
            <?php else : ?>
              <p><?php esc_html_e('Nu ai setat încă această adresă.', 'papetarie-storefront'); ?></p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
