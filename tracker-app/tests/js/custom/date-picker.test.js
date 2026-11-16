import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    formatForPicker,
    parsePickerValue,
    toNativeValue,
    toTextValue,
    usesNativePicker,
} from '../../../resources/js/custom/date-picker.js';

function pointer(coarse) {
    vi.stubGlobal('window', { matchMedia: (query) => ({ matches: coarse && query === '(pointer: coarse)' }) });
}

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('parsePickerValue', () => {
    it('reads a date', () => {
        expect(parsePickerValue('2030-06-01')).toEqual({ date: '2030-06-01', hour: null, minute: null });
    });

    it('reads the text field format, with or without a space before AM/PM', () => {
        expect(parsePickerValue('2030-06-01 1:05PM')).toEqual({ date: '2030-06-01', hour: 13, minute: 5 });
        expect(parsePickerValue('2030-06-01 12:00 am')).toEqual({ date: '2030-06-01', hour: 0, minute: 0 });
        expect(parsePickerValue('2030-06-01 12:30PM')).toEqual({ date: '2030-06-01', hour: 12, minute: 30 });
    });

    it('reads what the server renders and what a native field holds', () => {
        expect(parsePickerValue('2030-06-01 13:00:00')).toEqual({ date: '2030-06-01', hour: 13, minute: 0 });
        expect(parsePickerValue('2030-06-01T09:30')).toEqual({ date: '2030-06-01', hour: 9, minute: 30 });
    });

    it('reads a Date', () => {
        expect(parsePickerValue(new Date(2030, 5, 1, 14, 45))).toEqual({ date: '2030-06-01', hour: 14, minute: 45 });
    });

    it('rejects anything else', () => {
        expect(parsePickerValue('')).toBeNull();
        expect(parsePickerValue(null)).toBeNull();
        expect(parsePickerValue('6/1/2030')).toBeNull();
        expect(parsePickerValue('2030-06-01 13:00PM')).toBeNull();
        expect(parsePickerValue('2030-06-01 24:00')).toBeNull();
        expect(parsePickerValue(new Date('nope'))).toBeNull();
    });
});

describe('toTextValue and toNativeValue', () => {
    const afternoon = { date: '2030-06-01', hour: 13, minute: 5 };
    const midnight = { date: '2030-06-01', hour: 0, minute: 0 };
    const dateOnly = { date: '2030-06-01', hour: null, minute: null };

    it('formats for the text field the way flatpickr did', () => {
        expect(toTextValue(afternoon, true)).toBe('2030-06-01 1:05PM');
        expect(toTextValue(midnight, true)).toBe('2030-06-01 12:00AM');
        expect(toTextValue(afternoon, false)).toBe('2030-06-01');
    });

    it('formats for a native field', () => {
        expect(toNativeValue(afternoon, true)).toBe('2030-06-01T13:05');
        expect(toNativeValue(dateOnly, true)).toBe('2030-06-01T00:00');
        expect(toNativeValue(afternoon, false)).toBe('2030-06-01');
    });

    it('gives an empty string for nothing', () => {
        expect(toTextValue(null, true)).toBe('');
        expect(toNativeValue(null, true)).toBe('');
    });
});

describe('usesNativePicker and formatForPicker', () => {
    it('uses the native picker only where the main pointer is a finger', () => {
        pointer(true);
        expect(usesNativePicker()).toBe(true);
        pointer(false);
        expect(usesNativePicker()).toBe(false);
    });

    it('formats a pasted value for the device', () => {
        pointer(true);
        expect(formatForPicker('2030-06-01 9:30AM', { withTime: true })).toBe('2030-06-01T09:30');
        expect(formatForPicker(new Date(2030, 5, 1, 14, 0), { withTime: true })).toBe('2030-06-01T14:00');

        pointer(false);
        expect(formatForPicker('2030-06-01 9:30AM', { withTime: true })).toBe('2030-06-01 9:30AM');
        expect(formatForPicker(new Date(2030, 5, 1, 14, 0), { withTime: true })).toBe('2030-06-01 2:00PM');
    });

    it('leaves a value it cannot read alone', () => {
        pointer(true);
        expect(formatForPicker('next Tuesday', { withTime: true })).toBe('next Tuesday');
    });
});
