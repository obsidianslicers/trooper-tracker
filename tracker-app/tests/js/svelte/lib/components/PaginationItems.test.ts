import { describe, expect, it } from 'vitest';
import type { PageLink } from '../../../../../resources/svelte/lib/domains/types.svelte';
import { paginationItems } from '../../../../../resources/svelte/lib/components/models/PaginationItems';

// Laravel's links for `current` of `last`, with its "..." gaps (two pages each side).
function laravelLinks(current: number, pages: (number | '...')[]): PageLink[] {
    const url = (n: number) => `/admin/costumes?page=${n}`;
    const last = Math.max(...pages.filter((p): p is number => p !== '...'));
    return [
        { url: current > 1 ? url(current - 1) : null, label: '&laquo; Previous', active: false },
        ...pages.map((p) =>
            p === '...'
                ? { url: null, label: '...', active: false }
                : { url: url(p), label: String(p), active: p === current },
        ),
        { url: current < last ? url(current + 1) : null, label: 'Next &raquo;', active: false },
    ];
}

// What a phone shows: everything that isn't wide-only, as text.
function onPhone(links: PageLink[]): string[] {
    return paginationItems(links)
        .filter((item) => !item.wide)
        .map((item) => (item.kind === 'gap' ? '…' : item.link.label));
}

function onDesktop(links: PageLink[]): string[] {
    return paginationItems(links)
        .filter((item) => item.kind === 'link' || !item.narrow)
        .map((item) => (item.kind === 'gap' ? '…' : item.link.label));
}

describe('paginationItems', () => {
    it('shows the first, last and neighbouring pages on a phone', () => {
        const links = laravelLinks(10, [1, 2, '...', 7, 8, 9, 10, 11, 12, 13, '...', 19, 20]);

        expect(onPhone(links)).toEqual([
            '&laquo; Previous', '1', '…', '9', '10', '11', '…', '20', 'Next &raquo;',
        ]);
    });

    it('keeps every link Laravel sent on a wider screen', () => {
        const links = laravelLinks(10, [1, 2, '...', 7, 8, 9, 10, 11, 12, 13, '...', 19, 20]);

        expect(onDesktop(links)).toEqual(links.map((l) => (l.label === '...' ? '…' : l.label)));
    });

    it('adds a gap on a phone where Laravel has none', () => {
        const links = laravelLinks(1, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        expect(onPhone(links)).toEqual(['&laquo; Previous', '1', '2', '…', '10', 'Next &raquo;']);
    });

    it('shows no gap when no page is hidden', () => {
        const links = laravelLinks(2, [1, 2, 3, 4]);

        expect(onPhone(links)).toEqual([
            '&laquo; Previous', '1', '2', '3', '4', 'Next &raquo;',
        ]);
    });

    it('marks the Previous and Next links', () => {
        const items = paginationItems(laravelLinks(2, [1, 2, 3]));

        expect(items[0]).toMatchObject({ kind: 'link', edge: 'previous' });
        expect(items.at(-1)).toMatchObject({ kind: 'link', edge: 'next' });
    });
});
