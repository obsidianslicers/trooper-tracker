import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { AwardRow, ListPageData } from "../types";

export class ListViewModel extends ViewModel {
    awards: AwardRow[] = $state([]);
    search_term: string = $state("");
    organization: ListPageData["organization"] = $state(null);

    constructor(pageData: ListPageData) {
        super();

        this.awards = pageData.awards.data;
        this.search_term = pageData.search_term;
        this.organization = pageData.organization;
    }

    /** The list with one filter changed and the others kept, back on the first page. */
    listUrl(changes: { search_term?: string; organization_id?: number | null }): string {
        const query: Record<string, string | number> = {};
        const search_term = changes.search_term ?? this.search_term;
        const organization_id = "organization_id" in changes
            ? changes.organization_id
            : this.organization?.id;

        if (search_term.trim().length > 0) {
            query.search_term = search_term.trim();
        }

        if (organization_id) {
            query.organization_id = organization_id;
        }

        return getRoute("admin.awards.index", query);
    }

    search = (e: Event) => {
        e.preventDefault();

        router.visit(this.listUrl({ search_term: this.search_term }));
    };

    get create_url(): string {
        return getRoute("admin.awards.create", { organization_id: this.organization?.id });
    }

    updateUrl(award: AwardRow): string {
        return getRoute("admin.awards.edit", { award: award.id });
    }

    copyUrl(award: AwardRow): string {
        return getRoute("admin.awards.create", { copy_id: award.id });
    }

    troopersUrl(award: AwardRow): string {
        return getRoute("admin.awards.troopers.index", { award: award.id });
    }
}
