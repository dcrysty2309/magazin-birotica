(() => {
  const groups = Array.from(document.querySelectorAll('[data-footer-accordion]'));

  if (!groups.length) {
    return;
  }

  const mobileQuery = window.matchMedia('(max-width: 767px)');
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const MOTION_MS = 200;
  const MOTION_EASE = 'cubic-bezier(0.4, 0, 0.2, 1)';
  const canAnimate = () => !reducedMotionQuery.matches && typeof Element.prototype.animate === 'function';

  // Mirrors the mobile category-menu accordion pattern: collapsed is
  // driven by the ABSENCE of .is-expanded on the group (real
  // display:none in CSS, so a broken/blocked script still leaves every
  // section collapsed-but-reachable rather than stuck open), and
  // .pap-footer-accordion-transitioning is a short-lived override that
  // forces the content back to display:block only for the ~200ms
  // animation window - without it, starting the close animation by
  // removing .is-expanded would also flip display:none instantly,
  // cutting the animation off before it could play.
  groups.forEach((group) => {
    const toggle = group.querySelector('[data-footer-accordion-toggle]');
    const content = group.querySelector('[data-footer-accordion-content]');

    if (!toggle || !content) {
      return;
    }

    toggle.addEventListener('click', () => {
      if (!mobileQuery.matches) {
        return;
      }

      const expanding = !group.classList.contains('is-expanded');
      toggle.setAttribute('aria-expanded', expanding ? 'true' : 'false');

      if (!canAnimate()) {
        group.classList.toggle('is-expanded', expanding);
        return;
      }

      content.getAnimations().forEach((anim) => anim.cancel());
      group.classList.remove('pap-footer-accordion-transitioning');
      content.style.overflow = '';

      if (expanding) {
        group.classList.add('is-expanded');
        const targetHeight = content.scrollHeight;
        content.style.overflow = 'hidden';

        content.animate(
          [
            { height: '0px', opacity: 0 },
            { height: `${targetHeight}px`, opacity: 1 },
          ],
          { duration: MOTION_MS, easing: MOTION_EASE }
        ).onfinish = () => {
          content.style.overflow = '';
        };
      } else {
        const startHeight = content.scrollHeight;
        group.classList.add('pap-footer-accordion-transitioning');
        group.classList.remove('is-expanded');
        content.style.overflow = 'hidden';

        content.animate(
          [
            { height: `${startHeight}px`, opacity: 1 },
            { height: '0px', opacity: 0 },
          ],
          { duration: MOTION_MS, easing: MOTION_EASE, fill: 'forwards' }
        ).onfinish = () => {
          content.style.overflow = '';
          group.classList.remove('pap-footer-accordion-transitioning');
        };
      }
    });
  });

  // Desktop/tablet never collapse - if a section was left open on a
  // phone and the viewport is then widened past the accordion
  // breakpoint, clear any leftover inline height/overflow and the
  // transitioning class so nothing stale interferes once it's back on
  // mobile again.
  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', (event) => {
      if (event.matches) {
        return;
      }

      groups.forEach((group) => {
        const content = group.querySelector('[data-footer-accordion-content]');
        group.classList.remove('pap-footer-accordion-transitioning');
        if (content) {
          content.style.height = '';
          content.style.overflow = '';
        }
      });
    });
  }
})();
