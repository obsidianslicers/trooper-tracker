import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { ListTroopersPageData, TrooperListFilters } from "../list-types";

type FilterChanges = Partial<TrooperListFilters>;

export class ListTroopersViewModel extends ViewModel {
    filters: TrooperListFilters;
    search_term: string = $state("");

    constructor(pageData: ListTroopersPageData) {
        super();

        this.filters = pageData.filters;
        this.search_term = pageData.filters.search_term;
    }

    /** The list with some filters changed, the others kept, back on the first page. */
    listUrl(changes: FilterChanges = {}): string {
        const filters = { ...this.filters, ...changes };
        const query: Record<string, string | number> = {};

        if (filters.search_term) query.search_term = filters.search_term;
        if (filters.membership_role) query.membership_role = filters.membership_role;
        if (filters.organization_id) query.organization_id = filters.organization_id;
        if (filters.sort_col) query.sort_col = filters.sort_col;
        if (filters.sort_dir) query.sort_dir = filters.sort_dir;

        return getRoute("admin.troopers.index", query);
    }

    /** Clicking the sorted column flips its direction; another column sorts A to Z. */
    sortUrl(key: string): string {
        const flips = this.filters.sort_col === key && this.filters.sort_dir === "asc";

        return this.listUrl({ sort_col: key, sort_dir: flips ? "desc" : "asc" });
    }

    sortIcon(key: string): string {
        if (this.filters.sort_col !== key) {
            return "fa-sort";
        }

        return this.filters.sort_dir === "asc" ? "fa-sort-up" : "fa-sort-down";
    }

    search = (e: Event) => {
        e.preventDefault();

        router.visit(this.listUrl({ search_term: this.search_term.trim() }));
    };

    get organization_id(): number | null {
        return this.filters.organization_id;
    }

    set organization_id(value: number | null) {
        if (value !== this.filters.organization_id) {
            router.visit(this.listUrl({ organization_id: value }));
        }
    }

    profileUrl(trooper_id: number): string {
        return getRoute("admin.troopers.show", { trooper: trooper_id });
    }
}
