<?php
/**
 * My Account Dashboard
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$user = wp_get_current_user();
$user_id = (int) $user->ID;

$last_orders = function_exists('papetarie_storefront_account_customer_orders')
    ? papetarie_storefront_account_customer_orders($user_id, ['limit' => 1])
    : [];
$last_order = null;
foreach ($last_orders as $candidate) {
    if ($candidate instanceof WC_Order) {
        $last_order = $candidate;
        break;
    }
}

$default_address = function_exists('papetarie_storefront_address_book_default_address')
    ? papetarie_storefront_address_book_default_address($user_id)
    : null;
$has_address = !empty($default_address) && trim((string) ($default_address['address_1'] ?? '')) !== '';
// Aceleasi randuri etichetate ca pe "Adresa mea" (my-address.php) - acolo
// blocul de text curgator fara etichete a fost semnalat ca neclar de user
// 2026-08-31, iar minicard-ul asta e acelasi tip de continut, doar in alt
// loc din cont.
$address_field_rows = [];

if ($has_address) {
    $company = trim((string) ($default_address['company'] ?? ''));
    $full_name = trim(trim((string) ($default_address['first_name'] ?? '')) . ' ' . trim((string) ($default_address['last_name'] ?? '')));
    if ($company !== '') {
        $address_field_rows[] = [__('Firmă', 'papetarie-storefront'), $company];
    }
    if ($full_name !== '') {
        $address_field_rows[] = [__('Nume', 'papetarie-storefront'), $full_name];
    }

    $phone = trim((string) ($default_address['phone'] ?? ''));
    if ($phone !== '') {
        $address_field_rows[] = [__('Telefon', 'papetarie-storefront'), $phone];
    }

    $state_code = strtoupper(sanitize_key((string) ($default_address['state'] ?? '')));
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $state_label = $state_code !== '' && isset($counties[$state_code]) ? $counties[$state_code] : $state_code;
    if ($state_label !== '') {
        $address_field_rows[] = [__('Județ', 'papetarie-storefront'), $state_label];
    }

    $city = trim((string) ($default_address['city'] ?? ''));
    if ($city !== '') {
        $address_field_rows[] = [__('Localitate', 'papetarie-storefront'), $city];
    }

    $address_full = trim((string) ($default_address['address_1'] ?? ''));
    $address_2 = trim((string) ($default_address['address_2'] ?? ''));
    if ($address_2 !== '') {
        $address_full .= ', ' . $address_2;
    }
    if ($address_full !== '') {
        $address_field_rows[] = [__('Adresă', 'papetarie-storefront'), $address_full];
    }

    $postcode = trim((string) ($default_address['postcode'] ?? ''));
    if ($postcode !== '') {
        $address_field_rows[] = [__('Cod poștal', 'papetarie-storefront'), $postcode];
    }

    $delivery_notes = trim((string) ($default_address['delivery_notes'] ?? ''));
    if ($delivery_notes !== '') {
        $address_field_rows[] = [__('Observații', 'papetarie-storefront'), $delivery_notes];
    }
}

$addresses_url = function_exists('wc_get_endpoint_url') ? wc_get_endpoint_url('edit-address') : '';
$orders_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : '';
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
?>

<div class="pap-account-page pap-account-page--dashboard">
  <div class="pap-account-dashboard-grid">
    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Ultima comandă', 'papetarie-storefront'); ?></h2>
        <?php if ($last_order instanceof WC_Order) : ?>
          <a href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('Toate comenzile', 'papetarie-storefront'); ?></a>
        <?php endif; ?>
      </div>
      <div class="pap-account-minicard__body<?php echo $last_order instanceof WC_Order ? '' : ' pap-account-minicard__body--empty'; ?>">
        <?php if ($last_order instanceof WC_Order) : ?>
          <?php
          $order_number = function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($last_order) : ('#' . $last_order->get_order_number());
          $items_preview = function_exists('papetarie_storefront_account_order_items_preview') ? papetarie_storefront_account_order_items_preview($last_order, 3) : ['items' => [], 'visible' => 3];
          $preview_items_all = $items_preview['items'];
          $preview_visible_count = (int) $items_preview['visible'];
          $preview_remaining = max(0, count($preview_items_all) - $preview_visible_count);
          ?>
          <div class="pap-account-minicard__order-row">
            <strong><?php echo esc_html($order_number); ?></strong>
            <?php echo function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($last_order) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <span class="pap-account-minicard__order-date">
              <?php echo esc_html($last_order->get_date_created() ? wp_date('j M Y, H:i', $last_order->get_date_created()->getTimestamp()) : ''); ?>
            </span>
          </div>
          <?php if (!empty($preview_items_all)) : ?>
            <ul class="pap-account-minicard__items-preview" data-order-items-preview>
              <?php foreach ($preview_items_all as $preview_index => $preview_item) : ?>
                <li
                  class="pap-account-minicard__item-row<?php echo $preview_index >= $preview_visible_count ? ' pap-account-minicard__item-row--extra' : ''; ?>"
                  <?php echo $preview_index >= $preview_visible_count ? 'hidden' : ''; ?>
                >
                  <span class="pap-account-minicard__item-thumb">
                    <img src="<?php echo esc_url($preview_item['image']); ?>" alt="" loading="lazy">
                  </span>
                  <span class="pap-account-minicard__item-name"><?php echo esc_html($preview_item['name']); ?></span>
                  <span class="pap-account-minicard__item-qty">×<?php echo esc_html((string) $preview_item['quantity']); ?></span>
                </li>
              <?php endforeach; ?>
              <?php if ($preview_remaining > 0) : ?>
                <?php
                $preview_expand_label = sprintf(
                    /* translators: %d: remaining product count */
                    _n('încă %d produs', 'încă %d produse', $preview_remaining, 'papetarie-storefront'),
                    $preview_remaining
                );
                $preview_collapse_label = __('Vezi mai puțin', 'papetarie-storefront');
                ?>
                <li class="pap-account-minicard__item-row pap-account-minicard__item-row--more">
                  <button
                    type="button"
                    class="pap-account-minicard__items-toggle"
                    data-order-items-toggle
                    data-expand-label="<?php echo esc_attr($preview_expand_label); ?>"
                    data-collapse-label="<?php echo esc_attr($preview_collapse_label); ?>"
                  >
                    <span class="pap-account-minicard__item-thumb pap-account-minicard__item-thumb--more" aria-hidden="true">+<?php echo esc_html((string) $preview_remaining); ?></span>
                    <span class="pap-account-minicard__item-name" data-order-items-toggle-label><?php echo esc_html($preview_expand_label); ?></span>
                  </button>
                </li>
              <?php endif; ?>
            </ul>
          <?php endif; ?>
          <div class="pap-account-minicard__footer">
            <div class="pap-account-minicard__footer-total">
              <span class="pap-account-minicard__footer-label"><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
              <strong><?php echo wp_kses_post(function_exists('papetarie_storefront_format_plain_currency_amount') ? papetarie_storefront_format_plain_currency_amount((float) $last_order->get_total()) : $last_order->get_formatted_order_total()); ?></strong>
            </div>
            <a class="pap-account-minicard__footer-button" href="<?php echo esc_url($last_order->get_view_order_url()); ?>"><?php esc_html_e('Vezi detalii', 'papetarie-storefront'); ?> →</a>
          </div>
        <?php else : ?>
          <p class="pap-account-minicard__empty-text"><?php esc_html_e('Nicio comandă încă.', 'papetarie-storefront'); ?></p>
          <a class="pap-account-minicard__empty-action" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Explorează magazinul', 'papetarie-storefront'); ?> →</a>
        <?php endif; ?>
      </div>
    </article>

    <article class="pap-account-minicard">
      <div class="pap-account-minicard__head">
        <h2><?php esc_html_e('Adresă livrare', 'papetarie-storefront'); ?></h2>
        <a href="<?php echo esc_url($addresses_url); ?>"><?php echo $has_address ? esc_html__('Modifică', 'papetarie-storefront') : esc_html__('Adaugă', 'papetarie-storefront'); ?></a>
      </div>
      <div class="pap-account-minicard__body<?php echo $has_address ? '' : ' pap-account-minicard__body--empty'; ?>">
        <?php if ($has_address) : ?>
          <div class="pap-account-address-card__content">
            <?php foreach ($address_field_rows as $field_row) : ?>
              <div class="pap-account-address-card__field">
                <span class="pap-account-address-card__field-label"><?php echo esc_html($field_row[0]); ?>:</span>
                <span class="pap-account-address-card__field-value"><?php echo esc_html($field_row[1]); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else : ?>
          <p class="pap-account-minicard__empty-text"><?php esc_html_e('Nicio adresă salvată.', 'papetarie-storefront'); ?></p>
          <a class="pap-account-minicard__empty-action" href="<?php echo esc_url($addresses_url); ?>">+ <?php esc_html_e('Adaugă adresă', 'papetarie-storefront'); ?> →</a>
        <?php endif; ?>
      </div>
    </article>
  </div>
</div>
