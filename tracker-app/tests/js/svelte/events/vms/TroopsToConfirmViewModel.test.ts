import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post } }));

import { TroopsToConfirmViewModel } from '../../../../../resources/svelte/pages/events/models/vms/TroopsToConfirmViewModel.svelte';
import type { TroopToConfirm } from '../../../../../resources/svelte/pages/events/models/types';

const troop = (id: number): TroopToConfirm => ({
    id,
    event_name: 'Holiday Parade',
    event_url: `/events/details/${id}#shift-${id}`,
    shift_label: 'Sat - Dec 05, 2026 - 10:00am - 2:00pm',
    is_tentative: false,
    confirm_url: `/events/confirm/${id}`,
});

describe('TroopsToConfirmViewModel', () => {
    beforeEach(() => post.mockClear());

    it('posts the answer to the troop', () => {
        const vm = new TroopsToConfirmViewModel();

        vm.answer(troop(7), 'unabletoattend');

        expect(post).toHaveBeenCalledWith('/events/confirm/7', { status: 'unabletoattend' }, expect.objectContaining({ preserveScroll: true }));
        expect(vm.submitting_id).toBe(7);
    });

    it('ignores another answer until the first one finishes', () => {
        const vm = new TroopsToConfirmViewModel();

        vm.answer(troop(7), 'attended');
        vm.answer(troop(8), 'attended');

        expect(post).toHaveBeenCalledTimes(1);

        post.mock.calls[0][2].onFinish();
        vm.answer(troop(8), 'attended');

        expect(post).toHaveBeenCalledTimes(2);
        expect(vm.submitting_id).toBe(8);
    });
});
