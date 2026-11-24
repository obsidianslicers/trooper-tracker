import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { SetupTrooper } from "../onboarding-types";

export type SetupForm = {
    email: string | null;
    legal_name: string | null;
    theme: string;
    notification_frequency: string;
};

/** The first-time setup form: confirms the trooper's email and name, and their preferences. */
export class SetupViewModel extends SubmitableViewModel<SetupViewModel, SetupForm> {
    constructor(trooper: SetupTrooper) {
        super();
        this.form = useForm<SetupForm>({
            email: trooper.email,
            legal_name: trooper.legal_name,
            theme: trooper.theme,
            notification_frequency: trooper.notification_frequency,
        });
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(getRoute("account.setup-submit"));
    };
}
