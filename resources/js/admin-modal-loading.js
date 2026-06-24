(() => {
  const spinnerSvg =
    '<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">' +
    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>' +
    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>' +
    '</svg>';

  const guessLoadingText = (btn) => {
    const label = (btn?.dataset?.loadingText || btn?.textContent || '').trim().toLowerCase();
    if (!label) return 'Processing...';
    if (label.includes('delete')) return 'Deleting...';
    if (label.includes('remove')) return 'Removing...';
    if (label.includes('save')) return 'Saving...';
    if (label.includes('update')) return 'Updating...';
    if (label.includes('add')) return 'Adding...';
    if (label.includes('assign')) return 'Assigning...';
    if (label.includes('confirm')) return 'Confirming...';
    return 'Processing...';
  };

  const setLoading = (btn, opts = {}) => {
    if (!btn || btn.dataset.loadingActive === '1') return;

    const text = opts.text || btn.dataset.loadingText || guessLoadingText(btn);

    const isInput = btn.tagName === 'INPUT';
    btn.dataset.loadingActive = '1';
    btn.dataset.loadingOriginalHtml = isInput ? btn.value : btn.innerHTML;
    btn.dataset.loadingOriginalDisabled = btn.disabled ? '1' : '0';
    btn.dataset.loadingOriginalAriaLabel = btn.getAttribute('aria-label') ?? '__NULL__';
    btn.dataset.loadingOriginalMinWidth = btn.style.minWidth || '';
    btn.dataset.loadingOriginalWidth = btn.style.width || '';
    btn.dataset.loadingOriginalHeight = btn.style.height || '';

    const rect = btn.getBoundingClientRect();
    if (rect.width) {
      const lockedWidth = `${rect.width}px`;
      btn.style.minWidth = lockedWidth;
      btn.style.width = lockedWidth;
    }
    if (rect.height) {
      btn.style.height = `${rect.height}px`;
    }

    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    btn.classList.add('opacity-75', 'cursor-wait');

    if (isInput) {
      btn.value = text;
      return;
    }

    const wrapper = document.createElement('span');
    wrapper.className = 'inline-flex w-full items-center justify-center gap-2';
    wrapper.innerHTML = spinnerSvg;

    const textNode = document.createElement('span');
    const textClass = opts.textClass || btn.dataset.loadingTextClass || '';
    if (textClass) {
      textNode.className = textClass;
    }
    textNode.textContent = text;
    wrapper.appendChild(textNode);

    btn.innerHTML = '';
    btn.appendChild(wrapper);

    // If locked size cannot fit spinner + text, fallback to icon-only to avoid clipping.
    if (btn.scrollWidth > btn.clientWidth + 1) {
      textNode.style.display = 'none';
      wrapper.classList.remove('gap-2');
      btn.dataset.loadingIconOnly = '1';
      btn.setAttribute('aria-label', text);
    }
  };

  const clearLoading = (btn) => {
    if (!btn || btn.dataset.loadingActive !== '1') return;

    const isInput = btn.tagName === 'INPUT';

    if (btn.dataset.loadingOriginalHtml !== undefined) {
      if (isInput) {
        btn.value = btn.dataset.loadingOriginalHtml;
      } else {
        btn.innerHTML = btn.dataset.loadingOriginalHtml;
      }
    }

    if (btn.dataset.loadingOriginalDisabled === '1') {
      btn.disabled = true;
    } else {
      btn.disabled = false;
    }

    btn.removeAttribute('aria-busy');
    btn.classList.remove('opacity-75', 'cursor-wait');
    btn.style.minWidth = btn.dataset.loadingOriginalMinWidth ?? '';
    btn.style.width = btn.dataset.loadingOriginalWidth ?? '';
    btn.style.height = btn.dataset.loadingOriginalHeight ?? '';

    if (btn.dataset.loadingOriginalAriaLabel === '__NULL__') {
      btn.removeAttribute('aria-label');
    } else if (btn.dataset.loadingOriginalAriaLabel !== undefined) {
      btn.setAttribute('aria-label', btn.dataset.loadingOriginalAriaLabel);
    }

    delete btn.dataset.loadingActive;
    delete btn.dataset.loadingOriginalHtml;
    delete btn.dataset.loadingOriginalDisabled;
    delete btn.dataset.loadingOriginalAriaLabel;
    delete btn.dataset.loadingOriginalMinWidth;
    delete btn.dataset.loadingOriginalWidth;
    delete btn.dataset.loadingOriginalHeight;
    delete btn.dataset.loadingIconOnly;
  };

  const isInModal = (el) => {
    if (!el) return false;
    const modalRoot = el.closest(
      '#lmsmodal, [aria-modal="true"], [role="dialog"], div[id*="Modal"], div[id*="modal"]'
    );
    if (!modalRoot) return false;
    if (modalRoot === el) return false;
    return true;
  };

  const isSubmitControl = (el) => {
    if (!el) return false;
    if (el.tagName === 'BUTTON') return true;
    if (el.tagName === 'INPUT') {
      const type = (el.type || '').toLowerCase();
      return type === 'submit' || type === 'image';
    }
    return false;
  };

  document.addEventListener(
    'submit',
    (event) => {
      const submitter = event.submitter || document.activeElement;
      if (!isSubmitControl(submitter)) return;
      if (!isInModal(submitter)) return;
      setLoading(submitter);
    },
    true
  );

  window.AdminModalLoading = {
    setLoading,
    clearLoading,
    isInModal,
  };
})();
