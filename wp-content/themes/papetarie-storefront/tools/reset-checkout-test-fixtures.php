<?php

/**
 * Reset checkout test fixtures to the baseline used by QA.
 *
 * Run from the WordPress container, for example:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/reset-checkout-test-fixtures.php
 */

require '/var/www/html/wp-load.php';

if (!function_exists('get_user_by')) {
    fwrite(STDERR, "WordPress bootstrap failed.\n");
    exit(1);
}

global $wpdb;

$baseline = [
    'checkout.oneaddress@test.local' => [
        'password' => 'Steauab23.',
        'display_name' => 'Cristian Editat Diaconescu',
        'meta' => [
            'first_name' => 'Cristian Editat',
            'last_name' => 'Diaconescu',
            'billing_first_name' => 'Cristian Editat',
            'billing_last_name' => 'Diaconescu',
            'billing_email' => 'checkout.oneaddress@test.local',
            'billing_phone' => '0736628325',
            'billing_country' => 'RO',
            'billing_state' => 'CJ',
            'billing_city' => 'Huedin',
            'billing_postcode' => '405400',
            'billing_address_1' => 'Aleea Editata 21',
            'billing_address_2' => '',
            'shipping_first_name' => 'Cristian Editat',
            'shipping_last_name' => 'Diaconescu',
            'shipping_phone' => '0736628325',
            'shipping_country' => 'RO',
            'shipping_state' => 'CJ',
            'shipping_city' => 'Huedin',
            'shipping_postcode' => '405400',
            'shipping_address_1' => 'Aleea Editata 21',
            'shipping_address_2' => '',
        ],
        'book' => [
            [
                'id' => 'test-one-cluj',
                'label' => '',
                'first_name' => 'Cristian Editat',
                'last_name' => 'Diaconescu',
                'phone' => '0736628325',
                'company' => '',
                'country' => 'RO',
                'state' => 'CJ',
                'city' => 'Huedin',
                'postcode' => '405400',
                'address_1' => 'Aleea Editata 21',
                'address_2' => '',
                'created_at' => '2026-06-25T11:02:03+00:00',
                'updated_at' => '2026-06-25T11:02:03+00:00',
                'source' => '',
            ],
        ],
        'default' => 'test-one-cluj',
    ],
    'checkout.multiaddress@test.local' => [
        'password' => 'Steauab23.',
        'display_name' => 'Cristian Diaconescu',
        'meta' => [
            'first_name' => 'Cristian',
            'last_name' => 'Diaconescu',
            'billing_first_name' => 'Cristian',
            'billing_last_name' => 'Diaconescu',
            'billing_email' => 'checkout.multiaddress@test.local',
            'billing_phone' => '0736628325',
            'billing_country' => 'RO',
            'billing_state' => 'CJ',
            'billing_city' => 'Huedin',
            'billing_postcode' => '405400',
            'billing_address_1' => 'Bd. Revizuit 77',
            'billing_address_2' => '',
            'shipping_first_name' => 'Cristian',
            'shipping_last_name' => 'Diaconescu',
            'shipping_phone' => '0736628325',
            'shipping_country' => 'RO',
            'shipping_state' => 'CJ',
            'shipping_city' => 'Huedin',
            'shipping_postcode' => '405400',
            'shipping_address_1' => 'Bd. Revizuit 77',
            'shipping_address_2' => '',
        ],
        'book' => [
            [
                'id' => 'test-multi-cluj',
                'label' => '',
                'first_name' => 'Edit Multi',
                'last_name' => 'Diaconescu',
                'phone' => '0736628325',
                'company' => '',
                'country' => 'RO',
                'state' => 'CJ',
                'city' => 'Huedin',
                'postcode' => '405400',
                'address_1' => 'Bd. Revizuit 77',
                'address_2' => '',
                'created_at' => '2026-06-25T09:06:43+00:00',
                'updated_at' => '2026-06-25T09:06:43+00:00',
                'source' => '',
            ],
            [
                'id' => 'test-multi-bucuresti',
                'label' => '',
                'first_name' => 'Cristian',
                'last_name' => 'Diaconescu',
                'phone' => '0740123456',
                'company' => '',
                'country' => 'RO',
                'state' => 'B',
                'city' => 'Sector 1',
                'postcode' => '010061',
                'address_1' => 'Victoriei 45',
                'address_2' => '',
                'created_at' => '2026-06-25T07:34:36+00:00',
                'updated_at' => '2026-06-25T07:34:36+00:00',
                'source' => '',
            ],
            [
                'id' => 'addr_b8f92984-2aaf-475c-bbf3-2be041b492fb',
                'label' => '',
                'first_name' => 'Adresa Noua',
                'last_name' => 'Diaconescu',
                'phone' => '0736628325',
                'company' => '',
                'country' => 'RO',
                'state' => 'CJ',
                'city' => 'Huedin',
                'postcode' => '405400',
                'address_1' => 'Strada Noua 55',
                'address_2' => '',
                'created_at' => '2026-06-25T09:02:55+00:00',
                'updated_at' => '2026-06-25T09:02:55+00:00',
                'source' => '',
            ],
        ],
        'default' => 'test-multi-cluj',
    ],
    'checkout.noaddress@test.local' => [
        'password' => 'Steauab23.',
        'display_name' => 'Checkout No Address',
        'clear' => [
            'first_name',
            'last_name',
            'billing_first_name',
            'billing_last_name',
            'billing_phone',
            'billing_country',
            'billing_state',
            'billing_city',
            'billing_postcode',
            'billing_address_1',
            'billing_address_2',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_phone',
            'shipping_country',
            'shipping_state',
            'shipping_city',
            'shipping_postcode',
            'shipping_address_1',
            'shipping_address_2',
            'papetarie_address_book',
            'papetarie_default_address_id',
            'papetarie_checkout_selected_address_shipping',
            'papetarie_checkout_selected_address_billing',
            'papetarie_address_book_form_state',
        ],
    ],
];

$requested_emails = [];
if (PHP_SAPI === 'cli' && !empty($argv) && is_array($argv) && count($argv) > 1) {
    $requested_emails = array_values(array_filter(array_map(static function ($value): string {
        return sanitize_email((string) $value);
    }, array_slice($argv, 1)), static fn(string $email): bool => $email !== ''));
}

if (!empty($requested_emails)) {
    $baseline = array_intersect_key($baseline, array_flip($requested_emails));
}

foreach ($baseline as $email => $config) {
    $user = get_user_by('email', $email);
    if (!$user) {
        $user = get_user_by('login', $email);
    }
    if (!$user) {
        $password = (string) ($config['password'] ?? 'Steauab23.');
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            echo 'missing:' . $email . ' (' . $user_id->get_error_message() . ")\n";
            continue;
        }
        $user = get_user_by('id', (int) $user_id);
        if (!$user instanceof WP_User) {
            echo "missing:$email\n";
            continue;
        }
    } elseif (!empty($config['password'])) {
        wp_set_password((string) $config['password'], $user->ID);
    }

    $user_id = (int) $user->ID;
    $display_name = (string) ($config['display_name'] ?? $email);

    if ($user->user_email !== $email) {
        wp_update_user([
            'ID' => $user_id,
            'user_email' => $email,
        ]);
    }

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name,
        'first_name' => trim(strtok($display_name, ' ') ?: $display_name),
        'last_name' => trim(str_replace(trim(strtok($display_name, ' ') ?: $display_name), '', $display_name)),
        'role' => 'customer',
    ]);

    if (!empty($config['clear'])) {
        foreach ($config['clear'] as $meta_key) {
            delete_user_meta($user_id, $meta_key);
        }
        update_user_meta($user_id, 'billing_email', $email);
        update_user_meta($user_id, 'billing_country', 'RO');
        update_user_meta($user_id, 'shipping_country', 'RO');
    }

    if (!empty($config['meta'])) {
        foreach ($config['meta'] as $meta_key => $value) {
            update_user_meta($user_id, $meta_key, $value);
        }
    }

    if (array_key_exists('book', $config)) {
        update_user_meta($user_id, 'papetarie_address_book', $config['book']);
    }

    if (array_key_exists('default', $config)) {
        update_user_meta($user_id, 'papetarie_default_address_id', $config['default']);
    }
}

$wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_sessions");

echo "checkout test fixtures reset\n";
