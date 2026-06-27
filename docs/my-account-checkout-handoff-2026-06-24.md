# My Account + Checkout Handoff

Date: 2026-06-24
Base commit: `61e7fc88`

## Context

This repo has two separate working areas that should stay separated unless a task explicitly crosses them:

- `My Account` covers sidebar, dashboard, orders, view order, edit account, favorites, and addresses.
- `Checkout` covers shipping/billing sync, summary cards, and checkout field population.

The current refactor focused on My Account addresses and the checkout address mapping needed to support the current address shape.

## My Account status

Completed:

- My Account now uses the standard WooCommerce billing and shipping address flow.
- No custom multi-address address book is exposed in the active UI.
- Checkout changes can be saved back to My Account only when the explicit save checkbox is checked.

Important files:

- `wp-content/themes/papetarie-storefront/includes/address-book.php`
- `wp-content/themes/papetarie-storefront/assets/js/address-book.js`
- `wp-content/themes/papetarie-storefront/style.css`

Things to keep in mind:

- Do not reintroduce a custom address book unless explicitly requested.
- Keep My Account UI consistent with the existing theme and WooCommerce endpoints.
- Preserve the standard billing/shipping address semantics.

## Checkout status

Completed:

- Checkout address mapping follows the current standard checkout fields.
- Checkout uses a single current address flow instead of multiple saved-address selectors.

Important files:

- `wp-content/themes/papetarie-storefront/assets/js/checkout.js`
- checkout-related address helpers in `wp-content/themes/papetarie-storefront/includes/address-book.php`

Things to keep in mind:

- Checkout should continue to stay compatible with WooCommerce customer meta.
- If address shape changes again, update both the My Account flow and checkout field mapping.
- Keep an eye on any legacy `address_2` references until they are fully retired.
- Billing/shipping behavior should remain untouched unless the task explicitly asks for it.

## Exact prompt to reuse tomorrow

Use this when continuing My Account work:

```text
Reiau contextul de la commit 61e7fc88 și citește brief-ul din docs/my-account-checkout-handoff-2026-06-24.md. Lucrează doar pe My Account, zona Adrese sau ce îți cer explicit. Nu atinge Checkout decât dacă îți spun clar.
```

Use this when continuing Checkout work:

```text
Reiau contextul de la commit 61e7fc88 și citește brief-ul din docs/my-account-checkout-handoff-2026-06-24.md. Lucrează doar pe Checkout și folosește ce există deja în My Account doar dacă e nevoie pentru adrese.
```

## Notes

- Two screenshot files are present in the repo root as untracked local artifacts:
  - `Screenshot 2026-06-23 at 21.37.06.png`
  - `Screenshot 2026-06-23 at 22.29.00.png`
- They were not included in the commit.
