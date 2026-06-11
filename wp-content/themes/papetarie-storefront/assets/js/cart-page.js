(function () {
  'use strict';

  var config = window.papCartPage || {};
  var updateOverlayText = (config.messages && config.messages.updateOverlay) || 'Coșul se actualizează...';
  var removeOverlayText = (config.messages && config.messages.removeOverlay) || 'Se elimină produsul...';
  var couponOverlayText = (config.messages && config.messages.couponOverlay) || 'Se aplică cuponul...';
  var page = document.querySelector('[data-cart-page]');
  var shell = document.querySelector('[data-cart-page-shell]');
  var overlay = document.querySelector('[data-cart-loading-overlay]');
  var cartForm = document.querySelector('.woocommerce-cart-form');
  var updateButton = document.querySelector('[data-cart-update-submit]');
  var checkoutButton = document.querySelector('[data-cart-checkout]');
  var cartAlert = document.querySelector('[data-cart-alert]');
  var cartAlertText = cartAlert ? cartAlert.querySelector('[data-cart-alert-text]') : null;
  var cartAlertBaseState = cartAlert ? (cartAlert.getAttribute('data-cart-alert-state') || 'none') : 'none';
  var cartAlertBaseMessage = cartAlertText ? cartAlertText.innerHTML.trim() : '';
  var qtyInputs = Array.prototype.slice.call(document.querySelectorAll('input.qty'));
  var stockTooltipTimers = new WeakMap();
  var isProgrammaticCartSubmit = false;

  function getAjaxUrl(action) {
    var endpoint = action || '';

    if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url) {
      return String(window.wc_add_to_cart_params.wc_ajax_url).replace('%%endpoint%%', endpoint);
    }

    if (window.wc_cart_params && window.wc_cart_params.ajax_url) {
      return window.wc_cart_params.ajax_url;
    }

    if (window.wc_add_to_cart_params && window.wc_add_to_cart_params.ajax_url) {
      return window.wc_add_to_cart_params.ajax_url;
    }

    return config.ajaxUrl || '';
  }

  function getQuantityRow(input) {
    return input ? input.closest('[data-cart-item-key]') : null;
  }

  function getQuantityBounds(row, input) {
    var minValue = parseInt((input && input.getAttribute('min')) || row.getAttribute('data-cart-item-min') || '1', 10);
    if (Number.isNaN(minValue) || minValue < 1) {
      minValue = 1;
    }

    var maxRaw = (input && input.getAttribute('max')) || row.getAttribute('data-cart-item-max') || '';
    var maxValue = parseInt(maxRaw, 10);
    if (maxRaw === '' || Number.isNaN(maxValue) || maxValue < minValue) {
      maxValue = 0;
    }

    return {
      min: minValue,
      max: maxValue
    };
  }

  function clampQuantity(value, bounds) {
    var quantity = parseInt(value, 10);
    if (Number.isNaN(quantity)) {
      quantity = bounds.min;
    }

    if (quantity < bounds.min) {
      quantity = bounds.min;
    }

    if (bounds.max > 0 && quantity > bounds.max) {
      quantity = bounds.max;
    }

    return quantity;
  }

  function getQuantityValue(input, bounds) {
    var quantity = parseInt(input && input.value, 10);
    if (Number.isNaN(quantity)) {
      quantity = bounds.min;
    }

    if (quantity < bounds.min) {
      quantity = bounds.min;
    }

    return quantity;
  }

  function getCommittedValue(input) {
    return String(input.getAttribute('data-cart-committed-value') || input.defaultValue || input.value || '1');
  }

  function setCommittedValue(input, value) {
    input.setAttribute('data-cart-committed-value', String(value));
  }

  function normalizeQuantityInput(input) {
    var row = getQuantityRow(input);
    if (!row) {
      return;
    }

    var bounds = getQuantityBounds(row, input);
    var value = getQuantityValue(input, bounds);

    if (String(value) !== String(input.value)) {
      input.value = String(value);
    }
  }

  function updateQuantityButtonState(input) {
    var row = getQuantityRow(input);
    if (!row || !input) {
      return;
    }

    var bounds = getQuantityBounds(row, input);
    var currentValue = getQuantityValue(input, bounds);
    var minusButton = row.querySelector('[data-cart-qty-step="-1"]');
    var plusButton = row.querySelector('[data-cart-qty-step="1"]');
    var isAtMin = currentValue <= bounds.min;
    var isAtMax = bounds.max > 0 && currentValue >= bounds.max;
    var isStockInsufficient = bounds.max > 0 && currentValue > bounds.max && !row.classList.contains('is-out-of-stock');
    var isStockLimit = bounds.max > 0 && isAtMax && !row.classList.contains('is-out-of-stock') && !isStockInsufficient;
    var stockTooltipTrigger = row.querySelector('[data-cart-stock-tooltip-trigger]');
    var stockTooltip = row.querySelector('[data-cart-stock-tooltip]');
    var stockTooltipText = String(row.getAttribute('data-cart-item-stock-limit-text') || '').trim();

    if (minusButton) {
      minusButton.disabled = Boolean(isAtMin || row.classList.contains('is-out-of-stock'));
      minusButton.setAttribute('aria-disabled', minusButton.disabled ? 'true' : 'false');
    }

    if (plusButton) {
      plusButton.disabled = Boolean(isAtMax || row.classList.contains('is-out-of-stock'));
      plusButton.setAttribute('aria-disabled', plusButton.disabled ? 'true' : 'false');
    }

    row.classList.toggle('is-stock-insufficient', isStockInsufficient);
    row.classList.toggle('is-stock-limit', isStockLimit);

    if (stockTooltipTrigger) {
      stockTooltipTrigger.hidden = !isStockLimit;
      stockTooltipTrigger.setAttribute('aria-hidden', isStockLimit ? 'false' : 'true');
      stockTooltipTrigger.setAttribute('aria-label', stockTooltipText || 'Informații despre stoc');
      stockTooltipTrigger.setAttribute('aria-expanded', 'false');
    }

    if (stockTooltip) {
      stockTooltip.textContent = stockTooltipText;
      if (!isStockLimit) {
        hideStockTooltip(row);
      }
    }
  }

  function maybeShowStockTooltipForInput(input) {
    var row = getQuantityRow(input);
    if (!row || row.classList.contains('is-out-of-stock')) {
      return;
    }

    var bounds = getQuantityBounds(row, input);
    if (bounds.max <= 0) {
      return;
    }

    var currentValue = getQuantityValue(input, bounds);
    var committedValue = parseInt(getCommittedValue(input), 10);
    if (Number.isNaN(committedValue)) {
      committedValue = bounds.min;
    }

    if (currentValue < bounds.max || committedValue >= bounds.max) {
      return;
    }

    var stockTooltipText = String(row.getAttribute('data-cart-item-stock-limit-text') || '').trim();
    if (!stockTooltipText) {
      return;
    }

    showStockTooltip(row, stockTooltipText);
  }

  function updateAllQuantityButtonStates() {
    qtyInputs.forEach(function (input) {
      updateQuantityButtonState(input);
    });
  }

  function clearStockTooltipTimer(row) {
    var timerId = stockTooltipTimers.get(row);
    if (timerId) {
      window.clearTimeout(timerId);
      stockTooltipTimers.delete(row);
    }
  }

  function hideStockTooltip(row) {
    if (!row) {
      return;
    }

    var tooltip = row.querySelector('[data-cart-stock-tooltip]');
    if (tooltip) {
      tooltip.hidden = true;
      tooltip.setAttribute('aria-hidden', 'true');
    }

    var trigger = row.querySelector('[data-cart-stock-tooltip-trigger]');
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    row.classList.remove('is-stock-tooltip-visible');
    clearStockTooltipTimer(row);
  }

  function showStockTooltip(row, text) {
    if (!row || !text) {
      return;
    }

    var tooltip = row.querySelector('[data-cart-stock-tooltip]');
    if (!tooltip) {
      return;
    }

    tooltip.textContent = text;
    tooltip.hidden = false;
    tooltip.setAttribute('aria-hidden', 'false');
    tooltip.setAttribute('role', 'tooltip');
    row.classList.add('is-stock-tooltip-visible');

    var trigger = row.querySelector('[data-cart-stock-tooltip-trigger]');
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'true');
    }

    clearStockTooltipTimer(row);

    stockTooltipTimers.set(
      row,
      window.setTimeout(function () {
        hideStockTooltip(row);
      }, 2200)
    );
  }

  function getStockTooltipTarget(event) {
    return event.target.closest('[data-cart-stock-tooltip-trigger], [data-cart-qty-step="1"]');
  }

  function maybeShowStockTooltip(target) {
    if (!target) {
      return;
    }

    var row = target.closest('[data-cart-item-key]');
    if (!row || row.classList.contains('is-out-of-stock')) {
      return;
    }

    var input = row.querySelector('input.qty');
    if (!input) {
      return;
    }

    var bounds = getQuantityBounds(row, input);
    var currentValue = clampQuantity(input.value, bounds);
    if (!(bounds.max > 0 && currentValue >= bounds.max)) {
      return;
    }

    var stockTooltipText = String(row.getAttribute('data-cart-item-stock-limit-text') || '').trim();
    if (!stockTooltipText) {
      return;
    }

    showStockTooltip(row, stockTooltipText);
  }

  function handleStockTooltipOver(event) {
    var target = getStockTooltipTarget(event);
    if (!target) {
      return;
    }

    maybeShowStockTooltip(target);
  }

  function handleStockTooltipOut(event) {
    var row = event.target.closest('[data-cart-item-key]');
    if (!row) {
      return;
    }

    var relatedTarget = event.relatedTarget;
    if (relatedTarget && row.contains(relatedTarget)) {
      return;
    }

    hideStockTooltip(row);
  }

  function handleStockTooltipClick(event) {
    var trigger = event.target.closest('[data-cart-stock-tooltip-trigger]');
    if (!trigger) {
      return;
    }

    event.preventDefault();
    maybeShowStockTooltip(trigger);
  }

  function isCartDirty() {
    return qtyInputs.some(function (input) {
      return String(input.value) !== getCommittedValue(input);
    });
  }

  function hasUnavailableItems() {
    return Boolean(document.querySelector('[data-cart-item-stock-status="outofstock"], .pap-cart-item.is-out-of-stock'));
  }

  function hasStockInsufficientItems() {
    return Boolean(document.querySelector('[data-cart-item-stock-status="stock-insufficient"], .pap-cart-item.is-stock-insufficient'));
  }

  function setUpdateButtonState(isDirty) {
    if (!updateButton) {
      return;
    }

    updateButton.disabled = !isDirty;
    updateButton.setAttribute('aria-disabled', isDirty ? 'false' : 'true');
    updateButton.classList.toggle('is-dirty', isDirty);
  }

  function setCartAlertState(isDirty) {
    if (!cartAlert) {
      return;
    }

    if (!cartAlertText) {
      return;
    }

    if (isDirty) {
      cartAlert.setAttribute('data-cart-alert-state', 'dirty');
      cartAlertText.innerHTML = 'Actualizează coșul pentru a continua.';
      cartAlert.hidden = false;
      cartAlert.setAttribute('aria-hidden', 'false');
      return;
    }

    cartAlert.setAttribute('data-cart-alert-state', cartAlertBaseState);

    if (cartAlertBaseState === 'none' || !cartAlertBaseMessage) {
      cartAlert.hidden = true;
      cartAlert.setAttribute('aria-hidden', 'true');
      cartAlertText.innerHTML = '';
      return;
    }

    cartAlert.hidden = false;
    cartAlert.setAttribute('aria-hidden', 'false');
    cartAlertText.innerHTML = cartAlertBaseMessage;
  }

  function setCheckoutState(state) {
    if (!checkoutButton) {
      return;
    }

    var isDirty = Boolean(state && state.dirty);
    var isUnavailable = Boolean(state && state.unavailable);
    var isStockIssue = Boolean(state && state.stockIssue);
    var isMinimumOrder = Boolean(state && state.minimumOrder);
    var isBlocked = isDirty || isUnavailable || isStockIssue || isMinimumOrder;

    checkoutButton.classList.toggle('is-disabled', isBlocked);
    checkoutButton.setAttribute('aria-disabled', isBlocked ? 'true' : 'false');
    checkoutButton.setAttribute('tabindex', isBlocked ? '-1' : '0');
  }

  function syncDirtyState() {
    var dirty = isCartDirty();
    var unavailable = hasUnavailableItems();
    var stockIssue = hasStockInsufficientItems();
    var minimumOrder = cartAlertBaseState === 'minimum-order';
    setUpdateButtonState(dirty);
    setCartAlertState(dirty);
    setCheckoutState({
      dirty: dirty,
      unavailable: unavailable,
      stockIssue: stockIssue,
      minimumOrder: minimumOrder
    });

    if (cartForm) {
      cartForm.classList.toggle('is-dirty', dirty);
      cartForm.classList.toggle('has-unavailable-items', unavailable);
      cartForm.classList.toggle('has-stock-issues', stockIssue);
    }
  }

  function setOverlayVisible(isVisible, text) {
    if (!overlay) {
      return;
    }

    var panelText = overlay.querySelector('.pap-cart-loading-overlay__text');
    if (panelText && typeof text === 'string' && text) {
      panelText.textContent = text;
    }

    overlay.hidden = !isVisible;
    overlay.setAttribute('aria-hidden', isVisible ? 'false' : 'true');

    if (shell) {
      shell.toggleAttribute('inert', Boolean(isVisible));
      shell.classList.toggle('is-locked', Boolean(isVisible));
      shell.setAttribute('aria-busy', isVisible ? 'true' : 'false');
    }

    document.body.classList.toggle('pap-cart-is-loading', Boolean(isVisible));
  }

  function showOverlay(text) {
    setOverlayVisible(true, text);
  }

  function setAllCommittedValuesFromDom() {
    qtyInputs = Array.prototype.slice.call(document.querySelectorAll('input.qty'));
    qtyInputs.forEach(function (input) {
      setCommittedValue(input, input.value);
    });
    updateAllQuantityButtonStates();
    syncDirtyState();
  }

  function handleQtyStepClick(event) {
    var button = event.target.closest('[data-cart-qty-step]');
    if (!button) {
      return;
    }

    var row = button.closest('[data-cart-item-key]');
    if (!row) {
      return;
    }

    event.preventDefault();

    var input = row.querySelector('input.qty');
    if (!input) {
      return;
    }

    var bounds = getQuantityBounds(row, input);
    var step = parseInt(button.getAttribute('data-cart-qty-step') || '0', 10);
    if (Number.isNaN(step) || step === 0) {
      step = 1;
    }

    var currentValue = clampQuantity(input.value, bounds);
    var nextValue = clampQuantity(currentValue + step, bounds);
    input.value = String(nextValue);
    updateQuantityButtonState(input);
    if (step > 0 && bounds.max > 0 && nextValue >= bounds.max) {
      maybeShowStockTooltip(input);
    }
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.focus({ preventScroll: true });
  }

  function handleQuantityInput(event) {
    var input = event.target.closest('input.qty');
    if (!input) {
      return;
    }

    normalizeQuantityInput(input);
    updateQuantityButtonState(input);
    maybeShowStockTooltipForInput(input);
    syncDirtyState();
  }

  function handleQuantityChange(event) {
    var input = event.target.closest('input.qty');
    if (!input) {
      return;
    }

    normalizeQuantityInput(input);
    updateQuantityButtonState(input);
    maybeShowStockTooltipForInput(input);
    syncDirtyState();
  }

  function submitCartForm() {
    if (!cartForm) {
      return;
    }

    showOverlay(updateOverlayText);
    isProgrammaticCartSubmit = true;

    window.requestAnimationFrame(function () {
      if (updateButton && typeof updateButton.click === 'function') {
        updateButton.click();
        return;
      }

      if (typeof cartForm.submit === 'function') {
        cartForm.submit();
      }
    });
  }

  function handleCartFormSubmit(event) {
    if (!cartForm || !event.target.closest('.woocommerce-cart-form')) {
      return;
    }

    if (isProgrammaticCartSubmit) {
      isProgrammaticCartSubmit = false;
      return;
    }

    if (!isCartDirty()) {
      return;
    }

    event.preventDefault();
    submitCartForm();
  }

  function handleCouponSubmit(event) {
    var form = event.target.closest('[data-cart-coupon-form]');
    if (!form) {
      return;
    }

    event.preventDefault();
    showOverlay(couponOverlayText);

    window.requestAnimationFrame(function () {
      if (typeof form.submit === 'function') {
        form.submit();
      }
    });
  }

  function handleRemoveClick(event) {
    var button = event.target.closest('[data-cart-remove-item]');
    if (!button) {
      return;
    }

    var href = button.getAttribute('href');
    if (!href) {
      return;
    }

    event.preventDefault();
    showOverlay(removeOverlayText);

    window.requestAnimationFrame(function () {
      window.location.href = href;
    });
  }

  function handleCheckoutClick(event) {
    var link = event.target.closest('[data-cart-checkout]');
    if (!link) {
      return;
    }

    if (link.classList.contains('is-disabled')) {
      event.preventDefault();
    }
  }

  function scrollHorizontalSlider(slider, direction) {
    if (!slider) {
      return;
    }

    var card = slider.querySelector('.pap-product-card');
    var amount = card ? card.offsetWidth + 16 : 260;
    slider.scrollBy({
      left: direction * amount * 2,
      behavior: 'smooth'
    });
  }

  function initHorizontalSliderShell(shell) {
    if (!shell) {
      return;
    }

    var slider = shell.querySelector('[data-featured-slider]');
    var prev = shell.querySelector('[data-featured-prev]');
    var next = shell.querySelector('[data-featured-next]');

    if (!slider) {
      return;
    }

    if (prev) {
      prev.addEventListener('click', function () {
        scrollHorizontalSlider(slider, -1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        scrollHorizontalSlider(slider, 1);
      });
    }
  }

  function initHorizontalSliders() {
    Array.prototype.slice.call(document.querySelectorAll('.pap-featured-slider-shell')).forEach(initHorizontalSliderShell);
  }

  function handleCouponRemoveClick(event) {
    var button = event.target.closest('[data-cart-remove-coupon]');
    if (!button) {
      return;
    }

    event.preventDefault();

    var couponCode = button.getAttribute('data-coupon-code') || '';
    if (!couponCode) {
      return;
    }

    var ajaxAction = (config.actions && config.actions.removeCoupon) || 'pap_cart_remove_coupon';
    var ajaxUrl = getAjaxUrl(ajaxAction);
    if (!ajaxUrl) {
      showOverlay(removeOverlayText);
      window.requestAnimationFrame(function () {
        window.location.reload();
      });
      return;
    }

    showOverlay(removeOverlayText);

    var formData = new FormData();
    formData.append('action', ajaxAction);
    formData.append('nonce', config.nonce || '');
    formData.append('coupon_code', couponCode);

    fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return {
            ok: response.ok,
            data: data
          };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data || result.data.success !== true) {
          throw result.data || new Error('Request failed');
        }

        window.location.reload();
      })
      .catch(function () {
        setOverlayVisible(false, updateOverlayText);
      });
  }

  function init() {
    if (!page || !cartForm) {
      return;
    }

    if (overlay) {
      setOverlayVisible(false, updateOverlayText);
    }

    setAllCommittedValuesFromDom();

    document.addEventListener('click', function (event) {
      handleQtyStepClick(event);
      handleRemoveClick(event);
      handleCouponRemoveClick(event);
      handleCheckoutClick(event);
      handleStockTooltipClick(event);
    });

    document.addEventListener('input', handleQuantityInput);
    document.addEventListener('change', handleQuantityChange);
    document.addEventListener('mouseover', handleStockTooltipOver);
    document.addEventListener('mouseout', handleStockTooltipOut);
    document.addEventListener('focusin', handleStockTooltipOver);
    document.addEventListener('focusout', handleStockTooltipOut);
    document.addEventListener('submit', function (event) {
      handleCartFormSubmit(event);
      handleCouponSubmit(event);
    });

    initHorizontalSliders();
    syncDirtyState();
  }

  init();
})();
