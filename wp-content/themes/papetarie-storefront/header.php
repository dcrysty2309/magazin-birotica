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
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e('Skip to content', 'papetarie-storefront'); ?></a>

<header class="pap-site-header" role="banner">
  <section class="pap-topbar">
    <div class="pap-shell pap-topbar-inner">
      <div class="pap-topbar-left">
        <span><?php esc_html_e('FREE SHIPPING on orders over $75', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('BULK PRICING for Business', 'papetarie-storefront'); ?></span>
        <span><?php esc_html_e('Same Day Despatch before 2PM', 'papetarie-storefront'); ?></span>
      </div>
      <div class="pap-topbar-right">
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'top-links',
                'container' => false,
                'menu_class' => 'pap-top-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-top-menu"><li><a href="#">School</a></li><li><a href="#">Office</a></li><li><a href="#">Creative</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
    </div>
  </section>

  <section class="pap-header">
    <div class="pap-shell pap-header-main">
      <a class="pap-logo" href="<?php echo esc_url(home_url('/')); ?>">
        <?php if (has_custom_logo()) : ?>
          <span class="pap-logo-image"><?php the_custom_logo(); ?></span>
        <?php else : ?>
          <span class="pap-logo-mark" aria-hidden="true">
            <i></i><i></i><i></i><i></i><i></i><i></i>
          </span>
        <?php endif; ?>
        <span class="pap-logo-text">
          <strong><?php bloginfo('name'); ?></strong>
          <small><?php bloginfo('description'); ?></small>
        </span>
      </a>

      <form class="pap-search" action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
        <input type="search" name="s" placeholder="<?php esc_attr_e('Search by product, SKU or keyword...', 'papetarie-storefront'); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
        <select name="product_cat">
          <option value=""><?php esc_html_e('All Categories', 'papetarie-storefront'); ?></option>
          <?php foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 6]) as $term) : ?>
            <option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"><?php esc_html_e('Search', 'papetarie-storefront'); ?></button>
        <input type="hidden" name="post_type" value="product">
      </form>

      <div class="pap-header-tools">
        <a href="<?php echo esc_url(wp_login_url()); ?>">
          <strong><?php esc_html_e('Account', 'papetarie-storefront'); ?></strong>
          <span><?php esc_html_e('Sign in', 'papetarie-storefront'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>">
          <strong><?php esc_html_e('Quick Order', 'papetarie-storefront'); ?></strong>
          <span><?php esc_html_e('Upload List', 'papetarie-storefront'); ?></span>
        </a>
        <a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#'); ?>">
          <strong><?php echo esc_html(papetarie_storefront_cart_total()); ?></strong>
          <span><?php echo esc_html(papetarie_storefront_cart_count() . ' items'); ?></span>
        </a>
      </div>
    </div>

    <div class="pap-nav-row">
      <div class="pap-shell pap-nav-inner">
        <a class="pap-category-trigger" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>"><?php esc_html_e('Shop by Category', 'papetarie-storefront'); ?></a>
        <nav class="pap-main-nav" aria-label="<?php esc_attr_e('Primary menu', 'papetarie-storefront'); ?>">
          <?php
          wp_nav_menu(
              [
                  'theme_location' => 'primary',
                  'container' => false,
                  'menu_class' => 'pap-primary-menu',
                  'fallback_cb' => static function (): void {
                      echo '<ul class="pap-primary-menu"><li><a href="#new-arrivals">New Arrivals</a></li><li><a href="#featured-products">Top Sellers</a></li><li><a href="#brands">Brands</a></li><li><a href="#deals">Deals</a></li></ul>';
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
                      echo '<ul class="pap-utility-menu"><li><a href="#">Need Help?</a></li><li><a href="#">Contact Us</a></li></ul>';
                  },
              ]
          );
          ?>
        </div>
      </div>
    </div>
  </section>
</header>

<div id="content" class="site-content" tabindex="-1">
