import { DeletableListViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqItem } from "../types";

export class FaqListViewModel extends DeletableListViewModel<FaqItem> {
    section_id: number | null;
    sortable: boolean;

    constructor(section_id: number | null, sortable: boolean) {
        super();

        this.section_id = section_id;
        this.sortable = sortable;
    }

    reorder = (ordered_ids: string[]) => {
        router.post(
            getRoute("admin.faq.reorder"),
            { ids: ordered_ids },
            { preserveState: true, preserveScroll: true },
        );
    };

    protected deleteRoute(item: FaqItem): string {
        return getRoute("admin.faq.delete", { faq: item.id });
    }
}
