(function () {
  if (window.papConfirmModal) {
    return;
  }

  var modal = null;
  var titleEl = null;
  var bodyEl = null;
  var confirmBtn = null;
  var cancelBtn = null;
  var resolveFn = null;
  var lastFocus = null;

  function buildModal() {
    modal = document.createElement('div');
    modal.className = 'pap-account-confirm-modal';
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
      '<div class="pap-account-confirm-modal__backdrop" data-pap-confirm-close aria-hidden="true"></div>' +
      '<div class="pap-account-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pap-confirm-modal-title">' +
        '<div class="pap-account-confirm-modal__head"><h2 id="pap-confirm-modal-title"></h2></div>' +
        '<div class="pap-account-confirm-modal__body">' +
          '<p></p>' +
          '<div class="pap-account-confirm-modal__actions">' +
            '<button type="button" class="pap-account-secondary-button" data-pap-confirm-close></button>' +
            '<button type="button" class="pap-account-danger-button" data-pap-confirm-ok></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);

    titleEl = modal.querySelector('h2');
    bodyEl = modal.querySelector('.pap-account-confirm-modal__body p');
    confirmBtn = modal.querySelector('[data-pap-confirm-ok]');
    // "button[data-pap-confirm-close]", nu doar "[data-pap-confirm-close]" -
    // backdrop-ul (un <div>) are acelasi atribut (ca sa inchida modalul si
    // el, la click), dar e primul in DOM; fara calificarea pe tag,
    // querySelector il alegea pe el in loc de butonul real, iar
    // "cancelBtn.textContent = 'Anuleaza'" scria textul in backdrop, nu in
    // buton (gasit live 2026-08-31, "Anuleaza" aparea plutind sus-stanga,
    // iar butonul din dialog ramanea gol).
    cancelBtn = modal.querySelector('button[data-pap-confirm-close]');

    Array.prototype.forEach.call(modal.querySelectorAll('[data-pap-confirm-close]'), function (node) {
      node.addEventListener('click', function () { settle(false); });
    });
    confirmBtn.addEventListener('click', function () { settle(true); });
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (window.papModalManager) {
      window.papModalManager.close(modal);
    }

    window.setTimeout(function () {
      modal.hidden = true;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus({ preventScroll: true });
      }
    }, 180);
  }

  function settle(result) {
    closeModal();
    if (typeof resolveFn === 'function') {
      var fn = resolveFn;
      resolveFn = null;
      fn(result);
    }
  }

  // Inlocuieste window.confirm() nativ pentru actiuni distructive (stergere
  // adresa/firma) - un popup de browser poate fi suprimat silentios de Chrome
  // (dupa mai multe dialog-uri repetate pe aceeasi pagina) sau respins din
  // greseala, iar formularul nu mai trimite nimic, fara nicio eroare vizibila
  // - paruse userului ca stergerea "se blocheaza". Acelasi stil vizual ca
  // modalul de confirmare stergere cont (form-edit-account.php). Gasit live
  // 2026-08-31.
  window.papConfirmModal = function (message, options) {
    if (!modal) {
      buildModal();
    }

    options = options || {};
    titleEl.textContent = options.title || 'Confirmă acțiunea';
    bodyEl.textContent = message || '';
    confirmBtn.textContent = options.confirmLabel || 'Da, confirm';
    cancelBtn.textContent = options.cancelLabel || 'Anulează';

    return new Promise(function (resolve) {
      resolveFn = resolve;
      lastFocus = document.activeElement;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');

      if (window.papModalManager) {
        window.papModalManager.open(modal, function () { settle(false); }, { focusTarget: lastFocus });
      }

      window.requestAnimationFrame(function () {
        modal.classList.add('is-open');
      });

      window.setTimeout(function () {
        confirmBtn.focus({ preventScroll: true });
      }, 0);
    });
  };
})();
