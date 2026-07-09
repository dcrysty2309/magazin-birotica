(function () {
  var activeAuthRequest = null;

  if (window.papAccountUi && window.papAccountUi.authState) {
    window.papAuthState = window.papAccountUi.authState;
  }

  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList || []);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getAuthRoots() {
    return toArray(document.querySelectorAll('[data-auth-root]'));
  }

  function isModalRoot(root) {
    return !!(root && root.closest('[data-auth-modal]'));
  }

  function findField(form, selectors) {
    var field = null;

    toArray(selectors).some(function (selector) {
      field = form.querySelector(selector);
      return !!field;
    });

    return field;
  }

  function getAuthSubmitButtons(form) {
    return toArray(
      form.querySelectorAll('.pap-auth-form-actions .button, .pap-auth-form-actions .woocommerce-button')
    );
  }

  function setAuthSubmitButtonsDisabled(form, disabled) {
    getAuthSubmitButtons(form).forEach(function (button) {
      button.disabled = disabled;
      button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      button.classList.toggle('is-loading', disabled);
      button.setAttribute('aria-busy', disabled ? 'true' : 'false');
    });
  }

  function clearInlineValidation(root) {
    toArray(root.querySelectorAll('.pap-is-invalid')).forEach(function (field) {
      field.classList.remove('pap-is-invalid');
      if (field.matches('input, select, textarea')) {
        field.removeAttribute('aria-invalid');
      }
    });

    toArray(root.querySelectorAll('.pap-field-error')).forEach(function (errorNode) {
      errorNode.remove();
    });
  }

  function clearAuthNotices(root) {
    toArray(root.querySelectorAll('.pap-auth-notices')).forEach(function (noticeWrap) {
      noticeWrap.innerHTML = '';
    });
  }

  function setInlineValidation(form, selectors, message) {
    var field = findField(form, selectors);

    if (!field) {
      return;
    }

    field.classList.add('pap-is-invalid');
    field.setAttribute('aria-invalid', 'true');

    var row = field.closest('.pap-form-row, .form-row, fieldset, .pap-auth-terms, label');
    if (!row) {
      return;
    }

    row.classList.add('pap-is-invalid');

    if (row.classList.contains('pap-auth-terms')) {
      return;
    }

    var errorNode = row.querySelector('.pap-field-error');
    if (!errorNode) {
      errorNode = document.createElement('small');
      errorNode.className = 'pap-field-error';
      row.appendChild(errorNode);
    }

    errorNode.textContent = message;
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
  }

  function validateAuthForm(form) {
    var formType = form.getAttribute('data-auth-form');
    var hasErrors = false;

    setAuthSubmitButtonsDisabled(form, false);
    clearInlineValidation(form);

    if (formType === 'login') {
      var username = findField(form, ['[name="username"]', '#username']);
      var password = findField(form, ['[name="password"]', '#password']);

      if (!username || !username.value.trim()) {
        setInlineValidation(form, ['[name="username"]', '#username'], 'Introdu emailul.');
        hasErrors = true;
      }

      if (username && username.value.trim() && !isValidEmail(username.value)) {
        setInlineValidation(form, ['[name="username"]', '#username'], 'Introdu un email valid.');
        hasErrors = true;
      }

      if (!password || !password.value.trim()) {
        setInlineValidation(form, ['[name="password"]', '#password'], 'Introdu parola.');
        hasErrors = true;
      }
    }

    if (formType === 'register') {
      var regFirstName = findField(form, ['[name="first_name"]', '#reg_first_name']);
      var regLastName = findField(form, ['[name="last_name"]', '#reg_last_name']);
      var regEmail = findField(form, ['[name="email"]', '#reg_email']);
      var regPassword = findField(form, ['[name="password"]', '#reg_password']);
      var regPasswordConfirm = findField(form, ['[name="password_confirm"]', '#reg_password_confirm']);
      var regTerms = findField(form, ['[name="agree_terms"]']);

      if (!regFirstName || !regFirstName.value.trim()) {
        setInlineValidation(form, ['[name="first_name"]', '#reg_first_name'], 'Completează prenumele.');
        hasErrors = true;
      }

      if (!regLastName || !regLastName.value.trim()) {
        setInlineValidation(form, ['[name="last_name"]', '#reg_last_name'], 'Completează numele.');
        hasErrors = true;
      }

      if (!regEmail || !regEmail.value.trim()) {
        setInlineValidation(form, ['[name="email"]', '#reg_email'], 'Introdu emailul.');
        hasErrors = true;
      }

      if (regEmail && regEmail.value.trim() && !isValidEmail(regEmail.value)) {
        setInlineValidation(form, ['[name="email"]', '#reg_email'], 'Introdu un email valid.');
        hasErrors = true;
      }

      if (regPassword && !regPassword.value.trim()) {
        setInlineValidation(form, ['[name="password"]', '#reg_password'], 'Introdu parola.');
        hasErrors = true;
      }

      if (!regPasswordConfirm || !regPasswordConfirm.value.trim()) {
        setInlineValidation(form, ['[name="password_confirm"]', '#reg_password_confirm'], 'Confirmă parola.');
        hasErrors = true;
      }

      if (regPassword && regPasswordConfirm && regPassword.value.trim() && regPasswordConfirm.value.trim() && regPassword.value !== regPasswordConfirm.value) {
        setInlineValidation(form, ['[name="password"]', '#reg_password'], 'Parolele nu se potrivesc.');
        setInlineValidation(form, ['[name="password_confirm"]', '#reg_password_confirm'], 'Parolele nu se potrivesc.');
        hasErrors = true;
      }

      if (!regTerms || !regTerms.checked) {
        setInlineValidation(form, ['[name="agree_terms"]'], 'Trebuie să accepți politica de confidențialitate.');
        hasErrors = true;
      }
    }

    if (formType === 'lost-password') {
      var userLogin = findField(form, ['[name="user_login"]', '#user_login']);
      if (!userLogin || !userLogin.value.trim()) {
        setInlineValidation(form, ['[name="user_login"]', '#user_login'], 'Introdu emailul.');
        hasErrors = true;
      }

      if (userLogin && userLogin.value.trim() && !isValidEmail(userLogin.value)) {
        setInlineValidation(form, ['[name="user_login"]', '#user_login'], 'Introdu un email valid.');
        hasErrors = true;
      }
    }

    if (formType === 'reset-password') {
      var password1 = findField(form, ['[name="password_1"]', '#password_1']);
      var password2 = findField(form, ['[name="password_2"]', '#password_2']);

      if (!password1 || !password1.value.trim()) {
        setInlineValidation(form, ['[name="password_1"]', '#password_1'], 'Introdu parola nouă.');
        hasErrors = true;
      }

      if (!password2 || !password2.value.trim()) {
        setInlineValidation(form, ['[name="password_2"]', '#password_2'], 'Confirmă parola nouă.');
        hasErrors = true;
      }

      if (password1 && password2 && password1.value.trim() && password2.value.trim() && password1.value !== password2.value) {
        setInlineValidation(form, ['[name="password_1"]', '#password_1'], 'Parolele nu se potrivesc.');
        setInlineValidation(form, ['[name="password_2"]', '#password_2'], 'Parolele nu se potrivesc.');
        hasErrors = true;
      }
    }

    if (hasErrors) {
      var firstInvalid = form.querySelector('.pap-is-invalid');
      if (firstInvalid && typeof firstInvalid.focus === 'function') {
        firstInvalid.focus({ preventScroll: false });
      }
      setAuthSubmitButtonsDisabled(form, false);
      return false;
    }

    return true;
  }

  function setTab(root, tabName) {
    var tabs = toArray(root.querySelectorAll('[data-auth-tab]'));
    var panels = toArray(root.querySelectorAll('[data-auth-panel]'));

    if (!panels.length) {
      return;
    }

    if (tabs.length) {
      tabs.forEach(function (tab) {
        var isActive = tab.getAttribute('data-auth-tab') === tabName;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    }

    panels.forEach(function (panel) {
      var isActive = panel.getAttribute('data-auth-panel') === tabName;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
    });
  }

  function togglePassword(button) {
    var field = button.closest('[data-password-field]');
    var input = field ? field.querySelector('input[type="password"], input[type="text"]') : null;

    if (!input) {
      return;
    }

    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-label', isHidden ? 'Ascunde parola' : 'Arată parola');
    button.classList.toggle('is-visible', isHidden);
  }

  function syncPasswordToggleVisibility(field) {
    if (!field) {
      return;
    }

    var input = field.querySelector('input[type="password"], input[type="text"]');
    var button = field.querySelector('[data-password-toggle]');
    if (!input || !button) {
      return;
    }

    var isActive = !!(input.value && input.value.trim());
    field.classList.toggle('is-password-active', isActive);
    button.hidden = !isActive && !button.classList.contains('is-visible');
    button.setAttribute('aria-hidden', button.hidden ? 'true' : 'false');
  }

  function initPasswordFields(root) {
    toArray(root.querySelectorAll('[data-password-field]')).forEach(function (field) {
      if (field.getAttribute('data-password-field-initialized') === '1') {
        syncPasswordToggleVisibility(field);
        return;
      }

      field.setAttribute('data-password-field-initialized', '1');
      syncPasswordToggleVisibility(field);

      var input = field.querySelector('input[type="password"], input[type="text"]');
      var button = field.querySelector('[data-password-toggle]');
      if (!input || !button) {
        return;
      }

      input.addEventListener('input', function () {
        syncPasswordToggleVisibility(field);
      });

      input.addEventListener('change', function () {
        syncPasswordToggleVisibility(field);
      });

      button.addEventListener('click', function () {
        window.setTimeout(function () {
          syncPasswordToggleVisibility(field);
        }, 0);
      });
    });
  }

  function getAuthModal() {
    if (!window.papAccountUi || !window.papAccountUi.modalSelector) {
      return null;
    }

    return document.querySelector(window.papAccountUi.modalSelector);
  }

  function getAuthViewRoots(modal) {
    return modal ? toArray(modal.querySelectorAll('[data-auth-view]')) : [];
  }

  function showAuthView(modal, viewName, focusTarget) {
    if (!modal) {
      return false;
    }

    var targetView = String(viewName || 'login');
    var activeRoot = null;

    getAuthViewRoots(modal).forEach(function (root) {
      var isActive = root.getAttribute('data-auth-view') === targetView;
      root.hidden = !isActive;

      if (isActive) {
        activeRoot = root;
        initAuthRoot(root);
        clearInlineValidation(root);

        if (targetView !== 'lost-password-confirmation') {
          clearAuthNotices(root);
        }

        if (targetView === 'login') {
          var loginTab = root.querySelector('[data-auth-tab="login"]');
          if (loginTab) {
            setTab(root, 'login');
          }
        }
      }
    });

    modal.dataset.authView = targetView;

    if (targetView === 'login' && modal.dataset.authPanel && modal.dataset.authPanel !== 'login') {
      return showAuthPanel(modal, 'login', focusTarget);
    }

    if (window.requestAnimationFrame) {
      window.requestAnimationFrame(function () {
        var fieldSelector = targetView === 'lost-password' ? '[name="user_login"]' : '[name="username"]';
        var firstField = activeRoot ? activeRoot.querySelector(fieldSelector) : null;
        var target = firstField || (
          targetView === 'lost-password-confirmation' || targetView === 'register-confirmation'
            ? (activeRoot ? activeRoot.querySelector('.pap-auth-back-link') : null)
            : focusTarget
        );

        if (target && typeof target.focus === 'function') {
          target.focus({ preventScroll: true });
        }
      });
    }

    return true;
  }

  function showAuthPanel(modal, panelName, focusTarget) {
    if (!modal) {
      return false;
    }

    var root = null;

    if (modal.matches && modal.matches('[data-auth-view="login"]')) {
      root = modal;
    } else {
      root = modal.querySelector('[data-auth-view="login"]');
    }

    if (!root) {
      return false;
    }

    initAuthRoot(root);
    clearInlineValidation(root);
    clearAuthNotices(root);
    setTab(root, panelName || 'login');
    modal.dataset.authPanel = panelName || 'login';

    window.requestAnimationFrame(function () {
      var selector = '[name="username"]';
      if (panelName === 'register') {
        selector = '[name="first_name"], [name="email"]';
      } else if (panelName === 'register-confirmation') {
        selector = '[data-auth-switch="login"]';
      }
      var firstField = root.querySelector(selector);
      var target = firstField || focusTarget;

      if (target && typeof target.focus === 'function') {
        target.focus({ preventScroll: true });
      }
    });

    return true;
  }

  function closeAuthModal(modal, focusTarget) {
    if (!modal) {
      return;
    }

    if (!focusTarget || (focusTarget && !document.contains(focusTarget))) {
      focusTarget = document.querySelector(window.papAccountUi && window.papAccountUi.accountSelector ? window.papAccountUi.accountSelector : '[data-pap-auth-account]');
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (window.papModalManager) {
      window.papModalManager.close(modal);
    }

    window.setTimeout(function () {
      modal.hidden = true;
      modal.__papAuthTrigger = null;
    }, 180);

    if (focusTarget && typeof focusTarget.focus === 'function') {
      window.setTimeout(function () {
        focusTarget.focus({ preventScroll: true });
      }, 200);
    }
  }

  function openAuthModal(trigger, initialView) {
    var modal = getAuthModal();
    if (!modal) {
      return false;
    }

    modal.__papAuthTrigger = trigger || null;

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');

    if (window.papModalManager) {
      window.papModalManager.open(modal, function (info) {
        closeAuthModal(modal, info && info.focusTarget ? info.focusTarget : trigger);
      }, {
        focusTarget: trigger
      });
    }

    if (initialView === 'register') {
      showAuthView(modal, 'login', trigger);
      showAuthPanel(modal, 'register', trigger);
    } else {
      showAuthView(modal, initialView || 'login', trigger);
    }

    window.requestAnimationFrame(function () {
      modal.classList.add('is-open');
    });

    return false;
  }

  function replaceAccountTool(html) {
    if (!html) {
      return;
    }

    var accountTool = document.querySelector(window.papAccountUi.accountSelector || '[data-pap-auth-account]');
    if (accountTool) {
      accountTool.outerHTML = html;
    }
  }

  function flashAccountTool() {
    var accountTool = document.querySelector(window.papAccountUi.accountSelector || '[data-pap-auth-account]');
    if (!accountTool) {
      return;
    }

    accountTool.classList.remove('is-auth-flash');
    void accountTool.offsetWidth;
    accountTool.classList.add('is-auth-flash');

    window.setTimeout(function () {
      accountTool.classList.remove('is-auth-flash');
    }, 1800);
  }

  function setAuthSuccessNotice(root, message) {
    if (!root) {
      return;
    }

    clearAuthNotices(root);

    root.insertAdjacentHTML(
      'afterbegin',
      [
        '<div class="pap-auth-notices" role="status" aria-live="polite">',
        '<div class="pap-auth-notice wc-block-components-notice-banner pap-auth-notice--success" role="status">',
        '<span class="pap-auth-notice-icon" aria-hidden="true">',
        '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M9.6 16.2L5.8 12.4l-1.4 1.4 5.2 5.2L19.8 8.8l-1.4-1.4z" fill="currentColor"></path></svg>',
        '</span>',
        '<div class="pap-auth-notice-copy wc-block-components-notice-banner__content">', escapeHtml(message), '</div>',
        '</div>',
        '</div>'
      ].join('')
    );
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#096;');
  }

  function getAuthInitials(state) {
    var initials = '';
    if (state && typeof state.initials === 'string') {
      initials = state.initials.trim();
    }

    if (initials) {
      return initials;
    }

    var firstName = state && typeof state.first_name === 'string' ? state.first_name.trim() : '';
    if (firstName) {
      return firstName.slice(0, 1).toUpperCase();
    }

    var displayName = state && typeof state.display_name === 'string' ? state.display_name.trim() : '';
    if (displayName) {
      return displayName.split(/\s+/)[0].slice(0, 1).toUpperCase();
    }

    return 'C';
  }

  function buildLoggedInAccountToolHtml(state) {
    var accountUrl = (window.papAccountUi && window.papAccountUi.loginUrl) || '/my-account/';
    var initials = getAuthInitials(state);

    return [
      '<a class="pap-tool-card pap-tool-card-account" href="', escapeAttribute(accountUrl), '" data-pap-auth-account>',
      '<span class="pap-tool-avatar" aria-hidden="true">', escapeHtml(initials), '</span>',
      '<span class="pap-tool-copy">',
      '<strong>Bun venit</strong>',
      '<span>Contul meu</span>',
      '</span>',
      '</a>'
    ].join('');
  }

  function updateAuthState(data) {
    var nextState = data && data.auth_state ? data.auth_state : null;
    if (!nextState) {
      return;
    }

    window.papAuthState = nextState;
    if (window.papAccountUi) {
      window.papAccountUi.authState = nextState;
    }

    if (window.dispatchEvent) {
      window.dispatchEvent(new CustomEvent('pap:auth-state-changed', { detail: nextState }));
    }
  }

  function applyCurrentUserPayload(data) {
    if (!data) {
      return;
    }

    updateAuthState(data);
    replaceAccountTool(data.account_html || buildLoggedInAccountToolHtml(data && data.auth_state ? data.auth_state : null));
  }

  function fetchCurrentUserState() {
    if (!window.papAccountUi || !window.papAccountUi.ajaxUrl || !window.papAccountUi.currentUserAction) {
      return Promise.reject(new Error('Missing auth refresh configuration'));
    }

    var params = new URLSearchParams();
    params.set('action', window.papAccountUi.currentUserAction);

    return fetch(window.papAccountUi.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString()
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error('Unable to refresh auth state');
        }

        return response.data || {};
      });
  }

  function submitModalLogin(form) {
    if (!window.papAccountUi || !window.papAccountUi.ajaxUrl || !window.papAccountUi.ajaxAction || !window.papAccountUi.ajaxNonce) {
      return;
    }

    if (activeAuthRequest && typeof activeAuthRequest.abort === 'function') {
      activeAuthRequest.abort();
    }

    var controller = new AbortController();
    activeAuthRequest = controller;

    var modal = form.closest('[data-auth-modal]');
    var root = form.closest('[data-auth-root]') || form;
    var params = new URLSearchParams(new FormData(form));

    params.set('action', window.papAccountUi.ajaxAction);
    params.set('nonce', window.papAccountUi.ajaxNonce);

    setAuthSubmitButtonsDisabled(form, true);
    clearAuthNotices(root);

    fetch(window.papAccountUi.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString(),
      signal: controller.signal
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        var data = response && response.data ? response.data : {};
        var successMessage = data.message || 'Te-ai autentificat cu succes.';

        if (!response || !response.success) {
          if (data.notice_html) {
            var notices = root.querySelector('.pap-auth-notices');
            if (notices) {
              notices.outerHTML = data.notice_html;
            } else {
              root.insertAdjacentHTML('afterbegin', data.notice_html);
            }
          } else if (data.message) {
            clearAuthNotices(root);
            var fallbackNotice = root.querySelector('.pap-auth-notices');
            if (fallbackNotice) {
              fallbackNotice.outerHTML = '<div class="pap-auth-notices" role="status" aria-live="polite"><div class="pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" role="alert"><div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' + escapeHtml(data.message) + '</div></div></div>';
            }
          }

          setAuthSubmitButtonsDisabled(form, false);
          return;
        }

        var currentUserPayloadPromise = Promise.resolve(data);
        currentUserPayloadPromise = fetchCurrentUserState().catch(function () {
          return data;
        });

        currentUserPayloadPromise.then(function (currentUserData) {
          var payload = currentUserData || data;

          applyCurrentUserPayload(payload);
          replaceAccountTool(payload.account_html || buildLoggedInAccountToolHtml(payload && payload.auth_state ? payload.auth_state : null));
          if (payload.cart_drawer || payload.cart_page) {
            window.dispatchEvent(new CustomEvent('pap:cart-state-changed', {
              detail: payload.cart_drawer || payload.cart_page
            }));
          }
          flashAccountTool();
          setAuthSuccessNotice(root, successMessage);
          setAuthSubmitButtonsDisabled(form, false);

          if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('pap:auth-login-success', { detail: payload }));
          }

          window.setTimeout(function () {
            closeAuthModal(modal, modal && modal.__papAuthTrigger ? modal.__papAuthTrigger : null);
          }, 350);
        });
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        if (window.console && typeof window.console.error === 'function') {
          window.console.error(error);
        }

        setAuthSubmitButtonsDisabled(form, false);
      })
      .finally(function () {
        if (activeAuthRequest === controller) {
          activeAuthRequest = null;
        }
      });
  }

  function submitModalLostPassword(form) {
    if (!window.papAccountUi || !window.papAccountUi.ajaxUrl || !window.papAccountUi.lostPasswordAction || !window.papAccountUi.lostPasswordNonce) {
      return;
    }

    if (activeAuthRequest && typeof activeAuthRequest.abort === 'function') {
      activeAuthRequest.abort();
    }

    var controller = new AbortController();
    activeAuthRequest = controller;

    var modal = form.closest('[data-auth-modal]');
    var root = form.closest('[data-auth-root]') || form;
    var params = new URLSearchParams(new FormData(form));

    params.set('action', window.papAccountUi.lostPasswordAction);
    params.set('nonce', window.papAccountUi.lostPasswordNonce);
    params.set('redirect', window.location.href);

    setAuthSubmitButtonsDisabled(form, true);
    clearAuthNotices(root);

    fetch(window.papAccountUi.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString(),
      signal: controller.signal
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        var data = response && response.data ? response.data : {};

        if (!response || !response.success) {
          if (data.notice_html) {
            var notices = root.querySelector('.pap-auth-notices');
            if (notices) {
              notices.outerHTML = data.notice_html;
            } else {
              root.insertAdjacentHTML('afterbegin', data.notice_html);
            }
          } else if (data.message) {
            clearAuthNotices(root);
            var fallbackNotice = root.querySelector('.pap-auth-notices');
            if (fallbackNotice) {
              fallbackNotice.outerHTML = '<div class="pap-auth-notices" role="status" aria-live="polite"><div class="pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" role="alert"><div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' + escapeHtml(data.message) + '</div></div></div>';
            }
          }

          setAuthSubmitButtonsDisabled(form, false);
          return;
        }

        showAuthView(modal, 'lost-password-confirmation', modal);
        setAuthSubmitButtonsDisabled(form, false);
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        if (window.console && typeof window.console.error === 'function') {
          window.console.error(error);
        }

        setAuthSubmitButtonsDisabled(form, false);
      })
      .finally(function () {
        if (activeAuthRequest === controller) {
          activeAuthRequest = null;
        }
      });
  }

  function submitModalRegister(form) {
    if (!window.papAccountUi || !window.papAccountUi.ajaxUrl || !window.papAccountUi.registerAction || !window.papAccountUi.registerNonce) {
      return;
    }

    if (activeAuthRequest && typeof activeAuthRequest.abort === 'function') {
      activeAuthRequest.abort();
    }

    var controller = new AbortController();
    activeAuthRequest = controller;

    var modal = form.closest('[data-auth-modal]');
    var root = form.closest('[data-auth-root]') || form;
    var params = new URLSearchParams(new FormData(form));

    params.set('action', window.papAccountUi.registerAction);
    params.set('nonce', window.papAccountUi.registerNonce);

    setAuthSubmitButtonsDisabled(form, true);
    clearAuthNotices(root);

    fetch(window.papAccountUi.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString(),
      signal: controller.signal
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        var data = response && response.data ? response.data : {};

        if (!response || !response.success) {
          if (data.notice_html) {
            var notices = root.querySelector('.pap-auth-notices');
            if (notices) {
              notices.outerHTML = data.notice_html;
            } else {
              root.insertAdjacentHTML('afterbegin', data.notice_html);
            }
          } else if (data.message) {
            clearAuthNotices(root);
            var fallbackNotice = root.querySelector('.pap-auth-notices');
            if (fallbackNotice) {
              fallbackNotice.outerHTML = '<div class="pap-auth-notices" role="status" aria-live="polite"><div class="pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" role="alert"><div class="pap-auth-notice-copy wc-block-components-notice-banner__content">' + escapeHtml(data.message) + '</div></div></div>';
            }
          }

          setAuthSubmitButtonsDisabled(form, false);
          return;
        }

        if (root && data.view === 'register-confirmation') {
          showAuthPanel(root, 'register-confirmation', form);
          setAuthSubmitButtonsDisabled(form, false);
          if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('pap:auth-register-success', { detail: data }));
          }
          return;
        }

        updateAuthState(data);
        replaceAccountTool(data.account_html || buildLoggedInAccountToolHtml(data && data.auth_state ? data.auth_state : null));

        closeAuthModal(modal, modal && modal.__papAuthTrigger ? modal.__papAuthTrigger : null);
        setAuthSubmitButtonsDisabled(form, false);

        if (window.dispatchEvent) {
          window.dispatchEvent(new CustomEvent('pap:auth-register-success', { detail: data }));
        }

        if (data.refresh_cart && typeof window.papRefreshCartDrawer === 'function') {
          Promise.resolve(window.papRefreshCartDrawer('refresh')).catch(function (error) {
            if (error && error.name === 'AbortError') {
              return;
            }

            if (window.console && typeof window.console.error === 'function') {
              window.console.error(error);
            }
          });
        }
      })
      .catch(function (error) {
        if (error && error.name === 'AbortError') {
          return;
        }

        if (window.console && typeof window.console.error === 'function') {
          window.console.error(error);
        }

        setAuthSubmitButtonsDisabled(form, false);
      })
      .finally(function () {
        if (activeAuthRequest === controller) {
          activeAuthRequest = null;
        }
      });
  }

  function bindAuthForms(root) {
    toArray(root.querySelectorAll('[data-auth-form]')).forEach(function (form) {
      if (form.getAttribute('data-auth-bound') === '1') {
        return;
      }

      form.setAttribute('data-auth-bound', '1');

      form.addEventListener('submit', function (event) {
        if (!validateAuthForm(form)) {
          event.preventDefault();
          return;
        }

        if (form.getAttribute('data-auth-form') === 'login' && isModalRoot(root)) {
          event.preventDefault();
          submitModalLogin(form);
          return;
        }

        if (form.getAttribute('data-auth-form') === 'lost-password' && isModalRoot(root)) {
          event.preventDefault();
          submitModalLostPassword(form);
          return;
        }

        if (form.getAttribute('data-auth-form') === 'register') {
          event.preventDefault();
          submitModalRegister(form);
          return;
        }
      });

      form.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.matches('input, select, textarea')) {
          return;
        }

        target.classList.remove('pap-is-invalid');
        target.removeAttribute('aria-invalid');
        clearAuthNotices(root);
        setAuthSubmitButtonsDisabled(form, false);
      });

      form.addEventListener('change', function () {
        var checkbox = form.querySelector('[name="agree_terms"]');
        if (checkbox) {
          var checkboxRow = checkbox.closest('.pap-auth-terms');
          if (checkbox.checked && checkboxRow) {
            checkbox.classList.remove('pap-is-invalid');
            checkbox.removeAttribute('aria-invalid');
            checkboxRow.classList.remove('pap-is-invalid');
            var checkboxError = checkboxRow.nextElementSibling && checkboxRow.nextElementSibling.classList && checkboxRow.nextElementSibling.classList.contains('pap-field-error')
              ? checkboxRow.nextElementSibling
              : null;
            if (checkboxError) {
              checkboxError.remove();
            }
          }
        }

        clearAuthNotices(root);
        setAuthSubmitButtonsDisabled(form, false);
      });

      setAuthSubmitButtonsDisabled(form, false);
    });
  }

  function initAuthRoot(root) {
    if (!root || root.getAttribute('data-auth-initialized') === '1') {
      return;
    }

    root.setAttribute('data-auth-initialized', '1');
    bindAuthForms(root);
    initPasswordFields(root);
  }

  function initAllAuthRoots() {
    getAuthRoots().forEach(initAuthRoot);
  }

  document.addEventListener('click', function (event) {
    var modalTrigger = event.target.closest('[data-auth-modal-open]');
    if (modalTrigger) {
      var modal = getAuthModal();
      if (modal) {
        event.preventDefault();
        openAuthModal(modalTrigger, modalTrigger.getAttribute('data-auth-switch') || 'login');
      }
      return;
    }

    var modalClose = event.target.closest('[data-auth-modal-close]');
    if (modalClose) {
      var currentModal = modalClose.closest('[data-auth-modal]');
      if (currentModal) {
        event.preventDefault();
        closeAuthModal(currentModal, currentModal.__papAuthTrigger || document.activeElement);
      }
      return;
    }

    var tabButton = event.target.closest('[data-auth-tab], [data-auth-switch]');
    if (tabButton) {
      var tabName = tabButton.getAttribute('data-auth-tab') || tabButton.getAttribute('data-auth-switch');
      var root = tabButton.closest('[data-auth-root]');
      if (root && tabName) {
        event.preventDefault();
        var modalRoot = root.closest('[data-auth-modal]');
        if (modalRoot && (tabName === 'login' || tabName === 'lost-password' || tabName === 'lost-password-confirmation')) {
          showAuthView(modalRoot, tabName, tabButton);
          return;
        }
        if (modalRoot && (tabName === 'register' || tabName === 'login')) {
          showAuthPanel(modalRoot, tabName, tabButton);
          return;
        }
        setTab(root, tabName);
        if (!isModalRoot(root) && history && history.replaceState && (tabName === 'login' || tabName === 'register')) {
          history.replaceState(null, '', '#' + tabName);
        }
      }
      return;
    }

    var passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle) {
      event.preventDefault();
      togglePassword(passwordToggle);
      return;
    }

    var googleButton = event.target.closest('[data-auth-google]');
    if (googleButton) {
      var loginUrl = googleButton.getAttribute('data-login-url');
      if (loginUrl) {
        window.location.href = loginUrl;
      }
      event.preventDefault();
    }
  });

  window.addEventListener('hashchange', function () {
    var hash = window.location.hash.replace('#', '');
    if (hash !== 'login' && hash !== 'register') {
      return;
    }

    getAuthRoots().forEach(function (root) {
      if (!isModalRoot(root)) {
        setTab(root, hash);
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash.replace('#', '');
    initAllAuthRoots();

    if (hash === 'login' || hash === 'register') {
      getAuthRoots().forEach(function (root) {
        if (!isModalRoot(root)) {
          setTab(root, hash);
        }
      });
    }

    var authParam = new URLSearchParams(window.location.search).get('pap_auth');
    if ((authParam === 'login' || authParam === 'lost-password') && window.papOpenAuthModal) {
      window.papOpenAuthModal(null, authParam);

      if (window.history && window.history.replaceState) {
        var cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('pap_auth');
        window.history.replaceState(null, '', cleanUrl.toString());
      }
    }
  });

  window.papOpenAuthModal = openAuthModal;
  window.papCloseAuthModal = closeAuthModal;
  window.papReplaceAuthAccountTool = replaceAccountTool;
})();
