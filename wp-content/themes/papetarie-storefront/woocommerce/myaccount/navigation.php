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

$account_menu_icons = function_exists('papetarie_storefront_account_menu_icon_map')
    ? papetarie_storefront_account_menu_icon_map()
    : [
        'dashboard' => 'home',
        'orders' => 'cart',
        'favorite' => 'heart',
        'edit-address' => 'location',
        'edit-account' => 'user',
        'customer-logout' => 'lock-outline',
    ];
$show_help_card = function_exists('is_wc_endpoint_url') && is_wc_endpoint_url();
?>

<nav class="woocommerce-MyAccount-navigation pap-account-nav" aria-label="<?php esc_attr_e('Account pages', 'woocommerce'); ?>">
  <div class="pap-account-nav-profile">
    <div class="pap-account-avatar" aria-hidden="true">
      <?php echo get_avatar($current_user->ID, 64, '', $display_name, ['class' => 'pap-account-avatar__img']); ?>
    </div>
    <div class="pap-account-nav-copy">
      <strong><?php echo esc_html($display_name); ?></strong>
      <span><?php echo esc_html($display_email !== '' ? $display_email : __('Cont autenticat', 'papetarie-storefront')); ?></span>
    </div>
  </div>

  <ul class="pap-account-nav-list">
    <?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
      <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
          <span class="pap-account-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($account_menu_icons[$endpoint] ?? 'chevron'); ?></span>
          <span class="pap-account-nav-label"><?php echo esc_html($label); ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($show_help_card) : ?>
    <aside class="pap-account-help-card">
      <div class="pap-account-help-card__icon" aria-hidden="true">
        <?php echo papetarie_storefront_icon('headset-outline'); ?>
      </div>
      <div class="pap-account-help-card__copy">
        <strong><?php esc_html_e('Ai nevoie de ajutor?', 'papetarie-storefront'); ?></strong>
        <p><?php esc_html_e('Suntem aici pentru tine.', 'papetarie-storefront'); ?></p>
      </div>
      <a class="pap-account-help-card__button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Contactează-ne', 'papetarie-storefront'); ?></a>
    </aside>
  <?php endif; ?>
</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
