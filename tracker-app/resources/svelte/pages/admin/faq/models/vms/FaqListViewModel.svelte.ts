import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqItem } from "../types";

export class FaqListViewModel extends ViewModel {
    section_id: number | null;
    sortable: boolean;
    deleting: FaqItem | null = $state(null);
    submitting: boolean = $state(false);

    constructor(section_id: number | null, sortable: boolean) {
        super();

        this.section_id = section_id;
        this.sortable = sortable;
    }

    reorder = (ordered_ids: string[]) => {
        const csrf_token = document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content;

        fetch(getRoute("admin.faq.reorder"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf_token ?? "",
            },
            body: JSON.stringify({ ids: ordered_ids }),
        });
    };

    get show(): boolean {
        return this.deleting !== null;
    }

    set show(value: boolean) {
        if (!value) {
            this.deleting = null;
        }
    }

    confirmDelete = (item: FaqItem) => {
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
            getRoute("admin.faq.delete", { faq: this.deleting.id }),
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
