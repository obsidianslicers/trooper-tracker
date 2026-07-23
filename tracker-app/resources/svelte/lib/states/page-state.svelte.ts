// lib/states/page-state.svelte.ts
import { page } from '@inertiajs/svelte';

class PageState {
    #title: string | null = $state(null);

    get title(): string | null { return this.#title ?? null; }
    set title(value: string | null) { this.#title = value; }

    /**
     * Svelte 5 fine-grained derived properties.
     * These will accurately cache and re-evaluate only when #rawStoreValue changes.
     */
    get appName(): string {
        return page.props?.config?.branding?.name ?? "Troop Tracker";
    }
}

export default new PageState();