(function ($) {
  const inlineData = window.papCompanyBookData || {};
  const cityPlaceholder = inlineData.cityPlaceholder || 'Alege localitatea';
  const countyFirstPlaceholder = inlineData.countyFirstPlaceholder || 'Alege județul întâi';
  const deleteConfirm = inlineData.deleteConfirm || 'Sigur vrei să ștergi această firmă?';
  const ajaxUrl = inlineData.ajaxUrl || '';
  const ajaxAction = inlineData.ajaxAction || 'papetarie_storefront_company_book';
  const ajaxNonce = inlineData.ajaxNonce || '';
  const lookupNonce = inlineData.lookupNonce || '';
  const currentMode = inlineData.currentMode || '';
  const currentCompanyId = inlineData.currentCompanyId || '';

  const messages = {
    required: 'Completează acest câmp.',
    cui: 'CUI-ul nu pare valid.',
  };

  let cityOptionsByCounty = inlineData.citiesByCounty && typeof inlineData.citiesByCounty === 'object'
    ? inlineData.citiesByCounty
    : {};
  let cityDataPromise = null;
  let lastTrigger = null;
  let lookupTimer = null;
  let lookupAbort = null;

  const getModal = () => document.querySelector('[data-company-book-modal]');

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

  // Orice caracter non-alfanumeric (cratima, apostrof, punct etc.) devine
  // spatiu inainte de comparatie - "Târgu-Mureș" din lista noastra vs
  // "Târgu Mureş" cum il intoarce ANAF pentru acelasi oras nu se
  // potriveau niciodata dupa autocompletare din CUI, desi orasul EXISTA in
  // lista (nu e o problema de date lipsa, e strict o diferenta de
  // caracter intre cratima si spatiu). Acelasi principiu ca
  // papetarie_storefront_normalize_locality_key() (PHP, foloseste deja
  // "/[^a-z0-9]+/u" -> spatiu de multa vreme, de-aia potrivirea judetului
  // nu avea aceasta problema) - aliniem si partea de JS la aceeasi
  // robustete, nu doar pentru cratima. Gasit live 2026-08-31/09-01, testand
  // cu multe firme reale din toata tara, nu doar cazurile semnalate
  // individual - user a cerut explicit sa nu ne mai bazam pe "prins din
  // intamplare".
  const normalizeValue = (value) => String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();

  // ANAF nu intoarce mereu un nume simplu de localitate la "sdenumire_Localitate" -
  // pentru satele componente ale unei comune vine ca "Sat X Com. Y" (ex.
  // "Sat Floreşti Com. Floreşti"), pe cand lista noastra de localitati are
  // fie satul singur ("Florești", cand numele satului-resedinta coincide cu
  // al comunei), fie "Sat (Comuna)" cand difera. Extragem ambele nume
  // candidate (sat + comuna) ca sa incercam potrivirea pe fiecare, in loc sa
  // cautam string-ul compus intreg ca atare (care nu exista niciodata ca
  // optiune).
  const extractLocalityCandidates = (raw) => {
    const text = String(raw || '').trim();
    const candidates = [text];
    const satComMatch = text.match(/^sat\s+(.+?)\s+com(?:\.|una)?\s+(.+)$/i);
    if (satComMatch) {
      candidates.push(satComMatch[1].trim(), satComMatch[2].trim());
    }
    const comOnlyMatch = text.match(/^com(?:\.|una)?\s+(.+)$/i);
    if (comOnlyMatch) {
      candidates.push(comOnlyMatch[1].trim());
    }
    const satOnlyMatch = text.match(/^sat\s+(.+)$/i);
    if (satOnlyMatch) {
      candidates.push(satOnlyMatch[1].trim());
    }
    // ANAF intoarce sectoarele Bucurestiului ca "Sector 6 Mun. Bucuresti"
    // (uneori "Sectorul 6 ..."), pe cand lista noastra de localitati are
    // "București (Sectorul 6)" - fara acest candidat, NICIO firma din
    // Bucuresti nu-si gasea vreodata localitatea dupa autocompletare din
    // CUI (doar satele/comunele aveau tratament special mai sus). Sectoarele
    // exista doar in Bucuresti, deci numarul e suficient, nu mai trebuie
    // extras si numele orasului din text. Gasit live 2026-08-31.
    const sectorMatch = text.match(/^sector(?:ul)?\s+(\d+)/i);
    if (sectorMatch) {
      candidates.push('București (Sectorul ' + sectorMatch[1] + ')');
    }
    // "Mun. X" / "Municipiul X" / "Oraș(ul) X" - alt prefix administrativ pe
    // care ANAF il adauga (ex. "Mun. Satu Mare" pentru un CUI cu sediul chiar
    // in orasul-resedinta de judet, unde lista noastra are doar "Satu Mare",
    // fara prefix). Acelasi principiu ca satOnlyMatch/comOnlyMatch de mai
    // sus, dar pentru orase/municipii, nu comune/sate. Gasit live 2026-08-31,
    // testand cu o firma reala din Satu Mare.
    const munMatch = text.match(/^mun(?:\.|icipiul)?\s+(.+)$/i);
    if (munMatch) {
      candidates.push(munMatch[1].trim());
    }
    const orasMatch = text.match(/^ora(?:s|ș)(?:ul)?\.?\s+(.+)$/i);
    if (orasMatch) {
      candidates.push(orasMatch[1].trim());
    }
    return candidates;
  };

  // Checkout-ul are propria lui logica de colorare a select-urilor
  // (".is-placeholder" gri vs ".has-value" text normal, vezi
  // syncSelectPlaceholderState() in checkout.js) care se actualizeaza pe
  // evenimentul "change" - un ".val()" setat direct din JS (fara sa simuleze
  // un "change" real) nu o declanseaza, deci selectul ramane cu clasa veche
  // (gri) chiar daca are acum o valoare reala. company-book.js e un fisier
  // separat, fara acces la functia din checkout.js, deci reproducem aceeasi
  // logica aici, de fiecare data cand setam o valoare de judet/localitate
  // din JS (lookup ANAF, alegere firma salvata etc).
  const syncSelectPlaceholderClass = ($field) => {
    if (!$field || !$field.length) {
      return;
    }
    const hasValue = String($field.val() || '').trim() !== '';
    $field.toggleClass('is-placeholder', !hasValue).toggleClass('has-value', hasValue);

    // Cand campul e select2-enhanced (Județ/Localitate sediu firmă - vezi
    // pap-enhanced-selects.js), un simplu ".val()" nu actualizeaza si cutia
    // vizuala a select2-ului (ramane cu label-ul vechi pana la un eveniment
    // de schimbare). Nu declansam ".trigger('change')" aici - ar reintra in
    // handler-ele generice de "change" ale aplicatiei (syncCitySelect() e
    // legata pe 'change' pe acelasi camp), ceea ce ar bucla infinit fiindca
    // syncCitySelect() la randul ei cheama syncSelectPlaceholderClass(). In
    // loc de asta, scriem direct in cutia select2 textul optiunii curent
    // selectate (mereu exista una - fie placeholder-ul, fie o valoare reala)
    // - fara evenimente, fara risc de recursivitate.
    if ($field.hasClass('select2-hidden-accessible')) {
      const $rendered = $field.next('.select2-container').find('.select2-selection__rendered');
      const selectedText = $field.find('option:selected').text();
      if ($rendered.length) {
        $rendered.text(selectedText).attr('title', selectedText);
      }
    }
  };

  const getRow = ($field) => $field.closest('.pap-float-field');

  const clearFieldError = ($field) => {
    const $row = getRow($field);
    $field.removeClass('pap-is-invalid').removeAttr('aria-invalid aria-describedby');
    $row.removeClass('pap-is-invalid');
    $row.find('.pap-field-error').text('');
  };

  const setFieldError = ($field, message) => {
    const $row = getRow($field);
    const descriptionId = `${$field.attr('id') || $field.attr('name') || 'company-field'}_description`;
    $field.addClass('pap-is-invalid').attr('aria-invalid', 'true').attr('aria-describedby', descriptionId);
    $row.addClass('pap-is-invalid');

    let $error = $row.find('.pap-field-error').first();
    if (!$error.length) {
      $error = $('<small>', { class: 'pap-field-error', 'aria-hidden': 'true' }).appendTo($row);
    }

    $error.attr('id', descriptionId).text(message);
  };

  const setFieldValid = ($field) => clearFieldError($field);

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
    const $stateField = $form.find('[data-company-book-state]').first();
    const $cityField = $form.find('[data-company-book-city]').first();

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

    syncSelectPlaceholderClass($stateField);

    // Select2 nu observa singur schimbarile de "disabled" facute direct pe
    // select-ul nativ (fara ".trigger('change')", evitat mai sus fiindca ar
    // relua bucla cu syncCitySelect()) - comutam manual clasa pe cutia lui,
    // la fel cum face select2 intern cand tine cont de starea disabled.
    const syncSelect2DisabledState = ($field, isDisabled) => {
      if ($field.hasClass('select2-hidden-accessible')) {
        $field.next('.select2-container').toggleClass('select2-container--disabled', isDisabled);
      }
    };

    if (!countyValue || options.length === 0) {
      $cityField.val('').prop('disabled', true).attr('aria-disabled', 'true');
      syncSelectPlaceholderClass($cityField);
      syncSelect2DisabledState($cityField, true);
      clearFieldError($cityField);
      return;
    }

    $cityField.prop('disabled', false).attr('aria-disabled', 'false');
    syncSelect2DisabledState($cityField, false);

    const exactMatch = options.find((option) => String(option) === currentValue);
    if (exactMatch) {
      $cityField.val(exactMatch);
    } else {
      const normalizedCurrent = normalizeValue(currentValue);
      const normalizedMatch = options.find((option) => normalizeValue(option) === normalizedCurrent);
      $cityField.val(normalizedMatch || '');
    }

    syncSelectPlaceholderClass($cityField);
    clearFieldError($cityField);
  };

  const validateField = ($field) => {
    if ($field.is(':disabled')) {
      clearFieldError($field);
      return true;
    }

    // Campurile din sectiunea "completare manuala" nu conteaza cat timp
    // sectiunea e ascunsa (utilizatorul nu a apasat nici "Completează
    // automat", nici checkbox-ul de completare manuala inca) - altfel
    // "denumire"/"state"/etc, desi invizibile, ar bloca submit-ul cu erori
    // pe care omul nu are cum sa le vada sau sa le rezolve.
    if ($field.closest('[hidden]').length) {
      clearFieldError($field);
      return true;
    }

    const value = String($field.val() || '').trim();
    const required = !!$field.prop('required');
    const type = String($field.attr('type') || '').toLowerCase();
    const name = String($field.attr('name') || '').toLowerCase();

    if ($field.is('select')) {
      if (required && value === '') {
        setFieldError($field, messages.required);
        return false;
      }
      setFieldValid($field);
      return true;
    }

    if (type === 'checkbox') {
      setFieldValid($field);
      return true;
    }

    if (required && value === '') {
      setFieldError($field, messages.required);
      return false;
    }

    if (value === '') {
      clearFieldError($field);
      return true;
    }

    if (name === 'cui') {
      const digits = value.replace(/[^0-9]/g, '');
      if (digits.length < 2 || digits.length > 10) {
        setFieldError($field, messages.cui);
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

    return isValid;
  };

  // "message" poate fi un string simplu (mesaj de succes) sau un array de
  // mesaje de validare - randate ca lista, nu inlantuite intr-un singur
  // paragraf ("Completează denumirea. Alege județul...", greu de citit).
  // Semnalat live de user 2026-08-31.
  const setNotice = ($form, message, type) => {
    const $noticeWrap = $form.closest('.pap-account-address-modal').find('[data-company-book-form-notice]').first();
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

  const setLookupStatus = ($form, message) => {
    $form.find('[data-company-book-lookup-status]').first().text(message || '');
  };

  // "Completează datele manual" nu mai e un checkbox (nu e cu adevarat o
  // stare on/off, e o schimbare de mod) - e un buton/link care isi schimba
  // textul intre starea "inchisa" si cea "deschisa" (vezi data-label-closed/
  // data-label-open in markup, setate din PHP).
  const setManualToggleLabel = ($toggle, isOpen) => {
    if (!$toggle.length) {
      return;
    }

    const label = isOpen ? $toggle.attr('data-label-open') : $toggle.attr('data-label-closed');
    if (label) {
      $toggle.text(label);
    }

    $toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
  };

  const setManualFieldsVisible = ($form, isVisible) => {
    $form.find('[data-company-book-manual-fields]').first().prop('hidden', !isVisible);
    setManualToggleLabel($form.find('[data-company-book-manual-toggle]').first(), isVisible);
  };

  const resetForm = ($form) => {
    $form.get(0).reset();
    $form.find('[name="pap_company_id"]').val('');
    $form.find('[name="pap_company_book_action"]').val('save');
    clearInlineValidation($form);
    setLookupStatus($form, '');
    setManualFieldsVisible($form, false);
    syncCitySelect($form);
  };

  const fillForm = ($form, company) => {
    const entry = company && typeof company === 'object' ? company : {};
    const setValue = (selector, value) => {
      const $field = $form.find(selector).first();
      if ($field.length) {
        $field.val(value === null || typeof value === 'undefined' ? '' : String(value));
      }
    };

    setValue('[name="pap_company_id"]', entry.id || '');
    setValue('[name="denumire"]', entry.denumire || '');
    setValue('[name="cui"]', entry.cui || '');
    setValue('[name="nr_reg_com"]', entry.nr_reg_com || '');
    setValue('[name="state"]', entry.state || '');
    syncCitySelect($form);
    setValue('[name="city"]', entry.city || '');
    setValue('[name="address_1"]', entry.address_1 || '');

    $form.find('[name="vat_payer"]').prop('checked', !!entry.vat_payer);
    setLookupStatus($form, '');
    setManualFieldsVisible($form, true);

    window.setTimeout(() => syncCitySelect($form), 0);
    window.setTimeout(() => syncCitySelect($form), 120);
  };

  const setModalTitle = (mode) => {
    const modal = getModal();
    if (!modal) {
      return;
    }

    const title = modal.querySelector('#pap-account-company-modal-title');
    if (!title) {
      return;
    }

    title.textContent = mode === 'edit' ? 'Editează firma' : 'Adaugă firmă';
  };

  const openModal = async (mode, company, trigger) => {
    const modal = getModal();
    if (!modal) {
      return;
    }

    await loadCityData();

    const $form = $(modal).find('[data-company-book-modal-form]').first();
    if (!$form.length) {
      return;
    }

    lastTrigger = trigger && typeof trigger.focus === 'function' ? trigger : null;

    if (mode === 'edit') {
      fillForm($form, company || {});
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

    const raw = trigger.getAttribute('data-company-book-entry');
    if (!raw) {
      return null;
    }

    try {
      return JSON.parse(raw);
    } catch (error) {
      return null;
    }
  };

  const requestCompanyBook = async (form) => {
    const formData = new FormData(form);
    formData.set('action', ajaxAction);
    formData.set('pap_company_book_nonce', ajaxNonce || formData.get('pap_company_book_nonce') || '');

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    });

    const json = await response.json();
    if (!json || !json.success) {
      const errorMessages = json && json.data && Array.isArray(json.data.messages) ? json.data.messages : [];
      const fallbackMessage = (json && json.data && json.data.message) || 'Nu am putut salva firma.';
      const requestError = new Error(errorMessages.length ? errorMessages.join(' ') : fallbackMessage);
      requestError.messages = errorMessages.length ? errorMessages : [fallbackMessage];
      throw requestError;
    }

    return json.data || {};
  };

  const setBusyState = ($form, isBusy) => {
    $form.find('button').each((_, button) => {
      const $button = $(button);
      if ($button.is('[data-company-book-modal-close]')) {
        return;
      }

      $button.prop('disabled', isBusy);
      $button.attr('aria-disabled', isBusy ? 'true' : 'false');
    });
  };

  // "Se facturează pe: X · CUI Y" - rezumat mereu vizibil in checkout, chiar
  // daca userul recolapseaza sectiunea de campuri manuale dupa un lookup CUI
  // reusit (raportat 2026-08-18: fara el, nicio confirmare persistenta a
  // cui ii apartine factura odata ce campurile sunt ascunse). No-op in afara
  // checkout-ului (modalul din Contul meu nu are acest element in markup).
  const updateCompanyBillingSummary = ($scope) => {
    const $summary = $scope.find('[data-checkout-company-billing-summary]').first();
    if (!$summary.length) {
      return;
    }

    const company = String($scope.find('[name="billing_company"]').first().val() || '').trim();
    const cui = String($scope.find('#billing_cui').first().val() || '').trim();

    $summary.prop('hidden', company === '');
    $summary.find('[data-checkout-company-billing-summary-name]').text(company);
    $summary.find('[data-checkout-company-billing-summary-cui]').text(cui ? `· CUI ${cui}` : '');
    // Separatorul "-" dintre rezumat si link-ul de toggle e un element
    // separat (nu un ::before pe buton, vezi comentariul din functions.php)
    // - trebuie ascuns/aratat in acelasi ritm cu rezumatul insusi.
    $scope.find('[data-checkout-company-billing-summary-sep]').prop('hidden', company === '');
  };

  // Dezvaluie sectiunea de campuri "manuale" (nume firma/reg.com/adresa)
  // dupa o incercare de lookup CUI, indiferent de rezultat - functioneaza
  // atat pentru modalul din Contul meu (".pap-company-manual-*"), cat si
  // pentru blocul de checkout ("[data-checkout-company-manual-*]"), care au
  // acelasi comportament dar markup separat (checkout nu are camp de adresa
  // separat, doar datele de facturare). Daca lookup-ul a reusit, campurile
  // apar precompletate pentru verificare; daca nu, apar goale pentru
  // completare manuala - oricum, utilizatorul nu ramane blocat cu un singur
  // camp de CUI vizibil dupa ce a incercat deja o cautare.
  const revealManualFieldsFor = ($scope) => {
    const $accountToggle = $scope.find('[data-company-book-manual-toggle]').first();
    const $accountFields = $scope.find('[data-company-book-manual-fields]').first();
    if ($accountToggle.length && $accountFields.length) {
      $accountFields.prop('hidden', false);
      setManualToggleLabel($accountToggle, true);
    }

    const $checkoutToggle = $scope.find('[data-checkout-company-manual-toggle]').first();
    const $checkoutFields = $scope.find('[data-checkout-company-manual-fields]').first();
    if ($checkoutToggle.length && $checkoutFields.length) {
      $checkoutFields.prop('hidden', false);
      setManualToggleLabel($checkoutToggle, true);
    }
  };

  // Cauta firma la ANAF cand utilizatorul termina de tastat CUI-ul (blur sau
  // pauza de 500ms dupa minim 2 cifre) - strict optional, orice esec doar
  // sterge mesajul de status si lasa formularul complet editabil manual (vezi
  // papetarie_storefront_company_book_lookup_cui() in company-book.php).
  const runCuiLookup = async ($form, $cuiField) => {
    const raw = String($cuiField.val() || '').trim();
    const digits = raw.replace(/[^0-9]/g, '');
    if (digits.length < 2) {
      setLookupStatus($form, '');
      return;
    }

    if (lookupAbort) {
      lookupAbort.abort();
    }
    lookupAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;

    setLookupStatus($form, 'Caut firma…');

    try {
      const formData = new FormData();
      formData.set('action', 'pap_lookup_cui');
      formData.set('nonce', lookupNonce);
      formData.set('cui', digits);

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        signal: lookupAbort ? lookupAbort.signal : undefined,
      });
      const json = await response.json();

      if (!json || !json.success || !json.data) {
        setLookupStatus($form, 'Nu am găsit firma — completează manual.');
        revealManualFieldsFor($form);
        return;
      }

      const data = json.data;
      // Suprascrie mereu, nu doar cand campul e gol - runCuiLookup() se
      // declanseaza acum DOAR la apasarea explicita a butonului "Completează
      // automat" (nu mai exista trigger automat pe blur/input), deci daca
      // userul apasa butonul a doua oara (dupa ce a modificat manual vreun
      // camp), rezultatul nou trebuie sa inlocuiasca ce era acolo, nu sa fie
      // ignorat.
      const setAlways = (selector, value) => {
        const $field = $form.find(selector).first();
        if ($field.length && value) {
          $field.val(value);
          // .val() nu declanseaza "input"/"change", deci starea de eroare
          // ramasa de la validarea anterioara (camp gol) nu s-ar sterge
          // singura - o curatam explicit aici, altfel textul rosu ramane
          // sub campul care tocmai a fost completat automat.
          clearFieldError($field);
        }
      };

      setAlways('[name="denumire"], [name="billing_company"]', data.denumire);
      setAlways('[name="nr_reg_com"], [name="billing_reg_no"]', data.nr_reg_com);
      setAlways('[name="address_1"], [name="billing_company_address"]', data.address_1);

      if (data.state) {
        const $stateField = $form.find('[data-company-book-state]').first();
        if ($stateField.length) {
          $stateField.val(data.state);
          syncSelectPlaceholderClass($stateField);
          syncCitySelect($form);
          if (data.city) {
            // ANAF intoarce localitatea fie ca nume simplu, fie compus
            // ("Sat X Com. Y" pentru satele componente) si cu
            // majuscule/diacritice diferite de varianta din lista noastra -
            // un ".val(data.city)" direct nu gaseste optiunea (potrivire
            // exacta, case-sensitive, string intreg) si selectul ramane pe
            // placeholder. Incercam fiecare nume candidat (string intreg,
            // apoi sat/comuna extrase separat) cu acelasi normalizeValue()
            // folosit si la restaurarea unei valori existente in
            // syncCitySelect().
            const $cityField = $form.find('[data-company-book-city]').first();
            const cityOptions = getCityOptions(String(data.state));
            const candidates = extractLocalityCandidates(data.city);
            let matchedCity = null;
            for (let i = 0; i < candidates.length && !matchedCity; i += 1) {
              const normalizedCandidate = normalizeValue(candidates[i]);
              matchedCity = cityOptions.find((option) => normalizeValue(option) === normalizedCandidate);
            }
            // Plasa de siguranta: daca ANAF foloseste un prefix administrativ
            // pe care extractLocalityCandidates() nu-l anticipeaza explicit
            // (ex. "Mun."/"Oraș"/"Sector" au fost adaugate unul cate unul, pe
            // masura ce au aparut in teste reale - abordare fragila, nu
            // exista cum sa "ghicim" din start toate variantele posibile),
            // incercam o potrivire "contine": textul brut de la ANAF
            // (normalizat) trebuie sa contina numele intreg al unei optiuni
            // din lista noastra (normalizat) ca sub-secventa - nu invers
            // (ar prinde fals-pozitive pe nume scurte generice). Dintre
            // optiunile care se potrivesc astfel, alegem cea mai lunga (mai
            // specifica), ca sa preferam "Viile Satu Mare" in fata unui
            // "Satu Mare" mai scurt cand ambele apar in text. Nu inlocuieste
            // candidatii expliciti de mai sus (raman prioritari - mai
            // precisi pentru cazul sat+comuna cu nume diferite), doar
            // completeaza cazurile neprevazute. Cerut explicit de user
            // 2026-08-31 ("nu pot lua fiecare judet si fiecare municipiu").
            if (!matchedCity) {
              const normalizedRaw = normalizeValue(data.city);
              let bestMatch = null;
              cityOptions.forEach((option) => {
                const normalizedOption = normalizeValue(option);
                if (normalizedOption.length >= 3 && normalizedRaw.indexOf(normalizedOption) !== -1) {
                  if (!bestMatch || normalizedOption.length > normalizeValue(bestMatch).length) {
                    bestMatch = option;
                  }
                }
              });
              matchedCity = bestMatch;
            }
            if (matchedCity) {
              $cityField.val(matchedCity);
              syncSelectPlaceholderClass($cityField);
              clearFieldError($cityField);
            }
          }
        }
      }

      if (typeof data.vat_payer !== 'undefined') {
        $form.find('[name="vat_payer"]').prop('checked', !!data.vat_payer);
        // Checkout nu are checkbox-ul "vat_payer" (doar modalul din "Contul
        // meu" il are) - are in schimb campul ascuns "billing_vat_payer",
        // unica sursa pentru Oblio la facturare. Vezi
        // papetarie_storefront_render_checkout_company_block() in
        // functions.php. Semnalat live de user 2026-08-31.
        $form.find('[name="billing_vat_payer"]').val(data.vat_payer ? '1' : '0');
      }

      // Nu repeta denumirea firmei aici - rezumatul persistent de mai jos
      // ("Se facturează pe: X") o arata deja, mereu vizibil chiar si cu
      // sectiunea colapsata; un mesaj scurt aici e suficient ca feedback
      // imediat (si anunt pentru cititoarele de ecran, ".aria-live").
      setLookupStatus($form, 'Firmă găsită.');
      revealManualFieldsFor($form);
      updateCompanyBillingSummary($form);
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return;
      }
      setLookupStatus($form, '');
    }
  };

  const bindCuiLookup = ($form) => {
    const $cuiField = $form.find('[data-company-book-cui]').first();
    if (!$cuiField.length) {
      return;
    }

    $cuiField.on('blur', () => runCuiLookup($form, $cuiField));
    $cuiField.on('input', () => {
      window.clearTimeout(lookupTimer);
      const digits = String($cuiField.val() || '').replace(/[^0-9]/g, '');
      if (digits.length < 2) {
        setLookupStatus($form, '');
        return;
      }
      lookupTimer = window.setTimeout(() => runCuiLookup($form, $cuiField), 600);
    });
  };

  // Bloc "Doresc factura pe firma" de pe checkout - independent de modalul
  // din Contul meu (poate sa nu existe deloc pe pagina de checkout).
  // Checkout-ul acestui tema isi re-randeaza sectiunea de adresa prin AJAX
  // (evenimentul "updated_checkout" al WooCommerce, vezi checkout.js) - orice
  // handler legat direct de un element specific (".on()") se pierde cand
  // acel element e inlocuit cu markup proaspat de la server. De-aia toata
  // interactiunea de aici foloseste delegare pe "document" (element-agnostic,
  // supravietuieste inlocuirii de DOM), nu legare directa pe elementul gasit
  // la momentul init().
  const bindCheckoutCompanyDelegation = () => {
    $(document).off('change.papCheckoutCompanyToggle', '[data-checkout-company-toggle]');
    $(document).on('change.papCheckoutCompanyToggle', '[data-checkout-company-toggle]', function () {
      const $wrap = $(this).closest('.pap-checkout-company');
      $wrap.find('[data-checkout-company-fields]').first().prop('hidden', !this.checked);
      // Nota "adresa de livrare = adresa de facturare" are sens doar cat
      // timp NU se cere factura pe firma - opusul campurilor de mai sus.
      $wrap.find('[data-checkout-company-note]').first().prop('hidden', this.checked);
    });

    $(document).off('click.papCheckoutCompanyManualToggle', '[data-checkout-company-manual-toggle]');
    $(document).on('click.papCheckoutCompanyManualToggle', '[data-checkout-company-manual-toggle]', function () {
      const $toggle = $(this);
      const $fieldsWrap = $toggle.closest('.pap-checkout-company').find('[data-checkout-company-manual-fields]').first();
      const willBeVisible = !!$fieldsWrap.prop('hidden');
      $fieldsWrap.prop('hidden', !willBeVisible);
      setManualToggleLabel($toggle, willBeVisible);
    });

    // Userul poate completa Firmă/CUI manual (lookup CUI esuat sau sarit
    // intentionat) - rezumatul trebuie sa reflecte si aceste editari live,
    // nu doar rezultatul unui lookup reusit.
    $(document).off('input.papCheckoutCompanySummary', '.pap-checkout-company [name="billing_company"], .pap-checkout-company #billing_cui');
    $(document).on('input.papCheckoutCompanySummary', '.pap-checkout-company [name="billing_company"], .pap-checkout-company #billing_cui', function () {
      updateCompanyBillingSummary($(this).closest('.pap-checkout-company'));
    });

    // Butonul "Completează automat" de la checkout - acelasi element/markup
    // ca in modalul din Contul meu, dar legat delegat (nu prin bindForm(),
    // care nu ruleaza deloc pe checkout) fiindca sectiunea de adresa se
    // re-randeaza prin AJAX. Scopat la ".pap-checkout-company" ca sa nu se
    // lege de doua ori pe butonul din modalul Contul meu (acela ramane legat
    // separat, direct, in bindForm()).
    $(document).off('click.papCheckoutCompanyAutocomplete', '.pap-checkout-company [data-company-book-autocomplete]');
    $(document).on('click.papCheckoutCompanyAutocomplete', '.pap-checkout-company [data-company-book-autocomplete]', async function () {
      const $button = $(this);
      const $scope = $button.closest('.pap-checkout-company-fields');
      const $cuiField = $scope.find('[data-company-book-cui]').first();
      if (!$cuiField.length) {
        return;
      }

      $button.prop('disabled', true);
      await runCuiLookup($scope, $cuiField);
      $button.prop('disabled', false);
      revealManualFieldsFor($scope);
    });

    const applyCheckoutCompanySelection = ($select) => {
      const $block = $select.closest('.pap-checkout-company');
      const $fieldsWrap = $block.find('[data-checkout-company-fields]').first();
      const $recordsScript = $block.find('[data-checkout-company-records]').first();

      let records = {};
      try {
        records = JSON.parse($recordsScript.text() || '{}');
      } catch (error) {
        records = {};
      }

      const companyId = String($select.val() || '');
      const entry = companyId && records[companyId] ? records[companyId] : null;

      const setValue = (selector, value) => {
        const $field = $fieldsWrap.find(selector).first();
        if ($field.length) {
          $field.val(value === null || typeof value === 'undefined' ? '' : String(value));
        }
      };

      setValue('[name="billing_company"]', entry ? entry.denumire : '');
      setValue('[name="billing_cui"]', entry ? entry.cui : '');
      setValue('[name="billing_reg_no"]', entry ? entry.nr_reg_com : '');
      setValue('[name="billing_company_address"]', entry ? entry.address_1 : '');
      // "vat_payer" e deja in inregistrarea firmei (JSON-ul din
      // data-checkout-company-records) - unica sursa pentru Oblio la
      // facturare (vezi runCuiLookup() pentru celalalt loc care scrie
      // acelasi camp, cazul lookup ANAF direct). Semnalat live de user
      // 2026-08-31.
      $fieldsWrap.find('[name="billing_vat_payer"]').val(entry && entry.vat_payer ? '1' : '0');

      // Judet/localitate au nevoie de acelasi tratament ca la lookup-ul ANAF:
      // repopulam optiunile de localitate pentru noul judet (syncCitySelect)
      // inainte sa incercam sa selectam orasul salvat, altfel selectul de
      // oras inca are optiunile judetului anterior (sau e gol).
      const $companyStateField = $fieldsWrap.find('[data-company-book-state]').first();
      if ($companyStateField.length) {
        $companyStateField.val(entry ? (entry.state || '') : '');
        syncSelectPlaceholderClass($companyStateField);
        syncCitySelect($fieldsWrap);
        if (entry && entry.city) {
          const $companyCityField = $fieldsWrap.find('[data-company-book-city]').first();
          const entryCityOptions = getCityOptions(String(entry.state || ''));
          const normalizedEntryCity = normalizeValue(entry.city);
          const matchedEntryCity = entryCityOptions.find((option) => normalizeValue(option) === normalizedEntryCity);
          if (matchedEntryCity) {
            $companyCityField.val(matchedEntryCity);
            syncSelectPlaceholderClass($companyCityField);
          }
        }
      }

      setLookupStatus($fieldsWrap, '');

      // "Ori alegi din listă, ori completezi una nouă" - cand s-a ales o
      // firma reala, ascundem tot blocul de "firma noua" (CUI + campuri
      // manuale); valorile raman totusi in DOM (doar [hidden], nu sterse) si
      // se trimit normal la submit. Cand s-a ales "— Firmă nouă —"
      // (companyId gol), blocul redevine vizibil.
      // "Ori alegi din listă, ori completezi una nouă" - cand s-a ales o
      // firma reala, blocul de CUI + campuri manuale ramane complet ascuns
      // (userul nu mai trebuie sa "confirme" vizual nimic, sistemul stie deja
      // ID-ul firmei si foloseste datele salvate la facturare); valorile
      // raman totusi in DOM (doar [hidden], nu sterse) si se trimit normal la
      // submit. Cand s-a ales "+ Adaugă firmă nouă" (companyId gol), blocul
      // redevine vizibil.
      const $newEntryWrap = $block.find('[data-checkout-company-new-entry]').first();
      if ($newEntryWrap.length) {
        $newEntryWrap.prop('hidden', !!entry);
      }
    };

    $(document).off('change.papCheckoutCompanySelect', '[data-checkout-company-select]');
    $(document).on('change.papCheckoutCompanySelect', '[data-checkout-company-select]', function () {
      applyCheckoutCompanySelection($(this));
    });

    // Selectul poate porni deja cu o firmă preselectată (ex. firma implicită
    // a userului), la incarcarea initiala a paginii SAU dupa ce WooCommerce
    // re-randeaza sectiunea prin AJAX (updated_checkout) - in ambele cazuri
    // nu se declanseaza niciun eveniment 'change', asa ca fara pasul asta
    // campurile billing_company_* raman goale la submit daca userul nu
    // atinge deloc dropdown-ul. Gatit strict de "data-checkout-company-
    // preselected" (setat din PHP doar cand $preselected_default a fost
    // calculat true la randare) - NU doar de "select-ul are o valoare", fiindca acel
    // select porneste mereu cu firma implicita marcata "selected" chiar si
    // dupa o eroare de validare in care userul completa manual alta firma
    // (caz in care ar suprascrie gresit datele tastate de el).
    $('[data-checkout-company-select][data-checkout-company-preselected]').each(function () {
      applyCheckoutCompanySelection($(this));
    });

    // Deliberat NU cautam automat pe blur/input aici - userul nu vrea niciun
    // fel de cautare "in timp ce scrie" pe checkout, doar la apasarea
    // explicita a butonului "Completează automat" (vezi handler-ul de mai
    // sus, ".papCheckoutCompanyAutocomplete"), la fel ca in Contul meu.
  };

  const applyCheckoutCompanyToggleState = () => {
    $('[data-checkout-company-toggle]').each(function () {
      const $wrap = $(this).closest('.pap-checkout-company');
      $wrap.find('[data-checkout-company-fields]').first().prop('hidden', !this.checked);
      $wrap.find('[data-checkout-company-note]').first().prop('hidden', this.checked);
    });
  };

  const bindForm = ($form) => {
    if ($form.data('papCompanyBookBound')) {
      return;
    }

    $form.data('papCompanyBookBound', true);

    const sync = () => syncCitySelect($form);
    sync();
    window.setTimeout(sync, 0);
    window.setTimeout(sync, 150);

    const $stateField = $form.find('[data-company-book-state]').first();
    if ($stateField.length) {
      $stateField.on('change select2:select select2:clear input', sync);
    }

    // Modalul din Contul meu foloseste buton explicit + checkbox "manual",
    // nu lookup automat pe blur/input (asta ramane doar la checkout, unde
    // spatiul e mai putin si fluxul trebuie sa fie mai rapid) - vezi
    // papetarie_storefront_company_book_render_form_fields() in
    // company-book.php pentru markup-ul "Completează automat" + checkbox.
    const $manualToggle = $form.find('[data-company-book-manual-toggle]').first();
    const $manualFields = $form.find('[data-company-book-manual-fields]').first();
    const revealManualFields = () => {
      $manualFields.prop('hidden', false);
      setManualToggleLabel($manualToggle, true);
    };

    if ($manualToggle.length && $manualFields.length) {
      $manualToggle.on('click', () => {
        const willBeVisible = !!$manualFields.prop('hidden');
        $manualFields.prop('hidden', !willBeVisible);
        setManualToggleLabel($manualToggle, willBeVisible);
      });
    }

    const $autocompleteBtn = $form.find('[data-company-book-autocomplete]').first();
    const $cuiField = $form.find('[data-company-book-cui]').first();
    if ($autocompleteBtn.length && $cuiField.length) {
      $autocompleteBtn.on('click', async () => {
        $autocompleteBtn.prop('disabled', true);
        await runCuiLookup($form, $cuiField);
        $autocompleteBtn.prop('disabled', false);
        // Dezvaluim campurile indiferent de rezultat - daca lookup-ul a
        // reusit, apar precompletate pentru verificare; daca nu, apar goale
        // pentru completare manuala. Nu are sens sa lasam utilizatorul intr-un
        // formular cu un singur camp vizibil dupa ce a incercat deja.
        revealManualFields();
      });
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
        await requestCompanyBook($form.get(0));
        window.location.reload();
      } catch (error) {
        setNotice($form, (error && error.messages) || (error && error.message) || 'Nu am putut salva firma.', 'error');
        setBusyState($form, false);
      }
    });
  };

  const bindDeleteForms = () => {
    $(document).off('submit.papCompanyBookDelete', '[data-company-delete-form]');
    $(document).on('submit.papCompanyBookDelete', '[data-company-delete-form]', async function (event) {
      event.preventDefault();

      const confirmed = window.papConfirmModal
        ? await window.papConfirmModal(deleteConfirm, { title: 'Ștergi firma?', confirmLabel: 'Da, șterge' })
        : window.confirm(deleteConfirm);
      if (!confirmed) {
        return;
      }

      const $form = $(this);
      const formData = new FormData(this);
      formData.set('action', ajaxAction);
      formData.set('pap_company_book_action', 'delete');
      formData.set('pap_company_book_nonce', ajaxNonce || formData.get('pap_company_book_nonce') || '');

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
          throw new Error(json && json.data && json.data.message ? json.data.message : 'Nu am putut șterge firma.');
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

  const bindSetDefaultForms = () => {
    $(document).off('submit.papCompanyBookSetDefault', '.pap-account-company-card__default-form');
    $(document).on('submit.papCompanyBookSetDefault', '.pap-account-company-card__default-form', async function (event) {
      event.preventDefault();

      const $form = $(this);
      const formData = new FormData(this);
      formData.set('action', ajaxAction);
      formData.set('pap_company_book_action', 'set_default');
      formData.set('pap_company_book_nonce', ajaxNonce || formData.get('pap_company_book_nonce') || '');

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
          throw new Error(json && json.data && json.data.message ? json.data.message : 'Nu am putut actualiza firma implicită.');
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
    $(document).off('click.papCompanyBookModal', '[data-company-book-open-modal]');
    $(document).on('click.papCompanyBookModal', '[data-company-book-open-modal]', async function (event) {
      event.preventDefault();

      const trigger = this;
      const mode = String(trigger.getAttribute('data-company-book-mode') || 'add');
      const entry = getEntryFromTrigger(trigger);
      await openModal(mode, entry, trigger);
    });

    $(document).off('click.papCompanyBookModalClose', '[data-company-book-modal-close]');
    $(document).on('click.papCompanyBookModalClose', '[data-company-book-modal-close]', (event) => {
      event.preventDefault();
      closeModal(document.activeElement);
    });
  };

  const autoOpenFromUrl = async () => {
    if (!currentMode || (currentMode !== 'add' && currentMode !== 'edit')) {
      return;
    }

    const entryTrigger = currentCompanyId
      ? Array.prototype.slice.call(document.querySelectorAll('[data-company-book-open-modal]')).find((trigger) => String(trigger.getAttribute('data-company-book-id') || '') === String(currentCompanyId))
      : null;
    const entry = entryTrigger ? getEntryFromTrigger(entryTrigger) : null;
    await openModal(currentMode, entry, entryTrigger || null);
  };

  const init = async () => {
    await loadCityData();

    if ($('.pap-checkout-company').length) {
      bindCheckoutCompanyDelegation();
      applyCheckoutCompanyToggleState();
      $(document.body).off('updated_checkout.papCheckoutCompany').on('updated_checkout.papCheckoutCompany', () => {
        applyCheckoutCompanyToggleState();
        bindCheckoutCompanyDelegation();
      });
    }

    const modal = getModal();
    if (!modal) {
      return;
    }

    bindOpeners();
    bindDeleteForms();
    bindSetDefaultForms();

    const $modalForm = $(modal).find('[data-company-book-modal-form]').first();
    if ($modalForm.length) {
      bindForm($modalForm);
    }

    if (modal.getAttribute('data-company-book-open-on-load') === '1') {
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
