<?php

defined('ABSPATH') || exit;

$header_menu_categories = function_exists('papetarie_storefront_get_mega_menu_categories') ? papetarie_storefront_get_mega_menu_categories() : [];
$header_menu_active_slug = function_exists('papetarie_storefront_active_mega_menu_slug') ? papetarie_storefront_active_mega_menu_slug($header_menu_categories) : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="pap-site-header" role="banner">
  <section class="pap-topbar" data-topbar>
    <div class="pap-shell pap-topbar-inner">
      <div class="pap-topbar-message">
        <span class="pap-topbar-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('truck'); ?></span>
        <p><?php esc_html_e('Transport GRATIS în Cluj-Napoca și localitățile limitrofe la comenzi de peste 150 lei, iar în țară la comenzi de peste 299 lei*.', 'papetarie-storefront'); ?></p>
      </div>
      <button class="pap-topbar-close" type="button" aria-label="<?php esc_attr_e('Închide mesajul de transport', 'papetarie-storefront'); ?>" data-topbar-close>×</button>
    </div>
  </section>

  <section class="pap-header">
    <div class="pap-shell pap-header-main">
      <a class="pap-logo" href="<?php echo esc_url(home_url('/')); ?>">
        <?php if (papetarie_storefront_has_real_logo()) : ?>
          <span class="pap-logo-image"><?php the_custom_logo(); ?></span>
        <?php else : ?>
          <span class="pap-logo-image">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-supplyhub-cropped.png'); ?>" alt="<?php esc_attr_e('SupplyHub Stationery Solutions', 'papetarie-storefront'); ?>">
          </span>
        <?php endif; ?>
        <?php if (papetarie_storefront_has_real_logo()) : ?>
          <span class="pap-logo-text">
            <strong><?php bloginfo('name'); ?></strong>
            <small><?php bloginfo('description'); ?></small>
          </span>
        <?php endif; ?>
      </a>

      <form class="pap-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
        <input type="search" name="s" placeholder="<?php esc_attr_e('Caută după produs, SKU sau cuvânt cheie...', 'papetarie-storefront'); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
        <button type="submit"><?php echo papetarie_storefront_icon('search'); ?><span><?php esc_html_e('Caută', 'papetarie-storefront'); ?></span></button>
        <input type="hidden" name="post_type" value="product">
      </form>

      <div class="pap-header-tools">
        <a class="pap-tool-card pap-tool-card-account" href="<?php echo esc_url(wp_login_url()); ?>">
          <i class="pap-tool-icon"><?php echo papetarie_storefront_icon('account'); ?></i>
          <span class="pap-tool-copy">
            <strong><?php esc_html_e('Cont', 'papetarie-storefront'); ?></strong>
            <span><?php esc_html_e('Autentificare', 'papetarie-storefront'); ?></span>
          </span>
        </a>
        <a class="pap-tool-card pap-tool-card-cart" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#'); ?>">
          <i class="pap-tool-icon"><?php echo papetarie_storefront_icon('cart'); ?></i>
          <span class="pap-tool-copy">
            <strong><?php esc_html_e('Coș', 'papetarie-storefront'); ?></strong>
            <span><?php echo esc_html(sprintf(_n('%s produs', '%s produse', papetarie_storefront_cart_count(), 'papetarie-storefront'), papetarie_storefront_cart_count())); ?></span>
          </span>
        </a>
      </div>
    </div>

    <div class="pap-nav-row">
      <div class="pap-shell pap-nav-inner">
        <div class="pap-category-menu-anchor">
        <button
          class="pap-category-trigger"
          type="button"
          aria-expanded="false"
          aria-controls="pap-header-category-menu"
          data-header-category-menu-trigger
        >
          <span class="pap-category-trigger-icon"><?php echo papetarie_storefront_icon('menu'); ?></span>
          <span><?php esc_html_e('Toate categoriile', 'papetarie-storefront'); ?></span>
        </button>
          <?php papetarie_storefront_render_header_category_menu($header_menu_categories, $header_menu_active_slug); ?>
        </div>
        <nav class="pap-main-nav" aria-label="<?php esc_attr_e('Meniu principal', 'papetarie-storefront'); ?>">
          <?php
          wp_nav_menu(
              [
                  'theme_location' => 'primary',
                  'container' => false,
                  'menu_class' => 'pap-primary-menu',
                  'fallback_cb' => static function (): void {
                      echo '<ul class="pap-primary-menu"><li><a href="#">Despre noi</a></li><li><a href="#">Produse promoționale</a></li><li><a href="#">SEAP</a></li></ul>';
                  },
              ]
          );
          ?>
        </nav>
        <div class="pap-help-links">
          <?php
          wp_nav_menu(
              [
                  'theme_location' => 'utility',
                  'container' => false,
                  'menu_class' => 'pap-utility-menu',
                  'fallback_cb' => static function (): void {
                    echo '<ul class="pap-utility-menu"><li><a href="#">' . papetarie_storefront_icon('help') . '<span>Ai nevoie de ajutor?</span></a></li></ul>';
                },
            ]
        );
          ?>
      </div>
    <?php if (false && $show_header_category_menu) : ?>
      <div class="pap-category-menu-shell" data-category-menu-shell>
        <div class="pap-shell pap-category-menu-inner">
          <div
            id="pap-category-menu"
            class="pap-category-menu pap-showcase-grid"
            data-category-menu
            hidden
          >
          <aside class="pap-category-menu-nav pap-showcase-nav" aria-label="<?php esc_attr_e('Categorii principale', 'papetarie-storefront'); ?>">
            <div class="pap-category-menu-nav-list pap-showcase-nav-list">
                      <?php foreach ($header_menu_categories as $category) : ?>
                        <a
                          class="pap-category-menu-nav-item pap-showcase-nav-item<?php echo $category['slug'] === $header_menu_active_slug ? ' is-active' : ''; ?>"
                          href="<?php echo esc_url($category['url']); ?>"
                          data-category-menu-item="<?php echo esc_attr($category['slug']); ?>"
                          data-category-menu-target="<?php echo esc_attr($category['slug']); ?>"
                          data-category-menu-has-children="<?php echo !empty($category['children']) ? '1' : '0'; ?>"
                          aria-controls="pap-category-menu-panel-<?php echo esc_attr($category['slug']); ?>"
                          aria-expanded="<?php echo !empty($category['children']) && $category['slug'] === $header_menu_active_slug ? 'true' : 'false'; ?>"
                        >
                  <span class="pap-category-menu-nav-icon pap-showcase-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon($category['icon']); ?></span>
                  <span class="pap-category-menu-nav-copy pap-showcase-nav-label"><?php echo esc_html(papetarie_storefront_short_category_name($category['slug'], $category['name'])); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </aside>

          <div class="pap-category-menu-panels pap-showcase-stage">
            <div class="pap-showcase-panels">
                      <?php foreach ($header_menu_categories as $category) : ?>
                        <?php if (empty($category['children'])) { continue; } ?>
                        <section
                          id="pap-category-menu-panel-<?php echo esc_attr($category['slug']); ?>"
                          class="pap-category-menu-panel pap-showcase-panel<?php echo $category['slug'] === $header_menu_active_slug ? ' is-active' : ''; ?>"
                  data-category-menu-panel="<?php echo esc_attr($category['slug']); ?>"
                  <?php echo $category['slug'] === $header_menu_active_slug ? '' : 'hidden'; ?>
                >
                          <div class="pap-showcase-panel-layout">
                            <div class="pap-showcase-panel-copy">
                              <div class="pap-showcase-panel-title"><?php echo esc_html($category['name']); ?></div>
                              <div class="pap-showcase-panel-columns">
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
                              </div>
                            </div>
                          </div>
                </section>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</header>

<script>
  (function () {
    var storageKey = 'pap_topbar_closed';
    var topbar = document.querySelector('[data-topbar]');
    var closeButton = document.querySelector('[data-topbar-close]');
    if (!topbar || !closeButton) {
      // continue
    }
    if (topbar && closeButton) {
      try {
        if (window.sessionStorage.getItem(storageKey) === '1') {
          topbar.hidden = true;
        }
      } catch (error) {}
      closeButton.addEventListener('click', function () {
        topbar.hidden = true;
        try {
          window.sessionStorage.setItem(storageKey, '1');
        } catch (error) {}
      });
    }
  })();
</script>

<div id="content" class="site-content" tabindex="-1">
