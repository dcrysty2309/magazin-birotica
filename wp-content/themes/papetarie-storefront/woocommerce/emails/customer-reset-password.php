<?php
/**
 * Suprascrie templateul WooCommerce implicit (emails/customer-reset-password.php) -
 * adresa de email afisata ca "Username" era interpretata de clientii de mail
 * (Gmail etc.) ca link clicabil, desi in sursa HTML nu era un <a> - fix prin
 * codificarea @ ca entitate HTML, identic vizual dar nedetectabil de
 * auto-linkify.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (!defined('ABSPATH')) {
    exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled('email_improvements');
$user_login_display = str_replace('@', '&#64;', esc_html($user_login));

?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($user_login)); ?></p>
<p><?php printf(esc_html__('Someone has requested a new password for the following account on %s:', 'woocommerce'), esc_html($blogname)); ?></p>
<?php if ($email_improvements_enabled) : ?>
	<div class="hr hr-top"></div>
	<p><?php echo wp_kses(sprintf(__('Username: <b>%s</b>', 'woocommerce'), $user_login_display), ['b' => []]); ?></p>
	<div class="hr hr-bottom"></div>
	<p><?php esc_html_e('If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:', 'woocommerce'); ?></p>
<?php else : ?>
	<p><?php echo wp_kses(sprintf(esc_html__('Username: %s', 'woocommerce'), $user_login_display), []); ?></p>
	<p><?php esc_html_e('If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce'); ?></p>
<?php endif; ?>
<p>
	<a class="link" href="<?php echo esc_url(add_query_arg(['key' => $reset_key, 'id' => $user_id, 'login' => rawurlencode($user_login)], wc_get_endpoint_url('lost-password', '', wc_get_page_permalink('myaccount')))); ?>">
		<?php
        if ($email_improvements_enabled) {
            esc_html_e('Reset your password', 'woocommerce');
        } else {
            esc_html_e('Click here to reset your password', 'woocommerce');
        }
        ?>
	</a>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php do_action('woocommerce_email_footer', $email); ?>
