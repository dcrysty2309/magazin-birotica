<?php
/**
 * My Address
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in() || !function_exists('WC') || !WC() || !(WC()->customer instanceof WC_Customer)) {
    echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea adresa ta.', 'papetarie-storefront') . '</p>';
    return;
}

$mode = function_exists('papetarie_storefront_address_book_current_mode')
    ? papetarie_storefront_address_book_current_mode()
    : 'add';
$edit_id = function_exists('papetarie_storefront_address_book_current_id')
    ? papetarie_storefront_address_book_current_id()
    : '';

// Single source of truth for this whole page: the address-book's own
// default entry (its get_all() seeds one from the legacy billing/shipping
// user meta the first time it's called if none exists yet). The view, the
// "Editează" pre-fill and "Șterge" all have to agree on the same record —
// reading the view from a separate billing/shipping snapshot while
// edit/delete act on the address-book entry meant Șterge could appear to
// do nothing (it removed the entry, but the view kept reading the
// untouched legacy meta) and Editează could open pre-filled with different
// data than what the card displayed.
$active_address = function_exists('papetarie_storefront_address_book_default_address')
    ? papetarie_storefront_address_book_default_address(get_current_user_id())
    : null;

$has_address = !empty($active_address) && trim((string) ($active_address['address_1'] ?? '')) !== '';
$full_name = '';
$lines = [];

if ($has_address) {
    $lines = function_exists('papetarie_storefront_address_book_format_lines')
        ? papetarie_storefront_address_book_format_lines($active_address)
        : [];
    $full_name = array_shift($lines) ?: '';

    $phone = trim((string) ($active_address['phone'] ?? ''));
    if ($phone !== '') {
        $lines[] = $phone;
    }
}

if (!$active_address) {
    $active_address = function_exists('papetarie_storefront_address_book_empty_entry')
        ? papetarie_storefront_address_book_empty_entry()
        : [];

    // Prima adresa a contului: pre-completam prenume/nume din cont (ca la
    // eMAG/Amazon) doar ca punct de plecare comod - ramane un camp editabil,
    // spre deosebire de email, pentru ca destinatarul unei livrari poate fi
    // altcineva decat titularul contului (cadou, livrare la birou etc.).
    $current_account_user = wp_get_current_user();
    if ($current_account_user instanceof WP_User && $current_account_user->ID > 0) {
        $active_address['first_name'] = trim((string) $current_account_user->first_name);
        $active_address['last_name'] = trim((string) $current_account_user->last_name);
    }
}

if ($edit_id !== '' && function_exists('papetarie_storefront_address_book_get')) {
    $stored_address = papetarie_storefront_address_book_get(get_current_user_id(), $edit_id);
    if (!empty($stored_address)) {
        $active_address = $stored_address;
    }
}

if (function_exists('papetarie_storefront_address_book_get_form_state')) {
    $active_address = array_merge($active_address, papetarie_storefront_address_book_get_form_state());
}

$active_address_json = wp_json_encode($active_address, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$edit_url = function_exists('papetarie_storefront_address_book_form_url')
    ? papetarie_storefront_address_book_form_url(['pap_address_action' => 'edit', 'pap_address_id' => (string) ($active_address['id'] ?? ''), 'pap_address_type' => 'shipping'])
    : '';
$add_url = function_exists('papetarie_storefront_address_book_form_url')
    ? papetarie_storefront_address_book_form_url(['pap_address_action' => 'add', 'pap_address_type' => 'shipping'])
    : '';
$delete_action_url = function_exists('papetarie_storefront_address_book_base_url') ? papetarie_storefront_address_book_base_url() : '';
?>

<div class="pap-account-page pap-account-page--addresses">
  <section class="pap-account-panel pap-account-panel--addresses">
    <article class="pap-account-address-card pap-account-address-card--single">
      <div class="pap-account-address-card__head">
        <h3><?php esc_html_e('Adresa mea', 'papetarie-storefront'); ?></h3>
      </div>

      <?php if (function_exists('papetarie_storefront_render_account_notice')) : ?>
        <?php papetarie_storefront_render_account_notice(); ?>
      <?php endif; ?>

      <?php if ($has_address) : ?>
        <div class="pap-account-address-card__body">
          <div class="pap-account-address-card__content">
            <?php if ($full_name !== '') : ?>
              <p class="pap-account-address-card__name"><?php echo esc_html($full_name); ?></p>
            <?php endif; ?>
            <div class="pap-account-address-card__lines">
              <?php foreach ($lines as $line) : ?>
                <p><?php echo esc_html($line); ?></p>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="pap-account-address-card__actions">
            <a
              class="pap-account-row-action"
              href="<?php echo esc_url($edit_url); ?>"
              data-address-book-open-modal
              data-address-book-mode="edit"
              data-address-book-id="<?php echo esc_attr((string) ($active_address['id'] ?? '')); ?>"
              data-address-book-entry="<?php echo esc_attr($active_address_json ?: '{}'); ?>"
            >
              <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
            </a>
            <form method="post" action="<?php echo esc_url($delete_action_url); ?>" data-address-delete-form>
              <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
              <input type="hidden" name="pap_address_book_action" value="delete">
              <input type="hidden" name="pap_address_id" value="<?php echo esc_attr((string) ($active_address['id'] ?? '')); ?>">
              <input type="hidden" name="pap_address_type" value="shipping">
              <button
                type="submit"
                class="pap-account-row-action pap-account-row-action--danger"
                onclick="return confirm('<?php echo esc_js(__('Sigur vrei să ștergi această adresă?', 'papetarie-storefront')); ?>');"
              >
                <?php esc_html_e('Șterge', 'papetarie-storefront'); ?>
              </button>
            </form>
          </div>
        </div>
      <?php else : ?>
        <div class="pap-account-address-card__empty">
          <div class="pap-account-address-card__empty-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s7-5.4 7-12a7 7 0 1 0-14 0c0 6.6 7 12 7 12z"></path>
              <circle cx="12" cy="10" r="2.8"></circle>
            </svg>
          </div>
          <div class="pap-account-address-card__empty-copy">
            <h4><?php esc_html_e('Nicio adresă salvată', 'papetarie-storefront'); ?></h4>
            <p><?php esc_html_e('Adaugă o adresă pentru a accelera plasarea comenzilor.', 'papetarie-storefront'); ?></p>
          </div>
          <a
            class="pap-account-primary-button pap-account-address-card__empty-action"
            href="<?php echo esc_url($add_url); ?>"
            data-address-book-open-modal
            data-address-book-mode="add"
          >+ <?php esc_html_e('Adaugă adresă', 'papetarie-storefront'); ?></a>
        </div>
      <?php endif; ?>
    </article>
  </section>

  <?php if (function_exists('papetarie_storefront_address_book_render_modal_html')) : ?>
    <?php echo papetarie_storefront_address_book_render_modal_html($active_address, $mode, in_array($mode, ['add', 'edit'], true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <?php endif; ?>
</div>
