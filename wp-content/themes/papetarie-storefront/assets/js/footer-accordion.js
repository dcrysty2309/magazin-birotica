(() => {
  const groups = Array.from(document.querySelectorAll('[data-footer-accordion]'));

  if (!groups.length) {
    return;
  }

  const mobileQuery = window.matchMedia('(max-width: 767px)');

  const entries = groups
    .map((group) => ({
      group,
      toggle: group.querySelector('[data-footer-accordion-toggle]'),
      content: group.querySelector('[data-footer-accordion-content]'),
    }))
    .filter((entry) => entry.toggle && entry.content);

  // The open/close motion is CSS-driven (max-height + opacity/transform/
  // visibility transitions in style.css) - JS only ever measures
  // scrollHeight ONCE per click (never per animation frame) and writes
  // it as the transition's target, then flips .is-expanded/
  // aria-expanded. The browser's own transition engine handles every
  // frame in between; prefers-reduced-motion is handled entirely by
  // the CSS, not by branching here.
  //
  // setProperty(..., 'important') is required here: style.css's base
  // rule is `max-height: 0 !important` (matching this file's
  // convention for winning over Storefront/WooCommerce), and a plain
  // (non-important) inline style can never beat an external
  // stylesheet's !important rule, no matter the specificity - the
  // inline write would silently no-op without this.
  const setExpanded = (entry, expanded) => {
    entry.toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    entry.group.classList.toggle('is-expanded', expanded);
    entry.content.style.setProperty(
      'max-height',
      expanded ? `${entry.content.scrollHeight}px` : '0px',
      'important'
    );
  };

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
            setExpanded(other, false);
          }
        });
      }

      setExpanded(entry, expanding);
    });
  });

  // Desktop/tablet never collapse - if a section was left open on a
  // phone and the viewport is then widened past the accordion
  // breakpoint, collapse it back (clearing the inline max-height too)
  // so nothing stale is left over once it's back on mobile again.
  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', (event) => {
      if (event.matches) {
        return;
      }

      entries.forEach((entry) => {
        setExpanded(entry, false);
        entry.content.style.maxHeight = '';
      });
    });
  }
})();
