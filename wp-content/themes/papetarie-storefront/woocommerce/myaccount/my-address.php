<?php
/**
 * My Address
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in() || !function_exists('WC') || !WC() || !(WC()->customer instanceof WC_Customer)) {
    echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea adresa ta.', 'papetarie-storefront') . '</p>';
    return;
}

$customer = WC()->customer;
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$edit_address_url = static function () use ($account_url): string {
    if (function_exists('wc_get_endpoint_url')) {
        return wc_get_endpoint_url('edit-address', 'billing', $account_url);
    }

    return add_query_arg(['edit-address' => 'billing'], $account_url);
};

$lines = function_exists('papetarie_storefront_checkout_standard_account_address_lines')
    ? papetarie_storefront_checkout_standard_account_address_lines()
    : [];
$full_name = array_shift($lines) ?: '';
?>

<div class="pap-account-page pap-account-page--addresses">
  <?php papetarie_storefront_render_account_page_head(
      __('Adresa mea', 'papetarie-storefront'),
      __('Adresa standard WooCommerce este sincronizată pentru billing și shipping și este folosită în checkout.', 'papetarie-storefront')
  ); ?>

  <section class="pap-account-panel pap-account-panel--addresses">
    <article class="pap-account-address-card pap-account-address-card--single">
      <div class="pap-account-address-card__head">
        <div class="pap-account-address-card__head-copy">
          <h3><?php esc_html_e('Adresa mea', 'papetarie-storefront'); ?></h3>
        </div>
        <a class="pap-account-row-action" href="<?php echo esc_url($edit_address_url()); ?>">
          <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
        </a>
      </div>

      <div class="pap-account-address-card__content">
        <?php if ($full_name !== '') : ?>
          <p><?php echo esc_html($full_name); ?></p>
        <?php endif; ?>

        <?php if (!empty($lines)) : ?>
          <?php foreach ($lines as $line) : ?>
            <p><?php echo esc_html($line); ?></p>
          <?php endforeach; ?>
        <?php elseif (trim((string) $customer->get_billing_address_1()) === '' && trim((string) $customer->get_shipping_address_1()) === '') : ?>
          <p><?php esc_html_e('Nu există încă o adresă completată.', 'papetarie-storefront'); ?></p>
        <?php endif; ?>
      </div>
    </article>
  </section>
</div>
