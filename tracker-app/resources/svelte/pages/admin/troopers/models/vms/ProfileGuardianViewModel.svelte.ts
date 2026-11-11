import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ProfileCostumesPageData, ProfileGuardianPageData } from "../profile-types";

export type ProfileGuardianForm = {
    date_of_birth: string | null;
    guardian_email: string | null;
};

export class ProfileGuardianViewModel extends SubmitableViewModel<ProfileGuardianViewModel, ProfileGuardianForm> {
    constructor(
        public readonly trooper_id: number,
        guardian: ProfileGuardianPageData,
    ) {
        super();
        this.form = useForm<ProfileGuardianForm>({ ...guardian });
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-guardian", { trooper: this.trooper_id }),
            createPartialReloadOptions(),
        );
    };
}

export type CostumeReviewAction = "approve" | "deny" | "revoke" | "undo-revoke";

export class ProfileCostumesViewModel {
    constructor(public readonly costumes: ProfileCostumesPageData) {}

    review = (trooper_costume_id: number, action: CostumeReviewAction) => {
        if (
            (action === "revoke" || action === "undo-revoke") &&
            !confirm(`Are you sure you want to ${action.replace("-", " ")} this costume?`)
        ) {
            return;
        }

        router.post(
            getRoute(`admin.troopers.costumes.${action}`, { trooper_costume: trooper_costume_id }),
            {},
            { preserveScroll: true },
        );
    };
}
