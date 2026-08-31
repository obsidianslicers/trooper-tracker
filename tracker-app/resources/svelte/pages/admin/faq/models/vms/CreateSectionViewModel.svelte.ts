import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { ISectionForm } from "../types";

export type CreateSectionForm = ISectionForm & {
    label: string;
    icon: string;
};

export class CreateSectionViewModel
    extends SubmitableViewModel<CreateSectionViewModel, CreateSectionForm>
    implements ISubmitableViewModel<ISectionForm> {

    constructor() {
        super();

        this.form = useForm<CreateSectionForm>({ label: "", icon: "" });
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.sections.create");

        this.form.post(url);
    };
}
