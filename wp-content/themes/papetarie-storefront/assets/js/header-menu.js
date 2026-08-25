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
  const panelSlugs = new Set(panels.map((panel) => panel.getAttribute('data-header-catmenu-panel')).filter(Boolean));

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

    if (!hoverQuery.matches) {
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
      openMenu(slug);
    });

    item.addEventListener('focus', () => {
      openMenu(slug);
    });
  });

  // Mobile drill-down: tapping a level-1 category with children swaps the
  // list for its panel - a separate "screen" with a back button - instead
  // of navigating or expanding inline. Within that screen, a level-2 group
  // with its own children (level 3) stays a simple in-place accordion
  // rather than drilling into a third screen. Gated to the same breakpoint
  // as the drawer itself, so a real mouse at a wider width keeps the
  // desktop hover flyout untouched.
  const mobileQuery = window.matchMedia('(max-width: 980px)');
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

  if (backButton) {
    backButton.addEventListener('click', () => {
      exitDrilldown();
    });
  }

  groupToggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      if (!mobileQuery.matches) {
        return;
      }

      event.preventDefault();

      const group = toggle.closest('.pap-header-catmenu-group');
      if (!group) {
        return;
      }

      const wasExpanded = group.classList.contains('is-expanded');
      group.classList.toggle('is-expanded', !wasExpanded);
      toggle.setAttribute('aria-expanded', wasExpanded ? 'false' : 'true');
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
        candidate.setAttribute('aria-expanded', 'false');
        const candidateColumn = candidate.closest('.pap-showcase-panel-columns-column');
        if (candidateColumn) {
          candidateColumn.classList.remove('is-expanded');
        }
      });

      if (!wasExpanded) {
        column.classList.add('is-expanded');
        toggle.setAttribute('aria-expanded', 'true');
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
    if (!hoverQuery.matches || !isOpen) {
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
