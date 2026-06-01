<?php

defined('ABSPATH') || exit;
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
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Sari la conținut', 'papetarie-storefront'); ?></a>

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
        <button class="pap-category-trigger" type="button">
          <span class="pap-category-trigger-icon"><?php echo papetarie_storefront_icon('menu'); ?></span>
          <span><?php esc_html_e('Toate categoriile', 'papetarie-storefront'); ?></span>
        </button>
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
