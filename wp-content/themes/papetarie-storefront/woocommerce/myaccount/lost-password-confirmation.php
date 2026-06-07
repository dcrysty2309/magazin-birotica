<?php
/**
 * Lost password confirmation text.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_confirmation_message');

if (!function_exists('wc_has_notice') || !wc_has_notice(__('Un email a fost trimis cu succes. Verifică inboxul.', 'papetarie-storefront'), 'success')) {
    papetarie_storefront_store_auth_notice(__('Un email a fost trimis cu succes. Verifică inboxul.', 'papetarie-storefront'), 'success');
}
?>

<div class="pap-auth-shell pap-auth-shell--lost-password pap-auth-shell--lost-password-confirmation">
  <div class="pap-shell pap-auth-shell-inner">
    <?php papetarie_storefront_render_auth_hero('lost-password'); ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>

      <section class="pap-auth-card pap-auth-card--single">
        <h2><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></h2>
        <p class="pap-auth-card-intro"><?php esc_html_e('Verifică inboxul pentru linkul de resetare și urmează pașii din mesaj.', 'papetarie-storefront'); ?></p>
        <p class="pap-auth-card-note"><?php esc_html_e('Poți reveni oricând la autentificare și continua de unde ai rămas.', 'papetarie-storefront'); ?></p>

        <div class="pap-auth-form-actions pap-auth-form-actions--single">
          <a class="woocommerce-button button pap-auth-back-link" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/')); ?>">
            <span class="pap-auth-back-link-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <path d="M14.5 5 8.5 11l6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span><?php esc_html_e('Înapoi la autentificare', 'papetarie-storefront'); ?></span>
          </a>
        </div>
      </section>
    </div>
  </div>
</div>

<?php do_action('woocommerce_after_lost_password_confirmation_message'); ?>
