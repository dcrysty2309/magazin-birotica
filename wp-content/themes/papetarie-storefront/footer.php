<?php

defined('ABSPATH') || exit;
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
$logo_image = get_stylesheet_directory_uri() . '/assets/images/logo-supplyhub-cropped.png';
?>
</div>

<footer class="pap-footer">
  <div class="pap-shell pap-footer-inner">
    <div class="pap-footer-brand">
      <a class="pap-footer-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Pagina principala', 'papetarie-storefront'); ?>">
        <?php if (papetarie_storefront_has_real_logo()) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <img src="<?php echo esc_url($logo_image); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
        <?php endif; ?>
      </a>

      <p class="pap-footer-description"><?php esc_html_e('Papetarie pentru birou si scoala. Simplu, ordonat, fara zgomot vizual.', 'papetarie-storefront'); ?></p>

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
      <h3><?php esc_html_e('Ajutor', 'papetarie-storefront'); ?></h3>
      <?php
      wp_nav_menu(
          [
              'theme_location' => 'footer-help',
              'container' => false,
              'menu_class' => 'pap-footer-menu',
              'fallback_cb' => static function (): void {
                  echo '<ul class="pap-footer-menu"><li><a href="#">Contact</a></li><li><a href="#">Livrare</a></li><li><a href="#">Intrebari frecvente</a></li></ul>';
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
          <h3 class="pap-footer-widget-title"><?php esc_html_e('Newsletter', 'papetarie-storefront'); ?></h3>
          <p class="pap-footer-newsletter-copy"><?php esc_html_e('Noutati scurte despre stocuri si produse.', 'papetarie-storefront'); ?></p>
          <form class="pap-footer-newsletter-form" action="#" method="post">
            <input type="email" placeholder="<?php esc_attr_e('Adresa de email', 'papetarie-storefront'); ?>" aria-label="<?php esc_attr_e('Adresa de email', 'papetarie-storefront'); ?>">
            <button type="submit"><?php esc_html_e('Aboneaza-te', 'papetarie-storefront'); ?></button>
          </form>
        </section>
      <?php endif; ?>
    </div>
  </div>

  <div class="pap-footer-meta">
    <div class="pap-shell pap-footer-meta-inner">
      <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>.</p>
      <div class="pap-footer-meta-links">
        <a href="#"><?php esc_html_e('Termeni si conditii', 'papetarie-storefront'); ?></a>
        <a href="#"><?php esc_html_e('Politica de confidentialitate', 'papetarie-storefront'); ?></a>
      </div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
