// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/svelte', () => ({ router: { post: vi.fn() }, page: { props: {} }, useForm: vi.fn() }));

import { EventMapPins } from '../../../../resources/svelte/pages/events/models/EventMapPins';
import type { MapEvent } from '../../../../resources/svelte/pages/events/models/types';


//  just enough of google.maps for the map page: listeners are kept so a test can "click"
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function fakeGoogleMaps(): any {
    const listening = (target: any) => {
        target.handlers = {};
        target.addListener = (name: string, handler: () => void) => {
            target.handlers[name] = handler;
        };
        target.click = () => target.handlers.click();
    };

    const popups: any[] = [];

    //  fitBounds "zooms" to Map.fitZoom, as close as Google goes on a single point by default
    class Map {
        static fitZoom = 21;

        [key: string]: any;

        constructor() {
            listening(this);
            this.center = null;
            this.zoom = null;
            this.bounds = null;
        }

        setCenter(center: unknown) {
            this.center = center;
        }

        setZoom(zoom: number) {
            this.zoom = zoom;
        }

        getZoom() {
            return this.zoom;
        }

        fitBounds(bounds: unknown) {
            this.bounds = bounds;
            this.zoom = Map.fitZoom;
        }
    }

    const markers: any[] = [];

    class Marker {
        [key: string]: any;

        constructor(options: any) {
            this.options = options;
            listening(this);
            markers.push(this);
        }

        setPosition(position: unknown) {
            this.options.position = position;
        }

        setMap(map: unknown) {
            this.map = map;
        }
    }

    class InfoWindow {
        [key: string]: any;

        constructor() {
            this.isOpen = false;
            popups.push(this);
        }

        setContent(content: unknown) {
            this.content = content;
        }

        open(options: any) {
            this.isOpen = true;
            this.anchor = options.anchor;
        }

        close() {
            this.isOpen = false;
        }
    }

    class LatLngBounds {
        points: unknown[];

        constructor() {
            this.points = [];
        }

        extend(point: unknown) {
            this.points.push(point);
        }
    }

    return {
        popups,
        markers,
        maps: {
            Map,
            Marker,
            InfoWindow,
            LatLngBounds,
            SymbolPath: { CIRCLE: 0 },
            //  the map is already idle, so a once-listener runs straight away
            event: { addListenerOnce: (_target: unknown, _name: string, handler: () => void) => handler() },
        },
    };
}

const pin = (overrides: Partial<MapEvent>): MapEvent => ({
    name: 'Library Day',
    url: '/events/1',
    date_range: 'Sat - Nov 14, 2026 - 10:00am - 2:00pm',
    venue_address: null,
    organization_id: null,
    lat: 41.88,
    lng: -87.63,
    ...overrides,
});

const events = [
    pin({}),
    pin({ lat: 41.52, lng: -88.08, name: 'Parade', url: '/events/2', date_range: 'Sun - Nov 15, 2026 - 1:00pm - 3:00pm' }),
];

function open(list: MapEvent[]): EventMapPins {
    const pins = new EventMapPins(document.createElement('div'));
    pins.showEvents(list);

    return pins;
}

describe('EventMapPins', () => {
    let fake: any;
    let map: EventMapPins;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });

        map = open(events);
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('keeps only one event open: another pin moves the popup there', () => {
        map.markers[0].click();
        map.markers[1].click();

        expect(fake.popups).toHaveLength(1);
        expect(fake.popups[0].isOpen).toBe(true);
        expect(fake.popups[0].anchor).toBe(map.markers[1]);
        expect(fake.popups[0].content.textContent).toContain('Parade');
        expect(fake.popups[0].content.textContent).not.toContain('Library Day');
    });

    it('closes the popup on a click on the map', () => {
        map.markers[0].click();
        map.map.click();

        expect(fake.popups[0].isOpen).toBe(false);
    });

    it('shows the name, date and link', () => {
        map.markers[0].click();

        const link = fake.popups[0].content.querySelector('a');

        expect(link.textContent).toBe('Library Day');
        expect(link.getAttribute('href')).toBe('/events/1');
        expect(fake.popups[0].content.textContent).toContain('Sat - Nov 14, 2026 - 10:00am - 2:00pm');
    });

    it('shows an event name as text, not HTML', () => {
        const name = 'Fish & Chips <img src=x onerror="alert(1)">';

        map.openPopup({ ...events[0], name }, map.markers[0]);

        const content = fake.popups[0].content;

        expect(content.querySelector('img')).toBeNull();
        expect(content.querySelector('a').textContent).toBe(name);
    });
});

describe('EventMapPins first view', () => {
    let fake: any;
    let getCurrentPosition: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });
        getCurrentPosition = vi.fn();
        vi.stubGlobal('navigator', { geolocation: { getCurrentPosition } });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('fits every upcoming event, and never asks for the location', () => {
        fake.maps.Map.fitZoom = 9;

        const map = open(events).map;

        expect(map.bounds.points).toEqual([
            { lat: 41.88, lng: -87.63 },
            { lat: 41.52, lng: -88.08 },
        ]);
        expect(map.zoom).toBe(9);
        expect(getCurrentPosition).not.toHaveBeenCalled();
    });

    it('stays zoomed out enough to see around a single event', () => {
        fake.maps.Map.fitZoom = 21;

        expect(open([events[0]]).map.zoom).toBe(12);
    });

    it('shows the continental US when there are no upcoming events', () => {
        const map = open([]).map;

        expect(map.center).toEqual({ lat: 39.8, lng: -98.6 });
        expect(map.zoom).toBe(4);
        expect(map.bounds).toBeNull();
    });
});

describe('EventMapPins as the filters change', () => {
    let fake: any;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('swaps the pins without moving the map again', () => {
        const pins = open(events);
        const first = pins.markers;
        pins.map.setZoom(7);

        pins.showEvents([events[1]]);

        expect(first.every((marker: any) => marker.map === null)).toBe(true);
        expect(pins.markers).toHaveLength(1);
        expect(pins.markers[0].options.title).toBe('Parade');
        expect(pins.map.zoom).toBe(7);
    });

    it('closes an open popup when the pins change', () => {
        const pins = open(events);
        pins.markers[0].click();

        pins.showEvents([events[1]]);

        expect(fake.popups[0].isOpen).toBe(false);
    });
});

describe('EventMapPins user location', () => {
    let fake: any;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('zooms in on the user and marks where they are', () => {
        const pins = open(events);

        pins.showUser({ lat: 41.9, lng: -87.7 });

        expect(pins.map.center).toEqual({ lat: 41.9, lng: -87.7 });
        expect(pins.map.zoom).toBe(11);
        expect(pins.user_marker.options.title).toBe('You are here');
    });

    it('moves the same dot on another click', () => {
        const pins = open(events);

        pins.showUser({ lat: 41.9, lng: -87.7 });
        pins.showUser({ lat: 42.0, lng: -88.0 });

        const dots = fake.markers.filter((marker: any) => marker.options.title === 'You are here');

        expect(dots).toHaveLength(1);
        expect(dots[0].options.position).toEqual({ lat: 42.0, lng: -88.0 });
    });
});
