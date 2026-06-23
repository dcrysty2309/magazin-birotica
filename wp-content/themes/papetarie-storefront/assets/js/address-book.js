(function ($) {
  const inlineData = window.papAddressBookData || {};
  const cityPlaceholder = inlineData.cityPlaceholder || 'Alege localitatea';
  const countyFirstPlaceholder = inlineData.countyFirstPlaceholder || 'Alege județul întâi';
  const deleteConfirm = inlineData.deleteConfirm || 'Sigur vrei să ștergi această adresă?';
  const messages = {
    required: 'Completează acest câmp.',
    phone: 'Introdu un număr de telefon valid.',
    postcode: 'Introdu un cod poștal valid.',
  };

  let cityOptionsByCounty = inlineData.citiesByCounty && typeof inlineData.citiesByCounty === 'object'
    ? inlineData.citiesByCounty
    : {};
  let cityDataPromise = null;

  const getDataUrl = () => {
    const script = document.currentScript;
    if (script && script.src) {
      try {
        return new URL('../../data/ro-localities-by-county.json', script.src).toString();
      } catch (error) {
        // Fall back below.
      }
    }

    return '/wp-content/themes/papetarie-storefront/data/ro-localities-by-county.json';
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

  const getCityOptions = (countyValue) => {
    if (!countyValue || !Object.prototype.hasOwnProperty.call(cityOptionsByCounty, countyValue)) {
      return [];
    }

    const options = cityOptionsByCounty[countyValue];
    return Array.isArray(options) ? options : [];
  };

  const getRow = ($field) => $field.closest('.form-row');

  const clearFieldError = ($field) => {
    const $row = getRow($field);
    $field.removeAttr('aria-invalid aria-describedby');
    $row
      .removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated');
    $row.find('.checkout-inline-error-message').remove();
  };

  const setFieldError = ($field, message, errorClass) => {
    const $row = getRow($field);
    const descriptionId = `${$field.attr('id') || $field.attr('name') || 'address-field'}_description`;
    clearFieldError($field);
    $field.attr('aria-invalid', 'true').attr('aria-describedby', descriptionId);
    $row.addClass(`woocommerce-invalid ${errorClass}`);
    $('<p>', {
      id: descriptionId,
      class: 'checkout-inline-error-message',
      role: 'alert',
      text: message,
    }).appendTo($row);
  };

  const setFieldValid = ($field) => {
    clearFieldError($field);
    getRow($field).addClass('woocommerce-validated');
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
    const required = $field.closest('.form-row').is('.validate-required');
    const type = String($field.attr('type') || '').toLowerCase();
    const name = String($field.attr('name') || '').toLowerCase();
    const id = String($field.attr('id') || '').toLowerCase();

    if ($field.is('select')) {
      if (required && value === '') {
        setFieldError($field, messages.required, 'woocommerce-invalid-required-field');
        return false;
      }
      setFieldValid($field);
      return true;
    }

    if (type === 'checkbox' || type === 'radio') {
      if (required && !$field.is(':checked')) {
        setFieldError($field, messages.required, 'woocommerce-invalid-required-field');
        return false;
      }
      setFieldValid($field);
      return true;
    }

    if (required && value === '') {
      setFieldError($field, messages.required, 'woocommerce-invalid-required-field');
      return false;
    }

    if (value === '') {
      clearFieldError($field);
      return true;
    }

    if (name.includes('phone') || id.includes('phone')) {
      const digits = value.replace(/\D/g, '');
      if (digits.length < 8) {
        setFieldError($field, messages.phone, 'woocommerce-invalid-phone');
        return false;
      }
    }

    if (name.includes('postcode') || id.includes('postcode') || name.includes('zip')) {
      if (!/^[0-9]{6}$/.test(value.replace(/\s+/g, ''))) {
        setFieldError($field, messages.postcode, 'woocommerce-invalid-postcode');
        return false;
      }
    }

    setFieldValid($field);
    return true;
  };

  const validateForm = ($form) => {
    let isValid = true;

    $form.find('.pap-address-form-row :input').each((_, field) => {
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
          setFieldError($cityField, 'Localitatea nu aparține județului selectat.', 'woocommerce-invalid-required-field');
          isValid = false;
        }
      }
    }

    return isValid;
  };

  const initForm = (form) => {
    const $form = $(form);
    if ($form.data('papAddressBookInit')) {
      return;
    }

    $form.data('papAddressBookInit', true);

    const sync = () => syncCitySelect($form);
    sync();
    window.setTimeout(sync, 0);
    window.setTimeout(sync, 150);

    const $stateField = $form.find('[data-address-book-state]').first();
    if ($stateField.length) {
      $stateField.on('change select2:select select2:clear input', sync);
      $(document).on('change select2:select select2:clear', '[data-address-book-state]', sync);
    }

    $form.find('.pap-address-form-row :input').each((_, field) => {
      const $field = $(field);
      $field.on('blur change', () => validateField($field));
    });

    $form.on('submit', (event) => {
      if (!validateForm($form)) {
        event.preventDefault();
      }
    });

    $form.find('[data-address-delete-form]').each((_, deleteForm) => {
      $(deleteForm).on('submit', (event) => {
        if (!window.confirm(deleteConfirm)) {
          event.preventDefault();
        }
      });
    });
  };

  const init = async () => {
    await loadCityData();
    $('.pap-account-address-form').each((_, form) => initForm(form));
    $('.pap-account-address-card [data-address-delete-form]').each((_, form) => initForm(form));
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