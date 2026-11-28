import type { PageLink } from "$lib/domains/types.svelte";

/**
 * One entry of the pagination bar. `wide` entries only show from the `sm` breakpoint up, and
 * `narrow` entries only below it, so a phone gets a short bar:
 * Previous, first, the pages next to the current one, last, Next.
 */
export type PaginationItem =
    | { kind: "link"; link: PageLink; edge: "previous" | "next" | null; wide: boolean }
    | { kind: "gap"; narrow: boolean; wide: boolean };

export function paginationItems(links: PageLink[]): PaginationItem[] {
    const numbers = links.map((link) => pageNumber(link)).filter((n): n is number => n !== null);
    const active = pageNumber(links.find((link) => link.active)) ?? numbers[0];
    const first = Math.min(...numbers);
    const last = Math.max(...numbers);
    const onPhone = (n: number) => n === first || n === last || Math.abs(n - active) <= 1;

    const items: PaginationItem[] = [];
    let pending_gap = false;

    links.forEach((link, i) => {
        const edge = i === 0 ? "previous" : i === links.length - 1 ? "next" : null;
        const n = pageNumber(link);

        if (edge === null && n === null) {
            items.push({ kind: "gap", narrow: false, wide: true });
            pending_gap = true;
            return;
        }

        if (n !== null && !onPhone(n)) {
            items.push({ kind: "link", link, edge, wide: true });
            pending_gap = true;
            return;
        }

        if (pending_gap) {
            items.push({ kind: "gap", narrow: true, wide: false });
            pending_gap = false;
        }
        items.push({ kind: "link", link, edge, wide: false });
    });

    return items;
}

function pageNumber(link: PageLink | undefined): number | null {
    if (!link || !/^\d+$/.test(link.label.trim())) return null;
    return Number(link.label.trim());
}
