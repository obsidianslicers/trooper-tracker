import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import { ListViewModel } from '../../../../../../resources/svelte/pages/admin/notices/models/vms/ListViewModel.svelte';
import type { ListPageData } from '../../../../../../resources/svelte/pages/admin/notices/models/types';

const pageData = (overrides: Partial<ListPageData> = {}): ListPageData => ({
    notices: { data: [], meta: { links: [] } },
    scope: 'active',
    organization: null,
    ...overrides,
});

describe('ListViewModel', () => {
    it('changes the scope and keeps the organization filter', () => {
        const vm = new ListViewModel(pageData({ organization: { id: 4, name: 'E2E Garrison' } }));

        expect(vm.listUrl({ scope: 'past' })).toBe('/admin.notices.index?scope=past&organization_id=4');
    });

    it('clears the organization filter and keeps the scope', () => {
        const vm = new ListViewModel(pageData({ scope: 'future', organization: { id: 4, name: 'E2E Garrison' } }));

        expect(vm.listUrl({ organization_id: null })).toBe('/admin.notices.index?scope=future');
    });

    it('creates a notice for the organization being shown', () => {
        const vm = new ListViewModel(pageData({ organization: { id: 4, name: 'E2E Garrison' } }));

        expect(vm.create_url).toBe('/admin.notices.create?organization_id=4');
    });
});
