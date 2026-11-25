// The part of an element ScrollHint reads and moves. Real elements fit it; tests pass plain objects.
export interface ScrollRow {
    scrollLeft: number;
    scrollWidth: number;
    clientWidth: number;
    getBoundingClientRect(): { left: number; right: number };
    scrollBy(options: ScrollToOptions): void;
    scrollTo(options: ScrollToOptions): void;
}

export interface ScrollItem {
    getBoundingClientRect(): { left: number; right: number };
}

// Room left beside a revealed item, so the chevron doesn't sit on top of it.
const CHEVRON_ROOM = 40;

// Sub-pixel scroll positions on high-density screens never reach the exact end.
const EDGE_SLACK = 2;

export class ScrollHintViewModel {
    more_before = $state(false);
    more_after = $state(false);

    update(row: ScrollRow): void {
        const max = row.scrollWidth - row.clientWidth;

        this.more_before = row.scrollLeft > EDGE_SLACK;
        this.more_after = row.scrollLeft < max - EDGE_SLACK;
    }

    step(row: ScrollRow, direction: -1 | 1): void {
        row.scrollBy({ left: direction * row.clientWidth * 0.7, behavior: 'smooth' });
    }

    reveal(row: ScrollRow, item: ScrollItem, behavior: ScrollBehavior = 'smooth'): void {
        const bounds = row.getBoundingClientRect();
        const box = item.getBoundingClientRect();
        const hidden_before = bounds.left + CHEVRON_ROOM - box.left;
        const hidden_after = box.right - (bounds.right - CHEVRON_ROOM);

        if (hidden_before > 0) {
            row.scrollTo({ left: row.scrollLeft - hidden_before, behavior });
        } else if (hidden_after > 0) {
            row.scrollTo({ left: row.scrollLeft + hidden_after, behavior });
        }
    }
}
