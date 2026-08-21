import type { Option } from "./domains/types.svelte";

export const TABS_CONTEXT = Symbol('tabs');
export const ONE_HOUR_MS = 60 * 60 * 1000;
export const ONE_DAY_MS = 24 * ONE_HOUR_MS;


export const YES_NO_OPTIONS: Option[] = [
    { value: true, label: 'Yes' },
    { value: false, label: 'No' }
];