import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

import type { IItemForm } from "../types";

function generateForm(): InertiaForm<CreateItemForm> {
    const data = {
        section_id: null,
        title: "",
        description: null,
        video_url: null,
    };
    return useForm<CreateItemForm>(data);
}

type CreateItemForm = IItemForm & {};

export class CreateItemViewModel
    extends SubmitableViewModel<CreateItemViewModel, CreateItemForm>
    implements ISubmitableViewModel<IItemForm> {

    constructor() {
        super();

        this.form = generateForm();
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.items.create");

        this.form.post(url);
    };
}
