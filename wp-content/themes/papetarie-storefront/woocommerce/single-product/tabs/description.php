<?php

defined('ABSPATH') || exit;

global $product, $post;

$tag_names = $product instanceof WC_Product ? wp_list_pluck(get_the_terms($product->get_id(), 'product_tag') ?: [], 'name') : [];

// Randare uniforma a descrierii (proza recompusa din hard-wrap, randuri
// "Eticheta: valoare" grupate separat, liste "- element" convertite in
// <ul>, HTML existent neatins) - vezi
// includes/product-description.php pentru detalii si motivul euristicii.
// Continutul original din baza de date nu e modificat, doar randarea.
$raw_content = (string) ($post->post_content ?? '');
$description_html = papetarie_storefront_format_description_content($raw_content);
$description_html = str_replace(']]>', ']]&gt;', $description_html);
?>
<div class="pap-product-description-box" data-description-box>
  <?php echo $description_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <div class="pap-product-description-fade" aria-hidden="true"></div>
</div>

<button type="button" class="pap-product-read-more" data-read-more>
  <span><?php esc_html_e('Citește mai mult', 'papetarie-storefront'); ?></span>
  <span class="pap-product-read-more-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron-down'); ?></span>
</button>

<?php if (!empty($tag_names)) : ?>
  <ul class="pap-product-tags">
    <?php foreach ($tag_names as $tag_name) : ?>
      <li><?php echo esc_html($tag_name); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
