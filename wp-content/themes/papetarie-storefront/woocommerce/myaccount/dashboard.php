<?php
/**
 * My Account Dashboard
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$order_count = function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($user_id) : 0;
$total_spent = function_exists('wc_get_customer_total_spent') ? (float) wc_get_customer_total_spent($user_id) : 0;
$wishlist_ids = function_exists('papetarie_storefront_account_wishlist_ids') ? papetarie_storefront_account_wishlist_ids() : [];
$recent_orders = function_exists('wc_get_orders') ? wc_get_orders([
    'customer_id' => $user_id,
    'limit' => 4,
    'orderby' => 'date',
    'order' => 'DESC',
]) : [];
$recently_viewed_ids = function_exists('papetarie_storefront_recently_viewed_product_ids') ? papetarie_storefront_recently_viewed_product_ids(4) : [];
$recommended_query = new WP_Query([
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => 4,
    'orderby' => 'date',
    'order' => 'DESC',
    'fields' => 'ids',
    'no_found_rows' => true,
    'post__not_in' => $wishlist_ids,
]);
?>

<div class="pap-account-dashboard">
  <div class="pap-account-dashboard-hero">
    <div class="pap-account-dashboard-hero-copy">
      <p class="pap-auth-eyebrow"><?php esc_html_e('My Account', 'papetarie-storefront'); ?></p>
      <h1><?php printf(esc_html__('Bun venit, %s', 'papetarie-storefront'), esc_html(wp_get_current_user()->display_name ?: wp_get_current_user()->user_email)); ?></h1>
      <p><?php esc_html_e('De aici urmărești comenzile, adresele, favoritele și suportul pentru contul tău.', 'papetarie-storefront'); ?></p>
    </div>
    <div class="pap-account-dashboard-actions">
      <a class="button" href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('Comenzile mele', 'papetarie-storefront'); ?></a>
      <a class="button" href="<?php echo esc_url(wc_get_account_endpoint_url('favorite')); ?>"><?php esc_html_e('Favorite', 'papetarie-storefront'); ?></a>
      <a class="button" href="<?php echo esc_url(wc_get_account_endpoint_url('suport')); ?>"><?php esc_html_e('Suport', 'papetarie-storefront'); ?></a>
      <a class="button" href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>"><?php esc_html_e('Detalii cont', 'papetarie-storefront'); ?></a>
    </div>
  </div>

  <div class="pap-account-stats">
    <article class="pap-account-stat-card">
      <span><?php esc_html_e('Comenzi', 'papetarie-storefront'); ?></span>
      <strong><?php echo esc_html((string) $order_count); ?></strong>
    </article>
    <article class="pap-account-stat-card">
      <span><?php esc_html_e('Valoare totală', 'papetarie-storefront'); ?></span>
      <strong><?php echo wp_kses_post(wc_price($total_spent)); ?></strong>
    </article>
    <article class="pap-account-stat-card">
      <span><?php esc_html_e('Favorite', 'papetarie-storefront'); ?></span>
      <strong data-wishlist-count><?php echo esc_html((string) count($wishlist_ids)); ?></strong>
    </article>
    <article class="pap-account-stat-card">
      <span><?php esc_html_e('Ultima comandă', 'papetarie-storefront'); ?></span>
      <strong>
        <?php
        if (!empty($recent_orders)) {
            $last_order = $recent_orders[0];
            echo esc_html($last_order instanceof WC_Order ? wc_format_datetime($last_order->get_date_created()) : __('Nespecificat', 'papetarie-storefront'));
        } else {
            esc_html_e('Nicio comandă', 'papetarie-storefront');
        }
        ?>
      </strong>
    </article>
  </div>

  <div class="pap-account-dashboard-grid">
    <section class="pap-account-section pap-account-section--orders">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Ultimele comenzi', 'papetarie-storefront'); ?></h2>
      </div>
      <?php if (empty($recent_orders)) : ?>
        <div class="pap-account-empty">
          <p><?php esc_html_e('Nu ai comenzi încă.', 'papetarie-storefront'); ?></p>
        </div>
      <?php else : ?>
        <div class="pap-account-orders-list">
          <?php foreach ($recent_orders as $order) : ?>
            <?php if (!$order instanceof WC_Order) { continue; } ?>
            <article class="pap-account-order-card">
              <strong><?php echo esc_html(sprintf(__('Comanda #%s', 'papetarie-storefront'), $order->get_order_number())); ?></strong>
              <span><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></span>
              <span><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
              <span><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
              <a href="<?php echo esc_url($order->get_view_order_url()); ?>"><?php esc_html_e('Vezi detalii', 'papetarie-storefront'); ?></a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="pap-account-section">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Produse recomandate', 'papetarie-storefront'); ?></h2>
      </div>
      <div class="pap-account-product-grid">
        <?php if ($recommended_query->have_posts()) : ?>
          <?php foreach ($recommended_query->posts as $product_id) : ?>
            <?php $product = wc_get_product((int) $product_id); ?>
            <?php if (!$product instanceof WC_Product) { continue; } ?>
            <?php papetarie_storefront_render_product_card($product, 'account'); ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section class="pap-account-section">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Vizualizate recent', 'papetarie-storefront'); ?></h2>
      </div>
      <?php if (empty($recently_viewed_ids)) : ?>
        <div class="pap-account-empty">
          <p><?php esc_html_e('Nu există produse vizualizate recent.', 'papetarie-storefront'); ?></p>
        </div>
      <?php else : ?>
        <div class="pap-account-product-grid">
          <?php foreach ($recently_viewed_ids as $product_id) : ?>
            <?php $product = wc_get_product((int) $product_id); ?>
            <?php if (!$product instanceof WC_Product) { continue; } ?>
            <?php papetarie_storefront_render_product_card($product, 'account'); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <?php do_action('woocommerce_account_dashboard'); ?>
</div>
