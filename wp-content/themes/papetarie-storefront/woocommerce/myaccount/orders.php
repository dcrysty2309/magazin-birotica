<?php
/**
 * My Account orders.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$current_page = max(1, (int) $current_page);
?>

<div class="pap-account-page pap-account-page--orders">
  <article class="pap-account-simplecard">
    <div class="pap-account-simplecard__head">
      <h2><?php esc_html_e('Comenzile mele', 'papetarie-storefront'); ?></h2>
    </div>

    <?php if (!$has_orders) : ?>
      <div class="pap-account-simplecard__empty">
        <div class="pap-account-simplecard__empty-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3.5" y="8" width="17" height="12" rx="1.5"></rect>
            <path d="M3.5 8 12 3l8.5 5"></path>
            <path d="M9 12v2.5a3 3 0 0 0 6 0V12"></path>
          </svg>
        </div>
        <h3><?php esc_html_e('Nu ai comenzi încă', 'papetarie-storefront'); ?></h3>
        <p><?php esc_html_e('Comenzile tale vor apărea aici după prima achiziție.', 'papetarie-storefront'); ?></p>
      </div>
    <?php else : ?>
      <div class="pap-account-orders-list-table">
        <div class="pap-account-orders-list-table__head" aria-hidden="true">
          <span><?php esc_html_e('Comandă', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Dată', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
          <span><?php esc_html_e('Status', 'papetarie-storefront'); ?></span>
          <span></span>
        </div>

        <?php foreach ($customer_orders->orders as $customer_order) : ?>
          <?php
          $order = wc_get_order($customer_order); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
          if (!$order instanceof WC_Order) {
              continue;
          }
          ?>
          <div class="pap-account-orders-list-table__row">
            <span class="pap-account-orders-list-table__number"><?php echo esc_html(function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($order) : ('#' . $order->get_order_number())); ?></span>
            <span class="pap-account-orders-list-table__date"><?php echo esc_html($order->get_date_created() ? wp_date('j M Y, H:i', $order->get_date_created()->getTimestamp()) : __('Nespecificat', 'papetarie-storefront')); ?></span>
            <span class="pap-account-orders-list-table__total"><?php echo wp_kses_post(function_exists('papetarie_storefront_format_plain_currency_amount') ? papetarie_storefront_format_plain_currency_amount((float) $order->get_total()) : $order->get_formatted_order_total()); ?></span>
            <span><?php echo function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($order) : esc_html(wc_get_order_status_name($order->get_status())); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            <span class="pap-account-orders-list-table__action">
              <a class="pap-account-row-action" href="<?php echo esc_url($order->get_view_order_url()); ?>">
                <?php esc_html_e('Vezi detalii', 'papetarie-storefront'); ?>
              </a>
            </span>
          </div>
        <?php endforeach; ?>

      </div>
    <?php endif; ?>
  </article>

  <?php if ($has_orders && 1 < (int) $customer_orders->max_num_pages) : ?>
    <?php
    $page_count = (int) $customer_orders->max_num_pages;
    $build_page_url = static function (int $page): string {
        return wc_get_endpoint_url('orders', (string) $page);
    };
    $pagination_chevron = papetarie_storefront_icon('chevron');
    ?>
    <nav class="pap-pagination-nav" aria-label="<?php esc_attr_e('Paginare comenzi', 'papetarie-storefront'); ?>">
      <?php if ($current_page > 1) : ?>
        <a class="page-numbers prev" href="<?php echo esc_url($build_page_url($current_page - 1)); ?>" aria-label="<?php esc_attr_e('Pagina anterioară', 'papetarie-storefront'); ?>">
          <span class="pap-pagination-icon pap-pagination-icon--prev" aria-hidden="true"><?php echo $pagination_chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        </a>
      <?php else : ?>
        <span class="page-numbers prev disabled" aria-hidden="true">
          <span class="pap-pagination-icon pap-pagination-icon--prev" aria-hidden="true"><?php echo $pagination_chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        </span>
      <?php endif; ?>

      <?php for ($page = 1; $page <= $page_count; $page++) : ?>
        <?php if ($page === $current_page) : ?>
          <span class="page-numbers current" aria-current="page"><?php echo esc_html((string) $page); ?></span>
        <?php else : ?>
          <a class="page-numbers" href="<?php echo esc_url($build_page_url($page)); ?>"><?php echo esc_html((string) $page); ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($current_page < $page_count) : ?>
        <a class="page-numbers next" href="<?php echo esc_url($build_page_url($current_page + 1)); ?>" aria-label="<?php esc_attr_e('Pagina următoare', 'papetarie-storefront'); ?>">
          <span class="pap-pagination-icon" aria-hidden="true"><?php echo $pagination_chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        </a>
      <?php else : ?>
        <span class="page-numbers next disabled" aria-hidden="true">
          <span class="pap-pagination-icon" aria-hidden="true"><?php echo $pagination_chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        </span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
