import { ViewModel } from "$lib/domains/types.svelte";
import type { PermissionRequest } from "./PendingPermissionRequestViewModel.svelte";

export type PermissionApprovalsPageData = {
    permission_requests: PermissionRequest[];
};

export class PermissionApprovalsViewModel extends ViewModel {
    permission_requests: PermissionRequest[] = $state([]);

    constructor(pageData?: PermissionApprovalsPageData) {
        super();
        this.permission_requests = pageData?.permission_requests || [];
    }
}
