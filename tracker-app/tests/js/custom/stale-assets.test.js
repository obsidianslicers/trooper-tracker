import { describe, expect, it, vi } from 'vitest';
import { reloadOnStaleAssets } from '../../../resources/js/custom/stale-assets.js';

function memoryStorage() {
    const items = new Map();
    return {
        getItem: (key) => (items.has(key) ? items.get(key) : null),
        setItem: (key, value) => items.set(key, value),
    };
}

describe('reloadOnStaleAssets', () => {
    it('reloads and stops the error the first time a build file is missing', () => {
        const event = { preventDefault: vi.fn() };
        const reload = vi.fn();

        const reloaded = reloadOnStaleAssets(event, memoryStorage(), reload, 1000);

        expect(reloaded).toBe(true);
        expect(reload).toHaveBeenCalledOnce();
        expect(event.preventDefault).toHaveBeenCalledOnce();
    });

    it('does not reload again within 30 seconds, so an outage cannot loop', () => {
        const storage = memoryStorage();
        const reload = vi.fn();
        reloadOnStaleAssets({ preventDefault: vi.fn() }, storage, reload, 1000);
        const event = { preventDefault: vi.fn() };

        const reloaded = reloadOnStaleAssets(event, storage, reload, 20000);

        expect(reloaded).toBe(false);
        expect(reload).toHaveBeenCalledOnce();
        expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('reloads again once the window has passed', () => {
        const storage = memoryStorage();
        const reload = vi.fn();
        reloadOnStaleAssets({ preventDefault: vi.fn() }, storage, reload, 1000);

        reloadOnStaleAssets({ preventDefault: vi.fn() }, storage, reload, 32000);

        expect(reload).toHaveBeenCalledTimes(2);
    });

    it('lets the error through when storage is unavailable', () => {
        const storage = { getItem: () => { throw new Error('blocked'); }, setItem: vi.fn() };
        const event = { preventDefault: vi.fn() };
        const reload = vi.fn();

        expect(reloadOnStaleAssets(event, storage, reload, 1000)).toBe(false);
        expect(reload).not.toHaveBeenCalled();
    });
});
