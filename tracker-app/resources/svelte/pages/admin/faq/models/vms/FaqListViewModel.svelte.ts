import { type Paginated, ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqItem } from "../types";

export type FaqSection = {
    id: number;
    label: string;
    icon: string;
    sort_order: number;
    faqs_count: number;
    update_route?: string;
};

export type FaqListPageData = {
    items: FaqItem[] | Paginated<FaqItem>;
    sections: FaqSection[];
    section_id: number | null;
    sortable: boolean;
}

export abstract class DeletableListViewModel<T extends { id: number }> extends ViewModel {
    deleting: T | null = $state(null);
    submitting: boolean = $state(false);

    protected abstract deleteRoute(item: T): string;

    get show(): boolean {
        return this.deleting !== null;
    }

    set show(value: boolean) {
        if (!value) {
            this.deleting = null;
        }
    }

    confirmDelete = (item: T) => {
        this.deleting = item;
    };

    cancelDelete = () => {
        this.deleting = null;
    };

    delete = (e: Event) => {
        e.preventDefault();

        if (!this.deleting) {
            return;
        }

        this.submitting = true;

        router.post(
            this.deleteRoute(this.deleting),
            {},
            {
                onFinish: () => {
                    this.submitting = false;
                    this.deleting = null;
                },
            },
        );
    };
}
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
