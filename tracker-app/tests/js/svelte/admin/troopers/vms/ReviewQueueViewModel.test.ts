import { describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({ router: { post } }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import { ReviewQueueViewModel } from '../../../../../../resources/svelte/pages/admin/troopers/models/vms/ReviewQueueViewModel.svelte';

describe('ReviewQueueViewModel', () => {
    it('approves and denies a costume', () => {
        const vm = new ReviewQueueViewModel();

        vm.approveCostume(5);
        expect(post.mock.calls.at(-1)![0]).toBe('/admin.troopers.costumes.approve?trooper_costume=5');
        expect(vm.isWorking('costume-5')).toBe(true);

        post.mock.calls.at(-1)![2].onFinish();
        expect(vm.isWorking('costume-5')).toBe(false);

        vm.denyCostume(5);
        expect(post.mock.calls.at(-1)![0]).toBe('/admin.troopers.costumes.deny?trooper_costume=5');
    });

    it('approves or removes one club\'s credit for a troop', () => {
        const vm = new ReviewQueueViewModel();

        vm.approveCredit(9, 3);
        expect(post.mock.calls.at(-1)![0]).toBe(
            '/admin.troopers.troop-verifications.approve?event_trooper=9&organization=3',
        );
        expect(vm.isWorking('credit-9-3')).toBe(true);
        expect(vm.isWorking('credit-9-4')).toBe(false);

        vm.removeCredit(9, 4);
        expect(post.mock.calls.at(-1)![0]).toBe(
            '/admin.troopers.troop-verifications.reject?event_trooper=9&organization=4',
        );
    });
});
