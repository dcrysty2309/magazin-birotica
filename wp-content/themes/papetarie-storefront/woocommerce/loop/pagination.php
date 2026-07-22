<?php

defined('ABSPATH') || exit;

$total = isset($total) ? $total : wc_get_loop_prop('total_pages');
$current = isset($current) ? $current : wc_get_loop_prop('current_page');
$base = isset($base) ? $base : esc_url_raw(str_replace(999999999, '%#%', remove_query_arg('add-to-cart', get_pagenum_link(999999999, false))));
$format = isset($format) ? $format : '';

if ($total <= 1) {
    return;
}

$current = max(1, (int) $current);
$per_page = (int) wc_get_loop_prop('per_page');
$total_items = (int) wc_get_loop_prop('total');
$range_start = $per_page > 0 ? (($current - 1) * $per_page) + 1 : 1;
$range_end = $per_page > 0 ? min($current * $per_page, $total_items) : $total_items;

$links = paginate_links(apply_filters(
    'woocommerce_pagination_args',
    [
        'base' => $base,
        'format' => $format,
        'add_args' => false,
        'current' => $current,
        'total' => $total,
        'prev_text' => esc_html__('Pagina anterioară', 'papetarie-storefront'),
        'next_text' => esc_html__('Pagina următoare', 'papetarie-storefront'),
        'type' => 'array',
        'end_size' => 1,
        'mid_size' => 2,
    ]
));

if (!$links) {
    return;
}
?>
<nav class="pap-archive-pagination-bar" aria-label="<?php esc_attr_e('Product Pagination', 'woocommerce'); ?>">
  <p class="pap-archive-pagination-range">
    <strong><?php echo esc_html($range_start . ' – ' . $range_end); ?></strong>
    <?php echo esc_html(sprintf(
        /* translators: %d: total product count */
        __('din %d de produse', 'papetarie-storefront'),
        $total_items
    )); ?>
  </p>
  <ul class="page-numbers pap-archive-pagination-list">
    <?php if ($current <= 1) : ?>
      <li><span class="page-numbers prev disabled" aria-hidden="true"><?php esc_html_e('Pagina anterioară', 'papetarie-storefront'); ?></span></li>
    <?php endif; ?>
    <?php foreach ($links as $link) : ?>
      <li><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
    <?php endforeach; ?>
    <?php if ($current >= $total) : ?>
      <li><span class="page-numbers next disabled" aria-hidden="true"><?php esc_html_e('Pagina următoare', 'papetarie-storefront'); ?></span></li>
    <?php endif; ?>
  </ul>
</nav>
