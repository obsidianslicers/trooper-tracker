export type MemberLookupResult = {
    identifier: string | null;
    primary_organization: PrimaryOrganization;
    existing_trooper_membership: ExistingTrooperMembership | null;
    service_name: string | null;
    member: any | null;
};

type ExistingTrooperMembership = {
    id: number;
    legal_name: string;
    display_name: string;
    membership_status: string;
};

type PrimaryOrganization = {
    id: number;
    name: string;
};