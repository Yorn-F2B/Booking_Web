/* ============================================================
   MCuong Hotel — main.js (chỉ làm đẹp giao diện, không logic)
   ============================================================ */

// ---------- Scroll progress bar ----------
(function () {
  const bar = document.getElementById('scroll-progress');
  if (!bar) return;
  window.addEventListener('scroll', function () {
    const scrolled = window.scrollY;
    const total = document.documentElement.scrollHeight - window.innerHeight;
    bar.style.width = total > 0 ? (scrolled / total * 100) + '%' : '0%';
  }, { passive: true });
})();

// ---------- Header scroll class ----------
(function () {
  const header = document.querySelector('.site-header');
  if (!header) return;
  const onScroll = function () {
    header.classList.toggle('scrolled', window.scrollY > 30);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

// ---------- AOS ----------
if (typeof AOS !== 'undefined') {
  AOS.init({ once: true, duration: 700, easing: 'ease-out-quart' });
} else {
  document.querySelectorAll('[data-aos]').forEach(function (el) {
    el.removeAttribute('data-aos');
    el.removeAttribute('data-aos-delay');
    el.removeAttribute('data-aos-duration');
  });
}

// ---------- Initialize all Swiper sliders ----------
function initAllSwipers() {
  // Swiper: rooms slider (trang chủ)
  if (document.querySelector('.roomsSwiper') && typeof Swiper !== 'undefined') {
    new Swiper('.roomsSwiper', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: true,
      pagination: { el: '.swiper-pagination', clickable: true },
      navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
      breakpoints: { 768: { slidesPerView: 2 }, 992: { slidesPerView: 3 } },
    });
  }
  
  // Swiper: room gallery
  if (typeof Swiper !== 'undefined') {
    document.querySelectorAll('.roomGallerySwiper').forEach(function (el) {
      new Swiper(el, {
        slidesPerView: 1,
        loop: true,
        spaceBetween: 0,
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
      });
    });
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAllSwipers);
} else {
  initAllSwipers();
}

// ---------- Toggle hiện/ẩn mật khẩu ----------
function togglePwd(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bx bx-hide';
  } else {
    input.type = 'password';
    icon.className = 'bx bx-show';
  }
}

// ---------- Kiểm tra độ mạnh mật khẩu ----------
function checkPwdStrength(val) {
  const bar = document.getElementById('pwdStrengthBar');
  const label = document.getElementById('pwdStrengthLabel');
  if (!bar || !label) return;
  
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  
  const levels = [
    { w: '0%',   bg: 'transparent', text: '' },
    { w: '25%',  bg: '#ef4444',     text: 'Yếu' },
    { w: '50%',  bg: '#f97316',     text: 'Trung bình' },
    { w: '75%',  bg: '#eab308',     text: 'Khá mạnh' },
    { w: '100%', bg: '#22c55e',     text: 'Mạnh' },
  ];
  const lv = levels[score] || levels[0];
  bar.style.width = lv.w;
  bar.style.background = lv.bg;
  label.textContent = lv.text;
  label.style.color = lv.bg;
}

// ---------- Avatar preview khi chọn file ----------
document.addEventListener('DOMContentLoaded', function() {
  const avatarInput = document.getElementById('avatarInput');
  if (avatarInput) {
    avatarInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      if (!file.type.startsWith('image/')) {
        alert('Vui lòng chọn file ảnh (jpg, png, webp...)');
        return;
      }
      const reader = new FileReader();
      reader.onload = function (e) {
        const avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview) avatarPreview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  // Click ảnh cũng mở file picker
  const avatarPreview = document.getElementById('avatarPreview');
  if (avatarPreview) {
    avatarPreview.addEventListener('click', function () {
      if (avatarInput) avatarInput.click();
    });
  }
});

// ---------- Toast helper (chỉ để demo) ----------
function showToast(message, type) {
  type = type || 'info';
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = [
      'position:fixed', 'bottom:1.5rem', 'right:1.5rem',
      'z-index:9999', 'display:flex', 'flex-direction:column', 'gap:.5rem',
      'max-width:340px', 'width:calc(100% - 2rem)'
    ].join(';');
    document.body.appendChild(container);
  }

  const colors = {
    success: { bg: '#0b1628', border: '#c9a84c', icon: '✓' },
    error:   { bg: '#7f1d1d', border: '#f87171', icon: '✕' },
    info:    { bg: '#1e3a5f', border: '#60a5fa', icon: 'i' },
  };
  const c = colors[type] || colors.info;

  const toast = document.createElement('div');
  toast.style.cssText = [
    'background:' + c.bg,
    'border:1px solid ' + c.border,
    'color:#fff',
    'padding:.85rem 1rem',
    'border-radius:.75rem',
    'font-size:.85rem',
    'display:flex', 'align-items:flex-start', 'gap:.65rem',
    'box-shadow:0 8px 28px rgba(0,0,0,.25)',
    'animation:toastIn .3s ease',
    'line-height:1.4',
  ].join(';');

  toast.innerHTML =
    '<span style="width:20px;height:20px;border-radius:50%;background:' + c.border +
    ';color:' + c.bg + ';display:inline-flex;align-items:center;justify-content:center;' +
    'font-size:.7rem;font-weight:700;flex-shrink:0;margin-top:1px">' + c.icon + '</span>' +
    '<span>' + message + '</span>';

  if (!document.getElementById('toast-style')) {
    const s = document.createElement('style');
    s.id = 'toast-style';
    s.textContent = '@keyframes toastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}';
    document.head.appendChild(s);
  }

  container.appendChild(toast);
  setTimeout(function () {
    toast.style.transition = 'opacity .3s ease, transform .3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    setTimeout(function () { toast.remove(); }, 320);
  }, 3500);
}