/** DATE PICKER **/
document.addEventListener('DOMContentLoaded', () => {
    function bindDatePickers() {
        document.querySelectorAll(".date-picker").forEach(function (el) {
            var options = { dateFormat: "Y-m-d" };
            flatpickr(el, options);
        });
    }
    bindDatePickers();
    document.body.addEventListener('htmx:afterSettle', bindDatePickers);
    document.body.addEventListener('tracker:date-picker:added', bindDatePickers);
});

/** DATETIME PICKER **/
document.addEventListener('DOMContentLoaded', () => {
    function bindDateTimePickers() {
        document.querySelectorAll(".datetime-picker").forEach(function (el) {
            var options = { enableTime: true, dateFormat: "Y-m-d h:iK" };
            flatpickr(el, options);
        });
    }
    bindDateTimePickers();
    document.body.addEventListener('htmx:afterSettle', bindDateTimePickers);
    document.body.addEventListener('tracker:date-time-picker:added', bindDateTimePickers);
});