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
$shipping_row_label = '';
$show_shipping_row = false;
$show_tax_row = false;
$shipping_total = 0.0;
$tax_total = $cart ? (float) $cart->get_total_tax() : 0.0;

if ($cart && (function_exists('papetarie_storefront_cart_needs_shipping') ? papetarie_storefront_cart_needs_shipping() : $cart->needs_shipping())) {
	$show_shipping_row = true;
	$has_calculated_shipping = $cart->has_calculated_shipping();
	$show_shipping = $cart->show_shipping();

	if (!$has_calculated_shipping || !$show_shipping) {
		$shipping_row_label = __('Se calculează la checkout', 'papetarie-storefront');
	} else {
		$shipping_total = (float) $cart->get_shipping_total();
		if ($cart->display_prices_including_tax()) {
			$shipping_total += (float) $cart->get_shipping_tax();
		}

		$packages = function_exists('WC') && WC()->shipping() ? WC()->shipping()->get_packages() : [];
		foreach ($packages as $package_index => $package) {
			$chosen_method = function_exists('wc_get_chosen_shipping_method_for_package') ? (string) wc_get_chosen_shipping_method_for_package($package_index, $package) : '';
			if ($chosen_method === '' || empty($package['rates'][$chosen_method])) {
				continue;
			}

			$rate = $package['rates'][$chosen_method];
			$shipping_row_label = papetarie_storefront_shipping_method_label_text($rate);
			break;
		}

		if ($shipping_row_label === '') {
			$shipping_row_label = __('Livrare', 'papetarie-storefront');
		}

		$shipping_row_value = papetarie_storefront_shipping_method_price_text(max(0.0, $shipping_total));
	}
}

if ($cart && function_exists('wc_tax_enabled') && wc_tax_enabled()) {
	$show_tax_row = true;
}
?>

<div class="pap-checkout-card pap-checkout-card--summary woocommerce-checkout-review-order-table" data-pap-checkout-section="order-summary">
	<div class="pap-checkout-summary-header">
		<div class="pap-checkout-summary-header__copy">
			<h2><?php esc_html_e('Total comandă', 'papetarie-storefront'); ?></h2>
			<p class="pap-checkout-summary-header__meta"><?php esc_html_e('Produsele sunt afișate în coloana principală, iar aici vezi doar totalurile finale.', 'papetarie-storefront'); ?></p>
		</div>
	</div>

	<div class="pap-checkout-summary-totals">
		<?php ob_start(); wc_cart_totals_subtotal_html(); $subtotal_html = (string) ob_get_clean(); ?>
		<div class="pap-checkout-summary-row cart-subtotal">
			<span><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
			<strong><?php echo wp_kses_post($subtotal_html); ?></strong>
		</div>

		<?php foreach (($cart ? $cart->get_coupons() : []) as $code => $coupon) : ?>
			<?php
			$coupon_label = $capture_output(static function () use ($coupon): void {
				wc_cart_totals_coupon_label($coupon);
			});
			$coupon_value = $capture_output(static function () use ($coupon): void {
				wc_cart_totals_coupon_html($coupon);
			});
			?>
			<div class="pap-checkout-summary-row cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
				<span><?php echo esc_html(wp_strip_all_tags($coupon_label)); ?></span>
				<strong><?php echo wp_kses_post($coupon_value); ?></strong>
			</div>
		<?php endforeach; ?>

		<?php foreach (($cart ? $cart->get_fees() : []) as $fee) : ?>
			<?php
			$fee_name = esc_html($fee->name);
			$fee_value = $capture_output(static function () use ($fee): void {
				wc_cart_totals_fee_html($fee);
			});
			?>
			<div class="pap-checkout-summary-row fee">
				<span><?php echo $fee_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<strong><?php echo wp_kses_post($fee_value); ?></strong>
			</div>
		<?php endforeach; ?>

		<?php if ($show_shipping_row) : ?>
			<div class="pap-checkout-summary-row cart-shipping">
				<span><?php esc_html_e('Livrare', 'papetarie-storefront'); ?></span>
				<strong>
					<span class="pap-checkout-summary-row__shipping-label"><?php echo esc_html($shipping_row_label); ?></span>
					<span class="pap-checkout-summary-row__shipping-price"><?php echo wp_kses_post($shipping_row_value); ?></span>
				</strong>
			</div>
		<?php endif; ?>

		<?php if ($show_tax_row) : ?>
			<div class="pap-checkout-summary-row tax-total">
				<span><?php esc_html_e('TVA', 'papetarie-storefront'); ?></span>
				<strong><?php echo wp_kses_post(wc_price(max(0.0, $tax_total))); ?></strong>
			</div>
		<?php endif; ?>

		<?php do_action('woocommerce_review_order_before_order_total'); ?>

		<?php ob_start(); wc_cart_totals_order_total_html(); $order_total_html = (string) ob_get_clean(); ?>
		<div class="pap-checkout-summary-row order-total">
			<span><?php esc_html_e('Total', 'woocommerce'); ?></span>
			<strong><?php echo wp_kses_post($order_total_html); ?></strong>
		</div>

		<?php do_action('woocommerce_review_order_after_order_total'); ?>
	</div>

	<div class="pap-checkout-summary-trust" aria-label="<?php esc_attr_e('Avantaje checkout', 'papetarie-storefront'); ?>">
		<span><?php esc_html_e('Plată sigură', 'papetarie-storefront'); ?></span>
		<span><?php esc_html_e('Livrare rapidă', 'papetarie-storefront'); ?></span>
		<span><?php esc_html_e('Suport dedicat', 'papetarie-storefront'); ?></span>
	</div>

	<div class="pap-checkout-summary-actions">
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

		<?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="button alt pap-cart-checkout pap-checkout-action pap-checkout-action--primary' . esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '') . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($checkout_order_button_text) . '" data-value="' . esc_attr($checkout_order_button_text) . '">' . esc_html($checkout_order_button_text) . '</button>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php do_action('woocommerce_review_order_after_submit'); ?>

		<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
	</div>

</div>
