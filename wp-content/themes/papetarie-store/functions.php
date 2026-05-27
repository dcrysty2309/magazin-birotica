<?php

declare(strict_types=1);

function papetarie_store_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');

    register_nav_menus(
        [
            'primary' => __('Primary Menu', 'papetarie-store'),
        ]
    );
}
add_action('after_setup_theme', 'papetarie_store_setup');

function papetarie_store_assets(): void
{
    wp_enqueue_style(
        'papetarie-store-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'papetarie_store_assets');
