<?php

declare(strict_types=1);

require '/var/www/html/wp-load.php';

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

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

if (!is_plugin_active('blocksy-companion/blocksy-companion.php') && file_exists(WP_PLUGIN_DIR . '/blocksy-companion/blocksy-companion.php')) {
    activate_plugin('blocksy-companion/blocksy-companion.php');
}

switch_theme('blocksy');

echo 'Theme: ' . wp_get_theme()->get('Name') . PHP_EOL;
echo 'Companion active: ' . (is_plugin_active('blocksy-companion/blocksy-companion.php') ? 'yes' : 'no') . PHP_EOL;
echo 'Front page: ' . $homePageId . PHP_EOL;
