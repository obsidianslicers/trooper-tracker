import { ViewModel } from "$lib/domains/types.svelte";

export type TrooperApproval = {
    id: number;
    is_visitor: boolean;
    is_denied: boolean;
    is_active: boolean;
    trooper_id: number;
    display_name: string;
    legal_name: string;
    email: string;
    phone: string;
    visitor_expires_at: string | null;
    visitor_expires_diff_for_humans: string | null;
    membership_role: string;
    is_minor: boolean;
    guardian: Guardian | null;
    trooper_requests: TrooperRequest[];
}

type Guardian = {
    trooper_id: number;
    display_name: string;
    legal_name: string;
    email: string;
    phone: string;
};

type TrooperRequest = {
    identifier: string;
    organization: Organization;
    primary_organization: PrimaryOrganization;
};

type Organization = {
    name: string;
    parent_name: string | null;
};

type PrimaryOrganization = {
    requires_guardian: boolean;
    name: string;
};

export class PendingTrooperApprovalViewModel extends ViewModel {
    //  already has $state from the parent
    submitting: boolean = $state(false);
    denying: boolean = $state(false);
    approval: TrooperApproval;

    constructor(pageData: TrooperApproval) {
        super();
        this.approval = pageData;
    }

    get visitation_expired(): boolean {
        return this.approval.is_visitor &&
            this.approval.visitor_expires_at !== null &&
            new Date(this.approval.visitor_expires_at) < new Date();
    }
}