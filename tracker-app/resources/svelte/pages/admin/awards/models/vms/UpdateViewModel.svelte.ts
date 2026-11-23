import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { Award, UpdatePageData } from "../types";

type RenameForm = { name: string };

/** Only an award's name changes once it's created. */
export class UpdateViewModel
    extends SubmitableViewModel<UpdateViewModel, RenameForm>
    implements ISubmitableViewModel<RenameForm> {

    award: Award;
    trooper_stamps: ITrooperStamps | null = $state(null);

    constructor(pageData: UpdatePageData) {
        super();

        this.award = pageData.award;
        this.trooper_stamps = pageData.award.trooper_stamps;
        this.form = useForm<RenameForm>({ name: pageData.award.name });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.awards.update", { award: this.award.id! }));
    };
}
