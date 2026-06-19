<?php
/**
 * My Account orders.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$status_filter = isset($_GET['order_status']) ? sanitize_key(wp_unslash($_GET['order_status'])) : 'all';
$period_filter = isset($_GET['order_period']) ? sanitize_key(wp_unslash($_GET['order_period'])) : 'all';
$search_filter = isset($_GET['order_search']) ? sanitize_text_field(wp_unslash($_GET['order_search'])) : '';
$current_page = max(1, (int) $current_page);
$current_args = $_GET;
unset($current_args['orders-page'], $current_args['paged']);

$status_options = [
    'all' => __('Toate comenzile', 'papetarie-storefront'),
    'completed' => __('Livrate', 'papetarie-storefront'),
    'processing' => __('În procesare', 'papetarie-storefront'),
    'pending' => __('În așteptare', 'papetarie-storefront'),
    'cancelled' => __('Anulate', 'papetarie-storefront'),
];

$period_options = [
    'all' => __('Oricând', 'papetarie-storefront'),
    '30d' => __('Ultimele 30 de zile', 'papetarie-storefront'),
    '90d' => __('Ultimele 90 de zile', 'papetarie-storefront'),
    '180d' => __('Ultimele 6 luni', 'papetarie-storefront'),
    '365d' => __('Ultimele 12 luni', 'papetarie-storefront'),
];
?>

<div class="pap-account-page pap-account-page--orders">
  <header class="pap-account-page-head">
    <p class="pap-account-page-eyebrow"><?php esc_html_e('Comenzi', 'papetarie-storefront'); ?></p>
    <h1><?php esc_html_e('Comenzile mele', 'papetarie-storefront'); ?></h1>
    <p><?php esc_html_e('Vezi istoricul comenzilor tale și intră rapid în detalii.', 'papetarie-storefront'); ?></p>
  </header>

  <section class="pap-account-panel pap-account-panel--orders">
    <form class="pap-account-orders-toolbar" method="get">
      <label class="pap-account-filter">
        <span><?php esc_html_e('Stare comandă', 'papetarie-storefront'); ?></span>
        <select name="order_status">
          <?php foreach ($status_options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>"<?php selected($status_filter, $value); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="pap-account-filter">
        <span><?php esc_html_e('Perioadă', 'papetarie-storefront'); ?></span>
        <select name="order_period">
          <?php foreach ($period_options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>"<?php selected($period_filter, $value); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="pap-account-search">
        <span class="screen-reader-text"><?php esc_html_e('Caută după numărul comenzii', 'papetarie-storefront'); ?></span>
        <input
          type="search"
          name="order_search"
          value="<?php echo esc_attr($search_filter); ?>"
          placeholder="<?php esc_attr_e('Caută după numărul comenzii...', 'papetarie-storefront'); ?>"
        >
      </label>

      <button class="pap-account-filter-submit" type="submit"><?php esc_html_e('Aplică', 'papetarie-storefront'); ?></button>
    </form>

    <?php if (!$has_orders) : ?>
      <div class="pap-account-empty-state">
        <p><?php esc_html_e('Nu ai comenzi încă.', 'papetarie-storefront'); ?></p>
      </div>
    <?php else : ?>
      <div class="pap-account-orders-table pap-account-orders-table--list">
        <div class="pap-account-orders-table__head" aria-hidden="true">
          <span><?php esc_html_e('Comandă', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Data', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Status', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Produse', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Acțiuni', 'papetarie-storefront'); ?></span>
        </div>

        <div class="pap-account-orders-table__body">
          <?php foreach ($customer_orders->orders as $customer_order) : ?>
            <?php
            $order = wc_get_order($customer_order); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
                  <?php esc_html_e('Detalii', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if (1 < (int) $customer_orders->max_num_pages) : ?>
          <nav class="pap-account-pagination" aria-label="<?php esc_attr_e('Paginare comenzi', 'papetarie-storefront'); ?>">
            <?php
            $page_count = (int) $customer_orders->max_num_pages;
            $query_args = array_filter($current_args, static fn($value) => $value !== '' && $value !== null);
            unset($query_args['paged'], $query_args['orders-page']);

            $build_page_url = static function (int $page) use ($query_args): string {
                $url = wc_get_endpoint_url('orders', (string) $page);
                if (!empty($query_args)) {
                    $url = add_query_arg($query_args, $url);
                }
                return $url;
            };
            ?>
            <?php if ($current_page > 1) : ?>
              <a class="pap-account-pagination__button" href="<?php echo esc_url($build_page_url($current_page - 1)); ?>" aria-label="<?php esc_attr_e('Pagina anterioară', 'papetarie-storefront'); ?>">‹</a>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $page_count; $page++) : ?>
              <a class="pap-account-pagination__button<?php echo $page === $current_page ? ' is-active' : ''; ?>" href="<?php echo esc_url($build_page_url($page)); ?>"><?php echo esc_html((string) $page); ?></a>
            <?php endfor; ?>

            <?php if ($current_page < $page_count) : ?>
              <a class="pap-account-pagination__button" href="<?php echo esc_url($build_page_url($current_page + 1)); ?>" aria-label="<?php esc_attr_e('Pagina următoare', 'papetarie-storefront'); ?>">›</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
