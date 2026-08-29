<?php

defined('ABSPATH') || exit;

global $product, $post;

$tag_names = $product instanceof WC_Product ? wp_list_pluck(get_the_terms($product->get_id(), 'product_tag') ?: [], 'name') : [];

// Feed-ul Aperta contine des line-break-uri "dure" in mijlocul unei
// propozitii/paragraf (text hard-wrapped la o anumita latime la sursa),
// nu paragrafe reale - wpautop() (folosit de the_content()) transforma
// orice linie noua simpla intr-un <br>, facand descrierea sa arate ca o
// insiruire de fragmente rupte in loc de paragrafe normale. Randurile
// goale reale (\n\n, adica paragrafe distincte in text) raman neatinse -
// doar liniile simple izolate devin un spatiu, nu o rupere vizibila.
// Continutul original din baza de date nu e modificat, doar randarea.
$raw_content = (string) ($post->post_content ?? '');
$normalized_content = str_replace(["\r\n", "\r"], "\n", $raw_content);
$normalized_content = (string) preg_replace('/(?<!\n)\n(?!\n)/', ' ', $normalized_content);

$description_html = apply_filters('the_content', $normalized_content);
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
