(() => {
  const shell = document.querySelector('[data-header-catmenu-shell]');

  if (!shell) {
    return;
  }

  const trigger = document.querySelector('[data-header-category-menu-trigger]');
  const anchor = document.querySelector('.pap-category-menu-anchor');
  const navRow = document.querySelector('.pap-nav-row');
  const menu = shell.querySelector('.pap-header-catmenu');
  const items = Array.from(shell.querySelectorAll('[data-header-catmenu-item]'));
  const panels = Array.from(shell.querySelectorAll('[data-header-catmenu-panel]'));
  const hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
  const mobileQuery = window.matchMedia('(max-width: 980px)');
  const panelSlugs = new Set(panels.map((panel) => panel.getAttribute('data-header-catmenu-panel')).filter(Boolean));

  // MICRO-MOTION: mirrors the --menu-motion-fast/-normal/--menu-ease
  // custom properties in style.css (kept as plain JS values since Web
  // Animations keyframes need numbers/strings, not var() lookups).
  // Every animation below is gated through canAnimate() so reduced-
  // motion users (or a browser without Element.animate) get exactly
  // the previous instant behavior, never a partial/broken animation.
  const MOTION_NORMAL = 220;
  const MOTION_EASE = 'cubic-bezier(0.4, 0, 0.2, 1)';
  const MOTION_L1L2_OFFSET = 16; // px, level 1<->level 2 slide distance
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  const canAnimate = () => !reducedMotionQuery.matches && typeof Element.prototype.animate === 'function';

  const catmenuLeft = shell.querySelector('.pap-header-catmenu-left');
  const catmenuRight = shell.querySelector('.pap-header-catmenu-right');

  let isOpen = false;
  let closeTimer = null;

  const clearCloseTimer = () => {
    if (closeTimer) {
      window.clearTimeout(closeTimer);
      closeTimer = null;
    }
  };

  const setActive = (slug) => {
    const hasPanel = !!(slug && panelSlugs.has(slug));

    // Fara panou activ - fie ca nu s-a facut inca hover pe nicio categorie,
    // fie ca s-a facut hover pe una fara copii (leaf) - meniul se restrange
    // la latimea sidebar-ului, exact ca la eMAG: panoul din dreapta nu ocupa
    // niciun spatiu (nu apare un dreptunghi alb gol) pana nu treci mouse-ul
    // peste o categorie care chiar are subcategorii.
    shell.classList.toggle('is-leaf-active', !hasPanel);

    if (!slug) {
      items.forEach((item) => {
        item.classList.remove('is-active');
        item.setAttribute('aria-expanded', 'false');
      });

      panels.forEach((panel) => {
        panel.classList.remove('is-active');
        panel.hidden = true;
      });
      return;
    }

    items.forEach((item) => {
      const isItemActive = item.getAttribute('data-header-catmenu-target') === slug;
      item.classList.toggle('is-active', isItemActive);
      const itemHasPanel = item.getAttribute('data-header-catmenu-has-children') === '1' && hasPanel;
      item.setAttribute('aria-expanded', isItemActive && itemHasPanel ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const isPanelActive = panel.getAttribute('data-header-catmenu-panel') === slug;
      panel.classList.toggle('is-active', isPanelActive);
      panel.hidden = !isPanelActive;
    });
  };

  // Fara niciun slug la deschidere - meniul porneste restrans la sidebar
  // (vezi is-leaf-active in setActive()), fara panou lateral, exact ca la
  // eMAG. Panoul apare abia cand utilizatorul trece mouse-ul peste o
  // categorie anume care are subcategorii.
  const openMenu = (slug = '') => {
    clearCloseTimer();
    isOpen = true;
    shell.hidden = false;
    if (menu) {
      menu.hidden = false;
    }
    shell.classList.add('is-open');
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'true');
    }
    setActive(slug);
  };

  const closeMenu = () => {
    clearCloseTimer();
    isOpen = false;
    shell.classList.remove('is-open');
    shell.classList.remove('is-leaf-active');
    shell.hidden = true;
    if (menu) {
      menu.hidden = true;
    }
    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }
    panels.forEach((panel) => {
      panel.hidden = true;
    });
    // exitDrilldown is declared further down in this same closure;
    // closeMenu is never invoked before that declaration runs (only via
    // later events or the final call at the bottom of this IIFE), so the
    // reference is always initialized by the time this body executes.
    exitDrilldown();
  };

  const scheduleClose = () => {
    clearCloseTimer();

    // Same desktop-only intent as the pointerover listener below: a real
    // mouse still reports hover:hover/pointer:fine at mobile widths, so
    // without this guard a mouseleave off the shell/menu (e.g. the cursor
    // leaving the page to pick an element in DevTools) would schedule
    // closeMenu() and blank out the mobile drawer's category shell.
    if (!hoverQuery.matches || mobileQuery.matches) {
      return;
    }

    closeTimer = window.setTimeout(() => {
      closeMenu();
    }, 120);
  };

  if (trigger) {
    trigger.addEventListener('mouseenter', () => {
      if (hoverQuery.matches) {
        openMenu();
      }
    });

    trigger.addEventListener('focus', () => openMenu());
    trigger.addEventListener('click', () => {
      if (hoverQuery.matches) {
        return;
      }

      if (isOpen) {
        closeMenu();
        return;
      }

      openMenu();
    });
  }

  shell.addEventListener('mouseenter', clearCloseTimer);
  shell.addEventListener('mouseleave', scheduleClose);
  menu && menu.addEventListener('mouseenter', clearCloseTimer);
  menu && menu.addEventListener('mouseleave', scheduleClose);

  items.forEach((item) => {
    const slug = item.getAttribute('data-header-catmenu-target');

    item.addEventListener('mouseenter', () => {
      // Ambient hover only means something for the desktop flyout; on
      // mobile it would otherwise let a real mouse silently swap the
      // active panel just by passing over an item (e.g. while inspecting
      // in DevTools), fighting the explicit tap-driven drilldown below.
      if (mobileQuery.matches) {
        return;
      }

      openMenu(slug);
    });

    item.addEventListener('focus', () => {
      if (mobileQuery.matches) {
        return;
      }

      openMenu(slug);
    });

    // Touch tablets land here too: wide enough for the two-column desktop
    // flyout (not the narrow drill-down below), but with no real hover to
    // reveal a category's panel before the tap navigates. Without this, a
    // tap on a category with children just followed its link straight
    // through - there was no way to see the panel at all. Same behavior
    // as eMAG: a tap on a parent with children only ever opens its panel,
    // never navigates directly - "Vezi toate produsele" inside the panel
    // is the way to reach the parent's own page.
    const hasChildren = item.getAttribute('data-header-catmenu-has-children') === '1';
    if (hasChildren) {
      item.addEventListener('click', (event) => {
        if (hoverQuery.matches || mobileQuery.matches) {
          return;
        }

        event.preventDefault();
        openMenu(slug);
      });
    }
  });

  // Mobile drill-down: tapping a level-1 category with children swaps the
  // list for its panel - a separate "screen" with a back button - instead
  // of navigating or expanding inline. Within that screen, a level-2 group
  // with its own children (level 3) stays a simple in-place accordion
  // rather than drilling into a third screen. Gated to the same breakpoint
  // as the drawer itself, so a real mouse at a wider width keeps the
  // desktop hover flyout untouched.
  const backButton = document.querySelector('[data-header-catmenu-back]');
  const backLabel = backButton ? backButton.querySelector('[data-header-catmenu-back-label]') : null;
  const groupToggles = Array.from(shell.querySelectorAll('[data-header-catmenu-group-toggle]'));
  const columnToggles = Array.from(shell.querySelectorAll('[data-header-catmenu-column-toggle]'));

  const collapseGroups = () => {
    groupToggles.forEach((toggle) => {
      toggle.setAttribute('aria-expanded', 'false');
      const group = toggle.closest('.pap-header-catmenu-group');
      if (group) {
        group.classList.remove('is-expanded');
      }
    });
  };

  const collapseColumns = () => {
    columnToggles.forEach((toggle) => {
      toggle.setAttribute('aria-expanded', 'false');
      const column = toggle.closest('.pap-showcase-panel-columns-column');
      if (column) {
        column.classList.remove('is-expanded');
      }
    });
  };

  const exitDrilldown = () => {
    if (navRow) {
      navRow.classList.remove('is-drilldown');
    }
    if (backButton) {
      backButton.hidden = true;
    }
    collapseGroups();
    collapseColumns();
    setActive('');
  };

  // MICRO-MOTION: level 1 <-> level 2. `direction` picks which side
  // slides out which way; `applyState` is the real, synchronous state
  // change (setActive/is-drilldown/backButton, or exitDrilldown) - it
  // always runs immediately regardless of whether the animation itself
  // plays, so the DOM is never one tap behind what was actually pressed
  // even under rapid repeated taps. Falls back to applying that state
  // change with no animation at all when reduced motion is on or either
  // side isn't found.
  let catmenuSwitchAnimations = [];

  const animateCatmenuSwitch = (direction, applyState) => {
    if (!canAnimate() || !catmenuLeft || !catmenuRight || !navRow) {
      applyState();
      return;
    }

    catmenuSwitchAnimations.forEach((anim) => anim.cancel());

    const leaving = direction === 'forward' ? catmenuLeft : catmenuRight;
    const entering = direction === 'forward' ? catmenuRight : catmenuLeft;
    const leavingOffset = direction === 'forward' ? -MOTION_L1L2_OFFSET : MOTION_L1L2_OFFSET;
    const enteringOffset = direction === 'forward' ? MOTION_L1L2_OFFSET : -MOTION_L1L2_OFFSET;

    navRow.classList.remove('pap-catmenu-transitioning');
    catmenuLeft.classList.remove('pap-catmenu-leaving');
    catmenuRight.classList.remove('pap-catmenu-leaving');

    navRow.classList.add('pap-catmenu-transitioning');
    leaving.classList.add('pap-catmenu-leaving');

    // The real state change - flips .is-drilldown, updates the back
    // button, sets which panel/slug is active. Runs while the override
    // classes above are already in place, so nothing flashes hidden
    // before the animation gets a chance to play it out.
    applyState();

    const leavingAnim = leaving.animate(
      [
        { transform: 'translateX(0)', opacity: 1 },
        { transform: `translateX(${leavingOffset}px)`, opacity: 0 },
      ],
      { duration: MOTION_NORMAL, easing: MOTION_EASE, fill: 'forwards' }
    );

    const enteringAnim = entering.animate(
      [
        { transform: `translateX(${enteringOffset}px)`, opacity: 0 },
        { transform: 'translateX(0)', opacity: 1 },
      ],
      { duration: MOTION_NORMAL, easing: MOTION_EASE }
    );

    catmenuSwitchAnimations = [leavingAnim, enteringAnim];

    leavingAnim.onfinish = () => {
      leaving.classList.remove('pap-catmenu-leaving');
      navRow.classList.remove('pap-catmenu-transitioning');
    };
  };

  items.forEach((item) => {
    const slug = item.getAttribute('data-header-catmenu-target');
    const hasChildren = item.getAttribute('data-header-catmenu-has-children') === '1';

    if (!hasChildren) {
      return;
    }

    item.addEventListener('click', (event) => {
      if (!mobileQuery.matches) {
        return;
      }

      event.preventDefault();

      animateCatmenuSwitch('forward', () => {
        setActive(slug);

        if (navRow) {
          navRow.classList.add('is-drilldown');
        }

        if (backButton) {
          backButton.hidden = false;
          if (backLabel) {
            const label = item.querySelector('.pap-header-catmenu-label');
            backLabel.textContent = label ? label.textContent : '';
          }
        }
      });
    });
  });

  if (backButton) {
    backButton.addEventListener('click', () => {
      animateCatmenuSwitch('backward', () => {
        exitDrilldown();
      });
    });
  }

  // MICRO-MOTION: accordion open/close. `container` is what carries
  // .is-expanded (drives the chevron rotation via the existing
  // aria-expanded CSS rules, untouched by this) and what the transient
  // .pap-catmenu-accordion-transitioning override targets; `content` is
  // the element whose real, measured scrollHeight is animated between
  // 0 and its natural height - no hardcoded/arbitrary max, and no
  // display:none jump cutting the animation off (see the CSS rule
  // pairs above .pap-header-catmenu-group--expandable:not(.is-expanded)
  // and .pap-showcase-panel-columns-column:not(.is-expanded)). Falls
  // back to the previous instant class toggle under reduced motion.
  const animateAccordion = (container, content, toggle, expanding) => {
    toggle.setAttribute('aria-expanded', expanding ? 'true' : 'false');

    if (!canAnimate()) {
      container.classList.toggle('is-expanded', expanding);
      return;
    }

    content.getAnimations().forEach((anim) => anim.cancel());
    container.classList.remove('pap-catmenu-accordion-transitioning');
    content.style.overflow = '';

    if (expanding) {
      container.classList.add('is-expanded');
      const targetHeight = content.scrollHeight;
      content.style.overflow = 'hidden';

      content.animate(
        [
          { height: '0px', opacity: 0 },
          { height: `${targetHeight}px`, opacity: 1 },
        ],
        { duration: MOTION_NORMAL, easing: MOTION_EASE }
      ).onfinish = () => {
        content.style.overflow = '';
      };
    } else {
      const startHeight = content.scrollHeight;
      container.classList.add('pap-catmenu-accordion-transitioning');
      container.classList.remove('is-expanded');
      content.style.overflow = 'hidden';

      content.animate(
        [
          { height: `${startHeight}px`, opacity: 1 },
          { height: '0px', opacity: 0 },
        ],
        { duration: MOTION_NORMAL, easing: MOTION_EASE, fill: 'forwards' }
      ).onfinish = () => {
        content.style.overflow = '';
        container.classList.remove('pap-catmenu-accordion-transitioning');
      };
    }
  };

  groupToggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      if (!mobileQuery.matches) {
        return;
      }

      event.preventDefault();

      const group = toggle.closest('.pap-header-catmenu-group');
      const content = group ? group.querySelector('.pap-header-catmenu-sublist') : null;
      if (!group || !content) {
        return;
      }

      const wasExpanded = group.classList.contains('is-expanded');
      animateAccordion(group, content, toggle, !wasExpanded);
    });
  });

  // Column-heading accordions (e.g. "Articole pentru birou"'s "Instrumente
  // și accesorii de birou" / "Echipamente de birou") only ever allow one
  // open at a time - opening one collapses whichever other one was open in
  // the same panel.
  columnToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
      if (!mobileQuery.matches) {
        return;
      }

      const column = toggle.closest('.pap-showcase-panel-columns-column');
      if (!column) {
        return;
      }

      const wasExpanded = column.classList.contains('is-expanded');
      const panel = toggle.closest('.pap-header-catmenu-panel');
      const scopedToggles = panel
        ? columnToggles.filter((candidate) => panel.contains(candidate))
        : columnToggles;

      scopedToggles.forEach((candidate) => {
        if (candidate === toggle) {
          return;
        }

        const candidateColumn = candidate.closest('.pap-showcase-panel-columns-column');
        const candidateContent = candidateColumn
          ? candidateColumn.querySelector('.pap-showcase-panel-columns-column-groups')
          : null;

        if (!candidateColumn || !candidateContent) {
          return;
        }

        if (candidateColumn.classList.contains('is-expanded')) {
          animateAccordion(candidateColumn, candidateContent, candidate, false);
        }
      });

      const content = column.querySelector('.pap-showcase-panel-columns-column-groups');
      if (content) {
        animateAccordion(column, content, toggle, !wasExpanded);
      }
    });
  });

  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', (event) => {
      if (!event.matches) {
        exitDrilldown();
      }
    });
  }

  document.addEventListener('pointerover', (event) => {
    // Desktop-only "close the flyout when the mouse wanders off elsewhere
    // on the page" behavior. hover:hover/pointer:fine reflects the input
    // device (a real mouse), not the viewport width, so it stays true even
    // when a desktop browser is narrowed or DevTools device mode emulates
    // a mobile width - without this guard, the exact same shell reused by
    // the off-canvas drawer would close itself the instant the cursor
    // crossed anything outside the anchor (e.g. while inspecting an
    // element), even though the mobile drawer should only ever close via
    // its own explicit controls (X, overlay tap, back button).
    if (!hoverQuery.matches || !isOpen || mobileQuery.matches) {
      return;
    }

    const target = event.target;

    if (!(target instanceof Element)) {
      return;
    }

    if (anchor && anchor.contains(target)) {
      return;
    }

    closeMenu();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen) {
      closeMenu();
      trigger && trigger.focus();
    }
  });

  if (hoverQuery.addEventListener) {
    hoverQuery.addEventListener('change', () => {
      if (!hoverQuery.matches) {
        clearCloseTimer();
      }
    });
  }

  closeMenu();

  // Exposed so the off-canvas drawer (separate IIFE below) can auto-expand
  // the category list the moment the drawer opens (no separate "Toate
  // categoriile" tap on mobile - see openDrawer()) and reset it when the
  // whole drawer closes. Opens with the real current category highlighted
  // (data-header-catmenu-active-slug, server-rendered - empty outside an
  // actual category page) rather than always starting blank.
  window.papOpenCategoryMenu = () => {
    openMenu(shell.getAttribute('data-header-catmenu-active-slug') || '');
  };
  window.papCloseCategoryMenu = closeMenu;
})();

// Mobile hamburger + off-canvas nav drawer. Reuses the site's shared modal
// stack (window.papModalManager) for Escape-to-close and body scroll lock,
// same mechanism as the cart drawer.
(() => {
  const trigger = document.querySelector('[data-mobile-menu-trigger]');
  const panel = document.querySelector('[data-mobile-nav-panel]');
  const overlay = document.querySelector('[data-mobile-nav-overlay]');

  if (!trigger || !panel || !overlay) {
    return;
  }

  const closeButtons = Array.from(panel.querySelectorAll('[data-mobile-nav-close]'));
  const modalManager = window.papModalManager || null;
  const desktopQuery = window.matchMedia('(min-width: 981px)');

  let isOpen = false;
  let hideTimer = null;

  const openDrawer = () => {
    if (isOpen) {
      return;
    }

    isOpen = true;
    window.clearTimeout(hideTimer);
    overlay.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    // Force a reflow between un-hiding the overlay and adding the
    // transition-triggering classes, so the slide-in/fade-in animate
    // instead of jumping straight to their open state.
    void overlay.offsetHeight;
    panel.classList.add('is-open');
    overlay.classList.add('is-open');

    if (modalManager) {
      modalManager.open(panel, closeDrawer, { focusTarget: trigger });
    }

    // No separate "Toate categoriile" tap on mobile - the drawer opens
    // straight into the category list.
    if (!desktopQuery.matches && typeof window.papOpenCategoryMenu === 'function') {
      window.papOpenCategoryMenu();
    }
  };

  function closeDrawer() {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    panel.classList.remove('is-open');
    overlay.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');

    if (typeof window.papCloseCategoryMenu === 'function') {
      window.papCloseCategoryMenu();
    }

    if (modalManager) {
      modalManager.close(panel);
    }

    window.clearTimeout(hideTimer);
    hideTimer = window.setTimeout(() => {
      overlay.hidden = true;
    }, 300);
  }

  trigger.addEventListener('click', () => {
    if (isOpen) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  overlay.addEventListener('click', closeDrawer);
  closeButtons.forEach((button) => {
    button.addEventListener('click', closeDrawer);
  });

  Array.from(panel.querySelectorAll('a[href]')).forEach((link) => {
    // A category row with children has its own click handler (accordion
    // expand/collapse, registered before this one) that calls
    // preventDefault() instead of navigating. Only close the drawer for
    // clicks that actually go somewhere.
    link.addEventListener('click', (event) => {
      if (event.defaultPrevented) {
        return;
      }

      closeDrawer();
    });
  });

  if (desktopQuery.addEventListener) {
    desktopQuery.addEventListener('change', (event) => {
      if (event.matches) {
        closeDrawer();
      }
    });
  }

  // Belt-and-braces alongside the matchMedia listener above: some
  // environments (older browsers, certain automation/embedding contexts)
  // don't reliably fire matchMedia 'change'. A plain resize check costs
  // nothing extra and guarantees the drawer/overlay/body-lock never gets
  // stuck open if the viewport crosses back into desktop territory.
  window.addEventListener('resize', () => {
    if (desktopQuery.matches) {
      closeDrawer();
    }
  });
})();
