import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { IEventTypeForm } from "../types";

function generateForm(): InertiaForm<IEventTypeForm> {
    return useForm<IEventTypeForm>({
        name: "",
        description: "",
        is_counted: true,
        is_active: true,
    });
}

export class CreateViewModel
    extends SubmitableViewModel<CreateViewModel, IEventTypeForm>
    implements ISubmitableViewModel<IEventTypeForm> {

    constructor() {
        super();

        this.form = generateForm();
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.event-types.create"));
    };
}
