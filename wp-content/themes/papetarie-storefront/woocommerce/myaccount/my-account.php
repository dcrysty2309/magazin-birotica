<?php
/**
 * My Account page wrapper.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    do_action('woocommerce_account_content');
    return;
}
?>

<div class="pap-account-shell">
  <div class="pap-shell pap-account-shell-inner">
    <aside class="pap-account-sidebar">
      <?php do_action('woocommerce_account_navigation'); ?>
    </aside>

    <main class="woocommerce-MyAccount-content pap-account-content" id="pap-account-content">
      <?php do_action('woocommerce_account_content'); ?>
    </main>
  </div>
</div>
