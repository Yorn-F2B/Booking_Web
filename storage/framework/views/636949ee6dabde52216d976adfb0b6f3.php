<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="<?php echo e(asset('assets/js/main.js')); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<script src="<?php echo e(asset('assets/js/project-date-picker.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/project-date-picker.js'))); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 700, once: true });
    if (typeof Swiper !== 'undefined') {
        new Swiper('.roomsSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            breakpoints: { 768: { slidesPerView: 2 }, 1200: { slidesPerView: 3 } },
            pagination: { el: '.swiper-pagination', clickable: true },
        });
    }
});
</script>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/user/partials/scripts.blade.php ENDPATH**/ ?>