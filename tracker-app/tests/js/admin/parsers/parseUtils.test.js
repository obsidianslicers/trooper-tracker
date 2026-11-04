import { describe, expect, it } from 'vitest';
import {
    firstInt,
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

describe('parseUsDateTime with free-text request form values', () => {
    const now = new Date(2026, 10, 1);

    const parse = (value, fallbackDate = null) => formatUsDateTime(parseUsDateTime(value, fallbackDate, now));

    it.each([
        ['12/13/2025 - 15:00', '2025-12-13 3:00PM'],
        ['2027-02-27 - 15:00', '2027-02-27 3:00PM'],
        ['2/27/2027 3:00 pm', '2027-02-27 3:00PM'],
        ['2/27/27 3pm', '2027-02-27 3:00PM'],
        ['2/27/27 3 pm', '2027-02-27 3:00PM'],
        ['2/27/2027 12:30 AM', '2027-02-27 12:30AM'],
        ['2/27/2027 12:00 pm', '2027-02-27 12:00PM'],
        ['3/4/26  - 10:30 a.m.', '2026-03-04 10:30AM'],
        ['03-04-2026  - 1000am', '2026-03-04 10:00AM'],
        ['March 4th, 2027  - 9a', '2027-03-04 9:00AM'],
        ['Sat. Mar 4 2027 - noon', '2027-03-04 12:00PM'],
        ['4 March 2027  - 930', '2027-03-04 9:30AM'],
        ['3/4/2026  - 11am-2pm', '2026-03-04 11:00AM'],
        ['3/4/2026  - 3pm CST', '2026-03-04 3:00PM'],
    ])('reads %s', (value, expected) => {
        expect(parse(value)).toBe(expected);
    });

    it('takes the upcoming year when none is given', () => {
        expect(parse('December 5  - 9am')).toBe('2026-12-05 9:00AM');
        expect(parse('March 5  - 9am')).toBe('2027-03-05 9:00AM');
    });

    it('reads a one-digit hour of 1-6 without am/pm as the afternoon', () => {
        expect(parse('3/4/2026  - 2:00')).toBe('2026-03-04 2:00PM');
        expect(parse('3/4/2026  - 6')).toBe('2026-03-04 6:00PM');
        expect(parse('3/4/2026  - 0600')).toBe('2026-03-04 6:00AM');
        expect(parse('3/4/2026  - 9:00')).toBe('2026-03-04 9:00AM');
    });

    it('puts a time without a date on the fallback date', () => {
        expect(parse('- 5pm', new Date(2026, 2, 4, 10))).toBe('2026-03-04 5:00PM');
        expect(parse('- 5pm')).toBeNull();
    });

    it.each([
        '2/30/2026 - 3pm',
        '2/27/2027 13:00 pm',
        '3/4/2026  - 25:00',
        '3/4/2026  -',
        'Saturday  - TBD',
        'Saturday, February 27th',
    ])('returns null for %j', (value) => {
        expect(parseUsDateTime(value, null, now)).toBeNull();
    });
});

describe('firstInt', () => {
    it.each([
        ['200', 200],
        ['200-300', 200],
        ['1,000+', 1000],
        ['~50', 50],
        ['?', null],
        ['a few', null],
        ['', null],
        [null, null],
    ])('reads %j as %j', (value, expected) => {
        expect(firstInt(value)).toBe(expected);
    });
});
