import type { ITrooperStamps, Option } from "$lib/domains/types.svelte";

export type ProfileDetailsPageData = {
    legal_name: string;
    display_name: string;
    email: string;
    phone: string | null;
    membership_status: string;
    membership_status_options: Option[];
    last_active_at: string;
    is_invalid: boolean;
    is_departed: boolean;
    is_untrusted: boolean;
    untrusted_reason: string | null;
    can_mark_untrusted: boolean;
    trooper_stamps: ITrooperStamps;
};

export type ProfileAuthorityOrganization = {
    id: number;
    parent_id: number | null;
    depth: number;
    name: string;
    is_moderator: boolean;
    should_notify: boolean;
    is_member: boolean;
};

export type ProfileAuthorityPageData = {
    membership_role: string;
    role_options: Option[];
    organizations: ProfileAuthorityOrganization[];
};

export type ProfileMembershipOrganization = {
    id: number;
    name: string;
    identifier: string | null;
    membership_status: string;
    assignment_id: number | null;
    is_member: boolean;
    can_edit: boolean;
    assignment_options: Option[];
};

export type ProfileMembershipsPageData = {
    status_options: Option[];
    organizations: ProfileMembershipOrganization[];
};

export type ProfileGuardianPageData = {
    date_of_birth: string | null;
    guardian_email: string | null;
};

export type ProfileCostumeReview = {
    id: number;
    costume: string | null;
    organization: string | null;
    status: "revoked" | "pending" | "club_records" | "approved";
    by: string | null;
    can_review: boolean;
};

export type ProfileCostumesPageData = {
    attached: { name: string; organizations: string }[];
    reviews: ProfileCostumeReview[];
};

export type ProfileEvent = {
    organization: string;
    event_id: number | null;
    event_name: string | null;
    shift_date: string | null;
    is_old: boolean;
};

export type ProfileChange = {
    what: string;
    label: string | null;
    field: string;
    old_value: string | null;
    new_value: string | null;
    when: string;
    who: string;
};

export type ProfilePageData = {
    trooper_id: number;
    display_name: string;
    membership_role: string;
    membership_role_label: string;
    details: ProfileDetailsPageData;
    authority: ProfileAuthorityPageData | null;
    memberships: ProfileMembershipsPageData;
    guardian: ProfileGuardianPageData | null;
    costumes: ProfileCostumesPageData;
    events: ProfileEvent[];
    changes: ProfileChange[];
};
