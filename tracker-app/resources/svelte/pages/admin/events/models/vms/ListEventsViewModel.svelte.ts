import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { AdminEventFilters, ListPageData } from "../list-types";

export class ListEventsViewModel extends ViewModel {
    filters: AdminEventFilters;
    search_term: string = $state("");

    constructor(pageData: ListPageData) {
        super();

        this.filters = pageData.filters;
        this.search_term = pageData.filters.search_term;
    }

    /** The list with some filters changed and the others kept, back on the first page. */
    listUrl(changes: Partial<AdminEventFilters> = {}): string {
        const filters = { ...this.filters, ...changes };
        const query: Record<string, string | number> = {};

        if (filters.search_term) query.search_term = filters.search_term;
        if (filters.status) query.status = filters.status;
        if (filters.organization_id) query.organization_id = filters.organization_id;

        return getRoute("admin.events.index", query);
    }

    get create_url(): string {
        return getRoute("admin.events.create", { organization_id: this.filters.organization_id });
    }

    search = (e: Event) => {
        e.preventDefault();

        router.visit(this.listUrl({ search_term: this.search_term.trim() }));
    };
}
