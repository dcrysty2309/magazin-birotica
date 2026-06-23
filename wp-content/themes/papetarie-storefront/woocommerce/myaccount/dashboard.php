<?php
/**
 * My Account Dashboard
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$user = wp_get_current_user();
$user_id = (int) $user->ID;
$recent_orders = function_exists('wc_get_orders') ? wc_get_orders([
    'customer_id' => $user_id,
    'limit' => 4,
    'orderby' => 'date',
    'order' => 'DESC',
]) : [];
$stats = function_exists('papetarie_storefront_account_dashboard_stats') ? papetarie_storefront_account_dashboard_stats($user_id) : [];
$last_order = null;

foreach ($recent_orders as $recent_order) {
    if ($recent_order instanceof WC_Order) {
        $last_order = $recent_order;
        break;
    }
}
?>

<div class="pap-account-page pap-account-page--dashboard">
  <?php papetarie_storefront_render_account_page_head(
      sprintf(__('Bun venit, %s!', 'papetarie-storefront'), wp_strip_all_tags($user->display_name ?: $user->user_email)),
      __('De aici poți gestiona comenzile, adresele și produsele favorite.', 'papetarie-storefront')
  ); ?>

  <section class="pap-account-stat-grid" aria-label="<?php esc_attr_e('Statistici cont', 'papetarie-storefront'); ?>">
    <?php foreach ($stats as $stat) : ?>
      <article class="pap-account-stat-card pap-account-stat-card--<?php echo esc_attr($stat['tone'] ?? 'blue'); ?>">
        <div class="pap-account-stat-card__icon" aria-hidden="true">
          <?php echo function_exists('papetarie_storefront_render_account_icon') ? papetarie_storefront_render_account_icon((string) ($stat['icon'] ?? 'cart')) : ''; ?>
        </div>
        <div class="pap-account-stat-card__copy">
          <span><?php echo esc_html($stat['label'] ?? ''); ?></span>
          <strong><?php echo wp_kses_post($stat['value'] ?? ''); ?></strong>
          <?php if (!empty($stat['link']) && !empty($stat['link_label'])) : ?>
            <a href="<?php echo esc_url($stat['link']); ?>"><?php echo esc_html($stat['link_label']); ?> <span aria-hidden="true">→</span></a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="pap-account-panel pap-account-panel--orders">
    <div class="pap-account-panel-head">
      <div class="pap-account-panel-head__copy">
        <h2><?php esc_html_e('Ultimele comenzi', 'papetarie-storefront'); ?></h2>
      </div>
      <a class="pap-account-panel-head__link" href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
        <?php esc_html_e('Vezi toate comenzile', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span>
      </a>
    </div>

    <?php if (empty($recent_orders)) : ?>
      <div class="pap-account-empty-state">
        <p><?php esc_html_e('Nu ai comenzi încă.', 'papetarie-storefront'); ?></p>
        <a class="pap-account-row-action" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">
          <?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span>
        </a>
      </div>
    <?php else : ?>
      <div class="pap-account-orders-table">
        <div class="pap-account-orders-table__head" aria-hidden="true">
          <span><?php esc_html_e('Comandă', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Data', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Status', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Produse', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Acțiuni', 'papetarie-storefront'); ?></span>
        </div>

        <div class="pap-account-orders-table__body">
          <?php foreach ($recent_orders as $order) : ?>
            <?php
            if (!$order instanceof WC_Order) {
                continue;
            }

            $items_count = function_exists('papetarie_storefront_account_order_items_count') ? papetarie_storefront_account_order_items_count($order) : max(1, (int) $order->get_item_count());
            ?>
            <article class="pap-account-order-row">
              <div class="pap-account-order-row__cell pap-account-order-row__cell--order" data-label="<?php esc_attr_e('Comandă', 'papetarie-storefront'); ?>">
                <strong><?php echo esc_html(function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($order) : ('#' . $order->get_order_number())); ?></strong>
                <span><?php echo esc_html($order->get_date_created() ? wp_date('j F Y', $order->get_date_created()->getTimestamp()) : __('Nespecificat', 'papetarie-storefront')); ?></span>
              </div>
              <div class="pap-account-order-row__cell pap-account-order-row__cell--date" data-label="<?php esc_attr_e('Data', 'papetarie-storefront'); ?>">
                <span><?php echo esc_html($order->get_date_created() ? wp_date('j F Y, H:i', $order->get_date_created()->getTimestamp()) : __('Nespecificat', 'papetarie-storefront')); ?></span>
              </div>
              <div class="pap-account-order-row__cell pap-account-order-row__cell--status" data-label="<?php esc_attr_e('Status', 'papetarie-storefront'); ?>">
                <?php echo function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($order) : esc_html(wc_get_order_status_name($order->get_status())); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </div>
              <div class="pap-account-order-row__cell pap-account-order-row__cell--products" data-label="<?php esc_attr_e('Produse', 'papetarie-storefront'); ?>">
                <strong><?php echo esc_html(sprintf(_n('%d produs', '%d produse', $items_count, 'papetarie-storefront'), $items_count)); ?></strong>
              </div>
              <div class="pap-account-order-row__cell pap-account-order-row__cell--total" data-label="<?php esc_attr_e('Total', 'papetarie-storefront'); ?>">
                <strong><?php echo wp_kses_post(function_exists('papetarie_storefront_format_plain_currency_amount') ? papetarie_storefront_format_plain_currency_amount((float) $order->get_total()) : $order->get_formatted_order_total()); ?></strong>
              </div>
              <div class="pap-account-order-row__cell pap-account-order-row__cell--actions" data-label="<?php esc_attr_e('Acțiuni', 'papetarie-storefront'); ?>">
                <a class="pap-account-row-action" href="<?php echo esc_url($order->get_view_order_url()); ?>">
                  <?php esc_html_e('Vezi detalii', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>
