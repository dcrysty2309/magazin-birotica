<?php
/**
 * Login Form
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

papetarie_storefront_render_auth_login_shell([
    'context' => 'page',
    'show_visual' => true,
    'show_register' => true,
    'id_prefix' => 'pap-auth-page-',
]);
