<?php
/**
 * Review order table.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$cart = function_exists('WC') ? WC()->cart : null;
$checkout_order_button_text = apply_filters('woocommerce_order_button_text', __('Plasează comanda', 'papetarie-storefront'));

$capture_output = static function (callable $callback): string {
	ob_start();
	$callback();
	return (string) ob_get_clean();
};

$shipping_row_value = '';
$show_shipping_row = false;
$shipping_total = 0.0;

if ($cart && (function_exists('papetarie_storefront_cart_needs_shipping') ? papetarie_storefront_cart_needs_shipping() : $cart->needs_shipping())) {
	$show_shipping_row = true;
	$has_calculated_shipping = $cart->has_calculated_shipping();
	$show_shipping = $cart->show_shipping();

	if (!$has_calculated_shipping || !$show_shipping) {
		$shipping_row_value = __('Se calculează la checkout', 'papetarie-storefront');
	} else {
		$shipping_total = (float) $cart->get_shipping_total();
		if ($cart->display_prices_including_tax()) {
			$shipping_total += (float) $cart->get_shipping_tax();
		}

		$shipping_total = max(0.0, $shipping_total);
		// Aceeasi regula ca pe pagina de cos (papetarie_storefront_cart_shipping_summary_data())
		// - fara ea, transportul gratuit aparea "0,00 lei" aici dar "Transport gratuit"
		// pe cos, pentru aceeasi comanda (gasit live 2026-07-31).
		$shipping_row_value = $shipping_total > 0.0
			? papetarie_storefront_shipping_method_price_text($shipping_total)
			: __('Transport gratuit', 'papetarie-storefront');
	}
}

?>

<div class="pap-cart-summary-card pap-checkout-summary-card woocommerce-checkout-review-order-table" data-pap-checkout-section="order-summary">
	<div class="pap-checkout-summary-header">
		<div class="pap-checkout-summary-header__copy">
			<h2 class="pap-cart-summary-title"><?php esc_html_e('Total comandă', 'papetarie-storefront'); ?></h2>
			<p class="pap-checkout-summary-subtitle"><?php esc_html_e('Sumar comandă curentă', 'papetarie-storefront'); ?></p>
		</div>
	</div>

	<div class="pap-checkout-summary-card__body">
	<div class="pap-cart-totals pap-checkout-summary-totals">
		<?php ob_start(); wc_cart_totals_subtotal_html(); $subtotal_html = (string) ob_get_clean(); ?>
		<div class="pap-cart-totals-row pap-checkout-summary-row cart-subtotal">
			<span><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
			<strong><?php echo wp_kses_post($subtotal_html); ?></strong>
		</div>

		<?php
		$applied_coupons = $cart ? (array) $cart->get_applied_coupons() : [];
		$has_coupon_discount_rows = false;
		?>

		<?php foreach (($cart ? $cart->get_fees() : []) as $fee) : ?>
			<?php
			$fee_name = esc_html($fee->name);
			$fee_value = $capture_output(static function () use ($fee): void {
				wc_cart_totals_fee_html($fee);
			});
			?>
			<div class="pap-cart-totals-row fee">
				<span><?php echo $fee_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php echo wp_kses_post($fee_value); ?></strong>
			</div>
		<?php endforeach; ?>

		<?php if ($show_shipping_row) : ?>
			<div class="pap-cart-totals-row pap-cart-totals-row--shipping pap-checkout-summary-row cart-shipping">
				<span><?php esc_html_e('Transport', 'papetarie-storefront'); ?></span>
				<strong>
					<span class="pap-checkout-summary-row__shipping-price"><?php echo wp_kses_post($shipping_row_value); ?></span>
				</strong>
			</div>
		<?php endif; ?>

		<?php foreach ($applied_coupons as $coupon_code) : ?>
			<?php
			$coupon_code = trim((string) $coupon_code);
			if ($coupon_code === '' || !$cart) {
				continue;
			}

			$coupon_discount_amount = (float) $cart->get_coupon_discount_amount($coupon_code);
			if ($cart->display_prices_including_tax()) {
				$coupon_discount_amount += (float) $cart->get_coupon_discount_tax($coupon_code);
			}

			if ($coupon_discount_amount <= 0.0001) {
				continue;
			}

			$has_coupon_discount_rows = true;
			?>
			<div class="pap-cart-totals-row pap-cart-totals-row--discount pap-checkout-summary-row cart-discount coupon-<?php echo esc_attr(sanitize_title($coupon_code)); ?>" data-coupon-code="<?php echo esc_attr($coupon_code); ?>">
				<span><?php echo esc_html(sprintf(__('Reducere (%s)', 'papetarie-storefront'), wc_format_coupon_code($coupon_code))); ?></span>
				<strong><?php echo wp_kses_post(wc_price(0 - $coupon_discount_amount)); ?></strong>
			</div>
		<?php endforeach; ?>

		<?php if (!$has_coupon_discount_rows && ((float) ($cart ? $cart->get_discount_total() : 0.0) > 0.0 || (float) ($cart ? $cart->get_discount_tax() : 0.0) > 0.0)) : ?>
			<?php $discount_amount = max(0.0, (float) $cart->get_discount_total() + (float) $cart->get_discount_tax()); ?>
			<div class="pap-cart-totals-row pap-cart-totals-row--discount pap-checkout-summary-row cart-discount cart-discount--summary">
				<span><?php esc_html_e('Reducere', 'papetarie-storefront'); ?></span>
				<strong><?php echo wp_kses_post(wc_price(0 - $discount_amount)); ?></strong>
			</div>
		<?php endif; ?>

		<?php do_action('woocommerce_review_order_before_order_total'); ?>

		<?php ob_start(); wc_cart_totals_order_total_html(); $order_total_html = (string) ob_get_clean(); ?>
		<div class="pap-cart-totals-row pap-cart-totals-row--total pap-checkout-summary-row order-total">
			<span><?php esc_html_e('Total', 'woocommerce'); ?></span>
			<strong><?php echo wp_kses_post($order_total_html); ?></strong>
		</div>

		<?php do_action('woocommerce_review_order_after_order_total'); ?>
	</div>

	<div class="pap-checkout-summary-actions">
		<p class="pap-checkout-summary-security">
			<span class="pap-checkout-summary-security__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true">
					<rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
				</svg>
			</span>
			<span class="pap-checkout-summary-security__text"><?php esc_html_e('Plată securizată · SSL 256-bit', 'papetarie-storefront'); ?></span>
		</p>

		<noscript>
			<?php
			printf(
				esc_html__('Deoarece browserul tău nu suportă JavaScript sau este dezactivat, te rugăm să apeși %1$sActualizează totalurile%2$s înainte de a plasa comanda.', 'woocommerce'),
				'<em>',
				'</em>'
			);
			?>
			<br />
			<button type="submit" class="button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e('Actualizează totalurile', 'woocommerce'); ?>">
				<?php esc_html_e('Actualizează totalurile', 'woocommerce'); ?>
			</button>
		</noscript>

		<?php wc_get_template('checkout/terms.php'); ?>

		<?php do_action('woocommerce_review_order_before_submit'); ?>

		<?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="button alt pap-cart-checkout pap-checkout-action pap-checkout-action--primary is-disabled' . esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '') . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($checkout_order_button_text) . '" data-value="' . esc_attr($checkout_order_button_text) . '" disabled="disabled" aria-disabled="true">' . esc_html($checkout_order_button_text) . '</button>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php do_action('woocommerce_review_order_after_submit'); ?>

		<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
	</div>
	</div>

</div>
