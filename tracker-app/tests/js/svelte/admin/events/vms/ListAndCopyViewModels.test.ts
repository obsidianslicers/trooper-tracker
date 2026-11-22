import { describe, expect, it, vi } from 'vitest';

const { visit, formPost } = vi.hoisted(() => ({ visit: vi.fn(), formPost: vi.fn() }));

vi.mock('@inertiajs/svelte', () => ({
    router: { visit },
    useForm: (data: Record<string, unknown>) => ({ ...data, post: formPost, errors: {}, processing: false, isDirty: false }),
}));
vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown> = {}) =>
        `/${name}?${new URLSearchParams(
            Object.fromEntries(Object.entries(params).filter(([, v]) => v !== null && v !== undefined)) as Record<string, string>,
        ).toString()}`,
}));

import { ListEventsViewModel } from '../../../../../../resources/svelte/pages/admin/events/models/vms/ListEventsViewModel.svelte';
import { CopyEventViewModel } from '../../../../../../resources/svelte/pages/admin/events/models/vms/CopyEventViewModel.svelte';

const listVm = (filters = {}) => new ListEventsViewModel({
    events: { data: [], meta: { links: [] } },
    filters: { status: null, search_term: '', organization_id: null, ...filters },
    organization: null,
    statuses: [],
});

describe('ListEventsViewModel', () => {
    it('keeps the other filters when one changes', () => {
        const vm = listVm({ search_term: 'parade', organization_id: 4 });

        expect(vm.listUrl({ status: 'open' })).toBe('/admin.events.index?search_term=parade&status=open&organization_id=4');
        expect(vm.listUrl({ organization_id: null })).toBe('/admin.events.index?search_term=parade');
    });

    it('creates an event for the organization being shown', () => {
        expect(listVm({ organization_id: 4 }).create_url).toBe('/admin.events.create?organization_id=4');
        expect(listVm().create_url).toBe('/admin.events.create?');
    });

    it('searches with the trimmed text', () => {
        const vm = listVm({ status: 'open' });

        vm.search_term = ' gala ';
        vm.search(new Event('submit'));

        expect(visit).toHaveBeenLastCalledWith('/admin.events.index?search_term=gala&status=open');
    });
});

describe('CopyEventViewModel', () => {
    it('copies with the new name and start', () => {
        const vm = new CopyEventViewModel({
            event: { id: 7, name: 'COPY OF Parade', event_start: '2030-05-04 09:30', organization_name: 'E2E Garrison' },
        });

        expect(vm.form.name).toBe('COPY OF Parade');
        expect(vm.cancel_url).toBe('/admin.events.edit?event=7');

        vm.submit(new Event('submit'));
        expect(formPost).toHaveBeenLastCalledWith('/admin.events.copy.store?event=7');
    });
});
