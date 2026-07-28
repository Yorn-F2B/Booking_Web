(() => {
  'use strict';

  const MAX_FILES = 5;
  const objectUrls = new WeakMap();

  function setFiles(input, files) {
    const dt = new DataTransfer();
    files.slice(0, MAX_FILES).forEach(file => dt.items.add(file));
    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function previewHost(input) {
    const explicit = input.dataset.previewTarget;
    if (explicit) return document.querySelector(explicit);

    const wrapper = input.closest('[data-file-preview-scope]')
      || input.closest('.col-12, .col-md-6, .mb-3, .field, .form-group')
      || input.parentElement;
    if (!wrapper) return null;

    const key = input.id || input.name;
    let box = wrapper.querySelector(`.js-file-preview[data-preview-for="${CSS.escape(key)}"]`);
    if (!box) {
      box = document.createElement('div');
      box.className = 'js-file-preview d-flex flex-wrap gap-2 mt-2';
      box.dataset.previewFor = key;
      wrapper.appendChild(box);
    }
    return box;
  }

  function clearUrls(input) {
    (objectUrls.get(input) || []).forEach(URL.revokeObjectURL);
    objectUrls.set(input, []);
  }

  function render(input) {
    const box = previewHost(input);
    if (!box) return;

    clearUrls(input);
    box.replaceChildren();
    const urls = [];

    [...input.files].slice(0, MAX_FILES).forEach((file, index) => {
      const item = document.createElement('div');
      item.className = 'position-relative border rounded p-1 bg-white';
      item.style.width = '112px';

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.textContent = '×';
      remove.className = 'btn btn-danger btn-sm position-absolute rounded-circle p-0';
      remove.style.cssText = 'right:-7px;top:-8px;width:24px;height:24px;z-index:2;line-height:20px';
      remove.addEventListener('click', () => setFiles(input, [...input.files].filter((_, i) => i !== index)));
      item.appendChild(remove);

      if (file.type.startsWith('image/')) {
        const url = URL.createObjectURL(file);
        urls.push(url);
        const img = document.createElement('img');
        img.src = url;
        img.alt = file.name;
        img.className = 'w-100 rounded';
        img.style.cssText = 'height:78px;object-fit:cover;cursor:zoom-in';
        img.addEventListener('click', () => window.open(url, '_blank', 'noopener'));
        item.appendChild(img);
      } else {
        const label = document.createElement('div');
        label.textContent = file.type === 'application/pdf' ? 'PDF' : 'Tệp';
        label.className = 'd-flex align-items-center justify-content-center bg-light rounded fw-bold';
        label.style.height = '78px';
        item.appendChild(label);
      }

      const name = document.createElement('div');
      name.textContent = file.name;
      name.title = file.name;
      name.className = 'small text-truncate mt-1';
      item.appendChild(name);
      box.appendChild(item);
    });

    objectUrls.set(input, urls);
  }

  function bind(input) {
    if (input.dataset.previewBound === '1') return;
    input.dataset.previewBound = '1';
    input.addEventListener('change', () => render(input));
    if (input.files?.length) render(input);
  }

  function init() {
    document.querySelectorAll('input[type="file"][data-persistent-files]').forEach(bind);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }

  window.addEventListener('pagehide', () => {
    document.querySelectorAll('input[type="file"][data-persistent-files]').forEach(clearUrls);
  }, { once: true });
})();
