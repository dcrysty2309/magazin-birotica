<?php

defined('ABSPATH') || exit;
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
$logo_image = get_stylesheet_directory_uri() . '/assets/images/logo-notix.png';
$logo_image_on_dark = get_stylesheet_directory_uri() . '/assets/images/logo-notix-on-dark.png';
?>
</div>

<?php if (function_exists('papetarie_storefront_is_checkout_or_order_received_page') && papetarie_storefront_is_checkout_or_order_received_page()) : ?>
  <footer class="pap-footer pap-footer--checkout">
    <div class="pap-footer-meta">
      <div class="pap-shell pap-footer-meta-inner">
        <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Toate drepturile rezervate.', 'papetarie-storefront'); ?></p>
        <p class="pap-footer-meta-legal">ARTFLEX SRL, CUI: 49485790, Reg. Com.: J2024000512123</p>
      </div>
    </div>
  </footer>
<?php else : ?>
  <footer class="pap-footer">
    <div class="pap-shell pap-footer-inner">
      <div class="pap-footer-brand">
        <a class="pap-footer-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>" style="background-image: url('<?php echo esc_url($logo_image); ?>');"></a>

        <p class="pap-footer-description"><?php esc_html_e('Papetarie pentru birou si scoala. Simplu, ordonat, fara zgomot vizual.', 'papetarie-storefront'); ?></p>

        <div class="pap-footer-anpc-badges">
          <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="nofollow noopener">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/anpc-sal.svg'); ?>" alt="<?php esc_attr_e('Soluționarea Alternativă a Litigiilor', 'papetarie-storefront'); ?>" loading="lazy" width="200" height="40">
          </a>
          <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="nofollow noopener">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/anpc-sol.svg'); ?>" alt="<?php esc_attr_e('Soluționarea Online a Litigiilor', 'papetarie-storefront'); ?>" loading="lazy" width="200" height="40">
          </a>
        </div>
      </div>

      <div class="pap-footer-links-group">
        <h3><?php esc_html_e('Magazin', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-shop',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function () use ($shop_url, $cart_url): void {
                    echo '<ul class="pap-footer-menu"><li><a href="' . esc_url($shop_url) . '">Toate produsele</a></li><li><a href="#featured-products">Recomandate</a></li><li><a href="' . esc_url($cart_url) . '">Cos</a></li></ul>';
                },
            ]
        );
        ?>
      </div>

      <div class="pap-footer-links-group">
        <h3><?php esc_html_e('Ajutor si contact', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-help',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li><li><a href="' . esc_url(home_url('/livrare/')) . '">Livrare</a></li><li><a href="' . esc_url(home_url('/politica-de-retur/')) . '">Politica de retur</a></li><li><a href="' . esc_url(home_url('/intrebari-frecvente/')) . '">Intrebari frecvente</a></li><li><a href="' . esc_url(home_url('/garantie/')) . '">Garantie</a></li></ul>';
                },
            ]
        );
        ?>
      </div>

      <div class="pap-footer-links-group">
        <h3><?php esc_html_e('Companie', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-about',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="' . esc_url(home_url('/despre-noi/')) . '">Despre NOTIX</a></li><li><a href="' . esc_url(home_url('/termeni-si-conditii/')) . '">Termeni si conditii</a></li><li><a href="' . esc_url(home_url('/politica-de-confidentialitate/')) . '">Confidentialitate</a></li><li><a href="' . esc_url(home_url('/politica-de-cookie-uri/')) . '">Cookie-uri</a></li></ul>';
                },
            ]
        );
        ?>
      </div>

      <div class="pap-footer-newsletter">
        <?php if (is_active_sidebar('footer-newsletter')) : ?>
          <?php dynamic_sidebar('footer-newsletter'); ?>
        <?php else : ?>
          <section class="pap-footer-newsletter-widget">
            <h3 class="pap-footer-widget-title"><?php esc_html_e('Noutati pe email', 'papetarie-storefront'); ?></h3>
            <p class="pap-footer-newsletter-copy"><?php esc_html_e('Stocuri noi si oferte, fara spam.', 'papetarie-storefront'); ?></p>
            <form class="pap-footer-newsletter-form" data-pap-newsletter-form>
              <input type="email" required placeholder="<?php esc_attr_e('Adresa ta de email', 'papetarie-storefront'); ?>" aria-label="<?php esc_attr_e('Adresa de email', 'papetarie-storefront'); ?>">
              <button type="submit"><?php esc_html_e('Aboneaza-te', 'papetarie-storefront'); ?></button>
            </form>
            <p class="pap-footer-newsletter-feedback" data-pap-newsletter-feedback hidden></p>
          </section>
        <?php endif; ?>
      </div>
    </div>

    <div class="pap-footer-meta">
      <div class="pap-shell pap-footer-meta-inner">
        <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Toate drepturile rezervate.', 'papetarie-storefront'); ?></p>
        <p class="pap-footer-meta-legal">ARTFLEX SRL, CUI: 49485790, Reg. Com.: J2024000512123</p>
      </div>
    </div>
  </footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
