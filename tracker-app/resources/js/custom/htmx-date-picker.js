/** DATE PICKER **/
document.addEventListener('DOMContentLoaded', () => {
    function bindDatePickers() {
        document.querySelectorAll(".date-picker").forEach(function (el) {
            var options = {};
            flatpickr(el, options);
        });
    }
    bindDatePickers();
    document.body.addEventListener('htmx:afterSettle', bindDatePickers);
});

/** DATETIME PICKER **/
document.addEventListener('DOMContentLoaded', () => {
    function bindDatePickers() {
        document.querySelectorAll(".datetime-picker").forEach(function (el) {
            var options = { enableTime: true, };
            flatpickr(el, options);
        });
    }
    bindDatePickers();
    document.body.addEventListener('htmx:afterSettle', bindDatePickers);
});