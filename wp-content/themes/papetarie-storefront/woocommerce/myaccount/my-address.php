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
$has_address = $full_name !== ''
    || !empty($lines)
    || trim((string) $customer->get_billing_address_1()) !== ''
    || trim((string) $customer->get_shipping_address_1()) !== '';
$mode = function_exists('papetarie_storefront_address_book_current_mode')
    ? papetarie_storefront_address_book_current_mode()
    : 'add';
$edit_id = function_exists('papetarie_storefront_address_book_current_id')
    ? papetarie_storefront_address_book_current_id()
    : '';
$active_address = function_exists('papetarie_storefront_address_book_empty_entry')
    ? papetarie_storefront_address_book_empty_entry()
    : [];

if ($edit_id !== '' && function_exists('papetarie_storefront_address_book_get')) {
    $stored_address = papetarie_storefront_address_book_get(get_current_user_id(), $edit_id);
    if (!empty($stored_address)) {
        $active_address = $stored_address;
    }
}

if (function_exists('papetarie_storefront_address_book_get_form_state')) {
    $active_address = array_merge($active_address, papetarie_storefront_address_book_get_form_state());
}
?>

<div class="pap-account-page pap-account-page--addresses">
  <?php
  papetarie_storefront_render_account_page_head(
      __('Adresa mea', 'papetarie-storefront'),
      __('Adresa standard WooCommerce este sincronizată pentru billing și shipping și este folosită în checkout.', 'papetarie-storefront')
  );
  ?>

  <section class="pap-account-panel pap-account-panel--addresses">
    <?php if ($has_address) : ?>
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

          <?php foreach ($lines as $line) : ?>
            <p><?php echo esc_html($line); ?></p>
          <?php endforeach; ?>
        </div>
      </article>
    <?php else : ?>
      <article class="pap-account-address-empty-state-card">
        <div class="pap-account-address-empty-state-card__icon" aria-hidden="true">
          <span class="pap-account-address-empty-state-card__icon-circle">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s7-5.4 7-12a7 7 0 1 0-14 0c0 6.6 7 12 7 12z"></path>
              <circle cx="12" cy="10" r="2.8"></circle>
            </svg>
          </span>
        </div>

        <div class="pap-account-address-empty-state-card__body">
          <h3><?php esc_html_e('Nu ai nicio adresă salvată', 'papetarie-storefront'); ?></h3>
          <p><?php esc_html_e('Adaugă o adresă pentru a o putea folosi la checkout.', 'papetarie-storefront'); ?></p>
          <a
            class="pap-account-secondary-button pap-account-address-empty-state-card__action"
            href="<?php echo esc_url(function_exists('papetarie_storefront_address_book_form_url') ? papetarie_storefront_address_book_form_url(['pap_address_action' => 'add', 'pap_address_type' => 'shipping']) : $edit_address_url()); ?>"
            data-address-book-open-modal
            data-address-book-mode="add"
          >
            <?php esc_html_e('Adaugă adresă nouă', 'papetarie-storefront'); ?>
          </a>
        </div>
      </article>
    <?php endif; ?>
  </section>

  <?php if (function_exists('papetarie_storefront_address_book_render_modal_html')) : ?>
    <?php echo papetarie_storefront_address_book_render_modal_html($active_address, $mode, in_array($mode, ['add', 'edit'], true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <?php endif; ?>
</div>
