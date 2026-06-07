<?php
/**
 * My Account navigation
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_navigation');

$current_user = wp_get_current_user();
$account_menu_icons = [
    'dashboard' => 'account',
    'orders' => 'cart',
    'downloads' => 'paper',
    'edit-address' => 'office',
    'payment-methods' => 'tag',
    'edit-account' => 'pen',
    'favorite' => 'heart',
    'oferte' => 'tag',
    'suport' => 'headset-outline',
    'retururi' => 'truck-outline',
    'customer-logout' => 'lock-outline',
];
?>

<nav class="woocommerce-MyAccount-navigation pap-account-nav" aria-label="<?php esc_attr_e('Account pages', 'woocommerce'); ?>">
  <div class="pap-account-nav-profile">
    <div class="pap-account-avatar">
      <?php echo get_avatar($current_user->ID, 96, '', $current_user->display_name ?: $current_user->user_email); ?>
    </div>
    <div class="pap-account-nav-copy">
      <strong><?php echo esc_html($current_user->display_name ?: __('Cont client', 'papetarie-storefront')); ?></strong>
      <span><?php echo esc_html($current_user->user_email); ?></span>
    </div>
  </div>

  <ul>
    <?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
      <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
          <span class="pap-account-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($account_menu_icons[$endpoint] ?? 'chevron'); ?></span>
          <span><?php echo esc_html($label); ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
