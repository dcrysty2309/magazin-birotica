<?php
/**
 * Suprascrie templateul WooCommerce implicit (emails/email-addresses.php) -
 * cel original arata mereu doua coloane (facturare + livrare), chiar si
 * cand sunt identice (cazul obisnuit la COD, unde clientul nu are de ce sa
 * completeze doua adrese diferite) - decizie 2026-08-16, dupa observatia ca
 * emailurile arata aglomerat/redundant fata de standardul din industrie.
 */

if (!defined('ABSPATH')) {
    exit;
}

$billing_address = $order->get_formatted_billing_address();
$shipping_address = $order->get_formatted_shipping_address();
$has_separate_shipping = !wc_ship_to_billing_address_only()
    && $order->needs_shipping_address()
    && $shipping_address
    && trim(wp_strip_all_tags($shipping_address)) !== trim(wp_strip_all_tags($billing_address));

$email_improvements_enabled = class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')
    && \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled('email_improvements');

?>
<table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: <?php echo $email_improvements_enabled ? '0' : '40px'; ?>; padding:0;" border="0" role="presentation">
	<tr>
		<td class="font-family text-align-left" style="border:0; padding:0;" valign="top" width="<?php echo $has_separate_shipping ? '50%' : '100%'; ?>">
			<?php if ($email_improvements_enabled) : ?>
				<b class="address-title"><?php echo esc_html($has_separate_shipping ? __('Adresă de facturare', 'papetarie-storefront') : __('Adresă de livrare', 'papetarie-storefront')); ?></b>
			<?php else : ?>
				<h2><?php echo esc_html($has_separate_shipping ? __('Adresă de facturare', 'papetarie-storefront') : __('Adresă de livrare', 'papetarie-storefront')); ?></h2>
			<?php endif; ?>

			<address class="address">
				<?php echo wp_kses_post(papetarie_storefront_format_compact_address($order, 'billing')); ?>
				<?php do_action('woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false); ?>
			</address>
		</td>
		<?php if ($has_separate_shipping) : ?>
			<td class="font-family text-align-left" style="padding:0;" valign="top" width="50%">
				<?php if ($email_improvements_enabled) : ?>
					<b class="address-title"><?php esc_html_e('Adresă de livrare', 'papetarie-storefront'); ?></b>
				<?php else : ?>
					<h2><?php esc_html_e('Adresă de livrare', 'papetarie-storefront'); ?></h2>
				<?php endif; ?>

				<address class="address">
					<?php echo wp_kses_post(papetarie_storefront_format_compact_address($order, 'shipping')); ?>
					<?php do_action('woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false); ?>
				</address>
			</td>
		<?php endif; ?>
	</tr>
</table>
<?php echo $email_improvements_enabled ? '<br>' : ''; ?>
