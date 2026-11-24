import type { Option } from "$lib/domains/types.svelte";
import type { SelectableOrganization } from "$lib/components/models/OrganizationSelectionViewModel.svelte";

/** SetupTrooperResource. */
export type SetupTrooper = {
    email: string | null;
    legal_name: string | null;
    theme: string;
    notification_frequency: string;
    theme_options: Option[];
    frequency_options: Option[];
};

/** SetupMembershipResource. */
export type SetupMembership = { id: number; name: string; assignment: string };

/** SetupPageData. */
export type SetupPageData = { trooper: SetupTrooper; memberships: SetupMembership[] };

/** DeniedRequestResource. */
export type DeniedRequest = {
    id: number;
    club: string | null;
    organization_id: number;
    identifier: string | null;
    region_id: number | null;
    unit_id: number | null;
};

/** DeniedPageData. */
export type DeniedPageData = {
    denial_reason: string | null;
    denied_requests: DeniedRequest[];
    organizations: SelectableOrganization[];
    account_type: "member" | "visitor" | "handler";
};
