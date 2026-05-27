<?php

defined('ABSPATH') || exit;

$asset_base = get_stylesheet_directory_uri() . '/assets/images';

$categories = [
    ['name' => 'School Supplies', 'icon' => '🎒'],
    ['name' => 'Writing Instruments', 'icon' => '🖊️'],
    ['name' => 'Notebooks & Pads', 'icon' => '📓'],
    ['name' => 'Paper Products', 'icon' => '📄'],
    ['name' => 'Files & Folders', 'icon' => '🗂️'],
    ['name' => 'Desk Organization', 'icon' => '🗃️'],
    ['name' => 'Art & Craft', 'icon' => '🎨'],
    ['name' => 'Technology', 'icon' => '🧮'],
];

$products = [
    ['name' => 'A5 Spiral Notebook', 'details' => '160 pages, ruled', 'sku' => 'SKU: NB-A5-160-BL', 'price' => '$1.45', 'image' => $asset_base . '/product-notebook-a5.png'],
    ['name' => 'Ballpoint Pens', 'details' => 'Blue ink, medium tip', 'sku' => 'SKU: PEN-BP-M-BL-10', 'price' => '$2.25', 'image' => $asset_base . '/product-pens-blue.png'],
    ['name' => 'Highlighters', 'details' => 'Assorted colors', 'sku' => 'SKU: HLT-CH-4ASST', 'price' => '$1.75', 'image' => $asset_base . '/product-highlighters.png'],
    ['name' => 'Lever Arch File A4', 'details' => '75mm, assorted colors', 'sku' => 'SKU: FIL-LAF-A4-75', 'price' => '$3.95', 'image' => $asset_base . '/product-binders-a4.png'],
    ['name' => 'Sticky Notes 76x76', 'details' => 'Neon, 100 sheets', 'sku' => 'SKU: STK-76-NE100', 'price' => '$0.85', 'image' => $asset_base . '/product-sticky-notes.png'],
    ['name' => 'Mesh Desk Organizer', 'details' => 'Multi-compartment', 'sku' => 'SKU: DSK-MSH-ORG', 'price' => '$6.50', 'image' => $asset_base . '/product-mesh-organizer.png'],
];

$brands = ['BIC', 'STAEDTLER', 'PAPERONE', 'Leuchtturm1917', 'Fellowes', 'tesa'];

get_header();
?>
<main id="primary" class="site-main pap-homepage">
  <section class="pap-hero">
    <div class="pap-shell pap-hero-grid">
      <div class="pap-hero-copy">
        <h1><?php esc_html_e('Everything you need.', 'papetarie-storefront'); ?> <span><?php esc_html_e('Every day.', 'papetarie-storefront'); ?></span></h1>
        <p><?php esc_html_e('Practical stationery for school, office and everywhere in between. Built around notebooks, writing tools, desk organization and fast bulk ordering.', 'papetarie-storefront'); ?></p>

        <div class="pap-hero-badges">
          <div><strong><?php esc_html_e('Quality Assured', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('Trusted brands', 'papetarie-storefront'); ?></span></div>
          <div><strong><?php esc_html_e('Great Value', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('Everyday low prices', 'papetarie-storefront'); ?></span></div>
          <div><strong><?php esc_html_e('Fast Delivery', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('Across the country', 'papetarie-storefront'); ?></span></div>
        </div>

        <div class="pap-hero-actions">
          <a class="pap-button pap-button-primary" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Shop All Products', 'papetarie-storefront'); ?></a>
          <a class="pap-button pap-button-secondary" href="#bulk-order"><?php esc_html_e('Bulk Order & Save', 'papetarie-storefront'); ?></a>
        </div>
      </div>

      <div class="pap-hero-visual" aria-hidden="true">
        <div class="pap-hex-cluster">
          <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <img src="<?php echo esc_url($asset_base . '/hero-stationery-desk.png'); ?>" alt="" loading="eager">
      </div>
    </div>
  </section>

  <section class="pap-shell pap-categories" aria-label="<?php esc_attr_e('Homepage categories', 'papetarie-storefront'); ?>">
    <?php foreach ($categories as $category) : ?>
      <article class="pap-category-card">
        <div class="pap-category-icon"><?php echo esc_html($category['icon']); ?></div>
        <h2><?php echo esc_html($category['name']); ?></h2>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('View all', 'papetarie-storefront'); ?></a>
      </article>
    <?php endforeach; ?>
  </section>

  <section id="featured-products" class="pap-shell pap-featured">
    <div class="pap-section-head">
      <h2><?php esc_html_e('Featured Products', 'papetarie-storefront'); ?></h2>
      <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('View all products', 'papetarie-storefront'); ?></a>
    </div>

    <div class="pap-product-grid">
      <?php foreach ($products as $product) : ?>
        <article class="pap-product-card">
          <button class="pap-wishlist" type="button" aria-label="<?php esc_attr_e('Add to wishlist', 'papetarie-storefront'); ?>">♡</button>
          <div class="pap-product-thumb">
            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>" loading="lazy">
          </div>
          <h3><?php echo esc_html($product['name']); ?></h3>
          <p><?php echo esc_html($product['details']); ?></p>
          <span class="pap-product-sku"><?php echo esc_html($product['sku']); ?></span>
          <strong class="pap-price"><?php echo esc_html($product['price']); ?></strong>
          <div class="pap-product-actions">
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Add to Cart', 'papetarie-storefront'); ?></a>
            <span>- 1 +</span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="deals" class="pap-shell pap-promos">
    <article class="pap-promo pap-promo-orange">
      <div>
        <span><?php esc_html_e('BACK TO SCHOOL', 'papetarie-storefront'); ?></span>
        <h3><?php esc_html_e('Stock up & save', 'papetarie-storefront'); ?></h3>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Shop essentials', 'papetarie-storefront'); ?></a>
      </div>
      <div class="pap-promo-emoji">🎒</div>
    </article>

    <article id="bulk-order" class="pap-promo pap-promo-navy">
      <div>
        <span><?php esc_html_e('BULK ORDER', 'papetarie-storefront'); ?></span>
        <h3><?php esc_html_e('Better prices for businesses & schools', 'papetarie-storefront'); ?></h3>
        <a href="#quote"><?php esc_html_e('Learn more', 'papetarie-storefront'); ?></a>
      </div>
      <div class="pap-promo-boxes">📦📦📦</div>
    </article>

    <article class="pap-promo pap-promo-light">
      <div>
        <span><?php esc_html_e('NEW CATALOGUE', 'papetarie-storefront'); ?></span>
        <h3><?php esc_html_e('2024 / 2025', 'papetarie-storefront'); ?></h3>
        <a href="<?php echo esc_url(home_url('/supplier-evident-catalog.pdf')); ?>"><?php esc_html_e('Browse now', 'papetarie-storefront'); ?></a>
      </div>
      <div class="pap-promo-emoji">📘</div>
    </article>
  </section>

  <section class="pap-shell pap-benefits">
    <h2><?php esc_html_e('Why Shop With Us?', 'papetarie-storefront'); ?></h2>
    <div class="pap-benefit-grid">
      <div><strong><?php esc_html_e('Wide Range', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('10,000+ products', 'papetarie-storefront'); ?></span></div>
      <div><strong><?php esc_html_e('Trusted Quality', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('From top brands', 'papetarie-storefront'); ?></span></div>
      <div><strong><?php esc_html_e('Competitive Prices', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('Everyday low prices', 'papetarie-storefront'); ?></span></div>
      <div><strong><?php esc_html_e('Easy Returns', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('Hassle-free returns', 'papetarie-storefront'); ?></span></div>
      <div><strong><?php esc_html_e('Expert Support', 'papetarie-storefront'); ?></strong><span><?php esc_html_e('We’re here to help', 'papetarie-storefront'); ?></span></div>
    </div>

    <div id="brands" class="pap-brand-row">
      <?php foreach ($brands as $brand) : ?>
        <span><?php echo esc_html($brand); ?></span>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="quote" class="pap-shell pap-quote-cta">
    <div class="pap-quote-copy">
      <h2><?php esc_html_e('Need it in bulk?', 'papetarie-storefront'); ?></h2>
      <p><?php esc_html_e('Get a custom quote in minutes. Special pricing, fast response and dedicated support for schools, offices and business orders.', 'papetarie-storefront'); ?></p>
    </div>
    <a class="pap-button pap-button-primary" href="#"><?php esc_html_e('Request a Quote', 'papetarie-storefront'); ?></a>
  </section>
</main>
<?php
get_footer();
