(function () {
  var scope = document.querySelector('.pap-archive-page');

  if (!scope || !window.papArchiveAddToCart) {
    return;
  }

  var cartModal = document.querySelector('[data-cart-modal]');
  var cartModalImage = cartModal ? cartModal.querySelector('[data-cart-modal-image]') : null;
  var cartModalThumb = cartModal ? cartModal.querySelector('[data-cart-modal-thumb]') : null;
  var cartModalName = cartModal ? cartModal.querySelector('[data-cart-modal-name]') : null;
  var cartModalPrice = cartModal ? cartModal.querySelector('[data-cart-modal-price]') : null;
  var cartModalLink = cartModal ? cartModal.querySelector('[data-cart-modal-link]') : null;
  var cartModalClosers = cartModal ? Array.prototype.slice.call(cartModal.querySelectorAll('[data-cart-modal-close]')) : [];

  function openCartModal(payload) {
    if (!cartModal) {
      return;
    }

    if (cartModalImage && cartModalThumb) {
      if (payload && payload.image_url) {
        cartModalImage.src = payload.image_url;
        cartModalImage.alt = payload.name || '';
        cartModalThumb.hidden = false;
      } else {
        cartModalImage.src = '';
        cartModalImage.alt = '';
        cartModalThumb.hidden = true;
      }
    }

    if (cartModalName) {
      cartModalName.textContent = payload && payload.name ? payload.name : '';
    }

    if (cartModalPrice) {
      cartModalPrice.innerHTML = payload && payload.price_html ? payload.price_html : '';
    }

    if (cartModalLink && payload && payload.cart_url) {
      cartModalLink.href = payload.cart_url;
    }

    cartModal.hidden = false;
    document.body.classList.add('pap-modal-open');
  }

  function closeCartModal() {
    if (!cartModal) {
      return;
    }

    cartModal.hidden = true;
    document.body.classList.remove('pap-modal-open');
  }

  cartModalClosers.forEach(function (closer) {
    closer.addEventListener('click', closeCartModal);
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.pap-archive-page .pap-home-add-to-cart');

    if (!button) {
      return;
    }

    var productId = button.getAttribute('data-product-id') || button.getAttribute('data-product_id');

    if (!productId) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    if (button.classList.contains('is-loading')) {
      return;
    }

    button.disabled = true;
    button.classList.add('is-loading');

    fetch(papArchiveAddToCart.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: new URLSearchParams({
        action: 'pap_home_add_to_cart',
        nonce: papArchiveAddToCart.nonce,
        product_id: productId,
        quantity: '1'
      })
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error(response && response.data && response.data.message ? response.data.message : 'Add to cart failed');
        }

        openCartModal(response.data || {});
      })
      .catch(function () {
        window.location.href = button.getAttribute('href') || papArchiveAddToCart.cartUrl;
      })
      .finally(function () {
        button.disabled = false;
        button.classList.remove('is-loading');
      });
  }, true);
})();
