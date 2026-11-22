import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";

type CreateForm = { name: string };

export class CreateViewModel
    extends SubmitableViewModel<CreateViewModel, CreateForm>
    implements ISubmitableViewModel<CreateForm> {

    constructor() {
        super();

        this.form = useForm<CreateForm>({ name: "" });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.costumes.create"));
    };
}
