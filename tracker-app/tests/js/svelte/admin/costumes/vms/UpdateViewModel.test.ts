import { describe, expect, it, vi } from 'vitest';

const { post, formPost, transform } = vi.hoisted(() => {
    const formPost = vi.fn();
    const transform = vi.fn();
    return { post: vi.fn(), formPost, transform };
});

vi.mock('@inertiajs/svelte', () => ({
    router: { post },
    useForm: (data: Record<string, unknown>) => {
        const form: Record<string, unknown> = { ...data, errors: {}, processing: false, isDirty: false };
        form.transform = (fn: (d: unknown) => unknown) => {
            transform(fn(form));
            return { post: formPost };
        };
        return form;
    },
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import { UpdateViewModel } from '../../../../../../resources/svelte/pages/admin/costumes/models/vms/UpdateViewModel.svelte';
import type { UpdatePageData } from '../../../../../../resources/svelte/pages/admin/costumes/models/types';

const pageData = (overrides: Partial<UpdatePageData> = {}): UpdatePageData => ({
    costume: {
        id: 3,
        name: 'TK',
        organization_ids: [1],
        can_delete: true,
        trooper_stamps: {} as UpdatePageData['costume']['trooper_stamps'],
    },
    organizations: [{ id: 1, name: '501st Legion' }, { id: 2, name: 'Rebel Legion' }],
    deletion_impact: { event_troopers: 0, trooper_costumes: 0 },
    confirm_delete: false,
    ...overrides,
});

describe('UpdateViewModel', () => {
    it('ticks and unticks the clubs that offer the costume', () => {
        const vm = new UpdateViewModel(pageData());

        vm.setOffered(2, true);
        vm.setOffered(1, false);

        expect(vm.isOffered(1)).toBe(false);
        expect(vm.isOffered(2)).toBe(true);
    });

    it('posts the ticked clubs the way the update request reads them', () => {
        const vm = new UpdateViewModel(pageData());
        vm.setOffered(2, true);

        vm.submit(new Event('submit'));

        expect(transform).toHaveBeenLastCalledWith({
            name: 'TK',
            organizations: { 1: { selected: '1' }, 2: { selected: '1' } },
        });
        expect(formPost).toHaveBeenLastCalledWith('/admin.costumes.update?costume=3');
    });

    it('opens the delete confirmation when the page asks for it', () => {
        expect(new UpdateViewModel(pageData({ confirm_delete: true })).show_delete).toBe(true);
    });

    it('deletes the costume after confirming', () => {
        const vm = new UpdateViewModel(pageData());

        vm.confirmDelete();
        vm.delete();

        expect(post).toHaveBeenLastCalledWith('/admin.costumes.destroy?costume=3', {}, expect.any(Object));
    });
});
