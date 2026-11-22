import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { CreatePageData } from "../types";

type CreateForm = { name: string };

export class CreateViewModel
    extends SubmitableViewModel<CreateViewModel, CreateForm>
    implements ISubmitableViewModel<CreateForm> {

    parent: CreatePageData["parent"];

    constructor(pageData: CreatePageData) {
        super();

        this.parent = pageData.parent;
        this.form = useForm<CreateForm>({ name: "" });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.organizations.store", { parent: this.parent.id }));
    };
}
