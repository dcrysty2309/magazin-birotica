<?php
/**
 * Checkout shipping information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shipping_fields = $checkout->get_checkout_fields('shipping');
$billing_fields = $checkout->get_checkout_fields('billing');
$order_fields = $checkout->get_checkout_fields('order');

$render_shipping_field = static function (string $key) use ($shipping_fields, $checkout): string {
    if (!isset($shipping_fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
        return '';
    }

    return papetarie_storefront_render_checkout_form_field($key, $shipping_fields[$key], $checkout->get_value($key), false);
};

$render_billing_field = static function (string $key) use ($billing_fields, $checkout): string {
    if (!isset($billing_fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
        return '';
    }

    return papetarie_storefront_render_checkout_form_field($key, $billing_fields[$key], $checkout->get_value($key), false);
};

$render_order_field = static function (string $key) use ($order_fields, $checkout): string {
    if (!isset($order_fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
        return '';
    }

    return papetarie_storefront_render_checkout_form_field($key, $order_fields[$key], $checkout->get_value($key), false);
};

$is_guest_checkout = !is_user_logged_in();
$shipping_address_mode = function_exists('papetarie_storefront_checkout_shipping_address_mode')
    ? papetarie_storefront_checkout_shipping_address_mode()
    : 'edit';
$shipping_mode_cookie_present = isset($_COOKIE['pap_checkout_shipping_mode']);
$has_standard_address = function_exists('papetarie_storefront_checkout_standard_address_has_content')
    ? papetarie_storefront_checkout_standard_address_has_content()
    : false;
$shipping_address_is_summary = $is_guest_checkout
    ? ('summary' === $shipping_address_mode)
    : ($has_standard_address && (!$shipping_mode_cookie_present || 'summary' === $shipping_address_mode));
$show_form = !$shipping_address_is_summary;

?>

<section
    class="pap-checkout-card pap-checkout-card--shipping-address pap-checkout-step pap-checkout-step--shipping-address<?php echo $shipping_address_is_summary ? ' is-summary-mode' : ''; ?> is-step-<?php echo esc_attr(function_exists('papetarie_storefront_checkout_step_state') ? papetarie_storefront_checkout_step_state('shipping-address') : 'active'); ?>"
    data-pap-checkout-section="shipping-address"
    data-pap-checkout-step="shipping-address"
    data-pap-step-state="<?php echo esc_attr(function_exists('papetarie_storefront_checkout_step_state') ? papetarie_storefront_checkout_step_state('shipping-address') : 'active'); ?>"
    data-pap-guest-shipping-mode="<?php echo esc_attr($shipping_address_mode); ?>"
>
    <div class="pap-checkout-card__head">
        <div class="pap-checkout-section-title-row">
            <span class="pap-checkout-section-badge" aria-hidden="true">1</span>
            <h2><?php esc_html_e('Adresa de livrare', 'papetarie-storefront'); ?></h2>
        </div>
    </div>

    <?php if ($is_guest_checkout) : ?>
        <div class="pap-checkout-guest-shipping">
            <div class="pap-checkout-guest-shipping__form" data-pap-guest-shipping-form <?php echo $show_form ? '' : 'hidden aria-hidden="true"'; ?>>
                <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

                <div class="pap-auth-form pap-checkout-address-form">
                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_first_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_last_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_email'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_shipping_field('shipping_state'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_shipping_field('shipping_city'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_shipping_field('shipping_address_1'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_shipping_field('shipping_postcode'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--stack">
                        <?php echo $render_order_field('order_comments'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <input type="hidden" name="pap_guest_shipping_snapshot" value="" data-pap-guest-shipping-snapshot>
                    <input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
                    <input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
                </div>
            </div>

            <div class="pap-checkout-guest-shipping__summary pap-checkout-guest-shipping__summary--selected" data-pap-guest-shipping-summary <?php echo $shipping_address_is_summary ? '' : 'hidden'; ?> aria-hidden="<?php echo $shipping_address_is_summary ? 'false' : 'true'; ?>">
                <?php echo function_exists('papetarie_storefront_get_checkout_guest_shipping_summary_html') ? papetarie_storefront_get_checkout_guest_shipping_summary_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <div class="pap-checkout-guest-shipping__actions pap-checkout-action-row" <?php echo $shipping_address_is_summary ? 'hidden' : ''; ?>>
                <button type="button" class="button alt pap-cart-checkout pap-checkout-action pap-checkout-action--primary pap-checkout-guest-shipping__continue" data-pap-guest-shipping-continue>
                    <?php esc_html_e('Continuă', 'papetarie-storefront'); ?>
                </button>
            </div>

            <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
        </div>
    <?php else : ?>
        <div class="pap-checkout-auth-shipping" data-pap-auth-shipping data-pap-auth-temporary-mode="<?php echo $shipping_address_is_summary ? 'summary' : 'form'; ?>">
            <div class="pap-checkout-auth-shipping__summary pap-checkout-guest-shipping__summary" data-pap-auth-shipping-summary <?php echo $shipping_address_is_summary ? '' : 'hidden'; ?> aria-hidden="<?php echo $shipping_address_is_summary ? 'false' : 'true'; ?>">
                <?php echo function_exists('papetarie_storefront_get_checkout_auth_shipping_summary_html') ? papetarie_storefront_get_checkout_auth_shipping_summary_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <div class="pap-checkout-auth-shipping__form" data-pap-auth-shipping-form <?php echo $shipping_address_is_summary ? 'hidden' : ''; ?>>
                <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

                <div class="pap-auth-form pap-checkout-address-form">
                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_first_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_last_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_email'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_shipping_field('shipping_state'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_shipping_field('shipping_city'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_shipping_field('shipping_address_1'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_shipping_field('shipping_postcode'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--stack">
                        <?php echo $render_order_field('order_comments'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <label class="pap-checkout-save-address">
                        <input type="checkbox" name="pap_save_address_for_future" value="1">
                        <span><?php esc_html_e('Actualizează adresa din contul meu cu aceste date', 'papetarie-storefront'); ?></span>
                    </label>

                    <input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
                    <input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
                </div>

                <div class="pap-checkout-auth-shipping__actions pap-checkout-action-row" data-pap-auth-shipping-actions>
                    <button type="button" class="button pap-checkout-action pap-checkout-action--secondary pap-checkout-guest-shipping__edit" data-pap-auth-address-cancel>
                        <?php esc_html_e('Înapoi la adrese', 'papetarie-storefront'); ?>
                    </button>
                    <button type="button" class="button alt pap-cart-checkout pap-checkout-action pap-checkout-action--primary pap-checkout-guest-shipping__continue" data-pap-auth-address-save>
                        <?php esc_html_e('Continuă', 'papetarie-storefront'); ?>
                    </button>
                </div>

                <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
            </div>
        </div>
    <?php endif; ?>
</section>
