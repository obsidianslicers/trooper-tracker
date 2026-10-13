<script lang="ts">
    import type { PermissionRequest } from "../../models/vms";
    import PermissionRequestDetails from "./PermissionRequestDetails.svelte";

    interface Props {
        requests: PermissionRequest[];
    }

    let { requests }: Props = $props();

    const status_classes: Record<string, string> = {
        pending: "bg-warning text-dark",
        approved: "bg-success",
        denied: "bg-danger",
    };
</script>

<h5 class="mt-4">Your Requests</h5>

{#if requests.length > 0}
    <div class="row row-cols-1 row-cols-md-2 g-4">
        {#each requests as request (request.id)}
            <div class="col">
                <div class="card h-100">
                    <div class="card-header text-uppercase d-flex justify-content-between">
                        {request.trooper.display_name}
                        <span class={["badge ms-2", status_classes[request.status]]}>
                            {request.status}
                        </span>
                    </div>
                    <div class="card-body">
                        <PermissionRequestDetails {request} />
                    </div>
                </div>
            </div>
        {/each}
    </div>
{:else}
    <p class="text-muted small">You have not submitted any permission requests.</p>
{/if}
