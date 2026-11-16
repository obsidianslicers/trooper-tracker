import { page } from '@inertiajs/svelte';
import { translate, type Messages, type Replacements } from './translate';

interface I18nProps {
    i18n?: { locale?: string; messages?: Messages };
    lang?: Messages;
}

/**
 * Translates a line keyed by its English text. Looks in the page's own groups
 * (the `lang` prop its PageData sends) and then in the shared `common` group.
 * Falls back to the English key, so a missing translation never looks broken.
 */
export function t(key: string, replace: Replacements = {}, count: number | null = null): string {
    const props = pageProps();

    return translate([props.lang, props.i18n?.messages], key, replace, count);
}

/**
 * The current language as a BCP 47 tag (e.g. "de"), for Intl date and number formatting.
 */
export function locale(): string {
    return (pageProps().i18n?.locale ?? 'en').replace('_', '-');
}

/**
 * Empty outside an Inertia page (for example in a unit test that mocks Inertia
 * without a page), so callers fall back to English instead of throwing.
 */
function pageProps(): I18nProps {
    try {
        return (page?.props ?? {}) as unknown as I18nProps;
    } catch {
        return {};
    }
}
