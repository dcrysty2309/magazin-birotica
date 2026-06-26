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

require_once __DIR__ . '/includes/address-book.php';

function papetarie_storefront_enqueue_styles(): void
{
    $stylesheet_path = get_stylesheet_directory() . '/style.css';

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
        file_exists($stylesheet_path) ? (string) filemtime($stylesheet_path) : wp_get_theme()->get('Version')
    );

    $fontawesome_local = WP_PLUGIN_DIR . '/wpforms-lite/assets/lib/font-awesome/css/all.min.css';
    $fontawesome_url = file_exists($fontawesome_local)
        ? content_url('/plugins/wpforms-lite/assets/lib/font-awesome/css/all.min.css')
        : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';

    wp_enqueue_style(
        'papetarie-storefront-fontawesome',
        $fontawesome_url,
        [],
        file_exists($fontawesome_local) ? (string) filemtime($fontawesome_local) : '6.5.2'
    );

    wp_enqueue_style(
        'papetarie-storefront-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        [],
        null
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_styles');

function papetarie_storefront_force_utf8_charset(string $charset): string
{
    return 'UTF-8';
}

add_filter('pre_option_blog_charset', 'papetarie_storefront_force_utf8_charset');
add_filter('option_blog_charset', 'papetarie_storefront_force_utf8_charset');

function papetarie_storefront_dequeue_checkout_legacy_icons(): void
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }

    wp_dequeue_style('storefront-icons');
    wp_deregister_style('storefront-icons');
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_dequeue_checkout_legacy_icons', 20);

function papetarie_storefront_configure_local_mailer($phpmailer): void
{
    $mail_host = (string) getenv('PAP_MAIL_HOST');
    if ('' === $mail_host) {
        return;
    }

    $mail_port = (int) getenv('PAP_MAIL_PORT');
    if ($mail_port <= 0) {
        $mail_port = 1025;
    }

    if (is_object($phpmailer) && method_exists($phpmailer, 'isSMTP')) {
        $phpmailer->isSMTP();
        $phpmailer->Host = $mail_host;
        $phpmailer->Port = $mail_port;
        $phpmailer->SMTPAuth = false;
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPAutoTLS = false;
        $phpmailer->Timeout = 10;
    }
}
add_action('phpmailer_init', 'papetarie_storefront_configure_local_mailer');

function papetarie_storefront_local_mail_from(string $from): string
{
    $override = (string) getenv('PAP_MAIL_FROM');
    return '' !== $override ? $override : $from;
}
add_filter('wp_mail_from', 'papetarie_storefront_local_mail_from');

function papetarie_storefront_local_mail_from_name(string $name): string
{
    $override = (string) getenv('PAP_MAIL_FROM_NAME');
    return '' !== $override ? $override : $name;
}
add_filter('wp_mail_from_name', 'papetarie_storefront_local_mail_from_name');

function papetarie_storefront_general_settings(array $settings): array
{
    $augmented_settings = [];

    foreach ($settings as $setting) {
        $augmented_settings[] = $setting;

        if (!empty($setting['id']) && $setting['id'] === 'woocommerce_price_thousand_sep') {
            $augmented_settings[] = [
                'title' => __('Minimum order amount', 'papetarie-storefront'),
                'id' => 'papetarie_minimum_order_amount',
                'type' => 'number',
                'default' => '50',
                'desc' => __('Order subtotal after discounts, excluding shipping and taxes. Set in RON.', 'papetarie-storefront'),
                'desc_tip' => true,
                'custom_attributes' => [
                    'min' => '0',
                    'step' => '0.01',
                ],
            ];
        }
    }

    return $augmented_settings;
}
add_filter('woocommerce_general_settings', 'papetarie_storefront_general_settings');

function papetarie_storefront_minimum_order_amount(): float
{
    $minimum = (float) get_option('papetarie_minimum_order_amount', 50);
    return $minimum > 0 ? $minimum : 0.0;
}

function papetarie_storefront_format_plain_currency_amount(float $amount): string
{
    $formatted = number_format((float) $amount, 2, '.', '');
    if (substr($formatted, -3) === '.00') {
        $formatted = substr($formatted, 0, -3);
    }

    return $formatted . ' lei';
}

function papetarie_storefront_cart_contents_total_after_discounts(): float
{
    if (!function_exists('WC') || !WC()->cart) {
        return 0.0;
    }

    return max(0.0, (float) WC()->cart->get_cart_contents_total());
}

function papetarie_storefront_cart_has_unavailable_items(): bool
{
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'] ?? null;
        if (!$product instanceof WC_Product || !$product->exists()) {
            return true;
        }

        if (!$product->is_in_stock() && !$product->backorders_allowed()) {
            return true;
        }
    }

    return false;
}

function papetarie_storefront_cart_has_stock_insufficient_items(): bool
{
    if (!function_exists('WC') || !WC()->cart) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'] ?? null;
        $quantity = max(1, (int) ($cart_item['quantity'] ?? 0));

        if (!$product instanceof WC_Product || !$product->exists() || !$product->managing_stock() || $product->backorders_allowed()) {
            continue;
        }

        $stock_quantity = $product->get_stock_quantity();
        if ($stock_quantity !== null && $quantity > max(0, (int) $stock_quantity)) {
            return true;
        }
    }

    return false;
}

function papetarie_storefront_cart_minimum_order_remaining(): float
{
    $remaining = papetarie_storefront_minimum_order_amount() - papetarie_storefront_cart_contents_total_after_discounts();
    return $remaining > 0 ? $remaining : 0.0;
}

function papetarie_storefront_cart_minimum_order_message(): string
{
    $minimum = papetarie_storefront_minimum_order_amount();
    $remaining = papetarie_storefront_cart_minimum_order_remaining();
    $minimum_html = '<strong>' . esc_html(papetarie_storefront_format_plain_currency_amount($minimum)) . '</strong>';
    $remaining_html = '<strong>' . esc_html(papetarie_storefront_format_plain_currency_amount($remaining)) . '</strong>';

    return sprintf(
        __('Valoarea minimă a comenzii este %1$s. Mai adaugă produse în valoare de %2$s pentru a continua.', 'papetarie-storefront'),
        $minimum_html,
        $remaining_html
    );
}

function papetarie_storefront_cart_warning_state(): array
{
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return [
            'type' => 'none',
            'message' => '',
            'visible' => false,
        ];
    }

    if (papetarie_storefront_cart_contents_total_after_discounts() + 0.0001 < papetarie_storefront_minimum_order_amount()) {
        return [
            'type' => 'minimum-order',
            'message' => papetarie_storefront_cart_minimum_order_message(),
            'visible' => true,
        ];
    }

    if (papetarie_storefront_cart_has_unavailable_items()) {
        return [
            'type' => 'unavailable',
            'message' => __('Un produs din coș nu mai este disponibil.', 'papetarie-storefront'),
            'visible' => true,
        ];
    }

    return [
        'type' => 'none',
        'message' => '',
        'visible' => false,
    ];
}

function papetarie_storefront_cart_minimum_order_notice(): string
{
    return sprintf(
        __('Valoarea minimă a comenzii este %s.', 'papetarie-storefront'),
        '<strong>' . esc_html(papetarie_storefront_format_plain_currency_amount(papetarie_storefront_minimum_order_amount())) . '</strong>'
    );
}

function papetarie_storefront_is_cart_updated_notice(string $notice): bool
{
    $normalized_notice = strtolower(remove_accents(trim(wp_strip_all_tags($notice))));

    if ($normalized_notice === '') {
        return false;
    }

    return strpos($normalized_notice, 'cart updated') !== false
        || strpos($normalized_notice, 'am actualizat cos') !== false
        || strpos($normalized_notice, 'actualizat cosul') !== false;
}

function papetarie_storefront_consume_cart_updated_notice(): string
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return '';
    }

    $all_notices = WC()->session->get('wc_notices', []);
    if (!is_array($all_notices) || empty($all_notices['success']) || !is_array($all_notices['success'])) {
        return '';
    }

    $filtered_success_notices = [];
    $consumed_notice = '';

    foreach ($all_notices['success'] as $notice) {
        if (!is_array($notice) || empty($notice['notice'])) {
            $filtered_success_notices[] = $notice;
            continue;
        }

        $message = trim(wp_strip_all_tags((string) $notice['notice']));
        if ($consumed_notice === '' && papetarie_storefront_is_cart_updated_notice($message)) {
            $consumed_notice = $message;
            continue;
        }

        $filtered_success_notices[] = $notice;
    }

    if ($consumed_notice === '') {
        return '';
    }

    if (empty($filtered_success_notices)) {
        unset($all_notices['success']);
    } else {
        $all_notices['success'] = array_values($filtered_success_notices);
    }

    WC()->session->set('wc_notices', empty($all_notices) ? null : $all_notices);

    return $consumed_notice;
}

function papetarie_storefront_is_orphaned_cart_notice(string $notice): bool
{
    $normalized_notice = strtolower(remove_accents(trim(wp_strip_all_tags($notice))));

    if ($normalized_notice === '') {
        return false;
    }

    $patterns = [
        'cart updated',
        'was added to your cart',
        'has been added to your cart',
        'removed.',
        'removed from your cart',
        'can only have 1',
        'choose the quantity of items you wish to add to your cart',
        'choose a product to add to your cart',
        'is already in your cart',
        'could not be added to the cart',
        'could not update the cart',
        'could not remove the product from the cart',
        'could not remove item from cart',
        'the cart is empty',
        'session expired',
    ];

    foreach ($patterns as $pattern) {
        if (strpos($normalized_notice, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function papetarie_storefront_clear_orphaned_cart_notices(): void
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $all_notices = WC()->session->get('wc_notices', []);
    if (!is_array($all_notices) || empty($all_notices)) {
        return;
    }

    $changed = false;

    foreach (['success', 'notice', 'error'] as $notice_type) {
        if (empty($all_notices[$notice_type]) || !is_array($all_notices[$notice_type])) {
            continue;
        }

        $filtered_notices = [];

        foreach ($all_notices[$notice_type] as $notice) {
            if (!is_array($notice) || empty($notice['notice'])) {
                $filtered_notices[] = $notice;
                continue;
            }

            if (papetarie_storefront_is_orphaned_cart_notice((string) $notice['notice'])) {
                $changed = true;
                continue;
            }

            $filtered_notices[] = $notice;
        }

        if (count($filtered_notices) !== count($all_notices[$notice_type])) {
            $changed = true;
        }

        if (empty($filtered_notices)) {
            unset($all_notices[$notice_type]);
        } else {
            $all_notices[$notice_type] = array_values($filtered_notices);
        }
    }

    if (!$changed) {
        return;
    }

    WC()->session->set('wc_notices', empty($all_notices) ? null : $all_notices);
}

function papetarie_storefront_cart_blocked_by_minimum_order(): bool
{
    if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
        return false;
    }

    return papetarie_storefront_cart_contents_total_after_discounts() + 0.0001 < papetarie_storefront_minimum_order_amount();
}

function papetarie_storefront_enforce_minimum_order_amount(): void
{
    if (!papetarie_storefront_cart_blocked_by_minimum_order()) {
        return;
    }

    $notice = papetarie_storefront_cart_minimum_order_notice();
    if (!function_exists('wc_has_notice') || !wc_has_notice($notice, 'error')) {
        wc_add_notice($notice, 'error');
    }
}
add_action('woocommerce_check_cart_items', 'papetarie_storefront_enforce_minimum_order_amount', 20);

function papetarie_storefront_unhook_cart_notices(): void
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);
}
add_action('wp', 'papetarie_storefront_unhook_cart_notices', 20);

function papetarie_storefront_redirect_checkout_for_minimum_order(): void
{
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page() || is_wc_endpoint_url('order-pay')) {
        return;
    }

    if (!papetarie_storefront_cart_blocked_by_minimum_order()) {
        return;
    }

    papetarie_storefront_enforce_minimum_order_amount();
    // Keep the checkout page visible; the notice is enough for now.
}
add_action('template_redirect', 'papetarie_storefront_redirect_checkout_for_minimum_order', 20);

function papetarie_storefront_disable_empty_cart_checkout_redirect(bool $redirect): bool
{
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page() || is_wc_endpoint_url('order-pay')) {
        return $redirect;
    }

    return false;
}
add_filter('woocommerce_checkout_redirect_empty_cart', 'papetarie_storefront_disable_empty_cart_checkout_redirect', 20);

function papetarie_storefront_enqueue_modal_manager_script(): void
{
    $modal_manager_script = get_stylesheet_directory() . '/assets/js/modal-manager.js';
    $modal_manager_version = file_exists($modal_manager_script) ? (string) filemtime($modal_manager_script) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-modal-manager',
        get_stylesheet_directory_uri() . '/assets/js/modal-manager.js',
        [],
        $modal_manager_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_modal_manager_script');

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
    $archive_add_to_cart_script = get_stylesheet_directory() . '/assets/js/archive-add-to-cart.js';
    $archive_add_to_cart_version = file_exists($archive_add_to_cart_script) ? (string) filemtime($archive_add_to_cart_script) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-archive-add-to-cart',
        get_stylesheet_directory_uri() . '/assets/js/archive-add-to-cart.js',
        ['papetarie-storefront-modal-manager'],
        $archive_add_to_cart_version,
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
    if (papetarie_storefront_is_checkout_or_order_received_page()) {
        return;
    }

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
    $cart_drawer_script = get_stylesheet_directory() . '/assets/js/cart-drawer.js';
    $cart_drawer_version = file_exists($cart_drawer_script) ? (string) filemtime($cart_drawer_script) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-cart-drawer',
        get_stylesheet_directory_uri() . '/assets/js/cart-drawer.js',
        ['papetarie-storefront-modal-manager'],
        $cart_drawer_version,
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
                'empty' => __('Coșul tău este gol.', 'papetarie-storefront'),
                'continue' => __('Continuă cumpărăturile', 'papetarie-storefront'),
            ],
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_cart_drawer_script');

function papetarie_storefront_enqueue_cart_page_script(): void
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    $cart_page_script = get_stylesheet_directory() . '/assets/js/cart-page.js';
    $cart_page_version = file_exists($cart_page_script) ? (string) filemtime($cart_page_script) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-cart-page',
        get_stylesheet_directory_uri() . '/assets/js/cart-page.js',
        ['jquery'],
        $cart_page_version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-cart-page',
        'papCartPage',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'messages' => [
                'updateOverlay' => __('Coșul se actualizează...', 'papetarie-storefront'),
                'removeOverlay' => __('Se elimină produsul...', 'papetarie-storefront'),
            ],
            'initialNotice' => ($cart_updated_notice = papetarie_storefront_consume_cart_updated_notice())
                ? [
                    'message' => $cart_updated_notice,
                    'type' => 'success',
                ]
                : null,
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_cart_page_script');

function papetarie_storefront_clear_orphaned_cart_notices_on_account_pages(): void
{
    if (is_admin() || !function_exists('is_account_page') || !is_account_page()) {
        return;
    }

    papetarie_storefront_clear_orphaned_cart_notices();
}
add_action('woocommerce_account_content', 'papetarie_storefront_clear_orphaned_cart_notices_on_account_pages', 9999);

function papetarie_storefront_override_cart_content(string $content): string
{
    if (!function_exists('is_cart') || !is_cart() || !function_exists('wc_get_template')) {
        return $content;
    }

    ob_start();

    if (function_exists('WC') && WC() && WC()->cart && WC()->cart->is_empty()) {
        wc_get_template('cart/cart-empty.php');
    } else {
        wc_get_template('cart/cart.php');
    }

    return (string) ob_get_clean();
}
add_filter('the_content', 'papetarie_storefront_override_cart_content', 99);

function papetarie_storefront_dequeue_cart_fragments(): void
{
    if (is_admin()) {
        return;
    }
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

    $cities_by_county = papetarie_storefront_romania_cities_by_county();
    $saved_addresses = function_exists('papetarie_storefront_address_book_checkout_selection_data')
        ? papetarie_storefront_address_book_checkout_selection_data()
        : [];
    $selected_billing_address_id = function_exists('papetarie_storefront_address_book_checkout_selected_address_id')
        ? papetarie_storefront_address_book_checkout_selected_address_id('billing')
        : '';
    $selected_shipping_address_id = function_exists('papetarie_storefront_address_book_checkout_selected_address_id')
        ? papetarie_storefront_address_book_checkout_selected_address_id('shipping')
        : '';
    $checkout_address_count = function_exists('papetarie_storefront_address_book_checkout_address_count')
        ? papetarie_storefront_address_book_checkout_address_count()
        : 0;
    $checkout_script_path = get_stylesheet_directory() . '/assets/js/checkout.js';
    $checkout_script_version = file_exists($checkout_script_path)
        ? (string) filemtime($checkout_script_path)
        : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-checkout',
        get_stylesheet_directory_uri() . '/assets/js/checkout.js',
        ['jquery'],
        $checkout_script_version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-checkout',
        'papCheckoutData',
        [
            'citiesByCounty' => $cities_by_county,
            'cityPlaceholder' => __('Alege localitatea', 'papetarie-storefront'),
            'countyFirstPlaceholder' => __('Alege județul întâi', 'papetarie-storefront'),
            'isLoggedIn' => is_user_logged_in(),
            'savedAddresses' => $saved_addresses,
            'selectedBillingAddressId' => $selected_billing_address_id,
            'selectedShippingAddressId' => $selected_shipping_address_id,
            'checkoutAddressCount' => $checkout_address_count,
            'isTemporaryCheckoutAddress' => is_user_logged_in() && function_exists('papetarie_storefront_address_book_checkout_has_temporary_address')
                ? papetarie_storefront_address_book_checkout_has_temporary_address()
                : false,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'selectAddressAction' => 'papetarie_storefront_checkout_select_address',
            'selectAddressNonce' => wp_create_nonce('pap_checkout_address'),
            'customerEmail' => is_user_logged_in() && function_exists('papetarie_storefront_address_book_checkout_email')
                ? papetarie_storefront_address_book_checkout_email(get_current_user_id())
                : '',
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_checkout_scripts');

function papetarie_storefront_checkout_title_filter(string $title, int $post_id = 0): string
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return $title;
    }

    $checkout_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('checkout') : (int) get_option('woocommerce_checkout_page_id');
    if ($checkout_page_id > 0 && ($post_id === $checkout_page_id || (int) get_queried_object_id() === $checkout_page_id)) {
        return '';
    }

    if ($post_id > 0 && $post_id === (int) get_queried_object_id()) {
        return '';
    }

    return $title;
}
add_filter('the_title', 'papetarie_storefront_checkout_title_filter', 10, 2);

function papetarie_storefront_hide_checkout_page_title(bool $show_title): bool
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return $show_title;
    }

    return false;
}
add_filter('woocommerce_show_page_title', 'papetarie_storefront_hide_checkout_page_title', 20);

function papetarie_storefront_checkout_notice_hooks(): void
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return;
    }

    remove_action('woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10);
}
add_action('wp', 'papetarie_storefront_checkout_notice_hooks', 20);

function papetarie_storefront_disable_checkout_coupon_form(): void
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return;
    }

    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
}
add_action('wp', 'papetarie_storefront_disable_checkout_coupon_form', 20);

function papetarie_storefront_disable_checkout_order_attribution_inputs(array $actions): array
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return $actions;
    }

    return [];
}
add_filter('wc_order_attribution_stamp_checkout_html_actions', 'papetarie_storefront_disable_checkout_order_attribution_inputs', 20);

function papetarie_storefront_remove_checkout_order_attribution_element(): void
{
    if (!papetarie_storefront_is_checkout_like_page() || !function_exists('wc_get_container')) {
        return;
    }

    $controller_class = '\Automattic\WooCommerce\Internal\Orders\OrderAttributionController';
    if (!class_exists($controller_class)) {
        return;
    }

    try {
        $controller = wc_get_container()->get($controller_class);
    } catch (Throwable $exception) {
        return;
    }

    if (!is_object($controller)) {
        return;
    }

    foreach ([
        'woocommerce_checkout_billing',
        'woocommerce_after_checkout_billing_form',
        'woocommerce_checkout_shipping',
        'woocommerce_after_order_notes',
        'woocommerce_checkout_after_customer_details',
        'woocommerce_register_form',
    ] as $hook) {
        remove_action($hook, [$controller, 'stamp_html_element']);
    }
}
add_action('wp', 'papetarie_storefront_remove_checkout_order_attribution_element', 25);

function papetarie_storefront_remove_checkout_order_review_heading(): void
{
    if (!papetarie_storefront_is_checkout_like_page()) {
        return;
    }

    remove_action('woocommerce_checkout_before_order_review', 'woocommerce_order_review_heading', 10);
}
add_action('wp', 'papetarie_storefront_remove_checkout_order_review_heading', 26);

function papetarie_storefront_cleanup_legacy_checkout_page(): void
{
    if (is_admin() || get_option('pap_checkout_legacy_page_cleaned')) {
        return;
    }

    $legacy_page = get_page_by_path('checkout-2', OBJECT, 'page');
    if (!$legacy_page instanceof WP_Post) {
        update_option('pap_checkout_legacy_page_cleaned', 1);
        return;
    }

    $official_checkout_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('checkout') : (int) get_option('woocommerce_checkout_page_id');
    if ($official_checkout_id > 0 && (int) $legacy_page->ID !== $official_checkout_id) {
        wp_trash_post((int) $legacy_page->ID);
    }

    update_option('pap_checkout_legacy_page_cleaned', 1);
}
add_action('init', 'papetarie_storefront_cleanup_legacy_checkout_page', 20);

function papetarie_storefront_get_checkout_shipping_methods_card_html(): string
{
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return '';
    }

    $shipping_eta = (string) apply_filters('papetarie_storefront_checkout_shipping_eta_label', __('48-168 ore', 'papetarie-storefront'));
    $fallback_shipping_cost = (float) apply_filters('papetarie_storefront_checkout_shipping_default_cost', 0.0);
    $packages = function_exists('WC') && WC() && WC()->shipping() ? WC()->shipping()->get_packages() : [];
    $has_shipping_method = !empty($packages) && WC()->cart->needs_shipping();
    $step_state = papetarie_storefront_checkout_step_state('shipping-methods');
    $body_hidden_attr = ' aria-hidden="false"';

    ob_start();
    ?>
    <section class="pap-checkout-card pap-checkout-card--shipping-methods pap-checkout-step pap-checkout-step--shipping-methods is-step-<?php echo esc_attr($step_state); ?>" data-pap-checkout-section="shipping-methods" data-pap-checkout-step="shipping-methods" data-pap-step-state="<?php echo esc_attr($step_state); ?>" aria-disabled="<?php echo esc_attr($step_state === 'disabled' ? 'true' : 'false'); ?>">
      <div class="pap-checkout-shipping-methods-inline__head">
        <div class="pap-checkout-section-title-row">
          <span class="pap-checkout-section-badge" aria-hidden="true">2</span>
          <h2><?php esc_html_e('Tip de livrare', 'papetarie-storefront'); ?></h2>
        </div>
        <?php if ($step_state === 'disabled') : ?>
          <p class="pap-checkout-card__intro"><?php esc_html_e('Completează adresa de livrare pentru a vedea metoda și costul transportului.', 'papetarie-storefront'); ?></p>
        <?php endif; ?>
      </div>

      <div class="pap-checkout-step__body"<?php echo $body_hidden_attr; ?>>
      <div class="pap-checkout-shipping-method-groups">
        <div class="pap-checkout-shipping-package">
          <?php if ($has_shipping_method) : ?>
            <?php foreach ($packages as $index => $package) : ?>
              <?php
              $available_methods = isset($package['rates']) && is_array($package['rates']) ? $package['rates'] : [];
              $available_methods = array_filter(
                  $available_methods,
                  static function ($rate, $rate_key): bool {
                      return str_starts_with((string) $rate_key, 'flat_rate');
                  },
                  ARRAY_FILTER_USE_BOTH
              );
              $chosen_method = function_exists('wc_get_chosen_shipping_method_for_package') ? (string) wc_get_chosen_shipping_method_for_package($index, $package) : '';
              $package_name = isset($package['package_name']) && is_string($package['package_name']) ? $package['package_name'] : '';
              $selected_rate = null;
              $selected_rate_key = '';
              if (!empty($available_methods)) {
                  foreach ($available_methods as $rate_key => $rate) {
                      if ((string) $chosen_method === (string) $rate_key) {
                          $selected_rate = $rate;
                          $selected_rate_key = (string) $rate_key;
                          break;
                      }
                  }

                  if (!$selected_rate) {
                      $selected_rate_key = (string) array_key_first($available_methods);
                      $selected_rate = $available_methods[$selected_rate_key] ?? reset($available_methods);
                  }
              }
              ?>
              <?php if ($package_name !== '' || count($packages) > 1) : ?>
                <p class="pap-checkout-shipping-package__title">
                  <?php echo esc_html($package_name !== '' ? $package_name : sprintf(__('Pachet %d', 'papetarie-storefront'), $index + 1)); ?>
                </p>
              <?php endif; ?>

              <?php if ($selected_rate instanceof WC_Shipping_Rate && $selected_rate_key !== '') :
                  $input_id = sprintf('shipping_method_%d_%s', (int) $index, sanitize_title($selected_rate_key));
                  $shipping_title = strtoupper(trim((string) $selected_rate->get_label()));
                  $shipping_cost = wc_price((float) $selected_rate->get_cost());
                  ?>
                  <div class="pap-checkout-shipping-method is-selected pap-checkout-shipping-summary">
                    <label for="<?php echo esc_attr($input_id); ?>">
                      <input
                        type="radio"
                        class="shipping_method input-radio"
                        name="<?php echo esc_attr(sprintf('shipping_method[%d]', (int) $index)); ?>"
                        id="<?php echo esc_attr($input_id); ?>"
                        value="<?php echo esc_attr($selected_rate_key); ?>"
                        data-index="<?php echo esc_attr((int) $index); ?>"
                        checked
                      >
                      <span class="pap-checkout-shipping-summary__icon" aria-hidden="true">
                        <i class="fa-solid fa-truck" aria-hidden="true"></i>
                      </span>
                      <span class="pap-checkout-shipping-summary__copy">
                        <span class="pap-checkout-shipping-summary__title"><?php echo esc_html($shipping_title !== '' ? $shipping_title : __('STANDARD', 'papetarie-storefront')); ?></span>
                        <span class="pap-checkout-shipping-summary__meta"><?php echo esc_html(sprintf(__('Livrare %s', 'papetarie-storefront'), $shipping_eta)); ?></span>
                      </span>
                      <span class="pap-checkout-shipping-summary__price"><?php echo wp_kses_post($shipping_cost); ?></span>
                    </label>
                  </div>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="pap-checkout-shipping-summary is-static">
              <div class="pap-checkout-shipping-summary__icon" aria-hidden="true">
                <i class="fa-solid fa-truck" aria-hidden="true"></i>
              </div>
              <div class="pap-checkout-shipping-summary__copy">
                <span class="pap-checkout-shipping-summary__title"><?php esc_html_e('STANDARD', 'papetarie-storefront'); ?></span>
                <span class="pap-checkout-shipping-summary__meta"><?php echo esc_html(sprintf(__('Livrare %s', 'papetarie-storefront'), $shipping_eta)); ?></span>
              </div>
              <span class="pap-checkout-shipping-summary__price"><?php echo wp_kses_post(wc_price($fallback_shipping_cost)); ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php echo papetarie_storefront_get_checkout_products_inline_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
    </section>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_render_checkout_shipping_methods_card(): void
{
    echo papetarie_storefront_get_checkout_shipping_methods_card_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function papetarie_storefront_get_checkout_products_inline_html(): string
{
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return '';
    }

    $cart = WC()->cart;
    $cart_items = $cart->get_cart();
    if (empty($cart_items)) {
        return '';
    }

    $visible_limit = 3;
    $visible_item_count = 0;
    foreach ($cart_items as $cart_item) {
        $product = $cart_item['data'] ?? null;
        if ($product instanceof WC_Product && $product->exists() && (int) ($cart_item['quantity'] ?? 0) > 0) {
            $visible_item_count++;
        }
    }

    $has_more_products = $visible_item_count > $visible_limit;
    $capture_output = static function (callable $callback): string {
        ob_start();
        $callback();
        return (string) ob_get_clean();
    };

    ob_start();
    ?>
    <div class="pap-checkout-shipping-products" data-pap-checkout-section="checkout-products">
      <div class="pap-checkout-product-list" role="list" aria-label="<?php esc_attr_e('Produse din comandă', 'papetarie-storefront'); ?>">
        <?php do_action('woocommerce_review_order_before_cart_contents'); ?>

        <?php $visible_index = 0; ?>
        <?php foreach ($cart_items as $cart_item_key => $cart_item) : ?>
          <?php
          $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
          if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
              continue;
          }

          $visible_index++;
          $is_faded = $has_more_products && $visible_index === $visible_limit;
          $is_hidden = $has_more_products && $visible_index > $visible_limit;

          $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
          $product_thumbnail = $_product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'alt' => $_product->get_name()]);
          if (!$product_thumbnail) {
              $product_thumbnail = '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($_product->get_name()) . '" loading="lazy">';
          }
          $product_name = wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key));
          $formatted_meta = $capture_output(static function () use ($cart_item): void {
              echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          });
          ?>
          <article class="pap-checkout-summary-item<?php echo $is_hidden ? ' is-hidden' : ''; ?><?php echo $is_faded ? ' is-faded' : ''; ?>" role="listitem" <?php echo $is_hidden ? 'hidden aria-hidden="true"' : ''; ?>>
            <div class="pap-checkout-summary-item__media">
              <?php if ($product_permalink !== '') : ?>
                <a class="pap-checkout-summary-thumb" href="<?php echo esc_url($product_permalink); ?>">
                  <?php echo wp_kses_post($product_thumbnail); ?>
                </a>
              <?php else : ?>
                <span class="pap-checkout-summary-thumb" aria-hidden="true">
                  <?php echo wp_kses_post($product_thumbnail); ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="pap-checkout-summary-item__copy">
              <div class="pap-checkout-summary-item__title-row">
                <h3 class="pap-checkout-summary-item__title">
                  <?php echo $product_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </h3>
                <span class="pap-checkout-summary-item__qty">× <?php echo esc_html((string) (int) $cart_item['quantity']); ?></span>
              </div>
              <?php if ($formatted_meta !== '') : ?>
                <div class="pap-checkout-summary-item__meta">
                  <?php echo wp_kses_post($formatted_meta); ?>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>

        <?php do_action('woocommerce_review_order_after_cart_contents'); ?>
      </div>

      <?php if ($has_more_products) : ?>
        <div class="pap-checkout-products-toggle-wrap">
          <button
            type="button"
            class="button pap-cart-delete-modal-button pap-cart-delete-modal-button--secondary pap-checkout-products-toggle"
            data-checkout-products-toggle
            aria-expanded="false"
            data-label-more="Arata mai mult +"
            data-label-less="Arata mai putin -"
          >
            Arata mai mult +
          </button>
        </div>
      <?php endif; ?>

    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_checkout_field_value(string $field_key): string
{
    if (!function_exists('WC') || !WC() || !WC()->checkout()) {
        return '';
    }

    $value = WC()->checkout()->get_value($field_key);
    if (is_string($value)) {
        $value = trim($value);
        if ($value !== '') {
            return $value;
        }
    }

    $customer = WC()->customer;
    if ($customer instanceof WC_Customer) {
        $getter = 'get_' . $field_key;
        if (method_exists($customer, $getter)) {
            return trim((string) $customer->{$getter}());
        }
    }

    return '';
}

function papetarie_storefront_checkout_shipping_address_mode(): string
{
    $mode = isset($_COOKIE['pap_checkout_shipping_mode']) ? sanitize_key((string) wp_unslash($_COOKIE['pap_checkout_shipping_mode'])) : '';

    if (in_array($mode, ['edit', 'summary'], true)) {
        return $mode;
    }

    return 'edit';
}

function papetarie_storefront_checkout_guest_shipping_snapshot(): array
{
    $raw = '';

    if (isset($_POST['pap_guest_shipping_snapshot'])) {
        $raw = (string) wp_unslash($_POST['pap_guest_shipping_snapshot']);
    } elseif (isset($_COOKIE['pap_checkout_shipping_snapshot'])) {
        $raw = (string) wp_unslash($_COOKIE['pap_checkout_shipping_snapshot']);
    }

    if ($raw === '') {
        return [];
    }

    $decoded = json_decode(rawurldecode($raw), true);
    if (!is_array($decoded)) {
        return [];
    }

    $allowed_keys = [
        '#billing_first_name',
        '#billing_last_name',
        '#billing_phone',
        '#billing_email',
        '#shipping_address_1',
        '#shipping_city',
        '#shipping_state',
        '#shipping_postcode',
        '#order_comments',
    ];

    $snapshot = [];
    foreach ($allowed_keys as $key) {
        $raw_value = isset($decoded[$key]) ? (string) $decoded[$key] : '';
        $value = $key === '#order_comments'
            ? sanitize_textarea_field($raw_value)
            : sanitize_text_field($raw_value);
        $snapshot[$key] = trim($value);
    }

    return $snapshot;
}

function papetarie_storefront_checkout_guest_shipping_summary_lines(): array
{
    if (!function_exists('WC') || !WC() || !WC()->checkout()) {
        return [];
    }

    $snapshot = papetarie_storefront_checkout_guest_shipping_snapshot();
    $checkout = WC()->checkout();
    $first_name = $snapshot['#billing_first_name'] ?: trim((string) $checkout->get_value('billing_first_name'));
    $last_name = $snapshot['#billing_last_name'] ?: trim((string) $checkout->get_value('billing_last_name'));
    $phone = $snapshot['#billing_phone'] ?: trim((string) $checkout->get_value('billing_phone'));
    $email = $snapshot['#billing_email'] ?: trim((string) $checkout->get_value('billing_email'));
    $address_1 = $snapshot['#shipping_address_1'] ?: trim((string) $checkout->get_value('shipping_address_1'));
    $city = $snapshot['#shipping_city'] ?: trim((string) $checkout->get_value('shipping_city'));
    $state = $snapshot['#shipping_state'] ?: trim((string) $checkout->get_value('shipping_state'));
    $postcode = $snapshot['#shipping_postcode'] ?: trim((string) $checkout->get_value('shipping_postcode'));

    $lines = [];
    $full_name = trim($first_name . ' ' . $last_name);
    $address_line = trim((string) $address_1);
    $city_line = trim(implode(', ', array_filter([$city, $state, $postcode])));

    if ($full_name !== '') {
        $lines[] = $full_name;
    }

    if ($address_line !== '') {
        $lines[] = $address_line;
    }

    if ($city_line !== '') {
        $lines[] = $city_line;
    }

    if ($phone !== '') {
        $lines[] = $phone;
    }

    if ($email !== '') {
        $lines[] = $email;
    }

    return $lines;
}

function papetarie_storefront_checkout_address_card_icon_svg(string $kind): string
{
    $icons = [
        'user' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/></svg>',
        'location' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true"><path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="1.8"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true"><path d="M6.6 3.6l2.1 4.2c.3.6.2 1.2-.3 1.7l-1 1c1.2 2.4 3.1 4.3 5.5 5.5l1-1c.5-.5 1.1-.6 1.7-.3l4.2 2.1c.6.3.9.9.8 1.5l-.4 2c-.1.6-.7 1.1-1.3 1.1C10 21.4 2.6 14 2.6 5.1c0-.6.5-1.2 1.1-1.3l2-.4c.4-.1.8 0 .9.2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'email' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true"><rect x="4" y="6" width="16" height="12" rx="1.5" stroke="currentColor" stroke-width="1.8"/><path d="M5 8l7 5 7-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];

    return $icons[$kind] ?? $icons['location'];
}

function papetarie_storefront_get_checkout_guest_shipping_summary_html(): string
{
    $lines = papetarie_storefront_checkout_guest_shipping_summary_lines();
    $full_name = array_shift($lines) ?: '';

    ob_start();
    ?>
    <div class="pap-checkout-address-card">
        <div class="pap-checkout-address-card__head">
            <div class="pap-checkout-address-card__title-copy pap-checkout-address-card__title-copy--with-icon">
                <span class="pap-checkout-address-card__user-icon" aria-hidden="true">
                    <?php echo papetarie_storefront_checkout_address_card_icon_svg('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
                <p class="pap-checkout-address-card__title"><?php esc_html_e('Adresa de livrare', 'papetarie-storefront'); ?></p>
                <?php if ($full_name !== '') : ?>
                    <p class="pap-checkout-address-card__name"><?php echo esc_html($full_name); ?></p>
                <?php endif; ?>
            </div>
            <button type="button" class="pap-checkout-address-card__action" data-pap-guest-shipping-edit>
                <?php esc_html_e('Modifică', 'papetarie-storefront'); ?>
            </button>
        </div>
        <?php if (!empty($lines)) : ?>
            <div class="pap-checkout-address-card__body">
                <?php foreach ($lines as $index => $line) : ?>
                    <p class="address-summary-row">
                        <?php if (0 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('location'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php elseif (1 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php elseif (2 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('email'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php endif; ?>
                        <span class="pap-checkout-address-card__line-text"><?php echo esc_html($line); ?></span>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="pap-checkout-address-card__empty">
                <strong><?php esc_html_e('Nu ai completat încă această adresă.', 'papetarie-storefront'); ?></strong>
                <p><?php esc_html_e('Deschide formularul ca să completezi datele necesare pentru comandă.', 'papetarie-storefront'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_get_checkout_auth_shipping_summary_html(): string
{
    $lines = papetarie_storefront_checkout_guest_shipping_summary_lines();
    $full_name = array_shift($lines) ?: '';

    ob_start();
    ?>
    <div class="pap-checkout-address-card">
        <div class="pap-checkout-address-card__head">
            <div class="pap-checkout-address-card__title-copy pap-checkout-address-card__title-copy--with-icon">
                <span class="pap-checkout-address-card__user-icon" aria-hidden="true">
                    <?php echo papetarie_storefront_checkout_address_card_icon_svg('user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </span>
                <p class="pap-checkout-address-card__title"><?php esc_html_e('Adresa de livrare', 'papetarie-storefront'); ?></p>
                <?php if ($full_name !== '') : ?>
                    <p class="pap-checkout-address-card__name"><?php echo esc_html($full_name); ?></p>
                <?php endif; ?>
            </div>
            <button type="button" class="pap-checkout-address-card__action" data-pap-auth-temporary-edit>
                <?php esc_html_e('Modifică', 'papetarie-storefront'); ?>
            </button>
        </div>
        <?php if (!empty($lines)) : ?>
            <div class="pap-checkout-address-card__body">
                <?php foreach ($lines as $index => $line) : ?>
                    <p class="address-summary-row">
                        <?php if (0 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('location'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php elseif (1 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('phone'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php elseif (2 === $index) : ?>
                            <span class="pap-checkout-address-card__icon address-summary-icon" aria-hidden="true">
                                <?php echo papetarie_storefront_checkout_address_card_icon_svg('email'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </span>
                        <?php endif; ?>
                        <span class="pap-checkout-address-card__line-text"><?php echo esc_html($line); ?></span>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="pap-checkout-address-card__empty">
                <strong><?php esc_html_e('Nu ai completat încă această adresă.', 'papetarie-storefront'); ?></strong>
                <p><?php esc_html_e('Deschide formularul ca să completezi datele necesare pentru comandă.', 'papetarie-storefront'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_checkout_step_state(string $step): string
{
    if (!function_exists('is_user_logged_in') || is_user_logged_in()) {
        return 'active';
    }

    $shipping_mode = papetarie_storefront_checkout_shipping_address_mode();
    $normalized_step = sanitize_key($step);

    if ($normalized_step === 'shipping-address') {
        return $shipping_mode === 'summary' ? 'complete' : 'active';
    }

    if ($normalized_step === 'shipping-methods') {
        return $shipping_mode === 'summary' ? 'active' : 'disabled';
    }

    return 'disabled';
}

function papetarie_storefront_get_checkout_shipping_address_html(): string
{
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return '';
    }

    ob_start();
    wc_get_template('checkout/form-shipping.php', ['checkout' => WC()->checkout()]);

    return (string) ob_get_clean();
}

function papetarie_storefront_get_checkout_payment_html(): string
{
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return '';
    }

    $suppress_order_button = static function () {
        return '';
    };

    add_filter('woocommerce_order_button_html', $suppress_order_button, 9999);

    ob_start();
    woocommerce_checkout_payment();
    $html = (string) ob_get_clean();

    remove_filter('woocommerce_order_button_html', $suppress_order_button, 9999);

    return $html;
}

function papetarie_storefront_get_checkout_order_review_html(): string
{
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return '';
    }

    ob_start();
    wc_get_template('checkout/review-order.php');

    return (string) ob_get_clean();
}

function papetarie_storefront_render_checkout_order_review(): void
{
    remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
    do_action('woocommerce_checkout_order_review');
    add_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
}

function papetarie_storefront_checkout_update_review_fragments(array $fragments): array
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return $fragments;
    }

    $fragments['section[data-pap-checkout-section="order-summary"]'] = papetarie_storefront_get_checkout_order_review_html();
    $fragments['div.woocommerce-checkout-review-order-table'] = papetarie_storefront_get_checkout_order_review_html();
    $fragments['section[data-pap-checkout-section="shipping-address"]'] = papetarie_storefront_get_checkout_shipping_address_html();
    $fragments['section[data-pap-checkout-section="shipping-methods"]'] = papetarie_storefront_get_checkout_shipping_methods_card_html();
    $fragments['section[data-pap-checkout-section="payment"]'] = papetarie_storefront_get_checkout_payment_html();

    return $fragments;
}
add_filter('woocommerce_update_order_review_fragments', 'papetarie_storefront_checkout_update_review_fragments', 20);

function papetarie_storefront_filter_checkout_payment_gateways(array $gateways): array
{
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return $gateways;
    }

    foreach ($gateways as $gateway_id => $gateway) {
        if (in_array($gateway_id, ['cod', 'paypal'], true)) {
            continue;
        }

        unset($gateways[$gateway_id]);
    }

    return $gateways;
}
add_filter('woocommerce_available_payment_gateways', 'papetarie_storefront_filter_checkout_payment_gateways', 20);

function papetarie_storefront_filter_checkout_shipping_rates(array $rates, array $package): array
{
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return $rates;
    }

    foreach ($rates as $rate_id => $rate) {
        if (str_starts_with((string) $rate_id, 'free_shipping') || str_starts_with((string) $rate_id, 'local_pickup')) {
            unset($rates[$rate_id]);
        }
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'papetarie_storefront_filter_checkout_shipping_rates', 20, 2);

function papetarie_storefront_filter_gateway_titles(string $title, string $gateway_id): string
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return $title;
    }

    return match ($gateway_id) {
        'cod' => __('Plată la livrare', 'papetarie-storefront'),
        'paypal' => __('Plată cu cardul', 'papetarie-storefront'),
        default => $title,
    };
}
add_filter('woocommerce_gateway_title', 'papetarie_storefront_filter_gateway_titles', 20, 2);

function papetarie_storefront_enqueue_account_scripts(): void
{
    if (is_user_logged_in()) {
        return;
    }

    $account_script = get_stylesheet_directory() . '/assets/js/account.js';
    $account_script_version = file_exists($account_script) ? (string) filemtime($account_script) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-account-ui',
        get_stylesheet_directory_uri() . '/assets/js/account.js',
        ['jquery'],
        $account_script_version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-account-ui',
        'papAccountUi',
        [
            'loginUrl' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(),
            'socialShortcode' => shortcode_exists('nextend_social_login') ? 'nextend_social_login' : '',
            'googleLoginUrl' => (string) apply_filters('papetarie_storefront_google_login_url', ''),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxAction' => 'pap_auth_login',
            'currentUserAction' => 'pap_auth_current_user',
            'lostPasswordAction' => 'pap_auth_lost_password',
            'ajaxNonce' => wp_create_nonce('pap_auth_login'),
            'registerAction' => 'pap_auth_register',
            'registerNonce' => wp_create_nonce('pap_auth_register'),
            'lostPasswordNonce' => wp_create_nonce('pap_auth_lost_password'),
            'modalSelector' => '#pap-auth-modal',
            'accountSelector' => '[data-pap-auth-account]',
            'authState' => papetarie_storefront_get_current_user_auth_state(),
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
    $json = wp_json_encode([
        'success' => true,
        'data' => $data,
    ]);

    if (!headers_sent()) {
        nocache_headers();
        status_header($status_code);
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Connection: close');
        header('Content-Length: ' . strlen((string) $json));
    }

    echo $json;

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }

    @flush();

    if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }

    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }

    exit;
}

function papetarie_storefront_send_json_error_fast(array $data, int $status_code = 400): void
{
    $json = wp_json_encode([
        'success' => false,
        'data' => $data,
    ]);

    if (!headers_sent()) {
        nocache_headers();
        status_header($status_code);
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Connection: close');
        header('Content-Length: ' . strlen((string) $json));
    }

    echo $json;

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }

    @flush();

    if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }

    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }

    exit;
}

function papetarie_storefront_body_class(array $classes): array
{
    $classes[] = 'theme-papetarie';

    if (papetarie_storefront_is_checkout_or_order_received_page()) {
        $classes[] = 'pap-simplified-checkout-page';
    }

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
        '<strong>Error:</strong> The password you entered for the email address %s is incorrect.' => '<strong>Eroare:</strong> Parola pentru acest email este incorectă.',
        '<strong>Error:</strong> The password you entered for the username %s is incorrect.' => '<strong>Eroare:</strong> Parola pentru acest utilizator este incorectă.',
        '<strong>Error:</strong> Invalid username, email address or incorrect password.' => '<strong>Eroare:</strong> Datele de autentificare sunt incorecte.',
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
        'Proceed to checkout' => 'Continuă către finalizare',
        'Cart' => 'Coș',
        'Checkout' => 'Finalizare comandă',
        'Subtotal' => 'Subtotal',
        'Total' => 'Total',
        'Update cart' => 'Actualizează coșul',
        'Apply coupon' => 'Aplică',
        'Coupon code' => 'Cod cupon',
        'Quantity' => 'Cantitate',
        'Remove' => 'Șterge',
        'Shipping' => 'Transport',
        'Cart totals' => 'Sumar comandă',
        'Estimated total' => 'Total estimat',
        'Free shipping' => 'Transport gratuit',
        'Billing details' => 'Date de facturare',
        'Shipping details' => 'Date de livrare',
        'Place order' => 'Plasează comanda',
        'Sorry, your session has expired.' => 'Sesiunea ta a expirat.',
        'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>' => 'Sesiunea ta a expirat. <a href="%s" class="wc-backward">Înapoi la magazin</a>',
        'Return to shop' => 'Înapoi la magazin',
        'An unexpected error has occurred. Please refresh the page and try again.' => 'A apărut o eroare neașteptată. Te rugăm să reîmprospătezi pagina și să încerci din nou.',
        'Please refresh the page and try again.' => 'Te rugăm să reîmprospătezi pagina și să încerci din nou.',
        'Please enter a valid email address.' => 'Introdu o adresă de email validă.',
        'Please enter a valid account username.' => 'Introdu un nume de utilizator valid.',
        'Please enter a password.' => 'Introdu parola.',
        'Passwords do not match.' => 'Parolele nu se potrivesc.',
        'An account is already registered with your email address. Please log in.' => 'Cont existent. Autentifică-te sau folosește alt email.',
        'An account is already registered with your email address. Please log in or use a different email address.' => 'Cont existent. Autentifică-te sau folosește alt email.',
        'An account is already registered with your email address.' => 'Cont existent. Folosește alt email.',
        'Please enter a valid account username and/or password.' => 'Datele de autentificare sunt incorecte.',
    ];

    return $map[$text] ?? $translated;
}
add_filter('gettext', 'papetarie_storefront_translate_frontend_strings', 20, 3);

function papetarie_storefront_translate_registration_email_exists(string $message, string $email): string
{
    return __('Contul există deja. Folosește alt email.', 'papetarie-storefront');
}
add_filter('woocommerce_registration_error_email_exists', 'papetarie_storefront_translate_registration_email_exists', 10, 2);

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
    $cities = [];

    foreach (papetarie_storefront_romania_cities_by_county() as $county_cities) {
        foreach ($county_cities as $city) {
            $cities[$city] = $city;
        }
    }

    return array_values($cities);
}

function papetarie_storefront_romania_localities_by_county(): array
{
    static $localities_by_county = null;

    if (null !== $localities_by_county) {
        return $localities_by_county;
    }

    $dataset_path = get_stylesheet_directory() . '/data/ro-localities-by-county.json';
    if (!file_exists($dataset_path)) {
        $localities_by_county = [];
        return $localities_by_county;
    }

    $decoded = json_decode((string) file_get_contents($dataset_path), true);
    if (!is_array($decoded)) {
        $localities_by_county = [];
        return $localities_by_county;
    }

    $counties = papetarie_storefront_romania_counties();
    $ordered = [];

    foreach ($counties as $county_code => $county_name) {
        $county_localities = $decoded[$county_code] ?? [];
        if (!is_array($county_localities)) {
            $county_localities = [];
        }

        $county_localities = array_values(array_filter(array_map(
            static fn ($city): string => trim((string) $city),
            $county_localities
        ), static fn (string $city): bool => $city !== ''));

        $ordered[$county_code] = array_values(array_unique($county_localities));
    }

    $localities_by_county = $ordered;

    return $localities_by_county;
}

function papetarie_storefront_romania_city_county_map(): array
{
    $city_map = [];

    foreach (papetarie_storefront_romania_cities_by_county() as $county_code => $county_cities) {
        foreach ($county_cities as $city) {
            $city_key = papetarie_storefront_normalize_city_key($city);
            if (!isset($city_map[$city_key])) {
                $city_map[$city_key] = [];
            }

            if (!in_array($county_code, $city_map[$city_key], true)) {
                $city_map[$city_key][] = $county_code;
            }
        }
    }

    return $city_map;
}

function papetarie_storefront_romania_cities_by_county(): array
{
    return papetarie_storefront_romania_localities_by_county();
}

function papetarie_storefront_checkout_city_options_for_county(string $county_code): array
{
    $county_code = sanitize_key($county_code);
    $cities_by_county = papetarie_storefront_romania_cities_by_county();

    if (!isset($cities_by_county[$county_code]) || !is_array($cities_by_county[$county_code])) {
        return [];
    }

    $options = [];

    foreach ($cities_by_county[$county_code] as $city) {
        $options[$city] = $city;
    }

    return $options;
}

function papetarie_storefront_normalize_city_key(string $city): string
{
    $city = trim(preg_replace('/\s+/', ' ', $city));
    $city = strtolower(remove_accents($city));

    return $city;
}

function papetarie_storefront_country_options(): array
{
    return [
        'RO' => __('România', 'papetarie-storefront'),
    ];
}

function papetarie_storefront_is_checkout_like_page(): bool
{
    if (is_admin() || !function_exists('is_singular') || !is_singular()) {
        return false;
    }

    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return false;
    }

    if (function_exists('is_checkout') && is_checkout()) {
        return true;
    }

    $post_id = (int) get_queried_object_id();
    if ($post_id <= 0) {
        return false;
    }

    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return false;
    }

    if (function_exists('has_block') && has_block('woocommerce/checkout', $content)) {
        return true;
    }

    if (function_exists('has_shortcode') && has_shortcode($content, 'woocommerce_checkout')) {
        return true;
    }

    return str_contains($content, 'woocommerce-checkout');
}

function papetarie_storefront_is_checkout_or_order_received_page(): bool
{
    if (function_exists('is_checkout') && is_checkout()) {
        return true;
    }

    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return true;
    }

    return false;
}

function papetarie_storefront_get_checkout_support_details(): array
{
    $phone = (string) apply_filters('papetarie_storefront_checkout_support_phone', '0740 123 456');
    $support_url = (string) apply_filters(
        'papetarie_storefront_checkout_support_url',
        function_exists('wc_get_account_endpoint_url')
            ? wc_get_account_endpoint_url('suport')
            : home_url('/my-account/suport/')
    );

    $phone = trim($phone);
    $support_url = trim($support_url);

    if ($support_url === '') {
        $support_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    }

    return [
        'phone' => $phone,
        'support_url' => $support_url,
    ];
}

function papetarie_storefront_get_checkout_header_html(): string
{
    $support = papetarie_storefront_get_checkout_support_details();
    $support_url = $support['support_url'];
    $phone = $support['phone'];

    ob_start();
    ?>
    <header class="pap-site-header pap-site-header--checkout" role="banner">
      <div class="pap-shell pap-checkout-header">
        <a class="pap-checkout-header__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Acasă', 'papetarie-storefront'); ?>">
          <span class="pap-checkout-header__logo-image">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-supplyhub-cropped.png'); ?>" alt="<?php esc_attr_e('SupplyHub Stationery Solutions', 'papetarie-storefront'); ?>">
          </span>
        </a>

        <div class="pap-checkout-header__support" aria-label="<?php esc_attr_e('Suport', 'papetarie-storefront'); ?>">
          <span class="pap-checkout-header__support-icon" aria-hidden="true"><i class="fa-solid fa-headset pap-checkout-header__support-fa"></i></span>
          <div class="pap-checkout-header__support-content">
            <span class="pap-checkout-header__support-label"><?php esc_html_e('Ai nevoie de ajutor?', 'papetarie-storefront'); ?></span>
            <div class="pap-checkout-header__support-body">
              <?php if ($phone !== '') : ?>
                <div class="pap-checkout-header__support-item">
                  <strong><?php echo esc_html($phone); ?></strong>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <a class="pap-checkout-header__support-compact" href="<?php echo esc_url($support_url); ?>" aria-label="<?php esc_attr_e('Suport', 'papetarie-storefront'); ?>">
          <span class="pap-checkout-header__support-icon" aria-hidden="true"><i class="fa-solid fa-headset pap-checkout-header__support-fa"></i></span>
        </a>
      </div>
    </header>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_normalize_address_fields(array $fields): array
{
    $counties = papetarie_storefront_romania_counties();
    $country_options = papetarie_storefront_country_options();

    $field_map = [
        'first_name' => [
            'label' => __('Prenume', 'papetarie-storefront'),
            'placeholder' => __('Prenume', 'papetarie-storefront'),
            'priority' => 10,
            'autocomplete' => 'given-name',
            'required' => true,
        ],
        'last_name' => [
            'label' => __('Nume', 'papetarie-storefront'),
            'placeholder' => __('Nume', 'papetarie-storefront'),
            'priority' => 20,
            'autocomplete' => 'family-name',
            'required' => true,
        ],
        'company' => [
            'label' => __('Firmă', 'papetarie-storefront'),
            'placeholder' => __('Denumire firmă', 'papetarie-storefront'),
            'priority' => 30,
            'required' => false,
        ],
        'address_1' => [
            'label' => __('Stradă și număr', 'papetarie-storefront'),
            'placeholder' => __('Strada Exemplu 12', 'papetarie-storefront'),
            'priority' => 40,
            'autocomplete' => 'address-line1',
            'required' => true,
        ],
        'address_2' => [
            'label' => __('Detalii adresă', 'papetarie-storefront'),
            'placeholder' => __('Bloc, scară, etaj, apartament', 'papetarie-storefront'),
            'priority' => 50,
            'autocomplete' => 'address-line2',
            'required' => false,
        ],
        'city' => [
            'label' => __('Localitate', 'papetarie-storefront'),
            'placeholder' => __('Alege localitatea', 'papetarie-storefront'),
            'type' => 'select',
            'options' => ['' => __('Alege localitatea', 'papetarie-storefront')],
            'custom_attributes' => [
                'data-placeholder' => __('Alege localitatea', 'papetarie-storefront'),
            ],
            'class' => ['wc-enhanced-select'],
            'priority' => 60,
            'autocomplete' => 'address-level2',
            'required' => true,
        ],
        'state' => [
            'label' => __('Județ', 'papetarie-storefront'),
            'type' => 'select',
            'options' => ['' => __('Alege județul', 'papetarie-storefront')] + $counties,
            'class' => ['wc-enhanced-select'],
            'priority' => 70,
            'required' => true,
        ],
        'postcode' => [
            'label' => __('Cod poștal', 'papetarie-storefront'),
            'placeholder' => __('123456', 'papetarie-storefront'),
            'priority' => 80,
            'autocomplete' => 'postal-code',
            'required' => true,
        ],
        'country' => [
            'label' => __('Țară', 'papetarie-storefront'),
            'type' => 'select',
            'options' => $country_options,
            'priority' => 90,
            'required' => true,
        ],
    ];

    foreach ($field_map as $field_key => $overrides) {
        if (!isset($fields[$field_key])) {
            continue;
        }

        $fields[$field_key] = array_merge($fields[$field_key], $overrides);
    }

    return $fields;
}

function papetarie_storefront_default_address_fields(array $fields): array
{
    return papetarie_storefront_normalize_address_fields($fields);
}
add_filter('woocommerce_default_address_fields', 'papetarie_storefront_default_address_fields');

function papetarie_storefront_billing_fields(array $fields): array
{
    $fields = papetarie_storefront_normalize_address_fields($fields);

    if (isset($fields['phone'])) {
        $fields['phone']['label'] = __('Telefon', 'papetarie-storefront');
        $fields['phone']['placeholder'] = __('0712 345 678', 'papetarie-storefront');
        $fields['phone']['priority'] = 35;
        $fields['phone']['required'] = true;
    }

    if (isset($fields['email'])) {
        $fields['email']['label'] = __('Email', 'papetarie-storefront');
        $fields['email']['placeholder'] = __('nume@exemplu.ro', 'papetarie-storefront');
        $fields['email']['priority'] = 36;
        $fields['email']['required'] = true;
    }

    return $fields;
}
add_filter('woocommerce_billing_fields', 'papetarie_storefront_billing_fields');

function papetarie_storefront_shipping_fields(array $fields): array
{
    $fields = papetarie_storefront_normalize_address_fields($fields);

    foreach (['phone', 'email'] as $field_key) {
        if (isset($fields[$field_key])) {
            unset($fields[$field_key]);
        }
    }

    return $fields;
}
add_filter('woocommerce_shipping_fields', 'papetarie_storefront_shipping_fields');

function papetarie_storefront_account_initials(string $display_name): string
{
    $display_name = trim(preg_replace('/\s+/', ' ', $display_name));
    if ($display_name === '') {
        return 'A';
    }

    $parts = preg_split('/\s+/', $display_name) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
    }

    $initials = strtoupper($initials);

    return $initials !== '' ? $initials : 'A';
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

function papetarie_storefront_cart_quantity_bounds(WC_Product $product): array
{
    $min_value = max(1, (int) $product->get_min_purchase_quantity());
    $max_value = (int) $product->get_max_purchase_quantity();
    $max_value = $max_value > 0 ? $max_value : 0;

    if ($product->backorders_allowed() && !$product->is_sold_individually()) {
        $max_value = 0;
    }

    if ($max_value > 0 && $max_value < $min_value) {
        $max_value = $min_value;
    }

    return [$min_value, $max_value];
}

function papetarie_storefront_render_cart_item_row_html(string $cart_item_key, array $cart_item): string
{
    $product = $cart_item['data'] ?? null;
    if ((int) ($cart_item['quantity'] ?? 0) < 1) {
        return '';
    }

    $is_product_valid = $product instanceof WC_Product && $product->exists();
    $is_unavailable = !$is_product_valid || (!$product->is_in_stock() && !$product->backorders_allowed());
    $is_variation_unavailable = $is_product_valid && $product instanceof WC_Product_Variation && !$product->is_in_stock() && !$product->backorders_allowed();
    $stock_quantity = null;
    $is_stock_insufficient = false;
    $quantity = max(1, (int) $cart_item['quantity']);
    [$min_value, $max_value] = $is_product_valid ? papetarie_storefront_cart_quantity_bounds($product) : [1, 0];
    $quantity = max($min_value, $quantity);

    if ($is_product_valid && $product->managing_stock()) {
        $raw_stock_quantity = $product->get_stock_quantity();
        if ($raw_stock_quantity !== null) {
            $stock_quantity = max(0, (int) $raw_stock_quantity);
            $is_stock_insufficient = !$is_unavailable && !$product->backorders_allowed() && $quantity > $stock_quantity;
        }
    }

    if ($is_product_valid && $max_value > 0 && !$is_stock_insufficient) {
        $quantity = min($quantity, $max_value);
    }

    $product_id = (int) ($cart_item['product_id'] ?? ($is_product_valid ? $product->get_id() : 0));
    $product_name = $is_product_valid ? $product->get_name() : __('Produs indisponibil', 'papetarie-storefront');
    $product_permalink = $is_product_valid && $product->is_visible() ? $product->get_permalink($cart_item) : '';
    $variation_html = $is_product_valid ? wc_get_formatted_cart_item_data($cart_item, true) : '';
    $description_source = $variation_html ? wp_strip_all_tags((string) $variation_html) : ($is_product_valid ? wp_strip_all_tags((string) $product->get_short_description()) : '');
    $product_description = trim(preg_replace('/\s+/', ' ', (string) $description_source));
    $thumbnail = $is_product_valid ? $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'alt' => $product_name]) : '';

    if (!$thumbnail) {
        $thumbnail = '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
    }

    if ($is_product_valid) {
        $quantity_input_max_value = ($max_value > $min_value && !$is_stock_insufficient) ? $max_value : '';
        $quantity_input = woocommerce_quantity_input(
            [
                'input_name' => "cart[{$cart_item_key}][qty]",
                'input_value' => $quantity,
                'max_value' => $quantity_input_max_value,
                'min_value' => $min_value,
                'product_name' => $product_name,
            ],
            $product,
            false
        );

        if ($is_unavailable) {
            $quantity_input = preg_replace('/<input\b/', '<input disabled="disabled" aria-disabled="true"', (string) $quantity_input, 1) ?: (string) $quantity_input;
        }
    } else {
        $quantity_input = sprintf(
            '<input type="number" class="input-text qty text" name="cart[%1$s][qty]" value="%2$d" min="%3$d" step="1" inputmode="numeric" autocomplete="off" disabled="disabled" aria-disabled="true" aria-label="%4$s">',
            esc_attr($cart_item_key),
            (int) $quantity,
            (int) $min_value,
            esc_attr__('Cantitate', 'papetarie-storefront')
        );
    }

    $minus_disabled = $is_unavailable || $quantity <= $min_value ? ' disabled' : '';
    $plus_disabled = $is_unavailable || ($is_product_valid && $max_value > 0 && $quantity >= $max_value) ? ' disabled' : '';
    $cart_item_total = '';
    $stock_limit_text = '';

    if ($is_product_valid && $max_value > 0) {
        $stock_limit_text = sprintf(
            _n('Stoc maxim disponibil: %s bucată.', 'Stoc maxim disponibil: %s bucăți.', $max_value, 'papetarie-storefront'),
            number_format_i18n($max_value)
        );
    }

    if ($is_product_valid && function_exists('WC') && WC()->cart) {
        $cart_item_total = (string) WC()->cart->get_product_subtotal($product, $quantity);
    }

    if ($cart_item_total === '' && isset($cart_item['line_total'])) {
        $cart_item_total = (string) wc_price((float) $cart_item['line_total']);
    }

    if ($cart_item_total === '' && isset($cart_item['line_subtotal'])) {
        $cart_item_total = (string) wc_price((float) $cart_item['line_subtotal']);
    }

    if ($cart_item_total === '') {
        $cart_item_total = '—';
    }

    ob_start();
    ?>
    <article
      class="pap-cart-item<?php echo $is_unavailable ? ' is-out-of-stock' : ''; ?><?php echo $is_stock_insufficient ? ' is-stock-insufficient' : ''; ?><?php echo $max_value > 0 && $quantity >= $max_value && !$is_unavailable && !$is_stock_insufficient ? ' is-stock-limit' : ''; ?>"
      data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>"
      data-cart-item-id="<?php echo esc_attr($product_id); ?>"
      data-cart-item-min="<?php echo esc_attr((string) $min_value); ?>"
      data-cart-item-max="<?php echo esc_attr($max_value > 0 ? (string) $max_value : ''); ?>"
      data-cart-item-stock-status="<?php echo esc_attr($is_unavailable ? 'outofstock' : ($is_stock_insufficient ? 'stock-insufficient' : 'instock')); ?>"
      data-cart-item-stock-limit-text="<?php echo esc_attr($stock_limit_text); ?>"
    >
      <?php if ($is_unavailable) : ?>
        <div class="pap-cart-item-stock-banner pap-cart-item-stock-banner--full" role="status" aria-live="polite">
          <span class="pap-cart-item-stock-banner__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('warning'); ?></span>
          <span>
            <?php
            if (!$is_product_valid) {
                esc_html_e('Acest produs nu mai există în catalog. Elimină-l din coș pentru a continua.', 'papetarie-storefront');
            } elseif ($is_variation_unavailable) {
                esc_html_e('Varianta selectată nu mai este disponibilă. Elimin-o din coș pentru a continua comanda.', 'papetarie-storefront');
            } else {
                esc_html_e('Produsul nu mai este disponibil în stoc. Elimină-l din coș pentru a continua comanda.', 'papetarie-storefront');
            }
            ?>
          </span>
        </div>
      <?php elseif ($is_stock_insufficient) : ?>
        <div class="pap-cart-item-stock-banner pap-cart-item-stock-banner--full" role="status" aria-live="polite">
          <span class="pap-cart-item-stock-banner__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('warning'); ?></span>
          <span>
            <span class="pap-cart-item-stock-banner__title"><?php esc_html_e('Cantitatea din coș depășește stocul disponibil.', 'papetarie-storefront'); ?></span>
            <?php if ($stock_quantity !== null) : ?>
              <span class="pap-cart-item-stock-banner__line">
                <?php
                echo esc_html(
                    sprintf(
                        _n('Mai este disponibilă doar %s bucată.', 'Mai sunt disponibile doar %s bucăți.', $stock_quantity, 'papetarie-storefront'),
                        number_format_i18n($stock_quantity)
                    )
                );
                ?>
              </span>
            <?php endif; ?>
            <span class="pap-cart-item-stock-banner__line"><?php esc_html_e('Actualizează cantitatea pentru a continua.', 'papetarie-storefront'); ?></span>
          </span>
        </div>
      <?php endif; ?>

      <div class="pap-cart-item-body">
        <div class="pap-cart-item-media">
          <?php if ($product_permalink) : ?>
            <a class="pap-cart-item-thumb" href="<?php echo esc_url($product_permalink); ?>">
              <?php echo wp_kses_post($thumbnail); ?>
            </a>
          <?php else : ?>
            <span class="pap-cart-item-thumb" aria-hidden="true">
              <?php echo wp_kses_post($thumbnail); ?>
            </span>
          <?php endif; ?>

          <div class="pap-cart-item-copy">
            <?php if (!$is_unavailable && $product instanceof WC_Product && $product->backorders_allowed()) : ?>
              <div class="pap-cart-item-backorder-badge"><?php esc_html_e('Disponibil la comandă', 'papetarie-storefront'); ?></div>
            <?php endif; ?>

            <?php if ($product_permalink) : ?>
              <a class="pap-cart-item-name" href="<?php echo esc_url($product_permalink); ?>"><?php echo esc_html($product_name); ?></a>
            <?php else : ?>
              <span class="pap-cart-item-name" aria-hidden="true"><?php echo esc_html($product_name); ?></span>
            <?php endif; ?>
            <?php if ($is_product_valid && $product->get_price_html()) : ?>
              <div class="pap-cart-item-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
            <?php endif; ?>
            <?php if ($product_description !== '') : ?>
              <div class="pap-cart-item-description"><?php echo esc_html($product_description); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="pap-cart-item-controls">
          <div class="pap-cart-qty-stack<?php echo $stock_limit_text !== '' ? ' has-stock-limit' : ''; ?>">
            <div class="pap-cart-qty-control<?php echo $max_value > 0 ? ' has-stock-limit' : ''; ?>" aria-label="<?php esc_attr_e('Cantitate', 'papetarie-storefront'); ?>">
              <button type="button" class="pap-cart-qty-control__button" data-cart-qty-step="-1"<?php echo $minus_disabled; ?> aria-label="<?php esc_attr_e('Scade cantitatea', 'papetarie-storefront'); ?>">-</button>
              <div class="pap-cart-qty-control__field">
                <?php echo apply_filters('woocommerce_cart_item_quantity', $quantity_input, $cart_item_key, $cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </div>
              <button type="button" class="pap-cart-qty-control__button" data-cart-qty-step="1"<?php echo $plus_disabled; ?> aria-label="<?php esc_attr_e('Crește cantitatea', 'papetarie-storefront'); ?>">+</button>
            </div>

            <?php if ($stock_limit_text !== '') : ?>
              <button
                type="button"
                class="pap-cart-stock-limit-trigger"
                data-cart-stock-tooltip-trigger
                data-cart-stock-tooltip-text="<?php echo esc_attr($stock_limit_text); ?>"
                aria-label="<?php echo esc_attr($stock_limit_text); ?>"
              >
                <span class="pap-cart-stock-limit-trigger__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('info'); ?></span>
              </button>
              <div class="pap-cart-stock-tooltip" data-cart-stock-tooltip hidden aria-hidden="true"></div>
            <?php endif; ?>
          </div>

          <div class="pap-cart-item-feedback" data-cart-item-feedback aria-live="polite"></div>
        </div>

        <div class="pap-cart-item-total">
          <?php echo wp_kses_post($cart_item_total); ?>
          <?php
          $remove_link = sprintf(
              '<a href="%s" class="pap-cart-remove" aria-label="%s" data-cart-remove-item data-cart-item-key="%s" data-cart-item-name="%s"><span class="pap-cart-remove__text">%s</span></a>',
              esc_url(wc_get_cart_remove_url($cart_item_key)),
              esc_attr(sprintf(__('Șterge %s din coș', 'papetarie-storefront'), $product_name)),
              esc_attr($cart_item_key),
              esc_attr($product_name),
              esc_html__('Elimină', 'papetarie-storefront')
          );
          echo apply_filters('woocommerce_cart_item_remove_link', $remove_link, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
          ?>
        </div>
      </div>
    </article>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_render_cart_items_html(): string
{
    $cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];

    ob_start();
    echo '<div class="pap-cart-items">';
    foreach ($cart as $cart_item_key => $cart_item) {
        echo papetarie_storefront_render_cart_item_row_html((string) $cart_item_key, (array) $cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    echo '</div>';

    return (string) ob_get_clean();
}

function papetarie_storefront_render_cart_summary_html(string $notice_html = ''): string
{
    $cart = function_exists('WC') && WC()->cart ? WC()->cart : null;
    $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    $coupon_codes = $cart ? $cart->get_applied_coupons() : [];
    $coupon_inline_error = papetarie_storefront_get_coupon_inline_error();
    $discount_total = $cart ? (float) $cart->get_discount_total() : 0.0;
    $has_discount = $discount_total > 0.0001;
    $needs_shipping = false;
    if ($cart) {
        $needs_shipping = $cart->needs_shipping();

        if (!$needs_shipping) {
            foreach ($cart->get_cart() as $cart_item) {
                $cart_product = $cart_item['data'] ?? null;
                if ($cart_product && is_object($cart_product) && method_exists($cart_product, 'needs_shipping') && $cart_product->needs_shipping()) {
                    $needs_shipping = true;
                    break;
                }
            }
        }
    }
    $has_calculated_shipping = $cart ? $cart->has_calculated_shipping() : false;
    $show_shipping = $cart ? $cart->show_shipping() : false;
    $shipping_value_html = '';
    $show_shipping_row = false;
    $show_tax_row = false;
    $tax_total = $cart ? (float) $cart->get_total_tax() : 0.0;
    $is_checkout_blocked = papetarie_storefront_cart_warning_state()['type'] !== 'none' || papetarie_storefront_cart_has_stock_insufficient_items();

    if ($cart && $needs_shipping) {
        $show_shipping_row = true;

        if (!$has_calculated_shipping || !$show_shipping) {
            $shipping_value_html = '<span class="pap-cart-totals-row__note">' . esc_html__('Se calculează la checkout', 'papetarie-storefront') . '</span>';
        } else {
            $shipping_total = (float) $cart->get_shipping_total();
            if ($cart->display_prices_including_tax()) {
                $shipping_total += (float) $cart->get_shipping_tax();
            }

            if ($shipping_total <= 0.0001) {
                $shipping_value_html = wp_kses_post('<span class="pap-cart-totals-row__note">' . esc_html__('Transport gratuit', 'papetarie-storefront') . '</span>');
            } else {
                $shipping_value_html = wp_kses_post(wc_price($shipping_total));
            }
        }
    }

    if ($cart && function_exists('wc_tax_enabled') && wc_tax_enabled() && !$cart->display_prices_including_tax() && $tax_total > 0.0001) {
        $show_tax_row = true;
    }

    ob_start();
    ?>
    <div class="pap-cart-summary-card" data-cart-summary-card>
      <h2 class="pap-cart-summary-title"><?php esc_html_e('Sumar comandă', 'papetarie-storefront'); ?></h2>

      <div class="pap-cart-totals">
        <div class="pap-cart-totals-row">
          <span><?php esc_html_e('Subtotal', 'papetarie-storefront'); ?></span>
          <strong data-cart-summary-subtotal><?php echo $cart ? wp_kses_post($cart->get_cart_subtotal()) : '—'; ?></strong>
        </div>
        <?php if ($has_discount) : ?>
          <div class="pap-cart-totals-row pap-cart-totals-row--discount">
            <span><?php esc_html_e('Reducere', 'papetarie-storefront'); ?></span>
            <strong data-cart-summary-discount><?php echo wp_kses_post('-' . wc_price($discount_total)); ?></strong>
          </div>
        <?php endif; ?>
        <?php if ($show_shipping_row) : ?>
          <div class="pap-cart-totals-row pap-cart-totals-row--shipping">
            <span><?php esc_html_e('Transport', 'papetarie-storefront'); ?></span>
            <strong data-cart-summary-shipping><?php echo wp_kses_post($shipping_value_html); ?></strong>
          </div>
        <?php endif; ?>
        <?php if ($show_tax_row) : ?>
          <div class="pap-cart-totals-row pap-cart-totals-row--tax">
            <span><?php esc_html_e('TVA', 'papetarie-storefront'); ?></span>
            <strong data-cart-summary-tax><?php echo wp_kses_post(wc_price($tax_total)); ?></strong>
          </div>
        <?php endif; ?>
      </div>

      <div class="pap-cart-totals-row pap-cart-totals-row--total">
        <span><?php esc_html_e('Total (TVA inclus)', 'papetarie-storefront'); ?></span>
        <strong data-cart-summary-total><?php echo $cart ? wp_kses_post($cart->get_total()) : '—'; ?></strong>
      </div>

      <?php $coupon_accordion_open = !empty($coupon_codes) || !empty($coupon_inline_error['message']); ?>
      <section class="pap-cart-coupon<?php echo $coupon_accordion_open ? ' is-open' : ''; ?>" data-cart-coupon-accordion>
        <h3 class="pap-cart-coupon-header">
          <button
            type="button"
            class="pap-cart-coupon-toggle"
            data-cart-coupon-toggle
            aria-expanded="<?php echo $coupon_accordion_open ? 'true' : 'false'; ?>"
            aria-controls="pap-cart-coupon-panel"
          >
            <span class="pap-cart-coupon-toggle__text"><?php esc_html_e('Ai un cod promoțional?', 'papetarie-storefront'); ?></span>
            <span class="pap-cart-coupon-toggle__icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
          </button>
        </h3>

        <div
          id="pap-cart-coupon-panel"
          class="pap-cart-coupon-panel"
          data-cart-coupon-panel
          <?php echo $coupon_accordion_open ? '' : 'hidden'; ?>
        >
          <form class="pap-cart-coupon-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post" data-cart-coupon-form>
            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
            <div class="pap-cart-coupon-row">
              <div class="pap-cart-coupon-input-wrap">
                <input
                  type="text"
                  name="coupon_code"
                  class="pap-cart-coupon-input"
                  data-cart-coupon-input
                  value=""
                  placeholder="<?php esc_attr_e('Cod promoțional', 'papetarie-storefront'); ?>"
                  aria-label="<?php esc_attr_e('Cod promoțional', 'papetarie-storefront'); ?>"
                  <?php echo !empty($coupon_inline_error['message']) ? 'aria-invalid="true"' : ''; ?>
                >
              </div>
              <button type="submit" class="pap-cart-coupon-button" name="apply_coupon" value="1"><?php esc_html_e('Aplică', 'papetarie-storefront'); ?></button>
            </div>
            <?php if (!empty($coupon_inline_error['message'])) : ?>
              <div class="pap-cart-coupon-error is-visible" data-cart-coupon-error role="alert" aria-hidden="false">
                <span class="pap-cart-coupon-error__text"><?php echo esc_html($coupon_inline_error['message']); ?></span>
              </div>
            <?php endif; ?>
          </form>

          <?php if (!empty($coupon_codes)) : ?>
            <div class="pap-cart-coupon-list" data-cart-coupon-list>
              <?php foreach ($coupon_codes as $coupon_code) : ?>
                <div class="pap-cart-coupon-chip">
                  <span class="pap-cart-coupon-chip__code"><?php echo esc_html(wc_format_coupon_code((string) $coupon_code)); ?></span>
                  <a
                    class="pap-cart-coupon-chip__remove"
                    data-cart-remove-coupon
                    data-cart-coupon-code="<?php echo esc_attr((string) $coupon_code); ?>"
                    href="<?php echo esc_url(add_query_arg('remove_coupon', rawurlencode((string) $coupon_code), wc_get_cart_url())); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Elimină cuponul %s', 'papetarie-storefront'), wc_format_coupon_code((string) $coupon_code))); ?>"
                  >
                    ×
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <a
        class="pap-cart-checkout<?php echo $is_checkout_blocked ? ' is-disabled' : ''; ?>"
        href="<?php echo esc_url($checkout_url); ?>"
        data-cart-checkout
        aria-disabled="<?php echo esc_attr($is_checkout_blocked ? 'true' : 'false'); ?>"
        tabindex="<?php echo esc_attr($is_checkout_blocked ? '-1' : '0'); ?>"
      >
        <span class="pap-cart-checkout-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('lock-outline'); ?></span>
        <span><?php esc_html_e('Continuă către finalizare', 'papetarie-storefront'); ?></span>
      </a>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_get_cart_page_payload(): array
{
    $count = (int) papetarie_storefront_cart_count();
    $count_label = papetarie_storefront_cart_count_label();
    $cart = function_exists('WC') && WC()->cart ? WC()->cart : null;

    return [
        'count' => $count,
        'count_label' => $count_label,
        'form_action' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
        'nonce' => wp_create_nonce('woocommerce-cart'),
        'items_html' => papetarie_storefront_render_cart_items_html(),
        'summary_html' => papetarie_storefront_render_cart_summary_html(),
        'total_html' => $cart ? wp_kses_post($cart->get_total()) : '',
        'has_items' => $cart ? !empty($cart->get_cart()) : false,
        'is_empty' => $cart ? empty($cart->get_cart()) : true,
        'page_html' => papetarie_storefront_render_cart_page_html(),
    ];
}

function papetarie_storefront_render_cart_empty_html(): string
{
    if (!function_exists('wc_get_template')) {
        return '';
    }

    ob_start();
    wc_get_template('cart/cart-empty.php');
    return (string) ob_get_clean();
}

function papetarie_storefront_render_cart_page_html(): string
{
    if (!function_exists('wc_get_template')) {
        return '';
    }

    ob_start();

    if (function_exists('WC') && WC() && WC()->cart && WC()->cart->is_empty()) {
        wc_get_template('cart/cart-empty.php');
    } else {
        wc_get_template('cart/cart.php');
    }

    return (string) ob_get_clean();
}

function papetarie_storefront_get_cart_page_fragments(): array
{
    $fragments = apply_filters('woocommerce_add_to_cart_fragments', []);
    $cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];

    ob_start();
    echo wp_kses_post(papetarie_storefront_render_cart_summary_html());
    $fragments['[data-cart-summary-card]'] = ob_get_clean();

    foreach ($cart as $cart_item_key => $cart_item) {
        $row_html = papetarie_storefront_render_cart_item_row_html((string) $cart_item_key, (array) $cart_item);
        if ($row_html === '') {
            continue;
        }

        $fragments['[data-cart-item-key="' . esc_attr((string) $cart_item_key) . '"]'] = $row_html;
    }

    return $fragments;
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
        <div class="pap-cart-drawer-main">
          <div class="pap-cart-drawer-row">
            <a class="pap-cart-drawer-name" href="<?php echo esc_url($product_permalink ? $product_permalink : '#'); ?>" <?php echo $product_permalink ? '' : 'aria-hidden="true" tabindex="-1"'; ?>><?php echo esc_html($product_name); ?></a>
            <span class="pap-cart-drawer-quantity">×<?php echo esc_html((string) $quantity); ?></span>
          </div>

          <span class="pap-cart-drawer-unit-price"><?php echo wp_kses_post($product->get_price_html()); ?> / buc.</span>

          <?php if ($variation_html) : ?>
            <div class="pap-cart-drawer-variation"><?php echo wp_kses_post($variation_html); ?></div>
          <?php endif; ?>
        </div>

        <div class="pap-cart-drawer-side">
          <span class="pap-cart-drawer-line-total"><?php echo function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_product_subtotal($product, $quantity)) : wp_kses_post($product->get_price_html()); ?></span>
          <button
            type="button"
            class="pap-cart-drawer-remove"
            data-cart-remove-item
            data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>"
            data-cart-item-name="<?php echo esc_attr($product_name); ?>"
            aria-label="<?php esc_attr_e('Elimină produsul din coș', 'papetarie-storefront'); ?>"
          >
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
          </button>
        </div>
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
              <strong data-cart-drawer-total><?php echo function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '—'; ?></strong>
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
            <h3 id="pap-cart-modal-title"><?php esc_html_e('Adăugat în coș', 'papetarie-storefront'); ?></h3>
          </div>
          <button class="pap-cart-modal-dismiss" type="button" aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>" data-cart-modal-close>×</button>
        </header>
        <div class="pap-cart-modal-body">
          <p class="pap-cart-modal-note"><?php esc_html_e('Poți continua cumpărăturile sau poți merge la coș.', 'papetarie-storefront'); ?></p>
          <div class="pap-cart-modal-product">
            <div class="pap-cart-modal-thumb" data-cart-modal-thumb hidden>
              <img src="" alt="" data-cart-modal-image>
            </div>
            <div class="pap-cart-modal-copy">
              <strong data-cart-modal-name></strong>
              <span class="pap-cart-modal-quantity" data-cart-modal-quantity hidden></span>
              <span class="pap-cart-modal-price" data-cart-modal-price></span>
            </div>
          </div>
        </div>

        <footer class="pap-cart-modal-actions">
          <button type="button" class="button pap-cart-delete-modal-button pap-cart-delete-modal-button--secondary" data-cart-modal-close><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?></button>
          <a class="button pap-cart-delete-modal-button pap-cart-delete-modal-button--primary" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? $cart_url : $shop_url); ?>" data-cart-modal-link><?php esc_html_e('Vezi coșul', 'papetarie-storefront'); ?></a>
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
    $subtotal = function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_cart_subtotal()) : '';
    $total = function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '';
    $cart = function_exists('WC') && WC()->cart ? WC()->cart->get_cart() : [];

    ob_start();
    papetarie_storefront_render_cart_drawer_items();
    $items_html = (string) ob_get_clean();

    return [
        'count' => $count,
        'count_label' => $count_label,
        'subtotal_html' => $subtotal,
        'total_html' => $total,
        'items_html' => $items_html,
        'has_items' => !empty($cart),
        'is_empty' => empty($cart),
        'cart_page' => papetarie_storefront_get_cart_page_payload(),
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

function papetarie_storefront_coupon_notice_to_inline_message(string $notice): string
{
    $notice = trim(wp_strip_all_tags($notice));

    if ($notice === '') {
        return '';
    }

    if (stripos($notice, 'Invalid coupon code') !== false) {
        return __('Codul promoțional introdus nu este valid.', 'papetarie-storefront');
    }

    if (stripos($notice, 'already applied and cannot be used in conjunction with other coupons') !== false) {
        return __('Acest cod promoțional este deja aplicat.', 'papetarie-storefront');
    }

    if (stripos($notice, 'Coupon code') !== false && stripos($notice, 'already applied') !== false) {
        return __('Acest cod promoțional este deja aplicat.', 'papetarie-storefront');
    }

    if (stripos($notice, 'already been applied') !== false) {
        return __('Acest cod promoțional este deja aplicat.', 'papetarie-storefront');
    }

    if (stripos($notice, 'already applied!') !== false) {
        return __('Acest cod promoțional este deja aplicat.', 'papetarie-storefront');
    }

    if (stripos($notice, 'has expired') !== false) {
        return __('Codul promoțional a expirat.', 'papetarie-storefront');
    }

    if (stripos($notice, 'minimum spend for coupon') !== false) {
        if (preg_match('/\bis\s+([0-9]+(?:[.,][0-9]+)?)/i', $notice, $matches)) {
            $minimum_amount = (float) str_replace(',', '.', $matches[1]);
            $minimum_amount = rtrim(rtrim(number_format($minimum_amount, 2, '.', ''), '0'), '.');
            return sprintf(
                /* translators: %s: minimum order amount */
                __('Acest cod poate fi utilizat doar pentru comenzi de minimum %s lei.', 'papetarie-storefront'),
                $minimum_amount
            );
        }

        return __('Acest cod poate fi utilizat doar pentru comenzi de minimum 100 lei.', 'papetarie-storefront');
    }

    if (stripos($notice, 'Usage limit for coupon') !== false) {
        return __('Acest cod promoțional nu mai poate fi folosit.', 'papetarie-storefront');
    }

    if (stripos($notice, 'not applicable to your cart contents') !== false) {
        return __('Acest cod promoțional nu se aplică pentru produsele din coș.', 'papetarie-storefront');
    }

    if (stripos($notice, 'not valid for sale items') !== false) {
        return __('Acest cod promoțional nu se aplică pentru produsele reduse.', 'papetarie-storefront');
    }

    return __('Codul promoțional introdus nu este valid.', 'papetarie-storefront');
}

function papetarie_storefront_clear_coupon_error_notices_from_session(): void
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $all_notices = WC()->session->get('wc_notices', []);
    if (!is_array($all_notices) || empty($all_notices['error']) || !is_array($all_notices['error'])) {
        return;
    }

    $filtered_error_notices = [];

    foreach ($all_notices['error'] as $notice) {
        if (!is_array($notice) || empty($notice['notice'])) {
            $filtered_error_notices[] = $notice;
            continue;
        }

        $message = papetarie_storefront_coupon_notice_to_inline_message((string) $notice['notice']);
        if ($message !== '') {
            continue;
        }

        $filtered_error_notices[] = $notice;
    }

    if (empty($filtered_error_notices)) {
        unset($all_notices['error']);
    } else {
        $all_notices['error'] = array_values($filtered_error_notices);
    }

    WC()->session->set('wc_notices', empty($all_notices) ? null : $all_notices);
}

function papetarie_storefront_get_coupon_inline_error(): array
{
    $inline_error = [
        'message' => '',
    ];

    if (!function_exists('WC') || !WC()) {
        return $inline_error;
    }

    $notices = function_exists('wc_get_notices') ? wc_get_notices('error') : [];
    if (!is_array($notices) || empty($notices)) {
        return $inline_error;
    }

    foreach ($notices as $notice) {
        if (!is_array($notice) || empty($notice['notice'])) {
            continue;
        }

        $message = papetarie_storefront_coupon_notice_to_inline_message((string) $notice['notice']);
        if ($message === '') {
            continue;
        }

        $inline_error['message'] = $message;
        papetarie_storefront_clear_coupon_error_notices_from_session();
        return $inline_error;
    }

    return $inline_error;
}

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
        'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.82 10.55 19.5C5.46 14.86 2 11.81 2 8.1 2 5.08 4.34 2.75 7.37 2.75c1.93 0 3.71.96 4.63 2.46.92-1.5 2.7-2.46 4.63-2.46 3.03 0 5.37 2.33 5.37 5.35 0 3.71-3.46 6.76-8.55 11.4L12 20.82Z" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l7 3v6c0 4.97-3.06 9.63-7 11-3.94-1.37-7-6.03-7-11V5l7-3zm-1 13l5-5-1.41-1.41L11 12.17l-1.59-1.58L8 12l3 3z" fill="currentColor"/></svg>',
        'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.59 13.41L11 3.83V3H4v7h.83l9.58 9.59a2 2 0 002.83 0l3.35-3.35a2 2 0 000-2.83zM6.5 8A1.5 1.5 0 118 6.5 1.5 1.5 0 016.5 8z" fill="currentColor"/></svg>',
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h11v9h2.17a3 3 0 015.66 1H23v2h-1a3 3 0 11-6 0H9a3 3 0 11-6 0H2v-2h1V5zm13 2v5h3.59L18.09 9H16z" fill="currentColor"/></svg>',
        'truck-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 7.5h9.5V15H3.5V7.5Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M13 10h2.2l2.8 2.8V15H13V10Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M17.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 17.2h1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M15.2 12.2h2.1L20.1 15v1.1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3.5h6a1 1 0 0 1 1 1V6h3v2H5V6h3V4.5a1 1 0 0 1 1-1ZM7.5 8h9l-.55 10.1A2 2 0 0 1 13.96 20h-3.92a2 2 0 0 1-1.99-1.9L7.5 8Zm3 2.1v6.7h1.2v-6.7h-1.2Zm2.8 0v6.7h1.2v-6.7h-1.2Z" fill="currentColor"/></svg>',
        'trash-2' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 6V4h8v2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 6l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'lock-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 11V8.8a4.5 4.5 0 1 1 9 0V11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><rect x="5.5" y="11" width="13" height="10" rx="1.8" ry="1.8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M12 15.2v2.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'headset-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12a6 6 0 0 1 12 0v5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M4.5 13.2v3a2 2 0 0 0 2 2H8v-7H6.5a2 2 0 0 0-2 2Zm15 0v3a2 2 0 0 1-2 2H16v-7h1.5a2 2 0 0 1 2 2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 19.5h4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'pen' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17.25 10.58-10.59 3.76 3.76L6.75 21H3v-3.75Zm12-9.66 1.41-1.42a2 2 0 0 1 2.83 0l.17.17a2 2 0 0 1 0 2.83L18 10.59 15 7.59Z" fill="currentColor"/></svg>',
        'paper' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'file-lines-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
        'heart-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.82 10.55 19.5C5.46 14.86 2 11.81 2 8.1 2 5.08 4.34 2.75 7.37 2.75c1.93 0 3.71.96 4.63 2.46.92-1.5 2.7-2.46 4.63-2.46 3.03 0 5.37 2.33 5.37 5.35 0 3.71-3.46 6.76-8.55 11.4L12 20.82Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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

function papetarie_storefront_auth_input_icon(string $name): string
{
    $icons = [
        'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.69-7.5 6v1h15v-1c0-3.31-3.36-6-7.5-6z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4V6Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 8l6 4.5L18 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.8 4.1c.6-.6 1.5-.8 2.3-.5l2.2.8c.7.3 1.2.9 1.2 1.7v2.1c0 .7-.4 1.4-1 1.7l-1.4.7a13 13 0 0 0 3.9 3.9l.7-1.4c.3-.6 1-1 1.7-1h2.1c.8 0 1.4.5 1.7 1.2l.8 2.2c.3.8.1 1.7-.5 2.3l-1.1 1.1c-.9.9-2.3 1.2-3.5.7-3.2-1.3-6.3-3.5-8.9-6.1-2.6-2.6-4.8-5.7-6.1-8.9-.5-1.2-.2-2.6.7-3.5l1.1-1.1Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 11V8.8a4.5 4.5 0 1 1 9 0V11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><rect x="5.5" y="11" width="13" height="10" rx="1.8" ry="1.8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M12 15.2v2.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'location' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s5-4.6 5-9a5 5 0 1 0-10 0c0 4.4 5 9 5 9z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
        'location-pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s5-4.6 5-9a5 5 0 1 0-10 0c0 4.4 5 9 5 9z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
        'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 10.5V21h13V10.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 21v-6h5v6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'building' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12M9 20v-5h6v5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function papetarie_storefront_checkout_field_icon(string $key, array $field): string
{
    $field_type = isset($field['type']) ? (string) $field['type'] : 'text';

    if ($field_type === 'email' || str_contains($key, 'email')) {
        return 'mail';
    }

    if (str_contains($key, 'phone')) {
        return 'phone';
    }

    if (str_contains($key, 'city')) {
        return 'location-pin';
    }

    if (str_contains($key, 'state') || str_contains($key, 'county')) {
        return 'location-pin';
    }

    if (str_contains($key, 'address_1')) {
        return 'home';
    }

    if ($key === 'order_comments') {
        return '';
    }

    return 'user';
}

function papetarie_storefront_render_checkout_form_field(string $key, array $field, $value = '', bool $with_icon = true): string
{
    $type = isset($field['type']) ? (string) $field['type'] : 'text';
    $type = $type !== '' ? strtolower($type) : 'text';
    $field_id = isset($field['id']) && is_string($field['id']) && $field['id'] !== '' ? $field['id'] : $key;
    $field_label = isset($field['label']) ? (string) $field['label'] : '';
    $field_placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';
    $required = !empty($field['required']);
    $classes = array_values(array_filter(array_unique(array_merge(
        ['woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row', 'form-row-wide', 'pap-form-row'],
        array_map('strval', (array) ($field['class'] ?? []))
    ))));
    if ($required) {
        $classes[] = 'validate-required';
    }

    $field_attributes = (array) ($field['custom_attributes'] ?? []);
    $field_attributes['id'] = $field_id;
    $field_attributes['name'] = $key;
    if ($required) {
        $field_attributes['required'] = 'required';
        $field_attributes['aria-required'] = 'true';
    }

    foreach (['autocomplete', 'maxlength', 'minlength', 'pattern', 'inputmode', 'autocapitalize', 'autocorrect', 'spellcheck', 'aria-label'] as $attribute_name) {
        if (!isset($field[$attribute_name]) || $field[$attribute_name] === '' || $field[$attribute_name] === null) {
            continue;
        }

        $field_attributes[$attribute_name] = $field[$attribute_name];
    }

    $input_classes = array_values(array_filter(array_unique(array_merge(
        ['input-text', 'woocommerce-Input', 'woocommerce-Input--text'],
        array_map('strval', (array) ($field['input_class'] ?? []))
    ))));

    if ('hidden' === $type) {
        return sprintf(
            '<input type="hidden" name="%1$s" id="%2$s" value="%3$s" />',
            esc_attr($key),
            esc_attr($field_id),
            esc_attr(is_scalar($value) ? (string) $value : '')
        );
    }

    $label_html = '';
    if ($field_label !== '') {
        $label_html = sprintf(
            '<label for="%1$s">%2$s%3$s</label>',
            esc_attr($field_id),
            esc_html($field_label),
            $required ? '<span class="required" aria-hidden="true">*</span>' : ''
        );
    }

    $input_html = '';
    $field_value = is_scalar($value) ? (string) $value : '';

    if ('select' === $type) {
        $options = (array) ($field['options'] ?? []);
        $attributes = [];
        foreach ($field_attributes as $attribute_name => $attribute_value) {
            if ($attribute_value === null || $attribute_value === false || $attribute_value === '') {
                continue;
            }
            $attributes[] = sprintf('%1$s="%2$s"', esc_attr((string) $attribute_name), esc_attr((string) $attribute_value));
        }

        $input_html = sprintf(
            '<select class="%1$s" %2$s>',
            esc_attr(implode(' ', $input_classes)),
            implode(' ', $attributes)
        );

        foreach ($options as $option_value => $option_label) {
            $option_value = is_scalar($option_value) ? (string) $option_value : '';
            $option_label = is_scalar($option_label) ? (string) $option_label : '';
            $selected = selected($field_value, $option_value, false);
            $input_html .= sprintf(
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($option_value),
                $selected,
                esc_html($option_label)
            );
        }

        $input_html .= '</select>';
    } elseif ('textarea' === $type) {
        $attributes = [];
        foreach ($field_attributes as $attribute_name => $attribute_value) {
            if ($attribute_value === null || $attribute_value === false || $attribute_value === '') {
                continue;
            }

            $attributes[] = sprintf('%1$s="%2$s"', esc_attr((string) $attribute_name), esc_attr((string) $attribute_value));
        }

        $input_html = sprintf(
            '<textarea class="%1$s" %2$s>%3$s</textarea>',
            esc_attr(implode(' ', $input_classes)),
            implode(' ', $attributes),
            esc_textarea($field_value)
        );
    } else {
        $attributes = [
            sprintf('type="%s"', esc_attr($type)),
            sprintf('class="%s"', esc_attr(implode(' ', $input_classes))),
            sprintf('name="%s"', esc_attr($key)),
            sprintf('id="%s"', esc_attr($field_id)),
            sprintf('value="%s"', esc_attr($field_value)),
        ];

        if ($field_placeholder !== '') {
            $attributes[] = sprintf('placeholder="%s"', esc_attr($field_placeholder));
        }

        foreach ($field_attributes as $attribute_name => $attribute_value) {
            if ($attribute_value === null || $attribute_value === false || $attribute_value === '') {
                continue;
            }

            if (in_array((string) $attribute_name, ['id', 'name'], true)) {
                continue;
            }

            $attributes[] = sprintf('%1$s="%2$s"', esc_attr((string) $attribute_name), esc_attr((string) $attribute_value));
        }

        $input_html = sprintf('<input %s />', implode(' ', $attributes));
    }

    if (!$with_icon) {
        return sprintf(
            '<fieldset class="%1$s">%2$s%3$s</fieldset>',
            esc_attr(implode(' ', $classes)),
            $label_html,
            $input_html
        );
    }

    $icon_name = papetarie_storefront_checkout_field_icon($key, $field);
    $icon_html = papetarie_storefront_auth_input_icon($icon_name);
    $wrapper_classes = [
        'pap-auth-input-field',
        'pap-auth-input-field--' . sanitize_html_class($icon_name ?: $type),
    ];

    if ('select' === $type) {
        $wrapper_classes[] = 'pap-auth-input-field--select';
        $input_classes[] = 'woocommerce-Input--select';
    }

    return sprintf(
        '<fieldset class="%1$s">%2$s<span class="%3$s">%4$s%5$s</span></fieldset>',
        esc_attr(implode(' ', $classes)),
        $label_html,
        esc_attr(implode(' ', $wrapper_classes)),
        $icon_html !== '' ? '<span class="pap-auth-input-icon" aria-hidden="true">' . $icon_html . '</span>' : '',
        $input_html
    );
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
        'info' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5a9.5 9.5 0 1 1 0 19 9.5 9.5 0 0 1 0-19Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 10.2v7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 6.6h.01" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',
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

    echo '<div class="pap-auth-notices" role="status" aria-live="polite">';

    if (empty($session_notices) && '' === trim($fallback_notice)) {
        echo '</div>';
        return;
    }

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
        }

        if ('' !== trim($fallback_notice) && empty($session_notices)) {
            echo '<div class="pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" role="alert">';
            echo '<span class="pap-auth-notice-icon wc-block-components-notice-banner__icon" aria-hidden="true">' . papetarie_storefront_notice_icon('error') . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' . esc_html($fallback_notice) . '</div>';
            echo '</div>';
        }
    }

    echo '</div>';

    if (function_exists('WC') && WC() && WC()->session) {
        WC()->session->set('pap_auth_notices', []);
        WC()->session->set('pap_auth_last_error', '');
    }
}

function papetarie_storefront_capture_login_errors($errors, $username, $password)
{
    if (!is_wp_error($errors)) {
        return $errors;
    }

    $messages = $errors->get_error_messages();
    foreach ($messages as $message) {
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

function papetarie_storefront_render_social_login_area(array $args = []): void
{
    $args = wp_parse_args(
        is_array($args) ? $args : [],
        [
            'show_register_switch' => false,
        ]
    );

    $show_register_switch = !empty($args['show_register_switch']);
    $google_url = (string) apply_filters('papetarie_storefront_google_login_url', '');
    $social_shortcode = shortcode_exists('nextend_social_login') ? 'nextend_social_login' : '';
    $button_disabled = ($google_url === '' && $social_shortcode === '');
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
          <a class="pap-auth-inline-switch pap-auth-social-switch" href="#" data-auth-switch="register"><?php esc_html_e('Creează unul nou', 'papetarie-storefront'); ?></a>
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

function papetarie_storefront_render_auth_login_shell(array $args = []): void
{
    if (!function_exists('get_template_part')) {
        return;
    }

    get_template_part('template-parts/auth/login-shell', null, $args);
}

function papetarie_storefront_render_auth_lost_password_shell(array $args = []): void
{
    if (!function_exists('get_template_part')) {
        return;
    }

    get_template_part('template-parts/auth/lost-password-shell', null, $args);
}

function papetarie_storefront_render_auth_lost_password_confirmation_shell(array $args = []): void
{
    if (!function_exists('get_template_part')) {
        return;
    }

    get_template_part('template-parts/auth/lost-password-confirmation-shell', null, $args);
}

function papetarie_storefront_auth_confirmation_meta_key(): string
{
    return 'pap_email_confirmed';
}

function papetarie_storefront_auth_activation_token_key(): string
{
    return 'pap_email_confirmation_token';
}

function papetarie_storefront_auth_activation_sent_key(): string
{
    return 'pap_email_confirmation_sent_at';
}

function papetarie_storefront_auth_is_confirmed(int $user_id): bool
{
    if ($user_id <= 0) {
        return true;
    }

    $confirmed = get_user_meta($user_id, papetarie_storefront_auth_confirmation_meta_key(), true);

    if ('' === $confirmed) {
        return true;
    }

    return in_array((string) $confirmed, ['1', 1, true, 'yes'], true);
}

function papetarie_storefront_auth_generate_activation_token(int $user_id): string
{
    $token = wp_generate_password(32, false, false);

    update_user_meta($user_id, papetarie_storefront_auth_activation_token_key(), wp_hash_password($token));
    update_user_meta($user_id, papetarie_storefront_auth_activation_sent_key(), time());

    return $token;
}

function papetarie_storefront_auth_get_activation_url(int $user_id, string $token): string
{
    $myaccount_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');

    return add_query_arg(
        [
            'pap_confirm_email' => 1,
            'uid' => $user_id,
            'token' => rawurlencode($token),
        ],
        $myaccount_url
    );
}

function papetarie_storefront_auth_send_activation_email(int $user_id, string $token): bool
{
    $user = get_user_by('id', $user_id);
    if (!$user instanceof WP_User) {
        return false;
    }

    $activation_url = papetarie_storefront_auth_get_activation_url($user_id, $token);
    $subject = __('Confirmă-ți contul SupplyHub', 'papetarie-storefront');
    $message = sprintf(
        '<p>%1$s</p><p><a href="%2$s">%3$s</a></p><p>%4$s</p>',
        esc_html__('Mulțumim pentru înregistrare. Pentru a activa contul, confirmă adresa de email accesând butonul de mai jos.', 'papetarie-storefront'),
        esc_url($activation_url),
        esc_html__('Confirmă contul', 'papetarie-storefront'),
        esc_html__('Dacă nu ai cerut acest cont, poți ignora acest mesaj.', 'papetarie-storefront')
    );

    return (bool) wp_mail(
        $user->user_email,
        $subject,
        $message,
        ['Content-Type: text/html; charset=UTF-8']
    );
}

function papetarie_storefront_handle_auth_activation_request(): void
{
    if (is_user_logged_in()) {
        return;
    }

    $should_process = !empty($_GET['pap_confirm_email']) && !empty($_GET['uid']) && !empty($_GET['token']);
    if (!$should_process) {
        return;
    }

    $user_id = absint($_GET['uid']);
    $token = sanitize_text_field(wp_unslash((string) $_GET['token']));

    if ($user_id <= 0 || '' === $token) {
        papetarie_storefront_store_auth_notice(__('Linkul de confirmare este invalid.', 'papetarie-storefront'), 'error');
        return;
    }

    $user = get_user_by('id', $user_id);
    if (!$user instanceof WP_User) {
        papetarie_storefront_store_auth_notice(__('Contul nu a putut fi confirmat.', 'papetarie-storefront'), 'error');
        return;
    }

    if (papetarie_storefront_auth_is_confirmed($user_id)) {
        papetarie_storefront_store_auth_notice(__('Contul este deja confirmat. Te poți autentifica.', 'papetarie-storefront'), 'success');
        $redirect_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
        wp_safe_redirect($redirect_url);
        exit;
    }

    $stored_hash = (string) get_user_meta($user_id, papetarie_storefront_auth_activation_token_key(), true);
    $sent_at = absint(get_user_meta($user_id, papetarie_storefront_auth_activation_sent_key(), true));
    $expired = $sent_at > 0 && (time() - $sent_at) > DAY_IN_SECONDS * 2;

    if ($expired) {
        papetarie_storefront_store_auth_notice(__('Linkul de confirmare a expirat. Solicită unul nou.', 'papetarie-storefront'), 'error');
        return;
    }

    if ('' === $stored_hash || !wp_check_password($token, $stored_hash, $user_id)) {
        papetarie_storefront_store_auth_notice(__('Linkul de confirmare este invalid sau a expirat.', 'papetarie-storefront'), 'error');
        return;
    }

    update_user_meta($user_id, papetarie_storefront_auth_confirmation_meta_key(), 1);
    delete_user_meta($user_id, papetarie_storefront_auth_activation_token_key());
    delete_user_meta($user_id, papetarie_storefront_auth_activation_sent_key());
    papetarie_storefront_store_auth_notice(__('Contul a fost confirmat. Te poți autentifica acum.', 'papetarie-storefront'), 'success');

    $redirect_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_auth_activation_request', 5);

function papetarie_storefront_block_unconfirmed_authentication($user, $username = '', $password = '')
{
    if (is_wp_error($user) || !$user instanceof WP_User) {
        return $user;
    }

    if (!papetarie_storefront_auth_is_confirmed((int) $user->ID)) {
        return new WP_Error(
            'pap_email_not_confirmed',
            __('Cont neconfirmat. Verifică emailul.', 'papetarie-storefront')
        );
    }

    return $user;
}
add_filter('authenticate', 'papetarie_storefront_block_unconfirmed_authentication', 30, 3);

function papetarie_storefront_get_current_user_first_name(): string
{
    $user = wp_get_current_user();
    if (!$user instanceof WP_User || !$user->exists()) {
        return '';
    }

    $first_name = trim((string) $user->first_name);
    if ($first_name !== '') {
        return $first_name;
    }

    $display_name = trim((string) $user->display_name);
    if ($display_name === '') {
      return '';
    }

    $parts = preg_split('/\s+/', $display_name);
    return $parts && !empty($parts[0]) ? (string) $parts[0] : $display_name;
}

function papetarie_storefront_get_current_user_initials(): string
{
    $user = wp_get_current_user();
    if (!$user instanceof WP_User || !$user->exists()) {
        return 'C';
    }

    $first_name = trim((string) $user->first_name);
    $last_name = trim((string) $user->last_name);
    $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

    $initials = '';
    if ($first_name !== '') {
        $initials .= $substr($first_name, 0, 1);
    }

    if ($last_name !== '') {
        $initials .= $substr($last_name, 0, 1);
    }

    if ($initials === '') {
        $display_name = trim((string) $user->display_name);
        if ($display_name !== '') {
            $parts = preg_split('/\s+/', $display_name);
            if ($parts && !empty($parts[0])) {
                $initials .= $substr((string) $parts[0], 0, 1);
            }
        }
    }

    $initials = strtoupper($initials);
    return $initials !== '' ? $initials : 'C';
}

function papetarie_storefront_get_current_user_auth_state(): array
{
    $user = wp_get_current_user();
    if (!$user instanceof WP_User || !$user->exists()) {
        return [
            'is_logged_in' => false,
        ];
    }

    return [
        'is_logged_in' => true,
        'user_id' => (int) $user->ID,
        'display_name' => (string) $user->display_name,
        'first_name' => (string) $user->first_name,
        'last_name' => (string) $user->last_name,
        'initials' => papetarie_storefront_get_current_user_initials(),
    ];
}

function papetarie_storefront_render_account_tool_html(): string
{
    $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
    $is_logged_in = is_user_logged_in();
    $initials = papetarie_storefront_get_current_user_initials();

    ob_start();
    if ($is_logged_in) :
        ?>
        <a class="pap-tool-card pap-tool-card-account" href="<?php echo esc_url($account_url); ?>" data-pap-auth-account>
          <span class="pap-tool-avatar" aria-hidden="true"><?php echo esc_html($initials); ?></span>
          <span class="pap-tool-copy">
            <strong><?php esc_html_e('Bun venit', 'papetarie-storefront'); ?></strong>
            <span><?php esc_html_e('Contul meu', 'papetarie-storefront'); ?></span>
          </span>
        </a>
        <?php
    else :
        ?>
        <button
          class="pap-tool-card pap-tool-card-account"
          type="button"
          data-pap-auth-account
          data-auth-modal-open
          onclick="return window.papOpenAuthModal ? window.papOpenAuthModal(this) : false;"
          aria-haspopup="dialog"
          aria-controls="pap-auth-modal"
        >
          <span class="pap-tool-icon-badge" aria-hidden="true">
            <i class="pap-tool-icon"><?php echo papetarie_storefront_icon('account'); ?></i>
          </span>
          <span class="pap-tool-copy">
            <strong><?php esc_html_e('Cont', 'papetarie-storefront'); ?></strong>
            <span><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></span>
          </span>
        </button>
        <?php
    endif;

    return (string) ob_get_clean();
}

function papetarie_storefront_get_current_user_account_payload(): array
{
    return [
        'account_html' => papetarie_storefront_render_account_tool_html(),
        'auth_state' => papetarie_storefront_get_current_user_auth_state(),
        'cart_drawer' => papetarie_storefront_get_cart_drawer_payload(),
        'cart_page' => papetarie_storefront_get_cart_page_payload(),
    ];
}

function papetarie_storefront_render_auth_modal(): void
{
    if (is_user_logged_in()) {
        return;
    }
    ?>
    <div class="pap-auth-modal" id="pap-auth-modal" data-auth-modal hidden aria-hidden="true">
      <div class="pap-auth-modal__backdrop" data-auth-modal-close aria-hidden="true"></div>
      <div class="pap-auth-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Autentificare', 'papetarie-storefront'); ?>">
        <div class="pap-auth-modal__content">
          <?php
          papetarie_storefront_render_auth_login_shell([
              'context' => 'modal',
              'show_visual' => false,
              'show_register' => true,
              'id_prefix' => 'pap-auth-modal-',
          ]);
          papetarie_storefront_render_auth_lost_password_shell([
              'context' => 'modal',
              'show_visual' => false,
              'id_prefix' => 'pap-auth-modal-lost-',
              'hidden' => true,
          ]);
          papetarie_storefront_render_auth_lost_password_confirmation_shell([
              'context' => 'modal',
              'show_visual' => false,
              'hidden' => true,
          ]);
          ?>
        </div>
      </div>
    </div>
    <?php
}
add_action('wp_footer', 'papetarie_storefront_render_auth_modal', 8);

function papetarie_storefront_render_auth_notice_html_from_session(): string
{
    ob_start();
    papetarie_storefront_render_auth_notices();
    return (string) ob_get_clean();
}

function papetarie_storefront_send_auth_error_response(string $message, int $status_code = 400): void
{
    papetarie_storefront_store_auth_notice($message, 'error');

    wp_send_json_error([
        'message' => $message,
        'notice_html' => papetarie_storefront_render_auth_notice_html_from_session(),
    ], $status_code);
}

function papetarie_storefront_handle_auth_lost_password_ajax(): void
{
    check_ajax_referer('pap_auth_lost_password', 'nonce');

    $user_login = isset($_POST['user_login']) ? sanitize_text_field(wp_unslash((string) $_POST['user_login'])) : '';

    if ('' === $user_login) {
        papetarie_storefront_send_auth_error_response(__('Introdu emailul.', 'papetarie-storefront'));
    }

    if (!is_email($user_login)) {
        papetarie_storefront_send_auth_error_response(__('Introdu un email valid.', 'papetarie-storefront'));
    }

    if (!function_exists('retrieve_password')) {
        papetarie_storefront_send_auth_error_response(__('Nu am putut procesa cererea. Încearcă din nou.', 'papetarie-storefront'), 500);
    }

    $result = retrieve_password($user_login);

    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        if ('' === trim($message)) {
            $message = __('Nu am putut procesa cererea. Încearcă din nou.', 'papetarie-storefront');
        }

        papetarie_storefront_send_auth_error_response($message);
    }

    papetarie_storefront_store_auth_notice(__('Un email a fost trimis cu succes. Verifică inboxul.', 'papetarie-storefront'), 'success');

    ob_start();
    papetarie_storefront_render_auth_lost_password_confirmation_shell([
        'context' => 'modal',
        'show_visual' => false,
    ]);
    $confirmation_html = (string) ob_get_clean();

    wp_send_json_success([
        'message' => __('Un email a fost trimis cu succes. Verifică inboxul.', 'papetarie-storefront'),
        'view' => 'lost-password-confirmation',
        'view_html' => $confirmation_html,
    ]);
}
add_action('wp_ajax_nopriv_pap_auth_lost_password', 'papetarie_storefront_handle_auth_lost_password_ajax');
add_action('wp_ajax_pap_auth_lost_password', 'papetarie_storefront_handle_auth_lost_password_ajax');

function papetarie_storefront_handle_auth_register_ajax(): void
{
    check_ajax_referer('pap_auth_register', 'nonce');

    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash((string) $_POST['first_name'])) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash((string) $_POST['last_name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) wp_unslash($_POST['email'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $password_confirm = isset($_POST['password_confirm']) ? (string) wp_unslash($_POST['password_confirm']) : '';
    $agree_terms = !empty($_POST['agree_terms']);

    $validation_messages = [];

    if ('' === $first_name) {
        $validation_messages[] = __('Completează prenumele.', 'papetarie-storefront');
    }

    if ('' === $last_name) {
        $validation_messages[] = __('Completează numele.', 'papetarie-storefront');
    }

    if ('' === $email) {
        $validation_messages[] = __('Introdu emailul.', 'papetarie-storefront');
    } elseif (!is_email($email)) {
        $validation_messages[] = __('Introdu un email valid.', 'papetarie-storefront');
    }

    if ('' === $password) {
        $validation_messages[] = __('Introdu parola.', 'papetarie-storefront');
    }

    if ('' === $password_confirm) {
        $validation_messages[] = __('Confirmă parola.', 'papetarie-storefront');
    }

    if ('' !== $password && '' !== $password_confirm && $password !== $password_confirm) {
        $validation_messages[] = __('Parolele nu se potrivesc.', 'papetarie-storefront');
    }

    if (!$agree_terms) {
        $validation_messages[] = __('Trebuie să accepți politica de confidențialitate.', 'papetarie-storefront');
    }

    if (!empty($validation_messages)) {
        foreach ($validation_messages as $message) {
            papetarie_storefront_store_auth_notice((string) $message, 'error');
        }

        wp_send_json_error([
            'message' => $validation_messages[0] ?? __('Verifică datele introduse.', 'papetarie-storefront'),
            'notice_html' => papetarie_storefront_render_auth_notice_html_from_session(),
        ], 400);
    }

    if (!isset($_POST['email'])) {
        papetarie_storefront_send_auth_error_response(__('Completează datele necesare pentru creare cont.', 'papetarie-storefront'));
    }

    $username = function_exists('wc_create_new_customer_username')
        ? wc_create_new_customer_username($email, [
            'first_name' => $first_name,
            'last_name' => $last_name,
        ])
        : sanitize_user(current(explode('@', $email)), true);

    if ('' === $username) {
        $username = sanitize_user(current(explode('@', $email)), true);
    }

    $new_customer = wc_create_new_customer($email, $username, $password, [
        'first_name' => $first_name,
        'last_name' => $last_name,
    ]);

    if (is_wp_error($new_customer)) {
        if ($new_customer->get_error_code() === 'registration-error-email-exists') {
            papetarie_storefront_send_auth_error_response(
                papetarie_storefront_translate_registration_email_exists('', $email)
            );
            return;
        }

        papetarie_storefront_send_auth_error_response($new_customer->get_error_message());
    }

    $new_customer_id = (int) $new_customer;

    update_user_meta($new_customer_id, 'first_name', $first_name);
    update_user_meta($new_customer_id, 'last_name', $last_name);
    update_user_meta($new_customer_id, 'billing_first_name', $first_name);
    update_user_meta($new_customer_id, 'billing_last_name', $last_name);
    update_user_meta($new_customer_id, papetarie_storefront_auth_confirmation_meta_key(), 0);

    $token = papetarie_storefront_auth_generate_activation_token($new_customer_id);
    $mail_sent = papetarie_storefront_auth_send_activation_email($new_customer_id, $token);

    if (!$mail_sent) {
        papetarie_storefront_store_auth_notice(__('Cont creat, dar emailul de confirmare nu a fost trimis. Încearcă din nou.', 'papetarie-storefront'), 'error');
        wp_send_json_error([
            'message' => __('Cont creat, dar emailul de confirmare nu a fost trimis. Încearcă din nou.', 'papetarie-storefront'),
            'notice_html' => papetarie_storefront_render_auth_notice_html_from_session(),
        ], 500);
    }

    papetarie_storefront_store_auth_notice(__('Ți-am trimis un email de confirmare. Verifică inboxul și activează contul înainte de autentificare.', 'papetarie-storefront'), 'success');

    wp_send_json_success([
        'message' => __('Ți-am trimis un email de confirmare. Verifică inboxul și activează contul înainte de autentificare.', 'papetarie-storefront'),
        'view' => 'register-confirmation',
    ]);
}
add_action('wp_ajax_nopriv_pap_auth_register', 'papetarie_storefront_handle_auth_register_ajax');
add_action('wp_ajax_pap_auth_register', 'papetarie_storefront_handle_auth_register_ajax');

function papetarie_storefront_handle_auth_login_ajax(): void
{
    if (!function_exists('WC') || !WC()) {
        wp_send_json_error([
            'message' => __('Autentificarea nu este disponibilă momentan.', 'papetarie-storefront'),
        ], 400);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_auth_login')) {
        wp_send_json_error([
            'message' => __('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'),
        ], 403);
    }

    $username = isset($_POST['username']) ? trim((string) sanitize_text_field(wp_unslash($_POST['username']))) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $remember = !empty($_POST['rememberme']);
    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash((string) $_POST['redirect_to'])) : '';

    if ('' === $username) {
        papetarie_storefront_send_auth_error_response(__('Introdu emailul.', 'papetarie-storefront'));
    }

    if ('' === $password) {
        papetarie_storefront_send_auth_error_response(__('Introdu parola.', 'papetarie-storefront'));
    }

    $validation_error = new WP_Error();
    $validation_error = apply_filters('woocommerce_process_login_errors', $validation_error, $username, $password);
    if ($validation_error instanceof WP_Error && $validation_error->get_error_code()) {
        papetarie_storefront_send_auth_error_response(wp_strip_all_tags((string) $validation_error->get_error_message()));
    }

    if (is_multisite()) {
        $user_data = get_user_by(is_email($username) ? 'email' : 'login', $username);
        if ($user_data && !is_user_member_of_blog($user_data->ID, get_current_blog_id())) {
            add_user_to_blog(get_current_blog_id(), $user_data->ID, 'customer');
        }
    }

    $creds = apply_filters(
        'woocommerce_login_credentials',
        [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ]
    );

    $user = wp_signon($creds, is_ssl());
    if (is_wp_error($user)) {
        papetarie_storefront_store_auth_notice(wp_strip_all_tags((string) $user->get_error_message()), 'error');
        do_action('woocommerce_login_failed');
        wp_send_json_error([
            'message' => wp_strip_all_tags((string) $user->get_error_message()),
            'notice_html' => papetarie_storefront_render_auth_notice_html_from_session(),
        ], 401);
    }

    if (function_exists('WC') && WC()->cart) {
        WC()->cart->calculate_totals();
        WC()->cart->set_session();
    }

    wp_send_json_success([
        'message' => __('Te-ai autentificat cu succes.', 'papetarie-storefront'),
        'account_html' => papetarie_storefront_render_account_tool_html(),
        'auth_state' => papetarie_storefront_get_current_user_auth_state(),
        'cart_drawer' => papetarie_storefront_get_cart_drawer_payload(),
        'cart_page' => papetarie_storefront_get_cart_page_payload(),
        'refresh_cart' => true,
    ]);
}
add_action('wp_ajax_nopriv_pap_auth_login', 'papetarie_storefront_handle_auth_login_ajax');
add_action('wp_ajax_pap_auth_login', 'papetarie_storefront_handle_auth_login_ajax');

function papetarie_storefront_handle_current_user_ajax(): void
{
    wp_send_json_success(papetarie_storefront_get_current_user_account_payload());
}
add_action('wp_ajax_nopriv_pap_auth_current_user', 'papetarie_storefront_handle_current_user_ajax');
add_action('wp_ajax_pap_auth_current_user', 'papetarie_storefront_handle_current_user_ajax');

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

function papetarie_storefront_cart_recommendation_source_product(WC_Product $product): WC_Product
{
    if ($product->is_type('variation') && $product->get_parent_id() > 0) {
        $parent = wc_get_product($product->get_parent_id());
        if ($parent instanceof WC_Product) {
            return $parent;
        }
    }

    return $product;
}

function papetarie_storefront_cart_recommendation_product_is_eligible(WC_Product $product): bool
{
    return $product->exists()
        && 'publish' === $product->get_status()
        && $product->is_visible()
        && $product->is_purchasable()
        && $product->is_in_stock();
}

function papetarie_storefront_cart_recommendation_products(int $limit = 8): array
{
    if (!function_exists('WC') || !WC()->cart || $limit < 1) {
        return [];
    }

    $exclude_ids = [];
    $cross_sell_ids = [];
    $category_slugs = [];

    foreach (WC()->cart->get_cart() as $cart_item) {
        $cart_product = $cart_item['data'] ?? null;
        if (!$cart_product instanceof WC_Product) {
            continue;
        }

        $exclude_ids[] = (int) $cart_product->get_id();

        $source_product = papetarie_storefront_cart_recommendation_source_product($cart_product);
        $exclude_ids[] = (int) $source_product->get_id();

        if ($source_product->get_parent_id() > 0) {
            $exclude_ids[] = (int) $source_product->get_parent_id();
        }

        $cross_sell_ids = array_merge($cross_sell_ids, $source_product->get_cross_sell_ids());

        $term_slugs = wp_get_post_terms($source_product->get_id(), 'product_cat', ['fields' => 'slugs']);
        if (!is_wp_error($term_slugs) && !empty($term_slugs)) {
            $category_slugs = array_merge($category_slugs, $term_slugs);
        }
    }

    $exclude_ids = array_values(array_unique(array_filter(array_map('absint', $exclude_ids))));
    $category_slugs = array_values(array_unique(array_filter(array_map('sanitize_title', $category_slugs))));

    $recommendations = [];
    $seen_ids = [];

    $append_product_ids = static function (array $candidate_ids) use (&$recommendations, &$seen_ids, $exclude_ids, $limit): void {
        foreach ($candidate_ids as $candidate_id) {
            if (count($recommendations) >= $limit) {
                break;
            }

            $candidate_id = absint($candidate_id);
            if ($candidate_id < 1 || in_array($candidate_id, $seen_ids, true) || in_array($candidate_id, $exclude_ids, true)) {
                continue;
            }

            $candidate = wc_get_product($candidate_id);
            if (!$candidate instanceof WC_Product || !papetarie_storefront_cart_recommendation_product_is_eligible($candidate)) {
                continue;
            }

            $seen_ids[] = $candidate_id;
            $recommendations[] = $candidate;
        }
    };

    $append_product_ids(array_values(array_unique(array_map('absint', $cross_sell_ids))));

    if (count($recommendations) < $limit && !empty($category_slugs)) {
        $same_category_ids = wc_get_products([
            'status' => 'publish',
            'stock_status' => 'instock',
            'limit' => max(16, ($limit - count($recommendations)) * 4),
            'category' => $category_slugs,
            'exclude' => $exclude_ids,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'return' => 'ids',
        ]);

        if (!empty($same_category_ids)) {
            $append_product_ids($same_category_ids);
        }
    }

    if (count($recommendations) < $limit) {
        $best_seller_ids = wc_get_products([
            'status' => 'publish',
            'stock_status' => 'instock',
            'limit' => max(24, ($limit - count($recommendations)) * 5),
            'exclude' => array_values(array_unique(array_merge($exclude_ids, $seen_ids))),
            'orderby' => 'popularity',
            'order' => 'DESC',
            'return' => 'ids',
        ]);

        if (!empty($best_seller_ids)) {
            $append_product_ids($best_seller_ids);
        }
    }

    return array_slice($recommendations, 0, $limit);
}

function papetarie_storefront_render_slider_product_card(WC_Product $product): void
{
    $product_id = $product->get_id();
    $product_name = $product->get_name();
    $product_url = $product->get_permalink();
    $product_image_id = $product->get_image_id();
    $product_image = $product_image_id
        ? wp_get_attachment_image($product_image_id, 'medium', false, ['loading' => 'lazy', 'alt' => $product_name])
        : '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
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
        $product_subtitle = __('Produs recomandat pentru birou și școală', 'papetarie-storefront');
    }
    $product_subtitle = wp_trim_words($product_subtitle, 8, '');
    ?>
    <article class="pap-product-card" data-product-name="<?php echo esc_attr($product_name); ?>">
      <?php echo papetarie_storefront_wishlist_button_html($product_id, 'home'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <div class="pap-product-thumb">
        <?php echo $product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
      <h3 data-product-name="<?php echo esc_attr($product_name); ?>"><?php echo esc_html($product_name); ?></h3>
      <p><?php echo esc_html($product_subtitle); ?></p>
      <div class="pap-product-meta">
        <strong class="pap-price"><?php echo wp_kses_post($product->get_price_html()); ?></strong>
        <div class="pap-product-actions">
          <button
            class="pap-home-add-to-cart"
            type="button"
            data-product-id="<?php echo esc_attr((string) $product_id); ?>"
            data-product-url="<?php echo esc_url($product_url); ?>"
            aria-label="<?php esc_attr_e('Adaugă în coș', 'papetarie-storefront'); ?>"
          >
            <span class="pap-product-action-icon"><?php echo papetarie_storefront_icon('cart'); ?></span>
          </button>
        </div>
      </div>
    </article>
    <?php
}

function papetarie_storefront_render_cart_recommendations_html(?string $title = null, ?string $subtitle = null, int $limit = 8): string
{
    if (!function_exists('WC') || !WC()->cart || $limit < 1) {
        return '';
    }

    $is_empty_cart = WC()->cart->is_empty();
    $title = $title !== null ? $title : ($is_empty_cart
        ? __('Descoperă produse populare', 'papetarie-storefront')
        : __('S-ar putea să-ți placă și', 'papetarie-storefront'));
    $subtitle = $subtitle !== null ? $subtitle : ($is_empty_cart
        ? __('Produse utile pentru birou, școală și organizare de zi cu zi.', 'papetarie-storefront')
        : __('Produse complementare pentru coșul tău.', 'papetarie-storefront'));

    $products = papetarie_storefront_cart_recommendation_products($limit);
    if (empty($products)) {
        return '';
    }

    ob_start();
    ?>
    <section class="<?php echo esc_attr($is_empty_cart ? 'pap-featured pap-cart-recommendations pap-cart-recommendations--empty' : 'pap-shell pap-featured pap-cart-recommendations'); ?>">
      <div class="pap-section-head pap-section-head-soft pap-section-head-featured">
        <h2><?php echo esc_html($title); ?></h2>
        <p><?php echo esc_html($subtitle); ?></p>
      </div>

      <div class="pap-featured-slider-shell">
        <button class="pap-featured-nav pap-featured-nav-prev" type="button" aria-label="<?php esc_attr_e('Produse anterioare', 'papetarie-storefront'); ?>" data-featured-prev>
          <span class="pap-featured-nav-icon pap-featured-nav-icon-prev" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
        </button>
        <div class="pap-featured-slider" data-featured-slider>
          <div class="pap-product-grid">
            <?php foreach ($products as $product) : ?>
              <?php if (!$product instanceof WC_Product) { continue; } ?>
              <?php papetarie_storefront_render_slider_product_card($product); ?>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="pap-featured-nav pap-featured-nav-next" type="button" aria-label="<?php esc_attr_e('Produse următoare', 'papetarie-storefront'); ?>" data-featured-next>
          <span class="pap-featured-nav-icon pap-featured-nav-icon-next" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
        </button>
      </div>
    </section>
    <?php

    return (string) ob_get_clean();
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

    if (function_exists('WC') && WC()->cart) {
        WC()->cart->calculate_totals();
        WC()->cart->set_session();

        if (function_exists('WC') && WC()->session && method_exists(WC()->session, 'save_data')) {
            WC()->session->save_data();
        }
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

    $cart_item_quantity = isset(WC()->cart->get_cart()[$added]['quantity']) ? (int) WC()->cart->get_cart()[$added]['quantity'] : $quantity;
    $cart_drawer = papetarie_storefront_get_cart_drawer_payload();
    $cart_page = papetarie_storefront_get_cart_page_payload();
    $fragments = papetarie_storefront_get_cart_page_fragments();

    papetarie_storefront_send_json_success_fast([
        'message' => __('Produsul a fost adăugat în coș', 'papetarie-storefront'),
        'name' => $product->get_name(),
        'price_html' => $product->get_price_html(),
        'cart_item_unit_price_text' => html_entity_decode(wp_strip_all_tags($product->get_price_html()), ENT_QUOTES, 'UTF-8'),
        'cart_item_total_html' => function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_product_subtotal($product, $cart_item_quantity)) : '',
        'cart_url' => wc_get_cart_url(),
        'image_url' => $image_url,
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_count_label' => papetarie_storefront_cart_count_label(),
        'cart_total_html' => function_exists('WC') && WC()->cart ? wp_kses_post(WC()->cart->get_total()) : '',
        'cart_item_key' => $added,
        'cart_item_quantity' => $cart_item_quantity,
        'cart_drawer' => $cart_drawer,
        'cart_page' => $cart_page,
        'fragments' => $fragments,
        'cart_hash' => function_exists('WC') && WC()->cart ? WC()->cart->get_cart_hash() : '',
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
    $country_options = papetarie_storefront_country_options();

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

    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label'] = __('Prenume', 'papetarie-storefront');
        $fields['billing']['billing_first_name']['placeholder'] = __('Prenume', 'papetarie-storefront');
        $fields['billing']['billing_first_name']['priority'] = 10;
    }

    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['label'] = __('Nume', 'papetarie-storefront');
        $fields['billing']['billing_last_name']['placeholder'] = __('Nume', 'papetarie-storefront');
        $fields['billing']['billing_last_name']['priority'] = 20;
    }

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

    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = __('Adresă', 'papetarie-storefront');
        $fields['billing']['billing_address_1']['placeholder'] = __('Strada Exemplu 12', 'papetarie-storefront');
        $fields['billing']['billing_address_1']['priority'] = 70;
    }

    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['label'] = __('Județ', 'papetarie-storefront');
        $fields['billing']['billing_state']['type'] = 'select';
        $fields['billing']['billing_state']['options'] = ['' => __('Alege județul', 'papetarie-storefront')] + $counties;
        $fields['billing']['billing_state']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['billing']['billing_state']['class'] ?? []),
            ['form-row-first', 'wc-enhanced-select']
        )));
        $fields['billing']['billing_state']['priority'] = 50;
    }

    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['label'] = __('Localitate', 'papetarie-storefront');
        $fields['billing']['billing_city']['type'] = 'select';
        $fields['billing']['billing_city']['options'] = ['' => __('Alege localitatea', 'papetarie-storefront')];
        $fields['billing']['billing_city']['placeholder'] = __('Alege localitatea', 'papetarie-storefront');
        $fields['billing']['billing_city']['custom_attributes'] = array_merge(
            (array) ($fields['billing']['billing_city']['custom_attributes'] ?? []),
            ['data-placeholder' => __('Alege localitatea', 'papetarie-storefront')]
        );
        $fields['billing']['billing_city']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['billing']['billing_city']['class'] ?? []),
            ['form-row-last', 'wc-enhanced-select']
        )));
        $fields['billing']['billing_city']['priority'] = 60;
    }

    if (isset($fields['billing']['billing_country'])) {
        $fields['billing']['billing_country']['label'] = __('Țară', 'papetarie-storefront');
        $fields['billing']['billing_country']['type'] = 'select';
        $fields['billing']['billing_country']['options'] = $country_options;
        $fields['billing']['billing_country']['default'] = 'RO';
        $fields['billing']['billing_country']['priority'] = 90;
    }

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = __('Telefon', 'papetarie-storefront');
        $fields['billing']['billing_phone']['placeholder'] = __('0712 345 678', 'papetarie-storefront');
        $fields['billing']['billing_phone']['priority'] = 40;
    }

    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = __('Email', 'papetarie-storefront');
        $fields['billing']['billing_email']['placeholder'] = __('nume@exemplu.ro', 'papetarie-storefront');
        $fields['billing']['billing_email']['priority'] = 30;
    }

    if (isset($fields['shipping']['shipping_state'])) {
        $fields['shipping']['shipping_state']['label'] = __('Județ', 'papetarie-storefront');
        $fields['shipping']['shipping_state']['type'] = 'select';
        $fields['shipping']['shipping_state']['options'] = ['' => __('Alege județul', 'papetarie-storefront')] + $counties;
        $fields['shipping']['shipping_state']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['shipping']['shipping_state']['class'] ?? []),
            ['wc-enhanced-select']
        )));
        $fields['shipping']['shipping_state']['priority'] = 50;
    }

    if (isset($fields['shipping']['shipping_city'])) {
        $fields['shipping']['shipping_city']['label'] = __('Localitate', 'papetarie-storefront');
        $fields['shipping']['shipping_city']['type'] = 'select';
        $fields['shipping']['shipping_city']['options'] = ['' => __('Alege localitatea', 'papetarie-storefront')];
        $fields['shipping']['shipping_city']['placeholder'] = __('Alege localitatea', 'papetarie-storefront');
        $fields['shipping']['shipping_city']['custom_attributes'] = array_merge(
            (array) ($fields['shipping']['shipping_city']['custom_attributes'] ?? []),
            ['data-placeholder' => __('Alege localitatea', 'papetarie-storefront')]
        );
        $fields['shipping']['shipping_city']['class'] = array_values(array_unique(array_merge(
            (array) ($fields['shipping']['shipping_city']['class'] ?? []),
            ['wc-enhanced-select']
        )));
        $fields['shipping']['shipping_city']['priority'] = 60;
    }

    if (isset($fields['shipping']['shipping_postcode'])) {
        $fields['shipping']['shipping_postcode']['label'] = __('Cod poștal', 'papetarie-storefront');
        $fields['shipping']['shipping_postcode']['placeholder'] = __('123456', 'papetarie-storefront');
        $fields['shipping']['shipping_postcode']['required'] = true;
        $fields['shipping']['shipping_postcode']['priority'] = 80;
        $fields['shipping']['shipping_postcode']['autocomplete'] = 'postal-code';
        $fields['shipping']['shipping_postcode']['inputmode'] = 'numeric';
        $fields['shipping']['shipping_postcode']['maxlength'] = 6;
    }

    if (isset($fields['shipping']['shipping_address_1'])) {
        $fields['shipping']['shipping_address_1']['label'] = __('Adresă', 'papetarie-storefront');
        $fields['shipping']['shipping_address_1']['placeholder'] = __('Strada Exemplu 12', 'papetarie-storefront');
        $fields['shipping']['shipping_address_1']['priority'] = 70;
    }

    if (isset($fields['shipping']['shipping_country'])) {
        $fields['shipping']['shipping_country']['label'] = __('Țară', 'papetarie-storefront');
        $fields['shipping']['shipping_country']['type'] = 'select';
        $fields['shipping']['shipping_country']['options'] = $country_options;
        $fields['shipping']['shipping_country']['default'] = 'RO';
    }

    unset($fields['billing']['billing_address_2'], $fields['shipping']['shipping_address_2']);

    $fields['order']['order_comments'] = [
        'type' => 'textarea',
        'label' => __('Observații pentru livrare / curier', 'papetarie-storefront'),
        'placeholder' => __('Interfon 12. Curierul să sune înainte.', 'papetarie-storefront'),
        'required' => false,
        'class' => ['form-row-wide'],
        'priority' => 10,
        'custom_attributes' => [
            'rows' => 4,
        ],
    ];

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'papetarie_storefront_checkout_fields');

function papetarie_storefront_checkout_default_value($value, string $input)
{
    if (!function_exists('is_checkout') || !is_checkout()) {
        return $value;
    }

    $value = is_string($value) ? trim($value) : $value;
    $country_options = papetarie_storefront_country_options();
    $counties = papetarie_storefront_romania_counties();
    $default_country = (string) get_option('woocommerce_default_country', 'RO:B');
    $default_state = '';

    if (str_contains($default_country, ':')) {
        [$default_country_code, $default_state_code] = array_pad(explode(':', $default_country, 2), 2, '');
        if ($default_country_code === 'RO' && isset($counties[$default_state_code])) {
            $default_state = $default_state_code;
        }
    }

    if ($input === 'billing_country' || $input === 'shipping_country') {
        if (is_string($value) && $value !== '' && isset($country_options[$value])) {
            return $value;
        }

        return 'RO';
    }

    if ($input === 'billing_state' || $input === 'shipping_state') {
        if (is_string($value) && $value !== '' && isset($counties[$value])) {
            return $value;
        }

        if (function_exists('is_checkout') && is_checkout() && !is_user_logged_in()) {
            return '';
        }

        return $default_state !== '' ? $default_state : '';
    }

    return $value;
}
add_filter('woocommerce_checkout_get_value', 'papetarie_storefront_checkout_default_value', 10, 2);

function papetarie_storefront_checkout_validate(array $data, \WP_Error $errors): void
{
    $counties = papetarie_storefront_romania_counties();
    $billing_company = isset($data['billing_company']) ? trim((string) $data['billing_company']) : '';
    $billing_cui = isset($data['billing_cui']) ? trim((string) $data['billing_cui']) : '';
    $billing_state = isset($data['billing_state']) ? sanitize_text_field((string) $data['billing_state']) : '';
    $billing_city = isset($data['billing_city']) ? trim((string) $data['billing_city']) : '';

    if ($billing_state === '') {
        $errors->add('billing_state_required', __('Selectează județul.', 'papetarie-storefront'));
    } else {
        if (!isset($counties[$billing_state])) {
            $errors->add('billing_state_invalid', __('Județul selectat nu este valid.', 'papetarie-storefront'));
        }
    }

    if ($billing_city === '') {
        $errors->add('billing_city_required', __('Completează orașul.', 'papetarie-storefront'));
    } elseif ($billing_state !== '' && isset($counties[$billing_state])) {
        $billing_city_options = papetarie_storefront_checkout_city_options_for_county($billing_state);
        $billing_city_key = papetarie_storefront_normalize_city_key($billing_city);
        $billing_city_matches = false;

        foreach (array_keys($billing_city_options) as $billing_city_option) {
            if (papetarie_storefront_normalize_city_key($billing_city_option) === $billing_city_key) {
                $billing_city_matches = true;
                break;
            }
        }

        if (!$billing_city_matches) {
            $errors->add('billing_city_state_mismatch', __('Localitatea selectată nu pare să aparțină județului ales.', 'papetarie-storefront'));
        }
    }

    $shipping_state = isset($data['shipping_state']) ? sanitize_text_field((string) $data['shipping_state']) : '';
    $shipping_city = isset($data['shipping_city']) ? trim((string) $data['shipping_city']) : '';
    $shipping_postcode = isset($data['shipping_postcode']) ? trim((string) $data['shipping_postcode']) : '';

    if (!empty($data['ship_to_different_address'])) {
        if ($shipping_state === '') {
            $errors->add('shipping_state_required', __('Selectează județul de livrare.', 'papetarie-storefront'));
        } else {
            if (!isset($counties[$shipping_state])) {
                $errors->add('shipping_state_invalid', __('Județul de livrare selectat nu este valid.', 'papetarie-storefront'));
            }
        }

        if ($shipping_city === '') {
            $errors->add('shipping_city_required', __('Completează localitatea de livrare.', 'papetarie-storefront'));
        } elseif ($shipping_state !== '' && isset($counties[$shipping_state])) {
            $shipping_city_options = papetarie_storefront_checkout_city_options_for_county($shipping_state);
            $shipping_city_key = papetarie_storefront_normalize_city_key($shipping_city);
            $shipping_city_matches = false;

            foreach (array_keys($shipping_city_options) as $shipping_city_option) {
                if (papetarie_storefront_normalize_city_key($shipping_city_option) === $shipping_city_key) {
                    $shipping_city_matches = true;
                    break;
                }
            }

            if (!$shipping_city_matches) {
                $errors->add('shipping_city_state_mismatch', __('Localitatea de livrare nu pare să aparțină județului ales.', 'papetarie-storefront'));
            }
        }
    }

    if ($shipping_postcode === '') {
        $errors->add('shipping_postcode_required', __('Completează codul poștal.', 'papetarie-storefront'));
    } elseif (!preg_match('/^[0-9]{6}$/', preg_replace('/\s+/', '', $shipping_postcode))) {
        $errors->add('shipping_postcode_invalid', __('Introdu un cod poștal valid.', 'papetarie-storefront'));
    }
}
add_action('woocommerce_after_checkout_validation', 'papetarie_storefront_checkout_validate', 10, 2);

function papetarie_storefront_save_billing_address_contact_fields(int $user_id, string $load_address): void
{
    if (in_array($load_address, ['facturare', 'billing'], true)) {
        $load_address = 'billing';
    } elseif (in_array($load_address, ['livrare', 'shipping'], true)) {
        $load_address = 'shipping';
    }

    if ($load_address !== 'billing') {
        return;
    }

    if (isset($_POST['billing_phone'])) {
        update_user_meta($user_id, 'billing_phone', sanitize_text_field(wp_unslash((string) $_POST['billing_phone'])));
    }

    if (isset($_POST['billing_email'])) {
        $email = sanitize_email(wp_unslash((string) $_POST['billing_email']));
        if ($email !== '') {
            update_user_meta($user_id, 'billing_email', $email);
            $user = get_user_by('id', $user_id);
            if ($user instanceof WP_User && $user->user_email !== $email) {
                wp_update_user([
                    'ID' => $user_id,
                    'user_email' => $email,
                ]);
            }
        }
    }
}
add_action('woocommerce_customer_save_address', 'papetarie_storefront_save_billing_address_contact_fields', 10, 2);

function papetarie_storefront_flush_rewrite_on_theme_switch(): void
{
    papetarie_storefront_register_account_endpoints();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'papetarie_storefront_flush_rewrite_on_theme_switch');

function papetarie_storefront_account_menu_items(array $items): array
{
    return [
        'dashboard' => __('Acasă', 'papetarie-storefront'),
        'orders' => __('Comenzile mele', 'papetarie-storefront'),
        'favorite' => __('Favorite', 'papetarie-storefront'),
        'edit-address' => __('Adrese', 'papetarie-storefront'),
        'edit-account' => __('Detalii cont', 'papetarie-storefront'),
        'customer-logout' => __('Deconectare', 'papetarie-storefront'),
    ];
}
add_filter('woocommerce_account_menu_items', 'papetarie_storefront_account_menu_items');

function papetarie_storefront_account_menu_icon_map(): array
{
    return [
        'dashboard' => 'sidebar-home',
        'orders' => 'sidebar-orders',
        'downloads' => 'sidebar-downloads',
        'favorite' => 'sidebar-favorite',
        'edit-address' => 'sidebar-address',
        'edit-account' => 'sidebar-details',
        'payment-methods' => 'sidebar-payment-methods',
        'customer-logout' => 'sidebar-logout',
    ];
}

function papetarie_storefront_account_icon_class(string $name): string
{
    $icons = [
        'account' => 'fa-solid fa-user',
        'cart' => 'fa-solid fa-cart-shopping',
        'chevron' => 'fa-solid fa-chevron-right',
        'dashboard' => 'fa-solid fa-house',
        'edit-account' => 'fa-solid fa-user-pen',
        'edit-address' => 'fa-solid fa-location-dot',
        'favorite' => 'fa-solid fa-heart',
        'credit-card' => 'fa-solid fa-credit-card',
        'download' => 'fa-solid fa-download',
        'help' => 'fa-solid fa-headset',
        'heart' => 'fa-solid fa-heart',
        'home' => 'fa-solid fa-house',
        'lock-outline' => 'fa-solid fa-lock',
        'logout' => 'fa-solid fa-right-from-bracket',
        'location' => 'fa-solid fa-location-dot',
        'orders' => 'fa-solid fa-cart-shopping',
        'payment-methods' => 'fa-solid fa-credit-card',
        'paper' => 'fa-solid fa-file-lines',
        'pen' => 'fa-solid fa-pen-to-square',
        'shield' => 'fa-solid fa-shield-halved',
        'truck' => 'fa-solid fa-truck-fast',
        'truck-outline' => 'fa-solid fa-truck-fast',
        'user' => 'fa-solid fa-user',
    ];

    return $icons[$name] ?? 'fa-solid fa-circle';
}

function papetarie_storefront_render_account_icon(string $name, string $extra_classes = ''): string
{
    if (str_starts_with($name, 'sidebar-')) {
        $svg_map = [
            'sidebar-home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 11.2 12 4l9 7.2"></path><path d="M5 10.5V21h14V10.5"></path><path d="M9.5 21v-6h5v6"></path></svg>',
            'sidebar-orders' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="5" y="3.5" width="14" height="17" rx="2"></rect><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>',
            'sidebar-downloads' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 4v10"></path><path d="M8 10.5 12 14.5l4-4"></path><path d="M5 20h14"></path></svg>',
            'sidebar-favorite' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 21s-7-4.8-9.2-9.2C1.1 8.9 2.7 5.8 5.9 4.8c1.9-.6 4 .1 5.3 1.7 1.3-1.6 3.4-2.3 5.3-1.7 3.2 1 4.8 4.1 3.1 7C19 16.2 12 21 12 21z"></path></svg>',
            'sidebar-address' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 21s6-5.2 6-11a6 6 0 0 0-12 0c0 5.8 6 11 6 11z"></path><circle cx="12" cy="10" r="2.25"></circle></svg>',
            'sidebar-details' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="5" width="16" height="14" rx="2"></rect><circle cx="9" cy="10" r="1.5"></circle><path d="M13 9h4"></path><path d="M7.8 14.2c.8-1.3 2.1-2.1 3.5-2.1s2.7.8 3.5 2.1"></path></svg>',
            'sidebar-payment-methods' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="14" rx="2"></rect><path d="M3.5 9h17"></path><path d="M7 15.5h3"></path><path d="M12 15.5h2.5"></path></svg>',
            'sidebar-logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 4a8 8 0 1 0 8 8"></path><path d="M12 4v7"></path></svg>',
        ];

        if (isset($svg_map[$name])) {
            return $svg_map[$name];
        }
    }

    $classes = trim(papetarie_storefront_account_icon_class($name) . ' ' . $extra_classes);

    return sprintf(
        '<i class="%s" aria-hidden="true"></i>',
        esc_attr($classes)
    );
}

function papetarie_storefront_account_order_display_number(WC_Order $order): string
{
    $raw_number = (string) $order->get_order_number();
    $normalized_number = preg_replace('/^SH-?/i', '', $raw_number);

    if (!is_string($normalized_number) || $normalized_number === '') {
        $normalized_number = $raw_number;
    }

    return '#SH-' . $normalized_number;
}

function papetarie_storefront_account_order_summary_secondary(WC_Order $order): string
{
    $status_data = papetarie_storefront_account_order_status_data($order);
    $date = $order->get_date_created() ? wp_date('j F Y', $order->get_date_created()->getTimestamp()) : '';

    switch ($order->get_status()) {
        case 'completed':
            return $date
                ? sprintf(
                    /* translators: %s: order date. */
                    __('Finalizată pe %s.', 'papetarie-storefront'),
                    $date
                )
                : __('Finalizată.', 'papetarie-storefront');
        case 'processing':
            return __('În procesare.', 'papetarie-storefront');
        case 'pending':
        case 'on-hold':
            return __('În așteptare.', 'papetarie-storefront');
        case 'cancelled':
        case 'refunded':
        case 'failed':
            return __('Anulată.', 'papetarie-storefront');
        default:
            return $date
                ? sprintf(
                    /* translators: %s: order date. */
                    __('%1$s pe %2$s.', 'papetarie-storefront'),
                    $status_data['label'],
                    $date
                )
                : $status_data['label'];
    }
}

function papetarie_storefront_account_order_status_data(WC_Order $order): array
{
    $status = $order->get_status();

    $map = [
        'completed' => [
            'label' => __('Livrată', 'papetarie-storefront'),
            'class' => 'is-success',
        ],
        'processing' => [
            'label' => __('Procesare', 'papetarie-storefront'),
            'class' => 'is-processing',
        ],
        'pending' => [
            'label' => __('În așteptare', 'papetarie-storefront'),
            'class' => 'is-pending',
        ],
        'on-hold' => [
            'label' => __('În așteptare', 'papetarie-storefront'),
            'class' => 'is-pending',
        ],
        'cancelled' => [
            'label' => __('Anulată', 'papetarie-storefront'),
            'class' => 'is-cancelled',
        ],
        'refunded' => [
            'label' => __('Anulată', 'papetarie-storefront'),
            'class' => 'is-cancelled',
        ],
        'failed' => [
            'label' => __('Anulată', 'papetarie-storefront'),
            'class' => 'is-cancelled',
        ],
    ];

    if (isset($map[$status])) {
        return $map[$status];
    }

    return [
        'label' => wc_get_order_status_name($status),
        'class' => 'is-neutral',
    ];
}

function papetarie_storefront_account_order_badge_html(WC_Order $order): string
{
    $status_data = papetarie_storefront_account_order_status_data($order);

    return sprintf(
        '<span class="pap-account-status-badge %1$s">%2$s</span>',
        esc_attr($status_data['class']),
        esc_html($status_data['label'])
    );
}

function papetarie_storefront_account_order_payment_suffix(WC_Order $order): string
{
    $payment_method_title = trim((string) $order->get_payment_method_title());
    $last4 = '';
    $meta_keys = [
        '_payment_method_last4',
        '_stripe_card_last4',
        '_stripe_source_last4',
        '_card_last4',
        'last4',
    ];

    foreach ($meta_keys as $meta_key) {
        $meta_value = trim((string) $order->get_meta($meta_key));
        if ($meta_value !== '') {
            $last4 = preg_replace('/[^0-9]/', '', $meta_value) ?: $meta_value;
            break;
        }
    }

    if ($payment_method_title === '') {
        $payment_method_title = __('Plată online', 'papetarie-storefront');
    }

    if ($last4 !== '') {
        return sprintf('%1$s • .... %2$s', $payment_method_title, $last4);
    }

    return $payment_method_title;
}

function papetarie_storefront_account_shipping_method_label(WC_Order $order): string
{
    $shipping_methods = [];

    foreach ($order->get_items('shipping') as $shipping_item) {
        if ($shipping_item instanceof WC_Order_Item_Shipping) {
            $label = trim((string) $shipping_item->get_name());
            if ($label !== '') {
                $shipping_methods[] = $label;
            }
        }
    }

    if (!$shipping_methods) {
        $shipping_methods[] = __('Curier rapid', 'papetarie-storefront');
    }

    return implode(', ', array_unique($shipping_methods));
}

function papetarie_storefront_account_shipping_company_label(WC_Order $order): string
{
    $shipping_company = trim((string) $order->get_shipping_company());
    $shipping_first_name = trim((string) $order->get_shipping_first_name());
    $shipping_last_name = trim((string) $order->get_shipping_last_name());
    $shipping_city = trim((string) $order->get_shipping_city());

    if ($shipping_company !== '') {
        return $shipping_company;
    }

    $name = trim($shipping_first_name . ' ' . $shipping_last_name);
    $parts = array_filter([$name, $shipping_city]);

    if ($parts) {
        return implode(' Â· ', $parts);
    }

    $billing_company = trim((string) $order->get_billing_company());
    if ($billing_company !== '') {
        return $billing_company;
    }

    return __('Fan Courier', 'papetarie-storefront');
}

function papetarie_storefront_account_order_totals_rows(WC_Order $order): array
{
    $subtotal = (float) $order->get_subtotal();
    $shipping_total = (float) $order->get_shipping_total();
    $shipping_tax = (float) $order->get_shipping_tax();
    $tax_total = (float) $order->get_total_tax();
    $items_total = $subtotal + $shipping_total + $tax_total;

    return [
        [
            'label' => __('Subtotal', 'papetarie-storefront'),
            'value' => papetarie_storefront_format_plain_currency_amount($subtotal),
        ],
        [
            'label' => __('Transport', 'papetarie-storefront'),
            'value' => papetarie_storefront_format_plain_currency_amount($shipping_total),
        ],
        [
            'label' => __('TVA', 'papetarie-storefront'),
            'value' => papetarie_storefront_format_plain_currency_amount($tax_total),
        ],
        [
            'label' => __('Total comandă', 'papetarie-storefront'),
            'value' => papetarie_storefront_format_plain_currency_amount((float) $order->get_total()),
        ],
    ];
}

function papetarie_storefront_account_order_item_rows(WC_Order $order): array
{
    $rows = [];

    foreach ($order->get_items('line_item') as $item_id => $item) {
        if (!$item instanceof WC_Order_Item_Product) {
            continue;
        }

        $product = $item->get_product();
        $quantity = max(1, (int) $item->get_quantity());
        $subtotal = (float) $order->get_line_subtotal($item, true, true);
        $total = (float) $item->get_total() + (float) $item->get_total_tax();

        $rows[] = [
            'name' => $item->get_name(),
            'sku' => $product instanceof WC_Product ? $product->get_sku() : '',
            'image' => $product instanceof WC_Product ? $product->get_image_id() : 0,
            'unit_price' => papetarie_storefront_format_plain_currency_amount($quantity > 0 ? $subtotal / $quantity : $subtotal),
            'quantity' => (string) $quantity,
            'total' => papetarie_storefront_format_plain_currency_amount($total),
        ];
    }

    return $rows;
}

function papetarie_storefront_account_order_items_count(WC_Order $order): int
{
    $count = 0;

    foreach ($order->get_items('line_item') as $item) {
        if ($item instanceof WC_Order_Item_Product) {
            $count += max(1, (int) $item->get_quantity());
        }
    }

    return $count;
}

function papetarie_storefront_account_real_order_statuses(): array
{
    if (!function_exists('wc_get_order_statuses')) {
        return [];
    }

    $statuses = array_map(static function (string $status): string {
        return str_replace('wc-', '', $status);
    }, array_keys(wc_get_order_statuses()));

    $statuses = array_values(array_filter($statuses, static function (string $status): bool {
        return $status !== 'checkout-draft' && $status !== '';
    }));

    /**
     * Allow explicit extension of the account orders status list.
     * Only real WooCommerce order statuses should be added here.
     */
    return array_values(array_unique((array) apply_filters('papetarie_storefront_account_real_order_statuses', $statuses)));
}

function papetarie_storefront_account_real_order_status_options(): array
{
    if (!function_exists('wc_get_order_statuses')) {
        return [];
    }

    $options = [];

    foreach (wc_get_order_statuses() as $status_key => $label) {
        $status = str_replace('wc-', '', (string) $status_key);
        if ($status === 'checkout-draft' || $status === '') {
            continue;
        }

        $options[$status] = (string) $label;
    }

    return $options;
}

function papetarie_storefront_account_customer_orders(int $user_id, array $args = []): array
{
    if (!function_exists('wc_get_orders')) {
        return [];
    }

    $query_args = array_merge([
        'customer_id' => $user_id,
        'type' => 'shop_order',
        'status' => papetarie_storefront_account_real_order_statuses(),
        'orderby' => 'date',
        'order' => 'DESC',
    ], $args);

    if (isset($query_args['status'])) {
        $status = $query_args['status'];
        if ($status === 'all') {
            $query_args['status'] = papetarie_storefront_account_real_order_statuses();
        } elseif (is_string($status)) {
            $query_args['status'] = [$status];
        } elseif (is_array($status)) {
            $query_args['status'] = array_values(array_intersect(array_map('sanitize_key', $status), papetarie_storefront_account_real_order_statuses()));
        }
    }

    if (empty($query_args['status'])) {
        $query_args['status'] = papetarie_storefront_account_real_order_statuses();
    }

    return wc_get_orders($query_args);
}

function papetarie_storefront_account_customer_order_count(int $user_id): int
{
    return count(papetarie_storefront_account_customer_orders($user_id, [
        'limit' => -1,
        'return' => 'ids',
    ]));
}

function papetarie_storefront_account_dashboard_stats(int $user_id): array
{
    $recent_orders = papetarie_storefront_account_customer_orders($user_id, [
        'limit' => -1,
    ]);
    $order_count = papetarie_storefront_account_customer_order_count($user_id);
    $wishlist_ids = papetarie_storefront_account_wishlist_ids();
    $last_order = null;

    foreach ($recent_orders as $recent_order) {
        if ($recent_order instanceof WC_Order) {
            $last_order = $recent_order;
            break;
        }
    }

    return [
        [
            'label' => __('Comenzi', 'papetarie-storefront'),
            'value' => (string) $order_count,
            'secondary' => $order_count > 0
                ? __('Comenzi plasate.', 'papetarie-storefront')
                : __('Nu ai comenzi încă.', 'papetarie-storefront'),
            'icon' => 'cart',
            'tone' => 'blue',
            'href' => wc_get_account_endpoint_url('orders'),
            'aria_label' => $order_count > 0
                ? sprintf(
                    /* translators: %s: order count. */
                    __('Comenzi: %s. Comenzi plasate.', 'papetarie-storefront'),
                    (string) $order_count
                )
                : __('Comenzi: 0. Nu ai comenzi încă.', 'papetarie-storefront'),
        ],
        [
            'label' => __('Ultima comandă', 'papetarie-storefront'),
            'value' => $last_order instanceof WC_Order
                ? (function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($last_order) : ('#' . $last_order->get_order_number()))
                : '—',
            'secondary' => $last_order instanceof WC_Order
                ? papetarie_storefront_account_order_summary_secondary($last_order)
                : __('Nicio comandă aici.', 'papetarie-storefront'),
            'icon' => 'paper',
            'tone' => 'green',
            'href' => $last_order instanceof WC_Order ? $last_order->get_view_order_url() : wc_get_account_endpoint_url('orders'),
            'aria_label' => $last_order instanceof WC_Order
                ? sprintf(
                    /* translators: %s: order number. */
                    __('Ultima comandă %s. %s', 'papetarie-storefront'),
                    function_exists('papetarie_storefront_account_order_display_number') ? papetarie_storefront_account_order_display_number($last_order) : ('#' . $last_order->get_order_number()),
                    papetarie_storefront_account_order_summary_secondary($last_order)
                )
                : __('Ultima comandă. Nicio comandă aici.', 'papetarie-storefront'),
        ],
        [
            'label' => __('Favorite', 'papetarie-storefront'),
            'value' => (string) count($wishlist_ids),
            'secondary' => count($wishlist_ids) > 0
                ? __('Produse salvate.', 'papetarie-storefront')
                : __('Nu ai produse favorite.', 'papetarie-storefront'),
            'icon' => 'heart',
            'tone' => 'orange',
            'href' => wc_get_account_endpoint_url('favorite'),
            'aria_label' => count($wishlist_ids) > 0
                ? sprintf(
                    /* translators: %s: favorite count. */
                    __('Favorite: %s. Produse salvate.', 'papetarie-storefront'),
                    (string) count($wishlist_ids)
                )
                : __('Favorite: 0. Nu ai produse favorite.', 'papetarie-storefront'),
        ],
    ];
}

function papetarie_storefront_customer_real_order_count_filter($order_count, WC_Customer $customer)
{
    if (!$customer instanceof WC_Customer || !$customer->get_id()) {
        return $order_count;
    }

    return papetarie_storefront_account_customer_order_count((int) $customer->get_id());
}
add_filter('woocommerce_customer_get_order_count', 'papetarie_storefront_customer_real_order_count_filter', 20, 2);

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
    <article class="pap-product-card pap-product-card--<?php echo esc_attr($context); ?>" data-product-name="<?php echo esc_attr($product_name); ?>">
      <?php echo papetarie_storefront_wishlist_button_html($product_id, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      <a class="pap-product-card-link" href="<?php echo esc_url($product_url); ?>">
        <div class="pap-product-thumb pap-product-thumb--<?php echo esc_attr($context); ?>">
          <?php echo $product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <div class="pap-product-copy">
          <h3 data-product-name="<?php echo esc_attr($product_name); ?>"><?php echo esc_html($product_name); ?></h3>
          <p><?php echo esc_html(wp_trim_words($product_subtitle, 9, '')); ?></p>
        </div>
      </a>
      <div class="pap-product-meta pap-product-meta--<?php echo esc_attr($context); ?>">
        <strong class="pap-price"><?php echo wp_kses_post($product->get_price_html()); ?></strong>
        <div class="pap-product-actions">
          <?php if ($can_add_to_cart && $product->is_type('simple')) : ?>
            <button
              type="button"
              class="pap-home-add-to-cart <?php echo esc_attr($action_class); ?>"
              aria-label="<?php echo esc_attr($action_text); ?>"
              data-product-id="<?php echo esc_attr($product->get_id()); ?>"
              data-product-url="<?php echo esc_url($product_url); ?>"
              data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
            >
              <span class="pap-product-action-icon"><?php echo papetarie_storefront_icon('cart'); ?></span>
            </button>
          <?php else : ?>
            <a
              class="pap-home-add-to-cart"
              href="<?php echo esc_url($action_url); ?>"
              aria-label="<?php echo esc_attr($action_text); ?>"
            >
              <span class="pap-product-action-icon"><?php echo papetarie_storefront_icon('cart'); ?></span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php
}

function papetarie_storefront_account_wishlist_ids(): array
{
    return papetarie_storefront_get_wishlist_ids();
}

function papetarie_storefront_render_account_page_head(string $title, string $description, string $actions_html = ''): void
{
    $has_actions = trim($actions_html) !== '';
    ?>
    <div class="pap-account-page-head<?php echo $has_actions ? ' pap-account-page-head--has-action' : ''; ?>">
      <h1><?php echo esc_html($title); ?></h1>
      <p><?php echo esc_html($description); ?></p>
      <?php if ($has_actions) : ?>
        <div class="pap-account-page-head__actions">
          <?php echo $actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

function papetarie_storefront_render_account_tabs(array $tabs, string $active_tab, string $aria_label = '', string $nav_class = 'pap-account-address-tabs', string $tab_class = 'pap-account-address-tabs__tab'): void
{
    $active_tab = sanitize_key($active_tab);
    $nav_class = trim($nav_class) !== '' ? $nav_class : 'pap-account-address-tabs';
    $tab_class = trim($tab_class) !== '' ? $tab_class : 'pap-account-address-tabs__tab';
    ?>
    <nav class="<?php echo esc_attr($nav_class); ?>"<?php echo $aria_label !== '' ? ' aria-label="' . esc_attr($aria_label) . '"' : ''; ?>>
      <?php foreach ($tabs as $tab_key => $tab) : ?>
        <?php
        $tab_key = sanitize_key((string) $tab_key);
        $label = (string) ($tab['label'] ?? '');
        $url = (string) ($tab['url'] ?? '');
        $is_active = $tab_key === $active_tab;
        ?>
        <a class="<?php echo esc_attr($tab_class . ($is_active ? ' is-active' : '')); ?>" href="<?php echo esc_url($url); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
          <?php echo esc_html($label); ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php
}

function papetarie_storefront_render_account_tab_section(string $section_class, callable $renderer): void
{
    $section_class = trim($section_class) !== '' ? $section_class : 'pap-account-section';
    ?>
    <section class="<?php echo esc_attr($section_class); ?>">
      <?php $renderer(); ?>
    </section>
    <?php
}

function papetarie_storefront_account_favorite_endpoint(): void
{
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea produsele favorite.', 'papetarie-storefront') . '</p>';
        return;
    }

    $ids = papetarie_storefront_account_wishlist_ids();
    ?>
    <div class="pap-account-page pap-account-page--favorites">
      <?php papetarie_storefront_render_account_page_head(
          __('Favorite', 'papetarie-storefront'),
          __('Produsele salvate pentru revenire rapidă și adăugare instant în coș.', 'papetarie-storefront')
      ); ?>

      <section class="pap-account-panel pap-account-panel--favorites">
        <?php if (!$ids) : ?>
          <div class="pap-account-empty-state">
            <p><?php esc_html_e('Nu ai produse salvate momentan.', 'papetarie-storefront'); ?></p>
            <a class="pap-account-row-action" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Continuă cumpărăturile', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span></a>
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
      </section>
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
              <span class="pap-account-support-icon" aria-hidden="true"><?php echo function_exists('papetarie_storefront_render_account_icon') ? papetarie_storefront_render_account_icon((string) $item['icon']) : ''; ?></span>
              <h3><?php echo esc_html($item['title']); ?></h3>
              <p><?php echo esc_html($item['text']); ?></p>
            </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
add_action('woocommerce_account_suport_endpoint', 'papetarie_storefront_account_support_endpoint');

function papetarie_storefront_store_return_notice(string $message, string $type = 'info'): void
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $notice_type = in_array($type, ['success', 'error', 'info', 'warning'], true) ? $type : 'info';
    $message = trim(wp_strip_all_tags($message));

    if ('' === $message) {
        return;
    }

    $notices = WC()->session->get('pap_return_notices', []);
    if (!is_array($notices)) {
        $notices = [];
    }

    $notices[] = [
        'type' => $notice_type,
        'message' => $message,
    ];

    WC()->session->set('pap_return_notices', $notices);
}

function papetarie_storefront_render_return_notices(): void
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }

    $notices = WC()->session->get('pap_return_notices', []);
    if (!is_array($notices) || empty($notices)) {
        return;
    }

    echo '<div class="pap-auth-notices pap-return-notices" role="status" aria-live="polite">';
    foreach ($notices as $notice) {
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
    }
    echo '</div>';

    WC()->session->set('pap_return_notices', []);
}

function papetarie_storefront_handle_return_request(): void
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_wc_endpoint_url('retururi')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pap_return_request'])) {
        return;
    }

    if (!is_user_logged_in()) {
        papetarie_storefront_store_return_notice(__('Trebuie să fii autentificat pentru a trimite o cerere de retur.', 'papetarie-storefront'), 'error');
        return;
    }

    $nonce = isset($_POST['pap_return_nonce']) ? sanitize_text_field(wp_unslash($_POST['pap_return_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_return_request')) {
        papetarie_storefront_store_return_notice(__('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'), 'error');
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
    papetarie_storefront_store_return_notice(__('Cererea de retur a fost trimisă. Revenim cu un răspuns.', 'papetarie-storefront'), 'success');

    $redirect_url = add_query_arg([], wc_get_account_endpoint_url('retururi'));
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_return_request');

function papetarie_storefront_is_checkout_test_cases_request(): bool
{
    if (is_admin() || wp_doing_ajax()) {
        return false;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');

    return $path === 'checkout-test-cases';
}

function papetarie_storefront_get_checkout_test_cases_title(): string
{
    $page = get_page_by_path('checkout-test-cases', OBJECT, 'page');

    if ($page instanceof WP_Post && $page->post_title !== '') {
        return $page->post_title;
    }

    return __('Index cazuri de testare Checkout', 'papetarie-storefront');
}

function papetarie_storefront_filter_checkout_test_cases_document_title(array $title_parts): array
{
    if (!papetarie_storefront_is_checkout_test_cases_request()) {
        return $title_parts;
    }

    $title_parts['title'] = papetarie_storefront_get_checkout_test_cases_title();

    return $title_parts;
}

add_filter('document_title_parts', 'papetarie_storefront_filter_checkout_test_cases_document_title', 20);

function papetarie_storefront_render_checkout_test_cases_route(): void
{
    if (!papetarie_storefront_is_checkout_test_cases_request()) {
        return;
    }

    if (!function_exists('status_header')) {
        return;
    }

    global $post, $wp_query;

    $page = get_page_by_path('checkout-test-cases', OBJECT, 'page');

    if ($page instanceof WP_Post) {
        $post = $page;

        if ($wp_query instanceof WP_Query) {
            $wp_query->is_404 = false;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->queried_object = $page;
            $wp_query->queried_object_id = (int) $page->ID;
            $wp_query->post = $page;
            $wp_query->posts = [$page];
            $wp_query->post_count = 1;
            $wp_query->found_posts = 1;
            $wp_query->max_num_pages = 1;
        }

        setup_postdata($post);
    }

    status_header(200);
    nocache_headers();

    $template = locate_template('checkout-test-cases.php', false, false);
    if ($template === '') {
        $template = get_stylesheet_directory() . '/checkout-test-cases.php';
    }

    if (is_readable($template)) {
        include $template;
        exit;
    }

    wp_die(esc_html__('Template-ul pentru indexul de testări checkout nu a fost găsit.', 'papetarie-storefront'));
}

add_action('template_redirect', 'papetarie_storefront_render_checkout_test_cases_route', 1);

function papetarie_storefront_get_checkout_test_comment_post_type(): string
{
    return 'pap_checkout_comment';
}

function papetarie_storefront_get_checkout_test_comment_statuses(): array
{
    return ['open', 'in_progress', 'fixed', 'ignored'];
}

function papetarie_storefront_normalize_checkout_test_comment_status(string $status): string
{
    $status = sanitize_key($status);

    return in_array($status, papetarie_storefront_get_checkout_test_comment_statuses(), true) ? $status : 'open';
}

function papetarie_storefront_get_checkout_test_comment_environment(): string
{
    $environment = function_exists('wp_get_environment_type') ? (string) wp_get_environment_type() : '';
    $environment = strtolower(trim($environment));

    return match ($environment) {
        'production', 'prod' => 'production',
        'staging', 'stage', 'qa' => 'staging',
        'local', 'development', 'dev' => 'local',
        default => 'local',
    };
}

function papetarie_storefront_register_checkout_test_comment_cpt(): void
{
    register_post_type(papetarie_storefront_get_checkout_test_comment_post_type(), [
        'labels' => [
            'name' => __('Checkout comments', 'papetarie-storefront'),
            'singular_name' => __('Checkout comment', 'papetarie-storefront'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'exclude_from_search' => true,
        'rewrite' => false,
        'query_var' => false,
        'has_archive' => false,
        'hierarchical' => false,
        'supports' => ['title', 'editor', 'author'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-clipboard',
    ]);
}
add_action('init', 'papetarie_storefront_register_checkout_test_comment_cpt');

function papetarie_storefront_format_checkout_test_comment(WP_Post $post): array
{
    $test_case_id = (string) get_post_meta($post->ID, 'test_case_id', true);
    $test_case_title = (string) get_post_meta($post->ID, 'test_case_title', true);
    $comment_text = (string) get_post_meta($post->ID, 'comment_text', true);
    if ($comment_text === '') {
        $comment_text = (string) $post->post_content;
    }

    $status = papetarie_storefront_normalize_checkout_test_comment_status((string) get_post_meta($post->ID, 'status', true));
    $environment = (string) get_post_meta($post->ID, 'environment', true);
    $page_url = (string) get_post_meta($post->ID, 'page_url', true);
    $screenshot_url = (string) get_post_meta($post->ID, 'screenshot_url', true);
    $created_at = (string) get_post_meta($post->ID, 'created_at', true);
    $updated_at = (string) get_post_meta($post->ID, 'updated_at', true);
    $user_id = (int) get_post_meta($post->ID, 'user_id', true);

    if ($created_at === '') {
        $created_at = (string) $post->post_date;
    }

    if ($updated_at === '') {
        $updated_at = (string) $post->post_modified;
    }

    $author_name = '';
    if ($user_id > 0) {
        $user = get_user_by('id', $user_id);
        if ($user instanceof WP_User) {
            $author_name = $user->display_name ?: $user->user_email;
        }
    }

    return [
        'id' => (int) $post->ID,
        'test_case_id' => $test_case_id,
        'test_case_title' => $test_case_title,
        'comment_text' => $comment_text,
        'environment' => $environment,
        'page_url' => $page_url,
        'user_id' => $user_id,
        'author_name' => $author_name,
        'status' => $status,
        'screenshot_url' => $screenshot_url,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
    ];
}

function papetarie_storefront_get_checkout_test_comments(array $query_args = []): array
{
    $posts = get_posts(array_merge([
        'post_type' => papetarie_storefront_get_checkout_test_comment_post_type(),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
        'fields' => 'all',
        'no_found_rows' => true,
        'suppress_filters' => true,
    ], $query_args));

    if (empty($posts)) {
        return [];
    }

    return array_values(array_map(static function ($post) {
        return $post instanceof WP_Post ? papetarie_storefront_format_checkout_test_comment($post) : [];
    }, $posts));
}

function papetarie_storefront_get_checkout_test_comment_index(): array
{
    $index = [];

    foreach (papetarie_storefront_get_checkout_test_comments() as $comment) {
        $case_id = isset($comment['test_case_id']) ? (string) $comment['test_case_id'] : '';
        if ($case_id === '') {
            continue;
        }

        if (!isset($index[$case_id])) {
            $index[$case_id] = [
                'test_case_id' => $case_id,
                'test_case_title' => isset($comment['test_case_title']) ? (string) $comment['test_case_title'] : '',
                'comments' => [],
                'total_count' => 0,
                'open_count' => 0,
                'latest_comment' => null,
                'has_open_comment' => false,
            ];
        }

        $index[$case_id]['comments'][] = $comment;
        $index[$case_id]['total_count']++;

        $status = isset($comment['status']) ? (string) $comment['status'] : 'open';
        if (in_array($status, ['open', 'in_progress'], true)) {
            $index[$case_id]['open_count']++;
            $index[$case_id]['has_open_comment'] = true;
        }

        if ($index[$case_id]['test_case_title'] === '' && isset($comment['test_case_title'])) {
            $index[$case_id]['test_case_title'] = (string) $comment['test_case_title'];
        }
    }

    foreach ($index as $case_id => $entry) {
        usort($index[$case_id]['comments'], static function (array $left, array $right): int {
            return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
        });

        $latest_comment = end($index[$case_id]['comments']);
        $index[$case_id]['latest_comment'] = $latest_comment ?: null;
        $index[$case_id]['latest_status'] = is_array($latest_comment) ? (string) ($latest_comment['status'] ?? 'open') : 'open';
        $index[$case_id]['latest_comment_text'] = is_array($latest_comment) ? (string) ($latest_comment['comment_text'] ?? '') : '';
        reset($index[$case_id]['comments']);
    }

    return $index;
}

function papetarie_storefront_insert_checkout_test_comment(array $payload)
{
    $case_id = isset($payload['test_case_id']) ? sanitize_text_field(wp_unslash((string) $payload['test_case_id'])) : '';
    $comment_text = isset($payload['comment_text']) ? sanitize_textarea_field(wp_unslash((string) $payload['comment_text'])) : '';
    $test_case_title = isset($payload['test_case_title']) ? sanitize_text_field(wp_unslash((string) $payload['test_case_title'])) : '';
    $page_url = isset($payload['page_url']) ? esc_url_raw(wp_unslash((string) $payload['page_url'])) : '';
    $screenshot_url = isset($payload['screenshot_url']) ? esc_url_raw(wp_unslash((string) $payload['screenshot_url'])) : '';
    $status = isset($payload['status']) ? papetarie_storefront_normalize_checkout_test_comment_status(sanitize_text_field(wp_unslash((string) $payload['status']))) : 'open';
    $comment_id = isset($payload['comment_id']) ? absint($payload['comment_id']) : 0;

    if ($case_id === '') {
        return new WP_Error('missing_case_id', __('Lipsește identificatorul cazului.', 'papetarie-storefront'));
    }

    if ($comment_text === '') {
        return new WP_Error('missing_comment', __('Comentariul nu poate fi gol.', 'papetarie-storefront'));
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user instanceof WP_User && $current_user->exists() ? (int) $current_user->ID : 0;
    $now_gmt = current_time('mysql', true);
    $now_local = current_time('mysql');
    $environment = papetarie_storefront_get_checkout_test_comment_environment();

    $post_data = [
        'post_type' => papetarie_storefront_get_checkout_test_comment_post_type(),
        'post_status' => 'publish',
        'post_title' => sprintf('%s | %s | %s', $case_id, $status, wp_trim_words(wp_strip_all_tags($comment_text), 8, '…')),
        'post_content' => $comment_text,
        'post_author' => $user_id,
        'post_date' => $now_local,
        'post_date_gmt' => $now_gmt,
    ];

    if ($comment_id > 0 && get_post_type($comment_id) === papetarie_storefront_get_checkout_test_comment_post_type()) {
        $post_data['ID'] = $comment_id;
        $result = wp_update_post($post_data, true);
        if (is_wp_error($result)) {
            return $result;
        }
        $post_id = (int) $result;
        $created_at = (string) get_post_meta($post_id, 'created_at', true);
        if ($created_at === '') {
            $created_at = $now_local;
        }
    } else {
        $result = wp_insert_post($post_data, true);
        if (is_wp_error($result)) {
            return $result;
        }
        $post_id = (int) $result;
        $created_at = $now_local;
    }

    update_post_meta($post_id, 'test_case_id', $case_id);
    update_post_meta($post_id, 'test_case_title', $test_case_title);
    update_post_meta($post_id, 'comment_text', $comment_text);
    update_post_meta($post_id, 'environment', $environment);
    update_post_meta($post_id, 'page_url', $page_url);
    update_post_meta($post_id, 'user_id', $user_id);
    update_post_meta($post_id, 'status', $status);
    update_post_meta($post_id, 'screenshot_url', $screenshot_url);
    update_post_meta($post_id, 'created_at', $created_at);
    update_post_meta($post_id, 'updated_at', $now_local);

    return $post_id;
}

function papetarie_storefront_migrate_legacy_checkout_case_comments(): void
{
    if (get_option('pap_checkout_case_comments_migrated', false)) {
        return;
    }

    $legacy_comments = get_option('pap_checkout_case_comments', []);
    if (!is_array($legacy_comments) || empty($legacy_comments)) {
        update_option('pap_checkout_case_comments_migrated', 1, false);
        return;
    }

    $existing_index = papetarie_storefront_get_checkout_test_comment_index();
    $imported_count = 0;
    $failed = false;

    foreach ($legacy_comments as $case_id => $comment_data) {
        $case_id = sanitize_text_field((string) $case_id);
        $comment_text = '';

        if (is_array($comment_data)) {
            $comment_text = isset($comment_data['comment']) ? (string) $comment_data['comment'] : '';
        } elseif (is_string($comment_data)) {
            $comment_text = $comment_data;
        }

        $comment_text = trim(wp_strip_all_tags($comment_text));
        if ($case_id === '' || $comment_text === '') {
            continue;
        }

        $already_imported = false;
        if (isset($existing_index[$case_id])) {
            foreach ($existing_index[$case_id]['comments'] as $existing_comment) {
                if (trim((string) ($existing_comment['comment_text'] ?? '')) === $comment_text) {
                    $already_imported = true;
                    break;
                }
            }
        }

        if ($already_imported) {
            continue;
        }

        $result = papetarie_storefront_insert_checkout_test_comment([
            'test_case_id' => $case_id,
            'test_case_title' => '',
            'comment_text' => $comment_text,
            'status' => 'open',
            'page_url' => home_url('/checkout-test-cases/'),
        ]);

        if (is_wp_error($result)) {
            $failed = true;
            break;
        }

        $imported_count++;
    }

    if (!$failed) {
        delete_option('pap_checkout_case_comments');
        update_option('pap_checkout_case_comments_migrated', 1, false);
    } elseif ($imported_count > 0) {
        update_option('pap_checkout_case_comments_migrated', 1, false);
    }
}
add_action('init', 'papetarie_storefront_migrate_legacy_checkout_case_comments', 20);

function papetarie_storefront_get_checkout_case_comments(): array
{
    $index = papetarie_storefront_get_checkout_test_comment_index();
    $comments = [];

    foreach ($index as $case_id => $entry) {
        if (empty($entry['comments'])) {
            continue;
        }

        $comments[$case_id] = [
            'comment' => (string) ($entry['latest_comment_text'] ?? ''),
            'updated_at' => isset($entry['latest_comment']['updated_at']) ? (string) $entry['latest_comment']['updated_at'] : '',
            'status' => (string) ($entry['latest_status'] ?? 'open'),
            'count' => (int) ($entry['total_count'] ?? 0),
        ];
    }

    return $comments;
}

function papetarie_storefront_save_checkout_case_comments(array $comments): bool
{
    $saved = true;

    foreach ($comments as $case_id => $comment_data) {
        $comment_text = '';
        $status = 'open';
        $comment_id = 0;
        $test_case_title = '';
        $page_url = home_url('/checkout-test-cases/');

        if (is_array($comment_data)) {
            $comment_text = isset($comment_data['comment']) ? (string) $comment_data['comment'] : '';
            $status = isset($comment_data['status']) ? (string) $comment_data['status'] : 'open';
            $comment_id = isset($comment_data['comment_id']) ? absint($comment_data['comment_id']) : 0;
            $test_case_title = isset($comment_data['test_case_title']) ? (string) $comment_data['test_case_title'] : '';
            $page_url = isset($comment_data['page_url']) ? (string) $comment_data['page_url'] : $page_url;
        } elseif (is_string($comment_data)) {
            $comment_text = $comment_data;
        }

        $result = papetarie_storefront_insert_checkout_test_comment([
            'test_case_id' => (string) $case_id,
            'test_case_title' => $test_case_title,
            'comment_text' => $comment_text,
            'status' => $status,
            'comment_id' => $comment_id,
            'page_url' => $page_url,
        ]);

        if (is_wp_error($result)) {
            $saved = false;
        }
    }

    return $saved;
}

function papetarie_storefront_handle_checkout_case_comment_save(): void
{
    if (!function_exists('wp_send_json_error')) {
        return;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_checkout_case_comments')) {
        wp_send_json_error([
            'message' => __('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'),
        ], 400);
    }

    $result = papetarie_storefront_insert_checkout_test_comment([
        'test_case_id' => isset($_POST['case_id']) ? (string) wp_unslash($_POST['case_id']) : '',
        'test_case_title' => isset($_POST['test_case_title']) ? (string) wp_unslash($_POST['test_case_title']) : '',
        'comment_text' => isset($_POST['comment']) ? (string) wp_unslash($_POST['comment']) : '',
        'status' => isset($_POST['status']) ? (string) wp_unslash($_POST['status']) : 'open',
        'comment_id' => isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0,
        'page_url' => isset($_POST['page_url']) ? (string) wp_unslash($_POST['page_url']) : home_url('/checkout-test-cases/'),
        'screenshot_url' => isset($_POST['screenshot_url']) ? (string) wp_unslash($_POST['screenshot_url']) : '',
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message(),
        ], 400);
    }

    wp_send_json_success([
        'comments' => papetarie_storefront_get_checkout_test_comment_index(),
        'saved_comment_id' => (int) $result,
    ]);
}

add_action('wp_ajax_pap_checkout_case_save_comment', 'papetarie_storefront_handle_checkout_case_comment_save');
add_action('wp_ajax_nopriv_pap_checkout_case_save_comment', 'papetarie_storefront_handle_checkout_case_comment_save');

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
      <?php papetarie_storefront_render_return_notices(); ?>

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

function papetarie_storefront_account_orders_query(array $args): array
{
    if (!function_exists('is_account_page') || !is_account_page()) {
        return $args;
    }

    $args['limit'] = 5;
    $args['paginate'] = true;
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';
    $allowed_statuses = function_exists('papetarie_storefront_account_real_order_statuses')
        ? papetarie_storefront_account_real_order_statuses()
        : [];

    $status = isset($_GET['order_status']) ? sanitize_key(wp_unslash($_GET['order_status'])) : 'all';
    if ($status !== 'all' && in_array($status, $allowed_statuses, true)) {
        $args['status'] = [$status];
    } else {
        $args['status'] = $allowed_statuses;
    }

    $period = isset($_GET['order_period']) ? sanitize_key(wp_unslash($_GET['order_period'])) : 'all';
    $period_map = [
        '30d' => '-30 days',
        '90d' => '-90 days',
        '180d' => '-180 days',
        '365d' => '-365 days',
    ];

    if (isset($period_map[$period])) {
        $args['date_created'] = '>' . gmdate('Y-m-d H:i:s', strtotime($period_map[$period]));
    }

    $search = isset($_GET['order_search']) ? trim((string) wp_unslash($_GET['order_search'])) : '';
    if ($search !== '') {
        $matching_order_ids = [];
        $candidate_ids = papetarie_storefront_account_customer_orders(get_current_user_id(), [
            'limit' => -1,
            'return' => 'ids',
        ]);

        foreach ($candidate_ids as $order_id) {
            $order = wc_get_order((int) $order_id);
            if (!$order instanceof WC_Order) {
                continue;
            }

            $display_number = papetarie_storefront_account_order_display_number($order);
            $raw_number = (string) $order->get_order_number();

            if (stripos($display_number, $search) !== false || stripos($raw_number, $search) !== false) {
                $matching_order_ids[] = (int) $order_id;
            }
        }

        $args['include'] = $matching_order_ids ?: [0];
    }

    return $args;
}
add_filter('woocommerce_my_account_my_orders_query', 'papetarie_storefront_account_orders_query');
