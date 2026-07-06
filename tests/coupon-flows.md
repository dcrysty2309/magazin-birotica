# Coupon Flows

Source of truth:
- `WC()->cart`

## CT-001 Apply valid coupon

Steps:
- Apply `test10`

Expected:
- coupon chip appears;
- discount row appears;
- total updates.

## CT-002 Invalid coupon

Steps:
- Apply `asdf`

Expected:
- red inline error appears;
- no discount is applied.

## CT-003 Invalid -> valid

Steps:
- Apply `asdf`;
- replace with `test10`;
- apply again.

Expected:
- previous error disappears;
- coupon applies normally;
- discount row appears;
- total updates.

## CT-004 Duplicate coupon

Steps:
- Apply `test10`;
- apply `test10` again.

Expected:
- no duplicate discount;
- message: `Codul promoțional este deja aplicat.`
- no stale success state from the first apply.

## CT-005 Remove coupon

Steps:
- Apply `test10`;
- remove the coupon.

Expected:
- chip disappears;
- discount row disappears;
- totals return to normal;
- minicart updates.

## CT-006 Reapply coupon

Steps:
- Apply `test10`;
- remove coupon;
- apply `test10` again.

Expected:
- coupon applies normally again;
- no stale state remains.

## CT-007 Refresh page

Steps:
- Apply `test10`;
- refresh the page.

Expected:
- coupon chip still exists;
- discount row still exists;
- total remains discounted.

## CT-008 Hard refresh

Steps:
- Apply `test10`;
- hard refresh the browser.

Expected:
- coupon persists from session/cart state.

## CT-009 Close browser and reopen

Steps:
- Apply `test10`;
- close the browser;
- reopen and return to cart.

Expected:
- coupon still applied.
- no stale duplicate-error notice appears.

## CT-010 Cart -> Checkout

Steps:
- Apply `test10`;
- go to checkout.

Expected:
- discount, total, and coupon state remain identical.

## CT-011 Checkout -> Cart

Steps:
- Apply `test10`;
- go back to cart.

Expected:
- discount, total, and coupon state remain identical.

## CT-012 Quantity increase

Steps:
- Apply `test10`;
- increase quantity.

Expected:
- discount recalculates.

## CT-013 Quantity decrease

Steps:
- Apply `test10`;
- decrease quantity.

Expected:
- discount recalculates.

## CT-014 Remove product

Steps:
- Apply `test10`;
- remove a product from the cart.

Expected:
- totals recalculate.

## CT-015 Empty cart

Steps:
- Apply `test10`;
- empty the cart.

Expected:
- coupon is removed automatically.

## CT-016 Minimum amount no longer met

Steps:
- Apply `test10`;
- reduce the cart below the coupon minimum.

Expected:
- coupon is invalidated;
- user receives a clear notification.

## CT-017 Minicart synchronization

Steps:
- Apply `test10`;
- open minicart.

Expected:
- discounted total is visible in minicart;
- badge count remains intact.

## CT-018 Multiple tabs

Steps:
- Tab A: apply coupon;
- Tab B: refresh cart.

Expected:
- same state in both tabs after refresh.

## CT-019 Session restore

Steps:
- leave the site;
- return later.

Expected:
- coupon persists while the Woo session is valid.
- summary rebuilds from `WC()->cart`, not JS memory.

## CT-020 Expired coupon

Steps:
- Apply an expired coupon.

Expected:
- proper inline error is shown.

## CT-021 Used coupon

Steps:
- Apply a used coupon.

Expected:
- proper inline error is shown.

## CT-022 Empty input

Steps:
- click Apply with no code.

Expected:
- `Introduceți un cod promoțional.`

## CT-023 Loading state

Steps:
- click Apply.

Expected:
- button is disabled while the request runs;
- loading state is visible.

## CT-024 No layout shifts

Steps:
- success;
- error;
- remove;
- refresh.

Expected:
- spacing, rows, and controls remain stable.

## CT-025 Cart coupon toast

Steps:
- apply `test10` in Cart;
- remove `test10` in Cart.

Expected:
- a small floating toast appears in the top-right corner;
- the toast auto-hides after a few seconds;
- the toast text clearly confirms apply/remove, for example:
  - `Codul tău promoțional a fost activat cu succes`
  - `Codul tău promoțional a fost eliminat`
- no trailing ellipsis or stale WooCommerce copy appears.

## CT-026 Checkout stays clean after cart coupon

Steps:
- apply `test10` in Cart;
- navigate to Checkout.

Expected:
- the Checkout page does not show the big WooCommerce success banner;
- only the checkout summary content remains visible.

## Release audit quick pass

Use this as the first manual verification block before release:

- CT-001 Apply valid coupon
  - pass when `test10` applies, the chip appears, the discount row appears, and the total updates.
- CT-002 Invalid coupon
  - pass when `asdf` shows the red inline error and no discount is applied.
- CT-003 Invalid -> valid
  - pass when `asdf` error clears and `test10` applies normally afterward.
- CT-004 Duplicate coupon
  - pass when applying `test10` twice shows `Codul promoțional este deja aplicat.` and no duplicate discount appears.
  - confirm the second apply does not clear the existing discount chip/row.
- CT-005 Remove coupon
  - pass when removing `test10` removes the chip, removes the discount row, and restores normal totals.
- CT-006 Reapply coupon
  - pass when `test10` works again after remove, with no stale state.
- CT-007 Refresh page
  - pass when `test10` survives `F5` with chip, discount row, and total intact.
- CT-008 Hard refresh
  - pass when `test10` survives `Ctrl+Shift+R` with the same cart totals.
- CT-009 Close browser and reopen
  - pass when the Woo session restores the applied coupon and the cart summary reconstructs correctly.
- CT-025 Cart coupon toast
  - pass when coupon apply/remove is confirmed by the floating top-right toast and it auto-hides.
- CT-026 Checkout stays clean after cart coupon
  - pass when Checkout does not render the old Woo success banner after applying a coupon in Cart.
