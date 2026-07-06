<?php

declare(strict_types=1);

require '/var/www/html/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/theme-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

add_filter(
    'filesystem_method',
    static function (): string {
        return 'direct';
    }
);

WP_Filesystem();

$skin = new Automatic_Upgrader_Skin();

function papetarie_install_plugin(string $packageUrl): void
{
    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $result = $upgrader->install($packageUrl);

    if ($result !== true) {
        throw new RuntimeException('Plugin install failed: ' . $packageUrl . ' result=' . var_export($result, true));
    }
}

function papetarie_install_theme(string $packageUrl): void
{
    $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin());
    $result = $upgrader->install($packageUrl);

    if ($result !== true) {
        throw new RuntimeException('Theme install failed: ' . $packageUrl . ' result=' . var_export($result, true));
    }
}

if (!file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
    papetarie_install_plugin('https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip');
}

if (!wp_get_theme('storefront')->exists()) {
    papetarie_install_theme('https://downloads.wordpress.org/theme/storefront.latest-stable.zip');
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    activate_plugin('woocommerce/woocommerce.php');
}

if (class_exists('WooCommerce\\Admin\\Features\\Features')) {
    update_option('woocommerce_allow_tracking', 'no');
}

if (class_exists('WC_Install')) {
    WC_Install::create_pages();
}

papetarie_setup_test_page_shortcode('cart', '[woocommerce_cart]');
papetarie_setup_test_page_shortcode('checkout', '[woocommerce_checkout]');
papetarie_setup_test_page_shortcode('myaccount', '[woocommerce_my_account]');

$homePageId = (int) get_option('page_on_front');

if ($homePageId <= 0) {
    $homePageId = wp_insert_post(
        [
            'post_title' => 'Acasa',
            'post_name' => 'acasa',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]
    );
}

update_option('show_on_front', 'page');
update_option('page_on_front', $homePageId);
update_option('blog_public', '0');
update_option('woocommerce_onboarding_profile', ['completed' => true]);

switch_theme('papetarie-storefront');

function papetarie_setup_test_gateway(string $gateway_id, array $settings): void
{
    $option_key = 'woocommerce_' . $gateway_id . '_settings';
    $current_settings = get_option($option_key, []);
    if (!is_array($current_settings)) {
        $current_settings = [];
    }

    $merged_settings = array_merge($current_settings, $settings);
    if (!array_key_exists('enabled', $settings)) {
        $merged_settings['enabled'] = 'yes';
    }

    update_option($option_key, $merged_settings);
}

function papetarie_setup_test_option(string $option_key, $value): void
{
    update_option($option_key, $value);
}

function papetarie_setup_test_page_shortcode(string $page_key, string $shortcode): void
{
    if (!function_exists('wc_get_page_id')) {
        return;
    }

    $page_id = (int) wc_get_page_id($page_key);
    if ($page_id <= 0) {
        return;
    }

    $page = get_post($page_id);
    if (!$page instanceof WP_Post) {
        return;
    }

    $expected_content = '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->';
    if (trim((string) $page->post_content) === trim($expected_content)) {
        return;
    }

    wp_update_post([
        'ID' => $page_id,
        'post_content' => $expected_content,
    ]);
}

function papetarie_setup_test_shipping_zone(): void
{
    if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping_Zone')) {
        return;
    }

    $target_zone = null;

    foreach (WC_Shipping_Zones::get_shipping_zones() as $zone) {
        if (!$zone instanceof WC_Shipping_Zone) {
            continue;
        }

        $locations = $zone->get_zone_locations('edit');
        foreach ($locations as $location) {
            if (!is_object($location)) {
                continue;
            }

            if ('country' === $location->type && 'RO' === strtoupper((string) $location->code)) {
                $target_zone = $zone;
                break 2;
            }
        }
    }

    if (!$target_zone instanceof WC_Shipping_Zone) {
        $target_zone = new WC_Shipping_Zone();
        $target_zone->set_zone_name('Romania Test');
        $target_zone->set_zone_order(0);
        $target_zone->save();
    }

    $target_zone->set_zone_name('Romania Test');
    $target_zone->set_zone_order(0);
    $target_zone->set_locations([
        [
            'code' => 'RO',
            'type' => 'country',
        ],
    ]);
    $target_zone->save();

    $allowed_methods = [
        'flat_rate' => [
            'title' => 'Livrare standard',
            'tax_status' => 'none',
            'cost' => '15',
            'type' => 'order',
        ],
        'free_shipping' => [
            'title' => 'Transport gratuit',
            'requires' => 'min_amount',
            'min_amount' => '150',
            'ignore_discounts' => 'no',
        ],
    ];

    foreach ($target_zone->get_shipping_methods(false, 'edit') as $method) {
        if ($method instanceof WC_Shipping_Method && !array_key_exists($method->id, $allowed_methods)) {
            $target_zone->remove_shipping_method($method->instance_id);
        }
    }

    foreach ($allowed_methods as $method_id => $settings) {
        $methods = $target_zone->get_shipping_methods(false, 'edit');
        $instance = null;

        foreach ($methods as $method) {
            if ($method instanceof WC_Shipping_Method && $method->id === $method_id) {
                $instance = $method;
                break;
            }
        }

        if (!$instance instanceof WC_Shipping_Method) {
            $instance_id = $target_zone->add_shipping_method($method_id);
            if ($instance_id > 0) {
                $instance = WC_Shipping_Zones::get_shipping_method($instance_id);
            }
        }

        if (!$instance instanceof WC_Shipping_Method) {
            continue;
        }

        $option_key = $instance->get_instance_option_key();
        if ('' === $option_key) {
            continue;
        }

        $current_method_settings = get_option($option_key, []);
        if (!is_array($current_method_settings)) {
            $current_method_settings = [];
        }

        update_option($option_key, array_merge($current_method_settings, $settings));
    }
}

function papetarie_setup_test_email_settings(): void
{
    update_option('woocommerce_email_from_address', get_option('admin_email'));
    update_option('woocommerce_email_from_name', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));

    foreach ([
        'new_order',
        'cancelled_order',
        'failed_order',
        'customer_on_hold_order',
        'customer_processing_order',
        'customer_completed_order',
        'customer_refunded_order',
        'customer_invoice',
        'customer_note',
    ] as $email_id) {
        $option_key = 'woocommerce_' . $email_id . '_settings';
        $current_settings = get_option($option_key, []);
        if (!is_array($current_settings)) {
            $current_settings = [];
        }

        $current_settings['enabled'] = 'yes';
        update_option($option_key, $current_settings);
    }
}

function papetarie_setup_test_woocommerce_settings(): void
{
    update_option('woocommerce_enable_shipping_calc', 'yes');
    update_option('woocommerce_calc_taxes', 'yes');
    update_option('woocommerce_prices_include_tax', 'no');
    update_option('woocommerce_shipping_tax_class', 'inherit');
    update_option('woocommerce_tax_based_on', 'shipping');
    update_option('woocommerce_shipping_cost_requires_address', 'no');
    update_option('woocommerce_store_address', 'Strada Exemplu 1');
    update_option('woocommerce_store_address_2', '');
    update_option('woocommerce_store_city', 'București');
    update_option('woocommerce_store_postcode', '010101');
    update_option('woocommerce_default_country', 'RO:B');
    update_option('woocommerce_allowed_countries', 'specific');
    update_option('woocommerce_specific_allowed_countries', ['RO']);
    update_option('woocommerce_ship_to_countries', 'specific');
    update_option('woocommerce_specific_ship_to_countries', ['RO']);
    update_option('woocommerce_default_customer_address', 'base');
    update_option('woocommerce_enable_reviews', 'yes');
    update_option('papetarie_minimum_order_amount', '50');

    $flat_rate_settings = get_option('woocommerce_flat_rate_1_settings', []);
    if (!is_array($flat_rate_settings)) {
        $flat_rate_settings = [];
    }
    $flat_rate_settings['title'] = 'Livrare standard';
    $flat_rate_settings['cost'] = '15';
    update_option('woocommerce_flat_rate_1_settings', $flat_rate_settings);

    $cheque_settings = get_option('woocommerce_cheque_settings', []);
    if (!is_array($cheque_settings)) {
        $cheque_settings = [];
    }
    $cheque_settings['enabled'] = 'no';
    $cheque_settings['title'] = 'Plată offline';
    update_option('woocommerce_cheque_settings', $cheque_settings);

    papetarie_setup_test_gateway('cod', [
        'title' => 'Plată la livrare',
        'description' => 'Plătești când primești comanda.',
        'instructions' => 'Plătești la livrare.',
        'enabled' => 'yes',
        'enable_for_methods' => [],
        'enable_for_virtual' => 'yes',
    ]);
    papetarie_setup_test_gateway('bt-ipay', [
        'title' => 'BT iPay',
        'description' => 'Plată securizată prin BT iPay.',
        'paymentDescription' => 'Comanda {order_number} - {shop_name}',
        'paymentFlow' => 'pay',
        'enabledCardSave' => 'no',
        'testMode' => 'yes',
        'logPayload' => 'no',
        'enabled' => 'yes',
    ]);
    papetarie_setup_test_gateway('bacs', [
        'title' => 'Transfer bancar',
        'description' => 'Plată prin transfer bancar.',
        'instructions' => 'Folosește numărul comenzii ca referință.',
        'enabled' => 'no',
    ]);
    papetarie_setup_test_gateway('cheque', [
        'title' => 'Plată offline',
        'description' => 'Metodă de test fără integrare externă.',
        'instructions' => 'Instrucțiuni pentru plata offline.',
        'enabled' => 'no',
    ]);

    papetarie_setup_test_shipping_zone();
    papetarie_setup_test_email_settings();
}

papetarie_setup_test_woocommerce_settings();

echo 'WooCommerce installed and activated.' . PHP_EOL;
echo 'Storefront installed.' . PHP_EOL;
echo 'Active theme: ' . wp_get_theme()->get('Name') . PHP_EOL;
echo 'Home page ID: ' . $homePageId . PHP_EOL;
