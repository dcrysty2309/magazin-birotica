(function () {
  'use strict';

  var initializedSliderShells = typeof WeakSet === 'function' ? new WeakSet() : null;
  // Indexul "tintit" de noi la ultimul click, per slider - separat de
  // scrollLeft-ul real al DOM-ului (vezi si animatia proprie mai jos).
  var sliderTargetIndex = typeof WeakMap === 'function' ? new WeakMap() : null;
  var sliderScrollTimers = typeof WeakMap === 'function' ? new WeakMap() : null;
  // Animatia noastra proprie in curs, per slider (id-ul de
  // requestAnimationFrame) - ne spune si cand un eveniment "scroll" e produs
  // de NOI (setand scrollLeft cadru cu cadru), nu de utilizator manual.
  var sliderAnimations = typeof WeakMap === 'function' ? new WeakMap() : null;

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

  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  // Animatie proprie (requestAnimationFrame), in locul lui
  // Element.scrollTo({behavior:'smooth'}) nativ. Motivul: scroll-ul smooth
  // nativ, cand e "re-tintit" printr-un al doilea apel inainte sa se termine
  // primul (exact ce se intampla la click-uri rapide repetate), se comporta
  // inconsistent intre browsere - unele corecteaza vizibil directia la
  // mijlocul miscarii, dand impresia ca sare putin inapoi/in stanga chiar
  // daca pozitia FINALA calculata era corecta (reprodus live 2026-07-31: "2-3
  // click-uri merg ok, al 3-4-lea tot sare putin"). Facand noi interpolarea
  // cadru cu cadru, plecam mereu din scrollLeft-ul REAL curent (oriunde ar fi
  // ramas o animatie anterioara intrerupta) si nu depindem de nicio stare
  // interna de animatie a browserului.
  function animateScrollTo(slider, targetLeft, duration) {
    duration = typeof duration === 'number' ? duration : 380;

    if (sliderAnimations) {
      var existing = sliderAnimations.get(slider);
      if (existing) {
        window.cancelAnimationFrame(existing.rafId);
      }
    }

    var startLeft = slider.scrollLeft;
    var delta = targetLeft - startLeft;
    var startTime = null;

    if (Math.abs(delta) < 1) {
      slider.scrollLeft = targetLeft;
      if (sliderAnimations) {
        sliderAnimations.delete(slider);
      }
      return;
    }

    // CSS "scroll-snap-type: mandatory" de pe slider face ca browserul sa
    // refuze sa randeze pozitiile intermediare pe care le setam noi cadru cu
    // cadru (nu sunt puncte de "snap") - ramane vizual blocat pe pozitia
    // curenta pana la finalul animatiei noastre si sare direct la urmatorul
    // punct de snap, exact saritura raportata. Il dezactivam cat dureaza
    // animatia proprie si il restauram la final, ca drag-ul manual (touch)
    // sa ramana cu snap.
    slider.style.scrollSnapType = 'none';

    function step(timestamp) {
      if (startTime === null) {
        startTime = timestamp;
      }

      var elapsed = timestamp - startTime;
      var progress = duration > 0 ? Math.min(1, elapsed / duration) : 1;
      slider.scrollLeft = startLeft + delta * easeOutCubic(progress);

      if (progress < 1) {
        var rafId = window.requestAnimationFrame(step);
        if (sliderAnimations) {
          sliderAnimations.set(slider, { rafId: rafId, targetLeft: targetLeft });
        }
      } else {
        slider.style.scrollSnapType = '';
        if (sliderAnimations) {
          sliderAnimations.delete(slider);
        }
      }
    }

    var initialRafId = window.requestAnimationFrame(step);
    if (sliderAnimations) {
      sliderAnimations.set(slider, { rafId: initialRafId, targetLeft: targetLeft });
    }
  }

  // Daca utilizatorul deruleaza manual (touch/trackpad/scrollbar), indexul
  // tinut de noi ar ramane invechit - il resincronizam dupa ce scroll-ul
  // manual s-a oprit (debounce, in lipsa suportului pentru "scrollend").
  // Ignoram evenimentele de scroll produse chiar de animatia noastra -
  // altfel ne-am resincroniza pe o pozitie intermediara, in plina miscare.
  function scheduleManualScrollResync(slider) {
    if (!sliderScrollTimers || (sliderAnimations && sliderAnimations.has(slider))) {
      return;
    }

    var existing = sliderScrollTimers.get(slider);
    if (existing) {
      window.clearTimeout(existing);
    }

    var timer = window.setTimeout(function () {
      if (sliderAnimations && sliderAnimations.has(slider)) {
        return;
      }
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
    // Fara wrap-around: la capete, click-ul suplimentar ramane pe loc in loc
    // sa teleporteze instantaneu la celalalt capat (teleportul instant, daca
    // prindea o animatie anterioara inca in desfasurare, producea exact
    // sariturea vizibila spre stanga raportata - vezi comentariul de la
    // animateScrollTo()).
    var targetIndex = Math.max(0, Math.min(maxIndex, currentIndex + direction));

    setTrackedIndex(slider, targetIndex);
    var target = targetIndex >= maxIndex ? maxScroll : targetIndex * amount;
    animateScrollTo(slider, target);
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
