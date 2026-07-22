# Product badges — "Ofertă" / "Recomandat" / "Popular"

**Status: disabled.** The badges still exist in code (nothing was deleted) but rendering is turned off at a single point in `functions.php` — see below. This doc exists so the feature can be picked back up later without having to reverse-engineer it.

## What it was

Every product card could show up to 3 small badges in its top-left corner, stacked if more than one applied:

| Badge | Condition | Where it comes from |
|---|---|---|
| **Ofertă** | `$product->is_on_sale()` | Real WooCommerce logic — true whenever the product has a sale price set. |
| **Recomandat** | `$product->is_featured()` | WooCommerce's built-in "Featured product" flag (set per-product in wp-admin). |
| **Popular** | `$product->get_total_sales() >= 20` | Actual lifetime sales count on the product, threshold filterable via `papetarie_storefront_popular_sales_threshold` (defaults to 20). |

So none of the three were fake/random — each is driven by real product data. The reason they were confusing wasn't the logic, it's that there was no admin-facing explanation of what "Recomandat" or "Popular" meant, or a way to see/control which products would get flagged, so it felt arbitrary while browsing.

## Where it lived

- Logic: `papetarie_storefront_get_product_badges()` in `functions.php`.
- Rendering: `papetarie_storefront_render_product_badges_html()` in `functions.php`.
- Called from the two shared card renderers — `papetarie_storefront_render_slider_product_card()` (homepage) and `papetarie_storefront_render_product_card()` (category archive, account order history) — so it showed up on every product card site-wide.
- CSS: `.pap-product-badges` / `.pap-product-badge--is-sale` / `--is-featured` / `--is-popular` in `style.css` (left as-is, unused while disabled).

## How it was turned off

`papetarie_storefront_render_product_badges_html()` checks a filter, `papetarie_storefront_enable_product_badges`, which defaults to `false`. The underlying data logic (`papetarie_storefront_get_product_badges()`) is untouched. To bring badges back (no core file edit needed):

```php
add_filter('papetarie_storefront_enable_product_badges', '__return_true');
```

## Open questions for next time

- Should "Recomandat"/"Popular" be admin-controlled (a manual toggle) instead of automatic, so it's predictable which products carry the label?
- Is a 20-sale threshold for "Popular" even meaningful yet, given the catalog is still mostly test data?
- Do we want all three, or is "Ofertă" (sale badge) the only one that's self-evidently useful to a shopper?
