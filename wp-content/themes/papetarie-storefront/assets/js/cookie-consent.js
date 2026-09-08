(function () {
  'use strict';

  var STORAGE_KEY = 'notix_cookie_consent';
  var CONSENT_MAX_AGE_DAYS = 365;

  function readConsent() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) { return null; }
      var data = JSON.parse(raw);
      if (!data || typeof data.ts !== 'number') { return null; }
      var ageDays = (Date.now() - data.ts) / (1000 * 60 * 60 * 24);
      if (ageDays > CONSENT_MAX_AGE_DAYS) { return null; }
      return data;
    } catch (e) {
      return null;
    }
  }

  function writeConsent(analytics) {
    var data = { necessary: true, analytics: !!analytics, ts: Date.now() };
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    } catch (e) { /* localStorage indisponibil, consimtamantul nu persista */ }
    document.dispatchEvent(new CustomEvent('notix:cookie-consent-updated', { detail: data }));
    return data;
  }

  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  ready(function () {
    var banner = document.getElementById('pap-cookie-banner');
    var modal = document.getElementById('pap-cookie-modal');
    if (!banner || !modal) { return; }

    var analyticsToggle = modal.querySelector('[data-pap-cookie-analytics]');
    var acceptAllBtn = banner.querySelector('[data-pap-cookie-accept-all]');
    var refuseBtn = banner.querySelector('[data-pap-cookie-refuse]');
    var settingsBtn = banner.querySelector('[data-pap-cookie-settings]');
    var modalSaveBtn = modal.querySelector('[data-pap-cookie-save]');
    var modalAcceptAllBtn = modal.querySelector('[data-pap-cookie-accept-all-modal]');
    var modalCloseBtn = modal.querySelector('[data-pap-cookie-close]');
    var footerSettingsLink = document.querySelector('[data-pap-cookie-reopen]');

    function hideBanner() { banner.hidden = true; }
    function showBanner() { banner.hidden = false; }
    function hideModal() { modal.hidden = true; document.body.classList.remove('pap-cookie-modal-open'); }
    function showModal() {
      var existing = readConsent();
      if (analyticsToggle) { analyticsToggle.checked = existing ? existing.analytics : false; }
      modal.hidden = false;
      document.body.classList.add('pap-cookie-modal-open');
    }

    var existingConsent = readConsent();
    if (!existingConsent) {
      showBanner();
    } else {
      document.dispatchEvent(new CustomEvent('notix:cookie-consent-updated', { detail: existingConsent }));
    }

    if (acceptAllBtn) {
      acceptAllBtn.addEventListener('click', function () {
        writeConsent(true);
        hideBanner();
        hideModal();
      });
    }
    if (refuseBtn) {
      refuseBtn.addEventListener('click', function () {
        writeConsent(false);
        hideBanner();
      });
    }
    if (settingsBtn) {
      settingsBtn.addEventListener('click', showModal);
    }
    if (modalAcceptAllBtn) {
      modalAcceptAllBtn.addEventListener('click', function () {
        writeConsent(true);
        hideBanner();
        hideModal();
      });
    }
    if (modalSaveBtn) {
      modalSaveBtn.addEventListener('click', function () {
        writeConsent(analyticsToggle ? analyticsToggle.checked : false);
        hideBanner();
        hideModal();
      });
    }
    if (modalCloseBtn) {
      modalCloseBtn.addEventListener('click', hideModal);
    }
    if (footerSettingsLink) {
      footerSettingsLink.addEventListener('click', function (e) {
        e.preventDefault();
        showModal();
      });
    }
    modal.addEventListener('click', function (e) {
      if (e.target === modal) { hideModal(); }
    });
  });
})();
