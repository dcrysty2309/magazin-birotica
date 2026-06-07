# Launch Plan - Magazin Papetarie

## Goal
Bring the store to a launch-ready state with a stable WooCommerce foundation, consistent UI, clean responsive behavior, and a product flow that works end-to-end on desktop, tablet, and mobile.

## Working Rules
- Prefer native WordPress / WooCommerce flows first.
- Use custom code only where it adds clear value or fixes a real limitation.
- Do not change things that already work unless there is a concrete reason.
- Keep the current visual system consistent:
  - square corners where the site already uses them
  - existing spacing logic
  - existing typography
  - Font Awesome icons when icons are needed
- Validate everything on real content, not only placeholder data.
- Test across:
  - desktop
  - tablet
  - mobile
  - keyboard focus
  - error states
  - success states

## What Must Be True At The End
- Product pages work correctly.
- Gallery works with real images.
- Add to cart works.
- Cart works.
- Checkout works.
- My Account works.
- Login / logout / password reset work.
- Order confirmation emails work.
- Error and success notices are clear.
- Validation is reliable.
- Mobile layout is stable.
- Tablet layout is stable.
- No obvious UI regressions remain.
- The site feels coherent, not patched together.

## High-Level Phases

### Phase 1 - Core Store QA
Focus:
- homepage
- category pages
- product pages
- cart
- checkout
- my account
- emails

Deliverables:
- all core flows functional
- no broken buttons, links, or templates
- clean notices for success / error

### Phase 2 - Product Page Hardening
Focus:
- image gallery
- image zoom / lightbox only if it behaves well on mobile
- product title, price, stock, SKU, category, brand
- simple and variable products
- related products
- add to cart behavior
- out-of-stock behavior

Deliverables:
- product page looks good with real images
- no layout shifts
- gallery behaves predictably
- mobile product page is usable

### Phase 3 - Cart and Checkout
Focus:
- cart quantity updates
- remove item
- subtotal / shipping / total
- checkout fields
- billing / shipping validation
- person / company choice
- Romania-specific address inputs
- card payment + cash on delivery

Deliverables:
- cart and checkout work without surprises
- notices are readable and actionable
- required fields are validated
- checkout is short enough to be usable

### Phase 4 - My Account
Focus:
- login
- logout
- registration
- lost password
- reset password
- orders
- addresses
- account details
- optional returns section

Deliverables:
- account dashboard feels intentional
- account pages are easy to use on mobile
- no dead links or confusing defaults

### Phase 5 - Notifications / Emails
Focus:
- order confirmation
- account creation
- password reset
- order processing / completion
- return-related emails if enabled

Deliverables:
- emails are branded, readable, and consistent
- no technical phrasing unless needed
- mail templates render well in common clients

### Phase 6 - Category and Catalog Strategy
Focus:
- category structure
- filters
- product attributes
- launch assortment

Deliverables:
- categories are lean and commercially useful
- attributes map to real filtering needs
- no unnecessary hierarchy
- product assignment is consistent

### Phase 7 - Supplier Research / Product Sourcing
Focus:
- identify suppliers worth contacting
- maintain contact status
- build an acquisition strategy
- compare pricing and lead times

Deliverables:
- supplier shortlist
- outreach tracker
- pricing research workflow
- decision on which suppliers are launch-worthy

## Information Needed Up Front
These are the items I need from you early so I do not build in the wrong direction.

### Business Model
- Are we launching with:
  - local stock
  - supplier-direct / dropshipping
  - hybrid model
- What is the acceptable delivery promise:
  - same day
  - 24h
  - 48-72h
  - mixed depending on product

### Payments
- Which methods are mandatory:
  - cash on delivery
  - card
  - bank transfer
- Which provider do we want to prefer:
  - WooPayments
  - Stripe
  - another local gateway

### Shipping
- Which courier / couriers should be supported?
- Do we allow mixed-supplier carts or should we restrict them?
- Do we want a single shipping fee or shipping rules per order type?

### Checkout Rules
- Do we need:
  - company / individual toggle
  - CUI / VAT fields
  - RO county and city lists
  - postcode validation
  - optional order notes
  - invoice company data

### Returns
- Do returns need:
  - just a policy page
  - account-based request form
  - status tracking
  - email notifications

### Catalog
- Which categories are launch categories?
- Which are only for later?
- Which categories should stay hidden until they have real products?
- What is the minimum SKU count per category for launch?

### Product Data
For test and real products, I need to know:
- which fields are mandatory
- whether every product has:
  - main image
  - gallery
  - SKU
  - brand
  - attributes
  - description
  - short description
- whether simple and variable products both matter now

### Visual Direction
- Do we keep the current square-corner system?
- Do we keep the current compact spacing?
- Do we standardize more strongly on existing homepage patterns?
- Are there any pages that should feel more editorial or more utility-first?

## QA Checklist

### Product Page
- title visible
- price visible
- stock visible
- gallery works
- add to cart works
- error messages are clear
- related products show correctly
- mobile layout stays usable

### Cart
- add item
- update quantity
- remove item
- totals update
- shipping visible
- empty cart state is clean

### Checkout
- guest checkout works
- customer account flow works
- billing address validates
- shipping address validates
- company fields validate
- payment method selection works
- success page appears

### My Account
- registration works
- login works
- logout works
- lost password works
- reset password works
- order history works

### Emails
- order confirmation received
- account email received
- password reset email received
- formatting is acceptable in Gmail and Outlook

### Responsive
- no horizontal overflow
- buttons remain tappable
- forms are readable
- gallery does not break
- account navigation is usable
- checkout fields stack properly

## Test Flow Scenarios
1. Visitor loads homepage.
2. Visitor opens category page.
3. Visitor opens product page.
4. Visitor adds product to cart.
5. Visitor updates cart quantity.
6. Visitor removes product.
7. Visitor goes to checkout.
8. Visitor chooses payment method.
9. Visitor completes order.
10. Visitor receives confirmation email.
11. Visitor creates an account.
12. Visitor logs in and logs out.
13. Visitor requests password reset.
14. Visitor attempts a return flow if enabled.

## Implementation Philosophy
- Prefer standard WooCommerce flows.
- If a plugin already solves the problem cleanly, prefer that over custom code.
- If custom code is needed, keep it narrow and maintainable.
- Do not invent UI where the standard flow is already good.
- Use modern patterns only when they improve clarity.
- Make sure every change is tested against real data.

## Open Decisions
The following decisions should be settled early:
- single supplier vs mixed suppliers at launch
- card provider choice
- exact shipping promise
- whether checkout is standard or one-page
- whether returns are in scope for launch
- which categories launch now vs later
- whether to allow company checkout data now or later

## Immediate Next Work
1. Product page polish.
2. Gallery QA with real images.
3. Cart and checkout QA.
4. My Account modernization.
5. Email templates.
6. Validation and notices.
7. Responsive pass on tablet and mobile.
8. Keep supplier research running in parallel, not blocking implementation.

