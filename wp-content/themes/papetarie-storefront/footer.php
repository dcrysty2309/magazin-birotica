<?php

defined('ABSPATH') || exit;
?>
</div>

<footer class="pap-footer">
  <div class="pap-shell pap-footer-grid">
    <div class="pap-footer-newsletter">
      <h2><?php esc_html_e('Stay in the loop', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Sign up for exclusive deals, new arrivals and catalog updates.', 'papetarie-storefront'); ?></p>
      <form class="pap-newsletter-form">
        <input type="email" placeholder="<?php esc_attr_e('Enter your email', 'papetarie-storefront'); ?>">
        <button type="submit"><?php esc_html_e('Subscribe', 'papetarie-storefront'); ?></button>
      </form>
    </div>

    <div class="pap-footer-links">
      <div>
        <h3><?php esc_html_e('Shop', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-shop',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="/?page_id=7">All Products</a></li><li><a href="#featured-products">Top Sellers</a></li><li><a href="#deals">Deals</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('Categories', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-categories',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">School Supplies</a></li><li><a href="#">Writing Instruments</a></li><li><a href="#">Notebooks & Pads</a></li><li><a href="#">Paper Products</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('Help & Info', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-help',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">Contact Us</a></li><li><a href="#">FAQ</a></li><li><a href="#">Shipping Information</a></li><li><a href="#">Bulk Orders</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('About Us', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-about',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">Our Story</a></li><li><a href="#">Sustainability</a></li><li><a href="#">Careers</a></li><li><a href="#">News</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
    </div>
  </div>

  <div class="pap-shell pap-footer-bottom">
    <p>© 2026 <?php bloginfo('name'); ?>. <?php esc_html_e('All rights reserved.', 'papetarie-storefront'); ?></p>
    <div>
      <a href="#"><?php esc_html_e('Terms & Conditions', 'papetarie-storefront'); ?></a>
      <a href="#"><?php esc_html_e('Privacy Policy', 'papetarie-storefront'); ?></a>
      <a href="#"><?php esc_html_e('Sitemap', 'papetarie-storefront'); ?></a>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
