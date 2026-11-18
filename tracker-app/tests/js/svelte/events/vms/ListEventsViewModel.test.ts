import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));
const cookies = vi.hoisted(() => new Map<string, string>());

vi.mock('@inertiajs/svelte', () => ({ router: { post }, page: { props: {} }, useForm: vi.fn() }));
vi.mock('ziggy-js', () => ({ route: (name: string, params?: Record<string, unknown>) => `/${name}/${params?.notice ?? ''}` }));
vi.mock('../../../../../resources/svelte/lib/cookies', () => ({
    getCookie: (name: string) => cookies.get(name) ?? null,
    setCookie: (name: string, value: string) => cookies.set(name, value),
}));

import {
    ListEventsViewModel,
} from '../../../../../resources/svelte/pages/events/models/vms/ListEventsViewModel.svelte';
import type {
    EventShift,
    EventSummary,
    ListEventsPageData,
} from '../../../../../resources/svelte/pages/events/models/types';

const shift = (overrides: Partial<EventShift> = {}): EventShift => ({
    id: 1,
    url: '/events/details/1#shift-1',
    date: 'Sat Nov 22',
    starts: '10:00am',
    ends: '2:00pm',
    troopers: 2,
    handlers: 0,
    guests: 0,
    my_status: null,
    my_status_label: null,
    ...overrides,
});

const event = (overrides: Partial<EventSummary> = {}): EventSummary => ({
    id: 1,
    name: 'Hospital Visit',
    url: '/events/details/1',
    venue_address: '1 Main St, Tampa',
    organization_id: 3,
    organization_name: 'Tampa Squad',
    logo_url: '/logo.png',
    month_key: '2026-11',
    month_label: 'November 2026',
    weekday: 'Sat',
    day: '22',
    month: 'Nov',
    date_label: 'Sat Nov 22, 2026',
    is_at_risk: false,
    is_locked: false,
    not_counted_notice: null,
    troopers_allowed: null,
    shifts: [shift()],
    organizations: [
        { id: 10, name: '501st', can_attend: true },
        { id: 11, name: 'Rebel Legion', can_attend: false },
    ],
    ...overrides,
});

const pageData = (events: EventSummary[]): ListEventsPageData => ({
    events,
    hosting_organizations: [
        { id: 1, name: 'Florida Garrison', parent_id: null, depth: 0 },
        { id: 2, name: 'Central Region', parent_id: 1, depth: 1 },
        { id: 3, name: 'Tampa Squad', parent_id: 2, depth: 2 },
        { id: 4, name: 'Orlando Squad', parent_id: 2, depth: 2 },
    ],
    notices: { count: 0, notice: null },
    has_map: false,
});

describe('ListEventsViewModel', () => {
    beforeEach(() => {
        cookies.clear();
        post.mockClear();
    });

    it('groups events by month in the order they arrive', () => {
        const vm = new ListEventsViewModel(pageData([
            event({ id: 1 }),
            event({ id: 2 }),
            event({ id: 3, month_key: '2026-12', month_label: 'December 2026' }),
        ]));

        expect(vm.months.map((m) => [m.label, m.events.length])).toEqual([
            ['November 2026', 2],
            ['December 2026', 1],
        ]);
    });

    it('searches event names and venues', () => {
        const vm = new ListEventsViewModel(pageData([
            event({ id: 1, name: 'Hospital Visit' }),
            event({ id: 2, name: 'Parade', venue_address: 'Downtown Orlando' }),
        ]));

        vm.setSearch('orlando');
        expect(vm.filtered.map((e) => e.id)).toEqual([2]);

        vm.setSearch('HOSPITAL');
        expect(vm.filtered.map((e) => e.id)).toEqual([1]);
    });

    it('shows events hosted above a picked unit, but not by its sibling', () => {
        const vm = new ListEventsViewModel(pageData([
            event({ id: 1, organization_id: 3 }),
            event({ id: 2, organization_id: 1 }),
            event({ id: 3, organization_id: 4 }),
        ]));

        vm.toggleHosting('3', true);

        expect(vm.filtered.map((e) => e.id)).toEqual([1, 2]);
        expect(cookies.get('hosting_organization_ids')).toBe('3');
    });

    it('shows events hosted anywhere beneath a picked club or garrison', () => {
        const vm = new ListEventsViewModel(pageData([
            event({ id: 1, organization_id: 3 }),
            event({ id: 2, organization_id: 4 }),
            event({ id: 3, organization_id: 1 }),
            event({ id: 4, organization_id: 99 }),
        ]));

        vm.toggleHosting('2', true);

        expect(vm.filtered.map((e) => e.id)).toEqual([1, 2, 3]);
    });

    it('treats every host picked as no host filter', () => {
        const vm = new ListEventsViewModel(pageData([event()]));

        for (const id of ['1', '2', '3', '4']) {
            vm.toggleHosting(id, true);
        }

        expect(vm.has_hosting_filter).toBe(false);
        expect(cookies.get('hosting_organization_ids')).toBe('');
    });

    it('narrows to my troops and to troops that need troopers', () => {
        const vm = new ListEventsViewModel(pageData([
            event({ id: 1, shifts: [shift({ my_status: 'going', my_status_label: 'Going' })] }),
            event({ id: 2, shifts: [shift({ troopers: 0 })] }),
            event({ id: 3, is_at_risk: true }),
        ]));

        expect(vm.my_event_count).toBe(1);

        vm.toggleOnlyMine();
        expect(vm.filtered.map((e) => e.id)).toEqual([1]);
        expect(vm.myStatusLabel(vm.filtered[0])).toBe('Going');

        vm.toggleOnlyMine();
        vm.toggleNeedsTroopers();
        expect(vm.filtered.map((e) => e.id)).toEqual([2, 3]);
    });

    it('works out whether a shift is full, empty or filling', () => {
        const vm = new ListEventsViewModel(pageData([]));
        const limited = event({ troopers_allowed: 4 });

        expect(vm.shiftState(limited, shift({ troopers: 4 }))).toBe('full');
        expect(vm.shiftState(limited, shift({ troopers: 0, guests: 1 }))).toBe('going');
        expect(vm.shiftState(event(), shift({ troopers: 0 }))).toBe('empty');
        expect(vm.shiftFill(limited, shift({ troopers: 1 }))).toBe(25);
        expect(vm.shiftFill(event(), shift())).toBeNull();
    });

    it('restores filters from the cookies the other event pages share', () => {
        cookies.set('hosting_organization_ids', '3,99');
        cookies.set('display_allowed_orgs', '0');

        const vm = new ListEventsViewModel(pageData([]));

        expect(vm.hosting_organization_ids).toEqual(['3']);
        expect(vm.show_allowed_orgs).toBe(false);
    });

    it('clears every filter at once', () => {
        const vm = new ListEventsViewModel(pageData([event()]));
        vm.setSearch('x');
        vm.toggleHosting('3', true);
        vm.toggleOnlyMine();

        vm.clearFilters();

        expect(vm.has_filters).toBe(false);
        expect(cookies.get('events_only_mine')).toBe('0');
    });

    it('counts the filters folded behind the phone Filters button, but not the search', () => {
        const vm = new ListEventsViewModel(pageData([event()]));
        vm.setSearch('x');
        expect(vm.filter_count).toBe(0);

        vm.toggleHosting('3', true);
        vm.toggleHosting('4', true);
        vm.toggleOnlyMine();
        expect(vm.filter_count).toBe(3);

        expect(vm.show_filters).toBe(false);
        vm.toggleFilters();
        expect(vm.show_filters).toBe(true);
    });

    it('hides a notice right away and marks it read', () => {
        const vm = new ListEventsViewModel({
            ...pageData([]),
            notices: {
                count: 1,
                notice: { id: 7, type: 'info', heading: 'NOW HEAR THIS!', title: 'Hi', html: '<p>Hi</p>' },
            },
        });

        vm.dismissNotice();

        expect(vm.notices.notice).toBeNull();
        expect(post).toHaveBeenCalledWith('/account.update-notice-as-read/7', {}, expect.objectContaining({
            only: ['flash', 'notices'],
        }));
    });
});
