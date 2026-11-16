// Types for date-picker.js, which the Svelte InputDate component imports.

export type PickerValue = { date: string; hour: number | null; minute: number | null };

export type DatePicker = { sync: () => void; destroy: () => void };

export function usesNativePicker(): boolean;

export function parsePickerValue(value: unknown): PickerValue | null;

export function toTextValue(parsed: PickerValue | null, withTime: boolean): string;

export function toNativeValue(parsed: PickerValue | null, withTime: boolean): string;

export function formatForPicker<T>(value: T, options?: { withTime?: boolean }): T | string;

export function attachDatePicker(
    input: HTMLInputElement,
    options?: { withTime?: boolean; onChange?: ((value: string) => void) | null },
): DatePicker;
