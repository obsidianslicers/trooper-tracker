<script lang="ts">
    import type { ListApprovalsViewModel } from "../models/vms";
    import TrooperApprovalCard from "./TrooperApprovalCard.svelte";

    interface Props {
        vm: ListApprovalsViewModel;
    }

    let { vm }: Props = $props();
</script>

<div class="mb-5">
    <h5>Trooper Approvals</h5>

    {#if vm.trooper_approvals.length > 0}
        <p class="text-muted small mb-3">
            {vm.trooper_approvals.length} trooper(s) awaiting review and activation.
        </p>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
            {#each vm.trooper_approvals as trooper_approval (trooper_approval.id)}
                <div class="col">
                    <TrooperApprovalCard approval={trooper_approval} />
                </div>
            {/each}
        </div>
    {:else}
        <p class="text-muted small mb-3">
            <i class="fa fa-fw fa-circle-check text-success me-2"></i>
            No troopers awaiting review and activation.
        </p>
    {/if}
</div>
