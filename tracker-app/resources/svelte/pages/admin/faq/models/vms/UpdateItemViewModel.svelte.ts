import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps, type Option } from "$lib/domains/types.svelte";
import { getRoute, propertyRemover } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { IItemForm } from "../types";

function generateForm(options: Partial<UpdateItemForm>): InertiaForm<UpdateItemForm> {
    const data = {
        section_id: null,
        title: "",
        description: null,
        video_url: null,
        ...options
    };

    propertyRemover(data, ['id', 'trooper_stamps']);

    return useForm<UpdateItemForm>(data);
}

type UpdateItemForm = IItemForm & {};

export type UpdateItemPageData = {
    section_options: Option[];
    item: UpdateItemForm & {
        id: number;
        trooper_stamps: ITrooperStamps;
    };
};

export class UpdateItemViewModel
    extends SubmitableViewModel<UpdateItemViewModel, UpdateItemForm>
    implements ISubmitableViewModel<IItemForm> {

    item_id: number = $state(0);
    trooper_stamps: ITrooperStamps | null = $state(null);

    constructor(pageData: UpdateItemPageData) {
        super();

        this.item_id = pageData.item.id;
        this.trooper_stamps = pageData.item.trooper_stamps;
        this.form = generateForm(pageData.item);
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.items.update", { item: this.item_id });

        this.form.post(url);
    };
}


