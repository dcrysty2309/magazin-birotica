<?php

defined('ABSPATH') || exit;

function papetarie_storefront_newsletter_admin_menu(): void
{
    add_menu_page(
        __('Newsletter', 'papetarie-storefront'),
        __('Newsletter', 'papetarie-storefront'),
        'manage_options',
        'pap-newsletter',
        'papetarie_storefront_newsletter_admin_page',
        'dashicons-email-alt',
        58
    );
}
add_action('admin_menu', 'papetarie_storefront_newsletter_admin_menu');

function papetarie_storefront_newsletter_admin_page(): void
{
    global $wpdb;
    $table = papetarie_storefront_newsletter_table_name();

    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $rows = $wpdb->get_results("SELECT email, created_at FROM {$table} ORDER BY created_at DESC LIMIT 500");
    $export_url = wp_nonce_url(admin_url('admin-post.php?action=pap_newsletter_export'), 'pap_newsletter_export');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Abonați newsletter', 'papetarie-storefront'); ?></h1>
        <p>
            <?php
            printf(
                /* translators: %d: numar de abonati */
                esc_html(_n('%d abonat în total.', '%d abonați în total.', $count, 'papetarie-storefront')),
                (int) $count
            );
            ?>
        </p>
        <p>
            <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">
                <?php esc_html_e('Exportă CSV', 'papetarie-storefront'); ?>
            </a>
        </p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email', 'papetarie-storefront'); ?></th>
                    <th><?php esc_html_e('Data abonării', 'papetarie-storefront'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="2"><?php esc_html_e('Niciun abonat încă.', 'papetarie-storefront'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function papetarie_storefront_newsletter_export(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Acces interzis.', 'papetarie-storefront'));
    }

    check_admin_referer('pap_newsletter_export');

    global $wpdb;
    $table = papetarie_storefront_newsletter_table_name();
    $rows = $wpdb->get_results("SELECT email, created_at FROM {$table} ORDER BY created_at ASC");

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-notix-' . gmdate('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'data abonare']);
    foreach ($rows as $row) {
        fputcsv($out, [$row->email, $row->created_at]);
    }
    fclose($out);
    exit;
}
add_action('admin_post_pap_newsletter_export', 'papetarie_storefront_newsletter_export');
