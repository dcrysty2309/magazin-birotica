<?php
/**
 * Checkout shipping information form.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$shipping_fields = $checkout->get_checkout_fields('shipping');
$billing_fields = $checkout->get_checkout_fields('billing');

$render_shipping_field = static function (string $key) use ($shipping_fields, $checkout): string {
    if (!isset($shipping_fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
        return '';
    }

    return papetarie_storefront_render_checkout_form_field($key, $shipping_fields[$key], $checkout->get_value($key), false);
};

$render_billing_field = static function (string $key) use ($billing_fields, $checkout): string {
    if (!isset($billing_fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
        return '';
    }

    return papetarie_storefront_render_checkout_form_field($key, $billing_fields[$key], $checkout->get_value($key), false);
};

$render_fields = static function (array $field_keys, string $context) use ($shipping_fields, $billing_fields, $checkout): string {
    $html = '';
    $fields = 'shipping' === $context ? $shipping_fields : $billing_fields;

    foreach ($field_keys as $key) {
        if (!isset($fields[$key]) || !function_exists('papetarie_storefront_render_checkout_form_field')) {
            continue;
        }

        $html .= papetarie_storefront_render_checkout_form_field($key, $fields[$key], $checkout->get_value($key), false);
    }

    return $html;
};

$is_guest_checkout = !is_user_logged_in();
$shipping_address_mode = function_exists('papetarie_storefront_checkout_shipping_address_mode')
    ? papetarie_storefront_checkout_shipping_address_mode()
    : 'edit';
$shipping_address_is_summary = 'summary' === $shipping_address_mode;
$logged_in_addresses = [];
$logged_in_selected_address_id = '';
$logged_in_email = '';
$logged_in_has_temporary_address = false;

if (!$is_guest_checkout && function_exists('papetarie_storefront_address_book_get_all')) {
    $current_user_id = get_current_user_id();
    $logged_in_addresses = papetarie_storefront_address_book_get_all($current_user_id);
    $logged_in_has_temporary_address = function_exists('papetarie_storefront_address_book_checkout_has_temporary_address')
        ? papetarie_storefront_address_book_checkout_has_temporary_address()
        : false;
    $selected_address = !$logged_in_has_temporary_address && function_exists('papetarie_storefront_address_book_checkout_selected_address')
        ? papetarie_storefront_address_book_checkout_selected_address($current_user_id, 'shipping')
        : null;
    $logged_in_selected_address_id = (string) ($selected_address['id'] ?? '');
    $logged_in_email = function_exists('papetarie_storefront_address_book_checkout_email')
        ? papetarie_storefront_address_book_checkout_email($current_user_id)
        : '';

    if (
        !$logged_in_has_temporary_address
        && $logged_in_selected_address_id !== ''
        && function_exists('papetarie_storefront_address_book_checkout_set_selected_address_id')
    ) {
        papetarie_storefront_address_book_checkout_set_selected_address_id('shipping', $logged_in_selected_address_id);
        papetarie_storefront_address_book_checkout_set_selected_address_id('billing', $logged_in_selected_address_id);
    }
}

$render_logged_in_address_card = static function (array $address, string $selected_id, string $email, bool $temporary_mode = false): void {
    $address_id = (string) ($address['id'] ?? '');
    $full_name = trim((string) ($address['first_name'] ?? '') . ' ' . (string) ($address['last_name'] ?? ''));
    $state_code = strtoupper(sanitize_key((string) ($address['state'] ?? '')));
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $state_label = isset($counties[$state_code]) ? (string) $counties[$state_code] : $state_code;
    $address_line = implode(', ', array_filter([
        trim((string) ($address['address_1'] ?? '')),
        trim((string) ($address['address_2'] ?? '')),
        trim((string) ($address['city'] ?? '')),
        $state_label,
        trim((string) ($address['postcode'] ?? '')),
    ]));
    $phone = trim((string) ($address['phone'] ?? ''));
    $is_selected = !$temporary_mode && $address_id !== '' && $address_id === $selected_id;
    ?>
    <div class="pap-checkout-address-option<?php echo $is_selected ? ' is-selected' : ''; ?>" data-pap-auth-address-option>
        <input
            type="radio"
            name="papetarie_checkout_selected_address_shipping"
            value="<?php echo esc_attr($address_id); ?>"
            data-checkout-address-selector
            data-checkout-address-prefix="shipping"
            <?php checked($is_selected); ?>
        >
        <div class="pap-checkout-address-card">
            <div class="pap-checkout-address-card__head">
                <div class="pap-checkout-address-card__title-copy">
                    <p class="pap-checkout-address-card__name"><?php echo esc_html($full_name); ?></p>
                </div>
            </div>
            <div class="pap-checkout-address-card__body">
                <?php if ($address_line !== '') : ?>
                    <p class="pap-checkout-address-card__line address-summary-row">
                        <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" focusable="false">
                                <path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z"></path>
                                <circle cx="12" cy="10" r="2"></circle>
                            </svg>
                        </span>
                        <span class="pap-checkout-address-card__line-text"><?php echo esc_html($address_line); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($phone !== '') : ?>
                    <p class="pap-checkout-address-card__line address-summary-row">
                        <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" focusable="false">
                                <path d="M6.6 3.6l2.1 4.2c.3.6.2 1.2-.3 1.7l-1 1c1.2 2.4 3.1 4.3 5.5 5.5l1-1c.5-.5 1.1-.6 1.7-.3l4.2 2.1c.6.3.9.9.8 1.5l-.4 2c-.1.6-.7 1.1-1.3 1.1C10 21.4 2.6 14 2.6 5.1c0-.6.5-1.2 1.1-1.3l2-.4c.4-.1.8 0 .9.2z"></path>
                            </svg>
                        </span>
                        <span class="pap-checkout-address-card__line-text"><?php echo esc_html($phone); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($email !== '') : ?>
                    <p class="pap-checkout-address-card__line address-summary-row">
                        <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" focusable="false">
                                <rect x="4" y="6" width="16" height="12" rx="1.5"></rect>
                                <path d="M5.5 7.5 12 12.8l6.5-5.3"></path>
                            </svg>
                        </span>
                        <span class="pap-checkout-address-card__line-text"><?php echo esc_html($email); ?></span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
};
?>

<section
    class="pap-checkout-card pap-checkout-card--shipping-address pap-checkout-step pap-checkout-step--shipping-address<?php echo $shipping_address_is_summary ? ' is-summary-mode' : ''; ?> is-step-<?php echo esc_attr(function_exists('papetarie_storefront_checkout_step_state') ? papetarie_storefront_checkout_step_state('shipping-address') : 'active'); ?>"
    data-pap-checkout-section="shipping-address"
    data-pap-checkout-step="shipping-address"
    data-pap-step-state="<?php echo esc_attr(function_exists('papetarie_storefront_checkout_step_state') ? papetarie_storefront_checkout_step_state('shipping-address') : 'active'); ?>"
    data-pap-guest-shipping-mode="<?php echo esc_attr($shipping_address_mode); ?>"
>
    <div class="pap-checkout-card__head">
        <div class="pap-checkout-section-title-row">
            <span class="pap-checkout-section-badge" aria-hidden="true">1</span>
            <h2><?php esc_html_e('Adresa de livrare', 'papetarie-storefront'); ?></h2>
        </div>
    </div>

    <?php if ($is_guest_checkout) : ?>
        <div class="pap-checkout-guest-shipping">
            <div class="pap-checkout-guest-shipping__form" data-pap-guest-shipping-form>
                <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

                <div class="pap-auth-form pap-checkout-address-form">
                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_first_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_last_name'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_billing_field('billing_email'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_billing_field('billing_phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_shipping_field('shipping_state'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php echo $render_shipping_field('shipping_city'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--stack">
                        <?php echo $render_shipping_field('shipping_address_1'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--stack">
                        <?php echo $render_shipping_field('shipping_address_2'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <input type="hidden" name="pap_guest_shipping_snapshot" value="" data-pap-guest-shipping-snapshot>
                    <input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
                    <input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
                </div>
            </div>

            <div class="pap-checkout-guest-shipping__summary" data-pap-guest-shipping-summary hidden aria-hidden="true">
                <?php echo function_exists('papetarie_storefront_get_checkout_guest_shipping_summary_html') ? papetarie_storefront_get_checkout_guest_shipping_summary_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <?php if ($checkout->is_registration_enabled() && !is_user_logged_in()) : ?>
                <div class="pap-checkout-guest-shipping__options">
                    <label class="pap-checkout-guest-option">
                        <input type="checkbox" name="createaccount" value="1" <?php checked($checkout->get_value('createaccount'), 1); ?>>
                        <span><?php esc_html_e('Creează cont după finalizarea comenzii', 'papetarie-storefront'); ?></span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="pap-checkout-guest-shipping__actions">
                <button type="button" class="button alt pap-cart-checkout pap-checkout-guest-shipping__continue" data-pap-guest-shipping-continue>
                    <?php esc_html_e('Continuă către livrare', 'papetarie-storefront'); ?>
                </button>
            </div>

            <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
        </div>
    <?php else : ?>
        <div class="pap-checkout-auth-shipping" data-pap-auth-shipping data-pap-auth-temporary-mode="<?php echo $logged_in_has_temporary_address ? 'summary' : 'list'; ?>">
            <div class="pap-checkout-auth-shipping__addresses" data-pap-auth-address-list <?php echo (empty($logged_in_addresses) || $logged_in_has_temporary_address) ? 'hidden' : ''; ?>>
                <div class="pap-checkout-auth-shipping__grid" data-pap-auth-address-grid>
                    <?php foreach ($logged_in_addresses as $logged_in_address) : ?>
                        <?php $render_logged_in_address_card($logged_in_address, $logged_in_selected_address_id, $logged_in_email, $logged_in_has_temporary_address); ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="pap-checkout-auth-shipping__add" data-pap-auth-address-add>
                    <?php esc_html_e('Adaugă adresă nouă', 'papetarie-storefront'); ?>
                </button>
            </div>

            <div class="pap-checkout-auth-shipping__summary pap-checkout-guest-shipping__summary" data-pap-auth-shipping-summary <?php echo $logged_in_has_temporary_address ? '' : 'hidden'; ?> aria-hidden="<?php echo $logged_in_has_temporary_address ? 'false' : 'true'; ?>">
                <?php echo function_exists('papetarie_storefront_get_checkout_auth_shipping_summary_html') ? papetarie_storefront_get_checkout_auth_shipping_summary_html() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>

            <div class="pap-checkout-auth-shipping__form" data-pap-auth-shipping-form <?php echo ($logged_in_has_temporary_address || !empty($logged_in_addresses)) ? 'hidden' : ''; ?>>
                <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

                <div class="pap-auth-form pap-checkout-address-form">
                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_fields(['billing_first_name', 'billing_last_name'], 'billing'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_fields(['billing_email', 'billing_phone'], 'billing'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_fields(['shipping_state', 'shipping_city'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--stack">
                        <?php echo $render_fields(['shipping_address_1'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <div class="pap-form-row pap-form-row--split">
                        <?php echo $render_fields(['shipping_address_2', 'shipping_postcode'], 'shipping'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>

                    <input type="hidden" name="ship_to_different_address" value="1">
                    <input type="hidden" id="shipping_first_name" name="shipping_first_name" value="<?php echo esc_attr($checkout->get_value('shipping_first_name')); ?>">
                    <input type="hidden" id="shipping_last_name" name="shipping_last_name" value="<?php echo esc_attr($checkout->get_value('shipping_last_name')); ?>">
                    <input type="hidden" id="shipping_company" name="shipping_company" value="<?php echo esc_attr($checkout->get_value('shipping_company')); ?>">
                    <input type="hidden" id="shipping_phone" name="shipping_phone" value="<?php echo esc_attr($checkout->get_value('shipping_phone')); ?>">
                    <input type="hidden" name="billing_country" value="<?php echo esc_attr($checkout->get_value('billing_country') ?: 'RO'); ?>">
                    <input type="hidden" name="shipping_country" value="<?php echo esc_attr($checkout->get_value('shipping_country') ?: 'RO'); ?>">
                </div>

                <div class="pap-checkout-auth-shipping__actions">
                    <button type="button" class="pap-checkout-auth-shipping__cancel" data-pap-auth-address-cancel <?php echo empty($logged_in_addresses) ? 'hidden' : ''; ?>>
                        <?php esc_html_e('Anulează', 'papetarie-storefront'); ?>
                    </button>
                    <button type="button" class="button alt pap-cart-checkout pap-checkout-guest-shipping__continue" data-pap-auth-address-save>
                        <?php esc_html_e('Continuă către livrare', 'papetarie-storefront'); ?>
                    </button>
                </div>
                <div class="pap-checkout-auth-shipping__notice" data-pap-auth-address-notice hidden role="alert"></div>

                <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
            </div>
        </div>
    <?php endif; ?>
</section>

