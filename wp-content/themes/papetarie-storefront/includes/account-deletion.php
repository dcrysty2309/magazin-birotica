<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Cerere de stergere cont (GDPR - dreptul la stergerea datelor). Nu sterge
 * automat comenzile/facturile - legea contabilitatii (82/1991) obliga
 * pastrarea documentelor fiscale ~10 ani, iar GDPR insusi excepteaza de la
 * stergere datele pastrate pentru o obligatie legala (art. 17(3)(b)). Deci
 * fluxul e: blocam contul si stergem imediat datele "sigure" (adrese, firme
 * salvate), trimitem o notificare pe contact@notix.ro ca cineva de la magazin
 * sa finalizeze manual (anonimizare/stergere completa) in max. 30 de zile,
 * cat cere GDPR. Decizie confirmata de user 2026-08-31.
 */
function papetarie_storefront_account_deletion_locked_meta_key(): string
{
    return '_pap_account_deletion_locked';
}

function papetarie_storefront_account_deletion_requested_at_meta_key(): string
{
    return '_pap_account_deletion_requested_at';
}

function papetarie_storefront_account_is_locked_for_deletion(int $user_id): bool
{
    return (bool) get_user_meta($user_id, papetarie_storefront_account_deletion_locked_meta_key(), true);
}

/**
 * Blocheaza login-ul pentru un cont deja marcat pentru stergere - fara asta,
 * doar distrugerea sesiunilor curente nu ar opri userul sa se logheze din
 * nou imediat dupa.
 */
function papetarie_storefront_block_locked_account_login($user)
{
    if (!($user instanceof WP_User)) {
        return $user;
    }

    if (papetarie_storefront_account_is_locked_for_deletion($user->ID)) {
        return new WP_Error(
            'pap_account_locked_for_deletion',
            __('Acest cont este în curs de ștergere, la cererea ta. Dacă ai nevoie de ajutor, scrie-ne la contact@notix.ro.', 'papetarie-storefront')
        );
    }

    return $user;
}
add_filter('authenticate', 'papetarie_storefront_block_locked_account_login', 30, 1);

function papetarie_storefront_handle_account_deletion_request(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Trebuie să fii autentificat.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap_account_deletion', 'nonce');

    $user = wp_get_current_user();
    $user_id = $user->ID;
    $full_name = trim($user->first_name . ' ' . $user->last_name);
    if ($full_name === '') {
        $full_name = $user->display_name;
    }

    $admin_email = (string) apply_filters('papetarie_storefront_account_deletion_notify_email', 'contact@notix.ro');
    $requested_at = current_time('d.m.Y H:i');

    $admin_subject = sprintf(
        /* translators: %s: emailul clientului */
        __('[Notix] Cerere de ștergere cont — %s', 'papetarie-storefront'),
        $user->user_email
    );
    $admin_body = implode("\n", [
        __('Un client a solicitat ștergerea contului (GDPR).', 'papetarie-storefront'),
        '',
        sprintf(__('Nume: %s', 'papetarie-storefront'), $full_name),
        sprintf(__('Email: %s', 'papetarie-storefront'), $user->user_email),
        sprintf(__('ID utilizator: %d', 'papetarie-storefront'), $user_id),
        sprintf(__('Data cererii: %s', 'papetarie-storefront'), $requested_at),
        '',
        __('Contul a fost blocat automat (nu se mai poate autentifica) și datele de profil (adrese, firme salvate) au fost șterse automat.', 'papetarie-storefront'),
        __('Comenzile și facturile au fost păstrate, conform obligației legale de arhivare fiscală (10 ani) - dreptul la ștergere nu se aplică datelor păstrate pentru o obligație legală (GDPR art. 17(3)(b)).', 'papetarie-storefront'),
        __('Verifică manual și finalizează cererea (anonimizare completă a contului) în cel mult 30 de zile, conform GDPR.', 'papetarie-storefront'),
    ]);

    wp_mail($admin_email, $admin_subject, $admin_body, ['Content-Type: text/plain; charset=UTF-8']);

    $user_subject = __('Am primit cererea ta de ștergere a contului — Notix', 'papetarie-storefront');
    $user_body = implode("\n", [
        __('Salut,', 'papetarie-storefront'),
        '',
        __('Am primit cererea ta de ștergere a contului Notix și am blocat deja accesul la el.', 'papetarie-storefront'),
        __('Datele tale de profil au fost șterse.', 'papetarie-storefront'),
        '',
        __('Dacă ai întrebări, scrie-ne la contact@notix.ro.', 'papetarie-storefront'),
        '',
        __('Echipa Notix', 'papetarie-storefront'),
    ]);

    wp_mail($user->user_email, $user_subject, $user_body, ['Content-Type: text/plain; charset=UTF-8']);

    update_user_meta($user_id, papetarie_storefront_account_deletion_locked_meta_key(), '1');
    update_user_meta($user_id, papetarie_storefront_account_deletion_requested_at_meta_key(), current_time('mysql'));

    if (function_exists('papetarie_storefront_address_book_save_all')) {
        papetarie_storefront_address_book_save_all($user_id, [], '');
        if (function_exists('papetarie_storefront_address_book_default_id_meta_key')) {
            delete_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key());
        }
    }

    if (function_exists('papetarie_storefront_company_book_save_all')) {
        papetarie_storefront_company_book_save_all($user_id, [], '');
        if (function_exists('papetarie_storefront_company_book_default_id_meta_key')) {
            delete_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key());
        }
    }

    if (class_exists('WP_Session_Tokens')) {
        WP_Session_Tokens::get_instance($user_id)->destroy_all();
    }

    wp_send_json_success([
        'message' => __('Cererea ta a fost înregistrată și contul a fost blocat. Vei fi delogat acum.', 'papetarie-storefront'),
        'redirectUrl' => home_url('/'),
    ]);
}
add_action('wp_ajax_pap_account_deletion_request', 'papetarie_storefront_handle_account_deletion_request');

/**
 * Vizibilitate in wp-admin pentru cererile de stergere - pana acum singurul
 * loc unde apareau era emailul trimis pe contact@notix.ro, nimic persistent
 * de verificat ulterior in admin. Adauga o coloana pe Utilizatori + un link
 * de filtrare rapida ("Cereri de stergere (N)"), fara sa construim o pagina
 * separata. Semnalat de user 2026-08-31.
 */
function papetarie_storefront_account_deletion_users_column(array $columns): array
{
    $columns['pap_deletion_request'] = __('Cerere ștergere', 'papetarie-storefront');

    return $columns;
}
add_filter('manage_users_columns', 'papetarie_storefront_account_deletion_users_column');

function papetarie_storefront_account_deletion_users_column_content(string $output, string $column_name, int $user_id): string
{
    if ($column_name !== 'pap_deletion_request') {
        return $output;
    }

    if (!papetarie_storefront_account_is_locked_for_deletion($user_id)) {
        return '—';
    }

    $requested_at = get_user_meta($user_id, papetarie_storefront_account_deletion_requested_at_meta_key(), true);
    $requested_display = $requested_at ? mysql2date('d.m.Y H:i', (string) $requested_at) : '';

    return sprintf(
        '<span style="color:#b42318;font-weight:600;">%s</span><br><small>%s</small>',
        esc_html__('Blocat, în așteptare', 'papetarie-storefront'),
        esc_html($requested_display)
    );
}
add_filter('manage_users_custom_column', 'papetarie_storefront_account_deletion_users_column_content', 10, 3);

function papetarie_storefront_account_deletion_sortable_column(array $columns): array
{
    $columns['pap_deletion_request'] = 'pap_deletion_request';

    return $columns;
}
add_filter('manage_users_sortable_columns', 'papetarie_storefront_account_deletion_sortable_column');

function papetarie_storefront_account_deletion_users_query(WP_User_Query $query): void
{
    if (!is_admin()) {
        return;
    }

    if ($query->get('orderby') === 'pap_deletion_request') {
        $query->set('meta_key', papetarie_storefront_account_deletion_requested_at_meta_key());
        $query->set('orderby', 'meta_value');
    }

    if (isset($_GET['pap_deletion_requests']) && $_GET['pap_deletion_requests'] === '1') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query->set('meta_key', papetarie_storefront_account_deletion_locked_meta_key());
        $query->set('meta_value', '1');
    }
}
// "pre_get_users" (nu "pre_user_query") - fireste inainte ca WP_User_Query
// sa construiasca SQL-ul (WHERE/ORDER BY) in prepare_query(). "pre_user_query"
// fireste mult mai tarziu, chiar inainte de rularea SQL-ului deja construit -
// ->set() acolo n-ar mai avea niciun efect asupra rezultatelor.
add_action('pre_get_users', 'papetarie_storefront_account_deletion_users_query');

function papetarie_storefront_account_deletion_users_view(array $views): array
{
    $count = (int) (new WP_User_Query([
        'meta_key' => papetarie_storefront_account_deletion_locked_meta_key(),
        'meta_value' => '1',
        'fields' => 'ID',
        'number' => -1,
    ]))->get_total();

    if ($count < 1) {
        return $views;
    }

    $is_active = isset($_GET['pap_deletion_requests']) && $_GET['pap_deletion_requests'] === '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $url = add_query_arg(['pap_deletion_requests' => '1'], admin_url('users.php'));

    $views['pap_deletion_requests'] = sprintf(
        '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
        esc_url($url),
        $is_active ? ' class="current" aria-current="page"' : '',
        esc_html__('Cereri de ștergere', 'papetarie-storefront'),
        $count
    );

    return $views;
}
add_filter('views_users', 'papetarie_storefront_account_deletion_users_view');
