import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { ProfileAuthorityOrganization, ProfileAuthorityPageData } from "../profile-types";

export type ProfileAuthorityForm = {
    membership_role: string;
    organizations: Record<number, { is_moderator: boolean; should_notify: boolean }>;
};

export class ProfileAuthorityViewModel extends SubmitableViewModel<ProfileAuthorityViewModel, ProfileAuthorityForm> {
    organizations: ProfileAuthorityOrganization[];

    constructor(
        public readonly trooper_id: number,
        public readonly authority: ProfileAuthorityPageData,
    ) {
        super();
        this.organizations = authority.organizations;
        this.form = useForm<ProfileAuthorityForm>({
            membership_role: authority.membership_role,
            organizations: Object.fromEntries(
                authority.organizations.map((o) => [
                    o.id,
                    { is_moderator: o.is_moderator, should_notify: o.should_notify },
                ]),
            ),
        });
    }

    get is_moderator_role(): boolean {
        return this.form.membership_role === "moderator";
    }

    // Moderating a parent organization implicitly covers its children.
    isCoveredByParent(organization: ProfileAuthorityOrganization): boolean {
        let parent_id = organization.parent_id;

        while (parent_id) {
            if (this.form.organizations[parent_id]?.is_moderator) {
                return true;
            }
            parent_id = this.organizations.find((o) => o.id === parent_id)?.parent_id ?? null;
        }

        return false;
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-authority", { trooper: this.trooper_id }),
            { preserveScroll: true, preserveState: true },
        );
    };
}
