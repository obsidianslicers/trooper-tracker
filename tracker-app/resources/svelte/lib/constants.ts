import type { Option } from "./domains/types.svelte";
import { t } from "$lib/i18n";

export const TABS_CONTEXT = Symbol('tabs');
export const ONE_HOUR_MS = 60 * 60 * 1000;
export const ONE_DAY_MS = 24 * ONE_HOUR_MS;


export function yesNoOptions(): Option[] {
    return [
        { value: true, label: t("Yes") },
        { value: false, label: t("No") }
    ];
}