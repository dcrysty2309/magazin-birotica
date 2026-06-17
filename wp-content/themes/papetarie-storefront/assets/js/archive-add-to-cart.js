(function () {
  var config = window.papStorefrontAddToCart || {};
  if (!config.ajaxUrl) {
    return;
  }

  var cartModal = document.querySelector('[data-cart-modal]');
  var cartModalImage = cartModal ? cartModal.querySelector('[data-cart-modal-image]') : null;
  var cartModalThumb = cartModal ? cartModal.querySelector('[data-cart-modal-thumb]') : null;
  var cartModalName = cartModal ? cartModal.querySelector('[data-cart-modal-name]') : null;
  var cartModalPrice = cartModal ? cartModal.querySelector('[data-cart-modal-price]') : null;
  var cartModalQuantity = cartModal ? cartModal.querySelector('[data-cart-modal-quantity]') : null;
  var cartModalLink = cartModal ? cartModal.querySelector('[data-cart-modal-link]') : null;
  var cartModalClosers = cartModal ? Array.prototype.slice.call(cartModal.querySelectorAll('[data-cart-modal-close]')) : [];
  var cartModalCloseTimer = null;
  var modalManager = window.papModalManager || null;
  var cartModalLastFocus = null;
  var cartToastHost = null;
  var pendingButtons = new WeakSet();
  var actionStatus = document.querySelector('[data-pap-action-status]');
  var actionBusyCount = window.__papActionBusyCount || 0;
  var perfEnabled = !!window.__papPerfDebug;

  function ensureActionStatus() {
    if (actionStatus) {
      return actionStatus;
    }

    actionStatus = document.createElement('div');
    actionStatus.className = 'pap-action-status';
    actionStatus.setAttribute('data-pap-action-status', '');
    actionStatus.setAttribute('role', 'status');
    actionStatus.setAttribute('aria-live', 'polite');
    actionStatus.hidden = true;
    actionStatus.innerHTML = '<span class="pap-action-status-spinner" aria-hidden="true"></span><span class="pap-action-status-text"></span>';
    document.body.appendChild(actionStatus);
    return actionStatus;
  }

  function updateActionStatus(message) {
    var node = ensureActionStatus();
    var text = node.querySelector('.pap-action-status-text');

    if (text) {
      text.textContent = message || '';
    }

    node.hidden = !message;
    document.body.classList.toggle('pap-action-busy', !!message);
  }

  function perfTime(label) {
    if (perfEnabled && window.console && typeof window.console.time === 'function') {
      window.console.time(label);
    }
  }

  function perfTimeEnd(label) {
    if (perfEnabled && window.console && typeof window.console.timeEnd === 'function') {
      window.console.timeEnd(label);
    }
  }

  function perfLog(label, value) {
    if (perfEnabled && window.console && typeof window.console.log === 'function') {
      window.console.log(label, value);
    }
  }

  function stripHtmlToText(html) {
    if (!html) {
      return '';
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = String(html);
    return (wrapper.textContent || wrapper.innerText || '').replace(/\s+/g, ' ').trim();
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatUnitPriceText(text) {
    var value = String(text || '').trim();
    return value ? value + ' / buc.' : '';
  }

  window.papSetActionBusy = window.papSetActionBusy || function (message) {
    actionBusyCount += 1;
    window.__papActionBusyCount = actionBusyCount;
    updateActionStatus(message || 'Se actualizează...');
  };

  window.papClearActionBusy = window.papClearActionBusy || function () {
    actionBusyCount = Math.max(0, (window.__papActionBusyCount || actionBusyCount || 1) - 1);
    window.__papActionBusyCount = actionBusyCount;

    if (actionBusyCount === 0) {
      updateActionStatus('');
    }
  };

  function openCartModal(payload, options) {
    if (!cartModal) {
      return;
    }

    cartModalLastFocus = options && options.focusTarget ? options.focusTarget : document.activeElement;
    clearTimeout(cartModalCloseTimer);

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
      cartModalPrice.innerHTML = payload && (payload.cart_item_total_html || payload.price_html)
        ? (payload.cart_item_total_html || payload.price_html)
        : '';
    }

    if (cartModalQuantity) {
      var cartQuantity = payload && typeof payload.cart_item_quantity !== 'undefined'
        ? Math.max(1, parseInt(payload.cart_item_quantity, 10) || 1)
        : 1;
      var unitPriceText = payload && payload.cart_item_unit_price_text
        ? String(payload.cart_item_unit_price_text)
        : stripHtmlToText(payload && payload.price_html ? payload.price_html : '');

      if (cartQuantity > 1) {
        cartModalQuantity.textContent = cartQuantity + ' × ' + unitPriceText;
        cartModalQuantity.hidden = false;
      } else {
        cartModalQuantity.textContent = '';
        cartModalQuantity.hidden = true;
      }
    }

    if (cartModalLink && payload && payload.cart_url) {
      cartModalLink.href = payload.cart_url;
    }

    cartModal.hidden = false;
    cartModal.setAttribute('aria-hidden', 'false');
    if (modalManager) {
      modalManager.open(cartModal, closeCartModal, { focusTarget: cartModalLastFocus });
    }

    window.requestAnimationFrame(function () {
      cartModal.classList.add('is-open');
    });
  }

  function closeCartModal() {
    if (!cartModal) {
      return;
    }

    cartModal.classList.remove('is-open');
    cartModal.setAttribute('aria-hidden', 'true');
    if (modalManager) {
      modalManager.close(cartModal);
    }

    clearTimeout(cartModalCloseTimer);
    cartModalCloseTimer = window.setTimeout(function () {
      cartModal.hidden = true;
      if (cartModalLastFocus && typeof cartModalLastFocus.focus === 'function') {
        cartModalLastFocus.focus({ preventScroll: true });
      }
    }, 220);
  }

  function updateCountBadge(count) {
    var safeCount = Math.max(0, parseInt(count, 10) || 0);
    var countLabel = safeCount === 1 ? '1 produs' : safeCount + ' produse';
    Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
      target.textContent = countLabel;
    });
  }

  function ensureCartToastHost() {
    if (cartToastHost) {
      return cartToastHost;
    }

    cartToastHost = document.querySelector('[data-cart-toast-host]');

    if (!cartToastHost) {
      cartToastHost = document.createElement('div');
      cartToastHost.className = 'pap-toast-host pap-toast-host--cart';
      cartToastHost.setAttribute('data-cart-toast-host', 'true');
      document.body.appendChild(cartToastHost);
    }

    return cartToastHost;
  }

  function hideCartToast(toast) {
    if (!toast) {
      return;
    }

    window.clearTimeout(toast._papToastTimer);
    window.clearTimeout(toast._papToastPauseTimer);
    window.clearTimeout(toast._papToastHideTimer);
    toast.classList.add('is-hiding');
    toast.classList.remove('is-visible');

    toast._papToastHideTimer = window.setTimeout(function () {
      if (!toast || !toast.parentNode) {
        return;
      }

      toast.parentNode.removeChild(toast);
    }, 220);
  }

  function scheduleCartToastHide(toast, delay) {
    if (!toast) {
      return;
    }

    window.clearTimeout(toast._papToastTimer);
    window.clearTimeout(toast._papToastPauseTimer);

    toast._papToastShownAt = Date.now();
    toast._papToastRemaining = Math.max(0, parseInt(delay, 10) || 0);

    toast._papToastTimer = window.setTimeout(function () {
      hideCartToast(toast);
    }, toast._papToastRemaining);
  }

  function showCartToast(message) {
    if (!document.body.classList.contains('woocommerce-cart')) {
      return;
    }

    var host = ensureCartToastHost();
    if (!host) {
      return;
    }

    var toast = document.createElement('div');
    toast.className = 'pap-toast pap-toast--cart pap-toast--success';
    toast.setAttribute('data-cart-toast', 'true');
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = '<span class="pap-toast__icon" aria-hidden="true">✓</span><span class="pap-toast__message" data-cart-toast-message></span><button type="button" class="pap-toast__close" data-cart-toast-close aria-label="Închide notificarea">×</button>';
    host.appendChild(toast);

    toast._papToastDuration = 3500;
    toast._papToastRemaining = toast._papToastDuration;
    toast._papToastShownAt = 0;
    toast._papToastPaused = false;

    toast.addEventListener('click', function (event) {
      if (event.target.closest('[data-cart-toast-close]')) {
        event.preventDefault();
        hideCartToast(toast);
      }
    });

    toast.addEventListener('pointerenter', function () {
      if (!toast.classList.contains('is-visible') || toast.classList.contains('is-hiding')) {
        return;
      }

      window.clearTimeout(toast._papToastTimer);
      window.clearTimeout(toast._papToastPauseTimer);
      toast._papToastPaused = true;

      if (toast._papToastShownAt) {
        toast._papToastRemaining = Math.max(0, toast._papToastRemaining - (Date.now() - toast._papToastShownAt));
      }
    });

    toast.addEventListener('pointerleave', function () {
      if (!toast.classList.contains('is-visible') || toast.classList.contains('is-hiding')) {
        return;
      }

      if (!toast._papToastPaused) {
        return;
      }

      toast._papToastPaused = false;

      if (toast._papToastRemaining <= 0) {
        toast._papToastRemaining = 250;
      }

      scheduleCartToastHide(toast, toast._papToastRemaining);
    });

    var textNode = toast.querySelector('[data-cart-toast-message]');
    if (textNode) {
      textNode.textContent = message || '';
    }

    requestAnimationFrame(function () {
      toast.classList.add('is-visible');
      toast._papToastPaused = false;
    });

    scheduleCartToastHide(toast, toast._papToastDuration);
  }

  window.papShowCartToast = window.papShowCartToast || showCartToast;

  function hideAllCartToasts() {
    Array.prototype.slice.call(document.querySelectorAll('[data-cart-toast]')).forEach(function (toast) {
      hideCartToast(toast);
    });
  }

  if (document.body.classList.contains('woocommerce-cart')) {
    window.addEventListener('beforeunload', hideAllCartToasts);
  }

  function isCartPage() {
    return document.body.classList.contains('woocommerce-cart') || Boolean(document.querySelector('[data-cart-page]'));
  }

  function dispatchCartEvents(data, button) {
    var fragments = data && data.fragments ? data.fragments : {};
    var cartHash = data && data.cart_hash ? data.cart_hash : '';
    var detail = data || {};

    document.body.dispatchEvent(new CustomEvent('pap:added-to-cart', {
      detail: {
        fragments: fragments,
        cartHash: cartHash,
        button: button || null
      }
    }));

    window.dispatchEvent(new CustomEvent('pap:cart-state-changed', {
      detail: detail
    }));
  }

  function applyDrawerPayload(data) {
    if (!data) {
      return;
    }

    var content = document.querySelector('[data-cart-drawer-content]');
    var countLabel = typeof data.count_label === 'string' ? data.count_label : null;
    if (countLabel !== null) {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = countLabel;
      });
    } else if (typeof data.count !== 'undefined') {
      var safeCount = Math.max(0, parseInt(data.count, 10) || 0);
      var fallbackLabel = safeCount === 1 ? '1 produs' : safeCount + ' produse';
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = fallbackLabel;
      });
    }

    if (content && typeof data.items_html === 'string') {
      content.innerHTML = data.items_html;

      var drawer = content.closest('[data-cart-drawer]');
      if (drawer) {
        drawer.classList.toggle('is-empty', !!content.querySelector('.pap-cart-drawer-empty'));
      }
    }

    if (typeof data.total_html === 'string') {
      Array.prototype.slice.call(document.querySelectorAll('[data-cart-drawer-total]')).forEach(function (target) {
        target.innerHTML = data.total_html;
      });
    }

    window.__papLastCartDrawerPayload = data;
    window.dispatchEvent(new CustomEvent('pap:cart-drawer-updated', { detail: data }));
  }

  window.papApplyCartDrawerPayload = window.papApplyCartDrawerPayload || applyDrawerPayload;

  function getProductCardInfo(button) {
    var card = button ? button.closest('.pap-product-card') : null;
    if (!card) {
      return null;
    }

    var link = card.querySelector('.pap-product-card-link');
    var title = card.querySelector('[data-product-name], .pap-product-copy h2, .pap-product-copy h3, .pap-product-copy strong, .pap-product-card-link h2, .pap-product-card-link h3, .pap-product-card-link strong, .pap-product-card > h2, .pap-product-card > h3, .pap-product-card > strong');
    var image = card.querySelector('.pap-product-thumb img');
    var price = card.querySelector('.pap-price');
    var productName = '';

    if (title) {
      productName = title.getAttribute('data-product-name') || title.textContent.trim();
    }

    if (!productName) {
      productName = card.getAttribute('data-product-name') || '';
    }

    return {
      card: card,
      productId: button.getAttribute('data-product-id') || button.getAttribute('data-product_id') || '',
      productUrl: link ? link.getAttribute('href') || '' : '',
      name: productName,
      imageUrl: image ? image.getAttribute('src') || '' : '',
      imageAlt: image ? image.getAttribute('alt') || productName : '',
      priceHtml: price ? price.innerHTML : '',
    };
  }

  function getProductPageInfo(button, form) {
    var productRoot = null;

    if (form) {
      productRoot = form.closest('.product');
    }

    if (!productRoot && button) {
      productRoot = button.closest('.product');
    }

    if (!productRoot) {
      productRoot = document.querySelector('body.single-product .product');
    }

    if (!productRoot) {
      return null;
    }

    var title = productRoot.querySelector('.product_title, h1.product_title, .summary .product_title');
    var image = productRoot.querySelector('.woocommerce-product-gallery img, .wp-post-image');
    var price = productRoot.querySelector('.summary .price, .woocommerce-product-details__short-description + .price, .price');

    return {
      card: productRoot,
      productId: getProductId(button, form),
      productUrl: window.location.href,
      name: title ? title.textContent.trim() : document.title,
      imageUrl: image ? image.getAttribute('src') || image.getAttribute('data-src') || '' : '',
      imageAlt: image ? image.getAttribute('alt') || (title ? title.textContent.trim() : '') : '',
      priceHtml: price ? price.innerHTML : '',
    };
  }

  function getProductInfo(button, form) {
    return getProductCardInfo(button) || getProductPageInfo(button, form);
  }
  function setLoadingState(element, isLoading) {
    if (!element) {
      return;
    }

    var card = element.closest ? element.closest('.pap-product-card') : null;

    element.classList.toggle('is-loading', isLoading);
    element.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    if ('disabled' in element) {
      element.disabled = isLoading;
    }

    if (element.tagName === 'A') {
      element.setAttribute('aria-disabled', isLoading ? 'true' : 'false');
    }

    if (card) {
      card.classList.toggle('is-loading', isLoading);
      card.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }
  }

  function getProductId(button, form) {
    if (button) {
      return button.getAttribute('data-product-id') || button.getAttribute('data-product_id') || button.getAttribute('value') || button.value || '';
    }

    if (!form) {
      return '';
    }

    var addToCartField = form.querySelector('input[name="add-to-cart"], button[name="add-to-cart"]');
    return addToCartField ? (addToCartField.getAttribute('value') || addToCartField.value || '') : '';
  }

  function getQuantity(form) {
    if (!form) {
      return 1;
    }

    var quantityField = form.querySelector('input[name="quantity"], input.qty');
    if (!quantityField) {
      return 1;
    }

    var value = parseInt(quantityField.value, 10);
    return Number.isNaN(value) || value < 1 ? 1 : value;
  }

  function getFallbackUrl(button, form) {
    if (button && button.tagName === 'A') {
      return button.getAttribute('href') || config.shopUrl || window.location.href;
    }

    if (form && form.getAttribute('action')) {
      return form.getAttribute('action');
    }

    return config.shopUrl || window.location.href;
  }

  function shouldHandleForm(form) {
    return !(
      form.classList.contains('variations_form')
      || form.classList.contains('grouped_form')
      || form.classList.contains('external')
    );
  }

  function sendAddToCart(button, form) {
    var productId = getProductId(button, form);
    if (!productId) {
      return;
    }

    var quantity = getQuantity(form);
    var fallbackUrl = getFallbackUrl(button, form);
    var payload = new URLSearchParams({
      action: config.action || 'pap_home_add_to_cart',
      nonce: config.nonce || '',
      product_id: productId,
      quantity: String(quantity)
    });

    perfTime('add-to-cart-total');
    perfTime('ajax-add-to-cart');

    setLoadingState(button, true);

    fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: payload.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error(response && response.data && response.data.message ? response.data.message : 'Add to cart failed');
        }

        var data = response.data || {};
        var drawerPayload = data.cart_drawer || data;

        try {
          window.__papLastCartDrawerPayload = drawerPayload;
          if (data.cart_page) {
            window.__papLastCartPagePayload = data.cart_page;
          }

          applyDrawerPayload(drawerPayload);
        } catch (uiError) {
        if (window.console && typeof window.console.error === 'function') {
            window.console.error(uiError);
          }
        }

        dispatchCartEvents(data, button);

        window.requestAnimationFrame(function () {
          if (isCartPage()) {
            showCartToast('Produsul a fost adăugat în coș');
            return;
          }

          perfTime('modal-open');
          openCartModal(data, { focusTarget: button });
          perfTimeEnd('modal-open');
        });

        if (perfEnabled) {
          perfLog('add-to-cart server timings', data.debug_timings || null);
        }
      })
      .finally(function () {
        if (perfEnabled) {
          perfTimeEnd('ajax-add-to-cart');
          perfTimeEnd('add-to-cart-total');
        }
      })
      .catch(function () {
        if (window.console && typeof window.console.error === 'function') {
          window.console.error('Add to cart AJAX failed.');
        }

        if (form && !document.body.classList.contains('woocommerce-cart')) {
          HTMLFormElement.prototype.submit.call(form);
          return;
        }

        if (!form && !document.body.classList.contains('woocommerce-cart')) {
          window.location.href = fallbackUrl;
        }
      })
      .finally(function () {
        setLoadingState(button, false);
        window.papClearActionBusy();
      });
  }

  cartModalClosers.forEach(function (closer) {
    closer.addEventListener('click', closeCartModal);
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('button.pap-home-add-to-cart, button.add_to_cart_button.ajax_add_to_cart, a.pap-home-add-to-cart, a.add_to_cart_button.ajax_add_to_cart');
    if (!button) {
      return;
    }

    var productId = getProductId(button);
    if (!productId) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    sendAddToCart(button, null);
  }, true);

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('form.cart');
    if (!form || !shouldHandleForm(form)) {
      return;
    }

    var button = form.querySelector('.single_add_to_cart_button');
    var productId = getProductId(button, form);
    if (!productId) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    sendAddToCart(button || form, form);
  }, true);
})();
