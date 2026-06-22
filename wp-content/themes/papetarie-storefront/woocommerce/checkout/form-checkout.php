<?php
/**
 * Checkout Form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>

<?php
$pap_checkout_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$pap_checkout_redirect_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/');
$pap_checkout_user = wp_get_current_user();
$pap_checkout_user_name = $pap_checkout_user instanceof WP_User && !empty($pap_checkout_user->display_name) ? $pap_checkout_user->display_name : '';
?>

<div class="pap-shell pap-checkout-page-shell">
  <div class="pap-checkout-shell">
    <header class="pap-checkout-hero">
      <h1><?php esc_html_e('Finalizează comanda', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Completează datele de livrare și confirmă comanda în pasul final.', 'papetarie-storefront'); ?></p>
    </header>

    <section class="pap-checkout-context <?php echo is_user_logged_in() ? 'pap-checkout-context--logged-in' : 'pap-checkout-context--guest'; ?>" aria-label="<?php esc_attr_e('Context checkout', 'papetarie-storefront'); ?>">
      <div class="pap-checkout-context__copy">
        <p class="pap-checkout-context__text">
          <?php if (is_user_logged_in()) : ?>
            <?php
            printf(
                esc_html__('Ești autentificat%s. Datele de facturare sunt precompletate și le poți ajusta înainte de finalizare.', 'papetarie-storefront'),
                $pap_checkout_user_name !== '' ? ' ca ' . esc_html($pap_checkout_user_name) : ''
            );
            ?>
          <?php else : ?>
            <?php esc_html_e('Poți finaliza comanda fără cont. Dacă ai deja un cont, autentificarea aduce datele salvate și reduce completarea manuală.', 'papetarie-storefront'); ?>
          <?php endif; ?>
        </p>
        <p class="pap-checkout-context__meta">
          <?php esc_html_e('Facturare clară, livrare flexibilă și un pas final de comandă simplu.', 'papetarie-storefront'); ?>
        </p>
      </div>
      <div class="pap-checkout-context__actions">
        <?php if (is_user_logged_in()) : ?>
          <a class="pap-checkout-context__link" href="<?php echo esc_url($pap_checkout_account_url); ?>">
            <?php esc_html_e('Vezi contul', 'papetarie-storefront'); ?>
          </a>
        <?php else : ?>
          <a class="button pap-checkout-context__button" href="<?php echo esc_url(add_query_arg('redirect_to', $pap_checkout_redirect_url, $pap_checkout_account_url)); ?>">
            <?php esc_html_e('Autentificare', 'papetarie-storefront'); ?>
          </a>
        <?php endif; ?>
      </div>
    </section>

    <?php if (function_exists('wc_notice_count') && wc_notice_count()) : ?>
      <div class="pap-checkout-notices woocommerce-notices-wrapper" role="status" aria-live="polite">
        <?php woocommerce_output_all_notices(); ?>
      </div>
    <?php endif; ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout pap-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
      <?php if ($checkout->get_checkout_fields()) : ?>
        <?php do_action('woocommerce_checkout_before_customer_details'); ?>

        <div class="pap-checkout-layout">
          <main id="customer_details" class="pap-checkout-main" aria-label="<?php esc_attr_e('Informații client', 'papetarie-storefront'); ?>">
            <?php echo papetarie_storefront_get_checkout_contact_card_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo papetarie_storefront_get_checkout_shipping_methods_card_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php wc_get_template('checkout/form-shipping.php', ['checkout' => $checkout]); ?>
            <?php echo papetarie_storefront_get_checkout_address_summary_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo papetarie_storefront_get_checkout_payment_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </main>

          <aside class="pap-checkout-sidebar" aria-label="<?php esc_attr_e('Rezumat comandă', 'papetarie-storefront'); ?>">
            <div class="pap-checkout-sidebar__sticky">
              <?php do_action('woocommerce_checkout_before_order_review'); ?>
              <div id="order_review" class="woocommerce-checkout-review-order">
                <?php papetarie_storefront_render_checkout_order_review(); ?>
              </div>
              <?php do_action('woocommerce_checkout_after_order_review'); ?>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php echo papetarie_storefront_get_checkout_address_modal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
