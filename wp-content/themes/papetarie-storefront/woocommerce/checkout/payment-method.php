<?php
/**
 * Output a single payment method.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$gateway_id = strtolower((string) $gateway->id);
$gateway_title = trim(wp_strip_all_tags((string) $gateway->get_title()));
$gateway_description = trim((string) $gateway->get_description());
$is_cod_gateway = strpos($gateway_id, 'cod') !== false
	|| strpos($gateway_id, 'cash') !== false
	|| strpos($gateway_title, 'ramburs') !== false
	|| strpos($gateway_title, 'cash') !== false
	|| strpos($gateway_title, 'livrare') !== false;
$is_card_gateway = !$is_cod_gateway;
$is_bt_ipay_gateway = strpos($gateway_id, 'bt-ipay') !== false
	|| strpos($gateway_title, 'bt ipay') !== false
	|| strpos($gateway_title, 'bt-ipay') !== false
	|| strpos($gateway_title, 'btepos') !== false;

$payment_title = $is_cod_gateway
	? __('Plata la livrare', 'papetarie-storefront')
	: ($is_bt_ipay_gateway
		? __('Card bancar (BT iPay)', 'papetarie-storefront')
		: __('Card bancar', 'papetarie-storefront'));

$payment_description = $is_cod_gateway
	? __('Plătești curierului la primirea comenzii.', 'papetarie-storefront')
	: __('Plată securizată prin BT iPay.', 'papetarie-storefront');

$payment_meta = $is_cod_gateway
	? __('Fără cost suplimentar', 'papetarie-storefront')
	: __('Visa • Mastercard', 'papetarie-storefront');

$gateway_icon = $is_cod_gateway
	? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M4 7.5h10.5v6H4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14.5 10h3.2L21 13.3V16.5h-6.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7.5" cy="18" r="1.7" stroke="currentColor" stroke-width="1.6"/><circle cx="17.5" cy="18" r="1.7" stroke="currentColor" stroke-width="1.6"/><path d="M4 10h3.2M4 12.6h2.3M4 15h1.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>'
	: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9h17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M7 15.5h3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 15.5h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>';
?>
<li class="wc_payment_method payment_method_<?php echo esc_attr($gateway->id); ?> pap-checkout-payment-method<?php echo checked($gateway->chosen, true, false) ? ' is-selected' : ''; ?>">
	<input
		id="payment_method_<?php echo esc_attr($gateway->id); ?>"
		type="radio"
		class="input-radio pap-checkout-payment-method__input"
		name="payment_method"
		value="<?php echo esc_attr($gateway->id); ?>"
		<?php checked($gateway->chosen, true); ?>
		data-order_button_text="<?php echo esc_attr($gateway->order_button_text); ?>"
	/>

	<label for="payment_method_<?php echo esc_attr($gateway->id); ?>" class="pap-checkout-payment-method__card">
		<span class="pap-checkout-payment-method__icon" aria-hidden="true">
			<?php echo $gateway_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</span>

		<span class="pap-checkout-payment-method__copy">
			<span class="pap-checkout-payment-method__title"><?php echo esc_html($payment_title); ?></span>
			<span class="pap-checkout-payment-method__description">
				<?php echo esc_html($gateway_description !== '' ? $gateway_description : $payment_description); ?>
			</span>
		</span>

		<span class="pap-checkout-payment-method__meta"><?php echo esc_html($payment_meta); ?></span>
	</label>

	<?php if ($gateway->has_fields()) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr($gateway->id); ?>" <?php if (!$gateway->chosen) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
