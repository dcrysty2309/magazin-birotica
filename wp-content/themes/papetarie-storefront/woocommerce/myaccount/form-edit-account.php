<?php
/**
 * Edit account form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');

// A failed save re-renders this same request directly (WC_Form_Handler only
// redirects when there are zero errors), so open straight into edit mode
// instead of flashing the view first — the errors live inside the edit form.
$pap_notice_type = function_exists('papetarie_storefront_get_account_notice_type') ? papetarie_storefront_get_account_notice_type() : '';
$pap_start_in_edit = $pap_notice_type === 'error';

$pap_full_name = trim($user->first_name . ' ' . $user->last_name);
if ($pap_full_name === '') {
    $pap_full_name = $user->display_name;
}
?>

<div class="pap-account-page pap-account-page--edit-account">
  <section class="pap-account-panel pap-account-panel--form pap-account-detail-panel<?php echo $pap_start_in_edit ? ' is-editing' : ''; ?>" data-account-detail-panel>
    <div class="pap-account-detail-head">
      <h2 class="pap-account-detail-title"><?php esc_html_e('Detalii cont', 'papetarie-storefront'); ?></h2>
      <button type="button" class="pap-account-detail-edit-toggle" data-account-edit-toggle>
        <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
      </button>
    </div>

    <?php papetarie_storefront_render_account_notice(); ?>

    <div class="pap-account-detail-view" data-account-detail-view>
      <div class="pap-account-detail-row">
        <span class="pap-account-detail-label"><?php esc_html_e('Nume:', 'papetarie-storefront'); ?></span>
        <span class="pap-account-detail-value"><?php echo esc_html($pap_full_name); ?></span>
      </div>
      <div class="pap-account-detail-row">
        <span class="pap-account-detail-label"><?php esc_html_e('Email:', 'papetarie-storefront'); ?></span>
        <span class="pap-account-detail-value"><?php echo esc_html($user->user_email); ?></span>
      </div>
      <div class="pap-account-detail-row pap-account-detail-row--last">
        <span class="pap-account-detail-label"><?php esc_html_e('Parolă:', 'papetarie-storefront'); ?></span>
        <span class="pap-account-detail-value">••••••••</span>
      </div>
    </div>

    <?php // novalidate: the account_email input is type="email", so without
    // this the browser's own constraint validation intercepts the submit
    // click before our JS submit handler ever runs — the native tooltip
    // shows instead of the site's inline red error, and .pap-is-invalid
    // never gets applied at all. ?>
    <form class="woocommerce-EditAccountForm edit-account pap-account-detail-edit" action="" method="post" novalidate data-account-detail-edit <?php do_action('woocommerce_edit_account_form_tag'); ?>>
      <?php do_action('woocommerce_edit_account_form_start'); ?>

      <!-- Figma shows "Nume:" as read-only text even in edit mode — name
           isn't user-editable here. The first/last name POST fields stay as
           hidden inputs (not just omitted) because WC_Form_Handler both
           requires them and overwrites $user->first_name/last_name with
           whatever comes through, so dropping them from the form entirely
           would blank out the name on every save. -->
      <div class="pap-account-detail-name-row">
        <span class="pap-account-detail-name-label"><?php esc_html_e('Nume:', 'papetarie-storefront'); ?></span>
        <span class="pap-account-detail-name-value"><?php echo esc_html($pap_full_name); ?></span>
      </div>
      <input type="hidden" name="account_first_name" id="account_first_name" value="<?php echo esc_attr($user->first_name); ?>" />
      <input type="hidden" name="account_last_name" id="account_last_name" value="<?php echo esc_attr($user->last_name); ?>" />
      <input type="hidden" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" />

      <div class="pap-float-field">
        <input type="email" class="woocommerce-Input woocommerce-Input--email" name="account_email" id="account_email" placeholder=" " autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" aria-required="true" />
        <label for="account_email"><?php esc_html_e('Adresa de email', 'papetarie-storefront'); ?></label>
        <small class="pap-field-error" aria-hidden="true"></small>
      </div>

      <?php do_action('woocommerce_edit_account_form_fields'); ?>

      <!-- readonly + onfocus removeAttribute: the standard trick to stop
           Chrome/most browsers from autofilling a saved password into this
           field on page load — browsers won't autofill a readonly input, so
           the user has to actually type their current password themselves
           every time they want to change it, instead of it silently arriving
           pre-filled. -->
      <div class="pap-float-field pap-float-field--password" data-password-field>
        <input type="password" class="woocommerce-Input woocommerce-Input--password" name="password_current" id="password_current" placeholder=" " autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" />
        <label for="password_current"><?php esc_html_e('Parola curentă', 'papetarie-storefront'); ?></label>
        <button class="pap-password-toggle pap-password-toggle--float" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
          <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
        <small class="pap-field-error" aria-hidden="true"></small>
      </div>

      <div class="pap-float-field pap-float-field--password" data-password-field>
        <input type="password" class="woocommerce-Input woocommerce-Input--password" name="password_1" id="password_1" placeholder=" " autocomplete="new-password" />
        <label for="password_1"><?php esc_html_e('Parolă nouă', 'papetarie-storefront'); ?></label>
        <button class="pap-password-toggle pap-password-toggle--float" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
          <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
        <small class="pap-field-error" aria-hidden="true"></small>
      </div>

      <div class="pap-float-field pap-float-field--password" data-password-field>
        <input type="password" class="woocommerce-Input woocommerce-Input--password" name="password_2" id="password_2" placeholder=" " autocomplete="new-password" />
        <label for="password_2"><?php esc_html_e('Confirmă parola nouă', 'papetarie-storefront'); ?></label>
        <button class="pap-password-toggle pap-password-toggle--float" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
          <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
        <small class="pap-field-error" aria-hidden="true"></small>
      </div>

      <?php do_action('woocommerce_edit_account_form'); ?>

      <div class="pap-account-detail-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <input type="hidden" name="action" value="save_account_details" />
        <button type="button" class="pap-account-secondary-button" data-account-edit-cancel><?php esc_html_e('Anulați', 'papetarie-storefront'); ?></button>
        <button type="submit" class="pap-account-primary-button" name="save_account_details" value="<?php esc_attr_e('Salvare', 'papetarie-storefront'); ?>"><?php esc_html_e('Salvare', 'papetarie-storefront'); ?></button>
      </div>

      <?php do_action('woocommerce_edit_account_form_end'); ?>
    </form>
  </section>
</div>

<?php do_action('woocommerce_after_edit_account_form'); ?>
