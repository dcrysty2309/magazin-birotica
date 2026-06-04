<?php

defined('ABSPATH') || exit;

$queried_object = get_queried_object();
$is_product_category = is_tax('product_cat') && $queried_object instanceof WP_Term;
$current_term = $is_product_category ? $queried_object : null;
$term_id = $current_term ? (int) $current_term->term_id : 0;
$term_slug = $current_term ? (string) $current_term->slug : '';
$archive_title = $current_term ? $current_term->name : (function_exists('woocommerce_page_title') ? woocommerce_page_title(false) : __('Produse', 'papetarie-storefront'));
$archive_description = $current_term ? wp_strip_all_tags((string) term_description($term_id, 'product_cat')) : '';
$query = $GLOBALS['wp_query'] ?? null;
$product_count = $query instanceof WP_Query ? (int) $query->found_posts : 0;
$is_empty_archive = $product_count === 0;
$archive_action_url = $current_term ? get_term_link($current_term, 'product_cat') : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'));

if (is_wp_error($archive_action_url) || !$archive_action_url) {
    $archive_action_url = home_url('/');
}

$price_bounds = function_exists('papetarie_storefront_get_archive_price_bounds') ? papetarie_storefront_get_archive_price_bounds($current_term) : ['min' => 0, 'max' => 0];
$price_min_bound = isset($price_bounds['min']) ? (float) $price_bounds['min'] : 0.0;
$price_max_bound = isset($price_bounds['max']) ? (float) $price_bounds['max'] : 0.0;

if ($price_max_bound < $price_min_bound) {
    $price_max_bound = $price_min_bound;
}

$current_min_price = isset($_GET['min_price']) ? (float) wc_format_decimal(wp_unslash($_GET['min_price'])) : $price_min_bound;
$current_max_price = isset($_GET['max_price']) ? (float) wc_format_decimal(wp_unslash($_GET['max_price'])) : $price_max_bound;
$current_min_price = max($price_min_bound, min($current_min_price, $price_max_bound));
$current_max_price = max($current_min_price, min($current_max_price, $price_max_bound));
$current_stock_status = isset($_GET['stock_status']) ? sanitize_key(wp_unslash($_GET['stock_status'])) : 'all';
$stock_status_options = function_exists('papetarie_storefront_stock_status_options') ? papetarie_storefront_stock_status_options() : [];
$current_orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';

$categories_tree = papetarie_storefront_get_mega_menu_categories();
$current_parent_category = null;
$current_child_terms = [];

foreach ($categories_tree as $category) {
    if ($current_term && ((int) $category['term_id'] === $term_id)) {
        $current_parent_category = $category;
        $current_child_terms = $category['children'];
        break;
    }

    foreach ($category['children'] as $child) {
        if ($current_term && ((int) $child['term_id'] === $term_id)) {
            $current_parent_category = $category;
            $current_child_terms = $category['children'];
            break 2;
        }
    }
}

$cover_image_id = $current_term ? (int) get_term_meta($term_id, 'thumbnail_id', true) : 0;
$archive_cover_image_url = '';

if ($cover_image_id > 0) {
    $archive_cover_image_url = (string) wp_get_attachment_image_url($cover_image_id, 'full');
}

if ($archive_cover_image_url === '') {
    $archive_cover_image_url = get_stylesheet_directory_uri() . '/assets/images/category-cover-fallback.png';
}

get_header();
?>
<main id="primary" class="site-main pap-archive-page">
  <div class="pap-shell pap-page-breadcrumbs pap-archive-breadcrumbs">
    <?php
    if (function_exists('woocommerce_breadcrumb')) {
        woocommerce_breadcrumb([
            'delimiter' => '<span class="pap-breadcrumb-delimiter" aria-hidden="true">/</span>',
            'wrap_before' => '<nav class="woocommerce-breadcrumb pap-breadcrumbs-nav">',
            'wrap_after' => '</nav>',
        ]);
    }
    ?>
  </div>

  <section class="pap-shell pap-archive-hero">
    <div class="pap-archive-hero-inner pap-archive-hero--cover" style="<?php echo esc_attr('--pap-archive-hero-image: url(' . $archive_cover_image_url . ');'); ?>">
      <div class="pap-archive-hero-copy">
        <h1><?php echo esc_html($archive_title); ?></h1>
        <p class="pap-archive-description">
          <?php
          if ($archive_description) {
              echo esc_html($archive_description);
          } else {
              esc_html_e('Selecția noastră de produse pentru această categorie.', 'papetarie-storefront');
          }
          ?>
        </p>
      </div>
    </div>
  </section>

  <section class="pap-shell pap-archive-layout">
    <aside class="pap-archive-sidebar">
      <div class="pap-archive-sidebar-box">
        <?php if (!$is_empty_archive) : ?>
          <div class="pap-archive-sidebar-filters">
            <div class="pap-archive-sidebar-head">
              <h2><?php esc_html_e('Filtre', 'papetarie-storefront'); ?></h2>
            </div>

            <form class="pap-archive-filter-form" method="get" action="<?php echo esc_url($archive_action_url); ?>">
              <?php if ($current_orderby !== '') : ?>
                <input type="hidden" name="orderby" value="<?php echo esc_attr($current_orderby); ?>">
              <?php endif; ?>
              <input type="hidden" name="paged" value="1">

              <div class="pap-archive-filter-group">
                <label><?php esc_html_e('Preț', 'papetarie-storefront'); ?></label>
                <div
                  class="pap-archive-price-slider"
                  data-currency="<?php echo esc_attr(get_woocommerce_currency()); ?>"
                  data-min-bound="<?php echo esc_attr($price_min_bound); ?>"
                  data-max-bound="<?php echo esc_attr($price_max_bound); ?>"
                >
                  <div class="pap-archive-price-slider-values">
                    <span data-price-min-value><?php echo wp_kses_post(wc_price($current_min_price)); ?></span>
                    <span data-price-max-value><?php echo wp_kses_post(wc_price($current_max_price)); ?></span>
                  </div>
                  <div class="pap-archive-price-slider-track">
                    <span class="pap-archive-price-slider-rail" aria-hidden="true"></span>
                    <span class="pap-archive-price-slider-fill" aria-hidden="true"></span>
                    <input
                      type="range"
                      name="min_price"
                      min="<?php echo esc_attr($price_min_bound); ?>"
                      max="<?php echo esc_attr($price_max_bound); ?>"
                      step="1"
                      value="<?php echo esc_attr($current_min_price); ?>"
                      data-price-slider="min"
                      aria-label="<?php esc_attr_e('Preț minim', 'papetarie-storefront'); ?>"
                    >
                    <input
                      type="range"
                      name="max_price"
                      min="<?php echo esc_attr($price_min_bound); ?>"
                      max="<?php echo esc_attr($price_max_bound); ?>"
                      step="1"
                      value="<?php echo esc_attr($current_max_price); ?>"
                      data-price-slider="max"
                      aria-label="<?php esc_attr_e('Preț maxim', 'papetarie-storefront'); ?>"
                    >
                  </div>
                </div>
              </div>

              <div class="pap-archive-filter-group">
                <label for="pap-filter-stock-status"><?php esc_html_e('Disponibilitate', 'papetarie-storefront'); ?></label>
                <select id="pap-filter-stock-status" name="stock_status">
                  <option value="all"<?php selected($current_stock_status, 'all'); ?>><?php esc_html_e('Toate', 'papetarie-storefront'); ?></option>
                  <?php foreach ($stock_status_options as $stock_status_key => $stock_status_label) : ?>
                    <option value="<?php echo esc_attr($stock_status_key); ?>"<?php selected($current_stock_status, $stock_status_key); ?>>
                      <?php echo esc_html($stock_status_label); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="pap-archive-filter-actions">
                <button type="submit"><?php esc_html_e('Aplică filtrele', 'papetarie-storefront'); ?></button>
                <a href="<?php echo esc_url($archive_action_url); ?>"><?php esc_html_e('Resetează', 'papetarie-storefront'); ?></a>
              </div>
            </form>
          </div>
        <?php else : ?>
          <div class="pap-archive-sidebar-empty">
            <div class="pap-archive-sidebar-empty-panel">
              <span class="pap-archive-sidebar-empty-icon" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
                  <circle cx="24" cy="24" r="24" fill="#EEF5FF"/>
                  <path d="M13 16.5C13 15.67 13.67 15 14.5 15H33.5C34.33 15 35 15.67 35 16.5C35 16.86 34.87 17.2 34.64 17.47L27 26.5V34.2C27 34.72 26.7 35.2 26.23 35.43L22.23 37.43C21.57 37.76 20.8 37.28 20.8 36.55V26.5L13.36 17.47C13.13 17.2 13 16.86 13 16.5Z" stroke="#0B3A78" stroke-width="2" stroke-linejoin="round"/>
                </svg>
              </span>
              <h2><?php esc_html_e('Produse în pregătire', 'papetarie-storefront'); ?></h2>
              <p><?php esc_html_e('Selecția acestei categorii este în curs de actualizare. Revino curând pentru produse alese cu grijă.', 'papetarie-storefront'); ?></p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <div class="pap-archive-content" id="pap-archive-products">
      <?php if (woocommerce_product_loop()) : ?>
        <div class="pap-archive-toolbar">
          <div class="pap-archive-toolbar-ordering">
            <?php if (function_exists('woocommerce_catalog_ordering')) : ?>
              <?php woocommerce_catalog_ordering(); ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="pap-archive-grid">
          <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <?php
            global $product;

            if (!$product instanceof WC_Product) {
                $product = wc_get_product(get_the_ID());
            }

            if (!$product instanceof WC_Product) {
                continue;
            }

            $product_name = $product->get_name();
            $product_url = $product->get_permalink();
            $product_image_id = $product->get_image_id();
            $product_subtitle = wp_strip_all_tags($product->get_short_description());

            if ($product_subtitle === '') {
                $product_subtitle = wp_strip_all_tags($product->get_attribute('pa_subtitlu'));
            }

            if ($product_subtitle === '') {
                $product_subtitle = wp_strip_all_tags($product->get_attribute('subtitlu'));
            }

            if ($product_subtitle === '') {
                $product_subtitle = __('Produs util pentru birou și școală.', 'papetarie-storefront');
            }

            $product_subtitle = wp_trim_words($product_subtitle, 9, '');

            if ($product_image_id) {
                $product_image = wp_get_attachment_image($product_image_id, 'woocommerce_thumbnail', false, [
                    'loading' => 'lazy',
                    'alt' => $product_name,
                ]);
            } else {
                $product_image = '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
            }

            $can_add_to_cart = $product->is_purchasable() && $product->is_in_stock();
            $action_url = $can_add_to_cart ? $product->add_to_cart_url() : $product_url;
            $action_text = $can_add_to_cart ? $product->add_to_cart_text() : __('Vezi produsul', 'papetarie-storefront');
            $action_class = $can_add_to_cart && $product->is_type('simple') ? 'add_to_cart_button ajax_add_to_cart' : '';
            ?>
            <article class="pap-product-card pap-product-card--archive">
              <a class="pap-product-card-link" href="<?php echo esc_url($product_url); ?>">
                <div class="pap-product-thumb pap-product-thumb--archive">
                  <?php echo $product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <div class="pap-product-copy">
                  <h2><?php echo esc_html($product_name); ?></h2>
                  <p><?php echo esc_html($product_subtitle); ?></p>
                </div>
              </a>
              <div class="pap-product-meta pap-product-meta--archive">
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
                    <span class="pap-product-action-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('cart'); ?></span>
                  </a>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <div class="pap-archive-pagination">
          <?php woocommerce_pagination(); ?>
        </div>
      <?php else : ?>
        <div class="pap-archive-empty">
          <span class="pap-archive-empty-icon" aria-hidden="true">
            <svg width="96" height="96" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
              <circle cx="48" cy="48" r="48" fill="#EDF4FB"/>
              <path d="M25 39.5L48 27L71 39.5L48 52L25 39.5Z" stroke="#0B3A75" stroke-width="3" stroke-linejoin="round"/>
              <path d="M25 39.5V60.5L48 73L71 60.5V39.5" stroke="#0B3A75" stroke-width="3" stroke-linejoin="round"/>
              <path d="M48 52V73" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M35.5 33L58.5 45.5" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M60.5 33L37.5 45.5" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M48 16V21" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
              <path d="M36 20L38.5 24.5" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
              <path d="M60 20L57.5 24.5" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
            </svg>
          </span>
          <h2><?php esc_html_e('Nu există produse în această categorie încă', 'papetarie-storefront'); ?></h2>
          <p><?php esc_html_e('Această categorie este în pregătire. Revino curând pentru produse alese cu grijă.', 'papetarie-storefront'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();
