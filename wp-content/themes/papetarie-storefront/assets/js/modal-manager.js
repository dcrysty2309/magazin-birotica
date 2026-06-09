(function () {
  if (window.papModalManager) {
    return;
  }

  var stack = [];
  var modalCount = 0;
  var listenerAttached = false;
  var bodyLockClass = 'pap-modal-open';

  function isElement(node) {
    return !!(node && node.nodeType === 1);
  }

  function findRecord(modal) {
    for (var index = 0; index < stack.length; index += 1) {
      if (stack[index].modal === modal) {
        return stack[index];
      }
    }

    return null;
  }

  function moveRecordToTop(record) {
    var index = stack.indexOf(record);
    if (index === -1 || index === stack.length - 1) {
      return;
    }

    stack.splice(index, 1);
    stack.push(record);
  }

  function syncBodyLock() {
    document.body.classList.toggle(bodyLockClass, modalCount > 0);
  }

  function ensureListener() {
    if (listenerAttached) {
      return;
    }

    document.addEventListener('keydown', handleKeydown, true);
    listenerAttached = true;
  }

  function releaseListener() {
    if (!listenerAttached || modalCount > 0) {
      return;
    }

    document.removeEventListener('keydown', handleKeydown, true);
    listenerAttached = false;
  }

  function open(modal, closeFn, options) {
    if (!isElement(modal) || typeof closeFn !== 'function') {
      return null;
    }

    var record = findRecord(modal);
    var focusTarget = options && options.focusTarget ? options.focusTarget : document.activeElement;

    if (!record) {
      record = {
        modal: modal,
        closeFn: closeFn,
        focusTarget: focusTarget && focusTarget !== modal ? focusTarget : null
      };
      stack.push(record);
      modalCount += 1;
    } else {
      record.closeFn = closeFn;
      record.focusTarget = focusTarget && focusTarget !== modal ? focusTarget : record.focusTarget;
      moveRecordToTop(record);
    }

    ensureListener();
    syncBodyLock();
    return record;
  }

  function close(modal) {
    if (!isElement(modal)) {
      return false;
    }

    var record = findRecord(modal);
    if (!record) {
      return false;
    }

    var index = stack.indexOf(record);
    if (index !== -1) {
      stack.splice(index, 1);
    }

    modalCount = Math.max(0, modalCount - 1);
    syncBodyLock();
    releaseListener();
    return true;
  }

  function getTop() {
    return stack.length ? stack[stack.length - 1] : null;
  }

  function handleKeydown(event) {
    if (event.key !== 'Escape') {
      return;
    }

    var top = getTop();
    if (!top || typeof top.closeFn !== 'function') {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    try {
      top.closeFn({
        reason: 'escape',
        focusTarget: top.focusTarget
      });
    } catch (error) {
      if (window.console && typeof window.console.error === 'function') {
        window.console.error(error);
      }
    }
  }

  window.papModalManager = {
    open: open,
    close: close,
    getTop: getTop
  };
})();
