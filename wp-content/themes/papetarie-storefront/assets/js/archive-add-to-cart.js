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
  var cartModalLink = cartModal ? cartModal.querySelector('[data-cart-modal-link]') : null;
  var cartModalClosers = cartModal ? Array.prototype.slice.call(cartModal.querySelectorAll('[data-cart-modal-close]')) : [];
  var pendingButtons = new WeakSet();
  var actionStatus = document.querySelector('[data-pap-action-status]');
  var actionBusyCount = window.__papActionBusyCount || 0;

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

  function updateCountBadge(count) {
    var safeCount = Math.max(0, parseInt(count, 10) || 0);
    var countLabel = safeCount === 1 ? '1 produs' : safeCount + ' produse';
    Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
      target.textContent = countLabel;
    });
  }

  function applyDrawerPayload(data) {
    if (!data) {
      return;
    }

    var countLabel = typeof data.count_label === 'string' ? data.count_label : null;
    if (countLabel !== null) {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = countLabel;
      });
    }

    if (typeof data.items_html === 'string') {
      var content = document.querySelector('[data-cart-drawer-content]');
      if (content) {
        content.innerHTML = data.items_html;
      }
    }

    if (typeof data.total_html === 'string') {
      Array.prototype.slice.call(document.querySelectorAll('[data-cart-drawer-total]')).forEach(function (target) {
        target.innerHTML = data.total_html;
      });
    }
  }

  function getDrawerContent() {
    return document.querySelector('[data-cart-drawer-content]');
  }

  function getCountTarget() {
    return document.querySelector('[data-pap-cart-count]');
  }

  function parseCountLabel(label) {
    var value = parseInt(String(label || '').replace(/[^0-9]/g, ''), 10);
    return Number.isNaN(value) ? 0 : value;
  }

  function setCountLabel(count) {
    var safeCount = Math.max(0, parseInt(count, 10) || 0);
    var countLabel = safeCount === 1 ? '1 produs' : safeCount + ' produse';

    Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
      target.textContent = countLabel;
    });

    return countLabel;
  }

  function getProductCardInfo(button) {
    var card = button ? button.closest('.pap-product-card') : null;
    if (!card) {
      return null;
    }

    var link = card.querySelector('.pap-product-card-link');
    var title = card.querySelector('.pap-product-copy h2, .pap-product-copy h3, .pap-product-copy strong');
    var image = card.querySelector('.pap-product-thumb img');
    var price = card.querySelector('.pap-price');

    return {
      card: card,
      productId: button.getAttribute('data-product-id') || button.getAttribute('data-product_id') || '',
      productUrl: link ? link.getAttribute('href') || '' : '',
      name: title ? title.textContent.trim() : '',
      imageUrl: image ? image.getAttribute('src') || '' : '',
      imageAlt: image ? image.getAttribute('alt') || (title ? title.textContent.trim() : '') : '',
      priceHtml: price ? price.innerHTML : '',
    };
  }

  function createDrawerItemElement(data, pending) {
    var article = document.createElement('article');
    article.className = 'pap-cart-drawer-item' + (pending ? ' pap-cart-drawer-item--pending' : '');
    article.setAttribute('data-cart-item-key', data.cart_item_key || '');
    article.setAttribute('data-cart-item-id', data.product_id || '');

    var thumb = document.createElement('a');
    thumb.className = 'pap-cart-drawer-thumb';
    thumb.href = data.product_url || '#';
    if (!data.product_url) {
      thumb.setAttribute('aria-hidden', 'true');
      thumb.setAttribute('tabindex', '-1');
    }

    var img = document.createElement('img');
    img.loading = 'lazy';
    img.alt = data.image_alt || data.name || '';
    img.src = data.image_url || '';
    thumb.appendChild(img);

    var copy = document.createElement('div');
    copy.className = 'pap-cart-drawer-copy';

    var copyHead = document.createElement('div');
    copyHead.className = 'pap-cart-drawer-copy-head';

    var name = document.createElement('a');
    name.className = 'pap-cart-drawer-name';
    name.href = data.product_url || '#';
    name.textContent = data.name || '';
    if (!data.product_url) {
      name.setAttribute('aria-hidden', 'true');
      name.setAttribute('tabindex', '-1');
    }
    copyHead.appendChild(name);
    copy.appendChild(copyHead);

    var headActions = document.createElement('div');
    headActions.className = 'pap-cart-drawer-head-actions';

    var headActionsTop = document.createElement('div');
    headActionsTop.className = 'pap-cart-drawer-head-actions-top';

    var qty = document.createElement('span');
    qty.className = 'pap-cart-drawer-quantity';
    qty.textContent = 'x' + String(Math.max(1, parseInt(data.quantity, 10) || 1));

    var lineTotal = document.createElement('span');
    lineTotal.className = 'pap-cart-drawer-line-total';
    lineTotal.innerHTML = data.price_html || '';

    headActionsTop.appendChild(qty);
    headActionsTop.appendChild(lineTotal);
    headActions.appendChild(headActionsTop);

    article.appendChild(thumb);
    article.appendChild(copy);
    copyHead.appendChild(headActions);
    return article;
  }

  function findDrawerItemByProductId(productId) {
    if (!productId) {
      return null;
    }

    return document.querySelector('[data-cart-item-id="' + String(productId) + '"]');
  }

  function findDrawerItemsContainer() {
    var content = getDrawerContent();
    if (!content) {
      return null;
    }

    var items = content.querySelector('.pap-cart-drawer-items');
    if (items) {
      return items;
    }

    var empty = content.querySelector('.pap-cart-drawer-empty');
    if (empty) {
      var created = document.createElement('div');
      created.className = 'pap-cart-drawer-items';
      content.innerHTML = '';
      content.appendChild(created);
      return created;
    }

    var fallback = document.createElement('div');
    fallback.className = 'pap-cart-drawer-items';
    content.appendChild(fallback);
    return fallback;
  }

  function getDrawerEmptyNode() {
    var content = getDrawerContent();
    return content ? content.querySelector('.pap-cart-drawer-empty') : null;
  }

  function setDrawerTotalHtml(html) {
    if (typeof html !== 'string') {
      return;
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-cart-drawer-total]')).forEach(function (target) {
      target.innerHTML = html;
    });
  }

  function beginOptimisticDrawerUpdate(button, quantity) {
    var info = getProductCardInfo(button);
    if (!info) {
      return null;
    }

    var currentCountLabel = getCountTarget() ? getCountTarget().textContent : '';
    var currentCount = parseCountLabel(currentCountLabel);
    var nextCount = currentCount + Math.max(1, parseInt(quantity, 10) || 1);
    setCountLabel(nextCount);

    var existingRow = findDrawerItemByProductId(info.productId);
    var drawerItems = findDrawerItemsContainer();
    var snapshot = existingRow ? existingRow.cloneNode(true) : null;
    var emptyNode = getDrawerEmptyNode();

    var previewData = {
      cart_item_key: 'pending-' + info.productId + '-' + Date.now(),
      product_id: info.productId,
      product_url: info.productUrl,
      name: info.name,
      image_url: info.imageUrl,
      image_alt: info.imageAlt,
      price_html: info.priceHtml,
      quantity: Math.max(1, parseInt(quantity, 10) || 1)
    };

    if (existingRow) {
      var input = existingRow.querySelector('[data-cart-qty-input]');
      var currentQty = input ? parseInt(input.value, 10) || 1 : 1;
      var nextQty = currentQty + previewData.quantity;
      if (input) {
        input.value = String(nextQty);
      }
      existingRow.setAttribute('data-cart-item-key', previewData.cart_item_key);
      existingRow.classList.add('pap-cart-drawer-item--pending');
    } else if (drawerItems) {
      if (emptyNode) {
        emptyNode.remove();
      }
      drawerItems.appendChild(createDrawerItemElement(previewData, true));
    }

    return {
      info: info,
      quantity: previewData.quantity,
      previousCountLabel: currentCountLabel,
      previousTotalHtml: document.querySelector('[data-cart-drawer-total]') ? document.querySelector('[data-cart-drawer-total]').innerHTML : '',
      existingRow: existingRow,
      existingRowSnapshot: snapshot,
      tempKey: previewData.cart_item_key
    };
  }

  function rollbackOptimisticDrawerUpdate(state) {
    if (!state) {
      return;
    }

    if (state.existingRow && state.existingRowSnapshot && state.existingRow.parentNode) {
      state.existingRow.parentNode.replaceChild(state.existingRowSnapshot, state.existingRow);
    } else if (!state.existingRow) {
      var tempRow = document.querySelector('[data-cart-item-key="' + state.tempKey + '"]');
      if (tempRow && tempRow.parentNode) {
        tempRow.parentNode.removeChild(tempRow);
      }
    }

    if (state.previousCountLabel) {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = state.previousCountLabel;
      });
    }

    if (state.previousTotalHtml) {
      setDrawerTotalHtml(state.previousTotalHtml);
    }
  }

  function finalizeOptimisticDrawerUpdate(state, data) {
    if (!state || !data) {
      return;
    }

    var row = state.existingRow || document.querySelector('[data-cart-item-key="' + state.tempKey + '"]');
    if (!row && data.cart_item_key) {
      var drawerItems = findDrawerItemsContainer();
      if (drawerItems) {
        row = createDrawerItemElement({
          cart_item_key: data.cart_item_key,
          product_id: state.info.productId,
          product_url: state.info.productUrl,
          name: data.name || state.info.name,
          image_url: data.image_url || state.info.imageUrl,
          image_alt: state.info.imageAlt,
          price_html: data.price_html || state.info.priceHtml,
          quantity: data.cart_item_quantity || state.quantity
        }, false);
        drawerItems.appendChild(row);
      }
    }

    if (row) {
      row.classList.remove('pap-cart-drawer-item--pending');
      if (data.cart_item_key) {
        row.setAttribute('data-cart-item-key', data.cart_item_key);
      }
      var qtyLabel = row.querySelector('.pap-cart-drawer-quantity');
      if (qtyLabel && data.cart_item_quantity) {
        qtyLabel.textContent = 'x' + String(data.cart_item_quantity);
      }
      var lineTotal = row.querySelector('.pap-cart-drawer-line-total');
      if (lineTotal && data.price_html) {
        lineTotal.innerHTML = data.price_html;
      }
    }

    if (typeof data.cart_count_label === 'string') {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = data.cart_count_label;
      });
    } else if (typeof data.cart_count !== 'undefined') {
      setCountLabel(data.cart_count);
    }

    if (typeof data.cart_total_html === 'string') {
      setDrawerTotalHtml(data.cart_total_html);
    }
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
        openCartModal(data);
        if (typeof data.cart_count !== 'undefined') {
          updateCountBadge(data.cart_count);
        }
        if (window.papRefreshCartDrawer) {
          window.papRefreshCartDrawer('refresh', data);
        } else {
          window.dispatchEvent(new CustomEvent('pap:cart-updated', { detail: data }));
        }
        applyDrawerPayload(data.cart_drawer || {});
      })
      .catch(function () {
        if (form) {
          HTMLFormElement.prototype.submit.call(form);
          return;
        }

        window.location.href = fallbackUrl;
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
