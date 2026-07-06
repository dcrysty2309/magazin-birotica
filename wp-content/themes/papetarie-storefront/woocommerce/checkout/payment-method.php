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

$payment_meta = '';

$gateway_icon = $is_cod_gateway
	? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect x="3.5" y="6.6" width="12.8" height="10.8" rx="1.7" stroke="currentColor" stroke-width="1.2"/><path d="M6.3 9.1h7.2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><circle cx="17.8" cy="12.2" r="2.8" stroke="currentColor" stroke-width="1.2"/><path d="M17.8 11v2.4M16.6 12.2H19" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>'
	: '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="14" rx="2" stroke="currentColor" stroke-width="1.2"/><path d="M3.5 9h17" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M7 15.5h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/><path d="M12 15.5h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>';

$gateway_meta_visual = '';
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

	</label>

	<?php if ($gateway->has_fields()) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr($gateway->id); ?>" <?php if (!$gateway->chosen) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
