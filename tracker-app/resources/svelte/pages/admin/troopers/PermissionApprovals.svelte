<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import PermissionRequestCard from "./components/permissions/PermissionRequestCard.svelte";
    import { PermissionApprovalsViewModel, type PermissionApprovalsPageData } from "./models";

    pageState.title = t("Permission Approvals");

    const page = usePage<PermissionApprovalsPageData>();

    let vm = new PermissionApprovalsViewModel(page.props);
</script>

{#if vm.permission_requests.length > 0}
    <p class="text-muted small mb-3">
        {t(":count permission request waiting for review.|:count permission requests waiting for review.", {}, vm.permission_requests.length)}
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
        {t("No permission requests waiting for review.")}
    </p>
{/if}
