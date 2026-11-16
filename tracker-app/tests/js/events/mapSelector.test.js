// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import mapSelector from '../../../resources/js/events/mapSelector.js';

//  just enough of google.maps for the map page: listeners are kept so a test can "click"
function fakeGoogleMaps() {
    const listening = (target) => {
        target.handlers = {};
        target.addListener = (name, handler) => {
            target.handlers[name] = handler;
        };
        target.click = () => target.handlers.click();
    };

    const popups = [];

    //  fitBounds "zooms" to Map.fitZoom, as close as Google goes on a single point by default
    class Map {
        static fitZoom = 21;

        constructor() {
            listening(this);
            this.center = null;
            this.zoom = null;
            this.bounds = null;
        }

        setCenter(center) {
            this.center = center;
        }

        setZoom(zoom) {
            this.zoom = zoom;
        }

        getZoom() {
            return this.zoom;
        }

        fitBounds(bounds) {
            this.bounds = bounds;
            this.zoom = Map.fitZoom;
        }
    }

    const markers = [];

    class Marker {
        constructor(options) {
            this.options = options;
            listening(this);
            markers.push(this);
        }

        setPosition(position) {
            this.options.position = position;
        }
    }

    class InfoWindow {
        constructor() {
            this.isOpen = false;
            popups.push(this);
        }

        setContent(content) {
            this.content = content;
        }

        open(options) {
            this.isOpen = true;
            this.anchor = options.anchor;
        }

        close() {
            this.isOpen = false;
        }
    }

    class LatLngBounds {
        constructor() {
            this.points = [];
        }

        extend(point) {
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
            event: { addListenerOnce: (target, name, handler) => handler() },
        },
    };
}

const events = [
    { lat: 41.88, lng: -87.63, name: 'Library Day', url: '/events/1', date_range: 'Sat - Nov 14, 2026 - 10:00am - 2:00pm' },
    { lat: 41.52, lng: -88.08, name: 'Parade', url: '/events/2', date_range: 'Sun - Nov 15, 2026 - 1:00pm - 3:00pm' },
];

describe('mapSelector', () => {
    let fake;
    let map;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });
        vi.spyOn(console, 'warn').mockImplementation(() => {});

        map = mapSelector(events);
        map.$refs = { map: document.createElement('div') };
        map.init();
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

        map.showBootstrapPopup({ ...events[0], name }, map.markers[0]);

        const content = fake.popups[0].content;

        expect(content.querySelector('img')).toBeNull();
        expect(content.querySelector('a').textContent).toBe(name);
    });
});

describe('mapSelector first view', () => {
    let fake;
    let getCurrentPosition;

    function open(list) {
        const map = mapSelector(list);
        map.$refs = { map: document.createElement('div') };
        map.init();

        return map.map;
    }

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

        const map = open(events);

        expect(map.bounds.points).toEqual([
            { lat: 41.88, lng: -87.63 },
            { lat: 41.52, lng: -88.08 },
        ]);
        expect(map.zoom).toBe(9);
        expect(getCurrentPosition).not.toHaveBeenCalled();
    });

    it('stays zoomed out enough to see around a single event', () => {
        fake.maps.Map.fitZoom = 21;

        expect(open([events[0]]).zoom).toBe(12);
    });

    it('shows the continental US when there are no upcoming events', () => {
        const map = open([]);

        expect(map.center).toEqual({ lat: 39.8, lng: -98.6 });
        expect(map.zoom).toBe(4);
        expect(map.bounds).toBeNull();
    });
});

describe('mapSelector "Use Current Location"', () => {
    let fake;
    let answer;
    let map;

    const here = { coords: { latitude: 41.9, longitude: -87.7 } };
    const PERMISSION_DENIED = 1;
    const TIMEOUT = 3;

    beforeEach(() => {
        fake = fakeGoogleMaps();
        vi.stubGlobal('google', { maps: fake.maps });
        //  each test says how the browser answers
        vi.stubGlobal('navigator', {
            geolocation: { getCurrentPosition: (ok, fail) => answer(ok, fail) },
        });

        map = mapSelector(events);
        map.$refs = { map: document.createElement('div') };
        map.init();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('zooms in on the user and marks where they are', () => {
        answer = (ok) => ok(here);

        map.centerOnUser();

        expect(map.map.center).toEqual({ lat: 41.9, lng: -87.7 });
        expect(map.map.zoom).toBe(11);
        expect(map.userMarker.options.title).toBe('You are here');
        expect(map.locating).toBe(false);
        expect(map.locationError).toBe('');
    });

    it('moves the same dot on another click', () => {
        answer = (ok) => ok(here);
        map.centerOnUser();

        answer = (ok) => ok({ coords: { latitude: 42.0, longitude: -88.0 } });
        map.centerOnUser();

        const dots = fake.markers.filter(marker => marker.options.title === 'You are here');

        expect(dots).toHaveLength(1);
        expect(dots[0].options.position).toEqual({ lat: 42.0, lng: -88.0 });
    });

    it('says when the browser blocks it, and keeps the map where it was', () => {
        answer = (ok, fail) => fail({ code: PERMISSION_DENIED, PERMISSION_DENIED });
        const zoom = map.map.zoom;

        map.centerOnUser();

        expect(map.locationError).toContain('turned off');
        expect(map.locating).toBe(false);
        expect(map.map.zoom).toBe(zoom);
        expect(map.userMarker).toBeNull();
    });

    it('offers to try again when the location is slow or unavailable', () => {
        answer = (ok, fail) => fail({ code: TIMEOUT, PERMISSION_DENIED });

        map.centerOnUser();

        expect(map.locationError).toContain('Try again');
    });

    it('clears an earlier error when it works', () => {
        answer = (ok, fail) => fail({ code: TIMEOUT, PERMISSION_DENIED });
        map.centerOnUser();

        answer = (ok) => ok(here);
        map.centerOnUser();

        expect(map.locationError).toBe('');
    });
});
