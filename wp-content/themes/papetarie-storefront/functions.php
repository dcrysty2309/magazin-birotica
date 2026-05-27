<?php

declare(strict_types=1);

function papetarie_storefront_setup(): void
{
    add_theme_support(
        'custom-logo',
        [
            'height' => 120,
            'width' => 320,
            'flex-height' => true,
            'flex-width' => true,
        ]
    );

    register_nav_menus(
        [
            'top-links' => __('Top Links', 'papetarie-storefront'),
            'primary' => __('Primary Menu', 'papetarie-storefront'),
            'utility' => __('Utility Menu', 'papetarie-storefront'),
            'footer-shop' => __('Footer Shop', 'papetarie-storefront'),
            'footer-categories' => __('Footer Categories', 'papetarie-storefront'),
            'footer-help' => __('Footer Help', 'papetarie-storefront'),
            'footer-about' => __('Footer About', 'papetarie-storefront'),
        ]
    );
}
add_action('after_setup_theme', 'papetarie_storefront_setup');

function papetarie_storefront_enqueue_styles(): void
{
    wp_enqueue_style(
        'storefront-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme('storefront')->get('Version')
    );

    wp_enqueue_style(
        'papetarie-storefront-style',
        get_stylesheet_uri(),
        ['storefront-parent-style'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_styles');

function papetarie_storefront_body_class(array $classes): array
{
    $classes[] = 'theme-papetarie';

    return $classes;
}
add_filter('body_class', 'papetarie_storefront_body_class');

function papetarie_storefront_cart_count(): string
{
    if (!function_exists('WC') || !WC()->cart) {
        return '0';
    }

    return (string) WC()->cart->get_cart_contents_count();
}

function papetarie_storefront_cart_total(): string
{
    if (!function_exists('WC') || !WC()->cart) {
        return '$0.00';
    }

    return wp_strip_all_tags((string) WC()->cart->get_cart_subtotal());
}
