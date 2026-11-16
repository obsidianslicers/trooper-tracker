/**
 * Front-end side of the tracker's translations (see docs/LOCALIZATION.md). Shared by
 * Svelte ($lib/i18n) and plain JS (resources/js/i18n.js).
 *
 * Lines are keyed by their English text, so a key with no translation is shown as-is.
 */

export type Messages = Record<string, string>;
export type Replacements = Record<string, string | number>;

/**
 * Looks a key up in each message map in turn and fills in its :placeholders.
 */
export function translate(
    sources: Array<Messages | null | undefined>,
    key: string,
    replace: Replacements = {},
    count: number | null = null,
): string {
    let line = key;

    for (const messages of sources) {
        if (messages && typeof messages[key] === 'string') {
            line = messages[key];
            break;
        }
    }

    if (count !== null) {
        line = choosePlural(line, count);
        replace = { count, ...replace };
    }

    return makeReplacements(line, replace);
}

/**
 * Supports Laravel's forms: "{0} none|{1} one|[2,*] many" and plain "one|many".
 */
export function choosePlural(line: string, count: number): string {
    const segments = line.split('|');

    for (const segment of segments) {
        const exact = segment.match(/^\s*\{(\d+)\}\s?/);

        if (exact && Number(exact[1]) === count) {
            return segment.slice(exact[0].length);
        }

        const range = segment.match(/^\s*\[(\d+|\*),\s*(\d+|\*)\]\s?/);

        if (range && inRange(count, range[1], range[2])) {
            return segment.slice(range[0].length);
        }
    }

    const plain = segments.filter((segment) => !/^\s*[[{]/.test(segment));

    if (plain.length === 0) {
        return line;
    }

    return count === 1 || plain.length === 1 ? plain[0] : plain[plain.length - 1];
}

function inRange(count: number, from: string, to: string): boolean {
    return (from === '*' || count >= Number(from)) && (to === '*' || count <= Number(to));
}

/**
 * Fills :name, :Name and :NAME the way Laravel does. Longer keys go first so :name
 * doesn't eat the start of :name_full.
 */
export function makeReplacements(line: string, replace: Replacements): string {
    const keys = Object.keys(replace).sort((a, b) => b.length - a.length);

    for (const key of keys) {
        const value = String(replace[key] ?? '');

        line = replaceAll(line, `:${key.toUpperCase()}`, value.toUpperCase());
        line = replaceAll(line, `:${capitalize(key)}`, capitalize(value));
        line = replaceAll(line, `:${key}`, value);
    }

    return line;
}

function replaceAll(line: string, search: string, value: string): string {
    return line.split(search).join(value);
}

function capitalize(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
