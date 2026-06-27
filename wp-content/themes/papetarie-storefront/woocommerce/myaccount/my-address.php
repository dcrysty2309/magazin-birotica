<?php
/**
 * My Addresses
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in() || !function_exists('WC') || !WC() || !WC()->customer instanceof WC_Customer) {
    echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea adresele salvate.', 'papetarie-storefront') . '</p>';
    return;
}

$customer = WC()->customer;
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$edit_address_url = static function (string $type) use ($account_url): string {
    if (function_exists('wc_get_endpoint_url')) {
        return wc_get_endpoint_url('edit-address', $type, $account_url);
    }

    return add_query_arg(['edit-address' => $type], $account_url);
};

$address_fields = static function (string $type) use ($customer): array {
    $is_billing = $type === 'billing';
    $first_name = $is_billing ? $customer->get_billing_first_name() : $customer->get_shipping_first_name();
    $last_name = $is_billing ? $customer->get_billing_last_name() : $customer->get_shipping_last_name();
    $company = $is_billing ? $customer->get_billing_company() : $customer->get_shipping_company();
    $address_1 = $is_billing ? $customer->get_billing_address_1() : $customer->get_shipping_address_1();
    $address_2 = $is_billing ? $customer->get_billing_address_2() : $customer->get_shipping_address_2();
    $city = $is_billing ? $customer->get_billing_city() : $customer->get_shipping_city();
    $state = $is_billing ? $customer->get_billing_state() : $customer->get_shipping_state();
    $postcode = $is_billing ? $customer->get_billing_postcode() : $customer->get_shipping_postcode();
    $country = $is_billing ? $customer->get_billing_country() : $customer->get_shipping_country();

    $lines = [];
    $name = trim($first_name . ' ' . $last_name);
    if ($name !== '') {
        $lines[] = $name;
    }
    if ($company !== '') {
        $lines[] = $company;
    }
    if ($address_1 !== '') {
        $lines[] = $address_1;
    }
    if ($address_2 !== '') {
        $lines[] = $address_2;
    }
    $city_line = trim(implode(', ', array_filter([$city, $state, $postcode])));
    if ($city_line !== '') {
        $lines[] = $city_line;
    }
    if ($country !== '' && $country !== 'RO') {
        $lines[] = $country;
    }

    return $lines;
};

$addresses = [
    'billing' => [
        'title' => __('Adresă de facturare', 'papetarie-storefront'),
        'url' => $edit_address_url('billing'),
        'lines' => $address_fields('billing'),
    ],
    'shipping' => [
        'title' => __('Adresă de livrare', 'papetarie-storefront'),
        'url' => $edit_address_url('shipping'),
        'lines' => $address_fields('shipping'),
    ],
];
?>

<div class="pap-account-page pap-account-page--addresses">
  <?php papetarie_storefront_render_account_page_head(
      __('Adrese', 'papetarie-storefront'),
      __('Gestionează adresa standard de facturare și adresa standard de livrare din cont.', 'papetarie-storefront')
  ); ?>

  <section class="pap-account-panel pap-account-panel--addresses">
    <div class="pap-account-address-grid pap-account-address-grid--standard">
      <?php foreach ($addresses as $address) : ?>
        <article class="pap-account-address-card">
          <div class="pap-account-address-card__head">
            <div class="pap-account-address-card__head-copy">
              <h3><?php echo esc_html($address['title']); ?></h3>
            </div>
            <a class="pap-account-row-action" href="<?php echo esc_url($address['url']); ?>">
              <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
            </a>
          </div>

          <div class="pap-account-address-card__content">
            <?php if (!empty($address['lines'])) : ?>
              <?php foreach ($address['lines'] as $line) : ?>
                <p><?php echo esc_html($line); ?></p>
              <?php endforeach; ?>
            <?php else : ?>
              <p><?php esc_html_e('Nu există încă o adresă completată.', 'papetarie-storefront'); ?></p>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>
