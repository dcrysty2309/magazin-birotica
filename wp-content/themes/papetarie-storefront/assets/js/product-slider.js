(function () {
  'use strict';

  var initializedSliderShells = typeof WeakSet === 'function' ? new WeakSet() : null;
  // Indexul "tintit" de noi la ultimul click, per slider - separat de
  // scrollLeft-ul real al DOM-ului. Necesar pentru ca scroll-ul e "smooth"
  // (animat): daca se da click din nou inainte ca animatia anterioara sa se
  // termine, scrollLeft e undeva la mijloc intre doua carduri in acel
  // moment, iar un index calculat de-acolo (Math.round(scrollLeft/amount))
  // pica adesea gresit, facand sageata sa para ca "sare" - reprodus live
  // 2026-07-31 la click-uri rapide repetate. Tinand indexul separat,
  // fiecare click porneste mereu de la ultima tinta reala, indiferent daca
  // animatia anterioara a apucat sa se termine vizual sau nu.
  var sliderTargetIndex = typeof WeakMap === 'function' ? new WeakMap() : null;
  var sliderScrollTimers = typeof WeakMap === 'function' ? new WeakMap() : null;

  function getSliderMetrics(slider) {
    var card = slider.querySelector('.pap-product-card');
    if (!card) {
      return null;
    }

    var gap = parseFloat(getComputedStyle(slider.querySelector('.pap-product-grid') || slider).columnGap) || 0;
    var amount = card.getBoundingClientRect().width + gap;
    var maxScroll = slider.scrollWidth - slider.clientWidth;
    var maxIndex = Math.max(0, Math.round(maxScroll / amount));

    return { amount: amount, maxScroll: maxScroll, maxIndex: maxIndex };
  }

  function indexFromScrollLeft(slider, amount, maxIndex) {
    return Math.max(0, Math.min(maxIndex, Math.round(slider.scrollLeft / amount)));
  }

  function getTrackedIndex(slider, amount, maxIndex) {
    if (sliderTargetIndex && sliderTargetIndex.has(slider)) {
      return sliderTargetIndex.get(slider);
    }

    return indexFromScrollLeft(slider, amount, maxIndex);
  }

  function setTrackedIndex(slider, index) {
    if (sliderTargetIndex) {
      sliderTargetIndex.set(slider, index);
    }
  }

  // Daca utilizatorul deruleaza manual (touch/trackpad/scrollbar), indexul
  // tinut de noi ar ramane invechit - il resincronizam dupa ce scroll-ul
  // manual s-a oprit (debounce, in lipsa suportului pentru "scrollend").
  function scheduleManualScrollResync(slider) {
    if (!sliderScrollTimers) {
      return;
    }

    var existing = sliderScrollTimers.get(slider);
    if (existing) {
      window.clearTimeout(existing);
    }

    var timer = window.setTimeout(function () {
      var metrics = getSliderMetrics(slider);
      if (metrics) {
        setTrackedIndex(slider, indexFromScrollLeft(slider, metrics.amount, metrics.maxIndex));
      }
      sliderScrollTimers.delete(slider);
    }, 150);

    sliderScrollTimers.set(slider, timer);
  }

  function scrollHorizontalSlider(slider, direction) {
    if (!slider) {
      return;
    }

    var metrics = getSliderMetrics(slider);
    if (!metrics) {
      return;
    }

    var amount = metrics.amount;
    var maxScroll = metrics.maxScroll;
    var maxIndex = metrics.maxIndex;
    var currentIndex = getTrackedIndex(slider, amount, maxIndex);
    var targetIndex = currentIndex + direction;

    if (targetIndex > maxIndex) {
      setTrackedIndex(slider, 0);
      slider.scrollLeft = 0;
      slider.scrollTo({ left: Math.min(amount, maxScroll), behavior: 'smooth' });
      return;
    }

    if (targetIndex < 0) {
      setTrackedIndex(slider, maxIndex);
      slider.scrollLeft = maxScroll;
      slider.scrollTo({ left: Math.max(maxScroll - amount, 0), behavior: 'smooth' });
      return;
    }

    setTrackedIndex(slider, targetIndex);
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

    slider.addEventListener('scroll', function () {
      scheduleManualScrollResync(slider);
    }, { passive: true });

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
