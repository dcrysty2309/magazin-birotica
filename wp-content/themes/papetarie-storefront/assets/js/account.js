(function () {
  function clearInlineValidation(form) {
    Array.prototype.slice.call(form.querySelectorAll('.pap-is-invalid')).forEach(function (field) {
      field.classList.remove('pap-is-invalid');
      if (field.matches('input, select, textarea')) {
        field.removeAttribute('aria-invalid');
      }
    });

    Array.prototype.slice.call(form.querySelectorAll('.pap-field-error')).forEach(function (errorNode) {
      errorNode.remove();
    });
  }

  function getAuthSubmitButtons(form) {
    return Array.prototype.slice.call(
      form.querySelectorAll('.pap-auth-form-actions .button, .pap-auth-form-actions .woocommerce-button')
    );
  }

  function setAuthSubmitButtonsDisabled(form, disabled) {
    getAuthSubmitButtons(form).forEach(function (button) {
      button.disabled = disabled;
      button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      button.classList.toggle('is-loading', disabled);
    });
  }

  function setInlineValidation(form, selector, message) {
    var field = form.querySelector(selector);

    if (!field) {
      return;
    }

    field.classList.add('pap-is-invalid');
    field.setAttribute('aria-invalid', 'true');
    var row = field.closest('.pap-form-row, .form-row, fieldset');
    if (!row) {
      return;
    }

    row.classList.add('pap-is-invalid');

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
      var username = form.querySelector('#username');
      var password = form.querySelector('#password');

      if (!username || !username.value.trim()) {
        setInlineValidation(form, '#username', 'Introdu emailul.');
        hasErrors = true;
      }

      if (username && username.value.trim() && !isValidEmail(username.value)) {
        setInlineValidation(form, '#username', 'Introdu un email valid.');
        hasErrors = true;
      }

      if (!password || !password.value.trim()) {
        setInlineValidation(form, '#password', 'Introdu parola.');
        hasErrors = true;
      }
    }

    if (formType === 'register') {
      var regEmail = form.querySelector('#reg_email');
      var regUsername = form.querySelector('#reg_username');
      var regPassword = form.querySelector('#reg_password');

      if (regUsername && !regUsername.value.trim()) {
        setInlineValidation(form, '#reg_username', 'Completează numele de utilizator.');
        hasErrors = true;
      }

      if (!regEmail || !regEmail.value.trim()) {
        setInlineValidation(form, '#reg_email', 'Introdu emailul.');
        hasErrors = true;
      }

      if (regEmail && regEmail.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(regEmail.value.trim())) {
        setInlineValidation(form, '#reg_email', 'Introdu un email valid.');
        hasErrors = true;
      }

      if (regPassword && !regPassword.value.trim()) {
        setInlineValidation(form, '#reg_password', 'Introdu parola.');
        hasErrors = true;
      }
    }

    if (formType === 'lost-password') {
      var userLogin = form.querySelector('#user_login');
      if (!userLogin || !userLogin.value.trim()) {
        setInlineValidation(form, '#user_login', 'Introdu emailul.');
        hasErrors = true;
      }

      if (userLogin && userLogin.value.trim() && !isValidEmail(userLogin.value)) {
        setInlineValidation(form, '#user_login', 'Introdu un email valid.');
        hasErrors = true;
      }
    }

    if (formType === 'reset-password') {
      var password1 = form.querySelector('#password_1');
      var password2 = form.querySelector('#password_2');
      if (!password1 || !password1.value.trim()) {
        setInlineValidation(form, '#password_1', 'Introdu parola nouă.');
        hasErrors = true;
      }

      if (!password2 || !password2.value.trim()) {
        setInlineValidation(form, '#password_2', 'Confirmă parola nouă.');
        hasErrors = true;
      }

      if (password1 && password2 && password1.value.trim() && password2.value.trim() && password1.value !== password2.value) {
        setInlineValidation(form, '#password_1', 'Parolele nu se potrivesc.');
        setInlineValidation(form, '#password_2', 'Parolele nu se potrivesc.');
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

  function bindAuthValidation() {
    Array.prototype.slice.call(document.querySelectorAll('[data-auth-form]')).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!validateAuthForm(form)) {
          event.preventDefault();
        }
      });

      form.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.matches('input')) {
          return;
        }
        target.classList.remove('pap-is-invalid');
        target.removeAttribute('aria-invalid');
        setAuthSubmitButtonsDisabled(form, false);
      });

      form.addEventListener('change', function () {
        setAuthSubmitButtonsDisabled(form, false);
      });

      setAuthSubmitButtonsDisabled(form, false);
    });
  }

  function setTab(tabName) {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-auth-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-auth-panel]'));

    if (!tabs.length || !panels.length) {
      return;
    }

    tabs.forEach(function (tab) {
      var isActive = tab.getAttribute('data-auth-tab') === tabName;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

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

  document.addEventListener('click', function (event) {
    var tabButton = event.target.closest('[data-auth-tab], [data-auth-switch]');
    if (tabButton) {
      var target = tabButton.getAttribute('data-auth-tab') || tabButton.getAttribute('data-auth-switch');
      if (target) {
        event.preventDefault();
        setTab(target);
        if (history && history.replaceState) {
          history.replaceState(null, '', '#' + target);
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
    if (hash === 'login' || hash === 'register') {
      setTab(hash);
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    var hash = window.location.hash.replace('#', '');
    if (hash === 'login' || hash === 'register') {
      setTab(hash);
    }

    bindAuthValidation();
  });
})();
