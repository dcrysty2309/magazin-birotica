<?php

defined('ABSPATH') || exit;

function papetarie_storefront_newsletter_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'pap_newsletter_subscribers';
}

function papetarie_storefront_newsletter_install(): void
{
    if (get_option('pap_newsletter_db_version') === '1') {
        return;
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = papetarie_storefront_newsletter_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('pap_newsletter_db_version', '1');
}
add_action('init', 'papetarie_storefront_newsletter_install');

function papetarie_storefront_newsletter_subscribe(): void
{
    check_ajax_referer('pap_newsletter_subscribe', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if ($email === '' || !is_email($email)) {
        wp_send_json_error(['message' => __('Adresa de email nu este validă.', 'papetarie-storefront')], 400);
    }

    global $wpdb;
    $table = papetarie_storefront_newsletter_table_name();

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE email = %s", $email));
    if ($existing) {
        wp_send_json_success(['message' => __('Ești deja abonat — mulțumim!', 'papetarie-storefront')]);
    }

    $inserted = $wpdb->insert($table, ['email' => $email, 'created_at' => current_time('mysql')], ['%s', '%s']);

    if (!$inserted) {
        wp_send_json_error(['message' => __('A apărut o eroare. Încearcă din nou.', 'papetarie-storefront')], 500);
    }

    wp_send_json_success(['message' => __('Te-ai abonat cu succes! Mulțumim.', 'papetarie-storefront')]);
}
add_action('wp_ajax_pap_newsletter_subscribe', 'papetarie_storefront_newsletter_subscribe');
add_action('wp_ajax_nopriv_pap_newsletter_subscribe', 'papetarie_storefront_newsletter_subscribe');

function papetarie_storefront_enqueue_newsletter_script(): void
{
    $script_path = get_stylesheet_directory() . '/assets/js/newsletter.js';
    $version = file_exists($script_path) ? (string) filemtime($script_path) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-newsletter',
        get_stylesheet_directory_uri() . '/assets/js/newsletter.js',
        [],
        $version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-newsletter',
        'papStorefrontNewsletter',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pap_newsletter_subscribe'),
            'genericError' => __('A apărut o eroare. Încearcă din nou.', 'papetarie-storefront'),
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_newsletter_script');
