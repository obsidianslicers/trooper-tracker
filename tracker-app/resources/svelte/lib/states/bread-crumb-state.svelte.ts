import pageState from '$lib/states/page-state.svelte';

interface BreadCrumb {
    id: number;
    title: string;
    url?: string;
}

/**
 * Provides a reactive breadcrumb trail service backed by Svelte `$state`.
 *
 * Manages breadcrumb segments for page-level navigation context and exposes
 * read-only accessors plus mutation helpers for building and clearing trails.
 */
class BreadCrumbState {
    /**
     * Reactive in-memory breadcrumb trail shared across consumers.
     *
     * Kept private to ensure all mutations go through service methods.
     */
    #trail = $state<BreadCrumb[]>([]);

    /**
     * Indicates whether the trail currently contains breadcrumb segments.
     *
     * @returns `true` when at least one breadcrumb exists.
     */
    get exists(): boolean {
        return this.#trail.length > 0;
    }

    /**
     * Returns the full breadcrumb trail.
     *
     * @returns Ordered breadcrumb segments from root to current location.
     */
    get crumbs(): BreadCrumb[] {
        return this.#trail;
    }

    /**
     * Clears all breadcrumb segments from the trail.
     *
     * Useful when entering a new top-level section.
     */
    clear(): void {
        this.#trail = [];
    }

    /**
     * Appends a non-link breadcrumb segment.
     *
     * @param title - Display text for the new breadcrumb segment.
     * @param url - Optional navigation target for the new breadcrumb segment.
     */
    home(title: string, url: string): BreadCrumbState {
        this.clear();

        return this.add(title, url);
    }

    /**
     * Appends a non-link breadcrumb segment.
     *
     * @param title - Display text for the new breadcrumb segment.
     * @param url - Optional navigation target for the new breadcrumb segment.
     */
    add(title: string, url: string): BreadCrumbState {
        if (url.startsWith('../')) {
            const last = this.#trail[this.#trail.length - 1];

            if (last?.url) {
                url = last.url + '/' + url.slice(3);
            }
        }

        if (url.endsWith('/')) {
            url = url.slice(0, -1);
        }

        this.#trail.push({ id: this.crumbs.length + 1, title, url });

        return this;
    }

    /**
     * Appends a non-link breadcrumb segment.
     *
     * @param title - Display text for the new breadcrumb segment.
     * @param url - Optional navigation target for the new breadcrumb segment.
     */
    title(title: string): void {
        pageState.title = title;

        this.#trail.push({ id: this.crumbs.length + 1, title });
    }
}

/**
 * Singleton breadcrumb state instance used throughout the application.
 */
export default new BreadCrumbState();