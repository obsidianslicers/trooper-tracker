// resources/js/utils/dateUtils.js

/**
 * Parse a US-style date string: "m/d/Y"
 * Returns a JS Date or null.
 */
export function parseUsDate(dateStr) {
    if (!dateStr) return null;

    const [m, d, y] = dateStr.split('/').map(Number);

    if (!m || !d || !y) return null;

    // JS months are 0-based
    return new Date(y, m - 1, d, 0, 0, 0, 0);
}

/**
 * Parse a 24-hour time string: "H:i"
 * Returns { hour, minute } or null.
 */
export function parseTime(timeStr) {
    if (!timeStr) return null;

    const [h, m] = timeStr.split(':').map(Number);

    if (Number.isNaN(h) || Number.isNaN(m)) return null;

    return { hour: h, minute: m };
}

/**
 * Convert a numeric-like string to an integer or null.
 */
export function toInt(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
}
