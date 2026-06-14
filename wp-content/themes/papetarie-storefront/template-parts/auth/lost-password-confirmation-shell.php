<?php
/**
 * Reusable lost password confirmation shell.
 *
 * @package papetarie-storefront
 */

defined('ABSPATH') || exit;

$args = wp_parse_args(
    is_array($args ?? null) ? $args : [],
    [
        'context' => 'page',
        'show_visual' => true,
        'hidden' => false,
    ]
);

$context = sanitize_key((string) $args['context']);
$show_visual = !empty($args['show_visual']);
$is_hidden = !empty($args['hidden']);

$shell_classes = [
    'pap-auth-shell',
    'pap-auth-shell--lost-password',
    'pap-auth-shell--lost-password-confirmation',
    'pap-auth-shell--' . $context,
];

if (!$show_visual) {
    $shell_classes[] = 'pap-auth-shell--modal';
}

if ('modal' !== $context) {
    do_action('woocommerce_before_lost_password_confirmation_message');
}

papetarie_storefront_store_auth_notice(__('Un email a fost trimis cu succes. Verifică inboxul.', 'papetarie-storefront'), 'success');
?>

<div class="<?php echo esc_attr(implode(' ', $shell_classes)); ?>" data-auth-root data-auth-view="lost-password-confirmation" data-auth-context="<?php echo esc_attr($context); ?>"<?php echo $is_hidden ? ' hidden' : ''; ?>>
  <div class="pap-shell pap-auth-shell-inner">
    <?php if ($show_visual) : ?>
      <?php papetarie_storefront_render_auth_hero('lost-password'); ?>
    <?php endif; ?>

    <div class="pap-auth-panel">
      <?php papetarie_storefront_render_auth_notices(); ?>

      <section class="pap-auth-card pap-auth-card--single">
        <h4><?php esc_html_e('Resetare parolă', 'papetarie-storefront'); ?></h4>
        <p class="pap-auth-card-intro"><?php esc_html_e('Verifică inboxul pentru linkul de resetare și urmează pașii din mesaj.', 'papetarie-storefront'); ?></p>
        <p class="pap-auth-card-note"><?php esc_html_e('Poți reveni oricând la autentificare și continua de unde ai rămas.', 'papetarie-storefront'); ?></p>

        <div class="pap-auth-form-actions pap-auth-form-actions--single">
          <?php if ('modal' === $context) : ?>
            <button class="woocommerce-button button pap-auth-back-link pap-auth-inline-switch" type="button" data-auth-switch="login">
              <span class="pap-auth-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                  <path d="M14.5 5 8.5 11l6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span><?php esc_html_e('Înapoi la autentificare', 'papetarie-storefront'); ?></span>
            </button>
          <?php else : ?>
            <a class="woocommerce-button button pap-auth-back-link" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/')); ?>">
              <span class="pap-auth-back-link-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                  <path d="M14.5 5 8.5 11l6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span><?php esc_html_e('Înapoi la autentificare', 'papetarie-storefront'); ?></span>
            </a>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </div>
</div>

<?php if ('modal' !== $context) : ?>
  <?php do_action('woocommerce_after_lost_password_confirmation_message'); ?>
<?php endif; ?>
