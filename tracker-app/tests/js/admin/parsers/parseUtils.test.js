import { describe, expect, it } from 'vitest';
import {
    formatUsDateTime,
    parseTime,
    parseUsDate,
    parseUsDateTime,
    toInt,
} from '../../../../resources/js/admin/parsers/parseUtils.js';

describe('parseUsDate', () => {
    it('reads m/d/Y', () => {
        expect(parseUsDate('12/13/2025')).toEqual(new Date(2025, 11, 13));
    });

    it('returns null for anything else', () => {
        expect(parseUsDate('soon')).toBeNull();
        expect(parseUsDate('')).toBeNull();
        expect(parseUsDate(null)).toBeNull();
    });
});

describe('parseUsDateTime', () => {
    it.each([
        ['12/13/2025 - 0930', new Date(2025, 11, 13, 9, 30)],
        ['12/13/2025 - 1300', new Date(2025, 11, 13, 13, 0)],
        ['1/2/2026 - 0000', new Date(2026, 0, 2, 0, 0)],
    ])('reads m/d/Y - Hi: %s', (value, expected) => {
        expect(parseUsDateTime(value)).toEqual(expected);
    });

    it.each(['12/13/2025', 'Saturday', '', null, undefined])('returns null for %j', (value) => {
        expect(parseUsDateTime(value)).toBeNull();
    });
});

describe('formatUsDateTime', () => {
    it('formats a date as Y-m-d g:iA', () => {
        expect(formatUsDateTime(new Date(2026, 0, 2, 0, 5))).toBe('2026-01-02 12:05AM');
        expect(formatUsDateTime(new Date(2026, 0, 2, 12, 0))).toBe('2026-01-02 12:00PM');
        expect(formatUsDateTime(new Date(2026, 0, 2, 13, 0))).toBe('2026-01-02 1:00PM');
    });

    it('returns null for a missing or invalid date', () => {
        expect(formatUsDateTime(null)).toBeNull();
        expect(formatUsDateTime(new Date('nope'))).toBeNull();
    });
});

describe('parseTime', () => {
    it('reads H:i', () => {
        expect(parseTime('14:30')).toEqual({ hour: 14, minute: 30 });
        expect(parseTime('9:05')).toEqual({ hour: 9, minute: 5 });
    });

    it('returns null for anything else', () => {
        expect(parseTime('2pm')).toBeNull();
        expect(parseTime('')).toBeNull();
    });
});

describe('toInt', () => {
    it('reads a number, or null', () => {
        expect(toInt('42')).toBe(42);
        expect(toInt('42 or so')).toBeNull();
    });
});
