<script lang="ts">
    import { t } from "$lib/i18n";
    import type { MembershipApprovalsViewModel } from "../../models/vms";
    import TrooperApprovalCard from "./TrooperApprovalCard.svelte";

    interface Props {
        vm: MembershipApprovalsViewModel;
    }

    let { vm }: Props = $props();
</script>

<div class="mb-5">
    <h5>{t("Trooper Approvals")}</h5>

    {#if vm.trooper_approvals.length > 0}
        <p class="text-muted small mb-3">
            {t(":count trooper awaiting review and activation.|:count troopers awaiting review and activation.", {}, vm.trooper_approvals.length)}
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
            {t("No troopers awaiting review and activation.")}
        </p>
    {/if}
</div>
