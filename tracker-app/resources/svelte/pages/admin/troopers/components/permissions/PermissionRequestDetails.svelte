<script lang="ts">
    import type { PermissionRequest, PermissionRequestOrganization } from "../../models/vms";

    interface Props {
        request: PermissionRequest;
    }

    let { request }: Props = $props();
</script>

<dl class="row mb-0">
    <dt class="col-4">Trooper:</dt>
    <dd class="col-8">
        {request.trooper.display_name}
        <span class="text-muted small">({request.trooper.legal_name})</span>
    </dd>
    {#if request.requester}
        <dt class="col-4">Requested By:</dt>
        <dd class="col-8">{request.requester.display_name}</dd>
    {/if}
    <dt class="col-4">Submitted:</dt>
    <dd class="col-8">{request.created}</dd>
    {#if request.membership_role_label}
        <dt class="col-4">Role:</dt>
        <dd class="col-8">{request.membership_role_label}</dd>
    {/if}
    {#snippet organizationList(organizations: PermissionRequestOrganization[])}
        {#each organizations as organization (organization.id)}
            <div>
                {#if organization.parent_name}
                    {organization.parent_name} —
                {/if}
                {organization.name}
            </div>
        {/each}
    {/snippet}
    {#if request.moderated_organizations.length > 0}
        <dt class="col-4">Add Moderation:</dt>
        <dd class="col-8">{@render organizationList(request.moderated_organizations)}</dd>
    {/if}
    {#if (request.unmoderated_organizations ?? []).length > 0}
        <dt class="col-4">Remove Moderation:</dt>
        <dd class="col-8">{@render organizationList(request.unmoderated_organizations)}</dd>
    {/if}
    {#if request.forum_groups_to_add.length > 0}
        <dt class="col-4">Add Forum Groups:</dt>
        <dd class="col-8">
            {request.forum_groups_to_add.map((g) => g.title).join(", ")}
        </dd>
    {/if}
    {#if request.forum_groups_to_remove.length > 0}
        <dt class="col-4">Remove Forum Groups:</dt>
        <dd class="col-8">
            {request.forum_groups_to_remove.map((g) => g.title).join(", ")}
        </dd>
    {/if}
    {#if request.note}
        <dt class="col-4">Note:</dt>
        <dd class="col-8" style="white-space: pre-line">{request.note}</dd>
    {/if}
    {#if request.denial_reason}
        <dt class="col-4">Denial Reason:</dt>
        <dd class="col-8">{request.denial_reason}</dd>
    {/if}
</dl>
