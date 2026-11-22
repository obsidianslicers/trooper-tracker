import { t } from "$lib/i18n";
import type { Trooper } from "$lib/components/models/TrooperPickerViewModel.svelte";
import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { RecruitPageData } from "../list-types";

type RecruitForm = {
    trooper_id: number | string | null;
    organization_id: number | null;
    identifier: string;
};

export class RecruitViewModel
    extends SubmitableViewModel<RecruitViewModel, RecruitForm>
    implements ISubmitableViewModel<RecruitForm> {

    identifiers: RecruitPageData["identifiers"];
    trooper: Trooper | null = $state(null);

    constructor(pageData: RecruitPageData) {
        super();

        this.identifiers = pageData.identifiers;
        this.form = useForm<RecruitForm>({ trooper_id: null, organization_id: null, identifier: "" });
    }

    /** What the picked unit's club calls a member's ID, marked optional when it is. */
    get identifier_label(): string | null {
        const identifier = this.form.organization_id
            ? this.identifiers[this.form.organization_id]
            : null;

        if (!identifier) {
            return null;
        }

        return identifier.is_required
            ? identifier.label
            : t(":label (optional)", { label: identifier.label });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.trooper_id = this.trooper?.id ?? null;
        this.form.post(getRoute("admin.troopers.recruit.store"), {
            onSuccess: () => {
                this.trooper = null;
                this.form.reset();
            },
        });
    };
}
