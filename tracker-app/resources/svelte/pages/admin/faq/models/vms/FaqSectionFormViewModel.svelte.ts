import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { FaqSection, FaqSectionFormData } from "../types";

function createFaqSectionForm(defaults: FaqSectionFormData): InertiaForm<FaqSectionFormData> {
    return useForm<FaqSectionFormData>(defaults);
}

export class FaqSectionFormViewModel extends SubmitableViewModel<FaqSectionFormViewModel, FaqSectionFormData> {
    mode: "create" | "update";
    section_id: number | null;

    constructor(mode: "create" | "update", section: FaqSection | null = null) {
        super();

        this.mode = mode;
        this.section_id = section?.id ?? null;

        this.form = createFaqSectionForm({
            label: section?.label ?? "",
            icon: section?.icon ?? "",
        });
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = this.mode === "create"
            ? getRoute("admin.faq.sections.create")
            : getRoute("admin.faq.sections.update", { section: this.section_id });

        this.form.post(url);
    };
}
