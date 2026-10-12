<script lang="ts">
    import type { PermissionRequest } from "../../models/vms";
    import { PendingPermissionRequestViewModel } from "../../models/vms";
    import PermissionRequestCardFooter from "./PermissionRequestCardFooter.svelte";
    import PermissionRequestDetails from "./PermissionRequestDetails.svelte";

    interface Props {
        request: PermissionRequest;
    }

    let { request }: Props = $props();

    let vm = new PendingPermissionRequestViewModel(request);
</script>

<div class="card h-100">
    <div class="card-header text-uppercase d-flex justify-content-between">
        {vm.request.trooper.display_name}
        {#if vm.request.membership_role_label}
            <span class="badge bg-secondary ms-2">{vm.request.membership_role_label}</span>
        {/if}
    </div>
    <div class="card-body">
        <PermissionRequestDetails request={vm.request} />
    </div>
    <PermissionRequestCardFooter {vm} />
</div>
