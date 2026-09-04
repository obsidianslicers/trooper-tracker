import { SubmitableViewModel, type ISubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { IItemForm } from "../types";

function generateForm(section_id: number | null): InertiaForm<CreateItemForm> {
    const data = {
        section_id: section_id,
        title: "",
        description: null,
        video_url: null,
    };
    return useForm<CreateItemForm>(data);
}

type CreateItemForm = IItemForm & {};

export type CreateItemPageData = {
    section_id: number | null;
    section_options: Option[];
};

export class CreateItemViewModel
    extends SubmitableViewModel<CreateItemViewModel, CreateItemForm>
    implements ISubmitableViewModel<IItemForm> {
    section_options: Option[] = $state([]);

    constructor(pageData: CreateItemPageData) {
        super();

        this.form = generateForm(pageData.section_id);
        this.section_options = pageData.section_options;
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.items.create");

        this.form.post(url);
    };
}
