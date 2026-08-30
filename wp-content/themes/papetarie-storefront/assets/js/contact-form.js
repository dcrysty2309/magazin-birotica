(function () {
  'use strict';

  var form = document.querySelector('[data-pap-contact-form]');
  if (!form || typeof papStorefrontContactForm === 'undefined') {
    return;
  }

  var button = form.querySelector('.pap-contact-form-submit');
  var feedback = form.querySelector('[data-pap-contact-feedback]');
  var defaultButtonLabel = button ? button.textContent : '';

  function clearErrors() {
    form.querySelectorAll('[data-pap-contact-error]').forEach(function (el) {
      el.textContent = '';
    });
    form.querySelectorAll('.has-error').forEach(function (el) {
      el.classList.remove('has-error');
    });
  }

  function showErrors(errors) {
    if (!errors) {
      return;
    }
    Object.keys(errors).forEach(function (field) {
      var errorEl = form.querySelector('[data-pap-contact-error="' + field + '"]');
      var input = form.querySelector('[name="' + field + '"]');
      if (errorEl) {
        errorEl.textContent = errors[field];
      }
      if (input) {
        input.closest('.pap-contact-form-field').classList.add('has-error');
      }
    });
  }

  function setFeedback(message, state) {
    if (!feedback) {
      return;
    }
    feedback.textContent = message;
    feedback.dataset.state = state;
    feedback.hidden = false;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    clearErrors();
    if (feedback) {
      feedback.hidden = true;
    }

    if (button) {
      button.disabled = true;
      button.textContent = 'Se trimite...';
    }

    var formData = new FormData(form);
    var body = new URLSearchParams();
    formData.forEach(function (value, key) {
      body.append(key, value);
    });
    body.set('action', 'pap_contact_form');
    body.set('nonce', papStorefrontContactForm.nonce);

    fetch(papStorefrontContactForm.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success) {
          setFeedback(data.data.message, 'success');
          form.reset();
        } else if (data && data.data && data.data.errors) {
          showErrors(data.data.errors);
          setFeedback(data.data.message, 'error');
        } else {
          var message = data && data.data && data.data.message ? data.data.message : papStorefrontContactForm.genericError;
          setFeedback(message, 'error');
        }
      })
      .catch(function () {
        setFeedback(papStorefrontContactForm.genericError, 'error');
      })
      .finally(function () {
        if (button) {
          button.disabled = false;
          button.textContent = defaultButtonLabel;
        }
      });
  });
})();
