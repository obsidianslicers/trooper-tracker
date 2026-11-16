import { attachDatePicker } from './date-picker.js';

/** DATE PICKER **/
document.addEventListener('DOMContentLoaded', () => {
    function bindDatePickers() {
        document.querySelectorAll(".date-picker").forEach(function (el) {
            attachDatePicker(el);
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
            attachDatePicker(el, { withTime: true });
        });
    }
    bindDateTimePickers();
    document.body.addEventListener('htmx:afterSettle', bindDateTimePickers);
    document.body.addEventListener('tracker:date-time-picker:added', bindDateTimePickers);
});
