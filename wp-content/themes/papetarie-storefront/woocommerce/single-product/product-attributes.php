<?php

defined('ABSPATH') || exit;

if (!$product_attributes) {
    return;
}
?>
<table class="woocommerce-product-attributes shop_attributes" aria-label="<?php esc_attr_e('Detalii produs', 'papetarie-storefront'); ?>">
  <?php foreach ($product_attributes as $product_attribute_key => $product_attribute) : ?>
    <tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--<?php echo esc_attr($product_attribute_key); ?>">
      <th class="woocommerce-product-attributes-item__label" scope="row"><?php echo wp_kses_post(papetarie_storefront_normalize_attribute_label((string) $product_attribute['label'])); ?></th>
      <td class="woocommerce-product-attributes-item__value"><?php echo wp_kses_post($product_attribute['value']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
    </tr>
  <?php endforeach; ?>
</table>
