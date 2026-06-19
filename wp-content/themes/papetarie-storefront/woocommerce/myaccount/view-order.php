<?php
/**
 * View Order
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$status_badge = function_exists('papetarie_storefront_account_order_badge_html') ? papetarie_storefront_account_order_badge_html($order) : '';
$order_number = function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($order) : ('#' . $order->get_order_number());
$created = $order->get_date_created();
$created_label = $created ? wp_date('j F Y, H:i', $created->getTimestamp()) : __('Nespecificat', 'papetarie-storefront');
$shipping_method = function_exists('papetarie_storefront_account_shipping_method_label') ? papetarie_storefront_account_shipping_method_label($order) : __('Curier rapid', 'papetarie-storefront');
$shipping_company = function_exists('papetarie_storefront_account_shipping_company_label') ? papetarie_storefront_account_shipping_company_label($order) : __('Fan Courier', 'papetarie-storefront');
$payment_label = function_exists('papetarie_storefront_account_order_payment_suffix') ? papetarie_storefront_account_order_payment_suffix($order) : trim((string) $order->get_payment_method_title());
$items = function_exists('papetarie_storefront_account_order_item_rows') ? papetarie_storefront_account_order_item_rows($order) : [];
$totals = function_exists('papetarie_storefront_account_order_totals_rows') ? papetarie_storefront_account_order_totals_rows($order) : [];
$invoice_url = trim((string) $order->get_meta('_invoice_pdf_url'));
?>

<div class="pap-account-page pap-account-page--view-order">
  <a class="pap-account-back-link" href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
    <span aria-hidden="true">←</span>
    <?php esc_html_e('Înapoi la comenzi', 'papetarie-storefront'); ?>
  </a>

  <header class="pap-account-view-order-head">
    <div class="pap-account-view-order-head__copy">
      <p class="pap-account-page-eyebrow"><?php esc_html_e('Comenzi', 'papetarie-storefront'); ?></p>
      <h1><?php echo esc_html(sprintf(__('Comanda %s', 'papetarie-storefront'), $order_number)); ?></h1>
      <p><?php echo esc_html(sprintf(__('Plasată pe %s', 'papetarie-storefront'), $created_label)); ?></p>
    </div>

    <div class="pap-account-view-order-head__meta">
      <?php echo $status_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <?php if ($invoice_url !== '') : ?>
        <a class="pap-account-invoice-button" href="<?php echo esc_url($invoice_url); ?>" target="_blank" rel="noopener noreferrer">
          <?php esc_html_e('Descarcă factura (PDF)', 'papetarie-storefront'); ?>
        </a>
      <?php endif; ?>
    </div>
  </header>

  <section class="pap-account-order-meta-grid" aria-label="<?php esc_attr_e('Informații comandă', 'papetarie-storefront'); ?>">
    <article class="pap-account-order-meta-card">
      <div class="pap-account-order-meta-card__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('truck-outline'); ?></div>
      <div class="pap-account-order-meta-card__copy">
        <h2><?php esc_html_e('Livrare', 'papetarie-storefront'); ?></h2>
        <strong><?php echo esc_html($shipping_method); ?></strong>
        <span><?php echo esc_html($shipping_company); ?></span>
      </div>
    </article>

    <article class="pap-account-order-meta-card">
      <div class="pap-account-order-meta-card__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('paper'); ?></div>
      <div class="pap-account-order-meta-card__copy">
        <h2><?php esc_html_e('Metodă de plată', 'papetarie-storefront'); ?></h2>
        <strong><?php echo esc_html($payment_label ?: __('Plată online', 'papetarie-storefront')); ?></strong>
        <span><?php echo esc_html($order->get_payment_method_title() ?: __('Online cu cardul', 'papetarie-storefront')); ?></span>
      </div>
    </article>
  </section>

  <section class="pap-account-panel pap-account-panel--items">
    <div class="pap-account-panel-head">
      <div class="pap-account-panel-head__copy">
        <h2><?php esc_html_e('Produse comandate', 'papetarie-storefront'); ?></h2>
      </div>
    </div>

    <div class="pap-account-order-items-table">
      <div class="pap-account-order-items-table__head" aria-hidden="true">
        <span><?php esc_html_e('Produs', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Preț unitar', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Cantitate', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
      </div>

      <div class="pap-account-order-items-table__body">
        <?php foreach ($items as $item) : ?>
          <article class="pap-account-order-item">
            <div class="pap-account-order-item__product">
              <?php if (!empty($item['image'])) : ?>
                <?php echo wp_get_attachment_image((int) $item['image'], 'thumbnail', false, ['class' => 'pap-account-order-item__thumb', 'loading' => 'lazy']); ?>
              <?php else : ?>
                <span class="pap-account-order-item__thumb pap-account-order-item__thumb--placeholder" aria-hidden="true"><?php echo papetarie_storefront_icon('paper'); ?></span>
              <?php endif; ?>
              <div class="pap-account-order-item__copy">
                <strong><?php echo esc_html($item['name']); ?></strong>
                <?php if (!empty($item['sku'])) : ?>
                  <span><?php echo esc_html(sprintf(__('SKU: %s', 'papetarie-storefront'), $item['sku'])); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="pap-account-order-item__cell"><?php echo esc_html($item['unit_price'] ?? ''); ?></div>
            <div class="pap-account-order-item__cell"><?php echo esc_html($item['quantity'] ?? '1'); ?></div>
            <div class="pap-account-order-item__cell pap-account-order-item__cell--total"><?php echo esc_html($item['total'] ?? ''); ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="pap-account-panel pap-account-panel--totals">
    <div class="pap-account-totals">
      <?php foreach ($totals as $total_row) : ?>
        <div class="pap-account-totals__row<?php echo !empty($total_row['label']) && $total_row['label'] === __('Total comandă', 'papetarie-storefront') ? ' is-total' : ''; ?>">
          <span><?php echo esc_html($total_row['label'] ?? ''); ?></span>
          <strong><?php echo esc_html($total_row['value'] ?? ''); ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
