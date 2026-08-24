import { DeletableListViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqSection } from "../types";

export class FaqSectionListViewModel extends DeletableListViewModel<FaqSection> {
    reorder = (ordered_ids: string[]) => {
        router.post(
            getRoute("admin.faq.sections.reorder"),
            { ids: ordered_ids },
            { preserveState: true, preserveScroll: true },
        );
    };

    protected deleteRoute(section: FaqSection): string {
        return getRoute("admin.faq.sections.delete", { section: section.id });
    }
}
