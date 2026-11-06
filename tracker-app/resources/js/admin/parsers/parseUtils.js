/**
 * Parse a US-style date string: "m/d/Y"
 * Returns a JS Date or null.
 */
export function parseUsDate(dateStr) {
    if (!dateStr) {
        return null;
    }

    const [m, d, y] = dateStr.split('/').map(Number);

    if (!m || !d || !y) {
        return null;
    }

    // JS months are 0-based
    return new Date(y, m - 1, d, 0, 0, 0, 0);
}

const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

/**
 * Parse a request form's "<date> - <time>". Requesters type both parts freely, e.g.
 * "12/13/2025 - 0930", "2027-02-27 - 15:00", "2/27/27  - 3pm", "March 4th, 2026 - 10:30 a.m.",
 * "Sat 3/4 - noon". A blank date (an end time like "- 5pm") takes `fallbackDate`'s day.
 * Returns a JS Date, or null when there is no usable date and time.
 */
export function parseUsDateTime(dateTimeStr, fallbackDate = null, now = new Date()) {
    if (!dateTimeStr) {
        return null;
    }

    const value = dateTimeStr.trim();

    // the form joins the two fields with " - "; ISO dates have dashes without spaces
    const split = value.match(/^(.*?)\s*(?:\s-|-\s)\s*(.*)$/)
        ?? value.match(/^(.*?)[\s,]+(\d{1,2}(?::\d{2})?\s*[ap]\.?\s*m?\.?|\d{1,2}:\d{2})$/i)
        ?? [value, value, ''];

    const date = split[1] === '' ? copyDate(fallbackDate) : parseLooseDate(split[1], now);
    const time = parseLooseTime(split[2]);

    if (!date || !time) {
        return null;
    }

    date.setHours(time.hour, time.minute, 0, 0);

    return date;
}

function copyDate(date) {
    return date instanceof Date && !isNaN(date) ? new Date(date) : null;
}

// "m/d/Y", "m/d/y", "m-d-Y", "m/d", "Y-m-d", "March 4th, 2026", "Mar. 4", "4 March 2026", with an optional weekday
function parseLooseDate(str, now) {
    const s = str.trim().replace(/^(mon|tue|wed|thu|fri|sat|sun)[a-z]*\.?,?\s*/i, '');

    let y, m, d, match;

    if ((match = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) {
        [y, m, d] = match.slice(1).map(Number);
    }
    else if ((match = s.match(/^(\d{1,2})[/-](\d{1,2})(?:[/-](\d{2}|\d{4}))?$/))) {
        [m, d, y] = match.slice(1).map(Number);
    }
    else if ((match = s.match(/^([a-z]{3,})\.?\s+(\d{1,2})(?:st|nd|rd|th)?,?(?:\s+(\d{4}))?$/i))) {
        m = MONTHS.indexOf(match[1].slice(0, 3).toLowerCase()) + 1;
        [d, y] = [Number(match[2]), Number(match[3])];
    }
    else if ((match = s.match(/^(\d{1,2})(?:st|nd|rd|th)?\s+([a-z]{3,})\.?,?(?:\s+(\d{4}))?$/i))) {
        m = MONTHS.indexOf(match[2].slice(0, 3).toLowerCase()) + 1;
        [d, y] = [Number(match[1]), Number(match[3])];
    }
    else {
        return null;
    }

    if (y && y < 100) {
        y += 2000;
    }

    // no year: the next such date, since requests are for upcoming events
    const recently = new Date(now.getFullYear(), now.getMonth() - 2, now.getDate());
    if (!y) {
        y = new Date(now.getFullYear(), m - 1, d) < recently ? now.getFullYear() + 1 : now.getFullYear();
    }

    const date = new Date(y, m - 1, d, 0, 0, 0, 0);

    // reject 2/30 and the like rather than rolling over
    return m >= 1 && date.getMonth() === m - 1 && date.getDate() === d ? date : null;
}

// "0930", "15:00", "930", "3pm", "3:30 p.m.", "noon"; extra words after the time are ignored
function parseLooseTime(str) {
    const s = (str ?? '').trim().toLowerCase();

    if (/^noon\b/.test(s)) {
        return { hour: 12, minute: 0 };
    }
    if (/^midnight\b/.test(s)) {
        return { hour: 0, minute: 0 };
    }

    const match = s.match(/^(\d{1,2})(?::?(\d{2}))?\s*(?:([ap])\.?\s*m?\.?)?(?![\d:])/);

    if (!match) {
        return null;
    }

    let hour = Number(match[1]);
    const minute = Number(match[2] ?? 0);

    if (match[3]) {
        if (hour < 1 || hour > 12) {
            return null;
        }

        hour = (hour % 12) + (match[3] === 'p' ? 12 : 0);
    }
    // no am/pm and a one-digit hour of 1-6 ("2:00", "5") is an afternoon; "0600" stays 6am
    else if (hour >= 1 && hour <= 6 && match[1].length === 1) {
        hour += 12;
    }

    return hour <= 23 && minute <= 59 ? { hour, minute } : null;
}

export function formatUsDateTime(date) {
    if (!(date instanceof Date) || isNaN(date)) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    let hour = date.getHours();

    const minute = String(date.getMinutes()).padStart(2, '0');
    const ampm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;

    if (hour === 0) {
        hour = 12;
    }

    return `${year}-${month}-${day} ${hour}:${minute}${ampm}`;
}

/**
 * Parse a 24-hour time string: "H:i"
 * Returns { hour, minute } or null.
 */
export function parseTime(timeStr) {
    if (!timeStr) {
        return null;
    }

    const [h, m] = timeStr.split(':').map(Number);

    if (Number.isNaN(h) || Number.isNaN(m)) {
        return null;
    }

    return { hour: h, minute: m };
}

/**
 * Convert a numeric-like string to an integer or null.
 */
export function toInt(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}

/**
 * The first whole number in free text, e.g. "200-300" → 200, "1,000+" → 1000, "a few" → null.
 */
export function firstInt(value) {
    const match = String(value ?? '').match(/\d[\d,]*/);
    return match ? Number(match[0].replace(/,/g, '')) : null;
}
