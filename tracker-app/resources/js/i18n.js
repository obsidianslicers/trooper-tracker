import { translate } from '../svelte/lib/translate';

/**
 * Translations for plain JS on Blade pages: reads the `common` group the layout
 * embeds in #tt-i18n. Svelte uses $lib/i18n instead. See docs/LOCALIZATION.md.
 */

let pageMessages = null;

/**
 * @param {string} key The English text of the line
 * @param {Record<string, string | number>} [replace] Values for :placeholders
 * @param {number | null} [count] Picks a plural form, like Laravel's trans_choice
 * @returns {string}
 */
export function t(key, replace = {}, count = null) {
    if (pageMessages === null) {
        pageMessages = readPageMessages();
    }

    return translate([pageMessages], key, replace, count);
}

function readPageMessages() {
    const element = typeof document === 'undefined' ? null : document.getElementById('tt-i18n');

    try {
        return JSON.parse(element?.textContent ?? '{}').messages ?? {};
    } catch {
        return {};
    }
}
