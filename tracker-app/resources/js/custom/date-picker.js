/**
 * Date and date-time pickers.
 *
 * On a touch-first device (phones, tablets) the field becomes a native `date` or
 * `datetime-local` input, so the phone shows its own picker. Everywhere else it stays a text
 * field and opens a Vanilla Calendar Pro popup.
 *
 * Values the server renders or the event paste parser fills in may be "2030-06-01",
 * "2030-06-01 1:00PM" or "2030-06-01 13:00:00". The text field shows "2030-06-01 1:00PM"
 * (the format flatpickr used); a native field holds "2030-06-01T13:00". The server's `date`
 * rule accepts both.
 */
import { Calendar, time } from 'vanilla-calendar-pro';
import 'vanilla-calendar-pro/styles/core.css';
import 'vanilla-calendar-pro/styles/time.css';

const VALUE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{1,2}):(\d{2})(?::\d{2})?(?:\s*([ap])m?)?)?$/i;

const pad = (number) => String(number).padStart(2, '0');

/**
 * True on a device whose main pointer is a finger. Those get the native pickers.
 */
export function usesNativePicker() {
    return typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(pointer: coarse)').matches;
}

/**
 * Reads any of the accepted value formats, or a Date (the Mercs paste parser returns those).
 *
 * @returns {{date: string, hour: number|null, minute: number|null}|null}
 */
export function parsePickerValue(value) {
    if (value instanceof Date) {
        return isNaN(value)
            ? null
            : {
                date: `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`,
                hour: value.getHours(),
                minute: value.getMinutes(),
            };
    }

    const match = String(value ?? '').trim().match(VALUE_PATTERN);

    if (!match) {
        return null;
    }

    const [, year, month, day, rawHour, rawMinute, meridiem] = match;
    const date = `${year}-${month}-${day}`;

    if (rawHour === undefined) {
        return { date, hour: null, minute: null };
    }

    let hour = Number(rawHour);
    const minute = Number(rawMinute);

    if (meridiem) {
        if (hour < 1 || hour > 12) {
            return null;
        }
        hour = (hour % 12) + (meridiem.toLowerCase() === 'p' ? 12 : 0);
    }

    return hour <= 23 && minute <= 59 ? { date, hour, minute } : null;
}

function twelveHour(hour) {
    return { hour: hour % 12 === 0 ? 12 : hour % 12, meridiem: hour >= 12 ? 'PM' : 'AM' };
}

/**
 * The text field's format: "2030-06-01", or "2030-06-01 1:00PM" with a time.
 */
export function toTextValue(parsed, withTime) {
    if (!parsed) {
        return '';
    }
    if (!withTime) {
        return parsed.date;
    }

    const { hour, meridiem } = twelveHour(parsed.hour ?? 0);

    return `${parsed.date} ${hour}:${pad(parsed.minute ?? 0)}${meridiem}`;
}

/**
 * The native field's format: "2030-06-01", or "2030-06-01T13:00" with a time (midnight when
 * the value had no time, as flatpickr assumed).
 */
export function toNativeValue(parsed, withTime) {
    if (!parsed) {
        return '';
    }
    if (!withTime) {
        return parsed.date;
    }

    return `${parsed.date}T${pad(parsed.hour ?? 0)}:${pad(parsed.minute ?? 0)}`;
}

/**
 * A value in the format this device's picker field takes. For code that writes into a picker
 * field directly (e.g. from Svelte), since a native field drops values it can't read.
 */
export function formatForPicker(value, { withTime = false } = {}) {
    const parsed = parsePickerValue(value);

    if (!parsed) {
        return value;
    }

    return usesNativePicker() ? toNativeValue(parsed, withTime) : toTextValue(parsed, withTime);
}

function calendarSelection(parsed, withTime) {
    if (!parsed) {
        return { selectedDates: [] };
    }

    const [year, month] = parsed.date.split('-').map(Number);
    const selection = { selectedDates: [parsed.date], selectedYear: year, selectedMonth: month - 1 };

    if (withTime) {
        const { hour, meridiem } = twelveHour(parsed.hour ?? 0);
        selection.selectedTime = `${pad(hour)}:${pad(parsed.minute ?? 0)} ${meridiem}`;
    }

    return selection;
}

function notify(input) {
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Turns a text input into a date (or date-time) picker. Calling it again on the same input
 * re-reads the input's value instead of attaching a second picker.
 *
 * @param {HTMLInputElement} input
 * @param {{withTime?: boolean, onChange?: (value: string) => void}} options
 * @returns {{sync: () => void, destroy: () => void}}
 */
export function attachDatePicker(input, { withTime = false, onChange = null } = {}) {
    if (input._trackerDatePicker) {
        input._trackerDatePicker.sync();
        return input._trackerDatePicker;
    }

    const picker = usesNativePicker()
        ? attachNative(input, withTime, onChange)
        : attachCalendar(input, withTime, onChange);

    input._trackerDatePicker = picker;

    return picker;
}

function attachNative(input, withTime, onChange) {
    input.type = withTime ? 'datetime-local' : 'date';
    input.removeAttribute('placeholder');
    input.dataset.picker = 'native';

    const sync = () => {
        const native = toNativeValue(parsePickerValue(input.value), withTime);
        if (native !== input.value) {
            input.value = native;
        }
    };
    const changed = () => onChange?.(input.value);

    sync();
    input.addEventListener('change', changed);

    return {
        sync,
        destroy() {
            input.removeEventListener('change', changed);
            delete input._trackerDatePicker;
        },
    };
}

function attachCalendar(input, withTime, onChange) {
    input.dataset.picker = 'calendar';
    input.autocomplete = 'off';

    // the server renders "2030-06-01 13:00:00"; show it as the field's own format
    const initial = parsePickerValue(input.value);
    if (initial && toTextValue(initial, withTime) !== input.value) {
        input.value = toTextValue(initial, withTime);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    let writing = false;

    const calendar = new Calendar(input, {
        inputMode: true,
        positionToInput: 'auto',
        // the tracker is always dark
        selectedTheme: 'dark',
        // a US calendar: weeks start on Sunday, and weekends aren't singled out in red
        firstWeekday: 0,
        selectedWeekends: [],
        timeStepMinute: 5,
        extensions: withTime ? [time] : [],
        selectionTimeMode: withTime ? 12 : false,
        ...calendarSelection(parsePickerValue(input.value), withTime),
        onChangeToInput(self) {
            const date = self.context.selectedDates[0];
            const parsed = date ? parsePickerValue(withTime ? `${date} ${self.context.selectedTime}` : date) : null;

            writing = true;
            input.value = toTextValue(parsed, withTime);
            notify(input);
            writing = false;
            onChange?.(input.value);

            // a date alone is done once it's picked; with a time the popup stays open for it
            if (!withTime && date) {
                self.hide();
            }
        },
    });
    calendar.init();

    // typed by hand, or set by other code: point the calendar at it
    const sync = () => {
        if (writing) {
            return;
        }
        const selection = calendarSelection(parsePickerValue(input.value), withTime);
        calendar.set(selection, { dates: true, month: true, year: true, time: true });
    };
    const typed = () => {
        if (writing) {
            return;
        }
        sync();
        onChange?.(input.value);
    };

    input.addEventListener('change', typed);
    // other code may have changed the value without an event (Svelte bindings, the paste parser)
    input.addEventListener('focus', sync);

    return {
        sync,
        destroy() {
            input.removeEventListener('change', typed);
            input.removeEventListener('focus', sync);
            calendar.destroy();
            delete input._trackerDatePicker;
        },
    };
}
