<?php
/**
 * My Account navigation
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_navigation');

$current_user = wp_get_current_user();
$display_name = trim((string) $current_user->display_name);
if ('' === $display_name) {
    $display_name = trim((string) $current_user->first_name . ' ' . (string) $current_user->last_name);
}
if ('' === $display_name) {
    $display_name = (string) $current_user->user_login;
}

$display_email = trim((string) $current_user->user_email);
$display_initials = function_exists('papetarie_storefront_account_initials')
    ? papetarie_storefront_account_initials($display_name)
    : strtoupper(substr($display_name !== '' ? $display_name : 'A', 0, 2));

$account_menu_icons = function_exists('papetarie_storefront_account_menu_icon_map')
    ? papetarie_storefront_account_menu_icon_map()
    : [
        'dashboard' => 'sidebar-home',
        'orders' => 'sidebar-orders',
        'edit-address' => 'sidebar-address',
        'edit-account' => 'sidebar-details',
        'customer-logout' => 'sidebar-logout',
    ];

$account_orders_count = function_exists('papetarie_storefront_account_customer_order_count')
    ? papetarie_storefront_account_customer_order_count((int) $current_user->ID)
    : 0;
?>

<nav class="woocommerce-MyAccount-navigation pap-account-nav" aria-label="<?php esc_attr_e('Account pages', 'woocommerce'); ?>">
  <div class="pap-account-nav-profile">
    <div class="pap-account-avatar" aria-hidden="true"><?php echo esc_html($display_initials); ?></div>
    <div class="pap-account-nav-copy">
      <strong><?php echo esc_html($display_name); ?></strong>
      <span><?php echo esc_html($display_email !== '' ? $display_email : __('Cont autenticat', 'papetarie-storefront')); ?></span>
    </div>
  </div>

  <ul class="pap-account-nav-list">
    <?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
      <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
        <a class="pap-account-nav-link" href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
          <?php $account_icon = $account_menu_icons[$endpoint] ?? ''; ?>
          <?php if ($account_icon !== '') : ?>
            <span class="pap-account-nav-icon" aria-hidden="true"><?php echo function_exists('papetarie_storefront_render_account_icon') ? papetarie_storefront_render_account_icon($account_icon) : ''; ?></span>
          <?php endif; ?>
          <span class="pap-account-nav-label"><?php echo esc_html($label); ?></span>
          <?php if ($endpoint === 'orders' && $account_orders_count > 0) : ?>
            <span class="pap-account-nav-badge"><?php echo esc_html((string) $account_orders_count); ?></span>
          <?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
