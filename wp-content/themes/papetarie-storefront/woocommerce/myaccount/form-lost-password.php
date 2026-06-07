<?php
/**
 * Lost password form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

<div class="pap-auth-shell pap-auth-shell--lost-password">
  <div class="pap-shell pap-auth-shell-inner">
    <?php papetarie_storefront_render_auth_hero('lost-password'); ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>

      <section class="pap-auth-card pap-auth-card--single">
        <h2><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></h2>
        <p class="pap-auth-card-intro"><?php esc_html_e('Completează câmpul de mai jos și îți trimitem linkul de resetare.', 'papetarie-storefront'); ?></p>
        <form method="post" class="woocommerce-ResetPassword lost_reset_password pap-auth-form" data-auth-form="lost-password" novalidate>
          <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
            <label for="user_login"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text" type="email" name="user_login" id="user_login" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>" required aria-required="true" />
          </fieldset>

          <?php do_action('woocommerce_lostpassword_form'); ?>

          <p class="form-row pap-auth-form-actions">
            <input type="hidden" name="wc_reset_password" value="true" />
            <button type="submit" class="woocommerce-Button button" value="<?php esc_attr_e('Resetare parolă', 'papetarie-storefront'); ?>"><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></button>
          </p>

          <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>

          <div class="pap-auth-form-links">
            <a class="pap-auth-back-link" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/')); ?>">
              <span class="pap-auth-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                  <path d="M14.5 5 8.5 11l6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span><?php esc_html_e('Înapoi la autentificare', 'papetarie-storefront'); ?></span>
            </a>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>

<?php do_action('woocommerce_after_lost_password_form'); ?>
