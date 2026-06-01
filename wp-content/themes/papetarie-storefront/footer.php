<?php

defined('ABSPATH') || exit;
?>
</div>

<footer class="pap-footer">
  <div class="pap-shell pap-footer-grid">
    <div class="pap-footer-newsletter">
      <h2><?php esc_html_e('Rămâi la curent', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Abonează-te pentru oferte, noutăți și actualizări de catalog.', 'papetarie-storefront'); ?></p>
      <form class="pap-newsletter-form">
        <input type="email" placeholder="<?php esc_attr_e('Introdu adresa de email', 'papetarie-storefront'); ?>">
        <button type="submit"><?php esc_html_e('Abonează-te', 'papetarie-storefront'); ?></button>
      </form>
    </div>

    <div class="pap-footer-links">
      <div>
        <h3><?php esc_html_e('Magazin', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-shop',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="/?page_id=7">Toate produsele</a></li><li><a href="#featured-products">Cele mai vândute</a></li><li><a href="#deals">Oferte</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('Categorii', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-categories',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">Rechizite școlare</a></li><li><a href="#">Instrumente de scris</a></li><li><a href="#">Caiete și blocnotesuri</a></li><li><a href="#">Produse din hârtie</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('Ajutor și informații', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-help',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">Contact</a></li><li><a href="#">Întrebări frecvente</a></li><li><a href="#">Informații livrare</a></li><li><a href="#">Comenzi en-gros</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
      <div>
        <h3><?php esc_html_e('Despre noi', 'papetarie-storefront'); ?></h3>
        <?php
        wp_nav_menu(
            [
                'theme_location' => 'footer-about',
                'container' => false,
                'menu_class' => 'pap-footer-menu',
                'fallback_cb' => static function (): void {
                    echo '<ul class="pap-footer-menu"><li><a href="#">Povestea noastră</a></li><li><a href="#">Sustenabilitate</a></li><li><a href="#">Cariere</a></li><li><a href="#">Noutăți</a></li></ul>';
                },
            ]
        );
        ?>
      </div>
    </div>
  </div>

  <div class="pap-shell pap-footer-bottom">
    <p>© 2026 <?php bloginfo('name'); ?>. <?php esc_html_e('Toate drepturile rezervate.', 'papetarie-storefront'); ?></p>
    <div>
      <a href="#"><?php esc_html_e('Termeni și condiții', 'papetarie-storefront'); ?></a>
      <a href="#"><?php esc_html_e('Politica de confidențialitate', 'papetarie-storefront'); ?></a>
      <a href="#"><?php esc_html_e('Hartă site', 'papetarie-storefront'); ?></a>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
