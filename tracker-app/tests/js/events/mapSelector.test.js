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

    class Map {
        constructor() {
            listening(this);
        }

        setCenter() {}

        setZoom() {}

        fitBounds() {}
    }

    class Marker {
        constructor(options) {
            this.options = options;
            listening(this);
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
        extend() {}
    }

    return {
        popups,
        maps: {
            Map,
            Marker,
            InfoWindow,
            LatLngBounds,
            SymbolPath: { CIRCLE: 0 },
            event: { addListenerOnce() {} },
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
        map.$el = document.createElement('div');
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
