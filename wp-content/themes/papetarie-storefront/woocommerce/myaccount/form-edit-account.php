<?php
/**
 * Edit account form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');
?>

<div class="pap-account-page pap-account-page--edit-account">
  <?php papetarie_storefront_render_account_page_head(
      __('Detalii cont', 'papetarie-storefront'),
      __('Actualizează datele de profil și parola asociată contului tău.', 'papetarie-storefront')
  ); ?>

  <section class="pap-account-panel pap-account-panel--form pap-account-panel--personal">
    <form class="woocommerce-EditAccountForm edit-account pap-account-form" action="" method="post" data-account-form="personal" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
      <?php do_action('woocommerce_edit_account_form_start'); ?>

      <div class="pap-account-form-section pap-account-form-section--personal">
        <h2 class="pap-account-form-section-title"><?php esc_html_e('Date personale', 'papetarie-storefront'); ?></h2>

        <div class="pap-account-form-row-pair">
          <p class="woocommerce-form-row form-row pap-form-row">
            <label for="account_first_name"><?php esc_html_e('Prenume', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
            <span class="pap-auth-input-field pap-auth-input-field--user">
              <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
              <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" aria-required="true" />
            </span>
          </p>
          <p class="woocommerce-form-row form-row pap-form-row">
            <label for="account_last_name"><?php esc_html_e('Nume', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
            <span class="pap-auth-input-field pap-auth-input-field--user">
              <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
              <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" aria-required="true" />
            </span>
          </p>
        </div>

        <input type="hidden" name="account_display_name" id="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" />

        <p class="woocommerce-form-row form-row pap-form-row">
          <label for="account_email"><?php esc_html_e('Adresă de email', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
          <span class="pap-auth-input-field pap-auth-input-field--email">
            <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('mail'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" aria-required="true" />
          </span>
        </p>
      </div>

      <?php do_action('woocommerce_edit_account_form_fields'); ?>

      <p class="pap-account-form-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="pap-account-primary-button" name="save_account_details" value="<?php esc_attr_e('Salvează modificările', 'papetarie-storefront'); ?>"><?php esc_html_e('Salvează modificările', 'papetarie-storefront'); ?></button>
        <input type="hidden" name="action" value="save_account_details" />
      </p>

      <?php do_action('woocommerce_edit_account_form_end'); ?>
    </form>
  </section>

  <section class="pap-account-panel pap-account-panel--form pap-account-panel--password">
    <form class="woocommerce-EditAccountForm edit-account pap-account-form" action="" method="post" data-account-form="password">
      <div class="pap-account-form-section pap-account-form-section--password pap-account-password-fieldset">
        <h2 class="pap-account-form-section-title"><?php esc_html_e('Schimbare parolă', 'papetarie-storefront'); ?></h2>

        <?php
        // Same handler (WC_Form_Handler::save_account_details) always reads/re-saves
        // name + email from $_POST, so this form carries them along unchanged —
        // otherwise submitting only a password change would blank them out.
        ?>
        <input type="hidden" name="account_first_name" value="<?php echo esc_attr($user->first_name); ?>" />
        <input type="hidden" name="account_last_name" value="<?php echo esc_attr($user->last_name); ?>" />
        <input type="hidden" name="account_display_name" value="<?php echo esc_attr($user->display_name); ?>" />
        <input type="hidden" name="account_email" value="<?php echo esc_attr($user->user_email); ?>" />

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
          <label for="password_current"><?php esc_html_e('Parola curentă', 'papetarie-storefront'); ?></label>
          <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
            <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
            <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
              <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </button>
          </span>
        </p>

        <div class="pap-account-form-row-pair">
          <p class="woocommerce-form-row form-row pap-form-row">
            <label for="password_1"><?php esc_html_e('Parola nouă', 'papetarie-storefront'); ?></label>
            <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
              <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
              <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
              <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </button>
            </span>
          </p>
          <p class="woocommerce-form-row form-row pap-form-row">
            <label for="password_2"><?php esc_html_e('Confirmă parola nouă', 'papetarie-storefront'); ?></label>
            <span class="pap-auth-input-field pap-auth-input-field--password pap-password-field" data-password-field>
              <span class="pap-auth-input-icon" aria-hidden="true"><?php echo papetarie_storefront_auth_input_icon('lock'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
              <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
              <button class="pap-password-toggle" type="button" data-password-toggle aria-label="<?php esc_attr_e('Arată parola', 'papetarie-storefront'); ?>">
                <?php echo papetarie_storefront_password_toggle_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </button>
            </span>
          </p>
        </div>
      </div>

      <?php do_action('woocommerce_edit_account_form'); ?>

      <p class="pap-account-form-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="pap-account-primary-button" name="save_account_details" value="<?php esc_attr_e('Actualizează parola', 'papetarie-storefront'); ?>"><?php esc_html_e('Actualizează parola', 'papetarie-storefront'); ?></button>
        <input type="hidden" name="action" value="save_account_details" />
      </p>
    </form>
  </section>
</div>

<?php do_action('woocommerce_after_edit_account_form'); ?>
