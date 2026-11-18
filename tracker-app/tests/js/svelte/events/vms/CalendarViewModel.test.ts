import { beforeEach, describe, expect, it, vi } from 'vitest';

const cookies = vi.hoisted(() => new Map<string, string>());

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} }, useForm: vi.fn() }));
vi.mock('../../../../../resources/svelte/lib/cookies', () => ({
    getCookie: (name: string) => cookies.get(name) ?? null,
    setCookie: (name: string, value: string) => cookies.set(name, value),
}));

import {
    CalendarViewModel,
} from '../../../../../resources/svelte/pages/events/models/vms/CalendarViewModel.svelte';
import type { CalendarEvent, CalendarMonth } from '../../../../../resources/svelte/pages/events/models/types';

const event = (overrides: Partial<CalendarEvent> = {}): CalendarEvent => ({
    id: 1,
    name: 'Hospital Visit',
    url: '/events/details/1',
    date: '2026-11-22',
    starts: '10:00am',
    venue_address: '1 Main St, Tampa',
    organization_id: 3,
    logo_url: '/logo.png',
    not_counted_notice: null,
    is_at_risk: false,
    is_mine: false,
    ...overrides,
});

const month = (key: string): CalendarMonth => ({ key, label: key, weeks: [] });

const hosts = [
    { id: 1, name: '501st Legion', parent_id: null, depth: 0 },
    { id: 2, name: 'Florida Garrison', parent_id: 1, depth: 1 },
    { id: 3, name: 'Tampa Squad', parent_id: 2, depth: 2 },
    { id: 4, name: 'Rebel Legion', parent_id: null, depth: 0 },
];

describe('CalendarViewModel', () => {
    beforeEach(() => cookies.clear());

    it('groups the events by day and counts them per month', () => {
        const vm = new CalendarViewModel({
            events: [
                event({ id: 1, date: '2026-11-22' }),
                event({ id: 2, date: '2026-11-22' }),
                event({ id: 3, date: '2026-12-05' }),
            ],
        });

        expect(vm.eventsOn('2026-11-22').map((e) => e.id)).toEqual([1, 2]);
        expect(vm.eventsOn('2026-11-23')).toEqual([]);
        expect(vm.countIn(month('2026-11'))).toBe(2);
        expect(vm.countIn(month('2026-12'))).toBe(1);
    });

    it('returns the holidays for a day', () => {
        const vm = new CalendarViewModel({ holidays: { '2026-11-26': ['Thanksgiving Day'] } });

        expect(vm.holidaysOn('2026-11-26')).toEqual(['Thanksgiving Day']);
        expect(vm.holidaysOn('2026-11-27')).toEqual([]);
    });

    it('filters by search and by squad, including the squads under a garrison', () => {
        const vm = new CalendarViewModel({
            hosting_organizations: hosts,
            events: [
                event({ id: 1, name: 'Toy drive', organization_id: 3 }),
                event({ id: 2, name: 'Parade', organization_id: 4 }),
            ],
        });

        vm.toggleHosting('2', true);
        expect(vm.eventsOn('2026-11-22').map((e) => e.id)).toEqual([1]);

        vm.clearHosting();
        vm.setSearch('parade');
        expect(vm.eventsOn('2026-11-22').map((e) => e.id)).toEqual([2]);
        expect(vm.has_filters).toBe(true);
    });
});
