# WooCommerce Test Flows

## Product
- open product page
- verify gallery with real images
- switch gallery thumbnails
- open image zoom/lightbox if enabled
- verify price, stock, SKU, category, brand
- add to cart
- test out-of-stock state

## Cart
- add one item
- add two quantities
- update quantity
- remove item
- verify subtotal, shipping, total
- verify empty cart state

## Checkout
- guest checkout
- login checkout
- person customer
- company customer
- PF fields validation
- CUI / company fields validation
- county selection
- city validation
- address and postcode validation
- delivery notes / observations handling
- COD payment
- card payment
- local is for quick iteration, staging is the final QA environment for checkout
- checkout bugs found in staging become the official fix queue for the next iteration

## My Account
- register account
- login
- logout
- forgot password
- reset password
- orders page
- addresses page
- account details page
- returns page

## Email
- order confirmation
- account creation
- password reset
- return request confirmation

## Responsive
- desktop
- tablet portrait
- tablet landscape
- mobile portrait
- mobile landscape
