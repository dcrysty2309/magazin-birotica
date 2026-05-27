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

echo 'WooCommerce installed and activated.' . PHP_EOL;
echo 'Storefront installed.' . PHP_EOL;
echo 'Active theme: ' . wp_get_theme()->get('Name') . PHP_EOL;
echo 'Home page ID: ' . $homePageId . PHP_EOL;
