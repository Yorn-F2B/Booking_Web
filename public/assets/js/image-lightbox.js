(() => {
  'use strict';

  function openImage(src, alt = 'Xem ảnh lớn') {
    let modal = document.getElementById('globalImagePreview');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'globalImagePreview';
      modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99999;display:none;align-items:center;justify-content:center;padding:24px';
      modal.innerHTML = '<button type="button" aria-label="Đóng" style="position:absolute;right:22px;top:15px;background:transparent;border:0;color:#fff;font-size:42px;cursor:pointer">×</button><img style="max-width:96vw;max-height:92vh;object-fit:contain;border-radius:10px" alt="Xem ảnh lớn">';
      modal.addEventListener('click', event => {
        if (event.target === modal || event.target.tagName === 'BUTTON') modal.style.display = 'none';
      });
      document.addEventListener('keydown', event => {
        if (event.key === 'Escape') modal.style.display = 'none';
      });
      document.body.appendChild(modal);
    }
    const image = modal.querySelector('img');
    image.src = src;
    image.alt = alt;
    modal.style.display = 'flex';
  }

  document.addEventListener('click', event => {
    const image = event.target.closest('img[data-image-preview], a.js-image-lightbox img, a[href] img');
    if (!image) return;
    const src = image.dataset.imagePreview || image.closest('a')?.href || image.src;
    if (!src || /\.pdf(?:\?|$)/i.test(src)) return;
    event.preventDefault();
    openImage(src, image.alt || 'Xem ảnh lớn');
  });
})();
