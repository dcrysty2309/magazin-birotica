<?php

declare(strict_types=1);

require '/var/www/html/wp-load.php';

if (!function_exists('wc_create_order') || !class_exists('WC_Order')) {
    fwrite(STDERR, "WooCommerce is not available.\n");
    exit(1);
}

function pap_my_account_seed_user(string $email, string $password, string $displayName): WP_User
{
    $user = get_user_by('email', $email);

    if (!$user instanceof WP_User) {
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            throw new RuntimeException('Cannot create user ' . $email . ': ' . $user_id->get_error_message());
        }
        $user = get_user_by('id', (int) $user_id);
    } else {
        wp_set_password($password, $user->ID);
    }

    if (!$user instanceof WP_User) {
        throw new RuntimeException('Unable to load user ' . $email);
    }

    wp_update_user([
        'ID' => $user->ID,
        'display_name' => $displayName,
        'first_name' => trim(strtok($displayName, ' ') ?: $displayName),
        'last_name' => trim(str_replace(trim(strtok($displayName, ' ') ?: $displayName), '', $displayName)),
        'role' => 'customer',
    ]);

    update_user_meta($user->ID, 'papetarie_wishlist', []);

    return get_user_by('id', $user->ID);
}

function pap_my_account_seed_tax_rate(): int
{
    global $wpdb;

    $existing_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country = %s AND tax_rate = %s AND tax_rate_name = %s LIMIT 1",
            'RO',
            '19.0000',
            'TVA 19%'
        )
    );

    if ($existing_id > 0) {
        return $existing_id;
    }

    return (int) WC_Tax::_insert_tax_rate([
        'tax_rate_country' => 'RO',
        'tax_rate_state' => '',
        'tax_rate' => '19.0000',
        'tax_rate_name' => 'TVA 19%',
        'tax_rate_priority' => 1,
        'tax_rate_compound' => 0,
        'tax_rate_shipping' => 1,
        'tax_rate_order' => 0,
        'tax_rate_class' => '',
    ]);
}

function pap_my_account_seed_products(int $count = 6): array
{
    $product_ids = wc_get_products([
        'status' => 'publish',
        'limit' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids',
    ]);

    if (count($product_ids) < 3) {
        throw new RuntimeException('Need at least 3 published products to seed account orders.');
    }

    return array_map('intval', $product_ids);
}

function pap_my_account_seed_delete_customer_orders(int $customer_id): void
{
    $order_ids = wc_get_orders([
        'customer_id' => $customer_id,
        'limit' => -1,
        'return' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    foreach ($order_ids as $order_id) {
        $order = wc_get_order((int) $order_id);
        if ($order instanceof WC_Order) {
            $order->delete(true);
        }
    }
}

function pap_my_account_seed_create_order(
    int $customer_id,
    array $product_ids,
    string $status,
    int $days_back,
    bool $with_shipping = false,
    bool $with_tax = false,
    array $quantities = []
): WC_Order {
    $order = wc_create_order(['customer_id' => $customer_id]);
    if (!$order instanceof WC_Order) {
        throw new RuntimeException('Failed to create order for customer ' . $customer_id);
    }

    $customer = get_user_by('id', $customer_id);
    $customer_email = $customer instanceof WP_User ? $customer->user_email : 'qa@example.com';

    $order->set_address([
        'first_name' => 'QA',
        'last_name' => 'Customer',
        'company' => 'QA Labs SRL',
        'email' => $customer_email,
        'phone' => '0712345678',
        'address_1' => 'Strada Testului 10',
        'city' => 'Bucuresti',
        'state' => 'B',
        'postcode' => '010101',
        'country' => 'RO',
    ], 'billing');

    $order->set_address([
        'first_name' => 'QA',
        'last_name' => 'Customer',
        'company' => 'Fan Courier',
        'address_1' => 'Strada Testului 10',
        'city' => 'Bucuresti',
        'state' => 'B',
        'postcode' => '010101',
        'country' => 'RO',
    ], 'shipping');

    $line_product_ids = $product_ids;
    $line_count = min(count($line_product_ids), 3);
    $line_product_ids = array_slice($line_product_ids, 0, $line_count);

    foreach ($line_product_ids as $index => $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            continue;
        }

        $quantity = isset($quantities[$index]) ? max(1, (int) $quantities[$index]) : 1;
        $item_id = $order->add_product($product, $quantity);
        if (!$item_id) {
            throw new RuntimeException('Failed to add product ' . $product_id . ' to seed order.');
        }
    }

    if ($with_shipping) {
        $shipping = new WC_Order_Item_Shipping();
        $shipping->set_method_title('Curier rapid');
        $shipping->set_method_id('fan_courier');
        $shipping->set_total(15);
        $order->add_item($shipping);
    }

    $order->set_payment_method('card');
    $order->set_payment_method_title('Online cu cardul');
    if ($with_shipping) {
        $order->update_meta_data('_payment_method_last4', '4242');
    }

    $order->update_meta_data('_pap_my_account_seed', '1');
    $order->update_meta_data('_pap_my_account_seed_user', (string) $customer_id);
    $order->update_meta_data('_pap_my_account_seed_created', gmdate('c'));
    $order->calculate_totals($with_tax);
    $order->set_status($status);
    $order->set_date_created((new DateTimeImmutable(sprintf('-%d days', max(0, $days_back))))->setTimezone(wp_timezone()));
    $order->save();

    return $order;
}

function pap_my_account_seed_create_set(
    WP_User $user,
    array $product_ids,
    int $order_count,
    array $status_cycle,
    bool $include_shipping_tax_order = false
): array {
    pap_my_account_seed_delete_customer_orders($user->ID);
    $orders = [];

    for ($index = 0; $index < $order_count; $index++) {
        $status = $status_cycle[$index % count($status_cycle)];
        $with_shipping = $include_shipping_tax_order && $index === 0;
        $with_tax = $include_shipping_tax_order && $index === 0;
        $quantities = $with_shipping ? [2, 1, 3] : [1];
        $order = pap_my_account_seed_create_order(
            $user->ID,
            $product_ids,
            $status,
            $index + 1,
            $with_shipping,
            $with_tax,
            $quantities
        );

        if ($with_shipping) {
            echo sprintf("Created seed order %s for %s\n", $order->get_id(), $user->user_email);
        }

        $orders[] = [
            'id' => $order->get_id(),
            'number' => function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($order) : ('#' . $order->get_order_number()),
            'status' => $order->get_status(),
            'total' => (float) $order->get_total(),
            'shipping_total' => (float) $order->get_shipping_total(),
            'tax_total' => (float) $order->get_total_tax(),
            'item_count' => function_exists('papetarie_storefront_account_order_items_count') ? papetarie_storefront_account_order_items_count($order) : (int) $order->get_item_count(),
        ];
    }

    return $orders;
}

$products = pap_my_account_seed_products();
$tax_rate_id = pap_my_account_seed_tax_rate();
update_option('woocommerce_calc_taxes', 'yes');
update_option('woocommerce_prices_include_tax', 'no');

$empty_user = pap_my_account_seed_user('qa.empty.account@example.com', 'QaEmpty123!', 'QA Empty');
$one_user = pap_my_account_seed_user('qa.one.order@example.com', 'QaOneOrder123!', 'QA One');
$five_user = pap_my_account_seed_user('qa.five.orders@example.com', 'QaFiveOrders123!', 'QA Five');
$twenty_user = pap_my_account_seed_user('qa.twenty.orders@example.com', 'QaTwentyOrders123!', 'QA Twenty');

pap_my_account_seed_create_set($empty_user, $products, 0, ['completed']);
$one_orders = pap_my_account_seed_create_set($one_user, $products, 1, ['completed']);
$five_orders = pap_my_account_seed_create_set($five_user, $products, 5, ['completed', 'processing', 'pending', 'cancelled'], true);
$twenty_orders = pap_my_account_seed_create_set($twenty_user, $products, 20, ['completed', 'processing', 'pending', 'cancelled'], true);

$fixture_data = [
    'generated_at' => gmdate('c'),
    'users' => [
        'empty' => [
            'email' => $empty_user->user_email,
            'password' => 'QaEmpty123!',
        ],
        'one' => [
            'email' => $one_user->user_email,
            'password' => 'QaOneOrder123!',
            'orders' => $one_orders,
        ],
        'five' => [
            'email' => $five_user->user_email,
            'password' => 'QaFiveOrders123!',
            'orders' => $five_orders,
        ],
        'twenty' => [
            'email' => $twenty_user->user_email,
            'password' => 'QaTwentyOrders123!',
            'orders' => $twenty_orders,
        ],
    ],
];

$fixture_path = '/var/www/html/wp-content/themes/papetarie-storefront/tools/my-account-fixtures.json';
file_put_contents($fixture_path, wp_json_encode($fixture_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);

echo sprintf("Seed finished. Tax rate ID: %d\n", $tax_rate_id);
