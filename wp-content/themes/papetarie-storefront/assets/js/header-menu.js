(() => {
  const shell = document.querySelector('[data-header-catmenu-shell]');

  if (!shell) {
    return;
  }

  const trigger = document.querySelector('[data-header-category-menu-trigger]');
  const anchor = document.querySelector('.pap-category-menu-anchor');
  const menu = shell.querySelector('.pap-header-catmenu');
  const items = Array.from(shell.querySelectorAll('[data-header-catmenu-item]'));
  const panels = Array.from(shell.querySelectorAll('[data-header-catmenu-panel]'));
  const hoverQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
  const panelSlugs = new Set(panels.map((panel) => panel.getAttribute('data-header-catmenu-panel')).filter(Boolean));
  const activeItem = shell.querySelector('.pap-header-catmenu-item.is-active');
  const activeItemSlug = activeItem && activeItem.getAttribute('data-header-catmenu-target');
  const activeItemHasPanel = !!(activeItemSlug && panelSlugs.has(activeItemSlug));
  let fallbackSlug = '';

  for (let index = 0; index < items.length; index += 1) {
    const candidateSlug = items[index].getAttribute('data-header-catmenu-target') || '';

    if (candidateSlug && panelSlugs.has(candidateSlug)) {
      fallbackSlug = candidateSlug;
      break;
    }
  }

  const defaultSlug = activeItemHasPanel ? activeItemSlug : fallbackSlug;

  let isOpen = false;
  let closeTimer = null;

  const clearCloseTimer = () => {
    if (closeTimer) {
      window.clearTimeout(closeTimer);
      closeTimer = null;
    }
  };

  const setActive = (slug) => {
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
      const itemHasPanel = item.getAttribute('data-header-catmenu-has-children') === '1' && panelSlugs.has(slug);
      item.setAttribute('aria-expanded', isItemActive && itemHasPanel ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const isPanelActive = panel.getAttribute('data-header-catmenu-panel') === slug;
      panel.classList.toggle('is-active', isPanelActive);
      panel.hidden = !isPanelActive;
    });
  };

  const openMenu = (slug = defaultSlug) => {
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
        openMenu(defaultSlug);
      }
    });

    trigger.addEventListener('focus', () => openMenu(defaultSlug));
    trigger.addEventListener('click', () => {
      if (hoverQuery.matches) {
        return;
      }

      if (isOpen) {
        closeMenu();
        return;
      }

      openMenu(defaultSlug);
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
})();
