(function () {
  'use strict';

  var initializedSliderShells = typeof WeakSet === 'function' ? new WeakSet() : null;

  function scrollHorizontalSlider(slider, direction) {
    if (!slider) {
      return;
    }

    var card = slider.querySelector('.pap-product-card');
    if (!card) {
      return;
    }

    var gap = parseFloat(getComputedStyle(slider.querySelector('.pap-product-grid') || slider).columnGap) || 0;
    var amount = card.getBoundingClientRect().width + gap;
    var maxScroll = slider.scrollWidth - slider.clientWidth;
    var maxIndex = Math.max(0, Math.round(maxScroll / amount));
    var currentIndex = Math.round(slider.scrollLeft / amount);
    var targetIndex = currentIndex + direction;

    if (targetIndex > maxIndex) {
      slider.scrollLeft = 0;
      slider.scrollTo({ left: Math.min(amount, maxScroll), behavior: 'smooth' });
      return;
    }

    if (targetIndex < 0) {
      slider.scrollLeft = maxScroll;
      slider.scrollTo({ left: Math.max(maxScroll - amount, 0), behavior: 'smooth' });
      return;
    }

    var target = targetIndex >= maxIndex ? maxScroll : targetIndex * amount;
    slider.scrollTo({ left: target, behavior: 'smooth' });
  }

  function initHorizontalSliderShell(shell) {
    if (!shell || (initializedSliderShells && initializedSliderShells.has(shell))) {
      return;
    }

    var slider = shell.querySelector('[data-featured-slider]');
    var prev = shell.querySelector('[data-featured-prev]');
    var next = shell.querySelector('[data-featured-next]');

    if (!slider) {
      return;
    }

    if (prev) {
      prev.addEventListener('click', function () {
        scrollHorizontalSlider(slider, -1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        scrollHorizontalSlider(slider, 1);
      });
    }

    if (initializedSliderShells) {
      initializedSliderShells.add(shell);
    }
  }

  function initHorizontalSliders() {
    Array.prototype.slice.call(document.querySelectorAll('.pap-featured-slider-shell')).forEach(initHorizontalSliderShell);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHorizontalSliders);
  } else {
    initHorizontalSliders();
  }
})();
