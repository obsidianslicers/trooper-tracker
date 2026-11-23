// @vitest-environment jsdom
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Modal from '../../../../../resources/svelte/lib/components/ui/Modal.svelte';

// The real Bootstrap modal: its fades end on a timer here, as jsdom has no transitions.
describe('Modal removed by its owner', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.className = '';
        document.body.removeAttribute('style');
        document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
    });

    const openModal = async () => {
        const view = render(Modal, { show: true, title: 'Find a Trooper' });
        await vi.runAllTimersAsync();
        expect(document.body.classList.contains('modal-open')).toBe(true);
        return view;
    };

    it('finishes closing when it is removed while fading out', async () => {
        const { rerender, unmount } = await openModal();

        await rerender({ show: false, title: 'Find a Trooper' });
        unmount();
        await tick();

        await expect(vi.runAllTimersAsync()).resolves.not.toThrow();
        expect(document.body.classList.contains('modal-open')).toBe(false);
        expect(document.querySelector('.modal-backdrop')).toBeNull();
    });

    it('closes when it is removed while open', async () => {
        const { unmount } = await openModal();

        unmount();
        await vi.runAllTimersAsync();

        expect(document.body.classList.contains('modal-open')).toBe(false);
        expect(document.querySelector('.modal-backdrop')).toBeNull();
    });
});
