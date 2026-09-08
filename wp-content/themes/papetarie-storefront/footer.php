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
        <p class="pap-footer-meta-legal">ARTFLEX SRL, CUI: 49485790, Reg. Com.: J2024000512123, Florești, str. Lacului, nr. 2, jud. Cluj &middot; <a href="mailto:contact@notix.ro">contact@notix.ro</a> &middot; <a href="tel:0740123456">0740 123 456</a> &middot; <a href="#" data-pap-cookie-reopen><?php esc_html_e('Setări cookies', 'papetarie-storefront'); ?></a></p>
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
        <p class="pap-footer-meta-legal">ARTFLEX SRL, CUI: 49485790, Reg. Com.: J2024000512123, Florești, str. Lacului, nr. 2, jud. Cluj &middot; <a href="mailto:contact@notix.ro">contact@notix.ro</a> &middot; <a href="tel:0740123456">0740 123 456</a> &middot; <a href="#" data-pap-cookie-reopen><?php esc_html_e('Setări cookies', 'papetarie-storefront'); ?></a></p>
      </div>
    </div>
  </footer>
<?php endif; ?>

<div class="pap-cookie-banner" id="pap-cookie-banner" role="dialog" aria-live="polite" aria-label="<?php esc_attr_e('Preferințe cookie-uri', 'papetarie-storefront'); ?>" hidden>
  <div class="pap-shell pap-cookie-banner-inner">
    <p class="pap-cookie-banner-text">
      <?php
      printf(
          /* translators: %s: link to the cookie policy page */
          esc_html__('Folosim cookie-uri strict necesare funcționării site-ului și, doar cu acordul tău, cookie-uri de analiză, ca să înțelegem cum e folosit site-ul. Poți afla mai multe în %s.', 'papetarie-storefront'),
          '<a href="' . esc_url(home_url('/politica-de-cookie-uri/')) . '" target="_blank">' . esc_html__('Politica de cookie-uri', 'papetarie-storefront') . '</a>'
      );
      ?>
    </p>
    <div class="pap-cookie-banner-actions">
      <button type="button" class="pap-cookie-btn pap-cookie-btn--ghost" data-pap-cookie-settings><?php esc_html_e('Setări cookies', 'papetarie-storefront'); ?></button>
      <button type="button" class="pap-cookie-btn pap-cookie-btn--outline" data-pap-cookie-refuse><?php esc_html_e('Refuză cookie-urile neesențiale', 'papetarie-storefront'); ?></button>
      <button type="button" class="pap-cookie-btn pap-cookie-btn--primary" data-pap-cookie-accept-all><?php esc_html_e('Acceptă toate', 'papetarie-storefront'); ?></button>
    </div>
  </div>
</div>

<div class="pap-cookie-modal" id="pap-cookie-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Setări cookies', 'papetarie-storefront'); ?>" hidden>
  <div class="pap-cookie-modal-panel">
    <div class="pap-cookie-modal-head">
      <h2><?php esc_html_e('Setări cookies', 'papetarie-storefront'); ?></h2>
      <button type="button" class="pap-cookie-modal-close" data-pap-cookie-close aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>">&times;</button>
    </div>
    <div class="pap-cookie-modal-body">
      <div class="pap-cookie-category">
        <div class="pap-cookie-category-head">
          <span class="pap-cookie-category-title"><?php esc_html_e('Necesare', 'papetarie-storefront'); ?></span>
          <label class="pap-cookie-switch pap-cookie-switch--locked">
            <input type="checkbox" checked disabled>
            <span class="pap-cookie-switch-track"></span>
          </label>
        </div>
        <p class="pap-cookie-category-desc"><?php esc_html_e('Necesare pentru funcționarea site-ului: coșul de cumpărături, sesiunea de autentificare, finalizarea comenzii. Nu pot fi dezactivate.', 'papetarie-storefront'); ?></p>
      </div>
      <div class="pap-cookie-category">
        <div class="pap-cookie-category-head">
          <span class="pap-cookie-category-title"><?php esc_html_e('Analiză', 'papetarie-storefront'); ?></span>
          <label class="pap-cookie-switch">
            <input type="checkbox" data-pap-cookie-analytics>
            <span class="pap-cookie-switch-track"></span>
          </label>
        </div>
        <p class="pap-cookie-category-desc"><?php esc_html_e('Ne ajută să înțelegem cum e folosit site-ul (pagini vizitate, trafic), ca să-l îmbunătățim. Se activează doar cu acordul tău.', 'papetarie-storefront'); ?></p>
      </div>
    </div>
    <div class="pap-cookie-modal-actions">
      <button type="button" class="pap-cookie-btn pap-cookie-btn--outline" data-pap-cookie-save><?php esc_html_e('Salvează preferințele', 'papetarie-storefront'); ?></button>
      <button type="button" class="pap-cookie-btn pap-cookie-btn--primary" data-pap-cookie-accept-all-modal><?php esc_html_e('Acceptă toate', 'papetarie-storefront'); ?></button>
    </div>
  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
