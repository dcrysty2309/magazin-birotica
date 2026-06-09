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
            'top-links' => __('Linkuri sus', 'papetarie-storefront'),
            'primary' => __('Meniu principal', 'papetarie-storefront'),
            'utility' => __('Meniu utilitar', 'papetarie-storefront'),
            'footer-shop' => __('Footer magazin', 'papetarie-storefront'),
            'footer-categories' => __('Footer categorii', 'papetarie-storefront'),
            'footer-help' => __('Footer ajutor', 'papetarie-storefront'),
            'footer-about' => __('Footer despre noi', 'papetarie-storefront'),
        ]
    );
}
add_action('after_setup_theme', 'papetarie_storefront_setup');

function papetarie_storefront_widgets_init(): void
{
    register_sidebar(
        [
            'name' => __('Footer newsletter', 'papetarie-storefront'),
            'id' => 'footer-newsletter',
            'description' => __('Widget area pentru un bloc de newsletter in footer.', 'papetarie-storefront'),
            'before_widget' => '<section class="pap-footer-newsletter-widget">',
            'after_widget' => '</section>',
            'before_title' => '<h3 class="pap-footer-widget-title">',
            'after_title' => '</h3>',
        ]
    );
}
add_action('widgets_init', 'papetarie_storefront_widgets_init');

if (is_admin()) {
    require_once __DIR__ . '/admin-category-ordering.php';
}

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

    wp_enqueue_style(
        'papetarie-storefront-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        [],
        '6.5.2'
    );

    wp_enqueue_style(
        'papetarie-storefront-open-sans',
        'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_styles');

function papetarie_storefront_enqueue_archive_scripts(): void
{
    if (!(is_shop() || is_product_category() || is_product_taxonomy())) {
        return;
    }

    wp_enqueue_script(
        'papetarie-storefront-archive-filters',
        get_stylesheet_directory_uri() . '/assets/js/archive-filters.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_archive_scripts');

function papetarie_storefront_enqueue_archive_add_to_cart_script(): void
{
    wp_enqueue_script(
        'papetarie-storefront-archive-add-to-cart',
        get_stylesheet_directory_uri() . '/assets/js/archive-add-to-cart.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script(
        'papetarie-storefront-archive-add-to-cart',
        'papStorefrontAddToCart',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pap_home_add_to_cart'),
            'action' => 'pap_home_add_to_cart',
            'drawerNonce' => wp_create_nonce('pap_cart_drawer'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
            'shopUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'),
        ]
    );

    wp_add_inline_script(
        'papetarie-storefront-archive-add-to-cart',
        "(function () {\n  window.papSetActionBusy = function () {};\n  window.papClearActionBusy = function () {};\n  var status = document.querySelector('[data-pap-action-status]');\n  if (status && status.parentNode) {\n    status.parentNode.removeChild(status);\n  }\n})();",
        'after'
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_archive_add_to_cart_script');

function papetarie_storefront_enqueue_header_menu_script(): void
{
    wp_enqueue_script(
        'papetarie-storefront-header-menu',
        get_stylesheet_directory_uri() . '/assets/js/header-menu.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_header_menu_script');

function papetarie_storefront_enqueue_cart_drawer_script(): void
{
    wp_enqueue_script(
        'papetarie-storefront-cart-drawer',
        get_stylesheet_directory_uri() . '/assets/js/cart-drawer.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script(
        'papetarie-storefront-cart-drawer',
        'papCartDrawer',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pap_cart_drawer'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
            'checkoutUrl' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
            'texts' => [
                'refreshError' => __('Nu am putut actualiza coșul.', 'papetarie-storefront'),
                'empty' => __('Coșul este gol.', 'papetarie-storefront'),
                'continue' => __('Continuă cumpărăturile', 'papetarie-storefront'),
            ],
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_cart_drawer_script');

function papetarie_storefront_dequeue_cart_fragments(): void
{
    if (is_admin()) {
        return;
    }

    wp_dequeue_script('wc-cart-fragments');
    wp_deregister_script('wc-cart-fragments');
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_dequeue_cart_fragments', 100);

function papetarie_storefront_cart_fragments(array $fragments): array
{
    ob_start();
    ?>
    <span data-pap-cart-count><?php echo esc_html(papetarie_storefront_cart_count_label()); ?></span>
    <?php
    $fragments['[data-pap-cart-count]'] = ob_get_clean();

    ob_start();
    ?>
    <div class="pap-cart-drawer-content" data-cart-drawer-content>
      <?php papetarie_storefront_render_cart_drawer_items(); ?>
    </div>
    <?php
    $fragments['[data-cart-drawer-content]'] = ob_get_clean();

    ob_start();
    ?>
    <strong data-cart-drawer-total><?php echo function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '—'; ?></strong>
    <?php
    $fragments['[data-cart-drawer-total]'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'papetarie_storefront_cart_fragments');

function papetarie_storefront_enqueue_checkout_scripts(): void
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    wp_enqueue_script(
        'papetarie-storefront-checkout',
        get_stylesheet_directory_uri() . '/assets/js/checkout.js',
        ['jquery'],
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_checkout_scripts');

function papetarie_storefront_enqueue_account_scripts(): void
{
    $should_enqueue = (function_exists('is_account_page') && is_account_page()) || (function_exists('is_checkout') && is_checkout());

    if (!$should_enqueue) {
        return;
    }

    wp_enqueue_script(
        'papetarie-storefront-account-ui',
        get_stylesheet_directory_uri() . '/assets/js/account.js',
        ['jquery', 'wc-password-strength-meter'],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script(
        'papetarie-storefront-account-ui',
        'papAccountUi',
        [
            'loginUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(),
            'socialShortcode' => shortcode_exists('nextend_social_login') ? 'nextend_social_login' : '',
            'googleLoginUrl' => (string) apply_filters('papetarie_storefront_google_login_url', ''),
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_account_scripts');

function papetarie_storefront_enqueue_wishlist_script(): void
{
    if (
        !(
            is_front_page()
            || is_home()
            || is_shop()
            || is_product()
            || is_product_category()
            || is_product_taxonomy()
            || (function_exists('is_account_page') && is_account_page())
        )
    ) {
        return;
    }

    wp_enqueue_script(
        'papetarie-storefront-wishlist',
        get_stylesheet_directory_uri() . '/assets/js/wishlist.js',
        ['jquery'],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script(
        'papetarie-storefront-wishlist',
        'papWishlist',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pap_wishlist_toggle'),
            'loginUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(),
            'messages' => [
                'added' => __('Adăugat la favorite.', 'papetarie-storefront'),
                'removed' => __('Eliminat din favorite.', 'papetarie-storefront'),
                'login' => __('Autentifică-te pentru a salva favoritele.', 'papetarie-storefront'),
                'error' => __('Nu am putut actualiza favoritele.', 'papetarie-storefront'),
            ],
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_wishlist_script');

function papetarie_storefront_send_json_success_fast(array $data, int $status_code = 200): void
{
    if (!headers_sent()) {
        nocache_headers();
        status_header($status_code);
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
    }

    echo wp_json_encode([
        'success' => true,
        'data' => $data,
    ]);

    exit;
}

function papetarie_storefront_body_class(array $classes): array
{
    $classes[] = 'theme-papetarie';

    return $classes;
}
add_filter('body_class', 'papetarie_storefront_body_class');

function papetarie_storefront_hide_account_page_title(bool $show_title): bool
{
    if (function_exists('is_account_page') && is_account_page() && !is_user_logged_in()) {
        return false;
    }

    return $show_title;
}
add_filter('woocommerce_show_page_title', 'papetarie_storefront_hide_account_page_title', 20);

function papetarie_storefront_translate_frontend_strings(string $translated, string $text, string $domain): string
{
    if (is_admin()) {
        return $translated;
    }

    $map = [
        'Login' => 'Autentificare',
        'Register' => 'Creare cont',
        'Lost your password?' => 'Ai uitat parola?',
        'Username or email address' => 'Email',
        'Username or email' => 'Email',
        'Invalid email address.' => 'Introdu o adresă de email validă.',
        'Invalid username or email.' => 'Emailul nu există în baza de date.',
        'Password' => 'Parolă',
        'Remember me' => 'Ține-mă minte',
        'Log in' => 'Autentificare',
        'Reset password' => 'Resetare parolă',
        'New password' => 'Parolă nouă',
        'Re-enter new password' => 'Confirmă parola',
        'Password reset email has been sent.' => 'Un email a fost trimis cu succes. Verifică inboxul.',
        'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.' => 'Un email a fost trimis cu succes. Verifică inboxul.',
        'Your password reset link appears to be invalid. Please request a new link below.' => 'Linkul de resetare pare invalid. Cere un link nou.',
        'Your password reset link has expired. Please request a new link below.' => 'Linkul de resetare a expirat. Cere un link nou.',
        'This key is invalid or has already been used. Please reset your password again if needed.' => 'Linkul de resetare este invalid sau a fost deja folosit. Cere un link nou.',
        'Password reset is not allowed for this user' => 'Resetarea parolei nu este permisă pentru acest utilizator.',
        'Your password has been reset successfully.' => 'Parola a fost schimbată cu succes. Te poți autentifica din nou.',
        'My account' => 'Contul meu',
        'Dashboard' => 'Panou',
        'Orders' => 'Comenzile mele',
        'Addresses' => 'Adrese',
        'Account details' => 'Detalii cont',
        'Downloads' => 'Descărcări',
        'Payment methods' => 'Metode de plată',
        'Logout' => 'Deconectare',
        'View cart' => 'Vezi coșul',
        'Proceed to checkout' => 'Mergi la finalizare',
        'Cart' => 'Coș',
        'Checkout' => 'Finalizare comandă',
        'Subtotal' => 'Subtotal',
        'Total' => 'Total',
        'Update cart' => 'Actualizează coșul',
        'Apply coupon' => 'Aplică cuponul',
        'Quantity' => 'Cantitate',
        'Billing details' => 'Date de facturare',
        'Shipping details' => 'Date de livrare',
        'Place order' => 'Plasează comanda',
        'Please enter a valid email address.' => 'Introdu o adresă de email validă.',
        'Please enter a valid account username.' => 'Introdu un nume de utilizator valid.',
        'Please enter a password.' => 'Introdu parola.',
        'Passwords do not match.' => 'Parolele nu se potrivesc.',
        'An account is already registered with your email address. Please log in.' => 'Există deja un cont cu această adresă de email. Te rugăm să te autentifici.',
        'Please enter a valid account username and/or password.' => 'Introdu un nume de utilizator și/sau o parolă validă.',
    ];

    return $map[$text] ?? $translated;
}
add_filter('gettext', 'papetarie_storefront_translate_frontend_strings', 20, 3);

function papetarie_storefront_unhook_auth_notices(): void
{
    if (is_admin()) {
        return;
    }

    $is_auth_page = (function_exists('is_account_page') && is_account_page())
        || (function_exists('is_lost_password_page') && is_lost_password_page());

    if (!$is_auth_page) {
        return;
    }

    remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
    remove_action('woocommerce_before_lost_password_form', 'woocommerce_output_all_notices', 10);
    remove_action('woocommerce_before_reset_password_form', 'woocommerce_output_all_notices', 10);
}
add_action('wp', 'papetarie_storefront_unhook_auth_notices', 20);

function papetarie_storefront_stock_status_options(): array
{
    if (function_exists('wc_get_product_stock_status_options')) {
        return wc_get_product_stock_status_options();
    }

    return [
        'instock' => __('În stoc', 'papetarie-storefront'),
        'outofstock' => __('Stoc epuizat', 'papetarie-storefront'),
        'onbackorder' => __('În precomandă', 'papetarie-storefront'),
    ];
}

function papetarie_storefront_romania_counties(): array
{
    return [
        'AB' => __('Alba', 'papetarie-storefront'),
        'AR' => __('Arad', 'papetarie-storefront'),
        'AG' => __('Argeș', 'papetarie-storefront'),
        'BC' => __('Bacău', 'papetarie-storefront'),
        'BH' => __('Bihor', 'papetarie-storefront'),
        'BN' => __('Bistrița-Năsăud', 'papetarie-storefront'),
        'BT' => __('Botoșani', 'papetarie-storefront'),
        'BR' => __('Brăila', 'papetarie-storefront'),
        'BV' => __('Brașov', 'papetarie-storefront'),
        'B' => __('București', 'papetarie-storefront'),
        'BZ' => __('Buzău', 'papetarie-storefront'),
        'CS' => __('Caraș-Severin', 'papetarie-storefront'),
        'CL' => __('Călărași', 'papetarie-storefront'),
        'CJ' => __('Cluj', 'papetarie-storefront'),
        'CT' => __('Constanța', 'papetarie-storefront'),
        'CV' => __('Covasna', 'papetarie-storefront'),
        'DB' => __('Dâmbovița', 'papetarie-storefront'),
        'DJ' => __('Dolj', 'papetarie-storefront'),
        'GL' => __('Galați', 'papetarie-storefront'),
        'GR' => __('Giurgiu', 'papetarie-storefront'),
        'GJ' => __('Gorj', 'papetarie-storefront'),
        'HR' => __('Harghita', 'papetarie-storefront'),
        'HD' => __('Hunedoara', 'papetarie-storefront'),
        'IL' => __('Ialomița', 'papetarie-storefront'),
        'IS' => __('Iași', 'papetarie-storefront'),
        'IF' => __('Ilfov', 'papetarie-storefront'),
        'MM' => __('Maramureș', 'papetarie-storefront'),
        'MH' => __('Mehedinți', 'papetarie-storefront'),
        'MS' => __('Mureș', 'papetarie-storefront'),
        'NT' => __('Neamț', 'papetarie-storefront'),
        'OT' => __('Olt', 'papetarie-storefront'),
        'PH' => __('Prahova', 'papetarie-storefront'),
        'SJ' => __('Sălaj', 'papetarie-storefront'),
        'SM' => __('Satu Mare', 'papetarie-storefront'),
        'SB' => __('Sibiu', 'papetarie-storefront'),
        'SV' => __('Suceava', 'papetarie-storefront'),
        'TR' => __('Teleorman', 'papetarie-storefront'),
        'TM' => __('Timiș', 'papetarie-storefront'),
        'TL' => __('Tulcea', 'papetarie-storefront'),
        'VS' => __('Vaslui', 'papetarie-storefront'),
        'VL' => __('Vâlcea', 'papetarie-storefront'),
        'VN' => __('Vrancea', 'papetarie-storefront'),
    ];
}

function papetarie_storefront_romania_cities(): array
{
    return [
        'Alba Iulia', 'Arad', 'Pitești', 'Bacău', 'Oradea', 'Bistrița', 'Botoșani', 'Brașov', 'Brăila', 'București',
        'Buzău', 'Reșița', 'Călărași', 'Cluj-Napoca', 'Constanța', 'Craiova', 'Sfântu Gheorghe', 'Târgoviște', 'Deva',
        'Galați', 'Giurgiu', 'Târgu Jiu', 'Miercurea Ciuc', 'Hunedoara', 'Slobozia', 'Iași', 'Baia Mare',
        'Drobeta-Turnu Severin', 'Târgu Mureș', 'Piatra Neamț', 'Slatina', 'Ploiești', 'Satu Mare', 'Zalău',
        'Sibiu', 'Suceava', 'Alexandria', 'Timișoara', 'Tulcea', 'Vaslui', 'Râmnicu Vâlcea', 'Focșani',
        'Făgăraș', 'Roman', 'Turda', 'Mediaș', 'Lugoj', 'Câmpina', 'Caracal', 'Onești', 'Bârlad', 'Mangalia',
        'Turnu Măgurele', 'Sighetu Marmației', 'Vatra Dornei', 'Tecuci', 'Rădăuți', 'Odorheiu Secuiesc',
        'Curtea de Argeș', 'Năsăud', 'Gherla', 'Sebeș', 'Aiud', 'Huși', 'Câmpulung', 'Dorohoi', 'Călărași',
    ];
}

function papetarie_storefront_price_ranges(): array
{
    return [
        [
            'key' => 'under-50',
            'label' => __('Sub 50 lei', 'papetarie-storefront'),
            'min' => null,
            'max' => 50,
        ],
        [
            'key' => '50-100',
            'label' => __('50 - 100 lei', 'papetarie-storefront'),
            'min' => 50,
            'max' => 100,
        ],
        [
            'key' => '100-200',
            'label' => __('100 - 200 lei', 'papetarie-storefront'),
            'min' => 100,
            'max' => 200,
        ],
        [
            'key' => '200-500',
            'label' => __('200 - 500 lei', 'papetarie-storefront'),
            'min' => 200,
            'max' => 500,
        ],
        [
            'key' => '500-1000',
            'label' => __('500 - 1.000 lei', 'papetarie-storefront'),
            'min' => 500,
            'max' => 1000,
        ],
        [
            'key' => '1000-1500',
            'label' => __('1.000 - 1.500 lei', 'papetarie-storefront'),
            'min' => 1000,
            'max' => 1500,
        ],
        [
            'key' => '1500-2000',
            'label' => __('1.500 - 2.000 lei', 'papetarie-storefront'),
            'min' => 1500,
            'max' => 2000,
        ],
        [
            'key' => '2000-3000',
            'label' => __('2.000 - 3.000 lei', 'papetarie-storefront'),
            'min' => 2000,
            'max' => 3000,
        ],
        [
            'key' => 'over-3000',
            'label' => __('Peste 3.000 lei', 'papetarie-storefront'),
            'min' => 3000,
            'max' => null,
        ],
    ];
}

function papetarie_storefront_get_selected_price_range_keys(): array
{
    $raw = $_GET['price_range'] ?? [];

    if (!is_array($raw)) {
        $raw = array_filter(array_map('trim', explode(',', (string) $raw)));
    }

    $allowed = array_column(papetarie_storefront_price_ranges(), 'key');
    $selected = [];

    foreach ($raw as $item) {
        $key = sanitize_key((string) $item);

        if ($key && in_array($key, $allowed, true)) {
            $selected[] = $key;
        }
    }

    return array_values(array_unique($selected));
}

function papetarie_storefront_get_custom_price_filter(): array
{
    $min_raw = $_GET['custom_price_min'] ?? $_GET['min_price'] ?? '';
    $max_raw = $_GET['custom_price_max'] ?? $_GET['max_price'] ?? '';

    $min = is_numeric($min_raw) ? (float) wp_unslash($min_raw) : null;
    $max = is_numeric($max_raw) ? (float) wp_unslash($max_raw) : null;
    $active = ($min !== null || $max !== null);
    $valid = $active && $min !== null && $max !== null && $min >= 0 && $max >= 0 && $min <= $max;

    return [
        'min' => $min,
        'max' => $max,
        'active' => $active,
        'valid' => $valid,
    ];
}

function papetarie_storefront_build_price_clause(array $range): array
{
    if ($range['min'] === null) {
        return [
            'key' => '_price',
            'value' => (float) $range['max'],
            'compare' => '<',
            'type' => 'NUMERIC',
        ];
    }

    if ($range['max'] === null) {
        return [
            'key' => '_price',
            'value' => (float) $range['min'],
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
    }

    return [
        'key' => '_price',
        'value' => [
            (float) $range['min'],
            (float) $range['max'],
        ],
        'compare' => 'BETWEEN',
        'type' => 'NUMERIC',
    ];
}

function papetarie_storefront_append_meta_query(array $meta_query, array $clause): array
{
    if (empty($meta_query)) {
        return [$clause];
    }

    if (isset($meta_query['relation'])) {
        $meta_query[] = $clause;

        return $meta_query;
    }

    $meta_query[] = $clause;

    return $meta_query;
}

function papetarie_storefront_strip_price_meta_query(array $meta_query): array
{
    if (!$meta_query) {
        return [];
    }

    if (isset($meta_query['relation'])) {
        $relation = $meta_query['relation'];
        $clean = ['relation' => $relation];

        foreach ($meta_query as $key => $clause) {
            if ($key === 'relation') {
                continue;
            }

            if (!is_array($clause)) {
                continue;
            }

            $clean_clause = papetarie_storefront_strip_price_meta_query($clause);

            if ($clean_clause) {
                $clean[] = $clean_clause;
            }
        }

        return count($clean) > 1 ? $clean : [];
    }

    if (isset($meta_query['key']) && $meta_query['key'] === '_price') {
        return [];
    }

    return $meta_query;
}

function papetarie_storefront_get_base_archive_query_args(?\WP_Term $term = null): array
{
    $query = $GLOBALS['wp_query'] ?? null;
    $args = $query instanceof \WP_Query ? $query->query_vars : [];

    $args['post_type'] = 'product';
    $args['post_status'] = 'publish';
    $args['fields'] = 'ids';
    $args['posts_per_page'] = 1;
    $args['no_found_rows'] = false;
    $args['ignore_sticky_posts'] = true;
    $args['cache_results'] = false;
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;
    $args['papetarie_ignore_price_filters'] = true;

    unset(
        $args['paged'],
        $args['page'],
        $args['price_range'],
        $args['custom_price_min'],
        $args['custom_price_max'],
        $args['min_price'],
        $args['max_price']
    );

    if (isset($args['meta_query']) && is_array($args['meta_query'])) {
        $args['meta_query'] = papetarie_storefront_strip_price_meta_query($args['meta_query']);
    }

    return $args;
}

function papetarie_storefront_get_price_range_counts(?\WP_Term $term = null): array
{
    $counts = [];
    $base_args = papetarie_storefront_get_base_archive_query_args($term);

    foreach (papetarie_storefront_price_ranges() as $range) {
        $args = $base_args;
        $args['meta_query'] = papetarie_storefront_append_meta_query(
            $args['meta_query'] ?? [],
            papetarie_storefront_build_price_clause($range)
        );

        $count_query = new \WP_Query($args);
        $counts[$range['key']] = (int) $count_query->found_posts;
    }

    return $counts;
}

function papetarie_storefront_get_archive_price_bounds(?\WP_Term $term = null): array
{
    global $wpdb;

    $table = $wpdb->prefix . 'wc_product_meta_lookup';
    $post_type = 'product';
    $post_status = 'publish';

    $sql = "
        SELECT MIN(lookup.min_price) AS min_price, MAX(lookup.max_price) AS max_price
        FROM {$table} lookup
        INNER JOIN {$wpdb->posts} posts ON posts.ID = lookup.product_id
        WHERE posts.post_type = %s
          AND posts.post_status = %s
    ";

    $params = [$post_type, $post_status];

    if ($term instanceof \WP_Term) {
        $term_ids = array_merge([$term->term_id], get_term_children($term->term_id, 'product_cat'));
        $term_ids = array_map('absint', array_filter($term_ids));

        if ($term_ids) {
            $placeholders = implode(',', array_fill(0, count($term_ids), '%d'));
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM {$wpdb->term_relationships} rel
                    INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id
                    WHERE rel.object_id = posts.ID
                      AND tax.taxonomy = 'product_cat'
                      AND tax.term_id IN ({$placeholders})
                )
            ";
            $params = array_merge($params, $term_ids);
        }
    }

    $query = $wpdb->prepare($sql, $params);
    $bounds = $wpdb->get_row($query, ARRAY_A);

    $min = isset($bounds['min_price']) ? (float) $bounds['min_price'] : 0.0;
    $max = isset($bounds['max_price']) ? (float) $bounds['max_price'] : 0.0;

    if ($max < $min) {
        $max = $min;
    }

    return [
        'min' => $min,
        'max' => $max,
    ];
}

function papetarie_storefront_filter_stock_status_query(array $meta_query, $query): array
{
    if (is_admin()) {
        return $meta_query;
    }

    if (!(is_shop() || is_product_category() || is_product_taxonomy())) {
        return $meta_query;
    }

    $stock_status = isset($_GET['stock_status']) ? sanitize_key(wp_unslash($_GET['stock_status'])) : '';

    if ($stock_status === '' || $stock_status === 'all') {
        return $meta_query;
    }

    $allowed_statuses = array_keys(papetarie_storefront_stock_status_options());

    if (!in_array($stock_status, $allowed_statuses, true)) {
        return $meta_query;
    }

    $meta_query[] = [
        'key' => '_stock_status',
        'value' => $stock_status,
        'compare' => '=',
    ];

    return $meta_query;
}
add_filter('woocommerce_product_query_meta_query', 'papetarie_storefront_filter_stock_status_query', 20, 2);

function papetarie_storefront_filter_price_ranges_query(array $meta_query, $query): array
{
    if (is_admin()) {
        return $meta_query;
    }

    if ($query instanceof \WP_Query && $query->get('papetarie_ignore_price_filters')) {
        return $meta_query;
    }

    if (!(is_shop() || is_product_category() || is_product_taxonomy())) {
        return $meta_query;
    }

    $selected_ranges = papetarie_storefront_get_selected_price_range_keys();
    $custom_price = papetarie_storefront_get_custom_price_filter();
    $price_clauses = [];
    $price_ranges = papetarie_storefront_price_ranges();

    foreach ($selected_ranges as $selected_range_key) {
        foreach ($price_ranges as $range) {
            if ($range['key'] === $selected_range_key) {
                $price_clauses[] = papetarie_storefront_build_price_clause($range);
                break;
            }
        }
    }

    if ($custom_price['valid']) {
        $price_clauses[] = [
            'key' => '_price',
            'value' => [
                (float) $custom_price['min'],
                (float) $custom_price['max'],
            ],
            'compare' => 'BETWEEN',
            'type' => 'NUMERIC',
        ];
    }

    if (!$price_clauses) {
        return $meta_query;
    }

    $price_query = count($price_clauses) === 1
        ? $price_clauses[0]
        : array_merge(['relation' => 'OR'], $price_clauses);

    return papetarie_storefront_append_meta_query($meta_query, $price_query);
}
add_filter('woocommerce_product_query_meta_query', 'papetarie_storefront_filter_price_ranges_query', 30, 2);

function papetarie_storefront_related_products_args(array $args): array
{
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    $args['orderby'] = 'rand';

    return $args;
}
add_filter('woocommerce_output_related_products_args', 'papetarie_storefront_related_products_args');

function papetarie_storefront_add_to_cart_message_html(string $message, $products, bool $show_qty): string
{
    $message = preg_replace('/\s*<a[^>]+class="button wc-forward[^"]*"[^>]*>.*?<\/a>/is', '', $message) ?? $message;
    $message = preg_replace('/\s{2,}/', ' ', $message) ?? $message;

    return trim($message);
}
add_filter('wc_add_to_cart_message_html', 'papetarie_storefront_add_to_cart_message_html', 10, 3);

function papetarie_storefront_remove_storefront_sidebar(): void
{
    if (!function_exists('is_woocommerce') || !is_woocommerce()) {
        return;
    }

    remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
}
add_action('wp', 'papetarie_storefront_remove_storefront_sidebar', 20);

function papetarie_storefront_email_styles(string $css): string
{
    $css .= "
        body, table, td, p, a, span, div {
            font-family: 'Open Sans', Arial, sans-serif !important;
        }
        .email-template-wrapper,
        .email-content {
            border-radius: 0 !important;
        }
        a.button {
            background: #173764 !important;
            border-color: #173764 !important;
            border-radius: 0 !important;
        }
    ";

    return $css;
}
add_filter('woocommerce_email_styles', 'papetarie_storefront_email_styles');

function papetarie_storefront_email_footer_text(): string
{
    return __('Magazin papetărie și birotică.', 'papetarie-storefront');
}
add_filter('woocommerce_email_footer_text', 'papetarie_storefront_email_footer_text');

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

function papetarie_storefront_cart_count_label(): string
{
    $count = (int) papetarie_storefront_cart_count();

    return sprintf(
        _n('%s produs', '%s produse', $count, 'papetarie-storefront'),
        number_format_i18n($count)
    );
}

function papetarie_storefront_cart_drawer_item_html(string $cart_item_key, array $cart_item): string
{
    $product = $cart_item['data'] ?? null;
    if (!$product instanceof WC_Product || !$product->exists() || (int) ($cart_item['quantity'] ?? 0) < 1) {
        return '';
    }

    $quantity = max(1, (int) $cart_item['quantity']);
    $product_id = (int) ($cart_item['product_id'] ?? $product->get_id());
    $product_name = $product->get_name();
    $product_permalink = $product->is_visible() ? $product->get_permalink($cart_item) : '';
    $thumbnail = $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'alt' => $product_name]);

    if (!$thumbnail) {
        $thumbnail = '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
    }

    $variation_html = wc_get_formatted_cart_item_data($cart_item, true);
    ?>
    <article class="pap-cart-drawer-item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" data-cart-item-id="<?php echo esc_attr($product_id); ?>">
      <a class="pap-cart-drawer-thumb" href="<?php echo esc_url($product_permalink ? $product_permalink : '#'); ?>" <?php echo $product_permalink ? '' : 'aria-hidden="true" tabindex="-1"'; ?>>
        <?php echo wp_kses_post($thumbnail); ?>
      </a>
      <div class="pap-cart-drawer-copy">
        <div class="pap-cart-drawer-copy-head">
          <a class="pap-cart-drawer-name" href="<?php echo esc_url($product_permalink ? $product_permalink : '#'); ?>" <?php echo $product_permalink ? '' : 'aria-hidden="true" tabindex="-1"'; ?>><?php echo esc_html($product_name); ?></a>
          <div class="pap-cart-drawer-head-actions">
            <span class="pap-cart-drawer-quantity">x<?php echo esc_html((string) $quantity); ?></span>
            <span class="pap-cart-drawer-line-total"><?php echo wp_kses_post($product->get_price_html()); ?></span>
            <button
              type="button"
              class="pap-cart-drawer-remove"
              data-cart-remove-item
              data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>"
              data-cart-item-name="<?php echo esc_attr($product_name); ?>"
              aria-label="<?php esc_attr_e('Elimină produsul din coș', 'papetarie-storefront'); ?>"
            >
              &times;
            </button>
          </div>
        </div>

        <?php if ($variation_html) : ?>
          <div class="pap-cart-drawer-meta"><?php echo wp_kses_post($variation_html); ?></div>
        <?php endif; ?>
      </div>
    </article>
    <?php

    return '';
}

function papetarie_storefront_render_cart_drawer_items(): void
{
    $cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];

    if (empty($cart)) {
        papetarie_storefront_render_cart_drawer_empty_state();
        return;
    }

    echo '<div class="pap-cart-drawer-items">';
    foreach ($cart as $cart_item_key => $cart_item) {
        echo papetarie_storefront_cart_drawer_item_html((string) $cart_item_key, (array) $cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    echo '</div>';
}

function papetarie_storefront_render_cart_drawer_empty_state(): void
{
    echo '<div class="pap-cart-drawer-empty" aria-live="polite">'
        . '<div class="pap-cart-drawer-empty-inner">'
        . '<div class="pap-cart-drawer-empty-illustration" aria-hidden="true">'
        . '<span class="pap-cart-drawer-empty-circle"></span>'
        . '<i class="fa-solid fa-cart-shopping pap-cart-drawer-empty-icon" aria-hidden="true"></i>'
        . '</div>'
        . '<strong class="pap-cart-drawer-empty-title">' . esc_html__('Coșul tău este gol', 'papetarie-storefront') . '</strong>'
        . '<p class="pap-cart-drawer-empty-message">' . esc_html__('Adaugă produse pentru a începe comanda.') . '<br>' . esc_html__('Poți găsi rapid consumabile, papetărie') . '<br>' . esc_html__('și echipamente de birou.') . '</p>'
        . '<button type="button" class="button pap-cart-drawer-empty-button" data-cart-drawer-empty-continue>'
        . '<i class="fa-solid fa-arrow-right pap-cart-drawer-empty-button-icon" aria-hidden="true"></i>'
        . '<span>' . esc_html__('Continuă cumpărăturile', 'papetarie-storefront') . '</span>'
        . '</button>'
        . '</div>'
        . '</div>';
}

function papetarie_storefront_render_cart_drawer(): void
{
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    ?>
    <div class="pap-cart-drawer" id="pap-cart-drawer" data-cart-drawer hidden aria-hidden="true">
      <div class="pap-cart-drawer-backdrop" data-cart-drawer-close aria-hidden="true"></div>
      <aside class="pap-cart-drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Coșul meu', 'papetarie-storefront'); ?>">
        <header class="pap-cart-drawer-head">
          <div class="pap-cart-drawer-head-copy">
            <h2><?php esc_html_e('Coșul meu', 'papetarie-storefront'); ?></h2>
          </div>
          <button type="button" class="pap-cart-drawer-close" data-cart-drawer-close aria-label="<?php esc_attr_e('Închide coșul', 'papetarie-storefront'); ?>">&times;</button>
        </header>

        <div class="pap-cart-drawer-body">
          <div class="pap-cart-drawer-content" data-cart-drawer-content>
            <?php papetarie_storefront_render_cart_drawer_items(); ?>
          </div>
        </div>

        <footer class="pap-cart-drawer-footer">
          <div class="pap-cart-drawer-summary">
            <div class="pap-cart-drawer-summary-row pap-cart-drawer-summary-row--total">
              <span><?php esc_html_e('Total', 'papetarie-storefront'); ?></span>
              <strong data-cart-drawer-total><?php echo function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : 'â€”'; ?></strong>
            </div>
          </div>

          <div class="pap-cart-drawer-actions">
            <a class="button pap-cart-drawer-button pap-cart-drawer-button--primary" href="<?php echo esc_url($cart_url); ?>"><?php esc_html_e('Vezi detalii coș', 'papetarie-storefront'); ?></a>
          </div>
        </footer>
      </aside>
    </div>
    <?php
}
add_action('wp_footer', 'papetarie_storefront_render_cart_drawer', 5);

function papetarie_storefront_render_cart_success_modal(): void
{
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    ?>
    <div class="pap-cart-modal" data-cart-modal hidden>
      <div class="pap-cart-modal-backdrop" data-cart-modal-close></div>
      <div class="pap-cart-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pap-cart-modal-title">
        <header class="pap-cart-modal-head">
          <div class="pap-cart-modal-head-copy">
            <h3 id="pap-cart-modal-title"><?php esc_html_e('Produsul a fost adăugat în coș', 'papetarie-storefront'); ?></h3>
          </div>
          <button class="pap-cart-modal-dismiss" type="button" aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>" data-cart-modal-close>×</button>
        </header>
        <div class="pap-cart-modal-body">
          <div class="pap-cart-modal-status" aria-hidden="true">
            <span class="pap-cart-modal-status-icon">
              <i class="fa-solid fa-check"></i>
            </span>
          </div>

          <div class="pap-cart-modal-product">
            <div class="pap-cart-modal-thumb" data-cart-modal-thumb hidden>
              <img src="" alt="" data-cart-modal-image>
            </div>
            <div class="pap-cart-modal-copy">
              <strong data-cart-modal-name></strong>
              <span class="pap-cart-modal-quantity" data-cart-modal-quantity></span>
              <span class="pap-cart-modal-price" data-cart-modal-price></span>
            </div>
          </div>
        </div>

        <footer class="pap-cart-modal-actions">
          <button type="button" class="button pap-cart-modal-button pap-cart-modal-button--secondary" data-cart-modal-close><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></button>
          <a class="button pap-cart-modal-button pap-cart-modal-button--primary" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? $cart_url : $shop_url); ?>" data-cart-modal-link><?php esc_html_e('Vezi detalii coș', 'papetarie-storefront'); ?></a>
        </footer>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'papetarie_storefront_render_cart_success_modal', 6);

function papetarie_storefront_render_cart_delete_modal(): void
{
    ?>
    <div class="pap-cart-delete-modal" data-cart-delete-modal hidden aria-hidden="true">
      <div class="pap-cart-delete-modal-backdrop" data-cart-delete-modal-close aria-hidden="true"></div>
      <div class="pap-cart-delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pap-cart-delete-modal-title" aria-describedby="pap-cart-delete-modal-message">
        <header class="pap-cart-delete-modal-head">
          <div class="pap-cart-delete-modal-head-copy">
            <h3 id="pap-cart-delete-modal-title"><?php esc_html_e('Eliminare produs', 'papetarie-storefront'); ?></h3>
          </div>
          <button type="button" class="pap-cart-delete-modal-close" data-cart-delete-modal-close aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>">&times;</button>
        </header>

        <div class="pap-cart-delete-modal-body">
          <div class="pap-cart-delete-modal-icon-shell" aria-hidden="true">
            <i class="fa-solid fa-trash-can pap-cart-delete-modal-icon"></i>
          </div>
          <p id="pap-cart-delete-modal-message" class="pap-cart-delete-modal-message">
            <?php esc_html_e('Sigur dorești să elimini produsul', 'papetarie-storefront'); ?>
            <strong data-cart-delete-modal-name></strong>
            <?php esc_html_e('din coș?', 'papetarie-storefront'); ?>
          </p>
        </div>

        <footer class="pap-cart-delete-modal-actions">
          <button type="button" class="button pap-cart-delete-modal-button pap-cart-delete-modal-button--secondary" data-cart-delete-modal-close data-cart-delete-modal-cancel><?php esc_html_e('Renunță', 'papetarie-storefront'); ?></button>
          <button type="button" class="button pap-cart-delete-modal-button pap-cart-delete-modal-button--primary" data-cart-delete-modal-confirm><?php esc_html_e('Șterge produsul', 'papetarie-storefront'); ?></button>
        </footer>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'papetarie_storefront_render_cart_delete_modal', 6);

function papetarie_storefront_get_cart_drawer_payload(): array
{
    $count = (int) papetarie_storefront_cart_count();
    $count_label = papetarie_storefront_cart_count_label();
    $total = function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '';
    $cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];

    ob_start();
    papetarie_storefront_render_cart_drawer_items();
    $items_html = (string) ob_get_clean();

    return [
        'count' => $count,
        'count_label' => $count_label,
        'total_html' => $total,
        'items_html' => $items_html,
        'has_items' => !empty($cart),
        'is_empty' => empty($cart),
    ];
}

function papetarie_storefront_ajax_cart_drawer_sync(): void
{
    $timing_start = microtime(true);
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(['message' => __('Coșul nu este disponibil momentan.', 'papetarie-storefront')], 400);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_cart_drawer')) {
        wp_send_json_error(['message' => __('Sesiunea a expirat. Reîncarcă pagina.', 'papetarie-storefront')], 403);
    }

    $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'refresh';
    $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field(wp_unslash($_POST['cart_item_key'])) : '';
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : null;

    if ($mode === 'remove') {
        if ($cart_item_key === '' || !WC()->cart->remove_cart_item($cart_item_key)) {
            wp_send_json_error(['message' => __('Nu am putut elimina produsul din coș.', 'papetarie-storefront')], 400);
        }
    } elseif ($mode === 'update') {
        if ($cart_item_key === '') {
            wp_send_json_error(['message' => __('Nu am putut actualiza produsul din coș.', 'papetarie-storefront')], 400);
        }

        $quantity = max(0, (int) $quantity);
        if ($quantity < 1) {
            WC()->cart->remove_cart_item($cart_item_key);
        } else {
            WC()->cart->set_quantity($cart_item_key, $quantity, true);
        }
    }

    $timing_before_response = microtime(true);
    $payload = papetarie_storefront_get_cart_drawer_payload();
    $payload['debug_timings'] = [
        'before_response_ms' => (int) round(($timing_before_response - $timing_start) * 1000),
    ];

    wp_send_json_success($payload);
}
add_action('wp_ajax_pap_cart_drawer_sync', 'papetarie_storefront_ajax_cart_drawer_sync');
add_action('wp_ajax_nopriv_pap_cart_drawer_sync', 'papetarie_storefront_ajax_cart_drawer_sync');

function papetarie_storefront_icon(string $name): string
{
    $icons = [
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 015.98 12.03l4.25 4.24-1.42 1.42-4.24-4.25A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z" fill="currentColor"/></svg>',
        'account' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.69-7.5 6v1h15v-1c0-3.31-3.36-6-7.5-6z" fill="currentColor"/></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l4 4h-3v7h-2V7H8l4-4zm-7 12h14v6H5v-6zm2 2v2h10v-2H7z" fill="currentColor"/></svg>',
        'catalog' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h7v7H4zM13 5h7v7h-7zM4 14h7v5H4zM13 14h7v5h-7z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
        'cart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2.2l2.1 10.5A2 2 0 0 0 9.3 16h7.9a2 2 0 0 0 2-1.6l1.3-7.4H6.2" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 20a1.1 1.1 0 1 0 0-.01M17.5 20a1.1 1.1 0 1 0 0-.01" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v2H4V7zm0 4h16v2H4v-2zm0 4h16v2H4v-2z" fill="currentColor"/></svg>',
        'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z" fill="currentColor"/></svg>',
        'help' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 17a1.25 1.25 0 110-2.5A1.25 1.25 0 0112 19zm1.33-5.94-.58.33c-.94.53-1.25.98-1.25 1.86h-2c0-1.67.79-2.67 2.27-3.5l.76-.43c.78-.44 1.22-1.02 1.22-1.76 0-1.16-.95-1.92-2.39-1.92-1.31 0-2.31.57-3.18 1.64L6.6 7.99C7.77 6.43 9.5 5.5 11.73 5.5c2.77 0 4.85 1.57 4.85 4.1 0 1.5-.75 2.67-3.25 3.46z" fill="currentColor"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.35-9.33-8.42C.59 9.1 2.36 5.5 6.1 5.5c2 0 3.38 1.04 4.28 2.27C11.28 6.54 12.66 5.5 14.66 5.5c3.74 0 5.51 3.6 3.43 7.08C19 16.65 12 21 12 21Z" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l7 3v6c0 4.97-3.06 9.63-7 11-3.94-1.37-7-6.03-7-11V5l7-3zm-1 13l5-5-1.41-1.41L11 12.17l-1.59-1.58L8 12l3 3z" fill="currentColor"/></svg>',
        'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.59 13.41L11 3.83V3H4v7h.83l9.58 9.59a2 2 0 002.83 0l3.35-3.35a2 2 0 000-2.83zM6.5 8A1.5 1.5 0 118 6.5 1.5 1.5 0 016.5 8z" fill="currentColor"/></svg>',
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h11v9h2.17a3 3 0 015.66 1H23v2h-1a3 3 0 11-6 0H9a3 3 0 11-6 0H2v-2h1V5zm13 2v5h3.59L18.09 9H16z" fill="currentColor"/></svg>',
        'truck-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 7.5h9.5V15H3.5V7.5Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M13 10h2.2l2.8 2.8V15H13V10Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M17.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 17.2h1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M15.2 12.2h2.1L20.1 15v1.1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3.5h6a1 1 0 0 1 1 1V6h3v2H5V6h3V4.5a1 1 0 0 1 1-1ZM7.5 8h9l-.55 10.1A2 2 0 0 1 13.96 20h-3.92a2 2 0 0 1-1.99-1.9L7.5 8Zm3 2.1v6.7h1.2v-6.7h-1.2Zm2.8 0v6.7h1.2v-6.7h-1.2Z" fill="currentColor"/></svg>',
        'lock-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 11V8.8a4.5 4.5 0 1 1 9 0V11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><rect x="5.5" y="11" width="13" height="10" rx="1.8" ry="1.8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M12 15.2v2.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'headset-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12a6 6 0 0 1 12 0v5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M4.5 13.2v3a2 2 0 0 0 2 2H8v-7H6.5a2 2 0 0 0-2 2Zm15 0v3a2 2 0 0 1-2 2H16v-7h1.5a2 2 0 0 1 2 2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 19.5h4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'pen' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17.25 10.58-10.59 3.76 3.76L6.75 21H3v-3.75Zm12-9.66 1.41-1.42a2 2 0 0 1 2.83 0l.17.17a2 2 0 0 1 0 2.83L18 10.59 15 7.59Z" fill="currentColor"/></svg>',
        'paper' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'file-lines-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
        'heart-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.8 5.25 14.3A4.9 4.9 0 0 1 4 11.1c0-2.78 2.18-4.9 4.9-4.9 1.5 0 2.78.73 3.6 1.83a4.56 4.56 0 0 1 3.6-1.83c2.72 0 4.9 2.12 4.9 4.9a4.9 4.9 0 0 1-1.25 3.2L12 20.8Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'tags-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.59 13.41L11 3.83V3H4v7h.83l9.58 9.59a2 2 0 002.83 0l3.35-3.35a2 2 0 000-2.83zM6.5 8A1.5 1.5 0 118 6.5 1.5 1.5 0 016.5 8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
        'archive' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4zM3 4h18v3H3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M10 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'organize' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h7v6H4zM13 5h7v4h-7zM13 11h7v8h-7zM4 13h7v6H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
        'office' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12M9 20v-5h6v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
        'school' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Zm-6 8v4c0 1.66 2.69 3 6 3s6-1.34 6-3v-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
        'display' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v10H4zM9 19h6M12 15v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'it' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v10H4zM9 20h6M12 16v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 10h.01M12 10h.01M16 10h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'machine' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14v10H5zM8 4h8M8 11h8M8 15h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'stapler' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14h10l4 3H8a4 4 0 0 1-4-4v-1l7-5 6 1 3 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function papetarie_storefront_password_toggle_icon(): string
{
    return '
      <span class="pap-password-toggle-icon pap-password-toggle-icon--show" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
          <path d="M2.2 12s3.5-5.5 9.8-5.5S21.8 12 21.8 12s-3.5 5.5-9.8 5.5S2.2 12 2.2 12Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="12" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/>
        </svg>
      </span>
      <span class="pap-password-toggle-icon pap-password-toggle-icon--hide" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
          <path d="M3 4l18 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          <path d="M2.2 12s3.5-5.5 9.8-5.5c1.4 0 2.6.2 3.7.6M21.8 12s-3.5 5.5-9.8 5.5c-1.4 0-2.6-.2-3.7-.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="12" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/>
        </svg>
      </span>';
}

function papetarie_storefront_notice_icon(string $type): string
{
    $icons = [
        'success' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20Zm4.3 7.8-5.1 6.3a1 1 0 01-1.55.08l-2.6-3a1 1 0 111.5-1.33l1.8 2.07 4.36-5.4a1 1 0 111.6 1.25Z" fill="currentColor"/></svg>',
        'error' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20Zm1 5v7h-2V7h2Zm0 9v2h-2v-2h2Z" fill="currentColor"/></svg>',
        'info' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20Zm1 15h-2V10h2v7Zm0-9h-2V6h2v2Z" fill="currentColor"/></svg>',
        'warning' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 1.6 20h20.8L12 2Zm0 6.2 1 5.3h-2l1-5.3Zm0 8.8a1.2 1.2 0 110-2.4 1.2 1.2 0 010 2.4Z" fill="currentColor"/></svg>',
    ];

    return $icons[$type] ?? $icons['info'];
}

function papetarie_storefront_store_auth_notice(string $message, string $type = 'error'): void
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $notice_type = in_array($type, ['success', 'error', 'info', 'warning'], true) ? $type : 'info';
    $message = trim(wp_strip_all_tags($message));

    if ('' === $message) {
        return;
    }

    $notices = WC()->session->get('pap_auth_notices', []);
    if (!is_array($notices)) {
        $notices = [];
    }

    $notices[] = [
        'type' => $notice_type,
        'message' => $message,
    ];

    WC()->session->set('pap_auth_notices', $notices);
    if ('error' === $notice_type) {
        WC()->session->set('pap_auth_last_error', $message);
    }
}

function papetarie_storefront_render_auth_notices(): void
{
    if (!function_exists('wc_get_notices')) {
        return;
    }

    $notices = wc_get_notices();
    $session_notices = [];
    if (function_exists('WC') && WC() && WC()->session) {
        $session_notices = WC()->session->get('pap_auth_notices', []);
        if (!is_array($session_notices)) {
            $session_notices = [];
        }
    }

    $fallback_notice = '';
    if (function_exists('WC') && WC() && WC()->session) {
        $fallback_notice = (string) WC()->session->get('pap_auth_last_error', '');
    }

    $type_map = [
        'error' => 'error',
        'success' => 'success',
        'notice' => 'info',
        'info' => 'info',
        'warning' => 'warning',
    ];

    echo '<div class="pap-auth-notices" role="status" aria-live="polite">';

    if (empty($notices) && empty($session_notices) && '' === trim($fallback_notice)) {
        echo '</div>';
        return;
    }

    $rendered = false;

    if (!empty($session_notices) || '' !== trim($fallback_notice)) {
        foreach ($session_notices as $notice) {
            $mapped_type = isset($notice['type']) ? (string) $notice['type'] : 'info';
            $mapped_type = in_array($mapped_type, ['error', 'success', 'info', 'warning'], true) ? $mapped_type : 'info';
            $message = isset($notice['message']) ? (string) $notice['message'] : '';

            if ('' === trim($message)) {
                continue;
            }

            echo '<div class="pap-auth-notice wc-block-components-notice-banner is-' . esc_attr($mapped_type) . ' pap-auth-notice--' . esc_attr($mapped_type) . '">';
            echo '<span class="pap-auth-notice-icon wc-block-components-notice-banner__icon" aria-hidden="true">' . papetarie_storefront_notice_icon($mapped_type) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' . wp_kses_post($message) . '</div>';
            echo '</div>';
            $rendered = true;
        }

        if (!$rendered && '' !== trim($fallback_notice)) {
            echo '<div class="pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" role="alert">';
            echo '<span class="pap-auth-notice-icon wc-block-components-notice-banner__icon" aria-hidden="true">' . papetarie_storefront_notice_icon('error') . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' . esc_html($fallback_notice) . '</div>';
            echo '</div>';
            $rendered = true;
        }
    } else {
        foreach ($notices as $type => $messages) {
            $mapped_type = $type_map[$type] ?? 'info';

            foreach ((array) $messages as $notice) {
                $message = is_array($notice) && isset($notice['notice']) ? (string) $notice['notice'] : (string) $notice;

                if ('' === trim($message)) {
                    continue;
                }

                echo '<div class="pap-auth-notice wc-block-components-notice-banner is-' . esc_attr($mapped_type) . ' pap-auth-notice--' . esc_attr($mapped_type) . '">';
                echo '<span class="pap-auth-notice-icon wc-block-components-notice-banner__icon" aria-hidden="true">' . papetarie_storefront_notice_icon($mapped_type) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' . wp_kses_post($message) . '</div>';
                echo '</div>';
            }
        }
    }

    echo '</div>';

    wc_clear_notices();
    if (function_exists('WC') && WC() && WC()->session) {
        WC()->session->set('pap_auth_notices', []);
        WC()->session->set('pap_auth_last_error', '');
    }
}

function papetarie_storefront_capture_login_errors($errors, $username, $password)
{
    if (!function_exists('wc_add_notice') || !is_wp_error($errors)) {
        return $errors;
    }

    $messages = $errors->get_error_messages();
    foreach ($messages as $message) {
        wc_add_notice(wp_strip_all_tags((string) $message), 'error');
        papetarie_storefront_store_auth_notice((string) $message, 'error');
    }

    return $errors;
}
add_filter('woocommerce_process_login_errors', 'papetarie_storefront_capture_login_errors', 10, 3);

function papetarie_storefront_capture_login_failure(): void
{
    if (function_exists('WC') && WC() && WC()->session) {
        $stored = WC()->session->get('pap_auth_notices', []);
        if (is_array($stored) && !empty($stored)) {
            return;
        }
        $stored_error = (string) WC()->session->get('pap_auth_last_error', '');
        if ('' !== trim($stored_error)) {
            return;
        }
    }

    if (function_exists('wc_has_notice') && wc_has_notice('Autentificarea a eșuat. Verifică emailul și parola.', 'error')) {
        return;
    }

    papetarie_storefront_store_auth_notice(__('Autentificarea a eșuat. Verifică emailul și parola.', 'papetarie-storefront'), 'error');
}
add_action('woocommerce_login_failed', 'papetarie_storefront_capture_login_failure');

function papetarie_storefront_render_auth_hero(string $context = 'login'): void
{
    $assets = get_stylesheet_directory_uri() . '/assets/images';
    $presets = [
        'login' => [
            'eyebrow' => '',
            'title' => __('Bine ai revenit!', 'papetarie-storefront'),
            'text' => __('Autentifică-te pentru a accesa contul tău.', 'papetarie-storefront') . "\n" . __('Și pentru a gestiona comenzile.', 'papetarie-storefront'),
            'bullets' => [
                [
                    'icon_style' => 'success',
                    'title' => __('Urmărește comenzile și facturile', 'papetarie-storefront'),
                    'text' => __('Vezi statusul, facturile și istoricul comenzilor în același loc.', 'papetarie-storefront'),
                    'icon' => 'file-lines-outline',
                ],
                [
                    'icon_style' => 'rose',
                    'title' => __('Salvezi produsele favorite', 'papetarie-storefront'),
                    'text' => __('Păstrezi rapid la îndemână produsele pe care le cumperi des.', 'papetarie-storefront'),
                    'icon' => 'heart-outline',
                ],
                [
                    'icon_style' => 'accent',
                    'title' => __('Primești oferte personalizate', 'papetarie-storefront'),
                    'text' => __('Primești recomandări și campanii relevante pentru contul tău.', 'papetarie-storefront'),
                    'icon' => 'tags-outline',
                ],
            ],
            'image' => $assets . '/auth-login-background-chatgpt.png',
        ],
        'register' => [
            'eyebrow' => __('Creare cont', 'papetarie-storefront'),
            'title' => __('Un cont pentru comenzi mai rapide', 'papetarie-storefront'),
            'text' => __('Înregistrează-te pentru a comanda mai repede, a păstra istoricul comenzilor și a primi avantaje în cont.', 'papetarie-storefront'),
            'bullets' => [
                ['icon' => 'cart', 'title' => __('Comandă rapidă', 'papetarie-storefront'), 'text' => __('Finalizezi achizițiile fără pași suplimentari.', 'papetarie-storefront')],
                ['icon' => 'archive', 'title' => __('Istoric clar al comenzilor', 'papetarie-storefront'), 'text' => __('Ai acces ușor la ce ai comandat deja.', 'papetarie-storefront')],
                ['icon' => 'heart', 'title' => __('Favorite și oferte dedicate', 'papetarie-storefront'), 'text' => __('Salvezi produse și primești avantaje în cont.', 'papetarie-storefront')],
            ],
            'image' => $assets . '/showcase-hero-user.png',
        ],
        'lost-password' => [
            'eyebrow' => '',
            'title' => __('Ai uitat parola?', 'papetarie-storefront'),
            'text' => __('Introdu adresa de email asociată contului și îți trimitem imediat instrucțiunile pentru resetare. Pașii sunt simpli, iar accesul la cont revine rapid și în siguranță.', 'papetarie-storefront'),
            'bullets' => [],
            'image' => $assets . '/auth-password-recovery-chatgpt.png',
        ],
        'reset-password' => [
            'eyebrow' => __('Parolă nouă', 'papetarie-storefront'),
            'title' => __('Setează o parolă nouă', 'papetarie-storefront'),
            'text' => __('Alege o parolă sigură.', 'papetarie-storefront') . "\n" . __('Revino imediat în cont.', 'papetarie-storefront'),
            'bullets' => [
                ['icon' => 'shield', 'title' => __('Parolă puternică', 'papetarie-storefront'), 'text' => __('Alege o parolă sigură pentru contul tău.', 'papetarie-storefront')],
                ['icon' => 'lock-outline', 'title' => __('Confirmare clară', 'papetarie-storefront'), 'text' => __('Finalizarea se face direct în pagina de reset.', 'papetarie-storefront')],
                ['icon' => 'pen', 'title' => __('Fără pași inutili', 'papetarie-storefront'), 'text' => __('Intri rapid înapoi în cont și continui cumpărăturile.', 'papetarie-storefront')],
            ],
            'image' => $assets . '/auth-password-recovery-chatgpt.png',
        ],
    ];

    $preset = $presets[$context] ?? $presets['login'];
    ?>
    <aside class="pap-auth-visual pap-auth-visual--<?php echo esc_attr($context); ?>" style="<?php echo esc_attr('--pap-auth-visual-image: url(' . esc_url($preset['image']) . ');'); ?>">
      <div class="pap-auth-visual-inner">
        <?php if (!empty($preset['eyebrow'])) : ?>
          <p class="pap-auth-eyebrow"><?php echo esc_html($preset['eyebrow']); ?></p>
        <?php endif; ?>
        <h2><?php echo esc_html($preset['title']); ?></h2>
        <p class="pap-auth-intro"><?php echo wp_kses_post(nl2br(esc_html($preset['text']))); ?></p>
        <?php if (!empty($preset['bullets'])) : ?>
          <ul class="pap-auth-benefits">
            <?php foreach ($preset['bullets'] as $bullet) : ?>
              <li>
                <?php
                $icon_style = $bullet['icon_style'] ?? '';
                ?>
                <span class="benefit-icon<?php echo $icon_style ? ' benefit-icon--' . esc_attr($icon_style) : ''; ?>" aria-hidden="true"><?php echo papetarie_storefront_icon((string) ($bullet['icon'] ?? '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span class="pap-auth-benefit-copy">
                  <strong><?php echo esc_html($bullet['title'] ?? $bullet['text']); ?></strong>
                  <span><?php echo esc_html($bullet['text']); ?></span>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <div class="pap-auth-visual-image">
          <img src="<?php echo esc_url($preset['image']); ?>" alt="" loading="lazy">
        </div>
      </div>
    </aside>
    <?php
}

function papetarie_storefront_render_social_login_area(): void
{
    $google_url = (string) apply_filters('papetarie_storefront_google_login_url', '');
    $social_shortcode = shortcode_exists('nextend_social_login') ? 'nextend_social_login' : '';
    $button_disabled = ($google_url === '' && $social_shortcode === '');
    $show_register_switch = function_exists('is_account_page') && is_account_page() && !is_user_logged_in();
    ?>
    <div class="pap-auth-social">
      <div class="pap-auth-divider"><span><?php esc_html_e('sau', 'papetarie-storefront'); ?></span></div>
      <button
        class="pap-auth-social-button pap-auth-social-button--google<?php echo $button_disabled ? ' pap-auth-social-button--inactive' : ''; ?>"
        type="button"
        data-auth-google
        data-login-url="<?php echo esc_attr($google_url); ?>"
        <?php echo $button_disabled ? 'disabled aria-disabled="true"' : ''; ?>
      >
          <i class="fa-brands fa-google" aria-hidden="true"></i>
          <span><?php esc_html_e('Continuă cu Google', 'papetarie-storefront'); ?></span>
      </button>
      <?php if ($show_register_switch) : ?>
        <div class="pap-auth-social-footer">
          <span class="pap-auth-social-prefix"><?php esc_html_e('Nu ai cont?', 'papetarie-storefront'); ?></span>
          <a class="pap-auth-inline-switch pap-auth-social-switch" href="#register" data-auth-switch="register"><?php esc_html_e('Creează unul nou', 'papetarie-storefront'); ?></a>
        </div>
      <?php endif; ?>
      <?php if ($social_shortcode !== '') : ?>
        <div class="pap-auth-social-shortcode">
          <?php echo do_shortcode('[' . $social_shortcode . ']'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

function papetarie_storefront_get_wishlist_ids(int $user_id = 0): array
{
    $user_id = $user_id > 0 ? $user_id : get_current_user_id();
    if ($user_id <= 0) {
        return [];
    }

    $wishlist = get_user_meta($user_id, 'papetarie_wishlist', true);
    if (!is_array($wishlist)) {
        return [];
    }

    return array_values(array_filter(array_map('absint', $wishlist)));
}

function papetarie_storefront_product_in_wishlist(int $product_id, int $user_id = 0): bool
{
    return in_array($product_id, papetarie_storefront_get_wishlist_ids($user_id), true);
}

function papetarie_storefront_wishlist_button_html(int $product_id, string $context = 'archive'): string
{
    $is_logged_in = is_user_logged_in();
    $is_favorite = papetarie_storefront_product_in_wishlist($product_id);
    $label = $is_favorite ? __('Scoate din favorite', 'papetarie-storefront') : __('Adaugă la favorite', 'papetarie-storefront');
    $icon = $is_favorite ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    $login_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();

    return sprintf(
        '<button type="button" class="pap-wishlist%s" data-product-id="%d" data-wishlist-action="%s" data-login-url="%s" aria-pressed="%s" aria-label="%s"><i class="%s" aria-hidden="true"></i><span class="screen-reader-text">%s</span></button>',
        $is_favorite ? ' is-active' : '',
        $product_id,
        esc_attr($is_logged_in ? 'toggle' : 'login'),
        esc_url($login_url),
        $is_favorite ? 'true' : 'false',
        esc_attr($label),
        esc_attr($icon),
        esc_html($label)
    );
}

function papetarie_storefront_handle_wishlist_toggle(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => __('Autentifică-te pentru a salva favoritele.', 'papetarie-storefront'),
            'login_url' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(),
        ], 401);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_wishlist_toggle')) {
        wp_send_json_error(['message' => __('Sesiunea a expirat. Reîncarcă pagina.', 'papetarie-storefront')], 403);
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $product = $product_id ? wc_get_product($product_id) : false;
    if (!$product instanceof WC_Product) {
        wp_send_json_error(['message' => __('Produsul nu a fost găsit.', 'papetarie-storefront')], 404);
    }

    $user_id = get_current_user_id();
    $wishlist = papetarie_storefront_get_wishlist_ids($user_id);
    $is_favorite = in_array($product_id, $wishlist, true);

    if ($is_favorite) {
        $wishlist = array_values(array_diff($wishlist, [$product_id]));
    } else {
        $wishlist[] = $product_id;
    }

    update_user_meta($user_id, 'papetarie_wishlist', array_values(array_unique(array_map('absint', $wishlist))));

    wp_send_json_success([
        'active' => !$is_favorite,
        'count' => count($wishlist),
        'message' => !$is_favorite ? __('Produs adăugat la favorite.', 'papetarie-storefront') : __('Produs eliminat din favorite.', 'papetarie-storefront'),
    ]);
}
add_action('wp_ajax_pap_toggle_wishlist', 'papetarie_storefront_handle_wishlist_toggle');

function papetarie_storefront_product_subtitle(WC_Product $product): string
{
    $product_subtitle = wp_strip_all_tags($product->get_short_description());
    if ($product_subtitle === '') {
        $product_subtitle = wp_strip_all_tags($product->get_attribute('pa_subtitlu'));
    }
    if ($product_subtitle === '') {
        $product_subtitle = wp_strip_all_tags($product->get_attribute('subtitlu'));
    }
    if ($product_subtitle === '') {
        $product_subtitle = wp_strip_all_tags($product->get_attribute('dimensiune'));
    }
    if ($product_subtitle === '') {
        $product_subtitle = __('Produs util pentru birou și școală.', 'papetarie-storefront');
    }

    return wp_trim_words($product_subtitle, 9, '');
}

function papetarie_storefront_recently_viewed_product_ids(int $limit = 4): array
{
    if (!function_exists('wc_get_products')) {
        return [];
    }

    $recently_viewed = isset($_COOKIE['woocommerce_recently_viewed']) ? wc_clean(wp_unslash($_COOKIE['woocommerce_recently_viewed'])) : '';
    if ($recently_viewed === '') {
        return [];
    }

    $ids = array_filter(array_map('absint', explode('|', $recently_viewed)));
    if (!$ids) {
        return [];
    }

    return array_reverse(array_slice(array_unique($ids), 0, $limit));
}

function papetarie_storefront_register_account_endpoints(): void
{
    add_rewrite_endpoint('favorite', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('oferte', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('suport', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('retururi', EP_ROOT | EP_PAGES);
}
add_action('init', 'papetarie_storefront_register_account_endpoints');

function papetarie_storefront_has_real_logo(): bool
{
    if (!has_custom_logo()) {
        return false;
    }

    $logo_id = (int) get_theme_mod('custom_logo');

    if ($logo_id <= 0) {
        return false;
    }

    $logo_url = wp_get_attachment_image_url($logo_id, 'full');

    if (!$logo_url) {
        return false;
    }

    return strpos($logo_url, 'woocommerce-placeholder') === false;
}

function papetarie_storefront_term_order(\WP_Term $term): int
{
    return (int) get_term_meta($term->term_id, 'order', true);
}

function papetarie_storefront_sort_terms(array $terms): array
{
    usort(
        $terms,
        static function (\WP_Term $left, \WP_Term $right): int {
            $left_order = papetarie_storefront_term_order($left);
            $right_order = papetarie_storefront_term_order($right);

            if ($left_order === $right_order) {
                return strcasecmp($left->name, $right->name);
            }

            return $left_order <=> $right_order;
        }
    );

    return $terms;
}

function papetarie_storefront_mega_menu_icon(string $slug, string $name): string
{
    $map = [
        'instrumente-de-scris-si-corectura' => 'pen',
        'articole-din-hartie' => 'paper',
        'arhivare' => 'archive',
        'organizare' => 'organize',
        'accesorii-pentru-birou' => 'office',
        'articole-scolare' => 'school',
        'consumabile-si-indosariere' => 'archive',
        'sisteme-de-prezentare-si-afisare' => 'display',
        'accesorii-it' => 'it',
        'echipamente-birou' => 'machine',
        'capsatoare-si-perforatoare' => 'stapler',
    ];

    if (isset($map[$slug])) {
        return $map[$slug];
    }

    $normalized = sanitize_title($name);

    return $map[$normalized] ?? 'menu';
}

function papetarie_storefront_get_mega_menu_categories(): array
{
    if (!taxonomy_exists('product_cat')) {
        return [];
    }

    $default_category = (int) get_option('default_product_cat');
    $parents = get_terms(
        [
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
            'exclude' => array_filter([$default_category]),
        ]
    );

    if (is_wp_error($parents) || !$parents) {
        return [];
    }

    $items = [];

    foreach (papetarie_storefront_sort_terms($parents) as $parent) {
        $children = get_terms(
            [
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $parent->term_id,
            ]
        );

        if (is_wp_error($children)) {
            $children = [];
        }

        $children = papetarie_storefront_sort_terms($children);

        $items[] = [
            'term_id' => $parent->term_id,
            'slug' => $parent->slug,
            'name' => $parent->name,
            'url' => get_term_link($parent),
            'description' => wp_strip_all_tags((string) term_description($parent->term_id, 'product_cat')),
            'icon' => papetarie_storefront_mega_menu_icon($parent->slug, $parent->name),
            'children' => array_map(
                static function (\WP_Term $child): array {
                    $grandchildren = get_terms(
                        [
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                            'parent' => $child->term_id,
                        ]
                    );

                    if (is_wp_error($grandchildren)) {
                        $grandchildren = [];
                    }

                    $grandchildren = papetarie_storefront_sort_terms($grandchildren);

                    return [
                        'term_id' => $child->term_id,
                        'slug' => $child->slug,
                        'name' => $child->name,
                        'url' => get_term_link($child),
                        'description' => wp_strip_all_tags((string) term_description($child->term_id, 'product_cat')),
                        'children' => array_map(
                            static function (\WP_Term $grandchild): array {
                                return [
                                    'term_id' => $grandchild->term_id,
                                    'slug' => $grandchild->slug,
                                    'name' => $grandchild->name,
                                    'url' => get_term_link($grandchild),
                                    'description' => wp_strip_all_tags((string) term_description($grandchild->term_id, 'product_cat')),
                                ];
                            },
                            $grandchildren
                        ),
                    ];
                },
                $children
            ),
        ];
    }

    return array_values(
        array_filter(
            $items,
            static fn (array $item): bool => !empty($item['children']) || $item['slug'] === 'test'
        )
    );
}

function papetarie_storefront_active_mega_menu_slug(array $categories): string
{
    if (!$categories) {
        return '';
    }

    if (is_tax('product_cat')) {
        $queried = get_queried_object();

        if ($queried instanceof \WP_Term) {
            foreach ($categories as $category) {
                if ($queried->term_id === $category['term_id']) {
                    return $category['slug'];
                }

                foreach ($category['children'] as $child) {
                    if ($child['term_id'] === $queried->term_id) {
                        return $category['slug'];
                    }
                }
            }
        }
    }

    return $categories[0]['slug'];
}

function papetarie_storefront_short_category_name(string $slug, string $name): string
{
    $map = [
        'instrumente-de-scris-si-corectura' => 'Instrumente de scris',
        'capsatoare-si-perforatoare' => 'Capsatoare',
        'accesorii-pentru-birou' => 'Accesorii birou',
        'articole-din-hartie' => 'Articole hârtie',
        'sisteme-de-prezentare-si-afisare' => 'Prezentare',
        'consumabile-si-indosariere' => 'Consumabile',
        'accesorii-it' => 'Accesorii IT',
        'articole-scolare' => 'Școlare',
        'echipamente-birou' => 'Echipamente',
    ];

    return $map[$slug] ?? $name;
}

function papetarie_storefront_render_mega_menu_panels(array $categories, string $active_slug, array $args = []): void
{
    if (empty($categories)) {
        return;
    }

    $args = wp_parse_args(
        $args,
        [
            'nav_aria_label' => __('Categorii principale', 'papetarie-storefront'),
            'nav_item_classes' => ['pap-showcase-nav-item'],
            'nav_icon_classes' => ['pap-showcase-nav-icon'],
            'nav_label_classes' => ['pap-showcase-nav-label'],
            'panel_item_classes' => ['pap-showcase-panel'],
            'panel_include_id' => false,
            'panel_id_prefix' => '',
            'panel_data_attr' => 'data-showcase-panel',
            'panel_title_class' => 'pap-showcase-panel-title',
            'panel_layout_class' => 'pap-showcase-panel-layout',
            'panel_copy_class' => 'pap-showcase-panel-copy',
            'panel_columns_class' => 'pap-showcase-panel-columns',
            'panel_group_class' => 'pap-showcase-panel-group',
            'panel_group_title_class' => 'pap-showcase-panel-group-title',
            'panel_sublist_class' => 'pap-showcase-panel-sublist',
            'panel_empty_class' => 'pap-showcase-panel-empty',
        ]
    );

    $nav_item_class = implode(' ', array_filter(array_merge(['pap-category-menu-nav-item'], (array) $args['nav_item_classes'])));
    $nav_icon_class = implode(' ', array_filter(array_merge(['pap-category-menu-nav-icon'], (array) $args['nav_icon_classes'])));
    $nav_label_class = implode(' ', array_filter(array_merge(['pap-category-menu-nav-copy'], (array) $args['nav_label_classes'])));
    $panel_item_class = implode(' ', array_filter(array_merge(['pap-category-menu-panel'], (array) $args['panel_item_classes'])));
    $panel_title_class = (string) $args['panel_title_class'];
    $panel_layout_class = (string) $args['panel_layout_class'];
    $panel_copy_class = (string) $args['panel_copy_class'];
    $panel_columns_class = (string) $args['panel_columns_class'];
    $panel_group_class = (string) $args['panel_group_class'];
    $panel_group_title_class = (string) $args['panel_group_title_class'];
    $panel_sublist_class = (string) $args['panel_sublist_class'];
    $panel_empty_class = (string) $args['panel_empty_class'];
    $include_id = !empty($args['panel_include_id']);
    $panel_id_prefix = (string) $args['panel_id_prefix'];
    $panel_data_attr = (string) $args['panel_data_attr'];

    ?>
    <?php foreach ($categories as $category) : ?>
      <?php if (empty($category['children'])) { continue; } ?>
      <section
        class="<?php echo esc_attr($panel_item_class); ?><?php echo $category['slug'] === $active_slug ? ' is-active' : ''; ?>"
        <?php if ($include_id) : ?>
          id="<?php echo esc_attr($panel_id_prefix . $category['slug']); ?>"
        <?php endif; ?>
        <?php echo esc_attr($panel_data_attr); ?>="<?php echo esc_attr($category['slug']); ?>"
        <?php echo $category['slug'] === $active_slug ? '' : 'hidden'; ?>
      >
        <div class="<?php echo esc_attr($panel_layout_class); ?>">
            <div class="<?php echo esc_attr($panel_copy_class); ?>">
            <div class="<?php echo esc_attr($panel_title_class); ?>"><?php echo esc_html($category['name']); ?></div>
            <div class="<?php echo esc_attr($panel_columns_class); ?>">
              <?php foreach ($category['children'] as $child) : ?>
                <div class="<?php echo esc_attr($panel_group_class); ?>">
                  <?php if (!empty($child['children'])) : ?>
                    <a class="<?php echo esc_attr($panel_group_title_class); ?>" href="<?php echo esc_url($child['url']); ?>">
                      <?php echo esc_html($child['name']); ?>
                    </a>
                    <ul class="<?php echo esc_attr($panel_sublist_class); ?>">
                      <?php foreach ($child['children'] as $grandchild) : ?>
                        <li>
                          <a href="<?php echo esc_url($grandchild['url']); ?>">
                            <?php echo esc_html($grandchild['name']); ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else : ?>
                    <a class="<?php echo esc_attr($panel_group_title_class); ?>" href="<?php echo esc_url($child['url']); ?>">
                      <?php echo esc_html($child['name']); ?>
                    </a>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        </section>
      <?php endforeach; ?>
    <?php
}

function papetarie_storefront_render_header_category_menu(array $categories, string $active_slug): void
{
    if (empty($categories)) {
        return;
    }

    ?>
    <div id="pap-header-category-menu" class="pap-header-catmenu-shell" data-header-catmenu-shell hidden>
      <div class="pap-header-catmenu">
        <aside class="pap-showcase-nav pap-header-catmenu-left" aria-label="<?php esc_attr_e('Categorii principale', 'papetarie-storefront'); ?>">
          <div class="pap-showcase-nav-list pap-header-catmenu-list">
            <?php foreach ($categories as $category) : ?>
              <a
                class="pap-showcase-nav-item pap-header-catmenu-item<?php echo $category['slug'] === $active_slug ? ' is-active' : ''; ?>"
                href="<?php echo esc_url($category['url']); ?>"
                data-header-catmenu-item="<?php echo esc_attr($category['slug']); ?>"
                data-header-catmenu-target="<?php echo esc_attr($category['slug']); ?>"
                data-header-catmenu-has-children="<?php echo !empty($category['children']) ? '1' : '0'; ?>"
                <?php if (!empty($category['children'])) : ?>
                  aria-controls="pap-header-catmenu-panel-<?php echo esc_attr($category['slug']); ?>"
                <?php endif; ?>
                aria-expanded="<?php echo !empty($category['children']) && $category['slug'] === $active_slug ? 'true' : 'false'; ?>"
              >
                <span class="pap-showcase-nav-icon pap-header-catmenu-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($category['icon']); ?></span>
                <span class="pap-showcase-nav-label pap-header-catmenu-label"><?php echo esc_html(papetarie_storefront_short_category_name($category['slug'], $category['name'])); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </aside>

        <div class="pap-header-catmenu-right">
          <div class="pap-header-catmenu-panels">
            <?php foreach ($categories as $category) : ?>
              <?php if (empty($category['children'])) { continue; } ?>
              <section
                class="pap-header-catmenu-panel<?php echo $category['slug'] === $active_slug ? ' is-active' : ''; ?>"
                data-header-catmenu-panel="<?php echo esc_attr($category['slug']); ?>"
                id="pap-header-catmenu-panel-<?php echo esc_attr($category['slug']); ?>"
                <?php echo $category['slug'] === $active_slug ? '' : 'hidden'; ?>
              >
                <div class="pap-header-catmenu-panel-title"><?php echo esc_html($category['name']); ?></div>
                <div class="pap-header-catmenu-group-list">
                  <?php foreach ($category['children'] as $child) : ?>
                    <div class="pap-header-catmenu-group">
                      <a class="pap-header-catmenu-group-title" href="<?php echo esc_url($child['url']); ?>">
                        <?php echo esc_html($child['name']); ?>
                      </a>
                      <?php if (!empty($child['children'])) : ?>
                        <ul class="pap-header-catmenu-sublist">
                          <?php foreach ($child['children'] as $grandchild) : ?>
                            <li>
                              <a href="<?php echo esc_url($grandchild['url']); ?>">
                                <?php echo esc_html($grandchild['name']); ?>
                              </a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php
}

function papetarie_storefront_ajax_add_to_cart(): void
{
    $timing_start = microtime(true);
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(['message' => __('Coșul nu este disponibil momentan.', 'papetarie-storefront')], 400);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_home_add_to_cart')) {
        wp_send_json_error(['message' => __('Sesiunea a expirat. Reîncarcă pagina.', 'papetarie-storefront')], 403);
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
    $product = $product_id ? wc_get_product($product_id) : false;

    if (!$product instanceof WC_Product) {
        wp_send_json_error(['message' => __('Produsul nu a fost găsit.', 'papetarie-storefront')], 404);
    }

    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        wp_send_json_error(['message' => __('Produsul nu poate fi adăugat în coș.', 'papetarie-storefront')], 400);
    }

    $timing_before_add = microtime(true);
    $added = WC()->cart->add_to_cart($product_id, $quantity);
    $timing_after_add = microtime(true);

    if (!$added) {
        wp_send_json_error(['message' => __('Nu am putut adăuga produsul în coș.', 'papetarie-storefront')], 400);
    }

    $image_url = '';
    $image_id = $product->get_image_id();
    if ($image_id) {
        $image_data = wp_get_attachment_image_src($image_id, 'thumbnail');
        if ($image_data) {
            $image_url = $image_data[0];
        }
    }

    $timing_before_response = microtime(true);

    $cart_drawer = papetarie_storefront_get_cart_drawer_payload();

    papetarie_storefront_send_json_success_fast([
        'message' => __('Produsul a fost adăugat în coș', 'papetarie-storefront'),
        'name' => $product->get_name(),
        'price_html' => $product->get_price_html(),
        'cart_url' => wc_get_cart_url(),
        'image_url' => $image_url,
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_count_label' => papetarie_storefront_cart_count_label(),
        'cart_total_html' => function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '',
        'cart_item_key' => $added,
        'cart_item_quantity' => isset(WC()->cart->get_cart()[$added]['quantity']) ? (int) WC()->cart->get_cart()[$added]['quantity'] : $quantity,
        'cart_drawer' => $cart_drawer,
        'debug_timings' => [
            'before_add_ms' => (int) round(($timing_before_add - $timing_start) * 1000),
            'after_add_ms' => (int) round(($timing_after_add - $timing_start) * 1000),
            'before_response_ms' => (int) round(($timing_before_response - $timing_start) * 1000),
        ],
    ]);
}
add_action('wp_ajax_pap_home_add_to_cart', 'papetarie_storefront_ajax_add_to_cart');
add_action('wp_ajax_nopriv_pap_home_add_to_cart', 'papetarie_storefront_ajax_add_to_cart');

function papetarie_storefront_checkout_fields(array $fields): array
{
    $counties = papetarie_storefront_romania_counties();
    $cities = papetarie_storefront_romania_cities();

    $fields['billing']['billing_customer_type'] = [
        'type' => 'radio',
        'label' => __('Tip client', 'papetarie-storefront'),
        'required' => true,
        'class' => ['form-row-wide', 'pap-customer-type-field'],
        'options' => [
            'person' => __('Persoană fizică', 'papetarie-storefront'),
            'company' => __('Persoană juridică', 'papetarie-storefront'),
        ],
        'default' => 'person',
        'priority' => 5,
    ];

    $company_fields = [
        'billing_company',
        'billing_cui',
        'billing_reg_no',
        'billing_bank_name',
        'billing_iban',
    ];

    foreach ($company_fields as $field_key) {
        if (!isset($fields['billing'][$field_key])) {
            $fields['billing'][$field_key] = [];
        }

        $fields['billing'][$field_key]['class'] = array_values(array_unique(array_merge(
            (array) ($fields['billing'][$field_key]['class'] ?? []),
            ['form-row-wide', 'pap-company-only']
        )));
        $fields['billing'][$field_key]['priority'] = $fields['billing'][$field_key]['priority'] ?? 90;
    }

    $fields['billing']['billing_company']['label'] = __('Firmă', 'papetarie-storefront');
    $fields['billing']['billing_company']['placeholder'] = __('Denumire firmă', 'papetarie-storefront');
    $fields['billing']['billing_company']['required'] = false;

    $fields['billing']['billing_cui'] = [
        'type' => 'text',
        'label' => __('CUI', 'papetarie-storefront'),
        'placeholder' => __('RO12345678', 'papetarie-storefront'),
        'required' => false,
        'class' => ['form-row-first', 'pap-company-only'],
        'priority' => 91,
    ];

    $fields['billing']['billing_reg_no'] = [
        'type' => 'text',
        'label' => __('Nr. registru comerțului', 'papetarie-storefront'),
        'placeholder' => __('J00/0000/2026', 'papetarie-storefront'),
        'required' => false,
        'class' => ['form-row-last', 'pap-company-only'],
        'priority' => 92,
    ];

    $fields['billing']['billing_bank_name'] = [
        'type' => 'text',
        'label' => __('Bancă', 'papetarie-storefront'),
        'placeholder' => __('Nume bancă', 'papetarie-storefront'),
        'required' => false,
        'class' => ['form-row-first', 'pap-company-only'],
        'priority' => 93,
    ];

    $fields['billing']['billing_iban'] = [
        'type' => 'text',
        'label' => __('IBAN', 'papetarie-storefront'),
        'placeholder' => __('RO00AAAA0000000000000000', 'papetarie-storefront'),
        'required' => false,
        'class' => ['form-row-last', 'pap-company-only'],
        'priority' => 94,
    ];

    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['label'] = __('Județ', 'papetarie-storefront');
        $fields['billing']['billing_state']['type'] = 'select';
        $fields['billing']['billing_state']['options'] = ['' => __('Alege județul', 'papetarie-storefront')] + $counties;
        $fields['billing']['billing_state']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['billing']['billing_state']['class'] ?? []),
            ['form-row-first']
        )));
        $fields['billing']['billing_state']['priority'] = 70;
    }

    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['label'] = __('Oraș', 'papetarie-storefront');
        $fields['billing']['billing_city']['placeholder'] = __('București', 'papetarie-storefront');
        $fields['billing']['billing_city']['custom_attributes'] = array_merge(
            (array) ($fields['billing']['billing_city']['custom_attributes'] ?? []),
            ['list' => 'pap-romanian-cities']
        );
        $fields['billing']['billing_city']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['billing']['billing_city']['class'] ?? []),
            ['form-row-last']
        )));
        $fields['billing']['billing_city']['priority'] = 71;
    }

    if (isset($fields['shipping']['shipping_state'])) {
        $fields['shipping']['shipping_state']['label'] = __('Județ', 'papetarie-storefront');
        $fields['shipping']['shipping_state']['type'] = 'select';
        $fields['shipping']['shipping_state']['options'] = ['' => __('Alege județul', 'papetarie-storefront')] + $counties;
    }

    if (isset($fields['shipping']['shipping_city'])) {
        $fields['shipping']['shipping_city']['label'] = __('Oraș', 'papetarie-storefront');
        $fields['shipping']['shipping_city']['placeholder'] = __('București', 'papetarie-storefront');
        $fields['shipping']['shipping_city']['custom_attributes'] = array_merge(
            (array) ($fields['shipping']['shipping_city']['custom_attributes'] ?? []),
            ['list' => 'pap-romanian-cities']
        );
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'papetarie_storefront_checkout_fields');

function papetarie_storefront_checkout_validate(array $data, \WP_Error $errors): void
{
    $customer_type = isset($data['billing_customer_type']) ? sanitize_key((string) $data['billing_customer_type']) : 'person';
    $billing_company = isset($data['billing_company']) ? trim((string) $data['billing_company']) : '';
    $billing_cui = isset($data['billing_cui']) ? trim((string) $data['billing_cui']) : '';
    $billing_state = isset($data['billing_state']) ? sanitize_text_field((string) $data['billing_state']) : '';
    $billing_city = isset($data['billing_city']) ? trim((string) $data['billing_city']) : '';
    $billing_postcode = isset($data['billing_postcode']) ? trim((string) $data['billing_postcode']) : '';

    if ($customer_type === 'company') {
        if ($billing_company === '') {
            $errors->add('billing_company_required', __('Completează denumirea firmei.', 'papetarie-storefront'));
        }

        if ($billing_cui === '') {
            $errors->add('billing_cui_required', __('Completează CUI-ul firmei.', 'papetarie-storefront'));
        }
    }

    if ($billing_state === '') {
        $errors->add('billing_state_required', __('Selectează județul.', 'papetarie-storefront'));
    } else {
        $counties = papetarie_storefront_romania_counties();
        if (!isset($counties[$billing_state])) {
            $errors->add('billing_state_invalid', __('Județul selectat nu este valid.', 'papetarie-storefront'));
        }
    }

    if ($billing_city === '') {
        $errors->add('billing_city_required', __('Completează orașul.', 'papetarie-storefront'));
    }

    if ($billing_postcode === '') {
        $errors->add('billing_postcode_required', __('Completează codul poștal.', 'papetarie-storefront'));
    }
}
add_action('woocommerce_after_checkout_validation', 'papetarie_storefront_checkout_validate', 10, 2);

function papetarie_storefront_checkout_city_datalist(): void
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    $cities = papetarie_storefront_romania_cities();
    if (!$cities) {
        return;
    }

    echo '<datalist id="pap-romanian-cities">';
    foreach ($cities as $city) {
        echo '<option value="' . esc_attr($city) . '"></option>';
    }
    echo '</datalist>';
}
add_action('woocommerce_after_checkout_billing_form', 'papetarie_storefront_checkout_city_datalist', 20);

function papetarie_storefront_flush_rewrite_on_theme_switch(): void
{
    papetarie_storefront_register_account_endpoints();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'papetarie_storefront_flush_rewrite_on_theme_switch');

function papetarie_storefront_account_menu_items(array $items): array
{
    $new_items = [];

    foreach ($items as $key => $label) {
        $new_items[$key] = $label;

        if ($key === 'orders') {
            $new_items['favorite'] = __('Favorite', 'papetarie-storefront');
            $new_items['oferte'] = __('Oferte', 'papetarie-storefront');
            $new_items['suport'] = __('Suport', 'papetarie-storefront');
            $new_items['retururi'] = __('Retururi', 'papetarie-storefront');
        }
    }

    if (!isset($new_items['favorite'])) {
        $new_items['favorite'] = __('Favorite', 'papetarie-storefront');
    }

    if (!isset($new_items['oferte'])) {
        $new_items['oferte'] = __('Oferte', 'papetarie-storefront');
    }

    if (!isset($new_items['suport'])) {
        $new_items['suport'] = __('Suport', 'papetarie-storefront');
    }

    if (!isset($new_items['retururi'])) {
        $new_items['retururi'] = __('Retururi', 'papetarie-storefront');
    }

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'papetarie_storefront_account_menu_items');

function papetarie_storefront_render_product_card(WC_Product $product, string $context = 'account', array $args = []): void
{
    $product_id = $product->get_id();
    $product_name = $product->get_name();
    $product_url = $product->get_permalink();
    $product_image_id = $product->get_image_id();
    $product_subtitle = $args['subtitle'] ?? papetarie_storefront_product_subtitle($product);
    $product_image = $product_image_id
        ? wp_get_attachment_image($product_image_id, 'medium', false, ['loading' => 'lazy', 'alt' => $product_name])
        : '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
    $can_add_to_cart = $product->is_purchasable() && $product->is_in_stock();
    $action_url = $can_add_to_cart ? $product->add_to_cart_url() : $product_url;
    $action_text = $can_add_to_cart ? $product->add_to_cart_text() : __('Vezi produsul', 'papetarie-storefront');
    $action_class = $can_add_to_cart && $product->is_type('simple') ? 'add_to_cart_button ajax_add_to_cart' : '';
    ?>
    <article class="pap-product-card pap-product-card--<?php echo esc_attr($context); ?>">
      <?php echo papetarie_storefront_wishlist_button_html($product_id, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <a class="pap-product-card-link" href="<?php echo esc_url($product_url); ?>">
        <div class="pap-product-thumb pap-product-thumb--<?php echo esc_attr($context); ?>">
          <?php echo $product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <div class="pap-product-copy">
          <h3><?php echo esc_html($product_name); ?></h3>
          <p><?php echo esc_html(wp_trim_words($product_subtitle, 9, '')); ?></p>
        </div>
      </a>
      <div class="pap-product-meta pap-product-meta--<?php echo esc_attr($context); ?>">
        <strong class="pap-price"><?php echo wp_kses_post($product->get_price_html()); ?></strong>
        <div class="pap-product-actions">
          <a
            class="pap-home-add-to-cart <?php echo esc_attr($action_class); ?>"
            href="<?php echo esc_url($action_url); ?>"
            aria-label="<?php echo esc_attr($action_text); ?>"
            <?php if ($can_add_to_cart && $product->is_type('simple')) : ?>
              data-product_id="<?php echo esc_attr($product->get_id()); ?>"
              data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
              rel="nofollow"
            <?php endif; ?>
          >
            <span class="pap-product-action-icon"><?php echo papetarie_storefront_icon('cart'); ?></span>
          </a>
        </div>
      </div>
    </article>
    <?php
}

function papetarie_storefront_account_wishlist_ids(): array
{
    return papetarie_storefront_get_wishlist_ids();
}

function papetarie_storefront_account_favorite_endpoint(): void
{
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea produsele favorite.', 'papetarie-storefront') . '</p>';
        return;
    }

    $ids = papetarie_storefront_account_wishlist_ids();
    ?>
    <div class="pap-account-section">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Favorite', 'papetarie-storefront'); ?></h2>
        <p><?php esc_html_e('Produsele salvate pentru revenire rapidă și adăugare instant în coș.', 'papetarie-storefront'); ?></p>
      </div>
      <?php if (!$ids) : ?>
        <div class="pap-account-empty">
          <p><?php esc_html_e('Nu ai produse salvate momentan.', 'papetarie-storefront'); ?></p>
          <a class="button" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></a>
        </div>
      <?php else : ?>
        <div class="pap-account-product-grid">
          <?php foreach ($ids as $product_id) : ?>
            <?php $product = wc_get_product($product_id); ?>
            <?php if (!$product instanceof WC_Product) { continue; } ?>
            <?php papetarie_storefront_render_product_card($product, 'account'); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
}
add_action('woocommerce_account_favorite_endpoint', 'papetarie_storefront_account_favorite_endpoint');

function papetarie_storefront_account_offers_endpoint(): void
{
    $sale_ids = function_exists('wc_get_product_ids_on_sale') ? wc_get_product_ids_on_sale() : [];
    $sale_ids = array_values(array_filter(array_map('absint', $sale_ids), static function (int $product_id): bool {
        return $product_id > 0 && get_post_type($product_id) === 'product';
    }));

    $query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 8,
        'post__in' => $sale_ids ?: [0],
        'orderby' => 'post__in',
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);
    ?>
    <div class="pap-account-section">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Oferte pentru mine', 'papetarie-storefront'); ?></h2>
        <p><?php esc_html_e('Produse cu preț redus, disponibile acum în magazin.', 'papetarie-storefront'); ?></p>
      </div>
      <?php if (!$query->have_posts()) : ?>
        <div class="pap-account-empty">
          <p><?php esc_html_e('Nu există oferte active în acest moment.', 'papetarie-storefront'); ?></p>
        </div>
      <?php else : ?>
        <div class="pap-account-product-grid">
          <?php foreach ($query->posts as $product_id) : ?>
            <?php $product = wc_get_product((int) $product_id); ?>
            <?php if (!$product instanceof WC_Product || !$product->is_on_sale()) { continue; } ?>
            <?php papetarie_storefront_render_product_card($product, 'account'); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
}
add_action('woocommerce_account_oferte_endpoint', 'papetarie_storefront_account_offers_endpoint');

function papetarie_storefront_account_support_endpoint(): void
{
    ?>
    <div class="pap-account-section">
      <div class="pap-account-section-head">
        <h2><?php esc_html_e('Suport', 'papetarie-storefront'); ?></h2>
        <p><?php esc_html_e('Răspunsuri scurte și direcții rapide pentru cele mai frecvente întrebări.', 'papetarie-storefront'); ?></p>
      </div>
      <div class="pap-account-support-grid">
        <?php
        $items = [
            [
                'icon' => 'truck-outline',
                'title' => __('Livrare', 'papetarie-storefront'),
                'text' => __('Verifică termenul de livrare afișat pe produs și în checkout.', 'papetarie-storefront'),
            ],
            [
                'icon' => 'shield',
                'title' => __('Retur', 'papetarie-storefront'),
                'text' => __('Poți deschide o cerere din secțiunea Retururi din cont.', 'papetarie-storefront'),
            ],
            [
                'icon' => 'paper',
                'title' => __('Facturare', 'papetarie-storefront'),
                'text' => __('Datele de facturare se completează în checkout și pot fi actualizate din cont.', 'papetarie-storefront'),
            ],
            [
                'icon' => 'help',
                'title' => __('Întrebări rapide', 'papetarie-storefront'),
                'text' => __('Dacă ai o nelămurire, începe cu această secțiune și apoi contactează echipa.', 'papetarie-storefront'),
            ],
        ];
        foreach ($items as $item) :
            ?>
            <article class="pap-account-support-card">
              <span class="pap-account-support-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($item['icon']); ?></span>
              <h3><?php echo esc_html($item['title']); ?></h3>
              <p><?php echo esc_html($item['text']); ?></p>
            </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
add_action('woocommerce_account_suport_endpoint', 'papetarie_storefront_account_support_endpoint');

function papetarie_storefront_handle_return_request(): void
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_wc_endpoint_url('retururi')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pap_return_request'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wc_add_notice(__('Trebuie să fii autentificat pentru a trimite o cerere de retur.', 'papetarie-storefront'), 'error');
        return;
    }

    $nonce = isset($_POST['pap_return_nonce']) ? sanitize_text_field(wp_unslash($_POST['pap_return_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_return_request')) {
        wc_add_notice(__('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'), 'error');
        return;
    }

    $order_number = isset($_POST['pap_return_order']) ? sanitize_text_field(wp_unslash($_POST['pap_return_order'])) : '';
    $reason = isset($_POST['pap_return_reason']) ? sanitize_text_field(wp_unslash($_POST['pap_return_reason'])) : '';
    $details = isset($_POST['pap_return_details']) ? sanitize_textarea_field(wp_unslash($_POST['pap_return_details'])) : '';
    $user = wp_get_current_user();
    $subject = sprintf(__('Cerere retur - %s', 'papetarie-storefront'), $user->display_name ?: $user->user_email);
    $message = implode("\n", [
        'Cerere retur nouă:',
        '',
        'Utilizator: ' . $user->display_name,
        'Email: ' . $user->user_email,
        'Număr comandă: ' . $order_number,
        'Motiv: ' . $reason,
        'Detalii: ' . $details,
    ]);

    wp_mail(get_option('admin_email'), $subject, $message);
    wc_add_notice(__('Cererea de retur a fost trimisă. Revenim cu un răspuns.', 'papetarie-storefront'), 'success');

    $redirect_url = add_query_arg([], wc_get_account_endpoint_url('retururi'));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_return_request');

function papetarie_storefront_returns_endpoint_content(): void
{
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a trimite o cerere de retur.', 'papetarie-storefront') . '</p>';
        return;
    }

    $current_user = wp_get_current_user();
    $prefill_order = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';
    $reasons = [
        'defect' => __('Produs defect', 'papetarie-storefront'),
        'gresit' => __('Produs greșit livrat', 'papetarie-storefront'),
        'nu_corespunde' => __('Nu corespunde descrierii', 'papetarie-storefront'),
        'alte_motive' => __('Alt motiv', 'papetarie-storefront'),
    ];
    ?>
    <div class="pap-account-return">
      <h2><?php esc_html_e('Cerere retur', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Completează formularul de mai jos pentru a trimite o solicitare de retur. Cererea ajunge la echipa noastră de suport.', 'papetarie-storefront'); ?></p>

      <form method="post" class="pap-return-form">
        <?php wp_nonce_field('pap_return_request', 'pap_return_nonce'); ?>
        <input type="hidden" name="pap_return_request" value="1">

        <p class="form-row form-row-wide">
          <label for="pap-return-order"><?php esc_html_e('Număr comandă', 'papetarie-storefront'); ?></label>
          <input type="text" id="pap-return-order" name="pap_return_order" value="<?php echo esc_attr($prefill_order); ?>" placeholder="<?php esc_attr_e('Ex: 12345', 'papetarie-storefront'); ?>">
        </p>

        <p class="form-row form-row-wide">
          <label for="pap-return-reason"><?php esc_html_e('Motiv retur', 'papetarie-storefront'); ?></label>
          <select id="pap-return-reason" name="pap_return_reason" required>
            <option value=""><?php esc_html_e('Alege motivul', 'papetarie-storefront'); ?></option>
            <?php foreach ($reasons as $key => $label) : ?>
              <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </p>

        <p class="form-row form-row-wide">
          <label for="pap-return-details"><?php esc_html_e('Detalii', 'papetarie-storefront'); ?></label>
          <textarea id="pap-return-details" name="pap_return_details" rows="5" placeholder="<?php esc_attr_e('Descrie pe scurt situația...', 'papetarie-storefront'); ?>"></textarea>
        </p>

        <p class="form-row form-row-wide pap-return-actions">
          <button type="submit" class="button"><?php esc_html_e('Trimite cererea', 'papetarie-storefront'); ?></button>
        </p>
      </form>
    </div>
    <?php
}
add_action('woocommerce_account_retururi_endpoint', 'papetarie_storefront_returns_endpoint_content');

function papetarie_storefront_orders_actions(array $actions, WC_Order $order): array
{
    if (!is_user_logged_in()) {
        return $actions;
    }

    if (in_array($order->get_status(), ['processing', 'completed'], true)) {
        $actions['retururi'] = [
            'url' => add_query_arg('order_id', (string) $order->get_id(), wc_get_account_endpoint_url('retururi')),
            'name' => __('Retur', 'papetarie-storefront'),
        ];
    }

    return $actions;
}
add_filter('woocommerce_my_account_my_orders_actions', 'papetarie_storefront_orders_actions', 10, 2);
