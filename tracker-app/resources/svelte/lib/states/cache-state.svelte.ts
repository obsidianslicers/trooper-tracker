import { ONE_DAY_MS } from '$lib/constants';
import storageStateSvelte from './storage-state.svelte';

export type Cached<T> = {
    data: T;
    cachedAt: number;
    lifespan: number;
};


class CacheState {
    async clear(key: string): Promise<void> {
        await storageStateSvelte.remove(key);
    }

    async set<T>(key: string, data: T, lifespan: number = ONE_DAY_MS): Promise<void> {
        const item: Cached<T> = {
            data,
            cachedAt: Date.now(),
            lifespan,
        };
        await storageStateSvelte.set(key, JSON.stringify(item));
    }

    async get<T>(key: string): Promise<T | null> {
        // Try to load from cache first
        const raw = await storageStateSvelte.get(key);
        let cached: Cached<T> | null = null;

        if (raw) {
            try {
                cached = JSON.parse(raw) as Cached<T>;

                const expired = (Date.now() - cached.cachedAt) > cached.lifespan;
                if (expired) {
                    await storageStateSvelte.remove(key);
                    return null;
                }
            }
            catch {
                // Ignore parse errors, fetch fresh
            }
        }

        if (cached) {
            return cached.data;
        }

        return null;
    }
}

export default new CacheState();