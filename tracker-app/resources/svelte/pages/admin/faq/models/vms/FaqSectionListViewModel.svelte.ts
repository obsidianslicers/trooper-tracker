import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { FaqSection } from "../types";

export class FaqSectionListViewModel extends ViewModel {
    deleting: FaqSection | null = $state(null);
    submitting: boolean = $state(false);

    reorder = (ordered_ids: string[]) => {
        const csrf_token = document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content;

        fetch(getRoute("admin.faq.sections.reorder"), {
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

    confirmDelete = (section: FaqSection) => {
        this.deleting = section;
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
            getRoute("admin.faq.sections.delete", { section: this.deleting.id }),
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
