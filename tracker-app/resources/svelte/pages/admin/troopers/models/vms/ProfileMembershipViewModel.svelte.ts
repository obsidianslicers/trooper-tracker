import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ProfileMembershipOrganization } from "../profile-types";

export type ProfileMembershipForm = {
    identifier: string;
    membership_status: string;
    assignment: number | null;
};

export class ProfileMembershipViewModel extends SubmitableViewModel<ProfileMembershipViewModel, ProfileMembershipForm> {
    pending_removal = $state(false);

    constructor(
        public readonly trooper_id: number,
        public readonly organization: ProfileMembershipOrganization,
    ) {
        super();
        this.form = useForm<ProfileMembershipForm>({
            identifier: organization.identifier ?? "",
            membership_status: organization.membership_status,
            assignment: organization.assignment_id,
        });
    }

    get confirm_message(): string {
        return `Remove this trooper from ${this.organization.name}?`;
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-membership", {
                trooper: this.trooper_id,
                organization: this.organization.id,
            }),
            createPartialReloadOptions(),
        );
    };

    remove = () => {
        this.pending_removal = true;
    };

    cancel = () => {
        this.pending_removal = false;
    };

    confirm = () => {
        this.pending_removal = false;

        router.post(
            getRoute("admin.troopers.remove-membership", {
                trooper: this.trooper_id,
                organization: this.organization.id,
            }),
            {},
            createPartialReloadOptions({
                onSuccess: () => {
                    this.form.identifier = "";
                    this.form.assignment = null;
                    this.form.membership_status = "active";
                },
            }),
        );
    };
}
