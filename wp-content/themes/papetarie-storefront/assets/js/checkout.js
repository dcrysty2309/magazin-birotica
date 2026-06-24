(function ($) {
  const selectors = {
    form: 'form.checkout',
    shipToggle: '#ship-to-different-address-checkbox',
    shippingAddress: '.shipping_address',
    guestShippingForm: '[data-pap-guest-shipping-form]',
    guestShippingSummary: '[data-pap-guest-shipping-summary]',
    guestShippingContinue: '[data-pap-guest-shipping-continue]',
    guestShippingEdit: '[data-pap-guest-shipping-edit]',
    addressModal: '[data-checkout-address-modal]',
    addressModalPanel: '[data-checkout-address-modal-panel]',
    addressModalOpen: '[data-checkout-address-modal-open]',
    addressModalClose: '[data-checkout-address-modal-close]',
    productsToggle: '[data-checkout-products-toggle]',
    formRow: '.form-row',
    field: '.input-text, select, textarea, input[type="checkbox"], input[type="radio"]',
  };

  const messages = {
    required: 'Completează acest câmp.',
    email: 'Introdu o adresă de email validă.',
    phone: 'Introdu un număr de telefon valid.',
    postcode: 'Introdu un cod poștal valid.',
  };

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phonePattern = /^[0-9+\s().-]{6,}$/;
  const postcodePattern = /^[0-9]{6}$/;
  const checkoutData = window.papCheckoutData || {};
  const isLoggedIn = Boolean(checkoutData.isLoggedIn);
  let cityOptionsByCounty = checkoutData.citiesByCounty || {};
  let cityDataPromise = null;
  const cityPlaceholder = checkoutData.cityPlaceholder || 'Alege localitatea';
  const countyFirstPlaceholder = checkoutData.countyFirstPlaceholder || 'Alege județul întâi';
  const savedAddressesById = checkoutData.savedAddresses && typeof checkoutData.savedAddresses === 'object'
    ? checkoutData.savedAddresses
    : {};
  const selectedBillingAddressId = String(checkoutData.selectedBillingAddressId || '');
  const selectedShippingAddressId = String(checkoutData.selectedShippingAddressId || '');
  const addressPairs = [
    { state: '#billing_state', city: '#billing_city' },
    { state: '#shipping_state', city: '#shipping_city' },
  ];
  const addressFieldMap = {
    billing: {
      country: '#billing_country',
      first_name: '#billing_first_name',
      last_name: '#billing_last_name',
      company: '#billing_company',
      phone: '#billing_phone',
      state: '#billing_state',
      city: '#billing_city',
      postcode: '#billing_postcode',
      address_1: '#billing_address_1',
      address_2: '#billing_address_2',
    },
    shipping: {
      country: '#shipping_country',
      first_name: '#shipping_first_name',
      last_name: '#shipping_last_name',
      company: '#shipping_company',
      phone: '#shipping_phone',
      state: '#shipping_state',
      city: '#shipping_city',
      postcode: '#shipping_postcode',
      address_1: '#shipping_address_1',
      address_2: '#shipping_address_2',
    },
  };
  let isProgrammaticFieldSync = false;

  const getCityDataUrl = () => {
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
      cityDataPromise = fetch(getCityDataUrl(), { credentials: 'same-origin' })
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

  const getCookieValue = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${encodeURIComponent(name)}=`);

    if (parts.length === 2) {
      return parts.pop().split(';').shift() || '';
    }

    return '';
  };

  const setCookieValue = (name, value) => {
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; path=/; max-age=2592000; samesite=lax`;
  };

  const getSnapshotField = () => getForm().find('[data-pap-guest-shipping-snapshot]').first();

  const setGuestShippingSnapshot = (snapshot) => {
    const serialized = JSON.stringify(snapshot && typeof snapshot === 'object' ? snapshot : {});
    const $snapshotField = getSnapshotField();

    if ($snapshotField.length) {
      $snapshotField.val(serialized);
    }

    setCookieValue('pap_checkout_shipping_snapshot', serialized);
  };

  const getGuestShippingSnapshot = () => {
    const raw = String(getCookieValue('pap_checkout_shipping_snapshot') || '').trim();

    if (!raw) {
      return null;
    }

    try {
      const decoded = JSON.parse(decodeURIComponent(raw));
      return decoded && typeof decoded === 'object' ? decoded : null;
    } catch (error) {
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
      } catch (nestedError) {
        return null;
      }
    }
  };

  const getForm = () => $(selectors.form).first();

  const getFieldRow = ($field) => $field.closest(selectors.formRow);

  const isFieldTouched = ($field) => $field.data('papTouched') === true;
  let guestShippingSummaryCache = null;
  const debugCheckout = (...args) => {
    if (window.console && typeof window.console.log === 'function') {
      window.console.log('[checkout]', ...args);
    }
  };

  const shouldShowValidation = () => getForm().data('papSubmitted') === true;

  const markFieldTouched = ($field) => {
    $field.data('papTouched', true);
  };

  const clearNativeInvalidState = ($field) => {
    const $row = getFieldRow($field);

    $field
      .removeClass('pap-is-invalid woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated')
      .removeAttr('aria-invalid aria-describedby');

    $row
      .removeClass('pap-is-invalid woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated');

    $row.find('.pap-field-error').remove();
  };

  const getSelectPlaceholder = ($field, countyValue) => {
    if ($field.is('#billing_city') || $field.is('#shipping_city')) {
      return countyValue ? cityPlaceholder : countyFirstPlaceholder;
    }

    return String($field.data('placeholder') || $field.find('option:first').text() || '');
  };

  const getCityOptionsForCounty = (countyValue) => {
    if (!countyValue || !Object.prototype.hasOwnProperty.call(cityOptionsByCounty, countyValue)) {
      return [];
    }

    const options = cityOptionsByCounty[countyValue];
    return Array.isArray(options) ? options : [];
  };

  const getFieldBySelector = (selector) => {
    return getForm().find(selector).first();
  };

  const getAddressById = (addressId) => {
    const key = String(addressId || '');
    if (!key || !Object.prototype.hasOwnProperty.call(savedAddressesById, key)) {
      return null;
    }

    const address = savedAddressesById[key];
    return address && typeof address === 'object' ? address : null;
  };

  const getAddressSelector = (prefix) => getForm().find(`[data-checkout-address-selector][data-checkout-address-prefix="${prefix}"]`).first();

  const getSelectedAddressId = (prefix) => {
    const $selector = getAddressSelector(prefix);
    if ($selector.length) {
      return String($selector.val() || '');
    }

    if (prefix === 'shipping') {
      return selectedShippingAddressId || selectedBillingAddressId;
    }

    return selectedBillingAddressId;
  };

  const getAddressFieldSelector = (prefix, fieldKey) => {
    const fieldMap = addressFieldMap[prefix];
    return fieldMap && fieldMap[fieldKey] ? fieldMap[fieldKey] : '';
  };

  const setCheckoutFieldValue = ($field, value) => {
    if (!$field.length || $field.is(':disabled')) {
      return;
    }

    const nextValue = value === null || typeof value === 'undefined' ? '' : String(value);
    $field.val(nextValue);
    clearFieldError($field);
  };

  const applySavedAddressToFields = (prefix, address) => {
    if (!address) {
      return;
    }

    const fields = addressFieldMap[prefix];
    if (!fields) {
      return;
    }

    const $countryField = getFieldBySelector(fields.country);
    const $stateField = getFieldBySelector(fields.state);
    const $cityField = getFieldBySelector(fields.city);

    setCheckoutFieldValue($countryField, address.country || 'RO');
    setCheckoutFieldValue($stateField, address.state || '');
    setCheckoutFieldValue(getFieldBySelector(fields.first_name), address.first_name || '');
    setCheckoutFieldValue(getFieldBySelector(fields.last_name), address.last_name || '');
    setCheckoutFieldValue(getFieldBySelector(fields.company), address.company || '');
    setCheckoutFieldValue(getFieldBySelector(fields.phone), address.phone || '');
    setCheckoutFieldValue(getFieldBySelector(fields.postcode), address.postcode || '');
    setCheckoutFieldValue(getFieldBySelector(fields.address_1), address.address_1 || '');
    setCheckoutFieldValue(getFieldBySelector(fields.address_2), address.address_2 || '');

    if ($stateField.length) {
      syncDependentCitySelect(`#${$stateField.attr('id')}`, false);
    }

    if ($cityField.length) {
      setCheckoutFieldValue($cityField, address.city || '');
    }
  };

  const applySavedAddressSelection = (prefix, addressId, options = {}) => {
    const address = getAddressById(addressId);
    if (!address) {
      return false;
    }

    const $selector = getAddressSelector(prefix);
    if ($selector.length && String($selector.val() || '') !== String(address.id || addressId || '')) {
      $selector.val(String(address.id || addressId || ''));
    }

    runProgrammaticFieldSync(() => {
      applySavedAddressToFields(prefix, address);

      const fields = addressFieldMap[prefix];
      if (fields && fields.state) {
        const $stateField = getFieldBySelector(fields.state);
        if ($stateField.length) {
          $stateField.trigger('change');
        }
      }

      if (fields && fields.city) {
        const $cityField = getFieldBySelector(fields.city);
        if ($cityField.length) {
          $cityField.trigger('change');
        }
      }
    });

    if (!options.silent) {
      $(document.body).trigger('update_checkout');
    }

    return true;
  };

  const syncSavedAddressSelectorState = () => {
    const $shipToggle = $(selectors.shipToggle);
    const shippingDifferent = $shipToggle.length ? $shipToggle.is(':checked') : true;
    const billingSelector = getAddressSelector('billing');
    const shippingSelector = getAddressSelector('shipping');
    const shippingShell = getForm().find('[data-checkout-address-selector-shell="shipping"]').first();

    if (billingSelector.length && !billingSelector.val()) {
      const billingFallback = selectedBillingAddressId || selectedShippingAddressId || '';
      if (billingFallback) {
        billingSelector.val(billingFallback);
      }
    }

    if (shippingSelector.length) {
      if (!shippingDifferent) {
        shippingSelector.val(billingSelector.length ? String(billingSelector.val() || '') : String(selectedBillingAddressId || ''));
        shippingSelector.prop('disabled', true).attr('aria-disabled', 'true');
        if (shippingShell.length) {
          shippingShell.attr('hidden', 'hidden');
        }
      } else {
        shippingSelector.prop('disabled', false).attr('aria-disabled', 'false');
        if (shippingShell.length) {
          shippingShell.removeAttr('hidden');
        }
        const shippingAddressId = String(shippingSelector.val() || selectedShippingAddressId || selectedBillingAddressId || '');
        if (shippingAddressId) {
          applySavedAddressSelection('shipping', shippingAddressId, { silent: true });
        }
      }
    }
  };

  const isAddressCityField = ($field) => $field.is('#billing_city, #shipping_city');

  const runProgrammaticFieldSync = (callback) => {
    const previousState = isProgrammaticFieldSync;
    isProgrammaticFieldSync = true;

    try {
      callback();
    } finally {
      isProgrammaticFieldSync = previousState;
    }
  };

  const populateCitySelect = ($cityField, countyValue, syncChange = true) => {
    if (!$cityField.length) {
      return;
    }

    const currentValue = String($cityField.val() || '');
    const options = getCityOptionsForCounty(countyValue);
    const placeholder = getSelectPlaceholder($cityField, countyValue);
    const hasOptions = options.length > 0;

    $cityField.empty();
    $cityField.append($('<option>', { value: '', text: placeholder }));

    options.forEach((city) => {
      $cityField.append($('<option>', { value: city, text: city }));
    });

    if (!countyValue || !hasOptions) {
      $cityField.val('').prop('disabled', true).attr('aria-disabled', 'true');
      clearFieldError($cityField);
    } else {
      $cityField.prop('disabled', false).attr('aria-disabled', 'false');
      if (options.includes(currentValue)) {
        $cityField.val(currentValue);
      } else {
        $cityField.val('');
      }
    }

    if (syncChange) {
      runProgrammaticFieldSync(() => {
        $cityField.trigger('change');
      });
    }
  };

  const syncDependentCitySelect = (selector, syncChange = true) => {
    const pair = addressPairs.find((entry) => entry.state === selector);
    if (!pair) {
      return;
    }

    const $stateField = getFieldBySelector(pair.state);
    const $cityField = getFieldBySelector(pair.city);
    if (!$stateField.length || !$cityField.length) {
      return;
    }

    populateCitySelect($cityField, String($stateField.val() || ''), syncChange);
  };

  const isVisibleField = ($field) => {
    const $row = getFieldRow($field);

    return $row.length ? $row.is(':visible') && !$field.is(':disabled') : $field.is(':visible') && !$field.is(':disabled');
  };

  const isModalField = ($field) => $field.closest(selectors.addressModal).length > 0;

  const isActiveModalField = ($field) => {
    const $panel = $field.closest(selectors.addressModalPanel);
    if (!$panel.length) {
      return false;
    }

    return $panel.is('.is-active') && !$panel.is(':hidden');
  };

  const focusField = ($field) => {
    const element = $field && $field.length ? $field.get(0) : null;
    if (element && typeof element.focus === 'function') {
      element.focus({ preventScroll: true });
      return;
    }

    if ($field && typeof $field.trigger === 'function') {
      $field.trigger('focus');
    }
  };

  const clearFieldError = ($field) => {
    const $row = getFieldRow($field);

    $field.removeAttr('aria-invalid aria-describedby');
    $field.removeClass('pap-is-invalid');
    $row
      .removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated pap-is-invalid');
    $row.find('.pap-field-error').remove();
  };

  const setFieldError = ($field, message, errorClass, forceDisplay = false) => {
    const $row = getFieldRow($field);
    const descriptionId = `${$field.attr('id') || $field.attr('name') || 'checkout-field'}_description`;

    if (!forceDisplay && !shouldShowValidation($field)) {
      return;
    }

    clearFieldError($field);
    $field.attr('aria-invalid', 'true').attr('aria-describedby', descriptionId);
    $field.addClass('pap-is-invalid');
    $row.addClass('pap-is-invalid');

    if (!message) {
      return;
    }

    $('<small>', {
      id: descriptionId,
      class: 'pap-field-error',
      role: 'alert',
      text: message,
    }).appendTo($row);
  };

  const setFieldValid = ($field, forceDisplay = false) => {
    const $row = getFieldRow($field);

    if (!forceDisplay && !shouldShowValidation($field)) {
      return;
    }

    clearFieldError($field);
  };

  const toggleShippingState = () => {
    const $form = getForm();
    const $shipToggle = $(selectors.shipToggle);
    const shippingDifferent = $shipToggle.length ? $shipToggle.is(':checked') : true;

    $form.toggleClass('pap-is-shipping-different', shippingDifferent);

    const $shippingAddress = $(selectors.shippingAddress);
    if (shippingDifferent || !$shipToggle.length) {
      $shippingAddress.attr('aria-hidden', 'false');
      $shippingAddress.find(selectors.field).each(function () {
        const $field = $(this);
        $field.prop('disabled', false).attr('aria-disabled', 'false');
      });
      syncDependentCitySelect('#shipping_state');
    } else {
      $shippingAddress.attr('aria-hidden', 'true');
      $shippingAddress.find(selectors.field).each(function () {
        const $field = $(this);
        clearFieldError($field);
        $field.prop('disabled', true).attr('aria-disabled', 'true');
      });
    }

    syncSavedAddressSelectorState();
  };

  const setProductsListState = (button, nextExpanded) => {
    const $button = $(button);
    const $card = $button.closest('.pap-checkout-card--shipping-methods, .pap-checkout-shipping-products');
    const moreLabel = String($button.data('labelMore') || $button.attr('data-label-more') || 'Arata mai mult +');
    const lessLabel = String($button.data('labelLess') || $button.attr('data-label-less') || 'Arata mai putin -');
    const $list = $card.find('.pap-checkout-product-list').first();

    $card.find('.pap-checkout-summary-item').each(function (index) {
      const $item = $(this);
      const isHidden = !nextExpanded && index >= 3;
      const isFaded = !nextExpanded && index === 2;
      if (nextExpanded) {
        $item.removeClass('is-faded');
      } else {
        $item.toggleClass('is-faded', isFaded);
      }
      $item.prop('hidden', isHidden);
      $item.attr('aria-hidden', isHidden ? 'true' : 'false');
    });

    if ($list.length) {
      if (nextExpanded) {
        $list.css('max-height', '');
      } else {
        const items = $list.find('.pap-checkout-summary-item').not('[hidden]');
        if (items.length >= 2) {
          const first = items.get(0);
          const second = items.get(1);
          const third = items.get(2);
          const gap = parseFloat(window.getComputedStyle($list.get(0)).rowGap || window.getComputedStyle($list.get(0)).gap || '0') || 0;
          let collapsedHeight = first.getBoundingClientRect().height + second.getBoundingClientRect().height + gap;
          if (third) {
            collapsedHeight += (third.getBoundingClientRect().height * 0.65) + gap;
          }
          $list.css('max-height', `${Math.ceil(collapsedHeight)}px`);
        }
      }
    }

    $button.attr('aria-expanded', nextExpanded ? 'true' : 'false');
    $button.text(nextExpanded ? lessLabel : moreLabel);
    $card.toggleClass('is-products-expanded', nextExpanded);
  };

  const toggleProductsList = (button) => {
    const $button = $(button);
    const expanded = $button.attr('aria-expanded') === 'true';
    setProductsListState(button, !expanded);
  };

  const syncProductsLists = () => {
    $(selectors.productsToggle).each(function () {
      const $button = $(this);
      setProductsListState(this, $button.attr('aria-expanded') === 'true');
    });
  };

  const getGuestShippingSection = () => getForm().find('[data-pap-checkout-section="shipping-address"]').first();

  const getCheckoutStepSection = (step) => getForm().find(`[data-pap-checkout-step="${step}"]`).first();

  const setCheckoutStepState = (step, state) => {
    const normalizedState = ['active', 'complete', 'disabled'].includes(state) ? state : 'disabled';
    const $section = getCheckoutStepSection(step);

    if (!$section.length) {
      return;
    }

    $section
      .removeClass('is-step-active is-step-complete is-step-disabled')
      .addClass(`is-step-${normalizedState}`)
      .attr('data-pap-step-state', normalizedState)
      .attr('aria-disabled', normalizedState === 'disabled' ? 'true' : 'false');

    const $body = $section.find('.pap-checkout-step__body').first();
      if ($body.length) {
        $body.find('input, select, textarea, button').each(function () {
          const $control = $(this);
          if ($control.is('[type="hidden"]')) {
            return;
        }

        $control.prop('disabled', normalizedState === 'disabled');
        $control.attr('aria-disabled', normalizedState === 'disabled' ? 'true' : 'false');
        });
      $body.removeAttr('hidden').attr('aria-hidden', 'false');
    }
  };

  const syncCheckoutStepStates = () => {
    if (!getForm().length) {
      return;
    }

    const guestMode = isLoggedIn ? 'summary' : getGuestShippingMode();
    const shippingAddressState = guestMode === 'summary' ? 'complete' : 'active';
    const shippingMethodsState = guestMode === 'summary' || isLoggedIn ? 'active' : 'disabled';
    const billingState = isLoggedIn ? 'active' : 'disabled';
    const paymentState = isLoggedIn ? 'active' : 'disabled';

    setCheckoutStepState('shipping-address', shippingAddressState);
    setCheckoutStepState('shipping-methods', shippingMethodsState);
    setCheckoutStepState('address-summary', billingState);
    setCheckoutStepState('payment', paymentState);
  };

  const getGuestShippingMode = () => {
    const $section = getGuestShippingSection();
    const explicitMode = String($section.data('papGuestShippingMode') || $section.attr('data-pap-guest-shipping-mode') || '').trim();
    const cookieMode = getCookieValue('pap_checkout_shipping_mode');
    const mode = cookieMode || explicitMode;

    if (mode === 'summary') {
      const snapshot = getGuestShippingSnapshot();
      if (snapshot && hasGuestShippingSnapshotData(snapshot)) {
        return 'summary';
      }
      return 'edit';
    }

    if (mode === 'edit') {
      return 'edit';
    }

    return 'edit';
  };

  const getGuestShippingFieldValue = (selector, source = 'dom') => {
    if (source === 'cache' && guestShippingSummaryCache) {
      const cachedValue = guestShippingSummaryCache[selector];
      if (cachedValue !== undefined) {
        return String(cachedValue || '').trim();
      }
    }

    const $field = getFieldBySelector(selector);
    if (!$field.length) {
      return '';
    }

    if ($field.is('select')) {
      const value = String($field.val() || '').trim();
      if (!value) {
        return '';
      }

      const $selected = $field.find('option:selected').first();
      return String($selected.length ? $selected.text() : value).trim();
    }

    return String($field.val() || '').trim();
  };

  const captureGuestShippingSummaryCache = () => ({
    '#billing_first_name': getGuestShippingFieldValue('#billing_first_name'),
    '#billing_last_name': getGuestShippingFieldValue('#billing_last_name'),
    '#billing_phone': getGuestShippingFieldValue('#billing_phone'),
    '#billing_email': getGuestShippingFieldValue('#billing_email'),
    '#shipping_address_1': getGuestShippingFieldValue('#shipping_address_1'),
    '#shipping_address_2': getGuestShippingFieldValue('#shipping_address_2'),
    '#shipping_city': getGuestShippingFieldValue('#shipping_city'),
    '#shipping_state': getGuestShippingFieldValue('#shipping_state'),
    '#shipping_postcode': getGuestShippingFieldValue('#shipping_postcode'),
  });

  const hasGuestShippingSnapshotData = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return false;
    }

    return Boolean(
      String(snapshot['#billing_first_name'] || '').trim()
      || String(snapshot['#billing_last_name'] || '').trim()
      || String(snapshot['#billing_phone'] || '').trim()
      || String(snapshot['#billing_email'] || '').trim()
      || String(snapshot['#shipping_state'] || '').trim()
      || String(snapshot['#shipping_city'] || '').trim()
      || String(snapshot['#shipping_address_1'] || '').trim()
      || String(snapshot['#shipping_postcode'] || '').trim()
    );
  };

  const captureAndPersistGuestShippingSummaryCache = () => {
    guestShippingSummaryCache = captureGuestShippingSummaryCache();
    setGuestShippingSnapshot(guestShippingSummaryCache);
    return guestShippingSummaryCache;
  };

  const setSelectFieldByLabel = ($field, label) => {
    if (!$field.length) {
      return;
    }

    const targetLabel = String(label || '').trim();
    if (!targetLabel) {
      setCheckoutFieldValue($field, '');
      return;
    }

    let matchedValue = '';
    $field.find('option').each(function () {
      const $option = $(this);
      if (String($option.text() || '').trim() === targetLabel) {
        matchedValue = String($option.val() || '').trim();
        return false;
      }
      return true;
    });

    setCheckoutFieldValue($field, matchedValue);
  };

  const hydrateGuestShippingFields = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return;
    }

    const firstName = String(snapshot['#billing_first_name'] || '').trim();
    const lastName = String(snapshot['#billing_last_name'] || '').trim();
    const phone = String(snapshot['#billing_phone'] || '').trim();
    const email = String(snapshot['#billing_email'] || '').trim();
    const address1 = String(snapshot['#shipping_address_1'] || '').trim();
    const address2 = String(snapshot['#shipping_address_2'] || '').trim();
    const countyLabel = String(snapshot['#shipping_state'] || '').trim();
    const cityLabel = String(snapshot['#shipping_city'] || '').trim();

    setCheckoutFieldValue(getFieldBySelector('#billing_first_name'), firstName);
    setCheckoutFieldValue(getFieldBySelector('#billing_last_name'), lastName);
    setCheckoutFieldValue(getFieldBySelector('#billing_phone'), phone);
    setCheckoutFieldValue(getFieldBySelector('#billing_email'), email);
    setCheckoutFieldValue(getFieldBySelector('#shipping_address_1'), address1);
    setCheckoutFieldValue(getFieldBySelector('#shipping_address_2'), address2);
    setSelectFieldByLabel(getFieldBySelector('#shipping_state'), countyLabel);
    syncDependentCitySelect('#shipping_state', false);
    setSelectFieldByLabel(getFieldBySelector('#shipping_city'), cityLabel);
    captureAndPersistGuestShippingSummaryCache();
  };

  const hasGuestShippingSummaryData = () => {
    return Boolean(
      getGuestShippingFieldValue('#billing_first_name')
      || getGuestShippingFieldValue('#billing_last_name')
      || getGuestShippingFieldValue('#billing_phone')
      || getGuestShippingFieldValue('#billing_email')
      || getGuestShippingFieldValue('#shipping_state')
      || getGuestShippingFieldValue('#shipping_city')
      || getGuestShippingFieldValue('#shipping_address_1')
      || getGuestShippingFieldValue('#shipping_postcode')
    );
  };

  const getGuestShippingSummaryLines = () => {
    const useCache = guestShippingSummaryCache !== null;
    const firstName = getGuestShippingFieldValue('#billing_first_name', useCache ? 'cache' : 'dom');
    const lastName = getGuestShippingFieldValue('#billing_last_name', useCache ? 'cache' : 'dom');
    const phone = getGuestShippingFieldValue('#billing_phone', useCache ? 'cache' : 'dom');
    const email = getGuestShippingFieldValue('#billing_email', useCache ? 'cache' : 'dom');
    const address1 = getGuestShippingFieldValue('#shipping_address_1', useCache ? 'cache' : 'dom');
    const address2 = getGuestShippingFieldValue('#shipping_address_2', useCache ? 'cache' : 'dom');
    const city = getGuestShippingFieldValue('#shipping_city', useCache ? 'cache' : 'dom');
    const state = getGuestShippingFieldValue('#shipping_state', useCache ? 'cache' : 'dom');
    const postcode = getGuestShippingFieldValue('#shipping_postcode', useCache ? 'cache' : 'dom');

    const lines = [];
    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    const addressLine = [address1, address2].filter(Boolean).join(', ').trim();
    const cityLine = [city, state, postcode].filter(Boolean).join(', ').trim();

    if (fullName) {
      lines.push(fullName);
    }

    if (addressLine) {
      lines.push(addressLine);
    }

    if (cityLine) {
      lines.push(cityLine);
    }

    if (phone) {
      lines.push(phone);
    }

    if (email) {
      lines.push(email);
    }

    return lines;
  };

  const syncGuestShippingSummary = () => {
    const $summary = getForm().find(selectors.guestShippingSummary).first();
    if (!$summary.length) {
      return;
    }

    if (guestShippingSummaryCache === null) {
      guestShippingSummaryCache = getGuestShippingSnapshot() || captureGuestShippingSummaryCache();
    }

    const lines = getGuestShippingSummaryLines();
    const $body = $summary.find('.pap-checkout-address-card__body').first();
    const $empty = $summary.find('.pap-checkout-address-card__empty').first();

    if (!lines.length) {
      if ($body.length) {
        $body.remove();
      }
      if ($empty.length) {
        $empty.find('strong').first().text('Nu ai completat încă această adresă.');
        $empty.find('p').first().text('Deschide formularul ca să completezi datele necesare pentru comandă.');
      }
      return;
    }

    if ($empty.length) {
      $empty.remove();
    }

    let $targetBody = $summary.find('.pap-checkout-address-card__body').first();
    if (!$targetBody.length) {
      $targetBody = $('<div class="pap-checkout-address-card__body"></div>').appendTo($summary.find('.pap-checkout-address-card').first());
    }

    $targetBody.empty();
    lines.forEach((line) => {
      $('<p>', { text: line }).appendTo($targetBody);
    });
  };

  const setVisibilityState = ($element, visible, displayValue) => {
    if (!$element.length) {
      return;
    }

    $element.prop('hidden', !visible).attr('aria-hidden', visible ? 'false' : 'true');
    $element.css('display', visible ? (displayValue || '') : 'none');
  };

  const setGuestShippingMode = (mode, persist = true) => {
    if (isLoggedIn) {
      return;
    }

    const normalizedMode = mode === 'summary' ? 'summary' : 'edit';
    debugCheckout('setGuestShippingMode', normalizedMode, { persist });
    const $section = getGuestShippingSection();
    const $form = $section.find(selectors.guestShippingForm).first();
    const $summary = $section.find(selectors.guestShippingSummary).first();
    const $summaryWrap = $section.find('.pap-checkout-guest-shipping__summary').first();
    const $options = $section.find('.pap-checkout-guest-shipping__options').first();
    const $actions = $section.find('.pap-checkout-guest-shipping__actions').first();
    const hasSummary = $summary.length > 0;

    $section.toggleClass('is-summary-mode', normalizedMode === 'summary');

    setVisibilityState($form, normalizedMode !== 'summary' || !hasSummary, 'grid');
    setVisibilityState($summary, normalizedMode === 'summary' && hasSummary, 'grid');
    setVisibilityState($summaryWrap, normalizedMode === 'summary' && hasSummary, 'grid');
    setVisibilityState($options, normalizedMode !== 'summary' || !hasSummary, 'grid');
    setVisibilityState($actions, normalizedMode !== 'summary' || !hasSummary, 'flex');

    if (normalizedMode === 'edit') {
      hydrateGuestShippingFields(getGuestShippingSnapshot() || guestShippingSummaryCache);
    }

    syncGuestShippingSummary();

    if (persist) {
      setCookieValue('pap_checkout_shipping_mode', normalizedMode);
    }

    syncCheckoutStepStates();
  };

  const validateGuestShippingFields = (forceDisplay = false) => {
    const selectorsToValidate = [
      '#billing_first_name',
      '#billing_last_name',
      '#billing_phone',
      '#billing_email',
      '#shipping_state',
      '#shipping_city',
      '#shipping_address_1',
    ];

    let firstInvalid = null;
    let hasErrors = false;
    selectorsToValidate.forEach((selector) => {
      const $field = getFieldBySelector(selector);
      if (!$field.length || $field.is(':disabled')) {
        return;
      }

      const valid = validateField($field, forceDisplay);
      if (!valid) {
        hasErrors = true;
        if (!firstInvalid) {
          firstInvalid = $field;
        }
      }
    });

    if (hasErrors && forceDisplay && firstInvalid && firstInvalid.length) {
      setGuestShippingMode('edit', false);
      const offset = Math.max(firstInvalid.closest(selectors.formRow).offset().top - 120, 0);
      $('html, body').animate({ scrollTop: offset }, 300);
      focusField(firstInvalid);
    }

    return !hasErrors;
  };

  const getAddressModal = () => $(selectors.addressModal).first();

  const setAddressModalPanel = ($modal, context) => {
    const targetContext = context === 'shipping' ? 'shipping' : 'billing';
    $modal.find(selectors.addressModalPanel).each(function () {
      const $panel = $(this);
      const panelContext = String($panel.data('checkoutAddressModalPanel') || $panel.attr('data-checkout-address-modal-panel') || '');
      const isActive = panelContext === targetContext;
      $panel.toggleClass('is-active', isActive);
      $panel.attr('aria-hidden', isActive ? 'false' : 'true');
      if (isActive) {
        $panel.prop('hidden', false);
      } else {
        $panel.prop('hidden', true);
      }
    });
  };

  const openAddressModal = (trigger) => {
    const $modal = getAddressModal();
    if (!$modal.length) {
      return;
    }

    const modal = $modal.get(0);
    const targetContext = trigger && typeof trigger.getAttribute === 'function' ? trigger.getAttribute('data-checkout-address-modal-context') : '';
    modal.__papCheckoutTrigger = trigger || null;
    modal.__papCheckoutContext = targetContext === 'shipping' ? 'shipping' : 'billing';
    setAddressModalPanel($modal, modal.__papCheckoutContext);
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    $modal.addClass('is-open');

    if (window.papModalManager) {
      window.papModalManager.open(modal, () => closeAddressModal(modal, trigger), {
        focusTarget: trigger || modal,
      });
    }

    window.setTimeout(() => {
      const preferredField = targetContext === 'shipping'
        ? modal.querySelector('#shipping_first_name, #shipping_address_1, #shipping_country')
        : modal.querySelector('#billing_address_1, #billing_country, #billing_first_name');
      const firstFocusable = preferredField || modal.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])')
        || modal.querySelector('button:not([disabled])');
      if (firstFocusable && typeof firstFocusable.focus === 'function') {
        firstFocusable.focus({ preventScroll: true });
      }
    }, 0);
  };

  const closeAddressModal = (modal, focusTarget) => {
    const $modal = $(modal || getAddressModal());
    if (!$modal.length) {
      return;
    }

    const dialog = $modal.get(0);
    const returnFocusTarget = focusTarget && typeof focusTarget.focus === 'function'
      ? focusTarget
      : dialog.__papCheckoutTrigger;
    $modal.removeClass('is-open');
    setAddressModalPanel($modal, 'billing');
    dialog.setAttribute('aria-hidden', 'true');
    if (window.papModalManager) {
      window.papModalManager.close(dialog);
    }
    window.setTimeout(() => {
      dialog.hidden = true;
      if (returnFocusTarget && typeof returnFocusTarget.focus === 'function') {
        returnFocusTarget.focus({ preventScroll: true });
      }
    }, 180);
  };

  const validateTextField = ($field, rule, forceDisplay = false, requiredMessage = messages.required) => {
    if (!forceDisplay && !isVisibleField($field)) {
      clearFieldError($field);
      return true;
    }

    const value = String($field.val() || '').trim();
    const required = $field.closest(selectors.formRow).is('.validate-required');

    if (required && value === '') {
      setFieldError($field, requiredMessage, 'woocommerce-invalid-required-field', forceDisplay);
      return false;
    }

    if (value === '') {
      clearFieldError($field);
      return true;
    }

    if (rule === 'email' && !emailPattern.test(value)) {
      setFieldError($field, messages.email, 'woocommerce-invalid-email', forceDisplay);
      return false;
    }

    if (rule === 'phone') {
      const digits = value.replace(/\D/g, '');
      if (!phonePattern.test(value) || digits.length < 8) {
        setFieldError($field, messages.phone, 'woocommerce-invalid-phone', forceDisplay);
        return false;
      }
    }

    if (rule === 'postcode' && !postcodePattern.test(value.replace(/\s+/g, ''))) {
      setFieldError($field, messages.postcode, 'woocommerce-invalid-postcode', forceDisplay);
      return false;
    }

    setFieldValid($field, forceDisplay);
    return true;
  };

  const validateSelectField = ($field, forceDisplay = false, requiredMessage = messages.required) => {
    if (!forceDisplay && !isVisibleField($field)) {
      clearFieldError($field);
      return true;
    }

    const value = String($field.val() || '').trim();
    const required = $field.closest(selectors.formRow).is('.validate-required');

    if (required && value === '') {
      setFieldError($field, requiredMessage, 'woocommerce-invalid-required-field', forceDisplay);
      return false;
    }

    setFieldValid($field, forceDisplay);
    return true;
  };

  const validateCheckboxField = ($field, forceDisplay = false, requiredMessage = messages.required) => {
    if (!forceDisplay && !isVisibleField($field)) {
      clearFieldError($field);
      return true;
    }

    if ($field.is(':radio')) {
      const name = $field.attr('name');
      const $group = name ? getForm().find(`input[type="radio"][name="${name}"]`) : $field;
      const required = $field.closest(selectors.formRow).is('.validate-required');
      const checked = $group.is(':checked');

      if (required && !checked) {
        setFieldError($field.first(), requiredMessage, 'woocommerce-invalid-required-field', forceDisplay);
        return false;
      }

      $group.each(function () {
        clearFieldError($(this));
      });

      return true;
    }

    const required = $field.closest(selectors.formRow).is('.validate-required');

    if (required && !$field.is(':checked')) {
      setFieldError($field, requiredMessage, 'woocommerce-invalid-required-field', forceDisplay);
      return false;
    }

    setFieldValid($field, forceDisplay);
    return true;
  };

  const validateField = ($field, forceDisplay = false) => {
    if ($field.is(':disabled')) {
      clearFieldError($field);
      return true;
    }

    const type = String($field.attr('type') || '').toLowerCase();
    const name = String($field.attr('name') || '').toLowerCase();
    const id = String($field.attr('id') || '').toLowerCase();
    const requiredMessageByField = {
      billing_first_name: 'Completează prenumele.',
      billing_last_name: 'Completează numele.',
      billing_email: 'Introdu emailul.',
      billing_phone: 'Introdu telefonul.',
      shipping_state: 'Alege județul.',
      shipping_city: 'Alege localitatea.',
      shipping_address_1: 'Completează adresa.',
    };
    const fieldKey = name || id;
    const requiredMessage = requiredMessageByField[fieldKey] || messages.required;

    if ($field.is('select')) {
      return validateSelectField($field, forceDisplay, requiredMessage);
    }

    if (type === 'checkbox' || type === 'radio') {
      return validateCheckboxField($field, forceDisplay, requiredMessage);
    }

    if (name.includes('email') || id.includes('email')) {
      return validateTextField($field, 'email', forceDisplay, requiredMessage);
    }

    if (name.includes('phone') || id.includes('phone')) {
      return validateTextField($field, 'phone', forceDisplay, requiredMessage);
    }

    if (name.includes('postcode') || id.includes('postcode') || name.includes('zip')) {
      return validateTextField($field, 'postcode', forceDisplay, requiredMessage);
    }

    return validateTextField($field, undefined, forceDisplay, requiredMessage);
  };

  const validateVisibleFields = (forceDisplay = false) => {
    let firstInvalid = null;
    let hasErrors = false;

    getForm()
      .find(selectors.field)
      .each(function () {
        const $field = $(this);
        const $row = getFieldRow($field);
        const shouldValidateHiddenModalField = forceDisplay && isModalField($field) && isActiveModalField($field);
        if (!$row.length || ($row.is(':hidden') && !shouldValidateHiddenModalField) || $field.is(':disabled')) {
          return;
        }

        const valid = validateField($field, forceDisplay);
        if (!valid) {
          hasErrors = true;
          if (!firstInvalid) {
            firstInvalid = $field;
          }
        }
      });

    if (forceDisplay && hasErrors && firstInvalid && firstInvalid.length) {
      const $firstModal = firstInvalid.closest(selectors.addressModal);
      if ($firstModal.length && $firstModal.is('[hidden]')) {
        openAddressModal(getForm().find(selectors.addressModalOpen).get(0));
        window.setTimeout(() => {
          focusField(firstInvalid);
        }, 220);
      } else {
        const offset = Math.max(firstInvalid.closest(selectors.formRow).offset().top - 120, 0);
        $('html, body').animate({ scrollTop: offset }, 300);
        focusField(firstInvalid);
      }
    }

    return !hasErrors;
  };

  const bindFieldValidation = () => {
    const $form = getForm();

    $form.on('blur', selectors.field, function () {
      const $field = $(this);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (getForm().data('papSubmitted') !== true) {
        window.setTimeout(() => {
          if (getForm().data('papSubmitted') !== true) {
            clearNativeInvalidState($field);
          }
        }, 0);
      }
    });

    $form.on('change', selectors.field, function () {
      const $field = $(this);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (getForm().data('papSubmitted') === true) {
        validateField($field);
      }

      captureAndPersistGuestShippingSummaryCache();
      clearFieldError($field);
      clearNativeInvalidState($field);
    });

    $form.on('input', selectors.field, function () {
      const $field = $(this);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (getForm().data('papSubmitted') !== true) {
        clearNativeInvalidState($field);
        return;
      }

      if (String($field.val() || '').trim() === '') {
        clearNativeInvalidState($field);
        return;
      }

      if (getForm().data('papSubmitted') === true) {
        validateField($field);
      }
      captureAndPersistGuestShippingSummaryCache();
    });

    $form.on('change', selectors.shipToggle, function () {
      toggleShippingState();
    });

    $form.on('change', '[data-checkout-address-selector]', function () {
      const $selector = $(this);
      const prefix = String($selector.data('checkoutAddressPrefix') || $selector.attr('data-checkout-address-prefix') || '');
      const addressId = String($selector.val() || '');

      if (!prefix || !addressId || !applySavedAddressSelection(prefix, addressId)) {
        return;
      }

      const $shipToggle = $(selectors.shipToggle);
      if (prefix === 'billing' && (!$shipToggle.length || !$shipToggle.is(':checked'))) {
        syncSavedAddressSelectorState();
      }
    });

    $form.on('change', '#billing_state, #shipping_state', function () {
      const $field = $(this);
      syncDependentCitySelect(`#${$field.attr('id')}`);
    });

    $form.on('change', '#billing_city, #shipping_city', function () {
      const $field = $(this);

      if (isProgrammaticFieldSync) {
        clearNativeInvalidState($field);
        return;
      }

      if (getForm().data('papSubmitted') === true) {
        validateField($field);
        return;
      }

      clearNativeInvalidState($field);
    });

    $form.on('click', selectors.addressModalOpen, function (event) {
      event.preventDefault();
      openAddressModal(this);
    });

    $form.on('click', selectors.addressModalClose, function (event) {
      event.preventDefault();
      const $modal = $(this).closest(selectors.addressModal);
      closeAddressModal($modal.get(0), this);
    });

    $form.on('click', selectors.productsToggle, function (event) {
      event.preventDefault();
      toggleProductsList(this);
    });

  $form.on('click', selectors.guestShippingContinue, function (event) {
      event.preventDefault();
      debugCheckout('click continue');
      if (!validateGuestShippingFields(true)) {
        debugCheckout('continue blocked by validation');
        return;
      }

      captureAndPersistGuestShippingSummaryCache();
      debugCheckout('continue valid', guestShippingSummaryCache);
      setGuestShippingMode('summary');
      debugCheckout('trigger update_checkout');
      $(document.body).trigger('update_checkout');
      const $summary = getGuestShippingSection().find(selectors.guestShippingSummary).first();
      const target = $summary.length ? $summary.get(0) : null;
      if (target && typeof target.scrollIntoView === 'function') {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });

    $form.on('click', selectors.guestShippingEdit, function (event) {
      event.preventDefault();
      debugCheckout('click modify');
      setGuestShippingMode('edit');

      const $firstField = getFieldBySelector('#billing_first_name');
      focusField($firstField);
    });

    document.addEventListener(
      'submit',
      function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches(selectors.form)) {
          return;
        }

        toggleShippingState();
        $form.data('papSubmitted', true);
        debugCheckout('submit event', {
          guestMode: !isLoggedIn ? getGuestShippingMode() : 'logged-in',
        });

        if (!isLoggedIn && getGuestShippingMode() === 'summary' && !validateGuestShippingFields(true)) {
          event.preventDefault();
          event.stopPropagation();
          if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
          }
          return;
        }

        if (!validateVisibleFields(true)) {
          event.preventDefault();
          event.stopPropagation();
          if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
          }
        }
      },
      true
    );
  };

  const syncCheckoutState = () => {
    if (!getForm().length) {
      return;
    }

    debugCheckout('syncCheckoutState start');
    toggleShippingState();
    if (!isLoggedIn) {
      setGuestShippingMode(getGuestShippingMode(), false);
    }
    syncCheckoutStepStates();
    syncDependentCitySelect('#billing_state');
    syncDependentCitySelect('#shipping_state');
    syncProductsLists();
    debugCheckout('syncCheckoutState end');

  };

  const bootstrap = async () => {
    await loadCityData();
    $(document.body).on('updated_checkout', syncCheckoutState);
    $(bindFieldValidation);
    $(syncCheckoutState);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      bootstrap();
    });
  } else {
    bootstrap();
  }
})(jQuery);
