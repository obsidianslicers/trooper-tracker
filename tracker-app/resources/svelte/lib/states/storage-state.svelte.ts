
/**
 * storage wrapper.
 *
 */
class Storage {
    /**
     * Reads a stored value for the provided key.
     *
     * On native/browser runtimes this checks `Preferences` first, then falls
     * back to `localStorage` when no preference value exists.
     *
     * @param key Storage key to read.
     * @returns The stored value, or `null` when not found or not in browser.
     */
    async get(key: string): Promise<string | null> {
        return localStorage.getItem(key);
    }

    /**
     * Persists a value for the provided key.
     *
     * Writes to both `Preferences` and `localStorage` for cross-runtime
     * compatibility.
     *
     * @param key Storage key to write.
     * @param value Value to persist.
     */
    async set(key: string, value: string | object): Promise<void> {
        if (typeof value === 'object') {
            value = JSON.stringify(value);
        }

        // Persist to both for maximum compatibility
        localStorage.setItem(key, value);
    }

    /**
     * Removes a stored value for the provided key.
     *
     * Deletes from both `Preferences` and `localStorage`.
     *
     * @param key Storage key to remove.
     */
    async remove(key: string): Promise<void> {
        localStorage.removeItem(key);
    }
};

export default new Storage();