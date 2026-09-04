import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { ISectionForm } from "../types";

function generateForm(): InertiaForm<CreateSectionForm> {
    return useForm<CreateSectionForm>({ label: "", icon: "", });
}

type CreateSectionForm = ISectionForm & {};

export class CreateSectionViewModel
    extends SubmitableViewModel<CreateSectionViewModel, CreateSectionForm>
    implements ISubmitableViewModel<ISectionForm> {

    constructor() {
        super();

        this.form = generateForm();
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.sections.create");

        this.form.post(url);
    };
}
