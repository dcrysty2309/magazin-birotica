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
  <header class="pap-account-page-head">
    <p class="pap-account-page-eyebrow"><?php esc_html_e('Cont', 'papetarie-storefront'); ?></p>
    <h1><?php esc_html_e('Detalii cont', 'papetarie-storefront'); ?></h1>
    <p><?php esc_html_e('Actualizează datele de profil și parola asociată contului tău.', 'papetarie-storefront'); ?></p>
  </header>

  <section class="pap-account-panel pap-account-panel--form">
    <form class="woocommerce-EditAccountForm edit-account pap-account-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
      <?php do_action('woocommerce_edit_account_form_start'); ?>

      <div class="pap-account-form-grid">
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first pap-form-row">
          <label for="account_first_name"><?php esc_html_e('Prenume', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
          <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" aria-required="true" />
        </p>
        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last pap-form-row">
          <label for="account_last_name"><?php esc_html_e('Nume', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
          <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" aria-required="true" />
        </p>
      </div>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
        <label for="account_display_name"><?php esc_html_e('Nume afișat', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr($user->display_name); ?>" aria-required="true" />
        <span id="account_display_name_description"><em><?php esc_html_e('Acesta va fi afișat în cont și în recenzii.', 'papetarie-storefront'); ?></em></span>
      </p>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
        <label for="account_email"><?php esc_html_e('Adresă de email', 'papetarie-storefront'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
        <input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" aria-required="true" />
      </p>

      <?php do_action('woocommerce_edit_account_form_fields'); ?>

      <fieldset class="pap-account-password-fieldset">
        <legend><?php esc_html_e('Schimbare parolă', 'papetarie-storefront'); ?></legend>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
          <label for="password_current"><?php esc_html_e('Parola curentă', 'papetarie-storefront'); ?></label>
          <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
        </p>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
          <label for="password_1"><?php esc_html_e('Parola nouă', 'papetarie-storefront'); ?></label>
          <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
        </p>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide pap-form-row">
          <label for="password_2"><?php esc_html_e('Confirmă parola nouă', 'papetarie-storefront'); ?></label>
          <input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
        </p>
      </fieldset>

      <?php do_action('woocommerce_edit_account_form'); ?>

      <p class="pap-account-form-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="pap-account-primary-button" name="save_account_details" value="<?php esc_attr_e('Salvează modificările', 'papetarie-storefront'); ?>"><?php esc_html_e('Salvează modificările', 'papetarie-storefront'); ?></button>
        <input type="hidden" name="action" value="save_account_details" />
      </p>

      <?php do_action('woocommerce_edit_account_form_end'); ?>
    </form>
  </section>
</div>

<?php do_action('woocommerce_after_edit_account_form'); ?>
