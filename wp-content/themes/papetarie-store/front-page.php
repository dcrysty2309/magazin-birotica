<?php get_header(); ?>

<main>
  <section class="hero">
    <div class="hero-card">
      <div class="eyebrow"><?php esc_html_e('Magazin online de papetarie', 'papetarie-store'); ?></div>
      <h1><?php esc_html_e('Instrumente care fac biroul sa arate bine si sa lucreze corect.', 'papetarie-store'); ?></h1>
      <p><?php esc_html_e('Pornim cu o tema custom simpla, apoi o extindem pentru produse, categorii, filtre si checkout. Structura este pregatita pentru WooCommerce.', 'papetarie-store'); ?></p>
      <div class="hero-actions">
        <a class="button-link primary" href="<?php echo esc_url(admin_url()); ?>"><?php esc_html_e('Admin WordPress', 'papetarie-store'); ?></a>
        <a class="button-link secondary" href="#de-ce-noi"><?php esc_html_e('Vezi directia', 'papetarie-store'); ?></a>
      </div>
    </div>
  </section>

  <section class="hero feature-grid">
    <article class="feature-card">
      <h2><?php esc_html_e('Caiete si agende', 'papetarie-store'); ?></h2>
      <p><?php esc_html_e('Produse organizate clar, cu accent pe selectie rapida si imagini curate.', 'papetarie-store'); ?></p>
    </article>
    <article class="feature-card">
      <h2><?php esc_html_e('Scris si desen', 'papetarie-store'); ?></h2>
      <p><?php esc_html_e('Pixuri, markere, seturi creative si accesorii pentru birou sau scoala.', 'papetarie-store'); ?></p>
    </article>
    <article class="feature-card">
      <h2><?php esc_html_e('Cadouri de birou', 'papetarie-store'); ?></h2>
      <p><?php esc_html_e('O baza buna pentru pachete tematice, promotii sezoniere si colectii curate.', 'papetarie-store'); ?></p>
    </article>
  </section>

  <section id="de-ce-noi" class="content-section">
    <div class="section-panel">
      <h2><?php esc_html_e('Ce construim de aici', 'papetarie-store'); ?></h2>
      <p><?php esc_html_e('Urmatorii pasi firesti sunt WooCommerce, pagina de arhiva pentru produse, sablon de produs, cos, checkout si setarea identitatii vizuale complete.', 'papetarie-store'); ?></p>
    </div>
  </section>
</main>

<?php get_footer(); ?>
