(function ($) {
  const selectors = {
    form: 'form.checkout',
    shipToggle: '#ship-to-different-address-checkbox',
    shippingAddress: '.shipping_address',
    guestShippingForm: '[data-pap-guest-shipping-form]',
    guestShippingSummary: '[data-pap-guest-shipping-summary]',
    guestShippingContinue: '[data-pap-guest-shipping-continue]',
    guestShippingEdit: '[data-pap-guest-shipping-edit]',
    authShipping: '[data-pap-auth-shipping]',
    authShippingForm: '[data-pap-auth-shipping-form]',
    authAddressList: '[data-pap-auth-address-list]',
    authShippingSummary: '[data-pap-auth-shipping-summary]',
    authAddressGrid: '[data-pap-auth-address-grid]',
    authAddressOption: '[data-pap-auth-address-option]',
    authAddressAdd: '[data-pap-auth-address-add]',
    authAddressCancel: '[data-pap-auth-address-cancel]',
    authAddressSave: '[data-pap-auth-address-save]',
    authAddressSaveCheckbox: 'input[name="pap_save_address_for_future"]',
    authTemporaryEdit: '[data-pap-auth-temporary-edit]',
    authAddressNotice: '[data-pap-auth-address-notice]',
    authAddressNoticeCopy: '[data-pap-auth-address-notice-copy]',
    authShippingActions: '[data-pap-auth-shipping-actions]',
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
  const checkoutAddressCount = Number(checkoutData.checkoutAddressCount || 0);
  const hasInitialTemporaryCheckoutAddress = Boolean(checkoutData.isTemporaryCheckoutAddress);
  let authAddressFormMode = '';
  let authTemporarySummaryVisible = hasInitialTemporaryCheckoutAddress;
  let authShippingBusy = false;
  let authShippingEditSnapshotCache = null;
  let authShippingDraft = null;
  let authCurrentOrderSnapshot = null;
  let initialCheckoutRefreshRequested = false;
  const postcodeSelectors = new Set(['#billing_postcode', '#shipping_postcode']);
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

  const normalizeCheckoutPostcodeSnapshot = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return {};
    }

    const postcode = String(
      snapshot.postcode
      || snapshot.shipping_postcode
      || snapshot['#shipping_postcode']
      || snapshot.billing_postcode
      || snapshot['#billing_postcode']
      || ''
    ).trim();

    return {
      ...snapshot,
      postcode,
      shipping_postcode: String(snapshot.shipping_postcode || snapshot['#shipping_postcode'] || postcode).trim(),
      billing_postcode: String(snapshot.billing_postcode || snapshot['#billing_postcode'] || postcode).trim(),
      '#shipping_postcode': String(snapshot['#shipping_postcode'] || postcode).trim(),
      '#billing_postcode': String(snapshot['#billing_postcode'] || postcode).trim(),
    };
  };

  const setGuestShippingSnapshot = (snapshot) => {
    const serialized = JSON.stringify(normalizeCheckoutPostcodeSnapshot(snapshot && typeof snapshot === 'object' ? snapshot : {}));
    const $snapshotField = getSnapshotField();

    if ($snapshotField.length) {
      $snapshotField.val(serialized);
    }

    setCookieValue('pap_checkout_shipping_snapshot', serialized);
  };

  const resetGuestShippingState = () => {
    guestShippingSummaryCache = null;
    setGuestShippingSnapshot({});
    setCookieValue('pap_checkout_shipping_mode', 'edit');
  };

  const getGuestShippingSnapshot = () => {
    const $snapshotField = getSnapshotField();
    const fieldRaw = $snapshotField.length ? String($snapshotField.val() || '').trim() : '';
    const raw = fieldRaw || String(getCookieValue('pap_checkout_shipping_snapshot') || '').trim();

    if (!raw) {
      return null;
    }

    try {
      const decoded = JSON.parse(decodeURIComponent(raw));
      return decoded && typeof decoded === 'object' ? normalizeCheckoutPostcodeSnapshot(decoded) : null;
    } catch (error) {
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? normalizeCheckoutPostcodeSnapshot(parsed) : null;
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

  const requestInitialCheckoutRefresh = (reason = 'bootstrap') => {
    if (initialCheckoutRefreshRequested) {
      return;
    }

    initialCheckoutRefreshRequested = true;

    window.setTimeout(() => {
      debugCheckout('request initial update_checkout', {
        reason,
        state: getPostcodeDebugState(),
      });
      $(document.body).trigger('update_checkout');
    }, 150);
  };

  const getActiveFieldDebugLabel = () => {
    const activeElement = document.activeElement;
    if (!activeElement) {
      return 'none';
    }

    const tagName = String(activeElement.tagName || '').toLowerCase();
    const id = String(activeElement.id || '').trim();
    const name = String(activeElement.getAttribute ? activeElement.getAttribute('name') || '' : '').trim();

    if (id) {
      return `${tagName}#${id}`;
    }

    if (name) {
      return `${tagName}[name="${name}"]`;
    }

    return tagName || 'unknown';
  };

  const getPostcodeDebugState = () => ({
    activeElement: getActiveFieldDebugLabel(),
    shippingPostcode: String(getFieldBySelector('#shipping_postcode').val() || '').trim(),
    billingPostcode: String(getFieldBySelector('#billing_postcode').val() || '').trim(),
    guestCachePostcode: guestShippingSummaryCache ? String(guestShippingSummaryCache.postcode || guestShippingSummaryCache['#shipping_postcode'] || guestShippingSummaryCache.shipping_postcode || '').trim() : '',
    authDraftPostcode: authShippingDraft ? String(authShippingDraft.postcode || authShippingDraft['#shipping_postcode'] || authShippingDraft.shipping_postcode || authShippingDraft['#billing_postcode'] || authShippingDraft.billing_postcode || '').trim() : '',
    authCurrentOrderPostcode: authCurrentOrderSnapshot ? String(authCurrentOrderSnapshot.postcode || authCurrentOrderSnapshot['#shipping_postcode'] || authCurrentOrderSnapshot.shipping_postcode || authCurrentOrderSnapshot['#billing_postcode'] || authCurrentOrderSnapshot.billing_postcode || '').trim() : '',
  });

  const bindPostcodeInputGuards = () => {
    if (document.documentElement && document.documentElement.dataset.papCheckoutPostcodeGuardsBound === '1') {
      return;
    }

    if (document.documentElement) {
      document.documentElement.dataset.papCheckoutPostcodeGuardsBound = '1';
    }

    const handlePostcodeKeystroke = (event) => {
      const target = event.target;
      if (!target || !target.matches || !target.matches('#billing_postcode, #shipping_postcode')) {
        return;
      }

      debugCheckout(`postcode ${event.type} capture`, {
        field: `#${target.id || ''}`,
        value: String(target.value || '').trim(),
        state: getPostcodeDebugState(),
      });

      if (!isProgrammaticFieldSync) {
        if (authAddressFormMode) {
          captureAuthShippingDraft(`postcode-input-capture:${target.id || ''}`);
        } else if (!isLoggedIn) {
          captureAndPersistGuestShippingSummaryCache();
        }
      }

      clearFieldError($(target));
      clearNativeInvalidState($(target));

      // Prevent WooCommerce core listeners from reacting to each keystroke.
      event.stopImmediatePropagation();
    };

    document.addEventListener('input', handlePostcodeKeystroke, true);
    document.addEventListener('keyup', handlePostcodeKeystroke, true);
  };

  const clearSessionExpiredNotice = () => {
    const $notices = $('ul.woocommerce-error, .woocommerce-notices-wrapper .woocommerce-error, .pap-checkout-notices .woocommerce-error').filter(':visible');
    if (!$notices.length) {
      return;
    }

    $notices.each(function () {
      const $notice = $(this);
      const text = String($notice.text() || '').replace(/\s+/g, ' ').trim().toLowerCase();
      const matchesExpired = text.includes('sesiunea ta a expirat') || text.includes('sorry, your session has expired');
      if (!matchesExpired) {
        return;
      }

      $notice.remove();
    });
  };

  const shouldShowValidation = () => getForm().data('papSubmitted') === true;

  const markFieldTouched = ($field) => {
    $field.data('papTouched', true);
  };

  const clearNativeInvalidState = ($field) => {
    const $row = getFieldRow($field);

    $field
      .removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated')
      .removeAttr('aria-invalid aria-describedby');

    $row
      .removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated');

    $row.find('.checkout-inline-error-message').remove();
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

  const getAddressSelector = (prefix) => getForm().find(`select[data-checkout-address-selector][data-checkout-address-prefix="${prefix}"]`).first();

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

  const setCheckboxFieldState = ($field, checked) => {
    if (!$field.length || $field.is(':disabled')) {
      return;
    }

    $field.prop('checked', Boolean(checked));
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

    setSelectFieldByValueOrLabel($countryField, address.country || 'RO', true);
    setSelectFieldByValueOrLabel($stateField, address.state || '', true);
    setCheckoutFieldValue(getFieldBySelector(fields.first_name), address.first_name || '');
    setCheckoutFieldValue(getFieldBySelector(fields.last_name), address.last_name || '');
    setCheckoutFieldValue(getFieldBySelector(fields.company), address.company || '');
    setCheckoutFieldValue(getFieldBySelector(fields.phone), address.phone || '');
    setSelectFieldByValueOrLabel(getFieldBySelector('#billing_state'), address.state || '', true);
    setSelectFieldByValueOrLabel(getFieldBySelector('#billing_city'), address.city || '', true);
    setCheckoutFieldValue(getFieldBySelector('#billing_address_1'), address.address_1 || '');
    setCheckoutFieldValue(getFieldBySelector('#billing_postcode'), address.postcode || '');
    setCheckoutFieldValue(getFieldBySelector(fields.postcode), address.postcode || '');
    setCheckoutFieldValue(getFieldBySelector(fields.address_1), address.address_1 || '');

    if ($stateField.length) {
      syncDependentCitySelect(`#${$stateField.attr('id')}`, false);
    }

    if ($cityField.length) {
      setSelectFieldByValueOrLabel($cityField, address.city || '', true);
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
    $row
      .removeClass('woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-invalid-phone woocommerce-invalid-postcode woocommerce-validated');
    $row.find('.checkout-inline-error-message').remove();
  };

  const setFieldError = ($field, message, errorClass, forceDisplay = false) => {
    const $row = getFieldRow($field);
    const descriptionId = `${$field.attr('id') || $field.attr('name') || 'checkout-field'}_description`;

    if (!forceDisplay && !shouldShowValidation($field)) {
      return;
    }

    clearFieldError($field);
    $field.attr('aria-invalid', 'true').attr('aria-describedby', descriptionId);
    $row.addClass(`woocommerce-invalid ${errorClass}`);

    if (!message) {
      return;
    }

    let $error = $row.find('.checkout-inline-error-message').first();
    if (!$error.length) {
      $error = $('<small>', {
      id: descriptionId,
      class: 'checkout-inline-error-message',
      role: 'alert',
        'aria-live': 'polite',
        'aria-atomic': 'true',
      }).appendTo($row);
    }

    $error.attr('id', descriptionId);
    $error.text(message);
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
    const moreLabel = String($button.data('labelMore') || $button.attr('data-label-more') || 'Arata mai mult');
    const lessLabel = String($button.data('labelLess') || $button.attr('data-label-less') || 'Arata mai putin');
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
    const hasConfirmedAuthAddress = hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot) && !authAddressFormMode;
    const shippingAddressState = isLoggedIn
      ? (hasConfirmedAuthAddress ? 'complete' : 'active')
      : (guestMode === 'summary' ? 'complete' : 'active');
    const shippingMethodsState = shippingAddressState === 'complete' ? 'active' : 'disabled';
    const billingState = isLoggedIn ? 'active' : 'disabled';
    const paymentState = shippingAddressState === 'complete' ? 'active' : 'disabled';

    setCheckoutStepState('shipping-address', shippingAddressState);
    setCheckoutStepState('shipping-methods', shippingMethodsState);
    setCheckoutStepState('address-summary', billingState);
    setCheckoutStepState('payment', paymentState);
    syncPaymentMethodCards();
    syncCheckoutSubmitState();
  };

  const hasConfirmedShippingAddress = () => {
    if (!isLoggedIn) {
      return getGuestShippingMode() === 'summary' && hasGuestShippingSnapshotData(getGuestShippingSnapshot());
    }

    return !authAddressFormMode && hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot);
  };

  const hasVisibleShippingMethodSelection = () => {
    const $shippingMethodsSection = getForm().find('[data-pap-checkout-section="shipping-methods"]').first();
    if (!$shippingMethodsSection.length) {
      return true;
    }

    const $notice = $shippingMethodsSection.find('.pap-checkout-shipping-methods__notice').first();
    if ($notice.length) {
      return false;
    }

    const $methods = $shippingMethodsSection.find('.pap-checkout-shipping-method');
    if (!$methods.length) {
      return true;
    }

    return $shippingMethodsSection.find('input.shipping_method:checked').length > 0;
  };

  const hasVisiblePaymentMethodSelection = () => {
    const $payment = getForm().find('#payment').first();
    if (!$payment.length) {
      return false;
    }

    const $notice = $payment.find('.pap-checkout-payment-methods__notice').first();
    if ($notice.length) {
      return false;
    }

    const $methods = $payment.find('input[name="payment_method"]');
    if (!$methods.length) {
      return false;
    }

    return $methods.filter(':checked').length > 0;
  };

  const getCheckoutSubmitBlockMessage = () => {
    if (!hasConfirmedShippingAddress()) {
      return 'Completează și confirmă adresa pentru a continua.';
    }

    if (!hasVisibleShippingMethodSelection()) {
      return 'Selectează metoda de livrare.';
    }

    const $payment = getForm().find('#payment').first();
    const hasPaymentMethods = $payment.find('input[name="payment_method"]').length > 0;

    if (!hasPaymentMethods) {
      return 'Nu există metode de plată active în WooCommerce pentru această comandă.';
    }

    if (!hasVisiblePaymentMethodSelection()) {
      return 'Selectează o metodă de plată.';
    }

    return '';
  };

  const syncPaymentMethodCards = () => {
    const $payment = getForm().find('#payment').first();
    if (!$payment.length) {
      return;
    }

    $payment.find('.pap-checkout-payment-method').each(function () {
      const $item = $(this);
      const $input = $item.find('input[name="payment_method"]').first();
      $item.toggleClass('is-selected', $input.length ? $input.is(':checked') : false);
    });
  };

  const syncCheckoutSubmitState = () => {
    const $payment = getForm().find('#payment').first();
    const $button = getForm().find('#place_order').first();
    const $hint = getForm().find('[data-pap-checkout-submit-hint]').first();

    if (!$payment.length || !$button.length) {
      return;
    }

    const blockMessage = getCheckoutSubmitBlockMessage();
    const isBlocked = blockMessage !== '';

    $button
      .prop('disabled', isBlocked)
      .attr('aria-disabled', isBlocked ? 'true' : 'false')
      .toggleClass('is-disabled', isBlocked);

    $payment
      .toggleClass('is-submit-blocked', isBlocked)
      .toggleClass('is-submit-ready', !isBlocked);

    if ($hint.length) {
      if (isBlocked) {
        $hint.text(blockMessage).removeAttr('hidden');
      } else {
        $hint.text('').attr('hidden', 'hidden');
      }
    }
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
    postcode: getGuestShippingFieldValue('#shipping_postcode'),
    '#shipping_postcode': getGuestShippingFieldValue('#shipping_postcode'),
    shipping_postcode: getGuestShippingFieldValue('#shipping_postcode'),
    billing_postcode: getGuestShippingFieldValue('#shipping_postcode'),
    '#order_comments': getGuestShippingFieldValue('#order_comments'),
  });

  const getRawFieldValue = (selector) => {
    const $field = getFieldBySelector(selector);
    if (!$field.length) {
      return '';
    }

    return String($field.val() || '').trim();
  };

  const syncBillingShadowFieldsFromCurrentFields = () => {
    const shippingState = getRawFieldValue('#shipping_state');
    const shippingCity = getRawFieldValue('#shipping_city');
    const shippingAddress1 = getRawFieldValue('#shipping_address_1');
    const shippingPostcode = getRawFieldValue('#shipping_postcode');

    setSelectFieldByValueOrLabel(getFieldBySelector('#billing_state'), shippingState || getRawFieldValue('#billing_state'), true);
    setSelectFieldByValueOrLabel(getFieldBySelector('#billing_city'), shippingCity || getRawFieldValue('#billing_city'), true);
    setCheckoutFieldValue(getFieldBySelector('#billing_address_1'), shippingAddress1 || getRawFieldValue('#billing_address_1'));
    setCheckoutFieldValue(getFieldBySelector('#billing_postcode'), shippingPostcode || getRawFieldValue('#billing_postcode'));
  };

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
      || String(snapshot['#shipping_address_2'] || '').trim()
      || String(snapshot.postcode || '').trim()
      || String(snapshot['#shipping_postcode'] || '').trim()
      || String(snapshot.shipping_postcode || '').trim()
    );
  };

  const captureAndPersistGuestShippingSummaryCache = () => {
    guestShippingSummaryCache = captureGuestShippingSummaryCache();
    syncBillingShadowFieldsFromCurrentFields();
    setGuestShippingSnapshot(guestShippingSummaryCache);
    return guestShippingSummaryCache;
  };

  const captureAuthShippingSnapshot = () => ({
    '#billing_first_name': getGuestShippingFieldValue('#billing_first_name'),
    '#billing_last_name': getGuestShippingFieldValue('#billing_last_name'),
    '#billing_phone': getGuestShippingFieldValue('#billing_phone'),
    '#billing_email': getGuestShippingFieldValue('#billing_email'),
    '#billing_country': getGuestShippingFieldValue('#billing_country'),
    '#shipping_country': getGuestShippingFieldValue('#shipping_country'),
    '#shipping_address_1': getGuestShippingFieldValue('#shipping_address_1'),
    '#shipping_address_2': getGuestShippingFieldValue('#shipping_address_2'),
    '#shipping_city': getGuestShippingFieldValue('#shipping_city'),
    '#shipping_state': getGuestShippingFieldValue('#shipping_state'),
    postcode: getGuestShippingFieldValue('#shipping_postcode') || getGuestShippingFieldValue('#billing_postcode'),
    '#billing_postcode': getGuestShippingFieldValue('#billing_postcode') || getGuestShippingFieldValue('#shipping_postcode'),
    '#shipping_postcode': getGuestShippingFieldValue('#shipping_postcode'),
    billing_postcode: getGuestShippingFieldValue('#billing_postcode') || getGuestShippingFieldValue('#shipping_postcode'),
    shipping_postcode: getGuestShippingFieldValue('#shipping_postcode'),
    billing_state: getGuestShippingFieldValue('#billing_state'),
    shipping_state: getGuestShippingFieldValue('#shipping_state'),
    billing_city: getGuestShippingFieldValue('#billing_city'),
    shipping_city: getGuestShippingFieldValue('#shipping_city'),
    billing_address_1: getGuestShippingFieldValue('#billing_address_1'),
    shipping_address_1: getGuestShippingFieldValue('#shipping_address_1'),
    '#order_comments': getGuestShippingFieldValue('#order_comments'),
  });

  const captureAuthShippingDraft = (reason = 'capture') => {
    authShippingDraft = captureAuthShippingSnapshot();
    syncBillingShadowFieldsFromCurrentFields();
    debugCheckout('auth draft captured', reason, authShippingDraft);
    return authShippingDraft;
  };

  const setAuthCurrentOrderSnapshot = (snapshot, reason = 'set-current-order') => {
    if (!snapshot || typeof snapshot !== 'object') {
      return;
    }

    authCurrentOrderSnapshot = normalizeCheckoutPostcodeSnapshot(snapshot);
    debugCheckout('auth current order snapshot set', reason, authCurrentOrderSnapshot);
  };

  const getCheckoutStandardAddressSnapshot = () => {
    const snapshot = checkoutData.checkoutStandardAddressSnapshot;
    if (!snapshot || typeof snapshot !== 'object') {
      return null;
    }

    if (Object.keys(snapshot).some((key) => String(key).startsWith('#'))) {
      return snapshot;
    }

    return {
      '#billing_first_name': String(snapshot.billing_first_name || ''),
      '#billing_last_name': String(snapshot.billing_last_name || ''),
      '#billing_email': String(snapshot.billing_email || ''),
      '#billing_phone': String(snapshot.billing_phone || ''),
      '#billing_country': String(snapshot.billing_country || 'RO') || 'RO',
      '#billing_state': String(snapshot.billing_state || ''),
      '#billing_city': String(snapshot.billing_city || ''),
      '#billing_postcode': String(snapshot.billing_postcode || ''),
      '#billing_address_1': String(snapshot.billing_address_1 || ''),
      '#billing_address_2': String(snapshot.billing_address_2 || ''),
      '#shipping_first_name': String(snapshot.shipping_first_name || ''),
      '#shipping_last_name': String(snapshot.shipping_last_name || ''),
      '#shipping_phone': String(snapshot.shipping_phone || ''),
      '#shipping_country': String(snapshot.shipping_country || 'RO') || 'RO',
      '#shipping_state': String(snapshot.shipping_state || snapshot.billing_state || ''),
      '#shipping_city': String(snapshot.shipping_city || snapshot.billing_city || ''),
      '#shipping_postcode': String(snapshot.shipping_postcode || snapshot.billing_postcode || ''),
      '#shipping_address_1': String(snapshot.shipping_address_1 || snapshot.billing_address_1 || ''),
      '#shipping_address_2': String(snapshot.shipping_address_2 || snapshot.billing_address_2 || ''),
      '#order_comments': String(snapshot.order_comments || ''),
    };
  };

  const hasMeaningfulAuthShippingSnapshot = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return false;
    }

    return Boolean(
      String(snapshot['#billing_first_name'] || '').trim()
      || String(snapshot.billing_first_name || '').trim()
      || String(snapshot['#billing_last_name'] || '').trim()
      || String(snapshot.billing_last_name || '').trim()
      || String(snapshot['#billing_phone'] || '').trim()
      || String(snapshot.billing_phone || '').trim()
      || String(snapshot['#billing_email'] || '').trim()
      || String(snapshot.billing_email || '').trim()
      || String(snapshot['#shipping_state'] || '').trim()
      || String(snapshot.shipping_state || '').trim()
      || String(snapshot.billing_state || '').trim()
      || String(snapshot['#shipping_city'] || '').trim()
      || String(snapshot.shipping_city || '').trim()
      || String(snapshot.billing_city || '').trim()
      || String(snapshot['#shipping_address_1'] || '').trim()
      || String(snapshot.shipping_address_1 || '').trim()
      || String(snapshot.billing_address_1 || '').trim()
      || String(snapshot.postcode || '').trim()
      || String(snapshot['#shipping_postcode'] || '').trim()
      || String(snapshot.shipping_postcode || '').trim()
      || String(snapshot.billing_postcode || '').trim()
      || String(snapshot['#order_comments'] || '').trim()
      || String(snapshot.order_comments || '').trim()
    );
  };

  const getAuthShippingInitialSnapshot = () => {
    if (hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot)) {
      return authCurrentOrderSnapshot;
    }

    if (hasMeaningfulAuthShippingSnapshot(authShippingDraft)) {
      return authShippingDraft;
    }

    if (hasMeaningfulAuthShippingSnapshot(authShippingEditSnapshotCache)) {
      return authShippingEditSnapshotCache;
    }

    const standardSnapshot = getCheckoutStandardAddressSnapshot();
    if (hasMeaningfulAuthShippingSnapshot(standardSnapshot)) {
      return standardSnapshot;
    }

    return captureAuthShippingSnapshot();
  };

  const hydrateAuthShippingFieldsFromSnapshot = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return;
    }

    debugCheckout('hydrateAuthShippingFieldsFromSnapshot', snapshot);
    const shippingStateValue = String(snapshot['#shipping_state'] || snapshot['#billing_state'] || '').trim();
    const shippingCityValue = String(snapshot['#shipping_city'] || snapshot['#billing_city'] || '').trim();
    const shippingAddress1Value = String(snapshot['#shipping_address_1'] || snapshot['#billing_address_1'] || '').trim();
    const shippingPostcodeValue = String(
      snapshot.postcode
      || snapshot['#shipping_postcode']
      || snapshot.shipping_postcode
      || snapshot['#billing_postcode']
      || snapshot.billing_postcode
      || ''
    ).trim();

    runProgrammaticFieldSync(() => {
      setCheckoutFieldValue(getFieldBySelector('#billing_first_name'), String(snapshot['#billing_first_name'] || '').trim());
      setCheckoutFieldValue(getFieldBySelector('#billing_last_name'), String(snapshot['#billing_last_name'] || '').trim());
      setCheckoutFieldValue(getFieldBySelector('#billing_phone'), String(snapshot['#billing_phone'] || '').trim());
      setCheckoutFieldValue(getFieldBySelector('#billing_email'), String(snapshot['#billing_email'] || '').trim());
      setSelectFieldByValueOrLabel(getFieldBySelector('#billing_state'), shippingStateValue, true);
      setSelectFieldByValueOrLabel(getFieldBySelector('#billing_city'), shippingCityValue, true);
      setCheckoutFieldValue(getFieldBySelector('#billing_address_1'), shippingAddress1Value);
      setCheckoutFieldValue(getFieldBySelector('#billing_postcode'), shippingPostcodeValue);
      setSelectFieldByValueOrLabel(getFieldBySelector('#billing_country'), String(snapshot['#billing_country'] || 'RO').trim() || 'RO', true);
      setSelectFieldByValueOrLabel(getFieldBySelector('#shipping_country'), String(snapshot['#shipping_country'] || 'RO').trim() || 'RO', true);
      setCheckoutFieldValue(getFieldBySelector('#shipping_address_1'), shippingAddress1Value);
      setCheckoutFieldValue(getFieldBySelector('#shipping_address_2'), String(snapshot['#shipping_address_2'] || '').trim());
      setCheckoutFieldValue(getFieldBySelector('#shipping_postcode'), shippingPostcodeValue);
      setCheckoutFieldValue(getFieldBySelector('#order_comments'), String(snapshot['#order_comments'] || '').trim());

      const $stateField = getFieldBySelector('#shipping_state');
      if ($stateField.length) {
        setSelectFieldByValueOrLabel($stateField, shippingStateValue, true);
        syncDependentCitySelect('#shipping_state', false);
      }

      const $cityField = getFieldBySelector('#shipping_city');
      if ($cityField.length) {
        setSelectFieldByValueOrLabel($cityField, shippingCityValue, true);
      }
    });
  };

  const getAuthShippingSummaryLinesFromSnapshot = (snapshot) => {
    const data = normalizeCheckoutPostcodeSnapshot(snapshot && typeof snapshot === 'object' ? snapshot : {});
    const firstName = String(data['#billing_first_name'] || '').trim();
    const lastName = String(data['#billing_last_name'] || '').trim();
    const phone = String(data['#billing_phone'] || '').trim();
    const email = String(data['#billing_email'] || '').trim();
    const shippingAddress1 = String(data['#shipping_address_1'] || '').trim();
    const shippingAddress2 = String(data['#shipping_address_2'] || '').trim();
    const shippingCity = String(data['#shipping_city'] || '').trim();
    const shippingState = String(data['#shipping_state'] || '').trim();
    const shippingPostcode = String(data.postcode || data['#shipping_postcode'] || data.shipping_postcode || '').trim();
    const billingAddress1 = String(data['#billing_address_1'] || '').trim();
    const billingAddress2 = String(data['#billing_address_2'] || '').trim();
    const billingCity = String(data['#billing_city'] || '').trim();
    const billingState = String(data['#billing_state'] || '').trim();
    const billingPostcode = String(data.billing_postcode || data['#billing_postcode'] || shippingPostcode || '').trim();
    const orderComments = String(data['#order_comments'] || '').trim();

    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    const streetLine = [shippingAddress1 || billingAddress1, shippingAddress2 || billingAddress2].filter(Boolean).join(', ').trim();
    const cityLine = [shippingCity || billingCity, shippingState || billingState, shippingPostcode || billingPostcode].filter(Boolean).join(', ').trim();

    const lines = [];

    if (fullName) {
      lines.push(fullName);
    }

    if (streetLine) {
      lines.push(streetLine);
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

    if (orderComments) {
      lines.push(`Observații: ${orderComments}`);
    }

    return lines;
  };

  const getAuthShippingSummaryLines = () => {
    if (hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot)) {
      const currentOrderLines = getAuthShippingSummaryLinesFromSnapshot(authCurrentOrderSnapshot);
      if (currentOrderLines.length > 0) {
        return currentOrderLines;
      }
    }

    if (hasMeaningfulAuthShippingSnapshot(authShippingDraft)) {
      const draftLines = getAuthShippingSummaryLinesFromSnapshot(authShippingDraft);
      if (draftLines.length > 0) {
        return draftLines;
      }
    }

    const standardSnapshot = getCheckoutStandardAddressSnapshot();
    if (hasMeaningfulAuthShippingSnapshot(standardSnapshot)) {
      const standardLines = getAuthShippingSummaryLinesFromSnapshot(standardSnapshot);
      if (standardLines.length > 0) {
        return standardLines;
      }
    }

    return getAuthShippingSummaryLinesFromSnapshot(captureAuthShippingSnapshot());
  };

  const getAuthShippingSummarySnapshotFromDom = () => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return null;
    }

    const $summary = $shipping.find(selectors.authShippingSummary).first();
    if (!$summary.length || !$summary.is(':visible')) {
      return null;
    }

    const summaryName = String($summary.find('.pap-checkout-address-card__name').first().text() || '').trim();
    const summaryLines = $summary
      .find('.pap-checkout-address-card__line-text')
      .map(function () {
        return String($(this).text() || '').trim();
      })
      .get()
      .filter(Boolean);

    if (!summaryName && !summaryLines.length) {
      return null;
    }

    const cityParts = String(summaryLines[1] || '').split(',').map((part) => part.trim()).filter(Boolean);
    const summaryCity = cityParts[0] || '';
    const summaryState = cityParts[1] || '';
    const summaryPostcode = cityParts[2] || '';

    return normalizeCheckoutPostcodeSnapshot({
      '#billing_first_name': String(getRawFieldValue('#billing_first_name') || '').trim(),
      '#billing_last_name': String(getRawFieldValue('#billing_last_name') || '').trim(),
      '#billing_phone': String(getRawFieldValue('#billing_phone') || '').trim(),
      '#billing_email': String(getRawFieldValue('#billing_email') || '').trim(),
      '#billing_country': String(getRawFieldValue('#billing_country') || 'RO').trim() || 'RO',
      '#shipping_country': String(getRawFieldValue('#shipping_country') || 'RO').trim() || 'RO',
      '#shipping_address_1': String(summaryLines[0] || getRawFieldValue('#shipping_address_1') || '').trim(),
      '#shipping_address_2': String(getRawFieldValue('#shipping_address_2') || '').trim(),
      '#shipping_city': summaryCity || String(getRawFieldValue('#shipping_city') || '').trim(),
      '#shipping_state': summaryState || String(getRawFieldValue('#shipping_state') || '').trim(),
      postcode: summaryPostcode || String(getRawFieldValue('#shipping_postcode') || getRawFieldValue('#billing_postcode') || '').trim(),
      '#shipping_postcode': summaryPostcode || String(getRawFieldValue('#shipping_postcode') || getRawFieldValue('#billing_postcode') || '').trim(),
      shipping_postcode: summaryPostcode || String(getRawFieldValue('#shipping_postcode') || getRawFieldValue('#billing_postcode') || '').trim(),
      billing_postcode: summaryPostcode || String(getRawFieldValue('#billing_postcode') || getRawFieldValue('#shipping_postcode') || '').trim(),
      billing_state: summaryState || String(getRawFieldValue('#billing_state') || '').trim(),
      shipping_state: summaryState || String(getRawFieldValue('#shipping_state') || '').trim(),
      billing_city: summaryCity || String(getRawFieldValue('#billing_city') || '').trim(),
      shipping_city: summaryCity || String(getRawFieldValue('#shipping_city') || '').trim(),
      billing_address_1: String(getRawFieldValue('#billing_address_1') || summaryLines[0] || '').trim(),
      shipping_address_1: String(getRawFieldValue('#shipping_address_1') || summaryLines[0] || '').trim(),
      '#order_comments': String(getRawFieldValue('#order_comments') || '').trim(),
      order_comments: String(getRawFieldValue('#order_comments') || '').trim(),
    });
  };

  const normalizeSelectMatchValue = (value) => {
    const text = String(value || '').trim().toLowerCase();
    if (!text) {
      return '';
    }

    try {
      return text
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    } catch (error) {
      return text.replace(/\s+/g, ' ').trim();
    }
  };

  const findSelectOptionValueByMatch = ($field, targetLabel) => {
    const normalizedTarget = normalizeSelectMatchValue(targetLabel);
    if (!$field.length || !normalizedTarget) {
      return '';
    }

    let matchedValue = '';
    $field.find('option').each(function () {
      const $option = $(this);
      const optionValue = String($option.val() || '').trim();
      const optionLabel = String($option.text() || '').trim();
      if (
        normalizeSelectMatchValue(optionValue) === normalizedTarget
        || normalizeSelectMatchValue(optionLabel) === normalizedTarget
      ) {
        matchedValue = optionValue;
        return false;
      }
      return true;
    });

    return matchedValue;
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
    setCheckoutFieldValue($field, findSelectOptionValueByMatch($field, targetLabel));
  };

  const setSelectFieldByValueOrLabel = ($field, rawValue, triggerChange = false) => {
    if (!$field.length) {
      return;
    }

    const targetValue = String(rawValue || '').trim();
    if (!targetValue) {
      setCheckoutFieldValue($field, '');
      return;
    }

    setCheckoutFieldValue($field, findSelectOptionValueByMatch($field, targetValue));

    if (triggerChange) {
      runProgrammaticFieldSync(() => {
        $field.trigger('change');
      });
    }
  };

  const hydrateGuestShippingFields = (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') {
      return;
    }

    debugCheckout('hydrateGuestShippingFields', snapshot);
    const firstName = String(snapshot['#billing_first_name'] || '').trim();
    const lastName = String(snapshot['#billing_last_name'] || '').trim();
    const phone = String(snapshot['#billing_phone'] || '').trim();
    const email = String(snapshot['#billing_email'] || '').trim();
    const address1 = String(snapshot['#shipping_address_1'] || '').trim();
    const countyLabel = String(snapshot['#shipping_state'] || '').trim();
    const cityLabel = String(snapshot['#shipping_city'] || '').trim();
    const postcode = String(
      snapshot.postcode
      || snapshot['#shipping_postcode']
      || snapshot.shipping_postcode
      || snapshot['#billing_postcode']
      || snapshot.billing_postcode
      || ''
    ).trim();
    const orderComments = String(snapshot['#order_comments'] || '').trim();

    setCheckoutFieldValue(getFieldBySelector('#billing_first_name'), firstName);
    setCheckoutFieldValue(getFieldBySelector('#billing_last_name'), lastName);
    setCheckoutFieldValue(getFieldBySelector('#billing_phone'), phone);
    setCheckoutFieldValue(getFieldBySelector('#billing_email'), email);
    setSelectFieldByLabel(getFieldBySelector('#billing_state'), countyLabel, true);
    syncDependentCitySelect('#billing_state', false);
    setSelectFieldByLabel(getFieldBySelector('#billing_city'), cityLabel, true);
    setCheckoutFieldValue(getFieldBySelector('#billing_address_1'), address1);
    setCheckoutFieldValue(getFieldBySelector('#shipping_address_1'), address1);
    setCheckoutFieldValue(getFieldBySelector('#shipping_postcode'), postcode);
    setCheckoutFieldValue(getFieldBySelector('#billing_postcode'), postcode);
    setSelectFieldByLabel(getFieldBySelector('#shipping_state'), countyLabel);
    syncDependentCitySelect('#shipping_state', false);
    setSelectFieldByLabel(getFieldBySelector('#shipping_city'), cityLabel);
    setCheckoutFieldValue(getFieldBySelector('#order_comments'), orderComments);
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
      || getGuestShippingFieldValue('#order_comments')
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
    const orderComments = getGuestShippingFieldValue('#order_comments', useCache ? 'cache' : 'dom');

    const lines = [];
    const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();
    const streetLine = [address1, address2].filter(Boolean).join(', ').trim();
    const locationLine = [city, state, postcode].filter(Boolean).join(', ').trim();

    if (fullName) {
      lines.push(fullName);
    }

    if (streetLine) {
      lines.push(streetLine);
    }

    if (locationLine) {
      lines.push(locationLine);
    }

    if (phone) {
      lines.push(phone);
    }

    if (email) {
      lines.push(email);
    }

    if (orderComments) {
      lines.push(`Observații: ${orderComments}`);
    }

    return lines;
  };

  const getAddressIconSvg = (kind) => {
    const icons = {
      user: `
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true" focusable="false">
          <path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"></circle>
        </svg>
      `,
      location: `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
          <circle cx="12" cy="10" r="2" stroke="currentColor" stroke-width="1.8"></circle>
        </svg>
      `,
      city: `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M4 20V9l6-3v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M10 20V5l6 3v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M16 20v-7l4-2v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M2.5 20h19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
        </svg>
      `,
      phone: `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M6.6 3.6l2.1 4.2c.3.6.2 1.2-.3 1.7l-1 1c1.2 2.4 3.1 4.3 5.5 5.5l1-1c.5-.5 1.1-.6 1.7-.3l4.2 2.1c.6.3.9.9.8 1.5l-.4 2c-.1.6-.7 1.1-1.3 1.1C10 21.4 2.6 14 2.6 5.1c0-.6.5-1.2 1.1-1.3l2-.4c.4-.1.8 0 .9.2z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      `,
      envelope: `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <rect x="4" y="6" width="16" height="12" rx="1.5" stroke="currentColor" stroke-width="1.8"></rect>
          <path d="M5 8l7 5 7-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      `,
      note: `
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
          <path d="M5 4.5h14a1.5 1.5 0 0 1 1.5 1.5v8.5A1.5 1.5 0 0 1 19 16H9l-4 4v-4H5A1.5 1.5 0 0 1 3.5 14V6A1.5 1.5 0 0 1 5 4.5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M7 8h10M7 11h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
        </svg>
      `,
    };

    return icons[kind] || icons.location;
  };

  const getSummaryRowIconKind = (index) => {
    if (index === 0) {
      return 'location';
    }

    if (index === 1) {
      return 'city';
    }

    if (index === 2) {
      return 'phone';
    }

    if (index === 3) {
      return 'envelope';
    }

    return 'note';
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
    const fullName = lines.shift() || '';
    const $body = $summary.find('.pap-checkout-address-card__body').first();
    const $empty = $summary.find('.pap-checkout-address-card__empty').first();
    const $head = $summary.find('.pap-checkout-address-card__head').first();
    const $titleCopy = $summary.find('.pap-checkout-address-card__title-copy').first();
    const $name = $summary.find('.pap-checkout-address-card__name').first();
    const $action = $summary.find('.pap-secondary-action').first();

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

    if ($titleCopy.length && !$name.length) {
      $titleCopy.empty();
      $('<span>', {
        class: 'pap-checkout-address-card__user-icon',
        html: getAddressIconSvg('user'),
        'aria-hidden': 'true',
      }).appendTo($titleCopy);
      $('<p>', {
        class: 'pap-checkout-address-card__title',
        text: 'Adresa de livrare',
      }).appendTo($titleCopy);
      $('<p>', {
        class: 'pap-checkout-address-card__name',
        text: fullName || '',
      }).appendTo($titleCopy);
    } else if ($name.length) {
      $name.text(fullName || '');
    }
    if ($head.length) {
      $head.toggleClass('has-action', !!$action.length);
    }

    if ($empty.length) {
      $empty.remove();
    }

    let $targetBody = $summary.find('.pap-checkout-address-card__body').first();
    if (!$targetBody.length) {
      $targetBody = $('<div class="pap-checkout-address-card__body"></div>').appendTo($summary.find('.pap-checkout-address-card').first());
    }

    $targetBody.empty();
    lines.forEach((text, index) => {
      if (!text) {
        return;
      }

      const $row = $('<p>', { class: 'pap-checkout-address-card__line address-summary-row' });
      $('<span>', {
        class: 'pap-checkout-address-card__icon address-summary-icon',
        html: getAddressIconSvg(getSummaryRowIconKind(index)),
        'aria-hidden': 'true',
      }).appendTo($row);
      $('<span>', { class: 'pap-checkout-address-card__line-text', text }).appendTo($row);
      $row.appendTo($targetBody);
    });
  };

  const setVisibilityState = ($element, visible, displayValue) => {
    if (!$element.length) {
      return;
    }

    $element.prop('hidden', !visible).attr('aria-hidden', visible ? 'false' : 'true');
    const element = $element.get(0);
    if (element && element.style && typeof element.style.setProperty === 'function') {
      element.style.setProperty('display', visible ? (displayValue || '') : 'none', 'important');
    } else {
      $element.css('display', visible ? (displayValue || '') : 'none');
    }
  };

  const setGuestShippingMode = (mode, persist = true, options = {}) => {
    if (isLoggedIn) {
      return;
    }

    const normalizedMode = mode === 'summary' ? 'summary' : 'edit';
    const shouldHydrate = options && Object.prototype.hasOwnProperty.call(options, 'hydrate') ? options.hydrate !== false : true;
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

    if (normalizedMode === 'edit' && shouldHydrate) {
      hydrateGuestShippingFields(normalizeCheckoutPostcodeSnapshot(getGuestShippingSnapshot() || guestShippingSummaryCache));
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
      '#shipping_postcode',
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
      setGuestShippingMode('edit', false, { hydrate: false });
      const offset = Math.max(firstInvalid.closest(selectors.formRow).offset().top - 120, 0);
      $('html, body').animate({ scrollTop: offset }, 300);
      focusField(firstInvalid);
    }

    return !hasErrors;
  };

  const validateAuthShippingFields = (forceDisplay = false) => {
    const selectorsToValidate = [
      '#billing_first_name',
      '#billing_last_name',
      '#billing_phone',
      '#billing_email',
      '#shipping_state',
      '#shipping_city',
      '#shipping_address_1',
      '#shipping_postcode',
    ];

    let firstInvalid = null;
    let hasErrors = false;

    selectorsToValidate.forEach((selector) => {
      const $field = getFieldBySelector(selector);
      if (!$field.length || $field.is(':disabled')) {
        return;
      }

      if (!validateField($field, forceDisplay)) {
        hasErrors = true;
        firstInvalid = firstInvalid || $field;
      }
    });

    if (hasErrors && forceDisplay && firstInvalid && firstInvalid.length) {
      const offset = Math.max(firstInvalid.closest(selectors.formRow).offset().top - 120, 0);
      $('html, body').animate({ scrollTop: offset }, 300);
      focusField(firstInvalid);
    }

    return !hasErrors;
  };

  const getAuthShipping = () => getForm().find(selectors.authShipping).first();

  const setAuthAddressNotice = (message = '') => {
    const hasMessage = Boolean(String(message || '').trim());

    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    let $notice = $shipping.find(selectors.authAddressNotice).first();

    if (!hasMessage) {
      if ($notice.length) {
        $notice.remove();
      }
      return;
    }

    if (!$notice.length) {
      const noticeHtml = [
        '<div class="pap-checkout-auth-shipping__notice pap-auth-notice wc-block-components-notice-banner is-error pap-auth-notice--error" data-pap-auth-address-notice role="alert" aria-live="assertive" aria-atomic="true">',
        '  <span class="pap-auth-notice-icon wc-block-components-notice-banner__icon" aria-hidden="true">',
        '    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20Zm1 5v7h-2V7h2Zm0 9v2h-2v-2h2Z" fill="currentColor"/></svg>',
        '  </span>',
        '  <div class="pap-auth-notice-copy wc-block-components-notice-banner__content" data-pap-auth-address-notice-copy></div>',
        '</div>',
      ].join('');

      const $form = $shipping.find(selectors.authShippingForm).first();
      if ($form.length) {
        $notice = $(noticeHtml);
        $form.prepend($notice);
      }
    }

    const $copy = $notice.find(selectors.authAddressNoticeCopy).first();
    if ($copy.length) {
      $copy.text(message);
    } else {
      $notice.text(message);
    }

    setVisibilityState($notice, true, 'flex');

    window.requestAnimationFrame(() => {
      const element = $notice.get(0);
      if (element && typeof element.scrollIntoView === 'function') {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  };

  const setAuthShippingBusy = (isBusy) => {
    authShippingBusy = Boolean(isBusy);

    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    $shipping.toggleClass('is-loading', authShippingBusy);
    $shipping.attr('aria-busy', authShippingBusy ? 'true' : 'false');
  };

  const syncAuthShippingActionWidths = () => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    const $actions = $shipping.find(selectors.authShippingActions).first();
    if (!$actions.length) {
      return;
    }

    if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) {
      $actions.css('--pap-auth-shipping-action-width', '');
      return;
    }

    const $buttons = $actions.find('.pap-checkout-action').filter(':visible');
    if ($buttons.length < 2) {
      $actions.css('--pap-auth-shipping-action-width', '');
      return;
    }

    let maxWidth = 0;
    $buttons.each(function () {
      const previousWidth = this.style.width;
      const previousMinWidth = this.style.minWidth;
      this.style.width = 'auto';
      this.style.minWidth = '0';
      const width = Math.ceil(this.getBoundingClientRect().width);
      maxWidth = Math.max(maxWidth, width);
      this.style.width = previousWidth;
      this.style.minWidth = previousMinWidth;
    });

    if (maxWidth > 0) {
      $actions.css('--pap-auth-shipping-action-width', `${maxWidth}px`);
    }
  };

  const applyAuthAddressToForm = (address) => {
    if (!address) {
      return;
    }

    debugCheckout('apply auth address to form', address);
    runProgrammaticFieldSync(() => {
      setCheckoutFieldValue(getFieldBySelector('#billing_first_name'), address.first_name || '');
      setCheckoutFieldValue(getFieldBySelector('#billing_last_name'), address.last_name || '');
      setCheckoutFieldValue(getFieldBySelector('#billing_phone'), address.phone || '');
      setCheckoutFieldValue(getFieldBySelector('#billing_email'), address.email || checkoutData.customerEmail || '');
      setSelectFieldByValueOrLabel(getFieldBySelector('#billing_state'), address.state || '', true);
      setSelectFieldByValueOrLabel(getFieldBySelector('#billing_city'), address.city || '', true);
      setCheckoutFieldValue(getFieldBySelector('#billing_postcode'), address.postcode || '');
      applySavedAddressToFields('shipping', address);
    });
  };

  const clearAuthAddressForm = () => {
    [
      '#billing_first_name',
      '#billing_last_name',
      '#billing_phone',
      '#billing_postcode',
      '#shipping_state',
      '#shipping_city',
      '#shipping_address_1',
      '#shipping_postcode',
    ].forEach((selector) => {
      setCheckoutFieldValue(getFieldBySelector(selector), '');
    });

    setCheckoutFieldValue(getFieldBySelector('#billing_email'), checkoutData.customerEmail || '');
    syncDependentCitySelect('#shipping_state', false);
  };

  const syncAuthShippingSummary = () => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    const $summary = $shipping.find(selectors.authShippingSummary).first();
    if (!$summary.length) {
      return;
    }

    const lines = getAuthShippingSummaryLines();
    const fullName = lines.shift() || '';
    const $body = $summary.find('.pap-checkout-address-card__body').first();
    const $empty = $summary.find('.pap-checkout-address-card__empty').first();
    const $head = $summary.find('.pap-checkout-address-card__head').first();
    const $titleCopy = $summary.find('.pap-checkout-address-card__title-copy').first();
    const $name = $summary.find('.pap-checkout-address-card__name').first();
    const $action = $summary.find('.pap-secondary-action').first();

    if (!lines.length) {
      authTemporarySummaryVisible = false;
      if ($body.length) {
        $body.remove();
      }
      if ($empty.length) {
        $empty.find('strong').first().text('Nu ai completat încă această adresă.');
        $empty.find('p').first().text('Deschide formularul ca sa completezi datele necesare pentru comanda.');
      }
      setAuthShippingFormVisible(true, {
        clear: false,
        mode: 'new',
      });
      return;
    }

    if ($titleCopy.length && !$name.length) {
      $titleCopy.empty();
      $('<span>', {
        class: 'pap-checkout-address-card__user-icon',
        html: getAddressIconSvg('user'),
        'aria-hidden': 'true',
      }).appendTo($titleCopy);
      $('<p>', {
        class: 'pap-checkout-address-card__title',
        text: 'Adresa de livrare',
      }).appendTo($titleCopy);
      $('<p>', {
        class: 'pap-checkout-address-card__name',
        text: fullName || '',
      }).appendTo($titleCopy);
    } else if ($name.length) {
      $name.text(fullName || '');
    }

    if ($head.length) {
      $head.toggleClass('has-action', !!$action.length);
    }

    if ($empty.length) {
      $empty.remove();
    }

    let $targetBody = $summary.find('.pap-checkout-address-card__body').first();
    if (!$targetBody.length) {
      $targetBody = $('<div class="pap-checkout-address-card__body"></div>').appendTo($summary.find('.pap-checkout-address-card').first());
    }

    $targetBody.empty();
    lines.forEach((text, index) => {
      if (!text) {
        return;
      }

      const $row = $('<p>', { class: 'pap-checkout-address-card__line address-summary-row' });
      $('<span>', {
        class: 'pap-checkout-address-card__icon address-summary-icon',
        html: getAddressIconSvg(getSummaryRowIconKind(index)),
        'aria-hidden': 'true',
      }).appendTo($row);
      $('<span>', { class: 'pap-checkout-address-card__line-text', text }).appendTo($row);
      $row.appendTo($targetBody);
    });
  };

  const getAuthAddressCount = () => {
    return getAuthShippingSummaryLines().length > 0 ? 1 : 0;
  };

  const setAuthShippingFormVisible = (visible, options = {}) => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    const $form = $shipping.find(selectors.authShippingForm).first();
    const $list = $shipping.find(selectors.authAddressList).first();
    const $summary = $shipping.find(selectors.authShippingSummary).first();
    const hasSummaryData = getAuthShippingSummaryLines().length > 0;
    const shouldClear = options.clear === true;
    const shouldRestore = options.restore === true;
    const showSummary = !visible && (authTemporarySummaryVisible || hasSummaryData);
    const showList = false;

    if (visible) {
      authAddressFormMode = options.mode || 'new';
      authTemporarySummaryVisible = false;
      const initialSnapshot = normalizeCheckoutPostcodeSnapshot(getAuthShippingInitialSnapshot());
      authShippingEditSnapshotCache = initialSnapshot;
      authShippingDraft = initialSnapshot;
      setCookieValue('pap_checkout_shipping_mode', 'edit');
      if (shouldClear) {
        clearAuthAddressForm();
        authShippingDraft = captureAuthShippingSnapshot();
      } else if (initialSnapshot) {
        hydrateAuthShippingFieldsFromSnapshot(initialSnapshot);
        authShippingDraft = captureAuthShippingSnapshot();
      }
      setCheckboxFieldState(getFieldBySelector(selectors.authAddressSaveCheckbox), false);
      setAuthAddressNotice('');
    } else {
      authAddressFormMode = '';
      if (shouldRestore && authShippingEditSnapshotCache) {
        hydrateAuthShippingFieldsFromSnapshot(authShippingEditSnapshotCache);
        authShippingDraft = authShippingEditSnapshotCache;
      }
      if (showSummary) {
        setCookieValue('pap_checkout_shipping_mode', 'summary');
      }
    }

    $shipping.toggleClass('is-summary-mode', showSummary);
    setVisibilityState($form, visible, 'grid');
    setVisibilityState($list, showList, 'grid');
    setVisibilityState($summary, showSummary, 'grid');
    setVisibilityState($shipping.find(selectors.authAddressCancel).first(), visible && (authTemporarySummaryVisible || hasSummaryData), 'inline-flex');
    $shipping.attr('data-pap-auth-temporary-mode', showSummary ? 'summary' : (visible ? 'form' : 'list'));

    if (showSummary && !shouldRestore) {
      syncAuthShippingSummary();
    }

    syncAuthShippingActionWidths();
  };

  const renderAuthAddressCard = (address, email, isSelected = true) => {
    const fullName = [address.first_name, address.last_name].filter(Boolean).join(' ').trim();
    const stateField = getFieldBySelector('#shipping_state');
    const stateLabel = (() => {
      const targetState = String(address.state || '').trim();
      if (!stateField.length || !targetState) {
        return targetState || '';
      }

      const matchedValue = findSelectOptionValueByMatch(stateField, targetState);
      if (!matchedValue) {
        return targetState;
      }

      const $matchedOption = stateField.find(`option[value="${matchedValue.replace(/"/g, '\\"')}"]`).first();
      return String($matchedOption.length ? $matchedOption.text() : targetState).trim() || targetState;
    })();
    const streetLine = [address.address_1, address.address_2].filter(Boolean).join(', ').trim();
    const locationLine = [address.city, stateLabel, address.postcode].filter(Boolean).join(', ').trim();
    const hasMultipleAddresses = getAuthAddressCount() > 1;

    const $option = $('<button>', {
      type: 'button',
      class: `pap-checkout-address-option${isSelected && hasMultipleAddresses ? ' is-selected' : ''}${hasMultipleAddresses ? ' is-selectable' : ' is-static'}`,
      'data-pap-auth-address-option': '',
      'data-checkout-address-selector': '',
      'data-checkout-address-prefix': 'shipping',
      'data-checkout-address-id': address.id,
    });
    if (hasMultipleAddresses) {
      $option.attr('aria-pressed', isSelected ? 'true' : 'false');
    }

    const $card = $('<div>', { class: 'pap-checkout-address-card' }).appendTo($option);
    const $head = $('<div>', { class: 'pap-checkout-address-card__head' }).appendTo($card);
    const $copy = $('<div>', { class: 'pap-checkout-address-card__title-copy pap-checkout-address-card__title-copy--with-icon' }).appendTo($head);
    $('<span>', {
      class: 'pap-checkout-address-card__user-icon',
      html: getAddressIconSvg('user'),
      'aria-hidden': 'true',
    }).appendTo($copy);
    $('<p>', { class: 'pap-checkout-address-card__title', text: 'Adresa de livrare' }).appendTo($copy);
    $('<p>', { class: 'pap-checkout-address-card__name', text: fullName }).appendTo($copy);

    const $body = $('<div>', { class: 'pap-checkout-address-card__body' }).appendTo($card);
    [
      { text: streetLine, icon: 'location' },
      { text: locationLine, icon: 'city' },
      { text: address.phone, icon: 'phone' },
      { text: email, icon: 'envelope' },
    ].forEach(({ icon, text }) => {
      if (!text) {
        return;
      }

      const $row = $('<p>', { class: 'pap-checkout-address-card__line address-summary-row' }).appendTo($body);
      $('<span>', {
        class: 'pap-checkout-address-card__icon address-summary-icon',
        html: getAddressIconSvg(icon),
        'aria-hidden': 'true',
      }).appendTo($row);
      $('<span>', { class: 'pap-checkout-address-card__line-text', text }).appendTo($row);
    });

    return $option;
  };

  const syncSelectedAddressCardState = (addressId) => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    if (getAuthAddressCount() <= 1) {
      return;
    }

    const $options = $shipping.find(selectors.authAddressOption);
    $options.removeClass('is-selected');
    $options.attr('aria-pressed', 'false');

    const $selected = $shipping.find(`[data-checkout-address-id="${String(addressId).replace(/"/g, '\\"')}"]`).first();
    if ($selected.length) {
      $selected.addClass('is-selected').attr('aria-pressed', 'true');
    }
  };

  const selectAuthAddress = (addressId, options = {}) => {
    const address = getAddressById(addressId);
    if (!address) {
      return;
    }

    if (getAuthAddressCount() <= 1) {
      return;
    }

    const shouldPersist = options.persist !== false;
    const shouldUpdateCheckout = options.updateCheckout !== false;

    authTemporarySummaryVisible = false;
    setAuthAddressNotice('');
    syncSelectedAddressCardState(addressId);
    applyAuthAddressToForm(address);
    authShippingDraft = captureAuthShippingSnapshot();
    setAuthCurrentOrderSnapshot(authShippingDraft, 'selectAuthAddress');

    if (shouldPersist && checkoutData.ajaxUrl && checkoutData.selectAddressNonce) {
      setAuthShippingBusy(true);
      $.post(checkoutData.ajaxUrl, {
        action: checkoutData.selectAddressAction || 'papetarie_storefront_checkout_select_address',
        nonce: checkoutData.selectAddressNonce,
        address_id: addressId,
      }).done(() => {
        setAuthShippingBusy(false);
        if (shouldUpdateCheckout) {
          $(document.body).trigger('update_checkout');
        }
      }).fail(() => {
        setAuthShippingBusy(false);
        setAuthAddressNotice('Adresa nu a putut fi selectată. Încearcă din nou.');
      });
      return;
    }

    setAuthShippingBusy(false);
    if (shouldUpdateCheckout) {
      $(document.body).trigger('update_checkout');
    }
  };

  const syncAuthSelectedAddressFields = () => {
    const $shipping = getAuthShipping();
    if (!$shipping.length) {
      return;
    }

    if (authAddressFormMode) {
      if (!$shipping.find(`${selectors.authShippingForm}:visible`).length) {
        setAuthShippingFormVisible(true, {
          clear: false,
          mode: authAddressFormMode,
        });
      } else if (authShippingDraft) {
        hydrateAuthShippingFieldsFromSnapshot(normalizeCheckoutPostcodeSnapshot(authShippingDraft));
      }
      return;
    }

    if (authTemporarySummaryVisible) {
      setAuthShippingFormVisible(false);
      return;
    }

    const selectedId = String(
      $shipping.find('[data-pap-auth-address-option].is-selected[data-checkout-address-id]').first().attr('data-checkout-address-id')
      || selectedShippingAddressId
      || ''
    );
    const address = getAddressById(selectedId);
    if (address) {
      applyAuthAddressToForm(address);
      authShippingDraft = normalizeCheckoutPostcodeSnapshot(captureAuthShippingSnapshot());
      setAuthCurrentOrderSnapshot(authShippingDraft, 'syncAuthSelectedAddressFields');
    }
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

  const hydrateSubmitAddressState = () => {
    if (isLoggedIn) {
      if (hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot)) {
        debugCheckout('hydrate submit from auth current order snapshot', authCurrentOrderSnapshot);
        hydrateAuthShippingFieldsFromSnapshot(authCurrentOrderSnapshot);
        return;
      }

      if (hasMeaningfulAuthShippingSnapshot(authShippingDraft)) {
        debugCheckout('hydrate submit from auth draft', authShippingDraft);
        hydrateAuthShippingFieldsFromSnapshot(authShippingDraft);
        return;
      }

      if (hasMeaningfulAuthShippingSnapshot(authShippingEditSnapshotCache)) {
        debugCheckout('hydrate submit from auth edit cache', authShippingEditSnapshotCache);
        hydrateAuthShippingFieldsFromSnapshot(authShippingEditSnapshotCache);
        return;
      }

      const summarySnapshot = getAuthShippingSummarySnapshotFromDom();
      if (hasMeaningfulAuthShippingSnapshot(summarySnapshot)) {
        debugCheckout('hydrate submit from auth summary dom snapshot', summarySnapshot);
        authShippingDraft = summarySnapshot;
        authCurrentOrderSnapshot = summarySnapshot;
        hydrateAuthShippingFieldsFromSnapshot(summarySnapshot);
      }

      return;
    }

    const guestSnapshot = normalizeCheckoutPostcodeSnapshot(getGuestShippingSnapshot() || guestShippingSummaryCache || captureGuestShippingSummaryCache());
    if (hasGuestShippingSnapshotData(guestSnapshot)) {
      debugCheckout('hydrate submit from guest snapshot', guestSnapshot);
      hydrateGuestShippingFields(guestSnapshot);
    }
  };

  const bindFieldValidation = () => {
    const $form = getForm();

    $form.on('blur', selectors.field, function () {
      const $field = $(this);
      const fieldSelector = `#${$field.attr('id') || ''}`;
      const isGuestAddressField = !isLoggedIn && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);
      const isAuthAddressField = authAddressFormMode && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#billing_postcode', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (postcodeSelectors.has(fieldSelector)) {
        debugCheckout('postcode blur', {
          field: fieldSelector,
          value: String($field.val() || '').trim(),
          state: getPostcodeDebugState(),
        });
      }

      if (getForm().data('papSubmitted') !== true) {
        window.setTimeout(() => {
          if (getForm().data('papSubmitted') !== true) {
            clearNativeInvalidState($field);
          }
        }, 0);
      }

      if (isAuthAddressField && !isProgrammaticFieldSync) {
        captureAuthShippingDraft(`blur:${fieldSelector}`);
      }

      if (isGuestAddressField && !isProgrammaticFieldSync) {
        captureAndPersistGuestShippingSummaryCache();
      }
    });

    $form.on('change', selectors.field, function () {
      const $field = $(this);
      const fieldSelector = `#${$field.attr('id') || ''}`;
      const isGuestAddressField = !isLoggedIn && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);
      const isAuthAddressField = authAddressFormMode && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#billing_postcode', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (postcodeSelectors.has(fieldSelector)) {
        debugCheckout('postcode change', {
          field: fieldSelector,
          value: String($field.val() || '').trim(),
          state: getPostcodeDebugState(),
        });
      }

      if (isAuthAddressField && !isProgrammaticFieldSync) {
        captureAuthShippingDraft(`change:${fieldSelector}`);
      }

      if (isGuestAddressField && !isProgrammaticFieldSync) {
        captureAndPersistGuestShippingSummaryCache();
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
      const fieldSelector = `#${$field.attr('id') || ''}`;
      const isGuestAddressField = !isLoggedIn && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);
      const isAuthAddressField = authAddressFormMode && ['#billing_first_name', '#billing_last_name', '#billing_phone', '#billing_email', '#billing_postcode', '#shipping_state', '#shipping_city', '#shipping_address_1', '#shipping_postcode', '#order_comments', '#shipping_country', '#billing_country'].includes(fieldSelector);

      if (isProgrammaticFieldSync && isAddressCityField($field)) {
        return;
      }

      if (postcodeSelectors.has(fieldSelector)) {
        debugCheckout('postcode input bubble', {
          field: fieldSelector,
          value: String($field.val() || '').trim(),
          state: getPostcodeDebugState(),
        });
      }

      if (isAuthAddressField && !isProgrammaticFieldSync) {
        captureAuthShippingDraft(`input:${fieldSelector}`);
      }

      if (isGuestAddressField && !isProgrammaticFieldSync) {
        captureAndPersistGuestShippingSummaryCache();
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

    $form.on('change', 'select[data-checkout-address-selector]', function () {
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

    $form.on('click', '[data-pap-auth-address-option][data-checkout-address-id]', function (event) {
      const $option = $(this);
      if ($option.is('button')) {
        event.preventDefault();
      }

      const prefix = String($option.attr('data-checkout-address-prefix') || $option.data('checkoutAddressPrefix') || 'shipping');
      const addressId = String($option.attr('data-checkout-address-id') || '');

      if (prefix === 'shipping') {
        selectAuthAddress(addressId);
        return;
      }

      if (!prefix || !addressId || !applySavedAddressSelection(prefix, addressId)) {
        return;
      }
    });

    $form.on('change', '#billing_state, #shipping_state', function () {
      const $field = $(this);
      syncDependentCitySelect(`#${$field.attr('id')}`);
      if (authAddressFormMode && !isProgrammaticFieldSync) {
        captureAuthShippingDraft(`state-change:${$field.attr('id') || ''}`);
      }
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

      if (authAddressFormMode && !isProgrammaticFieldSync) {
        captureAuthShippingDraft(`city-change:${$field.attr('id') || ''}`);
      }

      clearNativeInvalidState($field);
    });

    $form.on('change', 'input[name^="shipping_method"], input[name="payment_method"]', function () {
      syncPaymentMethodCards();
      syncCheckoutSubmitState();
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

    $form.on('click', selectors.authAddressAdd, function (event) {
      event.preventDefault();
      setAuthShippingFormVisible(true, {
        clear: true,
        mode: 'new',
      });
      focusField(getFieldBySelector('#billing_first_name'));
    });

    $form.on('click', selectors.authTemporaryEdit, function (event) {
      event.preventDefault();
      debugCheckout('click modify auth summary');
      setAuthShippingFormVisible(true, {
        clear: false,
        mode: 'temporary',
      });
      if (!authShippingDraft) {
        authShippingDraft = captureAuthShippingSnapshot();
      }
      focusField(getFieldBySelector('#billing_first_name'));
    });

    $form.on('click', selectors.authAddressCancel, function (event) {
      event.preventDefault();
      setAuthShippingFormVisible(false, {
        restore: true,
      });
      setCheckboxFieldState(getFieldBySelector(selectors.authAddressSaveCheckbox), false);
      setAuthAddressNotice('');
    });

    $form.on('change', selectors.authAddressSaveCheckbox, function () {
      debugCheckout('save address checkbox change', {
        checked: $(this).is(':checked'),
      });
    });

    $form.on('click', selectors.authAddressSave, function (event) {
      event.preventDefault();
      if (!validateAuthShippingFields(true)) {
        return;
      }

      const $button = $(this);
      $button.prop('disabled', true).attr('aria-busy', 'true');
      setAuthShippingBusy(true);
      setAuthAddressNotice('');
      const authShippingSnapshot = normalizeCheckoutPostcodeSnapshot(captureAuthShippingDraft('save-click'));

      $.post(checkoutData.ajaxUrl, {
        action: checkoutData.selectAddressAction || 'papetarie_storefront_checkout_select_address',
        nonce: checkoutData.selectAddressNonce,
        mode: 'temporary',
        pap_auth_shipping_snapshot: JSON.stringify(authShippingSnapshot),
        pap_save_address_for_future: getFieldBySelector(selectors.authAddressSaveCheckbox).is(':checked') ? '1' : '0',
      })
        .done((response) => {
          if (!response || !response.success) {
            setAuthShippingBusy(false);
            setAuthAddressNotice('Adresa nu a putut fi salvată. Încearcă din nou.');
            return;
          }

          authTemporarySummaryVisible = true;
          authShippingEditSnapshotCache = authShippingSnapshot;
          authShippingDraft = authShippingSnapshot;
          setCheckboxFieldState(getFieldBySelector(selectors.authAddressSaveCheckbox), false);
          setAuthCurrentOrderSnapshot(authShippingSnapshot, 'auth-save-success');
          setCookieValue('pap_checkout_shipping_mode', 'summary');
          setAuthAddressNotice('');
          setAuthShippingFormVisible(false);
          setAuthShippingBusy(false);
          $(document.body).trigger('update_checkout');

          const $summary = getAuthShipping().find(selectors.authShippingSummary).first();
          const target = $summary.length ? $summary.get(0) : null;
          if (target && typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        })
        .fail((xhr) => {
          setAuthShippingBusy(false);
          const response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
          const message = response && response.data && response.data.message
            ? response.data.message
            : 'Adresa nu a putut fi salvată. Verifică datele și încearcă din nou.';
          setAuthAddressNotice(message);
        })
        .always(() => {
          $button.prop('disabled', false).removeAttr('aria-busy');
          setAuthShippingBusy(false);
        });
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

        const guestMode = !isLoggedIn ? getGuestShippingMode() : 'logged-in';
        const isSummarySubmit = (!isLoggedIn && guestMode === 'summary')
          || (isLoggedIn && !authAddressFormMode && hasMeaningfulAuthShippingSnapshot(authCurrentOrderSnapshot));

        hydrateSubmitAddressState();

        if (!isSummarySubmit) {
          if (!isLoggedIn && guestMode === 'summary' && !validateGuestShippingFields(true)) {
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
    } else {
      if (authAddressFormMode && authShippingDraft) {
        debugCheckout('syncCheckoutState rehydrate auth draft', authShippingDraft);
        hydrateAuthShippingFieldsFromSnapshot(authShippingDraft);
      }
      if (getAuthAddressCount() > 0) {
        setCookieValue('pap_checkout_shipping_mode', getCookieValue('pap_checkout_shipping_mode') || 'summary');
      } else {
        setCookieValue('pap_checkout_shipping_mode', 'edit');
      }
      resetGuestShippingState();
      syncAuthSelectedAddressFields();

      if (!authAddressFormMode) {
        const $authSummary = getAuthShipping().find(selectors.authShippingSummary).first();
        if ($authSummary.length && $authSummary.is(':visible')) {
          const summarySnapshot = getAuthShippingSummarySnapshotFromDom();
          if (hasMeaningfulAuthShippingSnapshot(summarySnapshot)) {
            setAuthCurrentOrderSnapshot(summarySnapshot, 'syncCheckoutState-summary');
          }
        }
      }
    }
    syncCheckoutStepStates();
    syncDependentCitySelect('#billing_state');
    syncDependentCitySelect('#shipping_state');
    syncProductsLists();
    syncAuthShippingActionWidths();
    debugCheckout('syncCheckoutState end');

  };

  const syncPaymentMethodSelection = () => {
    const $payment = getForm().find('#payment').first();
    if (!$payment.length) {
      return;
    }

    if (String($payment.attr('data-pap-step-state') || '').trim() === 'disabled') {
      syncPaymentMethodCards();
      syncCheckoutSubmitState();
      return;
    }

    const $checked = $payment.find('input[name="payment_method"]:checked').first();
    if ($checked.length) {
      syncPaymentMethodCards();
      syncCheckoutSubmitState();
      return;
    }

    const $visible = $payment.find('input[name="payment_method"]').filter(':visible').first();
    if ($visible.length) {
      $visible.prop('checked', true).trigger('change');
    }

    syncPaymentMethodCards();
    syncCheckoutSubmitState();
  };

  const bootstrap = async () => {
    await loadCityData();
    bindPostcodeInputGuards();
    $(document.body).on('update_checkout', () => {
      debugCheckout('body update_checkout event', getPostcodeDebugState());
    });
    $(document.body).on('updated_checkout', () => {
      syncCheckoutState();
      syncPaymentMethodSelection();
      syncPaymentMethodCards();
      syncCheckoutSubmitState();
    });
    $(document.body).on('updated_checkout', () => {
      debugCheckout('body updated_checkout event', getPostcodeDebugState());
      clearSessionExpiredNotice();
    });
    $(document.body).on('checkout_error', () => {
      setAuthShippingBusy(false);
      debugCheckout('body checkout_error event', getPostcodeDebugState());
    });
    $(window).on('resize', () => {
      syncAuthShippingActionWidths();
    });
    $(bindFieldValidation);
    $(syncCheckoutState);
    syncPaymentMethodSelection();
    syncPaymentMethodCards();
    syncCheckoutSubmitState();
    requestInitialCheckoutRefresh('bootstrap');
    clearSessionExpiredNotice();
    window.setTimeout(clearSessionExpiredNotice, 250);
    window.setTimeout(clearSessionExpiredNotice, 1250);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      bootstrap();
    });
  } else {
    bootstrap();
  }
})(jQuery);
