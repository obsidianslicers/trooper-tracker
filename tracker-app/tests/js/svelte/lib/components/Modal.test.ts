// @vitest-environment jsdom
import { render } from '@testing-library/svelte';
import { tick } from 'svelte';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Like Bootstrap's modal: show() and hide() are ignored until the running fade ends.
const fake = vi.hoisted(() => {
    const state = { instance: null as null | FakeModal };

    class FakeModal {
        fading = false;

        constructor(public element: HTMLElement) {
            state.instance = this;
        }

        show() {
            if (this.fading || this.element.classList.contains('show')) return;
            this.fading = true;
            this.element.classList.add('show');
        }

        hide() {
            if (this.fading || !this.element.classList.contains('show')) return;
            this.fading = true;
            this.element.classList.remove('show');
        }

        endFade() {
            this.fading = false;
            const open = this.element.classList.contains('show');
            this.element.dispatchEvent(new Event(open ? 'shown.bs.modal' : 'hidden.bs.modal'));
        }

        dispose() {}
    }

    return Object.assign(state, { FakeModal });
});

vi.mock('bootstrap', () => ({ Modal: fake.FakeModal }));

import ConfirmationModal from '../../../../../resources/svelte/lib/components/ConfirmationModal.svelte';

const props = (show: boolean) => ({ show, onConfirm: vi.fn(), onCancel: vi.fn() });

describe('Modal', () => {
    beforeEach(() => {
        fake.instance = null;
    });

    it('closes when asked to before its fade-in ends', async () => {
        const { rerender, container } = render(ConfirmationModal, props(true));
        const modal = container.ownerDocument.querySelector('.modal')!;

        await rerender(props(false));
        expect(modal.classList.contains('show')).toBe(true);

        fake.instance!.endFade();
        await tick();
        fake.instance!.endFade();

        expect(modal.classList.contains('show')).toBe(false);
    });

    it('opens again when asked to while it is fading out', async () => {
        const first = props(true);
        const { rerender, container } = render(ConfirmationModal, first);
        const modal = container.ownerDocument.querySelector('.modal')!;
        fake.instance!.endFade();

        await rerender(props(false));
        await rerender(props(true));
        fake.instance!.endFade();
        await tick();

        expect(modal.classList.contains('show')).toBe(true);
        expect(first.onCancel).not.toHaveBeenCalled();
    });

    it('tells its owner when Bootstrap closes it, by Esc or the backdrop', async () => {
        const open = props(true);
        const { container } = render(ConfirmationModal, open);
        fake.instance!.endFade();

        fake.instance!.hide();
        fake.instance!.endFade();
        await tick();

        expect(open.onCancel).toHaveBeenCalled();
        expect(container.ownerDocument.querySelector('.modal')!.classList.contains('show')).toBe(false);
    });
});
