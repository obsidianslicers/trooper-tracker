import { describe, expect, it, vi } from 'vitest';

const { visit, formPost } = vi.hoisted(() => ({ visit: vi.fn(), formPost: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({
    router: { visit },
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        post: formPost,
        reset: vi.fn(),
        errors: {},
        processing: false,
        isDirty: false,
    }),
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(params as Record<string, string>).toString()}`,
}));

import { ListTroopersViewModel } from '../../../../../../resources/svelte/pages/admin/troopers/models/vms/ListTroopersViewModel.svelte';
import { RecruitViewModel } from '../../../../../../resources/svelte/pages/admin/troopers/models/vms/RecruitViewModel.svelte';
import type { ListTroopersPageData } from '../../../../../../resources/svelte/pages/admin/troopers/models/list-types';

const listVm = (filters: Partial<ListTroopersPageData['filters']> = {}) => new ListTroopersViewModel({
    troopers: { data: [], meta: { links: [] } },
    filters: {
        search_term: '',
        membership_role: null,
        organization_id: null,
        sort_col: 'display_name',
        sort_dir: 'asc',
        ...filters,
    },
    organization: null,
    sort_columns: [],
    roles: [],
    organization_options: [],
});

describe('ListTroopersViewModel', () => {
    it('keeps the other filters when one changes', () => {
        const vm = listVm({ search_term: 'fett', organization_id: 4 });

        expect(vm.listUrl({ membership_role: 'moderator' })).toBe(
            '/admin.troopers.list?search_term=fett&membership_role=moderator&organization_id=4&sort_col=display_name&sort_dir=asc',
        );
        expect(vm.listUrl({ organization_id: null })).not.toContain('organization_id');
    });

    it('flips the sorted column and sorts others A to Z', () => {
        const vm = listVm({ sort_col: 'display_name', sort_dir: 'asc' });

        expect(vm.sortUrl('display_name')).toContain('sort_dir=desc');
        expect(vm.sortUrl('last_active_at')).toContain('sort_col=last_active_at&sort_dir=asc');
        expect(vm.sortIcon('display_name')).toBe('fa-sort-up');
        expect(vm.sortIcon('last_active_at')).toBe('fa-sort');
    });

    it('searches with the trimmed text', () => {
        const vm = listVm();

        vm.search_term = '  boba ';
        vm.search(new Event('submit'));

        expect(visit).toHaveBeenLastCalledWith('/admin.troopers.list?search_term=boba&sort_col=display_name&sort_dir=asc');
    });

    it('shows a group when one is picked, and does nothing when it is the same one', () => {
        visit.mockClear();
        const vm = listVm({ organization_id: 4 });

        vm.organization_id = 4;
        expect(visit).not.toHaveBeenCalled();

        vm.organization_id = 9;
        expect(visit).toHaveBeenLastCalledWith(expect.stringContaining('organization_id=9'));
    });
});

describe('RecruitViewModel', () => {
    const vm = new RecruitViewModel({
        organization_options: [],
        identifiers: {
            7: { label: 'TKID', is_required: true },
            8: { label: 'Member ID', is_required: false },
        },
    });

    it('names the identifier for the picked unit', () => {
        expect(vm.identifier_label).toBeNull();

        vm.form.organization_id = 7;
        expect(vm.identifier_label).toBe('TKID');

        vm.form.organization_id = 8;
        expect(vm.identifier_label).toBe('Member ID (optional)');
    });

    it('sends the picked trooper', () => {
        vm.trooper = { id: 12, legal_name: 'Jango Fett', display_name: 'TK-12', email: '' };
        vm.submit(new Event('submit'));

        expect(vm.form.trooper_id).toBe(12);
        expect(formPost).toHaveBeenLastCalledWith('/admin.troopers.recruit?', expect.any(Object));
    });
});
