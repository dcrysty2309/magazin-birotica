/**
 * Turns the Județ/Localitate <select> elements into height-capped dropdowns
 * using WooCommerce's bundled selectWoo (Select2 fork) instead of the native
 * OS popup — county/city lists run into the hundreds of options (some
 * counties have 700+ localities), which the native <select> renders as one
 * giant screen-covering list.
 */
(function ($) {
  'use strict';

  if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
    return;
  }

  var SELECTOR = '#address-book-state, #address-book-city, #shipping_state, #shipping_city, #billing_state, #billing_city';

  function dropdownParentFor($el) {
    // The address-book modal renders at a very high z-index (it sits above
    // the whole page). Select2 appends its dropdown to <body> by default,
    // which then paints BEHIND the modal (lower stacking order) and gets
    // visually clipped by it. Anchoring the dropdown inside the modal keeps
    // it in the same stacking context so it always paints on top.
    var $modal = $el.closest('.pap-account-address-modal');
    return $modal.length ? $modal : $(document.body);
  }

  function initOne($el) {
    if ($el.hasClass('select2-hidden-accessible') || $el.prop('multiple')) {
      return;
    }

    $el.select2({
      width: '100%',
      // Hides the search box unconditionally (select2 adds a
      // "select2-search--hide" class rather than omitting the markup, so
      // don't check for the search box's presence in the DOM — check its
      // display/class instead).
      minimumResultsForSearch: Infinity,
      dropdownParent: dropdownParentFor($el),
    });
  }

  function scan() {
    $(SELECTOR).each(function () {
      initOne($(this));
    });
  }

  $(scan);

  // WooCommerce fires this after any AJAX checkout fragment refresh
  // (shipping method change, cart update, etc.) — the shipping_state/
  // shipping_city fields can come back as fresh, un-enhanced <select>
  // elements when that happens. The address-book modal doesn't need an
  // equivalent hook: it's a show/hide toggle on markup that's already in the
  // DOM at page load (each "Editează" target is its own full page load, per
  // includes/address-book.php), not a dynamic re-render.
  $(document.body).on('updated_checkout', scan);
})(window.jQuery);
