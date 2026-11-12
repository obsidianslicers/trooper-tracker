import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { ProfileGuardianPageData } from "../profile-types";

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
