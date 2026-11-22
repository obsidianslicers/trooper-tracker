import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { CostumeRow, ListPageData } from "../types";

export class ListViewModel extends ViewModel {
    costumes: CostumeRow[] = $state([]);
    search_term: string = $state("");

    constructor(pageData: ListPageData) {
        super();

        this.costumes = pageData.costumes.data;
        this.search_term = pageData.search_term;
    }

    search = (e: Event) => {
        e.preventDefault();

        const term = this.search_term.trim();

        router.visit(getRoute("admin.costumes.list", term.length > 0 ? { search_term: term } : {}));
    };

    updateUrl(costume: CostumeRow): string {
        return getRoute("admin.costumes.update", { costume: costume.id });
    }

    deleteUrl(costume: CostumeRow): string {
        return getRoute("admin.costumes.delete", { costume: costume.id });
    }
}
