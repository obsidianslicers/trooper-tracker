// @vitest-environment jsdom
import { fireEvent, render } from '@testing-library/svelte';
import { tick } from 'svelte';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('bootstrap', () => ({
    Modal: class {
        constructor(public element: HTMLElement) {}
        show() {}
        hide() {}
        dispose() {}
    },
}));
vi.mock('$lib/utils', () => ({ getRoute: () => '/search/troopers' }));

import TrooperPicker from '../../../../../resources/svelte/lib/components/TrooperPicker.svelte';

const flush = async () => {
    for (let i = 0; i < 5; i++) {
        await tick();
        await Promise.resolve();
    }
};

describe('TrooperPicker', () => {
    let fetch_mock: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        fetch_mock = vi.fn(() => Promise.resolve({ json: () => Promise.resolve([]) }));
        vi.stubGlobal('fetch', fetch_mock);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('lists friends with one request when it opens with an empty search', async () => {
        // A request still in flight, as when the server is slow: the picker must not start more.
        fetch_mock.mockImplementation(() => new Promise(() => {}));
        const { getByRole } = render(TrooperPicker, {
            as_button: true,
            mode: 'friends',
            button_label: 'Add a friend',
        });

        await fireEvent.click(getByRole('button', { name: 'Add a friend' }));
        await flush();

        expect(fetch_mock).toHaveBeenCalledTimes(1);
        expect(fetch_mock.mock.calls[0][0]).toContain('search_mode=friends');
    });

    it('sends nothing on opening outside friends mode', async () => {
        const { getByRole } = render(TrooperPicker, {
            as_button: true,
            button_label: 'Pick',
        });

        await fireEvent.click(getByRole('button', { name: 'Pick' }));
        await flush();

        expect(fetch_mock).not.toHaveBeenCalled();
    });
});
