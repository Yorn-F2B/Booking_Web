<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        AOS.init({
            duration: 700,
            once: true
        });

        if (typeof Swiper !== 'undefined') {
            new Swiper('.roomsSwiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                breakpoints: {
                    768: {
                        slidesPerView: 2
                    },
                    1200: {
                        slidesPerView: 3
                    }
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                },
            });
        }

        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('input[type="date"]').forEach(function (input) {
                flatpickr(input, {
                    locale: 'vn',

                    // Giá trị thật gửi lên Laravel vẫn là Y-m-d
                    dateFormat: 'Y-m-d',

                    // Cái người dùng nhìn thấy là d/m/Y
                    altInput: true,
                    altFormat: 'd/m/Y',

                    minDate: 'today',
                    allowInput: false,

                    onChange: function (selectedDates, dateStr, instance) {
                        if (input.name !== 'check_in_date') {
                            return;
                        }

                        const form = input.closest('form');

                        if (!form) {
                            return;
                        }

                        const checkOutInput = form.querySelector('input[name="check_out_date"]');

                        if (!checkOutInput || !checkOutInput._flatpickr || selectedDates.length === 0) {
                            return;
                        }

                        const nextDay = new Date(selectedDates[0]);
                        nextDay.setDate(nextDay.getDate() + 1);

                        checkOutInput._flatpickr.set('minDate', nextDay);

                        const currentCheckout = checkOutInput._flatpickr.selectedDates[0];

                        if (!currentCheckout || currentCheckout <= selectedDates[0]) {
                            checkOutInput._flatpickr.setDate(nextDay, true);
                        }
                    }
                });
            });
        }
    });
</script>