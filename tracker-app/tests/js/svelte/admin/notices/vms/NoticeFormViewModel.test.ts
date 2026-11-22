import { describe, expect, it, vi } from 'vitest';

const post = vi.fn();

vi.mock('@inertiajs/svelte', () => ({
    router: { post: vi.fn() },
    useForm: (data: Record<string, unknown>) => ({ ...data, post, errors: {}, processing: false, isDirty: false }),
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import { NoticeFormViewModel } from '../../../../../../resources/svelte/pages/admin/notices/models/vms/NoticeFormViewModel.svelte';
import type { NoticeFormPageData } from '../../../../../../resources/svelte/pages/admin/notices/models/types';

const pageData = (id: number | null): NoticeFormPageData => ({
    notice: {
        id,
        organization_id: 4,
        organization_name: 'E2E Garrison',
        type: 'info',
        title: 'Muster',
        starts_at: '2030-06-01 13:00',
        ends_at: '2030-06-02 13:00',
        message: 'North gate.',
    },
    type_options: [],
});

describe('NoticeFormViewModel', () => {
    it('creates a notice that has no id', () => {
        const vm = new NoticeFormViewModel(pageData(null));

        vm.submit(new Event('submit'));

        expect(vm.is_update).toBe(false);
        expect(post).toHaveBeenLastCalledWith('/admin.notices.store?');
    });

    it('updates a notice that has an id', () => {
        const vm = new NoticeFormViewModel(pageData(7));

        vm.submit(new Event('submit'));

        expect(vm.is_update).toBe(true);
        expect(post).toHaveBeenLastCalledWith('/admin.notices.update?notice=7');
    });

    it('cancels back to the list for the notice organization', () => {
        expect(new NoticeFormViewModel(pageData(null)).cancel_url).toBe('/admin.notices.index?organization_id=4');
    });
});
