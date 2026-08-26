<?php

defined('ABSPATH') || exit;

$header_menu_categories = function_exists('papetarie_storefront_get_mega_menu_categories') ? papetarie_storefront_get_mega_menu_categories() : [];
$header_menu_active_slug = function_exists('papetarie_storefront_active_mega_menu_slug') ? papetarie_storefront_active_mega_menu_slug($header_menu_categories) : '';
// The shared helper falls back to the first category when there is no real
// current category (needed by front-page.php's showcase module, which
// always shows one panel by default) - the header menu has no such
// default-panel need, so outside an actual category page nothing should
// read as "active".
if (!is_tax('product_cat')) {
    $header_menu_active_slug = '';
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if (papetarie_storefront_is_checkout_or_order_received_page()) : ?>
  <?php echo papetarie_storefront_get_checkout_header_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php else : ?>
  <header class="pap-site-header" role="banner">
    <section class="pap-topbar" data-topbar>
      <div class="pap-shell pap-topbar-inner">
        <div class="pap-topbar-message">
          <span class="pap-topbar-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('truck'); ?></span>
          <p class="pap-topbar-message-full"><?php esc_html_e('Transport GRATIS în Cluj-Napoca și localitățile limitrofe la comenzi de peste 150 lei, iar în țară la comenzi de peste 299 lei*.', 'papetarie-storefront'); ?></p>
          <p class="pap-topbar-message-short"><?php esc_html_e('GRATIS peste 150 lei (Cluj) / 299 lei (țară)*', 'papetarie-storefront'); ?></p>
        </div>
        <button class="pap-topbar-close" type="button" aria-label="<?php esc_attr_e('Închide mesajul de transport', 'papetarie-storefront'); ?>" data-topbar-close>×</button>
      </div>
    </section>

    <section class="pap-header">
      <div class="pap-shell pap-header-main">
        <button
          class="pap-mobile-menu-trigger"
          type="button"
          aria-label="<?php esc_attr_e('Deschide meniul', 'papetarie-storefront'); ?>"
          aria-expanded="false"
          aria-controls="pap-nav-row"
          data-mobile-menu-trigger
        >
          <span class="pap-mobile-menu-trigger-icon" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        </button>

        <a class="pap-logo" href="<?php echo esc_url(home_url('/')); ?>">
          <?php if (papetarie_storefront_has_real_logo()) : ?>
            <span class="pap-logo-image"><?php the_custom_logo(); ?></span>
          <?php else : ?>
            <span class="pap-logo-image">
              <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-notix.png'); ?>" alt="<?php esc_attr_e('Notix', 'papetarie-storefront'); ?>">
            </span>
          <?php endif; ?>
          <?php if (papetarie_storefront_has_real_logo()) : ?>
            <span class="pap-logo-text">
              <strong><?php bloginfo('name'); ?></strong>
              <small><?php bloginfo('description'); ?></small>
            </span>
          <?php endif; ?>
        </a>

        <form class="pap-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search" autocomplete="off">
          <input type="search" name="s" autocomplete="off" placeholder="<?php esc_attr_e('Caută după produs, SKU sau cuvânt cheie...', 'papetarie-storefront'); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
          <button type="submit"><?php echo papetarie_storefront_icon('search'); ?><span><?php esc_html_e('Caută', 'papetarie-storefront'); ?></span></button>
          <input type="hidden" name="post_type" value="product">
        </form>

        <div class="pap-header-tools">
          <?php echo papetarie_storefront_render_account_tool_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          <button
            class="pap-tool-card pap-tool-card-cart"
            type="button"
            data-cart-drawer-trigger
            aria-controls="pap-cart-drawer"
            aria-expanded="false"
          >
            <span class="pap-tool-icon-badge" aria-hidden="true">
              <i class="pap-tool-icon"><?php echo papetarie_storefront_icon('cart'); ?></i>
              <span class="pap-tool-count-badge" data-pap-cart-count-badge><?php echo esc_html(papetarie_storefront_cart_count()); ?></span>
            </span>
            <span class="pap-tool-copy">
              <strong><?php esc_html_e('Coș', 'papetarie-storefront'); ?></strong>
              <span data-pap-cart-count><?php echo esc_html(papetarie_storefront_cart_count_label()); ?></span>
            </span>
          </button>
        </div>
      </div>

      <div class="pap-mobile-nav-overlay" data-mobile-nav-overlay hidden></div>

      <div class="pap-nav-row" id="pap-nav-row" data-mobile-nav-panel>
        <div class="pap-mobile-nav-head">
          <span class="pap-mobile-nav-head-title"><?php esc_html_e('Meniu', 'papetarie-storefront'); ?></span>
          <button class="pap-mobile-nav-close" type="button" aria-label="<?php esc_attr_e('Închide meniul', 'papetarie-storefront'); ?>" data-mobile-nav-close>&times;</button>
        </div>
        <div class="pap-shell pap-nav-inner">
          <div class="pap-mobile-nav-section-title"><?php esc_html_e('Categorii', 'papetarie-storefront'); ?></div>

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

          <div class="pap-mobile-nav-section-title" data-mobile-nav-links-title><?php esc_html_e('Informații', 'papetarie-storefront'); ?></div>

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
                        echo '<ul class="pap-utility-menu"><li><a href="#">' . papetarie_storefront_icon('headset-outline') . '<span>Ai nevoie de ajutor?</span></a></li></ul>';
                    },
                ]
            );
            ?>
          </div>
        </div>
      </div>
    </section>
  </header>
<?php endif; ?>

<script>
  (function () {
    var storageKey = 'pap_topbar_closed';
    var topbar = document.querySelector('[data-topbar]');
    var closeButton = document.querySelector('[data-topbar-close]');
    if (!topbar || !closeButton) {
      return;
    }

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
  })();
</script>

<div id="content" class="site-content" tabindex="-1">
