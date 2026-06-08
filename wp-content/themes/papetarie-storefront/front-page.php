<?php

defined('ABSPATH') || exit;

$asset_base = get_stylesheet_directory_uri() . '/assets/images';
$showcase_categories = papetarie_storefront_get_mega_menu_categories();
$showcase_active_slug = papetarie_storefront_active_mega_menu_slug($showcase_categories);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

$showcase_slides = [
    [
        'image' => $asset_base . '/showcase-hero-user.png',
    ],
    [
        'image' => $asset_base . '/showcase-hero-user-2.png',
    ],
    [
        'image' => $asset_base . '/showcase-hero-user.png',
    ],
];

$showcase_category_images = [
    'instrumente-de-scris-si-corectura' => $asset_base . '/showcase-slide-1-stationery.png',
    'articole-din-hartie' => $asset_base . '/showcase-slide-1-stationery.png',
    'arhivare' => $asset_base . '/showcase-slide-3-organization.png',
    'organizare' => $asset_base . '/showcase-slide-3-organization.png',
    'accesorii-pentru-birou' => $asset_base . '/showcase-hero-user.png',
    'articole-scolare' => $asset_base . '/showcase-slide-2-school.png',
    'consumabile-si-indosariere' => $asset_base . '/showcase-slide-3-organization.png',
    'sisteme-de-prezentare-si-afisare' => $asset_base . '/showcase-hero-user-2.png',
    'accesorii-it' => $asset_base . '/showcase-hero-user-2.png',
    'echipamente-birou' => $asset_base . '/showcase-hero-user-2.png',
    'capsatoare-si-perforatoare' => $asset_base . '/showcase-hero-user.png',
];

$showcase_category_positions = [
    'instrumente-de-scris-si-corectura' => 'center center',
    'articole-din-hartie' => 'center center',
    'arhivare' => 'center center',
    'organizare' => 'center center',
    'accesorii-pentru-birou' => '72% center',
    'articole-scolare' => 'center center',
    'consumabile-si-indosariere' => 'center center',
    'sisteme-de-prezentare-si-afisare' => '60% center',
    'accesorii-it' => '68% center',
    'echipamente-birou' => '68% center',
    'capsatoare-si-perforatoare' => '70% center',
];

$featured_product_images = [
    584 => $asset_base . '/product-notebook-a5.png',
    586 => $asset_base . '/product-pens-blue.png',
    588 => $asset_base . '/product-calculator.png',
    589 => $asset_base . '/product-binders-a4.png',
    591 => $asset_base . '/product-sticky-notes.png',
    593 => $asset_base . '/product-mesh-organizer.png',
    595 => $asset_base . '/product-highlighters.png',
    597 => $asset_base . '/product-scissors.png',
    598 => $asset_base . '/product-clipboard.png',
    599 => $asset_base . '/product-correction-tape.png',
    600 => $asset_base . '/product-paper-ream.png',
    601 => $asset_base . '/product-glue-stick.png',
];

$products = function_exists('wc_get_products') ? wc_get_products([
    'status' => 'publish',
    'limit' => 12,
    'featured' => true,
    'orderby' => 'menu_order',
    'order' => 'ASC',
]) : [];

$offer_products = [];
if (function_exists('wc_get_products') && function_exists('wc_get_product_ids_on_sale')) {
    $sale_product_ids = wc_get_product_ids_on_sale();
    if (!empty($sale_product_ids)) {
        $sale_products = wc_get_products([
            'status' => 'publish',
            'limit' => 8,
            'include' => $sale_product_ids,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($sale_products as $product) {
            if (!$product instanceof WC_Product) {
                continue;
            }

            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            if ($regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price) {
                continue;
            }

            $discount = (int) round((1 - ($sale_price / $regular_price)) * 100);
            if ($discount <= 0) {
                continue;
            }

            $product_name = $product->get_name();
            $product_id = $product->get_id();
            $product_url = $product->get_permalink();
            $product_image_id = $product->get_image_id();
            if (isset($featured_product_images[$product_id])) {
                $product_image = '<img src="' . esc_url($featured_product_images[$product_id]) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
            } else {
                $product_image = $product_image_id
                    ? wp_get_attachment_image($product_image_id, 'medium', false, ['loading' => 'lazy', 'alt' => $product_name])
                    : '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
            }

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
                $product_subtitle = __('Produs în ofertă pentru birou și școală', 'papetarie-storefront');
            }

            $offer_products[] = [
                'id' => $product_id,
                'name' => $product_name,
                'url' => $product_url,
                'image' => $product_image,
                'subtitle' => wp_trim_words($product_subtitle, 8, ''),
                'price_html' => wc_price($sale_price),
                'old_price_html' => wc_price($regular_price),
                'discount' => $discount,
            ];
        }
    }
}

if (empty($offer_products) && function_exists('wc_get_products')) {
    $fallback_offer_products = wc_get_products([
        'status' => 'publish',
        'limit' => 8,
        'featured' => true,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ]);

    if (empty($fallback_offer_products)) {
        $fallback_offer_products = wc_get_products([
            'status' => 'publish',
            'limit' => 8,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    foreach ($fallback_offer_products as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }

        $regular_price = (float) $product->get_regular_price();
        if ($regular_price <= 0) {
            $regular_price = (float) $product->get_price();
        }
        if ($regular_price <= 0) {
            continue;
        }

        $product_name = $product->get_name();
        $product_id = $product->get_id();
        $product_url = $product->get_permalink();
        $product_image_id = $product->get_image_id();
        if (isset($featured_product_images[$product_id])) {
            $product_image = '<img src="' . esc_url($featured_product_images[$product_id]) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
        } else {
            $product_image = $product_image_id
                ? wp_get_attachment_image($product_image_id, 'medium', false, ['loading' => 'lazy', 'alt' => $product_name])
                : '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
        }

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
            $product_subtitle = __('Produs pentru birou și școală', 'papetarie-storefront');
        }

        $offer_products[] = [
            'id' => $product_id,
            'name' => $product_name,
            'url' => $product_url,
            'image' => $product_image,
            'subtitle' => wp_trim_words($product_subtitle, 8, ''),
            'price_html' => wc_price($regular_price * 0.8),
            'old_price_html' => wc_price($regular_price),
            'discount' => 20,
        ];
    }
}

$trust_features = [
    [
        'icon' => 'truck-outline',
        'title' => 'Livrare rapida',
        'copy' => 'In 24-48h oriunde in tara',
    ],
    [
        'icon' => 'truck-outline',
        'title' => 'Transport gratuit',
        'copy' => 'La comenzi de la 300 RON',
    ],
    [
        'icon' => 'lock-outline',
        'title' => 'Plata securizata',
        'copy' => '100% sigur si protejat',
    ],
    [
        'icon' => 'headset-outline',
        'title' => 'Suport dedicat',
        'copy' => 'Suntem aici sa te ajutam',
    ],
];

$package_offers = [
    [
        'slug' => 'office',
        'title' => 'Kit birou esential',
        'items' => ['5 caiete A4', '10 pixuri albastre', 'Sticky notes', 'Corector banda'],
        'price' => '39.90 lei',
        'old_price' => '64.60 lei',
        'image' => $asset_base . '/package-office-photo.png',
    ],
    [
        'slug' => 'student',
        'title' => 'Kit elev esential',
        'items' => ['3 caiete A4', '12 markere colorate', 'Penar echipat', 'Lipici solid'],
        'price' => '59.90 lei',
        'old_price' => '78.40 lei',
        'image' => $asset_base . '/package-student-photo.png',
    ],
    [
        'slug' => 'archive',
        'title' => 'Kit arhivare',
        'items' => ['4 bibliorafturi', 'Separatoare color', 'Etichete autoadezive', 'Folii protectoare'],
        'price' => '69.90 lei',
        'old_price' => '92.60 lei',
        'image' => $asset_base . '/package-archive-photo.png',
    ],
];

get_header();
?>
<main id="primary" class="site-main pap-homepage">
  <section class="pap-showcase" data-showcase>
    <div class="pap-shell pap-showcase-grid">
      <aside class="pap-showcase-nav" aria-label="<?php esc_attr_e('Categorii produse', 'papetarie-storefront'); ?>">
        <div class="pap-showcase-nav-list">
          <?php foreach ($showcase_categories as $category) : ?>
            <a
              class="pap-showcase-nav-item<?php echo $category['slug'] === $showcase_active_slug && !empty($category['children']) ? ' is-active' : ''; ?>"
              href="<?php echo esc_url($category['url']); ?>"
              data-showcase-tab="<?php echo esc_attr($category['slug']); ?>"
              data-showcase-has-children="<?php echo !empty($category['children']) ? '1' : '0'; ?>"
              title="<?php echo esc_attr($category['name']); ?>"
            >
              <span class="pap-showcase-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($category['icon']); ?></span>
              <span class="pap-showcase-nav-label"><?php echo esc_html(papetarie_storefront_short_category_name($category['slug'], $category['name'])); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>

      <div class="pap-showcase-stage" data-showcase-stage>
        <div class="pap-showcase-slides">
          <?php foreach ($showcase_slides as $index => $slide) : ?>
            <article class="pap-showcase-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-showcase-slide="<?php echo esc_attr((string) $index); ?>">
              <div class="pap-showcase-slide-visual" aria-hidden="true">
                <img class="pap-showcase-visual-banner" src="<?php echo esc_url($slide['image']); ?>" alt="" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="pap-showcase-dots" aria-hidden="true">
          <?php foreach ($showcase_slides as $index => $slide) : ?>
            <button class="pap-showcase-dot<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-showcase-dot="<?php echo esc_attr((string) $index); ?>">
              <span class="screen-reader-text"><?php echo esc_html(sprintf(__('Slide %s', 'papetarie-storefront'), $index + 1)); ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="pap-showcase-panels">
          <?php papetarie_storefront_render_mega_menu_panels(
              $showcase_categories,
              $showcase_active_slug,
              [
                  'nav_aria_label' => __('Categorii produse', 'papetarie-storefront'),
                  'panel_data_attr' => 'data-showcase-panel',
                  'panel_include_id' => false,
              ]
          ); ?>
          <?php if (false) : ?>
          <?php foreach ($showcase_categories as $category) : ?>
            <section class="pap-showcase-panel<?php echo $category['slug'] === $showcase_active_slug ? ' is-active' : ''; ?>" data-showcase-panel="<?php echo esc_attr($category['slug']); ?>" <?php echo $category['slug'] === $showcase_active_slug ? '' : 'hidden'; ?>>
              <div class="pap-showcase-panel-layout">
                <div class="pap-showcase-panel-copy">
                  <div class="pap-showcase-panel-title"><?php echo esc_html($category['name']); ?></div>
                  <div class="pap-showcase-panel-columns">
                  <?php if ($category['children']) : ?>
                    <?php foreach ($category['children'] as $child) : ?>
                      <div class="pap-showcase-panel-group">
                        <a class="pap-showcase-panel-group-title" href="<?php echo esc_url($child['url']); ?>">
                          <?php echo esc_html($child['name']); ?>
                        </a>
                        <?php if (!empty($child['children'])) : ?>
                          <ul class="pap-showcase-panel-sublist">
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
                  <?php else : ?>
                    <div class="pap-showcase-panel-empty">
                      <strong><?php esc_html_e('Categoria este în curs de populare', 'papetarie-storefront'); ?></strong>
                      <span><?php esc_html_e('Vom adăuga în scurt timp subcategorii și produse relevante aici.', 'papetarie-storefront'); ?></span>
                    </div>
                  <?php endif; ?>
                  </div>
                </div>
                <aside class="pap-showcase-panel-aside pap-showcase-panel-aside-image">
                  <a class="pap-showcase-panel-aside-link" href="<?php echo esc_url($category['url']); ?>">
                    <img
                      src="<?php echo esc_url($showcase_category_images[$category['slug']] ?? ($asset_base . '/showcase-hero-user.png')); ?>"
                      alt="<?php echo esc_attr($category['name']); ?>"
                      style="object-position: <?php echo esc_attr($showcase_category_positions[$category['slug']] ?? 'center center'); ?>;"
                      loading="lazy"
                    >
                  </a>
                </aside>
              </div>
            </section>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if (!empty($products)) : ?>
  <section id="featured-products" class="pap-shell pap-featured">
    <div class="pap-section-head pap-section-head-soft pap-section-head-featured">
      <h2><?php esc_html_e('Produse recomandate', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Selecție de produse utile pentru birou, școală și organizare de zi cu zi.', 'papetarie-storefront'); ?></p>
    </div>

    <div class="pap-featured-slider-shell">
      <button class="pap-featured-nav pap-featured-nav-prev" type="button" aria-label="<?php esc_attr_e('Produse anterioare', 'papetarie-storefront'); ?>" data-featured-prev>
        <span class="pap-featured-nav-icon pap-featured-nav-icon-prev" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
      </button>
      <div class="pap-featured-slider" data-featured-slider>
        <div class="pap-product-grid">
          <?php foreach ($products as $product) : ?>
            <?php
            if (!$product instanceof WC_Product) {
                continue;
            }

            $product_name = $product->get_name();
            $product_id = $product->get_id();
            $product_url = $product->get_permalink();
            $product_image_id = $product->get_image_id();
            if (isset($featured_product_images[$product_id])) {
                $product_image = '<img src="' . esc_url($featured_product_images[$product_id]) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
            } else {
                $product_image = $product_image_id
                    ? wp_get_attachment_image($product_image_id, 'medium', false, ['loading' => 'lazy', 'alt' => $product_name])
                    : '<img src="' . esc_url(wc_placeholder_img_src('woocommerce_thumbnail')) . '" alt="' . esc_attr($product_name) . '" loading="lazy">';
            }
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
            <article class="pap-product-card">
              <?php echo papetarie_storefront_wishlist_button_html($product_id, 'home'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              <div class="pap-product-thumb">
                <?php echo $product_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              </div>
              <h3><?php echo esc_html($product_name); ?></h3>
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
          <?php endforeach; ?>
        </div>
      </div>
      <button class="pap-featured-nav pap-featured-nav-next" type="button" aria-label="<?php esc_attr_e('Produse următoare', 'papetarie-storefront'); ?>" data-featured-next>
        <span class="pap-featured-nav-icon pap-featured-nav-icon-next" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
      </button>
    </div>
  </section>
  <?php endif; ?>

  <div class="pap-cart-modal" data-cart-modal hidden>
    <div class="pap-cart-modal-backdrop" data-cart-modal-close></div>
    <div class="pap-cart-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pap-cart-modal-title">
      <button class="pap-cart-modal-dismiss" type="button" aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>" data-cart-modal-close>×</button>
      <h3 id="pap-cart-modal-title"><?php esc_html_e('Produsul a fost adăugat în coș', 'papetarie-storefront'); ?></h3>
      <div class="pap-cart-modal-product">
        <div class="pap-cart-modal-thumb">
          <img src="" alt="" data-cart-modal-image>
        </div>
        <div class="pap-cart-modal-copy">
          <strong data-cart-modal-name></strong>
          <span data-cart-modal-price></span>
        </div>
        <a class="pap-cart-modal-link" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : $shop_url); ?>" data-cart-modal-link><?php esc_html_e('Vezi detalii coș', 'papetarie-storefront'); ?></a>
      </div>
    </div>
  </div>

  <section class="pap-shell pap-packages" id="recommended-packages">
    <div class="pap-packages-head">
      <div class="pap-section-head pap-section-head-packages">
        <h2><?php esc_html_e('Pachete recomandate', 'papetarie-storefront'); ?></h2>
      </div>
      <p class="pap-packages-subtitle"><?php esc_html_e('Selecție de pachete utile pentru birou, școală și organizare de zi cu zi.', 'papetarie-storefront'); ?></p>
    </div>

    <div class="pap-packages-grid">
      <?php foreach ($package_offers as $package) : ?>
        <article class="pap-package-card pap-package-card--<?php echo esc_attr($package['slug']); ?>">
          <div class="pap-package-copy">
            <h3><?php echo esc_html($package['title']); ?></h3>
            <ul class="pap-package-list">
              <?php foreach ($package['items'] as $item) : ?>
                <li><?php echo esc_html($item); ?></li>
              <?php endforeach; ?>
            </ul>
            <div class="pap-package-pricing">
              <strong class="pap-package-price"><?php echo esc_html($package['price']); ?></strong>
              <span class="pap-package-old-price"><?php echo esc_html($package['old_price']); ?></span>
            </div>
            <a class="pap-package-button" href="<?php echo esc_url($shop_url); ?>">
              <?php esc_html_e('Adaugă în coș', 'papetarie-storefront'); ?>
            </a>
          </div>

          <div class="pap-package-art" aria-hidden="true">
            <img
              class="pap-package-art-image"
              src="<?php echo esc_url($package['image']); ?>"
              alt=""
              loading="lazy"
            >
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if (!empty($offer_products)) : ?>
  <section id="special-offers" class="pap-shell pap-featured pap-offers">
    <div class="pap-section-head pap-section-head-soft pap-section-head-featured">
      <h2><?php esc_html_e('Oferte speciale', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Produse selectate cu reducere de 20% pentru birou, școală și organizare de zi cu zi.', 'papetarie-storefront'); ?></p>
    </div>

    <div class="pap-featured-slider-shell pap-offers-slider-shell">
      <button class="pap-featured-nav pap-featured-nav-prev" type="button" aria-label="<?php esc_attr_e('Oferte anterioare', 'papetarie-storefront'); ?>" data-offers-prev>
        <i class="fa-solid fa-angle-left pap-featured-nav-icon" aria-hidden="true"></i>
      </button>
      <div class="pap-featured-slider pap-offers-slider" data-offers-slider>
        <div class="pap-product-grid pap-offers-grid">
          <?php foreach ($offer_products as $offer) : ?>
            <article class="pap-product-card pap-product-card--offer">
              <span class="pap-offer-badge">-<?php echo esc_html((string) $offer['discount']); ?>%</span>
              <?php echo papetarie_storefront_wishlist_button_html((int) $offer['id'], 'home'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
              <a class="pap-offer-link" href="<?php echo esc_url($offer['url']); ?>" aria-label="<?php echo esc_attr($offer['name']); ?>">
                <div class="pap-product-thumb">
                  <?php echo $offer['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
              </a>
              <h3><?php echo esc_html($offer['name']); ?></h3>
              <p><?php echo esc_html($offer['subtitle']); ?></p>
              <div class="pap-product-meta pap-offer-meta">
                <div class="pap-offer-prices">
                  <strong class="pap-price pap-offer-price"><?php echo wp_kses_post($offer['price_html']); ?></strong>
                  <span class="pap-offer-old-price"><?php echo wp_kses_post($offer['old_price_html']); ?></span>
                </div>
                <div class="pap-product-actions">
                  <button
                    class="pap-home-add-to-cart"
                    type="button"
                    data-product-id="<?php echo esc_attr((string) $offer['id']); ?>"
                    data-product-url="<?php echo esc_url($offer['url']); ?>"
                    aria-label="<?php esc_attr_e('Adaugă în coș', 'papetarie-storefront'); ?>"
                  >
                    <span class="pap-product-action-icon"><?php echo papetarie_storefront_icon('cart'); ?></span>
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
      <button class="pap-featured-nav pap-featured-nav-next" type="button" aria-label="<?php esc_attr_e('Oferte următoare', 'papetarie-storefront'); ?>" data-offers-next>
        <i class="fa-solid fa-angle-right pap-featured-nav-icon" aria-hidden="true"></i>
      </button>
    </div>
  </section>
  <?php endif; ?>

  <section class="pap-shell pap-trust-bar">
    <div class="pap-trust-strip" aria-label="<?php esc_attr_e('Avantaje magazin', 'papetarie-storefront'); ?>">
      <?php foreach ($trust_features as $feature) : ?>
        <div class="pap-trust-item">
          <span class="pap-trust-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($feature['icon']); ?></span>
          <div class="pap-trust-copy">
            <strong><?php echo esc_html($feature['title']); ?></strong>
            <span><?php echo esc_html($feature['copy']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</main>
<script>
  (function () {
    var showcase = document.querySelector('[data-showcase]');
    if (!showcase) {
      return;
    }

    var showcaseGrid = showcase.querySelector('.pap-showcase-grid');
    var nav = showcase.querySelector('.pap-showcase-nav');
    var navItems = Array.prototype.slice.call(showcase.querySelectorAll('[data-showcase-tab]'));
    var panels = Array.prototype.slice.call(showcase.querySelectorAll('[data-showcase-panel]'));
    var stage = showcase.querySelector('[data-showcase-stage]');
    var slides = Array.prototype.slice.call(showcase.querySelectorAll('[data-showcase-slide]'));
    var dots = Array.prototype.slice.call(showcase.querySelectorAll('[data-showcase-dot]'));
    var featuredSlider = document.querySelector('[data-featured-slider]');
    var featuredPrev = document.querySelector('[data-featured-prev]');
    var featuredNext = document.querySelector('[data-featured-next]');
    var offersSlider = document.querySelector('[data-offers-slider]');
    var offersPrev = document.querySelector('[data-offers-prev]');
    var offersNext = document.querySelector('[data-offers-next]');
    var cartModal = document.querySelector('[data-cart-modal]');
    var cartModalImage = cartModal ? cartModal.querySelector('[data-cart-modal-image]') : null;
    var cartModalName = cartModal ? cartModal.querySelector('[data-cart-modal-name]') : null;
    var cartModalPrice = cartModal ? cartModal.querySelector('[data-cart-modal-price]') : null;
    var cartModalLink = cartModal ? cartModal.querySelector('[data-cart-modal-link]') : null;
    var cartModalClosers = cartModal ? Array.prototype.slice.call(cartModal.querySelectorAll('[data-cart-modal-close]')) : [];
    var currentSlide = 0;
    var sliderTimer = null;
    var debugOpen = false;

    function syncStageHeight() {
      if (!nav || !stage) {
        return;
      }

      stage.style.height = '';
    }

    function resetShowcaseState() {
      if (stage) {
        stage.style.height = '';
        stage.classList.remove('is-panel-visible');
      }

      navItems.forEach(function (item) {
        item.classList.remove('is-active');
      });

      panels.forEach(function (panel) {
        panel.hidden = false;
        panel.classList.remove('is-active');
      });
    }

    function setActivePanel(slug, keepVisible) {
      var isMobile = window.matchMedia('(max-width: 980px)').matches;
      var activePanel = null;
      var hasPanel = false;

      navItems.forEach(function (item) {
        item.classList.toggle('is-active', item.getAttribute('data-showcase-tab') === slug);
        if (item.getAttribute('data-showcase-tab') === slug) {
          hasPanel = item.getAttribute('data-showcase-has-children') === '1';
        }
      });

      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-showcase-panel') === slug;
        panel.classList.toggle('is-active', active);
        panel.hidden = isMobile ? !active : false;
        if (active) {
          activePanel = panel;
        }
      });

      if (hasPanel && activePanel) {
        if (stage) {
          stage.style.display = '';
        }
        stage.classList.toggle('is-panel-visible', keepVisible !== false);
      } else {
        if (stage) {
          stage.classList.remove('is-panel-visible');
          stage.style.display = '';
        }
        panels.forEach(function (panel) {
          panel.hidden = true;
          panel.classList.remove('is-active');
        });
      }
    }

    function hidePanels() {
      if (debugOpen) {
        return;
      }

      if (window.matchMedia('(max-width: 980px)').matches) {
        return;
      }

      resetShowcaseState();
    }

    function showSlide(index) {
      currentSlide = index;
      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === index);
      });
      dots.forEach(function (dot, dotIndex) {
        dot.classList.toggle('is-active', dotIndex === index);
      });
    }

    function startSlider() {
      if (slides.length < 2) {
        return;
      }

      if (sliderTimer) {
        window.clearInterval(sliderTimer);
      }

      sliderTimer = window.setInterval(function () {
        showSlide((currentSlide + 1) % slides.length);
      }, 4200);
    }

    navItems.forEach(function (item) {
      var slug = item.getAttribute('data-showcase-tab');
      var hasPanel = item.getAttribute('data-showcase-has-children') === '1';

      if (hasPanel) {
        item.addEventListener('mouseenter', function () {
          if (window.matchMedia('(min-width: 981px)').matches) {
            setActivePanel(slug, hasPanel);
          }
        });

        item.addEventListener('focus', function () {
          setActivePanel(slug, hasPanel);
        });
      } else {
        item.addEventListener('mouseenter', function () {
          resetShowcaseState();
        });

        item.addEventListener('focus', function () {
          resetShowcaseState();
        });
      }
    });

    if (showcaseGrid) {
      showcaseGrid.addEventListener('mouseleave', hidePanels);
    }

    stage.addEventListener('mouseleave', function (event) {
      if (window.matchMedia('(max-width: 980px)').matches) {
        return;
      }

      var related = event.relatedTarget;
      if (related && showcaseGrid && showcaseGrid.contains(related)) {
        return;
      }

      hidePanels();
    });

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        showSlide(parseInt(dot.getAttribute('data-showcase-dot'), 10));
        startSlider();
      });
    });

    function scrollHorizontalSlider(slider, direction) {
      if (!slider) {
        return;
      }

      var card = slider.querySelector('.pap-product-card');
      var amount = card ? card.offsetWidth + 16 : 260;
      slider.scrollBy({
        left: direction * amount * 2,
        behavior: 'smooth'
      });
    }

    if (featuredPrev) {
      featuredPrev.addEventListener('click', function () {
        scrollHorizontalSlider(featuredSlider, -1);
      });
    }

    if (featuredNext) {
      featuredNext.addEventListener('click', function () {
        scrollHorizontalSlider(featuredSlider, 1);
      });
    }

    if (offersPrev) {
      offersPrev.addEventListener('click', function () {
        scrollHorizontalSlider(offersSlider, -1);
      });
    }

    if (offersNext) {
      offersNext.addEventListener('click', function () {
        scrollHorizontalSlider(offersSlider, 1);
      });
    }

    function openCartModal(payload) {
      if (!cartModal) {
        return;
      }

      if (cartModalImage) {
        if (payload.image_url) {
          cartModalImage.src = payload.image_url;
          cartModalImage.alt = payload.name || '';
          cartModalImage.parentElement.hidden = false;
        } else {
          cartModalImage.src = '';
          cartModalImage.alt = '';
          cartModalImage.parentElement.hidden = true;
        }
      }

      if (cartModalName) {
        cartModalName.textContent = payload.name || '';
      }

      if (cartModalPrice) {
        cartModalPrice.innerHTML = payload.price_html || '';
      }

      if (cartModalLink && payload.cart_url) {
        cartModalLink.href = payload.cart_url;
      }

      cartModal.hidden = false;
      document.body.classList.add('pap-modal-open');
    }

    function closeCartModal() {
      if (!cartModal) {
        return;
      }

      cartModal.hidden = true;
      document.body.classList.remove('pap-modal-open');
    }

    cartModalClosers.forEach(function (closer) {
      closer.addEventListener('click', closeCartModal);
    });

    showSlide(0);
    if (debugOpen) {
      setActivePanel('<?php echo esc_js($showcase_active_slug); ?>', true);
    } else {
      resetShowcaseState();
      hidePanels();
    }
    syncStageHeight();
    startSlider();
    window.addEventListener('resize', syncStageHeight);
    window.addEventListener('load', syncStageHeight);
    window.addEventListener('pageshow', function () {
      if (!window.matchMedia('(max-width: 980px)').matches) {
        resetShowcaseState();
      }
      window.requestAnimationFrame(syncStageHeight);
    });

    if (window.ResizeObserver && nav) {
      var navResizeObserver = new ResizeObserver(function () {
        syncStageHeight();
      });

      navResizeObserver.observe(nav);
      window.addEventListener('beforeunload', function () {
        navResizeObserver.disconnect();
      });
    }
  })();
</script>
<?php
get_footer();
