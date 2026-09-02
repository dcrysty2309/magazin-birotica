<?php
/**
 * Thankyou page.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shop_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
$shop_url = $shop_page_id > 0 && function_exists('wc_get_page_permalink')
	? wc_get_page_permalink('shop')
	: home_url('/');
$my_account_orders_url = function_exists('wc_get_endpoint_url') && function_exists('wc_get_page_permalink')
	? wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))
	: home_url('/my-account/orders/');

$success_icon = '<svg viewBox="0 0 64 64" fill="none" aria-hidden="true" focusable="false"><circle cx="32" cy="32" r="30" fill="#ECF8EE" stroke="#C8E9D0" stroke-width="2"/><path d="M22.5 32.6 29.1 39.2 42.4 25.8" stroke="#2F8F4E" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$document_icon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M7 3.75h7.7L18.75 7.8V20.25H7A1.75 1.75 0 0 1 5.25 18.5V5.5A1.75 1.75 0 0 1 7 3.75Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M14.7 3.75v4.1h4.05" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M8.8 10.2h6.4M8.8 13.6h6.4M8.8 17h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
$calendar_icon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect x="4" y="5" width="16" height="15" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M7.5 3.75v3.1M16.5 3.75v3.1M4 9h16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M8.25 12h.01M12 12h.01M15.75 12h.01M8.25 15.5h.01M12 15.5h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>';
$mail_icon = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M5.5 8l6.5 4.8L18.5 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

?>

<main class="pap-shell pap-order-received">
	<section class="pap-order-received__hero">
		<div class="pap-order-received__status" aria-hidden="true">
			<?php echo $success_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<h1><?php esc_html_e('Comanda a fost înregistrată cu succes!', 'papetarie-storefront'); ?></h1>
		<p><?php esc_html_e('Îți mulțumim. Am primit comanda ta și o vom procesa în cel mai scurt timp.', 'papetarie-storefront'); ?></p>
	</section>

	<?php if ($order) : ?>
		<section class="pap-order-received__stats" aria-label="<?php esc_attr_e('Detaliile comenzii', 'papetarie-storefront'); ?>">
			<article class="pap-order-received__stat">
				<div class="pap-order-received__stat-icon" aria-hidden="true"><?php echo $document_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="pap-order-received__stat-copy">
					<span class="pap-order-received__stat-label"><?php esc_html_e('Număr comandă', 'papetarie-storefront'); ?></span>
					<strong class="pap-order-received__stat-value"><?php echo esc_html('#' . $order->get_order_number()); ?></strong>
				</div>
			</article>

			<article class="pap-order-received__stat">
				<div class="pap-order-received__stat-icon" aria-hidden="true"><?php echo $calendar_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="pap-order-received__stat-copy">
					<span class="pap-order-received__stat-label"><?php esc_html_e('Data comenzii', 'papetarie-storefront'); ?></span>
					<strong class="pap-order-received__stat-value"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
				</div>
			</article>
		</section>

		<p class="pap-order-received__email">
			<span class="pap-order-received__email-icon" aria-hidden="true"><?php echo $mail_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span>
				<?php
				printf(
					esc_html__('Îți vom trimite un email cu detaliile comenzii la adresa %s.', 'papetarie-storefront'),
					'<strong>' . esc_html($order->get_billing_email()) . '</strong>'
				);
				?>
			</span>
		</p>

		<div class="pap-order-received__actions">
			<a class="pap-button--secondary pap-order-received__button" href="<?php echo esc_url($shop_url); ?>">
				<?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?>
			</a>

			<?php if (is_user_logged_in()) : ?>
				<a class="pap-order-received__orders-link" href="<?php echo esc_url($my_account_orders_url); ?>">
					<?php esc_html_e('Vezi comenzile mele', 'papetarie-storefront'); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<section class="pap-order-received__card pap-order-received__card--empty">
			<p><?php esc_html_e('Nu am putut găsi comanda. Te rugăm să revii în magazin și să încerci din nou.', 'papetarie-storefront'); ?></p>
			<a class="pap-button--secondary pap-order-received__button" href="<?php echo esc_url($shop_url); ?>">
				<?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?>
			</a>
		</section>
	<?php endif; ?>

	<?php if ($order) : ?>
		<script>
			(function () {
				// Eveniment de conversie GA4 ("purchase") - singurul loc din care
				// pleaca aici, fiindca templateul de multumire al temei nu mai
				// apeleaza hook-ul standard WooCommerce "woocommerce_thankyou"
				// (suprascris integral, fara acel do_action) - un handler agatat pe
				// hook-ul lipsa nu ar fi rulat niciodata. Deduplicare prin
				// sessionStorage: pagina de multumire poate fi reincarcata de user
				// (F5, buton "inapoi") - fara aceasta verificare, fiecare refresh ar
				// trimite din nou aceeasi comanda catre Analytics, umflând artificial
				// veniturile raportate. Cerut de user 2026-09-01.
				const orderKey = 'pap_ga4_purchase_<?php echo esc_js((string) $order->get_id()); ?>';
				if (sessionStorage.getItem(orderKey)) {
					return;
				}
				sessionStorage.setItem(orderKey, '1');

				if (typeof gtag !== 'function') { return; }

				gtag('event', 'purchase', {
					transaction_id: <?php echo wp_json_encode($order->get_order_number()); ?>,
					value: <?php echo wp_json_encode((float) $order->get_total()); ?>,
					tax: <?php echo wp_json_encode((float) $order->get_total_tax()); ?>,
					shipping: <?php echo wp_json_encode((float) $order->get_shipping_total()); ?>,
					currency: <?php echo wp_json_encode($order->get_currency()); ?>,
					items: <?php
					$pap_ga4_items = [];
					foreach ($order->get_items() as $pap_ga4_item) {
						$pap_ga4_product = $pap_ga4_item->get_product();
						$pap_ga4_items[] = [
							'item_id' => $pap_ga4_product instanceof WC_Product ? $pap_ga4_product->get_sku() : (string) $pap_ga4_item->get_product_id(),
							'item_name' => $pap_ga4_item->get_name(),
							'price' => (float) $order->get_item_total($pap_ga4_item, false, false),
							'quantity' => (int) $pap_ga4_item->get_quantity(),
						];
					}
					echo wp_json_encode($pap_ga4_items);
					?>,
				});
			}());
		</script>
	<?php endif; ?>

	<?php $pap_order_received_phone = function_exists('papetarie_storefront_get_checkout_support_details') ? papetarie_storefront_get_checkout_support_details()['phone'] : ''; ?>
	<?php if ($pap_order_received_phone !== '') : ?>
		<p class="pap-order-received__help">
			<?php
			printf(
				esc_html__('Dacă ai întrebări, ne poți contacta la %s.', 'papetarie-storefront'),
				'<a class="pap-order-received__help-phone" href="' . esc_attr('tel:+4' . preg_replace('/\s+/', '', $pap_order_received_phone)) . '">' . esc_html($pap_order_received_phone) . '</a>'
			);
			?>
		</p>
	<?php endif; ?>

	<?php if (!is_user_logged_in()) : ?>
		<script>
			(function () {
				const cookieNames = <?php echo wp_json_encode(['pap_checkout_shipping_snapshot', 'pap_checkout_shipping_mode']); ?>;

				const clearCookie = (name) => {
					document.cookie = `${encodeURIComponent(name)}=; path=/; max-age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; samesite=lax`;
				};

				const clearGuestCheckoutState = () => {
					cookieNames.forEach(clearCookie);
				};

				if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', clearGuestCheckoutState, { once: true });
				} else {
					clearGuestCheckoutState();
				}
			}());
		</script>
	<?php endif; ?>
</main>
