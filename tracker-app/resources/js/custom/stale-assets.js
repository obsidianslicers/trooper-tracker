// After a deploy the old hashed build files are gone, so a tab opened before the deploy
// fails to load the next page's chunk. Reloading picks up the new build. Only reload once
// in a short window so a real network outage can't cause a reload loop.
const STORAGE_KEY = 'tt-stale-assets-reload';
const RELOAD_WINDOW_MS = 30000;

export function reloadOnStaleAssets(event, storage, reload, now = Date.now()) {
    try {
        const last_reload = storage.getItem(STORAGE_KEY);
        if (last_reload !== null && now - Number(last_reload) < RELOAD_WINDOW_MS) {
            return false;
        }
        storage.setItem(STORAGE_KEY, String(now));
    } catch {
        return false;
    }

    event.preventDefault();
    reload();

    return true;
}

if (typeof window !== 'undefined') {
    window.addEventListener('vite:preloadError', (event) => {
        reloadOnStaleAssets(event, window.sessionStorage, () => window.location.reload());
    });
}
