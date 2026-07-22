# Pagination — Category & Shop Archives

This document defines how pagination behaves on product archive pages (categories, shop).

## Rule

**60 products per page.** Pagination only appears once a category has more than 60 products; below that, every product renders on a single page and no pagination controls are shown.

This mirrors eMAG's approach, which was the explicit reference for this decision.

## Implementation

Controlled by a single filter in `functions.php`:

```php
add_filter('loop_shop_per_page', static fn (): int => 60, 20);
```

WooCommerce's own pagination markup (`woocommerce_pagination()`, rendered in `woocommerce/loop/pagination.php`) already only outputs page-number controls when `$wp_query->max_num_pages > 1`, so no extra "only show if > 60" logic is needed — it falls out naturally from the per-page value.

Page-number styling (bordered squares, navy active state) lives in `style.css` under `.pap-archive-pagination-bar` / `.pap-archive-pagination-list` (Figma node 65:1504) — the border is intentional, matching the same bordered-pill treatment used elsewhere (filter cards, sort dropdown). The bar also shows the item range ("1 – 60 din 66 de produse"), computed in `woocommerce/loop/pagination.php` from `wc_get_loop_prop('per_page')` / `wc_get_loop_prop('total')`. This is scoped to the shop/category archive only — reviews and my-account order pagination use a separate `.pap-pagination-nav` class untouched by this design.

## Performance

At 60 products per page, the main cost is images. Two things already in place keep this cheap:

- Every product thumbnail is rendered with `loading="lazy"` (see `papetarie_storefront_render_product_card()` / `papetarie_storefront_render_slider_product_card()` in `functions.php`) — the browser only fetches images as they scroll into view, so a 60-card grid doesn't front-load 60 full-size images at once.
- Images go through `wp_get_attachment_image()` / `WC_Product::get_image()`, both of which emit `srcset`/`sizes` automatically, so each card downloads an appropriately-sized image rather than a full-resolution original.

If pages still feel heavy in practice (slow hosting, large uncompressed source images, etc.), the next lever is the image size registered for product thumbnails (`woocommerce_thumbnail` / the `medium` size), not the per-page count.

## Open question — mobile

60/page is confirmed for desktop. Whether mobile should use a smaller per-page value (e.g. to reduce initial scroll-render cost on slower connections) is **not decided yet** — flagging here rather than guessing. If/when a mobile-specific number is picked, it can be added as a device check inside the same `loop_shop_per_page` filter.
