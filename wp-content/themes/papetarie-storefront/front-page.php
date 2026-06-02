<?php

defined('ABSPATH') || exit;

$asset_base = get_stylesheet_directory_uri() . '/assets/images';
$showcase_categories = papetarie_storefront_get_mega_menu_categories();
$showcase_active_slug = papetarie_storefront_active_mega_menu_slug($showcase_categories);
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$add_to_cart_nonce = wp_create_nonce('pap_home_add_to_cart');

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

if (function_exists('wc_get_products')) {
    $unique_products = [];
    $seen_product_ids = [];
    $seen_product_names = [];

    foreach ($products as $product) {
        if (!$product instanceof WC_Product) {
            continue;
        }

        $product_id = $product->get_id();
        $product_name = mb_strtolower(trim($product->get_name()));

        if (isset($seen_product_ids[$product_id]) || isset($seen_product_names[$product_name])) {
            continue;
        }

        $seen_product_ids[$product_id] = true;
        $seen_product_names[$product_name] = true;
        $unique_products[] = $product;
    }

    if (count($unique_products) < 12) {
        $fallback_products = wc_get_products([
            'status' => 'publish',
            'limit' => 24,
            'orderby' => 'date',
            'order' => 'DESC',
            'exclude' => array_keys($seen_product_ids),
        ]);

        foreach ($fallback_products as $product) {
            if (!$product instanceof WC_Product) {
                continue;
            }

            $product_id = $product->get_id();
            $product_name = mb_strtolower(trim($product->get_name()));

            if (isset($seen_product_ids[$product_id]) || isset($seen_product_names[$product_name])) {
                continue;
            }

            $seen_product_ids[$product_id] = true;
            $seen_product_names[$product_name] = true;
            $unique_products[] = $product;

            if (count($unique_products) >= 12) {
                break;
            }
        }
    }

    $products = array_slice($unique_products, 0, 12);
}

if (empty($products) && function_exists('wc_get_products')) {
    $products = wc_get_products([
        'status' => 'publish',
        'limit' => 12,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
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

get_header();
?>
<main id="primary" class="site-main pap-homepage">
  <section class="pap-showcase" data-showcase>
    <div class="pap-shell pap-showcase-grid">
      <aside class="pap-showcase-nav" aria-label="<?php esc_attr_e('Categorii produse', 'papetarie-storefront'); ?>">
        <div class="pap-showcase-nav-list">
          <?php foreach ($showcase_categories as $category) : ?>
            <button
              class="pap-showcase-nav-item<?php echo $category['slug'] === $showcase_active_slug ? ' is-active' : ''; ?>"
              type="button"
              data-showcase-tab="<?php echo esc_attr($category['slug']); ?>"
              title="<?php echo esc_attr($category['name']); ?>"
            >
              <span class="pap-showcase-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($category['icon']); ?></span>
              <span class="pap-showcase-nav-label"><?php echo esc_html(papetarie_storefront_short_category_name($category['slug'], $category['name'])); ?></span>
            </button>
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
        </div>
      </div>
    </div>
  </section>

  <section id="featured-products" class="pap-shell pap-featured">
    <div class="pap-section-head pap-section-head-soft pap-section-head-featured">
      <h2><?php esc_html_e('Produse recomandate', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Selecție de produse utile pentru birou, școală și organizare de zi cu zi.', 'papetarie-storefront'); ?></p>
    </div>

    <div class="pap-featured-slider-shell">
      <button class="pap-featured-nav pap-featured-nav-prev" type="button" aria-label="<?php esc_attr_e('Produse anterioare', 'papetarie-storefront'); ?>" data-featured-prev>
        <i class="fa-solid fa-angle-left pap-featured-nav-icon" aria-hidden="true"></i>
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
              <button class="pap-wishlist" type="button" aria-label="<?php esc_attr_e('Adaugă la favorite', 'papetarie-storefront'); ?>">♡</button>
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
        <i class="fa-solid fa-angle-right pap-featured-nav-icon" aria-hidden="true"></i>
      </button>
    </div>
  </section>

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
    var addToCartButtons = Array.prototype.slice.call(document.querySelectorAll('.pap-home-add-to-cart'));
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

      if (window.matchMedia('(max-width: 980px)').matches) {
        stage.style.height = '';
        return;
      }

      stage.style.height = nav.offsetHeight + 'px';
    }

    function setActivePanel(slug, keepVisible) {
      var isMobile = window.matchMedia('(max-width: 980px)').matches;

      navItems.forEach(function (item) {
        item.classList.toggle('is-active', item.getAttribute('data-showcase-tab') === slug);
      });

      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-showcase-panel') === slug;
        panel.classList.toggle('is-active', active);
        panel.hidden = isMobile ? !active : false;
      });

      stage.classList.toggle('is-panel-visible', keepVisible !== false);
    }

    function hidePanels() {
      if (debugOpen) {
        return;
      }

      if (window.matchMedia('(max-width: 980px)').matches) {
        return;
      }

      stage.classList.remove('is-panel-visible');
      panels.forEach(function (panel) {
        panel.hidden = false;
        panel.classList.remove('is-active');
      });
      navItems.forEach(function (item) {
        item.classList.remove('is-active');
      });
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

      item.addEventListener('mouseenter', function () {
        if (window.matchMedia('(min-width: 981px)').matches) {
          setActivePanel(slug, true);
        }
      });

      item.addEventListener('focus', function () {
        setActivePanel(slug, true);
      });

      item.addEventListener('click', function () {
        setActivePanel(slug, true);
      });
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

    function scrollFeatured(direction) {
      if (!featuredSlider) {
        return;
      }

      var card = featuredSlider.querySelector('.pap-product-card');
      var amount = card ? card.offsetWidth + 16 : 260;
      featuredSlider.scrollBy({
        left: direction * amount * 2,
        behavior: 'smooth'
      });
    }

    if (featuredPrev) {
      featuredPrev.addEventListener('click', function () {
        scrollFeatured(-1);
      });
    }

    if (featuredNext) {
      featuredNext.addEventListener('click', function () {
        scrollFeatured(1);
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

    addToCartButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var productId = button.getAttribute('data-product-id');
        if (!productId) {
          return;
        }

        button.disabled = true;
        button.classList.add('is-loading');

        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams({
            action: 'pap_home_add_to_cart',
            nonce: '<?php echo esc_js($add_to_cart_nonce); ?>',
            product_id: productId,
            quantity: '1'
          })
        })
          .then(function (response) { return response.json(); })
          .then(function (response) {
            if (!response || !response.success) {
              throw new Error(response && response.data && response.data.message ? response.data.message : 'Add to cart failed');
            }

            openCartModal(response.data || {});
          })
          .catch(function () {
            window.location.href = button.getAttribute('data-product-url') || '<?php echo esc_url($shop_url); ?>';
          })
          .finally(function () {
            button.disabled = false;
            button.classList.remove('is-loading');
          });
      });
    });

    showSlide(0);
    if (debugOpen) {
      setActivePanel('<?php echo esc_js($showcase_active_slug); ?>', true);
    } else {
      hidePanels();
    }
    syncStageHeight();
    startSlider();
    window.addEventListener('resize', syncStageHeight);
    window.addEventListener('load', syncStageHeight);
  })();
</script>
<?php
get_footer();
