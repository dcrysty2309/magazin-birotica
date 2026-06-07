(function () {
  if (!window.papCartDrawer) {
    return;
  }

  var drawer = document.querySelector('[data-cart-drawer]');
  var trigger = document.querySelector('[data-cart-drawer-trigger]');

  if (!drawer || !trigger) {
    return;
  }

  var backdrop = drawer.querySelector('[data-cart-drawer-close]');
  var closeButtons = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-close]'));
  var panel = drawer.querySelector('.pap-cart-drawer-panel');
  var content = drawer.querySelector('[data-cart-drawer-content]');
  var subtotalTargets = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-subtotal]'));
  var totalTargets = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-total]'));
  var countTarget = document.querySelector('[data-pap-cart-count]');
  var lastFocus = null;
  var syncInFlight = null;
  var syncController = null;
  var hoverCloseTimer = null;

  function setBodyLocked(isLocked) {
    document.body.classList.toggle('pap-cart-drawer-open', isLocked && isMobileLayout());
  }

  function setTriggerState(isOpen) {
    trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  function setDrawerState(isOpen) {
    drawer.hidden = !isOpen;
    drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    setBodyLocked(isOpen);
    setTriggerState(isOpen);
  }

  function isMobileLayout() {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function positionDrawer() {
    if (isMobileLayout()) {
      drawer.style.removeProperty('--pap-cart-drawer-top');
      drawer.style.removeProperty('--pap-cart-drawer-right');
      drawer.style.removeProperty('--pap-cart-drawer-width');
      return;
    }

    var triggerRect = trigger.getBoundingClientRect();
    var panelWidth = Math.min(292, window.innerWidth - 24);
    var rightOffset = Math.max(12, window.innerWidth - triggerRect.right);
    var topOffset = Math.max(12, triggerRect.bottom + 10);

    drawer.style.setProperty('--pap-cart-drawer-top', topOffset + 'px');
    drawer.style.setProperty('--pap-cart-drawer-right', rightOffset + 'px');
    drawer.style.setProperty('--pap-cart-drawer-width', panelWidth + 'px');
  }

  function setBusy(isBusy) {
    drawer.classList.toggle('is-loading', isBusy);
  }

  function updateSummary(data) {
    if (countTarget && typeof data.count_label === 'string') {
      countTarget.textContent = data.count_label;
    }

    if (typeof data.subtotal_html === 'string') {
      subtotalTargets.forEach(function (target) {
        target.innerHTML = data.subtotal_html;
      });
    }

    if (typeof data.total_html === 'string') {
      totalTargets.forEach(function (target) {
        target.innerHTML = data.total_html;
      });
    }
  }

  function updateContent(data) {
    if (content && typeof data.items_html === 'string') {
      content.innerHTML = data.items_html;
    }
    updateSummary(data || {});
  }

  function syncCart(mode, payload) {
    if (syncController) {
      syncController.abort();
    }

    setBusy(true);
    var currentController = new AbortController();
    syncController = currentController;

    var params = new URLSearchParams();
    params.append('action', 'pap_cart_drawer_sync');
    params.append('nonce', papCartDrawer.nonce);
    params.append('mode', mode || 'refresh');

    if (payload && payload.cart_item_key) {
      params.append('cart_item_key', payload.cart_item_key);
    }

    if (payload && typeof payload.quantity !== 'undefined' && payload.quantity !== null) {
      params.append('quantity', String(payload.quantity));
    }

    syncInFlight = fetch(papCartDrawer.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString(),
      signal: currentController.signal
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error(response && response.data && response.data.message ? response.data.message : papCartDrawer.texts.refreshError);
        }

        updateContent(response.data || {});
        window.dispatchEvent(new CustomEvent('pap:cart-drawer-updated', { detail: response.data || {} }));
        return response.data || {};
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        if (content) {
          content.innerHTML = '<div class="pap-cart-drawer-empty pap-cart-drawer-empty--error"><p>' + (error && error.message ? error.message : papCartDrawer.texts.refreshError) + '</p></div>';
        }
      })
      .finally(function () {
        setBusy(false);
        syncInFlight = null;
        if (syncController === currentController) {
          syncController = null;
        }
      });

    return syncInFlight;
  }

  function openDrawer() {
    if (!drawer.hidden) {
      positionDrawer();
      return;
    }

    clearTimeout(hoverCloseTimer);
    lastFocus = document.activeElement;
    positionDrawer();
    setDrawerState(true);

    window.requestAnimationFrame(function () {
      var closeButton = drawer.querySelector('[data-cart-drawer-close]');
      if (closeButton && typeof closeButton.focus === 'function') {
        closeButton.focus({ preventScroll: true });
      }
    });

    syncCart('refresh');
  }

  function closeDrawer() {
    if (drawer.hidden) {
      return;
    }

    setDrawerState(false);

    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus({ preventScroll: true });
    }
  }

  function toggleDrawer() {
    if (drawer.hidden) {
      openDrawer();
      return;
    }

    closeDrawer();
  }

  function getCartItemRow(node) {
    return node ? node.closest('[data-cart-item-key]') : null;
  }

  function changeQuantity(row, quantity) {
    if (!row) {
      return;
    }

    var key = row.getAttribute('data-cart-item-key');
    if (!key) {
      return;
    }

    syncCart('update', {
      cart_item_key: key,
      quantity: quantity
    });
  }

  trigger.addEventListener('mouseenter', openDrawer);
  if (panel) {
    panel.addEventListener('mouseenter', function () {
      clearTimeout(hoverCloseTimer);
    });
    panel.addEventListener('mouseleave', function () {
      clearTimeout(hoverCloseTimer);
      hoverCloseTimer = window.setTimeout(function () {
        if (!panel.matches(':hover') && !trigger.matches(':hover')) {
          closeDrawer();
        }
      }, 180);
    });
  }
  trigger.addEventListener('focusin', openDrawer);
  trigger.addEventListener('mouseleave', function () {
    clearTimeout(hoverCloseTimer);
    hoverCloseTimer = window.setTimeout(function () {
      if ((panel && !panel.matches(':hover')) && !trigger.matches(':hover')) {
        closeDrawer();
      }
    }, 180);
  });

  closeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      closeDrawer();
    });
  });

  drawer.addEventListener('click', function (event) {
    var removeButton = event.target.closest('[data-cart-remove-item]');
    if (removeButton) {
      event.preventDefault();
      var removeKey = removeButton.getAttribute('data-cart-item-key');
      if (removeKey) {
        syncCart('remove', { cart_item_key: removeKey });
      }
      return;
    }

    var stepButton = event.target.closest('[data-cart-qty-step]');
    if (stepButton) {
      event.preventDefault();
      var row = getCartItemRow(stepButton);
      var input = row ? row.querySelector('[data-cart-qty-input]') : null;
      if (!input) {
        return;
      }

      var currentValue = parseInt(input.value, 10);
      if (Number.isNaN(currentValue) || currentValue < 1) {
        currentValue = 1;
      }

      var step = parseInt(stepButton.getAttribute('data-cart-qty-step'), 10) || 0;
      var nextValue = currentValue + step;
      if (nextValue < 1) {
        nextValue = 1;
      }

      input.value = String(nextValue);
      changeQuantity(row, nextValue);
      return;
    }
  });

  drawer.addEventListener('change', function (event) {
    var input = event.target.closest('[data-cart-qty-input]');
    if (!input) {
      return;
    }

    var row = getCartItemRow(input);
    var value = parseInt(input.value, 10);

    if (Number.isNaN(value) || value < 1) {
      value = 1;
      input.value = '1';
    }

    changeQuantity(row, value);
  });

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      closeDrawer();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !drawer.hidden) {
      closeDrawer();
    }
  });

  window.addEventListener('pap:cart-updated', function () {
    syncCart('refresh');
  });

  window.addEventListener('resize', function () {
    if (!drawer.hidden) {
      positionDrawer();
    }
  });

  window.addEventListener('scroll', function () {
    if (!drawer.hidden) {
      positionDrawer();
    }
  }, { passive: true });

  if (window.jQuery) {
    window.jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div', function () {
      syncCart('refresh');
    });
  }

  drawer.setAttribute('aria-hidden', 'true');
})();
