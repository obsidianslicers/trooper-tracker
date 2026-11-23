import type { Trooper } from "$lib/components/models/TrooperPickerViewModel.svelte";
import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { AssignTrooperPageData, AwardSummary, IAssignTrooperForm } from "../types";

export class AssignTrooperViewModel
    extends SubmitableViewModel<AssignTrooperViewModel, IAssignTrooperForm>
    implements ISubmitableViewModel<IAssignTrooperForm> {

    award: AwardSummary;
    trooper: Trooper | null = $state(null);

    constructor(pageData: AssignTrooperPageData) {
        super();

        this.award = pageData.award;
        this.form = useForm<IAssignTrooperForm>({
            trooper_id: null,
            award_date: pageData.award_date,
        });
    }

    get cancel_url(): string {
        return getRoute("admin.awards.troopers.index", { award: this.award.id! });
    }

    pick = (trooper: Trooper | null) => {
        this.trooper = trooper;
        this.form.trooper_id = trooper?.id ?? null;
    };

    submit = (e: Event) => {
        e.preventDefault();

        this.form.trooper_id = this.trooper?.id ?? null;
        this.form.post(getRoute("admin.awards.troopers.store", { award: this.award.id! }));
    };
}
