(function ($) {
  const inlineData = window.papAddressBookData || {};
  const cityPlaceholder = inlineData.cityPlaceholder || 'Alege localitatea';
  const countyFirstPlaceholder = inlineData.countyFirstPlaceholder || 'Alege județul întâi';
  const deleteConfirm = inlineData.deleteConfirm || 'Sigur vrei să ștergi această adresă?';
  const ajaxUrl = inlineData.ajaxUrl || '';
  const ajaxAction = inlineData.ajaxAction || 'papetarie_storefront_address_book';
  const ajaxNonce = inlineData.ajaxNonce || '';
  const currentMode = inlineData.currentMode || '';
  const currentAddressId = inlineData.currentAddressId || '';

  const messages = {
    required: 'Completează acest câmp.',
    email: 'Introdu o adresă de email validă.',
    phone: 'Introdu un număr de telefon valid.',
    postcode: 'Introdu un cod poștal valid.',
  };

  let cityOptionsByCounty = inlineData.citiesByCounty && typeof inlineData.citiesByCounty === 'object'
    ? inlineData.citiesByCounty
    : {};
  let cityDataPromise = null;
  let lastTrigger = null;

  const getModal = () => document.querySelector('[data-address-book-modal]');

  const getDataUrl = () => {
    const script = document.currentScript;
    if (script && script.src) {
      try {
        return new URL('../../data/siruta-localities-by-county.json', script.src).toString();
      } catch (error) {
        // Fall back below.
      }
    }

    return '/wp-content/themes/papetarie-storefront/data/siruta-localities-by-county.json';
  };

  const loadCityData = async () => {
    if (Object.keys(cityOptionsByCounty).length > 0) {
      return cityOptionsByCounty;
    }

    if (!cityDataPromise) {
      cityDataPromise = fetch(getDataUrl(), { credentials: 'same-origin' })
        .then((response) => (response.ok ? response.json() : {}))
        .then((json) => {
          if (json && typeof json === 'object' && !Array.isArray(json)) {
            cityOptionsByCounty = json;
          }

          return cityOptionsByCounty;
        })
        .catch(() => cityOptionsByCounty);
    }

    return cityDataPromise;
  };

  const normalizeValue = (value) => String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

  // ".pap-float-field" — same wrapper Detalii cont uses (form-edit-account.php),
  // so error state here is the identical ".pap-is-invalid" class on the
  // wrapper + input, with the message written into the wrapper's own
  // pre-rendered ".pap-field-error" node, matching account.js's
  // setInlineValidation()/clearInlineValidation() pattern.
  const getRow = ($field) => $field.closest('.pap-float-field');

  const clearFieldError = ($field) => {
    const $row = getRow($field);
    $field.removeClass('pap-is-invalid').removeAttr('aria-invalid aria-describedby');
    $row.removeClass('pap-is-invalid');
    $row.find('.pap-field-error').text('');
  };

  const setFieldError = ($field, message) => {
    const $row = getRow($field);
    const descriptionId = `${$field.attr('id') || $field.attr('name') || 'address-field'}_description`;
    $field.addClass('pap-is-invalid').attr('aria-invalid', 'true').attr('aria-describedby', descriptionId);
    $row.addClass('pap-is-invalid');

    let $error = $row.find('.pap-field-error').first();
    if (!$error.length) {
      $error = $('<small>', { class: 'pap-field-error', 'aria-hidden': 'true' }).appendTo($row);
    }

    $error.attr('id', descriptionId).text(message);
  };

  const setFieldValid = ($field) => {
    clearFieldError($field);
  };

  const clearInlineValidation = ($form) => {
    $form.find('.pap-is-invalid').each((_, node) => {
      const $node = $(node);
      $node.removeClass('pap-is-invalid').removeAttr('aria-invalid aria-describedby');
    });
    $form.find('.pap-field-error').text('');
  };

  const getCityOptions = (countyValue) => {
    if (!countyValue || !Object.prototype.hasOwnProperty.call(cityOptionsByCounty, countyValue)) {
      return [];
    }

    const options = cityOptionsByCounty[countyValue];
    return Array.isArray(options) ? options : [];
  };

  const syncCitySelect = ($form) => {
    const $stateField = $form.find('[data-address-book-state]').first();
    const $cityField = $form.find('[data-address-book-city]').first();

    if (!$stateField.length || !$cityField.length) {
      return;
    }

    const countyValue = String($stateField.val() || '');
    const currentValue = String($cityField.val() || '');
    const options = getCityOptions(countyValue);
    const placeholder = countyValue ? cityPlaceholder : countyFirstPlaceholder;

    $cityField.empty();
    $cityField.append($('<option>', { value: '', text: placeholder }));

    options.forEach((city) => {
      $cityField.append($('<option>', { value: city, text: city }));
    });

    if (!countyValue || options.length === 0) {
      $cityField.val('').prop('disabled', true).attr('aria-disabled', 'true');
      clearFieldError($cityField);
      return;
    }

    $cityField.prop('disabled', false).attr('aria-disabled', 'false');

    const exactMatch = options.find((option) => String(option) === currentValue);
    if (exactMatch) {
      $cityField.val(exactMatch);
    } else {
      const normalizedCurrent = normalizeValue(currentValue);
      const normalizedMatch = options.find((option) => normalizeValue(option) === normalizedCurrent);
      $cityField.val(normalizedMatch || '');
    }

    clearFieldError($cityField);
  };

  const validateField = ($field) => {
    if ($field.is(':disabled')) {
      clearFieldError($field);
      return true;
    }

    const value = String($field.val() || '').trim();
    const required = !!$field.prop('required');
    const type = String($field.attr('type') || '').toLowerCase();
    const name = String($field.attr('name') || '').toLowerCase();
    const id = String($field.attr('id') || '').toLowerCase();

    if ($field.is('select')) {
      if (required && value === '') {
        setFieldError($field, messages.required);
        return false;
      }
      setFieldValid($field);
      return true;
    }

    if (type === 'checkbox' || type === 'radio') {
      if (required && !$field.is(':checked')) {
        setFieldError($field, messages.required);
        return false;
      }
      setFieldValid($field);
      return true;
    }

    if (type === 'email' || name.includes('email') || id.includes('email')) {
      if (required && value === '') {
        setFieldError($field, messages.required);
        return false;
      }

      if (value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        setFieldError($field, messages.email);
        return false;
      }
    }

    if (required && value === '') {
      setFieldError($field, messages.required);
      return false;
    }

    if (value === '') {
      clearFieldError($field);
      return true;
    }

    if (name.includes('phone') || id.includes('phone')) {
      const digits = value.replace(/\D/g, '');
      if (digits.length < 8) {
        setFieldError($field, messages.phone);
        return false;
      }
    }

    if (name.includes('postcode') || id.includes('postcode') || name.includes('zip')) {
      if (!/^[0-9]{6}$/.test(value.replace(/\s+/g, ''))) {
        setFieldError($field, messages.postcode);
        return false;
      }
    }

    setFieldValid($field);
    return true;
  };

  const validateForm = ($form) => {
    let isValid = true;

    $form.find('.pap-float-field :input').each((_, field) => {
      const $field = $(field);
      if ($field.is('[type="hidden"]')) {
        return;
      }

      if (!validateField($field)) {
        isValid = false;
      }
    });

    const $stateField = $form.find('[data-address-book-state]').first();
    const $cityField = $form.find('[data-address-book-city]').first();

    if ($stateField.length && $cityField.length && !$cityField.is(':disabled')) {
      const countyValue = String($stateField.val() || '');
      const cityValue = String($cityField.val() || '');
      const options = getCityOptions(countyValue);
      if (countyValue && cityValue && options.length) {
        const normalizedCity = normalizeValue(cityValue);
        const matches = options.some((option) => normalizeValue(option) === normalizedCity);
        if (!matches) {
          setFieldError($cityField, 'Localitatea nu aparține județului selectat.');
          isValid = false;
        }
      }
    }

    return isValid;
  };

  // "message" poate fi un string simplu (mesaj de succes) sau un array de
  // mesaje de validare - randate ca lista, nu inlantuite intr-un singur
  // paragraf ("Completează denumirea. Alege județul...", greu de citit).
  // Semnalat live de user 2026-08-31.
  const setNotice = ($form, message, type) => {
    const $noticeWrap = $form.closest('.pap-account-address-modal').find('[data-address-book-form-notice]').first();
    if (!$noticeWrap.length) {
      return;
    }

    const messages = Array.isArray(message) ? message.filter(Boolean) : (message ? [message] : []);
    if (!messages.length) {
      $noticeWrap.empty();
      return;
    }

    const noticeClass = type === 'success' ? 'is-success' : 'is-error';
    const $notice = $('<div>', {
      class: `pap-account-address-modal__notice ${noticeClass}`,
      role: 'alert',
    });

    if (messages.length > 1) {
      const $list = $('<ul>', { class: 'pap-account-address-modal__notice-list' });
      messages.forEach((msg) => {
        $('<li>').text(msg).appendTo($list);
      });
      $notice.append($list);
    } else {
      $notice.text(messages[0]);
    }

    $noticeWrap.empty().append($notice);
  };

  const clearNotice = ($form) => setNotice($form, '', '');

  const resetForm = ($form) => {
    $form.get(0).reset();
    $form.find('[name="pap_address_id"]').val('');
    $form.find('[name="pap_address_book_action"]').val('save');
    clearInlineValidation($form);
    syncCitySelect($form);
  };

  const fillForm = ($form, address) => {
    const entry = address && typeof address === 'object' ? address : {};
    const setValue = (selector, value) => {
      const $field = $form.find(selector).first();
      if ($field.length) {
        $field.val(value === null || typeof value === 'undefined' ? '' : String(value));
      }
    };

    setValue('[name="pap_address_id"]', entry.id || '');
    setValue('[name="first_name"]', entry.first_name || '');
    setValue('[name="last_name"]', entry.last_name || '');
    setValue('[name="phone"]', entry.phone || '');
    setValue('[name="state"]', entry.state || '');
    syncCitySelect($form);
    setValue('[name="city"]', entry.city || '');
    setValue('[name="address_1"]', entry.address_1 || '');
    setValue('[name="postcode"]', entry.postcode || '');
    setValue('[name="country"]', entry.country || 'RO');
    setValue('[name="delivery_notes"]', entry.delivery_notes || '');

    window.setTimeout(() => syncCitySelect($form), 0);
    window.setTimeout(() => syncCitySelect($form), 120);
  };

  const setModalTitle = (mode) => {
    const modal = getModal();
    if (!modal) {
      return;
    }

    const title = modal.querySelector('#pap-account-address-modal-title');
    if (!title) {
      return;
    }

    title.textContent = mode === 'edit' ? 'Editează adresa' : 'Adaugă adresă';
  };

  const openModal = async (mode, address, trigger) => {
    const modal = getModal();
    if (!modal) {
      return;
    }

    await loadCityData();

    const $form = $(modal).find('[data-address-book-modal-form]').first();
    if (!$form.length) {
      return;
    }

    lastTrigger = trigger && typeof trigger.focus === 'function' ? trigger : null;

    if (mode === 'edit') {
      fillForm($form, address || {});
    } else {
      resetForm($form);
    }

    clearNotice($form);
    setModalTitle(mode);

    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');

    if (window.papModalManager) {
      window.papModalManager.open(modal, () => closeModal(lastTrigger), {
        focusTarget: lastTrigger || trigger || document.activeElement,
      });
    }

    window.setTimeout(() => {
      const firstFocusable = modal.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
        || modal.querySelector('button:not([disabled])');
      if (firstFocusable && typeof firstFocusable.focus === 'function') {
        firstFocusable.focus({ preventScroll: true });
      }
    }, 0);
  };

  const closeModal = (focusTarget) => {
    const modal = getModal();
    if (!modal) {
      return;
    }

    const returnFocusTarget = focusTarget && typeof focusTarget.focus === 'function'
      ? focusTarget
      : lastTrigger && typeof lastTrigger.focus === 'function'
        ? lastTrigger
        : null;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (window.papModalManager) {
      window.papModalManager.close(modal);
    }

    window.setTimeout(() => {
      modal.hidden = true;
      lastTrigger = returnFocusTarget;
      if (returnFocusTarget) {
        returnFocusTarget.focus({ preventScroll: true });
      }
    }, 180);
  };

  const getEntryFromTrigger = (trigger) => {
    if (!trigger) {
      return null;
    }

    const raw = trigger.getAttribute('data-address-book-entry');
    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw);
    } catch (error) {
      return null;
    }
  };

  const requestAddressBook = async (form) => {
    const formData = new FormData(form);
    formData.set('action', ajaxAction);
    formData.set('pap_address_book_nonce', ajaxNonce || formData.get('pap_address_book_nonce') || '');

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    });

    const json = await response.json();
    if (!json || !json.success) {
      const errorMessages = json && json.data && Array.isArray(json.data.messages) ? json.data.messages : [];
      const fallbackMessage = (json && json.data && json.data.message) || 'Nu am putut salva adresa.';
      const requestError = new Error(errorMessages.length ? errorMessages.join(' ') : fallbackMessage);
      requestError.messages = errorMessages.length ? errorMessages : [fallbackMessage];
      throw requestError;
    }

    return json.data || {};
  };

  const setBusyState = ($form, isBusy) => {
    $form.find('button').each((_, button) => {
      const $button = $(button);
      if ($button.is('[data-address-book-modal-close]')) {
        return;
      }

      $button.prop('disabled', isBusy);
      $button.attr('aria-disabled', isBusy ? 'true' : 'false');
    });
  };

  const bindForm = ($form) => {
    if ($form.data('papAddressBookBound')) {
      return;
    }

    $form.data('papAddressBookBound', true);

    const sync = () => syncCitySelect($form);
    sync();
    window.setTimeout(sync, 0);
    window.setTimeout(sync, 150);

    const $stateField = $form.find('[data-address-book-state]').first();
    if ($stateField.length) {
      $stateField.on('change select2:select select2:clear input', sync);
      $(document).on('change select2:select select2:clear', '[data-address-book-state]', sync);
    }

    $form.find('.pap-float-field :input').each((_, field) => {
      const $field = $(field);
      $field.on('blur change', () => validateField($field));
    });

    $form.on('submit', async (event) => {
      event.preventDefault();

      clearNotice($form);
      if (!validateForm($form)) {
        return;
      }

      setBusyState($form, true);
      try {
        // Full reload, not an AJAX list-patch: the single "Adresa mea" card
        // needs to re-render either the filled view (name/lines/actions) or
        // the empty state depending on the result, and a reload guarantees
        // both stay byte-for-byte identical to a normal page render instead
        // of a second, hand-maintained JS rendering of the same markup.
        await requestAddressBook($form.get(0));
        window.location.reload();
      } catch (error) {
        setNotice($form, (error && error.messages) || (error && error.message) || 'Nu am putut salva adresa.', 'error');
        setBusyState($form, false);
      }
    });
  };

  const bindDeleteForms = () => {
    $(document).off('submit.papAddressBookDelete', '[data-address-delete-form]');
    $(document).on('submit.papAddressBookDelete', '[data-address-delete-form]', async function (event) {
      event.preventDefault();

      const confirmed = window.papConfirmModal
        ? await window.papConfirmModal(deleteConfirm, { title: 'Ștergi adresa?', confirmLabel: 'Da, șterge' })
        : window.confirm(deleteConfirm);
      if (!confirmed) {
        return;
      }

      const $form = $(this);
      const formData = new FormData(this);
      formData.set('action', ajaxAction);
      formData.set('pap_address_book_action', 'delete');
      formData.set('pap_address_book_nonce', ajaxNonce || formData.get('pap_address_book_nonce') || '');

      const $button = $form.find('button[type="submit"]').first();
      $button.prop('disabled', true);

      try {
        const response = await fetch(ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
        });
        const json = await response.json();
        if (!json || !json.success) {
          throw new Error(json && json.data && json.data.message ? json.data.message : 'Nu am putut șterge adresa.');
        }

        window.location.reload();
      } catch (error) {
        if (window.console && typeof window.console.error === 'function') {
          window.console.error(error);
        }
        $button.prop('disabled', false);
      }
    });
  };

  const bindOpeners = () => {
    $(document).off('click.papAddressBookModal', '[data-address-book-open-modal]');
    $(document).on('click.papAddressBookModal', '[data-address-book-open-modal]', async function (event) {
      event.preventDefault();

      const trigger = this;
      const mode = String(trigger.getAttribute('data-address-book-mode') || 'add');
      const entry = getEntryFromTrigger(trigger);
      await openModal(mode, entry, trigger);
    });

    $(document).off('click.papAddressBookModalClose', '[data-address-book-modal-close]');
    $(document).on('click.papAddressBookModalClose', '[data-address-book-modal-close]', (event) => {
      event.preventDefault();
      closeModal(document.activeElement);
    });
  };

  const autoOpenFromUrl = async () => {
    if (!currentMode || (currentMode !== 'add' && currentMode !== 'edit')) {
      return;
    }

    const entryTrigger = currentAddressId
      ? Array.prototype.slice.call(document.querySelectorAll('[data-address-book-open-modal]')).find((trigger) => String(trigger.getAttribute('data-address-book-id') || '') === String(currentAddressId))
      : null;
    const entry = entryTrigger ? getEntryFromTrigger(entryTrigger) : null;
    await openModal(currentMode, entry, entryTrigger || null);
  };

  const init = async () => {
    await loadCityData();

    const modal = getModal();
    if (!modal) {
      return;
    }

    bindOpeners();
    bindDeleteForms();

    const $modalForm = $(modal).find('[data-address-book-modal-form]').first();
    if ($modalForm.length) {
      bindForm($modalForm);
    }

    if (modal.getAttribute('data-address-book-open-on-load') === '1') {
      await autoOpenFromUrl();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      init();
    });
  } else {
    init();
  }

  window.addEventListener('pageshow', () => {
    init();
  });
})(window.jQuery);
