<?php

defined('ABSPATH') || exit;

$asset_base = get_stylesheet_directory_uri() . '/assets/images';
$asset_dir = get_stylesheet_directory() . '/assets/images';
$showcase_categories = papetarie_storefront_get_mega_menu_categories();
$showcase_active_slug = papetarie_storefront_active_mega_menu_slug($showcase_categories);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

// filemtime()-versioned URL, same idea as core's own wp_enqueue_style/
// script auto-versioning - these hero images are hardcoded <img>/
// <source> paths (not going through wp_enqueue_*), but now sit behind
// a 1-year Cache-Control (see the perf-audit .htaccess changes), so
// without a cache-busting query string a re-exported image at the
// same filename would never reach a visitor's browser until that
// cache naturally expired.
$versioned_asset = static function (string $filename) use ($asset_base, $asset_dir): string {
    $path = $asset_dir . '/' . $filename;
    $version = file_exists($path) ? (string) filemtime($path) : '1';

    return $asset_base . '/' . $filename . '?v=' . $version;
};

// Art-directed per breakpoint, not one image stretched/cropped by CSS
// for every viewport - image_tablet/image_mobile are optional per
// slide (falls back tablet -> desktop, mobile -> tablet -> desktop),
// so existing/future slides that only define 'image' keep working
// unchanged.
$showcase_slides = [
    [
        'image' => $versioned_asset('showcase-hero-user.jpg'),
        'image_tablet' => $versioned_asset('showcase-hero-user-tablet.jpg'),
        'image_mobile' => $versioned_asset('showcase-hero-user-mobile.jpg'),
    ],
    [
        'image' => $versioned_asset('showcase-hero-user-2.jpg'),
        'image_tablet' => $versioned_asset('showcase-hero-user-2-tablet.jpg'),
        'image_mobile' => $versioned_asset('showcase-hero-user-2-mobile.jpg'),
    ],
    [
        'image' => $versioned_asset('showcase-hero-user.jpg'),
        'image_tablet' => $versioned_asset('showcase-hero-user-tablet.jpg'),
        'image_mobile' => $versioned_asset('showcase-hero-user-mobile.jpg'),
    ],
];

$showcase_category_images = [
    'instrumente-de-scris-si-corectura' => $asset_base . '/showcase-slide-1-stationery.png',
    'articole-din-hartie' => $asset_base . '/showcase-slide-1-stationery.png',
    'arhivare' => $asset_base . '/showcase-slide-3-organization.png',
    'organizare' => $asset_base . '/showcase-slide-3-organization.png',
    'accesorii-pentru-birou' => $asset_base . '/showcase-hero-user.jpg',
    'articole-scolare' => $asset_base . '/showcase-slide-2-school.png',
    'consumabile-si-indosariere' => $asset_base . '/showcase-slide-3-organization.png',
    'sisteme-de-prezentare-si-afisare' => $asset_base . '/showcase-hero-user-2.jpg',
    'accesorii-it' => $asset_base . '/showcase-hero-user-2.jpg',
    'echipamente-birou' => $asset_base . '/showcase-hero-user-2.jpg',
    'capsatoare-si-perforatoare' => $asset_base . '/showcase-hero-user.jpg',
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

$products = function_exists('wc_get_products') ? wc_get_products([
    'status' => 'publish',
    'limit' => 5,
    'featured' => true,
    'orderby' => 'menu_order',
    'order' => 'ASC',
]) : [];

if (function_exists('wc_get_products') && count($products) < 5) {
    $featured_ids = array_map(static function ($product) {
        return $product instanceof WC_Product ? $product->get_id() : 0;
    }, $products);

    $supplemental_products = wc_get_products([
        'status' => 'publish',
        'limit' => 5 - count($products),
        'exclude' => $featured_ids,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $products = array_merge($products, $supplemental_products);
}

$package_offers = [
    [
        'slug' => 'office',
        'title' => 'Kit Angajat Nou',
        'subtitle' => 'Perfect pentru prima zi la birou',
        'badge' => ['label' => 'Recomandat', 'class' => 'is-featured'],
        'items' => ['5 caiete A4', '10 pixuri albastre', 'Sticky notes', 'Corector banda'],
        'included_count' => 10,
        'icons' => ['paper', 'pen', 'file-lines-outline', 'organize'],
        'price' => '149,99 lei',
        'old_price' => '220,00 lei',
        'discount' => '−70 lei',
        'image' => $asset_base . '/package-office-photo-v2.jpg',
    ],
    [
        'slug' => 'student',
        'title' => 'Kit Școlar Complet',
        'subtitle' => 'Tot ce are nevoie un elev',
        'badge' => ['label' => 'Cel mai popular', 'class' => 'is-popular'],
        'items' => ['3 caiete A4', '12 markere colorate', 'Penar echipat', 'Lipici solid'],
        'included_count' => 8,
        'icons' => ['school', 'pen', 'paper'],
        'price' => '89,99 lei',
        'old_price' => '130,00 lei',
        'discount' => '−40 lei',
        'image' => $asset_base . '/package-student-photo-v2.jpg',
    ],
    [
        'slug' => 'archive',
        'title' => 'Kit Birou Premium',
        'subtitle' => 'Productivitate la superlativ',
        'badge' => ['label' => 'Premium', 'class' => 'is-premium'],
        'items' => ['4 bibliorafturi', 'Separatoare color', 'Etichete autoadezive', 'Folii protectoare'],
        'included_count' => 12,
        'icons' => ['archive', 'organize', 'briefcase-outline', 'tag'],
        'price' => '249,99 lei',
        'old_price' => '370,00 lei',
        'discount' => '−120 lei',
        'image' => $asset_base . '/package-archive-photo-v2.jpg',
    ],
];

get_header();
?>
<main id="primary" class="site-main pap-homepage">
  <section class="pap-showcase" data-showcase>
    <div class="pap-shell">
    <div class="pap-showcase-grid">
      <aside class="pap-showcase-nav" aria-label="<?php esc_attr_e('Categorii produse', 'papetarie-storefront'); ?>">
        <div class="pap-showcase-nav-list">
          <?php foreach ($showcase_categories as $category) : ?>
            <a
              class="pap-showcase-nav-item<?php echo $category['slug'] === $showcase_active_slug && !empty($category['children']) ? ' is-active' : ''; ?>"
              href="<?php echo esc_url($category['url']); ?>"
              data-showcase-tab="<?php echo esc_attr($category['slug']); ?>"
              data-showcase-has-children="<?php echo !empty($category['children']) ? '1' : '0'; ?>"
            >
              <span class="pap-showcase-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($category['icon']); ?></span>
              <span class="pap-showcase-nav-label"><?php echo esc_html(papetarie_storefront_short_category_name($category['slug'], $category['name'])); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>

      <div class="pap-showcase-stage" data-showcase-stage>
        <div class="pap-showcase-slides">
          <?php foreach ($showcase_slides as $index => $slide) :
              $is_first_slide = $index === 0;
              // Only the FIRST slide is the real LCP candidate and gets
              // eager+high priority. Slide 2 still loads eagerly (no
              // priority hint) rather than loading="lazy" - this stage's
              // own slides sit on top of each other via opacity/
              // visibility, never display:none, and a visibility:hidden
              // element doesn't reliably count as "near the viewport"
              // for native lazy-loading, so a lazy slide 2 could still be
              // blank the moment autoplay/a dot tap reveals it 4.2s
              // later. Only slide 3+ (here, a repeat of slide 1's own
              // already-cached images) stays loading="lazy".
              $is_second_slide = $index === 1;
              $desktop_image = $slide['image'];
              $tablet_image = $slide['image_tablet'] ?? $desktop_image;
              $mobile_image = $slide['image_mobile'] ?? $tablet_image;
          ?>
            <article class="pap-showcase-slide<?php echo $is_first_slide ? ' is-active' : ''; ?>" data-showcase-slide="<?php echo esc_attr((string) $index); ?>">
              <div class="pap-showcase-slide-visual" aria-hidden="true">
                <picture>
                  <source media="(max-width: 767px)" srcset="<?php echo esc_url($mobile_image); ?>">
                  <source media="(max-width: 1023px)" srcset="<?php echo esc_url($tablet_image); ?>">
                  <img
                    class="pap-showcase-visual-banner"
                    src="<?php echo esc_url($desktop_image); ?>"
                    alt=""
                    <?php if ($is_first_slide || $is_second_slide) : ?>
                      loading="eager"
                    <?php else : ?>
                      loading="lazy"
                    <?php endif; ?>
                  >
                </picture>
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
                      src="<?php echo esc_url($showcase_category_images[$category['slug']] ?? ($asset_base . '/showcase-hero-user.jpg')); ?>"
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
    </div>
  </section>

  <section class="pap-header-benefits">
    <div class="pap-shell">
      <div class="pap-header-benefits-inner">
        <div class="pap-header-benefit-item">
          <span class="pap-header-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('benefit-delivery'); ?></span>
          <span><?php esc_html_e('Livrare 24-48h', 'papetarie-storefront'); ?></span>
        </div>
        <div class="pap-header-benefit-item">
          <span class="pap-header-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('benefit-payment'); ?></span>
          <span><?php esc_html_e('Plată securizată', 'papetarie-storefront'); ?></span>
        </div>
        <div class="pap-header-benefit-item">
          <span class="pap-header-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('benefit-support'); ?></span>
          <span><?php esc_html_e('Suport dedicat', 'papetarie-storefront'); ?></span>
        </div>
        <div class="pap-header-benefit-item">
          <span class="pap-header-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('benefit-returns'); ?></span>
          <span><?php esc_html_e('Retur 14 zile', 'papetarie-storefront'); ?></span>
        </div>
      </div>
    </div>
  </section>

  <?php
  if (!empty($products) && function_exists('papetarie_storefront_render_product_slider_section')) {
      echo papetarie_storefront_render_product_slider_section(
          __('Produse populare', 'papetarie-storefront'),
          '',
          $products,
          $shop_url,
          ['pap-shell', 'pap-product-slider--four-cols'],
          'featured-products'
      ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
  }
  ?>

  <section class="pap-shell pap-industries" id="industries">
    <div class="pap-industries-grid">
      <div class="pap-industries-hero">
        <span class="pap-industries-hero-label"><?php esc_html_e('Industrii', 'papetarie-storefront'); ?></span>
        <h2><?php esc_html_e('Găsește mai rapid', 'papetarie-storefront'); ?><br><?php esc_html_e('produsele potrivite', 'papetarie-storefront'); ?></h2>
        <p><?php esc_html_e('Am organizat produsele în funcție de tipul afacerii tale pentru a economisi timp.', 'papetarie-storefront'); ?></p>
      </div>
      <div class="pap-industries-cards">
        <a class="pap-industries-card" href="<?php echo esc_url($shop_url); ?>" style="background-image: linear-gradient(0deg, rgba(13,46,97,0.78) 0%, rgba(13,46,97,0.1) 55%, rgba(13,46,97,0) 100%), url('<?php echo esc_url($asset_base . '/industries-schools-v2.jpg'); ?>');">
          <strong class="pap-industries-card-title"><?php esc_html_e('Școli & Universități', 'papetarie-storefront'); ?></strong>
          <span class="pap-industries-card-count"><?php esc_html_e('800+ produse', 'papetarie-storefront'); ?></span>
          <span class="pap-industries-card-arrow" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
        </a>
        <a class="pap-industries-card" href="<?php echo esc_url($shop_url); ?>" style="background-image: linear-gradient(0deg, rgba(13,46,97,0.78) 0%, rgba(13,46,97,0.1) 55%, rgba(13,46,97,0) 100%), url('<?php echo esc_url($asset_base . '/industries-offices-v2.jpg'); ?>');">
          <strong class="pap-industries-card-title"><?php esc_html_e('Birouri corporate', 'papetarie-storefront'); ?></strong>
          <span class="pap-industries-card-count"><?php esc_html_e('1.200+ produse', 'papetarie-storefront'); ?></span>
          <span class="pap-industries-card-arrow" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
        </a>
      </div>
    </div>
  </section>

  <section class="pap-shell pap-packages" id="recommended-packages">
    <div class="pap-packages__card">
      <div class="pap-packages__gradient-bar" aria-hidden="true"></div>
      <div class="pap-packages__card-body">
        <div class="pap-packages-head">
          <span class="pap-packages-accent" aria-hidden="true"></span>
          <h2><?php esc_html_e('Recomandate pentru tine', 'papetarie-storefront'); ?></h2>
        </div>

        <div class="pap-packages-grid">
          <?php foreach ($package_offers as $package) : ?>
            <article class="pap-package-card pap-package-card--<?php echo esc_attr($package['slug']); ?>">
              <div class="pap-package-photo">
                <img
                  class="pap-package-photo-image"
                  src="<?php echo esc_url($package['image']); ?>"
                  alt=""
                  loading="lazy"
                >
                <?php if (!empty($package['badge'])) : ?>
                  <span class="pap-package-badge pap-package-badge--<?php echo esc_attr($package['badge']['class']); ?>"><?php echo esc_html($package['badge']['label']); ?></span>
                <?php endif; ?>
              </div>

              <div class="pap-package-body">
                <h3><?php echo esc_html($package['title']); ?></h3>
                <p class="pap-package-subtitle"><?php echo esc_html($package['subtitle']); ?></p>

                <div class="pap-package-meta">
                  <p class="pap-package-count"><strong><?php echo esc_html((string) $package['included_count']); ?></strong> <?php esc_html_e('produse incluse', 'papetarie-storefront'); ?></p>

                  <div class="pap-package-avatars" aria-hidden="true">
                    <?php foreach ($package['icons'] as $icon_name) : ?>
                      <span class="pap-package-avatar"><?php echo papetarie_storefront_icon($icon_name); ?></span>
                    <?php endforeach; ?>
                    <?php if ($package['included_count'] > count($package['icons'])) : ?>
                      <span class="pap-package-avatar pap-package-avatar--more">+<?php echo esc_html((string) ($package['included_count'] - count($package['icons']))); ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="pap-package-footer">
                  <div class="pap-package-pricing">
                    <strong class="pap-package-price"><?php echo esc_html($package['price']); ?></strong>
                    <span class="pap-package-pricing-sub">
                      <span class="pap-package-old-price"><?php echo esc_html($package['old_price']); ?></span>
                      <span class="pap-package-discount"><?php echo esc_html($package['discount']); ?></span>
                    </span>
                  </div>

                  <a class="pap-home-add-to-cart pap-package-button" href="<?php echo esc_url($shop_url); ?>">
                    <span class="pap-product-action-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('bag'); ?></span>
                    <span class="pap-product-action-label"><?php esc_html_e('Adaugă în coș', 'papetarie-storefront'); ?></span>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="pap-shell pap-offer-banner">
    <div class="pap-offer-banner-inner">
      <div class="pap-offer-banner-copy">
        <span class="pap-offer-banner-label"><?php esc_html_e('Companii · Școli · Instituții', 'papetarie-storefront'); ?></span>
        <h2><?php esc_html_e('Ai nevoie de o ofertă personalizată?', 'papetarie-storefront'); ?></h2>
      </div>
      <a class="pap-offer-banner-button" href="<?php echo esc_url(home_url('/contact/')); ?>">
        <?php esc_html_e('Cere ofertă', 'papetarie-storefront'); ?>
      </a>
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

    // Indexul "tintit" de noi la ultimul click, separat de scrollLeft-ul
    // real - scroll-ul e "smooth" (animat), deci daca se da click din nou
    // inainte ca animatia anterioara sa se termine, scrollLeft e undeva la
    // mijloc intre doua carduri, iar un index calculat direct de-acolo pica
    // adesea gresit, facand sageata sa para ca "sare" la click-uri rapide
    // repetate (reprodus live 2026-07-31, pe sliderele "recomandate" si
    // "oferte" de pe homepage).
    var sliderTargetIndex = typeof WeakMap === 'function' ? new WeakMap() : null;
    var sliderScrollTimers = typeof WeakMap === 'function' ? new WeakMap() : null;
    var sliderAnimations = typeof WeakMap === 'function' ? new WeakMap() : null;

    function getSliderMetrics(slider) {
      var card = slider.querySelector('.pap-product-card');
      if (!card) {
        return null;
      }

      var gap = parseFloat(getComputedStyle(slider.querySelector('.pap-product-grid') || slider).columnGap) || 0;
      var amount = card.getBoundingClientRect().width + gap;
      var maxScroll = slider.scrollWidth - slider.clientWidth;
      var maxIndex = Math.max(0, Math.round(maxScroll / amount));

      return { amount: amount, maxScroll: maxScroll, maxIndex: maxIndex };
    }

    function indexFromScrollLeft(slider, amount, maxIndex) {
      return Math.max(0, Math.min(maxIndex, Math.round(slider.scrollLeft / amount)));
    }

    function getTrackedSliderIndex(slider, amount, maxIndex) {
      if (sliderTargetIndex && sliderTargetIndex.has(slider)) {
        return sliderTargetIndex.get(slider);
      }

      return indexFromScrollLeft(slider, amount, maxIndex);
    }

    function scheduleManualSliderResync(slider) {
      if (!sliderScrollTimers || (sliderAnimations && sliderAnimations.has(slider))) {
        return;
      }

      var existing = sliderScrollTimers.get(slider);
      if (existing) {
        window.clearTimeout(existing);
      }

      var timer = window.setTimeout(function () {
        if (sliderAnimations && sliderAnimations.has(slider)) {
          return;
        }
        var metrics = getSliderMetrics(slider);
        if (metrics && sliderTargetIndex) {
          sliderTargetIndex.set(slider, indexFromScrollLeft(slider, metrics.amount, metrics.maxIndex));
        }
        sliderScrollTimers.delete(slider);
      }, 150);

      sliderScrollTimers.set(slider, timer);
    }

    function easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    // Animatie proprie (requestAnimationFrame), in locul lui
    // Element.scrollTo({behavior:'smooth'}) nativ - scroll-ul smooth nativ,
    // re-tintit printr-un al doilea apel inainte sa se termine primul (exact
    // ce se intampla la click-uri rapide repetate), se comporta inconsistent
    // intre browsere, dand impresia ca sare putin inapoi la mijlocul miscarii
    // chiar daca pozitia finala calculata era corecta (reprodus live
    // 2026-07-31). Interpoland noi cadru cu cadru, plecam mereu din
    // scrollLeft-ul real curent.
    function animateScrollTo(slider, targetLeft, duration) {
      duration = typeof duration === 'number' ? duration : 380;

      if (sliderAnimations) {
        var existing = sliderAnimations.get(slider);
        if (existing) {
          window.cancelAnimationFrame(existing.rafId);
        }
      }

      var startLeft = slider.scrollLeft;
      var delta = targetLeft - startLeft;
      var startTime = null;

      if (Math.abs(delta) < 1) {
        slider.scrollLeft = targetLeft;
        if (sliderAnimations) { sliderAnimations.delete(slider); }
        return;
      }

      // CSS "scroll-snap-type: mandatory" de pe slider face ca browserul sa
      // refuze sa randeze pozitiile intermediare pe care le setam noi cadru
      // cu cadru (nu sunt puncte de "snap") - ramane vizual blocat pe pozitia
      // curenta pana la finalul animatiei noastre si sare direct la
      // urmatorul punct de snap, exact saritura raportata. Il dezactivam cat
      // dureaza animatia proprie si il restauram la final, ca drag-ul manual
      // (touch) sa ramana cu snap.
      slider.style.scrollSnapType = 'none';

      function step(timestamp) {
        if (startTime === null) { startTime = timestamp; }
        var elapsed = timestamp - startTime;
        var progress = duration > 0 ? Math.min(1, elapsed / duration) : 1;
        slider.scrollLeft = startLeft + delta * easeOutCubic(progress);

        if (progress < 1) {
          var rafId = window.requestAnimationFrame(step);
          if (sliderAnimations) { sliderAnimations.set(slider, { rafId: rafId, targetLeft: targetLeft }); }
        } else {
          slider.style.scrollSnapType = '';
          if (sliderAnimations) {
            sliderAnimations.delete(slider);
          }
        }
      }

      var initialRafId = window.requestAnimationFrame(step);
      if (sliderAnimations) { sliderAnimations.set(slider, { rafId: initialRafId, targetLeft: targetLeft }); }
    }

    function scrollHorizontalSlider(slider, direction) {
      if (!slider) {
        return;
      }

      var metrics = getSliderMetrics(slider);
      if (!metrics) {
        return;
      }

      var amount = metrics.amount;
      var maxScroll = metrics.maxScroll;
      var maxIndex = metrics.maxIndex;
      var currentIndex = getTrackedSliderIndex(slider, amount, maxIndex);
      var targetIndex = currentIndex + direction;

      // Wrap-around: la capat, click-ul suplimentar continua de la celalalt
      // capat in loc sa ramana blocat. Teleportul instant la 0/maxScroll e
      // sigur acum - scroll-snap-type e deja dezactivat de orice animatie
      // anterioara inca in desfasurare (vezi animateScrollTo()).
      if (targetIndex > maxIndex) {
        if (sliderTargetIndex) { sliderTargetIndex.set(slider, 0); }
        slider.scrollLeft = 0;
        animateScrollTo(slider, Math.min(amount, maxScroll));
        return;
      }

      if (targetIndex < 0) {
        if (sliderTargetIndex) { sliderTargetIndex.set(slider, maxIndex); }
        slider.scrollLeft = maxScroll;
        animateScrollTo(slider, Math.max(maxScroll - amount, 0));
        return;
      }

      if (sliderTargetIndex) { sliderTargetIndex.set(slider, targetIndex); }
      var target = targetIndex >= maxIndex ? maxScroll : targetIndex * amount;
      animateScrollTo(slider, target);
    }

    [featuredSlider, offersSlider].forEach(function (slider) {
      if (slider) {
        slider.addEventListener('scroll', function () {
          scheduleManualSliderResync(slider);
        }, { passive: true });
      }
    });

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
