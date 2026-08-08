<?php
/**
 * My Account Dashboard
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$user = wp_get_current_user();
$user_id = (int) $user->ID;
$first_name = trim((string) $user->first_name) ?: wp_strip_all_tags($user->display_name ?: $user->user_email);

$last_orders = function_exists('papetarie_storefront_account_customer_orders')
    ? papetarie_storefront_account_customer_orders($user_id, ['limit' => 1])
    : [];
$last_order = null;
foreach ($last_orders as $candidate) {
    if ($candidate instanceof WC_Order) {
        $last_order = $candidate;
        break;
    }
}

$default_address = function_exists('papetarie_storefront_address_book_default_address')
    ? papetarie_storefront_address_book_default_address($user_id)
    : null;
$has_address = !empty($default_address) && trim((string) ($default_address['address_1'] ?? '')) !== '';
$address_lines = [];
$address_name = '';
if ($has_address) {
    $address_lines = function_exists('papetarie_storefront_address_book_format_lines')
        ? papetarie_storefront_address_book_format_lines($default_address)
        : [];
    $address_name = array_shift($address_lines) ?: '';
    $phone = trim((string) ($default_address['phone'] ?? ''));
    if ($phone !== '') {
        $address_lines[] = $phone;
    }
}

$addresses_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('edit-address') : '';
$orders_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : '';
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
?>

<div class="pap-account-page pap-account-page--dashboard">
  <div class="pap-account-dashboard-head">
    <div class="pap-account-dashboard-head__copy">
      <h1><?php echo esc_html(sprintf(__('Bine ai revenit, %s!', 'papetarie-storefront'), $first_name)); ?></h1>
      <p><?php echo esc_html(date_i18n('l, j F Y')); ?></p>
    </div>
  </div>

  <div class="pap-account-dashboard-grid">
    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Ultima comandă', 'papetarie-storefront'); ?></h2>
        <?php if ($last_order instanceof WC_Order) : ?>
          <a href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('Toate comenzile', 'papetarie-storefront'); ?></a>
        <?php endif; ?>
      </div>
      <div class="pap-account-minicard__body<?php echo $last_order instanceof WC_Order ? '' : ' pap-account-minicard__body--empty'; ?>">
        <?php if ($last_order instanceof WC_Order) : ?>
          <?php
          $items_count = function_exists('papetarie_storefront_account_order_items_count') ? papetarie_storefront_account_order_items_count($last_order) : max(1, (int) $last_order->get_item_count());
          $order_number = function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($last_order) : ('#' . $last_order->get_order_number());
          ?>
          <div class="pap-account-minicard__order-row">
            <strong><?php echo esc_html($order_number); ?></strong>
            <?php echo function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($last_order) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </div>
          <p class="pap-account-minicard__meta">
            <?php echo esc_html(sprintf(
                /* translators: 1: order date, 2: item count */
                _n('%1$s · %2$d produs', '%1$s · %2$d produse', $items_count, 'papetarie-storefront'),
                $last_order->get_date_created() ? wp_date('j M Y', $last_order->get_date_created()->getTimestamp()) : '',
                $items_count
            )); ?>
          </p>
          <div class="pap-account-minicard__footer">
            <strong><?php echo wp_kses_post(function_exists('papetarie_storefront_format_plain_currency_amount') ? papetarie_storefront_format_plain_currency_amount((float) $last_order->get_total()) : $last_order->get_formatted_order_total()); ?></strong>
            <a href="<?php echo esc_url($last_order->get_view_order_url()); ?>"><?php esc_html_e('Vezi detalii', 'papetarie-storefront'); ?> →</a>
          </div>
        <?php else : ?>
          <p class="pap-account-minicard__empty-text"><?php esc_html_e('Nicio comandă încă.', 'papetarie-storefront'); ?></p>
          <a class="pap-account-minicard__empty-action" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Explorează magazinul', 'papetarie-storefront'); ?> →</a>
        <?php endif; ?>
      </div>
    </article>

    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Adresă livrare', 'papetarie-storefront'); ?></h2>
        <a href="<?php echo esc_url($addresses_url); ?>"><?php echo $has_address ? esc_html__('Modifică', 'papetarie-storefront') : esc_html__('Adaugă', 'papetarie-storefront'); ?></a>
      </div>
      <div class="pap-account-minicard__body<?php echo $has_address ? '' : ' pap-account-minicard__body--empty'; ?>">
        <?php if ($has_address) : ?>
          <p class="pap-account-minicard__name"><?php echo esc_html($address_name); ?></p>
          <div class="pap-account-minicard__lines">
            <?php foreach ($address_lines as $line) : ?>
              <p><?php echo esc_html($line); ?></p>
            <?php endforeach; ?>
          </div>
        <?php else : ?>
          <p class="pap-account-minicard__empty-text"><?php esc_html_e('Nicio adresă salvată.', 'papetarie-storefront'); ?></p>
          <a class="pap-account-minicard__empty-action" href="<?php echo esc_url($addresses_url); ?>">+ <?php esc_html_e('Adaugă adresă', 'papetarie-storefront'); ?> →</a>
        <?php endif; ?>
      </div>
    </article>
  </div>
</div>
