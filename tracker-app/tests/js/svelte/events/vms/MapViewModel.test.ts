import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const cookies = vi.hoisted(() => new Map<string, string>());

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} }, useForm: vi.fn() }));
vi.mock('../../../../../resources/svelte/lib/cookies', () => ({
    getCookie: (name: string) => cookies.get(name) ?? null,
    setCookie: (name: string, value: string) => cookies.set(name, value),
}));

import { MapViewModel } from '../../../../../resources/svelte/pages/events/models/vms/MapViewModel.svelte';
import type { MapEvent } from '../../../../../resources/svelte/pages/events/models/types';

const event = (overrides: Partial<MapEvent> = {}): MapEvent => ({
    id: 1,
    name: 'Library Day',
    url: '/events/details/1',
    date_range: 'Sat - Nov 14, 2026 - 10:00am - 2:00pm',
    venue_address: '1 Main St, Tampa',
    organization_id: 3,
    lat: 41.88,
    lng: -87.63,
    ...overrides,
});

const hosts = [
    { id: 1, name: 'Florida Garrison', parent_id: null, depth: 0 },
    { id: 3, name: 'Tampa Squad', parent_id: 1, depth: 1 },
    { id: 4, name: 'Orlando Squad', parent_id: 1, depth: 1 },
];

const PERMISSION_DENIED = 1;
const TIMEOUT = 3;

describe('MapViewModel', () => {
    let answer: (ok: (p: unknown) => void, fail: (e: unknown) => void) => void;

    beforeEach(() => {
        cookies.clear();
        vi.stubGlobal('navigator', {
            geolocation: { getCurrentPosition: (ok: never, fail: never) => answer(ok, fail) },
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('filters the pins by search and squad', () => {
        const vm = new MapViewModel({
            events: [
                event({ id: 1, name: 'Library Day', organization_id: 3 }),
                event({ id: 2, name: 'Parade', venue_address: 'Orlando', organization_id: 4 }),
            ],
            hosting_organizations: hosts,
        });

        vm.setSearch('parade');
        expect(vm.filtered.map((e) => e.id)).toEqual([2]);

        vm.setSearch('');
        vm.toggleHosting('3', true);
        expect(vm.filtered.map((e) => e.id)).toEqual([1]);
    });

    it('keeps where the user is, once they ask', () => {
        answer = (ok) => ok({ coords: { latitude: 41.9, longitude: -87.7 } });
        const vm = new MapViewModel({ events: [event()] });

        vm.locate();

        expect(vm.user_position).toEqual({ lat: 41.9, lng: -87.7 });
        expect(vm.locating).toBe(false);
        expect(vm.location_error).toBe('');
    });

    it('says when the browser blocks it', () => {
        answer = (_ok, fail) => fail({ code: PERMISSION_DENIED, PERMISSION_DENIED });
        const vm = new MapViewModel({ events: [event()] });

        vm.locate();

        expect(vm.location_error).toContain('turned off');
        expect(vm.locating).toBe(false);
        expect(vm.user_position).toBeNull();
    });

    it('offers to try again when the location is slow, and clears it once it works', () => {
        const vm = new MapViewModel({ events: [event()] });

        answer = (_ok, fail) => fail({ code: TIMEOUT, PERMISSION_DENIED });
        vm.locate();
        expect(vm.location_error).toContain('Try again');

        answer = (ok) => ok({ coords: { latitude: 41.9, longitude: -87.7 } });
        vm.locate();
        expect(vm.location_error).toBe('');
    });
});
