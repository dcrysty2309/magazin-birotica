<?php

defined('ABSPATH') || exit;

if (empty($related_products)) {
    return;
}

$heading = apply_filters('woocommerce_product_related_products_heading', __('Produse similare', 'papetarie-storefront'));
?>
<section class="pap-related-products pap-featured-slider-shell">
  <div class="pap-related-products-head">
    <div class="pap-related-products-title">
      <span class="pap-related-products-accent" aria-hidden="true"></span>
      <?php if ($heading) : ?>
        <h2><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
    </div>
    <div class="pap-related-products-nav">
      <button type="button" class="pap-related-nav-btn" aria-label="<?php esc_attr_e('Produse anterioare', 'papetarie-storefront'); ?>" data-featured-prev>
        <span class="pap-related-nav-icon pap-related-nav-icon--prev" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
      </button>
      <button type="button" class="pap-related-nav-btn" aria-label="<?php esc_attr_e('Produse următoare', 'papetarie-storefront'); ?>" data-featured-next>
        <span class="pap-related-nav-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
      </button>
    </div>
  </div>

  <div class="pap-featured-slider" data-featured-slider>
    <div class="pap-product-grid">
      <?php foreach ($related_products as $related_product) : ?>
        <?php papetarie_storefront_render_product_card($related_product); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
wp_reset_postdata();
