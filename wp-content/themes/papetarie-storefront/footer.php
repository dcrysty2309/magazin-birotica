<?php

defined('ABSPATH') || exit;
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
$logo_image = get_stylesheet_directory_uri() . '/assets/images/logo-notix.png';

$pap_footer_link = static function (string $url, string $icon, string $color, string $label): string {
    $iconMarkup = function_exists('papetarie_storefront_icon') ? papetarie_storefront_icon($icon) : '';
    return '<li><a href="' . esc_url($url) . '"><span class="pap-footer-link-icon" style="--ic:' . esc_attr($color) . '">' . $iconMarkup . '</span>' . esc_html($label) . '</a></li>';
};
?>
</div>

<?php if (function_exists('papetarie_storefront_is_checkout_or_order_received_page') && papetarie_storefront_is_checkout_or_order_received_page()) : ?>
  <footer class="pap-footer pap-footer--checkout">
    <div class="pap-footer-meta">
      <div class="pap-shell pap-footer-meta-inner pap-footer-meta-inner--checkout">
        <p>© 2026 SupplyHub</p>
        <div class="pap-footer-meta-links">
          <a href="<?php echo esc_url(home_url('/termeni-si-conditii/')); ?>"><?php esc_html_e('Termeni și condiții', 'papetarie-storefront'); ?></a>
          <a href="<?php echo esc_url(home_url('/politica-de-confidentialitate/')); ?>"><?php esc_html_e('Politica de confidențialitate', 'papetarie-storefront'); ?></a>
          <a href="<?php echo esc_url(home_url('/politica-de-cookie-uri/')); ?>"><?php esc_html_e('Politica de cookie-uri', 'papetarie-storefront'); ?></a>
        </div>
      </div>
    </div>
  </footer>
<?php else : ?>
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

        <div class="pap-footer-anpc">
          <a class="pap-footer-anpc-badge" href="https://anpc.ro/ce-este-sal/" target="_blank" rel="noopener">
            <span class="pap-footer-anpc-mark">ANPC</span>
            <span class="pap-footer-anpc-text">
              <?php esc_html_e('Solutionarea alternativa a litigiilor', 'papetarie-storefront'); ?>
              <span class="pap-footer-anpc-btn"><?php esc_html_e('Detalii', 'papetarie-storefront'); ?></span>
            </span>
          </a>
          <a class="pap-footer-anpc-badge pap-footer-anpc-badge--sol" href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener">
            <span class="pap-footer-anpc-text pap-footer-anpc-text--center">
              <?php esc_html_e('Solutionarea online a litigiilor', 'papetarie-storefront'); ?>
              <span class="pap-footer-anpc-btn"><?php esc_html_e('Detalii', 'papetarie-storefront'); ?></span>
            </span>
          </a>
        </div>

        <style>
          .pap-footer-anpc { display: flex; flex-direction: column; gap: 8px; margin: 16px 0 0; max-width: 260px; }
          .pap-footer-anpc-badge {
            display: flex; align-items: center; gap: 10px;
            border: 1.5px solid #0d2e61; border-radius: 8px; padding: 8px 10px;
            text-decoration: none; background: #fff;
          }
          .pap-footer-anpc-mark { font-weight: 900; font-size: 11px; letter-spacing: .02em; color: #c22029; flex-shrink: 0; }
          .pap-footer-anpc-text {
            display: flex; align-items: center; justify-content: space-between; gap: 8px; flex: 1;
            font-size: 9.5px; font-weight: 800; letter-spacing: .02em; text-transform: uppercase; color: #0d2e61; line-height: 1.3;
          }
          .pap-footer-anpc-text--center { justify-content: center; flex-direction: column; text-align: center; gap: 6px; }
          .pap-footer-anpc-btn {
            flex-shrink: 0; font-size: 9px; font-weight: 800; letter-spacing: .04em;
            background: #0d2e61; color: #fff; padding: 3px 9px; border-radius: 999px; text-transform: uppercase;
          }
          .pap-footer-menu a { display: flex; align-items: center; gap: 18px; }
          .pap-footer-link-icon {
            flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;
            width: 16px; height: 14px;
            clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
            background: var(--ic, #0d2e61);
          }
          .pap-footer-link-icon svg { width: 8px; height: 8px; color: #fff; }
        </style>

      </div>

      <div class="pap-footer-links-group">
        <h3><?php esc_html_e('Magazin', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-shop',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function () use ($shop_url, $cart_url, $pap_footer_link): void {
                    echo '<ul class="pap-footer-menu">'
                        . $pap_footer_link($shop_url, 'catalog', '#0d2e61', 'Toate produsele')
                        . $pap_footer_link('#featured-products', 'star', '#ff5b1f', 'Recomandate')
                        . $pap_footer_link($cart_url, 'cart', '#8a32b0', 'Cos')
                        . '</ul>';
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
                'fallback_cb' => static function () use ($pap_footer_link): void {
                    echo '<ul class="pap-footer-menu">'
                        . $pap_footer_link('#', 'headset-outline', '#0d2e61', 'Contact')
                        . $pap_footer_link(home_url('/livrare/'), 'truck-outline', '#0d5e4a', 'Livrare')
                        . $pap_footer_link(home_url('/politica-de-retur/'), 'undo', '#ff5b1f', 'Politica de retur')
                        . $pap_footer_link(home_url('/intrebari-frecvente/'), 'help', '#f3373d', 'Intrebari frecvente')
                        . $pap_footer_link(home_url('/garantie/'), 'check-circle', '#0d5e4a', 'Garantie')
                        . '</ul>';
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
                'theme_location' => 'footer-company',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function () use ($pap_footer_link): void {
                    echo '<ul class="pap-footer-menu">'
                        . $pap_footer_link(home_url('/despre-noi/'), 'heart-outline', '#8a32b0', 'Despre NOTIX')
                        . $pap_footer_link(home_url('/termeni-si-conditii/'), 'file-lines-outline', '#0d2e61', 'Termeni si conditii')
                        . $pap_footer_link(home_url('/politica-de-confidentialitate/'), 'shield', '#8a32b0', 'Confidentialitate')
                        . $pap_footer_link(home_url('/politica-de-cookie-uri/'), 'cookie', '#ff5b1f', 'Cookie-uri')
                        . '</ul>';
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
          <a href="<?php echo esc_url(home_url('/termeni-si-conditii/')); ?>"><?php esc_html_e('Termeni si conditii', 'papetarie-storefront'); ?></a>
          <a href="<?php echo esc_url(home_url('/politica-de-confidentialitate/')); ?>"><?php esc_html_e('Politica de confidentialitate', 'papetarie-storefront'); ?></a>
          <a href="<?php echo esc_url(home_url('/politica-de-cookie-uri/')); ?>"><?php esc_html_e('Politica de cookie-uri', 'papetarie-storefront'); ?></a>
        </div>
      </div>
    </div>
  </footer>
<?php endif; ?>

<div class="pap-cookie-banner" id="pap-cookie-banner" hidden>
  <div class="pap-cookie-banner-inner">
    <p class="pap-cookie-banner-text">
      <?php esc_html_e('Folosim cookie-uri strict necesare pentru funcționarea site-ului (coș de cumpărături, sesiune). Detalii în', 'papetarie-storefront'); ?>
      <a href="<?php echo esc_url(home_url('/politica-de-cookie-uri/')); ?>"><?php esc_html_e('Politica de cookie-uri', 'papetarie-storefront'); ?></a>.
    </p>
    <div class="pap-cookie-banner-actions">
      <button type="button" class="pap-cookie-banner-btn pap-cookie-banner-btn--accept" id="pap-cookie-accept"><?php esc_html_e('Am înțeles', 'papetarie-storefront'); ?></button>
    </div>
  </div>
</div>
<style>
  .pap-cookie-banner {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 9999;
    background: var(--pap-navy, #0d2e61); color: #fff;
    box-shadow: 0 -6px 20px rgba(0,0,0,.18);
  }
  .pap-cookie-banner-inner {
    max-width: 1200px; margin: 0 auto; padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;
  }
  .pap-cookie-banner-text { margin: 0; font-size: 13.5px; line-height: 1.5; color: #dbe3ee; flex: 1; min-width: 240px; }
  .pap-cookie-banner-text a { color: #fff; text-decoration: underline; }
  .pap-cookie-banner-btn {
    border: none; border-radius: 8px; padding: 10px 20px; font-weight: 700; font-size: 13.5px; cursor: pointer; white-space: nowrap;
  }
  .pap-cookie-banner-btn--accept { background: var(--pap-orange, #ff5b1f); color: #fff; }
  .pap-cookie-banner-btn--accept:hover { background: var(--pap-orange-deep, #f0440b); }
</style>
<script>
  (function () {
    var COOKIE_NAME = 'pap_cookie_consent';
    var banner = document.getElementById('pap-cookie-banner');
    var acceptBtn = document.getElementById('pap-cookie-accept');
    if (!banner || !acceptBtn) { return; }

    function getCookie(name) {
      var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
      return match ? decodeURIComponent(match[1]) : null;
    }
    function setCookie(name, value, days) {
      var d = new Date();
      d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
      document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
    }

    if (!getCookie(COOKIE_NAME)) {
      banner.hidden = false;
    }

    acceptBtn.addEventListener('click', function () {
      setCookie(COOKIE_NAME, 'accepted', 365);
      banner.hidden = true;
      /* Notă pentru dezvoltator: dacă se adaugă Google Analytics/Meta Pixel,
         acele scripturi ar trebui încărcate abia după acest consimțământ,
         nu necondiționat la încărcarea paginii. */
    });
  })();
</script>

<?php wp_footer(); ?>
</body>
</html>
