import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { FaqFormData, FaqItem } from "../types";

function createFaqForm(defaults: FaqFormData): InertiaForm<FaqFormData> {
    return useForm<FaqFormData>(defaults);
}

export class FaqFormViewModel extends SubmitableViewModel<FaqFormViewModel, FaqFormData> {
    mode: "create" | "update";
    faq_id: number | null;

    constructor(mode: "create" | "update", faq: FaqItem | null = null, section_id: number | null = null) {
        super();

        this.mode = mode;
        this.faq_id = faq?.id ?? null;

        this.form = createFaqForm({
            section_id: faq?.section_id ?? section_id,
            title: faq?.title ?? "",
            description: faq?.description ?? "",
            video_url: faq?.video_url ?? "",
        });
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = this.mode === "create"
            ? getRoute("admin.faq.create")
            : getRoute("admin.faq.update", { faq: this.faq_id });

        this.form.post(url);
    };
}
