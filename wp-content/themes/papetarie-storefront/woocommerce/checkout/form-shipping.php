<?php
/**
 * Checkout shipping information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shipping_fields = $checkout->get_checkout_fields('shipping');

$render_shipping_fields = static function (array $field_keys) use ($shipping_fields, $checkout): void {
    foreach ($field_keys as $key) {
        if (!isset($shipping_fields[$key])) {
            continue;
        }

        woocommerce_form_field($key, $shipping_fields[$key], $checkout->get_value($key));
    }
};

?>

<section class="pap-checkout-section pap-checkout-section--shipping">
	<div class="pap-checkout-section__head">
		<h2><?php esc_html_e('Date de livrare', 'papetarie-storefront'); ?></h2>
		<p class="pap-checkout-card__intro"><?php esc_html_e('Livrarea standard folosește adresa introdusă mai jos.', 'papetarie-storefront'); ?></p>
	</div>

	<div class="woocommerce-shipping-fields">
		<?php if (true === WC()->cart->needs_shipping_address()) : ?>
			<div class="shipping_address">
				<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

				<div class="pap-checkout-section__fields pap-checkout-section__fields--shipping">
					<?php $render_shipping_fields(['shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_postcode', 'shipping_address_1', 'shipping_address_2']); ?>
				</div>

				<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
			</div>
		<?php else : ?>
			<p class="pap-checkout-card__intro">
				<?php esc_html_e('Comanda nu necesită o adresă de livrare separată. Rezumatul rămâne actualizat în dreapta.', 'papetarie-storefront'); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
