<?php
/**
 * Login Form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$registration_enabled = 'yes' === get_option('woocommerce_enable_myaccount_registration');
?>

<div class="pap-auth-shell pap-auth-shell--login">
  <div class="pap-shell pap-auth-shell-inner">
    <?php papetarie_storefront_render_auth_hero('login'); ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>
      <?php if ($registration_enabled) : ?>
        <div class="pap-auth-tabs" role="tablist" aria-label="<?php esc_attr_e('Autentificare și creare cont', 'papetarie-storefront'); ?>">
          <button class="pap-auth-tab is-active" type="button" data-auth-tab="login" aria-selected="true"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
          <button class="pap-auth-tab" type="button" data-auth-tab="register" aria-selected="false"><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></button>
        </div>
      <?php endif; ?>

      <div class="pap-auth-panels">
        <section class="pap-auth-card pap-auth-card--login is-active" data-auth-panel="login">
          <h2><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></h2>
          <form class="woocommerce-form woocommerce-form-login login pap-auth-form" method="post" novalidate data-auth-form="login">
            <?php do_action('woocommerce_login_form_start'); ?>

            <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
              <label for="username"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
              <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

            <div class="pap-auth-form-meta pap-auth-form-meta--login">
              <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                <span><?php esc_html_e('Ține-mă minte', 'papetarie-storefront'); ?></span>
              </label>
              <a class="pap-auth-link-action" href="<?php echo esc_url(function_exists('wc_lostpassword_url') ? wc_lostpassword_url() : wp_lostpassword_url()); ?>"><?php esc_html_e('Ai uitat parola?', 'papetarie-storefront'); ?></a>
            </div>

            <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>

            <p class="form-row pap-auth-form-actions pap-auth-form-actions--login">
              <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Autentificare', 'papetarie-storefront'); ?>"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
            </p>

            <?php if ($registration_enabled) : ?>
              <div class="pap-auth-form-links pap-auth-form-links--login">
                <span class="pap-auth-form-links-label"><?php esc_html_e('Nu ai cont?', 'papetarie-storefront'); ?></span>
                <a class="pap-auth-inline-switch" href="#register" data-auth-switch="register"><?php esc_html_e('Creează unul nou', 'papetarie-storefront'); ?></a>
              </div>
            <?php endif; ?>

            <?php do_action('woocommerce_login_form_end'); ?>
          </form>
        </section>

        <?php if ($registration_enabled) : ?>
          <section class="pap-auth-card pap-auth-card--register" data-auth-panel="register" hidden>
            <h2><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></h2>
            <p class="pap-auth-card-intro"><?php esc_html_e('Finalizezi comenzi mai repede, vezi istoricul și salvezi favoritele.', 'papetarie-storefront'); ?></p>

            <form method="post" class="woocommerce-form woocommerce-form-register register pap-auth-form" data-auth-form="register" novalidate <?php do_action('woocommerce_register_form_tag'); ?>>
              <?php do_action('woocommerce_register_form_start'); ?>

              <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                  <label for="reg_username"><?php esc_html_e('Nume utilizator', 'papetarie-storefront'); ?></label>
                  <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" placeholder="<?php esc_attr_e('Nume utilizator', 'papetarie-storefront'); ?>" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </fieldset>
              <?php endif; ?>

              <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                <label for="reg_email"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
                <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </fieldset>

              <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                  <label for="reg_password"><?php esc_html_e('Parolă', 'papetarie-storefront'); ?></label>
                  <span class="pap-password-field" data-password-field>
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e('Introdu parola', 'papetarie-storefront'); ?>" required aria-required="true" />
                    <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                      <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                  </span>
                  <small class="pap-auth-password-note"><?php esc_html_e('Alege o parolă sigură, cu cel puțin 8 caractere.', 'papetarie-storefront'); ?></small>
                </fieldset>
              <?php else : ?>
                <p class="pap-auth-card-note"><?php esc_html_e('Un link pentru setarea parolei va fi trimis la emailul tău.', 'papetarie-storefront'); ?></p>
              <?php endif; ?>

              <?php do_action('woocommerce_register_form'); ?>

              <p class="form-row pap-auth-form-actions">
                <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Creare cont', 'papetarie-storefront'); ?>"><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></button>
              </p>

              <div class="pap-auth-form-links">
                <span><?php esc_html_e('Ai deja cont?', 'papetarie-storefront'); ?></span>
                <button class="pap-auth-inline-switch" type="button" data-auth-switch="login"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
              </div>

              <?php do_action('woocommerce_register_form_end'); ?>
            </form>
          </section>
        <?php endif; ?>
      </div>

      <?php papetarie_storefront_render_social_login_area(); ?>
    </div>
  </div>
</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
