<?php

defined('ABSPATH') || exit;

get_header();
?>
<main id="primary" class="site-main pap-styleguide-page">
  <section class="pap-styleguide-hero">
    <div class="pap-shell">
      <p class="pap-styleguide-kicker"><?php esc_html_e('Sistem de brand', 'papetarie-storefront'); ?></p>
      <h1><?php esc_html_e('Ghid vizual SupplyHub', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Pagina asta definește direcția vizuală și de produs pentru storefront: logo, culori, tipografie, iconuri, componente și planul de implementare care ne mută de la prototype la magazin online real.', 'papetarie-storefront'); ?></p>
    </div>
  </section>

  <section class="pap-shell pap-styleguide-section">
    <div class="pap-styleguide-grid pap-styleguide-grid-brand">
      <article class="pap-styleguide-card">
        <h2><?php esc_html_e('Logo principal', 'papetarie-storefront'); ?></h2>
        <div class="pap-styleguide-logo-wrap">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-supplyhub.svg'); ?>" alt="<?php esc_attr_e('Logo SupplyHub', 'papetarie-storefront'); ?>">
        </div>
      </article>
      <article class="pap-styleguide-card">
        <h2><?php esc_html_e('Obiective de design', 'papetarie-storefront'); ?></h2>
        <ul class="pap-styleguide-list">
          <li><?php esc_html_e('poziționare clară pentru papetărie retail și B2B', 'papetarie-storefront'); ?></li>
          <li><?php esc_html_e('cumpărare rapidă cu search mare și acces clar la categorii', 'papetarie-storefront'); ?></li>
          <li><?php esc_html_e('layout comercial apropiat de referința SupplyHub', 'papetarie-storefront'); ?></li>
          <li><?php esc_html_e('limbaj vizual cu încredere ridicată, nu doar boutique sau decorativ', 'papetarie-storefront'); ?></li>
        </ul>
      </article>
    </div>
  </section>

  <section class="pap-shell pap-styleguide-section">
    <h2><?php esc_html_e('Sistem de culori', 'papetarie-storefront'); ?></h2>
    <div class="pap-color-grid">
      <div class="pap-color-swatch"><span style="background:#ff5b1f"></span><strong>Orange</strong><small>#FF5B1F</small></div>
      <div class="pap-color-swatch"><span style="background:#0d2e61"></span><strong>Navy</strong><small>#0D2E61</small></div>
      <div class="pap-color-swatch"><span style="background:#1aa8e5"></span><strong>Blue</strong><small>#1AA8E5</small></div>
      <div class="pap-color-swatch"><span style="background:#8a32b0"></span><strong>Purple</strong><small>#8A32B0</small></div>
      <div class="pap-color-swatch"><span style="background:#f3373d"></span><strong>Red</strong><small>#F3373D</small></div>
      <div class="pap-color-swatch"><span style="background:#0d5e4a"></span><strong>Green</strong><small>#0D5E4A</small></div>
    </div>
  </section>

  <section class="pap-shell pap-styleguide-section">
    <div class="pap-styleguide-grid">
      <article class="pap-styleguide-card">
        <h2><?php esc_html_e('Tipografie', 'papetarie-storefront'); ?></h2>
        <div class="pap-styleguide-type">
          <h3><?php esc_html_e('Tot ce ai nevoie.', 'papetarie-storefront'); ?></h3>
          <p><?php esc_html_e('Headline comercial mare, contrast puternic și line-height compact. Acesta este tonul pentru hero și promoțiile cheie.', 'papetarie-storefront'); ?></p>
          <small><?php esc_html_e('Textele de UI rămân directe, utilitare și orientate spre comerț.', 'papetarie-storefront'); ?></small>
        </div>
      </article>
      <article class="pap-styleguide-card">
        <h2><?php esc_html_e('Butoane și inputuri', 'papetarie-storefront'); ?></h2>
        <div class="pap-styleguide-actions">
          <a class="pap-button pap-button-primary" href="#"><?php esc_html_e('Acțiune principală', 'papetarie-storefront'); ?></a>
          <a class="pap-button pap-button-secondary" href="#"><?php esc_html_e('Acțiune secundară', 'papetarie-storefront'); ?></a>
        </div>
        <form class="pap-search pap-styleguide-search" action="#">
          <input type="search" value="<?php esc_attr_e('Caută după produs, SKU sau cuvânt cheie...', 'papetarie-storefront'); ?>">
          <select><option><?php esc_html_e('Toate categoriile', 'papetarie-storefront'); ?></option></select>
          <button type="submit"><?php echo papetarie_storefront_icon('search'); ?><span><?php esc_html_e('Caută', 'papetarie-storefront'); ?></span></button>
        </form>
      </article>
    </div>
  </section>

  <section class="pap-shell pap-styleguide-section">
    <h2><?php esc_html_e('Iconuri și limbaj de încredere', 'papetarie-storefront'); ?></h2>
    <div class="pap-icon-grid">
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('account'); ?></span><strong><?php esc_html_e('Cont', 'papetarie-storefront'); ?></strong></div>
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('upload'); ?></span><strong><?php esc_html_e('Comandă rapidă', 'papetarie-storefront'); ?></strong></div>
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('cart'); ?></span><strong><?php esc_html_e('Coș', 'papetarie-storefront'); ?></strong></div>
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('shield'); ?></span><strong><?php esc_html_e('Calitate garantată', 'papetarie-storefront'); ?></strong></div>
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('tag'); ?></span><strong><?php esc_html_e('Preț bun', 'papetarie-storefront'); ?></strong></div>
      <div class="pap-icon-card"><span><?php echo papetarie_storefront_icon('truck'); ?></span><strong><?php esc_html_e('Livrare rapidă', 'papetarie-storefront'); ?></strong></div>
    </div>
  </section>

  <section class="pap-shell pap-styleguide-section">
    <h2><?php esc_html_e('Plan de execuție', 'papetarie-storefront'); ?></h2>
    <div class="pap-roadmap">
      <article class="pap-roadmap-step"><strong>Faza 1</strong><span><?php esc_html_e('Fundament de brand: logo, header, iconografie, comportament search, sistem de spacing.', 'papetarie-storefront'); ?></span></article>
      <article class="pap-roadmap-step"><strong>Faza 2</strong><span><?php esc_html_e('Alinierea homepage-ului la referință: imagini reale, carduri de categorii, produse recomandate, bannere promo.', 'papetarie-storefront'); ?></span></article>
      <article class="pap-roadmap-step"><strong>Faza 3</strong><span><?php esc_html_e('Date reale de comerț: categorii WooCommerce, produse, logică de preț, hook-uri B2B.', 'papetarie-storefront'); ?></span></article>
      <article class="pap-roadmap-step"><strong>Faza 4</strong><span><?php esc_html_e('Template-uri de shop: arhivă, pagină produs, coș, checkout, cont, filtre de catalog.', 'papetarie-storefront'); ?></span></article>
      <article class="pap-roadmap-step"><strong>Faza 5</strong><span><?php esc_html_e('Integrare furnizori: strategie de import, sincronizare stoc, mapare SKU, curățare catalog.', 'papetarie-storefront'); ?></span></article>
    </div>
  </section>
</main>
<?php
get_footer();
