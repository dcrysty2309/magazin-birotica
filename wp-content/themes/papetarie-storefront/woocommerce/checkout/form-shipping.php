<?php
/**
 * Checkout shipping information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shipping_fields = $checkout->get_checkout_fields('shipping');
$billing_fields = $checkout->get_checkout_fields('billing');

$render_shipping_fields = static function (array $field_keys) use ($shipping_fields, $checkout): void {
    foreach ($field_keys as $key) {
        if (!isset($shipping_fields[$key])) {
            continue;
        }

        woocommerce_form_field($key, $shipping_fields[$key], $checkout->get_value($key));
    }
};

$render_billing_fields = static function (array $field_keys) use ($billing_fields, $checkout): void {
    foreach ($field_keys as $key) {
        if (!isset($billing_fields[$key])) {
            continue;
        }

        woocommerce_form_field($key, $billing_fields[$key], $checkout->get_value($key));
    }
};
?>

<section class="pap-checkout-card pap-checkout-card--shipping-address" data-pap-checkout-section="shipping-address">
	<div class="pap-checkout-card__head">
		<div class="pap-checkout-section-title-row">
			<span class="pap-checkout-section-badge" aria-hidden="true">1</span>
			<h2><?php esc_html_e('Adresă de livrare', 'papetarie-storefront'); ?></h2>
		</div>
	</div>

	<div class="woocommerce-shipping-fields">
		<?php if (true === WC()->cart->needs_shipping_address()) : ?>
			<h3 id="ship-to-different-address">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e('Livrare la o adresa diferita?', 'papetarie-storefront'); ?></span>
				</label>
			</h3>

			<div class="shipping_address">
				<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

				<div class="pap-checkout-section__fields pap-checkout-section__fields--contact">
					<?php $render_billing_fields(['billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone']); ?>
				</div>

				<div class="pap-checkout-section__fields pap-checkout-section__fields--shipping">
					<?php $render_shipping_fields(['shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_postcode', 'shipping_address_1', 'shipping_address_2']); ?>
				</div>

				<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
			</div>
		<?php endif; ?>
	</div>
</section>