import { beforeEach, describe, expect, it, vi } from 'vitest';

const cookies = vi.hoisted(() => new Map<string, string>());

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} }, useForm: vi.fn() }));
vi.mock('../../../../../resources/svelte/lib/cookies', () => ({
    getCookie: (name: string) => cookies.get(name) ?? null,
    setCookie: (name: string, value: string) => cookies.set(name, value),
}));

import {
    PastEventsViewModel,
} from '../../../../../resources/svelte/pages/events/models/vms/PastEventsViewModel.svelte';
import type { PastEvent } from '../../../../../resources/svelte/pages/events/models/types';

const event = (overrides: Partial<PastEvent> = {}): PastEvent => ({
    id: 1,
    name: 'Library Day',
    url: '/events/details/1',
    date_label: 'Nov 1, 2026',
    organization_id: 3,
    organization_name: 'Tampa Squad',
    logo_url: '/logo.png',
    shifts: 1,
    unique_troopers: 4,
    total_troopers: 4,
    direct_funds: 0,
    indirect_funds: 0,
    ...overrides,
});

describe('PastEventsViewModel', () => {
    beforeEach(() => cookies.clear());

    it('knows whether it is showing cancelled events', () => {
        expect(new PastEventsViewModel({ status: 'cancelled', lookback_days: 30 }).is_cancelled).toBe(true);
        expect(new PastEventsViewModel({ status: 'closed', lookback_days: null }).is_cancelled).toBe(false);
    });

    it('filters the rows by name', () => {
        const vm = new PastEventsViewModel({
            events: [event({ id: 1, name: 'Library Day' }), event({ id: 2, name: 'Walk for Kids' })],
        });

        vm.setSearch('walk');

        expect(vm.filtered.map((e) => e.id)).toEqual([2]);
    });

    it('restores the squad filter saved by the other events pages', () => {
        cookies.set('hosting_organization_ids', '4');
        const vm = new PastEventsViewModel({
            hosting_organizations: [
                { id: 3, name: 'Tampa Squad', parent_id: null, depth: 0 },
                { id: 4, name: 'Rebel Legion', parent_id: null, depth: 0 },
            ],
            events: [event({ id: 1, organization_id: 3 }), event({ id: 2, organization_id: 4 })],
        });

        expect(vm.filtered.map((e) => e.id)).toEqual([2]);
    });
});
