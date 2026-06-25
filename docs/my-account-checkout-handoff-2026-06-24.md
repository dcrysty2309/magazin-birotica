# My Account + Checkout Handoff

Date: 2026-06-24
Base commit: `61e7fc88`

## Context

This repo has two separate working areas that should stay separated unless a task explicitly crosses them:

- `My Account` covers sidebar, dashboard, orders, view order, edit account, favorites, and addresses.
- `Checkout` covers saved addresses, shipping/billing sync, checkout modals, and checkout field population.

The current refactor focused on My Account addresses and the checkout address mapping needed to support the current address shape.

## My Account status

Completed:

- The `Adrese` page now behaves as a list-first page.
- The old dedicated add-address page flow was replaced by a centered modal.
- Add/edit share the same modal form.
- The address list updates without refresh after save/delete.
- Empty state is compact and informational.
- The info alert uses Font Awesome directly.
- The address form now follows the checkout standard fields: Prenume, Nume, Email, Telefon, Județ, Localitate, Adresă, Cod poștal, Observații pentru livrare / curier.
- The old separate `address_2` field is legacy-only and should not reappear as a visible UI field.

Important files:

- `wp-content/themes/papetarie-storefront/includes/address-book.php`
- `wp-content/themes/papetarie-storefront/assets/js/address-book.js`
- `wp-content/themes/papetarie-storefront/style.css`

Things to keep in mind:

- Do not reintroduce a separate add-address page unless explicitly requested.
- Keep My Account UI consistent with the existing square, non-rounded style.
- Keep the modal single-column and simple.
- The address list should stay shipping-focused unless a task explicitly brings back billing UI.

## Checkout status

Completed:

- Checkout address mapping follows the current address standard used by checkout.
- Saved address data remains compatible with the checkout selectors.

Important files:

- `wp-content/themes/papetarie-storefront/assets/js/checkout.js`
- checkout-related address helpers in `wp-content/themes/papetarie-storefront/includes/address-book.php`

Things to keep in mind:

- Checkout should continue to consume saved addresses from My Account.
- If address shape changes again, update both the My Account form and checkout field mapping.
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
