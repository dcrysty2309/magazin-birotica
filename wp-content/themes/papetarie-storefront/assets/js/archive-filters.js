(() => {
  const forms = document.querySelectorAll('.pap-archive-filter-form');

  forms.forEach((form) => {
    const slider = form.querySelector('.pap-archive-price-slider');
    if (!slider) {
      return;
    }

    const minBound = Number(slider.dataset.minBound || 0);
    const maxBound = Number(slider.dataset.maxBound || 0);
    const minInput = slider.querySelector('[data-price-slider="min"]');
    const maxInput = slider.querySelector('[data-price-slider="max"]');
    const minValue = slider.querySelector('[data-price-min-value]');
    const maxValue = slider.querySelector('[data-price-max-value]');
    const fill = slider.querySelector('.pap-archive-price-slider-fill');
    const currency = slider.dataset.currency || 'RON';
    const formatter = new Intl.NumberFormat('ro-RO', {
      style: 'currency',
      currency,
      maximumFractionDigits: 0,
    });

    if (!minInput || !maxInput || !minValue || !maxValue || !fill) {
      return;
    }

    const range = Math.max(maxBound - minBound, 1);

    const render = () => {
      const currentMin = Math.max(minBound, Math.min(Number(minInput.value || minBound), maxBound));
      const currentMax = Math.max(currentMin, Math.min(Number(maxInput.value || maxBound), maxBound));

      minValue.textContent = formatter.format(currentMin);
      maxValue.textContent = formatter.format(currentMax);

      const left = ((currentMin - minBound) / range) * 100;
      const right = 100 - ((currentMax - minBound) / range) * 100;

      fill.style.left = `${left}%`;
      fill.style.right = `${right}%`;
    };

    const syncInputs = (active) => {
      let currentMin = Number(minInput.value || minBound);
      let currentMax = Number(maxInput.value || maxBound);

      if (currentMin > currentMax) {
        if (active === 'min') {
          currentMax = currentMin;
          maxInput.value = String(currentMax);
        } else {
          currentMin = currentMax;
          minInput.value = String(currentMin);
        }
      }

      render();
    };

    minInput.addEventListener('input', () => syncInputs('min'));
    maxInput.addEventListener('input', () => syncInputs('max'));

    render();
  });
})();
