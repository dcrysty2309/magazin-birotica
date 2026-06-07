<?php
/**
 * Lost password reset form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_reset_password_form');

$args = wp_parse_args(
    isset($args) && is_array($args) ? $args : [],
    [
        'key' => isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '',
        'login' => isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '',
    ]
);
?>

<div class="pap-auth-shell pap-auth-shell--reset-password">
  <div class="pap-shell pap-auth-shell-inner">
    <?php papetarie_storefront_render_auth_hero('reset-password'); ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>
      <div class="pap-auth-panel-head">
        <p class="pap-auth-eyebrow"><?php esc_html_e('Parolă nouă', 'papetarie-storefront'); ?></p>
        <h1><?php esc_html_e('Alege o parolă sigură', 'papetarie-storefront'); ?></h1>
        <p><?php esc_html_e('Completează cele două câmpuri de mai jos pentru a salva o parolă nouă.', 'papetarie-storefront'); ?></p>
      </div>

      <section class="pap-auth-card pap-auth-card--single">
        <form method="post" class="woocommerce-ResetPassword lost_reset_password pap-auth-form" data-auth-form="reset-password" novalidate>
          <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
            <label for="password_1"><?php esc_html_e('Parolă nouă', 'papetarie-storefront'); ?></label>
            <span class="pap-password-field" data-password-field>
              <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" />
              <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </button>
            </span>
          </fieldset>

          <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
            <label for="password_2"><?php esc_html_e('Confirmă parola', 'papetarie-storefront'); ?></label>
            <span class="pap-password-field" data-password-field>
              <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" />
              <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </button>
            </span>
          </fieldset>

          <input type="hidden" name="reset_key" value="<?php echo esc_attr($args['key']); ?>" />
          <input type="hidden" name="reset_login" value="<?php echo esc_attr($args['login']); ?>" />

          <?php do_action('woocommerce_resetpassword_form'); ?>

          <p class="form-row pap-auth-form-actions">
            <input type="hidden" name="wc_reset_password" value="true" />
            <button type="submit" class="woocommerce-Button button" value="<?php esc_attr_e('Salvează parola', 'papetarie-storefront'); ?>"><?php esc_html_e('Salvează parola', 'papetarie-storefront'); ?></button>
          </p>

          <?php wp_nonce_field('reset_password', 'woocommerce-reset-password-nonce'); ?>
        </form>
      </section>
    </div>
  </div>
</div>

<?php do_action('woocommerce_after_reset_password_form'); ?>
