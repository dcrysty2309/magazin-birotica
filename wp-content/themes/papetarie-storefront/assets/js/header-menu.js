(() => {
  const shell = document.querySelector('[data-category-menu-shell]');
  if (!shell) {
    return;
  }

  const navRow = shell.closest('.pap-nav-row');
  const trigger = shell.querySelector('[data-category-menu-trigger]');
  const menu = shell.querySelector('[data-category-menu]');
  const stage = shell.querySelector('.pap-category-menu-panels');
  const items = Array.from(shell.querySelectorAll('[data-category-menu-item]'));
  const panels = Array.from(shell.querySelectorAll('[data-category-menu-panel]'));
  const hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
  const defaultActiveItem = shell.querySelector('.pap-category-menu-nav-item.is-active');
  const defaultSlug = (defaultActiveItem && defaultActiveItem.getAttribute('data-category-menu-target')) || (items[0] && items[0].getAttribute('data-category-menu-target')) || '';

  let isOpen = false;
  let closeTimer = null;

  const clearCloseTimer = () => {
    if (closeTimer) {
      window.clearTimeout(closeTimer);
      closeTimer = null;
    }
  };

  const setPanelVisible = (visible) => {
    if (!menu) {
      return;
    }

    menu.classList.toggle('is-panel-visible', visible);

    if (stage) {
      stage.hidden = !visible;
    }

    if (!visible) {
      panels.forEach((panel) => {
        panel.classList.remove('is-active');
        panel.hidden = true;
      });
    }
  };

  const setActive = (slug, revealPanel = false) => {
    if (!slug) {
      return;
    }

    items.forEach((item) => {
      const isActive = item.getAttribute('data-category-menu-target') === slug;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const isActive = panel.getAttribute('data-category-menu-panel') === slug;
      panel.classList.toggle('is-active', isActive && revealPanel);
      panel.hidden = !(isActive && revealPanel);
    });
  };

  const openMenu = (slug = defaultSlug, revealPanel = false) => {
    if (!menu) {
      return;
    }

    clearCloseTimer();
    isOpen = true;
    menu.hidden = false;
    shell.classList.add('is-open');
    setPanelVisible(revealPanel);

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'true');
    }

    setActive(slug, revealPanel);
  };

  const closeMenu = () => {
    if (!menu) {
      return;
    }

    clearCloseTimer();
    isOpen = false;
    shell.classList.remove('is-open');
    menu.hidden = true;
    setPanelVisible(false);

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }
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

  if (navRow) {
    navRow.addEventListener('mouseenter', () => {
      if (hoverQuery.matches) {
        openMenu(defaultSlug, false);
      }
    });

    navRow.addEventListener('mouseleave', scheduleClose);
  }

  shell.addEventListener('mouseenter', clearCloseTimer);
  shell.addEventListener('mouseleave', scheduleClose);

  if (trigger) {
    trigger.addEventListener('focus', () => {
      openMenu(defaultSlug, false);
    });

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

  menu.addEventListener('mouseenter', clearCloseTimer);
  menu.addEventListener('mouseleave', scheduleClose);
  shell.addEventListener('focusout', (event) => {
    const nextTarget = event.relatedTarget;

    if (!nextTarget || !shell.contains(nextTarget)) {
      scheduleClose();
    }
  });

  items.forEach((item) => {
    const slug = item.getAttribute('data-category-menu-target');

    item.addEventListener('mouseenter', () => {
      if (hoverQuery.matches) {
        openMenu(slug, true);
      }
    });

    item.addEventListener('focus', () => {
      openMenu(slug, true);
    });

    item.addEventListener('click', () => {
      if (hoverQuery.matches) {
        return;
      }

      openMenu(slug, true);
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen) {
      closeMenu();

      if (trigger) {
        trigger.focus();
      }
    }
  });

  if (hoverQuery.addEventListener) {
    hoverQuery.addEventListener('change', () => {
      if (!hoverQuery.matches) {
        clearCloseTimer();
      }
    });
  }

  setPanelVisible(false);
  closeMenu();
})();
