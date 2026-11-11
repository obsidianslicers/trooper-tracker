import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ProfileDetailsPageData } from "../profile-types";

export type ProfileDetailsForm = {
    legal_name: string;
    display_name: string;
    email: string;
    phone: string;
    membership_status: string;
};

export type UntrustedForm = {
    untrusted_reason: string;
    review_costumes: boolean;
};

const reloadOptions = { preserveScroll: true, preserveState: true };

export class ProfileDetailsViewModel extends SubmitableViewModel<ProfileDetailsViewModel, ProfileDetailsForm> {
    untrusted = useForm<UntrustedForm>({ untrusted_reason: "", review_costumes: false });

    constructor(
        public readonly trooper_id: number,
        public readonly details: ProfileDetailsPageData,
    ) {
        super();
        this.form = useForm<ProfileDetailsForm>({
            legal_name: details.legal_name,
            display_name: details.display_name,
            email: details.email,
            phone: details.phone ?? "",
            membership_status: details.membership_status,
        });
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-profile", { trooper: this.trooper_id }),
            reloadOptions,
        );
    };

    markUntrusted = (e: Event) => {
        e.preventDefault();
        this.untrusted.post(
            getRoute("admin.troopers.untrusted", { trooper: this.trooper_id }),
            reloadOptions,
        );
    };

    clearUntrusted = () => {
        this.untrusted.post(
            getRoute("admin.troopers.trusted", { trooper: this.trooper_id }),
            reloadOptions,
        );
    };

    action = (route_name: "void" | "unvoid" | "rip" | "unrip") => {
        router.post(
            getRoute(`admin.troopers.${route_name}`, { trooper: this.trooper_id }),
            {},
            reloadOptions,
        );
    };
}
