import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type MembershipLookupResult = {
    identifier: string | null;
    primary_organization: PrimaryOrganization;
    existing_trooper_membership: ExistingTrooperMembership | null;
    service_name: string;
    member: any;
};

type PrimaryOrganization = {
    id: number;
    name: string;
};

type ExistingTrooperMembership = {
    id: number;
    legal_name: string;
    display_name: string;
    membership_status: string;
};

export class MembershipLookupViewModel extends ViewModel {
    trooper_request_id: number = $state(0);
    performing_lookup: boolean = $state(true);
    lookup: MembershipLookupResult | null = $state(null);
    constructor(trooper_request_id: number) {
        super();
        this.trooper_request_id = trooper_request_id;
        this.lookupMembership();
    }

    get existing_trooper_route(): string | null {
        if (this.lookup != null && this.lookup.existing_trooper_membership != null) {
            const params = { trooper: this.lookup.existing_trooper_membership.id };
            return getRoute('admin.troopers.profile', params);
        }
        return null;
    }


    lookupMembership = async () => {
        this.performing_lookup = true;

        const url = getRoute('admin.troopers.approvals.lookup-membership', {
            trooper_request: this.trooper_request_id,
        });

        try {
            this.lookup = await fetch(url).then((res) => res.json());
        } finally {
            this.performing_lookup = false;
        }
    }
}