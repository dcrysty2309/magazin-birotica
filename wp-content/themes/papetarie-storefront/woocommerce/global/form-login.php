<?php
/**
 * Login form for checkout / widgets.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$redirect = isset($redirect) ? $redirect : '';
?>

<div class="pap-auth-inline">
  <div class="pap-auth-card pap-auth-card--inline">
    <h2><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></h2>
    <?php papetarie_storefront_render_auth_notices(); ?>
    <form class="woocommerce-form woocommerce-form-login login pap-auth-form" method="post" novalidate data-auth-form="login">
      <?php do_action('woocommerce_login_form_start'); ?>

      <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
        <label for="username"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
      </fieldset>

      <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
        <label for="password"><?php esc_html_e('Parolă', 'papetarie-storefront'); ?></label>
        <span class="pap-password-field" data-password-field>
          <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="<?php esc_attr_e('Introdu parola', 'papetarie-storefront'); ?>" required aria-required="true" />
          <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
            <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </button>
        </span>
      </fieldset>

      <?php do_action('woocommerce_login_form'); ?>

      <p class="form-row pap-auth-form-actions">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
          <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
          <span><?php esc_html_e('Ține-mă minte', 'papetarie-storefront'); ?></span>
        </label>
        <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Autentificare', 'papetarie-storefront'); ?>"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
      </p>

      <div class="pap-auth-form-links">
        <a class="pap-auth-link-action" href="<?php echo esc_url(function_exists('wc_lostpassword_url') ? wc_lostpassword_url() : wp_lostpassword_url()); ?>"><?php esc_html_e('Ai uitat parola?', 'papetarie-storefront'); ?></a>
        <?php if (!empty($redirect)) : ?>
          <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect); ?>">
        <?php endif; ?>
      </div>

      <?php do_action('woocommerce_login_form_end'); ?>
    </form>
    <?php papetarie_storefront_render_social_login_area(); ?>
  </div>
</div>
