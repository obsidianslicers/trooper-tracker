import { ViewModel } from "$lib/domains/types.svelte";
import type { TrooperApproval } from "./PendingTrooperApprovalViewModel.svelte";
import type { TrooperRequest } from "./PendingTrooperRequestViewModel.svelte";

export type ListApprovalsPageData = {
    trooper_approvals: TrooperApproval[];
    trooper_requests: TrooperRequest[];
};

export class ListApprovalsViewModel extends ViewModel {
    trooper_approvals: TrooperApproval[] = $state([]);
    trooper_requests: TrooperRequest[] = $state([]);

    constructor(pageData?: ListApprovalsPageData) {
        super();
        this.trooper_approvals = pageData?.trooper_approvals || [];
        this.trooper_requests = pageData?.trooper_requests || [];
    }
}