<?php
/**
 * Suprascrie templateul WooCommerce implicit (emails/customer-cancelled-order.php) -
 * textul default repeta numarul comenzii (deja afisat in subiect si antet) si
 * suna administrativ; il inlocuim cu un mesaj simplu + nota de rambursare doar
 * cand comanda nu e COD (magazinul e COD-only in prezent, deci ramane latent).
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (!defined('ABSPATH')) {
    exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled('email_improvements');

do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e('Comanda ta a fost anulată.', 'papetarie-storefront'); ?></p>
<?php if ($order->get_payment_method() !== 'cod' && $order->get_payment_method() !== '') : ?>
	<p><?php esc_html_e('Dacă ai achitat deja comanda, suma va fi rambursată prin metoda de plată utilizată.', 'papetarie-storefront'); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

if ($additional_content) {
    echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
    echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action('woocommerce_email_footer', $email);
