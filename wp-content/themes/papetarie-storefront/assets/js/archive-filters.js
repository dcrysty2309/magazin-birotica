(() => {
  const forms = document.querySelectorAll('.pap-archive-filter-form');

  const toNumber = (value) => {
    if (value === '') {
      return null;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  };

  forms.forEach((form) => {
    const minInput = form.querySelector('[data-custom-price-min]');
    const maxInput = form.querySelector('[data-custom-price-max]');

    if (!minInput || !maxInput) {
      return;
    }

    const validate = () => {
      const min = toNumber(minInput.value.trim());
      const max = toNumber(maxInput.value.trim());

      minInput.setCustomValidity('');
      maxInput.setCustomValidity('');

      if (min === null && max === null) {
        return true;
      }

      if (min === null || max === null) {
        const message = 'Completează atât Min cât și Max.';
        minInput.setCustomValidity(message);
        maxInput.setCustomValidity(message);
        return false;
      }

      if (min < 0 || max < 0) {
        const message = 'Valorile trebuie să fie pozitive.';
        minInput.setCustomValidity(message);
        maxInput.setCustomValidity(message);
        return false;
      }

      if (min > max) {
        const message = 'Min trebuie să fie mai mic sau egal cu Max.';
        minInput.setCustomValidity(message);
        maxInput.setCustomValidity(message);
        return false;
      }

      return true;
    };

    minInput.addEventListener('input', validate);
    maxInput.addEventListener('input', validate);
    form.addEventListener('submit', (event) => {
      if (!validate()) {
        event.preventDefault();
        minInput.reportValidity();
      }
    });
  });
})();
