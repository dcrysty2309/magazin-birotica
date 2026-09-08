<?php
/**
 * Suprascrie templateul WooCommerce implicit (emails/email-order-details.php) -
 * capetele de tabel ("Product"/"Quantity"/"Price") vin traduse din pachetul
 * de limba RO cu litera mica ("produs"), fara sa putem controla asta prin
 * filtrul gettext fara sa afectam si restul site-ului - le scriem direct
 * aici, hardcodat, ca sa avem control deplin asupra capitalizarii.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined('ABSPATH') || exit;

$text_align = is_rtl() ? 'right' : 'left';

$email_improvements_enabled = FeaturesUtil::feature_is_enabled('email_improvements');
$block_email_editor_enabled = FeaturesUtil::feature_is_enabled('block_email_editor');
$display_section_divider = (bool) apply_filters('woocommerce_email_body_display_section_divider', true);
$heading_class = $email_improvements_enabled ? 'email-order-detail-heading' : '';
$order_table_class = $email_improvements_enabled ? 'email-order-details' : '';
$order_total_text_align = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align = $email_improvements_enabled ? 'right' : 'left';
$product_column_label = count($order->get_items()) === 1 ? __('Produs', 'papetarie-storefront') : __('Produse', 'papetarie-storefront');

if ($email_improvements_enabled) {
    add_filter('woocommerce_order_shipping_to_display_shipped_via', '__return_false');
}

do_action('woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email); ?>

<h2 class="<?php echo esc_attr($heading_class); ?>">
	<?php
    if ($email_improvements_enabled) {
        echo esc_html__('Rezumat comandă', 'papetarie-storefront');
    }
    if ($email_improvements_enabled) {
        echo '<br><span>';
    }
    if ($sent_to_admin) {
        /* translators: %s: Order ID. */
        $order_number_string = __('[Comandă #%s]', 'papetarie-storefront');
        if ($email_improvements_enabled) {
            /* translators: %s: Order ID. */
            $order_number_string = __('Comandă #%s', 'papetarie-storefront');
        }
        $before = '<a class="link" href="' . esc_url($order->get_edit_order_url()) . '"' . ($block_email_editor_enabled ? ' style="text-decoration: none;"' : '') . '>';
        $after = '</a>';
        echo wp_kses_post($before . sprintf($order_number_string . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format('c'), wc_format_datetime($order->get_date_created())));
    } else {
        echo wp_kses_post(sprintf('(<time datetime="%s">%s</time>)', $order->get_date_created()->format('c'), wc_format_datetime($order->get_date_created())));
    }
    if ($email_improvements_enabled) {
        echo '</span>';
    }
    ?>
</h2>

<div style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
	<table class="td font-family <?php echo esc_attr($order_table_class); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
		<?php if (!$block_email_editor_enabled) : ?>
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr($text_align); ?>;"><?php echo esc_html($product_column_label); ?></th>
				<th class="td" scope="col" style="text-align:center;"><?php esc_html_e('Cantitate', 'papetarie-storefront'); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr($order_total_text_align); ?>;"><?php esc_html_e('Preț', 'papetarie-storefront'); ?></th>
			</tr>
		</thead>
		<?php endif; ?>
		<tbody>
			<?php
            $image_size = $email_improvements_enabled ? 48 : 32;
            echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $order,
                [
                    'show_sku' => $sent_to_admin,
                    'show_image' => $email_improvements_enabled,
                    'image_size' => [$image_size, $image_size],
                    'plain_text' => $plain_text,
                    'sent_to_admin' => $sent_to_admin,
                ]
            );
            ?>
		</tbody>
	</table>
	<table class="td font-family <?php echo esc_attr($order_table_class); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
		<?php
        $item_totals = $order->get_order_item_totals();
        if (isset($item_totals['payment_method'], $item_totals['order_total'])) {
            $payment_method_row = $item_totals['payment_method'];
            unset($item_totals['payment_method']);
            $reordered_totals = [];
            foreach ($item_totals as $total_key => $total_row) {
                if ($total_key === 'order_total') {
                    $reordered_totals['payment_method'] = $payment_method_row;
                }
                $reordered_totals[$total_key] = $total_row;
            }
            $item_totals = $reordered_totals;
        }
        $item_totals_count = count($item_totals);

        if ($item_totals) {
            $i = 0;
            foreach ($item_totals as $total) {
                ++$i;
                $last_class = ($i === $item_totals_count) ? ' order-totals-last' : '';
                ?>
				<tr class="order-totals order-totals-<?php echo esc_attr($total['type'] ?? 'unknown'); ?><?php echo esc_attr($last_class); ?>">
					<th class="td text-align-left" scope="row" colspan="2">
						<?php
                        echo wp_kses_post($total['label']) . ' ';
                        if ($email_improvements_enabled) {
                            echo isset($total['meta']) ? wp_kses_post($total['meta']) : '';
                        }
                        ?>
					</th>
					<td class="td text-align-<?php echo esc_attr($order_total_text_align); ?>"><?php echo wp_kses_post($total['value']); ?></td>
				</tr>
				<?php
            }
        }
        if ($order->get_customer_note() && !$email_improvements_enabled) {
            ?>
			<tr>
				<th class="td text-align-left" scope="row" colspan="2"><?php esc_html_e('Notă:', 'papetarie-storefront'); ?></th>
				<td class="td text-align-left"><?php echo wp_kses(nl2br(wc_wptexturize_order_note($order->get_customer_note())), []); ?></td>
			</tr>
			<?php
        }
        ?>
	</table>
	<?php if ($order->get_customer_note() && $email_improvements_enabled) { ?>
		<?php if ($display_section_divider) : ?>
			<hr style="border: 0; border-top: 1px solid #1E1E1E; border-top-color: rgba(30, 30, 30, 0.2); margin: 20px 0;">
		<?php endif; ?>
		<table class="td font-family <?php echo esc_attr($order_table_class); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1" role="presentation">
			<tr class="order-customer-note">
				<td class="td text-align-left">
					<b><?php esc_html_e('Notă client', 'papetarie-storefront'); ?></b><br>
					<?php echo wp_kses(nl2br(wc_wptexturize_order_note($order->get_customer_note())), ['br' => []]); ?>
				</td>
			</tr>
		</table>
	<?php } ?>
</div>

<?php
if ($email_improvements_enabled) {
    remove_filter('woocommerce_order_shipping_to_display_shipped_via', '__return_false');
}

do_action('woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email);
