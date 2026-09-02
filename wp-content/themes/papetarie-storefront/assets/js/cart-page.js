(function () {
  'use strict';

  var config = window.papCartPage || {};
  var updateOverlayText = (config.messages && config.messages.updateOverlay) || 'Coșul se actualizează...';
  var removeOverlayText = (config.messages && config.messages.removeOverlay) || 'Se elimină produsul...';
  var removeErrorText = (config.messages && config.messages.removeError) || 'Nu am putut elimina produsul. Încearcă din nou.';
  var minimumOrderData = config.minimumOrder || {};
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
  var initializedSliderShells = typeof WeakSet === 'function' ? new WeakSet() : null;
  // Vezi comentariul din scrollHorizontalSlider() mai jos - indexul "tintit"
  // de noi la ultimul click, separat de scrollLeft-ul real (care e undeva
  // la mijloc intre doua carduri in timp ce animatia "smooth" anterioara
  // inca ruleaza).
  var sliderTargetIndex = typeof WeakMap === 'function' ? new WeakMap() : null;
  var sliderScrollTimers = typeof WeakMap === 'function' ? new WeakMap() : null;
  var sliderAnimations = typeof WeakMap === 'function' ? new WeakMap() : null;

  function refreshCartPageRefs() {
    page = document.querySelector('[data-cart-page]');
    shell = document.querySelector('[data-cart-page-shell]');
    overlay = document.querySelector('[data-cart-loading-overlay]');
    cartForm = document.querySelector('.woocommerce-cart-form');
    updateButton = document.querySelector('[data-cart-update-submit]');
    checkoutButton = document.querySelector('[data-cart-checkout]');
    cartAlert = document.querySelector('[data-cart-alert]');
    cartAlertText = cartAlert ? cartAlert.querySelector('[data-cart-alert-text]') : null;
    cartAlertBaseState = cartAlert ? (cartAlert.getAttribute('data-cart-alert-state') || 'none') : 'none';
    cartAlertBaseMessage = cartAlertText ? cartAlertText.innerHTML.trim() : '';
    cartMain = document.querySelector('[data-cart-page] .pap-cart-main');
    cartLayout = document.querySelector('[data-cart-page] .pap-cart-layout');
    cartLeftColumn = document.querySelector('[data-cart-left-column]');
    cartRightColumn = document.querySelector('[data-cart-right-column]');
    cartEmptyStack = document.querySelector('[data-cart-empty-stack]');
  }

  var cartMain = document.querySelector('[data-cart-page] .pap-cart-main');
  var cartLayout = document.querySelector('[data-cart-page] .pap-cart-layout');
  var cartLeftColumn = document.querySelector('[data-cart-left-column]');
  var cartRightColumn = document.querySelector('[data-cart-right-column]');
  var cartEmptyStack = document.querySelector('[data-cart-empty-stack]');

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

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
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
      cartAlertText.innerHTML = 'Modificările din coș trebuie actualizate înainte de a continua.';
      cartAlert.hidden = false;
      cartAlert.setAttribute('aria-hidden', 'false');
      return;
    }

    cartAlert.setAttribute('data-cart-alert-state', cartAlertBaseState);

    if (cartAlertBaseState === 'none' || !cartAlertBaseMessage) {
      if (minimumOrderData && minimumOrderData.blocked && minimumOrderData.message) {
        cartAlert.setAttribute('data-cart-alert-state', 'minimum-order');
        cartAlert.hidden = false;
        cartAlert.setAttribute('aria-hidden', 'false');
        cartAlertText.innerHTML = minimumOrderData.message;
        return;
      }

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
    var minimumOrder = Boolean((minimumOrderData && minimumOrderData.blocked) || cartAlertBaseState === 'minimum-order');
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

  function replaceCartItemsHtml(html) {
    if (typeof html !== 'string' || !html) {
      return;
    }

    var existingItems = document.querySelector('.pap-cart-items');
    var wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    var nextItems = wrapper.firstElementChild;

    if (existingItems && nextItems) {
      if (nextItems.classList && nextItems.classList.contains('pap-cart-items')) {
        existingItems.outerHTML = nextItems.outerHTML;
        return;
      }

      existingItems.innerHTML = html;
      return;
    }

    if (cartForm && nextItems) {
      cartForm.insertAdjacentElement('afterbegin', nextItems);
    }
  }

  function buildCartColumnsHtml(payload) {
    var formAction = payload && typeof payload.form_action === 'string' && payload.form_action
      ? payload.form_action
      : window.location.href;
    var nonceValue = payload && typeof payload.nonce === 'string' ? payload.nonce : '';
    var itemsHtml = typeof payload.items_html === 'string' ? payload.items_html : '';
    var summaryHtml = typeof payload.summary_html === 'string' ? payload.summary_html : '';
    var alertHtml = '';

    if (cartAlertBaseMessage) {
      alertHtml = [
        '<div class="pap-cart-alert-area">',
        '<div class="pap-cart-alert" data-cart-alert data-cart-alert-state="',
        escapeHtml(cartAlertBaseState || 'none'),
        '" role="status" aria-live="polite">',
        '<svg class="pap-cart-alert__icon" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">',
        '<path d="M10 1.66699L18.3337 17.5003H1.66699L10 1.66699Z" fill="#F59E0B"/>',
        '<path d="M10 6.66699V11.667" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round"/>',
        '<path d="M10 14.167H10.0083" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round"/>',
        '</svg>',
        '<span class="pap-cart-alert__text" data-cart-alert-text>',
        cartAlertBaseMessage,
        '</span>',
        '</div>',
        '</div>'
      ].join('');
    }

    return [
      '<h1 class="pap-cart-title">Coșul tău</h1>',
      alertHtml,
      '<div class="pap-cart-columns">',
      '<div class="pap-cart-left" data-cart-left-column>',
      '<div class="pap-cart-headings" aria-hidden="true"><span>PRODUS</span><span>TOTAL</span></div>',
      '<form id="pap-cart-form" class="woocommerce-cart-form pap-cart-form" action="', escapeHtml(formAction), '" method="post">',
      itemsHtml,
      '<input type="hidden" name="woocommerce-cart-nonce" value="', escapeHtml(nonceValue), '">',
      '<input type="hidden" name="update_cart" value="1">',
      '</form>',
      '<div class="pap-cart-actions-row">',
      '<button type="submit" class="pap-cart-update-submit" data-cart-update-submit form="pap-cart-form" name="update_cart" value="1">Actualizează coșul</button>',
      '</div>',
      '</div>',
      '<aside class="pap-cart-right" aria-label="Sumar comandă" data-cart-right-column>',
      '<div class="pap-cart-summary">',
      summaryHtml,
      '</div>',
      '</aside>',
      '</div>'
    ].join('');
  }

  function replaceEmptyCartLayout(payload) {
    if (!cartMain || !payload || !payload.has_items) {
      return false;
    }

    cartMain.classList.remove('pap-cart-main--empty');
    cartMain.innerHTML = buildCartColumnsHtml(payload);
    cartLayout = document.querySelector('[data-cart-page] .pap-cart-layout');
    if (cartLayout) {
      cartLayout.classList.remove('pap-cart-layout--empty');
    }

    refreshCartPageRefs();
    return true;
  }

  function replaceCartSummaryHtml(html) {
    if (typeof html !== 'string' || !html) {
      return;
    }

    var summary = document.querySelector('[data-cart-summary-card]');
    if (!summary) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    var nextSummary = wrapper.firstElementChild;
    if (!nextSummary) {
      return;
    }

    summary.parentNode.replaceChild(nextSummary, summary);
    refreshCartPageRefs();
  }

  function replaceCartPageHtml(html) {
    if (typeof html !== 'string' || !html || !page) {
      return false;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    var nextPage = wrapper.querySelector('[data-cart-page]');

    if (!nextPage) {
      return false;
    }

    page.outerHTML = nextPage.outerHTML;
    refreshCartPageRefs();
    initHorizontalSliders();
    return true;
  }

  function applyCartPagePayload(payload) {
    if (!payload) {
      return;
    }

    var normalizedPayload = payload && payload.cart_page && !payload.items_html && !payload.summary_html
      ? payload.cart_page
      : payload;

    var isEmptyState = typeof normalizedPayload.is_empty === 'boolean'
      ? normalizedPayload.is_empty
      : !normalizedPayload.has_items;
    var currentIsEmpty = !!(page && page.classList.contains('pap-cart-page--empty'));

    if (typeof normalizedPayload.page_html === 'string' && normalizedPayload.page_html && isEmptyState !== currentIsEmpty) {
      if (replaceCartPageHtml(normalizedPayload.page_html)) {
        qtyInputs = Array.prototype.slice.call(document.querySelectorAll('input.qty'));
        setAllCommittedValuesFromDom();
        syncDirtyState();
        return;
      }
    }

    if (typeof normalizedPayload.items_html === 'string') {
      if (!cartForm && normalizedPayload.has_items) {
        replaceEmptyCartLayout(normalizedPayload);
      } else {
        replaceCartItemsHtml(normalizedPayload.items_html);
      }
    }

    if (typeof normalizedPayload.summary_html === 'string') {
      replaceCartSummaryHtml(normalizedPayload.summary_html);
    }

    if (typeof normalizedPayload.count_label === 'string') {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = normalizedPayload.count_label;
      });
    } else if (typeof normalizedPayload.count !== 'undefined') {
      var safeCount = Math.max(0, parseInt(normalizedPayload.count, 10) || 0);
      var countLabel = safeCount === 1 ? '1 produs' : safeCount + ' produse';
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = countLabel;
      });
    }

    refreshCartPageRefs();
    qtyInputs = Array.prototype.slice.call(document.querySelectorAll('input.qty'));
    setAllCommittedValuesFromDom();
    syncDirtyState();
  }

  window.papApplyCartPagePayload = window.papApplyCartPagePayload || applyCartPagePayload;

  window.addEventListener('pap:cart-state-changed', function (event) {
    var detail = event && event.detail ? event.detail : null;
    if (!detail) {
      return;
    }

    applyCartPagePayload(detail.cart_page || detail);
  });

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

  function clearCouponErrorState() {
    var couponInput = document.querySelector('[data-cart-coupon-input]');
    var couponError = document.querySelector('[data-cart-coupon-error]');

    if (couponInput) {
      couponInput.removeAttribute('aria-invalid');
    }

    if (couponError) {
      couponError.parentNode && couponError.parentNode.removeChild(couponError);
    }
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

    clearCouponErrorState();
  }

  function handleCouponInput(event) {
    var input = event.target.closest('[data-cart-coupon-input]');
    if (!input) {
      return;
    }

    clearCouponErrorState();
  }

  function handleCouponAccordionToggle(event) {
    var button = event.target.closest('[data-cart-coupon-toggle]');
    if (!button) {
      return;
    }

    event.preventDefault();

    var accordion = button.closest('[data-cart-coupon-accordion]');
    if (!accordion) {
      return;
    }

    var panel = accordion.querySelector('[data-cart-coupon-panel]');
    if (!panel) {
      return;
    }

    var isOpen = button.getAttribute('aria-expanded') === 'true';
    var nextOpen = !isOpen;

    button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
    accordion.classList.toggle('is-open', nextOpen);

    if (nextOpen) {
      panel.removeAttribute('hidden');
    } else {
      panel.setAttribute('hidden', '');
    }
  }

  function fallbackToFullReload(href) {
    window.requestAnimationFrame(function () {
      window.location.href = href;
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

    var cartItemKey = button.getAttribute('data-cart-item-key');
    var ajaxUrl = config.ajaxUrl;
    var nonce = config.removeNonce;

    // Eliminarea facea pana acum o navigare completa a paginii
    // (window.location.href) - "nu merge fluid", semnalat de user
    // 2026-08-30. Reutilizam acelasi endpoint AJAX ca mini-cosul din
    // header (pap_cart_drawer_sync, deja testat acolo) - fara reload,
    // doar randurile/sumarul se actualizeaza. Daca requestul esueaza (retea
    // etc.), cadem cu gratie pe navigarea completa de dinainte, ca actiunea
    // sa se termine oricum.
    if (!cartItemKey || !ajaxUrl || !nonce) {
      fallbackToFullReload(href);
      return;
    }

    var params = new URLSearchParams();
    params.append('action', 'pap_cart_drawer_sync');
    params.append('nonce', nonce);
    params.append('mode', 'remove');
    params.append('cart_item_key', cartItemKey);

    fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString()
    })
      .then(function (response) { return response.json(); })
      .then(function (response) {
        if (!response || !response.success || !response.data) {
          throw new Error(removeErrorText);
        }

        var data = response.data;
        if (typeof window.papApplyCartPagePayload === 'function') {
          window.papApplyCartPagePayload(data.cart_page || data);
        }
        window.dispatchEvent(new CustomEvent('pap:cart-state-changed', { detail: data }));

        // [data-pap-cart-count-badge] (cifra din header) e populat doar
        // server-side la incarcarea paginii si prin sistemul de "fragments"
        // al WooCommerce, care se declanseaza pe evenimente jQuery pe care
        // acest fetch() nu le emite - fara asta ramanea cu numarul vechi
        // dupa o eliminare AJAX. Actualizat direct din raspuns.
        if (typeof data.count !== 'undefined') {
          var safeBadgeCount = Math.max(0, parseInt(data.count, 10) || 0);
          Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count-badge]')).forEach(function (badge) {
            badge.textContent = String(safeBadgeCount);
            badge.hidden = safeBadgeCount === 0;
          });
        }

        setOverlayVisible(false);
      })
      .catch(function () {
        fallbackToFullReload(href);
      });
  }

  function handleCheckoutClick(event) {
    var link = event.target.closest('[data-cart-checkout]');
    if (!link) {
      return;
    }

    var minimumBlocked = String(link.getAttribute('data-cart-minimum-order-blocked') || '0') === '1'
      || Boolean(minimumOrderData && minimumOrderData.blocked);

    if (link.classList.contains('is-disabled') || String(link.getAttribute('aria-disabled') || 'false') === 'true' || minimumBlocked) {
      event.preventDefault();
    }
  }

  function getSliderMetrics(slider) {
    var card = slider.querySelector('.pap-product-card');
    if (!card) {
      return null;
    }

    var gap = parseFloat(getComputedStyle(slider.querySelector('.pap-product-grid') || slider).columnGap) || 0;
    var amount = card.getBoundingClientRect().width + gap;
    var maxScroll = slider.scrollWidth - slider.clientWidth;
    var maxIndex = Math.max(0, Math.round(maxScroll / amount));

    return { amount: amount, maxScroll: maxScroll, maxIndex: maxIndex };
  }

  function indexFromScrollLeft(slider, amount, maxIndex) {
    return Math.max(0, Math.min(maxIndex, Math.round(slider.scrollLeft / amount)));
  }

  function getTrackedSliderIndex(slider, amount, maxIndex) {
    if (sliderTargetIndex && sliderTargetIndex.has(slider)) {
      return sliderTargetIndex.get(slider);
    }

    return indexFromScrollLeft(slider, amount, maxIndex);
  }

  function scheduleManualSliderResync(slider) {
    if (!sliderScrollTimers || (sliderAnimations && sliderAnimations.has(slider))) {
      return;
    }

    var existing = sliderScrollTimers.get(slider);
    if (existing) {
      window.clearTimeout(existing);
    }

    var timer = window.setTimeout(function () {
      if (sliderAnimations && sliderAnimations.has(slider)) {
        return;
      }
      var metrics = getSliderMetrics(slider);
      if (metrics && sliderTargetIndex) {
        sliderTargetIndex.set(slider, indexFromScrollLeft(slider, metrics.amount, metrics.maxIndex));
      }
      sliderScrollTimers.delete(slider);
    }, 150);

    sliderScrollTimers.set(slider, timer);
  }

  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  // Animatie proprie (requestAnimationFrame), in locul lui
  // Element.scrollTo({behavior:'smooth'}) nativ - scroll-ul smooth nativ,
  // re-tintit printr-un al doilea apel inainte sa se termine primul (exact
  // ce se intampla la click-uri rapide repetate), se comporta inconsistent
  // intre browsere, dand impresia ca sare putin inapoi la mijlocul miscarii
  // chiar daca pozitia finala calculata era corecta (reprodus live
  // 2026-07-31). Interpoland noi cadru cu cadru, plecam mereu din
  // scrollLeft-ul real curent, fara nicio stare interna de animatie a
  // browserului cu care sa ne certam.
  function animateScrollTo(slider, targetLeft, duration) {
    duration = typeof duration === 'number' ? duration : 380;

    if (sliderAnimations) {
      var existing = sliderAnimations.get(slider);
      if (existing) {
        window.cancelAnimationFrame(existing.rafId);
      }
    }

    var startLeft = slider.scrollLeft;
    var delta = targetLeft - startLeft;
    var startTime = null;

    if (Math.abs(delta) < 1) {
      slider.scrollLeft = targetLeft;
      if (sliderAnimations) { sliderAnimations.delete(slider); }
      return;
    }

    // CSS "scroll-snap-type: mandatory" de pe slider face ca browserul sa
    // refuze sa randeze pozitiile intermediare pe care le setam noi cadru cu
    // cadru (nu sunt puncte de "snap") - ramane vizual blocat pe pozitia
    // curenta pana la finalul animatiei noastre si sare direct la urmatorul
    // punct de snap, exact saritura raportata. Il dezactivam cat dureaza
    // animatia proprie si il restauram la final, ca drag-ul manual (touch)
    // sa ramana cu snap.
    slider.style.scrollSnapType = 'none';

    function step(timestamp) {
      if (startTime === null) { startTime = timestamp; }
      var elapsed = timestamp - startTime;
      var progress = duration > 0 ? Math.min(1, elapsed / duration) : 1;
      slider.scrollLeft = startLeft + delta * easeOutCubic(progress);

      if (progress < 1) {
        var rafId = window.requestAnimationFrame(step);
        if (sliderAnimations) { sliderAnimations.set(slider, { rafId: rafId, targetLeft: targetLeft }); }
      } else {
        slider.style.scrollSnapType = '';
        if (sliderAnimations) {
          sliderAnimations.delete(slider);
        }
      }
    }

    var initialRafId = window.requestAnimationFrame(step);
    if (sliderAnimations) { sliderAnimations.set(slider, { rafId: initialRafId, targetLeft: targetLeft }); }
  }

  // Indexul "tintit" de noi la ultimul click, actualizat sincron, indiferent
  // daca animatia anterioara s-a terminat vizual sau nu.
  function scrollHorizontalSlider(slider, direction) {
    if (!slider) {
      return;
    }

    var metrics = getSliderMetrics(slider);
    if (!metrics) {
      return;
    }

    var amount = metrics.amount;
    var maxScroll = metrics.maxScroll;
    var maxIndex = metrics.maxIndex;
    var currentIndex = getTrackedSliderIndex(slider, amount, maxIndex);
    var targetIndex = currentIndex + direction;

    // Wrap-around: la capat, click-ul suplimentar continua de la celalalt
    // capat in loc sa ramana blocat. Teleportul instant la 0/maxScroll e
    // sigur acum - scroll-snap-type e deja dezactivat de orice animatie
    // anterioara inca in desfasurare (vezi animateScrollTo()).
    if (targetIndex > maxIndex) {
      if (sliderTargetIndex) { sliderTargetIndex.set(slider, 0); }
      slider.scrollLeft = 0;
      animateScrollTo(slider, Math.min(amount, maxScroll));
      return;
    }

    if (targetIndex < 0) {
      if (sliderTargetIndex) { sliderTargetIndex.set(slider, maxIndex); }
      slider.scrollLeft = maxScroll;
      animateScrollTo(slider, Math.max(maxScroll - amount, 0));
      return;
    }

    if (sliderTargetIndex) { sliderTargetIndex.set(slider, targetIndex); }
    var target = targetIndex >= maxIndex ? maxScroll : targetIndex * amount;
    animateScrollTo(slider, target);
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

    slider.addEventListener('scroll', function () {
      scheduleManualSliderResync(slider);
    }, { passive: true });
  }

  function initHorizontalSliders() {
    Array.prototype.slice.call(document.querySelectorAll('.pap-featured-slider-shell')).forEach(function (shell) {
      if (initializedSliderShells && initializedSliderShells.has(shell)) {
        return;
      }

      initHorizontalSliderShell(shell);

      if (initializedSliderShells) {
        initializedSliderShells.add(shell);
      }
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
      handleCheckoutClick(event);
      handleStockTooltipClick(event);
      handleCouponAccordionToggle(event);
    });

    document.addEventListener('input', handleQuantityInput);
    document.addEventListener('input', handleCouponInput);
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
