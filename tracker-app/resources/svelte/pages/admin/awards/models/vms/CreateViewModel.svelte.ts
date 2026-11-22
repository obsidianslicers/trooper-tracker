import { SubmitableViewModel, type ISubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { CreatePageData, IAwardForm } from "../types";

export class CreateViewModel
    extends SubmitableViewModel<CreateViewModel, IAwardForm>
    implements ISubmitableViewModel<IAwardForm> {

    frequency_options: Option[] = $state([]);
    organization_options: Option[] = $state([]);

    constructor(pageData: CreatePageData) {
        super();

        this.frequency_options = pageData.frequency_options;
        this.organization_options = pageData.organization_options;
        this.form = useForm<IAwardForm>({
            organization_id: pageData.award.organization_id,
            frequency: pageData.award.frequency,
            name: pageData.award.name,
        });
    }

    get cancel_url(): string {
        return getRoute("admin.awards.list", { organization_id: this.form.organization_id ?? undefined });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.awards.create"));
    };
}
