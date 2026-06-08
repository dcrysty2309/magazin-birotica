(function () {
  function initCartDrawer() {
    if (!window.papCartDrawer) {
      return;
    }

    var drawer = document.querySelector('[data-cart-drawer]');
    var trigger = document.querySelector('[data-cart-drawer-trigger]');

    if (!drawer || !trigger) {
      return;
    }

    var backdrop = drawer.querySelector('.pap-cart-drawer-backdrop');
    var closeButtons = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-close]'));
    var panel = drawer.querySelector('.pap-cart-drawer-panel');
    var content = drawer.querySelector('[data-cart-drawer-content]');
    var subtotalTargets = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-subtotal]'));
    var totalTargets = Array.prototype.slice.call(drawer.querySelectorAll('[data-cart-drawer-total]'));
    var lastFocus = null;
    var syncInFlight = null;
    var syncController = null;
    var scrollLockActive = false;
    var closeTimer = null;

    function setBodyLocked(isLocked) {
      document.body.classList.toggle('pap-cart-drawer-open', isLocked);
      scrollLockActive = isLocked;
      document.body.classList.toggle('pap-cart-drawer-scroll-locked', scrollLockActive);
      document.documentElement.classList.toggle('pap-cart-drawer-scroll-locked', scrollLockActive);
    }

    function setTriggerState(isOpen) {
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function setDrawerState(isOpen) {
      clearTimeout(closeTimer);
      drawer.hidden = false;
      drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      drawer.classList.toggle('is-open', isOpen);
      setBodyLocked(isOpen);
      setTriggerState(isOpen);

      if (!isOpen) {
        closeTimer = window.setTimeout(function () {
          drawer.hidden = true;
        }, 280);
      }
    }

    function isMobileLayout() {
      return window.matchMedia('(max-width: 768px)').matches;
    }

    function isScrollableElement(element) {
      if (!element) {
        return false;
      }

      return element.scrollHeight > element.clientHeight + 1;
    }

    function canElementScroll(element, deltaY) {
      if (!isScrollableElement(element)) {
        return false;
      }

      if (deltaY < 0 && element.scrollTop <= 0) {
        return false;
      }

      if (deltaY > 0 && element.scrollTop + element.clientHeight >= element.scrollHeight - 1) {
        return false;
      }

      return true;
    }

    function getScrollableDrawerElement(target) {
      if (!target || !panel) {
        return null;
      }

      var scroller = target.closest('[data-cart-drawer-content]');
      if (scroller && panel.contains(scroller)) {
        return scroller;
      }

      return target.closest('.pap-cart-drawer-panel');
    }

    function handleScrollLockWheel(event) {
      if (!scrollLockActive || drawer.hidden) {
        return;
      }

      var scroller = getScrollableDrawerElement(event.target);
      var deltaY = event.deltaY || 0;

      if (scroller && canElementScroll(scroller, deltaY)) {
        return;
      }

      event.preventDefault();
    }

    function handleScrollLockTouch(event) {
      if (!scrollLockActive || drawer.hidden) {
        return;
      }

      if (event.target.closest('.pap-cart-drawer-panel')) {
        return;
      }

      event.preventDefault();
    }

    function handleScrollLockKeydown(event) {
      if (!scrollLockActive || drawer.hidden) {
        return;
      }

      var keys = ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '];
      if (keys.indexOf(event.key) !== -1 && !event.target.closest('.pap-cart-drawer-panel')) {
        event.preventDefault();
      }
    }

    function positionDrawer() {
      drawer.style.setProperty('--pap-cart-drawer-top', '0px');
      drawer.style.setProperty('--pap-cart-drawer-right', '0px');
      drawer.style.setProperty('--pap-cart-drawer-width', isMobileLayout() ? '100vw' : '388px');
    }

    function setBusy(isBusy) {
      drawer.classList.toggle('is-loading', isBusy);
      if (isBusy && window.papSetActionBusy) {
        window.papSetActionBusy('Coșul se actualizează...');
      }

      if (!isBusy && window.papClearActionBusy) {
        window.papClearActionBusy();
      }
    }

    function getCountTargets() {
      return Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]'));
    }

    function formatCountLabel(count) {
      var safeCount = Math.max(0, parseInt(count, 10) || 0);
      return safeCount === 1 ? '1 produs' : safeCount + ' produse';
    }

    function updateSummary(data) {
      var countLabel = null;
      if (typeof data.count_label === 'string') {
        countLabel = data.count_label;
      } else if (typeof data.count === 'number') {
        countLabel = formatCountLabel(data.count);
      }

      if (countLabel !== null) {
        getCountTargets().forEach(function (target) {
          target.textContent = countLabel;
        });
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

      return fetch(papCartDrawer.ajaxUrl, {
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
          if (syncController === currentController) {
            syncController = null;
          }
        });
    }

    window.papRefreshCartDrawer = function (mode, payload) {
      return syncCart(mode || 'refresh', payload);
    };

    function openDrawer() {
      lastFocus = document.activeElement;
      positionDrawer();
      clearTimeout(closeTimer);
      drawer.hidden = false;
      drawer.setAttribute('aria-hidden', 'false');
      setBodyLocked(true);
      setTriggerState(true);

      window.requestAnimationFrame(function () {
        drawer.classList.add('is-open');
      });
    }

    function closeDrawer() {
      if (!drawer.classList.contains('is-open')) {
        return;
      }

      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      setBodyLocked(false);
      setTriggerState(false);

      clearTimeout(closeTimer);
      closeTimer = window.setTimeout(function () {
        drawer.hidden = true;
      }, 280);

      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus({ preventScroll: true });
      }
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

    var handleTrigger = function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
      }
      if (drawer.classList.contains('is-open')) {
        closeDrawer();
        return;
      }

      openDrawer();
    };

    trigger.addEventListener('click', handleTrigger);

    closeButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        closeDrawer();
      });
    });

    if (backdrop) {
      backdrop.addEventListener('click', closeDrawer);
    }

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

    document.addEventListener('wheel', handleScrollLockWheel, { passive: false, capture: true });
    document.addEventListener('touchmove', handleScrollLockTouch, { passive: false, capture: true });
    document.addEventListener('keydown', handleScrollLockKeydown, { capture: true });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !drawer.hidden) {
        closeDrawer();
      }
    });

    window.addEventListener('resize', function () {
      if (drawer.classList.contains('is-open')) {
        positionDrawer();
      }
    });

    window.addEventListener('scroll', function () {
      if (drawer.classList.contains('is-open')) {
        positionDrawer();
      }
    }, { passive: true });

    drawer.setAttribute('aria-hidden', 'true');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCartDrawer);
  } else {
    initCartDrawer();
  }
})();
