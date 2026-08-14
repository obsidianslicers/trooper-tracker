import { ViewModel } from "$lib/domains/types.svelte";

export type OrganizationMembership = {
    membership_path: string;
    membership_status: string;
    identifier: string;
}

export type MembershipsPageData = {
    organization_memberships: OrganizationMembership[]
};

export class MembershipsViewModel extends ViewModel {
    organization_memberships: OrganizationMembership[] = $state([]);

    constructor(pageData?: MembershipsPageData) {
        super();
        this.organization_memberships = pageData?.organization_memberships ?? [];
    }
}