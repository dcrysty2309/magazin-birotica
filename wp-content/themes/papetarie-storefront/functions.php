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

function papetarie_storefront_body_class(array $classes): array
{
    $classes[] = 'theme-papetarie';

    return $classes;
}
add_filter('body_class', 'papetarie_storefront_body_class');

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

function papetarie_storefront_icon(string $name): string
{
    $icons = [
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 015.98 12.03l4.25 4.24-1.42 1.42-4.24-4.25A7.5 7.5 0 1110.5 3zm0 2a5.5 5.5 0 100 11 5.5 5.5 0 000-11z" fill="currentColor"/></svg>',
        'account' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.14 0-7.5 2.69-7.5 6v1h15v-1c0-3.31-3.36-6-7.5-6z" fill="currentColor"/></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l4 4h-3v7h-2V7H8l4-4zm-7 12h14v6H5v-6zm2 2v2h10v-2H7z" fill="currentColor"/></svg>',
        'cart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2.2l2.1 10.5A2 2 0 0 0 9.3 16h7.9a2 2 0 0 0 2-1.6l1.3-7.4H6.2" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.5 20a1.1 1.1 0 1 0 0-.01M17.5 20a1.1 1.1 0 1 0 0-.01" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v2H4V7zm0 4h16v2H4v-2zm0 4h16v2H4v-2z" fill="currentColor"/></svg>',
        'chevron' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.59 16.59 13.17 12 8.59 7.41 10 6l6 6-6 6z" fill="currentColor"/></svg>',
        'help' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 17a1.25 1.25 0 110-2.5A1.25 1.25 0 0112 19zm1.33-5.94-.58.33c-.94.53-1.25.98-1.25 1.86h-2c0-1.67.79-2.67 2.27-3.5l.76-.43c.78-.44 1.22-1.02 1.22-1.76 0-1.16-.95-1.92-2.39-1.92-1.31 0-2.31.57-3.18 1.64L6.6 7.99C7.77 6.43 9.5 5.5 11.73 5.5c2.77 0 4.85 1.57 4.85 4.1 0 1.5-.75 2.67-3.25 3.46z" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l7 3v6c0 4.97-3.06 9.63-7 11-3.94-1.37-7-6.03-7-11V5l7-3zm-1 13l5-5-1.41-1.41L11 12.17l-1.59-1.58L8 12l3 3z" fill="currentColor"/></svg>',
        'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.59 13.41L11 3.83V3H4v7h.83l9.58 9.59a2 2 0 002.83 0l3.35-3.35a2 2 0 000-2.83zM6.5 8A1.5 1.5 0 118 6.5 1.5 1.5 0 016.5 8z" fill="currentColor"/></svg>',
        'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h11v9h2.17a3 3 0 015.66 1H23v2h-1a3 3 0 11-6 0H9a3 3 0 11-6 0H2v-2h1V5zm13 2v5h3.59L18.09 9H16z" fill="currentColor"/></svg>',
        'truck-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 7.5h9.5V15H3.5V7.5Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M13 10h2.2l2.8 2.8V15H13V10Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M17.5 19a1.4 1.4 0 1 0 0-.01Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 17.2h1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M15.2 12.2h2.1L20.1 15v1.1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'lock-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 11V8.8a4.5 4.5 0 1 1 9 0V11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><rect x="5.5" y="11" width="13" height="10" rx="1.8" ry="1.8" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M12 15.2v2.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'headset-outline' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12a6 6 0 0 1 12 0v5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M4.5 13.2v3a2 2 0 0 0 2 2H8v-7H6.5a2 2 0 0 0-2 2Zm15 0v3a2 2 0 0 1-2 2H16v-7h1.5a2 2 0 0 1 2 2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 19.5h4" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        'pen' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 17.25 10.58-10.59 3.76 3.76L6.75 21H3v-3.75Zm12-9.66 1.41-1.42a2 2 0 0 1 2.83 0l.17.17a2 2 0 0 1 0 2.83L18 10.59 15 7.59Z" fill="currentColor"/></svg>',
        'paper' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm6 1.5V9h4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 13h6M9 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
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

    return array_values(array_filter($items, static fn (array $item): bool => !empty($item['children'])));
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
              <?php if (!empty($category['children'])) : ?>
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
              <?php else : ?>
                <div class="<?php echo esc_attr($panel_empty_class); ?>">
                  <strong><?php esc_html_e('Categoria este în curs de populare', 'papetarie-storefront'); ?></strong>
                  <span><?php esc_html_e('Vom adăuga în scurt timp subcategorii și produse relevante aici.', 'papetarie-storefront'); ?></span>
                </div>
              <?php endif; ?>
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
                aria-controls="pap-header-catmenu-panel-<?php echo esc_attr($category['slug']); ?>"
                aria-expanded="<?php echo $category['slug'] === $active_slug ? 'true' : 'false'; ?>"
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
              <section
                class="pap-header-catmenu-panel<?php echo $category['slug'] === $active_slug ? ' is-active' : ''; ?>"
                data-header-catmenu-panel="<?php echo esc_attr($category['slug']); ?>"
                id="pap-header-catmenu-panel-<?php echo esc_attr($category['slug']); ?>"
                <?php echo $category['slug'] === $active_slug ? '' : 'hidden'; ?>
              >
                <div class="pap-header-catmenu-panel-title"><?php echo esc_html($category['name']); ?></div>
                <?php if (!empty($category['children'])) : ?>
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
                <?php else : ?>
                  <div class="pap-header-catmenu-empty">
                    <strong><?php esc_html_e('Categoria este în curs de populare', 'papetarie-storefront'); ?></strong>
                  </div>
                <?php endif; ?>
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

    $added = WC()->cart->add_to_cart($product_id, $quantity);

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

    wp_send_json_success([
        'message' => __('Produsul a fost adăugat în coș', 'papetarie-storefront'),
        'name' => $product->get_name(),
        'price_html' => $product->get_price_html(),
        'cart_url' => wc_get_cart_url(),
        'image_url' => $image_url,
        'cart_count' => WC()->cart->get_cart_contents_count(),
    ]);
}
add_action('wp_ajax_pap_home_add_to_cart', 'papetarie_storefront_ajax_add_to_cart');
add_action('wp_ajax_nopriv_pap_home_add_to_cart', 'papetarie_storefront_ajax_add_to_cart');
