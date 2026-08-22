/* ============================================================
   MCuong Hotel — main.js (chỉ làm đẹp giao diện, không logic)
   ============================================================ */

// ---------- Scroll progress bar ----------
(function () {
    const bar = document.getElementById("scroll-progress");
    if (!bar) return;
    window.addEventListener(
        "scroll",
        function () {
            const scrolled = window.scrollY;
            const total =
                document.documentElement.scrollHeight - window.innerHeight;
            bar.style.width = total > 0 ? (scrolled / total) * 100 + "%" : "0%";
        },
        { passive: true },
    );
})();

// ---------- Header scroll class ----------
(function () {
    const header = document.querySelector(".site-header");
    if (!header) return;
    const onScroll = function () {
        header.classList.toggle("scrolled", window.scrollY > 30);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
})();

// ---------- AOS ----------
if (typeof AOS !== "undefined") {
    AOS.init({ once: true, duration: 700, easing: "ease-out-quart" });
} else {
    document.querySelectorAll("[data-aos]").forEach(function (el) {
        el.removeAttribute("data-aos");
        el.removeAttribute("data-aos-delay");
        el.removeAttribute("data-aos-duration");
    });
}

// ---------- Initialize all Swiper sliders ----------
function initAllSwipers() {
    // Swiper: rooms slider (trang chủ)
    if (
        document.querySelector(".roomsSwiper") &&
        typeof Swiper !== "undefined"
    ) {
        new Swiper(".roomsSwiper", {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            pagination: { el: ".swiper-pagination", clickable: true },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                992: { slidesPerView: 3 },
            },
        });
    }

    // Swiper: room gallery
    if (typeof Swiper !== "undefined") {
        document.querySelectorAll(".roomGallerySwiper").forEach(function (el) {
            new Swiper(el, {
                slidesPerView: 1,
                loop: true,
                spaceBetween: 0,
                pagination: {
                    el: el.querySelector(".swiper-pagination"),
                    clickable: true,
                },
                navigation: {
                    nextEl: el.querySelector(".swiper-button-next"),
                    prevEl: el.querySelector(".swiper-button-prev"),
                },
            });
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAllSwipers);
} else {
    initAllSwipers();
}

// ---------- Toggle hiện/ẩn mật khẩu ----------
function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.className = "bx bx-hide";
    } else {
        input.type = "password";
        icon.className = "bx bx-show";
    }
}

// ---------- Kiểm tra độ mạnh mật khẩu ----------
function checkPwdStrength(val) {
    const bar = document.getElementById("pwdStrengthBar");
    const label = document.getElementById("pwdStrengthLabel");
    if (!bar || !label) return;

    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: "0%", bg: "transparent", text: "" },
        { w: "25%", bg: "#ef4444", text: "Yếu" },
        { w: "50%", bg: "#f97316", text: "Trung bình" },
        { w: "75%", bg: "#eab308", text: "Khá mạnh" },
        { w: "100%", bg: "#22c55e", text: "Mạnh" },
    ];
    const lv = levels[score] || levels[0];
    bar.style.width = lv.w;
    bar.style.background = lv.bg;
    label.textContent = lv.text;
    label.style.color = lv.bg;
}

// ---------- Avatar preview khi chọn file ----------
document.addEventListener("DOMContentLoaded", function () {
    const avatarInput = document.getElementById("avatarInput");
    if (avatarInput) {
        avatarInput.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;
            if (!file.type.startsWith("image/")) {
                alert("Vui lòng chọn file ảnh (jpg, png, webp...)");
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                const avatarPreview = document.getElementById("avatarPreview");
                if (avatarPreview) avatarPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Click ảnh cũng mở file picker
    const avatarPreview = document.getElementById("avatarPreview");
    if (avatarPreview) {
        avatarPreview.addEventListener("click", function () {
            if (avatarInput) avatarInput.click();
        });
    }
});

// ---------- Toast helper: toàn hệ thống dùng toast góc trên ----------
function showToast(message, type) {
    type = type || "info";
    if (window.AppToast && typeof window.AppToast.show === "function") {
        return window.AppToast.show(message, type);
    }

    window.__appToastQueue = window.__appToastQueue || [];
    window.__appToastQueue.push({ message: String(message || ""), type: type, options: {} });
    return null;
}

(function () {
    const input = document.getElementById("avatarInput");
    const preview = document.getElementById("avatarPreview");

    if (!input || !preview) return;

    input.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        // preview ngay
        preview.src = URL.createObjectURL(file);

        // upload
        const formData = new FormData();
        formData.append("avatar", file);
        formData.append(
            "_token",
            document.querySelector('meta[name="csrf-token"]').content,
        );

        fetch("/user-settings/avatar", {
            method: "POST",
            body: formData,
        })
            .then((res) => res.json())
            .then((data) => {
                console.log("Uploaded avatar:", data);
            })
            .catch((err) => console.error(err));
    });
})();
