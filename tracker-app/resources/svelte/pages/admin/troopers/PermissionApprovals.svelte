<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import PermissionRequestCard from "./components/permissions/PermissionRequestCard.svelte";
    import { PermissionApprovalsViewModel, type PermissionApprovalsPageData } from "./models";

    pageState.title = "Permission Approvals";

    const page = usePage<PermissionApprovalsPageData>();

    let vm = new PermissionApprovalsViewModel(page.props);
</script>

{#if vm.permission_requests.length > 0}
    <p class="text-muted small mb-3">
        {vm.permission_requests.length} permission request(s) waiting for review.
    </p>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4">
        {#each vm.permission_requests as request (request.id)}
            <div class="col">
                <PermissionRequestCard {request} />
            </div>
        {/each}
    </div>
{:else}
    <p class="text-muted small mb-3">
        <i class="fa fa-fw fa-circle-check text-success me-2"></i>
        No permission requests waiting for review.
    </p>
{/if}
