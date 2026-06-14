<?php
/**
 * Reusable lost password shell.
 *
 * @package papetarie-storefront
 */

defined('ABSPATH') || exit;

$args = wp_parse_args(
    is_array($args ?? null) ? $args : [],
    [
        'context' => 'page',
        'show_visual' => true,
        'id_prefix' => 'pap-auth-lost-password-',
        'hidden' => false,
    ]
);

$context = sanitize_key((string) $args['context']);
$show_visual = !empty($args['show_visual']);
$id_prefix = sanitize_key((string) $args['id_prefix']);
$is_hidden = !empty($args['hidden']);

if ('' === $id_prefix) {
    $id_prefix = 'pap-auth-' . $context . '-lost-';
}

$shell_classes = [
    'pap-auth-shell',
    'pap-auth-shell--lost-password',
    'pap-auth-shell--' . $context,
];

if (!$show_visual) {
    $shell_classes[] = 'pap-auth-shell--modal';
}

if ('modal' !== $context) {
    do_action('woocommerce_before_lost_password_form');
}
?>

<div class="<?php echo esc_attr(implode(' ', $shell_classes)); ?>" data-auth-root data-auth-view="lost-password" data-auth-context="<?php echo esc_attr($context); ?>"<?php echo $is_hidden ? ' hidden' : ''; ?>>
  <div class="pap-shell pap-auth-shell-inner">
    <?php if ($show_visual) : ?>
      <?php papetarie_storefront_render_auth_hero('lost-password'); ?>
    <?php endif; ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>

      <section class="pap-auth-card pap-auth-card--single">
        <h4><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></h4>
        <p class="pap-auth-card-intro pap-auth-card-intro--compact"><?php esc_html_e('Completează câmpul de mai jos și îți trimitem linkul de resetare.', 'papetarie-storefront'); ?></p>
        <form method="post" class="woocommerce-ResetPassword lost_reset_password pap-auth-form" data-auth-form="lost-password" novalidate>
          <fieldset class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
            <label for="<?php echo esc_attr($id_prefix . 'user_login'); ?>"><?php esc_html_e('Email', 'papetarie-storefront'); ?></label>
            <span class="pap-auth-input-field pap-auth-input-field--email">
              <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
              <input class="woocommerce-Input woocommerce-Input--text input-text" type="email" name="user_login" id="<?php echo esc_attr($id_prefix . 'user_login'); ?>" autocomplete="email" placeholder="<?php esc_attr_e('Email', 'papetarie-storefront'); ?>" required aria-required="true" />
            </span>
          </fieldset>

          <?php do_action('woocommerce_lostpassword_form'); ?>

          <p class="form-row pap-auth-form-actions">
            <input type="hidden" name="wc_reset_password" value="true" />
            <button type="submit" class="woocommerce-Button button" value="<?php esc_attr_e('Resetare parolă', 'papetarie-storefront'); ?>"><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></button>
          </p>

          <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>

          <div class="pap-auth-social-footer pap-auth-social-footer--center">
            <?php if ('modal' === $context) : ?>
              <span class="pap-auth-social-prefix"><?php esc_html_e('Ți-ai amintit parola?', 'papetarie-storefront'); ?></span>
              <a class="pap-auth-inline-switch pap-auth-social-switch" href="#" data-auth-switch="login"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></a>
            <?php else : ?>
              <span class="pap-auth-social-prefix"><?php esc_html_e('Ți-ai amintit parola?', 'papetarie-storefront'); ?></span>
              <a class="pap-auth-inline-switch pap-auth-social-switch" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/')); ?>"><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></a>
            <?php endif; ?>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>

<?php if ('modal' !== $context) : ?>
  <?php do_action('woocommerce_after_lost_password_form'); ?>
<?php endif; ?>
