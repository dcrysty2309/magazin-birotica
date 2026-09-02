(function () {
  function toast(message, type) {
    var node = document.querySelector('[data-pap-toast]');

    if (!node) {
      node = document.createElement('div');
      node.setAttribute('data-pap-toast', 'true');
      node.className = 'pap-toast';
      document.body.appendChild(node);
    }

    node.className = 'pap-toast pap-toast--' + (type || 'info');
    node.textContent = message || '';
    node.classList.add('is-visible');

    window.clearTimeout(node._papToastTimer);
    node._papToastTimer = window.setTimeout(function () {
      node.classList.remove('is-visible');
    }, 2400);
  }

  function setButtonState(button, active) {
    button.classList.toggle('is-active', active);
    button.setAttribute('aria-pressed', active ? 'true' : 'false');

    var icon = button.querySelector('i');
    if (icon) {
      icon.className = active ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    }

    var sr = button.querySelector('.screen-reader-text');
    if (sr) {
      sr.textContent = active ? 'Scoate din favorite' : 'Adaugă la favorite';
    }

    button.setAttribute('aria-label', active ? 'Scoate din favorite' : 'Adaugă la favorite');
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.pap-wishlist');
    if (!button || !window.papWishlist) {
      return;
    }

    event.preventDefault();

    var action = button.getAttribute('data-wishlist-action');
    var loginUrl = button.getAttribute('data-login-url') || papWishlist.loginUrl;

    if (action === 'login') {
      var redirectUrl = loginUrl;
      if (redirectUrl && redirectUrl.indexOf('redirect_to=') === -1) {
        redirectUrl += (redirectUrl.indexOf('?') === -1 ? '?' : '&') + 'redirect_to=' + encodeURIComponent(window.location.href);
      }
      window.location.href = redirectUrl;
      return;
    }

    if (button.classList.contains('is-loading')) {
      return;
    }

    var productId = button.getAttribute('data-product-id');
    if (!productId) {
      return;
    }

    button.classList.add('is-loading');
    button.disabled = true;

    fetch(papWishlist.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: new URLSearchParams({
        action: 'pap_toggle_wishlist',
        nonce: papWishlist.nonce,
        product_id: productId
      })
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error(response && response.data && response.data.message ? response.data.message : papWishlist.messages.error);
        }

        setButtonState(button, !!response.data.active);
        toast(response.data.message || (response.data.active ? papWishlist.messages.added : papWishlist.messages.removed), 'success');

        var countNode = document.querySelector('[data-wishlist-count]');
        if (countNode && typeof response.data.count !== 'undefined') {
          countNode.textContent = response.data.count;
        }

        document.dispatchEvent(new CustomEvent('pap:wishlist-updated', {
          detail: response.data
        }));
      })
      .catch(function (error) {
        toast(error && error.message ? error.message : papWishlist.messages.error, 'error');
      })
      .finally(function () {
        button.classList.remove('is-loading');
        button.disabled = false;
      });
  });
})();
