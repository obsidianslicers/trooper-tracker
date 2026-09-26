import { ViewModel } from "$lib/domains/types.svelte";
import type { TrooperApproval } from "./PendingTrooperApprovalViewModel.svelte";
import type { TrooperRequest } from "./PendingTrooperRequestViewModel.svelte";

export type MembershipApprovalsPageData = {
    trooper_approvals: TrooperApproval[];
    trooper_requests: TrooperRequest[];
};

export class MembershipApprovalsViewModel extends ViewModel {
    trooper_approvals: TrooperApproval[] = $state([]);
    trooper_requests: TrooperRequest[] = $state([]);

    constructor(pageData?: MembershipApprovalsPageData) {
        super();
        this.trooper_approvals = pageData?.trooper_approvals || [];
        this.trooper_requests = pageData?.trooper_requests || [];
    }
}