(() => {
  const groups = Array.from(document.querySelectorAll('[data-footer-accordion]'));

  if (!groups.length) {
    return;
  }

  const mobileQuery = window.matchMedia('(max-width: 767px)');
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const MOTION_MS = 320;
  const MOTION_EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
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
  const closeGroup = (group, toggle, content) => {
    if (!group.classList.contains('is-expanded')) {
      return;
    }

    toggle.setAttribute('aria-expanded', 'false');

    if (!canAnimate()) {
      group.classList.remove('is-expanded');
      return;
    }

    content.getAnimations().forEach((anim) => anim.cancel());
    const startHeight = content.scrollHeight;
    group.classList.add('pap-footer-accordion-transitioning');
    group.classList.remove('is-expanded');
    content.style.overflow = 'hidden';

    content.animate(
      [
        { height: `${startHeight}px`, opacity: 1, transform: 'translateY(0)' },
        { height: '0px', opacity: 0, transform: 'translateY(-6px)' },
      ],
      { duration: MOTION_MS, easing: MOTION_EASE, fill: 'forwards' }
    ).onfinish = () => {
      content.style.overflow = '';
      group.classList.remove('pap-footer-accordion-transitioning');
    };
  };

  const openGroup = (group, toggle, content) => {
    toggle.setAttribute('aria-expanded', 'true');

    if (!canAnimate()) {
      group.classList.add('is-expanded');
      return;
    }

    content.getAnimations().forEach((anim) => anim.cancel());
    group.classList.remove('pap-footer-accordion-transitioning');
    group.classList.add('is-expanded');
    const targetHeight = content.scrollHeight;
    content.style.overflow = 'hidden';

    content.animate(
      [
        { height: '0px', opacity: 0, transform: 'translateY(-6px)' },
        { height: `${targetHeight}px`, opacity: 1, transform: 'translateY(0)' },
      ],
      { duration: MOTION_MS, easing: MOTION_EASE }
    ).onfinish = () => {
      content.style.overflow = '';
    };
  };

  const entries = groups
    .map((group) => ({
      group,
      toggle: group.querySelector('[data-footer-accordion-toggle]'),
      content: group.querySelector('[data-footer-accordion-content]'),
    }))
    .filter((entry) => entry.toggle && entry.content);

  entries.forEach((entry) => {
    entry.toggle.addEventListener('click', () => {
      if (!mobileQuery.matches) {
        return;
      }

      const expanding = !entry.group.classList.contains('is-expanded');

      if (expanding) {
        // Only one section open at a time - close every other expanded
        // group before opening this one, so the mobile footer never
        // grows past a single expanded section.
        entries.forEach((other) => {
          if (other !== entry) {
            closeGroup(other.group, other.toggle, other.content);
          }
        });
        openGroup(entry.group, entry.toggle, entry.content);
      } else {
        closeGroup(entry.group, entry.toggle, entry.content);
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

      entries.forEach(({ group, content }) => {
        group.classList.remove('pap-footer-accordion-transitioning');
        if (content) {
          content.style.height = '';
          content.style.overflow = '';
        }
      });
    });
  }
})();
