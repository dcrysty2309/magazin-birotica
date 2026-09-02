(function () {
  'use strict';

  var form = document.querySelector('[data-pap-newsletter-form]');
  if (!form || typeof papStorefrontNewsletter === 'undefined') {
    return;
  }

  var input = form.querySelector('input[type="email"]');
  var button = form.querySelector('button[type="submit"]');
  var feedback = form.parentElement.querySelector('[data-pap-newsletter-feedback]');
  var defaultButtonLabel = button ? button.textContent : '';

  if (input) {
    input.addEventListener('invalid', function () {
      if (input.validity.valueMissing) {
        input.setCustomValidity('Completează adresa de email.');
      } else if (input.validity.typeMismatch) {
        input.setCustomValidity('Adresa de email nu este validă.');
      } else {
        input.setCustomValidity('');
      }
    });
    input.addEventListener('input', function () {
      input.setCustomValidity('');
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

    var email = input ? input.value.trim() : '';
    if (!email) {
      setFeedback('Introdu o adresă de email.', 'error');
      return;
    }

    if (button) {
      button.disabled = true;
      button.textContent = 'Se trimite...';
    }

    var body = new URLSearchParams();
    body.set('action', 'pap_newsletter_subscribe');
    body.set('nonce', papStorefrontNewsletter.nonce);
    body.set('email', email);

    fetch(papStorefrontNewsletter.ajaxUrl, {
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
          form.hidden = true;
        } else {
          var message = data && data.data && data.data.message ? data.data.message : papStorefrontNewsletter.genericError;
          setFeedback(message, 'error');
        }
      })
      .catch(function () {
        setFeedback(papStorefrontNewsletter.genericError, 'error');
      })
      .finally(function () {
        if (button) {
          button.disabled = false;
          button.textContent = defaultButtonLabel;
        }
      });
  });
})();
