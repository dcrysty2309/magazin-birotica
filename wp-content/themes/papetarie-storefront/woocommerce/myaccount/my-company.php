<?php
/**
 * Firmele mele — pagina de management a firmelor salvate pentru facturare.
 *
 * Nu e un template WooCommerce nativ (spre deosebire de my-address.php) -
 * "firmele-mele" e un endpoint custom (vezi papetarie_storefront_register_account_endpoints()
 * din functions.php), inclus explicit prin wc_get_template() din callback-ul
 * legat de hook-ul woocommerce_account_firmele-mele_endpoint.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea firmele tale.', 'papetarie-storefront') . '</p>';
    return;
}

$user_id = get_current_user_id();
$companies = function_exists('papetarie_storefront_company_book_get_all')
    ? papetarie_storefront_company_book_get_all($user_id)
    : [];

$mode = function_exists('papetarie_storefront_company_book_current_mode')
    ? papetarie_storefront_company_book_current_mode()
    : 'list';
$edit_id = function_exists('papetarie_storefront_company_book_current_id')
    ? papetarie_storefront_company_book_current_id()
    : '';

$modal_company = [];
if ($mode === 'edit' && $edit_id !== '' && function_exists('papetarie_storefront_company_book_get')) {
    $modal_company = papetarie_storefront_company_book_get($user_id, $edit_id) ?? [];
    if (empty($modal_company)) {
        $mode = 'list';
    }
}

if (function_exists('papetarie_storefront_company_book_get_form_state')) {
    $form_state = papetarie_storefront_company_book_get_form_state();
    if (!empty($form_state)) {
        $modal_company = array_merge($modal_company, $form_state);
    }
}

$add_url = function_exists('papetarie_storefront_company_book_form_url')
    ? papetarie_storefront_company_book_form_url(['pap_company_action' => 'add'])
    : '';
$delete_action_url = function_exists('papetarie_storefront_company_book_base_url')
    ? papetarie_storefront_company_book_base_url()
    : '';
?>

<div class="pap-account-page pap-account-page--companies">
  <section class="pap-account-panel pap-account-panel--addresses">
    <article class="pap-account-address-card pap-account-address-card--single">
      <div class="pap-account-address-card__head">
        <h3><?php esc_html_e('Firmele mele', 'papetarie-storefront'); ?></h3>
      </div>

      <?php if (function_exists('papetarie_storefront_render_account_notice')) : ?>
        <?php papetarie_storefront_render_account_notice(); ?>
      <?php endif; ?>

    <?php if (empty($companies)) : ?>
        <div class="pap-account-address-card__body">
          <div class="pap-account-address-card__empty">
            <div class="pap-account-address-card__empty-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="3.5" width="10" height="17" rx="1"></rect>
                <path d="M14 9.5h6v11h-6"></path>
                <path d="M7 7.5h4"></path>
                <path d="M7 11h4"></path>
                <path d="M7 14.5h4"></path>
              </svg>
            </div>
            <div class="pap-account-address-card__empty-copy">
              <h4><?php esc_html_e('Nicio firmă salvată', 'papetarie-storefront'); ?></h4>
              <p><?php esc_html_e('Adaugă o firmă ca să poți factura rapid pe persoană juridică la checkout.', 'papetarie-storefront'); ?></p>
            </div>
            <a
              class="pap-account-primary-button pap-account-address-card__empty-action"
              href="<?php echo esc_url($add_url); ?>"
              data-company-book-open-modal
              data-company-book-mode="add"
            >+ <?php esc_html_e('Adaugă firmă', 'papetarie-storefront'); ?></a>
          </div>
        </div>
    <?php else : ?>
      <div class="pap-account-address-card__body">
      <div class="pap-account-company-grid">
        <?php foreach ($companies as $company) :
            $rows = function_exists('papetarie_storefront_company_book_table_rows')
                ? papetarie_storefront_company_book_table_rows($company)
                : [];
            $denumire = trim((string) ($company['denumire'] ?? ''));
            $nr_reg_com = trim((string) ($company['nr_reg_com'] ?? ''));
            $company_json = wp_json_encode($company, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $is_default = !empty($company['is_default']);
            $edit_url_company = function_exists('papetarie_storefront_company_book_form_url')
                ? papetarie_storefront_company_book_form_url(['pap_company_action' => 'edit', 'pap_company_id' => (string) ($company['id'] ?? '')])
                : '';
        ?>
          <article class="pap-account-company-card<?php echo $is_default ? ' is-default' : ''; ?>">
            <div class="pap-account-company-card__head">
              <span class="pap-account-company-card__icon" aria-hidden="true">
                <svg viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4.25 15.5833V2.83333C4.25 2.45761 4.39926 2.09728 4.66493 1.8316C4.93061 1.56592 5.29094 1.41667 5.66667 1.41667H11.3333C11.7091 1.41667 12.0694 1.56592 12.3351 1.8316C12.6007 2.09728 12.75 2.45761 12.75 2.83333V15.5833H4.25Z" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M4.25 8.5H2.83333C2.45761 8.5 2.09728 8.64926 1.8316 8.91493C1.56592 9.18061 1.41667 9.54094 1.41667 9.91667V14.1667C1.41667 14.5424 1.56592 14.9027 1.8316 15.1684C2.09728 15.4341 2.45761 15.5833 2.83333 15.5833H4.25" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12.75 6.375H14.1667C14.5424 6.375 14.9027 6.52426 15.1684 6.78993C15.4341 7.05561 15.5833 7.41594 15.5833 7.79167V14.1667C15.5833 14.5424 15.4341 14.9027 15.1684 15.1684C14.9027 15.4341 14.5424 15.5833 14.1667 15.5833H12.75" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7.08333 4.25H9.91667" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7.08333 7.08333H9.91667" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7.08333 9.91667H9.91667" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M7.08333 12.75H9.91667" stroke="currentColor" stroke-width="1.41667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <div class="pap-account-company-card__head-copy">
                <div class="pap-account-company-card__title-row">
                  <p class="pap-account-company-card__name"><?php echo esc_html($denumire); ?></p>
                  <?php if ($is_default) : ?>
                    <span class="pap-account-company-card__badge"><?php esc_html_e('Implicită', 'papetarie-storefront'); ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($nr_reg_com !== '') : ?>
                  <p class="pap-account-company-card__regcom"><?php echo esc_html($nr_reg_com); ?></p>
                <?php endif; ?>
              </div>
            </div>

            <div class="pap-account-company-card__body">
              <div class="pap-account-company-card__table">
                <?php foreach ($rows as $row) : ?>
                  <div class="pap-account-company-card__row">
                    <span class="pap-account-company-card__row-label"><?php echo esc_html($row['label']); ?></span>
                    <span class="pap-account-company-card__row-value"><?php echo esc_html($row['value']); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="pap-account-company-card__actions">
                <a
                  class="pap-account-company-card__action-btn"
                  href="<?php echo esc_url($edit_url_company); ?>"
                  data-company-book-open-modal
                  data-company-book-mode="edit"
                  data-company-book-id="<?php echo esc_attr((string) ($company['id'] ?? '')); ?>"
                  data-company-book-entry="<?php echo esc_attr($company_json ?: '{}'); ?>"
                >
                  <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
                </a>
                <form method="post" action="<?php echo esc_url($delete_action_url); ?>" data-company-delete-form>
                  <?php wp_nonce_field('pap_company_book_save', 'pap_company_book_nonce'); ?>
                  <input type="hidden" name="pap_company_book_action" value="delete">
                  <input type="hidden" name="pap_company_id" value="<?php echo esc_attr((string) ($company['id'] ?? '')); ?>">
                  <button
                    type="submit"
                    class="pap-account-company-card__action-btn pap-account-company-card__action-btn--muted"
                  >
                    <?php esc_html_e('Șterge', 'papetarie-storefront'); ?>
                  </button>
                </form>
              </div>

              <?php if (!$is_default) : ?>
                <form method="post" action="<?php echo esc_url($delete_action_url); ?>" class="pap-account-company-card__default-form">
                  <?php wp_nonce_field('pap_company_book_save', 'pap_company_book_nonce'); ?>
                  <input type="hidden" name="pap_company_book_action" value="set_default">
                  <input type="hidden" name="pap_company_id" value="<?php echo esc_attr((string) ($company['id'] ?? '')); ?>">
                  <button type="submit" class="pap-account-company-card__default-link">
                    <?php esc_html_e('Setează ca implicită', 'papetarie-storefront'); ?>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>

        <a class="pap-account-company-card pap-account-company-card--add" href="<?php echo esc_url($add_url); ?>" data-company-book-open-modal data-company-book-mode="add">
          <span class="pap-account-company-card--add__icon" aria-hidden="true">
            <svg viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3.75 9H14.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 3.75V14.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <?php esc_html_e('Adaugă firmă', 'papetarie-storefront'); ?>
        </a>
      </div>
      </div>
    <?php endif; ?>
    </article>
  </section>

  <?php if (function_exists('papetarie_storefront_company_book_render_modal_html')) : ?>
    <?php echo papetarie_storefront_company_book_render_modal_html($modal_company, $mode, in_array($mode, ['add', 'edit'], true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <?php endif; ?>
</div>
