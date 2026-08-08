<?php
/**
 * View Order
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$status_badge = function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($order) : '';
$order_number = function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($order) : ('#' . $order->get_order_number());
$created = $order->get_date_created();
$created_label = $created ? wp_date('j F Y', $created->getTimestamp()) : __('Nespecificat', 'papetarie-storefront');
$payment_label = function_exists('papetarie_storefront_account_order_payment_suffix') ? papetarie_storefront_account_order_payment_suffix($order) : trim((string) $order->get_payment_method_title());
$items = function_exists('papetarie_storefront_account_order_item_rows') ? papetarie_storefront_account_order_item_rows($order) : [];
$totals = function_exists('papetarie_storefront_account_order_totals_rows') ? papetarie_storefront_account_order_totals_rows($order) : [];

$shipping_name = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
if ($shipping_name === '') {
    $shipping_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
}
$shipping_state_code = strtoupper(sanitize_key((string) $order->get_shipping_state()));
$shipping_counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
$shipping_state_label = $shipping_state_code !== '' && isset($shipping_counties[$shipping_state_code]) ? $shipping_counties[$shipping_state_code] : $order->get_shipping_state();
$shipping_lines = array_values(array_filter([
    trim(implode(', ', array_filter([$order->get_shipping_address_1(), $order->get_shipping_address_2()]))),
    trim(implode(', ', array_filter([$order->get_shipping_city(), $shipping_state_label]))),
    $order->get_billing_phone(),
]));
?>

<div class="pap-account-page pap-account-page--view-order">
  <article class="pap-account-simplecard">
    <div class="pap-account-simplecard__head pap-account-simplecard__head--order">
      <div class="pap-account-simplecard__head-title">
        <h2><?php echo esc_html(sprintf(__('Comanda %s', 'papetarie-storefront'), $order_number)); ?></h2>
        <?php echo $status_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
      <span class="pap-account-simplecard__head-date"><?php echo esc_html($created_label); ?></span>
    </div>

    <div class="pap-account-order-lines">
      <div class="pap-account-order-lines__head" aria-hidden="true">
        <span><?php esc_html_e('Produs', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Cant.', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Preț unitar', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
      </div>

      <?php foreach ($items as $item) : ?>
        <?php $item_url = (string) ($item['url'] ?? ''); ?>
        <div class="pap-account-order-lines__row">
          <div class="pap-account-order-line__product">
            <?php if ($item_url !== '') : ?><a href="<?php echo esc_url($item_url); ?>" class="pap-account-order-line__thumb-link"><?php endif; ?>
            <?php if (!empty($item['image'])) : ?>
              <?php echo wp_get_attachment_image((int) $item['image'], 'thumbnail', false, ['class' => 'pap-account-order-line__thumb', 'loading' => 'lazy']); ?>
            <?php else : ?>
              <span class="pap-account-order-line__thumb pap-account-order-line__thumb--placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3.5" y="3.5" width="17" height="17" rx="2"></rect><path d="m3.5 15.5 5-5 4 4 3-3 4.5 4.5"></path><circle cx="8.5" cy="8.5" r="1.5"></circle></svg>
              </span>
            <?php endif; ?>
            <?php if ($item_url !== '') : ?></a><?php endif; ?>
            <div class="pap-account-order-line__copy">
              <?php if ($item_url !== '') : ?>
                <a href="<?php echo esc_url($item_url); ?>"><strong><?php echo esc_html($item['name']); ?></strong></a>
              <?php else : ?>
                <strong><?php echo esc_html($item['name']); ?></strong>
              <?php endif; ?>
              <?php if (!empty($item['sku'])) : ?>
                <span><?php echo esc_html(sprintf(__('SKU: %s', 'papetarie-storefront'), $item['sku'])); ?></span>
              <?php endif; ?>
              <?php if (empty($item['available'])) : ?>
                <span class="pap-account-order-line__unavailable"><?php esc_html_e('Indisponibil momentan', 'papetarie-storefront'); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <span class="pap-account-order-line__qty"><?php echo esc_html($item['quantity'] ?? '1'); ?></span>
          <span class="pap-account-order-line__unit"><?php echo esc_html($item['unit_price'] ?? ''); ?></span>
          <span class="pap-account-order-line__total"><?php echo esc_html($item['total'] ?? ''); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </article>

  <div class="pap-account-order-detail-grid">
    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Sumar plată', 'papetarie-storefront'); ?></h2>
      </div>
      <div class="pap-account-minicard__body pap-account-minicard__body--totals">
        <?php foreach ($totals as $total_row) : ?>
          <div class="pap-account-totals-row<?php echo !empty($total_row['label']) && $total_row['label'] === __('Total plătit', 'papetarie-storefront') ? ' is-total' : ''; ?>">
            <span><?php echo esc_html($total_row['label'] ?? ''); ?></span>
            <strong><?php echo esc_html($total_row['value'] ?? ''); ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Adresă livrare', 'papetarie-storefront'); ?></h2>
      </div>
      <div class="pap-account-minicard__body">
        <?php if ($shipping_name !== '') : ?>
          <p class="pap-account-minicard__name"><?php echo esc_html($shipping_name); ?></p>
        <?php endif; ?>
        <div class="pap-account-minicard__lines">
          <?php foreach ($shipping_lines as $line) : ?>
            <p><?php echo esc_html($line); ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    </article>

    <article class="pap-account-minicard pap-account-order-detail-grid__payment">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Metodă de plată', 'papetarie-storefront'); ?></h2>
      </div>
      <div class="pap-account-minicard__body">
        <p><?php echo esc_html($payment_label ?: __('Plată online', 'papetarie-storefront')); ?></p>
      </div>
    </article>
  </div>
</div>
