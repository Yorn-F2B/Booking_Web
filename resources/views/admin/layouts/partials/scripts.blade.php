<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (typeof flatpickr !== 'undefined') {

            document.querySelectorAll('input[type="date"]').forEach(function (input) {

                flatpickr(input, {
                    locale: 'vn',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    minDate: input.hasAttribute('data-no-min') ? null : 'today',
                    allowInput: false,

                    onChange: function (selectedDates) {

                        if (input.name !== 'check_in_date') {
                            return;
                        }

                        const form = input.closest('form');

                        if (!form) {
                            return;
                        }

                        const checkOutInput = form.querySelector(
                            'input[name="check_out_date"]'
                        );

                        if (
                            !checkOutInput ||
                            !checkOutInput._flatpickr ||
                            selectedDates.length === 0
                        ) {
                            return;
                        }

                        const nextDay = new Date(selectedDates[0]);

                        nextDay.setDate(nextDay.getDate() + 1);

                        checkOutInput._flatpickr.set(
                            'minDate',
                            nextDay
                        );

                        const currentCheckout =
                            checkOutInput._flatpickr.selectedDates[0];

                        if (
                            !currentCheckout ||
                            currentCheckout <= selectedDates[0]
                        ) {
                            checkOutInput._flatpickr.setDate(
                                nextDay,
                                true
                            );
                        }
                    }
                });

            });

        }

    });
</script>