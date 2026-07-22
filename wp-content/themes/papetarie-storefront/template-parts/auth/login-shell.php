<?php
/**
 * Reusable authentication shell.
 *
 * @package papetarie-storefront
 */

defined('ABSPATH') || exit;

$args = wp_parse_args(
    is_array($args ?? null) ? $args : [],
    [
        'context' => 'page',
        'show_visual' => true,
        'show_register' => true,
        'id_prefix' => 'pap-auth-page-',
    ]
);

$context = sanitize_key((string) $args['context']);
$show_visual = !empty($args['show_visual']);
$show_register = !empty($args['show_register']);
$id_prefix = sanitize_key((string) $args['id_prefix']);

if ('' === $id_prefix) {
    $id_prefix = 'pap-auth-' . $context . '-';
}

$shell_classes = [
    'pap-auth-shell',
    'pap-auth-shell--login',
    'pap-auth-shell--' . $context,
];

if (!$show_visual) {
    $shell_classes[] = 'pap-auth-shell--modal';
}

if ('modal' !== $context) {
    do_action('woocommerce_before_customer_login_form');
}
?>

<div class="<?php echo esc_attr(implode(' ', $shell_classes)); ?>" data-auth-root data-auth-view="login" data-auth-context="<?php echo esc_attr($context); ?>">
  <div class="pap-shell pap-auth-shell-inner">
    <?php if ($show_visual) : ?>
      <?php papetarie_storefront_render_auth_hero('login'); ?>
    <?php endif; ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>
      <?php if ($show_register && 'modal' !== $context) : ?>
        <div class="pap-auth-tabs" role="tablist" aria-label="<?php esc_attr_e('Autentificare și creare cont', 'papetarie-storefront'); ?>">
          <button class="pap-auth-tab is-active" type="button" data-auth-tab="login" aria-selected="true"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
          <button class="pap-auth-tab" type="button" data-auth-tab="register" aria-selected="false"><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></button>
        </div>
      <?php endif; ?>

      <div class="pap-auth-panels">
        <section class="pap-auth-card pap-auth-card--login is-active" data-auth-panel="login">
          <h4><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></h4>
          <form class="woocommerce-form woocommerce-form-login login pap-auth-form" method="post" novalidate data-auth-form="login" data-auth-context="<?php echo esc_attr($context); ?>">
            <?php do_action('woocommerce_login_form_start'); ?>

            <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
              <label for="<?php echo esc_attr($id_prefix . 'username'); ?>"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
              <span class="pap-auth-input-field pap-auth-input-field--email">
                <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <input
                  type="email"
                  class="woocommerce-Input woocommerce-Input--text input-text"
                  name="username"
                  id="<?php echo esc_attr($id_prefix . 'username'); ?>"
                  autocomplete="email"
                  placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>"
                  value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
                  required
                  aria-required="true"
                /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </span>
            </fieldset>

            <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
              <label for="<?php echo esc_attr($id_prefix . 'password'); ?>"><?php esc_html_e('Parolă', 'papetarie-storefront'); ?></label>
              <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
                <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <input
                  class="woocommerce-Input woocommerce-Input--text input-text"
                  type="password"
                  name="password"
                  id="<?php echo esc_attr($id_prefix . 'password'); ?>"
                  autocomplete="current-password"
                  placeholder="<?php esc_attr_e('Introdu parola', 'papetarie-storefront'); ?>"
                  required
                  aria-required="true"
                />
                <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                  <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
              </span>
            </fieldset>

            <?php do_action('woocommerce_login_form'); ?>

            <div class="pap-auth-form-meta pap-auth-form-meta--login">
              <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                <input class="woocommerce-form__input woocommerce-form__input-checkbox pap-checkbox-input" name="rememberme" type="checkbox" id="<?php echo esc_attr($id_prefix . 'rememberme'); ?>" value="forever" />
                <span><?php esc_html_e('Ține-mă minte', 'papetarie-storefront'); ?></span>
              </label>
              <?php if ('modal' === $context) : ?>
                <a class="pap-auth-link-action pap-auth-inline-switch" href="#" data-auth-switch="lost-password"><?php esc_html_e('Ai uitat parola?', 'papetarie-storefront'); ?></a>
              <?php else : ?>
                <a class="pap-auth-link-action" href="<?php echo esc_url(function_exists('wc_lostpassword_url') ? wc_lostpassword_url() : wp_lostpassword_url()); ?>"><?php esc_html_e('Ai uitat parola?', 'papetarie-storefront'); ?></a>
              <?php endif; ?>
            </div>

            <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>

            <p class="form-row pap-auth-form-actions pap-auth-form-actions--login">
              <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Autentificare', 'papetarie-storefront'); ?>"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></button>
            </p>

            <?php if (!$show_register || 'modal' !== $context) : ?>
              <?php do_action('woocommerce_login_form_end'); ?>
            <?php endif; ?>
          </form>

          <?php papetarie_storefront_render_social_login_area([
            'show_register_switch' => $show_register,
          ]); ?>
        </section>

        <?php if ($show_register) : ?>
          <section class="pap-auth-card pap-auth-card--register" data-auth-panel="register" hidden>
            <h4><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></h4>

            <form method="post" class="woocommerce-form woocommerce-form-register register pap-auth-form" data-auth-form="register" novalidate <?php do_action('woocommerce_register_form_tag'); ?>>
              <?php do_action('woocommerce_register_form_start'); ?>

            <div class="pap-form-row pap-form-row--split">
              <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                <label for="<?php echo esc_attr($id_prefix . 'reg_first_name'); ?>"><?php esc_html_e('Prenume', 'papetarie-storefront'); ?></label>
                <span class="pap-auth-input-field pap-auth-input-field--user">
                  <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                  <input
                    type="text"
                    class="woocommerce-Input woocommerce-Input--text input-text"
                    name="first_name"
                    id="<?php echo esc_attr($id_prefix . 'reg_first_name'); ?>"
                    autocomplete="given-name"
                    placeholder="<?php esc_attr_e('Prenume', 'papetarie-storefront'); ?>"
                    value="<?php echo ( ! empty( $_POST['first_name'] ) ) ? esc_attr( wp_unslash( $_POST['first_name'] ) ) : ''; ?>"
                    required
                    aria-required="true"
                  /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
              </fieldset>

              <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                <label for="<?php echo esc_attr($id_prefix . 'reg_last_name'); ?>"><?php esc_html_e('Nume', 'papetarie-storefront'); ?></label>
                <span class="pap-auth-input-field pap-auth-input-field--user">
                  <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                  <input
                    type="text"
                    class="woocommerce-Input woocommerce-Input--text input-text"
                    name="last_name"
                    id="<?php echo esc_attr($id_prefix . 'reg_last_name'); ?>"
                    autocomplete="family-name"
                    placeholder="<?php esc_attr_e('Nume', 'papetarie-storefront'); ?>"
                    value="<?php echo ( ! empty( $_POST['last_name'] ) ) ? esc_attr( wp_unslash( $_POST['last_name'] ) ) : ''; ?>"
                    required
                    aria-required="true"
                  /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
              </fieldset>
            </div>

              <div class="pap-form-row pap-form-row--stack">
                <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                  <label for="<?php echo esc_attr($id_prefix . 'reg_email'); ?>"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
                  <span class="pap-auth-input-field pap-auth-input-field--email">
                    <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <input
                      type="email"
                      class="woocommerce-Input woocommerce-Input--text input-text"
                      name="email"
                      id="<?php echo esc_attr($id_prefix . 'reg_email'); ?>"
                      autocomplete="email"
                      placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>"
                      value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>"
                      required
                      aria-required="true"
                    /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  </span>
                </fieldset>
              </div>

              <div class="pap-form-row pap-form-row--split">
                <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                  <label for="<?php echo esc_attr($id_prefix . 'reg_password'); ?>"><?php esc_html_e('Parolă', 'papetarie-storefront'); ?></label>
                  <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
                    <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <input
                      type="password"
                      class="woocommerce-Input woocommerce-Input--text input-text"
                      name="password"
                      id="<?php echo esc_attr($id_prefix . 'reg_password'); ?>"
                      autocomplete="new-password"
                      placeholder="<?php esc_attr_e('Introdu parola', 'papetarie-storefront'); ?>"
                      required
                      aria-required="true"
                    />
                    <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                      <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                  </span>
                </fieldset>

                <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
                  <label for="<?php echo esc_attr($id_prefix . 'reg_password_confirm'); ?>"><?php esc_html_e('Confirmare parolă', 'papetarie-storefront'); ?></label>
                  <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
                    <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <input
                      type="password"
                      class="woocommerce-Input woocommerce-Input--text input-text"
                      name="password_confirm"
                      id="<?php echo esc_attr($id_prefix . 'reg_password_confirm'); ?>"
                      autocomplete="new-password"
                      placeholder="<?php esc_attr_e('Confirmă parola', 'papetarie-storefront'); ?>"
                      required
                      aria-required="true"
                    />
                    <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                      <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </button>
                  </span>
                </fieldset>
              </div>

              <label class="pap-auth-terms">
                <input type="checkbox" class="pap-checkbox-input" name="agree_terms" value="1" required aria-required="true">
                <span>
                  <?php esc_html_e('Sunt de acord cu', 'papetarie-storefront'); ?>
                  <a href="<?php echo esc_url(function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : home_url('/privacy-policy/')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('politica de confidențialitate', 'papetarie-storefront'); ?>
                  </a>
                </span>
              </label>

              <p class="form-row pap-auth-form-actions">
                <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Creare cont', 'papetarie-storefront'); ?>"><?php esc_html_e('Creare cont', 'papetarie-storefront'); ?></button>
              </p>

              <?php wp_nonce_field('pap_auth_register', 'pap_auth_register_nonce'); ?>

              <div class="pap-auth-social-footer">
                <span class="pap-auth-social-prefix"><?php esc_html_e('Ai deja cont?', 'papetarie-storefront'); ?></span>
                <a class="pap-auth-inline-switch pap-auth-social-switch" href="#" data-auth-switch="login"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></a>
              </div>
            </form>
          </section>

          <section class="pap-auth-card pap-auth-card--single pap-auth-card--register-confirmation" data-auth-panel="register-confirmation" hidden>
            <h4><?php esc_html_e('Confirmă emailul', 'papetarie-storefront'); ?></h4>
            <p class="pap-auth-card-intro"><?php esc_html_e('Ți-am trimis un email de confirmare. Verifică inboxul și activează contul înainte de autentificare.', 'papetarie-storefront'); ?></p>
            <p class="pap-auth-card-note"><?php esc_html_e('După confirmare, te poți loga și continua comenzile în contul tău.', 'papetarie-storefront'); ?></p>
            <div class="pap-auth-social-footer">
              <span class="pap-auth-social-prefix"><?php esc_html_e('Ai deja cont?', 'papetarie-storefront'); ?></span>
              <a class="pap-auth-inline-switch pap-auth-social-switch" href="#" data-auth-switch="login"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></a>
            </div>
          </section>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ('modal' !== $context) : ?>
  <?php do_action('woocommerce_after_customer_login_form'); ?>
<?php endif; ?>
