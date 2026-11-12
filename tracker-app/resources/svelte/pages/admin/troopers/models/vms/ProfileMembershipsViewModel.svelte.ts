import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ProfileMembershipsPageData } from "../profile-types";

export type ProfileMembershipsForm = {
    organizations: Record<
        number,
        { identifier: string; membership_status: string; assignment: number | null }
    >;
};

export class ProfileMembershipsViewModel extends SubmitableViewModel<ProfileMembershipsViewModel, ProfileMembershipsForm> {
    constructor(
        public readonly trooper_id: number,
        public readonly memberships: ProfileMembershipsPageData,
    ) {
        super();
        this.form = useForm<ProfileMembershipsForm>({
            organizations: Object.fromEntries(
                memberships.organizations
                    .filter((o) => o.can_edit)
                    .map((o) => [
                        o.id,
                        {
                            identifier: o.identifier ?? "",
                            membership_status: o.membership_status,
                            assignment: o.assignment_id,
                        },
                    ]),
            ),
        });
    }

    submitOrganization = (e: Event, organization_id: number) => {
        e.preventDefault();
        this.form
            .transform((data) => data.organizations[organization_id])
            .post(
                getRoute("admin.troopers.update-membership", {
                    trooper: this.trooper_id,
                    organization: organization_id,
                }),
                createPartialReloadOptions(),
            );
    };

    pending_organization_id = $state<number | null>(null);
    confirm_message = $state("");
    is_confirming = $state(false);

    remove = (organization_id: number, name: string) => {
        this.pending_organization_id = organization_id;
        this.confirm_message = `Remove this trooper from ${name}?`;
        this.is_confirming = true;
    };

    cancel = () => {
        this.is_confirming = false;
        this.pending_organization_id = null;
    };

    confirm = () => {
        const organization_id = this.pending_organization_id;
        this.cancel();

        if (organization_id === null) {
            return;
        }

        router.post(
            getRoute("admin.troopers.remove-membership", {
                trooper: this.trooper_id,
                organization: organization_id,
            }),
            {},
            createPartialReloadOptions(),
        );
    };
}
