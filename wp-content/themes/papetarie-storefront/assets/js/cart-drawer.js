(function () {
  function initCartDrawer() {
    if (!window.papCartDrawer) {
      return;
    }

    var modalManager = window.papModalManager || null;
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
    var deleteModal = document.querySelector('[data-cart-delete-modal]');
    var deleteModalName = deleteModal ? deleteModal.querySelector('[data-cart-delete-modal-name]') : null;
    var deleteModalCancel = deleteModal ? deleteModal.querySelector('[data-cart-delete-modal-cancel]') : null;
    var deleteModalConfirm = deleteModal ? deleteModal.querySelector('[data-cart-delete-modal-confirm]') : null;
    var deleteModalClosers = deleteModal ? Array.prototype.slice.call(deleteModal.querySelectorAll('[data-cart-delete-modal-close]')) : [];
    var lastFocus = null;
    var closeTimer = null;
    var syncController = null;
    var pendingDeleteItem = null;
    var deleteRequestInFlight = false;
    var perfEnabled = !!window.__papPerfDebug;

    function setBodyLocked(isLocked) {
      document.body.classList.toggle('pap-cart-drawer-open', isLocked);
      document.body.classList.toggle('pap-cart-drawer-scroll-locked', isLocked);
      document.documentElement.classList.toggle('pap-cart-drawer-scroll-locked', isLocked);
    }

    function setTriggerState(isOpen) {
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function positionDrawer() {
      drawer.style.setProperty('--pap-cart-drawer-top', '0px');
      drawer.style.setProperty('--pap-cart-drawer-right', '0px');
      drawer.style.setProperty('--pap-cart-drawer-width', window.matchMedia('(max-width: 768px)').matches ? '100vw' : '388px');
    }

    function formatCountLabel(count) {
      var safeCount = Math.max(0, parseInt(count, 10) || 0);
      return safeCount === 1 ? '1 produs' : safeCount + ' produse';
    }

    function updateCountTargets(label) {
      Array.prototype.slice.call(document.querySelectorAll('[data-pap-cart-count]')).forEach(function (target) {
        target.textContent = label;
      });
    }

    function updateSummary(data) {
      if (typeof data.count_label === 'string') {
        updateCountTargets(data.count_label);
      } else if (typeof data.count !== 'undefined') {
        updateCountTargets(formatCountLabel(data.count));
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

    function refreshEmptyState() {
      var isEmpty = !!(content && content.querySelector('.pap-cart-drawer-empty'));
      drawer.classList.toggle('is-empty', isEmpty);
    }

    function applyCartDrawerPayload(data) {
      if (!data) {
        return;
      }

      if (content && typeof data.items_html === 'string') {
        content.innerHTML = data.items_html;
      }

      updateSummary(data);
      refreshEmptyState();
      window.dispatchEvent(new CustomEvent('pap:cart-drawer-updated', { detail: data }));
    }

    window.papApplyCartDrawerPayload = applyCartDrawerPayload;

    function closeQuickCart() {
      closeDrawer();
    }

    window.closeQuickCart = closeQuickCart;

    function syncCart(mode, payload) {
      if (syncController) {
        syncController.abort();
      }

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

          var data = response.data || {};
          applyCartDrawerPayload(data);
          return data;
        })
        .catch(function (error) {
          if (error && error.name === 'AbortError') {
            return;
          }

          if (window.console && typeof window.console.error === 'function') {
            window.console.error(error);
          }
        })
        .finally(function () {
          if (syncController === currentController) {
            syncController = null;
          }
        });
    }

    function openDrawer(options) {
      var focusTarget = options && options.focusTarget ? options.focusTarget : document.activeElement;
      lastFocus = focusTarget;
      positionDrawer();
      clearTimeout(closeTimer);
      drawer.hidden = false;
      drawer.setAttribute('aria-hidden', 'false');
      setBodyLocked(true);
      setTriggerState(true);
      if (modalManager) {
        modalManager.open(drawer, closeDrawer, { focusTarget: lastFocus });
      }

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
      if (modalManager) {
        modalManager.close(drawer);
      }

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

    function getCartItemName(row) {
      if (!row) {
        return '';
      }

      var nameNode = row.querySelector('.pap-cart-drawer-name');
      return nameNode ? nameNode.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function openDeleteModal(item) {
      if (!deleteModal) {
        return;
      }

      pendingDeleteItem = item || null;
      lastFocus = document.activeElement;

      if (deleteModalName) {
        deleteModalName.textContent = item && item.name ? item.name : 'acest produs';
      }

      deleteModal.hidden = false;
      deleteModal.setAttribute('aria-hidden', 'false');
      if (modalManager) {
        modalManager.open(deleteModal, closeDeleteModal, { focusTarget: lastFocus });
      }

      window.requestAnimationFrame(function () {
        deleteModal.classList.add('is-open');
        if (deleteModalCancel && typeof deleteModalCancel.focus === 'function') {
          deleteModalCancel.focus({ preventScroll: true });
        }
      });
    }

    function closeDeleteModal(options) {
      var restoreFocus = !options || options.restoreFocus !== false;
      var forceClose = !!(options && options.force);

      if (!deleteModal || deleteModal.hidden) {
        return;
      }

      if (deleteRequestInFlight && !forceClose) {
        return;
      }

      deleteModal.classList.remove('is-open');
      deleteModal.setAttribute('aria-hidden', 'true');
      if (modalManager) {
        modalManager.close(deleteModal);
      }
      deleteModal.hidden = true;

      pendingDeleteItem = null;

      if (restoreFocus && lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus({ preventScroll: true });
      }
    }

    function confirmDeleteItem() {
      if (!pendingDeleteItem || !pendingDeleteItem.key) {
        closeDeleteModal();
        return;
      }

      if (deleteModalConfirm && deleteModalConfirm.disabled) {
        return;
      }

      var item = pendingDeleteItem;
      if (deleteModalConfirm) {
        deleteModalConfirm.disabled = true;
      }
      deleteRequestInFlight = true;

      perfTime('remove-from-cart-total');
      perfTime('ajax-remove-cart');

      Promise.resolve(syncCart('remove', { cart_item_key: item.key }))
        .then(function (data) {
          if (!data) {
            return;
          }

          perfLog('remove-from-cart server payload', data);
          perfTimeEnd('ajax-remove-cart');
          perfTimeEnd('remove-from-cart-total');
          closeDeleteModal({ restoreFocus: false, force: true });

          window.requestAnimationFrame(function () {
            openDrawer({ focusTarget: trigger });
          });
        })
        .finally(function () {
          deleteRequestInFlight = false;
          if (deleteModalConfirm) {
            deleteModalConfirm.disabled = false;
          }
        });
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

    if (deleteModal) {
      deleteModalClosers.forEach(function (button) {
        button.addEventListener('click', function () {
          closeDeleteModal();
        });
      });

      if (deleteModalConfirm) {
        deleteModalConfirm.addEventListener('click', function () {
          confirmDeleteItem();
        });
      }
    }

    if (backdrop) {
      backdrop.addEventListener('click', closeDrawer);
    }

    refreshEmptyState();

    drawer.addEventListener('click', function (event) {
      var emptyContinueButton = event.target.closest('[data-cart-drawer-empty-continue]');
      if (emptyContinueButton) {
        event.preventDefault();
        event.stopPropagation();
        closeQuickCart();
        return;
      }

      var removeButton = event.target.closest('[data-cart-remove-item]');
      if (removeButton) {
        event.preventDefault();
        event.stopPropagation();
        var removeKey = removeButton.getAttribute('data-cart-item-key');
        var row = getCartItemRow(removeButton);
        var removeName = removeButton.getAttribute('data-cart-item-name') || getCartItemName(row);
        if (removeKey) {
          closeQuickCart();
          window.requestAnimationFrame(function () {
            openDeleteModal({
              key: removeKey,
              name: removeName
            });
          });
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
