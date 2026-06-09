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

  function applyDrawerPayload(data) {
    if (!data) {
      return;
    }

    if (window.papApplyCartDrawerPayload) {
      window.papApplyCartDrawerPayload(data);
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

    var main = document.createElement('div');
    main.className = 'pap-cart-drawer-main';

    var row = document.createElement('div');
    row.className = 'pap-cart-drawer-row';

    var name = document.createElement('a');
    name.className = 'pap-cart-drawer-name';
    name.href = data.product_url || '#';
    name.textContent = data.name || '';
    if (!data.product_url) {
      name.setAttribute('aria-hidden', 'true');
      name.setAttribute('tabindex', '-1');
    }
    row.appendChild(name);

    var qty = document.createElement('span');
    qty.className = 'pap-cart-drawer-quantity';
    qty.textContent = '×' + String(Math.max(1, parseInt(data.quantity, 10) || 1));

    row.appendChild(qty);

    var unitPrice = document.createElement('span');
    unitPrice.className = 'pap-cart-drawer-unit-price';
    unitPrice.textContent = formatUnitPriceText(data.cart_item_unit_price_text || stripHtmlToText(data.price_html || data.cart_item_total_html || ''));

    var side = document.createElement('div');
    side.className = 'pap-cart-drawer-side';

    var lineTotal = document.createElement('span');
    lineTotal.className = 'pap-cart-drawer-line-total';
    lineTotal.innerHTML = data.cart_item_total_html || data.price_html || '';

    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'pap-cart-drawer-remove';
    remove.setAttribute('data-cart-remove-item', '');
    remove.setAttribute('data-cart-item-key', data.cart_item_key || '');
    remove.setAttribute('data-cart-item-name', data.name || '');
    remove.setAttribute('aria-label', 'Elimină produsul din coș');
    remove.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';

    main.appendChild(row);
    main.appendChild(unitPrice);
    copy.appendChild(main);
    side.appendChild(lineTotal);
    side.appendChild(remove);
    copy.appendChild(side);
    article.appendChild(thumb);
    article.appendChild(copy);
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
      cart_item_unit_price_text: stripHtmlToText(info.priceHtml),
      cart_item_total_html: info.priceHtml,
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
          cart_item_unit_price_text: data.cart_item_unit_price_text || stripHtmlToText(data.price_html || state.info.priceHtml),
          cart_item_total_html: data.cart_item_total_html || data.price_html || state.info.priceHtml,
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
        qtyLabel.textContent = '×' + String(data.cart_item_quantity);
      }
      var unitPrice = row.querySelector('.pap-cart-drawer-unit-price');
      if (unitPrice) {
        unitPrice.textContent = formatUnitPriceText(data.cart_item_unit_price_text || stripHtmlToText(data.price_html || state.info.priceHtml));
      }
      var lineTotal = row.querySelector('.pap-cart-drawer-line-total');
      if (lineTotal) {
        lineTotal.innerHTML = data.cart_item_total_html || data.price_html || '';
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
    var productInfo = getProductInfo(button, form);
    var optimisticState = productInfo && getProductCardInfo(button) ? beginOptimisticDrawerUpdate(button, quantity) : null;
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
        if (optimisticState) {
          finalizeOptimisticDrawerUpdate(optimisticState, data.cart_drawer || data);
        }

        applyDrawerPayload(data.cart_drawer || data);

        window.requestAnimationFrame(function () {
          perfTime('modal-open');
          openCartModal(data, { focusTarget: button });
          perfTimeEnd('modal-open');
        });

        if (perfEnabled) {
          perfLog('add-to-cart server timings', data.debug_timings || null);
          perfTimeEnd('ajax-add-to-cart');
          perfTimeEnd('add-to-cart-total');
        }
      })
      .catch(function () {
        if (optimisticState) {
          rollbackOptimisticDrawerUpdate(optimisticState);
          closeCartModal();
        }

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
