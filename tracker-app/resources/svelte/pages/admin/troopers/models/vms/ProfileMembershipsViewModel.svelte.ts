import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
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

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-memberships", { trooper: this.trooper_id }),
            { preserveScroll: true, preserveState: true },
        );
    };

    remove = (organization_id: number, name: string) => {
        if (!confirm(`Remove this trooper from ${name}?`)) {
            return;
        }

        router.post(
            getRoute("admin.troopers.remove-membership", {
                trooper: this.trooper_id,
                organization: organization_id,
            }),
            {},
            { preserveScroll: true },
        );
    };
}
