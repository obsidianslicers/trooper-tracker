import { describe, expect, it, vi } from 'vitest';
import {
    ScrollHintViewModel,
    type ScrollRow,
} from '../../../../../resources/svelte/lib/components/models/ScrollHintViewModel.svelte';

// A 300px-wide row holding 900px of tabs, starting at x = 0 on the screen.
function makeRow(scroll_left = 0): ScrollRow {
    return {
        scrollLeft: scroll_left,
        scrollWidth: 900,
        clientWidth: 300,
        getBoundingClientRect: () => ({ left: 0, right: 300 }),
        scrollBy: vi.fn(),
        scrollTo: vi.fn(),
    };
}

const item = (left: number, right: number) => ({ getBoundingClientRect: () => ({ left, right }) });

describe('ScrollHintViewModel', () => {
    it('shows only the after hint at the start', () => {
        const subject = new ScrollHintViewModel();

        subject.update(makeRow(0));

        expect(subject.more_before).toBe(false);
        expect(subject.more_after).toBe(true);
    });

    it('shows both hints in the middle', () => {
        const subject = new ScrollHintViewModel();

        subject.update(makeRow(300));

        expect(subject.more_before).toBe(true);
        expect(subject.more_after).toBe(true);
    });

    it('shows only the before hint at the end, allowing for sub-pixel scrolling', () => {
        const subject = new ScrollHintViewModel();

        subject.update(makeRow(599.5));

        expect(subject.more_before).toBe(true);
        expect(subject.more_after).toBe(false);
    });

    it('shows no hints when everything fits', () => {
        const subject = new ScrollHintViewModel();
        const row = { ...makeRow(0), scrollWidth: 300 };

        subject.update(row);

        expect(subject.more_before).toBe(false);
        expect(subject.more_after).toBe(false);
    });

    it('steps most of a row width in either direction', () => {
        const subject = new ScrollHintViewModel();
        const row = makeRow(300);

        subject.step(row, 1);
        subject.step(row, -1);

        expect(row.scrollBy).toHaveBeenNthCalledWith(1, { left: 210, behavior: 'smooth' });
        expect(row.scrollBy).toHaveBeenNthCalledWith(2, { left: -210, behavior: 'smooth' });
    });

    it('scrolls an item cut off on the right clear of the chevron', () => {
        const subject = new ScrollHintViewModel();
        const row = makeRow(0);

        subject.reveal(row, item(220, 320), 'instant');

        expect(row.scrollTo).toHaveBeenCalledWith({ left: 60, behavior: 'instant' });
    });

    it('scrolls an item cut off on the left clear of the chevron', () => {
        const subject = new ScrollHintViewModel();
        const row = makeRow(300);

        subject.reveal(row, item(-50, 50));

        expect(row.scrollTo).toHaveBeenCalledWith({ left: 210, behavior: 'smooth' });
    });

    it('leaves a fully visible item where it is', () => {
        const subject = new ScrollHintViewModel();
        const row = makeRow(0);

        subject.reveal(row, item(100, 200));

        expect(row.scrollTo).not.toHaveBeenCalled();
    });
});
