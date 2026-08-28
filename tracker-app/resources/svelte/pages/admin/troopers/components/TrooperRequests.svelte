<script lang="ts">
    import type { ListApprovalsViewModel } from "../models";
    import TrooperRequestCard from "./TrooperRequestCard.svelte";

    interface Props {
        vm: ListApprovalsViewModel;
    }

    let { vm }: Props = $props();
</script>

<h5 class="mt-4">Trooper Requests</h5>

{#if vm.trooper_requests.length > 0}
    <p class="text-muted small mb-3">
        {vm.trooper_requests.length} trooper(s) requesting membership to the selected
        organizations.
    </p>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        {#each vm.trooper_requests as trooper_request (trooper_request.id)}
            <div class="col">
                <TrooperRequestCard request={trooper_request} />
            </div>
        {/each}
    </div>
{:else}
    <p class="text-muted small mb-3">
        <i class="fa fa-fw fa-circle-check text-success me-2"></i>
        No troopers requesting a membership setup with an organization.
    </p>
{/if}
