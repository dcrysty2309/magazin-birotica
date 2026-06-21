<?php
/**
 * Checkout billing information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$billing_fields = $checkout->get_checkout_fields('billing');

$render_fields = static function (array $field_keys) use ($billing_fields, $checkout): void {
    foreach ($field_keys as $key) {
        if (!isset($billing_fields[$key])) {
            continue;
        }

        woocommerce_form_field($key, $billing_fields[$key], $checkout->get_value($key));
    }
};
?>

<?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

<section class="pap-checkout-section pap-checkout-section--billing">
	<div class="pap-checkout-section__head">
		<h2><?php esc_html_e('Date de facturare', 'papetarie-storefront'); ?></h2>
	</div>

	<div class="pap-checkout-section__fields pap-checkout-section__fields--contact">
		<div class="pap-checkout-contact-pair pap-checkout-contact-pair--name">
			<?php $render_fields(['billing_first_name', 'billing_last_name']); ?>
		</div>

		<div class="pap-checkout-contact-pair pap-checkout-contact-pair--details">
			<?php $render_fields(['billing_email', 'billing_phone']); ?>
		</div>
	</div>

	<div class="pap-checkout-section__fields pap-checkout-section__fields--billing">
		<?php
		$render_fields([
			'billing_address_1',
			'billing_address_2',
			'billing_state',
			'billing_city',
			'billing_postcode',
			'billing_country',
		]);
		?>
	</div>

	<div class="pap-checkout-section__fields pap-checkout-section__fields--company">
		<?php
		$render_fields([
			'billing_company',
			'billing_cui',
			'billing_reg_no',
			'billing_bank_name',
			'billing_iban',
		]);
		?>
	</div>
</section>

<?php if (!is_user_logged_in() && $checkout->is_registration_enabled()) : ?>
	<div class="pap-checkout-account-fields">
		<?php if (!$checkout->is_registration_required()) : ?>
			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked((true === $checkout->get_value('createaccount') || (true === apply_filters('woocommerce_create_account_default_checked', false))), true); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e('Vreau să creez și un cont', 'papetarie-storefront'); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action('woocommerce_before_checkout_registration_form', $checkout); ?>

		<?php if ($checkout->get_checkout_fields('account')) : ?>
			<div class="pap-checkout-card__fields pap-checkout-card__fields--account">
				<?php foreach ($checkout->get_checkout_fields('account') as $key => $field) : ?>
					<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action('woocommerce_after_checkout_registration_form', $checkout); ?>
	</div>
<?php endif; ?>

<?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
