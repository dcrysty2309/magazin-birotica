<?php
/**
 * Checkout shipping information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shipping_fields = $checkout->get_checkout_fields('shipping');
$billing_fields = $checkout->get_checkout_fields('billing');

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

$render_fields = static function (array $field_keys, string $context) use ($shipping_fields, $billing_fields, $checkout): string {
    $html = '';
    $fields = 'shipping' === $context ? $shipping_fields : $billing_fields;

    foreach ($field_keys as $key) {
        if (!isset($fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
            continue;
        }

        $html .= papetarie_storefront_render_checkout_form_field($key, $fields[$key], $checkout->get_value($key), false);
    }

    return $html;
};

$is_guest_checkout = !is_user_logged_in();
$shipping_address_mode = function_exists('papetarie_storefront_checkout_shipping_address_mode')
    ? papetarie_storefront_checkout_shipping_address_mode()
    : 'edit';
$shipping_address_is_summary = 'summary' === $shipping_address_mode;
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
			<h2><?php esc_html_e('Adresă de livrare', 'papetarie-storefront'); ?></h2>
		</div>
	</div>

	<?php if ($is_guest_checkout) : ?>
		<div class="pap-checkout-guest-shipping">
			<div class="pap-checkout-guest-shipping__form" data-pap-guest-shipping-form>
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

					<div class="pap-form-row pap-form-row--stack">
						<?php echo $render_shipping_field('shipping_address_1'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<div class="pap-form-row pap-form-row--stack">
						<?php echo $render_shipping_field('shipping_address_2'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<input type="hidden" name="pap_guest_shipping_snapshot" value="" data-pap-guest-shipping-snapshot>
					<input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
					<input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
				</div>

				<div class="pap-checkout-guest-shipping__summary" data-pap-guest-shipping-summary hidden aria-hidden="true">
					<?php echo function_exists('papetarie_storefront_get_checkout_guest_shipping_summary_html') ? papetarie_storefront_get_checkout_guest_shipping_summary_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php if ($checkout->is_registration_enabled() && !is_user_logged_in()) : ?>
					<div class="pap-checkout-guest-shipping__options">
						<label class="pap-checkout-guest-option">
							<input type="checkbox" name="createaccount" value="1" <?php checked($checkout->get_value('createaccount'), 1); ?>>
							<span><?php esc_html_e('Creează cont după finalizarea comenzii', 'papetarie-storefront'); ?></span>
						</label>
					</div>
				<?php endif; ?>

				<div class="pap-checkout-guest-shipping__actions">
					<button type="button" class="button alt pap-cart-checkout pap-checkout-guest-shipping__continue" data-pap-guest-shipping-continue>
						<?php esc_html_e('Continuă către livrare', 'papetarie-storefront'); ?>
					</button>
				</div>

				<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
			</div>
		</div>
	<?php else : ?>
		<div class="woocommerce-shipping-fields">
			<?php if (true === WC()->cart->needs_shipping_address()) : ?>
				<h3 id="ship-to-different-address">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e('Livrare la o adresa diferita?', 'papetarie-storefront'); ?></span>
					</label>
				</h3>

				<div class="shipping_address">
					<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

					<div class="pap-auth-form pap-checkout-address-form">
						<div class="pap-form-row pap-form-row--split">
							<?php echo $render_fields(['billing_first_name', 'billing_last_name'], 'billing'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="pap-form-row pap-form-row--split">
							<?php echo $render_fields(['billing_email', 'billing_phone'], 'billing'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="pap-form-row pap-form-row--split">
							<?php echo $render_fields(['shipping_state', 'shipping_city'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="pap-form-row pap-form-row--stack">
							<?php echo $render_fields(['shipping_address_1'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="pap-form-row pap-form-row--stack">
							<?php echo $render_fields(['shipping_address_2'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
						<input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
					</div>

					<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
