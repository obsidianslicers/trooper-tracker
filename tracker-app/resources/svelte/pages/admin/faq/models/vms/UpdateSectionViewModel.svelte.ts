import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps } from "$lib/domains/types.svelte";
import { getRoute, propertyRemover } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { ISectionForm } from "../types";

function generateForm(options: Partial<UpdateSectionForm>): InertiaForm<UpdateSectionForm> {
    const data = {
        label: '',
        icon: '',
        ...options
    };

    propertyRemover(data, ['id', 'trooper_stamps']);

    return useForm<UpdateSectionForm>(data);
}

type UpdateSectionForm = ISectionForm & {
    label: string;
    icon: string;
};

export type UpdateSectionPageData = {
    section: UpdateSectionForm & {
        id: number;
        trooper_stamps: ITrooperStamps;
    };
};

export class UpdateSectionViewModel
    extends SubmitableViewModel<UpdateSectionViewModel, UpdateSectionForm>
    implements ISubmitableViewModel<ISectionForm> {

    section_id: number = $state(0);
    trooper_stamps: ITrooperStamps | null = $state(null);

    constructor(pageData: UpdateSectionPageData) {
        super();

        this.section_id = pageData.section.id;
        this.trooper_stamps = pageData.section.trooper_stamps;
        this.form = generateForm(pageData.section);
    }

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute("admin.faq.sections.update", { section: this.section_id })

        this.form.post(url);
    };
}


